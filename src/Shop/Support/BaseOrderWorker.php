<?php

namespace Edwin404\Shop\Support;


use Carbon\Carbon;
use Edwin404\Base\Support\Exception;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Shop\Types\OrderWorkerStatus;

abstract class BaseOrderWorker
{
    public $orderWorker;
    public $order;
    public $orderGoods;

    private $lastResult;

    // 继承并实现,如果有异常直接抛出异常
    public abstract function run();

    // 可以调用这个方法保存结果
    public function setLastResult($lastResult)
    {
        $this->lastResult = $lastResult;
    }

    public function markRunning()
    {
        $this->markStatus(OrderWorkerStatus::RUNNING);
    }

    public function markFailed()
    {
        $this->markStatus(OrderWorkerStatus::FAILED);
    }

    public function markCompleted()
    {
        $this->markStatus(OrderWorkerStatus::COMPLETED);
    }

    public function saveLastResult()
    {
        $this->orderWorker = ModelHelper::updateOne(
            'order_worker',
            ['id' => $this->orderWorker['id']],
            ['lastResult' => $this->lastResult,]
        );
    }

    private function markStatus($status)
    {
        if (empty($this->orderWorker)) {
            throw new Exception('OrderWorker empty');
        }
        $this->orderWorker = ModelHelper::updateOne('order_worker',
            ['id' => $this->orderWorker['id']],
            [
                'status' => $status,
                'runTime' => Carbon::now(),
            ]
        );
    }


}