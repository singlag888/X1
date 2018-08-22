<?php

/**
 * 基本控制器类
 * 95%的情况下需要模板，因此默认加载模板
 * 是否需要SESSION服务等在应用指定
 */
class sscappController extends controller
{
    const INIT_TEMPLATE = 1;
    const INIT_SESSION = 2;
    protected static $searchOrderTypes = [
        ['key' => 101, 'val' => '账户充值'],
        ['key' => 102, 'val' => '充值优惠'],
        ['key' => 106, 'val' => '提款不符退款'],
        ['key' => 107, 'val' => '活动礼金'],
        ['key' => 155, 'val' => '手工增余额'],
        ['key' => 154, 'val' => '接收转账'],
        ['key' => 201, 'val' => '账户提现'],
        ['key' => 202, 'val' => '手工减余额'],
        ['key' => 212, 'val' => '充入下级'],
        ['key' => 301, 'val' => '投注返点'],
        ['key' => 302, 'val' => '投注抽水'],
        ['key' => 303, 'val' => '撤单返款'],
        ['key' => 304, 'val' => '追号返款'],
        ['key' => 308, 'val' => '投注返奖'],
        ['key' => 321, 'val' => '平台理赔'],
        ['key' => 401, 'val' => '投注扣款'],
        ['key' => 414, 'val' => '追号扣款'],
        ['key' => 415, 'val' => '奖池嘉奖'],
        ['key' => 503, 'val' => '日投注返佣'],
        ['key' => 600, 'val' => '抽奖扣款'],
        ['key' => 701, 'val' => '电子返水'],
    ];
    protected static $orderTypes = [
        101 => '账户充值',
        102 => '充值优惠',
        106 => '提款不符退款',
        107 => '活动礼金',
        155 => '系统增余额',
        154 => '接收转账',
        161 => '从波音转出',
        162 => '从MW转出',
        201 => '账户提现',
        202 => '系统减余额',
        211 => '转入波音',
        212 => '充入下级',
        213 => '转入MW',
        301 => '投注返点',
        302 => '投注抽水',
        303 => '撤单返款',
        304 => '追号返款',
        308 => '投注返奖',
        321 => '平台理赔',
        401 => '投注扣款',
        414 => '追号扣款',
        415 => '奖池嘉奖',
        503 => '日投注返佣',
        600 => '抽奖扣款',
        701 => '电子返水',
    ];
    /*private static $orderTypes = [
        101 => '账户充值',
        102 => '充值优惠',
        //103 => '首冲送',
        106 => '提款不符退款',
        107 => '活动礼金',
        155 => '手工增余额',
        154 => '接收转账',
        201 => '账户提现',
        202 => '手工减余额',
        212 => '充入下级',
        301 => '投注返点',
        302 => '投注抽水',
        303 => '撤单返款',
        304 => '追号返款',
        308 => '投注返奖',
        321 => '平台理赔',
        401 => '投注扣款',
        414 => '追号扣款',
        415 => '奖池嘉奖',
        //501 => '日流水返佣',
        //502 => '日亏损返佣',
        503 => '日投注返佣',
        600 => '抽奖扣款',
        //601 => '注册送',
        //602 => '签到送',
        //603 => '日盈利送',
        //604 => '日亏损送',
        701 => '电子返水',
    ];*/
    protected static $reqJson = null;
    protected static $PublicImgCdn = null;
    protected static $mobileDomain = null;
    protected static $domain = null;
    // 不用进行检查的方法
    static protected $dutyFree = array(
        'default' => array(
            'index',
            'welcome',
            'verifyCode',
            'lobby',
            'activityList',
            'activityDetail',  //>>活动详情 todo  需要配置权限
            'getLastVersion',
            'showDatas',
            'hotShow',
            'getAllLottery',
            'getOpen',
            'totalPrize',
            'showHot',
            'newOpen',
            'noticeList',
            'getCurrentIssue',
            'getAppToken',
            'getAppAlert',
            'verifyCode',
            'getType',
            'lotteryList',
            'newLobby',
            'getVersion',
            'testJpush',
        ),
        'user' => array(
            'login',
            'loginout',
            'register',
            'regconf',
        ),
        'fin' => array(
            'icbcInterface',
            'icbcInterfaceNew',
            'autoWithdraw',
            'autoWithdrawBack',
            'receivePayResult',
        ),
        'help' => array(
            'aboutme',
            'doc',
            'upload',
            'getService',
            'useHelp',
        ),
        'pay' => array(
            'backPay',
            'createOrder',
            'inStep'
        ),
    );

    // 不用进行检查的方法
    static protected $ACLFree = array(
        'default' => array(
            'index',
            'welcome',
            'verifyCode',
            'promoList',
            'promoDetail',
            'bannerList',
            'noticeList',
            'noticeDetail',
            'activityDetail',  //>>活动详情 todo  需要配置权限
            'getLastVersion',
            'showDatas',
            'getAppToken',
        ),
        'user' => array(
            'getCurrentUser',
            'changePassword',
            'bindCardDetail',
            'applyWithdraw',
            'withdrawList',
            'resetPwd',
        ),
        'game' => array(
            'lotteryList',
            'methodList',
            'getIssueInfo',
            'play',
            'packageList',
            'packageDetail',
            'cancelPackage',
            'cancelTrace',
            'traceList',
            'traceDetail',
            'orderList',
            'lotteriesHistory',
            'lotteriesRebate',
        ),
        'pay' => array(
            'backPay',
            'createOrder',
            'inStep'
        ),
        'fin' => array(
            'receivePayResult',
        ),

        'help' => array(
            'useHelp',
        )
    );
    //post不需要数据加密的
    static protected $NoEncode = array(
        'fin' => array(
            'receivePayResult',
        ),
        'pay' => array(
            'backPay',
            'inStep'
        ),
        'user' => array(
            'regconf',
            'teamReportCentral',
        ),
    );

    /**
     * @param $filename 文件名
     * @param string $dir logs的下级目录名
     * @param array $datas 打印数据,必须是数组
     * @param string $type none 不打印 post:打印post get:打印get input:打印输入流 all:打印前面三种
     * @return bool
     */
    protected function sprintLog($filename, $datas = [], $dir = 'sprint', $type = 'NONE')
    {
        if (!is_string($filename) || !is_string($dir)) return false;
        $dir = str_replace(' ', '', $dir);
        $path = './logs/' . $dir . '/' . date('Ymd') . '/';
        $filename = str_replace(' ', '', $filename);
        $filename = $path . $filename . '.txt';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $REQUEST_METHOD = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '无法获取';
        $REQUEST_URI = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '无法获取';
        $QUERY_STRING = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '无法获取';
        $CONTENT_TYPE = !empty($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '无法获取';
        $HTTP_COOKIE = !empty($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : '无法获取';
        $REMOTE_ADDR = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '无法获取';
        $postdata = '============================start==============================' . PHP_EOL;
        $postdata .= 'Print Position : ' . CONTROLLER . ' / ' . ACTION . ' : ' . __LINE__ . '行   ';
        $postdata .= 'Print Time : ' . date('Y-m-d H:i:s') . PHP_EOL;
        $postdata .= 'REQUEST_METHOD : ' . $REQUEST_METHOD . PHP_EOL;
        $postdata .= 'REQUEST_URI    : ' . $REQUEST_URI . PHP_EOL;
        $postdata .= 'QUERY_STRING   : ' . $QUERY_STRING . PHP_EOL;
        $postdata .= 'CONTENT_TYPE   : ' . $CONTENT_TYPE . PHP_EOL;
        $postdata .= 'HTTP_COOKIE    : ' . $HTTP_COOKIE . PHP_EOL;
        $postdata .= 'REMOTE_ADDR    : ' . $REMOTE_ADDR . PHP_EOL;
        switch (strtoupper($type)) {
            case 'ALL':
                $postdata .= 'POST_DATAS     : ' . json_encode($this->request->getPostAsArray()) . PHP_EOL;
                $postdata .= 'GET_DATAS      : ' . json_encode($this->request->getGetAsArray()) . PHP_EOL;
                $postdata .= 'IMPUT_DATAS    : ' . file_get_contents("php://input") . PHP_EOL;
                break;
            case 'POST':
                $postdata .= 'POST_DATAS     : ' . json_encode($this->request->getPostAsArray()) . PHP_EOL;
                break;
            case 'GET':
                $postdata .= 'GET_DATAS      : ' . json_encode($this->request->getGetAsArray()) . PHP_EOL;
                break;
            case 'INPUT':
                $postdata .= 'IMPUT_DATAS    : ' . file_get_contents("php://input") . PHP_EOL;
                break;
        }

        if (!empty($datas) && (is_array($datas) || is_object($datas))) {
            $postdata .= 'DEVELOPER_NEED : ' . '<<<show' . PHP_EOL;
            foreach ($datas as $key => $val) {

                if (!is_array($val) && !is_object($val)) $postdata .= '    ' . $key . ' : ' . $val . PHP_EOL;
                else $postdata .= '    ' . $key . ' : ' . json_encode($val) . PHP_EOL;
            }
            $postdata .= 'show;' . PHP_EOL;
        }
        $postdata .= '===========================end===============================' . PHP_EOL;

        return file_put_contents($filename, $postdata, FILE_APPEND) ? true : false;
    }

    public function init($init = 0)
    {
        if (USE_ENCODE && $this->getIsPostRequest()) {
            $is_wap=$this->request->getGet('is_wap', 'intval', 0);
            if ( $is_wap== 0||$is_wap == 3) { //todo:wap端开发阶段关闭wap的数据加解密
                if (!isset(self::$NoEncode[CONTROLLER]) || (self::$NoEncode[CONTROLLER] != '*' && !in_array(ACTION, self::$NoEncode[CONTROLLER]))) {
                    $decrypted_arr = [];
                    if (empty($datas = $this->request->getPost('datas', 'string', ''))) $this->showMsg(404, "数据为空,禁止访问");
                    if (CONTROLLER == 'default' && ACTION == 'getAppToken') {
                        $pi_key = openssl_pkey_get_private(file_get_contents(PROJECT_PATH . 'safe/rsa_private_key.pem'));//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
                        $decrypted = $this->_privateDecrypt($datas, $pi_key);
                        if (empty($decrypted) || empty($decrypted_arr = json_decode($decrypted, true))) $this->showMsg(404, '数据解析失败,请重试!');
                    } else {
                        if ( $is_wap== 0){
                            $apiToken = isset($_SERVER['HTTP_APITOKEN']) && !empty($_SERVER["HTTP_APITOKEN"]) ? $_SERVER["HTTP_APITOKEN"] : '';
                        }else{
                            $apiToken = $this->request->getGet('apiToken','string','');
                        }
                        if (empty($apiToken)) $this->showMsg(7032, 'apiToken错误');
                        $key = $this->cutRedisDatabase(function () use ($apiToken) {
                            return $GLOBALS['redis']->get('appToken_' . $apiToken);
                        });
                        if (empty($key)) $this->showMsg(7032, 'apiToken错误');
                        $decrypted = $this->_aseDecrypt($datas, $key);
                        if (empty($decrypted) || empty($decrypted_arr = json_decode($decrypted, true))) $this->showMsg(404, '数据解析失败,请重试!');
                    }
                    $this->request->setAppPost('*', $decrypted_arr);
                }
            }
        }
        $this->_getimgMobileInfo();
        return true;
    }

    private function chk_session_key(&$session_id)
    {
        if (!empty($session_id)) {
            if (strlen($session_id) != 40 || !preg_match('/^[a-z,0-9]{40}$/', $session_id)) $this->showMsg(7001, 'token错误');
            $tmp_session_id = substr($session_id, 0, 32);
            $verify = sprintf('%08x', crc32($_SERVER['HTTP_USER_AGENT'] . PROJECT . $tmp_session_id));
            if ($verify !== substr($session_id, 32)) {
                $this->sprintLog('token_fails', [
                    'http_user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'project' => PROJECT,
                    'tmp_session_id' => $tmp_session_id,
                    'sid' => $session_id,
                    'verify' => $verify,
                ]);
                $this->showMsg(7001, 'token验证失败');
            }
        }
    }

    private function getSetSessionToken()
    {
        if (!(CONTROLLER == 'user' && ACTION == 'login')) {
            if ($this->request->getGet('is_wap', 'intval', 0) == 0) {
                $sid = isset($_SERVER['HTTP_SID']) && !empty($_SERVER["HTTP_SID"]) ? $_SERVER["HTTP_SID"] : '';
            } else {
                $sid = $this->request->getGet('sid', 'string', '');
            }

            $this->chk_session_key($sid);
            if (!empty($sid)) {
                $_COOKIE[PROJECT . "SESSID"] = $sid;
                $_POST = $this->request->getPostAsArray();
                $_GET = $this->request->getGetAsArray();
                $GLOBALS['REQUEST']->init();
            }
        }
    }

    /**
     * 实现权限验证，
     * 能从某个存储中找到user_id对应的权限
     */
    public function validate($controller = CONTROLLER, $action = ACTION)
    {
        $this->chk_maintenance();
        $GLOBALS['templateDirectory'] = 'default';
        $this->getSetSessionToken();
        //默认都加载session
        start_sessions(10080);//默认保存7天
        //是否存在请求源
        if (isset($_SERVER["HTTP_ORIGIN"])) {
            header('Access-Control-Allow-Origin:' . $_SERVER["HTTP_ORIGIN"]);
            header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers:Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With');
            header('Access-Control-Max-Age:86400');
            header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Hiddenin:true');
        }
        if (isset($GLOBALS['SESSION']['user_id']) && !empty($GLOBALS['SESSION']['user_id'])) {
            //检查是否被踢出
            $needCheckOne = config::getConfig('limit_one_ip_online');
            $newSessInfo = array();
            if ($needCheckOne && $GLOBALS['SESSION']->isEdgeOut($GLOBALS['SESSION']['user_id'], 0, $newSessInfo)) {
                $GLOBALS['SESSION']->destroy();
                $GLOBALS['SESSION'] = array();
                $this->showMsg(7006, mobileErrorCode::USER_LOGIN_OTHER_SIDE);
            }
        }
        //未登录免检产品
        if (!empty(self::$dutyFree[$controller])) {
            if (in_array($action, self::$dutyFree[$controller]) || in_array('*', self::$dutyFree[$controller])) {
                return true;
            }
        }

        //初步判断，没有登录的就跳至登录界面
        if (empty($GLOBALS['SESSION']['user_id'])) {
            $this->showMsg(7001, mobileErrorCode::USER_NOT_LOGIN);
        }
        $menu = array();
        //免检权限产品
        if (!empty(self::$ACLFree[$controller]) && (in_array($action, self::$ACLFree[$controller]) || in_array('*', self::$ACLFree[$controller]))) {
            $menu['is_log'] = 0;
        } //查询是否具有权限
        elseif (!userGroups::verifyPriv(array($controller, $action), 0, $menu)) {
            userLogs::addLog(0, "App:访问失败：无权限,访问地址{$controller}/{$action}");
            $this->sprintLog('no_permission', ['group_id'=>$GLOBALS['SESSION']['group_id'],'show'=>"App:访问失败：无权限,用户id:{$GLOBALS['SESSION']['user_id']},ip地址:{$GLOBALS['REQUEST']['client_ip']},访问地址{$controller}/{$action}", 'controller'=>$controller, 'action'=>$action]);
            $this->showMsg(7002, mobileErrorCode::USER_NOT_HAVE_ACL);
        }
        if (!$menu) $this->showMsg(7003, mobileErrorCode::MENU_NOT_FOUND);
        //记录日志
        if ($menu['is_log'] == 1) userLogs::addLog(1, '访问成功');
        return true;
    }

    //获取CND域名 默认第一个是最快的
    protected function getimgCdnUrl($is_wap=true)
    {
        if (file_exists(ROOT_PATH . 'cdn.xml')) {
            $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
            if($is_wap)$imgCdnUrl = (string)$xml->mobile;
            else $imgCdnUrl = (string)$xml->web;
        } else {
            $imgCdnUrl = config::getConfig('mobile_site_main_domain');
        }
        return $imgCdnUrl;
    }

    public function isLogined($needUser = 0)
    {
        if (isset($GLOBALS['SESSION']['user_id']) && $GLOBALS['SESSION']['user_id'] > 0) {
            return $needUser == 0 ? true : (empty($user = users::getItem($GLOBALS['SESSION']['user_id'])) ? false : $user);
        }
        return false;
    }

    private function _privateDecrypt($encrypted, $pi_key)
    {
        $crypto = '';
        foreach (str_split(base64_decode($encrypted), 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $pi_key);
            $crypto .= $decryptData;
        }
        return $crypto;
    }

    private function _aseEncrypt($data, $key)
    {
        $plaintext = json_encode($data);//原始数据转为json字符串的待加密数据
        $ciphertext_raw = openssl_encrypt($plaintext, "AES-128-ECB", $key, $options = OPENSSL_RAW_DATA);//进行aes-ecb数据加密
        return base64_encode($ciphertext_raw);//base64编码
    }

    private function _aseDecrypt($ciphertext, $key)
    {
        return openssl_decrypt(base64_decode($ciphertext), "AES-128-ECB", $key, $options = OPENSSL_RAW_DATA);//解密加密的数据
    }

    protected function showMsg($errno, $errstr, $data = '')
    {
        $is_wap = $this->request->getGet('is_wap', 'intval', 0);
        if ($is_wap == 1) {
            $result = array(
                'errno' => $errno,
                'errstr' => $errstr,
            );
            if (!empty($data)) {
                $result['data'] = $data;
            }
            die(base64_encode(json_encode($result)));
        } elseif ($is_wap == 2) {
            die(json_encode(['errno' => $errno, 'errstr' => $errstr, 'data' => $data]));
        }
        $result = array(
            'errno' => (int)$errno,
            'errstr' => (string)$errstr,
        );
        if (!empty($data)) {
            if (CONTROLLER == 'default' && ACTION == 'getAppToken') {
                $token = !empty($data['token']) ? $data['token'] : '';
            } else {
                $is_wap=$this->request->getGet('is_wap','intval',0);
                if($is_wap==3){
                    $token = $this->request->getGet('apiToken','string','');
                }else{
                    $token = isset($_SERVER['HTTP_APITOKEN']) && !empty($_SERVER["HTTP_APITOKEN"]) ? $_SERVER["HTTP_APITOKEN"] : '';
                }

            }
            if (empty($token)) $result = ['errno' => 7032, 'errstr' => '缺少apiToken'];
            else {
                $result = $this->cutRedisDatabase(function () use ($token, $data, $result) {
                    $key = $GLOBALS['redis']->get('appToken_' . $token);
                    if (empty($key)) $result = ['errno' => 7032, 'errstr' => '获取apiToken失败'];
                    else {
                        $result['data'] = $this->_aseEncrypt($data, $key);
                    }
                    return $result;
                });
            }
        }
        die(json_encode($result));
    }

    protected function getIsPostRequest()
    {
        return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'POST');
    }

    protected function chkRuquestType($type)
    {
        if ($type === 1) {
            if (!$this->getIsPostRequest()) $this->showMsg(6002, mobileErrorCode::REQUEST_ERROR);
        } elseif ($type === 0) {
            if ($this->getIsPostRequest()) $this->showMsg(6002, mobileErrorCode::REQUEST_ERROR);
        } else {
            $type = $this->getIsPostRequest() ? 1 : 0;
        }
        return $type;
    }

    protected function chkUserAndChidId($type = '')
    {
        $type = $this->chkRuquestType($type);
        $userId = $user_id = $type == 1 ? $this->request->getPost('user_id', 'intval', 0) : $this->request->getGet('user_id', 'intval', 0);
        if (!$user = users::getItem($userId)) $this->showMsg(7012, mobileErrorCode::USER_INVALID);
        if ($userId != $GLOBALS['SESSION']['user_id']) {
            if (!in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))) $this->showMsg(7018, mobileErrorCode::CHILDREN_NOT_FOUND);
        }
        return [$userId, $user];
    }

    protected function chkUser($type = '')
    {
        $type = $this->chkRuquestType($type);
        $userId = $this->chkUserId($type);
        if (!$user = users::getItem($userId)) $this->showMsg(7012, "非法请求，该用户不存在或已被冻结");
        if ($user['username'] != $GLOBALS['SESSION']['username']) $this->showMsg(7012, mobileErrorCode::USER_INVALID);
        return [$userId, $user];
    }

    protected function chkUserId($method = 1)
    {
        $user_id = $method == 1 ? $this->request->getPost('user_id', 'intval', 0) : $this->request->getGet('user_id', 'intval', 0);
        if (empty($user_id)) $this->showMsg(7012, '非法请求!缺少必要参数');
        if ($user_id != $GLOBALS['SESSION']['user_id']) $this->showMsg(7012, mobileErrorCode::USER_INVALID);
        return (int)$user_id;
    }

    /**
     * 日期查找范围
     * @param $start_time
     * @param $end_time
     * @param $number
     * @param $type 1=>day, 2=>week, 3=>month
     * @param $delay_day 延迟天数
     */
    protected function searchDate2($number = 1, $type = 2, $delay_day = 0)
    {
        $date = $this->request->getGet('date', 'trim', '');
        if (!empty($date)) {
            if ($date == date('Y-m-d', strtotime($date))) {
                if (strtotime($date) < time()) {
                    $start_time = $date . ' 00:00:00';
                    $end_time = $date . ' 23:59:59';
                } else {
                    $date = date("Y-m-d");
                    $start_time = $date . ' 00:00:00';
                    $end_time = $date . ' 23:59:59';
                }
                return [$start_time, $end_time];
            }
            $this->showMsg(7022, '查询日期格式错误');
        } else {
            $type = $type == 1 ? ' days' : ($type == 2 ? ' weeks' : ' months');
            $curTime = date('Y-m-d', strtotime('-' . $delay_day . ' days'));
            $minDate = date('Y-m-d', strtotime('-' . $number . $type . '+1 days -' . $delay_day . ' days'));
            $start_date = $this->request->getGet('start_date', 'trim', '');
            $end_date = $this->request->getGet('end_date', 'trim', '');
            $start_time = strtotime($start_date);
            $end_time = strtotime($end_date);

            if (!empty($start_date) && !empty($end_date)) {
                if ($start_time > $end_time) {
                    list($start_time, $end_time, $start_date, $end_date) = [$end_time, $start_time, $end_date, $start_date];
                }
                if ($start_date != date('Y-m-d', $start_time) || $end_date != date('Y-m-d', $end_time)) $this->showMsg(7022, '查询日期格式错误');
                if ($start_time < strtotime($minDate)) $start_date = $minDate;
                if ($end_time > strtotime($curTime)) $end_date = $curTime;
            } elseif (!empty($start_date)) {
                if ($start_time < strtotime($minDate)) $start_date = $minDate;
                elseif ($start_time > strtotime($curTime)) $start_date = $curTime;
                $end_date = $curTime;
            } elseif (!empty($end_date)) {
                if ($end_time < strtotime($minDate)) $end_date = $minDate;
                elseif ($end_time > strtotime($curTime)) $end_date = $curTime;
                $start_date = $minDate;
            } else {
                $start_date = $minDate;
                $end_date = $curTime;
            }
            $start_time = $start_date . ' 00:00:00';
            $end_time = $end_date . ' 23:59:59';
            return [$start_time, $end_time];
        }
    }

    protected function getPageTool($count)
    {
        $limit = $this->request->getGet('limit', 'intval', DEFAULT_PER_PAGE);
        if ($limit <= 0) $limit = DEFAULT_PER_PAGE;
        if ($limit > DEFAULT_MAX_PAGELIMIT) $limit = DEFAULT_MAX_PAGELIMIT;
        $res = $this->getPageList($count, $limit);
        $res[] = $limit;
        return $res;
    }

    protected function getPageList($totalRecordsNum, $perPage = DEFAULT_PER_PAGE)
    {
        if ($perPage <= 0) $perPage = DEFAULT_PER_PAGE;
        if ($perPage > DEFAULT_MAX_PAGELIMIT) $perPage = DEFAULT_MAX_PAGELIMIT;
        $page = Request::getInstance()->getGet('page', 'intval', 1);
        $totalPages = ceil($totalRecordsNum / $perPage);
        if ($totalPages == 0) $totalPages = 1;
        $totals = $totalRecordsNum;
        if ($page < 1) $page = 1;
        $page = $page > $totalPages ? $totalPages : $page;
        $startPos = ($page - 1) * $perPage;
        return [$page, $totalPages, $totals, $startPos];
    }

    /**
     * 获取手机端相关信息
     */

    private function _getimgMobileInfo()
    {
        //>>从cdn.xml 文件取数据
        if (file_exists(ROOT_PATH . 'cdn.xml')) {
            $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
            self::$PublicImgCdn = (string)$xml->mobile;
        } else {
            self::$PublicImgCdn = config::getConfig('mobile_site_main_domain');
        }

        //>>获取手机端域名
        $mobileDomain = config::getConfig('mobile_site_main_domain');
        if (!preg_match('/http:\/\//', $mobileDomain)) {
            $mobileDomain = 'http://' . $mobileDomain;  //>>如果不是以http://开头
        }
        //>>获取pc端域名
        $domain = config::getConfig('site_main_domain');
        if (!preg_match('/http:\/\//', $domain)) {
            $domain = 'http://' . $domain;  //>>如果不是以http://开头
        }
        self::$mobileDomain = $mobileDomain;
        self::$domain = $domain;
        unset($mobileDomain);
        unset($domain);
    }

    /**
     * author snow
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

    /**
     * 判断是否是在app库 ,如果不在切换到app库.
     * @return bool
     */
    protected function selectRedisApp()
    {
        return $GLOBALS['redis']->getDbId() !== REDIS_DB_APP ? $GLOBALS['redis']->select(REDIS_DB_APP) : true;
    }

    /**
     * 记录报错信息
     * @param string $logName
     */
    public function logdump($logName = '支付')
    {
        static $count = 0;
        $argsNum = func_num_args();
        $args = func_get_args();
        $str = '';
        if (extension_loaded('xdebug')) {
            $str .= "" . date('Y-m-d H:i:s') . " BEGIN DEBUG($count) at " . xdebug_call_class() . "::" . xdebug_call_function() . "() [" . " " . xdebug_call_line() . "]\n";
        } else {
            $call_stack = debug_backtrace();
            $str .= date('Y-m-d H:i:s') . " Debug (no xdebug)  " . $call_stack [0] ['file'] . ":" . $call_stack [0] ['line'] . "\n";
        }

        for ($i = 0; $i < $argsNum; ++$i) {
            if (is_string($args[$i])) {
                $str .= $args[$i] . "\n";
            } else {
                $str .= var_export($args[$i], true) . "\n";
            }
        }

        $count++;
        $str .= "**************END DEBUG($count)**************";
        $log_file = LOG_PATH . 'payLog/' . $logName . '.log';
        $this->log_write_file($log_file, $str . "\n", FILE_APPEND);
    }

    /**
     * 创建错误日志文件夹与写入错误文件
     * @param $file
     * @param $content
     * @param int $flag
     */
    private function log_write_file($file, $content, $flag = 0)
    {
        $dirname = LOG_PATH . 'payLog';
        if (file_exists($dirname) === false) {
            if (@mkdir($dirname, 0777, true) === false) {

            }
        }
        if ($flag === FILE_APPEND) {
            file_put_contents($file, $content . "\n", LOCK_EX | FILE_APPEND);
        } else {
            file_put_contents($file, $content . "\n", LOCK_EX);
        }
    }

    /**
     * 计算当前时间到下一段开始时间的时间差 返回毫秒
     * @param int $lottery_id 彩种id
     * @return int
     */
    protected function calTime($lottery_id)
    {
        $lotteryInfo = lottery::getItem($lottery_id);
        $lotteryNum = count($lotteryInfo['settings']);
        $time = date('H:i:s');
        $kTime = 0;
        if ($lotteryNum == 1) {//只有一个时间段
            $startTime = $lotteryInfo['settings'][0]['start_time'];//开始时间
            $endTime = $lotteryInfo['settings'][0]['end_time'];//结束时间
            if (($endTime < '12:00:00') && ($startTime < $endTime) || ($startTime == $endTime))//这些情况没有休市时间段
            {
                $tmp = true;
            } else {
                $tmp = false;
            }

            if (!$tmp) {//开始时间与结束时间相等表明没有休市限制
                if ($endTime > $startTime) {
                    if ($time > $endTime)//跨天
                    {
                        $kTime = strtotime($startTime) + 86400 - strtotime($time);
                    }
                    if ($time < $startTime) {
                        $kTime = strtotime($startTime) - strtotime($time);
                    }
                } else {
                    if ($time < $startTime && $time > $endTime) {
                        $kTime = strtotime($startTime) - strtotime($time);
                    }
                }
            }
        } else {
            $arr = $lotteryInfo['settings'];
            usort($arr, function ($v, $v1) {//对时间段数组进行排序--主要处理后台不按照顺序设置休市时间
                if ($v['start_time'] < $v1['start_time']) {
                    return -1;
                } else {
                    return 1;
                }
            });
            $num = $lotteryNum;
            foreach ($arr as $key => $val) {
                if ($key + 1 < $num) {//最后一段时间之前的时间段 这些时间段没有跨天时间
                    if ($time > $val['end_time'] && $time < $arr[$key + 1]['start_time']) {
                        $kTime = strtotime($arr[$key + 1]['start_time']) - strtotime($time);
                        break;
                    }
                } else {//最后一段时间
                    if (($val['end_time'] > $arr[0]['start_time']) && ($val['end_time'] < '12:00:00') || ($val['end_time'] == $arr[0]['start_time'])) {
                        $tmp = true;//这些情况没有休市
                    } else {
                        $tmp = false;
                    }
                    if (!$tmp) {
                        if ($val['end_time'] > $arr[0]['start_time']) {
                            if ($time > $val['end_time']) {//表明跨天
                                $kTime = strtotime($arr[0]['start_time']) + 86400 - strtotime($time);
                            }
                            if ($time < $arr['0']['start_time']) {
                                $kTime = strtotime($arr['0']['start_time']) - strtotime($time);
                            }
                        } else {
                            //没有跨天
                            if ($time > $val['end_time'] && $time < $arr[0]['start_time']) {
                                $kTime = strtotime($arr[0]['start_time']) - strtotime($time);
                            }
                        }
                    }
                }
            }
        }
        return $kTime * 1000;
    }

    /**
     * redis 加锁
     * @param string $name 锁标识
     * @param int $timeout 循环获取锁的等待超时时间,在此时间内会一直尝试获取锁直到超时,为0表示失败后直接返回不等待
     * @param int $expire 当前锁的最大生存时间(秒),必须大于0,如果超过生存时间锁仍未被释放,则系统会自动强制释放
     * @param int $waitInterval 获取锁失败后挂起再试的时间间隔(微秒)  1秒=1000毫秒=1000000微秒
     * @return bool
     */
    public function redisLock($name, $timeout = 0, $expire = 15, $waitInterval = 100000)
    {
        $now = time();
        $timeoutAt = $now + $timeout;//获取锁失败时的等待超时时刻
        $expireAt = $now + $expire;//锁的最大生存时刻
        $redisKey = "Lock:{$name}";
        $res = $GLOBALS['redis']->setnx($redisKey, $expireAt);//设定锁
        if (!$res) {
            return true;//表示获取到了锁 不在进行锁的设定
        }
        while (true) {

            if ($res) {
                $GLOBALS['redis']->expire($redisKey, $expireAt);//设定key的失效时间
                $this->_lockedNames[$name] = $expireAt;//将锁标识房间数组里
                return false;//表示设置锁
            }
            $ttl = $GLOBALS['redis']->ttl($redisKey);//获取key的剩余过期时间(秒)
            //ttl小于0表示key没有设置生存时间（key是不会不存在的，因为前面setnx会自动创建）
            //如果出现这种状况，那就是进程的某个实例setnx成功后 crash 导致紧跟着的expire没有被调用 这时可以直接设置expire并把锁纳为己用
            if ($ttl < 0) {
                $GLOBALS['redis']->set($redisKey, $expireAt);//设定key的失效时间
                $this->_lockedNames[$name] = $expireAt;//将锁标识房间数组里
                return false;
            }
            //如果没有设置锁的失败时间 或者已经超过最大等待时间,结束循环
            if ($timeout <= 0 || $timeoutAt < microtime(true)) {
                break;
            }
            //隔$waitInterval后继续请求
            usleep($waitInterval);
        }

    }

    /**
     * redis解锁
     * @param string $name 锁标识
     * @return bool
     */
    public function redisUnlock($name)
    {
        if ($this->isLocking($name)) {
            //删除锁
            if ($GLOBALS['redis']->deleteKey("Lock:$name")) {
                unset($this->_lockedNames[$name]);
                return true;
            }
        }
        return false;
    }

    /**
     * 获取当前锁状态
     * @param string $name
     * @return bool
     */
    public function isLocking($name)
    {
        if (isset($this->_lockedNames['redis'][$name])) {
            return $this->_lockedNames['redis'][$name] = $GLOBALS['redis']->get("Lock:$name");
        }
        return false;
    }

    protected function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
        if (!$this->mkdirs(dirname($dir), $mode)) return FALSE;
        return @mkdir($dir, $mode);
    }

    protected function chk_maintenance()
    {
        $res = $this->cutRedisDatabase(function () {
            return $GLOBALS['redis']->hget('appset', 'systemMaintenance');
        });
        if (empty($res)) {
            $model = M('appSystemMaintenance');
            if (!empty($data = $model->find())) {
                $this->cutRedisDatabase(function () use ($data) {
                    $GLOBALS['redis']->hset('appset', 'systemMaintenance', serialize($data));
                });
            }
        } else {
            $data = unserialize($res);
        }
        if (isset($data['is_show']) && $data['is_show'] == 1) {
            $this->showMsg(6009, mobileErrorCode::SYSTEM_MAINTENANCE, [
                'is_show' => 1,
                'info' => isset($data['info']) ? $data['info'] : '',
                'show_time' => isset($data['show_time']) ? $data['show_time'] : '',
                'ser_addr' => config::getConfig('service_url', ''),
            ]);
        }
    }

    protected function requestLog($type=1,$filename='requestLog', $dir = 'sprint')
    {
        $path = './logs/' . $dir . '/' . date('Ymd') . '/';
        $filename = $path . $filename . '.txt';
        if (!file_exists($path)) mkdir($path, 0777, true);
        $REQUEST_METHOD = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $postdata = '============================start==============================' . PHP_EOL;
        $postdata .= 'Print Position : ' . CONTROLLER . ' / ' . ACTION . PHP_EOL;
        $postdata .= 'Print Time     : ' . time() . PHP_EOL;
        $postdata .= 'REQUEST_METHOD : ' . $REQUEST_METHOD . PHP_EOL;
        $postdata .= 'METHOD : ' . $type;
        $postdata .= '===========================end===============================' . PHP_EOL;

        return file_put_contents($filename, $postdata, FILE_APPEND) ? true : false;
    }
}

