<?php

namespace Edwin404\Api\Helper;


use Edwin404\Base\Support\CurlHelper;
use Illuminate\Support\Str;
use Edwin404\Api\Helper\ApiSignHelper;

class ApiHelper
{
    public static function request($appId, $appSecret, $url, $param = [])
    {
        $param ['app_id'] = $appId;
        $param['nonce_str'] = Str::random(16);
        $param['sign'] = $signCalc = ApiSignHelper::calc($param, $appSecret);
        return CurlHelper::postStandardJson($url, $param);
    }
}