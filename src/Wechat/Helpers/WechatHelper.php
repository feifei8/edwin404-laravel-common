<?php

namespace Edwin404\Wechat\Helpers;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\PredisCache;
use Edwin404\Base\Support\FileHelper;
use Edwin404\Wechat\Facades\WechatServiceFacade;
use Edwin404\Wechat\Support\Application;
use Illuminate\Support\Facades\Redis;

class WechatHelper
{
    private static $apps = [];

    /**
     * @param $accountId
     * @param $wechatAccount
     * @param $addition
     * @return Application
     */
    public static function app($accountId, $wechatAccount = null, $addition = ['payment' => true,])
    {
        $accountId = intval($accountId);

        if (isset(self::$apps[$accountId])) {
            return self::$apps[$accountId];
        }

        if (null === $wechatAccount) {
            $wechatAccount = WechatServiceFacade::load($accountId);
            if (empty($wechatAccount)) {
                return null;
            }
        }

        if (empty($wechatAccount['enable'])) {
            return null;
        }

        $options = [
            'debug' => true,
            'app_id' => $wechatAccount['appId'],
            'secret' => $wechatAccount['appSecret'],
            'token' => $wechatAccount['appToken'],
            'aes_key' => $wechatAccount['appEncodingKey'],
            'cache' => self::getCacheDriver(),
            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs/wechat_' . $accountId . '.log'),
            ],
        ];

        if (!empty($addition['payment'])) {
            if ($addition['payment'] === true) {
                $payment = WechatServiceFacade::loadPaymentByAccountId($accountId);
            } else {
                $payment = $addition['payment'];
            }
            if ($payment) {

                $certPath = storage_path('wechat/' . FileHelper::number2dir($accountId) . '/payment/cert.pem');
                $keyPath = storage_path('wechat/' . FileHelper::number2dir($accountId) . '/payment/key.pem');
                if (!file_exists($certPath) || !file_exists($keyPath)) {
                    FileHelper::checkSaveFileDir($certPath);
                    file_put_contents($certPath, $payment['dataCert']);
                    FileHelper::checkSaveFileDir($keyPath);
                    file_put_contents($keyPath, $payment['dataKey']);
                }

                $options['payment'] = [
                    'app_id' => $wechatAccount['appId'],
                    'merchant_id' => $payment['merchantId'],
                    'key' => $payment['key'],
                    'device_info' => 'WEB',
                    'cert_path' => file_exists($certPath) ? $certPath : null,
                    'key_path' => file_exists($keyPath) ? $keyPath : null,
                ];

            }
        }

        self::$apps[$accountId] = new Application($options);
        self::$apps[$accountId]->account = $wechatAccount;
        self::$apps[$accountId]->detectAuthType();

        return self::$apps[$accountId];
    }

    public
    static function appByAlias($alias)
    {
        $wechatAccount = WechatServiceFacade::loadByAlias($alias);
        if (empty($wechatAccount)) {
            return null;
        }
        return self::app($wechatAccount['id'], $wechatAccount);
    }

    /**
     * @return Cache
     */
    public
    static function getCacheDriver()
    {
        static $driver = null;
        if (null === $driver) {
            switch (config('wechat.cache.driver')) {
                case 'file':
                    $driver = new FilesystemCache(config('wechat.cache.filePath'));
                    break;
                case 'redis':
                    $redis = Redis::connection(config('wechat.cache.redisConnectionName'));
                    $driver = new PredisCache($redis);
            }
        }
        return $driver;
    }

}