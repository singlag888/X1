<?php

/**
 * 基本控制器类
 * 95%的情况下需要模板，因此默认加载模板
 * 是否需要SESSION服务等在应用指定
 */
class sscController extends controller
{

    const INIT_TEMPLATE = 1;
    const INIT_SESSION = 2;

    //const INIT_USERLOGIN_FALSE = 4;
    //const INIT_USERLOGIN_TRUE  = 8;
    //static protected $role = NULL; //这个暂时没用到
    // 不用进行检查的方法
    static protected $dutyFree = array(
        //'test'=>['*'],
        'default' => array(
            //'*',  //代表所有方法均不需检查
            'index',
            'welcome',
            'forgetPwd',
            'login',
            'logout',
            'verifyCode',
            'viewNotice',
            'register',
            'marketReg',
            'autoReg',
            'qrRedirect',
            'savePage',
            'openInfo',
            'domainInfo',
            'legalVisit',
        ),
        'fin' => array(
            'receiveRXWXPayResult',
            'receiveRXZFBPayResult',
            'receiveBFZFBPayResult',
            'receiveBFWXPayResult',
            'receiveFQWXPayResult',
            'receiveFQWYPayResult',
            'receiveSBZFBPayResult',
            'receiveSBWXPayResult',
            'receiveXYPayResult',
            'receiveFQQQPayResult',
            'receiveYBZFBPayResult',
            'receiveYBWXPayResult',
            'receiveHFZFBPayResult',
            'receiveHFWXPayResult',
            'receiveUFZFBPayResult',
            'receiveUFWXPayResult',
            'receiveXMPayResult',
            'receiveSFWXPayResult',
            'receiveSFZFBPayResult',
            'receiveSFQQPayResult',
            'receiveGTZFBPayResult',
            'receiveGTWXPayResult',
            'receiveLBWXPayResult',
            'receiveLBWYPayResult',
            'receivePayResult',
        ),
        'client' => array(
            '*'
        ),
        'fake' => array(
            '*'
        ),
        'service' => array(
            'checkStatus',
        ),
        'game' => array(
            'lobby',
        ),
        'help' => array(
            'download',
            'chart',
            'platformact',
            'latestnew',
        ),
        'newPay' => array(//TODO: it's Permissions place
            'backPay',
        ),
        'test' => array(
            'backPay',
        ),
        'pay' => array(
            'renxinBack',
            'shunbaoBack',
            'shunfuBack',
            'xiongmaoBack',
            'gaotongBack',
            'luoboBack',
            'jinfukaBack',
            'zhifuPay',
            'zhifuBack',
            'duodebaoPay',
            'duodebaoBack',
            'qicaiBack',
            'yifuBack',
            'yifutongdaoBack',
            'kaolaBack',
            'wangfutongBack',
            'wanzhongyunBack',
            'yftBack',
            'huibaotongBack',
            'xiangjiaoBack',
            'yibaozhifuBack',
            'yunanfuPay',
            'yunanfuBack',
            'haiouBack',
            'sankBack',
            'qingyifuBack',
            'ludeBack',
            'zhitongbaoPay',
            'zhitongbaoBack',
            'yuyingBack',
            'weifutongBack',
            'yzBack',
            'dingfengBack',
            'ufuBack',
            'dingyiBack',
            'qianbaobaoPay',
            'qianbaobaoBack',
            'yiaiPay',
            'yiaiBack',
            'zshBack',
            'zaixianbaoBack',
            'zaixianbaoBack',
            'jieanfuPay',
            'jieanfuBack',
            'xdfBack',
            'ebaoBack',
            'xidakejiBack',
            'zaixianbaoBack',
            'tianfubaoPay',
            'tianfubaoBack',
            'mgBack',
            'mgPay',
            'yunxunPay',
            'yunxunBack',
            'caifubaoBack',
            'changchengfuBack',
            'yibaotongPay',
            'yibaotongBack',
            'jinyinbaoPay',
            'jinyinbaoBack',
            'huitongPay',
            'huitongBack',
            'jialianBack',
            'caimaoPay',
            'caimaoBack',
            'xinfubaoPay',
            'xinfubaoBack',
            'yuanbaoPay',
            'yuanbaoBack',
            'boshiPay',
            'boshiBack',
            'ydfPay',
            'ydfBack',
            'yhxfBack',
            'yhxfPay',
            'xbswPay',
            'xbswBack',
            'yuebaoPay',
            'yuebaoBack',
            'zhifuhuiPay',
            'zhifuhuiBack',
            'shiguangfuPay',
            'shiguangfuBack',
            'ouchuangPay',
            'ouchuangBack',
            'lwPay',
            'lwBack',
            'lzfPay',
            'lzfBack',
            'mifuBack',
            'alaPay',
            'alaBack',
            'zunPay',
            'zunBack',
            'xunPay',
            'xunBack',
            'qbjhPay',
            'qbjhBack',
            'bjhfPay',
            'bjhfBack',
            'wzhPay',
            'wzhBack',
            'ruiyinPay',
            'ruiyinBack',
            'zf32Pay',
            'zf32Back',
            'caihonPay',
            'caihonBack',
            'qianenPay',
            'qianenBack',
            'baishiPay',
            'baishiBack',
            'yitongPay',
            'yitongBack',
            'huihePay',
            'huiheBack',
            'tongsaoPay',
            'tongsaoBack',
            'qiqiPay',
            'qiqiBack',
            'lyBack',
            'jinyangPay',
            'jinyangBack',
            'F86Pay',
            'F86Back',
            'sftPay',
            'sftBack',
            'ddBack',
            'tsBack',
            'zhidebaoPay',
            'zhidebaoBack',
            'qianlongtongPay',
            'qianlongtongBack',
            'quanyuPay',
            'quanyuBack',
            'jlBack',
            'kexunPay',
            'kexunBack',
            'yafuPay',
            'yafuBack',
            'fukatongPay',
            'fukatongBack',
            'hufuPay',
            'hufuBack',
            'safePay',
            'safeBack',
        ));

    static protected $attactMenu = array(
        'default' => array(
            'forgetPwd',
            'login',
            'logout',
        ),
        'fake' => array(
            '*'
        ),
    );

    static protected $cacheFiles = array(
        'default' => array(
            'login' => array(
                'tpl' => 'login',//模板名
                'domainPerfix' => true,//开启域名前缀
                'way' => 'post',//获取参数方法,可空
                'paramName' => 'verify',//合法请求参数，合法参数的请求不走静态缓存，可空
                'paramType' => 'trim',
                'paramData' => 'login',//具体参数，可空
            ),
        ),
        'fake' => array(
            'platformact' => array(
                'tpl' => 'platformact',//模板名
                'domainPerfix' => false,//同个cache文件不同域名前缀，可达到更好效果
                'way' => 'post',//获取参数方法,可空
                'paramName' => 'verify',//合法请求参数，合法参数的请求不走静态缓存，可空
                'paramType' => 'trim',
                'paramData' => 'login',//具体参数，可空
            ),
        ),
    );

    static protected $kindImg = array('banner_img', 'thumb_img', 'main_img', 'm_banner_img', 'm_thumb_img', 'm_main_img');
    static public $PublicImgCdn = '';

    /**
     * 实现权限验证，
     * 能从某个存储中找到user_id对应的权限
     */
    public function validate($controller = CONTROLLER, $action = ACTION)
    {
        //判断模板 先cookie然后域名
        if (isset($_COOKIE['templateDirectoryCookie1']) && $_COOKIE['templateDirectoryCookie1'] == '1') {
            $GLOBALS['templateDirectory'] = 'default';
        } elseif (isset($_COOKIE['templateDirectoryCookie1']) && $_COOKIE['templateDirectoryCookie1'] == '2') {
            $GLOBALS['templateDirectory'] = 'newssc';
        } else {
            //默认模板
            $GLOBALS['templateDirectory'] = 'newssc';
        }

        //攻击页面提前return
        if (!empty(self::$attactMenu[$controller])) {
            if (in_array($action, self::$attactMenu[$controller]) || in_array('*', self::$attactMenu[$controller])) {
                return true;
            }
        }
        //默认都加载session
        start_sessions(300);

        //未登录免检产品
        if (!empty(self::$dutyFree[$controller])) {
            if (in_array($action, self::$dutyFree[$controller]) || in_array('*', self::$dutyFree[$controller])) {
                return true;
            }
        }

        //初步判断，没有登录的就跳至登录界面
        if (empty($GLOBALS['SESSION']['user_id'])) {//这里判断是login的话，没必要往下继续
            redirect(url("default", "login"), 0, TRUE);
        }

        //判断所在组权限，该control&action是否有权限。。。
        $menu = array();
        if (!userGroups::verifyPriv(array($controller, $action), 0, $menu)) {
            //throw new exception2('Access forbidden');
            userLogs::addLog(0, '访问失败：无权限');
            showMsg('您没有足够的权限访问,只对特殊会员开放!');
        }

        //检查是否被踢出
        $needCheckOne = config::getConfig('limit_one_ip_online');
        $newSessInfo = [];
        if ($needCheckOne && $GLOBALS['SESSION']->isEdgeOut($GLOBALS['SESSION']['user_id'], 0, $newSessInfo)) {
            showAlert("您已经从别处登录（IP：{$newSessInfo['client_ip']}），如果这不是您本人亲自操作，为保证安全请立即修改密码！", url(NULL, 'logout'));
        }

        if (!$menu) {
            showMsg('出错了！访问的菜单不存在。');
        }

        //记录日志
        if ($menu['is_log'] == 1) {
            userLogs::addLog(1, '访问成功');
        }

        return true;
    }

    public function isLogined()
    {
        return isset($GLOBALS['SESSION']['user_id']) && $GLOBALS['SESSION']['user_id'] > 0;
    }

    public function init($init = 0)
    {
        if ($init > 0) {
            if (($init & self::INIT_TEMPLATE) === self::INIT_TEMPLATE) {
                if (self::$view === NULL) {

                    self::$view = new view($GLOBALS['templateDirectory']);
                    //设置默认标题
                    if (!isset($this->titles[ACTION])) {
                        throw new exception2(ACTION . '的标题未设置');
                    }
                    self::$view->setVar('curTitle', $this->titles[ACTION]);
                    if ($this->request->getGet('compress', 'intval', 0)) {
                        //self::$view->setEchoPolicy(Template::HTML_COMPRESSOR, false);
                    }
                }
            }
        }
        //生成版本号
        self::$view->setVar('html_version', HTML_VERSION_NUM);

        //如果访问的是攻击页面，则参与告警阈值判断
        if (!empty(self::$attactMenu[CONTROLLER])) {
            if (in_array(ACTION, self::$attactMenu[CONTROLLER]) || in_array('*', self::$attactMenu[CONTROLLER])) {
                if (!file_exists(ROOT_PATH . 'waring.txt')) {
                    die('no waring file');
                }

                $num = file_get_contents(ROOT_PATH . 'waring.txt');
                if ($num > PC_ATTACT_NUM) {
                    if ($this->request->getCookie('legalVisit_' . session_id(), 'trim')) {
                        $this->getimgCdnUrl();//因为启用阈值就走不到CDN了,所以这里要加一下
                        return true;
                    } else {
                        self::$view->render('default_legalvisit');
                        exit;
                    }
                }
            }
        }

        self::$view->cacheOutPut(self::$cacheFiles, CONTROLLER, ACTION, $this->request);

        //设置一些登录后的常用显示数据
        if ($this->isLogined()) {
            $this->setCommonData();
        }

        $this->getimgCdnUrl();

        return true;
    }

    public function setCommonData()
    {
        if (self::$view) {
            //默认风格
            self::$view->setVar('styleDir', $this->request->getCookie('styleDir', 'trim', 'blue|dark'));
            self::$view->setVar('user_id', $GLOBALS['SESSION']['user_id']);
            self::$view->setVar('username', $GLOBALS['SESSION']['username']);
            self::$view->setVar('last_ip', $GLOBALS['SESSION']['last_ip']);
            self::$view->setVar('last_time', $GLOBALS['SESSION']['last_time']);
            self::$view->setVar('html_version_num', HTML_VERSION_NUM);
        }
    }

    /**
     * 日期查找范围
     * @param $start_time
     * @param $end_time
     * @param $number
     * @param $type 1=>day, 2=>week, 3=>month
     * @param $delay_day 延迟天数
     */
    protected function searchDate(&$start_time, &$end_time, $number = 1, $type = 2, $delay_day = 0)
    {
        $type = $type == 1 ? ' days' : ($type == 2 ? ' weeks' : ' months');

        $curTime = date('Y-m-d', strtotime('-' . $delay_day . ' days'));
        $minDate = date('Y-m-d', strtotime('-' . $number . $type . '+1 days -' . $delay_day . ' days'));

        if ($endDate = $this->request->getGet('endDate', 'trim', $curTime)) {
            if ($endDate > $curTime) $endDate = $curTime;
            elseif ($endDate < $minDate) $endDate = $minDate;
        }

        if ($startDate = $this->request->getGet('startDate', 'trim', $curTime)) {
            if ($startDate > $endDate) $startDate = $endDate;
            elseif ($startDate < $minDate) $startDate = $minDate;
            elseif ($startDate > $curTime) $startDate = $curTime;
        }

        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);

        $start_time = $startDate . ' 00:00:00';
        $end_time = $endDate . ' 23:59:59';
    }

    //获取CND域名 默认第一个是最快的
    protected function getimgCdnUrl()
    {

        $imgCdnUrl = '';

        if (file_exists(ROOT_PATH . 'cdn.xml')) {
            $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
            $imgCdnUrl = (string)$xml->web;
            self::$PublicImgCdn = (string)$xml->public_pay;
        } else {
            $imgCdnUrl = config::getConfig('site_main_domain');
            self::$PublicImgCdn = config::getConfig('site_main_domain');
        }

        self::$view->setVar('imgCdnUrl', $imgCdnUrl);
    }

    protected function getUser()
    {
        if ($GLOBALS['SESSION']['user_id']) {
            $user = users::getItem($GLOBALS['SESSION']['user_id']);
            self::$view->setVar('user', $user);
            // redirect(url("default", "login"), 2, TRUE);
        }
    }

    protected function getNotics()
    {
        $notices = notices::getItems(1, 0, 1, 0, 15);
        self::$view->setVar('notices', $notices);
    }

    protected function getNotReadMsgNum()
    {
        //得到未读信息
        $noReadMsg = messages::getReceivesNumber($GLOBALS['SESSION']['user_id'], '0');
        $noReadMsg = $noReadMsg > 99 ? 99 : $noReadMsg;

        self::$view->setVar('noReadMsg', $noReadMsg);
    }

    protected function getActivities()
    {
        $activities = activity::getItems();
        foreach ($activities as $k => &$activity) {
            if (strtotime($activity['end_time']) < time()) {
                unset($activities[$k]);
                continue;
            }
            foreach ($activity as $k => $v) {
                if (in_array($k, self::$kindImg)) {
                    preg_match('@.*(images_fh.*)$@', $v, $macth);
                    if (isset($macth[1])) {
                        $activity[$k] = $macth[1];
                    } else {
                        $activity[$k] = '';
                    }
                }
            }
        }
        unset($activity);
        self::$view->setVar('activities', $activities);
    }
    /**
     * author snow
     * 切换redis 库
     * @param Closure $closure
     * @return mixed
     */
    public function cutRedisDatabase(Closure $closure,$db_index=REDIS_DB_DEFAULT)
    {
        $GLOBALS['redis']->select($db_index);//>>切换到app库
        $result = $closure();//>>执行程序
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);//>>切换回默认库
        return $result;
    }
}
