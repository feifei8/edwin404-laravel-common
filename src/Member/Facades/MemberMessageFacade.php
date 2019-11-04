<?php

namespace Edwin404\Member\Facades;

use Illuminate\Support\Facades\Facade;

class MemberMessageFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'memberMessageService';
    }
}