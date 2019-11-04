<?php

namespace Edwin404\Api\Middleware;

use Edwin404\Api\Services\ApiAppService;
use Edwin404\Base\Support\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Edwin404\Api\Helper\ApiSignHelper;
use Illuminate\Support\Str;

class ApiSignTimestampCheck
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
        $timestamp = Input::get('timestamp');
        $appId = Input::get('app_id');

        if (empty($appId)) {
            return Response::json(-1, 'app_id empty');
        }

        if (empty($timestamp)) {
            return Response::json(-1, 'timestamp empty');
        }

        if ($timestamp < time() - 1800 || $timestamp > time() + 1800) {
            return Response::json(-1, 'timestamp not valid');
        }

        if (empty($sign)) {
            return Response::json(-1, 'sign empty');
        }

        $apiApp = $this->apiAppService->loadByAppId($appId);
        if (empty($apiApp)) {
            return Response::json(-1, 'invalid app_id');
        }

        $params = Input::all();
        unset($params['sign']);
        if (isset($params['_input'])) {
            unset($params['_input']);
        }

        $signCalc = ApiSignHelper::calc($params, $apiApp['appSecret']);
        if ($sign != $signCalc) {
            Log::info('sign not match : ' . $signCalc);
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
        return Response::generate(-1, 'sign error', Response::json(-1, 'sign error'));
    }

    protected function moduleCheck($apiApp, $controller, $action)
    {
        return Response::generate(0, null);
    }
}
