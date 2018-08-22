<?php

//include_once ADMIN_PATH . 'model/userGiftsControl.php';
/* * ****************************************************
 * FILE      : 用于转盘抽奖活动， 排奖计划与中奖派奖逻辑
 * @copyright: 开发部
 * @Describe : 活动下线可删除本文件及DB user_gifts_roulette
 *             文件中所有时间参数都是内部传过来的， 已经确保正确性，所以
 *             为效率考虑可以不进行日期参数的正确性校验
 * **************************************************** */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 用于转盘活动， 排奖计划与中奖派奖逻辑
 * @author Davy
 */
class userGiftsVoucher extends userGiftsBase implements userGiftsInerface
{

    /**
     * 活动的类型
     */
    public $gift_type = 'voucher';

    /**
     * 活动的中文名称
     */
    public $cnTitle = '现金礼券';

    function __construct($settings)
    {
        parent::__construct($settings);
    }

    public function createTopButton()
    {
        $html = '<a href="/?&c=user&a=userGifts&giftType=voucher" target="_blank" class="topLinkBtn">现金礼券白送啦</a>';

        return $html;
    }

    public function createRightFloat()
    {
        $html = '<div id="FloatAD" class="ADBox"><a href="/?&c=user&a=userGifts&giftType=voucher" target="_blank"><img src="images/sign/float.png" width="180" height="185"></a><span onclick="document.getElementById(\'FloatAD\').style.display=\'none\';" class="ADcloser"></span></div>';

        return $html;
    }

    public function createBanner()
    {
        $html = '<li style="background: url(images/sign/qiandaobanner.jpg) no-repeat center top"><a href="/?&c=user&a=userGifts&giftType=voucher&alert=1" target="_blank">voucher</a></li>';

        return $html;
    }

    public function runFrontLogicControll($request, $view)
    {
        $alert = $request->getGet('alert', 'intval', 0);
        if ($alert) {
            //$view->render('user_gifts_sign');
            exit;
        }
        else {
            $priceLevel = array(
                1000 => 6,
                3000 => 20,
                8000 => 60,
                15000 => 130,
                35000 => 280,
                100000 => 600,
                250000 => 1500,
                550000 => 3800,
            );
            $DayPackages = projects::getUsersDayPackages(date('Y-m-d 00:00:00'), date('Y-m-d H:i:s'), 0, $GLOBALS['SESSION']['user_id'], true);
            
            $view->setVar('markDays102', $markDays102);
            $view->render('user_gifts_sign');
            exit;
        }
    }

    public function cronPerformLogic($prama = array())
    {
        if (empty($prama)) {
            throw new exception2('活动参数不正确');
        }
        $date = date("Y-m-d 23:59:59", strtotime($prama['date']) - 86400);
        $startDate = $this->promoStartTime;
        $endDate = $this->promoEndTime;
        $yesterdayDate = date("Y-m-d", strtotime($date));
        if (strtotime($date) > strtotime($endDate) || strtotime($date) < strtotime($startDate)) {
            die('活动日期在有效范围之外');
        }
        $mission102 = array(
            1888 => array(3 => 8, 5 => 18, 8 => 38, 12 => 58, 15 => 98),
            5888 => array(3 => 38, 5 => 108, 8 => 188, 12 => 298, 15 => 388),
            12888 => array(3 => 88, 5 => 158, 8 => 258, 12 => 398, 15 => 888),
            18888 => array(3 => 198, 5 => 398, 8 => 518, 12 => 968, 15 => 1288),
            38888 => array(3 => 398, 5 => 658, 8 => 1088, 12 => 1980, 15 => 2580),
            88888 => array(3 => 688, 5 => 1588, 8 => 2288, 12 => 3888, 15 => 5888),
            188888 => array(3 => 1988, 5 => 3088, 8 => 5088, 12 => 9888, 15 => 13888),
        );
        $sendPrizeDay = array(3 => 3, 5 => 5, 8 => 8, 12 => 12, 15 => 15);

        //统计每日用户流水,达到标准就插入一条
        $userPackages = projects::getUsersDayPackages($yesterdayDate . ' 00:00:00', $date, 0, 0, true);

        foreach ($userPackages as $v) {

            if ($v['total_amount'] >= 1888 && $v['create_date'] == $yesterdayDate) {//必须大于最小标准并且是昨天投注数据才继续
                if ($v['total_amount'] >= 188888) {
                    $standard = 188888;
                }
                elseif ($v['total_amount'] >= 88888) {
                    $standard = 88888;
                }
                elseif ($v['total_amount'] >= 38888) {
                    $standard = 38888;
                }
                elseif ($v['total_amount'] >= 18888) {
                    $standard = 18888;
                }
                elseif ($v['total_amount'] >= 12888) {
                    $standard = 12888;
                }
                elseif ($v['total_amount'] >= 5888) {
                    $standard = 5888;
                }
                else {
                    $standard = 1888;
                }

                echo "检测投注用户user_id:{$v['user_id']},username={$v['username']},当日流水{$v['total_amount']},对应等级为{$standard}:";
                $userGifts = userGifts::getItems($startDate, $date, $v['user_id'], 9, 102);
                if ($userGifts) {//如果有投注记录因为倒序取第一条也就是最近的一天
                    if (strtotime($userGifts[0]['to_time']) == strtotime(date("Y-m-d", strtotime($date) - 86400))) {//对于定时任务来说是否前天生成过投注记录否则说明投注断开了
                        echo "检测到是连续投注to_time为{$userGifts[0]['to_time']}\n";
                        $prizeLeve = floor($userGifts[0]['gift']); //默认是以前天投注奖金标准
                        $progress = $userGifts[0]['progress'] + 1;
                        $data = array(
                            'progress' => $progress,
                            'to_time' => $yesterdayDate,
                        );
                        if ($v['total_amount'] < $userGifts[0]['gift']) {//时间线上最小金额为标准
                            $data['gift'] = $prizeLeve = $standard;
                        }

                        if (isset($sendPrizeDay[$progress])) {//达到规定天数则给钱,并给相应等级的奖金
                            echo "连续投注，且达到第{$progress}天，准备派发礼金\n";
                            $promos = promos::getItems($v['user_id'], 0, 0, 8, 6, date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")); //查询执行当天的是否有发奖记录
                            if ($promos) {//如果存在记录 查看其中是否有投注活动记录
                                foreach ($promos as $promo) {
                                    if ($promo['notes'] == 102) {   //如果是投注活动直接跳出，不重复发奖
                                        if (!userGifts::updateItem($userGifts[0]['ug_id'], $data)) {
                                            echo '[异常]ug_id:' . $userGifts[0]['ug_id'] . '更新进度：' . $progress . '失败' . "\n";
                                        }
                                        echo "已经派发过礼金，不再发放\n";
                                        continue 2;
                                    }
                                }
                            }
                            if ($mission102[$prizeLeve][$sendPrizeDay[$progress]] > $this->giftAmountLimit) {//果发钱金额大于活动发放最大限度
                                echo "第{$progress}天，准备派发礼金{$mission102[$prizeLeve][$sendPrizeDay[$progress]]},但是大于规定的额数{$this->giftAmountLimit},以红包形式发送\n";

                                $giftData['user_id'] = $v['user_id'];
                                $giftData['progress'] = $progress;
                                $giftData['ug_id'] = $userGifts[0]['ug_id'];
                                $giftData['gift'] = $mission102[$prizeLeve][$sendPrizeDay[$progress]];
                                $giftData['type'] = 102;
                                $giftData['title'] = '投注优惠';
                                $giftData['from_time'] = $userGifts[0]['from_time'];
                                $giftData['to_time'] = $yesterdayDate;

                                if (!$this->checkSendGiftAmount('sign', $giftData)) {//以红包方式发送
                                    echo 'ug_id:' . $userGifts[0]['ug_id'] . '更新进度：' . $progress . ',以红包发送失败';
                                }
                                else {
                                    echo "ug_id:{$userGifts[0]['ug_id']}发送红包形式{$mission102[$prizeLeve][$sendPrizeDay[$progress]]}成功！";
                                }
                                continue;
                            }
                            else {
                                if (!userGifts::sendUserGifts($userGifts[0]['ug_id'], 0, '102', $mission102[$prizeLeve][$sendPrizeDay[$progress]], $data, $userGifts[0]['from_time'], $yesterdayDate)) {
                                    echo "[异常]ug_id:" . $userGifts[0]['ug_id'] . '发送礼金：' . $mission102[$prizeLeve][$sendPrizeDay[$progress]] . '失败';
                                }
                                else {
                                    echo "ug_id:{$userGifts[0]['ug_id']}发送礼金{$mission102[$prizeLeve][$sendPrizeDay[$progress]]}成功！";
                                }
                                continue;
                            }
                        }
                        else {
                            if (!userGifts::updateItem($userGifts[0]['ug_id'], $data)) {
                                echo 'ug_id:' . $userGifts[0]['ug_id'] . '更新进度：' . $progress . '失败';
                            }
                            else {
                                echo "连续投注，但未达到第{$progress}天发放结点，因此只是progress++\n";
                            }
                            continue;
                        }
                    }
                    elseif (strtotime($userGifts[0]['to_time']) == strtotime(date("Y-m-d", strtotime($date)))) {
                        echo "重复运行\n";
                        continue;
                    }
                }
                //如果没有记录或者是断开的情况 则重新插入记录
                $data = array(
                    'type' => 102,
                    'title' => '投注优惠',
                    'user_id' => $v['user_id'],
                    'gift' => $standard,
                    'failed_gift' => 0,
                    'progress' => 1,
                    'status' => 9,
                    'from_time' => $v['create_date'],
                    'to_time' => $v['create_date'],
                    'apply_ip' => '0.0.0.0',
                    'remark' => '投注优惠',
                );
                if (!userGifts::addItem($data)) {
                    echo "[异常终止]添加userGifts失败:" . var_export($data, true);
                    exit;
                }
                $auto_id = $GLOBALS['db']->insert_id();
                echo "首次投注或者断开后再投注，新建一条记录ug_id={$auto_id}，初始礼金等级为{$standard}\n";
            }
        }
    }

}

?>