<?php

namespace Edwin404\Member\Helpers;


class MemberHelper
{
    public static function name($memberUser)
    {
        if (!empty($memberUser['nickname'])) {
            return $memberUser['nickname'];
        }
        if (!empty($memberUser['username'])) {
            return $memberUser['username'];
        }
        if (!empty($memberUser['email'])) {
            $pcs = explode('@', $memberUser['email']);
            return $pcs[0];
        }
        if (!empty($memberUser['phone'])) {
            return substr($memberUser['phone'], 0, 3) . '****' . substr($memberUser['phone'], 8);
        }
        return '用户' . $memberUser['id'];
    }

}