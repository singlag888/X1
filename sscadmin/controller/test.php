<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：测试流程
 */
class testController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'userStat' => '测试统计图表',
        'testBuy' => '测试投注',
        'testRebate' => '测试返点',
        'testPT' => '测试PT接口',
        'testFilter' => '测试过滤器',
    );
    static $lotteryTypes = array(
        '1' => 'Digital',
        '2' => 'Lotto',
        '3' => '乐透分区型（蓝红球）',
        '5' => 'keno',
        '6' => 'quick3',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function testPT()
    {
        $PT = new PT();
        $username = $this->request->getPost('username', 'trim');
        $password = $this->request->getPost('password', 'trim');
        $newpassword = $this->request->getPost('newpassword', 'trim');
        $amount = $this->request->getPost('amount', 'trim');

        $specialUser = $this->request->getPost('specialUser', 'intval', 0);
        $startDate = $this->request->getPost('startDate', 'trim');
        $endDate = $this->request->getPost('endDate', 'trim');
        switch ($this->request->getPost('op', 'trim')) {
            case 'createPlayer':
                $result = $PT->createPlayer($username, $password);
                die();
                break;
            case 'updatePlayer':    //改密码
                $result = $PT->updatePlayer($username, $newpassword);
                die();
                break;
            case 'getPlayerInfo':
                $result = $PT->getPlayerInfo($username);
                die();
                break;
            case 'isPlayerOnline':
                $result = $PT->isPlayerOnline($username);
                die();
                break;
            case 'logoutPlayer':
                $result = $PT->logoutPlayer($username);
                die();
                break;
            case 'resetFailedLoginPlayer':
                $result = $PT->resetFailedLoginPlayer($username);
                die();
                break;
            case 'transferIn':
                $ref_id = "D" . date('YmdHis00') . rand(1000, 9999) . "E";
                $result = $PT->transferIn($username, $amount, $ref_id);
                die();
                break;
            case 'transferOut':
                $ref_id = "W" . date('YmdHis00') . rand(1000, 9999) . "E";
                $result = $PT->transferOut($username, $amount, $ref_id);
                die();
                break;
            case 'gameStats':
                $result = $PT->gameStats($specialUser ? $username : '', $startDate, $endDate);
                die();
                break;
            case 'playerStats':
                $byPlayer = false;
                $result = $PT->playerStats($specialUser ? $username : '', $startDate, $endDate, $byPlayer);
                die();
                break;
            case 'playerGames':
                $result = $PT->playerGames($username, $startDate, $endDate);
                die();
                break;
        }

        self::$view->render('test_testpt');
    }

    public function userStat()
    {
        if (!$GLOBALS['xc']->exists('method', 'count1')) {
            dump('新设置缓存，保存20秒');
            $GLOBALS['xc']->set('method', 'count1', 1, 20);
        }
//        dump($GLOBALS['xc']->get('method', 'count1'));
//        dump($GLOBALS['xc']->get('method', 'count123'));
//        $GLOBALS['xc']->inc('method', 'count1', 1);
//        dump($GLOBALS['xc']->get('method', 'count1'));
//        $GLOBALS['xc']->dec('method', 'count1', 1);
//        dump($GLOBALS['xc']->get('method', 'count1'));
//        dump($GLOBALS['xc']->sets('method', array('count1' => '111', 'count2' => '222', )));
//        $noCacheKeys = array();
//        dump($noCacheKeys === NULL);
//        dump($GLOBALS['xc']->gets('method', array('count1', 'count2', 'count3'), $noCacheKeys), $noCacheKeys);

        dump(prizes::getItem(315));
        $ids = array(313, 314, 315, 316);
        dump(prizes::getItemsById($ids));
        print_r($GLOBALS['_PROFILE']);
        dump($GLOBALS['xc']->gets('prizes', $ids));
        $GLOBALS['xc']->delete('prizes', 315);
        dump($GLOBALS['xc']->gets('prizes', $ids));
        $GLOBALS['xc']->deletes('prizes', $ids);
        dump($GLOBALS['xc']->gets('prizes', $ids));

//        $ids = array(316,323,324,325);
//        dump(prizes::getItemsById($ids)); print_r($GLOBALS['_PROFILE']);

        $show1 = $this->request->getGet('show1', 'intval');
        $show2 = $this->request->getGet('show2', 'intval');
        $show3 = $this->request->getGet('show3', 'intval');
        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d', time()));
        if (!$show1 && !$show2 && !$show3) {
            $show1 = $show2 = $show3 = 1;
        }
        self::$view->setVar('show1', $show1);
        self::$view->setVar('show2', $show2);
        self::$view->setVar('show3', $show3);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        if ($this->request->getGet('op', 'trim') == 'getXML') {
            $fc = new flashChart();
            $nowTime = time();
            $totalDay = ceil((strtotime($endDate) - strtotime($startDate)) / 86400);

            //底部总共显示数
            $days = array();
            for ($i = strtotime($startDate); $i < $nowTime; $i += 86400) {
                $days[] = date('y-m-d', $i);
            }
            $fc->addLabels($days);
            //底部显示几栏标签 一般为10
            $labelstep = ceil($totalDay / 10);
            $fc->setChart('labelstep', $labelstep);

            //模拟生成1号数据
            if ($show1) {
                $tmp = array();
                for ($k = 0, $i = strtotime($startDate); $i < $nowTime; $i += 86400, $k++) {
                    $tmp[] = rand(200, 200 + 10 * $k);
                }
                $fc->addData($tmp, '用户总数');
            }

            //模拟生成2号数据
            if ($show2) {
                $tmp = array();
                for ($k = 0, $i = strtotime($startDate); $i < $nowTime; $i += 86400, $k++) {
                    $tmp[] = rand(100, 100 + $k * 2);
                }
                $fc->addData($tmp, '参与游戏用户数', 'FF0000');
            }

            //模拟生成3号数据
            if ($show3) {
                $tmp = array();
                for ($k = 0, $i = strtotime($startDate); $i < $nowTime; $i += 86400, $k++) {
                    $tmp[] = rand(20, 20 + $k * 2);
                }
                $fc->addData($tmp, '中奖用户数', '00FF00');
            }
            $fc->display();
            exit;
        }

        self::$view->render('test_userstat');
    }

    public function testBuy()
    {
//        issues::updateMissHot(2, 37128);
//        die();
        //ajax
        if ($this->request->getPost('op', 'trim') == 'getUserRebate') {
            $user_id = $this->request->getPost('user_id', 'intval');
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $lottery = lottery::getItem($lottery_id);
            $rebate = userRebates::getUserRebate($user_id, $lottery['property_id']);

            die($rebate);
        }

        //ajax
        if ($this->request->getPost('op', 'trim') == 'getSingleNum') {
            $method_id = $this->request->getPost('method_id', 'intval');
            $code = $this->request->getPost('code', 'trim');
            $result = array('errno' => -1, 'errstr' => '');
            if ($method = methods::getItem($method_id)) {
                if ($singleNums = methods::isLegalCode($method, $code)) {
                    $result['errno'] = 0;
                    $result['single_num'] = $singleNums;
                }
            }

            die(json_encode($result));
        }

        if ('buy' == $this->request->getPost('op', 'trim')) {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $method_id = $this->request->getPost('method_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $curRebate = $this->request->getPost('curRebate', 'floatval');
            $modes = $this->request->getPost('modes', 'floatval');
            $user_id = $this->request->getPost('user_id', 'intval');
            $codes = $this->request->getPost('codes', 'array');
            $multiple = $this->request->getPost('multiple', 'intval');
            //$traceNum = $this->request->getPost('traceNum', 'intval');
            $traceDetails = $this->request->getPost('traceDetails', 'array');
            $stopOnWin = $this->request->getPost('stopOnWin', 'intval');

            //预设查询框
            self::$view->setVar('lottery_id', $lottery_id);
            self::$view->setVar('method_id', $method_id);
            self::$view->setVar('issue', $issue);
            self::$view->setVar('curRebate', $curRebate);
            self::$view->setVar('modes', $modes);
            self::$view->setVar('user_id', $user_id);

            $result = array('errno' => 0, 'errstr' => '');
            try {
                if ($traceDetails) {    //追号情况
                    foreach ($traceDetails as $v) {
                        if (!is_numeric($v) || $v <= 0) {
                            throw new exception2('不正确的追号倍数', 1);
                        }
                    }

                    game::trace($lottery_id, $curRebate, $modes, $user_id, $codes, $multiple, $traceDetails, $stopOnWin);
                }
                else {
                    game::buy($lottery_id, $issue, $curRebate, $modes, $user_id, $codes, $multiple);
                }
            } catch (exception2 $e) {
                $result['errno'] = $e->getCode();
                $result['errstr'] = $e->getMessage();
            }
//logdump($result);
            die(json_encode($result));
        }

        //得到所有彩种
        $lotterys = lottery::getItems();
        self::$view->setVar('lotterys', $lotterys)->setVar('json_lotterys', json_encode($lotterys));
        //得到彩种玩法组玩法二级联动
        $methods = methods::getItems(0, 0, -1, 2);
        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0))->setVar('json_methods', json_encode($methods));
        //得到所有用户
        $users = users::getItems();
        self::$view->setVar('json_users', json_encode($users));

        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('test_testbuy');
    }

    public function testRebate()
    {
        //ajax 得到该奖期所有订单 $packages
        $op = $this->request->getPost('op', 'trim');
        if ($op == 'getPackage') {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $packages = projects::getPackages($lottery_id, -1, -1, $issue, 0);

            die(json_encode($packages));
        }
        elseif ('doRebate' == $op) {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $package_id = $this->request->getPost('package_id', 'intval');

            $result = array('errno' => 0, 'errstr' => '');
            try {
                game::rebate($lottery_id, $issue, array($package_id));
            } catch (exception2 $e) {
                $result['errno'] = $e->getCode() ? $e->getCode() : 1000;
                $result['errstr'] = $e->getMessage();
            }
//logdump($result);
            die(json_encode($result));
        }
        elseif ('doCancelRebate' == $op) {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $package_id = $this->request->getPost('package_id', 'intval');

            $result = array('errno' => 0, 'errstr' => '');
            try {
                game::cancelRebate($lottery_id, $issue, $package_id);
            } catch (exception2 $e) {
                $result['errno'] = $e->getCode() ? $e->getCode() : 1000;
                $result['errstr'] = $e->getMessage();
            }

            die(json_encode($result));
        }
        elseif ('doCancelPackage' == $op) {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $package_id = $this->request->getPost('package_id', 'intval');
            if (!$package = projects::getPackage($package_id)) {
                showMsg('找不到该订单');
            }

            $result = array('errno' => 0, 'errstr' => '');
            try {
                $admin_id = 1;
                game::cancelPackage($package, 9, $admin_id); //1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
            } catch (exception2 $e) {
                $result['errno'] = $e->getCode() ? $e->getCode() : 1000;
                $result['errstr'] = $e->getMessage();
            }

            die(json_encode($result));
        }
        elseif ('doJudgePrize' == $op) {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $package_id = $this->request->getPost('package_id', 'intval');

            $result = array('errno' => 0, 'errstr' => '');
            $lottery = lottery::getItem($lottery_id);
            $issueInfo = issues::getItem(0, $issue, $lottery_id);
            $package = projects::getPackage($package_id);

            try {
                $totalPrize = game::checkPrize($lottery, $issueInfo, array($package));
                logdump("最终奖金: {$totalPrize}");
            } catch (exception2 $e) {
                $result['errno'] = $e->getCode() ? $e->getCode() : 1000;
                $result['errstr'] = $e->getMessage();
            }

            die(json_encode($result));
        }
        elseif ('doSendPrize' == $op) {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $package_id = $this->request->getPost('package_id', 'intval');

            $result = array('errno' => 0, 'errstr' => '');
            try {
                game::sendPrize($lottery_id, $issue, $package_id);
            } catch (exception2 $e) {
                $result['errno'] = $e->getCode() ? $e->getCode() : 1000;
                $result['errstr'] = $e->getMessage();
            }

            die(json_encode($result));
        }
        elseif ('doCancelPrize' == $op) {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $package_id = $this->request->getPost('package_id', 'intval');

            $result = array('errno' => 0, 'errstr' => '');
            try {
                game::cancelPrize($lottery_id, $issue, $package_id);
            } catch (exception2 $e) {
                $result['errno'] = $e->getCode() ? $e->getCode() : 1000;
                $result['errstr'] = $e->getMessage();
            }

            die(json_encode($result));
        }

        $dates = array();
        for ($i = 0; $i < 30; $i++) {
            $dates[] = date('Y-m-d', time() - 86400 * $i);
        }
        self::$view->setVar('dates', $dates);

        //得到所有彩种
        $lotterys = lottery::getItems();
        self::$view->setVar('lotterys', $lotterys)->setVar('json_lotterys', json_encode($lotterys));

        self::$view->render('test_testrebate');
    }

    /**
     * @todo 测试过滤器
     */
    public function testFilter()
    {
        echo '<h1>未过滤前 $_GET</h1>';
        echo "<pre>";
        print_r($_GET);
        echo '<h1>过滤后 $_GET</h1>';
        foreach ($_GET as $key => $value) {
            echo $this->request->getGet($key, 'trim');
            echo '<br>';
        }
        echo '<hr>';

        echo '<h1>未过滤前 $_POST</h1>';
        echo "<pre>";
        print_r($_POST);
        echo '<h1>过滤后 $_GET</h1>';
        foreach ($_POST as $key => $value) {
            echo $this->request->getPost($key, 'trim');
            echo '<br>';
        }
        echo '<hr>';

        echo '<h1>未过滤前 $_COOKIE</h1>';
        echo "<pre>";
        print_r($_COOKIE);
        echo '<h1>过滤后 $_COOKIE</h1>';
        foreach ($_COOKIE as $key => $value) {
            echo $this->request->getCookie($key, 'trim');
            echo '<br>';
        }
        echo '<hr>';

        exit;
    }

}

?>