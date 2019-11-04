<?php

namespace Edwin404\Member\Types;


use Edwin404\Base\Support\BaseType;

class MemberChatMsgType implements BaseType
{
    const TEXT = 1;
    const IMAGE = 2;

    public static function getList()
    {
        return [
            self::TEXT => '图片',
            self::IMAGE => '文字',
        ];
    }


}