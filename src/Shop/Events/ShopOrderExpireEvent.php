<?php

namespace Edwin404\Shop\Events;

class ShopOrderExpireEvent
{
    public $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }


}