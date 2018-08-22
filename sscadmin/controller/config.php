<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：配置管理
 */
class configController extends sscAdminController
{
    //方法概览
    public $titles = array(
        'configList'    => '配置列表',
        'addConfig'     => '增加配置',
        'editConfig'    => '修改配置',
        'deleteConfig'  => '删除配置',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function configList()
    {
        $parent_id = $this->request->getGet('parent_id', 'intval', 0);
        $prev_id = $this->request->getGet('prev_id', 'intval', 0);
        $configs = config::getItems($parent_id);

        self::$view->setVar('configs', $configs);
        self::$view->setVar('parent_id', $parent_id);
        self::$view->setVar('prev_id', $prev_id);
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加配置', 'url' => url('config', 'addConfig', array('parent_id' => $parent_id)))));
        self::$view->render('config_configlist');
    }

    public function addConfig()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations  = array(0 => array('title'=>'返回配置列表','url'=>url('config','configList', array('parent_id' => $parent_id))));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'title' => $this->request->getPost('title', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'cfg_key' => $this->request->getPost('cfg_key', 'trim'),
                'cfg_value' => $this->request->getPost('cfg_value', 'trim'),
                'parent_id' => $this->request->getPost('parent_id', 'intval'),
                );
            if (!config::addItem($data)) {
                showMsg("添加配置失败!请检查数据输入是否完整。");
            }

            showMsg("添加成功！");
        }

        self::$view->setVar('parent_id', $parent_id);
        self::$view->setVar('parentConfigs', config::getItems(0));
        self::$view->render('config_addconfig');
        $GLOBALS['mc']->flush();
    }

    public function editConfig()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations  = array(0 => array('title'=>'返回配置列表','url'=>url('config','configList', array('parent_id' => $parent_id))));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'parent_id' => $this->request->getPost('parent_id', 'intval'),
                'title' => $this->request->getPost('title', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'cfg_key' => $this->request->getPost('cfg_key', 'trim'),
                'cfg_value' => $this->request->getPost('cfg_value', 'trim'),
                );
            if (!config::updateItem($this->request->getPost('config_id', 'trim'), $data)) {
                showMsg("没有数据被更新！", 1, $locations);
            }

            showMsg("更新成功！", 0, $locations);
        }

        if (!$config_id = $this->request->getGet('config_id', 'trim')) {
            showMsg("参数无效");
        }
        $config = config::getItem($config_id);

        self::$view->setVar('config', $config);
        self::$view->setVar('parentConfigs', config::getItems(0));
        self::$view->render('config_addconfig');
        $GLOBALS['mc']->flush();
    }

    public function deleteConfig()
    {
        $locations  = array(0 => array('title'=>'返回配置列表','url'=>url('config','configList')));
        if (!$config_id = $this->request->getGet('config_id', 'trim')) {
            showMsg("参数无效！", 1, $locations);
        }

        if (!config::deleteItem($config_id)) {
            showMsg("删除数据失败！", 0, $locations);
        }

        showMsg("删除数据成功！", 0, $locations);
    }

}
?>