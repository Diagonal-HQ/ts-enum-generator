<?php

namespace Diagonal\TsEnumGenerator\Tests;

use Diagonal\TsEnumGenerator\TsEnumGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            TsEnumGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('ts-enum-generator.default_source_dir', 'tests/fixtures/enums');
        config()->set('ts-enum-generator.default_destination_dir', 'tests/output');
    }
} 