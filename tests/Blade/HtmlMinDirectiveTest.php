<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests\Blade;

use Akankov\LaravelCompressHtml\Tests\TestCase;
use Override;

final class HtmlMinDirectiveTest extends TestCase
{
    /** @var list<string> */
    private array $tempPaths = [];

    public function testBlockMinifiesItsContents(): void
    {
        $template = "@htmlmin\n<div>\n    <p>hi</p>\n</div>\n@endhtmlmin";

        $output = $this->renderBladeView($template);

        self::assertStringContainsString('<div>', $output);
        self::assertStringContainsString('hi', $output);
        self::assertStringNotContainsString("\n    ", $output);
        self::assertLessThan(\strlen($template), \strlen($output));
    }

    public function testBladeInterpolationStaysEscaped(): void
    {
        $template = '@htmlmin<p>{{ $payload }}</p>@endhtmlmin';

        $output = $this->renderBladeView($template, ['payload' => '<script>alert(1)</script>']);

        self::assertStringNotContainsString('<script>alert(1)</script>', $output);
        self::assertStringContainsString('&lt;script&gt;', $output);
    }

    /**
     * Render a Blade snippet through a real on-disk view (the production
     * PhpEngine path) rather than Blade::render(). Blade::render() wraps the
     * string in an anonymous component whose own output buffering interleaves
     * with the @htmlmin directive's ob_start()/ob_get_clean() and leaves the
     * buffer stack unbalanced — which PHPUnit flags as a risky test. A file
     * view exercises the directive exactly as a real app would.
     *
     * @param array<string, mixed> $data
     */
    private function renderBladeView(string $template, array $data = []): string
    {
        $dir = sys_get_temp_dir() . '/htmlmin-views-' . uniqid('', true);
        mkdir($dir);
        $path = $dir . '/view.blade.php';
        file_put_contents($path, $template);
        $this->tempPaths[] = $path;
        $this->tempPaths[] = $dir;

        return view()->file($path, $data)->render();
    }

    #[Override]
    protected function tearDown(): void
    {
        $tempDir = realpath(sys_get_temp_dir());
        foreach ($this->tempPaths as $path) {
            $real = realpath($path);
            // Defensive: only ever remove paths this test created under the
            // system temp dir — they come from sys_get_temp_dir(), never input.
            if ($real === false || $tempDir === false || !str_starts_with($real, $tempDir)) {
                continue;
            }
            if (is_file($real)) {
                unlink($real); // nosemgrep: php.lang.security.unlink-use.unlink-use
            } elseif (is_dir($real)) {
                rmdir($real);
            }
        }
        $this->tempPaths = [];

        parent::tearDown();
    }
}
