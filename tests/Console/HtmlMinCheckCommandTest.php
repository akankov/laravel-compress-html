<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests\Console;

use Akankov\LaravelCompressHtml\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

final class HtmlMinCheckCommandTest extends TestCase
{
    private const string FIXTURE = __DIR__ . '/../Fixtures/sample.html';

    public function testReportsByteSavingsForVerboseHtml(): void
    {
        $exitCode = Artisan::call('html-min:check', ['file' => self::FIXTURE]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Reduced from', $output);
        self::assertMatchesRegularExpression('/-\d+\.\d%/', $output);
    }

    public function testFailsWhenFileIsMissing(): void
    {
        $exitCode = Artisan::call('html-min:check', ['file' => '/does/not/exist.html']);
        $output = Artisan::output();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Cannot read input file', $output);
    }
}
