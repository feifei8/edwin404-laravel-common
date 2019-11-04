<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\Exception;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Member\Types\MemberMoneyCashStatus;
use Edwin404\Member\Types\MemberMoneyChargeStatus;
use Edwin404\Shop\Helpers\OrderHelper;

class MemberMoneyService
{
    public function paginateLog($memberUserId, $page, $pageSize, $option = [])
    {
        $option['where']['memberUserId'] = $memberUserId;
        return ModelHelper::modelPaginate('member_money_log', $page, $pageSize, $option);
    }

    public function getTotal($memberUserId)
    {
        $m = ModelHelper::load('member_money', ['memberUserId' => $memberUserId]);
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
            throw new Exception('MemberMoneyService -> change empty');
        }
        $m = ModelHelper::loadWithLock('member_money', ['memberUserId' => $memberUserId]);
        if (empty($m)) {
            $m = ModelHelper::add('member_money', ['memberUserId' => $memberUserId, 'total' => 0,]);
        }
        if ($change < 0 && $m['total'] + $change < 0) {
            throw new Exception('MemberMoneyService -> total change to empty');
        }
        ModelHelper::add('member_money_log', ['memberUserId' => $memberUserId, 'change' => $change, 'remark' => $remark]);
        $m = ModelHelper::updateOne('member_money', ['id' => $m['id']], ['total' => $m['total'] + $change]);
        if ($m['total'] < 0) {
            throw new Exception('MemberMoneyService -> total empty');
        }
    }

    /**
     * !! 这个方法应该在事务中调用
     *
     * @throws \Exception
     */
    public function cash($memberUserId, $money, $moneyAfterTax, $type, $realname, $account, $remark = '余额提现')
    {
        $this->change($memberUserId, -$money, '余额提现');
        ModelHelper::add('member_money_cash', [
            'memberUserId' => $memberUserId,
            'status' => MemberMoneyCashStatus::VERIFYING,
            'money' => $money,
            'moneyAfterTax' => $moneyAfterTax,
            'type' => $type,
            'realname' => $realname,
            'account' => $account,
            'remark' => $remark,
        ]);
    }

    public function paginateCash($memberUserId, $page, $pageSize, $option = [])
    {
        $option['where']['memberUserId'] = $memberUserId;
        return ModelHelper::modelPaginate('member_money_cash', $page, $pageSize, $option);
    }

    public function createChange($memberUserId, $fee)
    {
        return ModelHelper::add('member_money_charge', [
            'sn' => OrderHelper::generateSN(),
            'status' => MemberMoneyChargeStatus::CREATED,
            'memberUserId' => $memberUserId,
            'fee' => $fee,
        ]);
    }

    public function processCharge($chargeId)
    {
        $charge = ModelHelper::loadWithLock('member_money_charge', ['id' => $chargeId]);
        if (empty($charge)) {
            throw new \Exception('member_money_charge empty -> ' . $chargeId);
        }
        if ($charge['status'] != MemberMoneyChargeStatus::CREATED) {
            throw new \Exception('member_money_charge status error -> ' . $chargeId);
        }
        $this->change($charge['memberUserId'], $charge['fee'], '充值');
        ModelHelper::updateOne('member_money_charge', ['id' => $chargeId], [
            'status' => MemberMoneyChargeStatus::SUCCESS,
        ]);
    }
}