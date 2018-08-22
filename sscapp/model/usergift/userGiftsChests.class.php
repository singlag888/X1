<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 新年开年红，开吉祥宝箱
 * @author Ray
 */
class userGiftsChests extends userGiftsBase implements userGiftsInerface
{

    /**
     * 最大抽奖次数
     */
    const MAX_LOTTERY_NUM = 8;

    /**
     * 用户抽奖的最小时间间隔
     */
    const MIN_LOTTERY_INTERVAL_TIME = 10;

    /**
     * 存在活动界面
     */
    public $adminPage = false;

    /**
     * 是否显示红包
     */
    public $showRecordOnRedGift = true;

    /**
     * 是否自动发放奖金
     */
    public $is_auto_verify = true;

    /**
     * 是否自动发放奖金
     */
    public $hiddenSuccessGift = true;

    /**
     * 后台操作方法
     * @var type
     */
    private $adminActions = array(
        'promoList' => array('prizeList'),
        'promoStatics' => array('prizeStatics'),
        'promoControl' => array('planPrizeList', 'addPlanPrize', 'deletePlanPrize'),
    );

    /**
     * @todo   参数化的优点在于以后便于模块化设计
     *          转向数据库读写参数做过渡准备
     * @author Davy 2015年12月22日
     * @param  $settings
     * @return
     */
    function __construct($settings)
    {
        parent::__construct($settings);
    }

    /**
     * 头部链接
     * @return type
     */
    public function createTopButton()
    {
        $js = '';
        $html = '<a href="/?&c=user&a=userGifts&giftType=chests" target="_blank" class="topLinkBtn">猴年开宝箱</a>';
        return $html . $js;
    }

    /**
     * Banner
     * @return type
     */
    public function createBanner()
    {
        $js = '';
        $html = '<li style="background: url(images/light_blue/chests/chests_banner.png) no-repeat center top"><a href="/?&c=user&a=userGifts&giftType=chests" target="_blank">猴年开宝箱</a></li>';
        return $html . $js;
    }

    /**
     * @todo   生产右下角浮动窗
     */
    public function createRightFloat()
    {
        $html = '<div id="FloatAD" class="ADBox"><a href="/?&c=user&a=userGifts&giftType=chests" class="show_chestsHD" target="_blank"><img src="../images/light_blue/chests/chests_float.png" width="180" height="185"></a><span onclick="document.getElementById(\'FloatAD\').style.display=\'none\';" class="ADcloser"></span></div>';

        return $html;
    }
    /**
     * 宝箱配置 $level => $config
     * @return type
     */
    static public function getAwardConfig()
    {
        return array(
            1 => array('water' => '388', 'minPrize' => 0.1, 'maxPrize' => 1,       'bigClass' => 'tcB_tt'),
            2 => array('water' => '1588', 'minPrize' => 2, 'maxPrize' => 6,        'bigClass' => 'tcB_tt'),
            3 => array('water' => '3888', 'minPrize' => 10, 'maxPrize' => 18,      'bigClass' => 'tcB_yy'),
            4 => array('water' => '8888', 'minPrize' => 22, 'maxPrize' => 38,      'bigClass' => 'tcB_yy'),
            5 => array('water' => '18888', 'minPrize' => 50, 'maxPrize' => 130,    'bigClass' => 'tcB_gg'),
            6 => array('water' => '38888', 'minPrize' => 150, 'maxPrize' => 280,   'bigClass' => 'tcB_gg'),
            7 => array('water' => '108888', 'minPrize' => 300, 'maxPrize' => 620,  'bigClass' => 'tcB_gg'),
            8 => array('water' => '288888', 'minPrize' => 880, 'maxPrize' => 1680, 'bigClass' => 'tcB_gg'),
        );
    }

    /**
     * 提示语配置
     * @return type
     */
    static public function getTipsConfig()
    {
        // errno为 时，表示成功
        return array(
            201 => array('errno' => '0', 'msg' => '您已成功打开了宝箱， 获得奖金 {prize} 元！'),
            400 => array('errno' => '4000', 'msg' => '用户不存在'),
            401 => array('errno' => '4001', 'msg' => '您需要 {water} 流水才能开此宝箱'),
            402 => array('errno' => '4002', 'msg' => '您今日的宝箱已经全部打开，感谢您的参与。'),
            403 => array('errno' => '4003', 'msg' => '现在不在活动期内！'),
            405 => array('errno' => '4005', 'msg' => '您提交的数据有问题，请检查后再提交！'),
            407 => array('errno' => '4007', 'msg' => '操作频繁，请稍后再试！'),
            500 => array('errno' => '5000', 'msg' => '网络错误！'),
        );
    }

    /**
     * 打开随机的宝箱
     * @param type $id  宝箱等级
     * @return array
     */
    static public function getRandomAward($id)
    {
        $awards = userGiftsChests::getAwardConfig();
        $minPrize = $awards[$id]['minPrize'] * 10;
        $maxPrize = $awards[$id]['maxPrize'] * 10;
        $random = rand($minPrize, $maxPrize) / 10;

        return $random;
    }

    static public function getItemByAwardId($title, $awardId, $userId, $startTime, $endTime)
    {
        $tips = userGiftsChests::getTipsConfig();
        if ($title == '' || $awardId == '' || $userId == '' || $startTime == '' || $endTime == '') {
            throw new exception2($tips[403]['msg'], $tips[403]['errno']);
        }

        $sql = 'SELECT * FROM user_gifts WHERE 1';
        $sql .= " AND title = '$title'";
        $sql .= ' AND progress = ' . $awardId;
        $sql .= ' AND user_id = ' . $userId;
        $sql .= " AND from_time = '$startTime'";
        $sql .= " AND to_time = '$endTime'";
        $sql .= ' LIMIT 1';

        $result = $GLOBALS['db']->getRow($sql);

        return $result;
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
    /**
     * @todo   给用户显示的红包提示语言
     */
    public function redGiftShowMsgToUser($userGift){
        return '请点击“领取”红包';
    }

    /**
     * @todo   在红包里面显示给用户看的红包进度
     */
    public function redGiftShowProgressToUser($userGift)
    {
        return  "100%";
    }

    /**
     * 用户打开宝箱逻辑
     * @param type $userId
     * @param type $id
     */
    private function lottery($userId, $id)
    {
        $awards = userGiftsChests::getAwardConfig();
        $tips = userGiftsChests::getTipsConfig();
        $now = time();
        $fromTime = date('Y-m-d 00:00:00', $now);
        $toTime = date('Y-m-d 23:59:59', $now);

        // 防止同一用户瞬间多次抽奖
        /* if (!userGiftsSlots::preventDDosAttack('slots', $userId)) {
          throw new exception2($tips[407]['msg'], $tips[407]['errno']);
          } */

        if ($this->checkExpireTime() !== 102) {
            throw new exception2($tips[403]['msg'], $tips[403]['errno']);
        }

        // 判断用户是否存在
        if (!$user = users::getItem($userId)) {
            throw new exception2($tips[400]['msg'], $tips[400]['errno']);
        }

        // 判断是否存在该宝箱
        if (!isset($awards[$id])) {
            throw new exception2($tips[405]['msg'], $tips[405]['errno'].'1');
        }

        // 获得用户已经打开的宝箱个数
        $runCount = userGifts::getItemsNumber(date('Y-m-d 00:00:00', $now), date('Y-m-d 23:59:59', $now), $userId, '', '', $this->cnTitle);
        if (!isset($runCount)) {
            logdump('猴年开宝箱：无法获得开启的宝箱个数，用户ID：' . $userId);
            throw new exception2($tips[500]['msg'], $tips[500]['errno']);
        }
        // 判断用户开宝箱的次数
        if ($runCount >= userGiftsChests::MAX_LOTTERY_NUM) {
            throw new exception2($tips[402]['msg'], $tips[402]['errno']);
        }

        // 判断用户是否已经打开该宝箱
        $openGift = userGiftsChests::getItemByAwardId($this->cnTitle, $id, $userId, date('Y-m-d 00:00:00', $now), date('Y-m-d 23:59:59', $now));
        if($openGift){
            throw new exception2($tips[405]['msg'], $tips[405]['errno'].'2');
        }
        // 判断用户的流水是否能够打开该宝箱
        $userWater = userGiftsChests::getCurrDayUserWater($userId);
        if ($awards[$id]['water'] > $userWater['totalWater']) {
            $msg401 = str_replace('{water}', $awards[$id]['water'], $tips[401]['msg']);
            throw new exception2($msg401, $tips[401]['errno']);
        }

        // 获得奖金
        $prize = userGiftsChests::getRandomAward($id);

        $data = array(
            'type' => 0,
            'title' => $this->cnTitle,
            'gift_type' => 'chests',
            'user_id' => $userId,
            'gift' => $prize,
            'progress' => $id,
            'from_time' => $fromTime,
            'to_time' => $toTime,
            'status' => 3,
            //'create_time' => date('Y-m-d H:i:s', $now),
            'remark' => $this->cnTitle,
        );
        if (!userGifts::addItem($data)) {
            logdump('猴年开宝箱：无法新增到userGifts表中，用户ID：' . $userId . '，金额：' . $prize);
            throw new exception2('01', '5030');
        }

        return array(
            'award' => array(
                'bigClass' => $awards[$id]['bigClass'],
            ),
            'prize' => $prize,
            'totalWater' => $userWater['totalWater'],
        );
    }

    /*     * ****************************************** 前后台控制区 ********************************************************** */
    /**
     * 前台页面控制区
     * @param type $requestData
     * @param type $viewObject
     */
    public function runFrontLogicControll($requestData, $viewObject)
    {
        $awardsConfig = userGiftsChests::getAwardConfig();
        $tips = userGiftsChests::getTipsConfig();
        $userId = $GLOBALS['SESSION']['user_id'];
        if (!$user = users::getItem($userId)) {
            $json['errno'] = 1001; //用户无效
            $json['errstr'] = '无效用户';
            die(json_encode($json));
        }

        if ($sa = $requestData->getPost('sa', 'trim', '')) {
            switch ($sa) {
                case 'lottery': // 用户开宝箱
                    try {
                        $id = $requestData->getPost('id', 'trim', '');
                        if (!$result = $this->lottery($userId, $id)) {
                            $result = array('msg' => $tips['500']['msg'], 'errno' => $tips['500']['errno']);
                        }
                    } catch (Exception $e) {
                        $result = array('msg' => $e->getMessage(), 'errno' => $e->getCode());
                    }

                    die(json_encode($result));
                    break;
                default :
                    break;
            }
            exit;
        }
        $now = time();
        $fromTime = date('Y-m-d 00:00:00', $now);
        $toTime = date('Y-m-d 23:59:59', $now);

        // 用户的流水
        // $startTime = '', $endTime = '', $userId = '', $status = '', $type = '', $title = '',
        $userWater = userGiftsChests::getCurrDayUserWater($userId);
        $userChests = userGifts::getItems($fromTime, $toTime, $userId, '', '', $this->cnTitle);
        $openChests = array();
        foreach ($userChests AS $userChest) {
            $openChests[intval($userChest['progress'])] = 1;
        }

        $viewObject->setVar('promoStartTime', $this->promoStartTime);
        $viewObject->setVar('promoEndTime', $this->promoEndTime);
        $viewObject->setVar('awardsConfig', $awardsConfig);
        $viewObject->setVar('userWater', $userWater);
        $viewObject->setVar('openChests', json_encode($openChests));
        $viewObject->setVar('tips', json_encode($tips));
        $viewObject->render('user_gifts_chests');
        exit;
    }

}

?>