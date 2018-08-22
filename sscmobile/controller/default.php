<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

/**
 * 控制器：商户管理后台
 * 承接管理员登录等基本后台业务
 */
class defaultController extends sscController
{

//方法概览
    public $titles = array(
        'index' => '默认首页',
        'main' => '主框架',
        'top' => '顶部内容',
        'usermenu' => '用户菜单',
        'welcome' => '欢迎页',
        'viewNotice' => '查看公告内容',
        'forgetPwd' => '忘记密码',
        'saveGuestBook' => '处理留言',
        'login' => '用户登录',
        'login2' => '用户登录2',
        'logout' => '退出登录',
        'verifyCode' => '验证码',
        'register' => '注册页面',
        'marketReg' => '推广注册',
        'autoReg' => '手机端自动注册游客',
        'download' => '客户端下载',
        'qrRedirect' => '客户端下载',
        'savePage' => '保存登录页',
        'openInfo' => '开奖信息',
        'domainInfo' => '域名信息',
        'legalVisit' => '防攻击点击验证',

    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function legalVisit()
    {
        $visit = $this->request->getPost('visit', 'intval', 0);
        if ($visit) {
            setcookie('legalVisit_' . session_id(), 1, time() + 86400);
            response(array('errno' => 0, 'errstr' => url('default', 'login')));
        }
    }

    public function savePage()
    {
        $host = $_SERVER['HTTP_HOST'];
        $Shortcut = "[InternetShortcut]
		URL=http://{$host}
		IconFile=http://{$host}/favicon.ico
		IconIndex=0
		HotKey=1613
		IDList=
		[{000214A0-0000-0000-C000-000000000046}]
		Prop3=19,2";
        header("Content-Type: application/octet-stream");
        header('Content-Disposition: attachment; filename=' . config::getConfig('site_title') . $host . '.url');
        echo $Shortcut;
    }

// 默认方法
    public function index()
    {
        if (self::isLogined()) {
            redirect(url("default", "welcome"), 2, TRUE);
        } else {
            redirect(url("default", "login"), 2, TRUE);
        }
    }

    public function main()
    {
        if (!self::isLogined()) {
            redirect(url("default", "login"), 2, TRUE);
        }

        if (!$group = userGroups::getItem($GLOBALS['SESSION']['group_id'])) {
            redirect(url("default", "login"), 2, TRUE);
        }
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            redirect(url("default", "login"), 2, TRUE);
        }
        //新手指导
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $GLOBALS['SESSION']['last_time'] = date('Y-m-d H:i:s');
            exit('1');
        }

        self::$view->setVar('user', $user);
        self::$view->render('default_main');
    }


    public function welcome()
    {

        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            redirect(url("default", "login"), 2, TRUE);
        }
        //得到网站标题
        self::$view->setVar('siteTitle', config::getConfig('site_title'));
        $prizeList = array();

        $prizeList = projects::getMaxPrizeList(date('Y-m-d H:i:s', strtotime('-2 days')), date('Y-m-d H:i:s'), 0, 20);
        self::$view->setVar('prizeList', $prizeList);
        self::$view->setVar('user', $user);
        //得到公告
        $notices = notices::getItems(1, 0, 4, 0, 15);
        self::$view->setVar('notices', $notices);

        /* 已登录状态下默认弹窗 开始 */
        $userAlert = (new userAlert)->getUserAlert($user['top_id']);

        if ($userAlert && $userAlert['type'] == userAlert::TYPE_IMAGE) {
            $userAlert['main_img'] = $this->matchPath($userAlert['main_img']);
            $userAlert['m_main_img'] = $this->matchPath($userAlert['m_main_img']);
        }

        self::$view->setVar('userAlert', $userAlert);

        /* 已登录状态下默认弹窗 结束 */

        /* ===============所有活动banner输出================ */
        $gift_banner = userGiftsControl::createBanners(1);
        self::$view->setVar('gift_banner', $gift_banner);
        /* ===============End 所有活动公共输出================ */
        $this->getNotReadMsgNum();
        $this->getActivities();
        self::$view->render('default_welcome');
    }

    private function matchPath($path)
    {
        preg_match('@.*(images_fh.*)$@', $path, $macth);
        return isset($macth[1]) ? $macth[1] : '';
    }

    public function viewNotice()
    {
        $nid = $this->request->getPost('nid', 'intval');
        if (!$notice = notices::getItem($nid, 0)) {
            die(json_encode(array('errno' => 1, 'errstr' => '找不到该公告')));
        }
        $result = array('errno' => 0, 'title' => $notice['title'], 'content' => $notice['content']);
        echo json_encode($result);
    }
    /*
        public function forgetPwd() {
            $verify = $this->request->getPost('verify', 'trim');
            switch ($verify) {
            case 'reset':
                $password = $this->request->getPost('password', 'trim');
                $password2 = $this->request->getPost('password2', 'trim');
                $nvpair = $this->request->getPost('nvpair', 'trim');
                if ($nvpair != $GLOBALS['SESSION']['nvpair']) {
                    showMsg('您未通过认证，修改密码失败');
                }

                if ($password != $password2) {
                    showMsg('两次输入的密码不一致');
                }

                if (!users::resetLoginPwd($GLOBALS['SESSION']['username'], $password)) {
                    showMsg("重置失败");
                }

    //清除session
                $GLOBALS['SESSION']->destroy();
                $GLOBALS['SESSION'] = array();
                showMsg("重置成功，您的新密码是{$password}，请牢记", 1);
                break;
            case 'email': //根据安全邮箱 发送重置后的随机密码明文至邮箱，
                showMsg("发送成功，请登录邮箱查收", 1);
                break;
            }
            self::$view->render("default_forgetpwd");
        }*/

    /**
     *
     */
    public function forgetPwd()
    {
        if (!isset($_SESSION['reset_username'])) {
            if ($username = strtolower($this->request->getPost('username', 'trim'))) {
                $safePwd = $this->request->getPost('safe_pwd', 'trim');
                $verifyCode = strtolower($this->request->getPost('verifyCode', 'trim'));

                if ((!$user = users::getItem($username))) {
                    showMsg('无效用户');
                }
                if (!(new captcha())->verifying($verifyCode)) {
                    showMsg('验证码错误');
                }
//				users::checkSafePwd($user['safe_pwd'], $safePwd, $user['user_id'], true);

                $_SESSION['reset_username'] = $username;
            }
        } else {
            if ($pwd = $this->request->getPost('pwd', 'trim')) {
                $confirm_pwd = $this->request->getPost('confirm_pwd', 'trim');
                if (!$pwd || strlen($pwd) < 6 || strlen($pwd) > 15 || preg_match('`^\d+$`', $pwd) || preg_match('`^[a-zA-Z]+$`', $pwd)) {
                    showMsg('密码长度为6-15字符，不能为纯数字或纯字母');
                }
                if ($pwd != $confirm_pwd) {
                    showMsg('两次输入的密码不相同');
                }
                $user = users::getItem($_SESSION['reset_username']);

                if ($user['pwd'] == password_hash($pwd, PASSWORD_DEFAULT)) {
                    showMsg('不能与原密码相同');
                }

                $enPwd = generateEnPwd($pwd);
                if ($enPwd == $user['safe_pwd'] || $enPwd == $user['secpwd']) {
                    showMsg("不能与资金密码或安全码相同");
                }

                if (!users::resetLoginPwd($_SESSION['reset_username'], $pwd)) {
                    showMsg("重置失败");
                }
                session_destroy();

                $links = array(0 => array('title' => '跳转登录页', 'url' => "location.href='" . url('default', 'login') . "'"));
                showMsg("重置成功", 0, $links, 'self', true);
            }
        }
        $username = isset($_SESSION['reset_username']) ? $_SESSION['reset_username'] : '';
        self::$view->setVar('username', $username);
        self::$view->render("default_forgetpwd");
    }

    public function login()
    {
        //得到公告
        $notices = notices::getItems(1, 0, 4, 0, 15);
        self::$view->setVar('notices', $notices);

        /* ===============所有活动banner输出================ */
        $gift_banner = userGiftsControl::createBanners(1);
        self::$view->setVar('gift_banner', $gift_banner);
        /* ===============End 所有活动公共输出================ */

        /* 未登录状态下默认弹窗 开始 */
        $userAlert = (new userAlert)->getUserAlert();

        if ($userAlert && $userAlert['type'] == userAlert::TYPE_IMAGE) {
            $userAlert['main_img'] = $this->matchPath($userAlert['main_img']);
            $userAlert['m_main_img'] = $this->matchPath($userAlert['m_main_img']);
        }

        self::$view->setVar('userAlert', $userAlert);
        /* 未登录状态下默认弹窗 结束 */

        $this->getActivities();
        self::$view->render("default_login", true, true);
    }

    public function login2()
    {
        $verify = $this->request->getPost('verify', 'trim');
        $username = $this->request->getPost('username', 'trim');
        if (!$verify) {
            //判断是否推广域名

            self::$view->render("default_login2", true);
            exit;
        }
        switch ($verify) {
            case 'login':
                $verifyCode = $this->request->getPost('verifyCode', 'trim');
                $username = strtolower($this->request->getPost('username', 'trim'));
                $encpassword = $this->request->getPost('encpassword', 'trim');
                $frm = $this->request->getPost('frm', 'intval', '1'); //1网页 2客户端 3WAP 4安卓APP 5苹果APP
                //条款选中按钮状态 0-没弹出时的值，1-弹出没选的值，2-弹出选中的值
                $first_flag = $this->request->getPost('flag', 'intval');

                if (!(new captcha())->verifying($verifyCode)) {
                    die(json_encode(array('errno' => 9900, 'errstr' => '验证码错误')));
                }
                // if ($verifyCode !== $GLOBALS['SESSION']['verifyCode']) {
                // 	die(json_encode(array('errno' => 9900, 'errstr' => '验证码错误')));
                // }
                if (!$username || !$encpassword || !preg_match('`^[a-z]\w{5,11}$`', $username)) {
                    userLogs::addLog(0, '登录失败：用户名或密码不正确');
                    die(json_encode(array('errno' => 2, 'errstr' => '用户名或密码不正确')));
                }

                // 普通登陆
                $result = users::login($username, $encpassword, '', $first_flag, $frm);

                if (!is_array($result)) {
                    if ($result == '必须同意协议') {
                        die(json_encode(array('errno' => 4, 'errstr' => $result)));
                    } else {
                        die(json_encode(array('errno' => 3, 'errstr' => $result)));
                    }
                }
                start_sessions(300);
                //登录信息写入session
                $GLOBALS['SESSION']['user_id'] = $result['user_id'];
                $GLOBALS['SESSION']['username'] = $result['username'];
                $GLOBALS['SESSION']['nick_name'] = $result['nick_name'];
                $GLOBALS['SESSION']['is_test'] = $result['is_test'];
                $GLOBALS['SESSION']['level'] = $result['level'];
                $GLOBALS['SESSION']['parent_id'] = $result['parent_id'];
                $GLOBALS['SESSION']['top_id'] = $result['top_id'];
                $GLOBALS['SESSION']['group_id'] = $result['group_id'];
                $GLOBALS['SESSION']['last_ip'] = $result['last_ip'];
                $GLOBALS['SESSION']['last_time'] = $result['last_time'];
                $GLOBALS['SESSION']['balance'] = $result['balance'];

                //登录成功后记录其登录来源
                $GLOBALS['SESSION']['frm'] = $frm;
                if ($result['last_ip'] != $GLOBALS['REQUEST']['client_ip'] && $result['last_ip'] != '0.0.0.0') {
                    // 最后一次登陆IP和本次不同
                    $links = array();
                }
                die(json_encode(array('errno' => 0, 'errstr' => url('default', 'welcome'))));
                break;
            case 'SP': //根据资金密码
                $username = strtolower($this->request->getPost('username', 'trim'));
                $encpwd = $this->request->getPost('encpwd', 'trim');
                //如果资金密码正确，给出重设登录密码页，否则提示资金密码不正确，并
                $result = users::login($username, '', $encpwd);
                if (!is_array($result)) {
                    showMsg($result);
                } else {
                    //登录信息写入session
                    $GLOBALS['SESSION']['username'] = $result['username'];
                    $GLOBALS['SESSION']['nvpair'] = md5($result['user_id'] . $result['username'] . microtime(true) . rand(10000, 99999));
                    //展示重置密码页
                    self::$view->setVar('nvpair', $GLOBALS['SESSION']['nvpair']);
                    self::$view->render("default_resetpwd");
                }
                break;
        }
    }

    //退出登录
    public function logout()
    {
        //记录日志
        // userLogs::addLog(1, $GLOBALS['SESSION']['username'] . '退出登录');
        start_sessions(300);
        //登录信息写入session
        $GLOBALS['SESSION']->destroy();
        $GLOBALS['SESSION'] = array();

        $url = getUrl() . "?a=login";
//这个只能重定向本帧，现在需要刷新父窗口
        //redirect(url('default', 'login'));
        $str = "<script>window.parent.location.href = '$url'; </script>";
        echo $str;
    }

    public function verifyCode()
    {
        $codeName = $this->request->getGet('cn', 'trim');
        if (!$codeName) {
            $codeName = 'verifyCode';
        }
//生成一个验证码，并把验证码信息存到session里面
        $captcha = new captcha();
        $captcha->setImage(array('width' => 100, 'height' => 30, 'type' => 'png'));
//改动：默认只显示数字
        //$captcha->setCode(array('characters' => '0-9,A-Z', 'length' => 4, 'deflect' => FALSE, 'multicolor' => FALSE));
        $captcha->setFont(array("space" => 0, "size" => 18, "left" => 5, "top" => 25, "file" => ''));
        $captcha->setMolestation(array("type" => 'point', "density" => 'fewness'));
        $captcha->setBgColor(array('r' => 200, 'g' => 200, 'b' => 200));
        $captcha->setFgColor(array('r' => 0, 'g' => 0, 'b' => 0));
        /*
                     * 将验证码信息保存到session
        */
// 输出到浏览器
        $captcha->paint();
        $_SESSION['verifyCode'] = $captcha->getcode();
    }

//处理留言
    public function saveGuestBook()
    {
        if (!$title = $this->request->getPost('title', 'trim')) {
            echo json_encode(array("errno" => 1, "errstr" => '参数不完整')); //参数不完整
            die();
        }
        if (!$content = $this->request->getPost('content', 'trim')) {
            echo json_encode(array("errno" => 1, "errstr" => '参数不完整')); //参数不完整
            die();
        }
        $qq = $this->request->getPost('qq', 'trim');
        $tel = $this->request->getPost('tel', 'trim');
        $kefu = $this->request->getPost('kefu', 'trim');

        $username = isset($GLOBALS['SESSION']['username']) ? $GLOBALS['SESSION']['username'] : '';
        $user_id = isset($GLOBALS['SESSION']['user_id']) ? $GLOBALS['SESSION']['user_id'] : '';
        $msgs = guestBooks::getItems($username);
        if ($msgs) {
            foreach ($msgs as $v) {
                if (date("Y-m-d", strtotime($v['create_time'])) == date("Y-m-d")) {
                    echo json_encode(array("errno" => 2, "errstr" => '尊敬的用户您好,你上一次的投诉我们正在积极处理中,监管部门将第一时间处理您的投诉,请耐心等待,24小时后您将可以再次投诉.')); //今日已提交过问题
                    die;
                }
            }
        }
        $data = array(
            'user_id' => $user_id,
            'username' => $username,
            'title' => $title,
            'domain' => $GLOBALS['REQUEST']->info['domain'],
            'qq' => $qq,
            'kefu' => $kefu,
            'tel' => $tel,
            'content' => $content,
            'client_ip' => $GLOBALS['REQUEST']['client_ip'],
            'create_time' => date("Y-m-d H:i:s"),
            'ts' => date("Y-m-d H:i:s"),
        );
        if (guestBooks::addItem($data)) {
            echo json_encode(array("errno" => 0, "errstr" => '提交成功')); //成功
        } else {
            echo json_encode(array("errno" => 3, "errstr" => '信息添加失败')); //添加失败
        }
        die();
    }

    public function register1()
    {
        $verify = $this->request->getPost('verify', 'trim');
        $realNameToggle = config::getConfig('real_name', 0);
        $regNeedMobile = config::getConfig('reg_need_mobile', 0);
        $regNeedQq = config::getConfig('reg_need_qq', 0);

        if ($verify != 'register') {
            // 注册界面
            $var = $this->request->getGet('var', 'trim');

            if ($var == "") {
                layerAlertM("对不起，推广链接不正确");
            }

            $tmpArr = explode("|", decode($var));
            $parent_id = $tmpArr[0];

            if (!is_numeric($parent_id) || !users::getItem($parent_id)) {
                layerAlertM("对不起，推广权限不够");
            }

            $prizeMode = $tmpArr[1];
            $userGroup = isset($tmpArr[2]) ? $tmpArr[2] : 1;

            if ($userGroup != 4) {//1总代 2一代 3普通代 4会员
                layerAlertM("对不起，推广链接不正确");
            }

            // 检查同个 IP 是否注册多个，如果是则考虑是恶意请求
            $cacheKey = $GLOBALS['REQUEST']['client_ip'];
            $mcUser = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
            $registerIpLimit = config::getConfig('register_ip_limit');

            if ($mcUser != false && $mcUser > $registerIpLimit - 1) {//这里不能写2
                layerAlertM("对不起，同个IP只能注册 " . $registerIpLimit . " 个账号");
            }

            self::$view->setVar('var', $var);
            self::$view->setVar('realNameToggle', $realNameToggle);
            self::$view->setVar('regNeedMobile', $regNeedMobile);
            self::$view->setVar('regNeedQq', $regNeedQq);
            self::$view->render('default_register');
            exit;
        } else {
            // 处理注册
            // 检查同个 IP 是否注册多个，如果是则考虑是恶意请求
            $cacheKey = $GLOBALS['REQUEST']['client_ip'];
            $mcUser = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
            $registerIpLimit = config::getConfig('register_ip_limit');

            if ($mcUser != false && $mcUser > $registerIpLimit - 1) {//这里不能写2
                die(json_encode(array('errno' => 9900, 'errstr' => '对不起，同个IP只能注册 ' . $registerIpLimit . ' 个账号')));
            }

            $mcUser = $mcUser !== false ? $mcUser : 0;
            $var = $this->request->getPost('var', 'trim');

            if ($var == "") {
                die(json_encode(array('errno' => 9901, 'errstr' => '对不起，推广链接不正确')));
            }

            $tmpArr = explode("|", decode($var));
            $parent_id = $tmpArr[0];

            if (!is_numeric($parent_id) || !users::getItem($parent_id)) {
                layerAlert("对不起，推广权限不够");
            }

            $prizeMode = $tmpArr[1]; //decode上级设置的返点 字符串 | 分隔
            $userGroup = isset($tmpArr[2]) ? $tmpArr[2] : 1;

            if ($userGroup != 4) {//推广链接过来的一律是会员
                die(json_encode(array('errno' => 9905, 'errstr' => '对不起，推广链接不正确')));
                // ("对不起，推广链接不正确");
            }

            $verifyCode = $this->request->getPost('verifyCode', 'trim');

            if (!(new captcha())->verifying($verifyCode)) {
                die(json_encode(array('errno' => 9900, 'errstr' => '验证码错误')));
            }

            $username = strtolower($this->request->getPost('username', 'trim')); //一律用小写

            if (!$username || !preg_match('`^[a-z]\w{5,11}$`', $username)) {
                die(json_encode(array('errno' => 9901, 'errstr' => '用户名长度为6-12个字母或数字，且必须以字母开头')));
            }

            // 检查用户名是否已注册
            if (users::getItem($username, -1)) {
                die(json_encode(array('errno' => 9906, 'errstr' => '该用户' . $username . '已经存在')));
            }

            $pwd = $this->request->getPost('pwd', 'trim');

            if (!$pwd || strlen($pwd) < 6 || strlen($pwd) > 15 || preg_match('`^\d+$`', $pwd) || preg_match('`^[a-zA-Z]+$`', $pwd)) {
                die(json_encode(array('errno' => 9902, 'errstr' => '密码长度为6-15字符，不能为纯数字或纯字母')));
            }

            $confirm_pwd = $this->request->getPost('confirm_pwd', 'trim');

            if ($pwd != $confirm_pwd) {
                die(json_encode(array('errno' => 9903, 'errstr' => '两次输入的密码不相同')));
            }

            $mobile = $regNeedMobile > 0 ? $this->request->getPost('mobile', 'trim') : '';

            if ($regNeedMobile > 0 && (!$mobile || !preg_match('`^1[3|4|5|7|8][0-9]{9}$`', $mobile))) {
                die(json_encode(array('errno' => 9907, 'errstr' => '手机号码有误')));
            }

            $qq = $regNeedQq > 0 ? $this->request->getPost('qq', 'trim', '') : '';

            if ($regNeedQq > 0 && !preg_match('`^[1-9]\d{4,11}$`', $qq)) {
                die(json_encode(array('errno' => 9905, 'errstr' => 'qq号码有误')));
            }

            $real_name = $realNameToggle > 0 ? $this->request->getPost('real_name', 'trim') : '';
            if ($realNameToggle > 0 && (!$real_name || !preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $real_name))) {
                die(json_encode(array('errno' => 9908, 'errstr' => '真实姓名必须为中文')));
            }
            // 这里看吧,姓名重复的太多了,如果确实要就加入到配置开关中
            // 如果真实姓名被使用则不能注册
            if ($realNameToggle > 0) {
                $realUsers = users::getItems(-1, true, 0, array('real_name', 'user_id'), 8, 0, '', '', '', '', '', '', $real_name);
                if (!empty($realUsers)) {
                    die(json_encode(array('errno' => 9909, 'errstr' => '该姓名已被使用')));
                }
            }

            //扩充会员字段
            $addtionalData = [];
            $addtionalData['nick_name'] = $username;
            $regNeedMobile > 0 && $addtionalData['mobile'] = $mobile;
            $realNameToggle > 0 && $addtionalData['real_name'] = $real_name;

            if (!is_numeric($parent_id) || !($parentUser = users::getItem($parent_id))) {
                die(json_encode(array('errno' => 9905, 'errstr' => '对不起，该上级推广链接不正确')));
            }

            try {
                if (!users::addUser($username, $pwd, $prizeMode, $parentUser, $addtionalData, 2, $qq)) {
                    die(json_encode(array('errno' => 9908, 'errstr' => '注册用户失败!请检查数据输入是否完整')));
                }

                if (users::imitateLogin($username, $pwd, 1)) {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey, $mcUser + 1, 86400);
                    $loginURL = "http://{$_SERVER['HTTP_HOST']}/?a=welcome";
                    die(json_encode(array('errno' => 0, 'errstr' => '注册成功,现在就可参与游戏', 'loginURL' => $loginURL)));
                } else {
                    die(json_encode(array('errno' => 9908, 'errstr' => '很遗憾由于网络原因登录失败，请及时联系工作人员')));
                }
            } catch (Exception $e) {
                die(json_encode(array('errno' => 9908, 'errstr' => $e->getMessage())));
            }
        }
    }
    public function register()
    {
        $verify = $this->request->getPost('verify', 'trim');
        $realNameToggle = config::getConfig('real_name', 0);
        $regNeedMobile = config::getConfig('reg_need_mobile', 0);
        $regNeedQq = config::getConfig('reg_need_qq', 0);

        if ($verify != 'register') {
            // 注册界面
            $var = $this->request->getGet('var', 'trim');

            if ($var == "") {
                layerAlert("对不起，推广链接不正确");
            }
            $marketLinkInfo = marketLink::getItemByCond("market_code = '{$var}'");

            if(empty($marketLinkInfo))
            {
                layerAlert("对不起，推广权限不够");
            }
            $user_id = isset($marketLinkInfo['user_id'])?$marketLinkInfo['user_id']:0;
            $userInfo = users::getItem($user_id);
            $parent_id = $user_id;

            if (!is_numeric($parent_id) || !users::getItem($parent_id)) {
                layerAlert("对不起，推广权限不够");
            }

            $prizeMode = isset($marketLinkInfo['prize_mode'])?$marketLinkInfo['prize_mode']:0;
            $userGroup = isset($userInfo['group_id']) ? $userInfo['group_id'] : 1;

//            if ($userGroup != 4) {//1总代 2一代 3普通代 4会员
//                layerAlert("对不起，推广链接不正确");
//            }

            // 检查同个 IP 是否注册多个，如果是则考虑是恶意请求
            $cacheKey = $GLOBALS['REQUEST']['client_ip'];
            $mcUser = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
            $registerIpLimit = config::getConfig('register_ip_limit');

            if ($mcUser != false && $mcUser > $registerIpLimit - 1) {//这里不能写2
                layerAlert("对不起，同个IP只能注册 " . $registerIpLimit . " 个账号");
            }

            self::$view->setVar('realNameToggle', $realNameToggle);
            self::$view->setVar('regNeedMobile', $regNeedMobile);
            self::$view->setVar('regNeedQq', $regNeedQq);
            self::$view->setVar('var', $var);
            self::$view->render('default_register');
            exit;
        } else {
            // 处理注册
            // 检查同个 IP 是否注册多个，如果是则考虑是恶意请求
            $cacheKey = $GLOBALS['REQUEST']['client_ip'];
            $mcUser = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
            $registerIpLimit = config::getConfig('register_ip_limit');

            if ($mcUser != false && $mcUser > $registerIpLimit - 1) {//这里不能写2
                die(json_encode(array('errno' => 9900, 'errstr' => '对不起，同个IP只能注册 ' . $registerIpLimit . ' 个账号')));
            }

            $mcUser = $mcUser !== false ? $mcUser : 0;
            $var = $this->request->getPost('var', 'trim');

            if ($var == "") {
                die(json_encode(array('errno' => 9901, 'errstr' => '对不起，推广链接不正确')));
            }

            $marketLinkInfo = marketLink::getItemByCond("market_code = '{$var}'");
            if(empty($marketLinkInfo))
            {
                layerAlert("对不起，推广权限不够");
            }
            $user_id = isset($marketLinkInfo['user_id'])?$marketLinkInfo['user_id']:0;
            $userInfo = users::getItem($user_id);
            $parent_id = $user_id;

            if (!is_numeric($parent_id) || !users::getItem($parent_id)) {
                layerAlert("对不起，推广权限不够");
            }
            $prizeMode = isset($marketLinkInfo['prize_mode'])?$marketLinkInfo['prize_mode']:0;
            $userGroup = isset($userInfo['group_id']) ? $userInfo['group_id'] : 1;
//            $prizeMode = $tmpArr[1]; //decode上级设置的返点 字符串 | 分隔
//            $userGroup = isset($tmpArr[2]) ? $tmpArr[2] : 1;
//
//            if ($userGroup != 4) {//推广链接过来的一律是会员
//                die(json_encode(array('errno' => 9905, 'errstr' => '对不起，推广链接不正确')));
//                // layerAlert("对不起，推广链接不正确");
//            }

            $verifyCode = $this->request->getPost('verifyCode', 'trim');

            if (!(new captcha())->verifying($verifyCode)) {
                die(json_encode(array('errno' => 9900, 'errstr' => '验证码错误,请重新获取或清除缓存')));
            }

            $username = strtolower($this->request->getPost('username', 'trim')); //一律用小写

            if (!$username || !preg_match('`^[a-z]\w{5,11}$`', $username)) {
                die(json_encode(array('errno' => 9901, 'errstr' => '用户名长度为6-12个字母或数字，且必须以字母开头')));
            }

            // 检查用户名是否已注册
            if (users::getItem($username, -1)) {
                die(json_encode(array('errno' => 9906, 'errstr' => '该用户' . $username . '已经存在')));
            }

            $pwd = $this->request->getPost('pwd', 'trim');

            if (!$pwd || strlen($pwd) < 6 || strlen($pwd) > 15 || preg_match('`^\d+$`', $pwd) || preg_match('`^[a-zA-Z]+$`', $pwd)) {
                die(json_encode(array('errno' => 9902, 'errstr' => '密码长度为6-15字符，不能为纯数字或纯字母')));
            }

            $confirm_pwd = $this->request->getPost('confirm_pwd', 'trim');

            if ($pwd != $confirm_pwd) {
                die(json_encode(array('errno' => 9903, 'errstr' => '两次输入的密码不相同')));
            }

            $mobile = $regNeedMobile > 0 ? $this->request->getPost('mobile', 'trim') : '';

            if ($regNeedMobile > 0 && (!$mobile || !preg_match('`^1[3|4|5|7|8][0-9]{9}$`', $mobile))) {
                die(json_encode(array('errno' => 9907, 'errstr' => '手机号码有误')));
            }

            $qq = $regNeedQq > 0 ? $this->request->getPost('qq', 'trim', '') : '';

            if ($regNeedQq > 0 && !preg_match('`^[1-9]\d{4,11}$`', $qq)) {
                die(json_encode(array('errno' => 9905, 'errstr' => 'qq号码有误')));
            }

            $real_name = $realNameToggle > 0 ? $this->request->getPost('real_name', 'trim') : '';
            if ($realNameToggle > 0 && (!$real_name || !preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $real_name))) {
                die(json_encode(array('errno' => 9908, 'errstr' => '真实姓名必须为中文')));
            }

            // 这里看吧,姓名重复的太多了,如果确实要就加入到配置开关中
            // 如果真实姓名被使用则不能注册
            if ($realNameToggle > 0) {
                $realUsers = users::getItems(-1, true, 0, array('real_name', 'user_id'), 8, 0, '', '', '', '', '', '', $real_name);
                if (!empty($realUsers)) {
                    die(json_encode(array('errno' => 9909, 'errstr' => '该姓名已被使用')));
                }
            }

            //扩充会员字段
            $addtionalData = [];
            $addtionalData['nick_name'] = $username;
            $regNeedMobile > 0 && $addtionalData['mobile'] = $mobile;
            $realNameToggle > 0 && $addtionalData['real_name'] = $real_name;

            if (!is_numeric($parent_id) || !($parentUser = users::getItem($parent_id))) {
                die(json_encode(array('errno' => 9905, 'errstr' => '对不起，该上级推广链接不正确')));
            }

            try {
                if (!users::addUser($username, $pwd, $prizeMode, $parentUser, $addtionalData, 2, $qq)) {
                    die(json_encode(array('errno' => 9908, 'errstr' => '注册用户失败!请检查数据输入是否完整')));
                }

                if (users::imitateLogin($username, $pwd, 1)) {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey, $mcUser + 1, 86400);
                    $loginURL = "http://{$_SERVER['HTTP_HOST']}/?a=welcome";
                    die(json_encode(array('errno' => 0, 'errstr' => '注册成功,现在就可参与游戏', 'loginURL' => $loginURL)));
                } else {
                    die(json_encode(array('errno' => 9908, 'errstr' => '很遗憾由于网络原因登录失败，请及时联系工作人员')));
                }
            } catch (Exception $e) {
                die(json_encode(array('errno' => 9908, 'errstr' => $e->getMessage())));
            }
        }
    }
    //用于市场推广账号的注册
    public function marketReg()
    {
        $verify = $this->request->getPost('verify', 'trim');
        $registerIpLimit = config::getConfig('register_ip_limit');
        // 注册时是否需要特殊资料的开关
        $realNameToggle = config::getConfig('real_name', 0);
        $regNeedMobile = config::getConfig('reg_need_mobile', 0);
        $regNeedQq = config::getConfig('reg_need_qq', 0);

        if ($verify != 'register') {
            // 检查同个 IP 是否注册多个，如果是则考虑是恶意请求
            $cacheKey = $GLOBALS['REQUEST']['client_ip'];
            $mcUser = $GLOBALS['mc']->get(__CLASS__, $cacheKey);

            if ($mcUser != false && $mcUser > $registerIpLimit - 1) {//这里不能写2
                layerAlertM("对不起，同个IP只能注册 " . $registerIpLimit . " 个账号");
            }

            self::$view->setVar('realNameToggle', $realNameToggle);
            self::$view->setVar('regNeedMobile', $regNeedMobile);
            self::$view->setVar('regNeedQq', $regNeedQq);
            self::$view->render('default_marketreg');
            exit;
        } else {
            // 处理注册
            // 检查同个 IP 是否注册多个，如果是则考虑是恶意请求
            $cacheKey = $GLOBALS['REQUEST']['client_ip'];
            $mcUser = $GLOBALS['mc']->get(__CLASS__, $cacheKey);

            if ($mcUser != false && $mcUser > $registerIpLimit - 1) {//这里不能写2
                die(json_encode(array('errno' => 9900, 'errstr' => '对不起，同个IP只能注册 ' . $registerIpLimit . ' 个账号')));
            }

            $mcUser = $mcUser !== false ? $mcUser : 0;

            $verifyCode = $this->request->getPost('verifyCode', 'trim');
            $username = strtolower($this->request->getPost('username', 'trim')); //一律用小写
            $pwd = $this->request->getPost('pwd', 'trim');
            $confirm_pwd = $this->request->getPost('confirm_pwd', 'trim');
            $qq = $regNeedQq > 0 ? $this->request->getPost('qq', 'trim', '') : '';
            $mobile = $regNeedMobile > 0 ? $this->request->getPost('mobile', 'trim') : '';
            $real_name = $realNameToggle > 0 ? $this->request->getPost('real_name', 'trim') : '';

            if (!(new captcha())->verifying($verifyCode)) {
                die(json_encode(array('errno' => 9900, 'errstr' => '验证码错误,请重新获取或清除缓存')));
            }

            if (!$username || !preg_match('`^[a-z]\w{5,11}$`', $username)) {
                die(json_encode(array('errno' => 9901, 'errstr' => '用户名长度为6-12个字母或数字，且必须以字母开头')));
            }
            if ($realNameToggle > 0 && (!$real_name || !preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $real_name))) {
                die(json_encode(array('errno' => 9908, 'errstr' => '真实姓名必须为中文')));
            }
            if (!$pwd || strlen($pwd) < 6 || strlen($pwd) > 15 || preg_match('`^\d+$`', $pwd) || preg_match('`^[a-zA-Z]+$`', $pwd)) {
                die(json_encode(array('errno' => 9902, 'errstr' => '密码长度为6-15字符，不能为纯数字或纯字母')));
            }
            if ($pwd != $confirm_pwd) {
                die(json_encode(array('errno' => 9903, 'errstr' => '两次输入的密码不相同')));
            }

            if ($regNeedQq > 0 && !preg_match('`^[1-9]\d{4,11}$`', $qq)) {
                die(json_encode(array('errno' => 9905, 'errstr' => 'qq号码有误')));
            }
            if ($regNeedMobile > 0 && (!$mobile || !preg_match('`^1[3|4|5|7|8][0-9]{9}$`', $mobile))) {
                die(json_encode(array('errno' => 9907, 'errstr' => '手机号码有误')));
            }

            // 这里看吧,姓名重复的太多了,如果确实要就加入到配置开关中
            //如果真实姓名被使用则不能注册
            if ($realNameToggle > 0) {
                $realUsers = users::getItems(-1, true, 0, array('real_name', 'user_id'), 8, 0, '', '', '', '', '', '', $real_name);
                if (!empty($realUsers)) {
                    die(json_encode(array('errno' => 9909, 'errstr' => '该姓名已被使用')));
                }
            }

            $prizeMode = 0; //市场推广域名注册的会员都是最高返点
//            preg_match('/^(((1?\d{1,2})|(2[0-4]\d)|(25[0-5]))\.){3}((1?\d{1,2})|(2[0-4]\d)|(25[0-5]))$/',$_SERVER['HTTP_HOST'],$addurl);
//			if(empty($addurl)){
//				preg_match('/[\w][\w-]*\.(?:com\.cn|com|cn|co|net|org|gov|cc|biz|info)(\/|$)/isU',$_SERVER['HTTP_HOST'],$addurl);
//			}
            $addurl = $_SERVER["REMOTE_ADDR"];
            if($addurl){
                preg_match('/[\w][\w-]*\.(?:com\.cn|com|cn|co|net|org|gov|cc|biz|info)(\/|$)/isU',$_SERVER['HTTP_HOST'],$domain);
                $addurl = isset($domain[0])?$domain[0]:'';
            };
            $domainUser = domains::getDomainUser($addurl);
            if(empty($domainUser)){//如果没有绑定用户则归属到use_id最小的推广用户去
                $userObj =  new baseModel('users');
                $userObj->options('order','ORDER BY user_id asc');
                $topUser = $userObj->where(['type'=>1,'status'=>8,'is_test'=>0,'level'=>0])->limit(0,1)->select();
                $tgUser = reset($topUser);
                $top_id = $tgUser['user_id'];
            } else { //如果该绑定了用户但是是普通总代 $prizeMode = 1900;
                if ($domainUser['type'] == 0) {
                    $prizeMode = 1900;
                }
                $top_id = $domainUser['user_id'];
            }

            if ($prizeMode == 0) {//该域名绑定的总代存在并且是推广总代
                //得到总代时时彩奖金组
                $representLotterys = lottery::getRepresent();
                $tmpUserRebate = array_spec_key(userRebates::getItems($top_id), 'property_id');
                $prizeMode = 2 * $representLotterys[1]['zx_max_comb'] * (1 - $representLotterys[1]['total_profit'] + $tmpUserRebate[1]['rebate']);
            }

            //检查用户名是否已注册
            if (users::getItem($username, -1)) {
                die(json_encode(array('errno' => 9906, 'errstr' => '该用户' . $username . '已经存在')));
            }

            //扩充会员字段
            $addtionalData = [];
            $regNeedMobile > 0 && $addtionalData['mobile'] = $mobile;
            $realNameToggle > 0 && $addtionalData['real_name'] = $real_name;

            if (!is_numeric($top_id) || !($parentUser = users::getItem($top_id))) {
                die(json_encode(array('errno' => 9905, 'errstr' => '对不起，该上级推广链接不正确')));
            }

            try {
                if (!users::addUser($username, $pwd, $prizeMode, $parentUser, $addtionalData, 2, $qq)) {
                    die(json_encode(array('errno' => 9908, 'errstr' => '注册用户失败!请检查数据输入是否完整')));
                }
                $result = users::getItem($username);
                //登录信息写入session
                $GLOBALS['SESSION']['user_id'] = $result['user_id'];
                $GLOBALS['SESSION']['username'] = $result['username'];
                $GLOBALS['SESSION']['nick_name'] = $result['nick_name'];
                $GLOBALS['SESSION']['is_test'] = $result['is_test'];
                $GLOBALS['SESSION']['level'] = $result['level'];
                $GLOBALS['SESSION']['parent_id'] = $result['parent_id'];
                $GLOBALS['SESSION']['top_id'] = $result['top_id'];
                $GLOBALS['SESSION']['group_id'] = $result['group_id'];
                $GLOBALS['SESSION']['last_ip'] = $result['last_ip'];
                $GLOBALS['SESSION']['last_time'] = $result['last_time'];
                $GLOBALS['SESSION']['balance'] = $result['balance'];

                $loginURL = "http://{$_SERVER['HTTP_HOST']}/?a=welcome";
                $GLOBALS['mc']->set(__CLASS__, $cacheKey, $mcUser + 1, 86400);
                die(json_encode(array('errno' => 0, 'errstr' => '注册成功,现在就可参与游戏', 'loginURL' => $loginURL)));
            } catch (Exception $e) {
                die(json_encode(array('errno' => 9909, 'errstr' => $e->getMessage())));
            }
        }
    }

    private function wechatReg($domain, $prizeMode, $parentUser)
    {

        //手机端可能有从微信进入需先判断
        $wechat = wechat::instance();
        $res = $wechat->oauth2();
        if (is_array($res)) {//如果网页授权了
            $user = users::getItem($res['openid'], 8, false, 2);
            if (!$user) {
                $username = $this->createUsername();
                $pwd = substr($username, 0, 1) . date('Ymd');
                $addtionalData = array(
                    "nick_name" => $res['nickname'],
                );
                if (!users::addUser($username, $pwd, $prizeMode, $parentUser, $addtionalData, 2, '', 1, $res['openid'], $res['nickname'])) {
                    layerAlertM('注册用户失败!请检查数据输入是否完整2');
                }
            }
            users::imitateLogin($res['openid'], '', 2);
            layerAlertM("欢迎{$res['nickname']},可直接去体验游戏", "http://{$domain}/?a=welcome", '进入游戏');
        }
    }

    //随机生成用户名
    private function createUsername()
    {
        do {
            // 生成随机账号
            $username = strtolower(getRandChar(rand(6, 10)));
            //检查用户名是否已注册
            if (!users::getItem($username, -1)) {
                break;
            }
        } while (1);

        return $username;
    }

    //自动注册一律是推广链接
    public function autoReg()
    {
        $var = $this->request->getGet('var', 'trim');

        if ($var == "") {
            layerAlertM("对不起，推广链接不正确1");
        }

        $tmpArr = explode("|", decode($var));
        //解密
        $parent_id = $tmpArr[0];
        if (!is_numeric($parent_id) || !users::getItem($parent_id)) {
            layerAlertM("对不起，推广权限不够");
        }

        $prizeMode = $tmpArr[1]; //decode上级设置的返点 字符串 | 分隔
        $tgtype = isset($tmpArr[2]) ? $tmpArr[2] : 1;
        if ($tgtype != 4) {
            layerAlertM("对不起，推广链接不正确2");
        }

        //找到主域名
        $mainDomain = $_SERVER['HTTP_HOST'];

        //检查同个IP是否一天注册多个，如果是则考虑是恶意请求
        $cacheKey = $GLOBALS['REQUEST']['client_ip'];
        $mcUser = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
        if ($mcUser != false && $mcUser > 1) {
            layerAlertM("对不起，同个IP只能注册两个账号");
        }

        $mcUser = $mcUser !== false ? $mcUser : 0;

        $username = $this->createUsername();
        $pwd = substr($username, 0, 1) . date('Ymd');

        if (!is_numeric($parent_id) || !($parentUser = users::getItem($parent_id))) {
            layerAlertM('对不起，该上级推广链接不正确3');
        }

        //推广链接正确的情况下检查是否是网页授权过来的
        $this->wechatReg($mainDomain, $prizeMode, $parentUser);

        try {
            //扩充会员字段
            $addtionalData = array(
                "nick_name" => $username,
            );
            if (!users::addUser($username, $pwd, $prizeMode, $parentUser, $addtionalData, 2, '', 1)) {
                layerAlertM('注册用户失败!请检查数据输入是否完整');
            }

            if (users::imitateLogin($username, $pwd, 1)) {
                //放入缓存，同一个IP只能注册两个
                $GLOBALS['mc']->set(__CLASS__, $cacheKey, $mcUser + 1, 86400);
                layerAlertM("恭喜注册成功,可直接去体验游戏！初始登陆密码：{$pwd}", "http://{$mainDomain}/?a=welcome", '进入游戏');
            } else {
                layerAlertM("很遗憾由于网络原因登录失败，请及时联系工作人员");
            }
        } catch (Exception $e) {
            layerAlertM($e->getMessage());
        }
    }


    public function download()
    {
        self::$view->render("default_download");
    }

    public function qrRedirect()
    {
        $siteMainDomain = config::getConfig('site_main_domain');
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            self::$view->render("default_qrredirect");
        } else {
            redirect('http://' . $siteMainDomain . '/upload/jyz_160309_1_1_1.apk', 1, FALSE);
        }
    }

    /**
     * 拿到开奖结果或最新开奖信息
     */
    public function openInfo()
    {
        # 本地不开奖,测试拿数据.
        // header('Access-Control-Allow-Origin:*');

        // lotteryId针对于单独彩种获取
        $lotteryId = $this->request->getGet('lotteryId', 'intval', 0);
        $lotteryList = $lotteryId > 0 ? [] : lottery::getItemsNew(['lottery_id','name','cname']);
        // sort 根据奖期排序 ASC DESC
        $sort = $this->request->getGet('sort', 'trim', 'DESC');
        // onlyLast 仅返回最后一期
        $onlyLast = $this->request->getGet('onlyLast', 'intval', 0);

        $fun = function (&$lottery) {
            switch ($lottery['lottery_id']) {
                case 9 :
                    return '?c=game&a=low3D';
                case 10 :
                    return '?c=game&a=P3P5';
                case 26 :
                    return '?c=game&a=xyft_x';
                default :
                    return '?c=game&a=' . strtolower($lottery['name']);
            }
        };

        /* 仅链接 开始 */
        // onlyLink 仅返回游戏链接
        $onlyLink = $this->request->getGet('onlyLink', 'intval', 0);
        if ($onlyLink) {
            $lotteryList || $lotteryList = lottery::getItemsNew(['lottery_id','name','cname']);
            foreach ($lotteryList as &$lottery) {
                $lottery = [
                    'lottery_id' => $lottery['lottery_id'],
                    'fun' => $fun($lottery),
                    'cname' => $lottery['cname'],
                ];
            }

            response($lotteryList);
        }
        /* 仅链接 结束 */

        // 下面是需要奖号的
        $GLOBALS['redis']->pushPrefix()->select(REDIS_DB_COMMON_DATA);

        if ($lotteryList) {
            foreach ($lotteryList as $key => &$lottery) {
                $cacheKey = 'openInfo_' . $lottery['lottery_id'];
                if ($info = $GLOBALS['redis']->hGet('cronOpenInfo', $cacheKey)) {
                    $info = json_decode($info, true);

                    if ($onlyLast) {
                        $lottery = [
                            'lotteryId' => $lottery['lottery_id'],
                            'fun' => $fun($lottery),
                            'issueInfo' => [
                                'count_down' => strtotime($info['issueInfo']['end_time']) - REQUEST_TIME,
                            ],
                            'lastIssueInfo' => [
                                'cname' => $info['lastIssueInfo']['cname'],
                                'issue' => $info['lastIssueInfo']['issue'],
                                'code' => $info['lastIssueInfo']['code'],
                            ],
                        ];
                    } else {
                        $info['issueInfo']['end_time'] = strtotime($info['issueInfo']['end_time']);
                        $info['issueInfo']['count_down'] = $info['issueInfo']['end_time'] - REQUEST_TIME;
                        $info['issueInfo']['input_time'] = strtotime($info['issueInfo']['input_time']);

                        if (strtoupper($sort) == 'ASC' && $info['openIssues']) $info['openIssues'] = array_reverse($info['openIssues']);
                        $info['fun'] = $fun($lottery);
                        $lottery = $info;
                    }
                } else {
                    // 类似秒秒彩这样的彩种是没有记录数据的
                    unset($lotteryList[$key]);
                }
            }

            response($lotteryList);
        } else {
            $cacheKey = 'openInfo_' . $lotteryId;
            if ($info = $GLOBALS['redis']->hGet('cronOpenInfo', $cacheKey)) {
                $info = json_decode($info, true);
                if (strtoupper($sort) == 'ASC') $info['openIssues'] = array_reverse($info['openIssues']);
            }

            response($info['openIssues']);
        }
    }

    public function domainInfo()
    {
        //判断是否推广域名
        $domain = domains::getItem($_SERVER['HTTP_HOST']);
        // 如果域名后台没有配置，默认按照普通域名处理
        echo empty($domain) ? 0 : $domain['type'];
    }
}
