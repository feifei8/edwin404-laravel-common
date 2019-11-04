<?php
namespace Edwin404\Wechat\Types;

use Edwin404\Base\Support\BaseType;

class WechatAuthStatus implements BaseType
{
    const NORMAL = 1;
    const CANCELED = 2;

    public static function getList()
    {
        return [
            self::NORMAL => '正常',
            self::CANCELED => '已取消',
        ];
    }

}