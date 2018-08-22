<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：权限菜单管理
 *
 */

class adminMenuController extends sscAdminController
{
    //方法概览
    public $titles = array(
        'menuList' => '权限列表',
        'addMenu'  => '增加权限',
        'editMenu' => '修改菜单',
        'deleteMenu'  => '删除菜单',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function menuList()
    {
        $parent_id = $this->request->getGet('parent_id', 'intval', 0);
        $prev_id = $this->request->getGet('prev_id', 'intval', 0);
        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'saveSort':
                    $sort_ids = $this->request->getPost('sort_ids', 'array');
                    foreach ($sort_ids as $menu_id => $sort) {
                        adminMenus::updateItem($menu_id, array('sort' => $sort));
                    }
                    showMsg("保存成功");
                    break;
            }
        }

        $menus = adminMenus::getItems($parent_id);

        self::$view->setVar('menus', $menus);
        self::$view->setVar('parent_id', $parent_id);
        self::$view->setVar('prev_id', $prev_id);
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加权限', 'url' => url('adminMenu', 'addMenu', array('parent_id' => $parent_id)))));
        self::$view->render('adminmenu_menulist');
    }

    public function addMenu()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations  = array(0 => array('title'=>'返回权限列表','url'=>url('adminMenu','menuList', array('parent_id' => $parent_id))));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'parent_id' => $this->request->getPost('parent_id', 'intval'),
                'title' => $this->request->getPost('title', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'control' => $this->request->getPost('control', 'trim'),
                'action' => $this->request->getPost('action', 'trim'),
                'is_menu' => $this->request->getPost('is_menu', 'intval'),
                'is_link' => $this->request->getPost('is_link', 'intval'),
                'is_log' => $this->request->getPost('is_log', 'intval'),
                'sort' => $this->request->getPost('sort', 'trim'),
                'is_enabled' => $this->request->getPost('is_enabled', 'intval'),
                );

            if (!adminMenus::addItem($data)) {
                showMsg("添加权限失败!请检查数据输入是否完整。");
            }

            showMsg("添加成功");
        }

        $firstSecondParents = adminMenus::getAllLevelItems(0, 1);
        self::$view->setVar('firstSecondParents', $firstSecondParents);
        self::$view->setVar('parent_id', $parent_id);
        self::$view->render('adminmenu_addmenu');
        $GLOBALS['mc']->flush();
    }

    public function editMenu()
    {
        if (!$prev_id = $this->request->getGet('prev_id', 'intval')) {
            $prev_id = $this->request->getPost('prev_id', 'intval');
        }
        $locations  = array(0 => array('title'=>'权限列表','url'=>url('adminMenu','menuList', array('parent_id' => $prev_id))));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'title' => $this->request->getPost('title', 'trim'),
                'description' => $this->request->getPost('description', 'trim'),
                'parent_id' => $this->request->getPost('parent_id', 'intval'),
                'control' => $this->request->getPost('control', 'trim'),
                'action' => $this->request->getPost('action', 'trim'),
                'is_menu' => $this->request->getPost('is_menu', 'intval'),
                'is_link' => $this->request->getPost('is_link', 'intval'),
                'is_log' => $this->request->getPost('is_log', 'intval'),
                'sort' => $this->request->getPost('sort', 'trim'),
                'is_enabled' => $this->request->getPost('is_enabled', 'intval'),
                );
            if (!adminMenus::updateItem($this->request->getPost('menu_id', 'intval'), $data)) {
                showMsg("没有数据被更新", 1, $locations);
            }

            showMsg("更新成功", 0, $locations);
        }
        if (!$menu_id = $this->request->getGet('menu_id', 'intval')) {
            showMsg("参数无效");
        }
        $menu = adminMenus::getItem($menu_id);

        $firstSecondParents = adminMenus::getAllLevelItems(0, 1);
        self::$view->setVar('firstSecondParents', $firstSecondParents);
        self::$view->setVar('menu', $menu);
        self::$view->setVar('prev_id', $prev_id);
        self::$view->render('adminmenu_addmenu');
        $GLOBALS['mc']->flush();
    }

    public function deleteMenu()
    {
        $locations  = array(0 => array('title'=>'返回权限列表','url'=>url('adminMenu','menuList')));
        if (!$menu_id = $this->request->getGet('menu_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }

        if (adminMenus::getItems($menu_id)) {
            showMsg("下面有子菜单，不能删除，若要删除，请先删除子菜单", 0, $locations);
        }

        if (!adminMenus::deleteItem($menu_id)) {
            showMsg("删除数据失败", 0, $locations);
        }

        showMsg("删除数据成功", 0, $locations);
    }

}
?>