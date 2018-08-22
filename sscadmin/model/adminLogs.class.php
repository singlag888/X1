<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class adminLogs
{
    static public function getItem($id)
    {
        $sql = 'SELECT * FROM adminlogs WHERE log_id = ' . intval($id);
        // 项目名：模块名：表名
        return $GLOBALS['db']->getRow($sql);
    }

    //这里更改 按控制器  和 方法 查询
    static public function getItems($admin_id = '', $action = '', $ip = '', $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.title,b.description FROM adminlogs a LEFT JOIN adminmenus b ON a.control = b.control AND a.action = b.action WHERE 1';
        if ($admin_id != '') {
            if (is_numeric($admin_id)) {
                $sql .= " AND a.admin_id = " . intval($admin_id);
            }
            else {
                $sql .= " AND a.username = '$admin_id'";
            }
        }
        if ($action !== '') {
            $sql .= " AND a.action = '$action'";
        }
        if ($ip != '') {
            $sql .= " AND a.client_ip = '$ip'";
        }
        if ($startDate !== '') {
            $sql .= " AND a.date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.date <= '$endDate'";
        }
        $sql .= ' ORDER BY log_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);
        foreach ($result as $k => $v) {
            $result[$k]['post_data'] = unserialize($v['post_data']);
        }

        return $result;
    }

    static public function getItemsNumber($admin_id = '', $action = '', $ip = '', $startDate = '', $endDate = '')
    {
        $sql = 'SELECT count(*) AS count FROM adminlogs a LEFT JOIN adminmenus b ON a.control = b.control AND a.action = b.action WHERE 1';
        if ($admin_id != '') {
            if (is_numeric($admin_id)) {
                $sql .= " AND a.admin_id = " . intval($admin_id);
            }
            else {
                $sql .= " AND a.username = '$admin_id'";
            }
        }
        if ($action !== '') {
            $sql .= " AND a.action = '$action'";
        }
        if ($ip != '') {
            $sql .= " AND a.client_ip = '$ip'";
        }
        if ($startDate !== '') {
            $sql .= " AND a.date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.date <= '$endDate'";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM adminlogs WHERE user_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'log_id');
    }

    static public function addLog($is_success, $remark, $username = '')
    {
        $postData = $GLOBALS['REQUEST']->getPostAsArray();
        //只记录POST方式的日志
        if ($is_success && !$postData) {
            return false;
        }
        $data = array(
            'admin_id' => isset($GLOBALS['SESSION']['admin_id']) ? $GLOBALS['SESSION']['admin_id'] : 0,
            'username' => $username ? $username : (isset($GLOBALS['SESSION']['admin_username']) ? $GLOBALS['SESSION']['admin_username'] : ''),
            'control' => CONTROLLER,
            'action' => ACTION,
            'is_success' => $is_success,
            'remark' => $remark,
            'client_ip' => $GLOBALS['REQUEST']['client_ip'],
            'date' => date('Y-m-d'),
            'get_data' => serialize($GLOBALS['REQUEST']->getGetAsArray()),
            'post_data' => serialize($postData),
        );

        return adminLogs::addItem($data);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('adminlogs', $data);
    }

}

?>