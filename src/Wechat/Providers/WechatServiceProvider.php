<?php

namespace Edwin404\Wechat\Providers;

use Edwin404\Wechat\Services\WechatService;
use Edwin404\Wechat\Support\WechatAuthorizationServer;
use Illuminate\Support\ServiceProvider;

class WechatServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../config/wechat.php' => config_path('wechat.php')
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/wechat.php', 'wechat'
        );

        $this->app->singleton('wechatAuthorizationServer_1', WechatAuthorizationServer::class);
        $this->app->singleton('wechatService', WechatService::class);
    }
}