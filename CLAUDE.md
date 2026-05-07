# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Status: pre-implementation

This directory is the **planning stage** of `akankov/laravel-compress-html`, the Laravel binding for the `akankov/html-min` engine. As of writing, the only artifact is `PLAN.md`; there is no `composer.json`, no `src/`, no test suite, and no `.git`. Read `PLAN.md` end-to-end before writing any code — it is the canonical source for package shape, dependencies, phase ordering, and TDD targets. Update `PLAN.md` (or replace it with the implementation) when reality diverges.

The parent workspace conventions in `../CLAUDE.md` apply here too (PHP `8.3.* || 8.4.* || 8.5.*` floor, `declare(strict_types=1)`, PHPStan level max, PHP-CS-Fixer PSR-12 + risky, `#[Override]`, `#[DataProvider]` on static `iterable` providers). Don't restate them in code review — enforce them.

## Package goals (from PLAN.md)

A small, focused package — roughly 300 LOC plus tests — that adds three integration surfaces and nothing else:

1. **`HtmlMinServiceProvider`** — singleton-binds `Akankov\HtmlMin\HtmlMin` and `Akankov\HtmlMin\Config\MinifierOptions`, publishes `config/htmlmin.php`.
2. **`@htmlmin … @endhtmlmin` Blade directive** — block tag that minifies a Blade chunk *after* Blade has rendered and escaped its expressions.
3. **`MinifyHtmlResponseMiddleware`** — Laravel-flavored response middleware that minifies `text/html` bodies. Opt-in only; the provider does NOT push it onto the global stack.

A `php artisan html-min:check` Artisan command is a stretch goal (Phase 4); skip it if Phases 1–3 get tight.

## Load-bearing invariants

These are the things that are easy to regress silently. Guard them with tests, not just code review.

- **Escape-before-minify ordering.** The Blade compiler must emit `<?php ob_start(); ?>` for `@htmlmin` and `<?php echo app(HtmlMin::class)->minify(ob_get_clean()); ?>` for `@endhtmlmin`. The minifier sees the *post-render* HTML, never the raw expression. Mirrors the trick in `twig-compress-html/src/Node/HtmlMinNode.php`. The PHPUnit test `testBladeInterpolationStaysEscaped` (template containing `{{ $payload }}` with `<script>alert(1)</script>`, asserts `&lt;script&gt;` survives in output) is the load-bearing test in the package — do not delete or weaken it.
- **Engine version.** `composer.json` must require `akankov/html-min: ^2.5` (not `^2.0`). The plan depends on `MinifierOptions` and the PSR-15 `MinifierMiddleware` shipped in 2.5.0. Note the parent `CLAUDE.md` flags a version skew between `html-min/CLAUDE.md` (still on v1.x docs) and the actual engine — trust the engine's source, not its CLAUDE.md, when in doubt.
- **Streamed/binary responses pass through.** `MinifyHtmlResponseMiddleware::shouldMinify()` must reject anything that isn't an `Illuminate\Http\Response` and anything whose `Content-Type` first segment isn't `text/html`. There is a dedicated test for `StreamedResponse` — keep it.
- **`composer.lock` is not committed.** Mirrors the engine and Twig sibling. CI runs `composer update` per PR so the package tests against the newest deps.

## Open questions before Phase 1 implementation

`PLAN.md` § "Open questions" lists three decisions that must be settled before code is written. Don't paper over them — surface them to the user:

1. **Package name** — `akankov/laravel-compress-html` (sibling pattern) vs. `akankov/html-min-laravel` (engine plan-doc reference).
2. **Middleware registration** — auto-pushed onto global stack vs. opt-in via the user's `Kernel.php`. Plan recommends opt-in.
3. **Config-key naming** — engine's camelCase (`removeComments`) vs. Laravel's snake_case (`remove_comments`) with provider-side conversion. Plan recommends snake_case.

If the user has not already chosen, ask before scaffolding `composer.json` and `config/htmlmin.php`.

## Commands cheat-sheet

The Makefile and `composer.json` will exist once Phase 1 lands. Until then there is nothing to run. After Phase 5 the expected targets are:

```bash
# Once composer.json exists
composer install
vendor/bin/phpunit                                      # full suite
vendor/bin/phpunit --filter testBladeInterpolationStaysEscaped   # single test
vendor/bin/phpstan analyse src tests                    # level max
vendor/bin/php-cs-fixer fix --dry-run --diff
make ci                                                 # cs-check + phpstan + test (mirrors twig sibling)
```

Tests use `Orchestra\Testbench\TestCase` (not a full Laravel app fixture) — register the provider via `getPackageProviders($app)` in the base test case.

## Phase ordering

Phases are intentionally one-PR-one-tag; do not bundle them.

1. **Phase 1** — skeleton + `HtmlMinServiceProvider` + publishable `config/htmlmin.php`.
2. **Phase 2** — `@htmlmin` Blade directive + the escape-before-minify test.
3. **Phase 3** — `MinifyHtmlResponseMiddleware` + content-type and streamed-response tests.
4. **Phase 4** *(stretch)* — `html-min:check` Artisan command.
5. **Phase 5** — README, GitHub Actions matrix (`PHP {8.3,8.4,8.5} × Laravel {11.x,12.x}`), tag `v0.1.0`. Cut `v1.0.0` only after Phase 3 has soaked through a release or two.

## Out of scope (deliberate non-goals)

Lumen support, Octane-specific polish, a filesystem cache layer, bespoke Inertia/Livewire wrappers, and a Laravel 10 back-port branch. See `PLAN.md` § "Out of scope" for the reasoning. Push back if a session starts drifting toward any of these.
