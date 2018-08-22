<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 转帐记录模型
 */
class transfers
{
    static public function getItem($id, $status = NULL)
    {
        $sql = 'SELECT * FROM transfers WHERE transfer_id = ' . intval($id);
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    // $action为cdeposit或cwithdrawal
    static public function getItems($username = '', $include_childs = 0, $action = '', $status = -1, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.username FROM transfers a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($username != '') {
            if ($include_childs == 0) {
                $sql .= " AND b.username = '$username'";
            }
            else {
                //先得到用户id
                if (!$user = users::getItem($username)) {
                    return array();
                }
                $sql .= " AND FIND_IN_SET('{$user['user_id']}',b.parent_tree)";
            }
        }
        if ($action != '') {
            $sql .= " AND a.action = '$action'";
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }

        $sql .= ' ORDER BY a.transfer_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql, array(),'transfer_id');

        return $result;
    }

    static public function getTrafficInfo($username = '', $include_childs = 0, $action = '', $status = -1, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount FROM transfers a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($username != '') {
            if ($include_childs == 0) {
                $sql .= " AND b.username = '$username'";
            }
            else {
                //先得到用户id
                if (!$user = users::getItem($username)) {
                    return array();
                }
                $sql .= " AND FIND_IN_SET('{$user['user_id']}',b.parent_tree)";
            }
        }
        if ($action != '') {
            $sql .= " AND a.action = '$action'";
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('invalid args');
        }

        $sql = 'SELECT * FROM transfers WHERE transfer_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'transfer_id');
    }

    /**
     * by KK  2013-05-22
     * @param type $platform 代表平台默认全部
     * @param type $start_time
     * @param type $end_time
     * @return array
     */
    static public function getTransferDiff($platform = -1, $start_time = '', $end_time = '')
    {
        $sql = 'SELECT top_id, SUM(amount) AS total_amount FROM transfers WHERE status = 5';
        if ($platform != -1) {
            $sql .= " AND platform = " . intval($platform);
        }
        if ($start_time != '') {
            $sql .= " AND create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND create_time <= '$end_time'";
        }
        $sql .= " GROUP BY top_id ORDER BY top_id ASC";

        return $GLOBALS['db']->getAll($sql, array(),'top_id');
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

        return 'TR' . encode($id) . 'E';
    }

    static public function dewrapId($str)  //, $issue, $lottery_id
    {
        if (!preg_match('`^TR(\w+)E$`Ui', $str, $match)) {
            return 0;
        }
        $result = decode($match[1]);
        if (!is_numeric($result)) {
            return 0;
        }

        return $result;
    }

    static public function getTransfer($element_id)
    {
        $sql = "SELECT * FROM transfers WHERE element_id = '$element_id'";

        return $GLOBALS['db']->getRow($sql);
    }

    //波音->商户
    static public function transferOutBBIN($user_id, $amount)
    {
        if (!$user = users::getItem($user_id, 8)) { //这里没有加锁的原因是因为最后才为用户加钱
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        //必须是正整数
        if (!is_int($amount) || $amount <= 0) {
            $GLOBALS['db']->rollback();
            throw new exception2('金额必须是大于0的整数');
        }

        log2("******** BBIN->商户 开始 用户:{$user['username']} 金额:{$amount} ********");

        //先查下余额吧，不够就没必要下一步了，转完要再核对一次，看余额是否正确扣除，如果不是取消操作
        $BBIN = new BBIN();
        //先查下余额吧，转完要再核对一次，看余额是否正确加上，如果不是取消操作
        $bbinBalance = $BBIN->checkBalance($user);
        if ($bbinBalance < 0) {
            $GLOBALS['db']->rollback();
            throw new exception2('由于您的波音额度没有正确获取，无法转帐');
        }
        if ($bbinBalance < $amount) {
            throw new exception2("转出失败！您在波音的余额不足$amount");
        }

        //余额足够时，插入待定转帐记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'platform' => 2, //1EA 2波音
            'amount' => $amount,
            'element_id' => '',
            'action' => 'OUT',
            'paymentid' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] + $amount, 4),
            'status' => 1, //对于存款：1发送待定请求 2发送失败 3成功接收到待定回应请求，并再次发送确认回应 4发送失败 5发送成功 对于取款不需要再次发送确认函
            'remark' => '',
        );
        if (!transfers::addItem($data)) {
            log2("从波音转出 失败DBN错误（-210）");
            throw new exception2("DBN错误（-210）");
        }
        $transfer_id = $GLOBALS['db']->insert_id();
        //转帐接口操作
        $flag = $BBIN->transferOut($transfer_id, $user, $amount, $data['pre_balance'], $data['balance']);

        //再次检查余额，看是否正确
        $bbinBalance2 = $BBIN->checkBalance($user);
        //bugfix 整型和浮点型相减，精度不一致，必须四舍五入才能比较，否则出现1.05 - 1.01 != 0.04的情况
        if ($bbinBalance2 >= 0 && $flag == 0 && round($bbinBalance - $amount, 4) == round($bbinBalance2, 4)) {
            log2("{$user['username']} 从波音转出成功！转帐前本地余额{$user['balance']}，bbin余额{$bbinBalance}，转出{$amount}，转帐后本地余额" . ($user['balance'] + $amount) . "，bbin余额{$bbinBalance2}");
        }
        else {
            log2("{$user['username']} 从波音转出失败！flag={$flag}，将立即退出，避免给本地余额加钱。转帐前本地余额{$user['balance']}，bbin余额{$bbinBalance}，转出{$amount}，转出后bbin余额{$bbinBalance2}");
            //这里不同于转入，那边如果出错应中止，避免后面的给帐户加钱
            throw new exception2("从波音转出 操作出错错误号：{$flag}，请联系客服");
        }

        if ($flag != 0) {
            switch ($flag) {
                case -200:
                    $msg = '网络通信错误';
                    break;
            }
            //不用列了，直接给错误号吧，自己明白就行了，也不想让客户知道错误细节
            throw new exception2("从波音转出 操作出错错误号：{$flag}，请联系客服");
        }

        //对于转出，应该转成功才记录帐变
        //开始事务
        $GLOBALS['db']->startTransaction();
        //1.增加帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 161, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => $amount,
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] + $amount, 4),
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("从波音转出 失败数据库错误");
            throw new exception2('从波音转出 DBN错误（-210）');
        }

        //2.更新转帐状态为“已成功”
        if (!transfers::updateItem($transfer_id, array('status' => 5))) {
            $GLOBALS['db']->rollback();
            throw new exception2("本地数据错误（-220）");
        }

        //最后再为客户加钱
        //flag返回0表示转帐成功，更新本地余额及相关状态 当本地数据库出错这里就必须抛异常了，不能再发确认函不然那边加钱了这边还没扣掉钱
        if (!users::updateBalance($user['user_id'], $amount)) {
            $GLOBALS['db']->rollback();
            log2("转出失败本地数据错误（-210）");
            throw new exception2("本地数据错误（-230）");   //-20x表本地数据的错误 更新余额失败
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            log2("转出失败事务提交出错");
            throw new exception2('事务提交出错');
        }

        return true;
    }

    //商户->PT
    static public function transferInPT($user_id, $amount)
    {
        //bugfix 事务应从这里开始
        //开始事务
        $GLOBALS['db']->startTransaction();

        if (!$user = users::getItem($user_id, 8, true)) {   //注意：这里加锁了
            throw new exception2("非法请求，该用户不存在或已被冻结");
        }

        //必须是正整数
        if (!is_int($amount) || $amount < 10) {
            $GLOBALS['db']->rollback();
            throw new exception2('金额必须是大于10的整数');
        }
        if ($user['balance'] <= 0 || $amount > $user['balance']) {
            $GLOBALS['db']->rollback();
            throw new exception2('转入金额不能超过目前余额（' . $user['balance'] . "）");
        }

        //1.插入待定转帐记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'platform' => 3, //1EA 2波音 3pt
            'amount' => -$amount,
            'element_id' => '',
            'action' => 'IN', //IN转入 OUT转出
            'paymentid' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] - $amount, 4),
            'status' => 1, //对于存款：1发送待定请求 2发送失败 3成功接收到待定回应请求，并再次发送确认回应 4发送失败 5发送成功 对于取款不需要再次发送确认函
            'remark' => '',
        );
        if (!transfers::addItem($data)) {
            log2("转入失败DBN错误（-210）");
            $GLOBALS['db']->rollback();
            throw new exception2("DBN错误（-210）");
        }
        $transfer_id = $GLOBALS['db']->insert_id();

        //2.增加帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 213, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$amount,
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] - $amount, 4),
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );

        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("转入失败DBN错误（-220）");
            throw new exception2('DBN错误（-220）');
        }

        //3.扣除本地余额
        if (!users::updateBalance($user['user_id'], -$amount)) {
            $GLOBALS['db']->rollback();
            log2("转入失败DBN错误（-230）");
            throw new exception2("DBN错误（-230）");   //-20x表本地数据的错误 更新余额失败
        }

        log2("******** 商户->PT 开始 用户:{$user['username']} 金额:{$amount} ********");

        //调接口转帐 不计入事务
        $PT = new PT();
        $ref_id = "D" . date('YmdHis00') . rand(1000, 9999) . "E";
        $result = $PT->transferIn($user['pt_username'], $amount, $ref_id);
        if (!$result) {
            throw new exception2('DBN错误（-220）');
        }

        //为保险起见，转帐成功或其他任何错误都必须提交事务扣钱，特别是网络超时，请求已经发送，但未正确回应，而波音那边已经充值
        if (!$GLOBALS['db']->commit()) {
            log2("转入失败事务提交出错");
            $GLOBALS['db']->rollback();
            throw new exception2('事务提交出错');
        }
        //前面没抛异常，表示转帐“已成功”
        if (!transfers::updateItem($transfer_id, array('status' => 5))) {
            log2("转入失败错误号：-230");
            throw new exception2("本地数据错误错误号：-230");
        }

        return true;
    }

    //PT->商户
    static public function transferOutPT($user_id, $amount)
    {
        if (!$user = users::getItem($user_id, 8)) { //这里没有加锁的原因是因为最后才为用户加钱
            throw new exception2("非法请求，该用户不存在或已被冻结");
        }

        //必须是正整数
        if (!is_int($amount) || $amount < 10) {
            $GLOBALS['db']->rollback();
            throw new exception2('金额必须是大于10的整数');
        }

        /* ------------------------------------pt活动临时代码--------------------------------------- */
        /* ------------------------------------pt活动常量--------------------------------------- */
//活动标题
        $pthuodongTitle = "休闲游戏震撼上线,彩票投注免费获得休闲游戏币";
//活动投注计算起始时间点,活动图标开始显示的时间
        $pthuodongStartCount = '2015-02-12 00:00:00';
//活动投注计算终止时间点
        $pthuodongEndCount = '2015-02-25 23:59:59';
//领取的开始时间
        $pthuodongStartGet = '2015-02-13 00:00:00';
//领取的终止时间
        $pthuodongEndGet = '2015-02-26 23:59:59';
//pt流水的开始时间
        $pthuodongStartBet = '2015-02-13 00:00:00';
//pt流水的终止时间
        $pthuodongEndBet = '2015-03-03 23:59:59';
//活动的结束日期,用于判断一些逻辑是否进入运行
        $pthuodongLastDate = '2015-03-05 23:59:59';
        /* ===============pt活动常量================ */
        if (time() <= strtotime($pthuodongLastDate)) {
            $userGifts = userGifts::getItems('', '', $user_id, '', '', $pthuodongTitle);
            if ($userGifts) {
                if (count($userGifts) > 1) {
                    throw new exception2("用户领取红包金额大于1");
                }
                $nowGet = $userGifts[0]['gift'];
                //正在进行
                if ($userGifts[0]['status'] == 1) {
                    $PT = new PT();
                    $result = $PT->getPlayerInfo($user['pt_username']);
                    if ($result && $result['errno'] == 0) {
                        $ptBalance = $result['balance'];
                        $maxTranMoney = ($ptBalance - $nowGet) <= 0 ? 0 : ($ptBalance - $nowGet);
                        if ($maxTranMoney < $amount) {
                            throw new exception2("您正在参加活动<<{$pthuodongTitle}>>,目前只能转移小于{$maxTranMoney}元.");
                        }
                    }
                    else {
                        throw new exception2("休闲游戏查询余额错误.");
                    }
                }
            }
        }
        /* ===============pt活动临时代码================ */

        log2("******** PT->商户 开始 用户:{$user['username']} 金额:{$amount} ********");
        //开始事务
        $GLOBALS['db']->startTransaction();
        //先查下余额吧，不够就没必要下一步了，转完要再核对一次，看余额是否正确扣除，如果不是取消操作
        //余额足够时，插入待定转帐记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'platform' => 3, //1EA 2波音 3PT
            'amount' => $amount,
            'element_id' => '',
            'action' => 'OUT',
            'paymentid' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] + $amount, 4),
            'status' => 1, //对于存款：1发送待定请求 2发送失败 3成功接收到待定回应请求，并再次发送确认回应 4发送失败 5发送成功 对于取款不需要再次发送确认函
            'remark' => '',
        );
        if (!transfers::addItem($data)) {
            log2("从波音转出 失败DBN错误（-210）");
            throw new exception2("DBN错误（-210）");
        }
        $transfer_id = $GLOBALS['db']->insert_id();
        $PT = new PT();
        $ref_id = "D" . date('YmdHis00') . rand(1000, 9999) . "E";
        $result = $PT->transferOut($user['pt_username'], $amount, $ref_id);
        if (!$result) {
            throw new exception2('DBN错误（-220）');
        }

        //对于转出，应该转成功才记录帐变
        //1.增加帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 162, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => $amount,
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] + $amount, 4),
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("从波音转出 失败数据库错误");
            throw new exception2('从波音转出 DBN错误（-210）');
        }

        //2.更新转帐状态为“已成功”
        if (!transfers::updateItem($transfer_id, array('status' => 5))) {
            $GLOBALS['db']->rollback();
            throw new exception2("本地数据错误（-220）");
        }

        //最后再为客户加钱
        //flag返回0表示转帐成功，更新本地余额及相关状态 当本地数据库出错这里就必须抛异常了，不能再发确认函不然那边加钱了这边还没扣掉钱
        if (!users::updateBalance($user['user_id'], $amount)) {
            $GLOBALS['db']->rollback();
            log2("转出失败本地数据错误（-210）");
            throw new exception2("本地数据错误（-230）");   //-20x表本地数据的错误 更新余额失败
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            log2("转出失败事务提交出错");
            throw new exception2('事务提交出错');
        }

        return true;
    }

    //商户->波音
    static public function transferInBBIN($user_id, $amount)
    {
        //bugfix 事务应从这里开始
        //开始事务
        $GLOBALS['db']->startTransaction();

        if (!$user = users::getItem($user_id, 8, true)) {   //注意：这里加锁了
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        //必须是正整数
        if (!is_int($amount) || $amount <= 0) {
            $GLOBALS['db']->rollback();
            throw new exception2('金额必须是大于0的整数');
        }
        if ($user['balance'] <= 0 || $amount > $user['balance']) {
            $GLOBALS['db']->rollback();
            throw new exception2('转入金额不能超过目前余额（' . $user['balance'] . "）");
        }

        //1.插入待定转帐记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'platform' => 2, //1EA 2波音
            'amount' => -$amount,
            'element_id' => '',
            'action' => 'IN', //IN转入 OUT转出
            'paymentid' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] - $amount, 4),
            'status' => 1, //对于存款：1发送待定请求 2发送失败 3成功接收到待定回应请求，并再次发送确认回应 4发送失败 5发送成功 对于取款不需要再次发送确认函
            'remark' => '',
        );
        if (!transfers::addItem($data)) {
            log2("转入失败DBN错误（-210）");
            $GLOBALS['db']->rollback();
            throw new exception2("DBN错误（-210）");
        }
        $transfer_id = $GLOBALS['db']->insert_id();

        //2.增加帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 211, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$amount,
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] - $amount, 4),
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );

        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("转入失败DBN错误（-220）");
            throw new exception2('DBN错误（-220）');
        }

        //3.扣除本地余额
        if (!users::updateBalance($user['user_id'], -$amount)) {
            $GLOBALS['db']->rollback();
            log2("转入失败DBN错误（-230）");
            throw new exception2("DBN错误（-230）");   //-20x表本地数据的错误 更新余额失败
        }

        log2("******** 商户->BBIN 开始 用户:{$user['username']} 金额:{$amount} ********");

        //调接口转帐 不计入事务
        $BBIN = new BBIN();
        //先查下余额吧，转完要再核对一次，看余额是否正确加上，如果不是取消操作
        $bbinBalance = $BBIN->checkBalance($user);
        if ($bbinBalance < 0) {
            $GLOBALS['db']->rollback();
            throw new exception2('由于您的波音额度没有正确获取，无法转帐');
        }
        //调用之前可以rollback()，之后就必须commit()了，有个特例除外：客户正在游戏(211)，因为可能网络问题导致程序中止，即钱还没扣，但EA那边已经加了钱，这很不安全
        $flag = $BBIN->transferIn($transfer_id, $user, $amount, $data['pre_balance'], $data['balance']);

        //为保险起见，转帐成功或其他任何错误都必须提交事务扣钱，特别是网络超时，请求已经发送，但未正确回应，而波音那边已经充值
        if (!$GLOBALS['db']->commit()) {
            log2("转入失败事务提交出错");
            $GLOBALS['db']->rollback();
            throw new exception2('事务提交出错');
        }

        //再次检查余额，看是否正确
        $bbinBalance2 = $BBIN->checkBalance($user);
        if ($bbinBalance2 >= 0 && $flag == 0 && round($bbinBalance + $amount, 4) == $bbinBalance2) {
            log2("{$user['username']} 转入成功！转帐前本地余额{$user['balance']}，bbin余额{$bbinBalance}，转入{$amount}，转帐后本地余额" . ($user['balance'] - $amount) . "，bbin余额{$bbinBalance2}");
        }
        else {
            log2("{$user['username']} 转入失败！flag={$flag}，转帐前本地余额{$user['balance']}，bbin余额{$bbinBalance}，转入{$amount}，转帐后本地余额" . ($user['balance'] - $amount) . "，bbin余额{$bbinBalance2}");
        }

        //其他任何错误均抛异常
        if ($flag != 0) {
            switch ($flag) {
                case -200:
                    $msg = '网络通信错误';
                    break;
            }
            //不用列了，直接给错误号吧，自己明白就行了，也不想让客户知道错误细节
            throw new exception2("转入波音 操作出错错误号：{$flag}");
        }

        //前面没抛异常，表示转帐“已成功”
        if (!transfers::updateItem($transfer_id, array('status' => 5))) {
            log2("转入失败错误号：-230");
            throw new exception2("本地数据错误错误号：-230");
        }

        return true;
    }

    // 向 MW 转入
    static public function transferInMW($user_id, $transferAmount)
    {
        // 开始事务
        $GLOBALS['db']->startTransaction();

        if (!$user = users::getItem($user_id, 8, true)) {
            // 注意：这里加锁了
            throw new exception2("非法请求，该用户不存在或已被冻结");
        }

        // 必须是正整数
        if (!is_int($transferAmount) || $transferAmount < 10) {
            $GLOBALS['db']->rollback();
            throw new exception2('金额必须是大于 10 的整数');
        }

        if ($user['balance'] <= 0 || $transferAmount > $user['balance']) {
            $GLOBALS['db']->rollback();
            throw new exception2('转入金额不能超过目前余额（' . $user['balance'] . "）");
        }

        // 1插入待定转帐记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'platform' => 4, // 1EA 2波音 3PT 4MW
            'amount' => -$transferAmount,
            'element_id' => '',
            'action' => 'IN', // IN转入 OUT转出
            'paymentid' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] - $transferAmount, 4),
            'status' => 1, // 对于存款：1发送待定请求 2发送失败 3成功接收到待定回应请求，并再次发送确认回应 4发送失败 5发送成功 对于取款不需要再次发送确认函
            'remark' => '',
        );

        if (!transfers::addItem($data)) {
            log2("转入失败 DBN 错误（-210）");
            $GLOBALS['db']->rollback();
            throw new exception2("DBN 错误（-210）");
        }

        $transfer_id = $GLOBALS['db']->insert_id();

        // 2增加帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 213, // 帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 213转入MW 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$transferAmount,
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] - $transferAmount, 4),
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );

        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("转入失败 DBN 错误（-220）");
            throw new exception2('DBN 错误（-220）');
        }

        // 3扣除本地余额
        if (!users::updateBalance($user['user_id'], -$transferAmount)) {
            $GLOBALS['db']->rollback();
            log2("转入失败 DBN 错误（-230）");
            throw new exception2("DBN 错误（-230）");   //-20x表本地数据的错误 更新余额失败
        }

        log2("******** 向 MW 转入开始 用户:{$user['username']} 金额:{$transferAmount} ********");

        // 调接口转帐 不计入事务
        $mw = new MW();
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
        $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $transferOrderNo = date('YmdHis');
        $transferNotifierUrl = $domain . "egame/transferPrepareCallback.php";
        $user = users::getItem($user_id);
        $transferPrepareResult = $mw->transferPrepare($user_id, $user['parent_id'], $user['top_id'], $user['username'], $transferAmount, $transferOrderNo, $transferNotifierUrl, 0);

        if ($transferPrepareResult['ret'] != 0000) {
            throw new exception2($transferPrepareResult['msg']);
        }

        $transferPayResult = $mw->transfer($transferPrepareResult['asinTransferOrderNo'], $transferPrepareResult['asinTransferDate'], $transferAmount, $transferOrderNo, $user['user_id'], $user['username']);

        if ($transferPrepareResult['ret'] != 0000) {
            throw new exception2($transferPayResult['msg']);
        }

        // 为保险起见，转帐成功或其他任何错误都必须提交事务扣钱，特别是网络超时，请求已经发送，但未正确回应，而波音那边已经充值
        if (!$GLOBALS['db']->commit()) {
            log2("转入失败事务提交出错");
            $GLOBALS['db']->rollback();
            throw new exception2('事务提交出错');
        }

        // 记录首次转入的时间
        $egameRakeback = egameRakeback::getItems($user_id);

        if (empty($egameRakeback)) {
            $data = array(
                'user_id' => $user_id,
                'first_transfer_time' => date('Y-m-d H:i:s'),
                'last_rakeback_time' => date('Y-m-d H:i:s'),
            );

            if (!egameRakeback::addItem($data)) {
                log2("记录首次转入的时间出错");
                $GLOBALS['db']->rollback();
                throw new exception2('记录首次转入的时间出错');
            }
        }

        // 前面没抛异常，表示转帐“已成功”
        if (!transfers::updateItem($transfer_id, array('status' => 5, 'element_id' => $transferOrderNo, 'paymentid' => $transferPrepareResult['asinTransferOrderNo']))) {
            log2("转入失败错误号：-230");
            throw new exception2("本地数据错误错误号：-230");
        }

        return true;
    }

    // 从 MW 转出
    static public function transferOutMW($user_id, $transferAmount)
    {
        if (!$user = users::getItem($user_id, 8)) {
            // 这里没有加锁的原因是因为最后才为用户加钱
            throw new exception2("非法请求，该用户不存在或已被冻结");
        }

        // 必须是正整数
        if (!is_int($transferAmount) || $transferAmount < 10) {
            $GLOBALS['db']->rollback();
            throw new exception2('金额必须是大于 10 的整数');
        }

        log2("******** 从 MW 转出开始，用户：{$user['username']}，金额：{$transferAmount} ********");

        // 开始事务
        $GLOBALS['db']->startTransaction();
        // 先查下余额吧，不够就没必要下一步了，转完要再核对一次，看余额是否正确扣除，如果不是取消操作
        // 余额足够时，插入待定转帐记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'platform' => 4, // 1EA 2波音 3PT 4MW
            'amount' => $transferAmount,
            'element_id' => '',
            'action' => 'OUT',
            'paymentid' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] + $transferAmount, 4),
            'status' => 1, // 对于存款：1发送待定请求 2发送失败 3成功接收到待定回应请求，并再次发送确认回应 4发送失败 5发送成功 对于取款不需要再次发送确认函
            'remark' => '',
        );

        if (!transfers::addItem($data)) {
            log2("从 MW 转出失败，DBN 错误（-210）");
            throw new exception2("DBN 错误（-210）");
        }

        $transfer_id = $GLOBALS['db']->insert_id();
        $mw = new MW();
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
        $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $transferOrderNo = date('YmdHis');
        $transferNotifierUrl = $domain . "egame/transferPrepareCallback.php";
        $transferPrepareResult = $mw->transferPrepare($user_id, $user['parent_id'], $user['top_id'], $user['username'], $transferAmount, $transferOrderNo, $transferNotifierUrl, 1);

        if ($transferPrepareResult['ret'] != 0000) {
            throw new exception2($transferPrepareResult['msg']);
        }

        $transferPayResult = $mw->transfer($transferPrepareResult['asinTransferOrderNo'], $transferPrepareResult['asinTransferDate'], $transferAmount, $transferOrderNo, $user['user_id'], $user['username']);

        if ($transferPrepareResult['ret'] != 0000) {
            throw new exception2($transferPayResult['msg']);
        }

        // 对于转出，应该转成功才记录帐变
        // 1增加帐变
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 162, // 帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => $transferAmount,
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] + $transferAmount, 4),
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );

        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("从 MW 转出失败，数据库错误");
            throw new exception2('从波音转出 DBN 错误（-210）');
        }

        // 2更新转帐状态为“已成功”
        if (!transfers::updateItem($transfer_id, array('status' => 5, 'element_id' => $transferOrderNo, 'paymentid' => $transferPrepareResult['asinTransferOrderNo']))) {
            $GLOBALS['db']->rollback();
            throw new exception2("本地数据错误（-220）");
        }

        // 最后再为客户加钱
        // flag 返回 0 表示转帐成功，更新本地余额及相关状态 当本地数据库出错这里就必须抛异常了，不能再发确认函不然那边加钱了这边还没扣掉钱
        if (!users::updateBalance($user['user_id'], $transferAmount)) {
            $GLOBALS['db']->rollback();
            log2("转出失败本地数据错误（-210）");
            throw new exception2("本地数据错误（-230）");   // -20x 表本地数据的错误 更新余额失败
        }

        // 看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            log2("转出失败事务提交出错");
            throw new exception2('事务提交出错');
        }

        return true;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->insert('transfers', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->updateSM('transfers', $data,array('transfer_id' => $id));
    }

    /**
     *  休闲游戏活动 转入pt，不经过余额转移
     * @param type $user_id
     * @param type $ip
     * @return boolean
     * @throws exception2
     */
    static public function pthuodongTransferInPT($user_id, $ip = '')
    {
        if (!$user = users::getItem($user_id, 8)) {   //注意：这里加锁了
            throw new exception2("非法请求，该用户不存在或已被冻结");
        }
        /* ------------------------------------pt活动常量--------------------------------------- */
//活动标题
        $pthuodongTitle = "休闲游戏震撼上线,彩票投注免费获得休闲游戏币";
//活动投注计算起始时间点,活动图标开始显示的时间
        $pthuodongStartCount = '2015-02-12 00:00:00';
//活动投注计算终止时间点
        $pthuodongEndCount = '2015-02-25 23:59:59';
//领取的开始时间
        $pthuodongStartGet = '2015-02-13 00:00:00';
//领取的终止时间
        $pthuodongEndGet = '2015-02-26 23:59:59';
//pt流水的开始时间
        $pthuodongStartBet = '2015-02-13 00:00:00';
//pt流水的终止时间
        $pthuodongEndBet = '2015-03-03 23:59:59';
//活动的结束日期,用于判断一些逻辑是否进入运行
        $pthuodongLastDate = '2015-03-05 23:59:59';
        /* ===============pt活动常量================ */
        $endTime = strtotime($pthuodongEndCount);
        if (time() < strtotime($pthuodongEndCount)) {
            $endTime = strtotime(date('Y-m-d 23:59:59', strtotime('-1 days')));
        }
        $reportDays = projects::getUsersDayPackages($pthuodongStartCount, date('Y-m-d H:i:s', $endTime), 0, $user_id);
        /**
         * 从开始到现在能获取的金额
         */
        $canGets = 0;
        foreach ($reportDays as $reportDay) {
//投注金额/天
//休闲游戏币
//588~3888
//6
//3888~5888
//38
//5888~8888
//58
//8888~38888
//88
//38888~58888
//388
//58888以上
//588
            if ($reportDay['total_amount'] > 58888) {
                $canGets+=588;
            }
            elseif ($reportDay['total_amount'] > 38888) {
                $canGets+=388;
            }
            elseif ($reportDay['total_amount'] > 8888) {
                $canGets+=88;
            }
            elseif ($reportDay['total_amount'] > 5888) {
                $canGets+=58;
            }
            elseif ($reportDay['total_amount'] > 3888) {
                $canGets+=38;
            }
            elseif ($reportDay['total_amount'] > 588) {
                $canGets+=6;
            }
        }
        $nowGet = 0;
        $amount = $canGets;
        $userGifts = userGifts::getItems('', '', $user_id, '', '', $pthuodongTitle);
        //开始事务
        $GLOBALS['db']->startTransaction();

        if ($userGifts) {
            if (count($userGifts) > 1) {
                throw new exception2("用户领取红包金额大于1");
            }
            $nowGet = $userGifts[0]['gift'];
            $amount = $amount - $nowGet;
            $remark = $userGifts[0]['remark'] . '---' . date('Y-m-d H:i:s') . ' 转入' . $amount;
            if (userGifts::updateItem($userGifts[0]['ug_id'], array('gift' => $canGets, 'min_total_water' => $canGets * 2, 'status' => 1,
                        'remark' => $remark)) === false) {
                throw new exception2("数据库错误，不能更新奖金");
            }
        }
        else {
            $giftData = array(
                'type' => 1,
                'title' => $pthuodongTitle,
                'user_id' => $user_id,
                'gift' => $amount,
                'from_time' => date('Y-m-d H:i:s'),
                'to_time' => $pthuodongEndBet,
                'min_total_water' => $amount * 2,
                'status' => 1,
                'apply_ip' => $ip,
                'remark' => date('Y-m-d H:i:s') . ' 转入' . $amount,
            );
            if (userGifts::addItem($giftData) === false) {
                throw new exception2("数据库错误，不能添加奖金");
            }
        }

        $userGifts = userGifts::getItems('', '', $user_id, '', '', $pthuodongTitle);
        if (!$userGifts) {
            throw new exception2("数据库错误，没有奖金");
        }
        $userGift = $userGifts[0];

        //必须是正整数
        if (!is_numeric($amount) || $amount <= 0) {
            throw new exception2("不能转入金额:$amount 元");
        }

        //1.插入待定转帐记录
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'top_id' => $user['top_id'],
            'platform' => 3, //1EA 2波音 3pt
            'amount' => -$amount,
            'element_id' => '',
            'action' => 'IN', //IN转入 OUT转出
            'paymentid' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'],
            'status' => 1, //对于存款：1发送待定请求 2发送失败 3成功接收到待定回应请求，并再次发送确认回应 4发送失败 5发送成功 对于取款不需要再次发送确认函
            'remark' => '',
            'gift_id' => $userGift['ug_id'],
        );
        if (!transfers::addItem($data)) {
            log2("转入失败DBN错误（-210）");
            $GLOBALS['db']->rollback();
            throw new exception2("DBN错误（-210）");
        }
        $transfer_id = $GLOBALS['db']->insert_id();

        //2.加一个红包
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 105, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => $amount,
            'pre_balance' => $user['balance'],
            'balance' => round($user['balance'] + $amount, 4),
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );

        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("转入失败DBN错误（-220）");
            throw new exception2('DBN错误（-220）');
        }

        //2.转出
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 213, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 161从波音转出 201提现 211转入波音 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$amount,
            'pre_balance' => round($user['balance'] + $amount, 4),
            'balance' => $user['balance'],
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $transfer_id,
            'admin_id' => 0,
        );

        if (!orders::addItem($orderData)) {
            $GLOBALS['db']->rollback();
            log2("转入失败DBN错误（-220）");
            throw new exception2('DBN错误（-220）');
        }

        //调接口转帐 不计入事务
        $PT = new PT();
        $ref_id = "D" . date('YmdHis00') . rand(1000, 9999) . "E";
        $result = $PT->transferIn($user['pt_username'], $amount, $ref_id);
        if (!$result) {
            log2("pt api transferIn失败");
            $GLOBALS['db']->rollback();
            throw new exception2('DBN错误（-220）');
        }
        if (!$GLOBALS['db']->commit()) {
            log2("转入失败事务提交出错");
            $GLOBALS['db']->rollback();
            throw new exception2('事务提交出错');
        }
        //前面没抛异常，表示转帐“已成功”
        if (!transfers::updateItem($transfer_id, array('status' => 5))) {
            log2("转入失败错误号：-230");
            throw new exception2("本地数据错误错误号：-230");
        }

        return true;
    }

    /**
     * author snow  游戏记录
     * @param $options
     * @return array
     */
    public static function getTransfersList($options)
    {
        //»设计条件
        $where = '';
        if(!empty($options['startDate'])){
            //»添加时间筛选条件
            $where .= "  AND (create_time BETWEEN '{$options['startDate']}' AND '{$options['endDate']}')";
        }

        //»用户和所属总代,只能有一个条件使用
        if(!empty($options['userName'])){
            //»添加用户筛选条件
            $where .= " AND (username = '{$options['userName']}'";

            //»如果需要包含下级
            if($options['include_childs'] == 1){
                //»查询当前用户的所有下级
                $userChildsStr = self::_getUserAllChilds($options['userName']);
                if($userChildsStr !== false){
                    $where .= " OR user_id IN {$userChildsStr}";
                }
            }
            $where .= ")";
        }else{
            if(!empty($options['top_id'])){
                //»添加所属总代筛选条件
                $where .= " AND top_id = '{$options['top_id']}'";
            }
        }

        //»处理金额选项  如果开始金额大于结束金额, 交换两个 的值.
        if($options['amount_start'] > $options['amount_end']){
            list($options['amount_start'], $options['amount_end']) = [$options['amount_end'], $options['amount_start']];
        }

        if(empty($options['amount_start'])){
            if(!empty($options['amount_end'])){
                $where .= " AND (amount = '{$options['amount_end']}')";
            }
        }else{
            //»只有一个为空
            $where .= " AND (amount BETWEEN '{$options['amount_start']}' AND '{$options['amount_end']}')";

        }


        if(!empty($options['status'])){
            //»添加状态筛选条件
            $where .= " AND status = '{$options['status']}'";
        }

        if(!empty($options['action'])){
            //»添加账变类型筛选条件
            $where .= " AND action = '{$options['action']}'";
        }

        if(!empty($options['platform'])){
            //»添加平台筛选条件
            $where .= " AND platform = '{$options['platform']}'";
        }

        if(($options['transfer_id']) != 0){
            //»添加订单编号筛选条件
            $where = " AND transfer_id = '{$options['transfer_id']}'";
        }

        $where .= ' ORDER BY create_time DESC';

        //»设计获取总金额与总条数
        $sql_total = <<<SQL
SELECT COUNT(*) AS totalConut, SUM(amount) AS totalAmount FROM transfers WHERE 1 {$where}
SQL;

        $totalResult = $GLOBALS['db']->getAll($sql_total);
        $totalCount  = 0;
        $totalAmount = 0;
        if($totalResult && is_array($totalResult) && !empty($totalResult)){
            //»如果有数据
            $totalResult = array_shift($totalResult);
            $totalCount = $totalResult['totalConut'];//»总条数
            $totalAmount = $totalResult['totalAmount'];//»总金额
        }
        //>>添加获取正确分页起始页.
        $options['startNum'] = getStartOffset($options['curPage'], $totalCount, $options['pageSize']);
        //»设计 分页数据
        $pageSql =<<<SQL
SELECT * FROM transfers WHERE 1 {$where} LIMIT {$options['startNum']},{$options['pageSize']}
SQL;
        $pageResult = $GLOBALS['db']->getAll($pageSql);


        //»获取当前数据表中的所有总代数据
        $topUsersSql =<<<SQL
SELECT DISTINCT a.top_id,b.userName FROM transfers AS a INNER JOIN users AS b ON a.top_id = b.user_id
SQL;
        $topUsers = $GLOBALS['db']->getAll($topUsersSql);

        //»添加状态常量
        $status   = [
            1  => '发送待定请求',
            2  => '发送失败',
            3  => '成功接收到待定回应请求，并再次发送确认回应',
            4  => '发送失败',
            5  => '成功',
        ];
        //»添加平台常量
        if(isset($GLOBALS['cfg']['gameTypes']) ){
            $platform = $GLOBALS['cfg']['gameTypes'];
        }else{
            //»暂时在这里写
            $platform = [
                1   => '彩票',
                4   => '2MW电子',
            ];
        }

        //»添加账变类型常量
        if(isset($GLOBALS['cfg']['egameAction']) ){
            $egameAction = $GLOBALS['cfg']['egameAction'];
        }else{
            //»暂时在这里写
            $egameAction = ['IN', 2   => 'OUT'];
        }
        return [
            'totalConut'    => $totalCount,
            'totalAmount'   => $totalAmount,
            'topUsers'      => $topUsers,
            'status'        => $status,
            'egameAction'   => $egameAction,
            'platform'      => $platform,
            'pageResult'    => is_array($pageResult) && !empty($pageResult) ? $pageResult : false,
        ];

    }

    public static function _getUserAllChilds($userName)
    {
        //»根据用户名获取用户id
        $sql =<<<SQL
SELECT user_id,username FROM users WHERE username = '{$userName}'
SQL;
        $user = $GLOBALS['db']->getRow($sql);
        if($user && is_array($user) && !empty($user)){
            $userId = $user['user_id'];
            self::_getUserChilds($userId);
            if(!empty(self::$childsData)){
                $result = self::$childsData;
                $data = [];
                foreach($result as $key => $val){
                    $data[] = $val['user_id'];
                }
                return "(" .  implode(',',$data) . ")";
            }
        }

        return false;
    }

    /**
     * 根据用户id查询出所有用户的下级
     * @param $userId
     * @return string
     */
    public static $childsData = [];//»定义一个静态变量,用来保存用户下级数据

    private static function _getUserChilds($userId)
    {
        $sql =<<<SQL
SELECT user_id FROM users WHERE parent_id = '{$userId}'
SQL;
        $result = $GLOBALS['db']->getAll($sql);

        if($result && is_array($result) && !empty($result)){
            self::$childsData = array_merge(self::$childsData,$result);
            foreach($result as $key => $val){
                self::_getUserChilds($val['user_id']);
            }
        }
    }

}

?>