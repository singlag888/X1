<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class egameMwSettings
{
    const CACHE_LIFETIME = 600;

    public static function getItem($name)
    {
        $cacheKey = __FUNCTION__ . $name;

        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = "SELECT * FROM `egame_mw_settings` WHERE 1 AND `name` ='$name'";
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_LIFETIME);
        }

        return $result;
    }

    public static function updateItem($name, $data)
    {
        if (empty($name)) {
            throw new exception2('参数无效');
        }

        if (!$GLOBALS['db']->updateSM('egame_mw_settings', $data, array('name' => $name))) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }
}