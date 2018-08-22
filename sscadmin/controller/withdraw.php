<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：提款管理
 */
class withdrawController extends sscAdminController
{

    const MACHINE_ID = 65535; //机器处理的admin_id为65535
    const RECENT_DW_DAYS = 7;   //最近需要显示的存提记录天数

    //方法概览

    public $titles = array(
        'withdrawList' => '提款列表',
        'checkNew' => '检查新提案',
        'viewWithdraw' => '查看提款提案',
        'acceptRequest' => '我要受理',
        'verify' => '审核提案',
        'pay' => '执行提案',
        'cancel' => '取消提案',
        'addWithdraw' => '手工添加提款提案',
        'reset2verified' => '重置为已审核状态',
        'reset2pay' => '重置为已支付',
        'quickVerify' => '一键审核',
        'quickCancel' => '一键拒绝',
        'deleteWithdrawMany' => '批量删除',
        'auditAjax'=>'点击稽核'
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //ajax调用 检查是否有新的“未处理”提案
    public function checkNew()
    {
        $withdraws = withdraws::getItems('', 0, -1, 0);
        $result = array('newNum' => count($withdraws));

        echo json_encode($result);
    }

    //提款列表
    public function withdrawList()
    {

        /***************** author snow **************************/

        $back_url = $GLOBALS['SESSION']['withdraw_back_url'];
        if ($back_url) {
            //>>如果存在,就把它置为空
            $GLOBALS['SESSION']['withdraw_back_url'] = null;
        }
        /***************** author snow **************************/
        self::$view->setVar('errorList', withdraws::getWithdrawErrors());

        $locations = [['title' => '返回提款列表', 'url' => url('withdraw', 'withdrawList')]];

        //$agent_id = -1, $user_id = 0, $trade_type = 0, $pay_bank_id = 0, $pay_card_id = '', $startDate = '', $endDate = '', $status = -1
        if ($reset_withdraw_id = $this->request->getGet('reset_withdraw_id', 'intval')) {
            if (!$withdraw = withdraws::getItem($reset_withdraw_id)) {
                showMsg("找不到提案！", 1, $locations);
            }
            //如果自己点进去且状态是“已受理”状态时，允许通过返回来重置为“未处理”
            if ($withdraw['verify_admin_id'] == $GLOBALS['SESSION']['admin_id'] && $withdraw['status'] == 1) {
                $data = array('verify_admin_id' => 0, 'start_time' => '0000-00-00 00:00:00', 'status' => '0');

                if (!withdraws::updateItem($reset_withdraw_id, $data)) {
                    showMsg("重置状态失败！数据库错误！", 1, $locations);
                }
            }
        }

        $top_username = $this->request->getGet('top_username', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        //>> snow 测试条件 默认为否
        $is_test = $this->request->getGet('is_test', 'intval', 0);
        $bank_id = $this->request->getGet('bank_id', 'intval', 0);  //用户提现银行
        $pay_bank_id = $this->request->getGet('pay_bank_id', 'intval', 0);
        $pay_card_id = $this->request->getGet('pay_card_id', 'intval', 0);
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d 00:00:00"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $withdraw_default_status = config::getConfig('withdraw_default_status');
        $withdrawDefaultStatus = $withdraw_default_status === null ? -1 : $withdraw_default_status;
        $status = $this->request->getGet('status', 'intval', $withdrawDefaultStatus);
        $withdraw_default_page_size = config::getConfig('withdraw_default_page_size');
        $withdrawDefaultPageSize = $withdraw_default_page_size === null ? 60 : $withdraw_default_page_size;
        $pageSize = $this->request->getGet('page_size', 'intval', $withdrawDefaultPageSize);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $startAmount = $this->request->getGet('start_amount', 'string' ,'');
        $endAmount = $this->request->getGet('end_amount', 'string','');
        if(!$startAmount && $startAmount<0 && !preg_match('/^\d+$/', $startAmount) && !preg_match('/^\d+\.\d{1,2}$/', $startAmount)){
            showMsg('起始金额错误！');
        }
        if(!$endAmount && $endAmount<0 && !preg_match('/^\d+$/', $endAmount) && !preg_match('/^\d+\.\d{1,2}$/', $endAmount)){
            showMsg('结束金额错误！');
        }
        if(!$startAmount && $startAmount>$endAmount) showMsg("起始金额不能大于结束金额!");

        //>>添加排除dcsite 数据条件
        $options = [
            'user_name'         => $top_username ? $top_username : $username,
            'include_childs'    => $include_childs,
            'is_test'           => $is_test,
            'status'            => $status,
            'pay_bank_id'       => $pay_bank_id,
            'pay_card_id'       => $pay_card_id,
            'bank_id'           => $bank_id,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'start'             => -1,  //>>-1 不分页
            'start_amount'      => $startAmount,
            'end_amount'        => $endAmount,
        ];
        $trafficInfo = withdraws::getTrafficInfoLExclude($options);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        //>>修改起始值.
        $options['amount'] = $pageSize;
        $options['start'] = getStartOffset($curPage, $trafficInfo['count'], $pageSize);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/

        $withdraws = withdraws::getItemsExclude($options);
        /**
         * 打码状态
         * 这里将原本同为一列的打码状态改为四列,分别为:时间(充值或提款),充值量,打码量,打码状态,(4项对应2个条件2个种情况)
         * 将traffic_status从普通文本改为数组,分别对应datetime,deposit,betAmount,status,
         */
//        foreach ($withdraws as $k => $v) {
//            if ($v['level'] != 0) {
//                $withdraws[$k]['traffic_status'] = ['lastWithdraw' => '', 'deposit' => '', 'betAmount' => '', 'status' => ''];
//                $thisWithdrawDateTime = $v['create_time'];
//                $user_id = $v['user_id'];
//                $lastDepositDateTime = '';
//                if ($userDeposits = deposits::getUserDeposits($user_id, '', $thisWithdrawDateTime)) {//查询此次提款之前的存款记录
//                    $lastDepositDateTime = $userDeposits[0]['finish_time'];
//                } else {
//                    $withdraws[$k]['traffic_status']['status'] = '<font color="red">没有存款记录或余额不足</font>';
//                    continue;
//                }
//
//
//                $packages = projects::getPackages(0, -1, -1, '', -1, 0, $user_id, 0, '', '', $lastDepositDateTime, $thisWithdrawDateTime, 0);
//                $egamePackages = egameMwSiteUsergamelog::getUserLogsSum($lastDepositDateTime, $thisWithdrawDateTime, $is_test, $user_id, XY_PREFIX, DEFAULT_PER_PAGE);
//
//                $lastBet = 0;
//                foreach ($packages as $value) {
//                    $lastBet += $value['amount'];
//                }
//
//                if (!empty($egamePackages) && isset($egamePackages[0]['total_play_money']) && $egamePackages[0]['total_play_money'] != null) {
//                    $lastBet += $egamePackages[0]['total_play_money'];
//                }
//
//                // 条件不符合单一时间段限制
//                if ($lastBet < $userDeposits[0]['amount']) {
//                    $withdraws[$k]['traffic_status'] = [
//                        'datetime' => '上次充值时间<font color="red">' . $lastDepositDateTime . '</font>',
//                        'deposit' => '<font color="red">' . $userDeposits[0]['amount'] . '</font>元',
//                        'betAmount' => '到本次取款打码量<font color="red">' . $lastBet . '</font>元',
//                        'status' => '未完成本次充值额度一倍'
//                    ];
//                    continue;
//                }
//
//                $lastWithdrawDateTime = '';
//
//                if ($lastSuccessWithdrawInfo = withdraws::getLastSuccessWithdrawInfo($user_id, '', $thisWithdrawDateTime)) {
//                    $lastWithdrawDateTime = $lastSuccessWithdrawInfo['finish_time'];
//                }
//
//                $packages = projects::getPackages(0, -1, -1, '', -1, 0, $user_id, 0, '', '', $lastWithdrawDateTime, $thisWithdrawDateTime, 0);
//                $egamePackages = egameMwSiteUsergamelog::getUserLogsSum($lastWithdrawDateTime, $thisWithdrawDateTime, $is_test, $user_id, XY_PREFIX, DEFAULT_PER_PAGE);
//
//                $totalBet = 0;
//                foreach ($packages as $value) {
//                    $totalBet += $value['amount'];
//                }
//
//                if (!empty($egamePackages) && isset($egamePackages[0]['total_play_money']) && $egamePackages[0]['total_play_money'] != null) {
//                    $totalBet += $egamePackages[0]['total_play_money'];
//                }
//
//                $usersDeposits = deposits::getUsersDeposits(array($user_id), $lastWithdrawDateTime, $thisWithdrawDateTime);
//                $totalDeposit = floatval($usersDeposits[$user_id]['total_deposit']);
//
//                $withdraws[$k]['traffic_status']['datetime'] = $lastWithdrawDateTime == '' ?
//                    '第一次提款' :
//                    '上次提款时间<font color="red">' . $lastWithdrawDateTime . '</font>';
//
//                if ($totalBet < $totalDeposit) {
//                    // 条件不符合总打码限制
//                    $withdraws[$k]['traffic_status']['deposit'] = '总充值<font color="red">' . $totalDeposit . '</font>元';
//                    $withdraws[$k]['traffic_status']['betAmount'] = '总打码量<font color="red">' . $totalBet . '</font>元';
//                    $withdraws[$k]['traffic_status']['status'] = '未完成总充值额度一倍';
//                } else {
//                    // 符合条件
//                    $withdraws[$k]['traffic_status']['deposit'] = '总充值<font color="green">' . $totalDeposit . '</font>元';
//                    $withdraws[$k]['traffic_status']['betAmount'] = '总打码量<font color="green">' . $totalBet . '</font>元';
//                    $withdraws[$k]['traffic_status']['status'] = '<font color="green">完成打码</font>';
//                }
//            } else {
//                $withdraws[$k]['traffic_status']['status'] = '总代不限';
//            }
//        }

        //查询选项
        self::$view->setVar('top_username', $top_username);
        self::$view->setVar('username', $username);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('bank_id', $bank_id);
        self::$view->setVar('pay_bank_id', $pay_bank_id);
        self::$view->setVar('pay_card_id', $pay_card_id);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('status', $status);
        self::$view->setVar('startAmount', $startAmount);
        self::$view->setVar('endAmount', $endAmount);

        //得到所有总代
        // $topUsers = users::getUserTree(0);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        self::$view->setVar('topUsers', $topUsers)->setVar('json_topUsers', $topUsers);
        //得到管理员列表
        $admins = admins::getItems();
        $admins['65535'] = array('admin_id' => 65535, 'username' => '机器');
        self::$view->setVar('admins', $admins);
        //客户提现银行列表
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        self::$view->setVar('withdrawBankList', getBankListFirstCharter($GLOBALS['cfg']['withdrawBankList']));
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //付款卡列表
        $withdrawCards = cards::getItems(2);
        $bankWithdrawCards = array();
        foreach ($withdrawCards as $v) {
            $bankWithdrawCards[$v['bank_id']][] = $v;
        }

        self::$view->setVar('pageSize', $pageSize);
        self::$view->setVar('withdrawCards', $withdrawCards);
        self::$view->setVar('bankWithdrawCards', $bankWithdrawCards);
        self::$view->setVar('withdraws', $withdraws);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('pageList', getPageList($trafficInfo['count'], $pageSize));
        self::$view->setVar('canAcceptRequest', adminGroups::verifyPriv(array(CONTROLLER, 'acceptRequest')));
        self::$view->render('withdraw_withdrawlist');
    }

    /**
     * 点击稽核
     *     步骤:
     *      1.找到本次提款订单
     *      2.总代不限(不查看)
     *
     * 算法:
     * 情况一:有上次提款
     * 1.  期间充值 = 上一次提款结束到这次提款发起期间充值成功总额
     * 2.  打码量  = 上次提款结束后的第一次存款开始到这测提款发起时间-> 已结算的订单总金额
     * 情况二:第一次提款
     * 1.  期间充值 = 充值总额
     * 2.  打码量 = 打码总量
     *
     */
    public function auditAjax(){

        $uid = $this->request->getPost('uid', 'intval', 0);
        $userInfo = users::getItem($uid);
        if(empty($userInfo))
        {
            die(json_encode(['code' => 1, 'msg' => '信息异常']));
        }
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval', 0);
        $withdraws = withdraws::getItem($withdraw_id);

        if (empty($withdraws)) {
            die(json_encode(['code' => 1, 'msg' => '信息异常']));
        }


        if ($userInfo['level'] != 0) {

            $withdraws['traffic_status'] = ['lastWithdraw' => '', 'deposit' => '', 'betAmount' => '', 'status' => '','datetime'=>''];
            $thisWithdrawDateTime = $withdraws['create_time'];
            $user_id = $withdraws['user_id'];

            /********************** snow  从这里开始修改逻辑************************************/

            //>>判断用户是否有存款
            $tmpSql =<<<SQL
SELECT user_id,amount,finish_time FROM deposits
WHERE user_id = {$uid}
AND `status` = 8
AND amount > 0
AND finish_time <= '{$thisWithdrawDateTime}' 
ORDER BY deposit_id desc  LIMIT 1
SQL;

            $tmpRow = $GLOBALS['db']->getRow($tmpSql);
            if (empty($tmpRow)) {
                //>>如果没有存款记录
                $withdraws['traffic_status']['status'] = '<font color="red">没有存款记录或余额不足</font>';
                die(json_encode(['code' => 0, 'data' => $withdraws]));
            }

            //>>查询用户的提款记录取最后一条
            $withSql =  'SELECT user_id,create_time FROM withdraws WHERE user_id = ' . $uid;
            $withSql .= ' AND `status` = 8 AND withdraw_id < \'' . $withdraw_id . '\' ORDER BY withdraw_id DESC LIMIT 1';
            $withdrawRow = $GLOBALS['db']->getRow($withSql);
            $lastWithdrawDateTime = '';
            if (empty($withdrawRow)){
                //>>第一次提款
                $withdraws['traffic_status']['datetime'] =   '第一次提款';
            } else {
                //>>不是第一次提款
                //>>获取上次提款时间
                $lastWithdrawDateTime = $withdrawRow['create_time'];
                $withdraws['traffic_status']['datetime'] = '上次提款时间<font color="red">' . $lastWithdrawDateTime . '</font>';
            }
            //>>最近一次存款金额
            $chunkAmount = $tmpRow['finish_time'] >= $lastWithdrawDateTime ? $tmpRow['amount'] : 0;

            //>> 查询出当前用户 截止当前提案发起之前的所有充值记录,
            $udSql =<<<SQL
SELECT IFNULL(user_id,{$user_id}) AS user_id,
SUM(amount) AS total_deposit,
IFNULL(MIN(finish_time),'{$lastWithdrawDateTime}') AS min_finish_time,
IFNULL(MAX(finish_time),'{$lastWithdrawDateTime}') AS max_finish_time
FROM deposits WHERE 1
AND  user_id = {$user_id}
AND  finish_time >= '{$lastWithdrawDateTime}'
AND  finish_time <= '{$thisWithdrawDateTime}'
AND  `status` = 8
SQL;

            $userDeposits = $GLOBALS['db']->getRow($udSql);
//            dd($udSql,$lastWithdrawDateTime,$userDeposits);
            //>> 查询出 当前用户 所有投注记录,  添加截止当前提案时间
            $lastDepositTime = $userDeposits['min_finish_time'];
            $packages = projects::getPackages(0, -1, -1, '', -1, 0, $user_id, 0, '', '', $lastDepositTime, $thisWithdrawDateTime, 0);
            $egamePackages = egameMwSiteUsergamelog::getUserLogsSum($lastWithdrawDateTime, $thisWithdrawDateTime, 0, $user_id, XY_PREFIX, DEFAULT_PER_PAGE);
            $totalBet   = 0;
            $chunk      = 0;
            if (!empty($packages)) {
                foreach ($packages as $withdrawsalue) {
                    if (!empty($userDeposits['max_finish_time']) && $userDeposits['max_finish_time'] <= $withdrawsalue['send_prize_time']) {
                        //>>获取从上次提款起到本次提款之间的首次存款时间打码量
                        $chunk += $withdrawsalue['amount'];
                    }
                    //>>获取总打码量
                    $totalBet += $withdrawsalue['amount'];
                }
            }

            //>>TODO 这里最后一次存款的打码量未加上egame 游戏记录打码量.

            if ($chunk < $chunkAmount)
            {
                //>>最近一次存款打码量未达标.
                $withdraws['traffic_status']['deposit'] = '最近一次充值<font color="red">' . $chunkAmount . '</font>元';
                $withdraws['traffic_status']['betAmount'] = '打码量<font color="red">' . $chunk . '</font>元';
                $withdraws['traffic_status']['status'] = '未完成充值额度一倍';
            }
            else
            {

                if (!empty($egamePackages) && isset($egamePackages[0]['total_play_money']) && $egamePackages[0]['total_play_money'] != null) {
                    $totalBet += $egamePackages[0]['total_play_money'];
                }

                $totalDeposit = floatval($userDeposits['total_deposit']);
                $withdraws['traffic_status']['deposit'] = '总充值<font color="red">' . $totalDeposit . '</font>元';
                $withdraws['traffic_status']['betAmount'] = '总打码量<font color="red">' . $totalBet . '</font>元';
                if ($totalBet < $totalDeposit) {
                    // 条件不符合总打码限制
                    $withdraws['traffic_status']['status'] = '未完成总充值额度一倍';
                } else {
                    // 符合条件
                    $withdraws['traffic_status']['status'] = '<font color="green">完成打码</font>';
                }
            }
            /********************** snow 从这里开始修改逻辑************************************/

        }else{
            $withdraws['traffic_status']['status'] = '总代不限';
        }
        die(json_encode(['code'=>0,'data'=>$withdraws]));
    }

    public function auditAjaxbak()
    {

        $uid = $this->request->getPost('uid', 'intval', 0);
        $userInfo = users::getItem($uid);
        if(empty($userInfo))
        {
            die(json_encode(['code' => 1, 'msg' => '信息异常']));
        }
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval', 0);
        $is_test = $this->request->getPost('is_test', 'intval', 0);
        $withdraws = withdraws::getItem($withdraw_id);

        if (empty($withdraws)) {
            die(json_encode(['code' => 1, 'msg' => '信息异常']));
        }

        if ($userInfo['level'] != 0) {
            $withdraws['traffic_status'] = ['lastWithdraw' => '', 'deposit' => '', 'betAmount' => '', 'status' => '','datetime'=>''];
            $thisWithdrawDateTime = $withdraws['create_time'];
            $user_id = $withdraws['user_id'];
            $userDeposits = deposits::getUserDeposits($user_id, '', $thisWithdrawDateTime);
            if ($userDeposits) {//查询此次提款之前的存款记录
                $lastDepositDateTime = $userDeposits[0]['finish_time'];
            } else {
                $withdraws['traffic_status']['status'] = '<font color="red">没有存款记录或余额不足</font>';
                die(json_encode(['code' => 0, 'data' => $withdraws]));
            }

            //$lastDepositDateTime上一次提款结束时间 $thisWithdrawDateTime本次提款发起时间
            $packages = projects::getPackages(0, -1, -1, '', -1, 0, $user_id, 0, '', '', $lastDepositDateTime, $thisWithdrawDateTime, 0);
            $egamePackages = egameMwSiteUsergamelog::getUserLogsSum($lastDepositDateTime, $thisWithdrawDateTime, 0, $user_id, XY_PREFIX, DEFAULT_PER_PAGE);

            $lastBet = 0;
            foreach ($packages as $withdrawsalue) {
                $lastBet += $withdrawsalue['amount'];
            }

            if (!empty($egamePackages) && isset($egamePackages[0]['total_play_money']) && $egamePackages[0]['total_play_money'] != null) {
                $lastBet += $egamePackages[0]['total_play_money'];
            }

            // 条件不符合单一时间段限制
            if ($lastBet < $userDeposits[0]['amount']) {
                $withdraws['traffic_status'] = [
                    'datetime' => '上次充值时间<font color="red">' . $lastDepositDateTime . '</font>',
                    'deposit' => '<font color="red">' . $userDeposits[0]['amount'] . '</font>元',
                    'betAmount' => '到本次取款打码量<font color="red">' . $lastBet . '</font>元',
                    'status' => '未完成本次充值额度一倍'
                ];
            }

            $lastWithdrawDateTime = '';

            if ($lastSuccessWithdrawInfo = withdraws::getLastSuccessWithdrawInfo($user_id, '', $thisWithdrawDateTime)) {
                $lastWithdrawDateTime = $lastSuccessWithdrawInfo['finish_time'];
            }

            $packages = projects::getPackages(0, -1, -1, '', -1, 0, $user_id, 0, '', '', $lastWithdrawDateTime, $thisWithdrawDateTime, 0);
            $egamePackages = egameMwSiteUsergamelog::getUserLogsSum($lastWithdrawDateTime, $thisWithdrawDateTime, 0, $user_id, XY_PREFIX, DEFAULT_PER_PAGE);

            $totalBet = 0;
            foreach ($packages as $withdrawsalue) {
                $totalBet += $withdrawsalue['amount'];
            }

            if (!empty($egamePackages) && isset($egamePackages[0]['total_play_money']) && $egamePackages[0]['total_play_money'] != null) {
                $totalBet += $egamePackages[0]['total_play_money'];
            }

            $usersDeposits = deposits::getUsersDeposits(array($user_id), $lastWithdrawDateTime, $thisWithdrawDateTime);
            $totalDeposit = floatval($usersDeposits[$user_id]['total_deposit']);

            $withdraws['traffic_status']['datetime'] = $lastWithdrawDateTime == '' ?
                '第一次提款' :
                '上次提款时间<font color="red">' . $lastWithdrawDateTime . '</font>';

            if ($totalBet < $totalDeposit) {
                // 条件不符合总打码限制
                $withdraws['traffic_status']['deposit'] = '总充值<font color="red">' . $totalDeposit . '</font>元';
                $withdraws['traffic_status']['betAmount'] = '总打码量<font color="red">' . $totalBet . '</font>元';
                $withdraws['traffic_status']['status'] = '未完成总充值额度一倍';
            } else {
                // 符合条件
                $withdraws['traffic_status']['deposit'] = '总充值<font color="green">' . $totalDeposit . '</font>元';
                $withdraws['traffic_status']['betAmount'] = '总打码量<font color="green">' . $totalBet . '</font>元';
                $withdraws['traffic_status']['status'] = '<font color="green">完成打码</font>';
            }
        } else {
            $withdraws['traffic_status']['status'] = '总代不限';
        }
        die(json_encode(['code'=>0,'data'=>$withdraws]));
    }

    // 查看提款提案
    public function viewWithdraw()
    {

        /************** author snow 添加返回url *******************************/

        if (!($back_url = $GLOBALS['SESSION']['withdraw_back_url'])) {
            $back_url = $this->request->getGet('withdraw_back_url','trim', '');
            if (!empty($back_url)) {
                //>>把back_url 写入session
                $GLOBALS['SESSION']['withdraw_back_url'] = $back_url;
            }

        }
        $back_url = $back_url ? base64_decode($back_url) : url('withdraw', 'withdrawList');
        self::$view->setVar('withdraw_back_url', $back_url);
        /************** author snow 添加返回url *******************************/
        $locations = array(0 => array('title' => '返回提款列表', 'url' => $back_url));
        $withdraw_id = $this->request->getGet('withdraw_id', 'intval');
        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            showMsg("找不到提案！", 1, $locations);
        }
        $user = users::getItem($withdraw['user_id'], -1);

        //得到管理员列表
        $admins = admins::getItems();
        $admins['65535'] = array('admin_id' => 65535, 'username' => '机器');
        self::$view->setVar('admins', $admins);

        //1.得到最近提款列表
        $recentDays = 30;
        $lastSuccessWithdraw = array();
        $startDate = '';
        //获取到已经提款成功的订单
        $withdraws = withdraws::getItems($user['user_id'], 0, -1, 8, 0, 0, 0, date('Y-m-d H:i:s', strtotime("-$recentDays days")), '');
        $totalWithdraws = array('count' => 0, 'total_amount' => 0);

        foreach ($withdraws as $v) {
            $totalWithdraws['total_amount'] += $v['amount'];
            $totalWithdraws['count']++;
            if (!$lastSuccessWithdraw && $v['withdraw_id'] < $withdraw_id) {
                $lastSuccessWithdraw = $v;
                $startDate = $lastSuccessWithdraw['create_time'];
            }
        }

        //得到这段时间的各项优惠值
        $totalPromos = promos::getTotalPromos($withdraw['user_id'], 0, $startDate, $withdraw['create_time']);

        //2.得到最近存款列表
        /*********************** 添加限定条件 ,只查询30天之前到本次提案发起时间之间的存款数据*******************************/
        $deposits = deposits::getItems($withdraw['user_id'], 0, 0, -1, 8, 0, 0, 0, 0, 0, '', date('Y-m-d H:i:s', strtotime("-$recentDays days")), $withdraw['create_time']);
        /*********************** 添加限定条件 ,只查询30天之前到本次提案发起时间之间的存款数据*******************************/
        //得到上次成功提款以来的存款量
        $totalDeposits = deposits::getTrafficInfo($withdraw['user_id'], 0, 0, -1, 8, 0, 0, 0, 0, 0, '', $startDate, $withdraw['create_time']);
        self::$view->setVar('totalDeposits', $totalDeposits);

        //得到日存提流水列表
        $dayDepositWithdraws = array();
        for ($i = 0; $i <= $recentDays; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayDepositWithdraws[$date] = array('date' => $date, 'deposit' => 0, 'withdraw' => 0);
        }
        foreach ($deposits as $v) {
            $date = substr($v['finish_time'], 0, 10);
            $dayDepositWithdraws[$date]['deposit'] += $v['amount'];
        }
        foreach ($withdraws as $v) {
            $date = substr($v['finish_time'], 0, 10);
            $dayDepositWithdraws[$date]['withdraw'] += $v['amount'];
        }
        $dayDepositWithdraws = array_slice($dayDepositWithdraws, 0, self::RECENT_DW_DAYS);
        ksort($dayDepositWithdraws);
        self::$view->setVar('dayDepositWithdraws', $dayDepositWithdraws);

        // 获得用户在PT日报表的值
        $PTSum = reportPt::getSumByUser($withdraw['user_id'], date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($withdraw['create_time'])));

        // 获得该用户上一次成功取款后的所有存款明细
        $depositSinceLastWithdraws = $deposits;
        foreach ($depositSinceLastWithdraws as $k => $v) {
            if ($startDate && $v['finish_time'] < $startDate) {
                unset($depositSinceLastWithdraws[$k]);
            }
        }
        $depositSinceLastWithdraws = array_reverse($depositSinceLastWithdraws);
        $depositAmountSinceLastWithdraw = 0;
        foreach ($depositSinceLastWithdraws as $v) {
            $depositAmountSinceLastWithdraw += $v['amount'];
        }

        $ifpt = $this->request->getGet('pt', 'intval'); // 获得传值过来的pt参数，如果有的话，便要通过接口取得PT数据
        $moneyTmp = 0;
        foreach ($depositSinceLastWithdraws AS $key => $value) {
            $playAmount = projects::getUsersDayPackages($value['finish_time'], $withdraw['create_time'], 0, $withdraw['user_id'], False);

            $PTPlayAmount = 0;
            if ($ifpt) {
                $PT = new PT();
                $PTResult = $PT->gameStats($user['username'], $value['finish_time'], $withdraw['create_time'], true);
                $PTPlayAmount = $PTResult['sale_stats'][0]['GAMES'];
            }

            $webPlayAmount = !empty($playAmount) ? $playAmount[0]['total_amount'] : 0;

            $depositSinceLastWithdraws[$key]['play_amount'] = $PTPlayAmount + $webPlayAmount;
            $depositAmountSinceLastWithdraw -= $moneyTmp;
            $depositSinceLastWithdraws[$key]['deposit_amount'] = $depositAmountSinceLastWithdraw;
            $moneyTmp = $value['amount'];
        }

        // @start 风险控制
        //注册一周内的 都算新客户
        $isNewUser = strtotime($withdraw['create_time']) - strtotime($user['reg_time']) < 86400 * 7 ? true : false;
        // 获得用户提款次数 没有值则是第一次提款 低危
        $crisis = array(
            0 => array('level' => '低危', 'value' => 0, 'desc' => '客户第一次提款'),
            1 => array('level' => '中危', 'value' => 0, 'desc' => '新客户短时间内频繁充值提款迹象'),
            2 => array('level' => '中危', 'value' => 0, 'desc' => '客户有过以下投注之一：“三星直选700注以上；组六包7个号以上；一星包5个号以上”'),
            3 => array('level' => '高危', 'value' => 0, 'desc' => '客户在三星，四星，五星直选中有一笔"奖金/投注金额"大于100，且提现超过20000元'),
            4 => array('level' => '中危', 'value' => 0, 'desc' => '客户更新了新的绑定卡，并且正在使用这张银行卡取款（时间范围：上次成功取款到本次申请取款之间）'),
            5 => array('level' => '高危', 'value' => 0, 'desc' => '客户修改了取款人姓名（时间范围：上次成功取款到本次申请取款之间）'),
            6 => array('level' => '高危', 'value' => 0, 'desc' => '客户通过客服修改资金密码（时间范围：上次成功取款到本次申请取款之间）'),
        );
        // 获得用户信息 X
        // 160217 上述注释明显没有反映下面代码的意思，不是获得用户信息，而是判断是否第一次提款 by william
        if (!$lastSuccessWithdraw) {
            $crisis[0]['value'] = 1;
        }
        // 新客户判断是否有频繁充提迹象 严防充值漏洞
        if ($isNewUser) {
            if ($user['deposit_num'] >= 3 && $totalDeposits['total_amount'] >= 5000) {
                if ($totalWithdraws['total_amount'] >= 2000) {
                    $crisis[1]['value'] = 1;
                }
            }
        }
        // 不同玩法的method_id。如果增加了新玩法，对应的method_id也要放进对应的数组中。
        // 三星直选
        $sanxzx = array(1 => 1, 10 => 1, 35 => 1, 49 => 1, 68 => 1, 83 => 1, 90 => 1, 103 => 1, 117 => 1, 123 => 1, 142 => 1, 158 => 1, 174 => 1, 190 => 1, 211 => 1, 225 => 1, 271 => 1, 305 => 1, 353 => 1, 377 => 1, 379 => 1, 381 => 1, 383 => 1, 385 => 1, 463 => 1, 474 => 1, 481 => 1, 503 => 1);
        // 四星直选
        $sixzx = array(79 => 1, 80 => 1, 111 => 1, 112 => 1, 129 => 1, 130 => 1, 223 => 1, 224 => 1, 349 => 1, 350 => 1, 488 => 1, 489 => 1);
        // 五星直选
        $wuxzx = array(46 => 1, 77 => 1, 140 => 1, 221 => 1, 316 => 1, 494 => 1);
        // 组六
        $zuliu = array(3 => 1, 37 => 1, 51 => 1, 70 => 1, 85 => 1, 92 => 1, 105 => 1, 119 => 1, 125 => 1, 192 => 1, 213 => 1, 227 => 1, 234 => 1, 249 => 1, 273 => 1, 307 => 1, 355 => 1, 465 => 1, 476 => 1, 483 => 1);
        // 一星
        $yix = array(33 => 1, 66 => 1, 97 => 1, 209 => 1, 303 => 1, 455 => 1);
        // 三星直选和四星 直选注数
        $sanNum = $siNum = 0;
        // 三星四星五星直选奖金和投注总额
        $sanPrize = $sanAmount = $siPrize = $siAmount = $wuPrize = $wuAmount = 0;

        //检查24小时内的订单
        $projects = projects::getItems(0, '', 0, $withdraw['user_id'], date('Y-m-d H:i:s', strtotime($withdraw['create_time']) - 86400), $withdraw['create_time']);
        foreach ($projects as $project) {
            //潜在的异常，不仅仅是针对新客户，老客户也同样有可能刷出漏洞，所以这里改成针对所有客户
            // 三星直选700以上
            if (isset($sanxzx[$project['method_id']])) {
                $sanNum += $project['single_num'];
                if ($sanNum > 700) {
                    $crisis[2]['value'] = 1;
                }
            } // 四星直选7000注以上
            else if (isset($sixzx[$project['method_id']])) {
                $siNum += $project['single_num'];
                if ($siNum > 7000) {
                    $crisis[2]['value'] = 1;
                }
            } // 组六包7个号以上
            else if (isset($zuliu[$project['method_id']])) {
                $codes = explode('|', $project['code']);
                foreach ($codes AS $code) {
                    if (strlen($code) > 7) {
                        $crisis[2]['value'] = 1;
                    }
                }
            } // 一星包7个号以上
            else if (isset($yix[$project['method_id']])) {
                $codes = explode('|', $project['code']);
                foreach ($codes AS $code) {
                    if (strlen($code) > 5) {
                        $crisis[2]['value'] = 1;
                        break;
                    }
                }
            }

            // 三星直选总奖金和总投注金额
            if (isset($sanxzx[$project['method_id']])) {
                $sanPrize += $project['prize'];
                $sanAmount += $project['amount'];
            } // 四星直选总奖金和总投注金额
            else if (isset($sixzx[$project['method_id']])) {
                $siPrize += $project['prize'];
                $siAmount += $project['amount'];
            } // 五星直选总奖金和总投注金额
            else if (isset($wuxzx[$project['method_id']])) {
                $wuPrize += $project['prize'];
                $wuAmount += $project['amount'];
            }
        }

        // 低于1%概率中奖的大额提现
        if ($withdraw['amount'] >= 20000) {
            $sanResult = $sanAmount == 0 ? 0 : $sanPrize / $sanAmount;
            $siResult = $siAmount == 0 ? 0 : $siPrize / $siAmount;
            $wuResult = $wuAmount == 0 ? 0 : $wuPrize / $wuAmount;

            if ($sanResult >= 100 || $siResult >= 100 || $wuResult >= 100) {
                $crisis[3]['value'] = 1;
            }
        }

        // 获得用户绑定卡 绑定卡更新时间在上一次成功提款到这次提款申请内
        $userBindCard = userBindCards::getItem($withdraw['card_num'], $withdraw['user_id']);
        if ($userBindCard && ($userBindCard['create_time'] < $withdraw['create_time'] && $userBindCard['create_time'] > $startDate)) {
            $crisis[4]['value'] = 1;
            $crisis[4]['datetime'] = $userBindCard['create_time'];
        }

        // 事件敏感操作数
        $events = events::getItems($user['username'], array(101, 103), $startDate, $withdraw['create_time']);

        foreach ($events as $event) {
            if ($event['type'] == 101) {
                $crisis[5]['value'] = 1;
                $crisis[5]['datetime'] = $event['create_time'];
            } else if ($event['type'] == 103) {
                $crisis[6]['value'] = 1;
                $crisis[6]['datetime'] = $event['create_time'];
            }
        }

        self::$view->setVar('crisis', $crisis);
        // @end

        //得到其他杂费（10退还提款 11手工增减余额）
        //注：退还提款不应计算在内，因为客户申请的时候就扣钱了，取消时又增加了，一减一加正好抵消，手工增减的话一般都是某些特殊原因，所以也不应计，计了就不对了
        //手续费基本不返
        //$trafficInfo = orders::getTrafficInfo(0, '', array(102), $withdraw['username'], 0, 0, 0, $startDate, $withdraw['create_time']);
        //self::$view->setVar('otherFees', $trafficInfo['total_amount']);

        //提款方式
        self::$view->setVar('tradeTypes', $GLOBALS['cfg']['tradeTypes']);
        //得到支持的银行列表
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);
        self::$view->setVar('withdrawBankList', getBankListFirstCharter($GLOBALS['cfg']['withdrawBankList']));
        //得到付款卡列表
        self::$view->setVar('payCards', cards::getItems(2, 0, 0, '', array(1, 2)));

        self::$view->setVar('lastSuccessWithdraw', $lastSuccessWithdraw);
        self::$view->setVar('totalPromos', $totalPromos);

        //得到配置
        self::$view->setVar('configs', config::getConfigs(array('min_withdraw_limit', 'max_withdraw_limit', 'max_auto_pay_limit', 'is_force_machine_pay')));
        // 显示给用户的拒绝备注信息
        self::$view->setVar('errors', withdraws::getWithdrawErrors());

        self::$view->setVar('PTSum', $PTSum);
        self::$view->setVar('depositSinceLastWithdraws', $depositSinceLastWithdraws);

        self::$view->setVar('withdraw', $withdraw);
        self::$view->setVar('user', $user);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        self::$view->setVar('topUsers', $topUsers)->setVar('json_topUsers', $topUsers);


        self::$view->render('withdraw_viewwithdraw');
    }

    // 我要受理 状态更新为 已受理1
    public function acceptRequest()
    {


        /************** author snow 添加返回url *******************************/


        if (!($back_url = $GLOBALS['SESSION']['withdraw_back_url'])) {
            $back_url = $this->request->getGet('withdraw_back_url','trim', '');
            if (!empty($back_url)) {
                //>>把back_url 写入session
                $GLOBALS['SESSION']['withdraw_back_url'] = $back_url;
            }

        }

        $back_url = $back_url ? base64_decode($back_url) :  url('withdraw', 'withdrawList');
        self::$view->setVar('withdraw_back_url', $back_url);
        /************** author snow 添加返回url *******************************/
        $withdraw_id = $this->request->getGet('withdraw_id', 'intval');

        $locations = array(0 => array('title' => '返回提款列表', 'url' =>$back_url));

        //所有管理员
        $admins = admins::getItems();
        self::$view->setVar('admins', $admins);

        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            showMsg("找不到提案！", 1, $locations);
        }

        if ($withdraw['status'] == 1 && $withdraw['verify_admin_id'] != $GLOBALS['SESSION']['admin_id']) {
            showMsg("受理失败！财务 {$admins[$withdraw['verify_admin_id']]['username']} 正在处理该提案，同时只能有一个人处理！", 1, $locations);
        }
        $user = users::getItem($withdraw['user_id']);
        if ($withdraw['status'] == 0) {
            //置状态为“已受理”
            $url = url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id));

            try {
                withdraws::acceptRequest($withdraw_id);
                echo 2;
            } catch (exception2 $e) {
                showMsg($e->getMessage(), $locations);
            }

            $withdraw['verify_admin_id'] = $GLOBALS['SESSION']['admin_id'];
            $withdraw['start_time'] = date('Y-m-d H:i:s');
            $withdraw['status'] = '1';
        }

        //1.得到最近提款列表
        $recentDays = 30;
        $lastSuccessWithdraw = array();
        $startDate = '';
        $withdraws = withdraws::getItems($user['user_id'], 0, -1, 8, 0, 0, 0, date('Y-m-d H:i:s', strtotime("-$recentDays days")), '');
        $totalWithdraws = array('count' => 0, 'total_amount' => 0);

        foreach ($withdraws as $v) {
            $totalWithdraws['total_amount'] += $v['amount'];
            $totalWithdraws['count']++;
            if (!$lastSuccessWithdraw && $v['withdraw_id'] < $withdraw_id) {
                $lastSuccessWithdraw = $v;
                $startDate = $lastSuccessWithdraw['create_time'];
            }
        }

        //得到这段时间的各项优惠值
        $totalPromos = promos::getTotalPromos($withdraw['user_id'], 0, $startDate, $withdraw['create_time']);

        //2.得到最近存款列表
        /*********************** 添加限定条件 ,只查询30天之前到本次提案发起时间之间的存款数据*******************************/
        $deposits = deposits::getItems($withdraw['user_id'], 0, -1, -1, 8, 0, 0, 0, 0, 0, '', date('Y-m-d H:i:s', strtotime("-$recentDays days")), $withdraw['create_time']);
        /*********************** 添加限定条件 ,只查询30天之前到本次提案发起时间之间的存款数据*******************************/
        //得到上次成功提款以来的存款量
        $totalDeposits = deposits::getTrafficInfo($withdraw['user_id'], 0, 0, -1, 8, 0, 0, 0, 0, 0, '', $startDate, $withdraw['create_time']);
        self::$view->setVar('totalDeposits', $totalDeposits);

        //得到日存提流水列表
        $dayDepositWithdraws = array();
        for ($i = 0; $i <= $recentDays; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayDepositWithdraws[$date] = array('date' => $date, 'deposit' => 0, 'withdraw' => 0);
        }
        foreach ($deposits as $v) {
            $date = substr($v['finish_time'], 0, 10);
            $dayDepositWithdraws[$date]['deposit'] += $v['amount'];
        }
        foreach ($withdraws as $v) {
            $date = substr($v['finish_time'], 0, 10);
            $dayDepositWithdraws[$date]['withdraw'] += $v['amount'];
        }
        $dayDepositWithdraws = array_slice($dayDepositWithdraws, 0, self::RECENT_DW_DAYS);
        ksort($dayDepositWithdraws);
        self::$view->setVar('dayDepositWithdraws', $dayDepositWithdraws);

        // 获得用户在PT日报表的值
        $PTSum = reportPt::getSumByUser($withdraw['user_id'], date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($withdraw['create_time'])));

        // 获得该用户上一次成功取款后的所有存款明细
        $depositSinceLastWithdraws = $deposits;
        foreach ($depositSinceLastWithdraws as $k => $v) {
            if ($startDate && $v['finish_time'] < $startDate) {
                unset($depositSinceLastWithdraws[$k]);
            }
        }
        $depositSinceLastWithdraws = array_reverse($depositSinceLastWithdraws);
        $depositAmountSinceLastWithdraw = 0;
        foreach ($depositSinceLastWithdraws as $v) {
            $depositAmountSinceLastWithdraw += $v['amount'];
        }

        $ifpt = $this->request->getGet('pt', 'intval'); // 获得传值过来的pt参数，如果有的话，便要通过接口取得PT数据
        $moneyTmp = 0;
        foreach ($depositSinceLastWithdraws AS $key => $value) {
            $playAmount = projects::getUsersDayPackages($value['finish_time'], $withdraw['create_time'], 0, $withdraw['user_id'], False);

            $PTPlayAmount = 0;
            if ($ifpt) {
                $PT = new PT();
                $PTResult = $PT->gameStats($user['username'], $value['finish_time'], $withdraw['create_time'], true);
                $PTPlayAmount = $PTResult['sale_stats'][0]['GAMES'];
            }

            $webPlayAmount = !empty($playAmount) ? $playAmount[0]['total_amount'] : 0;

            $depositSinceLastWithdraws[$key]['play_amount'] = $PTPlayAmount + $webPlayAmount;
            $depositAmountSinceLastWithdraw -= $moneyTmp;
            $depositSinceLastWithdraws[$key]['deposit_amount'] = $depositAmountSinceLastWithdraw;
            $moneyTmp = $value['amount'];
        }

        // @start 风险控制
        //注册一周内的 都算新客户
        $isNewUser = strtotime($withdraw['create_time']) - strtotime($user['reg_time']) < 86400 * 7 ? true : false;
        // 获得用户提款次数 没有值则是第一次提款 低危
        $crisis = array(
            0 => array('level' => '低危', 'value' => 0, 'desc' => '客户第一次提款'),
            1 => array('level' => '中危', 'value' => 0, 'desc' => '新客户短时间内频繁充值提款迹象'),
            2 => array('level' => '中危', 'value' => 0, 'desc' => '客户有过以下投注之一：“三星直选700注以上；组六包7个号以上；一星包5个号以上”'),
            3 => array('level' => '高危', 'value' => 0, 'desc' => '客户在三星，四星，五星直选中有一笔"奖金/投注金额"大于100，且提现超过20000元'),
            4 => array('level' => '中危', 'value' => 0, 'desc' => '客户更新了新的绑定卡，并且正在使用这张银行卡取款（时间范围：上次成功取款到本次申请取款之间）'),
            5 => array('level' => '高危', 'value' => 0, 'desc' => '客户修改了取款人姓名（时间范围：上次成功取款到本次申请取款之间）'),
            6 => array('level' => '高危', 'value' => 0, 'desc' => '客户通过客服修改资金密码（时间范围：上次成功取款到本次申请取款之间）'),
        );
        // 获得用户信息 X
        // 160217 上述注释明显没有反映下面代码的意思，不是获得用户信息，而是判断是否第一次提款 by william
        if (!$lastSuccessWithdraw) {
            $crisis[0]['value'] = 1;
        }
        // 新客户判断是否有频繁充提迹象 严防充值漏洞
        if ($isNewUser) {
            if ($user['deposit_num'] >= 3 && $totalDeposits['total_amount'] >= 5000) {
                if ($totalWithdraws['total_amount'] >= 2000) {
                    $crisis[1]['value'] = 1;
                }
            }
        }
        // 不同玩法的method_id。如果增加了新玩法，对应的method_id也要放进对应的数组中。
        // 三星直选
        $sanxzx = array(1 => 1, 10 => 1, 35 => 1, 49 => 1, 68 => 1, 83 => 1, 90 => 1, 103 => 1, 117 => 1, 123 => 1, 142 => 1, 158 => 1, 174 => 1, 190 => 1, 211 => 1, 225 => 1, 271 => 1, 305 => 1, 353 => 1, 377 => 1, 379 => 1, 381 => 1, 383 => 1, 385 => 1, 463 => 1, 474 => 1, 481 => 1, 503 => 1);
        // 四星直选
        $sixzx = array(79 => 1, 80 => 1, 111 => 1, 112 => 1, 129 => 1, 130 => 1, 223 => 1, 224 => 1, 349 => 1, 350 => 1, 488 => 1, 489 => 1);
        // 五星直选
        $wuxzx = array(46 => 1, 77 => 1, 140 => 1, 221 => 1, 316 => 1, 494 => 1);
        // 组六
        $zuliu = array(3 => 1, 37 => 1, 51 => 1, 70 => 1, 85 => 1, 92 => 1, 105 => 1, 119 => 1, 125 => 1, 192 => 1, 213 => 1, 227 => 1, 234 => 1, 249 => 1, 273 => 1, 307 => 1, 355 => 1, 465 => 1, 476 => 1, 483 => 1);
        // 一星
        $yix = array(33 => 1, 66 => 1, 97 => 1, 209 => 1, 303 => 1, 455 => 1);
        // 三星直选和四星 直选注数
        $sanNum = $siNum = 0;
        // 三星四星五星直选奖金和投注总额
        $sanPrize = $sanAmount = $siPrize = $siAmount = $wuPrize = $wuAmount = 0;

        //检查24小时内的订单
        $projects = projects::getItems(0, '', 0, $withdraw['user_id'], date('Y-m-d H:i:s', strtotime($withdraw['create_time']) - 86400), $withdraw['create_time']);
        foreach ($projects as $project) {
            //潜在的异常，不仅仅是针对新客户，老客户也同样有可能刷出漏洞，所以这里改成针对所有客户
            // 三星直选700以上
            if (isset($sanxzx[$project['method_id']])) {
                $sanNum += $project['single_num'];
                if ($sanNum > 700) {
                    $crisis[2]['value'] = 1;
                }
            } // 四星直选7000注以上
            else if (isset($sixzx[$project['method_id']])) {
                $siNum += $project['single_num'];
                if ($siNum > 7000) {
                    $crisis[2]['value'] = 1;
                }
            } // 组六包7个号以上
            else if (isset($zuliu[$project['method_id']])) {
                $codes = explode('|', $project['code']);
                foreach ($codes AS $code) {
                    if (strlen($code) > 7) {
                        $crisis[2]['value'] = 1;
                    }
                }
            } // 一星包7个号以上
            else if (isset($yix[$project['method_id']])) {
                $codes = explode('|', $project['code']);
                foreach ($codes AS $code) {
                    if (strlen($code) > 5) {
                        $crisis[2]['value'] = 1;
                        break;
                    }
                }
            }

            // 三星直选总奖金和总投注金额
            if (isset($sanxzx[$project['method_id']])) {
                $sanPrize += $project['prize'];
                $sanAmount += $project['amount'];
            } // 四星直选总奖金和总投注金额
            else if (isset($sixzx[$project['method_id']])) {
                $siPrize += $project['prize'];
                $siAmount += $project['amount'];
            } // 五星直选总奖金和总投注金额
            else if (isset($wuxzx[$project['method_id']])) {
                $wuPrize += $project['prize'];
                $wuAmount += $project['amount'];
            }
        }

        // 低于1%概率中奖的大额提现
        if ($withdraw['amount'] >= 20000) {
            $sanResult = $sanAmount == 0 ? 0 : $sanPrize / $sanAmount;
            $siResult = $siAmount == 0 ? 0 : $siPrize / $siAmount;
            $wuResult = $wuAmount == 0 ? 0 : $wuPrize / $wuAmount;

            if ($sanResult >= 100 || $siResult >= 100 || $wuResult >= 100) {
                $crisis[3]['value'] = 1;
            }
        }

        // 获得用户绑定卡 绑定卡更新时间在上一次成功提款到这次提款申请内
        $userBindCard = userBindCards::getItem($withdraw['card_num'], $withdraw['user_id']);
        if ($userBindCard && ($userBindCard['create_time'] < $withdraw['create_time'] && $userBindCard['create_time'] > $startDate)) {
            $crisis[4]['value'] = 1;
            $crisis[4]['datetime'] = $userBindCard['create_time'];
        }

        // 事件敏感操作数
        $events = events::getItems($user['username'], array(101, 103), $startDate, $withdraw['create_time']);

        foreach ($events as $event) {
            if ($event['type'] == 101) {
                $crisis[5]['value'] = 1;
                $crisis[5]['datetime'] = $event['create_time'];
            } else if ($event['type'] == 103) {
                $crisis[6]['value'] = 1;
                $crisis[6]['datetime'] = $event['create_time'];
            }
        }

        self::$view->setVar('crisis', $crisis);
        // @end

        //得到其他杂费（10退还提款 11手工增减余额）
        //注：退还提款不应计算在内，因为客户申请的时候就扣钱了，取消时又增加了，一减一加正好抵消，手工增减的话一般都是某些特殊原因，所以也不应计，计了就不对了
        //手续费基本不返
        //$trafficInfo = orders::getTrafficInfo(0, '', array(102), $withdraw['username'], 0, 0, 0, $startDate, $withdraw['create_time']);
        //self::$view->setVar('otherFees', $trafficInfo['total_amount']);

        //提款方式
        self::$view->setVar('tradeTypes', $GLOBALS['cfg']['tradeTypes']);
        //得到支持的银行列表
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);
        //得到付款卡列表
        self::$view->setVar('payCards', cards::getItems(2, 0, 0, '', array(1, 2)));

        self::$view->setVar('lastSuccessWithdraw', $lastSuccessWithdraw);
        self::$view->setVar('totalPromos', $totalPromos);

        //得到配置
        self::$view->setVar('configs', config::getConfigs(array('min_withdraw_limit', 'max_withdraw_limit', 'max_auto_pay_limit', 'is_force_machine_pay')));
        // 显示给用户的拒绝备注信息
        self::$view->setVar('errors', withdraws::getWithdrawErrors());

        self::$view->setVar('PTSum', $PTSum);
        self::$view->setVar('depositSinceLastWithdraws', $depositSinceLastWithdraws);

        self::$view->setVar('withdraw', $withdraw);
        self::$view->setVar('user', $user);
        self::$view->render('withdraw_viewwithdraw');
    }

    // 审核 状态更新为 已审核2
    public function verify()
    {
        /*********** author snow 验证是否有异常提款*************/
        $this->_verifyUserWithdrawNumber('MSG');
        /*********** author snow 验证是否有异常提款*************/
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval');
        $locations = array(0 => array('title' => '返回提款列表', 'url' => url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id))));

        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            showMsg("找不到提案！", 1, $locations);
        }

        if ($withdraw['status'] != 1) {
            showMsg("该提案状态不是“已受理”，拒绝审核！", 1, $locations);
        }

        //置状态为“已审核”
        $data = array('verify_admin_id' => $GLOBALS['SESSION']['admin_id'], 'verify_time' => date('Y-m-d H:i:s'), 'status' => '2');

        if (!withdraws::updateItem($withdraw_id, $data)) {
            showMsg("审核失败！数据库错误！", 1, $locations);
        }
        showAlert('审核成功！', url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id)));
    }

    public function reset2verified()
    {
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval');
        $locations = array(0 => array('title' => '返回提款列表', 'url' => url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id))));

        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            showMsg("找不到提案！", 1, $locations);
        }

        if ($withdraw['status'] != 3 && $withdraw['status'] != 4) {
            showMsg("该提案状态没有经过机器受理，不可重置！", 1, $locations);
        }

        //置状态为“已审核”
        $data = array('status' => '2');
        if (!withdraws::updateItem($withdraw_id, $data)) {
            showMsg("重置失败！数据库错误！", 1, $locations);
        }
        showAlert('重置为“已审核”成功！可以手工方式执行付款', url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id)));
    }

    public function reset2pay()
    {
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval');
        $locations = array(0 => array('title' => '返回提款列表', 'url' => url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id))));

        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            showMsg("找不到提案！", 1, $locations);
        }

        if ($withdraw['status'] != 3 && $withdraw['status'] != 4) {
            showMsg("该提案状态没有经过机器受理，不可重置！", 1, $locations);
        }

        //置状态为“已审核”
        $data = array('status' => '2');

        if (!$cards = cards::getItems(2, 0, 0, '', 1)) {
            showMsg("没有可用的付款卡！", 1, $locations);
        }
        $card = reset($cards);
        try {
            $flag = withdraws::pay($withdraw_id, $card['card_num'], 0, '', self::MACHINE_ID);
            showAlert('重置为“已付款”成功！', url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id)));
        } catch (exception2 $e) {
            showMsg($e->getMessage());
        }
    }

    // 执行付款 置状态为"已成功"
    public function pay()
    {
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval');
        $pay_card_num = $this->request->getPost('pay_card_num', 'trim');
        $auto = $this->request->getPost('auto', 'intval');
        $fee = $this->request->getPost('fee', 'floatval');
        $locations = array(0 => array('title' => '返回提案', 'url' => url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id))));

        // 处理提款
        try {
            //交给机器处理，简单更改下状态即可
            if ($auto) {
                withdraws::changeToMachine($withdraw_id, $GLOBALS['SESSION']['admin_id']);
                /*********** author snow 添加返回带参数***********************************/
                $back_url = $GLOBALS['SESSION']['withdraw_back_url'];
                /*********** author snow 添加返回带参数***********************************/
                $back_url = $back_url ? base64_decode($back_url) :  url('withdraw', 'withdrawList');
                showAlert('已提交给机器处理，请执行最后的付款操作！', $back_url);
            } else {
                $flag = withdraws::pay($withdraw_id, $pay_card_num, $fee, '', $GLOBALS['SESSION']['admin_id']);
                if ($flag === -1) {
                    showAlert('注意：付款卡余额已低于系统设置最低限额，请及时充值！', url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id)));
                } elseif ($flag === -2) {
                    showAlert('注意：付款卡余额已超过今天的付款限额，请不要再使用！', url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id)));
                } elseif ($flag === -3) {
                    showAlert('注意：付款卡不是启用或者正在使用的状态，请不要再次使用！', url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id)));
                } else {
                    showMsg('执行提款成功！', 1, $locations);
                }
            }
        } catch (exception2 $e) {
            showMsg($e->getMessage());
        }
    }

    // 取消提款 并写明原因 同时把游戏币退还给客户
    public function cancel()
    {
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval');
        $remark = $this->request->getPost('remark', 'trim');
        $errno = $this->request->getPost('errno', 'trim');
        $locations = array(0 => array('title' => '返回提款列表', 'url' => url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id))));
        if ($errno == '') {
            showMsg("拒绝提款必须写明原因！", 1, $locations);
        }
        if ($remark == '' || $remark == '请输入取消原因！') {
            showMsg("拒绝提款必须写明原因！", 1, $locations);
        }

        // 取消
        try {
            if (!withdraws::cancel($withdraw_id, $GLOBALS['SESSION']['admin_id'], $remark, $errno)) {
                showAlert('取消提款失败！数据库错误！', url('withdraw', 'viewWithdraw', url('withdraw', 'viewWithdraw', array('withdraw_id' => $withdraw_id))));
            }
            showMsg("取消提款成功！", 1, $locations);
        } catch (exception2 $e) {
            showMsg($e->getMessage());
        }
    }

    //提款允许手工添加
    public function addWithdraw()
    {
        $locations = array(0 => array('title' => '继续添加提款', 'url' => url('withdraw', 'addWithdraw')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            if (!$user = users::getItem($username)) {
                showMsg("无效的用户名，请检查", 1, $locations);
            }
            if (!$bank_id = $this->request->getPost('bank_id', 'intval')) {
                showMsg("请选择提款银行", 1, $locations);
            }
            if (!$card_name = $this->request->getPost('card_name', 'trim')) {
                showMsg("请输入卡户名", 1, $locations);
            }
            if (!$card_num = $this->request->getPost('card_num', 'trim')) {
                showMsg("请输入卡号", 1, $locations);
            }
            if (!$province = $this->request->getPost('province', 'trim')) {
                showMsg("请选择省份", 1, $locations);
            }
            if (!$city = $this->request->getPost('city', 'trim')) {
                showMsg("请选择城市", 1, $locations);
            }
            if (!$branch = $this->request->getPost('branch', 'trim')) {
                showMsg("请输入支行地址", 1, $locations);
            }
            $amount = $this->request->getPost('amount', 'trim');
            if (!preg_match("`^\d+(\.\d{1,2})?$`", $amount)) {
                showMsg("金额不对。请检查", 1, $locations);
            }
            $remark = $this->request->getPost('remark', 'trim');

            $flag = withdraws::applyWithdraw($user['user_id'], $bank_id, $card_num, $province, $city, $branch, $amount, '');
            if ($flag !== true) {
                showMsg("添加提款失败! 错误信息：" . $flag, 1);
            }

            showMsg("添加提款成功！", 0, $locations);
        }
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到支持的银行列表
        self::$view->setVar('bankList', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到交易类型
        self::$view->setVar('tradeTypes', $GLOBALS['cfg']['tradeTypes']);

        self::$view->setVar('curLink', '<a href="./?c=' . CONTROLLER . '&a=addwithdraw">手工提款</a>');
        self::$view->render('withdraw_addwithdraw');
    }
    /********************* author snow 添加验证是否有异常提款*******************************************/
    /**
     * author snow 添加验证是否有异常提款
     * @param string $type 返回方式
     */
    private function _verifyUserWithdrawNumber($type = 'JSON')
    {

        $user_id = $this->request->getPost('user_id', 'intval');

        if (!$user_id) {
            response([
                'errMsg' => '没有传入正确的用户编号!',
                'errCode' => 1,
            ],$type);
        }
        if (withdraws::getUserWithdrawNumber($user_id) > 1) {
            //>>如果有多于1 条的提款数据.不允许进行操作
            response([
                'errMsg' => '该用户存在其他异常出款请求，请核实!',
                'errCode' => 1,
            ],$type);
        }

    }
    /********************* author snow 添加验证是否有异常提款*******************************************/

    /**
     * 一键审核
     * 添加开始时间 验证时间 受理人 状态
     */
    public function quickVerify()
    {
        $this->_verifyUserWithdrawNumber();

        $withdraw_id = $this->request->getPost('withdraw_id', 'intval');

        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            response([
                'errCode' => 1,
                'errMsg' => '找不到提案!',
            ]);
        }

        if ($withdraw['status'] == 9) {
            response([
                'errCode' => 1,
                'errMsg' => '该提案已被取消!',
            ]);
        }

        if ($withdraw['status'] > 0 && $withdraw['verify_admin_id'] != $GLOBALS['SESSION']['admin_id']) {
            response([
                'errCode' => 1,
                'errMsg' => '该提案已被其他管理员处理!',
            ]);
        }

        $updateData = [
            'verify_admin_id' => $GLOBALS['SESSION']['admin_id'],
            'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
            'start_time' => date('Y-m-d H:i:s'),
            'verify_time' => date('Y-m-d H:i:s'),
            'finish_time' => date('Y-m-d H:i:s'),
            'status' => 8,
            'errno' => 0,
        ];

        if (!withdraws::updateItem($withdraw_id, $updateData)) {
            response([
                'errCode' => 1,
                'errMsg' => '一键审核失败!数据库错误!',
            ]);
        }

        $admins = admins::getItems();
        $data = withdraws::getItem($withdraw_id);
        $data['handler_username'] = $admins[$data['finish_admin_id']]['username'];

        response([
            'errCode' => 0,
            'errMsg' => '一键审核成功!',
            'data' => $data,
        ]);
    }

    /**
     * 一键拒绝
     */
    public function quickCancel()
    {
        $withdraw_id = $this->request->getGet('withdraw_id', 'intval', 0);
        $withdraw_id || $withdraw_id = $this->request->getPost('withdraw_id', 'intval', 0);

        if (!$withdraw = withdraws::getItem($withdraw_id)) {
            response(['errCode' => 1, 'errMsg' => '找不到提案!']);
        }

        if(IS_AJAX){
            $errno = $this->request->getPost('errno', 'intval', 0);
            $orderRemark = $this->request->getPost('order_remark', 'trim', '');
            $remark = $this->request->getPost('remark', 'trim', '');

            if ($withdraw['status'] == 3) {
                response(['errCode' => 1, 'errMsg' => '对不起，目前仅能取消未成功执行的提案，已经执行成功的暂不支持取消!']);
            }

            if (in_array($withdraw['status'], [8, 9])) {
                response(['errCode' => 1, 'errMsg' => '该提案已经处理完毕!']);
            }

            if ($withdraw['status'] > 0 && $withdraw['verify_admin_id'] != $GLOBALS['SESSION']['admin_id']) {
                response(['errCode' => 1, 'errMsg' => '该提案已被其他管理员处理!']);
            }

            if (!$user = users::getItem($withdraw['user_id'])) {
                response(['errCode' => 1, 'errMsg' => '该用户不存在或已被禁用!']);
            }

            if (!withdraws::getWithdrawErrors($errno)) {
                response(['errCode' => 1, 'errMsg' => '无效的取消原因!']);
            }

            if (!$remark) {
                response(['errCode' => 1, 'errMsg' => '取消客户的提款必须写明原因!']);
            }

            M()->startTrans();

            // 增加'退还提款'帐变
            $orderData = [
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
                'admin_id' => $GLOBALS['SESSION']['admin_id'],
                'remark' => $orderRemark,
            ];
            if (!orders::addItem($orderData)) {
                M()->rollback();
                response([
                    'errCode' => 1,
                    'errMsg' => '提交事务失败1!',
                ]);
            }
            // 把发起提案时扣的游戏币退给客户
            if (!users::updateBalance($withdraw['user_id'], $withdraw['amount'])) {
                M()->rollback();
                response([
                    'errCode' => 1,
                    'errMsg' => '提交事务失败2!',
                ]);
            }

            $updateData = [
                'verify_admin_id' => $GLOBALS['SESSION']['admin_id'],
                'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
                'start_time' => date('Y-m-d H:i:s'),
                'verify_time' => date('Y-m-d H:i:s'),
                //>>author snow 没有添加执行时间
                'finish_time' => date('Y-m-d H:i:s'),
                'status' => 9,
                'errno' => $errno,
                'remark' => $remark,
            ];

            if (!withdraws::updateItem($withdraw_id, $updateData)) {
                M()->rollback();
                response([
                    'errCode' => 1,
                    'errMsg' => '一键拒绝失败!数据库错误!',
                ]);
            }

            M()->commit();

            response([
                'errCode' => 0,
                'errMsg' => '一键拒绝成功!',
            ]);
        }else{
            self::$view->setVar('errorList', withdraws::getWithdrawErrors());
            self::$view->render('withdraw_quickcancel');
        }
    }
    /********************** snow 批量删除提款记录*********************************************/
    public function deleteWithdrawMany()
    {
        //>>批量删除存款记录

        $ids  = $this->request->getPost('ids', 'array');
        //>>对数据进行验证
        if(!empty($ids)){
            foreach($ids as $key => $val){
                if(is_numeric($val)){
                    $ids[$key] = (int)$val;
                }else{
                    unset($ids[$key]);
                }

            }
            if(!empty($ids)){
                $whereIn = '(' . implode(',', $ids) . ')';
                //>>删除数据
                $sql = 'DELETE FROM `withdraws` WHERE withdraw_id IN' . $whereIn;
                $result = $GLOBALS['db']->query($sql,[],'d');
                if($result && $result > 0){
                    //>>删除成功
                    die(json_encode(['flag' => true, 'data' => ['count' => $result]]));
                }
            }
        }
        die(json_encode(['flag' => false, 'data' => ['error' => '删除失败']]));
    }
    /********************** snow 批量删除提款记录*********************************************/

}

