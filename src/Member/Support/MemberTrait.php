<?php

namespace Edwin404\Member\Support;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;

trait MemberTrait
{
    protected $memberUser = null;

    protected function memberUserSetup()
    {
        View::share('_memberUserId', $this->memberUserId());
        View::share('_memberUser', $this->memberUser());
    }

    protected function memberUser()
    {
        if (null == $this->memberUser) {
            $this->memberUser = Request::session()->get('_memberUser');
        }
        return $this->memberUser;
    }

    protected function memberUserId()
    {
        $this->memberUser();
        return intval($this->memberUser['id']);
    }
}