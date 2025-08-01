<?php

namespace Diagonal\TsEnumGenerator;

use Diagonal\TsEnumGenerator\Console\GenerateCommand;
use Illuminate\Support\ServiceProvider;

class TsEnumGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ts-enum-generator.php', 'ts-enum-generator');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ts-enum-generator.php' => config_path('ts-enum-generator.php')
        ], 'ts-enum-generator-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
            ]);
        }
    }
}
