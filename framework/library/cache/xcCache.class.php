<?php

/**
 * @package Light_Cache
 */
class xcCache extends cache
{
    public function __construct($settings = '')
    {
        if (!extension_loaded('xcache')) {
            throw new exception2('The xcache extension must be loaded for using this backend !');
        }
    }

    public function connect($timeout = 1)
    {
        
    }

    public function close()
    {
        
    }

    public function set($prefix, $key, $value, $expire = 60)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['SET']) === true) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ']++;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['SET']++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> set ' . $this->prefix . '_'  . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['SET'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> set ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        return xcache_set($this->prefix . '_' . $prefix . '_' . $key, $value, $expire);
    }

    public function sets($prefix, $data, $expire = 60)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['SETS'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['SETS'] += count($data);
            //$GLOBALS['_PROFILE']->DETAIL[] = 'XCACHE-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export(array_keys($data), true);
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['SETS'] = count($data);
            //$GLOBALS['_PROFILE']->DETAIL[] = 'XCACHE-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export(array_keys($data), true);
        }

        $result = array();
        $tmp = true;
        foreach ($data as $key => $value) {
            $tmp = xcache_set($this->prefix . '_' . $prefix . '_' . $key, $value, $expire);
        }

        return $tmp;
    }

    //key不存在返回null
    public function get($prefix, $key)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['GET'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ']++;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['GET']++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> get ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['GET'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> get ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        $result = xcache_get($this->prefix . '_' . $prefix . '_' . $key);
        if ($result === NULL || $result === false) {
            return false;
        }
        else {
            return $result;
        }
    }

    public function gets($prefix, $keys, &$noCacheKeys = NULL)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['GETS'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['GETS'] += count($keys);
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHE-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export($keys, true);
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['GETS'] = count($keys);
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHE-> gets ' . $this->prefix . '_' . $prefix . '_' . var_export($keys, true);
        }

        $result = array();
        foreach ($keys as $key) {
            $tmp = xcache_get($this->prefix . '_' . $prefix . '_' . $key);
            if ($tmp !== NULL) {
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

    public function exists($prefix, $key)
    {
        return xcache_isset($this->prefix . '_' . $prefix . '_' . $key);
    }

    public function delete($prefix, $key)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETE'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ']++;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETE']++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> delete ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETE'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> delete ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        return xcache_unset($this->prefix . '_' . $prefix . '_' . $key);
    }
    
    public function deletes($prefix, $keys)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETES'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ']++;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETES']++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> deletes ' . $this->prefix . '_' . $prefix . '_' . var_export($keys, true);
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETES'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> deletes ' . $this->prefix . '_' . $prefix . '_' . var_export($keys, true);
        }

        return xcache_unset_by_prefix($this->prefix . '_' . $prefix);
    }

    public function deletesByPrefix($prefix)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETES'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ']++;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETES']++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> deletesByPrefix ' . $this->prefix . '_' . $prefix;
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DELETES'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> deletesByPrefix ' . $this->prefix . '_' . $prefix;
        }

        return xcache_unset_by_prefix($this->prefix . '_' . $prefix);
    }

    public function inc($prefix, $key, $step = 1)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['INC'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ']++;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['INC']++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> inc ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['INC'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> inc ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        return xcache_inc($this->prefix . '_' . $prefix . '_' . $key, $step);
    }

    public function dec($prefix, $key, $step = 1)
    {
        if (isset($GLOBALS['_PROFILE']->XCACHE['COUNT']['DEC'])) {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ']++;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DEC']++;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> dec ' . $this->prefix . '_' . $prefix . '_' . $key;
        }
        else {
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['REQ'] = 1;
            $GLOBALS['_PROFILE']->XCACHE['COUNT']['DEC'] = 1;
            $GLOBALS['_PROFILE']->DETAIL[] = 'XCACHED-> dec ' . $this->prefix . '_' . $prefix . '_' . $key;
        }

        return xcache_dec($this->prefix . '_' . $prefix . '_' . $key, $step);
    }

    public function flush()
    {
        
    }

    static public function getStats()
    {
        
    }

}

?>