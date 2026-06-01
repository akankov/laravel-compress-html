<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests\Console;

use Akankov\LaravelCompressHtml\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Override;

final class HtmlMinCheckCommandTest extends TestCase
{
    private const string FIXTURE = __DIR__ . '/../Fixtures/sample.html';

    /** @var list<string> */
    private array $tempFiles = [];

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

    public function testReportsSizesInKilobytes(): void
    {
        // > 1 KiB but < 1 MiB so the byte counts format as "KB".
        $path = $this->writeTempHtml(str_repeat('<div>   x   </div>', 200));

        $exitCode = Artisan::call('html-min:check', ['file' => $path]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('KB', $output);
    }

    public function testReportsSizesInMegabytes(): void
    {
        // > 1 MiB so the byte counts format as "MB".
        $path = $this->writeTempHtml(str_repeat('<div>   x   </div>', 70_000));

        $exitCode = Artisan::call('html-min:check', ['file' => $path]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('MB', $output);
    }

    private function writeTempHtml(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'htmlmin-check-') . '.html';
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    #[Override]
    protected function tearDown(): void
    {
        $tempDir = realpath(sys_get_temp_dir());
        foreach ($this->tempFiles as $path) {
            $real = realpath($path);
            // Defensive: only ever remove files this test created under the
            // system temp dir — the paths come from tempnam(sys_get_temp_dir()),
            // never from user input.
            if ($real !== false && $tempDir !== false && str_starts_with($real, $tempDir) && is_file($real)) {
                unlink($real); // nosemgrep: php.lang.security.unlink-use.unlink-use
            }
        }
        $this->tempFiles = [];

        parent::tearDown();
    }
}
