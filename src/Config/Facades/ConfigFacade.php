<?php

namespace Edwin404\Config\Facades;

use Illuminate\Support\Facades\Facade;

class ConfigFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'configService';
    }
}