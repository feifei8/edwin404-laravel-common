<?php

namespace Edwin404\Member\Types;


class MemberCreditChargeOrderStatus
{
    const WAIT_PAY = 1;
    const WAIT_CHARGE = 2;
    const EXPIRED = 3;
    const COMPLETED = 4;

    public static function getList()
    {
        return [
            self::WAIT_PAY => '等待付款',
            self::WAIT_CHARGE => '等待充值',
            self::EXPIRED => '已过期',
            self::COMPLETED => '已完成',
        ];
    }
}