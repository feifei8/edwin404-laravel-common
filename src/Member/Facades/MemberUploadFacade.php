<?php

namespace Edwin404\Member\Facades;

use Illuminate\Support\Facades\Facade;

class MemberUploadFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'memberUploadService';
    }
}