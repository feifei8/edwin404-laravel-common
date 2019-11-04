<?php

namespace Edwin404\Data\Providers;

use Edwin404\Data\Services\DataService;
use Illuminate\Support\ServiceProvider;

class DataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../config/data.php' => config_path('data.php')
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/data.php', 'data'
        );

        $this->app->singleton('dataService', DataService::class);
    }
}
