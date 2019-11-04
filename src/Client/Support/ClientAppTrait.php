<?php

namespace Edwin404\Client\Support;


use Illuminate\Support\Facades\Session;

trait ClientAppTrait
{
    protected $clientApp = null;

    protected function clientApp()
    {
        if (null == $this->clientApp) {
            $this->clientApp = Session::get('_client_app');
        }
        return $this->clientApp;
    }
}