<?php

namespace Edwin404\Common\Type;

use Edwin404\Base\Support\BaseType;

class SwitchType implements BaseType
{
    const YES = 1;
    const NO = 0;

    public static function getList()
    {
        return [
            self::YES => '是',
            self::NO => '否',
        ];
    }

}