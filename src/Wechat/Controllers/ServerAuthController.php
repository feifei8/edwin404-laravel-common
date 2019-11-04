<?php

namespace Edwin404\Wechat\Controllers;

use Edwin404\Base\Support\Response;
use Edwin404\Wechat\Facades\WechatAuthorizationServerFacade;
use Edwin404\Wechat\Services\WechatService;
use Edwin404\Wechat\Types\WechatAuthStatus;
use Edwin404\Wechat\Types\WechatAuthType;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class ServerAuthController extends Controller
{
    private $dispatchUrl;
    private $dispatchAction;
    private $bindAction;

    protected function setConfig($dispatchUrl, $dispatchAction, $bindAction)
    {
        $this->dispatchUrl = $dispatchUrl;
        $this->dispatchAction = $dispatchAction;
        $this->bindAction = $bindAction;
    }

    public function jump()
    {
        $callback = action($this->bindAction);
        $dispatchPath = action($this->dispatchAction, ['callback' => bin2hex($callback)], false);
        $dispatch = $this->dispatchUrl . $dispatchPath;

        $authUrl = WechatAuthorizationServerFacade::getAuthUrl($dispatch);

        return Response::send(0, '正在跳转...', null, $authUrl);
    }

    public function dispatch($callback = '')
    {
        $authCode = Input::get('auth_code');
        if (empty($authCode)) {
            return Response::send(-1, 'auth code empty');
        }

        if (empty($callback)) {
            return Response::send(-1, 'callback empty');
        }

        $callback = @hex2bin($callback);
        if (empty($callback)) {
            return Response::send(-1, 'callback parse error');
        }

        if (strpos($callback, '?') === false) {
            $callback .= '?';
        } else {
            $callback .= '&';
        }
        $callback .= 'auth_code=' . urlencode($authCode);

        return redirect($callback);
    }

    public function bind(WechatService $wechatService)
    {
        $authCode = Input::get('auth_code');
        if (empty($authCode)) {
            return Response::send(-1, 'auth code empty');
        }

        $queryInfo = WechatAuthorizationServerFacade::getQueryAuth($authCode);

        $wechatAccountAppId = $queryInfo['authorization_info']['authorizer_appid'];
        $wechatAccount = $wechatService->loadAccountByAppIdAndAuthType($wechatAccountAppId, WechatAuthType::OAUTH);

        if (empty($wechatAccount)) {
            // 创建
            $data = [];
            $data['authType'] = WechatAuthType::OAUTH;
            $data['authStatus'] = WechatAuthStatus::NORMAL;
            $data['enable'] = true;
            $data['appId'] = $wechatAccountAppId;
            $data['alias'] = $wechatService->generateAccountAlias();
            $data['authorizerRefreshToken'] = $queryInfo['authorization_info']['authorizer_refresh_token'];
            $wechatAccount = $wechatService->add($data);
            $accountId = $wechatAccount['id'];
        } else {
            // 更新
            $data = [];
            $data['authStatus'] = WechatAuthStatus::NORMAL;
            $data['authorizerRefreshToken'] = $queryInfo['authorization_info']['authorizer_refresh_token'];
            $wechatAccount = $wechatService->update($wechatAccount['id'], $data);
            $accountId = $wechatAccount['id'];
        }

        $authorizerInfo = WechatAuthorizationServerFacade::getAuthorizerInfo($wechatAccountAppId);
        $data = [];
        $data['name'] = $authorizerInfo['authorizer_info']['nick_name'];
        if (!empty($authorizerInfo['authorizer_info']['head_img'])) {
            $data['avatar'] = $authorizerInfo['authorizer_info']['head_img'];
        }
        $data['serviceInfo'] = $authorizerInfo['authorizer_info']['service_type_info']['id'];
        $data['verifyInfo'] = $authorizerInfo['authorizer_info']['verify_type_info']['id'];
        $data['username'] = $authorizerInfo['authorizer_info']['user_name'];
        $data['wechat'] = $authorizerInfo['authorizer_info']['alias'];

        $data['func'] = [];
        foreach ($authorizerInfo['authorization_info']['func_info'] as $funcInfo) {
            $data['func'][] = $funcInfo['funcscope_category']['id'];
        }

        $wechatService->update($accountId, $data);

        Session::put('authWechatAccountId', $accountId);

        $wechatAccount = $wechatService->load($accountId);
        $ret = $this->checkAndBind($wechatAccount);

        if ($ret['code']) {
            return Response::send(-1, $ret['msg']);
        }

        return Response::send(0, '绑定成功(请关闭当前页面)');
    }

    protected function checkAndBind($wechatAccount)
    {
        return Response::generate(0, null);
    }

}