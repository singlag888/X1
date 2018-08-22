<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/13
 * Time: 16:53
 */
class crontab extends sscappController {
    /**
     * 切换redis 库
     * @param Closure $closure
     * @return mixed
     */
    public function cutRedisDatabase(Closure $closure)
    {
        $GLOBALS['redis']->select(REDIS_DB_APP);//>>切换到app库
        $result = $closure();//>>执行程序
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);//>>切换回默认库
        return $result;
    }
    public function getAllAmount()
    {
        $lotterys=lottery::getItemsNew(['lottery_id']);
        $loterys_amount=[];
        foreach ($lotterys as $lottery) {
            $data2 = projects::getAmountTotal($lottery['lottery_id'], -1, '', 0, '', '');
            $total_amount = empty($data2) || empty($data2['total_amount']) ? rand(10000,99999).'.'.rand(100,999) : $data2['total_amount'];
            $loterys_amount[$lottery['lottery_id']] = $total_amount;
        }
        $expire= strtotime(date('Y-m-d 23:59:59')) - time();
        $this->cutRedisDatabase(function()use($loterys_amount,$expire){
            $GLOBALS['redis']->setex('loterys_amount', $expire, serialize($loterys_amount));
        });
        echo 'getAllAmount:end';
    }
    const ODD_WIN_INTERCEPT = 40;     //热门游戏中奖率拦截值(百分比)
    const BET_PEOPLE_CEIL = 30;     //热门游戏下注人数向上提升(百分比)
    const TOTAL_CEIL = 30;     //热门游戏下注金额向上提升(百分比)
    const WIN_CEIL = 30;     //热门游戏中奖金额向上提升(百分比)
    const SERACHHOT_DAYTIME_BEFORE = 6;      //热门游戏查询几天前开始的,-1:表示全部
    const TOTAL_PRIZE_CEIL = 50;     //累计中奖金额向上提升(百分比)
    public function getLotteryCount()
    {
        $win_ceil = config::getConfig('win_ceil', self::WIN_CEIL);
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
        $total_ceil = config::getConfig('total_ceil', self::TOTAL_CEIL);
        $lottery_ids = array_column($lotterys, 'lottery_id');
        $bet_people_ceil = config::getConfig('bet_people_ceil', self::BET_PEOPLE_CEIL);
        $odd_win_intercept = config::getConfig('odd_win_intercept', self::ODD_WIN_INTERCEPT);
        $serachhot_daytime_before = config::getConfig('serachhot_daytime_before', self::SERACHHOT_DAYTIME_BEFORE);
        if ($serachhot_daytime_before < -1) {
           die('serachhot_daytime_before配置出错');
        } elseif ($serachhot_daytime_before == -1) {
            $endDate = '';
            $startDate = '';
        } else {
            $endDate = date('Y-m-d 23:59:59');
            $startDate = date("Y-m-d 00:00:00", strtotime("$endDate -$serachhot_daytime_before day"));
        }
        $loterys_count=[];
        $expire= strtotime(date('Y-m-d 23:59:59')) - time();
        foreach ($lottery_ids as $lottery_id){
            if($lottery_id==15) continue;
            $lo_pri_info=$this->getLotteryPrizeInfo($lottery_id,$startDate, $endDate,$expire);
            $data1=$lo_pri_info['prizeTotal'];
            $data2=$lo_pri_info['amountTotal'];
            $prize_count = empty($data1) || empty($data1['total_count']) ? 0 : $data1['total_count'];
            $total_prize = empty($data1) || empty($data1['total_prize']) ? 0 : $data1['total_prize'];
            $all_count = empty($data2) || empty($data2['total_count']) ? 0 : $data2['total_count'];
            $total_amount = empty($data2) || empty($data2['total_amount']) ? 0 : $data2['total_amount'];
            $odd_win = 0;
            if ($all_count != 0) $odd_win = round($prize_count / $all_count * 100, 2);
            if ($odd_win < $odd_win_intercept) {
                if ($odd_win_intercept < 50) $odd_win = $odd_win_intercept + $odd_win / 2;
                elseif ($odd_win_intercept < 80) $odd_win = $odd_win_intercept + $odd_win / 10;
                else $odd_win = $odd_win_intercept + $odd_win / 15;
            }
            $i_total=ceil($total_amount * (100 + $total_ceil) / 100);
            $i_bet_people=ceil($all_count * (100 + $bet_people_ceil) / 100);
            $i_prize=ceil($total_prize * (100 + $win_ceil) / 100);

            $loterys_count[]= [
                'lottery_id' => $lottery_id,
                'lottery_name' => $lotterys[$lottery_id]['cname'],
                'bet_people' => $i_bet_people,
                'total' => $i_total,
                'prize' => $i_prize,
                'odds_win' => $odd_win,
            ];

        }
        foreach ($loterys_count as $key => $row) {
            $total[$key] = $row['total'];
            $bet_peoples[$key] = $row['bet_people'];
            $openinfos[$key] = empty($info) ? 0 : 1;
        }
        array_multisort($openinfos, SORT_DESC, $total, SORT_DESC, $bet_peoples, SORT_DESC, $loterys_count);

        $this->cutRedisDatabase(function()use($loterys_count,$expire){
            $GLOBALS['redis']->setex('loterys_count', $expire, serialize($loterys_count));
        });
        echo 'getLotteryCount:end';
    }
    private function getLotteryPrizeInfo($lottery_id,$startDate, $endDate,$expire){
        $data1 = projects::getPrizeTotal($lottery_id, 1, '', 0, $startDate, $endDate);
        $this->cutRedisDatabase(function()use($lottery_id,$data1,$expire){
            $GLOBALS['redis']->setex('lottery_'.$lottery_id.'_prizeTotal',$expire,serialize($data1));
        });
        $data2 = projects::getAmountTotal($lottery_id, -1, '', 0, $startDate, $endDate);
        $this->cutRedisDatabase(function()use($lottery_id,$data2,$expire){
            $GLOBALS['redis']->setex('lottery_'.$lottery_id.'_amountTotal',$expire,serialize($data2));
        });
        return [
            'prizeTotal'=>$data1,
            'amountTotal'=>$data2,
        ];
    }
}
/*
 * 首页热门彩票
  public function getLotteryCount()
    {
        $loterys_count=$this->cutRedisDatabase(function(){
            return $GLOBALS['redis']->get('loterys_count');
        });
        if($loterys_count==false){
            $win_ceil = config::getConfig('win_ceil', self::WIN_CEIL);
            $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
            $total_ceil = config::getConfig('total_ceil', self::TOTAL_CEIL);
            $lottery_ids = array_column($lotterys, 'lottery_id');
            $bet_people_ceil = config::getConfig('bet_people_ceil', self::BET_PEOPLE_CEIL);
            $odd_win_intercept = config::getConfig('odd_win_intercept', self::ODD_WIN_INTERCEPT);
            $serachhot_daytime_before = config::getConfig('serachhot_daytime_before', self::SERACHHOT_DAYTIME_BEFORE);
            if ($serachhot_daytime_before < -1) {
                die('serachhot_daytime_before配置出错');
            } elseif ($serachhot_daytime_before == -1) {
                $endDate = '';
                $startDate = '';
            } else {
                $endDate = date('Y-m-d 23:59:59');
                $startDate = date("Y-m-d 00:00:00", strtotime("$endDate -$serachhot_daytime_before day"));
            }
            $loterys_count=[];
            $expire= strtotime(date('Y-m-d 23:59:59')) - time();
            foreach ($lottery_ids as $lottery_id){
                if($lottery_id==15) continue;
                $lo_pri_info=$this->getLotteryPrizeInfo($lottery_id,$startDate, $endDate,$expire);
                $data1=$lo_pri_info['prizeTotal'];
                $data2=$lo_pri_info['amountTotal'];
                $prize_count = empty($data1) || empty($data1['total_count']) ? 0 : $data1['total_count'];
                $total_prize = empty($data1) || empty($data1['total_prize']) ? 0 : $data1['total_prize'];
                $all_count = empty($data2) || empty($data2['total_count']) ? 0 : $data2['total_count'];
                $total_amount = empty($data2) || empty($data2['total_amount']) ? 0 : $data2['total_amount'];
                $odd_win = 0;
                if ($all_count != 0) $odd_win = round($prize_count / $all_count * 100, 2);
                if ($odd_win < $odd_win_intercept) {
                    if ($odd_win_intercept < 50) $odd_win = $odd_win_intercept + $odd_win / 2;
                    elseif ($odd_win_intercept < 80) $odd_win = $odd_win_intercept + $odd_win / 10;
                    else $odd_win = $odd_win_intercept + $odd_win / 15;
                }
                $i_total=ceil($total_amount * (100 + $total_ceil) / 100);
                $i_bet_people=ceil($all_count * (100 + $bet_people_ceil) / 100);
                $i_prize=ceil($total_prize * (100 + $win_ceil) / 100);

                $loterys_count[]= [
                    'lottery_id' => $lottery_id,
                    'lottery_name' => $lotterys[$lottery_id]['cname'],
                    'bet_people' => $i_bet_people,
                    'total' => $i_total,
                    'prize' => $i_prize,
                    'odds_win' => $odd_win,
                ];

            }
            foreach ($loterys_count as $key => $row) {
                $total[$key] = $row['total'];
                $bet_peoples[$key] = $row['bet_people'];
                $openinfos[$key] = empty($info) ? 0 : 1;
            }
            array_multisort($openinfos, SORT_DESC, $total, SORT_DESC, $bet_peoples, SORT_DESC, $loterys_count);

            $this->cutRedisDatabase(function()use($loterys_count,$expire){
                $GLOBALS['redis']->setex('loterys_count', $expire, serialize($loterys_count));
            });
        }else $loterys_count=unserialize($loterys_count);
        return $loterys_count;
    }
    public function hotShow()
    {
        list($cachekey, $expire) = $this->setHotCacheKeyExpire();
        $GLOBALS['redis']->select(REDIS_DB_APP);
        if (($res = $GLOBALS['redis']->get('hot_' . $cachekey)) == false) {
            $res=$this->getLotteryCount();
            $output = array_slice($res, 0, 3);
            foreach ($output as $k => $i) {
                $openinfo = $this->_openInfo($i['lottery_id']);
                $info = '';
                if (!empty($openinfo['issueInfo'])) $info = $openinfo['issueInfo'];
                if (!empty($info)) $info['count_down'] = strtotime($info['end_time']) - REQUEST_TIME;
                $output[$k]['openinfo'] = $info;
                $output[$k]['sertime'] = REQUEST_TIME;
            }
            $GLOBALS['redis']->select(REDIS_DB_APP);
            if ($expire == -1) {
                $GLOBALS['redis']->set('hot_' . $cachekey, serialize($output));
            } else $GLOBALS['redis']->setex('hot_' . $cachekey, $expire, serialize($output));
        } else $output = unserialize($res);

        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $output);
    }
 */
/*=======================================================================================================*/
/* 最新开奖
     public function getAllAmount()
    {
        $loterys_amount=$this->cutRedisDatabase(function(){
            return $GLOBALS['redis']->get('loterys_amount');
        });
        if($loterys_amount==false){
            $lotterys=lottery::getItemsNew(['lottery_id']);
            $loterys_amount=[];
            foreach ($lotterys as $lottery) {
                $data2 = projects::getAmountTotal($lottery['lottery_id'], -1, '', 0, '', '');
                $total_amount = empty($data2) || empty($data2['total_amount']) ? rand(10000,99999).'.'.rand(100,999) : $data2['total_amount'];
                $loterys_amount[$lottery['lottery_id']] = $total_amount;
            }
            $expire= strtotime(date('Y-m-d 23:59:59')) - time();
            $this->cutRedisDatabase(function()use($loterys_amount,$expire){
                $GLOBALS['redis']->setex('loterys_amount', $expire, serialize($loterys_amount));
            });
        }else $loterys_amount=unserialize($loterys_amount);
        return $loterys_amount;
    }

    public function newOpen()
    {
        $res = $this->_openInfo(0, 1);
        $amounts=$this->getAllAmount();
        foreach ($res as &$lottery) {
            $lottery_id=$lottery['lotteryId'];
            $total_amount = empty($amounts) || empty($amounts[$lottery_id]) ? 0 : $amounts[$lottery_id];
            $lottery['total_amount'] = $total_amount;
            $lottery['kTime'] = $this->calTime($lottery['lotteryId']);
        }
        unset($lottery);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, array_values($res));
    }
 */
