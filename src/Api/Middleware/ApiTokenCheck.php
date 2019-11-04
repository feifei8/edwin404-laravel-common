<?php

namespace Edwin404\Api\Middleware;

use Edwin404\Api\Constant\ResponseCodes;
use Edwin404\Api\Services\ApiSessionService;
use Edwin404\Base\Support\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class ApiTokenCheck
{
    function __construct()
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Log::info(print_r(\Illuminate\Support\Facades\Request::header(),true));
        $token = $this->token($request);
        if (!$token || strlen($token) != ApiSessionService::TOKEN_LENGTH) {
            return Response::json(ResponseCodes::API_TOKEN_EMPTY, 'api token empty');
        }
        $request->headers->set('api-token', $token);
        return $next($request);
    }

    // Token获取顺序 (api_token)
    // 1. post 中
    // 2. get 中
    // 3. header 中
    // 4. cookie 中
    protected function token(Request &$request)
    {
        $apiToken = Input::get('api_token', null);
        if (!empty($apiToken)) {
            return $apiToken;
        }
        $apiToken = $request->header('api-token');
        if (!empty($apiToken)) {
            return $apiToken;
        }
        if (!empty($_COOKIE['api_token'])) {
            return $_COOKIE['api_token'];
        }
        return null;
    }

}
