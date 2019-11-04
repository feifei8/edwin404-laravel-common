<?php

namespace Edwin404\Shop\Jobs;

use Edwin404\Base\Support\BaseJob;
use Edwin404\Base\Support\Exception;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Shop\Types\OrderWorkerStatus;
use Illuminate\Support\Facades\Log;

class OrderWorkerJob extends BaseJob
{

    public $id;

    public function handle()
    {
        if (empty($this->id)) {
            throw new Exception('OrderWorkerJob -> id empty');
        }
        $orderWorker = ModelHelper::load('order_worker', ['id' => $this->id]);
        if (empty($orderWorker)) {
            throw new Exception('OrderWorkerJob -> order worker empty');
        }

        if ($orderWorker['status'] != OrderWorkerStatus::WAIT_START) {
            throw new Exception('OrderWorkerJob -> order worker status error -> ' . json_encode($orderWorker));
        }

        $order = ModelHelper::load('order', ['id' => $orderWorker['orderId']]);
        $orderGoods = ModelHelper::load('order_goods', ['id' => $orderWorker['orderGoodsId']]);

        if (empty($order)) {
            throw new Exception('OrderWorkerJob -> order empty -> ' . json_encode($orderWorker));
        }
        if (empty($orderGoods)) {
            throw new Exception('OrderWorkerJob -> order goods empty -> ' . json_encode($orderWorker));
        }

        $namespace = config('shop.orderWorkerNamespace', null);
        $cls = $namespace . '' . $orderWorker['worker'];
        if (!class_exists($cls)) {
            throw new Exception('OrderWorkerJob -> worker not exists -> ' . $cls . ' -> ' . json_encode($orderWorker));
        }

        $ins = new $cls();
        $ins->orderWorker = $orderWorker;
        $ins->order = $order;
        $ins->orderGoods = $orderGoods;
        try {
            $ins->markRunning();
            $ins->run();
            $ins->markCompleted();
        } catch (\Exception $e) {
            Log::error('OrderWorkerJob -> worker error -> ' . json_encode($ins));
            $ins->markFailed();
        } finally {
            $ins->saveLastResult();
        }

    }
}
