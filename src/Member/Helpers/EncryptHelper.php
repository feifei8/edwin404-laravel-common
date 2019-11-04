<?php

namespace Edwin404\Member\Helpers;


class EncryptHelper
{
    public static function md5Encode($password, $passwordSalt)
    {
        return md5(md5($password) . md5($passwordSalt));
    }
}