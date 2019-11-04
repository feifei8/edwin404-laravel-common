<?php

namespace Edwin404\Member\Helpers;


use Edwin404\Base\Support\HtmlHelper;
use Edwin404\Common\Helpers\EmotionHelper;
use Edwin404\Member\Types\MemberChatMsgType;
use Edwin404\SmartAssets\Helper\AssetsHelper;

class MemberChatHelper
{
    public static function summary($msg)
    {
        switch ($msg['type']) {
            case MemberChatMsgType::TEXT:
                $msg['content'] = preg_replace('/<img class="emotion".*?>/', '[表情]', $msg['content']);
                return HtmlHelper::text($msg['content']);
            case MemberChatMsgType::IMAGE:
                return '[图片]';
        }
        return '';
    }
}