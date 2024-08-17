<?php

namespace Syntech\Syntechtable\Providers;

use Illuminate\Support\ServiceProvider;
use Syntech\Syntechtable\Console\Commands\MakeTableCommand;

class SyntechTableServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('command.syntechtable.make', function () {
            return new MakeTableCommand();
        });

        $this->commands([
            'command.syntechtable.make',
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/syntechtable.php', 'syntechtable'
        );

    }


    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/syntechtable.php' => config_path('syntechtable.php'),
        ], 'config');
    }


}
