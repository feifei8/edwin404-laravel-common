<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\ModelHelper;

class MemberAddressService
{
    public function loadWithUserId($userId, $id)
    {
        return ModelHelper::load('member_address', ['id' => $id, 'userId' => $userId]);
    }

    public function delete($id)
    {
        ModelHelper::delete('member_address', ['id' => $id]);
    }

    public function update($id, $data)
    {
        return ModelHelper::updateOne('member_address', ['id' => $id], $data);
    }

    public function add($data)
    {
        return ModelHelper::add('member_address', $data);
    }

    public function listAllByUserId($userId)
    {
        return ModelHelper::model('member_address')->where(['userId' => $userId])->orderBy('id', 'desc')->orderBy('isDefault', 'desc')->get()->toArray();
    }

    public function getDefault($userId)
    {
        $address = ModelHelper::load('member_address', ['userId' => $userId, 'isDefault' => 1]);
        if (empty($address)) {
            $address = ModelHelper::load('member_address', ['userId' => $userId]);
        }
        return $address;
    }

    public function clearDefault($userId)
    {
        ModelHelper::update('member_address', ['userId' => $userId], ['isDefault' => 0]);
    }

    public function truncate($memberUserId)
    {
        ModelHelper::delete('member_address', ['userId' => $memberUserId]);
    }

}