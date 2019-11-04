<?php
namespace Edwin404\Wechat\Types;

use Edwin404\Base\Support\BaseType;

class WechatVerifyInfo implements BaseType
{

    /**
     * 授权方认证类型:
     * -1代表未认证，
     * 0代表微信认证，
     * 1代表新浪微博认证，
     * 2代表腾讯微博认证，
     * 3代表已资质认证通过、还未通过名称认证
     * 4代表已资质认证通过、还未通过名称认证，但通过了新浪微博认证，
     * 5代表已资质认证通过、还未通过名称认证，但通过了腾讯微博认证
     */

    const NOT_VERIFY = -1;
    const VERIFY_WECHAT = 0;
    const VERIFY_WEIBO_SINA = 1;
    const VERIFY_WEIBO_TENCENT = 2;
    const VERIFY_QUALIFY_NONAME = 3;
    const VERIFY_QUALIFY_WEIBO_SINA_NONAME = 4;
    const VERIFY_QUALIFY_WEIBO_TECENT_NONAME = 5;

    public static function getList()
    {
        return [
            self::NOT_VERIFY => '未认证',
            self::VERIFY_WECHAT => '微信认证',
            self::VERIFY_WEIBO_SINA => '新浪微博认证',
            self::VERIFY_WEIBO_TENCENT => '腾讯微博认证',
            self::VERIFY_QUALIFY_NONAME => '资质认证通过、还未通过名称认证',
            self::VERIFY_QUALIFY_WEIBO_SINA_NONAME => '资质认证通过、还未通过名称认证，但通过了新浪微博认证',
            self::VERIFY_QUALIFY_WEIBO_TECENT_NONAME => '资质认证通过、还未通过名称认证，但通过了腾讯微博认证',
        ];
    }

}