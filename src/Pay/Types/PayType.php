<?php

namespace Edwin404\Pay\Types;

use Edwin404\Base\Support\BaseType;

class PayType implements BaseType
{
    const ALIPAY = 'alipay';
    const WECHAT_MOBILE = 'wechat_mobile';
    const WECHAT_MINI_PROGRAM = 'wechat_mini_program';
    const WECHAT = 'wechat';

    const ALIPAY_MANUAL = 'alipay_manual';
    const WECHAT_MANUAL = 'wechat_manual';

    // 新版支付宝PC端
    const ALIPAY_WEB = 'alipay_web';

    const OFFLINE_PAY = 'offline_pay';

    public static function getList()
    {
        return [
            self::ALIPAY => '支付宝',
            self::ALIPAY_WEB => '支付宝-Web',
            self::WECHAT_MOBILE => '微信手机',
            self::WECHAT_MINI_PROGRAM => '微信小程序',
            self::WECHAT => '微信网页',
            self::ALIPAY_MANUAL => '支付宝手动',
            self::WECHAT_MANUAL => '微信手动',
            self::OFFLINE_PAY => '货到付款',
        ];
    }


}