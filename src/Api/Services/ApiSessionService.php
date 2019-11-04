<?php

namespace Edwin404\Api\Services;


use Carbon\Carbon;
use Edwin404\Base\Support\ModelHelper;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class ApiSessionService
{
    const DATA_MAX_LENGTH = 500;
    const EXPIRE_SECONDS = 2592000;
    const TOKEN_LENGTH = 64;

    private $tokenDataCache = [];

    public function getToken()
    {
        return Request::instance()->header('api-token', null);
    }

    public function getOrGenerateToken()
    {
        $token = Request::instance()->header('api-token', null);
        if (empty($token)) {
            $token = Str::random(self::TOKEN_LENGTH);
            Request::instance()->headers->set('api-token', $token);
        }
        return $token;
    }

    public function all($token = null, $default = [])
    {
        if (empty($token)) {
            $token = Request::instance()->header('api-token');
        }
        if (empty($token)) {
            return $default;
        }
        if (!isset($this->tokenDataCache[$token])) {
            $m = ModelHelper::load('api_token', ['token' => $token]);
            if (empty($m)) {
                $this->tokenDataCache[$token] = [];
                return $default;
            }
            if (strtotime($m['expireTime']) < time()) {
                ModelHelper::delete('api_token', ['token' => $token]);
                $this->tokenDataCache[$token] = [];
                return $default;
            } else {
                $data = @json_decode($m['data'], true);
                if (empty($data)) {
                    $data = $default;
                }
                $this->tokenDataCache[$token] = $data;
                $update = [];
                $update['expireTime'] = Carbon::now()->addSeconds(self::EXPIRE_SECONDS);
                ModelHelper::updateOne('api_token', ['id' => $m['id']], $update);
            }

        }
        return $this->tokenDataCache[$token];
    }

    public function get($name, $defaultValue = null, $token = null)
    {
        $all = $this->all($token);
        if (isset($all[$name])) {
            return $all[$name];
        }
        return $defaultValue;
    }

    public function put($name, $value, $token = null)
    {
        if (empty($token)) {
            $token = $this->getOrGenerateToken();
        }

        $m = ModelHelper::load('api_token', ['token' => $token]);
        if (empty($m)) {
            $m = ModelHelper::add('api_token', ['token' => $token, 'data' => json_encode([])]);
        }

        if (!isset($this->tokenDataCache[$token])) {
            $this->tokenDataCache[$token] = @json_decode($m['data'], true);
        }
        $this->tokenDataCache[$token][$name] = $value;
        if (null === $value) {
            unset($this->tokenDataCache[$token][$name]);
        }
        if (empty($this->tokenDataCache)) {
            ModelHelper::delete('api_token', ['id' => $m['id']]);
            return true;
        }
        $dataJson = json_encode($this->tokenDataCache[$token]);
        if (strlen($dataJson) > 500) {
            throw new \Exception('ApiSessionService.LengthOversize -> ' . $dataJson);
            return false;
        }
        $update = [];
        $update['data'] = $dataJson;
        $update['expireTime'] = Carbon::now()->addSeconds(self::EXPIRE_SECONDS);
        ModelHelper::updateOne('api_token', ['id' => $m['id']], $update);
        return true;
    }

    public function forget($name, $token = null)
    {
        if (empty($token)) {
            $token = Request::instance()->header('api-token');
        }
        if (empty($token)) {
            return true;
        }
        $this->put($name, null, $token);
    }

}