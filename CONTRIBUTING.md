# Contributing

Thanks for helping improve `akankov/laravel-compress-html`. This project is a
small Laravel binding wrapping [`akankov/html-min`](https://github.com/akankov/html-min),
so focused changes with clear tests are easiest to review.

## Reporting Bugs

Open a bug report when:

- The `@htmlmin` Blade directive produces incorrect or unexpected output.
- Blade's autoescape interacts incorrectly with the directive (especially:
  user-supplied content escapes the safe-by-default contract).
- `MinifyHtmlResponseMiddleware` minifies a response it should pass through,
  passes through a response it should minify, or corrupts a `text/html` body.
- `HtmlMinServiceProvider` fails to register, mis-applies configuration, or
  breaks container resolution.

Include:

- The package version or commit SHA (`composer show akankov/laravel-compress-html`).
- The PHP version and the Laravel version.
- The smallest Blade template or HTTP response that reproduces the issue.
- The contents of `config/htmlmin.php` if you've published and modified it.
- The actual rendered output and the expected output.
- Any related stack trace.

Please do not report security vulnerabilities in public issues. Use the process
in [SECURITY.md](SECURITY.md) instead.

## Requesting Features

Feature requests should describe the use case (Blade template, route group,
middleware behavior), the expected output, and why the behavior belongs in
this binding rather than in caller code or in `akankov/html-min` itself. If
the change could affect existing rendered output of the directive or
middleware, call that out clearly.

## Development Setup

Install dependencies from the repository root:

```bash
composer install
```

Useful local checks:

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/rector process --dry-run
```

Or run the full pipeline at once:

```bash
make ci
```

## Tests

Add regression coverage for behavior changes:

- `tests/HtmlMinServiceProviderTest.php` — singleton wiring, snake_case →
  camelCase config mapping, `vendor:publish` integration.
- `tests/Blade/HtmlMinDirectiveTest.php` — `@htmlmin … @endhtmlmin` block
  behavior. The `testBladeInterpolationStaysEscaped` test is load-bearing —
  it locks in the escape-before-minify ordering. Do not weaken or remove it.
- `tests/Http/MinifyHtmlResponseMiddlewareTest.php` — content-type allowlist,
  streamed-response pass-through, JSON pass-through, byte-shrink on
  `text/html`.

Tests use `orchestra/testbench` rather than a full Laravel app fixture. Keep
tests compatible with PHP 8.3. The Composer platform and Rector config are
pinned to PHP 8.3 even when local checks also run newer versions.

## Pull Requests

Before opening a pull request:

- Keep the change focused and explain the user-visible behavior.
- Add or update tests for directive, middleware, or provider changes.
- Update the README and `CHANGELOG.md` when configuration or behavior changes.
- Run `make ci` and mention any check that could not be run.
- Link the related issue when there is one.
