<?php

namespace Edwin404\Wechat\Types;


use Edwin404\Base\Support\BaseType;

class AuthorizerOption implements BaseType
{
    const LOCATION_REPORT = 'location_report';
    const VOICE_RECOGNIZE = 'voice_recognize';
    const CUSTOMER_SERVICE = 'customer_service';

    public static function getList()
    {
        return [
            self::LOCATION_REPORT => '地理位置上报选项 (0无上报 1进入会话时上报 2每5s上报)',
            self::VOICE_RECOGNIZE => '语音识别开关选项 (0关闭语音识别 1开启语音识别)',
            self::CUSTOMER_SERVICE => '多客服开关选项 (0关闭多客服 1开启多客服)',
        ];
    }


}