<?php

namespace Edwin404\Api\Type;


use Edwin404\Base\Support\BaseType;

class Platform implements BaseType
{
    const ANDROID = 1;
    const IOS = 2;

    public static function getList()
    {
        return [
            self::ANDROID => 'Android',
            self::IOS => 'iOS',
        ];
    }

}