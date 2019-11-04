<?php
namespace Edwin404\Wechat\Services;

use Edwin404\Base\Support\FileHelper;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Wechat\Helpers\WechatHelper;
use Edwin404\Wechat\Message\MessageBase;
use Edwin404\Wechat\Message\MessageType;
use Edwin404\Wechat\Types\WechatQrcodeStatus;
use Edwin404\Wechat\Types\WechatQrcodeType;
use Illuminate\Support\Str;

class WechatService
{
    public function update($id, $data)
    {
        if ($data && array_key_exists('func', $data)) {
            $data['func'] = json_encode($data['func']);
        }
        return ModelHelper::updateOne('wechat_account', ['id' => $id], $data);
    }

    public function load($id)
    {
        $m = ModelHelper::load('wechat_account', ['id' => $id]);
        if ($m && array_key_exists('func', $m)) {
            $m['func'] = @json_decode($m['func'], true);
            if (!is_array($m['func'])) {
                $m['func'] = [];
            }
        }
        return $m;
    }

    public function loadByAlias($alias)
    {
        $m = ModelHelper::load('wechat_account', ['alias' => $alias]);
        if ($m && array_key_exists('func', $m)) {
            $m['func'] = @json_decode($m['func'], true);
            if (!is_array($m['func'])) {
                $m['func'] = [];
            }
        }
        return $m;
    }

    public function add($data)
    {
        if ($data && array_key_exists('func', $data)) {
            $data['func'] = @json_encode($data['func']);
        }
        $data['alias'] = strtolower(Str::random(32));
        return ModelHelper::add('wechat_account', $data);
    }

    public function loadPaymentByAccountId($accountId)
    {
        return ModelHelper::load('wechat_payment', ['accountId' => $accountId]);
    }

    public function updatePaymentByAccountId($accountId, $data = [])
    {
        if (empty($data)) {
            return;
        }
        $wechatPayment = ModelHelper::load('wechat_payment', ['accountId' => $accountId]);
        $certPath = storage_path('wechat/' . FileHelper::number2dir($accountId) . '/payment/cert.pem');
        $keyPath = storage_path('wechat/' . FileHelper::number2dir($accountId) . '/payment/key.pem');
        if (file_exists($certPath)) {
            @unlink($certPath);
        }
        if (file_exists($keyPath)) {
            @unlink($keyPath);
        }

        if (empty($wechatPayment)) {
            $data ['accountId'] = $accountId;
            return ModelHelper::add('wechat_payment', $data);
        } else {
            return ModelHelper::updateOne('wechat_payment', ['id' => $wechatPayment['id']], $data);
        }
    }

    public function generateAccountAlias()
    {
        $times = 0;
        do {
            $alias = strtolower(Str::random(32));
        } while ($times++ < 10 && ModelHelper::model('wechat_account')->where(['alias' => $alias])->exists());
        return $alias;
    }


    public function loadAccountByAppIdAndAuthType($appId, $authType)
    {
        $m = ModelHelper::load('wechat_account', ['appId' => $appId, 'authType' => $authType]);
        if ($m && array_key_exists('func', $m)) {
            $m['func'] = @json_decode($m['func'], true);
            if (!is_array($m['func'])) {
                $m['func'] = [];
            }
        }
        return $m;
    }

    public function putSubscribe($accountId, $data)
    {
        if ($data && array_key_exists('reply', $data)) {
            $data['reply'] = json_encode($data['reply']);
        }
        $m = ModelHelper::load('wechat_subscribe', ['accountId' => $accountId]);
        if (empty($m)) {
            $data['accountId'] = $accountId;
            ModelHelper::add('wechat_subscribe', $data);
        } else {
            ModelHelper::updateOne('wechat_subscribe', ['accountId' => $accountId], $data);
        }
    }

    public function getSubscribe($accountId)
    {
        $m = ModelHelper::load('wechat_subscribe', ['accountId' => $accountId]);
        if (empty($m)) {
            $m = ModelHelper::add('wechat_subscribe', ['accountId' => $accountId]);
        }
        $m['reply'] = @json_decode($m['reply'], true);
        return $m;
    }

    public function putMenu($accountId, $menuArray)
    {
        if (!is_array($menuArray)) {
            return Response::generate(-1, '菜单数据有误');
        }

        if (empty($menuArray)) {
            return Response::generate(-1, '菜单数据为空');
        }

        foreach ($menuArray as $col => &$menu1) {
            if (empty($menu1['title'])) {
                return Response::generate(-1, '菜单' . ($col + 1) . '标题不能为空');
            }
            if (!empty($menu1['child']) && is_array($menu1['child'])) {
                // 包含二级菜单
                foreach ($menu1['child'] as $row => &$menu2) {
                    if (empty($menu2['data'])) {
                        return Response::generate(-1, '菜单' . ($col + 1) . '-' . ($row + 1) . '数据不能为空');
                    }
                    if (empty($menu2['data']['k'])) {
                        $menu2['data']['k'] = 'kk_' . time() . '_' . strtolower(Str::random(5));
                    }
                    $message = MessageBase::fromArray($menu2['data']);
                    if (empty($message)) {
                        return Response::generate(-1, '菜单' . ($col + 1) . '-' . ($row + 1) . '数据格式不正确');
                    }
                }
            } else {
                // 只有一级菜单
                if (empty($menu1['data'])) {
                    return Response::generate(-1, '菜单' . ($col + 1) . '数据不能为空');
                }
                if (empty($menu1['data']['k'])) {
                    $menu1['data']['k'] = 'kk_' . time() . '_' . strtolower(Str::random(5));
                }
                $message = MessageBase::fromArray($menu1['data']);
                if (empty($message)) {
                    return Response::generate(-1, '菜单' . ($col + 1) . '数据格式不正确');
                }
            }
        }

        $data = json_encode($menuArray);
        if (strlen($data) > 65535) {
            return Response::generate(-1, '菜单数据太长,不能保存');
        }

        $m = ModelHelper::load('wechat_menu', ['accountId' => $accountId]);
        if (empty($m)) {
            ModelHelper::add('wechat_menu', ['accountId' => $accountId, 'data' => $data]);
        } else {
            ModelHelper::updateOne('wechat_menu', ['id' => $m['id']], ['data' => $data]);
        }

        return Response::generate(0, null, $menuArray);
    }

    public function getMenu($accountId)
    {
        $m = ModelHelper::load('wechat_menu', ['accountId' => $accountId]);
        if (empty($m)) {
            return null;
        }
        return @json_decode($m['data'], true);
    }

    public function getMenuWechatFormat($accountId)
    {
        $m = ModelHelper::load('wechat_menu', ['accountId' => $accountId]);
        if (empty($m)) {
            return Response::generate(-1, 'wechat menu not found');
        }

        $menuArray = @json_decode($m['data'], true);
        if (empty($menuArray) || !is_array($menuArray)) {
            return Response::generate(-1, 'menu data error');
        }

        $wechatMenuData = [];
        foreach ($menuArray as $menu1) {
            $menu = [];
            if (!empty($menu1['child']) && is_array($menu1['child'])) {
                // 包含二级菜单
                $menu['name'] = $menu1['title'];
                $menu['sub_button'] = [];
                foreach ($menu1['child'] as $menu2) {
                    $menuItem = $this->convertMenuItemToWechatFormat($menu2);
                    if ($menuItem) {
                        $menu['sub_button'][] = $menuItem;
                    }
                }
                if (empty($menu['sub_button'])) {
                    $menu = null;
                }
            } else {
                // 只有一级菜单
                $menu = $this->convertMenuItemToWechatFormat($menu1);
            }

            if (empty($menu)) {
                continue;
            }
            $wechatMenuData[] = $menu;
        }

        if (empty($wechatMenuData)) {
            return Response::generate(-1, 'convert wechat menu error');
        }

        return Response::generate(0, null, $wechatMenuData);
    }

    public function convertMenuItemToWechatFormat($menu)
    {
        if (empty($menu['title'])) {
            return null;
        }
        if (empty($menu['data'])) {
            return null;
        }
        $message = MessageBase::fromArray($menu['data']);
        if (empty($message)) {
            return null;
        }
        if (!$message->k) {
            return null;
        }
        switch ($message->type) {
            case MessageType::LINK:
                $menuItem = [];
                $menuItem['type'] = 'view';
                $menuItem['name'] = $menu['title'];
                $menuItem['url'] = $message->url;
                return $menuItem;
            case MessageType::IMAGE:
            case MessageType::TEXT:
            case MessageType::CUSTOM:
                $menuItem = [];
                $menuItem['type'] = 'click';
                $menuItem['name'] = $menu['title'];
                $menuItem['key'] = $message->k;
                return $menuItem;
        }
        return null;
    }

    // 1 - 100000000 永久二维码
    // 100000000 ~   临时二维码
    const QRCODE_TEMP_SCENE_MIN = 100000000;

    public function createQrcode($accountId, $type, $usage, $option = ['expire' => 2592000,])
    {
        $app = WechatHelper::app($accountId);
        if (empty($app)) {
            return Response::generate(-1, '公众号不存在');
        }

        $qrcode = ModelHelper::load('wechat_qrcode', ['accountId' => $accountId, 'usage' => $usage, 'type' => $type, 'status' => WechatQrcodeStatus::VALID,]);
        if (!empty($qrcode)) {
            switch ($type) {
                case WechatQrcodeType::FOREVER:
                    return Response::generate(0, '二维码已经存在', $qrcode);

                case WechatQrcodeType::TEMP:
                    if (empty($qrcode['expire']) || strtotime($qrcode['expire']) < time() + 60) {
                        ModelHelper::updateOne('wechat_qrcode', ['id' => $qrcode['id']], ['status' => WechatQrcodeStatus::INVALID]);
                    } else {
                        return Response::generate(0, '二维码已经存在', $qrcode);
                    }
            }
        }

        try {
            ModelHelper::transactionBegin();
            //为了保证同一个账户同时只有一个进入
            $account = ModelHelper::loadWithLock('wechat_account', ['id' => $accountId]);
            $qrcode = ModelHelper::load('wechat_qrcode', ['accountId' => $accountId, 'status' => WechatQrcodeStatus::INVALID, 'type' => $type]);
            if (empty($qrcode)) {
                $qrcode = [];
                $qrcode['accountId'] = $accountId;
                $qrcode['type'] = $type;
                $qrcode['status'] = WechatQrcodeStatus::INVALID;
                if (WechatQrcodeType::TEMP == $type) {
                    $qrcode['sceneTemp'] = intval(ModelHelper::model('wechat_qrcode')->where(['accountId' => $accountId, 'type' => $type])->max('sceneTemp')) + 1;
                    $qrcode['scene'] = 0;
                } elseif (WechatQrcodeType::FOREVER == $type) {
                    $qrcode['scene'] = intval(ModelHelper::model('wechat_qrcode')->where(['accountId' => $accountId, 'type' => $type])->max('scene')) + 1;
                    $qrcode['sceneTemp'] = 0;
                }
                $qrcode = ModelHelper::add('wechat_qrcode', $qrcode);
            }
            if (empty($qrcode)) {
                ModelHelper::transactionCommit();
                return Response::generate(-2, '创建二维码失败');
            }


            if ($type == WechatQrcodeType::TEMP) {
                if (empty($option['expire'])) {
                    $option['expire'] = 7 * 24 * 3600;
                }
                $ret = $app->qrcode->temporary(self::QRCODE_TEMP_SCENE_MIN + $qrcode['sceneTemp'], $option['expire'])->toArray();
                if (empty($ret['ticket']) || empty($ret['expire_seconds']) || empty($ret['url'])) {
                    ModelHelper::transactionCommit();
                    return Response::generate(-1, '生成临时二维码出错', $ret);
                }
                $update = [];
                $update['status'] = WechatQrcodeStatus::VALID;
                $update['expire'] = date('Y-m-d H:i:s', time() + $ret['expire_seconds']);
                $update['ticket'] = $ret['ticket'];
                $update['url'] = $ret['url'];
                $update['usage'] = $usage;
                $qrcode = ModelHelper::updateOne('wechat_qrcode', ['id' => $qrcode['id']], $update);
                ModelHelper::transactionCommit();
                return Response::generate(0, null, $qrcode);
            }


            if ($type == WechatQrcodeType::FOREVER) {
                $ret = $app->qrcode->forever($qrcode['scene'])->toArray();
                if (empty($ret['ticket']) || empty($ret['url'])) {
                    return Response::generate(-1, '生成永久二维码出错', $ret);
                }
                $update = [];
                $update['status'] = WechatQrcodeStatus::VALID;
                $update['expire'] = null;
                $update['ticket'] = $ret['ticket'];
                $update['url'] = $ret['url'];
                $update['usage'] = $usage;
                $qrcode = ModelHelper::updateOne('wechat_qrcode', ['id' => $qrcode['id']], $update);
                ModelHelper::transactionCommit();
                return Response::generate(0, null, $qrcode);
            }

            ModelHelper::transactionCommit();
            return Response::generate(-1, '生成二维码出错');
        } catch (\Exception $e) {

            ModelHelper::transactionRollback();
            throw $e;

        }

    }

    public function loadQrcodeByAccountIdAndScene($accountId, $scene)
    {
        $scene = $scene . '';
        if ($scene > self::QRCODE_TEMP_SCENE_MIN) {
            $wechatQrcode = ModelHelper::load('wechat_qrcode', ['accountId' => $accountId, 'sceneTemp' => $scene - self::QRCODE_TEMP_SCENE_MIN, 'status' => WechatQrcodeStatus::VALID]);
        } else {
            $wechatQrcode = ModelHelper::load('wechat_qrcode', ['accountId' => $accountId, 'scene' => $scene, 'status' => WechatQrcodeStatus::VALID]);
        }
        if (empty($wechatQrcode)) {
            return null;
        }
        return $wechatQrcode;
    }

    public function getQrcodeCountByStatusAndType($accountId, $status, $type)
    {
        return ModelHelper::count('wechat_qrcode', ['accountId' => $accountId, 'status' => $status, 'type' => $type]);
    }

//
//    public function loadAccountByAlias($accountAlias)
//    {
//        $wechatAccount = WechatAccount::where(['alias' => $accountAlias])->first();
//        if (empty($wechatAccount)) {
//            return null;
//        }
//        $wechatAccount->func = @json_decode($wechatAccount->func);
//        return $wechatAccount->toArray();
//    }
//
//    public function loadAccount($id)
//    {
//        $wechatAccount = WechatAccount::where(['id' => $id])->first();
//        if (empty($wechatAccount)) {
//            return null;
//        }
//        $wechatAccount->func = @json_decode($wechatAccount->func);
//        return $wechatAccount->toArray();
//    }
//
//    public function updateAccount($id, $data = [])
//    {
//        if (empty($data)) {
//            return;
//        }
//        $wechatAccount = WechatAccount::where(['id' => $id])->first();
//        if (empty($wechatAccount)) {
//            return;
//        }
//        foreach ($data as $k => $v) {
//            $wechatAccount->$k = $v;
//        }
//        $wechatAccount->save();
//    }
//
//    public function existAccount($id)
//    {
//        $wechatAccount = WechatAccount::where(['id' => $id])->first();
//        if (empty($wechatAccount)) {
//            return false;
//        }
//        return true;
//    }
//
//    public function addAccount($data = [])
//    {
//        $wechatAccount = new WechatAccount();
//        foreach ($data as $k => $v) {
//            $wechatAccount->$k = $v;
//        }
//        $wechatAccount->save();
//
//        return Response::generate(0, null, ['id' => $wechatAccount->id]);
//    }
//
//    public function addPayment($data)
//    {
//        $wechatPayment = new WechatPayment();
//        foreach ($data as $k => $v) {
//            $wechatPayment->$k = $v;
//        }
//        $wechatPayment->save();
//    }
//

//

//
//    public function appByAlias($accountAlias)
//    {
//        $wechatAccount = $this->loadAccountByAlias($accountAlias);
//        if (empty($wechatAccount)) {
//            return null;
//        }
//        return $this->app(0, $wechatAccount);
//    }
//
//    /**
//     * @param int $accountId
//     * @param null $wechatAccount
//     * @return Application
//     */


}