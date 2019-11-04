<?php
namespace Edwin404\Member\Types;

use Edwin404\Base\Support\BaseType;

class MemberOauthType implements BaseType
{
    const WECHAT_MOBILE = 'wechatmobile';
    const WECHAT_UNION = 'wechatunion';
    const WECHAT = 'wechat';

    public static function getList()
    {
        return [
            self::WECHAT_MOBILE => '微信手机授权',
            self::WECHAT => '微信扫码授权',
        ];
    }
}