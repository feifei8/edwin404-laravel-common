<?php

namespace Edwin404\Shop\Jobs;

use Edwin404\Base\Support\BaseJob;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Shop\Events\ShopOrderExpireEvent;
use Edwin404\Shop\Types\OrderStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class OrderExpireCheckJob extends BaseJob
{
    public $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        ModelHelper::transactionBegin();
        $order = ModelHelper::loadWithLock('order', ['id' => $this->orderId]);
        if (empty($order)) {
            ModelHelper::transactionCommit();
            return;
        }
        if ($order['status'] != OrderStatus::WAIT_PAY) {
            ModelHelper::transactionCommit();
            return;
        }

        $orderGoods = ModelHelper::model('order_goods')->where(['orderId' => $order['id']])->lockForUpdate()->get()->toArray();
        ModelHelper::modelJoin($orderGoods, 'goodsSnapshotId', '_goodsSnapshot', 'goods_snapshot', 'id');

        foreach ($orderGoods as $orderGood) {
            if ($orderGood['_goodsSnapshot']['specSpec']) {
                // 包含规格的商品
                ModelHelper::change(
                    'goods_spec',
                    ['goodsId' => $orderGood['goodsId'], 'spec' => $orderGood['_goodsSnapshot']['specSpec'],],
                    'stock',
                    +$orderGood['amount']
                );
                Log::info('goods.' . $orderGood['goodsId'] . '.' . $orderGood['_goodsSnapshot']['specSpec'] . ' stock +' . $orderGood['amount']);
            } else {
                // 不包含规格的商品
                ModelHelper::change(
                    'goods',
                    ['id' => $orderGood['goodsId']],
                    'stock',
                    +$orderGood['amount']
                );
                Log::info('goods.' . $orderGood['goodsId'] . ' stock +' . $orderGood['amount']);
            }
        }

        ModelHelper::updateOne('order', ['id' => $this->orderId], ['status' => OrderStatus::CANCEL_EXPIRED]);

        ModelHelper::transactionCommit();

        Event::fire(new ShopOrderExpireEvent($this->orderId));

    }
}
