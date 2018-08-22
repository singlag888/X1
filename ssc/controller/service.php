<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：文章管理
 */
class serviceController extends sscController
{
    //方法概览 测试提交
    public $titles = array(
        'checkStatus' => '验证状态',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function checkStatus()
    {
        $status = $this->request->getPost('status', 'intval');

        echo ROOT_PATH.'test.html';
    }

}
?>