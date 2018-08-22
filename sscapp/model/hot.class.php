<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class hot
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM hot WHERE id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM hot  WHERE id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItemByLotteryId($lottery_id,$lottery_belong)
    {
        if (is_int($lottery_id) && is_int($lottery_belong)) {
            if($lottery_belong>1)$lottery_belong=1;
            $sql = "SELECT * FROM hot WHERE lottery_id=$lottery_id AND lottery_belong=$lottery_belong";
            return $GLOBALS['db']->getRow($sql);
        }
        return false;
    }

    static public function getItems($limit=3)
    {
        if($limit==-1)$sql = 'SELECT * FROM hot ORDER BY last_update_time DESC';
        else $sql = 'SELECT * FROM hot ORDER BY last_update_time DESC limit '.$limit;
        return $GLOBALS['db']->getAll($sql);
    }

    static public function getCount()
    {
        $sql = 'SELECT count(*) as count FROM hot';

        return $GLOBALS['db']->getRow($sql);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('hot', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('hot',$data,array('id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM hot WHERE id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }
}

?>