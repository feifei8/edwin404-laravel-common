<?php

namespace Edwin404\Forum\Facades;

use Illuminate\Support\Facades\Facade;

class ForumFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'forumService';
    }
}