<?php

namespace Edwin404\Pay\Events;

// 这个事件只会触发一次,在监听支付回调时已经进行过去重处理
// !! 注意，很容易出现一个PayListener被多次监听，这样会被多次调用
class OrderPayedEvent
{
    public $biz;
    public $bizId;
    public $order;
}