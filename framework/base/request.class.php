<?php

/**
 * 最终决定采用静态类，别在这里耗时间了
 *
 * @package Apf_Request
 */
class request implements ArrayAccess
{

    //static $info = array();'' => 'XMLHttpRequest',
    public $info = array('protocol' => NULL, 'domain' => NULL, 'main_domain' => NULL, 'sub_domain' => NULL, 'client_ip' => NULL, 'proxy_ip' => NULL, 'server_ip' => NULL,
        'uri' => NULL, 'url' => NULL, 'ua' => NULL, 'referer' => NULL, 'query_string' => NULL, 'is_post' => NULL, 'browser_type' => NULL, 'is_ajax' => NULL);
    static private $_get;
    static private $_post;
    static private $_cookie;
    static private $instance = NULL;

    function __construct()
    {
        // 如果要取消$_GET等变量应在引导程序里面做，类里面尽量独立，不要实现一些运行时参数
        // 不推荐实例化，应采用Request::getInstance()方式
        $this->init();
    }

    static public function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->info[] = $value;
        }
        else {
            $this->info[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->info[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->info[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->info[$offset]) ? $this->info[$offset] : null;
    }

    public function init()
    {
        self::$_get = $_GET;
        self::$_post = $_POST;
        self::$_cookie = $_COOKIE;

        // 去掉自动加的引号
        if (get_magic_quotes_gpc()) {
            //self::$_get = array_walk_recursive(self::$_get, array($this, 'addslashes_deep'));
            //self::$_post = array_walk_recursive(self::$_post, array($this, 'addslashes_deep'));
            array_walk_recursive(self::$_get, create_function('&$value, $key', '$value = stripslashes($value);'));
            array_walk_recursive(self::$_post, create_function('&$value, $key', '$value = stripslashes($value);'));
        }

        $this->info = $this->getRequestInfo();
    }

    public function getDomain()
    {
        if (!isset($this->info['domain'])) {
            $this->info = array_merge($this->info, self::parseMinorDomain(parseMainDomain()));
        }

        return $this->info['domain'];
    }

    public function getMainDomain()
    {
        if (!isset($this->info['main_domain'])) {
            $this->info = array_merge($this->info, self::parseMinorDomain(parseMainDomain()));
        }

        return $this->info['main_domain'];
    }

    public function getSubDomain()
    {
        if (!isset($this->info['sub_domain'])) {
            $this->info = array_merge($this->info, self::parseMinorDomain(parseMainDomain()));
        }

        return $this->info['sub_domain'];
    }

    // 完整路径，带查询串
    public function getRequestURI()
    {
        if (!isset($this->info['uri'])) {
            $this->info['uri'] = $_SERVER["REQUEST_URI"];
        }

        return $this->info['uri'];
    }

    // 不带查询串，前面不带/
    public function getURL()
    {
        if (!isset($this->info['url'])) {
            $this->info['url'] = substr($_SERVER["SCRIPT_NAME"], 1);
        }

        return $this->info['url'];
    }

    public function getQueryString()
    {
        if (!isset($this->info['query_string'])) {
            $request_info['uri'] = $_SERVER['REQUEST_URI'];
            if (strpos($request_info['uri'], '?') !== false) {
                $request_info['query_string'] = substr(strstr($request_info['uri'], '?'), 1);
                $request_info['url'] = @parse_url(substr($request_info['uri'], 0, strpos($request_info['uri'], '?')));
            }
            else {
                $request_info['query_string'] = '';
                $request_info['url'] = @parse_url($request_info['uri']);
            }

            $request_info['url'] = substr(@$request_info['url']['path'], 1);
            if ($request_info['url'] === '/' || $request_info['url'] == '') {
                $request_info['url'] = 'index.php';
            }

            // 将进行了目录级 rewrite 的数据放在 query_string 串里
            if (empty($request_info['query_string']) === true && strpos($request_info['url'], '/') > 0) {
                $request_info['query_string'] = basename($request_info['url']);
                if ($request_info['url'] === $request_info['query_string']) {
                    $request_info['query_string'] = '';
                }
            }
            $this->info = array_merge($this->info, $request_info);
        }

        return $this->info['query_string'];
    }

    public function getServerIp()
    {
        if (!isset($this->info['server_ip'])) {
            $this->info['server_ip'] = self::_getServerIp();
        }

        return $this->info['server_ip'];
    }

    public function getClientIp()
    {
        if (!isset($this->info['client_ip'])) {
            $this->info['client_ip'] = self::_getClientIp();
        }

        return $this->info['client_ip'];
    }

    public function getReferer()
    {
        if (!isset($this->info['referer'])) {
            $this->info['referer'] = $_SERVER['HTTP_REFERER'];
        }

        return $this->info['referer'];
    }

    public function getUserAgent()
    {
        if (!isset($this->info['ua'])) {
            $this->info['ua'] = $_SERVER['HTTP_USER_AGENT'];
        }

        return $this->info['ua'];
    }

    public function getRequestInfo()
    {
        static $request_info = NULL;

        if ($request_info === NULL) {
            $request_info = self::parseMinorDomain(self::parseMainDomain());
            $request_info['client_ip'] = self::getClientIp();
            $request_info['server_ip'] = self::getServerIp();
            //proxy_ip不一定是REMOTE_ADDR，如果有交换层的情况
            if (isset($_SERVER['HTTP_X_FORWARDED_PATH'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_PATH']);
                //取第一个IP是最前端IP
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $request_info['proxy_ip'] = $ip;
                        break;
                    }
                }
            }
            elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $request_info['proxy_ip'] = $_SERVER['REMOTE_ADDR'];
            }
            else {
                $request_info['proxy_ip'] = '0.0.0.0';
            }

            while (isset($_SERVER['REQUEST_URI']{1}) === true) {
                if ($_SERVER['REQUEST_URI']{0} === '/' && $_SERVER['REQUEST_URI']{1} === '/') {
                    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 1);
                }
                else {
                    break;
                }
            }

            $request_info['uri'] = $_SERVER['REQUEST_URI'];
            if (strpos($request_info['uri'], '?') !== false) {
                $request_info['query_string'] = substr(strstr($request_info['uri'], '?'), 1);
                $request_info['url'] = @parse_url(substr($request_info['uri'], 0, strpos($request_info['uri'], '?')));
            }
            else {
                $request_info['query_string'] = '';
                $request_info['url'] = @parse_url($request_info['uri']);
            }

            $request_info['url'] = substr(@$request_info['url']['path'], 1);
            if ($request_info['url'] === '/' || $request_info['url'] == '') {
                $request_info['url'] = 'index.php';
            }

            // 将进行了目录级 rewrite 的数据放在 query_string 串里
            if (empty($request_info['query_string']) === true && strpos($request_info['url'], '/') > 0) {
                $request_info['query_string'] = basename($request_info['url']);
                if ($request_info['url'] === $request_info['query_string']) {
                    $request_info['query_string'] = '';
                }
            }

            /* 变量初始化 */
            if (isset($_SERVER['HTTP_USER_AGENT']) === true) {
                $request_info['ua'] = $_SERVER['HTTP_USER_AGENT'];
            }
            else {
                $request_info['ua'] = $_SERVER['HTTP_USER_AGENT'] = '';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $request_info['is_post'] = true;
            }
            else {
                $request_info['is_post'] = false;
            }

            //130331增加 是否是ajax请求 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $request_info['is_ajax'] = true;
            }
            else {
                $request_info['is_ajax'] = false;
            }
        }

        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) === true) {
            $_SERVER['HTTP_ACCEPT_ENCODING'] = strtolower($_SERVER['HTTP_ACCEPT_ENCODING']);
        }
        else {
            $_SERVER['HTTP_ACCEPT_ENCODING'] = '';
        }

        if (isset($_SERVER['HTTP_REFERER']) === true) {
            $request_info['referer'] = $_SERVER['HTTP_REFERER'];
        }
        else {
            $request_info['referer'] = $_SERVER['HTTP_REFERER'] = '';
        }

        if (isset($_SERVER['HTTP_ACCEPT']) === false) {
            $_SERVER['HTTP_ACCEPT'] = '';
        }

        $request_info['browser_type'] = self::getBrowserType();
        $request_info['protocol'] = self::getProtocol();    //协议

        return $request_info;
    }

    private function parseMainDomain()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $domain = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        elseif (isset($_SERVER['HTTP_HOST'])) {
            $domain = $_SERVER['HTTP_HOST'];
        }
        elseif (isset($_SERVER['SERVER_NAME'])) {
            $domain = $_SERVER['SERVER_NAME'];
        }
        else {
            $domain = false;
        }

        /* 去掉后面的端口信息 */
        if (strpos($domain, ':') !== false) {
            $domain = substr($domain, 0, strpos($domain, ':'));
        }

        return $domain;
    }

    //
    private function parseMinorDomain($host)
    {
        $domain['domain'] = $host;
        $domain['main_domain'] = preg_replace("/.*?([^\.\/]+(\.(cn|com|net|org|gov|edu))+)$/", "\\1", $domain['domain']);

        if ($domain['main_domain'] !== $domain['domain']) {
            $domain['sub_domain'] = str_replace('.' . $domain['main_domain'], '', $domain['domain']);
        }
        else {
            /* 如果是无二级域名的访问，默认为 www 二级域名 */
//            $domain['sub_domain'] = '';
//            $domain['domain'] = 'www.' . $domain['domain'];
            //感觉上面那样不好，还是按原样来吧
            $domain['sub_domain'] = '';
        }

        return $domain;
    }

    /* 获得用户的真实 IP 地址 */
    private function _getClientIp()
    {
        static $realip = NULL;

        if ($realip !== NULL) {
            return $realip;
        }

        if (isset($_SERVER)) {

            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
            elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realip = $ip;
                        break;
                    }
                }
            }
            elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $realip = $_SERVER['HTTP_X_REAL_IP'];
            }
            else {
                $realip = '0.0.0.0';
            }
        }
        else {
            if (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            }
            elseif (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            }
            elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $realip = $_SERVER['HTTP_X_REAL_IP'];
            }
            else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

        return $realip;
    }

    /* 获取服务器端的 IP 地址 */
    private function _getServerIp()
    {
        static $serverip = NULL;

        if ($serverip !== NULL) {
            return $serverip;
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            $serverip = $_SERVER['SERVER_ADDR'];
        }
        else {
            $ip = @file_get_contents(DATA_PATH . '/SERVER_ADDR');
            if (empty($ip) === false) {
                preg_match('/\d{1,3}(?<!192|127)(?:\.\d{1,3}){3}/', $ip, $match);
                $serverip = trim($match[0]);
            }
        }

        if (empty($serverip) === true) {
            trigger_error('[LIB_DOMAIN] _getServerIp 函数获取地址出错，ip=' . $ip, E_USER_WARNING);

            $serverip = '0.0.0.0';
        }

        return $serverip;
    }

    //1网页 2客户端 3WAP 4安卓 5苹果
    //声明：所有手机端（不管app还是浏览器）均识别为wap，要想精确区分是app还是浏览器，除非传特定http头，或者在登录时通过表单值识别存入session来区分
    public function getBrowserType()
    {
        $clientkeywords = array(
            'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-'
            , 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu',
            'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini',
            'operamobi', 'opera mobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
        );
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", $_SERVER['HTTP_USER_AGENT']) || stripos($_SERVER['HTTP_ACCEPT'], 'wml') !== false || stripos($_SERVER['HTTP_ACCEPT'], 'wap') !== false) {
            $type = 'wap';
        }
        elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'android app') !== false) {
            $type = 'android';
        }
        elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'iphone app') !== false) {
            $type = 'iphone';
        }
        else {
            $type = 'web';
        }

        return $this->info['browser_type'] = $type;
    }

    private function callback_addslashes(&$value, $key)
    {
        $value = addslashes($value);
    }

    private function callback_stripslashes(&$value, $key)
    {
        $value = stripslashes($value);
    }

    // 参考fetch_get()
    //@param	$escape = 1 对字符串转义， 0 不转义
    public function getGet($key, $type, $default = NULL, $escape = 1)
    {
        if (isset(self::$_get[$key]) === false) {
            switch ($type) {
                case 'array':
                    return array();
                    break;
                case 'intval':
                    return ($default === NULL) ? 0 : (int) $default;
                    break;
                case 'floatval':
                    return ($default === NULL) ? 0.0 : (float) $default;
                    break;
                case 'bigval':
                    return ($default === NULL) ? 0 : bigval($default);
                    break;
                case 'string':
                case 'binary':
                case 'trim':
                    if ($escape == 1) {
                        $default = addslashes($default);
                    }
                    // 无默认值的情况
                    if ($default === NULL) {
                        return '';
                    }
                    // 仅有默认值的情况
                    elseif (strpos($default, '|') === false) {
                        if ($type === 'string') {
                            return (string) $default;
                        }
                        else {
                            return trim((string) $default);
                        }
                    }
                    // 枚举的情况
                    else {
                        $_value = explode('|', $default);

                        if ($type === 'string' || $type === 'binary') {
                            return (string) $_value[0];
                        }
                        else {
                            return trim((string) $_value[0]);
                        }
                    }
                    break;
                default:
                    throw new exception2('getGet() unknown data filter type');
                    break;
            }
        }
        else {
            $value = self::$_get[$key];
            switch ($type) {
                case 'array':
                    $value = (array) $value;
                    return $value;
                    break;
                case 'intval':
                    return (int) $value;
                    break;
                case 'floatval':
                    return (float) $value;
                    break;
                case 'bigval':
                    return bigval($value);
                    break;
                case 'string':
                case 'binary':
                case 'trim':
                    if(is_array($value)){
                        //这里考虑肯定被伪造链接在刷
                        die('get() value data Illegal');
                    }
                    if ($escape == 1) {
                        $value = addslashes($value);
                    }
                    if ($type !== 'binary') {
                        // 暂不需要这么严格的过滤
                        //$value = replace_crlf((string) $value);
                    }
                    /* 枚举的情况 */
                    if ($default !== NULL) {
                        if (strpos($default, '|') !== false) {
                            $_value = explode('|', $default);
                            if ($value === '' || in_array($value, $_value) === false) {
                                $value = (string) $_value[0];
                            }
                        }
                        elseif ($value === '') {
                            $value = (string) $default;
                        }
                    }

                    if ($type === 'trim') {
                        $value = trim($value);
                    }
                    return $value;
                    break;
                default:
                    throw new exception2('getGet() unknown data filter type');
                    break;
            }
        }
    }

    public function setAppPost($key, $value){
        if($key=='*') self::$_post=$value;
        else self::$_post[$key]=$value;
    }

    // 复制代码为了保证效率，少一次函数调用开销
    //@param	$escape = 1 对字符串转义， 0 不转义
    public function getPost($key, $type, $default = NULL, $escape = 1)
    {
        if (isset(self::$_post[$key]) === false) {
            switch ($type) {
                case 'array':
                    return array();
                    break;
                case 'intval':
                    return ($default === NULL) ? 0 : (int) $default;
                    break;
                case 'floatval':
                    return ($default === NULL) ? 0.0 : (float) $default;
                    break;
                case 'bigval':
                    return ($default === NULL) ? 0 : bigval($default);
                    break;
                case 'string':
                case 'binary':
                case 'trim':
                    if ($escape == 1) {
                        $default = addslashes($default);
                    }
                    // 无默认值的情况
                    if ($default === NULL) {
                        return '';
                    }
                    // 仅有默认值的情况
                    elseif (strpos($default, '|') === false) {
                        if ($type === 'string') {
                            return (string) $default;
                        }
                        else {
                            return trim((string) $default);
                        }
                    }
                    // 枚举的情况
                    else {
                        $_value = explode('|', $default);

                        if ($type === 'string' || $type === 'binary') {
                            return (string) $_value[0];
                        }
                        else {
                            return trim((string) $_value[0]);
                        }
                    }
                    break;
                default:
                    throw new exception2('getPost() unknown data filter type');
                    break;
            }
        }
        else {
            $value = self::$_post[$key];
            switch ($type) {
                case 'array':
                    $value = (array) $value;
                    return $value;
                    break;
                case 'intval':
                    return (int) $value;
                    break;
                case 'floatval':
                    return (float) $value;
                    break;
                case 'bigval':
                    return bigval($value);
                    break;
                case 'string':
                case 'binary':
                case 'trim':
                    if ($escape == 1) {
                        $value = addslashes($value);
                    }
                    if ($type !== 'binary') {
                        // 暂不用这么严格的过滤
                        //$value = replace_crlf((string) $value);
                    }
                    /* 枚举的情况 */
                    if ($default !== NULL) {
                        if (strpos($default, '|') !== false) {
                            $_value = explode('|', $default);
                            if ($value === '' || in_array($value, $_value) === false) {
                                $value = (string) $_value[0];
                            }
                        }
                        elseif ($value === '') {
                            $value = (string) $default;
                        }
                    }

                    if ($type === 'trim') {
                        $value = trim($value);
                    }
                    return $value;
                    break;
                default:
                    throw new exception2('getPost() unknown data filter type');
                    break;
            }
        }
    }

    //@param	$escape = 1 对字符串转义， 0 不转义
    public function getCookie($key, $type, $default = NULL, $escape = 1)
    {
        if (isset(self::$_cookie[$key]) === false) {
            switch ($type) {
                case 'array':
                    return array();
                    break;
                case 'intval':
                    return ($default === NULL) ? 0 : (int) $default;
                    break;
                case 'floatval':
                    return ($default === NULL) ? 0.0 : (float) $default;
                    break;
                case 'bigval':
                    return ($default === NULL) ? 0 : bigval($default);
                    break;
                case 'string':
                case 'binary':
                case 'trim':
                    if ($escape == 1) {
                        $default = addslashes($default);
                    }
                    // 无默认值的情况
                    if ($default === NULL) {
                        return '';
                    }
                    // 仅有默认值的情况
                    elseif (strpos($default, '|') === false) {
                        if ($type === 'string') {
                            return (string) $default;
                        }
                        else {
                            return trim((string) $default);
                        }
                    }
                    // 枚举的情况
                    else {
                        $_value = explode('|', $default);

                        if ($type === 'string' || $type === 'binary') {
                            return (string) $_value[0];
                        }
                        else {
                            return trim((string) $_value[0]);
                        }
                    }
                    break;
            }
        }
        else {
            $value = self::$_cookie[$key];
            switch ($type) {
                case 'array':
                    $value = (array) $value;
                    return $value;
                    break;
                case 'intval':
                    return (int) $value;
                    break;
                case 'floatval':
                    return (float) $value;
                    break;
                case 'bigval':
                    return bigval($value);
                    break;
                case 'string':
                case 'binary':
                case 'trim':
                    if ($escape == 1) {
                        $value = addslashes($value);
                    }
                    if ($type !== 'binary') {
                        // 暂不用这么严格的过滤
                        //$value = replace_crlf((string) $value);
                    }
                    /* 枚举的情况 */
                    if ($default !== NULL) {
                        if (strpos($default, '|') !== false) {
                            $_value = explode('|', $default);
                            if ($value === '' || in_array($value, $_value) === false) {
                                $value = (string) $_value[0];
                            }
                        }
                        elseif ($value === '') {
                            $value = (string) $default;
                        }
                    }

                    if ($type === 'trim') {
                        $value = trim($value);
                    }
                    return $value;
                    break;
            }
        }
    }

    public function getGetAsArray()
    {
        return self::$_get;
    }

    public function getPostAsArray()
    {
        return self::$_post;
    }

    public function getCookieAsArray()
    {
        return self::$_cookie;
    }

    public function getProtocol()
    {
        return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on')) ? 'https' : 'http';
    }

    public function isMarketLink()
    {
        $var = $this->getGet('var', 'trim', '');
       // return preg_match('@^[\d\w]{6,12}$@', $var);//如果有var并值也满足则是代表该请求是推广短链接
       return preg_match("/^[a-zA-Z0-9]{3,10}$/", $var);//var只能由字母和数字组成3-10位
    }

    public function isSelfLottery()
    {
        $lotteryId = $this->getGet('lotteryId', 'trim', '') or  $lotteryId = $this->getPost('lotteryId', 'trim', '');
        return in_array($lotteryId, [11,13,16,18,25]) ? true : false;
    }

}

?>