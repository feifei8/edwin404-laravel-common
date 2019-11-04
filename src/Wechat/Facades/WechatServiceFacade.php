<?php

namespace Edwin404\Wechat\Facades;

use Illuminate\Support\Facades\Facade;

class WechatServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'wechatService';
    }
}