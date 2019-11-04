<?php

namespace Edwin404\Pay\Services;

use Carbon\Carbon;
use EasyWeChat\Payment\Order;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Config\Facades\ConfigFacade;
use Edwin404\Pay\Events\OrderPayedEvent;
use Edwin404\Pay\Types\PayType;
use Edwin404\Pay\Types\PayOrderStatus;
use Edwin404\Tecmz\Helpers\ConfigEnvHelper;
use Edwin404\Wechat\Support\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Payment\Client\Charge;
use Payment\Common\PayException;
use Payment\Config;

class PayOrderService
{
    public function createPayOrder($biz, $bizId, $payType, $feeTotal)
    {
        ModelHelper::delete('pay_order', ['biz' => $biz, 'bizId' => $bizId]);
        $order = [];
        $order['biz'] = $biz;
        $order['bizId'] = $bizId;
        $order['payType'] = $payType;
        $order['status'] = PayOrderStatus::NEW_ORDER;
        $order['payOrderId'] = null;
        $order['feeTotal'] = $feeTotal;
        $order['timePayCreated'] = null;
        $order['timePay'] = null;
        $order['feeRefund'] = null;
        $order['timeRefundCreated'] = null;
        $order['timeRefundSuccess'] = null;
        $order['timeClosed'] = null;
        $order = ModelHelper::add('pay_order', $order);
        return $order;
    }

    public function update($id, $data)
    {
        return ModelHelper::updateOne('pay_order', ['id' => $id], $data);
    }

    public function load($id)
    {
        return ModelHelper::load('pay_order', ['id' => $id]);
    }

    public function getByBizAndBizId($biz, $bizId)
    {
        return ModelHelper::load('pay_order', [
            'biz' => $biz,
            'bizId' => $bizId,
        ]);
    }

    /**
     * 创建支付订单
     *
     * @param $biz : 业务
     * @param $bizId : 业务ID
     * @param $payType : 支付类型
     * @param $feeTotal : 支付金额
     * @param $title : 支付标题
     * @param $body : 支付说明
     * @param $redirect : 支付成功后跳转的链接
     *
     * @return array
     * 正确: [code=>0,msg=>'ok',data=>[link=>'<支付跳转链接>']]
     * 错误: [code=>-1,msg=>'<错误信息>']
     *
     * @usage
     * 支付宝 data.link 支付跳转链接
     */
    public function create($biz, $bizId, $payType, $feeTotal, $title, $body, $redirect = null, $option = [])
    {
        Session::put('payRedirect', $redirect);

        switch ($payType) {
            case PayType::ALIPAY_WEB:
                return $this->createAlipayWeb($biz, $bizId, $feeTotal, $title, $body, $option);

            case PayType::ALIPAY:
                return $this->createAlipay($biz, $bizId, $feeTotal, $title, $body, $option);

            case PayType::WECHAT:
                return $this->createWechat($biz, $bizId, $feeTotal, $title, $body, $option);

            case PayType::WECHAT_MOBILE:
                return $this->createWechatMobile($biz, $bizId, $feeTotal, $title, $body, $option);

            case PayType::WECHAT_MINI_PROGRAM:
                return $this->createWechatMiniProgram($biz, $bizId, $feeTotal, $title, $body, $option);
        }
        return Response::generate(-1, 'error payType');
    }

    public static function getWechatApp()
    {
        $payStoragePath = base_path('storage/cache/pay/');
        if (!file_exists($payStoragePath)) {
            @mkdir($payStoragePath, 0777, true);
        }

        if (!file_exists($certPath = $payStoragePath . 'wechat_cert.pem')) {
            file_put_contents($certPath, ConfigFacade::get('payWechatFileCert'));
        }
        if (!file_exists($keyPath = $payStoragePath . 'wechat_key.pem')) {
            file_put_contents($keyPath, ConfigFacade::get('payWechatFileKey'));
        }

        $options = [
            'debug' => true,
            'app_id' => ConfigFacade::get('payWechatAppId'),
            'secret' => ConfigFacade::get('payWechatAppSecret'),
            'token' => ConfigFacade::get('payWechatAppToken'),
            'aes_key' => null,
            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs/wechat_pay.log'),
            ],
            'payment' => [
                'merchant_id' => ConfigFacade::get('payWechatMerchantId'),
                'key' => ConfigFacade::get('payWechatKey'),
                'device_info' => 'WEB',
                'cert_path' => $certPath,
                'key_path' => $keyPath,
            ],
        ];
        //print_r($options);exit();

        return new Application($options);
    }

    private function createWechat($biz, $bizId, $feeTotal, $title, $body, $option = [])
    {
        if (!ConfigFacade::get('payWechatOn', false)) {
            return Response::generate(-1, 'wechat pay not enable');
        }

        $order = $this->createPayOrder($biz, $bizId, PayType::WECHAT, $feeTotal);

        $attributes = [
            'trade_type' => 'NATIVE',
            'body' => $title,
            'detail' => $body,
            'out_trade_no' => config('pay.payOrderOutTradeNoPrefix') . '_' . $order['id'],
            'total_fee' => intval($feeTotal * 100),
            'notify_url' => action('\Edwin404\Pay\Controllers\NotifyController@index', ['payType' => PayType::WECHAT]),
        ];

        if (isset($option['limitPay']) && $option['limitPay'] == 'no_credit') {
            $attributes['limit_pay'] = 'no_credit';
        }

        $app = self::getWechatApp();

        $ret = $app->payment->prepare(new Order($attributes));
        //Log::error('ret->'.print_r($ret,true));
        if (!isset($ret['return_code']) || $ret['return_code'] != 'SUCCESS') {
            return Response::generate(-1, '创建订单失败:1:(' . (isset($ret['return_msg']) ? $ret['return_msg'] : 'NULL') . ')');
        }

        if (!isset($ret['result_code'])) {
            return Response::generate(-1, '订单创建失败:2');
        }

        if ($ret['result_code'] != 'SUCCESS') {
            /**
             * Array
             * (
             * [result_code] => FAIL
             * [return_msg] => invalid total_fee
             * )
             */
            $errMsg = $ret['err_code_des'];
            return Response::generate(-1, '创建订单失败:3:(' . $errMsg . ')');
        }

        /**
         * Array
         * (
         * [return_code] => SUCCESS
         * [return_msg] => OK
         * [appid] => wx206291b49c286cf8
         * [mch_id] => 1332368501
         * [device_info] => WEB
         * [nonce_str] => lzhZUOfsZQ6ROjrP
         * [sign] => A22DEEA21FF653777D999EA95581B46D
         * [result_code] => SUCCESS
         * [prepay_id] => wx201604171122366d091af8e40730202548
         * [trade_type] => NATIVE
         * [code_url] => weixin://wxpay/s/xxxx
         * )
         */
        $this->update($order['id'], ['status' => PayOrderStatus::CREATED]);
        $codeUrl = $ret['code_url'];
        return Response::generate(0, null, ['codeUrl' => $codeUrl, 'successRedirect' => Session::get('payRedirect')]);

    }

    public static function getWechatMobileApp()
    {
        $payStoragePath = base_path('storage/cache/pay/');
        if (!file_exists($payStoragePath)) {
            @mkdir($payStoragePath, 0777, true);
        }

        if (!file_exists($certPath = $payStoragePath . 'wechat_mobile_cert.pem')) {
            file_put_contents($certPath, ConfigFacade::get('payWechatMobileFileCert'));
        }
        if (!file_exists($keyPath = $payStoragePath . 'wechat_mobile_key.pem')) {
            file_put_contents($keyPath, ConfigFacade::get('payWechatMobileFileKey'));
        }

        $options = [
            'debug' => true,
            'app_id' => ConfigEnvHelper::get('payWechatMobileAppId'),
            'secret' => ConfigEnvHelper::get('payWechatMobileAppSecret'),
            'token' => ConfigEnvHelper::get('payWechatMobileAppToken'),
            'aes_key' => null,
            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs/wechat_pay.log'),
            ],
            'payment' => [
                'merchant_id' => ConfigEnvHelper::get('payWechatMobileMerchantId'),
                'key' => ConfigEnvHelper::get('payWechatMobileKey'),
                'device_info' => 'WEB',
                'cert_path' => $certPath,
                'key_path' => $keyPath,
            ],
        ];

        return new Application($options);
    }

    public static function getWechatMiniProgramApp()
    {
        $payStoragePath = base_path('storage/cache/pay/');
        if (!file_exists($payStoragePath)) {
            @mkdir($payStoragePath, 0777, true);
        }

        if (!file_exists($certPath = $payStoragePath . 'wechat_mini_program_cert.pem')) {
            file_put_contents($certPath, ConfigFacade::get('payWechatMiniProgramFileCert'));
        }
        if (!file_exists($keyPath = $payStoragePath . 'wechat_mini_program_key.pem')) {
            file_put_contents($keyPath, ConfigFacade::get('payWechatMiniProgramFileKey'));
        }

        $options = [
            'debug' => true,
            'app_id' => ConfigEnvHelper::get('payWechatMiniProgramAppId'),
            'secret' => ConfigEnvHelper::get('payWechatMiniProgramAppSecret'),
            'token' => ConfigEnvHelper::get('payWechatMiniProgramAppToken'),
            'aes_key' => null,
            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs/wechat_pay.log'),
            ],
            'payment' => [
                'merchant_id' => ConfigEnvHelper::get('payWechatMiniProgramMerchantId'),
                'key' => ConfigEnvHelper::get('payWechatMiniProgramKey'),
                'device_info' => 'WEB',
                'cert_path' => $certPath,
                'key_path' => $keyPath,
            ],
        ];

        return new Application($options);
    }

    private function createWechatMobile($biz, $bizId, $feeTotal, $title, $body, $option = [])
    {
        if (!ConfigFacade::get('payWechatMobileOn', false)) {
            return Response::generate(-1, 'wechat mobile pay not enable');
        }

        if (empty($option['openId'])) {
            return Response::generate(-1, 'wechat mobile openId empty');
        }

        $order = $this->createPayOrder($biz, $bizId, PayType::WECHAT_MOBILE, $feeTotal);

        $attributes = [
            'openid' => $option['openId'],
            'trade_type' => 'JSAPI',
            'body' => $title,
            'detail' => $body,
            'out_trade_no' => config('pay.payOrderOutTradeNoPrefix') . '_' . $order['id'],
            'total_fee' => intval($feeTotal * 100),
            'notify_url' => action('\Edwin404\Pay\Controllers\NotifyController@index', ['payType' => PayType::WECHAT_MOBILE]),
        ];

        if (isset($option['limitPay']) && $option['limitPay'] == 'no_credit') {
            $attributes['limit_pay'] = 'no_credit';
        }

        $app = self::getWechatMobileApp();

        $ret = $app->payment->prepare(new Order($attributes))->toArray();

        if (!isset($ret['return_code']) || $ret['return_code'] != 'SUCCESS') {
            return Response::generate(-1, '创建订单失败:2:(' . $ret['return_msg'] . ')');
        }

        if (!isset($ret['result_code'])) {
            return Response::generate(-1, '订单创建失败:2');
        }

        if ($ret['result_code'] == 'SUCCESS') {
            /**
             * Array
             * (
             * [return_code] => SUCCESS
             * [return_msg] => OK
             * [appid] => wx206291b49c286cf8
             * [mch_id] => 1332368501
             * [device_info] => WEB
             * [nonce_str] => lzhZUOfsZQ6ROjrP
             * [sign] => A22DEEA21FF653777D999EA95581B46D
             * [result_code] => SUCCESS
             * [prepay_id] => wx201604171122366d091af8e40730202548
             * [trade_type] => JSAPI
             * )
             */
            $this->update($order['id'], [
                'status' => PayOrderStatus::CREATED,
                'payOrderId' => $ret['prepay_id'],
                'timePayCreated' => Carbon::now(),
            ]);
            $json = $app->payment->configForPayment($ret['prepay_id']);
            return Response::generate(0, null, ['json' => $json, 'successRedirect' => Session::get('payRedirect')]);
        } else {
            /**
             * Array
             * (
             * [result_code] => FAIL
             * [return_msg] => invalid total_fee
             * )
             */
            $errMsg = $ret['err_code_des'];
            return Response::generate(-1, '创建订单失败:4:(' . $errMsg . ')');
        }

    }

    private function createWechatMiniProgram($biz, $bizId, $feeTotal, $title, $body, $option = [])
    {
        if (!ConfigFacade::get('payWechatMiniProgramOn', false)) {
            return Response::generate(-1, 'wechat mini program pay not enable');
        }

        if (empty($option['openId'])) {
            return Response::generate(-1, 'wechat mini program openId empty');
        }

        $order = $this->createPayOrder($biz, $bizId, PayType::WECHAT_MINI_PROGRAM, $feeTotal);

        $attributes = [
            'openid' => $option['openId'],
            'trade_type' => 'JSAPI',
            'body' => $title,
            'detail' => $body,
            'out_trade_no' => config('pay.payOrderOutTradeNoPrefix') . '_' . $order['id'],
            'total_fee' => intval($feeTotal * 100),
            'notify_url' => action('\Edwin404\Pay\Controllers\NotifyController@index', ['payType' => PayType::WECHAT_MINI_PROGRAM]),
        ];

        if (isset($option['limitPay']) && $option['limitPay'] == 'no_credit') {
            $attributes['limit_pay'] = 'no_credit';
        }

        $app = self::getWechatMiniProgramApp();

        $ret = $app->payment->prepare(new Order($attributes))->toArray();

        if (!isset($ret['return_code']) || $ret['return_code'] != 'SUCCESS') {
            return Response::generate(-1, '创建订单失败:2:(' . $ret['return_msg'] . ')');
        }

        if (!isset($ret['result_code'])) {
            return Response::generate(-1, '订单创建失败:2');
        }

        if ($ret['result_code'] == 'SUCCESS') {
            /**
             * Array
             * (
             * [return_code] => SUCCESS
             * [return_msg] => OK
             * [appid] => wx206291b49c286cf8
             * [mch_id] => 1332368501
             * [device_info] => WEB
             * [nonce_str] => lzhZUOfsZQ6ROjrP
             * [sign] => A22DEEA21FF653777D999EA95581B46D
             * [result_code] => SUCCESS
             * [prepay_id] => wx201604171122366d091af8e40730202548
             * [trade_type] => JSAPI
             * )
             */
            $this->update($order['id'], [
                'status' => PayOrderStatus::CREATED,
                'payOrderId' => $ret['prepay_id'],
                'timePayCreated' => Carbon::now(),
            ]);
            $json = $app->payment->configForPayment($ret['prepay_id']);
            return Response::generate(0, null, ['json' => $json, 'successRedirect' => Session::get('payRedirect')]);
        } else {
            /**
             * Array
             * (
             * [result_code] => FAIL
             * [return_msg] => invalid total_fee
             * )
             */
            $errMsg = $ret['err_code_des'];
            return Response::generate(-1, '创建订单失败:4:(' . $errMsg . ')');
        }

    }

    public function initAlipay()
    {
        config([
            'latrell-alipay.partner_id' => config('pay.alipay.partnerId'),
            'latrell-alipay.seller_id' => config('pay.alipay.sellerId'),
            'latrell-alipay-web.key' => config('pay.alipay.key'),
            'latrell-alipay-web.return_url' => action('\Edwin404\Pay\Controllers\ReturnController@index', ['payType' => PayType::ALIPAY]),
            'latrell-alipay-web.notify_url' => action('\Edwin404\Pay\Controllers\NotifyController@index', ['payType' => PayType::ALIPAY]),
        ]);
    }

    public function payConfig($type)
    {
        switch ($type) {
            case PayType::ALIPAY_WEB:
                return [
                    'use_sandbox' => false,
                    'app_id' => ConfigFacade::get('payAlipayWebAppId'),
                    'sign_type' => 'RSA2',
                    'ali_public_key' => ConfigFacade::get('payAlipayWebAliPublicKey'),
                    'rsa_private_key' => ConfigFacade::get('payAlipayWebRSAPrivateKey'),
                    'limit_pay' => [
                        //'balance',// 余额
                        //'moneyFund',// 余额宝
                        //'debitCardExpress',// 	借记卡快捷
                        //'creditCard',//信用卡
                        //'creditCardExpress',// 信用卡快捷
                        //'creditCardCartoon',//信用卡卡通
                        //'credit_group',// 信用支付类型（包含信用卡卡通、信用卡快捷、花呗、花呗分期）
                    ],// 用户不可用指定渠道支付当有多个渠道时用“,”分隔
                    'notify_url' => action('\Edwin404\Pay\Controllers\NotifyController@index', ['payType' => PayType::ALIPAY_WEB]),
                    'return_url' => action('\Edwin404\Pay\Controllers\ReturnController@index', ['payType' => PayType::ALIPAY_WEB]),
                    'return_raw' => true,
                ];
        }
        return null;
    }

    private function createAlipayWeb($biz, $bizId, $feeTotal, $title, $body, $option = [])
    {
        $config = $this->payConfig(PayType::ALIPAY_WEB);
        $order = $this->createPayOrder($biz, $bizId, PayType::ALIPAY_WEB, $feeTotal);
        $payData = [
            'body' => $body,
            'subject' => $title,
            'order_no' => config('pay.payOrderOutTradeNoPrefix') . '_' . $order['id'],
            'timeout_express' => time() + 3600 * 24,// 表示必须 600s 内付款
            'amount' => $feeTotal,
            'return_param' => '',
            'goods_type' => '0',
            'store_id' => '',
            'qr_mod' => '',
        ];
        try {
            $url = Charge::run(Config::ALI_CHANNEL_WEB, $config, $payData);
        } catch (PayException $e) {
            return Response::generate(-1, "创建支付错误(" . $e->errorMessage() . ")");
        }

        $this->update($order['id'], ['status' => PayOrderStatus::CREATED]);

        $data = [];
        $data['link'] = $url;

        return Response::generate(0, 'ok', $data);

    }

    private function createAlipay($biz, $bizId, $feeTotal, $title, $body, $option = [])
    {

        $this->initAlipay();

        // 设置支付宝参数
        if (!config('latrell-alipay.partner_id') || !config('latrell-alipay.seller_id') || !config('latrell-alipay-web.key')) {
            return Response::generate(-1, 'alipay config error');
        }

        $order = $this->createPayOrder($biz, $bizId, PayType::ALIPAY, $feeTotal);

        if (!empty($option['alipay_wap'])) {
            $alipay = app('alipay.wap');
        } else {
            $alipay = app('alipay.web');
        }
        $alipay->setOutTradeNo(config('pay.payOrderOutTradeNoPrefix') . '_' . $order['id']);
        $alipay->setTotalFee($feeTotal);
        $alipay->setSubject($title);
        $alipay->setBody($body);

        $data = [];
        $data['link'] = $alipay->getPayLink();

        $this->update($order['id'], [
            'status' => PayOrderStatus::CREATED,
        ]);

        return Response::generate(0, 'ok', $data);
    }

    public function handleOrderPay($type, $outTradeNo)
    {
        $shouldFireEvent = false;
        $order = null;

        Log::notice($type . ' notify -> data verification success.', [
            'out_trade_no' => $outTradeNo
        ]);

        $pieces = explode('_', $outTradeNo);
        if (count($pieces) != 2) {
            return Response::generate(-1, 'outTradeNo error');
        }

        $outTradeNoPrefix = $pieces[0];
        $orderId = $pieces[1];

        if ($outTradeNoPrefix != config('pay.payOrderOutTradeNoPrefix')) {
            return Response::generate(-1, 'outTradeNo prefix not match');
        }

        try {
            ModelHelper::transactionBegin();
            $order = ModelHelper::loadWithLock('pay_order', ['id' => $orderId]);
            if (empty($order)) {
                ModelHelper::transactionCommit();
                return Response::generate(-1, 'order not found');
            }
            if ($order['status'] == PayOrderStatus::CREATED) {
                Log::notice("aliay return -> update order to payed");
                ModelHelper::updateOne('pay_order', ['id' => $order['id']], [
                    'status' => PayOrderStatus::PAYED,
                    'timePay' => Carbon::now(),
                ]);
                $shouldFireEvent = true;
            }
            ModelHelper::transactionCommit();
        } catch (\Exception $e) {
            ModelHelper::transactionRollback();
        }

        if ($shouldFireEvent) {
            $event = new OrderPayedEvent();
            $event->biz = $order['biz'];
            $event->bizId = $order['bizId'];
            $event->order = $order;
            Event::fire($event);
            Log::notice('fire-order-log->' . print_r($event, true));
        }

        return Response::generate(0, null);
    }

}