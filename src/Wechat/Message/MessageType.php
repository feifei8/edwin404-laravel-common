<?php

namespace Edwin404\Wechat\Message;


use Edwin404\Base\Support\BaseType;

class MessageType implements BaseType
{
    const UNKNOWN = 'unknown';

    const TEXT = 'text';
    const NEWS = 'news';
    const IMAGE = 'image';
    const LINK = 'link';
    const CUSTOM = 'custom';

    public static function getList()
    {
        return [
            self::TEXT => '文本',
            self::NEWS => '图文',
            self::IMAGE => '图片',
            self::LINK => '链接',
            self::CUSTOM => '自定义',
        ];
    }


}