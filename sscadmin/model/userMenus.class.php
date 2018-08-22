<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userMenus
{
    static public function getItem($id, $isEnabled = NULL)
    {
        $sql = 'SELECT * FROM usermenus WHERE menu_id = ' . intval($id);
        if ($isEnabled !== NULL) {
            $sql .= " AND is_enabled=$isEnabled";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($parent_id = NULL, $is_menu = NULL, $is_enabled = NULL)
    {
        $sql = 'SELECT * FROM usermenus WHERE 1';
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
        $sql .= " ORDER BY sort ASC";
        $result = $GLOBALS['db']->getAll($sql, array(), 'menu_id');

        return $result;
    }

    static public function getItemsById($ids, $isMenu = NULL, $isEnabled = NULL)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        if (empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM usermenus WHERE menu_id IN(' . implode(',', $ids) . ')';
        if ($isMenu !== NULL) {
            $sql .= " AND is_menu=" . intval($isMenu);
        }
        if ($isEnabled !== NULL) {
            $sql .= " AND is_enabled=" . intval($isEnabled);
        }
        $sql .= " ORDER BY sort ASC";

        return $GLOBALS['db']->getAll($sql, array(), 'menu_id');
    }

    //递归：得到任意层级结构 $levelLimit可指定取无穷层数的前面几层 0为不限制层数:) parent_id为0时表示全部层级
    static public function getAllLevelItems($parentId, $isMenu = NULL, $levelLimit = 0, &$nowLevel = 0, &$result = array())
    {
        $result[$parentId]['childs'] = array();
        if ($levelLimit && $nowLevel >= $levelLimit) {
            return $result;
        }
        if (!$tmp = userMenus::getItems($parentId, $isMenu)) {
            return $result;
        }
        else {
            $result[$parentId]['childs'] = $tmp;
        }
        foreach ($tmp as $k => $v) {
            //用下面这句也可以，但只能return $result;因为这不是固定的值！
            //$result[$parentId]['childs'] = userMenus::getAllLevelItems($v['menu_id'], $include_child, $levelLimit, ++$nowLevel, $result[$parentId]['childs']);
            $tmpLevel = ++$nowLevel;
            userMenus::getAllLevelItems($v['menu_id'], $isMenu, $levelLimit, $tmpLevel, $result[$parentId]['childs']);
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
        if (!$group = userMenus::getItem($menu_id)) {
            return false;
        }
        if ($includeSelf) {
            $result[$group['menu_id']] = $group;
        }
        while (1) {
            if (!$tmp = userMenus::getItem($group['parent_id'])) {
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
        $tmp = userMenus::getItemsById($ids, 1, 1);
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
        $tmp = userMenus::getItems();
        foreach ($tmp as $v) {
            if ($v['parent_id'] == 0) {
                $result[$v['menu_id']] = $v;
                $result[$v['menu_id']]['submenu'] = array();    //初始化数组
            }
        }
        //二级
        foreach ($tmp as $v) {
            //判断所属 上级数组是否已给值--如果没有又存在parent_id  则是第三级
            if ($v['parent_id'] > 0 && !empty($result[$v['parent_id']])) {
                $result[$v['parent_id']]['submenu'][$v['menu_id']] = $v;
            }
        }
        ///三级要不要?
        return $result;
    }

    static public function getPriv($control, $action)
    {
        $sql = "SELECT * FROM usermenus WHERE control='{$control}' AND action='{$action}'";
        $sql .= " AND is_enabled = 1";  //必须已经启用

        return $GLOBALS['db']->getRow($sql);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('usermenus', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('usermenus', $data, array('menu_id' => $id));
    }

    static public function deleteItem($id)
    {

        $sql = "DELETE FROM usermenus WHERE menu_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}
?>