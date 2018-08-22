<?php

/* * ****************************************************
 * FILE      : 用于签到活动
 * @copyright: 
 * @Describe :
 * **************************************************** */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userGiftsSign extends userGiftsBase implements userGiftsInerface
{

    /**
     * 活动的类型
     */
    public $gift_type = 'sign';

    /**
     * 活动的中文名称
     */
    public $cnTitle = '签到送';

    public $signType = 602;

    private $mission101 = array(6888 => 58, 18888 => 128, 68888 => 388, 188888 => 1088, 888888 => 588, 1688888=>8888, 3888888=>18888);

    private $lockKey = 'user_gift_sign_lock_';

    function __construct($settings)
    {
        parent::__construct($settings);
    }
/*
    public function createTopButton()
    {
        $html = '<a href="index.jsp?&c=user&a=userGifts&giftType=sign" target="_blank" class="topLinkBtn">双重彩金第二轮</a>';

        return $html;
    }

    public function createRightFloat()
    {
        $html = '<div id="FloatAD" class="ADBox"><a href="index.jsp?&c=user&a=userGifts&giftType=sign" target="_blank"><img src="images/sign/float.png" width="180" height="185"></a><span onclick="document.getElementById(\'FloatAD\').style.display=\'none\';" class="ADcloser"></span></div>';

        return $html;
    }
*/
    public function createBanner()
    {
        $cdnUrl = $this->getimgCdnUrl();
        $html = '<a href="javascript:;" class="lb-img1 fl"><img src="'.$cdnUrl.'/images_fh/index-3.jpg" alt=""></a>';

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

        $user_id = $GLOBALS['SESSION']['user_id'];

        if($request->getPost('type', 'trim') == 'sign' || $request->getPost('type', 'trim') == 'reward')
        {
            if($GLOBALS['mc']->get($this->lockKey, $user_id)){
                die(urldecode(json_encode(array('errno' => 299018, 'errstr' => urlencode('系统繁忙，请稍后重试')))));
            }

            if (!$GLOBALS['mc']->set($this->lockKey, $user_id, 'lock', 30)) {
                $this->reposeAjax(299019, '系统错误，请稍后尝试');
            }
        }


        if ($this->checkExpireTime() !== 102) {
            $notice = date('此活动在m月d号H:i开放!', strtotime($this->promoStartTime));
            die($notice);
        }
/*        $sql = 'select user_id, sum(gift) From user_gifts group by user_id,type';
        $result = $GLOBALS['db']->getAll($sql);
        print_r($result);exit;*/

        $signCount = 0; //签到数
        $getReward = false; //是否领取奖励
        $isSignToday = false; //今天是否签到

        $endDate = date('Y-m-d 23:59:59');
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $yesterday = date('Y-m-d 00:00:00', strtotime('-1 days'));

        if(($userGifts = userGifts::getItems($startDate, $endDate, $GLOBALS['SESSION']['user_id'], '', $this->signType)))
        {
            $userGift = $userGifts[0];

            if ($userGift['to_time'] == date("Y-m-d 00:00:00")) {
                $isSignToday = true;
            }

            if($userGift['progress'] == 7 && $userGift['to_time'] == $yesterday){
                $signCount = 0;
            }
            elseif($userGift['to_time'] >= $yesterday){
                $signCount = intval($userGift['progress']);
            }

            foreach ($userGifts as $userGift){
                if($userGift['progress'] == 7 && $userGift['to_time'] == $yesterday && $userGift['status'] == 9){
                    if($this->getTotalBet($startDate, date('Y-m-d 23:59:59', strtotime('-1 days'))) >= min(array_keys($this->mission101))){
                        $getReward = true;
                        break;
                    }
                }
            }
        }

        //post签到
        if ($request->getPost('type', 'trim') == 'sign')
        {
            $signDay = $request->getPost('sign_day', 'intval', 1);

            if($signDay > 7 || $signDay < 1){
                $this->reposeAjax(1, '签到无效');
            }

            if($isSignToday || (!$signCount && $signDay != 1) || ($signCount && $signDay != $userGifts[0]['progress']+1) ){
                $this->reposeAjax(1, '请按签到顺序进行签到完成， 如上述所述');
            }

            $minBetAmount  = 688;
            if($this->getTotalBet(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')) < $minBetAmount){
                $this->reposeAjax(2, '您的有效投注流水未完成，无法签到');
            }

            $result = $signCount ? userGifts::updateItem($userGifts[0]['ug_id'],['progress' => $signCount+1,'to_time' => date("Y-m-d")]) : userGifts::addItem($this->generateData($request->getClientIp()));
            $this->reposeAjax(!$result, $result ? '签到成功，连续签到并达标要求可领取奖励' : '签到请求失败请重试');
        }

        //post领取奖励
        if ($request->getPost('type', 'trim') == 'reward')
        {
            if(!$getReward) $this->reposeAjax(1,'请连续完成签到7天后，次日领取活动奖励');

/*            if(userGifts::existLikeIpUser([$this->signType], $request->getClientIp())){
                $this->reposeAjax(5, '您的IP地址已经领取过该活动礼金，无法重复领取');
            }*/

            if(userGifts::existSameBankUser([$this->signType])){
                $this->reposeAjax(6, '您所绑定的银行姓名，已经领取过该活动礼金，无法重复领取');
            }

            $userGifts = userGifts::getItems($startDate, $yesterday, $GLOBALS['SESSION']['user_id'], 9, $this->signType);
            $ug_id = $userGifts[0]['ug_id'];

            $amount = $this->getActiveAmount();
            $userGiftData = array('status' => 4, 'verify_time' => date('Y-m-d H:i:s'), 'verify_admin_id' => 0, 'gift'=>$amount);

            if (!userGifts::sendUserGifts($ug_id, 0, strval($this->signType), $amount, $userGiftData, $userGifts[0]['from_time'], '', $this->signType)) {
                $this->reposeAjax(2,'领取失败请重试');
            }
            $this->reposeAjax(0,'您已成功领取奖励'.$amount.'元，可继续参加签到活动哦');
        }

        //本周投注金额
        $from_time = $signCount ? $userGifts[0]['from_time'] : date('Y-m-d 00:00:00');
        $thisBetAmount = $this->getTotalBet($from_time, date('Y-m-d 23:59:59'));

        //上周投注金额
        $lastBetAmount = 0;
        if($userGifts) {
            if(($signCount == 0 && $userGifts[0]['to_time'] == $yesterday)){
                $lastBetAmount = $this->getTotalBet($userGifts[0]['from_time'], date('Y-m-d 23:59:59', strtotime($userGifts[0]['to_time'])));
            }
            elseif($signCount && isset($userGifts[1]) && (strtotime($userGifts[1]['to_time']) + 24*3600) == strtotime($userGifts[0]['to_time'])){
                $lastBetAmount = $this->getTotalBet($userGifts[1]['from_time'], date('Y-m-d 23:59:59', strtotime($userGifts[1]['to_time'])));
            }

        }

        $view->setVar('signCount', $signCount);
        $view->setVar('promoStartTime', $this->promoStartTime);
        $view->setVar('getReward', $getReward);
        $view->setVar('isSignToday', $isSignToday);
        $view->setVar('thisBetAmount', $thisBetAmount);
        $view->setVar('lastBetAmount', $lastBetAmount);
        $view->render('user_gifts_sign');
        exit;
    }

    /**
     * 获取活动奖励金额
     * @return int
     */
    private function getActiveAmount(){
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $endDate = date('Y-m-d 23:59:59', strtotime('-1 days'));

        $betAmount = $this->getTotalBet($startDate, $endDate);
        $activeAmounts = array_reverse($this->mission101, true);

        $amount = 0;
        foreach ($activeAmounts as $bet=>$active){
            if($betAmount >= $bet){
                $amount = $active;
                break;
            }
        }
        return $amount;
    }

    /**
     * 投注总额
     * @param $startDate
     * @param $endDate
     * @return int|number
     */
    private function getTotalBet($startDate, $endDate)
    {
/*        $lotteries = lottery::getItems();
        $lottery_ids = array_column($lotteries, 'lottery_id');
        $lottery_ids = array_diff($lottery_ids, [9, 10]); //除3D,排三

        $packages = projects::getPackages($lottery_ids,1,-1,'',-1,0,$GLOBALS['SESSION']['user_id'],0,$startDate,$endDate,'','',0);
        return $packages? array_sum(array_column($packages, 'amount')):0;*/

        $sql = 'SELECT SUM(amount) as amount FROM packages WHERE lottery_id NOT IN (9,10) AND user_id='.$GLOBALS['SESSION']['user_id'].' AND cancel_status=0 AND check_prize_status!=0';
        $sql .= ' AND create_time>=\''.$startDate.'\' AND create_time<=\''.$endDate.'\'';

        $result = $GLOBALS['db']->getRow($sql);
        return $result['amount'];
    }

    /**
     * 当天的投注额
     * @return int
     * @throws exception2
     */
/*    private function todayBetAmount()
    {
        $lotteries = lottery::getItems();
        $lottery_ids = array_column($lotteries, 'lottery_id');
        $lottery_ids = array_diff($lottery_ids, [9, 10]); //除3D,排三

        $DayPackages = projects::getUsersDayPackages(date('Y-m-d 00:00:00'), date('Y-m-d H:i:s'), $lottery_ids, $GLOBALS['SESSION']['user_id'], false);
        return $DayPackages ? $DayPackages[0]['total_amount'] : 0;
    }*/

    /**
     * 生成insert数据
     * @param $clientIp
     * @return array
     */
    private function generateData($clientIp) {
        $data = array(
            'type' => $this->signType,
            'title' => '签到送',
            'user_id' => $GLOBALS['SESSION']['user_id'],
            'gift' => 0,
            'failed_gift' => 0,
            'progress' => 1,
            'status' => 9,
            'from_time' => date('Y-m-d'),
            'to_time' => date('Y-m-d'),
            'apply_ip' => $clientIp,
            'remark' => '签到送',
            'min_day_water' => 688,
            'min_total_water' => 6888,
            'is_include_child' => 0,
        );
        return $data;
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