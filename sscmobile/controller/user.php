<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：用户管理
 */
class userController extends sscController
{

    //方法概览
    public $titles = array(
        'showBalance' => '显示余额',
        'childList' => '会员管理',
        'childReport' => '会员报表',
        'teamNowReport' => '团队即时报表',
        'teamDayReport' => '团队日结报表',
        'teamWinReport' => '团队盈亏报表',
        'teamGiftReport' => '团队活动报表',
        'teamReport' => '实际盈亏',
        'teamPromos' => '优惠列表',
        'teamPromosList' => '优惠详单',
        'transferMoney' => '会员转账',
        'regChild' => '注册下级',
        'childRebateList' => '佣金明细',
        'childPackageList' => '下级订单查询',
        'setRebate' => '设置返点',
        'showTeam' => '团队',
        'receiveBox' => '收件箱',
        'sendBox' => '发件箱',
        'sendMsg' => '发消息',
        'viewMsg' => '查看消息',
        'editProfile' => '基本资料',
        'setting' => '个性设置',
        'editSecurity' => '安全资料',
        'editPwd' => '修改登陆密码',
        'editSecPwd' => '修改资金密码',
        'editSafePwd' => '修改安全码',
        'recycle' => '回收',
        'userGifts' => '礼品券功能',
        'sendOutQuota' => '下发配额',
        'setPwd' => '设置密码中心',
        'teamReportCentral' => '代理报表中心',
        'getMarketCodeAjax'=>'根据返点获取code',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function showBalance()
    {
        //sleep(2);
        $result = array('balance' => -1);
        if ($user = users::getItem($GLOBALS['SESSION']['user_id'], 8, false, 1,-1, 'balance')) {
            $GLOBALS['SESSION']['balance'] = $result['balance'] = $user['balance'];
        }
        echo json_encode($result);
        exit;
    }

    /**
     * 前端操作
     */
    public function userGifts()
    {
        $op = $this->request->getPost('op', 'trim', '');
        $option = $this->request->getGet('option', 'trim', '');

        //活动类型: 必须在 user_gift表  gift_type字段值域内逐渐丰富：roulette | card | brokeEgg | seo | sign
        $giftType = $this->request->getGet('giftType', 'trim', '') ? $this->request->getGet('giftType', 'trim', '') : $this->request->getPost('giftType', 'trim', '');

        if ($option == 'prizeNotice') { //四十万大奖公告
            self::$view->render('user_gifts_notice');
            exit;
        }

        //活动前端控制逻辑
        $promoObject = userGiftsControl::registPromo($giftType);
        if ($promoObject instanceof userGiftsBase) {
            $promoObject->runFrontLogicControll($this->request, self::$view);
        }

        if ($op) {
            $ug_id = $this->request->getPost('ug_id', 'trim', '');
            switch ($op) {
                case 'apply':
                    if (!$userGift = userGifts::getItem($ug_id)) {
                        die();
                    }
                    //检查是否自己的礼品卷
                    if ($GLOBALS['SESSION']['user_id'] != $userGift['user_id']) {
                        die();
                    }
                    
                    // 活动红包是否自动发放
                    $is_auto_verify = false;
                    $promoObject = userGiftsControl::getPromoObject($userGift['gift_type']);
                    if ($promoObject instanceof userGiftsBase) { //能得到对象则表示过了时间范围验证
                        if($promoObject->is_auto_verify === true) {
                            $is_auto_verify = true;
                        }
                    }
                    
                    if ($userGift['status'] == 2) {
                        if (userGifts::updateItem($ug_id, array('status' => 8))) {
                            die(json_encode(array('errno' => 0, 'result' => 8)));
                        }
                        else {
                            die(json_encode(array('errno' => 128002, 'errstr' => "申请领取礼金异常，请稍后尝试")));
                        }
                    }
                    if ($userGift['status'] == 3) {
                        if (userGifts::updateItem($ug_id, array('status' => 7))) {

                            if ($is_auto_verify === true) {
                                if (!userGifts::sendUserGifts($userGift['ug_id'], 0)) {
                                    userGifts::updateItem($ug_id, array('status' => 3));
                                }
                                die(json_encode(array('errno' => 0, 'result' => 4)));
                            }

                            die(json_encode(array('errno' => 0, 'result' => 7)));
                        }
                        else {
                            die(json_encode(array('errno' => 128003, 'errstr' => "申请领取礼金异常，请稍后尝试")));
                        }
                    }
                    if ($userGift['status'] == 0) {
                        if (userGifts::updateItem($ug_id, array('status' => 1))) {
                            die(json_encode(array('errno' => 0, 'result' => 1)));
                        }
                        else {
                            die(json_encode(array('errno' => 128004, 'errstr' => "开始礼金异常，请稍后尝试")));
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        else {
            $userGifts = userGifts::getItems('', '', $GLOBALS['SESSION']['user_id']);
            //krsort($userGifts);
            if ($userGifts) {
                $result = array();
                $now = date('Y-m-d H:i:s');

                foreach ($userGifts as $k => $userGift) {
                    if ($userGift['status'] == 0) {
                        //自动开始
                        if (userGifts::updateItem($userGift['ug_id'], array('status' => 1))) {
                            $userGift['status'] = 1;
                        }
                    }
                    if (!empty($userGift['the_last_showdate']) && strtotime($userGift['the_last_showdate']) < time()) {
                        continue;
                    }

                    //已过期三个月的红包不显示
                    if ($userGift['to_time'] != userGifts::MAX_TIME && strtotime($userGift['to_time']) < (time() - 60 * 60 * 24 * 60)) {
                        continue;
                    }

                    //活动采取注册制：没有注册的运行中活动不显示
                    $promoObject = userGiftsControl::registPromo($userGift['gift_type']);
                    if ($promoObject instanceof userGiftsBase) { //能得到对象则表示过了时间范围验证
                        if($promoObject->showRecordOnRedGift !== true) {
                            continue;
                        }
                        // 隐藏已领取的红包，在红包对象中新增属性 hiddenSuccessGift，为true时，unset掉
                        if ($promoObject->hiddenSuccessGift === true && $userGift['status'] == 4) {
                            continue;
                        }
                        //在红包里面显示给用户看的红包进度
                        $userGift['progress'] = $promoObject->redGiftShowProgressToUser($userGift);

                        //根据活动类型在红包里面显示给用户看的文言
                        $userGift['dec'] = $promoObject->redGiftShowMsgToUser($userGift);

                        //加载显示到前台
                        if ($userGift['from_time'] <= $now) {
                            $result[] = $userGift;
                        }
                    }
                }
                die(json_encode(array('count' => count($result), 'userGifts' => $result)));
            }
        }
        die();
    }

    public function childList()
    {
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
        $range = $this->request->getGet('range', 'intval', '0|1');  //0表示直接下级 1表示所有下级
        $online = $this->request->getGet('online', 'intval', '-1');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');

        $flag = 0;
        $userRebates = $users = array();
        $usersNumber = 0;

        $userLevelName = '<a href="/?c=user&a=childList&range='.$range.'">本级</a>';
        if ($user = users::getItem($username)) {
            if ($user['user_id'] == $GLOBALS['SESSION']['user_id']) {
                $flag = 1;
                $nowLevel = $user['level'];
            }
            else {
                $allParent = users::getAllParent($user['user_id']);
                if (isset($allParent[$GLOBALS['SESSION']['user_id']])) {
                    $flag = 1;
                }
                $nowUser = users::getItem($GLOBALS['SESSION']['user_id']);
                $nowLevel = $nowUser['level'];

                $allParent = array_reverse($allParent);
                $allParent[] = $user;
            }
        }else{
            $nowLevel = 0;
        }

        if ($flag == 1) {
            $orderBy = $sortKey && $sortDirection? [$sortKey=>$sortDirection]:[];

            $usersNum = users::getItemsNumber($user['user_id'], true, $range,8,-1);
            /***************** snow  获取正确的分页开始****************************/
            $curPage  = $this->request->getGet('curPage', 'intval', 1);
            $startPos = getStartOffset($curPage, $usersNum);
            /***************** snow  获取正确的分页开始****************************/
            $users = users::getItems($user['user_id'], true, $range, array(), 8, -1, '', '', '', '', '', '', '', '', '', '', -1, $startPos, DEFAULT_PER_PAGE, $orderBy);

            foreach ($users as $iUserId => $value)
            {
                $subPrizeMode = userRebates::userPrizeMode($iUserId);
                $users[$iUserId]['prize_mode'] = $subPrizeMode;
                $users[$iUserId]['rebate'] = number_format(userRebates::getRebateByPrizeMode($subPrizeMode),1);
            }
        }

        //预设查询值
        self::$view->setVar('nowLevel', $nowLevel);
        self::$view->setVar('transfer_forbidden', config::getConfig('transfer_forbidden'));
        self::$view->setVar('user', $user);
        self::$view->setVar('loginUser', $GLOBALS['SESSION']);
        self::$view->setVar('username', $username);
        self::$view->setVar('range', $range);
        self::$view->setVar('flag', $flag);
        self::$view->setVar('users', $users);
        self::$view->setVar('pageList', getPageListMobile($usersNum,DEFAULT_PER_PAGE));
        self::$view->setVar('userRebates', $userRebates);
        self::$view->setVar('self_user_id', $GLOBALS['SESSION']['user_id']);
        self::$view->setVar('properties', $GLOBALS['cfg']['property']);
        self::$view->setVar('sortDirection', $sortDirection);
        self::$view->setVar('sortKey', $sortKey);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加用户', 'url' => url('user', 'addUser'))));
        self::$view->render('user_childlist');
    }

    //总代给自己的下级分红
    public function transferMoney()
    {
        //关闭代充功能
        if(config::getConfig('transfer_forbidden')){
            showMsg("无法使用该功能");
        }
        $fromUser = users::getItem($GLOBALS['SESSION']['user_id']);
        // 判断该总代是否被限制转账给下级
        $config = config::getConfig('top_transfer_forbidden');
        $tmp = explode(',', $config);
        if (in_array($fromUser['username'], $tmp)) {
            showMsg("无法使用该功能");
        }
        if (!$user_id = $this->request->getGet('user_id', 'intval')) {
            $user_id = $this->request->getPost('user_id', 'intval');
        }
        $toUser = users::getItem($user_id);
        //到3代为止不能忘下级转账了
        if ($fromUser['level'] > 2) {
            showMsg("不支持使用此功能");
        }
//        if (1 != $toUser['level']) {
//            showMsg("不支持使用此功能2");
//        }
        if ('doTransfer' == $this->request->getPost('op', 'trim')) {
            //140616 bugfix 转钱须验证资金密码，因为登录密码容易被修改
            if (!$secpassword = $this->request->getPost('secpwd', 'trim')) {
                showMsg("请输入资金密码");
            }
            if (strlen($secpassword) < 6 || strlen($secpassword) > 15 || preg_match('`^\d+$`', $secpassword) || preg_match('`^[a-zA-Z]+$`', $secpassword)) {
                showMsg("资金密码不对");
            }
            
            $amount = $this->request->getPost('amount', 'floatval');
            $amount1 = $this->request->getPost('amount1', 'floatval');
            $safePwd = $this->request->getPost('safe_pwd', 'trim');

            if (!floatval($amount)) {
                showMsg("金额无效");
            }
            if($amount != $amount1){
                showMsg("两次输入的金额必须一致");
            }
            if($amount < 10){
                showMsg("最低代充金额为10元");
            }
            if ($fromUser['balance'] < $amount) {
                showMsg("余额不足");
            }
//            users::checkSafePwd($fromUser['safe_pwd'], $safePwd);

            $orderTotal = orders::getTrafficInfo(0,'',212,$GLOBALS['SESSION']['user_id'],0,0,0,date('Y-m-d 00:00:00'));

            if((abs($orderTotal['total_amount']) + $amount) > config::getConfig('day_transfer_limit', 'floatval')){
                showMsg("您今天的转账金额已经超限");
            }

            try {
                if (users::transferMoney($fromUser, $toUser, $amount, $secpassword)) {
                    showMsg("给下级转账成功");
                } else {
                    showMsg("给下级转账失败");
                }

            } catch (exception2 $exception2) {
                showMsg($exception2->getMessage());
            }
        }
        self::$view->setVar('user', $toUser);
        self::$view->render('user_transfermoney');
    }

    public function teamReport()
    {
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
        $submit = $this->request->getGet('submit', 'trim', '查询');
        if (strtotime($startDate) < strtotime('-65 days')) {
            $startDate = date("Y-m-d", strtotime('-65 days'));
        }
        if (strtotime($endDate) < strtotime('-65 days')) {
            $endDate = date('Y-m-d', strtotime('-65 days'));
        }
        if (strtotime($startDate) > strtotime($endDate)) {
            $startDate = $endDate;
        }
        $top_id = $GLOBALS['SESSION']['user_id'];
        $totalInfo = array(
            'last_team_balance' => 0,
            'team_balance' => 0,
            'deposit_amount' => 0,
            'withdraw_amount' => 0,
            'real_win' => 0,
            'buy_amount' => 0,
            'prize_amount' => 0,
            'rebate_amount' => 0,
            'game_win' => 0,
            'promos' => 0,
            'diff' => 0,
            'play_user_num' => 0,
            'prize_user_num' => 0,
            'reg_num' => 0,
            'first_deposit_num' => 0,
            'first_deposit_amount' => 0,
        );
        if (strtotime($startDate) < strtotime('2015-01-01')) {
            $startDate = '2015-01-01';
        }
        if (strtotime($endDate) < strtotime('2015-01-01')) {
            $endDate = '2015-01-01';
        }
//        if ($submit == '查询') {
//            $teamReport = teamReports::getItems($top_id, $startDate, $endDate);
//        }
//        elseif ($submit == '查询下级') {
//            $teamReport = teamReports::getChildren($top_id, $startDate, $endDate);
//            self::$view->setVar('showUsername', 1);
//        }

        $teamReport = teamReports::getChildren($top_id, $startDate, $endDate, true);
        self::$view->setVar('showUsername', 1);

        //$teamReportNumber = teamReports::getItemsNumber($top_id, $startDate, $endDate);
        foreach ($teamReport as $k => $v) {
            if ($k == $top_id) {
                $teamReport[$k]['username'].='(团队)';
                $totalInfo = $teamReport[$k];
            }
//            $totalInfo['last_team_balance'] += $v['last_team_balance'];
//            $totalInfo['team_balance'] += $v['team_balance'];
//            $totalInfo['deposit_amount'] += $v['deposit_amount'];
//            $totalInfo['withdraw_amount'] += $v['withdraw_amount'];
//            $totalInfo['real_win'] += $v['real_win'];
//            $totalInfo['buy_amount'] += $v['buy_amount'];
//            $totalInfo['prize_amount'] += $v['prize_amount'];
//            $totalInfo['rebate_amount'] += $v['rebate_amount'];
//            $totalInfo['game_win'] += $v['game_win'];
//            $totalInfo['promos'] += $v['promos'];
//            $totalInfo['diff'] += $v['diff'];
//            $totalInfo['reg_num'] += $v['reg_num'];
//            $totalInfo['first_deposit_num'] += $v['first_deposit_num'];
//            $totalInfo['first_deposit_amount'] += $v['first_deposit_amount'];
        }
        //预设查询框
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('teamReport', $teamReport);
        self::$view->setVar('totalInfo', $totalInfo);
        self::$view->render('user_teamreport');
    }

    //不活跃天数	余额	充值金额	提现金额	团队购买量	佣金	投注盈亏
    public function childReport()
    {
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        if ($startDate < date("Y-m-d", strtotime('-35 days'))) {
            $startDate = date("Y-m-d", strtotime('-35 days'));
        }
        if ($endDate < date('Y-m-d', strtotime('-35 days'))) {
            $endDate = date('Y-m-d', strtotime('-35 days'));
        }
        if ($startDate > $endDate) {
            $startDate = $endDate;
        }

        if (strlen($endDate) <= 10) {
            $startTime = "{$startDate} 00:00:00";
            $endTime = "{$endDate} 23:59:59";
        }
        else {
            $startTime = $startDate;
            $endTime = $endDate;
        }

        $flag = 0;
        $totalWithdraws = $totalDeposits = $childReport = $totalInfo = $users = $recentBuy = array();
        if ($user = users::getItem($username,8,false,1,1)) {
            if ($user['user_id'] == $GLOBALS['SESSION']['user_id']) {
                $flag = 1;
            }
            else {
                $allParent = users::getAllParent($user['user_id']);
                if (isset($allParent[$GLOBALS['SESSION']['user_id']])) {
                    $flag = 1;
                }
            }
        }

        if ($flag == 1) {
            $teamDeposit = deposits::getTeamDeposits($user['user_id'], $startDate, $endDate);
            $teamWithdraw = withdraws::getTeamWithdraws($user['user_id'], $startDate, $endDate);
            $teamBalance = users::getTeamBalances($user['user_id']);
            //先得到自己和直接下级
            $users = users::getItems($user['user_id'], true, 0, array('user_id', 'balance', 'last_time'), -1);

            foreach ($users as $k => $v) {
                $users[$k]['inactive_days'] = $v['last_time'] == '0000-00-00 00:00:00' ? '从未登录' : ceil((time() - strtotime($v['last_time'])) / 86400);
            }

            //团队购买量 各级代理总返点量 各用户自身投注的奖金
            $childReport = projects::getChildReport($user['user_id'], $startTime, $endTime);

            $totalInfo = array('balance' => 0, 'deposit' => 0, 'withdraw' => 0, 'amount' => 0, 'rebate' => 0, 'contribute_rebate' => 0, 'prize' => 0, 'pt_game_win' => 0, 'pt_buy_amount' => 0, 'pt_amount' => 0, 'pt_prize' => 0, 'final' => 0,);
            foreach ($childReport as $k => $v) {
                $totalInfo['amount'] += $v['total_amount'];
                $totalInfo['prize'] += $v['total_prize'];
                $totalInfo['rebate'] += $v['total_rebate'];  //返点
                $totalInfo['contribute_rebate'] += $v['total_contribute_rebate']; //下级佣金量
                $totalInfo['pt_game_win'] += $v['pt_game_win'];
                $totalInfo['pt_prize'] += $v['pt_prize'];    //PT中奖
                $totalInfo['pt_buy_amount'] += $v['pt_buy_amount'];
                $totalInfo['pt_amount'] += $v['pt_amount'];
            }
            $totalInfo['balance'] = $teamBalance['team_total_balance'];
            $totalInfo['deposit'] = $teamDeposit['team_total_deposit'];
            $totalInfo['withdraw'] = $teamWithdraw['team_total_withdraw'];
            $totalInfo['final'] = $totalInfo['rebate'] + $totalInfo['prize'] - $totalInfo['amount'];

            self::$view->setVar('teamBalance', $teamBalance);
            self::$view->setVar('teamDeposit', $teamDeposit);
            self::$view->setVar('teamWithdraw', $teamWithdraw);
        }

        //把数据都组装进childReport数组，以方便排序
        foreach ($childReport as $k => $v) {
            $user_id = $v['user_id'];
            $childReport[$k]['user_balance'] = isset($teamBalance[$user_id]['total_balance']) ? $teamBalance[$user_id]['total_balance'] : $totalInfo['balance'];
            $childReport[$k]['total_deposit'] = isset($teamDeposit[$user_id]['total_deposit']) ? $teamDeposit[$user_id]['total_deposit'] : $totalInfo['deposit'];
            $childReport[$k]['total_withdraw'] = isset($teamWithdraw[$user_id]['total_withdraw']) ? $teamWithdraw[$user_id]['total_withdraw'] : $totalInfo['withdraw'];
            $childReport[$k]['user_inactive_days'] = $users[$user_id]['inactive_days'];
            $childReport[$k]['profit_and_lost'] = ($v['total_rebate'] + $v['total_prize'] - $v['total_amount']);
            $childReport[$k]['total_rebate'] = $v['total_rebate']; //返点
            $childReport[$k]['total_contribute_rebate'] = $v['total_contribute_rebate']; //下级佣金量
            $childReport[$k]['pt_game_win'] = $v['pt_game_win'];
            $childReport[$k]['pt_prize'] = $v['pt_prize'];   //PT中奖
            $childReport[$k]['pt_buy_amount'] = $v['pt_buy_amount'];
            $childReport[$k]['pt_amount'] = $v['pt_amount'];
        }

        //排序字段名合法，则做排序 
        if (in_array($sortKey, array('username', 'user_inactive_days', 'user_balance',
                    'total_deposit', 'total_withdraw', 'total_amount',
                    'total_contribute_rebate', 'total_prize', 'profit_and_lost', 'pt_game_win', 'pt_buy_amount', 'pt_amount'))) {
            $sort_arr = array(); //这个变量用于多维排序
            foreach ($childReport as $key => $row) {
                $sort_arr[$key] = $row[$sortKey];
            }
            if ($sortDirection > 0) {
                array_multisort($sort_arr, SORT_ASC, $childReport);
            }
            else {
                array_multisort($sort_arr, SORT_DESC, $childReport);
            }
        }

        //预设查询值
        self::$view->setVar('username', $username);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('sortKey', $sortKey);
        self::$view->setVar('sortDirection', $sortDirection);

        self::$view->setVar('flag', $flag);
        self::$view->setVar('childReport', $childReport);
        self::$view->setVar('totalDeposits', $totalDeposits);
        self::$view->setVar('totalWithdraws', $totalWithdraws);
        self::$view->setVar('totalInfo', $totalInfo);
        self::$view->setVar('users', $users);
        self::$view->setVar('user', $user);
        self::$view->setVar('recentBuy', $recentBuy);
        self::$view->setVar('selfUsername', $GLOBALS['SESSION']['username']);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加用户', 'url' => url('user', 'addUser'))));
        self::$view->render('user_childreport');
    }

    //显示团队或个人优惠详细列表
    public function teamPromosList()
    {
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']); //默认自己
        $include_childs = $this->request->getGet('include_childs', 'intval', 1); //是否包含下级
        $is_test = 0; //正式账号
        $status = 8; //已成功
        $error_str = '';  //错误显示控制:有错误不显示记录，显示错误信息
        $types = array(5, 6, 9);  //前台只显示：其它优惠/充值返佣/活动红包
        $startDate = $this->request->getGet('startDate', 'trim');
        $endDate = $this->request->getGet('endDate', 'trim');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        if (empty($startDate) || empty($endDate)) {
            showMsg('参数错误！');
        }
        if ($startDate < date('Y-m-d', strtotime('-40 days'))) {
            $startDate = date('Y-m-d', strtotime('-40 days'));
        }
        if ($endDate < date('Y-m-d', strtotime('-40 days'))) {
            $endDate = date('Y-m-d', strtotime('-40 days'));
        }
        if ($startDate > $endDate) {
            $startDate = $endDate;
        }

        //先得到用户id
        if (!$search_user = users::getItem($username)) {
            showMsg('此用户不存在！');
        }

        //校验是否超出用户权限
        if ($username != $GLOBALS['SESSION']['username']) {
            //根据自己的权限列出可查找的用户
            // $check_users = users::getUserTree($GLOBALS['SESSION']['user_id'], true, 1, 8);
            $check_users = users::getUserTreeField([
                'field' => ['user_id'],
                'parent_id' => $GLOBALS['SESSION']['user_id'],
                'recursive' => 1,
                'status' => 8
            ]);
            if (!isset($check_users[$search_user['user_id']])) {
                showMsg('您无权查看此用户！');
            }
        }

        //以后可以接受前台用户定义的排序参数(预留变更)
        $order_by = ' ORDER BY a.finish_time DESC';
        $trafficInfo = promos::getTrafficInfo($username, $include_childs, $is_test, $status, $types, '',$startDate, $endDate);
        /***************** snow  获取正确的分页开始****************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /***************** snow  获取正确的分页开始****************************/

        $promos = promos::getItems($username, $include_childs, $is_test, $status, $types, $startDate, $endDate, $order_by, $startPos, DEFAULT_PER_PAGE);

        if (empty($promos)) {
            $error_str = '无查询记录';
        }

        //查询选项
        self::$view->setVar('username', $username);
        self::$view->setVar('promoType', $GLOBALS['cfg']['promoTypes']);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('promos', $promos);
        self::$view->setVar('error_str', $error_str);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('pageList', getPageListMobile($trafficInfo['count'], DEFAULT_PER_PAGE));

        self::$view->render('user_teampromoslist');
    }

    //显示团队或个人优惠金额汇总
    public function teamPromos()
    {
        //默认自己
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
        $include_childs = 1; //是否包含下级
        $status = 8; //已成功
        $error_str = '';  //错误显示控制:有错误不显示记录，显示错误信息
        $types = array(5, 6, 9);  //前台只显示：其它优惠/充值返佣/活动红包

        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
        if ($startDate < date('Y-m-d', strtotime('-40 days'))) {
            $startDate = date('Y-m-d', strtotime('-40 days'));
        }
        if ($endDate < date('Y-m-d', strtotime('-40 days'))) {
            $endDate = date('Y-m-d', strtotime('-40 days'));
        }
        if ($startDate > $endDate) {
            $startDate = $endDate;
        }
        if (strlen($endDate) <= 10) {
            $startDate = "{$startDate} 00:00:00";
            $endDate = "{$endDate} 23:59:59";
        }

        //先得到用户id
        if (!$search_user = users::getItem($username)) {
            showMsg('此用户不存在！');
        }

        //校验是否超出用户权限
        if ($username != $GLOBALS['SESSION']['username']) {
            //根据自己的权限列出可查找的用户
            // $check_users = users::getUserTree($GLOBALS['SESSION']['user_id'], true, 1, 8);
            $check_users = users::getUserTreeField([
                'field' => ['user_id'],
                'parent_id' => $GLOBALS['SESSION']['user_id'],
                'recursive' => 1,
                'status' => 8
            ]);
            if (!isset($check_users[$search_user['user_id']])) {
                showMsg('您无权查看此用户！');
            }
        }

        $result = promos::getTotalAmountByUserName($username, $include_childs, $status, $types, $startDate, $endDate);
        if (!isset($result) || empty($result)) {
            $error_str = '无查询记录';
        }
        else {
            //计算团队汇总：自己+直属团队
            $team_total = 0;
            foreach ($result as $k => $v) {
                $team_total += $v['total_amount'];
            }
        }

        //查询选项
        self::$view->setVar('username', $username);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('result', isset($result) ? $result : '');
        self::$view->setVar('error_str', $error_str);
        self::$view->setVar('team_total', isset($team_total) ? $team_total : '');
        self::$view->render('user_teampromos');
    }

    // 显示用户所有团队下（包括自己的购买行为）的有返点记录的订单及返点金额
    /* 此功能新版已不再使用
      public function childRebateList()
      {
      $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
      $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d'));
      $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d', strtotime('+1 day')));
      $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
      if (strtotime($startDate) < strtotime('-7 days')) {
      $startDate = date('Y-m-d', strtotime('-7 days'));
      }
      if ($endDate <= $startDate) {
      $endDate = '';
      }
      $users = $userDiffRebates = array();
      $userDiffRebatesNumber = 0;
      if ($user = users::getItem($username)) {
      $userDiffRebates = userDiffRebates::getUserDiffRebates($user['user_id'], $startDate, $endDate, $startPos, DEFAULT_PER_PAGE);
      $userDiffRebatesNumber = userDiffRebates::getUserDiffRebatesNumber($user['user_id'], $startDate, $endDate);
      $users = users::getItemsById(array_keys(array_spec_key($userDiffRebates, 'package_user_id')));
      foreach ($userDiffRebates as $k => $v) {
      $userDiffRebates[$k]['wrap_id'] = projects::wrapId($v['package_id'], $v['issue'], $v['lottery_id']);
      $userDiffRebates[$k]['amount'] = number_format($v['rebate_amount'] / $v['diff_rebate'], 2);
      }
      }

      //设置默认查询选项
      self::$view->setVar('username', $username);
      self::$view->setVar('startDate', $startDate);
      self::$view->setVar('endDate', $endDate);

      self::$view->setVar('userDiffRebates', $userDiffRebates);
      self::$view->setVar('users', $users);
      self::$view->setVar('pageList', getPageListMobile($userDiffRebatesNumber, DEFAULT_PER_PAGE));
      //self::$view->setVar('actionLinks', array(0 => array('title' => '增加用户', 'url' => url('user', 'addUser'))));
      self::$view->render('user_childrebatelist');
      }
     *
     */


    public function childPackageList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        $package_id = projects::dewrapId($wrap_id);
        $issue = $this->request->getGet('issue', 'trim');
        $is_trace = $this->request->getGet('is_trace', 'intval', -1);
        $check_prize_status = $this->request->getGet('check_prize_status', 'intval', -1);
        $include_childs = $this->request->getGet('include_childs', 'intval', '1|0');
        $status = $this->request->getGet('status', 'intval', -1);
        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $this->searchDate($start_time, $end_time);
        if(($username = $this->request->getGet('username', 'trim')) && $username!= $GLOBALS['SESSION']['username'])
        {
            if(!$user = users::getItem($username)){
                showMsg("非法请求，该用户不存在或已被冻结");
            }
            if(!in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))){
                showMsg("非法请求，此用户不是你的下级");
            }
            $userId = $user['user_id'];
        }else{
            $username = $GLOBALS['SESSION']['username'];
            $userId = $GLOBALS['SESSION']['user_id'];
        }

        //得到所有彩种
        $lotterys = lottery::getItemsNew(['cname','lottery_id','zx_max_comb','total_profit','property_id']);

        //得到所有彩种奖期示例
        $lotteryIssueFormat = stripslashes(config::getConfig('lottery_issue_format'));

        $realAmount = $totalAmount = $totalPrize = $totalProfit = 0;

        if ($package_id) {
            $packages = projects::getPackagesById(array($package_id));
            $packagesNumber = 1;
            $packagesTotal = [];
        }
        else {
            $packagesNumber = projects::getPackagesNumber($lottery_id, $check_prize_status, -1, $issue, $is_trace, 0, $userId, $include_childs, $start_time, $end_time, $status);
            /***************** snow  获取正确的分页开始****************************/
            $curPage  = $this->request->getGet('curPage', 'intval', 1);
            $startPos = getStartOffset($curPage, $packagesNumber);
            /***************** snow  获取正确的分页开始****************************/
            $packages = projects::getPackages($lottery_id, $check_prize_status, -1, $issue, $is_trace, 0, $userId, $include_childs, $start_time, $end_time, '', '', $status, '', $startPos, DEFAULT_PER_PAGE);
            $packagesTotal = projects::getPackageTotal($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', -1, 0, $userId, $include_childs, $start_time, $end_time, '', '', $status);
//            $packagesTotal['total_profit'] = 0;
        }

        //为算出奖金，先得到这些用户的返点
        if ($packages) {
            $user_ids = array_keys(array_spec_key($packages, 'user_id'));
            $userRebates = userRebates::getUsersRebates($user_ids, 0, 1);

            foreach ($packages as $k => $v)
            {
                $packages[$k]['amount'] = number_format($v['amount'], 4).'元';

                $packages[$k]['wrap_id'] = projects::wrapId($v['package_id'], $v['issue'], $v['lottery_id']);
                $packages[$k]['prize_mode'] = 2 * $lotterys[$v['lottery_id']]['zx_max_comb'] * (1 - $lotterys[$v['lottery_id']]['total_profit'] + $userRebates[$v['user_id']][$lotterys[$v['lottery_id']]['property_id']] - $v['cur_rebate']);

                if ($v['cancel_status'] == 0) $realAmount += $v['amount'];
                if ($v['check_prize_status'] == 1) $totalPrize += $v['prize'];

                if($v['cancel_status'] > 0 || $v['check_prize_status'] == 0)
                {
                    $packages[$k]['prize'] = '--';
                    $packages[$k]['profit'] = '--';
                }
                else{
                    $profit = $v['prize'] - $v['amount'];
                    $packages[$k]['prize'] = number_format($v['prize'], 4).'元';
                    $packages[$k]['profit'] = number_format($profit, 4).'元';
                    $totalProfit += $profit;
                }
                $totalAmount += $v['amount'];
            }
        }

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('wrap_id', $wrap_id);
        self::$view->setVar('issue', $issue);
        self::$view->setVar('is_trace', $is_trace);
        self::$view->setVar('check_prize_status', $check_prize_status);
        self::$view->setVar('username', $username);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('status', $status);

        self::$view->setVar('packages', $packages);

        self::$view->setVar('packagesTotal', $packagesTotal);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('realAmount', $realAmount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('totalProfit', $totalProfit);
        self::$view->setVar('packagesNumber', $packagesNumber);

        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('lotteryIssueFormat', $lotteryIssueFormat);
        self::$view->setVar('pageListMobile', getPageListMobile($packagesNumber, DEFAULT_PER_PAGE));
        self::$view->render('user_childpackagelist');
    }

    //设置用户返点 可高不许低
/*    public function setRebate()
    {
        if (!$user_id = $this->request->getGet('user_id', 'intval')) {
            $user_id = $this->request->getPost('user_id', 'intval');
        }
        //检查是不是自己的孩子
        $users = users::getUserTree($GLOBALS['SESSION']['user_id']);
        $selfUser = $users[$GLOBALS['SESSION']['user_id']];
        unset($users[$GLOBALS['SESSION']['user_id']]);
        if (!isset($users[$user_id])) {
            showMsg('您不能跨级设定返点');
        }
//logdump($user_id, $selfUser);
        if ($this->request->getPost('submitBtn', 'trim')) {
            $user_id = $this->request->getPost('user_id', 'trim');
            $rebates = $this->request->getPost('rebates', 'array');
//dump($rebates);die();
            if (!userRebates::saveRebate($user_id, $rebates)) {
                showMsg("设置返点失败");
            }

            showMsg("设置成功", 1);
        }
//logdump(array($users[$user_id]['user_id'], $users[$user_id]['parent_id']));
        //得到自己和上级的返点
        $userRebates = userRebates::getUsersRebates(array($users[$user_id]['user_id'], $users[$user_id]['parent_id']), 0, 1);
        $selfRebate = $userRebates[$user_id];
        $parentRebate = $userRebates[$users[$user_id]['parent_id']];
//logdump($selfRebate, $parentRebate);
        //得到彩种代表
        $representLotterys = lottery::getRepresent();
        self::$view->setVar('representLotterys', $representLotterys);
//logdump($parentRebate, $selfRebate);
        //得到可调返点范围
        $allow = array();
        foreach ($representLotterys as $property_id => $lottery) {
            if (!isset($parentRebate[$property_id])) {
                showMsg($GLOBALS['cfg']['property'][$property_id] . " : 联系上级或公司设置完成自己的返点,才能给下级设置返点");
            }
            if (!isset($selfRebate[$property_id])) {
                //设置下级的返点有个逻辑，取比自己低等级的列表
                $selfRebate[$property_id] = -1;
            }
            $gaps = userRebates::genRebateGap($lottery, $parentRebate[$property_id], false);    //不再有平级功能，所以必须小于自己的返点 $selfUser['level'] < 2 && $selfUser['quota'] > 0
            $tmp = array();
//logdump($gaps);
            foreach ($gaps as $vv) {
                if ($vv['rebate'] <= $parentRebate[$property_id] && $vv['rebate'] >= $selfRebate[$property_id]) {
                    if ($property_id == 4) {
                        //JYZ-283快乐扑克特殊处理：使用豹子11050代替转直注数,所以整体/24
                        $vv['prize'] = floor($vv['prize'] / 24);
                    }
                    $tmp[] = $vv;
                }
            }
            $allow[$property_id] = $tmp;
        }

        $balance = users::getTeamBalance($user_id);
        self::$view->setVar('balance', $balance);

        self::$view->setVar('allow', $allow);
        self::$view->setVar('selfRebate', $selfRebate);
        self::$view->setVar('parentRebate', $parentRebate);
        self::$view->setVar('user', $users[$user_id]);

        self::$view->setVar('properties', $GLOBALS['cfg']['property']);
        self::$view->render('user_setrebate');
    }*/

    public function setRebate()
    {
        $user_id = $this->request->getGet('user_id', 'intval',$this->request->getPost('user_id', 'intval'));
        $user = users::getItem($GLOBALS['SESSION']['user_id']);
        $subUser = users::getItem($user_id);

        if($subUser['parent_id'] != $user['user_id']) die(json_encode(array('errno' => 1, 'errstr' => "此用户不是你的下级")));

        $subPrizeMode = userRebates::userPrizeMode($subUser['user_id']);
        $aPrizeMode = userRebates::setSubPrizeModes($subUser, false);

        foreach ($aPrizeMode as $prizeMode=>$rebate){
            if($prizeMode < $subPrizeMode) unset($aPrizeMode[$prizeMode]);
        }

        if(!$aPrizeMode) die(json_encode(array('errno' => 2, 'errstr' => "此用户不能设置返点")));

        if($setPrizeMode = $this->request->getPost('prize_group', 'intval'))
        {
            if(!in_array($setPrizeMode, array_keys($aPrizeMode))) die(json_encode(array('errno' => 3, 'errstr' => "返点错误")));

            if(userRebates::updateRebate($subUser, $setPrizeMode) === false){
                die(json_encode(array('errno' => 3, 'errstr' => "设置返点失败")));
            } else {
                die(json_encode(array('errno' => 0, 'errstr' => "设置返点成功")));
            }
        }

        self::$view->setVar('user', $subUser);
        self::$view->setVar('aPrizeMode', $aPrizeMode);
        self::$view->setVar('subPrizeMode', $subPrizeMode);

        self::$view->render('user_setrebate');
    }
    /**
     * 下级开户
     * 1.判断其group_id
     * 2.检查返点值（奖金）是否正确
     * 3.看users表还有哪些需要的字段
     */
    public function regChild()
    {
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }
        $op = $this->request->getPost('op', 'trim');
        //生成微信二维码
        if($op == 'createWeixinCode')
        {
            $link = $this->request->getPost('link', 'trim','');
            $url = users::generalPromoteURLC($link);
            $result['errno'] = 0;
            $result['errstr'] = '';
            $result['url'] = $url;

            die(json_encode($result));
        }
        //AJAX生成推广代码
        //AJAX生成保存修改推广代码
        if ($op == "createCode") {
            $market_code = $this->request->getPost('market_code', 'trim','');
            $prize_mode = $this->request->getPost('prize_mode', 'intval',0);
            $result['errno'] = 0;
            $result['errstr'] = '操作成功!';
            if($prize_mode  === 0)
            {
                $result['errno'] = 1;
                $result['errstr'] = '数据异常,请联系开发人员!';
                die(json_encode($result));
            }
            if(!preg_match('`^[0-9a-zA-Z]{3,10}$`',$market_code))
            {
                $result['errno'] = 1;
                $result['errstr'] = '推广码格式不正确!';
                die(json_encode($result));
            }
            $user_id=$GLOBALS['SESSION']['user_id'];
            $domain = $_SERVER['HTTP_HOST'];
            $url = "http://{$domain}/?var=" . $market_code;//生成的链接

            //判断进行的操作是增加还是修改
            $info = marketLink::getItemByCond(" prize_mode = '{$prize_mode}' AND user_id={$user_id}", 'prize_mode');
            $key = empty($info) ? 'ad' : 'up';
            //判断推广码是否已经存在
            $res = marketLink::getItemByCond(" market_code='{$market_code}'", 'prize_mode');

            if($key === 'ad')
            {
                if($res)
                {
                    $result['errno'] = 1;
                    $result['errstr'] = '推广码已经存在!';
                    die(json_encode($result));
                }
                $data=array(
                    'user_id'=>$user_id,
                    'market_code'=>$market_code,
                    'prize_mode'=>$prize_mode,
                    'link'=>$url
                );
                $res=marketLink::addItem($data);
                if(!$res)
                {
                    $result['errno'] = 1;
                    $result['errstr'] = '操作失败!';
                    die(json_encode($result));
                }
                $result['url'] = $url;
                die(json_encode($result));

            }//添加
            if($key === 'up')
            {
                $re = marketLink::getItemByCond("user_id ='{$user_id}' and prize_mode = '{$prize_mode}' and market_code='{$market_code}'", 'market_code');

                if($res && !$re)
                {
                    $result['errno'] = 1;
                    $result['errstr'] = '推广码已经存在!';
                    die(json_encode($result));
                }
                $data=array(
                    'user_id'=>$user_id,
                    'market_code'=>$market_code,
                    'prize_mode'=>$prize_mode,
                    'link'=>$url
                );
                marketLink::updateItem($data,['user_id'=>$GLOBALS['SESSION']['user_id'],'prize_mode'=>$prize_mode]);
                $result['url'] = $url;
                die(json_encode($result));
            }//修改
        }

        //得到彩种代表
        $representLotterys = lottery::getRepresent();
        self::$view->setVar('representLotterys', $representLotterys);
        //得到用户所有返点
        if (!$tmp = userRebates::getItems($GLOBALS['SESSION']['user_id'])) {
            log2("系统异常：用户{$GLOBALS['SESSION']['username']}找不到返点数据");
            showMsg('系统异常：找不到返点数据');
        }

        //030412 判断如果有一个返点为0则不允许开户
        foreach ($tmp as $v) {
            if ($v['rebate'] == 0 && !in_array($v['property_id'], array(7, 8, 9))) {//六合彩双色球不用检查返点
                log2("用户{$GLOBALS['SESSION']['username']}的{$GLOBALS['cfg']['property'][$v['property_id']]}返点已经为0，不能开户");
                showMsg("您的{$GLOBALS['cfg']['property'][$v['property_id']]}返点已经为0，不能开户");
            }
        }

        /*        $selfRebate = array();
                foreach ($tmp as $v) {
                    //$v['prize'] = 2 * $lotterys[$v['lottery_id']]['zx_max_comb'] * (1 - $lotterys[$v['lottery_id']]['total_profit'] + $v['rebate']);
                    $selfRebate[$v['property_id']] = $v['rebate'];
                }*/
        //self::$view->setVar('rebates', $selfRebates);
        //得到返点差列表
        /*        $gaps = array();
                foreach ($representLotterys as $lottery) {
                    //新追加快三：先有用户后设置返点，会出现上级返点未设置的异常
                    if (!isset($selfRebate[$lottery['property_id']])) {
                        showMsg($GLOBALS['cfg']['property'][$lottery['property_id']] . " : 联系上级或公司设置完成自己的返点,才能注册下级");
                    }
                    $gaps[$lottery['property_id']] = userRebates::genRebateGap($lottery, $selfRebate[$lottery['property_id']], true);  //开放高点，取消平级帐号
                }
                self::$view->setVar('gaps', $gaps);*/

        //提交
        if ($this->request->getPost('submit', 'trim')) {
            $username = strtolower($this->request->getPost('username', 'trim'));
            $password = $this->request->getPost('password', 'trim');
            $password2 = $this->request->getPost('password2', 'trim');
            $prize_mode = $this->request->getPost('prize_mode', 'trim');
            $childRebates = $this->request->getPost('childRebates', 'array');
            //数据正确性判断
            if (!$username || !preg_match('`^[a-z]\w{5,11}$`', $username)) {
                showMsg("用户名长度为6-12个字母或数字，且必须以字母开头");
            }
            if (!$password || strlen($password) < 6 || strlen($password) > 15 || preg_match('`^\d+$`', $password) || preg_match('`^[a-zA-Z]+$`', $password)) {
                showMsg("密码长度为6-15字符，不能为纯数字或纯字母");
            }
            if ($password != $password2) {
                showMsg('两次输入的密码不相同');
            }

            //检查返点
            /*            if (count($childRebates) != count($representLotterys)) {
                            showMsg('必须设定所有奖金值');
                        }*/
            /*
                        foreach ($childRebates as $property_id => $rebate) {
                            $flag = false;
                            foreach ($gaps[$property_id] as $vv) {
                                if ($vv['rebate'] == $rebate) {
                                    $flag = true;
                                }
                            }
                            if (!$flag) {
                                showMsg('奖金设定不正确');
                            }
                        }*/

            try {
                if (!users::addUser($username, $password, $prize_mode, $user, array(), 1)) {
                    showMsg("添加用户失败!请检查数据输入是否完整");
                }
            } catch (Exception $e) {
                showMsg($e->getMessage());
            }

            showMsg("添加用户成功", 1);
        }

        $aPrizeMode = userRebates::addSubPrizeModes($user);
        $marketLinkInfo = '';
        $key='';
        if(!empty($aPrizeMode)) {
            $key = key($aPrizeMode);

            $marketLinkInfo = marketLink::getItemByCond("user_id = '{$GLOBALS['SESSION']['user_id']}' and prize_mode =" . $key);
        }
        self::$view->setVar('key', $key);
        self::$view->setVar('user', $user);
        self::$view->setVar('aPrizeMode', $aPrizeMode);
        //推广链接不能有高点号
        self::$view->setVar('aLinkPrizeMode', $aPrizeMode);
        self::$view->setVar('marketLinkInfo', $marketLinkInfo);

        self::$view->render('user_regchild');
    }
    /**
     * 根据奖金组获取推广码
     */
    public function getMarketCodeAjax()
    {
        $prize_mode = $this->request->getPost('id', 'intval',0);
        $user_id =isset($GLOBALS['SESSION']['user_id'])?$GLOBALS['SESSION']['user_id']:0;

        if(intval($user_id) <=0 || $prize_mode <= 0)
        {
            echo json_encode(['code'=> 0]);exit;
        }
        $info = marketLink::getItemByCond("user_id = $user_id and prize_mode = $prize_mode");
        if($info)
        {
            echo json_encode(['code'=> 200 ,'data' =>$info]);exit;
        }else{
            echo json_encode(['code'=> 400]);exit;
        }
    }
    /**
     * 下级开户
     * 1.判断其group_id
     * 2.检查返点值（奖金）是否正确
     * 3.看users表还有哪些需要的字段
     */
//    public function regChild()
//    {
//        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
//            showMsg("非法请求，该用户不存在或已被冻结");
//        }
//        $op = $this->request->getPost('op', 'trim');
//        //AJAX生成推广代码
//        if ($op == "createCode") {
//
//            $tgtype = $this->request->getPost('tgtype', 'intval', 0);
////            $childRebates = $this->request->getPost('childRebates', 'array');
//            $prize_mode = $this->request->getPost('prizemode', 'trim');
//            $flag = $this->request->getPost('flag', 'intval', 1);
//            $regType = $this->request->getPost('regType', 'intval', 1);
//
//            $result = array('errno' => 0, 'errstr' => '');
//            $url = users::generalPromoteURL($GLOBALS['SESSION']['user_id'], $prize_mode, $tgtype, $flag, $regType);
//            if (empty($url)) {
//                $result['errno'] = 1;
//                $result['errstr'] = '找不到推广域，请联系客服';
//                die(json_encode($result));
//            }
//            $result['url'] = $url;
//            die(json_encode($result));
//        }
//        //得到彩种代表
//        $representLotterys = lottery::getRepresent();
//        self::$view->setVar('representLotterys', $representLotterys);
//        //得到用户所有返点
//        if (!$tmp = userRebates::getItems($GLOBALS['SESSION']['user_id'])) {
//            log2("系统异常：用户{$GLOBALS['SESSION']['username']}找不到返点数据");
//            showMsg('系统异常：找不到返点数据');
//        }
//
//        //030412 判断如果有一个返点为0则不允许开户
//        foreach ($tmp as $v) {
//            if ($v['rebate'] == 0 && !in_array($v['property_id'], array(7,8,9))) {//六合彩双色球幸运28不用检查返点
//                log2("用户{$GLOBALS['SESSION']['username']}的{$GLOBALS['cfg']['property'][$v['property_id']]}返点已经为0，不能开户");
//                showMsg("您的{$GLOBALS['cfg']['property'][$v['property_id']]}返点已经为0，不能开户");
//            }
//        }
//
//
//        //提交
//        if ($this->request->getPost('submit', 'trim')) {
//            $username = strtolower($this->request->getPost('username', 'trim'));
//            $group_id = $this->request->getPost('group_id', 'intval', '2|3|4');//2 已代理 3 普通代理 4 会员
//            $password = $this->request->getPost('password', 'trim');
//            $password2 = $this->request->getPost('password2', 'trim');
//            $prize_mode = $this->request->getPost('prize_mode', 'trim');
////            $childRebates = $this->request->getPost('childRebates', 'array');
//            //数据正确性判断
//            if (!$username || !preg_match('`^[a-z]\w{5,11}$`', $username)) {
//                showMsg("用户名长度为6-12个字母或数字，且必须以字母开头");
//            }
//            if (!$password || strlen($password) < 6 || strlen($password) > 15 || preg_match('`^\d+$`', $password) || preg_match('`^[a-zA-Z]+$`', $password)) {
//                showMsg("密码长度为6-15字符，不能为纯数字或纯字母");
//            }
//            if ($password != $password2) {
//                showMsg('两次输入的密码不相同');
//            }
//
//            //只有代理才能开号!
//            if ($user['group_id'] == 4) {
//                showMsg('只有代理才能开号');
//            }
//
//            try {
//                if (!users::addUser($username, $password, $prize_mode, $user, array(),1)) {
//                    showMsg("添加用户失败!请检查数据输入是否完整");
//                }
//            } catch (Exception $e) {
//                showMsg($e->getMessage());
//            }
//
//            showMsg("添加用户成功", 1);
//        }
//        $aPrizeMode = userRebates::addSubPrizeModes($user);
//        self::$view->setVar('user', $user);
//        self::$view->setVar('aPrizeMode', $aPrizeMode);
//        //推广链接不能有高点号
//        self::$view->setVar('aLinkPrizeMode', $aPrizeMode);
//
//        self::$view->render('user_regchild');
//    }

    /**
     * 显示该用户（包括他本身）的所有团队余额之和
     */
    public function showTeam()
    {
        $user_id = $this->request->getGet('user_id', 'intval');
        $allParent = users::getAllParent($user_id, true);
        if (count($allParent) < 2) {
            showMsg("系统出错：找不到上级");
        }
        $user = array_shift($allParent);
        $parent = array_shift($allParent);
        $balance = users::getTeamBalance($user_id);

        self::$view->setVar('user', $user);
        self::$view->setVar('parent', $parent);
        self::$view->setVar('balance', $balance);
        self::$view->render('user_showteam');
    }

    public function receiveBox()
    {
        $locations = array(0 => array('title' => '返回收件箱', 'url' => url('user', 'receiveBox')));
        //show all
        $isRead = -1;
        if ('delete' == $this->request->getPost('op', 'trim')) {
            if (!$deleteItems = $this->request->getPost('deleteItems', 'array')) {
                showMsg('请选择要删除的项目');
            }

            foreach ($deleteItems as $v) {
                if (!messages::deleteMsgTarget($v, false)) {
                    showMsg('删除失败');
                }
            }

            showMsg('删除成功', 1, $locations);
        }

        /***************** snow  获取正确的分页开始****************************/
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $messagsNumber = messages::getReceivesNumber($GLOBALS['SESSION']['user_id'], $isRead);
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $messagsNumber);
        /***************** snow  获取正确的分页开始****************************/
        $messages = messages::getReceives($GLOBALS['SESSION']['user_id'], $isRead, 1, $startPos, DEFAULT_PER_PAGE);
        if ($messages) {
            $users = users::getItemsById(array_keys(array_spec_key($messages, 'from_user_id')));
            self::$view->setVar('users', $users);
            foreach ($messages as &$v) {
                if ($v['type'] == 1) {
                    $v['title'] = preg_replace("/(q{1,2}\s*)?\d{5,10}/i", '', $v['title']);
                }
            }
        }

        self::$view->setVar('messages', $messages);

        self::$view->setVar('pageList', getPageListMobile($messagsNumber, DEFAULT_PER_PAGE));
        self::$view->render('user_receivebox');
    }

    public function sendBox()
    {
        $locations = array(0 => array('title' => '返回发件箱', 'url' => url('user', 'sendBox')));
        if ('delete' == $this->request->getPost('op', 'trim')) {
            if (!$deleteItems = $this->request->getPost('deleteItems', 'array')) {
                showMsg('请选择要删除的项目');
            }
            foreach ($deleteItems as $v) {
                if (!messages::deleteItem($v, false)) {
                    showMsg('删除失败');
                }
            }

            showMsg('删除成功', 1, $locations);
        }

        /***************** snow  获取正确的分页开始****************************/
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $messagsNumber = messages::getItemsNumber($GLOBALS['SESSION']['user_id']);
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $messagsNumber);
        /***************** snow  获取正确的分页开始****************************/
        $messages = messages::getItems($GLOBALS['SESSION']['user_id'], 1, '', '', $startPos, DEFAULT_PER_PAGE);

        if ($messages) {
            $userIds = array();
            foreach ($messages as &$v) {
                $v['title'] = preg_replace("/(q{1,2}\s*)?\d{5,10}/i", '', $v['title']);
                $userIds = array_merge($userIds, array_keys($v['targets']));
            }
            $users = users::getItemsById($userIds);
            self::$view->setVar('users', $users);
        }

        self::$view->setVar('messages', $messages);
        self::$view->setVar('pageList', getPageListMobile($messagsNumber, DEFAULT_PER_PAGE));
        self::$view->render('user_sendbox');
    }

    public function viewMsg()
    {
        $locations = array(0 => array('title' => '返回发件箱', 'url' => url('user', 'sendBox')));
        $msg_id = $this->request->getGet('msg_id', 'intval');
        $msgType = $this->request->getGet('msgType', 'trim', 'receive|send');
        //初始化用户名
        $username = '';
        if ($msgType == 'receive') {
            if (!$message = messages::getItem($msg_id, 0, NULL)) {
                showMsg('该消息不存在');
            }
            $allTargets = messages::getAllTargets($msg_id);

            if (!isset($allTargets[$GLOBALS['SESSION']['user_id']])) {
                showMsg('无权访问');
            }
            if ($message['type'] == 1) {
                $user = users::getItem($message['from_user_id']);
                //如果来自收件箱 显示发件人。
                $username = isset($user['username']) ? $user['username'] : '';
            }

            messages::updateHasRead($msg_id, $GLOBALS['SESSION']['user_id'], 1);
        }
        else {
            if (!$message = messages::getItem($msg_id, $GLOBALS['SESSION']['user_id'], NULL)) {
                showMsg('该消息不存在');
            }
            //如果来自发件箱 显示收件人。
            $allTargets = messages::getAlltargets($msg_id);
            $tmpUsername = array();
            foreach ($allTargets as $k => $v) {
                $tmpUsername[] = $v['username'];
            }
            //显示收件人列表
            $username = implode(",", $tmpUsername);
        }

        //前端用户之间无论是收发件都需要过滤  电话号码/QQ号等敏感信息
        if ($message['type'] == 1) {
            $message['title'] = preg_replace("/(q{1,2}\s*)?\d{5,10}/i", '', $message['title']);
            $message['content'] = preg_replace("/(q{1,2}\s*)?\d{5,10}/i", '', $message['content']);
        }

        self::$view->setVar('message', $message);
        self::$view->setVar('username', $username);
        self::$view->setVar('msgType', $msgType);
        self::$view->render('user_viewmsg');
    }

    /**
     * 给某个用户单独发消息
     * 代理只能给自己的直接下级发送（可群发） 会员只能和上级代理发消息
     */
    public function sendMsg()
    {
        $msg_id = $this->request->getGet('msg_id', 'intval');
        // $childs = users::getUserTree($GLOBALS['SESSION']['user_id'], false);
        $childs = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => $GLOBALS['SESSION']['user_id'],
            'includeSelf' => false
        ]);

        if(!$msg_id){
            $msg_id = $this->request->getPost('msg_id', 'intval');
        }

        if ($this->request->getPost('submit', 'trim')) {
            $target = $this->request->getPost('target', 'trim', 'parent|child');
            $title = $this->request->getPost('title', 'trim');
            $content = $this->request->getPost('content', 'trim');
            $selectChild = $this->request->getPost('selectChild', 'array');
            if (!$title) {
                showMsg('请填写标题');
            }

            //回复某条留言
            if ($msg_id > 0) {
                if (!$message = messages::getItem($msg_id)) {
                    showMsg('该消息不存在');
                }
                $to_user_ids = array($message['from_user_id']);
            }
            else {
                if ($target == 'parent') {
                    if (!$GLOBALS['SESSION']['parent_id']) {
                        showMsg('非法请求');
                    }
                    $to_user_ids = array($GLOBALS['SESSION']['parent_id']);
                }
                else {
                    if (!$selectChild) {
                        showMsg('没有选择发送目标');
                    }

                    foreach ($selectChild as $k => $v) {
                        if (!isset($childs[$v])) {
                            showMsg('发送目标不存在');
                        }
                    }
                    $to_user_ids = $selectChild;
                }
            }

            $data = array(
                'title' => $title,
                'content' => $content,
                'from_user_id' => $GLOBALS['SESSION']['user_id'],
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 1,
            );
            if (!messages::addMsg($data, $to_user_ids)) {
                showMsg('发送消息失败');
            }

            showMsg('发送消息成功', 1);
        }
        if ($msg_id > 0) {
            if (!$message = messages::getItem($msg_id)) {
                showMsg('该消息不存在');
            }
            self::$view->setVar('message', $message);
        }
        $to_user_id = $this->request->getGet('user_id', 'intval');
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg('该用户不存在');
        }

        self::$view->setVar('user', $user);
        self::$view->setVar('to_user_id', $to_user_id);
        self::$view->setVar('msg_id', $msg_id);
        self::$view->setVar('childs', $childs);
        self::$view->render('user_sendmsg');
    }


    public function setPwd(){
        self::$view->render('user_setpwd');
    }

    /**
     * 修改安全码
     * @throws exception2
     */
    public function editSafePwd()
    {
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            exit(json_encode([
                'errno'=>1,'errstr'=>'非法请求，该用户不存在或已被冻结'
            ]));
        }

        if ($this->request->getPost('submit', 'trim'))
        {
            $oldSafePwd = $this->request->getPost('old_safe_pwd', 'trim');
            $safePwd = $this->request->getPost('safe_pwd', 'trim');
            $safePwd2 = $this->request->getPost('safe_pwd2', 'trim');

            //数据正确性判断
            if (!$safePwd || $safePwd != $safePwd2) {
                exit(json_encode([
                    'errno'=>2,'errstr'=>'两次输入的密码必须一致'
                ]));
            }
            if (!preg_match('`^\w{6,12}$`', $safePwd) || preg_match('`^[a-zA-Z]+$`', $safePwd) || preg_match('`^\d+$`', $safePwd)) {
                exit(json_encode([
                    'errno'=>3,'errstr'=>'安全码必须是6-12位字母数字混合，且不能为全是数字或全是字母'
                ]));
            }

            //修改数据
            $data = array(
                'safe_pwd' => generateEnPwd($safePwd),
            );
/*            if($data['safe_pwd'] == $user['safe_pwd'] || users::getItemBySafePwd($data['safe_pwd'])){
                showMsg("安全码无效");
            }*/
            if($data['safe_pwd'] == $user['safe_pwd']){
                exit(json_encode([
                    'errno'=>4,'errstr'=>'不能与原密码相同'
                ]));
            }
            if($user['safe_pwd'] && !$oldSafePwd){
                exit(json_encode([
                    'errno'=>5,'errstr'=>'请填写当前安全码'
                ]));
            }
            if($user['safe_pwd']){
                $flag =  users::checkSafePwd($user['safe_pwd'], $oldSafePwd,null,true,false);
                if( $flag != null ){
                    exit(json_encode([
                        'errno'=>18,'errstr'=>$flag
                    ]));
                }
            }


            if($data['safe_pwd'] == $user['secpwd'] || md5($safePwd) == $user['pwd']){

                exit(json_encode([
                    'errno'=>6,'errstr'=>'安全码必须和登陆密码和资金密码不一致'
                ]));
            }
            
            //dump($data);
            if (!users::updateItem($GLOBALS['SESSION']['user_id'], $data)) {

                exit(json_encode([
                    'errno'=>7,'errstr'=>'安全码必须和登陆密码和资金密码不一致'
                ]));
            }

            exit(json_encode([
                'errno'=>0,'errstr'=>'修改成功'
            ]));
        }

        self::$view->setVar('user', users::getItem($GLOBALS['SESSION']['user_id']));
        self::$view->setVar('existSafePwd', !empty($user['safe_pwd']));
        self::$view->render('user_editsafepwd');
    }

 //修改资金密码
    public function editSecPwd()
    {
        $user = users::getItem($GLOBALS['SESSION']['user_id']);
        $key = empty($user['secpwd'])?'add':'up';
        if ($this->request->getPost('submit', 'trim')) {
            $secpassword = $this->request->getPost('secpassword', 'trim');
            $secpassword2 = $this->request->getPost('secpassword2', 'trim');
            $oldsecpwd = $this->request->getPost('oldsecpwd', 'trim');
            /**************************** author snow 下面这行代码覆盖了上面的代码**********************************************/
//            $key = $this->request->getPost('key', 'trim');
            /**************************** author snow 下面这行代码覆盖了上面的代码**********************************************/
//            $safePwd = $this->request->getPost('safe_pwd', 'trim');

            //数据正确性判断
            if (!$secpassword || $secpassword != $secpassword2) {
                exit(json_encode([
                    'errno' => 1, 'errstr' => '两次输入的密码必须一致'
                ]));
            }
            if (!preg_match('`^\w{6,16}$`', $secpassword) || preg_match('`^[a-zA-Z]+$`', $secpassword) || preg_match('`^\d+$`', $secpassword)) {
                exit(json_encode([
                    'errno' => 2, 'errstr' => '密码必须是6-16位字母数字混合，且不能为全是数字或全是字母'
                ]));
            }

            if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
                exit(json_encode([
                    'errno' => 3, 'errstr' => '非法请求，该用户不存在或已被冻结'
                ]));
            }
            if($key == 'up') {
                if ($user['secpwd'] != generateEnPwd($oldsecpwd)) {
                    exit(json_encode([
                        'errno' => 1, 'errstr' => '旧的资金密码不正确'
                    ]));
                }
            }
//            $flag =  users::checkSafePwd($user['safe_pwd'], $safePwd,null,false,false);
//            if( $flag != null ){
//                exit(json_encode([
//                    'errno'=>18,'errstr'=>$flag
//                ]));
//            }
            //修改数据
            $data = array(
                'secpwd' => generateEnPwd($secpassword),
            );

            if ($data['secpwd'] == $user['secpwd']) {
                exit(json_encode([
                    'errno' => 4, 'errstr' => '资金密码与之前相同'
                ]));
            }

            if (!users::updateItem($GLOBALS['SESSION']['user_id'], $data)) {
                exit(json_encode([
                    'errno' => 5, 'errstr' => '修改资金密码失败'
                ]));
            }
            exit(json_encode([
                'errno' => 0, 'errstr' => '修改资金密码成功'
            ]));
        }

//        if(!$user['safe_pwd']){
//            showMsg("您尚未设置安全码，请先 <a href='javascript:void(0);' onclick=window.location.href='?c=user&a=editSafePwd';parent.layer.closeAll();>点此设置安全码</a>");
//        }


        self::$view->setVar('key', $key);
        self::$view->setVar('user', users::getItem($GLOBALS['SESSION']['user_id']));
        self::$view->render('user_editsecpwd');
    }

    //修改登陆密码
    public function editPwd()
    {
        //处理修改游客账号及密码
        if($this->request->getPost('editTourist', 'trim')){
            $username = $this->request->getPost('username', 'trim');
            $pwd = $this->request->getPost('pwd', 'trim');

            if (!$username || !preg_match('`^[a-z]\w{5,11}$`', $username)) {
                die(json_encode(array('errno' => 9901, 'errstr' => '用户名长度为6-12个字母或数字，且必须以字母开头')));
            }

            //检查用户名是否已注册
            if (users::getItem($username, -1)) {
                die(json_encode(array('errno' => 9906, 'errstr' => '该用户' . $username . '已经存在')));
            }

            if (!$pwd || strlen($pwd) < 6 || strlen($pwd) > 15 || preg_match('`^\d+$`', $pwd) || preg_match('`^[a-zA-Z]+$`', $pwd)) {
                die(json_encode(array('errno' => 9902, 'errstr' => '密码长度为6-15字符，不能为纯数字或纯字母')));
            }

            if (users::updateItem($GLOBALS['SESSION']['user_id'], array('username' => $username, 'pwd' => password_hash($pwd,PASSWORD_DEFAULT), 'is_tourist' => 0))) {
                $GLOBALS['SESSION']['username'] = $username;
                exit(json_encode(array('errno' => 0, 'errstr' => '修改成功请谨记您的新账号:'.$username)));
            }
            exit(json_encode(array('errno' => 7, 'errstr' => '网络存在异常，修改账号失败,如需帮助请联系客服人员')));
        }
        if ($this->request->getPost('submit', 'trim')) {

            $password = $this->request->getPost('password', 'trim');
            $password2 = $this->request->getPost('password2', 'trim');
            $safePwd = $this->request->getPost('safe_pwd', 'trim');

            //数据正确性判断
            if (!$password || $password != $password2) {
                //showMsg("两次输入的密码必须一致");
                exit(json_encode([
                    'errno'=>1,'errstr'=>'两次输入的密码必须一致'
                ]));
            }
            if (!preg_match('`^\w{6,15}$`', $password) || preg_match('`^[a-zA-Z]+$`', $password) || preg_match('`^\d+$`', $password)) {
               // showMsg("密码必须是6-15位字母数字混合，且不能为全是数字或全是字母");
                exit(json_encode([
                    'errno'=>2,'errstr'=>'密码必须是6-15位字母数字混合，且不能为全是数字或全是字母'
                ]));
            }
            if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
                exit(json_encode([
                    'errno'=>3,'errstr'=>'非法请求，该用户不存在或已被冻结'
                ]));
            }

//           $flag =  users::checkSafePwd($user['safe_pwd'], $safePwd,null,false,false);
//            if( $flag != null ){
//                exit(json_encode([
//                    'errno'=>18,'errstr'=>$flag
//                ]));
//            }

            $enPassword = generateEnPwd($password);

            if($enPassword == $user['secpwd']){
                exit(json_encode([
                    'errno'=>4,'errstr'=>'登陆密码必须和资金密码和安全码不一致'
                ]));
            }

            //修改数据
            $data = array(
                'pwd' => password_hash($password, PASSWORD_DEFAULT),
            );
            if (!users::updateItem($GLOBALS['SESSION']['user_id'], $data)) {
                exit(json_encode([
                    'errno'=>5,'errstr'=>'修改登录密码失败'
                ]));
            }
            exit(json_encode([
                'errno'=>0,'errstr'=>'修改登录密码成功'
            ]));
        }
        $user = users::getItem($GLOBALS['SESSION']['user_id']);
//        if(!$user['safe_pwd']){
//            showMsg("您尚未设置安全码，请先 <a href='index.jsp?c=user&a=editSafePwd';>点此设置安全码</a>");
//        }
        self::$view->setVar('user', $user);
        self::$view->render('user_editpwd');
    }

    //修改资料
    public function editProfile()
    {
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        //修改数据
        if ($this->request->getPost('submitBtn', 'trim')) {
            $nick_name = $this->request->getPost('nick_name', 'trim');
            $qqnum = $this->request->getPost('qqnum', 'trim');
            $province = $this->request->getPost('province', 'trim');
            $city = $this->request->getPost('city', 'trim');

            //数据正确性判断
            if (!$nick_name || strlen($nick_name) > 18) {
                showMsg("请输入正确的昵称，18个字符以内");
            }
            if (!$qqnum || !preg_match('`^[1-9]\d{4,9}$`', $qqnum)) {
                showMsg("请输入正确的QQ号(5~10位数字)");
            }

            //更新记录
            $data = array(
                'nick_name' => $nick_name,
                'qq' => $qqnum,
            );
            if ($province && $city) {
                $data['province'] = $province;
                $data['city'] = $city;
            }
            if (users::updateItem($GLOBALS['SESSION']['user_id'], $data) === false) {
                showMsg("修改资料失败");
            }

            showMsg("修改资料成功", 1);
        }

        self::$view->setVar('user', $user);
        self::$view->render('user_editprofile');
    }

    //修改安全资料
    public function editSecurity()
    {
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
//            $real_name = $this->request->getPost('real_name', 'trim');
            $secpassword = $this->request->getPost('secpassword', 'trim');
            $secpassword2 = $this->request->getPost('secpassword2', 'trim');
//            $email = $this->request->getPost('email', 'trim');

/*            if ($user['real_name'] || $email) {
                //showMsg("您已设置过安全资料，不能修改");
            }

            //严格检查真实姓名
            if (!$real_name || !preg_match('`^[\x80-\xff]{6,30}$`', $real_name)) {
                showMsg("真实姓名填写不正确，必须为中文");
            }*/
            //严格检查密码规则
            if (!$secpassword || !$secpassword2 || $secpassword != $secpassword2) {
                showMsg("两次输入的资金密码不对");
            }
            if (!preg_match('`^\w{6,20}$`', $secpassword) || preg_match('`^[a-zA-Z]+$`', $secpassword) || preg_match('`^\d+$`', $secpassword)) {
                showMsg("资金密码必须是6-20位字母数字混合，且不能为纯数字或纯字母");
            }
            if (md5($secpassword) == $user['pwd']) {
                showMsg("资金密码不能和登录密码相同");
            }
            //严格检查邮箱
/*            if (!preg_match('`^[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+\.[_a-zA-Z\d\-]+$`', $email)) {
                showMsg('Email地址无效');
            }*/
//            //严格检查生日
//            if (!preg_match('`^19[4-9][0-9]-\d{2}-\d{2}$`', $birth) || !strtotime($birth)) {
//                showMsg('生日填写不正确');
//            }

            $data = array(
//                'real_name' => $real_name,
                'secpwd' => md5($secpassword),
//                'email' => $email,
                    //'birth' => $birth,
            );

            //检查是否有重复用户
/*            $flag = users::checkUnique($real_name, $email);
            $remark = $errstr = '';
            if ($flag < 0) {
                switch ($flag) {
                    //030509 真实姓名可以重复 因为有重名的情况
//                    case -2:
//                        $errstr = '真实姓名重复';
//                        break;
                    case -3:
                        $errstr = '邮箱重复';
                        break;
                    default:
                        break;
                }
            }
            if ($errstr != '') {
                showMsg($errstr);
            }*/

            //更新记录
            if (!$data) {
                showMsg('没有数据要更新');
            }

            if (users::updateItem($GLOBALS['SESSION']['user_id'], $data) === false) {
                showMsg("修改安全资料失败");
            }
            $link[] = array('title' => '返回');
            $link[] = array('url' => '/?c=fin&a=bindCard', 'title' => '绑定银行卡');
            showMsg("设置成功，请牢记您的安全资料：<br/>资金密码：{$secpassword}，安全邮箱：$email", 1, $link);
        }

        self::$view->setVar('user', $user);
        self::$view->render('user_editsecurity');
    }

    public function setting()
    {
        if ($this->request->getPost('style', 'trim')) {
            setcookie('styleDir', $this->request->getPost('style', 'trim'), time() + 864000);
            echo '1';
            die();
        }
        self::$view->render('user_setting');
    }

    public function recycle()
    {
        if ($this->request->getPost('submit', 'trim')) {
            $target_user_id = $this->request->getPost('target_user_id', 'intval');
            $remark = $this->request->getPost('remark', 'trim');
            if (!$target_user = users::getItem($target_user_id)) {
                showMsg('该用户不存在');
            }

            $data = array(
                'user_id' => $GLOBALS['SESSION']['user_id'],
                'target_user_id' => $target_user_id,
                'old_username' => $target_user['username'],
                'new_username' => '', //由后台改名 $new_username
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 0, //0未处理 8已处理 9拒绝处理
                'remark' => $remark
            );
            if (recycles::getItems($GLOBALS['SESSION']['user_id'], $target_user_id, 0)) {
                showMsg('您的上一次申请已受理，请勿重复提交');
            }
            if (!recycles::addItem($data)) {
                showMsg('申请回收失败');
            }

            showMsg("您申请回收用户{$target_user['username']}，请等待被审核", 1);
        }
        $target_user_id = $this->request->getGet('user_id', 'intval');
        if (!$target_user = users::getItem($target_user_id)) {
            showMsg('该用户不存在');
        }

        self::$view->setVar('target_user', $target_user);
        self::$view->render('user_recycle');
    }

    /*
    *团队即时报表
    *author asta 2016-06-03
    */
    public function teamNowReport(){
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');

        $flag = 0;
        $users = array();
        $usersNumber = 0;
        $userLevelName = '<a href="/?c=user&a=teamNowReport">本级</a>';
        if ($user = users::getItem($username,8,false,1,1)) {
            if ($user['user_id'] == $GLOBALS['SESSION']['user_id']) {
                $flag = 1;
            } else {
                $allParent = users::getAllParent($user['user_id'],false,1);
                if (isset($allParent[$GLOBALS['SESSION']['user_id']])) {
                    $flag = 1;
                }

                $allParent = array_reverse($allParent);
                $allParent[] = $user;

                $is_children = 0;
                foreach ($allParent as $parent){
                    if($parent['user_id'] == $GLOBALS['SESSION']['user_id']){
                        $is_children = 1;
                        continue;
                    }
                    if($is_children){
                        $userLevelName .= ' &gt; <a href="/?c=user&a=teamNowReport&username='.$parent['username'].'">'.$parent['username'].'</a>';
                    }
                }
            }
        }

        if ($flag == 1) {

            $users = users::getItems($user['user_id'], true, 0, array(), 8, -1, '', '', '', '', '', '', '', '', '', '', -1,-1,DEFAULT_PER_PAGE,[],'',-1,1);
           if(isset($users[$user['user_id']])) {
               unset($users[$user['user_id']]);
           }


            //这里包含了PT数据 需询问产品是否去除或者接入
            $teamTotalProjects = projects::getChildReport($user['user_id'] , date('Y-m-d 00:00:00') , date('Y-m-d H:i:s'));
            $teamTotalDeposits = deposits::getTeamDeposits($user['user_id'] , date('Y-m-d 00:00:00') , date('Y-m-d H:i:s'));
            $teamTotalWithdraws = withdraws::getTeamWithdraws($user['user_id'] , date('Y-m-d 00:00:00') , date('Y-m-d H:i:s'));
            $teamTotalGifts = userGifts::getTeamTotalGift($user['user_id'], date('Y-m-d 00:00:00'), date('Y-m-d H:i:s'));
            $pageData = $teamReport = $teamData = $teamTotal =$self = array();

            //获取自己数据
            $self['username'] = $username;
            $self['level'] = $user['level'];
            $pageData['page_deposit'] = $self['total_deposit'] = $teamTotalDeposits[$user['user_id']]['total_deposit'];
            $pageData['page_withdraw'] = $self['total_withdraw'] = $teamTotalWithdraws[$user['user_id']]['total_withdraw'];
            $pageData['page_amount'] = $self['total_amount'] = $teamTotalProjects[$user['user_id']]['total_amount'];
            $pageData['page_prize'] = $self['total_prize'] = $teamTotalProjects[$user['user_id']]['total_prize'];
            $pageData['page_rebate'] = $self['total_rebate'] = $teamTotalProjects[$user['user_id']]['total_rebate'];
            $pageData['page_contribute_rebate'] = $self['total_contribute_rebate'] = $teamTotalProjects[$user['user_id']]['total_contribute_rebate'];
            $pageData['page_promo_active'] = $self['total_promo_active'] = $teamTotalGifts[$user['user_id']]; //彩票活动暂时保留

            $pageData['page_win'] = $self['total_win'] = round(($self['total_prize'] + $self['total_rebate'] + $self['total_promo_active'] - $self['total_amount']), 4);


            //获取各项总计数据
            $teamTotal['team_total_deposit'] = $teamTotalDeposits['team_total_deposit'];
            $teamTotal['team_total_withdraw'] = $teamTotalWithdraws['team_total_withdraw'];
            $teamTotal['team_total_amount'] = $self['total_amount'];
            $teamTotal['team_total_prize'] = $self['total_prize'];
            $teamTotal['team_total_rebate'] = $self['total_rebate'];
            $teamTotal['team_total_contribute_rebate'] = $self['total_contribute_rebate'];
            $teamTotal['team_total_promo_active'] = array_sum($teamTotalGifts);

            //把自己数据总计数据从团队数据中剔除，剩下的都是团队成员的干净数据
            unset($teamTotalDeposits[$user['user_id']]);
            unset($teamTotalWithdraws[$user['user_id']]);
            unset($teamTotalProjects[$user['user_id']]);
            unset($teamTotalGifts[$user['user_id']]);

            unset($teamTotalDeposits['team_total_deposit']);
            unset($teamTotalWithdraws['team_total_withdraw']);

            foreach ($users as $user_id => $v) {
                $teamReport[$user_id]['username'] = $v['username'];
                $teamReport[$user_id]['level'] = $v['level'];
                $teamReport[$user_id]['total_deposit'] = $teamTotalDeposits[$user_id]['total_deposit'];
                $teamReport[$user_id]['total_withdraw'] = $teamTotalWithdraws[$user_id]['total_withdraw'];
                $teamReport[$user_id]['total_amount'] = $teamTotalProjects[$user_id]['total_amount'];
                $teamReport[$user_id]['total_prize'] = $teamTotalProjects[$user_id]['total_prize'];
                $teamReport[$user_id]['total_rebate'] = $teamTotalProjects[$user_id]['total_rebate'];
                $teamReport[$user_id]['total_contribute_rebate'] = $teamTotalProjects[$user_id]['total_contribute_rebate'];
                $teamReport[$user_id]['total_promo_active'] = $teamTotalGifts[$user_id];

                $teamReport[$user_id]['total_win'] = round(($teamReport[$user_id]['total_prize'] + $teamReport[$user_id]['total_rebate'] + $teamReport[$user_id]['total_promo_active'] - $teamReport[$user_id]['total_amount']), 4);

                //循环加以后是最终的汇总数据
                $teamTotal['team_total_amount'] += $teamTotalProjects[$user_id]['total_amount'];
                $teamTotal['team_total_prize'] += $teamTotalProjects[$user_id]['total_prize'];
                $teamTotal['team_total_rebate'] += $teamTotalProjects[$user_id]['total_rebate'];
                $teamTotal['team_total_contribute_rebate'] += $teamTotalProjects[$user_id]['total_contribute_rebate'];
            }
            $teamTotal['team_total_win'] = round(($teamTotal['team_total_rebate'] + $teamTotal['team_total_prize'] + $teamTotal['team_total_promo_active'] - $teamTotal['team_total_amount']),4);


            //排序字段名合法，则做排序
            if (in_array($sortKey, array('total_deposit', 'total_withdraw', 'total_amount','total_prize','total_win','total_promo_active','total_rebate'))) {
                $sort_arr = array(); //这个变量用于多维排序
                foreach ($teamReport as $key => $row) {
                    $sort_arr[$key] = $row[$sortKey];
                }
                if ($sortDirection > 0) {
                    array_multisort($sort_arr, SORT_ASC, $teamReport);
                } else {
                    array_multisort($sort_arr, SORT_DESC, $teamReport);
                }
            }
            //$curPageTeamReport = array_slice($teamReport, $startPos, DEFAULT_PER_PAGE);

            //本页总计
            foreach ($teamReport as $val) {
                $pageData['page_deposit'] += $val['total_deposit'];
                $pageData['page_withdraw'] += $val['total_withdraw'];
                $pageData['page_amount'] += $val['total_amount'];
                $pageData['page_prize'] += $val['total_prize'];
                $pageData['page_rebate'] += $val['total_rebate'];
                $pageData['page_contribute_rebate'] += $val['total_contribute_rebate'];//贡献佣金返点，暂时不显示前台
                $pageData['page_promo_active'] += $val['total_promo_active'];
                $pageData['page_win'] += $val['total_win'];
            }


            self::$view->setVar('sortKey', $sortKey);
            self::$view->setVar('sortDirection', $sortDirection);
            self::$view->setVar('self', $self);
            self::$view->setVar('pageData', $pageData);
            self::$view->setVar('teamTotal', $teamTotal);
            self::$view->setVar('curPageTeamReport', $teamReport);
            self::$view->setVar('userLevelName', $userLevelName);
            self::$view->setVar('pageList', getPageListMobile($usersNumber));
        }


        //预设查询值
        self::$view->setVar('username', $username);
        self::$view->setVar('flag', $flag);
        self::$view->render('user_teamnowreport');
    }

    /*
    *团队日度报表
    *author asta 2016-06-06
    */
    public function teamDayReport(){
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
        $sortKey = $this->request->getGet('sortKey', 'trim');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');

        $this->searchDate($startDate, $endDate, 2, 3, 1);

        $flag = 0;
        $users = array();

        if ($user = users::getItem($username)) {
            if ($user['user_id'] == $GLOBALS['SESSION']['user_id']) {
                $flag = 1;
            } else {
                $allParent = users::getAllParent($user['user_id']);
                if (isset($allParent[$GLOBALS['SESSION']['user_id']])) {
                    $flag = 1;
                }
            }
        }

        $startDay = date('Y-m-d' , strtotime($startDate));
        $endDay = date('Y-m-d' , strtotime($endDate));
        if ($flag == 1) {

            $i = 0;
            do{
                $date = date('Y-m-d',strtotime($startDay) + $i * 86400);
                $days[] = $date;
                $i++;
            }while (strtotime($date) < strtotime($endDay));


            $users = users::getItems($user['user_id'], true, 0, array(), 8, -1, '', '', '', '', '', '', '', '', '', '', -1);
            unset($users[$user['user_id']]);
            $usersNumber = users::getItemsNumber($user['user_id'], true, 0, 8);
            //这里包含了PT数据 需询问产品是否去除或者接入
            /*************** snow 2017/10/31 增加缓存时长 start***********************************/
            $cacheKey1 = __FUNCTION__ . '_getChildDayReport_' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            $cacheKey2 = __FUNCTION__ . '_getTeamDayDeposits_' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            $cacheKey3 = __FUNCTION__ . '_getTeamDayWithdraws_' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            /*************** snow 2017/10/31 增加缓存时长 start***********************************/
            //>>定义是否要写缓存  如果结束时间不等于当天 ,且没有缓存的情况下写入缓存.如果查询结束时间为当天 ,则不缓存
//            $write_cache_flag = $endDate === date('Y-m-d 23:59:59') ? false : true;
            $write_cache_flag =  false;      //>>要求暂时取消写入缓存
            //给每一次不同查询条件缓存一次，做到程序优化和数据库减压
            if(($teamTotalProjects = $GLOBALS['mc']->get(__CLASS__, $cacheKey1)) === false) {
                $teamTotalProjects = projects::getChildDayReport($user['user_id'] , $startDate, $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey1, $teamTotalProjects, CACHE_EXPIRE_LONG); //放入缓存
                }
            }

            if(($teamTotalDeposits = $GLOBALS['mc']->get(__CLASS__, $cacheKey2)) === false) {
                $teamTotalDeposits = deposits::getTeamDayDeposits($user['user_id'] , $startDate, $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey2, $teamTotalDeposits, CACHE_EXPIRE_LONG); //放入缓存
                }
            }

            if(($teamTotalWithdraws = $GLOBALS['mc']->get(__CLASS__, $cacheKey3)) === false) {
                $teamTotalWithdraws = withdraws::getTeamDayWithdraws($user['user_id'] , $startDate, $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey3, $teamTotalWithdraws, CACHE_EXPIRE_LONG); //放入缓存
                }
            }
            /*************** snow 2017/10/31 增加缓存时长 end  ***********************************/

            $teamReport = $teamTotal = $self = $selfData = $pageData = array();

            //获取自己数据
            $selfData['username'] = $username;
            $selfData['level'] = $user['level'];

            $teamTotal['team_total_deposit'] = $teamTotalDeposits['team_total_day_deposit'];
            $teamTotal['team_total_withdraw'] = $teamTotalWithdraws['team_total_day_withdraw'];
            $teamTotal['team_total_amount'] = 0;
            $teamTotal['team_total_prize'] = 0;
            $teamTotal['team_total_rebate'] = 0;
            $teamTotal['team_total_contribute_rebate'] = 0;
            $teamTotal['team_total_promo_active'] = 0;//数据暂时缺省为0

            foreach ($days as $day) {
                $self[$day]['total_deposit'] = $teamTotalDeposits[$day][$user['user_id']]['day_deposit'];
                $self[$day]['total_withdraw'] = $teamTotalWithdraws[$day][$user['user_id']]['day_withdraw'];
                $self[$day]['total_amount'] = $teamTotalProjects[$day][$user['user_id']]['total_amount'];
                $self[$day]['total_prize'] = $teamTotalProjects[$day][$user['user_id']]['total_prize'];
                $self[$day]['total_rebate'] = $teamTotalProjects[$day][$user['user_id']]['total_rebate'];
                $self[$day]['total_contribute_rebate'] = $teamTotalProjects[$day][$user['user_id']]['total_contribute_rebate'];
                $self[$day]['total_promo_active'] = 0;//彩票活动暂时保留

                $self[$day]['total_win'] = round(($self[$day]['total_prize'] + $self[$day]['total_rebate'] - $self[$day]['total_amount']), 4);


                //从总计中除去自己的数据就是纯团队的
                // $teamTotal['team_total_deposit'] = $teamTotal['team_total_deposit'] - $teamTotalDeposits[$day][$user['user_id']]['day_deposit'];
                // $teamTotal['team_total_withdraw'] = $teamTotal['team_total_withdraw'] - $teamTotalWithdraws[$day][$user['user_id']]['day_withdraw'];
                //获取各项总计数据

                // 这里注意掉，先不把自己数据计算进去，需询问产品是否在前台显示后决定
                $teamTotal['team_total_amount'] += $self[$day]['total_amount'];
                $teamTotal['team_total_prize'] += $self[$day]['total_prize'];
                $teamTotal['team_total_rebate'] += $self[$day]['total_rebate'];
                $teamTotal['team_total_contribute_rebate'] += $self[$day]['total_contribute_rebate'];

                //把自己数据and总计数据从团队数据中剔除，剩下的都是团队成员的干净数据
                unset($teamTotalDeposits[$day][$user['user_id']]);
                unset($teamTotalWithdraws[$day][$user['user_id']]);
                unset($teamTotalProjects[$day][$user['user_id']]);
                unset($teamTotalDeposits['team_total_day_deposit']);
                unset($teamTotalWithdraws['team_total_day_withdraw']);

                foreach ($users as $user_id => $v) {
                    //这里注释的是直属代理的团队数据，现在要求不展示,归属到self下
                    // $teamReport[$day][$user_id]['username'] = $v['username'];
                    // $teamReport[$day][$user_id]['level'] = $v['level'];
                    // $teamReport[$day][$user_id]['total_deposit'] = $teamTotalDeposits[$day][$user_id]['day_deposit'];
                    // $teamReport[$day][$user_id]['total_withdraw'] = $teamTotalWithdraws[$day][$user_id]['day_withdraw'];
                    // $teamReport[$day][$user_id]['total_amount'] = $teamTotalProjects[$day][$user_id]['total_amount'];
                    // $teamReport[$day][$user_id]['total_prize'] = $teamTotalProjects[$day][$user_id]['total_prize'];
                    // $teamReport[$day][$user_id]['total_rebate'] = $teamTotalProjects[ $day][$user_id]['total_rebate'];
                    // $teamReport[$day][$user_id]['total_contribute_rebate'] = $teamTotalProjects[$day][$user_id]['total_contribute_rebate'];
                    // $teamReport[$day][$user_id]['total_promo_active'] = 0;
                    // $teamReport[$day][$user_id]['total_win'] = round(($teamReport[$day][$user_id]['total_prize'] + $teamReport[$day][$user_id]['total_rebate'] - $teamReport[$day][$user_id]['total_amount']), 4);

                    $self[$day]['total_deposit'] += $teamTotalDeposits[$day][$user_id]['day_deposit'];
                    $self[$day]['total_withdraw'] += $teamTotalWithdraws[$day][$user_id]['day_withdraw'];
                    $self[$day]['total_amount'] += $teamTotalProjects[$day][$user_id]['total_amount'];
                    $self[$day]['total_prize'] += $teamTotalProjects[$day][$user_id]['total_prize'];
                    $self[$day]['total_rebate'] += $teamTotalProjects[ $day][$user_id]['total_rebate'];
                    $self[$day]['total_contribute_rebate'] += $teamTotalProjects[$day][$user_id]['total_contribute_rebate'];
                    $self[$day]['total_promo_active'] += 0;
                    //这里需要注意 以后total_promo_active total_awards有值得时候 需要加入盈亏中计算
                    $self[$day]['total_win'] += round(($teamTotalProjects[$day][$user_id]['total_prize'] + $teamTotalProjects[$day][$user_id]['total_rebate'] - $teamTotalProjects[$day][$user_id]['total_amount']), 4);



                    //循环加以后是最终的汇总数据
                    $teamTotal['team_total_amount'] += $teamTotalProjects[$day][$user_id]['total_amount'];
                    $teamTotal['team_total_prize'] += $teamTotalProjects[$day][$user_id]['total_prize'];
                    $teamTotal['team_total_rebate'] += $teamTotalProjects[$day][$user_id]['total_rebate'];
                    $teamTotal['team_total_contribute_rebate'] += $teamTotalProjects[$day][$user_id]['total_contribute_rebate'];
                }
            }
            $teamTotal['team_total_win'] = round(($teamTotal['team_total_rebate'] + $teamTotal['team_total_prize'] + $teamTotal['team_total_promo_active'] - $teamTotal['team_total_amount']),4);

            $resetReport =array();
            // foreach($teamReport as $d=>$v){//为了各种排序把数据打散
            //     foreach($v as $user_id => $udata){
            //         $udata['user_id'] = $user_id;
            //         $udata['day'] = $d;
            //         $resetReport[] = $udata;
            //     }
            // }

            foreach($self as $d => $v){//为了各种排序把自己的数据打散
                $v['day'] = $d;
                $resetReport[] = $v;
            }


            //排序字段名合法，则做排序
            if (in_array($sortKey, array('day', 'total_deposit', 'total_withdraw', 'total_amount','total_prize','total_win','total_promo_active','total_rebate'))) {
                $sort_arr = array();

                foreach ($resetReport as $key => $row) {
                    $sort_arr[$key] = $row[$sortKey];
                }
                if ($sortDirection > 0) {
                    array_multisort($sort_arr, SORT_ASC, $resetReport);
                } else {
                    array_multisort($sort_arr, SORT_DESC, $resetReport);
                }
            }

            /***************** snow  获取正确的分页开始****************************/
            $curPage  = $this->request->getGet('curPage', 'intval', 1);
            $startPos = getStartOffset($curPage, count($resetReport));
            /***************** snow  获取正确的分页开始****************************/
            $curPageTeamReport = array_slice($resetReport, $startPos, DEFAULT_PER_PAGE);

            $pageData['page_deposit'] = $pageData['page_withdraw'] = $pageData['page_amount'] = $pageData['page_prize'] = $pageData['page_rebate'] = $pageData['page_contribute_rebate'] = $pageData['page_promo_active'] = $pageData['page_win'] = 0;

            //本页总计
            foreach ($curPageTeamReport as $val) {
                $pageData['page_deposit'] += $val['total_deposit'];
                $pageData['page_withdraw'] += $val['total_withdraw'];
                $pageData['page_amount'] += $val['total_amount'];
                $pageData['page_prize'] += $val['total_prize'];
                $pageData['page_rebate'] += $val['total_rebate'];
                $pageData['page_contribute_rebate'] += $val['total_contribute_rebate'];//下级贡献返点，不过暂时不显示在前台
                $pageData['page_promo_active'] += $val['total_promo_active'];
                $pageData['page_win'] += $val['total_win'];
            }


            self::$view->setVar('sortKey', $sortKey);
            self::$view->setVar('sortDirection', $sortDirection);
            self::$view->setVar('selfData', $selfData);
            self::$view->setVar('teamTotal', $teamTotal);
            self::$view->setVar('pageData', $pageData);
            self::$view->setVar('curPageTeamReport', $curPageTeamReport);
            self::$view->setVar('pageList', getPageListMobile(count($days)));
        }


        //预设查询值
        self::$view->setVar('username', $username);
        self::$view->setVar('flag', $flag);
        self::$view->render('user_teamdayreport');
    }




    /*
    *团队盈亏报表
    *author asta 2016-06-11
    */
    public function teamWinReport(){
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        $this->searchDate($startDate, $endDate, 2, 3);

        $flag = 0;
        $users = array();
        $usersNumber = 0;
        $userLevelName = '<a href="/?c=user&a=teamWinReport&startDate='.explode(' ', $startDate)[0].'&endDate='.explode(' ', $endDate)[0].'">本级</a>';
        if ($user = users::getItem($username,8,false,1,1)) {
            if ($user['user_id'] == $GLOBALS['SESSION']['user_id']) {
                $flag = 1;
            } else {
                $allParent = users::getAllParent($user['user_id']);
                if (isset($allParent[$GLOBALS['SESSION']['user_id']])) {
                    $flag = 1;
                }

                $allParent = array_reverse($allParent);
                $allParent[] = $user;

                $is_children = 0;
                foreach ($allParent as $parent){
                    if($parent['user_id'] == $GLOBALS['SESSION']['user_id']){
                        $is_children = 1;
                        continue;
                    }
                    if($is_children){
                        $userLevelName .= ' &gt; <a href="/?c=user&a=teamWinReport&username='.$parent['username'].'&startDate='.explode(' ', $startDate)[0].'&endDate='.explode(' ', $endDate)[0].'">'.$parent['username'].'</a>';
                    }
                }
            }
        }
        
        if ($flag == 1) {
            $users = users::getItems($user['user_id'], true, 0, array(), 8, -1, '', '', '', '', '', '', '', '', '', '', -1,-1,DEFAULT_PER_PAGE,[],'',-1,1);
            unset($users[$user['user_id']]);
            $usersNumber = users::getItemsNumber($user['user_id'], true, 0, 8);

            //这里包含了PT数据 需询问产品是否去除或者接入
            /*************** snow 2017/10/31 增加缓存时长  start***********************************/
            $cacheKey1 = __FUNCTION__ . 'getChildReport' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            $cacheKey2 = __FUNCTION__ . 'getTeamDeposits' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            $cacheKey3 = __FUNCTION__ . 'getTeamWithdraws' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            $cacheKey4 = __FUNCTION__ . 'getChildCommission' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            $cacheKey5 = __FUNCTION__ . 'getTeamTotalGift' . $user['user_id'] . '_' . $startDate . '_' . $endDate;
            //>>定义是否要写缓存  如果结束时间不等于当天 ,且没有缓存的情况下写入缓存.如果查询结束时间为当天 ,则不缓存
            $write_cache_flag = $endDate === date('Y-m-d 23:59:59') ? false : true;
            /*************** snow 2017/10/31 增加缓存时长 start***********************************/
            //给每一次不同查询条件缓存一次，做到程序优化和数据库减压
            if(($teamTotalProjects = $GLOBALS['mc']->get(__CLASS__, $cacheKey1)) === false) {

                $teamTotalProjects = projects::getChildReport($user['user_id'] ,  $startDate , $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey1, $teamTotalProjects, CACHE_EXPIRE_LONG); //放入缓存
                }
            }

            if(($teamTotalDeposits = $GLOBALS['mc']->get(__CLASS__, $cacheKey2)) === false) {
                $teamTotalDeposits = deposits::getTeamDeposits($user['user_id'] ,  $startDate , $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey2, $teamTotalDeposits, CACHE_EXPIRE_LONG); //放入缓存
                }
            }

            if(($teamTotalWithdraws = $GLOBALS['mc']->get(__CLASS__, $cacheKey3)) === false) {
                $teamTotalWithdraws = withdraws::getTeamWithdraws($user['user_id'] ,  $startDate , $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey3, $teamTotalWithdraws, CACHE_EXPIRE_LONG); //放入缓存
                }
            }

            if(($teamTotalCommission = $GLOBALS['mc']->get(__CLASS__, $cacheKey4)) === false) {
                $teamTotalCommission = orders::getChildCommission($user,  $startDate, $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey4, $teamTotalCommission, CACHE_EXPIRE_LONG); //放入缓存
                }
             }

            if(($teamTotalGifts = $GLOBALS['mc']->get(__CLASS__, $cacheKey5)) === false) {
                $teamTotalGifts = userGifts::getTeamTotalGift($user['user_id'], $startDate, $endDate);
                if ($write_cache_flag)
                {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey5, $teamTotalGifts, CACHE_EXPIRE_LONG); //放入缓存
                }
            }
            /*************** snow 2017/10/31 增加缓存时长 start***********************************/
            $pageData = $teamReport = $teamData = $teamTotal =$self = array();

            //获取自己数据
            $self['username'] = $username;
            $self['level'] = $user['level'];
            $pageData['page_deposit'] = $self['total_deposit'] = $teamTotalDeposits[$user['user_id']]['total_deposit'];
            $pageData['page_withdraw'] = $self['total_withdraw'] = $teamTotalWithdraws[$user['user_id']]['total_withdraw'];
            $pageData['page_amount'] = $self['total_amount'] = $teamTotalProjects[$user['user_id']]['total_amount'];
            $pageData['page_prize'] = $self['total_prize'] = $teamTotalProjects[$user['user_id']]['total_prize'];
            $pageData['page_rebate'] = $self['total_rebate'] = $teamTotalProjects[$user['user_id']]['total_rebate'];
            $pageData['page_contribute_rebate'] = $self['total_contribute_rebate'] = $teamTotalProjects[$user['user_id']]['total_contribute_rebate'];
//            $pageData['page_promo_active'] = $self['total_promo_active'] = $teamTotalGifts[$user['user_id']];//彩票活动暂时保留
            $pageData['page_commission'] = $self['total_commission'] = $teamTotalCommission[$user['user_id']]['total_commission'];//返佣
//            $pageData['page_win'] = $self['total_win'] = round(($self['total_promo_active'] + $self['total_prize'] + $self['total_rebate'] + $self['total_commission'] - $self['total_amount']), 4);
            $pageData['page_win'] = $self['total_win'] = round(($self['total_prize'] + $self['total_rebate'] + $self['total_commission'] - $self['total_amount']), 4);

            //获取各项总计数据
            $teamTotal['team_total_deposit'] = $teamTotalDeposits['team_total_deposit'];
            $teamTotal['team_total_withdraw'] = $teamTotalWithdraws['team_total_withdraw'];
            $teamTotal['team_total_amount'] = $self['total_amount'];
            $teamTotal['team_total_prize'] = $self['total_prize'];
            $teamTotal['team_total_rebate'] = $self['total_rebate'];
            $teamTotal['team_total_contribute_rebate'] = $self['total_contribute_rebate'];
//            $teamTotal['team_total_promo_active'] = array_sum($teamTotalGifts);
            $teamTotal['team_total_commission'] = $teamTotalCommission['team_total_commission'];


            //把自己数据总计数据从团队数据中剔除，剩下的都是团队成员的干净数据
            unset($teamTotalDeposits[$user['user_id']]);
            unset($teamTotalWithdraws[$user['user_id']]);
            unset($teamTotalProjects[$user['user_id']]);
            unset($teamTotalCommission[$user['user_id']]);
            unset($teamTotalGifts[$user['user_id']]);

            unset($teamTotalDeposits['team_total_deposit']);
            unset($teamTotalWithdraws['team_total_withdraw']);

            foreach ($users as $user_id => $v) {
                if(!isset($teamReport[$user_id]))
                {
                    continue;
                }
                $teamReport[$user_id]['username'] = $v['username'];
                $teamReport[$user_id]['level'] = $v['level'];

                $teamReport[$user_id]['total_deposit'] = isset($teamTotalDeposits[$user_id]['total_deposit'])?$teamTotalDeposits[$user_id]['total_deposit']:0;
                $teamReport[$user_id]['total_withdraw'] = isset($teamTotalWithdraws[$user_id]['total_withdraw'])?$teamTotalWithdraws[$user_id]['total_withdraw']:0;
                $teamReport[$user_id]['total_amount'] = isset($teamTotalProjects[$user_id]['total_amount'])?$teamTotalProjects[$user_id]['total_amount']:0;
                $teamReport[$user_id]['total_prize'] = isset($teamTotalProjects[$user_id]['total_prize'])?$teamTotalProjects[$user_id]['total_prize']:0;
                $teamReport[$user_id]['total_rebate'] = isset($teamTotalProjects[$user_id]['total_rebate'])?$teamTotalProjects[$user_id]['total_rebate']:0;
                $teamReport[$user_id]['total_contribute_rebate'] = isset($teamTotalProjects[$user_id]['total_contribute_rebate'])?$teamTotalProjects[$user_id]['total_contribute_rebate']:0;

                $teamReport[$user_id]['total_commission'] =  isset($teamTotalCommission[$user_id]['total_commission'])?$teamTotalCommission[$user_id]['total_commission']:0;
//                $teamReport[$user_id]['total_promo_active'] = $teamTotalGifts[$user_id];
//                $teamReport[$user_id]['total_win'] = round(($teamReport[$user_id]['total_prize'] + $teamReport[$user_id]['total_rebate'] + $teamReport[$user_id]['total_commission'] + $teamReport[$user_id]['total_promo_active'] - $teamReport[$user_id]['total_amount']), 4);
                $teamReport[$user_id]['total_win'] = round(($teamReport[$user_id]['total_prize'] + $teamReport[$user_id]['total_rebate'] + $teamReport[$user_id]['total_commission'] - $teamReport[$user_id]['total_amount']), 4);

                //循环加以后是最终的汇总数据
                $teamTotal['team_total_amount'] += $teamTotalProjects[$user_id]['total_amount'];
                $teamTotal['team_total_prize'] += $teamTotalProjects[$user_id]['total_prize'];
                $teamTotal['team_total_rebate'] += $teamTotalProjects[$user_id]['total_rebate'];
                $teamTotal['team_total_contribute_rebate'] += $teamTotalProjects[$user_id]['total_contribute_rebate'];
            }

//            $teamTotal['team_total_win'] = round(($teamTotal['team_total_rebate'] + $teamTotal['team_total_prize'] +  $teamTotal['team_total_promo_active'] + $teamTotal['team_total_commission'] - $teamTotal['team_total_amount']),4);
            $teamTotal['team_total_win'] = round(($teamTotal['team_total_rebate'] + $teamTotal['team_total_prize'] + $teamTotal['team_total_commission'] - $teamTotal['team_total_amount']),4);

            //排序字段名合法，则做排序
//            if (in_array($sortKey, array('total_deposit', 'total_withdraw', 'total_amount','total_prize','total_rebate','total_commission','total_promo_active','total_win'))) {
            if (in_array($sortKey, array('total_deposit', 'total_withdraw', 'total_amount','total_prize','total_rebate','total_commission','total_win'))) {
                $sort_arr = array(); //这个变量用于多维排序
                foreach ($teamReport as $key => $row) {
                    $sort_arr[$key] = $row[$sortKey];
                }
                if ($sortDirection > 0) {
                    array_multisort($sort_arr, SORT_ASC, $teamReport);
                } else {
                    array_multisort($sort_arr, SORT_DESC, $teamReport);
                }
            }

            /***************** snow  获取正确的分页开始****************************/
            $curPage  = $this->request->getGet('curPage', 'intval', 1);
            $startPos = getStartOffset($curPage, count($teamReport));
            /***************** snow  获取正确的分页开始****************************/
            $curPageTeamReport = array_slice($teamReport, $startPos, DEFAULT_PER_PAGE);

            //本页总计
            foreach ($curPageTeamReport as $val) {
                $pageData['page_deposit'] += $val['total_deposit'];
                $pageData['page_withdraw'] += $val['total_withdraw'];
                $pageData['page_amount'] += $val['total_amount'];
                $pageData['page_prize'] += $val['total_prize'];
                $pageData['page_rebate'] += $val['total_rebate'];
                $pageData['page_contribute_rebate'] += $val['total_contribute_rebate'];//贡献佣金返点，暂时不显示前台
//                $pageData['page_promo_active'] += $val['total_promo_active'];
                $pageData['page_win'] += $val['total_win'];
                $pageData['page_commission'] += $val['total_commission'];
            }


            self::$view->setVar('sortKey', $sortKey);
            self::$view->setVar('sortDirection', $sortDirection);
            self::$view->setVar('userLevelName', $userLevelName);
            self::$view->setVar('self', $self);
            self::$view->setVar('pageData', $pageData);
            self::$view->setVar('teamTotal', $teamTotal);
            self::$view->setVar('curPageTeamReport', $curPageTeamReport);
            self::$view->setVar('pageList', getPageListMobile($usersNumber));
        }


        //预设查询值
        self::$view->setVar('username', $username);
        self::$view->setVar('flag', $flag);
        self::$view->render('user_teamwinreport');
    }


    /**
     * 团队活动报表
     */
    public function teamGiftReport()
    {
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        if(($username = $this->request->getGet('username', 'trim')))
        {
            if(!$user = users::getItem($username)){
                showMsg("非法请求，该用户不存在或已被冻结");
            }
            if($username != $GLOBALS['SESSION']['username'] && !in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))){
                showMsg("非法请求，此用户不是你的下级");
            }
            $user_id = $user['user_id'];
            $username = $user['username'];
            $include_childs = 0;
        }
        else{
            $user_id = $GLOBALS['SESSION']['user_id'];
            $username = '';
            $include_childs = 1;
        }

        $this->searchDate($startDate, $endDate, 2, 3);

        $giftTypes = [103=>'first_deposit', 501=>'sub_bet', 502=>'sub_loss', 601=>'register', 602=>'sign', 603=>'day_win', 604=>'day_loss'];

        $datas = [];
        if($totalGifts = userGifts::getTotalGift($startDate, $endDate, $user_id, 4, '', $include_childs))
        {
            foreach ($totalGifts as $userGift)
            {
                if(!in_array($userGift['type'], array_keys($giftTypes))) continue;
                $datas[ $userGift['user_id'] ]['username'] = $userGift['username'];
                $datas[ $userGift['user_id'] ][ $giftTypes[ $userGift['type'] ] ] = $userGift['gift'];
            }

            //用户无此类型记录则为0
            foreach ($datas as $user_id=>$data){
                if($diffTypes = array_diff($giftTypes, array_keys($data))){
                    foreach ($diffTypes as $typeName){
                        $datas[ $user_id ][$typeName] = 0;
                    }
                }
            }
        }

        //本页总计和总计初始化
        $pageTotal = $allPageTotal = [];
        foreach ($giftTypes as $typeName){
            $pageTotal[$typeName] = $allPageTotal[$typeName] = 0;
        }

        $count = count($datas);
        if($datas)
        {
            foreach ($giftTypes as $typeName) $allPageTotal[$typeName] = array_sum(array_column($datas, $typeName));

            /***************** snow  获取正确的分页开始****************************/
            $curPage  = $this->request->getGet('curPage', 'intval', 1);
            $startPos = getStartOffset($curPage, count($datas));
            /***************** snow  获取正确的分页开始****************************/
            if($datas = array_slice($datas, $startPos, DEFAULT_PER_PAGE)){
                foreach ($giftTypes as $typeName) $pageTotal[$typeName] = array_sum(array_column($datas, $typeName));
            }
        }

        self::$view->setVar('pageList', getPageListMobile($count));
        self::$view->setVar('teamGifts', $datas);
        self::$view->setVar('allPageTotal', $allPageTotal);
        self::$view->setVar('pageTotal', $pageTotal);
        self::$view->setVar('username', $username);
        self::$view->render('user_teamgiftreport');
    }

    /**
     * 下发配额
     */
    public function sendOutQuota()
    {
        $subUserId = $this->request->getGet('user_id', 'intval', $this->request->getPost('user_id', 'intval'));
        if(!$subUserId || (!$subUser = users::getItem($subUserId))){
            die(json_encode(array('errno' => 1, 'errstr' => "用户不存在")));
        }
        $user = users::getItem($GLOBALS['SESSION']['user_id']);
        $subPrizeMode = userRebates::userPrizeMode($subUserId);

        if(in_array($subPrizeMode, array_keys(userRebates::$basicRebates))){
            die(json_encode(array('errno' => 2, 'errstr' => "不能调整该用户配额")));
        }

        $aQuotas = users::countOfQuota($user, false, true);

/*        if($aQuotas = users::countOfQuota($user, false, true))
        {
            foreach ($aQuotas as $key => $aQuota){
                if($aQuota['prize_mode'] <= $subPrizeMode && $aQuota['available'] > $aQuota['used']) unset($aQuotas[$key]);
            }
        }*/

/*        if(empty($aQuotas) || min(array_column($aQuotas, 'prize_mode')) > $subPrizeMode){
            die(json_encode(array('errno' => 2, 'errstr' => "不能调整该用户配额")));
        }*/

        if($this->request->getGet('check', 'intval')) {
            die(json_encode(array('errno' => 0, 'errstr' => "")));
        }

        if ($aPrizeMode = $this->request->getPost('prize_mode', 'array'))
        {
            $aCount = array_combine($aPrizeMode, $this->request->getPost('count', 'array'));

            if(!users::sendOutQuota($user, $subUser, $aCount)) die(json_encode(array('errno' => 3, 'errstr' => "操作失败，请检查填写数据是否正确")));
            else die(json_encode(array('errno' => 0, 'errstr' => "操作成功")));
        }

        $aSubQuotas = users::countOfQuota($subUser, true, true);

        self::$view->setVar('user', $subUser);
        self::$view->setVar('aQuotas', $aQuotas);
        self::$view->setVar('aSubQuotas', $aSubQuotas);
        self::$view->setVar('subPrizeMode', $subPrizeMode);
        self::$view->render('user_sendoutquota');
    }


    public function teamReportCentral(){

        self::$view->render('user_teamreportcentral');
    }


}

?>