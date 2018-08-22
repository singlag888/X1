<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：优惠管理
 */
class promoController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'promoList' => '优惠列表',
        'checkNew' => '检查新提案',
        'promoDetail' => '查看优惠详情',
        'verify' => '审核优惠',
        'execute' => '执行优惠',
        'cancel' => '取消优惠',
        'addPromo' => '手工添加优惠',
	    'agentBonus' => '代理分红',
	    'companyClaim' => '平台理赔',
        'bonusList' => '分红统计',
        'batchImportBonus' => '批量导入分红',
        'batchVerifyBonus' => '批量审核分红',
        'batchExecuteBonus' => '批量执行分红',
        'batchCancelBonus' => '批量取消分红',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //优惠列表
    public function promoList()
    {
        //$agent_id = -1, $user_id = 0, $type = 0, $promo_bank_id = 0, $promo_card_id = '', $startDate = '', $endDate = '', $status = -1
        $top_username = $this->request->getGet('top_username', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        $is_test = $this->request->getGet('is_test', 'intval', -1);
        //优惠类型：默认查配置中的所有类型
        $types = $this->request->getGet('types', 'array');
        if (empty($types)) {
        	foreach ($GLOBALS['cfg']['promoTypes'] as $key => $value) {
        		$types[$key] = $key;
        	}
        }

        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d 00:00:00"));   //, date('Y-m-d H:i:s', strtotime('-7 days'))
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $status = $this->request->getGet('status', 'intval', -1);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $trafficInfo = promos::getTrafficInfo($top_username ? $top_username : $username, $include_childs, $is_test, $status, $types, '', $startDate, $endDate);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $promos = promos::getItems($top_username ? $top_username : $username, $include_childs, $is_test, $status, $types, $startDate, $endDate, '', $startPos, DEFAULT_PER_PAGE);

        //查询选项
        self::$view->setVar('top_username', $top_username);
        self::$view->setVar('username', $username);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('types', $types);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('status', $status);

        //得到所有总代
        // $topUsers = users::getUserTree(0);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        //>>author snow 返回数组，不返回json
        self::$view->setVar('json_topUsers', $topUsers);
        //得到管理员列表
        self::$view->setVar('admins', admins::getItems());
        self::$view->setVar('promoTypes', $GLOBALS['cfg']['promoTypes']);

        self::$view->setVar('promos', $promos);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('pageList', getPageList($trafficInfo['count'], DEFAULT_PER_PAGE));
        self::$view->render('promo_promolist');
    }

    //ajax调用 检查是否有新的“未处理”提案
    public function checkNew()
    {
        $promos = promos::getItems('', 0, -1, 0);
        $result = array('newNum' => count($promos));
        echo json_encode($result);
    }

    // 查看优惠优惠 状态更改为"已受理"
    public function promoDetail()
    {
        $locations = array(0 => array('title' => '返回优惠列表', 'url' => url('promo', 'promoList')));
        $promo_id = $this->request->getGet('promo_id', 'intval');
        if (!$promo = promos::getItem($promo_id)) {
            showMsg("找不到优惠", 1, $locations);
        }
        if (!$user = users::getItem($promo['user_id'])) {
            showMsg("找不到该用户", 1, $locations);
        }

        //得到管理员列表
        self::$view->setVar('admins', admins::getItems());
        //优惠方式
        self::$view->setVar('promoTypes', $GLOBALS['cfg']['promoTypes']);

        self::$view->setVar('promo', $promo);
        self::$view->setVar('user', $user);
        self::$view->render('promo_promodetail');
    }

    // 优惠还是要审核的。不像存款
    public function verify()
    {
        $promo_id = $this->request->getPost('promo_id', 'intval');
        $locations = array(0 => array('title' => '返回优惠列表', 'url' => url('promo', 'promoDetail', array('promo_id' => $promo_id))));
        if (!$promo = promos::getItem($promo_id)) {
            showMsg("找不到优惠", 1, $locations);
        }
        if ($promo['status'] != 0 && $promo['status'] != 1) {
            showMsg("该优惠状态不是“未处理”，不能再次审核", 1, $locations);
        }

        //审核
        $url = url('promo', 'promoDetail', array('promo_id' => $promo_id));
        if (($flag = promos::verify($promo_id, $GLOBALS['SESSION']['admin_id'], '已审核')) !== true) {
            showMsg($flag, 1, $locations);
        }

        showMsg('审核成功', 0, $locations);
    }

    // 执行优惠 为玩家充游戏币 状态为"已成功"
    public function execute()
    {
        $locations = array(0 => array('title' => '返回优惠列表', 'url' => url('promo', 'promoList')));   //, array('promo_id' => $promo_id)

        $promo_id = $this->request->getPost('promo_id', 'intval');
        $remark = $this->request->getPost('remark', 'trim');

        // 处理充值
        if (promos::execute($promo_id, $GLOBALS['SESSION']['admin_id'], $remark) !== true) {
            showMsg("操作失败", 1, $locations);
        }

        showAlert("执行成功", url('promo', 'promoList'));
    }

    // 取消优惠 并写明原因
    public function cancel()
    {
        $locations = array(0 => array('title' => '返回优惠列表', 'url' => url('promo', 'promoList')));

        $promo_id = $this->request->getPost('promo_id', 'intval');
        $reason = $this->request->getPost('reason', 'trim');

        // 取消
        $url = url('promo', 'promoList');
        if (!promos::cancel($promo_id, $GLOBALS['SESSION']['admin_id'], $reason)) {
            showMsg("取消失败数据库错误", 1, $locations);
        }

        showAlert('取消成功', url('promo', 'promoList'));
    }

    //添加优惠
    public function addPromo()
    {
        $type = $this->request->getGet('type', 'intval');
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            $type = $this->request->getPost('type', 'intval');  //1|2|9
            $promo_amount = $this->request->getPost('promo_amount', 'floatval');
            $notes = $this->request->getPost('notes', 'trim');

            //此处只针对流水天梯活动加以金额限制
            if($promo_amount > 4886){
                showMsg("流水天梯不能超过4886");
            }
            if (($result = promos::addPromo($username, $type, 0, $promo_amount, $notes, date('Y-m-d H:i:s'), 0)) !== true) {
                showMsg("添加优惠失败!（{$result}）");
            }

            $locations = array(
                0 => array('title' => '继续添加优惠', 'url' => url('promo', 'addPromo', array('type' => $type))),
                1 => array('title' => '返回优惠列表', 'url' => url('promo', 'promoList')),
            );
            showMsg("添加成功", 0, $locations);
        }

        self::$view->setVar('type', $type);
        self::$view->setVar('first_deposit_rate', config::getConfig('first_deposit_rate'));
        self::$view->setVar('max_first_deposit', config::getConfig('max_first_deposit'));
        self::$view->setVar('max_again_deposit', config::getConfig('max_again_deposit'));
        self::$view->setVar('again_deposit_rate', config::getConfig('again_deposit_rate'));
        self::$view->render('promo_addpromo');
    }
    
    
    //代理分红
    public function agentBonus()
    {
        $type = $this->request->getGet('type', 'intval');
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            $type = $this->request->getPost('type', 'intval');  //1|2|9
            $promo_amount = $this->request->getPost('promo_amount', 'floatval');
            $notes = $this->request->getPost('notes', 'trim');

            if (($result = promos::addPromo($username, $type, 0, $promo_amount, $notes, date('Y-m-d H:i:s'), 0)) !== true) {
                showMsg("添加优惠失败!（{$result}）");
            }

            $locations = array(
                0 => array('title' => '继续添加优惠', 'url' => url('promo', 'agentBonus', array('type' => $type))),
            );
            showMsg("添加成功", 0, $locations);
        }

        self::$view->setVar('type', $type);
        self::$view->render('promo_agentbonus');
    }
    
    //平台理赔
    public function companyClaim()
    {
        $type = $this->request->getGet('type', 'intval');
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            $type = $this->request->getPost('type', 'intval');  //1|2|9
            $promo_amount = $this->request->getPost('promo_amount', 'floatval');
            $notes = $this->request->getPost('notes', 'trim');

            if (($result = promos::addPromo($username, $type, 0, $promo_amount, $notes, date('Y-m-d H:i:s'), 0)) !== true) {
                showMsg("添加优惠失败!（{$result}）");
            }

            $locations = array(
                0 => array('title' => '继续添加优惠', 'url' => url('promo', 'companyClaim', array('type' => $type))),
            );
            showMsg("添加成功", 0, $locations);
        }

        self::$view->setVar('type', $type);
        self::$view->render('promo_companyclaim');
    }

    public function bonusList()
    {
        $status = $this->request->getGet('status', 'intval', 1);
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d 00:00:00"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $type = 3;   //优惠类型 1首存 2再存 3代理分红 9其他
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $trafficInfo = promos::getTrafficInfo('', 0, 0, $status, $type, '',$startDate, $endDate);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $promos = promos::getItems('', 0, 0, $status, $type, $startDate, $endDate, '', $startPos, DEFAULT_PER_PAGE);
        //优惠类型
        self::$view->setVar('promoTypes', $GLOBALS['cfg']['promoTypes']);

        //预选中值
        self::$view->setVar('status', $status);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('promos', $promos);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('pageList', getPageList($trafficInfo['count'], DEFAULT_PER_PAGE));

        self::$view->render('promo_bonuslist');
    }

    //批量计算并导入分红
    public function batchImportBonus()
    {
        $locations = array(0 => array('title' => '返回分红统计', 'url' => url('promo', 'bonusList')));
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d 00:00:00"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $targetRebate = $this->request->getPost('targetRebate', 'trim');
        if ($startDate >= $endDate) {
            showMsg("日期不正确");
        }

        //先检查日期范围内有无任何已返红利数据！
        $type = 3;  //优惠类型 1首存 2再存 3代理分红 9其他
        if ($promos = promos::getItems('', 0, 0, 8, $type, $startDate, $endDate)) {
            showMsg("从{$startDate}-{$endDate}之间已派发过红利，不能再次派发");
        }

        $configs = config::getConfigs(array('bonus_rate_1956', 'bonus_rate_1954'));

        //计算1954应得的红利数据
        $users = [];
        if ($targetRebate == 0.127) {
            //1.先找出用1954的有哪些用户
            $users = userRebates::getUsersByRebate($targetRebate);
            foreach ($users as $k => $v) {
                //2.计算用户的团队数据
                $childSales = projects::getChildSales(0, $v['user_id'], 0, 0, $startDate, $endDate);
                $totalInfo = array('amount' => 0, 'rebate' => 0, 'real_amount' => 0, 'prize' => 0, 'final' => 0,);
                foreach ($childSales as $kk => $vv) {
                    $totalInfo['amount'] += $vv['total_amount'];
                    $totalInfo['rebate'] += $vv['total_rebate'];
                    $totalInfo['prize'] += $vv['total_prize'];
                }
                $totalInfo['final'] = $totalInfo['prize'] + $totalInfo['rebate'] - $totalInfo['amount'];

                //3.计算用户的所有优惠金额
                $type = 0;
                $promoInfo = promos::getTrafficInfo($v['username'], 0, 8, $type, '',$startDate, date('Y-m-d H:i:s'));

                //4.中奖+返点+优惠-投注=实际盈亏 对于盈亏值为负的代理，计算其分红
                $users[$k]['win_lose'] = $promoInfo['total_amount'] + $totalInfo['final'];
                if ($users[$k]['win_lose'] < 0) {
                    $users[$k]['bonus'] = abs($users[$k]['win_lose'] * $configs['bonus_rate_1954']);
                }
                elseif ($users[$k]['win_lose'] > 0) {
                    $users[$k]['bonus'] = 0;
                }
                else {
                    unset($users[$k]);
                }
            }
        }
        elseif ($targetRebate == 0.128) {   //对于总代的分红，还要减去1954的分红
            //1.先找出用1956的有哪些用户
            //$users = userRebates::getUsersByRebate($targetRebate);
            // 这里的业务数据有点不明确,暂不修改此处.
            $users = users::getUserTree(0);
            foreach ($users as $k => $v) {
                //2.计算用户的团队数据
                $childSales = projects::getChildSales(0, $v['user_id'], 0, 0, $startDate, $endDate);
                $totalInfo = array('amount' => 0, 'rebate' => 0, 'real_amount' => 0, 'prize' => 0, 'final' => 0,);
                foreach ($childSales as $kk => $vv) {
                    $totalInfo['amount'] += $vv['total_amount'];
                    $totalInfo['rebate'] += $vv['total_rebate'];
                    $totalInfo['prize'] += $vv['total_prize'];
                }
                $totalInfo['final'] = $totalInfo['prize'] + $totalInfo['rebate'] - $totalInfo['amount'];

                //3.计算用户的所有优惠金额
                $type = 0;
                $promoInfo = promos::getTrafficInfo($v['username'], 0, 8, $type, '',$startDate, $endDate);
                //4.还要计算下级1954的分红，要扣除
                $childPromoInfo = promos::getTrafficInfo($v['user_id'], 1, 8, 3, '',$startDate, $endDate);
                //5.对于盈亏值为正的代理，计算其分红
                $users[$k]['win_lose'] = $promoInfo['total_amount'] + $childPromoInfo['total_amount'] + $totalInfo['final'];
                if ($users[$k]['win_lose'] < 0) {
                    $users[$k]['bonus'] = abs($users[$k]['win_lose'] * $configs['bonus_rate_1956']);
                }
                elseif ($users[$k]['win_lose'] > 0) {
                    $users[$k]['bonus'] = 0;
                }
                else {
                    unset($users[$k]);
                }
            }
        }
        else {
            showMsg("未知代理级别");
        }

        $count = 0;
        foreach ($users as $v) {
            if ($v['bonus'] > 0) {
                if (($result = promos::addPromo($v['user_id'], 3, $v['win_lose'], $v['bonus'], '', $endDate, 1)) !== true) {
                    showMsg("统计失败：$result");
                }
                $count++;
            }
        }

        showMsg('统计入库成功，共计' . $count . ' 条记录', 1, $locations);
    }

    //批量审核分红
    public function batchVerifyBonus()
    {
        $locations = array(0 => array('title' => '返回分红统计', 'url' => url('promo', 'bonusList')));
        $deleteItems = $this->request->getPost('deleteItems', 'array');
        if (!$deleteItems) {
            showMsg('没有选择要操作的项目');
        }
        $amount = $count = 0;
        $type = 3;  //优惠类型 1首存 2再存 3代理分红 9其他
        foreach ($deleteItems as $v) {
            if (($flag = promos::verify($v, $GLOBALS['SESSION']['admin_id'], '已审核')) !== true) {
                showMsg($flag);
            }
        }

        showMsg("批量审核成功", 1, $locations);
    }

    //批量执行分红
    public function batchExecuteBonus()
    {
        $locations = array(0 => array('title' => '返回分红统计', 'url' => url('promo', 'bonusList')));
        if (!$deleteItems = $this->request->getPost('deleteItems', 'array')) {
            showMsg('没有选择要操作的项目');
        }
        foreach ($deleteItems as $v) {
            if (($flag = promos::execute($v, $GLOBALS['SESSION']['admin_id'], '批量已执行')) !== true) {
                showMsg($flag);
            }
        }

        showMsg("批量执行成功", 1, $locations);
    }

    //批量取消分红
    public function batchCancelBonus()
    {
        $locations = array(0 => array('title' => '返回分红统计', 'url' => url('promo', 'bonusList')));
        $remark = $this->request->getPost('remark', 'trim');
        if (!$deleteItems = $this->request->getPost('deleteItems', 'array')) {
            showMsg('没有选择要操作的项目');
        }

        foreach ($deleteItems as $v) {
            if (($flag = promos::cancel($v, $GLOBALS['SESSION']['admin_id'], $remark)) !== true) {
                showMsg($flag);
            }
        }

        showMsg("批量取消成功", 1, $locations);
    }

}

?>