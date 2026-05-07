[![CI](https://github.com/akankov/laravel-compress-html/actions/workflows/ci.yml/badge.svg)](https://github.com/akankov/laravel-compress-html/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/akankov/laravel-compress-html/v)](https://packagist.org/packages/akankov/laravel-compress-html)
[![Monthly Downloads](http://poser.pugx.org/akankov/laravel-compress-html/d/monthly)](https://packagist.org/packages/akankov/laravel-compress-html)
[![Dependents](http://poser.pugx.org/akankov/laravel-compress-html/dependents)](https://packagist.org/packages/akankov/laravel-compress-html)
[![License](http://poser.pugx.org/akankov/laravel-compress-html/license)](https://packagist.org/packages/akankov/laravel-compress-html)

# laravel-compress-html

Laravel integration for [`akankov/html-min`](https://packagist.org/packages/akankov/html-min) — adds a Blade `@htmlmin` block directive, an opt-in HTML response middleware, and a publishable config-driven service provider.

## Requirements

- PHP `8.3.* || 8.4.* || 8.5.*`
- Laravel 11.x, 12.x, or 13.x
- `akankov/html-min` `^2.5`

## Install

```sh
composer require akankov/laravel-compress-html
```

The service provider is registered automatically via Laravel's package auto-discovery (`extra.laravel.providers` in `composer.json`); no manual `config/app.php` edit is needed.

Optionally publish the config file to tune the 29 minifier toggles:

```sh
php artisan vendor:publish --tag=htmlmin-config
```

This drops `config/htmlmin.php` into your application — every key defaults to the engine's default, so you only need to edit the ones you want to flip.

## Blade directive

```blade
@htmlmin
<div>
    <p>{{ $user->name }}</p>
</div>
@endhtmlmin
```

The block captures rendered output, then minifies it. Variables interpolated via `{{ $expr }}` are escaped by Blade *before* the buffer reaches the minifier, so it's safe to interpolate user data inside.

## Response middleware

Opt-in: the service provider does **not** push the middleware onto the global stack — register it explicitly where you want it.

Globally, in `bootstrap/app.php` (Laravel 11+):

```php
use Akankov\LaravelCompressHtml\Http\MinifyHtmlResponseMiddleware;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    // …
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(MinifyHtmlResponseMiddleware::class);
    })
    // …
    ->create();
```

Or per-route / per-group:

```php
use Akankov\LaravelCompressHtml\Http\MinifyHtmlResponseMiddleware;

Route::middleware(MinifyHtmlResponseMiddleware::class)
    ->group(function (): void {
        // routes whose responses should be minified
    });
```

The middleware only touches `Illuminate\Http\Response` instances whose `Content-Type` first segment is `text/html`. JSON, streamed, and binary responses pass through unchanged.

## Configuration

Every key in `config/htmlmin.php` is a snake_case mirror of a property on `Akankov\HtmlMin\Config\MinifierOptions`. The provider converts them with `Str::camel()` when constructing the options object, so:

```php
'remove_comments'    => true,    // → MinifierOptions::$removeComments
'sum_up_whitespace'  => true,    // → MinifierOptions::$sumUpWhitespace
'optimize_attributes' => true,   // → MinifierOptions::$optimizeAttributes
```

See the published config file for the full list of 29 keys with their defaults.

## Tests

```sh
composer install
vendor/bin/phpunit
make ci   # cs-check + phpstan + rector-check + test
```

## License

MIT
