<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}


class killNum
{

    static public function getItem($lotteryId,$issue)
    {
        $sql = 'SELECT * FROM ssc_share.`kill_num_'.$lotteryId.'` WHERE lottery_id='.$lotteryId.' AND issue=\''.$issue.'\'';
        return $GLOBALS['share_db']->getRow($sql);
    }

    static public function addItem($lotteryId,$data)
    {
        return $GLOBALS['share_db']->insert('ssc_share.kill_num_'.$lotteryId, $data);
    }

    static public function replaceAddItem($lotteryId,$data)
    {
        return $GLOBALS['share_db']->replaceInsert('ssc_share.kill_num_'.$lotteryId, $data);
    }

}

?>