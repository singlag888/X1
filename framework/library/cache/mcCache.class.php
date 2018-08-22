<?php

/**
 * @package Light_Cache
 */
class mcCache extends cache
{

    private $pools = array();
    private $mcpool = NULL;
    public $connected = '';

    public function __construct($settings = '')
    {
        if (!extension_loaded('memcache')) {
            throw new exception2('The memcache extension must be loaded for using this backend !');
        }
    }

    public function connect($timeout = 1)
    {
        if (isset($this->pools[$this->server]) === true) {
            $this->mcpool = $this->pools[$this->server];
            $this->connected = $this->server;

            return true;
        }
        else {
            $set = @parse_url($this->settings);

            if (isset($set['port']) === false) {
                $set['port'] = '7380';
            }

            $this->mcpool = new Memcache;

            /*
              $this->mcpool->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
             */
            /* 这个不知道会不会引起之后读的错误，需要更多的测试
              $this->mcpool->setOption(Memcached::OPT_BUFFER_WRITES, true);
              $this->mcpool->setOption(Memcached::OPT_TCP_NODELAY, true);
              $this->mcpool->setOption(Memcached::OPT_NO_BLOCK, true);

             */

            if (@$this->mcpool->connect($set['host'], $set['port']) === true) {
                $this->pools[$this->server] = $this->mcpool;
                $this->connected = $this->server;

                return true;
            }

            return false;
        }
    }

    public function close()
    {
        $this->connected = '';
        $this->settins = '';
        $this->server = '';
        $this->prefix = '';

        return true;
    }

    public function set($prefix, $key, $value, $expire = 60)
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PROFILE']->MEMCACHE['COUNT']['SET']) === true) {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] ++;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['SET'] ++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> set ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['SET'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> set ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        $result = @$this->mcpool->set($this->prefix . '_' . $prefix . '_' . $key, $value, false, $expire);

        if ($result === NULL || $result === false) {
            return false;
        }
        else {
            return $result;
        }
    }

    public function sets($prefix, $data, $expire = 60)
    { 
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PROFILE']->MEMCACHE['COUNT']['SETS'])) {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['SETS'] += count($data);
            //$GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export(array_keys($data), true);
        }
        else {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['SETS'] = count($data);
            //$GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export(array_keys($data), true);
        }

        $result = array();
        $tmp = true;
        foreach ($data as $key => $value) {
            $tmp = @$this->mcpool->set($this->prefix . '_' . $prefix . '_' . $key, $value, false, $expire);
        }

        return $tmp;
    }

    public function add($prefix, $key, $value, $expire = 60)
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        return $this->mcpool->add($this->prefix . '_' . $prefix . '_' . $key, $value, false, $expire);
    }

    public function get($prefix, $key)
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PROFILE']->MEMCACHE['COUNT']['GET']) === true) {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] ++;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['GET'] ++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> get ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['GET'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> get ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        $result = $this->mcpool->get($this->prefix . '_' . $prefix . '_' . $key);

        if ($result === NULL || $result === false) {
            return false;
        }
        else {
            return $result;
        }
    }

    public function gets($prefix, $keys, &$noCacheKeys = NULL)
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PROFILE']->MEMCACHE['COUNT']['GETS'])) {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['GETS'] += count($keys);
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export($keys, true);
        }
        else {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['GETS'] = count($keys);
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export($keys, true);
        }

        $result = array();
        foreach ($keys as $key) {
            $tmp = $this->mcpool->get($this->prefix . '_' . $prefix . '_' . $key);
            if ($tmp !== false) {
                $result[$key] = $tmp;
            }
            else {
                if ($noCacheKeys !== NULL) {
                    $noCacheKeys[] = $key;
                }
            }
        }

        return $result;
    }

    public function delete($prefix, $key)
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PROFILE']->MEMCACHE['COUNT']['DELETE']) === true) {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] ++;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['DELETE'] ++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> delete ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['DELETE'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> delete ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        $result = $this->mcpool->delete($this->prefix . '_' . $prefix . '_' . $key);

        if ($result === NULL || $result === false) {
            return false;
        }
        else {
            return $result;
        }
    }

    public function deletes($prefix, $keys)
    {
        
    }

    public function inc($prefix, $key, $step = 1)
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PROFILE']->MEMCACHE['COUNT']['INC']) === true) {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] ++;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['INC'] ++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> inc ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['INC'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> inc ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        $result = $this->mcpool->increment($this->prefix . '_' . $prefix . '_' . $key, $step);

        if ($result === 0 || $result === NULL || $result === false) {
            return false;
        }
        else {
            return $result;
        }
    }

    public function dec($prefix, $key, $step = 1)
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        if (isset($GLOBALS['_PROFILE']->MEMCACHE['COUNT']['DEC']) === true) {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] ++;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['DEC'] ++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> dec ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->MEMCACHE['COUNT']['DEC'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'MEMCACHED-> dec ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        $result = $this->mcpool->decrement($this->prefix . '_' . $prefix . '_' . $key, $step);

        if ($result === NULL || $result === false) {
            return false;
        }
        else {
            return $result;
        }
    }

    public function flush()
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        return $this->mcpool->flush();
    }

    static public function getStats()
    {
        if ($this->connected !== $this->server) {
            if ($this->connect() === false) {
                return false;
            }
        }

        return $this->mcpool->getStats();
    }

}

?>