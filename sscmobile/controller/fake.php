<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：文章管理
 */
class fakeController extends sscController
{
    //方法概览 测试提交
    public $titles = array(
        'lobby' => '彩种大厅',
        'platformact' => '优惠活动',
        'result' => '开奖结果',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
        $domain = domains::getItem($_SERVER['HTTP_HOST']);
        $domainType = isset($domain['type']) ? $domain['type'] : 0;
        self::$view->setVar('domainType', $domainType);
    }

    public function lobby()
    {
        self::$view->render('fake_lobby');
    }


    public function platformact()
    {
        $this->getActivities();
        self::$view->render('fake_platformact',true);
    }

    public function result()
    {
        $this->getActivities();
        self::$view->render('fake_result',true);
    }

}
?>