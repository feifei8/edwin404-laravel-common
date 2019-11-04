<?php

namespace Edwin404\Member\Types;


use Edwin404\Base\Support\BaseType;

class MemberMoneyCashType implements BaseType
{
    const ALIPAY = 1;

    public static function getList()
    {
        return [
            self::ALIPAY => '支付宝',
        ];
    }

}