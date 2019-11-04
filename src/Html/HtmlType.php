<?php

namespace Edwin404\Html;


use Edwin404\Base\Support\BaseType;

class HtmlType implements BaseType
{
    const RICH_TEXT = 1;
    const MARKDOWN = 2;
    const SIMPLE_TEXT = 3;

    public static function getList()
    {
        return [
            self::RICH_TEXT => '富文本',
            self::MARKDOWN => 'Markdown',
            self::SIMPLE_TEXT => '简单文本',
        ];
    }

}