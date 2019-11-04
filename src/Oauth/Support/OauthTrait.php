<?php

namespace Edwin404\Oauth\Support;


use Edwin404\Base\Support\Response;
use Edwin404\Config\Facades\ConfigFacade;
use Edwin404\Oauth\Core\Oauth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

trait OauthTrait
{
    private $dispatchUrl;
    private $dispatchAction;
    private $loginAction;

    private $config = [];
    private $types = [];

    protected function addTypes($type)
    {
        if (is_array($type)) {
            foreach ($type as $t) {
                $this->types[$t] = true;
            }
        } else {
            $this->types[$type] = true;
        }
    }

    protected function setConfig($dispatchUrl, $dispatchAction, $loginAction)
    {
        $this->dispatchUrl = $dispatchUrl;
        $this->dispatchAction = $dispatchAction;
        $this->loginAction = $loginAction;
    }

    protected function initConfig($type)
    {
        $callback = action($this->loginAction, ['type' => $type]);
        $dispatchPath = action($this->dispatchAction, ['type' => $type, 'callback' => bin2hex($callback)], false);
        $dispatch = $this->dispatchUrl . $dispatchPath;

        $this->config = [
            'APP_KEY' => ConfigFacade::get('oauth_' . $type . '_appKey'),
            'APP_SECRET' => ConfigFacade::get('oauth_' . $type . '_appSecret'),
            'CALLBACK' => $dispatch
        ];
    }

    public function jump($type = '')
    {
        if (empty($this->types[$type])) {
            return Response::send(-1, '未知的类型');
        }

        if (!$type || !ConfigFacade::get('oauth_' . $type . '_enable')) {
            return Response::send(-1, '登录未开启');
        }

        $this->initConfig($type);
        $sns = Oauth::getInstance($type, $this->config);
        $url = $sns->getRequestCodeURL();

        $redirect = Input::get('redirect', Request::url());
        if (!empty($redirect)) {
            Session::put('oauthRedirect', $redirect);
        }

        return redirect($url);
    }

    /**
     * 域名分发
     *
     * @param string $type
     * @param string $callback
     * @return mixed
     */
    public function dispatch($type = '', $callback = '')
    {

        if (!$type || !ConfigFacade::get('oauth_' . $type . '_enable')) {
            return Response::send(-1, '登录失败(' . $type . '未开启)');
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
     * @param string $type
     * @return array [code=>0,data=>[oauth=>$oauth,openid=>$openid]]
     */
    protected function parseLogin($type = '')
    {

        if (!$type || !ConfigFacade::get('oauth_' . $type . '_enable')) {
            return Response::generate(-1, '登录失败(' . $type . '未开启)');
        }

        $code = Input::get('code', '');
        if (empty($code)) {
            return Response::generate(-1, '登录失败(code为空)');
        }

        $token = null;
        $openid = null;
        $oauth = null;
        try {
            $this->initConfig($type);
            $oauth = Oauth::getInstance($type, $this->config);
            $token = $oauth->getAccessToken($code, null);
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