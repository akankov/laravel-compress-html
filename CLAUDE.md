# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`akankov/laravel-compress-html` — Laravel 11/12/13 binding for the [`akankov/html-min`](https://github.com/akankov/html-min) HTML minifier engine. All four originally-planned phases shipped (`v0.1.0` → `v0.1.1` → `v0.2.0`); the package is in maintenance mode pending real-world feedback before a `v1.0.0` cut.

Three integration surfaces, all auto-discovered via `extra.laravel.providers`:

- **`HtmlMinServiceProvider`** — singleton-binds `Akankov\HtmlMin\HtmlMin` and `Akankov\HtmlMin\Config\MinifierOptions`; publishes `config/htmlmin.php` (29 snake_case toggles → `MinifierOptions`'s camelCase properties via `Str::camel()`); registers Blade directives in `boot()`; registers the `html-min:check` Artisan command under `runningInConsole`.
- **`@htmlmin … @endhtmlmin` Blade directive** — buffers rendered Blade output through the minifier *after* Blade has escaped `{{ $expr }}` interpolations.
- **`MinifyHtmlResponseMiddleware`** — opt-in HTTP middleware that minifies `text/html` response bodies; everything else passes through. The provider does NOT push it onto the global stack.
- **`php artisan html-min:check FILE`** — Phase 4 Artisan command. Reads a file, minifies it in memory, prints the byte savings; never writes to disk.

The original implementation plan with rationale and rejected alternatives is preserved in `PLAN.md` for historical context. The parent workspace conventions in `../CLAUDE.md` apply (PHP `8.3.* || 8.4.* || 8.5.*` floor, `declare(strict_types=1)`, PHPStan level max, PHP-CS-Fixer PSR-12 + risky, `#[Override]` on inherited methods, `#[DataProvider]` on static `iterable` providers).

## Load-bearing invariants

Things that are easy to regress silently. Tests guard them; do not weaken or remove the guards.

- **Escape-before-minify ordering.** `Blade/HtmlMinCompiler::open()` emits `<?php ob_start(); ?>` and `close()` emits `<?php echo app(HtmlMin::class)->minify(ob_get_clean()); ?>`. Blade compiles `{{ $expr }}` to `<?php echo e($expr); ?>` *before* our `ob_start()` wraps it, so the buffer captures already-escaped HTML. `tests/Blade/HtmlMinDirectiveTest.php::testBladeInterpolationStaysEscaped` locks this in with an XSS payload (`<script>alert(1)</script>` → `&lt;script&gt;…`). Same shape as `twig-compress-html/tests/HtmlMinExtensionTest.php::testNestedVariablesAreEscapedInsideTag`.
- **Engine version.** `composer.json` requires `akankov/html-min: ^2.5`. The middleware adapter mirrors the engine's `MinifierMiddleware::shouldMinify()` policy (split on `;`, lowercase, allowlist `text/html`). The provider's snake-to-camel mapping depends on `MinifierOptions`'s named-arg promoted-property constructor.
- **Streamed/binary responses pass through.** `MinifyHtmlResponseMiddleware::shouldMinify()` rejects anything that isn't `Illuminate\Http\Response` (so `StreamedResponse`, `BinaryFileResponse` pass through) and anything whose `Content-Type` first segment isn't `text/html` (so JSON, plain text pass through). `tests/Http/MinifyHtmlResponseMiddlewareTest.php` covers JSON, streamed, and content-type-less responses — keep them green.
- **In tests, prefer the global `app()` helper over `$this->app->make(...)`.** Larastan correctly types `app(HtmlMin::class)` as `HtmlMin`, but `$this->app` is genuinely nullable in `Illuminate\Foundation\Testing\TestCase`. PHPStan level max rejects unguarded calls on it, and the analyzer's own directive forbids working around the nullability with `assert()` / `@var` / casts.
- **`composer.lock` is not committed.** Mirrors the engine and Twig sibling. CI runs `composer update` per push so the package tests against current resolutions.
- **`#[Override]` only on `register()`.** `Illuminate\Support\ServiceProvider` has a concrete `register()` but no `boot()` — the framework only calls `boot()` if a subclass declares it. Adding `#[Override]` to `boot()` will fail at load time.

## Commands cheat-sheet

```bash
composer install
vendor/bin/phpunit                              # full suite (12 tests, 24 assertions)
vendor/bin/phpunit --filter testBladeInterpolationStaysEscaped
vendor/bin/phpstan analyse                      # level max + Larastan
vendor/bin/php-cs-fixer fix --dry-run --diff    # PSR-12 + risky + 8.x migration
vendor/bin/rector process --dry-run             # UP_TO_PHP_83 + TYPE_DECLARATION + DEAD_CODE
make ci                                         # cs-check + phpstan + rector-check + test
```

CI matrix on GitHub Actions: PHP 8.3 / 8.4 / 8.5 × Laravel 11.* / 12.* / 13.* (9 test jobs) plus three single-PHP gates (PHPStan, php-cs-fixer, rector). Tests use `Orchestra\Testbench\TestCase` rather than a full Laravel app fixture; `tests/TestCase.php` registers the provider via `getPackageProviders($app)`.

## Layout

```
src/
├── HtmlMinServiceProvider.php
├── Blade/HtmlMinCompiler.php
├── Console/HtmlMinCheckCommand.php
└── Http/MinifyHtmlResponseMiddleware.php
config/htmlmin.php                          # publishable; 29 snake_case keys
tests/
├── TestCase.php                            # Testbench base
├── HtmlMinServiceProviderTest.php
├── Blade/HtmlMinDirectiveTest.php
├── Console/HtmlMinCheckCommandTest.php
├── Http/MinifyHtmlResponseMiddlewareTest.php
└── Fixtures/sample.html                    # verbose HTML used by the Artisan-command test
```

## Out of scope (deliberate non-goals)

Lumen support, Octane-specific polish, a filesystem cache layer, bespoke Inertia/Livewire wrappers, a Laravel 10 back-port, and Phan in CI. See `PLAN.md` § "Out of scope" for the original reasoning. Push back if a session starts drifting toward any of these.

## Toward `v1.0.0`

The package stays in `0.x` until Phase 3 has soaked through real-world use without breaking changes. Promote to `v1.0.0` only when bug reports / issues confirm the public API has stabilized — not on a calendar. `SECURITY.md` explicitly notes that `0.x` may ship security fixes alongside breaking changes; that promise tightens once `1.0.0` is out.
