# `akankov/laravel-compress-html` — Implementation Plan

Sibling Composer package that wraps `akankov/html-min` (the engine) for
Laravel applications. Mirrors the shape of `twig-compress-html/` —
small surface area, thin glue, all heavy lifting delegated to the
engine.

## Context

`akankov/html-min` v2.5.0 ships with everything a Laravel binding
needs:

- `Akankov\HtmlMin\HtmlMin` — the engine itself
- `Akankov\HtmlMin\Config\MinifierOptions` — readonly value object that
  holds the 29 toggles; built from a flat array, perfect for mapping
  to a Laravel config file
- `Akankov\HtmlMin\Middleware\MinifierMiddleware` — already a PSR-15
  middleware, but Laravel uses its own middleware contract; we provide
  a Laravel-flavored adapter rather than asking users to bridge PSR-15
  themselves

The plan is to ship a small, focused package (~300 LOC + tests) that
adds three integration surfaces and nothing more:

1. **Service provider** — wires `HtmlMin` and `MinifierOptions` into
   the container, publishes a config file.
2. **Blade directive** — `@htmlmin … @endhtmlmin` block tag for
   inline-minifying a Blade template chunk.
3. **HTTP middleware** — Laravel-flavored response minification for
   the global stack (or scoped to specific route groups).

An optional Artisan command falls out almost for free; it's listed as
a stretch goal.

## Project shape

```
laravel-compress-html/
├── composer.json
├── README.md
├── CHANGELOG.md
├── LICENSE
├── Makefile
├── phpunit.xml.dist
├── phpstan.neon
├── .php-cs-fixer.dist.php
├── .github/
│   └── workflows/
│       └── ci.yml
├── config/
│   └── htmlmin.php                     # publishable defaults
├── src/
│   ├── HtmlMinServiceProvider.php
│   ├── Blade/
│   │   └── HtmlMinCompiler.php         # the @htmlmin block compiler
│   ├── Http/
│   │   └── MinifyHtmlResponseMiddleware.php
│   └── Console/
│       └── HtmlMinCheckCommand.php     # stretch goal
└── tests/
    ├── TestCase.php                    # extends Orchestra\Testbench\TestCase
    ├── HtmlMinServiceProviderTest.php
    ├── Blade/
    │   └── HtmlMinDirectiveTest.php
    ├── Http/
    │   └── MinifyHtmlResponseMiddlewareTest.php
    └── Console/
        └── HtmlMinCheckCommandTest.php
```

## Composer manifest

```json
{
  "name": "akankov/laravel-compress-html",
  "description": "Laravel integration for the akankov/html-min HTML minifier (Blade directive, response middleware, service provider).",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": "8.3.* || 8.4.* || 8.5.*",
    "akankov/html-min": "^2.5",
    "illuminate/support": "^11.0 || ^12.0",
    "illuminate/contracts": "^11.0 || ^12.0",
    "illuminate/http": "^11.0 || ^12.0",
    "illuminate/view": "^11.0 || ^12.0"
  },
  "require-dev": {
    "orchestra/testbench": "^9.0 || ^10.0",
    "phpunit/phpunit": "^11.0 || ^12.0",
    "phpstan/phpstan": "^2.1",
    "friendsofphp/php-cs-fixer": "^3.65",
    "rector/rector": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Akankov\\LaravelCompressHtml\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Akankov\\LaravelCompressHtml\\Tests\\": "tests/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "Akankov\\LaravelCompressHtml\\HtmlMinServiceProvider"
      ]
    }
  },
  "config": {
    "platform": { "php": "8.3" },
    "sort-packages": true
  }
}
```

Two intentional posture choices:

- **PHP floor matches the engine** (`8.3.* || 8.4.* || 8.5.*`). Same
  three-line constraint, not the looser `^8.3`. Discipline established
  in the engine's v2.0 release; we inherit it.
- **Laravel constraint targets currently-supported branches only.**
  Laravel 11 (Mar 2024) and 12 (Q1 2025) are within their bug-fix
  windows at time of writing. Older versions are out of scope. If
  someone needs Laravel 10 support, that's a back-port branch, not a
  default constraint.

## Phase 1 — Skeleton + service provider (~1 day)

**Goal**: `composer require akankov/laravel-compress-html` works in a
fresh Laravel app, publishes a config file, makes `HtmlMin` available
via DI.

### Files

- `config/htmlmin.php` — flat array mirroring `MinifierOptions`'
  29 fields. Each value falls back to the corresponding
  `MinifierOptions` default. Comments map each key to the engine
  behaviour.
- `src/HtmlMinServiceProvider.php` — extends
  `Illuminate\Support\ServiceProvider`. In `register()`:
  - Merge config defaults with `mergeConfigFrom(__DIR__ . '/../config/htmlmin.php', 'htmlmin')`.
  - Bind `MinifierOptions::class` as a singleton built from the merged config.
  - Bind `HtmlMin::class` as a singleton constructed from
    the resolved `MinifierOptions`.
  In `boot()` (only when running in console):
  - `$this->publishes([__DIR__ . '/../config/htmlmin.php' => config_path('htmlmin.php')], 'htmlmin-config')`.

### TDD

- `HtmlMinServiceProviderTest::testHtmlMinIsBoundAsSingleton()`:
  resolve `HtmlMin` twice from the container, assert same instance.
- `HtmlMinServiceProviderTest::testMinifierOptionsBuildFromConfig()`:
  set `config(['htmlmin.removeComments' => false])` before resolution,
  assert the resolved `MinifierOptions::$removeComments === false`.
- `HtmlMinServiceProviderTest::testConfigIsPublishable()`:
  call the `vendor:publish` command via `Artisan::call()` against a
  Testbench app, assert `config_path('htmlmin.php')` exists.

Use `Orchestra\Testbench\TestCase` as the base class and add the
provider via `getPackageProviders($app)`.

### Verification

- `vendor/bin/phpunit` green.
- `vendor/bin/phpstan analyse src tests` clean (level max).

## Phase 2 — `@htmlmin` Blade directive (~½ day)

**Goal**: a Blade template can write

```blade
@htmlmin
<div>
    {{ $user->name }}
</div>
@endhtmlmin
```

and emit minified HTML in the rendered output. User-controlled
interpolations (`{{ $user->name }}`) must remain Blade-escaped — the
minifier sees the post-render HTML, never the raw expression.

### Implementation

`HtmlMinCompiler` exposes two static methods that Blade's `directive()`
hooks accept:

```php
final class HtmlMinCompiler
{
    public static function open(): string
    {
        return '<?php ob_start(); ?>';
    }

    public static function close(): string
    {
        // app(HtmlMin::class) so the directive picks up the bound singleton
        return '<?php echo app(\\Akankov\\HtmlMin\\HtmlMin::class)->minify(ob_get_clean()); ?>';
    }
}
```

`HtmlMinServiceProvider::boot()` registers the directives:

```php
Blade::directive('htmlmin', static fn (): string => HtmlMinCompiler::open());
Blade::directive('endhtmlmin', static fn (): string => HtmlMinCompiler::close());
```

### TDD

- `HtmlMinDirectiveTest::testBlockMinifiesItsContents()`:
  render a string template via `Blade::render()`, assert the rendered
  HTML has no leading/trailing whitespace and no inter-tag newlines.
- `HtmlMinDirectiveTest::testBladeInterpolationStaysEscaped()`:
  template with `@htmlmin <p>{{ $payload }}</p> @endhtmlmin`,
  payload `<script>alert(1)</script>`, render, assert the rendered
  HTML contains `&lt;script&gt;` (escaped) — verifying the minifier
  runs on already-escaped output, not on raw expressions.

This second test is the most important one in the package: it locks in
the ordering invariant that user input is escaped *before* the minifier
sees it. Same shape as the existing `twig-compress-html` test.

## Phase 3 — `MinifyHtmlResponseMiddleware` (~½ day)

**Goal**: register a middleware that minifies HTML response bodies on
the way out of the framework. Functionally equivalent to the engine's
PSR-15 `MinifierMiddleware`, but using Laravel's
`Illuminate\Foundation\Http\Middleware\TrimStrings`-shaped contract.

### Implementation

```php
final class MinifyHtmlResponseMiddleware
{
    public function __construct(private HtmlMin $minifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldMinify($response)) {
            return $response;
        }

        $response->setContent(
            $this->minifier->minify((string) $response->getContent()),
        );

        return $response;
    }

    private function shouldMinify(SymfonyResponse $response): bool
    {
        if (!$response instanceof Response) {
            return false; // streamed/binary responses pass through
        }
        $type = strtolower(trim(explode(';', $response->headers->get('Content-Type', ''), 2)[0]));
        return $type === 'text/html';
    }
}
```

### TDD

- `MinifyHtmlResponseMiddlewareTest::testMinifiesHtmlResponses()`:
  build a `Response` with verbose HTML, run through middleware via a
  closure that returns it, assert `$response->getContent()` is shorter
  and has no inter-tag newlines.
- `MinifyHtmlResponseMiddlewareTest::testLeavesJsonResponsesAlone()`:
  same setup with `application/json`, assert content unchanged.
- `MinifyHtmlResponseMiddlewareTest::testStreamedResponsesPassThrough()`:
  `StreamedResponse` instance — middleware returns it unchanged
  because we can't (and shouldn't) buffer streamed bodies.

### Wiring

The provider does NOT push the middleware onto the global stack —
that's a policy choice the application owns. README documents the
two registration patterns:

```php
// app/Http/Kernel.php — global
protected $middleware = [
    // ... existing ...
    \Akankov\LaravelCompressHtml\Http\MinifyHtmlResponseMiddleware::class,
];

// or routes/web.php — per route group
Route::middleware(\Akankov\LaravelCompressHtml\Http\MinifyHtmlResponseMiddleware::class)
    ->group(function () {
        // routes whose responses should be minified
    });
```

## Phase 4 — Stretch: Artisan command (~2h)

`php artisan html-min:check path/to/file.html` — minifies the file
and prints the savings (`Reduced from 12.4 KB to 9.1 KB (-26.6%)`).
Useful smoke-test in CI/dev. Implementation is roughly the engine's
`Cli` class wrapped in an `Illuminate\Console\Command` shell.

Defer if Phase 1–3 get tight; not needed for v0.1.0.

## Phase 5 — README, CI, first release (~½ day)

### README

- Install via Composer.
- Auto-discovery: `extra.laravel.providers` registers the provider; no
  manual `config/app.php` changes needed.
- Three usage sections (Blade directive, middleware, config publishing).
- Troubleshooting: streamed responses, middleware ordering vs. CSRF.
- Version compatibility table.

### CI (`.github/workflows/ci.yml`)

Matrix: PHP `[8.3, 8.4, 8.5]` × Laravel `[11.x, 12.x]`. Six jobs.
Each runs `composer install`, `vendor/bin/phpunit`,
`vendor/bin/phpstan analyse`, `vendor/bin/php-cs-fixer fix --dry-run`.

Use `orchestra/testbench` to bring up a minimal Laravel kernel for
PHPUnit; no full app needed in the test fixtures.

### First release

- Tag `v0.1.0` to signal pre-stable. Communicate "API may shift in
  response to early feedback".
- Once Phase 3 has soaked for a release or two without breaking
  changes, cut `v1.0.0`.

## Out of scope (deliberate non-goals)

- **Lumen support**. Lumen is in maintenance mode; not worth the
  complexity.
- **Octane mode polish**. The provider's singletons are stateless
  enough to survive Octane's worker model, but no explicit testing.
  Add a Phase 6 in a future minor if users report issues.
- **Filesystem cache**. Pre-minified static templates are an
  application concern; the engine has no caching layer to expose.
- **Inertia / Livewire integrations**. Both render through Blade and
  ultimately through HTTP responses, so the existing surfaces cover
  them. No bespoke wrappers.
- **Laravel 10 back-port branch**. Not unless someone asks.

## Working agreements

These mirror the engine's discipline; documented here so they survive a
session reset.

- **TDD by default**. `make test` red → green → refactor. The Blade
  test that locks in escape-before-minify is the load-bearing one.
- **`make ci` green before commit**. Same gate as the engine
  (`cs-check + phpstan + test`).
- **Per-feature releases**. Each of phases 1–3 is one PR + one tag.
  Avoid bundling.
- **Mirror the engine's `composer.lock` policy**: don't commit it.
  CI runs `composer update` per PR so the package tests against the
  newest deps.

## Open questions for the next session

These don't block the plan but should be settled before Phase 1
implementation starts.

1. **Package naming**. `akankov/laravel-compress-html` follows the
   `twig-compress-html` sibling pattern. Confirm or pick
   `akankov/html-min-laravel` (matches the engine's plan-doc
   reference).
2. **Service-class tagging**. Should the middleware be auto-registered
   via the provider, or strictly opt-in via the user's `Kernel.php`?
   Recommendation: opt-in. Auto-pushing into the global stack is the
   kind of surprise that earns a one-star review.
3. **Config-key naming convention**. Follow the engine's
   `MinifierOptions` (camelCase, no `do` prefix:
   `removeComments`, `sumUpWhitespace`) or convert to Laravel's
   snake_case style (`remove_comments`, `sum_up_whitespace`)?
   Recommendation: snake_case — matches Laravel idioms; the provider
   does the conversion when building `MinifierOptions`.

## Verification of the plan itself

When this plan is executed, the package should pass these end-to-end
checks before v0.1.0 ships:

- A fresh Laravel 12 app: `composer require
  akankov/laravel-compress-html`, then `php artisan vendor:publish
  --tag=htmlmin-config`, file appears at `config/htmlmin.php`.
- A Blade template using `@htmlmin … @endhtmlmin` renders minified
  output; user input is still escaped.
- Adding the middleware to the global stack reduces the byte-size of
  HTML responses end-to-end via a real `Http::get()` call.
- `php artisan html-min:check fixtures/big.html` reports a percentage
  reduction (if Phase 4 ships).

If any of these fail, the package is not ready for tag.
