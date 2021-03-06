<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}
class messages
{
    static public function getItem($id, $user_id = 0, $status = 1)
    {
        $sql = 'SELECT *  FROM msgs  WHERE msg_id = ' . intval($id);
        if ($user_id != 0) {
            $sql .= " AND from_user_id = $user_id";
        }
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //发件箱
    static public function getItems($from_user_id = -1, $status = 1, $dateStart = '', $dateEnd = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT  mt.to_user_id,m.content,m.type,m.from_user_id,m.msg_id,m.create_time,m.title,m.create_time,m.from_user_id FROM msg_targets mt LEFT JOIN msgs m ON mt.msg_id = m.msg_id  WHERE 1';
        //$sql = 'SELECT a.title,a.create_time,a.from_user_id FROM msgs a WHERE 1';
        if ($from_user_id != -1) {
            $sql .= " AND m.from_user_id = " . intval($from_user_id);
        }
        if ($status != -1) {
            $sql .= " AND m.status = " . intval($status);
        }
        if ($dateStart != '') {
            $sql .= " AND m.create_time >= '" . $dateStart . "'";
        }
        if ($dateEnd != '') {
            $sql .= " AND m.create_time <= '" . $dateEnd . "'";
        }
        $sql .= " ORDER BY mt.msg_id DESC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);
        $finalResult = array();
        foreach ($result as $v) {
            if (!isset($finalResult[$v['msg_id']])) {
                $finalResult[$v['msg_id']] = array('msg_id' => $v['msg_id'], 'title' => $v['title'], 'create_time' => $v['create_time'], 'from_user_id' => $v['from_user_id'], 'type' => $v['type'], 'targets' => array());
            }
            $finalResult[$v['msg_id']]['targets'][$v['to_user_id']] = $v;
        }

        return $finalResult;
    }

    /*
     * 2013-04-05 修改
     * 增加两个参数：开始时间和结束时间
     */
    static public function getItemsNumber($from_user_id = -1, $status = 1, $dateStart = '', $dateEnd = '')
    {
        $sql = 'SELECT COUNT(m.msg_id) AS count FROM msgs m LEFT JOIN users u on m.from_user_id = u.user_id  WHERE 1';
        if ($from_user_id !== -1 && $from_user_id !== '') {
            if (is_numeric($from_user_id)) {
                $sql .= " AND m.from_user_id = " . intval($from_user_id);
            } else {
                $sql .= " AND u.username = '$from_user_id'";
            }
        }
        if ($status != -1) {
            $sql .= " AND m.status = " . intval($status);
        }
        if ($dateStart != '') {
            $sql .= " AND m.create_time >= '" . $dateStart . "'";
        }
        if ($dateEnd != '') {
            $sql .= " AND m.create_time <= '" . $dateEnd . "'";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    /**
     * 根据msgid 取得全部接收人
     * @param <int> $id
     * @return <array>  返回
     */
    static public function getAlltargets($id)
    {
        $sql = 'SELECT u.username,t.mt_id,t.to_user_id FROM msg_targets t LEFT JOIN users u ON t.to_user_id=u.user_id WHERE t.msg_id = ' . intval($id);

        return $GLOBALS['db']->getAll($sql, array(),'to_user_id');
    }

    //收件箱
    static public function getReceives($to_user_id = -1, $has_read = -1, $status = 1, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT b.*,a.title,a.create_time,a.from_user_id,a.content,a.type FROM msg_targets b LEFT JOIN msgs a ON b.msg_id = a.msg_id WHERE 1';
        if ($to_user_id != -1) {
            $sql .= " AND b.to_user_id = " . intval($to_user_id);
        }
        if ($has_read != -1) {
            $sql .= " AND b.has_read = " . intval($has_read);
        }
        if ($status != -1) {
            $sql .= " AND b.status = " . intval($status);
        }
        $sql .= " ORDER BY a.msg_id DESC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql);
    }

    static public function getReceivesNumber($to_user_id = -1, $has_read = -1, $status = 1)
    {
        $sql = 'SELECT COUNT(*) AS count FROM msg_targets WHERE 1';
        if ($to_user_id != -1) {
            $sql .= " AND to_user_id = " . intval($to_user_id);
        }
        if ($has_read != -1) {
            $sql .= " AND has_read = " . intval($has_read);
        }
        if ($status != -1) {
            $sql .= " AND status = " . intval($status);
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function addMsg($data, $to_user_ids)
    {
        if (!is_array($data) || !is_array($to_user_ids)) {
            throw new exception2('参数无效');
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        //添加至msgs表
        if (!messages::addItem($data)) {
            throw new exception2('添加消息内容失败');
        }
        if (!$msg_id = $GLOBALS['db']->insert_id()) {
            throw new exception2('事务失败1');
        }

        //添加至msg_targets表
        foreach ($to_user_ids as $to_user_id) {
            if (!is_numeric($to_user_id) || $to_user_id < 0) {
                throw new exception2('参数无效2');
            }
            $data2 = array(
                'msg_id' => $msg_id,
                'to_user_id' => $to_user_id,
                'has_read' => 0,
                'status' => 1,
            );
            if (!messages::addMsgTarget($data2)) {
                throw new exception2('事务失败2');
            }
        }

        //It seems OK
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('提交事务失败3');
        }

        return $msg_id;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('msgs', $data);
    }

    //更新信息已读状态
    static public function updateHasRead($id, $uid, $hasRead)
    {
        if (!is_numeric($id) || !is_numeric($hasRead) || !is_numeric($uid)) {
            throw new exception2('参数无效');
        }

        $sql = "UPDATE msg_targets SET has_read=$hasRead WHERE msg_id=$id AND to_user_id=$uid LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('msgs',$data, array('msg_id'=>$id));
    }

    static public function deleteItem($id, $real_delete = false)
    {
        if ($real_delete) {
            $sql = "DELETE FROM msgs WHERE msg_id=" . intval($id) . ' LIMIT 1';
            $type ='d';
        }
        else {
            $sql = "UPDATE msgs SET status = 0 WHERE msg_id=" . intval($id) . ' LIMIT 1';
            $type = 'u';
        }
        return $GLOBALS['db']->query($sql, array(), $type);
    }

    //注意 删除只针对msg_targets表对msgs的引用，消息本身删不删并不重要
    static public function deleteMsgTarget($id, $real_delete = false)
    {
        if ($real_delete) {
            $sql = "DELETE FROM msg_targets WHERE mt_id=" . intval($id) . ' LIMIT 1';
            $type = 'd';
        }
        else {
            $sql = "UPDATE msg_targets SET status = 0 WHERE mt_id=" . intval($id) . ' LIMIT 1';
            $type = 'u';
        }

        return $GLOBALS['db']->query($sql, array(), $type);
    }

    static public function addMsgTarget($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('msg_targets', $data);
    }

}

?>