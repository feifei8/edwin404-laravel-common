<?php

namespace Edwin404\Member\Types;


use Edwin404\Base\Support\BaseType;

class MemberMoneyCashStatus implements BaseType
{
    const VERIFYING = 1;
    const SUCCESS = 2;

    public static function getList()
    {
        return [
            self::VERIFYING => '正在审核',
            self::SUCCESS => '提现成功',
        ];
    }

}