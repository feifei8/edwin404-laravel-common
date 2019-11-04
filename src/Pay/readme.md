## 使用步骤:

1. 创建数据库 2016_11_24_000000_create_pay_order
2. 注册 PayServiceProvider
3. 设置回调路由
4. 监听支付事件

```
Route::any('pay/return/{payType}','\Edwin404\Pay\Controllers\ReturnController@index');
Route::any('pay/notify/{payType}','\Edwin404\Pay\Controllers\NotifyController@index');
```


## 测试代码:

#### 创建订单

```
$ret = $payOrderService->create('test_pay', 3, PayType::ALIPAY, '0.01', '测试支付', '测试Body');
if ($ret['code']) {
    return Response::send(-1, $ret['msg']);
}
return redirect($ret['data']['link']);
```

#### 监听订单支付的事件 OrderPayedEvent

```
class PayListener
{
    public function onOrderPayed(OrderPayedEvent $event)
    {
        Log::info('order pay for xuanli -> ' . print_r($event, true));

        $biz = $event->biz;
        $bizId = $event->bizId;

    }

    public function subscribe($events)
    {
        $events->listen(
            OrderPayedEvent::class,
            '\App\Listeners\PayListener@onOrderPayed'
        );
    }
}
```