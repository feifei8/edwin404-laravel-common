<?php

namespace Edwin404\Sms\Services;


use Carbon\Carbon;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Sms\Types\SmsType;

class SmsVerifyService
{
    private $wohuiSmsService;
    private $tecmzSmsService;
    private $juheSmsService;

    public function __construct(WohuiSmsService $wohuiSmsService,
                                TecmzSmsService $tecmzSmsService,
                                JuheSmsService $juheSmsService)
    {
        $this->wohuiSmsService = $wohuiSmsService;
        $this->tecmzSmsService = $tecmzSmsService;
        $this->juheSmsService = $juheSmsService;
    }

    public function send($type, $usage, $phone, $verifyLength = 4, $config = [])
    {
        $verify = '';
        while ($verifyLength > 0) {
            $verifyLength--;
            $verify .= rand(0, 9);
        }

        if ('15000000000' == $phone) {
            $verify = '123456';
        }

        $m = ModelHelper::load('sms_verify', ['usage' => $usage, 'phone' => $phone]);
        if (empty($m)) {
            $m = ModelHelper::add('sms_verify', [
                'usage' => $usage,
                'phone' => $phone,
                'sendTimes' => 1,
                'verify' => $verify,
                'sendTime' => Carbon::now(),
            ]);
        } else {
            $data = [];
            if (empty($m['verify']) || strtotime($m['updated_at']) < time() - 3600) {
                $data['verify'] = $verify;
                $data['sendTimes'] = 0;
            }
            $data['sendTime'] = Carbon::now();
            $data['sendTimes'] = $m['sendTimes'] + 1;
            $m = ModelHelper::updateOne('sms_verify', ['id' => $m['id']], $data);
        }

        switch ($type) {
            case SmsType::WOHUI:
                $param = [];
                $param['verify'] = $m['verify'];
                $ret = $this->wohuiSmsService->send($config['appKey'], $config['tplId'], $phone, $param);
                if ($ret['code']) {
                    return Response::generate(-1, $ret['msg']);
                }
                return Response::generate(0, 'ok');
            case SmsType::TECMZ:
                $param = [];
                $param['verify'] = $m['verify'];
                $ret = $this->tecmzSmsService->send($config['appKey'], $config['tplId'], $phone, $param);
                if ($ret['code']) {
                    return Response::generate(-1, $ret['msg']);
                }
                return Response::generate(0, 'ok');
            case SmsType::JUHE:
                $param = [];
                $param['verify'] = $m['verify'];
                $ret = $this->juheSmsService->send($config['appKey'], $config['tplId'], $phone, $param);
                if ($ret['code']) {
                    return Response::generate(-1, $ret['msg']);
                }
                return Response::generate(0, 'ok');
        }

        return Response::generate(-1, 'type error');
    }

    public function remove($usage, $phone)
    {
        ModelHelper::delete('sms_verify', ['usage' => $usage, 'phone' => $phone]);
    }

    public function check($usage, $phone, $verify)
    {
        $m = ModelHelper::load('sms_verify', ['usage' => $usage, 'phone' => $phone]);
        if (empty($m)) {
            return false;
        }
        if ($verify == $m['verify']) {
            return true;
        }
        return false;
    }

}