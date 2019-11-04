<?php

namespace Edwin404\Data\Facades;

use Illuminate\Support\Facades\Facade;

class DataFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dataService';
    }
}