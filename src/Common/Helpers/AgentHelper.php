<?php

namespace Edwin404\Common\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Facades\Agent;

class AgentHelper
{

    public static function isWechat()
    {
        static $isWechat = null;
        if (null === $isWechat) {
            $userAgent = Request::header('User-Agent');
            if (strpos($userAgent, 'MicroMessenger') !== false) {
                $isWechat = true;
            } else {
                $isWechat = false;
            }
        }
        return $isWechat;
    }

    public static function isMobile()
    {
        return Agent::isPhone() || Agent::isTablet();
    }

    public static function isPC()
    {
        return !Agent::isPhone() && !Agent::isTablet();
    }

}