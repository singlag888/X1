<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class egameMwSiteUsergamelog
{
    public static function getItem($belong_datetime)
    {
        $sql = "SELECT emsu_id FROM egame_mw_site_usergamelog WHERE belong_datetime = " . "'$belong_datetime'";

        return $GLOBALS['db']->getRow($sql);
    }

    public static function getTopLogs($start_time = '', $end_time = '', $is_test = 0, $top_id = 0, $xy_prefix)
    {
        $sql = 'SELECT e.top_id, sum(e.play_money) play_money, sum(win_money) win_money, (sum(win_money) - sum(e.play_money)) play_jifen_amount, e.xy_prefix FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($top_id > 0) {
            $sql .= " AND e.top_id = '$top_id'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $sql .= " GROUP BY e.top_id";
        $result = $GLOBALS['db']->getAll($sql, array(),'top_id');

        return $result;
    }

    public static function getUserLogs($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix, $start, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT e.emsu_id, e.uid, e.parent_id, e.top_id, e.merchant_id, e.game_name, e.game_type, e.game_num, e.play_money, e.win_money, e.log_date, e.belong_datetime, e.xy_prefix, u.username FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1 AND e.play_money != 0 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($user_id > 0) {
            $sql .= " AND e.uid = '$user_id'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $sql .= ' ORDER BY e.log_date DESC';

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    public static function getUserLogsSum($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix, $start, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT sum(e.play_money) as total_play_money, sum(e.win_money) as total_win_money FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1 AND e.play_money != 0 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($user_id > 0) {
            $sql .= " AND e.uid = '$user_id'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $sql .= ' ORDER BY e.log_date DESC';
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    public static function getUserLogsGroupByUserId($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix, $start, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT e.emsu_id, e.uid, e.parent_id, e.top_id, e.merchant_id, e.game_name, e.game_type, e.game_num, e.log_date, e.belong_datetime, e.xy_prefix, u.username, sum(e.play_money) as total_play_money, sum(e.win_money) as total_win_money FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1 AND e.play_money != 0 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $sql .= " AND FIND_IN_SET('{$user_id}',u.parent_tree)";
        $sql .= " GROUP BY e.uid";

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    public static function getUserLogsGroupCount($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix)
    {
        $sql = 'SELECT count(*) AS count, sum(e.play_money) as total_play_money, sum(e.win_money) as total_win_money FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1 AND e.play_money != 0 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($user_id > 0) {
            $sql .= " AND e.uid = '$user_id'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    public static function getUserLogsGroupPageCount($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix, $start, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT count(*) AS count, sum(e.play_money) as total_play_money, sum(e.win_money) as total_win_money FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($user_id > 0) {
            $sql .= " AND e.uid = '$user_id'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    public static function getUserLogsGroupCountByUserId($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix)
    {
        $sql = 'SELECT count(*) AS count, sum(e.play_money) as total_play_money, sum(e.win_money) as total_win_money FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1 AND e.play_money != 0 AND xy_prefix =' .  "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $sql .= " AND FIND_IN_SET('{$user_id}',u.parent_tree)";
        $sql .= " GROUP BY e.uid";
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    public static function getUserLogsGroupPageCountByUserId($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix, $start, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT count(*) AS count, sum(e.play_money) as total_play_money, sum(e.win_money) as total_win_money FROM egame_mw_site_usergamelog e LEFT JOIN users u ON true WHERE 1 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $sql .= " AND FIND_IN_SET('{$user_id}',u.parent_tree)";

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    public static function getUserLogsCount($start_time = '', $end_time = '', $is_test = 0, $user_id = 0, $xy_prefix, $start, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT count(*) AS count, sum(e.play_money) as total_play_money, sum(e.win_money) as total_win_money FROM egame_mw_site_usergamelog e LEFT JOIN users u ON e.uid=u.user_id WHERE 1  AND e.play_money != 0 AND xy_prefix =' . "'$xy_prefix'";

        if ($is_test > 0) {
            $sql .= " AND u.is_test = '$is_test'";
        }

        if ($user_id > 0) {
            $sql .= " AND e.uid = '$user_id'";
        }

        if ($start_time != '') {
            $sql .= " AND e.log_date >= '$start_time'";
        }

        if ($end_time != '') {
            $sql .= " AND e.log_date <= '$end_time'";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    // 批量插入提高速度
    public static function addItems($datas, $belongDatetime, $xyPrefix)
    {
        $tmp = array();

        foreach ($datas as $v) {
            $idInfo = explode('_', $v['merchantId']);

            if (count($idInfo) < 3) {
                continue;
            }

            $uidInfo = explode('_', $v['uid']);

            if (count($uidInfo) < 3) {
                continue;
            }

            $tmp[] = "('{$uidInfo[2]}','{$idInfo[2]}','{$idInfo[1]}','{$v['merchantId']}','{$v['gameName']}','{$v['gameType']}','{$v['gameNum']}','{$v['playMoney']}','{$v['winMoney']}','{$v['logDate']}','{$belongDatetime}','{$xyPrefix}')";
        }

        $sql = "INSERT INTO egame_mw_site_usergamelog (uid,parent_id,top_id,merchant_id,game_name,game_type,game_num,play_money,win_money,log_date,belong_datetime,xy_prefix) VALUES " . implode(',', $tmp);

        return $GLOBALS['db']->query($sql, array(), 'i');
    }
}