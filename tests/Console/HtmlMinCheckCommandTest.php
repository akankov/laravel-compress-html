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

    public function testReportsExactByteSavingsForSmallInput(): void
    {
        // 18 B in, 14 B out (savings 4, 22.2%); both under 1 KiB so they format
        // as "B". The exact line pins the savings subtraction, the percentage
        // computation, and the "B" branch of formatBytes.
        $path = $this->writeTempHtml('<div>   x   </div>');

        $exitCode = Artisan::call('html-min:check', ['file' => $path]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Reduced from 18 B to 14 B (-22.2%)', $output);
    }

    public function testZeroSavingsStillReportsMinusSign(): void
    {
        // Already-minimal input the engine leaves byte-for-byte unchanged, so
        // savings is exactly 0. The sign is "-" because savings >= 0; this pins
        // the `>= 0 ? '-' : '+'` branch that every reducing input leaves
        // untested (the engine never grows output).
        $path = $this->writeTempHtml('hello');

        $exitCode = Artisan::call('html-min:check', ['file' => $path]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Reduced from 5 B to 5 B (-0.0%)', $output);
    }

    public function testEmptyFileReportsZeroWithoutDividingByZero(): void
    {
        // An empty file makes beforeBytes 0, exercising the `beforeBytes > 0`
        // guard's else branch (percent = 0.0). Pins both the guard and the 0.0
        // fallback — without the guard this would divide by zero.
        $path = $this->writeTempHtml('');

        $exitCode = Artisan::call('html-min:check', ['file' => $path]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Reduced from 0 B to 0 B (-0.0%)', $output);
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
        // 200 × 18 B = 3600 B in, 2800 B out — both format as "KB". Assert the
        // exact line so the KB threshold, divisor, and "%.1f" formatting are
        // all pinned (a loose "contains KB" lets those mutate freely).
        $path = $this->writeTempHtml(str_repeat('<div>   x   </div>', 200));

        $exitCode = Artisan::call('html-min:check', ['file' => $path]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Reduced from 3.5 KB to 2.7 KB (-22.2%)', $output);
    }

    public function testReportsSizesInMegabytes(): void
    {
        // 100_000 × 18 B = 1.8 MB in, 1.4 MB out — both format as "MB". Exact
        // assertion pins the MB threshold and divisor.
        $path = $this->writeTempHtml(str_repeat('<div>   x   </div>', 100_000));

        $exitCode = Artisan::call('html-min:check', ['file' => $path]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Reduced from 1.7 MB to 1.3 MB (-22.2%)', $output);
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
