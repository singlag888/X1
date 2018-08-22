<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：电子游戏管理
 */
class egameController extends sscController
{
    public $titles = array(
        'play' => '参与游戏',
        'lobby' => '游戏大厅',
        'transfer' => '额度转换',
        'userInfo' => '用户信息',
        'gameRecodeList' => '电子游戏记录',
        'transferRecodeList' => '转账记录',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
        $this->getUser();
        $this->getNotics();
        $this->getNotReadMsgNum();
    }

    function lobby()
    {
        $systemStatus = egameMwSettings::getItem('system_status');

        if (empty($systemStatus) || $systemStatus === null || !isset($systemStatus['value'])) {
            showMsg('系统状态异常');
        }

        if ($systemStatus['value'] === '1') {
            showMsg('系统维护');
        }

        $mw = new MW();
        $gameInfo = $mw->getGameInfo();
        $domain = $mw->getDomain();
        $enableGames = egameGameSet::getItems(0, 1);
        $gameList = array();

        if (isset($gameInfo['ret']) && $gameInfo['ret'] == '0000') {
            foreach ($gameInfo['games'] as $key => $value) {
                if (isset($enableGames[$value['gameId']])) {
                    $gameList[$value['gameId']] = $value;
                }
            }

            $GLOBALS['nav'] = 'egame';

            self::$view->setVar('games', $gameList);
            self::$view->setVar('domain', $domain);
            self::$view->render('egame_lobby');
        } else {
            showMsg('从 MW 获取游戏列表失败');
        }
    }

    function play()
    {
        $systemStatus = egameMwSettings::getItem('system_status');

        if (empty($systemStatus) || $systemStatus === null || !isset($systemStatus['value'])) {
            showMsg('系统状态异常');
        }

        if ($systemStatus['value'] === '1') {
            showMsg('系统维护');
        }

        $mw = new MW();
        $uid = $GLOBALS['SESSION']['user_id'];
        $username = $GLOBALS['SESSION']['username'];
        $gameId = $this->request->getGet('gameId', 'trim');
        $game = egameGameSet::getItem($gameId);

        if ($game['status'] != 1) {
            showMsg('此游戏已关闭');
        }

        $user = users::getItem($uid);
        $response = $mw->oauth($uid, $user['parent_id'], $user['top_id'], $username, $gameId);
        $domain = $mw->getDomain();

        header('location:' . $domain . $response['interface']);
    }

    public function userInfo()
    {
        $uid = $GLOBALS['SESSION']['user_id'];
        $username = $GLOBALS['SESSION']['username'];
        $user = users::getItem($uid);
        $mw = new MW();

        die($mw->userInfo($uid, $user['parent_id'], $user['top_id'], $username));
    }

    function transfer()
    {
        $systemStatus = egameMwSettings::getItem('system_status');

        if (empty($systemStatus) || $systemStatus === null || !isset($systemStatus['value'])) {
            showMsg('系统状态异常');
        }

        if ($systemStatus['value'] === '1') {
            showMsg('系统维护');
        }

        self::$view->render('egame_transfer');
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

    public function gameRecodeList()
    {
        $game_id = $this->request->getGet('game_id', 'intval');
        $sub_user = $this->request->getGet('sub_user', 'trim');
        $this->searchDate($start_time, $end_time, 15, 1);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $user = users::getItem($GLOBALS['SESSION']['user_id']);
        $games = egameGameSet::getItems();
        $packages = null;
        $totalAmount = 0;
        $totalPrize = 0;
        $packagesTotal = array();
        $pageCount = 0;
        $curPage = $this->request->getGet('curPage', 'intval', 1);//>>snow 获取当前页
        if (empty($sub_user)) {
            $count = egameMwSiteUsergamelog::getUserLogsGroupCount($start_time, $end_time, 0, $user['user_id'], XY_PREFIX );
            /********************* snow 获取正确的分页开始******************************/
            $startPos = getStartOffset($curPage, $count[0]['count']);
            /********************* snow 获取正确的分页开始******************************/
            $packages = egameMwSiteUsergamelog::getUserLogs($start_time, $end_time, 0, $user['user_id'], XY_PREFIX , $startPos, DEFAULT_PER_PAGE);
            $pageCount = $count[0]['count'];

            foreach ($packages as $key => $value) {
                $totalAmount += $value['play_money'];
                $totalPrize += $value['win_money'];
            }

            $totalProfit =  $totalPrize - $totalAmount;

            if (isset($count[0]['total_play_money'])) {
                $packagesTotal['total_amount'] = $count[0]['total_play_money'];
                $packagesTotal['total_prize'] = $count[0]['total_win_money'];
                $packagesTotal['total_profit'] = $packagesTotal['total_prize'] - $packagesTotal['total_amount'];
                $packagesNumber = $count[0]['count'];
            } else {
                $packagesTotal['total_amount'] = 0;
                $packagesTotal['total_prize'] = 0;
                $packagesTotal['total_profit'] = 0;
                $packagesNumber = 0;
            }
        } else {
            $count = egameMwSiteUsergamelog::getUserLogsGroupCountByUserId($start_time, $end_time, 0, $user['user_id'], XY_PREFIX );

            /********************* snow 获取正确的分页开始******************************/
            $totalCount = isset($count[0]['count']) ? $count[0]['count'] : 0;
            $startPos = getStartOffset($curPage, $totalCount);
            /********************* snow 获取正确的分页开始******************************/
            $packages = egameMwSiteUsergamelog::getUserLogsGroupByUserId($start_time, $end_time, 0, $user['user_id'], XY_PREFIX , $startPos, DEFAULT_PER_PAGE);
            $pageCount = empty($count) ? 0 : count($count);

            foreach ($packages as $key => $value) {
                $totalAmount += $value['total_play_money'];
                $totalPrize += $value['total_win_money'];
            }

            $totalProfit =  $totalPrize - $totalAmount;

            if (isset($count[0]['total_play_money'])) {
                $packagesTotal['total_amount'] = $count[0]['total_play_money'];
                $packagesTotal['total_prize'] = $count[0]['total_win_money'];
            } else {
                $packagesTotal['total_amount'] = 0;
                $packagesTotal['total_prize'] = 0;
            }

            $packagesTotal['total_profit'] =  $packagesTotal['total_prize'] - $packagesTotal['total_amount'] ;
            $packagesNumber = empty($count) ? 0 : count($count);
        }

        // 预设查询框
        self::$view->setVar('packagesNumber', $packagesNumber);
        self::$view->setVar('packagesTotal', $packagesTotal);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('totalProfit', $totalProfit);
        self::$view->setVar('sub_user', $sub_user);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('game_id', $game_id);
        self::$view->setVar('games', $games);
        self::$view->setVar('packages', $packages);
        self::$view->setVar('pageList', getPageList($pageCount, DEFAULT_PER_PAGE));
        self::$view->render('egame_gamerecodelist');
    }

    public function transferRecodeList()
    {
        $this->searchDate($start_time, $end_time, 15, 1);
        $action = $this->request->getGet('orderType', 'trim');
        $sub_user = $this->request->getGet('sub_user', 'trim');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $curPage  = $this->request->getGet('curPage', 'intval', 1) ;//>>获取当前页
        $user = users::getItem($GLOBALS['SESSION']['user_id']);

        $options = [
            'startDate' => $start_time,
            'endDate' => $end_time,
            'transfer_id' => '',
            'userName' => $user['username'],
            'include_childs' => $sub_user == 'on' ? 1 : 0,
            'top_id' => '',
            'amount_start' => '',
            'amount_end' => '',
            'status' => '',
            'action' => $action,
            'platform' => 4,
            'curPage'  => $curPage,
            'pageSize' => DEFAULT_PER_PAGE,
        ];

        $packages = null;
        $totalAmount = 0;
        $totalPrize = 0;
        $totalProfit = 0;
        $packagesNumber = 0;
        $packagesTotal = array();
        $orders = transfers::getTransfersList($options);
        $packages = $orders['pageResult'];

        // 预设查询框
        self::$view->setVar('actionName', array(
            'IN' => '转入',
            'OUT' => '转出',
        ));

        self::$view->setVar('packagesNumber', $packagesNumber);
        self::$view->setVar('packagesTotal', $packagesTotal);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('totalProfit', $totalProfit);
        self::$view->setVar('sub_user', $sub_user);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('action', $action);
        self::$view->setVar('packages', $packages);
        self::$view->setVar('pageList', getPageList($orders['totalConut'], DEFAULT_PER_PAGE));
        self::$view->render('egame_transferrecodelist');
    }

}