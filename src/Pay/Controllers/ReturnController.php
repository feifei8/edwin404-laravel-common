<?php

namespace Edwin404\Pay\Controllers;

use Edwin404\Base\Support\Response;
use Edwin404\Pay\Services\PayOrderService;
use Edwin404\Pay\Types\PayType;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class ReturnController extends Controller
{

    public function index(PayOrderService $payOrderService, $payType = '')
    {
        switch ($payType) {
            // 新版已经不再推荐使用return 在notify中获取通知
            case PayType::ALIPAY_WEB:
                $redirect = Session::get('payRedirect');
                if (null === $redirect) {
                    return Response::send(0, '支付成功');
                } else {
                    return Response::send(0, null, null, $redirect);
                }
                break;

            case PayType::ALIPAY:

                $payOrderService->initAlipay();

                if (!app('alipay.web')->verify()) {
                    Log::notice('alipay return -> query data verification fail.', [
                        'data' => Request::getQueryString()
                    ]);
                    return Response::send(-1, '支付失败:-1');
                }

                switch (Input::get('trade_status')) {
                    case 'TRADE_SUCCESS':
                    case 'TRADE_FINISHED':

                        Log::notice('alipay return -> data verification success.', [
                            'out_trade_no' => Input::get('out_trade_no'),
                            'trade_no' => Input::get('trade_no')
                        ]);

                        $ret = $payOrderService->handleOrderPay(PayType::WECHAT_MOBILE, Input::get('out_trade_no', ''));
                        if ($ret['code']) {
                            return Response::send(-1, '支付失败:' . $ret['msg']);
                        }

                        $redirect = Session::get('payRedirect');
                        if (null === $redirect) {
                            return Response::send(0, '支付成功');
                        } else {
                            return Response::send(0, null, null, $redirect);
                        }
                }

                return Response::send(-1, '支付失败');
        }
    }

}