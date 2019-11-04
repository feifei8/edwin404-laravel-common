<?php

namespace Edwin404\Wechat\Events;

use EasyWeChat\Message\Text;
use Edwin404\Wechat\Support\Application;

class TextRecvEvent
{
    /**
     * @var Application
     */
    public $app;
    /**
     * @var Text
     */
    public $data;
}