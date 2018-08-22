<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 波音接口实现思路：
 * 需要事先在后台“配置管理”里面建好一个默认代理帐号，用来接纳直接客户，然后对于代理及其下的客户，应在波音代理后台预先建立相应波音代理帐号（代理可自由设定用户名），其下的客户对应在波音的代理帐户下开户；
 *
 * 1.发送数据均以utf8编码，post方式请求；
 * 2.由于波音限制用户名长度及不能有下划线，所以不能直接用7v+用户名形式，而在数据库中新增一字段，采用7v+user_id+字母x的形式，这样不至于用户名超长；
 * 3.密码统一为7vtech+4位用户id，对用户而言透明无需关注；
 * 4.key值计算比较BT:A+B+C，其中B=strtolower(md5('LWIN999'.'7vhy01'.'2c4URy4'.'20121223'))，其中LWIN999是固定值，2c4URy4表示keyB，对于不同接口其值不同，日期是utc-4，即当前时间-12，A和C分别表示前后插入一定干扰字符
 *
 ********************************** 接口列表 *************************************
 * open ---- 创建会员
 * login    ---- 登录
 * logout   ---- 登出
 * checkBalance($user) ---- 查询余额
 * transfer2EA($transfer_id, $user, $amount) ---- 转入EA
 * transfer2Custom($transfer_id, $user, $amount)  ---- 从EA转出
 *********************************************************************************

一、登录
发过去的数据：
<?xml version="1.0"?>
<request action="Login">
    <element>
        <website>LWIN999</properties>
        <username>7sv3</username>
        <uppername>7svbase</username>
        <username>123def</username>
    </element>
</request>
验证登录请求

待完善

 */

class BBIN
{
    
    /**
     * 官方接口地址
     * @var <type> 
     */
    private $interfaceURL;

    /**
     * 固定值，站点标识
     * @var string
     */
    private $website = 'LWIN999';

    /**
     * 固定值，加密token
     * @var array
     */
    private $keyB = array(
        'CreateMember'  => 'qYI0s9qmp',
        'Login'  => 'jVT56kw',
        'Logout'  => '2c4URy4',
        'CheckUsrBalance'  => 'F7rhvnElc',
        'Transfer'  => '53IkD3JMon',
        );

    private $bbinIP;  //bbin服务器ip

    static $CLoginURL    = 'https://ag.jinyazhou88.com/app/index.jsp';    //代理进行登录
    static $CProfileURL  = 'https://ag.jinyazhou88.com/app/user/user_gen.php';    //代理基本资料
    static $CAgentGainLossURL  = 'https://ag.jinyazhou88.com/app/report/report_unmem4.php?BigBoss=&level=sa&fast_search=no&act=1&ReportType=1&FastReport=on&GameKind=0&report_gametype=0&report_jackpot=0&report_ptype=S_ALL&pay_type=&report_playtype=S_ALL&report_gamecode=0&report_kind=1&Currency=all';    //输赢值

    protected $wget = NULL;

    const DIGITAL_PADDING_NUM = 6;  //生成element_id中的用户id用几位填充，一般6位即足够，能表示百万了，即便超过也不会出错的，只是长度增加了一位而已
    static $successCodes = array(
        '11000' => '转帐成功',
        '10003' => '转帐失败',
        '11000' => '重复转帐',
        '10002' => '余额不足',
        '10004' => 'key不得为空',
        '10005' => '额度检查错误',
        '10006' => '提款需为正整数或浮点',

    );

    public function __construct()
    {
        if (RUN_ENV < 3) {
            $this->interfaceURL = 'http://888.jinyazhou88.com/app/WebService/XML/display.php';
            $this->bbinIP = '103.17.24.234';
        }
        else { //正式环境
            $this->interfaceURL = 'http://888.jinyazhou88.com/app/WebService/XML/display.php';
            $this->bbinIP = '103.17.24.234';
        }

        $this->wget = new wget();
        $this->wget->setHttpVersion('1.0')
            ->setConnection('Close')    // Keep-Alive 将非常慢
            ->setReferer('')
            ->setCookie('')
            //->setPostData($postData)  //用时再具体设置
            ->setContentType('application/x-www-form-urlencoded')
            ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)');
    }

    /**
     * 非官方接口：登录c级代理帐号以长期保持在线状态
     */
    public function loginC()
    {
        $bbin_c_account = config::getConfig('bbin_c_account');
        $parts = explode(':', $bbin_c_account);
        if (count($parts) != 2) {
            return -400;    //后台未设置好代理登录帐户
        }
        //langx=zh-cn&login=login&username=ddaili01&passwd=a123456
        $postData = "langx=zh-cn&login=login&username={$parts[0]}&passwd={$parts[1]}";
        $this->wget->setPostData($postData);
//logdump($postData);
        $content = $this->wget->getContents('CURL', 'POST', self::$CLoginURL);
        $cookie = $this->wget->getResponseHeader("Set-Cookie");
//logdump($cookie);
        //一个月须改一次密码 否则登录会出现 <script>document.location.replace('https://ag.live199.net/app/chg_passwd_30.php' );</script>
        if (strlen($content) < 300 && preg_match('`chg_passwd_30\.php`Uims', $content)) {
            return -1;
        }

        //'Set-Cookie' => 'sid=n5cb90d1z2datsk5z4otv0z4wc1czd2; path=/; domain=ag.live199.net',
        if (!preg_match('`(sid=\w+);`Uims', $cookie, $match)) {
            return false;
        }

        //保存会话
        $sessInfo = "langx=zh-cn;" . $match[1];
        config::updateValueByKey('bbin_sess_info', $sessInfo);

        return true;
    }

    /**
     * 非官方接口：显示c级代理自身可用额度
     */
    public function showCBalance()
    {
        $configs = config::getConfigs(array('bbin_c_account', 'bbin_sess_info'));
        $parts = explode(':', $configs['bbin_c_account']);
        if (count($parts) != 2) {
            return -400;    //后台未设置好代理登录帐户
        }
        if (empty($configs['bbin_sess_info'])) {
            return -401;    //后台未设置好代理登录状态
        }

        //https://ag.live199.net/app/user/user.php?level=6&upperid=3374941&HallID=5&disable=N&sort=3&orderby=desc&page=&loginname=
        $this->wget->setCookie($configs['bbin_sess_info']);
        $content = trim($this->wget->getContents('SOCKET', 'GET', self::$CProfileURL));
        if (!$content) {
            return -1;  //网络超时
        }
        if (!BBIN::isOnline($content)) {
            return -404;    //会话已到期，需要重新登录
        }
//logdump($content);
        //分析内容
        $pattern = '`BB额度</div>\s*</td>\s*<td.*>\s*<table.*>\s*<tr><td><div.*>(\d[\d,.]*)</div>.*会员BB额度:(\d[\d,.]*)</div>`Uims';
        if (!preg_match($pattern, $content, $match)) {
            return -128;
        }

        $result = array(
            'agent_balance' => str_replace(',', '', $match[1]),
            'total_user_balance' => str_replace(',', '', $match[2]),
        );
//logdump($result);
        return $result;
    }

    /**
     * 非官方接口：显示各代理商当天输赢
     */
    public function getAgentGainLoss($startDate, $endDate)
    {
        $configs = config::getConfigs(array('bbin_c_account', 'bbin_sess_info'));
        $parts = explode(':', $configs['bbin_c_account']);
        if (count($parts) != 2) {
            return -400;    //后台未设置好代理登录帐户
        }
        if (empty($configs['bbin_sess_info'])) {
            return -401;    //后台未设置好代理登录状态
        }
        if (!preg_match('`^201\d-\d{2}-\d{2}$`', $startDate) || !strtotime($startDate) || !preg_match('`^201\d-\d{2}-\d{2}$`', $endDate) || !strtotime($endDate)) {
            return -402;    //日期非法
        }
        if ($startDate > $endDate) {
            return -403;    //开始日期不能大于结束日期
        }

        //&date_start=2013-05-01&date_end=2013-05-01
        //https://ag.jinyazhou88.com/app/report/report_unmem4.php?BigBoss=&level=sa&fast_search=no&act=1&ReportType=1&FastReport=on&GameKind=0&report_gametype=0&report_jackpot=0&report_ptype=S_ALL&pay_type=&report_playtype=S_ALL&report_gamecode=0&report_kind=1&Currency=all
        $this->wget->setCookie($configs['bbin_sess_info']);
        $content = trim($this->wget->getContents('SOCKET', 'GET', self::$CAgentGainLossURL . "&date_start={$startDate}&date_end={$endDate}"));
        if (!$content) {
            return -1;  //网络超时
        }
//logdump($content);
        if (!BBIN::isOnline($content)) {
            return -401;    //会话已到期，需要重新登录
        }

        //分析内容
        $pattern = '`<table[^>]*id="myTableAll"[^>]*>.*<tbody[^>]*>(.*)</tbody>`Uims';
        if (!preg_match($pattern, $content, $match)) {
            return -128;    //页面已改版
        }
        $pattern = '`<tr[^>]*>.*<td[^>]*>(.+)</td>.*<td[^>]*>(\d+)</td>.*<td[^>]*>(.+)</td>.*<td[^>]*>(.+)</td>.*<td[^>]*>(.+)</td>.*</tr>`Uims';
        preg_match_all($pattern, $match[1], $matches);
//logdump($matches);
        if (count($matches[1]) == 0) {
            return -128;    //页面已改版
        }
        $result = array();
        foreach ($matches[1] as $k => $v) {
            $userId = BBIN::decodeUserId(trim(strip_tags($matches[1][$k])));
            $betTimes = str_replace(',', '', trim(strip_tags($matches[2][$k])));
            $water = str_replace(',', '', trim(strip_tags($matches[3][$k])));
            $amount = str_replace(',', '', trim(strip_tags($matches[4][$k])));
            $realWater = str_replace(',', '', trim(strip_tags($matches[5][$k])));
            if ($userId > 0) {
                $result[] = array(
                    'top_id' => $userId,
                    'bet_times' => $betTimes,
                    'water' => $water,
                    'real_water' => $realWater,
                    'amount' => $amount,
                );
            }
        }
//logdump($result);
        return $result;
    }

    /**
     * 开户 用户名4~12数字字母 密码6~12数字字母
     * 如需要指定特定密码 传$user['bbin_pwd']进来
     * @param string $user 用户信息
     * @return type
     */
    public function open($user)
    {
        if (!is_array($user)) {
            return -101;    //参数不对
        }

        $action = 'CreateMember';
        //总代也可以自助激活一个普通帐号，密码也是x0pwd+ID，使其自己可以玩真人游戏，只是用户名规则为"ag"+username，以示和普通用户区别
        if ($user['parent_id'] == 0) {
            $bbin_username = "ag" . $user['username'];
        }
        else {
            $bbin_username = "x0" . $user['username'];
        }
        if (!$parentBBINUserName = BBIN::getParentBBINUserName($user)) {
            return -103;    //查找上级代理出错，或者代理未开通波音帐号！
        }
        //如果指定了密码，按指定密码保存，否则按默认规则
        if ($user['bbin_pwd']) {
            $bbin_pwd = $user['bbin_pwd'];
        }
        else {
            $bbin_pwd = "x0pwd" . $user['user_id'];
        }

        $key = "12345" . strtolower(md5($this->website . $bbin_username . $this->keyB[$action] . date('Ymd', strtotime('-12 hours')))) . '12';
        $postData = '<?xml version="1.0"?>' .
                '<request action="' . $action . '">'.
                '<element>'.
                '<website>' . $this->website .'</website>'.
                '<username>' . $bbin_username . '</username>'.
                '<uppername>' . $parentBBINUserName . '</uppername>'.
                '<password>' . $bbin_pwd . '</password>'.
                '<key>' . $key . '</key>'.
                '</element>'.
                '</request>';
        $this->wget->setPostData($postData);
        /* <?xml version="1.0"?><request action="CreateMember"><element><website>LWIN999</website><username>7v0003</username><uppername>d7vdl01</uppername><password>7vtech0003</password><key>1234568ba4fc3253ef7319fa484786cc6834812</key></element></request> */
        log2("{$bbin_username}波音开户 $action POST ".$this->interfaceURL."\n".$postData);
        $content = trim($this->wget->getContents('CURL', 'POST', $this->interfaceURL));
        log2("{$bbin_username}波音开户响应 responseStatus=".$this->wget->getResponseHttpCode()."\n".$content);
        if ($this->wget->getResponseHttpCode() != 200) {
            return -200;    //网络通信错误
        }

        /* 成功时返回XML <?xml version='1.0' encoding='UTF-8' standalone='yes'?><Data><Record><Code>21100</Code><Message>The User data is add success.</Message></Record></Data> */
        $array = dom2array($content);
        if (!isset($array['data'][0]['record'][0]['code'][0]['#text'][0])) {
            log2("{$user['bbin_username']} 波音开户 没有返回预期内容");
            return -201;    //返回的数据格式错误
        }

        //如果不是21100为出错，将提前返回
        if ($array['data'][0]['record'][0]['code'][0]['#text'][0] != '21100') {
            return -$array['data'][0]['record'][0]['code'][0]['#text'][0];  //一律为负数
        }

        //保存bbin用户名 对于非默认密码才保存
        $data = array(
            'bbin_username' => $bbin_username,
        );
        //一般不需要传指定密码，默认即可
        if ($user['bbin_pwd']) {
            $data['bbin_pwd'] = authcode($user['bbin_pwd'], 'ENCODE', "{$user['user_id']}{$user['username']}");
        }
        if (!users::updateItem($user['user_id'], $data)) {
            return -301;
        }

        return 0;
    }

    static public function isOnline($responseBody)
    {
        if (preg_match('`请重新登入,谢谢`Uims', $responseBody)) {
            config::updateValueByKey('bbin_sess_info', '');
            return false;
        }

        return true;
    }

    public function login($user)
    {
        if (!is_array($user) || !$user['bbin_username'] || strlen($user['bbin_username']) > 12) {
            return -101;    //参数不对
        }

        $action = 'Login';
        if (!$parentBBINUserName = BBIN::getParentBBINUserName($user)) {
            return -103;    //查找上级代理出错，或者代理未开通波音帐号！
        }
        if (!$user['bbin_pwd']) {
            $bbin_pwd = "x0pwd" . $user['user_id'];
        }
        else {
            if (!$origin = authcode($user['bbin_pwd'], 'DECODE', "{$user['user_id']}{$user['username']}")) {
                return -150;
            }
            $bbin_pwd = $origin;
        }
        $key = "12345678" . strtolower(md5($this->website . $user['bbin_username'] . $this->keyB[$action] . date('Ymd', strtotime('-12 hours')))) . '1';
        $postData = '<?xml version="1.0"?>' .
                '<request action="' . $action . '">'.
                '<element>'.
                '<website>' . $this->website . '</website>'.
                '<username>' . $user['bbin_username']. '</username>'.
                '<uppername>' . $parentBBINUserName . '</uppername>'.
                '<password>' . $bbin_pwd . '</password>'.
                '<lang>zh-cn</lang>'.
                '<page_site>live</page_site>'.
                '<key>' . $key . '</key>'.
                '</element>'.
                '</request>';
        $this->wget->setPostData($postData);
        log2("{$user['bbin_username']} 登录波音 $action POST ".$this->interfaceURL."\n".$postData);
        $content = trim($this->wget->getContents('CURL', 'POST', $this->interfaceURL));
        log2("{$user['bbin_username']} 登录波音响应 responseStatus=".$this->wget->getResponseHttpCode()."\n".$content);
        if ($this->wget->getResponseHttpCode() != 200) {
            return -200;    //网络通信错误
        }

        //成功时返回html <html><head><title></title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body onload='document.post_form.submit();'><form id='post_form' name='post_form' method=post action='http://777.jinyazhou88.com' ><input name=uid  type='hidden' value='lc688eadz3firfniz9h2jc2z6uke7z012'><input name=langx type='hidden' value='zh-cn'></form></body></html>
        //如果短时间内频繁登入则提示 <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'><script language=javascript>alert ('請稍後 0.5 分鐘後，再重新登入');</script>
        //如果发送的xml不合法，会直接报错：<br /><b>Fatal error</b>:  Call to a member function attributes() on a non-object in <b>/home/gbt/member/www/app/WebService/presenter/Presenter.php</b> on line <b>14</b><br />
        if (!preg_match('`<html><head>.*<input\s*name=uid\s*type=\'hidden\'\s*value=\'\w{20,}\'>.*</body></html>`Uims', $content)) {
            $array = dom2array($content);
            if (!isset($array['data'][0]['record'][0]['code'][0]['#text'][0])) {
                log2("{$user['bbin_username']} 登出波音 没有返回预期内容");
                return -201;    //返回的数据格式错误
            }

            //23000表示体系错误
            return -$array['data'][0]['record'][0]['code'][0]['#text'][0];
        }

        return $content;
    }

    //这个好像用不到
    public function logout($user)
    {
        if (!is_array($user) || !$user['bbin_username'] || strlen($user['bbin_username']) > 12) {
            return -101;    //参数不对
        }

        $action = 'Logout';
        $key = "1234" . strtolower(md5($this->website . $user['bbin_username'] . $this->keyB[$action] . date('Ymd', strtotime('-12 hours')))) . '123456';
        $postData = '<?xml version="1.0"?>' .
                '<request action="' . $action . '">'.
                '<element>'.
                '<website>LWIN999</website>'.
                '<username>' . $user['bbin_username']. '</username>'.
                '<key>' . $key . '</key>'.
                '</element>'.
                '</request>';
        $this->wget->setPostData($postData);
        log2("{$user['bbin_username']} 登出波音 $action POST ".$this->interfaceURL."\n".$postData);
        $content = trim($this->wget->getContents('CURL', 'POST', $this->interfaceURL));
        log2("{$user['bbin_username']} 登出波音响应 responseStatus=".$this->wget->getResponseHttpCode()."\n".$content);
        if ($this->wget->getResponseHttpCode() != 200) {
            return -200;    //网络通信错误
        }

        /* 成功时返回XML <?xml version='1.0' encoding='UTF-8' standalone='yes'?><Data><Record><Code>22001</Code><Message>User is logout.</Message></Record></Data> */
        $array = dom2array($content);
        if (!isset($array['data'][0]['record'][0]['code'][0]['#text'][0])) {
            log2("{$user['bbin_username']} 登出波音 没有返回预期内容");
            return -201;    //返回的数据格式错误
        }

        //成功返回22001，其他错误号均为出错
        if ($array['data'][0]['record'][0]['code'][0]['#text'][0] != '22001') {
            return -$array['data'][0]['record'][0]['code'][0]['#text'][0];  //一律为负数
        }

        return 0;
    }

    //检查用户在波音的余额 已通过
    public function checkBalance($user)
    {
        if (!is_array($user) || !$user['bbin_username'] || strlen($user['bbin_username']) > 12) {
            return -101;    //参数不对
        }

        $action = 'CheckUsrBalance';
        if (!$parentBBINUserName = BBIN::getParentBBINUserName($user)) {
            return -103;    //查找上级代理出错，或者代理未开通波音帐号！
        }
        $key = BBIN::rndStr(9) . strtolower(md5($this->website . $user['bbin_username'] . $this->keyB[$action] . date('Ymd', strtotime('-12 hours')))) . BBIN::rndStr(6);
        $postData = '<?xml version="1.0"?>' .
                '<request action="' . $action . '">'.
                '<element>'.
                '<website>' . $this->website . '</website>'.
                '<username>' . $user['bbin_username']. '</username>'.
                '<uppername>' . $parentBBINUserName . '</uppername>'.
                '<key>' . $key . '</key>'.
                '</element>'.
                '</request>';
        $this->wget->setPostData($postData);
        log2("{$user['bbin_username']} 波音余额 $action POST ".$this->interfaceURL."\n".$postData);
        $content = trim($this->wget->getContents('CURL', 'POST', $this->interfaceURL));
        log2("{$user['bbin_username']} 波音余额响应 responseStatus=".$this->wget->getResponseHttpCode()."\n".$content);
        if ($this->wget->getResponseHttpCode() != 200) {
            return -200;    //网络通信错误
        }

        /* 成功时返回XML <?xml version='1.0' encoding='UTF-8' standalone='yes'?><Data Page="1" PageLimit="500 " TotalNumber="1" TotalPage="1"><Record><LoginName>7vhy01</LoginName><Currency>RMB</Currency><Balance>1000</Balance><TotalBalance>1000</TotalBalance></Record></Data> */
        $array = dom2array($content);
        if (!isset($array['data'][0]['record'][0]['balance'][0]['#text'][0])) {
            log2("{$user['bbin_username']}波音余额 没有返回预期内容");
            if (!empty($array['data'][0]['record'][0]['code'][0]['#text'][0])) {
                return -$array['data'][0]['record'][0]['code'][0]['#text'][0];  //一律为负数
            }
            else {
                return -201;    //返回的数据格式错误
            }
        }

        //成功时返回余额
        return $array['data'][0]['record'][0]['balance'][0]['#text'][0];
    }

    //商户->波音
    public function transferIn($transfer_id, $user, $amount, $pre_balance, $balance)
    {
        if ($transfer_id <= 0 || $pre_balance <= 0 || $balance < 0 || !is_array($user) || !$user['bbin_username'] || strlen($user['bbin_username']) > 12 || !is_int($amount) || $amount <= 0) {
            return -101;    //参数不对
        }

        if (round($pre_balance - $amount, 4) != $balance) {
            return -102;    //参数不对
        }

        $action = 'Transfer';
        if (!$parentBBINUserName = BBIN::getParentBBINUserName($user)) {
            return -103;    //查找上级代理出错，或者代理未开通波音帐号！
        }
        $remitno = '211' . str_pad($transfer_id, 6, '0', STR_PAD_LEFT);
        $key = '12' . strtolower(md5($this->website . $user['bbin_username'] . $remitno . $this->keyB[$action] . date('Ymd', strtotime('-12 hours')))) . '1234567';
        $postData = '<?xml version="1.0"?>' .
                '<request action="' . $action . '">'.
                '<element>'.
                '<website>' . $this->website . '</website>'.
                '<username>' . $user['bbin_username']. '</username>'.
                '<uppername>' . $parentBBINUserName . '</uppername>'.
                '<remitno>' . $remitno . '</remitno>'.
                '<action>IN</action>'.
                '<remit>' . $amount . '</remit>'.
                '<newcredit>' . $balance . '</newcredit>'.
                '<credit>' . $pre_balance . '</credit>'.
                '<key>' . $key . '</key>'.
                '</element>'.
                '</request>';
        $this->wget->setPostData($postData);
        log2("{$user['bbin_username']} 转入波音 $action POST ".$this->interfaceURL."\n".$postData);
        $content = trim($this->wget->getContents('CURL', 'POST', $this->interfaceURL));
        log2("{$user['bbin_username']} 转入波音响应 responseStatus=".$this->wget->getResponseHttpCode()."\n".$content);
        if ($this->wget->getResponseHttpCode() != 200) {
            return -200;    //网络通信错误
        }

        /* 成功时返回XML <?xml version='1.0' encoding='UTF-8' standalone='yes'?><Data><Record><Code>44001</Code><Message>The url get error</Message></Record></Data> */
        $array = dom2array($content);
        if (!isset($array['data'][0]['record'][0]['code'][0]['#text'][0])) {
            log2("{$user['bbin_username']} 转入波音 没有返回预期内容");
            return -201;    //返回的数据格式错误
        }

        //成功返回11100，其他错误号均为出错
        if ($array['data'][0]['record'][0]['code'][0]['#text'][0] != '11100') {
            return -$array['data'][0]['record'][0]['code'][0]['#text'][0];  //一律为负数
        }

        return 0;
    }

    //商户<-波音
    public function transferOut($transfer_id, $user, $amount, $pre_balance, $balance)
    {
        if ($transfer_id <= 0 || $pre_balance < 0 || $balance < 0 || !is_array($user) || !$user['bbin_username'] || strlen($user['bbin_username']) > 12 || !is_int($amount) || $amount <= 0) {
            return -101;    //参数不对
        }
        
        if (round($pre_balance + $amount, 4) != $balance) {
            return -102;    //参数不对
        }

        $action = 'Transfer';
        if (!$parentBBINUserName = BBIN::getParentBBINUserName($user)) {
            return -103;    //查找上级代理出错，或者代理未开通波音帐号！
        }
        $remitno = '161' . str_pad($transfer_id, 6, '0', STR_PAD_LEFT);
        $key = '12' . strtolower(md5($this->website . $user['bbin_username'] . $remitno . $this->keyB[$action] . date('Ymd', strtotime('-12 hours')))) . '1234567';
        $postData = '<?xml version="1.0"?>' .
                '<request action="' . $action . '">'.
                '<element>'.
                '<website>' . $this->website . '</website>'.
                '<username>' . $user['bbin_username']. '</username>'.
                '<uppername>' . $parentBBINUserName . '</uppername>'.
                '<remitno>' . $remitno . '</remitno>'.
                '<action>OUT</action>'.
                '<remit>' . $amount . '</remit>'.
                '<newcredit>' . $balance . '</newcredit>'.
                '<credit>' . $pre_balance . '</credit>'.
                '<key>' . $key . '</key>'.
                '</element>'.
                '</request>';
        $this->wget->setPostData($postData);
        log2("{$user['bbin_username']} 转出波音 $action POST ".$this->interfaceURL."\n".$postData);
        $content = trim($this->wget->getContents('CURL', 'POST', $this->interfaceURL));
        log2("{$user['bbin_username']} 转出波音响应 responseStatus=".$this->wget->getResponseHttpCode()."\n".$content);
        if ($this->wget->getResponseHttpCode() != 200) {
            return -200;    //网络通信错误
        }

        /* 成功时返回XML <?xml version='1.0' encoding='UTF-8' standalone='yes'?><Data><Record><Code>44001</Code><Message>The url get error</Message></Record></Data> */
        $array = dom2array($content);
        if (!isset($array['data'][0]['record'][0]['code'][0]['#text'][0])) {
            log2("{$user['bbin_username']} 转入波音 没有返回预期内容");
            return -201;    //返回的数据格式错误
        }

        //成功返回11100，其他错误号均为出错
        if ($array['data'][0]['record'][0]['code'][0]['#text'][0] != '11100') {
            return -$array['data'][0]['record'][0]['code'][0]['#text'][0];  //一律为负数
        }

        return 0;
    }

    //按指定长度生成随机字符
    static public function rndStr($length)
    {
        if ($length <= 0) {
            return '';
        }

        $haystack = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $haystack{rand(0, 61)};
        }

        return $result;
    }

    //把bbin代理名还原成总代的user_id
    static public function decodeUserId($bbin_agent_username)
    {
        if (!preg_match('`^djyz(\d+)x$`', $bbin_agent_username, $match)) {
            return 0;
        }

        return intval($match[1]);
    }

    //总代下的所有下级均映射为bbin代理商下的直接下级
    static public function getParentBBINUserName($user)
    {
        //总代也开个普通号，因代理商是不能玩游戏的，其上级是以总代为命名规则的代理商号，这并非总代自身的bbin帐号
        if ($user['user_id'] == $user['top_id']) {
            //$parentBBINUserName = $user['bbin_username'];
            //$parentBBINUserName = 'cjyz88';
            $parentBBINUserName = "djyz" . str_pad($user['user_id'], 4, '0', STR_PAD_LEFT) . 'x';
        }
        else {
            if (!$topUser = users::getItem($user['top_id'])) {
                return '';    //上级帐户不存在或已被禁用
            }
            $parentBBINUserName = "djyz" . str_pad($topUser['user_id'], 4, '0', STR_PAD_LEFT) . 'x';
        }

        return $parentBBINUserName;
    }

}
?>