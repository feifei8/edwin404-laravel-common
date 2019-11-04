<?php

namespace Edwin404\Customer\Middleware;


use Edwin404\Base\Support\Response;
use Edwin404\Customer\Services\CustomerService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CustomerAuth
{
    protected $customerService;

    function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
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

        $customerUserId = Session::get('customerUserId', 0);
        if ($customerUserId) {
            $customerUser = $this->customerService->load($customerUserId);
        } else {
            $customerUser = null;
        }

        $request->session()->flash('_customerUser', $customerUser);

        $ret = $this->check($controller, $action, $customerUser);
        if ($ret['code']) {
            return $ret['data'];
        }

        return $next($request);
    }

    // 继承这个方法并实现
    protected function check($controller, $action, $customerUser)
    {
        return Response::generate(0, 'ok');
    }

}