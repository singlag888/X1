<?php

/* * ****************************************************
 * FILE      : 用于注册活动
 * @copyright: 
 * @Describe :
 * **************************************************** */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}


class userGiftsRegister extends userGiftsBase implements userGiftsInerface
{

    /**
     * 活动的类型
     */
    public $gift_type = 'register';

    /**
     * 活动的中文名称
     */
    public $cnTitle = '注册活动';

    private $regTitle = '注册送';
    private $depTitle = '首冲送';

    private $regType = 601;
    private $depType = 103;

    private $lockKey = 'user_gift_register_lock_';

    function __construct($settings)
    {
        parent::__construct($settings);
    }

    public function createTopButton()
    {
        $html = '';

        return $html;
    }

    public function createRightFloat()
    {
        $html = '';

        return $html;
    }

    public function createBanner()
    {
        $cdnUrl = $this->getimgCdnUrl();
        $html = '<a href="javascript:;" class="lb-img1 fl"><img src="'.$cdnUrl.'/images_fh/index-2.jpg" alt=""></a>';

        return $html;
    }

    public function createMobileBanner()
    {
        $cdnUrl = $this->getimgCdnUrl(1);
        $html = '<a href="javascript:;"><img src="'.$cdnUrl.'/images/mobile/banner.png" alt=""></a>';

        return $html;
    }

    public function runFrontLogicControll($request, $view)
    {
        header("Content-type:text/html;charset=utf-8");
        if ($this->checkExpireTime() !== 102) {
            $notice = date('此活动在m月d号H:i开放!', strtotime($this->promoStartTime));
            die($notice);
        }
        $user_id = $GLOBALS['SESSION']['user_id'];
        $username = $GLOBALS['SESSION']['username'];

        $registerTime = users::getItem($user_id)['reg_time'];

/*
        if($registerTime >= $this->promoStartTime && userGifts::allowSendGift([$this->regType, $this->depType], $request->getClientIp()))
        {

            if(!userGifts::getItems('','',$user_id, '', 4, $this->depTitle))
            {
                $todayRegGifts = userGifts::getItems(date('Y-m-d 00:00:00'),'','', '', 4, $this->regTitle);
                $todayDepGifts = userGifts::getItems(date('Y-m-d 00:00:00'),'','', '', 4, $this->depTitle);

                $todayRegAmount = $todayRegGifts? array_sum(array_column($todayRegGifts, 'gift')): 0;
                $todayDepAmount = $todayDepGifts? array_sum(array_column($todayDepGifts, 'gift')): 0;

                //每日仅限2000名
                if(count($todayRegAmount)<2000 && count($todayDepAmount)<2000)
                {
                    //首次充值金额
                    $FirstDepositAmount = deposits::getFirstDeposits($username, 0, -1, $registerTime)['total_amount'];

                    if($this->getTotalBet() >= ($FirstDepositAmount + 28) && $FirstDepositAmount >= 18)
                    {
                        $DepositReward = true;
                    }
                }
            }
        }*/

        if($request->getPost('type', 'trim') == 'register' || $request->getPost('type', 'trim') == 'deposit')
        {
            if($GLOBALS['mc']->get($this->lockKey, $user_id)){
                die(urldecode(json_encode(array('errno' => 299018, 'errstr' => urlencode('系统繁忙，请稍后重试')))));
            }

            if (!$GLOBALS['mc']->set($this->lockKey, $user_id, 'lock', 30)) {
                $this->reposeAjax(299019, '系统错误，请稍后尝试');
            }

            if($registerTime < $this->promoStartTime){
                $this->reposeAjax(1, '此活动只有新用户可参加');
            }

            $limitAmount = 2000;

            $todayRegGifts = userGifts::getItems(date('Y-m-d 00:00:00'), '', '', '', $this->regType);
            $todayDepGifts = userGifts::getItems(date('Y-m-d 00:00:00'), '', '', '', $this->depType);

            if(count($todayRegGifts) >= $limitAmount || count($todayDepGifts) >= $limitAmount)
            {
                $userReg = userGifts::getItems(date('Y-m-d 00:00:00'), '', $user_id, '', $this->regType);
                $userDeposit = userGifts::getItems(date('Y-m-d 00:00:00'), '', $user_id, '', $this->depType);

                if(!$userReg && !$userDeposit){
                    $this->reposeAjax(2, '此活动每日仅限额'.$limitAmount.'名');
                }
            }

            if(!$cards = userBindCards::getItems($user_id)){
                $this->reposeAjax(3, '未绑定银行资料，请绑定后领取');
            }

/*            foreach ($cards as $card){
                if($card['status'] != 1){
                    $this->reposeAjax(4, '请检查您的银行卡是否都为锁定状态');
                }
            }*/
        }

        if($request->getPost('type', 'trim') == 'register')
        {
            if(userGifts::getItems('','',$user_id, '', $this->regType)){
                $this->reposeAjax(5, '您已经领取新用户注册礼金，请勿重复操作');
            }

            if(userGifts::existLikeIpUser([$this->regType], $request->getClientIp())){
                $this->reposeAjax(6, '您的IP地址已经领取过该活动礼金，无法重复领取');
            }

            if(userGifts::existSameBankUser([$this->regType])){
                $this->reposeAjax(7, '您所绑定的银行姓名，已经领取过该活动礼金，无法重复领取');
            }

/*            if($ugId = userGifts::addItem($this->generateRegData($request->getClientIp())))
            {
                if(userGifts::sendUserGifts($ugId, 0, strval($this->regType), 8 , [], date('Y-m-d'), '', $this->regType)){
                    $this->reposeAjax(0, '成功领取新用户注册礼金，祝您游戏愉快');
                }
            }*/

            $data = $this->generateRegData($request->getClientIp());
            if (userGifts::addUserGifts($data)) {
                $this->reposeAjax(0, '成功领取新用户注册礼金，祝您游戏愉快');
            }

            $this->reposeAjax(8, '领取失败');
        }

//        $FirstDepositAmount = deposits::getFirstDeposits($username, 0, -1, $registerTime)['total_amount'];

        $deposits = deposits::getItems($user_id, 0, -1, -1, 8);
        $depositCount = count($deposits);
        $FirstDepositAmount = $depositCount ? $deposits[$depositCount-1]['amount'] : 0;

        if($request->getPost('type', 'trim') == 'deposit')
        {
            if(userGifts::getItems('','',$user_id, '', $this->depType)){
                $this->reposeAjax(5, '您已成功领取活动礼金，请勿再次操作');
            }

            if(userGifts::existLikeIpUser([$this->depType], $request->getClientIp())){
                $this->reposeAjax(6, '您的IP地址已经领取过该活动礼金，无法重复领取');
            }

            if(userGifts::existSameBankUser([$this->depType])){
                $this->reposeAjax(7, '您所绑定的姓名，已经领取过该活动礼金，无法重复领取');
            }

            if($FirstDepositAmount < 300){
                $this->reposeAjax(8, '首次充值任务未达到300元，无法领取充值送礼金，完成首次充值任务即可领取');
            }

            $endDate = '';
            if(count($deposits) >1) $endDate = $deposits[$depositCount-2]['create_time'];

            if($this->getTotalBet($endDate) < ($FirstDepositAmount + 36) * 2) {
                $this->reposeAjax(9, '您的有效投注流水未完成，请详阅活动说明');
            }

/*            if($ugId = userGifts::addItem($this->generateDepositData($request->getClientIp())))
            {
                if(userGifts::sendUserGifts($ugId, 0, strval($this->depType), 28 , [], date('Y-m-d'), '', $this->depType)){
                    $this->reposeAjax(0, '成功领取首冲送礼金，祝您游戏愉快');
                }
            }*/

            $data = $this->generateDepositData($request->getClientIp());
            if (userGifts::addUserGifts($data)) {
                $this->reposeAjax(0, '成功领取首冲送礼金，祝您游戏愉快');
            }

            $this->reposeAjax(10, '领取失败');
        }

        $boundCard = userBindCards::getItems($user_id) ? true : false;

        $view->setVar('boundCard', $boundCard);
        $view->setVar('promoStartDate', date('Y年m月d号',strtotime($this->promoStartTime)));
        $view->setVar('FirstDepositAmount', $FirstDepositAmount);
        $view->render('user_gifts_register');
        exit;
    }


    /**
     * 生成insert数据
     * @param $clientIp
     * @return array
     */
    private function generateRegData($clientIp) {
        $data = array(
            'type' => $this->regType,
            'title' => $this->regTitle,
            'user_id' => $GLOBALS['SESSION']['user_id'],
            'gift' => 8,
            'failed_gift' => 0,
            'progress' => 1,
            'status' => 4,
            'from_time' => date('Y-m-d H:i:s'),
            'to_time' => date('Y-m-d H:i:s'),
            'apply_ip' => $clientIp,
            'admin_id' => 0,
            'remark' => $this->regTitle,
        );
        return $data;
    }

    /**
     * 生成insert数据
     * @param $clientIp
     * @return array
     */
    private function generateDepositData($clientIp) {
        $data = array(
            'type' => $this->depType,
            'title' => $this->depTitle,
            'user_id' => $GLOBALS['SESSION']['user_id'],
            'gift' => 36,
            'failed_gift' => 0,
            'progress' => 1,
            'status' => 4,
            'from_time' => date('Y-m-d H:i:s'),
            'to_time' => date('Y-m-d H:i:s'),
            'apply_ip' => $clientIp,
            'admin_id' => 0,
            'remark' => $this->depTitle,
        );
        return $data;
    }

    /**
     * 用户有效投注额
     * @return int|number
     */
    private function getTotalBet($endDate = ''){
/*        $lotteries = lottery::getItems();
        $lottery_ids = array_column($lotteries, 'lottery_id');
        $lottery_ids = array_diff($lottery_ids, [9, 10]); //除3D,排三*/
        $sql = 'SELECT SUM(amount) as amount FROM packages WHERE lottery_id NOT IN (9,10) AND user_id='.$GLOBALS['SESSION']['user_id'].' AND cancel_status=0 AND check_prize_status!=0';
        if($endDate) $sql .= ' AND create_time<=\''.$endDate.'\'';

        $result = $GLOBALS['db']->getRow($sql);

        return $result['amount'];

/*        $packages = projects::getPackages($lottery_ids,-1,-1,'',-1,0,$GLOBALS['SESSION']['user_id'],0,'','','','',0);

        return $packages? array_sum(array_column($packages, 'amount')):0;*/
    }

    /**
     * 响应ajax
     * @param $isFail
     * @param $info
     */
    private function reposeAjax($isFail, $info){
        $GLOBALS['mc']->delete($this->lockKey, $GLOBALS['SESSION']['user_id']);
        $error = $isFail ? 1 : 0;
        die(urldecode(json_encode(array('errno' => $error, 'errstr' => urlencode($info)))));
    }
}

?>