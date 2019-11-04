<?php

namespace Edwin404\Oauth\Core;

class WechatSDK extends Oauth
{
    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $GetRequestCodeURL = 'https://open.weixin.qq.com/connect/qrconnect';

    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $GetAccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * 获取request_code的额外参数,可在配置中修改 URL查询字符串格式
     * @var srting
     */
    protected $Authorize = 'scope=snsapi_login';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://api.weixin.qq.com/';

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api 微博API
     * @param  string $param 调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'GET', $multi = false)
    {
        /* 腾讯微信调用公共参数 */
        $params = array(
            'access_token' => $this->Token['access_token'],
            'openid' => $this->openid(),
            'lang' => 'zh_CN'
        );

        $data = $this->http($this->url($api), $this->param($params, $param), $method);
        return json_decode($data, true);
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result, $extend)
    {
        $data = @json_decode($result, true);
        if (!empty($data['access_token']) && !empty($data['expires_in'])) {
            $this->Token = $data;
            $data['openid'] = $this->openid();
            return $data;
        } else {
            throw new \Exception("获取微信 ACCESS_TOKEN 出错：{$result}");
        }
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function openid()
    {
        $data = $this->Token;
        if (isset($data['openid'])) {
            return $data['openid'];
        } else {
            throw new \Exception('没有获取到openid！');
        }
    }
}