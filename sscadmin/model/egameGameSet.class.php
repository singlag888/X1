<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class egameGameSet
{
    const CACHE_LIFETIME = 600;

    static public function getItems($game_type = -1, $status = -1)
    {
        $cacheKey = __FUNCTION__ . $game_type . $status;

        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM egame_mw_set WHERE 1';

            if ($game_type != -1 && is_numeric($game_type)) {
                $sql .= " AND type = " . intval($game_type);
            }

            if ($status != -1) {
                $sql .= " AND status = $status";
            }

            $result = $GLOBALS['db']->getAll($sql, array(),'game_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_LIFETIME);
        }

        return $result;
    }

    static public function getItem($gameId)
    {
        $cacheKey = __FUNCTION__ . $gameId;

        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM egame_mw_set WHERE 1 AND game_id =' . $gameId;
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_LIFETIME);
        }

        return $result;
    }


    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        if (!$GLOBALS['db']->updateSM('egame_mw_set', $data, array('id' => $id))) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }
}