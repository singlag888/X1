<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

// 帐变模型
class orders
{
    static public $business = array(
        /* 10x 20x 客户帐变相关的帐变 */
        '101' => array('title' => '充值', 'controller' => 'deposit', 'action' => 'viewDeposit', 'key_id' => 'deposit_id'),
        '102' => array('title' => '充值优惠', 'controller' => 'deposit', 'action' => 'viewDeposit', 'key_id' => 'deposit_id'),
        '103' => array('title' => '首存优惠', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),
        '104' => array('title' => '再存优惠', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),
        '105' => array('title' => '其他优惠', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),
        '106' => array('title' => '提现不符退款', 'controller' => 'withdraw', 'action' => 'viewWithdraw', 'key_id' => 'withdraw_id'),
        '107' => array('title' => '活动红包', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),
        '151' => array('title' => '不活跃下级清理'),
        '152' => array('title' => '下级存款返佣', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),
        '153' => array('title' => '代理分红', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),    //特指总代
        '154' => array('title' => '接收转账'),  //目前特指一代所得分红
        '155' => array('title' => '手工加余额', 'controller' => 'user', 'action' => 'balanceAdjustList', 'key_id' => 'ba_id'),  //只允许加
        '161' => array('title' => '从波音转出'),
        '162' => array('title' => '从休闲游戏转出'),
        '201' => array('title' => '提现', 'controller' => 'withdraw', 'action' => 'viewWithdraw', 'key_id' => 'withdraw_id'),
        '202' => array('title' => '手工减余额', 'controller' => 'user', 'action' => 'balanceAdjustList', 'key_id' => 'ba_id'),  //只允许减
        '203' => array('title' => '小额资金清理'),
        '204' => array('title' => '特殊充值扣费', 'controller' => 'deposit', 'action' => 'viewDeposit', 'key_id' => 'deposit_id'),
        '205' => array('title' => '取消礼品卷', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),
        '211' => array('title' => '转入波音'),
        '212' => array('title' => '给下级转账'),
        '213' => array('title' => '转入休闲游戏'),
        //'300' => array('title' => '特殊金额清理'),
        /* 30x 40x 客户游戏相关的帐变 */
        '301' => array('title' => '投注返点', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '302' => array('title' => '下级 投注返点', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '303' => array('title' => '撤单返款', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '304' => array('title' => '追号中止返款'),
        '308' => array('title' => '中奖', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '321' => array('title' => '平台理赔', 'controller' => 'promo', 'action' => 'promoDetail', 'key_id' => 'promo_id'),
        '401' => array('title' => '投注', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '411' => array('title' => '撤消返点', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '412' => array('title' => '撤单手续费', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '413' => array('title' => '撤消中奖', 'controller' => 'game', 'action' => 'packageDetail', 'key_id' => 'wrap_id'),
        '414' => array('title' => '追号扣款', 'controller' => '', 'action' => '', 'key_id' => ''),
        '415' => array('title' => '奖池嘉奖', 'controller' => '', 'action' => '', 'key_id' => ''),
        '501' => array('title' => '下级投注满1000返水', 'controller' => '', 'action' => '', 'key_id' => ''),
        '502' => array('title' => '下级亏损满1000返水', 'controller' => '', 'action' => '', 'key_id' => ''),
        '503' => array('title' => '会员每日返水', 'controller' => '', 'action' => '', 'key_id' => ''),
        '600' => array('title' => '抽奖扣款', 'controller' => '', 'action' => '', 'key_id' => ''),
        '601' => array('title' => '注册送', 'controller' => '', 'action' => '', 'key_id' => ''),
        '602' => array('title' => '签到送', 'controller' => '', 'action' => '', 'key_id' => ''),
        '603' => array('title' => '日盈利送', 'controller' => '', 'action' => '', 'key_id' => ''),
        '604' => array('title' => '日亏损送', 'controller' => '', 'action' => '', 'key_id' => ''),
        '701' => array('title' => '电子返水', 'controller' => '', 'action' => '', 'key_id' => ''),
    );

    static public function getItem($id)
    {
        $sql = 'SELECT * FROM orders WHERE order_id = ' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($lottery_id = 0, $issue = '', $type = 0, $user_id = '', $include_childs = 0, $start_amount = 0, $end_amount = 0, $start_time = '', $end_time = '', $start = -1, $amount = DEFAULT_PER_PAGE,$freeze = -1)
    {
        $sql = 'SELECT a.*,b.username,b.status AS user_status,b.type AS user_type FROM orders a LEFT JOIN users b ON a.from_user_id=b.user_id WHERE b.is_test = 0';
        if ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($issue != '') {
            $sql .= " AND a.issue = '$issue'";
        }
        if (!empty($type)) {
            if (is_array($type)) {
                $sql .= " AND a.type IN(" . implode(',', $type) . ")";
            }
            else {
                $sql .= " AND a.type = " . intval($type);
            }
        }
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
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.from_user_id');
            }
        }
        if ($start_amount > 0) {
            $sql .= " AND abs(a.amount) >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND abs(a.amount) <= $end_amount";
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        $sql .= ' ORDER BY order_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getTrafficInfo($lottery_id = 0, $issue = '', $type = 0, $user_id = '', $include_childs = 0, $start_amount = 0, $end_amount = 0, $start_time = '', $end_time = '',$freeze = -1)
    {
        $sql = 'SELECT COUNT(*) AS count, sum(amount) AS total_amount FROM orders a LEFT JOIN users b ON a.from_user_id=b.user_id WHERE b.is_test = 0';
        if ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($issue != '') {
            $sql .= " AND a.issue = '$issue'";
        }
        if (!empty($type)) {
            if (is_array($type)) {
                $sql .= " AND a.type IN(" . implode(',', $type) . ")";
            }
            else {
                $sql .= " AND a.type = " . intval($type);
            }
        }
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
                            return 0;
                        }
                    }else{
                        if (!$user = users::getItem($user_id,8,false,1,1)) {
                            return 0;
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
                //141104 优化情节 联表find_in_set()用不上索引，应改为单表查出团队ID再IN更优
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.from_user_id');
            }
        }
        if ($start_amount > 0) {
            $sql .= " AND abs(a.amount) >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND abs(a.amount) <= $end_amount";
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        if (!$result['total_amount']) {
            $result['total_amount'] = 0;
        }

        return $result;
    }

    static public function getTopPromos($type, $start_time = '', $end_time = '')
    {
        $sql = 'SELECT b.top_id AS top_id, SUM(amount) AS total_amount FROM orders a LEFT JOIN users b ON a.from_user_id=b.user_id WHERE b.is_test = 0';
        if (is_array($type)) {
            $sql .= " AND a.type IN(" . implode(',', $type) . ")";
        }
        else {
            $sql .= " AND a.type = " . intval($type);
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        $sql .= " GROUP BY top_id";
        $result = $GLOBALS['db']->getAll($sql, array(),'top_id');

        return $result;
    }

    //取得条件内账变用户
    //保留其它几个参数 可以用在统计
    static public function getOrderUserId($lottery_id = 0, $issue = '', $type = 0, $start_amount = 0, $end_amount = 0, $start_time = '', $end_time = '')
    {
        $sql = 'SELECT from_user_id FROM orders  WHERE 1';
        if ($lottery_id != 0) {
            $sql .= " AND lottery_id = " . intval($lottery_id);
        }
        if ($issue != '') {
            $sql .= " AND issue = '$issue'";
        }
        if ($type != 0) {
            if (is_array($type)) {
                $sql .= " AND type IN(" . implode(',', $type) . ")";
            }
            else {
                $sql .= " AND type = " . intval($type);
            }
        }
        if ($start_amount > 0) {
            $sql .= " AND abs(amount) >= $start_amount";
        }
        if ($end_amount > 0) {
            $sql .= " AND abs(amount) <= $end_amount";
        }

        if ($start_time != '') {
            $sql .= " AND create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND create_time <= '$end_time'";
        }
        $sql .= ' GROUP BY from_user_id ';
        $result = $GLOBALS['db']->getAll($sql, array(),'from_user_id');

        return $result;
    }

    static public function getBusinessUrl($type, $key_id)
    {
        $business = self::$business[$type];

        if (!isset($business['controller ']) || empty($key_id)) {
            return '';
        }

        $url = 'index.jsp?c=' . $business['controller'] . '&a=' . $business['action'] . '&' . $business['key_id'] . '=' . $key_id;

        return $url;
    }

    static public function getChildCommission($user, $start_time = '', $end_time = '',$freeze = -1)
    {
        if ($freeze == -1) {
            // $teamUsers = users::getUserTree($user['user_id'], true, 1, 8);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id'],
                'parent_id' => $user['user_id'],
                'recursive' => 1,
                'status' => 8
            ]);
        } else {
            // $teamUsers = users::getUserTree($user['user_id'], true, 1, 8,-1,'',1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id'],
                'parent_id' => $user['user_id'],
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
            if ($v['parent_id'] == $user['user_id']) {
                $result[$v['user_id']]['total_commission'] = 0;
            }
        }

        $result['team_total_commission'] = $result[$user['user_id']]['total_commission'] = 0;

        //1.得到直属下级的贡献值
        $sql = "SELECT o.from_user_id,o.to_user_id ,SUM(o.amount) AS total_commission FROM orders o LEFT JOIN users u ON o.to_user_id = u.user_id WHERE o.from_user_id = {$user['user_id']} AND o.type in (501,502)";
        if ($start_time != '') {
            $sql .= " AND o.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND o.create_time <= '$end_time'";
        }
        $sql .= ' GROUP BY o.to_user_id';
        $result1 = $GLOBALS['db']->getAll($sql, array(),'to_user_id');//这里得出来是贡献出去的，所以不能算到他们自己团队里去，要算到上级去

// array(2) {
//   [10296]=>
//   array(3) {
//     ["user_id"]=>
//     int(10100)
//     ["to_user_id"]=>
//     int(10296)
//     ["total_commission"]=>
//     string(7) "12.0000"
//   }
//   [10297]=>
//   array(3) {
//     ["user_id"]=>
//     int(10100)
//     ["to_user_id"]=>
//     int(10297)
//     ["total_commission"]=>
//     string(7) "24.0000"
//   }
// }
        //2.得到直属下级的所有孩子贡献值
        $userLevel = $user['level'] + 2;
        $result2 = array();
        $allChilds = array_keys(users::getItems($user['user_id'], false, true, array('user_id')));
        if($allChilds){
            $sql = "SELECT SUBSTRING_INDEX(u.parent_tree, ',', {$userLevel}) AS direct_child,SUM(o.amount) AS total_commission FROM orders o LEFT JOIN users u ON o.to_user_id = u.user_id WHERE  u.parent_id != {$user['user_id']} AND o.type in (501,502)";
            if($allChilds){
                $sql .=" AND o.from_user_id IN (".implode(',', $allChilds).")";
            }
            if ($start_time != '') {
                $sql .= " AND o.create_time >= '$start_time'";
            }
            if ($end_time != '') {
                $sql .= " AND o.create_time <= '$end_time'";
            }
            $sql .= ' GROUP BY direct_child';
            //这里得出的是下级贡献的，所以就是自己团队的返佣数据
            $result2 = $GLOBALS['db']->getAll($sql, array(),'direct_child');
        }

// array(2) {
//   ["10100,10296"]=>
//   array(2) {
//     ["direct_child"]=>
//     string(11) "10100,10296"
//     ["total_commission"]=>
//     string(7) "12.0000"
//   }
//   ["10100,10309"]=>
//   array(2) {
//     ["direct_child"]=>
//     string(11) "10100,10309"
//     ["total_commission"]=>
//     string(7) "12.0000"
//   }
// }
        //3.合并结果
        foreach ($result1 as $val) {
            $result['team_total_commission'] += $val['total_commission'];
            $result[$user['user_id']]['total_commission'] += $val['total_commission'];
        }
        foreach ($result2 as $selfChildUserId => $v) {
            $tmp = explode(',', $selfChildUserId);
            $directUserId = end($tmp);//直属下级
            $result[$directUserId]['total_commission'] += $v['total_commission'];
            $result['team_total_commission'] += $v['total_commission'];
        }

        return $result;
    }

    static public function wrapId($id)    //, $issue, $lottery_id
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

        return 'O' . encode($id) . 'E';
    }

    static public function dewrapId($str)  //, $issue, $lottery_id
    {
        if (!preg_match('`^O(\w+)E$`Ui', $str, $match)) {
            return 0;
        }
        $result = decode($match[1]);
        if (!is_numeric($result)) {
            return 0;
        }

        return $result;
    }

    //批量插入提高速度
    static public function addItems($datas)
    {
        if (!is_array($datas)) {
            throw new exception2('参数无效', 3409);
        }
        if (count($datas) == 0) {
            return true;
        }
        $tmp = array();
        foreach ($datas as $v) {
            $tmp[] = "('{$v['lottery_id']}','{$v['issue']}','{$v['from_user_id']}','{$v['from_username']}','{$v['to_user_id']}','{$v['to_username']}','{$v['type']}','{$v['amount']}','{$v['pre_balance']}','{$v['balance']}','{$v['create_time']}','{$v['business_id']}','{$v['admin_id']}')";
        }
        $sql = "INSERT INTO orders (lottery_id,issue,from_user_id,from_username,to_user_id,to_username,type,amount,pre_balance,balance,create_time,business_id,admin_id) VALUES " . implode(',', $tmp);

        return $GLOBALS['db']->query($sql, array(), 'i');
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('orders', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('orders',$data,array('order_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM orders WHERE order_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}

?>