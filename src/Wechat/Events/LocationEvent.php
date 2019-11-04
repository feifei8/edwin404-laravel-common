<?php

namespace Edwin404\Wechat\Events;

use Edwin404\Wechat\Support\Application;

class LocationEvent
{
    /**
     * @var Application
     */
    public $app;

    public $data;
    
    public $latitude;
    public $longitude;
    public $precision;
}