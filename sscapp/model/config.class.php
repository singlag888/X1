<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class config
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM config WHERE config_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM config WHERE cfg_key = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($parent_id = NULL)
    {
        $sql = 'SELECT * FROM config WHERE 1';
        if ($parent_id !== NULL) {
            $sql .= " AND parent_id = " . intval($parent_id);
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM config WHERE config_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'config_id');
    }

    static public function getConfig($cfg_key, $default = NULL)
    {
        $cacheKey = $cfg_key;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM config WHERE cfg_key = \'' . $cfg_key . '\'';
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 3600);
        }
        return $result ? $result['cfg_value'] : $default;
    }
    static public function rgetConfigs($cfg_keys)
    {
        $cacheKey = implode('_',$cfg_keys);
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM config WHERE cfg_key IN ("'.implode('","',$cfg_keys).'")';
            $result = $GLOBALS['db']->getAll($sql);
            $result =$result ? array_combine(array_column($result,'cfg_key'),array_column($result,'cfg_value')) : '';
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 3600);
        }
        return $result;
    }

    static public function getConfigWithCache($cfg_key, $default = NULL)
    {
        $cacheKey = $cfg_key;

        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM config WHERE cfg_key = \'' . $cfg_key . '\'';
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 3600);
        }

        return $result ? $result['cfg_value'] : $default;
    }

    static public function getConfigs($cfg_keys, $default = NULL)
    {
        $sql = 'SELECT * FROM config WHERE cfg_key IN("' . implode('","', $cfg_keys) . '")';
        $result = array();
        $tmp = $GLOBALS['db']->getAll($sql);
        foreach ($tmp as $v) {
            if(!empty($v)){
                $result[$v['cfg_key']] = $v['cfg_value'];
            }
        }

        foreach($cfg_keys as $v) {
            if (!isset($result[$v])) {
                $result[$v] = $default;
            }
        }

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('config', $data);
    }

    static public function updateValueByKey($cfg_key, $cfg_value)
    {
        if (!$cfg_key) {
            throw new exception2('invalid args');
        }

        $sql = "UPDATE config SET cfg_value = '$cfg_value' WHERE cfg_key = '$cfg_key' LIMIT 1";
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

        return $GLOBALS['db']->updateSM('config',$data,array('config_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM config WHERE config_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}
?>