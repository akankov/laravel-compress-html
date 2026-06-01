<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests\Blade;

use Akankov\HtmlMin\HtmlMin;
use Akankov\LaravelCompressHtml\Blade\HtmlMinCompiler;
use PHPUnit\Framework\TestCase;

/**
 * Direct unit test of the directive compiler. The end-to-end directive test
 * exercises this through Blade, but Blade caches compiled views, so the
 * compiler methods are not reliably re-invoked per run — assert the emitted
 * PHP contract here so it is always covered and pinned.
 */
final class HtmlMinCompilerTest extends TestCase
{
    public function testOpenStartsOutputBuffering(): void
    {
        self::assertSame('<?php ob_start(); ?>', HtmlMinCompiler::open());
    }

    public function testCloseMinifiesTheBufferedRenderViaTheContainerBoundEngine(): void
    {
        $close = HtmlMinCompiler::close();

        // Emits PHP that resolves HtmlMin from the container and minifies the
        // captured buffer — so Blade-escaped interpolation is minified, never
        // raw user input.
        self::assertStringContainsString('ob_get_clean()', $close);
        self::assertStringContainsString('->minify(', $close);
        self::assertStringContainsString(HtmlMin::class, $close);
    }
}
