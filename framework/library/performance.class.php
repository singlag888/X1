<?php

class performance
{

    private $status = array();

    public function countMysql()
    {
        if (empty($this->status['MYSQL']['COUNT']) === true) {
            return 0;
        }
        else {
            return $this->status['MYSQL']['COUNT'];
        }
    }

    public function countMC()
    {
        if (empty($this->status['MEMCACHE']['COUNT']) === true) {
            return 0;
        }

        $result = array();
        foreach ($this->status['MEMCACHE']['COUNT'] AS $key => $count) {
            $result[] = $key . ':' . $count;
        }

        return implode('/', $result);
    }

    public function countXC()
    {
        if (isset($this->status['XCACHE']['COUNT']) === false) {
            return 0;
        }

        $result = array();
        foreach ($this->status['XCACHE']['COUNT'] AS $key => $count) {
            $result[] = $key . ':' . $count;
        }

        return implode('/', $result);
    }

    public function getDetail()
    {
        if (isset($this->status['DETAIL']) === false) {
            return array();
        }
        else {
            return $this->status['DETAIL'];
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function __set($key, $value)
    {
        $this->status[$key] = $value;
    }

    public function &__get($key)
    {
        return $this->status[$key];
    }

    public function __isset($key)
    {
        return isset($this->status[$key]);
    }

    public function __unset($key)
    {
        unset($this->status[$key]);
    }

}

?>