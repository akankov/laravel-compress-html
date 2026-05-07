<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Console;

use Akankov\HtmlMin\HtmlMin;
use Illuminate\Console\Command;

/**
 * `php artisan html-min:check path/to/file.html` — minifies the given
 * file in memory and prints the byte savings. Useful as a CI/dev
 * smoke-check ("did our last template change inflate the page?")
 * without modifying the file on disk.
 */
final class HtmlMinCheckCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'html-min:check {file : Path to an HTML file to minify and measure}';

    /**
     * @var string
     */
    protected $description = 'Minify an HTML file and report the byte savings without writing changes to disk.';

    public function handle(HtmlMin $minifier): int
    {
        $file = $this->argument('file');
        if (!\is_string($file) || !is_file($file) || !is_readable($file)) {
            $this->components->error(\sprintf(
                'Cannot read input file: %s',
                \is_string($file) ? $file : '<missing>',
            ));

            return self::FAILURE;
        }

        $original = file_get_contents($file);
        if ($original === false) {
            $this->components->error(\sprintf('Cannot read input file: %s', $file));

            return self::FAILURE;
        }

        $minified = $minifier->minify($original);
        $beforeBytes = \strlen($original);
        $afterBytes = \strlen($minified);
        $savings = $beforeBytes - $afterBytes;
        $percent = $beforeBytes > 0 ? ($savings / $beforeBytes) * 100 : 0.0;

        $this->components->info(\sprintf(
            'Reduced from %s to %s (%s%.1f%%)',
            self::formatBytes($beforeBytes),
            self::formatBytes($afterBytes),
            $savings >= 0 ? '-' : '+',
            abs($percent),
        ));

        return self::SUCCESS;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return \sprintf('%.1f MB', $bytes / 1024.0 / 1024.0);
        }

        if ($bytes >= 1024) {
            return \sprintf('%.1f KB', $bytes / 1024.0);
        }

        return $bytes . ' B';
    }
}
