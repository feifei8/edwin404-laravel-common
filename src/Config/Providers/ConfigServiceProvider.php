<?php

namespace Edwin404\Config\Providers;

use Edwin404\Config\Services\ConfigService;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('configService', ConfigService::class);
    }
}
