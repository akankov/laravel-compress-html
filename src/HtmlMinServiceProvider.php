<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml;

use Akankov\HtmlMin\Config\MinifierOptions;
use Akankov\HtmlMin\HtmlMin;
use Akankov\LaravelCompressHtml\Blade\HtmlMinCompiler;
use Akankov\LaravelCompressHtml\Console\HtmlMinCheckCommand;
use Error;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Override;

final class HtmlMinServiceProvider extends ServiceProvider
{
    public const string CONFIG_KEY = 'htmlmin';

    public const string PUBLISH_TAG = 'htmlmin-config';

    private const string CONFIG_PATH = __DIR__ . '/../config/htmlmin.php';

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, self::CONFIG_KEY);

        $this->app->singleton(MinifierOptions::class, static function (Application $app): MinifierOptions {
            /** @var array<string, mixed> $config */
            $config = $app->make(Repository::class)->get(self::CONFIG_KEY, []);

            return self::optionsFromConfig($config);
        });

        $this->app->singleton(
            HtmlMin::class,
            static fn (Application $app): HtmlMin => new HtmlMin($app->make(MinifierOptions::class)),
        );
    }

    public function boot(): void
    {
        Blade::directive('htmlmin', static fn (): string => HtmlMinCompiler::open());
        Blade::directive('endhtmlmin', static fn (): string => HtmlMinCompiler::close());

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [self::CONFIG_PATH => $this->app->configPath(self::CONFIG_KEY . '.php')],
                self::PUBLISH_TAG,
            );

            $this->commands([HtmlMinCheckCommand::class]);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function optionsFromConfig(array $config): MinifierOptions
    {
        $camelCased = [];
        $originalKeys = [];
        foreach ($config as $key => $value) {
            $camel = Str::camel($key);
            $camelCased[$camel] = $value;
            $originalKeys[$camel] = $key;
        }

        try {
            // @phpstan-ignore-next-line argument.type — keys/values are constrained by the published config schema; MinifierOptions's typed promoted properties enforce the final type contract.
            return new MinifierOptions(...$camelCased);
        } catch (Error $e) {
            // A published config/htmlmin.php can drift from the engine: a key the
            // installed MinifierOptions no longer accepts makes the named-arg
            // spread throw "Unknown named parameter $camelKey" on every request.
            // Rethrow naming the key as it appears in the user's config file so the
            // fix (re-publish or remove the stale key) is obvious.
            if (preg_match('/Unknown named parameter \$(\w+)/', $e->getMessage(), $m) !== 1) {
                throw $e;
            }

            $offending = $originalKeys[$m[1]] ?? Str::snake($m[1]);

            throw new InvalidArgumentException(
                "htmlmin: unknown config key '{$offending}' in config/htmlmin.php. "
                . 'The installed akankov/html-min no longer accepts this option — '
                . 'remove the key or re-publish the config with '
                . '`php artisan vendor:publish --tag=' . self::PUBLISH_TAG . ' --force`.',
                previous: $e,
            );
        }
    }
}
