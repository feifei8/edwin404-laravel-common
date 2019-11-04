<?php

namespace Edwin404\Pay\Controllers;


use Edwin404\Pay\Services\PayOrderService;
use Edwin404\Pay\Types\PayType;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Payment\Client\Notify;
use Payment\Config;
use Payment\Notify\PayNotifyInterface;

if (class_exists('\Payment\Notify\PayNotifyInterface')) {
    class PayAlipayWebNotify implements PayNotifyInterface
    {
        private $payOrderService;

        public function __construct(PayOrderService $payOrderService)
        {
            $this->payOrderService = $payOrderService;
        }

        public function notifyProcess(array $data)
        {
            switch ($data['trade_status']) {
                case 'TRADE_SUCCESS':
                case 'TRADE_FINISHED':
                    Log::notice('alipay notify -> data verification success.', [
                        'out_trade_no' => $data['out_trade_no'],
                        'trade_no' => $data['trade_no'],
                    ]);

                    $ret = $this->payOrderService->handleOrderPay(PayType::ALIPAY_WEB, $data['out_trade_no']);
                    if ($ret['code']) {
                        return false;
                    }
                    return true;

            }
            return true;
        }

    }
}

class NotifyController extends Controller
{
    public function index(PayOrderService $payOrderService, $payType = '')
    {
        switch ($payType) {

            case PayType::ALIPAY_WEB:
                $config = $payOrderService->payConfig($payType);
                return Notify::run(Config::ALI_CHARGE, $config, new PayAlipayWebNotify($payOrderService));

            case PayType::ALIPAY:
                $payOrderService->initAlipay();
                if (!app('alipay.web')->verify()) {
                    Log::notice('alipay return -> query data verification fail.', [
                        'data' => Request::instance()->getContent()
                    ]);
                    return 'fail';
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
                            return 'fail';
                        }

                        return 'success';
                }
                return 'fail';

            case PayType::WECHAT_MOBILE:

                return PayOrderService::getWechatMobileApp()->payment->handleNotify(function ($notify, $successful) use ($payOrderService) {
                    if ($successful) {
                        $ret = $payOrderService->handleOrderPay(PayType::WECHAT_MOBILE, $notify->out_trade_no);
                        if ($ret['code']) {
                            return 'fail';
                        }
                        return true;
                    }
                    return true;
                });

            case PayType::WECHAT_MINI_PROGRAM:

                return PayOrderService::getWechatMiniProgramApp()->payment->handleNotify(function ($notify, $successful) use ($payOrderService) {
                    if ($successful) {
                        $ret = $payOrderService->handleOrderPay(PayType::WECHAT_MINI_PROGRAM, $notify->out_trade_no);
                        if ($ret['code']) {
                            return 'fail';
                        }
                        return true;
                    }
                    return true;
                });

            case PayType::WECHAT:

                return PayOrderService::getWechatApp()->payment->handleNotify(function ($notify, $successful) use ($payOrderService) {
                    if ($successful) {
                        $ret = $payOrderService->handleOrderPay(PayType::WECHAT, $notify->out_trade_no);
                        if ($ret['code']) {
                            return 'fail';
                        }
                        return true;
                    }
                    return true;
                });

        }
    }

}