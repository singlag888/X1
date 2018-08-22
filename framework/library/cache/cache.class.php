<?php

/**
 * cache抽象类，提供一些抽象方法，具体由对应的mc,xc,apc,file类等实现
 *
 * @package Light_Cache
 */
abstract class cache
{

    public $settings = '';
    public $server = '';
    public $prefix = '';

    // 允许不同协议的实例
    static protected $_instance = array();

    abstract function connect();

    abstract function get($prefix, $key);

    abstract function gets($prefix, $keys);

    abstract function set($prefix, $key, $value, $expire = 60);

    abstract function sets($prefix, $data, $expire = 60);

    abstract function delete($prefix, $key);

    abstract function deletes($prefix, $keys);

    abstract function inc($prefix, $key, $step = 1);

    abstract function dec($prefix, $key, $step = 1);

    abstract function close();

    // 可以继承Object类，但还得派生
    public static function getInstance($settings)
    {
        $set = @parse_url($settings);
        if (!isset(self::$_instance[$set['scheme']])) {
            $className = $set['scheme'];
            require_once "{$className}.class.php";
            self::$_instance[$set['scheme']] = new $className($settings);

            if ($settings !== '') {
                self::$_instance[$set['scheme']]->parseSettings($settings);
            }

            start_performance();
        }

        if (self::$_instance[$set['scheme']]->settings !== $settings) {
            self::$_instance[$set['scheme']]->close();
            self::$_instance[$set['scheme']]->parseSettings($settings);
        }

        return self::$_instance[$set['scheme']];
    }

    public function parseSettings($settings)
    {
        static $parsed = array();
        $this->settings = $settings;

        if (isset($parsed[$settings]) === false) {
            $this->server = $parsed[$settings]['server'] = substr($settings, 0, strrpos($settings, '/'));
            $this->prefix = $parsed[$settings]['prefix'] = substr($settings, strrpos($settings, '/') + 1);
        }
        else {
            $this->server = $parsed[$settings]['server'];
            $this->prefix = $parsed[$settings]['prefix'];
        }

        if (empty($this->prefix) === true) {
            throw new exception2('this cache class prefix is required.');
        }
    }
}
?>
