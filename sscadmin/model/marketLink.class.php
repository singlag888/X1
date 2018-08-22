<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class marketLink
{
    static public function getItemByCond($where = '',$field = '*')
    {

        if($where) {
            $sql = "select $field from market_link where ".$where;
        }else{
            $sql = "select $field from market_link";
        }

        return $GLOBALS['db']->getRow($sql);
    }
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('market_link', $data);
    }

    static public function updateItem( $data,$where)
    {


        if (!$GLOBALS['db']->updateSM('market_link', $data, $where)) {
            return false;
        }


        return $GLOBALS['db']->affected_rows();
    }
    static public function getItemsByCond($where = '',$field = '*')
    {
        if($where) {
            $sql = "select $field from market_link where ".$where;
        }else{
            $sql = "select $field from market_link";
        }

        return $GLOBALS['db']->getAll($sql);
    }


}

?>