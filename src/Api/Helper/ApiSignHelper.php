<?php

namespace Edwin404\Api\Helper;


class ApiSignHelper
{
    public static function calc($params, $appSecret)
    {
        ksort($params, SORT_STRING);

        $str = [];
        foreach ($params as $k => $v) {
            $str [] = $k . '=' . urlencode($v);
        }
        $str[] = 'app_secret=' . $appSecret;
        $str = join('&', $str);

        $sign = md5($str);

        return $sign;
    }
}