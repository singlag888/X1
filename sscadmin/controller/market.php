<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：用户管理
 *
 * 注：“用户”指代理和会员的统称
 */
class marketController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'userRank' => '用户排行',
        'issueMethodRank' => '奖期排行',
        'reportDay' => '日度报表',
        'userReportDay' => '用户日投注',
        'userGifts' => '礼品券',
        'addUserGifts' => '增加礼品券',
        'sendUserGifts' => '发放礼品券',
        'deleteUserGifts' => '删除礼品券',
        'amountChart' => '总账图表',
        'saleChart' => '销量图表',
        'userChart' => '用户图表',
        'roulettePlan' => '转盘计划',
        'roulettePlanList' => '转盘列表',
        'rouletteUnrealList' => '非真实中奖记录',
        'rouletteUnrealPlan' => '创建非真实中奖纪录',
        'rouletteAwardList' => '开奖详细列表',
        'rouletteAwardReport' => '开奖统计列表',
        'cardList' => '卡牌抽奖列表',
        'cardExchangeList' => '卡牌变现列表',
        'cardPrizeStaistics' => '卡牌派奖统计',
        'promoList' => '活动列表区',
        'promoStatics' => '活动统计区',
        'promoControl' => '活动控制区',
        'activityList' => '优惠活动列表',
        'pushActivity' => '优惠活动推送',
        'addActivity' => '新增优惠活',
        'editActivity' => '编辑优惠活动',
        'deleteActivity' => '删除优惠活动',
        'sendLolSkin' => '英雄联盟发皮肤',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function userRank()
    {
        $rankType = $this->request->getGet('rankType', 'intval', 1);
        $limit = $this->request->getGet('limit', 'intval', 100);
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));

        switch ($rankType) {
            case 1:
                $users = projects::getUsersRankPackages($start_time, $end_time, 1, $limit);
                break;
            case 2:
                $users = projects::getUsersRankPackages($start_time, $end_time, 0, $limit);
                break;
            case 3:
                $users = projects::getUsersRankWin($start_time, $end_time, 1, $limit);
                break;
            case 4:
                $users = projects::getUsersRankWin($start_time, $end_time, 0, $limit);
                break;
            default:
                throw new exception2('排行类型参数无效');
                break;
        }

        //预设查询值
        self::$view->setVar('rankType', $rankType);
        self::$view->setVar('limit', $limit);
        self::$view->setVar('users', $users);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->render('market_userrank');
    }

    public function issueMethodRank()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval', -1);
        $method_id = $this->request->getGet('method_id', 'intval', -1);
        $rankType = $this->request->getGet('rankType', 'intval', 1);
        $limit = $this->request->getGet('limit', 'intval', 100);
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        $types = $this->request->getGet('types', 'trim', 'sale');
        $group = $this->request->getGet('group', 'trim', 'issue');
        $submit = $this->request->getGet('submit', 'trim', '');
        if ($submit == '查询') {
            $issues = projects::getIssueMethodRank($lottery_id, $method_id, $types, $start_time, $end_time, $group, $rankType, $limit);
            if ($types == 'profit' && isset($issues)) {
                foreach ($issues as $k => $v) {
                    $issues[$k]['total_profit'] = $v['total_amount'] - $v['total_prize'];
                    $issues[$k]['key'] = (int) $issues[$k]['total_profit'];
                }
                $issues = array_spec_key($issues, 'key');
                $rankType == 1 ? krsort($issues) : ksort($issues);
            }
            if (isset($issues)) {
                self::$view->setVar('issues', $issues);
            }
        }

        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id','name']);
        //得某彩种所有玩法
        $methods = methods::getItems(0, 0, -1, 2);

        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0))->setVar('json_methods', json_encode($methods));
        self::$view->setVar('json_lotterys', json_encode($lotterys));

        //预设查询值
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('method_id', $method_id);
        self::$view->setVar('types', $types);
        self::$view->setVar('group', $group);
        self::$view->setVar('rankType', $rankType);
        self::$view->setVar('limit', $limit);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->render('market_issuemethodrank');
    }

    //日度报表 reportDay
    public function reportDay()
    {
        if ($this->request->getGet('sa', 'trim') == 'firstDepositList') {
            $first_deposit_user_id = $this->request->getGet('first_deposit_user_id', 'trim');
            $deposits = deposits::getItemsById(explode(",", $first_deposit_user_id));
            $userIds = array_keys(array_spec_key($deposits, 'user_id'));
            $users = users::getItemsById($userIds);
            foreach ($deposits as $k => $v) {
                if (isset($users[$v['user_id']])) {
                    $deposits[$k]['username'] = $users[$v['user_id']]['username'];
                    $deposits[$k]['reg_time'] = $users[$v['user_id']]['reg_time'];
                    if ($users[$v['user_id']]['real_name']) {
                        $deposits[$k]['real_name'] = $users[$v['user_id']]['real_name'];
                    }
                }
            }
            $sameNameNums = ['total_buy_sum' => 0,'total_prize_sum' => 0,'total_win_sum' => 0,];
            $realNames = array_keys(array_spec_key($deposits, 'real_name'));
            if ($realNames) {
                $sameNameNums = users::getRealNameNum($realNames);
            }
            foreach ($deposits as $k => $v) {
                if (isset($v['real_name']) && isset($sameNameNums[$v['real_name']])) {
                    $deposits[$k]['real_name_num'] = $sameNameNums[$v['real_name']]['num'] - 1;
                }
                else {
                    $deposits[$k]['real_name_num'] = 0;
                }
            }
            self::$view->setVar('deposits', $deposits);
            self::$view->render('market_firstdeposit_list');
            die;
        }

        $perPage = DEFAULT_PER_PAGE;
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
        $result = $this->_getTotalWin($startDate, $endDate);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $reportDayNumber = reportDay::getItemsNumber($startDate, $endDate);
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $reportDayNumber, $perPage);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $reportDays = reportDay::getItems($startDate, $endDate, $startPos, $perPage);
        $totalInfo = [
            'total_buy_sum' => 0,
            'total_prize_sum' => 0,
            'total_win_sum' => 0
        ];
        foreach ($reportDays as $key => $reportDay) {
            if (isset($result[$reportDay['date']]))
            {
                //>>如果相应的日期数据存在
                //>>合并两个数据
                $reportDays[$key] = array_merge($reportDay,$result[$reportDay['date']]);
            }
            $totalInfo['total_buy_sum'] += $reportDays[$key]['total_buy'];
            $totalInfo['total_prize_sum'] += $reportDays[$key]['total_prize'];
            $totalInfo['total_win_sum'] += $reportDays[$key]['total_win'];
            $reportDay['user_avg_buy'] = (float) $reportDay['play_user_num'] != 0 ? (float) $reportDay['buy_amount'] / (float) $reportDay['play_user_num'] : 0;
            foreach ($reportDay as $k => $v) {

                if (is_numeric($v)) {
                    if (isset($totalInfo[$k])) {
                        $totalInfo[$k]+=$v;
                    }
                    else {
                        $totalInfo[$k] = $v;
                    }
                }
            }
        }

        //预设查询框
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('reportDays', $reportDays);
        self::$view->setVar('totalInfo', $totalInfo);
        self::$view->setVar('pageList', getPageList($reportDayNumber, $perPage));
        self::$view->render('market_reportday');
    }

    /**
     * 从userSales 销量表中获取游戏盈亏
     * @param $start
     * @param $end
     */
    private function _getTotalWin($start, $end)
    {
        $sql =<<<SQL
SELECT belong_date,SUM(buy_amount) AS total_buy,SUM(prize_amount) AS total_prize,  (SUM(buy_amount) - SUM(prize_amount)) AS total_win  FROM user_sales	
WHERE belong_date >= '{$start}'  AND belong_date <= '{$end}'
GROUP BY belong_date
SQL;
        return $GLOBALS['db']->getAll($sql,[], 'belong_date');

    }
    /**
     * 用户每日的投注分析
     */
    public function userReportDay()
    {
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $min = $this->request->getGet('min', 'intval', 100);
        $max = $this->request->getGet('max', 'intval', 100000);
        $countDays = 1;
        $sDay = strtotime($startDate);
        $eDay = strtotime($endDate);
        $sDate = getdate($sDay);
        $eDate = getdate($eDay);
        $countDays += round(abs(mktime(12, 0, 0, $sDate['mon'], $sDate['mday'], $sDate['year']) - mktime(12, 0, 0, $eDate['mon'], $eDate['mday'], $eDate['year'])) / 86400);
        $reportDays = projects::getUsersDayPackages($startDate . ' 00:00:00', $endDate . ' 23:59:59');
        $totalInfo = array();
        $totalDes = array();
        $totalDay = array();
        foreach ($reportDays as $reportDay) {

            if (!isset($totalInfo[$reportDay['user_id']])) {
                $totalInfo[$reportDay['user_id']] = 0;
            }

            if ($reportDay['total_amount'] >= $min && $reportDay['total_amount'] <= $max) {
                $totalInfo[$reportDay['user_id']]+=1;
            }

            if (isset($totalDes[$reportDay['user_id']])) {
                $totalDes[$reportDay['user_id']][$reportDay['create_date']] = $reportDay['total_amount'];
            }
            else {
                $totalDes[$reportDay['user_id']] = array();
                $totalDes[$reportDay['user_id']]['username'] = $reportDay['username'];
                $totalDes[$reportDay['user_id']][$reportDay['create_date']] = $reportDay['total_amount'];
            }
        }
        krsort($totalInfo);
        $totalDay[] = date("Y-m-d", $sDay);
        $plusDay = 1;
        while (!in_array(date("Y-m-d", $eDay), $totalDay)) {
            $totalDay[] = date("Y-m-d", $sDay + ($plusDay * 86400));
            $plusDay++;
        }
        //预设查询框
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('min', $min);
        self::$view->setVar('max', $max);
        self::$view->setVar('countDays', $countDays);
        self::$view->setVar('reportDays', $reportDays);
        self::$view->setVar('totalInfo', $totalInfo);
        self::$view->setVar('totalDes', $totalDes);
        self::$view->setVar('totalDay', $totalDay);
        self::$view->render('market_userreportday');
    }

    /**
     * 2014国庆乐翻天
     */
    public function guoqingActivity()
    {
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('0 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d", strtotime('0 days')));
        $activitys = activityGuoqing::getItems($startDate, $endDate);
        $result = array();
        if ($activitys) {
            foreach ($activitys as $value) {
                $key = $value['date'] . $value['username'] . $value['amount'];
                if (!isset($result[$key])) {
                    $result[$key] = array();
                    $result[$key]['date'] = $value['date'];
                    $result[$key]['username'] = $value['username'];
                    $result[$key]['amount'] = $value['amount'];
                    $result[$key]['check_num'] = $value['check_num'];
                    $result[$key]['is_joined'] = $value['is_joined'];
                    $result[$key]['is_check_prize'] = $value['is_check_prize'];
                    $result[$key]['prize_num'] = '';
                    $result[$key]['level'] = '';
                }
                else {
                    $result[$key]['prize_num'].=",";
                }
                $result[$key]['prize_num'].=$value['prize_num'];
                if ($value['level'] != 0) {
                    if ($result[$key]['level'] != '') {
                        $result[$key]['level'].=',';
                    }
                    $result[$key]['level'].=$value['prize_num'] . '(' . $value['level'] . '等奖)';
                }
            }
        }
        //预设查询框
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('activitys', $result);
        self::$view->render('market_guoqingactivity');
    }

    /**
     *  'addUserGifts' => '增加礼品券',
     */
    public function addUserGifts()
    {
        $locations = array(0 => array('title' => '返回列表', 'url' => url('market', 'userGifts')));
        self::$view->setVar('actionLinks', $locations);
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $usernames = $this->request->getPost('usernames', 'trim');
            //这样*nix/win通用了
            $usernames = explode("\n", $usernames);
            foreach ($usernames as $k => $v) {
                $usernames[$k] = trim($usernames[$k]);
                if (!$user = users::getItem($usernames[$k])) {
                    showMsg("添加失败，没有此用户：‘{$usernames[$k]}’");
                }
                $data = array(
                    'user_id' => $user['user_id'],
                    'gift' => $this->request->getPost('gift', 'trim'),
                    'failed_gift' => $this->request->getPost('failed_gift', 'trim'),
                    'type' => $this->request->getPost('type', 'trim'),
                    'title' => $this->request->getPost('title', 'trim'),
                    'from_time' => $this->request->getPost('from_time', 'trim'),
                    'to_time' => $this->request->getPost('to_time', 'trim'),
                    'min_day_water' => $this->request->getPost('min_day_water', 'trim'),
                    'min_total_water' => $this->request->getPost('min_total_water', 'trim'),
                    'is_include_child' => $this->request->getPost('is_include_child', 'trim'),
                    'remark' => $this->request->getPost('remark', 'trim'),
                    'admin_id' => $GLOBALS['SESSION']['admin_id'],
                    'status' => 0, //需要点击
                );
                if (!userGifts::addUserGifts($data)) {
                    showMsg("添加失败!请检查数据输入是否完整。");
                }
            }

            showMsg("添加成功");
        }
        self::$view->render('market_addusergifts');
    }

    /**
     *  'userGifts' => '礼品券',
     */
    public function userGifts()
    {
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加礼品券', 'url' => url('market', 'addUserGifts'))));
        $from_time = $this->request->getGet('from_time', 'trim', date("Y-m-d 00:00:00", strtotime('-1 days')));
        $to_time = $this->request->getGet('to_time', 'trim', userGifts::MAX_TIME);
        $status = $this->request->getGet('status', 'trim');
        $type = $this->request->getGet('type', 'trim', '');
        $title = $this->request->getGet('title', 'trim', '');
        $username = $this->request->getGet('username', 'trim');
        $userId = '';
        if ($username) {
            if (!$user = users::getItem($username)) {
                showMsg("没有此用户：‘{$username}’");
            }
            $userId = $user['user_id'];
        }
        $pageNum = 5000; //DEFAULT_PER_PAGE
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * $pageNum;
        $userGiftsNumber = userGifts::getItemsNumber($from_time, $to_time, $userId, $status, $type, $title);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $userGiftsNumber, $pageNum);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $userGifts = userGifts::getItems($from_time, $to_time, $userId, $status, $type, $title, $startPos, $pageNum);
        $titles = userGifts::getTitles();
        self::$view->setVar('pageList', getPageList($userGiftsNumber, $pageNum));
        self::$view->setVar('from_time', $from_time);
        self::$view->setVar('to_time', $to_time);
        self::$view->setVar('status', $status);
        self::$view->setVar('type', $type);
        self::$view->setVar('title', $title);
        self::$view->setVar('titles', $titles);
        self::$view->setVar('username', $username);
        self::$view->setVar('userGifts', $userGifts);
        self::$view->setVar('canVerify', adminGroups::verifyPriv(array(CONTROLLER, 'sendUserGifts')));
        //后台可以设置不需要删除功能
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteUserGifts')));
        self::$view->render('market_usergifts');
    }

    /**
     * 发放成功后改价格
     */
    public function sendLolSkin()
    {
        if (IS_AJAX) {
            $gift = $this->request->getPost('gift', 'intval', 0);
            $ug_id = $this->request->getPost('ug_id', 'intval', 0);
            $UserGifts = M('UserGifts');
            $result = $UserGifts
                ->where(['ug_id' => $ug_id])
                ->update([
                    'gift' => $gift,
                    'status' => 4,
                ]);

            if ($result === false) {
                response(['errCode' => 1, 'errMsg' => $UserGifts->getError()]);
            }

            response(['errCode' => 0, 'errMsg' => '', 'info' => '编辑成功']);
        }
    }

    /**
     *    'sendUserGifts' => '发放礼品券',
     */
    public function sendUserGifts()
    {
        if ($ug_id = $this->request->getGet('ug_id', 'intval')) {
            if (!$userGift = userGifts::getItem($ug_id)) {
                showMsg("没有此数据");
            }

            $op = $this->request->getGet('op', 'trim', '');

            /**
             * 拒绝发放的情况
             */
            if ($op == 'not') {
                if ($userGift['status'] == 7) {
                    if (!userGifts::updateItem($ug_id, array('status' => 9, 'verify_time' => date('Y-m-d H:i:s'), 'verify_admin_id' => $GLOBALS['SESSION']['admin_id']))) {
                        showMsg("拒绝发放不成功");
                    }
                    showMsg("拒绝发放成功");
                }
                else if ($userGift['status'] == 8) {
                    if (!userGifts::updateItem($ug_id, array('status' => 10, 'verify_time' => date('Y-m-d H:i:s'), 'verify_admin_id' => $GLOBALS['SESSION']['admin_id']))) {
                        showMsg("拒绝发放不成功");
                    }
                    showMsg("拒绝发放成功");
                }
            }
            /**
             * 资格审核的情况
             */
            else if ($op == 'verify') {
                $userGift['status'] = 1;
                if (!userGifts::addUserGifts($userGift)) {
                    showMsg("通过资格审核不成功");
                }
                showMsg("成功通过资格审核");
            }
            /**
             * 发放审核的情况
             */
            else {
                if ($userGift['status'] == 7) {
                    if (!userGifts::sendUserGifts($ug_id, $GLOBALS['SESSION']['admin_id'])) {
                        showMsg("发放不成功");
                    }
                    showMsg("发放成功");
                }
                else if ($userGift['status'] == 8) {
                    if (!userGifts::sendUserFailedGifts($ug_id, $GLOBALS['SESSION']['admin_id'])) {
                        showMsg("发放不成功");
                    }
                    showMsg("发放成功");
                }
            }
        }
    }

    /**
     *    'deleteUserGifts' => '删除礼品券',
     */
    public function deleteUserGifts()
    {
        if ($ug_id = $this->request->getGet('ug_id', 'intval')) {
            if (!userGifts::deleteUserGifts($ug_id, $GLOBALS['SESSION']['admin_id'])) {
                showMsg("删除失败");
            }
            else {
                showMsg("删除成功");
            }
        }
    }

    public function amountChart()
    {
        $show1 = $this->request->getGet('show1', 'intval');
        $show2 = $this->request->getGet('show2', 'intval');
        $top_id = $this->request->getGet('top_id', 'intval', '-1');
        //得到所有总代
        // $topUsers = users::getUserTree(0,-1,0,-1,-1,'',1);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
            'freeze' => 1
        ]);
        //>>修改传入数组,不传入json
        self::$view->setVar('json_topUsers', $topUsers);
        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d', time()));
        if (!$show1 && !$show2) {
            $show1 = $show2 = 1;
        }
        if ($top_id) {
            $user = users::getItem($top_id,8,false,1,1);
            self::$view->setVar('user', $user);
        }
        self::$view->setVar('show1', $show1);
        self::$view->setVar('show2', $show2);
        self::$view->setVar('top_id', $top_id);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        if ($this->request->getGet('op', 'trim') == 'getXML') {
            $fc = new flashChart();
            $totalDay = ceil((strtotime($endDate) - strtotime($startDate)) / 86400);
            $days = $deposit_amounts = $withdraw_amounts = array();
            $teamReport = teamReports::getItems($top_id, $startDate, $endDate);
            //初始数组
            for ($i = strtotime($startDate); $i < strtotime($endDate); $i += 86400) {
                $deposit_amounts[date('Y-m-d', $i)] = 0.00;
                $withdraw_amounts[date('Y-m-d', $i)] = 0.00;
                $days[] = date('y-m-d', $i);
            }
            foreach ($teamReport as $k => $v) {
                $deposit_amounts[$v['belong_date']] += $v['deposit_amount'];
                $withdraw_amounts[$v['belong_date']] += $v['withdraw_amount'];
            }

            //logdump($prize_amount);
            $fc->addLabels($days);
            //底部显示几栏标签 一般为10
            $labelstep = ceil($totalDay / 10);
            $fc->setChart('labelstep', $labelstep);

            if ($show1) {
                $fc->addData(array_values($deposit_amounts), '存款量', '#0033CC');
            }
            if ($show2) {
                $fc->addData(array_values($withdraw_amounts), '提款量', 'FF0000');
            }
            $fc->display();
            exit;
        }

        self::$view->render('market_amountchart');
    }

    public function userChart()
    {
        $show1 = $this->request->getGet('show1', 'intval');
        $show2 = $this->request->getGet('show2', 'intval');
        $show3 = $this->request->getGet('show3', 'intval');
        $show4 = $this->request->getGet('show4', 'intval');
        $lottery_id = $this->request->getGet('lottery_id', 'intval');

        //得到所有彩种
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
        self::$view->setVar('json_lotterys', json_encode($lotterys));

        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d', time()));

        if (!$show1 && !$show2 && !$show3 && !$show4) {
            $show1 = $show2 = $show3 = $show4 = 1;
        }
        if ($lottery_id) {
            $lottery = lottery::getItem($lottery_id);
            self::$view->setVar('lottery', $lottery);
        }
        self::$view->setVar('show1', $show1);
        self::$view->setVar('show2', $show2);
        self::$view->setVar('show3', $show3);
        self::$view->setVar('show4', $show4);
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        if ($this->request->getGet('op', 'trim') == 'getXML') {
            $fc = new flashChart();
            $totalDay = ceil((strtotime($endDate) - strtotime($startDate)) / 86400);
            $play_user_nums = $first_deposit_nums = $avail_first_deposit_nums = $reg_nums = $regUserNum = array();


            $dayReport = reportDay::getItems($startDate, $endDate);
            if (!empty($dayReport)) {
                $dayReport = array_spec_key($dayReport, 'date');
            }

            $users = users::getItems(-1, true, 0, array('user_id', 'reg_time'), 8, 0, $startDate, $endDate,'','','','','','','','',-1,-1,DEFAULT_PER_PAGE,[],'',-1,1);
            //按日期统计注册人数
            foreach ($users as $v) {
                $day = date('Y-m-d', strtotime($v['reg_time']));
                if (!isset($regUserNum[$day])) {
                    $regUserNum[$day] = 0;
                }
                else {
                    $regUserNum[$day] ++;
                }
            }

            //初始数组
            for ($i = strtotime($startDate); $i < strtotime($endDate); $i += 86400) {
                $play_user_nums[date('Y-m-d', $i)] = 0;
                $first_deposit_nums[date('Y-m-d', $i)] = 0;
                $avail_first_deposit_nums[date('Y-m-d', $i)] = 0;
                $reg_nums[date('Y-m-d', $i)] = 0;
                $days[] = date('y-m-d', $i);
                //以上先初始化，如果有此日数据则赋值
                if (isset($dayReport[date('Y-m-d', $i)])) {
                    $play_user_nums[date('Y-m-d', $i)] = $lottery_id != 0 ? $dayReport[date('Y-m-d', $i)]['play_user_num_' . $lottery_id] : $dayReport[date('Y-m-d', $i)]['play_user_num'];
                    $avail_first_deposit_nums[date('Y-m-d', $i)] = $dayReport[date('Y-m-d', $i)]['avail_first_deposit_num'];
                    $first_deposit_nums[date('Y-m-d', $i)] = $dayReport[date('Y-m-d', $i)] = $dayReport[date('Y-m-d', $i)]['first_deposit_num'];
                }
                if (isset($regUserNum[date('Y-m-d', $i)])) {
                    $reg_nums[date('Y-m-d', $i)] = $regUserNum[date('Y-m-d', $i)];
                }
            }

            // logdump($user_num);
            $fc->addLabels($days);
            //底部显示几栏标签 一般为10
            $labelstep = ceil($totalDay / 10);
            $fc->setChart('labelstep', $labelstep);

            if ($show1) {
                $fc->addData(array_values($play_user_nums), '参与游戏用户数', 'FF0000');
            }
            if ($show2) {
                $fc->addData(array_values($reg_nums), '开户量', '#00FFCC');
            }
            if ($show3) {
                $fc->addData(array_values($first_deposit_nums), '首存人数', '#0000CC');
            }
            if ($show4) {
                $fc->addData(array_values($avail_first_deposit_nums), '有效首存人数', '#EB21BE');
            }
            $fc->display();
            exit;
        }

        self::$view->render('market_userchart');
    }

    public function saleChart()
    {

        $show1 = $this->request->getGet('show1', 'intval');
        $show2 = $this->request->getGet('show2', 'intval');
        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d', time()));

        if (!$show1 && !$show2) {
            $show1 = $show2 = 1;
        }

        self::$view->setVar('show1', $show1);
        self::$view->setVar('show2', $show2);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        if ($this->request->getGet('op', 'trim') == 'getXML') {
            $fc = new flashChart();
            $totalDay = ceil((strtotime($endDate) - strtotime($startDate)) / 86400);
            $days = $buy_amounts = $avg_amounts = array();
            $dayReport = reportDay::getItems($startDate, $endDate);
            if (!empty($dayReport)) {
                $dayReport = array_spec_key($dayReport, 'date');
            }
            //初始数组
            for ($i = strtotime($startDate); $i < strtotime($endDate); $i += 86400) {
                $buy_amounts[date('Y-m-d', $i)] = 0.00;
                $avg_amounts[date('Y-m-d', $i)] = 0.00;
                $days[] = date('y-m-d', $i);
                if (isset($dayReport[date('Y-m-d', $i)])) {
                    $buy_amounts[date('Y-m-d', $i)] = $dayReport[date('Y-m-d', $i)]['buy_amount'];
                    //暂时不显示人均销量
                    //$avg_amounts[date('Y-m-d', $i)] = number_format($dayReport[date('Y-m-d', $i)]['buy_amount'] / $dayReport[date('Y-m-d', $i)]['play_user_num'], 2);
                }
            }

            $fc->addLabels($days);
            //底部显示几栏标签 一般为10
            $labelstep = ceil($totalDay / 10);
            $fc->setChart('labelstep', $labelstep);

            if ($show1) {
                $fc->addData(array_values($buy_amounts), '投注量 ', '00FF00');
            }
            if ($show2) {
                $fc->addData(array_values($avg_amounts), '人均销量', '#00FFCC');
            }
            $fc->display();
            exit;
        }

        self::$view->render('market_salechart');
    }

    public function roulettePlan()
    {

        if ($this->request->getPost('op', 'trim') == 'sendPlans') {
            if (!$datas = $this->request->getPost('datas', 'array')) {
                echo 2;
                exit;
            }
            $plan_type = $this->request->getPost('plan_type', 'intval');
            $plan_level = $this->request->getPost('plan_level', 'intval');
            $tmp = array();
            $tmp['plan_type'] = $plan_type;
            $tmp['plan_level'] = $plan_level;
            $tmp['create_admin_id'] = $GLOBALS['SESSION']['admin_id'];
            $tmp['create_time'] = date("Y-m-d H:i:s");
            $GLOBALS['db']->startTransaction();
            foreach ($datas as $v) {
                $tmp['plan_time'] = $v;
                if (!userGiftsRoulette::addItem($tmp)) {
                    $GLOBALS['db']->rollback();
                    echo 3;
                    exit;
                }
            }
            $GLOBALS['db']->commit();
            echo 1;
            exit;
        }
        $prizeLevels = userGiftsRoulette::getLevelToPrizeString();
        unset($prizeLevels[0]);
        unset($prizeLevels[1]);
        unset($prizeLevels[7]);
        self::$view->setVar('prizeLevels', $prizeLevels);
        self::$view->render('market_rouletteplan');
    }

    public function roulettePlanList()
    {
        if ($this->request->getGet('op', 'trim') == 'del') {
            $id = $this->request->getGet('id', 'intval');
            if (userGiftsRoulette::deleteItem($id)) {
                echo 1;
                exit;
            }
            echo 2;
            exit;
        }

        $prizeLevel = $this->request->getGet('prizeLevel', 'intval', -1);
        $start_time = $this->request->getGet('start_time', 'trim', date('Y-m-d'));
        if(strlen($start_time) < 11) {
            $start_time .= ' 00:00:00';
        }
        $end_time = $this->request->getGet('end_time', 'trim', date('Y-m-d'));
        if(strlen($end_time) < 11) {
            $end_time .= ' 23:59:59';
        }
        $time_section = $this->request->getGet('time_section', 'intval', 0);
        $plan_type = $this->request->getGet('plan_type', 'trim', '');
        $sort = $this->request->getGet('sort', 'intval', 0);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        if ($plan_type === '') {
            $plan_type = array(1, 2);
        }

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $count = userGiftsRoulette::getItemsNumber($start_time, $end_time, '', array(0, 1), $plan_type, $prizeLevel, $time_section);
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $count);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $plans = userGiftsRoulette::getItems($start_time, $end_time, '', array(0, 1), $plan_type, $prizeLevel, array('section' => $time_section, 'sort' => $sort), $startPos);

        $admins = admins::getItems(0, 1);
        $locations[] = array('title' => '添加计划', 'url' => url('market', 'roulettePlan'));
        $locations[] = array('title' => '开奖详细列表', 'url' => url('market', 'rouletteAwardList'));
        $locations[] = array('title' => '开奖统计列表', 'url' => url('market', 'rouletteAwardReport'));
        $locations[] = array('title' => '非真实中奖列表', 'url' => url('market', 'rouletteUnrealList'));
        $prizeLevels = userGiftsRoulette::getLevelToPrizeString();
        unset($prizeLevels[0]);
        unset($prizeLevels[1]);
        unset($prizeLevels[7]);

        self::$view->setVar('actionLinks', $locations);
        self::$view->setVar('prizeLevel', $prizeLevel);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('time_section', $time_section);
        self::$view->setVar('plan_type', $plan_type);
        self::$view->setVar('sort', $sort);
        self::$view->setVar('prizeLevels', $prizeLevels);
        self::$view->setVar('admins', $admins);
        self::$view->setVar('plans', $plans);
        self::$view->setVar('pageList', getPageList($count));
        self::$view->render('market_rouletteplanlist');
    }

    public function rouletteUnrealList()
    {

        if ($this->request->getGet('op', 'trim') == 'del') {
            $id = $this->request->getGet('id', 'intval');
            if (userGiftsRoulette::deleteItem($id)) {
                echo 1;
                exit;
            }
            echo 2;
            exit;
        }

        $prizeLevel = $this->request->getGet('prizeLevel', 'intval', -1);
        $start_time = $this->request->getGet('start_time', 'trim', date('Y-m-d 00:00:00'));
        $end_time = $this->request->getGet('end_time', 'trim', date('Y-m-d 23:59:59'));
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $count = userGiftsRoulette::getItemsNumber($start_time, $end_time, '', 3, '', $prizeLevel);
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $count);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $plans = userGiftsRoulette::getItems($start_time, $end_time, '', 3, 0, $prizeLevel, array('section' => 0, 'sort' => 1), $startPos);
        $admins = admins::getItems(0, 1);
        $locations = array(array('title' => '创建非真实中奖纪录', 'url' => url('market', 'rouletteUnrealPlan')));

        $prizeLevels = userGiftsRoulette::getLevelToPrizeString();
        unset($prizeLevels[0]);
        unset($prizeLevels[1]);

        self::$view->setVar('plans', $plans);
        self::$view->setVar('prizeLevel', $prizeLevel);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('actionLinks', $locations);
        self::$view->setVar('admins', $admins);
        self::$view->setVar('pageList', getPageList($count));
        self::$view->setVar('prizeLevels', $prizeLevels);
        self::$view->render('market_rouletteunreallist');
    }

    public function rouletteUnrealPlan()
    {
        if ($this->request->getPost('submit', 'trim')) {
            $prizeLevel = $this->request->getPost('prizeLevel', 'intval');
            $username = $this->request->getPost('username', 'trim');
            $plan_time = $this->request->getPost('plan_time', 'trim');

            if (!$user = users::getItem($username)) {
                showMsg('该用户不存在');
            }

            $data = array();
            $data['plan_time'] = $plan_time;
            $data['plan_type'] = 0;
            $data['plan_level'] = $prizeLevel;
            $data['status'] = 3;
            $data['user_id'] = $user['user_id'];
            $data['create_admin_id'] = $GLOBALS['SESSION']['admin_id'];
            $data['create_time'] = date("Y-m-d H:i:s");
            if (userGiftsRoulette::addItem($data)) {
                showMsg('创操作成功');
            }
            showMsg('操作失败');
        }

        $prizeLevels = userGiftsRoulette::getLevelToPrizeString();
        unset($prizeLevels[0]);
        unset($prizeLevels[1]);


        self::$view->setVar('prizeLevels', $prizeLevels);
        self::$view->render('market_rouletteunrealplan');
    }

    public function rouletteAwardList()
    {
        //统计数据不包含假数据 status = 1
        $locations = array(array('title' => '开奖结果统计', 'url' => url('market', 'rouletteAwardReport')));
        $planLevel = $this->request->getGet('planLevel', 'intval', -1);
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        if(strlen($start_time) < 11) {
            $start_time .= ' 00:00:00';
        }
        $end_time = $this->request->getGet('end_time', 'trim', date('Y-m-d 23:59:59'));
        if(strlen($end_time) < 11) {
            $end_time .= ' 23:59:59';
        }
        $time_section = $this->request->getGet('time_section', 'intval', 0);
        $plan_type = $this->request->getGet('plan_type', 'trim', '');
        $status = $this->request->getGet('status', 'trim', '');

        $sort = $this->request->getGet('sort', 'intval', 0);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $user_id = '';
        if ($username = $this->request->getGet('username', 'trim', '')) {
            $user_id = -1;
            if ($user = users::getItem($username)) {
                $user_id = $user['user_id'];
            }
        }
        $planType = $plan_type !== '' ? $plan_type : array(1, 2);
        $status = $status !== '' ? $status : array(0, 1);

        $awards = array();
        $count = 0;
        if ($user_id != -1) {
            $count = userGiftsRoulette::getItemsNumber($start_time, $end_time, $user_id, $status, $planType, $planLevel, $time_section);

            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            $curPage = $this->request->getGet('curPage', 'intval', 1);
            //>>判断输入的页码是否超过最大值.
            $startPos = getStartOffset($curPage, $count);
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/

            $awards = userGiftsRoulette::getItems($start_time, $end_time, $user_id, $status, $planType, $planLevel, array('section' => $time_section, 'sort' => $sort), $startPos);
        }

        $planLevels = userGiftsRoulette::getLevelToPrizeString();
        unset($planLevels[0]);
        unset($planLevels[1]);

        self::$view->setVar('actionLinks', $locations);
        self::$view->setVar('planLevel', $planLevel);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('time_section', $time_section);
        self::$view->setVar('plan_type', $plan_type);
        self::$view->setVar('status', $status);
        self::$view->setVar('username', $username);
        self::$view->setVar('sort', $sort);
        self::$view->setVar('planLevels', $planLevels);
        self::$view->setVar('awards', $awards);
        self::$view->setVar('pageList', getPageList($count));
        self::$view->render('market_rouletteawardlist');
    }

    public function rouletteAwardReport()
    {
        $start_time = $this->request->getPost('start_time', 'trim', date("Y-m-d"));
        if(strlen($start_time) < 11) {
            $start_time .= ' 00:00:00';
        }
        $end_time = $this->request->getPost('end_time', 'trim', date('Y-m-d'));
        if(strlen($end_time) < 11) {
            $end_time .= ' 23:59:59';
        }
        $time_section = $this->request->getPost('time_section', 'intval', 0);
        $originalPlans = $addPlans = array(2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0);
        //$plans = userGiftsRoulette::getItems($start_time, $end_time, '', '', array(1, 2));
        $plans = userGiftsRoulette::getItems($start_time, $end_time, '', '', array(1, 2), -1, array('section' => $time_section, 'sort' => 1));
        foreach ($plans as $v) {
            if ($v['plan_type'] == 1) {
                $originalPlans[$v['plan_level']] ++;
            }
            if ($v['plan_type'] == 2) {
                $addPlans[$v['plan_level']] ++;
            }
        }
        $awardReport = userGiftsRoulette::getAwardReport($start_time, $end_time, $time_section);

        self::$view->setVar('awardReport', $awardReport);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('originalPlans', $originalPlans);
        self::$view->setVar('addPlans', $addPlans);
        self::$view->setVar('time_section', $time_section);
        self::$view->setVar('planLevels', userGiftsRoulette::getLevelToPrizeString());
        self::$view->render('market_rouletteawardreport');
    }

    public function cardList()
    {
        $locations = array(
            array('title' => '变现记录', 'url' => url('market', 'cardExchangeList')),
            array('title' => '派奖统计', 'url' => url('market', 'cardPrizeStaistics')),
        );
        $cardsConfig = userGiftsCard::getCardConfig();
        unset($cardsConfig[5]);
        unset($cardsConfig[10]);
        unset($cardsConfig[15]);
        unset($cardsConfig[20]);

        $cardId = $this->request->getGet('card_id', 'intval');
        $startTime = $this->request->getGet('start_time', 'trim', userGiftsCard::ACTIVITY_START_TIME);
        $endTime = $this->request->getGet('end_time', 'trim', userGiftsCard::ACTIVITY_END_TIME);
        $username = $this->request->getGet('username', 'trim');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $cardCount = userGiftsCard::getItemsNumber('', $username, $cardId, array(0, 7, 8), $startTime, $endTime);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $cardCount);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $cards = userGiftsCard::getItems('', $username, $cardId, array(0, 7, 8), $startTime, $endTime, '', '', 'create_time DESC', $startPos);

        self::$view->setVar('actionLinks', $locations);
        self::$view->setVar('cardsConfig', $cardsConfig);
        self::$view->setVar('cards', $cards);
        self::$view->setVar('cardCount', $cardCount);
        self::$view->setVar('cardId', $cardId);
        self::$view->setVar('startTime', $startTime);
        self::$view->setVar('endTime', $endTime);
        self::$view->setVar('username', $username);
        self::$view->setVar('pageList', getPageList($cardCount));
        self::$view->render('market_cardlist');
    }

    public function cardExchangeList()
    {
        $locations = array(
            array('title' => '变现记录', 'url' => url('market', 'cardExchangeList')),
            array('title' => '派奖统计', 'url' => url('market', 'cardPrizeStaistics')),
        );
        $cardsConfig = userGiftsCard::getCardConfig();

        $cardId = $this->request->getGet('card_id', 'intval');
        $startTime = $this->request->getGet('start_time', 'trim', userGiftsCard::EXCHANGE_START_TIME);
        $endTime = $this->request->getGet('end_time', 'trim', userGiftsCard::EXCHANGE_END_TIME);
        $username = $this->request->getGet('username', 'trim');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $cardsCount = userGiftsCard::getItemsNumber('', $username, $cardId, array(7, 9), '', '', $startTime, $endTime);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $cardsCount);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $cards = userGiftsCard::getItems('', $username, $cardId, array(7, 9), '', '', $startTime, $endTime, 'prize_time DESC', $startPos);

        self::$view->setVar('actionLinks', $locations);
        self::$view->setVar('cardsConfig', $cardsConfig);

        self::$view->setVar('cards', $cards);
        self::$view->setVar('cardsCount', $cardsCount);

        self::$view->setVar('cardId', $cardId);
        self::$view->setVar('startTime', $startTime);
        self::$view->setVar('endTime', $endTime);
        self::$view->setVar('username', $username);
        self::$view->setVar('pageList', getPageList($cardsCount));
        self::$view->render('market_cardexchangelist');
    }

    public function cardPrizeStaistics()
    {
        $locations = array(
            array('title' => '变现记录', 'url' => url('market', 'cardExchangeList')),
            array('title' => '派奖统计', 'url' => url('market', 'cardPrizeStaistics')),
        );
        $cardsConfig = userGiftsCard::getCardConfig();

        $startTime = $this->request->getGet('start_time', 'trim', userGiftsCard::EXCHANGE_START_TIME);
        $endTime = $this->request->getGet('end_time', 'trim', userGiftsCard::EXCHANGE_END_TIME);
        $cardStaistics = userGiftsCard::getCardStaistics($startTime, $endTime);

        $totalCount = $totalPrize = 0;
        foreach ($cardStaistics['prize'] AS $cardId =>$value) {
            $totalCount += $value['count'];
            $totalPrize += $value['count'] * $cardsConfig[$cardId]['prize'];
        }

        $distinctUser = userGiftsCard::getCardDistinctUser($startTime, $endTime);

        self::$view->setVar('actionLinks', $locations);
        self::$view->setVar('cardsConfig', $cardsConfig);

        self::$view->setVar('startTime', $startTime);
        self::$view->setVar('endTime', $endTime);
        self::$view->setVar('cardStaistics', $cardStaistics);
        self::$view->setVar('totalCount', $totalCount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('distinctUser', $distinctUser);

        self::$view->render('market_cardprizestaistics');
    }

    public function activityList()
    {

        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'saveSort':
                    $sort_ids = $this->request->getPost('sort_ids', 'array');
                    foreach ($sort_ids as $menu_id => $sort) {
                        if ($sort > 255 || $sort < 0)
                        {
                            //>>验证sort ,只能输入0-255的数字
                            showMsg('只能输入0-255的数字,请检查后重新输入');
                        }
                        activity::updateItem($menu_id, array('sort' => $sort));
                    }
                    /******************** snow start 修改使用活动可以排序*************************/
                    //删除cache文件
                    /************** author snow 删除缓存**********************/
                    $this->deleteCacheForActivity();
                    /************** author snow 删除缓存**********************/
                    exec('rm -f '.ROOT_PATH.'ssc/cache/*');
                    exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
                    @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
                    /******************** snow end 修改使用活动可以排序*************************/
                    showMsg("保存成功");
                    break;
            }
        }
        $activities = activity::getItems();
        foreach ($activities as $k => $v) {
            $activities[$k]['banner_img_thumb'] = $this->_activityThumbImg($v['banner_img']);
            $activities[$k]['thumb_img_thumb'] = $this->_activityThumbImg($v['thumb_img']);
            $activities[$k]['main_img_thumb'] = $this->_activityThumbImg($v['main_img']);
            $activities[$k]['m_banner_img_thumb'] = $this->_activityThumbImg($v['m_banner_img']);
            $activities[$k]['m_thumb_img_thumb'] = $this->_activityThumbImg($v['m_thumb_img']);
            $activities[$k]['m_main_img_thumb'] = $this->_activityThumbImg($v['m_main_img']);
        }
        self::$view->setVar('canPush', adminGroups::verifyPriv(array(CONTROLLER, 'pushActivity')));
        self::$view->setVar('activities', $activities);
        self::$view->setVar('actionLinks', array(0 => array('title' => '新增活动', 'url' => url('market', 'addActivity'))));
        self::$view->render('market_activitylist');
    }

    public function pushActivity(){
        $locations = array(0 => array('title' => '返回优惠活动列表', 'url' => url('market', 'activityList')));
        $activity_model = M('activity');
        if (empty($id = $this->request->getGet('id', 'intval'))) showMsg("参数无效");
        if (empty($activity=$activity_model->where(['id'=>$id])->find())) {
            showMsg("该活动不存在");
        }
        if(empty($activity['m_thumb_img']) || empty($activity['m_main_img'])){
            showMsg('请上传手机活动简图和手机活动图');
        }
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $title=$this->request->getPost('title','string','');
            $alert=$this->request->getPost('alert','string','');
            if(empty($title)||empty($alert)) showMsg("请填写推送标题和内容");
            $title=mb_strlen($title)>20?mb_substr($title,0,20).'...':$title;
            $alert=mb_strlen($alert)>100?mb_substr($alert,0,100).'...':$alert;

            M()->startTrans();
            $res=$activity_model->where(['id'=>$id])->update(['push_msg'=>serialize(['title'=>$title,'alert'=>$alert])]);
            if($res===false){
                M()->rollback();
                showMsg('推送数据失败1');
            }
            if($this->pushAll($title, $alert,['type'=>'2','activity_id'=>$id])!==true){
                M()->rollback();
                showMsg('推送数据失败2');
            }
            M()->commit();
            showMsg("推送数据成功", 0, $locations);
        }
        $title='优惠活动来啦!';
        $alert="最新优惠活动 {$activity['title']} 重磅来袭!活动时间:{$activity['start_time']}至{$activity['end_time']},千万不要错过!";
        if(!empty($activity['push_msg'])){
            $push_msg=unserialize($activity['push_msg']);
            if(!empty($push_msg['title']))$title=$push_msg['title'];
            if(!empty($push_msg['alert']))$alert=$push_msg['alert'];
        }
        self::$view->setVar('id', $id);
        self::$view->setVar('alert', $alert);
        self::$view->setVar('title', $title);

        self::$view->render('marker_pushActivity');
    }

    public function addActivity()
    {
        if($this->request->getPost('add', 'intval')){
            $data = array();

            $title =$this->request->getPost('title', 'trim');
            $target =$this->request->getPost('target', 'trim');
            $content =$this->request->getPost('content', 'trim');
            $start_time =$this->request->getPost('start_time', 'trim');
            $end_time =$this->request->getPost('end_time', 'trim');

            $data['title'] = $title;
            $data['target'] = $target;
            $data['content'] = $content;
            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;

            $types = 'jpg|png|gif';  //>>图片允许上传的格式
            $maxsize = 2048;         //>>允许上传的图片大小
            $up = new upload( $types, $maxsize );
            $up->set_thumb(100,80);
            $fs = $up->execute();

            foreach ($fs as $k => $v) {
                if($v['flag'] != 1){
                    $data[$k] = '';
                    continue;
                }
                $data[$k] = $v['dir'] . $v['name'];
                //上传七牛
                $qiniu = new uptoqiniu($v['name'],$v['dir']);
                $qiniu->upload();

                //>>上传到阿里云存储
                $aliyun = new uploadaliyun($v['name'], $v['dir']);
                if(($result = $aliyun->upload()) !== true){
                    showMsg($result);
                }
            }

            activity::addItem($data);
            /************** author snow 删除缓存**********************/
            $this->deleteCacheForActivity();
            /************** author snow 删除缓存**********************/
            exec('rm -f '.ROOT_PATH.'ssc/cache/*');
            exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
            @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
            showMsg('添加成功');
        }


        self::$view->render('market_addactivity');
    }

    public function editActivity()
    {
        if($this->request->getPost('edit', 'trim')){
            $data = array();
            $id =$this->request->getPost('id', 'trim');
            $title =$this->request->getPost('title', 'trim');
            $target =$this->request->getPost('target', 'trim');
            $content =$this->request->getPost('content', 'trim');
            $start_time =$this->request->getPost('start_time', 'trim');
            $end_time =$this->request->getPost('end_time', 'trim');
            $sort =$this->request->getPost('sort', 'trim');

            $data['title'] = $title;
            $data['target'] = $target;
            $data['content'] = $content;
            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;
            $data['sort'] = $sort;
            $types = 'jpg|png|gif';  //>>图片允许上传的格式
            $maxsize = 2048;         //>>允许上传的图片大小
            $up = new upload( $types, $maxsize );
            $up->set_thumb(100,80);
            $fs = $up->execute();
            $activity = activity::getItem($id);
            foreach ($fs as $k => $v) {
                if($v['flag'] == 1){
                    $thumbImg = $this->_activityThumbImg($activity[$k]);
                    //原来的图片和后台缩略图则删掉
                    @unlink($activity[$k]);
                    @unlink($thumbImg);
                    $data[$k] = $v['dir'] . $v['name'];
                    //上传七牛
                    $qiniu = new uptoqiniu($v['name'],$v['dir']);
                    $qiniu->upload();

                    //>>上传到阿里云存储
                    $aliyun = new uploadaliyun($v['name'], $v['dir']);
                    if(($result = $aliyun->upload()) !== true){
                        showMsg($result);
                    }
                } else {
                    $data[$k] = '';
                }
            }

            activity::updateItem($id, $data);
            //删除cache文件
            /************** author snow 删除缓存**********************/
            $this->deleteCacheForActivity();
            /************** author snow 删除缓存**********************/
            exec('rm -f '.ROOT_PATH.'ssc/cache/*');
            exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
            @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
            showMsg('更新成功');
        }
        $id =$this->request->getGet('id', 'trim');
        $activity = activity::getItem($id);
        $activity['banner_img_thumb'] = $this->_activityThumbImg($activity['banner_img']);
        $activity['thumb_img_thumb'] = $this->_activityThumbImg($activity['thumb_img']);
        $activity['main_img_thumb'] = $this->_activityThumbImg($activity['main_img']);
        $activity['m_banner_img_thumb'] = $this->_activityThumbImg($activity['m_banner_img']);
        $activity['m_thumb_img_thumb'] = $this->_activityThumbImg($activity['m_thumb_img']);
        $activity['m_main_img_thumb'] = $this->_activityThumbImg($activity['m_main_img']);

        self::$view->setVar('activity', $activity);
        self::$view->render('market_editactivity');
    }

    private function _activityThumbImg($srcImg)
    {
        if($srcImg == ''){
            return $srcImg;
        }
        preg_match('@.*(images_fh.*)$@',$srcImg,$macth);
        if(isset($macth[1])){
            $tmp = explode('/', $macth[1]);
            $srcName = $tmp[count($tmp)-1];
            unset($tmp[count($tmp)-1]);
            $newImg = implode('/', $tmp);
            $img = $newImg.'/thumb_'.$srcName;

            //return $newImg.'/thumb_'.$srcName;
            //由于系统jpeg类库问题先将jpg图片的缩略图转换成png格式
            if(preg_match('@^.*jpg_mpeg$@',$img)){
                $img = substr($img,0,strrpos($img,'.')+1) . 'png';
            }
        } else {
            $img = '';
        }

        return $img;
    }

    public function deleteActivity()
    {
        $id =$this->request->getGet('id', 'trim');
        $activity = activity::getItem($id);
        $kindImg = array('banner_img','thumb_img','main_img','m_banner_img','m_thumb_img','m_main_img');
        foreach ($activity as $k => $v) {
            if(in_array($k,$kindImg)){
                $thumbImg = $this->_activityThumbImg($v);
                @unlink($v);
                @unlink($thumbImg);
            }
        }
        activity::deleteItem($id);
        /************** author snow 删除缓存**********************/
        $this->deleteCacheForActivity();
        /************** author snow 删除缓存**********************/
        //删除cache文件
        exec('rm -f '.ROOT_PATH.'ssc/cache/*');
        exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
        @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
        showMsg('删除成功');
    }

    /**
     * author snow 删除活动相关缓存
     * @return bool
     */
    private function deleteCacheForActivity()
    {
        $redisKey1 = 'appGetActivities_1';
        $redisKey0 = 'appGetActivities_0';

        $GLOBALS['redis']->select(REDIS_DB_APP);//>>切换到app库

        //>>删除缓存
        $GLOBALS['redis']->del($redisKey1);
        $GLOBALS['redis']->del($redisKey0);
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);//>>切换回默认库
        return true;
    }

    public function promoList()
    {
        userGiftsControl::createBackendPage('promoList', $this->request, self::$view);
    }

    public function promoStatics()
    {
        userGiftsControl::createBackendPage('promoStatics', $this->request, self::$view);
    }

    public function promoControl()
    {
        userGiftsControl::createBackendPage('promoControl', $this->request, self::$view);
    }
    public function pushAll($title,$alert,array $extras)
    {
        $appKey=config::getConfig('app_jpush_key','');
        $masterSecret=config::getConfig('app_jpush_masterSecret','');
        if(empty($appKey)||empty($masterSecret))showMsg('请添加推送相关配置信息');
        require_once FRAMEWORK_PATH . 'library/vendor/autoload.php';
        $client = new JPush\Client(trim($appKey), trim($masterSecret));
        try {
            $pusher = $client->push();
            $pusher->setPlatform(array('ios', 'android'));
            $pusher->addAllAudience();

            $pusher->iosNotification([
                "title" => $title,
                "body" => $alert
            ], array(
                'sound' => 'sound.caf',
                'content-available' => true,
                'mutable-content' => true,
                'category' => 'jiguang',
                'extras' => $extras,
            ))->androidNotification($alert, array(
                'title' => $title,
                'extras' => $extras,
            ));


            $pusher->options(array(
                'time_to_live' => 86400,
                'apns_production' => JPUSH_SWITCH,
            ))
                ->send();
            return true;

        } catch (\JPush\Exceptions\APIConnectionException $e) {
            return $e->getMessage();
        } catch (\JPush\Exceptions\APIRequestException $e) {
            return $e->getMessage();
        }
    }
}

