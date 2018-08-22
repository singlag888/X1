<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 提款记录模型
 */
class withdraws
{

    const MACHINE_ID = 65535; //机器处理的admin_id为65535

    static public function getItem($id, $status = NULL)
    {
        $sql = 'SELECT * FROM withdraws WHERE withdraw_id = ' . intval($id);
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }
	
    static public function getItems($user_id = '', $include_childs = 0, $is_test = -1, $status = -1, $pay_bank_id = 0, $pay_card_id = 0, $bank_id = 0, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE, $create_stime = '', $create_etime = '',$min_amount='',$max_amount='',$freeze = -1,$orderBy= '')
    {
        $sql = 'SELECT a.*,b.level,b.is_test,b.status AS user_status FROM withdraws a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        //尽量用user_id为优
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND a.user_id = '$user_id'";
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
                        'status' => 8,
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
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= ' AND b.is_test = ' . intval($is_test);
        }
        if(is_array($status)){
            $sql .= ' AND a.status IN(' . implode(',', $status) . ')';
        }
        elseif ($status !== -1) {
            $sql .= ' AND a.status = ' . intval($status);
        }
        if ($pay_bank_id != 0) {
            $sql .= ' AND a.pay_bank_id = ' . intval($pay_bank_id);
        }
        if ($pay_card_id != 0) {
            $sql .= ' AND a.pay_card_id = ' . intval($pay_card_id);
        }
        if ($bank_id != 0) {
            $sql .= ' AND a.bank_id = ' . intval($bank_id);
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        /************************** 为了与列表统计统一* 修改为提案发起时间  *****************************************/
        if ($startDate !== '') {
            $sql .= " AND a.finish_time >= '$create_stime'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.finish_time <= '$create_etime'";
        }

        if ($create_stime !== '') {
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($create_etime !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }
        /************************** 为了与列表统计统一* 修改为提案发起时间  *****************************************/
        if($min_amount!=='' && $max_amount!=='' && is_numeric($min_amount) && is_numeric($max_amount)){
            $sql .= " AND a.amount <= {$max_amount} AND a.amount >= {$min_amount}";
        }elseif($min_amount!=='' && is_numeric($min_amount)){
            $sql .= " AND a.amount >= {$min_amount}";
        }elseif($max_amount!=='' && is_numeric($max_amount)){
            $sql .= " AND a.amount <= {$max_amount}";
        }
        if($orderBy)
        {
            $sql.=' ORDER BY '.$orderBy;
        }else {
            $sql .= ' ORDER BY a.withdraw_id DESC';
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /************************ snow  复制一个方法,用来排除dcsite 总代下的所有数据 start************************************/
    /**
     * author snow
     * @param $options
     * @return mixed
     */
    static public function getTrafficInfoLExclude($options)
    {
        $default_options = [
            'user_name'         => '',  //>>总代名字或者用户名称
            'include_childs'    => 0,   //>>是否包含下级
            'is_test'           => -1,  //>>默认不显示测试账号
            'status'            => -1,  //>>状态
            'pay_bank_id'       => 0,   //>>支付卡bank_id
            'pay_card_id'       => 0,   //>>支付卡id
            'bank_id'           => 0,   //>>
            'start_date'        => '',  //>>起始时间
            'end_date'          => '',  //>>结束时间
            'start'             => -1,  //>>limit 的开始记录数
            'amount'            => DEFAULT_PER_PAGE,//>>默认每页显示条数
            'create_stime'      => '',    //>>记录创建开始时间
            'create_etime'      => '',    //>>记录创建结束时间
            'start_amount'      => '',  //>>最小金额
            'end_amount'        => '',  //>>最大金额
            'freeze'            => -1,  //>>是否统计冻结账号
            'orderBy'           => '',
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;

        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount,sum(fee) AS total_fee FROM withdraws a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';

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
                    if($default_options['freeze'] == -1) {
                        if (!$user = users::getItem($default_options['user_name'])) {
                            return array('count' => 0, 'total_amount' => 0, 'total_fee' => 0);
                        }
                    }else{
                        if (!$user = users::getItem($default_options['user_name'],8,false,1,-1)) {
                            return array('count' => 0, 'total_amount' => 0, 'total_fee' => 0);
                        }
                    }
                    $default_options['user_name'] = $user['user_id'];
                }
                if($default_options['freeze'] == -1) {
                    // $teamUsers = users::getUserTree($default_options['user_name'], true, 1, 8);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_name'],
                        'recursive' => 1,
                        'status' => 8,
                    ]);
                }else{
                    // 又是一个BUG 不管参数是啥 freeze 始终传的 -1,真的扯把子
                    // $teamUsers = users::getUserTree($default_options['user_name'], true, 1, 8,-1,'',-1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_name'],
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        } else {
            //>>没有输入用户名的时候
            //>>排除dcsite
            $sql .= ' AND b.top_id != IF((SELECT user_id FROM users WHERE username = \'dcsite\'),(SELECT user_id FROM users WHERE username = \'dcsite\'), -1) AND b.status = 8';
        }
        if ($default_options['is_test'] != -1) {
            $sql .= " AND b.is_test = " . intval($default_options['is_test']);
        }

        if($default_options['status'] !== -1){
            if(is_array($default_options['status'])){
                $sql .= " AND a.status IN(" . implode(',', $default_options['status']) . ")";
            } else {
                $sql .= " AND a.status = " . intval($default_options['status']);
            }
        }

        if ($default_options['pay_bank_id'] != 0) {
            $sql .= " AND a.pay_bank_id = " . intval($default_options['pay_bank_id'] );
        }
        if ($default_options['pay_card_id'] != 0) {
            $sql .= " AND a.pay_card_id = " . intval($default_options['pay_card_id']);
        }
        if ($default_options['bank_id'] != 0) {
            $sql .= " AND a.bank_id = " . intval($default_options['bank_id']);
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
        if ($default_options['start_amount'] !== ''){
            $sql .= " AND a.amount >= '{$default_options['start_amount']}'";
        }
        if ($default_options['end_amount'] !== ''){
            $sql .= " AND a.amount <= '{$default_options['end_amount']}'";
        }
        if($default_options['orderBy'])
        {
            $sql .=' ORDER BY '.$default_options['orderBy'];
        }
        $result = $GLOBALS['db']->getRow($sql);
        if (!$result['total_amount']) {
            $result['total_amount'] = 0;
        }
        if (!$result['total_fee']) {
            $result['total_fee'] = 0;
        }

        return $result;
    }

    /**
     * author snow
     * @param $options
     * @return mixed
     */
    static public function getItemsExclude($options)
    {


        $default_options = [
            'user_name'         => '',  //>>总代名字或者用户名称
            'include_childs'    => 0,   //>>是否包含下级
            'is_test'           => -1,  //>>默认不显示测试账号
            'status'            => -1,  //>>状态
            'pay_bank_id'       => 0,   //>>支付卡bank_id
            'pay_card_id'       => 0,   //>>支付卡id
            'bank_id'           => 0,   //>>
            'start_date'        => '',  //>>起始时间
            'end_date'          => '',  //>>结束时间
            'start'             => -1,  //>>limit 的开始记录数
            'amount'            => DEFAULT_PER_PAGE,//>>默认每页显示条数
            'create_stime'      => '',    //>>记录创建开始时间
            'create_etime'      => '',    //>>记录创建结束时间
            'start_amount'      => '',  //>>最小金额
            'end_amount'        => '',  //>>最大金额
            'freeze'            => -1,
            'orderBy'           => '',
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $sql = 'SELECT a.*,b.level,b.is_test,b.status AS user_status FROM withdraws a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
//        //>>添加去除相应id的代码
        //尽量用user_id为优
        if ($default_options['user_name'] != '') {
            //>>snow 添加,如果选择了总代或用户名 ,查询全部数据
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_name']);
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    $sql .= " AND a.user_id = '{$default_options['user_name']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_name']}'";
                }
            }
            else {
                if (!$tmp) {
                    if($default_options['freeze'] == -1) {
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_name'])) {
                            return array();
                        }
                    }else{
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_name'],8,false,1,1)) {
                            return array();
                        }
                    }
                    $default_options['user_name'] = $user['user_id'];
                }
                if($default_options['freeze'] == -1) {
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_name'],
                        'recursive' => 1,
                        'status' => 8,
                    ]);
                }else{
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_name'],
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        } else {
            $sql .= ' AND  b.top_id != IF((SELECT user_id FROM users WHERE username = \'dcsite\'),(SELECT user_id FROM users WHERE username = \'dcsite\'), -1) AND b.status = 8';
        }

        if ($default_options['is_test'] != -1) {
            $sql .= ' AND b.is_test = ' . intval($default_options['is_test']);
        }
        if(is_array($default_options['status'])){
            $sql .= ' AND a.status IN(' . implode(',', $default_options['status']) . ')';
        }
        elseif ($default_options['status'] !== -1) {
            $sql .= ' AND a.status = ' . intval($default_options['status']);
        }
        if ($default_options['pay_bank_id'] != 0) {
            $sql .= ' AND a.pay_bank_id = ' . intval($default_options['pay_bank_id']);
        }
        if ($default_options['pay_card_id'] != 0) {
            $sql .= ' AND a.pay_card_id = ' . intval($default_options['pay_card_id']);
        }
        if ($default_options['bank_id'] != 0) {
            $sql .= ' AND a.bank_id = ' . intval($default_options['bank_id']);
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
        if($default_options['start_amount']!=='' && $default_options['end_amount']!=='' && is_numeric($default_options['start_amount']) && is_numeric($default_options['end_amount'])){
            $sql .= " AND a.amount <= {$default_options['end_amount']} AND a.amount >= {$default_options['start_amount']}";
        }elseif($default_options['start_amount']!=='' && is_numeric($default_options['start_amount'])){
            $sql .= " AND a.amount >= {$default_options['start_amount']}";
        }elseif($default_options['end_amount']!=='' && is_numeric($default_options['end_amount'])){
            $sql .= " AND a.amount <= {$default_options['end_amount']}";
        }
        if($default_options['orderBy'])
        {
            $sql.=' ORDER BY '.$default_options['orderBy'];
        }else {
            $sql .= ' ORDER BY a.withdraw_id DESC';
        }
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
    static public function getItemsExcludeStatistics($options)
    {


        $default_options = [
            'user_name'         => '',  //>>总代名字或者用户名称
            'include_childs'    => 0,   //>>是否包含下级
            'is_test'           => -1,  //>>默认不显示测试账号
            'status'            => -1,  //>>状态
            'pay_bank_id'       => 0,   //>>支付卡bank_id
            'pay_card_id'       => 0,   //>>支付卡id
            'bank_id'           => 0,   //>>
            'start_date'        => '',  //>>起始时间
            'end_date'          => '',  //>>结束时间
            'start'             => -1,  //>>limit 的开始记录数
            'amount'            => DEFAULT_PER_PAGE,//>>默认每页显示条数
            'create_stime'      => '',    //>>记录创建开始时间
            'create_etime'      => '',    //>>记录创建结束时间
            'start_amount'      => '',  //>>最小金额
            'end_amount'        => '',  //>>最大金额
            'freeze'            => -1,
            'orderBy'           => '',
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $sql =<<<SQL
SELECT LEFT(finish_time, 10) AS belongDate, SUM(amount) as amount,COUNT(DISTINCT (a.user_id)) AS num FROM withdraws AS a,users AS b
WHERE a.user_id = b.user_id 
AND b.top_id !=IF((SELECT user_id FROM users WHERE username = 'dcsite'),(SELECT user_id FROM users WHERE username = 'dcsite'),-1)
SQL;
        //尽量用user_id为优
        if ($default_options['user_name'] != '') {
            //>>snow 添加,如果选择了总代或用户名 ,查询全部数据
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_name']);
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    $sql .= " AND a.user_id = '{$default_options['user_name']}'";
                }
                else {
                    $sql .= " AND b.username = '{$default_options['user_name']}'";
                }
            }
            else {
                if (!$tmp) {
                    if($default_options['freeze'] == -1) {
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_name'])) {
                            return array();
                        }
                    }else{
                        //如果不是数字id,先得到用户id
                        if (!$user = users::getItem($default_options['user_name'],8,false,1,1)) {
                            return array();
                        }
                    }
                    $default_options['user_name'] = $user['user_id'];
                }
                if($default_options['freeze'] == -1) {
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_name'],
                        'recursive' => 1,
                        'status' => 8,
                    ]);
                }else{
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $default_options['user_name'],
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
                }
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($default_options['is_test'] != -1) {
            $sql .= ' AND b.is_test = ' . intval($default_options['is_test']);
        }
        if(is_array($default_options['status'])){
            $sql .= ' AND a.status IN(' . implode(',', $default_options['status']) . ')';
        }
        elseif ($default_options['status'] !== -1) {
            $sql .= ' AND a.status = ' . intval($default_options['status']);
        }
        if ($default_options['pay_bank_id'] != 0) {
            $sql .= ' AND a.pay_bank_id = ' . intval($default_options['pay_bank_id']);
        }
        if ($default_options['pay_card_id'] != 0) {
            $sql .= ' AND a.pay_card_id = ' . intval($default_options['pay_card_id']);
        }
        if ($default_options['bank_id'] != 0) {
            $sql .= ' AND a.bank_id = ' . intval($default_options['bank_id']);
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
        if($default_options['start_amount']!=='' && $default_options['end_amount']!=='' && is_numeric($default_options['start_amount']) && is_numeric($default_options['end_amount'])){
            $sql .= " AND a.amount <= {$default_options['end_amount']} AND a.amount >= {$default_options['start_amount']}";
        }elseif($default_options['start_amount']!=='' && is_numeric($default_options['start_amount'])){
            $sql .= " AND a.amount >= {$default_options['start_amount']}";
        }elseif($default_options['end_amount']!=='' && is_numeric($default_options['end_amount'])){
            $sql .= " AND a.amount <= {$default_options['end_amount']}";
        }
        $sql .= ' GROUP BY belongDate DESC';
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }
    /************************ snow  复制一个方法,用来排除dcsite 总代下的所有数据 end************************************/
    static public function getItemsList($user_id = '', $include_childs = 0, $is_test = -1, $status = -1, $pay_bank_id = 0, $pay_card_id = 0, $bank_id = 0, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.level,b.is_test,b.status AS user_status FROM withdraws a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        //尽量用user_id为优
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND a.user_id = '$user_id'";
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
                    'status' => 8,
                ]);
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= ' AND b.is_test = ' . intval($is_test);
        }
        if(is_array($status)){
            $sql .= ' AND a.status IN(' . implode(',', $status) . ')';
        }
        elseif ($status !== -1) {
            $sql .= ' AND a.status = ' . intval($status);
        }
        if ($pay_bank_id != 0) {
            $sql .= ' AND a.pay_bank_id = ' . intval($pay_bank_id);
        }
        if ($pay_card_id != 0) {
            $sql .= ' AND a.pay_card_id = ' . intval($pay_card_id);
        }
        if ($bank_id != 0) {
            $sql .= ' AND a.bank_id = ' . intval($bank_id);
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }

        $sql .= ' ORDER BY a.withdraw_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }
    static public function getItemsListNum($user_id = '', $include_childs = 0, $is_test = -1, $status = -1, $pay_bank_id = 0, $pay_card_id = 0, $bank_id = 0, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT count(a.withdraw_id) AS num FROM withdraws a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        //尽量用user_id为优
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND a.user_id = '$user_id'";
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
                    'status' => 8,
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if(is_array($status)){
            $sql .= " AND a.status IN(" . implode(',', $status) . ")";
        }
        elseif ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($pay_bank_id != 0) {
            $sql .= " AND a.pay_bank_id = " . intval($pay_bank_id);
        }
        if ($pay_card_id != 0) {
            $sql .= " AND a.pay_card_id = " . intval($pay_card_id);
        }
        if ($bank_id != 0) {
            $sql .= " AND a.bank_id = " . intval($bank_id);
        }
        //160105 应财务要求 统计时间由 提案发起时间 改为 执行时间
        if ($startDate !== '') {
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }



        $result = $GLOBALS['db']->getRow($sql);

        return $result['num'];
    }

    static public function getWithdrawErrors($errno = '')
    {
        $result = array(
            '101' => '账号、用户名不符',
            '102' => '银行维护',
            '103' => '流水不足充值的30%',
            '104' => '24小时之内更改了绑定银行卡',
            '105' => '24小时之内修改了安全信息',
            '106' => '投注流水异常',
            '107' => '提款系统维护',
        );

        if ($errno) {
            return isset($result[$errno]) ? $result[$errno] : '';
        }

        return $result;
    }

    static public function getTrafficInfo($user_id = '', $include_childs = 0, $is_test = -1, $status = -1, $pay_bank_id = 0, $pay_card_id = 0, $bank_id = 0, $startDate = '', $endDate = '', $create_stime = '', $create_etime = '',$freeze = -1,$orderBy='')
    {
        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount,sum(fee) AS total_fee FROM withdraws a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
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
                            return array('count' => 0, 'total_amount' => 0, 'total_fee' => 0);
                        }
                    }else{
                        if (!$user = users::getItem($user_id,8,false,1,-1)) {
                            return array('count' => 0, 'total_amount' => 0, 'total_fee' => 0);
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
                        'status' => 8,
                    ]);
                }else{
                    // 这里又是一个BUG
                    // $teamUsers = users::getUserTree($user_id, true, 1, 8,-1,'',-1);
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

        if($status !== -1){
            if(is_array($status)){
                $sql .= " AND a.status IN(" . implode(',', $status) . ")";
            } else {
                $sql .= " AND a.status = " . intval($status);
            }
        }

        if ($pay_bank_id != 0) {
            $sql .= " AND a.pay_bank_id = " . intval($pay_bank_id);
        }
        if ($pay_card_id != 0) {
            $sql .= " AND a.pay_card_id = " . intval($pay_card_id);
        }
        if ($bank_id != 0) {
            $sql .= " AND a.bank_id = " . intval($bank_id);
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
            $sql .=' ORDER BY '.$orderBy;
        }
        $result = $GLOBALS['db']->getRow($sql);
        if (!$result['total_amount']) {
            $result['total_amount'] = 0;
        }
        if (!$result['total_fee']) {
            $result['total_fee'] = 0;
        }

        return $result;
    }
	/**
	 *
	 * @param unknown_type $user_id
	 * @param unknown_type $include_childs
	 * @param unknown_type $is_test
	 * @param unknown_type $status
	 * @param unknown_type $pay_bank_id
	 * @param unknown_type $pay_card_id
	 * @param unknown_type $bank_id
	 * @param unknown_type $startDate
	 * @param unknown_type $endDate
	 * @param unknown_type $create_stime
	 * @param unknown_type $create_etime
	 * @param unknown_type $startAmount 起始金额
	 * @param unknown_type $endAmount	结束金额
	 * @param unknown_type $freeze
	 * @param unknown_type $orderBy
	 * @return multitype:number |number
	 * @author L
	 * @time : 2017-11-11 19:34
	 */
    static public function getTrafficInfoL($user_id = '', $include_childs = 0, $is_test = -1, $status = -1, $pay_bank_id = 0, $pay_card_id = 0, $bank_id = 0, $startDate = '', $endDate = '', $create_stime = '', $create_etime = '',$startAmount,$endAmount,$freeze = -1,$orderBy='')
    {
    	$sql = 'SELECT count(*) AS count,sum(amount) AS total_amount,sum(fee) AS total_fee FROM withdraws a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
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
    						return array('count' => 0, 'total_amount' => 0, 'total_fee' => 0);
    					}
    				}else{
    					if (!$user = users::getItem($user_id,8,false,1,-1)) {
    						return array('count' => 0, 'total_amount' => 0, 'total_fee' => 0);
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
                        'status' => 8,
                    ]);
    			}else{
                    // 这里又是一个BUG
    				// $teamUsers = users::getUserTree($user_id, true, 1, 8,-1,'',-1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'status' => 8,
                        'freeze' => 1
                    ]);
    			}
    			$sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
    		}
    	}
    	if ($is_test != -1) {
    		$sql .= ' AND b.is_test = ' . intval($is_test);
    	}
    
    	if($status !== -1){
    		if(is_array($status)){
    			$sql .= ' AND a.status IN(' . implode(',', $status) . ')';
    		} else {
    			$sql .= ' AND a.status = ' . intval($status);
    		}
    	}
    
    	if ($pay_bank_id != 0) {
    		$sql .= ' AND a.pay_bank_id = ' . intval($pay_bank_id);
    	}
    	if ($pay_card_id != 0) {
    		$sql .= ' AND a.pay_card_id = ' . intval($pay_card_id);
    	}
    	if ($bank_id != 0) {
    		$sql .= ' AND a.bank_id = ' . intval($bank_id);
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
    	if ($startAmount !== ''){
    		$sql .= " AND a.amount >= '$startAmount'";
    	}
    	if ($endAmount !== ''){
    		$sql .= " AND a.amount <= '$endAmount'";
    	}
    	if($orderBy)
    	{
    		$sql .=' ORDER BY '.$orderBy;
    	}
    	//dd($sql);
    	$result = $GLOBALS['db']->getRow($sql);
    	if (!$result['total_amount']) {
    		$result['total_amount'] = 0;
    	}
    	if (!$result['total_fee']) {
    		$result['total_fee'] = 0;
    	}
    
    	return $result;
    }
    
    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM withdraws WHERE withdraw_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'withdraw_id');
    }

    static public function wrapId($id)    //, $issue, $lottery_id
    {
       //T130117001010028E
       // switch ($lottery_id) {
       //     case '1':
       //     case '2':
       //         $str = substr(str_replace('-', '', $issue), 4);
       //         $str = str_pad($str, 10, '0', STR_PAD_RIGHT) . str_pad($trace_id, 5, '0', STR_PAD_LEFT);
       //         break;
       //     default:
       //         throw new exception2("Unknown rules for lottery {$lottery_id}");
       //         break;
       // }

       // $result = "T{$lottery_id}{$str}E";

        return 'W' . encode($id) . 'E';
    }

    static public function dewrapId($str)  //, $issue, $lottery_id
    {
        if (!preg_match('`^W(\w+)E$`Ui', $str, $match)) {
            return 0;
        }
        $result = decode($match[1]);
        if (!is_numeric($result)) {
            return 0;
        }

        return $result;
    }

    static public function getUsersWithdraws($user_ids, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT user_id, SUM(amount) as total_withdraw FROM withdraws WHERE 1';
        if (!is_array($user_ids)) {
            throw new exception2('参数无效');
        }
        if (empty($user_ids)) {
            return array();
        }
        foreach ($user_ids as $v) {
            if (!is_numeric($v)) {
                throw new exception2('参数无效');
            }
        }
        $sql .= " AND user_id IN(" . implode(',', $user_ids) . ")";

        //这里应该用finish_time，因为只有执行时用户资金才会变化
        if ($startDate !== '') {
            $sql .= " AND finish_time >= '{$startDate}'";
        }
        if ($endDate !== '') {
            $sql .= " AND finish_time <= '{$endDate}'";
        }
        $sql .= " AND status = 8 GROUP BY user_id ORDER BY user_id ASC";
        $result = $GLOBALS['db']->getAll($sql, array(),'user_id');
        foreach ($user_ids as $v) {
            if (!isset($result[$v])) {
                $result[$v] = array('user_id' => $v, 'total_withdraw' => 0);
            }
        }

        return $result;
    }

    //得到任意用户及其直属下级团队提款量

    /**
     * author snow 添加提款次数
     * @param $user_id
     * @param string $startDate
     * @param string $endDate
     * @param int $freeze
     * @return array
     * @throws exception2
     */
    static public function getTeamWithdraws($user_id, $startDate = '', $endDate = '',$freeze = -1)
    {
        if($freeze == -1) {
            // $teamUsers = users::getUserTree($user_id, true, 1, 8, -1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'parent_tree'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
            ]);
        }else{
            // $teamUsers = users::getUserTree($user_id, true, 1, 8, -1, '', 1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'parent_tree'],
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
                $result[$v['user_id']]['total_withdraw']        = 0;
                $result[$v['user_id']]['total_withdraw_count']  = 0;
            }
        }

        $self = $teamUsers[$user_id];
        $teamTotalWithdraw      = $self['total_withdraw']       = 0;
        $teamTotalWithdrawCount = $self['total_withdraw_count'] = 0;

        if ($self['parent_id'] == 0) {
            $selfLevel = 0;
        }
        else {
            $selfLevel = count(explode(',', $self['parent_tree']));
        }

        $sql =<<<SQL
SELECT w.user_id,u.parent_id, u.parent_tree, COUNT(withdraw_id) AS total_withdraw_count,SUM(w.amount) AS total_withdraw
FROM withdraws w
LEFT JOIN users u ON w.user_id=u.user_id
WHERE u.is_test = 0 
AND w.status = 8
SQL;
         if($freeze == -1) {
             $sql .= ' AND u.status = 8';
         }else{
             $sql .= ' AND (u.status = 8 or u.status = 1)';
         }
        if ($startDate != '') {
            $sql .= " AND w.finish_time >= '$startDate'";
        }
        if ($endDate != '') {
            $sql .= " AND w.finish_time <= '$endDate'";
        }

//        $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'w.user_id');
        /************************* author snow 实测下面的代码效率更快*******************************************/
        $sql .= " AND (FIND_IN_SET({$user_id},u.parent_tree) OR u.user_id = {$user_id})";
        $sql .= ' GROUP BY w.user_id';

        $tmp1 = $GLOBALS['db']->getAll($sql, array(),'user_id');

        if (isset($tmp1[$user_id])) {
            $teamTotalWithdraw      = $self['total_withdraw']       = $tmp1[$user_id]['total_withdraw'];
            $teamTotalWithdrawCount = $self['total_withdraw_count'] = $tmp1[$user_id]['total_withdraw_count'];
            unset($tmp1[$user_id]);
        }

        foreach ($tmp1 as $k => $v) {
            $teamTotalWithdraw      += $v['total_withdraw']; //团队提款量
            $teamTotalWithdrawCount += $v['total_withdraw_count']; //团队提款次数
            if (isset($result[$k])) { //直接下级
                $result[$k]['total_withdraw']       += $v['total_withdraw'];
                $result[$k]['total_withdraw_count'] += $v['total_withdraw_count'];
            }
            else { //非直接下级
                $parent_ids = explode(',', $v['parent_tree']);
                if(isset($parent_ids[$selfLevel + 1]) && isset( $result[$parent_ids[$selfLevel + 1]])) {
                    $result[$parent_ids[$selfLevel + 1]]['total_withdraw']          += $v['total_withdraw'];
                    $result[$parent_ids[$selfLevel + 1]]['total_withdraw_count']    += $v['total_withdraw_count'];
                }
            }
        }

        $result[$user_id]['total_withdraw']         = $self['total_withdraw'];
        $result[$user_id]['total_withdraw_count']   = $self['total_withdraw_count'];
        $result['team_total_withdraw']              = $teamTotalWithdraw;
        $result['team_total_withdraw_count']        = $teamTotalWithdrawCount;
//        dd($result);
        return $result;
    }

    //得到任意用户及其直属下级团队日存款量
    static public function getTeamDayWithdraws($user_id, $startDate = '', $endDate = '')
    {
        // $teamUsers = users::getUserTree($user_id, true, 1, 8);
        $teamUsers = users::getUserTreeField([
            'field' => ['user_id', 'parent_id', 'level'],
            'parent_id' => $user_id,
            'recursive' => 1,
            'status' => 8,
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
                $dayRport[$d][$a]['day_withdraw'] = 0;
            }
            //$selfDayRport[$d]['day_withdraw'] = 0;//自己的数据可以先省略需要展示再开启
        }

        $selfLevel = $teamUsers[$user_id]['level'];

        $sql = 'SELECT w.user_id,u.parent_id, u.parent_tree, w.amount, w.finish_time FROM withdraws w LEFT JOIN users u ON w.user_id=u.user_id WHERE u.status = 8 AND u.is_test = 0 AND w.status = 8';
        if ($startDate != '') {
            $sql .= " AND w.finish_time >= :startDate";
            $paramArr[':startDate'] = $startDate;
        }
        if ($endDate != '') {
            $sql .= " AND w.finish_time <= :endDate";
            $paramArr[':endDate'] = $endDate;
        }

        $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'w.user_id');

        $tmp1 = $GLOBALS['db']->getAll($sql, $paramArr);

        $dayRport['team_total_day_withdraw'] = 0;
        foreach ($days as $day) {
            foreach ($tmp1 as $v) {
                $bool = strtotime($v['finish_time']) <= strtotime($day.' 23:59:59') && strtotime($v['finish_time']) >= strtotime($day);
                if($bool){
                    $dayRport['team_total_day_withdraw'] += $v['amount'];
                    if ($user_id == $v['user_id'] || in_array($v['user_id'], $agent)) {
                        //$selfDayRport[$day]['day_withdraw'] += $v['amount'];//自己的数据可以先省略需要展示再开启
                        $dayRport[$day][$v['user_id']]['day_withdraw'] += $v['amount'];
                    } else {
                        $parent_ids = explode(',', $v['parent_tree']);
                        $dayRport[$day][$parent_ids[$selfLevel + 1]]['day_withdraw'] += $v['amount'];
                    }
                }
            }
        }

        return $dayRport;
    }

    //得到正在处理的记录 前台用来判断一个人多次提交提款请求
    //0未处理 1已受理 2已审核 3已成功 4不符提款要求取消提款
    static public function getPendingWithdraw($user_id)
    {
        if ($user_id <= 0) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM withdraws WHERE user_id = ' . intval($user_id) . ' AND status < 8';

        return $GLOBALS['db']->getRow($sql);
    }

    //得到用户上次成功提款信息，审核用户提款时用到
    static public function getLastSuccessWithdraw($withdraw_id, $user_id)
    {
        if ($withdraw_id <= 0 || $user_id <= 0) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM withdraws WHERE withdraw_id < ' . $withdraw_id . ' AND user_id = ' . intval($user_id) . ' AND status = 8 ORDER BY withdraw_id DESC';

        return $GLOBALS['db']->getRow($sql);
    }

    //得到用户上次成功提款信息，审核用户提款时用到
    static public function getLastSuccessWithdrawInfo($user_id, $startDate = '', $endDate = '')
    {
        if ($user_id <= 0) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM withdraws WHERE user_id = ' . intval($user_id);

        if ($startDate !== '') {
            $sql .= " AND finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND finish_time <= '$endDate'";
        }

        $sql .= ' AND status = 8 ORDER BY withdraw_id DESC LIMIT 1';

        return $GLOBALS['db']->getRow($sql);
    }
/**************************** snow ***************************************************/

    /**
     * //>>获取用户截止到当前的当日总提款额度
     * @param $user_id
     * @param $start
     * @param $end
     * @return bool|int
     * @throws exception2
     */
    private static function _getAmount_count($user_id, $start, $end)
    {

        $sql = 'SELECT user_id, SUM(amount) as total_withdraw FROM withdraws WHERE 1';
        if (!is_numeric($user_id)) {
            throw new exception2('参数无效');
        }
        if (empty($user_id)) {
            return false;
        }

        $sql        .= ' AND user_id = ' . $user_id;

        //这里应该用create_time，因为只只要发起了提款,只要没有被拒绝 ,就算
        if ($start !== '') {
            $sql    .= " AND create_time >= '$start'";
        }
        if ($end !== '') {
            $sql    .= " AND create_time <= '$end'";
        }
        $sql        .= ' AND status != 9 ';
        $result     = $GLOBALS['db']->getRow($sql);

        if(is_array($result) && !empty($result)){
            return (int)$result['total_withdraw'];
        }
        return false;

    }


    /**************************** snow ***************************************************/
    //前台用户申请提款  写帐变扣余额，若不符再加条“取消提款”的帐变并退还游戏币
    static public function applyWithdraw($user_id, $withdraw_bank_id, $withdraw_card_num, $province, $city, $branch_name, $withdraw_amount, $secpwd = '', $bank_name = '')
    {
        if (!isset($GLOBALS['cfg']['withdrawBankList'][$withdraw_bank_id])) {
            return "请选择要提款的银行";
        }
        if (!$withdraw_card_num) {
            return "请输入卡号";
        }
        # TODO : app 没有这个东西,暂时注释掉.
        /*if (!$province || !$city) {
            return "请选择正确的省份和城市";
        }
        if (!$branch_name) {
            return "请输入支行详细地址";
        }*/

        $config = config::getConfigs(array('min_withdraw_limit', 'max_withdraw_limit','max_withdraw_day_limit',''));
//        $config = config::getConfigs(array('min_withdraw_limit', 'max_withdraw_limit'));
        if ($withdraw_amount < $config['min_withdraw_limit']) {
            return "金额不足最低提款限额{$config['min_withdraw_limit']}，无法提交";
        }

        /*********************** snow  修改文案 限制单笔金额不能多于100万  单日限额不能大于1000万 **********************/


        if ($withdraw_amount > $config['max_withdraw_limit']) {
            return "金额超过单笔最高提款限额{$config['max_withdraw_limit']}，无法提交";
        }

        //>> DO 获取今日已经提取金额的总额度.判断是 加上现在是否超过1000万.

            $startTime      = date('Y-m-d 00:00:00');   //>>开始时间
            $endTime        = date('Y-m-d H:i:s');      //>>结束时间截止到现在
            $amount_count   = self::_getAmount_count($user_id,$startTime, $endTime);
            if(!empty($config['max_withdraw_day_limit'])){
                if(($amount_count + (int)$withdraw_amount) > (int)$config['max_withdraw_day_limit']){
                    return "总数已经超过了单日最高限额{$config['max_withdraw_day_limit']}，无法提交";
                }
            }
        /*********************** snow  添加 限制单笔金额不能多于100万 单日限额不能大于1000万 ***************/
        //开始事务
        $GLOBALS['db']->startTransaction();

        //数据正确性判断
        if (!$user = users::getItem($user_id, 8, true)) {
            return "该用户不存在或已被冻结";
        }

        //查看礼品券发放情况
        // $gift = userGifts::getUserNotFinishGifts($user['user_id']);
        // if ($withdraw_amount > ($user['balance'] - $gift)) {
        //     $GLOBALS['db']->rollback();
        //     return "您提款的金额不能超过当前余额，无法提交";
        // }
        if($withdraw_amount > $user['balance']){
            $GLOBALS['db']->rollback();
            return "您提款的金额不能超过当前余额，无法提交";
        }

        if ($secpwd && $user['secpwd'] != generateEnPwd($secpwd)) {
            $GLOBALS['db']->rollback();
            return "您输入的资金密码不对";
        }
        if (withdraws::getPendingWithdraw($user['user_id'])) {
            $GLOBALS['db']->rollback();
            return "您上笔提款正在处理，请等待上笔提款处理完成";
        }

        //提现必须验证消费流水达到前次提款后充值量的30%
        //查询最近提款成功的时间,这里强制查一行，而且刚好是倒序 取最近的一条数据
        $lastDraw = withdraws::getItems($user_id, 0,  -1,  8,  0,  0,  0,  '', '', 0, 1,  '',  '');
        $startTime = ''; //就无限制
        if( count($lastDraw) == 1 ) {
            $startTime = $lastDraw[0]['finish_time'];
        }
        //统计指定时间内的总充值数量
        // $rechangeMoney = deposits::getRechangeBydate($user_id, $startTime);
        // $startTime1 = $startTime2 = '';
        // if( isset($rechangeMoney['finish_time']) ){
        //     $startTime1 = $rechangeMoney['finish_time'];
        // }elseif( !isset($rechangeMoney['finish_time']) && $startTime == '' ){
        //     //$startTime == '' //说明时间无限制了，但是又找不到充值记录，进而说明没提款过但是又查找不出充值记录，却能跑这里来？
        //     return "该用户没有充值过却能提款，非法提款";
        // }
        // if( isset($rechangeMoney['finish_time']) ){
        //     $startTime1 = $rechangeMoney['finish_time'];
        // }
        // if( $startTime1 == '' ){//说明上次提款后没充值过
        //     //检查上次提款前一次的最近一次充值的合法性
        //     $rechangeMoney = deposits::getRechangeBydate($user_id, $startTime,false);
        //     if( !isset($rechangeMoney['finish_time'])  ){ //为空，说明要怀疑上次提款能成功的问题了
        //         //而没有充值记录直接过来提款，肯定是有问题的，要拦截这种情况
        //         //return "该用户从来没充值，上次却能成功，非法提款";
        //     }
        //     //验证最近这笔的合法性 一样走下面的逻辑 ,只是时间是从$startTime2到$startTime1
        //     $startTime2 = $rechangeMoney['finish_time'];
        // }
        // $betMoney = projects::getPackageTotal( 0,  [1,2],  -1,  '',  -1,  0, $user_id ,   0, ($startTime2!=''?$startTime2:$startTime1),  ($startTime2 !='' ?$startTime1 : ''),   '',   '', 0,0);
        // !isset($betMoney['total_amount']) && $betMoney['total_amount'] = 0;
        // //查询消费的钱每一笔提款要求达到前次提款后充值量的30%，若无前次提款则需要满足当前时间中以前所有充值量的30%
        // if( $betMoney['total_amount'] < bcmul($rechangeMoney['total_amount'],0.3) ){
        //     $GLOBALS['db']->rollback();
        //     return $startTime2==''?"每一笔提款要求消费达到前次提款后充值量的30%，若无前次提款则需要满足当前时间中以前所有充值量的30%"
        //         :"上一笔提款没有消费达到前次提款后充值量的30%，非法提款";
        // }
        $bankName = $bank_name != '' ? $bank_name : $user['real_name'];
        //1.生成未处理的提款提案
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'bank_id' => $withdraw_bank_id,
            'card_name' => $bankName,
            'card_num' => $withdraw_card_num,
            'province' => $province,
            'city' => $city,
            'branch' => $branch_name,
            'amount' => $withdraw_amount,
            'fee' => -1, //为达成交易所产生的手续费 为0或者正数 现在当然还不知道 付款的时候才知
            'pay_bank_id' => 0,
            'pay_card_id' => 0,
            'order_num' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'status' => 0, //0未处理 1已受理 2已审核 3交给机器受理 4机器正在受理 8已成功 9因故取消
            'finish_time' => date('Y-m-d H:i:s'),
            'last_balance' => $user['balance'] - $withdraw_amount, //申请提款后的余额，下次提款时可参考核对
        );

        if (!withdraws::addItem($data)) {
            $GLOBALS['db']->rollback();
            return '错误码1';
        }
        $withdraw_id = $GLOBALS['db']->insert_id();
        //2.增加帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 201, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$withdraw_amount,
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] - $withdraw_amount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $withdraw_id,
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            return '错误码2';
        }

        //3.扣减游戏币
        if (!users::updateBalance($user['user_id'], -$withdraw_amount)) {
            $GLOBALS['db']->rollback();
            return '错误码3';
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            $GLOBALS['db']->rollback();
            return '错误码5';
        }
        return $withdraw_id;
    }
    static public function ipIsChina(){
        $ip=trim($GLOBALS['REQUEST']['client_ip']);
        if(empty($ip)) return true;
        if(in_array(trim(explode('.',$ip)[0]),['192','127'])) return true;
        try {
            $res1 = file_get_contents("http://ip.taobao.com/service/getIpInfo.php?ip=$ip");
            $res1 = json_decode($res1,true);
            if(!empty($res1) && isset($res1['data']["country"]) && isset($res1['data']["country_id"])){
                if ($res1[ "code"]==0){
                    if($res1['data']["country"]=='中国' && $res1['data']["country_id"]=='CN') return true;
                }
            }else{
                $ip11 = @file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=".$ip);
                $ip11 = trim(trim(explode('=',$ip11)[1]),';');
                $res=json_decode($ip11,true);
                if(!empty($res) && isset($res["country"]) && isset($res["province"])){
                    if($res["country"]=='中国' && !in_array($res['province'],['香港','澳门'])) return true;
                }
            }
            return false;
        }catch (Exception $e){
            return false;
        }
    }

    //执行提款 不添加帐变，因为已经在发起提款时增加
    static public function pay($withdraw_id, $pay_card_num, $my_fee = 0, $order_num = '', $admin_id = '')
    {
        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            throw new exception2('找不到提案');
        }
        //对于机器处理的待支付状态为4
        if ($withdraw['status'] != 2 && $withdraw['status'] != 4) {
            throw new exception2('提款提案状态不是“已审核”，拒绝执行');
        }
        if ($withdraw['amount'] <= 0) {
            throw new exception2('提款金额为负数，出错了');
        }
        if ($pay_card_num == '') {
            throw new exception2('付款卡号为空');
        }
        if (!$user = users::getItem($withdraw['user_id'])) {
            throw new exception2('该用户不存在或已被禁用');
        }
        if ($my_fee < 0 || $my_fee > ceil($withdraw['amount'] * 0.01)) {
            throw new exception2('手续费不可能大于金额的1%，拒绝执行');
        }
        if (!$card = cards::getItem($pay_card_num, 2)) {
            throw new exception2('找不到该付款卡');
        }
        if ($card['status'] != 1 && $card['status'] != 2) {
            throw new exception2("该付款卡状态异常({$card['status']})，拒绝执行付款操作");
        }
        if ($card['balance'] < ($withdraw['amount'] + $my_fee)) {
            throw new exception2("付款卡余额为{$card['balance']}，不足以支付这笔提款{$withdraw['amount']}");
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        //2.增加银行卡帐变
        $cardOrderData = array(
            'from_card_type' => 2, //源卡类型 1收款 2付款 3备用金
            'from_bank_id' => $card['bank_id'],
            'from_card_id' => $card['card_id'],
            'to_card_type' => 0, //源卡类型 1收款 2付款 3备用金
            'to_bank_id' => 0,
            'to_card_id' => 0,
            'order_type' => 2, //1收款 2付款 3内转入 4内转出 每次内转将产生2条卡帐变
            'amount' => -$withdraw['amount'],
            'my_fee' => -$my_fee,
            'pre_balance' => $card['balance'],
            'balance' => $card['balance'] + (-$withdraw['amount']) + (-$my_fee),
            'create_time' => date('Y-m-d H:i:s'),
            'ref_id' => $withdraw['withdraw_id'], //相关id，对于存提款为其提案id，对于内转由于没有专门表记录，所以为0
            'ref_user_id' => $withdraw['user_id'], //相关用户id，对于存提款为当事用户id，对于内转为0
            'ref_username' => $withdraw['username'], //相关用户名，对于存提款为当事用户名，对于内转为空
            'admin_id' => $admin_id,
            'remark' => '',
        );


        if (!cardOrders::addItem($cardOrderData)) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败1');
        }

        //3.付款后，卡里面钱少了，当然要及时更新余额
        if (!cards::updateBalance($card['card_id'], -$withdraw['amount'] - $my_fee)) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败2');
        }

        //5.关于记录付款卡流水帐，可以从yj后台查到，所以这里不需要记录，当然，硬要记也行
        //7.最后更新付款提案状态为“已成功”
        $data = array(
            'pay_bank_id' => $card['bank_id'],
            'pay_card_id' => $card['card_id'],
            'order_num' => $order_num,
            'fee' => $my_fee,
            'finish_admin_id' => $admin_id,
            'status' => 8, //'0未处理 1已受理 2已审核 3机器已受理 8已成功 9不符提款要求取消提款'
            'finish_time' => date('Y-m-d H:i:s'),
            'errno' => 0,
        );
        if (!withdraws::updateItem($withdraw['withdraw_id'], $data)) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败3');
        }
        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('提交事务失败4');
        }

       //  是否换卡统一由YJ控制
       // //判断余额是否超限或者低于最低限额
       // $paycard_min_balance = config::getConfig('paycard_min_balance', 1000);
       // if ($card['balance'] - ($withdraw['amount'] + $my_fee) < $paycard_min_balance) {
       //     log2("{$card['card_name']}余额{$card['balance']}低于最低限额{$paycard_min_balance}，余额不足而下线");
       //     cards::updateItem($card['card_id'], array('status' => 4));
       //     return -1;  //余额不足而下线
       // }

       // $dayTraffic = cardOrders::getDayTraffic($card['bank_id'], $card['card_id'], 2, date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
       // log2("{$card['card_name']}当日付款流量:{$dayTraffic} 最高限额:{$card['day_limit']}");
       // if (abs($dayTraffic) >= $card['day_limit']) {
       //     //log2("已超过日支付最高限额，当日不再使用");
       //     cards::updateItem($card['card_id'], array('status' => 5));
       //     return -2;  //余额达到日支付上限而下线
       // }

       // // 检查是否强制设为禁用 换卡
       // if ($card['status'] != 1 && $card['status'] != 2) {
       //     return -3;
       // }

        return true;
    }

    //执行测试帐号的提款
    static public function payTester($withdraw_id)
    {
        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            throw new exception2('找不到提案');
        }
        //对于机器处理的待支付状态为4
        if ($withdraw['status'] != 0) {
            throw new exception2('该提款提案不是未审核，拒绝执行');
        }
        if ($withdraw['amount'] <= 0) {
            throw new exception2('提款金额为负数，出错了');
        }
        if (!$user = users::getItem($withdraw['user_id'])) {
            throw new exception2('该用户不存在或已被禁用');
        }

        //7.最后更新付款提案状态为“已成功”
        $data = array(
            'pay_bank_id' => 0,
            'pay_card_id' => 0,
            'order_num' => '',
            'fee' => 0,
            'finish_admin_id' => 65535,
            'status' => 8, //'0未处理 1已受理 2已审核 3机器已受理 8已成功 9不符提款要求取消提款'
            'finish_time' => date('Y-m-d H:i:s'),
            'errno' => 0,
            'remark' => '测试帐号,虚拟执行',
        );

        if (!withdraws::updateItem($withdraw['withdraw_id'], $data)) {
            return false;
        }

        return true;
    }

    //转给机器自动付款
    static public function changeToMachine($withdraw_id, $admin_id)
    {
        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            throw new exception2('找不到提案');
        }
        //2人同时打开审核后的页面，准备限制只有审核的人才可以付款，但考虑有分工情况，这里不限制执行者和审核者须为同一个人
        if ($withdraw['status'] != 2) { //0未处理 1已受理 2已审核 3交给机器受理 4机器正在受理 8已成功 9因故取消
            throw new exception2('提款提案状态不是“已审核”，拒绝执行');
        }

        $config = config::getConfigs(array('min_withdraw_limit', 'max_withdraw_limit', 'max_auto_pay_limit'));
        if ($withdraw['amount'] < $config['min_withdraw_limit']) {
            throw new exception2("金额不足最低提款限额{$config['min_withdraw_limit']}，无法提交");
        }
        if ($withdraw['amount'] > $config['max_withdraw_limit']) {
            throw new exception2("金额超过当日提款限额{$config['max_withdraw_limit']}，无法提交");
        }
        if ($withdraw['amount'] > $config['max_auto_pay_limit']) {
            throw new exception2("金额超过机器自动付款最大限额{$config['max_auto_pay_limit']}，无法提交");
        }

        if (!withdraws::updateItem($withdraw_id, array('status' => 3, 'finish_admin_id' => $admin_id))) {
            throw new exception2("数据更新失败");
        }

        return true;
    }

    //取消提款 添加帐变“退还提款” 返还游戏币，更新状态为已取消9
    static public function cancel($withdraw_id, $admin_id, $remark, $errno = -1)
    {
        //开始事务
        $GLOBALS['db']->startTransaction();

        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            throw new exception2('找不到提案');
        }
        if ($withdraw['status'] == 3) {
            throw new exception2('对不起，目前仅能取消未成功执行的提案，已经执行成功的暂不支持取消');
        }
        if ($withdraw['amount'] <= 0) {
            throw new exception2('提款金额为负数，出错了');
        }
        if (!$user = users::getItem($withdraw['user_id'], -1)) {
            throw new exception2('该用户不存在或已被禁用');
        }
        if (!withdraws::getWithdrawErrors($errno)) {
            throw new exception2('无效的取消原因');
        }
        if (!$remark) {
            throw new exception2('取消客户的提款必须写明原因');
        }

        //1.增加'退还提款'帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $withdraw['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 106, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => $withdraw['amount'],
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] + $withdraw['amount'],
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $withdraw['withdraw_id'],
            'admin_id' => $admin_id,
        );
        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败1');
        }
        //2.把发起提案时扣的游戏币退给客户
        if (!users::updateBalance($withdraw['user_id'], $withdraw['amount'])) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败2');
        }
        //3. 没有进行真实付款，所以不需要更新付款卡余额
       // if (!cards::updateBalance($withdraw['pay_card_id'], -$withdraw['amount'], $fee)) {
       //     $GLOBALS['db']->rollback();
       //     return false;
       // }
        //5.最后更新付款提案状态为“已取消提款”不再受理
        if (!withdraws::updateItem($withdraw_id, array('finish_admin_id' => $admin_id, 'status' => 9, 'errno' => $errno, 'remark' => $remark, 'finish_time' => date('Y-m-d H:i:s')))) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败3');
        }
        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败4');
        }

        return true;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('withdraws', $data);
    }

    static public function updateItem($id, $data, $addonsConditions = array())
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('withdraws' , $data , array_merge($addonsConditions , array('withdraw_id' => $id)));
    }

    /** 暂不允许删除
      static public function deleteItem($id, $realDelete = false)
      {
      if (!is_array($ids)) {
      throw new exception2('参数无效');
      }

      if ($realDelete) {
      $sql = "DELETE FROM withdraws WHERE withdraw_id = " . intval($id);
      }
      else {
      $sql = "UPDATE withdraws SET status = -1 WHERE withdraw_id = " . intval($id);
      }

      return $GLOBALS['db']->query($sql);
      }
     *
     */
    //自动付款专用 得到一笔交给机器处理的款项
    static public function getSubmitMachineWithdraw($bank_id = 0)
    {
        //开始事务
        $GLOBALS['db']->startTransaction();
        $sql = 'SELECT * FROM withdraws WHERE 1';
        if ($bank_id > 0) {
            $sql .= " AND bank_id = " . intval($bank_id);
        }
        $sql .= ' AND status = 3';
        $result = $GLOBALS['db']->getRow($sql);

        if ($result) {
            if (!withdraws::updateItem($result['withdraw_id'], array('status' => 4), array('status' => 3))) {
                $GLOBALS['db']->rollback();
                return array();
            }
            if (!$GLOBALS['db']->commit()) {
                $GLOBALS['db']->rollback();
                return array();
            }
        }

        return $result;
    }

    //自动付款专用 保存付款结果 不管成功还是失败都有个交待
    static public function saveMachinePayResult($pay_bank_id, $pay_card_num, $withdraw_id, $errno, $errstr, $my_fee, $order_num)
    {
        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            throw new exception2('参数无效');
        }
        if ($withdraw['status'] != 4) {
            throw new exception2('invalid status');
        }

        //若付款成功 写卡帐变 更新提案状态为8
        if ($errno == 0) {
            return withdraws::pay($withdraw['withdraw_id'], $pay_card_num, $my_fee, $order_num, self::MACHINE_ID);
        }
        else {  //若失败则不写卡帐变，但要写用户帐变（不符取消提款），退回用户游戏币
            //150306 为避免可能的重复出款情况，不直接取消
            //return self::cancel($withdraw['withdraw_id'], self::MACHINE_ID, $errstr, $errno);
            $data = array('errno' => $errno, 'remark' => $errstr);
            return withdraws::updateItem($withdraw['withdraw_id'], $data);
        }
    }

    static public function acceptRequest($id)
    {
        if (!$withdraw = withdraws::getItem($id)) {
            throw new exception2('参数无效');
        }

        // 开始事务
        $GLOBALS['db']->startTransaction();

        // 1 查询行锁
        $sql = 'SELECT * FROM withdraws WHERE withdraw_id = ' . $id . ' AND status = 0';
        $GLOBALS['db']->query($sql);

        $data = array('verify_admin_id' => $GLOBALS['SESSION']['admin_id'], 'start_time' => date('Y-m-d H:i:s'), 'status' => '1');
        if (!withdraws::updateItem($id, $data, array('status' => 0))) {
            throw new exception2('受理失败！数据库错误！');
        }

        if (!$GLOBALS['db']->commit()) {
            $GLOBALS['db']->rollback();
            throw new exception2('受理失败！事务出错', 5060);
        }

        return true;
    }


    /**
     * author snow 获取传入用户的未处理提款数量
     * @param $user_id
     * @return int
     */
    public static function getUserWithdrawNumber($user_id)
    {
        $sql ='SELECT COUNT(*) AS countNumber FROM withdraws WHERE user_id = ' . $user_id . ' AND `status` = 0';
        $result = $GLOBALS['db']->getRow($sql);
        return $result['countNumber'];
    }


}

