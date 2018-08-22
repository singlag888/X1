<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

class issuesMini extends baseModel
{

    static public   function getItem($id = 0, $issue = '', $lotteryId = 0)
    {
        //用$issue查的，必须也同时提供$lotteryId
        if (!is_numeric($id) || (!$id && !$issue) || ($issue && !$lotteryId)) {
            throw new exception2('参数无效');
        }

        if (is_numeric($id)) {
            $sql = 'SELECT * FROM ssc_share.`issues_mini` WHERE issue_id = ' . intval($id);
        } else {
            if (!preg_match('`^\d[\d-]{4,}\d$`Ui', $issue)) {
                throw new exception2('参数无效');
            }
            $sql = 'SELECT * FROM ssc_share.`issues_mini` WHERE issue = \'' . $issue . '\'';
        }
        if ($lotteryId > 0) {
            $sql .= " AND lottery_id = " . intval($lotteryId);
        }

        return $GLOBALS['share_db']->getRow($sql);
    }

    static public function getItems($lotteryId = 0, $belong_date = '', $start_sale_time1 = 0, $start_sale_time2 = 0, $end_sale_time1 = 0, $end_sale_time2 = 0, $status_code = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE, $fields = '*')
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

    public function getCurrentIssue($lotteryId)
    {
        $time =& $_SERVER['REQUEST_TIME'];

        $belongDate = date('Y-m-d', $time);
        // 新疆时时彩 00-02时间跨天的期号是属于前一天的。
        if ($lotteryId == 4) {
            $hour = date('H', $time);
            if ($hour == '00' || $hour == '01') {
                $belongDate = date('Y-m-d', $time - 86400);
            }
        }
        $dateTime = date('Y-m-d H:i:s', $time);
        $this->where([
                'lottery_id' => $lotteryId,
                'start_sale_time' => ['<=', $dateTime],
                'end_sale_time' => ['>=', $dateTime],
            ]);

        // 如果是六合彩不用加belongdate查询字段
        if (!in_array($lotteryId, [21, 22, 26])) $this->where(['belong_date' => $belongDate]);

        return $this->db('share_db')->find();
    }

    static public   function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['share_db']->updateSM('issues_mini', $data , array('issue_id' => $id));
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
        $db = 'share_db';
        if ($lottery_id == 15) {
            //$sql = "UPDATE issue_mmc SET " . implode(',', $tmp) . " WHERE 1";
            $table = 'issue_mmc';
            $db = 'db';
        } else {
            //$sql = "UPDATE issues SET " . implode(',', $tmp) . " WHERE lottery_id={$lottery_id}";
            $table = 'ssc_share.`issues_mini`';
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

        return $GLOBALS[$db]->updateSM($table,$data,$where,$limit);
    }
}

