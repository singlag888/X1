<?php

/**
 * 基本控制器类
 * 95%的情况下需要模板，因此默认加载模板
 * 是否需要SESSION服务等在应用指定
 */
class sscAdminController extends controller
{

    const INIT_TEMPLATE = 1;
    const INIT_SESSION = 2;

    //const INIT_USERLOGIN_FALSE = 4;
    //const INIT_USERLOGIN_TRUE  = 8;
    //static protected $role = NULL; //这个暂时没用到
    // 不用进行检查的方法
    static protected $dutyFree = array(
        'default' => array(
            //'*',  //代表所有方法均不需检查
            'index',
            'login',
            'logout',
            'verifyCode',
            'top',
            'welcome',
            'usermenu',
            'main',
            'excel',
        ),
        //客户端收款接口 仅一个save_deposit()
        'autoDeposit' => array(
            '*',
        ),
        //客户端提款接口 get_withdraw() save_withdraw()
        'autoPay' => array(
            '*',
        ),
    );

    /**
     * 实现权限验证，
     * 能从某个存储中找到user_id对应的权限
     */
    public function validate($controller = CONTROLLER, $action = ACTION)
    {

        //未登录免检产品
        if (!empty(self::$dutyFree[$controller])) {
            if (in_array($action, self::$dutyFree[$controller]) || in_array('*', self::$dutyFree[$controller])) {
                return true;
            }
        }

        start_sessions(1440);

        //初步判断，没有登录的就跳至登录界面
        if (empty($GLOBALS['SESSION']['admin_id'])) {
            redirect(url('default', 'login'), 0, TRUE);
        }
        //登录后免检产品 不再需要，这里加这些代码不优美，后台加几个权限搞定
//        else {
//            if ($controller == 'default' && in_array($action, array('main', 'usermenu', 'top', 'welcome'))) {
//                return true;
//            }
//        }
        //判断所在组权限，该control&action是否有权限。。。
        $menu = array();
        if (!adminGroups::verifyPriv(array($controller, $action), 0, $menu)) {
            //throw new exception2('Access forbidden');
            adminLogs::addLog(0, '访问失败：无权限');

            // 某些ajax操作报无权限直接给了个页面,这样肯定是看不到下效果的.
            // 于是添加针对ajax的响应
            if (IS_AJAX) {
                response([
                    'errCode' => 1,
                    'errMsg' => '出错了！您没有足够的权限访问。',
                    'status' => 0,
                    'info' => '出错了！您没有足够的权限访问。'
                ]);
            } else {
                showMsg('出错了！您没有足够的权限访问');
            }
        }

        //检查是否被踢出
        $newSessInfo = array();
        if ($GLOBALS['SESSION']->isEdgeOut($GLOBALS['SESSION']['admin_id'], 1, $newSessInfo)) {
            showAlert("您已经在别处登录（IP：{$newSessInfo['client_ip']}），如果这不是您本人亲自操作，请向客服反映！", url(NULL, 'logout'));
        }
        if (!$menu) {
            showMsg('出错了！访问的菜单不存在');
        }

        //记录日志
        if ($menu['is_log'] == 1) {
            adminLogs::addLog(1, '访问成功');
        }

        return true;
    }

    public function isLogined()
    {
        return isset($GLOBALS['SESSION']['admin_id']) && $GLOBALS['SESSION']['admin_id'] > 0;
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

        //前面已经start_sessions()了，但这里仍需要做，因为免检产品提前返回了导致session还没初始化
        if (($init & self::INIT_SESSION) === self::INIT_SESSION) {
            start_sessions(3000);
        }

        //设置一些登录后的常用显示数据
        if ($this->isLogined()) {
            $this->setCommonData();
        }

        return true;
    }

    public function setCommonData()
    {
        if (self::$view) {
            self::$view->setVar('admin_id', $GLOBALS['SESSION']['admin_id']);
            self::$view->setVar('admin_name', $GLOBALS['SESSION']['admin_username']);
            self::$view->setVar('admin_last_ip', $GLOBALS['SESSION']['admin_last_ip']);
            self::$view->setVar('admin_last_time', $GLOBALS['SESSION']['admin_last_time']);
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

}
