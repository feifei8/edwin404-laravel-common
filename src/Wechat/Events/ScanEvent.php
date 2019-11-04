<?php

namespace Edwin404\Wechat\Events;

use EasyWeChat\Message\Text;
use Edwin404\Wechat\Support\Application;

class ScanEvent
{
    /**
     * @var Application
     */
    public $app;
    /**
     * @var Text
     */
    public $data;

    public $scene;

    public $isSubscribe;
}