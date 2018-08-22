<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：封锁管理
 */
class lockController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'lockList' => '封锁列表',
        'lockAdd' => '增加配置',
        'lockDelete' => '删除配置'
    );
 
    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function lockList()
    {
        $lotterys = lottery::getItemsById(locks::$lotterys);
        if (!$lottery_id = $this->request->getGet('lottery_id', 'intval', 0)) {
            if (!$lottery_id = $this->request->getPost('lottery_id', 'intval', 0)) {
                $tmp = reset($lotterys);
                $lottery_id = $tmp['lottery_id'];
            }
        }
        $lottery = $lotterys[$lottery_id];  //当前显示的彩种
        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('lottery', $lottery);
        if ($this->request->getPost('submit', 'trim')) {
            $lock_ids = $this->request->getPost('lock_ids', 'array');
            foreach ($lock_ids as $lock_id => $lock_limit) {
                if ($lock_limit) {
                    locks::updateItem($lock_id, array('lock_limit' => $lock_limit));
                }
            }
            $method_ids = $this->request->getPost('method_ids', 'array');
            if($method_ids){
                foreach ($method_ids as $method_id => $lock_limit) {
                    if ($lock_limit) {
                        locks::addItem(array(
                            'lottery_id' => $lottery_id,
                            'method_id' => $method_id,
                            'lock_limit' => $lock_limit,
                            'create_time' => date('Y-m-d H:i:s')
                                )
                        );
                    }
                }
            }
            showMsg("保存成功");
        }
        if($lottery_id == 9 || $lottery_id == 10){
            $lockLimit = locks::getLockLimit($lottery_id,0);
            self::$view->setVar('lockLimit', $lockLimit);
        }
        $locks = locks::getItems($lottery_id);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加配置', 'url' => url('lock', 'lockAdd', array('lottery_id' => $lottery_id)))));
        self::$view->setVar('locks', $locks);
        self::$view->render('lock_locklist');
    }

    public function lockAdd()
    {
        $lotterys = lottery::getItemsById(locks::$lotterys);
        if (!$lottery_id = $this->request->getGet('lottery_id', 'intval', 0)) {
            if (!$lottery_id = $this->request->getPost('lottery_id', 'intval', 0)) {
                $tmp = reset($lotterys);
                $lottery_id = $tmp['lottery_id'];
            }
        }
        $lottery = $lotterys[$lottery_id];  //当前显示的彩种
        $methods = methods::getItems($lottery_id, 0, -1, 0, $is_lock = 1);  //当前显示的方法

        if ($this->request->getPost('submit', 'trim')) {
            $method_id = $this->request->getPost('method_id', 'trim');
            if (!$method_id) {
                showMsg("保存失败,玩法为空");
            }
            $lock_limit = $this->request->getPost('lock_limit', 'trim');
            if (!$lock_limit) {
                showMsg("保存失败,封锁值为空");
            }
            $res = locks::addItem(array(
                        'lottery_id' => $lottery_id,
                        'method_id' => $method_id,
                        'lock_limit' => $lock_limit,
                        'create_time' => date('Y-m-d H:i:s')
            ));
            if ($res) {
                showMsg("保存成功");
            }
            else {
                showMsg("保存失败");
            }
        }
        self::$view->setVar('methods', $methods);
        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('lottery', $lottery);
        self::$view->render('lock_addLock');
    }

    public function lockDelete()
    {
        if (!$lock_id = $this->request->getGet('lock_id', 'intval')) {
            showMsg("参数无效", 1);
        }
        if (!locks::deleteItem($lock_id)) {
            showMsg("删除数据失败", 0);
        }

        showMsg("删除数据成功", 0);
    }

}

?>