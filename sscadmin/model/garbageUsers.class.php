<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class garbageUsers
{
    /**
     * 添加
     */
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('garbage_users', $data);
    }

}

?>