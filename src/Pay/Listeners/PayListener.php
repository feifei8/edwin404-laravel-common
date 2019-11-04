<?php

namespace Edwin404\Pay\Listeners;

use Edwin404\Pay\Events\OrderPayedEvent;
use Illuminate\Support\Facades\Log;

class PayListener
{
    public function onOrderPayed(OrderPayedEvent $event)
    {
        Log::info('order pay -> ' . print_r($event, true));
    }

    public function subscribe($events)
    {
        $events->listen(
            OrderPayedEvent::class,
            '\Edwin404\Pay\Listeners\PayListener@onOrderPayed'
        );
    }
}