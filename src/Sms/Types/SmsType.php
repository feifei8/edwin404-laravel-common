<?php

namespace Edwin404\Sms\Types;

use Edwin404\Base\Support\BaseType;

class SmsType implements BaseType
{
    const JUHE = 1;
    const WOHUI = 2;
    const TECMZ = 3;

    public static function getList()
    {
        return [
            self::JUHE => '聚合',
            self::WOHUI => '沃慧',
            self::TECMZ => '墨子',
        ];
    }

}