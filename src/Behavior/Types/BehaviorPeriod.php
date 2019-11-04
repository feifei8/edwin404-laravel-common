<?php

namespace Edwin404\Behavior\Types;


use Edwin404\Base\Support\BaseType;

class BehaviorPeriod implements BaseType
{
    const ONE_MINUTE = 1;
    const FIVE_MINUTES = 2;
    const TEN_MINUTES = 3;
    const THIRTY_MINUTES = 4;
    const HOUR = 5;
    const DAY = 6;

    public static function getList()
    {
        return [
            self::ONE_MINUTE => '一分钟',
            self::FIVE_MINUTES => '五分钟',
            self::TEN_MINUTES => '十分钟',
            self::THIRTY_MINUTES => '三十分钟',
            self::HOUR => '一小时',
            self::DAY => '一天',
        ];
    }


}