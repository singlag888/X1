<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}


class poolPrize
{

    const POOL_PRIZE_PROPORTION = 0.002;

    static public function getItem()
    {
        $sql = 'SELECT * FROM pool_prize';
        return $GLOBALS['db']->getRow($sql);
    }


    static public function updateItem($data)
    {
        return $GLOBALS['db']->updateSM('pool_prize', $data);
    }

    static public function addItem($data)
    {
        return $GLOBALS['db']->insert('pool_prize', $data);
    }
    
    static public function getCurDayPoolPrize(){
        $date = date('Y-m-d');
        $sql = 'SELECT SUM(amount-award_prize) AS total_pool_prize FROM packages WHERE lottery_id IN (1,4,8) AND cancel_status = 0 AND send_prize_status=1  AND send_prize_time<=\''.$date.' 23:59:59\'';
        $sql .= ' AND send_prize_time>=\''.$date.' 00:00:00\'';

        $packageAmount = $GLOBALS['db']->getRow($sql);
        return $packageAmount? $packageAmount['total_pool_prize'] * self::POOL_PRIZE_PROPORTION : 0;
    }

    /**
     * 获得当前期上一期奖池金额
     * @return int
     */
    static public function getPrevIssuePrizePool()
    {
        $amount = 0;
        $officialLotteryId = [1, 4, 8];

        foreach ($officialLotteryId as $lotteryId)
        {
            if($lastIssue = issues::getLastIssue($lotteryId))
            {
                $issue = $lastIssue['issue'];
                $sql = 'select SUM(amount) as total_amount from packages where lottery_id = '.$lotteryId . ' AND cancel_status = 0 AND issue = \''. $lastIssue['issue'] .'\'';
                $aPackages = $GLOBALS['db']->getRow($sql);
                $awardPrize = awards::getIssueAwardPrize($lotteryId, $issue);
                $amount += $aPackages['total_amount'] * self::POOL_PRIZE_PROPORTION - $awardPrize;
            }
        }

        return $amount;
    }

}

?>