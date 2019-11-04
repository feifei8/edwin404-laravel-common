<?php

namespace Edwin404\User\Middleware;

use Closure;
use Edwin404\Base\Support\Response;
use Edwin404\User\Services\UserService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class UserAuth
{
    protected $userService;

    function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
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

        $userId = Session::get('userId', 0);
        if ($userId) {
            $user = $this->userService->load($userId);
        } else {
            $user = null;
        }

        $request->session()->flash('_user', $user);

        $ret = $this->check($controller, $action, $user);
        if ($ret['code']) {
            return $ret['data'];
        }

        return $next($request);
    }

    // 继承这个方法并实现
    protected function check($controller, $action, $user)
    {
        return Response::generate(0, 'ok');
    }

}