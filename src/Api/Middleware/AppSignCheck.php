<?php

namespace Edwin404\Api\Middleware;

use Edwin404\Api\Services\ApiAppService;
use Edwin404\Base\Support\Response;
use Edwin404\Base\Support\SignHelper;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Edwin404\Api\Helper\ApiSignHelper;
use Illuminate\Support\Str;

class AppSignCheck
{

    protected $prefix = null;

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

        if (empty($timestamp)) {
            return Response::json(-1, 'timestamp empty');
        }

        if (empty($sign)) {
            return Response::json(-1, 'sign empty');
        }

        $params = Input::all();
        unset($params['sign']);
        if (isset($params['_input'])) {
            unset($params['_input']);
        }

        if (!SignHelper::checkWithoutSecret($sign, $params, $this->prefix)) {
            //echo $signCalc;
            $ret = $this->signNotMatch();
            if ($ret['code']) {
                return $ret['data'];
            }
        }

        return $next($request);
    }

    protected function signNotMatch()
    {
        return Response::generate(-1, 'sign error', Response::json(-1, 'sign error'));
    }

}
