<?php

namespace Edwin404\Wechat\Support;


use EasyWeChat\Core\Exceptions\HttpException;
use EasyWeChat\Core\Http;
use EasyWeChat\Support\Log;
use Edwin404\Config\Services\ConfigService;
use Edwin404\Wechat\Helpers\WechatHelper;
use Edwin404\Wechat\Types\AuthorizerOption;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

class WechatAuthorizationServer
{
    private $request;
    private $configService;
    private $cache;

    private $rawContent;

    private $componentAppId;
    private $componentAppSecret;
    private $componentToken;
    private $componentEncodingKey;
    private $componentVerifyTicket;

    public function __construct(Request $request, ConfigService $configService)
    {
        $this->request = $request;
        $this->configService = $configService;
        $this->cache = WechatHelper::getCacheDriver();

        $this->initializeLogger();
        $this->init();
    }

    /**
     * Initialize logger.
     */
    private function initializeLogger()
    {
        if (Log::hasLogger()) {
            return;
        }

        $logger = new Logger('easywechat');
        $logger->pushHandler(new NullHandler());
        Log::setLogger($logger);
    }

    public function init()
    {
        $useCache = env('WECHAT_AUTHORIZATION_SERVER_USE_CACHE', true);

        $this->componentAppId = $this->configService->get('wechatAuthorizationAppId', null, $useCache);
        $this->componentAppSecret = $this->configService->get('wechatAuthorizationAppSecret', null, $useCache);
        $this->componentToken = $this->configService->get('wechatAuthorizationToken', null, $useCache);
        $this->componentEncodingKey = $this->configService->get('wechatAuthorizationEncodingKey', null, $useCache);
        $this->componentVerifyTicket = $this->configService->get('wechatAuthorizationComponentVerifyTicket', null, $useCache);

        $this->rawContent = $this->request->getContent(false);
    }

    public function getRawContent()
    {
        return $this->rawContent;
    }

    public function getComponentAccessToken($forceRefresh = false)
    {
        $cacheKey = 'wx.component_access_token';

        $cached = $this->cache->fetch($cacheKey);

        if ($forceRefresh || empty($cached)) {

            $params = [
                'component_appid' => $this->componentAppId,
                'component_appsecret' => $this->componentAppSecret,
                'component_verify_ticket' => $this->componentVerifyTicket,
            ];

            $http = new Http();
            $token = $http->parseJSON($http->json('https://api.weixin.qq.com/cgi-bin/component/api_component_token', $params));

            if (empty($token['component_access_token'])) {
                throw new HttpException('Request AccessToken fail. response 1 : ' . json_encode($token, JSON_UNESCAPED_UNICODE));
            }

            $this->cache->save($cacheKey, $token['component_access_token'], $token['expires_in'] - 60 * 10);

            $cached = $token['component_access_token'];
        }

        return $cached;
    }

    public function getPreAuthCode()
    {
        $params = [
            'component_appid' => $this->componentAppId,
        ];

        $http = new Http();

        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode';
        $queries = ['component_access_token' => $this->getComponentAccessToken()];
        $token = $http->parseJSON($http->json($url, $params, JSON_UNESCAPED_UNICODE, $queries));

        if (empty($token['pre_auth_code'])) {
            throw new HttpException('Request AccessToken fail. response 2 : ' . json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        return $token['pre_auth_code'];
    }

    public function getAuthUrl($redirectUri)
    {
        $preAuthCode = $this->getPreAuthCode();
        $url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=$this->componentAppId&pre_auth_code=$preAuthCode&redirect_uri=" . urlencode($redirectUri);
        return $url;
    }


    public function getQueryAuth($authorizationCode)
    {
        /**
         * {
         *   "authorization_info": {
         *   "authorizer_appid": "wxf8b4f85f3a794e77",
         *   "authorizer_access_token": "QXjUqNqfYVH0yBE1iI_7vuN_9gQbpjfK7hYwJ3P7xOa88a89-Aga5x1NMYJyB8G2yKt1KCl0nPC3W9GJzw0Zzq_dBxc8pxIGUNi_bFes0qM",
         *   "expires_in": 7200,
         *   "authorizer_refresh_token": "dTo-YCXPL4llX-u1W1pPpnp8Hgm4wpJtlR6iV0doKdY",
         *   "func_info": [
         *     {"funcscope_category": {"id": 1}},
         *     {"funcscope_category": {"id": 2}},
         *     {"funcscope_category": {"id": 3}}
         *   ]
         * }
         */

        $params = [
            'component_appid' => $this->componentAppId,
            'authorization_code' => $authorizationCode,
        ];

        $http = new Http();
        //$info = $http->parseJSON($http->json('https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' . urlencode($this->getComponentAccessToken()), $params));

        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth';
        $queries = ['component_access_token' => $this->getComponentAccessToken()];
        $info = $http->parseJSON($http->json($url, $params, JSON_UNESCAPED_UNICODE, $queries));

        if (empty($info['authorization_info'])) {
            throw new HttpException('Request AccessToken fail. response 3 : ' . json_encode($info, JSON_UNESCAPED_UNICODE));
        }

        return $info;
    }

    public function getAuthorizerInfo($authorizerAppId)
    {
        /**
         * {
         *   "authorizer_info": {
         *   "nick_name": "微信SDK Demo Special",
         *   "head_img": "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
         *   "service_type_info": { "id": 2 },
         *   "verify_type_info": { "id": 0 },
         *   "user_name":"gh_eb5e3a772040",
         *   "business_info": {"open_store": 0, "open_scan": 0, "open_pay": 0, "open_card": 0, "open_shake": 0},
         *   "alias":"paytest01"
         *   },
         *   "qrcode_url":"URL",
         *   "authorization_info": {
         *     "appid": "wxf8b4f85f3a794e77",
         *     "func_info": [
         *       { "funcscope_category": { "id": 1 } },
         *       { "funcscope_category": { "id": 2 } },
         *       { "funcscope_category": { "id": 3 } }
         *     ]
         *   }
         * }
         */

        $params = [
            'component_appid' => $this->componentAppId,
            'authorizer_appid' => $authorizerAppId,
        ];

        $http = new Http();
        //$info = $http->parseJSON($http->json('https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=' . urlencode($this->getComponentAccessToken()), $params));
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info';
        $queries = ['component_access_token' => $this->getComponentAccessToken()];
        $info = $http->parseJSON($http->json($url, $params, JSON_UNESCAPED_UNICODE, $queries));

        if (empty($info['authorizer_info'])) {
            throw new HttpException('Request AccessToken fail. response 4 : ' . json_encode($info, JSON_UNESCAPED_UNICODE));
        }

        return $info;
    }

    /**
     * 获取授权方的选项设置信息
     */
    /** @see AuthorizerOption */
    public function getAuthorizerOption($authorizerAppId, $option)
    {
        /**
         * 发送
         * {
         * "component_appid":"appid_value",
         * "authorizer_appid": " auth_appid_value ",
         * "option_name": "option_name_value"
         * }
         */

        /**
         * 接收
         * {
         * "authorizer_appid":"wx7bc5ba58cabd00f4",
         * "option_name":"voice_recognize",
         * "option_value":"1"
         * }
         */

        $params = [
            'component_appid' => $this->componentAppId,
            'authorizer_appid' => $authorizerAppId,
            'option_name' => $option,
        ];

        $http = new Http();
        //$info = $http->parseJSON($http->json('https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option?component_access_token=' . urlencode($this->getComponentAccessToken()), $params));
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option';
        $queries = ['component_access_token' => $this->getComponentAccessToken()];
        $info = $http->parseJSON($http->json($url, $params, JSON_UNESCAPED_UNICODE, $queries));

        if (!isset($info['authorizer_appid'])) {
            throw new HttpException('Request AccessToken fail. response 5 : ' . json_encode($info, JSON_UNESCAPED_UNICODE));
        }

        return $info;

    }

    /**
     * 设置授权方的选项信息
     *
     * @param $option
     * @param $value
     */
    /** @see AuthorizerOption */
    public function setAuthorizerOption($authorizerAppId, $option, $value)
    {
        /**
         * 发送
         *
         * {
         * "component_appid":"appid_value",
         * "authorizer_appid": " auth_appid_value ",
         * "option_name": "option_name_value",
         * "option_value":"option_value_value"
         * }
         */

        /**
         * 返回
         *
         * {
         * "errcode":0,
         * "errmsg":"ok"
         * }
         */

        $params = [
            'component_appid' => $this->componentAppId,
            'authorizer_appid' => $authorizerAppId,
            'option_name' => $option,
            'option_value' => $value,
        ];

        $http = new Http();
        //$info = $http->parseJSON($http->json('https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option?component_access_token=' . urlencode($this->getComponentAccessToken()), $params));
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option';
        $queries = ['component_access_token' => $this->getComponentAccessToken()];
        $info = $http->parseJSON($http->json($url, $params, JSON_UNESCAPED_UNICODE, $queries));

        if (isset($info['errcode']) && 0 == $info['errcode']) {
            return;
        }

        throw new HttpException('Request AccessToken fail. response 6 : ' . json_encode($info, JSON_UNESCAPED_UNICODE));

    }


    public function getComponentAppId()
    {
        return $this->componentAppId;
    }

    public function getComponentToken()
    {
        return $this->componentToken;
    }

    public function getComponentEncodingKey()
    {
        return $this->componentEncodingKey;
    }

}