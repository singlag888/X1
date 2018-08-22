<?php

/**
 * 活动的工具类
 * 处理与活动有关的公共方法
 */
class userGiftsTools {
    function __construct() {
        ;
    }

    /**
     * 获得用户已用流水
     * @param type $userId
     * @param type $startTime
     * @param type $endTime
     */
    static public function getCurrDayUserWater($userId)
    {
        $startTime = date('Y-m-d 00:00:00', time());

        // 获得用户当天（除PT外）投注流水
        $totalWater = 0;
        $userPackages = projects::getPackages(0, -1, 0, '', -1, 0, $userId, 0, '', '', $startTime, '', 0);
        if ($userPackages) {
            foreach ($userPackages as $v) {
                $totalWater += $v['amount'];
            }
        }

        return array(
                'totalWater' => $totalWater, // 从0点累计到当前时间总流水
        );
    }

}