<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class recycles
{
    static public function getItem($id, $status = -1)
    {
        $sql = 'SELECT * FROM recycles WHERE recycle_id = ' . intval($id);
        if ($status != -1) {
            $sql .= " AND status = " . intval($status);
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($user_id = 0, $target_user_id = 0, $status = -1, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM recycles WHERE 1';
        if ($user_id > 0) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        if ($target_user_id > 0) {
            $sql .= " AND target_user_id = " . intval($target_user_id);
        }
        if ($status != -1) {
            $sql .= " AND status = " . intval($status);
        }
        $sql .= ' ORDER BY recycle_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber($user_id = 0, $target_user_id = 0, $status = -1)
    {
        $sql = 'SELECT count(*) AS count FROM recycles WHERE 1';
        if ($user_id > 0) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        if ($target_user_id > 0) {
            $sql .= " AND target_user_id = " . intval($target_user_id);
        }
        if ($status != -1) {
            $sql .= " AND status = " . intval($status);
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM recycles WHERE recycle_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'recycle_id');
    }

    //处理回收
    static public function processRecycle($recycle_id, $reason, $new_username = '', $admin_id = 0)
    {
        if (!$recycle_id || !$reason) {
            throw new exception2('参数无效');
        }
        if (!$recycle = recycles::getItem($recycle_id, 0)) {
            throw new exception2('找不到要被回收对象');
        }
        if (!$target_user = users::getItem($recycle['target_user_id'], 8)) {
            throw new exception2('找不到要被回收对象');
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        //1.删除绑定卡信息
        $bindCards = userBindCards::getItems($target_user['user_id'], 0, '', 1);
        foreach ($bindCards as $v) {
            userBindCards::deleteItem($v['bind_card_id']);
        }

        //2.删除安全资料
        $data = array(
                'real_name' => '',
                'secpwd' => '',
                'email' => '',
                'qq'    => '',
                'status' => 5,  //0已删除 1冻结 5已回收 8正常
                'remark' => $reason,
            );
        if ($new_username) {
            if (users::getItem($new_username, -1)) {
                showMsg("新用户名已经存在，请重新选择");
            }
            $data['username'] = $new_username;
        }
        if (users::updateItem($target_user['user_id'], $data) === false) {
            showMsg("修改安全资料失败");
        }

        //3.更新受理情况
        recycles::updateItem($recycle_id, array(
            'new_username' => $new_username,
            'finish_admin_id' => $admin_id,
            'status' => 8,
        ));

        //everything is OK
        $GLOBALS['db']->commit();

        return true;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('recycles', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('recycles',$data,array('recycle_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM recycles WHERE recycle_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}
?>