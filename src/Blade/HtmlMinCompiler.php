<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Blade;

use Akankov\HtmlMin\HtmlMin;

/**
 * Compiles `@htmlmin … @endhtmlmin` directive pairs to PHP that buffers
 * the captured render and runs it through {@see HtmlMin}.
 *
 * The block intentionally captures the *post-render* output: Blade has
 * already escaped any `{{ $expr }}` interpolations by the time the
 * buffer is closed, so the minifier never sees raw user input.
 */
final class HtmlMinCompiler
{
    public static function open(): string
    {
        return '<?php ob_start(); ?>';
    }

    public static function close(): string
    {
        return \sprintf(
            '<?php echo app(\\%s::class)->minify(ob_get_clean()); ?>',
            HtmlMin::class,
        );
    }
}
