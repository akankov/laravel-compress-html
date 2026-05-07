# Security Policy

## Supported Versions

Security fixes land on the latest tagged release. While the package is in
its `0.x` window the public API may shift in response to early feedback,
and a security fix may ship alongside breaking changes. Once `1.x` is
out, older majors will only be patched for critical issues.

| Version       | Supported |
| ------------- | --------- |
| 0.x (latest)  | ✅        |
| anything older | ❌        |

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security problems.**

Report vulnerabilities privately via GitHub's private reporting:

1. Go to the repo's [Security tab](https://github.com/akankov/laravel-compress-html/security).
2. Click **Report a vulnerability**.
3. Describe the issue, affected versions, and a proof-of-concept if you
   have one.

If GitHub's private reporting is unavailable to you, email
<akankov@gmail.com> instead.

## What to expect

- **Acknowledgement**: within 5 business days.
- **Triage & severity assessment**: within 10 business days.
- **Fix timeline**: depends on severity. Critical issues get a patch
  release as soon as a fix is verified; low-severity issues may be
  bundled into the next regular release.
- **Disclosure**: coordinated. We'll publish a GitHub Security Advisory
  (GHSA) crediting the reporter once a fix is released, unless you
  request otherwise.

## Scope

Findings in scope:

- The `@htmlmin … @endhtmlmin` Blade directive breaking Blade's autoescape
  contract — for example, allowing user-supplied HTML to bypass `{{ }}`
  escaping, or producing markup that enables XSS.
- `MinifyHtmlResponseMiddleware` corrupting response bodies in a way that
  introduces an injection or escape vector (e.g. minifying a non-HTML body
  while still returning a HTML-typed `Content-Type`).
- `HtmlMinServiceProvider` exposing services or configuration in ways that
  weaken the host application's security posture (e.g. unintended bindings,
  config publishing leaking credentials).
- Denial-of-service via pathological template input rendered through the
  directive, or pathological response bodies passed through the middleware
  (catastrophic regex, exponential blowup, unbounded memory).

Out of scope:

- Issues in `akankov/html-min` itself (report those to
  [akankov/html-min](https://github.com/akankov/html-min/security)).
- Issues in Laravel upstream (report those to the
  [Laravel security team](https://laravel.com/docs/security)).
- Issues that require a malicious maintainer to already be running code on
  your system.
- Findings in the dev-only toolchain (PHPUnit, PHPStan, Larastan, etc.)
  unless they affect the published artifact.
