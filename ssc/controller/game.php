<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：游戏管理
 */
class gameController extends sscController
{

    const MAX_TRACE_ISSUE_NUM = 240;   //最大可追号期数
    const MAX_ISSUES_NUMBER = 20;   //针对秒秒彩，最大历史开奖期数

    public $titles = array(
        'play' => '参与游戏',
        'cqssc' => '重庆时时彩',
        'cqssc_x' => '重庆时时彩信用',
        'sd11y' => '山东十一运',
        'sd11y_x' => '山东十一运信用',
        'hljssc' => '黑龙江时时彩',
        'hljssc_x' => '黑龙江时时彩信用',
        'xjssc' => '新疆时时彩',
        'xjssc_x' => '新疆时时彩信用',
        'js115' => '江苏11选5',
        'js115_x' => '江苏11选5信用',
        'jx115' => '江西11选5',
        'jx115_x' => '江西11选5信用',
        'gd115' => '广东11选5',
        'gd115_x' => '广东11选5信用',
        'tjssc' => '天津时时彩',
        'tjssc_x' => '天津时时彩信用',
        'low3D' => '福彩3D',
        'low3D_x' => '福彩3D信用',
        'P3P5' => '体彩P3P5',
        'P3P5_x' => '体彩P3P5信用',
        'yzffc' => '幸运分分彩',
        'yzffc_x' => '幸运分分彩信用',
        'jsks' => '江苏快三',
        'jsks_x' => '江苏快三信用',
        'ksffc' => '快三夺宝',
        'ksffc_x' => '快三夺宝信用',
        'klpk' => '快乐扑克',
        'klpk_x' => '快乐扑克信用',
        'yzmmc' => '幸运秒秒彩',
        'yzmmc_x' => '幸运秒秒彩信用',
        'ffc115' => '11选5分分彩',
        'ffc115_x' => '11选5分分彩信用',
        'bjpks' => '北京PK拾',
        'bjpks_x' => '北京PK拾信用',
        'dj15' => '东京1.5分彩信用',
        'dj15_x' => '东京1.5分彩信用',
        'ahks' => '安徽快三',
        'ahks_x' => '安徽快三信用',
        'fjks' => '福建快三',
        'fjks_x' => '福建快三信用',
        'lhc' => '香港六合彩',
        'lhc_x' => '香港六合彩信用',
        'ssq' => '双色球',
        'ssq_x' => '双色球信用',
        'xy28' => '幸运28',
        'xy28_x' => '幸运28信用',
        'qqffc' => 'qq分分彩',
        'qqffc_x' => 'qq分分彩信用',
        'jslhc' => '极速六合彩',
        'jslhc_x' => '极速六合彩信用',
        'xyft_x' => '幸运飞艇信用',
        'egame' => '休闲游戏',
        'packageList' => '游戏记录',
        'packageTeamList' => '团队游戏记录',
        'packageDetail' => '订单详情',
        'cacelPackage' => '用户撤单',
        'traceList' => '追号记录',
        'traceDetail' => '追号单详情',
        'cancelTrace' => '追号撤单',
        'cancelAllTrace' => '所有追号撤单',
        'egameList' => '休闲游戏记录',
        'memberReport' => '游戏报表',
        'chart' => '用户走势图',
        'lobby' => '游戏大厅',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
        $this->getUser();
        $this->getNotics();
        $this->getNotReadMsgNum();
    }

    public function lobby()
    {
        $GLOBALS['nav'] = 'game';
        self::$view->render('game_lobby');
    }


    //全部是ajax请求
    public function play()
    {
        $op = $this->request->getPost('op', 'trim', 'buy');
        switch ($op) {
            case 'buy':
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
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
                    $result['errno'] = 65534; //表示可能由于某种原因的重复订单错误号
                    echo json_encode($result);
                    exit;
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
                    //print_r($e);
                    $result['errno'] = $e->getCode();
                    $result['errstr'] = $e->getMessage();
                    $mmcResult['errno'] = $e->getCode();
                    $mmcResult['errstr'] = $e->getMessage();
                    log2("traceBug:errno=" . $e->getCode() . ",errstr=" . $e->getMessage());
                }

                if ($lotteryId == 15) {
                    echo json_encode($mmcResult);
                } else {
                    echo json_encode($result);
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

// $lotteryId =17;
// $issue='626079';
// $curRebate=0.005;
// $modes='0.5';
                if ($GLOBALS['mc']->get('mc', $GLOBALS['SESSION']['user_id'] . 'buyToken') == $token) {
                    $result['errno'] = 65534; //表示可能由于某种原因的重复订单错误号
                    echo json_encode($result);
                    exit;
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
                    $result['errno'] = $e->getCode();
                    $result['errstr'] = $e->getMessage();
                    log2("traceBug:errno=" . $e->getCode() . ",errstr=" . $e->getMessage());
                }

                echo json_encode($result);
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
                if (!$lottery_id = $this->request->getPost('lotteryId', 'intval')) {
                    die(json_encode(array('errno' => 1, 'errstr' => '参数错误')));
                }
                if (!$issueInfo = issues::getCurrentIssue($lottery_id)) {
                    //{issue_id: '11444', issue:'20130131-080', 'end_time': '2013/01/31 19:18:30', 'input_time': '2013/01/31 19:20:30'}
                    if (!$issues = issues::getItems($lottery_id, '', 0, 0, 0, 0, 0, '', 0, 1)) {
                        die(json_encode(array('errno' => 2, 'errstr' => 'issue data error')));
                    }
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
                        'issue' => $lastIssueInfo['issue'],
                        'code' => $lastIssueInfo['code'],
                        'original_code' => $lastIssueInfo['original_code'],
                    );
                }
                $lastOpenIssueInfo = issues::getLastOpenIssue($lottery_id);
                $kTime = $this->calTime($lottery_id);
                $result = array('errno' => 0, 'kTime' => $kTime, 'errstr' => '', 'issueInfo' => $issueInfo, 'lastIssueInfo' => $lastIssueInfo, 'lastOpenIssueInfo' => $lastOpenIssueInfo, 'serverTime' => date('Y/m/d H:i:s'));

                echo json_encode($result);
                break;
            case 'getLastIssueCode': //得到上一期的开奖结果，还没开出来返回空
                if (!$lottery_id = $this->request->getPost('lotteryId', 'intval')) {
                    die(json_encode(array('errno' => 1, 'errstr' => '参数错误')));
                }
                if (!$issue = $this->request->getPost('issue', 'trim')) {
                    die(json_encode(array('errno' => 1, 'errstr' => '参数错误')));
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
                        $result = array('errno' => 0, 'kTime' => $kTime, 'errstr' => '', 'issueInfo' => $issueInfo, 'serverTime' => date('Y/m/d H:i:s'));
                    } else {
                        $result = array('errno' => 0, 'kTime' => $kTime, 'errstr' => '', 'issueInfo' => array(), 'serverTime' => date('Y/m/d H:i:s'));
                    }
                } else {
                    $result = array('errno' => 1, 'errstr' => 'cur issue data error');
                }

                echo json_encode($result);
                break;
            case 'getLastOpenIssue': //得到最近已开奖的奖期
                if (!$lotteryId = $this->request->getPost('lotteryId', 'intval')) {
                    die(json_encode(array('errno' => 1, 'errstr' => '参数错误')));
                }
                $res = ['errno' => 1, 'issueInfo' => ''];
                if ($row = issues::getLastOpenIssue($lotteryId)) {
                    $res = ['errno' => 0, 'issueInfo' => $row];
                }

                echo json_encode($res);
                break;
            case 'getTracePage':   //显示追号页内容
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                $mids = $this->request->getPost('mids', 'trim');
                if (!$lotteryId || !$mids) {
                    die(json_encode(array('errno' => 1, 'errstr' => '参数错误')));
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
                    die(json_encode(array('errno' => 2, 'errstr' => '没有可追号奖期')));
                }
                $issues2 = array();
                foreach ($issues as $v) {
                    $issues2[] = $v['issue'];
                }

                echo json_encode(array('errno' => 0, 'errstr' => '', 'issues' => $issues2, 'prize' => $prize, 'prizeLimit' => $prize_limit));
                break;
            case 'getCurContextIssues'://得到包括当前期前后5期
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                //得到前面2期
                if (!$tmp1 = issues::getItems($lotteryId, '', 0, 0, 0, time(), -1, 'issue_id DESC', 0, 2)) {
                    die(json_encode(array('errno' => 2, 'errstr' => 'issue data error')));
                }
                //得到当前期
                $tmp2 = issues::getCurrentIssue($lotteryId);
                //得到后面2期
                if (!$tmp3 = issues::getItems($lotteryId, '', time(), 0, 0, 0, 0, '', 0, 2)) {
                    die(json_encode(array('errno' => 2, 'errstr' => 'issue data error2')));
                }
                $issues = array_merge($tmp1, array($tmp2), $tmp3);
                if (count($issues) != 5) {
                    die(json_encode(array('errno' => 2, 'errstr' => 'issue data error3')));
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
                $result = array('errno' => 0, 'errstr' => '', 'issueInfos' => $issueInfos);
                echo json_encode($result);
                break;
            case 'getBuyRecords':   //得到某一期的投注记录 用于前台右上角显示 玩法类型     投注内容    倍数  金额  状态  奖金
                $lotteryId = $this->request->getPost('lotteryId', 'intval');
                $issue = $this->request->getPost('issue', 'trim');

                $result = array('errno' => 0, 'errstr' => '', 'prj' => $this->comGetBuyRecords($lotteryId, $issue));
                echo json_encode($result);
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
                } else {
                    $result['errno'] = 1;
                }
                echo json_encode($result);
                break;
        }

        exit;
    }

    /**
     * 计算当前时间到下一段开始时间的时间差 返回毫秒
     * @param $lottery_id 彩种id
     * @return int
     */
    public function calTime($lottery_id)
    {
        $lotteryInfo = lottery::getItem($lottery_id);
        $lotteryNum = count($lotteryInfo['settings']);
        $time = date('H:i:s');
        $kTime = 0;
        if ($lotteryNum == 1) {//只有一个时间段
            $startTime = $lotteryInfo['settings'][0]['start_time'];//开始时间
            $endTime = $lotteryInfo['settings'][0]['end_time'];//结束时间
            if (($endTime < '12:00:00') && ($startTime < $endTime) || ($startTime == $endTime))//这些情况没有休市时间段
            {
                $tmp = true;
            } else {
                $tmp = false;
            }

            if (!$tmp) {//开始时间与结束时间相等表明没有休市限制
                if ($endTime > $startTime) {
                    if ($time > $endTime)//跨天
                    {
                        $kTime = strtotime($startTime) + 86400 - strtotime($time);
                    }
                    if ($time < $startTime) {
                        $kTime = strtotime($startTime) - strtotime($time);
                    }
                } else {
                    if ($time < $startTime && $time > $endTime) {
                        $kTime = strtotime($startTime) - strtotime($time);
                    }
                }
            }
        } else {
            $arr = $lotteryInfo['settings'];
            usort($arr, function ($v, $v1) {//对时间段数组进行排序--主要处理后台不按照顺序设置休市时间
                if ($v['start_time'] < $v1['start_time']) {
                    return -1;
                } else {
                    return 1;
                }
            });
            $num = $lotteryNum;
            foreach ($arr as $key => $val) {
                if ($key + 1 < $num) {//最后一段时间之前的时间段 这些时间段没有跨天时间
                    if ($time > $val['end_time'] && $time < $arr[$key + 1]['start_time']) {
                        $kTime = strtotime($arr[$key + 1]['start_time']) - strtotime($time);
                        break;
                    }
                } else {//最后一段时间
                    if (($val['end_time'] > $arr[0]['start_time']) && ($val['end_time'] < '12:00:00') || ($val['end_time'] == $arr[0]['start_time'])) {
                        $tmp = true;//这些情况没有休市
                    } else {
                        $tmp = false;
                    }
                    if (!$tmp) {
                        if ($val['end_time'] > $arr[0]['start_time']) {
                            if ($time > $val['end_time']) {//表明跨天
                                $kTime = strtotime($arr[0]['start_time']) + 86400 - strtotime($time);
                            }
                            if ($time < $arr['0']['start_time']) {
                                $kTime = strtotime($arr['0']['start_time']) - strtotime($time);
                            }
                        } else {
                            //没有跨天
                            if ($time > $val['end_time'] && $time < $arr[0]['start_time']) {
                                $kTime = strtotime($arr[0]['start_time']) - strtotime($time);
                            }
                        }
                    }
                }
            }
        }
        return $kTime * 1000;
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

    public function egame()
    {
        $lottery_id = 12;
        //得到是谁投的
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }
        $template_name = 'game_egame';
        $test_user = $this->request->getGet('test_user', 'trim');
        $test_pass = $this->request->getGet('test_pass', 'trim');
        if ($test_user && $test_pass) {
            $template_name = 'game_egame_test';
            self::$view->setVar('test_user', $test_user);
            self::$view->setVar('test_pass', $test_pass);
        }
        self::$view->setVar('user', $user);
        self::$view->render($template_name);
    }

    public function cqssc()
    {
        $lottery_id = 1;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function cqssc_x()
    {
        $lottery_id = 1;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function sd11y()
    {
        $lottery_id = 2;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function sd11y_x()
    {
        $lottery_id = 2;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function hljssc()
    {
        $lottery_id = 3;
        $template_name = 'game_cqssc';  //暂没有区分
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new , disable
    public function hljssc_x()
    {
        $lottery_id = 3;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function xjssc()
    {
        $lottery_id = 4;
        $template_name = 'game_cqssc';  //暂没有区分
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function xjssc_x()
    {
        $lottery_id = 4;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function js115()
    {
        $lottery_id = 5;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function js115_x()
    {
        $lottery_id = 5;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function jx115()
    {
        $lottery_id = 6;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function jx115_x()
    {
        $lottery_id = 6;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function gd115()
    {
        $lottery_id = 7;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function gd115_x()
    {
        $lottery_id = 7;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function tjssc()
    {
        $lottery_id = 8;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function tjssc_x()
    {
        $lottery_id = 8;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function low3D()
    {
        $lottery_id = 9;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function low3D_x()
    {
        $lottery_id = 9;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function P3P5()
    {
        $lottery_id = 10;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function P3P5_x()
    {
        $lottery_id = 10;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function yzffc()
    {
        $lottery_id = 11;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function yzffc_x()
    {
        $lottery_id = 11;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function jsks()
    {
        $lottery_id = 12;
        $template_name = 'game_jsks';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    public function jsks_x()
    {
        $lottery_id = 12;
        $template_name = 'game_jsks_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function ksffc()
    {
        $lottery_id = 13;
        $template_name = 'game_jsks';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    public function ksffc_x()
    {
        $lottery_id = 13;
        $template_name = 'game_jsks_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function klpk()
    {
        $lottery_id = 14;
        $template_name = 'game_klpk';  //暂没有区分
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function klpk_x()
    {
        $lottery_id = 14;
        $template_name = 'game_klpk_x';  //暂没有区分
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function yzmmc()
    {
        $lottery_id = 15;
        $template_name = 'game_cqmmc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function yzmmc_x()
    {
        $lottery_id = 15;
        $template_name = 'game_cqmmc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function ffc115()
    {
        $lottery_id = 16;
        $template_name = 'game_cqssc';  //暂没有区分
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function ffc115_x()
    {
        $lottery_id = 16;
        $template_name = 'game_cqssc_x';  //暂没有区分
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function bjpks()
    {
        $lottery_id = 17;
        $template_name = 'game_pks';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    public function bjpks_x()
    {
        $lottery_id = 17;
        $template_name = 'game_pks_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function dj15()
    {
        $lottery_id = 18;
        $template_name = 'game_cqssc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function dj15_x()
    {
        $lottery_id = 18;
        $template_name = 'game_cqssc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function ahks()
    {
        $lottery_id = 19;
        $template_name = 'game_jsks';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    public function ahks_x()
    {
        $lottery_id = 19;
        $template_name = 'game_jsks_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function fjks()
    {
        $lottery_id = 20;
        $template_name = 'game_jsks';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function fjks_x()
    {
        $lottery_id = 20;
        $template_name = 'game_jsks_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    private function getLhcLive()
    {
        $arr = [];
        $switch = config::getConfigWithCache('lhc_live_streaming');
        $arr['switch'] = $switch == 1 ? 1 : 0;
        $src = config::getConfigWithCache('lhc_live_url');
        $arr['src'] = !empty($src) ? $src : '';
        $arr['type'] = '';
        $tou = explode('://', $src)[0];
        if ($tou == 'rtmp') {
            $arr['type'] = 'rtmp';
        } elseif (preg_match('/\.m3u8/', $src, $matches)) {
            $arr['type'] = 'hls';
        } elseif ($tou == 'http') {
            $arr['type'] = 'http';
        }
        self::$view->setVar('live_switch', $arr['switch']);
        self::$view->setVar('live_src', $arr['src']);
        self::$view->setVar('live_type', $arr['type']);
    }

    public function lhc()
    {
        $lottery_id = 21;
        $template_name = 'game_lhc';
        $this->getLhcLive();
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    public function lhc_x()
    {
        $lottery_id = 21;
        $template_name = 'game_lhc_x';
        $this->getLhcLive();
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function ssq()
    {
        $lottery_id = 22;
        $template_name = 'game_ssq';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function ssq_x()
    {
        $lottery_id = 22;
        $template_name = 'game_ssq_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function xy28()
    {
        $lottery_id = 23;
        $template_name = 'game_xy28';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function xy28_x()
    {
        $lottery_id = 23;
        $template_name = 'game_xy28_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function qqffc()
    {
        $lottery_id = 24;
        $template_name = 'game_qqffc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    # new
    public function qqffc_x()
    {
        $lottery_id = 24;
        $template_name = 'game_qqffc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function jslhc()
    {
        $lottery_id = 25;
        $template_name = 'game_jslhc';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_OFFICIAL);
    }

    public function jslhc_x()
    {
        $lottery_id = 25;
        $template_name = 'game_jslhc_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    public function xyft_x()
    {
        $lottery_id = 26;
        $template_name = 'game_xyft_x';
        self::_show($lottery_id, $template_name, methodGroups::GROUP_CREDIT);
    }

    static function _show($lottery_id, $templateName, $group = methodGroups::GROUP_ALL)
    {
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            redirect(url('default', 'login'), 2, TRUE);
        }
        $lottery = lottery::getItem($lottery_id);
        self::$view->setVar('lottery', $lottery);
        self::$view->setVar('minRebateGaps', json_encode($lottery['min_rebate_gaps']));
        //得到全包奖金
        self::$view->setVar('maxCombPrize', $lottery['zx_max_comb'] * 2);

        //得到当天已开奖奖期
        if ($lottery_id == 4 && date('Hi') <= '0200') { //对于跨天的在0点以后，应取昨天的$belong_date
            $belong_date = date('Y-m-d', strtotime('-1 day'));
        } else {
            $belong_date = date('Y-m-d');
        }

        $issues = array();
        /**
         * todo待优化
         * lottery_id_3=lottery_id__status_code   lottery_id_4=lottery_id__status_code__belong_date
         * 初步思路：去掉lottery_id_2,lottery_id_3索引，增加lottery_id_4索引
         * EXPLAIN SELECT * FROM `issues` WHERE 1 AND lottery_id = 11 AND belong_date = '2016-03-09' and status_code=0 AND end_sale_time > '2016-03-09 15:03:16' ORDER BY issue_id ASC
         * 不指定索引 mysql不会用到索引 SELECT sql_no_cache * FROM `issues` WHERE 1 AND lottery_id = 11 AND belong_date >= '2016-02-28' AND belong_date <= '2016-03-09' AND status_code = 2 ORDER BY issue_id DESC LIMIT 0, 1
         * 指定lottery_id_4索引16ms SELECT SQL_NO_CACHE * FROM `issues` USE INDEX ( lottery_id_4 ) WHERE 1 AND lottery_id =11 AND status_code =2 AND belong_date >= '2016-03-08' AND belong_date <= '2016-03-09' ORDER BY issue_id DESC LIMIT 0 , 1
         * 指定lottery_id_3索引最快1ms 很奇怪 SELECT SQL_NO_CACHE * FROM `issues` USE INDEX ( lottery_id_3 ) WHERE 1 AND lottery_id =11 AND status_code =2 ORDER BY issue_id DESC LIMIT 0 , 1
         *
         */
        if ($lottery_id != 15) {
            // if (!$tmp = issues::getItems($lottery_id, $belong_date, 0, 0, 0, 0, 2, 'issue_id DESC', 0, self::MAX_ISSUES_NUMBER)) {
            if (!$tmp = issues::getItems($lottery_id, $belong_date, 0, 0, 0, 0, 2, 'issue_id DESC', 0, 5)) {
                if (!$tmp = issues::getItems($lottery_id, date('Y-m-d', strtotime('-1 day')), 0, 0, 0, 0, 2, 'issue_id DESC', 0, 5)) {
                    $tmp = issues::getItems($lottery_id, array(date('Y-m-d', strtotime('-10 day')), date('Y-m-d', strtotime('-1 day'))), 0, 0, 0, 0, 2, 'issue_id DESC', 0, 5);
                }
            }

            foreach ($tmp as $v) {
                $issues[] = array(
                    'issue_id' => $v['issue_id'],
                    'issue' => $v['issue'],
                    'code' => $v['code'],
                    'original_code' => $v['original_code'],
                );
            }

            self::$view->setVar('json_openedIssues', json_encode($issues));
            //得到最近一期遗漏冷热
            $recentIssue = reset($issues);
            if (isset($recentIssue['issue_id']) && !empty($recentIssue['issue_id']) && ($history = issues::getIssueHistory($recentIssue['issue_id']))) {
                self::$view->setVar('json_missHot', json_encode(array('miss' => unserialize($history['miss_info']), 'hot' => unserialize($history['hot_info']),)));
            } else {
                self::$view->setVar('json_missHot', json_encode(array()));
            }
        } else {
            self::$view->setVar('json_openedIssues', json_encode($issues));
            self::$view->setVar('json_missHot', json_encode(array()));
        }

        //得到用户返点
        $userRebate = userRebates::getUserRebate($GLOBALS['SESSION']['user_id'], $lottery['property_id']);
        if (!$userRebate) {
            showMsg('此彩种您还没有分配返点，请联系上级或者客服');
        }

        // $lottery_id == 21 && log2file('lhc','rebate:'.$userRebate);

        $redisKey = 'js_methods_' . $lottery_id . '_' . $group;
        $result = $GLOBALS['redis']->hGet(__FUNCTION__, $redisKey);

        if (!$result) {
            $result = [];
            $methods = methods::getPlayMethods($lottery_id, 0, $group);
            $prizes = prizes::getItems(0, 0, 0, 0, 1);
            foreach ($methods as $k => $v) {
                foreach ($v['childs'] as $kk => $vv) {
                    for ($i = 1; $i <= $vv['levels']; $i++) {
                        $methods[$k]['childs'][$kk]['prize'][$i] = $prizes[$methods[$k]['childs'][$kk]['method_id']][$i]['prize'];
                    }
                }

                $result[$lottery_id][] = $methods[$k];
            }
            $result = json_encode($result, JSON_UNESCAPED_UNICODE);
            $GLOBALS['redis']->hSet(__FUNCTION__, $redisKey, $result);
        }
        self::$view->setVar('methods', $result);
        self::$view->setVar('rebate', $userRebate);
        self::$view->setVar('user', $user);
        self::$view->render($templateName);
    }

    /**
     * 休闲游戏简单报表：
     * 仅显示自己的休闲游戏数据，可按日期查询
     */
    public function egameList()
    {
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
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
        } else {
            $startTime = $startDate;
            $endTime = $endDate;
        }

        $childReport = $users = array();

        //团队购买量 各级代理总返点量 各用户自身投注的奖金
        //$childReports = projects::getChildReport($GLOBALS['SESSION']['user_id'], $startTime, $endTime);
        $childReports = reportPt::getSumGroupByUser($GLOBALS['SESSION']['user_id'], $startTime, $endTime, 8);
        $childReport = isset($childReports[$GLOBALS['SESSION']['user_id']]) ? $childReports[$GLOBALS['SESSION']['user_id']] : array('pt_game_win' => 0, 'pt_prize' => 0, 'pt_buy_amount' => 0, 'pt_amount' => 0);

        $user = users::getItem($GLOBALS['SESSION']['username']);

        //预设查询值
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('user', $user);

        self::$view->setVar('childReport', $childReport);
        self::$view->render('game_egamelist');
    }

    /**
     * 游戏报表
     */
    public function memberReport()
    {
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
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
        } else {
            $startTime = $startDate;
            $endTime = $endDate;
        }

        $totalWithdraws = $totalDeposits = $childReport = $totalInfo = $users = $recentBuy = array();
        $user = users::getItem($GLOBALS['SESSION']['username']);

        //团队购买量 各级代理总返点量 各用户自身投注的奖金
        $childReport = projects::getChildReport($user['user_id'], $startTime, $endTime);
        //得到直接下级充值量
        $totalDeposits = deposits::getUsersDeposits(array_keys($childReport), $startTime, $endTime);
        //得到直接下级提款量
        $totalWithdraws = withdraws::getUsersWithdraws(array_keys($childReport), $startTime, $endTime);

        //把数据都组装进childReport数组，以方便排序
        foreach ($childReport as $k => $v) {
            $user_id = $user['user_id'];
            $childReport[$k]['user_balance'] = $user['balance'];
            $childReport[$k]['total_deposit'] = $totalDeposits[$user_id]['total_deposit'];
            $childReport[$k]['total_withdraw'] = $totalWithdraws[$user_id]['total_withdraw'];
            $childReport[$k]['profit_and_lost'] = ($v['total_rebate'] + $v['total_prize'] - $v['total_amount']);
            $childReport[$k]['total_rebate'] = $v['total_rebate']; //返点
            $childReport[$k]['total_contribute_rebate'] = $v['total_contribute_rebate']; //下级佣金量
            $childReport[$k]['pt_game_win'] = $v['pt_game_win'];
            $childReport[$k]['pt_prize'] = $v['pt_prize'];   //PT中奖
            $childReport[$k]['pt_buy_amount'] = $v['pt_buy_amount'];
            $childReport[$k]['pt_amount'] = $v['pt_amount'];
        }

        //预设查询值
        self::$view->setVar('user', $user);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('childReport', $childReport);
        self::$view->setVar('totalDeposits', $totalDeposits);
        self::$view->setVar('totalWithdraws', $totalWithdraws);

        self::$view->render('game_memberreport');
    }


    public function packageList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        $check_prize_status = $this->request->getGet('check_prize_status', 'intval', -1);

        $mmcPopup = $this->request->getGet('mmcPopup', 'trim');
        //$username = $GLOBALS['SESSION']['username'];    //$this->request->getGet('username', 'trim');
        $include_childs = 0; //$this->request->getGet('include_childs', 'intval', 0);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $this->searchDate($start_time, $end_time, 15, 1);

        //得到所有彩种
        // $lotterys = lottery::getItems(0, -1);
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname', 'zx_max_comb', 'total_profit', 'property_id'], 0, -1);
        $keyTmp = 0;
        //添加对应的默认值
        $packagesTotal = ['total_profit' => 0, 'total_prize' => 0, 'total_amount' => 0];
        if ($wrap_id) {
            $check_prize_status = -1;
            $package_id = projects::dewrapId($wrap_id);
            $packages = projects::getPackagesById(array($package_id), $GLOBALS['SESSION']['user_id']);
            $packagesNumber = 1;
        } else {
            if ($check_prize_status == 65535) {
                $cancel_status = 65535;
            } elseif ($check_prize_status == 0) {
                $cancel_status = 0;
            }  elseif ($check_prize_status == 100) {
                $check_prize_status = -1;
                $keyTmp= 1;
                $cancel_status = 0;
            }else {
                $cancel_status = -1;
            }

            $packagesNumber = projects::getPackagesNumber($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', -1, 0, $GLOBALS['SESSION']['user_id'], $include_childs, $start_time, $end_time, $cancel_status);

            /***************** snow  获取正确的分页开始****************************/
            $curPage  = $this->request->getGet('curPage', 'intval', 1);
            $startPos = getStartOffset($curPage, $packagesNumber);
            /***************** snow  获取正确的分页开始****************************/
            $packages = projects::getPackages($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', -1, 0, $GLOBALS['SESSION']['user_id'], $include_childs, $start_time, $end_time, '', '', $cancel_status, '', $startPos, DEFAULT_PER_PAGE);
            $packagesTotal = projects::getPackageTotal($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', -1, 0, $GLOBALS['SESSION']['user_id'], $include_childs, $start_time, $end_time, '', '', $cancel_status);
        }

        $realAmount = $totalAmount = $totalPrize = $totalProfit = 0;
        //为算出奖金，先得到这些用户的返点
        if ($packages) {
            $user_ids = array_keys(array_spec_key($packages, 'user_id'));
            $userRebates = userRebates::getUsersRebates($user_ids, 0, 1);

            $nowTime = time();
            foreach ($packages as $k => $v) {
                $packages[$k]['amount'] = number_format($v['amount'], 4) . '元';
                $packages[$k]['wrap_id'] = projects::wrapId($v['package_id'], $v['issue'], $v['lottery_id']);
                $packages[$k]['prize_mode'] = 2 * $lotterys[$v['lottery_id']]['zx_max_comb'] * (1 - $lotterys[$v['lottery_id']]['total_profit'] + $userRebates[$v['user_id']][$lotterys[$v['lottery_id']]['property_id']] - $v['cur_rebate']);

                $packages[$k]['show_cancel'] = false;
                if ($v['lottery_id'] != 15) {
                    $issueInfo = issues::getItem(0, $v['issue'], $v['lottery_id']);
                    if ($v['cancel_status'] == 0 && $nowTime < strtotime($issueInfo['cannel_deadline_time'])) {
                        $packages[$k]['show_cancel'] = true;
                    }
                }

                if ($v['cancel_status'] == 0) $realAmount += $v['amount'];
                if ($v['check_prize_status'] == 1) $totalPrize += $v['prize'];

                if ($v['cancel_status'] > 0 || $v['check_prize_status'] == 0) {
                    $packages[$k]['prize'] = '--';
                    $packages[$k]['profit'] = '--';
                } else {
                    $profit = $v['prize'] - $v['amount'];
                    $packages[$k]['prize'] = number_format($v['prize'], 4) . '元';
                    $packages[$k]['profit'] = number_format($profit, 4) . '元';
                    $totalProfit += $profit;
                }

                $totalAmount += $v['amount'];
            }
        }
        $user = users::getItem($GLOBALS['SESSION']['username']);
         if($keyTmp == 1)
         {
             $check_prize_status = 100;
         }
        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('check_prize_status', $check_prize_status);
        self::$view->setVar('user', $user);
        self::$view->setVar('wrap_id', $wrap_id);
        //self::$view->setVar('username', $username);
        self::$view->setVar('packages', $packages);
        self::$view->setVar('packagesNumber', $packagesNumber);
        self::$view->setVar('packagesTotal', $packagesTotal);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('realAmount', $realAmount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('totalProfit', $totalProfit);
        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('pageList', getPageList($packagesNumber, DEFAULT_PER_PAGE));
        if ($mmcPopup == '1') {
            self::$view->render('game_packagelist_mmc');
        } else {
            self::$view->render('game_packagelist');
        }
    }

    public function packageTeamList()
    {
        $curTime = date('Y-m-d');
        $dec6Days = date('Y-m-d', strtotime('-6 days'));

        if ($endDate = $this->request->getGet('endDate', 'trim')) {
            if ($endDate > $curTime) $endDate = $curTime;
            elseif ($endDate < $dec6Days) $endDate = $dec6Days;
        } else {
            $endDate = $curTime;
        }

        if ($startDate = $this->request->getGet('startDate', 'trim')) {
            if ($startDate > $endDate) $startDate = $endDate;
            elseif ($startDate < $dec6Days) $startDate = $dec6Days;
            elseif ($startDate > $curTime) $startDate = $curTime;
        } else {
            $startDate = $curTime;
        }

        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        $startDate .= ' 00:00:00';
        $endDate .= ' 59:59:59';

        $lottery_id = $this->request->getGet('lottery_id', 'intval');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        if ($username = $this->request->getGet('username', 'trim')) {
            if (!$user = users::getItem($username)) {
                showMsg("非法请求，该用户不存在或已被冻结");
            }
            if (!in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))) {
                showMsg("非法请求，此用户不是你的下级");
            }
            $userId = $user['user_id'];
            $include_childs = 0;
        } else {
            $userId = $GLOBALS['SESSION']['user_id'];
            $include_childs = 1;
        }


        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname', 'zx_max_comb', 'total_profit', 'property_id'], 0, -1);
        $cancel_status = -1;
        $packagesNumber = projects::getPackagesNumber($lottery_id, $cancel_status, -1, '', -1, 0, $userId, $include_childs, $startDate, $endDate, $cancel_status);

        /***************** snow  获取正确的分页开始****************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $packagesNumber);
        /***************** snow  获取正确的分页开始****************************/
        $packages = projects::getPackages($lottery_id, $cancel_status, -1, '', -1, 0, $userId, $include_childs, $startDate, $endDate, '', '', $cancel_status, '', $startPos, DEFAULT_PER_PAGE);

        $realAmount = $totalAmount = $totalPrize = 0;
        //为算出奖金，先得到这些用户的返点
        if ($packages) {
            $user_ids = array_keys(array_spec_key($packages, 'user_id'));
            $userRebates = userRebates::getUsersRebates($user_ids, 0, 1);
            foreach ($packages as $k => $v) {
                $packages[$k]['wrap_id'] = projects::wrapId($v['package_id'], $v['issue'], $v['lottery_id']);
                $packages[$k]['prize_mode'] = 2 * $lotterys[$v['lottery_id']]['zx_max_comb'] * (1 - $lotterys[$v['lottery_id']]['total_profit'] + $userRebates[$v['user_id']][$lotterys[$v['lottery_id']]['property_id']] - $v['cur_rebate']);
                $totalAmount += $v['amount'];
                if ($v['cancel_status'] == 0) {
                    $realAmount += $v['amount'];
                }
                if ($v['check_prize_status'] == 1) {
                    $totalPrize += $v['prize'];
                }
            }
        }
        $user = users::getItem($GLOBALS['SESSION']['username']);

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('user', $user);
        self::$view->setVar('username', $username);
        self::$view->setVar('packages', $packages);
        self::$view->setVar('packagesNumber', $packagesNumber);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('realAmount', $realAmount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('pageList', getPageList($packagesNumber, DEFAULT_PER_PAGE));

        self::$view->render('game_packageteamlist');
    }


    public function packageDetail()
    {
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        $mmcPopup = $this->request->getGet('mmcPopup', 'trim');
        if (!$package_id = projects::dewrapId($wrap_id)) {
            showMsg('订单编号无效');
        }
        if (!$package = projects::getPackage($package_id)) {
            showMsg('找不到该订单');
        }
        $package['wrap_id'] = $wrap_id;
        $isSelf = true;
        if ($package['user_id'] != $GLOBALS['SESSION']['user_id']) {
            $isSelf = false;
            //核实是否是其上级 上级有权查看下级订单 否则不准查看
            $parents = users::getAllParent($package['user_id']);
            if (!isset($parents[$GLOBALS['SESSION']['user_id']])) {
                showMsg('您无权查看该订单');
            }
        }
        //得到是谁投的
        if (!$user = users::getItem($package['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }
        self::$view->setVar('user', $user);

        //如果是追号单，跳转至
        if ($package['trace_id']) {
            redirect(url('game', 'traceDetail', array('wrap_id' => traces::wrapId($package['trace_id'], $package['issue'], $package['lottery_id']))));
        }

        $package['show_cancel'] = false;
        if ($isSelf && $package['lottery_id'] != 15) {
            $issueInfo = issues::getItem(0, $package['issue'], $package['lottery_id']);
            if ($package['cancel_status'] == 0 && time() < strtotime($issueInfo['cannel_deadline_time'])) {
                $package['show_cancel'] = true;
            }
        }

        //得到所有彩种
        $lottery = lottery::getItem($package['lottery_id']);
        self::$view->setVar('lottery', $lottery);
        //得到奖金系列
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);

        //得到开奖号码
        $openCodes = issues::getCodes($package['lottery_id'], array($package['issue']));
        $openCode = '';
        if (isset($openCodes[$package['issue']])) {
            $openCode = $openCodes[$package['issue']];
            if ($lottery['property_id'] == 4) {
                $suit_arr = array(
                    's' => 'poker_heit',
                    'h' => 'poker_hongt',
                    'c' => 'poker_meih',
                    'd' => 'poker_fangk'
                );
                $partys = explode(' ', $openCode);
                $openCode = array();
                foreach ($partys as $v) {
                    $openCode[] = array('num' => str_replace('T', '10', $v[0]), 'suit' => $suit_arr[$v[1]]);
                }
            }
        }

        self::$view->setVar('openCode', $openCode);

        //订单详情
        $projects = projects::getItems(0, '', $package_id);
        //先得到用户返点
        $userRebate = userRebates::getUserRebate($GLOBALS['SESSION']['user_id'], $lottery['property_id']);
        $prizes = prizes::getItems($package['lottery_id'], 0, 0, 0, 1);
        foreach ($projects as $k => $v) {
            if ($v['lottery_id'] == 14) {
                $projects[$k]['code'] = str_replace('T', '10', $v['code']);
            }
            //计算如果中奖的奖金
            if ($v['cur_rebate'] > $userRebate) {
                showMsg('系统出错');
            }
            $projects[$k]['will_prize'] = number_format($v['multiple'] * $v['modes'] * $prizes[$v['method_id']][1]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
        }
        //得到彩种玩法组玩法二级联动
        $methods = methods::getItemsNew(['method_id', 'name', 'cname']);
        self::$view->setVar('methods', $methods);

        self::$view->setVar('package', $package);
        self::$view->setVar('projects', $projects);
        self::$view->setVar('isSelf', $isSelf);
        if ($mmcPopup == '1' || $package['lottery_id'] == 15) {
            self::$view->render('game_packagedetail_mmc');
        } else {
            self::$view->render('game_packagedetail');
        }
    }

    public function cacelPackage()
    {
        $wrap_id = $this->request->getPost('wrap_id', 'trim');
        if (!$package_id = projects::dewrapId($wrap_id)) {
            die(json_encode(array('errno' => 11, 'errstr' => '参数无效！')));
        }
        if (!$package = projects::getPackage($package_id, $GLOBALS['SESSION']['user_id'])) {
            die(json_encode(array('errno' => 12, 'errstr' => '找不到该订单')));
        }

        try {
            game::cancelPackage($package, 1); //1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
            die(json_encode(array('errno' => 0, 'errstr' => '撤单成功')));
        } catch (exception2 $e) {
            die(json_encode(array('errno' => 13, 'errstr' => $e->getMessage())));
        }
    }

    public function traceList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $status = $this->request->getGet('status', 'intval', -1);
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $issue = ''; //$this->request->getGet('issue', 'trim');
        $modes = 0;  //$this->request->getGet('modes', 'floatval');
        $is_test = -1;
        $username = $GLOBALS['SESSION']['username']; //$this->request->getGet('username', 'trim');
        $include_childs = 0; //$this->request->getGet('include_childs', 'intval', 0);

        $this->searchDate($start_time, $end_time, 15, 1);

        if ($wrap_id) {
            $trace_id = traces::dewrapId($wrap_id);
            $traces = traces::getItemsById(array($trace_id), $GLOBALS['SESSION']['user_id']);
            $tracesNumber = 1;
            $packagesTotal = [];
        } else {
            $tracesNumber = traces::getItemsNumber($lottery_id, $is_test, $issue, $modes, $GLOBALS['SESSION']['user_id'], $include_childs, $start_time, $end_time, $status);

            /***************** snow  获取正确的分页开始****************************/
            $curPage  = $this->request->getGet('curPage', 'intval', 1);
            $startPos = getStartOffset($curPage, $tracesNumber);
            /***************** snow  获取正确的分页开始****************************/
            $traces = traces::getItems($lottery_id, $is_test, $issue, $modes, $GLOBALS['SESSION']['user_id'], $include_childs, $start_time, $end_time, $startPos, DEFAULT_PER_PAGE, $status);

            $packagesTotal = projects::getTracePackageTotal($lottery_id, $GLOBALS['SESSION']['user_id'], $status, -1, -1, '', -255, -1, 0, 0, $start_time, $end_time);
        }

        $realAmount = $totalAmount = $totalPrize = $totalProfit = 0;
        if ($traces) {
            //是否显示撤单
            $nowTime = time();
//            $packagesTotal['total_profit'] = $packagesTotal['total_prize'] - $packagesTotal['total_amount'];

            foreach ($traces as $k => $v) {
                $traces[$k]['wrap_id'] = traces::wrapId($v['trace_id'], $v['start_issue'], $v['lottery_id']);
                $totalAmount += $v['total_amount'];
                $traces[$k]['profit'] = 0;
                $traces[$k]['show_cancel'] = false;  //是否显示撤单

                if ($packages = projects::getPackages($lottery_id, -1, -1, '', $v['trace_id'])) {
                    foreach ($packages as $package) {
                        $issueInfo = issues::getItem(0, $package['issue'], $package['lottery_id']);
                        if ($package['cancel_status'] == 0 && $nowTime < strtotime($issueInfo['cannel_deadline_time'])) {
                            $traces[$k]['show_cancel'] = true;
                        }
                        if ($package['cancel_status'] == 0) $realAmount += $package['amount'];
                        if ($package['check_prize_status'] == 1) $totalPrize += $package['prize'];

                        if ($package['cancel_status'] == 0 && $package['check_prize_status'] > 0) {
                            $traces[$k]['profit'] += $package['prize'] - $package['amount'];
//                            $totalProfit += $traces[$k]['profit'];
                        }
                    }
                }
                /*
                                $packages = projects::getPackageTotal($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', $v['trace_id']);
                                $traces[$k]['profit'] = $packages['total_prize'] - $packages['total_amount'];*/
                $totalProfit += $traces[$k]['profit'];
            }
        }

        $user = users::getItem($GLOBALS['SESSION']['username']);

        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname'], 0, -1);
        self::$view->setVar('user', $user);
        self::$view->setVar('lotterys', $lotterys);

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        //self::$view->setVar('issue', $issue);
        //self::$view->setVar('modes', $modes);
        self::$view->setVar('wrap_id', $wrap_id);
        self::$view->setVar('status', $status);
        //self::$view->setVar('username', $username);
        //self::$view->setVar('include_childs', $include_childs);

        self::$view->setVar('traces', $traces);
        self::$view->setVar('tracesNumber', $tracesNumber);

        self::$view->setVar('packagesTotal', $packagesTotal);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('realAmount', $realAmount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('totalProfit', $totalProfit);

        self::$view->setVar('pageList', getPageList($tracesNumber, DEFAULT_PER_PAGE));
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_tracelist');
    }

    //追号详情
    public function traceDetail()
    {
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        if (!$trace_id = traces::dewrapId($wrap_id)) {
            showMsg('参数无效');
        }
        if (!$trace = traces::getItem($trace_id)) {
            showMsg('找不到该追号单');
        }
        $trace['wrap_id'] = $wrap_id;
        if (!$packages = projects::getPackages($trace['lottery_id'], -1, -1, '', $trace['trace_id'], 0, '', 0, '', '', '', '', -1, 'package_id ASC')) {
            showMsg('找不到追号详细列表');
        }
        $isSelf = true;
        $package = reset($packages);
        if ($package['user_id'] != $GLOBALS['SESSION']['user_id']) {
            $isSelf = false;
            //核实是否是其上级
            $parents = users::getAllParent($package['user_id']);
            if (!isset($parents[$GLOBALS['SESSION']['user_id']])) {
                showMsg('您无权查看该追号单');
            }
        }

        $package = reset($packages);
        //得到所有彩种
        $lottery = lottery::getItem($trace['lottery_id']);
        self::$view->setVar('lottery', $lottery);
        //得到奖金系列
        //得到用户返点
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        $prizeMode = 2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $userRebate - $package['cur_rebate']);
        if ($lottery['lottery_id'] == 14) {
            //JYZ-283快乐扑克特殊处理：使用豹子11050代替转直注数,所以整体/24
            $prizeMode = floor($prizeMode / 24);
        }
        self::$view->setVar('prizeMode', $prizeMode);
        //得到奖期截止时间 以决定是否可以用户撤单
        $issues = array_keys(array_spec_key($packages, 'issue'));
        //dump( $issues );
        $issueInfos = issues::getItemsByIssue($trace['lottery_id'], $issues);
        self::$view->setVar('issueInfos', $issueInfos);
        self::$view->setVar('nowTime', date('Y-m-d H:i:s'));
        //得到开奖号码列表
        $openCodes = issues::getCodes($trace['lottery_id'], $issues);
        if ($lottery['property_id'] == 4) {
            $suit_arr = array(
                's' => 'poker_heit',
                'h' => 'poker_hongt',
                'c' => 'poker_meih',
                'd' => 'poker_fangk'
            );
            foreach ($openCodes as $k => $v) {
                $partys = explode(' ', $v);
                $code = array();
                foreach ($partys as $poker) {
                    $code[] = array('num' => str_replace('T', '10', $poker[0]), 'suit' => $suit_arr[$poker[1]]);
                }
                $openCodes[$k] = $code;
            }
        }
        self::$view->setVar('openCodes', $openCodes);

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
                    $codes = explode("_", $v['code']);
                    foreach ($codes as $code) {
                        $codeLevels[] = $klpkLevels[$code];
                    }
                    sort($codeLevels);
                    $prizeLevel = $codeLevels[0];
                }
            }
            //计算如果中奖的奖金
            if ($v['cur_rebate'] > $userRebate) {
                showMsg('系统出错');
            }

            $projects[$k]['will_prize'] = number_format($v['modes'] * $prizes[$v['method_id']][$prizeLevel]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
        }
        $methods = methods::getItemsNew(['method_id', 'cname']);
        //得到彩种玩法组玩法
        self::$view->setVar('methods', $methods);
        self::$view->setVar('trace', $trace);
        self::$view->setVar('projects', $projects);
        self::$view->setVar('packages', $packages);
        self::$view->setVar('isSelf', $isSelf);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_tracedetail');
    }

    //取消追号
    public function cancelTrace()
    {
        $wrap_id = $this->request->getPost('wrap_id', 'trim');
        $package_ids = $this->request->getPost('pkids', 'array');
        if (!$trace_id = traces::dewrapId($wrap_id)) {
            die(json_encode(array('errno' => 201, 'errstr' => '参数无效')));
        }
        if (!is_array($package_ids) || !count($package_ids)) {
            die(json_encode(array('errno' => 202, 'errstr' => '参数无效2')));
        }
        try {
            if (game::cancelTrace($GLOBALS['SESSION']['user_id'], $trace_id, $package_ids)) {
                echo json_encode(array('errno' => 0, 'errstr' => '操作成功!'));
            }
        } catch (exception2 $e) {
            echo json_encode(array('errno' => 1, 'errstr' => $e->getMessage()));
        }
    }

    /**
     * 取消所有能取消的注单
     * @throws exception2
     */
    public function cancelAllTrace()
    {
        $wrap_id = $this->request->getPost('wrap_id', 'trim');
        if (!$trace_id = traces::dewrapId($wrap_id)) {
            die(json_encode(array('errno' => 1, 'errstr' => '参数无效！')));
        }
        $package_ids = [];
        $nowTime = time();
        if ($aPackages = projects::getPackages(0, -1, -1, '', $trace_id)) {
            foreach ($aPackages as $package) {
                if (!$issueInfo = issues::getItem(0, $package['issue'], $package['lottery_id'])) {
                    die(json_encode(array('errno' => 2, 'errstr' => '找不到奖期！')));

                }
                if ($nowTime > strtotime($issueInfo['cannel_deadline_time']) || $package['cancel_status'] > 0) {
                    continue;
                }
                $package_ids[] = $package['package_id'];
            }
        }
        if ($package_ids) {
            game::cancelTrace($GLOBALS['SESSION']['user_id'], $trace_id, $package_ids);
            die(json_encode(array('errno' => 0, 'errstr' => '撤单成功')));
        }

        die(json_encode(array('errno' => 3, 'errstr' => '参数无效！')));
    }


    public function chart()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval', '1');
        $issueNum = $this->request->getGet('issueNum', 'intval', '50');
        $issueNum = ($issueNum != 50 && $issueNum != 100 & $issueNum != 200) ? 50 : $issueNum;

        //取得 奖期信息
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("没有这个奖期信息");
        }

        switch ($lottery['lottery_type']) {
            case 1:
                $groups = array("万位", "千位", "百位", "十位", "个位");
                $vaildnum = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'); //游戏有效号码
                break;
            case 2:
                $groups = array("万位", "千位", "百位", "十位", "个位");
                $vaildnum = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11');
                break;
            case 3:
                $groups = array("红球分布", "蓝球分布");
                $vaildnum[] = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33');
                $vaildnum[] = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16');
                break;
            case 4://福彩3D
                $groups = array("百位", "十位", "个位");
                $vaildnum = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'); //游戏有效号码
                break;
            case 6:
                $vaildnum = array('1', '2', '3', '4', '5', '6');
                $groups = array("奇偶数", "大小数");
                $sumnum = array('3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18');
                $oddeven = $bigsmall = array('0', '1', '2', '3');
                break;
            case 7:
                $vaildnum = array('A', '2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K');
                $forms = array('散牌', '同花', '顺子', '同花顺', '豹子', '对子');
                $colorForms = array('红桃', '梅花', '黑桃', '方块');
                break;
            case 8:
                $groups = array("冠军", "亚军", "三名", "四名", "五名", "六名", "七名", "八名", "九名", "十名");
                $vaildnum = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10');
                break;
            case 9:
                $groups = array("正码", "特码");
                $vaildnum = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49);
                break;
            case 10:
                $groups = array("百位", "十位", "个位");
                $vaildnum = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
                break;
            default :
                showMsg('参数无效');
                break;
        }

        //列出近几期 数据
        $issues = issues::getItems($lottery_id, '', 0, 0, 0, 0, 2, 'issue_id DESC', 0, $issueNum);
        asort($issues);
        if (!$historys = issues::getMissHot($lottery_id, '', 0, $issueNum)) {
            showMsg("没有历史记录数据");
        }

        $codes = array();
        foreach ($issues as $k => $v) {
            $codes[$v['issue_id']]['issue'] = $v['issue'];
            $codes[$v['issue_id']]['issue_id'] = $v['issue_id'];
            $codes[$v['issue_id']]['miss_info'] = isset($historys[$v['issue_id']]['miss_info']) ? unserialize($historys[$v['issue_id']]['miss_info']) : array();

            switch ($lottery['lottery_type']) {
                case 1:
                case 10:
                    $codes[$v['issue_id']]['openNumber'] = str_split($v['code']);
                    break;
                case 2:
                    $tmp = $codes[$v['issue_id']]['openNumber'] = explode(" ", $v['code']);
                    break;
                case 3:
                    $tmp = $codes[$v['issue_id']]['openNumber'] = explode(" ", $v['code']);
                    $codes[$v['issue_id']]['redNumber'] = array_slice($tmp, 0, 6);
                    $codes[$v['issue_id']]['blueNumber'] = $tmp[6];
                    break;
                case 4:
                    $codes[$v['issue_id']]['openNumber'] = str_split($v['code']);
                    break;
                case 6:
                    $codes[$v['issue_id']]['openNumber'] = str_split($v['code']);
                    $codes[$v['issue_id']]['sum'] = array_sum(str_split($v['code']));
                    $oddnum = $evennum = $bignum = $smallnum = 0;
                    for ($i = 0, $len = strlen($v['code']); $i < $len; $i++) {
                        if ($v['code'][$i] % 2 == 0) {
                            $evennum++;
                        } else {
                            $oddnum++;
                        }
                        if ($v['code'][$i] <= 3) {
                            $smallnum++;
                        } else {
                            $bignum++;
                        }
                    }
                    $codes[$v['issue_id']]['oddnum'] = $oddnum;
                    $codes[$v['issue_id']]['evennum'] = $evennum;
                    $codes[$v['issue_id']]['bignum'] = $bignum;
                    $codes[$v['issue_id']]['smallnum'] = $smallnum;
                    $codes[$v['issue_id']]['miss_k3'] = isset($historys[$v['issue_id']]['miss_k3']) ? unserialize($historys[$v['issue_id']]['miss_k3']) : array();
                    break;
                case 7:
                    $codes[$v['issue_id']]['openNumber'] = explode(" ", $v['code']);
                    $codes[$v['issue_id']]['form'] = methods::getPokerForm($v['code']);
                    $codes[$v['issue_id']]['miss_poker'] = isset($historys[$v['issue_id']]['miss_poker']) ? unserialize($historys[$v['issue_id']]['miss_poker']) : array();
                    break;
                case 8:
                case 9:
                    $tmp = $codes[$v['issue_id']]['openNumber'] = explode(" ", $v['code']);
                    $codes[$v['issue_id']]['zhengNumber'] = array_slice($tmp, 0, 6);
                    $codes[$v['issue_id']]['teNumber'] = $tmp[6];
                    break;
                default :
                    showMsg('参数无效');
                    break;
            }
        }

        $codeGroup = array();
        if ($lottery['lottery_type'] != 7) {
            $totalGroup = count($groups);
            for ($i = 0; $i < $totalGroup; $i++) {//号码组
                $codeGroup[] = array(
                    "name" => $groups[$i],
                    'wei' => $i,
                    'ballstytle' => $i % 2 + 1,
                    'normalstytle' => $i % 2 + 3
                );
            }

            self::$view->setVar('totalGroup', $totalGroup);
            self::$view->setVar('codeGroup', $codeGroup);
        }

        self::$view->setVar('lottery', $lottery);
        self::$view->setVar('vaildnum', $vaildnum);
        self::$view->setVar('totalNum', count($vaildnum));
        self::$view->setVar('codes', $codes);
        self::$view->setVar('issueNum', $issueNum);

        switch ($lottery['lottery_type']) {
            case 3:
                self::$view->render('game_ssq_chart');
                break;
            case 6:
                $scodeGroup = array(
                    'name' => '和值走势',
                    'wei' => 0,
                    'ballstytle' => 1,
                    'normalstytle' => 3
                );
                self::$view->setVar('scodeGroup', $scodeGroup);
                self::$view->setVar('sumnum', $sumnum);
                self::$view->setVar('oddeven', $oddeven);
                self::$view->render("game_k3_chart");
                break;
            case 7:
                self::$view->setVar('colorForms', $colorForms);
                self::$view->setVar('forms', $forms);
                self::$view->render('game_poker_chart');
                break;
            case 9:
                self::$view->render('game_lhc_chart');
                break;
            default:
                self::$view->render('game_chart');
        }
    }
}
