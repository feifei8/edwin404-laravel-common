<?php

namespace Edwin404\Wechat\Support;


use Edwin404\Base\Support\Response;
use Edwin404\Config\Facades\ConfigFacade;
use Edwin404\Oauth\Core\Oauth;
use Edwin404\Wechat\Facades\WechatAuthorizationServerFacade;
use Edwin404\Wechat\Helpers\WechatHelper;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

trait AuthorizationOauthTrait
{
    private $dispatchUrl;
    private $dispatchAction;
    private $loginAction;

    private $config = [];

    protected function setConfig($dispatchUrl, $dispatchAction, $loginAction)
    {
        $this->dispatchUrl = $dispatchUrl;
        $this->dispatchAction = $dispatchAction;
        $this->loginAction = $loginAction;
    }

    protected function initConfig($accountId, &$app)
    {
        $callback = action($this->loginAction, ['accountId' => $accountId]);
        $dispatchPath = action($this->dispatchAction, ['accountId' => $accountId, 'callback' => bin2hex($callback)], false);
        $dispatch = $this->dispatchUrl . $dispatchPath;

        $this->config = [
            'APP_KEY' => $app->account['appId'],
            'APP_SECRET' => 'empty',
            'CALLBACK' => $dispatch,
            'AUTHORIZE' => 'scope=snsapi_base,snsapi_userinfo&state=&component_appid=' . WechatAuthorizationServerFacade::getComponentAppId(),
        ];
    }

    public function jump($accountId = 0)
    {
        $app = WechatHelper::app($accountId);
        if (empty($app)) {
            return Response::send(-1, 'app not found');
        }

        $this->initConfig($accountId, $app);
        $sns = Oauth::getInstance('WechatmobileAuthorization', $this->config);
        $url = $sns->getRequestCodeURL();

        $redirect = Input::get('redirect', '/');
        if (!empty($redirect)) {
            Session::put('oauthRedirect', $redirect);
        }

        return redirect($url);
    }

    /**
     * 域名分发
     *
     * @param string $accountId
     * @param string $callback
     * @return mixed
     */
    public function dispatch($accountId = '', $callback = '')
    {
        $app = WechatHelper::app($accountId);
        if (empty($app)) {
            return Response::send(-1, 'app not found');
        }

        $code = Input::get('code', '');
        if (empty($code)) {
            return Response::send(-1, '登录失败(code为空)');
        }

        if (empty($callback)) {
            return Response::send(-1, '登录失败(callback为空)');
        }

        $callback = @hex2bin($callback);
        if (empty($callback)) {
            return Response::send(-1, '登录失败(callback解析失败)');
        }

        if (strpos($callback, '?') === false) {
            $callback .= '?';
        } else {
            $callback .= '&';
        }
        $callback .= 'code=' . urlencode($code);

        return redirect($callback);

    }

    /**
     * 解析回调数据
     * @param string $accountId
     * @return array [code=>0,data=>]
     */
    protected function parseLogin($accountId = '')
    {
        $app = WechatHelper::app($accountId);
        if (empty($app)) {
            return Response::send(-1, 'app not found');
        }

        $code = Input::get('code', '');
        if (empty($code)) {
            return Response::generate(-1, '登录失败(code为空)');
        }

        $token = null;
        $openid = null;
        $oauth = null;
        try {
            $this->initConfig($accountId, $app);
            $oauth = Oauth::getInstance('WechatmobileAuthorization', $this->config);
            $token = $oauth->getAccessToken($code, null, [
                'component_appid' => WechatAuthorizationServerFacade::getComponentAppId(),
                'component_access_token' => WechatAuthorizationServerFacade::getComponentAccessToken(),
            ]);
            $openid = $oauth->openid();
        } catch (\Exception $e) {
            return Response::generate(-1, '登录失败(' . $e->getMessage() . ')');
        }

        if (empty($token) || empty($openid)) {
            return Response::generate(-1, '登录失败(token=' . print_r($token, true) . ',openid=' . $openid . ')');
        }

        return Response::generate(0, null, [
            'openid' => $openid,
            'oauth' => &$oauth
        ]);
    }
}