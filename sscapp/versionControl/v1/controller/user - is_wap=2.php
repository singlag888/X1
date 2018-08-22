<?php

use common\model\baseModel;
use common\model\onlineUser;

/**
 * 控制器：商户管理后台
 * 承接管理员登录等基本后台业务
 */
class userController extends sscappController
{
    /**
     * 方法概览
     * @var array
     */
    public $titles = array(
        'info' => '查看/修改基础信息',
        'login' => '用户登录',
        'loginout' => '用户退出登录',
        'register' => '用户注册',
        'editSecPwd' => '设置资金密码',
        'teamWinReport' => '团队盈亏报表',
        'childList' => '会员管理',
        'regChild' => '新增代理',
        'makelink' => '新增会员(推广码)',
        'receiveBox' => '系统信息',
        'uploadImg' => '上传图片',
    );

    public function init()
    {
        parent::init(parent::INIT_SESSION);
    }

    public function info()
    {
        list($user_id, $user) = $this->chkUser();
        if ($this->getIsPostRequest()) {
            $data = [];
            $nick_name = $this->request->getPost('nick_name', 'string', '');
            $real_name = $this->request->getPost('real_name', 'string', '');
            $mobile = $this->request->getPost('mobile', 'string', '');
            if (!empty($nick_name) && $nick_name != $user['nick_name']) $data['nick_name'] = $nick_name;

            if(!empty($user['real_name']) && !empty($real_name))$this->showMsg(5002, '真实姓名已存在不能再次进行修改');
//            if (!empty($real_name) && $real_name != $user['real_name']) $data['real_name'] = $real_name;
            if (!empty($real_name) && empty( $user['real_name'])) $data['real_name'] = $real_name;
            if (!empty($data['real_name']) && !$this->chkRealname($real_name)) $this->showMsg(5002, mobileErrorCode::REALNAME_FORMAT_ERROR);

            if(!empty($user['mobile']) && !empty($mobile))$this->showMsg(5005, '手机号码已绑定,修改请联系客服');
//            if (!empty($mobile) && $mobile != $user['mobile']) $data['mobile'] = $mobile;
            if (!empty($mobile) && empty( $user['mobile'])) $data['mobile'] = $mobile;
            if (!empty($data['mobile']) && !$this->chkTel($mobile)) $this->showMsg(5005, mobileErrorCode::TEL_FORMAT_ERROR);

            if (!empty($data['real_name']) && config::getConfig('real_name', 0) > 0) {
                $realUsers = users::getItems(-1, true, 0, array('real_name', 'user_id'), [1, 5, 8], 0, '', '', '', '', '', '', $real_name);
                if (!empty($realUsers)) $this->showMsg(5006, mobileErrorCode::REALNAME_REPEAT_ERROR);
            }
            if (!empty($data['mobile'])) {
                $mobileUsers = users::getItems(-1, true, 0, array('mobile', 'user_id'), [1, 5, 8], 0, '', '', '', '', '', '', '', $mobile);
                if (!empty($mobileUsers)) $this->showMsg(5010, mobileErrorCode::MOBILE_REPEAT_ERROR);
            }
            if (empty($data))/*$this->showMsg(5017,mobileErrorCode::UPDATE_USER_SAME_ERROR);*/
                $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
            if (is_numeric(users::updateItem($user_id, $data))) {
                foreach ($data as $key => $val) $GLOBALS['SESSION'][$key] = $val;
                $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
            }
            $this->showMsg(5016, mobileErrorCode::UPDATE_USER_INFO_ERROR);
        }
        $card = userBindCards::getItemByUid($user_id,1,'bank_id,card_num,bank_username');
        $bank_name = isset($card['bank_id']) && !empty($bid = $card['bank_id']) && isset($GLOBALS['cfg']['bankList'][$bid]) ? $GLOBALS['cfg']['bankList'][$bid] : '';
        $bank_bank_username = isset($card['bank_username']) ? $card['bank_username'] : '';
        $card_num = empty($card) ? '' : substr($card['card_num'], 0, 4) . str_repeat('*', strlen($card['card_num']) - 8) . substr($card['card_num'], -4, 4);
        $data = [
            'username' => $user['username'],
            'nick_name' => $user['nick_name'],
            'real_name' => $user['real_name'],
            'balance' => $user['balance'],
            'mobile' => $user['mobile'],
            'isset_secpwd' => !empty($user['secpwd']) ? 1 : 0,
            'card_num' => $card_num,
            'bank_bank_username' => $bank_bank_username,
            'bank_name' => $bank_name,
        ];
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $data);
    }

    /**
     * 验证用户名
     * @param $username
     * @return bool
     */
    private function chkUsername($username)
    {
        return !empty($username) && preg_match('`^[a-z]\w{5,11}$`', $username);
    }

    /**
     * 验证真实姓名
     * @param $realname
     * @return bool
     */
    private function chkRealname($realname)
    {
        return !empty($realname) && preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $realname);
    }

    /**
     * 验证密码
     * @param $password
     * @return bool
     */
    private function chkPwd($password)
    {
        return !empty($password) && strlen($password) >= 6 && strlen($password) <= 15 && !preg_match('`^\d+$`', $password) && !preg_match('`^[a-zA-Z]+$`', $password);
    }

    /**
     * 验证手机号码
     * @param $mobile
     * @return bool
     */
    private function chkTel($mobile)
    {
        return !empty($mobile) && preg_match('`^1[3|4|5|7|8][0-9]{9}$`', $mobile);
    }

    /**
     * 验证qq号码
     * @param $qq
     * @return bool
     */
    private function chkQq($qq)
    {
        return !empty($qq) && preg_match('`^[1-9]\d{4,11}$`', $qq);
    }

    /**
     * 获取json请求数据
     * @return array
     */
    private function _getPostJson($params)
    {
        $jsonDatas = [];
        foreach ($params as $param) {
            $jsonDatas[] = trim($this->getJsonRequest($param, 'string', ''));
        }
        return $jsonDatas;
    }

    /**
     * 获取post请求数据
     * @return array
     */
    private function _getPost($params)
    {
        $postDatas = [];
        foreach ($params as $param) {
            $postDatas[] = trim($this->request->getPost($param, 'string', ''));
        }
        return $postDatas;
    }

    /**
     * 验证验证码
     * @param $code 验证码
     * @param $key redis key
     * @param $type 0:不区分大小写 1:区分大小写
     * @return bool
     */
    private function verify2Code($code, $key, $type = 0)
    {
        $r_code = $this->cutRedisDatabase(function () use ($key, $code) {
            $res = $GLOBALS['redis']->get('verify_' . $key);
            $GLOBALS['redis']->del('verify_' . $key);
            return $res;
        });
        if ($type == 1) {
            return !empty($r_code) && $r_code == $code;
        }

        return !empty($r_code) && strtolower($r_code) == strtolower($code);
    }

    /**************************************注册register start*********************************************/
    /**
     * 用户注册参数
     * @param $username 会员账号
     * @param $realname 真实姓名
     * @param $password 登录密码
     * @param $mobile 手机号码
     * @param $qq qq号码
     */
    public function regconf()
    {
        $configs = config::rgetConfigs(['real_name', 'reg_need_mobile', 'reg_need_qq']);
        if (empty($configs)) $configs = '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $configs);
    }

    /**
     * author rock
     *  $username 会员账号
     *  $realname 真实姓名
     *  $password 登录密码
     *  $mobile 手机号码
     *  $qq qq号码
     */
    public function register()
    {
        list($username, $realname, $password, $mobile, $qq, $var, $verifyCode, $captcha_id, $register_from) = $this->_getPost(['username', 'realname', 'password', 'mobile', 'qq', 'var', 'verifyCode', 'captcha_id', 'register_from']);
        if (!$this->verify2Code($verifyCode, $captcha_id)) $this->showMsg(7034, mobileErrorCode::VERIFYCODE_ERROR);

        list($registerIpLimit, $realNameToggle, $regNeedMobile, $regNeedQq) = $this->_getRegConf();
        // 处理注册
        // 检查同个 IP 是否注册多个，如果是则考虑是恶意请求
        $mcUser = $this->_getRegMcUser($registerIpLimit);
        list($errno, $errmsg) = $this->_chkRegPostData($username, $password, $realname, $mobile, $qq, $registerIpLimit, $realNameToggle, $regNeedQq, $regNeedMobile);
        if (!empty($errno) && !empty($errmsg)) $this->showMsg($errno, $errmsg);
        list($prizeMode, $parentUser, $addtionalData) = $this->_handleRegRebate($var, $regNeedMobile, $realNameToggle, $mobile, $realname);
        try {
            if (!users::addUser($username, $password, $prizeMode, $parentUser, $addtionalData, 2, $qq)) $this->showMsg(5009, mobileErrorCode::REGISTER_ERROR);
            $this->_setRegMcUser($mcUser);

            /******************* author snow 判断如果注册来源是wap写入session 信息,并返回**********************/
            if ($register_from == 'wap') {
                $user = users::getItem($username, -1);
                //>>获取用户信息
                $returnData = $this->_setSessionAndReturnData($user);
                $this->showMsg(0, '登陆成功', $returnData);

            }
            /******************* author snow 判断如果注册来源是wap写入session 信息,并返回**********************/
            $this->showMsg(0, '注册成功!');
        } catch (Exception $e) {
            $this->showMsg(5010, $e->getMessage());
        }
    }

    /**
     * 获取限制相关配置
     * @return array
     */
    private function _getRegConf()
    {
        $configs = [];
        $configs[] = config::getConfig('register_ip_limit');
        // 注册时是否需要特殊资料的开关
        $configs[] = config::getConfig('real_name', 0);
        $configs[] = config::getConfig('reg_need_mobile', 0);
        $configs[] = config::getConfig('reg_need_qq', 0);
        return $configs;
    }

    /**
     * 设置请求ip注册个数
     * @return mixed
     */
    private function _setRegMcUser($mcUser)
    {
        $GLOBALS['mc']->set(__CLASS__, $GLOBALS['REQUEST']['client_ip'], $mcUser + 1, 86400);//同一ip注册账号个数限制,时间为1天
    }

    /**
     * 获取请求ip注册个数
     * @return mixed
     */
    private function _getRegMcUser($registerIpLimit)
    {
        $mcUser = $GLOBALS['mc']->get(__CLASS__, $GLOBALS['REQUEST']['client_ip']);
        if ($mcUser != false && $mcUser > $registerIpLimit - 1) $this->showMsg(5011, '对不起，同个IP只能注册 ' . $registerIpLimit . ' 个账号');
        return $mcUser == false ? 0 : $mcUser;
    }

    /**
     * 验证数据
     * @param $mcUser 请求ip地址
     * @param $username 用户名
     * @param $password 密码
     * @param $realname 真实名字
     * @param $mobile 手机号
     * @param $qq qq号码
     * @param $registerIpLimit ip限制
     * @param $realNameToggle 是否允许同名注册
     * @param $regNeedQq 是否需要qq号
     * @param $regNeedMobile 是否需要手机号
     */
    private function _chkRegPostData($username, $password, $realname, $mobile, $qq, $registerIpLimit, $realNameToggle, $regNeedQq, $regNeedMobile)
    {
        if (!$this->chkUsername($username)) $this->showMsg(5001, mobileErrorCode::USERNAME_FORMAT_ERROR);
        if (!$this->chkPwd($password)) $this->showMsg(5003, mobileErrorCode::PWD_FORMAT_ERROR);
        if ($realNameToggle && !$this->chkRealname($realname)) $this->showMsg(5002, mobileErrorCode::REALNAME_FORMAT_ERROR);
        if ($regNeedQq > 0 && !$this->chkQq($qq)) $this->showMsg(5004, mobileErrorCode::QQ_FORMAT_ERROR);
        if ($regNeedMobile > 0 && !$this->chkTel($mobile)) $this->showMsg(5005, mobileErrorCode::TEL_FORMAT_ERROR);
        //如果真实姓名被使用则不能注册
        if ($realNameToggle > 0) {
            $realUsers = users::getItems(-1, true, 0, array('user_id'), [1, 5, 8], 0, '', '', '', '', '', '', $realname, '', '', '', -1, 0, 1);
            if (!empty($realUsers)) $this->showMsg(5006, mobileErrorCode::REALNAME_REPEAT_ERROR);
        }
        if ($regNeedMobile > 0) {
            $mobileUsers = users::getItems(-1, true, 0, array('user_id'), [1, 5, 8], 0, '', '', '', '', '', '', '', $mobile, '', '', -1, 0, 1);
            if (!empty($mobileUsers)) $this->showMsg(5010, mobileErrorCode::MOBILE_REPEAT_ERROR);
        }
        if ($regNeedQq > 0) {
            $qqUsers = users::getItems(-1, true, 0, array('user_id'), [1, 5, 8], 0, '', '', '', '', '', '', '', '', $qq, '', -1, 0, 1);
            if (!empty($qqUsers)) $this->showMsg(5011, mobileErrorCode::QQ_REPEAT_ERROR);
        }
        //检查用户名是否已注册
        if (users::getItem($username, -1)) $this->showMsg(5007, mobileErrorCode::USERNAME_REPEAT_ERROR);
    }

    /**
     * 返点
     * @param $regNeedMobile 是否需要手机号
     * @param $realNameToggle 是否允许同名
     * @param $mobile 手机号码
     * @param $realname 真实姓名
     * @return array
     */
    private function _handleRegRebate($var, $regNeedMobile, $realNameToggle, $mobile, $realname)
    {
        $result = [];
        $is_wap=$this->request->getGet('is_wap','intval',0);
        $register_from=$this->request->getPost('register_from','string','app');
        if (!empty($var)){
            $marketLinkInfo = marketLink::getItemByCond("market_code = '{$var}'");
            if (empty($marketLinkInfo)) $this->showMsg(5018, '推广码错误');
            if (!isset($marketLinkInfo['prize_mode']) || empty($marketLinkInfo['prize_mode'])) $this->showMsg(5018, '推广码错误');
            $top_id = isset($marketLinkInfo['user_id']) ? $marketLinkInfo['user_id'] : 0;
            if (empty($top_id)) $this->showMsg(5018, '推广码错误');
            if (empty($parent = users::getItem($top_id)) || $parent['status'] != 8 || $parent['level'] == 100) $this->showMsg(5018, '推广码错误');
            $prizeMode = $marketLinkInfo['prize_mode'];
        } elseif($is_wap==0 || $register_from=='app'){
            $this->showMsg(5018, '请输入推广码');
        }else{
            $prizeMode = 0; //市场推广域名注册的会员都是最高返点
            preg_match('/[\w][\w-]*\.(?:com\.cn|com|cn|co|net|org|gov|cc|biz|info)(\?|\/|$)/isU',$_SERVER['HTTP_HOST'],$addurl);
//            $path='';
//            if(isset($addurl[0])&&!empty($path=$addurl[0])){
//                $path=rtrim(rtrim($path,'?'),'/');
//            }
//            $domainUser = !empty($path)?domains::getDomainUser($path):'';
            $domainUser = isset($addurl[0])?domains::getDomainUser($addurl[0]):'';
            if (empty($domainUser)) {//如果没有绑定用户则归属到use_id最小的推广用户去
                $userObj = new baseModel('users');
                $userObj->options('order', 'ORDER BY user_id asc');
                $topUser = $userObj->where(['type' => 1, 'status' => 8, 'is_test' => 0, 'level' => 0])->limit(0, 1)->select();
                if (empty($topUser)) {
                    $this->showMsg(5018,  '未知的推广上级');
                }
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
            if (!is_numeric($top_id) || !($parentUser = users::getItem($top_id))) {
                $this->showMsg(5018, '对不起，该上级推广链接不正确');
            }
        }
        if (!is_numeric($top_id) || empty($parentUser = users::getItem($top_id))) $this->showMsg(5018, '推广码错误');
        $result = [$prizeMode, $parentUser];
        //扩充会员字段
        $addtionalData = [];
        $regNeedMobile > 0 && $addtionalData['mobile'] = $mobile;
        $realNameToggle > 0 && $addtionalData['real_name'] = $realname;
        $result[] = $addtionalData;
        return $result;
    }
    /**************************************注册register end || 登录login  start*********************************************/
    /**
     * 用户登录
     * @param $username
     * @param $password
     */
    public function login()
    {
        list($username, $password) = $this->_getLogJson();
        $frm = $this->_setFrom();
        $username = strtolower($username);
        $result = users::login($username, $password, '', 2, $frm);
        if (!is_array($result)) {
            $this->showMsg(7005, $result);
        }
        $returnData = $this->_setSessionAndReturnData($result);
        //登录成功后记录其登录来源
        $GLOBALS['SESSION']['frm'] = $frm;
//        if ($result['last_ip'] != $GLOBALS['REQUEST']['client_ip'] && $result['last_ip'] != '0.0.0.0') { // 最后一次登陆IP和本次不同
//            $this->showMsg(7006,  mobileErrorCode::USER_LOGIN_OTHER_SIDE);
//        }
        $this->showMsg(0, '登陆成功', $returnData);
    }

    /**
     * 获取请求参数
     * @return array
     */
    private function _getLogJson()
    {
        list($username, $password, $verifyCode, $captcha_id) = $this->_getPost(['username', 'password', 'verifyCode', 'captcha_id']);

        if ($this->request->getGet('is_wap', 'intval') != 2) {
            if (!$this->verify2Code($verifyCode, $captcha_id)) $this->showMsg(7034, mobileErrorCode::VERIFYCODE_ERROR);
        }

        //用户名自动转小写:用户就可以随便大小写了
        $username = strtolower($username);
//        if (!$this->chkUsername($username))$this->showMsg(5001, mobileErrorCode::USERNAME_FORMAT_ERROR);
//        if (!$this->chkPwd($password))$this->showMsg(5003, mobileErrorCode::PWD_FORMAT_ERROR);
        return [$username, $password];
    }

    /**
     * 设置请求来源
     * @return int
     */
    private function _setFrom()
    {
        //1网页 2客户端 3WAP 4安卓APP 5苹果APP
        $from = $this->request->getPost('str', 'string', '');
        if (!in_array($from, [1, 2, 3, 4, 5])) {
            $this->showMsg(7005, mobileErrorCode::USER_LOGIN_ERR);
        }

        return $from;
    }

    /**
     * 设置session用户数据 以及返回数据
     * @param $data
     * @return array
     */
    private function _setSessionAndReturnData($data)
    {
        $tags=[];
        if($data['level']!=0){
            if(!empty($data['parent_tree'])){
                $p_tree=explode(',',$data['parent_tree']);
                foreach ($p_tree as $p){
                    $tags[]='pt_'.$p;
                }
            }
            if(!empty($data['top_id'])){
                $tags[]='t_'.$data['top_id'];
            }
            if(!empty($data['parent_id'])){
                $tags[]='p_'.$data['parent_id'];
            }
        }

        $returnDatas = array();
        $list = ['user_id', 'username', 'level', 'parent_id', 'top_id', 'group_id', 'last_ip', 'last_time'];
        $list2 = ['real_name', 'nick_name', 'balance', 'is_test', 'status'];
        foreach ($list as $val) {
            $GLOBALS['SESSION'][$val] = $returnDatas[$val] = isset($data[$val]) ? $data[$val] : '';
        }
        foreach ($list2 as $val2) {
            $returnDatas[$val2] = isset($data[$val2]) ? $data[$val2] : '';
        }
        $returnDatas['isset_secpwd'] = !empty($data['secpwd']) ? 1 : 0;
        $returnDatas['tags'] = !empty($tags) ? $tags : '';
        $returnDatas['pid'] = !empty($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';
        $returnDatas['sid'] = $GLOBALS['SESSION']->getYSSessionId();
        return $returnDatas;
    }
    /**************************************登录login  end || 退出登录loginout  start*********************************************/
    /**
     * 退出登录
     */
    public function loginout()
    {
        //记录日志
        userLogs::addLog(1, $GLOBALS['SESSION']['username'] . '退出登录');
        //登出信息 销毁session
        $GLOBALS['SESSION']->destroy();
        $GLOBALS['SESSION'] = array();
        $this->showMsg(0, '退出登录成功');
    }
    /**************************************退出登录loginout  end || 设置资金密码editSecPwd start*********************************************/
    /**
     * 设置资金密码
     */
    public function editSecPwd()
    {
        if (!$this->getIsPostRequest()) $this->showMsg(6002, mobileErrorCode::REQUEST_ERROR);
        list($user_id, $user) = $this->chkUser(1);
        if(!empty($user['secpwd'])){
            $no=$this->cutRedisDatabase(function()use($user_id){
                return $GLOBALS['redis']->get('resetSecPwd_'.$user_id);
            });
            if(empty($no)){
                $no=5;
            } elseif($no<=0){
                $GLOBALS['mc']->set('lock', $user_id, 1, ((strtotime(date('Y-m-d 00:00:00')) + 60 * 60 * 24) - time()));
                $this->showMsg(7036, '由于您今日已有4次输入资金密码错误，帐号已被锁定24小时，请明天再来，或联系客服解锁');
            }
            $old_secpwd = $this->request->getPost('old_secpwd', 'string', '');
            if(empty($old_secpwd)) $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
            if(generateEnPwd($old_secpwd)!=$user['secpwd']){
                $no-=1;
                $this->cutRedisDatabase(function()use($user_id,$no){
                    $time=strtotime(date('Y-m-d').' 23:59:59')-time();
                    $GLOBALS['redis']->setex('resetSecPwd_'.$user_id,$time,$no);
                });
                $this->showMsg(7036, "原资金密码错误，您有" . $no . "次重试机会");
            }
        }
        $secpassword = $this->request->getPost('secpassword', 'string', '');
        if (empty($secpassword) || !preg_match('`^\w{6,16}$`', $secpassword) || preg_match('`^[a-zA-Z]+$`', $secpassword) || preg_match('`^\d+$`', $secpassword)) $this->showMsg(5012, mobileErrorCode::SECPWD_FORMAT_ERROR);

        if (password_verify($secpassword, $user['pwd'])) $this->showMsg(5015, mobileErrorCode::SECPWD_SAME_ERROR);
        $secpwd = generateEnPwd($secpassword);
        if ($secpwd == $user['secpwd']) $this->showMsg(5013, mobileErrorCode::SECPWD_EXISTS);
        if (!users::updateItem($user_id, ['secpwd' => $secpwd])) $this->showMsg(5014, mobileErrorCode::SECPWD_UPDATE_ERROR);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
    }

    /************************************************************团队管理盈亏报表*********************************************************************/
    public function teamReportCentral()
    {
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
    }

    /*
        *团队盈亏报表
        *author asta 2016-06-11
        */
    public function teamWinReport()
    {
        list($uid, $user) = $this->chkUserAndChidId(0);
        list($start_time, $end_time) = $this->searchDate2(1, 3);
        $username = $this->request->getGet('username', 'trim', $GLOBALS['SESSION']['username']);
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        $limit = $this->request->getGet('limit', 'intval', DEFAULT_PER_PAGE);
        if ($limit <= 0) $limit = DEFAULT_PER_PAGE;
        if ($limit > DEFAULT_MAX_PAGELIMIT) $limit = DEFAULT_MAX_PAGELIMIT;
        $users = users::getItems($user['user_id']);//获取下级
        unset($users[$user['user_id']]);
        $usersNumber = users::getItemsNumber($user['user_id']);//获取数量
        list($page, $totalPages, $totals, $startPos) = $this->getPageList($usersNumber, $limit);
        //这里包含了PT数据 需询问产品是否去除或者接入
        /*************** snow 2017/10/31 增加缓存时长  start***********************************/
        $cacheKey1 = __FUNCTION__ . 'getChildReport' . $user['user_id'] . '_' . $start_time . '_' . $end_time;
        $cacheKey2 = __FUNCTION__ . 'getTeamDeposits' . $user['user_id'] . '_' . $start_time . '_' . $end_time;
        $cacheKey3 = __FUNCTION__ . 'getTeamWithdraws' . $user['user_id'] . '_' . $start_time . '_' . $end_time;
        $cacheKey4 = __FUNCTION__ . 'getChildCommission' . $user['user_id'] . '_' . $start_time . '_' . $end_time;
        $cacheKey5 = __FUNCTION__ . 'getTeamTotalGift' . $user['user_id'] . '_' . $start_time . '_' . $end_time;
        //>>定义是否要写缓存  如果结束时间不等于当天 ,且没有缓存的情况下写入缓存.如果查询结束时间为当天 ,则不缓存
//         $write_cache_flag = $end_time === date('Y-m-d 23:59:59') ? false : true;
        $write_cache_flag = false;      //>>要求暂时取消写入缓存
        /*************** snow 2017/10/31 增加缓存时长 start***********************************/

        //给每一次不同查询条件缓存一次，做到程序优化和数据库减压
        if (($teamTotalProjects = $GLOBALS['mc']->get(__CLASS__, $cacheKey1)) === false) {
            $teamTotalProjects = projects::getChildReport($user['user_id'], $start_time, $end_time);
            if ($write_cache_flag) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey1, $teamTotalProjects, CACHE_EXPIRE_LONG); //放入缓存
            }
        }

        if (($teamTotalDeposits = $GLOBALS['mc']->get(__CLASS__, $cacheKey2)) === false) {
            $teamTotalDeposits = deposits::getTeamDeposits($user['user_id'], $start_time, $end_time);
            if ($write_cache_flag) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey2, $teamTotalDeposits, CACHE_EXPIRE_LONG); //放入缓存
            }
        }


        if (($teamTotalWithdraws = $GLOBALS['mc']->get(__CLASS__, $cacheKey3)) === false) {
            $teamTotalWithdraws = withdraws::getTeamWithdraws($user['user_id'], $start_time, $end_time);
            if ($write_cache_flag) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey3, $teamTotalWithdraws, CACHE_EXPIRE_LONG); //放入缓存
            }
        }

        if (($teamTotalCommission = $GLOBALS['mc']->get(__CLASS__, $cacheKey4)) === false) {
            $teamTotalCommission = orders::getChildCommission($user, $start_time, $end_time);
            if ($write_cache_flag) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey4, $teamTotalCommission, CACHE_EXPIRE_LONG); //放入缓存
            }
        }

        if (($teamTotalGifts = $GLOBALS['mc']->get(__CLASS__, $cacheKey5)) === false) {
            $teamTotalGifts = userGifts::getTeamTotalGift($user['user_id'], $start_time, $end_time);
            if ($write_cache_flag) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey5, $teamTotalGifts, CACHE_EXPIRE_LONG); //放入缓存
            }
        }

        $pageData = $teamTotal = $teamReport = $self = array();

        //获取自己数据
        $self['username'] = $username;
        $self['level'] = $user['level'];

        $pageData['page_withdraw'] = $self['total_withdraw'] = isset($teamTotalWithdraws[$user['user_id']]['total_withdraw']) ? $teamTotalWithdraws[$user['user_id']]['total_withdraw'] : 0;
        $pageData['page_deposit'] = $self['total_deposit'] = isset($teamTotalDeposits[$user['user_id']]['total_deposit']) ? $teamTotalDeposits[$user['user_id']]['total_deposit'] : 0;
        $pageData['page_amount'] = $self['total_amount'] = isset($teamTotalProjects[$user['user_id']]['total_amount']) ? $teamTotalProjects[$user['user_id']]['total_amount'] : 0;
        $pageData['page_prize'] = $self['total_prize'] = isset($teamTotalProjects[$user['user_id']]['total_prize']) ? $teamTotalProjects[$user['user_id']]['total_prize'] : 0;
        $pageData['page_rebate'] = $self['total_rebate'] = isset($teamTotalProjects[$user['user_id']]['total_rebate']) ? $teamTotalProjects[$user['user_id']]['total_rebate'] : 0;
        $pageData['page_contribute_rebate'] = $self['total_contribute_rebate'] = isset($teamTotalProjects[$user['user_id']]['total_contribute_rebate']) ? $teamTotalProjects[$user['user_id']]['total_contribute_rebate'] : 0;
        $pageData['page_commission'] = $self['total_commission'] = isset($teamTotalCommission[$user['user_id']]['total_commission']) ? $teamTotalCommission[$user['user_id']]['total_commission'] : 0;//返佣
        $pageData['page_win'] = $self['total_win'] = round(($self['total_prize'] + $self['total_rebate'] + $self['total_commission'] - $self['total_amount']), 4);

        //获取各项总计数据
        $teamTotal['team_total_deposit'] = $teamTotalDeposits['team_total_deposit'];
        $teamTotal['team_total_withdraw'] = $teamTotalWithdraws['team_total_withdraw'];
        $teamTotal['team_total_amount'] = $self['total_amount'];
        $teamTotal['team_total_prize'] = $self['total_prize'];
        $teamTotal['team_total_rebate'] = $self['total_rebate'];
        $teamTotal['team_total_contribute_rebate'] = $self['total_contribute_rebate'];
        $teamTotal['team_total_commission'] = $teamTotalCommission['team_total_commission'];


        //把自己数据总计数据从团队数据中剔除，剩下的都是团队成员的干净数据
        if (isset($teamTotalDeposits[$user['user_id']])) {
            unset($teamTotalDeposits[$user['user_id']]);
        }
        if (isset($teamTotalWithdraws[$user['user_id']])) {
            unset($teamTotalWithdraws[$user['user_id']]);
        }
        if (isset($teamTotalProjects[$user['user_id']])) {
            unset($teamTotalProjects[$user['user_id']]);
        }
        if (isset($teamTotalCommission[$user['user_id']])) {
            unset($teamTotalCommission[$user['user_id']]);
        }
        if (isset($teamTotalGifts[$user['user_id']])) {
            unset($teamTotalGifts[$user['user_id']]);
        }
        if (isset($teamTotalDeposits['team_total_deposit'])) {
            unset($teamTotalDeposits['team_total_deposit']);
        }
        if (isset($teamTotalWithdraws['team_total_withdraw'])) {
            unset($teamTotalWithdraws['team_total_withdraw']);
        }
        foreach ($users as $user_id => $v) {
            $teamReport[$user_id]['username'] = $v['username'];
            $teamReport[$user_id]['level'] = $v['level'];

            $teamReport[$user_id]['total_deposit'] = isset($teamTotalDeposits[$user_id]['total_deposit']) ? $teamTotalDeposits[$user_id]['total_deposit'] : 0;
            $teamReport[$user_id]['total_withdraw'] = isset($teamTotalWithdraws[$user_id]['total_withdraw']) ? $teamTotalWithdraws[$user_id]['total_withdraw'] : 0;
            $teamReport[$user_id]['total_amount'] = isset($teamTotalProjects[$user_id]['total_amount']) ? $teamTotalProjects[$user_id]['total_amount'] : 0;
            $teamReport[$user_id]['total_prize'] = isset($teamTotalProjects[$user_id]['total_prize']) ? $teamTotalProjects[$user_id]['total_prize'] : 0;
            $teamReport[$user_id]['total_rebate'] = isset($teamTotalProjects[$user_id]['total_rebate']) ? $teamTotalProjects[$user_id]['total_rebate'] : 0;
            $teamReport[$user_id]['total_contribute_rebate'] = isset($teamTotalProjects[$user_id]['total_contribute_rebate']) ? $teamTotalProjects[$user_id]['total_contribute_rebate'] : 0;

            $teamReport[$user_id]['total_commission'] = isset($teamTotalCommission[$user_id]['total_commission']) ? $teamTotalCommission[$user_id]['total_commission'] : 0;

            if (isset($teamReport[$user_id])) {
                $teamReport[$user_id]['total_win'] = round(($teamReport[$user_id]['total_prize'] + $teamReport[$user_id]['total_rebate'] + $teamReport[$user_id]['total_commission'] - $teamReport[$user_id]['total_amount']), 4);
            } else {
                $teamReport[$user_id]['total_win'] = 0;
            }
            //循环加以后是最终的汇总数据
            $teamTotal['team_total_amount'] += isset($teamTotalProjects[$user_id]['total_amount']) ? $teamTotalProjects[$user_id]['total_amount'] : 0;
            $teamTotal['team_total_prize'] += isset($teamTotalProjects[$user_id]['total_prize']) ? $teamTotalProjects[$user_id]['total_prize'] : 0;
            $teamTotal['team_total_rebate'] += isset($teamTotalProjects[$user_id]['total_rebate']) ? $teamTotalProjects[$user_id]['total_rebate'] : 0;
            $teamTotal['team_total_contribute_rebate'] += isset($teamTotalProjects[$user_id]['total_contribute_rebate']) ? $teamTotalProjects[$user_id]['total_contribute_rebate'] : 0;
        }
        $teamTotal['team_total_win'] = round(($teamTotal['team_total_rebate'] + $teamTotal['team_total_prize'] + $teamTotal['team_total_commission'] - $teamTotal['team_total_amount']), 4);

        //排序字段名合法，则做排序
        if (in_array($sortKey, array('total_deposit', 'total_withdraw', 'total_amount', 'total_prize', 'total_rebate', 'total_commission', 'total_win'))) {
            $sort_arr = array(); //这个变量用于多维排序
            foreach ($teamReport as $key => $row) {
                $sort_arr[$key] = $row[$sortKey];
            }
            if ($sortDirection > 0) {
                array_multisort($sort_arr, SORT_ASC, $teamReport);
            } else {
                array_multisort($sort_arr, SORT_DESC, $teamReport);
            }
        }

        $curPageTeamReport = array_values(array_slice($teamReport, $startPos, $limit));
        //本页总计
        $notsef = [
            'page_deposit' => 0,
            'page_withdraw' => 0,
            'page_amount' => 0,
            'page_prize' => 0,
            'page_rebate' => 0,
            'page_contribute_rebate' => 0,
            'page_win' => 0,
            'page_commission' => 0
        ];
        foreach ($curPageTeamReport as $val) {
            $notsef['page_deposit'] += $val['total_deposit'];
            $notsef['page_withdraw'] += $val['total_withdraw'];
            $notsef['page_amount'] += $val['total_amount'];
            $notsef['page_prize'] += $val['total_prize'];
            $notsef['page_rebate'] += $val['total_rebate'];
            $notsef['page_contribute_rebate'] += $val['total_contribute_rebate'];//贡献佣金返点，暂时不显示前台
            $notsef['page_win'] += $val['total_win'];
            $notsef['page_commission'] += $val['total_commission'];
        }
        $pageData['page_deposit'] += $notsef['page_deposit'];
        $pageData['page_withdraw'] += $notsef['page_withdraw'];
        $pageData['page_amount'] += $notsef['page_amount'];
        $pageData['page_prize'] += $notsef['page_prize'];
        $pageData['page_rebate'] += $notsef['page_rebate'];
        $pageData['page_contribute_rebate'] += $notsef['page_contribute_rebate'];//贡献佣金返点，暂时不显示前台
        $pageData['page_win'] += $notsef['page_win'];
        $pageData['page_commission'] += $notsef['page_commission'];

        $show['page'] = $page;
        $show['totalPages'] = $totalPages;
        $show['count'] = $totals;
        $show['all_count'] = [
            'page_count' => [
                'has_self' => $pageData,
                'not_self' => $notsef,
            ],
            'total_count' => $teamTotal
        ];
        $show['self'] = !empty($self) ? $self : '';
        $show['other'] = !empty($curPageTeamReport) ? $curPageTeamReport : '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $show);
    }

    /************************************************************会员管理*********************************************************************/
    public function childList()
    {
        list($user_id, $user) = $this->chkUserAndChidId(0);
        $range = $this->request->getGet('range', 'intval', '0|1');  //0表示直接下级 1表示所有下级
        $online = $this->request->getGet('online', 'intval', '-1');
        $sort = $this->request->getGet('sort', 'intval', '0');
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        $limit = $this->request->getGet('limit', 'intval', DEFAULT_PER_PAGE);
        if ($limit <= 0) $limit = DEFAULT_PER_PAGE;
        if ($limit > DEFAULT_MAX_PAGELIMIT) $limit = DEFAULT_MAX_PAGELIMIT;
        $orderBy = [];
        if ($sort == 0) $sort = config::getConfig('user_sort', 1);
        switch ($sort) {
            case 1:
                $orderBy['reg_time'] = 1;
                break;
            case 2:
                $orderBy['reg_time'] = -1;
                break;
            case 3:
                $orderBy['level'] = -1;
                break;
            case 4:
                $orderBy['level'] = 1;
                break;
        }
        $userOnlineList = (new onlineUser())->getOnline();
        $fileds = 'u.user_id,u.balance,u.reg_time';
        $allUsers = $online == 1 ? users::getAllOnlineItemsById($userOnlineList, $user['user_id'], true, $range, $fileds) : users::getItemsByLike($user['user_id'], true, $range, array(), 8, -1, '', '', '', '', '', '', '', '', '', '', -1, [], -1, DEFAULT_PER_PAGE, $fileds);
        $totalUsers = count($allUsers);
        list($page, $totalPages, $totals, $startPos) = $this->getPageList($totalUsers, $limit);
        $orderBy = $sortKey && $sortDirection ? [$sortKey => $sortDirection] : $orderBy;
        $select = 'u.user_id,u.username,u.level,u.balance,u.last_time,u.reg_time';
        $users = $online == 1 ? users::getOnlineItemsById($userOnlineList, $user['user_id'], true, $range, array(), 8, -1, $orderBy, $startPos, $limit, $select) : users::getItemsByLike($user['user_id'], true, $range, array(), 8, -1, '', '', '', '', '', '', '', '', '', '', -1, $orderBy, $startPos, $limit, $select);
        $pageUsers = count($users);

        $showDatas['page'] = $page;
        $showDatas['totalPages'] = $totalPages;
        $showDatas['count'] = $totals;

        if (empty($allUsers)) $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $this->childListReturnNull($showDatas));
        $todayPageRegCount = $pageOnline = $totalOnline = $todayRegCount = 0;
        $balances_totals = array_column($allUsers, 'balance');
        $balances_page = array_column($users, 'balance');
        $totalBalance = array_sum($balances_totals);
        $pageBalance = array_sum($balances_page);
        foreach ($allUsers as $agent) {
            if ($agent['reg_time'] >= date('Y-m-d 00:00:00')) $todayRegCount += 1;
            if (in_array($agent['user_id'], $userOnlineList)) $totalOnline += 1;
        }

        foreach ($users as $iUserId => $value) {
            $subPrizeMode = userRebates::userPrizeMode($iUserId);
            $prize_mode = $subPrizeMode;
            $rebate = number_format(userRebates::getRebateByPrizeMode($subPrizeMode), 1);
            $users[$iUserId]['price_rebate'] = $prize_mode . '/' . $rebate;
            $users[$iUserId]['online'] = in_array($iUserId, $userOnlineList) ? 1 : 0;
            if ($users[$iUserId]['online'] == 1) $pageOnline += 1;
            if ($value['reg_time'] >= date('Y-m-d 00:00:00')) $todayPageRegCount += 1;
        }

        $showDatas['statistics'] = [
            'page' => [
                'pageUsers' => $pageUsers,
                'pageBalance' => $pageBalance,
                'todayPageRegCount' => $todayPageRegCount,
                'pageOnline' => $pageOnline,
            ],
            'totals' => [
                'totalUsers' => $totalUsers,
                'totalBalance' => $totalBalance,
                'todayRegCount' => $todayRegCount,
                'totalOnline' => $totalOnline,
            ]
        ];
        if (!empty($users[$user_id])) {
            $own = $users[$user_id];
            unset($users[$user_id]);
            array_unshift($users, $own);
        }
        $showDatas['show_datas'] = !empty($users) ? array_values($users) : '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $showDatas);
    }

    private function childListReturnNull($showDatas)
    {
        $showDatas['statistics'] = [
            'page' => [
                'pageUsers' => 0,
                'pageBalance' => 0,
                'todayPageRegCount' => 0,
                'pageOnline' => 0,
            ],
            'totals' => [
                'totalUsers' => 0,
                'totalBalance' => 0,
                'todayRegCount' => 0,
                'totalOnline' => 0,
            ]
        ];
        $showDatas['show_datas'] = '';
        return $showDatas;
    }

    /************************************************************新增会员(推广码) 新增代理**********************************************************/
    public function rebatePrizeReturn($type)
    {
        list($user_id, $user) = $this->chkUser(0);
        $tmp = userRebates::getItems($user_id);
        foreach ($tmp as $v) {
            if ($v['rebate'] == 0 && !in_array($v['property_id'], array(7, 8, 9))) {//六合彩双色球不用检查返点
                log2("用户{$GLOBALS['SESSION']['username']}的{$GLOBALS['cfg']['property'][$v['property_id']]}返点已经为0，不能开户");
                $this->showMsg(7024, "您的{$GLOBALS['cfg']['property'][$v['property_id']]}返点已经为0，不能开户");
            }
        }
        $aPrizeMode = userRebates::addSubPrizeModes($user);
        $arrs = '';
        if (!empty($aPrizeMode)) {
            if ($type == 1) {
                $arrs = array_map(function ($a, $b) {
                    return ['rebate' => $b, 'prizeMode' => $a, 'prizeShow' => $a . '/' . $a / 2];
                }, array_keys($aPrizeMode), array_values($aPrizeMode));
            } elseif ($type == 2) {
                $marketLinkInfo = marketLink::getItemsByCond("user_id = $user_id");
                $arrs = [];
                foreach ($aPrizeMode as $prize_mode => $rebate) {
                    $arr['prizeMode'] = $prize_mode;
                    $arr['prize_rebate'] = $prize_mode . '/' . $rebate;
                    if (!empty($marketLinkInfo)) {
                        foreach ($marketLinkInfo as $v) {
                            if ($v['prize_mode'] == $prize_mode) {
                                $arr['market_code'] = $v["market_code"];
                                $arr['link'] = $v["link"];
                                break;
                            }
                            $arr['market_code'] = '';
                            $arr['link'] = '';
                        }
                    } else {
                        $arr['market_code'] = '';
                        $arr['link'] = '';
                    }
                    $arrs[] = $arr;
                }
            } else {
                $this->showMsg(6002, mobileErrorCode::REQUEST_ERROR);
            }
        }
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $arrs);
    }

    /**
     * 新增代理
     */
    public function regChild()
    {
        if (!$this->getIsPostRequest()) $this->rebatePrizeReturn(1);
        list($user_id, $user) = $this->chkUser(1);
        list($username, $password, $prize_mode) = $this->getRegChid($user);
        try {
            if (!users::addUser($username, $password, $prize_mode, $user, array(), 1)) $this->showMsg(5009, mobileErrorCode::REGISTER_ERROR);
            $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
        } catch (Exception $e) {
            $this->showMsg(5010, $e->getMessage());
        }
    }

    private function getRegChid($user)
    {
        $username = strtolower($this->request->getPost('username', 'trim', ''));
        $password = $this->request->getPost('password', 'trim', '');
        $prize_mode = $this->request->getPost('prize_mode', 'trim', '');
        $aPrizeMode = userRebates::addSubPrizeModes($user);
        if (!in_array($prize_mode, array_keys($aPrizeMode))) $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
        if (!$this->chkUsername($username)) $this->showMsg(5001, mobileErrorCode::USERNAME_FORMAT_ERROR);
        if (!$this->chkPwd($password)) $this->showMsg(5003, mobileErrorCode::PWD_FORMAT_ERROR);
        return [$username, $password, $prize_mode];
    }

    /**
     * 新增会员(推广码)
     */
    public function makelink()
    {
        if (!$this->getIsPostRequest()) $this->rebatePrizeReturn(2);
        list($user_id, $user) = $this->chkUser(1);
        list($market_code, $prize_mode) = $this->getMakeLink();
        $this->_chkMlinkParames($market_code, $prize_mode, $user);
        $link = $this->getLink($user['top_id'], $market_code);
        $res = $this->_handleMlData($link, $market_code, $prize_mode, $user_id);
        if (!$res) $this->showMsg(7025, '生成链接失败,请稍后再试');
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['link' => $link]);
    }

    private function getMakeLink()
    {
        $market_code = $this->request->getPost('market_code', 'trim', '');
        $prize_mode = $this->request->getPost('prize_mode', 'trim', '');
        return [$market_code, $prize_mode];
    }

    private function _chkMlinkParames($market_code, $prize_mode, $user)
    {
        if (!preg_match('`^[0-9a-zA-Z]{3,10}$`', $market_code)) $this->showMsg(7025, '推广码格式错误');
        $aPrizeMode = userRebates::addSubPrizeModes($user);
        if (!in_array($prize_mode, array_keys($aPrizeMode))) $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
        //判断推广码是否已经存在
//        if(marketLink::getItemByCond(" prize_mode != '{$prize_mode}' AND market_code='{$market_code}'", 'prize_mode'))$this->showMsg(7025,'推广码已经存在!');
    }

    private function _handleMlData($link, $market_code, $prize_mode, $user_id)
    {
        //判断进行的操作是增加还是修改
        $res = marketLink::getItemByCond(" market_code = '{$market_code}'", 'user_id,prize_mode');
        if (!empty($res) && $res['user_id'] != $user_id && $res['prize_mode'] != $prize_mode) $this->showMsg(7025, '推广码已经存在!');
        $info = marketLink::getItemByCond(" prize_mode = '{$prize_mode}' AND user_id={$user_id}", 'prize_mode');
        $key = empty($info) ? 'ad' : 'up';
        if ($key === 'ad') {
            $data = array(
                'user_id' => $user_id,
                'market_code' => $market_code,
                'prize_mode' => $prize_mode,
                'link' => $link
            );
            $res = marketLink::addItem($data);
            return $res ? true : false;
        }
        $data = array(
            'user_id' => $user_id,
            'market_code' => $market_code,
            'prize_mode' => $prize_mode,
            'link' => $link
        );
        $info = marketLink::getItemByCond(" prize_mode = '{$prize_mode}' AND user_id={$user_id} AND market_code='{$market_code}' AND link='{$link}'", 'user_id');
        if (!empty($info)) return true;
        $res = marketLink::updateItem($data, ['user_id' => $GLOBALS['SESSION']['user_id'], 'prize_mode' => $prize_mode]);
        return $res === false ? false : true;
    }

    private function getLink($top_id, $market_code)
    {
        $url = $_SERVER['HTTP_HOST'];
        preg_match('/[\w][\w-]*\.(?:com\.cn|com|cn|co|net|org|gov|cc|biz|info)(\/|$)/isU', $url, $addurl);
        if ($addurl) $url = rtrim($addurl[0], '/');
        $domain = config::getConfig('site_main_domain', $url);
//        $res=domains::getItemByTopId($top_id);
//        $domains=array_column($res,'name');
//        if(empty($domainso))$this->showMsg(6004,'当前总代未绑定域名,请联系总代');
//        $domain = preg_match('/[\w][\w-]*\.(?:com\.cn|com|cn|co|net|org|gov|cc|biz|info)(\/|$)/isU',$_SERVER['HTTP_HOST'],$addurl);
//        if(!$domain)$this->showMsg(6004,mobileErrorCode::REQUEST_HOST_ERROR);
//        $domain=rtrim($addurl[0],'/');
//        if(!in_array($domain,$domains))$this->showMsg(6004,mobileErrorCode::REQUEST_HOST_ERROR);
        return "http://{$domain}?var=" . $market_code;//生成的链接
    }


    /**
     * author snow
     * 站内收件箱  批量删除信息
     */
    public function receiveBox()
    {
        $isRead = -1;
        $op = $this->request->getPost('op', 'trim');
        $mt_ids = $this->request->getPost('mt_ids', 'array');
        //>>判断如果进行删除操作
        if ('delete' == $op) {
            $success = 0;
            $error = 0;
            if(empty($mt_ids)) {
                $msg_id=$this->request->getPost('msg_id', 'trim');
                if(empty($msg_id))$this->showMsg(6003,mobileErrorCode::REQUEST_PARAMS_ERROR);
                if (!messages::deleteMsgTargetByMsgId($msg_id, $GLOBALS['SESSION']['user_id'], false)) {
                    ++$error;
                } else {
                    ++$success;
                }
            }else{
                foreach ($mt_ids as $v) {
                    if (!messages::deleteMsgTarget($v, false)) {
                        ++$error;
                    } else {
                        ++$success;
                    }
                }
            }
            //>>循环删除,统计删除成功与失败的条数
            $msg = ($success > 0 ? $success . '条删除成功,' : '') . ($error > 0 ? $error . '条删除失败' : '');
            $flag = $error > 0 ? 1 : 0;
            $this->showMsg($flag, $msg);
        }
        if ($op == 'readed') {
            if(empty($mt_ids)) {
                $msg_id=$this->request->getPost('msg_id', 'trim');
                if(empty($msg_id))$this->showMsg(6003,mobileErrorCode::REQUEST_PARAMS_ERROR);
                $result = messages::updateOneReadedByMsgId($msg_id, $GLOBALS['SESSION']['user_id'], 1);
            }else{
                $result = messages::updateAllHasRead($mt_ids, $GLOBALS['SESSION']['user_id'], 1);
            }
            if ($result === false) $this->showMsg(9001, mobileErrorCode::MYSQL_DML_FAIL);
            $this->showMsg(0, "{$result}条修改状态成功");
        }
        if ($op == 'not_read') {
            $result = messages::getReadCount($GLOBALS['SESSION']['user_id']);
            $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['not_read_num' => $result]);
        }

        //>>添加获取详情
        if ($op == 'msgInfo') {
            $msg_id = $this->request->getPost('msg_id', 'intval', 0);
            if ($msg_id !== 0) {
                //>>查询详情
                $msgInfo = messages::getMsgInfo($msg_id,['msg_id','title', 'create_time', 'content']);
                $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $msgInfo);
            } else {
                $this->showMsg(0, '你访问的数据不存在', []);
            }
        }
        //>>进行分页数据查询
        /***************** snow  获取正确的分页开始****************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        $limit   = $this->request->getGet('limit', 'intval', 10);
        $pageSize = $limit <= 10 ? 10 :($limit > 1000 ? 1000 : $limit );
        $messagesNumber = messages::getReceivesNumber($GLOBALS['SESSION']['user_id'], $isRead, 1);
        if ($curPage > ceil($messagesNumber / $pageSize)) {
            $this->showMsg(0, ' 我是有底线的,已经没有更多消息了', []);
        }
        $startPos = getStartOffset($curPage, $messagesNumber, $pageSize);
        /***************** snow  获取正确的分页开始****************************/
        $defaultOptions = [
            'to_user_id'    => $GLOBALS['SESSION']['user_id'],
            'has_read'      => $isRead,
            'status'        => 1,
            'start'         => $startPos,
            'amount'        => $pageSize,
            'field'         => ['msg_id', 'title', 'create_time', 'type'],
        ];
        $messages = messages::getReceivesExclude($defaultOptions);
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as &$v) {
                if ($v['type'] == 1) {
                    $v['title'] = preg_replace("/(q{1,2}\s*)?\d{5,10}/i", '', $v['title']);
                }
            }
        } else {
            $messages = [];
        }

        if (empty($messages)) {
            $this->showMsg(0, '无系统消息', []);
        }
        $this->showMsg(0, '', $messages);
    }

    public function editPwd()
    {
        list($user_id, $user) = $this->chkUser(1);
        $password = $this->request->getPost('password', 'trim');
//            $safePwd = $this->request->getPost('safe_pwd', 'trim');
        //数据正确性判断
        if (!$this->chkPwd($password)) $this->showMsg(7036, mobileErrorCode::PWD_FORMAT_ERROR);

        $enPassword = generateEnPwd($password);
        if ($enPassword == $user['secpwd']) $this->showMsg(7036, mobileErrorCode::SECPWD_SAME_ERROR);

        // 修改数据password_hash($password,PASSWORD_DEFAULT)
        $data = array(
            'pwd' => password_hash($password, PASSWORD_DEFAULT),
        );
        if ($data['pwd'] == $user['pwd']) $this->showMsg(7036, mobileErrorCode::UPDATE_USERPWD_SAME_ERROR);
        if (!users::updateItem($user_id, $data)) $this->showMsg(7036, '修改登录密码失败');
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
    }
    public function resetPwd()
    {
        list($user_id, $user) = $this->chkUser(1);
        $password = $this->request->getPost('password', 'trim');
        //数据正确性判断
        if (!$this->chkPwd($password)) $this->showMsg(7036, mobileErrorCode::PWD_FORMAT_ERROR);
        $old_pwd = $this->request->getPost('old_pwd', 'trim');
        $no=$this->cutRedisDatabase(function()use($user_id){
            return $GLOBALS['redis']->get('resetPwd_'.$user_id);
        });
        if(empty($no)){
            $no=5;
        } elseif($no<=0){
            $GLOBALS['mc']->set('lock', $user_id, 1, ((strtotime(date('Y-m-d 00:00:00')) + 60 * 60 * 24) - time()));
            $this->showMsg(7036, '由于您今日已有4次输入密码错误，帐号已被锁定24小时，请明天再来，或联系客服解锁');
        }
        if (!password_verify($old_pwd, $user['pwd'])) {
            $no-=1;
            $this->cutRedisDatabase(function()use($user_id,$no){
                $time=strtotime(date('Y-m-d').' 23:59:59')-time();
                $GLOBALS['redis']->setex('resetPwd_'.$user_id,$time,$no);
            });
            $this->showMsg(7036, "原密码错误，您有" . $no . "次重试机会");
        }
        if($old_pwd==$password)$this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
//            $safePwd = $this->request->getPost('safe_pwd', 'trim');

        $enPassword = generateEnPwd($password);
        if ($enPassword == $user['secpwd']) $this->showMsg(7036, mobileErrorCode::SECPWD_SAME_ERROR);

        // 修改数据password_hash($password,PASSWORD_DEFAULT)
        $data = array(
            'pwd' => password_hash($password, PASSWORD_DEFAULT),
        );
        if ($data['pwd'] == $user['pwd']) $this->showMsg(7036, mobileErrorCode::UPDATE_USERPWD_SAME_ERROR);
        if (!users::updateItem($user_id, $data)) $this->showMsg(7036, '修改登录密码失败');
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
    }
}

