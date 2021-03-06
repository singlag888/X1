<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class lottery
{
    const CACHE_LIFETIME = 600;

//    static public function getItem($id)
//    {
//        $cacheKey = $id;
//        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
//            $sql = 'SELECT * FROM lottery WHERE lottery_id = ' . intval($id);
//            $result = $GLOBALS['db']->getRow($sql);
//            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_LIFETIME);
//        }
//
//        if ($result) {
//            $result['settings'] = unserialize($result['settings']);
//            $result['min_rebate_gaps'] = unserialize($result['min_rebate_gaps']);
//        }
//
//        return $result;
//    }

    static public function getItem($id)
    {
        if ($data = $GLOBALS['redis']->hGet('lotteryList', $id)) {
            $data = json_decode($data, true);

           // $id == 21 && log2file('lhc', 'redis:' . json_encode(unserialize($data['min_rebate_gaps']), JSON_UNESCAPED_UNICODE));
        } else {
            $sql = 'SELECT * FROM lottery WHERE lottery_id = ' . intval($id);
            $data = $GLOBALS['db']->getRow($sql);
            $GLOBALS['redis']->hSet('lotteryList', $id, json_encode($data, JSON_UNESCAPED_UNICODE));

            //$id == 21 && log2file('lhc', 'db:' . json_encode(unserialize($data['min_rebate_gaps']), JSON_UNESCAPED_UNICODE));
        }
        if ($data) {
            $data['settings'] = unserialize($data['settings']);
            $data['min_rebate_gaps'] = unserialize($data['min_rebate_gaps']);
        }

        return $data;
    }

    static public function getItems($lottery_type = 0, $status = 8)
    {
        $cacheKey = __FUNCTION__ . $lottery_type . $status;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM lottery WHERE 1';
            if ($lottery_type != 0 && is_numeric($lottery_type)) {
                $sql .= " AND lottery_type = " . intval($lottery_type);
            }
            if ($status != -1) {
                $sql .= " AND status = $status";
            }
            $result = $GLOBALS['db']->getAll($sql, array(),'lottery_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_LIFETIME);
        }
        foreach ($result as $k => $v) {
            $result[$k]['settings'] = unserialize($v['settings']);
        }

        return $result;
    }

    static public function getModesItemsByCond($cond = '', $field = '*' ,$orderBy = '')
    {
        $sql = "SELECT $field FROM lottery_modes WHERE 1 ".$cond.$orderBy;
        return $GLOBALS['db']->getAll($sql, array());
    }
    static public function getModesItemByCond($cond = '', $field = '*' ,$orderBy = '')
    {
        $sql = "SELECT $field FROM lottery_modes WHERE 1 ".$cond.$orderBy;
        return $GLOBALS['db']->getRow($sql, array());
    }

    static public function getItemsNew($files = '*', $lottery_type = 0, $status = 8)
    {
        $strFiles = is_array($files) ? implode(',', $files) : $files;
        $cacheKey = __FUNCTION__ . $lottery_type . $status . $strFiles;
        $data = $GLOBALS['redis']->get($cacheKey);
        if (!$data) {
            $sql = 'SELECT ' . $strFiles . ' FROM lottery WHERE 1';
            if ($lottery_type != 0 && is_numeric($lottery_type)) {
                $sql .= ' AND lottery_type = ' . intval($lottery_type);
            }
            if ($status != -1) {
                $sql .= ' AND status = ' . $status;
            }
            $data = $GLOBALS['db']->getAll($sql, array(), 'lottery_id');

            $GLOBALS['redis']->setex($cacheKey, 600, json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        if(!is_array($data)){
            $data = json_decode($data, true);
        }
        foreach ($data as $k => $v) {
            if(isset($v['settings'])){
                $data[$k]['settings'] = unserialize($v['settings']);
            }
        }

        return $data;
    }

    /**
     * 获取彩种列表
     * @param string $cond 条件
     * @param string $field 查询字段
     * @param string $orderBy 排序方式
     * @return array|null
     */
    static public function getAllItems($cond='status = 8',$field = '*',$orderBy = ' ORDER BY sort ')
    {
        $cacheKey = __FUNCTION__ ;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = "SELECT $field FROM lottery WHERE ".$cond.$orderBy;

            $result = $GLOBALS['db']->getAll($sql, array(),'lottery_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_LIFETIME);
        }
        foreach ($result as $k => $v) {
            if(isset($v['settings'])) {
                $result[$k]['settings'] = unserialize($v['settings']);
            }
        }

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }
        $noCacheKeys = array();
        $result = $GLOBALS['mc']->gets(__CLASS__, $ids, $noCacheKeys);
        if ($noCacheKeys) {
            $sql = 'SELECT * FROM lottery WHERE lottery_id IN(' . implode(',', $noCacheKeys) . ')';
            $noCacheResult = $GLOBALS['db']->getAll($sql, array(),'lottery_id');
            $GLOBALS['mc']->sets(__CLASS__, $noCacheResult, self::CACHE_LIFETIME);
            $result += $noCacheResult;
        }
        return $result;
    }

    static public function getRepresent()
    {
        $cacheKey = __FUNCTION__;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM lottery GROUP BY property_id';
            $result = $GLOBALS['db']->getAll($sql, array(),'property_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_LIFETIME);
        }

        return $result;
    }

}

?>