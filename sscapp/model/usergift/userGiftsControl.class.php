<?php

/* * ****************************************************
 * FILE      : 用于促销活动框架对象 包括一个接口，一个工厂，一个基类，一个活动包装扩展对象controll
 * @Describe : 1、原则：同一时间范围内，同一类型的活动是唯一的，即同时不能有两个砸蛋活动开展；

  2、注册活动需要 变更 userGiftsControl::registPromo()方法,该方法也相当于getInstance返回活动对象
  //seo 直客活动  2016年1月1日0时至2016年1月30日23时59分59秒
  $seoSettings = array(   //传参初始化对象，同时多个活动时有banner和topButton的sort排序参数
  'promoStartTime' => '2015-12-01 00:00:00',  //活动开始时间  必须传 (所有活动对象都有的参数，见基类)
  'promoEndTime' => '2016-01-30 23:59:59',    //活动结束时间  必须传
  'maxGiftUsers' => 88,                        //下面是该活动对象的特别参数
  'giftPrize' => 18,
  'topid' => 17338,
  );
  //第一个参数是 活动类型
  $objList[] =  salePromoFactory::createPromo('seo', $seoSettings);

  3、系统调用取得活动对象如下： 参数留空则返回所有已注册并且在时间范围内的对象,取不到则返回null
  $allObject = userGiftsControl::registPromo();
  获取单类型活动对象：
  $seoObject = userGiftsControl::registPromo('seo');
  if($seoObject instanceof userGiftsBase) {
  $seoObject->perform($user);
  }

  4、时间有限所以其他应用和需求在做具体活动时进行扩展
  例如：右下角浮动窗是否显示的控制变量等, 假设两个活动同时上 砸蛋/卡牌 都有浮动窗但同时只能有一个则需要控制
  default main 及 welcome 页面的HTML JS 等

  5、user_gifts 表的type定义
     101：签到
     102：注册
     103：首充
     104：日盈利送
     105：日亏损送
  =====================================================================================================

  添加一个新的活动类型步骤：
  一、在ADMIN_PATH . 'model/usergift/ 下添加一个新的对象  例如： userGiftsSeo.class.php
  对象继承于userGiftsBase ,实现接口userGiftsInerface

  二、在userGiftsControl.class.php 里面记录加载这个模块的位置变量
  $GLOBALS['AUTOLOAD_CLASSES']['userGiftsSeo'] =  ADMIN_PATH . 'model/usergift/userGiftsSeo.class.php';

  三、在userGiftsControl.class.php function registPromo 定义该活动，见上文

  四、填充对应方法，实现接口
 * *****************************************************/

//定义活动所有的模块位置 避免每次都要修改index.jsp
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsSeo'] = ADMIN_PATH . 'model/usergift/userGiftsSeo.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsRoulette'] = ADMIN_PATH . 'model/usergift/userGiftsRoulette.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsCard'] = ADMIN_PATH . 'model/usergift/userGiftsCard.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsSign'] = ADMIN_PATH . 'model/usergift/userGiftsSign.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsSimple'] = ADMIN_PATH . 'model/usergift/userGiftsSimple.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsSlots'] = ADMIN_PATH . 'model/usergift/userGiftsSlots.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsChests'] = ADMIN_PATH . 'model/usergift/userGiftsChests.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsRegister'] = ADMIN_PATH . 'model/usergift/userGiftsRegister.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsBet'] = ADMIN_PATH . 'model/usergift/userGiftsBet.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsLol'] = ADMIN_PATH . 'model/usergift/userGiftsLol.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsKingGlory'] = ADMIN_PATH . 'model/usergift/userGiftsKingGlory.class.php';

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 接口设计 规范所有活动统一行为
 * 活动基本接口：包括各种宣传Banner/TopButton； 有效期校验；
 * @author User
 */
interface userGiftsInerface
{
    /**
     * @todo   生产顶部按钮
     */
    function createTopButton();

    /**
     * @todo   生产Banner
     */
    function createBanner();

    /**
     * @todo   生产手机Banner
     */
    function createMobileBanner();

    /**
     * @todo   生产右下角浮动窗
     */
    function createRightFloat();

    /**
     * @todo   生产default_main页面JS
     */
    function createHTMLJSToDefaultMainPage();

    /**
     * @todo   生产default_welcome页面公告HTML及对应JS
     */
    function createHTMLJSToDefaultWelcomePage();

    /**
     * @todo   校验当前时间是否在:活动开始/结束时间内
     */
    function checkExpireTime();

    /**
     * @todo 根据活动类型在红包里面显示给用户看的文言
     */
    function redGiftShowMsgToUser($userGift);

    /**
     * @todo 在红包里面显示给用户看的红包进度
     */
    function redGiftShowProgressToUser($userGift);

    /**
     * @todo 后台cron的运行逻辑
     */
    function cronPerformLogic($cronParam);
}

/**
 * 活动的前端逻辑：显示活动页面/抽奖/判奖等等
 * 前端处理接口
 */
interface userGiftFrontLogic
{
    /**
     * @todo 活动的前端逻辑：显示活动页面/抽奖/判奖等等
     * $requestData Controll对象的$this->request属性
     * $viewObject  Controll对象的self::$view 模板对象
     */
    function runFrontControll($requestData, $viewObject);
}

/**
 * 促销活动的工厂方法
 * @author User
 * 负责读取salePromo的定义表装配生成活动整体
 */
class salePromoFactory
{
    private static $types = array(
        'seo',
        'roulette',
        'card',
        'sign',
        'slots',
        'simple',
        'chests',
        'voucher',
        'register',
        'bet',
        'lol',
        'king_glory',
    );
    private static $setting = array();

    /**
     * 生成活动公共属性
     * roulette | card | brokeEgg | seo | sign
     */
    public static function setPromoTime()
    {
        if (RUN_ENV == 3) {
            // 签到
            self::$setting['sign'] = array(
                'promoPrepareTime' => '2013-04-23 00:00:00',
                'promoStartTime' => '2013-10-09 10:00:00',
                'promoEndTime' => '2013-10-17 20:00:00',
                'cnTitle' => '签到',
            );
            // 注册
            self::$setting['register'] = array(
                'promoPrepareTime' => '2013-04-23 00:00:00',
                'promoStartTime' => '2013-11-18 10:00:00',
                'promoEndTime' => '2013-12-31 20:00:00',
                'cnTitle' => '注册',
            );
            //投注
            self::$setting['bet'] = array(
                'promoPrepareTime' => '2013-04-23 00:00:00',
                'promoStartTime' => '2013-10-09 10:00:00',
                'promoEndTime' => '2013-12-13 23:59:59',
                'cnTitle' => '投注',
            );
            // lol
            self::$setting['lol'] = array(
                'promoPrepareTime' => '2017-08-07 00:00:00',
                'promoStartTime' => '2017-08-07 10:00:00',
                'promoEndTime' => '2030-12-31 23:59:59',
                'cnTitle' => '英雄联盟',
            );
            // king_glory
            self::$setting['king_glory'] = array(
                'promoPrepareTime' => '2017-08-07 00:00:00',
                'promoStartTime' => '2017-08-07 10:00:00',
                'promoEndTime' => '2030-12-31 23:59:59',
                'cnTitle' => '王者荣耀',
            );
        } else {
            // 签到
            self::$setting['sign'] = array(
                'promoPrepareTime' => '2013-04-23 00:00:00',
                'promoStartTime' => '2013-10-09 10:00:00',
                'promoEndTime' => '2013-10-17 20:00:00',
                'cnTitle' => '签到',
            );
            // 注册
            self::$setting['register'] = array(
                'promoPrepareTime' => '2013-04-23 00:00:00',
                'promoStartTime' => '2013-04-18 10:00:00',
                'promoEndTime' => '2013-12-31 20:00:00',
                'cnTitle' => '注册',
            );
            //投注
            self::$setting['bet'] = array(
                'promoPrepareTime' => '2013-04-23 00:00:00',
                'promoStartTime' => '2013-04-09 10:00:00',
                'promoEndTime' => '2013-12-13 23:59:59',
                'cnTitle' => '投注',
            );
            // lol
            self::$setting['lol'] = array(
                'promoPrepareTime' => '2017-08-07 00:00:00',
                'promoStartTime' => '2017-08-07 10:00:00',
                'promoEndTime' => '2030-12-31 23:59:59',
                'cnTitle' => '英雄联盟',
            );
            // king_glory
            self::$setting['king_glory'] = array(
                'promoPrepareTime' => '2017-08-07 00:00:00',
                'promoStartTime' => '2017-08-07 10:00:00',
                'promoEndTime' => '2030-12-31 23:59:59',
                'cnTitle' => '王者荣耀',
            );
        }
    }

    /**
     * 工厂模式生成活动类型对象
     * @param string $giftType 支持的活动类型 roulette | card | brokeEgg | seo | sign
     * @return object
     * @throws exception2
     */
    public static function createPromo($giftType)
    {
        //设置所有活动时间
        self::setPromoTime();
        if (in_array($giftType, self::$types)) {
            $class = 'userGifts' . parse_name($giftType,1);
            return new $class(self::$setting[$giftType]);
        } else {
            throw new exception2('non-supported permutation');
        }
    }

}

/**
 * 活动基类
 */
class userGiftsBase
{

    /**
     * 用于顶部按钮多活动时排序
     */
    public $sortForTopButton = 1;

    /**
     * 用于Banner多活动时排序
     */
    public $sortForBanner = 1;

    /**
     * 用于活动开始时间
     */
    public $promoStartTime = '0000-00-00 00:00:00';

    /**
     * 用于活动结束时间
     */
    public $promoEndTime = '0000-00-00 00:00:00';

    /**
     * 用于活动预发布开始时间
     */
    public $promoPrepareTime = 'NULL';

    /**
     * 活动的英文名称：
     */
    public $enTitle = '';

    /**
     * 活动的中文名称
     */
    public $cnTitle = '';

    /**
     * 该活动是否在红包里显示gift记录
     */
    public $showRecordOnRedGift = false;

    /**
     * 该活动红包是否自动发放
     */
    public $is_auto_verify = false;

    /**
     * 是否自动发放奖金
     */
    public $hiddenSuccessGift = true;

    /**
     * 该活动是否存在后台管理界面
     */
    public $adminPage = false;

    public $imgCdnUrl = '';

    /**
     * @todo   参数化的优点在于以后便于模块化设计
     *          转向数据库读写参数做过渡准备
     * @param  $settings
     * @return
     */
    function __construct($settings)
    {
        if (!empty($settings['cnTitle'])) {
            $this->cnTitle = $settings['cnTitle'];
        }
        if (!empty($settings['promoPrepareTime'])) {
            $this->promoPrepareTime = $settings['promoPrepareTime'];
        }
        if (!empty($settings['promoStartTime'])) {
            $this->promoStartTime = $settings['promoStartTime'];
        }
        if (!empty($settings['promoEndTime'])) {
            $this->promoEndTime = $settings['promoEndTime'];
        }
        if (!empty($settings['sortForBanner'])) {
            $this->sortForBanner = $settings['sortForBanner'];
        }
        if (!empty($settings['sortForTopButton'])) {
            $this->sortForTopButton = $settings['sortForTopButton'];
        }


    }

    //获取CND域名 默认第一个是最快的 $type 0PC 1手机
    protected function getimgCdnUrl($type = 0)
    {
        $imgCdnUrl = '';

        if (file_exists(ROOT_PATH . 'cdn.xml')) {
            $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
            $imgCdnUrl = $type ? (string)$xml->mobile : (string)$xml->web;
        } else {
            $imgCdnUrl = config::getConfig('site_main_domain');
        }

        return $imgCdnUrl;
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
     * @todo   生产手机Banner
     */
    public function createMobileBanner()
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
     * @todo   生产default_main页面JS
     */
    function createHTMLJSToDefaultMainPage()
    {
        return '';
    }

    /**
     * @todo   生产default_welcome页面公告HTML及对应JS
     */
    function createHTMLJSToDefaultWelcomePage()
    {
        return '';
    }

    /**
     * @todo   校验当前时间是否在:活动开始/结束时间内
     */
    public function checkExpireTime()
    {
        //将来预发布处得专门处理101，102情况。 在预发布时101则不能执行活动逻辑智能进行Banner等的展示
        //暂时这样处理，再次梳理时完善此处。 包括cron的逻辑，在最后一天实际上是超出活动时间的
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/s', $this->promoPrepareTime) && strtotime($this->promoPrepareTime) < time() && time() < strtotime($this->promoStartTime)) {
            return 101;
        } elseif (strtotime($this->promoStartTime) <= time() && time() <= strtotime($this->promoEndTime)) {
            return 102;
        }
        return false;
    }

    /**
     * @todo   给用户显示的红包提示语言
     */
    public function redGiftShowMsgToUser($userGift)
    {
        $dec = "从${userGift['from_time']}开始";
        if ($userGift['to_time'] != userGifts::MAX_TIME) {
            $dec .= "到${userGift['to_time']}";
        }
        $dec .= "，";
        if ($userGift['min_total_water'] > 0) {
            $dec .= "总流水达到${userGift['min_total_water']}";
        } else if ($userGift['min_day_water'] > 0) {
            $dec .= "每日流水达到${userGift['min_day_water']}";
        }
        if ($userGift['is_include_child']) {
            $dec .= "(包含你的下级的流水)";
        }
        if ($userGift['type'] == 0) {
            $dec .= "的时候，可获得礼金发放资格";
        } else if ($userGift['type'] == 1) {
            $dec .= "的时候，此礼金解除提现冻结";
        }
        if ($userGift['failed_gift'] != 0 && in_array($userGift['status'], array(1, 2, 8))) {
            $dec .= "，目前已经完成的礼金${userGift['failed_gift']}";
        }
    }

    /**
     * @todo   在红包里面显示给用户看的红包进度
     */
    public function redGiftShowProgressToUser($userGift)
    {
        $progress = 0;
        if ($userGift['min_total_water'] > 0) {
            $progress = number_format($userGift['progress'] / $userGift['min_total_water'] * 100, 0, '.', '');
        } else if ($userGift['min_day_water'] > 0) {
            $progress = 100; //每日流水要求的红包都是发了就完成的
        }
        if ($progress > 100) {
            $progress = 100;
        }
        return $progress . "%";
    }

    /**
     * @todo 活动的前端逻辑：显示活动页面/抽奖/判奖等等
     */
    public function runFrontControll($requestData, $viewObject)
    {
        return '';
    }

    /**
     * @todo   cron 运行逻辑
     * @param  $cronParam
     * @return Array
     */
    function cronPerformLogic($cronParam)
    {

    }

}

/**
 * 活动执行控制类：门面模式 Facede
 */
class userGiftsControl
{
    /*
     * 活动对象列表
     */

    private static $promoObjList = array();

    /**
     * 活动注册
     * @param userGiftsControl $gift_type roulette | card | brokeEgg | seo | sign
     * @return userGiftsControl | array
     *
     * 本方案是为了过渡到模块化设计，以后台控制参数传递而采用的方法；以后重构时只需要修改此方法
     * 不需要改动设计好的各个活动对象
     *
     * 还有一个方案是不需要注册：直接遍历usergift下所有的对象 根据各自的checkExpireTime()方法
     * 自动生成活动对象列表，参数直接定义到对象内部__construction()
     * 这样做的优点是 热插拔，对象往里一扔自动上线下线,不需要修改本方法。发布只需要更新 对应的活动对象
     */
    public static function registPromo($gift_type = 'all')
    {
        if (!empty(self::$promoObjList)) {
            if ($gift_type == 'all') {
                return self::$promoObjList;
            }
            if (isset(self::$promoObjList[$gift_type])) {
                return self::$promoObjList[$gift_type];
            }
        }

        $objList['sign'] = salePromoFactory::createPromo('sign');
        $objList['register'] = salePromoFactory::createPromo('register');
        $objList['bet'] = salePromoFactory::createPromo('bet');
        $objList['lol'] = salePromoFactory::createPromo('lol');
        $objList['king_glory'] = salePromoFactory::createPromo('king_glory');

        //不在当前时间范围内的活动直接注销
        foreach ($objList as $key => $promoObj) {
            if ($promoObj->checkExpireTime() == TRUE) {
                self::$promoObjList[$key] = $promoObj;
            }
        }

        ///////////// 返回  ////////////
        if ($gift_type == 'all') {
            return self::$promoObjList;
        }
        if (isset(self::$promoObjList[$gift_type])) {
            return self::$promoObjList[$gift_type];
        }
        return null;
    }

    /**
     * @todo   生产顶部按钮
     * 可以考虑排序的实现
     */
    static public function createTopButtons()
    {
        $resultCode = '';
        if (empty(self::$promoObjList)) {
            self::registPromo();
        }

        foreach (self::$promoObjList as $promoObject) {
            $resultCode .= $promoObject->createTopButton();
        }
        return $resultCode;
    }

    /**
     * @todo   生产Banners
     * 可以考虑排序的实现
     * 考虑有预热的情况，分别在各自的方法内实现
     * $type 0 pc 1 手机
     */
    static public function createBanners($type = 0)
    {
        $resultCode = '';
        //手动实现预发布：例如在上线之前提前发布Banner是常见现象
        //新春活动一预发布
        // if (strtotime('2016-01-20 00:00:00') < time() && time() < strtotime('2016-02-07 23:59:59')) {
        //     $resultCode .= "<li style='background: url(images/light_blue/firstDeposit.png) no-repeat center top'><a target='_blank' href='index.jsp?c=user&a=userGifts&giftType=simple&option=prevPage'>firstDeposit</a></li>";
        // }

        $function = $type == 0 ? 'createBanner' : 'createMobileBanner';

        if (empty(self::$promoObjList)) {
            self::registPromo();
        }

        foreach (self::$promoObjList as $promoObject) {
            $resultCode .= $promoObject->$function();
        }

        return $resultCode;
    }

    /**
     * @todo   生产default_main页面JS
     */
    static public function createHTMLJSToDefaultMainPage()
    {
        if (empty(self::$promoObjList)) {
            self::registPromo();
        }
        $resultCode = '';
        foreach (self::$promoObjList as $promoObject) {
            $resultCode .= $promoObject->createHTMLJSToDefaultMainPage();
        }
        return $resultCode;
    }

    /**
     * @todo   生产default_welcome页面公告HTML及JS
     */
    static public function createHTMLJSToDefaultWelcomePage()
    {
        if (empty(self::$promoObjList)) {
            self::registPromo();
        }
        $resultCode = '';
        foreach (self::$promoObjList as $promoObject) {
            $resultCode .= $promoObject->createHTMLJSToDefaultWelcomePage();
        }
        return $resultCode;
    }

    /**
     * @todo   生产RightFloats
     * 同一时期只有一个唯一的右浮动
     * 在各自的 createRightFloat() 方法里面控制，所以遍历所有的了，不产生浮窗方法直接返回空，没有效率问题
     */
    static public function createRightFloat()
    {
        if (empty(self::$promoObjList)) {
            self::registPromo();
        }
        $resultCode = '';
        foreach (self::$promoObjList as $promoObject) {
            $resultCode .= $promoObject->createRightFloat();
        }
        return $resultCode;
    }

    /**
     * 有cron运算的执行运算逻辑
     * @param  $cronParam  cron中传递的参数 Array
     */
    static public function cronPerformLogic($cronParam)
    {
        //活动cron控制逻辑
        if (empty(self::$promoObjList)) {
            self::registPromo();
        }
        try {
            foreach (self::$promoObjList as $promoObject) {
                if ($promoObject instanceof userGiftsBase) {
                    $promoObject->cronPerformLogic($cronParam);
                }
            }
        } catch (exception2 $e) {
            echo '活动cron错误：' . $promoObject->cnTitle . $e->getMessage();
        }
        return true;
    }

    /**
     * @todo   活动页面：例如 砸蛋，签到，抽奖转盘等
     * 形式有两种 1、没有新页面  直接JS弹出层； 2、弹出新页面显示活动
     *
     */
    static public function showGiftPage()
    {

    }

    /**
     *
     */
    static public function createBackendPage($action, $requestData, $viewObject)
    {
        // 获得活动类型
        $giftType = $requestData->getGet('giftType', 'trim', 'slots');
        $promoObject = self::registPromo($giftType);
        if ($promoObject instanceof userGiftsBase) {
            // 进入具体红包类中，进行后台页面配置
            if ($promoObject->adminPage == true) {
                $promoObject->runBackendLogicControll($action, $requestData, $viewObject, self::$promoObjList);
            }
        }
    }

}

?>