<?php

namespace Edwin404\Config\Services;

use Edwin404\Base\Support\ModelHelper;
use Illuminate\Support\Facades\Cache;

class ConfigService
{
    public function getArray($key, $defaultValue = [], $useCache = true)
    {
        $value = $this->get($key, json_encode($defaultValue), $useCache);
        $value = @json_decode($value, true);
        if (!is_array($value) || empty($value)) {
            $value = [];
        }
        return $value;
    }

    public function get($key, $defaultValue = '', $useCache = true)
    {
        $cacheFlag = 'config/' . $key;
        $value = null;
        if ($useCache) {
            $value = Cache::get($cacheFlag);
            if (null !== $value) {
                if (empty($value)) {
                    return $defaultValue;
                }
                return $value;
            }
        }
        if (null === $value) {
            $config = ModelHelper::load('config', ['key' => $key]);
            if ($config) {
                Cache::forever($cacheFlag, $config['value']);
                if (empty($config['value'])) {
                    return $defaultValue;
                }
                return $config['value'];
            } else {
                Cache::forever($cacheFlag, $defaultValue);
            }
        }
        return $defaultValue;
    }

    public function set($key, $value)
    {
        $cacheFlag = 'config/' . $key;
        Cache::forget($cacheFlag);
        $config = ModelHelper::load('config', ['key' => $key]);
        if ($config) {
            ModelHelper::updateOne('config', ['id' => $config['id']], ['value' => $value]);
        } else {
            ModelHelper::add('config', ['key' => $key, 'value' => $value]);
        }
    }

}