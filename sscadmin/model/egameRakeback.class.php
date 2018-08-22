<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 电子返点记录模型
 */
class egameRakeback
{
    static public function getItem($id)
    {
        $sql = 'SELECT * FROM egame_rakeback WHERE rakeback_id = ' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($user_id = '', $include_childs = 0, $is_test = -1, $rakeback_amount = -1, $first_transfer_time = '', $last_rakeback_time = '', $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.username,b.is_test FROM egame_rakeback a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= ' AND b.user_id = \'' . $user_id . '\'';
                } else {
                    $sql .= ' AND b.username = \'' . $user_id . '\'';
                }
            } else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id)) {
                        return [];
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= ' AND b.is_test = ' . intval($is_test);
        }
        if ($rakeback_amount !== -1) {
            $sql .= ' AND a.rakeback_amount = ' . $rakeback_amount;
        }
        if ($first_transfer_time !== '') {    //提案发起时间
            $sql .= ' AND a.first_transfer_time >= \'' . $first_transfer_time . '\'';
        }
        if ($last_rakeback_time !== '') {
            $sql .= ' AND a.last_rakeback_time <= \'' . $last_rakeback_time . '\'';
        }
        if (empty($order_by)) {
            $sql .= ' ORDER BY a.rakeback_id DESC';
        } else {
            $sql .= ' ' . $order_by;
        }

        if ($start > -1) {
            $sql .= ' LIMIT ' . $start . ', ' . $amount;
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('egame_rakeback', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('egame_rakeback',$data,array('user_id'=>$id));
    }
}
