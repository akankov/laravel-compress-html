<?php

declare(strict_types=1);

namespace Akankov\LaravelCompressHtml\Tests;

use Akankov\LaravelCompressHtml\HtmlMinServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param Application $app
     *
     * @return list<class-string>
     */
    #[Override]
    protected function getPackageProviders($app): array
    {
        return [HtmlMinServiceProvider::class];
    }
}
