<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userRebates
{
    // public static $hiddenRebates = [
    //     1960=>[1=>8.0, 2=>6.5, 3=>15.0, 4=>15.0, 5=>11.5, 6=>8.0,7=>2, 8=>0.0, 9=>0.0],
    // ];

    // public static $highRebates = [
    //     1958=>[1=>7.9, 2=>6.4, 3=>14.9, 4=>14.9, 5=>11.4, 6=>7.9,7=>1.9, 8=>0.0, 9=>0.0],
    //     1956=>[1=>7.8, 2=>6.3, 3=>14.8, 4=>14.8, 5=>11.3, 6=>7.8,7=>1.8, 8=>0.0, 9=>0.0],
    //     1954=>[1=>7.7, 2=>6.2, 3=>14.7, 4=>14.7, 5=>11.2, 6=>7.7,7=>1.7, 8=>0.0, 9=>0.0],
    //     1952=>[1=>7.6, 2=>6.1, 3=>14.6, 4=>14.6, 5=>11.1, 6=>7.6,7=>1.6, 8=>0.0, 9=>0.0],
    // ];

    // public static $basicRebates = [
    //     1950=>[1=>7.5, 2=>6.0, 3=>14.5, 4=>14.5, 5=>11.0, 6=>7.5,7=>1.5, 8=>0.0, 9=>0.0],
    //     1940=>[1=>7.0, 2=>5.9, 3=>14.0, 4=>14.0, 5=>10.5, 6=>7.0,7=>1.4, 8=>0.0, 9=>0.0],
    //     1930=>[1=>6.5, 2=>5.8, 3=>13.0, 4=>13.0, 5=>10.0, 6=>6.5,7=>1.3, 8=>0.0, 9=>0.0],
    //     1920=>[1=>6.0, 2=>5.7, 3=>12.0, 4=>12.0, 5=>9.5, 6=>6.0,7=>1.2, 8=>0.0, 9=>0.0],
    //     1910=>[1=>5.5, 2=>5.6, 3=>11.0, 4=>11.0, 5=>9.0, 6=>5.5,7=>1.1, 8=>0.0, 9=>0.0],
    //     1900=>[1=>5.0, 2=>5.5, 3=>10.0, 4=>10.0, 5=>8.5, 6=>5.0,7=>1.0, 8=>0.0, 9=>0.0],
    //     1890=>[1=>4.5, 2=>5.0, 3=>9.0, 4=>9.0, 5=>8.0, 6=>4.5,7=>0.9, 8=>0.0, 9=>0.0],
    //     1880=>[1=>4.0, 2=>4.5, 3=>8.0, 4=>8.0, 5=>7.5, 6=>4.0,7=>0.8, 8=>0.0, 9=>0.0],
    //     1870=>[1=>3.5, 2=>4.0, 3=>7.0, 4=>7.0, 5=>7.0, 6=>3.5,7=>0.7, 8=>0.0, 9=>0.0],
    //     1860=>[1=>3.0, 2=>3.5, 3=>6.0, 4=>6.0, 5=>6.0, 6=>3.0,7=>0.6, 8=>0.0, 9=>0.0],
    //     1850=>[1=>2.5, 2=>3.0, 3=>5.0, 4=>5.0, 5=>5.0, 6=>2.5,7=>0.5, 8=>0.0, 9=>0.0],
    //     1840=>[1=>2.0, 2=>2.5, 3=>4.0, 4=>4.0, 5=>4.0, 6=>2.0,7=>0.4, 8=>0.0, 9=>0.0],
    //     1830=>[1=>1.5, 2=>2.0, 3=>3.0, 4=>3.0, 5=>3.0, 6=>1.5,7=>0.3, 8=>0.0, 9=>0.0],
    //     1820=>[1=>1.0, 2=>1.5, 3=>2.0, 4=>2.0, 5=>2.0, 6=>1.0,7=>0.2, 8=>0.0, 9=>0.0],
    //     1810=>[1=>0.5, 2=>1.0, 3=>1.0, 4=>1.0, 5=>1.0, 6=>0.5,7=>0.1, 8=>0.0, 9=>0.0],
    //     1800=>[1=>0.0, 2=>0.0, 3=>0.0, 4=>0.0, 5=>0.0, 6=>0.0,7=>0.0, 8=>0.0, 9=>0.0],
    // ];

    public static $hiddenRebates = [
        1990=>[1=>9.5, 2=>8.0, 3=>16.5, 4=>16.5, 5=>13.0, 6=>9.5,7=>5.5, 8=>0.0, 9=>0.0],
    ];

    public static $highRebates = [
        1988=>[1=>9.4, 2=>7.9, 3=>16.4, 4=>16.4, 5=>12.9, 6=>9.4,7=>5.0, 8=>0.0, 9=>0.0],
        1986=>[1=>9.3, 2=>7.8, 3=>16.3, 4=>16.3, 5=>12.8, 6=>9.3,7=>4.5, 8=>0.0, 9=>0.0],
        1984=>[1=>9.2, 2=>7.7, 3=>16.2, 4=>16.2, 5=>12.7, 6=>9.2,7=>4.0, 8=>0.0, 9=>0.0],
        1982=>[1=>9.1, 2=>7.6, 3=>16.1, 4=>16.1, 5=>12.6, 6=>9.1,7=>3.5, 8=>0.0, 9=>0.0],
        1980=>[1=>9.0, 2=>7.5, 3=>16.0, 4=>16.0, 5=>12.5, 6=>9.0,7=>3.0, 8=>0.0, 9=>0.0],
        1970=>[1=>8.5, 2=>7.0, 3=>15.5, 4=>15.5, 5=>12.0, 6=>8.5,7=>2.5, 8=>0.0, 9=>0.0],
        1960=>[1=>8.0, 2=>6.5, 3=>15.0, 4=>15.0, 5=>11.5, 6=>8.0,7=>2.0, 8=>0.0, 9=>0.0],
    ];

    public static $basicRebates = [
        1958=>[1=>7.9, 2=>6.4, 3=>14.9, 4=>14.9, 5=>11.4, 6=>7.9,7=>1.9, 8=>0.0, 9=>0.0],
        1956=>[1=>7.8, 2=>6.3, 3=>14.8, 4=>14.8, 5=>11.3, 6=>7.8,7=>1.8, 8=>0.0, 9=>0.0],
        1954=>[1=>7.7, 2=>6.2, 3=>14.7, 4=>14.7, 5=>11.2, 6=>7.7,7=>1.7, 8=>0.0, 9=>0.0],
        1952=>[1=>7.6, 2=>6.1, 3=>14.6, 4=>14.6, 5=>11.1, 6=>7.6,7=>1.6, 8=>0.0, 9=>0.0],
        1950=>[1=>7.5, 2=>6.0, 3=>14.5, 4=>14.5, 5=>11.0, 6=>7.5,7=>1.5, 8=>0.0, 9=>0.0],
        1940=>[1=>7.0, 2=>5.9, 3=>14.0, 4=>14.0, 5=>10.5, 6=>7.0,7=>1.4, 8=>0.0, 9=>0.0],
        1930=>[1=>6.5, 2=>5.8, 3=>13.0, 4=>13.0, 5=>10.0, 6=>6.5,7=>1.3, 8=>0.0, 9=>0.0],
        1920=>[1=>6.0, 2=>5.7, 3=>12.0, 4=>12.0, 5=>9.5, 6=>6.0,7=>1.2, 8=>0.0, 9=>0.0],
        1910=>[1=>5.5, 2=>5.6, 3=>11.0, 4=>11.0, 5=>9.0, 6=>5.5,7=>1.1, 8=>0.0, 9=>0.0],
        1900=>[1=>5.0, 2=>5.5, 3=>10.0, 4=>10.0, 5=>8.5, 6=>5.0,7=>1.0, 8=>0.0, 9=>0.0],
        1890=>[1=>4.5, 2=>5.0, 3=>9.0, 4=>9.0, 5=>8.0, 6=>4.5,7=>0.9, 8=>0.0, 9=>0.0],
        1880=>[1=>4.0, 2=>4.5, 3=>8.0, 4=>8.0, 5=>7.5, 6=>4.0,7=>0.8, 8=>0.0, 9=>0.0],
        1870=>[1=>3.5, 2=>4.0, 3=>7.0, 4=>7.0, 5=>7.0, 6=>3.5,7=>0.7, 8=>0.0, 9=>0.0],
        1860=>[1=>3.0, 2=>3.5, 3=>6.0, 4=>6.0, 5=>6.0, 6=>3.0,7=>0.6, 8=>0.0, 9=>0.0],
        1850=>[1=>2.5, 2=>3.0, 3=>5.0, 4=>5.0, 5=>5.0, 6=>2.5,7=>0.5, 8=>0.0, 9=>0.0],
        1840=>[1=>2.0, 2=>2.5, 3=>4.0, 4=>4.0, 5=>4.0, 6=>2.0,7=>0.4, 8=>0.0, 9=>0.0],
        1830=>[1=>1.5, 2=>2.0, 3=>3.0, 4=>3.0, 5=>3.0, 6=>1.5,7=>0.3, 8=>0.0, 9=>0.0],
        1820=>[1=>1.0, 2=>1.5, 3=>2.0, 4=>2.0, 5=>2.0, 6=>1.0,7=>0.2, 8=>0.0, 9=>0.0],
        1810=>[1=>0.5, 2=>1.0, 3=>1.0, 4=>1.0, 5=>1.0, 6=>0.5,7=>0.1, 8=>0.0, 9=>0.0],
        1800=>[1=>0.0, 2=>0.0, 3=>0.0, 4=>0.0, 5=>0.0, 6=>0.0,7=>0.0, 8=>0.0, 9=>0.0],
    ];

    const CACHE_TIME = 7200;

    static public function getItem($id, $status = 1)
    {
        $sql = 'SELECT * FROM user_rebates WHERE ur_id = ' . intval($id);
        if ($status != -1) {
            $sql .= ' AND status= ' . intval($status);
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //得到某用户的所有彩种返点，在开户时就有用到 其中user_id为必选参数
    static public function getItems($user_ids, $status = 1)
    {
        if (!is_array($user_ids)) {
            $user_ids = array($user_ids);
        }
        $noCacheKeys = array();
        $result = $GLOBALS['mc']->gets(__CLASS__, $user_ids, $noCacheKeys);
        if ($noCacheKeys) {
            $sql = "SELECT * FROM user_rebates WHERE user_id IN(" . implode(',', $noCacheKeys) . ")";
            $sql .= ' ORDER BY user_id ASC';
            $noCacheResult = $GLOBALS['db']->getAll($sql);
            $tmp2 = array();
            foreach ($noCacheResult as $v) {
                $tmp2[$v['user_id']][] = $v;
            }
            $GLOBALS['mc']->sets(__CLASS__, $tmp2, self::CACHE_TIME);
            $result += $tmp2;
        }
        $out = array();
        foreach ($result as $k => $v) {
            foreach ($v as $kk => $vv) {
                if ($status == -1 || $vv['status'] == $status) {
                    $out[] = $vv;
                }
            }
        }
        return $out;
    }

    //得到用户某个彩种类别具体返点
    static public function getUserRebate($user_id, $property_id, $status = 1)
    {
        if (!is_numeric($user_id) || !isset($GLOBALS['cfg']['property'][$property_id])) {
            throw new exception2('参数无效');
        }

        $sql = "SELECT * FROM user_rebates WHERE user_id = $user_id ";
        if (!$results = $GLOBALS['db']->getAll($sql)) {
            log2("user_id=$user_id 找不到返点数据");
            //throw new exception2('找不到返点数据');
            return false;
        }

        foreach ($results as $result) {
            if ($property_id == $result['property_id']) {
                if ($status != -1 && $result['status'] != $status) {
                    continue;
                }

                return $result['rebate'];
            }
        }

        return NULL;
    }

    //批量得到一批用户某个彩种具体返点 通常用于一次得到某用户的所有上级返点
    static public function getUsersRebates($user_ids, $property_id = 0, $style = 0)
    {
        if (!is_array($user_ids) || ($property_id && !isset($GLOBALS['cfg']['property'][$property_id]))) {
            throw new exception2('参数无效');
        }
        if (empty($user_ids)) {
            return array();
        }
        foreach ($user_ids as $v) {
            if (!$v || !is_numeric($v)) {
                throw new exception2('参数无效');
            }
        }
        $noCacheKeys = array();
        $tmp = $GLOBALS['mc']->gets(__CLASS__, $user_ids, $noCacheKeys);
        if ($noCacheKeys) {
            $sql = "SELECT * FROM user_rebates WHERE user_id IN(" . implode(',', $noCacheKeys) . ")";
            $sql .= ' ORDER BY user_id ASC';
            $noCacheResult = $GLOBALS['db']->getAll($sql);
            $tmp2 = array();
            foreach ($noCacheResult as $v) {
                $tmp2[$v['user_id']][] = $v;
            }
            $GLOBALS['mc']->sets(__CLASS__, $tmp2, self::CACHE_TIME);
            $tmp += $tmp2;
        }

        $result = array();
        if ($property_id != 0) {
            foreach ($tmp as $k => $v) {
                foreach ($v as $kk => $vv) {
                    if ($vv['property_id'] != $property_id) {
                        unset($tmp[$k][$kk]);
                    }
                }
            }
        }
        if ($style == 0) {  //用于一次得到一批用户某个property_id的返点
            //$tmp = array_spec_key($tmp, 'user_id');
            foreach ($user_ids as $v) {
                if (isset($tmp[$v])) {
                    $tmp3 = reset($tmp[$v]);
                    $result[$v] = $tmp3['rebate'];
                }
            }
            arsort($result); //按代理层次由上往下排列，保留用户id作为数组下标
            ksort($result);
        }
        elseif ($style == 1) {  //用于批量得到一批用户各采种的返点
            foreach ($tmp as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $result[$k][$vv['property_id']] = $vv['rebate'];
                }
            }
        }

        return $result;
    }

    //个别方法：得到具有某一返点值的用户列表
    static public function getUsersByRebate($rebate)
    {
        if (!is_numeric($rebate)) {
            throw new exception2('参数无效');
        }
        $sql = "SELECT u.* FROM user_rebates ur LEFT JOIN users u ON ur.user_id = u.user_id WHERE ur.status = 1 AND ur.rebate = '$rebate' AND u.status = 8";
        $sql .= ' ORDER BY u.user_id ASC';
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    //生成指定间隔的返点列表，用于设置返点的select框
    static public function genRebateGap($lottery, $rebate, $level, $needSelf = false)
    {
        $gaps = unserialize($lottery['min_rebate_gaps']);
        if (($rebate < 0 && $lottery['lottery_id'] != 21 && $lottery['lottery_id'] != 22) || $rebate < 0 || $rebate > 0.16 || !is_array($gaps)) {
            d($rebate);
            throw new exception2('参数无效');
        }
        $result = array();

        foreach ($gaps as $k=>$v) {
            if($k == 2 && $level!=0) continue; 

            if ($rebate > $v['to']) {
                for ($i = $v['from']; $i <= $v['to']; $i+=$v['gap']) {
                    $result[] = number_format($i, REBATE_PRECISION);
                }
            }
            else {
                for ($i = $v['from']; $i < $v['to'] && strval($i) < $rebate; $i+=$v['gap']) {
                    $result[] = number_format($i, REBATE_PRECISION);
                }
                if ($needSelf) {
                    $result[] = $rebate;
                }
            }
        }


        $result = array_reverse(array_unique($result));
        //计算相应奖金
        $finalResult = array();
        foreach ($result as $v) {
            $prize = 2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $v);
            $finalResult[] = array(
                'rebate' => $v,
                'prize' => $prize,
            );
        }

        return $finalResult;
    }

    
    static public function genRebateGapByProperty($property_id = 1)
    {
        $representLotterys = lottery::getRepresent();
        foreach ($representLotterys as $lottery)
        {
            if($lottery['property_id'] == $property_id)
            {
                $topMaxRebate = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION); //总代最大留水
                return self::genRebateGap($lottery, $topMaxRebate, 0, true);
            }
        }
    }
    

    //前台保存非总代用户返点，没有的就新加，调高最简单 只要没超过上限；调低要依次检查下级是否有冲突，若有冲突依次调低
 /*   static public function saveRebate($user_id, $rebates)
    {
        if ($user_id <= 0 || !is_array($rebates)) {
            throw new exception2('参数无效2');
        }
        //检查rebate的正确性 不可能超过1960
        foreach ($rebates as $k => $v) {
            if (!isset($GLOBALS['cfg']['property'][$k]) || !is_numeric($v) || $v < 0 || $v > 0.15) {
                throw new exception2('参数无效3');
            }
        }
        if (!$user = users::getItem($user_id)) {
            throw new exception2('找不到用户');
        }
        if (!$parentUser = users::getItem($user['parent_id'])) {
            throw new exception2('找不到上级');
        }
        //得到自己和上级的返点
        $userRebates = userRebates::getUsersRebates(array($user['user_id'], $user['parent_id']), 0, 1);
        $selfRebate = $userRebates[$user['user_id']];
        $parentRebate = $userRebates[$user['parent_id']];
        $representLotterys = lottery::getRepresent();
        if (count($representLotterys) != count($rebates)) {
            throw new exception2('参数无效4');
        }

        $gaps = array();
        foreach ($representLotterys as $property_id => $lottery) {
            //注：这里按父结点返点生成gaps
            $gaps[$property_id] = userRebates::genRebateGap($lottery, $parentRebate[$property_id], $user['level'], false);  //注：平级功能取消，所以这里一律为false $parentUser['level'] < 2
        };
        //开始事务
        $GLOBALS['db']->startTransaction();
        foreach ($rebates as $property_id => $rebate) {
            $flag = false;
            foreach ($gaps[$property_id] as $vv) {
                if (round($vv['rebate'], 4) == round($rebate, 4)) {
                    $flag = true;
                }
            }
            if (!$flag) {
                throw new exception2('返点值设定不正确');
            }
            if ($rebate >= $parentRebate[$property_id]) {
                throw new exception2('返点设定不在预设的范围内');
            }
            //如果想设成高点号（1954-1942，即0.127-0.12），则需检查是否在公共配额范围之内
            //硬编码，高点号仅检查ssc的配额，十一运没有高点号概念
            if ($property_id == 1 && $rebate > 0.12) {
                //1.得到公共配额
                $prizeMode = 2 * $representLotterys[$property_id]['zx_max_comb'] * (1 - $representLotterys[$property_id]['total_profit'] + $rebate);
                $cfgKey = "limit_quota_$prizeMode";
                $pubQuota = config::getConfig($cfgKey, 1);
                //2.得到自已已开高点号数量，如果超过公共配额，则不允许操作 注：测试帐号也算一个名额，也可以不算的
                $directChilds = users::getItems($parentUser['user_id'], false, 0, array(), array(8, 1));
                unset($directChilds[$user_id]); //自己当然不用算在内
                $directChildsRebates = userRebates::getUsersRebates(array_keys($directChilds), 1, 0);
                $count = 0;
                foreach ($directChildsRebates as $kk => $vv) {
                    if ($vv == $rebate) {
                        $count++;
                    }
                }
                //私人配额先不管，有需要再加，注意userRebates::saveRebate()也需要同步加
                $privateQuotas = @unserialize($parentUser['quota']);
                if (!isset($privateQuotas[$cfgKey])) {
                    $privateQuotas[$cfgKey] = 0;
                }
                log2("{$parentUser['username']}(id={$parentUser['user_id']})在升点用户{$user['username']}(id={$user['user_id']})，目前有{$prizeMode}高点帐号的人有{$count}个，此高点帐号的公共配额限制是{$pubQuota}个，私人额外配额是{$privateQuotas[$cfgKey]}个");
                if ($count >= $pubQuota + $privateQuotas[$cfgKey]) {
                    throw new exception2("{$prizeMode}高点号的限额已达到上限" . ($pubQuota + $privateQuotas[$cfgKey]) . "，不能再升点");
                }
            }
            $result = userRebates::saveUserRebateLoop($gaps, $user_id, $property_id, $rebate);
            if ($result['errno'] != 0) {
                throw new exception2($result['errstr']);
            }
        }

        //everything is OK
        $GLOBALS['db']->commit();
        $GLOBALS['mc']->delete(__CLASS__, $user_id);

        return true;
    }*/

    /**
     * 保存用户返点
     * @param $user_id
     * @param $prize_mode
     * @throws exception2
     */
    static public function saveRebate($user_id, $prize_mode)
    {
        if (!$user = users::getItem($user_id)) {
            throw new exception2('找不到用户');
        }
        $rebates = self::getRebateByPrizeMode($prize_mode, null);

        foreach ($rebates as $property_id => $rebate) {
            $data = array(
                'user_id' => $user_id,
                'property_id' => $property_id,
                'rebate' => strval($rebate/100),
                'status' => 1,
            );
            if(userRebates::saveItem($data, $errorUserIds) === false){
                throw new exception2('保存返点失败');
            }
        }
        return true;
    }

    /**
     * 增加返点
     * @param $user_id
     * @return bool
     * @throws exception2
     */
    static public function addRebate($user_id)
    {
        $userRebates = userRebates::getItems([$user_id]);
        $userPrizeMode = userRebates::userPrizeMode($user_id);
        $configRebates = userRebates::getRebateByPrizeMode($userPrizeMode, null);

        $rebates = [];
        foreach ($userRebates as $key=>$rebate){
            $rebates[$rebate['property_id']] = strval($rebate['rebate']*100);
        }
        $addRebates = array_diff_key($configRebates, $rebates);

        if($addRebates){
            $GLOBALS['db']->startTransaction();
            foreach ($addRebates as $property_id => $rebate){
                $data = array(
                    'user_id' => $user_id,
                    'property_id' => $property_id,
                    'rebate' => strval($rebate/100),
                    'status' => 1,
                );
                $res = userRebates::saveItem($data, $errorUserIds);
                if ($res === false) {
                    $GLOBALS['db']->rollback();
                    return false;
                }
            }
            $GLOBALS['db']->commit();
        }
        return true;
    }

    /**
     * @param $user
     * @param $setPrizeMode
     */
    static public function updateRebate($user, $setPrizeMode)
    {
        if(!self::addRebate($user['user_id'])) return false;

        if($setPrizeMode != self::userPrizeMode($user['user_id']))
        {
            $setRebates = self::getRebateByPrizeMode($setPrizeMode, null);
            $userRebates = userRebates::getItems([$user['user_id']]);
            $userPrizeMode = userRebates::userPrizeMode($user['user_id']);

            $setQuota = [];
            $userQuota = $user['quota'] ? unserialize($user['quota']) : [];

            //修改配额
/*            if($sQuota = users::generateQuota($user['level'], $setPrizeMode))
            {
                $aQuota = unserialize($sQuota);
                foreach ($aQuota as $prizeMode => $limit){
                    $setQuota[$prizeMode] = isset($userQuota[$prizeMode]) ? $userQuota[$prizeMode] : 0;
                }
            }
            $setQuota = $setQuota ? serialize($setQuota): '';*/

            $GLOBALS['db']->startTransaction();

/*            if(users::updateItem($user['user_id'], ['quota'=>$setQuota]) === false){
                $GLOBALS['db']->rollback();
                throw new exception2('设置配额失败');
            }*/

            //降点,上级回收配额
            if($user['quota'] && $setPrizeMode < $userPrizeMode)
            {
                $retrieve = [];
                foreach (unserialize($user['quota']) as $prize_mode => $available){
                    if($prize_mode > $setPrizeMode && $available){
                        $retrieve[$prize_mode] = $available;
                    }
                }
                if($retrieve)
                {
                    foreach ($retrieve as $prize_mode=>$available){
                        if(users::decQuota($user, $prize_mode, $available) === false){
                            $GLOBALS['db']->rollback();
                            return false;
                        }
                    }

                    if($user['level'] != 0){
                        if($parentUser = users::getItem($user['parent_id'])) {
                            foreach ($retrieve as $prize_mode=>$available){
                                if(users::addQuota($parentUser, $prize_mode, $available) === false){
                                    $GLOBALS['db']->rollback();
                                    return false;
                                }
                            }
                        }
                    }
                }
            }

            //修改返点
            foreach ($userRebates as $rebate)
            {
                if(isset($setRebates[$rebate['property_id']]))
                {
                    $data = array(
                        'user_id'=>$user['user_id'],
                        'rebate' => strval($setRebates[$rebate['property_id']]/100),
                    );
                    if(userRebates::updateItem($rebate['ur_id'], $data) === false ){
                        $GLOBALS['db']->rollback();
                        throw new exception2('更新返点失败');
                    }
                }
            }
            $GLOBALS['db']->commit();
        }
        return true;
    }


    /**
     *
     * @param type $data
     * @param type $errorUserIds 用于保存有冲突的下级用户id
     * @return type
     * @throws exception2
     */
    static public function saveItem($data, &$errorUserIds = NULL)
    {
        if (!is_array($data) || empty($data['user_id']) || empty($data['property_id']) || !isset($data['rebate']) || empty($data['status'])) {
            throw new exception2('参数无效');
        }

        $sql = "SELECT * FROM user_rebates WHERE user_id = {$data['user_id']} AND property_id = '{$data['property_id']}'";
        if ($result = $GLOBALS['db']->getRow($sql)) {
            if ($data['rebate'] < $result['rebate']) {
                $representLotterys = lottery::getRepresent();
                $user = users::getItem($data['user_id']);
                $childrentUsers = users::getItems($data['user_id'], false, 0, array('user_id', 'username'));
//                $childrentUserIds = array();
//                foreach ($childrentUsers as $childrentUser) {
//                    $childrentUserIds[] = $childrentUser['user_id'];
//                }
                $childrentUserIds=array_column($childrentUsers,'user_id');
                $childrentRebates = userRebates::getUsersRebates($childrentUserIds, 0, 1);
                $errorUsernames = array();
                foreach ($childrentRebates as $childrentUserId => $childrentRebate) {
                    if (isset($childrentRebate[$data['property_id']]) && $childrentRebate[$data['property_id']] >= $data['rebate']) {
                        if ($errorUserIds !== null) {
                            $errorUserIds[] = $childrentUserId;
                        }
                        $errorUsernames[] = $childrentUsers[$childrentUserId]['username'];
                    }
                }
                if ($errorUsernames) {
                    throw new exception2('设置返点失败:' . $user['username'] . '的直接下属 [' . implode(',', $errorUsernames) . '] 的' . $representLotterys[$data['property_id']]['cname'] . '返点大于或者等于' . $data['rebate']);
                }

                //throw new exception2('返点不能下调');
            }
            return userRebates::updateItem($result['ur_id'], $data);
        }

        return userRebates::addItem($data);
    }

    static public function saveUserRebateLoop($gaps, $user_id, $property_id, $newRebate)
    {
//logdump($user_id, $newRebate);
        $result = array('errno' => 0, 'errstr' => '');
        $errorUserIds = array();
        try {
            $data = array(
                'user_id' => $user_id,
                'property_id' => $property_id,
                'rebate' => $newRebate,
                'status' => 1,
            );
            $res = userRebates::saveItem($data, $errorUserIds);
            if ($res === false) {
                $result['errno'] = -3;
                $result['errstr'] = 'db error';
            }
        } catch (exception2 $e) {
            $result['errno'] = -2;
            $result['errstr'] = $e->getMessage();

            if ($errorUserIds && $newRebate != 0) {
                foreach ($errorUserIds as $errorUserId) {
                    $tempRebate = 0;
                    for ($i = 0; $i < count($gaps[$property_id]); $i++) {
                        if ($gaps[$property_id][$i]['rebate'] < $newRebate) {
                            //下级设置为这个上级的下一返点
                            $tempRebate = $gaps[$property_id][$i]['rebate'];
                            break;
                        }
                    }
                    $result = userRebates::saveUserRebateLoop($gaps, $errorUserId, $property_id, $tempRebate);
                    //设置下级返点的逻辑错误 ，不能往下执行了.
                    if ($result['errno'] != 0) {
                        return $result;
                    }
                }
                //下级返点冲突解决，重新尝试执行
                $result = userRebates::saveUserRebateLoop($gaps, $user_id, $property_id, $newRebate);
            }
            if ($errorUserIds && $newRebate == 0) {
                $result['errno'] = -1;
                $result['errstr'] = '返点已经到最小值0,不能设置下级返点，上级user_id:' . $user_id;
            }
        }

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data) || !isset($GLOBALS['cfg']['property'][$data['property_id']]) || !isset($data['user_id']) || !isset($data['rebate'])) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('user_rebates', $data);
    }

    static public function updateItem($ur_id, $data)
    {
        if (!is_numeric($ur_id) || !is_array($data)) {
            throw new exception2('参数无效');
        }
        // $GLOBALS['xc']->delete(__CLASS__, $data['user_id']);

        if ($GLOBALS['db']->updateSM('user_rebates', $data, array('ur_id' => $ur_id)) === false) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    /**
     * 通过奖金组模式获取返点
     * @param $prizeMode
     * @param $property_id
     * @return array
     */
    static public function getRebateByPrizeMode($prizeMode,$property_id = 1){

        $rebates = self::$hiddenRebates+self::$highRebates+self::$basicRebates;
        if($property_id){
            return $rebates[$prizeMode][$property_id];
        }else{
            return $rebates[$prizeMode];
        }

        //$representLotterys = lottery::getRepresent();
        //return $prizeMode/2/$representLotterys[$property_id]['zx_max_comb']-1+$representLotterys[$property_id]['total_profit'];
    }

    /**
     * 通过返点获取奖金组模式
     * @param $rebate
     * @param $property_id
     * @return int
     */
    static public function getPrizeModeByRebate($rebate, $property_id = 1)
    {
        $rebates = self::$hiddenRebates+self::$highRebates+self::$basicRebates;
        foreach ($rebates as $prizeMode => $val){
            if($val[$property_id] == $rebate) return $prizeMode;
        }
        //$representLotterys = lottery::getRepresent();
        //return 2 * $representLotterys[$property_id]['zx_max_comb'] * (1 - $representLotterys[$property_id]['total_profit'] + $rebate);
    }


    /**
     * 获取用户奖金组
     * @param $user_id
     * @return int
     * @throws exception2
     */
    static public function userPrizeMode($user_id)
    {
        $userRebate = strval(userRebates::getUserRebate($user_id, 1)*100);
        return  userRebates::getPrizeModeByRebate($userRebate, 1);
    }

    /**
     * 由level和prizeMode获取所有奖金组和返点
     * @param $level
     * @param null $prizeMode
     * @param bool $high
     * @return array
     */
    static public function levelPrizeModes($level, $prizeMode = null, $high = false)
    {
        $data = $rebates = [];

        $level or $rebates += self::$hiddenRebates;
        $rebates += self::$highRebates;
        $high or $rebates += self::$basicRebates;

        $prizeMode or $prizeMode = max(array_keys($rebates));

        foreach ($rebates as $prize_mode => $rebate) {
            if($prizeMode >= $prize_mode) $data[$prize_mode] = number_format($rebate[1],1);
        }
        return $data;
    }

    /**
     * 获取可增加下级的奖金组和返点
     * @param $user
     * @param bool $in_basic
     * @return array
     */
    static public function addSubPrizeModes($user, $in_basic = true){

        $data = [];
        $aQuotas = users::countOfQuota($user);
        $userPrizeMode = self::userPrizeMode($user['user_id']);
        foreach ($aQuotas as $aQuota) {
            if($aQuota['available'] > $aQuota['used']) {
                $data[$aQuota['prize_mode']] = $aQuota['rebate'];
            }
        }

        if($in_basic)
        {
            foreach (self::$basicRebates as $prize_mode => $aRebate) {
                if($prize_mode<$userPrizeMode) {
                    $data[$prize_mode] = number_format($aRebate[1],1);
                }
            }
        }
        return $data;
    }

    /**
     *获取可编辑下级的奖金组和返点
     * @param $user
     * @param $sys_user
     * @return array
     */
    static public function setSubPrizeModes($user, $sys_user = true)
    {
        //用户奖金组
        $userPrizeMode = userRebates::userPrizeMode($user['user_id']);
        $data[$userPrizeMode] = self::getRebateByPrizeMode($userPrizeMode);

        //直属上级
        $maxPrizeMode = max(array_keys(self::$hiddenRebates));
        if($user['parent_id']){
            $maxPrizeMode = userRebates::userPrizeMode($user['parent_id']);
        }

        //直属下级
        $subMaxPrizeMode = 0;
        if($directChilds = users::getItems($user['user_id'], false, 0, array(), array(8, 1)))
        {
            foreach ($directChilds as $subId => $subData)
            {
                $subPrizeMode = self::userPrizeMode($subId);
                if($subPrizeMode > $subMaxPrizeMode) $subMaxPrizeMode = $subPrizeMode;
            }
        }

        //可用奖金组=>返点
        $basicPrizeMode = array_keys(self::$basicRebates);
        $highPrizeMode = $sys_user ? array_keys(self::$hiddenRebates+self::$highRebates) : array_keys(self::addSubPrizeModes(users::getItem($user['parent_id']), false));

        $rebates = self::$hiddenRebates+self::$highRebates+self::$basicRebates;
        foreach ($rebates as $prizeMode=>$rebate)
        {
            if(in_array($prizeMode, $highPrizeMode) && $prizeMode <= $maxPrizeMode && $prizeMode >= $subMaxPrizeMode){
                $data[$prizeMode] = $rebate[1];
            }
            elseif(in_array($prizeMode, $basicPrizeMode) && $prizeMode > $subMaxPrizeMode && $prizeMode < $maxPrizeMode){
                $data[$prizeMode] = $rebate[1];
            }
        }
        ksort($data);
        return $data;
    }
}

?>