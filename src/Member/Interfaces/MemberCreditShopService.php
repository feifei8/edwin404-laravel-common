<?php

namespace Edwin404\Member\Interfaces;


interface MemberCreditShopService
{
    // 获取用户的积分总值
    public function getTotalCredit($memberUserId);

    // 获取用户积分
    public function paginateCredit($memberUserId, $page, $pageSize, $option = []);

    // 锁定用户积分
    public function lockCredit($memberUserId, $credit, $remark);

    // 提交锁定用户积分
    public function commitCredit($lockCreditId);

    // 回滚锁定用户积分
    public function rollbackCredit($lockCreditId);

}