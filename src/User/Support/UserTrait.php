<?php

namespace Edwin404\User\Support;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;

trait UserTrait
{
    private $user = null;

    private function userSetup()
    {
        View::share('_userId', $this->userId());
        View::share('_user', $this->user());
    }

    protected function user()
    {
        if (null == $this->user) {
            $this->user = Request::session()->get('_user');
        }
        return $this->user;
    }

    protected function userId()
    {
        $this->user();
        if (empty($this->user['id'])) {
            return 0;
        }
        return $this->user['id'];
    }
}