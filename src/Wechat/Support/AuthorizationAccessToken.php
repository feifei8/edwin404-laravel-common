<?php

namespace Edwin404\Wechat\Support;

use Doctrine\Common\Cache\Cache;
use EasyWeChat\Core\AccessToken;
use EasyWeChat\Core\Exceptions\HttpException;
use Edwin404\Wechat\Facades\WechatAuthorizationServerFacade;
use Edwin404\Wechat\Facades\WechatServiceFacade;
use Illuminate\Support\Facades\Log;

/**
 * Class AuthorizationAccessToken.
 */
class AuthorizationAccessToken extends AccessToken
{

    protected $account;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected $prefix = 'easywechat.authorization.access_token.';

    /**
     * Constructor.
     *
     * @param array $account
     * @param \Doctrine\Common\Cache\Cache $cache
     */
    public function __construct(&$account, Cache $cache = null)
    {
        $this->account = &$account;
        $this->cache = $cache;
    }

    /**
     * Get token from WeChat API.
     *
     * @param bool $forceRefresh
     *
     * @return string
     */
    public function getToken($forceRefresh = false)
    {
        $cacheKey = $this->prefix . '-' . $this->account['appId'] . '-' . $this->account['authType'];

        $cached = $this->getCache()->fetch($cacheKey);

        if ($forceRefresh || empty($cached)) {
            $token = $this->getTokenFromServer();

            // XXX: T_T... 7200 - 1500
            $this->getCache()->save($cacheKey, $token['authorizer_access_token'], $token['expires_in'] - 1500);

            // 更新 authorizer_refresh_token
            WechatServiceFacade::update($this->account['id'], ['authorizerRefreshToken' => $token['authorizer_refresh_token']]);

            return $token['authorizer_access_token'];
        }
        return $cached;
    }

    /**
     * Return the app id.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->account['appId'];
    }

    /**
     * Return the secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return null;
    }

    /**
     * Get the access token from WeChat server.
     *
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     *
     * @return array|bool
     */
    public function getTokenFromServer()
    {
        $params = [
            'component_appid' => WechatAuthorizationServerFacade::getComponentAppId(),
            'authorizer_appid' => $this->account['appId'],
            'authorizer_refresh_token' => $this->account['authorizerRefreshToken'],
        ];

        $http = $this->getHttp();
        //$url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=" . urlencode($accessToken);
        //$token = $http->parseJSON($http->json($url, $params));

        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token';
        $queries = ['component_access_token' => WechatAuthorizationServerFacade::getComponentAccessToken()];
        $token = $http->parseJSON($http->json($url, $params, JSON_UNESCAPED_UNICODE, $queries));

        if (empty($token['authorizer_access_token'])) {
            throw new HttpException('Request AccessToken fail. response: ' . json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        return $token;
    }

}
