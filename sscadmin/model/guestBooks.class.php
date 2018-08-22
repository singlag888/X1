<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class guestBooks
{
    static public function getItem($id)
    {
        $sql = 'SELECT g.*,a.username AS deal_admin_username FROM guestbooks AS g LEFT JOIN admins AS a ON a.admin_id=g.deal_admin_id WHERE g.gb_id = ' . intval($id);
        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($username = '', $status = 0, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT g.*,a.username AS deal_admin_username FROM guestbooks AS g LEFT JOIN admins AS a ON a.admin_id=g.deal_admin_id WHERE 1';
        if ($username != '') {
            $sql .= " AND g.username='{$username}'";
        }
        if ($status != 0) {
            $sql .= " AND g.status = {$status}";
        }

        $sql .= " ORDER BY g.gb_id DESC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql);
    }

    static public function getItemsNumber($username = '', $status = 0)
    {
        $sql = 'SELECT count(*) AS count FROM guestbooks WHERE 1';

        if ($username != '') {
            $sql .= " AND  username='{$username}'";
        }
        if ($status != 0) {
            $sql .= " AND status = {$status}";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('guestbooks', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('guestbooks',$data,array('gb_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM guestbooks WHERE gb_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }
    
    static public function replyGuestBook($gb_id, $title, $content, $to_user_id)
    {
        if (!is_numeric($gb_id) || !is_string($title) || !is_string($title) || !is_numeric($to_user_id)) {
            throw new exception2('参数无效');
        }
        $msgData = array(
            'type' => 2,
            'title' => $title,
            'content' => $content,
            'from_user_id' => $GLOBALS['SESSION']['admin_id'],
            'create_time' => date('Y-m-d H:i:s'),
            'status' => 1,
        );
        if (!$msg_id = messages::addMsg($msgData, array($to_user_id))) {
            throw new exception2('消息发送失败');
        }
        $gusetData = array(
            'status' => 3,
            'deal_admin_id' => $GLOBALS['SESSION']['admin_id'],
            'msg_id' => $msg_id,
            'deal_time' => date("Y-m-d H:i:s"),
        );
        if (!guestBooks::updateItem($gb_id, $gusetData)) {
            throw new exception2('留言状态修改失败');
        }
        return true;
    }

}

?>