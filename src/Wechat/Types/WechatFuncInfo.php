<?php
namespace Edwin404\Wechat\Types;


use Edwin404\Base\Support\BaseType;

class WechatFuncInfo implements BaseType
{
    const MESSAGE = 1;
    const USER = 2;
    const ACCOUNT = 3;
    const WEB = 4;
    const MICRO_SHOP = 5;
    const CUSTOMER_SERVICE = 6;
    const GROUP_MESSAGE = 7;
    const CARD = 8;
    const SCAN = 9;
    const WIFI = 10;
    const MATERIAL = 11;
    const SHAKE = 12;
    const SHOP = 13;
    const PAY = 14;
    const MENU = 15;

    public static function getList()
    {
        return [
            1 => '消息管理权限',
            2 => '用户管理权限',
            3 => '帐号服务权限',
            4 => '网页服务权限',
            5 => '微信小店权限',
            6 => '微信多客服权限',
            7 => '群发与通知权限',
            8 => '微信卡券权限',
            9 => '微信扫一扫权限',
            10 => '微信连WIFI权限',
            11 => '素材管理权限',
            12 => '微信摇周边权限',
            13 => '微信门店权限',
            14 => '微信支付权限',
            15 => '自定义菜单权限',
        ];
    }

}