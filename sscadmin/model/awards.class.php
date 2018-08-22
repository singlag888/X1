<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

// 帐变模型
class awards
{

    static public function getItem($id)
    {
        $sql = 'SELECT * FROM awards WHERE aw_id = ' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($user_id = 0, $lottery_id = 0, $package_id = 0, $issue = '', $belong_date = '', $start_time = '', $end_time = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM awards a LEFT JOIN users b ON a.user_id=b.user_id WHERE b.is_test = 0';
        if ($user_id != 0) {
            $sql .= " AND a.user_id = " . intval($user_id);
        }
        if ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($package_id != 0) {
            $sql .= " AND a.package_id = " . intval($package_id);
        }
        if ($issue != '') {
            $sql .= " AND a.issue = '{$issue}'";
        }
        if ($belong_date != '') {
            $sql .= " AND a.belong_date = '{$belong_date}'";
        }
        if ($start_time != '') {
            $sql .= " AND a.award_ts >= '{$start_time}'";
        }
        if ($end_time != '') {
            $sql .= " AND a.award_ts <= '{$end_time}'";
        }
        $sql.=' ORDER BY aw_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }
        return $GLOBALS['db']->insert('awards', $data);
    }


    /**
     * 获取奖期嘉奖总额
     * @param $lottery
     * @param $issue
     * @return mixed
     */
    public static function getIssueAwardPrize($lottery, $issue)
    {
        $sql = 'SELECT COALESCE(sum(award_prize), 0) as total_award FROM awards WHERE lottery_id = ' . $lottery. ' and issue = \''. $issue . '\'';
        $res = $GLOBALS['db']->getRow($sql);
        return $res['total_award'];
    }

    /**
     * 获取用户嘉奖总额
     * @param $userId
     * @param int $lotteryId
     * @param int $check_prize_status
     * @param int $include_childs
     * @return array
     */
    public static function getUserAwardPrize($userId, $lotteryId = 0, $check_prize_status = -1, $include_childs = 0, $start_time = '', $end_time = '')
    {
        $award_prize = 0;
        if(in_array($check_prize_status, [-1, 1]))
        {
            $sql = 'SELECT sum(a.award_prize) as award_prize FROM awards a LEFT JOIN packages p ON a.package_id=p.package_id WHERE ';
            if($include_childs){
                // $teamUsers = users::getUserTree($userId, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $userId,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }else{
                $sql .= 'a.user_id='.$userId;
            }
            !$lotteryId or $sql .= ' and a.lottery_id='.$lotteryId;
            !$start_time or $sql .= " AND p.create_time >= '$start_time'";
            !$end_time or $sql .= " AND p.create_time <= '$end_time'";

            if($result = $GLOBALS['db']->getRow($sql)) $award_prize = $result['award_prize'];
        }
        return $award_prize;
    }
}

?>