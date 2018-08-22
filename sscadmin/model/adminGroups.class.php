<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class adminGroups
{
    const CACHE_TIME = 600;

    static public function getItem($id)
    {
        $cacheKey = $id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM admingroups WHERE group_id = ' . intval($id);
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        return $result;
    }

    static public function getItems($parent_id = -1)
    {
        $key = is_array($parent_id) ? implode(',', $parent_id) : $parent_id ;
        $cacheKey = __FUNCTION__ . $key;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM admingroups WHERE 1';
            if ($parent_id != -1) {
                if (is_array($parent_id)) {
                    $sql .= " AND parent_id IN(" . implode(',', $parent_id) .")";
                }
                else {
                    $sql .= " AND parent_id = " . intval($parent_id);
                }
            }
            $sql .= ' ORDER BY sort ASC';
            $result = $GLOBALS['db']->getAll($sql, array(), 'group_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }
        if (empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM admingroups WHERE group_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(), 'group_id');
    }

    //得到一二层级
    static public function getFirstSecondItems()
    {
        if (!$result = adminGroups::getItems(0)) {
            return array();
        }
        foreach ($result as &$v) {
            $v['childs'] = array();
        }
        unset($v);
        $level2 = adminGroups::getItems(array_keys($result));
        foreach ($level2 as $v) {
            $result[$v['parent_id']]['childs'][$v['group_id']] = $v;
        }

        return $result;
    }

    //递归：得到任意层级结构 $levelLimit可指定取无穷层数的前面几层 0为不限制层数:)
    static public function getAllLevelItems($parent_id, $levelLimit = 0, &$nowLevel = 0, &$result = array())
    {
        $result[$parent_id]['childs'] = array();
        if ($levelLimit && $nowLevel >= $levelLimit) {
            return $result;
        }
        if (!$tmp = adminGroups::getItems($parent_id)) {
            return $result;
        }
        else {
            $result[$parent_id]['childs'] = $tmp;
        }
        foreach ($tmp as $k => $v) {
            //用下面这句也可以，但只能return $result;因为这不是固定的值！
            $level = ++$nowLevel;
            adminGroups::getAllLevelItems($v['group_id'], $levelLimit, $level , $result[$parent_id]['childs']);
            $nowLevel = 0;
        }

        return @reset(reset($result));
    }

    static public function getAllParents($group_id, $includeSelf = true)
    {
        if (!is_numeric($group_id)) {
            throw new exception2('参数无效');
        }
        $result = array();
        if (!$group = adminGroups::getItem($group_id)) {
            return false;
        }
        if ($includeSelf) {
            $result[$group['group_id']] = $group;
        }
        while (1) {
            if (!$tmp = adminGroups::getItem($group['parent_id'])) {
                break;
            }
            $group = $tmp;
            $result[$tmp['group_id']] = $tmp;
        }

        return $result;
    }

    //手动验证是否有权限 系统在进入控制器以前也在sscAdminController里面进行了判断
    //支持用权限名和控制器-行为来检查
    static public function verifyPriv($priv_name, $group_id = 0, &$menu = array())
    {
        if ($result = adminMenus::getItemByControlAction($priv_name[0], $priv_name[1])) {
                $menu = $result;
                if (!$group_id) {
                $group_id = $GLOBALS['SESSION']['admin_group_id'];  //默认验证当前登录人员的权限
            }
            $sql = "SELECT * FROM admingroups WHERE group_id={$group_id} AND FIND_IN_SET({$result['menu_id']}, priv_list)";
            if ($result = $GLOBALS['db']->getRow($sql)) {
                return true;
            }
        }

        return false;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('admingroups', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('admingroups', $data, array('group_id'=>$id));
    }

    static public function deleteItem($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $sql = "DELETE FROM admingroups WHERE group_id = $id";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}
?>