<?php

namespace Edwin404\Shop\Helpers;


use Edwin404\Base\Support\ModelHelper;
use Edwin404\Shop\Jobs\OrderWorkerJob;
use Edwin404\Shop\Types\OrderWorkerStatus;
use Illuminate\Foundation\Bus\DispatchesJobs;

class OrderWorkerHelper
{
    use DispatchesJobs;

    private static function instance()
    {
        static $instance = null;
        if (null === $instance) {
            return new OrderWorkerHelper();
        }
        return $instance;
    }

    public static function put($orderId, $orderGoodsId, $worker, $remark = null)
    {
        $data = [];
        $data['orderId'] = $orderId;
        $data['orderGoodsId'] = $orderGoodsId;
        $data['worker'] = $worker;
        $data['remark'] = $remark;
        $data['status'] = OrderWorkerStatus::WAIT_START;
        $data = ModelHelper::add('order_worker', $data);

        $job = new OrderWorkerJob();
        $job->id = $data['id'];
        self::instance()->dispatch($job);
    }

}