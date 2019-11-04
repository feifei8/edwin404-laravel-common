<?php

namespace Edwin404\Shop\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class ShopServiceProvider extends ServiceProvider
{
    public function boot(Dispatcher $dispatcher)
    {
        $this->publishes([
            __DIR__ . '/../../../config/shop.php' => config_path('shop.php')
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/shop.php', 'shop'
        );
    }

}
