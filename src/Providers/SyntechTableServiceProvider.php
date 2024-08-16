<?php

namespace Syntech\SyntechTable\Providers;

use Illuminate\Support\ServiceProvider;
use SyntechTable\Commands\MakeTableCommand;

class SyntechTableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/syntechtable.php' => config_path('syntechtable.php'),
            ], 'config');

            $this->commands([
                MakeTableCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/syntechtable.php', 'syntechtable');
    }
}
