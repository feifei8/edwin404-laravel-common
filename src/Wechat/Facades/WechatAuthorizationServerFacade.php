<?php

namespace Edwin404\Wechat\Facades;

use Illuminate\Support\Facades\Facade;

class WechatAuthorizationServerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'wechatAuthorizationServer_1';
    }
}