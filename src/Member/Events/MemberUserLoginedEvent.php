<?php

namespace Edwin404\Member\Events;


class MemberUserLoginedEvent
{
    public $memberUserId;

    public function __construct($memberUserId)
    {
        $this->memberUserId = $memberUserId;
    }


}