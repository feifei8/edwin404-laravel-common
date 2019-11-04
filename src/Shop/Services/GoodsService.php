<?php

namespace Edwin404\Shop\Services;


use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\TreeHelper;
use Edwin404\Common\Type\SwitchType;
use Edwin404\Shop\Helpers\GoodsHelper;
use Edwin404\Shop\Types\GoodsSaleStatus;
use Illuminate\Support\Facades\Cache;

class GoodsService
{
    const CACHE_KEY_PREFIX = 'edwin404.shop.goods.';

    public function listCategory($cacheMinutes = 60)
    {
        if (!$cacheMinutes) {
            return ModelHelper::find('category');
        } else {
            return Cache::remember(self::CACHE_KEY_PREFIX . 'category', $cacheMinutes, function () {
                return ModelHelper::find('category');
            });
        }
    }

    public function listChildCategoryTree($pid = 0, $cacheMinutes = 60)
    {
        $categories = self::listCategory($cacheMinutes);
        $categoryTree = TreeHelper::nodeMerge($categories, $pid, 'id', 'pid', 'sort', 'asc');
        return $categoryTree;
    }

    public function getCategory($id, $cacheMinutes = 60)
    {
        if (!$cacheMinutes) {
            $categories = ModelHelper::find('category');
        } else {
            $categories = Cache::remember(self::CACHE_KEY_PREFIX . 'category', $cacheMinutes, function () {
                return ModelHelper::find('category');
            });
        }
        foreach ($categories as $category) {
            if ($category['id'] == $id) {
                return $category;
            }
        }
        return null;
    }

    public function getCategoryChildIds($categoryId, $cacheMinutes = 60)
    {
        $categories = $this->listCategory($cacheMinutes);
        $categoryIds = TreeHelper::allChildIds($categories, $categoryId);
        return $categoryIds;
    }

    public function clearCategoryCache()
    {
        Cache::forget(self::CACHE_KEY_PREFIX . 'category');
    }

    public function get($goodsId)
    {
        $goods = ModelHelper::load('goods', ['id' => $goodsId]);
        return $goods;
    }

    public function getDetail($goodsId)
    {
        $detail = ModelHelper::load('goods_detail', ['goodsId' => $goodsId]);
        if ($detail) {
            if (isset($detail['images'])) {
                $detail['images'] = @json_decode($detail['images'], true);
                if (empty($detail['images'])) {
                    $detail['images'] = [];
                }
            }
        }
        return $detail;
    }

    public function getAttr($goodsId)
    {
        $ms = ModelHelper::find('goods_attr', ['goodsId' => $goodsId]);
        $attr = [];
        foreach ($ms as $m) {
            $attr[] = [
                'name' => $m['name'],
                'value' => $m['value'],
            ];
        }
        return $attr;
    }

    /**
     * 商品的规格数据
     *
     * @return array [
     *  'param'=>[
     *      [name=>大小,values=>[L,M,S]],
     *      [name=>颜色,values=>[红色,黄色,绿色]],
     *  ],
     *  'map'=>[
     *     大小=>[L,M,S],
     *     颜色=>[红色,黄色,绿色],
     * ],
     *  'info'=>[
     *    '大小:L|颜色:红色'=>[ price=>1,marketPrice=>2,stock=>999,cover=>xxxx, ],
     *    '大小:L|颜色:绿色'=>[ price=>1,marketPrice=>2,stock=>999,cover=>xxxx, ],
     * ],
     * ]
     */
    public function getSpec($goodsId)
    {
        $map = [];
        $info = [];
        $param = [];

        if ($goodsId) {
            $ms = ModelHelper::find('goods_spec', ['goodsId' => $goodsId]);
            foreach ($ms as $m) {
                $pcs = explode('|', $m['spec']);
                foreach ($pcs as $pc) {
                    $pair = explode(':', $pc);
                    $pair[0] = trim($pair[0]);
                    $pair[1] = trim($pair[1]);
                    if (!isset($map[$pair[0]])) {
                        $map[$pair[0]] = [$pair[1]];
                    } else {
                        if (!in_array($pair[1], $map[$pair[0]])) {
                            $map[$pair[0]][] = $pair[1];
                        }
                    }
                }
                $spec = $m['spec'];
                unset($m['id']);
                unset($m['created_at']);
                unset($m['updated_at']);
                unset($m['goodsId']);
                unset($m['spec']);
                $info[$spec] = $m;
            }
        }

        foreach ($map as $k => $v) {
            $param[] = [
                'name' => $k,
                'values' => $v,
            ];
        }

        return [
            'param' => $param,
            'map' => $map,
            'info' => $info,
        ];
    }

    /**
     * @param $goodsId
     * @return array
     *
     * @deprecated
     * 暂时不知道这个函数有什么用处
     */
    public function getSpecWithStock($goodsId)
    {
        $map = [];
        $info = [];

        if ($goodsId) {
            $ms = ModelHelper::find('goods_spec', ['goodsId' => $goodsId]);
            foreach ($ms as $m) {
                if (empty($m['stock'])) {
                    continue;
                }
                $pcs = explode('|', $m['spec']);
                foreach ($pcs as $pc) {
                    $pair = explode(':', $pc);
                    $pair[0] = trim($pair[0]);
                    $pair[1] = trim($pair[1]);
                    if (!isset($map[$pair[0]])) {
                        $map[$pair[0]] = [$pair[1]];
                    } else {
                        if (!in_array($pair[1], $map[$pair[0]])) {
                            $map[$pair[0]][] = $pair[1];
                        }
                    }
                }
                $spec = $m['spec'];
                unset($m['id']);
                unset($m['created_at']);
                unset($m['updated_at']);
                unset($m['goodsId']);
                unset($m['spec']);
                $info[$spec] = $m;
            }
        }

        return [
            'map' => $map,
            'info' => $info,
        ];
    }

    public function isSpecGoods($goodsId)
    {
        return ModelHelper::exists('goods_spec', ['goodsId' => $goodsId]);
    }

    public function paginateGoodsByCategory($categoryId, $page, $pageSize, $option = [])
    {
        $childCategoryIds = $this->getCategoryChildIds($categoryId);
        $childCategoryIds[] = $categoryId;
        if (isset($option['whereIn'])) {
            $option['whereIn'][] = ['categoryId', $childCategoryIds];
        } else {
            $option['whereIn'] = ['categoryId', $childCategoryIds];
        }
        $option['where']['saleStatus'] = GoodsSaleStatus::ON;
        $option['where']['isVisible'] = SwitchType::YES;
        $paginateData = ModelHelper::modelPaginate('goods', $page, $pageSize, $option);
        if (!empty($paginateData['records'])) {
            $goodsIds = array_pluck($paginateData['records'], 'id');
            $goodsSpecs = ModelHelper::model('goods_spec')->whereIn('goodsId', $goodsIds)->get()->toArray();
            $goodsStock = [];
            foreach ($goodsSpecs as $goodsSpec) {
                if (!isset($goodsStock[$goodsSpec['goodsId']])) {
                    $goodsStock[$goodsSpec['goodsId']] = $goodsSpec['stock'];
                } else {
                    $goodsStock[$goodsSpec['goodsId']] += $goodsSpec['stock'];
                }
            }
            foreach ($paginateData['records'] as &$record) {
                if (isset($goodsStock[$record['id']])) {
                    $record['_stock'] = $goodsStock[$record['id']];
                } else {
                    $record['_stock'] = $record['stock'];
                }
            }
        }
        return $paginateData;
    }

    public function paginateGoodsByKeyword($keyword, $page, $pageSize, $option = [])
    {
        $option['whereOperate'] = ['title', 'like', '%' . $keyword . '%'];
        $option['where']['saleStatus'] = GoodsSaleStatus::ON;
        $option['where']['isVisible'] = SwitchType::YES;
        $paginateData = ModelHelper::modelPaginate('goods', $page, $pageSize, $option);
        if (!empty($paginateData['records'])) {
            $goodsIds = array_pluck($paginateData['records'], 'id');
            $goodsSpecs = ModelHelper::model('goods_spec')->whereIn('goodsId', $goodsIds)->get()->toArray();
            $goodsStock = [];
            foreach ($goodsSpecs as $goodsSpec) {
                if (!isset($goodsStock[$goodsSpec['goodsId']])) {
                    $goodsStock[$goodsSpec['goodsId']] = $goodsSpec['stock'];
                } else {
                    $goodsStock[$goodsSpec['goodsId']] += $goodsSpec['stock'];
                }
            }
            foreach ($paginateData['records'] as &$record) {
                if (isset($goodsStock[$record['id']])) {
                    $record['_stock'] = $goodsStock[$record['id']];
                } else {
                    $record['_stock'] = $record['stock'];
                }
            }
        }
        return $paginateData;
    }

    public function addCart($memberUserId, $goodsId, $spec = null, $amount = 1)
    {
        if (empty($spec)) {
            $spec = '';
        }
        $cartGoodsExists = ModelHelper::find('cart', ['memberUserId' => $memberUserId, 'goodsId' => $goodsId]);
        if (!empty($cartGoodsExists)) {
            foreach ($cartGoodsExists as $goods) {
                if ($goods['spec'] == $spec) {
                    return ModelHelper::updateOne('cart', ['id' => $goods['id']], ['amount' => $goods['amount'] + $amount]);
                }
            }
        }
        return ModelHelper::add('cart', [
            'memberUserId' => $memberUserId,
            'goodsId' => $goodsId,
            'spec' => $spec,
            'amount' => $amount,
        ]);
    }

    public function updateCart($id, $data)
    {
        return ModelHelper::updateOne('cart', ['id' => $id], $data);
    }

    public function updateOrder($orderId, $data)
    {
        return ModelHelper::updateOne('order', ['id' => $orderId], $data);
    }

    public function getCartCount($memberUserId)
    {
        return ModelHelper::count('cart', ['memberUserId' => $memberUserId]);
    }

    public function listCartWithGoodsInfo($memberUserId, $cartIds = null)
    {
        $carts = ModelHelper::model('cart')
            ->where(['memberUserId' => $memberUserId]);
        if (null != $cartIds) {
            $carts = $carts->whereIn('id', $cartIds);
        }
        $carts = $carts->orderBy('id', 'desc')->get()->toArray();
        ModelHelper::modelJoin($carts, 'goodsId', '_goods', 'goods', 'id');
        foreach ($carts as &$cart) {
            if (empty($cart['_goods'])) {
                continue;
            }
            if (!empty($cart['spec'])) {
                $spec = ModelHelper::load('goods_spec', ['goodsId' => $cart['goodsId'], 'spec' => $cart['spec']]);
                if (empty($spec)) {
                    $cart['spec'] = [];
                    continue;
                }
                $cart['_goodsSpec'] = $spec;

                $cart['spec'] = GoodsHelper::unified2KeyValue($cart['spec']);
                $cart['_goodsSpec']['spec'] = GoodsHelper::unified2KeyValue($cart['_goodsSpec']['spec']);

            }
        }
        return $carts;
    }

    public function getCartWithGoodsInfo($cartId)
    {
        $cart = ModelHelper::load('cart', ['id' => $cartId]);
        if (empty($cart)) {
            return null;
        }
        $cart['_goods'] = ModelHelper::load('goods', ['id' => $cart['goodsId']]);
        if (!empty($cart['_goods'])) {
            if (!empty($cart['spec'])) {
                $spec = ModelHelper::load('goods_spec', ['goodsId' => $cart['goodsId'], 'spec' => $cart['spec']]);
                if ($spec) {
                    $cart['_goodsSpec'] = $spec;
                    $cart['spec'] = GoodsHelper::unified2KeyValue($cart['spec']);
                    $cart['_goodsSpec']['spec'] = GoodsHelper::unified2KeyValue($cart['_goodsSpec']['spec']);
                }
            }
        }
        return $cart;
    }

    public function getSnapshot($goodsId, $snapshotId)
    {
        $goodsSnapshot = ModelHelper::load('goods_snapshot', [
            'id' => $snapshotId,
            'goodsId' => $goodsId,
        ]);
        if ($goodsSnapshot) {
            $goodsSnapshot['attrData'] = @json_decode($goodsSnapshot['attrData'], true);
            $goodsSnapshot['specSpec'] = GoodsHelper::unified2KeyValue($goodsSnapshot['specSpec']);
            $goodsSnapshot['detailImages'] = @json_decode($goodsSnapshot['detailImages'], true);
        }
        return $goodsSnapshot;
    }

    public function getOrCreateSnapshot($goodsId, $spec = '')
    {
        if (empty($spec)) {
            $spec = '';
        }
        $goods = ModelHelper::load('goods', ['id' => $goodsId]);
        if (empty($goods)) {
            return null;
        }

        if (is_array($spec)) {
            $spec = GoodsHelper::keyValue2Unified($spec);
        }

        if ($spec) {
            $goodsSpec = ModelHelper::load('goods_spec', ['goodsId' => $goodsId, 'spec' => $spec]);
            if (empty($goodsSpec)) {
                return null;
            }
        } else {
            $goodsSpec = null;
        }

        $goodsAttrs = ModelHelper::model('goods_attr')->where(['goodsId' => $goodsId])->orderBy('id', 'desc')->get()->toArray();

        $goodsDetail = ModelHelper::load('goods_detail', ['goodsId' => $goodsId]);

        $where = [];
        $where['goodsId'] = $goods['id'];
        $where['goodsUpdatedTime'] = $goods['updated_at'];
        $where['specUpdatedTime'] = $goodsSpec ? $goodsSpec['updated_at'] : '2000-01-01 00:00:00';
        $where['goodsUpdatedTime'] = empty($goodsAttrs) ? '2000-01-01 00:00:00' : $goodsAttrs[0]['updated_at'];
        $where['detailUpdatedTime'] = $goodsDetail['updated_at'];

        $goodsSnapshot = ModelHelper::load('goods_snapshot', $where);
        if (!empty($goodsSnapshot)) {
            return $goodsSnapshot;
        }

        $goodsSnapshot = [];
        foreach ($where as $k => $v) {
            $goodsSnapshot[$k] = $v;
        }

        $goodsSnapshot['goodsId'] = $goods['id'];

        $goodsKeys = [
            'categoryId', 'title', 'price', 'marketPrice', 'shippingPrice', 'credit', 'shippingCredit',
            'cover', 'isVirtual',
        ];
        $goodsSpecKeys = [
            'spec', 'price', 'marketPrice', 'credit', 'cover',
        ];

        foreach ($goodsKeys as $goodsKey) {
            if (isset($goods[$goodsKey])) {
                $goodsSnapshot[$goodsKey] = $goods[$goodsKey];
            }
        }
        if ($goodsSpec) {
            foreach ($goodsSpecKeys as $goodsSpecKey) {
                if (isset($goodsSpec[$goodsSpecKey])) {
                    $goodsSnapshot['spec' . ucfirst($goodsSpecKey)] = $goodsSpec[$goodsSpecKey];
                }
            }
        }
        $attrData = [];
        foreach ($goodsAttrs as $goodsAttr) {
            $attrData[] = [
                'name' => $goodsAttr['name'],
                'value' => $goodsAttr['value'],
            ];
        }
        $goodsSnapshot['attrData'] = json_encode($attrData);

        $goodsSnapshot['detailImages'] = $goodsDetail['images'];
        $goodsSnapshot['detailContent'] = $goodsDetail['content'];

        $goodsSnapshot = ModelHelper::add('goods_snapshot', $goodsSnapshot);

        return $goodsSnapshot;

    }

    public function paginateOrderInfo($memberUserId, $page, $pageSize, $option = [])
    {
        $option['where']['memberUserId'] = $memberUserId;
        $paginateData = ModelHelper::modelPaginate('order', $page, $pageSize, $option);
        foreach ($paginateData['records'] as &$record) {
            $orderGoods = ModelHelper::find('order_goods', ['orderId' => $record['id']]);
            if (!empty($orderGoods[0]['goodsSnapshotId'])) {
                ModelHelper::modelJoin($orderGoods, 'goodsSnapshotId', '_goodsSnapshot', 'goods_snapshot', 'id');
            }
            foreach ($orderGoods as &$orderGood) {
                if (!empty($orderGood['_goodsSnapshot'])) {
                    $orderGood['_goodsSnapshot']['specSpec'] = GoodsHelper::unified2KeyValue($orderGood['_goodsSnapshot']['specSpec']);
                }
            }
            $record['_orderGoods'] = $orderGoods;
        }
        return $paginateData;
    }

    public function getOrderInfo($orderId)
    {
        $order = ModelHelper::load('order', ['id' => $orderId]);
        if (empty($order)) {
            return null;
        }
        $orderGoods = ModelHelper::find('order_goods', ['orderId' => $order['id']]);
        if (!empty($orderGoods[0]['goodsSnapshotId'])) {
            ModelHelper::modelJoin($orderGoods, 'goodsSnapshotId', '_goodsSnapshot', 'goods_snapshot', 'id');
        }
        foreach ($orderGoods as &$orderGood) {
            if (!empty($orderGood['_goodsSnapshot'])) {
                $orderGood['_goodsSnapshot']['specSpec'] = GoodsHelper::unified2KeyValue($orderGood['_goodsSnapshot']['specSpec']);
            }
        }
        $order['_orderGoods'] = $orderGoods;

        $isAllVirtual = true;
        foreach ($orderGoods as &$orderGood) {
            if (isset($orderGood['_goodsSnapshot'])) {
                if (!$orderGood['_goodsSnapshot']['isVirtual']) {
                    $isAllVirtual = false;
                    break;
                }
            }
        }
        $order['_isAllVirtual'] = $isAllVirtual;

        return $order;
    }

    /**
     * 检查商品库存
     * 如果是包含规格的商品,需要指明spec参数
     * 如果是非规格商品,请忽略spec参数
     *
     * 如果该函数在事务中调用,请注意调用顺序,防止死锁
     *
     * @param $goodsId
     * @param int $amount
     * @param null $spec
     * @return bool
     */
    public function isGoodsStockValid($goodsId, $amount = 1, $spec = null)
    {
        if ($this->isSpecGoods($goodsId)) {
            if (empty($spec)) {
                return false;
            }
            $goodsSpec = ModelHelper::loadWithLock('goods_spec', ['goodsId' => $goodsId, 'spec' => $spec]);
            if (empty($goodsSpec)) {
                return false;
            }
            if ($goodsSpec['stock'] < $amount) {
                return false;
            }
        } else {
            $goods = ModelHelper::loadWithLock('goods', ['id' => $goodsId]);
            if (empty($goods)) {
                return false;
            }
            if ($goods['stock'] < $amount) {
                return false;
            }
        }
        return true;
    }

    public function deleteCart($memberUserId, $cartId)
    {
        $cart = ModelHelper::load('cart', ['id' => $cartId, 'memberUserId' => $memberUserId]);
        if (empty($cart)) {
            return;
        }
        ModelHelper::delete('cart', ['id' => $cart['id']]);
    }

}