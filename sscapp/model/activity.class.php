<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class activity
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

    /**
     * author  snow
     * 修改活动列表 获取按字段获取,可传入字符串或者数组
     * @param string $field
     * @return bool
     */
    static public function getItems($field = '*')
    {
        //>>把数组字段转换成字符串
        if (is_array($field) && !empty($field))
        {
            $field = implode(',', $field);
        }

        //>>如果不是字符串,返回false;
        if (!is_string($field)) {
            return false;
        }
        $sql = 'SELECT ' . $field . ' FROM activity  ORDER BY sort';

        return $GLOBALS['db']->getAll($sql);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('activity', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('activity',$data,array('id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM activity WHERE id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }
}

?>