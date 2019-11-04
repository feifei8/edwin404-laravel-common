<?php

namespace Edwin404\Wechat\Controllers;


use EasyWeChat\Message\Text;
use EasyWeChat\Server\BadRequestException;
use Edwin404\Config\Facades\ConfigFacade;
use Edwin404\Wechat\Events\LocationEvent;
use Edwin404\Wechat\Events\MenuClickEvent;
use Edwin404\Wechat\Events\ScanEvent;
use Edwin404\Wechat\Events\SubscribeEvent;
use Edwin404\Wechat\Events\TextRecvEvent;
use Edwin404\Wechat\Facades\WechatAuthorizationServerFacade;
use Edwin404\Wechat\Helpers\WechatHelper;
use Edwin404\Wechat\Services\WechatService;
use Edwin404\Wechat\Types\WechatAuthType;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use function PHPSTORM_META\type;

/**
 * Class HandleController
 * @package Edwin404\Wechat\Controllers
 *
 * @route
 * Route::any('handle/{appId}', '\Edwin404\Wechat\Controllers\HandleController@index');
 */
class HandleController extends Controller
{
    const TEST_APP_ID = 'wx570bc396a51b8ff8';

    public function index(WechatService $wechatService,
                          $appId = null,
                          $alias = null)
    {
        if ($appId == self::TEST_APP_ID) {
            $m = $wechatService->loadAccountByAppIdAndAuthType(self::TEST_APP_ID, WechatAuthType::OAUTH);
            if (!$m) {
                $wechatService->add([
                    'authType' => WechatAuthType::OAUTH,
                    'authStatus' => 1,
                    'name' => '发布测试',
                    'enable' => 1,
                    'appId' => self::TEST_APP_ID,
                    'alias' => '6orafl8dcfpt9pyflok2z66y8p39co8t',
                    'username' => 'gh_3c884a361561',
                ]);
            }
        }

        if ($appId == self::TEST_APP_ID . '_refresh') {
            $openId = ConfigFacade::get('wechatAuthorizationPublishTestOpenId');
            $queryAuthCode = ConfigFacade::get('wechatAuthorizationPublishTestQueryAuthCode');
            if ($openId && $queryAuthCode) {
                $account = $wechatService->loadAccountByAppIdAndAuthType(self::TEST_APP_ID, WechatAuthType::OAUTH);
                $app = WechatHelper::app($account['id'], $account);
                $app->staff->message(new Text(['content' => $queryAuthCode . '_from_api']))->to($openId)->send();
                ConfigFacade::set('wechatAuthorizationPublishTestOpenId', null);
                ConfigFacade::set('wechatAuthorizationPublishTestQueryAuthCode', null);
                return 'OK';
            }
            return '<script>setTimeout(function(){window.location.reload();},1000);</script>Waiting...';
        }

        if ($alias) {
            $account = $wechatService->loadAccountByAppIdAndAuthType($appId, WechatAuthType::CONFIG);
            if (empty($account)) {
                return 'success';
            }
        } else {
            $account = $wechatService->loadAccountByAppIdAndAuthType($appId, WechatAuthType::OAUTH);
            if (empty($account)) {
                return 'success';
            }
        }
        $app = WechatHelper::app($account['id']);
        if (empty($app)) {
            return 'success';
        }

        $app->server->setMessageHandler(function ($message) use (&$app, &$wechatService) {
            switch ($message->MsgType) {
                case 'event':
                    // 事件消息
                    if ($app->account['appId'] == self::TEST_APP_ID) {
                        $app->setReply(new Text(['content' => $message->Event . 'from_callback']));
                    }
                    switch ($message->Event) {
                        case 'CLICK':
                            // EventKey	事件KEY值，与自定义菜单接口中KEY值对应
                            $eventKey = $message->EventKey;
                            if ($eventKey) {
                                // 菜单点击事件
                                $event = new MenuClickEvent();
                                $event->app = &$app;
                                $event->data = &$message;
                                $event->key = &$eventKey;
                                Event::fire($event);
                            }
                            break;
                        case 'SCAN':
                            // EventKey	事件KEY值，是一个32位无符号整数，即创建二维码时的二维码scene_id
                            // Ticket	二维码的ticket，可用来换取二维码图片
                            $eventKey = $message->EventKey;
                            if ($eventKey) {
                                // 扫一扫消息(参数二维码)
                                $event = new ScanEvent();
                                $event->app = &$app;
                                $event->data = &$message;
                                $event->scene = $eventKey;
                                $event->isSubscribe = false;
                                Event::fire($event);
                            }
                            break;
                        case 'LOCATION':
                            // Latitude  地理位置纬度
                            // Longitude 地理位置经度
                            // Precision 地理位置精度
                            $latitude = $message->Latitude;
                            $longitude = $message->Longitude;
                            $precision = $message->Precision;
                            if ($latitude && $longitude && $precision) {
                                // 上报地理位置事件
                                $event = new LocationEvent();
                                $event->app = &$app;
                                $event->data = &$message;
                                $event->latitude = $latitude;
                                $event->longitude = $longitude;
                                $event->precision = $precision;
                                Event::fire($event);
                            }
                            break;
                        case 'subscribe':
                            // EventKey	事件KEY值，qrscene_为前缀，后面为二维码的参数值
                            // Ticket	二维码的ticket，可用来换取二维码图片

                            $subscribeEvent = new SubscribeEvent();
                            $subscribeEvent->app = &$app;
                            $subscribeEvent->data = &$message;

                            $eventKey = $message->EventKey;
                            if ($eventKey && Str::startsWith($eventKey, 'qrscene_')) {

                                $scene = substr($eventKey, strlen('qrscene_'));

                                // 扫一扫消息(参数二维码)
                                $event = new ScanEvent();
                                $event->app = &$app;
                                $event->data = &$message;
                                $event->scene = $scene;
                                $event->isSubscribe = true;
                                Event::fire($event);

                                $event->scene = $scene;
                            }

                            Event::fire($subscribeEvent);

                            break;
                    }
                    break;
                case 'text':
                    // 文本消息

                    if ($app->account['appId'] == self::TEST_APP_ID) {
                        if ($message->Content == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
                            $app->setReply(new Text(['content' => 'TESTCOMPONENT_MSG_TYPE_TEXT_callback']));
                        } else if (Str::startsWith($message->Content, 'QUERY_AUTH_CODE:')) {
                            $queryAuthCode = substr($message->Content, strlen('QUERY_AUTH_CODE:'));
                            $ret = WechatAuthorizationServerFacade::getQueryAuth($queryAuthCode);
                            $wechatService->update($app->account['id'], ['authorizerRefreshToken' => $ret['authorization_info']['authorizer_refresh_token']]);
                            ConfigFacade::set('wechatAuthorizationPublishTestOpenId', $message->FromUserName);
                            ConfigFacade::set('wechatAuthorizationPublishTestQueryAuthCode', $queryAuthCode);
                        }
                    }

                    $event = new TextRecvEvent();
                    $event->app = &$app;
                    $event->data = &$message;
                    Event::fire($event);
                    break;
            }
            $reply = $app->getReply();

            if (null === $reply) {
                $reply = 'success';
            }
            return $reply;
        });

        try {
            $app->server->serve()->send();
        } catch (\Exception $e) {
            if ($e instanceof BadRequestException) {
                //直接请求的忽略
            } else {
                throw $e;
            }
        }

    }
}