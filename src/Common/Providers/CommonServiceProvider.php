<?php

namespace Edwin404\Common\Providers;

use Illuminate\Support\ServiceProvider;

class CommonServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../../../resources/views', 'common');
    }

    public function register()
    {
    }

}
