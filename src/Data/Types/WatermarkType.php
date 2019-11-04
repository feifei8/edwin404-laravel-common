<?php

namespace Edwin404\Data\Types;


use Edwin404\Base\Support\BaseType;

class WatermarkType implements BaseType
{
    const NONE = 1;
    const TEXT = 2;
    const TEXT_USERNAME = 3;
    const IMAGE = 4;

    public static function getList()
    {
        return [
            self::NONE => '无',
            self::TEXT => '自定义文字',
            self::TEXT_USERNAME => '自定义文字+用户名',
            self::IMAGE => '图片',
        ];
    }

}