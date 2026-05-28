<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests;

use Akankov\HtmlMin\Config\MinifierOptions;
use Akankov\HtmlMin\HtmlMin;
use Illuminate\Support\Facades\Artisan;

final class HtmlMinServiceProviderTest extends TestCase
{
    public function testHtmlMinIsBoundAsSingleton(): void
    {
        $first = app(HtmlMin::class);
        $second = app(HtmlMin::class);

        self::assertSame($first, $second);
    }

    public function testMinifierOptionsBuildFromConfig(): void
    {
        config(['htmlmin.remove_comments' => false]);
        app()->forgetInstance(MinifierOptions::class);

        $options = app(MinifierOptions::class);

        self::assertFalse($options->removeComments);
    }

    public function testSnakeCaseConfigKeysMapToCamelCaseProperties(): void
    {
        config([
            'htmlmin.optimize_via_html_dom_parser' => false,
            'htmlmin.sum_up_whitespace' => false,
            'htmlmin.remove_deprecated_type_from_script_tag' => false,
        ]);
        app()->forgetInstance(MinifierOptions::class);

        $options = app(MinifierOptions::class);

        self::assertFalse($options->optimizeViaHtmlDomParser);
        self::assertFalse($options->sumUpWhitespace);
        self::assertFalse($options->removeDeprecatedTypeFromScriptTag);
    }

    public function testInlineMinifyConfigKeysMapToOptions(): void
    {
        config([
            'htmlmin.minify_inline_css' => true,
            'htmlmin.minify_inline_js' => true,
        ]);
        app()->forgetInstance(MinifierOptions::class);

        $options = app(MinifierOptions::class);

        self::assertTrue($options->minifyInlineCss);
        self::assertTrue($options->minifyInlineJs);
    }

    public function testInlineCssConfigDrivesMinification(): void
    {
        config(['htmlmin.minify_inline_css' => true]);
        app()->forgetInstance(MinifierOptions::class);
        app()->forgetInstance(HtmlMin::class);

        $out = app(HtmlMin::class)->minify('<style>a { color: red; /* x */ }</style>');

        self::assertStringContainsString('<style>a{color:red}</style>', $out);
    }

    public function testConfigIsPublishable(): void
    {
        Artisan::call('vendor:publish', ['--tag' => 'htmlmin-config', '--force' => true]);

        self::assertFileExists(config_path('htmlmin.php'));
    }
}
