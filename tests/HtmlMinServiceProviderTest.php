<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests;

use Akankov\HtmlMin\Config\MinifierOptions;
use Akankov\HtmlMin\HtmlMin;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use TypeError;

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

    public function testStartTagOmissionConfigKeyMapsToOptions(): void
    {
        config(['htmlmin.remove_omitted_html_start_tags' => true]);
        app()->forgetInstance(MinifierOptions::class);

        $options = app(MinifierOptions::class);

        self::assertTrue($options->removeOmittedHtmlStartTags);
    }

    public function testInlineCssConfigDrivesMinification(): void
    {
        config(['htmlmin.minify_inline_css' => true]);
        app()->forgetInstance(MinifierOptions::class);
        app()->forgetInstance(HtmlMin::class);

        $out = app(HtmlMin::class)->minify('<style>a { color: red; /* x */ }</style>');

        self::assertStringContainsString('<style>a{color:red}</style>', $out);
    }

    public function testUnknownConfigKeyThrowsHelpfulException(): void
    {
        config(['htmlmin.totally_bogus_key' => true]);
        app()->forgetInstance(MinifierOptions::class);

        $this->expectException(InvalidArgumentException::class);
        // Assert the full message: it names the offending snake_case key as it
        // appears in the published config, the file, and the fix. The exact
        // string also pins every segment of the concatenated message.
        $this->expectExceptionMessage(
            "htmlmin: unknown config key 'totally_bogus_key' in config/htmlmin.php. "
            . 'The installed akankov/html-min no longer accepts this option — '
            . 'remove the key or re-publish the config with '
            . '`php artisan vendor:publish --tag=htmlmin-config --force`.',
        );

        app(MinifierOptions::class);
    }

    public function testWronglyTypedConfigValuePropagatesOriginalError(): void
    {
        // A wrong-typed value (not an unknown key) makes the MinifierOptions
        // constructor throw a TypeError, which is not an "unknown named
        // parameter" error. The provider must rethrow it unchanged rather than
        // mislabeling it as an unknown config key.
        config(['htmlmin.remove_comments' => 'not-a-bool']);
        app()->forgetInstance(MinifierOptions::class);

        $this->expectException(TypeError::class);

        app(MinifierOptions::class);
    }

    public function testConfigIsPublishable(): void
    {
        Artisan::call('vendor:publish', ['--tag' => 'htmlmin-config', '--force' => true]);

        self::assertFileExists(config_path('htmlmin.php'));
    }
}
