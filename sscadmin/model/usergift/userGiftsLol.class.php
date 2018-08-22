<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userGiftsLol extends userGiftsBase implements userGiftsInerface
{
    const STATUS_LOSE = 2;
    const STATUS_WIN = 3;

    /**
     * 活动的类型
     */
    public $gift_type = 'lol';

    /**
     * 活动的中文名称
     */
    public $cnTitle = '英雄联盟';

    private $prize = 1; // 范围内奖品数量
    private $cost = 2; // 每次2块钱
    private $type = 600; // 帐变类型
    private $maxCost = 0; // 最大非中奖基数 // 计算方式 50次中一次就是 50 * cost
    private $winRate = 0;
    private $lockKey = 'user_gift_lol_lock_';
    private $cacheKey = 'hero_poker_pic';
    private $userId = 0;
    private $heroPokerPicDir = '';

    function __construct($settings)
    {
        parent::__construct($settings);
        if (isset($GLOBALS['SESSION']['user_id'])) {
            $this->lockKey .= $GLOBALS['SESSION']['user_id'];
            $this->userId = $GLOBALS['SESSION']['user_id'];
        }

        $this->cost = config::getConfig('lol_cost', 2);
        $this->maxCost = 50 * $this->cost;
        $this->winRate = config::getConfig('win_rate', 0.1);
        $this->winRate > 1 && $this->winRate = 1;
        $this->winRate < 0 && $this->winRate = 0;
        $this->heroPokerPicDir = ROOT_PATH . 'ssc/images_fh/hero/hero_poker_pic/';
    }

    public function createTopButton()
    {
        return '';
    }

    public function createRightFloat()
    {
        return '';
    }

    public function createBanner()
    {
        $cdnUrl = $this->getimgCdnUrl();
        if (isset($GLOBALS['SESSION']['user_id'])) {
            $html = '<a target="_blank" href="?c=user&a=userGifts&giftType=lol" class="lb-img1 fl"><img src="' . $cdnUrl . '/images_fh/hero/pc-banner-lol.png" alt=""></a>';
        } else {
            $html = '<a href="javascript:;" class="goLogin"><img src="' . $cdnUrl . '/images_fh/hero/pc-banner-lol.png" alt=""></a>';
        }

        return $html;
    }

    public function createMobileBanner()
    {
        $cdnUrl = $this->getimgCdnUrl(1);
        $html = '<li><a href="?c=user&a=userGifts&giftType=lol"><img src="' . $cdnUrl . '/images/mobile/mobile-banner-lol.png" alt=""></a></li>';
        $html = ''; // 移动端不要
        return $html;
    }

    /**
     * @param request $request
     * @param view $view
     * @var redisCache $GLOBALS ['redis']
     */
    public function runFrontLogicControll($request, $view)
    {
        if ($this->checkExpireTime() !== 102) {
            $info = date('此活动在m月d号H:i开放!', strtotime($this->promoStartTime));
            $this->reposeAjax(['errCode' => 2, 'errMsg' => $info]);
        }

        if (IS_AJAX) {
            if ($GLOBALS['redis']->get($this->lockKey)) {
                $this->reposeAjax(['errCode' => 299018, 'errMsg' => '系统繁忙，请稍后重试']);
            }
            if (!$GLOBALS['redis']->setex($this->lockKey, 30, 'lock')) {
                $this->reposeAjax(['errCode' => 299019, 'errMsg' => '系统繁忙，请稍后重试']);
            }

            // 检查用户状态
            if (!$user = users::getItem($this->userId, 8)) {
                $this->reposeAjax(['errCode' => 1100, 'errMsg' => '用户不存在或已被禁用']);
            }

            // 判断资金是否足够
            if ($user['balance'] < $this->cost) {
                $this->reposeAjax(['errCode' => 2260, 'errMsg' => '用户余额不足' . $this->cost . '元']);
            }

            // roll 点
            $status = $this->roll();
            // roll到了就抽一个皮肤
            $skin = $status == self::STATUS_WIN ? $this->getSkin() : '';

            if ($skin instanceof Exception) {
                $this->reposeAjax(['errCode' => 1, 'errMsg' => $skin->getMessage()]);
            }
            // $skin = $this->getSkin(); # TEST

            // 加入礼券
            $data = $this->generateData($request->getClientIp(), $status, $skin ? $skin['title'] : '');
            if ($this->addGiftRecord($data) === false) {
                $this->reposeAjax(['errCode' => 299018, 'errMsg' => '系统繁忙，请稍后重试！']);
            }

            // 返回皮肤信息
            $this->reposeAjax([
                'errCode' => 0,
                'errMsg' => '',
                'status' => $status,
                'skin' => $skin,
            ]);
        }

        $view->render('user_gifts_lol');
        exit;
    }

    /**
     * roll 点
     * @return int
     */
    private function roll()
    {
        // 这里将roll的逻辑改简单点,中奖率可配置.
        if ($this->winRate > 0) {
            $winRate = $this->winRate * 10000; // 精确到万分之一
            if (mt_rand(1, 10000) <= $winRate) {
                return self::STATUS_WIN;
            }
        } else {
            # TODO : 这里考虑下是否都只算当天的
            $UserGifts = M('UserGifts');
            // 1.统计当前抽奖次数
            $count = $UserGifts->where([
                'type' => $this->type,
                'user_id' =>$this->userId
            ])->count();

            // 2.统计当前中奖次数
            $countWin = $UserGifts->where([
                'type' => $this->type,
                'user_id' =>$this->userId,
                'status' => self::STATUS_WIN
            ])->count();

            // 3.计算是否有可派奖次数
            $countEnableWin = intval($count * $this->cost / $this->maxCost) + $this->prize;
            $enabledWin = $countEnableWin > $countWin;

            if ($enabledWin) {
                // 4.标准概率中奖或可派奖次数大于2
                if (mt_rand(1, $this->maxCost) <= $this->cost || $countEnableWin - $countWin >= 2) {
                    return self::STATUS_WIN;
                }
            }
        }

        return self::STATUS_LOSE;
    }

    private function getSkin()
    {
        $files = $GLOBALS['redis']->get($this->cacheKey);
        if ($files) {
            $files = json_decode($files, true);
        } else {
            if (!is_dir($this->heroPokerPicDir)) {
                return new exception2('英雄扑克图文件夹未找到');
            }

            if (!is_readable($this->heroPokerPicDir)) {
                return new exception2('英雄扑克图文件夹不可读');
            }

            //获取某目录下所有文件、目录名（不包括子目录下文件、目录名）
            $handler = opendir($this->heroPokerPicDir);
            while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
                if ($filename != '.' && $filename != '..') {

                    // 图片不能使用空格命名
                    $filenameStd = str_replace(' ', '_', $filename);
                    if ($filename != $filenameStd) {
                        // 将空格转为下划线保存
                        rename($this->heroPokerPicDir . $filename, $this->heroPokerPicDir . $filenameStd);
                    }

                    $files[] = [
                        'filename' => $filenameStd,
                        'title' => $filenameStd = str_replace('_', ' ', substr($filename, 0, -4)), // 后缀 .jpg | .png  是固定的4位
                    ];
                }
            }
            closedir($handler);

            $files && $GLOBALS['redis']->setex($this->cacheKey, 7200, json_encode($files, JSON_UNESCAPED_UNICODE));
        }

        $count = count($files);

        if ($count < 1) {
            return new exception2('没有可派奖的皮肤');
        }

        $pos = mt_rand(1, $count) - 1; // 下标0开始,到count - 1

        $cdnUrl = $this->getimgCdnUrl();

        return [
            'id' => $pos, // 这个id就是下标,从0开始.
            'title' => $files[$pos]['title'],
            'url' => $cdnUrl . '/' . $this->matchPath($this->heroPokerPicDir . $files[$pos]['filename'])
        ];
    }

    private function matchPath($path)
    {
        preg_match('@.*(images_fh.*)$@', $path, $macth);
        return isset($macth[1]) ? $macth[1] : '';
    }

    private function addGiftRecord($data)
    {
        if (!$user = users::getItem($this->userId)) {
            // throw new exception2('用户不存在');
            return false;
        }

        M()->startTrans();
        M('UserGifts')->insert($data);

//        $promoData = [
//            'user_id' => $user['user_id'],
//            'top_id' => $user['top_id'],
//            'type' => 6, //'活动红包'
//            'win_lose' => 0,
//            'amount' => 0,
//            'create_time' => date('Y-m-d H:i:s'),
//            'notes' => $data['type'],
//            'status' => 8, //已执行
//            'admin_id' => 0, //自动执行 无admin所以ID = 0
//            'verify_admin_id' => 0,
//            'verify_time' => date('Y-m-d H:i:s'),
//            'finish_admin_id' => 0,
//            'finish_time' => date('Y-m-d H:i:s'),
//            'remark' => '自动执行',
//        ];
//
//        $promoId = M('Promos')->insert($promoData);
//        if ($promoId === false) {
//            M()->rollback();
//            return false;
//        }

        $orderData = [
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => $data['type'],
            'amount' => -abs($this->cost),
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] - abs($this->cost), // 这里是消费,要减
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => 0,
            'admin_id' => $data['admin_id'],
        ];

        $orderId = M('Orders')->insert($orderData);
        if ($orderId === false) {
            M()->rollback();
            return false;
        }

        if (!users::updateBalance($data['user_id'], -abs($this->cost))) {
            M()->rollback();
            return false;
        }

        M()->commit();
        return true;
    }

    /**
     * 生成insert数据
     * @param $clientIp
     * @return array
     */
    private function generateData($clientIp, $status = self::STATUS_LOSE, $remark = '')
    {
        $data = [
            'type' => $this->type,
            'title' => $this->cnTitle,
            'user_id' => $this->userId,
            'gift' => 0, // 这里默认是0,当派送了奖品后,这里记录奖品价值
            'failed_gift' => 0,
            'progress' => 1,
            'status' => $status,
            'from_time' => date('Y-m-d H:i:s'),
            'to_time' => date('Y-m-d H:i:s'),
            'apply_ip' => $clientIp,
            'admin_id' => 0,
            'remark' => $remark, //这里填写中奖的皮肤信息
        ];
        return $data;
    }

    private function reposeAjax($data)
    {
        $GLOBALS['redis']->del($this->lockKey);
        response($data);
    }
}