<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：银行卡管理
 */
class cardController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'cardList' => '银行卡列表',
        'orderList' => '卡帐变列表',
        'autoDepositList' => '自动收款列表',
        'manualSaveDeposit' => '手工补单',
        'addOtherFee' => '其他运营费用登记',
        'addCard' => '增加银行卡',
        'editCard' => '编辑银行卡',
        'editCardImportInfo' => '编辑银行卡重要信息',
        'getCard' => '得到某类型的卡',
        'innerTransfer' => '转移余额',
        'deleteCard' => '删除银行卡',
        'allocGroup' => '分配卡组',
        'groupList' => '卡组列表',
        'addGroup' => '添加卡组',
        'editGroup' => '编辑卡组',
        'deleteGroup' => '删除卡组',
        'editStatus' => '修改状态',
        'refreshGroup' => '刷新层级',
        'depositGroupList' => '充值类别管理',
        'depositGroupAdd' => '充值类别添加',
        'depositGroupUpdate' => '充值类别修改',
        'deleteCardMany' => '批量删除卡'
    );

    private $usageListKey = 'usageListKeyOfStatus_1';  //>>充值卡类别缓存key
    private $cardGroupsKey = 'cardGroupsKey_1';        //>>卡层级hash key
    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function cardList()
    {
        $type = $this->request->getGet('type', 'intval', 0);    //1收款 2付款
        $bank_id = $this->request->getGet('bank_id', 'intval', 0);
        $usage = $this->request->getGet('usage', 'intval', 0);
        $card_name = $this->request->getGet('card_name', 'trim');
        $status = $this->request->getGet('status', 'intval', -2);
        $ref_group_id = $this->request->getGet('ref_group_id', 'string', -1);
        $use_palce = $this->request->getGet('use_place', 'trim', '');
        if ($sa = $this->request->getPost('sa', 'trim')) {
            switch ($sa) {
                case 'saveSort':
                    $sort_ids = $this->request->getPost('sort_ids', 'array');
                    foreach ($sort_ids as $menu_id => $sort) {
                        cards::updateItem($menu_id, array('sort' => $sort));
                    }
                    showMsg("保存成功");
                    break;
            }

        }
/*********************** author snow 修改循环查询为一次查询*************************************************/

        //>>获取出入款账号卡数据
        $cards = cards::getItemsAd($type, $bank_id, $usage, $card_name, $status, cards::ORDER_BY_BANK_ID, $ref_group_id, $use_palce);


        // 得到所有层级 几乎不会改变 所有使用缓存
        $field = ['ref_group_id', 'name'];
        $redisKey = implode('-', $field);
        $card_groups = redisHashGet($this->cardGroupsKey, $redisKey, function() use($field) {

            return cards::getGroupsExcludeForField($field);

        });

        foreach ($cards as $key => $value) {
            $cards[$key]['ref_group_name'] = getCardGroupName($value['ref_group_id'],$card_groups);
        }

//        while (list($key, $val) = each($cards)) {
//            if(!empty($val['ref_group_id'])) {
//                $val['ref_group_name'] = cards::getGroupByRefGroupIdC($val['ref_group_id']);
//                $cards[$key] = $val;
//            }else{
//                $val['ref_group_name'] ='暂无';
//                if($val) {
//                    $cards[$key] = $val;
//                }else{
//                    $cards[$key] = 0;
//                }
//            }
//        }

        /**********************author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到支持的银行列表
        self::$view->setVar('bankList', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
        /**********************author snow  把银行卡列表转换成包含首字母的数组******************************/
        // 增加 使用缓存 ,常用,且数据量小, 注意,所有使用缓存的地方 ,在进行编辑与添加删除时需要更新缓存
        $usageList = redisGet($this->usageListKey, function (){
            return (new cardDepositGroup)
                ->order('sort')
                ->where(['status' => 1])
                ->index('cdg_id')
                ->select();
        },CACHE_EXPIRE_LONG);
        self::$view->setVar('usageList', $usageList);

        /*********************** author snow 修改循环查询为一次查询*************************************************/
        self::$view->setVar('card_groups', $card_groups);
        //查询选项
        if($ref_group_id)
        {
            if(strpos($ref_group_id,',') ==true)
            {
                $ref_group_id_arr=explode(',',$ref_group_id);
            }else{
                $ref_group_id_arr[0] = $ref_group_id;
            }
        }else{
            $ref_group_id_arr[0] = 0;
        }
        self::$view->setVar('card_group', $ref_group_id_arr);
        self::$view->setVar('type', $type);
        self::$view->setVar('bank_id', $bank_id);
        self::$view->setVar('usage', $usage);
        self::$view->setVar('card_name', $card_name);
        self::$view->setVar('use_place', $use_palce);
        self::$view->setVar('status', $status);
        /*********************** author snow 修改循环查询为一次查询*************************************************/
        $options = [
            'canChangeBalance'  => [CONTROLLER, 'changeBalance'],
            'canSetStatus'      => [CONTROLLER, 'setStatus'],
            'canInnerTransfer'  => [CONTROLLER, 'innerTransfer'],
            'canEdit'           => [CONTROLLER, 'editCard'],
            'canDelete'         => [CONTROLLER, 'deleteCard'],
        ];
        self::$view->setVar(admins::getAdminPermission($options));
        /*********************** author snow 修改循环查询为一次查询*************************************************/
        self::$view->setVar('cards', $cards);
        self::$view->setVar('actionLinks', array(0 => array('title' => '新增银行卡', 'url' => url('card', 'addCard'))));
        self::$view->render('card_cardlist');
    }

    public function orderList()
    {
        $from_card_type = $this->request->getGet('from_card_type', 'intval');
        $from_card_id = $this->request->getGet('from_card_id', 'intval');
        $order_type = $this->request->getGet('order_type', 'intval');
        $ref_username = $this->request->getGet('ref_username', 'trim');
        /******************** snow  修改获取正确页码值******************************/
        //>>author snow添加默认值,开始时间与结束时间
        $startDate = $this->request->getGet('startDate', 'trim',date('Y-m-d 00:00:00'));
        $endDate = $this->request->getGet('endDate', 'trim',date('Y-m-d 23:59:59'));
        $trafficInfo = cardOrders::getTrafficInfo($from_card_type, $from_card_id, $order_type, -1, $ref_username, $startDate, $endDate);
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /******************** snow  修改获取正确页码值******************************/
        $cardOrders = cardOrders::getItems($from_card_type, $from_card_id, $order_type, -1, $ref_username, $startDate, $endDate, $startPos, DEFAULT_PER_PAGE);

        //得到支持的银行列表
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);
        //得到交易方式
        self::$view->setVar('cardOrderTypes', $GLOBALS['cfg']['cardOrderTypes']);
        //得到银行和相应卡的二级联动
        $allCards = cards::getItemsWithDeletedCard();
        $typeCards = array();
        foreach ($allCards as $v) {
            $typeCards[$v['type']][] = $v;
        }
        self::$view->setVar('typeCards', $typeCards);
        self::$view->setVar('allCards', $allCards);
//        dd($typeCards);
        //设置预查询值
        self::$view->setVar('from_card_type', $from_card_type);
        self::$view->setVar('from_card_id', $from_card_id);
        self::$view->setVar('order_type', $order_type);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('cardOrders', $cardOrders);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('pageList', getPageList($trafficInfo['count'], DEFAULT_PER_PAGE));
        self::$view->render('card_orderlist');
    }

    public function autoDepositList()
    {
        $bank_id = $this->request->getGet('bank_id', 'intval');
        $card_id = $this->request->getGet('card_id', 'intval');
        //$usage = $this->request->getGet('usage', 'intval');
        $status = $this->request->getGet('status', 'intval', -1);
        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d 00:00:00', time()));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d 23:59:59', time()));
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $trafficInfo = autoDeposits::getTrafficInfo($card_id, $startDate, $endDate, $status);
        /******************** snow  修改获取正确页码值******************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /******************** snow  修改获取正确页码值******************************/
        $autoDeposits = autoDeposits::getItems($card_id, $startDate, $endDate, $status, $startPos, DEFAULT_PER_PAGE);
        //得到支持的银行列表
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);
        //得到交易方式
        self::$view->setVar('tradeTypes', $GLOBALS['cfg']['tradeTypes']);
        //得到银行和相应卡的二级联动
        $cards = cards::getItems(1, 0, 0, '', -2);
        $bankCards = array();
        foreach ($cards as $v) {
            $bankCards[$v['bank_id']][] = $v;
        }
        self::$view->setVar('bankCards', $bankCards);
        self::$view->setVar('cards', $cards);

        //设置预查询值
        self::$view->setVar('bank_id', $bank_id);
        self::$view->setVar('card_id', $card_id);
        //self::$view->setVar('usage', $usage);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('autoDeposits', $autoDeposits);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('pageList', getPageList($trafficInfo['count'], DEFAULT_PER_PAGE));
        self::$view->render('card_autodepositlist');
    }

    public function manualSaveDeposit()
    {
        $locations = array(0 => array('title' => '继续补单', 'url' => url('card', 'manualSaveDeposit')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $player_pay_time = $this->request->getPost('player_pay_time', 'trim');
            if (!$card_id = $this->request->getPost('card_id', 'trim')) {
                showMsg("请选择收款卡。请检查", 1, $locations);
            }

            if (!$card = cards::getItem($card_id, NULL, NULL)) {
                showMsg("找不到该卡(id={$card_id})");
            }

            $player_pay_time = str_replace(array('年', '月', '日'), array('-', '-', ''), $player_pay_time);
            if (!preg_match("`^(\d{4})-(\d{2})-(\d{2}) \d{2}:\d{2}:\d{2}$`", $player_pay_time)) {
                showMsg("时间格式不对。请检查", 1, $locations);
            }
            $amount = $this->request->getPost('amount', 'intval');
            if (!is_numeric($amount)) {
                showMsg("请填写数字金额", 1, $locations);
            }

            $data = array(
                'bank_id' => $this->request->getPost('bank_id', 'trim'),
                'card_id' => $card_id,
                'player_card_name' => $this->request->getPost('player_card_name', 'trim'),
                'player_pay_time' => $player_pay_time,
                'amount' => $amount,
                'fee' => 0,
                'order_num' =>  date('YmdHis'),
                'ref_user' => $this->request->getPost('user_name', 'trim'),
                'status' => 0,    //-1首次使用，不参与任何计算 0未充 1已充 2重复等原因暂不处理
            );

            //保存补单
            try {
                //开始事务
                $GLOBALS['db']->startTransaction();
                //1.添加记录
                autoDeposits::addItem($data);
                $data['auto_id'] = $GLOBALS['db']->insert_id();
                //说明：付款卡player_bank_id和收款卡bank_id是同一银行
                $data['player_bank_id'] = $data['bank_id'];

                if (empty($data['auto_id']) || empty($data['ref_user'])) {
                    throw new exception2("参数无效33");
                }
                if (!$user = users::getItem($data['ref_user'], -1)) {
                    throw new exception2("不存在的会员({$data['ref_user']})");
                }

                //1.先加一条存款记录
                $deposit = array(
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'top_id' => $user['top_id'],
                    'player_bank_id' => $data['player_bank_id'],
                    'player_card_name' => $data['player_card_name'],
                    'player_pay_time' => $data['player_pay_time'],
                    'amount' => $data['amount'],
                    'fee' => $data['fee'],
                    'order_num' => $data['order_num'],
                    'trade_type' => 1, //1网转 2ATM有卡转账,自助终端转账 4手机网转 5ATM无卡现存 6柜台汇款 7跨行汇款
                    'deposit_bank_id' => $data['bank_id'],
                    'deposit_card_id' => $data['card_id'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'status' => 8, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9因故取消
                    'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
                    'finish_time' => date('Y-m-d H:i:s'),
                    'ref_auto_id' => $data['auto_id'],
                    'remark' => isset($data['remark']) ? $data['remark'] : "{$GLOBALS['SESSION']['admin_username']}-".date('Y-m-d H:i:s')."银行补单"
                );

                deposits::addItem($deposit);
                $deposit_id = $GLOBALS['db']->insert_id();
                $deposit['deposit_id'] = $deposit_id;

                //3.更改为已充值
                if (!autoDeposits::updateItem($data['auto_id'], array('status' => 1), array('status' => 0))) {
                    $GLOBALS['db']->rollback();
                    throw new exception2("更改待定充值记录状态失败(auto_id={$data['auto_id']})", 500);   //应退出，因为表明auto_deposits表数据出了异常
                }

                //看来前面的操作都顺利，提交事务:)
                if (!$GLOBALS['db']->commit()) {
                    $GLOBALS['db']->rollback();
                    throw new exception2("提交事务失败(auto_id={$data['auto_id']})", 500);
                }

                showMsg("添加补单成功", 0, $locations);
            } catch (exception2 $e) {
                showMsg("添加补单失败! 错误信息：" . $e->getMessage(), 1);
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
        self::$view->render('card_manualsavedeposit');
    }

    //增加银行卡
    public function addCard()
    {
        //新增数据
        $groups = cards::getGroups(1);
        $isCanEditImportInfo = adminGroups::verifyPriv(array('card', 'editCardImportInfo'));

        if ($this->request->getPost('submit', 'trim')) {
            $direct_display = $this->request->getPost('direct_display', 'trim') == 'on' ? 1 : 0;
            $not_integer = $this->request->getPost('not_integer', 'trim') == 'on' ? 1 : 0;
            $not_zero = $this->request->getPost('not_zero', 'trim') == 'on' ? 1 : 0;

            $data = array(
                'card_name' => $this->request->getPost('card_name', 'trim'),
                'card_num' => $this->request->getPost('card_num', 'trim'),
                'bind_email' => $this->request->getPost('bind_email', 'trim'),
                'province' => $this->request->getPost('province', 'trim'),
                'city' => $this->request->getPost('city', 'trim'),
                'type' => $this->request->getPost('type', 'intval'),
                'bank_id' => $this->request->getPost('bank_id', 'intval'),
                'usage' => $this->request->getPost('usage', 'intval'),
                'balance' => $this->request->getPost('balance', 'intval'),
                'status' => $this->request->getPost('status', 'intval'),
                'login_name' => $this->request->getPost('login_name', 'trim'),
                'balance_limit' => $this->request->getPost('balance_limit', 'floatval'),
                'day_limit' => $this->request->getPost('day_limit', 'floatval'),
                'shop_url_wap' => $this->request->getPost('shop_url_wap', 'trim'),
                'call_back_url' => $this->request->getPost('call_back_url', 'trim'),
                'return_url' => $this->request->getPost('return_url', 'trim'),
                'remark' => $this->request->getPost('remark', 'trim'),
                /*********************** snow 新添加的字段*********************************************/
                'pay_small_input' => $this->request->getPost('pay_small_input', 'intval'),
                'pay_max_input' => $this->request->getPost('pay_max_input', 'intval'),
                'pay_id_input' => $this->request->getPost('pay_id_input', 'intval'),

                //>>snow  添加新的字段  折扣
                'discount' => $this->request->getPost('discount', 'floatval'),
                //>>添加支付类名
                'obj_name' => $this->request->getPost('obj_name', 'trim',''),
                /*********************** snow 新添加的字段*********************************************/
                'not_integer' => $not_integer,
                'not_zero' => $not_zero,
                'direct_display' => $direct_display,
                'ref_group_id'=>$this->request->getPost('ref_group_id', 'trim'),
                'use_place'=>$this->request->getPost('use_place','array',[]),
                'subbranch' => $this->request->getPost('subbranch', 'trim'),
            );

            if (cards::getItemByCardNum($data['card_num'])) {
                $locations = array(0 => array('title' => '返回', 'url' => url('card', 'addCard' . '&edit_back=1')));
                showMsg("卡号已存在", 0, $locations);
            }

            if (in_array($data['bank_id'], $GLOBALS['cfg']['isNewpay'])) {
                $data['is_newpay'] = 1;
            } else {
                $data['is_newpay'] = 0;
            }

            if ($isCanEditImportInfo) {
                $data['shop_url'] = $this->request->getPost('shop_url', 'trim');
                $data['mer_no'] = $this->request->getPost('mer_no', 'trim');
                $data['mer_key'] = $this->request->getPost('mer_key', 'trim');
                $data['card_pass'] = $this->request->getPost('card_pass', 'trim');
                $data['ukeypwd'] = $this->request->getPost('ukeypwd', 'trim');
            }

            if (file_exists(ROOT_PATH . 'cdn.xml')) {
                $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
                $imgCdnUrl = (string)$xml->web;
            } else {
                $imgCdnUrl = config::getConfig('site_main_domain');
            }

            if (isset($_FILES["qrcode_image"]["name"]) && !empty($_FILES["qrcode_image"]["name"])) {
                $up = new upload();
                $up->set_thumb(100, 80);
                $fs = $up->execute(); // 上传到服务器
                $main_img = $fs['qrcode_image'];

                if ($main_img['flag'] != 1) {
                    // 上传失败
                    $error = '上传' . $main_img['name'] . '时出错。';
                    $error .= $up->getError($main_img['flag']);
                    response([$error], 'MSG');
                }

                // 上传到七牛
                $qiniu = new uptoqiniu($main_img['name'], $main_img['dir']);
                $qiniu->upload();

                // 上传到阿里云
                $aliyun = new uploadaliyun($main_img['name'], $main_img['dir']);

                if(($result = $aliyun->upload()) !== true){
                    // 上传失败
                    showMsg($result);
                }

                $dirIndex = stripos($main_img['dir'], 'images_fh');
                $dir = substr($main_img['dir'], $dirIndex);
                $data['netway'] =  $imgCdnUrl . '/' . $dir . $main_img['name'];
            } else {
                $data['netway'] = $this->request->getPost('netway', 'trim');
            }

/******************************* snow 获取所有选中卡组 down*****************************************************/
       // $ref_group_ids = $this->request->getPost('ref_group', 'array');
       // $data['ref_group_ids'] = $ref_group_ids;
        $this->_cardItemHandle($data);
        //>>验证字段不能为空
/******************************* snow 获取所有选中卡组  up*****************************************************/
        $result = cards::addItem($data);
            //>>验证添加是否成功
/******************************* snow 验证是否添加成功  down*****************************************************/
         // $this->_cardAddResultHandle($result,$data['ref_group_id']);
            if($result){
                //>>全部插入成功
                $locations = array(0 => array('title' => '返回', 'url' => url('card', 'addCard' . '&edit_back=1')));
                showMsg("添加成功", 0, $locations);
            }
/******************************* snow 验证是否添加成功  up*****************************************************/
        }

        $editBack = $this->request->getGet('edit_back', 'intval');

        self::$view->setVar('editBack',$editBack);
        self::$view->setVar('isCanEditImportInfo', $isCanEditImportInfo);

        /**********************author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到支持的银行列表
        self::$view->setVar('payClassList', json_encode($GLOBALS['cfg']['payClassList']));
        self::$view->setVar('bankList', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
/**********************author snow  把银行卡列表转换成包含首字母的数组******************************/
        self::$view->setVar('groups', $groups);
/******************************* snow 添加变量区别添加还是编辑  down*****************************************************/
        self::$view->setVar('group_is_add', true);
/******************************* snow 添加变量区别添加还是编辑  up*****************************************************/
        self::$view->render('card_addcard');
    }
/******************************* snow 验证是否添加成功 down *****************************************************/
    /**
     * 验证是否添加成功
     * @param $result  string 返回值
     * @param $ref_group_ids  array post获取到的卡组数据
     */
    private function _cardAddResultHandle($result,$ref_group_ids){
        if($result === true){
            //>>全部插入成功
            showMsg("层级添加成功");
        }

        if($result === false){
            //>>全部插入失败
            showMsg("添加银行卡失败!请检查数据输入是否完整。");
        }

        if(is_array($result) && !empty($result)){
            //>>全部插入失败.

            if(count($result) == count($ref_group_ids)){
                showMsg("添加银行卡失败!请检查数据输入是否完整。");

            }else{
                //>>部分插入成功.
                $tmp     = []; //>>失败的.
                $success = [];  //>>成功的.
                foreach($result as $key => $val){
                    foreach($ref_group_ids as $k => $v){
                        if($val == $v['ref_group_id']){
                            $tmp[]       = $v['name'];
                        }else{
                            $success[]   = $v['name'];
                        }
                    }
                }
                $tmpStr  = implode(',',$tmp);

                showMsg($tmpStr  ." 添加银行卡失败!" . $success . "添加银行卡成功。");
            }

        }
    }

/******************************* snow 验证是否添加成功*   up ****************************************************/
/******************************* snow 验证card 添加字段不能为空 down *****************************************************/
    /**
     * 添加字段不能为空
     * @param $data  array  需要验证的数据
     */
    private function _cardItemHandle(&$data){
        $resultStr = '';
        if(empty($data['use_place']) || !is_array($data['use_place']))showMsg("请选择支付使用场景");
        $use_place=0;
        foreach ($data['use_place'] as $val)$use_place=$use_place|$val;
        if($use_place<=0 || $use_place>7)showMsg("请选择支付使用场景");
        $data['use_place']=$use_place;
        if(empty($data['card_name'])){
            //>>户名不能为空
            $resultStr .= "户名";
        }

        if(empty($data['ref_group_id'])){
            //>>户名不能为空
            showMsg($resultStr . " :请选择卡组!");
        }
        if($data['status'] == '-1'){
            //>>户名不能为空
            $resultStr .=  empty($resultStr) ? "状态" : ",状态";
        }
        if(!empty($resultStr)){
            showMsg($resultStr . " :字段不能为空");
        }

        //>>添加三个字段验证
        if($data['pay_small_input'] < 1 || $data['pay_small_input'] > 99999999999){
            $resultStr .=  empty($resultStr) ? "最小额度必须是大于等于1小于99999999999的整数" : ",最小额度必须是大于等于1小于99999999999的整数";
        }


        if($data['pay_max_input'] < 1 || $data['pay_max_input'] < $data['pay_small_input'] || $data['pay_max_input'] > 99999999999){
            $resultStr .=  empty($resultStr) ? "最大额度必须是大于等于1小于99999999999的整数,且不能小于最小额度" : ",最大额度必须是大于等于1小于99999999999的整数,且不能小于最小额度";
        }

        //>>判断最小额度不能大于最大额度.
        if ($data['pay_max_input'] <= $data['pay_small_input'])
        {
            $resultStr .=  empty($resultStr) ? "最小额度不能大于最大额度" : ",最小额度不能大于最大额度";
        }

        //>>snow 验证 discount 字段 小数点后面最多只能有两位小数
           if($data['discount'] < 0 || $data['discount'] > 10 || strlen(substr($data['discount'],strrpos((string)$data['discount'],'.'))) > 3 ){
            $resultStr .=  empty($resultStr) ? "折扣必须介于0 - 10 之间的小数或整数,且不能为0" : ",折扣必须介于0 - 10 之间的小数或整数,且不能为0";
        }

        //>>添加验证支付类名
        if ($data['bank_id'] > 100)
        {
            //>>back_id > 100 是第三方支付,才需要验证支付类名
            if ($data['obj_name'] == '')
            {
//                $resultStr .=  empty($resultStr) ? "第三方支付支付类名不能为空" : ",第三方支付支付类名不能为空";
            }
            else
            {
                //>>不为空,验证 只能输入英文字母
                if ( !preg_match('/^([a-zA-Z]+){3,64}$/',$data['obj_name']))
                {
                    $resultStr .=  empty($resultStr) ? "第三方支付支付类名只能输入英文字母,且不能大于64个字符小于3个字符" : ",第三方支付支付类名只能输入英文字母,且不能大于64个字符小于3个字符";
                }
            }

        }

        if(!empty($resultStr)){
            showMsg($resultStr);
        }
    }
    /******************************* snow 验证card 添加字段不能为空 up *****************************************************/
    //修改银行卡 不能修改余额
    public function editCard()
    {
        $locations = array(0 => array('title' => '返回银行卡列表', 'url' => url('card', 'cardList')));
        $isCanEditImportInfo = adminGroups::verifyPriv(array('card', 'editCardImportInfo'));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $direct_display = $this->request->getPost('direct_display', 'trim') == 'on' ? 1 : 0;
            $not_integer = $this->request->getPost('not_integer', 'trim') == 'on' ? 1 : 0;
            $not_zero = $this->request->getPost('not_zero', 'trim') == 'on' ? 1 : 0;

            $data = array(
                'card_name' => $this->request->getPost('card_name', 'trim'),
                'card_num' => $this->request->getPost('card_num', 'trim'),
                'bind_email' => $this->request->getPost('bind_email', 'trim'),
                'province' => $this->request->getPost('province', 'trim'),
                'city' => $this->request->getPost('city', 'trim'),
                //不允许更改卡类型，因为相关的card_id都是固定的，如果改了可能引起帐目混乱
//                'type' => $this->request->getPost('type', 'intval'),
                'bank_id' => $this->request->getPost('bank_id', 'intval'),
                'usage' => $this->request->getPost('usage', 'intval'),
                'status' => $this->request->getPost('status', 'intval'),
                'login_name' => $this->request->getPost('login_name', 'trim'),
                'balance' => $this->request->getPost('balance', 'trim'),
                'balance_limit' => $this->request->getPost('balance_limit', 'trim'),
                'day_limit' => $this->request->getPost('day_limit', 'trim'),
                'shop_url_wap' => $this->request->getPost('shop_url_wap', 'trim'),
                'call_back_url' => $this->request->getPost('call_back_url', 'trim'),
                'return_url' => $this->request->getPost('return_url', 'trim'),
                'ref_group_id' => $this->request->getPost('ref_group_id', 'trim'),
                'remark' => $this->request->getPost('remark', 'trim'),
                /*********************** snow 新添加的字段*********************************************/
                'pay_small_input' => $this->request->getPost('pay_small_input', 'intval'),
                'pay_max_input' => $this->request->getPost('pay_max_input', 'intval'),
                'pay_id_input' => $this->request->getPost('pay_id_input', 'intval'),
                'discount' => $this->request->getPost('discount', 'floatval'),
                //>>添加支付类名
                'obj_name' => $this->request->getPost('obj_name', 'trim',''),
                /*********************** snow 新添加的字段*********************************************/
                'not_integer' => $not_integer,
                'not_zero' => $not_zero,
                'direct_display' => $direct_display,
                'use_place'=>$this->request->getPost('use_place','array',[]),
                'subbranch' => $this->request->getPost('subbranch', 'trim'),
            );

            $card_id = $this->request->getPost('card_id', 'intval' , 0);
            if($card_id <= 0)
            {
                showMsg("参数无效");
            }

            $oldCard = cards::getItem($card_id);

            if (($oldCard['card_num'] != $data['card_num'] && cards::getItemByCardNum($data['card_num']))) {
                $locations = array(0 => array('title' => '返回', 'url' => url('card', 'editCard' . '&card_id=' . $card_id . '&edit_back=1')));
                showMsg("卡号已存在", 1, $locations);
            }

            if (in_array($data['bank_id'], $GLOBALS['cfg']['isNewpay'])) {
                $data['is_newpay'] = 1;
            } else {
                $data['is_newpay'] = 0;
            }

            if ($isCanEditImportInfo) {
                $data['shop_url'] = $this->request->getPost('shop_url', 'trim');
                $data['mer_no'] = $this->request->getPost('mer_no', 'trim');
                $data['mer_key'] = $this->request->getPost('mer_key', 'trim');
                $data['card_pass'] = $this->request->getPost('card_pass', 'trim');
                $data['ukeypwd'] = $this->request->getPost('ukeypwd', 'trim');
            }

            if (file_exists(ROOT_PATH . 'cdn.xml')) {
                $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
                $imgCdnUrl = (string)$xml->web;
            } else {
                $imgCdnUrl = config::getConfig('site_main_domain');
            }

            if (isset($_FILES["qrcode_image"]["name"]) && !empty($_FILES["qrcode_image"]["name"])) {
                $up = new upload();
                $up->set_thumb(100, 80);
                $fs = $up->execute(); // 上传到服务器
                $main_img = $fs['qrcode_image'];

                if ($main_img['flag'] != 1) {
                    // 上传失败
                    $error = '上传' . $main_img['name'] . '时出错。';
                    $error .= $up->getError($main_img['flag']);
                    response([$error], 'MSG');
                }

                // 上传到七牛
                $qiniu = new uptoqiniu($main_img['name'], $main_img['dir']);
                $qiniu->upload();

                // 上传到阿里云
                $aliyun = new uploadaliyun($main_img['name'], $main_img['dir']);

                if(($result = $aliyun->upload()) !== true){
                    // 上传失败
                    showMsg($result);
                }

                $dirIndex = stripos($main_img['dir'], 'images_fh');
                $dir = substr($main_img['dir'], $dirIndex);
                $data['netway'] =  $imgCdnUrl . '/' . $dir . $main_img['name'];
            } else {
                $data['netway'] = $this->request->getPost('netway', 'trim');
            }

            $this->_cardItemHandle($data);
				if($data['bank_id'] == '118'){
					$data['shop_url'] = '';
				}
                if (!cards::updateItem($card_id, $data)) {
                    $locations = array(0 => array('title' => '返回', 'url' => url('card', 'editCard' . '&card_id=' . $card_id . '&edit_back=1')));
                    showMsg("没有数据被更新", 1, $locations);
                }
                //清除缓存
                if(cards::getCache($card_id) !==false)
                {
                    cards::clearCache($card_id);
                }
                if(cards::getCache($data['card_num']) !==false)
                {
                    cards::clearCache($data['card_num']);
                }

            $locations = array(0 => array('title' => '返回', 'url' => url('card', 'editCard' . '&card_id=' . $card_id . '&edit_back=1')));
            showMsg("更新成功", 0, $locations);
        }
        if (!$card_id = $this->request->getGet('card_id', 'intval')) {
            showMsg("参数无效");
        }

        if (!$card = cards::getItem($card_id, NULL, NULL)) {
            showMsg("该卡不存在");
        }
        $arrTmp=[];//用于层级回显
        if(strpos($card['ref_group_id'],',') !==false)
        {
            $arrTmp=explode(',',$card['ref_group_id']);
        }else{
            $arrTmp[0]=$card['ref_group_id'];
        }
        $groups = cards::getGroups(1);
        $editBack = $this->request->getGet('edit_back', 'intval');

        if (!$isCanEditImportInfo) {
            unset($card['shop_url']);
            unset($card['mer_no']);
            unset($card['card_pass']);
            unset($card['ukeypwd']);
            unset($card['mer_key']);
        }

        /**********************author snow 把银行卡列表转换成包含首字母的数组******************************/
        //得到支持的银行列表
        self::$view->setVar('payClassList', json_encode($GLOBALS['cfg']['payClassList']));
        self::$view->setVar('bankList', getBankListFirstCharter($GLOBALS['cfg']['bankList']));
        /**********************author snow  把银行卡列表转换成包含首字母的数组******************************/
        self::$view->setVar('card', $card);
        self::$view->setVar('groups', $groups);
        self::$view->setVar('arrTmp', $arrTmp);
        self::$view->setVar('editBack',$editBack);
        self::$view->setVar('isCanEditImportInfo', $isCanEditImportInfo);


        self::$view->render('card_addcard');
    }

    public function editCardImportInfo()
    {
        if (!$card_id = $this->request->getPost('card_id', 'intval')) {
            showMsg("参数无效");
        }

        if (!$card = cards::getItem($card_id, NULL, NULL)) {
            showMsg("该卡不存在");
        }

        die(json_encode(array(
            'errCode' => 0,
            'mer_no' => $card['mer_no'],
            'card_pass' => $card['card_pass'],
            'ukeypwd' => $card['ukeypwd'],
            'mer_key' => $card['mer_key'],
        )));
    }

    //ajax 内转时使用 得到某种类型的卡
    public function getCard()
    {
        $type = $this->request->getPost('type', 'intval');
        $cards = cards::getItems($type);
        echo json_encode($cards);
    }

    public function innerTransfer()
    {
        $locations = array(0 => array('title' => '返回银行卡列表', 'url' => url('card', 'cardList')));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $card_id = $this->request->getPost('card_id', 'intval');
            $to_card_id = $this->request->getPost('to_card_id', 'intval');
            $amount = $this->request->getPost('amount', 'floatval');
            $fee = $this->request->getPost('fee', 'floatval');
            if ($amount <= 0) {
                showMsg("余额 $amount 不合法", 1, $locations);
            }
            if ($fee < 0) {
                showMsg("手续费不能小于0", 1, $locations);
            }
            if ($card_id <= 0 || $to_card_id <= 0) {
                showMsg("参数无效");
            }
            if (!$card = cards::getItem($card_id, NULL, NULL)) {    //被禁用的卡也允许转出钱
                showMsg("该源卡不存在");
            }
            if (!$to_card = cards::getItem($to_card_id, NULL, NULL)) {    //被禁用的卡也允许转出钱
                showMsg("该目标卡不存在");
            }
            if ($fee > ceil($amount * 0.01)) {
                showMsg("手续费不对，因为不可能大于1%");
            }
            if ($amount + $fee > $card['balance']) {
                showMsg("转移金额超过了原来金额，提交失败");
            }
            if (($result = cards::innerTransfer($card_id, $amount, $fee, $to_card_id)) !== true) {
                showMsg("内转失败：$result", 1, $locations);
            }

            showMsg("内转成功", 0, $locations);
        }

        if (!$card = cards::getItem($this->request->getGet('card_id', 'intval'), NULL, NULL)) {
            showMsg("该卡不存在");
        }

        //得到支持的银行列表
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);

        self::$view->setVar('card', $card);
        self::$view->render('card_innertransfer');
    }

    public function deleteCard()
    {
        $locations = array(0 => array('title' => '返回银行卡列表', 'url' => url('card', 'cardList')));
        if (!$card_id = $this->request->getGet('card_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }

        if (!cards::deleteItem($card_id)) {
            showMsg("删除数据失败", 0, $locations);
        }
        //清除缓存
        if(cards::getCache($card_id))
        {
            cards::clearCache($card_id);
        }

        $cardInfo = cards::getItem($card_id);
        if($cardInfo && cards::getCache($cardInfo['card_num']))
        {
            cards::clearCache($cardInfo['card_num']);
        }

        showMsg("删除数据成功", 0, $locations);
    }



    /**
     * 卡组操作
     */
    //分卡模型框架
    public function allocGroup()
    {
        $locations = array(0 => array('title' => '返回分配银行卡列表', 'url' => url('card', 'allocGroup', array('sa' => 'rightList'))));
        //设定卡
        if ($this->request->getPost('submit', 'trim')) {
            $ref_group_ids = $this->request->getPost('ref_group_ids', 'array');
            if (!$ref_group_ids) {
                showMsg("请选择要替换的卡", 1, $locations);
            }

            foreach ($ref_group_ids as $user_id => $ref_group_id) {
                if ($ref_group_id == -1) {
                    continue;
                }
                users::updateItem($user_id, array('ref_group_id' => $ref_group_id));
            }

            showMsg("设定成功");
        }

        $sa = $this->request->getGet('sa', 'trim');
        if ($sa == '') {
            self::$view->render('card_allocgroup');
            exit;
        } elseif ($sa == 'leftTree') {
            self::$view->render('card_allocgroup_lefttree');
            exit;
        } elseif ($sa == 'rightList') {
            $parent_id = $this->request->getGet('parent_id', 'intval', 0);

            //得到银行分组
            self::$view->setVar('cardGroups', cards::getGroups());

            //显示直接下级
            $users = users::getItems($parent_id);
            //显示继承的ref_group_id
            $parents = users::getAllParent($parent_id, true);
            $inherited_group_id = 0;
            foreach ($parents as $v) {
                if ($v['ref_group_id'] > 0) {
                    $inherited_group_id = $v['ref_group_id'];
                    break;
                }
            }
            self::$view->setVar('inherited_group_id', $inherited_group_id);
            self::$view->setVar('users', $users);
            self::$view->render('card_allocgroup_rightlist');
            exit;
        }
    }

    public function groupList()
    {
        $key=$this->request->getGet('key','trim');
        if($key ==='show')
        {
            $ref_group_id=$this->request->getGet('ref_group_id','intval');
            if($ref_group_id <= 0)
            {
                showMsg("参数出错");
            }
            $curPage=$this->request->getGet('curPage','trim',1);
            if($curPage > 1)
            {
                $start=($curPage-1)*DEFAULT_PER_PAGE;
            }else{
                $start = 0;
            }
            $usersList=users::getItemsList(" and ref_group_id = $ref_group_id and status = 8",$start,DEFAULT_PER_PAGE);
            $num=users::getItemsListNum(" and ref_group_id = $ref_group_id and status = 8");

            self::$view->setVar('pageList', getPageList($num['num'], DEFAULT_PER_PAGE));
            self::$view->setVar('userList', $usersList);
            self::$view->render('card_grouplistshowpeople');die();
        }
//        $groups = cards::getGroups();
        //>>suthor snow 修改为使用统计数据
        $groups = cards::getGroupsExclude();
        self::$view->setVar('groups', $groups);
        self::$view->render('card_grouplist');
    }


    public function addGroup()
    {
        $locations = array(0 => array('title' => '返回卡组列表', 'url' => url('card', 'groupList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $status_value = $this->request->getPost('status', 'trim');
            $status_value = $status_value == 'on' ? 1 : 0;

            $is_fixed_value = $this->request->getPost('is_fixed', 'trim');
            $is_fixed_value = $is_fixed_value == 'on' ? 1 : 0;

            $data = array(
                'ref_group_id' => $this->request->getPost('ref_group_id', 'intval'),
                'name' => $this->request->getPost('name', 'trim'),
                'level_amount' => $this->request->getPost('level_amount', 'floatval'),
                'deposit_count' => $this->request->getPost('deposit_count', 'intval'),
                'commission_percentage' => $this->request->getPost('commission_percentage', 'floatval'),
                'egame_commission_percentage' => $this->request->getPost('egame_commission_percentage', 'floatval'),
                'sort' => $this->request->getPost('sort', 'intval'),
                'status' => $status_value,
                'is_fixed' => $is_fixed_value,
            );
            if(strpos($data['name'],'测试')!==false) {
                $res = cards::chkCardCeshiGroups();
                if (!empty($res)) {
                    showMsg("添加失败! 测试层级已经添加过了");

                }
            }
            if (!cards::addGroup($data)) {
                showMsg("添加卡组失败!请检查数据输入是否完整。");
            }

            //>>author snow 添加成功,删除缓存
            redisDelHashForKey($this->cardGroupsKey);

            showMsg("添加成功");
        }

        self::$view->render('card_addgroup');
    }

    public function editGroup()
    {
        $locations = array(0 => array('title' => '返回卡组列表', 'url' => url('card', 'groupList')));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            if (!$cg_id = $this->request->getPost('cg_id', 'intval')) {
                showMsg('参数无效');
            }

            $status_value = $this->request->getPost('status', 'trim');
            $status_value = $status_value == 'on' ? 1 : 0;
            $is_fixed_value = $this->request->getPost('is_fixed', 'trim');
            $is_fixed_value = $is_fixed_value == 'on' ? 1 : 0;
            $cg_id = $this->request->getPost('cg_id', 'intval');

            $data = array(
                'name' => $this->request->getPost('name', 'trim'),
                'level_amount' => $this->request->getPost('level_amount', 'floatval'),
                'deposit_count' => $this->request->getPost('deposit_count', 'intval'),
                'commission_percentage' => $this->request->getPost('commission_percentage', 'floatval'),
                'egame_commission_percentage' => $this->request->getPost('egame_commission_percentage', 'floatval'),
                'ref_group_id' => $this->request->getPost('ref_group_id', 'intval'),
                'sort' => $this->request->getPost('sort', 'intval'),
                'status' => $status_value,
                'is_fixed' => $is_fixed_value,
            );
            if(strpos($data['name'],'测试')!==false) {
                $res = cards::chkCardCeshiGroups($data['name']);
                if (!empty($res)) {
                    showMsg("修改失败! 测试层级已经存在");

                }
            }

            if (cards::getGroupByDepositAmount($cg_id, $data['level_amount'])) {
                showMsg("不同卡组，存款金额不能相同，没有数据被更新", 1, $locations);
            }

            if (!cards::updateGroup($cg_id, $data)) {
                showMsg("没有数据被更新", 1, $locations);
            }


            //>>author snow 添加成功,删除缓存
            redisDelHashForKey($this->cardGroupsKey);

            showMsg("更新成功", 0, $locations);
        }
        if (!$cg_id = $this->request->getGet('cg_id', 'intval')) {
            showMsg("参数无效");
        }
        if (!$group = cards::getGroup($cg_id)) {
            showMsg("该卡不存在");
        }

        self::$view->setVar('group', $group);
        self::$view->render('card_addgroup');
    }

    public function refreshGroup()
    {
        $locations = array(0 => array('title' => '返回卡组列表', 'url' => url('card', 'groupList')));

        //获取层级
        $cards_groups = cards::getGroups(1, 0, 1);
        $card_keys = "";
        $valid_cards_groups = array();
        $count = 0;

        foreach ($cards_groups as $key => $value) {
            if ($card_keys != "") {
                $card_keys .= ",";
            }

            $valid_cards_groups[$count++] = $value;
            $card_keys .= $key;
        }

        //获取需要更新的用户
        $users = users::getUsersForUpdateGroup($valid_cards_groups[0]['ref_group_id']);
        $newUsers = array();

        foreach ($cards_groups as $cards_groups_key => $cards_groups_value) {
            $newUsers[$cards_groups_key] = '';
        }

        foreach ($users as $users_key => $users_value) {
            if (!array_key_exists($users_value['ref_group_id'], $cards_groups)) {
                continue;
            }

            foreach ($cards_groups as $cards_groups_key => $cards_groups_value) {
                if ($users_value['total_amount'] >= $cards_groups_value['level_amount'] && $users_value['total_count'] >= $cards_groups_value['deposit_count']) {

                    if ($users_value['ref_group_id'] == $cards_groups_key) {
                        break;
                    }

                    if ($newUsers[$cards_groups_key] != '') {
                        $newUsers[$cards_groups_key] = $newUsers[$cards_groups_key] . ',';
                    }

                    $newUsers[$cards_groups_key] .= $users_key;
                    break;
                }
            }
        }

        if (users::updateUsersGroup($newUsers)) {
            showMsg("刷新成功", 0, $locations);
        } else {
            showMsg("刷新失败", 0, $locations);
        }
    }

    public function deleteGroup()
    {
        $locations = array(0 => array('title' => '返回卡组列表', 'url' => url('card', 'groupList')));
        if (!$cg_id = $this->request->getGet('cg_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }

        if (!cards::deleteGroup($cg_id)) {
            showMsg("删除数据失败", 0, $locations);
        }

        //>>author snow 删除缓存
        redisDelHashForKey($this->cardGroupsKey);
        showMsg("删除数据成功", 0, $locations);
    }

    public function editStatus()
    {
        $result = array('errno' => 0, 'errstr' => '');

        $status = $this->request->getPost('status', 'intval');
        $card_id = $this->request->getPost('card_id', 'intval');

        if (!$card_id && $status < 0) {
            $result['errno'] = -1;
            $result['errstr'] = '参数错误';
            die(json_encode($result));
        }
        $data = array(
            'status' => $status,
        );
        if (!cards::updateItem($card_id, $data)) {
            $result['errno'] = -1;
            $result['errstr'] = '状态更新失败';
            die(json_encode($result));
        }
         //清除缓存
        if(cards::getCache($card_id))
        {
            cards::clearCache($card_id);
        }

        $cardInfo = cards::getItem($card_id);
        if($cardInfo && cards::getCache($cardInfo['card_num']))
        {
            cards::clearCache($cardInfo['card_num']);
        }

        die(json_encode($result));
    }

    /* ------------------ 充值卡组管理开始 ------------------ */

    /**
     * 充值卡类别列表
     */
    public function depositGroupList()
    {
        $actionLinks = [['title' => '新增类别', 'url' => url('card', 'depositGroupAdd')]];
        $list = (new cardDepositGroup())
            ->order('sort asc')
            ->select();
        self::$view->setVar('list', $list);
        self::$view->setVar('actionLinks', $actionLinks);
        self::$view->render('card_depositgrouplist');
    }

    /**
     * 添加充值卡类别
     */
    public function depositGroupAdd()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $data = array(
                'group_name' => $this->request->getPost('group_name', 'trim'),
                'fee_rate' => $this->request->getPost('fee_rate', 'floatval'),
                'sort' => $this->request->getPost('sort', 'intval'),
                'status' => $this->request->getPost('status', 'intval'),
            );

            $id = (new cardDepositGroup())->insert($data);
            $locations = array(0 => array('title' => '返回充值类别列表', 'url' => url('card', 'depositGroupList')));
            if ($id !== false) {

                //>>snow 添加成功,删除更新redis 缓存
                $GLOBALS['redis']->del($this->usageListKey);

                response(['添加数据成功', 0, $locations], 'MSG');
            }
            response(['添加数据失败', 0, $locations], 'MSG');
        } else {
            self::$view->render('card_depositgroupedit');
        }
    }

    /**
     * 编辑充值卡类别
     */
    public function depositGroupUpdate()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $data = array(
                'group_name' => $this->request->getPost('group_name', 'trim'),
                'fee_rate' => $this->request->getPost('fee_rate', 'floatval'),
                'sort' => $this->request->getPost('sort', 'intval'),
                'status' => $this->request->getPost('status', 'intval'),
            );

            $cdg_id = $this->request->getPost('cdg_id', 'intval');

            $result = (new cardDepositGroup())->where(['cdg_id' => $cdg_id])->update($data);
            $locations = array(0 => array('title' => '返回充值类别列表', 'url' => url('card', 'depositGroupList')));
            if ($result !== false) {

                //>>author snow 编辑成功,删除缓存
                $GLOBALS['redis']->del($this->usageListKey);
                response(['修改数据成功', 0, $locations], 'MSG');
            }
            response(['修改数据失败', 0, $locations], 'MSG');
        } else {
            $cdg_id = $this->request->getGet('cdg_id', 'intval');
            $data = (new cardDepositGroup())->find($cdg_id);

            self::$view->setVar('data', $data);
            self::$view->render('card_depositgroupedit');
        }
    }

    /**
     * 删除充值卡类别
     */
    public function depositGroupDelete()
    {

    }
    /* ------------------ 充值卡组管理结束 ------------------ */


    /********************** snow 批量删除存款记录*********************************************/
    public function deleteCardMany()
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
                $sql = 'DELETE FROM `cards` WHERE card_id IN' . $whereIn;
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
