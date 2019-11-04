<?php

namespace Edwin404\User\Services;


use Edwin404\Base\Support\ModelHelper;

class UserService
{
    public function load($id)
    {
        return ModelHelper::load('user', ['id' => $id]);
    }

    public function update($id, $data)
    {
        return ModelHelper::updateOne('user', ['id' => $id], $data);
    }

    public function add($data)
    {
        return ModelHelper::add('user', $data);
    }

}