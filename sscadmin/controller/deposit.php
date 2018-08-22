<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

/**
 * 控制器：存款管理
 */
class depositController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'depositList'                           => '存款列表',
        'checkNew'                              => '检查新提案',
        'viewDeposit'                           => '查看存款提案',
        'charge'                                => '审核并充值',
        'cancel'                                => '取消提案',
        'addDeposit'                            => '手工存款',
        'manualDepositList'                     => '手工存款列表',
        'viewManualDeposit'                     => '手工存款明细',
        'addManualDeposit'                      => '新增手工存款提案',
        'operateManualDeposit'                  => '处理手工存款提案',
        'acceptManualDeposit'                   => '受理手工存款',
        'recover'                               => '恢复提案',
        'snowExportDepositListGetTotalCount'    => '导出存款记录,获取总条数',
        'snowExportDepositListGetData'          => '导出存款记录,获取数据',
        'deleteDepositMany'                     => '批量删除数据',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);

    }

    public function recover()
    {
        $deposit_id = $this->request->getPost('deposit_id', 'trim');

        // 取消
        $data = array('finish_admin_id' => 0, 'status' => '0', 'remark' => '');
        $result = deposits::updateItem($deposit_id, $data);


        if (IS_AJAX) {
            if (!$result) {
                response(['errno' => $result, 'errstr' => '恢复失败！数据库错误！']);
            }
            response(['errno' => 0, 'errstr' => '恢复成功!']);
        } else {
            $locations = array(0 => array('title' => '返回存款列表', 'url' => url('deposit', 'viewDeposit', array('deposit_id' => $deposit_id))));

            if (!$result) {
                response(['恢复失败！数据库错误！', 1, $locations], 'MSG');
            }
            response(['恢复成功！', url('deposit', 'viewDeposit', array('deposit_id' => $deposit_id))], 'ALERT');
        }
    }

    //ajax调用 检查是否有新的“未处理”提案
    public function checkNew()
    {
        $deposits = (new baseModel('deposits'))->where([
            'status'=>['<' , 8]
        ])->select();

        $result = array('newNum' => count($deposits));
        $where=" and finish_time = '0000-00-00 00:00:00' and (status = 3 or status = 0) and (UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(create_time) > 1800) order by deposit_id desc limit 50";
        $list=deposits::getListByCond($where,'deposit_id');
        if($list)
        {
            $str = '';
          foreach ($list as $v)
          {
              $str.=','.$v['deposit_id'];
          }
          if($str) {
              $str = ltrim($str, ',');
              deposits::upList("deposit_id in ($str)", 9);
          }
        }
        echo json_encode($result);
    }

    public function depositList()
    {

        /************** author snow 添加返回url *******************************/


        $back_url = $GLOBALS['SESSION']['back_url'];
        if ($back_url) {
            //>>如果存在,就把它置为空
            $GLOBALS['SESSION']['back_url'] = null;
        }
        /************** author snow 添加返回url *******************************/
        //$agent_id = -1, $user_id = 0, $trade_type = 0, $deposit_bank_id = 0, $deposit_card_id = '', $startDate = '', $endDate = '', $status = -1
        $top_username = $this->request->getGet('top_username', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        //>> snow 测试条件 默认为否
        $is_test = $this->request->getGet('is_test', 'intval', '0');
        $is_manual = $this->request->getGet('is_manual', 'intval', '-1');
        $trade_type = $this->request->getGet('trade_type', 'trim');
        $deposit_bank_id = $this->request->getGet('deposit_bank_id', 'intval', 0);
        $deposit_card_id = $this->request->getGet('deposit_card_id', 'intval', 0);
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d 00:00:00"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $time_status = $this->request->getGet('time_status', 'intval', 1);
        //$startAmount = $this->request->getGet('startAmount', 'intval', 1);
        $startAmount = $this->request->getGet('start_amount', 'intval' );
        $endAmount = $this->request->getGet('end_amount', 'intval');
        $status = $this->request->getGet('status', 'intval', -1);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        //>>snow 获取当前页
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $order_num = $this->request->getGet('order_num', 'trim', '');
        if($startAmount >0 && $endAmount>0)
        {
            if($startAmount > $endAmount)
            {
                showMsg("起始金额不能大于结束金额!");
            }
        }
        /************************* snow 添加时间判断  ,提示,只能查询导出一个月之内的数据*******************************************/
        $export_excel_status = $this->request->getGet('export_deposit_list', 'intval');
        if($export_excel_status === 1){
            $startPos = -1;   //>>等于-1  导出全部数据
            //>>判断开始时间与结束时间,是否在一个月之内.如果不是,取结束时间到之前1个月的数据.
            $result = $this->_toDetermineOfmonth($startDate,$endDate);
            if($result['flag'] === false){
                //>>查询时间超过了一个月
                showMsg('查询时间超过了一个月');
            }

        }
        /************************* snow 添加时间判断  ,提示,只能查询导出一个月之内的数据*******************************************/
        if ($time_status == 1) {//从创建时间查
            $create_stime = $startDate;
            $create_etime = $endDate;
            $finish_stime = $finish_etime = '';
        } elseif ($time_status == 2) {//从完成处理时间查
            $finish_stime = $startDate;
            $finish_etime = $endDate;
            $create_stime = $create_etime = '';
        }

        //>>添加排除dcsite 数据条件
        $options = [
            'user_name' => $top_username ? $top_username : $username,
            'include_childs' => $include_childs,
            'is_test' => $is_test,
            'is_manual' => $is_manual,
            'status' => $status,
            'trade_type' => $trade_type,
            'deposit_bank_id' => $deposit_bank_id,
            'deposit_card_id' => $deposit_card_id,
            'start_amount' => $startAmount,
            'end_amount' => $endAmount,
            'order_num' => $order_num,
            'start_date' => $finish_stime,
            'end_date' => $finish_etime,
            'start' =>  -1,  //>>起始值,分页的.-1为不分页
            'create_stime' => $create_stime,
            'create_etime' => $create_etime,
        ];

//       echo '<pre>';
//       print_r($deposits);exit;
        //>>添加排除dcsite 数据条件
        $trafficInfo = deposits::getTrafficInfoByTradeTypesExclude($options);
        //>>snow 获取到正确的分页码
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        $options['start'] = $startPos;  //>>修改分页起始值.
        $deposits = deposits::getItemsByTradeTypesExclude($options);
        //得到所有总代
        // $topUsers = users::getUserTree(0);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        self::$view->setVar('json_topUsers', $topUsers);
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到支持的银行列表
        self::$view->setVar('bankList', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //存款方式
        $tradeTypes = (new cardDepositGroup)->index(true)->order('sort ASC')->select();
        self::$view->setVar('tradeTypes', $tradeTypes);
        //得到收款卡列表
        $depositCards = cards::getItemsWithDeletedCard(1);

        $bankDepositCards = array();
        foreach ($depositCards as $v) {
            $bankDepositCards[$v['bank_id']][] = $v;
        }

        //本页有效金额
        $pageRealAmount = 0;
        foreach ($deposits as $deposit) {
            if ($deposit['status'] == 8) {
                $pageRealAmount += $deposit['amount'];
            }
        }

        /******************* snow 添加判断 ,是否是导出数据到excel*********************************************/
        if($export_excel_status === 1){
            //>>调用方法处理数据 ,并导出到excel 表格
            $this->_exportDeposiListData($deposits,$GLOBALS['cfg']['bankList'],$depositCards);

        }else{

            //查询选项
            self::$view->setVar('time_status', $time_status);
            self::$view->setVar('top_username', $top_username);
            self::$view->setVar('username', $username);
            self::$view->setVar('include_childs', $include_childs);
            self::$view->setVar('is_test', $is_test);
            self::$view->setVar('is_manual', $is_manual);
            self::$view->setVar('trade_type', $trade_type);
            if($startAmount > 0) {
                self::$view->setVar('startAmount', $startAmount);
            }
            if($endAmount > 0) {
                self::$view->setVar('endAmount', $endAmount);
            }
            self::$view->setVar('deposit_bank_id', $deposit_bank_id);
            self::$view->setVar('deposit_card_id', $deposit_card_id);
            self::$view->setVar('startDate', $startDate);
            self::$view->setVar('endDate', $endDate);
            self::$view->setVar('status', $status);
            self::$view->setVar('pageRealAmount', $pageRealAmount);

            self::$view->setVar('bankDepositCards', $bankDepositCards);
            self::$view->setVar('deposits', $deposits);
            self::$view->setVar('trafficInfo', $trafficInfo);
            self::$view->setVar('depositCards', $depositCards);
            self::$view->setVar('pageList', getPageList($trafficInfo['count'], DEFAULT_PER_PAGE));
            self::$view->render('deposit_depositlist');
        }
        /******************* snow 添加判断 ,是否是导出数据到excel*********************************************/

    }


    /**
     * by snow  2017-09-09
     * @param $startDate  string 开始日期
     * @param $endDate    string  结束日期
     * @return bool      返回 是两个日期之差是否在一个月之内
     */
    private function _toDetermineOfmonth($startDate, $endDate)
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
        if(($actTime = $endTime - 24 * 3600 * 32) > $startTime){
            $result = ['startDate' => date('Y-m-d H:i:s', $actTime), 'flag' => false];
        }else{
            $result = ['startDate' => $startDate, 'flag' => true];
        }

        return $result;


    }
    /**
     * 处理数据,组合成 导出excel 表格需要的格式
     * @param $data
     * @param $bankList
     * @param $depositCards
     * @return bool
     */

    private function _exportDeposiListData($data, $bankList, $depositCards)
    {
        //>>如果没有数据,直接返回
        if(!is_array($data) || empty($data)){
            showMsg('没有可导出的数据');
        }

        //>>状态
        $status = [
            '0' => '未处理',
            '1' => '已受理',
            '2' => '已审核',
            '8' => '已执行',
            '9' => '取消提案',
        ];
        //>>导出表格的字段名称
        $title=['提案id','用户id','用户名','类型','卡户名','金额','收款银行','收款卡','网站订单','支付订单','客户发起时间','完成时间','时间差','真实存款姓名微信号或其他账号','备注','状态',];
        //>>处理数据 组合成需要的数据
        $excelArray = [];//>>需要导出的数据
        foreach($data as $key => $val){

            //>>按照$title的顺序组装数据
            $excelArray[] = [
                $val['deposit_id'],
                $val['user_id'],
                $val['username'] . (isset($val['type']) && $val['type'] == 1 ? '[推广]' : '') . ($val['is_test'] ? '[测试]' : '') . ($val['user_status'] == 0 ? '[已删除]' : ($val['user_status'] == 1 ? '[已冻结' : ($val['user_status'] == 5 ? '[已回收]' : '')))  ,
                $val['level'] == 0 ? '总代' : ($val['level'] == 100 ? '会员' : $val['level'] . '代'),
                mb_substr($val['player_card_name'], 0, mb_strlen($val['player_card_name'], 'utf-8') - 1, 'utf-8') . '*',
                $val['amount'],
                isset($bankList[$val['deposit_bank_id']]) ? $bankList[$val['deposit_bank_id']] : '',
                isset($depositCards[$val['deposit_card_id']]['card_name']) ? $depositCards[$val['deposit_card_id']]['card_name'] : '',
                $val['shop_order_num'],
                $val['order_num'],
                $val['create_time'],
                $val['finish_time'],
                $val['finish_time'] !== '0000-00-00 00:00:00' ? ((int)strtotime($val['finish_time']) - (int)strtotime($val['create_time'])) . '秒' : '未完成',
                $val['real_name'] ,
                $val['remark'] ,
                isset($status[$val['status']]) ?  $status[$val['status']] : '',

            ];
        }
        unset($data);
        $fileName = '存款记录' . date('YmdHis');
        $this->_exportExcelData($excelArray, $title, $fileName);

    }


/******************************** snow  导出数据到excel 表格************************************************************/
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


/******************************** snow  导出数据到excel 表格************************************************************/
    // 查看存款提案 状态更改为"已受理"
    public function viewDeposit()
    {


        /************** author snow 添加返回url *******************************/

        $back_url = $this->request->getGet('back_url','trim', '');
        if (!empty($back_url)) {
            //>>把back_url 写入session
            $GLOBALS['SESSION']['back_url'] = $back_url;
        } else {
            $back_url = $GLOBALS['SESSION']['back_url'];
        }
        self::$view->setVar('back_url', $back_url);
        /************** author snow 添加返回url *******************************/
        $locations = array(0 => array('title' => '返回存款列表', 'url' => url('deposit', 'depositList')));
        $deposit_id = $this->request->getGet('deposit_id', 'intval');
        if (!$deposit = deposits::getItem($deposit_id)) {
            showMsg("找不到提案！");
        }
        //允许查看，但不能执行充值
        if (!$user = users::getItem($deposit['user_id'], -1)) {
            //showMsg("用户不存在");
        }

        //得到管理员列表
        self::$view->setVar('admins', admins::getItems());
        //存款方式
        self::$view->setVar('tradeTypes', $GLOBALS['cfg']['tradeTypes']);
        //得到支持的银行列表
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);
        //得到收款卡列表
        $depositCards = cards::getItemsWithDeletedCard(1);

        self::$view->setVar('deposit', $deposit);
        self::$view->setVar('user', $user);
        self::$view->setVar('depositCards', $depositCards);
        self::$view->render('deposit_viewdeposit');
    }

//    // 应财务要求，存款直接执行，所以略过审核
//    public function verify()
//    {
//        $locations  = array(0 => array('title' => '返回存款列表', 'url'=>url('deposit', 'viewDeposit', array('deposit_id' => $deposit_id))));
//
//        $deposit_id = $this->request->getPost('deposit_id', 'intval');
//        if (!$deposit = deposits::getItem($deposit_id)) {
//            showMsg("找不到提案！", 1, $locations);
//        }
//        if ($deposit['status'] != 1) {  //状态必须是“已受理”的情况下才能审核
//            showMsg("该提案状态不是“已审核”，拒绝执行充值！", 1, $locations);
//        }
//
//        //审核
//        $url = url('deposit', 'viewDeposit', array('deposit_id' => $deposit_id));
//        $data = array('verify_admin_id' => $GLOBALS['SESSION']['admin_id'], 'status' => '2');
//        if (!deposits::updateItem($deposit_id, $data)) {
//            showMsg("审核失败！数据库错误！", 1, $locations);
//        }
//        echo "<script>alert('审核成功！');window.location.href='$url';</script>";
//        die();
//    }

    // 审核并充值 为玩家充游戏币 状态为"已成功"
    public function charge()
    {
        $deposit_id = $this->request->getPost('deposit_id', 'intval');

        // 处理充值
        $result = deposits::charge($deposit_id, $GLOBALS['SESSION']['admin_id']);
        // 不用审核了，存款一步到位
//        $data = array('status' => '2');
//        if (!deposits::updateItem($deposit_id, $data)) {
//            showMsg("审核失败！数据库错误！", 1, $locations);
//        }

        if (IS_AJAX) {
            if ($result !== true) {
                response(['errno' => $result, 'errstr' => '操作失败!']);
            }
            response(['errno' => 0, 'errstr' => '审核并执行成功!']);
        } else {

            $back_url = $GLOBALS['SESSION']['back_url'] ? base64_decode($GLOBALS['SESSION']['back_url']) :  url('deposit', 'depositList');

            $locations = array(0 => array('title' => '返回存款列表', 'url' => $back_url));   //, array('deposit_id' => $deposit_id)

            if ($result !== true) {
                response(['操作失败！错误代码:' . $result, 1, $locations], 'MSG');
            }
            response(['审核并执行成功！', 0, $locations], 'MSG');
        }

    }

    // 取消提案 并写明原因
    public function cancel()
    {
        $deposit_id = $this->request->getPost('deposit_id', 'intval');
        $remark = $this->request->getPost('remark', 'trim');

        $locations = array(0 => array('title' => '返回存款列表', 'url' => url('deposit', 'viewDeposit', array('deposit_id' => $deposit_id))));

        $errstr = '';
        if ($remark == '' || $remark == '请输入取消原因！') {
            $errstr = '取消提案必须写明原因！';
        }
        if (!$deposit = deposits::getItem($deposit_id)) {
            $errstr = '找不到提案！';
        }
        if ($deposit['status'] >= 8) {
            $errstr = '对不起，该笔提案已经处理，拒绝再次操作！';
        }

        if ($errstr)
            IS_AJAX ? response(['errno' => 1, 'errstr' => $errstr]) : response([$errstr, 1, $locations], 'MSG');

        // 取消
        $data = array('finish_admin_id' => $GLOBALS['SESSION']['admin_id'], 'status' => '9', 'remark' => $remark);

        if (!deposits::updateItem($deposit_id, $data)) {
            IS_AJAX ? response(['errno' => 1, 'errstr' => '取消失败！数据库错误！']) :
                response(['取消失败！数据库错误！', 1, $locations], 'MSG');
        }
        
        IS_AJAX ? response(['errno' => 0, 'errstr' => '取消成功！']) :
            response(['取消成功！', url('deposit', 'viewDeposit', array('deposit_id' => $deposit_id))], 'ALERT');
    }

    //存款允许手工添加
    public function addDeposit()
    {
        $locations = array(0 => array('title' => '继续添加存款', 'url' => url('deposit', 'addDeposit')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            if (!$user = users::getItem($username)) {
                showMsg("无效的用户名，请检查", 1, $locations);
            }
            if (!$card_id = $this->request->getPost('card_id', 'trim')) {
                showMsg("请选择收款卡。请检查", 1, $locations);
            }
            $player_pay_time = $this->request->getPost('player_pay_time', 'trim');
            $player_pay_time = str_replace(array('年', '月', '日'), array('-', '-', ''), $player_pay_time);
            if (!preg_match("`^(\d{4})-(\d{2})-(\d{2}) \d{2}:\d{2}:\d{2}$`", $player_pay_time)) {
                showMsg("时间格式不对。请检查", 1, $locations);
            }
            $amount = $this->request->getPost('amount', 'trim');
            if (!preg_match("`^\d+(\.\d{1,2})?$`", $amount)) {
                showMsg("金额不对。请检查", 1, $locations);
            }
//            $order_num = $this->request->getPost('order_num', 'trim');
//            if ($order_num && !preg_match("`^HQH\d{21}$`", $order_num)) {
//                showMsg("订单号不对。请检查", 1, $locations);
//            }

            if (!$order_num = $this->request->getPost('order_num', 'trim')) {
                $order_num = 'SG' . date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
            }


            $trade_type = $this->request->getPost('trade_type', 'intval');
            $fee = $this->request->getPost('fee', 'floatval');
            $remark = $this->request->getPost('remark', 'trim');
            //如果是ATM现存可以得到手续费，其他非网银存款不可知
            if ($trade_type == 5) {
                if ($fee != 0) {
                    showMsg("对于ATM无卡现存，客户并没有花手续费，所以必须填写0", 1, $locations);
                }
                //存款表的fee字段表示的是客户所花的手续费，客户无卡现存并没有付出手续费，所以这里不应减手续费
                //$amount2 -= $fee2;
            }
            if ($fee > 0 && $fee > ceil($amount / 100)) {
                showMsg("手续费太多了，拒绝执行", 1, $locations);
            }

            $data = array(
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'top_id' => $user['top_id'],
                'player_card_name' => $this->request->getPost('player_card_name', 'trim'),
                'player_pay_time' => $player_pay_time,
                'amount' => $amount,
                'fee' => $fee,
                'order_num' => $order_num,
                'trade_type' => $trade_type,
                'deposit_bank_id' => $this->request->getPost('bank_id', 'trim'),
                'deposit_card_id' => $card_id,
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 0, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
                'finish_time' => date('Y-m-d H:i:s'),
                'remark' => $remark,
            );

            //保存补单
            try {
                deposits::addItem($data);
                showMsg("添加存款成功！", 0, $locations);
            } catch (exception2 $e) {
                showMsg("添加存款失败! 错误信息：" . $e->getMessage(), 1);
            }
        }
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到支持的银行列表
        self::$view->setVar('bankList', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到交易类型
        self::$view->setVar('tradeTypes', $GLOBALS['cfg']['tradeTypes']);

        $depositCards = cards::getItems(1);
        $bankDepositCards = array();
        foreach ($depositCards as $v) {
            $bankDepositCards[$v['bank_id']][] = $v;
        }
        self::$view->setVar('bankDepositCards', $bankDepositCards);
        self::$view->render('deposit_adddeposit');
    }

    // 手工存款列表
    public function manualDepositList()
    {
        if ($sa = $this->request->getGet('sa', 'trim')) { // ajax
            switch ($sa) {
                case 'checkNew': // 获得用户的真实姓名
                    $deposits = manualDeposits::getItems('', -1, -1, '', 0);
                    $result = array('newNum' => count($deposits));

                    echo json_encode($result);
                    break;
            }
            exit;
        }

        $username = $this->request->getGet('username', 'trim');
        $depositBankId = $this->request->getGet('depositBankId', 'intval', '-1');
        $depositCardId = $this->request->getGet('depositCardId', 'intval', '-1');
        $startInputDate = $this->request->getGet('startInputDate', 'trim', date("Y-m-d 00:00:00"));
        $endInputDate = $this->request->getGet('endInputDate', 'trim', date("Y-m-d 23:59:59"));
        $inputAdmin = $this->request->getGet('inputAdmin', 'trim');
        $startFinishDate = $this->request->getGet('startFinishDate', 'trim');
        $endFinishDate = $this->request->getGet('endFinishDate', 'trim');
        $finishAdmin = $this->request->getGet('finishAdmin', 'trim');
        $orderNum1 = $this->request->getGet('orderNum1', 'trim');
        $status = $this->request->getGet('status', 'intval', '-1');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $curPage  = $this->request->getGet('curPage', 'intval', 1);//>>snow 获取当前页码值
        $manualDepostsCount = manualDeposits::getItemsCount($username, $depositBankId, $depositCardId, $orderNum1, $status, $inputAdmin, $finishAdmin, $startInputDate, $endInputDate, $startFinishDate, $endFinishDate);
        //>>snow 获取正确页码值
        $curPage = getStartOffset($curPage, $manualDepostsCount['count']);
        $startPos= ($curPage - 1) * DEFAULT_PER_PAGE;
        $manualDeposts = manualDeposits::getItems($username, $depositBankId, $depositCardId, $orderNum1, $status, $inputAdmin, $finishAdmin, $startInputDate, $endInputDate, $startFinishDate, $endFinishDate, $startPos, DEFAULT_PER_PAGE);

        $admins = admins::getItems();

        $cardIds = array();
        foreach ($manualDeposts AS $manualDepost) {
            $cardIds[] = $manualDepost['deposit_card_id'];
        }
        // 获得对应银行的银行卡
        $depositCards = cards::getItemsById($cardIds);
//        dd($manualDeposts,$cardIds,$depositCardId);
        self::$view->setVar('username', $username);
        self::$view->setVar('depositBankId', $depositBankId);
        self::$view->setVar('depositCardId', $depositCardId);
        self::$view->setVar('startInputDate', $startInputDate);
        self::$view->setVar('endInputDate', $endInputDate);
        self::$view->setVar('inputAdmin', $inputAdmin);
        self::$view->setVar('startFinishDate', $startFinishDate);
        self::$view->setVar('endFinishDate', $endFinishDate);
        self::$view->setVar('finishAdmin', $finishAdmin);
        self::$view->setVar('orderNum1', $orderNum1);
        self::$view->setVar('status', $status);
        self::$view->setVar('totalAmount0', $manualDepostsCount['totalAmount0']);
        self::$view->setVar('totalAmount1', $manualDepostsCount['totalAmount1']);

        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到支持的银行列表
        self::$view->setVar('banks', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        self::$view->setVar('depositCards', $depositCards);

        self::$view->setVar('actionLinks', array(0 => array('title' => '新增手工存款', 'url' => url('deposit', 'addManualDeposit'))));
        self::$view->setVar('admins', $admins);
        self::$view->setVar('manualDeposts', $manualDeposts);
        self::$view->setVar('pageList', getPageList($manualDepostsCount['count'], DEFAULT_PER_PAGE));

        self::$view->render('deposit_manualdepositlist');
    }

    // 手工存款详情页
    public function viewManualDeposit()
    {
        $locations = array(0 => array('title' => '手工存款列表', 'url' => url('deposit', 'manualDepositList')));

        $md_id = $this->request->getGet('md_id', 'intval');
        if (!$manualDeposit = manualDeposits::getItem($md_id)) {
            showMsg('错误的手工存款提案ID：' . $md_id, 1, $locations);
        }
        // 获得对应银行的银行卡
        $depositCards = cards::getItem($manualDeposit['deposit_card_id']);

        self::$view->setVar('canAcceptManualDeposit', adminGroups::verifyPriv(array(CONTROLLER, 'acceptManualDeposit')));
        self::$view->setVar('depositCards', $depositCards);
        self::$view->setVar('manualDeposit', $manualDeposit);
        self::$view->render('deposit_viewmanualdeposit');
    }

    // 新增手工存款提案
    public function addManualDeposit()
    {
        $locations = array(0 => array('title' => '继续添加手工存款', 'url' => url('deposit', 'addManualDeposit')));
        // 查询用户账号是否有效
        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'checkUsername': // 获得用户的真实姓名
                    $username = $this->request->getPost('username', 'trim');
                    if (!preg_match('`^[a-zA-Z]\w{3,}$`', $username)) {
                        return array();
                    }

                    $result = array('user_id' => '', 'real_name' => '');
                    if ($user = users::getItem($username)) {
                        $result['user_id'] = $user['user_id'];
                    }

                    echo json_encode($result);
                    break;
            }
            exit;
        }

        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            if (empty($username) || !$user = users::getItem($username)) {
                showMsg("无效的用户名，请检查", 1, $locations);
            }
            $deposit_bank_id = $this->request->getPost('deposit_bank_id', 'intval');
            if ($deposit_bank_id <= 0) {
                showMsg('无效的支付渠道', 1, $locations);
            }
            $card_name_0 = $this->request->getPost('card_name_0', 'trim');
            if ($deposit_bank_id != 203 && $card_name_0 == '') {
                showMsg('无效的汇款户名', 1, $locations);
            }
            $amount_0 = $this->request->getPost('amount_0', 'trim');
            if (!preg_match("`^\d+(\.\d{1,2})?$`", $amount_0)) {
                showMsg("金额不对。请检查", 1, $locations);
            }

            $order_num_0 = $this->request->getPost('order_num_0', 'trim');
            if ($order_num_0 == '') {
                showMsg('无效的流水号', 1, $locations);
            }
            if ($deposit = deposits::getItemByOrderNum($order_num_0, 1, 8)) {
                showMsg('已经有这条充值记录了', 1, $locations);
            }

            $remark_0 = $this->request->getPost('remark_0', 'trim');

            $data = array(
                'user_id' => $user['user_id'],
                'username' => $username,
                'deposit_bank_id' => $deposit_bank_id,
                'amount_0' => $amount_0,
                'card_name_0' => $card_name_0,
                'order_num_0' => $order_num_0,
                'remark_0' => $remark_0,
                'input_admin_id' => $GLOBALS['SESSION']['admin_id'],
                'input_time' => date('Y-m-d H:i:s'),
            );

            if (!manualDeposits::addItem($data)) {
                showMsg("新增提案失败，请检查参数");
            }

            showMsg("新增提案成功", 0, $locations);
        }
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        self::$view->setVar('banks', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
        /********************** author snow 把银行卡列表转换成包含首字母的数组******************************/
        self::$view->render('deposit_addmanualdeposit');
    }

    // 受理存款提案
    public function acceptManualDeposit()
    {
        $md_id = $this->request->getPost('md_id', 'intval');
        $locations = array(0 => array('title' => '处理手工存款提案', 'url' => url('deposit', 'operateManualDeposit', array('md_id' => $md_id))));

        if (!$manualDeposit = manualDeposits::getItem($md_id)) {
            showMsg('错误的手工存款提案ID：' . $md_id, 1, $locations);
        }
        if ($manualDeposit['status'] != 0) {
            showMsg('提案状态已经被改变，无法重新受理', 1, $locations);
        }


        $data = array(
            'status' => '1',
            'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
            'finish_time' => date('Y-m-d H:i:s'),
        );

        if (!manualDeposits::updateItem($md_id, $data)) {
            showMsg('提案受理失败');
        }

        redirect(url('deposit', 'operateManualDeposit', array('md_id' => $md_id)));
        showMsg('提案受理成功', 1, $locations);
    }

    // 处理存款提案
    public function operateManualDeposit()
    {
        $md_id = $this->request->getGet('md_id', 'intval');
        $locations = array(0 => array('title' => '手工存款详细页', 'url' => url('deposit', 'viewManualDeposit', array('md_id' => $md_id))));
        if (!$manualDeposit = manualDeposits::getItem($md_id)) {
            showMsg('错误的手工存款提案ID：' . $md_id, 1, $locations);
        }
        if ($manualDeposit['finish_admin_id'] != $GLOBALS['SESSION']['admin_id']) {
            showMsg('您不是受理人，无法进行此操作', 1, $locations);
        }
        if ($manualDeposit['status'] < 1) {
            showMsg('该提案还未被受理', 1, $locations);
        }
        if ($manualDeposit['status'] >= 4) {
            showMsg('提案状态已经被改变，无法进行操作', 1, $locations);
        }

        if ($sa = $this->request->getGet('sa', 'trim')) {
            $locations_1 = array(0 => array('title' => '处理手工存款提案', 'url' => url('deposit', 'operateManualDeposit', array('md_id' => $md_id))));
            switch ($sa) {
                case 'cancel': // 取消提案
                    $remark_1 = $this->request->getPost('remark_1', 'trim');
                    if ($remark_1 == '') {
                        showMsg('该提案还未被受理', 1, $locations_1);
                    }
                    $data = array(
                        'remark_1' => $remark_1,
                        'status' => '4',
                        'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
                        'finish_time' => date('Y-m-d H:i:s'),
                    );

                    if (!manualDeposits::updateItem($md_id, $data)) {
                        showMsg('拒绝提案失败');
                    }

                    showMsg('拒绝提案成功', 1, $locations);

                    break;
                case 'compare': // 财务新增数据并对比
                    if ($this->request->getPost('submit', 'trim')) {
                        $data = array(
                            'deposit_card_id' => $this->request->getPost('deposit_card_id', 'intval'),
                            'amount_1' => $this->request->getPost('amount_1', 'trim'),
                            'card_name_1' => $this->request->getPost('card_name_1', 'trim'),
                            'order_num_1' => $this->request->getPost('order_num_1', 'trim'),
                            'remark_1' => $this->request->getPost('remark_1', 'trim'),
                            'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
                            'finish_time' => date('Y-m-d H:i:s'),
                        );

                        try {
                            $result = manualDeposits::compareManualDeposit($md_id, $data);

                            if ($result === true) {
                                showMsg("手动存款已成功执行", 0, $locations);
                            } else {
                                showMsg("信息对比不一致，提案自动取消", 0, $locations);
                            }
                        } catch (Exception $e) {
                            $e->getCode() == 2 ? showMsg($e->getMessage(), 1, $locations_1) : showMsg($e->getMessage(), 1, $locations);
                        }
                    }
                    break;
                default:
                    showMsg('错误的地址', 1, $locations);
                    break;
            }
        }

        // 获得银行对应的银行卡
        // $bankId = $manualDeposit['deposit_bank_id'] != 101 ? $manualDeposit['deposit_bank_id'] : 0;
        // $usage = $manualDeposit['deposit_bank_id'] != 101 ? 0 : 1;
        $depositCards = cards::getItems(1, 0, 0, '', 2);

        self::$view->setVar('manualDeposit', $manualDeposit);
        self::$view->setVar('depositCards', $depositCards);
        self::$view->render('deposit_operatemanualdeposit');
    }



    /**
     * snow
     * 导出存款记录  获取总条数
     */
    public function snowExportDepositListGetTotalCount()
    {
        $top_username = $this->request->getGet('top_username', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        $is_test = $this->request->getGet('is_test', 'intval', '0');
        $is_manual = $this->request->getGet('is_manual', 'intval', '-1');
        $trade_type = $this->request->getGet('trade_type', 'trim');
        $deposit_bank_id = $this->request->getGet('deposit_bank_id', 'intval', 0);
        $deposit_card_id = $this->request->getGet('deposit_card_id', 'intval', 0);
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d 00:00:00"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $time_status = $this->request->getGet('time_status', 'intval', 1);
        $startAmount = $this->request->getGet('start_amount', 'intval' );
        $endAmount = $this->request->getGet('end_amount', 'intval');
        $status = $this->request->getGet('status', 'intval', -1);
        $order_num = $this->request->getGet('order_num', 'trim', '');
        if($startAmount >0 && $endAmount>0)
        {
            if($startAmount > $endAmount)
            {
                showMsg("起始金额不能大于结束金额!");
            }
        }
        /************************* snow 添加时间判断  ,提示,只能查询导出一个月之内的数据*******************************************/


        //>>判断开始时间与结束时间,是否在一个月之内.如果不是,取结束时间到之前1个月的数据.
        $result = $this->_toDetermineOfmonth($startDate,$endDate);
        if($result['flag'] === false){
            //>>查询时间超过了一个月
            $data       = [
                'flag' => false,
                'data' => [
                    'error'    => '一次只能导出一个月的数据',
                ]
            ];
            die(json_encode($data));
        }


        /************************* snow 添加时间判断  ,提示,只能查询导出一个月之内的数据*******************************************/
        if ($time_status == 1) {//从创建时间查
            $create_stime = $startDate;
            $create_etime = $endDate;
            $finish_stime = $finish_etime = '';
        } elseif ($time_status == 2) {//从完成处理时间查
            $finish_stime = $startDate;
            $finish_etime = $endDate;
            $create_stime = $create_etime = '';
        }


        //>>先获取总条数
        $NumberInfo = deposits::snow_getNewmaxNumbers($top_username ? $top_username : $username, $include_childs, $is_test, $is_manual, $status, $trade_type, $deposit_bank_id, $deposit_card_id, $startAmount, $endAmount, $order_num, $finish_stime, $finish_etime, $create_stime, $create_etime);

            $maxNumber  = $NumberInfo['count'];
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
                    'where'         => base64_encode($NumberInfo['where']),
                    'fileName'      => '存款记录-' . date('YmdHis',strtotime($startDate)) . '至' . date('YmdHis', strtotime($endDate) ). '-' . time()
                ]
            ];

            die(json_encode($data));


    }

    /**
     * snow
     * 导出存款记录  获取数据
     */
    public function snowExportDepositListGetData()
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
            $headlist=['提案id','用户id','用户名','类型','卡户名','金额','收款银行','收款卡','网站订单','支付订单','客户发起时间','完成时间','时间差','真实存款姓名微信号或其他账号','备注','状态',];
            //输出Excel列名信息
            foreach ($headlist as $key => $value) {
                //CSV的Excel支持GBK编码，一定要转换，否则乱码
                $headlist[$key] = iconv('utf-8', 'gbk', $value);
            }
            //将数据通过fputcsv写到文件句柄
            if(fputcsv($fp, $headlist) === false){
                fclose($fp);
                die(json_encode(['flag' => false, 'data' =>['error' => '首行数据出错' ] ]));
            };
            unset($headlist);
        }

        //得到收款卡列表
        $depositCards   = cards::getItemsWithDeletedCard(1);
        $data           = deposits::snow_getDepositsAdmin($startPos, $pageSize, $where);
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {

            $row = $data[$i];
            $row = $this->_exportDepositsListToCsv($row, $GLOBALS['cfg']['bankList'],$depositCards);

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
    public function snowExportDepositList()
    {
        $top_username = $this->request->getGet('top_username', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $include_childs = $this->request->getGet('include_childs', 'intval', 0);
        $is_test = $this->request->getGet('is_test', 'intval', '0');
        $is_manual = $this->request->getGet('is_manual', 'intval', '-1');
        $trade_type = $this->request->getGet('trade_type', 'trim');
        $deposit_bank_id = $this->request->getGet('deposit_bank_id', 'intval', 0);
        $deposit_card_id = $this->request->getGet('deposit_card_id', 'intval', 0);
        $startDate = $this->request->getGet('startDate', 'trim', date("Y-m-d 00:00:00"));
        $endDate = $this->request->getGet('endDate', 'trim', date("Y-m-d 23:59:59"));
        $time_status = $this->request->getGet('time_status', 'intval', 1);
        //$startAmount = $this->request->getGet('startAmount', 'intval', 1);
        $startAmount = $this->request->getGet('start_amount', 'intval' );
        $endAmount = $this->request->getGet('end_amount', 'intval');
        $status = $this->request->getGet('status', 'intval', -1);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取当前页码值
        $order_num = $this->request->getGet('order_num', 'trim', '');
        if($startAmount >0 && $endAmount>0)
        {
            if($startAmount > $endAmount)
            {
                showMsg("起始金额不能大于结束金额!");
            }
        }
        /************************* snow 添加时间判断  ,提示,只能查询导出一个月之内的数据*******************************************/
        $export_excel_status = $this->request->getGet('export_deposit_list', 'intval');
        if($export_excel_status === 1){
            $startPos = -1;   //>>等于-1  导出全部数据
            //>>判断开始时间与结束时间,是否在一个月之内.如果不是,取结束时间到之前1个月的数据.
            $result = $this->_toDetermineOfmonth($startDate,$endDate);
            if($result['flag'] === false){
                //>>查询时间超过了一个月
                showMsg('查询时间超过了一个月');
            }

        }
        /************************* snow 添加时间判断  ,提示,只能查询导出一个月之内的数据*******************************************/
        if ($time_status == 1) {//从创建时间查
            $create_stime = $startDate;
            $create_etime = $endDate;
            $finish_stime = $finish_etime = '';
        } elseif ($time_status == 2) {//从完成处理时间查
            $finish_stime = $startDate;
            $finish_etime = $endDate;
            $create_stime = $create_etime = '';
        }


        //>>先获取总条数
        $NumberInfo = deposits::snow_getmaxNumbers($top_username ? $top_username : $username, $include_childs, $is_test, $is_manual, $status, $trade_type, $deposit_bank_id, $deposit_card_id, $startAmount, $endAmount, $order_num, $finish_stime, $finish_etime, $create_stime, $create_etime);
        $maxNumber  = $NumberInfo['count'];
        //>>获取正确页码值
        $pageSize   = 10000;
        $startPos   = getStartOffset($curPage, $maxNumber, $pageSize);

        //得到收款卡列表
        $depositCards = cards::getItemsWithDeletedCard(1);

        $bankDepositCards = array();
        foreach ($depositCards as $v) {
            $bankDepositCards[$v['bank_id']][] = $v;
        }
            $page       = ceil($maxNumber / $pageSize);
            //>>设置脚本执行时间为1分钟
            set_time_limit(60);
            ini_set('memory_limit', '512M');
            $fileName = '存款记录-' . date('YmdHis',strtotime($startDate)) . '至' . date('YmdHis', strtotime($endDate));
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
            header('Cache-Control: max-age=0');

            //打开PHP文件句柄,php://output 表示直接输出到浏览器
            $fp = fopen('php://output', 'a');
            //>>导出表格的字段名称
            $headlist=['提案id','用户id','用户名','类型','卡户名','金额','收款银行','收款卡','网站订单','支付订单','客户发起时间','完成时间','时间差','真实存款姓名微信号或其他账号','备注','状态',];
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
            for($k = 0; $k < $page; ++$k){
                $startPos = $k * 20000;
                //逐行取出数据，不浪费内存
                $data = deposits::snow_getDepositsAdmin( $startPos, 20000);
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
                    $row = $this->_exportDepositsListToCsv($row, $GLOBALS['cfg']['bankList'],$depositCards);

                    foreach ($row as $key => $value) {
                        $value      = $value === '' ? '空' : $value;
                        $row[$key]  = iconv('utf-8', 'GBK//IGNORE', $value);
                    }

                    fputcsv($fp, $row);
                }

                unset($data);

            }


        }

    /**
     * snow  处理需要导出的数据
     * @param $val
     * @param $bankList
     * @param $depositCards
     * @return array
     */
    private function _exportDepositsListToCsv($val, $bankList, $depositCards)
    {
        //>>状态
        $status = [
            '0' => '未处理',
            '1' => '已受理',
            '2' => '已审核',
            '8' => '已执行',
            '9' => '取消提案',
        ];


        //>>按照$title的顺序组装数据
       return [
            $val['deposit_id'],
            $val['user_id'],
            $val['username'] . (isset($val['type']) && $val['type'] == 1 ? '[推广]' : '') . ($val['is_test'] ? '[测试]' : '') . ($val['user_status'] == 0 ? '[已删除]' : ($val['user_status'] == 1 ? '[已冻结' : ($val['user_status'] == 5 ? '[已回收]' : '')))  ,
            $val['level'] == 0 ? '总代' : ($val['level'] == 100 ? '会员' : $val['level'] . '代'),
            mb_substr($val['player_card_name'], 0, mb_strlen($val['player_card_name'], 'utf-8') - 1, 'utf-8') . '*',
            $val['amount'],
            isset($bankList[$val['deposit_bank_id']]) ? $bankList[$val['deposit_bank_id']] : '',
            isset($depositCards[$val['deposit_card_id']]['card_name']) ? $depositCards[$val['deposit_card_id']]['card_name'] : '',
            $val['shop_order_num'],
            $val['order_num'],
            $val['create_time'],
            $val['finish_time'],
            $val['finish_time'] !== '0000-00-00 00:00:00' ? ((int)strtotime($val['finish_time']) - (int)strtotime($val['create_time'])) . '秒' : '未完成',
            $val['real_name'] ,
            $val['remark'] ,
            isset($status[$val['status']]) ?  $status[$val['status']] : '',

        ];
    }
/********************** snow 批量删除存款记录*********************************************/
    public function deleteDepositMany()
    {
        //>>批量删除存款记录

        $ids  = $this->request->getPost('ids', 'array');

        //>>对数据进行验证
       if(!empty($ids)){
           foreach($ids as $key => $val){
               if(is_numeric($val)){
                   $ids[$key] = (int)$val;
               }else{
                   unset($ids[$key]);
               }

           }
           if(!empty($ids)){
               $whereIn = '(' . implode(',', $ids) . ')';
               //>>删除数据
               $sql = 'DELETE FROM `deposits` WHERE deposit_id IN' . $whereIn;
               $result = $GLOBALS['db']->query($sql,[],'d');
               if($result && $result > 0){
                   //>>删除成功
                   die(json_encode(['flag' => true, 'data' => ['count' => $result]]));
               }
           }
       }
        die(json_encode(['flag' => false, 'data' => ['error' => '删除失败']]));
    }
    /********************** snow 批量删除存款记录*********************************************/
}

?>