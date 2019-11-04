<?php

namespace Edwin404\Wechat\Controllers;


use EasyWeChat\Encryption\Encryptor;
use Edwin404\Config\Services\ConfigService;
use Edwin404\Wechat\Services\WechatService;
use Edwin404\Wechat\Support\WechatAuthorizationServer;
use Edwin404\Wechat\Types\WechatAuthStatus;
use Edwin404\Wechat\Types\WechatAuthType;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class NotifyController extends Controller
{
    public function index(ConfigService $configService,
                          WechatAuthorizationServer $wechatAuthorizationServer,
                          WechatService $wechatService)
    {

        $wechatAuthorizationAppId = $configService->get('wechatAuthorizationAppId');
        $wechatAuthorizationAppSecret = $configService->get('wechatAuthorizationAppSecret');
        $wechatAuthorizationToken = $configService->get('wechatAuthorizationToken');
        $wechatAuthorizationEncodingKey = $configService->get('wechatAuthorizationEncodingKey');

        $msgSignature = Input::get('msg_signature');
        $nonce = Input::get('nonce');
        $timestamp = Input::get('timestamp');

        $postXML = $wechatAuthorizationServer->getRawContent();

        $encryptor = new Encryptor($wechatAuthorizationAppId, $wechatAuthorizationToken, $wechatAuthorizationEncodingKey);

        $msg = $encryptor->decryptMsg($msgSignature, $nonce, $timestamp, $postXML);

        Log::notice("WECHAT_NOTIFY " . json_encode($msg, true));

        switch ($msg['InfoType']) {
            // 在公众号第三方平台创建审核通过后，微信服务器会向其“授权事件接收URL”每隔10分钟定时推送component_verify_ticket
            // @see https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&id=open1453779503&token=&lang=zh_CN
            case 'component_verify_ticket':
                $configService->set('wechatAuthorizationComponentVerifyTicket', $msg['ComponentVerifyTicket']);
                Log::notice("WECHAT_NOTIFY UPDATE wechatAuthorizationComponentVerifyTicket -> " . $msg['ComponentVerifyTicket']);
                break;
            case 'unauthorized':
                /**
                 * POST数据示例（取消授权通知）
                 * <xml>
                 * <AppId>第三方平台appid</AppId>
                 * <CreateTime>1413192760</CreateTime>
                 * <InfoType>unauthorized</InfoType>
                 * <AuthorizerAppid>公众号appid</AuthorizerAppid>
                 * </xml>
                 */
                $appId = $msg['AuthorizerAppid'];
                $account = $wechatService->loadAccountByAppIdAndAuthType($appId, WechatAuthType::OAUTH);
                if (empty($account)) {
                    return;
                }
                $wechatService->update($account['id'], ['authStatus' => WechatAuthStatus::CANCELED]);
                break;
            case 'authorized':
                /**
                 * POST数据示例（授权成功通知）
                 * <xml>
                 * <AppId>第三方平台appid</AppId>
                 * <CreateTime>1413192760</CreateTime>
                 * <InfoType>authorized</InfoType>
                 * <AuthorizerAppid>公众号appid</AuthorizerAppid>
                 * <AuthorizationCode>授权码（code）</AuthorizationCode>
                 * <AuthorizationCodeExpiredTime>过期时间</AuthorizationCodeExpiredTime>
                 * </xml>
                 */
                break;
            case 'updateauthorized':
                /**
                 * POST数据示例（授权更新通知）
                 * <xml>
                 * <AppId>第三方平台appid</AppId>
                 * <CreateTime>1413192760</CreateTime>
                 * <InfoType>updateauthorized</InfoType>
                 * <AuthorizerAppid>公众号appid</AuthorizerAppid>
                 * <AuthorizationCode>授权码（code）</AuthorizationCode>
                 * <AuthorizationCodeExpiredTime>过期时间</AuthorizationCodeExpiredTime>
                 * </xml>
                 */
                break;

        }
    }
}