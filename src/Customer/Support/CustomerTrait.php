<?php

namespace Edwin404\Customer\Support;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;

trait CustomerTrait
{
    private $customerUser = null;

    private function customerUserSetup()
    {
        View::share('_customerUserId', $this->customerUserId());
        View::share('_customerUser', $this->customerUser());
    }

    protected function customerUser()
    {
        if (null == $this->customerUser) {
            $this->customerUser = Request::session()->get('_customerUser');
        }
        return $this->customerUser;
    }

    protected function customerUserId()
    {
        $this->customerUser();
        return $this->customerUser['id'];
    }
}