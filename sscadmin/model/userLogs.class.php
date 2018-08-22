<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userLogs
{
    static public function getItem($id)
    {
        $sql = 'SELECT * FROM userlogs WHERE log_id = ' . intval($id);
        // 项目名：模块名：表名
        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($user_id = '', $action = '', $ip = '', $startDate = '', $endDate = '', $is_success = NULL, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        //111111 为效率起见，不要联表使用count
        //$sql = 'SELECT a.*,b.title,b.description FROM userlogs a LEFT JOIN usermenus b ON a.control = b.control AND a.action = b.action WHERE 1';
        $sql = 'SELECT a.* FROM userlogs a WHERE 1';
        if ($user_id != '') {
            if (is_numeric($user_id)) {
                $sql .= " AND a.user_id = " . intval($user_id);
            }
            else {
                $sql .= " AND a.username = '$user_id'";
            }
        }
        if ($action != '') {
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
        if ($is_success !== NULL) {
            $sql .= " AND a.is_success = '$is_success'";
        }
        $sql .= ' ORDER BY a.log_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);
        foreach ($result as $k => $v) {
            $result[$k]['post_data'] = unserialize($v['post_data']);
        }

        return $result;
    }

    //111111 为效率起见，不要联表使用count
    static public function getItemsNumber($user_id = '', $action = '', $ip = '', $startDate = '', $endDate = '', $is_success = NULL)
    {
        $sql = 'SELECT count(*) AS count FROM userlogs a WHERE 1';
        if ($user_id != '') {
            if (is_numeric($user_id)) {
                $sql .= " AND a.user_id = " . intval($user_id);
            }
            else {
                $sql .= " AND a.username = '$user_id'";
            }
        }
        if ($action != '') {
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
        if ($is_success !== NULL) {
            $sql .= " AND a.is_success = '$is_success'";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM userlogs WHERE log_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'log_id');
    }

    static public function addLog($is_success, $remark, $username = '', $user_id = 0, $frm = 1)
    {
        $data = array(
            'user_id'  => isset($GLOBALS['SESSION']['user_id']) ? $GLOBALS['SESSION']['user_id'] : $user_id,
            'username' => isset($GLOBALS['SESSION']['username']) ? $GLOBALS['SESSION']['username'] : $username,
            'control'   => CONTROLLER,
            'action'    => ACTION,
            'is_success' => $is_success,
            'remark'=> $remark,
            'client_ip' => $GLOBALS['REQUEST']['client_ip'],
            'frm' => isset($GLOBALS['SESSION']['frm']) ? $GLOBALS['SESSION']['frm'] : $frm,
            'domain' => $_SERVER['HTTP_HOST'],
            'proxy_ip' => $GLOBALS['REQUEST']['proxy_ip'],
            'user_agent' => strlen($_SERVER['HTTP_USER_AGENT']) > 255 ? substr($_SERVER['HTTP_USER_AGENT'], 0 , 254) : $_SERVER['HTTP_USER_AGENT'],
            'date' => date('Y-m-d'),
            'get_data'  => serialize($GLOBALS['REQUEST']->getGetAsArray()),
            'post_data' => serialize($GLOBALS['REQUEST']->getPostAsArray()),
        );

        return userLogs::addItem($data);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('userlogs', $data);
    }

    static public function resetTodayFailedRecord($user_id)
    {
        $sql = "UPDATE userlogs SET is_success = -32768 WHERE user_id = '$user_id' AND action = 'login' AND date = '" . date('Y-m-d') . "'";

        return $GLOBALS['db']->query($sql, array(), 'u');
    }

}
?>