<?php
//include_once ADMIN_PATH . 'model/userGiftsControl.php';
/* * ****************************************************
 * FILE      : 用于简单逻辑的活动
 * @copyright: 开发部
 * @Describe : 业务逻辑描述：
 *
 * 触发条件： 以客户当日的第一笔存款为基数， 若当日的有效投注流水达到一定的倍数，则回馈给客户一定的礼金
 * 执行逻辑：在每日凌晨的cron中判断昨日的流水，决定返回给客户的礼金，无需审核直接发放，进入帐变
 *
 * **************************************************** */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * @author Davy
 */
class userGiftsSimple extends userGiftsBase implements userGiftsInerface, userGiftFrontLogic
{
    /**
     * 流水<=>礼金规则
     * 举例：客户当日第一笔存款100元，当日的有效投注为2000元，则发放给用户100*13%=13元
     * 流水倍数　　≤10　≤15　≤20　≤35　≤60
     * 回馈比例　　6%　8%　13%　28%　50%
     */
    public $prizeReguler = array();

    /**
     * 活动的类型
     */
    public $gift_type = 'simple';

    /**
     * 活动的中文名称
     */
    public $cnTitle = '简单活动';

    /**
     * 该活动是否在红包里显示gift记录
     */
    public $showRecordOnRedGift = true;

    /**
     * @todo   参数化的优点在于以后便于模块化设计
     *          转向数据库读写参数做过渡准备
     * @author Davy 2015年12月22日
     * @param  $settings
     * @return
     */
    function __construct($settings){
        parent::__construct($settings);
        $this->prizeReguler = $settings['prizeReguler'];
    }

    /**
     * @todo   给用户显示的红包提示语言
     */
    public function redGiftShowMsgToUser($userGift){
        return $userGift['remark'];
    }

    /**
     * 根据规则和流水返回礼金比例
     * @param $water 流水
     * @param $baseValue 当日首存，作为统计比较基数
     */
    public function getPrizeScaleByWater($water, $baseValue)
    {
        //$this->prizeReguler = array(60 => 0.5, 35 => 0.28, 20 => 0.13, 15 => 0.08, 10 => 0.06);
        foreach ($this->prizeReguler as $multiple => $scale) {
            if(round($water, 2) >= round($multiple * $baseValue, 2)) {
                return array('prize' => round($baseValue * $scale, 2), 'deposit' => $baseValue, 'water' => $water, 'scale' => $scale);
            }
        }
        return array('prize' => 0, 'deposit' => $baseValue, 'water' => $water, 'scale' => 0);
    }



    /**
     * @todo   生产顶部按钮
     */
    public function createTopButton()
    {
        return '';
    }

    /**
     * @todo   生产Banner
     */
    public function createBanner()
    {
        return '';
    }

    /**
     * @todo   生产右下角浮动窗
     */
    public function createRightFloat()
    {
        return '';
    }

    /**
     * 发放礼金
     * @param $prizeByUsers 结构：$prizeByUsers[12345] = array('prize' => 12, 'deposit' => 200, 'water' => 2000, 'scale' => 0.06);
     * @param $date         活动日期
     */
    public function sendPrizeToUsers($prizeByUsers, $date)
    {
        if(empty($prizeByUsers)) {
            return false;
        }

        //所有用户信息
        $users = users::getItems(-1, true, 0, array('user_id', 'top_id', 'username', 'balance'), 8, 0);

        $GLOBALS['db']->startTransaction();

        //清除旧数据
//         echo "清除旧数据 \n";
//         $sql = "DELETE FROM user_gifts WHERE gift_type='" . $this->gift_type . "' AND from_time >= '{$date} 00:00:00' AND from_time <= '{$date} 23:59:59'";
//         if (!$GLOBALS['db']->query($sql)) {
//             $GLOBALS['db']->rollback();
//             throw new exception2("userGifts::delete Fail");
//         }

        //查询已发红包
        $sql = "SELECT user_id FROM user_gifts WHERE from_time >= '{$date} 00:00:00' AND from_time <= '{$date} 23:59:59' AND gift_type = 'simple'";
        $alreadySendUsers = $GLOBALS['db']->getAll($sql, 'user_id');

        //发送红包金额总计
        $total_amount = 0;
        //$prizeByUsers = array('prize' => $baseValue * $scale, 'deposit' => $baseValue, 'water' => $water, 'scale' => $scale);
        foreach ($prizeByUsers as $uid => $value) {
            //已发红包的跳过
            if(isset($alreadySendUsers[$uid])) {
                continue;
            }
            $prize = $value['prize'];           //礼金
            $firstDeposit = $value['deposit'];  //当日首存
            $water = $value['water'];           //当日流水
            $scale = $value['scale'];           //返奖比例
            if(!isset($users[$uid])) {
                throw new exception2("sendPrizeToUsers Not find user  user_id: " . $uid);
            }
            $scale = (string)($scale * 100) . '%';
            $remark = "日期：{$date}, 首存：{$firstDeposit}, 流水：{$water}, 返奖比例：{$scale}, 金额：{$prize}元";

            //1、写入user_gift表
            $giftdata = array(
                    'title' => $this->cnTitle,
                    'gift_type' => $this->gift_type,
                    'user_id' => $uid,
                    'gift' => $prize,
                    'from_time' => $date,
                    'to_time' => $this->promoEndTime,
                    'type' => 0,
                    'status' => 4,  //成功发放礼金
                    'progress' => 100,
                    'min_total_water' => 1,
                    'remark' => $remark,
            );

            if (!userGifts::addItem($giftdata)) {
                $GLOBALS['db']->rollback();
                throw new exception2("userGifts::addItem Fail :  user_id: " . $uid);
            }

            //2、写入promos表
            $promo_data = array(
                    'user_id' => $uid,
                    'top_id' => $users[$uid]['top_id'],
                    'type' => 6, //'活动红包'
                    'win_lose' => 0,
                    'amount' => $prize,
                    'create_time' => $date,
                    'notes' => $this->cnTitle . "-" . '活动红包',
                    'status' => 8, //已执行
                    'admin_id' => 0, //自动执行 无admin所以ID = 0
                    'verify_admin_id' => 0,
                    'verify_time' => $date,
                    'finish_admin_id' => 0,
                    'finish_time' => $date,
                    'remark' => '自动执行',
            );

            if (!promos::addItem($promo_data)) {
                $GLOBALS['db']->rollback();
                throw new exception2("promos::addItem Fail :  user_id: " . $uid);
            }

            $promo_id = $GLOBALS['db']->insert_id();

            //3、增加用户帐变
            $orderData = array(
                    'lottery_id' => 0,
                    'issue' => '',
                    //'package_id' => 0,
                    'from_user_id' => $uid,
                    'from_username' => $users[$uid]['username'],
                    'to_user_id' => 0,
                    'to_username' => '',
                    'type' => 107, //帐变类型107 礼券返现
                    'amount' => $prize,
                    'pre_balance' => $users[$uid]['balance'],
                    'balance' => $users[$uid]['balance'] + $prize,
                    'create_time' => $date,
                    'business_id' => $promo_id,
                    'admin_id' => 0,
            );

            if (!orders::addItem($orderData)) {
                throw new exception2("orders::addItem Fail : user_id: " . $uid);
            }

            //4、更新用户余额
            if (!users::updateBalance($uid, $prize)) {
                throw new exception2("users::updateBalance Fail : user_id: " . $uid);
            }
            echo "\n " . $users[$uid]['user_id'] . ' ' . $users[$uid]['username'] . " 详细信息 ： " . $remark . "\n";
            $total_amount += $prize;
        }


        //提交事务
        if (!$GLOBALS['db']->commit()) {
            return false;
        }
        echo ($this->cnTitle . " 成功发放礼金 总金额 = {$total_amount} \n");
        return true;
    }

    /**
     * 触发条件： 以客户当日的第一笔存款为基数， 若当日的有效投注流水达到一定的倍数，则回馈给客户一定的礼金
     * 执行逻辑：在每日凌晨的cron中判断昨日的流水，决定返回给客户的礼金，无需审核直接发放，进入帐变
     *
     * 注意事项：
     * 1、不能重复跑本cron 因为user_gift可以删但帐变和优惠表生成了,如果想重复跑请手动执行SQL处理(重复跑会生成重复数据)
     * 2、相关SQL
     *  -- 查询当日所有该活动的红包记录
        SELECT * FROM user_gifts WHERE from_time >= '$startTime' AND from_time <= '$endTime' AND gift_type = 'simple';

        -- 获取当日所有用户第一笔存款记录  注意时间限定字段为：finish_time
        SELECT user_id, amount FROM deposits WHERE status = 8 AND finish_time >= '$startTime'  AND finish_time <= '$endTime' GROUP BY user_id;

        -- 优惠表
        SELECT * FROM promos WHERE notes ='猴年首存吉祥红包-活动红包' create_time >= '' AND create_time <= '';

        -- 帐变表
        SELECT o.* FROM orders o LEFT JOIN promos p ON o.business_id=p.promo_id WHERE p.notes ='猴年首存吉祥红包-活动红包' o.create_time >= '' AND o.create_time <= '' AND o.type = 107;
     * @param  $cronParam  cron中传递的参数 Array
     * @author Davy 2015年12月23日
     * @return $prizeByUsers 结构：$prizeByUsers[12345] = array('prize' => 12, 'deposit' => 200, 'water' => 2000, 'scale' => 0.06);
     */
    public function cronPerformLogic($cronParam){
        //cron运行日期
        $date = $cronParam['date'];
        $is_test = 0;
        $status = 8;
        $startTime = $date . ' 00:00:00';
        $endTime = $date . ' 23:59:59';

        //获取当日所有用户第一笔存款记录  注意时间限定字段为：finish_time
        $sql = 'SELECT user_id, amount FROM deposits WHERE 1';
        $sql .= " AND status = " . intval($status);
        $sql .= " AND finish_time >= '$startTime'";
        $sql .= " AND finish_time <= '$endTime'";
        $sql .= " GROUP BY user_id";

        $allDeposit = $GLOBALS['db']->getAll($sql);

        //客户当日的第一笔存款
        $depositByUser = array();
        foreach ($allDeposit as $deposit) {
            $depositByUser[$deposit['user_id']] = $deposit['amount'];
        }
        echo "当日有首存的用户数量为：" . count($depositByUser) . "\n";

        //统计客户当日总流水（PT除外） 从用户自活动开始日0点累计到当前时间
        $waterByUser = projects::getUsersDayPackages($startTime, $endTime, 0, 0, false);
        $waterByUser = array_spec_key($waterByUser, 'user_id');
        echo "当日有流水的用户数量为：" . count($waterByUser) . "\n";

        //统计客户当日获取礼金
        $prizeByUsers = array();
        foreach ($depositByUser as $uid => $depositAmount) {
            //返礼金规则  例如：有流水的情况下 再判断有充值则计算 奖金
            if(isset($waterByUser[$uid]) && $waterByUser[$uid]['total_amount'] > 0) {
                $tmp = $this->getPrizeScaleByWater($waterByUser[$uid]['total_amount'], $depositAmount);
                //奖金 > 0 写入预备发奖金列表； 排除了 流水不够最小倍数10 奖金0 的情况
                if(is_numeric($tmp['prize']) && $tmp['prize'] > 0) {
                    $prizeByUsers[$uid] =$tmp;
                }
            }
        }
//         foreach ($waterByUser as $uid => $waterRow) {
//             //返礼金规则  例如：60(键)倍流水返 50%(值)礼金
//             $prizeByUsers[$uid] = $this->getPrizeScaleByWater($waterRow['total_amount'], $depositByUser[$uid]);
//         }

        echo "当日发红包的用户数量为：" . count($prizeByUsers) . "\n";
        echo "\n****************************************************************************************\n";

        //发放礼金逻辑
        $this->sendPrizeToUsers($prizeByUsers, $date);
    }
}

?>