<?php

/**
 * 数据库抽象类，具体数据库需要继承这个，并实现DML标准方法
 * 调用唯一实例 $instance = DB::getInstance($settings);
 *
 */
abstract class db
{
    static $default_charset = 'utf8';
    // 允许不同协议的实例
    static protected $instance = array();

    abstract function connect();

    abstract function getOne($sql);

    abstract function getCol($sql,$col);

    abstract function getRow($sql);

    abstract function getAll($sql);

    abstract function query($sql);

    abstract function close();

    // 可以继承Object类，但还得派生
    static public function getInstance($settings, $isShare = false)
    {
        if (RUN_ENV == 3) {
            if (!preg_match('`^\w+://\w+:([^@]+)@\S+$`Uim', $settings, $match)) {
                exit('dconfig set error');
            }
            if (!$pa = authcode($match[1], 'DECODE', PROJECT)) {
                exit('dconfig2 set error');
            }

            $settings = str_replace($match[1], $pa, $settings);
        }

        $set = @parse_url($settings);
        // if (!isset(self::$instance[$set['scheme']])) {
            require_once dirname(__FILE__) . "/drivers/{$set['scheme']}.class.php";
            $className = ucfirst($set['scheme']);
            self::$instance[$set['scheme']] = new $className($settings, self::$default_charset, RUN_ENV < 3 ? true : false, $isShare);
        // }

        // if (self::$instance[$set['scheme']]->settings !== $settings) {
        //     self::$instance[$set['scheme']]->close();
        //     self::$instance[$set['scheme']]->parseSettings($settings);
        // }

        return self::$instance[$set['scheme']];
    }

    public function buildMysqlURL($urlInfo)
    {
        return 'mysql://' . $urlInfo['username'] . ':' . $urlInfo['password']
        . '@' . $urlInfo['ip'] . ':' . $urlInfo['port'] . '/?' . $urlInfo['database'];
    }

    function dbCreatIn($item_list, $field_name = '')
    {
        if (!is_array($item_list)) {
            $item_list = explode(',', $item_list);
        }
        if (empty($item_list)) {
            return $field_name . " IN ('') ";
        }
        $item_list = array_unique($item_list);

        return $field_name . " IN ('" . implode("','", $item_list) . "') ";
    }

}
?>
