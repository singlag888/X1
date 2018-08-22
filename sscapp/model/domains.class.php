<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class domains
{

    const CACHE_TIME = 7200;

    static public function getItem($id, $status = 1)
    {
        if(($result = $GLOBALS['mc']->get(__CLASS__, $id)) === false){
            if (is_numeric($id)) {
                $sql = 'SELECT * FROM domains WHERE domain_id = ' . intval($id);
            }
            elseif (is_string($id)) {
                $sql = "SELECT * FROM domains WHERE name like {$id}%";
            }
            else {
                throw new exception2('参数无效');
            }
            if ($status != -1) {
                $sql .= " AND status = $status";
            }

            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $id, $result, 86400);
        }

        return $result;
    }

    static public function getTopByDomain($id, $status = 1)
    {
        if(($result = $GLOBALS['mc']->get(__CLASS__, $id)) === false){
            if (is_numeric($id)) {
                $sql = 'SELECT ud.top_id FROM domains as d LEFT JOIN user_domains as ud ON ud.domain_id=d.domain_id WHERE d.domain_id = ' . intval($id);
            }
            elseif (is_string($id)) {
                $sql = "SELECT ud.top_id FROM domains as d LEFT JOIN user_domains as ud ON ud.domain_id=d.domain_id WHERE d.name like '".$id."%'";
            }
            else {
                throw new exception2('参数无效');
            }
            if ($status != -1) {
                $sql .= " AND d.status = $status";
            }

            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $id, $result, 86400);
        }
        return !empty($result['top_id'])?$result['top_id']:false;
    }
    static public function getItems($status = 1)
    {
        $sql = 'SELECT * FROM domains WHERE 1';
        if ($status !== -1) {
            $sql .= " AND status = " . intval($status);
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }
    static public function getItemByTopId($top_id,$status = 1)
    {
        $sql = 'SELECT a.name FROM domains AS a LEFT JOIN user_domains AS b ON b.domain_id = a.domain_id WHERE b.top_id='.$top_id.' AND a.status='.(int)$status;

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsWithKey($status = 1)
    {
        $sql = 'SELECT * FROM domains WHERE 1';
        if ($status !== -1) {
            $sql .= " AND status = " . intval($status);
        }
        $result = $GLOBALS['db']->getAll($sql, array(),'domain_id');

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }
        if (empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM domains WHERE domain_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'domain_id');
    }

    static public function getItemsByName($name)
    {
        $sql = 'SELECT * FROM domains WHERE name ="' . $name . '"';

        return $GLOBALS['db']->getAll($sql, array(),'name');
    }

    static public function getCanBoundDomains()
    {
        $sql = "SELECT DISTINCT(d.`name`),d.type,d.domain_id,d.status FROM domains d LEFT JOIN user_domains ud using(domain_id) where ud.domain_id is NULL";

        return $GLOBALS['db']->getAll($sql, array(),'domain_id');
    }

    //获取域名绑定的用户
    static public function getDomainUser($name)
    {
        if (!is_string($name)) {
            throw new exception2('参数无效');
        }

        $sql = "SELECT u.type,u.user_id FROM domains d  LEFT JOIN user_domains ud ON d.domain_id = ud.domain_id LEFT JOIN users u ON u.user_id = ud.top_id WHERE d.name = '{$name}' LIMIT 1";
        $res = $GLOBALS['db']->getRow($sql);
        if(empty($res) || $res['user_id'] === NULL || $res['type'] === NULL){
            $res = array();
        }

        return $res;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('domains', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('domains',$data,array('domain_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM domains WHERE domain_id=" . intval($id) . ' LIMIT 1';

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * @param type $top_id
     * @param type $domain_id
     * @param type $isCache 如果为1表示强制重新取
     * @return type
     */
    static public function getUserDomains($top_id = 0, $domain_id = 0, $isCache = 0)
    {
        // $cacheKey = !is_array($domain_id) ? __FUNCTION__ . $top_id . '_' . $domain_id : __FUNCTION__ . $top_id . '_' . implode('-', $domain_id) ;
        // if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false || $isCache) {
            $sql = 'SELECT a.*,b.name FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE 1';
            $paramArr = array();
            if ($top_id != 0) {
                $sql .= " AND a.top_id = $top_id";
            }
            if ($domain_id != 0) {
                if (is_array($domain_id)) {
                    $sql .= " AND a.domain_id IN (" . implode(',', $domain_id) . ")";
                }
                else {
                    $sql .= " AND a.domain_id = " . intval($domain_id);
                }
            }
            $sql .= " AND b.status = 1";
            $result = array();
            $tmp = $GLOBALS['db']->getAll($sql);
            foreach ($tmp as $v) {
                $result[$v['top_id']][$v['domain_id']] = $v;
            }
        //     $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        // }

        return $result;
    }

    //应增加重复性判断(top_id+domain_id)，避免mysql出现duplicate key
    static public function addUserDomain($data)
    {
        if (!is_array($data) || empty($data['top_id']) || empty($data['username']) || empty($data['domain_id'])) {
            throw new exception2('参数无效');
        }

        $sql = "SELECT * FROM user_domains WHERE top_id = {$data['top_id']} AND domain_id = {$data['domain_id']}";
        if (!$GLOBALS['db']->getRow($sql)) {
            if (!$GLOBALS['db']->insert('user_domains', $data)) {
                throw new exception2('db error');
            }
        }

        return true;
    }

    static public function deleteUserDomain($domain_id, $top_id = 0)
    {
        $sql = "DELETE FROM user_domains WHERE 1 AND domain_id = " . intval($domain_id);
        if ($top_id > 0) {
            $sql .= " AND top_id = " . intval($top_id);
        }

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}

?>