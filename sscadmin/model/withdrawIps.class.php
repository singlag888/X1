<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 提款记录模型
 */
class withdrawIps
{
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('withdraw_ips', $data);
    }
}

?>