<?php

use common\model\onlineUser;

/**
 * 控制器：用户管理
 *
 * 注：“用户”指代理和会员的统称
 */
class userController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'showBalance' => '查看用户余额',
        'showBalanceBatch' => '批量查看用户余额',
        'userList' => '团队列表',
        'userList1' => '用户列表',
        'logList' => '用户日志',
        'msgList' => '消息列表',
        'sendMsg' => '发送消息',
        'viewMsg' => '消息详情',
        'pushMsg' => '消息推送',
        'bindCardList' => '绑定卡列表',
        'viewUser' => '查看用户资料',
        'manualUpdateBalance' => '手工增余额',
        'manualUpdateBalanceBatch' => '手工批量增余额',
        'manualUpdateBalance1' => '手工减余额',
        'deleteCard' => '删除绑定卡',
        'main' => '用户列表',
        'userTree' => '用户孩子树',
        'userTreeAllocCard' => '用户孩子树_分卡模型',
        'getChild' => '得到某一级孩子',
        'addTop' => '增加总代',
        'editUser' => '修改用户资料',
        'editRealName' => '修改用户姓名',
        'editRebate' => '修改返点',
        'editQuota' => '修改配额',
        'editPwd' => '修改密码',
        'freezeUser' => '冻结用户',
        'unFreezeUser' => '解冻用户',
        'recycleList' => '回收帐号列表',
        'processRecycle' => '回收',
        'deleteUser' => '删除用户',
        'deleteMsg' => '删除消息',
        'setTopRebate' => '设置总代返点',
        'saveTopRebate' => '保存总代返点',
        'setPrizeMode' => '设置奖金模式',
        'batchSendMessage' => '批量发消息',
        'transferLevel' => '层级互转',
        'eventList' => '用户事件列表',
        'addBalanceAdjust' => '新增调额申请',
        'balanceAdjustList' => '调额列表',
        'executeBalanceAdjust' => '执行调额申请',
        'cancelBalanceAdjust' => '取消调额申请',
        'levelAdjustList' => '调级管理',
        'addLevelAdjust' => '新增提案',
        'cancelLevelAdjust' => '取消提案',
        'addManualBalanceToTest' => '给测试账号添加额度',
        'editBindCard' => '编辑银行卡',
        'editBindCardRealName' => '编辑用户银行卡真实姓名',
        'editKillMmc' => '修改用户秒秒彩杀率',
        'editGroup' => '修改层级',
        'setTestUser' => '设置测试用户',
        'editRemark' => '修改备注',
        'exportUserData' => '批量导出会员数据',
        'transferAgent' => '会员转移代理',
    );

    /**
     * 批量导出用户数据
     * auther: the rock
     */
    public function exportUserData(){
        if($GLOBALS['SESSION']['admin_group_id']!=1)die ('当前用户无权限访问此页面!');// 只有全权限组可以有导出会员数据功能
        $sql="SELECT a.username,b.username as parent_name,
            CASE
            WHEN a.level=100 THEN '会员'
            WHEN a.level =0 THEN '总代理'
            ELSE CONCAT(a.level, '级代理') END AS `type`,
            a.balance,a.reg_time,a.reg_ip,a.qq,a.mobile
            FROM users AS a
            LEFT JOIN users AS b ON a.parent_id=b.user_id
            WHERE a.status=8 AND a.is_test=0 ORDER BY a.username ASC , a.level ASC";
        $excelArrray = $GLOBALS['db']->getAll($sql, array());
        $excelFile = "会员信息";
        if (!$excelArrray) die('数据为空,无法导出!');
        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel.php';
        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel2007.php';

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator($GLOBALS['SESSION']['admin_username'])->setLastModifiedBy($GLOBALS['SESSION']['admin_username']);
        $title=['用户名','用户上级','类型','余额','注册时间','注册IP','QQ号码','手机号码'];
        foreach ($title as $key => $value) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(PHPExcel_Cell::stringFromColumnIndex($key) . '1', $value);
            in_array($key,[4,5])?$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($key))->setWidth(20):$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($key))->setWidth(15);
        }
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        for ($index = 0; $index < count($excelArrray); $index++) {
            $col = 0;
            foreach ($excelArrray[$index] as $key => $value) {
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
        $objPHPExcel->getActiveSheet()->setTitle($excelFile);
        $objPHPExcel->setActiveSheetIndex(0);
        header($contentType);
        header('Content-Disposition: attachment;filename="' . $excelFile . $endfix . '"');
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
     * 会员转移代理
     */
    public function transferAgent(){
        $locations = array(0 => array('title' => '会员转移代理', 'url' => url('user', 'transferAgent')));
        //>>ajax
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $info=[];
            if($this->request->getPost('type', 'trim')=='chk_u'){
                $username = $this->request->getPost('username', 'trim');
                /************************ snow 如果用户总代为dcsite 进行转移 start********************************************/
//                $sql = 'SELECT `user_id`,a.`status`,`level`,a.`parent_id` FROM users WHERE username = :username' ;
                $sql='SELECT a.`user_id`,b.username AS top_name,a.level, a.`status`,
a.`parent_id` FROM users AS a, users AS b WHERE a.username = :username AND a.status=8 AND a.top_id = b.user_id';
                /************************ snow 如果用户总代为dcsite 进行转移 end********************************************/
                $paramArr[':username'] = $username;
                $row=$GLOBALS['db']->getRow($sql,$paramArr);
                if(empty($row)){
                    echo json_encode(['status'=>0,'msg'=>'该用户不存在,无法转移!','info'=>[]]);
                    return false;
                }
                if($row['level']!=100){
                    echo json_encode(['status'=>0,'msg'=>'该用户已是代理,无法转移!','info'=>[]]);
                    return false;
                }

                /************************ snow 如果用户总代为dcsite 进行转移 start********************************************/
                if ($row['top_name'] == 'dcsite')
                {
                    //>>禁止dcsite 总代下的用户转移到其它地方
                    echo json_encode(['status'=>0,'msg'=>'禁止dcsite 总代下的用户转移到其它代理名下!','info'=>[]]);
                    exit;
                }
                /************************ snow 如果用户总代为dcsite 进行转移 end********************************************/
                $status=$row['status'];
                switch ($row['status']){
                    case 8:
                        //>>获取所有代理列表
                        $sql = 'SELECT `user_id`,`username`,`level` FROM users WHERE `user_id`!='.$row["parent_id"].' AND `status` = 8 AND `level` between 0 and 99 ORDER BY `level` ASC,`username` ASC';
                        $rows=$GLOBALS['db']->getAll($sql,[]);
                        if(empty($rows)){
                            $msg='当前不存在代理,无法转移!';
                            $status=-1;
                            break;
                        }
                        $info=['show'=>$rows,'uid'=>$row['user_id']];
                        $msg='该用户ok!';
                        break;
                    case 5:
                        $msg='该用户已被回收!无法转移!';
                        break;
                    case 1:
                        $msg='该用户已被冻结!无法转移!';
                        break;
                    default:
                        $msg='该用户不存在!无法转移!';
                        break;
                }
                echo json_encode(['status'=>$status,'msg'=>$msg,'info'=>$info]);
                exit;
            }elseif($this->request->getPost('type', 'trim')=='chk_p'){
                $uid=$this->request->getPost('uid', 'trim');
                $username=$this->request->getPost('uname', 'trim');
                $pid=$this->request->getPost('pid', 'trim');
                //>>根据uid和uname判断用户是否存在
                $sql='SELECT `user_id` FROM users WHERE username = :username AND user_id=:user_id AND status=8';
                $paramArr=[':user_id'=>$uid,':username'=>$username];
                if(empty($GLOBALS['db']->getRow($sql,$paramArr))){
                    echo json_encode(['status'=>0,'msg'=>'该用户不存在!无法转移!','info'=>$info]);
                    return false;
                }
                //>>判断返点大小 1时时彩 2十一选五 3快三 4扑克 5低频 6PK拾
                $sql="SELECT user_id,property_id, rebate,CASE WHEN property_id=1 THEN '时时彩' WHEN property_id =2 THEN '十一选五' WHEN property_id =3 THEN '快三' WHEN property_id =4 THEN '扑克' WHEN property_id =5 THEN '低频' WHEN property_id =6 THEN 'PK拾' END AS `name` FROM user_rebates WHERE user_id in (:uid,:pid) AND status=1 ORDER BY property_id,user_id";
                $paramArr1=[':uid'=>$uid,':pid'=>$pid];
                $res=$GLOBALS['db']->getAll($sql,$paramArr1);
                if(!empty($res)){
                    $property_ids=array_unique(array_column($res,'property_id'));
                    $re_arr=[];
                    foreach ($property_ids as $key){
                        foreach($res as $val){
                            if($key==$val['property_id']){
                                $re_arr[$key][]=$val;
                            }
                        }
                    }
                    foreach($re_arr as $arr){
                        if($arr[0]['user_id']==$uid){
                            if(count($arr)==1 || $arr[0]['rebate']>$arr[1]['rebate']) {
                                echo json_encode(['status' => -3, 'msg' => "转移代理的" . $arr[0]['name'] . "返点低于当前用户无法转移", 'info' => []]);
                                return false;
                            }
                        }else{
                            if(count($arr)==2 && $arr[0]['rebate']<$arr[1]['rebate']) {
                                echo json_encode(['status'=>-3,'msg'=>"转移代理的".$arr[0]['name']."返点低于当前用户无法转移",'info'=>[]]);
                                return false;
                            }
                        }
                    }
                    echo json_encode(['status'=>1,'msg'=>"转移代理ok",'info'=>[]]);
                    return true;
                }
            }else{
                $status=-2;
                $msg='请求方式错误!';
                echo json_encode(['status'=>$status,'msg'=>$msg,'info'=>$info]);
                return false;
            }
        //>>post
        }elseif( isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST')){
            $uid=$this->request->getPost('uid', 'trim');
            $username=$this->request->getPost('username', 'trim');
            $parent_id=$this->request->getPost('parent_id', 'trim');
//            $sql='SELECT `user_id` FROM users WHERE username = :username AND user_id=:user_id AND status=8';
            $sql='SELECT a.`user_id`,b.username FROM users AS a, users AS b WHERE a.username = :username AND a.user_id=:user_id AND a.status=8 AND a.top_id = b.user_id';
            $paramArr[':user_id'] = $uid;
            $paramArr[':username'] = $username;
            if(empty($GLOBALS['db']->getRow($sql,$paramArr))){
                showMsg("该用户不存在!无法转移!", 1, $locations);
            }
            $sql_1='SELECT `top_id`, username, `status`,`parent_tree` FROM users WHERE user_id=:parent_id';
            $paramArr1[':parent_id'] = $parent_id;
            $parent=$GLOBALS['db']->getRow($sql_1,$paramArr1);//0已删除 1冻结 5已回收 8正常

            /**************************** snow 可以进行代理转移的过期时间******************************************/

            if (trim($parent['username']) == 'dcsite')
            {
                //>>如果转移代理为dcsite 设定一个时间限制 ,超过这个时间就不可以进行转移
                if (date('Y-m-d') > DCSITE_TRANSFER_DATE)
                {
                   showMsg('超过了可以转移到dcsite总代的规定时间', 1, $locations) ;
                }
            }

            /**************************** snow 可以进行代理转移的过期时间******************************************/
            if(empty($parent) || $parent['status']==0){
                showMsg("转移代理不存在!无法转移!", 1, $locations);
            }elseif($parent['status']==1){
                showMsg("转移代理被冻结!无法转移!", 1, $locations);
            }elseif($parent['status']==5){
                showMsg("转移代理已回收!无法转移!", 1, $locations);
            }


            $sql_2 = "UPDATE users SET top_id = {$parent['top_id']} ,parent_id=:parent_id,parent_tree=:parent_tree  WHERE user_id =:user_id";
            $paramArr2[':parent_id'] = $parent_id;
            if(empty($parent['parent_tree'])){
                $paramArr2[':parent_tree'] = $parent_id;
            }else{
                $paramArr2[':parent_tree'] = $parent['parent_tree'].','.$parent_id;
            }
            //>>author snow 下面这行,多多余,导出parent_tree 出错.
//            $paramArr2[':parent_tree'] = $parent_id;
            $paramArr2[':user_id'] = $uid;
            $res = $GLOBALS['db']->query($sql_2, $paramArr2, 'u');
            if (!$res) {
                $GLOBALS['db']->rollback();
                throw new exception2("转移代理失败", 500);
            }
            /************************* snow 代理转移成功后,刷新相应缓存 start********************************************/
            //>>刷新获取用户上级层级关系
            $this->_deleteUserParentCache($uid);
            /************************* snow 代理转移成功后,刷新相应缓存 end**********************************************/
            showMsg("执行成功", 0, $locations);
        }else{
            self::$view->render('user_transferagent');
        }
    }


    /**
     * 代理转移成功后,刷新相应缓存
     * @param $user_id int  用户id
     * @return bool
     */
    private function _deleteUserParentCache($user_id)
    {
        //>>刷新获取用户上级层级关系
        $prev_cache_key  = 'getAllParent' . '_' . $user_id;
        $class     = 'users';
        //>>删除memcache 缓存
        return $GLOBALS['mc']->delete($class,$prev_cache_key);

    }

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    /*
     * 查询用户余额  传入用户ID或用户名
     * 返回JSON 格式：user_id: 和余额   如用户不存在user_id为空
     * by kk   2013-04-09
     */
    public function showBalance()
    {
        //传user_id或username均可
        $username = $this->request->getPost('username', 'trim');
        if (!is_numeric($username) && !preg_match('`^[a-zA-Z]\w{3,}$`', $username)) {
            return array();
        }

        $result = array('user_id' => '', 'balance' => -1);
        if ($user = users::getItem($username)) {
            $result['balance'] = $user['balance'];
            $result['user_id'] = $user['user_id'];
        }

        echo json_encode($result);
        exit;
    }

    /*
    * 查询用户余额  传入用户ID或用户名
    * 返回JSON 格式：user_id: 和余额   如用户不存在user_id为空
    * by kk   2013-04-09
    */
    public function showBalanceBatch()
    {
        //传user_id或username均可
        $username = $this->request->getPost('username', 'trim');
        $username = preg_replace("/(\n)|(\s)|(\t)|(\')|(')|[\.,\。,\—,\-,\?,\？,\<,\《,\>,》,\|,\|,，,@,$,%,^,&,*,+,=,￥,\||]/", ',', $username);
        $usernameArr = explode(',', $username);
        $where = ' ';
        if (count($usernameArr) > 1) {
            foreach ($usernameArr as $k => $v) {
                if (!empty($v)) {
                    $where .= "  username = '{$v}' or ";
                }
            }
            $where = rtrim($where, ' or');
        } else {
            $where .= " username = '{$usernameArr[0]}'";
        }
        $userList = users::getListByCond($where, 'user_id,username,balance');
        if (empty($userList)) {
            echo json_encode(['datas' => $userList, 'err' => 'all']);
        } else {
            $listArrTmp = [];
            foreach ($userList as $k => $v) {
                $listArrTmp[] = $v['username'];
            }
            $errArrTmp = array_diff($usernameArr, $listArrTmp);
            sort($errArrTmp);
            echo json_encode(['datas' => $userList, 'err' => $errArrTmp]);

        }


        exit;
    }

    //团队列表
    public function userList()
    {

        //>>默认值应该为-1
        $parent_id = $this->request->getGet('parent_id', 'intval', -1);
        $this->_userList($parent_id);
    }

    //用户列表
    public function userList1()
    {
        $parent_id = $this->request->getGet('parent_id', 'intval', -1);
        $this->_userList($parent_id);
    }

    private function _userList($parent_id)
    {
        // 头部功能按钮
        $locations = [
            ['title' => '批量发送信息', 'url' => url('user', 'batchSendMessage')],
//            ['title' => '给测试账号添加额度', 'url' => url('user', 'addManualBalanceToTest')],
        ];
        // 全权限组增加导出会员数据功能
        if ($GLOBALS['SESSION']['admin_group_id'] == 1) $locations[] = ['title' => '批量导出会员数据', 'url' => url('user', 'exportUserData')];

        self::$view->setVar('actionLinks', $locations);

        $recursive = $this->request->getGet('recursive', 'intval', 0);
        $startDate = $this->request->getGet('startDate', 'trim', '2017-05-01 00:00:00');
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d 23:59:59'));
        $startDeposit = $this->request->getGet('startDeposit', 'trim', date('Y-m-d 00:00:00'));
        $endDeposit = $this->request->getGet('endDeposit', 'trim', date('Y-m-d 23:59:59'));
        $lastStartDate = $this->request->getGet('lastStartDate', 'trim');
        $lastEndDate = $this->request->getGet('lastEndDate', 'trim');
        $username = $this->request->getGet('username', 'trim');
        $reg_ip = $this->request->getGet('reg_ip', 'trim');
        $real_name = $this->request->getGet('real_name', 'trim');
        $mobile = $this->request->getGet('mobile', 'trim');
        $qq = $this->request->getGet('qq', 'trim');
        $email = $this->request->getGet('email', 'trim');
        $has_deposited = $this->request->getGet('has_deposited', 'intval', '-1');
        $status = $this->request->getGet('status', 'intval', '-1');
        $online = $this->request->getGet('online', 'intval', '-1');
        $is_test = $this->request->getGet('is_test', 'intval', '-1');
        $depositAmount = $this->request->getGet('depositAmount', 'intval', 0);
//        $offset = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $curPage = $this->request->getGet('curPage', 'intval', 1); //>>snow 获取当前页码值
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        $sort = $this->request->getGet('sort', 'intval', '0');
        $orderBy=[];
        if($sort==0)$sort=config::getConfig('user_sort',1);
        switch($sort){
            case 1:
                $orderBy['reg_time']=1;
                break;
            case 2:
                $orderBy['reg_time']=-1;
                break;
            case 3:
                $orderBy['level']=-1;
                break;
            case 4:
                $orderBy['level']=1;
                break;
        }
        $orderBy = $sortKey && $sortDirection ? [$sortKey => $sortDirection] : $orderBy;

        # TODO: 这下面的查询逻辑其实有问题,但是要抓紧时间修改BUG,所以采用一个节约时间的折中做法,SQL可以后面优化
        # TODO: 就算是打死我,我也不做这个优化.
        # 分页并非直接筛选,而是先查出全部数据,再做筛选.   :D 比❤
        # 来给个思路吧,要修改的话是这样的
        # 如果有在线状态的筛选,就把在线用户放到userId筛选中
        # 然后充值金额也放到连表中
        # 然后统计和列表SQL统一一下
        $usersNumber = 0;
        $countOnline = 0;
        $totalCount = 0;
        # 筛选在线状态可以在这个查询里面写
        # 在线就是user_id IN   不在线就是 user_id NOT IN
        $users = users::getItemsIncludeParentByLike($parent_id, true, $recursive, array(), $status, $is_test, $startDate, $endDate, $lastStartDate, $lastEndDate, $username, $reg_ip, $real_name, $mobile, $qq, $email, $has_deposited, $orderBy);
        if (!empty($users)) {
            // 1.筛选在线状态
            $onlineUserIdList = (new onlineUser())->getOnline();
            $totalCount = count($onlineUserIdList);
            foreach ($users as $userId => &$user) {
                $isOnline = in_array($user['user_id'], $onlineUserIdList);
                if (
                    ($online > 0 && !$isOnline) ||
                    ($online === 0 && $isOnline)
                ) {
                    unset($users[$userId]);
                }

                $user['online'] = $isOnline;
            }
            reset($users);

            if ($users) {
                // 2.筛选充值金额
                $userIds = array_keys($users);
                $usersDeposits  = deposits::getUsersDeposits($userIds, $startDeposit, $endDeposit);
                /**************** author snow 添加提款数据*********************/
                $usersWithdraws = withdraws::getUsersWithdraws($userIds, $startDeposit, $endDeposit);
                /**************** author snow 添加提款数据*********************/
                unset($userIds);
                foreach ($users as $userId => &$user) {

                    if ($usersDeposits[$userId]['total_deposit'] >= $depositAmount) {
                        $user['deposit'] = $usersDeposits[$userId]['total_deposit'];
                        $user['withdraw']= $usersWithdraws[$userId]['total_withdraw'];
                    } else {
                        unset($users[$userId]);
                    }
                }
            }

            // 3.统计筛选后的在线人数,并非总在线.
            array_walk($users, function (&$item) use (&$countOnline) {
                $item['online'] && ++$countOnline;
            });

            // 4.这里居然是分页 = =
            $usersNumber = count($users);
            //>>获取正确起始页 snow
            $offset = getStartOffset($curPage, $usersNumber);
            $users = array_slice($users, $offset, DEFAULT_PER_PAGE, true);
        }
        //设置查询值
        self::$view->setVar('depositAmount', $depositAmount);
        //得到所有总代
        // $topUsers = users::getUserTree(0);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        /***************************** author snow 修改少查数据库***********************************************/

        self::$view->setVar([
            'topUsers'      => $topUsers,
            'parent_id'     => $parent_id,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'startDeposit'  => $startDeposit,
            'endDeposit'    => $endDeposit,
            'lastStartDate' => $lastStartDate,
            'lastEndDate'   => $lastEndDate,
            'has_deposited' => $has_deposited,
            'status'        => $status,
            'online'        => $online,
            'sort'          => $sort,
            'is_test'       => $is_test,
            'username'      => $username,
            'reg_ip'        => $reg_ip,
            'real_name'     => $real_name,
            'mobile'        => $mobile,
            'qq'            => $qq,
            'email'         => $email,
            'reqUri'        => $_SERVER['REQUEST_URI'],
            'sortDirection' => $sortDirection,
            'sortKey'       => $sortKey,
            'finalUsers'    => $users,
            'countOnline'   => $countOnline,
            'totalCount'    => $totalCount,

        ]);
  //>>需要验证权限的路由 主要用于是否显示
        $tmpData = [
             'canKillMmc'           => [CONTROLLER,'editKillMmc'],
             'canViewUser'          => [CONTROLLER,'viewUser'],
             'canFreezeUser'        => [CONTROLLER,'freezeUser'],
             'canUnfreezeUser'      => [CONTROLLER,'unFreezeUser'],
             'canEditUser'          => [CONTROLLER,'editUser'],
             'canEditRealName'      => [CONTROLLER,'editRealName'],
             'canEditRebate'        => [CONTROLLER,'editRebate'],
             'canEditQuota'         => [CONTROLLER,'editQuota'],
             'canEditPwd'           => [CONTROLLER,'editPwd'],
             'canDeleteUser'        => [CONTROLLER,'deleteUser'],
        ];

        //>>author snow 把多次查询修改为只查询一次,而且 只是获取权限而已.
        self::$view->setVar(admins::getAdminPermission($tmpData));
        /***************************** author snow 修改少查数据库***********************************************/
        self::$view->setVar('pageList', getPageList($usersNumber, DEFAULT_PER_PAGE));
//        self::$view->setVar('actionLinks', array(0 => array('title' => '增加用户', 'url' => url('user', 'addUser'))));
        self::$view->render('user_userlist');
    }

    public function logList()
    {
        $username = $this->request->getGet('username', 'trim');
        $control = $this->request->getGet('control', 'intval');
        $action = $this->request->getGet('action', 'trim');
        $ip = $this->request->getGet('ip', 'trim');
        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d'));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d', strtotime('+1 day')));
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $userLogsNumber = userLogs::getItemsNumber($username, $action, $ip, $startDate, $endDate);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $userLogsNumber);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/

        $userLogs = userLogs::getItems($username, $action, $ip, $startDate, $endDate, NULL, $startPos, DEFAULT_PER_PAGE);
        //得到代理列表
        self::$view->setVar('allMenus', userMenus::getAllCatMenus());

        self::$view->setVar('username', $username);
        self::$view->setVar('control', $control);
        self::$view->setVar('action', $action);
        self::$view->setVar('ip', $ip);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('userLogs', $userLogs);
        self::$view->setVar('pageList', getPageList($userLogsNumber, DEFAULT_PER_PAGE));
        self::$view->render('user_loglist');
    }

    /*
     * 用户消息列表
     * by KK 2013-04-03
     */
    public function msgList()
    {
        $username = $this->request->getGet('username', 'trim');
        $startDate = $this->request->getGet('startDate', 'trim',date('Y-m-d 00:00:00'));
        $endDate = $this->request->getGet('endDate', 'trim',date('Y-m-d 23:59:59'));
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        if ($username != '') {
            //如果是search  取得用户ID
            $searchUser = users::getItem($username);
            if (!empty($searchUser['user_id'])) {
                $user_id = $searchUser['user_id'];
            } else {
                //没有找到这个用户
                $user_id = -2;
            }
        } else {
            $user_id = 0;
        }

        //得到消息列表
        $userMsgNumber = messages::getItemsNumber($user_id, 1, $startDate, $endDate);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $userMsgNumber);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $userMsgs = messages::getItems($user_id, 1, $startDate, $endDate, $startPos, DEFAULT_PER_PAGE);
        $tmp = array_keys(array_spec_key($userMsgs, 'from_user_id'));

        $users = users::getItemsById($tmp); //得到用户名的数组

        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteMsg')));
        self::$view->setVar('canView', adminGroups::verifyPriv(array(CONTROLLER, 'viewMsg')));
        self::$view->setVar('canPush', adminGroups::verifyPriv(array(CONTROLLER, 'pushMsg')));
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('users', $users);
        self::$view->setVar('username', $username);
        self::$view->setVar('userMsgs', $userMsgs);
        self::$view->setVar('pageList', getPageList($userMsgNumber, DEFAULT_PER_PAGE));
        self::$view->render('user_msglist');
    }
    public function pushMsg(){
        $msg_id=$this->request->getGet('msg_id','intval',0);
        $msg=M('msgs')->where(['msg_id'=>$msg_id])->find();
        if(empty($msg))showMsg('该消息不存在');
        $title=mb_strlen($msg['title'])>20?mb_substr($msg['title'],0,20).'...':$msg['title'];
        $content=mb_strlen($msg['content'])>100?mb_substr($msg['content'],0,100).'...':$msg['content'];
        $msg_targets=M('msgTargets')->field('to_user_id')->where(['status'=>1,'msg_id'=>$msg_id,'has_read'=>0])->select();
        if(empty($msg_targets))showMsg('此消息无未读用户,无法推送');
        $user_ids=array_column($msg_targets,'to_user_id');
        $i=1;
        $item=0;
        $alias=[];
        foreach ($user_ids as $uid){
            $alias[$item][]='u_'.$uid;
            if($i%1000==0)$item++;
            $i++;
        }
        $res_msg='';
        foreach ($alias as $alias_item){
            $res=$this->pushUsers($title, $content,$alias_item,['type'=>'3','msg_id'=>$msg_id],1);
            if($res==false){
                $res_msg.=" $res";
            }
        }
        if(!empty($res_msg))showMsg('推送失败'.$res_msg);
        showMsg('推送成功');
    }
    /**
     * 查看消息详情
     */
    public function viewMsg()
    {
        $locations = array(0 => array('title' => '消息列表', 'url' => url('user', 'msgList')));
        if (!$msg_id = $this->request->getGet('msg_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }
        if (!$message = messages::getItem($msg_id)) {
            showMsg('该消息不存在');
        }
        $user = users::getItem($message['from_user_id']);

        $getTarget = messages::getAlltargets($msg_id);
        $usernameArr = array();
        foreach ($getTarget as $k => $v) {
            if ($v['to_user_id'] == 0) {
                $usernameArr[] = '系统管理员';
            } else {
                $usernameArr[] = $v['username'];
            }
        }
        $toUserName = implode(",", $usernameArr);

        self::$view->setVar('message', $message);
        self::$view->setVar('user', $user);
        self::$view->setVar('toUserName', $toUserName);
        self::$view->render('user_viewmsg');
    }

    /*
     * 删除消息
     * 2013-04-04
     */
    public function deleteMsg()
    {
        $locations = array(0 => array('title' => '返回消息列表', 'url' => url('user', 'msgList')));
        if (!$msg_id = $this->request->getGet('msg_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }
        if (!messages::deleteItem($msg_id)) {
            showMsg("删除数据失败", 0, $locations);
        }
        $getTargets = messages::getAlltargets($msg_id);
        foreach ($getTargets as $v) {
            if (!messages::deleteMsgTarget($v['mt_id'])) {
                showMsg("删除信息接受人失败", 0, $locations);
            }
        }
        showMsg("删除数据成功", 0, $locations);
    }

    /*
     * 点击发送消息
     * by kk 2013-04-04
     */
    public function sendMsg()

    {
        $locations = array(0 => array('title' => '用户列表', 'url' => url('user', 'userList')));
        //处理数据
        if ($this->request->getPost('submit', 'trim')) {
            $title = $this->request->getPost('title', 'trim');
            $content = $this->request->getPost('content', 'trim');
            $target = $this->request->getPost('target', 'trim');
            $user_id = $this->request->getPost('user_id', 'trim');

            if (!$user_id) {
                showMsg('非法请求');
            }
            if ($target == 'allchild') {
                //发送给全部下级
                // $sendChilds = users::getUserTree($user_id, true, 1);
                $sendChilds = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1
                ]);
                $to_user_ids = array_keys(array_spec_key($sendChilds, 'user_id'));
            } elseif ($target == 'child') {
                //发送到子下级
                // $sendChilds = users::getUserTree($user_id, true);
                $sendChilds = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                ]);
                $to_user_ids = array_keys(array_spec_key($sendChilds, 'user_id'));
            } else {
                $to_user_ids = array($user_id);
            }
            $data = array(
                'title' => $title,
                'content' => $content,
                'type' => '2', //类型
                'from_user_id' => '0', //0 为管理员
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 1,
            );

            if (!messages::addMsg($data, $to_user_ids)) {
                showMsg('发送消息失败', 1, $locations);
            }

            showMsg('发送消息成功', 0, $locations);
        }

        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
            showMsg("参数无效");
        }
        $user = users::getItem($user_id);
        // $childs = users::getUserTree($user_id, false);
        $childs = users::getUserTreeField([
            'field' => ['user_id'],
            'parent_id' => $user_id,
            'includeSelf' => false
        ]);

        self::$view->setVar('user', $user);
        self::$view->setVar('childs', $childs);
        self::$view->setVar('user', $user);
        self::$view->render('user_sendmsg');
    }

    /**
     * redis 加锁
     * @param string $name 锁标识
     * @param int $timeout 循环获取锁的等待超时时间,在此时间内会一直尝试获取锁直到超时,为0表示失败后直接返回不等待
     * @param int $expire 当前锁的最大生存时间(秒),必须大于0,如果超过生存时间锁仍未被释放,则系统会自动强制释放
     * @param int $waitInterval 获取锁失败后挂起再试的时间间隔(微秒)  1秒=1000毫秒=1000000微秒
     * @return bool
     */
    public function redisLock($name, $timeout = 0, $expire = 15, $waitInterval = 100000)
    {
        $now = time();
        $timeoutAt = $now + $timeout;//获取锁失败时的等待超时时刻
        $expireAt = $now + $expire;//锁的最大生存时刻
        $redisKey = "Lock:{$name}";
        $res =  $GLOBALS['redis'] -> setnx($redisKey,$expireAt);//设定锁
        if(!$res)
        {
            return true;//表示获取到了锁 不在进行锁的设定
        }
        while (true)
        {

            if($res)
            {
                $GLOBALS['redis'] -> expire($redisKey,$expireAt);//设定key的失效时间
                $this->_lockedNames[$name] = $expireAt;//将锁标识房间数组里
                return false;//表示设置锁
            }
            $ttl = $GLOBALS['redis'] ->ttl($redisKey);//获取key的剩余过期时间(秒)
            //ttl小于0表示key没有设置生存时间（key是不会不存在的，因为前面setnx会自动创建）
            //如果出现这种状况，那就是进程的某个实例setnx成功后 crash 导致紧跟着的expire没有被调用 这时可以直接设置expire并把锁纳为己用
            if($ttl < 0)
            {
                $GLOBALS['redis'] -> set($redisKey,$expireAt);//设定key的失效时间
                $this->_lockedNames[$name] = $expireAt;//将锁标识房间数组里
                return false;
            }
            //如果没有设置锁的失败时间 或者已经超过最大等待时间,结束循环
            if($timeout <= 0 || $timeoutAt < microtime(true))
            {
                break;
            }
            //隔$waitInterval后继续请求
            usleep($waitInterval);
        }

    }

    /**
     * redis解锁
     * @param string $name 锁标识
     * @return bool
     */
    public function redisUnlock($name)
    {
        if($this->isLocking($name))
        {
            //删除锁
            if($GLOBALS['redis'] -> deleteKey("Lock:$name"))
            {
                unset($this->_lockedNames[$name]);
                return true;
            }
        }
        return false;
    }
    /**
     * 手工增余额
     */
    public function manualUpdateBalance()
    {
        $locations = array(0 => array('title' => '手工增余额', 'url' => url('user', 'manualUpdateBalance')));
        $op = $this->request->getPost('op', 'trim');

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            $token = $this->request->getPost('token', 'trim');
            if (!$user = users::getItem($username)) {
                showMsg("无效的用户名，请检查", 1, $locations);
            }
            if($this->redisLock($token))
            {
                showMsg('手速太快,请勿重复提交订单!',1,$locations);
            }

            $orderType = 155;   //手工增余额

            $amount = $this->request->getPost('amount', 'floatval');
            //只允许负数
            if ($amount <= 0) {
                showMsg("金额不对。请检查", 1, $locations);
            }
            $remark = $this->request->getPost('remark', 'trim');
            if (!$remark) {
                showMsg("请填写备注");
            }
            /********************* snow  添加数据边界限制*********************************************/
           if($user['balance'] + $amount > 99999999999.99){
               showMsg("超出了数据限制。请检查", 1, $locations);
           }
            /********************* snow  添加数据边界限制*********************************************/
            $remark = $this->request->getPost('remark', 'trim');
            if (!$remark) {
                showMsg("请填写备注");
            }
            if (!users::manualUpdateBalance($user, $amount, $orderType, $GLOBALS['SESSION']['admin_id'], $remark)) {
                showMsg("操作失败", 1, $locations);
            }
            $this->redisLock($token);
            showMsg("执行成功", 0, $locations);
        }

        self::$view->render('user_manualupdatebalance');
    }

    /**
     * 手工批量增余额
     */
    public function manualUpdateBalanceBatch()
    {
        $locations = array(0 => array('title' => '人工批量增余额', 'url' => url('user', 'manualUpdateBalanceBatch')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {

            $params = $this->request->getPost('params', 'array');
            $token = $this->request->getPost('token', 'trim');
            if($this->redisLock($token))
            {
                showMsg('手速太快,请勿重复提交订单!',1,$locations);
            }
            if (empty($params)) {
                showMsg("无效的用数据,请重新输入!", 1, $locations);
            }
            $where='';
            $userIdArr=[];
            foreach ($params as $k => $v) {
                if(floatval($v['amount']) < 0)
                {
                    showMsg("金额不对。请检查!", 1, $locations);
                }
                if(empty($v['remark']))
                {
                    showMsg("请将备注填写完整!");
                }
                $userIdArr[]=$v['user_id'];
                $where .= "  username = '{$v['username']}' or ";
            }
            $where = rtrim($where, ' or');
            $userList = users::getListByCond($where, 'user_id');
            $userIdListArr=[];
            foreach ($userList as $k=>$v)
            {
                $userIdListArr[]=$v['user_id'];
            }
            $checkUser=array_diff($userIdArr,$userIdListArr);
            if(!empty($checkUser))
            {
                showMsg("用户信息不正确。请检查!", 1, $locations);
            }
            $orderType = 155;   //手工增余额
            if (!users::manualUpdateBalanceBatch($params,$userIdArr, $orderType, $GLOBALS['SESSION']['admin_id'])) {
                showMsg("操作失败", 1, $locations);
            }
            $this->redisLock($token);
            showMsg("执行成功", 0, $locations);
        }
        self::$view->render('user_manualupdatebalancebatch');
    }

    /**
     * 手工减余额
     */
    public function manualUpdateBalance1()
    {
        $locations = array(0 => array('title' => '手工减余额', 'url' => url('user', 'manualUpdateBalance1')));
        $op = $this->request->getPost('op', 'trim');

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            if (!$user = users::getItem($username)) {
                showMsg("无效的用户名，请检查", 1, $locations);
            }
            $orderType = 202;   //手工减余额
            if ($orderType == 0) {
                showMsg("无效的参数", 1, $locations);
            }
            $amount = $this->request->getPost('amount', 'floatval');
            if ($amount <= 0) {
                showMsg("金额不对。请检查", 1, $locations);
            }
            $remark = $this->request->getPost('remark', 'trim');
            if (!$remark) {
                showMsg("请填写备注");
            }
            if ($amount > $user['balance']) {
                showMsg("金额不能大于用户余额");
            }
            if (!users::manualUpdateBalance($user, -$amount, $orderType, $GLOBALS['SESSION']['admin_id'], $remark)) {
                showMsg("操作失败", 1, $locations);
            }
            showMsg("执行成功", 0, $locations);
        }

        self::$view->render('user_manualupdatebalance1');
    }

    //用户绑定的银行卡列表
    public function bindCardList()
    {
        $username = $this->request->getGet('username', 'trim');
        $bank_username = $this->request->getGet('bank_username', 'trim');
        $bank_id = $this->request->getGet('bank_id', 'intval', 0);
        $card_num = $this->request->getGet('card_num', 'trim');
        $status = $this->request->getGet('status', 'intval', -1);
        $wrongCard = $this->request->getGet('wrongCard', 'trim');
        $pageNum = DEFAULT_PER_PAGE;
        if ($wrongCard) {
            $pageNum = PHP_INT_MAX;
        }
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * $pageNum;
        /*        $user = users::getItem($username);
                $user_id = empty($user) ? 0 : $user['user_id'];*/
        $cardsNumber = userBindCards::getItemsNumber(0, $bank_id, $card_num, $status, $username, $bank_username);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $cardsNumber, $pageNum);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $cards = userBindCards::getItems(0, $bank_id, $card_num, $status, $startPos, $pageNum, $username, $bank_username);

        if ($wrongCard) {
            $out = array();
            foreach ($cards as $card) {
                if (!checkBankCard($card['card_num'])) {
                    $out[] = $card;
                }
            }
            $cards = $out;
            $cardsNumber = count($cards);
        }
        /***************** author snow 处理中文首字母*****************************/
        //得到支持的银行列表
        self::$view->setVar('withdrawBankList', getBankListFirstCharter($GLOBALS['cfg']['withdrawBankList']));
        /***************** author snow 处理中文首字母*****************************/

        //查询选项
        self::$view->setVar('username', $username);
        self::$view->setVar('bank_username', $bank_username);
        self::$view->setVar('bank_id', $bank_id);
        self::$view->setVar('card_num', $card_num);
        self::$view->setVar('status', $status);

        self::$view->setVar('cards', $cards);
        self::$view->setVar('pageList', getPageList($cardsNumber, $pageNum));
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteCard')));
        self::$view->render('user_bindcardlist');
    }

    public function editBindCard()
    {
        $bind_card_id = $this->request->getGet('bind_card_id', 'intval') ?: $this->request->getPost('bind_card_id', 'intval');
        $editBindCardRealName = adminGroups::verifyPriv(array(CONTROLLER, 'editBindCardRealName'));

        if (!$bindCard = userBindCards::getItem($bind_card_id)) {
            showMsg("该银行卡不存在或已被删除");
        }
        if ($bank_username = $this->request->getPost('bank_username', 'trim')) {
            $card_num = $this->request->getPost('card_num', 'trim');
            if(strlen($card_num) <= 8)
            {
                showMsg("卡号不正确!");
            }
            $cardInfo = userBindCards::getItem($card_num);
            if($cardInfo && $cardInfo['bind_card_id'] != $bind_card_id)
            {
                showMsg("卡号已经被其他用户绑定!");
            }

            $data = [
                'province' => $this->request->getPost('province', 'trim'),
                'city' => $this->request->getPost('city', 'trim'),
                'branch' => $this->request->getPost('branch_name', 'trim'),
                'bank_id' => $this->request->getPost('bind_bank_id', 'intval'),
                'card_num' => $this->request->getPost('card_num', 'trim'),
            ];

            if ($editBindCardRealName) {
                $data['bank_username'] = $bank_username;
            }

            if (false === userBindCards::updateItem($bind_card_id, $data)) {
                showMsg('修改失败');
            }
            showMsg('修改成功');
        }

        self::$view->setVar('editBindCardRealName', $editBindCardRealName);
        self::$view->setVar('withdrawBankList', $GLOBALS['cfg']['withdrawBankList']);
        self::$view->setVar('bindCard', $bindCard);
        self::$view->render('user_editbindcard');
    }

    public function editGroup()
    {
        if (!$user_id = $this->request->getPost('user_id', 'trim')) {
            die(json_encode(array("errno" => 1, 'errstr' => "参数错误")));
        }

        if (!$user = users::getItem($user_id, -1)) {
            die(json_encode(array("errno" => 2, 'errstr' => "该用户不存在")));
        }

        if (!$ref_group_id = $this->request->getPost('ref_group_id', 'trim')) {
            die(json_encode(array("errno" => 1, 'errstr' => "参数错误")));
        }

        if (!$ref_group = cards::getGroupByRefGroupId($ref_group_id, 1)) {
            die(json_encode(array("errno" => 3, 'errstr' => "该层级不存在或者已禁用")));
        }

        $real_name = $this->request->getPost('real_name', 'trim');

        $nick_name = $this->request->getPost('nick_name', 'trim');

        $qq = $this->request->getPost('qq', 'trim');

        $mobile = $this->request->getPost('mobile', 'trim');

        $data = array(
            'ref_group_id' => $ref_group_id,
            'real_name' => $real_name,
            'nick_name' => $nick_name,
            'qq' => $qq,
            'mobile' => $mobile,
        );
        if($data['real_name'] == ""){
            unset($data['real_name']);
        }
        if($data['nick_name'] == ""){
            unset($data['nick_name']);
        }
        if($data['qq'] == ""){
            unset($data['qq']);
        }
        if($data['mobile'] == ""){
            unset($data['mobile']);
        }

        if (!users::updateItem($user_id, $data)) {
            die(json_encode(array("errno" => 4, 'errstr' => "没有数据被更新")));
        }

        die(json_encode(array("errno" => 0, 'errstr' => "修改成功")));
    }

    public function editRemark()
    {
        if (!$user_id = $this->request->getPost('user_id', 'trim')) {
            die(json_encode(array("errno" => 1, 'errstr' => "参数错误")));
        }

        if (!$user = users::getItem($user_id, -1)) {
            die(json_encode(array("errno" => 2, 'errstr' => "该用户不存在")));
        }

        /*if (!$remark1 = $this->request->getPost('remark1', 'trim')) {
            die(json_encode(array("errno" => 1, 'errstr' => "参数错误")));
        }*/
        $remark1 = $this->request->getPost('remark1', 'trim');

        $data = array(
            'remark' => $remark1,
        );


        if (!users::updateItem($user_id, $data)) {
            die(json_encode(array("errno" => 4, 'errstr' => "没有数据被更新")));
        }

        die(json_encode(array("errno" => 0, 'errstr' => "修改成功")));

    }

    public function setTestUser()
    {
        if (!$user_id = $this->request->getPost('user_id', 'intval')) {
            die(json_encode(array("errno" => 1, 'errstr' => "参数错误1")));
        }

        $is_test_user = $this->request->getPost('is_test_user', 'intval');

        if ($is_test_user < 0) {
            die(json_encode(array("errno" => 1, 'errstr' => "参数错误2")));
        }

        $data = array(
            'is_test' => $is_test_user,
        );

        if (!users::updateItem($user_id, $data)) {
            die(json_encode(array("errno" => 4, 'errstr' => "没有数据被更新")));
        }

        die(json_encode(array("errno" => 0, 'errstr' => "修改成功")));
    }

    //查看用户
    public function viewUser()
    {
        $locations = array(0 => array('title' => '返回用户列表', 'url' => url('user', 'userList')));

        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
            showMsg("invalid args");
        }

        if (!$user = users::getItem($user_id, -1)) {
            showMsg("该用户不存在");
        }

        $user_id = $user['user_id'];
        ///手动解锁
        if ($unlock = $this->request->getPost('unlock', 'trim')) {
            $GLOBALS['mc']->delete('lock', $user_id);
        }
        self::$view->setVar('islock', $GLOBALS['mc']->get('lock', $user_id));

        //得到所有父级
        $allParent = users::getAllParent($user['user_id']);
        //150701 允许总代降级后 有可能一代比总代ID小 上级ID不一定比下级大 因此不能按ID作为排序依据
        //ksort($allParent);
        $allParent = array_spec_key($allParent, 'level');
        ksort($allParent);
        $allParent = array_spec_key($allParent, 'user_id');
        self::$view->setVar('allParent', $allParent);

        //得到返点
        $tmp = userRebates::getItems($user_id);
        $userRebates = array();
        $representLotterys = lottery::getRepresent();
        foreach ($tmp as $v) {
            $v['prize'] = 2 * $representLotterys[$v['property_id']]['zx_max_comb'] * (1 - $representLotterys[$v['property_id']]['total_profit'] + $v['rebate']);
            //JYZ-283快乐扑克特殊处理：使用豹子11050代替转直注数,所以整体/24
            if ($v['property_id'] == 4) {
                $v['prize'] = floor($v['prize'] / 24);
            }
            $userRebates[$v['property_id']] = $v;
        }
        self::$view->setVar('userRebates', $userRebates);
        self::$view->setVar('properties', $GLOBALS['cfg']['property']);
        //得到其团队余额（不包括自己）
        self::$view->setVar('teamBalance', users::getTeamBalance($user['user_id'], false));
        //得到绑定银行卡列表
        $bindCards = userBindCards::getItems($user['user_id'], 0, '', 1);
        self::$view->setVar('bindCards', $bindCards);
        self::$view->setVar('bankList', $GLOBALS['cfg']['withdrawBankList']);

        // 当层级为总代的时候,添加alert绑定
        if ($user['level'] === 0) {
            $userAlert = new userAlert();
            $alertList = $userAlert
                ->field('ua_id,title')
                ->where(['default' => 0])
                ->select();
            $ua_id = $userAlert
                ->where("FIND_IN_SET({$user['user_id']},user_tree)")
                ->getField('ua_id');

            self::$view->setVar('ua_id', $ua_id);
            self::$view->setVar('alertList', $alertList);
        }

        self::$view->setVar('user', $user);

        //获取卡组
        $groups = cards::getGroups();
        /***************************snow 添加 查看qq 和 查看手机************************************************/
        self::$view->setVar('showUserQQ', adminGroups::verifyPriv(array(CONTROLLER, 'showUserQQ')));
        self::$view->setVar('showUserPhone', adminGroups::verifyPriv(array(CONTROLLER, 'showUserPhone')));
        /***************************snow 添加 查看qq 和 查看手机************************************************/
        self::$view->setVar('groups', $groups);

        self::$view->render('user_viewuser');
    }

    //删除银行卡的绑定
    public function deleteCard()
    {
        $locations = array(0 => array('title' => '返回绑定卡列表', 'url' => url('user', 'bindCardList')));
        if (!$bind_card_id = $this->request->getGet('bind_card_id', 'intval')) {
            showMsg("参数无效！", 1, $locations);
        }
        $bind_card = userBindCards::getItem($bind_card_id);
        $admin = admins::getItem($GLOBALS['SESSION']['admin_id'], NULL);

        if ($this->request->getGet('frozen', 'trim')) {
            if (!$remark = $this->request->getPost('remark', 'trim')) {
                showMsg("参数无效！", 1, $locations);
            }
            // 插入events
            $eventData = array(
                'user_id' => $bind_card['user_id'],
                'type' => 104,
                'new_value' => '',
                'old_value' => $bind_card['card_num'],
                'remark' => $remark,
                'create_time' => date('Y-m-d H:i:s'),
                'admin_id' => $GLOBALS['SESSION']['admin_id']
            );
            if (!events::addItem($eventData)) {
                return false;
            }

            $remark = date("Y-m-d H:i:s") . "时间由{$admin['username']}进行冻结，原因: " . $remark;
            if (!userBindCards::deleteItem($bind_card_id, true, $remark)) {
                showMsg("冻结数据失败！", 0, $locations);
            }
        } else {
            if (!$remark = $this->request->getPost('remark', 'trim')) {
                showMsg("参数无效！", 1, $locations);
            }
            // 插入events
            $eventData = array(
                'user_id' => $bind_card['user_id'],
                'type' => 104,
                'new_value' => '',
                'old_value' => $bind_card['card_num'],
                'remark' => $remark,
                'create_time' => date('Y-m-d H:i:s'),
                'admin_id' => $GLOBALS['SESSION']['admin_id']
            );
            if (!events::addItem($eventData)) {
                return false;
            }

            $remark = date("Y-m-d H:i:s") . "时间由{$admin['username']}进行删除，原因: " . $remark;
            if (!userBindCards::deleteItem($bind_card_id, false, $remark)) {
                showMsg("删除数据失败！", 0, $locations);
            }
        }
        showMsg("删除数据成功！", 0, $locations);
    }

    //用户列表框架
    public function main()
    {
        self::$view->render('user_main');
    }

    //用户列表左边孩子树
    public function userTree()
    {
        self::$view->render('user_usertree');
    }

    //ajax得到任意级别下的孩子
    public function getChild()
    {
        $parent_id = $this->request->getGet('parent_id', 'intval', 0);
        $users = users::getChild($parent_id, -1);

        echo json_encode($users);
    }

    public function setTopRebate()
    {
        //得到彩种代表
        $representLotterys = lottery::getRepresent();

        //得到所有总代
        // $tops = users::getUserTree(0);
        $tops = users::getUserTreeField([
            'field' => ['user_id', 'username', 'type'],
            'parent_id' => 0,
        ]);
        self::$view->setVar('tops', $tops);

        //得到总代现有返点
        $tmp = userRebates::getItems(array_keys($tops));
        $userRebates = array();
        foreach ($tmp as $v) {
            $v['prize'] = 2 * $representLotterys[$v['property_id']]['zx_max_comb'] * (1 - $representLotterys[$v['property_id']]['total_profit'] + $v['rebate']);
            if ($v['property_id'] == 4) {
                //JYZ-283快乐扑克特殊处理：使用豹子11050代替转直注数,所以整体/24
                $v['prize'] = floor($v['prize'] / 24);
            }
            $userRebates[$v['user_id']][$v['property_id']] = $v;
        }
        self::$view->setVar('userRebates', $userRebates);
        //得到返点差列表
        $gaps = array();
        foreach ($representLotterys as $lottery) {
            $topMaxRebate = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION); //总代最大留水

            $gaps[$lottery['property_id']] = userRebates::genRebateGap($lottery, $topMaxRebate, 0, true);
            //JYZ-283快乐扑克特殊处理：使用豹子11050代替转直注数,所以整体/24
            if ($lottery['property_id'] == 4) {
                foreach ($gaps[$lottery['property_id']] as &$result) {
                    $result['prize'] = floor($result['prize'] / 24);
                }
            }
        }
        self::$view->setVar('gaps', $gaps);
        self::$view->setVar('properties', $GLOBALS['cfg']['property']);
        self::$view->setVar('canSaveTopRebate', adminGroups::verifyPriv(array(CONTROLLER, 'saveTopRebate')));
        self::$view->render('user_settoprebate');
    }

    //ajax保存总代返点，单独做个action是为了灵活设置权限
    public function saveTopRebate()
    {
        //showMsg('此功能暂时关闭');
        $result = array('errno' => 0, 'errstr' => '');
        $user_id = $this->request->getPost('user_id', 'intval');
        $property_id = $this->request->getPost('property_id', 'intval');
        $newRebate = $this->request->getPost('rebate', 'floatval');
        if (!$user_id || !isset($GLOBALS['cfg']['property'][$property_id]) || !$newRebate) {
            $result['errno'] = -1;
            $result['errstr'] = '返点不能为0';
            die(json_encode($result));
        }

        //得到彩种代表
        $representLotterys = lottery::getRepresent();
        //得到返点差列表
        $gaps = array();
        foreach ($representLotterys as $lottery) {
            $topMaxRebate = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION); //总代最大留水
            $gaps[$lottery['property_id']] = userRebates::genRebateGap($lottery, $topMaxRebate, 0, true);
        }

        $result = userRebates::saveUserRebateLoop($gaps, $user_id, $property_id, $newRebate);

        //总代生成默认配额
        if (!$result['errno'] && $property_id == 1) {
            $rebate = userRebates::getUserRebate($user_id, $property_id);
            if ($rebate === false) {
                $data = array('quota' => users::generateQuota(0, $newRebate));
                users::updateItem($this->request->getPost('user_id', 'intval'), $data);
            }
        }

        die(json_encode($result));
    }

    //增加总代
    public function addTop()
    {
        $locations = array(0 => array('title' => '用户列表', 'url' => url('user', 'main')));

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = strtolower($this->request->getPost('username', 'trim'));
            $nickname = strtolower($this->request->getPost('nickname', 'trim'));
            $day_salary = $this->request->getPost('day_salary', 'trim');
            $month_salary = $this->request->getPost('month_salary', 'trim');
            $week_salary = $this->request->getPost('week_salary', 'trim');
            $pwd = $this->request->getPost('pwd', 'trim');
            $pwd2 = $this->request->getPost('pwd2', 'trim');
            if (!$username || !preg_match('`^[a-zA-Z]\w{5,11}$`', $username)) {
                showMsg("用户名长度为6-12个字符，且必须以字母开头");
            }
            if (!$nickname || !preg_match('`^[a-zA-Z]\w{0,11}$`', $nickname)) {
                showMsg("昵称长度为1-12个字符，且必须以字母开头");
            }
            if (!$pwd || strlen($pwd) < 6 || strlen($pwd) > 15 || preg_match('`^\d+$`', $pwd) || preg_match('`^[a-zA-Z]+$`', $pwd)) {
                showMsg("密码长度为6-15字符，不能为纯数字或纯字母");
            }
            if ($pwd != $pwd2) {
                showMsg('两次输入的密码不相同');
            }
            $prize_mode = $this->request->getPost('prize_mode', 'intval');
            if(!$prize_mode)showMsg('返点不可为空');

            $type_value = $this->request->getPost('tg_user', 'trim');

            $type = $type_value == 'on' ? 1 : 0;

            $data = array(
                'username' => $username,
                'pwd' => password_hash($pwd, PASSWORD_DEFAULT),
                'secpwd' => '', //资金密码由客户自己设置
                'level' => 0, //0总代 1一代 2二代 3三代 4四代 5五代 10会员
                'parent_id' => 0,
                'top_id' => 0, //未知，需添加后才知道
                'parent_tree' => '',
                'nick_name' => $nickname,
                'balance' => 0,
                'type' => $type,
                'group_id' => 1, //硬编码 总代为1 1代为2 普通代理为3,会员为4
                'reg_ip' => $GLOBALS['REQUEST']['client_ip'],
                'reg_proxy_ip' => $GLOBALS['REQUEST']['client_ip'],
                'reg_time' => date('Y-m-d H:i:s'),
                'last_ip' => '0.0.0.0',
                'last_time' => '0000-00-00 00:00:00',
                'user_rank' => '0',
                'remark' => '',
                'status' => 8, //0已删除 1冻结 8正常
                'admin_id' => $GLOBALS['SESSION']['admin_id'],
                'month_salary' => $month_salary,
                'day_salary' => $day_salary,
                'week_salary' => $week_salary,
                // 'quota' => users::generateQuota(0, $prize_mode),
            );
            /******************* author snow 添加获取默认配额********************************/
            $default_num = config::getConfig('limit_quota_level_0_default','10000000');
            if(($data['quota']=$this->addTopQuota($data['level'],$prize_mode,$default_num))===false) showMsg("返点参数错误");
            /******************* author snow 添加获取默认配额********************************/

            try {
                $top_id = users::addTop($data, $this->request->getPost('domainIds', 'array'), $prize_mode);
            } catch (Exception $e) {
                showMsg($e->getMessage());
            }
            $GLOBALS['mc']->flush();
            showMsg("添加成功");
        }
        // $domains = domains::getItems(1);
        $domains = domains::getCanBoundDomains();
        $salaryStandard = config::getConfigs(array('day_standard', 'week_standard', 'month_standard'));

        self::$view->setVar('salaryStandard', $salaryStandard);
//        $gaps = userRebates::genRebateGapByProperty();
        self::$view->setVar('domains', $domains);
        self::$view->setVar('aPrizeModes', userRebates::levelPrizeModes(0));
//        self::$view->setVar('gaps', $gaps);
        self::$view->render('user_addtop');

    }
    private function addTopQuota($level,$prizeMode,$num=10000){
        $aPrizeModes = userRebates::levelPrizeModes($level, $prizeMode, true);
        if(!empty($aPrizeModes)){
            $tmp_arr=[];
            foreach ($aPrizeModes as $prizeMode => $rebate) {
                $tmp_arr[$prizeMode]=$num;
            }
            return serialize($tmp_arr);
        }
        return false;
    }

    /* 2017-12-24 17:18 by tommy */
    /* 修改真实姓名 begin */
    public function editRealName()
    {
        $user_id = $this->request->getPost('user_id', 'intval');

        $user = M('users')
            ->field(['user_id', 'real_name'])
            ->where([
                'status' => 8
            ])
            ->find($user_id);

        if (!$user) {
            response([
                'errCode' => 1,
                'errMsg' => '无效的用户.'
            ]);
        }

        $real_name = $this->request->getPost('real_name', 'trim');

        if($user['real_name'] == $real_name){
            response([
                'errCode' => 2,
                'errMsg' => '要修改的数据与之前相同.'
            ]);
        }

        if (!preg_match('`^[.\x80-\xff]{3,48}$`', $real_name)) {
            response([
                'errCode' => 3,
                'errMsg' => '真实姓名需在1-16个中文字符间.'
            ]);
        }

        M()->startTrans();

        // 1.插入events
        $eventData = array(
            'user_id' => $user['user_id'],
            'type' => 101,
            'new_value' => $real_name,
            'old_value' => $user['real_name'],
            'remark' => '人工修改真实姓名',
            'create_time' => date('Y-m-d H:i:s'),
            'admin_id' => $GLOBALS['SESSION']['admin_id']
        );

        if (!events::addItem($eventData)) {
            M()->rollback();
            response([
                'errCode' => 4,
                'errMsg' => '记录事件失败.'
            ]);
        }

        // 2.更新用户姓名
        $data = [
            'real_name' => $real_name,
            'remark' => date('Y-m-d H:i:s') . ' 由 ' . $GLOBALS['SESSION']['admin_username'] . '修改姓名;',
            'admin_id' => $GLOBALS['SESSION']['admin_id'],
        ];

        $result = M('users')->where(['user_id' => $user['user_id']])->update($data);

        if ($result === false) {
            M()->rollback();
            response([
                'errCode' => 5,
                'errMsg' => '执行修改姓名失败.'
            ]);
        }

        if ($result > 0) {
            M()->commit();
            response([
                'errCode' => 0,
                'errMsg' => '执行修改姓名成功.'
            ]);
        } else {
            M()->rollback();
            response([
                'errCode' => 6,
                'errMsg' => '没有数据被修改.'
            ]);
        }
    }
    /* 修改真实姓名 end */

    /**
     * 编辑返点
     * @throws exception2
     */
    public function editRebate()
    {
        $user_id = $this->request->getGet('user_id', 'intval', $this->request->getPost('user_id', 'intval'));

        if (!$user = users::getItem($user_id)) showMsg("用户不存在，或者已冻结");

        $aPrizeMode = userRebates::setSubPrizeModes($user);

        //修改数据
        if ('doEditRebate' == $this->request->getPost('op', 'trim')) {
            $setPrizeMode = $this->request->getPost('prize_group', 'intval');
            if (!in_array($setPrizeMode, array_keys($aPrizeMode))) showMsg("返点错误");

            $locations = array(0 => array('title' => '用户列表', 'url' => url('user', 'userList', array('parent_id' => $user['parent_id']))));

            if (userRebates::updateRebate($user, $setPrizeMode)) showMsg("更新成功", 0, $locations);
            else showMsg("更新失败");
        }

        self::$view->setVar('aPrizeMode', $aPrizeMode);
        self::$view->setVar('userPrizeMode', userRebates::userPrizeMode($user_id));

        self::$view->setVar('user', $user);

        self::$view->render('user_editrebate');
    }

    //修改可开目标特殊帐号配额
    public function editQuota()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations = array(0 => array('title' => '用户列表', 'url' => url('user', 'userList', array('parent_id' => $parent_id))));
        //修改数据
        if ($this->request->getPost('submit', 'trim')) {

            $quotas = $this->request->getPost('quota', 'array');
            $isAdd = $this->request->getPost('is_add', 'intval');
            $user_id = $this->request->getPost('user_id', 'intval');
            $user = users::getItem($user_id);
            foreach ($quotas as $prizeMode => $num) {
                if ($num <= 0) continue;
                if ($isAdd) {
                    users::addQuota($user, $prizeMode, $num);
                } else {
                    users::decQuota($user, $prizeMode, $num);
                }
            }

            showMsg("更新成功", 0, $locations);
        }
        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
            showMsg("参数无效");
        }

        $user = users::getItem($user_id);

        $currentQuota = users::countOfQuota($user, false, true);

        $aPrizeModes = userRebates::levelPrizeModes($user['level'], userRebates::userPrizeMode($user['user_id']), true);
        //得到公共配额配置
//        $pubQuotas = config::getConfigs(array_keys($quota));
//        self::$view->setVar('pubQuotas', $pubQuotas);
        self::$view->setVar('currentQuota', $currentQuota);
        self::$view->setVar('aPrizeModes', $aPrizeModes);
        self::$view->setVar('user', $user);
        self::$view->render('user_editquota');
        $GLOBALS['mc']->flush();
    }

    public function editPwd()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations = array(0 => array('title' => '用户列表', 'url' => url('user', 'userList', array('parent_id' => $parent_id))));
        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $data = array(
                'nickname' => $this->request->getPost('nickname', 'trim'),
                'status' => $this->request->getPost('status', 'intval'), //0已删除 1冻结 8正常
                'admin_id' => $GLOBALS['SESSION']['admin_id'],
            );
            $user_id = $this->request->getPost('user_id', 'intval');
            $pwd = $this->request->getPost('pwd', 'trim');
            $pwd2 = $this->request->getPost('pwd2', 'trim');
            $secpwd = $this->request->getPost('secpwd', 'trim');
            $secpwd2 = $this->request->getPost('secpwd2', 'trim');

            $reason = $this->request->getPost('reason', 'trim');
            if (!$user = users::getItem($user_id, -1)) {
                showMsg("找不到该用户");
            }
            if (!$pwd && !$secpwd) {
                showMsg("没有数据要提交");
            }
            if ($pwd && $secpwd && $pwd == $secpwd) {
                showMsg("登录密码和资金密码不能相同");
            }

            if ($pwd) {
                if (!$pwd || strlen($pwd) < 6 || preg_match('`^\d+$`', $pwd) || preg_match('`^[a-zA-Z]+$`', $pwd)) {
                    showMsg("密码不少于6个字符，不能为纯数字或纯字母");
                }
                if ($pwd != $pwd2) {
                    showMsg("两次输入的密码不一致");
                }
                $data['pwd'] = md5($pwd);
            }
            if ($secpwd) {
                if (!$secpwd || strlen($secpwd) < 6 || preg_match('`^\d+$`', $secpwd) || preg_match('`^[a-zA-Z]+$`', $secpwd)) {
                    showMsg("密码不少于6个字符，不能为纯数字或纯字母");
                }
                if ($secpwd != $secpwd2) {
                    showMsg("两次输入的资金密码不一致");
                }
                $data['secpwd'] = generateEnPwd($secpwd);
            }

            if ($reason == '') {
                showMsg("必须注明原因");
            }

            // 修改密码
            if (!users::updatePwd($user, $pwd, $secpwd, '', $reason, $GLOBALS['SESSION']['admin_id'])) {
                showMsg("没有数据被更新", 1, $locations);
            }
            showMsg("更新成功", 0, $locations);
        }
        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
            showMsg("参数无效");
        }

        $user = users::getItem($user_id);
        self::$view->setVar('user', $user);
        self::$view->render('user_editpwd');
    }

    public function freezeUser()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations = array(0 => array('title' => '返回用户列表', 'url' => url('user', 'userList', array('parent_id' => $parent_id))));
        if ('doFreezeUser' == $this->request->getPost('op', 'trim')) {
            $user_id = $this->request->getPost('user_id', 'intval');
            $freezeType = $this->request->getPost('freezeType', 'intval', '1'); //目前只有一个冻结方式
            $includeChild = $this->request->getPost('includeChild', 'intval', '0|1');
            $freezeCard = $this->request->getPost('freezeCard', 'intval', '0');

            if (!$user_id) {
                showMsg("参数无效！", 1, $locations);
            }
            if (!$reason = $this->request->getPost('reason', 'trim')) {
                showMsg("请输入原因", 1, $locations);
            }

            $users = !$includeChild ? [$user_id] : array_keys(users::getItems($user_id,true,true));
            if (!users::setStatus($user_id, $freezeType, $reason, $includeChild, $GLOBALS['SESSION']['admin_id'], $freezeCard)) {
                showMsg("没有数据被更新", 0, $locations);
            }
            /********************* snow 禁用用户成功后马上踢下线 start**************************************/
            $this->_delUserFromSession($user_id, $includeChild);
            /********************* snow 禁用用户成功后马上踢下线 end**************************************/
            showMsg("冻结用户成功", 0, $locations);
        }

        if (!$user_id = $this->request->getGet('user_id', 'intval')) {
            showMsg("参数无效");
        }
        if (!$user = users::getItem($user_id, -1)) {
            showMsg("找不到用户");
        }
        self::$view->setVar('user', $user);
        self::$view->setVar('parent_id', $parent_id);
        self::$view->render('user_freezeuser');
    }
    /********************* snow 禁用用户成功后马上踢下线 start**************************************/

    /**
     * 踢用户下线  删除redis 和session 数据
     * @param $user_id  int 用户id
     * @param $includeChild  int  是否包括下级
     * @return mixed
     */
    private  function _delUserFromSession($user_id, $includeChild)
    {
        //>>第一步,判断并获取需要踢下线的所有用户
        $users = !$includeChild ? [$user_id] : array_keys(users::getItems($user_id,true,true));
        $GLOBALS['redis']->select(REDIS_DB_SESSION);
        foreach ($users as $user_id)
        {
            //>>获取用户信息并转换为数组,获取session_id
            $session_id = $this->_getSeeeionIdByUser_id($user_id);
            $GLOBALS['redis']->del('session_0_' . $user_id);
            if (!empty($session_id))
            {
                //>>删除相应session
                $GLOBALS['redis']->del('session_' . $session_id);
            };
        }
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);

        return true;
    }

    /**
     * 根据用户id 获取session_id
     * @param $user_id  int 用户id
     * @return string string session_id
     */
    private function _getSeeeionIdByUser_id($user_id)
    {
        $userInfo = json_decode($GLOBALS['redis']->get('session_0_' . $user_id), true);
        return  is_array($userInfo) && !empty($userInfo) ? $userInfo['session_id'] : '';
    }
    /********************* snow 禁用用户成功后马上踢下线 end**************************************/
    public function transferLevel()
    {
        $locations = array(0 => array('title' => '返回用户列表', 'url' => url('user', 'userList')));

        $sa = $this->request->getPost('sa', 'trim');
        switch ($sa) {
            case 'checkUser':
                $username = $this->request->getPost('username', 'trim');
                if (!$user = users::getItem($username)) {
                    return json_encode(array('errno' => 1, 'errstr' => '无此用户'));
                }

                $userRebates = userRebates::getItems(array($user['user_id']));
                $str = '';
                foreach ($userRebates as $v) {
                    $str .= "{$GLOBALS['cfg']['property'][$v['property_id']]}：{$v['rebate']} ";
                }
                echo json_encode(array('errno' => 0, 'user_id' => $user['user_id'], 'username' => $user['username'], 'level' => $user['level'], 'rebate_info' => $str));
                die();
                break;
            case 'transfer':
                $username = $this->request->getPost('username', 'trim');
                $transferType = $this->request->getPost('transferType', 'intval', '1|2');
                $top_username = $this->request->getPost('top_username', 'trim');
                if ($transferType == 1) {
                    users::decreaseLevel($username, $top_username);  // 总代=》一代
                } else {
                    users::IncreaseLevel($username);  // 一代=》总代
                }

                showMsg("转移成功", 0, $locations);
                break;
        }

        //得到所有总代
        // $topUsers = users::getUserTree(0);
        $topUsers = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => 0,
        ]);
        self::$view->setVar('json_topUsers', json_encode($topUsers));

        //得到返点差列表
        $representLotterys = lottery::getRepresent();
        $gaps = array();
        $lottery = $representLotterys[1];
        $topMaxRebate = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION); //总代最大留水
        $gaps = userRebates::genRebateGap($lottery, $topMaxRebate, 0, true);
        self::$view->setVar('gaps', $gaps);

        self::$view->render('user_transferlevel');
    }

    public function batchSendMessage()
    {
        if ($user_id = $this->request->getGet('user_id', 'intval')) {
            if (!$user = users::getItem($user_id)) {
                showMsg('没有此用户或者此用户也被冻结');
            }
            self::$view->setVar('user', $user);
        } else {
            //得到所有总代
            // $topUsers = users::getUserTree(0);
            $topUsers = users::getUserTreeField([
                'field' => ['user_id', 'username'],
                'parent_id' => 0,
            ]);
            self::$view->setVar('json_topUsers', json_encode($topUsers));
        }

        if ('doBatchSendMessage' == $this->request->getPost('op', 'trim')) {
            $push_msg=[];
            $is_push=$this->request->getPost('is_push', 'intval',0);
            if (!$messageType = $this->request->getPost('messageType', 'intval')) {
                showMsg('参数不对，发送类型');
            }
            $title = $this->request->getPost('title', 'trim');
            $push_msg['title']=mb_strlen($title)>20?mb_substr($title,0,20).'...':$title;
            $content = $this->request->getPost('content', 'trim');
            $push_msg['content']=mb_strlen($content)>100?mb_substr($content,0,100).'...':$content;
            if (!$title) {
                showMsg('参数无效:标题没有填写');
            }
            if (mb_strlen($title, 'UTF-8') > 18) {
                showMsg('参数无效:标题长度大于18个字符');
            }
            if (!$content) {
                showMsg('参数无效:消息内容没有填写');
            }
            if (!$selectMethod = $this->request->getPost('selectMethod', 'intval')) {
                showMsg('参数不对，接受方的输入方式');
            }
            $userIds = array();
            if ($selectMethod == 2) {
                if (!$userIds = $this->request->getPost('userIds', 'trim')) {
                    showMsg('参数不对，用户列表');
                }
                $userIds = explode(',', $userIds);
            }
            if ($selectMethod == 1) {
                if (!$input_username = $this->request->getPost('input_username', 'trim')) {
                    showMsg('参数不对，用户列表');
                }
                $input_usernames = explode("\n", $input_username);
                foreach ($input_usernames as $input_username) {
                    if (!trim($input_username)) {
                        continue;
                    }
                    if (!$input_user = users::getItem(trim($input_username))) {
                        showMsg('用户 ' . $input_username . ' 不存在或者已经冻结');
                    }
                    $userIds[] = $input_user['user_id'];
                }
            }
            if ($selectMethod == 3 && $this->request->getPost('userIds', 'trim')) {
                $userIds[] = $this->request->getPost('userIds', 'trim');
            }
            $push_msg['userIds']=[];
            $push_msg['messageType']=$messageType;
            if ($userIds) {
                if (in_array('-1', $userIds)) {
                    unset($userIds);
                    $userIds = array();
                    foreach ($topUsers as $topUser) {
                        $userIds[] = $topUser['user_id'];
                    }
                }
                $push_msg['userIds']=$userIds;
                $sendUserIds = array();
                foreach ($userIds as $userId) {
                    if ($messageType > 1) {
                        $recur = 0;
                        if (2 == $messageType) {
                            $recur = 0;
                        }
                        if (3 == $messageType) {
                            $recur = 1;
                        }
                        // $users = users::getUserTree($userId, true, $recur);
                        $users = users::getUserTreeField([
                            'field' => ['user_id'],
                            'parent_id' => $userId,
                            'recursive' => $recur
                        ]);
                        if (count($users)) {
                            foreach ($users as $u) {
                                $sendUserIds[] = $u['user_id'];
                            }
                        }
                    }
                    $sendUserIds[] = $userId;
                }
                $data = array(
                    'type' => 2,
                    'title' => $title,
                    'content' => $content,
                    'from_user_id' => 0, /* =0 ，表示系统管理员 */
                    'create_time' => date('Y-m-d H:i:s'),
                    'status' => 1,
                );
                $sendUserIds = array_unique($sendUserIds);
                $GLOBALS['db']->startTransaction();
                $result = messages::addMsg($data, $sendUserIds);
                if ($result) {
                    if($is_push){
                        $prefix='';
                        if($push_msg['messageType']==2) $prefix='p_';
                        elseif($push_msg['messageType']==3) $prefix='pt_';
                        $i=1;
                        $item1=$item=0;
                        $alias=$tags=[];
                        foreach ($push_msg['userIds'] as $uid){
                            if($push_msg['messageType']==2||$push_msg['messageType']==3){
                                $tags[$item][]=$prefix.$uid;
                                if($i%20==0)$item++;
                            }
                            $alias[$item1][]='u_'.$uid;
                            if($i%1000==0)$item1++;
                            $i++;
                        }
                        $push=[];
                        $push['tags']=$tags;
                        $push['alias']=$alias;
                        //todo:推送是否要做成功失败的判断
                        if(!empty($push['alias'])){
                            foreach ($push['alias'] as $alias_item){
                                $this->pushUsers($push_msg['title'], $push_msg['content'],$alias_item,['type'=>'3','msg_id'=>$result],1);
                            }
                        }
                        if(!empty($push['tags'])){
                            foreach ($push['tags'] as $tags_item){
                                $this->pushUsers($push_msg['title'], $push_msg['content'],$tags_item,['type'=>'3','msg_id'=>$result],2);
                            }
                        }
                    }
                    showMsg('消息发送成功！');
                } else {
                    $GLOBALS['db']->rollback();
                    showMsg('消息发送失败！');
                }
            }
        }

        $onlyuser = $this->request->getGet('onlyuser', 'intval');
        $precontent = $this->request->getPost('precontent', 'trim');
        $pretitle = $this->request->getPost('pretitle', 'trim');
        $precontent = str_replace('\r\n', "\r\n", $precontent);
        self::$view->setVar('canPush', adminGroups::verifyPriv(array(CONTROLLER, 'pushMsg')));
        self::$view->setVar('onlyuser', $onlyuser);
        self::$view->setVar('precontent', $precontent);
        self::$view->setVar('pretitle', $pretitle);
        self::$view->render('user_batchsendmessage');
    }
    public function pushUsers($title, $alert,$push_users,array $extras,$type=1)
    {
        $appKey=config::getConfig('app_jpush_key','');
        $masterSecret=config::getConfig('app_jpush_masterSecret','');
        if(empty($appKey)||empty($masterSecret))showMsg('请添加推送相关配置信息');
        require_once FRAMEWORK_PATH . 'library/vendor/autoload.php';
        $client = new JPush\Client(trim($appKey), trim($masterSecret));
        try {
            $pusher = $client->push();
            $pusher->setPlatform(array('ios', 'android'));
            if($type==1){
                $pusher->addAlias($push_users);
            }else{
                $pusher->addTag($push_users);
            }
            $pusher->iosNotification([
                "title" => $title,
                "body" => $alert
            ], array(
                'sound' => 'sound.caf',
                'content-available' => true,
                'mutable-content' => true,
                'category' => 'jiguang',
                'extras' => $extras,
            ))->androidNotification($alert, array(
                'title' => $title,
                'extras' => $extras,
            ));

            $pusher->options(array(
                'time_to_live' => 86400,
                'apns_production' => JPUSH_SWITCH,
            ))
                ->send();
            return true;

        } catch (\JPush\Exceptions\APIConnectionException $e) {
            return $e->getMessage();
        } catch (\JPush\Exceptions\APIRequestException $e) {
            return $e->getMessage();
        }
    }
    private function _getONline()
    {
        //>>1从redis 获取在线人数 先加上一个常量
        $online = new onlineUser();
        $number = $online->countOnline();

        //>>这个里面的方法切换了redis 库 .需要切换到app库 天坑
        $this->selectRedisApp();
        return (float)($number + rand(1, 100) / 100 * self::ADD_ONLINE_USER_NUMBER + self::ADD_ONLINE_USER_NUMBER);
    }

    public function aliasJpush(array $alais,$title,$content){
        $appKey=config::getConfig('app_jpush_key','');
        $masterSecret=config::getConfig('app_jpush_masterSecret','');
        if(empty($appKey)||empty($masterSecret))showMsg('请添加推送相关配置信息');
        require_once FRAMEWORK_PATH . 'library/vendor/autoload.php';
        $client = new JPush\Client(trim($appKey), trim($masterSecret));
        try {
            $response = $client->push()
                ->setPlatform(array('ios', 'android'))
                ->addAlias($alais)
                ->setNotificationAlert('Hi, JPush')
                ->iosNotification([
                    "title"=> "系统消息",
                    "subtitle"=> $title,
                    "body"=> $content
                ], array(
                    'sound' => 'sound.caf',
                    'content-available' => true,
                    'mutable-content' => true,
                    'category' => 'jiguang',
                    'extras' => array(
                        'key' => 'iosNotification',
                        'jiguang'
                    ),
                ))
                ->options(array(
                    'time_to_live' => 86400,
                    'apns_production' => JPUSH_SWITCH,
                ))
                ->send();
            print '发送完成';
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            print $e->getMessage();
        } catch (\JPush\Exceptions\APIRequestException $e) {
            print $e->getMessage();
        }
    }

    public function unFreezeUser()
    {
        if (!$parent_id = $this->request->getGet('parent_id', 'intval')) {
            $parent_id = $this->request->getPost('parent_id', 'intval');
        }
        $locations = array(0 => array('title' => '返回用户列表', 'url' => url('user', 'userList', array('parent_id' => $parent_id))));

        if ('doUnFreezeUser' == $this->request->getPost('op', 'trim')) {
            $user_id = $this->request->getPost('user_id', 'intval');
            $includeChild = $this->request->getPost('includeChild', 'intval', '0|1');
            if (!$user_id) {
                showMsg("参数无效！", 1, $locations);
            }
            if (!$reason = $this->request->getPost('reason', 'trim')) {
                showMsg("请输入原因", 1, $locations);
            }

            if (!users::setStatus($user_id, 8, date('Y-m-d H:i:s') . "由{$GLOBALS['SESSION']['admin_username']}进行解冻，原因：$reason", $includeChild, $GLOBALS['SESSION']['admin_id'])) {
                showMsg("没有数据被更新", 0, $locations);
            }

            showMsg("解冻用户成功", 0, $locations);
        }

        if (!$user_id = $this->request->getGet('user_id', 'intval')) {
            showMsg("参数无效");
        }
        if (!$user = users::getItem($user_id, -1)) {
            showMsg("找不到用户");
        }

        self::$view->setVar('user', $user);
        self::$view->setVar('parent_id', $parent_id);
        self::$view->render('user_unfreezeuser');
    }

    public function recycleList()
    {
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $recyclesNumber = recycles::getItemsNumber(0, 0, -1);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $recyclesNumber);
        $recycles = recycles::getItems(0, 0, -1,$startPos); //>>修改传入开始值
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/

        $userIds = array_merge(array_keys(array_spec_key($recycles, 'user_id')), array_keys(array_spec_key($recycles, 'target_user_id')));
        $users = users::getItemsById($userIds);

        self::$view->setVar('recycles', $recycles);
        self::$view->setVar('users', $users);
        self::$view->setVar('pageList', getPageList($recyclesNumber, DEFAULT_PER_PAGE));
        self::$view->render('user_recyclelist');
    }

    public function processRecycle()
    {
        if (!$recycle_id = $this->request->getGet('recycle_id', 'intval')) {
            $recycle_id = $this->request->getPost('recycle_id', 'intval');
        }
        $locations = array(0 => array('title' => '返回申请回收列表', 'url' => url('user', 'recycleList')));

        if (!$recycle = recycles::getItem($recycle_id)) {
            showMsg("找不到数据", 0, $locations);
        }

        if ('doProcessRecycle' == $this->request->getPost('op', 'trim')) {
            $new_username = $this->request->getPost('new_username', 'trim');
            $reason = $this->request->getPost('reason', 'trim');
            if (!recycles::processRecycle($recycle_id, $reason, $new_username, $GLOBALS['SESSION']['admin_id'])) {
                showMsg("没有数据被更新", 0, $locations);
            }

            showMsg("回收用户成功", 0, $locations);
        } elseif ('refuseProcessRecycle' == $this->request->getPost('op', 'trim')) {  //拒绝受理
            $data = array(
                'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
                'status' => 9,
            );
            recycles::updateItem($recycle_id, $data);

            showMsg("已拒绝对该用户的受理请求", 0, $locations);
        }

        if (!$target_user = users::getItem($recycle['target_user_id'], -1)) {
            showMsg("找不到用户");
        }

        //得到绑定银行卡列表
        $bindCards = userBindCards::getItems($target_user['user_id'], 0, '', 1);
        self::$view->setVar('bindCards', $bindCards);
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);

        self::$view->setVar('recycle_id', $recycle_id);
        self::$view->setVar('recycle', $recycle);
        self::$view->setVar('target_user', $target_user);
        self::$view->render('user_processrecycle');
    }

    public function deleteUser()
    {
        $locations = array(0 => array('title' => '返回用户列表', 'url' => url('user', 'userList')));
        if (!$user_id = $this->request->getGet('user_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }

        if (!users::updateItem($user_id, array('status' => 0))) {
            showMsg("伪删除数据失败", 0, $locations);
        }

        showMsg("伪删除数据成功", 0, $locations);
    }

    public function eventList()
    {
        $username = $this->request->getGet('username', 'trim');
        $type = $this->request->getGet('type', 'intval');
        $admin = $this->request->getGet('admin', 'trim');
        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d'));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d', strtotime('+1 day')));
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $eventsNumber = events::getItemsNumber($username, $type, $startDate, $endDate, $admin);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $eventsNumber);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/

        $events = events::getItems($username, $type, $startDate, $endDate, $admin, $startPos, DEFAULT_PER_PAGE);

        self::$view->setVar('username', $username);
        self::$view->setVar('type', $type);
        self::$view->setVar('admin', $admin);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('events', $events);
        self::$view->setVar('pageList', getPageList($eventsNumber, DEFAULT_PER_PAGE));
        self::$view->render('user_eventlist');
    }

    /**
     * 新增调额申请
     * by ray 2015-9-7 13:34:02
     */
    public function addBalanceAdjust()
    {
        $locations = array(0 => array('title' => '调额列表', 'url' => url('user', 'balanceAdjustList')));

        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'showRealName': // 获得用户的真实姓名
                    $username = $this->request->getPost('username', 'trim');
                    if (!is_numeric($username) && !preg_match('`^[a-zA-Z]\w{3,}$`', $username)) {
                        return array();
                    }

                    $result = array('user_id' => '', 'real_name' => '');
                    if ($user = users::getItem($username)) {
                        $result['real_name'] = $user['real_name'];
                        $result['user_id'] = $user['user_id'];
                    }

                    echo json_encode($result);
                    break;
            }
            exit;
        }

        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            if (!$user = users::getItem($username)) {
                showMsg("无效的用户名，请检查", 1, $locations);
            }
            $reason = $this->request->getPost('reason', 'intval');
            if ($reason <= 0) {
                showMsg("无效的原因", 1, $locations);
            }
            $type = $this->request->getPost('type', 'intval');
            if ($type <= 0) {
                showMsg("无效的类型", 1, $locations);
            }
            $amount = $this->request->getPost('amount', 'floatval');
            //不允许负数
            if ($amount <= 0) {
                showMsg("金额不对。请检查", 1, $locations);
            }
            if ($user['balance'] + $amount < 0) {
                showMsg("调整金额后，用户资金账户不能为负数！", 1, $locations);
            }
            $remark = $this->request->getPost('remark', 'trim');
            if (!$remark) {
                showMsg("请填写备注", 1, $locations);
            }

            if ($type == 1) {
                $tmpAmount = -$amount;
            } elseif ($type == 2) {
                $tmpAmount = $amount;
            }
            $data = array(
                'user_id' => $user['user_id'],
                'reason' => $reason,
                'amount' => $tmpAmount,
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] + $tmpAmount,
                'status' => 0,
                'remark' => $remark,
                'input_admin_id' => $GLOBALS['SESSION']['admin_id'],
                'input_time' => date('Y-m-d H:i:s')
            );
            userBalanceAdjust::addItem($data);

            showMsg("新增提案成功", 0, $locations);
        }

        self::$view->render('user_addbalanceadjust');
    }

    /**
     * 调额列表
     * by ray 2015-9-7
     */
    public function balanceAdjustList()
    {
        $username = $this->request->getGet('username', 'trim');
        $realname = $this->request->getGet('realname', 'trim');
        $type = $this->request->getGet('type', 'trim');
        $operate = $this->request->getGet('operate', 'trim', '-1');
        $amount = $this->request->getGet('amount', 'floatval');
        $startInputDate = $this->request->getGet('startInputDate', 'trim', date("Y-m-d 00:00:00"));
        $endInputDate = $this->request->getGet('endInputDate', 'trim', date("Y-m-d 23:59:59"));
        $inputAdmin = $this->request->getGet('inputAdmin', 'trim');
        $startFinishDate = $this->request->getGet('startFinishDate', 'trim');
        $endFinishDate = $this->request->getGet('endFinishDate', 'trim');
        $finishAdmin = $this->request->getGet('finishAdmin', 'trim');
        $reason = $this->request->getGet('reason', 'intval', '-1');
        $remark = $this->request->getGet('remark', 'trim');
        $status = $this->request->getGet('status', 'intval', '-1');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $amountCondition = '';
        if ($operate != '-1' && $amount >= 0) {
            $amountCondition = $operate . ' ' . $amount;
        }
        $balanceAdjustsCount = userBalanceAdjust::getItemsCount($username, $realname, $type, $amountCondition, $inputAdmin, $finishAdmin, $startInputDate, $endInputDate, $startFinishDate, $endFinishDate, $reason, $remark, $status);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $balanceAdjustsCount);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $balanceAdjusts = userBalanceAdjust::getItems($username, $realname, $type, $amountCondition, $inputAdmin, $finishAdmin, $startInputDate, $endInputDate, $startFinishDate, $endFinishDate, $reason, $remark, $status, $startPos, DEFAULT_PER_PAGE);

        // 获得当前页小计
        $currentTotalAmount = 0;
        foreach ($balanceAdjusts as $v) {
            $currentTotalAmount += abs($v['amount']);
        }

        // 获得管理员列表
        $admins = admins::getItems();

        self::$view->setVar('username', $username);
        self::$view->setVar('realname', $realname);
        self::$view->setVar('type', $type);
        self::$view->setVar('operate1', $operate);
        self::$view->setVar('amount', $amount);
        self::$view->setVar('startInputDate', $startInputDate);
        self::$view->setVar('endInputDate', $endInputDate);
        self::$view->setVar('inputAdmin', $inputAdmin);
        self::$view->setVar('startFinishDate', $startFinishDate);
        self::$view->setVar('endFinishDate', $endFinishDate);
        self::$view->setVar('finishAdmin', $finishAdmin);
        self::$view->setVar('reason', $reason);
        self::$view->setVar('remark', $remark);
        self::$view->setVar('status', $status);
        self::$view->setVar('currentTotalAmount', $currentTotalAmount);
        self::$view->setVar('totalAmount', $balanceAdjustsCount['totalAmount']);

        self::$view->setVar('actionLinks', array(0 => array('title' => '新增调额申请', 'url' => url('user', 'addBalanceAdjust'))));
        self::$view->setVar('admins', $admins);
        self::$view->setVar('balanceAdjusts', $balanceAdjusts);
        self::$view->setVar('pageList', getPageList($balanceAdjustsCount['count'], DEFAULT_PER_PAGE));
        self::$view->render('user_balanceadjustlist');
    }

    /**
     * 执行调额申请
     */
    public function executeBalanceAdjust()
    {
        $locations = array(0 => array('title' => '调额列表', 'url' => url('user', 'balanceAdjustList')));

        $ba_id = $this->request->getGet('id', 'intval');

        // 修改状态
        try {
            userBalanceAdjust::executeBalanceAdjust($ba_id);
        } catch (Exception $e) {
            showMsg($e->getMessage(), 1, $locations);
        }

        showMsg("执行提案成功", 0, $locations);
    }

    /**
     * 取消调额申请
     */
    public function cancelBalanceAdjust()
    {
        $locations = array(0 => array('title' => '调额列表', 'url' => url('user', 'balanceAdjustList')));

        $ba_id = $this->request->getGet('id', 'intval');

        // 修改状态
        try {
            userBalanceAdjust::cancelBalanceAdjust($ba_id);
        } catch (Exception $e) {
            showMsg($e->getMessage(), 1, $locations);
        }

        showMsg("取消提案成功", 0, $locations);
    }

    /*
     * 调级管理
     *
     * by nyjah   2015-09-21
     */
    public function levelAdjustList()
    {
        $username = $this->request->getGet('username', 'trim', '');
        $startInputTime = $this->request->getGet('start_input_time', 'trim');
        $endInputTime = $this->request->getGet('end_input_time', 'trim');
        $inputAdmin = $this->request->getGet('input_admin', 'trim');
        $status = $this->request->getGet('status', 'intval', '-1');
        $type = $this->request->getGet('type', 'intval', '-1');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;

        $admins = admins::getItems();

        $adminsTmp = array_spec_key($admins, 'username');

        $inputAdminId = isset($adminsTmp[$inputAdmin]) ? $adminsTmp[$inputAdmin]['admin_id'] : 0;

        $count = userLevelAdjust::getItemsNumber();

        $locations = array(array('title' => '新增提案', 'url' => url('user', 'addLevelAdjust')));
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $count);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/

        $results = userLevelAdjust::getItems($username, $type, $inputAdminId, $startInputTime, $endInputTime, 0, '', '', $status, $startPos);

        self::$view->setVar('admins', $admins);
        self::$view->setVar('actionLinks', $locations);
        self::$view->setVar('username', $username);
        self::$view->setVar('type', $type);
        self::$view->setVar('startInputTime', $startInputTime);
        self::$view->setVar('endInputTime', $endInputTime);
        self::$view->setVar('inputAdmin', $inputAdmin);
        self::$view->setVar('status', $status);
        self::$view->setVar('results', $results);
        self::$view->setVar('pageList', getPageList($count));
        self::$view->render('user_leveladjustlist');
    }

    /*
     * 新增调级提案
     *
     * by nyjah   2015-09-22
     */
    public function addLevelAdjust()
    {
        $locations = array(0 => array('title' => '新增提案', 'url' => url('user', 'addLevelAdjust')), 1 => array('title' => '调级管理', 'url' => url('user', 'levelAdjustList')));
        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'showRealName': // 获得用户的真实姓名
                    $username = $this->request->getPost('username', 'trim');
                    if (!preg_match('`^[a-zA-Z]\w{3,}$`', $username)) {
                        return array();
                    }

                    $result = array('user_id' => '', 'real_name' => '');
                    if ($user = users::getItem($username)) {
                        $result['real_name'] = $user['real_name'];
                        $result['user_id'] = $user['user_id'];
                        $result['level'] = $user['level'];
                    }

                    echo json_encode($result);
                    break;
                case 'checkAdjustLevel'://如果是降级检查返点
                    $username = $this->request->getPost('username', 'trim');
                    $parent = $this->request->getPost('parent', 'trim');
                    $type = $this->request->getPost('type', 'intval');
                    $result = users::checkAdjustLevel($type, $username, $parent);
                    echo json_encode($result);
                    break;
            }
            exit;
        }

        if ($this->request->getPost('op', 'trim') == 'addLevelAdjust') {

            $type = $this->request->getPost('type', 'intval');
            $username = $this->request->getPost('username', 'trim');
            $parent = $this->request->getPost('parent', 'trim');
            $remark = $this->request->getPost('remark', 'trim');

            if (!$user = users::getItem($username)) {
                showMsg("无效的用户名，请检查", 1, $locations);
            }
            if ($type == 1) {
                if (!$parentUser = users::getItem($parent)) {
                    showMsg("无效的上级代理，请检查", 1, $locations);
                }
            }

            $data = array(
                'user_id' => $user['user_id'],
                'parent_id' => $type == 1 ? $parentUser['user_id'] : 0,
                'username' => $username,
                'parent_name' => isset($parentUser['username']) ? $parentUser['username'] : '',
                'type' => $type,
                'input_admin_id' => $GLOBALS['SESSION']['admin_id'],
                'input_time' => date('Y-m-d H:i:s'),
                'remark' => $remark
            );
            userLevelAdjust::addItem($data);

            showMsg("执行成功", 0, $locations);
        }
        $agents = users::getItems(0, true, 0, array(), 8, 0);
        self::$view->setVar('agents', json_encode($agents));
        self::$view->render('user_addleveladjust');
    }

    public function cancelLevelAdjust()
    {
        $locations = array(0 => array('title' => '调级管理', 'url' => url('user', 'levelAdjustList')));

        $laId = $this->request->getGet('id', 'intval');

        if (!$levelAdjust = userLevelAdjust::getItem($laId)) {
            showMsg("无效的提案", 1, $locations);
        }
        if ($levelAdjust['status'] > 0) {
            showMsg("该状态已经被修改，无法再次修改", 0, $locations);
        }

        // 修改状态
        try {
            userLevelAdjust::cancelLevelAdjust($laId);
        } catch (Exception $e) {
            showMsg($e->getMessage(), 1, $locations);
        }

        showMsg("执行成功", 0, $locations);
    }

    // 给测试账号增加额度
    public function addManualBalanceToTest()
    {
        $locations = array(0 => array('title' => '给测试账号增加额度', 'url' => url('user', 'addManualBalanceToTest')));

        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'showRealName': // 获得用户的真实姓名
                    $result = array('user_id' => '', 'real_name' => '', 'is_test' => '');
                    $username = $this->request->getPost('username', 'trim');
                    if (!preg_match('`^[a-zA-Z]\w{3,}$`', $username)) {
                        echo json_encode($result);
                        break;
                    }

                    if ($user = users::getItem($username)) {
                        $result['user_id'] = $user['user_id'];
                        $result['real_name'] = $user['real_name'];
                        $result['is_test'] = $user['is_test'];
                    }

                    echo json_encode($result);
                    break;
            }
            exit;
        }

        if ($this->request->getPost('submit', 'trim')) {
            $username = $this->request->getPost('username', 'trim');
            $amount = $this->request->getPost('amount', 'floatval');

            try {
                users::addManualBalanceToTest($username, $amount);
            } catch (Exception $e) {
                showMsg($e->getMessage(), 1, $locations);
            }
            showMsg("执行成功", 0, $locations);
        }

        self::$view->render('user_addmanualbalancetotest');
    }


    public function editKillMmc()
    {
        $user_id = $this->request->getGet('user_id', 'intval', $this->request->getPost('user_id', 'intval'));

        if (!$user = users::getItem($user_id)) showMsg("用户不存在，或者已冻结");

        //修改数据
        if ('doEditKill' == $this->request->getPost('op', 'trim')) {
            $killMmc = $this->request->getPost('kill_mmc', 'floatval');

            if (users::updateItem($user['user_id'], array('kill_mmc' => $killMmc))) showMsg("更新成功", 0);
            else showMsg("更新失败");
        }

        self::$view->setVar('user', $user);
        self::$view->render('user_editkillmmc');
    }


}


