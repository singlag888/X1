<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

class issues extends baseModel
{

    static $hotIssueCount = 10; //用于统计冷热的期数

    static public   function getItem($id = 0, $issue = '', $lotteryId = 0)
    {
        //用$issue查的，必须也同时提供$lotteryId
        if (!is_numeric($id) || (!$id && !$issue) || ($issue && !$lotteryId)) {
            throw new exception2('参数无效');
        }

        if ($id > 0) {
            $sql = 'SELECT * FROM ssc_share.`issues` WHERE issue_id = ' . intval($id);
        }
        else {
            if (!preg_match('`^\d[\d-]{4,}\d$`Ui', $issue)) {
                throw new exception2('参数无效');
            }
            $sql = 'SELECT * FROM ssc_share.`issues` WHERE issue = \'' . $issue . '\'';
        }
        if ($lotteryId > 0) {
            $sql .= " AND lottery_id = " . intval($lotteryId);
        }

        return $GLOBALS['share_db']->getRow($sql);
    }

    //example $fields = 'issue,belong_date'
       static public   function getItems($lotteryId = 0, $belong_date = '', $start_sale_time1 = 0, $start_sale_time2 = 0, $end_sale_time1 = 0, $end_sale_time2 = 0, $status_code = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE, $fields = '*')
    {

        $sql = "SELECT {$fields} FROM ssc_share.`issues` WHERE 1";
        if ($lotteryId > 0) {
            $sql .= " AND lottery_id = " . intval($lotteryId);
        }
        if (is_array($belong_date) && count($belong_date) == 2) {
            $sql .= " AND belong_date >='{$belong_date[0]}' AND belong_date <= '{$belong_date[1]}'";
        }
        elseif ($belong_date != '') {
            $sql .= " AND belong_date = '$belong_date'";
        }

        if ($start_sale_time1 > 0) {
            $sql .= " AND start_sale_time > '" . date('Y-m-d H:i:s', $start_sale_time1) . "'";
        }
        if ($start_sale_time2 > 0) {
            $sql .= " AND start_sale_time < '" . date('Y-m-d H:i:s', $start_sale_time2) . "'";
        }

        if ($end_sale_time1 > 0) {
            $sql .= " AND end_sale_time > '" . date('Y-m-d H:i:s', $end_sale_time1) . "'";
        }
        if ($end_sale_time2 > 0) {
            $sql .= " AND end_sale_time < '" . date('Y-m-d H:i:s', $end_sale_time2) . "'";
        }
        if ($status_code != -1) {
            $sql .= " AND status_code = $status_code";
        }

        if ($order_by) {
            $sql .= " ORDER BY $order_by";
        }
        else {
            $sql .= " ORDER BY issue_id ASC";
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['share_db']->getAll($sql);
    }

    static public   function getItemsMini($lotteryId = 0, $belong_date = '', $start_sale_time1 = 0, $start_sale_time2 = 0, $end_sale_time1 = 0, $end_sale_time2 = 0, $status_code = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE, $fields = '*')
    {

        $sql = "SELECT {$fields} FROM ssc_share.`issues_mini` WHERE 1";
        if ($lotteryId > 0) {
            $sql .= " AND lottery_id = " . intval($lotteryId);
        }
        if (is_array($belong_date) && count($belong_date) == 2) {
            $sql .= " AND belong_date >='{$belong_date[0]}' AND belong_date <= '{$belong_date[1]}'";
        }
        elseif ($belong_date != '') {
            $sql .= " AND belong_date = '$belong_date'";
        }

        if ($start_sale_time1 > 0) {
            $sql .= " AND start_sale_time > '" . date('Y-m-d H:i:s', $start_sale_time1) . "'";
        }
        if ($start_sale_time2 > 0) {
            $sql .= " AND start_sale_time < '" . date('Y-m-d H:i:s', $start_sale_time2) . "'";
        }

        if ($end_sale_time1 > 0) {
            $sql .= " AND end_sale_time > '" . date('Y-m-d H:i:s', $end_sale_time1) . "'";
        }
        if ($end_sale_time2 > 0) {
            $sql .= " AND end_sale_time < '" . date('Y-m-d H:i:s', $end_sale_time2) . "'";
        }
        if ($status_code != -1) {
            $sql .= " AND status_code = $status_code";
        }

        if ($order_by) {
            $sql .= " ORDER BY $order_by";
        }
        else {
            $sql .= " ORDER BY issue_id ASC";
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['share_db']->getAll($sql);
    }


    static public function getItemsNumber($lotteryId = 0, $belong_date = '', $start_sale_time1 = 0, $start_sale_time2 = 0, $end_sale_time1 = 0, $end_sale_time2 = 0, $status_code = -1)
    {
        $sql = "SELECT COUNT(*) AS count FROM ssc_share.`issues` WHERE 1";
        if ($lotteryId > 0) {
            $sql .= " AND lottery_id = " . intval($lotteryId);
        }
        if (is_array($belong_date) && count($belong_date) == 2) {
            $sql .= " AND belong_date >='{$belong_date[0]}' AND belong_date <= '{$belong_date[1]}'";
        }
        elseif ($belong_date != '') {
            $sql .= " AND belong_date = '$belong_date'";
        }

        if ($start_sale_time1 > 0) {
            $sql .= " AND start_sale_time > '" . date('Y-m-d H:i:s', $start_sale_time1) . "'";
        }
        if ($start_sale_time2 > 0) {
            $sql .= " AND start_sale_time < '" . date('Y-m-d H:i:s', $start_sale_time2) . "'";
        }

        if ($end_sale_time1 > 0) {
            $sql .= " AND end_sale_time > '" . date('Y-m-d H:i:s', $end_sale_time1) . "'";
        }
        if ($end_sale_time2 > 0) {
            $sql .= " AND end_sale_time < '" . date('Y-m-d H:i:s', $end_sale_time2) . "'";
        }
        if ($status_code != -1) {
            $sql .= " AND status_code = $status_code";
        }
        $result = $GLOBALS['share_db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsByIssue($lotteryId, $issues)
    {
        if ($lotteryId <= 0 || !is_array($issues)) {
            throw new exception2('参数无效11');
        }
        if (count($issues) == 0) {
            return array();
        }
        $sql = "SELECT * FROM ssc_share.`issues` WHERE lottery_id = $lotteryId AND issue in('" . implode("','", $issues) . "')";

        return $GLOBALS['share_db']->getAll($sql, array(),'issue');
    }

    // 得到当前正处在销售期的奖期
    static public  function getCurrentIssue($lotteryId, $time = 'CURRENT_TIME')
    {
        if ($time == 'CURRENT_TIME') {
            $time = $_SERVER['REQUEST_TIME'];
        }

        $belongDate = date('Y-m-d', $time);
        //新疆时时彩 00-02时间跨天的期号是属于前一天的。
        if ($lotteryId == 4) {
            $hour = date('H', $time);
            if ($hour == '00' || $hour == '01') {
                $belongDate = date('Y-m-d', $time - 60 * 60 * 24);
            }
        }
        $dateTime = date('Y-m-d H:i:s', $time);
        $sql = 'SELECT * FROM ssc_share.`issues_mini` WHERE lottery_id = ' . intval($lotteryId) . " AND `start_sale_time` <= '$dateTime' AND `end_sale_time` >= '$dateTime'";
        //如果是六合彩不用加belongdate查询字段
        $where = $lotteryId != 21 && $lotteryId != 22 && $lotteryId != 26  ? " AND belong_date = '{$belongDate}'" : '';
        $limit = ' LIMIT 1';
        return $GLOBALS['share_db']->getRow($sql.$where.$limit);
    }

    /**
     * 查询出对应彩票的剩余期数
     * by snow
     * @return array  返回数据
     */
    public static function getExpireIssueList()
    {
        //>>status 0 为禁用 . 8 为正常使用
        $time = date('Y-m-d H:i:s');
        $sql = <<<SQL
SELECT a.lottery_id,`name`,cname,b.count_num  FROM ssc_share.`lottery`  AS a  LEFT JOIN (
SELECT COUNT(*) AS count_num, lottery_id FROM ssc_share.`issues_mini`
WHERE start_sale_time > '{$time}' GROUP BY lottery_id
) AS b ON  a.lottery_id = b.lottery_id
WHERE a.status = 8
SQL;

        $result = $GLOBALS['share_db']->getAll($sql);
        $expireIssueList = [];
        if($result){
            foreach($result as $key => $val){
                $val['count_num'] = is_null($val['count_num']) ? 0 : $val['count_num'];
                switch($val['lottery_id']){
                    //>>低频彩,剩余1期报警
                    case 9 :  //>>3d
                    case 10 : //>>p3p5
                    case 21 : //>>六合彩
                    case 22 : //>>双色球
                        if($val['count_num'] < 1){
                            $expireIssueList[] = $val;
                        }
                        break;
                    case 15:  //>>秒秒彩,过滤掉
                        break;
                    default; //>>高频彩 .30期报警
                        if($val['count_num'] < 30){
                            $expireIssueList[] = $val;
                        }
                }
            }
        }

        return $expireIssueList;
    }

    /**
     * 得到最近应该开奖的奖期,issues_tmp是issues的临时表，把cpu压力转向临时表
     * @param <type> $lotteryId
     * @param <boolean> $no_condition 是否无条件，差别在于是否方便录号
     * @return <type>
     */
    static public  function getLastIssue($lotteryId, $no_condition = false)
    {
        // $sql = 'SELECT i.*,l.cname FROM `issues_tmp` i LEFT JOIN lottery l ON i.lottery_id=l.lottery_id WHERE i.lottery_id = ' . intval($lotteryId) . " AND i.belong_date >='" . date('Y-m-d', strtotime("-8 day")) . "'";
        // if ($no_condition) {
        //     $sql .= " AND i.end_sale_time < '" . date('Y-m-d H:i:s') . "'";
        // }
        // else {
        //     $sql .= " AND i.earliest_input_time < '" . date('Y-m-d H:i:s') . "'";
        // }
        // $sql .= " ORDER BY issue_id DESC LIMIT 1";
        // $sql = 'SELECT * FROM `issues_tmp` WHERE lottery_id = ' . intval($lotteryId);

        // if (!$result = $GLOBALS['share_db']->getRow($sql)) {
        //     return array();
        // }
        $GLOBALS['redis']->pushPrefix()->select(REDIS_DB_COMMON_DATA);
        $issuesTmp = $GLOBALS['redis']->hGet('cli', 'issuesTmp');
        $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);

        if(!$issuesTmp){
            return array();
        }

        $issuesTmpArr = array_spec_key(json_decode($issuesTmp, true), 'lottery_id') ;

        return isset($issuesTmpArr[$lotteryId]) ? $issuesTmpArr[$lotteryId] : array();
    }

    /**
     * 返回最近一期没有开奖的奖期
     * @param <int> $lotteryId
     * @param <bool> $check_is_saling 是否取正在销售期的奖期
     * @return <array>
     */
    static public  function getLastNoDrawIssue($lotteryId, $check_is_saling = true)
    {
        if ($lotteryId <= 0) {
            return array();
        }

        //优化情节 尽管Using filesort难以避免，但用上belong_date大量减少结果集，查询时间由生产环境的200ms缩短为20ms!
        $sql = 'SELECT issue_id FROM ssc_share.`issues_mini` USE INDEX(lottery_id_2) WHERE lottery_id = ' . intval($lotteryId) . ' AND belong_date <= "' . date("Y-m-d", strtotime('+1 days')) . '" AND status_code >= 2 ORDER BY issue_id DESC';

        if (!$openedIssue = $GLOBALS['share_db']->getRow($sql)) {
            return array();
        }
        $sql = 'SELECT * FROM ssc_share.`issues_mini` WHERE lottery_id = ' . intval($lotteryId) . ' AND issue_id>"' . $openedIssue['issue_id'] . '" AND status_code < 2 ORDER BY issue_id ASC';

        if (!$unopenedIssue = $GLOBALS['share_db']->getRow($sql)) {
            return array();
        }
        if ($check_is_saling) {
            // 如果正在销售期，返回空
            if (date('Y-m-d H:i:s') < $unopenedIssue['earliest_input_time']) {
                return array();
            }
        }

        return $unopenedIssue;
    }

    /**
     * 返回六合彩最近一期没有开奖的奖期
     * @param <int> $lotteryId
     * @param <bool> $check_is_saling 是否取正在销售期的奖期
     * @return <array>
     */
    static public  function getLhcLastNoDrawIssue($lotteryId, $check_is_saling = true)
    {
        if ($lotteryId <= 0) {
            return array();
        }

        switch ($lotteryId) {
            case 21:
                $beginDay=date('Y-m-01', strtotime(date("Y-m-d")));
                $belongDate = date('Y-m-d', strtotime("$beginDay +1 month -1 day"));
                break;
            //以下为开发自主六合彩预留
            //case 六合分分彩id:
                //break;
            default:
                $belongDate = date("Y-m-d", strtotime('+1 days'));
        }

        //优化情节 尽管Using filesort难以避免，但用上belong_date大量减少结果集，查询时间由生产环境的200ms缩短为20ms!
        $sql = 'SELECT issue_id FROM ssc_share.`issues` USE INDEX(lottery_id_2) WHERE lottery_id = ' . intval($lotteryId) . ' AND belong_date <= "' . $belongDate . '" AND status_code >= 2 ORDER BY issue_id DESC';

        if (!$openedIssue = $GLOBALS['share_db']->getRow($sql)) {
            return array();
        }
        $sql = 'SELECT * FROM ssc_share.`issues` WHERE lottery_id = ' . intval($lotteryId) . ' AND issue_id>"' . $openedIssue['issue_id'] . '" AND status_code < 2 ORDER BY issue_id ASC';

        if (!$unopenedIssue = $GLOBALS['share_db']->getRow($sql)) {
            return array();
        }
        if ($check_is_saling) {
            // 如果正在销售期，返回空
            if (date('Y-m-d H:i:s') < $unopenedIssue['earliest_input_time']) {
                return array();
            }
        }

        return $unopenedIssue;
    }

    /**
     * 返回六合彩没有开奖的奖期 按照时间倒顺序排列 最新的在最前面
     * @param <int> $lotteryId
     * @return <array>
     */
    static public  function getLhcNoDrawIssues($lotteryId)
    {
        if ($lotteryId <= 0) {
            return array();
        }

        switch ($lotteryId) {
            case 21:
                $beginDay=date('Y-m-01', strtotime(date("Y-m-d")));
                $belongDate = date('Y-m-d', strtotime("$beginDay +1 month -1 day"));
                break;
            //以下为开发自主六合彩预留
            //case 六合分分彩id:
                //break;
            default:
                $belongDate = date("Y-m-d", strtotime('+1 days'));
        }

        //优化情节 尽管Using filesort难以避免，但用上belong_date大量减少结果集，查询时间由生产环境的200ms缩短为20ms!
        $sql = 'SELECT * FROM ssc_share.`issues` USE INDEX(lottery_id_2) WHERE lottery_id = ' . intval($lotteryId) . ' AND belong_date <= "' . $belongDate . '" AND earliest_input_time < "'. date('Y-m-d H:i:s') .'" AND status_code < 2 ORDER BY issue_id DESC';

        if (!$results = $GLOBALS['share_db']->getAll($sql)) {
            return array();
        }

        return $results;
    }


    /**
     * 返回没有开奖的奖期 按照时间倒顺序排列 最新的在最前面
     * @param <int> $lotteryId
     * @return <array>
     */
    static public  function getNoDrawIssues($lotteryId, $limit = 0)
    {
        if ($lotteryId <= 0) {
            return array();
        }

        //优化情节 尽管Using filesort难以避免，但用上belong_date大量减少结果集，查询时间由生产环境的200ms缩短为20ms!
        $sql = 'SELECT * FROM ssc_share.`issues_mini` USE INDEX(lottery_id_2) WHERE lottery_id = ' . intval($lotteryId) . ' AND belong_date <= "' . date("Y-m-d", strtotime('+1 days')) . '" AND earliest_input_time < "'. date('Y-m-d H:i:s') .'" AND status_code < 2 ORDER BY issue_id DESC';

        if($limit != 0){
            $sql .= ' LIMIT '. $limit;
        }
        if (!$results = $GLOBALS['share_db']->getAll($sql)) {
            return array();
        }

        return $results;
    }


    //得到需要返点的奖期
    static public  function getNeed2RebateIssue($lotteryId)
    {
        //$sCondition   = " A.`statuscode`=2 AND A.`statususerpoint`!=2 AND A.`lotteryid`='". $this->iLotteryId
        //. "' AND A.`saleend`<'" . $sCurrentTime . "' ORDER BY A.`saleend` ASC";
        if ($lotteryId <= 0) {
            return array();
        }

        $sql = 'SELECT issue FROM ssc_share.`issues_mini` WHERE lottery_id = ' . intval($lotteryId) . " AND status_code = 2 AND status_rebate < 2 AND earliest_input_time < '" . date('Y-m-d H:i:s') . "' ORDER BY issue_id ASC";

        if (!$result = $GLOBALS['share_db']->getAll($sql, array(),'issue')) {
            return array();
        }

        return $result;
    }

    //得到需要检查中奖的奖期
    static public  function getNeed2CheckPrizeIssue($lotteryId)
    {
        if ($lotteryId <= 0) {
            return array();
        }

        $sql = "SELECT * FROM ssc_share.`issues_mini` WHERE lottery_id = " . intval($lotteryId) . " AND status_code = 2 AND status_check_prize < 2 AND earliest_input_time < '" . date('Y-m-d H:i:s') . "' ORDER BY issue_id ASC";
        if (!$result = $GLOBALS['share_db']->getRow($sql)) {
            return array();
        }

        return $result;
    }

    //得到需要派奖的奖期
    static public  function getNeed2SendPrizeIssue($lotteryId)
    {

        if ($lotteryId <= 0) {
            return array();
        }

        //improve: status_check_prize = 2会更加保险，必须是检查奖金CLI已经运行完毕
        $sql = 'SELECT * FROM ssc_share.`issues_mini` WHERE lottery_id = ' . intval($lotteryId) . " AND status_code = 2 AND status_send_prize < 2 AND earliest_input_time < '" . date('Y-m-d H:i:s') . "' AND status_check_prize = 2 ORDER BY issue_id ASC";
        if (!$result = $GLOBALS['share_db']->getRow($sql)) {
            return array();
        }

        return $result;
    }

    // 得到每天的奖期数 用于生成奖期的时候判断 待确认保留
    static public   function getDayIssueNumbers($lotteryId, $startDate = 0, $endDate = 0)
    {
        if ($lotteryId <= 0) {
            throw new exception2('彩种ID参数无效');
        }
        $sql = 'SELECT belong_date,count(*) AS count FROM ssc_share.`issues` WHERE lottery_id=' . intval($lotteryId);
        if ($startDate) {
            $sql .= ' AND belong_date >=' . date('Y-m-d', $startDate);
        }
        if ($endDate) {
            $sql .= ' AND belong_date <=' . date('Y-m-d', $endDate);
        }
        $sql .= ' GROUP BY belong_date ORDER BY belong_date ASC';
        $tmp = $GLOBALS['share_db']->getAll($sql);
        $result = array();
        foreach ($tmp as $v) {
            $result[$v['belong_date']] = $v['count'];
        }

        return $result;
    }

    //得到开奖号码
    static public   function getCodes($lotteryId, $issues)
    {
        //用$issue查的，必须也同时提供$lotteryId
        if (!is_numeric($lotteryId) || !is_array($issues)) {
            throw new exception2('参数无效');
        }
        $db = 'share_db';
        if ($lotteryId == 15) {
            $sql = "SELECT * FROM issue_mmc WHERE issue IN('" . implode("','", $issues) . "')";
            $db = 'db';
        }
        else {
            $sql = "SELECT * FROM ssc_share.`issues` WHERE lottery_id = " . intval($lotteryId) . " AND issue IN('" . implode("','", $issues) . "')";
        }

        $tmp = $GLOBALS[$db]->getAll($sql);
        $result = array();
        foreach ($tmp as $k => $v) {
            if ($lotteryId == 15) {
                $result[$v['issue']] = $v['code'];
            }
            elseif ($v['status_code'] == 2 && $v['rank'] >= 100) {
                $result[$v['issue']] = $v['code'];
            }
        }

        return $result;
    }

    //奖期批量生成
    static public   function genIssue($lotteryId, $startDate, $endDate, $firstIssue)
    {
        $startTS = strtotime($startDate);
        $endTS = strtotime($endDate);
        if ($startTS > $endTS || $endTS - $startTS > 86400 * 365) {
            throw new exception2('日期范围不合法！');
        }
        if (!$lottery = lottery::getItem($lotteryId)) {
            throw new exception2('找不到彩种信息！');
        }

        // 判断是否需要起始期号
        if (strpos($lottery['issue_rule'], 'd') === false) {
            if (!$firstIssue) {
                throw new exception2('没有天数的奖期规则必须指定起始期号！');
            }
            if (!issues::checkIssueRule($firstIssue, $lottery['issue_rule'])) {
                throw new exception2('请正确输入起始奖期！');
            }
        }

        $dayTs = $startTS;
        if($lotteryId == 18){//如果是东京，$startTS需要加一天
            $dayTs += 86400;
        }

        //删除可能有的重复奖期先
        issues::deleteItemByDate($lotteryId, $dayTs);
        /**
         *
         * CQSSC:   100121-054       ymd-[n3]
         * JX-SSC:  20100121-036    Ymd-[n3]
         * HLJSSC:  0016571         [n7]
         * SSL:     20100121-11     Ymd-[n2]
         * SD11Y:   10012131        ymd-[n2]
         * 格式符： y,m,d的值分别为0,1,0，0表示清零，1表示不清零
         */
        $rules = issues::analyze($lottery['issue_rule']);
        $totalCounter = 0;
        $curIssueNumber = intval(substr($firstIssue, 0 - $rules['n'])); // 获取期号，一般在最后几位

        for ($i = $startTS; $i <= $endTS; $i += 86400) {
            //休市则忽略
            if ($i >= strtotime($lottery['yearly_start_closed']) && $i <= strtotime($lottery['yearly_end_closed'])) {
                continue;
            }

            $belong_date = date('Y-m-d', $i);    // 属于哪天的奖期
            $sample = $rules['sample'];
            // 先替换日期大部
            if ($rules['ymd']) {
                $tampTs = $i;
                if($lotteryId == 18){//如果是东京1.5奖期就生成为第二天的
                    $tampTs += 86400 ;
                }
                $sample = @preg_replace('`([ymd]+)`ie', "date('\\1', $tampTs)", $sample);
            }

            // 得到当前期号$curIssue
            if ($rules['n']) {
                // 如果按天清零，或者按年清零的时候跨年了，则数字部分从头开始
                if (!$rules['d'] || (!$rules['y'] && date('Y', $i) > date('Y', $startTS))) {
                    $curIssueNumber = 1;
                }
            }
            // 开始生成
            /*
             *     [0] => Array
              (
              [start_time] => 05:00:00
              [end_time] => 09:58:30
              [cycle] => 10
              [end_sale] => 60
              [code_time] => 120
              [drop_time] => 60
              )
             */
            if($lotteryId == 18){//如果是东京1.5 belong_date需要在本次$i时间上加一天
                $belong_date = date('Y-m-d', $i+86400);
            }
            $datas = array();
            $setLoop = 0;
            foreach ($lottery['settings'] as $v) {

                if (!$v['is_use']) {
                    continue;
                }

                $isFirst = 0;
                $startTime = time2second($v['start_time']);
                $endTime = time2second($v['end_time']);

                if ($endTime < $startTime) {
                    $endTime += 86400;
                }

                if(($lotteryId == 18 || $lotteryId == 26) && $setLoop != 0 ){//如果是东京1.5第二销售阶段时间需要在原来时间戳基础上加一天
                    $startTime += 86400 ;
                    $endTime += 86400 ;
                }

                for ($j = $startTime; $j <= $endTime - $v['cycle'];) {
                    $curIssueStartTime = date('Y-m-d H:i:s', $i + $j - $v['end_sale']);

                    if (!$isFirst) {
                        $curIssueEndTimeStamp = $i + time2second($v['first_end_time']);
                        if(($lotteryId == 18 || $lotteryId == 26) && $setLoop != 0 ){
                            $curIssueEndTimeStamp += 86400;
                        }
                    }
                    else {
                        $curIssueEndTimeStamp = $i + $j + $v['cycle'];
                    }

                    $curIssueEndTime = date('Y-m-d H:i:s', $curIssueEndTimeStamp - $v['end_sale']);
                    $curDropTime = date('Y-m-d H:i:s', $curIssueEndTimeStamp - $v['drop_time']);
                    $curCodeTime = date('Y-m-d H:i:s', $curIssueEndTimeStamp + $v['code_time']);
                    $finalIssue = str_replace("[n{$rules['n']}]", str_pad($curIssueNumber, $rules['n'], '0', STR_PAD_LEFT), $sample);

                    // 写入
                    $data = array(
                        'lottery_id' => $lotteryId,
                        'belong_date' => $belong_date,
                        'issue' => $finalIssue,
                        'start_sale_time' => $curIssueStartTime,
                        'end_sale_time' => $curIssueEndTime,
                        'cannel_deadline_time' => $curDropTime,
                        'earliest_input_time' => $curCodeTime,
                    );
                    $datas[] = $data;
                    if (!$isFirst) {
                        $j = time2second($v['first_end_time']);
                        if(($lotteryId == 18 || $lotteryId == 26) && $setLoop != 0 ){
                            $j += 86400;
                        }
                    }
                    else {
                        $j += $v['cycle'];
                    }
                    $isFirst++;
                    $curIssueNumber++;
                    $totalCounter++;
                }

                $setLoop++;
            }

            //批量添加
            if (!$GLOBALS['db']->multipInsert('issues' , $datas)) {
                throw new exception2("奖期批量插入失败！");
            }
        }

        return $totalCounter;
    }

    /**
     * 获取最近的已开奖的奖期
     * @param <int> $lotteryId
     * @param <array> $startDateArr
     * @param <array> $endDateArr
     * @param <array> $issueArr
     * @return <int>
     */
    static public function getLastOpenIssue($lotteryId)
    {
        if(!is_numeric($lotteryId)){
            throw new exception2('彩种ID不正确!');
        }
        $sql = "SELECT `issue`,`code` FROM ssc_share.`issues_mini` WHERE `lottery_id` = {$lotteryId} AND `code` <> '' ORDER BY issue_id DESC LIMIT 1";

        return $GLOBALS['share_db']->getRow($sql);
    }

    /**
     * 六合彩手动生成奖期
     * @param <int> $lotteryId
     * @param <array> $startDateArr
     * @param <array> $endDateArr
     * @param <array> $issueArr
     * @return <int>
     */
    static public function diyGenIssue($lottery, $startDateArr, $endDateArr, $issueArr)
    {
        if(!is_array($issueArr) || !is_array($startDateArr) || !is_array($endDateArr) || !is_array($lottery)){
            throw new exception2('生成奖期参数不正确！');
        }

        $datas = array();
        $totalCounter = 0;
        foreach ($issueArr as $k => $issue) {

            $startTS = strtotime($startDateArr[$k]);
            $endTS = strtotime($endDateArr[$k]);
            if ($startTS > $endTS || $endTS - $startTS > 86400 * 365) {
                throw new exception2('日期范围不合法！');
            }

            // 判断是否需要起始期号
            if (!issues::checkIssueRule($issue, $lottery['issue_rule'])) {
                throw new exception2('请正确输入起始奖期！');
            }

            $setting = $lottery['settings'][0];

            //删除可能有的重复奖期先
            issues::deleteItemByDate($lottery['lottery_id'], $startTS);

            //休市则忽略
            if ($startTS >= strtotime($lottery['yearly_start_closed']) && $startTS <= strtotime($lottery['yearly_end_closed'])) {
                continue;
            }
            $belong_date = date('Y-m-d', $startTS);    // 属于哪天的奖期

            $curIssueStartTime = date('Y-m-d H:i:s', $startTS - $setting['end_sale']);
            $curIssueEndTime = date('Y-m-d H:i:s', $endTS - $setting['end_sale']);
            $curDropTime = date('Y-m-d H:i:s', $endTS - $setting['drop_time']);
            $curCodeTime = date('Y-m-d H:i:s', $endTS + $setting['code_time']);

            $datas[] = array(
                        'lottery_id' => $lottery['lottery_id'],
                        'belong_date' => $belong_date,
                        'issue' => $issue,
                        'start_sale_time' => $curIssueStartTime,
                        'end_sale_time' => $curIssueEndTime,
                        'cannel_deadline_time' => $curDropTime,
                        'earliest_input_time' => $curCodeTime,
                    );

            $totalCounter++;
        }

        //批量添加
        if (!$GLOBALS['db']->multipInsert('issues' , $datas)) {
            throw new exception2("奖期批量插入失败！");
        }

        return $totalCounter;
    }

    //录入号码或者是验证号码
    static public  function drawNumber($issueId, $code, $rank, $admin_id ,$original_number = '')
    {
        if (!$issueId || $code === '') {
            throw new exception2('参数无效');
        }

        if (!$issue = issues::getItem($issueId)) {
            throw new exception2('找不到奖期');
        }

        //彩种不存在
        if (!$lottery = lottery::getItem($issue['lottery_id'])) {
            throw new exception2('找不到彩种');
        }

        if ($issue["status_code"] == 2) {
            throw new exception2('号码状态为已验证，不能再录');
        }
        elseif ($issue["status_code"] == 3) {
            throw new exception2("杜绝不看奖期就录号的行为，这里拒绝录入，原因：即将录入的奖期{$issue['issue']}已设置为官方未开奖！");
        }

//        if (!$lastNoDrawIssue = issues::getLastNoDrawIssue($issue['lottery_id'])) {
//            throw new exception2('还没到开奖时间');
//        }

        if (date('Y-m-d H:i:s') < $issue['earliest_input_time']) {
            throw new exception2('不能提前录号');
        }

        // 这里写死判断规则
        switch ($lottery['lottery_type']) {
            case 1:// 数字型 5个连续数字
                if (!preg_match('`^\d{5}$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            case 2:// 乐透同区型, 5个数字用空格分隔，允许录号不加前导0，这里自动加上
                $tmpArray = explode(' ', $code);
                if (count($tmpArray) != 5) {
                    throw new exception2('乐透型必须是5个数字:'.$code);
                }
                $tmpArray2 = array();
                for ($i = 0; $i < count($tmpArray); $i++) {
                    $tmp = intval($tmpArray[$i]);
                    if ($tmp < 1 || $tmp > 11) {
                        throw new exception2('号码必须在1~11之间:'.$code);
                    }
                    $tmpArray2[] = str_pad($tmp, 2, '0', STR_PAD_LEFT);
                }
                $code = implode(' ', $tmpArray2);
                break;
            case 3:// 双色球
                if (!preg_match('`^(\d{2}\s){6}\d{2}$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            case 4:// 低频3D型 3个连续数字
                if (!preg_match('`^\d{3}$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            case 5:// 基诺型, 允许录号不加前导0，这里自动加上
                $tmpArray = explode(' ', $code);
                if (count($tmpArray) != 20) {
                    throw new exception2('基诺型必须是20个数字:'.$code); //操作失败:号码格式不正确.
                }
                $tmpArray2 = array();
                for ($i = 0; $i < count($tmpArray); $i++) {
                    $tmp = intval($tmpArray[$i]);
                    if ($tmp < 1 || $tmp > 80) {
                        throw new exception2('号码必须在1~80之间:'.$code); //操作失败:号码格式不正确.
                    }
                    $tmpArray2[] = str_pad($tmp, 2, '0', STR_PAD_LEFT);
                }
                $code = implode(' ', $tmpArray2);
                break;
            case 6:// 江苏快三型 3位数字 范围1~6
                if (!preg_match('`^[1-6]{3}$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            case 7:// 扑克型 2c 4d Ad
                if (!preg_match('`^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            case 8:// pk拾
                if (!preg_match('`^(\d{2}\s{1}){9}\d{2}$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            case 9:// 六合彩
                if (!preg_match('`^(\d{1,2}\s){6}\d{1,2}$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            case 10:
                if (!preg_match('`^\d{3}$`', $code)) {
                    throw new exception2('号码格式不正确:'.$code);
                }
                break;
            default:
                // 可以添加其他彩种的判断
                throw new exception2('对其他类型彩种的号码验证，暂不支持');
        }

        $rankConfigs = config::getConfigs(array('person_rank', 'least_rank'));
        $newRank = $rank + $issue['rank'];

        //开始事务
        if (FALSE == $GLOBALS['db']->startTransaction()) {
            throw new exception2('开始事务失败');
        }

        if ($issue["status_code"] == 1) {
            //验证成功，需要检测两个身份是否重合
            if ($issue["input_admin_id"] == $admin_id) {
                throw new exception2('一个人不能同时录入号码和审核号码');
            }
            if ($code == $issue["code"]) {
                $data = array(
                    'status_code' => 2,
                    'verify_time' => date("Y-m-d H:i:s"),
                    'verify_admin_id' => $admin_id,
                    'rank' => $newRank,
                );
                if (!issues::updateItem($issueId, $data)) {
                    $GLOBALS['db']->rollback();
                    throw new exception2('更新失败');
                }
                if(!issuesMini::updateItem($issueId, $data)){
                    $GLOBALS['db']->rollback();
                    throw new exception2('更新mini失败');
                }
                if ($data["status_code"] == 2) {
                    //将开奖结果记录到mc缓存
                    $cachePrefix = 'latest_opencode_';
                    $cacheKey = $lottery['lottery_id'];
                    $cacheValue = array('issue' => $issue['issue'], 'code' => $code);
                    $GLOBALS['mc']->set($cachePrefix, $cacheKey, $cacheValue, 60 * 60 * 24 * 2);
                }

            }
            else {
                // 号码不一致属严重错误，重置0
                $newRank = 0; // 重要
                $data = array(
                    'code' => '',
                    'input_time' => '0000-00-00 00:00:00',
                    'input_admin_id' => 0,
                    'status_code' => 0,
                    'rank' => 0,
                );
                if (!issues::updateItem($issueId, $data)) {
                    $GLOBALS['db']->rollback();
                    throw new exception2('更新失败');
                }

                //先commit重置为0再抛异常
                if (!$GLOBALS['db']->commit()) {
                    throw new exception2('提交事务失败');
                }
                throw new exception2('两次号码不正确,需要重新输入号码');
            }
        }
        else {
            //首次录入号码
            $data = array(
                'code' => $code,
                'original_code' => $original_number,
                'input_admin_id' => $admin_id,
                'input_time' => date("Y-m-d H:i:s"),
                'rank' => $newRank,
            );
            // 如果设置一个人录入的分值即达标，直接开奖
            if ($newRank >= $rankConfigs['least_rank']) {
                $data["status_code"] = 2;
            }
            else {
                $data["status_code"] = 1;
            }

            if (!issues::updateItem($issueId, $data)) {
                $GLOBALS['db']->rollback();
                throw new exception2('录入号码失败');
            }
            if(!issuesMini::updateItem($issueId, $data)){
                $GLOBALS['db']->rollback();
                throw new exception2('录入号码mini失败');
            }
            if ($data["status_code"] == 2) {
                //将开奖结果记录到mc缓存
                $cachePrefix = 'latest_opencode_';
                $cacheKey = $lottery['lottery_id'];
                $cacheValue = array('issue' => $issue['issue'], 'code' => $code);
                $GLOBALS['mc']->set($cachePrefix, $cacheKey, $cacheValue, 60 * 60 * 24 * 2);
            }

        }

        // 现在只看权重, 成功写开奖历史表issuehistory，不够返回权重值
        if ($newRank >= $rankConfigs['least_rank']) {
            $data = array(
                'issue_id' => $issue['issue_id'],
                'lottery_id' => $issue['lottery_id'],
                'belong_date' => $issue['belong_date'],
                'issue' => $issue['issue'],
                'code' => $code,
                'miss_info' => '',
                'hot_info' => '',
                'miss_k3' => '',
                'hot_k3' => '',
            );

            if (!issues::addIssueHistory($data)) {
                throw new exception2('添加至历史表失败');
            }
        }

        // 提交事务
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('提交失败');
        }

        if ($newRank < $rankConfigs['least_rank']) {
            return false;
        }

        return true;
    }

    /**
     * 检查奖期格式是否正确
     * @param <String> $issue
     * @param <String> $issuerule
     * @return <Boolean>
     */
    static public  function checkIssueRule($issue, $issuerule)
    {
        if (!preg_match('`^\w+[\w-]+$`', $issue, $match)) {
            return false;
        }

        $result = issues::analyze($issuerule);
        if (strlen($issue) != $result['length']) {

            return false;
        }

        $pattern = preg_replace(array('`^[yY][md]*`i', '`\[(n)(\d+)\]`'), array("\\d{{$result['ymd_length']}}", "\\d{{$result['n']}}"), $result['sample']);
        if (!preg_match("`^{$pattern}$`i", $issue)) {
            return false;
        }

        if ($result['ymd'] && $result['ymd_length']) {
            preg_match("`^\d{{$result['ymd_length']}}`i", $issue, $match);
            $date = date('Y-m-d', strtotime($match[0]));
            if ($date < '2010-01-01' || $date > '2038-01-19') {
                return false;
            }
        }

        return true;
    }

    /**
     * 私有方法 分析奖期规则
     * @param <string> $issuerule
     * @return <array>
     * @author
     */
    private static function analyze($issuerule)
    {
        $tmp = explode('|', $issuerule);
        $result['sample'] = $tmp[0];
        $result['ymd'] = '';
        $result['n'] = 0;
        preg_match_all('`\[(n)(\d+)\]`', $tmp[0], $matches);
        if ($matches[1]) {
            $result['n'] = $matches[2][0];
        }

        // must be ahead if exist date
        if (preg_match('`^[yY][md]*`i', $tmp[0], $match)) {
            $result['ymd'] = $match[0];
        }

        $result['ymd_length'] = strlen(date($result['ymd']));
        $result['length'] = $result['n'];
        if ($result['ymd']) {
            $result['length'] += $result['ymd_length'];
        }
        $result['length'] += strlen(preg_replace(array('`^[yY][md]*`i', '`\[(n)(\d+)\]`i'), '', $result['sample']));

        $tmp3 = explode(',', $tmp[1]);
        $result['y'] = $tmp3[0] ? true : false;
        $result['m'] = $tmp3[1] ? true : false;
        $result['d'] = $tmp3[2] ? true : false;

        return $result;
    }

    // 延迟奖期时间
    static public  function delayIssueTime($lotteryId, $start_issue, $end_issue, $delay)
    {
        if ($start_issue >= $end_issue) {
            throw new exception2("结束奖期不能不大于开始奖期");
        }
        // 如果不在开售期暂不判断
        if ($currentIssue = issues::getCurrentIssue($lotteryId)) {
            if ($start_issue <= $currentIssue['issue']) {
                throw new exception2('开始奖期必须在当前期以后');
            }
        }

        //登记延长记录
        $data = array(
            'lottery_id' => $lotteryId,
            'start_issue' => $start_issue,
            'end_issue' => $end_issue,
            'delay' => $delay, //秒
        );
        if (!issues::addIssueDelays($data)) {
            throw new exception2('添加失败');
        }

        //更新具体奖期
        $sql = "UPDATE issues SET start_sale_time=ADDDATE(start_sale_time, INTERVAL $delay SECOND), end_sale_time=ADDDATE(end_sale_time, INTERVAL $delay SECOND), " .
                " cannel_deadline_time=ADDDATE(cannel_deadline_time, INTERVAL $delay SECOND), earliest_input_time=ADDDATE(earliest_input_time, INTERVAL $delay SECOND)";
        $sql .= " WHERE issue >='$start_issue' AND issue <='$end_issue'";
        $GLOBALS['db']->query($sql, array(), 'u');

        return $GLOBALS['db']->affected_rows();
    }

    static public   function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('issues', $data);
    }

    static public   function updateItemByLottery($lottery_id, $issue, $data)
    {
        if ($lottery_id <= 0 || !$issue || !$data) {
            throw new exception2('参数无效');
        }

        //issue_mmc不用更新status_rebate
        if ($lottery_id == 15) {
            return true;
        }

        $where = array();
        $limit = 1;

        if ($lottery_id == 15) {
            //$sql = "UPDATE issue_mmc SET " . implode(',', $tmp) . " WHERE 1";
            $table = 'issue_mmc';
        } else {
            //$sql = "UPDATE issues SET " . implode(',', $tmp) . " WHERE lottery_id={$lottery_id}";
            $table = 'issues';
            $where = array('lottery_id' => $lottery_id);
        }

        if (is_array($issue)) {
            // $sql .= " AND issue IN('" . implode("','", $issue) ."')";
            // $sql .= " LIMIT " . count($issue);
            $where['issue IN'] = $issue;
            $limit = count($issue);
        }
        else {
            // $sql .= " AND issue = '{$issue}'";
            // $sql .= " LIMIT 1";
            $where['issue'] = $issue;
        }

        return $GLOBALS['db']->updateSM($table,$data,$where,$limit);
    }

    static public   function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('issues', $data , array('issue_id' => $id));
    }

    //删除日期之后的奖期 批量生成奖期时使用
    static public   function deleteItemByDate($lotteryId, $belong_date = 0, $start_sale_time = 0)
    {
        if ($lotteryId <= 0) {
            throw new exception2('彩种ID非法');
        }
        if (!$belong_date && !$start_sale_time) {
            throw new exception2('必须指定时间');
        }
        $sql = 'DELETE FROM issues WHERE lottery_id = ' . intval($lotteryId);
        if ($belong_date) {
            $sql .= ' AND belong_date >= "' . date('Y-m-d', $belong_date) . '"';
        }
        if ($start_sale_time) {
            $sql .= ' AND start_sale_time >= "' . date('Y-m-d H:i:s', $start_sale_time) . '"';
        }

        $GLOBALS['db']->query($sql , array(), 'd');
        return $GLOBALS['db']->affected_rows();
    }

    //删除奖期 只能删除未录入号码的奖期
     static public function deleteItem($issueId)
    {
        if ($issueId <= 0) {
            throw new exception2('操作失败：无效的奖期ID');
        }
        $sql = "DELETE FROM issues WHERE issue_id=$issueId AND `status_code` = 0";
        $GLOBALS['db']->query($sql, array(), 'd');
        return $GLOBALS['db']->affected_rows();
    }

    /**
     * 奖期历史模型
     */
    static public   function getIssueHistory($id)
    {
        //用$issue查的，必须也同时提供$lotteryId
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM ssc_share.`issue_history` WHERE issue_id = ' . intval($id);

        return $GLOBALS['share_db']->getRow($sql);
    }

    static public   function addIssueHistory($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        if (!$GLOBALS['db']->insert('issue_history', $data)) {
            return false;
        }

//        return issues::updateMissHot($data['lottery_id'], $data['issue_id']);
        return true;
    }

    static public   function deleteIssueHistory($id)
    {
        $sql = "DELETE FROM issue_history WHERE issue_id=" . intval($id) . " LIMIT 1";

        return $GLOBALS['db']->query($sql, array(),'d');
    }

    /**
     * 计算遗漏和冷热
     * @param <int> $lotteryId
     * @param <int> $issueId
     * @param <int> $openCode
     * @param <int> $hotIssueCount   用于统计的冷热数据期数
     * @return <boolean>
     */
    static public   function updateMissHot($lotteryId, $issueId)
    {
        if (!$lottery = lottery::getItem($lotteryId)) {
            throw new exception2('找不到彩种');
        }

        $sql = "SELECT issue_id,code FROM ssc_share.`issue_history` WHERE lottery_id='$lotteryId' AND issue_id <='$issueId' ORDER BY issue_id DESC LIMIT 0,50";

        if (!$histories = $GLOBALS['share_db']->getAll($sql, array(),'issue_id')) {
            return true;
        }

        if (!isset($histories[$issueId])) {
            throw new exception2("指定历史期不存在(issue_id={$issueId})");
        }

        $func = 'updateMissHot' . $lottery['property_id'];
        $missData = self::$func($histories, $issueId);

        //保存冷热数据
        $set = '';
        foreach ($missData as $field => $data) {
            $set .= $field . "='" . serialize($data) . "',";
        }

        $sql = "UPDATE issue_history SET " . substr($set, 0, -1) . " WHERE lottery_id=$lotteryId AND issue_id=$issueId LIMIT 1";
        return $GLOBALS['db']->query($sql, array(), 'u');
    }

    static public   function updateMissHot1($histories, $issueId)
    {
        $parts = str_split($histories[$issueId]['code']);
        $keys = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
        $tmp = array_fill_keys($keys, 0);
        $hotInfo = $missInfo = array_fill(0, count($parts), $tmp);
        $appeared = array();
        //以近50期为标本，计算遗漏和冷热
        $count = 0;
        foreach ($histories as $v) {
            $parts = str_split($v['code']);

            foreach ($parts as $wei => $prizeCode) {
                $appeared[$wei][$prizeCode] = $prizeCode;
                foreach ($missInfo[$wei] as $digital => $nums) {
                    if (!isset($appeared[$wei][$digital])) {
                        $missInfo[$wei][$digital] ++;
                    }
                    if ($count < self::$hotIssueCount && $digital == $prizeCode) {
                        $hotInfo[$wei][$digital] ++;
                    }
                }
            }
            $count++;
        }

        return array('miss_info' => $missInfo, 'hot_info' => $hotInfo);
    }

    static public   function updateMissHot2($histories, $issueId)
    {
        $parts = explode(' ', $histories[$issueId]['code']);
        $keys = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11');
        $tmp = array_fill_keys($keys, 0);
        $hotInfo = $missInfo = array_fill(0, count($parts), $tmp);

        $appeared = array();
        //以近50期为标本，计算遗漏和冷热
        $count = 0;
        foreach ($histories as $v) {
            $parts = explode(' ', $v['code']);

            foreach ($parts as $wei => $prizeCode) {
                $appeared[$wei][$prizeCode] = $prizeCode;
                foreach ($missInfo[$wei] as $digital => $nums) {
                    if (!isset($appeared[$wei][$digital])) {
                        $missInfo[$wei][$digital] ++;
                    }
                    if ($count < self::$hotIssueCount && $digital == $prizeCode) {
                        $hotInfo[$wei][$digital] ++;
                    }
                }
            }
            $count++;
        }

        return array('miss_info' => $missInfo, 'hot_info' => $hotInfo);
    }

    static public   function updateMissHot3($histories, $issueId)
    {
        $parts = str_split($histories[$issueId]['code']);
        $keys = array(1, 2, 3, 4, 5, 6);
        $mix_keys = array(0, 1, 2, 3);
        $sum_keys = array(3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18);
        $missOdd = $missEven = $missBig = $missSmall = $missMix = array_fill_keys($mix_keys, 0);
        $hotOdd = $hotEven = $hotBig = $hotSmall = array_fill_keys($mix_keys, 0);
        $missSum = $hotSum = array_fill_keys($sum_keys, 0);
        $miss_k3 = $hot_k3 = array();
        ksort($histories);

        $hotInfo = $missInfo = array_fill_keys($keys, 0);

        //以近50期为标本，计算遗漏和冷热
        $count = 0;
        foreach ($histories as $v) {
            $parts = str_split($v['code']);
            $oddnum = $evennum = $bignum = $smallnum = 0;
            $sumnum = array_sum($parts);

            foreach ($parts as $wei => $prizeCode) {
                if ($prizeCode % 2 != 0) {
                    $oddnum++;
                }
                else {
                    $evennum++;
                }
                if ($prizeCode <= 3) {
                    $smallnum++;
                }
                else {
                    $bignum++;
                }
            }

            //计算每期奇偶大小数遗漏
            foreach ($missMix as $k1 => $v1) {//每一期奇偶数大小数和值跟和结果项对比
                if ($oddnum != $k1) {
                    $missOdd[$k1] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotOdd[$k1] ++;
                    }
                    $missOdd[$k1] = 0;
                }
                if ($evennum != $k1) {
                    $missEven[$k1] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotEven[$k1] ++;
                    }
                    $missEven[$k1] = 0;
                }
                if ($bignum != $k1) {
                    $missBig[$k1] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotBig[$k1] ++;
                    }
                    $missBig[$k1] = 0;
                }
                if ($smallnum != $k1) {
                    $missSmall[$k1] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotSmall[$k1] ++;
                    }
                    $missSmall[$k1] = 0;
                }
            }
            //计算每期和值遗漏
            foreach ($missSum as $k2 => $v2) {
                if ($sumnum != $k2) {
                    $missSum[$k2] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotSum[$k2] ++;
                    }
                    $missSum[$k2] = 0;
                }
            }
            //开奖号的遗漏和冷热
            foreach ($missInfo as $k3 => $v3) {
                if (!in_array($k3, $parts)) {
                    $missInfo[$k3] ++; //k3开奖遗漏
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotInfo[$k3] ++; //k3开奖热数据
                    }
                    $missInfo[$k3] = 0;
                }
            }
            $count++;
        }

        $miss_k3['odd'] = $missOdd;
        $miss_k3['even'] = $missEven;
        $miss_k3['big'] = $missBig;
        $miss_k3['small'] = $missSmall;
        $miss_k3['sum'] = $missSum;
        $hot_k3['odd'] = $hotOdd;
        $hot_k3['even'] = $hotEven;
        $hot_k3['big'] = $hotBig;
        $hot_k3['small'] = $hotSmall;
        $hot_k3['sum'] = $hotSum;

        return array('miss_info' => $missInfo, 'hot_info' => $hotInfo, 'miss_k3' => $miss_k3, 'hot_k3' => $hot_k3);
    }

    static public   function updateMissHot4($histories, $issueId)
    {
        $parts = explode(' ', $histories[$issueId]['code']);
        $keys = array('A', '2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K');
        $missForms = $hotForms = array('散牌' => 0, '同花' => 0, '顺子' => 0, '同花顺' => 0, '豹子' => 0, '对子' => 0);
        $missColors = $hotColors = array('h' => 0, 'c' => 0, 's' => 0, 'd' => 0);
        ksort($histories);

        $tmp = array_fill_keys($keys, 0);
        $hotInfo = $missInfo = $tmp;

        //以近50期为标本，计算遗漏和冷热
        $count = 0;
        foreach ($histories as $v) {
            $parts = explode(' ', $v['code']);

            //计算花色遗漏冷热
            $pokerColors = array_unique(array($parts[0][1], $parts[1][1], $parts[2][1]));
            foreach ($missColors as $k1 => $v1) {
                if (!in_array($k1, $pokerColors)) {
                    $missColors[$k1] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotColors[$k1] ++;
                    }
                    $missColors[$k1] = 0;
                }
            }

            //计算形态遗漏冷热
            $form = methods::getPokerForm($v['code']);
            foreach ($missForms as $k2 => $v2) {
                if ($form != $k2) {
                    $missForms[$k2] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotForms[$k2] ++;
                    }
                    $missForms[$k2] = 0;
                }
            }

            //开奖号的遗漏和冷热
            $pokerFirstCodes = array($parts[0][0], $parts[1][0], $parts[2][0]);
            foreach ($missInfo as $k3 => $v3) {
                if (!in_array($k3, $pokerFirstCodes)) {
                    $missInfo[$k3] ++;
                }
                else {
                    if ($count < self::$hotIssueCount) {
                        $hotInfo[$k3] ++;
                    }
                    $missInfo[$k3] = 0;
                }
            }
            $count++;
        }

        $miss_poker['form'] = $missForms;
        $miss_poker['color'] = $missColors;
        $hot_poker['form'] = $hotColors;
        $hot_poker['color'] = $hotForms;

        return array('miss_info' => $missInfo, 'hot_info' => $hotInfo, 'miss_poker' => $miss_poker, 'hot_poker' => $hot_poker);
    }

    static public   function updateMissHot5($histories, $issueId)
    {
        return self::updateMissHot1($histories, $issueId);
    }

    static public   function updateMissHot6($histories, $issueId)
    {
        $parts = explode(' ', $histories[$issueId]['code']);
        $keys = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10');
        $tmp = array_fill_keys($keys, 0);
        $hotInfo = $missInfo = array_fill(0, count($parts), $tmp);

        $appeared = array();
        //以近50期为标本，计算遗漏和冷热
        $count = 0;
        foreach ($histories as $v) {
            $parts = explode(' ', $v['code']);

            foreach ($parts as $wei => $prizeCode) {
                $appeared[$wei][$prizeCode] = $prizeCode;
                foreach ($missInfo[$wei] as $digital => $nums) {
                    if (!isset($appeared[$wei][$digital])) {
                        $missInfo[$wei][$digital] ++;
                    }
                    if ($count < self::$hotIssueCount && $digital == $prizeCode) {
                        $hotInfo[$wei][$digital] ++;
                    }
                }
            }
            $count++;
        }

        return array('miss_info' => $missInfo, 'hot_info' => $hotInfo);
    }

    static public   function updateMissHot7($histories, $issueId)
    {
        $parts = explode(' ', $histories[$issueId]['code']);
        $keys = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49);
        $tmp = array_fill_keys($keys, 0);
        $hotInfo = $missInfo = array_fill(0, count($parts), $tmp);

        $appeared = array();
        //以近50期为标本，计算遗漏和冷热
        $count = 0;
        foreach ($histories as $v) {
            $parts = explode(' ', $v['code']);

            foreach ($parts as $wei => $prizeCode) {
                $appeared[$wei][$prizeCode] = $prizeCode;
                foreach ($missInfo[$wei] as $digital => $nums) {
                    if (!isset($appeared[$wei][$digital])) {
                        $missInfo[$wei][$digital] ++;
                    }
                    if ($count < self::$hotIssueCount && $digital == $prizeCode) {
                        $hotInfo[$wei][$digital] ++;
                    }
                }
            }
            $count++;
        }

        return array('miss_info' => $missInfo, 'hot_info' => $hotInfo);
    }

    static public   function updateMissHot8($histories, $issueId)
    {
        $parts = explode(' ', $histories[$issueId]['code']);
        $keys = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33);
        $tmp = array_fill_keys($keys, 0);
        $hotInfo = $missInfo = array_fill(0, count($parts), $tmp);

        $appeared = array();
        //以近50期为标本，计算遗漏和冷热
        $count = 0;
        foreach ($histories as $v) {
            $parts = explode(' ', $v['code']);

            foreach ($parts as $wei => $prizeCode) {
                $appeared[$wei][$prizeCode] = $prizeCode;
                foreach ($missInfo[$wei] as $digital => $nums) {
                    if (!isset($appeared[$wei][$digital])) {
                        $missInfo[$wei][$digital] ++;
                    }
                    if ($count < self::$hotIssueCount && $digital == $prizeCode) {
                        $hotInfo[$wei][$digital] ++;
                    }
                }
            }
            $count++;
        }

        return array('miss_info' => $missInfo, 'hot_info' => $hotInfo);
    }

    static public   function updateMissHot9($histories, $issueId)
    {
        return self::updateMissHot1($histories, $issueId);
    }

    /**
     * 取得 history 数据
     */
    static public   function getMissHot($lotteryId, $issueId = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        if (!$lottery = lottery::getItem($lotteryId)) {
            throw new exception2('找不到彩种');
        }

        $sql = "SELECT * FROM ssc_share.`issue_history` WHERE lottery_id='$lotteryId' ";

        if ($issueId != '') {
            $sql.= "AND issue_id <='$issueId' ";
        }
        $sql .= " ORDER BY issue_id DESC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        if (!$histories = $GLOBALS['share_db']->getAll($sql, array(),'issue_id')) {
            return false;
        }
        return $histories;
    }

    /**
     * 奖期顺延表模型
     */
    static public function getIssueDelays($lotteryId = 0)
    {
        $sql = "SELECT * FROM ssc_share.`issue_delays` WHERE 1";
        if ($lotteryId > 0) {
            $sql .= " AND lottery_id = " . intval($lotteryId);
        }

        $sql .= " ORDER BY id_id DESC";

        return $GLOBALS['share_db']->getAll($sql);
    }

    static public   function addIssueDelays($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('issue_delays', $data);
    }

    static public   function getLatestCode($lottery_id)
    {
        if (!is_numeric($lottery_id)) {
            throw new exception2('参数无效');
        }
        $cachePrefix = 'latest_opencode_';
        $cacheKey = $lottery_id;

        return $GLOBALS['mc']->get($cachePrefix, $cacheKey);
    }


    /**
     * 秒秒彩奖期表模型
     */
    static public   function addIssueMMC($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('issue_mmc', $data);
    }

    //批量插入提高速度
    static public   function addIssueMMCs($datas)
    {
        if (!is_array($datas)) {
            throw new exception2('参数无效', 3409);
        }
        if (count($datas) == 0) {
            return true;
        }
        $tmp = array();
        foreach ($datas as $v) {
            $tmp[] = "('{$v['user_id']}','{$v['issue']}','{$v['code']}','{$v['belong_date']}')";
        }
        $sql = "INSERT INTO issue_mmc (user_id,issue,code,belong_date) VALUES " . implode(',', $tmp);

        return $GLOBALS['db']->query($sql, array() , 'i');
    }
}

?>