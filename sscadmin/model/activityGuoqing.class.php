<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class activityGuoqing
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM activity_guoqing WHERE activity_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM activity_guoqing  WHERE activity_id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //取得报表 按UID , 日期
    static public function getItems($startDate = '', $endDate = '', $userId = -1, $orderby = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,u.username FROM activity_guoqing  AS a LEFT JOIN users AS u ON a.user_id=u.user_id WHERE 1';
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND a.date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.date <= '$endDate'";
        }
        if ($userId !== -1) {
            $sql .= " AND a.user_id = '$userId'";
        }
        if ($orderby !== '') {
            $sql .= " ORDER BY a.$orderby DESC";
        }
        else {
            $sql .= " ORDER BY a.activity_id DESC";
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

// dump($sql);
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber($startDate = '', $endDate = '')
    {
        $sql = "SELECT COUNT(*) AS count FROM activity_guoqing  WHERE 1";
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND date <= '$endDate'";
        }
        $sql .= " LIMIT 0,1 ";
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('activity_guoqing', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('activity_guoqing',$data,array('activity_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM activity_guoqing WHERE activity_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     *  开奖
     * @param type $date 奖期日期
     * @param type $number 开奖号码5位
     * @return boolean
     */
    static public function checkPrize($date, $number)
    {
        //按照amount降序排行 
        $activitys = activityGuoqing::getItems($date, $date, -1, 'amount');

        $maxLevel1 = 1;
        $winLevel1 = 0;
        $level1 = substr($number, 1);

        $maxLevel2 = 99999;
        $winLevel2 = 0;
        $level2 = substr($number, 2);

        $maxLevel3 = 3;
        $winLevel3 = 0;
        $level3 = substr($number, 3);

        $maxLevel4 = 5;
        $winLevel4 = 0;
        $level4 = substr($number, 3);

        $winUsers = array();
        $winUsersLevel = array();

        $userCodes = array();
        foreach ($activitys as $activity) {
            //未开奖 与已经参与
            if ($activity['is_joined'] == 1) {
                if (!isset($userCodes[$activity['user_id']])) {
                    $userCodes[$activity['user_id']] = array();
                }
                $userCodes[$activity['user_id']][] = array('activity_id' => $activity['activity_id'], 'prize_num' => $activity['prize_num']);
            }
        }
        foreach ($activitys as $activity) {
            if (activityGuoqing::updateItem($activity['activity_id'], array('check_num' => $number, 'is_check_prize' => 1, 'level' => 0)) === false) {
                throw new exception2("更新开奖号码出现错误 activity_id:{$activity['activity_id']}");
            }
        }

        foreach ($userCodes as $userId => $activityChecks) {
            $winUsersLevel[$userId] = array();
            foreach ($activityChecks as $activity) {
                //发1等奖
                if ($winLevel1 < $maxLevel1 && $level1 == $activity['prize_num']) {
                    //这里排除发同一种将
                    $winUsersLevel[$userId][1] = $activity['activity_id'];
                }
                //发2等奖
                elseif ($winLevel2 < $maxLevel2 && $level2 == substr($activity['prize_num'], 1)) {
                    //这里排除发同一种将
                    $winUsersLevel[$userId][2] = $activity['activity_id'];
                }
                //发3等奖
                elseif ($winLevel3 < $maxLevel3 && $level3 == substr($activity['prize_num'], 2)) {
                    //这里排除发同一种将
                    $winUsersLevel[$userId][3] = $activity['activity_id'];
                }
                //发4等奖
                elseif ($winLevel4 < $maxLevel4 && $level4 == substr($activity['prize_num'], 2)) {
                    //这里排除发同一种将
                    $winUsersLevel[$userId][4] = $activity['activity_id'];
                }
            }
            //多个号码只能发一个奖
            if (isset($winUsersLevel[$userId][1])) {
                if (activityGuoqing::updateItem($winUsersLevel[$userId][1], array('level' => 1)) !== false) {
                    $winLevel1++;
                }
                else {
                    throw new exception2("更新发奖出现错误 activity_id:{$winUsersLevel[$userId][1]}");
                }
            }
            elseif (isset($winUsersLevel[$userId][2])) {
                if (activityGuoqing::updateItem($winUsersLevel[$userId][2], array('level' => 2)) !== false) {
                    $winLevel2++;
                }
                else {
                    throw new exception2("更新发奖出现错误 activity_id:{$winUsersLevel[$userId][2]}");
                }
            }
            elseif (isset($winUsersLevel[$userId][3])) {
                if (activityGuoqing::updateItem($winUsersLevel[$userId][3], array('level' => 3)) !== false) {
                    $winLevel3++;
                }
                else {
                    throw new exception2("更新发奖出现错误 activity_id:{$winUsersLevel[$userId][3]}");
                }
            }
            elseif (isset($winUsersLevel[$userId][4])) {
                if (activityGuoqing::updateItem($winUsersLevel[$userId][4], array('level' => 4)) !== false) {
                    $winLevel4++;
                }
                else {
                    throw new exception2("更新发奖出现错误 activity_id:{$winUsersLevel[$userId][4]}");
                }
            }
        }

        return true;
    }

    /*
     * old backup
     */
    static public function checkPrize1($date, $number)
    {
        //按照amount降序排行 
        $activitys = activityGuoqing::getItems($date, $date, -1, 'amount');

        $maxLevel1 = 1;
        $winLevel1 = 0;
        $level1 = substr($number, 1);

        $maxLevel2 = 99999;
        $winLevel2 = 0;
        $level2 = substr($number, 2);

        $maxLevel3 = 3;
        $winLevel3 = 0;
        $level3 = substr($number, 3);

        $maxLevel4 = 5;
        $winLevel4 = 0;
        $level4 = substr($number, 3);

        $winUsers = array();

        foreach ($activitys as $activity) {
            if (!activityGuoqing::updateItem($activity['activity_id'], array('check_num' => $number, 'is_check_prize' => 1))) {
                throw new exception2("更新开奖号码出现错误 activity_id:{$activity['activity_id']}");
            }
            if (in_array($activity['user_id'], $winUsers)) {
                continue;
            }

            //未开奖 与已经参与
            if ($activity['is_check_prize'] == 0 && $activity['is_joined'] == 1) {
                //发1等奖
                if ($winLevel1 < $maxLevel1 && $level1 == $activity['prize_num']) {
                    if (activityGuoqing::updateItem($activity['activity_id'], array('level' => 1))) {
                        $winLevel1++;
                        $winUsers[] = $activity['user_id'];
                    }
                    else {
                        throw new exception2("更新发奖出现错误 activity_id:{$activity['activity_id']}");
                    }
                }
                //发2等奖
                elseif ($winLevel2 < $maxLevel2 && $level2 == substr($activity['prize_num'], 1)) {
                    if (activityGuoqing::updateItem($activity['activity_id'], array('level' => 2))) {
                        $winLevel2++;
                        $winUsers[] = $activity['user_id'];
                    }
                    else {
                        throw new exception2("更新发奖出现错误 activity_id:{$activity['activity_id']}");
                    }
                }
                //发3等奖
                elseif ($winLevel3 < $maxLevel3 && $level3 == substr($activity['prize_num'], 2)) {
                    if (activityGuoqing::updateItem($activity['activity_id'], array('level' => 3))) {
                        $winLevel3++;
                        $winUsers[] = $activity['user_id'];
                    }
                    else {
                        throw new exception2("更新发奖出现错误 activity_id:{$activity['activity_id']}");
                    }
                }

                //发4等奖
                elseif ($winLevel4 < $maxLevel4 && $level4 == substr($activity['prize_num'], 2)) {
                    if (activityGuoqing::updateItem($activity['activity_id'], array('level' => 4))) {
                        $winLevel4++;
                        $winUsers[] = $activity['user_id'];
                    }
                    else {
                        throw new exception2("更新发奖出现错误 activity_id:{$activity['activity_id']}");
                    }
                }
            }
        }

        return true;
    }

}

?>