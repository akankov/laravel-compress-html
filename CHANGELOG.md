# Changelog

All notable changes to `akankov/laravel-compress-html` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial Laravel binding for `akankov/html-min` `^2.5`:
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
- GitHub Actions CI matrix on PHP 8.3 / 8.4 / 8.5 × Laravel 11 / 12,
  plus separate jobs for PHPStan, Rector dry-run, and PHP-CS-Fixer.
