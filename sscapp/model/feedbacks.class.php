<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class feedbacks
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM activity WHERE id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM activity  WHERE id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems()
    {
        $sql = 'SELECT * FROM feedbacks ORDER BY create_time';

        return $GLOBALS['db']->getAll($sql);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('feedbacks', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('feedbacks',$data,array('id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM feedbacks WHERE feedback_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }
}

?>