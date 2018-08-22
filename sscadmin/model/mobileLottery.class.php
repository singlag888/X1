<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class mobileLottery
{

    const CACHE_TIME = 7200;

    /**
     * @todo   为手机端API提供彩种数据
     * @author Davy 2016年1月6日
     * @param  $lotteryID  0 表示所有彩种;  > 0 表示单一彩种查询; Array   支持部分彩种
     * @param  $fields     定义返回列
     * @return Array
     */
    static public function getLotterys($lotteryId = 0, $status = 8)
    {
        if(is_array($lotteryId) && !empty($lotteryId)) {
            $cacheKey = md5(serialize($lotteryId));
        }
        else {
            $cacheKey = __FUNCTION__ . $lotteryId;
        }

        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = "SELECT lottery_id, name, cname, lottery_type, property_id , yearly_start_closed, yearly_end_closed, status  FROM lottery WHERE 1";
            if(is_array($lotteryId) && !empty($lotteryId)) {
                $sql .= " AND lottery_id IN ( '" . implode("','", $lotteryId) . "')";
            }
            elseif ($lotteryId > 0) {
                $sql .= " AND lottery_id = " . intval($lotteryId);
            }
            if ($status != -1) {
                $sql .= " AND status = $status";
            }
            $result = $GLOBALS['db']->getAll($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 7200);
        }

        return $result;
    }

    /**
     * @todo   根据彩种ID获取旗下玩法列表
     *  1、lotteryID==0   methodGroupID==0  查所有彩种所有可玩玩法
      2、lotteryID==1   methodGroupID==0  查所有彩种==1的所有可玩玩法
      3、lotteryID==0   methodGroupID==164  查玩法组164的所有可玩玩法
     * @author Davy 2016年1月8日
     * @param  $lottery_id  彩种ID
     * @return Array
     */
    static public function getPlayMethods($lottery_id = 0, $mg_id = 0)
    {
        $cacheKey = __FUNCTION__ . $lottery_id . '_' . $mg_id;
        //if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
        $sql = 'SELECT a.method_id,a.lottery_id,a.name,a.cname,a.mg_id,b.name as mg_name,a.can_input,a.levels FROM methods a LEFT JOIN method_groups b ON a.mg_id=b.mg_id WHERE a.status = 8';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id > 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }

        if ($mg_id != 0) {
            $sql .= " AND a.mg_id = " . intval($mg_id);
        }
        $sql .= ' ORDER BY b.sort ASC, a.sort ASC';
        $result = $GLOBALS['db']->getAll($sql);
        $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        //}
        $tmp = array();
        //混合组选手机端不上
        $zuxuanMethodNames = array('SXHHZX', 'QSHHZX', 'ZSHHZX');
        foreach ($result as $k => $v) {
            if (!isset($tmp[$v['mg_id']])) {
                $tmp[$v['mg_id']] = array('mg_id' => $v['mg_id'], 'mg_name' => $v['mg_name'], 'childs' => array());
            }
            if (!in_array($v['name'], $zuxuanMethodNames)) {
                $tmp[$v['mg_id']]['childs'][] = $v;
            }
        }
        $result = array_values($tmp);

        return $result;
    }


    static public function cancelTrace($user_id, $trace_id, $package_ids, $admin_id = 0)
    {
        if (!is_numeric($user_id) || !is_numeric($trace_id) || !is_array($package_ids) || !count($package_ids)) {
            throw new exception2('参数无效');
        }

        //130903 为防止并发读 加入独占锁 加锁
        $GLOBALS['db']->startTransaction();

        if (!$trace = traces::getItem($trace_id, true)) {
            throw new exception2('找不到该追号单', 1100);
        }

        if ($trace['is_locked']) {
            throw new exception2('该追号单正在使用', 1101);
        }

        if (!traces::updateItem($trace_id, array('is_locked' => 1))) {
            throw new exception2('锁定追号单失败', 9801);
        }

        if (!$packages = projects::getPackages(0, -1, -1, '', $trace_id, 0, $user_id)) {
            throw new exception2('找不到该追号计划相应订单', 1102);
        }
        foreach ($package_ids as $package_id) {
            if (!isset($packages[$package_id])) {
                throw new exception2('参数无效');
            }
        }
        foreach ($package_ids as $package_id) {
            try {
                game::cancelPackage($packages[$package_id], $admin_id ? 9 : 1, $admin_id);    //1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
            } catch (Exception $e) {
                //移动端已经开奖的过滤掉不报错：JYZ-580  [APP] 追号单撤单问题
                if($e->getCode() == 1301) {
                    continue;
                }
                throw new exception2($e->getMessage(), $e->getCode());
            }

        }

        //如果没有可以进行的追号单，应更改追号状态为已取消
        foreach ($package_ids as $package_id) {
            if (isset($packages[$package_id])) {
                unset($packages[$package_id]);
            }
        }
        if (!$packages) {
            traces::updateItem($trace_id, array('status' => 3));
        }
        else {
            $package = reset($packages);
            $issueInfos = issues::getItemsByIssue($package['lottery_id'], array_keys(array_spec_key($packages, 'issue')));
            $count = 0;
            foreach ($packages as $v) {
                if (time() < strtotime($issueInfos[$v['issue']]['cannel_deadline_time']) && $v['cancel_status'] == 0) {
                    $count++;
                }
            }
            if (!$count) {
                traces::updateItem($trace_id, array('status' => 3));
            }
        }

        //130903 为防止并发读 加入独占锁 解锁
        traces::updateItem($trace_id, array('is_locked' => 0));

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return true;
    }


    /**
     * @todo   为手机端API提供彩种数据
     * @author Davy 2016年1月6日
     * @param  $lotteryID  0 表示所有彩种
     * @param  $fields     定义返回列
     * @return array
     */
    static public function getNotices($status = 1, $type = -1, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT notice_id, title, img_path, link, start_time, is_stick FROM notices WHERE 1';
        $sql .= " AND type = '".intval($type)."'";
        $sql .= " AND start_time <= '".date('Y-m-d H:i:s')."'";
        $sql .= " AND expire_time >= '".date('Y-m-d H:i:s')."'";
        if ($status !== NULL) {
            $sql .= " AND status = " . intval($status);
        }
        $sql .= ' ORDER BY is_stick DESC, notice_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;

    }


    /**
     * 彩种过滤器
     * 由于前期移动端只支持部分彩种所以设置过滤器
     * 全部都走这个过滤器的话以后需要改动统一调整本方法即可
     * @param   $lotteryID > 0 用户传入固定ID则按照具体ID查询
     * $lotteryID == 0 认为获取所有支持的彩种
     * @author davy 2016年1月26日
     * @return array lotteryIDs  OR int lotteryID
     */
    static public function lotterysFilter($lotteryID)
    {
        //配置：支持的彩种列表
        $support_ids = array_keys($GLOBALS['cfg']['lotteryList']);

        //获取所有支持的彩种列表
        if($lotteryID == 0) {
            $lotteryID = $support_ids;
        }
        elseif($lotteryID < 0 || !is_numeric($lotteryID) || !in_array($lotteryID . '', $support_ids)) {
            showMsg(8002, mobileErrorCode::SYS_GET_PARAM_ERR);
        }

        return $lotteryID;
    }

    /**
     * 移动端最新版本查询
     * @param string $start_time 当前时间
     * @author davy 2016年1月26日
     * @return array
     */
    static public function getLastVersion($start_time)
    {
        $sql = 'SELECT * FROM mobile_release WHERE start_time <= \'' . $start_time . '\' AND status = 1 AND type = 1 order by version_number desc LIMIT 1';

        return $GLOBALS['db']->getRow($sql);
    }
}
