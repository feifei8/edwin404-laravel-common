<?php

namespace Edwin404\Shipping;


use Edwin404\Base\Support\BaseType;

class ShippingType implements BaseType
{
    const ASAP = 1;
    const BOOK = 2;

    public static function getList()
    {
        return [
            self::ASAP => '尽快配送',
            self::BOOK => '预约时间',
        ];
    }
}