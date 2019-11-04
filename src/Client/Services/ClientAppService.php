<?php

namespace Edwin404\Client\Services;

use Edwin404\Base\Support\ModelHelper;

class ClientAppService
{
    public function loadByAppId($appId)
    {
        return ModelHelper::load('client_app', ['appId' => $appId]);
    }

    public function load($id)
    {
        return ModelHelper::load('client_app', ['id' => $id]);
    }
}