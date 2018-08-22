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
        'userInfo' => '用户信息',
        'transfer' => '额度转换',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
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
        $enableGames = egameGameSet::getItems(1, 1);
        $gameList = array();

        if (isset($gameInfo['ret']) && $gameInfo['ret'] == '0000') {
            foreach ($gameInfo['games'] as $key => $value) {
                if (isset($enableGames[$value['gameId']])) {
                    $gameList[$value['gameId']] = $value;
                }
            }

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
        $response = $mw->oauth($uid, $user['parent_id'], $user['top_id'], $username, $gameId, 2);
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
}