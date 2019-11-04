<?php

namespace Edwin404\Api\Middleware;

use Edwin404\Api\Services\ApiAppService;
use Edwin404\Base\Support\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Edwin404\Api\Helper\ApiSignHelper;
use Illuminate\Support\Str;

class ApiAppIdCheck
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
        $appId = Input::get('app_id');

        if (empty($appId)) {
            return Response::json(-1, 'app_id empty');
        }

        $apiApp = $this->apiAppService->loadByAppId($appId);
        if (empty($apiApp)) {
            return Response::json(-1, 'invalid app_id');
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

    protected function moduleCheck($apiApp, $controller, $action)
    {
        return Response::generate(0, null);
    }
}
