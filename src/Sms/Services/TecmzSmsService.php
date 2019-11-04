<?php

namespace Edwin404\Sms\Services;


use Edwin404\Base\Support\Response;

class TecmzSmsService
{
    public function send($appKey, $tplId, $phone, $param = [])
    {

        if (empty($appKey)) {
            return Response::generate(-1, 'appKey empty');
        }
        if (empty($tplId)) {
            return Response::generate(-1, 'tplId empty');
        }
        if (empty($phone)) {
            return Response::generate(-1, 'phone empty');
        }
        if (!preg_match('/^1\\d{10}$/', $phone)) {
            return Response::generate(-1, 'phone format error');
        }


        $params = [];
        $params['app_key'] = $appKey;
        $params['tpl_id'] = $tplId;
        $params['phone'] = $phone;

        foreach ($param as $k => $v) {
            $params['param_' . $k] = $v;
        }

        $url = "http://sms.tecmz.com/api/sms_send?" . http_build_query($params);

        $content = file_get_contents($url);
        $jsonResult = @json_decode($content, true);
        if (!isset($jsonResult['code'])) {
            return Response::generate(-1, 'send error');
        }
        if ($jsonResult['code']) {
            return Response::generate(-1, $jsonResult['msg']);
        }
        return Response::generate(0, 'ok');
    }
}