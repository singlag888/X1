<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：奖金组及奖金管理
 */
class prizeController extends sscAdminController
{
    //方法概览
    public $titles = array(
            //'prizeList'=> '奖金列表',
            'addPrize' => '增加奖金',
            'editPrize'=> '修改奖金',
            'groupList' => '奖金组列表',
            'addGroup'  => '增加奖金组',
            'editGroup' => '修改奖金组',
            'viewGroup' => '查看奖金组',
            'setBaseGroup' => '设置基本组',
        );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    /** 在添加奖金组时一并添加了
    public function prizeList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $mg_id = $this->request->getGet('mg_id', 'intval');
        $prizes = prizes::getItems($lottery_id, $mg_id);

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        self::$view->setVar('prizes', $prizes);
        self::$view->setVar('mg_id', $mg_id);
        self::$view->setVar('actionLinks', array(
            1 => array('title' => '奖金组列表', 'url' => url('prize', 'groupList', array('lottery_id' => $lottery_id))),
            0 => array('title' => '增加奖金', 'url' => url('prize', 'addPrize', array('lottery_id' => $lottery_id, 'mg_id' => $mg_id))),
            ));
        self::$view->render('prize_prizelist');
    }

    public function addPrize()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $mg_id = $this->request->getGet('mg_id', 'intval');
        $actionLinks  = array(0 => array('title'=>'奖金组列表','url'=>url('prize','prizeList', array('lottery_id' => $lottery_id, 'mg_id' => $mg_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'lottery_id' => $lottery_id,
                'mg_id' => $this->request->getPost('mg_id', 'intval'),
                'name' => $this->request->getPost('name', 'trim'),
                'cname' => $this->request->getPost('cname', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'max_comb' => $this->request->getPost('max_comb', 'intval'),
                'expand_num' => $this->request->getPost('expand_num', 'intval'),
                'prize_level' => $this->request->getPost('prize_level', 'intval'),
                'is_dup_prize' => $this->request->getPost('is_dup_prize', 'intval', '0|1'),
                'status' => $this->request->getPost('status', 'trim'),
                'sort' => $this->request->getPost('sort', 'intval'),
                );
            if (!prizes::addItem($data)) {
                showMsg("添加奖金失败!请检查数据输入是否完整。");
            }

            showMsg("添加成功！", 0, $actionLinks);
        }

        self::$view->setVar('mg_id', $mg_id);

        $groups = prizes::getGroups($lottery_id);
        self::$view->setVar('groups', $groups);
        self::$view->render('prize_addprize');
    }

    public function editPrize()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $locations  = array(0 => array('title'=>'奖金列表','url'=>url('prize','prizeList', array('lottery_id' => $lottery_id, 'mg_id' =>$this->request->getPost('mg_id', 'intval')))));
            $data = array(
                'lottery_id' => $lottery_id,
                'mg_id' => $this->request->getPost('mg_id', 'intval'),
                'name' => $this->request->getPost('name', 'trim'),
                'cname' => $this->request->getPost('cname', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'max_comb' => $this->request->getPost('max_comb', 'intval'),
                'expand_num' => $this->request->getPost('expand_num', 'intval'),
                'prize_level' => $this->request->getPost('prize_level', 'intval'),
                'is_dup_prize' => $this->request->getPost('is_dup_prize', 'intval', '0|1'),
                'status' => $this->request->getPost('status', 'trim'),
                'sort' => $this->request->getPost('sort', 'intval'),
                );
dump($data);die();
            if (!prizes::updateItem($this->request->getPost('prize_id', 'intval'), $data)) {
                showMsg("没有数据被更新！", 1, $locations);
            }

            showMsg("更新成功！", 0, $locations);
        }

        if (!$prize_id = $this->request->getGet('prize_id', 'trim')) {
            showMsg("参数无效");
        }

        $groups = prizes::getGroups($lottery_id);
        self::$view->setVar('groups', $groups);

        $prize = prizes::getItem($prize_id);

        self::$view->setVar('prize', $prize);
        self::$view->render('prize_addprize');
    }
     *
     */

    //奖金组列表
    public function groupList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        $groups = prizes::getGroups($lottery_id);
        self::$view->setVar('groups', $groups);
        self::$view->setVar('actionLinks',array(
                array('title' => '彩种列表', 'url' => url('lottery', 'lotteryList')),
                array('title' => '设置基本组', 'url' => url('prize', 'setBaseGroup', array('lottery_id' => $lottery_id))),
                array('title' => '增加固定奖金组', 'url' => url('prize', 'addGroup', array('lottery_id' => $lottery_id))),
            ));
        self::$view->render('prize_grouplist');
    }

    //设置基本组
    public function setBaseGroup()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $actionLinks  = array(0 => array('title'=>'奖金组列表','url'=>url('prize','groupList', array('lottery_id' => $lottery_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'lottery_id' => $lottery_id,
                'name' => $this->request->getPost('name', 'trim'),
                'disp_name' => $this->request->getPost('disp_name', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'is_base' => 1,
                'max_top_rebate' => $this->request->getPost('max_top_rebate', 'floatval'),
                'prize' => $this->request->getPost('prize', 'array'),
                'top_rebate' => $this->request->getPost('top_rebate', 'array'),
                );

            //如果存在就更新，否则添加
            if ($baseGroup = prizes::getBaseGroup($lottery_id)) {
                prizes::updateGroup($baseGroup['pg_id'], $data);
            }
            else {
                prizes::addGroup($data);
            }

            showMsg("更新成功！", 0, $actionLinks);
        }

        $prizes = array();
        if ($baseGroup = prizes::getBaseGroup($lottery_id)) {
            $prizes = prizes::getItems($lottery_id, 0, $baseGroup['pg_id'], 0, 1);
        }
        $methods = methods::getItems($lottery_id, 0, -1, 1);
        self::$view->setVar('baseGroup', $baseGroup);
        self::$view->setVar('prizes', $prizes);
        self::$view->setVar('methods', $methods);
        self::$view->render('prize_setbasegroup');
        $GLOBALS['mc']->flush();
    }

    //增加奖金组
    public function addGroup()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $actionLinks  = array(0 => array('title'=>'奖金组列表','url'=>url('prize','groupList', array('lottery_id' => $lottery_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'lottery_id' => $lottery_id,
                'name' => $this->request->getPost('name', 'trim'),
                'disp_name' => $this->request->getPost('disp_name', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'is_base' => 0,
                'max_top_rebate' => $this->request->getPost('max_top_rebate', 'floatval'),
                'prize' => $this->request->getPost('prize', 'array'),
                'top_rebate' => $this->request->getPost('top_rebate', 'array'),
                );
            if (!prizes::addGroup($data)) {
                showMsg("添加奖金失败!请检查数据输入是否完整。");
            }

            showMsg("添加成功！", 0, $actionLinks);
        }

        $methods = methods::getItems($lottery_id, 0, -1, 1);

        self::$view->setVar('methods', $methods);
        self::$view->render('prize_addgroup');
        $GLOBALS['mc']->flush();
    }

    //修改奖金组
    public function editGroup()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $actionLinks  = array(0 => array('title'=>'奖金组列表','url'=>url('prize','groupList', array('lottery_id' => $lottery_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'lottery_id' => $lottery_id,
                'name' => $this->request->getPost('name', 'trim'),
                'disp_name' => $this->request->getPost('disp_name', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'max_top_rebate' => $this->request->getPost('max_top_rebate', 'floatval'),
                'prize' => $this->request->getPost('prize', 'array'),
                'top_rebate' => $this->request->getPost('top_rebate', 'array'),
                );
            prizes::updateGroup($this->request->getPost('pg_id', 'intval'), $data);

            showMsg("更新成功！", 0, $actionLinks);
        }

        if (!$pg_id = $this->request->getGet('pg_id', 'intval')) {
            showMsg("参数无效");
        }
        if (!$group = prizes::getGroup($pg_id)) {
            showMsg("找不到奖金组");
        }

        if (!$prizes = prizes::getItems($lottery_id, 0, $pg_id, 0, 1)) {
            showMsg("不再支持固定奖金组");
        }
        $methods = methods::getItems($lottery_id, 0, -1, 1);
        self::$view->setVar('group', $group);
        self::$view->setVar('prizes', $prizes);
        self::$view->setVar('methods', $methods);
        self::$view->render('prize_addgroup');
    }

    public function viewGroup()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $actionLinks  = array(0 => array('title'=>'奖金组列表','url'=>url('prize','groupList', array('lottery_id' => $lottery_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        if (!$pg_id = $this->request->getGet('pg_id', 'intval')) {
            showMsg("参数无效");
        }
        if (!$group = prizes::getGroup($pg_id)) {
            showMsg("找不到奖金组");
        }

        $prizes = prizes::getItems($lottery_id, 0, $pg_id, 0, 1);
        $methods = methods::getItems($lottery_id, 0, -1, 1);
        self::$view->setVar('group', $group);
        self::$view->setVar('prizes', $prizes);
        self::$view->setVar('methods', $methods);
        self::$view->render('prize_viewgroup');
    }
}
?>