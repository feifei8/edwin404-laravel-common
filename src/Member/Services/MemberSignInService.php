<?php

namespace Edwin404\Member\Services;


use Carbon\Carbon;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;

class MemberSignInService
{

    public function paginateSignInItem($memberUserId, $page, $pageSize, $option = [])
    {
        $option['where']['memberUserId'] = $memberUserId;
        return ModelHelper::modelPaginate('member_sign_in_item', $page, $pageSize, $option);
    }

    public function loadByMemberUserId($memberUserId)
    {
        $m = ModelHelper::load('member_sign_in', ['memberUserId' => $memberUserId]);
        if (empty($m)) {
            ModelHelper::add('member_sign_in', [
                'memberUserId' => $memberUserId,
                'continualDayCount' => 0,
                'dayCount' => 0,
            ]);
            $m = ModelHelper::load('member_sign_in', ['memberUserId' => $memberUserId]);
        }
        return $m;
    }

    public function signIn($memberUserId)
    {
        $today = date('Y-m-d', time());
        $yesterday = date('Y-m-d', time() - 24 * 3600);
        $m = $this->loadByMemberUserId($memberUserId);
        if ($m['day'] == $today) {
            return Response::generate(-1, '今天已经签过到了');
        }
        ModelHelper::transactionBegin();
        $m = ModelHelper::loadWithLock('member_sign_in', ['memberUserId' => $memberUserId]);
        if ($m['day'] == $today) {
            ModelHelper::transactionCommit();
            return Response::generate(-1, '今天已经签过到了');
        }
        $update = [];
        $update['day'] = $today;
        $update['dayCount'] = $m['dayCount'] + 1;
        if ($m['day'] == $yesterday) {
            $update['continualDayCount'] = $m['continualDayCount'] + 1;
        } else {
            $update['continualDayCount'] = 1;
        }
        ModelHelper::updateOne('member_sign_in', ['id' => $m['id']], $update);
        ModelHelper::add('member_sign_in_item', ['memberUserId' => $memberUserId]);
        ModelHelper::transactionCommit();

        return Response::generate(0, null);
    }

}