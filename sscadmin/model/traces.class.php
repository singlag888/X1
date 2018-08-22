<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

// 追号模型
class traces
{
    static public function getItem($id, $needLock = false)
    {
        //注：如需要独占读，必须先开始事务，否则没有效果
        $sql = 'SELECT * FROM traces WHERE trace_id = ' . intval($id);
        if ($needLock) {
            $sql .= " LIMIT 1";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($lottery_id = 0, $is_test, $start_issue = '', $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $start = -1, $amount = DEFAULT_PER_PAGE, $status = -1)
    {
        $sql = 'SELECT a.*,b.username FROM traces a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($status != -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($start_issue != '') {
            $sql .= " AND a.start_issue = '$start_issue'";
        }
        if ($modes != 0) {
            $sql .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if ($include_childs == 0) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                $sql .= " AND FIND_IN_SET('{$user_id}',b.parent_tree)";
            }
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        $sql .= " ORDER BY trace_id DESC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber($lottery_id = 0, $is_test, $start_issue = '', $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $status = -1)
    {
        $sql = 'SELECT COUNT(*) AS count FROM traces a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($status != -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($start_issue != '') {
            $sql .= " AND a.start_issue = '$start_issue'";
        }
        if ($modes != 0) {
            $sql .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if ($include_childs == 0) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                $sql .= " AND FIND_IN_SET('{$user_id}',b.parent_tree)";
            }
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }


    /*********************** snow 复制代码,用来排除dcsite start********************************************/
    /**
     * author snow
     * @param $options
     * @return mixed
     */
    static public function getItemsExclude($options)
    {


        $default_options = [
            'lottery_id'                => 0,   //>>彩种id
            'is_test'                   => -1,  //>>默认不显示测试账号
            'start_issue'               => '',  //>>开始奖期
            'modes'                     => 0,   //>>元角模式
            'user_id'                   => '',  //>>用户id
            'include_childs'            => 0,   //>>是否包含下级
            'start_date'                => '',  //>>起始时间
            'end_date'                  => '',  //>>结束时间
            'start'                     => -1,  //>>limit 的开始记录数
            'amount'                    => DEFAULT_PER_PAGE,//>>默认每页显示条数
            'status'                    => -1,  //>>状态
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $sql = 'SELECT a.*,b.username FROM traces a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';


        if(is_array($default_options['lottery_id']) && !empty($default_options['lottery_id'])) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $default_options['lottery_id']) . "')";
        }
        elseif ($default_options['lottery_id'] != 0) {
            $sql .= " AND a.lottery_id = " . intval($default_options['lottery_id']);
        }

        if ($default_options['status'] != -1) {
            $sql .= " AND a.status = " . intval($default_options['status']);
        }
        if ($default_options['start_issue'] != '') {
            $sql .= " AND a.start_issue = '{$default_options['start_issue']}'";
        }
        if ($default_options['modes'] != 0) {
            $sql .= " AND a.modes = '{$default_options['modes']}'";
        }
        if ($default_options['user_id'] != '') {
            //>>snow 添加 ,如果选择了总代,或用户名,查询所有数据
//            $default_options['is_test'] = -1;
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_id']);
            if ($default_options['include_childs'] == 0) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '{$default_options['user_id']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_id']}'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($default_options['user_id'])) {
                        return array();
                    }
                    $default_options['user_id'] = $user['user_id'];
                }
                $sql .= " AND (FIND_IN_SET('{$default_options['user_id']}',b.parent_tree) OR b.user_id = {$default_options['user_id']})";
            }
        } else {
            //>>没有输入用户名的时候
            //>>排除dcsite  不排除了
            $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
        }

        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }

        if ($default_options['start_date'] != '') {
            $sql .= " AND a.create_time >= '{$default_options['start_date']}'";
        }
        if ($default_options['end_date'] != '') {
            $sql .= " AND a.create_time <= '{$default_options['end_date']}'";
        }
        $sql .= " ORDER BY trace_id DESC";
        if ($default_options['start'] > -1) {
            $sql .= " LIMIT {$default_options['start']}, {$default_options['amount']}";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     * author snow
     * @param $options
     * @return mixed
     */
    static public function getItemsNumberExclude($options)
    {

        //>>设置默认参数

        $default_options = [
            'lottery_id'                => 0,   //>>彩种id
            'is_test'                   => -1,  //>>默认不显示测试账号
            'start_issue'               => '',  //>>开始奖期
            'modes'                     => 0,   //>>元角模式
            'user_id'                   => '',  //>>用户id
            'include_childs'            => 0,   //>>是否包含下级
            'start_date'                => '',  //>>起始时间
            'end_date'                  => '',  //>>结束时间
            'status'                    => -1,  //>>状态
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;

        $sql = 'SELECT COUNT(*) AS count FROM traces a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';

//        //>>排除dcsite  不排除了
//        $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
        if(is_array($default_options['lottery_id']) && !empty($default_options['lottery_id'])) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $default_options['lottery_id']) . "')";
        }
        elseif ($default_options['lottery_id'] != 0) {
            $sql .= " AND a.lottery_id = " . intval($default_options['lottery_id']);
        }

        if ($default_options['status'] != -1) {
            $sql .= " AND a.status = " . intval($default_options['status']);
        }
        if ($default_options['start_issue'] != '') {
            $sql .= " AND a.start_issue = '{$default_options['start_issue']}'";
        }
        if ($default_options['modes'] != 0) {
            $sql .= " AND a.modes = '{$default_options['modes']}'";
        }
        if ($default_options['user_id'] != '') {

            //>>snow 添加  ,如果传入的用户不为空 ,把istest 置为-1 查询全部相关数据
//            $default_options['is_test'] = -1;
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_id']);
            if ($default_options['include_childs'] == 0) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '{$default_options['user_id']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_id']}'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($default_options['user_id'])) {
                        return 0;
                    }
                    $default_options['user_id'] = $user['user_id'];
                }
                $sql .= " AND FIND_IN_SET('{$default_options['user_id']}',b.parent_tree)";
            }
        } else {
            //>>排除dcsite  没有输入用户名的时候
            $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
        }
        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }
        if ($default_options['start_date'] != '') {
            $sql .= " AND a.create_time >= '{$default_options['start_date']}'";
        }
        if ($default_options['end_date'] != '') {
            $sql .= " AND a.create_time <= '{$default_options['end_date']}'";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }


    /*********************** snow 复制代码,用来排除dcsite end  ********************************************/
    static public function getItemsById($ids, $user_id = 0)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM traces WHERE trace_id IN(' . implode(',', $ids) . ')';
        if ($user_id) {
            $sql .= " AND user_id = $user_id";
        }

        return $GLOBALS['db']->getAll($sql, array(),'trace_id');
    }

    //按规则生成唯一订单编号
    static public function wrapId($trace_id, $issue, $lottery_id)
    {
//        //T130117001010028E
        switch ($lottery_id) {
            case '1':
                $str = 'CQ';
                break;
            case '2':
                $str = 'SD';
                break;
            case '3':
                $str = 'HLJ';
                break;
            case '4':
                $str = 'XJ';
                break;
            case '5':
                $str = 'CQ5';
                break;
            case '6':
                $str = 'JX5';
                break;
            case '7':
                $str = 'GD5';
                break;
            case '8':
                $str = 'TJ';
                break;
            case '9':
                $str = 'L3D';
                break;
            case '10':
                $str = 'P3';
                break;
            case '11':
                $str = 'YF';
                break;
            case '12':
                $str = 'JS';
                break;
            case '13':
                $str = 'KSF';
                break;
            case '14':
                $str = 'PK';
                break;
            case '15':
                $str = 'MMC';
                break;
            case '16':
                $str = 'FF5';
                break;
            case '17':
                $str = 'PKS';
                break;
            case '18':
                $str = 'DJ';
                break;
            case '19':
                $str = 'AHKS';
                break;
            case '20':
                $str = 'FJKS';
                break;
            case '21':
                $str = 'LHC';
                break;
            case '22':
                $str = 'SSQ';
            case '23':
                $str = 'XY';
                break;
            case '24':
                $str = 'QQ';
                break;
            case '25':
                $str = 'JSLH';
                break;
            case '26':
                $str = 'XYFT';
                break;
            default:
                throw new exception2("Unknown rules for lottery {$lottery_id}");
                break;
        }
        $str .= substr(str_replace('-', '', $issue), -8);
        $str .= str_pad($trace_id, 7, '0', STR_PAD_LEFT);
        $result = "{$str}T";
        return $result;

        //return 'T' . encode($trace_id) . 'E';
    }

    static public function dewrapId($str)  //, $issue, $lottery_id
    {
        if (!preg_match('`^(\w{15,21})T$`Ui', $str, $match)) {
            return 0;
        }

        $result = ltrim(substr($str, -8, 7), '0');

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('traces', $data);
    }

    static public function updateCancelTimes($trace_id, $times = 1)
    {
        if (!is_numeric($trace_id) || !is_numeric($times)) {
            return false;
        }
        $sql = "UPDATE traces SET cancel_times = cancel_times + $times WHERE trace_id = $trace_id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function updateWinTimes($trace_id, $times = 1)
    {
        if (!is_numeric($trace_id) || !is_numeric($times)) {
            return false;
        }
        $sql = "UPDATE traces SET win_times = win_times + $times WHERE trace_id = $trace_id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function updateFinishTimes($trace_id, $times = 1)
    {
        if (!is_numeric($trace_id) || !is_numeric($times)) {
            return false;
        }
        $sql = "UPDATE traces SET finish_times = finish_times + $times WHERE trace_id = $trace_id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('traces', $data, array('trace_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM traces WHERE trace_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * 以下是trace_details模型
     */
    static public function addTraceDetail($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('trace_details', $data);
    }

    static public function updateTraceDetail($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('trace_details',$data, array('td_id'=>$id));
    }

    static public function deleteTraceDetail($id)
    {
        $sql = "DELETE FROM trace_details WHERE td_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}

?>