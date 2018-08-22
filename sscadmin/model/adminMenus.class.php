<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class adminMenus
{
    const CACHE_TIME = 600;
    
    static public function getItem($id, $isEnabled = NULL)
    {
        $cacheKey = $id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM adminmenus WHERE menu_id = ' . intval($id);
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }
        
        if ($isEnabled !== NULL) {
            return $result && ($result['is_enabled'] == $isEnabled);
        }

        return $result;
    }

    static public function getItems($parent_id = NULL, $is_menu = NULL, $is_enabled = NULL)
    {
        $cacheKey = __FUNCTION__ . $parent_id . $is_menu . $is_enabled;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM adminmenus WHERE 1';
            if ($parent_id !== NULL) {
                if (is_array($parent_id)) {
                    $sql .= " AND parent_id IN(" . implode(',', $parent_id) . ")";
                }
                else {
                    $sql .= " AND parent_id=" . intval($parent_id);
                }
            }
            if ($is_menu !== NULL) {
                $sql .= " AND is_menu = $is_menu";
            }
            if ($is_enabled !== NULL) {
                $sql .= " AND is_enabled = $is_enabled";
            }
            $sql .= " ORDER BY sort ASC, menu_id ASC";
            $result = $GLOBALS['db']->getAll($sql, array(), 'menu_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        return $result;
    }

    static public function getItemsById($ids, $isMenu = NULL, $isEnabled = NULL)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM adminmenus WHERE menu_id IN(' . implode(',', $ids) . ')';
        if ($isMenu !== NULL) {
            $sql .= " AND is_menu=" . intval($isMenu);
        }
        if ($isEnabled !== NULL) {
            $sql .= " AND is_enabled=" . intval($isEnabled);
        }
        $sql .= " ORDER BY sort ASC, menu_id ASC";

        return $GLOBALS['db']->getAll($sql, array(),'menu_id');
    }
    
    static public function getItemByControlAction($control, $action, $isEnabled = 1)
    {
        $cacheKey = __FUNCTION__ . $control . $action . $isEnabled;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = "SELECT * FROM adminmenus WHERE control='$control' AND action='$action' AND is_enabled = $isEnabled";
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        return $result;
    }

    //递归：得到任意层级结构 $levelLimit可指定取无穷层数的前面几层 0为不限制层数:) parent_id为0时表示全部层级
    static public function getAllLevelItems($parentId, $isMenu = NULL, $levelLimit = 0, &$nowLevel = 0, &$result = array())
    {
        $result[$parentId]['childs'] = array();
        if ($levelLimit && $nowLevel >= $levelLimit) {
            return $result;
        }
        if (!$tmp = adminMenus::getItems($parentId, $isMenu)) {
            return $result;
        }
        else {
            $result[$parentId]['childs'] = $tmp;
        }

        foreach ($tmp as $k => $v) {
            //用下面这句也可以，但只能return $result;因为这不是固定的值！
            //$result[$parentId]['childs'] = adminMenus::getAllLevelItems($v['menu_id'], $include_child, $levelLimit, ++$nowLevel, $result[$parentId]['childs']);
            $level = ++$nowLevel;
            adminMenus::getAllLevelItems($v['menu_id'], $isMenu, $levelLimit, $level, $result[$parentId]['childs']);
            $nowLevel = 0;
        }

        return @reset(reset($result));
    }

    //得到所有父级
    static public function getAllParents($menu_id, $includeSelf = true)
    {
        if (!is_numeric($menu_id)) {
            throw new exception2('参数无效');
        }
        $result = array();
        if (!$group = adminMenus::getItem($menu_id)) {
            return false;
        }
        if ($includeSelf) {
            $result[$group['menu_id']] = $group;
        }
        while (1) {
            if (!$tmp = adminMenus::getItem($group['parent_id'])) {
                break;
            }
            $group = $tmp;
            $result[$tmp['menu_id']] = $tmp;
        }

        return $result;
    }

    // 得到一二级有权限的菜单
    static public function getCatMenus($ids)
    {
        $result = array();
        $tmp = adminMenus::getItemsById($ids, 1, 1);
        foreach ($tmp as $v) {
            if ($v['parent_id'] == 0) {
                $result[$v['menu_id']] = $v;
                $result[$v['menu_id']]['submenu'] = array();    //初始化数组
            }
        }

        foreach ($tmp as $v) {
            if ($v['parent_id'] > 0 && array_key_exists($v['parent_id'], $result)) {
                $result[$v['parent_id']]['submenu'][$v['menu_id']] = $v;
            }
        }

        return $result;
    }

    // 得到一二级所有权限菜单
    static public function getAllCatMenus()
    {
        $result = array();
        $tmp = adminMenus::getItems();
        foreach ($tmp as $v) {
            if ($v['parent_id'] == 0) {
                $result[$v['menu_id']] = $v;
                $result[$v['menu_id']]['submenu'] = array();    //初始化数组
            }
        }

        foreach ($tmp as $v) {
            if ($v['parent_id'] > 0) {
                $result[$v['parent_id']]['submenu'][$v['menu_id']] = $v;
            }
        }

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('adminmenus', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('adminmenus',$data,array('menu_id'=>$id));
    }

    static public function deleteItem($id)
    {

        $sql = "DELETE FROM adminmenus WHERE menu_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}
?>