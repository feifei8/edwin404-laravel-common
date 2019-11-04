<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\Exception;
use Edwin404\Base\Support\ModelHelper;

class MemberCreditService
{
    public function paginateLog($memberUserId, $page, $pageSize, $option = [])
    {
        $option['where']['memberUserId'] = $memberUserId;
        return ModelHelper::modelPaginate('member_credit_log', $page, $pageSize, $option);
    }

    public function getTotal($memberUserId)
    {
        $m = ModelHelper::load('member_credit', ['memberUserId' => $memberUserId]);
        if (empty($m)) {
            return 0;
        }
        return $m['total'];
    }

    /**
     * !! 这个方法应该在事务中调用
     *
     * @param $memberUserId
     * @param $change
     * @param $remark
     * @throws \Exception
     */
    public function change($memberUserId, $change, $remark)
    {
        if (!$change) {
            throw new Exception('MemberCreditService -> change empty');
        }
        $m = ModelHelper::loadWithLock('member_credit', ['memberUserId' => $memberUserId]);
        if (empty($m)) {
            $m = ModelHelper::add('member_credit', ['memberUserId' => $memberUserId, 'total' => 0,]);
        }
        if ($change < 0 && $m['total'] + $change < 0) {
            throw new Exception('MemberCreditService -> total change to empty');
        }
        ModelHelper::add('member_credit_log', ['memberUserId' => $memberUserId, 'change' => $change, 'remark' => $remark]);
        $m = ModelHelper::updateOne('member_credit', ['id' => $m['id']], ['total' => $m['total'] + $change]);
        if ($m['total'] < 0) {
            throw new Exception('UserCreditService -> total empty');
        }
    }

}