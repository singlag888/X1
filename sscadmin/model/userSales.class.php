<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userSales
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM user_sales WHERE us_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM user_sales  WHERE us_id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //取得报表 按UID , 日期
    static public function getItems($userId = -1, $belong_date = '', $start = -1, $pageSize = DEFAULT_PER_PAGE)
    {
        $paramArr = array();
        $sql = 'SELECT * FROM user_sales  WHERE 1';
        if ($userId !== -1) {    // 具体用户
            $sql .= " AND user_id = :user_id";
            $paramArr[':user_id'] = $userId;
        }
        if ($belong_date !== '') {    // 起始时间
            $sql .= " AND belong_date = '{$belong_date}'";
        }
        $sql .= " ORDER BY belong_date ASC";
        if ($start > -1) {
            $sql .= " LIMIT :start, :pageSize";
            $paramArr[':start'] = $start;
            $paramArr[':pageSize'] = $pageSize;
        }

        $result = $GLOBALS['db']->getAll($sql , $paramArr);

        return $result;
    }

    /**
     * 获得item行数
     * @param type $startDate
     * @param type $endDate
     * @param type $lottery_id
     * @param type $buyType 0:获得所有行, 1:获得已经投注的用户个数
     * @param type $prizeType 0:获得所有行, 1:获得已经中奖的用户个数
     * @return int
     */
    static public function getItemsNumber($belong_date = '', $lottery_id = 0, $buyType = 0, $prizeType = 0)
    {
        $sql = "SELECT COUNT(*) AS count FROM user_sales WHERE 1";
        if ($belong_date !== '') {    //提案发起时间
            $sql .= " AND belong_date = '$belong_date'";
        }

        // 计算投注的人数，即游戏人数
        if ($buyType == 1) {
            if ($lottery_id > 0) {
                $sql .= ' AND buy_amount_' . $lottery_id . ' > 0';
            }
            else {
                $sql .= ' AND buy_amount > 0';
            }
        }
        // 计算中奖的人数
        if ($prizeType == 1) {
            $sql .= ' AND prize_amount > 0';
        }
        $sql .= ' LIMIT 0, 1';

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    /**
     * 
     * @param type $startDate
     * @param type $endDate
     * @param type $lottery_id
     * @param type $buyType 0:获得所有行, 1:获得已经投注的用户个数
     * @param type $prizeType 0:获得所有行, 1:获得已经中奖的用户个数
     * @return type
     */
    static public function getTeamItemsNumber($startDate = '', $endDate = '', $buyType = 0, $prizeType = 0)
    {
        $sql = "SELECT top_id, COUNT(*) AS count FROM user_sales WHERE 1";
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND belong_date <= '$endDate'";
        }
        // 计算投注的人数，即游戏人数
        if ($buyType == 1) {
            $sql .= ' AND buy_amount > 0';
        }
        // 计算中奖的人数
        if ($prizeType == 1) {
            $sql .= ' AND prize_amount > 0';
        }
        $sql .= ' GROUP BY top_id';

        $result = $GLOBALS['db']->getAll($sql, array(),'top_id');

        return $result;
    }

    /**
     * 获得投注总额和奖金总额。
     * @param type $startDate
     * @param type $endDate
     * @param type $user_id
     * @return type
     */
    static public function getDayTotalSales($belong_date = '', $user_id = 0)
    {
        $sql = 'SELECT us.belong_date, SUM(us.buy_amount) AS total_buy, SUM(us.win_amount) AS total_win, SUM(us.buy_amount_1) AS total_buy_1, SUM(us.win_amount_1) AS total_win_1, SUM(us.buy_amount_2) AS total_buy_2, SUM(us.win_amount_2) AS total_win_2, SUM(us.buy_amount_3) AS total_buy_3, SUM(us.win_amount_3) AS total_win_3, SUM(us.buy_amount_4) AS total_buy_4, SUM(us.win_amount_4) AS total_win_4,
                SUM(us.buy_amount_5) AS total_buy_5, SUM(us.win_amount_5) AS total_win_5, SUM(us.buy_amount_6) AS total_buy_6, SUM(us.win_amount_6) AS total_win_6, SUM(us.buy_amount_7) AS total_buy_7, SUM(us.win_amount_7) AS total_win_7, SUM(us.buy_amount_8) AS total_buy_8, SUM(us.win_amount_8) AS total_win_8, SUM(us.buy_amount_9) AS total_buy_9, SUM(us.win_amount_9) AS total_win_9,
                SUM(us.buy_amount_10) AS total_buy_10, SUM(us.win_amount_10) AS total_win_10, SUM(us.buy_amount_11) AS total_buy_11, SUM(us.win_amount_11) AS total_win_11, SUM(us.buy_amount_12) AS total_buy_12, SUM(us.win_amount_12) AS total_win_12, SUM(us.buy_amount_13) AS total_buy_13, SUM(us.win_amount_13) AS total_win_13, SUM(us.buy_amount_14) AS total_buy_14, SUM(us.win_amount_14) AS total_win_14,
                SUM(us.buy_amount_15) AS total_buy_15, SUM(us.win_amount_15) AS total_win_15, SUM(us.buy_amount_16) AS total_buy_16, SUM(us.win_amount_16) AS total_win_16, SUM(us.buy_amount_17) AS total_buy_17, SUM(us.win_amount_17) AS total_win_17, SUM(us.buy_amount_18) AS total_buy_18, SUM(us.win_amount_18) AS total_win_18, SUM(us.buy_amount_19) AS total_buy_19, SUM(us.win_amount_19) AS total_win_19, 
                SUM(us.buy_amount_20) AS total_buy_20, SUM(us.win_amount_20) AS total_win_20,SUM(us.buy_amount_21) AS total_buy_21, SUM(us.win_amount_21) AS total_win_21,SUM(us.buy_amount_22) AS total_buy_22, SUM(us.win_amount_22) AS total_win_22,SUM(us.buy_amount_23) AS total_buy_23, SUM(us.win_amount_23) AS total_win_23,SUM(us.buy_amount_24) AS total_buy_24, SUM(us.win_amount_24) AS total_win_24,SUM(us.buy_amount_25) AS total_buy_25, SUM(us.win_amount_25) AS total_win_25,SUM(us.buy_amount_26) AS total_buy_26, SUM(us.win_amount_26) AS total_win_26
                FROM user_sales us LEFT JOIN users u ON us.user_id=u.user_id WHERE u.status = 8';
        if ($belong_date != '') {
            $sql .= " AND us.belong_date = '$belong_date'";
        }

        if ($user_id > 0) {
            $sql .= ' AND us.user_id = ' . $user_id;
        }

        $sql .= ' GROUP BY us.belong_date';
        $tmp = $GLOBALS['db']->getRow($sql);

        return $tmp;
    }


    /********************************* snow 复制代码用来排除dcsite 总代数据 start***********************************************************/

    /**
     * author snow
     * 获得当天的销量、奖金和返点总额
     * 排除dcsite 下的数据
     * @param string $belong_date
     * @param int $user_id
     * @return mixed
     */
    static public function getDayTotalSalesExclude($belong_date = '', $user_id = 0)
    {
        $sql =<<<SQL
SELECT us.belong_date, SUM(us.buy_amount) AS total_buy, SUM(us.win_amount) AS total_win, SUM(us.buy_amount_1) AS total_buy_1, SUM(us.win_amount_1) AS total_win_1, SUM(us.buy_amount_2) AS total_buy_2, SUM(us.win_amount_2) AS total_win_2, SUM(us.buy_amount_3) AS total_buy_3, SUM(us.win_amount_3) AS total_win_3, SUM(us.buy_amount_4) AS total_buy_4, SUM(us.win_amount_4) AS total_win_4,SUM(us.buy_amount_5) AS total_buy_5, SUM(us.win_amount_5) AS total_win_5, SUM(us.buy_amount_6) AS total_buy_6, SUM(us.win_amount_6) AS total_win_6, SUM(us.buy_amount_7) AS total_buy_7, SUM(us.win_amount_7) AS total_win_7, SUM(us.buy_amount_8) AS total_buy_8, SUM(us.win_amount_8) AS total_win_8, SUM(us.buy_amount_9) AS total_buy_9, SUM(us.win_amount_9) AS total_win_9,SUM(us.buy_amount_10) AS total_buy_10, SUM(us.win_amount_10) AS total_win_10, SUM(us.buy_amount_11) AS total_buy_11, SUM(us.win_amount_11) AS total_win_11, SUM(us.buy_amount_12) AS total_buy_12, SUM(us.win_amount_12) AS total_win_12, SUM(us.buy_amount_13) AS total_buy_13, SUM(us.win_amount_13) AS total_win_13, SUM(us.buy_amount_14) AS total_buy_14, SUM(us.win_amount_14) AS total_win_14,SUM(us.buy_amount_15) AS total_buy_15, SUM(us.win_amount_15) AS total_win_15, SUM(us.buy_amount_16) AS total_buy_16, SUM(us.win_amount_16) AS total_win_16, SUM(us.buy_amount_17) AS total_buy_17, SUM(us.win_amount_17) AS total_win_17, SUM(us.buy_amount_18) AS total_buy_18, SUM(us.win_amount_18) AS total_win_18, SUM(us.buy_amount_19) AS total_buy_19, SUM(us.win_amount_19) AS total_win_19, SUM(us.buy_amount_20) AS total_buy_20, SUM(us.win_amount_20) AS total_win_20,SUM(us.buy_amount_21) AS total_buy_21, SUM(us.win_amount_21) AS total_win_21,SUM(us.buy_amount_22) AS total_buy_22, SUM(us.win_amount_22) AS total_win_22,SUM(us.buy_amount_23) AS total_buy_23, SUM(us.win_amount_23) AS total_win_23,SUM(us.buy_amount_24) AS total_buy_24, SUM(us.win_amount_24) AS total_win_24,SUM(us.buy_amount_25) AS total_buy_25, SUM(us.win_amount_25) AS total_win_25,SUM(us.buy_amount_26) AS total_buy_26, SUM(us.win_amount_26) AS total_win_26 
FROM user_sales AS us LEFT JOIN users AS u ON us.user_id=u.user_id WHERE u.status = 8
SQL;


        //>>添加排除总代dcsite 相关数据
        $sql .= ' AND us.user_id NOT IN ' . users::getUsersSqlByUserName('dcsite');
        if ($belong_date != '') {
            $sql .= " AND us.belong_date = '$belong_date'";
        }

        if ($user_id > 0) {
            $sql .= ' AND us.user_id = ' . $user_id;
        }

        $sql .= ' GROUP BY us.belong_date';
        $tmp = $GLOBALS['db']->getRow($sql);

        return $tmp;
    }

    static public function getTeamTotalSalesExclude($startDate = '', $endDate = '')
    {
        // 判断是否存在总代，不存在返回空
        // $targetUsers = users::getUserTree(0, true, 0, 8);
        $targetUsers = users::getUserTreeField([
            'field' => ['user_id', 'username', 'level', 'is_test', 'parent_id'],
            'parent_id' => 0,
            'status' => 8,
        ]);
        if (!$targetUsers) {
            return [];
        }
        //先初始化
        $result = [];
        foreach ($targetUsers as $k => $v) {
            //>> snow 排除掉dcsite 总代
            if ($v['username'] != 'dcsite')
            {
                $result[$v['user_id']]['user_id'] = $v['user_id'];
                $result[$v['user_id']]['username'] = $v['username'];
                $result[$v['user_id']]['level'] = $v['level'];
                $result[$v['user_id']]['is_test'] = $v['is_test'];
                $result[$v['user_id']]['parent_id'] = $v['parent_id'];
                $result[$v['user_id']]['total_amount'] = 0;
                $result[$v['user_id']]['total_prize'] = 0;
                $result[$v['user_id']]['total_rebate'] = 0;
                $result[$v['user_id']]['total_win'] = 0;
            }
        }
        //>>snow 释放内存
        unset($targetUsers);
        // 从user_sales中获得所有总代的总投注、总奖金、总返点、总盈亏
        $sql1 = 'SELECT user_id, top_id, SUM(buy_amount) AS total_amount, SUM(prize_amount) AS total_prize, SUM(rebate_amount) AS total_rebate, SUM(win_amount) AS total_win FROM user_sales WHERE 1';

        //>>排除dcsite 总代
        $sql1 .= ' AND user_id NOT IN ' . users::getUsersSqlByUserName();
        if ($startDate != '') {
            $sql1 .= " AND belong_date >= '$startDate'";
        }
        if ($endDate != '') {
            $sql1 .= " AND belong_date <= '$endDate'";
        }
        $sql1 .= ' GROUP BY top_id ORDER BY top_id DESC';
        $tmp = $GLOBALS['db']->getAll($sql1, array(),'top_id');

        foreach ($tmp AS $k => $v) {
            if (!isset($result[$k])) {
                log2("不存在的top_id:$k", $tmp);
                continue;
            }
            $result[$k]['total_amount'] += $v['total_amount'];
            $result[$k]['total_prize'] += $v['total_prize'];
            $result[$k]['total_rebate'] += $v['total_rebate'];
            $result[$k]['total_win'] += $v['total_win'];
        }
        //>>snow 释放内存
        unset($tmp);
        return $result;
    }


    /********************************* snow 复制代码用来排除dcsite 总代数据 end  ***********************************************************/
    static public function getTeamTotalSales($startDate = '', $endDate = '')
    {
        // 判断是否存在总代，不存在返回空
        // $targetUsers = users::getUserTree(0, true, 0, 8);
        $targetUsers = users::getUserTreeField([
            'field' => ['user_id', 'username', 'level', 'is_test', 'parent_id'],
            'parent_id' => 0,
            'status' => 8,
        ]);
        if (!$targetUsers) {
            return array();
        }
        //先初始化
        $result = array();
        foreach ($targetUsers as $k => $v) {
            $result[$v['user_id']]['user_id'] = $v['user_id'];
            $result[$v['user_id']]['username'] = $v['username'];
            $result[$v['user_id']]['level'] = $v['level'];
            $result[$v['user_id']]['is_test'] = $v['is_test'];
            $result[$v['user_id']]['parent_id'] = $v['parent_id'];
            $result[$v['user_id']]['total_amount'] = 0;
            $result[$v['user_id']]['total_prize'] = 0;
            $result[$v['user_id']]['total_rebate'] = 0;
            $result[$v['user_id']]['total_win'] = 0;
        }

        // 从user_sales中获得所有总代的总投注、总奖金、总返点、总盈亏
        $sql1 = 'SELECT user_id, top_id, SUM(buy_amount) AS total_amount, SUM(prize_amount) AS total_prize, SUM(rebate_amount) AS total_rebate, SUM(win_amount) AS total_win
                FROM user_sales WHERE 1';
        if ($startDate != '') {
            $sql1 .= " AND belong_date >= '$startDate'";
        }
        if ($endDate != '') {
            $sql1 .= " AND belong_date <= '$endDate'";
        }
        $sql1 .= ' GROUP BY top_id ORDER BY top_id DESC';
        $tmp = $GLOBALS['db']->getAll($sql1, array(),'top_id');

        foreach ($tmp AS $k => $v) {
            if (!isset($result[$k])) {
                log2("不存在的top_id:$k", $tmp);
                continue;
            }
            $result[$k]['total_amount'] += $v['total_amount'];
            $result[$k]['total_prize'] += $v['total_prize'];
            $result[$k]['total_rebate'] += $v['total_rebate'];
            $result[$k]['total_win'] += $v['total_win'];
        }

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('user_sales', $data);
    }

    /**
     *
     * @param type $datas
     * @param type $insertOnceLimit  一次插入数
     * @return boolean
     * @throws exception2
     */
    static public function addItems($datas, $insertOnceLimit)
    {
        if (!is_array($datas)) {
            throw new exception2('参数无效');
        }
        if (count($datas) == 0) {
            return true;
        }

        $lotterys = lottery::getItems(0, -1);
        $lottery_ids = array_keys($lotterys);
        $count = count($datas);
        $tmp1 = array();
        foreach ($datas as $user_id => $v) {
            foreach ($lottery_ids AS $lottery_id) {
                $v['buy_amount_' . $lottery_id] = isset($v['buy_amount_' . $lottery_id]) ? $v['buy_amount_' . $lottery_id] : 0;
                $v['win_amount_' . $lottery_id] = isset($v['win_amount_' . $lottery_id]) ? $v['win_amount_' . $lottery_id] : 0;
            }

            $tmp1[] = "('{$user_id}', '{$v['top_id']}','{$v['belong_date']}', '{$v['buy_amount']}', '{$v['prize_amount']}', '{$v['rebate_amount']}', '{$v['win_amount']}', '{$v['buy_amount_1']}', '{$v['win_amount_1']}','{$v['buy_amount_2']}','{$v['win_amount_2']}','{$v['buy_amount_3']}','{$v['win_amount_3']}','{$v['buy_amount_4']}','{$v['win_amount_4']}',
                '{$v['buy_amount_5']}', '{$v['win_amount_5']}', '{$v['buy_amount_6']}', '{$v['win_amount_6']}', '{$v['buy_amount_7']}', '{$v['win_amount_7']}', '{$v['buy_amount_8']}', '{$v['win_amount_8']}', '{$v['buy_amount_9']}', '{$v['win_amount_9']}', '{$v['buy_amount_10']}', '{$v['win_amount_10']}',
                '{$v['buy_amount_11']}', '{$v['win_amount_11']}', '{$v['buy_amount_12']}', '{$v['win_amount_12']}', '{$v['buy_amount_13']}', '{$v['win_amount_13']}', '{$v['buy_amount_14']}', '{$v['win_amount_14']}', '{$v['buy_amount_15']}', '{$v['win_amount_15']}', '{$v['buy_amount_16']}', '{$v['win_amount_16']}', '{$v['buy_amount_17']}', '{$v['win_amount_17']}', '{$v['buy_amount_18']}', '{$v['win_amount_18']}', '{$v['buy_amount_19']}', '{$v['win_amount_19']}', '{$v['buy_amount_20']}', '{$v['win_amount_20']}', '{$v['buy_amount_21']}', '{$v['win_amount_21']}', '{$v['buy_amount_22']}', '{$v['win_amount_22']}', '{$v['buy_amount_23']}', '{$v['win_amount_23']}', '{$v['buy_amount_24']}', '{$v['win_amount_24']}', '{$v['buy_amount_25']}', '{$v['win_amount_25']}', '{$v['buy_amount_26']}', '{$v['win_amount_26']}')";
        }

        $tmp2 = '';
        $times = ceil($count / $insertOnceLimit);
        for ($i = 0; $i < $times; $i++) {
            $tmp2 = array_slice($tmp1, $i * $insertOnceLimit, $insertOnceLimit);
            $sql = "INSERT INTO user_sales (user_id, top_id, belong_date, buy_amount, prize_amount, rebate_amount, win_amount, buy_amount_1, win_amount_1, buy_amount_2, win_amount_2, buy_amount_3, win_amount_3, buy_amount_4, win_amount_4, buy_amount_5, win_amount_5, buy_amount_6, win_amount_6,buy_amount_7, win_amount_7, buy_amount_8, win_amount_8, buy_amount_9, win_amount_9, buy_amount_10, win_amount_10, buy_amount_11, win_amount_11, buy_amount_12, win_amount_12, buy_amount_13, win_amount_13, buy_amount_14, win_amount_14, buy_amount_15, win_amount_15, buy_amount_16, win_amount_16, buy_amount_17, win_amount_17, buy_amount_18, win_amount_18, buy_amount_19, win_amount_19, buy_amount_20, win_amount_20, buy_amount_21, win_amount_21, buy_amount_22, win_amount_22, buy_amount_23, win_amount_23, buy_amount_24, win_amount_24, buy_amount_25, win_amount_25, buy_amount_26, win_amount_26) VALUES " . implode(',', $tmp2);

            if (!$GLOBALS['db']->query($sql, array(), 'i')) {
                log2("sql执行出错:$i", $sql);
            }
        }

        return true;
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $paramArr = $set = array();
        foreach ($data as $k => $v) {
            $set[] = $k . '=:' . $k;
            $paramArr[':'.$k] = $v;
        }
        $sql = "UPDATE  user_sales  SET " . implode(',', $set) . " WHERE us_id=:us_id LIMIT 1";
        $paramArr[':us_id'] = $id;
        if (!$GLOBALS['db']->query($sql, $paramArr, 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM user_sales WHERE us_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    static public function deleteItems($belong_date)
    {
        $sql = "DELETE FROM user_sales WHERE 1 AND belong_date = '$belong_date'";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}

?>