<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Http;

use Akankov\HtmlMin\HtmlMin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Buffers `text/html` responses and runs them through {@see HtmlMin}.
 * Mirrors {@see \Akankov\HtmlMin\Middleware\MinifierMiddleware}'s
 * content-type policy: split on `;`, lowercase, allowlist match on
 * `text/html`. Streamed and non-text/html responses pass through.
 *
 * Opt-in: register manually in `app/Http/Kernel.php` or per-route group.
 * The service provider does not push it onto the global stack.
 */
final readonly class MinifyHtmlResponseMiddleware
{
    public function __construct(private HtmlMin $minifier)
    {
    }

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        /** @var SymfonyResponse $response */
        $response = $next($request);

        if (!$this->shouldMinify($response)) {
            return $response;
        }

        $response->setContent($this->minifier->minify((string) $response->getContent()));

        return $response;
    }

    private function shouldMinify(SymfonyResponse $response): bool
    {
        if (!$response instanceof Response) {
            return false;
        }

        $header = $response->headers->get('Content-Type', '');
        if ($header === null || $header === '') {
            return false;
        }

        $type = strtolower(trim(explode(';', $header, 2)[0]));

        return $type === 'text/html';
    }
}
