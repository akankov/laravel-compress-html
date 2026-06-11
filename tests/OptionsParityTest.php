<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests;

use Akankov\HtmlMin\Config\MinifierOptions;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Drift guard between the engine's option surface and the published config.
 *
 * `config/htmlmin.php` promises two things: every MinifierOptions constructor
 * parameter has a snake_case key, and every default mirrors the engine
 * verbatim (so publishing the config is a behavioral no-op). Nothing else
 * enforces that promise: `composer update` pulling an engine release that
 * adds an option would otherwise leave the new option silently unreachable
 * from config. This test makes that drift a CI failure.
 *
 * Deliberately a plain PHPUnit test (no Testbench container) — the contract
 * under test is between two files, not the framework wiring.
 */
final class OptionsParityTest extends TestCase
{
    public function testConfigExposesExactlyTheEngineOptionSurface(): void
    {
        $config = self::publishedConfig();

        $engineKeys = [];
        foreach ((new ReflectionMethod(MinifierOptions::class, '__construct'))->getParameters() as $parameter) {
            $engineKeys[] = self::snakeCase($parameter->getName());
        }

        self::assertEqualsCanonicalizing(
            $engineKeys,
            array_keys($config),
            'config/htmlmin.php keys no longer match the constructor surface of MinifierOptions.',
        );
    }

    public function testConfigDefaultsMirrorTheEngineDefaultsVerbatim(): void
    {
        $config = self::publishedConfig();

        foreach ((new ReflectionMethod(MinifierOptions::class, '__construct'))->getParameters() as $parameter) {
            $key = self::snakeCase($parameter->getName());
            if (!\array_key_exists($key, $config)) {
                continue; // the key-surface test reports missing keys with a better message
            }

            self::assertSame(
                $parameter->getDefaultValue(),
                $config[$key],
                \sprintf('config/htmlmin.php default for "%s" drifted from MinifierOptions::$%s.', $key, $parameter->getName()),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function publishedConfig(): array
    {
        $config = require __DIR__ . '/../config/htmlmin.php';
        self::assertIsArray($config);

        /** @var array<string, mixed> $config */
        return $config;
    }

    private static function snakeCase(string $camelCase): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $camelCase));
    }
}
