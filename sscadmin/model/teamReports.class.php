<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class teamReports
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM team_report WHERE tr_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM team_report  WHERE tr_id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

/******************************* snow 修改条件  start,获取报表**************************************************************/

    //取得报表 按UID , 日期
    static public function snow_getItems($user_id = -1, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT t.* FROM team_report AS t  INNER  JOIN users AS u  ON t.user_id = u.user_id WHERE 1 AND u.is_test = 0';
        if ($user_id != -1) {
            $sql .= " AND t.user_id = " . intval($user_id);
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND t.belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND t.belong_date <= '$endDate'";
        }
        $sql .= " ORDER BY t.user_id ASC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     * 获取报表总条数
     * @param int $user_id
     * @param string $startDate
     * @param string $endDate
     * @return mixed
     */
    static public function snow_getItemsNumber($user_id = -1, $startDate = '', $endDate = '')
    {
        $sql = "SELECT COUNT(*) AS count FROM team_report AS t  INNER  JOIN users AS u  ON t.user_id = u.user_id WHERE 1 AND u.is_test = 0";
        if ($user_id != -1) {
            $sql .= " AND t.user_id = " . intval($user_id);
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND t.belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND t.belong_date <= '$endDate'";
        }
        $sql .= " LIMIT 0,1 ";
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    /******************************* snow 修改条件 end,获取报表**************************************************************/

    //取得报表 按UID , 日期
    static public function getItems($user_id = -1, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM team_report  WHERE 1';
        if ($user_id != -1) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND belong_date <= '$endDate'";
        }
        $sql .= " ORDER BY user_id ASC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql, [], 'user_id');

        return $result;
    }

    static public function getItemsNumber($user_id = -1, $startDate = '', $endDate = '')
    {
        $sql = "SELECT COUNT(*) AS count FROM team_report  WHERE 1";
        if ($user_id != -1) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND belong_date <= '$endDate'";
        }
        $sql .= " LIMIT 0,1 ";
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    //取得报表 按UID , 日期
    static public function getChildren($user_id, $startDate = '', $endDate = '', $includeSelf = false)
    {
        $sql = 'SELECT u.parent_id,u.username,t.* FROM users AS u  LEFT JOIN team_report AS t on u.user_id=t.user_id WHERE 1';
        if ($includeSelf) {
            $sql .= " AND (u.user_id = " . intval($user_id) . " or u.parent_id = " . intval($user_id) . ")";
        }
        else {
            $sql .= " AND u.parent_id = " . intval($user_id);
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND t.belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND t.belong_date <= '$endDate'";
        }
//dump($sql);
        $result = $GLOBALS['db']->getAll($sql);
        $output = array();
        if ($result) {
            foreach ($result as $v) {
                if (!isset($output[$v['user_id']])) {
                    $output[$v['user_id']] = array(
                        'username' => $v['username'],
                        'belong_date' => $startDate . " - " . $endDate,
                        'last_team_balance' => 0,
                        'team_balance' => $v['team_balance'],
                        'last_pt_amount' => $v['pt_amount'],
                        'pt_amount' => 0,
                        'pt_buy_amount' => 0,
                        'pt_game_win' => 0,
                        'deposit_amount' => 0,
                        'withdraw_amount' => 0,
                        'buy_amount' => 0,
                        'real_win' => 0,
                        'prize_amount' => 0,
                        'rebate_amount' => 0,
                        'game_win' => 0,
                        'promos' => 0,
                        'diff' => 0,
                        'play_user_num' => 0,
                        'prize_user_num' => 0,
                        'reg_num' => 0,
                        'first_deposit_num' => 0,
                        'first_deposit_amount' => 0,
                        'lastDate' => strtotime($v['belong_date']),
                        'real_win_sum' => 0,
                    );
                }
                if ($v['belong_date'] == $startDate) {
                    $output[$v['user_id']]['last_team_balance'] = $v['last_team_balance'];
                    $output[$v['user_id']]['last_pt_amount'] = $v['last_pt_amount'];
                    $output[$v['user_id']]['last_team_balance'] +=$output[$v['user_id']]['last_pt_amount'];
                }
                if (strtotime($v['belong_date']) >= $output[$v['user_id']]['lastDate']) {
                    $output[$v['user_id']]['lastDate'] = strtotime($v['belong_date']);
                    $output[$v['user_id']]['team_balance'] = $v['team_balance'];
                    $output[$v['user_id']]['pt_amount'] = $v['pt_amount'];
                    $output[$v['user_id']]['team_balance'] +=$output[$v['user_id']]['pt_amount'];
                }
                $output[$v['user_id']]['deposit_amount']+=$v['deposit_amount'];
                $output[$v['user_id']]['withdraw_amount']+=$v['withdraw_amount'];
                $output[$v['user_id']]['buy_amount']+=$v['buy_amount'] + $v['pt_buy_amount'];
                $output[$v['user_id']]['real_win_sum']+=$v['real_win'];
                $output[$v['user_id']]['pt_game_win']+=$v['pt_game_win'];
                $output[$v['user_id']]['pt_buy_amount']+=$v['pt_buy_amount'];
            }
            foreach ($output as $key => $value) {
                $output[$key]['real_win'] = $output[$key]['real_win_sum'];
                // $output[$key]['real_win'] = $output[$key]['pt_amount'] - $output[$key]['last_pt_amount'] + $output[$key]['team_balance'] - $output[$key]['last_team_balance'] + $output[$key]['withdraw_amount'] - $output[$key]['deposit_amount'];
            }
        }

        return $output;
    }

    //取得报表 按UID , 日期
    static public function getUserStats($user_id = -1, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT user_id, belong_date, SUM(last_team_balance) AS last_team_balance, SUM(team_balance) AS team_balance, SUM(deposit_amount) AS deposit_amount, SUM(withdraw_amount) AS withdraw_amount, ' .
                'SUM(real_win) AS real_win, SUM(buy_amount) AS buy_amount, SUM(prize_amount) AS prize_amount, SUM(rebate_amount) AS rebate_amount, ' .
                'SUM(game_win) AS game_win, SUM(diff) AS diff, SUM(reg_num) AS reg_num, SUM(promos) AS promos,SUM(first_deposit_num) AS first_deposit_num,SUM(first_deposit_amount) AS first_deposit_amount' .
                ' FROM team_report  WHERE 1';
        if ($user_id != -1) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND belong_date <= '$endDate'";
        }
        $sql .= " GROUP BY user_id ORDER BY user_id ASC";

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }
    /*********************************** snow start 修改技术要求逻辑 与总账报表一至**********************************************/
    //取得报表 按UID , 日期
    /**
     * author snow 修改成options 传参数
     * @param $options
     * @return mixed
     */
    static public function snow_getUserStats($options = [])
    {
        $default_options = [
            'user_id'       => -1,
            'startDate'     => '',
            'endDate'       => '',
            'start'         => 0,
            'pageSize'      => DEFAULT_PER_PAGE,
        ];

        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        //>>添加投注日返水
        //>>author snow 添加 优惠彩金
        $sql = 'SELECT t.user_id, t.belong_date, SUM(t.last_team_balance) AS last_team_balance, SUM(t.team_balance) AS team_balance,SUM(t.promo_amount) AS promo_amount, SUM(t.deposit_amount) AS deposit_amount, SUM(t.withdraw_amount) AS withdraw_amount, ' .
            'SUM(t.real_win) AS real_win, SUM(t.buy_amount) AS buy_amount, SUM(t.prize_amount) AS prize_amount, SUM(t.rebate_amount) AS rebate_amount, ' .
            'SUM(t.game_win) AS game_win, SUM(t.diff) AS diff, SUM(t.reg_num) AS reg_num, SUM(t.promos) AS promos, SUM(back_water) as back_water, SUM(t.first_deposit_num) AS first_deposit_num,SUM(t.first_deposit_amount) AS first_deposit_amount' .
            ' FROM team_report AS t INNER JOIN users AS u ON t.user_id = u.user_id   WHERE 1 AND u.is_test = 0';
        if ($default_options['user_id'] != -1) {
            $sql .= " AND t.user_id = " . intval($default_options['user_id']);
        }
        if ($default_options['startDate'] !== '') {    //提案发起时间
            $sql .= " AND t.belong_date >= '{$default_options['startDate']}'";
        }
        if ($default_options['endDate'] !== '') {
            $sql .= " AND t.belong_date <= '{$default_options['endDate']}'";
        }
        $sql .= " GROUP BY t.user_id ORDER BY t.user_id ASC LIMIT {$default_options['start']}, {$default_options['pageSize']}";

        $result = $GLOBALS['db']->getAll($sql);
        return $result;
    }

    /**
     * author snow 修改成options 传参数  获取查询总条数
     * @param $options
     * @return mixed
     */
    public static function snow_getUserStatsNumber($options = [])
    {
        $default_options = [
            'user_id'       => -1,
            'startDate'     => '',
            'endDate'       => '',
        ];

        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        //>>添加投注日返水
        $sql = 'SELECT COUNT(DISTINCT(t.user_id) ) AS count_number FROM team_report AS t INNER JOIN users AS u ON t.user_id = u.user_id   WHERE 1  AND u.is_test = 0';
        if ($default_options['user_id'] != -1) {
            $sql .= " AND t.user_id = " . intval($default_options['user_id']);
        }
        if ($default_options['startDate'] !== '') {    //提案发起时间
            $sql .= " AND t.belong_date >= '{$default_options['startDate']}'";
        }
        if ($default_options['endDate'] !== '') {
            $sql .= " AND t.belong_date <= '{$default_options['endDate']}'";
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count_number'];
    }
    /*********************************** snow end 修改技术要求逻辑 与总账报表一至**********************************************/
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('team_report', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('team_report',$data,array('tr_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM team_report WHERE tr_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * 按日期删除数据
     * @param $date
     */
    static public function deleteItemByBelongDate($date)
    {
        $sql = "DELETE FROM team_report WHERE belong_date='{$date}'";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }
}

