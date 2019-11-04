<?php

namespace Edwin404\Shop\Helpers;


class OrderHelper
{
    /**
     * 生成一个22位长度的订单号
     * @return string
     * @example
     * 20170101+121210+00000000
     */
    public static function generateSN()
    {
        return date('YmdHis', time()) . rand(10000000, 99999999);
    }
}