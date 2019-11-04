<?php

namespace Edwin404\Shop\Types;


use Edwin404\Base\Support\BaseType;

class OrderWorkerStatus implements BaseType
{
    const WAIT_START = 1;
    const RUNNING = 2;
    const COMPLETED = 3;
    const FAILED = 4;

    public static function getList()
    {
        return [
            self::WAIT_START => '等待开始',
            self::RUNNING => '正在执行',
            self::COMPLETED => '执行结束',
            self::FAILED => '执行失败',
        ];
    }

}