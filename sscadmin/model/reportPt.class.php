<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 注：该表不包含is_test字段，因为凡是进入PT都是真钱，不是测试帐号
 *     讨论后：不包含status字段，LEFT JOIN users表解决
 */
class reportPt
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM report_pt WHERE rp_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM report_pt  WHERE rp_id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($user_id = 0, $belong_date = '')
    {
        $sql = 'SELECT * FROM report_pt WHERE 1';
        if($user_id != 0) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        if($belong_date != ''){
            $sql .= " AND belong_date = '" . $belong_date . "'";
        }

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 获得用户的总计
     * @param type $userId
     * @param type $startDate
     * @param type $endDate
     * @return type
     */
    static public function getSumByUser($userId = 0, $startDate = '', $endDate = '')
    {
        $sql = "SELECT user_id, SUM(pt_buy_amount) AS pt_buy_amount, SUM(pt_prize_amount) AS pt_prize, SUM(pt_win_lose) AS pt_game_win FROM report_pt WHERE 1  ";
        if($userId != 0) {
            $sql .= " AND user_id = " . intval($userId);
        }
        if ($startDate != '') {
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate != '') {
            $sql .= " AND belong_date <= '$endDate'";
        }

        $sql .= ' GROUP BY user_id ORDER BY user_id DESC';
        $result = $GLOBALS['db']->getAll($sql, array(),'user_id');

        return $result;
    }
    
    /**
     * 按照用户和日期范围获取PT SUM 数据
     * 当$user_id 有团队时列出其自己+直属下级数据,当$user_id只是会员则显示自己
     * 注：PT都是真钱所以无test账户
     * @author Davy 2015-04-27
     * @param $user_id
     * @param $start_time
     * @param $end_time
     * @param $status
     */
    static public function getSumGroupByUser($user_id, $start_time = '', $end_time = '', $status = -1)
    {
        //先得到所有孩子 140609 取所有孩子
        // $users = users::getUserTree($user_id, true, 1, 8);
        $users = users::getUserTreeField([
            'field' => ['user_id'],
            'parent_id' => $user_id,
            'recursive' => 1,
            'status' => 8
        ]);
        if (!$users) {
            return array();
        }

        //得到自己和直接下级(包括下面所有级别孩子)的总PT data
        $sql = 'SELECT r.user_id,u.parent_tree ';
        $sql .= ',sum(r.pt_buy_amount) as pt_buy_amount ';
        $sql .= ',sum(r.pt_prize_amount) as pt_prize ';
        $sql .= ',sum(r.pt_win_lose) as pt_game_win ';
        $sql .= 'FROM report_pt r LEFT JOIN users u ON r.user_id=u.user_id ';
        $sql .= 'WHERE 1 ';
        if ($start_time != '') {
            $sql .= " AND r.belong_date >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND r.belong_date <= '$end_time'";
        }
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= ' AND u.status IN (' . implode(',', $status) . ')';
            }
            else {
                $sql .= ' AND u.status = ' . intval($status);
            }
        }

        //141104 优化情节 联表find_in_set()用不上索引，应改为单表查出团队ID再IN更优
        $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($users), 'u.user_id');
        $sql .= ' GROUP BY r.user_id ORDER BY r.user_id DESC';
        $result = $GLOBALS['db']->getAll($sql, array(),'user_id');

        return $result;
    }

    /**
     * @todo   按照日期获取PT总代团队数据
     * @author Davy 2015-04-27
     * @param $status
     * @param $date
     */
    static public function getTopTeamData($date, $status = -1)
    {
        $sql = "SELECT r.top_id,sum(r.last_pt_balance) as total_last_pt_balance ";
        $sql .= ",sum(r.pt_balance) as total_pt_balance ";
        $sql .= ",sum(r.pt_buy_amount) as total_pt_buy_amount ";
        $sql .= ",sum(r.pt_prize_amount) as total_pt_prize_amount ";
        $sql .= ",sum(r.pt_win_lose) as total_pt_win_lose ";
        $sql .= "FROM report_pt r LEFT JOIN users u ON r.user_id=u.user_id ";
        $sql .= "WHERE r.belong_date='{$date}' ";
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND u.status IN (" . implode(',', $status) . ")";
            }
            else {
                $sql .= " AND u.status = " . intval($status);
            }
        }
        $sql .= " GROUP BY r.top_id ORDER BY r.top_id ASC";
        $result = $GLOBALS['db']->getAll($sql, array(),'top_id');

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('report_pt', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('report_pt',$data,array('rp_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM report_pt WHERE rp_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}

?>