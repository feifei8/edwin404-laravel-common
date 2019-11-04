<?php

namespace Edwin404\Pay\Providers;

use Illuminate\Support\ServiceProvider;
use Latrell\Alipay\AlipayServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class PayServiceProvider extends ServiceProvider
{
    public function boot(Dispatcher $dispatcher)
    {
        $this->publishes([
            __DIR__ . '/../../../config/pay.php' => config_path('pay.php')
        ], 'config');

        $this->app->register(AlipayServiceProvider::class);

        $dispatcher->subscribe(config('pay.payListener'));
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/pay.php', 'pay'
        );
    }

}
