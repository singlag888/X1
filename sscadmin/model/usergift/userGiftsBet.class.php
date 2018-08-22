<?php

/* * ****************************************************
 * FILE      : 用于输赢送活动
 * @copyright:
 * @Describe :
 * **************************************************** */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}


class userGiftsBet extends userGiftsBase implements userGiftsInerface
{
    /**
     * 活动的类型
     */
    public $gift_type = 'bet';

    /**
     * 活动的中文名称
     */
    public $cnTitle = '输赢活动';

    private $winType = 603;
    private $lossType = 604;

    private $lockKey = 'user_gift_bet_lock_';

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
        $html = '<a href="javascript:;" class="lb-img1 fl"><img src="'.$cdnUrl.'/images_fh/index-1.png" alt=""></a>';

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

        if($request->getPost('type', 'trim') == 'bet')
        {
            if($GLOBALS['mc']->get($this->lockKey, $user_id)){
                die(urldecode(json_encode(array('errno' => 299018, 'errstr' => urlencode('系统繁忙，请稍后重试')))));
            }

            if (!$GLOBALS['mc']->set($this->lockKey, $user_id, 'lock', 30)) {
                $this->reposeAjax(299019, '系统错误，请稍后尝试');
            }

            $today = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');

            if(userGifts::getItems($today,$todayEnd,$user_id, '', $this->winType) || userGifts::getItems($today,$todayEnd,$user_id, '', $this->lossType)){
                $this->reposeAjax(1, '您已经领取返利，请次日完成相关要求后再次领取');
            }

            $betAmount = $this->getTotalBet($prize);
            $profit = $prize - $betAmount;

            $isWin = $profit > 0 ? true : false;

            if(!$amountReward = $this->getRewardAmount($betAmount, $prize)){
                $this->reposeAjax(2, '您当日的有效投注流水为'.number_format($betAmount,2).'元，盈亏'.number_format($profit,2).'元，未达到领取礼金的条件');
            }

            if(userGifts::existLikeIpUser([$this->winType , $this->lossType], $request->getClientIp(), $today)){
                $this->reposeAjax(5, '您的IP地址已经领取过该活动礼金，无法重复领取');
            }

            if(userGifts::existSameBankUser([$this->winType,$this->lossType], $today)){
                $this->reposeAjax(6, '您所绑定的银行姓名，已经领取过该活动礼金，无法重复领取');
            }

            $type = $isWin ? $this->winType : $this->lossType;
            $data = $this->generateData($request->getClientIp(), $amountReward, $type);

/*            if($ugId = userGifts::addItem($data))
            {
                if(userGifts::sendUserGifts($ugId, 0, strval($this->cnTitle), $amountReward , [], date('Y-m-d'), '', $type)){
                    $this->reposeAjax(0, '您当天的有效投注流水为'.number_format($betAmount,2).'元，盈亏'.number_format($profit,2).'元，已成功领取'.$amountReward.'元礼金');
                }
            }*/

            if (userGifts::addUserGifts($data)) {
                $this->reposeAjax(0, '您当天的有效投注流水为'.number_format($betAmount,2).'元，盈亏'.number_format($profit,2).'元，已成功领取'.$amountReward.'元礼金');
            }

            $this->reposeAjax(3, '领取失败');
        }

        $view->render('user_gifts_bet');
        exit;
    }


    /**
     * 生成insert数据
     * @param $clientIp
     * @return array
     */
    private function generateData($clientIp, $gift, $type) {

        if($type == $this->winType){
            $title = '日盈利送';
        }else{
            $title = '日亏损送';
        }
        $data = array(
            'type' => $type,
            'title' => $title,
            'user_id' => $GLOBALS['SESSION']['user_id'],
            'gift' => $gift,
            'failed_gift' => 0,
            'progress' => 1,
            'status' => 4,
            'from_time' => date('Y-m-d'),
            'to_time' => date('Y-m-d'),
            'apply_ip' => $clientIp,
            'admin_id' => 0,
            'remark' => $this->cnTitle,
        );
        return $data;
    }


    /**
     * 获取嘉奖金额
     * @param $betAmount
     * @param $prize
     * @return int
     */
    private function getRewardAmount($betAmount, $prize)
    {
        $profitLosses = [
            ['min' => 3000, 'max'=>5000, 'bet'=>10000, 'loss_send'=>68, 'profit_send'=>28],
            ['min' => 5001, 'max'=>10000, 'bet'=>30000, 'loss_send'=>108, 'profit_send'=>48],
            ['min' => 10001, 'max'=>20000, 'bet'=>50000, 'loss_send'=>218, 'profit_send'=>88],
            ['min' => 20001, 'max'=>30000, 'bet'=>100000, 'loss_send'=>368, 'profit_send'=>138],
            ['min' => 30001, 'max'=>50000, 'bet'=>200000, 'loss_send'=>588, 'profit_send'=>218],
            ['min' => 50001, 'max'=>100000, 'bet'=>350000, 'loss_send'=>888, 'profit_send'=>368],
            ['min' => 100001, 'max'=>120000, 'bet'=>600000, 'loss_send'=>1888, 'profit_send'=>688],
            ['min' => 180001, 'max'=>300000, 'bet'=>1000000, 'loss_send'=>2888, 'profit_send'=>1288],
            ['min' => 300001, 'bet'=>1500000, 'loss_send'=>5888, 'profit_send'=>2888],
        ];
        $profitLosses = array_reverse($profitLosses);

//        $this->getTotalBet($betAmount, $prize);

        $amount = $betAmount - $prize;

        foreach ($profitLosses as $profitLoss)
        {
            if($betAmount >= $profitLoss['bet'] && abs($amount) >= $profitLoss['min'])
            {
                return $amount > 0 ? $profitLoss['loss_send'] : $profitLoss['profit_send'];
            }
        }
        return 0;
    }

    /**
     * 获取当天流水
     * @param $betAmount
     * @param $prize
     */
    private function getTotalBet(&$prize)
    {
        $startDate = date('Y-m-d 00:00:00');
        $endDate = date('Y-m-d 23:59:59');

        $sql = 'SELECT COALESCE(SUM(amount),0) as amount, COALESCE(SUM(prize),0) as prize FROM packages WHERE lottery_id NOT IN (9,10) AND user_id='.$GLOBALS['SESSION']['user_id'].' AND cancel_status=0 AND check_prize_status!=0';
        $sql .= ' AND create_time>=\''.$startDate.'\' AND create_time<=\''.$endDate.'\'';

        $result = $GLOBALS['db']->getRow($sql);

        $betAmount = $result['amount'];
        $prize = $result['prize'];

        return $betAmount;
/*        $packages = projects::getPackages($lottery_ids,1,-1,'',-1,0,$GLOBALS['SESSION']['user_id'],0,$startDate,$endDate,'','',0);

        foreach ($packages as $package){
            $prize += $package['prize'];
            $betAmount += $package['amount'];
        }*/
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