<?php

namespace Edwin404\Wechat\Events;

use Edwin404\Wechat\Support\Application;

class MenuClickEvent
{
    /**
     * @var Application
     */
    public $app;
    /**
     * @var message
     */
    public $data;
    /**
     * @var string
     */
    public $key;
}