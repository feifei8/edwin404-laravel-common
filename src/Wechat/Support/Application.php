<?php

namespace Edwin404\Wechat\Support;

use EasyWeChat\Message\Image;
use EasyWeChat\Message\News;
use EasyWeChat\Message\Text;
use Edwin404\Base\Support\CurlHelper;
use Edwin404\Base\Support\Response;
use Edwin404\SmartAssets\Helper\AssetsHelper;
use Edwin404\Wechat\Facades\WechatAuthorizationServerFacade;
use Edwin404\Wechat\Message\MessageBase;
use Edwin404\Wechat\Message\MessageType;
use Edwin404\Wechat\Types\WechatAuthType;
use EdwinFound\Utils\FileUtil;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Application extends \EasyWeChat\Foundation\Application
{
    /**
     * @var array WechatAccount
     */
    public $account;
    private $reply = null;

    public function __construct($config)
    {
        parent::__construct($config);
    }

    // 使用第三方授权,需要access_token的获取方式不一样
    public function detectAuthType()
    {
        if (WechatAuthType::OAUTH == $this->account['authType']) {

            $this['config']->set('app_id', WechatAuthorizationServerFacade::getComponentAppId());
            $this['config']->set('token', WechatAuthorizationServerFacade::getComponentToken());
            $this['config']->set('aes_key', WechatAuthorizationServerFacade::getComponentEncodingKey());

            $this->offsetUnset('access_token');
            $this['access_token'] = function () {
                return new AuthorizationAccessToken(
                    $this->account,
                    $this['cache']
                );
            };


        }
    }

    // 被动回应消息(同一个消息同时得到多个插件的响应,只有第一个响应起作用)

    public function setReply($reply)
    {
        if (null !== $this->reply) {
            return false;
        }
        $this->reply = $reply;
        return true;
    }

    public function getReply()
    {
        return $this->reply;
    }

    public function clearReply()
    {
        $this->reply = null;
    }


    public function setReplyMessage($message)
    {
        if (null !== $this->reply) {
            return null;
        }
        switch ($message->type) {
            case MessageType::TEXT:
                $this->reply = new Text(['content' => $message->content]);
                break;
            case MessageType::NEWS:
                $news = new News();
                $news->title = $message->title;
                $news->image = AssetsHelper::fixFull($message->cover);
                $news->description = $message->summary;
                $news->url = $message->link;
                $this->reply = $news;
                break;
            case MessageType::IMAGE:
                $imageUrl = $message->url;
                if (empty($imageUrl)) {
                    return null;
                }
                $imageCacheKey = 'wechat.mediaId.' . md5($imageUrl);
                $mediaId = Cache::get($imageCacheKey, null);
                if (null === $mediaId) {
                    if (Str::startsWith($imageUrl, 'http://') || Str::startsWith($imageUrl, 'https://') || Str::startsWith($imageUrl, '//')) {
                        if (Str::startsWith($imageUrl, '//')) {
                            $imageUrl = 'http://' . $imageUrl;
                        }
                        $image = CurlHelper::getContent($imageUrl);
                        if (empty($image)) {
                            return null;
                        }
                        @mkdir(public_path('temp'));
                        $filename = public_path('temp/' . Str::random(32) . '.' . FileUtil::extension($imageUrl));
                        file_put_contents($filename, $image);
                    } else {
                        if (Str::startsWith($imageUrl, '/')) {
                            $imageUrl = substr($imageUrl, 1);
                        }
                        $filename = public_path($imageUrl);
                    }
                    $uploadRet = $this->material_temporary->uploadImage($filename);
                    $mediaId = $uploadRet['media_id'];
                    Cache::put($imageCacheKey, $mediaId, 60 * 24 * 2);
                }
                $this->reply = new Image(['media_id' => $mediaId]);
                break;
            case MessageType::CUSTOM:
                $this->reply = new Text(['content' => 'Wechat.MessageCustom']);
                break;
        }
        return $this->reply;
    }

    public function sendStaffMessage($openId, $message)
    {
        try {
            $sendResult = $this->staff->message($message)->to($openId)->send();
            if (isset($sendResult['errcode']) && $sendResult['errcode'] == 0) {
                return Response::generate(0, null, $sendResult);
            }
            return Response::generate(-1, null, $sendResult);
        } catch (\Exception $e) {
            return Response::generate(-2, '发送消息异常', $e->getMessage());
        }
    }

    public function sendNotice($notice)
    {
        try {
            $sendResult = $this->notice->send($notice)->toArray();
            if (isset($sendResult['errcode']) && $sendResult['errcode'] == 0) {
                return Response::generate(0, null, $sendResult);
            }
            return Response::generate(-1, null, $sendResult);
        } catch (\Exception $e) {
            return Response::generate(-2, '发送通知异常', $e->getMessage());
        }
    }

    public function publishMenu($menu)
    {
        try {
            $sendResult = $this->menu->add($menu)->toArray();
            if (isset($sendResult['errcode']) && $sendResult['errcode'] == 0 && $sendResult['errmsg'] && 'ok' == $sendResult['errmsg']) {
                return Response::generate(0, null);
            }
            return Response::generate(-1, null, $sendResult);
        } catch (\Exception $e) {
            return Response::generate(-2, '发送通知异常', $e->getMessage());
        }
    }

    public function uploadMaterial($localFilename, $cacheMinutes = 2800)
    {
        $cacheKey = 'wechat.mediaId.' . md5($localFilename);
        $mediaId = Cache::get($cacheKey, null);
        if (null === $mediaId || $cacheMinutes === null) {
            $uploadRet = $this->material_temporary->uploadImage($localFilename);
            $mediaId = $uploadRet['media_id'];
            if (null === $cacheMinutes) {
            } else {
                Cache::put($cacheKey, $mediaId, $cacheMinutes);
            }
        }
        return $mediaId;
    }

}