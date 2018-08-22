<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 号码表示方法：号区之间用,分隔，同区内号码之间一般不用分隔符，若可能有2位表示的，用_分隔（如和值玩法或sd11y）
 * 注单表示方法（改进版）：String codes = "46:1,2,3,4,5|6,7,8,9,0|1,2,3,4,5#43:1,2,3|6,7,0";
 */
class methods
{

    //三星包点注数对应表
    static $SXBD = array(
        0 => 1, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 7, 7 => 8, 8 => 10, 9 => 12,
        10 => 13, 11 => 14, 12 => 15, 13 => 15, 14 => 15, 15 => 15, 16 => 14, 17 => 13, 18 => 12,
        19 => 10, 20 => 8, 21 => 7, 22 => 5, 23 => 4, 24 => 3, 25 => 2, 26 => 1, 27 => 1,
    );
    //二星包点注数对应表
    static $EXBD = array(
        0 => 1, 1 => 1, 2 => 2, 3 => 2, 4 => 3, 5 => 3, 6 => 4, 7 => 4, 8 => 5, 9 => 5,
        10 => 5, 11 => 4, 12 => 4, 13 => 3, 14 => 3, 15 => 2, 16 => 2, 17 => 1, 18 => 1,
    );
    //三星和值注数对应表
    static $SXHZ = array(
        0 => 1, 1 => 3, 2 => 6, 3 => 10, 4 => 15, 5 => 21, 6 => 28, 7 => 36, 8 => 45, 9 => 55, 10 => 63, 11 => 69, 12 => 73, 13 => 75,
        14 => 75, 15 => 73, 16 => 69, 17 => 63, 18 => 55, 19 => 45, 20 => 36, 21 => 28, 22 => 21, 23 => 15, 24 => 10, 25 => 6, 26 => 3, 27 => 1,
    );
    //二星和值注数对应表
    static $EXHZ = array(
        0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 7, 7 => 8, 8 => 9, 9 => 10,
        10 => 9, 11 => 8, 12 => 7, 13 => 6, 14 => 5, 15 => 4, 16 => 3, 17 => 2, 18 => 1,
    );
    //三星组选和值注数对应表(低频特有)
    static $SXZXHZ = array(
        1 => 1, 2 => 2, 3 => 2, 4 => 4, 5 => 5, 6 => 6, 7 => 8, 8 => 10, 9 => 11, 10 => 13, 11 => 14, 12 => 14, 13 => 15,
        14 => 15, 15 => 14, 16 => 14, 17 => 13, 18 => 11, 19 => 10, 20 => 8, 21 => 6, 22 => 5, 23 => 4, 24 => 2, 25 => 2, 26 => 1,
    );
    //山东定单双 0单5双:750.0000元 (1注) 5单0双:125.0000元 (6注)1单4双:25.0000元 (30注)4单1双:10.0000元 (75注)2单3双:5.0000元 (150注)3单2双:3.7000元 (200注)
    static $SDDDS = array('0单5双', '5单0双', '1单4双', '4单1双', '2单3双', '3单2双');
    //快乐扑克
    static $pokerNumMaps = array(
        1 => 'A', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => 'T', 11 => 'J', 12 => 'Q', 13 => 'K',
    );
    static $pokerSuitMaps = array(
        's' => '黑桃', 'h' => '红桃', 'c' => '梅花', 'd' => '方块',
    );
    static $lhcMaps = array(
        1 => array('color' => '红', 'zodiac' => '鸡', 'tail' => 1, 'bigSmall' => '小', 'oddEven' => '单'),
        2 => array('color' => '红', 'zodiac' => '猴', 'tail' => 2, 'bigSmall' => '小', 'oddEven' => '双'),
        3 => array('color' => '蓝', 'zodiac' => '羊', 'tail' => 3, 'bigSmall' => '小', 'oddEven' => '单'),
        4 => array('color' => '蓝', 'zodiac' => '马', 'tail' => 4, 'bigSmall' => '小', 'oddEven' => '双'),
        5 => array('color' => '绿', 'zodiac' => '蛇', 'tail' => 5, 'bigSmall' => '小', 'oddEven' => '单'),
        6 => array('color' => '绿', 'zodiac' => '龙', 'tail' => 6, 'bigSmall' => '小', 'oddEven' => '双'),
        7 => array('color' => '红', 'zodiac' => '兔', 'tail' => 7, 'bigSmall' => '小', 'oddEven' => '单'),
        8 => array('color' => '红', 'zodiac' => '虎', 'tail' => 8, 'bigSmall' => '小', 'oddEven' => '双'),
        9 => array('color' => '蓝', 'zodiac' => '牛', 'tail' => 9, 'bigSmall' => '小', 'oddEven' => '单'),
        10 => array('color' => '蓝', 'zodiac' => '鼠', 'tail' => 0, 'bigSmall' => '小', 'oddEven' => '双'),
        11 => array('color' => '绿', 'zodiac' => '猪', 'tail' => 1, 'bigSmall' => '小', 'oddEven' => '单'),
        12 => array('color' => '红', 'zodiac' => '狗', 'tail' => 2, 'bigSmall' => '小', 'oddEven' => '双'),
        13 => array('color' => '红', 'zodiac' => '鸡', 'tail' => 3, 'bigSmall' => '小', 'oddEven' => '单'),
        14 => array('color' => '蓝', 'zodiac' => '猴', 'tail' => 4, 'bigSmall' => '小', 'oddEven' => '双'),
        15 => array('color' => '蓝', 'zodiac' => '羊', 'tail' => 5, 'bigSmall' => '小', 'oddEven' => '单'),
        16 => array('color' => '绿', 'zodiac' => '马', 'tail' => 6, 'bigSmall' => '小', 'oddEven' => '双'),
        17 => array('color' => '绿', 'zodiac' => '蛇', 'tail' => 7, 'bigSmall' => '小', 'oddEven' => '单'),
        18 => array('color' => '红', 'zodiac' => '龙', 'tail' => 8, 'bigSmall' => '小', 'oddEven' => '双'),
        19 => array('color' => '红', 'zodiac' => '兔', 'tail' => 9, 'bigSmall' => '小', 'oddEven' => '单'),
        20 => array('color' => '蓝', 'zodiac' => '虎', 'tail' => 0, 'bigSmall' => '小', 'oddEven' => '双'),
        21 => array('color' => '绿', 'zodiac' => '牛', 'tail' => 1, 'bigSmall' => '小', 'oddEven' => '单'),
        22 => array('color' => '绿', 'zodiac' => '鼠', 'tail' => 2, 'bigSmall' => '小', 'oddEven' => '双'),
        23 => array('color' => '红', 'zodiac' => '猪', 'tail' => 3, 'bigSmall' => '小', 'oddEven' => '单'),
        24 => array('color' => '红', 'zodiac' => '狗', 'tail' => 4, 'bigSmall' => '小', 'oddEven' => '双'),
        25 => array('color' => '蓝', 'zodiac' => '鸡', 'tail' => 5, 'bigSmall' => '大', 'oddEven' => '单'),
        26 => array('color' => '蓝', 'zodiac' => '猴', 'tail' => 6, 'bigSmall' => '大', 'oddEven' => '双'),
        27 => array('color' => '绿', 'zodiac' => '羊', 'tail' => 7, 'bigSmall' => '大', 'oddEven' => '单'),
        28 => array('color' => '绿', 'zodiac' => '马', 'tail' => 8, 'bigSmall' => '大', 'oddEven' => '双'),
        29 => array('color' => '红', 'zodiac' => '蛇', 'tail' => 9, 'bigSmall' => '大', 'oddEven' => '单'),
        30 => array('color' => '红', 'zodiac' => '龙', 'tail' => 0, 'bigSmall' => '大', 'oddEven' => '双'),
        31 => array('color' => '蓝', 'zodiac' => '兔', 'tail' => 1, 'bigSmall' => '大', 'oddEven' => '单'),
        32 => array('color' => '绿', 'zodiac' => '虎', 'tail' => 2, 'bigSmall' => '大', 'oddEven' => '双'),
        33 => array('color' => '绿', 'zodiac' => '牛', 'tail' => 3, 'bigSmall' => '大', 'oddEven' => '单'),
        34 => array('color' => '红', 'zodiac' => '鼠', 'tail' => 4, 'bigSmall' => '大', 'oddEven' => '双'),
        35 => array('color' => '红', 'zodiac' => '猪', 'tail' => 5, 'bigSmall' => '大', 'oddEven' => '单'),
        36 => array('color' => '蓝', 'zodiac' => '狗', 'tail' => 6, 'bigSmall' => '大', 'oddEven' => '双'),
        37 => array('color' => '蓝', 'zodiac' => '鸡', 'tail' => 7, 'bigSmall' => '大', 'oddEven' => '单'),
        38 => array('color' => '绿', 'zodiac' => '猴', 'tail' => 8, 'bigSmall' => '大', 'oddEven' => '双'),
        39 => array('color' => '绿', 'zodiac' => '羊', 'tail' => 9, 'bigSmall' => '大', 'oddEven' => '单'),
        40 => array('color' => '红', 'zodiac' => '马', 'tail' => 0, 'bigSmall' => '大', 'oddEven' => '双'),
        41 => array('color' => '蓝', 'zodiac' => '蛇', 'tail' => 1, 'bigSmall' => '大', 'oddEven' => '单'),
        42 => array('color' => '蓝', 'zodiac' => '龙', 'tail' => 2, 'bigSmall' => '大', 'oddEven' => '双'),
        43 => array('color' => '绿', 'zodiac' => '兔', 'tail' => 3, 'bigSmall' => '大', 'oddEven' => '单'),
        44 => array('color' => '绿', 'zodiac' => '虎', 'tail' => 4, 'bigSmall' => '大', 'oddEven' => '双'),
        45 => array('color' => '红', 'zodiac' => '牛', 'tail' => 5, 'bigSmall' => '大', 'oddEven' => '单'),
        46 => array('color' => '红', 'zodiac' => '鼠', 'tail' => 6, 'bigSmall' => '大', 'oddEven' => '双'),
        47 => array('color' => '蓝', 'zodiac' => '猪', 'tail' => 7, 'bigSmall' => '大', 'oddEven' => '单'),
        48 => array('color' => '蓝', 'zodiac' => '狗', 'tail' => 8, 'bigSmall' => '大', 'oddEven' => '双'),
        49 => array('color' => '绿', 'zodiac' => '鸡', 'tail' => 9, 'bigSmall' => '大', 'oddEven' => '单'),
    );

    const BIG_ZODIAC = '鸡';//本命肖

    static $prizes = array();   //缓存基本奖金组

    static $helpData = array();   //缓存开奖号码形态，避免多次分析同一号码

    const CACHE_TIME = 7200;

    static public function getItem($id, $status = 8)
    {
        $cacheKey = $id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM methods WHERE method_id = ' . intval($id);
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }
        if ($result) {
            if (!$status === null && $result['status'] != $status) {

                return null;
            }
            $result['expands'] = unserialize($result['expands']);
            $result['field_def'] && ($result['field_def'] = unserialize($result['field_def'])) || ($result['field_def'] = array());
        }

        return $result;
    }

    static public function getItems($lottery_id = 0, $mg_id = 0, $status = -1, $style = 0, $is_lock = -1)
    {
        $cacheKey = __FUNCTION__ . $lottery_id . '_' . $mg_id . '_' . $status . '_' . $is_lock;
        //if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {

            $sql = 'SELECT a.*,b.name as mg_name FROM methods a LEFT JOIN method_groups b ON a.mg_id=b.mg_id WHERE 1';
            if ($lottery_id != 0) {
                $sql .= " AND a.lottery_id = " . intval($lottery_id);
            }
            if ($mg_id != 0) {
                if (is_array($mg_id)) {
                    $sql .= " AND a.mg_id IN(" . implode(',', $mg_id) . ")";
                } else {
                    $sql .= " AND a.mg_id = " . intval($mg_id);
                }
            }
            if ($is_lock != -1) {
                $sql .= " AND a.is_lock = " . intval($is_lock);
            }
            if ($status != -1) {
                $sql .= " AND a.status = " . intval($status);
            }
            $sql .= ' ORDER BY a.sort ASC';
            $result = $GLOBALS['db']->getAll($sql, array(), 'method_id');
            foreach ($result as $k => $v) {
                $result[$k]['expands'] = unserialize($result[$k]['expands']);
                $result[$k]['field_def'] && ($result[$k]['field_def'] = unserialize($result[$k]['field_def'])) || ($result[$k]['field_def'] = array());
            }
            //$GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
       // }

        if ($style == 1) {  //单个彩种的二级联动
            $tmp = array();
            foreach ($result as $k => $v) {
                $tmp[$v['mg_id']][] = $v;
            }
            $result = $tmp;
        } elseif ($style == 2) {  //所有彩种的三级联动
            $tmp = array();
            foreach ($result as $k => $v) {
                if (!isset($tmp[$v['lottery_id']][$v['mg_id']])) {
                    $tmp[$v['lottery_id']][$v['mg_id']] = array('mg_id' => $v['mg_id'], 'mg_name' => $v['mg_name']);
                }
                $tmp[$v['lottery_id']][$v['mg_id']]['childs'][] = $v;
            }
            $result = $tmp;
        }

        return $result;
    }

    static public function getItemsNew($fields = '*',$lottery_id = 0, $mg_id = 0, $status = -1, $is_lock = -1)
    {
        $strFiles = is_array($fields) ? implode(',', $fields) : $fields;
        $cacheKey = __FUNCTION__ . $lottery_id . $strFiles;
        if (($result = $GLOBALS['redis']->get(__CLASS__, $cacheKey)) === false) {

            $sql = 'SELECT '. $strFiles . ' FROM methods WHERE 1';
            if ($lottery_id != 0) {
                $sql .= " AND lottery_id = " . intval($lottery_id);
            }
            if ($mg_id != 0) {
                if (is_array($mg_id)) {
                    $sql .= " AND mg_id IN(" . implode(',', $mg_id) . ")";
                } else {
                    $sql .= " AND mg_id = " . intval($mg_id);
                }
            }
            if ($is_lock != -1) {
                $sql .= " AND is_lock = " . intval($is_lock);
            }
            if ($status != -1) {
                $sql .= " AND status = " . intval($status);
            }
            $sql .= ' ORDER BY sort ASC';
            $result = $GLOBALS['db']->getAll($sql, array(), 'method_id');
            foreach ($result as $k => $v) {
                if(isset($result[$k]['expands'])){
                    $result[$k]['expands'] = unserialize($result[$k]['expands']);
                }

                isset($result[$k]['field_def']) && ($result[$k]['field_def'] = unserialize($result[$k]['field_def'])) || ($result[$k]['field_def'] = array());
            }

            $GLOBALS['redis']->setex($cacheKey, 600, json_encode($result, JSON_UNESCAPED_UNICODE));
       }

       if(!is_array($result)){
            $result = json_decode($result, true);
        }

        return $result;
    }

    static public function getPlayMethods($lottery_id = 0, $mg_id = 0, $group_tag = 0)
    {
        $cacheKey = __FUNCTION__ . $lottery_id . '_' . $mg_id . '_' . $group_tag;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT a.method_id,a.lottery_id,a.name,a.cname,a.description,a.mg_id,b.name as mg_name,a.field_def,a.can_input,a.levels, a.method_property FROM methods a LEFT JOIN method_groups b ON a.mg_id=b.mg_id WHERE a.status = 8';
            if ($lottery_id != 0) {
                $sql .= " AND a.lottery_id = " . intval($lottery_id);
            }
            if ($mg_id != 0) {
                if (is_array($mg_id)) {
                    $sql .= " AND a.mg_id IN(" . implode(',', $mg_id) . ")";
                } else {
                    $sql .= " AND a.mg_id = " . intval($mg_id);
                }
            }

            if ($group_tag > 0) {
                $sql .= ' AND (b.group_tag&' . $group_tag . ' > 0 OR  b.group_tag = 0)';
            }

            $sql .= ' ORDER BY b.sort ASC, a.sort ASC';
            $result = $GLOBALS['db']->getAll($sql, array(), 'method_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }
        foreach ($result as $k => $v) {
            $result[$k]['field_def'] && ($result[$k]['field_def'] = unserialize($result[$k]['field_def'])) || ($result[$k]['field_def'] = array());
        }

        $tmp = array();
        foreach ($result as $k => $v) {
            if (!isset($tmp[$v['mg_id']])) {
                $tmp[$v['mg_id']] = array('mg_id' => $v['mg_id'], 'mg_name' => $v['mg_name'], 'lottery_id' => $v['lottery_id']);
            }
            $tmp[$v['mg_id']]['childs'][] = $v;
        }
        $result = array_values($tmp);

        return $result;
    }

    /**
     * 150401  得到孩子列表
     * @param type $ids
     * @param type $status
     * @return array
     */
    static public function getItemsById($ids, $status = 8)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $noCacheKeys = array();
        $result = $GLOBALS['mc']->gets(__CLASS__, $ids, $noCacheKeys);
        /************************ snow ********************************************/
        //>>去掉值为空的
        foreach ($noCacheKeys as $key => $val) {
            if (empty($val)) {
                unset($noCacheKeys[$key]);
            }
        }
        /************************ snow ********************************************/
        if ($noCacheKeys) {

            $sql = 'SELECT * FROM methods WHERE method_id IN(' . implode(',', $noCacheKeys) . ')';
            $noCacheResult = $GLOBALS['db']->getAll($sql, array(), 'method_id');
            $GLOBALS['mc']->sets(__CLASS__, $noCacheResult, self::CACHE_TIME);
            $result += $noCacheResult;
        }

        $output = array();
        if ($result) {
            foreach ($result as $method) {
                if (!$status === null && $method['status'] != $status) {
                    continue;
                }
                $method['expands'] = unserialize($method['expands']);
                $method['field_def'] && ($method['field_def'] = unserialize($method['field_def'])) || ($method['field_def'] = array());
                $output[$method['method_id']] = $method;
            }
        }

        return $output;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('methods', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $tmp = array();
        foreach ($data as $k => $v) {
            $tmp[] = "`$k`='" . $v . "'";
        }
        $sql = "UPDATE methods SET " . implode(',', $tmp) . " WHERE method_id=$id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function deleteItem($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $sql = "DELETE FROM methods WHERE method_id = $id";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    //玩法组
    static public function getGroup($id)
    {
        $cacheKey = 'group_' . $id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM method_groups WHERE mg_id = ' . intval($id);
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        return $result;
    }

    static public function getGroups($lottery_id = -1)
    {
        $cacheKey = __FUNCTION__ . $lottery_id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM method_groups WHERE 1';
            if ($lottery_id != -1) {
                if (is_array($lottery_id)) {
                    $sql .= " AND lottery_id IN(" . implode(',', $lottery_id) . ")";
                } else {
                    $sql .= " AND lottery_id = " . intval($lottery_id);
                }
            }
            $sql .= ' ORDER BY sort ASC';
            $result = $GLOBALS['db']->getAll($sql, array(), 'mg_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        return $result;
    }

    static public function addGroup($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('method_groups', $data);
    }

    static public function updateGroup($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('method_groups', $data, array('mg_id' => $id));
    }

    /**
     * APP 获取所有一码不定位玩法id数组
     * @return array
     */
    static public function getAllBDWIds()
    {
        $cacheKey = __FUNCTION__ . '_';
        if (($BDW_method_ids = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            //所有一码不定位玩法缩写数组
            $BDW_method_names = array(
                'YMBDW',        //后三一码不定位
                'QSYMBDW',      //前三一码不定位
                'ZSYMBDW',      //中三一码不定位
                'SXYMBDW',      //四星一码不定位
                'WXYMBDW',      //五星一码不定位
            );

            $sql = "SELECT method_id from methods WHERE name IN ( '" . implode("','", $BDW_method_names) . "' )";
            $result = $GLOBALS['db']->getAll($sql, array(), 'method_id');
            $BDW_method_ids = array_keys($result);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $BDW_method_ids, self::CACHE_TIME);
            return $BDW_method_ids;
        }

        return $BDW_method_ids;
    }

    /**
     * 以下是数字分析部分
     */
    /**
     * 分析开奖号码的形态
     * @param type $code
     * @return array
     */
    static public function analyzeDigital($code)
    {
        if (!preg_match('`^\d{5}$`', $code)) {
            return false;
        }

        $result = array();
        //分析三星组态
        $parts = str_split(substr($code, 2, 3));
        $tmp = array_unique($parts);
        if (count($tmp) == 3) {
            $result['SX'] = '组六';
        } elseif (count($tmp) == 2) {
            $result['SX'] = '组三';
        } else {
            $result['SX'] = '豹子';
        }

        //三星和值
        $result['SXHZ'] = array_sum($parts);

        //三星大小单双 12345
        $nums = array();
        $nums[] = ($code{2} % 2 == 1 ? '3' : '4') . ($code{2} < 5 ? '2' : '1');
        $nums[] = ($code{3} % 2 == 1 ? '3' : '4') . ($code{3} < 5 ? '2' : '1');
        $nums[] = ($code{4} % 2 == 1 ? '3' : '4') . ($code{4} < 5 ? '2' : '1');
        $result['SXDXDS'] = methods::expand($nums);
        $result['SXDXDS'] = str_replace(array(1, 2, 3, 4), array('大', '小', '单', '双'), $result['SXDXDS']);
        //二星大小单双
        $nums = array();
        $nums[] = ($code{3} % 2 == 1 ? '3' : '4') . ($code{3} < 5 ? '2' : '1');
        $nums[] = ($code{4} % 2 == 1 ? '3' : '4') . ($code{4} < 5 ? '2' : '1');
        $result['EXDXDS'] = methods::expand($nums);
        $result['EXDXDS'] = str_replace(array(1, 2, 3, 4), array('大', '小', '单', '双'), $result['EXDXDS']);
        //前二大小单双
        $nums = array();
        $nums[] = ($code{0} % 2 == 1 ? '3' : '4') . ($code{0} < 5 ? '2' : '1');
        $nums[] = ($code{1} % 2 == 1 ? '3' : '4') . ($code{1} < 5 ? '2' : '1');
        $result['QEDXDS'] = methods::expand($nums);
        $result['QEDXDS'] = str_replace(array(1, 2, 3, 4), array('大', '小', '单', '双'), $result['QEDXDS']);

        //二星和值
        array_shift($parts);
        $tmp = array_unique($parts);
        if (count($tmp) == 2) {
            $result['EX'] = '非对子';
        } else {
            $result['EX'] = '对子';
        }
        $result['EXHZ'] = array_sum($parts);

        //分析前三组态
        $parts = str_split(substr($code, 0, 3));
        $tmp = array_unique($parts);
        if (count($tmp) == 3) {
            $result['QS'] = '组六';
        } elseif (count($tmp) == 2) {
            $result['QS'] = '组三';
        } else {
            $result['QS'] = '豹子';
        }

        //前三和值
        $result['QSHZ'] = array_sum($parts);

        //前二和值
        array_pop($parts);
        $tmp = array_unique($parts);
        if (count($tmp) == 2) {
            $result['QE'] = '非对子';
        } else {
            $result['QE'] = '对子';
        }
        $result['QEHZ'] = array_sum($parts);

        // 分析中三
        $parts = str_split(substr($code, 1, 3));
        $tmp = array_unique($parts);
        if (count($tmp) == 3) {
            $result['ZS'] = '组六';
        } elseif (count($tmp) == 2) {
            $result['ZS'] = '组三';
        } else {
            $result['ZS'] = '豹子';
        }

        //中三和值
        $result['ZSHZ'] = array_sum($parts);

        return $result;
    }

    //分析低频3D号码形态 按后三处理
    static public function analyzeLow3D($code)
    {
        if (!preg_match('`^\d{3}$`', $code)) {
            return false;
        }

        $result = array();
        //分析三星组态
        $parts = str_split($code);
        $tmp = array_unique($parts);
        if (count($tmp) == 3) {
            $result['SX'] = '组六';
        } elseif (count($tmp) == 2) {
            $result['SX'] = '组三';
        } else {
            $result['SX'] = '豹子';
        }

        //三星和值
        $result['SXHZ'] = array_sum($parts);

        //三星大小单双
        $nums = array();
        $nums[] = ($parts[0] % 2 == 1 ? '3' : '4') . ($parts[0] < 5 ? '2' : '1');
        $nums[] = ($parts[1] % 2 == 1 ? '3' : '4') . ($parts[1] < 5 ? '2' : '1');
        $nums[] = ($parts[2] % 2 == 1 ? '3' : '4') . ($parts[2] < 5 ? '2' : '1');
        $result['SXDXDS'] = methods::expand($nums);
        $result['SXDXDS'] = str_replace(array(1, 2, 3, 4), array('大', '小', '单', '双'), $result['SXDXDS']);
        //二星大小单双
        $nums = array();
        $nums[] = ($parts[1] % 2 == 1 ? '3' : '4') . ($parts[1] < 5 ? '2' : '1');
        $nums[] = ($parts[2] % 2 == 1 ? '3' : '4') . ($parts[2] < 5 ? '2' : '1');
        $result['EXDXDS'] = methods::expand($nums);
        $result['EXDXDS'] = str_replace(array(1, 2, 3, 4), array('大', '小', '单', '双'), $result['EXDXDS']);
        //前二大小单双
        $nums = array();
        $nums[] = ($parts[0] % 2 == 1 ? '3' : '4') . ($parts[0] < 5 ? '2' : '1');
        $nums[] = ($parts[1] % 2 == 1 ? '3' : '4') . ($parts[1] < 5 ? '2' : '1');
        $result['QEDXDS'] = methods::expand($nums);
        $result['QEDXDS'] = str_replace(array(1, 2, 3, 4), array('大', '小', '单', '双'), $result['QEDXDS']);

        //二星和值
        array_shift($parts);
        $tmp = array_unique($parts);
        if (count($tmp) == 2) {
            $result['EX'] = '非对子';
        } else {
            $result['EX'] = '对子';
        }
        $result['EXHZ'] = array_sum($parts);

        return $result;
    }

    //字符串排序
    static public function strOrder($str = '', $orderBy = 'ASC')
    {
        if ($str == '' || !isset($str{1})) {
            return $str;
        }
        $parts = str_split($str);
        if ($orderBy == 'DESC') {
            rsort($parts);
        } else {
            sort($parts);
        }
        return implode('', $parts);
    }

    /**
     * 计算阶乘
     * @param integer $n
     * @return integer
     */
    static public function factorial($n)
    {
        if ($n <= 1) {
            return 1;
        } else {
            return $n * methods::factorial($n - 1);
        }
    }

    /**
     * 邻位对换法,求一个数组的全排列
     * @param type $nums 原数组 如array('1','2','3')
     * @return type 按排列展开后的全排列 array('123','132','213','231','321','312')
     */
    static public function getAllP($nums)
    {
        $out = array();
        $out [] = $nums; //添加第一个组合
        $count = count($nums);
        $keys = array_keys($nums);
        $i = 1;
        while ($i < $count) {
            if ($keys [$i] > 0) {
                $keys [$i]--;
                $j = 0;
                if ($i % 2 == 1)
                    $j = $keys [$i];
                //互换 $i $j位置 形成不同的  序列
                $tmp = $nums [$j];
                $nums [$j] = $nums [$i];
                $nums [$i] = $tmp;
                $i = 1;
                $out [] = $nums;
            } elseif ($keys [$i] == 0) {
                $keys [$i] = $i;
                $i++;
            }
        }
        return $out;
    }

    /**
     * 待完成 通用方法：任意的n,m得到排列结果 n>=m P5_3=C5_3*3!
     * @param array $array 原数组 如array('1','2','3')
     * @param integer $base <= 数组长度 如2
     * @return array $result 按排列展开后的数组array('12','13','21','31','23','32')
     */
    static public function P($nums, $base, $delimiter = '')
    {
        $cArray = methods::C($nums, $base, ','); // 先取出所有组合

        $out = array(); // 输出
        foreach ($cArray as $c) {
            $ps = methods::getAllP(explode(',', $c));

            //排列输出为数组
            //$out = array_merge($out, $ps);
            //排列输出为字符串
            foreach ($ps as $p) {
                $out[] = implode($delimiter, $p);
            }
        }
        return $out;
    }

    /**
     * 待完成 通用方法：任意的n,m得到组合结果 n>m 如C(array('a','b','c','d','e'),3)
     * * 1.初始化一个字符串：11100;--------1的个数表示需要选出的组合
     * 2.将1依次向后移动造成不同的01字符串，构成不同的组合，1全部移动到最后面，移动完成：00111.
     * 3.移动方法：每次遇到第一个10字符串时，将其变成01,在此子字符串前面的字符串进行倒序排列,后面的不变：形成一个不同的组合.
     *            如：11100->11010->10110->01110->11001->10101->01101->10011->01011->00111
     *            一共形成十个不同的组合:每一个01字符串对应一个组合---如11100对应组合01 02 03;01101对应组合02 03 05
     *
     * @param array $array 原数组 如array('1','2','3')
     * @param integer $base <= 数组长度 如2
     * @param string $delimiter 分隔符，默认无
     * @return array $result 按组合展开后的数组array('12','13','23')
     */
    static public function C($array, $base, $delimiter = '')
    {
        if (!is_array($array) || count($array) < $base) {
            return array();
        } elseif (count($array) == $base) {   //相同只能一种可能，直接输出
            return array(implode($delimiter, $array));
        }

        if ($base == 1) {
            return $array;
        }

        $result = $resultIndex = array();
        $initStr = $teminalStr = '';
        for ($i = 0; $i < $base; $i++) {
            $teminalStr .= '1';
        }
        $initStr = $teminalStr;
        for ($i = $base; $i < count($array); $i++) {
            $initStr .= '0';
        }
        $resultIndex[] = $initStr;

        while (substr($initStr, -$base) != $teminalStr) {
            $parts = explode('10', $initStr, 2);
            $initStr = methods::strOrder($parts[0], 'DESC') . '01' . $parts[1];
            $resultIndex[] = $initStr;
        }

        //替换转成对应元素
        foreach ($resultIndex as $v) {
            $tmp = '';
            for ($i = 0; $i < count($array); $i++) {
                if ($v{$i} == '1') {
                    $tmp .= $array[$i] . $delimiter;
                }
            }
            $result[] = trim($tmp, $delimiter);
        }

        return $result;
    }

    /**
     * 得到展开式 不通用 仅支持2，3，4，5 CQSSC及类似适用
     * @param array $nums 原数组 如array('12','234','12345')
     * @return array $result 按排列展开后的数组
     */
    static public function expand($nums)
    {
        $result = array();
        $tmpNums = array();
        foreach ($nums as $v) {
            $tmp = str_split($v);
            sort($tmp);
            $tmpNums[] = $tmp;
        }

        switch (count($nums)) {
            case 1:
                $result = reset($tmpNums);
                break;
            case 2:
                for ($i = 0; $i < count($tmpNums[0]); $i++) {
                    for ($j = 0; $j < count($tmpNums[1]); $j++) {
                        $result[] = $tmpNums[0][$i] . $tmpNums[1][$j];
                    }
                }
                break;
            case 3:
                for ($i = 0; $i < count($tmpNums[0]); $i++) {
                    for ($j = 0; $j < count($tmpNums[1]); $j++) {
                        for ($k = 0; $k < count($tmpNums[2]); $k++) {
                            $result[] = $tmpNums[0][$i] . $tmpNums[1][$j] . $tmpNums[2][$k];
                        }
                    }
                }
                break;
            case 4:
                for ($i = 0; $i < count($tmpNums[0]); $i++) {
                    for ($j = 0; $j < count($tmpNums[1]); $j++) {
                        for ($k = 0; $k < count($tmpNums[2]); $k++) {
                            for ($l = 0; $l < count($tmpNums[3]); $l++) {
                                $result[] = $tmpNums[0][$i] . $tmpNums[1][$j] . $tmpNums[2][$k] . $tmpNums[3][$l];
                            }
                        }
                    }
                }
                break;
            case 5:
                for ($i = 0; $i < count($tmpNums[0]); $i++) {
                    for ($j = 0; $j < count($tmpNums[1]); $j++) {
                        for ($k = 0; $k < count($tmpNums[2]); $k++) {
                            for ($l = 0; $l < count($tmpNums[3]); $l++) {
                                for ($m = 0; $m < count($tmpNums[4]); $m++) {
                                    $result[] = $tmpNums[0][$i] . $tmpNums[1][$j] . $tmpNums[2][$k] . $tmpNums[3][$l] . $tmpNums[4][$m];
                                }
                            }
                        }
                    }
                }
                break;
            default:
                throw new exception2('non-supported permutation');
                break;
        }

        return $result;
    }

    /**
     * 得到展开式 不通用 仅支持2，3，5 SD11Y及类似适用
     * @param array $nums 原数组 如array('01_02_03','02_04','01_05')
     * @return array $result 按排列展开后的数组 不含重复数字
     * Array
     * (
     * [0] => 01 02 05
     * [1] => 01 04 05
     * [2] => 02 04 01
     * [3] => 02 04 05
     * [4] => 03 02 01
     * [5] => 03 02 05
     * [6] => 03 04 01
     * [7] => 03 04 05
     * )
     */
    static public function expandLotto($nums)
    {
        $result = array();
        $tmpNums = array();
        foreach ($nums as $v) {
            $tmp = explode('_', $v);
            sort($tmp);
            $tmpNums[] = $tmp;
        }

        switch (count($nums)) {
            case 2:
                for ($i = 0; $i < count($tmpNums[0]); $i++) {
                    for ($j = 0; $j < count($tmpNums[1]); $j++) {
                        $result[] = $tmpNums[0][$i] . ' ' . $tmpNums[1][$j];
                    }
                }
                break;
            case 3:
                for ($i = 0; $i < count($tmpNums[0]); $i++) {
                    for ($j = 0; $j < count($tmpNums[1]); $j++) {
                        for ($k = 0; $k < count($tmpNums[2]); $k++) {
                            $result[] = $tmpNums[0][$i] . ' ' . $tmpNums[1][$j] . ' ' . $tmpNums[2][$k];
                        }
                    }
                }
                break;
            case 5:
                for ($i = 0; $i < count($tmpNums[0]); $i++) {
                    for ($j = 0; $j < count($tmpNums[1]); $j++) {
                        for ($k = 0; $k < count($tmpNums[2]); $k++) {
                            for ($l = 0; $l < count($tmpNums[3]); $l++) {
                                for ($m = 0; $m < count($tmpNums[4]); $m++) {
                                    $result[] = $tmpNums[0][$i] . ' ' . $tmpNums[1][$j] . ' ' . $tmpNums[2][$k] . ' ' . $tmpNums[2][$l] . ' ' . $tmpNums[2][$m];
                                }
                            }
                        }
                    }
                }
                break;
            default:
                throw new exception2('non-supported permutation');
                break;
        }

        //去除重复的
        foreach ($result as $k => $v) {
            $parts = explode(' ', $v);
            if (count(array_unique($parts)) != count($parts)) {
                unset($result[$k]);
            }
        }

        return array_values($result);
    }

    /**
     * 判断开奖号码是否合法
     * @param type $lottery
     * @param type $prize_code
     */
    static public function isLegalPrizeCode($lottery, $prize_code)
    {
        switch ($lottery['lottery_type']) {
            case '1':   //数字型
                return preg_match('`^\d{5}$`i', $prize_code);
                break;
            case '2':   //乐透同区型
                if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`i', $prize_code)) {
                    return false;
                }
                foreach (explode(' ', $prize_code) as $v) {
                    if (intval($v) < 1 || intval($v) > 11) {
                        return false;
                    }
                }
                break;
            case '4':   //低频3D
                return preg_match('`^\d{3}$`i', $prize_code);
                break;
            case '7':   //poker     Th As 7d
                return preg_match('`^([ATJQK2-9][shcd])(\s[ATJQK2-9][shcd]){2}$`U', $prize_code);
                break;
            case '8':   //pk拾
                if (!preg_match('`^([01]\d\s{1}){9}[01]\d$`U', $prize_code)) {
                    return false;
                }
                foreach (explode(' ', $prize_code) as $v) {
                    if (intval($v) < 1 || intval($v) > 10) {
                        return false;
                    }
                }
                break;
            default:
                throw new exception2('Non support lottery type');
                break;
        }

        return true;
    }

    /**
     * 此方法用于前台下注时判断指定玩法所投注号码是否合法 避免非正常提交 后台中奖时为慎重起见也可再次判断
     *
     * 判断注单号码表示是否合法 选区之间用逗号分隔，同区之内如有可能2位数字的（如山东十一运）可用下划线分隔，同理，对于三星包点可能超过9，如果买2个号也应采用下划线分隔，如8_10
     * @param array $method 玩法数组
     * @param string $code 号码
     * @return integer 所包含的购买注数，注意这和转直注数不是一个概念
     */
    static public function isLegalCode($method, $code)
    {
        switch ($method['name']) {
            case 'TMBZ':
                if (!preg_match('/^\d(_\d){0,9}$/', $code)) {
                    return false;
                }

                return count(explode('_', $code));
                break;
            case 'TMBS'://幸运28特码包三
                if (!preg_match('/^(\d{1,2}(_\d{1,2}){2})(_\d{1,2})*$/', $code)) {
                    return false;
                }
                $count = count(explode('_', $code));

                return $count * ($count - 1) * ($count - 2) / 6;
                break;
            case 'HLZX'://双色球01_02_03_04_05_06,04_05_06
                $parts = explode(',', $code);
                if (!preg_match('/^\d{2}(_\d{2}){0,32}$/', $parts[0]) || !preg_match('/^\d{2}(_\d{2}){0,15}$/', $parts[1])) {
                    return false;
                }
                $redPart = explode('_', $parts[0]);
                $bluePart = explode('_', $parts[1]);

                $redCount = count($redPart);
                $blueCount = count($bluePart);
                if ($redCount != count(array_unique($redPart)) || $blueCount != count(array_unique($bluePart))) {
                    return false;
                }

                return ($redCount * ($redCount - 1) * ($redCount - 2) * ($redCount - 3) * ($redCount - 4) / 120) * $blueCount;
                break;
            //六合彩
            case 'TMZX'://$code 9_20_32_45
            case 'TMWS':
            case 'ZTWS':
                $reg = '';
                if ($method['name'] == 'TMZX') {
                    $reg = '`^\d{1,2}(_\d{1,2}){0,48}$`i';
                } elseif ($method['name'] == 'TMWS' || 'ZTWS' == $method['name']) {
                    $reg = '`^\d(_\d){0,9}$`i';
                }

                if (!preg_match($reg, $code)) {
                    return false;
                }

                $parts = explode('_', $code);
                foreach ($parts as &$part) {
                    $part = intval($part);
                    if ($part < 0 || $part > 49) {
                        return false;
                    }
                }

                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                return count($parts);
                break;
            case 'ZTYM':
                $parts = explode('_', $code);
                foreach ($parts as &$part) {
                    $part = intval($part);
                    if ($part <= 0 || $part > 49) {
                        return false;
                    }
                }
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                return count($parts);
                break;
            case 'XYDXDS':
            case 'XYSB':
            case 'JDX':
            case 'TMSX'://code'鼠_好_兔_龙_蛇_马_猴'
            case 'TMSB':
            case 'TMDXDS':
            case 'ZTYX':
                if ('TMSX' == $method['name'] || 'ZTYX' == $method['name']) {
                    $reg = '`^[鼠牛虎兔龙蛇马羊猴鸡狗猪]+$`';
                } elseif ('TMSB' == $method['name'] || 'XYSB' == $method['name']) {
                    $reg = '`^[红蓝绿]+$`';
                } elseif ('TMDXDS' == $method['name'] || 'XYDXDS' == $method['name']) {
                    $reg = '`^[大小单双]+$`';
                } elseif ('JDX' == $method['name']) {
                    $reg = '`^极大|极小$`';
                }

                $parts = explode('_', $code);
                foreach ($parts as $v) {
                    if (!preg_match($reg, $v)) {
                        return false;
                    }
                }

                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                return count($parts);
                break;
            case 'ELX':
            case 'SLX':
            case 'SILX':
                $reg = '`^[鼠牛虎兔龙蛇马羊猴鸡狗猪]+$`';
                $parts = explode('_', $code);
                foreach ($parts as $v) {
                    if (!preg_match($reg, $v)) {
                        return false;
                    }
                }

                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                if ($method['name'] == 'ELX') {
                    $c = 2;
                    if (count($parts) < 2) {
                        return false;
                    }
                } elseif ($method['name'] == 'SLX') {
                    $c = 3;
                    if (count($parts) < 3) {
                        return false;
                    }
                } elseif ($method['name'] == 'SILX') {
                    $c = 4;
                    if (count($parts) < 4) {
                        return false;
                    }
                }

                return count(methods::C($parts, $c));
                break;
            case 'SZS':
            case 'EZE':
            case 'SZE':
                $c = $method['name'] == 'SZS' || $method['name'] == 'SZE' ? 3 : 2;

                //----------------- 下面这段是错误的。
                $reg = '`^\d{1,2}(_\d{1,2}){0,48}$`i';
                if (!preg_match($reg, $code)) {
                    return false;
                }
                //-----------------

                $parts = explode('_', $code);
                foreach ($parts as &$part) {
                    $part = intval($part);
                    if ($part <= 0 || $part > 49) {
                        return false;
                    }
                }
                $count = count($parts);

                if ($count < $c || $count != count(array_unique($parts))) {
                    return false;
                }

                return count(methods::C($parts, $c));
                break;
            //三星
            case 'SXZX':    //三星直选 12,34,567;
            case 'ZSZX':   //中三直选
            case 'QSZX':    //前三直选
                if (!preg_match('`^\d{1,10},\d{1,10},\d{1,10}$`i', $code)) {
                    return false;
                }
                //3个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    //优化情节：大于一个数的才需要判断是否可能重复号码
                    if (strlen($v) > 1) {
                        $tmp = str_split($v);
                        if (count($tmp) != count(array_unique($tmp))) {
                            return false;
                        }
                    }
                }

                //算注数 相乘即可
                return strlen($parts[0]) * strlen($parts[1]) * strlen($parts[2]);
                break;
            case 'SXZS':    //三星组三 123;
            case 'ZSZS':   //中三组三
            case 'QSZS':
                if (!preg_match('`^\d{2,10}$`i', $code)) {
                    return false;
                }
                //1个号区 同位上不应有重复号码
                $tmp = str_split($code);
                if (count($tmp) != count(array_unique($tmp))) {
                    return false;
                }

                //算注数 Cn_2 * 2 正好是Pn_2
                return strlen($code) * (strlen($code) - 1);
                break;
            case 'SXZL':    //三星组六  1234;
            case 'ZSZL':    //中三组六
            case 'QSZL':
                if (!preg_match('`^\d{3,10}$`i', $code)) {
                    return false;
                }
                //1个号区 同区上不应有重复号码
                $tmp = str_split($code);
                if (count($tmp) != count(array_unique($tmp))) {
                    return false;
                }

                //算注数 Cn_3
                return strlen($code) * (strlen($code) - 1) * (strlen($code) - 2) / methods::factorial(3);
                break;
            case 'SXLX':    //三星连选 12345,123,58;
            case 'ZSLX':   //中三连选
            case 'QSLX':    //前三连选
                if (!preg_match('`^\d{1,10},\d{1,10},\d{1,10}$`i', $code)) {
                    return false;
                }
                //3个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                }

                //算注数 后三注数+后二注数+后一注数
                $betNums3 = strlen($parts[0]) * strlen($parts[1]) * strlen($parts[2]);
                $betNums2 = strlen($parts[1]) * strlen($parts[2]);
                $betNums1 = strlen($parts[2]);
                return $betNums3 + $betNums2 + $betNums1;
                break;
            case 'SXBD':    //三星包点 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果;
            case 'ZSBD':
            case 'QSBD':
                if (!preg_match('`^\d{1,2}(_\d{1,2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //判断具体的值
                $betNums = 0;
                foreach ($parts as $v) {
                    if (!is_numeric($v) || $v < 0 || $v > 27) {
                        return false;
                    }
                    $betNums += self::$SXBD[$v];
                }

                return $betNums;
                break;
            case 'SXHHZX':    //三星混合组选 仅单式;
            case 'ZSHHZX':   //中三混合组选
            case 'QSHHZX':    //前三混合组选 仅单式
                if (!preg_match('`^\d,\d,\d$`i', $code)) {
                    return false;
                }
                //不应是豹子号
                $parts = explode(',', $code);
                if ($parts[0] == $parts[1] && $parts[1] == $parts[2]) {
                    return false;
                }

                //算注数 相乘即可
                return 1;
                break;

            //二星
            case 'EXZX':    //二星直选 0123456789,0123456789
            case 'QEZX':
                if (!preg_match('`^\d{1,10},\d{1,10}$`i', $code)) {
                    return false;
                }
                //2个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                }

                //算注数 相乘
                return strlen($parts[0]) * strlen($parts[1]);
                break;
            case 'EXZUX':    //二星组选 0123456789
            case 'QEZUX':
                if (!preg_match('`^\d{2,10}$`i', $code)) {
                    return false;
                }
                //1个号区 同区上不应有重复号码
                $tmp = str_split($code);
                if (count($tmp) != count(array_unique($tmp))) {
                    return false;
                }

                //不允许选择超过7个号码 否则视为非法 这应作为通用方法，因为每一field_def都有定义max_selected
                if (count($tmp) > $method['field_def'][1]['max_selected']) {
                    return false;
                }

                //算注数 Cn_2
                return count($tmp) * (count($tmp) - 1) / 2;
                break;
            case 'EXLX':    //二星连选 0123456789,0123456789
            case 'QELX':
                if (!preg_match('`^\d{1,10},\d{1,10}$`i', $code)) {
                    return false;
                }
                //3个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                }

                //算注数 后二注数+后一注数
                $betNums2 = strlen($parts[0]) * strlen($parts[1]);
                $betNums1 = strlen($parts[1]);
                return $betNums2 + $betNums1;
                break;
            case 'EXBD':    //二星包点 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果
            case 'QEBD':
                if (!preg_match('`^\d{1,2}(_\d{1,2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //判断具体的值
                $betNums = 0;
                foreach ($parts as $v) {
                    if (!is_numeric($v) || $v < 0 || $v > 18) {
                        return false;
                    }
                    $betNums += self::$EXBD[$v];
                }

                return $betNums;
                break;

            //一星
            case 'YXZX':    //一星直选 0123456789
                if (!preg_match('`^\d{1,10}$`i', $code)) {
                    return false;
                }
                //1个号区 同区上不应有重复号码
                $tmp = str_split($code);
                if (count($tmp) != count(array_unique($tmp))) {
                    return false;
                }

                //算注数 简单
                return strlen($code);
                break;
            case 'WXDW':    //五星定位 0123456789,0123456789,0123456789,0123456789,0123456789 或,2,3,,都是合法的
                if (!preg_match('`^(\d{0,10}|\-),(\d{0,10}|\-),(\d{0,10}|\-),(\d{0,10}|\-),(\d{0,10}|\-)$`i', $code)) {
                    return false;
                }
                //5个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                $betNums = 0;
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                    if ($v != '-') {
                        $betNums += strlen($v);
                    }
                }

                //算注数 相乘即可
                return $betNums;
                break;
            case 'SXDW':    //低频3D特有 三星定位 0123456789,0123456789,0123456789 或,2,都是合法的
                if (!preg_match('`^\d{0,10},\d{0,10},\d{0,10}$`i', $code)) {
                    return false;
                }
                //3个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                $betNums = 0;
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                    $betNums += strlen($v);
                }

                //算注数 相乘即可
                return $betNums;
                break;

            // 时时彩玩法

            // 时时彩总和
            case 'SSC-ZH':
                # TODO :
                break;
            // 时时彩龙虎
            case 'SSC-LH':
                # TODO :
                break;
            // 时时彩两面大小单双
            case 'SSC-LMDXDS':
                preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $code, $parts);
                $length = count($parts[0]);
                if ($length > 20 || $length === 0) {
                    return false;
                }
                foreach ($parts[0] as $v) {
                    if (!in_array($v, array('大', '小', '单', '双'))) {
                        return false;
                    }
                }
                if (strpos($code, ',') !== false && substr_count($code, ',') !== 4) {
                    return false;
                }

                return $length;
                break;
            // 时时彩前三形态
            case 'SSC-QSXT':
                # TODO :
                break;
            // 时时彩中三形态
            case 'SSC-ZSXT':
                # TODO :
                break;
            // 时时彩后三形态
            case 'SSC-HSXT':
                # TODO :
                break;

            //不定位
            case 'EMBDW':   //三星二码不定位 后三二码不定位
            case 'ZSEMBDW': //中三二码不定位
            case 'QSEMBDW': //低频P3特有 前三二码不定位
            case 'SXEMBDW': //四星二码
            case 'WXEMBDW': //五星二码
                if (!preg_match('`^\d{2,}$`i', $code)) {
                    return false;
                }
                $betCodes = methods::C(str_split($code), 2);
                return count($betCodes);
                break;
            case 'YMBDW':   //三星一码不定位 后三一码不定位
            case 'ZSYMBDW': //新增中三一码不定位
            case 'SXYMBDW': //新增四星一码不定位
            case 'WXYMBDW': //新增五星一码不定位
            case 'QSYMBDW': //低频P3特有 前三一码不定位
                if (!preg_match('`^\d+$`i', $code)) {
                    return false;
                }

                //算注数 有几个号码就是几注
                return strlen($code);
                break;
            case 'WXSMBDW': //五星三码不定位
                if (!preg_match('`^\d{3,}$`i', $code)) {
                    return false;
                }
                $betCodes = methods::C(str_split($code), 3);
                return count($betCodes);
                break;

            //大小单双
            case 'SXDXDS':    //三星大小单双 大,小,单 一注仅限一个号码 因为奖金本来就低
                $parts = explode(',', $code);
                if (count($parts) != 3) {
                    return false;
                }
                foreach ($parts as $v) {
                    if (!in_array($v, array('大', '小', '单', '双'))) {
                        return false;
                    }
                }

                //算注数 一次只能有一注
                return 1;
                break;
            case 'EXDXDS':    //二星大小单双 大,单 一注仅限一个号码 因为奖金本来就低
            case 'QEDXDS':      //前二大小单双 低频3D特有
                $parts = explode(',', $code);
                if (count($parts) != 2) {
                    return false;
                }
                foreach ($parts as $v) {
                    if (!in_array($v, array('大', '小', '单', '双'))) {
                        return false;
                    }
                }

                //算注数 一次只能有一注
                return 1;
                break;
            case 'TMHZ'://幸运28
            case 'SXHZ'://三星和值 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果;
            case 'ZSHZ'://中三和值
            case 'QSHZ':
                if (!preg_match('`^\d{1,2}(_\d{1,2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                //判断具体的值
                $betNums = 0;
                foreach ($parts as $v) {
                    if (!is_numeric($v) || $v < 0 || $v > 27) {
                        return false;
                    }
                    $betNums += self::$SXHZ[$v];
                }

                return $betNums;
                break;
            case 'EXHZ':    //二星和值 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果
            case 'QEHZ':
                if (!preg_match('`^\d{1,2}(_\d{1,2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //判断具体的值
                $betNums = 0;
                foreach ($parts as $v) {
                    if (!is_numeric($v) || $v < 0 || $v > 18) {
                        return false;
                    }
                    $betNums += self::$EXHZ[$v];
                }

                return $betNums;
                break;
            case 'SXZXHZ':  //低频3D特有 组选和值
            case 'QSZXHZ':  //低频P3P5特有 组选和值
                if (!preg_match('`^\d{1,2}(_\d{1,2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                //判断具体的值
                $betNums = 0;
                foreach ($parts as $v) {
                    if (!is_numeric($v) || $v < 1 || $v > 26) {
                        return false;
                    }
                    $betNums += self::$SXZXHZ[$v];
                }

                return $betNums;
                break;

            //四星
            case 'SIXZX':    //四星直选
            case 'QSIZX':    //前四直选
                if (!preg_match('`^\d{1,10},\d{1,10},\d{1,10},\d{1,10}$`i', $code)) {
                    return false;
                }
                //4个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    //优化情节：大于一个数的才需要判断是否可能重复号码
                    if (strlen($v) > 1) {
                        $tmp = str_split($v);
                        if (count($tmp) != count(array_unique($tmp))) {
                            return false;
                        }
                    }
                }

                //算注数 相乘即可
                return strlen($parts[0]) * strlen($parts[1]) * strlen($parts[2]) * strlen($parts[3]);
                break;

            //五星
            case 'WXZX':    //五星直选
                if (preg_match('`^\d{1},\d{1},\d{1},\d{1},\d{1}$`i', $code)) {
                    return 1;
                }
                if (!preg_match('`^\d{1,10},\d{1,10},\d{1,10},\d{1,10},\d{1,10}$`i', $code)) {
                    return false;
                }
                //5个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    //优化情节：大于一个数的才需要判断是否可能重复号码
                    if (strlen($v) > 1) {
                        $tmp = str_split($v);
                        if (count($tmp) != count(array_unique($tmp))) {
                            return false;
                        }
                    }
                }

                return strlen($parts[0]) * strlen($parts[1]) * strlen($parts[2]) * strlen($parts[3]) * strlen($parts[4]);
                break;
            case 'WXLX':    //五星连选
                if (!preg_match('`^\d{1,10},\d{1,10},\d{1,10},\d{1,10},\d{1,10}$`i', $code)) {
                    return false;
                }
                //5个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                }

                //算注数 五星注数+后三注数+后二注数+后一注数
                $betNums5 = strlen($parts[0]) * strlen($parts[1]) * strlen($parts[2]) * strlen($parts[3]) * strlen($parts[4]);
                $betNums3 = strlen($parts[2]) * strlen($parts[3]) * strlen($parts[4]);
                $betNums2 = strlen($parts[3]) * strlen($parts[4]);
                $betNums1 = strlen($parts[4]);
                return $betNums5 + $betNums3 + $betNums2 + $betNums1;
                break;
            case 'REZX':    //任二直选
                if (!preg_match('`^(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-)$`i', $code)) {
                    return false;
                }
                //5个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                }

                $singleNum = 0;
                for ($i = 0; $i < 4; $i++) {
                    if ($parts[$i] != '-') {
                        for ($j = ($i + 1); $j < 5; $j++) {
                            if ($parts[$j] != '-') {
                                $singleNum += strlen($parts[$i]) * strlen($parts[$j]);
                            }
                        }
                    }
                }
                return $singleNum;
                break;
            case 'RSZX':    //任三直选
                if (!preg_match('`^(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-)$`i', $code)) {
                    return false;
                }
                //5个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                }

                $singleNum = 0;
                for ($i = 0; $i < 3; $i++) {
                    if ($parts[$i] != '-') {
                        for ($j = ($i + 1); $j < 4; $j++) {
                            if ($parts[$j] != '-') {
                                for ($k = ($j + 1); $k < 5; $k++) {
                                    if ($parts[$k] != '-') {
                                        $singleNum += strlen($parts[$i]) * strlen($parts[$j]) * strlen($parts[$k]);
                                    }
                                }
                            }
                        }
                    }
                }
                return $singleNum;
                break;
            case 'RSIZX':    //任四直选
                if (!preg_match('`^(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-),(\d{1,10}|\-)$`i', $code)) {
                    return false;
                }
                //5个号区 同区上不应有重复号码
                $parts = explode(',', $code);
                foreach ($parts as $v) {
                    $tmp = str_split($v);
                    if (count($tmp) != count(array_unique($tmp))) {
                        return false;
                    }
                }

                $singleNum = 0;
                for ($g = 0; $g < 2; $g++) {
                    if ($parts[$g] != '-') {
                        for ($i = ($g + 1); $i < 3; $i++) {
                            if ($parts[$i] != '-') {
                                for ($j = ($i + 1); $j < 4; $j++) {
                                    if ($parts[$j] != '-') {
                                        for ($k = ($j + 1); $k < 5; $k++) {
                                            if ($parts[$k] != '-') {
                                                $singleNum += strlen($parts[$g]) * strlen($parts[$i]) * strlen($parts[$j]) * strlen($parts[$k]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                return $singleNum;
                break;
            //  SD11Y   SD11Y   SD11Y   SD11Y   SD11Y   SD11Y   SD11Y   SD11Y
            //前三
            case 'SDQSZX':  //前三直选 01_02_03_04,02_03,01_05 计算方法应展开再排除重复
                $parts = explode(',', $code);
                if (count($parts) != 3) {
                    return false;
                }

                $tmp = array();
                foreach ($parts as $k => $v) {
                    //号码不得重复
                    $tmp[$k] = explode('_', $v);
                    if (count($tmp[$k]) != count(array_unique($tmp[$k]))) {
                        return false;
                    }
                    foreach ($tmp[$k] as $vv) {
                        if (!preg_match('`^(01|02|03|04|05|06|07|08|09|10|11)$`', $vv)) {
                            return false;
                        }
                    }
                }
                $result = methods::expandLotto($parts);

                return count($result);
                break;
            case 'SDQSZUX':     //前三组选 一段 01_02_03_04
                if (!preg_match('`^\d{2}_\d{2}_\d{2}(_[_0-9]+)*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                //最后精确判断每个数字是否在合适大小
                foreach ($parts as $v) {
                    if (intval($v) < 1 || intval($v) > 11) {
                        return false;
                    }
                }

                //算注数 Cn_3
                return count($parts) * (count($parts) - 1) * (count($parts) - 2) / methods::factorial(3);
                break;
            case 'PKS-GYHZ'://06_09_10_11_14
                $parts = explode(',', $code);
                if (count($parts) != 1) {
                    return false;
                }
                //号码不得重复
                $tmp = explode('_', $parts[0]);
                if (count($tmp) != count(array_unique($tmp))) {
                    return false;
                }
                foreach ($tmp as $v) {
                    if (!preg_match('`^(03|04|05|06|07|08|09|10|11|12|13|14|15|16|17|18|19)$`', $v)) {
                        return false;
                    }
                }

                return count($tmp);
                break;
            case 'PKS-Q5LM':
            case 'PKS-H5LM':
                preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $code, $parts);
                $length = count($parts[0]);
                if ($length > 20 || $length === 0) {
                    return false;
                }
                foreach ($parts[0] as $v) {
                    if (!in_array($v, array('大', '小', '单', '双'))) {
                        return false;
                    }
                }
                if (strpos($code, ',') !== false && substr_count($code, ',') !== 4) {
                    return false;
                }

                return $length;
                break;
            case 'PKS-GYDXDS':
                preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $code, $parts);
                $length = count($parts[0]);
                if ($length > 4) {
                    return false;
                }
                foreach ($parts[0] as $v) {
                    if (!in_array($v, array('大', '小', '单', '双'))) {
                        return false;
                    }
                }

                return $length;
                break;
            case 'PKS-CGJ':
                $parts = explode(',', $code);
                if (count($parts) != 1) {
                    return false;
                }
                //号码不得重复
                $tmp = explode('_', $parts[0]);
                if (count($tmp) != count(array_unique($tmp))) {
                    return false;
                }
                foreach ($tmp as $v) {
                    if (!preg_match('`^(01|02|03|04|05|06|07|08|09|10)$`', $v)) {
                        return false;
                    }
                }

                return count($tmp);
                break;
            case 'PKS-CQE':
                $parts = explode(',', $code);
                if (count($parts) != 2) {
                    return false;
                }

                $tmp = array();
                foreach ($parts as $k => $v) {
                    //号码不得重复
                    $tmp[$k] = explode('_', $v);
                    if (count($tmp[$k]) != count(array_unique($tmp[$k]))) {
                        return false;
                    }
                    foreach ($tmp[$k] as $vv) {
                        if (!preg_match('`^(01|02|03|04|05|06|07|08|09|10)$`', $vv)) {
                            return false;
                        }
                    }
                }
                $result = methods::expandLotto($parts);

                return count($result);
                break;
            case 'PKS-CQS':
                $parts = explode(',', $code);
                if (count($parts) != 3) {
                    return false;
                }

                $tmp = array();
                foreach ($parts as $k => $v) {
                    //号码不得重复
                    $tmp[$k] = explode('_', $v);
                    if (count($tmp[$k]) != count(array_unique($tmp[$k]))) {
                        return false;
                    }
                    foreach ($tmp[$k] as $vv) {
                        if (!preg_match('`^(01|02|03|04|05|06|07|08|09|10)$`', $vv)) {
                            return false;
                        }
                    }
                }
                $result = methods::expandLotto($parts);

                return count($result);
                break;
            case 'PKS-DWD':
            case 'PKS-DWDH5':
                $parts = explode(',', $code);
                if (count($parts) != 5) {
                    return false;
                }
                $betNums = 0;
                foreach ($parts as $v) {
                    if ($v != '') {
                        if (!preg_match('`^\d{2}(_\d{2})*$`i', $v)) {
                            return false;
                        }
                        //号码不得重复
                        $parts2 = explode('_', $v);
                        $betNums += count($parts2);
                        if (count($parts2) != count(array_unique($parts2))) {
                            return false;
                        }
                    }
                }

                //算注数
                return $betNums;
                break;
            case 'PKS-OT':
            case 'PKS-TN':
            case 'PKS-TE':
            case 'PKS-FS':
            case 'PKS-FSIX':
                if ($code != '龙' && $code != '虎') {
                    return false;
                }
                return 1;
                break;
            case 'SDQEZX':     //前二直选 二段 01_02_03_04,02_03
                $parts = explode(',', $code);
                if (count($parts) != 2) {
                    return false;
                }

                $tmp = array();
                foreach ($parts as $k => $v) {
                    //号码不得重复
                    $tmp[$k] = explode('_', $v);
                    if (count($tmp[$k]) != count(array_unique($tmp[$k]))) {
                        return false;
                    }
                    foreach ($tmp[$k] as $vv) {
                        if (!preg_match('`^(01|02|03|04|05|06|07|08|09|10|11)$`', $vv)) {
                            return false;
                        }
                    }
                }
                $result = methods::expandLotto($parts);

                return count($result);
                break;
            case 'SDQEZUX':     //前二组选 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_2
                return count($parts) * (count($parts) - 1) / 2;
                break;

            //任选
            case 'SDRX1':     //任选1 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数
                return count($parts);
                break;
            case 'SDRX2':     //任选2 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_2
                return count($parts) * (count($parts) - 1) / 2;
                break;
            case 'SDRX3':     //任选3 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_3
                return count($parts) * (count($parts) - 1) * (count($parts) - 2) / 6;
                break;
            case 'SDRX4':     //任选4 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}_\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_4
                return count($parts) * (count($parts) - 1) * (count($parts) - 2) * (count($parts) - 3) / 24;
                break;
            case 'SDRX5':     //任选5 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}_\d{2}_\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_5
                return count($parts) * (count($parts) - 1) * (count($parts) - 2) * (count($parts) - 3) * (count($parts) - 4) / 120;
                break;
            case 'SDRX6':     //任选6 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_6
                return count($parts) * (count($parts) - 1) * (count($parts) - 2) * (count($parts) - 3) * (count($parts) - 4) * (count($parts) - 5) / 720;
                break;
            case 'SDRX7':     //任选7 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_7
                return count($parts) * (count($parts) - 1) * (count($parts) - 2) * (count($parts) - 3) * (count($parts) - 4) * (count($parts) - 5) * (count($parts) - 6) / 5040;
                break;
            case 'SDRX8':     //任选8 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数 Cn_8
                return count($parts) * (count($parts) - 1) * (count($parts) - 2) * (count($parts) - 3) * (count($parts) - 4) * (count($parts) - 5) * (count($parts) - 6) * (count($parts) - 7) / 40320;
                break;

            //前3不定位胆
            case 'SDQSBDW':     //前3不定位胆 一段 01_02_03_04_05_06_07_08_09_10_11
                if (!preg_match('`^\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数
                return count($parts);
                break;
            //前3定位胆
            case 'SDQSDWD':     //前3定位胆 01_02_03,04_05,06_07为一单 也可以只买一位，如'01_02_03,,'表示只买个位胆，没买的位留空
                $parts = explode(',', $code);
                if (count($parts) != 3) {
                    return false;
                }
                $betNums = 0;
                foreach ($parts as $v) {
                    if ($v != '') {
                        if (!preg_match('`^\d{2}(_\d{2})*$`i', $v)) {
                            return false;
                        }
                        //号码不得重复
                        $parts2 = explode('_', $v);
                        $betNums += count($parts2);
                        if (count($parts2) != count(array_unique($parts2))) {
                            return false;
                        }
                    }
                }

                //算注数
                return $betNums;
                break;

            //定单双
            case 'SDDDS':     //0单5双:750.0000元 (1注) 5单0双:125.0000元 (6注)1单4双:25.0000元 (30注)4单1双:10.0000元 (75注)2单3双:5.0000元 (150注)3单2双:3.7000元 (200注)
                //projects表中用012345表示0~5单(5-0~5)双
//                if (!preg_match('`^[0-5]+$`i', $code)) {
//                    return false;
//                }
//                //号码不得重复
//                $parts = str_split($code);
//                if (count($parts) != count(array_unique($parts))) {
//                    return false;
//                }
//                return count($parts);
                //更改：拟用实际中文作为projects表中表示 只有一注 判断中奖方法也要改
                if (!in_array($code, self::$SDDDS)) {
                    return false;
                }

                //一次只能有一注
                return 1;
                break;
            //猜中位
            case 'SDCZW':     // 一段03_04_05_06_07_08_09 全买
                if (!preg_match('`^\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                foreach ($parts as $v) {
                    if (intval($v) < 3 || intval($v) > 9) {
                        return false;
                    }
                }

                //算注数
                return count($parts);
                break;

            case 'YFFS':    //趣味玩法,一帆风顺
            case 'HSCS':    //趣味玩法,好事成双
            case 'SXBX':    //趣味玩法,三星报喜
            case 'SJFC':    //趣味玩法,四季发财
                //传来的数据模式 13567
                if (!preg_match('`^\d+$`U', $code)) {
                    return false;
                }

                $parts = array_unique(str_split($code));
                if (count($parts) !== strlen($code)) {
                    return false;
                }

                //算注数
                return count($parts);
                break;


            case 'ZUX120':      //组选120
                //传来的数据模式 13567
                if (!preg_match('`^\d+$`U', $code)) {
                    return false;
                }

                $parts = array_unique(str_split($code));
                if (count($parts) !== strlen($code)) {
                    return false;
                }
                if (strlen($code) > 4) {
                    return strlen($code) === 5 ? 1 : (methods::factorial(strlen($code)) / (methods::factorial(strlen($code) - 5) * 120));
                }
                return 0;
                break;
            case 'ZUX24':       //组选24
                //传来的数据模式 13567
                if (!preg_match('`^\d+$`U', $code)) {
                    return false;
                }

                $parts = array_unique(str_split($code));
                if (count($parts) !== strlen($code)) {
                    return false;
                }
                if (strlen($code) > 3) {
                    return methods::factorial(strlen($code)) / (methods::factorial(strlen($code) - 4) * 24);
                }
                return 0;
                break;
            case 'ZUX6':       //组选6
                //传来的数据模式 13567
                if (!preg_match('`^\d+$`U', $code)) {
                    return false;
                }

                $parts = array_unique(str_split($code));
                if (count($parts) !== strlen($code)) {
                    return false;
                }
                if (strlen($code) > 1) {
                    return methods::factorial(strlen($code)) / (methods::factorial(strlen($code) - 2) * 2);
                }
                return 0;
                break;

            case 'ZUX10':    //组选10
            case 'ZUX5':    //组选5
            case 'ZUX4':    //组选4
                //传来的数据模式 13567,6541
                if (!preg_match('`^\d+,\d+$`U', $code)) {
                    return false;
                }
                $code = explode(',', $code);
                foreach ($code as $key => $code2) {
                    $code[$key] = array_unique(str_split($code2));
                    if (count($code[$key]) !== strlen($code2)) {
                        return false;
                    }
                }
                $num = 0;
                $compareNum = count($code[1]);
                if (count($code[0]) > 0 && $compareNum > 0) {
                    foreach ($code[0] as $value) {
                        $tmp = $compareNum;
                        if (in_array($value, $code[1]) === true) {
                            $tmp = $compareNum - 1;
                        }
                        if ($tmp > 0) {
                            $num += methods::factorial($tmp) / methods::factorial($tmp - 1);
                        }
                    }
                }
                return $num;
                break;

            case 'ZUX20':    //组选20
            case 'ZUX12':    //组选12
                //传来的数据模式 13567,6541
                if (!preg_match('`^\d+,\d+$`U', $code)) {
                    return false;
                }
                $code = explode(',', $code);
                foreach ($code as $key => $code2) {
                    $code[$key] = array_unique(str_split($code2));
                    if (count($code[$key]) !== strlen($code2)) {
                        return false;
                    }
                }
                $num = 0;
                $compareNum = count($code[1]);
                if (count($code[0]) > 0 && $compareNum > 1) {
                    foreach ($code[0] as $value) {
                        $tmp = $compareNum;
                        if (in_array($value, $code[1]) === true) {
                            $tmp = $compareNum - 1;
                        }
                        if ($tmp > 1) {
                            $num += methods::factorial($tmp) / (methods::factorial($tmp - 2) * 2);
                        }
                    }
                }
                return $num;
                break;
            case 'ZUX60':    //组选60
                //传来的数据模式 13567,6541
                if (!preg_match('`^\d+,\d+$`U', $code)) {
                    return false;
                }
                $code = explode(',', $code);
                foreach ($code as $key => $code2) {
                    $code[$key] = array_unique(str_split($code2));
                    if (count($code[$key]) !== strlen($code2)) {
                        return false;
                    }
                }
                $num = 0;
                $compareNum = count($code[1]);
                if (count($code[0]) > 0 && $compareNum > 2) {
                    foreach ($code[0] as $value) {
                        $tmp = $compareNum;
                        if (in_array($value, $code[1]) === true) {
                            $tmp = $compareNum - 1;
                        }
                        if ($tmp > 2) {
                            $num += methods::factorial($tmp) / (methods::factorial($tmp - 3) * 6);
                        }
                    }
                }
                return $num;
                break;
            case 'ZUX30':    //组选30
                //传来的数据模式 13567,6541
                if (!preg_match('`^\d+,\d+$`U', $code)) {
                    return false;
                }
                $code = explode(',', $code);
                foreach ($code as $key => $code2) {
                    $code[$key] = array_unique(str_split($code2));
                    if (count($code[$key]) !== strlen($code2)) {
                        return false;
                    }
                }
                $num = 0;
                $compareNum = count($code[0]);
                if ($compareNum > 1 && count($code[1]) > 0) {
                    foreach ($code[1] as $value) {
                        $tmp = $compareNum;
                        if (in_array($value, $code[0]) === true) {
                            $tmp = $compareNum - 1;
                        }
                        if ($tmp > 1) {
                            $num += methods::factorial($tmp) / (methods::factorial($tmp - 2) * 2);
                        }
                    }
                }
                return $num;
                break;

            /** 江苏快三
             *  号码表示方法：号区之间用,分隔，同区内号码之间一般不用分隔符，若可能有2位表示的，用_分隔（如和值玩法或sd11y）
             * 注单表示方法（改进版）：String codes = "46:1,2,3,4,5|6,7,8,9,0|1,2,3,4,5#43:1,2,3|6,7,0";
             */
            case 'JSETDX':  //二同单选 2个号区 11_22,34 排重处理要另外做
                if (!preg_match('`^\d{2}(_\d{2})*,\d+$`i', $code)) {
                    return false;
                }
                $parts = explode(',', $code);

                //号区1 同区上不应有重复号码
                $tmp0 = explode('_', $parts[0]);
                if (count($tmp0) != count(array_unique($tmp0))) {
                    return false;
                }
                $possibleCodes = array('11', '22', '33', '44', '55', '66');
                foreach ($tmp0 as $v) {
                    if (!in_array($v, $possibleCodes)) {
                        return false;
                    }
                }

                //号区2 同区上不应有重复号码 并且不应和号区一的重复
                $tmp1 = str_split($parts[1]);
                if (count($tmp1) != count(array_unique($tmp1))) {
                    return false;
                }
                foreach ($tmp1 as $v) {
                    if (strpos($parts[0], strval($v)) !== false) {
                        return false;
                    }
                }

                //算注数 相乘即可
                return count($tmp0) * count($tmp1);
                break;
            case 'JSETFX':  //二同复选 1个号区 11_22_33 排重处理要另外做
                if (!preg_match('`^\d{2}(_\d{2})*$`i', $code)) {
                    return false;
                }

                //号区1 同区上不应有重复号码
                $parts = explode('_', $code);
                $possibleCodes = array('11', '22', '33', '44', '55', '66');
                foreach ($parts as $v) {
                    if (!in_array($v, $possibleCodes)) {
                        return false;
                    }
                }

                //算注数 相乘即可
                return count($parts);
                break;
            case 'JSHZ':   //快三和值
                if (!preg_match('`^\d{1,2}(_\d{1,2})*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                //判断具体的值
                $betNums = 0;
                foreach ($parts as $v) {
                    if (!is_numeric($v) || $v < 3 || $v > 18) {
                        return false;
                    }
                }
                $betNums = count($parts);

                return $betNums;
                break;
            case 'JSEBT':   //二不同号
                if (!preg_match('`^\d{2,6}$`i', $code)) {
                    return false;
                }
                $parts = str_split($code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                //判断具体的值
                $betNums = count(methods::C($parts, 2));
                return $betNums;
                break;
            case 'JSSTDX':   //三同号单选
                if (!preg_match('`^\d{3}(_\d{3})*$`i', $code)) {
                    return false;
                }

                //号区1 同区上不应有重复号码
                $parts = explode('_', $code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                $possibleCodes = array('111', '222', '333', '444', '555', '666');
                foreach ($parts as $v) {
                    if (!in_array($v, $possibleCodes)) {
                        return false;
                    }
                }

                //算注数 相乘即可
                return count($parts);
                break;
            case 'JSSTTX':   //三同号通选,可能是信用玩法

                if ($code != '111_222_333_444_555_666' && $code != '三同号通选') {
                    return false;
                }
                return 1;
                break;
            case 'JSSBT':   //三不同号
                if (!preg_match('`^\d{3,6}$`i', $code)) {
                    return false;
                }
                $parts = str_split($code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                //判断具体的值
                $betNums = count(methods::C($parts, 3));
                return $betNums;
                break;
            case 'JSSLTX':   //三连号通选
                if ($code != '123_234_345_456' && $code != '三连号通选') {
                    return false;
                }
                return 1;
                break;
            // 快三和值大小单双
            case 'KS-HZDXDS':
                preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $code, $parts);
                $length = count($parts[0]);
                if ($length > 4 || $length === 0) {
                    return false;
                }

                foreach ($parts[0] as $v) {
                    if (!in_array($v, array('大', '小', '单', '双'))) {
                        return false;
                    }
                }

                return $length;
                break;

            /////////////////////////  快乐扑克区    ////////////////////////
            /** 快乐扑克区
             *  号码表示方法：
             *  1、同花包选  2、顺子包选    3、同花顺包选       4、豹子包选      5、对子包选      (全部都已上述文字为注码)
             *  6、同花单选 : 黑桃_红桃_梅花_方块
             * 7、顺子单选 : A23_345_9TJ_TJQ
             *
             * 8、同花顺单选 : 红桃顺子_黑桃顺子_梅花顺子_方块顺子
             *
             * 9、豹子单选 : AAA_222_TTT_JJJ_QQQ
             *
             * 10、对子单选 ：AA_22_TT_JJ_QQ
             *
             * 11、任选一 : A_T_J
             * .........
             * 12、任选六 : A_3_T_J_Q_K
             */
            case 'PKSZ':   //扑克 顺子  A23_345_9TJ_TJQ
                if (!preg_match('`^(A23|234|345|456|567|678|789|89T|9TJ|TJQ|JQK|QKA)(_(A23|234|345|456|567|678|789|89T|9TJ|TJQ|JQK|QKA))*$`U', $code)) {
                    return false;
                }

                $parts = explode('_', $code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数
                return count($parts);

                break;
            case 'PKTH':   //扑克 同花
                $parts = explode('_', $code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                $possibleCodes = array('黑桃', '红桃', '梅花', '方块');
                foreach ($parts as $v) {
                    if (!in_array($v, $possibleCodes)) {
                        return false;
                    }
                }

                //算注数
                return count($parts);

                break;
            case 'PKTHS':   //同花顺 红桃顺子_黑桃顺子_梅花顺子_方块顺子
                $parts = explode('_', $code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                $possibleCodes = array('红桃顺子', '黑桃顺子', '梅花顺子', '方块顺子');
                foreach ($parts as $v) {
                    if (!in_array($v, $possibleCodes)) {
                        return false;
                    }
                }

                //算注数
                return count($parts);

                break;
            case 'PKBZ':   //豹子 AAA_222_TTT_JJJ_QQQ
                if (!preg_match('`^(AAA|222|333|444|555|666|777|888|999|TTT|JJJ|QQQ|KKK)(_(AAA|222|333|444|555|666|777|888|999|TTT|JJJ|QQQ|KKK))*$`U', $code)) {
                    return false;
                }
                $parts = explode('_', $code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数
                return count($parts);

                break;
            case 'PKDZ':   //对子 AA_22_TT_JJ_QQ
                if (!preg_match('`^(AA|22|33|44|55|66|77|88|99|TT|JJ|QQ|KK)(_(AA|22|33|44|55|66|77|88|99|TT|JJ|QQ|KK))*$`U', $code)) {
                    return false;
                }
                $parts = explode('_', $code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                //算注数
                return count($parts);

                break;
            case 'PKBX':   //包选 同花包选_顺子包选_同花顺包选_豹子包选_对子包选
                $parts = explode('_', $code);
                //号码不得重复
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }
                $possibleCodes = array('同花包选', '顺子包选', '同花顺包选', '豹子包选', '对子包选');
                foreach ($parts as $v) {
                    if (!in_array($v, $possibleCodes)) {
                        return false;
                    }
                }

                //算注数
                return count($parts);

                break;
            case 'PKRX1':   //扑克 任选一
                if (!preg_match('`^([ATJQK|2-9])(_[ATJQK|2-9])*$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                if (count($parts) != count(array_unique($parts))) {
                    return false;
                }

                return count($parts);

                break;
            case 'PKRX2':   //扑克 任选二
                if (!preg_match('`^([ATJQK|2-9])(_[ATJQK|2-9]){1,}$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                $codeNum = count($parts);
                if ($codeNum != count(array_unique($parts))) {
                    return false;
                }

                return $codeNum * ($codeNum - 1) / 2;

                break;
            case 'PKRX3':   //扑克 任选三
                if (!preg_match('`^([ATJQK|2-9])(_[ATJQK|2-9]){2,}$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                $codeNum = count($parts);
                if ($codeNum != count(array_unique($parts))) {
                    return false;
                }

                return $codeNum * ($codeNum - 1) * ($codeNum - 2) / 6;

                break;
            case 'PKRX4':   //扑克 任选四
                if (!preg_match('`^([ATJQK|2-9])(_[ATJQK|2-9]){3,}$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                $codeNum = count($parts);
                if ($codeNum != count(array_unique($parts))) {
                    return false;
                }
                return $codeNum * ($codeNum - 1) * ($codeNum - 2) * ($codeNum - 3) / 24;

                break;
            case 'PKRX5':   //扑克 任选五
                if (!preg_match('`^([ATJQK|2-9])(_[ATJQK|2-9]){4,}$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                $codeNum = count($parts);
                if ($codeNum != count(array_unique($parts))) {
                    return false;
                }
                return $codeNum * ($codeNum - 1) * ($codeNum - 2) * ($codeNum - 3) * ($codeNum - 4) / 120;

                break;
            case 'PKRX6':   //扑克 任选六
                if (!preg_match('`^([ATJQK|2-9])(_[ATJQK|2-9]){5,}$`i', $code)) {
                    return false;
                }
                //号码不得重复
                $parts = explode('_', $code);
                $codeNum = count($parts);
                if ($codeNum != count(array_unique($parts))) {
                    return false;
                }
                return $codeNum * ($codeNum - 1) * ($codeNum - 2) * ($codeNum - 3) * ($codeNum - 4) * ($codeNum - 5) / 720;

                break;
            default:
                throw new exception2('未知的玩法', 1201);
                break;
        }

        return true;
    }

    /**
     * 根据不同玩法判断是否中奖 正常情况返回0表示没有中奖，否则表示具体的单倍奖金 注意 所得结果须再x投注倍数x模式才是最终奖金！
     * 将被CRON定时调用执行，出错会抛异常，如玩法不存在，号码非法等
     * @param array $lottery 彩种数组
     * @param int $curRebate 当时选择的返点
     * @param array $method 玩法数组
     * @param string $prize_code 开奖号码
     * @param string $projectCode 用户所购号码 不同区段用,分隔，sd11y的同段多个号码用_分隔
     * @return float 具体奖金
     */
    static public function computePrize($lottery, $method, $user_id, $userRebate, $curRebate, $prize_code, $projectCode)
    {
        if (!is_array($lottery) || !is_array($method) || !is_numeric($userRebate) || $userRebate >= (1 - $lottery['total_profit']) || $curRebate < 0 || !is_string($prize_code) || strlen($prize_code) < 3 || $projectCode == '') {
            throw new exception2('参数无效');
        }
        if ($curRebate > $userRebate) {
            //如果当天投注用户被调低了返点，这里会执行到而抛异常，作为兼容性考虑，可以改成最大返点，这不会形成一个bug
            log2("Illegal rebate(非法返点),pls verify the rebate is correction!(user_id={$user_id},rebate={$userRebate},curRebate={$curRebate},code={$projectCode}");
            //throw new exception2("Illegal rebate!(user_id={$user_id},rabate={$userRebate},curRebate={$curRebate},code={$projectCode}"); //用户投注时选择的返点不可能大于其最大返点
            $curRebate = $userRebate;
        }

        //辅助数据
        switch ($lottery['lottery_type']) {
            case '1':   //数字型 特指时时彩
                if (!isset(self::$helpData[$prize_code])) {
                    self::$helpData[$prize_code] = methods::analyzeDigital($prize_code);
                }
                $helpData = self::$helpData[$prize_code];
                $back4 = substr($prize_code, 1, 4);
                $back3 = substr($prize_code, 2, 3);
                $back2 = substr($prize_code, 3, 2);
                $back1 = substr($prize_code, 4, 1);
                $fore4 = substr($prize_code, 0, 4);
                $fore3 = substr($prize_code, 0, 3);     //万千百 前三直选
                $fore2 = substr($prize_code, 0, 2);    //万千 前二直选
                $fore21 = substr($prize_code, 1, 1);    //千 用于前二连选的二等奖
                $foreTH = substr($prize_code, 1, 2);     //千百 用于前三连选的二等奖
                $foreH = substr($prize_code, 2, 1);     //百 用于前三连选的三等奖
                $mid3 = substr($prize_code, 1, 3);     //千百十  中三直选;
                $mid2 = substr($prize_code, 2, 2);      //千百 用于中三连选的二等奖;
                $mid1 = substr($prize_code, 3, 1);      //千百 用于中三连选的三等奖;
                break;
            case '2':   //特指11选5
                $sdfore3 = substr($prize_code, 0, 8);
                $sdfore2 = substr($prize_code, 0, 5);
                break;
            case '3':
                $tmpBalls = explode(' ', $prize_code);
                $redBalls = array_slice($tmpBalls, 0, 6);
                $blueBall = $tmpBalls[6];
                break;
            case '4':  //福彩3D 只有3个开奖号码 按后三处理
                if (!isset(self::$helpData[$prize_code])) {
                    self::$helpData[$prize_code] = methods::analyzeLow3D($prize_code);
                }
                $helpData = self::$helpData[$prize_code];
                $back3 = $prize_code;
                $fore2 = substr($prize_code, 0, 2);     //百十位 用于前三连选的二等奖
                $back2 = substr($prize_code, 1, 2);     //十个位
                break;
            case '8': //PK拾
                $pksback5 = substr($prize_code, -14);
                $pksfore5 = substr($prize_code, 0, 14);
                $pksfore3 = substr($prize_code, 0, 8);
                $pksfore2 = substr($prize_code, 0, 5);
                $pksfore1 = substr($prize_code, 0, 2);
            case '9': //六合彩
                $tmpPrizeCodes = array_map("intval", explode(' ', $prize_code));
                $specilCode = $tmpPrizeCodes[6];
                break;
            case '10':
                $prizeArr = str_split($prize_code);
                $sum = array_sum($prizeArr);
                break;
            default:
                break;
        }
        $codes = explode("|", $projectCode);
        $allPrize = 0;

        foreach ($codes as $kk => $code) {
            //判断所购号码是否合法
            //为效率起见，谨慎的不再判断，因为投注时已进行严格判断
//            if (!$betNums = methods::isLegalCode($method, $code)) {
//                throw new exception2('Illegal code');
//            }
            $level = 0;
            switch ($method['name']) {
                case 'TMBZ':
                    $parts = explode('_', $code);
                    $uniquePrize = array_unique($prizeArr);
                    if (count($uniquePrize) == 1) {
                        foreach ($parts as $betCode) {
                            if ($betCode == $uniquePrize[0]) {
                                $level = 1;
                                break;
                            }
                        }
                    }
                    break;
                case 'TMBS':
                    $level = array();
                    $parts = explode('_', $code);
                    $count = count($parts);
                    if ($count >= 3 && in_array($sum, $parts)) {
                        $fillNum = ($count - 1) * ($count - 2) / 2;
                        $level = array_fill(0, $fillNum, 1);
                    }
                    break;
                case 'TMHZ'://幸运28
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $sum) {
                            $level = 1;
                            break;
                        }
                    }
                    break;
                case 'XYDXDS':
                    $resStat = $level = array();
                    $betCodeArr = explode('_', $code);
                    $sum > 13 ? array_push($resStat, '大') : array_push($resStat, '小');
                    $sum % 2 == 0 ? array_push($resStat, '双') : array_push($resStat, '单');
                    foreach ($betCodeArr as $code) {
                        if (in_array($code, $resStat)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'JDX':
                    $betCodeArr = explode('_', $code);
                    $resStat = $sum <= 4 ? '极小' : ($sum >= 23 ? '极大' : '');
                    foreach ($betCodeArr as $code) {
                        if ($code != '' && $code == $resStat) {
                            $level = 1;
                        }
                    }
                    break;
                case 'XYSB':
                    $betCodeArr = explode('_', $code);
                    if (in_array($sum, array(3, 6, 9, 12, 15, 18, 21, 24))) {
                        $resStat = '红';
                    } elseif (in_array($sum, array(1, 4, 7, 10, 16, 19, 22, 25))) {
                        $resStat = '绿';
                    } elseif (in_array($sum, array(2, 5, 8, 11, 17, 20, 23, 26))) {
                        $resStat = '蓝';
                    } else {
                        $resStat = '';
                    }
                    foreach ($betCodeArr as $code) {
                        if ($code != '' && $code == $resStat) {
                            $level = 1;
                        }
                    }
                    break;
                case 'HLZX'://code 01_02_03_04_05,05_08
                    $tmpCodes = explode(',', $code);
                    $redCodes = explode('_', $tmpCodes[0]);
                    $blueCodes = explode('_', $tmpCodes[1]);
                    $redWin = $blueWin = 0;
                    foreach ($redCodes as $redCode) {//计算中多少个红球
                        if (in_array($redCode, $redBalls)) {
                            $redWin++;
                        }
                    }
                    if (in_array($blueBall, $blueCodes)) {
                        $blueWin++;
                    }

                    if ($redWin == 5) {
                        if ($blueWin == 1) {
                            $level = 1;
                        } else {
                            $level = 2;
                        }
                    } elseif ($redWin == 4) {
                        if ($blueWin == 1) {
                            $level = 2;
                        } else {
                            $level = 3;
                        }
                    } elseif ($redWin == 3 && $blueWin == 1) {
                        $level = 3;
                    } elseif ($redWin <= 2 && $blueWin == 1) {
                        $level = 4;
                    }

                    break;
                case 'TMZX':// code 11_22_32_43 prize_code 1 2 3 45 23 12 37
                    $betCodeArr = explode('_', $code);
                    if (in_array(intval($specilCode), $betCodeArr)) {
                        $level = 1;
                    }
                    break;
                case 'TMSX':// code 鼠_牛_虎_兔 prize_code 1 2 3 45 23 12 37
                    $betZodiac = explode('_', $code);
                    $openZodiac = self::$lhcMaps[$specilCode]['zodiac'];
                    if (in_array($openZodiac, $betZodiac)) {
                        $level = $openZodiac == self::BIG_ZODIAC ? 2 : 1;
                    }

                    break;
                case 'TMWS':// code 0_1_3_4 prize_code 1 2 3 45 23 12 37
                    $betCodeArr = explode('_', $code);
                    $openTail = self::$lhcMaps[$specilCode]['tail'];
                    if (in_array($openTail, $betCodeArr)) {
                        $level = $openTail == 0 ? 1 : 2;
                    }
                    break;
                case 'TMSB':
                    $betCodeArr = explode('_', $code);
                    $openColor = self::$lhcMaps[$specilCode]['color'];
                    if (in_array($openColor, $betCodeArr)) {
                        $level = $openColor == '红' ? 2 : 1;
                    }
                    break;
                case 'TMDXDS':
                    $level = array();
                    $betCodeArr = explode('_', $code);
                    $openBigSmall = self::$lhcMaps[$specilCode]['bigSmall'];
                    $openOddEven = self::$lhcMaps[$specilCode]['oddEven'];
                    if (in_array($openBigSmall, $betCodeArr)) {
                        $level[] = $openBigSmall == '小' ? 1 : 2;
                    }
                    if (in_array($openOddEven, $betCodeArr)) {
                        $level[] = $openOddEven == '双' ? 1 : 2;
                    }
                    break;
                case 'ZTYM':
                    $level = array();
                    $betCodeArr = explode('_', $code);
                    foreach ($betCodeArr as $v) {
                        if (in_array($v, $tmpPrizeCodes)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'ZTYX':
                    $level = $openZodiacs = array();
                    $betZodiac = explode('_', $code);
                    foreach ($tmpPrizeCodes as $v) {
                        $openZodiacs[] = self::$lhcMaps[$v]['zodiac'];//得到所有开奖的生肖再去重
                    }
                    foreach (array_unique($openZodiacs) as $vv) {
                        if (in_array($vv, $betZodiac)) {
                            $level[] = $vv == self::BIG_ZODIAC ? 2 : 1;
                        }
                    }

                    break;
                case 'ZTWS':
                    $level = $openTails = array();
                    $betTail = explode('_', $code);
                    foreach ($tmpPrizeCodes as $v) {
                        $openTails[] = self::$lhcMaps[$v]['tail'];//得到所有开奖的尾再去重
                    }

                    foreach (array_unique($openTails) as $vv) {
                        if (in_array($vv, $betTail)) {
                            $level[] = $vv == 0 ? 1 : 2;
                        }
                    }

                    break;
                case 'ELX': //code 龙_虎_兔
                case 'SLX':
                case 'SILX':
                    $level = $openZodiacs = $winZodiacs = array();
                    $betZodiac = explode('_', $code);
                    foreach ($tmpPrizeCodes as $v) {
                        $openZodiacs[] = self::$lhcMaps[$v]['zodiac'];//得到所有开奖的生肖再去重
                    }

                    foreach (array_unique($openZodiacs) as $vv) {
                        if (in_array($vv, $betZodiac)) {
                            $winZodiacs[] = $vv;
                        }
                    }

                    $legalNum = $method['name'] == 'ELX' ? 2 : ($method['name'] == 'SLX' ? 3 : 4);

                    if (count($winZodiacs) >= $legalNum) {
                        $expandZodiac = methods::C($winZodiacs, $legalNum);
                        foreach ($expandZodiac as $zodiac) {//虎兔
                            $level[] = preg_match('`' . self::BIG_ZODIAC . '`', $zodiac) == 1 ? 2 : 1;
                        }
                    }
                    break;
                case 'SZS'://code 2_10_13_14_15 prizecode 2 10 13 12 3 6 11
                case 'EZE':
                    unset($tmpPrizeCodes[6]);
                    $c = $method['name'] == 'SZS' ? 3 : 2;
                    $betCode = explode('_', $code);
                    $level = $winCode = array();
                    foreach ($betCode as $v) {
                        if (in_array($v, $tmpPrizeCodes)) {
                            $winCode[] = $v;
                        }
                    }
                    if (count($winCode) >= $c) {
                        $expandBetNum = methods::C($winCode, $c);
                        for ($i = 0, $len = count($expandBetNum); $i < $len; $i++) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SZE':
                    unset($tmpPrizeCodes[6]);
                    $betCode = explode('_', $code);
                    $level = $winCode = array();
                    foreach ($betCode as $v) {
                        if (in_array($v, $tmpPrizeCodes)) {
                            $winCode[] = $v;
                        }
                    }
                    $countWin = count($winCode);
                    $countBet = count($betCode);
                    if ($countWin >= 2) {//其中包括中两注和中三注的情况，一等奖=Cn,3 ，二等奖= Cn,2 * (m-n) m代表countBet
                        if ($countWin >= 3) {
                            $levenOneNum = count(methods::C($winCode, 3));//中一等奖注数
                            for ($i = 0, $len = $levenOneNum; $i < $len; $i++) {
                                $level[] = 1;
                            }
                        }

                        $levenTowNum = count(methods::C($winCode, 2)) * ($countBet - $countWin);//中二等奖注数
                        for ($i = 0; $i < $levenTowNum; $i++) {
                            $level[] = 2;
                        }
                    }

                    break;
                case 'SXZX':    //三星直选 三段 0123456789,0123456789,0123456789 复式 1000
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$back3[0]}\d*,\d*{$back3[1]}\d*,\d*{$back3[2]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;
                case 'SXZS':    //三星组三 一段 0123456789 复式90注 只要有2个数字相同即为中奖
                    $back3Parts = str_split($back3);
                    //如果不是组三态显然没中奖
                    $tmp = array_values(array_unique($back3Parts));
                    if (count($tmp) == 2) {
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'SXZL':    //三星组六 一段 0123456789 复式120注
                    $back3Parts = str_split($back3);
                    $tmp = array_unique($back3Parts);
                    if (count($tmp) == 3) { //首先要是组六态
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false && strpos($code, $tmp[2]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'SXLX':    //三星连选 三段 0123456789,0123456789,0123456789 复式 全包1110注
                    //兼中兼得(即可同时中一二三等奖) 百十个 十个 个 分别对应123等奖
                    $parts = explode(',', $code);
                    $expandNums = methods::expand($parts);
                    $level = array();
                    if (in_array($back3, $expandNums)) {
                        $level[] = 1;
                    }

                    array_shift($parts);    //再判断是否二等奖
                    $expandNums = methods::expand($parts);
                    if (in_array($back2, $expandNums)) {
                        $level[] = 2;
                    }

                    $tmp = array_pop($parts);   //再判断是否有三等奖
                    if (strpos($tmp, $back1) !== false) {
                        $level[] = 3;
                    }
                    break;
                case 'SXBD':    //三星包点 一段 0_1_2_3_4_..._27 买的时候可以一次选多个号码，因为有大于9的结果 豹子按直选派奖，组三态按组三派奖，组六态按组六派奖
                    // 包点与和值的区别：包点只算组合，不排列，和值相当于排列，按转直注数计，如包点3相当于3注：003，012，111，按开奖号码形态派奖
                    // 和值3相当于把包点的号码转直：003组三态转成3注，012组六态转成6注，111豹子转成1注，因此相当于买10注直选
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['SXHZ']) {
                            if ($helpData['SX'] == '组六') {
                                $level = 3;
                            } elseif ($helpData['SX'] == '组三') {
                                $level = 2;
                            } elseif ($helpData['SX'] == '豹子') {
                                $level = 1;
                            }
                        }
                    }
                    break;
                case 'SXHHZX':    //三星混合组选 仅支持单式手工录入 组三态按组三派奖，组六态按组六派奖
                    $back3Parts = str_split($back3);
                    $tmp = array_values(array_unique($back3Parts)); //522 122 121 211 112
                    $codeParts = explode(',', $code);   //252
                    $codeTmp = array_values(array_unique($codeParts));  // 12
                    sort($back3Parts);
                    sort($codeParts);
                    //按开奖号码形态分别判断
                    if (count($tmp) == 2 && count($codeTmp) == 2) {    //如果不是组三态显然没中奖
                        if ($codeParts[0] === $back3Parts[0] && $codeParts[1] === $back3Parts[1] && $codeParts[2] === $back3Parts[2]) {
                            $level = 1;
                        }
                    } elseif (count($tmp) == 3 && count($codeTmp) == 3) {
                        if ($codeParts[0] === $back3Parts[0] && $codeParts[1] === $back3Parts[1] && $codeParts[2] === $back3Parts[2]) {
                            $level = 2;
                        }
                    }
                    break;

                // 时时彩玩法

                // 时时彩总和
                case 'SSC-ZH':
                    # TODO :
                    break;
                // 时时彩龙虎
                case 'SSC-LH':
                    # TODO :
                    break;
                // 时时彩两面大小单双
                case 'SSC-LMDXDS':
                    $level = [];
                    foreach (explode(',', $code) as $key => $bets) {
                        $openItem = (int)$prize_code[$key];
                        $isBig = $openItem >= 5;
                        $isOdd = $openItem % 2 > 0;

                        for ($i = 0, $len = mb_strlen($bets, 'utf-8'); $i < $len; ++$i) {
                            $bet = mb_substr($bets, $i, 1, 'utf-8');
                            // p($openItem,$bet);
                            if (
                                ($isBig && $bet === '大') ||
                                (!$isBig && $bet === '小') ||
                                ($isOdd && $bet === '单') ||
                                (!$isOdd && $bet === '双')
                            ) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                // 时时彩前三形态
                case 'SSC-QSXT':
                    # TODO :
                    break;
                // 时时彩中三形态
                case 'SSC-ZSXT':
                    # TODO :
                    break;
                // 时时彩后三形态
                case 'SSC-HSXT':
                    # TODO :
                    break;

                //前三
                case 'QSZX':    //前三直选
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$fore3[0]}\d*,\d*{$fore3[1]}\d*,\d*{$fore3[2]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;
                case 'QSZS':    //前三组三
                    $parts = str_split($fore3);
                    //如果不是组三态显然没中奖
                    $tmp = array_values(array_unique($parts));
                    if (count($tmp) == 2) {
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'QSZL':    //前三组六
                    $parts = str_split($fore3);
                    $tmp = array_unique($parts);
                    if (count($tmp) == 3) { //首先要是组六态
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false && strpos($code, $tmp[2]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'QSLX':    //前三连选 三段 0123456789,0123456789,0123456789 复式 全包1110注
                    //兼中兼得(即可同时中一二三等奖) 万千百 千百 百 分别对应123等奖
                    $parts = explode(',', $code);
                    $expandNums = methods::expand($parts);
                    $level = array();
                    if (in_array($fore3, $expandNums)) {
                        $level[] = 1;
                    }

                    array_shift($parts);    //再判断是否二等奖
                    $expandNums = methods::expand($parts);
                    if (in_array($foreTH, $expandNums)) {
                        $level[] = 2;
                    }

                    $tmp = array_pop($parts);   //再判断是否有三等奖
                    if (strpos($tmp, $foreH) !== false) {
                        $level[] = 3;
                    }
                    break;
                case 'QSBD':
                    // 包点与和值的区别：包点只算组合，不排列，和值相当于排列，按转直注数计，如包点3相当于3注：003，012，111，按开奖号码形态派奖
                    // 和值3相当于把包点的号码转直：003组三态转成3注，012组六态转成6注，111豹子转成1注，因此相当于买10注直选
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['QSHZ']) {
                            if ($helpData['QS'] == '组六') {
                                $level = 3;
                            } elseif ($helpData['QS'] == '组三') {
                                $level = 2;
                            } elseif ($helpData['QS'] == '豹子') {
                                $level = 1;
                            }
                        }
                    }
                    break;
                case 'QSHHZX':    //前三混合组选 仅支持单式手工录入 组三态按组三派奖，组六态按组六派奖
                    $fore3Parts = str_split($fore3);
                    $tmp = array_values(array_unique($fore3Parts));
                    $codeParts = explode(',', $code);
                    $codeTmp = array_values(array_unique($codeParts));
                    sort($fore3Parts);
                    sort($codeParts);
                    //按开奖号码形态分别判断
                    if (count($tmp) == 2 && count($codeTmp) == 2) {    //如果不是组三态显然没中奖
                        if ($codeParts[0] === $fore3Parts[0] && $codeParts[1] === $fore3Parts[1] && $codeParts[2] === $fore3Parts[2]) {
                            $level = 1;
                        }
                    } elseif (count($tmp) == 3 && count($codeTmp) == 3) {
                        if ($codeParts[0] === $fore3Parts[0] && $codeParts[1] === $fore3Parts[1] && $codeParts[2] === $fore3Parts[2]) {
                            $level = 2;
                        }
                    }
                    break;
                case 'ZSZX':    //中三直选 三段 0123456789,0123456789,0123456789 复式 1000
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$mid3[0]}\d*,\d*{$mid3[1]}\d*,\d*{$mid3[2]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;
                case 'ZSZS':    //中三组三 一段 0123456789 复式90注 只要有2个数字相同即为中奖
                    $mid3Parts = str_split($mid3);
                    //如果不是组三态显然没中奖
                    $tmp = array_values(array_unique($mid3Parts));
                    if (count($tmp) == 2) {
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'ZSZL':    //中三组六 一段 0123456789 复式120注
                    $mid3Parts = str_split($mid3);
                    $tmp = array_unique($mid3Parts);
                    if (count($tmp) == 3) { //首先要是组六态
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false && strpos($code, $tmp[2]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'ZSLX':    //中三连选 三段 0123456789,0123456789,0123456789 复式 全包1110注
                    //兼中兼得(即可同时中一二三等奖) 千百十 百十 十 分别对应123等奖
                    $parts = explode(',', $code);
                    $expandNums = methods::expand($parts);
                    $level = array();
                    if (in_array($mid3, $expandNums)) {
                        $level[] = 1;
                    }

                    array_shift($parts);    //再判断是否二等奖
                    $expandNums = methods::expand($parts);
                    if (in_array($mid2, $expandNums)) {
                        $level[] = 2;
                    }

                    $tmp = array_pop($parts);   //再判断是否有三等奖
                    if (strpos($tmp, $mid1) !== false) {
                        $level[] = 3;
                    }
                    break;
                case 'ZSBD':    // 中三包点
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['ZSHZ']) {
                            if ($helpData['ZS'] == '组六') {
                                $level = 3;
                            } elseif ($helpData['ZS'] == '组三') {
                                $level = 2;
                            } elseif ($helpData['ZS'] == '豹子') {
                                $level = 1;
                            }
                        }
                    }
                    break;
                case 'ZSHHZX':    //中三混合组选
                    $mid3Parts = str_split($mid3);
                    $tmp = array_values(array_unique($mid3Parts));
                    $codeParts = explode(',', $code);
                    $codeTmp = array_values(array_unique($codeParts));
                    sort($mid3Parts);
                    sort($codeParts);
                    //按开奖号码形态分别判断
                    if (count($tmp) == 2 && count($codeTmp) == 2) {    //如果不是组三态显然没中奖
                        if ($codeParts[0] === $mid3Parts[0] && $codeParts[1] === $mid3Parts[1] && $codeParts[2] === $mid3Parts[2]) {
                            $level = 1;
                        }
                    } elseif (count($tmp) == 3 && count($codeTmp) == 3) {
                        if ($codeParts[0] === $mid3Parts[0] && $codeParts[1] === $mid3Parts[1] && $codeParts[2] === $mid3Parts[2]) {
                            $level = 2;
                        }
                    }
                    break;

                //二星
                case 'EXZX':    //二星直选 0123456789,0123456789
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$back2[0]}\d*,\d*{$back2[1]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;
                case 'EXZUX':    //二星组选 0123456789 yb限制最多只能买7个号码
                    $back2Parts = str_split($back2);
                    $tmp = array_unique($back2Parts);
                    if (count($tmp) == 2) { //首先要是不同号码才可能中奖
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'EXLX':    //二星连选 0123456789,0123456789 全包110注
                    //兼中兼得(即可同时中一二等奖) 十个 个 分别对应12等奖
                    $parts = explode(',', $code);
                    $expandNums = methods::expand($parts);
                    $level = array();
                    if (in_array($back2, $expandNums)) {
                        $level[] = 1;
                    }

                    $tmp = array_pop($parts);   //再判断是否二等奖 仅个位相同
                    if (strpos($tmp, $back1) !== false) {
                        $level[] = 2;
                    }
                    break;
                case 'EXBD':    //二星包点 一段 0_1_2_3_4_..._18 买的时候可以一次选多个号码，因为有大于9的结果 对子按直选派奖，非对子按组选派奖
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['EXHZ']) {
                            if ($helpData['EX'] == '非对子') {
                                $level = 2;
                            } elseif ($helpData['EX'] == '对子') {
                                $level = 1;
                            }
                        }
                    }
                    break;

                //前二
                case 'QEZX':    //前二直选
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$fore2[0]}\d*,\d*{$fore2[1]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;
                case 'QEZUX':   //前二组选
                    $parts = str_split($fore2);
                    $tmp = array_unique($parts);
                    if (count($tmp) == 2) { //首先要是不同号码才可能中奖
                        if (strpos($code, $tmp[0]) !== false && strpos($code, $tmp[1]) !== false) {
                            $level = 1;
                        }
                    }
                    break;
                case 'QELX':    //前二连选 0123456789,0123456789 全包110注
                    //兼中兼得(即可同时中一二等奖) 万千 千 分别对应12等奖
                    $parts = explode(',', $code);
                    $expandNums = methods::expand($parts);
                    $level = array();
                    if (in_array($fore2, $expandNums)) {
                        $level[] = 1;
                    }

                    $tmp = array_pop($parts);   // 再判断是否二等奖 仅千位相同
                    if (strpos($tmp, $fore21) !== false) {
                        $level[] = 2;
                    }
                    break;
                case 'QEBD':
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['QEHZ']) {
                            if ($helpData['QE'] == '非对子') {
                                $level = 2;
                            } elseif ($helpData['QE'] == '对子') {
                                $level = 1;
                            }
                        }
                    }
                    break;

                //一星
                case 'YXZX':    //一星直选 个位 0123456789
                    if (strpos($code, $back1) !== false) {
                        $level = 1;
                    }
                    break;
                case 'WXDW':    //低频P3P5也有 五星定位 0123456789,0123456789,0123456789,0123456789,0123456789 或,2,3,,都是合法的
                    $prizeParts = str_split($prize_code);
                    $level = array();
                    $parts = explode(',', $code);

                    if (strpos($parts[0], $prizeParts[0]) !== false) {
                        $level[] = 1;
                    }
                    if (strpos($parts[1], $prizeParts[1]) !== false) {
                        $level[] = 1;
                    }
                    if (strpos($parts[2], $prizeParts[2]) !== false) {
                        $level[] = 1;
                    }
                    if (strpos($parts[3], $prizeParts[3]) !== false) {
                        $level[] = 1;
                    }
                    if (strpos($parts[4], $prizeParts[4]) !== false) {
                        $level[] = 1;
                    }
                    break;
                case 'SXDW':    //低频3D特有 三星定位 0123456789,0123456789,0123456789 或,2,3都是合法的
                    $prizeParts = str_split($prize_code);
                    $level = array();
                    $parts = explode(',', $code);
                    if (strpos($parts[0], $prizeParts[0]) !== false) {
                        $level[] = 1;
                    }
                    if (strpos($parts[1], $prizeParts[1]) !== false) {
                        $level[] = 1;
                    }
                    if (strpos($parts[2], $prizeParts[2]) !== false) {
                        $level[] = 1;
                    }
                    break;

                //不定位
                case 'YMBDW':   //后三一码不定位 3D一码不定位
                    $back3Parts = array_unique(str_split($back3));
                    $betCodes = str_split($code);
                    $level = array();
                    foreach ($betCodes as $v) {
                        if (in_array($v, $back3Parts)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'EMBDW':   //后三二码不定位 3D二码不定位
                    $prizeCodes = array_unique(str_split($back3));
                    sort($prizeCodes);
                    $num = count($prizeCodes);
                    $level = array();
                    if ($num != 1) {
                        $prizeCodes = methods::C($prizeCodes, 2);
                        $betCodes = methods::C(str_split($code), 2);
                        foreach ($betCodes as $v) {
                            if (in_array($v, $prizeCodes)) {
                                $level[] = 1;
                            }
                        }
                    }

                    break;
                case 'QSYMBDW':   //低频P3P5特有 前三一码不定位
                    $prizeCodes = array_unique(str_split($fore3));
                    $betCodes = str_split($code);
                    $level = array();
                    foreach ($betCodes as $v) {
                        if (in_array($v, $prizeCodes)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'QSEMBDW':   //低频P3P5特有 前三二码不定位
                    $prizeCodes = array_unique(str_split($fore3));
                    sort($prizeCodes);
                    $num = count($prizeCodes);
                    $level = array();
                    if ($num != 1) {
                        $prizeCodes = methods::C($prizeCodes, 2);
                        $betCodes = methods::C(str_split($code), 2);
                        foreach ($betCodes as $v) {
                            if (in_array($v, $prizeCodes)) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                case 'ZSYMBDW': //中三一码不定位
                    $prizeCodes = array_unique(str_split($mid3));
                    $betCodes = str_split($code);
                    $level = array();
                    foreach ($betCodes as $v) {
                        if (in_array($v, $prizeCodes)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'ZSEMBDW': //中三二码不定位
                    $prizeCodes = array_unique(str_split($mid3));
                    sort($prizeCodes);
                    $num = count($prizeCodes);
                    $level = array();
                    if ($num != 1) {
                        $prizeCodes = methods::C($prizeCodes, 2);
                        $betCodes = methods::C(str_split($code), 2);
                        foreach ($betCodes as $v) {
                            if (in_array($v, $prizeCodes)) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                case 'SXYMBDW': //四星一码不定位
                    $prizeCodes = array_unique(str_split($back4));
                    $betCodes = str_split($code);
                    $level = array();
                    foreach ($betCodes as $v) {
                        if (in_array($v, $prizeCodes)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SXEMBDW': //四星二码不定位
                    $prizeCodes = array_unique(str_split($back4));
                    sort($prizeCodes);
                    $num = count($prizeCodes);
                    $level = array();
                    if ($num != 1) {
                        $prizeCodes = methods::C($prizeCodes, 2);
                        $betCodes = methods::C(str_split($code), 2);
                        foreach ($betCodes as $v) {
                            if (in_array($v, $prizeCodes)) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                case 'WXYMBDW': //五星一码不定位
                    $prizeCodes = array_unique(str_split($prize_code));
                    $betCodes = str_split($code);
                    $level = array();
                    foreach ($betCodes as $v) {
                        if (in_array($v, $prizeCodes)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'WXEMBDW': //五星二码不定位
                    $prizeCodes = array_unique(str_split($prize_code));
                    sort($prizeCodes);
                    $num = count($prizeCodes);
                    $level = array();
                    if ($num != 1) {
                        $prizeCodes = methods::C($prizeCodes, 2);
                        $betCodes = methods::C(str_split($code), 2);
                        foreach ($betCodes as $v) {
                            if (in_array($v, $prizeCodes)) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                case 'WXSMBDW': //五星三码不定位
                    $prizeCodes = array_unique(str_split($prize_code));
                    sort($prizeCodes);
                    $num = count($prizeCodes);
                    $level = array();
                    if ($num >= 3) {
                        $prizeCodes = methods::C($prizeCodes, 3);
                        $betCodes = methods::C(str_split($code), 3);
                        foreach ($betCodes as $v) {
                            if (in_array($v, $prizeCodes)) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                //大小单双
                case 'SXDXDS':    //三星大小单双 只能是单式 projects存的时候就应该是1,3,4样式
                    $tmp = str_replace(',', '', $code);
                    if (in_array($tmp, $helpData['SXDXDS'])) {
                        $level = 1;
                    }
                    break;
                case 'EXDXDS':    //二星大小单双
                    $tmp = str_replace(',', '', $code);
                    if (in_array($tmp, $helpData['EXDXDS'])) {
                        $level = 1;
                    }
                    break;
                case 'QEDXDS':    //低频3D P3P5特有 前二大小单双
                    $tmp = str_replace(',', '', $code);
                    if (in_array($tmp, $helpData['QEDXDS'])) {
                        $level = 1;
                    }
                    break;
                case 'SXHZ':    //三星和值
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['SXHZ']) {
                            $level = 1;
                        }
                    }
                    break;
                case 'EXHZ':    //二星和值
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['EXHZ']) {
                            $level = 1;
                        }
                    }
                    break;
                case 'QSHZ':    //前三和值
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['QSHZ']) {
                            $level = 1;
                        }
                    }
                    break;
                case 'ZSHZ':    //中三和值
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['ZSHZ']) {
                            $level = 1;
                        }
                    }
                    break;
                case 'QEHZ':    //前二和值
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['QEHZ']) {
                            $level = 1;
                        }
                    }
                    break;
                case 'SXZXHZ':  //低频3D特有 三星组选和值 豹子号除外，组三态按组三派奖，组六态按组六派奖
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['SXHZ']) {
                            if ($helpData['SX'] == '组六') {
                                $level = 2;
                            } elseif ($helpData['SX'] == '组三') {
                                $level = 1;
                            }
                        }
                    }
                    break;
                case 'QSZXHZ':  //低频P3P5特有 P3即前三组选和值 豹子号除外，组三态按组三派奖，组六态按组六派奖
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $helpData['QSHZ']) {
                            if ($helpData['QS'] == '组六') {
                                $level = 2;
                            } elseif ($helpData['QS'] == '组三') {
                                $level = 1;
                            }
                        }
                    }
                    break;

                //四星
                case 'SIXZX':    //四星直选
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$back4[0]}\d*,\d*{$back4[1]}\d*,\d*{$back4[2]}\d*,\d*{$back4[3]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;
                case 'QSIZX':    //前四直选
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$fore4[0]}\d*,\d*{$fore4[1]}\d*,\d*{$fore4[2]}\d*,\d*{$fore4[3]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;

                //五星
                case 'WXZX':    //五星直选
                    //160104 经测试，正则是最快方案
                    if (preg_match("`\d*{$prize_code[0]}\d*,\d*{$prize_code[1]}\d*,\d*{$prize_code[2]}\d*,\d*{$prize_code[3]}\d*,\d*{$prize_code[4]}\d*`", $code)) {
                        $level = 1;
                    }
                    break;
                case 'WXLX':    //五星连选 0123456789,0123456789,0123456789,0123456789,0123456789 全包101110注
                    //兼中兼得(即可同时中一二三四等奖)万千百十个 百十个 十个 个 分别对应1234等奖
                    $parts = explode(',', $code);
                    $expandNums = methods::expand($parts);
                    $level = array();
                    if (in_array($prize_code, $expandNums)) {
                        $level[] = 1;
                    }

                    $parts = array_slice($parts, 2);    //再判断是否中三星（二等奖）
                    $expandNums = methods::expand($parts);
                    if (in_array($back3, $expandNums)) {
                        $level[] = 2;
                    }

                    array_shift($parts);    //再判断是否中二星（三等奖）
                    $expandNums = methods::expand($parts);
                    if (in_array($back2, $expandNums)) {
                        $level[] = 3;
                    }

                    $tmp = array_pop($parts);   //再判断是否中一星（四等奖）
                    if (strpos($tmp, $back1) !== false) {
                        $level[] = 4;
                    }
                    break;
                case 'REZX':     //任二直选	-,-,6,4,7
                    //20150520-644	92464	1020.0000已中奖			01234,12345,23456,34567,45678
                    $codeParts = explode(',', $code);
                    $level = array();
                    //方法二
                    $selected = 0;
                    for ($i = 0; $i < 5; $i++) {
                        if ($codeParts[$i] != '-') {
                            if (strpos($codeParts[$i], $prize_code[$i]) !== false) {
                                $selected++;
                            }
                        }
                    }

                    switch ($selected) {
                        case 2: //C(2,2)
                            $level = array(1);
                            break;
                        case 3: //C(3,2)
                            $level = array(1, 1, 1);
                            break;
                        case 4: //C(4,2)
                            $level = array(1, 1, 1, 1, 1, 1);
                            break;
                        case 5: //C(5,2)
                            $level = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
                            break;
                        default:
                            break;
                    }

//                	//方法一 效率低弃用
//                    $n = 4; //5!
//                    for ($i = 0; $i < $n; $i++) {
//                    	if ($codeParts[$i] != '-') {
//                    		for ($j = $i; $j < $n; $j++) {
//                    			if ($codeParts[$j+1] != '-') {
//                    				//当前位置的中奖号 最多10个
//                    				$curPosPrizeCode = $prize_code[$i].$prize_code[$j+1];
//                    				//A*B 严格按位置展开的投注
//                    				$extendNums = self::expandByOrder($codeParts[$i], $codeParts[$j+1]);
//                    				if (in_array($curPosPrizeCode, $extendNums)) {
//                    					$level[] = 1;
//                    				}
//                    			}
//                    		}
//                    	}
//                    }
                    break;
                case 'RSZX':     //任三直选	-,-,6,4,7
                    $codeParts = explode(',', $code);
                    $level = array();

                    $selected = 0;
                    for ($i = 0; $i < 5; $i++) {
                        if ($codeParts[$i] != '-') {
                            if (strpos($codeParts[$i], $prize_code[$i]) !== false) {
                                $selected++;
                            }
                        }
                    }

                    switch ($selected) {
                        case 3: //C(3,3)
                            $level = array(1);
                            break;
                        case 4: //C(4,3)
                            $level = array(1, 1, 1, 1);
                            break;
                        case 5: //C(5,3)
                            $level = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
                            break;
                        default:
                            ;
                            break;
                    }
                    break;
                case 'RSIZX':     //任四直选 13,-,645,46,777
                    $codeParts = explode(',', $code);
                    $level = array();

                    $selected = 0;
                    for ($i = 0; $i < 5; $i++) {
                        if ($codeParts[$i] != '-') {
                            if (strpos($codeParts[$i], $prize_code[$i]) !== false) {
                                $selected++;
                            }
                        }
                    }

                    switch ($selected) {
                        case 4:
                            $level = array(1);
                            break;
                        case 5: //C(5,4)
                            $level = array(1, 1, 1, 1, 1);
                            break;
                        default:
                            ;
                            break;
                    }
                    break;
                //
                // 中奖判断SD11Y   SD11Y   SD11Y   SD11Y   SD11Y   SD11Y   SD11Y   SD11Y
                //
                case 'SDQSZX':  //前三直选 01_02_03_04,02_03,01_05
                    $expandNums = methods::expandLotto(explode(',', $code));
                    if (in_array($sdfore3, $expandNums)) {
                        $level = 1;
                    }
                    break;
                case 'SDQSZUX':     //前三组选 01_02_03_04 4注 只需逐个判断中奖号码都在里面即可
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $sdfore3);
                    $count = 0;
                    foreach ($prizeParts as $v) {
                        if (in_array($v, $codeParts)) {
                            $count++;
                        }
                    }
                    if ($count == 3) {
                        $level = 1;
                    }
                    break;

                // 这里是一个后五写在前面 一个前五写在后面 注意魂顺序
                // 这样就只需写一个偏移量就好
                case 'PKS-H5LM':
                    $offset = 5;
                case 'PKS-Q5LM':
                    $level = [];
                    empty($offset) && $offset = 0;
                    $openCodeList = explode(' ', $prize_code);
                    foreach (explode(',', $code) as $key => $bets) {
                        $openItem = (int)$openCodeList[$key + $offset];
                        $isBig = $openItem > 5;
                        $isOdd = $openItem % 2 > 0;

                        for ($i = 0, $len = mb_strlen($bets, 'utf-8'); $i < $len; ++$i) {
                            $bet = mb_substr($bets, $i, 1, 'utf-8');

                            // p($openItem, $bet);

                            if (
                                ($isBig && $bet === '大') ||
                                (!$isBig && $bet === '小') ||
                                ($isOdd && $bet === '单') ||
                                (!$isOdd && $bet === '双')
                            ) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                case 'PKS-GYDXDS':
                    $level = [];
                    $prizeCodeArr = explode(' ', $prize_code);
                    $gyhz = $prizeCodeArr[0] + $prizeCodeArr[1];
                    preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $code, $expandNums);
                    $bigSmall = $gyhz > 10 ? '大' : '小';
                    $evenOdd = $gyhz % 2 == 0 ? '双' : '单';
                    foreach ($expandNums[0] as $v) {
                        if ($v == $bigSmall || $v == $evenOdd) {
                            switch ($v) {
                                case '大':
                                    $level[] = 1;
                                    break;
                                case '小':
                                    $level[] = 2;
                                    break;
                                case '单':
                                    $level[] = 3;
                                    break;
                                case '双':
                                    $level[] = 4;
                                    break;
                            }
                        }
                    }
                    break;
                case 'PKS-GYHZ':
                    $expandNums = explode('_', $code);
                    $prizeCodeArr = explode(' ', $prize_code);
                    $gyhz = $prizeCodeArr[0] + $prizeCodeArr[1];
                    foreach ($expandNums as $v) {
                        if ($v == $gyhz) {
                            switch ($v) {
                                case 3:
                                case 4:
                                case 18:
                                case 19:
                                    $level = 1;
                                    break;
                                case 5:
                                case 6:
                                case 16:
                                case 17:
                                    $level = 2;
                                    break;
                                case 7:
                                case 8:
                                case 14:
                                case 15:
                                    $level = 3;
                                    break;
                                case 9:
                                case 10:
                                case 12:
                                case 13:
                                    $level = 4;
                                    break;
                                case 11:
                                    $level = 5;
                                    break;
                            }
                        }
                    }
                    break;
                case 'PKS-CGJ':
                    $expandNums = explode('_', $code);
                    if (in_array($pksfore1, $expandNums)) {
                        $level = 1;
                    }
                    break;
                case 'PKS-CQE':
                    $expandNums = methods::expandLotto(explode(',', $code));
                    if (in_array($pksfore2, $expandNums)) {
                        $level = 1;
                    }
                    break;
                case 'PKS-CQS':
                    $expandNums = methods::expandLotto(explode(',', $code));
                    if (in_array($pksfore3, $expandNums)) {
                        $level = 1;
                    }
                    break;
                case 'PKS-DWD':
                    $codeParts = explode(',', $code);
                    $prizeParts = explode(' ', $pksfore5);
                    $level = array();
                    foreach ($codeParts as $k => $v) {
                        if ($v != '') {
                            if (in_array($prizeParts[$k], explode('_', $v))) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                case 'PKS-DWDH5':
                    $codeParts = explode(',', $code);
                    $prizeParts = explode(' ', $pksback5);
                    $level = array();
                    foreach ($codeParts as $k => $v) {
                        if ($v != '') {
                            if (in_array($prizeParts[$k], explode('_', $v))) {
                                $level[] = 1;
                            }
                        }
                    }
                    break;
                case 'PKS-OT':
                    $prizdCodes = explode(' ', $prize_code);
                    $res = $prizdCodes[0] > $prizdCodes[9] ? '龙' : '虎';
                    if ($res == $code) {
                        $level = 1;
                    }
                    break;
                case 'PKS-TN':
                    $prizdCodes = explode(' ', $prize_code);
                    $res = $prizdCodes[1] > $prizdCodes[8] ? '龙' : '虎';
                    if ($res == $code) {
                        $level = 1;
                    }
                    break;
                case 'PKS-TE':
                    $prizdCodes = explode(' ', $prize_code);
                    $res = $prizdCodes[2] > $prizdCodes[7] ? '龙' : '虎';
                    if ($res == $code) {
                        $level = 1;
                    }
                    break;
                case 'PKS-FS':
                    $prizdCodes = explode(' ', $prize_code);
                    $res = $prizdCodes[3] > $prizdCodes[6] ? '龙' : '虎';
                    if ($res == $code) {
                        $level = 1;
                    }
                    break;
                case 'PKS-FSIX':
                    $prizdCodes = explode(' ', $prize_code);
                    $res = $prizdCodes[4] > $prizdCodes[5] ? '龙' : '虎';
                    if ($res == $code) {
                        $level = 1;
                    }
                    break;
                case 'SDQEZX':     //前二直选
                    $expandNums = methods::expandLotto(explode(',', $code));
                    if (in_array($sdfore2, $expandNums)) {
                        $level = 1;
                    }
                    break;
                case 'SDQEZUX':     //前二组选
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $sdfore2);
                    $count = 0;
                    foreach ($prizeParts as $v) {
                        if (in_array($v, $codeParts)) {
                            $count++;
                        }
                    }
                    if ($count == 2) {
                        $level = 1;
                    }
                    break;

                //任选 中奖判断
                case 'SDRX1':     //任选一 01_02_03 复式 可以中多注，一单最多可中5注
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $count = 0;
                    foreach ($codeParts as $v) {
                        if (in_array($v, $prizeParts)) {
                            $count++;   //$count表示中了几注
                        }
                    }

                    if ($count > 0) {
                        $level = array();
                        for ($i = $count; $i > 0; $i--) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SDRX2':     //任选2 复式 需要按Cn_2展开，再逐一判断是否中奖，因为中奖是5个里面任意2个相同，所以一单最多中C5_2=10注
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $expandNums = methods::C($codeParts, 2, ' ');
                    $count = 0;
                    foreach ($expandNums as $v) {
                        $tmp = explode(' ', $v);
                        if (in_array($tmp[0], $prizeParts) && in_array($tmp[1], $prizeParts)) {
                            $count++;
                        }
                    }

                    if ($count > 0) {
                        $level = array();
                        for ($i = $count; $i > 0; $i--) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SDRX3':     //任选3 复式 需要按Cn_3展开，再逐一判断是否中奖，因为中奖是5个里面任意2个相同，所以一单最多中C5_3=10注
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $expandNums = methods::C($codeParts, 3, ' ');
                    $count = 0;
                    foreach ($expandNums as $v) {
                        $tmp = explode(' ', $v);
                        if (in_array($tmp[0], $prizeParts) && in_array($tmp[1], $prizeParts) && in_array($tmp[2], $prizeParts)) {
                            $count++;
                        }
                    }

                    if ($count > 0) {
                        $level = array();
                        for ($i = $count; $i > 0; $i--) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SDRX4':     //任选4
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $expandNums = methods::C($codeParts, 4, ' ');
                    $count = 0;
                    foreach ($expandNums as $v) {
                        $tmp = explode(' ', $v);
                        if (in_array($tmp[0], $prizeParts) && in_array($tmp[1], $prizeParts) && in_array($tmp[2], $prizeParts) && in_array($tmp[3], $prizeParts)) {
                            $count++;
                        }
                    }

                    if ($count > 0) {
                        $level = array();
                        for ($i = $count; $i > 0; $i--) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SDRX5':     //任选5 只可能中一个奖
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $expandNums = methods::C($codeParts, 5, ' ');
                    foreach ($expandNums as $v) {
                        $tmp = explode(' ', $v);
                        if (in_array($tmp[0], $prizeParts) && in_array($tmp[1], $prizeParts) && in_array($tmp[2], $prizeParts) && in_array($tmp[3], $prizeParts) && in_array($tmp[4], $prizeParts)) {
                            $level = 1;
                        }
                    }
                    break;
                case 'SDRX6':     //任选6 复式 需先得到Cn_6，再得到C6_5，再和任选5一样的判断 也可按Cn-5_1算中奖注数
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $expandNums = methods::C($codeParts, 6, ' ');
                    $expandNums2 = array();
                    foreach ($expandNums as $v) {
                        $tmp = methods::C(explode(' ', $v), 5, ' ');
                        foreach ($tmp as $vv) {
                            $expandNums2[] = $vv;
                        }
                    }

                    $level = array();
                    foreach ($expandNums2 as $v) {
                        $tmp = explode(' ', $v);
                        if (in_array($tmp[0], $prizeParts) && in_array($tmp[1], $prizeParts) && in_array($tmp[2], $prizeParts) && in_array($tmp[3], $prizeParts) && in_array($tmp[4], $prizeParts)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SDRX7':     //任选7 复式 需先得到Cn_7，再得到C7_5，再和任选5一样的判断 也可按Cn-5_2算中奖注数
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $expandNums = methods::C($codeParts, 7, ' ');
                    $expandNums2 = array();
                    foreach ($expandNums as $v) {
                        $tmp = methods::C(explode(' ', $v), 5, ' ');
                        foreach ($tmp as $vv) {
                            $expandNums2[] = $vv;
                        }
                    }

                    $level = array();
                    foreach ($expandNums2 as $v) {
                        $tmp = explode(' ', $v);
                        if (in_array($tmp[0], $prizeParts) && in_array($tmp[1], $prizeParts) && in_array($tmp[2], $prizeParts) && in_array($tmp[3], $prizeParts) && in_array($tmp[4], $prizeParts)) {
                            $level[] = 1;
                        }
                    }
                    break;
                case 'SDRX8':     //任选8 复式 需先得到Cn_8，再得到C8_5，再和任选5一样的判断 也可按Cn-5_3算中奖注数
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    $expandNums = methods::C($codeParts, 8, ' ');
                    $expandNums2 = array();
                    foreach ($expandNums as $v) {
                        $tmp = methods::C(explode(' ', $v), 5, ' ');
                        foreach ($tmp as $vv) {
                            $expandNums2[] = $vv;
                        }
                    }

                    $level = array();
                    foreach ($expandNums2 as $v) {
                        $tmp = explode(' ', $v);
                        if (in_array($tmp[0], $prizeParts) && in_array($tmp[1], $prizeParts) && in_array($tmp[2], $prizeParts) && in_array($tmp[3], $prizeParts) && in_array($tmp[4], $prizeParts)) {
                            $level[] = 1;
                        }
                    }
                    break;

                //前3不定位胆 判断
                case 'SDQSBDW':     //前3不定位胆
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $sdfore3);
                    $count = 0;
                    foreach ($codeParts as $v) {
                        if (in_array($v, $prizeParts)) {
                            $count++;
                        }
                    }

                    if ($count > 0) {
                        $level = array();
                        for ($i = $count; $i > 0; $i--) {
                            $level[] = 1;
                        }
                    }
                    break;
                //前3定位胆 判断
                case 'SDQSDWD':     //前3定位胆 01_02_03,04_05,06为一单 也可以只买一位，如01_02_03,,表示只买个位胆，没买的位留空
                    $codeParts = explode(',', $code);
                    $prizeParts = explode(' ', $sdfore3);
                    $count = 0;
                    foreach ($codeParts as $k => $v) {
                        if ($v != '') {
                            if (in_array($prizeParts[$k], explode('_', $v))) {
                                $count++;
                            }
                        }
                    }

                    if ($count > 0) {
                        $level = array();
                        for ($i = $count; $i > 0; $i--) {
                            $level[] = 1;
                        }
                    }
                    break;

                //定单双
                case 'SDDDS':     //0单5双:750.0000元 (1注) 5单0双:125.0000元 (6注)1单4双:25.0000元 (30注)4单1双:10.0000元 (75注)2单3双:5.0000元 (150注)3单2双:3.7000元 (200注)
                    //projects表中用012345表示0~5单(5-0~5)双 复式 只可能中一个奖
                    //$parts = str_split($code);
                    //改动: projects表中拟用中文直接表示注单
                    $prizeParts = explode(' ', $prize_code);
                    $oddNum = 0;    //记录有几个单数
                    foreach ($prizeParts as $v) {
                        if (intval($v) % 2 == 1) {
                            $oddNum++;
                        }
                    }

                    if (intval($code) == $oddNum) {

                    }

                    if (intval($code) == $oddNum) {
                        switch ($oddNum) {
                            case '0':   //0单5双 一等奖750元
                                $level = 1;
                                break;
                            case '1':   //1单4双 三等奖25元
                                $level = 3;
                                break;
                            case '2':   //2单3双 五等奖5元
                                $level = 5;
                                break;
                            case '3':   //3单2双 六等奖3.7元
                                $level = 6;
                                break;
                            case '4':   //4单1双 四等奖10元
                                $level = 4;
                                break;
                            case '5':   //5单0双 二等奖125元
                                $level = 2;
                                break;
                        }
                    }
                    break;
                //猜中位
                case 'SDCZW':     //03_04_05_06_07_08_09 全买 复式 只可能中一个奖
                    $codeParts = explode('_', $code);
                    $prizeParts = explode(' ', $prize_code);
                    sort($prizeParts);
                    if (in_array($prizeParts[2], $codeParts)) {
                        switch ($prizeParts[2]) {
                            case '03':  //一等奖27元
                            case '09':
                                $level = 1;
                                break;
                            case '04':  //二等奖12元
                            case '08':
                                $level = 2;
                                break;
                            case '05':  //三等奖8.4元
                            case '07':
                                $level = 3;
                                break;
                            case '06':  //四等奖7.5元
                                $level = 4;
                                break;
                        }
                    }
                    break;

                case 'YFFS':    //趣味玩法,一帆风顺
                case 'HSCS':    //趣味玩法,好事成双
                case 'SXBX':    //趣味玩法,三星报喜
                case 'SJFC':    //趣味玩法,四季发财
                    $level = array();
                    $times = array('YFFS' => 1, 'HSCS' => 2, 'SXBX' => 3, 'SJFC' => 4);
                    $prizeParts = str_split($code);
                    foreach ($prizeParts as $code4) {
                        if (substr_count($prize_code, $code4) >= $times[$method['name']]) {
                            $level[] = 1;
                        }
                    }
                    break;

                case 'ZUX120':      //组选120
                    $prizeParts = methods::C(str_split($code), 5);
                    $tmpPrizeCode = methods::strOrder($prize_code);
                    foreach ($prizeParts as $code4) {
                        if ($code4 === $tmpPrizeCode) {
                            $level = 1;
                            break;
                        }
                    }
                    break;
                case 'ZUX24':      //组选24
                    $prizeParts = methods::C(str_split($code), 4);
                    $tmpPrizeCode = methods::strOrder(substr($prize_code, 1, 4));
                    foreach ($prizeParts as $code4) {
                        if ($code4 === $tmpPrizeCode) {
                            $level = 1;
                            break;
                        }
                    }
                    break;
                case 'ZUX6':      //组选6
                    $prizeParts = methods::C(str_split($code), 2);
                    $tmpPrizeCode = methods::strOrder(substr($prize_code, 1, 4));
                    foreach ($prizeParts as $code4) {
                        if (methods::strOrder(str_repeat($code4, 2)) === $tmpPrizeCode) {
                            $level = 1;
                            break;
                        }
                    }
                    break;

                case 'ZUX60':    //组选60
                    $parts = explode(',', $code);
                    $parts[0] = str_split($parts[0]);
                    $parts[1] = methods::C(str_split($parts[1]), 3);
                    $tmpPrizeCode = methods::strOrder($prize_code);
                    foreach ($parts[0] as $value) {
                        foreach ($parts[1] as $val) {
                            if (strpos($val, $value) === false && $tmpPrizeCode === methods::strOrder($value . $value . $val)) {
                                $level = 1;
                                break 2;
                            }
                        }
                    }
                    break;

                case 'ZUX30':    //组选30
                    $parts = explode(',', $code);
                    $parts[1] = str_split($parts[1]);
                    $parts[0] = methods::C(str_split($parts[0]), 2);
                    $tmpPrizeCode = methods::strOrder($prize_code);
                    foreach ($parts[1] as $value) {
                        foreach ($parts[0] as $val) {
                            if (strpos($val, $value) === false && $tmpPrizeCode === methods::strOrder($value . str_repeat($val, 2))) {
                                $level = 1;
                                break 2;
                            }
                        }
                    }
                    break;

                case 'ZUX20':    //组选20
                    $parts = explode(',', $code);
                    $parts[0] = str_split($parts[0]);
                    $parts[1] = methods::C(str_split($parts[1]), 2);
                    $tmpPrizeCode = methods::strOrder($prize_code);
                    foreach ($parts[0] as $value) {
                        foreach ($parts[1] as $val) {
                            if (strpos($val, $value) === false && $tmpPrizeCode === methods::strOrder($value . $value . $value . $val)) {
                                $level = 1;
                                break 2;
                            }
                        }
                    }
                    break;

                case 'ZUX10':    //组选10
                    $parts = explode(',', $code);
                    $parts[0] = str_split($parts[0]);
                    $parts[1] = str_split($parts[1]);
                    $tmpPrizeCode = methods::strOrder($prize_code);
                    foreach ($parts[0] as $value) {
                        foreach ($parts[1] as $val) {
                            if ($val !== $value && $tmpPrizeCode === methods::strOrder($value . $value . $value . $val . $val)) {
                                $level = 1;
                                break 2;
                            }
                        }
                    }
                    break;

                case 'ZUX5':    //组选5
                    $parts = explode(',', $code);
                    $parts[0] = str_split($parts[0]);
                    $parts[1] = str_split($parts[1]);
                    $tmpPrizeCode = methods::strOrder($prize_code);
                    foreach ($parts[0] as $value) {
                        foreach ($parts[1] as $val) {
                            if ($val !== $value && $tmpPrizeCode === methods::strOrder($value . $value . $value . $value . $val)) {
                                $level = 1;
                                break 2;
                            }
                        }
                    }
                    break;

                case 'ZUX12':    //组选12
                    $parts = explode(',', $code);
                    $parts[0] = str_split($parts[0]);
                    $parts[1] = methods::C(str_split($parts[1]), 2);
                    $tmpPrizeCode = methods::strOrder(substr($prize_code, 1, 4));
                    foreach ($parts[0] as $value) {
                        foreach ($parts[1] as $val) {
                            if (strpos($val, $value) === false && $tmpPrizeCode === methods::strOrder($value . $value . $val)) {
                                $level = 1;
                                break 2;
                            }
                        }
                    }
                    break;

                case 'ZUX4':    //组选4
                    $parts = explode(',', $code);
                    $parts[0] = str_split($parts[0]);
                    $parts[1] = str_split($parts[1]);
                    $tmpPrizeCode = methods::strOrder(substr($prize_code, 1, 4));
                    foreach ($parts[0] as $value) {
                        foreach ($parts[1] as $val) {
                            if ($val !== $value && $tmpPrizeCode === methods::strOrder($value . $value . $value . $val)) {
                                $level = 1;
                                break 2;
                            }
                        }
                    }
                    break;

                //江苏快三
                case 'JSETDX':  //二同单选 2个号区 22_33,14 开133，335
                    $parts = explode(',', $code);
                    $prizeParts = str_split($prize_code);
                    //如果不是组三态显然没中奖
                    $tmp = array_values(array_unique($prizeParts));
                    if (count($tmp) == 2) {
                        $tmpFlag0 = 0;
                        foreach (explode('_', $parts[0]) as $v) {
                            if (strpos($prize_code, $v) !== false) {
                                $tmpFlag0++;
                                break;
                            }
                        }
                        foreach (str_split($parts[1]) as $v) {
                            if (strpos($prize_code, $v) !== false) {
                                $tmpFlag0++;
                                break;
                            }
                        }
                        if ($tmpFlag0 == 2) {
                            $level = 1;
                        }
                    }
                    break;
                case 'JSETFX':  //二同复选 1个号区 11_22_33
                    $parts = explode('_', $code);
                    $prizeParts = str_split($prize_code);
                    //如果不是组三态显然没中奖
                    $tmp = array_values(array_unique($prizeParts));
                    if (count($tmp) == 2) {
                        $tmpFlag0 = 0;
                        foreach ($parts as $v) {
                            if (strpos($prize_code, $v) !== false) {
                                $tmpFlag0 = 1;
                                break;
                            }
                        }
                        if ($tmpFlag0 == 1) {
                            $level = 1;
                        }
                    }
                    break;
                case 'JSHZ':   //快三和值
                    $parts = explode('_', $code);
                    $prizeParts = str_split($prize_code);
                    $hz = array_sum($prizeParts);
                    foreach ($parts as $v) {
                        if ($v == $hz) {
                            switch ($hz) {
                                case 3:  //一等奖302.4
                                case 18:
                                    $level = 1;
                                    break;
                                case 4:  //二等奖100.8
                                case 17:
                                    $level = 2;
                                    break;
                                case 5:  //三等奖50.4
                                case 16:
                                    $level = 3;
                                    break;
                                case 6:  //四等奖30.24
                                case 15:
                                    $level = 4;
                                    break;
                                case 7:  //五等奖20.16
                                case 14:
                                    $level = 5;
                                    break;
                                case 8:  //六等奖14.4
                                case 13:
                                    $level = 6;
                                    break;
                                case 9:  //七等奖12.096
                                case 12:
                                    $level = 7;
                                    break;
                                case 10:  //八等奖11.2
                                case 11:
                                    $level = 8;
                                    break;
                            }
                        }
                    }
                    break;
                case 'JSEBT':   //二不同号
                    $parts = str_split($code);
                    $prizeParts = str_split($prize_code);
                    $hitTimes = 0;
                    foreach ($parts as $v) {
                        if (in_array($v, $prizeParts)) {
                            $hitTimes++;
                        }
                    }
                    if ($hitTimes == 2) {
                        $level = 1;
                    } elseif ($hitTimes == 3) {
                        $level = array(1, 1, 1);
                    }
                    break;
                case 'JSSTDX':   //三同号单选
                    $parts = explode('_', $code);
                    foreach ($parts as $v) {
                        if ($v == $prize_code) {
                            $level = 1;
                            break;
                        }
                    }
                    break;
                case 'JSSTTX':   //三同号通选
                    $possibleCodes = array('111', '222', '333', '444', '555', '666');
                    if (in_array($prize_code, $possibleCodes)) {
                        $level = 1;
                    }
                    break;
                case 'JSSBT':   //三不同号
                    $parts = str_split($code);
                    $prizeParts = str_split($prize_code);
                    $hitTimes = 0;
                    foreach ($parts as $v) {
                        if (in_array($v, $prizeParts)) {
                            $hitTimes++;
                        }
                    }
                    if ($hitTimes > 2) {
                        $level = 1;
                    }
                    break;
                case 'JSSLTX':   //三连号通选
                    $possibleCodes = array('123', '234', '345', '456');
                    if (in_array($prize_code, $possibleCodes)) {
                        $level = 1;
                    }
                    break;

                // 快三和值大小单双
                case 'KS-HZDXDS':
                    $level = [];
                    $prizeParts = str_split($prize_code);
                    $sum = array_sum($prizeParts);

                    $isBig = $sum > 10;
                    $isOdd = $sum % 2 > 0;

                    for ($i = 0, $len = mb_strlen($code, 'utf-8'); $i < $len; ++$i) {
                        $bet = mb_substr($code, $i, 1, 'utf-8');

                        if (
                            ($isBig && $bet === '大') ||
                            (!$isBig && $bet === '小') ||
                            ($isOdd && $bet === '单') ||
                            (!$isOdd && $bet === '双')
                        ) {
                            $level[] = 1;
                        }
                    }

                    break;
                case 'JSHZDX':
                    break;
                case 'JSSJ':
                    break;
                /////////////////////////  快乐扑克区    ////////////////////////

                //$prize_code   Th 7c Ks
                case 'PKDZ':   //对子  $code AA_33_44_66
                    $tmp = explode(' ', $prize_code);
                    $tmpDuizi = array($tmp[0][0], $tmp[1][0], $tmp[2][0]);
                    //判断是否对子
                    if (count(array_unique($tmpDuizi)) == 2) {
                        $tmpDuizi = $tmpDuizi[0] == $tmpDuizi[1] ? $tmpDuizi[0] . $tmpDuizi[1] : ($tmpDuizi[0] == $tmpDuizi[2] ? $tmpDuizi[0] . $tmpDuizi[2] : $tmpDuizi[1] . $tmpDuizi[2]);
                        //中奖判断
                        if (strpos($code, $tmpDuizi) !== false) {
                            $level = 1;
                        }
                    }

                    break;
                case 'PKBZ':   //豹子  $code AAA_333_444_666
                    $tmp = explode(' ', $prize_code);
                    $tmpBaozi = array($tmp[0][0], $tmp[1][0], $tmp[2][0]);
                    //判断是否豹子
                    if (count(array_unique($tmpBaozi)) == 1) {
                        //中奖判断
                        if (strpos($code, implode('', $tmpBaozi)) !== false) {
                            $level = 1;
                        }
                    }

                    break;
                case 'PKTHS':   //同花顺子  $code 红桃顺子_黑桃顺子_梅花顺子_方块顺子
                    $tmp = explode(' ', $prize_code);
                    //花色数组 h,c,s
                    $tmpColors = array($tmp[0][1], $tmp[1][1], $tmp[2][1]);
                    //开奖三张牌花色不同
                    if (count(array_unique($tmpColors)) == 1) {
                        //开奖同花顺的花色
                        $prizeColor = self::$pokerSuitMaps[$tmp[0][1]];

                        $pokerFlip = array_flip(self::$pokerNumMaps);
                        $tmpSort = array($pokerFlip[$tmp[0][0]], $pokerFlip[$tmp[1][0]], $pokerFlip[$tmp[2][0]]);
                        sort($tmpSort);
                        //判断是否顺子
                        if (($tmpSort[0] + 1 == $tmpSort[1] && $tmpSort[0] + 2 == $tmpSort[2]) || ($tmpSort[0] == 1 && $tmpSort[1] == 12 && $tmpSort[2] == 13)) {
                            //中奖判断
                            $parts = explode('_', $code);
                            foreach ($parts as $v) {
                                if (mb_substr($v, 0, 2, 'utf-8') == $prizeColor) {
                                    $level = 1;
                                }
                            }
                        }
                    }

                    break;
                case 'PKSZ':   //扑克 顺子  $code A23_345_9TJ_TJQ
                    $pokerFlip = array_flip(self::$pokerNumMaps);
                    $tmp = explode(' ', $prize_code);
                    $tmpCodes = array($pokerFlip[$tmp[0][0]], $pokerFlip[$tmp[1][0]], $pokerFlip[$tmp[2][0]]);   //10,11,12
                    //对开奖的三张牌从小到大排序,组成一个字符串
                    sort($tmpCodes);
                    $priePoker = self::$pokerNumMaps[$tmpCodes[0]] . self::$pokerNumMaps[$tmpCodes[1]] . self::$pokerNumMaps[$tmpCodes[2]];

                    //中奖判断
                    $parts = explode('_', $code);

                    if ((in_array($priePoker, $parts)) || (in_array('QKA', $parts) && $priePoker == 'AQK')) {
                        $level = 1;
                    }

                    break;
                case 'PKTH':   //扑克 同花      $code 黑桃_红桃
                    $tmp = explode(' ', $prize_code);
                    //花色数组 h,c,s
                    $tmpColors = array($tmp[0][1], $tmp[1][1], $tmp[2][1]);
                    if (count(array_unique($tmpColors)) == 1) {
                        $possibleCodes = self::$pokerSuitMaps;
                        //开奖同花的花色       红桃
                        $prizeSuit = $possibleCodes[$tmp[0][1]];
                        $parts = explode('_', $code);

                        if (in_array($prizeSuit, $parts)) {
                            $level = 1;
                        }
                    }

                    break;
                case 'PKBX':   //扑克 包选      $code 同花包选_顺子包选_豹子包选_对子包选_同花顺包选
                    $winLevel = methods::getPokerForm($prize_code);
                    $tmpForm = array('同花顺' => '同花顺包选', '豹子' => '豹子包选', '顺子' => '顺子包选', '同花' => '同花包选', '对子' => '对子包选');
                    $tmplevel = array('同花顺包选' => 1, '豹子包选' => 2, '顺子包选' => 3, '同花包选' => 4, '对子包选' => 5);
                    $parts = explode('_', $code);
                    $level = array();
                    if (isset($tmpForm[$winLevel])) {
                        if (in_array($tmpForm[$winLevel], $parts)) {
                            $level[] = $tmplevel[$tmpForm[$winLevel]];
                        }
                        if ($tmpForm[$winLevel] == '同花顺包选') {
                            if (in_array('顺子包选', $parts)) {
                                $level[] = $tmplevel['顺子包选'];
                            }
                            if (in_array('同花包选', $parts)) {
                                $level[] = $tmplevel['同花包选'];
                            }
                        }
                    }

                    break;
                case 'PKRX1':   //扑克 任选一   $code  A_2_T_K
                    $tmp = explode(' ', $prize_code);
                    $tmpCodes = array($tmp[0][0], $tmp[1][0], $tmp[2][0]);   //T,7,K
                    //号码不得重复
                    $parts = explode('_', $code);
                    $level = array();
                    foreach ($parts as $v) {
                        if (in_array($v, $tmpCodes)) {
                            $level[] = 1;
                        }
                    }

                    break;
                case 'PKRX2':   //扑克 任选二   $code  A_2_T_K_3_5
                    $tmp = explode(' ', $prize_code);
                    $openCodes = array_unique(array($tmp[0][0], $tmp[1][0], $tmp[2][0]));   //T,7,K
                    //判断不是豹子
                    if (count($openCodes) != 1) {
                        $parts = explode('_', $code);
                        $x = $betNum = 0;
                        foreach ($openCodes as $v) {
                            if (in_array($v, $parts)) {
                                $x++;
                            }
                        }
                        if (count($openCodes) == 2 && $x == 2) {//如果是对子
                            $betNum = 1;
                        }
                        if (count($openCodes) == 3) {//不是对子最多只有3注
                            if ($x == 2) {
                                $betNum = 1;
                            }
                            if ($x == 3) {
                                $betNum = 3;
                            }
                        }
                        if ($betNum) {
                            $level = array_fill(0, $betNum, 1);
                        }
                    }

                    break;
                case 'PKRX3':   //扑克 任选三   $code  A_2_T_K_3_5
                    $tmp = explode(' ', $prize_code);
                    $openCodes = array_unique(array($tmp[0][0], $tmp[1][0], $tmp[2][0]));   //T,7,K
                    $parts = explode('_', $code);

                    $x = $betNum = 0;
                    foreach ($openCodes as $v) {
                        if (in_array($v, $parts)) {
                            $x++;
                        }
                    }
                    if (count($openCodes) == 1 && $x == 1) {//如果是豹子 C $codeNum-1,2
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) / 2;
                    }
                    if (count($openCodes) == 2 && $x == 2) {//如果是对子 C $codeNum-2,1
                        $betNum = count($parts) - $x;
                    }
                    if (count($openCodes) == 3 && $x == 3) {
                        $betNum = 1;
                    }
                    if ($betNum) {
                        $level = array_fill(0, $betNum, 1);
                    }

                    break;
                case 'PKRX4':   //扑克 任选四   $code  A_2_T_K_3_5
                    $tmp = explode(' ', $prize_code);
                    $openCodes = array_unique(array($tmp[0][0], $tmp[1][0], $tmp[2][0]));   //T,7,K
                    $parts = explode('_', $code);

                    $x = $betNum = 0;
                    foreach ($openCodes as $v) {
                        if (in_array($v, $parts)) {
                            $x++;
                        }
                    }

                    if (count($openCodes) == 1 && $x == 1) {//如果是豹子 C $codeNum-1,3
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) * ($codeNum - 2) / 6;
                    }
                    if (count($openCodes) == 2 && $x == 2) {//如果是对子 C $codeNum-2,2
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) / 2;
                    }
                    if (count($openCodes) == 3 && $x == 3) {//如果其他 C $codeNum-3,1
                        $betNum = count($parts) - $x;
                    }
                    if ($betNum) {
                        $level = array_fill(0, $betNum, 1);
                    }

                    break;
                case 'PKRX5':   //扑克 任选五   $code  A_2_T_K_3_5
                    $tmp = explode(' ', $prize_code);
                    $openCodes = array_unique(array($tmp[0][0], $tmp[1][0], $tmp[2][0]));   //T,7,K
                    $parts = explode('_', $code);

                    $x = $betNum = 0;
                    foreach ($openCodes as $v) {
                        if (in_array($v, $parts)) {
                            $x++;
                        }
                    }

                    if (count($openCodes) == 1 && $x == 1) {//如果是豹子 C $codeNum-1,4
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) * ($codeNum - 2) * ($codeNum - 3) / 24;
                    }
                    if (count($openCodes) == 2 && $x == 2) {//如果是对子 C $codeNum-2,3
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) * ($codeNum - 2) / 6;
                    }
                    if (count($openCodes) == 3 && $x == 3) {//如果其他 C $codeNum-3,2
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) / 2;
                    }
                    if ($betNum) {
                        $level = array_fill(0, $betNum, 1);
                    }

                    break;
                case 'PKRX6':   //扑克 任选六   $code  A_2_T_K_3_5
                    $tmp = explode(' ', $prize_code);
                    $openCodes = array_unique(array($tmp[0][0], $tmp[1][0], $tmp[2][0]));   //T,7,K
                    $parts = explode('_', $code);

                    $x = $betNum = 0;
                    foreach ($openCodes as $v) {
                        if (in_array($v, $parts)) {
                            $x++;
                        }
                    }
                    if (count($openCodes) == 1 && $x == 1) {//如果是豹子 C $codeNum-1,5
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) * ($codeNum - 2) * ($codeNum - 3) * ($codeNum - 4) / 120;
                    }
                    if (count($openCodes) == 2 && $x == 2) {//如果是对子 C $codeNum-2,4
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) * ($codeNum - 2) * ($codeNum - 3) / 24;
                    }
                    if (count($openCodes) == 3 && $x == 3) {//如果其他 C $codeNum-3,3
                        $codeNum = count($parts) - $x;
                        $betNum = $codeNum * ($codeNum - 1) * ($codeNum - 2) / 6;
                    }
                    if ($betNum) {
                        $level = array_fill(0, $betNum, 1);
                    }

                    break;
                default:
                    throw new exception2('Non supported method');
                    break;
            }

            //算具体奖金 先得到奖金信息
            if ($level === 0) {
                continue;
            }

            if (!self::$prizes) {
                if (!self::$prizes = prizes::getItems(0, 0, 0, 0, 2)) {
                    throw new exception2('No base prize info');
                }
            }
            if (!isset(self::$prizes[$lottery['lottery_id']][$method['method_id']])) {
                throw new exception2('No base prize info2');
            }
            $prize = self::$prizes[$lottery['lottery_id']][$method['method_id']];
            $finalPrize = 0;

            //一般情况下复式顶多只能中一个奖，此时用数字表示几等奖，直接取相应奖金
            //对于不定位胆、山东任选1~4等的复式可能中不止一个奖，此时level为数组，表示相应的奖金加起来
            if (is_numeric($level)) {
                if (!isset($prize[$level]['prize']) || $prize[$level]['prize'] <= 0) {
                    throw new exception2('Invalid prize level');
                }
                $finalPrize = $prize[$level]['prize'];
            } elseif (is_array($level)) {
                foreach ($level as $v) {
                    if (!isset($prize[$v]['prize']) || $prize[$v]['prize'] <= 0) {
                        throw new exception2('Invalid prize level');
                    }
                    $finalPrize += $prize[$v]['prize'];
                }
            }

            //最终奖金再根据投单时返点进行比例调整 一码不定位固定奖金为6.61
            $finalPrize = $finalPrize * (1 - $lottery['total_profit'] + $userRebate - $curRebate) / (1 - $lottery['total_profit']);
//             $tmpname = array('YMBDW',);
//             if ($method['name'] == 'YMBDW' && $finalPrize > 7.01) {
//                 $finalPrize = 7.01;
//             }
            $allPrize += $finalPrize;
        }

        return $allPrize;
    }

    /**
     * 获得某一玩法的最大单倍奖金。（注意 所得结果须再x投注倍数x模式才是最终奖金！）
     * @param array $lottery_id 彩种ID
     * @param array $method_id 玩法ID
     * @return float 单倍奖金
     */
    static public function computeMethodPrize($lottery_id, $method_id)
    {
        if (!is_numeric($lottery_id) || !is_numeric($method_id) || $lottery_id <= 0 || $method_id <= 0) {
            throw new exception2('参数无效');
        }
        //从computePrize复制的一段代码,指定level=1表示中了可能的最大奖。
        $level = 1;
        if (!self::$prizes) {
            if (!self::$prizes = prizes::getItems(0, 0, 0, 0, 2)) {
                throw new exception2('No base prize info');
            }
        }
        if (!isset(self::$prizes[$lottery_id][$method_id])) {
            throw new exception2('No base prize info2');
        }
        $prize = self::$prizes[$lottery_id][$method_id];
        $finalPrize = 0;

        //一般情况下复式顶多只能中一个奖，此时用数字表示几等奖，直接取相应奖金
        //对于不定位胆、山东任选1~4等的复式可能中不止一个奖，此时level为数组，表示相应的奖金加起来
        if (!isset($prize[$level]['prize']) || $prize[$level]['prize'] <= 0) {
            throw new exception2('Invalid prize level');
        }
        $finalPrize = $prize[$level]['prize'];

        return $finalPrize;
    }

    /**
     * author nyjah
     * 判断扑克形态
     * @param $code string '4s 6c Kd'
     * @return string
     */
    static public function getPokerForm($code)
    {
        if (!preg_match('`^([ATJQK2-9][hscd])(\s([ATJQK2-9][hscd])){2}$`i', $code)) {
            throw new exception2('参数无效2998');
        }
        $pokerNumMaps = array(
            'A' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            'T' => 10,
            'J' => 11,
            'Q' => 12,
            'K' => 13,
        );
        $openNumber = explode(" ", $code);
        //判断形态
        $colors = array($openNumber[0][1], $openNumber[1][1], $openNumber[2][1]);
        $codeNum = array($pokerNumMaps[$openNumber[0][0]], $pokerNumMaps[$openNumber[1][0]], $pokerNumMaps[$openNumber[2][0]]);
        sort($codeNum);
        if (($codeNum[0] + 1 == $codeNum[1] && $codeNum[0] + 2 == $codeNum[2]) || ($codeNum[0] == 1 && $codeNum[1] == 12 && $codeNum[2] == 13)) {
            if (count(array_unique($colors)) == 1) {
                $form = '同花顺';
            } else {
                $form = '顺子';
            }
        } else {
            if (count(array_unique($colors)) == 1) {
                $form = '同花';
            } else {
                if (count(array_unique($codeNum)) == 1) {
                    $form = '豹子';
                } elseif (count(array_unique($codeNum)) == 2) {
                    $form = '对子';
                } else {
                    $form = '散牌';
                }
            }
        }

        return $form;
    }

    //只针对低频调用,获取直选号和相应的次数
    public static function get3dP3AllP($method_name, $codes)
    {
        if (!is_string($method_name) || !is_array($codes)) {
            throw new exception2('参数无效29300');
        }

        $resAllPCode = array();
        switch ($method_name) {
            case 'QSZX':
            case 'SXZX'://codes array("12,23,356","45,34,7")
                foreach ($codes as $code) {
                    $expandCode = methods::expand(explode(',', $code));
                    foreach ($expandCode as $c) {
                        if (array_key_exists($c, $resAllPCode)) {
                            $resAllPCode[$c] += 1;
                        } else {
                            $resAllPCode[$c] = 1;
                        }
                    }
                }
                break;
            case 'QSHZ':
            case 'SXHZ'://codes array("5_6_7_18","11_12_13")
                foreach ($codes as $code) {
                    $singleCodeGroup = explode('_', $code);
                    foreach ($singleCodeGroup as $v) {
                        for ($i = 0; $i <= 9; $i++) {
                            for ($j = 0; $j <= 9; $j++) {
                                for ($k = 0; $k <= 9; $k++) {
                                    if ($i + $j + $k == $v) {
                                        $c = $i . $j . $k;
                                        if (array_key_exists($c, $resAllPCode)) {
                                            $resAllPCode[$c] += 1;
                                        } else {
                                            $resAllPCode[$c] = 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 'QSZS':
            case 'SXZS'://codes array("2345","45")
                $allP = methods::getEnumNumber();
                foreach ($codes as $code) {
                    $combos = methods::C(str_split($code), 2);
                    foreach ($combos as $c) {
                        foreach ($allP as $v) {
                            if (count(array_unique($v)) == 2) {//组三需要去重
                                $p = implode('', $v);
                                if (strpos($p, $c) !== false) {
                                    if (array_key_exists($p, $resAllPCode)) {
                                        $resAllPCode[$p] += 1;
                                    } else {
                                        $resAllPCode[$p] = 1;
                                    }
                                }
                            }

                        }
                    }
                }
                break;
            case 'QSZL':
            case 'SXZL'://codes array("123","456")
                foreach ($codes as $code) {
                    $combos = methods::C(str_split($code), 3);
                    foreach ($combos as $c) {
                        $allP = methods::getAllP(str_split($c));
                        foreach ($allP as $p) {
                            $pStr = implode('', $p);
                            if (array_key_exists($pStr, $resAllPCode)) {
                                $resAllPCode[$pStr] += 1;
                            } else {
                                $resAllPCode[$pStr] = 1;
                            }
                        }
                    }
                }
                break;
            case 'QSHHZX':
            case 'SXHHZX'://codes array("1,2,3","4,4,6")
                $allP = methods::getEnumNumber();
                foreach ($codes as $code) {

                    $codeArr = explode(',', $code);
                    $uniqueCode = array_unique($codeArr);
                    if (count($uniqueCode) == 2) {//组三
                        $uCode = implode('', $uniqueCode);
                        foreach ($allP as $p) {
                            if (count(array_unique($p)) == 2) {
                                $pStr = implode('', $p);
                                if (strpos($pStr, $uCode) !== false) {
                                    if (array_key_exists($pStr, $resAllPCode)) {
                                        $resAllPCode[$pStr] += 1;
                                    } else {
                                        $resAllPCode[$pStr] = 1;
                                    }
                                }
                            }
                        }
                    } elseif (count($uniqueCode) == 3) {//组六
                        $ps = methods::getAllP($codeArr);
                        foreach ($ps as $p) {
                            $pStr = implode('', $p);
                            if (array_key_exists($pStr, $resAllPCode)) {
                                $resAllPCode[$pStr] += 1;
                            } else {
                                $resAllPCode[$pStr] = 1;
                            }
                        }
                    }
                }
                break;
            case 'QSZXHZ':
            case 'SXZXHZ':// array('4_5_6')
                foreach ($codes as $code) {
                    $singleCode = explode('_', $code);
                    foreach ($singleCode as $v) {
                        for ($i = 0; $i <= 9; $i++) {
                            for ($j = 0; $j <= 9; $j++) {
                                for ($k = 0; $k <= 9; $k++) {
                                    if ($i + $j + $k == $v && $i != $j && $i != $k && $j != $i) {
                                        $c = $i . $j . $k;
                                        if (array_key_exists($c, $resAllPCode)) {
                                            $resAllPCode[$c] += 1;
                                        } else {
                                            $resAllPCode[$c] = 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 'QSYMBDW':
            case 'YMBDW'://array(1234,3456)
                $allP = methods::getEnumNumber();
                foreach ($codes as $code) {
                    foreach ($allP as $p) {
                        if (array_intersect(str_split($code), $p)) {
                            $c = implode('', $p);
                            if (array_key_exists($c, $resAllPCode)) {
                                $resAllPCode[$c] += 1;
                            } else {
                                $resAllPCode[$c] = 1;
                            }
                        }
                    }
                }
                break;
            case 'QSEMBDW':
            case 'EMBDW'://array(1234,3456)
                $allP = methods::getEnumNumber();
                foreach ($codes as $code) {
                    $combos = methods::C(str_split($code), 2);
                    foreach ($combos as $combo) {
                        foreach ($allP as $p) {
                            $c = implode('', $p);
                            if (strpos($c, $combo) !== false) {
                                if (array_key_exists($c, $resAllPCode)) {
                                    $resAllPCode[$c] += 1;
                                } else {
                                    $resAllPCode[$c] = 1;
                                }
                            }
                        }
                    }
                }
                break;
            case 'QEZX'://array("1234,3456","23,4")
                foreach ($codes as $code) {
                    $expandCode = methods::expand(explode(',', $code));
                    foreach ($expandCode as $qr) {
                        for ($i = 0; $i <= 9; $i++) {
                            $c = $qr . $i;
                            if (array_key_exists($c, $resAllPCode)) {
                                $resAllPCode[$c] += 1;
                            } else {
                                $resAllPCode[$c] = 1;
                            }
                        }
                    }
                }
                break;
            case 'QEZUX'://array("1234")
                foreach ($codes as $code) {
                    $expandCode = methods::C(str_split($code), 2);
                    foreach ($expandCode as $qr) {
                        for ($i = 0; $i <= 9; $i++) {
                            $c = $qr . $i;
                            if (array_key_exists($c, $resAllPCode)) {
                                $resAllPCode[$c] += 1;
                            } else {
                                $resAllPCode[$c] = 1;
                            }
                            $c1 = $qr[1] . $qr[0] . $i;
                            if (array_key_exists($c1, $resAllPCode)) {
                                $resAllPCode[$c1] += 1;
                            } else {
                                $resAllPCode[$c1] = 1;
                            }
                        }
                    }
                }
                break;
            case 'EXZX'://array("12,34")
                foreach ($codes as $code) {
                    $expandCode = methods::expand(explode(',', $code));
                    foreach ($expandCode as $qr) {
                        for ($i = 0; $i <= 9; $i++) {
                            $c = $i . $qr;
                            if (array_key_exists($c, $resAllPCode)) {
                                $resAllPCode[$c] += 1;
                            } else {
                                $resAllPCode[$c] = 1;
                            }
                        }
                    }
                }
                break;
            case 'EXZUX':
                foreach ($codes as $code) {
                    $expandCode = methods::C(str_split($code), 2);
                    foreach ($expandCode as $qr) {
                        for ($i = 0; $i <= 9; $i++) {
                            $c = $i . $qr;
                            if (array_key_exists($c, $resAllPCode)) {
                                $resAllPCode[$c] += 1;
                            } else {
                                $resAllPCode[$c] = 1;
                            }
                            $c1 = $i . $qr[1] . $qr[0];
                            if (array_key_exists($c1, $resAllPCode)) {
                                $resAllPCode[$c1] += 1;
                            } else {
                                $resAllPCode[$c1] = 1;
                            }
                        }
                    }
                }
                break;
            case 'QEDXDS'://array("单,大")
                $arr = [
                    '大' => [5, 6, 7, 8, 9],
                    '小' => [0, 1, 2, 3, 4],
                    '单' => [1, 3, 5, 7, 9],
                    '双' => [0, 2, 4, 6, 8],
                ];
                foreach ($codes as $code) {
                    $codeArr = explode(',', $code);
                    $hundred = implode('', $arr[$codeArr[0]]);
                    $ten = implode('', $arr[$codeArr[1]]);
                    $expandCode = methods::expand(array($hundred, $ten));
                    foreach ($expandCode as $v) {
                        for ($i = 0; $i <= 9; $i++) {
                            $c = $v . $i;
                            if (array_key_exists($c, $resAllPCode)) {
                                $resAllPCode[$c] += 1;
                            } else {
                                $resAllPCode[$c] = 1;
                            }
                        }
                    }

                }
                break;
            case 'EXDXDS':
                $arr = [
                    '大' => [5, 6, 7, 8, 9],
                    '小' => [0, 1, 2, 3, 4],
                    '单' => [1, 3, 5, 7, 9],
                    '双' => [0, 2, 4, 6, 8],
                ];
                foreach ($codes as $code) {
                    $codeArr = explode(',', $code);
                    $ten = implode('', $arr[$codeArr[0]]);
                    $figure = implode('', $arr[$codeArr[1]]);
                    $expandCode = methods::expand(array($ten, $figure));
                    foreach ($expandCode as $v) {
                        for ($i = 0; $i <= 9; $i++) {
                            $c = $i . $v;
                            if (array_key_exists($c, $resAllPCode)) {
                                $resAllPCode[$c] += 1;
                            } else {
                                $resAllPCode[$c] = 1;
                            }
                        }
                    }
                }
                break;
            case 'SXDW'://array("3,,","34,6,")
                foreach ($codes as $code) {
                    $codeArr = explode(',', $code);
                    if ($codeArr[0]) {
                        $split = str_split($codeArr[0]);
                        foreach ($split as $v1) {
                            for ($j1 = 0; $j1 <= 9; $j1++) {
                                for ($i1 = 0; $i1 <= 9; $i1++) {
                                    $c1 = $v1 . $j1 . $i1;
                                    if (array_key_exists($c1, $resAllPCode)) {
                                        $resAllPCode[$c1] += 1;
                                    } else {
                                        $resAllPCode[$c1] = 1;
                                    }
                                }
                            }
                        }
                    }
                    if ($codeArr[1]) {
                        $split = str_split($codeArr[1]);
                        foreach ($split as $v2) {
                            for ($j2 = 0; $j2 <= 9; $j2++) {
                                for ($i2 = 0; $i2 <= 9; $i2++) {
                                    $c2 = $j2 . $v2 . $i2;
                                    if (array_key_exists($c2, $resAllPCode)) {
                                        $resAllPCode[$c2] += 1;
                                    } else {
                                        $resAllPCode[$c2] = 1;
                                    }
                                }
                            }
                        }
                    }
                    if ($codeArr[2]) {
                        $split = str_split($codeArr[2]);
                        foreach ($split as $v3) {
                            for ($j3 = 0; $j3 <= 9; $j3++) {
                                for ($i3 = 0; $i3 <= 9; $i3++) {
                                    $c3 = $j3 . $v3 . $i3;
                                    if (array_key_exists($c3, $resAllPCode)) {
                                        $resAllPCode[$c3] += 1;
                                    } else {
                                        $resAllPCode[$c3] = 1;
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 'WXDW'://该case用不上，因为对P5的定位胆和后二两种玩法没有封锁
                foreach ($codes as $code) {
                    $codeArr = explode(',', $code);
                    if ($codeArr[0]) {
                        $split = str_split($codeArr[0]);
                        foreach ($split as $v1) {
                            for ($j1 = 0; $j1 <= 9; $j1++) {
                                for ($i1 = 0; $i1 <= 9; $i1++) {
                                    for ($a1 = 0; $a1 <= 9; $a1++) {
                                        for ($b1 = 0; $b1 <= 9; $b1++) {
                                            $c1 = $v1 . $j1 . $i1 . $a1 . $b1;
                                            if (array_key_exists($c1, $resAllPCode)) {
                                                $resAllPCode[$c1] += 1;
                                            } else {
                                                $resAllPCode[$c1] = 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($codeArr[1]) {
                        $split = str_split($codeArr[1]);
                        foreach ($split as $v2) {
                            for ($j2 = 0; $j2 <= 9; $j2++) {
                                for ($i2 = 0; $i2 <= 9; $i2++) {
                                    for ($a2 = 0; $a2 <= 9; $a2++) {
                                        for ($b2 = 0; $b2 <= 9; $b2++) {
                                            $c2 = $v2 . $j2 . $i2 . $a2 . $b2;
                                            if (array_key_exists($c2, $resAllPCode)) {
                                                $resAllPCode[$c2] += 1;
                                            } else {
                                                $resAllPCode[$c2] = 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($codeArr[2]) {
                        $split = str_split($codeArr[2]);
                        foreach ($split as $v3) {
                            for ($j3 = 0; $j3 <= 9; $j3++) {
                                for ($i3 = 0; $i3 <= 9; $i3++) {
                                    for ($a3 = 0; $a3 <= 9; $a3++) {
                                        for ($b3 = 0; $b3 <= 9; $b3++) {
                                            $c3 = $v3 . $j3 . $i3 . $a3 . $b3;
                                            if (array_key_exists($c3, $resAllPCode)) {
                                                $resAllPCode[$c3] += 1;
                                            } else {
                                                $resAllPCode[$c3] = 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($codeArr[3]) {
                        $split = str_split($codeArr[3]);
                        foreach ($split as $v4) {
                            for ($j4 = 0; $j4 <= 9; $j4++) {
                                for ($i4 = 0; $i4 <= 9; $i4++) {
                                    for ($a4 = 0; $a4 <= 9; $a4++) {
                                        for ($b4 = 0; $b4 <= 9; $b4++) {
                                            $c4 = $v4 . $j4 . $i4 . $a4 . $b4;
                                            if (array_key_exists($c4, $resAllPCode)) {
                                                $resAllPCode[$c4] += 1;
                                            } else {
                                                $resAllPCode[$c4] = 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($codeArr[4]) {
                        $split = str_split($codeArr[4]);
                        foreach ($split as $v5) {
                            for ($j5 = 0; $j5 <= 9; $j5++) {
                                for ($i5 = 0; $i5 <= 9; $i5++) {
                                    for ($a5 = 0; $a5 <= 9; $a5++) {
                                        for ($b5 = 0; $b5 <= 9; $b5++) {
                                            $c5 = $v5 . $j5 . $i5 . $a5 . $b5;
                                            if (array_key_exists($c5, $resAllPCode)) {
                                                $resAllPCode[$c5] += 1;
                                            } else {
                                                $resAllPCode[$c5] = 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            default:
                throw new exception2("不支持的玩法11034");
                break;
        }

        return $resAllPCode;
    }

    //获得低频所有直选号 000-999
    public static function getEnumNumber($isCombin = 0)
    {
        $aBetNumber = $aCombinNumber = [];

        for ($i = 0; $i < 10; $i++) {
            for ($j = 0; $j < 10; $j++) {
                for ($k = 0; $k < 10; $k++) {
                    if ($isCombin) {
                        $aNumber = [$i, $j, $k];
                        sort($aNumber);
                        if (!in_array(implode($aNumber), $aCombinNumber)) {
                            $aCombinNumber[] = implode($aNumber);
                            $aBetNumber[] = [$i, $j, $k];
                        }
                    } else {
                        $aBetNumber[] = [$i, $j, $k];
                    }
                }
            }
        }
        return $aBetNumber;
    }

}
