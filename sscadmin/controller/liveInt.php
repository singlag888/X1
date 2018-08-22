<?php

if (!defined('IN_LIGHT')) {
    die('Hacking attempt');
}

/**
 * 控制器：真人管理
 */
class liveIntController extends sscAdminController
{
    static $wget = NULL;

    public $titles = array(
        'setBBIN'     => '波音接口设置',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //设置波音会话信息 便于接口的使用
    public function setBBIN()
    {
        $locations  = array(0 => array('title'=>'返回接口管理','url'=>url('liveInt','setBBIN')));
        $startDate = $this->request->getPost('startDate', 'trim', date('Y-m-d'));
        $endDate = $this->request->getPost('endDate', 'trim', date('Y-m-d'));
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        
        //新增数据
        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'saveSessInfo':    //登录并保存会话
                    $BBIN = new BBIN();
                    $result = $BBIN->loginC();
                    self::$view->setVar('result', $result);
                    break;
                case 'showCBalance':    //显示代理自身可用额度
                    $BBIN = new BBIN();
                    $result = $BBIN->showCBalance();
                    self::$view->setVar('result', $result);
                    break;
                case 'getAgentGainLoss':    //显示各代理商当天输赢
                    $BBIN = new BBIN();
                    $result = $BBIN->getAgentGainLoss($startDate, $endDate);
                    self::$view->setVar('result', $result);
                    break;

//                case 'getUsers':    //最新会员列表
//                    try {
//                        $BBIN = new BBIN();
//                        $result = $BBIN->getUsers();
//                        self::$view->setVar('result', $result);
//                    } catch (exception2 $e) {
//                        showMsg($e->getMessage());
//                    }
//                    break;
//                case 'checkBalance':    //检查额度
//                    try {
//                        $username = $this->request->getPost('username', 'trim');
//                        if (!$user = users::getItem($username)) {
//                            showMsg("该用户不存在或已被冻结！");
//                        }
//                        $BBIN = new BBIN();
//                        $result = $BBIN->checkBalance($user);
//                        self::$view->setVar('username', $user['username']);
//                        self::$view->setVar('result', $result);
//                    } catch (exception2 $e) {
//                        showMsg($e->getMessage());
//                    }
//                    break;
//                case 'transferIn':    //转入波音帐户
//                    try {
//                        $username = $this->request->getPost('username', 'trim');
//                        $amount = $this->request->getPost('amount', 'floatval');
//                        if (!$user = users::getItem($username)) {
//                            showMsg("该用户不存在或已被冻结！");
//                        }
//                        $BBIN = new BBIN();
//                        $result = $BBIN->transferIn($user, $amount);
//                        self::$view->setVar('username', $user['username']);
//                        self::$view->setVar('amount', $amount);
//                        self::$view->setVar('result', $result);
//                    } catch (exception2 $e) {
//                        showMsg($e->getMessage());
//                    }
//                    break;
//                case 'transferOut':    //转出波音帐户
//                    try {
//                        $username = $this->request->getPost('username', 'trim');
//                        $amount = $this->request->getPost('amount', 'floatval');
//                        if (!$user = users::getItem($username)) {
//                            showMsg("该用户不存在或已被冻结！");
//                        }
//                        $BBIN = new BBIN();
//                        $result = $BBIN->transferOut($user, $amount);
//                        self::$view->setVar('username', $user['username']);
//                        self::$view->setVar('amount', $amount);
//                        self::$view->setVar('result', $result);
//                    } catch (exception2 $e) {
//                        showMsg($e->getMessage());
//                    }
//                    break;
            }
        }

        $config = config::getItem('bbin_sess_info');

        self::$view->setVar('sa', $sa);
        self::$view->setVar('config', $config);
        self::$view->setVar('canTestBBIN', adminGroups::verifyPriv(array(CONTROLLER, 'testBBIN')));
        self::$view->setVar('curLink', '<a href="./?c=' . CONTROLLER . '&a=setBBIN">波音接口管理</a>');
        self::$view->render('liveint_setbbin');
    }

}
?>