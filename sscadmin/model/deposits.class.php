<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 存款记录模型
 */
class deposits
{

    const MACHINE_ID = 65535; //机器处理的admin_id为65535

    static public function getItem($id, $status = NULL)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM deposits WHERE deposit_id = ' . intval($id);
        }
        elseif (preg_match('`^YP\w{2,}$`', $id)) {
            $sql = 'SELECT * FROM deposits WHERE order_num = \'' . $id . '\'';
        }
        else {
            return array();
        }
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }
    static public function getItemByCond($cond = '')
    {
        $sql = 'SELECT * FROM deposits WHERE 1';
        if($cond)
        {
            $sql .= ' AND '.$cond;
        }
        return $GLOBALS['db']->getRow($sql);
    }

    //增加两个参数:start_amount  ,  end_amount  可以按金额范围搜索,易扩展点吧
    //$is_manual 是否手动充值
    static public function getItems($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = 0, $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE, $create_stime = '',$create_etime = '',$freeze = -1,$orderBy = '')
    {
        $sql = 'SELECT a.*,b.username,b.level,b.is_test,b.status AS user_status FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';

        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    if($freeze == -1) {
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($user_id)) {
                            return array();
                        }
                    }else{
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($user_id,8,false,1,1)) {
                            return array();
                        }
                    }
                    $user_id = $user['user_id'];
                }
                if($freeze == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'status' => 8
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, 8,-1,'',-1); // 这里freeze -1 完全是BUG
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status != -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != 0) {
            $sql .= " AND a.trade_type = " . intval($trade_type);
        }
        if ($deposit_bank_id != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            $sql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            $sql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.finish_time <= '$endDate'";
        }

        if ($create_stime !== '') {
            $sql .= " AND a.create_time >= '$create_stime'";
        }
        if ($create_etime !== '') {
            $sql .= " AND a.create_time <= '$create_etime'";
        }
        if($orderBy) {
            $sql .= ' ORDER BY '.$orderBy;
        }else{
            $sql .= ' ORDER BY deposit_id DESC';
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }
    /************************ snow  复制一个方法,用来排除dcsite 总代下的所有数据 start************************************/

    //增加两个参数:start_amount  ,  end_amount  可以按金额范围搜索,易扩展点吧
    //$is_manual 是否手动充值
    /**
     * author snow 修改为数组传参
     * @param $options
     * @return mixed
     */
    static public function getItemsExclude($options)
    {
        //>>默认参数
        $default_options = [
        'user_id'           => '',
        'include_childs'    => 0,
        'is_test'           => -1,
        'is_manual'         => -1,
        'status'            => -1,
        'trade_type'        => 0,
        'deposit_bank_id'   => 0,
        'deposit_card_id'   => 0,
        'start_amount'      => 0,
        'end_amount'        => 0,
        'order_num'         => '',
        'startDate'         => '',
        'endDate'           => '',
        'start'             => -1,
        'amount'            => DEFAULT_PER_PAGE,
        'create_stime'      => '',
        'create_etime'      => '',
        'freeze'            => -1,
        'orderBy'           => '',
        'field'             => '*',
    ];

        //>>合并传入参数与默认参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $from = 'a.*';
        if (is_string($default_options['field'])) {
            $from = 'a.' . $default_options['field'];
        } elseif (is_array($default_options['field']) && !empty($default_options['field'])) {
            foreach ($default_options['field'] as $key => $val)
            {
                $default_options[$key] = 'a.' . $val;
            }

            $from = implode(',', $default_options['field']);
        }
        $sql = 'SELECT ' . $from . ',b.username,b.level,b.is_test,b.status AS user_status FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
//        //>>添加排除统计优惠彩金晕,不能排除snow
          //>>添加去除相应id的代码
        $sql .= ' AND a.user_id  NOT IN ' . (users::getUsersSqlByUserName());
        if ($default_options['user_id'] != '') {
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_id']);
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '{$default_options['user_id']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_id']}'";
                }
            }
            else {
                if (!$tmp) {
                    if($default_options['freeze'] == -1) {
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_id'])) {
                            return array();
                        }
                    }else{
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_id'],8,false,1,1)) {
                            return array();
                        }
                    }
                    $default_options['user_id'] = $user['user_id'];
                }
                if($default_options['freeze'] == -1) {
                    // $teamUsers = users::getUserTree($default_options['user_id'], true, 1, 8);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_id'],
                        'recursive' => 1,
                        'status' => 8
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($default_options['user_id'], true, 1, 8,-1,'',-1); // 这里freeze -1 完全是BUG
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_id'],
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }
        if ($default_options['is_manual'] == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($default_options['is_manual'] == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($default_options['status'] != -1) {
            $sql .= " AND a.status = " . intval($default_options['status']);
        }
        if ($default_options['trade_type'] != 0) {
            $sql .= " AND a.trade_type = " . intval($default_options['trade_type']);
        }
        if ($default_options['deposit_bank_id'] != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($default_options['deposit_bank_id']);
        }
        if ($default_options['deposit_card_id'] != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($default_options['deposit_card_id']);
        }
        if ($default_options['start_amount'] > 0) {
            $sql .= " AND a.amount >= {$default_options['start_amount']}";
        }
        if ($default_options['end_amount'] > 0) {
            $sql .= " AND a.amount <= {$default_options['end_amount']}";
        }
        if ($default_options['order_num'] != '') {
            $sql .= " AND a.order_num = '{$default_options['order_num']}'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($default_options['startDate'] !== '') {
            $sql .= " AND a.finish_time >= '{$default_options['startDate']}'";
        }
        if ($default_options['endDate'] !== '') {
            $sql .= " AND a.finish_time <= '{$default_options['endDate']}'";
        }

        if ($default_options['create_stime'] !== '') {
            $sql .= " AND a.create_time >= '{$default_options['create_stime']}'";
        }
        if ($default_options['create_etime'] !== '') {
            $sql .= " AND a.create_time <= '{$default_options['create_etime']}'";
        }
        if($default_options['orderBy']) {
            $sql .= ' ORDER BY '.$default_options['orderBy'];
        }else{
            $sql .= ' ORDER BY deposit_id DESC';
        }
        if ($default_options['start'] > -1) {
            $sql .= " LIMIT {$default_options['start']}, {$default_options['amount']}";
        }
        $result = $GLOBALS['db']->getAll($sql,[],'deposit_id');
        return $result;
    }


    static public function getItemsExcludeStatistics($options)
    {
        //>>默认参数
        $default_options = [
            'user_id'           => '',
            'include_childs'    => 0,
            'is_test'           => -1,
            'is_manual'         => -1,
            'status'            => -1,
            'trade_type'        => 0,
            'deposit_bank_id'   => 0,
            'deposit_card_id'   => 0,
            'start_amount'      => 0,
            'end_amount'        => 0,
            'order_num'         => '',
            'startDate'         => '',
            'endDate'           => '',
            'start'             => -1,
            'amount'            => DEFAULT_PER_PAGE,
            'create_stime'      => '',
            'create_etime'      => '',
            'freeze'            => -1,
            'orderBy'           => '',
            'field'             => '*',
        ];

        //>>合并传入参数与默认参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $sql =<<<SQL
SELECT LEFT(finish_time, 10) AS belongDate,a.trade_type, SUM(amount) as amount,COUNT(DISTINCT (a.user_id)) AS num FROM deposits AS a,users AS b
WHERE a.user_id = b.user_id
AND b.top_id !=IF((SELECT user_id FROM users WHERE username = 'dcsite'),(SELECT user_id FROM users WHERE username = 'dcsite'),-1)
SQL;

        if ($default_options['user_id'] != '') {
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_id']);
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '{$default_options['user_id']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_id']}'";
                }
            }
            else {
                if (!$tmp) {
                    if($default_options['freeze'] == -1) {
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_id'])) {
                            return array();
                        }
                    }else{
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_id'],8,false,1,1)) {
                            return array();
                        }
                    }
                    $default_options['user_id'] = $user['user_id'];
                }
                if($default_options['freeze'] == -1) {
                    // $teamUsers = users::getUserTree($default_options['user_id'], true, 1, 8);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_id'],
                        'recursive' => 1,
                        'status' => 8
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($default_options['user_id'], true, 1, 8,-1,'',-1); // 这里freeze -1 完全是BUG
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_id'],
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }
        if ($default_options['is_manual'] == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($default_options['is_manual'] == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($default_options['status'] != -1) {
            $sql .= " AND a.status = " . intval($default_options['status']);
        }
        if ($default_options['trade_type'] != 0) {
            $sql .= " AND a.trade_type = " . intval($default_options['trade_type']);
        }
        if ($default_options['deposit_bank_id'] != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($default_options['deposit_bank_id']);
        }
        if ($default_options['deposit_card_id'] != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($default_options['deposit_card_id']);
        }
        if ($default_options['start_amount'] > 0) {
            $sql .= " AND a.amount >= {$default_options['start_amount']}";
        }
        if ($default_options['end_amount'] > 0) {
            $sql .= " AND a.amount <= {$default_options['end_amount']}";
        }
        if ($default_options['order_num'] != '') {
            $sql .= " AND a.order_num = '{$default_options['order_num']}'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($default_options['startDate'] !== '') {
            $sql .= " AND a.finish_time >= '{$default_options['startDate']}'";
        }
        if ($default_options['endDate'] !== '') {
            $sql .= " AND a.finish_time <= '{$default_options['endDate']}'";
        }

        if ($default_options['create_stime'] !== '') {
            $sql .= " AND a.create_time >= '{$default_options['create_stime']}'";
        }
        if ($default_options['create_etime'] !== '') {
            $sql .= " AND a.create_time <= '{$default_options['create_etime']}'";
        }

        $sql .= 'GROUP BY belongDate, a.trade_type';
        $result = $GLOBALS['db']->getAll($sql);
        return $result;
    }

    static public function getItemsNum($options)
    {
        //>>默认参数
        $default_options = [
            'user_id'           => '',
            'include_childs'    => 0,
            'is_test'           => -1,
            'is_manual'         => -1,
            'status'            => -1,
            'trade_type'        => 0,
            'deposit_bank_id'   => 0,
            'deposit_card_id'   => 0,
            'start_amount'      => 0,
            'end_amount'        => 0,
            'order_num'         => '',
            'startDate'         => '',
            'endDate'           => '',
            'start'             => -1,
            'amount'            => DEFAULT_PER_PAGE,
            'create_stime'      => '',
            'create_etime'      => '',
            'freeze'            => -1,
            'orderBy'           => '',
            'field'             => '*',
        ];

        //>>合并传入参数与默认参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $sql =<<<SQL
SELECT LEFT(finish_time, 10) AS belongDate, COUNT(DISTINCT (a.user_id)) AS num FROM deposits AS a,users AS b
WHERE a.user_id = b.user_id 
AND b.top_id !=(SELECT user_id FROM users WHERE username = 'dcsite')
SQL;

        if ($default_options['user_id'] != '') {
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_id']);
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '{$default_options['user_id']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_id']}'";
                }
            }
            else {
                if (!$tmp) {
                    if($default_options['freeze'] == -1) {
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_id'])) {
                            return array();
                        }
                    }else{
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_id'],8,false,1,1)) {
                            return array();
                        }
                    }
                    $default_options['user_id'] = $user['user_id'];
                }
                if($default_options['freeze'] == -1) {
                    // $teamUsers = users::getUserTree($default_options['user_id'], true, 1, 8);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_id'],
                        'recursive' => 1,
                        'status' => 8
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($default_options['user_id'], true, 1, 8,-1,'',-1); // 这里freeze -1 完全是BUG
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_id'],
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }
        if ($default_options['is_manual'] == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($default_options['is_manual'] == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($default_options['status'] != -1) {
            $sql .= " AND a.status = " . intval($default_options['status']);
        }
        if ($default_options['trade_type'] != 0) {
            $sql .= " AND a.trade_type = " . intval($default_options['trade_type']);
        }
        if ($default_options['deposit_bank_id'] != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($default_options['deposit_bank_id']);
        }
        if ($default_options['deposit_card_id'] != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($default_options['deposit_card_id']);
        }
        if ($default_options['start_amount'] > 0) {
            $sql .= " AND a.amount >= {$default_options['start_amount']}";
        }
        if ($default_options['end_amount'] > 0) {
            $sql .= " AND a.amount <= {$default_options['end_amount']}";
        }
        if ($default_options['order_num'] != '') {
            $sql .= " AND a.order_num = '{$default_options['order_num']}'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($default_options['startDate'] !== '') {
            $sql .= " AND a.finish_time >= '{$default_options['startDate']}'";
        }
        if ($default_options['endDate'] !== '') {
            $sql .= " AND a.finish_time <= '{$default_options['endDate']}'";
        }

        if ($default_options['create_stime'] !== '') {
            $sql .= " AND a.create_time >= '{$default_options['create_stime']}'";
        }
        if ($default_options['create_etime'] !== '') {
            $sql .= " AND a.create_time <= '{$default_options['create_etime']}'";
        }

        $sql .= 'GROUP BY belongDate';
        $result = $GLOBALS['db']->getAll($sql);
        return $result;
    }
    /************************ snow  复制一个方法,用来排除dcsite 总代下的所有数据 end**************************************/
    //$is_manual 是否手动充值
    static public function getItemsList($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = 0, $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.username,b.level,b.is_test,b.status AS user_status FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
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
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status != -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != 0) {
            $sql .= " AND a.trade_type = " . intval($trade_type);
        }
        if ($deposit_bank_id != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            $sql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            $sql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }

        $sql .= ' ORDER BY deposit_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }
    static public function getItemsListNum($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = 0, $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '')
    {
        $sql = 'SELECT count(a.deposit_id) as num FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
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
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status != -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != 0) {
            $sql .= " AND a.trade_type = " . intval($trade_type);
        }
        if ($deposit_bank_id != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            $sql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            $sql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }

        $sql .= ' ORDER BY deposit_id DESC';
        $result = $GLOBALS['db']->getRow($sql);
        return $result['num'];
    }

    static public function getListByCond($cond,$field='*')
    {
        $sql = "select $field from deposits where 1";
        if($cond)
        {
            $sql.=' '.$cond;
        }

        return $GLOBALS['db']->getAll($sql);
    }
    static  public function upList($cond,$status = 9)
    {

        $sql = " update deposits set status = $status, remark = '订单超过30分钟自动取消' where ".$cond;
        return $GLOBALS['db']->query($sql,array(),'u');
    }


    static public function getItemsByTradeTypes($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = 0, $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE, $create_stime = '',$create_etime = '')
    {
        $sql = 'SELECT a.*,b.username,b.level,b.is_test,b.status AS user_status ,c.username as adminname FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id LEFT JOIN admins c ON c.admin_id=a.finish_admin_id WHERE 1';

        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
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
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status != -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != "") {
            $sql .= " AND a.trade_type in (" . $trade_type . ')';
        }
        if ($deposit_bank_id != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            $sql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            $sql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.finish_time <= '$endDate'";
        }

        if ($create_stime !== '') {
            $sql .= " AND a.create_time >= '$create_stime'";
        }
        if ($create_etime !== '') {
            $sql .= " AND a.create_time <= '$create_etime'";
        }

        $sql .= ' ORDER BY deposit_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

/******************** snow 复制代码 ,用来排除dcsite 数据 start*******************************************/

    /**
     * author snow
     * @param $options
     * @return mixed
     */
    static public function getItemsByTradeTypesExclude($options)
    {

        //>>修改成options 模式,参数太多.
        $default_options = [
            'user_name' => '',      //>>总代名字或者用户名称
            'include_childs' => 0,  //>>是否包含下级
            'is_test' => -1,        //>>默认不显示测试账号
            'is_manual' => -1,      //>>上分分类  .
            'status' => -1,         //>>状态
            'trade_type' => 0,      //>>支付方式
            'deposit_bank_id' => 0, //>>收款卡back_id
            'deposit_card_id' => 0, //>>收款卡id
            'start_amount' => 0,     //>>最小金额
            'end_amount' => 0,       //>>最大金额
            'order_num' => '',       //>>三方交易号
            'start_date' => '',      //>>起始时间
            'end_date' => '',        //>>结束时间
            'start' => -1,           //>>limit 的开始记录数
            'page_site' => DEFAULT_PER_PAGE,//>>默认每页显示条数
            'create_stime' => '',    //>>记录创建开始时间
            'create_etime' => '',    //>>记录创建结束时间
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $sql = 'SELECT a.*,b.username,b.level,b.is_test,b.status AS user_status ,c.username as adminname FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id LEFT JOIN admins c ON c.admin_id=a.finish_admin_id WHERE 1';

//        //>>添加排除dcsite 条件  又不过滤了.
//        $sql .= ' AND a.user_id NOT IN' . users::getUsersSqlByUserName();
        if ($default_options['user_name'] != '') {
            //>>snow 添加  如果选择了总代或用户名 查询所有数据
//            $default_options['is_test'] = -1;
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_name'] );
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '{$default_options['user_name']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_name']}'";
                }
            }
            else {
                $user_id = 0;
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($default_options['user_name'])) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        } else {
            //>>没有输入用户名的时候
            //>>排除dcsite
            $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
        }
        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }
        if ($default_options['is_manual'] == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($default_options['is_manual'] == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($default_options['status'] != -1) {
            $sql .= " AND a.status = " . intval($default_options['status']);
        }
        if ($default_options['trade_type'] != "") {
            $sql .= " AND a.trade_type in (" . $default_options['trade_type'] . ')';
        }
        if ($default_options['deposit_bank_id'] != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($default_options['deposit_bank_id']);
        }
        if ($default_options['deposit_card_id'] != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($default_options['deposit_card_id']);
        }
        if ($default_options['start_amount'] > 0) {
            $sql .= " AND a.amount >= {$default_options['start_amount']}";
        }
        if ($default_options['end_amount'] > 0) {
            $sql .= " AND a.amount <= {$default_options['end_amount']}";
        }
        if ($default_options['order_num'] != '') {
            $sql .= " AND a.order_num = '{$default_options['order_num']}'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($default_options['start_date'] !== '') {
            $sql .= " AND a.finish_time >= '{$default_options['start_date']}'";
        }
        if ($default_options['end_date'] !== '') {
            $sql .= " AND a.finish_time <= '{$default_options['end_date']}'";
        }

        if ($default_options['create_stime'] !== '') {
            $sql .= " AND a.create_time >= '{$default_options['create_stime']}'";
        }
        if ($default_options['create_etime'] !== '') {
            $sql .= " AND a.create_time <= '{$default_options['create_etime']}'";
        }

        $sql .= ' ORDER BY deposit_id DESC';
        if ($default_options['start'] > -1) {
            $sql .= " LIMIT {$default_options['start']}, {$default_options['page_site']}";
        }
//        dd($sql);
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

/******************** snow 复制代码 ,用来排除dcsite 数据 end  *******************************************/
    public static $depositSql;
    static public function snow_getDepositsAdmin( $start = -1, $amount = 20000, $where = null)
    {
        $sql = 'SELECT a.*,b.username,b.level,b.is_test,b.status AS user_status ,c.username as adminname FROM deposits a LEFT JOIN users b ON a.user_id = b.user_id LEFT JOIN admins c ON c.admin_id=a.finish_admin_id WHERE 1';

        if(!is_null($where)){
            $sql .= $where;
        }else{
            if(!empty(self::$depositSql)){
                $sql .= self::$depositSql;
            }
        }

        $sql .= ' ORDER BY deposit_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        return $GLOBALS['db']->getAll($sql);

    }

    /**
     * snow
     * 导出数据获取总条数
     * @param string $user_id
     * @param int $include_childs
     * @param int $is_test
     * @param int $is_manual
     * @param int $status
     * @param string $trade_type
     * @param int $deposit_bank_id
     * @param int $deposit_card_id
     * @param int $start_amount
     * @param int $end_amount
     * @param string $order_num
     * @param string $startDate
     * @param string $endDate
     * @param string $create_stime
     * @param string $create_etime
     * @return mixed
     */
    static public function snow_getmaxNumbers($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = "", $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $create_stime = '',$create_etime = '')
    {


        self::$depositSql = '';
        $sql = 'SELECT count(*) AS count FROM deposits a LEFT JOIN users b ON a.user_id = b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$depositSql .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$depositSql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id)) {
                        return array('count' => 0, 'total_amount' => 0);
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                self::$depositSql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            self::$depositSql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            self::$depositSql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            self::$depositSql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status !== -1) {
            self::$depositSql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != "") {
            self::$depositSql .= " AND a.trade_type in(" . $trade_type . ")";
        }
        if ($deposit_bank_id != 0) {
            self::$depositSql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            self::$depositSql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            self::$depositSql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            self::$depositSql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            self::$depositSql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            self::$depositSql .= " AND a.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            self::$depositSql .= " AND a.finish_time <= '$endDate'";
        }
        if ($create_stime !== '') {
            self::$depositSql .= " AND a.create_time >= '$create_stime'";
        }
        if ($create_etime !== '') {
            self::$depositSql .= " AND a.create_time <= '$create_etime'";
        }

        $sql .= self::$depositSql;
        return $GLOBALS['db']->getRow($sql);

    }

    /**
     * snow
     * 导出数据获取总条数 及相关信息是
     * @param string $user_id
     * @param int $include_childs
     * @param int $is_test
     * @param int $is_manual
     * @param int $status
     * @param string $trade_type
     * @param int $deposit_bank_id
     * @param int $deposit_card_id
     * @param int $start_amount
     * @param int $end_amount
     * @param string $order_num
     * @param string $startDate
     * @param string $endDate
     * @param string $create_stime
     * @param string $create_etime
     * @return mixed
     */
    static public function snow_getNewmaxNumbers($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = "", $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $create_stime = '',$create_etime = '')
    {


        self::$depositSql = '';
        $sql = 'SELECT count(*) AS count FROM deposits a LEFT JOIN users b ON a.user_id = b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$depositSql .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$depositSql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id)) {
                        return array('count' => 0, 'total_amount' => 0);
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                self::$depositSql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            self::$depositSql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            self::$depositSql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            self::$depositSql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status !== -1) {
            self::$depositSql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != "") {
            self::$depositSql .= " AND a.trade_type in(" . $trade_type . ")";
        }
        if ($deposit_bank_id != 0) {
            self::$depositSql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            self::$depositSql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            self::$depositSql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            self::$depositSql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            self::$depositSql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            self::$depositSql .= " AND a.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            self::$depositSql .= " AND a.finish_time <= '$endDate'";
        }
        if ($create_stime !== '') {
            self::$depositSql .= " AND a.create_time >= '$create_stime'";
        }
        if ($create_etime !== '') {
            self::$depositSql .= " AND a.create_time <= '$create_etime'";
        }

        $sql .= self::$depositSql;
        $result =  $GLOBALS['db']->getRow($sql);
        $result['where'] = self::$depositSql;
        return $result;

    }


    static public function getTrafficInfo($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = 0, $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $create_stime = '',$create_etime = '',$freeze = -1,$orderBy = '')
    {
        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount, sum(if(a.status=8, amount, 0)) AS total_real_amount, sum(fee) AS total_fee FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
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
                    if($freeze == -1) {
                        if (!$user = users::getItem($user_id)) {
                            return array('count' => 0, 'total_amount' => 0);
                        }
                    }else{
                        if (!$user = users::getItem($user_id,8,false,1,1)) {
                            return array('count' => 0, 'total_amount' => 0);
                        }
                    }
                    $user_id = $user['user_id'];
                }
                if($freeze == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'status' => 8
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, 8,-1,'',1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != 0) {
            $sql .= " AND a.trade_type = " . intval($trade_type);
        }
        if ($deposit_bank_id != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            $sql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            $sql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.finish_time <= '$endDate'";
        }
        if ($create_stime !== '') {
            $sql .= " AND a.create_time >= '$create_stime'";
        }
        if ($create_etime !== '') {
            $sql .= " AND a.create_time <= '$create_etime'";
        }

        if($orderBy)
        {
            $sql.=" ORDER BY ".$orderBy;
        }

        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result['total_amount'])) {
            $result['total_amount'] = 0;
        }
        if (empty($result['total_fee'])) {
            $result['total_fee'] = 0;
        }
        return $result;
    }


    /************************ snow 复制代码 排除dcsite 总代数据 start *****************************************************/


    static public function getTrafficInfoExclude($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = 0, $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $create_stime = '',$create_etime = '',$freeze = -1,$orderBy = '')
    {
        $sql = 'SELECT count(*) AS count,sum(IF(a.trade_type != 6,a.amount,0)) AS total_amount, sum(IF(a.trade_type = 6,a.amount,0)) AS promo_amount,sum(if(a.status=8, amount, 0)) AS total_real_amount, sum(fee) AS total_fee FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';

//        //>>添加排除统计优惠彩金
//        $sql .= ' AND a.trade_type != 6';
        //>>添加排除总代dcsite 相关数据
        $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName('dcsite');
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
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
                    if($freeze == -1) {
                        if (!$user = users::getItem($user_id)) {
                            return array('count' => 0, 'total_amount' => 0);
                        }
                    }else{
                        if (!$user = users::getItem($user_id,8,false,1,1)) {
                            return array('count' => 0, 'total_amount' => 0);
                        }
                    }
                    $user_id = $user['user_id'];
                }
                $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1
                    ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != 0) {
            $sql .= " AND a.trade_type = " . intval($trade_type);
        }
        if ($deposit_bank_id != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            $sql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            $sql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.finish_time <= '$endDate'";
        }
        if ($create_stime !== '') {
            $sql .= " AND a.create_time >= '$create_stime'";
        }
        if ($create_etime !== '') {
            $sql .= " AND a.create_time <= '$create_etime'";
        }

        if($orderBy)
        {
            $sql.=" ORDER BY ".$orderBy;
        }

        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result['total_amount'])) {
            $result['total_amount'] = 0;
        }
        if (empty($result['total_fee'])) {
            $result['total_fee'] = 0;
        }
        return $result;
    }


    /**
     * author snow
     * @param $options
     * @return mixed
     */
    static public function getTrafficInfoByTradeTypesExclude($options)
    {
        //>>修改成options 模式,参数太多.
        $default_options = [
            'user_name' => '',      //>>总代名字或者用户名称
            'include_childs' => 0,  //>>是否包含下级
            'is_test' => -1,        //>>默认不显示测试账号
            'is_manual' => -1,      //>>上分分类  .
            'status' => -1,         //>>状态
            'trade_type' => 0,      //>>支付方式
            'deposit_bank_id' => 0, //>>收款卡back_id
            'deposit_card_id' => 0, //>>收款卡id
            'start_amount' => 0,     //>>最小金额
            'end_amount' => 0,       //>>最大金额
            'order_num' => '',       //>>三方交易号
            'start_date' => '',      //>>起始时间
            'end_date' => '',        //>>结束时间
            'create_stime' => '',    //>>记录创建开始时间
            'create_etime' => '',    //>>记录创建结束时间
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;


        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount, sum(if(a.status=8, amount, 0)) AS total_real_amount, sum(fee) AS total_fee FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';

//        //>>添加排除总代dcsite 相关数据  又不要了
//        $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName('dcsite');
        if ($default_options['user_name'] != '') {
            //>>snow 添加,如果选择了总代或用户名 ,查询全部数据
//            $default_options['is_test'] = -1;
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_name']);
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '{$default_options['user_name']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_name']}'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($default_options['user_name'])) {
                        return array('count' => 0, 'total_amount' => 0);
                    }
                    $default_options['user_name'] = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($default_options['user_name'], true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $default_options['user_name'],
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }else {
            //>>没有输入用户名的时候
            //>>排除dcsite
            $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
        }
        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }
        if ($default_options['is_manual'] == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($default_options['is_manual'] == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($default_options['status'] !== -1) {
            $sql .= " AND a.status = " . intval($default_options['status']);
        }
        if ($default_options['trade_type'] != "") {
            $sql .= " AND a.trade_type in(" . $default_options['trade_type'] . ")";
        }
        if ($default_options['deposit_bank_id'] != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($default_options['deposit_bank_id']);
        }
        if ($default_options['deposit_card_id'] != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($default_options['deposit_card_id']);
        }
        if ($default_options['start_amount'] > 0) {
            $sql .= " AND a.amount >= {$default_options['start_amount']}";
        }
        if ($default_options['end_amount'] > 0) {
            $sql .= " AND a.amount <= {$default_options['end_amount']}";
        }
        if ($default_options['order_num'] != '') {
            $sql .= " AND a.order_num = '{$default_options['order_num']}'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($default_options['start_date'] !== '') {
            $sql .= " AND a.finish_time >= '{$default_options['start_date']}'";
        }
        if ($default_options['end_date'] !== '') {
            $sql .= " AND a.finish_time <= '{$default_options['end_date']}'";
        }
        if ($default_options['create_stime'] !== '') {
            $sql .= " AND a.create_time >= '{$default_options['create_stime']}'";
        }
        if ($default_options['create_etime'] !== '') {
            $sql .= " AND a.create_time <= '{$default_options['create_etime']}'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result['total_amount'])) {
            $result['total_amount'] = 0;
        }
        if (empty($result['total_fee'])) {
            $result['total_fee'] = 0;
        }
        return $result;
    }


    /************************ snow 复制代码 排除dcsite 总代数据 end   *****************************************************/
    static public function getTrafficInfoByTradeTypes($user_id = '', $include_childs = 0, $is_test = -1, $is_manual = -1, $status = -1, $trade_type = "", $deposit_bank_id = 0, $deposit_card_id = 0, $start_amount = 0, $end_amount = 0, $order_num = '', $startDate = '', $endDate = '', $create_stime = '',$create_etime = '')
    {
        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount, sum(if(a.status=8, amount, 0)) AS total_real_amount, sum(fee) AS total_fee FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
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
                        return array('count' => 0, 'total_amount' => 0);
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($is_manual == 0) {  //前台自动存款上分的全部修改成65534(设置成>10000的数值)
            $sql .= " AND (a.finish_admin_id >= 10000)";
        }
        if ($is_manual == 1) {  //手工存款需审核所以是后台管理员ID
            $sql .= " AND (a.finish_admin_id < 10000)";
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($trade_type != "") {
            $sql .= " AND a.trade_type in(" . $trade_type . ")";
        }
        if ($deposit_bank_id != 0) {
            $sql .= " AND a.deposit_bank_id = " . intval($deposit_bank_id);
        }
        if ($deposit_card_id != 0) {
            $sql .= " AND a.deposit_card_id = " . intval($deposit_card_id);
        }
        if ($start_amount > 0) {
            $sql .= " AND a.amount >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND a.amount <= $end_amount";
        }
        if ($order_num != '') {
            $sql .= " AND a.order_num = '$order_num'";
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.finish_time <= '$endDate'";
        }
        if ($create_stime !== '') {
            $sql .= " AND a.create_time >= '$create_stime'";
        }
        if ($create_etime !== '') {
            $sql .= " AND a.create_time <= '$create_etime'";
        }

        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result['total_amount'])) {
            $result['total_amount'] = 0;
        }
        if (empty($result['total_fee'])) {
            $result['total_fee'] = 0;
        }
        return $result;
    }


    //$type 1 查三方号 2 查商城号
    static public function getItemByOrderNum($order_num , $type = 1 ,$status = -1)
    {
        if (!isset($order_num)) {
            return array();
        }

        $field = '`order_num`';

        if($type == 2){
            $field = '`shop_order_num`';
        }
        $sql = 'SELECT * FROM deposits WHERE ' . $field . '= "' . $order_num . '"';
        if($status != -1){
            $sql .= ' AND status ='.$status;
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //首存人数与金额
    static public function getFirstDeposits($username = '', $include_childs = 0, $is_test = -1, $startDate = '', $endDate = '')
    {
        $GLOBALS['db']->query('SET SESSION group_concat_max_len=1024000; ');
        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount,CAST(GROUP_CONCAT(d.deposit_id)AS CHAR) AS first_deposit_user_id,GROUP_CONCAT(c.real_name) AS real_names  FROM (';
        $sql .= 'SELECT a.deposit_id,b.real_name FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($username != '') {
            if ($include_childs == 0) {
                $sql .= " AND b.username = '$username'";
            }
            else {
                //先得到用户id
                if (!$user = users::getItem($username)) {
                    return array('count' => 0, 'total_amount' => 0);
                }
                $sql .= " AND (b.top_id = {$user['user_id']} )";
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        $sql .= " AND a.status = 8";
        //这里会自动取出第一条值,也可以考虑使用时间 排序取第一個值的方案
        $sql .= " GROUP BY a.user_id )  c LEFT JOIN deposits d ON c.deposit_id = d.deposit_id where 1  ";

        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND d.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND d.finish_time <= '$endDate'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result['total_amount'])) {
            $result['total_amount'] = 0;
        }

        return $result;
    }


    /********************************* snow 复制代码用来排除dcsite 总代数据 start***********************************************************/


    /**
     * author snow
     * 获取首存人数与金额 排除dcsite
     * @param string $username
     * @param int $include_childs
     * @param int $is_test
     * @param string $startDate
     * @param string $endDate
     * @return mixed
     */
    static public function getFirstDepositsExclude($username = '', $include_childs = 0, $is_test = -1, $startDate = '', $endDate = '')
    {
        $GLOBALS['db']->query('SET SESSION group_concat_max_len=1024000; ');
        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount,CAST(GROUP_CONCAT(d.deposit_id)AS CHAR) AS first_deposit_user_id,GROUP_CONCAT(c.real_name) AS real_names  FROM (';
        $sql .= 'SELECT a.deposit_id,b.real_name FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';

        //>>添加排除统计优惠彩金
        $sql .= ' AND a.trade_type != 6';
        //>>添加排除总代dcsite 相关数据
        $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName('dcsite');
        if ($username != '') {
            if ($include_childs == 0) {
                $sql .= " AND b.username = '$username'";
            }
            else {
                //先得到用户id
                if (!$user = users::getItem($username)) {
                    return array('count' => 0, 'total_amount' => 0);
                }
                $sql .= " AND (b.top_id = {$user['user_id']} )";
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        $sql .= " AND a.status = 8";
        //这里会自动取出第一条值,也可以考虑使用时间 排序取第一個值的方案
        $sql .= " GROUP BY a.user_id )  c LEFT JOIN deposits d ON c.deposit_id = d.deposit_id where 1  ";

        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND d.finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND d.finish_time <= '$endDate'";
        }

        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result['total_amount'])) {
            $result['total_amount'] = 0;
        }

        return $result;
    }

    static public function getTotalInfo($user_id = '', $startDate = '', $endDate = '')
    {
        if (empty($user_id)) {
            return ['total_amount' =>0,'total_count' =>0];
        }
        //>>按区间排重
        $sql =<<<SQL
SELECT COUNT(DISTINCT(d.user_id)) AS total_count,IFNULL(SUM(d.amount),0) AS total_amount
FROM deposits AS d,users AS u
WHERE d.user_id = u.user_id AND (FIND_IN_SET({$user_id},u.parent_tree) OR u.user_id = {$user_id})
AND d.finish_time > '{$startDate}' AND d.finish_time < '{$endDate}'
AND d.status = 8
AND d.trade_type != 6 
AND u.is_test = 0 
SQL;
        //>>按日排重
//        $sql =<<<SQL
//SELECT SUM(s.num) AS total_count,SUM(s.amount) AS total_amount FROM (
//SELECT COUNT(DISTINCT(d.user_id)) AS num,IFNULL(SUM(d.amount),0) AS amount, LEFT(d.finish_time,10) AS belong_dates
//FROM deposits AS d,users AS u
//WHERE d.user_id = u.user_id AND (FIND_IN_SET({$user_id},u.parent_tree) OR u.user_id = {$user_id})
//AND d.finish_time > '{$startDate}' AND d.finish_time < '{$endDate}'
//AND d.status = 8
//AND d.trade_type != 6
//AND u.is_test = 0
//GROUP BY belong_dates) s
//SQL;
        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result['total_amount'])) {
            $result['total_amount'] = 0;
        }
        if (empty($result['total_count'])) {
            $result['total_count'] = 0;
        }
        return $result;
    }

    /********************************* snow 复制代码用来排除dcsite 总代数据 end  ***********************************************************/


    static public function wrapId($id)  //, $issue, $lottery_id
    {
//        //T130117001010028E
//        switch ($lottery_id) {
//            case '1':
//            case '2':
//                $str = substr(str_replace('-', '', $issue), 4);
//                $str = str_pad($str, 10, '0', STR_PAD_RIGHT) . str_pad($trace_id, 5, '0', STR_PAD_LEFT);
//                break;
//            default:
//                throw new exception2("Unknown rules for lottery {$lottery_id}");
//                break;
//        }
//
//        $result = "T{$lottery_id}{$str}E";
        return 'D' . encode($id) . 'E';
    }

    static public function dewrapId($str)
    {  //, $issue, $lottery_id
        if (!preg_match('`^D(\w+)E$`Ui', $str, $match)) {
            return 0;
        }
        $result = decode($match[1]);
        if (!is_numeric($result)) {
            return 0;
        }

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM deposits WHERE deposit_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'deposit_id');
    }

    //获取单个或多个用户存款总金额
    static public function getUsersDeposits($user_ids, $startDate = '', $endDate = '')
    {
        $userIdsArr = array();
        $sql = 'SELECT user_id, SUM(amount) as total_deposit FROM deposits WHERE 1';
        if (is_array($user_ids)) {
            foreach ($user_ids as &$v) {
                if (!is_numeric($v)) {
                    throw new exception2('参数无效');
                }
            }
            $sql .= ' AND user_id IN(' . implode(',', $user_ids) . ')';
            $userIdsArr = $user_ids;
        } elseif (is_numeric($user_ids)) {
            $sql .= ' AND user_id = ' . $user_ids;
            $userIdsArr[] = $user_ids;
        }

        //这里应该用finish_time，因为只有执行时用户资金才会变化
        if ($startDate !== '') {
            $sql .= ' AND finish_time >= \'' . $startDate . '\'';
        }
        if ($endDate !== '') {
            $sql .= ' AND finish_time <= \'' . $endDate . '\'';
        }
        $sql .= ' AND status = 8 GROUP BY user_id ORDER BY user_id ASC';
        $result = $GLOBALS['db']->getAll($sql, array(), 'user_id');

        foreach ($userIdsArr as &$v) {
            if (!isset($result[$v])) {
                $result[$v] = array('user_id' => $v, 'total_deposit' => 0);
            }
        }

        return $result;
    }

    //获取单用户存款数据
    static public function getUserDeposits($user_id, $startDate = '', $endDate = '')
    {
        $sql = "SELECT user_id, finish_time, amount FROM deposits WHERE user_id = $user_id";

        //这里应该用finish_time，因为只有执行时用户资金才会变化
        if ($startDate !== '') {
            $sql .= " AND finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND finish_time <= '$endDate'";
        }
        $sql .= " AND status = 8 ORDER BY deposit_id DESC";
        $result = $GLOBALS['db']->getAll($sql, array(),'');
        return $result;
    }

    //获取单用户某时间段得总充值金额
    static public function getUserSumDeposits($user_id, $startDate = '', $endDate = '')
    {
        $sql = "SELECT SUM(amount) total_deposit FROM deposits WHERE user_id = $user_id";

        //这里应该用finish_time，因为只有执行时用户资金才会变化
        if ($startDate !== '') {
            $sql .= " AND finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND finish_time <= '$endDate'";
        }
        $sql .= " AND status = 8 GROUP BY user_id";
        $res = $GLOBALS['db']->getCol($sql, 'total_deposit');

        return empty($res) ? 0 : $res[0];
    }

    //得到任意用户及其直属下级团队存款量
    /**
     * author  snow 修改会员报表,拆分充值 为存款与优惠彩金  添加充值次数
     * @param $user_id
     * @param string $startDate
     * @param string $endDate
     * @param int $freeze
     * @return array
     * @throws exception2
     */
    static public function getTeamDeposits($user_id, $startDate = '', $endDate = '',$freeze = -1)
    {
        if($freeze == -1)
        {
            // $teamUsers = users::getUserTree($user_id, true, 1, 8, -1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'level'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8
            ]);
        }else {
            // $teamUsers = users::getUserTree($user_id, true, 1, 8, -1, '', 1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'level'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
                'freeze' => 1
            ]);
        }

        if (!$teamUsers) {
            throw new exception2('参数无效');
        }

        $result = array();
        //获得直接下级
        foreach ($teamUsers as $v) {
            if ($v['parent_id'] == $user_id) {
                $result[$v['user_id']]['total_deposit']         = 0;
                $result[$v['user_id']]['total_deposit_count']   = 0;
                $result[$v['user_id']]['total_promo']           = 0;
                $result[$v['user_id']]['total_promo_count']     = 0;
            }
        }

        $self = $teamUsers[$user_id];
        $teamTotalDeposit       = $self['total_deposit']         = 0;
        $teamTotalDepositCount  = $self['total_deposit_count']   = 0;
        $teamTotalPromo         = $self['total_promo']           = 0;
        $teamTotalPromoCount    = $self['total_promo_count']     = 0;

        // if ($self['parent_id'] == 0) {
        //     $selfLevel = 0;
        // }
        // else {
        //     $selfLevel = count(explode(',', $self['parent_tree']));
        // }
        $selfLevel = $self['level'];
        $sql =<<<SQL
SELECT d.user_id,
u.parent_id,
u.parent_tree,
COUNT(IF(d.trade_type != 6,d.deposit_id,NULL )) AS total_deposit_count,
SUM(IF(d.trade_type != 6,d.amount,0)) AS total_deposit,
COUNT(IF(d.trade_type = 6,d.deposit_id,NULL )) AS total_promo_count,
SUM(IF(d.trade_type = 6,d.amount,0)) AS total_promo
FROM deposits d
LEFT JOIN users u ON d.user_id=u.user_id
WHERE u.is_test = 0 AND d.status = 8
SQL;

        if($freeze == -1) {
            $sql .= ' AND  u.status = 8';
        }else{
            $sql .= ' AND (u.status = 8 or u.status = 1 )';
        }
        if ($startDate != '') {
            $sql .= " AND d.finish_time >= '$startDate'";
        }
        if ($endDate != '') {
            $sql .= " AND d.finish_time <= '$endDate'";
        }

//        $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'u.user_id');
        /************************* author snow 实测下面的代码效率更快*******************************************/
        $sql .= " AND (FIND_IN_SET({$user_id},u.parent_tree) OR u.user_id = {$user_id})";
        $sql .= ' GROUP BY d.user_id';

        $tmp1 = $GLOBALS['db']->getAll($sql, array(),'user_id');
        if (isset($tmp1[$user_id])) {
            $teamTotalDeposit       = $self['total_deposit']        = $tmp1[$user_id]['total_deposit'];
            $teamTotalDepositCount  = $self['total_deposit_count']  = $tmp1[$user_id]['total_deposit_count'];
            $teamTotalPromo         = $self['total_promo']          = $tmp1[$user_id]['total_promo'];
            $teamTotalPromoCount    = $self['total_promo_count']    = $tmp1[$user_id]['total_promo_count'];
            unset($tmp1[$user_id]);
        }

        foreach ($tmp1 as $k => $v) {

            $teamTotalDeposit       += $v['total_deposit'];         //团队存款量
            $teamTotalDepositCount  += $v['total_deposit_count'];   //团队存款次数
            $teamTotalPromo         += $v['total_promo'];           //团队优惠量
            $teamTotalPromoCount    += $v['total_promo_count'];     //团队优惠次数
            if (isset($result[$k])) { //直接下级
                $result[$k]['total_deposit']        += $v['total_deposit'];
                $result[$k]['total_deposit_count']  += $v['total_deposit_count'];
                $result[$k]['total_promo']          += $v['total_promo'];
                $result[$k]['total_promo_count']    += $v['total_promo_count'];
            }
            else { //非直接下级
                $parent_ids = explode(',', $v['parent_tree']);
                if(isset($parent_ids[$selfLevel + 1]) && isset($result[$parent_ids[$selfLevel + 1]])) {
                    $result[$parent_ids[$selfLevel + 1]]['total_deposit']       += $v['total_deposit'];
                    $result[$parent_ids[$selfLevel + 1]]['total_deposit_count'] += $v['total_deposit_count'];
                    $result[$parent_ids[$selfLevel + 1]]['total_promo']         += $v['total_promo'];
                    $result[$parent_ids[$selfLevel + 1]]['total_promo_count']   += $v['total_promo_count'];
                }
            }
        }

        $result[$user_id]['total_deposit']          = $self['total_deposit'];
        $result[$user_id]['total_deposit_count']    = $self['total_deposit_count'];
        $result[$user_id]['total_promo']            = $self['total_promo'];
        $result[$user_id]['total_promo_count']      = $self['total_promo_count'];
        $result['team_total_deposit']               = $teamTotalDeposit;
        $result['team_total_deposit_count']         = $teamTotalDepositCount;
        $result['team_total_promo']                 = $teamTotalPromo;
        $result['team_total_promo_count']           = $teamTotalPromoCount;

        return $result;
    }

    //得到任意用户及其直属下级团队日存款量
    static public function getTeamDayDeposits($user_id, $startDate = '', $endDate = '')
    {
        // $teamUsers = users::getUserTree($user_id, true, 1, 8);
        $teamUsers = users::getUserTreeField([
            'field' => ['user_id', 'parent_id', 'level'],
            'parent_id' => $user_id,
            'recursive' => 1,
            'status' => 8
        ]);

        if (!$teamUsers) {
            throw new exception2('参数无效');
        }

        $paramArr = $days = $agent = $dayRport = array();

        $starDay = date('Y-m-d' , strtotime($startDate));
        $endDay = date('Y-m-d' , strtotime($endDate));

        $i = 0;
        do{
            $date = date('Y-m-d',strtotime($starDay) + $i * 86400);
            $days[] = $date;
            $i++;
        }while (strtotime($date) < strtotime($endDay));
        //获得直接下级
        foreach ($teamUsers as $v1) {
            if ($v1['parent_id'] == $user_id) {
                $agent[] = $v1['user_id'];
            }
        }
        $agent[] = $user_id;
        foreach ($days as $d) {
            foreach ($agent as $a) {
                $dayRport[$d][$a]['day_deposit'] = 0;
            }
            //$selfDayRport[$d]['day_deposit'] = 0;//自己的数据可以先省略需要展示再开启
        }

        $selfLevel = $teamUsers[$user_id]['level'];

        $sql = 'SELECT d.user_id,u.parent_id, u.parent_tree, d.finish_time, d.amount FROM deposits d LEFT JOIN users u ON d.user_id=u.user_id WHERE u.status = 8 AND u.is_test = 0 AND d.status = 8';
        if ($startDate != '') {
            $sql .= ' AND d.finish_time >= :startDate';
            $paramArr[':startDate'] = $startDate;
        }
        if ($endDate != '') {
            $sql .= ' AND d.finish_time <= :endDate';
            $paramArr[':endDate'] = $endDate;
        }

        $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'd.user_id');

        $tmp1 = $GLOBALS['db']->getAll($sql, $paramArr);

        $dayRport['team_total_day_deposit'] = 0;
        foreach ($days as $day) {
            foreach ($tmp1 as $v) {
                $bool = strtotime($v['finish_time']) <= strtotime($day.' 23:59:59') && strtotime($v['finish_time']) >= strtotime($day);
                if($bool){
                    $dayRport['team_total_day_deposit'] += $v['amount'];
                    if ($user_id == $v['user_id'] || in_array($v['user_id'], $agent)) {
                        //$selfDayRport[$day]['day_deposit'] += $v['amount'];//自己的数据可以先省略需要展示再开启
                        $dayRport[$day][$v['user_id']]['day_deposit'] += $v['amount'];
                    } else {
                        $parent_ids = explode(',', $v['parent_tree']);
                        $dayRport[$day][$parent_ids[$selfLevel + 1]]['day_deposit'] += $v['amount'];
                    }
                }
            }
        }

        return $dayRport;
    }

    //得到用户未决的存款提案
    static public function getUserPendingDeposit($user_id)
    {
        if (!is_numeric($user_id)) {
            throw new exception2('参数无效');
        }
        $sql = "SELECT * FROM deposits WHERE user_id = $user_id AND status < 8";
        $result = $GLOBALS['db']->getRow($sql);

        return $result;
    }

    //使用在线支付 的充值
    static public function onlinePaymentCharge($user_id, $username, $card_id, $bank_id, $amount, $player_pay_time, $order_num, $target_fee = 0, $source_fee = 0)
    {

        if (!$user = users::getItem($username)) {
            log2file("dep3rd.log", "无效的用户名：" . $username);
            throw new exception2('无效的用户名');
        }
        if (!$card_id || !$bank_id || !$order_num) {
            log2file("dep3rd.log", "无效的输入信息：" . $card_id . "," . $bank_id . "," . $order_num);
            throw new exception2('无效的输入信息');
        }

        if (!preg_match("`^(\d{4})-(\d{2})-(\d{2}) \d{2}:\d{2}:\d{2}$`", $player_pay_time)) {
            log2file("dep3rd.log", "充值时间不正确：" . $player_pay_time);
            throw new exception2('充值时间不正确');
        }

        if (!preg_match("`^\d+(\.\d{1,2})?$`", $amount)) {
            log2file("dep3rd.log", "金额不正确：" . $amount);
            throw new exception2('金额不正确');
        }
        //检查是否有这条充值记录了
        if ($deposit = deposits::getItem($order_num)) {
            log2file("dep3rd.log", "已经有这条记录，不能重复充值：" . var_export($deposit, true));
            throw new exception2("重复的订单号{$order_num}");
        }

        //增加存款记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'player_pay_time' => $player_pay_time,
            'player_card_name' => '',
            'amount' => $amount,
            'fee' => $source_fee,
            'target_fee' => $target_fee,
            'order_num' => $order_num,
            'trade_type' => '1',
            'deposit_bank_id' => $bank_id,
            'deposit_card_id' => $card_id,
            'create_time' => $player_pay_time,
            'status' => 0, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
            'finish_time' => $player_pay_time,
            'remark' => $GLOBALS['REQUEST']['client_ip'],
        );
        if (!deposits::addItem($data)) {
            return false;
            throw new exception2('增加记录失败');
        }
        $deposit_id = $GLOBALS['db']->insert_id();
        //JYZ-417 65534 认为是自动的,当手工处理时，一条新记录是 0 ， 审核过后修改为管理员ID
        return deposits::charge($deposit_id, 65534);
    }

    static public function applyICBCDeposit($user_id, $deposit_amount, $postscript)
    {
        if ($deposit_amount < 100) {
            //for debug
            //return "金额不足100，无法提交";
        }

        if (!$user = users::getItem($user_id)) {
            return "该用户不存在或已被冻结";
        }

        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'agent_id' => $user['agent_id'],
            'player_card_name' => '',
            'player_pay_time' => '',
            'amount' => $deposit_amount,
            'fee' => -1,
            'order_num' => '',
            'postscript' => $postscript,
            'trade_type' => 1,
            'deposit_bank_id' => 0,
            'deposit_card_id' => 0,
            'create_time' => date('Y-m-d H:i:s'), //提案建立时间
            'status' => 0, //未处理
            'finish_time' => date('Y-m-d H:i:s'),
        );
        if (!deposits::addItem($data)) {
            return '添加数据失败';
        }

        return true;
    }

    //玩家添加待定的存款提案  不写帐变不加余额，这些在执行的时候完成
    static public function applyDeposit($user_id, $player_bank_id, $player_card_name, $deposit_time, $deposit_amount, $deposit_trade_type, $accept_card_id, $order_num)
    {
        //数据正确性判断
        if (!$player_bank_id) {
            return "没有指定汇款卡";
        }
        if (!$accept_card_id) {
            return "没有指定收款卡";
        }
        if (!$player_card_name) {
            return "请输入汇款人户名";
        }

        $config = config::getConfigs(array('min_deposit_limit', 'max_deposit_limit'));
        if ($deposit_amount < $config['min_deposit_limit']) {
            return "金额低于最低充值限额{$config['min_deposit_limit']}，无法提交";
        }
        if ($deposit_amount > $config['max_deposit_limit']) {
            return "金额超过最高充值限额{$config['max_deposit_limit']}，无法提交";
        }

        if (!$card = cards::getItem($accept_card_id, 1, NULL)) {
            return "收款卡不存在";
        }
        if (!$user = users::getItem($user_id)) {
            return "该用户不存在或已被冻结";
        }

        //如果之前有提交的申请，防止用户恶意重复提交
        if (deposits::getUserPendingDeposit($user_id)) {
            return "您上笔存款正在处理，请等待上一笔存款完成";
        }

        $fee = -1;
        if ($deposit_trade_type == 5) { //ATM无卡现存是确定没花手续费的，网转的也是确定的，在autoCharge()里面更新，其他方式的暂不确定为-1
            $fee = 0;
        }

        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'player_bank_id' => $player_bank_id,
            'player_card_name' => $player_card_name,
            'player_pay_time' => $deposit_time,
            'amount' => $deposit_amount,
            'fee' => $fee,
            'order_num' => $order_num,
            'trade_type' => $deposit_trade_type,
            'deposit_bank_id' => $card['bank_id'],
            'deposit_card_id' => $card['card_id'],
            'create_time' => date('Y-m-d H:i:s'),
            'status' => 0, //未处理
            'finish_time' => date('Y-m-d H:i:s'),
        );

        if (!deposits::addItem($data)) {
            return '添加数据失败';
        }

        return true;
    }

    static public function onlinePaymentCharge2($user_id, $username, $card_id, $bank_id, $amount, $player_pay_time, $order_num,$shop_order_num, $target_fee = 0, $source_fee = 0, $logName = 'requestError.log')
    {

        if (!$user = users::getItem($username)) {
            log2file($logName, "无效的用户名：" . $username);
            throw new exception2('无效的用户名');
        }
        if (!$card_id || !$bank_id || !$order_num || !$shop_order_num) {
            log2file($logName, "无效的输入信息：" . $card_id . "," . $bank_id . "," . $order_num. "," . $shop_order_num);
            throw new exception2('无效的输入信息');
        }

        if (!preg_match("`^(\d{4})-(\d{2})-(\d{2}) \d{2}:\d{2}:\d{2}$`", $player_pay_time)) {
            log2file($logName, "充值时间不正确:" . $player_pay_time ." 用户:".$user_id);
            throw new exception2('充值时间不正确');
        }

        if (!preg_match("`^\d+(\.\d{1,2})?$`", $amount)) {
            log2file($logName, "金额不正确：" . $amount);
            throw new exception2('金额不正确');
        }
        //检查是否有这条充值记录了
        if (!$deposit = deposits::getItemByOrderNum($shop_order_num,2)) {
            log2file($logName, "用户ID：".$user_id."没有这条记录订单号：".$shop_order_num."，不能充值：");
            throw new exception2("用户ID：".$user_id."没有该订单号{$shop_order_num}");
        }

        $random_benefit = config::getConfig('random_benefit');

        if($deposit['amount'] - $amount > $random_benefit){
            throw new exception2('用户ID：'.$user_id.'充值金额异常');
        }

        if ($deposit['shop_order_num'] != $shop_order_num) {
            log2file($logName, "用户{$user_id}商城号不一致，原有：{$deposit['shop_order_num']}，回调：{$shop_order_num}");
            throw new exception2('商城交易号不一致');
        }
        if ($deposit['status'] > 3) {
            log2file($logName, "提案状态不是“未处理”，用户{$user_id}提案{$deposit['deposit_id']}");
            throw new exception2('提案状态不是“未处理”，拒绝执行');
        }
        if ($deposit['user_id'] != $user_id || $deposit['username'] != $username) {
            log2file($logName, "提案人与充值人不符，提案用户{$deposit['user_id']},{$deposit['username']};回调用户{$user_id},{$username}");
            throw new exception2('提案人与充值人不符，拒绝执行');
        }

        //开始事务
        $GLOBALS['db']->startTransaction();


        //修改存款记录为成功
        $data = array(
            'fee' => $source_fee,
            'target_fee' => $target_fee,
            'order_num' => $order_num,
            'status' => 8, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
            'create_time' => $player_pay_time,
            'remark' => $GLOBALS['REQUEST']['client_ip']
        );

        if(!$GLOBALS['db']->updateSM('deposits', $data, array('shop_order_num' => $shop_order_num,'user_id' => $user_id))) {
            $GLOBALS['db']->rollback();
            return false;
        }

        //65534表示自动的
        if (($flag = deposits::_charge($deposit, 65534)) !== true) {
            $GLOBALS['db']->rollback();
            return $flag;
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    // 手工为客户充游戏币
    static public function charge($deposit_id, $admin_id)
    {
        if (!$deposit = deposits::getItem($deposit_id)) {
            throw new exception2('找不到提案');
        }
        if (!($deposit['status'] <= 3)) {
            throw new exception2('提案状态不是“未处理”，拒绝执行');
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        if (($flag = deposits::_charge($deposit, $admin_id)) !== true) {
            $GLOBALS['db']->rollback();
            return $flag;
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    //充游戏币业务逻辑 没有事务
    static private function _charge($deposit, $admin_id, $ref_auto_id = 0)
    {

        if ($deposit['amount'] <= 0) {
            throw new exception2('充值金额为负数，出错了');
        }
        //暂不检查无卡现存必须为整数，因为建行是先行扣掉再汇过来的
//        if ($deposit['trade_type'] == 5 && $deposit['amount'] % 100 != 0) {
//            throw new exception2('ATM无卡现存必须为100的整数');
//        }

        if (!$user = users::getItem($deposit['user_id'])) {
            throw new exception2('该用户不存在或已被禁用');
        }
        if (!$card = cards::getItem($deposit['deposit_card_id'], 1, NULL)) {
            throw new exception2('该收款卡不存在');
        }

        // $promoFee = config::getConfig('promo_fee'); // 三方充值优惠
        $promoFee = (new cardDepositGroup())->getFieldByCdgId($deposit['trade_type'], 'fee_rate');
        $promoAmount = $cardReceiveFee = 0;

        if($promoFee > 0 && $deposit['trade_type'] != 6){//优惠彩金的充值方式不参与充值优惠活动
            $promoAmount = $deposit['amount'] * $promoFee;

            $promo_data = array(
                'user_id' => $user['user_id'],
                'top_id' => $user['top_id'],
                'type' => 6, //'活动红包'
                'win_lose' => 0,
                'amount' => $promoAmount,
                'create_time' => date('Y-m-d H:i:s'),
                'notes' => 102,
                'status' => 8, //已执行
                'admin_id' => 0, //自动执行 无admin所以ID = 0
                'verify_admin_id' => 0,
                'verify_time' => date('Y-m-d H:i:s'),
                'finish_admin_id' => 0,
                'finish_time' => date('Y-m-d H:i:s'),
                'remark' => '自动执行',
            );
            if (!promos::addItem($promo_data)) {
                return -1001;
            }
            //1.增加充值优惠帐变
            $orderData = array(
                'lottery_id' => 0,
                'issue' => '',
                'from_user_id' => $deposit['user_id'],
                'from_username' => $deposit['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => 102, //帐变类型 101充值 102充值优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                'amount' => $promoAmount,
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] + $promoAmount,
                'create_time' => date('Y-m-d H:i:s'),
                'business_id' => $deposit['deposit_id'],
                'admin_id' => $admin_id,
            );

            if (!orders::addItem($orderData)) {
                return -1100;
            }
        }

        //1.增加用户帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $deposit['user_id'],
            'from_username' => $deposit['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 101, //帐变类型 101充值 102充值优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => $deposit['amount'],
            'pre_balance' => $user['balance'] + $promoAmount,
            'balance' => $user['balance'] + $deposit['amount'] + $promoAmount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $deposit['deposit_id'],
            'admin_id' => $admin_id,
        );

        if (!orders::addItem($orderData)) {
            return -1101;
        }

        //对于手工存款：加帐变,更新收款卡余额
        //2.增加银行卡帐变
        $cardOrderData = array(
            'from_card_type' => 1, //源卡类型 1收款 2付款 3备用金
            'from_bank_id' => $card['bank_id'],
            'from_card_id' => $card['card_id'],
            'to_card_type' => 0, //源卡类型 1收款 2付款 3备用金
            'to_bank_id' => 0,
            'to_card_id' => 0,
            'order_type' => 1, //1收款 2付款 3内转入 4内转出 每次内转将产生2条卡帐变
            'amount' => $deposit['amount'],
            'my_fee' => -$cardReceiveFee,
            'pre_balance' => $card['balance'],
            'balance' => $card['balance'] + $deposit['amount'] + (-$cardReceiveFee),
            'create_time' => date('Y-m-d H:i:s'),
            'ref_auto_id' => $ref_auto_id,
            'ref_id' => $deposit['deposit_id'], //相关id，对于存提款为其提案id，对于内转由于没有专门表记录，所以为0
            'ref_user_id' => $deposit['user_id'], //相关用户id，对于存提款为当事用户id，对于内转为0
            'ref_username' => $deposit['username'], //相关用户名，对于存提款为当事用户名，对于内转为空
            'admin_id' => $admin_id,
            'remark' => '',
        );

        if (!cardOrders::addItem($cardOrderData)) {
            return -1102;
        }
        //3.更新收款卡余额
        if (!cards::updateBalance($deposit['deposit_card_id'], $deposit['amount'] - $cardReceiveFee - $promoAmount)) {
            return -1103;
        }

        $remark = '';

        //7.为客户加游戏币并且充值次数增加一次
        if (!users::updateBalance($deposit['user_id'], $deposit['amount'] + $promoAmount, true)) {
            return -1105;
        }


        //8---充值成功后,如果是开启充满200 上级送钱活动就展开
        //如果是总代充值的不用送钱了吧? 所以这里判断要有上级
        $config = config::getConfigs(array('deposit_gift_open', 'deposit_gift_limit', 'deposit_parent_gift', 'deposit_grandfather_gift'));
        $deposit_gift_limit = explode(',', $config['deposit_gift_limit']);
        $deposit_parent_gift = explode(',', $config['deposit_parent_gift']);
        $deposit_grandfather_gift = explode(',', $config['deposit_grandfather_gift']);
        //140403 增加条件 首次充值的才送
        //140413 修改条件 每天仅送一次，但只给直接上级返10元，上上级不再返利
        if ($user['is_test'] != 0 && $user['parent_id'] > 0 && $config['deposit_gift_open'] == 1 && $deposit['amount'] >= $deposit_gift_limit[0]) {
            //这里使用的是直接到账变,并不是增加到优惠列表去
            //查找当天 有没有充过 设定金额  以上的记录
            $usedAmount = 0;
            $maxAmount = 68;
            //取得上级用户名 -- 必须要取得数据，才知道是否有上上级
            $parent_user = users::getItem($user['parent_id'], -1);
            //把每笔反佣加起来
            if ($userDeposits = deposits::getItems($user['user_id'], 0, -1, -1, 8, 0, 0, 0, $deposit_gift_limit[0], 0, '', date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59"))) {
                foreach ($userDeposits as $userDeposit) {
                    $temp_gift = 0;
                    for ($i = 0; $i < count($deposit_gift_limit); $i++) {
                        if ($deposit_gift_limit[$i] <= intval($userDeposit['amount'])) {
                            $temp_gift = $deposit_parent_gift[$i];
                        }
                    }
                    $usedAmount += $temp_gift;
                }
            }

            //如果总量超过了，不返
            if ($usedAmount < $maxAmount) {
                $parent_gift = 0;
                for ($i = 0; $i < count($deposit_gift_limit); $i++) {
                    if ($deposit_gift_limit[$i] <= intval($deposit['amount'])) {
                        $parent_gift = $deposit_parent_gift[$i];
                    }
                }
                //如果总量超过了，调整
                if (($parent_gift + $usedAmount) > $maxAmount) {
                    $parent_gift = $maxAmount - $usedAmount;
                }
                if ($parent_user['status'] == 8) {
                    $promo_data = array(
                        'user_id' => $parent_user['user_id'],
                        'top_id' => $parent_user['top_id'],
                        'type' => 5, //'下级存款反佣'
                        'win_lose' => 0,
                        'amount' => $parent_gift,
                        'create_time' => date('Y-m-d H:i:s'),
                        'notes' => '下级存款反佣',
                        'status' => 8, //已执行
                        'admin_id' => 0, //自动执行 无admin所以ID = 0
                        'verify_admin_id' => 0,
                        'verify_time' => date('Y-m-d H:i:s'),
                        'finish_admin_id' => 0,
                        'finish_time' => date('Y-m-d H:i:s'),
                        'remark' => '自动执行',
                    );
                    if (!promos::addItem($promo_data)) {
                        return -1117;
                    }
                    $promo_id = $GLOBALS['db']->insert_id();

                    //增加到账变表里
                    $parentOrderData = array(
                        'lottery_id' => 0,
                        'issue' => '',
                        'from_user_id' => $parent_user['user_id'],
                        'from_username' => $parent_user['username'],
                        'to_user_id' => 0,
                        'to_username' => '',
                        'type' => 152, //帐变类型152
                        'amount' => $parent_gift,
                        'pre_balance' => $parent_user['balance'],
                        'balance' => $parent_user['balance'] + $parent_gift,
                        'create_time' => date('Y-m-d H:i:s'),
                        'business_id' => $promo_id,
                        'admin_id' => $admin_id,
                    );

                    if (!orders::addItem($parentOrderData)) {
                        return -1106;
                    }
                    if (!users::updateBalance($parent_user['user_id'], $parent_gift)) {
                        return -1107;
                    }
                }
                //如果设置上上级也返，则处理上上级
                if ($deposit_grandfather_gift[0] > 0 && $parent_user['parent_id'] > 0) {
                    // 直接查找状态为正常8 的用户
                    if ($grandfather_user = users::getItem($parent_user['parent_id'])) {
                        $grandfather_gift = 0;
                        for ($i = 0; $i < count($deposit_gift_limit); $i++) {
                            if ($deposit_gift_limit[$i] <= intval($deposit['amount'])) {
                                $grandfather_gift = $deposit_grandfather_gift[$i];
                            }
                        }

                        $promo_data = array(
                            'user_id' => $grandfather_user['user_id'],
                            'top_id' => $grandfather_user['top_id'],
                            'type' => 5, //'下级存款反佣'
                            'win_lose' => 0,
                            'amount' => $grandfather_gift,
                            'create_time' => date('Y-m-d H:i:s'),
                            'notes' => '下级存款反佣',
                            'status' => 8, //已执行
                            'admin_id' => 0, //自动执行 无admin所以ID = 0
                            'verify_admin_id' => 0,
                            'verify_time' => date('Y-m-d H:i:s'),
                            'finish_admin_id' => 0,
                            'finish_time' => date('Y-m-d H:i:s'),
                            'remark' => '自动执行',
                        );
                        if (!promos::addItem($promo_data)) {
                            return -1117;
                        }
                        $promo_id = $GLOBALS['db']->insert_id();

                        //增加到账变表里
                        $grandfatherOrderData = array(
                            'lottery_id' => 0,
                            'issue' => '',
                            'from_user_id' => $grandfather_user['user_id'],
                            'from_username' => $grandfather_user['username'],
                            'to_user_id' => 0,
                            'to_username' => '',
                            'type' => 152, //帐变类型152
                            'amount' => $grandfather_gift,
                            'pre_balance' => $grandfather_user['balance'],
                            'balance' => $grandfather_user['balance'] + $grandfather_gift,
                            'create_time' => date('Y-m-d H:i:s'),
                            'business_id' => $promo_id,
                            'admin_id' => $admin_id,
                        );

                        if (!orders::addItem($grandfatherOrderData)) {
                            return -1108;
                        }
                        if (!users::updateBalance($grandfather_user['user_id'], $grandfather_gift)) {
                            return -1109;
                        }
                    }
                }
            }
        }
        //9.关于记录存款卡流水帐，可以从yj后台查到，所以这里不需要记录，当然，硬要记也行
        //11.最后更新存款提案状态为“已成功”
        $depositData = array('finish_admin_id' => $admin_id, 'status' => 8, 'finish_time' => date('Y-m-d H:i:s'));
        if ($remark) {
            $depositData['remark'] = $remark;
        }

        if (!deposits::updateItem($deposit['deposit_id'], $depositData)) {
            return -1110;
        }

        return true;
    }

    //采用附言后，直接按传过来的用户名充值，流程更简单
    static public function autoCharge($autoData)
    {
        if (!$autoData || empty($autoData['auto_id']) || empty($autoData['ref_user'])) {
            throw new exception2("参数无效3");
        }
        if (!$user = users::getItem($autoData['ref_user'], -1)) {
            throw new exception2("不存在的会员({$autoData['ref_user']})");
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        //1.先加一条存款记录
        $deposit = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'player_bank_id' => $autoData['player_bank_id'],
            'player_card_name' => $autoData['player_card_name'],
            'player_pay_time' => $autoData['player_pay_time'],
            'amount' => $autoData['amount'],
            'fee' => $autoData['fee'],
            'order_num' => $autoData['order_num'],
            'trade_type' => 1, //1网转 2ATM有卡转账,自助终端转账 4手机网转 5ATM无卡现存 6柜台汇款 7跨行汇款
            'deposit_bank_id' => $autoData['bank_id'],
            'deposit_card_id' => $autoData['card_id'],
            'create_time' => date('Y-m-d H:i:s'),
            'status' => 3, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9因故取消
            'finish_admin_id' => 0,
            'finish_time' => date('Y-m-d H:i:s'),
            'ref_auto_id' => $autoData['auto_id'],
            'remark' => isset($autoData['remark']) ? $autoData['remark'] : "由{$GLOBALS['SESSION']['admin_username']}于".date('Y-m-d H:i:s')."添加"
        );

        if (!$deposit['order_num']) {
            switch ($deposit['deposit_bank_id']) {
                case '102'://财付通
                    $deposit['order_num'] = 'TP' . date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                    break;
                default:
                    $deposit['order_num'] = 'XX' . date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                    break;
            }
        }

        deposits::addItem($deposit);
        $deposit_id = $GLOBALS['db']->insert_id();
        $deposit['deposit_id'] = $deposit_id;

        //2.执行充值逻辑
        if (!deposits::_charge($deposit, self::MACHINE_ID, $autoData['auto_id'])) {
            $GLOBALS['db']->rollback();
            throw new exception2("充值业务失败(auto_id={$autoData['auto_id']},deposit_id={$deposit['deposit_id']})", 500);   //应退出，因为表明数据库某个表出了问题
        }

        //3.更改为已充值
        if (!autoDeposits::updateItem($autoData['auto_id'], array('status' => 1), array('status' => 0))) {
            $GLOBALS['db']->rollback();
            throw new exception2("更改待定充值记录状态失败(auto_id={$autoData['auto_id']})", 500);   //应退出，因为表明auto_deposits表数据出了异常
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            $GLOBALS['db']->rollback();
            throw new exception2("提交事务失败(auto_id={$autoData['auto_id']})", 500);
        }

        return $deposit['deposit_id'];
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }
        //order_num添加唯一索引，所以为空时改为null值
        if (isset($data['order_num']) && !$data['order_num']) {
            unset($data['order_num']);
        }

        return $GLOBALS['db']->insert('deposits', $data);
    }

    static public function updateItem($id, $data, $addonsConditions = array())
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        if (!$GLOBALS['db']->updateSM('deposits' ,$data , array_merge($addonsConditions , array('deposit_id' => $id)))) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    // 暂不允许删除
    static public function deleteItem($id, $realDelete = false)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        if ($realDelete) {
            $sql = "DELETE FROM deposits WHERE deposit_id = " . intval($id);
            $type = 'd';
        }
        else {
            $sql = "UPDATE deposits SET status = -1 WHERE deposit_id = " . intval($id);
            $type = 'u';
        }

        return $GLOBALS['db']->query($sql, array(), $type);
    }

    /**
     * 获取指定时间内的充值总量和最早的时间
     * @param $uid
     * @param $date
     * @param bool $flag
     * @return mixed
     */
    static public function getRechangeBydate($uid,$date,$flag=true){
        if( $flag ){
            $date = ''?'':' AND a.finish_time >= "\'.$date.\'"';
            $fTime = 'min(a.finish_time)';
        }else{
            $date = ''?'':' AND a.finish_time < "\'.$date.\'"';
            $fTime = 'max(a.finish_time)';
        }
        $sql = 'SELECT sum(a.amount) AS total_amount,'.$fTime.' AS finish_time FROM deposits a LEFT JOIN users b ON a.user_id=b.user_id WHERE  b.user_id = '
            .$uid.' AND a.status = 8'.$date;
        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * author  snow  获取实时团队 首存金额与首存人数
     * 不查询优惠彩金
     * @return array
     */
    public static function getTeamFirstDeposit()
    {
        //>>开始时间
        $start = date('Y-m-d 00:00:00');
        //>>当前时间
        $end   = date('Y-m-d H:i:s');
        //>>设计查询sql  只为获取实时数据,条件定死
        $sql   =<<<SQL
SELECT a.top_id,a.trade_type,COUNT(DISTINCT(a.user_id)) AS first_deposit_num,SUM(a.amount) AS first_deposit_amount FROM deposits a 
LEFT JOIN users b ON a.user_id=b.user_id 
WHERE 1 
AND a.trade_type != 6 
AND b.is_test = 0 
AND a.status = 8 
AND a.finish_time >= '{$start}' 
AND a.finish_time <= '{$end}'
GROUP BY a.top_id,a.trade_type
SQL;
        return $GLOBALS['db']->getAll($sql,[],'top_id');

    }
}




