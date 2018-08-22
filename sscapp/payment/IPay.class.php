<?php

namespace sscapp\payment;

interface IPay
{

    /**
     * 去支付
     */
    public function run();

    /**
     * 外部回调操作.
     */
    public function callback();

    /**
     * @return mixed
     * 生成订单号
     */
    public function orderNumber();

}