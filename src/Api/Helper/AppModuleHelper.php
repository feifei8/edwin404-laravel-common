<?php

namespace Edwin404\Api\Helper;


use Carbon\Carbon;

class AppModuleHelper
{
    public static function modulePermit($apiApp, $module)
    {
        if (empty($apiApp)) {
            return false;
        }
        if (!$apiApp['module' . $module . 'Enable']) {
            return false;
        }
        if (empty($apiApp['module' . $module . 'Expire'])) {
            return false;
        }
        $date = Carbon::parse($apiApp['module' . $module . 'Expire'])->toDateString();
        if (empty($date)) {
            return false;
        }
        $expire = strtotime($date) + 24 * 3600 - 1;
        if ($expire < time()) {
            return false;
        }
        return true;
    }

    public static function expireDate($apiApp, $module)
    {
        if (!self::modulePermit($apiApp, $module)) {
            return null;
        }
        return Carbon::parse($apiApp['module' . $module . 'Expire'])->toDateString();
    }
}