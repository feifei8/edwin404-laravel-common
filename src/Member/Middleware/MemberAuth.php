<?php

namespace Edwin404\Member\Middleware;

use Closure;
use Edwin404\Base\Support\Response;
use Edwin404\Member\Services\MemberService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class MemberAuth
{
    protected $memberService;

    function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
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

        $memberUserId = Session::get('memberId', 0);
        if (empty($memberUserId)) {
            $memberUserId = Session::get('memberUserId', 0);
        }
        if ($memberUserId) {
            $memberUser = $this->memberService->load($memberUserId);
        } else {
            $memberUser = null;
        }

        $request->session()->flash('_memberUser', $memberUser);

        $ret = $this->check($controller, $action, $memberUser);
        if ($ret['code']) {
            return $ret['data'];
        }

        return $next($request);
    }

    // 继承这个方法并实现
    protected function check($controller, $action, $memberUser)
    {
        return Response::generate(0, 'ok');
    }

}