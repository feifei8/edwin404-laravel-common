<?php

namespace Edwin404\Placeholder\Util;


use Edwin404\SmartAssets\Helper\AssetsHelper;

class CrossDomainUtil
{
    public static function fix($url)
    {
        return AssetsHelper::fix('/cross_domain/base64/' . base64_encode($url));
    }

}