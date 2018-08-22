<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：彩种管理
 */
class lotteryController extends sscAdminController
{
    static $pageNum = DEFAULT_PER_PAGE;

    //方法概览
    public $titles = array(
        'lotteryList' => '彩种列表',
        'addLottery' => '增加彩种',
        'editLottery' => '修改彩种',
        'issueList' => '奖期列表',
        'genIssue' => '批量生成奖期',
        'diyGenIssue' => '手动生成奖期',
        'deleteIssue' => '删除奖期',
        'modesList' => '模式列表',
        'addModes' => '添加彩种模式',
        'editModes'=>'编辑彩种模式',
        'delModesAjax'=>'禁用启用彩种',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function modesList()
    {
        $list = lottery::getModesItemsByCond();
//        echo '<pre>';
//        print_r($list);exit;
        self::$view->setVar('list', $list);
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加模式', 'url' => url('lottery', 'addModes'))));
        self::$view->render('lottery_modeslist');
    }


    public function addModes()
    {
        if ($this->request->getPost('submit', 'trim')) {
            $modes_name = $this->request->getPost('modes_name', 'trim');
            $modes_value = $this->request->getPost('modes_value', 'trim');
            $modes_area = $this->request->getPost('modes_area', 'trim');
            $use = $this->request->getPost('use', 'intval');
            $status = $this->request->getPost('status', 'intval');
            if(empty($modes_name) || empty($modes_value) || intval($modes_area) <=0)
            {
                showMsg("参数错误");
            }

            $GLOBALS['db']->startTransaction();
            if($use === 1)//更改彩种表
            {
                $lotteys = lottery::getItemsByCond('','modes,lottery_id');
                if(!empty($lotteys))
                {
                    $tmp = '';
                    foreach ($lotteys as $k =>$v)
                    {
                        if(!($modes_area&$v['modes']))
                        {

                            $tmp .= $v['lottery_id'].',';
                        }
                    }
                    if(!empty($tmp))
                    {
                        $tmp=rtrim($tmp,',');
                        $res = lottery::updateDatas(" modes = modes+$modes_area" ," and lottery_id in ($tmp)");
                        if(!$res)
                        {
                            $GLOBALS['db']->rollback();
                            showMsg("添加失败");
                        }
                    }
                }
            }

            $data = array(
                'modes_name' => $modes_name,
                'modes_value' => $modes_value,
                'modes_area' =>$modes_area,
                'status' => $status,
            );
            if (!lottery::addMode($data)) {
                $GLOBALS['db']->rollback();
                showMsg("添加彩种模式失败!请检查数据输入是否完整");
            }
            $GLOBALS['db']->commit();
            showMsg("添加成功");
        }
        $mode = lottery::getModesItemByCond('',"max(modes_area) as modes_area");
       $modes_area = empty($mode['modes_area'])?0:$mode['modes_area'];
       if(empty($modes_area))
       {
           $modes_area = 1;
       }else{
           $modes_area = intval($modes_area) * 2;
       }

        self::$view->setVar('modes_area', $modes_area);
        self::$view->render('lottery_addmodes');
    }

    public function editModes()
    {
        if ($this->request->getPost('submit', 'trim')) {
            $modes_area = $this->request->getPost('modes_area', 'trim');
            $modes_name = $this->request->getPost('modes_name', 'trim');
            $modes_value = $this->request->getPost('modes_value', 'intval');
            if(empty($modes_area) || empty($modes_name) || empty($modes_value))
            {
                showMsg("参数错误!");
            }
            $data=array(
                'modes_area'=>$modes_area,
                'modes_name'=>$modes_name,
                'modes_value'=>$modes_value,
            );
            $res = lottery::updateModeItem($modes_area,$data);
            if(!$res)
            {
                showMsg('没有数据修改');
            }
            showMsg('修改成功!bbbd');
        }

        $modes_area = $this->request->getGet('modes_area', 'trim');
        if(empty($modes_area))
        {
            showMsg("参数错误!");
        }
        $mode = lottery::getModesItemByCond(" AND modes_area =".$modes_area);

        self::$view->setVar('mode', $mode);
        self::$view->render('lottery_addmodes');
    }

    public function delModesAjax()
    {
        $area = $this->request->getPost('area', 'intval');
        $status = $this->request->getPost('status', 'intval');
        if($area <=0 || !in_array($status,[0,1]))
        {
            die(json_encode(['code'=>1,'msg'=>'参数错误!']));
        }
        $data=array(
            'modes_area'=>$area,
            'status'=>$status,
        );
        $GLOBALS['db']->startTransaction();
        $res = lottery::updateModeItem($area,$data);
        if(!$res)
        {
            $GLOBALS['db']->rollback();
            die(json_encode(['code'=>1,'msg'=>'操作失败1!']));
        }

        $lotteys = lottery::getItemsByCond('','modes,lottery_id');
        $modes_area = 0;
        if(!empty($lotteys))
        {
            $tmp = '';
            foreach ($lotteys as $k =>$v)
            {
                if(!($area&$v['modes']) && $status==1)//启用  彩种没有改模式 应该增加
                {

                    $tmp .= $v['lottery_id'].',';
                    $modes_area = $area;
                }

                if(($area&$v['modes']) && $status==0)//禁用 彩种有该模式应该减去
                {

                    $tmp .= $v['lottery_id'].',';
                    $modes_area=0-$area;
                }
            }
            if(!empty($tmp))
            {
                $tmp=rtrim($tmp,',');
                $res = lottery::updateDatas(" modes = modes+$modes_area" ," and lottery_id in ($tmp)");
                if(!$res)
                {
                    $GLOBALS['db']->rollback();
                    die(json_encode(['code'=>1,'msg'=>'操作失败2!']));
                }
            }
        }
        $GLOBALS['db']->commit();
        die(json_encode(['code'=>0,'msg'=>'']));
   }
    //彩种列表
    public function lotteryList()
    {
        $lotterys = lottery::getItems(0, -1);
        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加彩种', 'url' => url('lottery', 'addLottery'))));
        self::$view->render('lottery_lotterylist');
    }

    //奖期列表
    public function issueList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg('找不到彩种');
        }

//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * self::$pageNum;
        $issuesNumber = issues::getItemsNumber($lottery_id);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $issuesNumber, self::$pageNum);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $issues = issues::getItems($lottery_id, '', 0, 0, 0, 0, -1, '', $startPos, self::$pageNum);
        $actionLinks = array(
            0 => array('title' => '彩种列表', 'url' => url('lottery', 'lotteryList')),
            1 => array('title' => '批量生成奖期', 'url' => url('lottery', 'genIssue', array('lottery_id' => $lottery_id))),
        );

        if ($lottery_id == 21 || $lottery_id == 22) {//六合彩 双色球
            array_push($actionLinks, array('title' => '手动生成奖期', 'url' => url('lottery', 'diyGenIssue', array('lottery_id' => $lottery_id))));
            unset($actionLinks[1]);
        }

        self::$view->setVar('issues', $issues);
        self::$view->setVar('issuesNumber', $issuesNumber);
        self::$view->setVar('lottery', $lottery);
        self::$view->setVar('actionLinks', $actionLinks);
        self::$view->setVar('pageList', getPageList($issuesNumber, self::$pageNum));
        self::$view->render('lottery_issuelist');
    }

    //删除奖期
    public function deleteIssue()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $actionLinks = array(0 => array('title' => '奖期列表', 'url' => url('lottery', 'issueList', array('lottery_id' => $lottery_id))));
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg('找不到彩种');
        }
        if (!$deleteItems = $this->request->getPost('deleteItems', 'array')) {
            showMsg('请选择要删除的彩种');
        }

        foreach ($deleteItems as $v) {
            if (!issues::deleteItem($v)) {
                showMsg('删除失败！已录号的奖期不得删除');
            }
        }

        showMsg('删除成功', 0, $actionLinks);
    }

    public function diyGenIssue()
    {

        $lottery_id = $this->request->getGet('lottery_id', 'intval');

        if ($lottery_id != 21 && $lottery_id != 22) {
            showMsg("操作失败:此方法只针对六合彩双色球.");
        }

        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("操作失败:获取彩种信息失败.");
        }

        if ('genIssue' == $this->request->getPost('sa', 'trim', '')) {
            $startDate = $this->request->getPost('startDate', 'array');
            $endDate = $this->request->getPost('endDate', 'array');
            $issue = $this->request->getPost('issue', 'array');
            $actionLinks = array(0 => array('title' => '彩种列表', 'url' => url('lottery', 'lotteryList')));

            $number = issues::diyGenIssue($lottery, $startDate, $endDate, $issue);
            showMsg("操作成功，共生成 $number 个奖期", 0, $actionLinks);
        }

        self::$view->setVar("lottery", $lottery);
        self::$view->render('lottery_diygenissue');
    }

    //批量生成奖期
    public function genIssue()
    {
        $actionLinks = array(0 => array('title' => '彩种列表', 'url' => url('lottery', 'lotteryList')));
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        if (!$lottery = lottery::getItem($lottery_id)) {
            showMsg("操作失败:获取彩种信息失败.");
        }

        switch ($sa = $this->request->getPost('sa', 'trim')) {
            case 'confirm': // 生成奖期执行前的 "确认页面"
                $firstDate = $this->request->getPost('firstDate', 'trim');
                $startDate = strtotime($this->request->getPost('startDate', 'trim'));
                $endDate = strtotime($this->request->getPost('endDate', 'trim'));
                $date1 = date('Y-m-d', $startDate);  // 管理员提交的生成奖期的 起始时间
                $date2 = date('Y-m-d', $endDate);    // 管理员提交的生成奖期的 结束时间
                if ($endDate < $startDate) {
                    showMsg("结束日期不能小于开始日期", 1, $actionLinks);
                }

                //找出和现有奖期日期是否有重复
                $intersectDates = array('startday' => '0', 'endday' => '0', 'intersect_startday' => '0', 'intersect_endday' => '0');
                if ($dayIssues = issues::getDayIssueNumbers($lottery_id)) {
                    /**
                     * $dayIssues =
                     *       [2010-02-21] => 72
                     *       [2010-02-22] => 72
                     *       [2010-02-24] => 72
                     *       [2010-02-26] => 72
                     */
                    $tmp = array_keys($dayIssues);
                    $intersectDates = array('startday' => reset($tmp), 'endday' => end($tmp));
                    if ($date1 <= $intersectDates['endday']) {
                        if ($date1 < $intersectDates['startday']) {
                            $intersectDates['intersect_startday'] = $intersectDates['startday'];
                        } else {
                            $intersectDates['intersect_startday'] = $date1;
                        }

                        if ($date2 >= $intersectDates['endday']) {
                            $intersectDates['intersect_endday'] = $intersectDates['endday'];
                        } else {
                            $intersectDates['intersect_endday'] = $date2;
                        }
                    }
                }

                foreach ($dayIssues as $k => $v) {
                    if ($k == $date1) {
                        //$intersectDates = array_slice($dayIssues, $i, count($dayIssues), true);
                        $intersectDates['intersectday'] = $k;
                    }
                }

                self::$view->setVar("lottery", $lottery);
                self::$view->setVar("intersectDates", $intersectDates);
                self::$view->setVar("firstDate", $firstDate);
                self::$view->setVar("date1", $date1);
                self::$view->setVar("date2", $date2);
                self::$view->render('lottery_genissue_confirm');
                exit;
                break;

            case 'addItem': // 生成奖期
                $startDate = $this->request->getPost('startDate', 'trim');
                $endDate = $this->request->getPost('endDate', 'trim');
                $firstDate = $this->request->getPost('firstDate', 'trim');
                if ($endDate < $startDate) {
                    showMsg("结束日期不能小于开始日期");
                }

                $number = issues::genIssue($lottery['lottery_id'], $startDate, $endDate, $firstDate);
                showMsg("操作成功，共生成 $number 个奖期", 0, $actionLinks);
                break;
        }

        // 显示增加游戏奖期页面
        self::$view->setVar("lottery", $lottery);
        self::$view->setVar("needFirst", strpos($lottery['issue_rule'], 'd') === false);
        self::$view->render('lottery_genissue');
    }

    //增加彩种
    public function addLottery()
    {
        $locations = array(0 => array('title' => '彩种列表', 'url' => url('lottery', 'lotteryList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            //奖期时间段 end_sale为截止购买时间，提前几十秒禁止购买，亦可理解为等待开奖时间，同时可以买下一期
            $fragsort = $this->request->getPost('fragsort', 'array');
            $settings = array();
            $modes = $this->request->getPost('modes', 'array');
            if(empty($modes))
            {
                showMsg('至少选择一种模式!');
            }
            $is_use = $this->request->getPost('is_use', 'array');
            $starthour = $this->request->getPost('starthour', 'array');
            $startminute = $this->request->getPost('startminute', 'array');
            $startsecond = $this->request->getPost('startsecond', 'array');
            $firstendhour = $this->request->getPost('firstendhour', 'array');
            $firstendminute = $this->request->getPost('firstendminute', 'array');
            $firstendsecond = $this->request->getPost('firstendsecond', 'array');
            $endhour = $this->request->getPost('endhour', 'array');
            $endminute = $this->request->getPost('endminute', 'array');
            $endsecond = $this->request->getPost('endsecond', 'array');
            $cycle = $this->request->getPost('cycle', 'array');
            $end_sale = $this->request->getPost('end_sale', 'array');
            $drop_time = $this->request->getPost('drop_time', 'array');
            $code_time = $this->request->getPost('code_time', 'array');

            $tmp = array_count_values($fragsort);
            foreach ($tmp as $v) {
                if ($v > 1) {
                    showMsg("段间序号不能重复");
                }
            }

            foreach ($is_use as $k => $v) {
                $settings[$k]['is_use'] = $is_use[$k];
                $settings[$k]['start_time'] = $starthour[$k] . ':' . $startminute[$k] . ':' . $startsecond[$k];
                $settings[$k]['first_end_time'] = $firstendhour[$k] . ':' . $firstendminute[$k] . ':' . $firstendsecond[$k];
                $settings[$k]['end_time'] = $endhour[$k] . ':' . $endminute[$k] . ':' . $endsecond[$k];
                $settings[$k]['cycle'] = $cycle[$k];
                $settings[$k]['end_sale'] = $end_sale[$k];
                $settings[$k]['drop_time'] = $drop_time[$k];
                $settings[$k]['code_time'] = $code_time[$k];
                $settings[$k]['frag_sort'] = $fragsort[$k];
            }

            //返点差区间
            $min_rebate_gaps = $this->request->getPost('min_rebate_gaps', 'array');
            foreach ($min_rebate_gaps as $k => $v) {
                if (!$v['from'] && !$v['to'] && !$v['gap']) {
                    unset($min_rebate_gaps[$k]);
                }
            }
            if (!$min_rebate_gaps) {
                showMsg("必须设置至少一个区间");
            }

            $issue_rule = $this->request->getPost('issue_rule', 'trim');
            $issue_rule .= '|' . $this->request->getPost('resetrule_year', 'intval') . ',' . $this->request->getPost('resetrule_month', 'intval') . ',' . $this->request->getPost('resetrule_day', 'intval');
            $status = $this->request->getPost('status', 'intval');
            $mode = 0;
            foreach ($modes as $k=>$v)
            {
                $mode=$mode|$v;
            }
            $data = array(
                'name' => $this->request->getPost('name', 'trim'),
                'cname' => $this->request->getPost('cname', 'trim'),
                'lottery_type' => $this->request->getPost('lottery_type', 'intval'),
                'property_id' => $this->request->getPost('property_id', 'intval', '1|2|3|4|5'),
                'description' => $this->request->getPost('description', 'trim'),
                'settings' => serialize($settings),
                'issue_rule' => $issue_rule,
                'zx_max_comb' => $this->request->getPost('zx_max_comb', 'intval'),
                'total_profit' => $this->request->getPost('total_profit', 'floatval'),
                'min_profit' => $this->request->getPost('min_profit', 'floatval'),
                'min_rebate_gaps' => serialize($min_rebate_gaps),
                'yearly_start_closed' => $this->request->getPost('yearly_start_closed', 'trim'),
                'yearly_end_closed' => $this->request->getPost('yearly_end_closed', 'trim'),
                'catch_delay' => $this->request->getPost('catch_delay', 'intval'),
                'catch_retry' => $this->request->getPost('catch_retry', 'intval'),
                'catch_interval' => $this->request->getPost('catch_interval', 'intval'),
                'status' => $status,
                'sort' => $this->request->getPost('sort', 'intval'),
                'modes'=>$mode,
            );

            if (!lottery::addItem($data)) {
                showMsg("添加彩种失败!请检查数据输入是否完整");
            }

            if ($status == 8) {
                //清空缓存
                if (lottery::getCache('getItems08')) {
                    lottery::clearCache('getItems08');
                }
                if (lottery::getCache('getItems0-1')) {
                    lottery::clearCache('getItems0-1');
                }
            }


            showMsg("添加成功");
        }

        $hours = range(0, 23);
        $minutes = range(0, 59);
        for ($i = 0; $i < 10; $i++) {
            $hours[$i] = '0' . $hours[$i];
            $minutes[$i] = '0' . $minutes[$i];
        }
        $modes = lottery::getModesItemsByCond();
        self::$view->setVar('modes', $modes);
        self::$view->setVar('hours', $hours);
        self::$view->setVar('minutes', $minutes);
        self::$view->setVar('properties', $GLOBALS['cfg']['property']);
        self::$view->render('lottery_addlottery');
        //$GLOBALS['mc']->flush();
    }

    //修改彩种
    public function editLottery()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations = array(0 => array('title' => '彩种列表', 'url' => url('lottery', 'lotteryList', array('parent_id' => $parent_id))));
        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $fragsort = $this->request->getPost('fragsort', 'array');
            $modes = $this->request->getPost('modes', 'array', []);

            # TODO : 有些家不要的如果加了强限制根本没法编辑
            /*if(empty($modes))
            {
                showMsg('至少选择一种模式!');
            }*/

            $settings = array();
            $is_use = $this->request->getPost('is_use', 'array');
            $starthour = $this->request->getPost('starthour', 'array');
            $startminute = $this->request->getPost('startminute', 'array');
            $startsecond = $this->request->getPost('startsecond', 'array');
            $firstendhour = $this->request->getPost('firstendhour', 'array');
            $firstendminute = $this->request->getPost('firstendminute', 'array');
            $firstendsecond = $this->request->getPost('firstendsecond', 'array');
            $endhour = $this->request->getPost('endhour', 'array');
            $endminute = $this->request->getPost('endminute', 'array');
            $endsecond = $this->request->getPost('endsecond', 'array');
            $cycle = $this->request->getPost('cycle', 'array');
            $end_sale = $this->request->getPost('end_sale', 'array');
            $drop_time = $this->request->getPost('drop_time', 'array');
            $code_time = $this->request->getPost('code_time', 'array');

            $tmp = array_count_values($fragsort);
            foreach ($tmp as $v) {
                if ($v > 1) {
                    showMsg("段间序号不能重复");
                }
            }

            foreach ($is_use as $k => $v) {
                $settings[$k]['is_use'] = $is_use[$k];
                $settings[$k]['start_time'] = $starthour[$k] . ':' . $startminute[$k] . ':' . $startsecond[$k];
                $settings[$k]['first_end_time'] = $firstendhour[$k] . ':' . $firstendminute[$k] . ':' . $firstendsecond[$k];
                $settings[$k]['end_time'] = $endhour[$k] . ':' . $endminute[$k] . ':' . $endsecond[$k];
                $settings[$k]['cycle'] = $cycle[$k];
                $settings[$k]['end_sale'] = $end_sale[$k];
                $settings[$k]['drop_time'] = $drop_time[$k];
                $settings[$k]['code_time'] = $code_time[$k];
                $settings[$k]['frag_sort'] = $fragsort[$k];
            }

            //返点差区间
            $min_rebate_gaps = $this->request->getPost('min_rebate_gaps', 'array');
            foreach ($min_rebate_gaps as $k => $v) {
                if (!$v['gap']) {
                    unset($min_rebate_gaps[$k]);
                }
            }
            if (!$min_rebate_gaps) {
                showMsg("必须设置至少一个区间");
            }

            $issue_rule = $this->request->getPost('issue_rule', 'trim');
            $issue_rule .= '|' . $this->request->getPost('resetrule_year', 'intval') . ',' . $this->request->getPost('resetrule_month', 'intval') . ',' . $this->request->getPost('resetrule_day', 'intval');
            $mode = 0;
            foreach ($modes as $k=>$v)
            {
                $mode=$mode|$v;
            }
            $data = array(
                'name' => $this->request->getPost('name', 'trim'),
                'cname' => $this->request->getPost('cname', 'trim'),
                'lottery_type' => $this->request->getPost('lottery_type', 'intval'),
                'property_id' => $this->request->getPost('property_id', 'intval', '1|2|3|4|5'),
                'description' => $this->request->getPost('description', 'trim'),
                'settings' => serialize($settings),
                'issue_rule' => $issue_rule,
                'zx_max_comb' => $this->request->getPost('zx_max_comb', 'intval'),
                'total_profit' => $this->request->getPost('total_profit', 'floatval'),
                'min_profit' => $this->request->getPost('min_profit', 'floatval'),
                'min_rebate_gaps' => serialize($min_rebate_gaps),
                'yearly_start_closed' => $this->request->getPost('yearly_start_closed', 'trim'),
                'yearly_end_closed' => $this->request->getPost('yearly_end_closed', 'trim'),
                'catch_delay' => $this->request->getPost('catch_delay', 'intval'),
                'catch_retry' => $this->request->getPost('catch_retry', 'intval'),
                'catch_interval' => $this->request->getPost('catch_interval', 'intval'),
                'status' => $this->request->getPost('status', 'intval'),
                'sort' => $this->request->getPost('sort', 'intval'),
                'modes'=>$mode,
            );
            $lottery_id = $this->request->getPost('lottery_id', 'intval');
            if (!lottery::updateItem($lottery_id, $data)) {
                showMsg("没有数据被更新", 1, $locations);
            }

            //清空缓存
            if (lottery::getCache('getItems08')) {
                lottery::clearCache('getItems08');
            }
            if (lottery::getCache('getItems0-1')) {
                lottery::clearCache('getItems0-1');
            }

            if ($GLOBALS['redis']->hGet('lotteryList', $lottery_id)) {
                $GLOBALS['redis']->hdel('lotteryList', $lottery_id);
            }
            showMsg("更新成功", 0, $locations);
        }

        if (!$lottery_id = $this->request->getGet('lottery_id', 'trim')) {
            showMsg("参数无效");
        }
        $lottery = lottery::getItem($lottery_id);
        foreach ($lottery['settings'] as &$v) {
            $tmp = explode(':', $v['start_time']);
            $v['starthour'] = $tmp[0];
            $v['startminute'] = $tmp[1];
            $v['startsecond'] = $tmp[2];
            $tmp = explode(':', $v['first_end_time']);
            $v['firstendhour'] = $tmp[0];
            $v['firstendminute'] = $tmp[1];
            $v['firstendsecond'] = $tmp[2];
            $tmp = explode(':', $v['end_time']);
            $v['endhour'] = $tmp[0];
            $v['endminute'] = $tmp[1];
            $v['endsecond'] = $tmp[2];
        }
        unset($v);

        $tmp = explode('|', $lottery['issue_rule']);
        $lottery['issue_rule1'] = $tmp[0];
        $lottery['issue_rule2'] = explode(',', $tmp[1]);

        $hours = range(0, 23);
        $minutes = range(0, 59);
        for ($i = 0; $i < 10; $i++) {
            $hours[$i] = '0' . $hours[$i];
            $minutes[$i] = '0' . $minutes[$i];
        }
        $modes = lottery::getModesItemsByCond();
        self::$view->setVar('modes', $modes);
        self::$view->setVar('hours', $hours);
        self::$view->setVar('minutes', $minutes);
        self::$view->setVar('lottery', $lottery);
        self::$view->setVar('parentLotterys', lottery::getItems(0));
        self::$view->setVar('properties', $GLOBALS['cfg']['property']);
        self::$view->render('lottery_addlottery');
        //$GLOBALS['mc']->flush();
    }

}

?>