<?php

namespace Edwin404\Client\Middleware;

use Edwin404\Base\Support\Response;
use Edwin404\Base\Support\SignHelper;
use Edwin404\Client\Services\ClientAppService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ClientAppIdCheck
{
    private $clientAppService;

    public function __construct(ClientAppService $clientAppService)
    {
        $this->clientAppService = $clientAppService;
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
        $sign = Input::get('sign');
        $nonceStr = Input::get('nonce_str');
        $timestamp = Input::get('timestamp');
        $appId = Input::get('app_id');

        if (empty($appId)) {
            return Response::json(-1, 'app_id empty');
        }

        if (empty($nonceStr)) {
            return Response::json(-1, 'nonce_str empty');
        }

        if (empty($timestamp)) {
            return Response::json(-1, 'timestamp empty');
        }

        if (empty($sign)) {
            return Response::json(-1, 'sign empty');
        }

        $clientApp = $this->clientAppService->loadByAppId($appId);
        if (empty($clientApp)) {
            return Response::json(-1, 'invalid app_id');
        }

        $params = Input::all();
        unset($params['sign']);
        if (isset($params['_input'])) {
            unset($params['_input']);
        }

        $signCalc = SignHelper::commonWithoutSecret($params);
        if (strtolower($sign) != strtolower($signCalc)) {
            $ret = $this->signNotMatch();
            if ($ret['code']) {
                return $ret['data'];
            }
        }

        $routeAction = Route::currentRouteAction();
        $pieces = explode('@', $routeAction);
        if (isset($pieces[0])) {
            $controller = $pieces[0];
        } else {
            $controller = null;
        }
        if (isset($pieces[1])) {
            $action = $pieces[1];
        } else {
            $action = null;
        }
        if (!Str::startsWith($controller, '\\')) {
            $controller = '\\' . $controller;
        }

        $ret = $this->moduleCheck($clientApp, $controller, $action);
        if ($ret['code']) {
            return $ret['data'];
        }

        Session::flash('_client_app', $clientApp);

        return $next($request);
    }

    protected function signNotMatch()
    {
        return Response::generate(-1, 'sign error', Response::json(-1, 'sign error'));
    }

    protected function moduleCheck($clientApp, $controller, $action)
    {
        return Response::generate(0, null);
    }
}
