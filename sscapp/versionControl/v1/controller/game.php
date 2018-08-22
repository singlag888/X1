<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：商户管理后台
 * 承接管理员登录等基本后台业务
 */
class gameController extends sscappController
{
    const MAX_TRACE_ISSUE_NUM = 240;   //最大可追号期数
    const OPENNUMS = 30;   //秒秒彩最大可连开次数
    //方法概览
    public $titles = array(
        'packageList' => '投注列表',
        'packageDetail' => '投注 详情',
        'traceList' => '追号列表',
        'traceDetail' => '追号详情',
        'play' => '参与游戏',
        'cancelPackage' => '用户撤单',
        'cancelTrace' => '追号撤单',
        'initCaiZhong' => '进入彩种页面',
        'cancelAllTrace' => 'All追号撤单',
    );

    //初始化
    public function init()
    {
        parent::init(parent::INIT_SESSION);
    }

    private function _show($lottery_id, $group = methodGroups::GROUP_ALL, $game_key)
    {

        // list($user_id, $user) = $this->chkUser(0);

        $lottery = lottery::getItem($lottery_id);
        if (empty($lottery)) die($this->showMsg(7029, mobileErrorCode::LOTTERY_NOT_EXISTS, []));
        $data['game_key'] = $game_key;
        $data['lottery'] = $lottery;
        $data['minRebateGaps'] = $lottery['min_rebate_gaps'];
        //得到全包奖金
        $data['maxCombPrize'] = $lottery['zx_max_comb'] * 2;


        //得到当天已开奖奖期
        if ($lottery_id == 4 && date('Hi') <= '0200') { //对于跨天的在0点以后，应取昨天的$belong_date
            $belong_date = date('Y-m-d', strtotime('-1 day'));
        } else {
            $belong_date = date('Y-m-d');
        }

        $issues = array();
        if ($lottery_id != 15) { //不等于秒秒彩
            if (!$tmp = issues::getItems($lottery_id, $belong_date, 0, 0, 0, 0, 2, 'issue_id DESC', 0, 5)) { //查询当前彩种今日最新五条开奖
                if (!$tmp = issues::getItems($lottery_id, date('Y-m-d', strtotime('-1 day')), 0, 0, 0, 0, 2, 'issue_id DESC', 0, 5)) { //查询当前彩种昨日日最新五条开奖
                    $tmp = issues::getItems($lottery_id, array(date('Y-m-d', strtotime('-10 day')), date('Y-m-d', strtotime('-1 day'))), 0, 0, 0, 0, 2, 'issue_id DESC', 0, 5);//查询当前彩种十日内最新五条开奖
                }
            }
            foreach ($tmp as $v) {
                $issues[] = array(
                    'issue_id' => $v['issue_id'],//奖期id
                    'issue' => $v['issue'],//奖期期号
                    'code' => $v['code'],//开奖号码
                    'original_code' => $v['original_code'],//获取开奖号码源数据(用处不大)
                );
            }
            $data['json_openedIssues'] = $issues;
            //得到最近一期遗漏冷热
            $recentIssue = reset($issues);
            //从开奖历史记录表中获取开奖数据
            if (isset($recentIssue['issue_id']) && !empty($recentIssue['issue_id']) && ($history = issues::getIssueHistory($recentIssue['issue_id']))) {
                //miss:最近10期的号码遗漏数据(定位) hot:最近十期连续出现次数
                $data['json_missHot'] = array('miss' => unserialize($history['miss_info']), 'hot' => unserialize($history['hot_info']),);
            } else {
                $data['json_missHot'] = array();
            }
        } else {
            $openNums = config::getConfig('mmc_open_num', self::OPENNUMS);
            $data['open_max_num'] = $openNums;
            $data['json_openedIssues'] = $issues;
            $data['json_missHot'] = array();
        }

        //得到用户返点
        $userRebate = userRebates::getUserRebate($GLOBALS['SESSION']['user_id'], $lottery['property_id']);//当前用户的当前彩种类型的返点
        if (!$userRebate) $this->showMsg(7031, '此彩种您还没有分配返点，请联系上级或者客服');

        $redisKey = 'js_methods_' . $lottery_id . '_' . $group;
        $result = $GLOBALS['redis']->hGet(__FUNCTION__ . 'app', $redisKey);
        if (!$result) {
            $result = [];
            $methods = methods::getPlayMethodsC($lottery_id, 0, $group);//group 1:官方玩法,2:信用玩法
            $prizes = prizes::getItems(0, 0, 0, 0, 1);
//            $minRebateGaps = $lottery['min_rebate_gaps'];
//            $maxCombPrize = $data['maxCombPrize'];
//            $lottery_type = $lottery['lottery_type'];
//            $prizeRate = 1 - $lottery['total_profit'];
            foreach ($methods as $k => $v) {
                foreach ($v['childs'] as $kk => $vv) {
                    for ($i = 1; $i <= $vv['levels']; $i++) {
                        $methods[$k]['childs'][$kk]['prize'][$i] = $prizes[$methods[$k]['childs'][$kk]['method_id']][$i]['prize'];
                    }
                    $numlevel = methods::getMethodNumLevel($methods[$k]['childs'][$kk]['name']);
                    $methods[$k]['childs'][$kk]['num_level'] = empty($numlevel) ? '' : $numlevel;
                }

                $result[] = $methods[$k];
            }

//            $result = json_encode($result, JSON_UNESCAPED_UNICODE);
            $result = serialize($result);
            $GLOBALS['redis']->hSet(__FUNCTION__ . 'app', $redisKey, $result);
        }
        $lotterys = lottery::getAllItems('status = 8', 'lottery_id,cname');
        $data['lotterys'] = array_values($lotterys);

        // $data['methods'] = array_values(json_decode($result, true));

//        $result = json_decode($result, true);
        $data['methods'] = unserialize($result);
//        $data['methods'] = array_values(count($result) > 1 ? $result : current($result));

        $data['rebate'] = $userRebate;
        $data['modes'] = lottery::getModesItemsByCond(' AND status = 1', 'modes_name,modes_value');//因为测试规划 代码提交晚 暂时不用
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $data);
    }

    public function initCaiZhong()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $game_key = $this->request->getGet('game_key', 'trim');
        $tmp = preg_match('/^[g,x]$/i', $game_key);//官方与信用玩法标识
        if ($lottery_id <= 0 || !$tmp) $this->showMsg(8002, mobileErrorCode::SYS_GET_PARAM_ERR, []);
        if ($game_key === 'g') $this->_show($lottery_id, methodGroups::GROUP_OFFICIAL, $game_key);
        elseif ($game_key === 'x') $this->_show($lottery_id, methodGroups::GROUP_CREDIT, $game_key);
    }

    //参与游戏-玩法投注
    public function play()
    {
        $op = $this->request->getPost('op', 'trim');
        switch ($op) {
            case 'buy':
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                $tTime = $this->calTime($lotteryId);
                if ($tTime > 0) {
                    $this->showMsg(80000, '彩种处于休市中,不能下单');
                }
                $xgame = $this->request->getPost('xgame', 'intval', 0);
                $issue = $this->request->getPost('issue', 'trim');
                $curRebate = $this->request->getPost('curRebate', 'floatval');
                $modes = $this->request->getPost('modes', 'trim');
                $codeStr = $this->request->getPost('codes', 'trim');

                $multiple = $this->request->getPost('multiple', 'intval');
                $traceData = $this->request->getPost('traceData', 'array');
                $stopOnWin = $this->request->getPost('stopOnWin', 'intval', '0|1');
                $token = $this->request->getPost('token', 'trim');
                $result = array('errno' => 0, 'errstr' => '');
                $mmcResult = array('errno' => 0, 'errstr' => '');


                if ($GLOBALS['mc']->get('mc', $GLOBALS['SESSION']['user_id'] . 'buyToken') == $token) {
                    //表示可能由于某种原因的重复订单错误号
                    $this->showMsg(65534, '由于某种原因的重复订单错误号buy');
                } else {
                    $GLOBALS['mc']->set('mc', $GLOBALS['SESSION']['user_id'] . 'buyToken', $token, 600);
                }

                try {
                    $codes = game::getCodesType($codeStr);
                    if ($traceData) { //追号情况
                        if ($lotteryId == 15) {
                            throw new exception2('非法访问');
                        }
                        $trace_id = game::trace($lotteryId, $curRebate, $modes, $GLOBALS['SESSION']['user_id'], $codes, $traceData, $stopOnWin, $issue);
                        $wrap_id = traces::wrapId($trace_id, $issue, $lotteryId);
                    } else {
                        if ($lotteryId == 15) {
                            $openCounts = $this->request->getPost('openCounts', 'intval', 1);
                            $openNums = config::getConfig('mmc_open_num', self::OPENNUMS);
                            if ($openCounts > $openNums) $this->showMsg(7031, '连开次数大于最大限制,请重新选择');
                            $mmcResult['data'] = game::mmcBuy($curRebate, $modes, $codes, $multiple, $openCounts);
                            $wrap_id = '';
                        } else {
                            //中间有任何异常抛出
                            $package_id = game::buy($lotteryId, $issue, $curRebate, $modes, $GLOBALS['SESSION']['user_id'], $codes, $multiple, $xgame);
                            $wrap_id = projects::wrapId($package_id, $issue, $lotteryId);
                        }
                    }
                    $result['pkgnum'] = $wrap_id;
                } catch (exception2 $e) {

                    $this->showMsg($e->getCode(), $e->getMessage());

                }

                if ($lotteryId == 15) {
                    $this->showMsg(0, '', $mmcResult['data']);
                } else {
                    $this->showMsg(0, '', $result);
                }

                break;
            case 'xbuy':
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                $issue = $this->request->getPost('issue', 'trim');
                $curRebate = $this->request->getPost('curRebate', 'floatval');
                $modes = $this->request->getPost('modes', 'trim');
                $codeStr = $this->request->getPost('codes', 'trim');
                $multiple = $this->request->getPost('multiple', 'intval');
                $token = $this->request->getPost('token', 'trim');
                $result = array('errno' => 0, 'errstr' => '');
                $tTime = $this->calTime($lotteryId);
                if ($tTime > 0) {
                    $this->showMsg(80000, '彩种处于休市中,不能下单');
                }
                if ($GLOBALS['mc']->get('mc', $GLOBALS['SESSION']['user_id'] . 'buyToken') == $token) {
                    //表示可能由于某种原因的重复订单错误号
                    $this->showMsg(65534, '由于某种原因的重复订单错误号x');
                } else {
                    $GLOBALS['mc']->set('mc', $GLOBALS['SESSION']['user_id'] . 'buyToken', $token, 600);
                }
                //前台严格规定注单格式一注为一个方案，+后面必须为正整数,多注则为711:5+1|17+2|38+334|40+453|41+342|42+100
// $codeStr ='619:09+34,,,,|,08+30,,,|,,,07+90,|,,,06+90,|,,,05+90,|,,,04+90,|,,,03+90,|,,,02+90,|,,,01+90,';
                try {
                    $codes = game::getCodesType($codeStr);
                    //中间有任何异常抛出
                    $package_id = game::xbuy($lotteryId, $issue, $curRebate, $modes, $GLOBALS['SESSION']['user_id'], $codes);

                    $wrap_id = projects::wrapId($package_id, $issue, $lotteryId);
                    $result['pkgnum'] = $wrap_id;
                } catch (exception2 $e) {
                    $this->showMsg($e->getCode(), $e->getMessage());
                }
                $this->showMsg(0, '', $result);
                break;
//            case 'getTodayDrawList':
//                  <li class="width80px">期号</li>
//                  <li class="width60px">开奖号</li>
//                  <li class="width55px">前3和值</li>
//                  <li class="width55px">后3和值</li>
//                  <li class="width140px">大小单双</li>
//                  <li class="width55px">前3组态</li>
//                  <li class="width55px">后3组态</li>
//                if (!$lottery_id = $this->request->getPost('lottery_id', 'intval')) {
//                    die(json_encode(array('errno' => 1, 'errstr' => '参数错误')));
//                }
//
//                if (!$tmp = issues::getItems($lottery_id, date('Y-m-d'), 0, 0, 0, 0, 2, 'issue_id DESC')) {
//                    $tmp = issues::getItems($lottery_id, date('Y-m-d',strtotime('-1 day')), 0, 0, 0, 0, 2, 'issue_id DESC', 0, 1);
//                }
//                $issues = array();
//                foreach ($tmp as $v) {
//                    $issues[] = array(
//                        'issue_id' => $v['issue_id'],
//                        'issue' => $v['issue'],
//                        'code' => $v['code'],
//                    );
//                }
//                if ($issues) {
//                    $result = array('errno' => 0, 'errstr' => '', 'issues' => $issues);
//                }
//                else {
//                    $result = array('errno' => 999, 'errstr' => 'issue data error');
//                }
//                echo json_encode($result);
//                break;
            case 'getCurIssue':
                if (!$lottery_id = $this->request->getPost('lotteryId', 'intval')) $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
                if (!$issueInfo = issues::getCurrentIssue($lottery_id)) { //issues_mini表查询当日当前时间之前最新开奖数据
                    //issue表查询当前彩种最新的一条数据
                    if (!$issues = issues::getItems($lottery_id, '', 0, 0, 0, 0, 0, '', 0, 1)) $this->showMsg(7030, mobileErrorCode::ISSUES_DATA_ERROR);
                    $issueInfo = reset($issues);
                }
                $issueInfo = array(
                    'issue_id' => $issueInfo['issue_id'],
                    'issue' => $issueInfo['issue'],
                    'end_time' => $issueInfo['end_sale_time'],
                    'input_time' => $issueInfo['earliest_input_time'],
                );
                if ($lastIssueInfo = issues::getLastIssue($lottery_id, true)) {
                    $lastIssueInfo = array(
                        'issue_id' => $lastIssueInfo['issue_id'],
                        'end_time' => $lastIssueInfo['end_sale_time'],
                        'issue' => $lastIssueInfo['issue'],
                        'code' => $lastIssueInfo['code'],
                        'original_code' => $lastIssueInfo['original_code'],
                    );
                }
                $lastOpenIssueInfo = issues::getLastOpenIssue($lottery_id);
                $kTime = $this->calTime($lottery_id);
                $result = array(
                    'kTime' => $kTime,

                    'issueInfo' => $issueInfo,
                    'lastIssueInfo' => $lastIssueInfo,
                    'lastOpenIssueInfo' => $lastOpenIssueInfo,
                    'serverTime' => date('Y-m-d H:i:s')
                );
                $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $result);
                break;
            case 'getLastIssueCode': //得到上一期的开奖结果，还没开出来返回空
                if (!$lottery_id = $this->request->getPost('lotteryId', 'intval')) {
                    $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
                }
                if (!$issue = $this->request->getPost('issue', 'trim')) {
                    $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
                }

                if ($issueInfo = issues::getItem(0, $issue, $lottery_id)) {
                    $kTime = $this->calTime($lottery_id);
                    //{issue_id: '11444', issue:'20130131-080', 'end_time': '2013/01/31 19:18:30', 'input_time': '2013/01/31 19:20:30'}
                    if ($issueInfo['status_code'] == 2) {
                        $issueInfo = array(
                            'issue_id' => $issueInfo['issue_id'],
                            'issue' => $issueInfo['issue'],
                            'code' => $issueInfo['code'],
                            'original_code' => $issueInfo['original_code'],
                        );
                        $result = array('kTime' => $kTime, 'issueInfo' => $issueInfo, 'serverTime' => date('Y-m-d H:i:s'));
                    } else {
                        $result = array('kTime' => $kTime, 'issueInfo' => array(), 'serverTime' => date('Y-m-d H:i:s'));
                    }
                } else {
                    $this->showMsg(7030, mobileErrorCode::ISSUES_DATA_ERROR);
                }

                $this->showMsg(0, '', $result);
                break;
            case 'getLastOpenIssue': //得到最近已开奖的奖期
                if (!$lotteryId = $this->request->getPost('lotteryId', 'intval')) {
                    $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
                }
                if ($row = issues::getLastOpenIssue($lotteryId)) {
                    $this->showMsg(0, '', $row);
                }


                break;
            case 'getTracePage':   //显示追号页内容
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                $mids = $this->request->getPost('mids', 'trim');
                if (!$lotteryId || !$mids) {
                    $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
                }

                //如果可以利润率追号，返回该玩法奖金
                $mids = array_unique(explode(',', $mids));
                $prize = 0;
                if (count($mids) == 1) {
                    if ($prizes = prizes::getItems($lotteryId, reset($mids), 0, 0, 1)) {
                        $prizeInfo = @reset(reset($prizes));
                        ksort($prizeInfo);
                        $prize = round($prizeInfo['prize'], 4);
                    }
                }
                if (!$prize_limit = config::getConfig('prize_limit')) { //任一期追号奖金不能超过此值 0为不限红
                    $prize_limit = 9999999;
                }

                $issue_date = date('Y-m-d');
                if (time() >= strtotime(date('Y-m-d 00:00:00')) && time() <= strtotime(date('Y-m-d 02:00:00'))) {
                    $issue_date = array(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'));
                }

                //可追号奖期肯定是还没开奖的，因此加status_code=0没错 这样可以利用上
                if (!$issues = issues::getItems($lotteryId, $issue_date, 0, 0, time(), 0, 0, '', 0, self::MAX_TRACE_ISSUE_NUM)) {
                    $this->showMsg(7017, mobileErrorCode::USER_NONE_TRACE_NUM);
                }
                $issues2 = array();
                foreach ($issues as $v) {
                    $issues2[] = $v['issue'];
                }
                $this->showMsg(0, '', ['issues' => $issues2, 'prize' => $prize, 'prizeLimit' => $prize_limit]);
                break;
            case 'getCurContextIssues'://得到包括当前期前后5期
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                //得到前面2期
                if (!$tmp1 = issues::getItems($lotteryId, '', 0, 0, 0, time(), -1, 'issue_id DESC', 0, 2)) {
                    $this->showMsg(7030, mobileErrorCode::ISSUES_DATA_ERROR);
                }
                //得到当前期
                $tmp2 = issues::getCurrentIssue($lotteryId);
                //得到后面2期
                if (!$tmp3 = issues::getItems($lotteryId, '', time(), 0, 0, 0, 0, '', 0, 2)) {
                    $this->showMsg(7030, mobileErrorCode::ISSUES_DATA_ERROR);
                }
                $issues = array_merge($tmp1, array($tmp2), $tmp3);
                if (count($issues) != 5) {
                    $this->showMsg(7030, mobileErrorCode::ISSUES_DATA_ERROR);
                }
                $issues = array_spec_key($issues, 'issue');
                ksort($issues);
                $issueInfos = array();
                foreach ($issues as $v) {
                    $issueInfos[] = array(
                        'issue_id' => $v['issue_id'],
                        'issue' => $v['issue'],
                    );
                }
                $this->showMsg(0, '', $issueInfos);
                break;
            case 'getBuyRecords':   //得到某一期的投注记录 用于前台右上角显示 玩法类型     投注内容    倍数  金额  状态  奖金
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                $issue = $this->request->getPost('issue', 'trim');

                $this->showMsg(0, '', $this->comGetBuyRecords($lotteryId, $issue));
                break;
            case 'getPrizeRank': //得到中奖排行榜
                $start_time = $this->request->getPost('start_time', 'trim', date('Y-m-d H:i:s', strtotime('-2 days')));
                $end_time = $this->request->getPost('end_time', 'trim', date('Y-m-d H:i:s'));
                $lotteryId = $this->request->getPost('lotteryId', 'trim');

                $prizeList = array();
                $result = array('errno' => 0, 'errstr' => '', 'data' => '');
                if ($prizeList = projects::getMaxPrizeList($start_time, $end_time, 0, 10)) {
                    //取得用户名
                    //$users = users::getItemsById(array_keys($prizeList));
                    $uid_arr = array();
                    foreach ($prizeList as $value) {
                        $uid_arr[] = $value['user_id'];
                    }
                    $users = users::getItemsById($uid_arr);
                    foreach ($prizeList as $k => &$v) {
                        $v['nick_name'] = substr($users[$v['user_id']]['username'], 0, 2) . '***' . substr($users[$v['user_id']]['username'], -2, 2);
                    }
                    $result['data'] = $prizeList;
                    $this->showMsg(0, '', $prizeList);
                } else {
                    $this->showMsg(1, '');
                }
        }

        exit;
    }

    public function comGetBuyRecords($lotteryId, $issue)
    {
        $projects = array();
        if ($tmp = projects::getItems($lotteryId, $issue, 0, $GLOBALS['SESSION']['user_id'], date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'), 0, 50)) {
            $packageIds = array_keys(array_spec_key($tmp, 'package_id'));
            $packages = projects::getPackagesById($packageIds);
            $methods = methods::getItemsNew(['method_id','cname']);
            $prizeStatus = '';
            foreach ($tmp as $v) {
                if ($packages[$v['package_id']]['cancel_status'] > 0) {
                    switch ($packages[$v['package_id']]['cancel_status']) {
                        case '1':
                            $prizeStatus = '用户撤单';
                            break;
                        case '2':
                            $prizeStatus = '追中撤单';
                            break;
                        case '3':
                            $prizeStatus = '出号撤单';
                            break;
                        case '4':
                            $prizeStatus = '未开撤单';
                            break;
                        case '9':
                            $prizeStatus = '系统撤单';
                            break;
                    }
                } elseif ($packages[$v['package_id']]['check_prize_status'] == 0) {
                    $prizeStatus = '未开奖';
                } elseif ($packages[$v['package_id']]['check_prize_status'] == 1) {
                    $prizeStatus = '已中奖';
                } elseif ($packages[$v['package_id']]['check_prize_status'] == 2) {
                    $prizeStatus = '未中奖';
                }
                $projects[] = array(
                    'prjID' => $v['project_id'],
                    'wrapId' => projects::wrapId($v['package_id'], $v['issue'], $v['lottery_id']),
                    'methodName' => $methods[$v['method_id']]['cname'],
                    'issue' => $v['issue'],
                    'multiple' => $v['multiple'],
                    'amount' => $v['amount'],
                    'prizeStatus' => $prizeStatus,
                    'prize' => $v['prize'],
                );
            }
        }
        return $projects;
        // $result = array('errno' => 0, 'errstr' => '', 'prj' => $projects);
    }


    /**
     * 投注列表(合并了追号单的方法)
     * @author nyjah 2016年1月21日
     * @return json
     */
    public function packageList2()
    {
        $check_prize_status = $this->request->getGet('type', 'intval', -1);
        if ($check_prize_status == 3) $this->traceList();
        list($user_id, $user) = $this->chkUser(0);
        list($start_time, $end_time) = $this->searchDate2(1, 3);
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $include_childs = 0;
        //得到所有彩种
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname'], 0, -1);
        //添加对应的默认值
        if ($check_prize_status == 65535) $cancel_status = 65535;
        elseif ($check_prize_status == 0) $cancel_status = 0;
        else $cancel_status = -1;
        $select = 'a.package_id,a.lottery_id,a.issue,a.xgame,a.create_time,a.amount,a.cancel_status,a.check_prize_status,a.trace_id,c.status';
        $orderBy = 'a.create_time DESC';
        $packages = projects::new_getPackages($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, $user_id, $include_childs, $start_time, $end_time, $cancel_status, $orderBy, -1, -1, $select, 1);
        $trace_ids = [];
        $page = $totalPages = 1;
        $totals = 0;
        if ($packages) {
            $nowTime = time();
            foreach ($packages as $key => &$item) {
                if (!empty($item['trace_id'])) {
                    if (in_array($item['trace_id'], $trace_ids)) {
                        unset($packages[$key]);
                        continue;
                    } else {
                        $trace_ids[] = $item['trace_id'];
                    }
                    $item['wrap_id'] = traces::wrapId($item['trace_id'], $item['issue'], $item['lottery_id']);
                    $item['is_trace'] = 1;
                } else {
                    $item['wrap_id'] = projects::wrapId($item['package_id'], $item['issue'], $item['lottery_id']);
                    $item['is_trace'] = 0;
                }
                $item['show_name'] = $lotterys[$item['lottery_id']]['cname'] . ($item['xgame'] ? '(信)' : '');
                $tmp = $item['create_time'];
                $tmp1 = $item['amount'];
                unset($item['create_time'], $item['amount']);
                $item['create_time'] = date("Y/m/d", strtotime($tmp));
                $item['amount'] = number_format($tmp1, 4);
                $show_cancel = 0;
                if ($item['lottery_id'] != 15) {
                    $issueInfo = issues::getItem(0, $item['issue'], $item['lottery_id']);
                    if ($item['cancel_status'] == 0 && $nowTime < strtotime($issueInfo['cannel_deadline_time'])) $show_cancel = 1;
                }
                $show_status = '异常';
                $o_status = -1;
                if ($item['is_trace'] && $item['status'] !== null) {
                    if ($item['status'] == 0) {
                        $show_status = '未开始';
                        $o_status = 8;
                    } elseif ($item['status'] == 1) {
                        $show_status = '进行中';
                        $o_status = 9;
                    } elseif ($item['status'] == 2) {
                        $show_status = '已完成';
                        $o_status = 10;
                    } elseif ($item['status'] == 3) {
                        $show_status = '已取消';
                        $o_status = 11;
                    } else {
                        $show_status = '异常';
                        $o_status = -1;
                    }
                } else {
                    if ($item['cancel_status'] == 0) {
                        if ($item['check_prize_status'] == 0) {
                            $o_status = 0;
                            $show_status = '未开奖';
                        } elseif ($item['check_prize_status'] == 1) {
                            $o_status = 1;
                            $show_status = '已中奖';
                        } else {
                            $o_status = 2;
                            $show_status = '未中奖';
                        }
                    } else { //0未撤单 1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
                        if ($item['cancel_status'] == 1) {
                            $o_status = 3;
                            $show_status = '个人撤单';
                        } elseif ($item['cancel_status'] == 2) {
                            $o_status = 4;
                            $show_status = '追中撤单';
                        } elseif ($item['cancel_status'] == 3) {
                            $o_status = 5;
                            $show_status = '出号撤单';
                        } elseif ($item['cancel_status'] == 4) {
                            $o_status = 6;
                            $show_status = '未开撤单';
                        } elseif ($item['cancel_status'] == 9) {
                            $o_status = 7;
                            $show_status = '系统撤单';
                        }
                    }
                }
                $item['show_status'] = $show_status;
                $item['status'] = $o_status;//-1:异常 0:未开奖 1:已中奖 2:未中奖 3:个人撤单 4:追中撤单 5:出号撤单 6:未开撤单 7:系统撤单
                $item['can_cancel'] = $show_cancel;
                unset($item['package_id'], $item['lottery_id'], $item['issue'], $item['xgame'], $item['cancel_status'], $item['check_prize_status']);
            }
            unset($item);
            $count = count($packages);
            list($page, $totalPages, $totals, $startPos, $limit) = $this->getPageTool($count);
            $packagelist = array_values(array_slice($packages, $startPos, $limit));
        }
        $data['pageTool']['page'] = $page;
        $data['pageTool']['totalPages'] = $totalPages;
        $data['pageTool']['count'] = $totals;
        $data['show_datas'] = !empty($packagelist) ? array_values($packagelist) : '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $data);
    }

    private function pageReturnNull()
    {
        $data['pageTool']['page'] = 1;
        $data['pageTool']['totalPages'] = 1;
        $data['pageTool']['count'] = 0;
        $data['show_datas'] = '';
        return $data;
    }

    /**
     * 投注列表
     * @author nyjah 2016年1月21日
     * @return json
     */
    public function packageList()
    {
        $check_prize_status = $this->request->getGet('type', 'intval', -1);
        $is_trace = -1;
        if ($check_prize_status == 3) {
            /**原追号订单列表展示追号记录列表**/
            $this->traceList();
            /**********************/
//            $check_prize_status=-1;
//            $is_trace=1;
        } elseif (in_array($check_prize_status, [0, 1])) {
            $is_trace = 0;
        }
        list($user_id, $user) = $this->chkUser(0);
        list($start_time, $end_time) = $this->searchDate2(1, 3);
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $include_childs = 0;
        //得到所有彩种
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname'], 0, -1);
//        $packagesTotal = ['total_profit' => 0, 'total_prize' => 0, 'total_amount' => 0];
        //添加对应的默认值
        if ($check_prize_status == 65535) $cancel_status = 65535;
        elseif ($check_prize_status == 0) $cancel_status = 0;
        else $cancel_status = -1;
//        $packagesNumber = projects::getPackagesNumber($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', -1, 0, $user_id, $include_childs, $start_time, $end_time, $cancel_status);
        $trace_id = $is_trace == 1 ? -255 : $is_trace;
//        $packagesTotal = projects::getPackageTotal($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', $trace_id, 0, $user_id, $include_childs, $start_time, $end_time, '', '', $cancel_status);

        $orderBy = 'a.create_time DESC';
        $count = projects::new_getPackageTotals($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, $user_id, $include_childs, $start_time, $end_time, $cancel_status, $orderBy, $is_trace);

        if (empty($count) || $count['count'] == 0) $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $this->pageReturnNull());
        list($page, $totalPages, $totals, $startPos, $limit) = $this->getPageTool($count['count']);
        $select = 'a.package_id,a.lottery_id,a.issue,a.xgame,a.create_time,a.amount,a.prize,a.cancel_status,a.check_prize_status';
        $orderBy = 'a.create_time DESC';
        $packages = projects::new_getPackages($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, $user_id, $include_childs, $start_time, $end_time, $cancel_status, $orderBy, $startPos, $limit, $select, $is_trace);
        //为算出奖金，先得到这些用户的返点
        $realAmount = $totalAmount = $totalPrize = $totalProfit = 0;
        if ($packages) {
            $nowTime = time();
            foreach ($packages as &$item) {
                $totalAmount += $item['amount'];
                $item['wrap_id'] = projects::wrapId($item['package_id'], $item['issue'], $item['lottery_id']);
                $item['show_name'] = $lotterys[$item['lottery_id']]['cname'] . ($item['xgame'] ? '(信)' : '');
                $show_cancel = 0;
                if ($item['lottery_id'] != 15) {
                    $issueInfo = issues::getItem(0, $item['issue'], $item['lottery_id']);
                    if (!empty($issueInfo) && isset($issueInfo['cannel_deadline_time'])) {
                        if ($item['cancel_status'] == 0 && $nowTime < strtotime($issueInfo['cannel_deadline_time'])) $show_cancel = 1;
                    }
                }
                $show_status = '异常';
                $o_status = -1;
                if ($item['cancel_status'] == 0) {
                    $realAmount += $item['amount'];
                    if ($item['check_prize_status'] == 0) {
                        $o_status = 0;
                        $show_status = '未开奖';
                    } elseif ($item['check_prize_status'] == 1) {
                        $totalPrize += $item['prize'];
                        $o_status = 1;
                        $show_status = '已中奖';
                    } else {
                        $o_status = 2;
                        $show_status = '未中奖';
                    }
                } else { //0未撤单 1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
                    if ($item['cancel_status'] == 1) {
                        $o_status = 3;
                        $show_status = '个人撤单';
                    } elseif ($item['cancel_status'] == 2) {
                        $o_status = 4;
                        $show_status = '追中撤单';
                    } elseif ($item['cancel_status'] == 3) {
                        $o_status = 5;
                        $show_status = '出号撤单';
                    } elseif ($item['cancel_status'] == 4) {
                        $o_status = 6;
                        $show_status = '未开撤单';
                    } elseif ($item['cancel_status'] == 9) {
                        $o_status = 7;
                        $show_status = '系统撤单';
                    }
                }
                if ($item['cancel_status'] <= 0 && $item['check_prize_status'] != 0) {
                    $profit = $item['prize'] - $item['amount'];
                    $totalProfit += $profit;
                }
                $item['show_status'] = $show_status;
                $item['status'] = $o_status;//-1:异常 0:未开奖 1:已中奖 2:未中奖 3:个人撤单 4:追中撤单 5:出号撤单 6:未开撤单 7:系统撤单
                $item['can_cancel'] = $show_cancel;
                $tmp = $item['create_time'];
                $tmp1 = $item['amount'];
                unset($item['create_time'], $item['amount']);
                $item['create_time'] = date("Y/m/d", strtotime($tmp));
                $item['amount'] = number_format($tmp1, 4);
                unset($item['package_id'], $item['xgame'], $item['cancel_status'], $item['check_prize_status']);
            }
            unset($item);
        }
//        $count = [
//            'totals' => $packagesTotal,
//            'page' => [
//                'page_count' => count($packages),
//                'page_prize' => $totalPrize,
//                'page_amount' => $totalAmount,
//                'page_real_amount' => $realAmount,
//                'page_profit' => $totalProfit,
//            ]
//        ];
        $data['pageTool']['page'] = $page;
        $data['pageTool']['totalPages'] = $totalPages;
        $data['pageTool']['count'] = $totals;
//        $data['count'] = $count;
        $data['selfWinReport'] =$this->selfWinReport($user_id,$start_time,$end_time);
        $data['show_datas'] = !empty($packages) ? array_values($packages) : '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $data);
    }

    /**
     * 追号列表
     * @author nyjah 2016年1月20日
     * @return json
     */
    public function traceList()
    {
        list($user_id, $user) = $this->chkUser(0);
        list($start_time, $end_time) = $this->searchDate2(1, 3);
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
//        $status = $this->request->getGet('status', 'intval', -1);
        $status = -1;
        //得到所有彩种
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname'], 0, -1);
        $issue = '';
        $modes = 0;
        $is_test = -1;
        $include_childs = 0;
        $tracesNumber = traces::getItemsNumber($lottery_id, $is_test, $issue, $modes, $user_id, $include_childs, $start_time, $end_time, $status);
        list($page, $totalPages, $totals, $startPos, $limit) = $this->getPageTool($tracesNumber);
        $traces = traces::getItems($lottery_id, $is_test, $issue, $modes, $user_id, $include_childs, $start_time, $end_time, $startPos, $limit, $status);
//        $packagesTotal = projects::getTracePackageTotal($lottery_id, $GLOBALS['SESSION']['user_id'], $status, -1, -1, '', -255, -1, 0, 0, $start_time, $end_time);
        $show_data = [];
        $realAmount = $totalAmount = $totalPrize = $totalProfit = 0;
        if ($traces) {
            //是否显示撤单
            $nowTime = time();
            foreach ($traces as $item) {
                $totalAmount += $item['total_amount'];
                $item['profit'] = 0;
                $data = [];
                $data['issue'] = $item['start_issue'];
                $data['wrap_id'] = traces::wrapId($item['trace_id'], $item['start_issue'], $item['lottery_id']);
                $data['show_name'] = $lotterys[$item['lottery_id']]['cname'];
                $data['create_time'] = date('Y/m/d', strtotime($item['create_time']));
                $data['amount'] = $item['total_amount'];  //是否显示撤单
                switch ($item['status']) { //追号状态(0:未开始 1:正在进行;2:已完成;3:已取消)
                    case 0:
                        $data['show_status'] = '未开始';
                        $data['status'] = 8;
                        break;
                    case 1:
                        $data['show_status'] = '进行中';
                        $data['status'] = 9;
                        break;
                    case 2:
                        $data['show_status'] = '已完成';
                        $data['status'] = 10;
                        break;
                    case 3:
                        $data['show_status'] = '已取消';
                        $data['status'] = 11;
                        break;
                    default:
                        $data['show_status'] = '异常';
                        $data['status'] = -1;
                }
                $data['can_cancel'] = 0;  //是否显示撤单
                if ($packages = projects::getPackages($lottery_id, -1, -1, '', $item['trace_id'])) {
                    foreach ($packages as $package) {
                        $issueInfo = issues::getItem(0, $package['issue'], $package['lottery_id']);
                        if ($package['cancel_status'] == 0 && $nowTime < strtotime($issueInfo['cannel_deadline_time'])) {
                            $data['can_cancel'] = 1;
                        }
                        if ($package['cancel_status'] == 0) {
                            $realAmount += $package['amount'];
                        }
                        if ($package['check_prize_status'] == 1) $totalPrize += $package['prize'];
                        if ($package['cancel_status'] == 0 && $package['check_prize_status'] > 0) {
                            $item['profit'] += $package['prize'] - $package['amount'];
                        }
                    }
                }
                $totalProfit += $item['profit'];
                $show_data[] = $data;
            }
        }
        $datas = [];
        $datas['pageTool']['page'] = $page;
        $datas['pageTool']['totalPages'] = $totalPages;
        $datas['pageTool']['count'] = $totals;

//        $datas['count'] = [
//            'totals' => array_merge(['total_count' => $tracesNumber], $packagesTotal),
//            'page' => [
//                'page_count' => count($show_data),
//                'page_prize' => $totalPrize,
//                'page_amount' => $totalAmount,
//                'page_real_amount' => $realAmount,
//                'page_profit' => $totalProfit,
//            ]
//        ];
        $datas['selfWinReport'] =$this->selfWinReport($user_id,$start_time,$end_time);
        $datas['show_datas'] = !empty($show_data) ? array_values($show_data) : '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $datas);
    }

    public function packageDetail()
    {
        list($user_id, $user) = $this->chkUser(0);
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        $type = $this->request->getGet('type', 'intval', 0);
        /*******************原追号订单列表是使用trace追号记录编号查询一对多******************/
        if ($type == 1) $this->traceDetail($wrap_id);//
        /********************************************************************************/
        if (!$package_id = projects::dewrapId($wrap_id)) $this->showMsg(7026, '订单编号无效');
        if (!$package = projects::getPackage($package_id)) $this->showMsg(7026, '找不到该订单');
        $lottery=lottery::getItem($package['lottery_id']);
        $package['lottery_status']=$lottery['status']==8?1:0;
        $package['wrap_id'] = $wrap_id;
        $isSelf = true;
        if ($package['user_id'] != $user_id) {
            $isSelf = false;
            //核实是否是其上级 上级有权查看下级订单 否则不准查看
            $parents = users::getAllParent($package['user_id']);
            if (!isset($parents[$user_id])) $this->showMsg(7002, '您无权查看该订单');
        }
        //得到是谁投的
        if (!$user = users::getItem($package['user_id'])) $this->showMsg(6003, "非法请求，该用户不存在或已被冻结");
        //如果是追号单，跳转至
//        if ($package['trace_id']) $this->traceDetail(traces::wrapId($package['trace_id'], $package['issue'], $package['lottery_id']),$package);

        $package['show_cancel'] = 0;
        if ($isSelf && $package['lottery_id'] != 15) {
            $issueInfo = issues::getItem(0, $package['issue'], $package['lottery_id']);
            if ($package['cancel_status'] == 0 && time() < strtotime($issueInfo['cannel_deadline_time'])) $package['show_cancel'] = 1;
        }
        //得到当前彩种
        $lottery = lottery::getItem($package['lottery_id']);
//        //得到奖金系列
//        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        //得到开奖号码
        $openCodes = issues::getCodes($package['lottery_id'], array($package['issue']));
        $openCode = '';
        if (isset($openCodes[$package['issue']])) {
            $openCode = $openCodes[$package['issue']];
            if ($lottery['property_id'] == 4) {
                $suit_arr = array(
//                    's' => 'poker_heit',
//                    'h' => 'poker_hongt',
//                    'c' => 'poker_meih',
//                    'd' => 'poker_fangk',
                    's' => '黑桃',
                    'h' => '红桃',
                    'c' => '梅花',
                    'd' => '方块'
                );
                $partys = explode(' ', $openCode);
                $openCode = array();
                foreach ($partys as $v) $openCode[] = array('num' => str_replace('T', '10', $v[0]), 'suit' => $suit_arr[$v[1]]);
            }
        }
        //订单详情
        $projects = projects::getItems(0, '', $package_id);
        //先得到用户返点
        $userRebate = userRebates::getUserRebate($user_id, $lottery['property_id']);
        if ($userRebate)
            $prizes = prizes::getItems($package['lottery_id'], 0, 0, 0, 1);
        foreach ($projects as $k => $v) {
            if ($v['lottery_id'] == 14) $projects[$k]['code'] = str_replace('T', '10', $v['code']);
            //计算如果中奖的奖金
            if ($v['cur_rebate'] > $userRebate) $this->showMsg(6005, mobileErrorCode::SYS_ERROR);
            $projects[$k]['will_prize'] = number_format($v['multiple'] * $v['modes'] * $prizes[$v['method_id']][1]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
        }
        //得到彩种玩法组玩法二级联动
        $methods = methods::getItemsNew(['method_id', 'name', 'cname']);
        $show_datas = $this->_handle_packageDetail_return($wrap_id, $lottery, $package, $openCode, $projects, $methods);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $show_datas);
    }

    public function _handle_packageDetail_return($wrap_id, $lottery, $package, $openCode, $projects, $methods)
    {
        $show_data = [];
        $show_data['show_type'] = 0;
//        $show_data['is_trace'] = 0;
        $show_data['wrap_id'] = $wrap_id;
        $show_data['lottery_status'] = $package['lottery_status'];
        $show_data['package_id'] = $package['package_id'];
        $show_data['lottery_id'] = $lottery['lottery_id'];//彩种id,根据此找logo
        $show_data['lottery_name'] = $lottery['cname'];//彩种
        $show_data['award_period'] = $package['issue'];//奖期
        $modes = M('lottery_modes')->where(['status'=>1])->select();
        $show_data['betting_mode'] = '';
          foreach ($modes as $k =>$v)
          {
              if( floatval($package['modes']) == $v['modes_value'])
              {
                  $show_data['betting_mode'] = $v['modes_name'];
              }
          }

        $show_data['betting_factor'] = $package['multiple'];//投注倍数
        $show_data['single_number'] = $package['single_num'];//单倍注数

        $show_data['is_trace'] = $package['trace_id'] > 0 ? 1 : 0;//是否追号
        $show_data['is_xgame'] = $package['xgame'];//是否追号
        if ($lottery['lottery_id'] == 14) {
            $winning_nums = [];
            if (!empty($openCode) && is_array($openCode)) {
                foreach ($openCode as $p) $winning_nums[] = $p['suit'] . $p['num'];
            }
            $winning_nums = implode(' , ', $winning_nums);
        } else {
            $winning_nums = isset($openCode) ? $openCode : '';
        }
        $show_data['winning_nums'] = $winning_nums;//开奖号码
        $show_data['create_time'] = date('Y-m-d H:i', strtotime($package['create_time']));//投注时间
        $show_data['total_amount'] = $package['amount'];//总金额
        $show_data['winning_amount'] = $package['prize'];//中奖金额
        $status = '异常';
        $o_status = -1;
        if ($package['cancel_status'] == 0) {
            if ($package['check_prize_status'] == 0) {
                $status = '未开奖';
                $o_status = 0;
            } elseif ($package['check_prize_status'] == 1) {
                $status = '已中奖';
                $o_status = 1;
            } else {
                $status = '未中奖';
                $o_status = 2;
            }
        } else {
            if ($package['cancel_status'] == 1) {
                $status = '个人撤单';
                $o_status = 3;
            } elseif ($package['cancel_status'] == 2) {
                $status = '追中撤单';
                $o_status = 4;
            } elseif ($package['cancel_status'] == 3) {
                $status = '出号撤单';
                $o_status = 5;
            } elseif ($package['cancel_status'] == 4) {
                $status = '未开撤单';
                $o_status = 6;
            } elseif ($package['cancel_status'] == 9) {
                $status = '系统撤单';
                $o_status = 7;
            }
        }
        $show_data['show_status'] = $status;//开奖号码
        $show_data['status'] = $o_status;//-1:异常 0:未开奖 1:已中奖 2:未中奖 3:个人撤单 4:追中撤单 5:出号撤单 6:未开撤单 7:系统撤单 8:追号未开始 9:追号进行中 10:追号已完成 11:追号已取消
        $rebate = '0.0000';
        $profit_loss = '0.0000';
        if (!!config::getConfig('rebate_toself', 1)) {
            if ($package['cancel_status'] == 0) {
                $rebate = (string)number_format($package['cur_rebate'] * $package['amount'], 4);
                $profit_loss = (string)number_format($package['cur_rebate'] * $package['amount'] + $package['prize'] - $package['amount'], 4);
            }
        } elseif ($package['cancel_status'] == 0) $profit_loss = number_format($package['prize'] - $package['amount'], 4);
        $show_data['rebate'] = $rebate;
        $show_data['profit_loss'] = $profit_loss;
        $show_data['can_cancel'] = $package['show_cancel'] ? 1 : 0;
        $orderDetail = [];
        foreach ($projects as $v) {
            $order = [];
            if (!isset($methods[$v['method_id']])) $this->showMsg(6005, mobileErrorCode::SYS_ERROR);
            $order['mid'] = isset($v['method_id']) ? $v['method_id'] : '';
            $order['game_name'] = isset($methods[$v['method_id']]['cname']) ? $methods[$v['method_id']]['cname'] : '';
            if ($methods[$v['method_id']]['name'] == 'JSSTTX') {
                $comment = ['111_222_333_444_555_666'];
            } elseif ($methods[$v['method_id']]['name'] == 'JSSLTX') {
                $comment = ['123_234_345_456'];
            } else {
                $v['code'] = str_replace("|", "||", $v['code']);
                $v['code'] = str_replace(",", " | ", $v['code']);
                $comment = explode('||', $v['code']);
            }
            $order['comment'] = array_map(function ($a) {
                return ['code' => $a];
            }, $comment);
            $order['num'] = $v['single_num'];
            $order['amount'] = $v['amount'];
            $order['prize'] = $v['prize'];
            $orderDetail[] = $order;
        }
        $show_data['oreder_detail'] = !empty($orderDetail) ? $orderDetail : '';
        //排除秒秒彩撤单
        if ($package['lottery_id'] != 15) if ($package['show_cancel']) $show_data['can_cancel'] = 1;
        return $show_data;
    }

    //追号详情
    public function traceDetail($wrap_id, $package = '')
    {
        list($user_id, $user) = $this->chkUser(0);
        if (!$trace_id = traces::dewrapId($wrap_id)) $this->showMsg(7026, '订单编号无效');
        if (!$trace = traces::getItem($trace_id)) $this->showMsg(7026, '找不到该追号单');
        $trace['wrap_id'] = $wrap_id;
        $lottery=lottery::getItem($trace['lottery_id']);
        $trace['lottery_status']=$lottery['status']==8?1:0;
        if (!$packages = projects::getPackages($trace['lottery_id'], -1, -1, '', $trace['trace_id'], 0, '', 0, '', '', '', '', -1, 'package_id ASC')) $this->showMsg(7026, '找不到追号详细列表');
        if (!$package) $package = reset($packages);
        $isSelf = true;
        if ($package['user_id'] != $user_id) {
            $isSelf = false;
            //核实是否是其上级
            $parents = users::getAllParent($package['user_id']);
            if (!isset($parents[$user_id])) $this->showMsg(7002, '您无权查看该订单');
        }
        //得到所有彩种
        $lottery = lottery::getItem($trace['lottery_id']);
        //得到奖金系列
        //得到用户返点
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        $prizeMode = 2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $userRebate - $package['cur_rebate']);
        if ($lottery['lottery_id'] == 14) {
            //JYZ-283快乐扑克特殊处理：使用豹子11050代替转直注数,所以整体/24
            $prizeMode = floor($prizeMode / 24);
        }
        //得到奖期截止时间 以决定是否可以用户撤单
        $issues = array_keys(array_spec_key($packages, 'issue'));
        $issueInfos = issues::getItemsByIssue($trace['lottery_id'], $issues);
        //得到开奖号码列表
        $openCodes = issues::getCodes($trace['lottery_id'], $issues);
        if ($lottery['property_id'] == 4) {
            $suit_arr = array(
//                's' => 'poker_heit',
//                'h' => 'poker_hongt',
//                'c' => 'poker_meih',
//                'd' => 'poker_fangk',
                's' => '黑桃',
                'h' => '红桃',
                'c' => '梅花',
                'd' => '方块'
            );
            foreach ($openCodes as $k => $v) {
                $partys = explode(' ', $v);
                $code = array();
                foreach ($partys as $poker) $code[] = array('num' => str_replace('T', '10', $poker[0]), 'suit' => $suit_arr[$poker[1]]);
                $openCodes[$k] = $code;
            }
        }

        //订单详情
        $projects = projects::getItems(0, '', $package['package_id']);
        $prizes = prizes::getItems($package['lottery_id'], 0, 0, 0, 1);
        $klpkLevels = array("同花顺包选" => 1, "豹子包选" => 2, "顺子包选" => 3, "同花包选" => 4, "对子包选" => 5);

        foreach ($projects as $k => $v) {
            $prizeLevel = 1;
            $codeLevels = array();
            if ($v['lottery_id'] == 14) {
                $projects[$k]['code'] = str_replace('T', '10', $v['code']);
                if ($v['method_id'] == 386) {//如果是包选
                    $codes = explode("|", $v['code']);
                    if($codes[0]==$v['code']){
                        $codes = explode("_", $v['code']);
                    }
                    foreach ($codes as $code) {
                        $code = trim($code);
                        $codeLevels[] = $klpkLevels[$code];
                    }
                    sort($codeLevels);
                    $prizeLevel = $codeLevels[0];
                }
            }
            //计算如果中奖的奖金
            if ($v['cur_rebate'] > $userRebate) $this->showMsg(6005, mobileErrorCode::SYS_ERROR);
            $projects[$k]['will_prize'] = number_format($v['modes'] * $prizes[$v['method_id']][$prizeLevel]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
        }

        //得到彩种玩法组玩法
        $methods = methods::getItemsNew(['method_id', 'cname']);
        $issue = $package['issue'];
        $order_id = projects::wrapId($package['package_id'], $issue, $package['lottery_id']);
        $show_datas = $this->_handle_traceDetail_return($order_id, $issue, $isSelf, $issueInfos, $wrap_id, $lottery, $trace, $package, $prizeMode, $projects, $methods, $packages, $openCodes);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $show_datas);
    }

    private function _handle_traceDetail_return($order_id, $issue, $isSelf, $issueInfos, $wrap_id, $lottery, $trace, $package, $prizeMode, $projects, $methods, $packages, $openCodes)
    {
        $show_data = [];
        $show_data['show_type'] = 1;
        $show_data['is_trace'] = 1;
        $show_data['is_xgame'] = $package['xgame'];//是否追号
        $show_data['trace_no'] = $wrap_id;
        $show_data['wrap_id'] = $order_id;
        $show_data['lottery_status'] = $trace['lottery_status'];
        $show_data['lottery_id'] = $lottery['lottery_id'];//彩种id,根据此找logo
        $show_data['lottery_name'] = $lottery['cname'];//彩种
        $show_data['award_period'] = $issue;//奖期
        $modes = M('lottery_modes')->where(['status'=>1])->select();
        $show_data['betting_mode'] = '';
        foreach ($modes as $k =>$v)
        {
            if( floatval($package['modes']) == $v['modes_value'])
            {
                $show_data['betting_mode'] = $v['modes_name'];
            }
        }
        $show_data['betting_factor'] = $package['multiple'];//投注倍数
        $show_data['single_number'] = $trace['single_num'];//单倍注数
        $show_data['total_chase_number'] = $trace['total_multiple'];//总追号倍数
        $show_data['plan_number'] = $trace['trace_times'];//计划追号期数
        $show_data['plan_total_amount'] = $trace['total_amount'];//计划总金额
        $show_data['prizeMode'] = $prizeMode;//奖金系列
        $show_data['is_stop_on_win'] = !empty($trace['stop_on_win']) ? 1 : 0;//中奖即停止
        if ($trace['status'] == 0) {
            $status = '未开始';
            $o_status = 8;
        } elseif ($trace['status'] == 1) {
            $status = '进行中';
            $o_status = 9;
        } elseif ($trace['status'] == 2) {
            $status = '已完成';
            $o_status = 10;
        } elseif ($trace['status'] == 3) {
            $status = '已取消';
            $o_status = 11;
        } else {
            $status = '异常';
            $o_status = -1;
        }
        $show_data['status'] = $o_status;//追号单状态
        $show_data['show_status'] = $status;//订单状态
        $show_data['create_time'] = date('Y-m-d H:i', strtotime($trace['create_time']));//投注时间
        $show_data['total_amount'] = $package['amount'];//总金额
        $show_data['winning_amount'] = $package['prize'];//中奖金额
        $rebate = '0.0000';
        $profit_loss = '0.0000';
        if (!!config::getConfig('rebate_toself', 1)) {
            if ($package['cancel_status'] == 0) {
                $rebate = (string)number_format($package['cur_rebate'] * $package['amount'], 4);
                $profit_loss = (string)number_format($package['cur_rebate'] * $package['amount'] + $package['prize'] - $package['amount'], 4);
            }
        } elseif ($package['cancel_status'] == 0) {
            $profit_loss = number_format($package['prize'] - $package['amount'], 4);
        }
        $show_data['rebate'] = $rebate;
        $show_data['profit_loss'] = $profit_loss;
        $orderDetail = [];
        foreach ($projects as $v) {
            $order = [];
            if (!isset($methods[$v['method_id']])) $this->showMsg(6005, mobileErrorCode::SYS_ERROR);
            $order['mid'] = isset($v['method_id']) ? $v['method_id'] : '';
            $order['game_name'] = isset($methods[$v['method_id']]['cname']) ? $methods[$v['method_id']]['cname'] : '';
            $v['code'] = str_replace("|", "||", $v['code']);
            $v['code'] = str_replace(",", " | ", $v['code']);
            $order['comment'] = array_map(function ($a) {
                return ['code' => $a];
            }, explode('||', $v['code']));
            $order['num'] = $v['single_num'];
            $order['amount'] = number_format($v['amount'] / $v['multiple'], 3);
            $order['prize'] = $v['will_prize'];
            $orderDetail[] = $order;
        }
        $show_data['oreder_detail'] = !empty($orderDetail) ? $orderDetail : '';
        $issueDetail = [];
        $nowTime = time();

        $total_prize = 0;
        $total_price = 0;
        $total_rebate = 0;
        $total_profit_loss = 0;
        foreach ($packages as $v) {
            $detail_data = [];
            $detail_data['package_id'] = $v['package_id'];
            $detail_data['chase_num'] = $v['issue'];//追号期号
            if (isset($openCodes[$v['issue']])) {
                if ($lottery['lottery_id'] == 14) {
                    $winning_nums = [];
                    foreach ($openCodes[$v['issue']] as $code) {
                        $winning_nums[] = $code['suit'] . $code['num'];
                    }
                    $winning_nums = implode(' , ', $winning_nums);
                } else {
                    $winning_nums = $openCodes[$v['issue']];
                }
            } else {
                $winning_nums = '';
            }
            $detail_data['winning_nums'] = $winning_nums;//开奖号码
            $detail_data['current_multiple'] = $v['multiple'];//当期倍数
            $detail_data['amount'] = $v['amount'];//投注金额
            $detail_data['prize'] = $v['prize'];//中奖金额

            $i_rebate = 0.0000;
            $i_profit_loss = 0.0000;
            if (!!config::getConfig('rebate_toself', 1)) {
                if ($v['cancel_status'] == 0) {
                    $i_rebate = $v['cur_rebate'] * $v['amount'];
                    $i_profit_loss = $v['cur_rebate'] * $v['amount'] + $v['prize'] - $v['amount'];
                }
            } elseif ($v['cancel_status'] == 0) {
                $i_profit_loss = $v['prize'] - $v['amount'];
            }
            if ($v['cancel_status'] > 0) {
                if ($v['cancel_status'] == 1) {
                    $status = '个人撤单';
                    $o_status = 3;
                } elseif ($v['cancel_status'] == 2) {
                    $status = '追中撤单';
                    $o_status = 4;
                } elseif ($v['cancel_status'] == 3) {
                    $status = '出号撤单';
                    $o_status = 5;
                } elseif ($v['cancel_status'] == 4) {
                    $status = '未开撤单';
                    $o_status = 6;
                } elseif ($v['cancel_status'] == 9) {
                    $status = '系统撤单';
                    $o_status = 7;
                }
            } else {
                $total_price += $v['amount'];
                $total_rebate += $i_rebate;
                $total_profit_loss += $i_profit_loss;
                if ($v['check_prize_status'] == 0) {
                    $status = '未开奖';
                    $o_status = 0;
                } elseif ($v['check_prize_status'] == 1) {
                    $status = '已中奖';
                    $o_status = 1;
                    $total_prize += $v['prize'];
                } else {
                    $status = '未中奖';
                    $o_status = 2;
                }
            }
            $detail_data['show_status'] = $status;//中奖金额
            $detail_data['status'] = $o_status;//中奖金额e_time'] > $nowTime && $v['cancel_status'] == 0);
//            p('===================================');
            if($trace['is_locked']==1){
                $detail_data['can_cancel']=0;
            }else{
                $detail_data['can_cancel'] = $isSelf && strtotime($issueInfos[$v['issue']]['end_sale_time']) > $nowTime && $v['cancel_status'] == 0 ? 1 : 0;//中奖金额
            }
//            $v['issue']==$issue?$current=$detail_data:$issueDetail[]=$detail_data;
            $issueDetail[] = $detail_data;
        }
//        if(isset($current))array_unshift($issueDetail, $current);
        $show_data['issue_detail'] = !empty($issueDetail) ? $issueDetail : '';
        $show_data['total_prize'] = $total_prize;
        $show_data['total_price'] = $total_price;
        $show_data['total_rebate'] = (string)number_format($total_rebate, 4);
        $show_data['total_profit_loss'] = (string)number_format($total_profit_loss, 4);
        return $show_data;
    }


    /**
     * 追号撤单
     */
    public function cancelTrace()
    {
        $wrap_id = $this->request->getPost('wrap_id', 'trim');
        $package_ids = $this->request->getPost('pkids', 'array');
        //>>查询追号订单是否存在
        if (!$trace_id = traces::dewrapId($wrap_id)) {
            $this->showMsg('6003', mobileErrorCode::REQUEST_PARAMS_ERROR);
        }

        //>>判断是否有传入订单号
        if (!is_array($package_ids) || !count($package_ids)) {
            $this->showMsg('6003', mobileErrorCode::REQUEST_PARAMS_ERROR);
        }
        try {
            //>>尝试执行撤单操作
            if (game::cancelTrace($GLOBALS['SESSION']['user_id'], $trace_id, $package_ids)) {
                $this->showMsg('0', mobileErrorCode::RETURN_SUCCESS, []);
            }
        } //>>抛出错误
        catch (exception2 $e) {
            $this->showMsg('6003', implode(',', $e->getMessages()));
        }
    }

    /**
     * author snow
     * 取消所有能取消的注单
     * @throws exception2
     */
    public function cancelAllTrace()
    {
        $wrap_id = $this->request->getPost('wrap_id', 'trim');
        //>>查询追号单是否存在
        if (!$trace_id = traces::dewrapId($wrap_id)) {
            $this->showMsg('6003', mobileErrorCode::REQUEST_PARAMS_ERROR);
        }
        $package_ids = [];
        $nowTime = time();
        //>>查询出追号单下所有订单
        $defaultOptions = [
            'lottery_id' => 0,
            'check_prize_status' => -1,
            'is_test' => -1,
            'issue' => '',
            'trace_id' => $trace_id,
            'field' => ['package_id', 'lottery_id', 'cancel_status','issue'],
        ];
        if ($aPackages = projects::getPackagesExclude($defaultOptions)) {
            foreach ($aPackages as $package) {
                if (!$issueInfo = issues::getItem(0, $package['issue'], $package['lottery_id'])) {
                    $this->showMsg('6003', '找不到奖期');

                }
                if ($nowTime > strtotime($issueInfo['cannel_deadline_time']) || $package['cancel_status'] > 0) {
                    continue;
                }
                $package_ids[] = $package['package_id'];
            }
        }
        if ($package_ids) {
            //>>尝试执行撤单操作
            try {
                if (game::cancelTrace($GLOBALS['SESSION']['user_id'], $trace_id, $package_ids)) {
                    $this->showMsg('0', mobileErrorCode::RETURN_SUCCESS, []);
                }
            } //>>抛出错误
            catch (exception2 $e) {
                $this->showMsg('8003', implode(',', $e->getMessages()));
            }
        }

        $this->showMsg('6003', mobileErrorCode::REQUEST_PARAMS_ERROR);
    }


    /**
     * author snow
     * 用户撤单
     */
    public function cancelPackage()
    {
        $wrap_id = $this->request->getPost('wrap_id', 'trim');
        if (!$package_id = projects::dewrapId($wrap_id)) {
            $this->showMsg('6003', mobileErrorCode::REQUEST_PARAMS_ERROR);
        }
        if (!$package = projects::getPackage($package_id, $GLOBALS['SESSION']['user_id'])) {
            $this->showMsg('6003', '订单不存在');
        }

        try {
            if($package['trace_id']!=0){
                game::cancelTrace($GLOBALS['SESSION']['user_id'], $package['trace_id'], [$package['package_id']]);
            }else{
                game::cancelPackage($package, 1); //1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
            }
            $this->showMsg('0', mobileErrorCode::RETURN_SUCCESS, []);
        } catch (exception2 $e) {
            $this->showMsg('6003', implode(',', $e->getMessages()));
        }
    }

}