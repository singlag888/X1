<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：财务管理
 */
class finController extends sscController
{
    //不可信任卡组ID(硬编码与秒到系统对接)
    const CARD_GROUP_UNTRUSTED = 6;

    //可信任卡组ID
    const CARD_GROUP_TRUSTED = 7;

    //默认注册超过N天，变为可信任卡组ID
    const DEFAULT_TRUSTED_DAYS = 15;

    //默认工行收款日额度
    const DEFAULT_ICBC_DAY_DEPOSIT_LIMIT = 1000000;

    public static $ipWhiteList = array('1.1.1.1','2.2.2.2');

    //方法概览
    public $titles = array(
        'showAccount' => '显示收款卡',
        'depositList' => '存款记录',
        'withdrawList' => '提款记录',
        'promoList' => '优惠记录',
        'bindCard' => '绑定银行卡',
        'unBindCard' => '解绑银行卡',
        'lockCard' => '锁定银行卡',
        'deposit' => '我要存款',
        'pay' => '充值页面',
        'bankPay' => '银行卡充值',
        'wechatPay' => '银行卡充值',
        'alipayPay' => '支付宝充值',
        'onlinePay' => '网银充值',
        'companyPay' => '公司入款',
        'withdraw' => '我要提款',
        'orderList' => '个人账变',
        'teamOrderList' => '团队资金明细',
        'tranMoney' => '资金转移',
        'receiveRXWXPayResult' => '仁信微信回调',
        'receiveRXZFBPayResult' => '仁信支付宝回调',
        'receiveBFZFBPayResult' => '百付支付宝回调',
        'receiveBFWXPayResult' => '百付微信回调',
        'receiveBFZFBWPayResult' => '百付支付宝WAP回调',
        'receiveFQWXPayResult' => '付乾微信回调',
        'receiveFQWYPayResult' => '付乾网银回调',
        'receiveFQQQPayResult' => '付乾QQ回调',
        'receiveSBZFBPayResult' => '顺宝支付宝回调',
        'receiveSBWXPayResult' => '顺宝微信回调',
        'receiveXYPayResult' => '幸运支付回调',
        'receiveYBZFBPayResult' => '银宝支付宝回调',
        'receiveYBWXPayResult' => '银宝微信回调',
        'receiveXMPayResult' => '熊猫支付回调',
        'receiveUFZFBPayResult' => 'U付支付宝回调',
        'receiveUFWXPayResult' => 'U付微信回调',
        'receiveSFWXPayResult' => '瞬付微信支付回调',
        'receiveSFZFBPayResult' => '瞬付支付宝支付回调',
        'receiveSFQQPayResult' => '瞬付QQ支付回调',
        'receiveSFZFBWPayResult' => '瞬付支付宝WAP支付回调',
        'receiveSFQQWPayResult' => '瞬付QQWAP支付回调',
        'receiveGTZFBPayResult' => '高通支付宝回调',
        'receiveGTWXPayResult' => '高通微信回调',
        'receiveLBWXPayResult' => '萝卜微信回调',
        'receiveLBWYPayResult' => '萝卜网银回调',
        'receivePayResult' => '第三方回调',
        'checkHasCard' => '检查用户是否绑定银行卡',
        'rechargeWithdraw'=>'个人充提',
        'rechargeWithdrawMenu'=>'个人充提菜单'
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //ajax调用 显示工行秒到帐号
    public function showAccount()
    {
        $bankId = $this->request->getGet('bankId', 'trim', '1');
        $usage = $this->request->getGet('usage', 'trim');


        // 140713 临时使用 新注册的用户暂不给工行充值方式
        // if ($bankId == 1) {
        //     $user = users::getItem($GLOBALS['SESSION']['user_id']);
        //     if (strtotime($user['reg_time']) > strtotime('-3 days')) {
        //         $result['errno'] = 1;
        //     }
        // }

        /**推广阶段暂时对所有人员开放工行start**/
        $user = users::getItem($GLOBALS['SESSION']['user_id']);
        $cards = cards::getRandSaveBankCards($user['ref_group_id']);
        if($cards){
            $randCard = array_splice($cards, rand(0, count($cards)-1), 1);
            $result['errno'] = 0;
            $result['card_num'] = $randCard[0]['card_num'];
            $result['card_name'] = $randCard[0]['card_name'];
            $result['bank_id'] = $randCard[0]['bank_id'];
            $result['bank_name'] = $GLOBALS['cfg']['bankList'][$randCard[0]['bank_id']];
            $result['postscript'] = $GLOBALS['SESSION']['user_id'];
        } else {
            $result['errno'] = 1;
        }

        echo json_encode($result);die;
        /**推广阶段暂时对所有人员开放工行end**/
        if ($result['errno'] == 0) {
            // 3支付宝等转到银行卡' 本地卡
            if ($usage == 3) {
                $cards = cards::getItems(1, 0, $usage, '', 2);
                if ($cards) {
                    $card = reset($cards);
                    $result['errno'] = 0;
                    $result['card_num'] = $card['card_num'];
                    $result['card_name'] = $card['card_name'];
                    $result['bank_id'] = $card['bank_id'];
                    $result['bank'] = $GLOBALS['cfg']['bankList'][$card['bank_id']];
                }
                else {
                    $result['errno'] = 1;
                }
            }
            else {
                //140710 只给信任的代理帐号
                //工行充值渠道风险控制
                //如果没有设置任何父级卡组则默认为不可信任
                $refGroupId = self::CARD_GROUP_UNTRUSTED;
                if ($bankId == 1) {
                    $parentUsers = users::getAllParent($GLOBALS['SESSION']['user_id'], true);
                    //150701 允许总代降级后 有可能一代比总代ID小 上级ID不一定比下级大 因此不能按ID作为排序依据
                    foreach ($parentUsers as $v) {
                        if ($v['ref_group_id']) {
                            $refGroupId = $v['ref_group_id'];
                            break;
                        }
                    }
                }

                /*
                 * 工行充值渠道风险控制 业务逻辑
                 * 系统控制参数：对注册时间不足 #多少天# 的客户，显示的是组别A中的充值卡卡号，
                 * 默认值15天
                 */
                $config = config::getConfigs(array('icbc_trust_day_limit'), self::DEFAULT_TRUSTED_DAYS);

                if(!is_numeric($config['icbc_trust_day_limit']) || $config['icbc_trust_day_limit'] < 0) {
                    $config['icbc_trust_day_limit'] = self::DEFAULT_TRUSTED_DAYS;
                }
                //父卡组是可信卡组  && 当前时间与注册时间的秒差大于设置时限
                $user = users::getItem($GLOBALS['SESSION']['user_id']);
                $time_diff = time() - strtotime($user['reg_time']);
                if($refGroupId == self::CARD_GROUP_TRUSTED && $time_diff >= $config['icbc_trust_day_limit'] * 24 * 3600) {
                    $refGroupId = self::CARD_GROUP_TRUSTED;
                }
                else {
                    $refGroupId = self::CARD_GROUP_UNTRUSTED;
                }

                $result = autoDeposits::getUsingCard($bankId, $refGroupId);
            }
        }

        if (!$result || !is_array($result) || !isset($result['errno'])) {
            $result = array('errno' => '-1', 'errstr' => '系统出错，请稍候再试');
        }
        if ($result['errno'] != 0) {
            $result = array('errno' => $result['errno'], 'errstr' => '系统繁忙，请稍候再试');
        }

        echo json_encode($result);
        exit;
    }

    //用户存款记录
    public function depositList()
    {
        $include_childs = $this->request->getGet('include_childs', 'intval', 1);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');

        $startDate = date('Y-m-d 00:00:00', strtotime('-60 days'));
        $endDate = date('Y-m-d 23:59:59');
        if(($username = $this->request->getGet('username', 'trim')) && $username != $GLOBALS['SESSION']['username'])
        {
            if(!$user = users::getItem($username,8,false,1,1)){
                showMsg("非法请求，该用户不存在或已被冻结");
            }
            if(!in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))){
                showMsg("非法请求，此用户不是你的下级");
            }
            $userId = $user['user_id'];
            $username = $user['username'];
//            $include_childs = 0;
        }else{
            $userId = $GLOBALS['SESSION']['user_id'];
            $username = $GLOBALS['SESSION']['username'];
//            $include_childs = 1;
        }
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'],8,false,1,1)) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        $orderBy = '  a.deposit_id DESC';
        if($sortKey) {
            $orderBy = (in_array($sortKey, ['user_id', 'username', 'level']) ? ' b.' : ' a.') . $sortKey . ($sortDirection == 1 ? ' ASC' : ' DESC');
        }
        $trafficInfo = deposits::getTrafficInfo($userId, $include_childs, -1, -1, 8, 0, 0, 0, 0, 0, '', $startDate, $endDate,'','',1,$orderBy);

        /***************** snow  获取正确的分页开始****************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /***************** snow  获取正确的分页开始****************************/
        $deposits = deposits::getItems($userId, $include_childs, -1, -1, 8, 0, 0, 0, 0, 0, '', $startDate, $endDate,$startPos,DEFAULT_PER_PAGE,'','',1,$orderBy);


        foreach ($deposits as $k => $v) {
            $deposits[$k]['wrap_id'] = deposits::wrapId($v['deposit_id']);
        }
        $tradeTypes = [1=>'网转', 2=>'ATM有卡转账',3=>'自助终端转账', 4=>'手机网转', 5=>'ATM无卡现存', 6=>'柜台汇款', 7=>'跨行汇款'];
        $status = [0=>'未处理', 1=>'已受理', 2=>'已审核', 3=>'机器正在受理', 4=>'需要人工干预', 8=>'已成功', 9=>'因故取消'];
        //预设查询值
        self::$view->setVar('tradeTypes', $tradeTypes);
        self::$view->setVar('status', $status);
        self::$view->setVar('username', $username);
        self::$view->setVar('sortKey', $sortKey);
        self::$view->setVar('sortDirection', $sortDirection);
        self::$view->setVar('newDeposits', $deposits);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('user', $user);
        self::$view->setVar('pageList', getPageListMobile($trafficInfo['count']));
        self::$view->render('fin_depositlist');
    }

    //用户取款记录
    public function withdrawList()
    {
        $include_childs = $this->request->getGet('include_childs', 'intval', 1);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $status = $this->request->getGet('status', 'intval', -1);
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        $startDate = date('Y-m-d 00:00:00', strtotime('-60 days'));
        $endDate = date('Y-m-d 23:59:59');
        $withdrawStatus = [7=>'正在处理', 8=>'结算成功', 9=>'结算失败'];

        self::$view->setVar('status', $status);
        if($status != -1){
            $status = in_array($status, [8,9]) ? $status : [0,1,2,3,4];
        }

        if(($username = $this->request->getGet('username', 'trim')) && $username != $GLOBALS['SESSION']['username'])
        {
            if(!$user = users::getItem($username,8,false,1,1)){
                showMsg("非法请求，该用户不存在或已被冻结");
            }
            if(!in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))){
                showMsg("非法请求，此用户不是你的下级");
            }
            $userId = $user['user_id'];
            $username = $user['username'];
//            $include_childs = 0;
        }else{
            $userId = $GLOBALS['SESSION']['user_id'];
            $username = $GLOBALS['SESSION']['username'];
//            $include_childs = 1;
        }
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'],8,false,1,1)) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        $orderBy = '  a.withdraw_id DESC';
        if($sortKey) {
            $orderBy = (in_array($sortKey, ['user_id', 'username', 'level']) ? ' b.' : ' a.') . $sortKey . ($sortDirection == 1 ? ' ASC' : ' DESC');
        }
        $trafficInfo = withdraws::getTrafficInfo($userId, $include_childs, -1, $status, 0, 0, 0, '', '', $startDate, $endDate,-1,$orderBy);

        /***************** snow  获取正确的分页开始****************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /***************** snow  获取正确的分页开始****************************/
        $withdraws = withdraws::getItems($userId, $include_childs, -1, $status, 0, 0, 0, '', '', $startPos, DEFAULT_PER_PAGE, $startDate, $endDate,'','',-1,$orderBy);

        foreach ($withdraws as $k => $v) {
            $withdraws[$k]['wrap_id'] = withdraws::wrapId($v['withdraw_id']);
        }
        self::$view->setVar('withdrawStatus', $withdrawStatus);
        //预设查询值
        self::$view->setVar('sortKey', $sortKey);
        self::$view->setVar('sortDirection', $sortDirection);
        self::$view->setVar('newWithdraws', $withdraws);
        self::$view->setVar('trafficInfo', $trafficInfo);
        self::$view->setVar('include_childs', $include_childs);
        self::$view->setVar('username', $username);
        self::$view->setVar('errors', withdraws::getWithdrawErrors());
        self::$view->setVar('pageList', getPageListMobile($trafficInfo['count'], DEFAULT_PER_PAGE, 1, 5));
        self::$view->render('fin_withdrawlist');
    }

    //用户优惠记录 不包括手续费优惠
    public function promoList()
    {
        $startDate = $this->request->getGet('startDate', 'trim', date('Y-m-d'));
        $endDate = $this->request->getGet('endDate', 'trim', date('Y-m-d'));
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        if ($startDate < date('Y-m-d', strtotime('-7 days'))) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        if ($endDate < date('Y-m-d', strtotime('-7 days'))) {
            $endDate = date('Y-m-d', strtotime('-7 days'));
        }
        if ($startDate >= $endDate) {
            $startDate = $endDate;
        }

        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        $trafficInfo = promos::getTrafficInfo($GLOBALS['SESSION']['username'], 0, -1, 3, 0, '',"{$startDate} 00:00:00", "$endDate 23:59:59");
        /***************** snow  获取正确的分页开始****************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /***************** snow  获取正确的分页开始****************************/

        $promos = promos::getItems($GLOBALS['SESSION']['username'], 0, -1, 3, 0, "{$startDate} 00:00:00", "$endDate 23:59:59", '', $startPos, DEFAULT_PER_PAGE); //状态为3,只显示成功执行的优惠
        foreach ($promos as $k => $v) {
            $promos[$k]['wrap_id'] = promos::wrapId($v['promo_id']);
        }

        //预设查询值
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        self::$view->setVar('promos', $promos);
        self::$view->setVar('user', $user);
        self::$view->setVar('pageList', getPageListMobile($trafficInfo['count'], DEFAULT_PER_PAGE, 1, 5));
        self::$view->render('fin_promolist');
    }

    /**
     * 锁定银行卡
     * @throws exception2
     *
     */
    public function lockCard(){
        $userId = $GLOBALS['SESSION']['user_id'];

        $bankCardId = $this->request->getGet('bind_card_id', 'trim');
        $cards = userBindCards::getItem($bankCardId, 0, null);
        if(!$cards || $cards['user_id'] != $userId){
            showMsg("银行卡错误!");
        }
        if($cards['status'] != 3){
            showMsg("此银行卡不能锁定!");
        }
        if (userBindCards::updateItem($bankCardId, ['status' => 1, 'frozen_ts' => 0, 'ts' => date('Y-m-d H:i:s')])) {
            showMsg("锁定成功", 1);
        }
        showMsg("锁定失败");
    }

    /**
     * 银行卡解绑
     */
    public function unBindCard()
    {
        if ($this->request->getPost('submit', 'trim'))
        {
            $userId = $GLOBALS['SESSION']['user_id'];
            $bankCardId = $this->request->getPost('bind_card_id', 'intval');
            $safePwd = $this->request->getPost('safe_pwd', 'trim');
            $verifyCode = $this->request->getPost('verifyCode', 'trim');

            if (strtoupper($verifyCode) !== strtoupper($GLOBALS['SESSION']['verifyCode'])) {
                showMsg("验证码错误!");
            }
            if(!$cards = userBindCards::getItem($bankCardId)){
                showMsg("银行卡不存在!");
            }
            if($cards['user_id'] != $userId){
                showMsg("银行卡错误!");
            }
            $users = users::getItem($userId);
//            users::checkSafePwd($users['safe_pwd'], $safePwd);
            if(userBindCards::updateItem($bankCardId, ['status'=>3, 'frozen_ts'=>time()])){
                showMsg("解绑成功", 1);
            }
            showMsg("解绑失败");

        }else{
            $bankCardId = $this->request->getGet('bind_card_id', 'trim');
            if(!$userBindCard = userBindCards::getItem($bankCardId)){
                showMsg("银行卡不存在!");
            }
            $userBindCard['card_num'] = str_repeat('*', 16) . substr($userBindCard['card_num'], -3);

            self::$view->setVar('withdrawBankList', $GLOBALS['cfg']['withdrawBankList']);

            self::$view->setVar('userBindCard', $userBindCard);

            self::$view->render('fin_unbindcard');
        }
    }

    //绑定银行卡
    public function bindCard()
    {
        $userId = $GLOBALS['SESSION']['user_id'];
        $locations = array(0 => array('title' => '返回列表', 'url' => url('fin', 'bindCard')));
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        //修改数据
        if ('doBindCard' == $this->request->getPost('op', 'trim')) {
            /*            if (!$secpassword = $this->request->getPost('secpassword', 'trim')) {
                            showMsg("请输入资金密码");
                        }
                        if (strlen($secpassword) < 6 || strlen($secpassword) > 15 || preg_match('`^\d+$`', $secpassword) || preg_match('`^[a-zA-Z]+$`', $secpassword)) {
                            showMsg("资金密码不对");
                        }*/


            $bind_bank_id = $this->request->getPost('bind_bank_id', 'intval');
            $bind_bank_username = $this->request->getPost('bind_bank_username', 'trim');
            $bind_card_num = $this->request->getPost('bind_card_num', 'trim');
            $bind_card_num2 = $this->request->getPost('bind_card_num2', 'trim');
            $province = $this->request->getPost('province', 'trim');
            $city = $this->request->getPost('city', 'trim');
            $branch_name = $this->request->getPost('branch_name', 'trim');

            if($bind_card_num != $bind_card_num2){
                showMsg("银行卡号和确认银行卡号不一致");
            }

            //换绑
            if($bind_card_id = $this->request->getPost('bind_card_id', 'intval'))
            {
                $userBindCard = userBindCards::getItem($bind_card_id);

                if(!$userBindCard || !$userBindCard['frozen_ts'] || $userBindCard['user_id'] != $userId || $userBindCard['status'] != 3){
                    showMsg("换绑无效");
                }
                if(intval($userBindCard['frozen_ts']) < time()+3*24*3600){
                    showMsg('正在解锁中,请稍后换绑');
                }
                $data = [
                    'bank_id' => $bind_bank_id,
                    'bank_username' => $bind_bank_username,
                    'card_num' => $bind_card_num,
                    'province' => $province,
                    'city' => $city,
                    'branch' => $branch_name,
                    'status' => 1,
                ];
                if (($result = userBindCards::changeBindCard($bind_card_id, $data)) !== true) {
                    showMsg($result);
                }
            }
            //绑定
            else{


                //>>author snow 先查询数据库,是否已经绑定了卡,防止绑定多张卡
                $bindCards = userBindCards::getItems($userId, 0, '', [1,3]);

                if (!empty($bindCards)) {
                    //>>说明已经有卡了,不能再进行卡绑定操作
                    showMsg("已经绑定银行卡,换绑请联系客服");
                }

                if (($result = userBindCards::bindCard($userId, $bind_bank_id, $bind_card_num, $province, $city, $branch_name, $bind_bank_username)) !== true) {
                    showMsg($result);
                }
            }
            //SEO 活动注册并绑定银行卡成功送礼金
            //不是最有效率的写法，有New对象的开销，但是为了模块化设计参数必须传递，必须New对象
            //而不是写死在对象里面的静态方法中，这样可以做到以后变成后台通过DB维护参数时不需要改动任何代码
            //$GLOBALS['mc']->set('seo', date("Ymd"), 0, 60 * 60 * 24);
            $seoObject = userGiftsControl::registPromo('seo');
            if($seoObject instanceof userGiftsBase) {
                $seoObject->perform($user);
            }

            showMsg("绑卡成功，可直接充值体验游戏。");
        }

        //得到支持银行列表  去掉支付宝 ，财付通
        unset($GLOBALS['cfg']['withdrawBankList'][101], $GLOBALS['cfg']['withdrawBankList'][102]);
        self::$view->setVar('withdrawBankList', $GLOBALS['cfg']['withdrawBankList']);

        $bindCards = userBindCards::getItems($GLOBALS['SESSION']['user_id'], 0, '', array(1,3));
        foreach ($bindCards as &$v) {
            $v['card_num'] = str_repeat('*', 16) . substr($v['card_num'], -4);
            $subLen = mb_strlen($v['bank_username'],'utf-8')-1;
            $v['bank_username'] = '*' . mb_substr($v['bank_username'], 1, $subLen, 'utf-8');
        }

        unset($v);
        self::$view->setVar('bindCards', $bindCards);
        self::$view->setVar('bind_card_id', $this->request->getGet('bind_card_id', 'intval'));

        self::$view->setVar('user', $user);
        self::$view->render('fin_bindcard');
    }

    //充值界面
    public function pay()
    {
        self::$view->render('fin_pay');
    }

    //充值界面
    public function bankPay()
    {
        // 140713 临时使用 新注册的用户暂不给工行充值方式
        // if ($bankId == 1) {
        //     $user = users::getItem($GLOBALS['SESSION']['user_id']);
        //     if (strtotime($user['reg_time']) > strtotime('-3 days')) {
        //         $result['errno'] = 1;
        //     }
        // }

        /**推广阶段暂时对所有人员开放工行start**/
        $user = users::getItem($GLOBALS['SESSION']['user_id']);

        /******************* snow 添加判断 ,如果查询不到用户数据,跳转到登录页面 start***************************************/
        if (empty($user)) {
            //>>跳转到登录页面
            showMsg("非法请求，该用户不存在或已被冻结");
            $url = getUrl() . "?a=login";
            $str = "<script>window.parent.location.href = '$url'; </script>";
            echo $str;
            exit;

        }
        /******************* snow 添加判断 ,如果查询不到用户数据,跳转到登录页面 end  ***************************************/
        $cards = cards::getCompanyPayCards($user['ref_group_id']);
        $result['qq_number'] = Config::getConfig('qq_number');
        if ($cards) {
            $result['errno'] = 0;
            foreach ($cards as $k => $v) {
                $cards[$k]['bank_name'] = $GLOBALS['cfg']['bankList'][$v['bank_id']];
                $cards[$k]['postscript'] = 'ID' . $user['user_id'] . '（转账时请复制此附言，方便客服查询确认）';
            }
        } else {
            $result['errno'] = 1;
        }
//        if($cards){
//            $randCard = array_splice($cards, rand(0, count($cards)-1), 1);
//            $result['errno'] = 0;
//            $result['card_id'] = $randCard[0]['card_id'];
//            $result['card_num'] = $randCard[0]['card_num'];
//            $result['card_name'] = $randCard[0]['card_name'];
//            $result['bank_id'] = $randCard[0]['bank_id'];
//            $result['bank_name'] = $GLOBALS['cfg']['bankList'][$randCard[0]['bank_id']];
//            $result['postscript'] = 'ID' . $user['user_id'] . '（转账时请复制此附言，方便客服查询确认）';
//            $result['shop_url'] = $randCard[0]['shop_url'];
//            $result['username'] = $user['username'];
//        } else {
//            $result['errno'] = 1;
//        }
        self::$view->setVar('bankList', $GLOBALS['cfg']['bankList']);
        self::$view->setVar('result', $result);
        self::$view->setVar('cards', $cards);
        self::$view->render('fin_bankpay');
    }
    public function rechargeWithdraw()
    {

        $key=$this->request->getGet('key', 'string','recharge');
        $startDate = date('Y-m-d 00:00:00', strtotime('-60 days'));
        $endDate = date('Y-m-d 23:59:59');
        $user_id=$GLOBALS['SESSION']['user_id'];
        $curPage=$this->request->getGet('curPage','trim',1);
        if($curPage > 1)
        {
            $start=($curPage-1)*DEFAULT_PER_PAGE;
        }else{
            $start = 0;
        }
        if(intval($user_id) <= 0)
        {
            die('请先登陆!');
        }
        $num = 0;
        if($key === 'recharge')
        {
            $num=deposits::getItemsListNum($user_id,0,-1,-1,-1,0,0,0,0,0,'',$startDate,$endDate);
            $list = deposits::getItemsList($user_id,0,-1,-1,-1,0,0,0,0,0,'',$startDate,$endDate,$start,DEFAULT_PER_PAGE);
        }elseif ($key === 'withdraw')
        {
            $num=withdraws::getItemsListNum($user_id,0,-1,-1,0,0,0,$startDate,$endDate);
            $list=withdraws::getItems($user_id,0,-1,-1,0,0,0,$startDate,$endDate,$start);
        }

        self::$view->setVar('key',$key);
        self::$view->setVar('list',$list);
        self::$view->setVar('pageList', getPageListMobile($num, DEFAULT_PER_PAGE));
        self::$view->render('fin_rechargeWithdraw');
    }
    public function rechargeWithdrawMenu(){

        self::$view->render('fin_rechargeWithdrawMenu');
    }
    public function wechatPay()
    {
      self::$view->render('fin_wechatpay');
    }

    public function alipayPay()
    {
       self::$view->render('fin_alipay');
    }

    public function onlinePay()
    {
        self::$view->render('fin_onlinepay');
    }
    public function companyPay()
    {
        self::$view->render('fin_companypay');
    }

    //我要存款 户汇完款自动到帐 不需提交了
    public function deposit()
    {

        $gflag = $this->request->getGet('flag', 'trim', '');

        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        $this->_checkWithdraw($gflag, 0, '', '');
        $op = $this->request->getPost('op', 'trim');
        /******************* snow 添加获取默认最大值*************************/
        $min_deposit_limit = config::getConfig('min_deposit_limit');
        /******************* snow 添加获取默认最大值*************************/
        $max_deposit_limit = config::getConfig('max_deposit_limit');

        if ('autoAddCase' == $op){
            if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
                die(json_encode(array("errno"=>101,"errstr" => '非法用户')));
            }

            $bankFlag = $this->request->getPost('flag', 'trim', '');

            if ($bankFlag == 'BP') {
                $bank_id = $this->request->getPost('bank_id', 'intval');
                $card_id = $this->request->getPost('card_id', 'intval');
            } else {
                $bank_id = $this->request->getPost('bank_id', 'trim');
                $bank_id = authcode($bank_id, 'DECODE', 'a6sbe!x4^5d_ghd');
                $card_id = $this->request->getPost('card_id', 'trim');
                $card_id = authcode($card_id, 'DECODE', 'a6sbe!x4^5d_ghd');
            }

            $deposit_amount = $this->request->getPost('deposit_amount', 'floatval');
            $remark = $this->request->getPost('remark', 'trim');
            /*************** snow 添加获取是否有支付账号*******************************/
            $pay_account_id = $this->request->getPost('pay_account_id','trim');
            /*************** snow 添加获取是否有支付账号*******************************/
//            if(empty($remark))
//            {
//                die(json_encode(array("errno"=>105, "errstr"=>"请输入扫码时的昵称或者账号")));
//            }
//            $json = stripslashes(config::getConfig('pay_flag'));
//            $flag = isset(json_decode($json,true)[$bank_id]) ? json_decode($json,true)[$bank_id] : "";
            $flag = isset($GLOBALS['cfg']['pay_flag'][$bank_id]) ? $GLOBALS['cfg']['pay_flag'][$bank_id] : 'BP';
            $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
            if(in_array($flag,['XIDAKEJIWX','XIDAKEJIWXW','XIDAKEJIZFB','XIDAKEJIZFBW','XIDAKEJIWY','CHANGCHENGFUWX','CHANGCHENGFUWXW','CHANGCHENGQQ'])){
                $min_deposit_limit=10;
            }elseif(in_array($flag,['YUNXUNWX','YUNXUNQQ','YUNXUNWY'])){
                $min_deposit_limit=30;
            }
            if(in_array($flag,['CAIFUBAOZFB','CAIFUBAOWX','CAIFUBAOQQ'])){
                $tmp=(int)($deposit_amount*100);
                if(!is_int($tmp/100)){
                    die(json_encode(array("errno"=>103, "errstr"=>"充值金额必须为整数!，无法提交")));
                }
            }
            if ($deposit_amount < $min_deposit_limit) {
                die(json_encode(array("errno"=>103, "errstr"=>"金额低于最低充值限额{$min_deposit_limit}，无法提交")));
            }
            if(!$card = cards::getItem($card_id, NULL, 2)){
                die(json_encode(array("errno"=>105, "errstr"=>"此充值卡不存在或已禁用")));
            }
			if($deposit_amount  >= 10 && $card['discount'] > '0' && $card['discount'] < 10){
				$deposit_amount = intval($deposit_amount * $card['discount'] / 10 );
			}
            if ($card['not_integer'] == 1 && !strpos((string)($deposit_amount), '.')) {
                $deposit_amount -= (mt_rand(1, 99) / 100.0);
            }

            if ($card['not_zero'] == 1 && $deposit_amount  >= 10 && $deposit_amount % 10 == 0) {
                $deposit_amount -= mt_rand(1, 4);
            };

            if ($card['not_integer'] == 1) {
                $deposit_amount = number_format($deposit_amount,2, '.', '');
            }

            if ($card['direct_display']) {
                $postData = array(
                    'deposit_amount' => $deposit_amount,
                    'username' => $this->request->getPost('username', 'trim'),
                    'user_id' => $this->request->getPost('user_id', 'intval'),
                    'card_id' => $card_id,
                    'bank_id' => $bank_id,
                    'codes' => $this->request->getPost('codes', 'trim'),
                    'requestURI' => $this->request->getPost('requestURI', 'trim'),
                    'call_back_url' => $this->request->getPost('call_back_url', 'trim'),
                    'shop_order_num' => $shop_order_num,
                    'th_ts' => $this->request->getPost('th_ts', 'trim'),
                    'netway' => $this->request->getPost('netway', 'trim'),
                    'hash' => $this->request->getPost('hash', 'trim'),
                );
            }

            $errstr = '';
			//dd($flag);
            switch ($flag) {

		case 'LBQQ':
                case 'LBZFB':
                case 'LBWY':
                case 'LBQQ_WAP':
                case 'LBQQWAP':
                case 'LBWX_WAP':
                case 'LBWXWAP':
                case 'LBZFB_WAP':
                case 'LBZFBWAP':
            	case 'TSWXWAP':
                case 'TSZFBWAP':
                case 'TSQQWAP':
                case 'TSJDWAP':
		case 'TSWX_WAP':
                case 'TSZFB_WAP':
                case 'TSQQ_WAP':
                case 'TSJD_WAP':
                case 'TSWX':
                case 'TSQQ':
                case 'TSZFB':
                case 'TSJD':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $shop_order_num = substr($shop_order_num,0,30);
                    break;
                case 'XMZF':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'BFZFB':
                    $maxDeposit = 2999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 2);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'BFWX':
                    $maxDeposit = 9999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 2);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'BFZFBW':
                    $maxDeposit = 2999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 2);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'FQWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'FQQQ':
                case 'SBWX':
                case 'SBZFB':
                    $maxDeposit = 2999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'SBWY':
                    $maxDeposit = 10000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'RXWX':
                case 'RXWXW':
                case 'RXZFB':
                case 'RXZFBW':
                case 'RXQQ':
                case 'RXQQW':
                case 'RXJD':
                case 'RXJDW':
                case 'RXCFT':
                case 'RXCFTW':
                case 'FQWX':
                case 'XY':
                    $maxDeposit = 49999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }

                    if ($card['direct_display']) {
                        $qrcode_url = $this->xingyunPayDirect($postData);
                    }
                    break;
                case 'YBZFB':
                case 'YBWX':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'UFZFB':
                case 'UFWX':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'SFWX':
                case 'SFZFB':
                case 'SFQQ':
                case 'SFZFBW':
                case 'SFQQW':
                case 'SFWXW':
                case 'SFJD':
                case 'SFJDW':
                case 'SFYL':
                    $maxDeposit = 3000;

                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }

                    if ($card['direct_display']) {
                        $qrcode_url = $this->shunfuPay($postData);

                        if (strpos($qrcode_url, '|') !== false) {
                            $errstr = explode('|', $qrcode_url)[1];
                        }
                    }

                    break;
                case 'GTZFBW':
                case 'GTQQW':
                case 'GTWXW':
                case 'GTZFB':
                case 'GTQQ':
                case 'GTWY':
                case 'GTWX':
                    $maxDeposit = 4999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'LBWX':
                case 'LBZFB':
                case 'LBQQ':
                case 'LBQQWAP':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    $shop_order_num = substr($shop_order_num,0,30);
                    break;
                case 'LBWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'JFKZFB_WAP':
                case 'JFKQQ_WAP':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'JFKWX':
                case 'JFKZFB':
                case 'JFKQQ':
                case 'JFKZWY':
                case 'ZFWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
		case 'TSWX_WAP':
                case 'TSZFB_WAP':
                case 'TSQQ_WAP':
                case 'TSJD_WAP':
                case 'TSWX':
                case 'TSQQ':
                case 'TSZFB':
                case 'TSJD':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $shop_order_num = substr($shop_order_num,0,30);
                    break;


                case 'JLWX':
                case 'JLQQ':
                case 'TSZFBWAP':
                case 'TSWXWAP':
                case 'TSWX_WAP':
                case 'TSZFB_WAP':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $shop_order_num = substr($shop_order_num,0,30);
                    break;
		    
		    
		
                case 'ZFQQ':
                case 'ZFWX':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'DDBWXW':
                case 'DDBWX':
                case 'DDBZFB':
                case 'DDBQQ':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'DDBWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'QCWX':
                case 'QCZFB':
                case 'QCWY':
                case 'QCQQ':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'YFZFB':
                case 'YFZFBW':
                case 'YFWX':
                case 'YFQQ':
                case 'YFWXW':
                    $temp_card_id = str_pad($card_id,8,'0',STR_PAD_LEFT);
                    $shop_order_num =  date("ymdHis") . $temp_card_id;

                    if ($card['direct_display']) {
                        $postData['shop_order_num'] = $shop_order_num;
                        $qrcode_url = $this->yifuPay($postData);

                        if (strpos($qrcode_url, '|') !== false) {
                            $errstr = explode('|', $qrcode_url)[1];
                        }
                    }

                    break;
                case 'YFTDWX':
                case 'YFTDWXW':
                case 'YFTDZFB':
                case 'YFTDQQ':
                case 'YFTDBD':
                    $maxDeposit = 10000;

                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }

                    if ($deposit_amount % 10 == 0) {
                        die(json_encode(array("errno"=>104, "errstr"=>"会员朋友，因微信风控，充值金额个位数不能为０，如您充值10000可改为10001")));
                    }

                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'YFTDWY':
                    $maxDeposit = 50000;

                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }

                    if ($deposit_amount % 10 == 0) {
                        die(json_encode(array("errno"=>104, "errstr"=>"会员朋友，因微信风控，充值金额个位数不能为０，如您充值10000可改为10001")));
                    }

                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'KLWX':
                case 'KLZFB':
                case 'KLWY':
                    $maxDeposit = 3000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'WFTWX':
                case 'WFTZFB':
                    $maxDeposit = 3000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'WFTQQ':
                    $maxDeposit = 10000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'WFTWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'WZYWX':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'YFTWX':
                case 'YFTWXW':
                    $maxDeposit = 3000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . date("YmdHis");
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'YFTZFB':
                case 'YFTZFBW':
                    $maxDeposit = 20000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . date("YmdHis");
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'HBTWXW':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }

                    if ($card['direct_display']) {
                        $qrcode_url = $this->huibaotongPay($postData);

                        if (strpos($qrcode_url, '|') !== false) {
                            $errstr = explode('|', $qrcode_url)[1];
                        }
                    }

                    break;
                case 'XJWX':
                case 'XJZFB':
                case 'XJWXW':
                case 'XJZFBW':
                    $maxDeposit = 10000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }

                    if ($card['direct_display']) {
                        $qrcode_url = $this->xiangjiaoPay($postData);

                        if (strpos($qrcode_url, '|') !== false) {
                            $errstr = explode('|', $qrcode_url)[1];
                        }
                    }

                    break;
                case 'YBZFWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'YAFWX':
                case 'YAFZFB':
                case 'YAFGZH':
                case 'YAFQQ':
                case 'YAFJD':
                case 'YAFWXW':
                case 'YAFYL':
                    $maxDeposit = 10000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'HOWX':
                case 'HOWXW':
                    $maxDeposit = 1000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'HOZFB':
                case 'HOZFBW':
                    $maxDeposit = 1500;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'HOWYW':
                case 'HOWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'HOWYKJ':
                case 'HOWYKJW':
                    $maxDeposit = 2000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'HOQQ':
                case 'HOQQW':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case '3KZFBW':
                case '3KWXW':
                    $maxDeposit = 10000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'QYFWX':
                case 'QYFZFB':
                case 'QYFZFBW':
                case 'QYFQQ':
                case 'QYFQQW':
                case 'QYFWXW':
                    $maxDeposit = 10000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = date("YmdHis") . '_' . $card_id . '_' . $flag . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'LDWX':
                    $maxDeposit = 20000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'LDWY':
                case 'LDZFB':
                    $maxDeposit = 20000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'ZTBWX':
                case 'ZTBQQ':
                case 'ZTBZFB':
                case 'ZTBWXW':
                case 'ZTBQQW':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'YYWX':
                case 'YYZFB':
                case 'YYQQ':
                case 'YYWY':
                    $maxDeposit = 49995;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'YZWX':
                    $maxDeposit = 3000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $temp_card_id = str_pad($card_id, 4, '0', STR_PAD_LEFT);
                     $shop_order_num =  rand(1000, 9999).time(). $card_id;
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'YZZFB':
                case 'YZZFBW':
                    $maxDeposit = 19900;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $temp_card_id = str_pad($card_id, 4, '0', STR_PAD_LEFT);
                    $shop_order_num =  rand(1000, 9999).time(). $card_id;
                    $postData['shop_order_num'] = $shop_order_num;
                    break;
                case 'UFUQQ':
                case 'UFUWY':
                case 'UFUZFB':
                case 'UFUWX':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'DINGFENGQQ':
                case 'DINGFENGWX':
                case 'DINGFENGALI':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'DINGYIWY':
                case 'DINGYIZFB':
                case 'DINGYIZFBW':
                case 'DINGYIWX':
                case 'DINGYIWXW':
                case 'DINGYICFT':
                case 'DINGYIQQ':
                case 'DINGYIQQW':
                case 'DINGYIJD':
                case 'DINGYIJDW':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    //dd($shop_order_num);
                    break;
                case 'ZAIXIANBAOZFB':
                case 'ZAIXIANBAOWX':
                case 'ZAIXIANBAOBD':
                case 'ZAIXIANBAOQQ':
                case 'ZAIXIANBAOJD':
                case 'ZAIXIANBAOWXW':
                    //$shop_order_num=substr($shop_order_num, 0, 30);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $shop_order_num = substr($shop_order_num,0,30);
                    break;
                case 'YIAIZFB':
                case 'YIAIZFBW':
                case 'YIAIWX':
                case 'YIAIWXW':
                case 'YIAIWY':
                case 'YIAIQQ':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 6);
                    $shop_order_num=substr($shop_order_num, 0, 30);
                    break;
                case 'ZSHZFB':
                case 'ZSHWX':
                case 'ZSHWY':
                case 'ZSHZFBWAP':
                case 'ZSHWXWAP':
                case 'ZSHQQWAP':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '-' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
					$shop_order_num = substr($shop_order_num,0,30);
                    break;
                case 'JIEANFUWY':
                case 'JIEANFUWX':
                case 'JIEANFUZFB':
                    do {
                        list($s1, $s2) = explode(' ', microtime());
                        $s=(string)explode('.',(string)$s1)[1];
                        $s=str_pad($s, 6, "0", STR_PAD_RIGHT);
                        $s=substr($s,0,6);
                        $shop_order_num = date("YmdHis") . $s;
                        $sql="SELECT deposit_id FROM deposits WHERE shop_order_num=$shop_order_num";
                        $res=$GLOBALS['db']->getRow($sql,[]);
                    } while (!empty($res));
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;
                case 'EBAOZFB':
                case 'EBAOWX':
                    $cards_1 = cards::getItemsById(array($card_id));
                    if (count($cards_1) == 0) die(json_encode(array("errno"=>104, "errstr"=>"支付不存在")));
                    $shop_order_num =$flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 6).$cards_1[$card_id]['mer_no'];
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;
                case 'XIDAKEJIZFB':
                case 'XIDAKEJIWX':
                case 'XIDAKEJIWY':
                case 'XIDAKEJIZFBW':
                case 'XIDAKEJIWXW':
                    $shop_order_num=substr($shop_order_num, 0, 30);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;
                case 'TIANFUBAOQQ':
                case 'TIANFUBAOWX':
                case 'TIANFUBAOWXW':
                case 'TIANFUBAOZFB':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    //$shop_order_num = substr($shop_order_num,0,30);
                    break;
                case 'YUNXUNWX':
                case 'YUNXUNQQ':
                case 'YUNXUNWY':
                case 'YUNXUNZFB':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 32);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;

                case 'CAIFUBAOZFB':
                case 'CAIFUBAOWX':
                case 'CAIFUBAOQQ':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 32);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;

                case 'CHANGCHENGFUZFB':
                case 'CHANGCHENGFUWX':
                case 'CHANGCHENGFUQQ':
                case 'CHANGCHENGFUWXW':
                case 'CHANGCHENGFUZFBW':
                    $shop_order_num = $card_id . '_CC'. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 32);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;
                case 'JINYINBAOWX'   :
                case 'JINYINBAOZFB'   :
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 32);
                    $rightAmount = array(2,3,4,5,6,7,8,9,10,11,12,13,15,16,18,19,20,22,25,29,30,32,33,35,39,40,45,49,50,55,59,60,65,66,68,70,75,80,88,90,95,99,100,101,110,120,130,140,150,160,170,180,188,190,198,199,200,210,250,290,298,300,400,500,600,700,800,900,999,1000,1200,1400,1500,1800,1900,1999,2000,2500,2900,3000);

                    while (!in_array($deposit_amount, $rightAmount)) {
                        $deposit_amount--;
                    }

//                    $maxDeposit = 500;
//                    if ($deposit_amount > $maxDeposit) {
//                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
//                    }
                    break;
                case 'JIALIANWX'   :
                case 'JIALIANZFB'   :
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 32);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;
                /****************************** snow down  易宝通支付*******************************/
                case 'YIBAOTONGQQ':
                case 'YIBAOTONGWX':
                case 'YIBAOTONGZFB':
                case 'YIBAOTONGWXW':
                case 'YIBAOTONGWY':
                    $shop_order_num  = $card_id  . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }

                    break;
                /****************************** snow up  易宝通支付*******************************/
                /****************************** snow down  汇通支付*******************************/
                case 'HUITONGQQ':
                case 'HUITONGWY':
                    $shop_order_num = $card_id  . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    break;

                /****************************** snow up  汇通支付*******************************/
		     case 'YDFQQ':
                case 'YDFWX':
                case 'YDFWY':
                case 'YDFZFB':
                 	$maxDeposit = 50000;
                   	if ($deposit_amount > $maxDeposit) {
                    	die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $shop_order_num = substr($shop_order_num,0,30);
                    //dd($shop_order_num);
                    break;
                case 'YHXFWX':
                case 'YHXFZFB':
                case 'YHXFQQ':
                case 'YHXFWY':
	                $maxDeposit = 50000;
	                if ($deposit_amount > $maxDeposit) {
	                	die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
	                }
	                $shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
	                $shop_order_num = substr($shop_order_num,0,30);
	                break;
	        case 'XBSWWX':
	    	case 'XBSWZFB':
	        case 'XBSWQQ':
	        case 'XBSWWY':
	             $maxDeposit = 50000;
	             if ($deposit_amount > $maxDeposit) {
	              die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
	             }
	             $shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
	             $shop_order_num = substr($shop_order_num,0,30);
	             break;
		    case 'MGWX':
	            case 'MGZFB':
	            case 'MGQQ':
	            case 'MGQQWAP':
	            case 'MGWY':
	            case 'MGWXWAP':
	             	$maxDeposit = 50000;
	             	if ($deposit_amount > $maxDeposit) {
	             		die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
	             	}
	             	$shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
	             	$shop_order_num = substr($shop_order_num,0,30);
	             	break;
                case 'CAIMAOWX':
                case 'CAIMAOQQ':
                case 'CAIMAOWY':
                case 'CAIMAOZFB':
                case 'CAIMAOWXW':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 24);
                    break;
                case 'XINFUBAOQQ':
                case 'XINFUBAOWX':
                case 'XINFUBAOZFB':
                case 'XINFUBAOWY':
                case 'XINFUBAOZFBW':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 30);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }

                    break;
                case 'YUANBAOWY':
                case 'YUANBAOZFB':
                case 'YUANBAOZFBW':
                case 'YUANBAOWX':
                case 'YUANBAOWXW':
                case 'YUANBAOQQ':
                case 'YUANBAOQQW':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 30);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }

                    break;
                case 'BOSHIWY':
                case 'BOSHIWX':
                case 'BOSHIWXW':
                case 'BOSHIZFB':
                case 'BOSHIZFBW':
                case 'BOSHIQQ':
                case 'BOSHIJD':
                case 'BOSHIJDW':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id.date("ymdHis").mt_rand(0,9);
                    //$shop_order_num = $card_id  ."1". date("YmdHis");
                    break;
                case 'YUEBAOWY':
                case 'YUEBAOWX':
                case 'YUEBAOWXW':
                case 'YUEBAOZFB':
                case 'YUEBAOZFBW':
                case 'YUEBAOQQ':
                case 'YUEBAOQQW':
                case 'YUEBAOKJ':
                case 'YUEBAOKJW':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 5);
                    //$shop_order_num=substr($shop_order_num, 0, 30);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }

                    break;
		    
		case 'LWWX':
                case 'LWZFB':
                case 'LWQQ':
                case 'LWWY':
                   $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                    	die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $shop_order_num = substr($shop_order_num,0,30);
                    
                    break;
                
		case 'LZFWX':
                case 'LZFZFB':
                case 'LZFQQ':
                case 'LZFWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                    	die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis").substr(abs(crc32(microtime() . $user['user_id'])), 0, 1);
                    $shop_order_num = substr($shop_order_num,0,30);
                    break;
		  
                case 'SHIGUANGFUQQ':
                case 'SHIGUANGFUWX':
                case 'SHIGUANGFUZFB':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis").mt_rand(0,9);
                    //$shop_order_num=substr($shop_order_num, 0, 30);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }
                    if (floor($deposit_amount) != $deposit_amount) {
                        die(json_encode(array("errno"=>104, "errstr"=>"充值金额必须为整数!")));
                    }

                    break;
                case 'ALAQQ':
                case 'ALAZFB':
                case 'ALAWX':
                case 'ALAWXW':
                case 'ALAWY':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 30);
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}无法提交")));
                    }

                    break;
                case 'ZUNWY':
                case 'ZUNZFB':
                case 'ZUNZFBW':
                case 'ZUNWX':
                case 'ZUNWXW':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 30);
                    break;
                    
                    
                    
                case 'LYQQWAP':
                case 'LYWXWAP':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 30);
                    break;
                case 'XUNWY':
                case 'XUNZFB':
                case 'XUNWX':
                case 'XUNQQ':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 25);
                    break;
                case 'QBJHZFB':
                case 'QBJHWX':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 20);
                    break;
                case 'BJHFWX':
                case 'BJHFWXW':
                case 'BJHFZFB':
                case 'BJHFZFBW':
                case 'BJHFWY':
                case 'BJHFKJ':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 20);
                    break;
                case 'WZHWX':
                case 'WZHZFB':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 20);
                    break;
                case 'ZF32WXW':
                case 'ZF32WY':
                case 'ZF32QQ':
                case 'ZF32QQW':
                case 'ZF32WX':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'CAIHONWX':
                case 'CAIHONQQ':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'QIANENWX':
                case 'QIANENQQ':
                case 'QIANENZFB':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'BAISHIWX':
                case 'BAISHIWXW':
                case 'BAISHIZFB':
                case 'BAISHIZFBW':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'HUIHEQQ':
                case 'HUIHEJD':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'YITONGWY':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id.substr(abs(crc32(microtime() . $user['user_id'])), 0, 10);
                    break;
                case 'QIQIWX':
                case 'QIQIZFB':
                case 'QIQIQQ':
                case 'QIQIJD':
                case 'QIQIQQW':
                case 'QIQIWXW':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'JINYANGQQ':
                case 'JINYANGQQW':
                case 'JINYANGWX':
                case 'JINYANGWXW':
                case 'JINYANGZFB':
                case 'JINYANGZFBW':
                case 'JINYANGJD':
                case 'JINYANGJDW':
                case 'JINYANGWY':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'SFTQQ':
                case 'SFTQQW':
                case 'SFTWX':
                case 'SFTWXW':
                case 'SFTZFB':
                case 'SFTZFBW':
                case 'SFTWY':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'F86WX':
                case 'F86QQ':
                case 'F86QQW':
                case 'F86WY':
                case 'F86KJ':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'QIANLONGTONGWX':
                case 'QIANLONGTONGZFB':
                case 'QIANLONGTONGJD':
                case 'QIANLONGTONGQQ':
                case 'QIANLONGTONGQQW':
                case 'QIANLONGTONGWY':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'ZHIDEBAOWY':
                case 'ZHIDEBAOQQ':
                case 'ZHIDEBAOQQW':
                case 'ZHIDEBAOZFB':
                case 'ZHIDEBAOZFBW':
                case 'ZHIDEBAOWX':
                case 'ZHIDEBAOWXW':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'QUANYUJD':
                case 'QUANYUZFB':
                case 'QUANYUWX':
                case 'QUANYUQQ':
                case 'QUANYUKJ':
                case 'QUANYUWXW':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'KEXUNWY':
                case 'KEXUNZFB':
                case 'KEXUNZFBW':
                case 'KEXUNWX':
                case 'KEXUNWXW':
                case 'KEXUNQQ':
                case 'KEXUNJD':
                case 'KEXUNQQW':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'YAFUQQ':
                case 'YAFUQQW':
                case 'YAFUWXW':
                case 'YAFUWY':
                case 'YAFUWX':
                case 'YAFUZFB':
                case 'YAFUZFBW':
                case 'YAFUJD':
                case 'YAFUJDW':
                case 'YAFUYL':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'FUKATONGWY':
                case 'FUKATONGKJ':
                case 'FUKATONGWX':
                case 'FUKATONGQQ':
                case 'FUKATONGJD':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'HUFUWY':
                case 'HUFUKJ':
                case 'HUFUWX':
                case 'HUFUWXW':
                case 'HUFUQQ':
                case 'HUFUQQW':
                case 'HUFUZFB':
                case 'HUFUZFBW':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'SAFEWY':
                case 'SAFEWX':
                case 'SAFEZFB':
                    $temp_card_id = str_pad($card_id,5,'0',STR_PAD_LEFT);
                    $shop_order_num = $temp_card_id. date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);
                    $shop_order_num=substr($shop_order_num, 0, 27);
                    break;
                case 'LBWXW':
                case 'LBKJ':
                case 'LBYL':
                case 'LBJD':
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    $shop_order_num = substr($shop_order_num,0,30);
                    break;
            }

            //如果是幸运支付的订单状态都是0，后台可以人工手动审核执行上分
            $orderStatus = $card['usage'] == 3 ? 0 : 3;
            $data = array(
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'top_id' => $user['top_id'],
                'player_pay_time' => date("Y-m-d H:i:s"),
                'player_card_name' => '',
                'trade_type' => $card['usage'],
                'real_name'=>$remark,
                'amount' => $deposit_amount,
                'deposit_bank_id' => $bank_id,
                'deposit_card_id' => $card_id,
                'shop_order_num' => $shop_order_num,
                'local_order_num' => date("YmdHis") . strtoupper(uniqid('', false)),
                'create_time' => date("Y-m-d H:i:s"),
                'status' => $orderStatus, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
                /******************** 添加支付账号 如果没有传入 默认为空**************************************/
                'pay_account_id' => $pay_account_id, //支付账号
                /******************** 添加支付账号 如果没有传入 默认为空**************************************/
            );

            if (!$deposit_id = deposits::addItem($data)) {
                die(json_encode(array("errno"=>102,"errstr" => '添加数据失败'))) ;
            }

            if ($card['direct_display']) {
                die(json_encode(array('errno' => 0, 'errstr' => $errstr, 'qrcode_url' => $qrcode_url, 'order_num' => $shop_order_num,"deposit_amount" => $deposit_amount))) ;
            }

            if ($bankFlag == 'BP') {
                die(json_encode(array('errno'=>0, 'errstr' => $shop_order_num, 'deposit_id' => $GLOBALS['db']->insert_id(), 'deposit_amount' => $deposit_amount, 'local_order_num' => $data['local_order_num']))) ;
            } else {
                die(json_encode(array('errno'=>0, 'errstr' =>  authcode($shop_order_num, 'ENCODE', 'a6sbe!x4^5d_ghd'), 'deposit_id' => $GLOBALS['db']->insert_id(), 'deposit_amount' => $deposit_amount, 'local_order_num' => $data['local_order_num']))) ;
            }
        } elseif('delCase' == $op){
            $deposit_id = $this->request->getPost('deposit_id', 'trim');
            if(deposits::deleteItem($deposit_id, true)){
                die(json_encode(array("errno"=>0,"errstr" => ''))) ;
            }
            die(json_encode(array("errno"=>104,"errstr" => '取消订单失败'))) ;
        }

        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
        $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $usage = $this->request->getGet('usage', 'trim', '');
        $cards = cards::getItemsC(1, 0, $usage, '', 2, cards::ORDER_BY_SORT, $user['ref_group_id']);

        $cardList = array();
        $tempCardList = array();

        foreach ($cards as $value) {
            if($value['bank_id']!=254){
                if (!array_key_exists($value['bank_id'], $tempCardList)) {
                    $tempCardList[$value['bank_id']] = array();
                }

                array_push($tempCardList[$value['bank_id']], $value);
            }
        }

        $cardSeq = 1;
        $cardIndex = 1;

        foreach ($tempCardList as $value) {
            if(!empty($value)) {
                $card = payCardsEncodeNew($user, 'receivePayResult', cards::choseCardToSaveNew($value, $domain), true);

                if (isset($card['card_id']) && !empty($card['use_place']) && ($card['use_place'] & 2) === 2) {
                    $cardList[$cardIndex]['card_id'] = $card['card_id'];

                    if (!isset ($card['login_name'])) {
                        continue;
                    }

                    $cardList[$cardIndex]['login_name'] = $card['login_name'] . $cardSeq++;
                    $cardList[$cardIndex]['bank_id'] = $card['bank_id'];
                    $cardList[$cardIndex]['codes'] = $card['codes'];
                    $cardList[$cardIndex]['requestURI'] = $card['requestURI'];
                    $cardList[$cardIndex]['shop_url'] = $card['shop_url'];
                    $cardList[$cardIndex]['call_back_url'] = $card['call_back_url'];
                    $cardList[$cardIndex]['return_url'] = $card['return_url'];
                    $cardList[$cardIndex]['netway'] = authcode($card['netway'], 'DECODE', 'a6sbe!x4^5d_ghd');
                    $cardList[$cardIndex]['remark'] = $card['remark'];
                    $cardList[$cardIndex]['direct_display'] = $card['direct_display'];
                    $cardList[$cardIndex]['remark'] = $card['remark'];
                    /************* snow 添加  是否填写支付账号字段***************************/
                    $cardList[$cardIndex]['pay_id_input'] = $card['pay_id_input'];
                    $cardList[$cardIndex]['pay_max_input'] = $card['pay_max_input'];
                    $cardList[$cardIndex]['pay_small_input'] = $card['pay_small_input'];
                    $cardList[$cardIndex]['discount'] = $card['discount'];
                    $cardList[$cardIndex]['is_newpay'] = $card['is_newpay'];
                    /************* snow 添加  是否填写支付账号字段***************************/
                    $cardIndex++;
                }
            }
        }

        $hash = generateEnPwd($user['username'] . '_' . $user['user_id'] . '_' . $user['user_id'] . '_'. $user['username'] . '_' . date('Ymd'));
        $payTimeOut = config::getConfig('pay_time_out', 60);
        $time = time() + $payTimeOut;

        self::$view->setVar('cardList', json_encode($cardList));
        self::$view->setVar('usage', $usage);
        self::$view->setVar('client_ip', $GLOBALS['REQUEST']['client_ip']);
        self::$view->setVar('hash',  substr($time,0,5) . $hash . substr($time,5,5) );
        self::$view->setVar('user', $user);
        self::$view->setVar('min_deposit_limit', $min_deposit_limit);
        /******************* snow 添加获取默认最大值*************************/
        self::$view->setVar('max_deposit_limit', $max_deposit_limit);
        /******************* snow 添加获取默认最大值*************************/
        self::$view->setVar('qq_number', Config::getConfig('qq_number'));

        self::$view->render('fin_deposit');
    }
    //我要存款 户汇完款自动到帐 不需提交了
    public function deposit_bak()
    {
        $gflag = $this->request->getGet('flag', 'trim', '');
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }

        $this->_checkWithdraw($gflag, 0, '', '');
        $bankList = array(
            '1'   => true,
            '101' => false, // 熊猫
            '110' => false, // 仁信微信
            '111' => false, // 百付支付宝
            '112' => false, // 百付微信
            '113' => false, // 百付支付宝WAP
            '114' => false, // 付乾微信
            '115' => false, // 付乾网银
            '116' => false, // 顺宝支付宝
            '117' => false, // 顺宝微信
            '118' => false, // 幸运支付
            '119' => false, // 付乾QQ
            '120' => false, // 银宝支付宝
            '121' => false, // 银宝微信
            '124' => false, // U付支付宝
            '125' => false, // U付微信
            '126' => false, // 仁信支付宝
            '127' => false, // 瞬付微信
            '128' => false, // 瞬付支付宝
            '129' => false, // 瞬付QQ
            '130' => false, // 瞬付支付宝WAP
            '131' => false, // 瞬付QQWAP
            '132' => false, // 高通支付宝
            '133' => false, // 高通微信
            '134' => false, // 萝卜微信
            '135' => false, // 萝卜网银
        );

        $reg_time = strtotime($user['reg_time']);
        //注册过了3天的显示网转储蓄卡
        // if ($reg_time < (time() - (60 * 60 * 24 * 3))) {
        //     $isBanks = true;
        // }

        $op = $this->request->getPost('op', 'trim');
        //修改数据
        if ('autoAddCase' == $op){
            if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
                die(json_encode(array("errno"=>101,"errstr" => '非法用户')));
            }

            $bank_id = $this->request->getPost('bank_id', 'intval');
            $deposit_amount = $this->request->getPost('deposit_amount', 'floatval');
            $card_id = $this->request->getPost('card_id', 'intval');
            $flag = $this->request->getPost('flag', 'trim');
            $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 8);

            if ($deposit_amount < 10) {
                die(json_encode(array("errno"=>103, "errstr"=>"金额低于最低充值限额10，无法提交")));
            }

            switch ($flag) {
                case 'XMZF':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 4);
                    break;
                case 'BFZFB':
                    $maxDeposit = 2999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 2);
                    break;
                case 'BFWX':
                    $maxDeposit = 9999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 2);
                    break;
                case 'BFZFBW':
                    $maxDeposit = 2999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    $shop_order_num = $card_id . '_' . $flag . date("YmdHis") . substr(abs(crc32(microtime() . $user['user_id'])), 0, 2);
                    break;
                case 'FQWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'FQQQ':
                case 'SBWX':
                case 'SBZFB':
                    $maxDeposit = 2999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'RXWX':
                case 'RXZFB':
                case 'FQWX':
                case 'XY':
                    $maxDeposit = 49999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'YBZFB':
                case 'YBWX':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'UFZFB':
                case 'UFWX':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'SFWX':
                case 'SFZFB':
                case 'SFQQ':
                case 'SFZFBW':
                case 'SFQQW':
                    $maxDeposit = 3000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'GTZFB':
                case 'GTWX':
                    $maxDeposit = 4999;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'LBWX':
                    $maxDeposit = 5000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
                case 'LBWY':
                    $maxDeposit = 50000;
                    if ($deposit_amount > $maxDeposit) {
                        die(json_encode(array("errno"=>104, "errstr"=>"金额超过充值限额{$maxDeposit}，无法提交")));
                    }
                    break;
            }

            if(!$card = cards::getItem($card_id, NULL, 2)){
                die(json_encode(array("errno"=>105, "errstr"=>"此充值卡不存在或已禁用")));
            }
            //如果是幸运支付的订单状态都是0，后台可以人工手动审核执行上分
            $orderStatus = $card['usage'] == 3 ? 0 : 3;
            $data = array(
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'top_id' => $user['top_id'],
                'player_pay_time' => date("Y-m-d H:i:s"),
                'player_card_name' => '',
                'trade_type' => $card['usage'],
                'amount' => $deposit_amount,
                'deposit_bank_id' => $bank_id,
                'deposit_card_id' => $card_id,
                'shop_order_num' => $shop_order_num,
                'create_time' => date("Y-m-d H:i:s"),
                'status' => $orderStatus, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
            );

            if (!$deposit_id = deposits::addItem($data)) {
                die(json_encode(array("errno"=>102,"errstr" => '添加数据失败'))) ;
            }

            die(json_encode(array("errno"=>0,"errstr" => $shop_order_num,"deposit_id" => $GLOBALS['db']->insert_id()))) ;
        } elseif('delCase' == $op){
            $deposit_id = $this->request->getPost('deposit_id', 'trim');
            if(deposits::deleteItem($deposit_id, true)){
                die(json_encode(array("errno"=>0,"errstr" => ''))) ;
            }
            die(json_encode(array("errno"=>104,"errstr" => '取消订单失败'))) ;
        }
        //0禁用，1启用，2正在使用，3超过收款限额而下线 4余额不足而下线 5超过当日支付限额而下线，当日不再使用

        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
        $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';

        $cards = cards::getItems(1, 0, 0, '', 2, cards::ORDER_BY_BANK_ID_AND_SORT, $user['ref_group_id']);
        $xmPay = $rxwxPay = $rxzfbPay = $bfzfbPay = $bfwxPay = $bfzfbwPay = $fqwxPay = $fqqqPay = $fqwyPay = $sbzfbPay = $sbwxPay = $xyPay = $ybzfbPay = $ybwxPay = $ufzfbPay = $ufwxPay = $sfwxPay = $sfzfbPay = $sfqqPay = $sfzfbwPay = $sfqqwPay = $gtzfbPay = $gtwxPay = $lbwxPay = $lbwyPay = array();
        $tmp = array(101 => array(), 110 => array(), 111 => array(), 112 => array(), 113 => array(), 114 => array(), 115 => array(), 116 => array(), 117 => array(), 118 => array(), 119 => array(), 120 => array(), 121 => array(), 124 => array(), 125 => array(), 126 => array(), 127 => array(), 128 => array(), 129 => array(), 130 => array(), 131 => array(), 132 => array(), 133 => array(), 134 => array(), 135 => array());
        foreach ($cards as $v) {
            $bankList[$v['bank_id']] = true;

            switch ($v['bank_id']) {
                // 熊猫
                case '101':
                    array_push($tmp[101], $v);
                    break;
                // 仁信微信
                case '110':
                    array_push($tmp[110], $v);
                    break;
                // 百付支付宝
                case '111':
                    array_push($tmp[111], $v);
                    break;
                // 百付微信
                case '112':
                    array_push($tmp[112], $v);
                    break;
                // 百付支付宝WAP
                case '113':
                    array_push($tmp[113], $v);
                    break;
                case '114':
                    array_push($tmp[114], $v);
                    break;
                // 付乾网银
                case '115':
                    array_push($tmp[115], $v);
                    break;
                // 顺宝支付宝
                case '116':
                    array_push($tmp[116], $v);
                    break;
                // 顺宝微信
                case '117':
                    array_push($tmp[117], $v);
                    break;
                // 幸运支付
                case '118':
                    array_push($tmp[118], $v);
                    break;
                case '119':
                    array_push($tmp[119], $v);
                    break;
                // 银宝支付宝
                case '120':
                    array_push($tmp[120], $v);
                    break;
                // 银宝微信
                case '121':
                    array_push($tmp[121], $v);
                    break;
                // U付支付宝
                case '124':
                    array_push($tmp[124], $v);
                    break;
                // U付微信
                case '125':
                    array_push($tmp[125], $v);
                    break;
                // 仁信支付宝
                case '126':
                    array_push($tmp[126], $v);
                    break;
                // 瞬付微信
                case '127':
                    array_push($tmp[127], $v);
                    break;
                // 瞬付支付宝
                case '128':
                    array_push($tmp[128], $v);
                    break;
                // 瞬付QQ
                case '129':
                    array_push($tmp[129], $v);
                    break;
                // 瞬付支付宝WAP
                case '130':
                    array_push($tmp[130], $v);
                    break;
                // 瞬付QQWAP
                case '131':
                    array_push($tmp[131], $v);
                    break;
                // 高通支付宝
                case '132':
                    array_push($tmp[132], $v);
                    break;
                // 高通微信
                case '133':
                    array_push($tmp[133], $v);
                    break;
                // 萝卜微信
                case '134':
                    array_push($tmp[134], $v);
                    break;
                // 萝卜网银
                case '135':
                    array_push($tmp[135], $v);
                    break;
            }
        }

        //>100 的属于第三方,充值到限自动切换卡功能
        if (!empty($tmp[101])) {
            $xmPay = cards::choseCardToSave($tmp[101], $domain);
            payCardsEncode('xm', $user, 'receiveXMPayResult', $xmPay);
        }
        if (!empty($tmp[110])) {
            $rxwxPay = cards::choseCardToSave($tmp[110], $domain);
            payCardsEncode('rxwx', $user, 'receiveRXWXPayResult', $rxwxPay);
        }
        if (!empty($tmp[111])) {
            $bfzfbPay = cards::choseCardToSave($tmp[111], $domain);
            payCardsEncode('bfzfb', $user, 'receiveBFZFBPayResult', $bfzfbPay);
        }
        if (!empty($tmp[112])) {
            $bfwxPay = cards::choseCardToSave($tmp[112], $domain);
            payCardsEncode('bfwx', $user, 'receiveBFWXPayResult', $bfwxPay);
        }
        if (!empty($tmp[113])) {
            $bfzfbwPay = cards::choseCardToSave($tmp[113], $domain);
            payCardsEncode('bfzfbw', $user, 'receiveBFZFBWPayResult', $bfzfbwPay);
        }
        if (!empty($tmp[114])) {
            $fqwxPay = cards::choseCardToSave($tmp[114], $domain);
            payCardsEncode('fqwx', $user, 'receiveFQWXPayResult', $fqwxPay);
        }
        if (!empty($tmp[115])) {
            $fqwyPay = cards::choseCardToSave($tmp[115], $domain);
            payCardsEncode('fqwy', $user, 'receiveFQWYPayResult', $fqwyPay);
        }
        if (!empty($tmp[116])) {
            $sbzfbPay = cards::choseCardToSave($tmp[116], $domain);
            payCardsEncode('sbzfb', $user, 'receiveSBZFBPayResult', $sbzfbPay);
        }
        if (!empty($tmp[117])) {
            $sbwxPay = cards::choseCardToSave($tmp[117], $domain);
            payCardsEncode('sbwx', $user, 'receiveSBWXPayResult', $sbwxPay);
        }
        if (!empty($tmp[118])) {
            $xyPay = cards::choseCardToSave($tmp[118], $domain);
            payCardsEncode('xy', $user, 'receiveXYPayResult', $xyPay);
        }
        if (!empty($tmp[119])) {
            $fqqqPay = cards::choseCardToSave($tmp[119], $domain);
            payCardsEncode('fqqq', $user, 'receiveFQQQPayResult', $fqqqPay);
        }
        if (!empty($tmp[120])) {
            $ybzfbPay = cards::choseCardToSave($tmp[120], $domain);
            payCardsEncode('ybzfb', $user, 'receiveYBZFBPayResult', $ybzfbPay);
        }
        if (!empty($tmp[121])) {
            $ybwxPay = cards::choseCardToSave($tmp[121], $domain);
            payCardsEncode('ybwx', $user, 'receiveYBWXPayResult', $ybwxPay);
        }
        if (!empty($tmp[124])) {
            $ufzfbPay = cards::choseCardToSave($tmp[124], $domain);
            payCardsEncode('ufzfb', $user, 'receiveUFZFBPayResult', $ufzfbPay);
        }
        if (!empty($tmp[125])) {
            $ufwxPay = cards::choseCardToSave($tmp[125], $domain);
            payCardsEncode('ufwx', $user, 'receiveUFWXPayResult', $ufwxPay);
        }
        if (!empty($tmp[126])) {
            $rxzfbPay = cards::choseCardToSave($tmp[126], $domain);
            payCardsEncode('rxzfb', $user, 'receiveRXZFBPayResult', $rxzfbPay);
        }
        if (!empty($tmp[127])) {
            $sfwxPay = cards::choseCardToSave($tmp[127], $domain);
            payCardsEncode('sfwx', $user, 'receiveSFWXPayResult', $sfwxPay);
        }
        if (!empty($tmp[128])) {
            $sfzfbPay = cards::choseCardToSave($tmp[128], $domain);
            payCardsEncode('sfzfb', $user, 'receiveSFZFBPayResult', $sfzfbPay);
        }
        if (!empty($tmp[129])) {
            $sfqqPay = cards::choseCardToSave($tmp[129], $domain);
            payCardsEncode('sfqq', $user, 'receiveSFQQPayResult', $sfqqPay);
        }
        if (!empty($tmp[130])) {
            $sfzfbwPay = cards::choseCardToSave($tmp[130], $domain);
            payCardsEncode('sfzfbw', $user, 'receiveSFZFBWPayResult', $sfzfbwPay);
        }
        if (!empty($tmp[131])) {
            $sfqqwPay = cards::choseCardToSave($tmp[131], $domain);
            payCardsEncode('sfqqw', $user, 'receiveSFQQWPayResult', $sfqqwPay);
        }
        if (!empty($tmp[132])) {
            $gtzfbPay = cards::choseCardToSave($tmp[132], $domain);
            payCardsEncode('gtzfb', $user, 'receiveGTZFBPayResult', $gtzfbPay);
        }
        if (!empty($tmp[133])) {
            $gtwxPay = cards::choseCardToSave($tmp[133], $domain);
            payCardsEncode('gtwx', $user, 'receiveGTWXPayResult', $gtwxPay);
        }
        if (!empty($tmp[134])) {
            $lbwxPay = cards::choseCardToSave($tmp[134], $domain);
            payCardsEncode('lbwx', $user, 'receiveLBWXPayResult', $lbwxPay);
        }
        if (!empty($tmp[135])) {
            $lbwyPay = cards::choseCardToSave($tmp[135], $domain);
            payCardsEncode('lbwy', $user, 'receiveLBWYPayResult', $lbwyPay);
        }
        //判断在线支付宝充值流水是否达到当日上限
        //$orderTrafficInfo = deposits::getTrafficInfo('', 0, -1, -1, 8, 0, 205, 0, 0, 0, date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59"));
        //if($orderTrafficInfo['total_amount'] >= $config['online_alipay_day_max_deposit_limit']){
        //  $isAliPay2 = false;
        //}

        //设置默认选中的支付方式
        $mobile_default_pay = config::getConfig('mobile_default_pay');
        self::$view->setVar('mobile_default_pay', $mobile_default_pay);

        self::$view->setVar('rxwxPay', $rxwxPay);
        self::$view->setVar('bfzfbPay', $bfzfbPay);
        self::$view->setVar('bfwxPay', $bfwxPay);
        self::$view->setVar('bfzfbwPay', $bfzfbwPay);
        self::$view->setVar('fqwxPay', $fqwxPay);
        self::$view->setVar('fqwyPay', $fqwyPay);
        self::$view->setVar('fqqqPay', $fqqqPay);
        self::$view->setVar('sbzfbPay', $sbzfbPay);
        self::$view->setVar('sbwxPay', $sbwxPay);
        self::$view->setVar('xyPay', $xyPay);
        self::$view->setVar('xmPay', $xmPay);
        self::$view->setVar('ybzfbPay', $ybzfbPay);
        self::$view->setVar('ybwxPay', $ybwxPay);
        self::$view->setVar('ufzfbPay', $ufzfbPay);
        self::$view->setVar('ufwxPay', $ufwxPay);
        self::$view->setVar('rxzfbPay', $rxzfbPay);
        self::$view->setVar('sfwxPay', $sfwxPay);
        self::$view->setVar('sfzfbPay', $sfzfbPay);
        self::$view->setVar('sfqqPay', $sfqqPay);
        self::$view->setVar('sfzfbwPay', $sfzfbwPay);
        self::$view->setVar('sfqqwPay', $sfqqwPay);
        self::$view->setVar('gtzfbPay', $gtzfbPay);
        self::$view->setVar('gtwxPay', $gtwxPay);
        self::$view->setVar('lbwxPay', $lbwxPay);
        self::$view->setVar('lbwyPay', $lbwyPay);

        self::$view->setVar('isXMPay', $bankList[101]);
        self::$view->setVar('isRXWXPay', $bankList[110]);
        self::$view->setVar('isBFZFBPay', $bankList[111]);
        self::$view->setVar('isBFWXPay', $bankList[112]);
        self::$view->setVar('isBFZFBWPay', $bankList[113]);
        self::$view->setVar('isFQWXPay', $bankList[114]);
        self::$view->setVar('isFQWYPay', $bankList[115]);
        self::$view->setVar('isSBZFBPay', $bankList[116]);
        self::$view->setVar('isSBWXPay', $bankList[117]);
        self::$view->setVar('isXYPay', $bankList[118]);
        self::$view->setVar('isFQQQPay', $bankList[119]);
        self::$view->setVar('isYBZFBPay', $bankList[120]);
        self::$view->setVar('isYBWXPay', $bankList[121]);
        self::$view->setVar('isUFZFBPay', $bankList[124]);
        self::$view->setVar('isUFWXPay', $bankList[125]);
        self::$view->setVar('isRXZFBPay', $bankList[126]);
        self::$view->setVar('isSFWXPay', $bankList[127]);
        self::$view->setVar('isSFZFBPay', $bankList[128]);
        self::$view->setVar('isSFQQPay', $bankList[129]);
        self::$view->setVar('isSFZFBWPay', $bankList[130]);
        self::$view->setVar('isSFQQWPay', $bankList[131]);
        self::$view->setVar('isGTZFBPay', $bankList[132]);
        self::$view->setVar('isGTWXPay', $bankList[133]);
        self::$view->setVar('isLBWXPay', $bankList[134]);
        self::$view->setVar('isLBWYPay', $bankList[135]);

        self::$view->setVar('isBanks', $bankList[1]);

        self::$view->setVar('client_ip', $GLOBALS['REQUEST']['client_ip']);
        self::$view->setVar('user', $user);

        self::$view->render('fin_deposit');
    }

    public function receiveRXWXPayResult()
    {
        echo payCallBack($this->request, 'RenXinWeiXinError.log');
    }

    public function receiveRXZFBPayResult()
    {
        echo payCallBack($this->request, 'RenXinZhiFuBaoError.log');
    }

    public function receiveBFZFBPayResult()
    {
        echo payCallBack($this->request, 'BaiFuZhiFuBaoError.log');
    }

    public function receiveBFWXPayResult()
    {
        echo payCallBack($this->request, 'BaiFuWeiXinError.log');
    }

    public function receiveBFZFBWPayResult()
    {
        echo payCallBack($this->request, 'BaiFuZhiFuBaoWapError.log');
    }

    public function receiveFQWXPayResult()
    {
        echo payCallBack($this->request, 'FuQianWeiXinError.log');
    }

    public function receiveFQWYPayResult()
    {
        echo payCallBack($this->request, 'FuQianWangYinError.log');
    }

    public function receiveSBZFBPayResult()
    {
        echo payCallBack($this->request, 'ShunBaoZhiFuBaoError.log');
    }

    public function receiveSBWXPayResult()
    {
        echo payCallBack($this->request, 'ShunBaoWeiXinError.log');
    }

    public function receiveXYPayResult()
    {
        echo payCallBack($this->request, 'XingYunZhiFuError.log');
    }

    public function receiveFQQQPayResult()
    {
        echo payCallBack($this->request, 'FQQQPayError.log');
    }

    public function receiveYBZFBPayResult()
    {
        echo payCallBack($this->request, 'YBZFBPayError.log');
    }

    public function receiveYBWXPayResult()
    {
        echo payCallBack($this->request, 'YBWXPayError.log');
    }

    public function receiveXMPayResult()
    {
        echo payCallBack($this->request, 'XMPayError.log');
    }

    public function receiveUFZFBPayResult()
    {
        echo payCallBack($this->request, 'UFZFBPayError.log');
    }

    public function receiveUFWXPayResult()
    {
        echo payCallBack($this->request, 'UFWXPayError.log');
    }

    public function receiveSFWXPayResult()
    {
        echo payCallBack($this->request, 'SFWXPayError.log');
    }

    public function receiveSFZFBPayResult()
    {
        echo payCallBack($this->request, 'SFZFBPayError.log');
    }

    public function receiveSFQQPayResult()
    {
        echo payCallBack($this->request, 'SFQQPayError.log');
    }

    public function receiveSFZFBWPayResult()
    {
        echo payCallBack($this->request, 'SFZFBWPayError.log');
    }

    public function receiveSFQQWPayResult()
    {
        echo payCallBack($this->request, 'SFQQWPayError.log');
    }

    public function receiveGTZFBPayResult()
    {
        echo payCallBack($this->request, 'GTZFBPayError.log');
    }

    public function receiveGTWXPayResult()
    {
        echo payCallBack($this->request, 'GTWXPayError.log');
    }

    public function receiveLBWXPayResult()
    {
        echo payCallBack($this->request, 'LBWXPayError.log');
    }

    public function receiveLBWYPayResult()
    {
        echo payCallBack($this->request, 'LBWYPayError.log');
    }

    public function receivePayResult()
    {
        echo payCallBack($this->request, 'PayError.log');
    }

    private function _checkWithdraw($flag, $errno = 0, $errstr = '', $showmsg = '')
    {
        if($flag == 'ajax'){
            $res = array('errno'=>$errno,'errstr'=>$errstr);
            die(json_encode($res));
        } else {
            if($showmsg != ''){
                showMsg($showmsg);
            }
        }
    }



    /****************** snow dcsite 总代下的用户不用出款***************************/
    /**
     * author snow
     * 验证总代dcsite下及自己不能进行提款操作
     * @param $flag
     * @param $user_id int 用户id
     */
    private function _handleDcsite($flag, $user_id)
    {

        $sql =<<<SQL
SELECT a.user_id,b.username AS top_name FROM  users AS a,users AS b WHERE a.user_id = {$user_id} AND a.top_id = b.user_id AND b.username = 'dcsite'
SQL;
        if ($GLOBALS['db']->getRow($sql))
        {
            //>>如果存在,说明属于dcsite 总代名下 ,不能进行提款
            $showmsg = $errstr = '属于dcsite 总代名下用户 ,不能进行提款';
            $this->_checkWithdraw($flag, -1, $errstr, $showmsg);
        }

    }

    /****************** snow dcsite 总代下的用户不用出款***************************/

    public function withdraw()
    {
        $user_id = $GLOBALS['SESSION']['user_id'];

        $flag = $this->request->getGet('flag', 'trim', '');

        /****************** snow dcsite 总代下的用户不用出款 start***************************/
        if ($flag != '')
        {
            //>> 兼容以前的代码 .调用方法判断
            $this->_handleDcsite($flag, $user_id);
        }
        /****************** snow dcsite 总代下的用户不用出款 end  ***************************/
        if (!$user = users::getItem($user_id)) {
            $showmsg = $errstr = '非法请求，该用户不存在或已被冻结';
            $this->_checkWithdraw($flag, 1, $errstr, $showmsg);
        }
//        if (!$user['safe_pwd']) {
//            $showmsg = "您尚未设置安全码，请先 <a href='index.jsp?c=user&a=editSafePwd' onclick=parent.layer.closeAll();>点此设置安全码</a>";
//            $errstr = '您尚未设置安全码';
//            $this->_checkWithdraw($flag, 2, $errstr, $showmsg);
//        }
        if (!$user['secpwd']) {
            $showmsg = "您尚未设置资金密码，请先 <a href='/?c=user&a=editSecPwd' onclick=parent.layer.closeAll();>点此设置资金密码</a>";
            $errstr = '您尚未设置资金密码';
            $this->_checkWithdraw($flag, 3, $errstr, $showmsg);
        }

        //自动填写已经绑定了的银行帐号
        if (!$userBindCards = userBindCards::getItems($user_id,0,'',1)) {
            $showmsg = "您尚未绑定任何银行卡，请先 <a href='/?c=fin&a=bindCard' onclick=parent.layer.closeAll();>点此绑定卡号</a>方可提款";
            $errstr = '您尚未绑定任何银行卡';
            $this->_checkWithdraw($flag, 4, $errstr, $showmsg);
        }

        $userBindCard = $userBindCards[0];
        if(strlen($userBindCard['card_num']) <= 8)
        {
            $re=empty($userBindCard['card_num'])?'空':$userBindCard['card_num'];

            $showmsg ="您的银行卡存在异常! 请联系客服!<br/>用户ID: {$user_id}<br/>卡 &nbsp;&nbsp;ID: {$userBindCard['bind_card_id']}<br/>卡 &nbsp;&nbsp;号: ".$re;
            $res = array('errno'=>-1,'errstr'=>$showmsg);
            die(json_encode($res));
        }
        //取得提款记录
        $start_date = date("Y-m-d 00:00:00");
        $end_date = date("Y-m-d 23:59:59");

        //试营业初期不做提款次数限制
        // $stats = withdraws::getTrafficInfo($GLOBALS['SESSION']['username'], 0, 0, 8, 0, 0, 0, $start_date, $end_date);
        // if ($stats['count'] >= config::getConfig('day_withdraw_num', 3)) {
        //     $errstr = $showmsg = '你已经超过每天提现次数限制';
        //     $this->_checkWithdraw($flag, 5, $errstr, $showmsg);
        // }

        if ('doWithdraw' == $this->request->getPost('op', 'trim'))
        {
            $flag = $this->request->getPost('flag', 'trim');

            /****************** snow dcsite 总代下的用户不用出款 start***************************/
                //>> 兼容以前的代码 .调用方法判断
                $this->_handleDcsite($flag, $user_id);
            /****************** snow dcsite 总代下的用户不用出款 end  ***************************/
            if($flag=='ip_chk'){
                if(withdraws::ipIsChina()===false){
                    $withdraw_id=$GLOBALS['mc']->get(__CLASS__, __FUNCTION__.'wid');
                    $GLOBALS['mc']->set(__CLASS__, __FUNCTION__.'wid', '');
                    $uid=$GLOBALS['SESSION']['user_id'];
                    if (!empty($withdraw_id) && $withdraw_id>0 && !empty($uid)) {
                        $m_model=M('withdraws');
                        $withdraw=$m_model
                            ->field('amount')
                            ->where(['withdraw_id' => $withdraw_id])
                            ->find();
                        $u_model=M('users');
                        $user=$u_model
                            ->field('top_id,user_id')
                            ->where(['user_id' => $uid])
                            ->find();
                        if(!empty($withdraw) && !empty($user)){
                            $withdraw_amount=$withdraw['amount'];
                            $ipNotes=array(
                                'withdraw_id'=>$withdraw_id,
                                'user_id'=>$user['user_id'],
                                'top_id'=>$user['top_id'],
                                'amount'=>$withdraw_amount,
                                'withdraw_ip'=>$GLOBALS['REQUEST']['client_ip'],
                                'create_time'=>date('Y-m-d H:i:s'),
                            );
                            if(withdrawIps::addItem($ipNotes)){
                                die('success');
                            }else{
                                log2file('ipException.log', $ipNotes);
                            }
                        }
                    }
                }else{
                    die("it's ok");
                }
                die();
            }
            if (!$secpassword = $this->request->getPost('secpassword', 'trim')) {
                $errstr = $showmsg = '请输入资金密码';
                $this->_checkWithdraw($flag, 6, $errstr, $showmsg);
            }
            $withdraw_amount = $this->request->getPost('withdraw_amount', 'floatval');
            if ($withdraw_amount <= 0) {
                $errstr = $showmsg = '请填写正确的金额';
                $this->_checkWithdraw($flag, 7, $errstr, $showmsg);
            }

            //提交提款请求
            $result = withdraws::applyWithdraw($GLOBALS['SESSION']['user_id'], $userBindCard['bank_id'], $userBindCard['card_num'], $userBindCard['province'], $userBindCard['city'], $userBindCard['branch'], $withdraw_amount, $secpassword,$userBindCard['bank_username']);
            $wid=!is_numeric($result)?-1:$result;
            $GLOBALS['mc']->set(__CLASS__, __FUNCTION__.'wid', $wid,120);
            if (!is_numeric($result)) {
                $errstr = $showmsg = $result;
                $this->_checkWithdraw($flag, 9, $errstr, $showmsg);
            }

            if ($user['is_test']) {
                withdraws::payTester($result);
                $errstr = $showmsg = '提款成功[测试帐号]';
                $this->_checkWithdraw($flag, 10, $errstr, $showmsg);
            }
            else {
                $errstr = '添加提款成功，请等待审核';
                $showmsg = '添加提款成功，请等待审核<script>showBalance();</script>';
                $this->_checkWithdraw($flag, 0, $errstr, $showmsg);
            }
        }

        $subLen = mb_strlen($userBindCard['bank_username'],'utf-8')-1;
        $userBindCard['bank_username'] = '*' . mb_substr($userBindCard['bank_username'], 1, $subLen, 'utf-8');
        $card_num = $userBindCard['card_num'];
        $userBindCard['card_num'] = substr($card_num,0,4) . str_repeat('*',strlen($card_num)-8) . substr($card_num, -4, 4);

        $this->_checkWithdraw($flag, 0, '', '');
		$config = M('config');
		$min = $config->field('cfg_value')->where(['cfg_key' => 'min_withdraw_limit'])->find();
		$max = $config->field('cfg_value')->where(['cfg_key' => 'max_withdraw_limit'])->find();
		$dayMax = $config->field('cfg_value')->where(['cfg_key' => 'max_withdraw_day_limit'])->find();
        //得到支持的银行列表
        self::$view->setVar('withdrawBankList', $GLOBALS['cfg']['withdrawBankList']);
        self::$view->setVar('userBindCard', $userBindCard);
        self::$view->setVar('user', $user);
		self::$view->setVar('min', $min['cfg_value']);
		self::$view->setVar('max', $max['cfg_value']);
		self::$view->setVar('dayMax', $dayMax['cfg_value']);
        self::$view->render('fin_withdraw');
    }
    private function _orderList($startDate, $endDate, $include_childs)
    {
        static $orderTypes = [
            101 => '账户充值',
            102 => '充值优惠',
            //103 => '首冲送',
            107 => '活动礼金',
            154 => '接收转账',
            201 => '账户提现',
            212 => '充入下级',
            301 => '投注返点',
            302 => '投注抽水',
            303 => '撤单返款',
            304 => '追号返款',
            308 => '投注返奖',
            321 => '平台理赔',
            401 => '投注扣款',
            414 => '追号扣款',
            415 => '奖池嘉奖',
            //501 => '日流水返佣',
            //502 => '日亏损返佣',
            503 => '日投注返佣',
            600 => '抽奖扣款',
            //601 => '注册送',
            //602 => '签到送',
            //603 => '日盈利送',
            //604 => '日亏损送',
            155 => '手工增余额',
            202 => '手工减余额',
            701 => '电子返水',
        ];

        $orderType = $this->request->getGet('orderType', 'intval') ?:array_keys($orderTypes);
        $lotteryId = $this->request->getGet('lotteryId', 'intval', 0);
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;


        if(($username = $this->request->getGet('username', 'trim')) && $username != $GLOBALS['SESSION']['username']){
            if(!$user = users::getItem($username,8,false,1,1)){
                showMsg("非法请求，该用户不存在或已被冻结");
            }
            if($username != $GLOBALS['SESSION']['username'] && !in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))){
                showMsg("非法请求，此用户不是你的下级");
            }
        } else {
            $username = $GLOBALS['SESSION']['username'];
        }

        $paramUserName = $username != $GLOBALS['SESSION']['username'] ? $username : '';

        $packageIds = [];
        $trafficInfo = orders::getTrafficInfo($lotteryId, '', $orderType, $username, $include_childs, 0, 0, $startDate, $endDate);
        /***************** snow  获取正确的分页开始****************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);
        $startPos = getStartOffset($curPage, $trafficInfo['count']);
        /***************** snow  获取正确的分页开始****************************/

        $orders = orders::getItems($lotteryId, '', $orderType, $username, $include_childs, 0, 0, $startDate, $endDate, $startPos, DEFAULT_PER_PAGE);
        foreach ($orders as $k => $v) {
            $business = orders::$business[$v['type']];
            if ($v['business_id'] > 0 && isset($business['key_id']) && $business['key_id'] == 'wrap_id') {
                $orders[$k]['wrap_id'] = projects::wrapId($v['business_id'], $v['issue'], $v['lottery_id']);
                $packageIds[] = $v['business_id'];
            }
        }

        // $orderTypes = $GLOBALS['cfg']['orderTypes'];
        // unset($orderTypes[104], $orderTypes[161], $orderTypes[211], $orderTypes[321], $orderTypes[412], $orderTypes[151]);
        if ($GLOBALS['SESSION']['level'] == 10) {
            unset($orderTypes[212], $orderTypes[302], $orderTypes[501], $orderTypes[502]);
        }

        $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
        //得到所有帐变类型
        self::$view->setVar('orderTypes', $orderTypes);

        //预设查询框
        self::$view->setVar('orderType', is_array($orderType) ? 0 : $orderType);
        self::$view->setVar('lotteryId', $lotteryId);
        self::$view->setVar('lotteries', $lotterys);
        self::$view->setVar('orders', $orders);
        self::$view->setVar('pageList', getPageListMobile($trafficInfo['count'], DEFAULT_PER_PAGE));
        //self::$view->setVar('actionLinks', array(0 => array('title' => '增加游戏', 'url' => url('game', 'addConfig'))));

        self::$view->setVar('username', $paramUserName);
    }

    public function teamOrderList()
    {
        $this->searchDate($startDate, $endDate);

        $include_childs = $this->request->getGet('username', 'trim') ? 0 : 1;

        $this->_orderList($startDate, $endDate, $include_childs);
        self::$view->render('fin_teamorderlist');
    }

    public function orderList()
    {


        /******************************* 修改查询时间为2周以前。********************************/
//         $this->searchDate($startDate, $endDate, 15, 1);
        $startDate = date('Y-m-d 00:00:00', strtotime('-14 days'));
        $endDate   = date('Y-m-d 23:59:59');
        /******************************* 修改查询时间为2周以前。********************************/
        $this->_orderList($startDate, $endDate, 0);
        self::$view->render('fin_orderlist');
    }

    /**
     * 资金转移
     */
    public function tranMoney()
    {
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            showMsg("非法请求，该用户不存在或已被冻结");
        }
        if (!$op = $this->request->getPost('op', 'trim')) {
            showMsg("非法请求，没有操作类型");
        }
        if ($user['is_test'] == 1) {
            die(json_encode(array('errno' => 90012, 'errstr' => '测试帐号不能够执行转帐')));
        }
        switch ($op) {
            case 'show':
                //    $PT = new PT();
                //   $ptUser = $PT->getPlayerInfo($user['pt_username']);
                $balancePt = '未获取';
                //  if (isset($ptUser['balance'])) {
                //      $balancePt = $ptUser['balance'];
                //  }
                $balances = array('0' => $user['balance'], '1' => $balancePt);
                die(json_encode(array('errno' => 0, 'balances' => $balances)));
                break;
            case 'tran':
                if(!$user['secpwd']){
                    die(json_encode(array('errno' => 90001, 'errstr' => '您尚未设置资金密码')));
                }
                if (!is_numeric($tranFrom = $this->request->getPost('tranFrom', 'intval'))) {
                    die(json_encode(array('errno' => 90002, 'errstr' => '请选择转出方')));
                }
                if (!is_numeric($tranTo = $this->request->getPost('tranTo', 'intval'))) {
                    die(json_encode(array('errno' => 90004, 'errstr' => '请选择转入方')));
                }
                if (!is_numeric($tranAmount = $this->request->getPost('tranAmount', 'intval'))) {
                    die(json_encode(array('errno' => 90005, 'errstr' => '请输入正确金额')));
                }
                if (!$tranPass = $this->request->getPost('tranPass', 'trim')) {
                    die(json_encode(array('errno' => 90006, 'errstr' => '请输入资金密码')));
                }
                if ($user['secpwd'] != generateEnPwd($tranPass)) {
                    die(json_encode(array('errno' => 90006, 'errstr' => '请输入正确的资金密码')));
                }

                if ($tranFrom == 0 && $tranTo == 1) {
                    try {
                        if (transfers::transferInMW($user['user_id'], $tranAmount)) {
                            die(json_encode(array('errno' => 0, 'errstr' => '转入成功')));
                        }
                        else {
                            die(json_encode(array('errno' => 90008, 'errstr' => '资金转移出错，请联系咨询相关客服')));
                        }
                    } catch (Exception $ex) {
                        die(json_encode(array('errno' => 90010, 'errstr' => $ex->getMessage())));
                    }
                }
                elseif ($tranFrom == 1 && $tranTo == 0) {
                    try {
                        if (transfers::transferOutMW($user['user_id'], $tranAmount)) {
                            die(json_encode(array('errno' => 0, 'errstr' => '转出成功')));
                        }
                        else {
                            die(json_encode(array('errno' => 90009, 'errstr' => '资金转移出错，请联系咨询相关客服')));
                        }
                    } catch (Exception $ex) {
                        die(json_encode(array('errno' => 90011, 'errstr' => $ex->getMessage())));
                    }
                }
                else {
                    die(json_encode(array('errno' => 90007, 'errstr' => '非法的转入转出方')));
                }

                break;
            default:
                showMsg("非法请求，不支持的操作类型");
                break;
        }
    }

    /**
     * 检查用户是否绑定银行卡，必须是登陆状态
     */
    public function checkHasCard()
    {
        if( ! $this->isLogined() ){
            showMsg("非法请求，该用户不存在或已被冻结");
        }
        $cnt = userBindCards::getItemsNumber($GLOBALS['SESSION']['user_id']);
        echo $cnt >= 1 ? 'true' : 'false';
    }

    public function curlPostData($url, $data, &$info)
    {
        // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            logdump('CURL Errno' . curl_error($curl)); //捕抓异常
        }
        $info = curl_getinfo($curl);
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    public function xingyunPayDirect($pay_data)
    {
        $username = $pay_data['username'];
        $user_id = $pay_data['user_id'];
        $bank_id = $pay_data['bank_id'];

        if ($username != '' && $user_id != '') {
            $codes = $pay_data['codes'];

            if (!$merchantSN = authcode($codes, 'DECODE', 'a6sbe!x4^5d_ghd')) {
                die('系统出错！(错误码：6000)');
            }

            if ($merchantSN != substr($username, -5) . substr($username, 0, 1) . $user_id) {
                die('系统出错！(错误码：6001)');
            }

            $card_id = $pay_data['card_id'];

            if ($card_id == '') {
                die('系统出错！(错误码：6003)');
            }

            $cards = cards::getItemsById(array($card_id));

            if (count($cards) == 0) {
                die('系统出错！(错误码：6004)');
            }

            $shop_order_num = $pay_data['shop_order_num'];
            $deposit_amount = $pay_data['deposit_amount'];

            $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
            $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
            $payName = $GLOBALS['cfg']['pay_name'][$bank_id];
            $shop_url = $cards[$card_id]['shop_url'];
            $callbackurl = $cards[$card_id]['call_back_url'];
            $returnurl = $cards[$card_id]['return_url'];

            if ($shop_url == "") {
                $callbackurl = $domain . 'pay/' . $payName . 'Back.php';
                $returnurl = $domain . 'pay/hrefback.php';
            } else if (strpos($shop_url, '?c=pay') === 0) {

            } else if (strpos($shop_url, '?c=pay') > 0) {

            } else {
                $callbackurl = $shop_url . '/pay/' . $payName . 'Back.php';
                $returnurl = $shop_url . '/hrefback.php';
            }

            $requestURI = $pay_data['requestURI'];
            $bank_id = $pay_data['bank_id'];

            $data = array(
                'order_number' => $shop_order_num,
                'user_id' => $user_id,
                'username' => $username,
                'amount' => $deposit_amount,
                'pay_time' => date('Y-m-d H:i:s'),
                'source' => $_SERVER['HTTP_HOST'],
                'requestURI' => $requestURI,
                'card_id' => $card_id,
                'bank_id' => $bank_id,
            );

            if (pay::addItem($data)) {
                return $cards[$card_id]['netway'];
            }
        } else {
            die();
        }
    }

    public function shunfuPay($pay_data)
    {
        $username = $pay_data['username'];
        $user_id = $pay_data['user_id'];
        $card_id = $pay_data['card_id'];
        $bank_id = $pay_data['bank_id'];

        if ($card_id == '') {
            die('系统出错！(错误码：6003)');
        }

        $cards = cards::getItemsById(array($card_id));

        if (count($cards) == 0) {
            die('系统出错！(错误码：6004)');
        }

        if ($username != '' && $user_id != '') {
            $codes = $this->request->getPost('codes', 'trim');

            if (!$merchantSN = authcode($codes, 'DECODE', 'a6sbe!x4^5d_ghd')) {
                die('系统出错！(错误码：6000)');
            }

            if ($merchantSN != substr($username, -5) . substr($username, 0, 1) . $user_id) {
                die('系统出错！(错误码：6001)');
            }

            $card_id = $pay_data['card_id'];

            if ($card_id == '') {
                die('系统出错！(错误码：6003)');
            }

            $cards = cards::getItemsById(array($card_id));

            if (count($cards) == 0) {
                die('系统出错！(错误码：6004)');
            }

            $shop_order_num = $pay_data['shop_order_num'];
            $deposit_amount = $pay_data['deposit_amount'];

            $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
            $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
            $payName = $GLOBALS['cfg']['pay_name'][$bank_id];
            $shop_url = $cards[$card_id]['shop_url'];
            $callbackurl = $cards[$card_id]['call_back_url'];
            $returnurl = $cards[$card_id]['return_url'];

            if ($shop_url == "") {
                $callbackurl = $domain . 'pay/' . $payName . 'Back.php';
                $returnurl = $domain . 'pay/hrefback.php';
            } else if (strpos($shop_url, '?c=pay') === 0) {

            } else if (strpos($shop_url, '?c=pay') > 0) {

            } else {
                $callbackurl = $shop_url . '/pay/' . $payName . 'Back.php';
                $returnurl = $shop_url . '/hrefback.php';
            }

            $postFields = array(
                'merNo' => $cards[$card_id]['mer_no'],
                'payNetway' => $cards[$card_id]['netway'],
                'random' => (string)rand(1000, 9999),
                'orderNo' => $shop_order_num,
                'amount' => (string)($deposit_amount * 100),
                'goodsInfo' => '充值',
                'callBackUrl' => $callbackurl,
                'callBackViewUrl' => $returnurl,
                'clientIP' => $this->getClientIp(),
            );

            $apiUrl = $GLOBALS['cfg']['pay_url']['shunfu_api'];
            ksort($postFields);
            $postFields['sign'] = $this->getShunFuSign($postFields, $cards[$card_id]['mer_key']);
            $postJson = $this->shunfuJsonEncode($postFields);
            $postArray = array('data' => $postJson);
            $response = $this->shunfuPost($apiUrl, $postArray);
            $response = json_decode($response, true);
            $requestURI = $pay_data['requestURI'];
            $bank_id = $pay_data['bank_id'];

            if ($response !== null && isset($response['resultCode']) & $response['resultCode'] === '00') {
                $data = array(
                    'order_number' => $shop_order_num,
                    'user_id' => $user_id,
                    'username' => $username,
                    'amount' => $deposit_amount,
                    'pay_time' => date('Y-m-d H:i:s'),
                    'source' => $_SERVER['HTTP_HOST'],
                    'requestURI' => $requestURI,
                    'card_id' => $card_id,
                    'bank_id' => $bank_id,
                );

                if (pay::addItem($data)) {
                    return $response['qrcodeInfo'];
                }
            } else {
                if (isset($response['resultMsg'])) {
                    return 'error|' . $response['resultMsg'];
                } else {
                    echo '生成订单失败';
                }

                log2file('shunfuPay.log', $postFields);
                log2file('shunfuPay.log', $response);
            }
        } else {
            die();
        }
    }

    public function getShunFuSign($value, $key)
    {
        return strtoupper(md5($this->shunfuJsonEncode($value) . $key));
    }

    public function shunfuJsonEncode($input)
    {
        if (is_string($input)) {
            $text = $input;
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(
                array("\r", "\n", "\t", "\""),
                array('\r', '\n', '\t', '\\"'),
                $text);
            $text = str_replace("\\/", "/", $text);
            return '"' . $text . '"';
        } else if (is_array($input) || is_object($input)) {
            $arr = array();
            $is_obj = is_object($input) || (array_keys($input) !== range(0, count($input) - 1));
            foreach ($input as $k => $v) {
                if ($is_obj) {
                    $arr[] = self::shunfuJsonEncode($k) . ':' . self::shunfuJsonEncode($v);
                } else {
                    $arr[] = self::shunfuJsonEncode($v);
                }
            }
            if ($is_obj) {
                $arr = str_replace("\\/", "/", $arr);
                return '{' . join(',', $arr) . '}';
            } else {
                $arr = str_replace("\\/", "/", $arr);
                return '[' . join(',', $arr) . ']';
            }
        } else {
            $input = str_replace("\\/", "/", $input);
            return $input . '';
        }
    }

    public function shunfuPost($url,$data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);

        if (curl_errno($ch)) {
            return curl_error($ch);
        }

        return $tmpInfo;
    }

    public function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public function yifuPay($pay_data)
    {
        $username = $pay_data['username'];
        $user_id = $pay_data['user_id'];
        $card_id = $pay_data['card_id'];
        $bank_id = $pay_data['bank_id'];

        if ($card_id == '') {
            die('系统出错！(错误码：6003)');
        }

        $cards = cards::getItemsById(array($card_id));

        if (count($cards) == 0) {
            die('系统出错！(错误码：6004)');
        }

        if ($username != '' && $user_id != '') {
            $codes = $this->request->getPost('codes', 'trim');

            if (!$merchantSN = authcode($codes, 'DECODE', 'a6sbe!x4^5d_ghd')) {
                die('系统出错！(错误码：6000)');
            }

            if ($merchantSN != substr($username, -5) . substr($username, 0, 1) . $user_id) {
                die('系统出错！(错误码：6001)');
            }

            $card_id = $this->request->getPost('card_id', 'intval');

            if ($card_id == '') {
                die('系统出错！(错误码：6003)');
            }

            $cards = cards::getItemsById(array($card_id));

            if (count($cards) == 0) {
                die('系统出错！(错误码：6004)');
            }

            $shop_order_num = $pay_data['shop_order_num'];
            $deposit_amount = $pay_data['deposit_amount'];

            $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
            $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
            $payName = $GLOBALS['cfg']['pay_name'][$bank_id];
            $shop_url = $cards[$card_id]['shop_url'];
            $callbackurl = $cards[$card_id]['call_back_url'];
            $returnurl = $cards[$card_id]['return_url'];

            if ($shop_url == "") {
                $callbackurl = $domain . 'pay/' . $payName . 'Back.php';
                $returnurl = $domain . 'pay/hrefback.php';
            } else if (strpos($shop_url, '?c=pay') === 0) {

            } else if (strpos($shop_url, '?c=pay') > 0) {

            } else {
                $callbackurl = $shop_url . '/pay/' . $payName . 'Back.php';
                $returnurl = $shop_url . '/hrefback.php';
            }

            if ($cards[$card_id]["netway"] == "WX") {
                $service = "1001102";
            } else if ($cards[$card_id]['netway'] == "ZFB") {
                $service = "1001101";
            } else if ($cards[$card_id]['netway'] == "ZFB_WAP") {
                $service = "1001101";
            }

            $postFields = array(
                'version' => 'V1.2',
                'charset' => 'utf-8',
                'signType' => 'MD5',
                'service' => $service,
                'merchantNo' => $cards[$card_id]["mer_no"],
                'tradeNumber' => $shop_order_num,
                'body' => '充值',
                'timeStart' => date('YmdHis'),
                'notifyUrl' => $callbackurl,
                'totalFee' => $deposit_amount,
                'attach' => '充值',
            );

            $postFields['sign'] = $this->getYiFuSign($postFields, $cards[$card_id]["mer_key"]);
            $apiUrl = $GLOBALS['cfg']['pay_url']['yifu_api'];
            $info = array();
            $response = $this->curlPostData($apiUrl, $postFields, $info);
            $response = json_decode($response, true);

            if ($response != null && isset($response['content'])) {
                $requestURI = $pay_data['requestURI'];
                $bank_id = $pay_data['bank_id'];

                $data = array(
                    'order_number' => $shop_order_num,
                    'user_id' => $user_id,
                    'username' => $username,
                    'amount' => $deposit_amount,
                    'pay_time' => date('Y-m-d H:i:s'),
                    'source' => $_SERVER['HTTP_HOST'],
                    'requestURI' => $requestURI,
                    'card_id' => $card_id,
                    'bank_id' => $bank_id,
                );

                if (pay::addItem($data)) {
                    if (isset($response['content']['qrcode'])) {
                        return $response['content']['qrcode'];
                    } else {
                        return 'error|' . $response;
                    }
                } else {
                    log2file('yifuPay.log', $postFields);
                    log2file('yifuPay.log', $response);
                    echo '生成订单失败';
                }
            } else {
                if ($response != null && isset($response['message'])) {
                    return 'error|' . $response['message'];
                }

                log2file('yifuPay.log', $postFields);
                log2file('yifuPay.log', $response);
            }
        } else {
            die();
        }
    }

    public function getYiFuSign($signData, $merKey)
    {
        ksort($signData);
        $str = '';

        foreach ($signData as $key => $row) {
            if ($key != 'sign') {
                if ($str != '') {
                    $str = $str . '&';
                }

                $str .= $key . '=' . $row;
            }
        }

        $sign = md5($str . '&key=' . $merKey);

        return $sign;
    }

    public function huibaotongPay($payData)
    {
        $username = $payData['username'];
        $user_id = $payData['user_id'];
        $card_id = $payData['card_id'];
        $bank_id = $payData['bank_id'];

        if ($card_id == '') {
            die('系统出错！(错误码：6003)');
        }

        $cards = cards::getItemsById(array($card_id));

        if (count($cards) == 0) {
            die('系统出错！(错误码：6004)');
        }

        if ($username != '' && $user_id != '') {
            $codes = $payData['codes'];

            if (!$merchantSN = authcode($codes, 'DECODE', 'a6sbe!x4^5d_ghd')) {
                die('系统出错！(错误码：6000)');
            }

            if ($merchantSN != substr($username, -5) . substr($username, 0, 1) . $user_id) {
                die('系统出错！(错误码：6001)');
            }

            $card_id = $payData['card_id'];

            if ($card_id == '') {
                die('系统出错！(错误码：6003)');
            }

            $cards = cards::getItemsById(array($card_id));

            if (count($cards) == 0) {
                die('系统出错！(错误码：6004)');
            }

            $shop_order_num =$payData['shop_order_num'];
            $deposit_amount = $payData['deposit_amount'];

            $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
            $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
            $payName = $GLOBALS['cfg']['pay_name'][$bank_id];
            $shop_url = $cards[$card_id]['shop_url'];
            $callbackurl = $cards[$card_id]['call_back_url'];
            $returnurl = $cards[$card_id]['return_url'];

            if ($shop_url == "") {
                $callbackurl = $domain . 'pay/' . $payName . 'Back.php';
                $returnurl = $domain . 'pay/hrefback.php';
            } else if (strpos($shop_url, '?c=pay') === 0) {

            } else if (strpos($shop_url, '?c=pay') > 0) {

            } else {
                $callbackurl = $shop_url . '/pay/' . $payName . 'Back.php';
                $returnurl = $shop_url . '/hrefback.php';
            }

            $payType = '';

            if ($cards[$card_id]["netway"] == "WX_WAP") {
                $payType = "WXWAP";
            }

            $postData = array();
            $postData['merchantNo'] = $cards[$card_id]["mer_no"];
            $postData['merchantOrderno'] = $shop_order_num;
            $postData['requestAmount'] = $deposit_amount;
            $postData['noticeSysaddress'] = $callbackurl;
            $postData['memberNo'] = rand(1000, 9999);
            $postData['memberGoods'] = $shop_order_num;
            $postData['payType'] = $payType;
            $postData['hmac'] = $this->getReqHmacString($postData['merchantNo'], $postData['merchantOrderno'], $postData['requestAmount'], $postData['noticeSysaddress'], $postData['memberNo'], $postData['memberGoods'], $postData['payType'], $cards[$card_id]["mer_key"]);
            $apiUrl = $GLOBALS['cfg']['pay_url']['huibaotong_api'];
            $info = array();
            $response = $this->curlPostData($apiUrl, http_build_query($postData), $info);
            $response = json_decode($response, true);

            if ($response != null && isset($response['code']) && $response['code'] == '000') {
                $requestURI = $payData['requestURI'];
                $bank_id = $payData['bank_id'];

                $data = array(
                    'order_number' => $shop_order_num,
                    'user_id' => $user_id,
                    'username' => $username,
                    'amount' => $deposit_amount,
                    'pay_time' => date('Y-m-d H:i:s'),
                    'source' => $_SERVER['HTTP_HOST'],
                    'requestURI' => $requestURI,
                    'card_id' => $card_id,
                    'bank_id' => $bank_id,
                );

                if (pay::addItem($data)) {
                    return $response['payUrl'];
                } else {
                    log2file('huibaotongPay.log', $postData);
                    log2file('huibaotongPay.log', $response);
                    echo '生成订单失败';
                }

                die();
            }  else {
                log2file('huibaotongPay.log', $postData);

                if ($response != null) {
                    log2file('huibaotongPay.log', $response);

                    if (isset($response['message'])) {
                        return 'error|' . $response['message'];
                    }
                } else {
                    echo '生成订单失败';
                }
            }
        } else {
            die();
        }
    }

    public function getReqHmacString ($pId,$pOrder,$pAmt,$pUrl,$pUid,$pPid,$pType,$merchantKey) {
        $sbOld = "";
        $sbOld = $sbOld . $pId;
        $sbOld = $sbOld . $pOrder;
        $sbOld = $sbOld . $pAmt;
        $sbOld = $sbOld . $pUrl;
        $sbOld = $sbOld . $pUid;
        $sbOld = $sbOld . $pPid;
        $sbOld = $sbOld . $pType;

        return $this->HmacMd5($sbOld, $merchantKey);
    }

    public function HmacMd5 ($data, $key) {
        $key = iconv("GB2312", "UTF-8", $key);
        $data = iconv("GB2312", "UTF-8", $data);

        $b = 64;

        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

    public function xiangjiaoPay($payData)
    {
        $username = $payData['username'];
        $user_id = $payData['user_id'];
        $card_id = $payData['card_id'];
        $bank_id = $payData['bank_id'];

        if ($card_id == '') {
            die('系统出错！(错误码：6000)');
        }

        if ($username != '' && $user_id != '') {
            $codes = $payData['codes'];

            if (!$merchantSN = authcode($codes, 'DECODE', 'a6sbe!x4^5d_ghd')) {
                die('系统出错！(错误码：6001)');
            }

            if ($merchantSN != substr($username, -5) . substr($username, 0, 1) . $user_id) {
                die('系统出错！(错误码：6002)');
            }

            $cards = cards::getItemsById(array($card_id));

            if (count($cards) == 0) {
                die('系统出错！(错误码：6003)');
            }

            $shop_order_num = $payData['shop_order_num'];
            $deposit_amount = $payData['deposit_amount'];

            $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
            $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
            $payName = $GLOBALS['cfg']['pay_name'][$bank_id];
            $shop_url = $cards[$card_id]['shop_url'];
            $callbackurl = $cards[$card_id]['call_back_url'];
            $returnurl = $cards[$card_id]['return_url'];

            if ($shop_url == "") {
                $callbackurl = $domain . 'pay/' . $payName . 'Back.php';
                $returnurl = $domain . 'pay/hrefback.php';
            } else if (strpos($shop_url, '?c=pay') === 0) {

            } else if (strpos($shop_url, '?c=pay') > 0) {

            } else {
                $callbackurl = $shop_url . '/pay/' . $payName . 'Back.php';
                $returnurl = $shop_url . '/hrefback.php';
            }

            $platfrom = '';

            if ($cards[$card_id]["netway"] == "WX") {
                $platfrom = "CR";
            } else if ($cards[$card_id]['netway'] == "ZFB") {
                $platfrom = "CR_ALI";
            } else if ($cards[$card_id]['netway'] == "ZFB_WAP") {
                $platfrom = "CR_ALI";
            }

            $postData = array();
            $postData['cid'] = (int)($cards[$card_id]["mer_no"]);
            $postData['total_fee'] = $deposit_amount * 100;
            $postData['title'] = '充值';
            $postData['attach'] = '充值';
            $postData['platform'] = $platfrom;
            $postData['orderno'] = $shop_order_num;
            $postData['sign'] = $this->getXiangJiaoSign($postData,  $cards[$card_id]["mer_key"]);
            $apiUrl = $GLOBALS['cfg']['pay_url']['xiangjiao_api'];
            $info = array();
            $jsonResponse = $this->curlPostData($apiUrl, json_encode($postData), $info);
            $response = json_decode($jsonResponse,true);

            if ($response != null && isset($response['err']) && $response['err'] == '0') {
                $requestURI = $payData['requestURI'];
                $bank_id = $payData['bank_id'];

                $data = array(
                    'order_number' => $shop_order_num,
                    'user_id' => $user_id,
                    'username' => $username,
                    'amount' => $deposit_amount,
                    'pay_time' => date('Y-m-d H:i:s'),
                    'source' => $_SERVER['HTTP_HOST'],
                    'requestURI' => $requestURI,
                    'card_id' => $card_id,
                    'bank_id' => $bank_id,
                );

                if (pay::addItem($data)) {
                    return $response['code_url'];
                } else {
                    log2file('xiangjiaoPay.log', $postData);
                    log2file('xiangjiaoPay.log', $jsonResponse);
                    return 'error|生成订单失败';
                }
            } else {
                log2file('xiangjiaoPay.log', $jsonResponse);
                log2file('xiangjiaoPay.log', $response);

                if ($response != null && isset($response['msg'])) {
                    return 'error|' . $response['msg'];
                } else {
                    return 'error|生成订单失败';
                }
            }
        } else {
            die();
        }
    }

    public function getXiangJiaoSign($data, $key)
    {
        $sign = strtoupper(md5($data['attach'] . $data['cid'] . $data['orderno'] . $data['title'] . $data['total_fee'] . $data['platform']. $key));
        return $sign;
    }
}

?>
