<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests\Blade;

use Akankov\LaravelCompressHtml\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

final class HtmlMinDirectiveTest extends TestCase
{
    public function testBlockMinifiesItsContents(): void
    {
        $template = "@htmlmin\n<div>\n    <p>hi</p>\n</div>\n@endhtmlmin";

        $output = Blade::render($template);

        self::assertStringContainsString('<div>', $output);
        self::assertStringContainsString('hi', $output);
        self::assertStringNotContainsString("\n    ", $output);
        self::assertLessThan(\strlen($template), \strlen($output));
    }

    public function testBladeInterpolationStaysEscaped(): void
    {
        $template = '@htmlmin<p>{{ $payload }}</p>@endhtmlmin';

        $output = Blade::render($template, ['payload' => '<script>alert(1)</script>']);

        self::assertStringNotContainsString('<script>alert(1)</script>', $output);
        self::assertStringContainsString('&lt;script&gt;', $output);
    }
}
