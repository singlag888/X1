<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/8
 * Time: 18:18
 */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class sessions{
    /**
     * @param string $uid
     * @param int $time
     * @return mixed
     */
    public static function getItemsByUid($uid, $timestamp, $field = 'user_id'){
        $sql = 'select '.$field.' from sessions where is_admin=0 AND user_id in('.$uid.') and expire_time>'.$timestamp;
        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 获取在线用户数
     * @return mixed
     */
    public static function countOnline(){
        $sql = 'SELECT COUNT(*) AS count FROM sessions  WHERE is_admin=0 AND expire_time>"'.time().'" AND user_id!=0';
        $result = $GLOBALS['db']->getRow($sql);
        return $result['count'];
    }
}