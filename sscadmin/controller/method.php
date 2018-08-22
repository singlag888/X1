<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：玩法组及玩法管理
 */
class methodController extends sscAdminController
{
    //方法概览
    public $titles = array(
            'methodList'=> '玩法列表',
            'addMethod' => '增加玩法',
            'editMethod'=> '修改玩法',
            'groupList' => '玩法组列表',
            'addGroup'  => '增加玩法组',
            'editGroup' => '修改玩法组',
        );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //玩法列表
    public function methodList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $mg_id = $this->request->getGet('mg_id', 'intval');
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        $actionLinks  = array(0 => array('title'=>'玩法组列表','url'=>url('method', 'methodList', array('lottery_id' => $lottery_id, 'mg_id' => $mg_id))));
        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'saveSort':
                    $sort_ids = $this->request->getPost('sort_ids', 'array');
                    foreach ($sort_ids as $method_id => $sort) {
                        methods::updateItem($method_id, array('sort' => $sort));
                    }
                    showMsg("保存成功", 1, $actionLinks);
                    break;
            }
        }

        $methods = methods::getItems($lottery_id, $mg_id);

        self::$view->setVar('methods', $methods);
        self::$view->setVar('mg_id', $mg_id);
        self::$view->setVar('actionLinks', array(
            1 => array('title' => '玩法组列表', 'url' => url('method', 'groupList', array('lottery_id' => $lottery_id))),
            0 => array('title' => '增加玩法', 'url' => url('method', 'addMethod', array('lottery_id' => $lottery_id, 'mg_id' => $mg_id))),
            ));
        self::$view->render('method_methodlist');
    }

    //增加玩法
    public function addMethod()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $mg_id = $this->request->getGet('mg_id', 'intval');
        $actionLinks  = array(0 => array('title'=>'玩法组列表','url'=>url('method','methodList', array('lottery_id' => $lottery_id, 'mg_id' => $mg_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $field_def = $this->request->getPost('field', 'array');
            foreach ($field_def as $k => $v) {
                if (!isset($field_def[$k]['has_filter_btn'])) {
                    $field_def[$k]['has_filter_btn'] = 0;
                }
            };
            $data = array(
                'lottery_id' => $lottery_id,
                'mg_id' => $this->request->getPost('mg_id', 'intval'),
                'name' => $this->request->getPost('name', 'trim'),
                'cname' => $this->request->getPost('cname', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'max_comb' => $this->request->getPost('max_comb', 'intval'),
                'max_money' => $this->request->getPost('max_money', 'intval'),
                'levels' => $this->request->getPost('levels', 'intval'),
                'expands' => serialize($this->request->getPost('count', 'array')),
                'field_def' => serialize($field_def),
                'can_input' => $this->request->getPost('can_input', 'intval'),
                'status' => $this->request->getPost('status', 'trim'),
                'method_property' => $this->request->getPost('method_property', 'intval'),
                'sort' => $this->request->getPost('sort', 'intval'),
                );

            // 清除回车符
            isset($data['description']) && $data['description'] = preg_replace("/\r|\n|\r\n/", '', $data['description']);

            if (!methods::addItem($data)) {
                showMsg("添加玩法失败!请检查数据输入是否完整。");
            }

            showMsg("添加成功！", 0, $actionLinks);
        }

        self::$view->setVar('mg_id', $mg_id);

        $groups = methods::getGroups($lottery_id);
        self::$view->setVar('groups', $groups);
        self::$view->render('method_addmethod');
    }

    //修改玩法
    public function editMethod()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $locations  = array(0 => array('title'=>'玩法列表','url'=>url('method','methodList', array('lottery_id' => $lottery_id, 'mg_id' =>$this->request->getPost('mg_id', 'intval')))));
            $field_def = $this->request->getPost('field', 'array');
            foreach ($field_def as $k => $v) {
                if (!isset($field_def[$k]['has_filter_btn'])) {
                    $field_def[$k]['has_filter_btn'] = 0;
                }
            }

            $data = array(
                'lottery_id' => $lottery_id,
                'mg_id' => $this->request->getPost('mg_id', 'intval'),
                'name' => $this->request->getPost('name', 'trim'),
                'cname' => $this->request->getPost('cname', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'max_comb' => $this->request->getPost('max_comb', 'intval'),
                'max_money' => $this->request->getPost('max_money', 'intval'),
                'levels' => $this->request->getPost('levels', 'intval'),
                'expands' => serialize($this->request->getPost('count', 'array')),
                'field_def' => serialize($field_def),
                'can_input' => $this->request->getPost('can_input', 'intval'),
                'status' => $this->request->getPost('status', 'trim'),
                'method_property' => $this->request->getPost('method_property', 'intval'),
                'is_lock' => $this->request->getPost('is_lock', 'trim'),
                'sort' => $this->request->getPost('sort', 'intval'),
                );

            // 清除回车符
            isset($data['description']) && $data['description'] = preg_replace("/\r|\n|\r\n/", '', $data['description']);

            if (!methods::updateItem($this->request->getPost('method_id', 'intval'), $data)) {
                showMsg("没有数据被更新！", 1, $locations);
            }

            showMsg("更新成功！", 0, $locations);
        }

        if (!$method_id = $this->request->getGet('method_id', 'trim')) {
            showMsg("参数无效");
        }

        $groups = methods::getGroups($lottery_id);
        self::$view->setVar('groups', $groups);

        $method = methods::getItem($method_id, NULL);
        $method['description']=  str_replace("\n", "<br/>", $method['description']);
        self::$view->setVar('method', $method)->setVar('json_method', json_encode($method));
        self::$view->render('method_addmethod');
    }

    public function groupList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);
        $actionLinks  = array(0 => array('title'=>'玩法组列表','url'=>url('method','groupList', array('lottery_id' => $lottery_id))));
        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'saveSort':
                    $sort_ids = $this->request->getPost('sort_ids', 'array');
                    foreach ($sort_ids as $mg_id => $sort) {
                        methods::updateGroup($mg_id, array('sort' => $sort));
                    }
                    showMsg("保存成功", 1, $actionLinks);
                    break;
            }
        }

        $groups = methods::getGroups($lottery_id);

        self::$view->setVar('groups', $groups);
        self::$view->setVar('actionLinks',array(
                1 => array('title' => '彩种列表', 'url' => url('lottery', 'lotteryList')),
                0 => array('title' => '增加玩法组', 'url' => url('method', 'addGroup', array('lottery_id' => $lottery_id))),
            ));
        self::$view->render('method_grouplist');
    }

    public function addGroup()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $actionLinks = array(0 => array('title' => '玩法组列表', 'url' => url('method', 'groupList', array('lottery_id' => $lottery_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'lottery_id' => $lottery_id,
                'name' => $this->request->getPost('name', 'trim'),
                'group_tag' => $this->request->getPost('group_tag', 'intval'),
                'description' => $this->request->getPost('description', 'trim'),
                'sort' => $this->request->getPost('sort', 'intval'),
            );
            if (!methods::addGroup($data)) {
                showMsg("添加玩法失败!请检查数据输入是否完整。");
            }

            showMsg("添加成功！", 0, $actionLinks);
        }

        self::$view->render('method_addgroup');
    }

    public function editGroup()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $actionLinks = array(0 => array('title' => '玩法组列表', 'url' => url('method', 'groupList', array('lottery_id' => $lottery_id))));

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("系统出错！找不到相应彩种");
        }
        self::$view->setVar('lottery', $lottery);

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'name' => $this->request->getPost('name', 'trim'),
                'group_tag' => $this->request->getPost('group_tag', 'intval'),
                'description' => $this->request->getPost('description', 'trim'),
                'sort' => $this->request->getPost('sort', 'intval'),
            );
            if (!methods::updateGroup($this->request->getPost('mg_id', 'intval'), $data)) {
                showMsg("没有数据被更新！", 1, $actionLinks);
            }

            showMsg("更新成功！", 0, $actionLinks);
        }

        if (!$mg_id = $this->request->getGet('mg_id', 'intval')) {
            showMsg("参数无效");
        }
        $group = methods::getGroup($mg_id);

        self::$view->setVar('group', $group);
        self::$view->render('method_addgroup');
        $GLOBALS['mc']->flush();
    }
}
