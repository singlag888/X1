<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}
use common\model\baseModel;
/**
 * 控制器：游戏管理
 */
class gameController extends sscAdminController
{

    //>>修改为每页查询20条
    static $teamReportPerPage = 20;
    //方法概览
    public $titles = array(
        'getIssue' => '得到彩种奖期',
        'gameList' => '游戏记录',
        'packageDetail' => '订单详情',
        'easyPackageDetail' => '订单简易详情',
        'traceDetail' => '追号单详情',
        'easyTraceDetail' => '追号单简易详情',
        'cacelPackage' => '系统撤单',
        'cancelTrace' => '追号单撤单',
        'traceList' => '追号记录',
        'unlockTrace' => '追号单解锁',
        //几个报表
        'orderList' => '帐变列表',
        'childReport' => '会员报表',
        'saleReport' => '代理损益报表',
        'teamReport' => '团队报表',
        'totalReport' => '总账报表',
        'realWin' => '实际盈亏',
        'singleSaleReport' => '单期盈亏报表',
        'topBalanceList' => '团队余额',
        'childPackageList' => '团队投注明细',
        'snowExportGameList' => '导出投注记录',
        'snowExportGameListGetTotalCount' => '导出投注记录,获取总条数等相关信息',
        'snowExportGameListGetData' => '导出投注记录,获取数据',
        'thePrizeAjax'=>'开奖',
        'totalReportForLottery'=>'彩种统计',

    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //ajax 读取某彩种奖期
    public function getIssue()
    {
        $lottery_id = $this->request->getPost('lottery_id', 'intval');
        $start_time = $this->request->getPost('start_time', 'trim', date('Y-m-d'));
        $issues = issues::getItems($lottery_id, date('Y-m-d', strtotime($start_time)));
        echo json_encode($issues);
    }

    /**
     * 获取当前月份所有的具体的天数日期
     * @return array
     */
    public function getCurMonthDays(){
        $y =  date('Y');
        $m =  date('m');
       // $dayLen =   cal_days_in_month(CAL_GREGORIAN,$m, $y);
        $dayLen =   date("t",strtotime($y.'-'.$m));
        $days = [];
        //倒序
        for( $i = $dayLen; $i>0;$i-- ){
            $days[] = $y.'-'.$m.'-'.str_pad($i,2,'0',STR_PAD_LEFT);
        }
        return $days;
    }


    public function gameList()
    {
         //游戏id
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        //是否是测试帐号  默认值为0
        $is_test = $this->request->getGet('is_test', 'intval', 0);
        //日期
        $date = $this->request->getGet('date', 'trim');
        //玩法id
        $method_id = $this->request->getGet('method_id', 'intval');
        //奖期
        $issue = $this->request->getGet('issue', 'trim');
        //元角模式
        $modes = $this->request->getGet('modes', 'floatval');
        //订单编号
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        //所属总代
        $top_username = $this->request->getGet('top_username', 'trim');
        //用户名
        $username = $this->request->getGet('username', 'trim');
        //是否包含下级
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        //游戏开始时间
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        //游戏结束时间
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        //订单状态
        $cancel_status = $this->request->getGet('cancel_status', 'intval');
        //中奖金额 必须>=
        $winMoney = $this->request->getGet('win_money', 'floatval');
        //投注金额 必须=
        $betMoney = $this->request->getGet('bet_money', 'floatval');
        //排序
        $sort = $this->request->getGet('sort', 'intval',-1);
        /*********************** snow 217-09-29 添加判断 ,确定在导出时最多只能导出的数据**********************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        if(($export_game = $this->request->getGet('export_game', 'intval')) === 1){

            $startPos = -1;   //>>start 为-1 导出全部数据
            $result = $this->_toDetermineOfmonthFormat($start_time,$end_time);
            if($result['flag'] === false){
                showMsg('只能导出1周之内的数据');
            }
        }
        /*********************** snow 217-09-29 添加判断 ,确定在导出时最多只能导出的数据**********************************/
        //当前页面 组装起来的数据分页的起始位置

        $package_id = projects::dewrapId($wrap_id);
        $packages = array();
        $packagesNumber = 0;
        $packagesAll = ['prizes'=>0,'amounts'=>0];
        //必须指定订单号或用户名才能查询 否则也没有意义
        if ($package_id) {
            if ($package = projects::getPackageSnow($package_id,0 , false,$betMoney,$winMoney)) {
                $tmp = users::getItem($package['user_id']);
                $package['username'] = $tmp['username'];
                $package['user_status'] = $tmp['status'];
                $packages[0] = $package;
                $packagesNumber = 1;
                $packagesAll = ['prizes'=>$package['prize'],'amounts'=>$package['amount']];
            }
        }
        elseif ($lottery_id || $issue || ($top_username ? $top_username : $username) || $winMoney > 0 || $betMoney > 0) {
            $order_by = $sort != -1 ? ($sort == 1 ? 'amount DESC,package_id DESC' : 'prize DESC,package_id DESC') : '';
            //>>参数
            $options = [
                'lottery_id'                => $lottery_id,   //>>彩种id
                'is_test'                   => $is_test,  //>>默认不显示测试账号
                'issue'                     => $issue,  //>>奖期
                'modes'                     => $modes,   //>>元角模式
                'user_id'                   => $top_username ? $top_username : $username,  //>>用户名称
                'include_childs'            => $include_childs,   //>>是否包含下级
                'start_date'                => $start_time,  //>>起始时间
                'end_date'                  => $end_time,  //>>结束时间
                'cancel_status'             => $cancel_status,  //>>是否撤单//>>撤单状态
                'betMoney'                  => $betMoney,   //>>投注金额
                'winMoney'                  => $winMoney,   //>>中奖金额
            ];
            //>>snow 把所有参数以数组传入.
            $packagesNumbers = projects::getNewPackagesNumber($options);
            //>>获取正确页码值
            if (!empty($packagesNumbers)) {
                $packagesNumber = $packagesNumbers['count'];
                $startPos = getStartOffset($curPage, $packagesNumber);
                $packages       = projects::getPackagesAdminExclude(['start' => $startPos, 'orderBy' => $order_by]);
                $packagesAll    = ['prizes'=>$packagesNumbers['prizes'],'amounts'=>$packagesNumbers['amounts']];
            } else {
                $startPos       = 0;
                $packages       = [];
                $packagesAll    = ['prizes' => 0, 'amount' => 0];
            }
        }
        //得到所有彩种
        // $lotterys = lottery::getItems(0, -1);
        $lotterys = lottery::getItemsNew(['lottery_id', 'property_id','zx_max_comb','total_profit','property_id','cname'],0,-1);
        self::$view->setVar('lotterys', $lotterys)->setVar('json_lotterys', json_encode($lotterys));
        //得到彩种玩法组玩法二级联动
        $methods = methods::getItems(0, 0, 8, 2);
        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0))->setVar('json_methods', json_encode($methods));
        //得到所有总代
        // $topUsers = users::getUserTree(0);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        //>> author snow 修改为页面赋值
        self::$view->setVar('json_topUsers', $topUsers);

        $totalAmount = $totalPrize = 0;
        //为算出奖金，先得到这些用户的返点
        $user_ids = array_keys(array_spec_key($packages, 'user_id'));
        $userRebates = userRebates::getUsersRebates($user_ids, 0, 1);
        // 取得彩种 和奖期

        $lotteryIssues = $tmp = array();
        //amount 投注金额 prize 赢取奖金;
        foreach ($packages as $k => $v) {
            $packages[$k]['wrap_id'] = projects::wrapId($v['package_id'], $v['issue'], $v['lottery_id']);

            /************************ snow 加一个判断 ,确认键值是否存在 ,在进行计算 *********************************************/

            if(isset($lotterys[$v['lottery_id']]) && isset($userRebates[$v['user_id']][$lotterys[$v['lottery_id']]['property_id']])){
                $packages[$k]['prize_mode'] =  2 * $lotterys[$v['lottery_id']]['zx_max_comb'] * (1 - $lotterys[$v['lottery_id']]['total_profit'] + $userRebates[$v['user_id']][$lotterys[$v['lottery_id']]['property_id']] - $v['cur_rebate']);
            }

            $totalAmount += $v['amount'];
            $tmp[$v['lottery_id']][] = $v['issue'];
            $totalPrize += $v['prize'];
        }
        foreach ($tmp as $k => $v) {
            $lotteryIssues[$k] = issues::getItemsByIssue($k, $v);
        }

        /********************************** 在这是添加一个判断 ,如果是导出数据就调用方法,并退出***********************************************/
        if($export_game === 1){
            //>>调用数据导出方法
            $this->_exportGameListData($packages, $lotteryIssues, $lotterys);

        }else{
            //日期列表
            self::$view->setVar('dates', $this->getCurMonthDays() );

            //预设查询框
            self::$view->setVar('sort', $sort);
            self::$view->setVar('lottery_id', $lottery_id);
            self::$view->setVar('is_test', $is_test);
            self::$view->setVar('date', $date);
            self::$view->setVar('method_id', $method_id);
            self::$view->setVar('issue', $issue);
            self::$view->setVar('lotteryIssues', $lotteryIssues);
            self::$view->setVar('modes', $modes);
            self::$view->setVar('wrap_id', $wrap_id);
            self::$view->setVar('top_username', $top_username);
            self::$view->setVar('username', $username);
            self::$view->setVar('include_childs', $include_childs);
            self::$view->setVar('start_time', $start_time);
            self::$view->setVar('end_time', $end_time);
            self::$view->setVar('cancel_status', $cancel_status);
            self::$view->setVar('win_money', $winMoney);
            self::$view->setVar('bet_money', $betMoney);

            self::$view->setVar('packages', $packages);
            self::$view->setVar('packagesAll', $packagesAll );
            self::$view->setVar('totalAmount', $totalAmount);
            self::$view->setVar('totalPrize', $totalPrize);
            self::$view->setVar('pageList', getPageList($packagesNumber, DEFAULT_PER_PAGE));
            //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
            self::$view->render('game_gamelist');
        }
        /********************************** 在这是添加一个判断 ,如果是导出数据就调用方法,并退出***********************************************/

    }
    /*********************************** snow  弹出数据到excel 表格 down 2017-09-29*********************************************************/
    /**
     * 处理数据,组合成 导出excel 表格需要的格式
     * @param $data
     * @param $lotteryIssues
     * @param $lotterys
     * @return bool
     */
    private function _exportGameListData($data, $lotteryIssues, $lotterys)
    {
        //>>如果没有数据,直接返回
        if(!is_array($data) || empty($data)){
            showMsg('没有可导出的数据');
        }
        //>用户状态
        $user_status = [
            '0' => '[已删除]',
            '1' => '[已冻结]',
            '5' => '[已回收]',
        ];

        //>>撤单状态
        $cancel_status = [
            '1' => '用户撤单',
            '2' => '追中撤单',
            '3' => '出号撤单',
            '4' => '未开撤单',
            '9' => '系统撤单',
        ];
        //>>导出表格的字段名称
        $title=['订单编号','用户','IP','彩种','奖期','单倍注数','倍数','模式','投注金额','购买时间','奖期截止时间','开奖号','中奖状态','奖金','派奖状态','撤单状态'];
        //>>处理数据 组合成需要的数据
        $excelArray = [];//>>需要导出的数据
        foreach($data as $key => $val){

            //>>按照$title的顺序组装数据
            $excelArray[] = [
                $val['wrap_id'],
                isset($user_status[$val['user_status']]) ?($val['is_test'] ? $val['username'] .'测试' : '' . $user_status[$val['user_status']])  : $val['username'] ,
                $val['user_id'],
                isset($lotterys[$val['lottery_id']]['cname']) ? $lotterys[$val['lottery_id']]['cname'] : '未知',
                $val['issue'],
                $val['single_num'],
                $val['multiple'],
                isset($GLOBALS['cfg']['modes'][strval(floatval($val['modes']))]) ? $GLOBALS['cfg']['modes'][strval(floatval($val['modes']))] : '',
                $val['amount'],
                $val['create_time'],
                isset($lotteryIssues[$val['lottery_id']][$val['issue']]['end_sale_time']) ? $lotteryIssues[$val['lottery_id']][$val['issue']]['end_sale_time'] : '',
                $val['lottery_id'] != 15 ? (isset($lotteryIssues[$val['lottery_id']][$val['issue']]['code']) && $lotteryIssues[$val['lottery_id']][$val['issue']]['code'] != '' ? $lotteryIssues[$val['lottery_id']][$val['issue']]['code'] : '未开') : '-',
                $val['check_prize_status'] == 0 ? '未判断' :($val['check_prize_status'] == 1 ? '中奖' : '没中'),
                $val['prize'] > 0 ? $val['prize'] : '',
                $val['check_prize_status'] == 1 ? ($val['send_prize_status'] == 0 ? '未派奖' : ($val['send_prize_status'] == 1 ? '已派奖' : '')) : '',
                $val['cancel_status'] == 0 ? (isset($cancel_status[$val['cancel_status']])  ? $cancel_status[$val['cancel_status']] : '') : '',

            ];
        }
        unset($data);
        $fileName = '游戏记录' . date('YmdHis');
        $this->_exportExcelData($excelArray, $title, $fileName);

    }

    //>>导出数据到excel 表格
    /**
     * @param $excelArray array  需要导出的数据
     * @param $title  array  表格标题
     * @param null $fileName  导出名称
     */
    private function _exportExcelData($excelArray, $title,$fileName = null){

        //>>判断传入数据是否存在
        //>>$excelArray 为二维数组 ,$title 一维数组 ,全部必须是索引数组 且,$excelArray 下面每个数组的长度与$title 一至 数据一对应.
        if(!is_array($excelArray) || empty($excelArray) || !is_array($title) || empty($title)){
            //>>传入参数出错
            showMsg('导出参数错误');
        }
        //>>处理文件名称
        $fileName = is_null($fileName) ? '结果' . date('YmdHis') : $fileName;
        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel.php';
        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel2007.php';
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator($GLOBALS['SESSION']['admin_username'])->setLastModifiedBy($GLOBALS['SESSION']['admin_username']);
        foreach ($title as $key => $value) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(PHPExcel_Cell::stringFromColumnIndex($key) . '1', $value);
            in_array($key,[4,5])?$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($key))->setWidth(20):$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($key))->setWidth(15);
        }
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        for ($index = 0; $index < count($excelArray); $index++) {
            $col = 0;
            foreach ($excelArray[$index] as $key => $value) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(PHPExcel_Cell::stringFromColumnIndex($col) . ($index + 2), (string) $value);
                $col++;
            }
        }
        $format = 'Excel5';
        $endfix = '.xls';
        $contentType = 'Content-Type: application/vnd.ms-excel';
        if (extension_loaded('ZipArchive')) {
            $format = 'Excel2007';
            $endfix = '.xlsx';
            $contentType = 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
        $objPHPExcel->getActiveSheet()->setTitle($fileName);
        $objPHPExcel->setActiveSheetIndex(0);
        header($contentType);
        header('Content-Disposition: attachment;filename="' . $fileName . $endfix . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $format);
        $objWriter->save('php://output');
        exit;
    }


    /**
     * by snow  2017-09-29
     * @param $startDate string 开始日期
     * @param $endDate    string  结束日期
     * @return bool      返回 是两个日期之差是否在一个月之内
     */
    private function _toDetermineOfmonthFormat(&$startDate, &$endDate)
    {


        //>>对时间格式进行验证
        if(date('Y-m-d H:i:s', strtotime($startDate)) !== $startDate){
            showMsg('时间格式不正确');
        }
        if(date('Y-m-d H:i:s', strtotime($endDate)) !== $endDate){
            showMsg('时间格式不正确');
        }
        $startTime             = strtotime($startDate);  //>>把开始日期转换成时间戳
        $endTime               = strtotime($endDate);  //>>把结束日期转换成时间戳
        if(($actTime = $endTime - 24 * 3600 * 8) > $startTime){
            $startDate = date('Y-m-d H:i:s', $actTime);
            $result = ['startDate' => $startDate, 'flag' => false];
        }else{
            $result = ['startDate' => $startDate, 'flag' => true];
        }

        return $result;


    }

    /*********************************** snow  弹出数据到excel 表格 up 2017-09-29*********************************************************/
    public function packageDetail()
    {
        $wrap_id = $this->request->getGet('wrap_id', 'trim');

        if (is_numeric($wrap_id)) {
            $package_id = $wrap_id;
        }
        else {
            if (!$package_id = projects::dewrapId($wrap_id)) {
                showMsg('订单编号无效');
            }
        }
        if (!$package = projects::getPackage($package_id)) {
            showMsg('找不到该订单');
        }
        // $package['wrap_id'] = projects::wrapId($package['package_id'], $package['issue'], $package['lottery_id']);
        $package['wrap_id'] = $wrap_id;

        //注：也允许查看被冻结用户的订单历史
        if (!$user = users::getItem($package['user_id'])) {
            //showMsg('找不到该用户');
        }
        //如果是追号单，跳转至
        if ($package['trace_id']) {
            redirect(url('game', 'traceDetail', array('wrap_id' => traces::wrapId($package['trace_id'], $package['issue'], $package['lottery_id']))));
        }

        //得到所有彩种
        $lottery = lottery::getItem($package['lottery_id']);
        self::$view->setVar('lottery', $lottery);
        //得到该用户返点，为计算奖金系列
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        $prizeMode = 2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $userRebate - $package['cur_rebate']);
        //JYZ-283快乐扑克特殊处理：使用豹子11050代替转直注数,所以整体/24
        if ($lottery['property_id'] == 4) {
            $prizeMode = floor($prizeMode / 24);
        }
        self::$view->setVar('prizeMode', $prizeMode);
        //得到开奖号码
        $openCodes = issues::getCodes($package['lottery_id'], array($package['issue']));
        $openCode = reset($openCodes);
        self::$view->setVar('openCode', $openCode);

        $projects = projects::getItems(0, '', $package_id);
        $totalAmount = 0;
        //计算如果中奖的奖金
        $prizes = prizes::getItems($package['lottery_id'], 0, 0, 0, 1);
        foreach ($projects as $k => $v) {
            $projects[$k]['codes'] = explode("|", $v['code']);
            $projects[$k]['will_prize'] = number_format($v['multiple'] * $v['modes'] * $prizes[$v['method_id']][1]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
            $totalAmount += $v['amount'];
        }

        //时间
        $lotteryIssues = issues::getItemsByIssue($package['lottery_id'], array($package['issue']));
        //得到彩种玩法组玩法二级联动
        $methods = methods::getItems(0, 0, -1, 2);
        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0));
        //得到管理员列表
        $admins = admins::getItems();
        self::$view->setVar('admins', $admins);

        self::$view->setVar('package', $package);
        self::$view->setVar('lotteryIssues', $lotteryIssues);
        self::$view->setVar('user', $user);
        self::$view->setVar('projects', $projects);
        self::$view->setVar('totalAmount', $totalAmount);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_packagedetail');
    }

    public function easyPackageDetail(){
        $wrap_id = $this->request->getGet('wrap_id', 'trim');

        if (is_numeric($wrap_id)) {
            $package_id = $wrap_id;
        }
        else {
            if (!$package_id = projects::dewrapId($wrap_id)) {
                showMsg('订单编号无效');
            }
        }
        if (!$package = projects::getPackage($package_id)) {
            showMsg('找不到该订单');
        }
        // $package['wrap_id'] = projects::wrapId($package['package_id'], $package['issue'], $package['lottery_id']);
        $package['wrap_id'] = $wrap_id;

        //注：也允许查看被冻结用户的订单历史
        if (!$user = users::getItem($package['user_id'])) {
            //showMsg('找不到该用户');
        }
        //如果是追号单，跳转至
        if ($package['trace_id']) {
            redirect(url('game', 'traceDetail', array('wrap_id' => traces::wrapId($package['trace_id'], $package['issue'], $package['lottery_id']))));
        }

        //得到所有彩种
        $lottery = lottery::getItem($package['lottery_id']);
        //得到该用户返点，为计算奖金系列
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        $projects = projects::getItems(0, '', $package_id);
        //计算如果中奖的奖金
        $prizes = prizes::getItems($package['lottery_id'], 0, 0, 0, 1);

        foreach ($projects as $k => $v) {
            /***************** snow 添加赔率返点显示在页面上 start******************************/

            //>>判断如果是xz开头的.只有奖金,没有返点
            $xy_prefix = XY_PREFIX;

            if (strpos($xy_prefix,'xz') === 0)
            {
                $projects[$k]['codes']          = explode("|", $v['code']);
                $projects[$k]['prize_rebate']   = number_format($v['modes'] * $prizes[$v['method_id']][1]['prize'],2) ;
                /***************** snow 添加赔率返点显示在页面上 end******************************/
                $projects[$k]['will_prize']     = number_format($v['multiple'] * $projects[$k]['prize_rebate'], 2);

            } else {

                $projects[$k]['codes']          = explode("|", $v['code']);
                $projects[$k]['prize_rebate']   = (number_format($v['modes'] * $prizes[$v['method_id']][1]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']),2)) . '/' . number_format($v['cur_rebate'] * 100, 2) . '%';
                /***************** snow 添加赔率返点显示在页面上 end******************************/
                $projects[$k]['will_prize']     = number_format($v['multiple'] * $v['modes'] * $prizes[$v['method_id']][1]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
            }
        }

        //得到彩种玩法组玩法二级联动
        $methods = methods::getItems(0, 0, -1, 2);
        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0));
        self::$view->setVar('projects', $projects);
        self::$view->render('game_easypackagedetail');
    }

    //追号详情
    public function traceDetail()
    {
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        if (!$trace_id = traces::dewrapId($wrap_id)) {
            showMsg('追号单编号无效');
        }
        if (!$trace = traces::getItem($trace_id)) {
            showMsg('找不到该追号单');
        }
        if (!$packages = projects::getPackages($trace['lottery_id'], -1, -1, '', $trace['trace_id'], 0, '', 0, '', '', '', '', -1, 'package_id ASC')) {
            showMsg('找不到追号详细列表');
        }

        $package = reset($packages);
        //得到所有彩种
        $lottery = lottery::getItem($trace['lottery_id']);
        self::$view->setVar('lottery', $lottery);
        //得到该用户返点，为计算奖金系列
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        $prizeMode = 2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $userRebate - $package['cur_rebate']);
        self::$view->setVar('prizeMode', $prizeMode);
        //得到开奖号码列表
        $issues = array_keys(array_spec_key($packages, 'issue'));
        $openCodes = issues::getCodes($trace['lottery_id'], $issues);
        self::$view->setVar('openCodes', $openCodes);

        //订单详情
        $projects = projects::getItems(0, '', $package['package_id']);
        //先得到用户返点
        //得到用户返点
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        $prizes = prizes::getItems($package['lottery_id'], 0, 0, 0, 1);
        foreach ($projects as $k => $v) {
            //计算如果中奖的奖金
            if ($v['cur_rebate'] > $userRebate) {
                showMsg('系统出错');
            }
            $projects[$k]['will_prize'] = number_format($v['modes'] * $prizes[$v['method_id']][1]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
        }
        //得到彩种玩法组玩法
        $methods = methods::getItems(0, 0, -1, 2);
        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0));
        self::$view->setVar('wrap_id', $wrap_id);
        self::$view->setVar('package', $package);
        self::$view->setVar('trace', $trace);
        self::$view->setVar('projects', $projects);
        self::$view->setVar('packages', $packages);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_tracedetail');
    }

    //追号简易详情
    public function easyTraceDetail()
    {
        $wrap_id = $this->request->getGet('wrap_id', 'trim');


        if (is_numeric($wrap_id)) {
            $package_id = $wrap_id;
        }
        else {
            if (!$package_id = projects::dewrapId($wrap_id)) {
                showMsg('订单编号无效');
            }
        }
        if (!$package = projects::getPackage($package_id)) {
            showMsg('找不到该订单');
        }

        $package['wrap_id'] = $wrap_id;

        if ($package['trace_id']) {
            $wrap_id = traces::wrapId($package['trace_id'], $package['issue'], $package['lottery_id']);
        }

        if (!$trace_id = traces::dewrapId($wrap_id)) {
            showMsg('追号单编号无效');
        }
        if (!$trace = traces::getItem($trace_id)) {
            showMsg('找不到该追号单');
        }
        if (!$packages = projects::getPackages($trace['lottery_id'], -1, -1, '', $trace['trace_id'], 0, '', 0, '', '', '', '', -1, 'package_id ASC')) {
            showMsg('找不到追号详细列表');
        }

        $package = reset($packages);
        //得到所有彩种
        $lottery = lottery::getItem($trace['lottery_id']);

        //得到开奖号码列表
        $issues = array_keys(array_spec_key($packages, 'issue'));
        $openCodes = issues::getCodes($trace['lottery_id'], $issues);


        //订单详情
        $projects = projects::getItems(0, '', $package['package_id']);
        //先得到用户返点
        //得到用户返点
        $userRebate = userRebates::getUserRebate($package['user_id'], $lottery['property_id']);
        $prizes = prizes::getItems($package['lottery_id'], 0, 0, 0, 1);
        foreach ($projects as $k => $v) {
            //计算如果中奖的奖金
            if ($v['cur_rebate'] > $userRebate) {
                showMsg('系统出错');
            }
            $projects[$k]['will_prize'] = number_format($v['modes'] * $prizes[$v['method_id']][1]['prize'] * (1 - $lottery['total_profit'] + $userRebate - $v['cur_rebate']) / (1 - $lottery['total_profit']), 4);
        }
        //得到彩种玩法组玩法
        $methods = methods::getItems(0, 0, -1, 2);
        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0));
        self::$view->setVar('openCodes', $openCodes);
        self::$view->setVar('package', $package);
        self::$view->setVar('projects', $projects);
        self::$view->setVar('packages', $packages);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_easytracedetail');
    }

    public function cacelPackage()
    {
        $user_id = $this->request->getPost('user_id', 'intval');
        $package_id = $this->request->getPost('package_id', 'intval');
        if (!$package = projects::getPackage($package_id)) {
            showMsg('找不到该订单');
        }

        game::cancelPackage($package, 9, $GLOBALS['SESSION']['admin_id']); //1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
        showMsg('撤单成功');
    }

    //取消追号
    public function cancelTrace()
    {
        $trace_id = $this->request->getPost('trace_id', 'intval');
        $package_ids = $this->request->getPost('pkids', 'array');
        if (!is_array($package_ids) || !count($package_ids)) {
            showMsg('参数无效');
        }

        try {
            if (!$trace = traces::getItem($trace_id)) {
                showMsg('找不到该追号单');
            }
            game::cancelTrace($trace['user_id'], $trace_id, $package_ids, $GLOBALS['SESSION']['admin_id']);
            showMsg('取消追号单成功');
        } catch (exception2 $e) {
            showMsg($e->getMessage());
        }
    }

    public function unlockTrace()
    {
        $wrap_id = $this->request->getPost('wrap_id', 'trim');
        if (!$trace_id = traces::dewrapId($wrap_id)) {
            showMsg('追号单编号无效');
        }
        if (!$trace = traces::getItem($trace_id)) {
            showMsg('找不到该追号单');
        }

        $result = array('errno' => 0, 'errstr' => '');
        if (!traces::updateItem($trace_id, array('is_locked' => 0))) {
            $result = array('errno' => 1, 'errstr' => '更新失败');
        }
        die(json_encode($result));
    }

    public function traceList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        //>> snow 测试条件 默认为否
        $is_test = $this->request->getGet('is_test', 'intval', 0);
        $issue = $this->request->getGet('issue', 'trim');
        $modes = $this->request->getGet('modes', 'floatval');
        $top_username = $this->request->getGet('top_username', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);

        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>> snow 获取当前分页页码
        $this->request->getGet('curPage', 'intval', 1);
        $traces = array();
        $tracesNumber = 0;
        if ($start_time) {
            /*************************snow  排除dcsite*************************/
            //>>不需要了.

            //>>参数
            $options = [
                'lottery_id'                => $lottery_id,   //>>彩种id
                'is_test'                   => $is_test,  //>>默认不显示测试账号
                'start_issue'               => $issue,  //>>开始奖期
                'modes'                     => $modes,   //>>元角模式
                'user_id'                   => $top_username ? $top_username : $username,  //>>用户名称
                'include_childs'            => $include_childs,   //>>是否包含下级
                'start_date'                => $start_time,  //>>起始时间
                'end_date'                  => $end_time,  //>>结束时间
                'start'                     => -1,  //>>limit 的开始记录数 -1 表示不分页
            ];
            //>>snow 把所有参数以数组传入.
            $tracesNumber = traces::getItemsNumberExclude($options);
            //>>获取正确的分页页码?
            $startPos = getStartOffset($curPage, $tracesNumber);
            $options['start'] = $startPos;  //>>修改分页起始值.
            $traces = traces::getItemsExclude($options);
            /*************************snow  排除dcsite*************************/
            foreach ($traces as $k => $v) {
                $traces[$k]['wrap_id'] = traces::wrapId($v['trace_id'], $v['start_issue'], $v['lottery_id']);
            }
        }

        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id','cname','name']);
        self::$view->setVar('lotterys', $lotterys)->setVar('json_lotterys', json_encode($lotterys));
        //得到所有总代
        // $topUsers = users::getUserTree(0);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        //>>author snow 修改为页面赋值 不传入json
        self::$view->setVar('json_topUsers', $topUsers);

        //日期列表
        $dates = array();
        for ($i = 0; $i < 30; $i++) {
            $dates[] = date('Y-m-d', time() - 86400 * $i);
        }
        self::$view->setVar('dates', $dates);

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('issue', $issue);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('modes', $modes);
        self::$view->setVar('top_username', $top_username);
        self::$view->setVar('username', $username);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);

        self::$view->setVar('traces', $traces);
        self::$view->setVar('tracesNumber', $tracesNumber);
        self::$view->setVar('pageList', getPageList($tracesNumber, DEFAULT_PER_PAGE));
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->setVar('canUnlockTrace', adminGroups::verifyPriv(array(CONTROLLER, 'unlockTrace')));
        self::$view->render('game_tracelist');
    }

    public function orderList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $method_id = $this->request->getGet('method_id', 'intval');
        $issue = $this->request->getGet('issue', 'trim');
        $types = $this->request->getGet('type', 'array');
        $top_username = $this->request->getGet('top_username', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        $start_amount = $this->request->getGet('start_amount', 'floatval', 0);
        $end_amount = $this->request->getGet('end_amount', 'floatval', 0);
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        $curPage = $this->request->getGet('curPage', 'intval', 1);//>>获取传入的页码 snow
        $orders = array();
        $trafficInfo = array('count' => 0, 'total_amount' => 0);
        $isCanSearch = false;

        //必须指定用户和时间范围才能查询 否则也没有意义
        if (($top_username ? $top_username : $username) || count($types) > 0) {
            $trafficInfo = orders::getTrafficInfo($lottery_id, $issue, $types, $top_username ? $top_username : $username, $include_childs, $start_amount, $end_amount, $start_time, $end_time,1);

            /*************** snow 添加 对传入页码值进行处理********************/
            $startPos = getStartOffset($curPage, $trafficInfo['count']);
            /*************** snow 添加 对传入页码值进行处理********************/
            $orders = orders::getItems($lottery_id, $issue, $types, $top_username ? $top_username : $username, $include_childs, $start_amount, $end_amount, $start_time, $end_time, $startPos, DEFAULT_PER_PAGE,1);
            $isCanSearch = true;
        }

        $totalAmount = 0;
        foreach ($orders as $k => $v) {
            $orders[$k]['business_url'] = orders::getBusinessUrl($v['type'], $v['business_id']);
            $totalAmount += $v['amount'];
        }

        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id','cname']);
        self::$view->setVar('lotterys', $lotterys)->setVar('json_lotterys', json_encode($lotterys));
        //得到彩种玩法组玩法二级联动
        $methods = methods::getItems(0, 0, -1, 2);
        self::$view->setVar('json_methods', json_encode($methods));
        //得到所有总代
        // $topUsers = users::getUserTree(0,true,0,-1,-1,'',1);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
            'freeze' => 1
        ]);
        //>>author snow  修改,直接传数组,不传json
        self::$view->setVar('json_topUsers', $topUsers);

        //日期列表
        $dates = array();
        for ($i = 0; $i < 30; $i++) {
            $dates[] = date('Y-m-d', time() - 86400 * $i);
        }
        self::$view->setVar('dates', $dates);

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('admins', array_column(admins::getItems(), 'username', 'admin_id'));
        self::$view->setVar('issue', $issue);
        self::$view->setVar('types', $types);
        self::$view->setVar('top_username', $top_username);
        self::$view->setVar('username', $username);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('start_amount', $start_amount);
        self::$view->setVar('end_amount', $end_amount);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('orders', $orders);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('isCanSearch', $isCanSearch);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('orderTypes', $GLOBALS['cfg']['orderTypes']);
        self::$view->setVar('pageList', getPageList($trafficInfo['count'], DEFAULT_PER_PAGE));
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_orderlist');
    }

    //会员报表

    /**
     * author snow 修改添加多项数据
     */
    public function childReport()
    {
        $top_username   = $this->request->getGet('top_username', 'trim');
        $username       = $this->request->getGet('username', 'trim');
        $ignore       = $this->request->getGet('ignore', 'intval',0);
        $ref_group_id   = $this->request->getGet('ref_group_id', 'intval',-1);
        $start_time     = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        $end_time       = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        $flag = 0;
        $totalWithdraws = $totalDeposits = $childReport = $totalInfo = $users = $recentBuy = array();
        //>>所有层级数据
        $cardGroups = cards::getGroups();
        self::$view->setVar('cardGroups', $cardGroups);
        self::$view->setVar('ignore', $ignore);
        self::$view->setVar('ref_group_id', $ref_group_id);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        unset($cardGroups);
        //>>是否忽略时间查询条件.
        if ($ignore === 1) {
            $start_time = '';
            $end_time   = '';
        }
        if ($user = users::getItem($top_username ? $top_username : $username,8,false,1,1)) {

            if($user['is_test'] == 1)
            {
                showMsg('不能查询测试账号!');
            }
            $teamDeposit = deposits::getTeamDeposits($user['user_id'], $start_time, $end_time,1);

            $teamWithdraw = withdraws::getTeamWithdraws($user['user_id'], $start_time, $end_time,1);
            $teamBalance = users::getTeamBalances($user['user_id'],1);

            /************** author snow 添加注册IP与最后登录IP 与登录次数 对于下面传了一长串空格参数,表示无言以对.*********/
            $users = users::getItems($user['user_id'], true, 0, array('user_id', 'balance', 'last_time', 'reg_ip', 'last_ip'), -1,-1,'','','','','','','','','','',-1,-1,DEFAULT_PER_PAGE,[],'',-1,1);


            //>> snow 获取用户登录次数;
            $usersLoginCount = $this->getUsersLoginCount($user['user_id'],substr($start_time,0,10), substr($end_time,0,10));

            $totalInfo = ['balance' => 0, 'login_count' => 0, 'deposit' => 0, 'withdraw' => 0, 'amount' => 0, 'rebate' => 0, 'contribute_rebate' => 0, 'prize' => 0, 'pt_game_win' => 0, 'pt_buy_amount' => 0, 'pt_prize' => 0, 'final' => 0];
            $totalInfo['login_count'] =array_sum(array_column($usersLoginCount,'login_count'));

            self::$view->setVar('usersLoginCount', $usersLoginCount);
            unset($sql);

            unset($usersLoginCount);
            /************************ author snow 添加注册IP与最后登录IP与登录次数**********************/

            foreach ($users as $k => $v) {
                $users[$k]['inactive_days'] = $v['last_time'] == '0000-00-00 00:00:00' ? '从未登录' : ceil((time() - strtotime($v['last_time'])) / 86400);
            }

            //团队购买量 各级代理总返点量 各用户自身投注的奖金
            $childReport = projects::getChildReport($user['user_id'], $start_time, $end_time,1);

            //$childReport 第一行是自己的，后面每个都是直属下级团队的
            foreach ($childReport as $k => $v) {
                if(isset($v['total_amount'])) {
                    $totalInfo['amount'] += $v['total_amount'];
                }else{
                    $totalInfo['amount']+=0;
                }
                if(isset($v['total_prize'])) {
                    $totalInfo['prize'] += $v['total_prize'];
                }else{
                    $totalInfo['prize']+=0;
                }
                if(isset($v['total_rebate'])) {
                    $totalInfo['rebate'] += $v['total_rebate'];
                }else{
                    $totalInfo['rebate'] = 0;
                }
                if(isset($v['total_contribute_rebate'])) {
                    $totalInfo['contribute_rebate'] += $v['total_contribute_rebate'];
                }else{
                    $totalInfo['contribute_rebate'] += 0;
                }
                // $totalInfo['pt_prize'] += $v['pt_prize'];    //PT中奖
                // $totalInfo['pt_game_win'] += $v['pt_game_win'];
                // $totalInfo['pt_buy_amount'] += $v['pt_buy_amount'];
            }
            $totalInfo['balance']           = $teamBalance['team_total_balance'];
            $totalInfo['deposit']           = $teamDeposit['team_total_deposit'];
            $totalInfo['deposit_count']     = $teamDeposit['team_total_deposit_count'];
            $totalInfo['promo']             = $teamDeposit['team_total_promo'];
            $totalInfo['withdraw']          = $teamWithdraw['team_total_withdraw'];
            $totalInfo['withdraw_count']    = $teamWithdraw['team_total_withdraw_count'];
            $totalInfo['final']             = $totalInfo['rebate'] + $totalInfo['prize'] - $totalInfo['amount'];
            self::$view->setVar('teamBalance', $teamBalance);
            self::$view->setVar('teamDeposit', $teamDeposit);
            self::$view->setVar('teamWithdraw', $teamWithdraw);
        }

        //得到所有总代
        // $topUsers = users::getUserTree(0,true,0,-1,-1,'',1);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username', 'is_test'],
            'parent_id' => 0,
            'freeze' => 1
        ]);

        $arrTmp=[];
        foreach ($topUsers as $k =>$v)// 除去测试账号
        {
            if($v['is_test'] !=1)
            {
                $arrTmp[$k]=$v;
            }
        }
        //>>author snow 修改不传json 传数组
        self::$view->setVar('json_topUsers', $arrTmp);

        //预设查询值
        self::$view->setVar('top_username', $top_username);
        self::$view->setVar('username', $username);
        self::$view->setVar('childReport', $childReport);
        self::$view->setVar('totalDeposits', $totalDeposits);
        self::$view->setVar('totalWithdraws', $totalWithdraws);
        self::$view->setVar('totalInfo', $totalInfo);
        self::$view->setVar('users', $users);
        self::$view->setVar('user', $user);
        self::$view->setVar('recentBuy', $recentBuy);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加用户', 'url' => url('user', 'addUser'))));
        self::$view->render('game_childreport');
    }

    /**
     * author snow 获取会员一段时间内的登录次数
     * @param $user_id
     * @param string $start
     * @param string $end
     * @return mixed
     */
    private function getUsersLoginCount($user_id, $start = '', $end = '')
    {

        //>>获取登录次数

        $where = '';
        $where .= !empty($start) ?  ' AND ul.date >=' . "'{$start}'" : '';
        $where .= !empty($start) ?  ' AND ul.date <=' . "'{$end}'" : '';

        $sql =<<<SQL
SELECT ul.user_id,COUNT(ul.log_id) AS login_count FROM userlogs as ul,users as u 
WHERE ul.user_id = u.user_id
AND ul.control = 'default'
AND ul.`action` = 'login' {$where}
AND (FIND_IN_SET({$user_id},u.parent_tree) OR u.user_id = {$user_id})
GROUP BY ul.user_id
SQL;
        return $GLOBALS['db']->getAll($sql,[], 'user_id');
    }
    //代理损益报表
    public function saleReport()
    {

        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $parent_id = $this->request->getGet('parent_id', 'intval');
        $is_test = $this->request->getGet('is_test', 'intval', 0);
        $modes = $this->request->getGet('modes', 'floatval');
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        $domain = $this->request->getGet('domain','trim');
        /**************************** snow  ,用来排除dcsite****************************************/
        $childSales = projects::getChildSalesExclude($lottery_id, $parent_id, $is_test, $modes, $start_time, $end_time,true, $domain,1);
        /**************************** snow  ,用来排除dcsite****************************************/
        $totalInfo = array('deposit_amount' => 0, 'amount' => 0, 'rebate' => 0, 'real_amount' => 0, 'prize' => 0, 'final' => 0,'balance'=>0);
        //>>snow 添加判断 ,有可能返回空值.
        if (!empty($childSales)) {
            foreach ($childSales as $k => $v) {
                /******************** snow 添加累积存款 start***************************************************/
                $totalInfo['deposit_amount'] += isset($v['deposit_totalAmount']) ? $v['deposit_totalAmount'] : 0;
                /******************** snow 添加累积存款 end  ***************************************************/
                $totalInfo['amount']    += $v['total_amount'];
                $totalInfo['rebate']    += $v['total_rebate'];
                $totalInfo['prize']     += $v['total_prize'];
                $totalInfo['balance']   += $v['balance'];
            }
        }
        $totalInfo['real_amount'] = $totalInfo['amount'] - $totalInfo['rebate'];
        $totalInfo['final'] = $totalInfo['rebate'] + $totalInfo['prize'] - $totalInfo['amount'];

        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id','cname']);
        self::$view->setVar('json_lotterys', json_encode($lotterys));
        //得到彩种玩法组玩法二级联动
        $methods = methods::getItems(0, 0, -1, 2);
        self::$view->setVar('methods', methods::getItems(0, 0, -1, 0))->setVar('json_methods', json_encode($methods));
        //得到所有总代
//         $topUsers = users::getUserTree(0,true,0,-1,-1,'',1);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
            'is_test' => 0,//>>snow 过滤测试账号
            'freeze' => 1
        ]);
        self::$view->setVar('json_topUsers', $topUsers);

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('parent_id', $parent_id);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('modes', $modes);
        self::$view->setVar('start_time', $start_time);
        self::$view->setVar('end_time', $end_time);
        self::$view->setVar('domain', $domain);
        self::$view->setVar('childSales', $childSales);
        self::$view->setVar('totalInfo', $totalInfo);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_salereport');
    }

    //团队报表 teamReport
    public function teamReport()
    {
        $isGroupBy = $this->request->getGet('isGroupBy', 'intval', 1);
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $startDateTime = $startDate . ' 00:00:00';
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
        $endDateTime = $endDate . ' 23:59:59';
        $top_id = $this->request->getGet('top_id', 'intval', '-1');
        /******************* author snow 添加 添加分页尺寸 **************************/
        $pageSize = $this->request->getGet('pageSize', 'intval', 20); //>>添加分页尺寸 默认20
        //>>处理分页尺寸
        if ($pageSize < 50) {
            self::$teamReportPerPage = 20;
        } elseif ($pageSize >= 50 && $pageSize < 100) {
            self::$teamReportPerPage = 50;
        } else {
            self::$teamReportPerPage = 100;
        }
        /******************* author snow 添加 添加分页尺寸 **************************/

        $game_type = $this->request->getGet('game_type', 'intval', '1');
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        /******************* author snow 添加 是否查询实时报表 标示**************************/
        $isLive = $this->request->getGet('isLive', 'trim', 'false');

        //>>验证时间格式 ,以前没有做验证

        if ( !validateDateFormat($startDate,'Y-m-d') || !validateDateFormat($endDate, 'Y-m-d') || $startDate > $endDate) {
            //>>时间验证未通过
            showMsg('时间格式错误,或者开始时间大于了结束时间');
        }
        /******************* author snow 添加 是否查询实时报表 标示**************************/
        $totalInfo = [
            'last_team_balance' => 0,
            'team_balance'      => 0,
            'deposit_amount'    => 0,
            'promo_amount'      => 0,
            'withdraw_amount'   => 0,
            'real_win'          => 0,
            'buy_amount'        => 0,
            'prize_amount'      => 0,
            'rebate_amount'     => 0,
            'game_win'          => 0,
            'promos'            => 0,
            //>> snow 添加投注日返水
            'back_water'        => 0,
            'diff'              => 0,
            'play_user_num'     => 0,
            'prize_user_num'    => 0,
            'reg_num'           => 0,
            'first_deposit_num' => 0,
            'first_deposit_amount' => 0,
        ];

        //得到所有总代
        // $topUsers = users::getUserTree(0,true,0,-1,-1,'',1);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
            'freeze' => 1
        ]);
        //>>author snow 修改不传入json 传数组
        self::$view->setVar('topUsers', $topUsers)->setVar('json_topUsers', $topUsers);

        //>>如果不是,走原来的流程
        switch ($game_type) {
            case 1:

                if ($isGroupBy == 1) {
                    $options = [
                        'user_id'       => $top_id,  //>>总代id
                        'startDate'     => $startDate,  //>>开始日期
                        'endDate'       => $endDate,  //>>结束时期

                    ];
                    $teamReportNumber       = teamReports::snow_getUserStatsNumber($options);
                    //>>添加分页相关参数到数组里面
                    $options['start']       = getStartOffset($curPage,$teamReportNumber,self::$teamReportPerPage);
                    $options['pageSize']    = self::$teamReportPerPage;
                    $teamReport = teamReports::snow_getUserStats($options);
                    foreach ($teamReport as $k => $v) {
                        $totalInfo['team_balance']          += $v['team_balance'];
                        $totalInfo['deposit_amount']        += $v['deposit_amount'];
                        $totalInfo['promo_amount']          += $v['promo_amount'];//>>author snow 添加优惠彩金.
                        $totalInfo['withdraw_amount']       += $v['withdraw_amount'];
                        $totalInfo['real_win']              += $v['real_win'];
                        $totalInfo['buy_amount']            += $v['buy_amount'];
                        $totalInfo['prize_amount']          += $v['prize_amount'];
                        $totalInfo['rebate_amount']         += $v['rebate_amount'];
                        $v['game_win']                      = $v['buy_amount'] - $v['prize_amount'];//>>手动计算损益.
                        $teamReport[$k]['game_win']         = $v['game_win'];
                        $totalInfo['game_win']              += $v['game_win'];
                        $totalInfo['promos']                += $v['promos'];
                        //>> snow 添加投注日返水
                        $totalInfo['back_water']            += $v['back_water'];
                        $totalInfo['diff']                  += $v['diff'];
                        $totalInfo['reg_num']               += $v['reg_num'];
                        $totalInfo['first_deposit_num']     += $v['first_deposit_num'];
                        $totalInfo['first_deposit_amount']  += $v['first_deposit_amount'];
                    }
                }
                else {

                    $teamReportNumber = teamReports::snow_getItemsNumber($top_id, $startDate, $endDate);
                    /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
                    //>>判断输入的页码是否超过最大值.
                    $startPos = getStartOffset($curPage, $teamReportNumber, self::$teamReportPerPage);
                    /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
                    $teamReport = teamReports::snow_getItems($top_id, $startDate, $endDate, $startPos, self::$teamReportPerPage);
                    foreach ($teamReport as $k => $v) {
                        $totalInfo['last_team_balance']         += $v['last_team_balance'];
                        $totalInfo['team_balance']              += $v['team_balance'];
                        $totalInfo['deposit_amount']            += $v['deposit_amount'];
                        $totalInfo['promo_amount']              += $v['promo_amount'];//>>author snow 添加优惠彩金.
                        $totalInfo['withdraw_amount']           += $v['withdraw_amount'];
                        $totalInfo['real_win']                  += $v['real_win'];
                        $totalInfo['buy_amount']                += $v['buy_amount'];
                        $totalInfo['prize_amount']              += $v['prize_amount'];
                        $totalInfo['rebate_amount']             += $v['rebate_amount'];
                        $v['game_win']                          = $v['buy_amount'] - $v['prize_amount'];//>>手动计算损益.
                        $teamReport[$k]['game_win']             = $v['game_win'];
                        $totalInfo['game_win']                  += $v['game_win'];
                        $totalInfo['promos']                    += $v['promos'];
                        //>> snow 添加投注日返水
                        $totalInfo['back_water']                += $v['back_water'];
                        $totalInfo['diff']                      += $v['diff'];
                        $totalInfo['play_user_num']             += $v['play_user_num'];
                        $totalInfo['prize_user_num']            += $v['prize_user_num'];
                        $totalInfo['reg_num']                   += $v['reg_num'];
                        $totalInfo['first_deposit_num']         += $v['first_deposit_num'];
                        $totalInfo['first_deposit_amount']      += $v['first_deposit_amount'];
                    }
                }
                break;

            case 4:
                //>>author snow 下面现行代码,使用,故注释掉
//                $topPromos = orders::getTopPromos(701);
                $egame_logs = egameMwSiteUsergamelog::getTopLogs($startDateTime, $endDateTime, 0,  $top_id, XY_PREFIX);
                if ($isGroupBy == 1) {
                    $options = [
                        'user_id'       => $top_id,  //>>总代id
                        'startDate'     => $startDate,  //>>开始日期
                        'endDate'       => $endDate,  //>>结束时期

                    ];
                    $teamReportNumber       = teamReports::snow_getUserStatsNumber($options);
                    //>>添加分页相关参数到数组里面
                    $options['start']       = getStartOffset($curPage,$teamReportNumber,self::$teamReportPerPage);
                    $options['pageSize']    = self::$teamReportPerPage;
                    $teamReport = teamReports::snow_getUserStats($options);

                    foreach ($teamReport as $k => &$v) {
                        $v['rebate_amount'] = 0;

                        if (isset($egame_logs[$v['user_id']])) {
                            $v['buy_amount'] = $egame_logs[$v['user_id']]['play_money'];
                            $v['prize_amount'] = $egame_logs[$v['user_id']]['win_money'];
                            $v['game_win'] = ($egame_logs[$v['user_id']]['win_money'] + $v['rebate_amount'] - $v['buy_amount']);
                        } else {
                            $v['buy_amount'] = 0;
                            $v['prize_amount'] = 0;
                            $v['game_win'] = 0;
                        }

                        $v['promos'] = 0;

                        $totalInfo['team_balance']          += $v['team_balance'];
                        $totalInfo['deposit_amount']        += $v['deposit_amount'];
                        $totalInfo['promo_amount']          += $v['promo_amount'];//>>author snow 添加优惠彩金.
                        $totalInfo['withdraw_amount']       += $v['withdraw_amount'];
                        $totalInfo['real_win']              += $v['real_win'];
                        $totalInfo['buy_amount']            += $v['buy_amount'];
                        $totalInfo['prize_amount']          += $v['prize_amount'];
                        $totalInfo['rebate_amount']         += $v['rebate_amount'];
                        $v['game_win']                      = $v['buy_amount'] - $v['prize_amount'];//>>手动计算损益.
                        $teamReport[$k]['game_win']         = $v['game_win'];
                        $totalInfo['game_win']              += $v['game_win'];
                        $totalInfo['promos']                += $v['promos'];
                        //>> snow 添加投注日返水
                        $totalInfo['back_water']            += $v['back_water'];
                        $totalInfo['diff']                  += $v['diff'];
                        $totalInfo['reg_num']               += $v['reg_num'];
                        $totalInfo['first_deposit_num']     += $v['first_deposit_num'];
                        $totalInfo['first_deposit_amount']  += $v['first_deposit_amount'];
                    }
                }
                else {
                    $teamReportNumber = teamReports::snow_getItemsNumber($top_id, $startDate, $endDate);
                    //>>判断输入的页码是否超过最大值.
                    $startPos = getStartOffset($curPage, $teamReportNumber, self::$teamReportPerPage);
                    $teamReport = teamReports::snow_getItems($top_id, $startDate, $endDate, $startPos, self::$teamReportPerPage);

                    foreach ($teamReport as $k => &$v) {
                        $v['rebate_amount'] = 0;
//
                        if (isset($egame_logs[$v['user_id']])) {
                            $v['buy_amount'] = $egame_logs[$v['user_id']]['play_money'];
                            $v['prize_amount'] = $egame_logs[$v['user_id']]['win_money'];
                            $v['game_win'] = ($egame_logs[$v['user_id']]['win_money'] + $v['rebate_amount'] - $v['buy_amount']);
                        } else {
                            $v['buy_amount'] = 0;
                            $v['prize_amount'] = 0;
                            $v['game_win'] = 0;
                        }

                        $v['promos'] = 0;

                        $totalInfo['last_team_balance']         += $v['last_team_balance'];
                        $totalInfo['team_balance']              += $v['team_balance'];
                        $totalInfo['deposit_amount']            += $v['deposit_amount'];
                        $totalInfo['promo_amount']              += $v['promo_amount'];//>>author snow 添加优惠彩金.
                        $totalInfo['withdraw_amount']           += $v['withdraw_amount'];
                        $totalInfo['real_win']                  += $v['real_win'];
                        $totalInfo['buy_amount']                += $v['buy_amount'];
                        $totalInfo['prize_amount']              += $v['prize_amount'];
                        $totalInfo['rebate_amount']             += $v['rebate_amount'];
                        $v['game_win']                          = $v['buy_amount'] - $v['prize_amount'];//>>手动计算损益.
                        $teamReport[$k]['game_win']             = $v['game_win'];
                        $totalInfo['game_win']                  += $v['game_win'];
                        $totalInfo['promos']                    += $v['promos'];
                        //>> snow 添加投注日返水
                        $totalInfo['back_water']                += $v['back_water'];
                        $totalInfo['diff']                      += $v['diff'];
                        $totalInfo['play_user_num']             += $v['play_user_num'];
                        $totalInfo['prize_user_num']            += $v['prize_user_num'];
                        $totalInfo['reg_num']                   += $v['reg_num'];
                        $totalInfo['first_deposit_num']         += $v['first_deposit_num'];
                        $totalInfo['first_deposit_amount']      += $v['first_deposit_amount'];
                    }
                }

                break;

            default:
                $egame_logs = egameMwSiteUsergamelog::getTopLogs($startDateTime, $endDateTime, 0,  $top_id, XY_PREFIX);
                //>>author snow 下面现行代码,使用,故注释掉
//                $topsGm = array();
//                $topPromos = orders::getTopPromos(701);

                if ($isGroupBy == 1) {

                    $options = [
                        'user_id'       => $top_id,  //>>总代id
                        'startDate'     => $startDate,  //>>开始日期
                        'endDate'       => $endDate,  //>>结束时期

                    ];
                    $teamReportNumber       = teamReports::snow_getUserStatsNumber($options);
                    //>>添加分页相关参数到数组里面
                    $options['start']       = getStartOffset($curPage,$teamReportNumber,self::$teamReportPerPage);
                    $options['pageSize']    = self::$teamReportPerPage;
                    $teamReport = teamReports::snow_getUserStats($options);
                    foreach ($teamReport as $k => &$v) {
                        if (isset($egame_logs[$v['user_id']])) {
                            $v['buy_amount'] += $egame_logs[$v['user_id']]['play_money'];
                            $v['prize_amount'] += $egame_logs[$v['user_id']]['win_money'];
                            $v['game_win'] += ($egame_logs[$v['user_id']]['win_money'] + $v['rebate_amount'] - $v['buy_amount']);
                        }

                        $totalInfo['team_balance'] += $v['team_balance'];
                        $totalInfo['deposit_amount'] += $v['deposit_amount'];
                        $totalInfo['withdraw_amount'] += $v['withdraw_amount'];
                        $totalInfo['real_win'] += $v['real_win'];
                        $totalInfo['buy_amount'] += $v['buy_amount'];
                        $totalInfo['prize_amount'] += $v['prize_amount'];
                        $totalInfo['rebate_amount'] += $v['rebate_amount'];
                        $v['game_win'] = $v['buy_amount'] - $v['prize_amount'];//>>手动计算损益.
                        $teamReport[$k]['game_win'] = $v['game_win'];
                        $totalInfo['game_win'] += $v['game_win'];
                        $totalInfo['promos'] += $v['promos'];
                        //>> snow 添加投注日返水
                        $totalInfo['back_water'] += $v['back_water'];
                        $totalInfo['diff'] += $v['diff'];
                        $totalInfo['reg_num'] += $v['reg_num'];
                        $totalInfo['first_deposit_num'] += $v['first_deposit_num'];
                        $totalInfo['first_deposit_amount'] += $v['first_deposit_amount'];
                    }
                }
                else {
                    $teamReportNumber = teamReports::snow_getItemsNumber($top_id, $startDate, $endDate);
                    //>>判断输入的页码是否超过最大值.
                    $startPos = getStartOffset($curPage, $teamReportNumber, self::$teamReportPerPage);
                    $teamReport = teamReports::snow_getItems($top_id, $startDate, $endDate, $startPos, self::$teamReportPerPage);
                    foreach ($teamReport as $k => &$v) {
                        if (isset($egame_logs[$v['user_id']])) {
                            $v['buy_amount'] += $egame_logs[$v['user_id']]['play_money'];
                            $v['prize_amount'] += $egame_logs[$v['user_id']]['win_money'];
                            $v['game_win'] += ($egame_logs[$v['user_id']]['win_money'] + $v['rebate_amount'] - $v['buy_amount']);
                        }

                        $totalInfo['last_team_balance'] += $v['last_team_balance'];
                        $totalInfo['team_balance'] += $v['team_balance'];
                        $totalInfo['deposit_amount'] += $v['deposit_amount'];
                        $totalInfo['withdraw_amount'] += $v['withdraw_amount'];
                        $totalInfo['real_win'] += $v['real_win'];
                        $totalInfo['buy_amount'] += $v['buy_amount'];
                        $totalInfo['prize_amount'] += $v['prize_amount'];
                        $totalInfo['rebate_amount'] += $v['rebate_amount'];
                        $v['game_win'] = $v['buy_amount'] - $v['prize_amount'];//>>手动计算损益.
                        $teamReport[$k]['game_win'] = $v['game_win'];
                        $totalInfo['game_win'] += $v['game_win'];
                        $totalInfo['promos'] += $v['promos'];
                        //>> snow 添加投注日返水
                        $totalInfo['back_water'] += $v['back_water'];
                        $totalInfo['diff'] += $v['diff'];
                        $totalInfo['play_user_num'] += $v['play_user_num'];
                        $totalInfo['prize_user_num'] += $v['prize_user_num'];
                        $totalInfo['reg_num'] += $v['reg_num'];
                        $totalInfo['first_deposit_num'] += $v['first_deposit_num'];
                        $totalInfo['first_deposit_amount'] += $v['first_deposit_amount'];
                    }
                }
                break;
        }

        //预设查询框
        if (config::getConfig('egame_agent_report', '0') == 1) {
            self::$view->setVar('gameTypes', $GLOBALS['cfg']['gameTypes']);
        } else {
            self::$view->setVar('gameTypes', null);
        }

        /******************* author snow 添加 是否查询实时报表 标示**************************/
        self::$view->setVar('isLive', $isLive);
        //>>添加分页尺寸
        self::$view->setVar('pageSize', self::$teamReportPerPage);
        /******************* author snow 添加 是否查询实时报表 标示**************************/
        self::$view->setVar('gameType', $game_type);
        self::$view->setVar('isGroupBy', $isGroupBy);
        self::$view->setVar('top_id', $top_id);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('teamReport', $teamReport);
        self::$view->setVar('totalInfo', $totalInfo);
        self::$view->setVar('pageList', getPageList($teamReportNumber, self::$teamReportPerPage));
        self::$view->render('game_teamreport');
    }

    /**
     * author snow  2017-09-09
     * @param $startDate  string 开始日期
     * @param $endDate    string  结束日期
     * @return bool      返回 是两个日期之差是否在一个月之内
     */
    private function _toDetermineOfmonth($startDate, $endDate)
    {
        //>>结束日期,不能大于当前日期.
        $endDate    = $endDate > date('Y-m-d') ? date('Y-m-d') : $endDate;
        if($startDate > $endDate){
            //>>如果开始日期大于结束日期  交换两个日期的值
            list($startDate, $endDate) = [$endDate, $startDate];
        }
        //>>对时间格式进行验证
        $preg = '/^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$/';

        if(!preg_match($preg,$startDate) || !preg_match($preg,$endDate)){
            showMsg("开始时间或者结束时间  格式不正确.正确格式 为2016-01-01");
            exit;
        }
        $time               = strtotime($endDate);  //>>把结束日期转换成时间戳
        $last_month_time    = mktime(date("G", $time), date("i", $time),date("s", $time), date("n", $time), 0, date("Y", $time));
        $last_month_t       =  date("t", $last_month_time);
        $preMonthDay        = date(date("Y-m", $last_month_time) . "-d", $time);
        if ($last_month_t < date("j", $time)) {
            $preMonthDay    = date("Y-m-t", $last_month_time);
        }
        //>>返回实际查询的开始时间
        if($startDate < $preMonthDay){
            $result = ['startDate' => $preMonthDay, 'endDate' => $endDate, 'flag' => false];
        }else{
            $result = ['startDate' => $startDate,  'endDate' => $endDate, 'flag' => true];
        }
        return $result;

    }
    //总账报表 teamReport
    public function totalReport()
    {
        $startDate  = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $endDate    = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
        /**************** author snow  总账报表修改 start*********************************************************/
        $tradeType  = $this->request->getGet('tradeType', 'intval', 0); //>>判断是否充值详情
        $withdraw   = $this->request->getGet('withdraw', 'intval', 0);   //>>判断是否提款详情
        $flag       = $this->request->getGet('flag', 'intval', 0);           //>>判断是否其它费用详情
        $sday       = $this->request->getGet('day', 'trim', '');
        //>>调用 方法获取实际查询的开始日期.
        $dateResult = $this->_toDetermineOfmonth($startDate,$endDate);
        $startDate  = $dateResult['startDate'];
        $endDate    = $dateResult['endDate'];
        //>>进行明细处理
        if ($tradeType > 0) {
            //>>充值分类明线
            $this->_getDepositsDayTail($tradeType,$sday);
        }

        if ($withdraw > 0) {
            //>>提款明细
            $this->_getWithdrawDayTail($sday);
        }

        if ($flag > 0) {
            //>>其它费用明细
            $this->_getOrderDayTail($flag, $sday);
        }

        $tmp = createGameTotalReportForMoreDay($startDate, $endDate);
        /**************************** snow  ,用来排除dcsite****************************************/
        $deposits_options = [
            'is_test'           => 0,   //>>是否测试账号
            'status'            => 8,   //>>充值记录状态
            'startDate'         => $startDate . ' 00:00:00',    //>>起始时间
            'endDate'           => $endDate . ' 23:59:59',      //>>结束时间
            'freeze'            => 1,   //>>是否查询冻结账号
        ];
        $deposits   = deposits::getItemsExcludeStatistics($deposits_options);
        $depositsNum   = deposits::getItemsNum($deposits_options);
        //>>提款参数
        $options = [
            'is_test'           => 0,   //>>默认不显示测试账号
            'status'            => 8,   //>>状态
            'pay_bank_id'       => 0,   //>>支付卡bank_id
            'pay_card_id'       => 0,   //>>支付卡id
            'bank_id'           => 0,   //>>
            'start_date'        => $startDate . ' 00:00:00',    //>>起始时间
            'end_date'          => $endDate . ' 23:59:59',      //>>结束时间
            'start_amount'      => 1,   //>>最小金额
            'freeze'            => 1,   //>>是否查询冻结账号
        ];
        $withdraws  = withdraws::getItemsExcludestatistics($options);
        //>>账变参数
        $orders_options = [
            'type'       =>[102, 503],
            'start_time' => $startDate . ' 00:00:00',
            'end_time'   => $endDate . ' 23:59:59',
            'freeze'     => 1,
        ];
        $orders     = orders::getItemsExcludeStatistics($orders_options);
        //>>snow 获取总投注,总中奖,总盈亏
        $packages   = projects::getItemsExclude($startDate . ' 00:00:00', $endDate . ' 23:59:59');
        /**************************** snow  ,用来排除dcsite****************************************/
        //>>snow 添加总投注 ,总中奖,总盈亏 默认值为0
        $pageTotal  = createGameTotalReportForDay();

//        $i=0;

        $depositsNum = array_column($depositsNum,'num','belongDate');
        //充值数据统计
        foreach($deposits as $v){
            //获取每天每个渠道充值
            if (isset($tmp[$v['belongDate']])) {
                $tmp[$v['belongDate']][$v['trade_type']] = $v;
                $pageTotal[$v['trade_type']]['amount'] += $v['amount'];         //>>时间段内充值按类型的合计金额
                $pageTotal[$v['trade_type']]['num']    += $v['num'];            //>>时间段内充值按类型的合计人数



                if(isset($depositsNum[$v['belongDate']]))
                {
                    $tmp[$v['belongDate']]['deposit']['num']    = $depositsNum[$v['belongDate']];

                }
                //>>总账不统计优惠彩金
                if (in_array($v['trade_type'],[1,2,3,4,5])) {

//                    $tmp[$v['belongDate']]['deposit']['num']    += $v['num'];    //>>每天充值的合计人数
                     $tmp[$v['belongDate']]['deposit']['amount'] += $v['amount']; //>>每天充值的合计金额
                    //$pageTotal['deposit']['num']                += $tmp[$v['belongDate']]['deposit']['num'];    //>>时间段内充值的合计人数
                    $pageTotal['deposit']['amount']             += $v['amount']; //>>时间段内充值的合计金额
                }
            }
//            ++$i;
        }
        //人数去重

            $pageTotal['deposit']['num']     = array_sum($depositsNum);

        //>>提款数据
        foreach ($withdraws as $val) {
           if (isset($tmp[$val['belongDate']])) {
               $tmp[$val['belongDate']]['withdraw'] = $val;                     //>>每天的提款数据
               $pageTotal['withdraw']['amount'] += $val['amount'];              //>>时间段内的提款合计金额
               $pageTotal['withdraw']['num']    += $val['num'];                 //>>时间段内的提款合计人数
           }

        }
        //>>其它费用
        foreach ($orders as $key => $order) {

            if (is_null($order['belongDate'])) {
                continue;
            }
           if (isset($tmp[$order['belongDate']])) {
               $tmp[$order['belongDate']][$order['type']] = $order;             //>>每天的其它费用数据
               $pageTotal[$order['type']]['amount'] += $order['amount'];        //>>每天的其它数据按类型合计金额
               $pageTotal[$order['type']]['num']    += $order['num'];           //>>每天的其它数据按类型合计人数
            }
        }

        /******************* author snow 添加四个字段,从日度报表中了数据***********************************/

        $model = new common\model\baseModel('report_day');

        //>>拿到数据
        $dayReportData = $model
            ->field(['date', 'top_num', 'new_top_num', 'new_user_num', 'first_deposit_num'])
            ->where( "`date` BETWEEN '{$startDate}' AND '{$endDate}'")
            ->index('date')
            ->select();
        if ($endDate == date('Y-m-d')) {
            //>>如果包含今天 ,还要添加今天 的数据.因为report_day 表中没有当天的数据
            $dayReportData[$endDate] = $this->_getFourData($endDate);
        }
        /******************* author snow 添加四个字段,从日度报表中了数据***********************************/
        //>>循环添加总投注,总中奖,总损益 总代数量,新增总代数,新增用户数,首存人数
        foreach ($tmp as $k => $v)
        {
            if (isset($packages[$k]) && is_array($packages[$k]))
            {
                $tmp[$k]['belong_date']         = $packages[$k]['belong_date'];
                $tmp[$k]['sum_amount']          = $packages[$k]['sum_amount'];      //>>每天的总投注
                $tmp[$k]['sum_prize']           = $packages[$k]['sum_prize'];       //>>每天的总中奖
                $tmp[$k]['sum_win']             = $packages[$k]['sum_win'];         //>>每天的总损益
                //>>计算总开始时间到结束时间的总值
                $pageTotal['sum_amount']            += $packages[$k]['sum_amount']; //>>时间段内的总投注
                $pageTotal['sum_prize']             += $packages[$k]['sum_prize'];  //>>时间段内的总中奖
                $pageTotal['sum_win']               += $packages[$k]['sum_win'];    //>>时间段内的总损益

            }

            if (isset($dayReportData[$k]) && is_array($dayReportData[$k]) && !empty($dayReportData[$k]))
            {
                $tmp[$k]['top_num']             = $dayReportData[$k]['top_num'];            //>>每天的总代数
                $tmp[$k]['new_top_num']         = $dayReportData[$k]['new_top_num'];        //>>每天新增的总代数
                $tmp[$k]['new_user_num']        = $dayReportData[$k]['new_user_num'];       //>>每天新增的用户数
                $tmp[$k]['first_deposit_num']   = $dayReportData[$k]['first_deposit_num'];  //>>每天的首充值数量
                //>>合计数据
                $pageTotal['new_top_num']           += $dayReportData[$k]['new_top_num'];       //>>时间段内新增总代数
                $pageTotal['new_user_num']          += $dayReportData[$k]['new_user_num'];      //>>时间段内新增用户数
                $pageTotal['first_deposit_num']     += $dayReportData[$k]['first_deposit_num']; //>>时间段内每天首充和
            }

        }
        if ($this->request->getGet('export_excel','trim','false') == 'totalReport')
        {
            //>>如果有这个值进行数据导出
            $this->_exportTotalReportData($startDate, $endDate, $tmp, $pageTotal);
        }
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('flag', json_encode($dateResult['flag'])); //>>添加是提示方案
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('dayData', $tmp);
        self::$view->setVar('pageTotal', $pageTotal);
        self::$view->render('game_totalreport');
    }

    /**
     * author snow 生成开始时间到结束时间的数组
     * @param $startTs
     * @param $endTs
     * @return array
     */
    private function _getDateDay($startTs, $endTs)
    {
        $dayData = [];
        while ($startTs <= $endTs) {
            $day = date('Y-m-d', $startTs);

            $dayData[$day] = [
                1 => ['amount'=>0,'num'=>[]],
                2 => ['amount'=>0,'num'=>[]],
                3 => ['amount'=>0,'num'=>[]],
                4 => ['amount'=>0,'num'=>[]],
                5 => ['amount'=>0,'num'=>[]],
                6 => ['amount'=>0,'num'=>[]],
                102 => ['amount'=>0,'num'=>[]],
                503 => ['amount'=>0,'num'=>[]],
                'withdraw' => ['amount'=>0,'num'=>[]],
            ];
            $startTs += 86400;
        }

        return $dayData;
    }


    /**
     * author snow 获取总账报表,当天的四个数据
     * @param null $endDate  查询的时间
     * @return array
     */
    private function _getFourData($endDate = null)
    {
        if (is_null($endDate)) {
            $endDate = date('Y-m-d');
        }

        $startTime  = $endDate . ' 00:00:00';
        $endTime    = $endDate . ' 23:59:59';
        $top_num    = users::getItemsNumber(0, true, 0, 8, 0);
        // 新增总代数
        $new_top_num    = users::getItemsNumber(0, true, 0, 8, 0, $startTime, $endTime);
        // 新增用户数
        /**************** snow 修改排除desite 总代名下数据 start**********************************/
        $new_user_num   = users::getItemsNumberExclude(-1, true, 0, 8, 0, $startTime, $endTime);

        // 首充
        $first_deposit  = deposits::getFirstDepositsExclude('', 0, 0, $startTime, $endTime);
        /**************** snow 修改排除desite 总代名下数据 end  **********************************/
        $first_deposit_num = $first_deposit['count'];
        return [
            'top_num'               => $top_num,
            'new_top_num'           => $new_top_num ,
            'new_user_num'          => $new_user_num ,
            'first_deposit_num'     => $first_deposit_num ,
        ];
    }
    /**
     * author snow 导出数据到excel 表格
     * @param $startDate  string 导出数据
     * @param $endDate
     * @param $dayData
     * @param $pageTotal
     */
    private function _exportTotalReportData($startDate, $endDate, $dayData, $pageTotal)
    {

        //>>想了想,还是写csv文件
        //>>头部标题
        $headList=[
            '日期',
            '总代数量',
            '新增总代数',
            '新增用户数',
            '首存人数',
            '银行卡入款',
            '银行卡入款人数',
            '线上支付',
            '线上支付人数',
            '扫码支付',
            '扫码支付人数',
            '微信收款',
            '微信收款人数',
            '支付宝收款',
            '支付宝收款人数',
            '存款人数',
            '总存款',
            '会员提款',
            '会员提款人数',
            '项目盈亏',
            '优惠彩金',
            '优惠彩金人数',
            '充值优惠',
            '充值优惠人数',
            '投注日返水',
            '投注日返水人数',
            '投注量',
            '中奖量',
            '总损益'
        ];

        //>>处理数据中多余的数据
        $excelData = [];
        //>>组合成导出数据需要的数据格式
        foreach ($dayData as $key => $val) {

            $excelData[] = [
                $key,
                $val['top_num'],
                $val['new_top_num'],
                $val['new_user_num'],
                $val['first_deposit_num'],
                $val[1]['amount'],
                $val[1]['num'],
                $val[2]['amount'],
                $val[2]['num'],
                $val[3]['amount'],
                $val[3]['num'],
                $val[4]['amount'],
                $val[4]['num'],
                $val[5]['amount'],
                $val[5]['num'],
                $val['deposit']['num'],
                $val['deposit']['amount'],
                $val['withdraw']['amount'],
                $val['withdraw']['num'],
                $val['deposit']['amount'] - $val['withdraw']['amount'],
                $val[6]['amount'],
                $val[6]['num'],
                $val[102]['amount'],
                $val[102]['num'],
                $val[503]['amount'],
                $val[503]['num'],
                $val['sum_amount'],
                $val['sum_prize'],
                $val['sum_win'],
            ];

        }



        $excelData[] = [
            '统计',
            '本页小计',
            $pageTotal['new_top_num'],
            $pageTotal['new_user_num'],
            $pageTotal['first_deposit_num'],
            $pageTotal[1]['amount'],
            $pageTotal['new_top_num']['num'],
            $pageTotal[2]['amount'],
            $pageTotal[2]['num'],
            $pageTotal[3]['amount'],
            $pageTotal[3]['num'],
            $pageTotal[4]['amount'],
            $pageTotal[4]['num'],
            $pageTotal[5]['amount'],
            $pageTotal[5]['num'],
            $pageTotal['deposit']['num'],
            $pageTotal['deposit']['amount'],
            $pageTotal['withdraw']['amount'],
            $pageTotal['withdraw']['num'],
            $pageTotal['deposit']['amount'] - $pageTotal['withdraw']['amount'],
            $pageTotal[6]['amount'],
            $pageTotal[6]['num'],
            $pageTotal[102]['amount'],
            $pageTotal[102]['num'],
            $pageTotal[503]['amount'],
            $pageTotal[503]['num'],
            $pageTotal['sum_amount'],
            $pageTotal['sum_prize'],
            $pageTotal['sum_win'],
        ];
        $fileName = '总账报表' . $startDate . '至' .$endDate;
        $result = export_data_to_csv($headList, $excelData, $fileName);

        die(json_encode($result));

    }

    /**
     * 获取按彩种统计的投注，中奖，盈亏
     */
    public function totalReportForLottery()
    {
        //>>获取参数
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
        //>>调用 方法获取实际查询的开始日期.
        $dateResult = $this->_toDetermineOfmonth($startDate,$endDate);
        $startDate  = $dateResult['startDate'];

        $dayData = projects::getItemsForLotteryExclude($startDate . ' 00:00:00', $endDate . ' 23:59:59');
        $sum_amount = 0;
        $sum_prize = 0;
        $sum_win = 0;
        foreach ($dayData as $key => $val){
            //>>获取总计
            $sum_amount += $val['sum_amount'];
            $sum_prize  += $val['sum_prize'];
            $sum_win    += $val['sum_win'];
        }
        self::$view->setVar('dayData', $dayData);
        self::$view->setVar('total', ['total_sum_amount' => $sum_amount, 'total_sum_prize' => $sum_prize, 'total_sum_win' => $sum_win, ]);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->render('game_totalReport_forLottery');
    }
    //实际盈亏
    public function realWin()
    {
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d", strtotime('-1 days')));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d"));
        $username = $this->request->getGet('username', 'trim');
        if ($user = users::getItem($username)) {
            $user_id = $user['user_id'];
            self::$view->setVar('username', $username);
            $submit = $this->request->getGet('submit', 'trim', '查询');
            if (strtotime($startDate) < strtotime('-65 days')) {
                $startDate = date("Y-m-d", strtotime('-65 days'));
            }
            if (strtotime($endDate) < strtotime('-65 days')) {
                $endDate = date('Y-m-d', strtotime('-65 days'));
            }
            if (strtotime($startDate) > strtotime($endDate)) {
                $startDate = $endDate;
            }

            $totalInfo = array(
                'last_team_balance' => 0,
                'team_balance' => 0,
                'deposit_amount' => 0,
                'withdraw_amount' => 0,
                'real_win' => 0,
                'buy_amount' => 0,
                'prize_amount' => 0,
                'rebate_amount' => 0,
                'game_win' => 0,
                'promos' => 0,
                'diff' => 0,
                'play_user_num' => 0,
                'prize_user_num' => 0,
                'reg_num' => 0,
                'first_deposit_num' => 0,
                'first_deposit_amount' => 0,
            );
            if (strtotime($startDate) < strtotime('2015-01-01')) {
                $startDate = '2015-01-01';
            }
            if (strtotime($endDate) < strtotime('2015-01-01')) {
                $endDate = '2015-01-01';
            }
//            if ($submit == '查询') {
//                $teamReport = teamReports::getItems($user_id, $startDate, $endDate);
//            }
//            elseif ($submit == '查询下级') {
//                $teamReport = teamReports::getChildren($user_id, $startDate, $endDate);
//                self::$view->setVar('showUsername', 1);
//            }

            $teamReport = teamReports::getChildren($user_id, $startDate, $endDate, true);
            self::$view->setVar('showUsername', 1);
            //$teamReportNumber = teamReports::getItemsNumber($top_id, $startDate, $endDate);
            foreach ($teamReport as $k => $v) {
                if ($k == $user_id) {
                    $teamReport[$k]['username'].='(团队)';
                    $totalInfo = $teamReport[$k];
                }
//                $totalInfo['last_team_balance'] += $v['last_team_balance'];
//                $totalInfo['team_balance'] += $v['team_balance'];
//                $totalInfo['deposit_amount'] += $v['deposit_amount'];
//                $totalInfo['withdraw_amount'] += $v['withdraw_amount'];
//                $totalInfo['real_win'] += $v['real_win'];
//                $totalInfo['buy_amount'] += $v['buy_amount'];
//                $totalInfo['prize_amount'] += $v['prize_amount'];
//                $totalInfo['rebate_amount'] += $v['rebate_amount'];
//                $totalInfo['game_win'] += $v['game_win'];
//                $totalInfo['promos'] += $v['promos'];
//                $totalInfo['diff'] += $v['diff'];
//                $totalInfo['reg_num'] += $v['reg_num'];
//                $totalInfo['first_deposit_num'] += $v['first_deposit_num'];
//                $totalInfo['first_deposit_amount'] += $v['first_deposit_amount'];
            }
            self::$view->setVar('teamReport', $teamReport);
            self::$view->setVar('totalInfo', $totalInfo);
        }
        //预设查询框
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->render('game_realwin');
    }

    //单期盈亏报表
    public function singleSaleReport()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $is_test = $this->request->getGet('is_test', 'intval', 0);
        $modes = $this->request->getGet('modes', 'floatval');
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d"));
        if (!$lottery_id) {
            $issueSales = $issues = $totalInfo = array();
        }
        else {
            /**************************** snow  ,用来排除dcsite****************************************/
            $issueSales = projects::getIssueSalesExclude($lottery_id, $is_test, $modes, "$startDate 00:00:00", "$startDate 23:59:59");
            /**************************** snow  ,用来排除dcsite****************************************/
            $issues = issues::getItemsByIssue($lottery_id, array_keys($issueSales));
            $totalInfo = array('amount' => 0, 'rebate' => 0, 'real_amount' => 0, 'prize' => 0, 'final' => 0,);
            foreach ($issueSales as $k => $v) {
                $totalInfo['amount'] += $v['total_amount'];
                $totalInfo['rebate'] += $v['total_rebate'];
                $totalInfo['prize'] += $v['total_prize'];
            }
            $totalInfo['real_amount'] = $totalInfo['amount'] - $totalInfo['rebate'];
            $totalInfo['final'] = $totalInfo['amount'] - $totalInfo['rebate'] - $totalInfo['prize'];
        }

        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id','cname']);
        self::$view->setVar('lotterys', $lotterys)->setVar('json_lotterys', json_encode($lotterys));
        //日期列表
        $dates = array();
        for ($i = 0; $i < 30; $i++) {
            $dates[] = date('Y-m-d', time() - 86400 * $i);
        }
        self::$view->setVar('dates', $dates);

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('modes', $modes);
        self::$view->setVar('startDate', $startDate);

        self::$view->setVar('issueSales', $issueSales);
        self::$view->setVar('issues', $issues);
        self::$view->setVar('totalInfo', $totalInfo);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_singlesalereport');
    }

    //总代团队余额
    public function topBalanceList()
    {
        $username = $this->request->getGet('username', 'trim');
        $status = $this->request->getGet('status', 'intval', 1);
        $is_test = $this->request->getGet('is_test', 'intval', 0);

        $result = array();
        $totalInfo = array('balance' => 0);
        if ($username) {
            if ($user = users::getItem($username, -1,false,1,1)) {
                $tmp = users::getTeamBalance($user['user_id'], true, $is_test,1);
                $result = array(
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'parent_id' => $user['parent_id'],
                    'type' => $user['type'],
                    'total_balance' => $tmp,
                );
            }
        }
        else {
            $result = users::getTopTeamBalance($status, $is_test,1);
            foreach ($result as $k => $v) {
                $totalInfo['balance'] += $v['total_balance'];
            }
        }

        //得到所有彩种
        // $lotterys = lottery::getItems();
        // self::$view->setVar('lotterys', $lotterys)->setVar('json_lotterys', json_encode($lotterys));
        //得到所有总代
        // $tops = users::getUserTree(0,true,0,-1,-1,'',1);
        $tops = users::getUserTreeField([
            'field' => ['user_id', 'username', 'type'],
            'parent_id' => 0,
            'freeze' => 1
        ]);
        self::$view->setVar('tops', $tops);

        //预设查询框
        self::$view->setVar('status', $status);
        self::$view->setVar('is_test', $is_test);
        self::$view->setVar('username', $username);

        self::$view->setVar('result', $result);
        self::$view->setVar('totalInfo', $totalInfo);
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));
        self::$view->render('game_topbalancelist');
    }

    public function childPackageList()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        $package_id = projects::dewrapId($wrap_id);
        $issue = $this->request->getGet('issue', 'trim');
        $is_trace = $this->request->getGet('is_trace', 'intval', -1);
        $check_prize_status = $this->request->getGet('check_prize_status', 'intval', -1);
        $include_childs = $this->request->getGet('include_childs', 'intval', '1|0');
        $status = $this->request->getGet('status', 'intval', -1);
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $this->searchDate($start_time, $end_time);
        if(($username = $this->request->getGet('username', 'trim')) && $username!= $GLOBALS['SESSION']['username'])
        {
            if(!$user = users::getItem($username,8,false,1,1)){
                showMsg("非法请求，该用户不存在或已被冻结");
            }
            if(!in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))){
                showMsg("非法请求，此用户不是你的下级");
            }
            $userId = $user['user_id'];
        }else{
            $username = $GLOBALS['SESSION']['username'];
            $userId = $GLOBALS['SESSION']['user_id'];
        }

        //得到所有彩种
        // $lotterys = lottery::getItems();
        $lotterys = lottery::getItemsNew(['lottery_id','zx_max_comb','total_profit','property_id']);
        //得到所有彩种奖期示例
        $lotteryIssueFormat = stripslashes(config::getConfig('lottery_issue_format'));

        $realAmount = $totalAmount = $totalPrize = $totalProfit = 0;

        if ($package_id) {
            $packages = projects::getPackagesById(array($package_id));
            $packagesNumber = 1;
            $packagesTotal = [];
        }
        else {
            $packagesNumber = projects::getPackagesNumber($lottery_id, $check_prize_status, -1, $issue, $is_trace, 0, $userId, $include_childs, $start_time, $end_time, $status,0,0,1);
            //>>判断输入的页码是否超过最大值.
            $startPos = getStartOffset($curPage, $packagesNumber, self::$teamReportPerPage);
            $packages = projects::getPackages($lottery_id, $check_prize_status, -1, $issue, $is_trace, 0, $userId, $include_childs, $start_time, $end_time, '', '', $status, '', $startPos, DEFAULT_PER_PAGE,-1,0,0,1);
            $packagesTotal = projects::getPackageTotal($lottery_id, $check_prize_status == 65535 ? -1 : $check_prize_status, -1, '', -1, 0, $userId, $include_childs, $start_time, $end_time, '', '', $status,1);
//            $packagesTotal['total_profit'] = 0;
        }

        //为算出奖金，先得到这些用户的返点
        if ($packages) {
            $user_ids = array_keys(array_spec_key($packages, 'user_id'));
            $userRebates = userRebates::getUsersRebates($user_ids, 0, 1);

            foreach ($packages as $k => $v)
            {
                $packages[$k]['amount'] = number_format($v['amount'], 4).'元';

                $packages[$k]['wrap_id'] = projects::wrapId($v['package_id'], $v['issue'], $v['lottery_id']);
                $packages[$k]['prize_mode'] = 2 * $lotterys[$v['lottery_id']]['zx_max_comb'] * (1 - $lotterys[$v['lottery_id']]['total_profit'] + $userRebates[$v['user_id']][$lotterys[$v['lottery_id']]['property_id']] - $v['cur_rebate']);

                if ($v['cancel_status'] == 0) $realAmount += $v['amount'];
                if ($v['check_prize_status'] == 1) $totalPrize += $v['prize'];

                if($v['cancel_status'] > 0 || $v['check_prize_status'] == 0)
                {
                    $packages[$k]['prize'] = '--';
                    $packages[$k]['profit'] = '--';
                }
                else{
                    $profit = $v['prize'] - $v['amount'];
                    $packages[$k]['prize'] = number_format($v['prize'], 4).'元';
                    $packages[$k]['profit'] = number_format($profit, 4).'元';
                    $totalProfit += $profit;
                }
                $totalAmount += $v['amount'];
            }
        }

        //预设查询框
        self::$view->setVar('lottery_id', $lottery_id);
        self::$view->setVar('wrap_id', $wrap_id);
        self::$view->setVar('issue', $issue);
        self::$view->setVar('is_trace', $is_trace);
        self::$view->setVar('check_prize_status', $check_prize_status);
        self::$view->setVar('username', $username);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('status', $status);

        self::$view->setVar('packages', $packages);

        self::$view->setVar('packagesTotal', $packagesTotal);
        self::$view->setVar('totalAmount', $totalAmount);
        self::$view->setVar('realAmount', $realAmount);
        self::$view->setVar('totalPrize', $totalPrize);
        self::$view->setVar('totalProfit', $totalProfit);
        self::$view->setVar('packagesNumber', $packagesNumber);

        self::$view->setVar('lotterys', $lotterys);
        self::$view->setVar('lotteryIssueFormat', $lotteryIssueFormat);
        self::$view->setVar('pageList', getPageList($packagesNumber, DEFAULT_PER_PAGE));
        self::$view->render('game_childpackagelist');
    }




    /**
     * snow
     * 导出投注记录  获取总条数
     */
    public function snowExportGameListGetTotalCount()
    {
        //游戏id
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        //是否是测试帐号
        $is_test = $this->request->getGet('is_test', 'intval', -1);
        //日期
        $date = $this->request->getGet('date', 'trim');
        //玩法id
        $method_id = $this->request->getGet('method_id', 'intval');
        //奖期
        $issue = $this->request->getGet('issue', 'trim');
        //元角模式
        $modes = $this->request->getGet('modes', 'floatval');
        //订单编号
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        //所属总代
        $top_username = $this->request->getGet('top_username', 'trim');
        //用户名
        $username = $this->request->getGet('username', 'trim');
        //是否包含下级
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        //游戏开始时间
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        //游戏结束时间
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        //订单状态
        $cancel_status = $this->request->getGet('cancel_status', 'intval');
        //中奖金额 必须>=
        $winMoney = $this->request->getGet('win_money', 'floatval');
        //投注金额 必须=
        $betMoney = $this->request->getGet('bet_money', 'floatval');
        //排序
        /*********************** snow 217-09-29 添加判断 ,确定在导出时最多只能导出1个月的数据**********************************/

        $result = $this->_toDetermineOfmonthFormat($start_time,$end_time);
        if($result['flag'] === false){
            //>>查询时间超过了一个月
            $data       = [
                'flag' => false,
                'data' => [
                    'error'    => '一次只能导出一周的数据',
                ]
            ];
            die(json_encode($data));
        }
        /*********************** snow 217-09-29 添加判断 ,确定在导出时最多只能导出1个月的数据**********************************/
        //当前页面 组装起来的数据分页的起始位置

        $package_id = projects::dewrapId($wrap_id);
        //必须指定订单号或用户名才能查询 否则也没有意义
        if ($package_id) {
            //>>只有一条数据 ,还是要进行导出
            $data       = [
                'flag' => true,
                'data' => [
                    'totalCount' => 1,
                    'pageSize' => 1,
                    'page' => 1,
                    'fileName' => '游戏记录-' . $package_id
                ]
            ];
            die(json_encode($data));

        }
        else {
            $check_prize_status = -1;
            //>>先获取总条数
            $packagesNumberInfo = projects::snow_getNewPackagesNumber($lottery_id, $check_prize_status, $is_test, $issue, -1,
                $modes, $top_username ? $top_username : $username, $include_childs, $start_time, $end_time, '', '',
                $cancel_status,$betMoney,$winMoney);


            if (!empty($packagesNumberInfo)) {
                $maxNumber  = $packagesNumberInfo['count'];
                if($maxNumber == 0){
                        die(['flag' => false, 'data' => '没有数据']);
                    }
                    $pageSize   = 10000;
                    $totalPage  = ceil($maxNumber / $pageSize);
                    $data       = [
                        'flag' => true,
                        'data' => [
                            'totalCount'    => $maxNumber,
                            'pageSize'      => $pageSize,
                            'totalPage'     => $totalPage,
                            'where'         => base64_encode($packagesNumberInfo['where']),
                            'fileName'      => '游戏记录-' . date('YmdHis',strtotime($start_time)) . '至' . date('YmdHis', strtotime($end_time) ). '-' . time()
                        ]
                    ];

                    die(json_encode($data));
                }

            $data       = [
                'flag' => false,
                'data' => [
                    'error'    => '没有数据可导出',
                ]
            ];
            die(json_encode($data));
            }


    }

    /**
     * snow
     * 导出投注记录  获取数据
     */
    public function snowExportGameListGetData()
    {
        //获取当前页数
        $page       = $this->request->getPost('page', 'intval');
        //获取每次查询条数
        $pageSize   = $this->request->getPost('pageSize', 'intval');
        $totalPage  = $this->request->getPost('totalPage', 'intval');
        //查询条件
        $where      = $this->request->getPost('where', 'trim');
        $where      = base64_decode($where);
        //>>文件名字
        $fileName   = $this->request->getPost('fileName', 'trim');

       //>>计算limi的开始数
        $startPos = ($page - 1) * $pageSize;
            //>>设置脚本执行时间为1分钟
            set_time_limit(0);
            ini_set('memory_limit', '1024M');

        if( !file_exists(ROOT_PATH . 'sscadmin/upload/file')){
            //>>如果文件夹不存在
            if( !mkdir(ROOT_PATH . 'sscadmin/upload/file',0777, true)){
                //>>创建目录失败
                die(json_encode(['flag' => false, 'data' =>['error' => '创建目录失败' ] ]));
            };
        }
//            $fp = fopen('php://output', 'a');
        $fp = fopen(ROOT_PATH . 'sscadmin/upload/file/'. $fileName . '.csv', 'a');
        if($page === 1){
            //>>导出表格的字段名称
            $headlist=['订单编号','用户','IP','彩种','奖期','单倍注数','倍数','模式','投注金额','购买时间','奖期截止时间','开奖号','中奖状态','奖金','派奖状态','撤单状态'];
            //输出Excel列名信息
            foreach ($headlist as $key => $value) {
                //CSV的Excel支持GBK编码，一定要转换，否则乱码
                $headlist[$key] = iconv('utf-8', 'GBK//IGNORE', $value);
            }
            //将数据通过fputcsv写到文件句柄
            if(fputcsv($fp, $headlist) === false){
                fclose($fp);
                die(json_encode(['flag' => false, 'data' =>['error' => '首行数据出错' ] ]));
            };
            unset($headlist);
        }

        //计数器
        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        // $lotterys = lottery::getItems(0, -1);
        $lotterys = lottery::getItemsNew(['lottery_id', 'property_id','zx_max_comb','total_profit','cname'],0, -1);
        $data = projects::snow_getPackagesAdmin($startPos, $pageSize, $where);
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {

            $row = $data[$i];
            $row = $this->_exportGameListToCsv($row, $lotterys);

            foreach ($row as $key => $value) {
                $value      = $value === '' ? '空' : $value;
                $row[$key]  = iconv('utf-8', 'GBK//IGNORE', $value);
            }

            if(fputcsv($fp, $row) === false){
                fclose($fp);
                $data = [
                    'flag' => false,
                    'data' => [
                        'error' =>'写入第'. ((($page-1) * $pageSize) + $i) . '行数据出错'  ,
                        'page' => $page
                    ]
                ];
                die(json_encode($data));
            };
            unset($row);
        }
        fclose($fp);
        if($page === $totalPage){
            //>>如果是最后一页面
            $data = [
                'flag' => true,
                'data'=> [
                    'fileName' => 'upload/file/'. $fileName . '.csv',
                ]
            ];
            die(json_encode($data));
        }
        $data = [
            'flag' => true,
            'data'=> [
                'page'=> $page,
            ]
           ];
        die(json_encode($data));
    }
    /**
     * snow
     * 导出投注记录
     */
    public function snowExportGameList()
    {
        //游戏id
        $lottery_id = $this->request->getGet('lottery_id', 'intval');
        //是否是测试帐号
        $is_test = $this->request->getGet('is_test', 'intval', -1);
        //日期
        $date = $this->request->getGet('date', 'trim');
        //玩法id
        $method_id = $this->request->getGet('method_id', 'intval');
        //奖期
        $issue = $this->request->getGet('issue', 'trim');
        //元角模式
        $modes = $this->request->getGet('modes', 'floatval');
        //订单编号
        $wrap_id = $this->request->getGet('wrap_id', 'trim');
        //所属总代
        $top_username = $this->request->getGet('top_username', 'trim');
        //用户名
        $username = $this->request->getGet('username', 'trim');
        //是否包含下级
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        //游戏开始时间
        $start_time = $this->request->getGet('start_time', 'trim', date("Y-m-d 00:00:00"));
        //游戏结束时间
        $end_time = $this->request->getGet('end_time', 'trim', date("Y-m-d 23:59:59"));
        //订单状态
        $cancel_status = $this->request->getGet('cancel_status', 'intval');
        //中奖金额 必须>=
        $winMoney = $this->request->getGet('win_money', 'floatval');
        //投注金额 必须=
        $betMoney = $this->request->getGet('bet_money', 'floatval');
        //排序
        $sort = $this->request->getGet('sort', 'intval', -1);
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>>获取当前页
        /*********************** snow 217-09-29 添加判断 ,确定在导出时最多只能导出1个月的数据**********************************/

        if(($export_game = $this->request->getGet('export_game', 'intval')) === 1){

            $result = $this->_toDetermineOfmonthFormat($start_time,$end_time);
            if($result['flag'] === false){
                showMsg('只能导出2个月之内的数据');
            }
        }
        /*********************** snow 217-09-29 添加判断 ,确定在导出时最多只能导出1个月的数据**********************************/
        //当前页面 组装起来的数据分页的起始位置

        $package_id = projects::dewrapId($wrap_id);
        $packages = array();
        //必须指定订单号或用户名才能查询 否则也没有意义
        if ($package_id) {
            //>>只有一条数据 ,还是要进行导出
            if ($package = projects::getPackageSnow($package_id,0 , false,$betMoney,$winMoney)) {
                $tmp = users::getItem($package['user_id']);
                $package['username'] = $tmp['username'];
                $package['user_status'] = $tmp['status'];
                $packages[0] = $package;
                //>>调用方法导出数据
               $this->_exportHandleDate($packages);
            }
        }
        else {
            $order_by = $sort != -1 ? ($sort == 1 ? 'amount DESC,package_id DESC' : 'prize DESC,package_id DESC') : '';
            $check_prize_status = -1;
            //>>先获取总条数
            $packagesNumberInfo = projects::snow_getNewPackagesNumber($lottery_id, $check_prize_status, $is_test, $issue, -1,
                $modes, $top_username ? $top_username : $username, $include_childs, $start_time, $end_time, '', '',
                $cancel_status,$betMoney,$winMoney);


            $maxNumber  = $packagesNumberInfo['count'];
            $pageSize   = 10000;
            //>>获取到正确的分页
            $startPos    = getStartOffset($curPage, $maxNumber, $pageSize);
            $page       = ceil($maxNumber / $pageSize);
            //>>设置脚本执行时间为1分钟
            set_time_limit(0);
            ini_set('memory_limit', '1024M');
            $fileName = '游戏记录-' . date('YmdHis',strtotime($start_time)) . '至' . date('YmdHis', strtotime($end_time));
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
            header('Cache-Control: max-age=0');

            //打开PHP文件句柄,php://output 表示直接输出到浏览器
            $fp = fopen('php://output', 'a');
            //>>导出表格的字段名称
            $headlist=['订单编号','用户','IP','彩种','奖期','单倍注数','倍数','模式','投注金额','购买时间','奖期截止时间','开奖号','中奖状态','奖金','派奖状态','撤单状态'];
            //输出Excel列名信息
            foreach ($headlist as $key => $value) {
                //CSV的Excel支持GBK编码，一定要转换，否则乱码
                $headlist[$key] = iconv('utf-8', 'gbk', $value);
            }
            //将数据通过fputcsv写到文件句柄
            fputcsv($fp, $headlist);
            unset($headlist);

            //计数器
            $num = 0;

            //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
            $limit = 5000;
            // $lotterys = lottery::getItems(0, -1);
            $lotterys = lottery::getItemsNew(['property_id','zx_max_comb','total_profit','cname'],0, -1);
            for($k = 0; $k < $page; ++$k){
                $startPos = $k * 20000;
                //逐行取出数据，不浪费内存
                $data = projects::snow_getPackagesAdmin($startPos, 20000);
                $count = count($data);
                for ($i = 0; $i < $count; $i++) {

                    $num++;

                    //刷新一下输出buffer，防止由于数据过多造成问题
                    if ($limit == $num) {
                        ob_flush();
                        flush();
                        $num = 0;
                    }

                    $row = $data[$i];
                    $row = $this->_exportGameListToCsv($row, $lotterys);

                    foreach ($row as $key => $value) {
                        $value      = $value === '' ? '空' : $value;
                        $row[$key]  = iconv('utf-8', 'GBK//IGNORE', $value);
                    }

                    fputcsv($fp, $row);
                }

                unset($data);

            }


        }


    }


    /**
     * 导出数据到csv
     * @param $val
     * @param $lotterys
     */
    private function _exportGameListToCsv($val, $lotterys)
    {

        //>用户状态
        $user_status = [
            '0' => '[已删除]',
            '1' => '[已冻结]',
            '5' => '[已回收]',
        ];

        //>>撤单状态
        $cancel_status = [
            '1' => '用户撤单',
            '2' => '追中撤单',
            '3' => '出号撤单',
            '4' => '未开撤单',
            '9' => '系统撤单',
        ];

        //>>需要导出的数据

        //>>按照$title的顺序组装数据

        $lottery_id  = $val['lottery_id'];
        $user_id    = $val['user_id'];
        $lottery    = isset($lotterys[$lottery_id]) ? $lotterys[$lottery_id] : null;
        //>>之前少写了这个变量
        $userRebates = userRebates::getUsersRebates([$user_id], 0, 1);
        $val['wrap_id'] = projects::wrapId($val['package_id'], $val['issue'], $lottery_id);

        /************************ snow 加一个判断 ,确认键值是否存在 ,在进行计算 *********************************************/

        if(isset($lottery) && isset($userRebates[$user_id][$lottery['property_id']])){
            $val['prize_mode'] =  2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $userRebates[$user_id][$lottery['property_id']] - $val['cur_rebate']);
        }

        $lotteryIssue = issues::getItemsByIssue($lottery_id, [$val['issue']]);

       return [
            $val['wrap_id'],
            isset($user_status[$val['user_status']]) ? (isset($val['is_test']) && !empty($val['is_test']) ? $val['username'] .'测试' : '' . $user_status[$val['user_status']])  : $val['username'] ,
            $val['user_ip'],
            isset($lottery['cname']) ? $lottery['cname'] : '未知',
            $val['issue'],
            $val['single_num'],
            $val['multiple'],
            isset($GLOBALS['cfg']['modes'][strval(floatval($val['modes']))]) ? $GLOBALS['cfg']['modes'][strval(floatval($val['modes']))] : '',
            $val['amount'],
            $val['create_time'],
            isset($lotteryIssue[$val['issue']]['end_sale_time']) ? $lotteryIssue[$val['issue']]['end_sale_time'] : '',
            $val['lottery_id'] != 15 ? (isset($lotteryIssue[$val['issue']]['code']) && $lotteryIssue[$val['issue']]['code'] != '' ? $lotteryIssue[$val['issue']]['code'] : '未开') : '-',
            $val['check_prize_status'] == 0 ? '未判断' :($val['check_prize_status'] == 1 ? '中奖' : '没中'),
            $val['prize'] > 0 ? $val['prize'] : '',
            $val['check_prize_status'] == 1 ? ($val['send_prize_status'] == 0 ? '未派奖' : ($val['send_prize_status'] == 1 ? '已派奖' : '')) : '',
            $val['cancel_status'] == 0 ? (isset($cancel_status[$val['cancel_status']])  ? $cancel_status[$val['cancel_status']] : '') : '',

        ];

    }

    /**
     * 人工开奖
     * 1.先判断是否中奖
     * 2.派奖
     */
    public function thePrizeAjax()
    {
        $wrap_id = $this->request->getPost('wrap_id','trim','');
        if(empty($wrap_id))
        {
            die(json_encode(['code'=>1,'msg'=>'参数异常!']));
        }
        $packageId = projects::dewrapId($wrap_id);
//        echo $packageId;exit;
       // echo $packageId;exit;
        if(intval($packageId) <= 0)
        {
            die(json_encode(['code'=>1,'msg'=>'参数异常!']));
        }
        $packageInfo = projects::getPackage($packageId);
        if(empty($packageInfo))
        {
            die(json_encode(['code'=>1,'msg'=>'订单异常!']));
        }
        if($packageInfo['cancel_status'] > 0)
        {
            die(json_encode(['code'=>1,'msg'=>'此订单已经撤单!']));
        }
        if($packageInfo['check_prize_status'] > 0)
        {
            die(json_encode(['code'=>1,'msg'=>'已经开过奖的不能再次开奖!']));
        }
        $lottery_id = $packageInfo['lottery_id'];
        $lotteryInfo = lottery::getItem($lottery_id);
        if(empty($lotteryInfo))
        {
            die(json_encode(['code'=>1,'msg'=>'没有找到此订单的彩种信息!']));
        }
        $userId = $packageInfo['user_id'];
        $userInfo = users::getItem($userId);
        if(empty($userInfo))
        {
            die(json_encode(['code'=>1,'msg'=>'用户状态异常!']));
        }
        $issues = issues::getItemsByIssue($lottery_id,[$packageInfo['issue']]);
        /******************  author snow 添加判断投注时间合法性*************************************/
        if ($issues[$packageInfo['issue']]['end_sale_time'] < $packageInfo['create_time']) {
            die(json_encode(['code'=>1,'msg'=>'投注时间异常,投注时间大于奖期截止时间!']));
        }
        /******************  author snow 添加判断投注时间合法性*************************************/
        if(empty($issues))
        {
            die(json_encode(['code'=>1,'msg'=>'没有找到此订单对应的将期信息!']));
        }
        $issueInfo = $issues[$packageInfo['issue']];


        if(empty($issueInfo['code']))
        {
            die(json_encode(['code'=>1,'msg'=>$lotteryInfo['cname'].$packageInfo['issue'].'期没有开奖,请耐心等待!']));
        }
        //判断是否中奖

        $projects = projects::getItems(0,0,$packageId);
        if(empty($userInfo))
        {
            die(json_encode(['code'=>1,'msg'=>'没有找到方案信息!']));
        }
        asort($projects);
        $packageProjects = array();
        foreach ($projects as $v) {
            $packageProjects[$packageId][$v['project_id']] = $v;
        }

        $methods = methods::getItems($lottery_id);
        $rebate =  userRebates::getUserRebate($userId, $lotteryInfo['property_id']);
        if (!$rebate) {
            die(json_encode(['code'=>1,'msg'=>"用户{$v['user_id']}的返点不存在"]));

        }
        $totalPrize  = 0;
        $packagePrize = 0;
        //开始事务
        $GLOBALS['db']->startTransaction();
        foreach ($packageProjects[$packageId] as $v) {
            if (!isset($methods[$v['method_id']])) {
                die(json_encode(['code'=>1,'msg'=>"玩法id={$v['method_id']}不存在"]));
            }

            if (!in_array($v['modes'], array('1', '0.1', '0.01', '0.001','0.5','0.05'))) {
                die(json_encode(['code'=>1,'msg'=>'Invalid modes']));
            }

            $prize = methods::computePrize($lotteryInfo, $methods[$v['method_id']], $v['user_id'], $rebate, $v['cur_rebate'], $issueInfo['code'], $v['code']);

            $finalPrize = round($prize * $v['multiple'] * $v['modes'], PRIZE_PRECISION);

            if ($finalPrize > 0) {
                $res = projects::updateItem($v['project_id'],['prize'=>$finalPrize]);
                if(!$res)
                {
                    die(json_encode(['code'=>1,'msg'=>'更新方案订单!']));
                }
                $packagePrize += $finalPrize;
            }
        }

        $packagePrize = round($packagePrize, 4);

        if ($packagePrize > 0) {
            $totalPrize += $packagePrize;
        }



        if(floatval($totalPrize) == 0)
        {//没有中奖
            $res = projects::updatePackage($packageId,['check_prize_status'=>2,'prize'=>0,'cur_rebate'=>$rebate]);
            if(!$res)
            {
                $GLOBALS['db']->rollback();
                die(json_encode(['code'=>1,'msg'=>'更新订单状态出错!']));
            }
            $GLOBALS['db']->commit();
           die(json_encode(['code'=>0,'msg'=>'']));
        }else{
            $res = projects::updatePackage($packageId,['check_prize_status'=>1,'prize'=>$totalPrize,'cur_rebate'=>$rebate]);
            if(!$res)
            {

                $GLOBALS['db']->rollback();
                die(json_encode(['code'=>1,'msg'=>'更新订单状态出错!']));
            }
        }
         $newPackageInfo = projects::getPackage($packageId);
         unset($packageInfo);
        //派奖
        $res = game::sendPrizeAdmin($lotteryInfo,$issueInfo,$newPackageInfo);
        if(!$res)
        {

            $GLOBALS['db']->rollback();
            die(json_encode(['code'=>1,'msg'=>game::$error]));
        }
        $GLOBALS['db']->commit();
        die(json_encode(['code'=>0,'msg'=>'']));
    }

    /**
     * author snow
     * 操作获取充值详情
     * @param $trade_type
     * @param $day $start
     */
    private function _getDepositsDayTail($trade_type, $day = '')
    {
        if ($day == '') {
            $start = date('Y-m-d 00:00:00');
            $end   = date('Y-m-d 23:59:59');
        } else {
            $start = $day . ' 00:00:00';
            $end   = $day . ' 23:59:59';
        }
        $sql =<<<SQL
SELECT d.user_id,u.username AS username,d.trade_type,COUNT(d.user_id) AS num,SUM(d.amount) AS amount, MIN(d.create_time) AS create_time,MAX(finish_time) AS finish_time FROM deposits AS d,users AS u
WHERE d.user_id = u.user_id AND u.top_id != (SELECT user_id FROM users WHERE username = 'dcsite')
AND d.finish_time > '{$start}' AND d.finish_time < '{$end}'
AND d.trade_type = {$trade_type}
AND d.status = 8 
AND u.is_test = 0 
GROUP BY d.user_id
SQL;
        $dayTail = $GLOBALS['db']->getAll($sql,[],'user_id');
        self::$view->setVar('str', '充值');
        self::$view->setVar('dayTail', $dayTail);
        self::$view->render('game_tailreport');
        exit;

    }

    /**
     * author snow
     * 操作获取提款详情
     * @param $day $start
     */
    private function _getWithdrawDayTail( $day = '')
    {
        if ($day == '') {
            $start = date('Y-m-d 00:00:00');
            $end   = date('Y-m-d 23:59:59');
        } else {
            $start = $day . ' 00:00:00';
            $end   = $day . ' 23:59:59';
        }
        $sql =<<<SQL
SELECT d.user_id,u.username AS username,COUNT(d.user_id) AS num,SUM(d.amount) AS amount, MIN(d.create_time) AS create_time,MAX(finish_time) AS finish_time 
FROM withdraws AS d,users AS u
WHERE d.user_id = u.user_id 
AND u.top_id != (SELECT user_id FROM users WHERE username = 'dcsite')
AND d.finish_time > '{$start}' AND d.finish_time < '{$end}'
AND d.status = 8 
AND u.is_test = 0 
AND d.amount >= 1 
GROUP BY d.user_id
SQL;
        $dayTail = $GLOBALS['db']->getAll($sql,[],'user_id');
        self::$view->setVar('str', '提款');
        self::$view->setVar('dayTail', $dayTail);
        self::$view->render('game_tailreport');
        exit;

    }


    /**
     * author snow
     * 操作获取其它费用详情
     * @param $flag string 类型
     * @param $day string 日期
     */
    private function _getOrderDayTail($flag, $day = '')
    {
        if ($day == '') {
            $start = date('Y-m-d 00:00:00');
            $end   = date('Y-m-d 23:59:59');
        } else {
            $start = $day . ' 00:00:00';
            $end   = $day . ' 23:59:59';
        }
        $sql =<<<SQL
SELECT a.from_user_id AS user_id,b.username AS username,COUNT(a.from_user_id) AS num,SUM(a.amount) AS amount, MIN(a.create_time) AS create_time,MAX(create_time) AS finish_time 
FROM orders AS a,users AS b  
WHERE a.from_user_id = b.user_id 
AND b.is_test = 0
AND a.type = {$flag}
AND b.top_id != (SELECT user_id FROM users WHERE username = 'dcsite') 
AND a.create_time >= '{$start}' 
AND a.create_time <= '{$end}' 
GROUP BY a.from_user_id DESC
SQL;
        $dayTail = $GLOBALS['db']->getAll($sql,[],'user_id');
        self::$view->setVar('str', $flag == 102 ? '充值优惠' : '投注日返水');
        self::$view->setVar('dayTail', $dayTail);
        self::$view->render('game_tailreport');
        exit;

    }



}

