<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：数据管理
 */
class dataController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'cleanIssue' => '奖期清理',
        'cleanUser' => '会员清理',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function cleanIssue()
    {

        die('待完成');

        $parentId = $this->request->getGet('parentId', 'intval', 0);
        $prevId = $this->request->getGet('prevId', 'intval', 0);
        $locks = lock::getItems($parentId);

        self::$view->setVar('locks', $locks);
        self::$view->setVar('parentId', $parentId);
        self::$view->setVar('prevId', $prevId);
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加配置', 'url' => url('lock', 'addConfig'))));
        self::$view->render('lock_locklist');
    }

    public function cleanUser()
    {
        if ($this->request->getGet('submit', 'trim')) {

            $type = $this->request->getGet('type', 'trim');
            if ($type == 'findUser') {
                $curPage = $this->request->getGet('curPage', 'intval', 1);
                $startPos = ($curPage - 1) * DEFAULT_PER_PAGE;
                $amount = $this->request->getGet('amount', 'floatval');
                $days = $this->request->getGet('days', 'intval');

                $lastEndTS = strtotime("-{$days} day");
                $users = users::getItems(-1, true, 0, array(), 8, 0); //查询所有正常状态用户
                $illegalUsers = array();
                $legalUserIds = array();
                foreach ($users as $v) {
                    if ($v['balance'] < $amount && strtotime($v['last_time']) < $lastEndTS) {
                        $illegalUsers[$v['user_id']] = $v;
                    }
                    else {
                        foreach (explode(',', $v['parent_tree']) as $parentId) {
                            $legalUserIds[$parentId] = $parentId;
                        }
                    }
                }
                foreach ($illegalUsers as $userId => $user) {
                    if ($user['level'] != 10 && isset($legalUserIds[$userId])) {
                        unset($illegalUsers[$userId]);
                    }
                }
                $totalNum = count($illegalUsers);
                ksort($illegalUsers);
                $illegalUsers = array_slice($illegalUsers, $startPos, DEFAULT_PER_PAGE);
                self::$view->setVar('curPage', $curPage);
                self::$view->setVar('amount', $amount);
                self::$view->setVar('days', $days);
                self::$view->setVar('illegalUsers', $illegalUsers);
                self::$view->setVar('pageList', getPageList($totalNum, DEFAULT_PER_PAGE));
            }
        }
        self::$view->render('data_cleanuser');
    }

}

?>