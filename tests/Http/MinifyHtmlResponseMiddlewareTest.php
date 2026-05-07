<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests\Http;

use Akankov\HtmlMin\HtmlMin;
use Akankov\LaravelCompressHtml\Http\MinifyHtmlResponseMiddleware;
use Akankov\LaravelCompressHtml\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MinifyHtmlResponseMiddlewareTest extends TestCase
{
    public function testMinifiesHtmlResponses(): void
    {
        $verbose = "<div>\n    <p>  hi  </p>\n</div>\n";
        $middleware = new MinifyHtmlResponseMiddleware(app(HtmlMin::class));

        $response = $middleware->handle(
            Request::create('/'),
            static fn (): Response => new Response($verbose, 200, ['Content-Type' => 'text/html']),
        );

        self::assertInstanceOf(Response::class, $response);
        $content = (string) $response->getContent();
        self::assertLessThan(\strlen($verbose), \strlen($content));
        self::assertStringContainsString('hi', $content);
        self::assertStringNotContainsString("\n", $content);
    }

    public function testLeavesJsonResponsesAlone(): void
    {
        $body = '{ "a" :   "b" }';
        $middleware = new MinifyHtmlResponseMiddleware(app(HtmlMin::class));

        $response = $middleware->handle(
            Request::create('/'),
            static fn (): Response => new Response($body, 200, ['Content-Type' => 'application/json']),
        );

        self::assertSame($body, (string) $response->getContent());
    }

    public function testLeavesHtmlResponsesWithoutContentTypeAlone(): void
    {
        $body = "<div>\n    <p>hi</p>\n</div>";
        $middleware = new MinifyHtmlResponseMiddleware(app(HtmlMin::class));

        $response = $middleware->handle(
            Request::create('/'),
            static fn (): Response => new Response($body, 200),
        );

        self::assertSame($body, (string) $response->getContent());
    }

    public function testStreamedResponsesPassThrough(): void
    {
        $streamed = new StreamedResponse(
            static fn (): int => print '<div>x</div>',
            200,
            ['Content-Type' => 'text/html'],
        );
        $middleware = new MinifyHtmlResponseMiddleware(app(HtmlMin::class));

        $response = $middleware->handle(
            Request::create('/'),
            static fn (): StreamedResponse => $streamed,
        );

        self::assertSame($streamed, $response);
    }
}
