# Changelog

All notable changes to `akankov/laravel-compress-html` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **`remove_omitted_html_start_tags` config key.** Exposes engine v2.9.0's
  opt-in `<html>`/`<head>`/`<body>` start-tag omission
  (`MinifierOptions::$removeOmittedHtmlStartTags`). Off by default, matching
  the engine default.
- **Options parity guard.** A reflection-based test
  (`tests/OptionsParityTest.php`) asserts that `config/htmlmin.php` exposes
  exactly the constructor surface of the engine's `MinifierOptions` and that
  every default mirrors the engine verbatim, so an engine release that adds,
  renames, or re-defaults an option now fails CI instead of drifting
  silently. (This guard is what surfaced the missing v2.9.0 key above.)

### Changed

- Engine requirement raised from `akankov/html-min ^2.8` to `^2.9` — the new
  config key maps to a named constructor argument that only exists from
  v2.9.0.

## [2.0.0] — 2026-06-07

Major version: drops support for an EOL framework (see below). No change to the
package's own API, Blade directive, middleware, or config — a Laravel 12 / 13
app upgrading from 1.0.0 needs no code changes.

### Removed

- **Dropped Laravel 11 support.** Laravel 11's security-support window has closed,
  and it is now permanently flagged by an upstream advisory (CVE-2026-48019, a
  CRLF injection in Laravel's default email rule — unrelated to this package)
  with no 11.x backport, so Composer can no longer install it. The supported
  range is now Laravel 12.x / 13.x: the `illuminate/*` constraints drop `^11.0`,
  `orchestra/testbench` drops `^9.0`, and the CI matrix drops `11.*`. Laravel 12
  and 13 are unaffected (the fix shipped in 12.60.0 / 13.10.0).

## [1.0.0] — 2026-06-01

First **stable** release. The public surface — the `@htmlmin` Blade directive,
`MinifyHtmlResponseMiddleware`, the `html-min:check` Artisan command, the
published `config/htmlmin.php` keys, and the service-provider bindings — is now
covered by a Semantic-Versioning stability promise: breaking changes are
reserved for a future major version. No behavioural change for existing users
upgrading from 0.4.0.

### Added

- **Line-coverage gate at 100%.** A `make coverage` target and a CI coverage job
  (pcov) enforce the floor via `bin/coverage-check.php`, matching the engine's
  measured-quality standard. New tests cover the Blade compiler directly
  (Blade's view cache shadowed it through the end-to-end test) and the
  `html-min:check` byte-formatting (KB/MB) and error paths.
- **Documentation** for the `html-min:check` Artisan command and a `Versioning`
  policy section in the README.

### Changed

- Simplified `HtmlMinCheckCommand` file reading to a single guarded
  `@file_get_contents()` (dropping a redundant `is_file()`/`is_readable()`
  pre-check), mirroring the engine's `Cli::readInput()`. Behaviour is unchanged —
  same error message and exit code 1 on an unreadable path.

## [0.4.0] — 2026-05-31

Tracks the latest engine: requires `akankov/html-min` **^2.8** (was ^2.6). Since
2.6 the engine gained 100% line coverage, mutation-tested hardening, an internal
decomposition, and an HTML-parser cleanup — all behaviour-preserving for the
well-formed output this binding produces. No API change to the Blade directive,
middleware, or service provider.

### Changed

- Bump the `akankov/html-min` requirement from `^2.6` to `^2.8` (and correct the
  README requirements line, which still listed `^2.5`).

## [0.3.0] — 2026-05-28

Exposes the opt-in inline CSS/JS minification toggles added in
`akankov/html-min` 2.6 through the publishable config. Released from PR
[#1](https://github.com/akankov/laravel-compress-html/pull/1).

### Added

- `minify_inline_css` and `minify_inline_js` config keys (both default `false`),
  mapping to the `doMinifyInlineCss()` / `doMinifyInlineJs()` toggles in
  `akankov/html-min`. Enable to minify the contents of inline `<style>` /
  `<script>` blocks. Requires `akankov/html-min` ^2.6.

## [0.2.0] — 2026-05-07

### Added

- `php artisan html-min:check FILE` Artisan command — minifies the
  given HTML file in memory and prints the byte savings (e.g.
  `Reduced from 12.4 KB to 9.1 KB (-26.6%)`). Useful as a CI/dev
  smoke-check; never writes back to disk. Auto-registered by the
  service provider when running in the console.

## [0.1.1] — 2026-05-07

### Added

- README badges: CI status, latest stable version, monthly downloads,
  dependents, license. Mirrors the `akankov/html-min` badge cluster.
- Community standards files: `CODE_OF_CONDUCT.md` (Contributor
  Covenant 2.1), `CONTRIBUTING.md`, `SECURITY.md`, GitHub issue
  templates (bug, feature, security contact link), and a
  pull-request template.

## [0.1.0] — 2026-05-07

### Added

- Initial Laravel binding for `akankov/html-min` `^2.5`. Supports
  Laravel 11.x, 12.x, and 13.x on PHP 8.3 / 8.4 / 8.5.
  - `HtmlMinServiceProvider` — singleton-binds `HtmlMin` and
    `MinifierOptions`, publishes `config/htmlmin.php` (snake_case
    keys mapped to `MinifierOptions`'s camelCase properties).
  - `@htmlmin … @endhtmlmin` Blade directive — buffers rendered
    output and minifies the captured HTML; Blade-escaped variables
    remain escaped.
  - `MinifyHtmlResponseMiddleware` — opt-in HTTP middleware that
    minifies `text/html` responses on the way out. Streamed and
    non-`text/html` responses pass through untouched.
- Quality toolchain mirroring `akankov/twig-compress-html`: PHPStan
  level max with Larastan, PHP-CS-Fixer with PSR-12 + PHP 8.3 +
  PHPUnit 10 migration rule sets, Rector (`UP_TO_PHP_83` +
  `TYPE_DECLARATION` + `DEAD_CODE`), PHPUnit with strict `failOn`
  settings.
- GitHub Actions CI matrix on PHP 8.3 / 8.4 / 8.5 × Laravel 11 / 12 /
  13, plus separate jobs for PHPStan, Rector dry-run, and PHP-CS-Fixer.
- `.github/FUNDING.yml` with the Ko-fi link, matching
  `akankov/html-min`.
