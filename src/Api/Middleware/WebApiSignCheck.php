<?php

namespace Edwin404\Api\Middleware;

use Edwin404\Api\Services\ApiAppService;
use Edwin404\Base\Support\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Edwin404\Api\Helper\ApiSignHelper;
use Illuminate\Support\Str;

class WebApiSignCheck
{
    private $apiAppService;

    public function __construct(ApiAppService $apiAppService)
    {
        $this->apiAppService = $apiAppService;
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
        $appId = Input::get('app_id');

        if (empty($appId)) {
            return Response::send(-1, 'app_id empty');
        }

        if (empty($nonceStr)) {
            return Response::send(-1, 'nonce_str empty');
        }

        if (empty($sign)) {
            return Response::send(-1, 'sign empty');
        }

        $apiApp = $this->apiAppService->loadByAppId($appId);
        if (empty($apiApp)) {
            return Response::send(-1, 'invalid app_id');
        }

        $params = Input::all();
        unset($params['sign']);
        if (isset($params['_input'])) {
            unset($params['_input']);
        }

        $signCalc = ApiSignHelper::calc($params, $apiApp['appSecret']);
        if ($sign != $signCalc) {
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

        $ret = $this->moduleCheck($apiApp, $controller, $action);
        if ($ret['code']) {
            return $ret['data'];
        }

        Session::flash('_api_app', $apiApp);

        return $next($request);
    }

    protected function signNotMatch()
    {
        return Response::generate(-1, 'sign error', Response::send(-1, 'sign error'));
    }

    protected function moduleCheck($apiApp, $controller, $action)
    {
        return Response::generate(0, null);
    }
}
