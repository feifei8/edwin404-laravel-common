<?php

namespace Edwin404\Demo\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class DemoHelper
{
    public static function shouldDenyAdminDemo()
    {
        if (Session::get('_adminUserId', null)
            && env('ADMIN_DEMO_USER_ID', 0)
            && Session::get('_adminUserId') == env('ADMIN_DEMO_USER_ID', 0)
        ) {
            return true;
        }
        return false;
    }
}