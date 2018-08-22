<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：电子游戏管理
 */
class egameController extends sscAdminController
{
    // 方法概览
    public $titles = array(
        'gameSet' => '游戏设定',
        'gameStatusEdit' => '游戏状态修改',
        'userGameLog' => '用户游戏日志',
        'merchantGameReport' => '代理商报表',
        'translateList' => '财务报表',
        'rakeback' => '手动返水',
        'balance' => '电子余额',
        'systemStatus' => '系统状态',
        'transfer' => '额度转换',

    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function gameSet()
    {
        $mw = new MW();
        $gameInfo = $mw->getGameInfo();

        if (isset($gameInfo['ret']) && $gameInfo['ret'] == '0000') {
            $games = egameGameSet::getItems();

            foreach ($gameInfo['games'] as $key => $value) {
                $games[$value['gameId']]['mw_status'] = $value['gameState'];
            }

            self::$view->setVar('games', $games);
            self::$view->render('egame_game_set');
        } else {
            die('从 MW 获取游戏列表错误');
        }
    }

    public function gameStatusEdit()
    {
        $id = $this->request->getPost('id', 'intval');

        if ($id < 0) {
            die(json_encode(array('error_no' => '1' , 'error_message' => '用户 ID 参数错误')));
        }

        $status = $this->request->getPost('status', 'intval');

        if ($status < 0) {
            die(json_encode(array('error_no' => '2' , 'error_message' => '状态参数错误')));
        }

        $data = [
            'status' => $status,
        ];

        if (egameGameSet::updateItem($id, $data)) {
            $GLOBALS['mc']->flush();
            die(json_encode(array('error_no' => '0' , 'error_message' => '修改成功')));
        } else {
            die(json_encode(array('error_no' => '3' , 'error_message' => '修改失败')));
        }
    }

    public function userGameLog()
    {
        $action = $this->request->getGet('action', 'trim');

        if ($action == 'getPage') {
            self::$view->setVar('start_time', date("Y-m-d 00:00:00"));
            self::$view->setVar('end_time', date("Y-m-d 23:59:59"));
            self::$view->render('egame_usergamelog');
            die();
        }

        $username = $this->request->getGet('username', 'trim');
        $user_id = 0;

        if ($username != '') {
            $user = users::getItemByUsername($username);

            if (empty($user)) {
                showMsg('用户不存在');
            } else {
                $user_id = $user['user_id'];
            }
        }

        $is_test = $this->request->getGet('is_test', 'intval', -1);
        $beginTime = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        $endTime = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $totalPlayAmount = 0;
        $totalPlayWin = 0;
        //>>最后一个参数不用的,不想改它的,不好 snow.
        $count = egameMwSiteUsergamelog::getUserLogsCount($beginTime, $endTime, $is_test, $user_id, XY_PREFIX,-1 );
        /******************** snow  修改获取正确页码值******************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $count[0]['count']);
        /******************** snow  修改获取正确页码值******************************/
        $userLogs = egameMwSiteUsergamelog::getUserLogs($beginTime, $endTime, $is_test, $user_id, XY_PREFIX , $startPos, DEFAULT_PER_PAGE);
        $sum = egameMwSiteUsergamelog::getUserLogsSum($beginTime, $endTime, $is_test, $user_id, XY_PREFIX , $startPos, DEFAULT_PER_PAGE);

        if (isset($sum[0]['total_play_money']) && $sum[0]['total_play_money'] != null) {
            $totalPlayAmount = $sum[0]['total_play_money'];
            $totalPlayWin = $sum[0]['total_win_money'];
        }

        if (isset($sum[0]['total_win_money']) && $sum[0]['total_win_money'] != null) {
            $totalPlayWin = $sum[0]['total_win_money'];
        }

        $totalPlayJifenAmount = number_format($totalPlayAmount - $totalPlayWin, 2);

        self::$view->setVar('start_time', $beginTime);
        self::$view->setVar('end_time', $endTime);
        self::$view->setVar('username', $username);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('totalPlayAmount', $totalPlayAmount);
        self::$view->setVar('totalPlayJifenAmount', $totalPlayJifenAmount);
        self::$view->setVar('totalPlayWin', $totalPlayWin);
        self::$view->setVar('userGameLog', $userLogs);
        self::$view->setVar('pageList', getPageList($count[0]['count'], DEFAULT_PER_PAGE));
        self::$view->render('egame_usergamelog');
    }

    public function merchantGameReport()
    {
        $action = $this->request->getPost('action', 'trim');

        if ($action == '') {
            self::$view->setVar('start_time', date("Y-m-d 00:00:00"));
            self::$view->setVar('end_time', date("Y-m-d 23:59:59"));
            self::$view->render('egame_merchantgamereport');
            die();
        }

        $username = $this->request->getPost('username', 'trim');
        $user = null;

        if ($username != '') {
            $user = users::getItemByUsername($username);

            if (empty($user)) {
                showMsg('代理商不存在');
            }
        }

        $is_test = $this->request->getPost('is_test', 'intval', -1);
        $user_id = $user == null ? 0 : $user['user_id'];
        $beginTime = $this->request->getPost('start_time', 'trim', date("Y-m-d 00:00:00"));
        $endTime = $this->request->getPost('end_time', 'trim', date("Y-m-d 23:59:59"));

        $merchantsGm = egameMwSiteUsergamelog::getTopLogs($beginTime, $endTime, $is_test, $user_id, XY_PREFIX);
        $totalPlayAmount = 0;
        $playWin = 0;

        foreach ($merchantsGm as $key => $value) {
            $totalPlayAmount += $value['play_money'];
            $playWin += $value['win_money'];
        }

        $playJifenAmount = $totalPlayAmount - $playWin;
        $tops = users::getAllAgentsWithId(-1, $is_test, 1);

        self::$view->setVar('tops', $tops);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('start_time', $beginTime);
        self::$view->setVar('end_time', $endTime);
        self::$view->setVar('username', $username);
        self::$view->setVar('totalPlayAmount', $totalPlayAmount);
        self::$view->setVar('totalPlayJifenAmount', $playJifenAmount);
        self::$view->setVar('totalPlayWin', $playWin);
        self::$view->setVar('merchantsGm', $merchantsGm);
        self::$view->render('egame_merchantgamereport');
    }

    /**
     * 资金往来报表 by snow 2017-09-09 17:20
     */
    public function translateList()
    {
        $curPage        = $this->request->getGet('curPage','intval',1);//»获取当前分页的页码,如果为空则为 1
        $pageSize       = DEFAULT_PER_PAGE;        //»获取分页开始数据
        //»开始时间
        $startDate      = $this->request->getGet('start_time', 'trim', date("Y-m-d") . ' 00:00:00');
        //»结束时
        $endDate        = $this->request->getGet('end_time', 'trim', date("Y-m-d") . ' 23:59:59');
        //»对时间进行验证有效性
        if( !strtotime($startDate) || !strtotime($endDate)){
            showMsg('开始时间或者结束时间格式不正确');exit;
        }
        if($startDate > $endDate){
            list($startDate, $endDate) = [ $endDate, $startDate ];
        }
        $transfer_id    = $this->request->getGet('transfer_id', 'intval');
        $user_id        = $this->request->getGet('user_id', 'intval');
        $userName       = $this->request->getGet('username', 'trim');
        $top_id         = $this->request->getGet('top_id', 'intval');
        $amount_start   = $this->request->getGet('start_amount', 'floatval', 0);
        $amount_end     = $this->request->getGet('end_amount', 'floatval', 0);
        $status         = $this->request->getGet('status', 'intval');
        $action         = $this->request->getGet('action', 'trim');
        $platform       = $this->request->getGet('platform', 'intval');
        $include_childs = $this->request->getGet('include_childs', 'intval');
        $options = [
            'startDate'         =>  $startDate,
            'endDate'           =>  $endDate,
            'transfer_id'       =>  $transfer_id,
            'userName'          =>  $userName,
            'include_childs'    =>  $include_childs,
            'top_id'            =>  $top_id,
            'amount_start'      =>  $amount_start,
            'amount_end'        =>  $amount_end,
            'status'            =>  $status,
            'action'            =>  $action,
            'platform'          =>  $platform,
            'curPage'           =>  $curPage,//>>当前页面
            'pageSize'          =>  $pageSize,
        ];
        $orders         = transfers::getTransfersList($options);
        $isCanSearch    = $orders['pageResult'] === false ? false : true;
        //日期列表
        $dates = array();
        for ($i = 0; $i < 30; $i++) {
            $dates[] = date('Y-m-d', time() - 86400 * $i);
        }
        self::$view->setVar('options', $options);
//        echo "<pre>";
//        var_dump($_SERVER,$_SERVER['REQUEST_URI']);exit;
        //预设查询框
        self::$view->setVar('actionName', array(
            'IN' => '转入',
            'OUT' => '转出',
            ));
        self::$view->setVar('top_id', $top_id);
        self::$view->setVar('orders', $orders);
        self::$view->setVar('url', $_SERVER['REQUEST_URI']);
        self::$view->setVar('isCanSearch', $isCanSearch);
        self::$view->setVar('pageList', getPageList($orders['totalConut'], DEFAULT_PER_PAGE));
        self::$view->render('egame_translatelist');
    }

    public function rakeback()
    {
        $ref_group_id = $this->request->getGet('ref_group_id', 'intval');

        if ($ref_group_id < 1) {
            showMsg("非法请求，卡组 ID 异常");
        }

        $egame_commission_percentage = $this->request->getGet('egame_commission_percentage', 'floatval');

        if ($egame_commission_percentage == 0) {
            showMsg("非法请求，返点不能为 0");
        }

        $rakebackUsers = users::getEgameRakebackUsers($ref_group_id);

        foreach ($rakebackUsers as $key => $value) {
            $startTime = $value['last_rakeback_time'];
            $endTime = date("Y-m-d 23:59:59", strtotime("-1 day"));

            if(strtotime($endTime) <= strtotime($startTime)) {
                continue;
            }

            $response = egameMwSiteUsergamelog::getUserLogsSum($startTime, $endTime, 0, $value['user_id'], XY_PREFIX, -1, DEFAULT_PER_PAGE);

            if ($response[0]['total_play_money'] == null) {
                continue;
            }

            $rakebackAmount = $response[0]['total_play_money'] * $egame_commission_percentage;

            // 1增加用户帐变
            $orderData = array(
                'lottery_id' => 0,
                'issue' => '',
                'from_user_id' => $key,
                'from_username' => $value['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => 701,
                'amount' => $rakebackAmount,
                'pre_balance' => $value['balance'],
                'balance' => $value['balance'] + $rakebackAmount,
                'create_time' => date('Y-m-d H:i:s'),
                'business_id' => 0,
                'admin_id' => $GLOBALS['SESSION']['admin_id'],
            );

            if (!orders::addItem($orderData)) {
                showMsg("添加帐变出错");
            }

            if (!users::updateBalance($key, $rakebackAmount)) {
                showMsg("更新余额错误");
            }

            if (!egameRakeback::updateItem($key, array(
                'last_rakeback_time' => $endTime,
                'rakeback_amount' => $rakebackAmount
            ))) {
                showMsg("更新返水时间错误");
            }

        }

        showMsg("返水成功");
    }

    public function balance()
    {
        $action = $this->request->getGet('action', 'trim');

        if ($action === '') {
            self::$view->render('egame_balance');
            die();
        }

        $username = $this->request->getGet('username', 'trim');

        if ($username === '') {
            showMsg('用户名不能为空');
        }

        $user = users::getItemByUsername($username);

        if (empty($user)) {
            showMsg('用户不存在');
        }

        $mw = new MW();
        $response = $mw->userInfo($user['user_id'], $user['parent_id'], $user['top_id'], $user['username']);
        $response = json_decode($response, true);
        $balance = 0;

        if ($response !== null && isset($response['money'])) {
            $balance = $response['money'];
        }

        self::$view->setVar('username', $user['username']);
        self::$view->setVar('balance', $balance);
        self::$view->render('egame_balance');
    }

    public function systemStatus()
    {
        $action = $this->request->getPost('action', 'trim');

        if ($action === '') {
            $systemStatus = egameMwSettings::getItem('system_status');

            if (empty($systemStatus) || $systemStatus === null) {
                showMsg('获取系统状态错误');
            }

            self::$view->setVar('systemStatus', $systemStatus);
            self::$view->render('egame_mw_system_status');
        } elseif ($action === 'edit') {
            $status = $this->request->getPost('status', 'intval');

            if ($status < 0) {
                die(json_encode(array('error_no' => '1' , 'error_message' => '状态参数错误')));
            }

            $name = $this->request->getPost('name', 'trim');

            if ($name == '') {
                die(json_encode(array('error_no' => '2' , 'error_message' => '名称参数错误')));
            }

            $data = [
                'value' => $status,
            ];

            if (egameMwSettings::updateItem($name, $data)) {
                $GLOBALS['mc']->flush();
                die(json_encode(array('error_no' => '0' , 'error_message' => '修改成功')));
            }

            die(json_encode(array('error_no' => '3' , 'error_message' => '修改失败')));
        }
    }

    public function transfer()
    {
        $action = $this->request->getGet('action', 'trim');

        if ($action === '') {
            self::$view->render('egame_transfer');
        } elseif ($action === 'transfer') {
            if (!is_numeric($tranFrom = $this->request->getGet('tranFrom', 'intval'))) {
                showMsg('请选择转出方');
            }

            if (!is_numeric($tranTo = $this->request->getGet('tranTo', 'intval'))) {
                showMsg('请选择转入方');
            }

            if (!is_numeric($tranAmount = $this->request->getGet('tranAmount', 'intval'))) {
                showMsg('请输入正确金额');
            }

            if (empty($username = $this->request->getGet('username', 'trim'))) {
                showMsg('请输入正确金额');
            }

            $user = users::getItemByUsername($username);

            if ($tranFrom === 1 && $tranTo === 4) {
                try {
                    $systemStatus = egameMwSettings::getItem('system_status');

                    if (empty($systemStatus) || $systemStatus === null || !isset($systemStatus['value'])) {
                        showMsg('系统状态异常，请联系咨询相关客服');
                    }

                    if ($systemStatus['value'] === '1') {
                        showMsg('系统维护，请联系咨询相关客服');
                    }

                    if (transfers::transferInMW($user['user_id'], $tranAmount)) {
                        showMsg('转入成功');
                    }

                    showMsg('资金转移出错，请联系咨询相关客服');
                } catch (Exception $ex) {
                    showMsg($ex->getMessage());
                }
            } elseif ($tranFrom === 4 && $tranTo === 1) {
                try {
                    $systemStatus = egameMwSettings::getItem('system_status');

                    if (empty($systemStatus) || $systemStatus === null || !isset($systemStatus['value'])) {
                        showMsg('系统状态异常，请联系咨询相关客服');
                    }

                    if ($systemStatus['value'] === '1') {
                        showMsg('系统维护，请联系咨询相关客服');
                    }

                    if (transfers::transferOutMW($user['user_id'], $tranAmount)) {
                        showMsg('转出成功');
                    }

                    showMsg('资金转移出错，请联系咨询相关客服');
                } catch (Exception $ex) {
                    showMsg($ex->getMessage());
                }
            } else {
                showMsg('非法的转入转出方');
            }
        }
    }


}