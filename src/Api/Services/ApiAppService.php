<?php

namespace Edwin404\Api\Services;

use Edwin404\Base\Support\ModelHelper;

class ApiAppService
{
    public function loadByAppId($appId)
    {
        return ModelHelper::load('api_app', ['appId' => $appId]);
    }

    public function load($id)
    {
        return ModelHelper::load('api_app', ['id' => $id]);
    }
}