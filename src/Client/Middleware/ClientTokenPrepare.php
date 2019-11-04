<?php

namespace Edwin404\Client\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class ClientTokenPrepare
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
        $request->headers->set('client-token', $token);
        return $next($request);
    }

    // Token获取顺序 (client_token)
    // 1. get 中
    // 2. post 中
    // 3. cookie 中
    // 4. header 中
    protected function token(Request &$request)
    {
        $clientToken = Input::get('client_token', null);
        if (!empty($clientToken)) {
            return $clientToken;
        }
        $clientToken = Cookie::get('client_token', null);
        if (!empty($clientToken)) {
            return $clientToken;
        }
        if (!empty($_COOKIE['client_token'])) {
            return $_COOKIE['client_token'];
        }
        $clientToken = $request->header('client-token');
        if (!empty($clientToken)) {
            return $clientToken;
        }
        return null;
    }

}
