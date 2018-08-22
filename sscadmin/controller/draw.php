<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：开奖管理
 */
class drawController extends sscAdminController
{
    protected $_error = '';
    static $lotteryTypes = array(
        '1' => '数字型',
        '2' => '乐透',
        '3' => '乐透分区型（蓝红球）',
        '4' => '3D',
        //'5' => 'keno',    //没有任何产品 可暂时屏蔽
        '6' => '快3',
        '7' => '快乐扑克',
        '8' => 'PK10',
        '9' => '六合彩',
        '10' => 'PC蛋蛋',
    );

    const OPEN_TIME_EARLY_LIMIT = 1800; //对出现官方提前开奖的问题报警时限30分钟(响30分过了不响了)

    //方法概览

    public $titles = array(
        'digital' => '数字型开奖',
        'lotto' => '乐透型开奖',
        'low3D' => '低频3D型开奖',
        'quick3' => '快三型开奖',
        'klpk' => '扑克型开奖',
        'bjpks' => 'PK拾开奖',
        'lhc' => '六合彩',
        'ssq' => '双色球',
        'pcdd' => 'PC蛋蛋',
        'delayIssue' => '奖期顺延管理',
        'cancelProject' => '系统撤单',
        'deleteIssueError' => '删除撤单记录',
        'testJudgePrize' => '测试中奖判断',
        'drawSourceList' => '开奖源列表',
        'addDrawSource' => '新增开奖源',
        'editDrawSource' => '修改开奖源',
        'editRank' => '设置权重',
        'testSource' => '测试开奖源',
        'setStatus' => '启禁用开奖源',
        'deleteSource' => '删除开奖源',
        'batchSetStatus' => '批量设置启禁用状态',
        'drawHistory' => '抓号历史',
        'resetDrawIssue' => '重置奖期状态',
        'drawMonitor' => '抓号监视页',
        'revoke' => '撤销派奖'
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function digital()
    {
        $this->_draw(1, __FUNCTION__);
    }

    public function lotto()
    {
        $this->_draw(2, __FUNCTION__);
    }

    public function ssq()
    {
        $this->_draw(3, __FUNCTION__);
    }

    public function low3D()
    {
        $this->_draw(4, __FUNCTION__);
    }

    public function quick3()
    {
        $this->_draw(6, __FUNCTION__);
    }

    public function klpk()
    {
        $this->_draw(7, __FUNCTION__);
    }

    public function bjpks()
    {
        $this->_draw(8, __FUNCTION__);
    }

    public function lhc()
    {
        $this->_draw(9, __FUNCTION__);
    }

    public function pcdd()
    {
        $this->_draw(10, __FUNCTION__);
    }

    private function _draw($lotteryType, $tpl)
    {
        //彩种类型(1:数字类型，2:乐透分区型(蓝红球)，3:乐透同区型，4:低频3D:，5:基诺型)
        // $lotterys = lottery::getItems($lotteryType,-1);
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname', 'name'], $lotteryType, -1);
        if (empty($lotterys)) {
            showMsg('此类型彩种已被禁用！', 0, array(0 => array('title' => '返回开奖列表', 'url' => url('draw', 'digital', array('lottery_id' => 1)))));
        }
        if (!$lottery_id = $this->request->getGet('lottery_id', 'intval', 0)) {
            if (!$lottery_id = $this->request->getPost('lottery_id', 'intval', 0)) {
                $tmp = reset($lotterys);
                $lottery_id = $tmp['lottery_id'];
            }
        }
        $lottery = $lotterys[$lottery_id];
        $locations = array(0 => array('title' => '返回开奖列表', 'url' => url('draw', $tpl, array('lottery_id' => $lottery_id))));
        $this->_manualOpenCode($lottery, $locations);
        $this->_manualDraw($lottery_id, $lotterys, $tpl);
    }

    private function _manualOpenCode($lottery, $locations)
    {
        if ($this->request->getPost('submit', 'trim')) {
            $issue_id = $this->request->getPost('issue_id', 'intval');
            $code = $this->request->getPost('code', 'trim');

            try {
                $rankConfigs = config::getConfigs(array('person_rank', 'least_rank'));
                if (issues::drawNumber($issue_id, $code, $rankConfigs['person_rank'], $GLOBALS['SESSION']['admin_id'])) {
                    //共享库后，后台手动开奖 就不能再进行判奖和派奖
                    // if ($issueInfo = issues::getNeed2CheckPrizeIssue($lottery['lottery_id'])) {
                    //     $result = game::batchCheckPrize($lottery, $issueInfo);
                    // }
                    // if ($issueInfo = issues::getNeed2SendPrizeIssue($lottery['lottery_id'])) {
                    //     $num = game::batchSendPrize($lottery, $issueInfo);
                    // }

                    showMsg("开奖成功，号码是{$code}", 0, $locations);
                }
            } catch (exception2 $e) {
                showMsg("出现异常：{$e->getMessage()}", 0, $locations);
            }

            showMsg("录号成功，等待审核", 0, $locations);
        }
    }

    private function _manualDraw($lottery_id, $lotterys, $tpl)
    {
        $lottery = $lotterys[$lottery_id];  //当前显示的彩种
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $curPage = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值

        //奖期列表
        $issuesNumber = issues::getItemsNumber($lottery_id, '', 0, 0, 0, time());
        /******************** snow  修改获取正确页码值******************************/
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $issuesNumber);
        /******************** snow  修改获取正确页码值******************************/
        $issues = issues::getItems($lottery_id, '', 0, 0, 0, time(), -1, 'issue_id DESC', $startPos, DEFAULT_PER_PAGE);

        //每家各自的判奖派奖反点状态从各自的checksend表中获取
        $tmpIssues = array_spec_key($issues, 'issue_id');
        $checkSendIssues = (new checkSend)->getItems(array_keys($tmpIssues));
        $tmpCheckSendIssues = array_spec_key($checkSendIssues, 'issue_id');

        foreach ($tmpIssues as $key => &$value) {
            $value['status_check_prize'] = 0;
            $value['status_send_prize'] = 0;
            $value['status_rebate'] = 0;
            if (isset($tmpCheckSendIssues[$key])) {
                $value['status_check_prize'] = $tmpCheckSendIssues[$key]['status_check_prize'];
                $value['status_send_prize'] = $tmpCheckSendIssues[$key]['status_send_prize'];
                $value['status_rebate'] = $tmpCheckSendIssues[$key]['status_rebate'];
            }
        }

        //得到管理员列表
        $admins = admins::getItems();
        $admins['65535'] = array('admin_id' => 65535, 'username' => '机器');
        self::$view->setVar('admins', $admins);
        //得到未开奖的奖期，如果还处在销售期则为空
        $lastNoDrawIssue = issues::getLastNoDrawIssue($lottery_id);
        $noDrawIssues = issues::getNoDrawIssues($lottery_id, 1400);
        if (!$lastNoDrawIssue && $noDrawIssues) {
            $lastNoDrawIssue = reset($noDrawIssues);
        }

        $tpl = 'draw_' . strtolower($tpl);

        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('lottery', $lottery);
        self::$view->setVar('issues', $tmpIssues);
        self::$view->setVar('lastNoDrawIssue', $lastNoDrawIssue);
        self::$view->setVar('noDrawIssues', $noDrawIssues);
        self::$view->setVar('least_rank', config::getConfig('least_rank'));
        self::$view->setVar('pageList', getPageList($issuesNumber, DEFAULT_PER_PAGE));
        self::$view->render($tpl);
    }

    //撤销开奖
    public function revoke()
    {

        $revokeIds = $this->request->getPost('revokeIds', 'trim');
        $lottery_id = $this->request->getGet('reset_lottery_id', 'intval', 0);
        $date = $this->request->getGet('date', 'trim');
        $issue = $this->request->getGet('issue', 'trim');

        $statusSendMapping = array(
            '0' => '未开始',
            '1' => '派奖中',
            '2' => '已完成'
        );

        $statusCodeMapping = array(
            '0' => '未写入',
            '1' => '写入待验证',
            '2' => '已验证',
            '3' => '官方未开奖'
        );

        $allStatusMapping = array(
            '0' => '未开始',
            '1' => '进行中',
            '2' => '已完成'
        );

        //执行撤销开奖
        if (!empty($revokeIds)) {
            $failCount = 0;
            $successCont = 0;
            $revokeIds = explode('|', $revokeIds);
            foreach ($revokeIds as $revokeId) {
                $data = array(
                    'code' => '',
                    'input_time' => '0000-00-00 00:00:00',
                    'input_admin_id' => 0,
                    'status_code' => 0,
                    'status_send_prize' => 0,
                    'status_check_prize' => 0,
                    'status_rebate' => 0,
                    'status_fetch' => 0,
                    'rank' => 0,
                );

                if (!issues::updateItem($revokeId, $data) || issuesMini::updateItem($revokeId, $data)) {
                    $failCount++;
                } else {
                    issues::deleteIssueHistory($revokeId);
                    $successCont++;
                }
            }

            echo json_encode('成功' . $successCont . '条' . ',' . '失败' . $failCount . '条');
            exit;
        }


        //查询开奖
        $issues = array();
        $issuesNumber = 0;

        if ($issue) {
            $issues[] = issues::getItem(0, $issue, $lottery_id);
        } else if ($date) {
//            $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

            $issuesNumber = issues::getItemsNumber($lottery_id, $date, 0, 0, 0);
            /******************** snow  修改获取正确页码值******************************/
            $curPage = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
            //>>获取正确页码值
            $startPos = getStartOffset($curPage, $issuesNumber);
            /******************** snow  修改获取正确页码值******************************/
            $issues = issues::getItems($lottery_id, $date, 0, 0, 0, 0, -1, 'issue_id DESC', $startPos, DEFAULT_PER_PAGE);
        }

        //参数
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);

        $dates = array();
        for ($i = 0; $i < 30; $i++) {
            $dates[] = date('Y-m-d', time() - 86400 * $i);
        }

        self::$view->setVar('allStatusMapping', $allStatusMapping);
        self::$view->setVar('statusCodeMapping', $statusCodeMapping);
        self::$view->setVar('statusSendMapping', $statusSendMapping);
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('date', $date);
        self::$view->setVar('issue', $issue);
        self::$view->setVar('dates', $dates);
        self::$view->setVar('typeLotterys', $lotterys);
        self::$view->setVar("issues", $issues);
        self::$view->setVar('pageList', getPageList($issuesNumber, DEFAULT_PER_PAGE));
        self::$view->render('draw_revoke');
    }

    //奖期顺延管理
    public function delayIssue()
    {
        $locations = array(0 => array('title' => '奖期顺延管理', 'url' => url('draw', 'delayIssue')));
        $sa = $this->request->getPost('sa', 'trim');
        switch ($sa) {
            case 'getIssue':
                $lottery_id = $this->request->getPost('lottery_id', 'intval');
                if (!$lottery = lottery::getItem($lottery_id)) {
                    showMsg("获取彩种信息失败", 1, $locations);
                }

                //得到今天还没进行的奖期
                $issues = issues::getItems($lottery_id, date('Y-m-d'), time(), 0, 0, 0);

                //延期历史列表
                $issueDelays = issues::getIssueDelays($lottery_id);

                self::$view->setVar('lottery', $lottery);
                self::$view->setVar('issues', $issues);
                self::$view->setVar('issueDelays', $issueDelays);
                self::$view->render("draw_delayissue2");
                exit;
                break;
            case 'setDelay': // 设置延迟
                $lottery_id = $this->request->getPost('lottery_id', 'intval');
                if (!$lottery = lottery::getItem($lottery_id)) {
                    showMsg("获取彩种信息失败", 1, $locations);
                }
                $start_issue = $this->request->getPost('start_issue', 'trim');
                $end_issue = $this->request->getPost('end_issue', 'trim');
                $delay = $this->request->getPost('delay', 'intval');

                $data = array(
                    'lottery_id' => $lottery_id,
                    'start_issue' => $start_issue,
                    'end_issue' => $end_issue,
                    'delay' => $delay,
                );

                if (issues::delayIssueTime($lottery_id, $start_issue, $end_issue, $delay)) {
                    showMsg("延迟奖期成功", 0, $locations);
                }
                showMsg("没有数据被更新", 1);
                break;
        }

        //彩种类型(1:数字类型，2:乐透分区型(蓝红球)，3:乐透同区型，4:基诺型，5:排列型，6:分组型)
        $typeLotterys = array();
        foreach (lottery::getItemsNew(['lottery_id', 'cname', 'lottery_type']) as $v) {
            $typeLotterys[$v['lottery_type']][$v['lottery_id']] = $v;
        }
        self::$view->setVar("lotteryTypes", self::$lotteryTypes);
        self::$view->setVar("json_typeLotterys", json_encode($typeLotterys));
        self::$view->render("draw_delayissue");
    }

    //系统撤单
    public function cancelProject()
    {
        $issue_exception_limit = config::getConfig('issue_exception_limit');    //最大允许撤单时间间隔，单位分钟
        $locations = array(0 => array('title' => '系统撤单', 'url' => url('draw', 'cancelProject')));
        $sa = $this->request->getPost('sa', 'trim');

        if ($sa == 'getIssue') {
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            if (!$lottery = lottery::getItem($lottery_id)) {
                showMsg("获取彩种信息失败", 1, $locations);
            }

            $issues = issues::getItemsMini($lottery_id, '', strtotime("-$issue_exception_limit minutes"), 0, 0, time(), -1, 'issue_id DESC');
            die(json_encode($issues));
        } elseif ($sa == 'cancelProject') {
            //提交撤单操作
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            $issue = $this->request->getPost('issue', 'trim');
            $type = $this->request->getPost('type', 'intval', '1|2|3|4');

            $real_start_time = $this->request->getPost('real_start_time', 'trim', '0000-00-00 00:00:00');
            // $real_code = $this->request->getPost('real_code', 'trim');

            if (!$lottery_id || !$issue) {
                showMsg('参数不完整', 1);
            }

            if ($type == 5) {
                $res = $this->_delayPrize(['issue' => $issue, 'lottery_id' => $lottery_id]);
                if (!$res) {
                    showMsg($this->_error, 1);
                }
                showMsg('官方延时派奖成功', 0, $locations);
            }

            // if ($type == 1) {
            //     if ($real_start_time == '') {
            //         showMsg('请输入官方实际开奖时间', 1, $locations);
            //     }
            // }
            // elseif ($type == 2) {
            //     if (!$real_code) {
            //         showMsg('请输入正确开奖号码', 1, $locations);
            //     }
            // }

            if (issueErrors::processIssueError($lottery_id, $issue, $type, '', $real_start_time, $GLOBALS['SESSION']['admin_id'])) {
                showMsg('撤销派奖成功', 0, $locations);
            } else {
                showMsg('撤销派奖出错', 1, $locations);
            }
        }


        //彩种类型(1:数字类型，2:乐透分区型(蓝红球)，3:乐透同区型，4:基诺型，5:排列型，6:分组型)
        $typeLotterys = array();
        foreach (lottery::getItemsNew(['lottery_id', 'cname', 'lottery_type']) as $v) {
            $typeLotterys[$v['lottery_type']][$v['lottery_id']] = $v;
        }
        self::$view->setVar("lotteryTypes", self::$lotteryTypes);
        self::$view->setVar("json_typeLotterys", json_encode($typeLotterys));
        self::$view->setVar("issue_exception_limit", $issue_exception_limit);

        /**
         * 显示撤单列表
         */
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $issueErrorsNumber = issueErrors::getItemsNumber();
        $curPage = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
        /******************** snow  修改获取正确页码值******************************/
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $issueErrorsNumber);
        /******************** snow  修改获取正确页码值******************************/
        $issueErrors = issueErrors::getItems(0, '', $startPos, DEFAULT_PER_PAGE);
        self::$view->setVar('issueErrors', $issueErrors);
        self::$view->setVar('issueErrorsNumber', $issueErrorsNumber);
        self::$view->setVar('pageList', getPageList($issueErrorsNumber, DEFAULT_PER_PAGE));
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteIssueError')));

        self::$view->render("draw_cancelproject");
    }


    /**
     * 延时开奖
     * 1.根据彩种和彩种奖期获取到本期开奖的开奖号-根据需求客服会手动录入开奖号 只用到issue里面获取
     * 2.批量判奖
     * 3.批量派奖
     * @param $data
     * @return bool
     */
    private function _delayPrize($data)
    {
        $lotteryId = $data['lottery_id'];
        $check_send_info = M('check_send')->where(['lottery_id' => $lotteryId, 'issue' => $data['issue'], 'status_check_prize' => 3, 'status_send_prize' => 3])->find();
        if (empty($check_send_info)) {
            $this->_error = '这个奖期不属于官方延时开奖!';
            return false;
        }
        $issueInfo = M('issues')->db('share_db')->where($data)->find();
        $code = $issueInfo['code'];
        if (empty($code)) {
            $this->_error = '没有获取到开奖号码!';
            return false;
        }

        $lotteryInfo = lottery::getItem($lotteryId);
        if (empty($lotteryInfo)) {
            $this->_error = '没有找到开奖彩种信息!';
            return false;
        }

        try {

            game::batchCheckPrize2($lotteryInfo, $issueInfo);
            sleep(1);
            $issueInfo = M('issues')->where($data)->find();
            game::batchSendPrize2($lotteryInfo, $issueInfo);

            checkSend::updateItemByLottery($lotteryId, $issueInfo['issue'], ['status_check_prize' => 2, 'status_send_prize' => 2, 'status_rebate' => 2]);
            return true;
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
            return false;
        }


    }


    public function deleteIssueError()
    {
        $locations = array(0 => array('title' => '返回列表', 'url' => url('draw', 'cancelProject')));
        if (!$ie_id = $this->request->getGet('ie_id', 'intval')) {
            showMsg("参数无效！", 1, $locations);
        }

        if (!issueErrors::deleteItem($ie_id)) {
            showMsg("删除数据失败！", 0, $locations);
        }

        showMsg("删除数据成功！", 0, $locations);
    }


    public function drawSourceList()
    {
        $lotteryDrawSources = drawSources::getItems(0, NULL, 1);

        //得到所有彩种
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
        self::$view->setVar('lotteries', $lotterys);

        self::$view->setVar('lotteryDrawSources', $lotteryDrawSources);
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加开奖源', 'url' => url('draw', 'addDrawSource'))));
        self::$view->render('draw_drawsourcelist');
    }

    /**
     * 删除开奖源 davy
     *
     */
    public function deleteSource()
    {
        $locations = array(0 => array('title' => '返回开奖源列表', 'url' => url('draw', 'drawSourceList')));
        $ds_id = $this->request->getGet('ds_id', 'intval', 0);
        if (empty($ds_id)) {
            showMsg("没有此开奖源ID");
        }

        if (!drawSources::deleteItem($ds_id)) {
            showMsg("删除开奖源失败");
        } else {
            showMsg("删除成功");
        }
    }

    public function addDrawSource()
    {
        $locations = array(0 => array('title' => '返回开奖源列表', 'url' => url('draw', 'drawSourceList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'lottery_id' => $this->request->getPost('lottery_id', 'intval'),
                'name' => $this->request->getPost('name', 'trim'),
                'url' => $this->request->getPost('url', 'trim'),
                'interface' => $this->request->getPost('interface', 'intval'),
                'rank' => $this->request->getPost('rank', 'intval'),
                'is_enabled' => $this->request->getPost('is_enabled', 'intval'),
                'create_time' => date('Y-m-d H:i:s'),
            );
            if (!drawSources::addItem($data)) {
                showMsg("添加开奖源失败");
            }

            showMsg("添加成功");
        }

        $lotterys = lottery::getItemsNew(['lottery_type', 'lottery_id', 'name']);
        $typeLotteries = array();
        foreach ($lotterys as $v) {
            $typeLotteries[$v['lottery_type']][$v['lottery_id']] = $v;
        }
        self::$view->setVar('json_typeLotteries', json_encode($typeLotteries));

        self::$view->setVar("lotteryTypes", self::$lotteryTypes);
        self::$view->render('draw_adddrawsource');
    }

    public function editDrawSource()
    {
        $locations = array(0 => array('title' => '返回开奖源列表', 'url' => url('draw', 'drawSourceList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'lottery_id' => $this->request->getPost('lottery_id', 'intval'),
                'name' => $this->request->getPost('name', 'trim'),
                'url' => $this->request->getPost('url', 'trim'),
                'interface' => $this->request->getPost('interface', 'intval'),
                'rank' => $this->request->getPost('rank', 'intval'),
                'is_enabled' => $this->request->getPost('is_enabled', 'intval'),
            );
            if (!drawSources::updateItem($this->request->getPost('ds_id', 'intval'), $data)) {
                showMsg("更新失败");
            }

            showMsg("更新成功", 0, $locations);
        }

        //得到彩种选择列表
        $lotterys = lottery::getItemsNew(['lottery_type', 'lottery_id', 'name']);
        $typeLotteries = array();
        foreach ($lotterys as $v) {
            $typeLotteries[$v['lottery_type']][$v['lottery_id']] = $v;
        }
        self::$view->setVar('lotteries', $lotterys);
        self::$view->setVar('json_typeLotteries', json_encode($typeLotteries));

        if (!$ds_id = $this->request->getGet('ds_id', 'intval')) {
            throw new exception2('参数无效');
        }
        if (!$drawSource = drawSources::getItem($ds_id, NULL)) {
            throw new exception2('找不到记录');
        }

        self::$view->setVar("drawSource", $drawSource);
        self::$view->setVar("lotteryTypes", self::$lotteryTypes);
        self::$view->render('draw_adddrawsource');
    }

    public function editRank()
    {
        $locations = array(0 => array('title' => '返回开奖源列表', 'url' => url('draw', 'drawSourceList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'rank' => $this->request->getPost('rank', 'intval'),
            );
            if (drawSources::updateItem($this->request->getPost('ds_id', 'intval'), $data) === false) {
                showMsg("更新失败");
            }

            showMsg("更新成功", 0, $locations);
        }

        //得到彩种选择列表
        $lotterys = lottery::getItemsNew(['lottery_id', 'name', 'lottery_type']);
        $typeLotteries = array();
        foreach ($lotterys as $v) {
            $typeLotteries[$v['lottery_type']][$v['lottery_id']] = $v;
        }
        self::$view->setVar('lotteries', $lotterys);
        self::$view->setVar('json_typeLotteries', json_encode($typeLotteries));

        if (!$ds_id = $this->request->getGet('ds_id', 'intval')) {
            throw new exception2('参数无效');
        }
        if (!$drawSource = drawSources::getItem($ds_id, NULL)) {
            throw new exception2('找不到记录');
        }

        self::$view->setVar("drawSource", $drawSource);
        self::$view->setVar("lotteryTypes", self::$lotteryTypes);
        self::$view->render('draw_editrank');
    }

    public function setStatus()
    {
        $locations = array(0 => array('title' => '返回开奖源列表', 'url' => url('draw', 'drawSourceList')));

        //修改数据
        if (!$ds_id = $this->request->getGet('ds_id', 'intval')) {
            throw new exception2('参数无效');
        }
        $enabled = $this->request->getGet('enabled', 'trim', 'true|false');
        $data = array(
            'is_enabled' => $enabled == 'true' ? 1 : 0,
        );

        if (!drawSources::updateItem($ds_id, $data)) {
            showMsg("没有数据被更新", 1, $locations);
        }

        showMsg("更新成功", 0, $locations);
    }

    public function batchSetStatus()
    {
        $locations = array(0 => array('title' => '返回开奖源列表', 'url' => url('draw', 'drawSourceList')));
        $deleteItems = $this->request->getPost('deleteItems', 'array');
        $enabled = $this->request->getPost('enabled', 'trim');

        if (!$deleteItems) {
            showMsg("没有数据被更新", 1, $locations);
        }

        $lotteryDrawSources = drawSources::getItems($deleteItems, NULL);
        foreach ($lotteryDrawSources as $v) {
            if (drawSources::updateItem($v['ds_id'], array('is_enabled' => $enabled == 'true' ? 1 : 0)) === false) {
                showMsg("更新失败");
            }
        }

        showMsg("更新成功", 0, $locations);
    }

    //测试最新抓号
    public function testSource()
    {
        $ds_id = $this->request->getGet('ds_id', 'intval');
        if (!$drawSource = drawSources::getItem($ds_id, NULL)) {
            $result = array('errno' => 1, 'errstr' => '找不到开奖源');
            die(json_encode($result));
        }

        if (!$drawSource['interface']) {
            $result = array('errno' => 1, 'errstr' => '接口未实现，无法测试！');
            die(json_encode($result));
        }

        if (!$lottery = lottery::getItem($drawSource['lottery_id'])) {
            $result = array('errno' => 1, 'errstr' => '找不到对应彩种');
            die(json_encode($result));
        }

        try {
            $expectedDate = date("Y-m-d");
            $t1 = microtime(true);
            $result = drawSources::fetchFromURL($lottery, $drawSource['url'], $expectedDate);
            $result += array('url' => $drawSource['url'], 'cname' => $lottery['cname'], 'name' => $drawSource['name']);
            die(json_encode($result));
            //sysMessage('抓取成功！'.var_export($result, true), 0);
            //die("<script>alert('抓取成功!\\n 源：{$drawSource['url']}\\n彩种：{$lottery['cnname']}\\n奖期：{$result['issue']}\\n号码：{$result['number']}\\n耗时：{$result['time']}秒\\n');</script>");
        } catch (Exception $e) {
            switch (substr($e->getCode(), 0, 1)) {
                case 1:
                    $errstr = "Error:" . $e->getMessage();
                    break;
                case 2:
                    $errstr = "Warning:" . $e->getMessage();
                    break;
                case 3:
                    $errstr = "Notice:" . $e->getMessage();
                    break;
                default:
                    $errstr = "Unknown:" . $e->getMessage();
                    break;
            }
            $result = array('errno' => 1, 'errstr' => $errstr);
            die(json_encode($result));
        }
    }

    //抓号历史
    public function drawHistory()
    {
        $locations = array(0 => array('title' => '返回开奖源列表', 'url' => url('draw', 'history')));
        $lotterys = lottery::getItemsNew(['lottery_id', 'lottery_type', 'name', 'cname']);
        $tmpLotteryId = $this->request->getPost('lottery_id', 'intval', 0);

        if ($tmpLotteryId != 0) {
            $lottery_id = $GLOBALS['SESSION']['lottery_id'] = $tmpLotteryId;
        } elseif (isset($GLOBALS['SESSION']['lottery_id'])) {
            $lottery_id = $GLOBALS['SESSION']['lottery_id'];
        } else {
            $tmp = reset($lotterys);
            $lottery_id = $tmp['lottery_id'];
        }
        $typeLotterys = array();
        foreach ($lotterys as $v) {
            $typeLotterys[$v['lottery_type']][] = $v;
        }
        //得到源
        $sources = drawSources::getItems($lottery_id);

        $histories = array();
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;


        /******************** snow  修改获取正确页码值******************************/
        $historiesNumber = drawSources::getHistoriesNumber($lottery_id);
        $curPage = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $historiesNumber);
        /******************** snow  修改获取正确页码值******************************/
        if ($tmpHistories = drawSources::getHistories($lottery_id, $startPos, DEFAULT_PER_PAGE)) {
            foreach ($tmpHistories as $v) {
                $v['His'] = substr($v['create_time'], 11);
                $v['rank'] = $v['number'] != 0 ? $v['rank'] : 0;
                if (!isset($histories[$v['issue']]['total_rank'])) {
                    $histories[$v['issue']]['total_rank'] = 0;
                }
                $histories[$v['issue']]['total_rank'] += $v['rank'];
                $histories[$v['issue']]['detail'][] = $v;
            }
            $tmp = array_keys(array_spec_key($tmpHistories, 'issue'));
            $issues = issues::getItemsByIssue($lottery_id, $tmp);
            self::$view->setVar("issues", $issues);
        } else {
            $historiesNumber = 0;
        }

        self::$view->setVar("histories", $histories);
        self::$view->setVar('pageList', getPageList($historiesNumber, DEFAULT_PER_PAGE));
        self::$view->setVar("types", self::$lotteryTypes);
        self::$view->setVar("typeLotterys", $typeLotterys);
        self::$view->setVar("sources", $sources);
        self::$view->setVar("lottery_id", $lottery_id);
        self::$view->setVar('canResetDrawIssue', adminGroups::verifyPriv(array(CONTROLLER, 'resetDrawIssue')));
        self::$view->render('draw_drawhistory');
    }

    /**
     * 重置奖期，这个功能仅供调试用
     * UPDATE `issues` SET `code` = '', `rank` = '0', `statusfetch` = '0', `statuscode` = '0' WHERE lotteryid=3 && issue= '20100623-045' LIMIT 1;
     */
    function resetDrawIssue()
    {
        $reset_lottery_id = $this->request->getPost('reset_lottery_id', 'intval', 0);
        $reset_issue = $this->request->getPost('reset_issue', 'trim');
        $result = array('errno' => 0, 'msg' => '');
        if (!$issue = issues::getItem(0, $reset_issue, $reset_lottery_id)) {
            $result = array('errno' => 1, 'errstr' => '不存在的奖期');
        } else {
            if (false === issues::updateItem($issue['issue_id'], array('code' => '', 'rank' => '0', 'status_fetch' => '0', 'status_code' => '0', 'status_check_prize' => 0, 'status_send_prize' => 0))) {
                $result = array('errno' => 1, 'errstr' => '更新错误');
            } else {
                $result = array('errno' => 0);
            }
        }

        die(json_encode($result));
    }

    //抓号监视页
    function drawMonitor()
    {
        $errors = $drawInfos = array();
        $lotterys = lottery::getItemsNew(['lottery_id', 'lottery_type', 'name', 'cname']);
        $config = config::getConfigs(array("least_rank", "person_rank"));
        /************************定义变量,获取奖期剩余不到3期的彩种数据*********************************/
        $expireIssueList = issues::getExpireIssueList();
        /************************添加功能,获取奖期剩余不到3期的彩种数据*********************************/
        //>>获取最后应该开的奖期
        //>>如果没有值,赋值为空字符串
        $expireIssueList = empty($expireIssueList) ? '' : $expireIssueList;
        /************************添加功能,获取奖期剩余不到3期的彩种数据*********************************/
        foreach ($lotterys as $v) {
            if (!$tmp = issues::getLastNoDrawIssue($v['lottery_id'])) {
                if (!$tmp = issues::getLastIssue($v['lottery_id'])) {
                    continue;
                }
            }
            $tmp['cname'] = $v['cname'];
            $tmp['least_rank'] = $config['least_rank'];
            $drawInfos[$v['lottery_id']] = $tmp;
            if ($tmp['status_fetch'] == 2) {
                if ($tmp['rank'] < $config['least_rank']) {
                    $errors['drawErr'][] = $tmp;
                }
            }
        }
//        echo "<pre>";
//        var_dump($expireIssueList);exit;
        //检查issue_errors表5分钟内如果有官方提前开奖的则报警
        $date_time = date('Y-m-d H:i:s', (time() - self::OPEN_TIME_EARLY_LIMIT));
        $issueErrorInfo = issueErrors::getItemsByEarlyOpenTime($date_time);

        if (!empty($issueErrorInfo)) {
            $errors['openTimeErr'] = $issueErrorInfo;
        }

        $flag = $this->request->getPost('flag', 'trim');
        $shop_domain_config = config::getItem('shop_domain');
        //监视的商城配置
        $eshopSetting = array(
            'shops' => array(//相应的页面JS shops_fail数组也要增加对应的商城键名
                'alipay2' => array(
                    'url' => $shop_domain_config['cfg_value'],
                    'pattern' => '`<div (id="category_tree)">`Uims',
                ),
            ),
            'interval_mins' => 1, //触发时间间隔/分
            'trigger_num' => 3, //触发报警次数
        );

        self::$view->setVar("shop_domain_config", $shop_domain_config);
        /************************赋值到前端,获取奖期剩余不到3期的彩种数据*********************************/
        self::$view->setVar("expireIssueList", json_encode($expireIssueList));
        /************************赋值到前端,获取奖期剩余不到3期的彩种数据*********************************/

        if ($flag == 'ajax') {
            echo json_encode(array('errors' => $errors,
                'data' => $drawInfos,
                /************************赋值到前端ajax,获取奖期剩余不到3期的彩种数据*********************************/
                'expireIssueList' => empty($expireIssueList) ? false : $expireIssueList));
            exit;
        } elseif ($flag == 'shopAjax') {
            $shopMonitorResult = drawSources::eshopMonitor($eshopSetting);
            echo json_encode(array_values($shopMonitorResult));
            exit;
        }

        self::$view->setVar("interval_mins", $eshopSetting['interval_mins']);
        self::$view->setVar("trigger_num", $eshopSetting['trigger_num']);
        self::$view->setVar("errors", $errors);
        self::$view->setVar("drawInfos", $drawInfos);
        self::$view->setVar("config", $config);
        self::$view->render('draw_drawmonitor');
    }

}

?>