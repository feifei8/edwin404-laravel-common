<?php

namespace Edwin404\Member\Types;


use Edwin404\Base\Support\BaseType;

class MemberMoneyChargeStatus implements BaseType
{
    const CREATED = 1;
    const SUCCESS = 2;

    public static function getList()
    {
        return [
            self::CREATED => '新创建',
            self::SUCCESS => '提现成功',
        ];
    }

}