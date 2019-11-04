<?php

namespace Edwin404\Member\Types;

use Edwin404\Base\Support\BaseType;

class ProfileGender implements BaseType
{
    const UNKNOWN = 0;
    const MALE = 1;
    const FEMALE = 2;

    public static function getList()
    {
        return [
            self::UNKNOWN => '未知',
            self::MALE => '男',
            self::FEMALE => '女',
        ];
    }


}