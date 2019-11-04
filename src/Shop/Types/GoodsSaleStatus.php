<?php

namespace Edwin404\Shop\Types;


use Edwin404\Base\Support\BaseType;

class GoodsSaleStatus implements BaseType
{
    const ON = 1;
    const OFF = 2;

    public static function getList()
    {
        return [
            self::ON => '正在销售',
            self::OFF => '已下架',
        ];
    }

}