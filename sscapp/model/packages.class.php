<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

class packages extends baseModel
{

    public function getNewWinList()
    {
        $cacheKey = __CLASS__ . __FUNCTION__;
        $data = $GLOBALS['redis']->get($cacheKey);
        if (!$data) {
        $data = $this
            ->alias('p')
            ->field('
                    p.lottery_id,
                    l.cname,
                    p.issue,
                    p.user_id,
                    u.username,
                    p.create_time,
                    p.prize'
            )
            ->join(' __USERS__ u ON p.user_id = u.user_id', 'LEFT')
            ->join(' __LOTTERY__ l ON l.lottery_id = p.lottery_id', 'LEFT')
            ->where([
                'p.check_prize_status' => 1,
                'p.cancel_status' => 0,
            ])
            ->order('create_time DESC')
            ->select();

            $GLOBALS['redis']->setex($cacheKey, 600, json_encode($data, JSON_UNESCAPED_UNICODE));
        } else {
            $data = json_decode($data, true);
        }

        return $data;
    }

}