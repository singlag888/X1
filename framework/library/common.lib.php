<?php

use common\model\baseModel;

if (!defined('IN_LIGHT')) {
    die('KCAH');
}
function exception_handler($e)
{
    if ($e instanceof exception2) {
        print $e->formatThrowTrace('<br />');
    } elseif ($e instanceof PDOException) {
        print formatThrowTrace($e, '<br />');
    } else {
        echo '异常，请联系平台客服！<br />' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
//        echo "Unknown exception type,system halt";
    }
    // exit(0);
}

function my_session_start()
{
    $sn = session_name();
    if (isset($_COOKIE[$sn])) {
        $sessid = $_COOKIE[$sn];
    } else if (isset($_GET[$sn])) {
        $sessid = $_GET[$sn];
    } else {
        session_start();
        return false;
    }

    if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $sessid)) {
        return false;
    }
    session_start();

    return true;
}

function formatThrowTrace(PDOException $e, $lf = "\r\n")
{
    $string = '';
    $traceInfos = array();
    $i = 0;
    $traceInfos[$i]['msg'] = $e->getMessage();
    $traceInfos[$i]['code'] = $e->getCode();
    $traceInfos[$i]['thrown_at'] = 'thrown at [' . $e->getFile() . ':' . $e->getLine() . ']';
    $traceInfos[$i]['trace'] = $e->getTrace();

    foreach ($traceInfos as $item) {
        if ($i++ > 0) {
            $string .= "Caused by: ";
        }
        $at = '@';
        $ld = '';
        $rd = '';

        $string .= "An exception has occured ({$item['msg']}{$ld},code={$item['code']})$lf";

        $j = 0;
        // 外层捕获返回友好页面,这里信息记录到日志
        //if (RUN_ENV < 3) {
            $string .= "\t\t{$item['thrown_at']}$lf";
        //}
        $j++;

        foreach ($item['trace'] as $traceInfo) {
            $class = empty($traceInfo['class']) ? '' : $traceInfo['class'];
            $type = empty($traceInfo['type']) ? '' : $traceInfo['type'];

            $args = '';
            $k = 0;
            if (!empty($traceInfo['args'])) {
                foreach ($traceInfo['args'] as $arg) {
                    if (is_array($arg)) {
                        $args .= 'Array';
                    } elseif (is_object($arg)) {
                        $args .= 'Object';
                    } else {
                        $args .= $arg;
                    }

                    if ($k++ < count($traceInfo['args']) - 1) {
                        $args .= ', ';
                    }
                }
            }

            $position = empty($traceInfo['file']) ? '' : "called at [{$traceInfo['file']}:{$traceInfo['line']}]";

            //测试机打印更多信息
            // 外层捕获返回友好页面,这里信息记录到日志
            if (RUN_ENV < 3) {
                $string .= "{$class}{$type}{$traceInfo['function']}($args) " . "{$position}$lf";
            }
            $j++;
        }
    }

    return $string;
}


function tofile($file, $content, $flag = 0)
{
    $pathinfo = pathinfo($file);

    if (!empty($pathinfo['dirname'])) {
        if (file_exists(LOG_PATH . $pathinfo['dirname']) === false) {
            if (@mkdir(LOG_PATH . $pathinfo['dirname'], 0777, true) === false) {
                return false;
            }
        }
    }
    if ($flag === FILE_APPEND) {
        return file_put_contents(LOG_PATH . $file, $content . PHP_EOL, LOCK_EX | FILE_APPEND);
    } else {
        return file_put_contents(LOG_PATH . $file, $content . PHP_EOL, LOCK_EX);
    }
}

function toFileAtTmp($file, $content, $flag = 0)
{
    // /tmp/run_pc_error.log
    // /tmp/run_admin_error.log
    // /tmp/run_mobile_error.log
    // /tmp/run_app_error.log

    $pathinfo = pathinfo($file);
    $logPath = '/tmp/';

    if (!empty($pathinfo['dirname'])) {
        if (file_exists($logPath . $pathinfo['dirname']) === false) {
            if (@mkdir($logPath . $pathinfo['dirname'], 0777, true) === false) {
                return false;
            }
        }
    }
    if ($flag === FILE_APPEND) {
        return file_put_contents($logPath . $file, $content . PHP_EOL, LOCK_EX | FILE_APPEND);
    } else {
        return file_put_contents($logPath . $file, $content . PHP_EOL, LOCK_EX);
    }
}

function subScreenStr($str, $length, $append = true, $htmlspecialchars = true)
{
    if ($length === 0 || isset($str{$length}) === false) {
        if ($htmlspecialchars === true) {
            return htmlspecialchars($str, ENT_QUOTES);
        } else {
            return $str;
        }
    }

    $i = 0;
    $j = 0;
    $strLength = strlen($str);
    while ($i < $strLength) {
        if ($str{$i} >= "\x80") {
            if ($j + 2 > $length) {
                break;
            } else {
                $i += 3;
                $j += 2;
            }
        } elseif ($j >= $length) {
            break;
        } else {
            ++$i;
            ++$j;
        }
    }

    if ($i === 0) {
        /* 处理字符串第一位字符是中文且 length = 1 的情况 */
        $newstr = mb_substr($str, 0, 1, 'UTF-8');
    } else {
        $newstr = substr($str, 0, $i);
    }

    if ($append && $str != $newstr) {
        $newstr .= '...';
    }

    if ($htmlspecialchars === true) {
        return htmlspecialchars($newstr, ENT_QUOTES);
    } else {
        return $newstr;
    }
}

/**
 * 递归得到目录下所有文件列表
 * @param string $path 当前路径
 * @param array $allowExtensions 允许的扩展名
 * @param array $unallowedExtensions 不允许的扩展名
 */
function scan_dir($path, $allowExtensions = array(), $unallowedExtensions = array(".", ".."))
{
    $result = array();
    if ($handle = opendir($path)) {
        while (($item = readdir($handle)) !== false) {
            if (in_array($item, $unallowedExtensions)) {
                continue;
            }
            if (is_dir($path . '/' . $item)) {
                $result = array_merge($result, scandir_recursive($path . '/' . $item, $allowExtensions, $unallowedExtensions));
            } else {
                if ($allowExtensions) {
                    if (($pos = strrpos($item, ".")) !== false) {
                        if (in_array(substr($item, $pos + 1), $allowExtensions)) {
                            $result[] = $path . '/' . $item;
                        }
                    }
                } else {
                    $result[] = $path . '/' . $item;
                }
            }
        }
    }

    return $result;
}

function used_memory()
{
    return sprintf(' %0.3f MB', memory_get_usage(true) / 1048576);
}

function array_spec_key($array, $key, $unset_key = false)
{
    if (empty($array) || !is_array($array)) {
        return array();
    }

    $new_array = array();
    foreach ($array AS $value) {
        if (!isset($value[$key])) {
            continue;
        }
        $value_key = $value[$key];
        if ($unset_key === true) {
            unset($value[$key]);
        }
        $new_array[$value_key] = $value;
    }

    return $new_array;
}

//开始session 不再担心命名空间，不同项目的不同session不受干扰
function start_sessions($lifetime = 30)
{
    if ($GLOBALS['PROJECTS'][PROJECT]['session'] === '') {
        static $flag = 0;
        if (!$flag) {
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION = array();
            $GLOBALS['SESSION'] = &$_SESSION;
            $flag++;
        }
    } else {
        if (!isset($GLOBALS['SESSION'])) {
            //第2个参数为cookie生命期，设置为0表示浏览器关闭即结束session，如果大于0表示多少分钟后才结束，区别在于关掉浏览器后是否还是登录状态
            //$GLOBALS['SESSION'] = new $GLOBALS['PROJECTS'][PROJECT]['session'](30, 10);
            $GLOBALS['SESSION'] = new $GLOBALS['PROJECTS'][PROJECT]['session']($lifetime, 0);
        }
    }

    return $GLOBALS['SESSION'];
}

/**
 * dom2array XML转换成数组
 * @param string $xmlString xml content
 */
function dom2array($xmlString)
{
    static $domDoc;

    if (!$domDoc) {
        $domDoc = new DOMDocument();
    }
    $domDoc->preserveWhiteSpace = false;
    if (!@$domDoc->loadXML($xmlString)) {
        return false;
    }

    return _dom2array($domDoc);
}

function _dom2array($node)
{
    $result = array();
//static $i = 0;echo "<p>第 $i 次进来,节点名：{$node->nodeName} ({$node->nodeType}) 节点值：{$node->nodeValue} "; $i++;
    if ($node->nodeType == XML_TEXT_NODE) {
        //echo "<h3>{$node->nodeValue}</h3>";
        return $node->nodeValue;
    }
    if ($node->hasAttributes()) {
        $result['@attributes'] = array();
        foreach ($node->attributes AS $attr) {
            $result['@attributes'][strtolower($attr->name)] = $attr->value;
        }
    }
    if ($node->hasChildNodes()) {
        foreach ($node->childNodes AS $nodeChild) {
            if ($node->firstChild->nodeName == $node->lastChild->nodeName && $node->childNodes->length > 1) {
                $result[strtolower($nodeChild->nodeName)][] = _dom2array($nodeChild);
            } else {
                $result[strtolower($nodeChild->nodeName)][] = _dom2array($nodeChild);
            }
        }
    }

    return $result;
}

function header_403($content = '')
{
    header2('Cache-Control: no-cache');
    header2($_SERVER['SERVER_PROTOCOL'] . ' 403', true, 403);

    exit("<h1>HTTP 403 Forbidden" . $content . "</h1>\r\n");
}

function header_404($content = '')
{
    header2('Cache-Control: no-cache');
    header2($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);

    exit("<h1>404 - Not Found" . $content . "</h1>\r\n");
}

function header2($string, $replace = true, $http_response_code = 0)
{
    $string = str_replace(array("\r", "\n"), array('', ''), $string);

    if (preg_match('/^\s*location:/is', $string)) {
        if ($http_response_code === 301) {
            @header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently', true, 301);
        }
        @header($string . "\r\n", $replace);

        exit;
    }

    if (empty($http_response_code)) {
        @header($string, $replace);
    } else {
        @header($string, $replace, $http_response_code);
    }
}

function dump()
{
    static $count = 0;
    $argsNum = func_num_args();
    $args = func_get_args();
    if (extension_loaded('xdebug')) {
        echo "<pre><p><strong>**************BEGIN DEBUG($count)************** at " . xdebug_call_class() . "::" . xdebug_call_function() . "() [" . xdebug_call_file() . " : <font color=red>" . xdebug_call_line() . "</font>]</strong></p>";
    } else {
        echo "<p>Debug info (no xdebug extension)</p>";
    }
    for ($i = 0; $i < $argsNum; ++$i) {
        if (is_array($args[$i]) && !empty($args[$i])) {
            print_r($args[$i]);
        } else {
            var_dump($args[$i]);
        }
    }
    echo "<p><strong>**************END DEBUG($count)**************</strong></p></pre>\n";

    $count++;
}

//记录系统日志，调试日志 所以叫dump嘛
function logdump()
{
    static $count = 0;
    $argsNum = func_num_args();
    $args = func_get_args();
    $str = '';
    if (extension_loaded('xdebug')) {
        $str .= "" . date('Y-m-d H:i:s') . " BEGIN DEBUG($count) at " . xdebug_call_class() . "::" . xdebug_call_function() . "() [" . str_replace(ROOT_PATH, '', xdebug_call_file()) . " " . xdebug_call_line() . "]\n";
    } else {
        $debug_backtrace = debug_backtrace();
        $info = reset($debug_backtrace);
        $str .= date('Y-m-d H:i:s') . " at " . basename($info['file']) . ":" . $info['line'] . "\n";
    }

    for ($i = 0; $i < $argsNum; ++$i) {
        if (is_string($args[$i])) {
            $str .= $args[$i] . "\n";
        } else {
            $str .= var_export($args[$i], true) . "\n";
        }
    }

    $count++;
    $str .= "**************END DEBUG($count)**************";    //tofile会再加个换行符
    $log_file = 'sysdbg.log';

    return tofile($log_file, $str . "\n", FILE_APPEND);
}

//记录业务运行日志
function log2()
{
    $argsNum = func_num_args();
    $args = func_get_args();
    $str = '';
    if (extension_loaded('xdebug')) {
        $str .= "" . date('m-d H:i:s') . " " . basename(xdebug_call_file()) . " " . xdebug_call_line() . " >>>";
    } else {
        $info = @reset(debug_backtrace());
        $str .= "" . date('m-d H:i:s') . " " . basename($info['file']) . ":" . $info['line'] . " >>>";
    }

    for ($i = 0; $i < $argsNum; ++$i) {
        if ($i > 0) {
            $str .= "\n";
        }
        if (is_string($args[$i])) {
            $str .= $args[$i];
            //$str .= var_export($args[$i], true);
        } else {
            $str .= var_export($args[$i], true);
        }
    }

    //$str .= "[end]";
    $fileName = date('Ymd') . '.app.log';

    tofile(date('Ym') . '/' . $fileName, $str . "\n", FILE_APPEND);
}

//第一个参数是指定文件名，便于需要集中记录某一部分功能的日志
function log2file()
{
    $argsNum = func_num_args();
    $args = func_get_args();
    if ($argsNum < 2 || !preg_match('`\w+`', $args[0])) {
        return false;
    }
    $str = '';
    if (extension_loaded('xdebug')) {
        $str .= "" . date('m-d H:i:s') . " " . basename(xdebug_call_file()) . " " . xdebug_call_line() . " >>>";
    } else {
        $debug_backtrace = debug_backtrace();
        $info = reset($debug_backtrace);
        $str .= "" . date('m-d H:i:s') . " " . basename($info['file']) . ":" . $info['line'] . " >>>";
    }

    for ($i = 1; $i < $argsNum; ++$i) {
        if ($i > 1) {
            $str .= "\n";
        }
        if (is_string($args[$i])) {
            $str .= $args[$i];
            //$str .= var_export($args[$i], true);
        } else {
            $str .= var_export($args[$i], true);
        }
    }

    //$str .= "[end]";
    $fileName = date('Ymd') . ".{$args[0]}";

    tofile(date('Ym') . '/' . $fileName, $str . "\n", FILE_APPEND);
}

function loadtime()
{
    if (defined('MICROTIME') === true) {
        return number_format(microtime(true) - MICROTIME, 3);
    } else {
        return 0.000;
    }
}

function start_performance()
{
    if (isset($GLOBALS['_PROFILE']) === false) {
        $GLOBALS['_PROFILE'] = new performance(time());
    }

    return $GLOBALS['_PROFILE'];
}

//前台不要调用此方法
function pageProfile()
{
    $str = " MYSQL查询" . $GLOBALS['_PROFILE']->countMysql() . "次，";
    $str = $str . "执行时间" . loadtime() * 1000 . "ms";
    //$str .= " mc:" . $GLOBALS['_PROFILE']->countMC();
    //$str .= " xc:" . $GLOBALS['_PROFILE']->countXC();
    $logStr = "";
    if ($GLOBALS['REQUEST']->getGet('profile', 'intval') == '255') {
        $logStr .= "\n********profile********\n";
        foreach ($GLOBALS['_PROFILE']->getDetail() as $v) {
            $logStr .= "$v\n";
        }
        $logStr .= "********end profile********\n";
    }

    if (RUN_ENV == 3) {
        log2($str . $logStr);
        return $str;
    }

    return $str . "<!-- $logStr -->";
}

function dump_profile()
{
    if (loadtime() >= 0.200) {
        $string = loadtime() . '@' . date('H:i:s', REQUEST_TIME) . '|' . DOMAIN . '|' . DISPATCH_FILE . '|' . "\r\n\r\n";

        $log_file = 'exec_profiler_log/' . date('Ymd', REQUEST_TIME) . '.log';

        return tofile($log_file, $string, FILE_APPEND);
    } else {
        return true;
    }
}

//得到分页码
function getPageList($totalRecordsNum, $perPage = DEFAULT_PER_PAGE, $style = 1, $visiblePageNum = 5)
{
    $curPage = Request::getInstance()->getGet('curPage', 'intval', 1);
    $totalPages = ceil($totalRecordsNum / $perPage);

    /**
     * 当前页的范围应该在 1 - 最大页数  之间
     * tommy
     */
    $curPage = $curPage > 0 ? $curPage : 1;
    $curPage = $curPage < $totalPages ? $curPage : $totalPages;

    $url = getUrl(0);
    if (!strpos($url, '?')) {
        $url .= '?';
    } else {
        $url = str_replace(array("curPage=$curPage"), '', $url);
        $url = trim($url, '&') . '&';
    }

    //bugfix: 遇到&reg_ip时&reg是html实体会被转义，所以应用&amp;表示&
    $url = str_replace('&', '&amp;', $url);

    if ($curPage > 1) {
        $firstPage = "<a class='symbol' href=\"{$url}curPage=1\">&laquo;<i></i></a>";
    } else {
        $firstPage = "<em>&laquo;</em>";
    }

    if ($curPage < $totalPages) {
        $endPage = "<a class='symbol' href=\"{$url}curPage={$totalPages}\">&raquo;<i></i></a>";
    } else {
        $endPage = "<em>&raquo;</em>";
    }

    if ($curPage < $totalPages) {
        $nextPage = "<a class='symbol' href=\"{$url}curPage=" . ($curPage + 1) . "\">&rsaquo;<i></i></a>";
    } else {
        $nextPage = "<em>&rsaquo;</em>";
    }

    if ($curPage > 1) {
        $prevPage = "<a class='symbol' href=\"{$url}curPage=" . ($curPage - 1) . "\">&lsaquo;<i></i></a>";
    } else {
        $prevPage = "<em>&lsaquo;</em>";
    }

    $html = "<div id=\"pageList\">" . $firstPage . $prevPage;

    $counter = 0;
    if ($curPage > floor($visiblePageNum / 2)) {
        $i = $curPage - floor($visiblePageNum / 2);
    } else {
        $i = 1;
    }
    for (; $i < $curPage && $i > 0; $i++, $counter++) {
        $html .= "<a href=\"{$url}curPage=$i\">$i<i></i></a>";
    }
    $html .= "<span class=\"curPage\">$curPage<i></i></span>";
    for ($i = $curPage + 1; $i <= $totalPages && $counter + 1 < $visiblePageNum; $i++, $counter++) {

        $html .= "<a href=\"{$url}curPage=$i\">$i<i></i></a>";
    }
    $html .= $nextPage . $endPage . "<label>[$curPage/$totalPages] 总计{$totalRecordsNum}条记录</label></div>";

    return $html;
}

function getPageListMobile($totalRecordsNum, $perPage = DEFAULT_PER_PAGE, $style = 1, $visiblePageNum = 5)
{
    $curPage = Request::getInstance()->getGet('curPage', 'intval', 1);
    $totalPages = ceil($totalRecordsNum / $perPage);

    $url = getUrl(0);
    if (!strpos($url, '?')) {
        $url .= '?';
    } else {
        $url = str_replace(array("curPage=$curPage"), '', $url);
        $url = trim($url, '&') . '&';
    }

    //bugfix: 遇到&reg_ip时&reg是html实体会被转义，所以应用&amp;表示&
    $url = str_replace('&', '&amp;', $url);

    if ($curPage > 1) {
        $firstPage = "<a class='symbol DisplayNone' href=\"{$url}curPage=1\">&laquo;<i></i></a>";
    } else {
        $firstPage = "<a class='symbol DisplayNone'>&laquo;</a>";
    }

    if ($curPage < $totalPages) {
        $endPage = "<a class='symbol DisplayNone' href=\"{$url}curPage={$totalPages}\">&raquo;<i></i></a>";
    } else {
        $endPage = "<a class='symbol DisplayNone'>&raquo;</a>";
    }

    if ($curPage < $totalPages) {
        $nextPage = "<a class='symbol FloatRight' href=\"{$url}curPage=" . ($curPage + 1) . "\">&rsaquo;<i></i></a>";
    } else {
        $nextPage = "<a class='symbol FloatRight'>&rsaquo;</a>";
    }

    if ($curPage > 1) {
        $prevPage = "<a class='symbol FloatLeft' href=\"{$url}curPage=" . ($curPage - 1) . "\">&lsaquo;<i></i></a>";
    } else {
        $prevPage = "<a class='symbol FloatLeft'>&lsaquo;</a>";
    }

    $html = "<div id=\"pageList\" class=\"TextAlignC\">" . $firstPage . $prevPage;

    $counter = 0;
    if ($curPage > floor($visiblePageNum / 2)) {
        $i = $curPage - floor($visiblePageNum / 2);
    } else {
        $i = 1;
    }
    for (; $i < $curPage && $i > 0; $i++, $counter++) {
        $html .= "<a href=\"{$url}curPage=$i\">$i<i></i></a>";
    }
    $html .= "<span class=\"curPage\">$curPage<i></i></span>";
    for ($i = $curPage + 1; $i <= $totalPages && $counter + 1 < $visiblePageNum; $i++, $counter++) {

        $html .= "<a href=\"{$url}curPage=$i\">$i<i></i></a>";
    }
    $html .= $nextPage . $endPage . "<label>[$curPage/$totalPages] 总计{$totalRecordsNum}条记录</label></div>";

    return $html;
}

//根据controller和action生成网址
function url($controllerName = NULL, $actionName = NULL, $params = NULL, $anchor = NULL)
{
    $baseurl = NULL;
    // 确定当前的 URL 基础地址和入口文件名
    $protocol = $GLOBALS['REQUEST']['protocol'];
    $http_host = $_SERVER["HTTP_HOST"];

    /**
     * 130717 处理代理服务器非80端口访问这种情况
     * 正常情况 可以用SERVER_NAME+SERVER_PORT或者HTTP_HOST
     * 'SERVER_PORT' => '85',
     * 'SERVER_NAME' => '*.abc.com',
     * 'HTTP_HOST' => 'cd1.abc.com:85',
     * nginx代理转发情况，只能用HTTP_HOST再特殊判断SERVER_PORT
     * 'SERVER_PORT' => '85',
     * 'SERVER_NAME' => '*.abc.com',
     * 'HTTP_HOST' => 'cd1.abc.com',
     * @todo index.jsp?&a=login 前面没参数时不显示&
     */
    if ($_SERVER['SERVER_PORT'] != 80) {
        if (strpos($http_host, ":{$_SERVER['SERVER_PORT']}") === false) {
            $http_host .= ":{$_SERVER['SERVER_PORT']}";
        }
    }

    if (strpos($_SERVER["REQUEST_URI"], '?') === false && substr($_SERVER["REQUEST_URI"], -1) == '/') {
        //保持原样
        //$baseurl = $protocol . '://' . $http_host . $_SERVER["REQUEST_URI"];
        //130304改动：为防止缓存带来的混乱，故对于没有写index.php的都显式给出，从而让防护产品不缓存php文件
        $baseurl = $protocol . '://' . $http_host . $_SERVER["SCRIPT_NAME"];
    } else {
        //保持原样
        //$baseurl = $protocol . '://' . $http_host . rtrim(dirname($_SERVER["REQUEST_URI"]), '\/') . '/';
        //130304改动：为防止缓存带来的混乱，故对于没有写index.php的都显式给出，从而让防护产品不缓存php文件
        $baseurl = $protocol . '://' . $http_host . $_SERVER["SCRIPT_NAME"];
    }

    //确定控制器和动作的名字
    if ($controllerName == NULL || $controllerName == 'default') {
        $controllerName = NULL;
    }
    if ($actionName == NULL || $actionName == 'index') {
        $actionName = NULL;
    }

    // 标准模式
    $url = $baseurl;    // 这里不应加/
    if ($controllerName || $actionName || !empty($params)) {
        $url .= '?';
    }
    if ($controllerName) {
        $url .= 'c=' . $controllerName;
    }
    if ($actionName) {
        $url .= '&a=' . $actionName;
    }

    if (is_array($params) && !empty($params)) {
        $tmp = array();
        foreach ($params as $k => $v) {
            $tmp[] = "$k=$v";
        }
        $url .= '&' . implode('&', $tmp);
    }
    if ($anchor) {
        $url .= '#' . $anchor;
    }

    return $url;
}

/**
 * 参数为0返回请求的完整URL
 * 参数为1返回请求的URL去掉参数
 * 参数为2返回请求的URL域名
 * @param type $isShowFullUri
 * @return string
 */
function getUrl($isShowFullUri = 1)
{
    $result = $GLOBALS['REQUEST']['protocol'] . '://';
    $result .= $_SERVER['HTTP_HOST'];
    //以下不需要，因为$_SERVER['HTTP_HOST']已包含可能的端口号
//    if (intval($_SERVER['SERVER_PORT']) != 80 && intval($_SERVER['SERVER_PORT']) != 443) {
//        $result .= ':' . $_SERVER["SERVER_PORT"];
//    }

    if ($isShowFullUri == 0) {
        $result = $result . $_SERVER["REQUEST_URI"]; //原始地址
    } elseif ($isShowFullUri == 1) {
        if (($pos = strpos($_SERVER["REQUEST_URI"], '?')) !== false) {
            $result = $result . substr($_SERVER["REQUEST_URI"], 0, $pos);
        } else {
            $result = $result . $_SERVER["REQUEST_URI"];
        }
    }

    return $result;
}

function redirect($url, $delay = 0, $js = FALSE, $jsWrapped = TRUE, $return = FALSE)
{
    if (empty($url)) {
        $url = getUrl();
    }

    $delay = (int)$delay;
    if (!$js) {
        if (headers_sent() || $delay > 0) {
            echo <<<EOT
<HTML><HEAD><META http-equiv="refresh" content="{$delay};URL={$url}" /></HEAD></HTML>
EOT;
            exit;
        } else {
            header("Location: {$url}");
            exit;
        }
    } else {
        $out = '';
        if ($jsWrapped) {
            $out .= '<script language="JavaScript" type="text/javascript">';
        }
        if ($delay > 0) {
            $out .= "window.setTimeout(function () { parent.location.href='{$url}'; }, {$delay});";
        } else {
            $out .= "parent.location.href='{$url}';";
        }
        if ($jsWrapped) {
            $out .= '</script>';
        }
        if ($return) {
            return $out;
        }
        echo $out;
        exit;
    }
}

/**
 *
 * @param String $string 原文
 * @param String $operation ENCODE或者DECODE，前者表示加密，后者表示解密
 * @param String $key 私钥
 * @param int $expiry 到期时间，单位秒，如1800表示半小时后到期，即使正确的密钥也无法解开
 * @return String 返回加密或者解密后的字符串
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4;

    $key = md5($key ? $key : 'US_KEY');
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : ''; // '123456789' => microtime()

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . rtrim(base64_encode($result), '=');
    }
}

/**
 * 简单加解密函数，注意key值必须为纯数字
 * @param <string> $str
 * @param <int> $key
 * @return <string>
 */
function encode($str, $key = 28)
{
    $str = strval($str);
    for ($i = 0; $i < strlen($str); $i++) {
        $str{$i} = chr(ord($str{$i}) + $key);
    }

    return $str = rtrim(base64_encode($str), '=');
}

function decode($str, $key = 28)
{
    $str = base64_decode($str);
    for ($i = 0; $i < strlen($str); $i++) {
        $str{$i} = chr(ord($str{$i}) - $key);
    }

    return $str;
}

//时间转成当天的秒数
function time2second($str)
{
    $tmp = explode(':', $str);
    return $tmp[0] * 3600 + $tmp[1] * 60 + $tmp[2];
}

//反操作
function second2time($second)
{
    $result['hour'] = intval($second / 3600);
    $second -= $result['hour'] * 3600;
    $result['minute'] = intval($second / 60);
    $result['second'] = $second - $result['minute'] * 60;

    return $result['hour'] . ':' . $result['minute'] . ':' . $result['second'];
}

function getInterval($ts)
{
    $intDay = floor((time() - $ts) / 86400);
    if ($intDay) {
        if (floor($intDay / 365) > 0) {
            $str = floor($intDay / 365) . "年前";
        } elseif (floor($intDay / 30) > 0) {
            $str = floor($intDay / 30) . "个月前";
        } elseif (floor($intDay / 7) > 0) {
            $str = floor($intDay / 7) . "周前";
        } else {
            $str = $intDay . "天前";
        }
    } else {
        $indMin = floor((time() - $ts) / 60);
        if (floor($indMin / 60) > 0) {
            $str = floor($indMin / 60) . "小时前";
        } else {
            $str = $indMin . "分钟前";
        }
    }

    return $str;
}

function outputAttachment($content, $filename, $contentType = '')
{
    if (stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
        header2('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
    } else {
        header2('Content-Disposition: attachment; filename="' . $filename . '"');
    }

    if ($contentType === '') {
        $contentType = 'application/octet-stream';
    }
    header2('Content-Type: ' . $contentType);
    header2('Cache-Control: no-store');

    echo $content;
}

//检验身份证是否合法
function checkIdCard($id_card)
{
    //$id_card = MoshBase::maddslashes($id_card);
    if (strlen($id_card) == 18) {
        if (self::idcard_check($id_card)) {
            return true;
        } else {
            return false;
        }
    } elseif (strlen($id_card) == 15) {
        $id_card = self::idcard_toNormal($id_card);
        if (self::idcard_check($id_card)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function idcard_verify_number($idcard_base)
{
    if (strlen($idcard_base) != 17) {
        return false;
    }
    //加权因子
    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
    //校验码对应值
    $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
    $checksum = 0;
    $baseLen = strlen($idcard_base);
    for ($i = 0; $i < $baseLen; $i++) {
        $checksum += substr($idcard_base, $i, 1) * $factor[$i];
    }
    $mod = $checksum % 11;
    $verify_number = $verify_number_list[$mod];
    return $verify_number;
}

//将15为省份证升级到18为
function idcard_toNormal($idcard)
{
    if (strlen($idcard) != 15) {
        return false;
    } else {
        //如果省份证顺序号码是996，997，998，999,这些是百岁以上老人的特殊编码
        if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
            $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
        } else {
            $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
        }
    }
}

//18位省份证校验码有效性检查
function idcard_check($idcard)
{
    if (strlen($idcard) != 18) {
        return false;
    }
    $idcard_base = substr($idcard, 0, 17);
    if (self::idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
        return false;
    } else {
        return true;
    }
}

/*
 * Description:  银行卡号Luhm校验
 * Luhm校验规则：16位银行卡号（19位通用）
 * 1.将未带校验位的 15（或18）位卡号从右依次编号 1 到 15（18），位于奇数位号上的数字乘以 2。
 * 2.将奇位乘积的个十位全部相加，再加上所有偶数位上的数字。
 * 3.将加法和加上校验位能被 10 整除。
 */
function checkBankCard($bankno)
{
    if (!preg_match('`^\d+$`', $bankno)) {
        return false;
    }
    $len = strlen($bankno);
    $lastNum = substr($bankno, $len - 1, 1);                //取出最后一位（与luhm进行比较）
    $topNum = substr($bankno, 0, $len - 1);                 //根据卡号数取出前15或18位
    $topLen = strlen($topNum);
    $reverseNo = array();                               //存放前15位或18位的逆序数字
    $j = 0;
    //前15或18位倒序存进数组
    for ($i = $topLen - 1; $i >= 0; $i--) {
        $reverseNo[$j] = $topNum{$i};
        $j++;
    }
    $oddlt = array();                                   //奇数位*2的积 <9
    $oddgt = array();                                   //奇数位*2的积 >9
    $even = array();                                    //偶数位数组
    $revLen = count($reverseNo);
    for ($m = 0; $m < $revLen; $m++) {
        if (($m + 1) % 2 == 1) {//奇数位
            if (intval($reverseNo[$m]) * 2 < 9) {
                $oddlt[$m] = intval($reverseNo[$m]) * 2;
            } else {
                $oddgt[$m] = intval($reverseNo[$m]) * 2;
            }
        } else {//偶数位
            $even[$m] = $reverseNo[$m];
        }
    }
    $oddgt_child1 = array();                        //奇数位*2 >9 的分割之后的数组个位数
    $oddgt_child2 = array();                        //奇数位*2 >9 的分割之后的数组十位数
    $oddgtLen = count($oddgt);
    foreach ($oddgt as $k => $v) {
        $oddgt_child1[$k] = intval($oddgt[$k] % 10);
        $oddgt_child2[$k] = intval($oddgt[$k] / 10);
    }

    $sumOddlt = array_sum($oddlt);                       //奇数位*2 < 9 的数组之和
    $sumEven = array_sum($even);                         //偶数位数组之和
    $sumOddChild1 = array_sum($oddgt_child1);            //奇数位*2 >9 的分割之后的数组个位数之和
    $sumOddChild2 = array_sum($oddgt_child2);            //奇数位*2 >9 的分割之后的数组十位数之和
    //计算总和
    $sumTotal = $sumOddlt + $sumEven + $sumOddChild1 + $sumOddChild2;

    //计算Luhm值
    $key = intval($sumTotal) % 10 == 0 ? 10 : intval($sumTotal) % 10;
    $luhm = 10 - $key;

    if ($lastNum == $luhm) {
        return true;
    } else {
        return false;
    }
}

/*
 * 匹配IP段
 * @param $needIp  string 需要被匹配的IP
 * @param $mattchIps  array 标准IP段或者IP
 * return bool
 */
function mattchIpSection($needIp, $mattchIps)
{
    if (!is_array($mattchIps)) {
        return false;
    }

    $flag = false;
    foreach ($mattchIps as $ip) {
        if (preg_match('/^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d|\*)\.((25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d|\*)\.){2}(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d|\*)$/', $ip)) {
            $needIpArr = explode(".", $needIp);
            $ipArr = explode(".", $ip);
            $flag = true;
            foreach ($ipArr as $k => $v) {
                if ($v != '*' && $v != $needIpArr[$k]) {
                    $flag = false;
                    break;
                }
            }

            if ($flag == true) {
                return true;
            }
        }
    }

    return $flag;
}

/**
 * 防止用户瞬间提交多次(可用于需要短时间内限制重复执行动作的情况)
 * $interval_time秒之内同一用户不能够重复执行
 * @author Davy 2015年12月2日
 * @param string $prefix cache唯一key
 * @param int $userId
 * @param int $interval 间隔时间 秒
 * @param int $lifetime 缓存多久 秒
 * @return
 */
function preventDDosAttack($prefix, $userId, $interval = 10, $lifetime = 864000)
{
    $nowStamp = time();
    $cacheKey = $userId;
    $lastTimeStamp = intval($GLOBALS['mc']->get($prefix, $cacheKey));
    if (($nowStamp - $lastTimeStamp) <= $interval) {
        return false;
    }

    if (!$GLOBALS['mc']->set($prefix, $cacheKey, $nowStamp, $lifetime)) {
        logdump('xc添加不成功', $prefix, $cacheKey);
    }

    return true;
}

/**
 * 资金和安全码加密
 * @param $safePwd
 * @return string
 */
function generateEnPwd($safePwd)
{
    return md5(sha1('etwe534#?32df$64wxc' . $safePwd));
}

/**
 * 不截断调试打印
 * @param
 */
function p()
{
    echo '<pre>';
    foreach (func_get_args() as $v) {
        var_dump($v);
    }
}

/**
 * 截断调试打印
 * @param
 * @return
 */
function dd()
{
    echo '<pre>';
    foreach (func_get_args() as $v) {
        var_dump($v);
    }
    die;
}

function array2object($array)
{
    if (is_array($array)) {
        $obj = new StdClass();
        foreach ($array as $key => $val) {
            $obj->$key = $val;
        }
    } else {
        $obj = $array;
    }
    return $obj;
}

function object2array($object)
{
    $array = [];
    if (is_object($object)) {
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }
    } else {
        $array = $object;
    }
    return $array;
}

function getRandChar($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }

    return $str;
}

function layerAlert($msg, $link = '')
{
    echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
    $loadJs = '<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script><script type="text/javascript" src="js/layer-v2.4/layer.js"></script>';
    $redirect = '';

    $layer = "<script>layer.alert('{$msg}', {skin: 'layui-layer-molv',closeBtn: 0,anim: 4}";
    if ($link != '') {
        $redirect = ",function(){ window.location.href='{$link}';}";
    }
    $end = ' );</script>';
    echo $loadJs . $layer . $redirect . $end . '</body></html>';
    exit;
}

//手机端调用
function layerAlertM($msg, $link = '', $btn = '确认')
{
    echo '<html><head><meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"><meta name="apple-mobile-web-app-capable" content="yes" /><meta name="format-detection" content="telphone=no" /><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><link rel="stylesheet" type="text/css" href="css/layer.m.css"></head><body>';
    $loadJs = '<script type="text/javascript" src="js/layer-v2.4/layer.m.js"></script>';
    $redirect = '';

    $layer = "<script>layer.open({style: 'width:100%;',anim:'up',shadeClose:false,content: '{$msg}' ,btn:'{$btn}'";
    if ($link != '') {
        $redirect = ",yes:function(){ window.location.href='{$link}';}";
    }
    $end = ' });</script>';
    echo $loadJs . $layer . $redirect . $end . '</body></html>';
    exit;
}

function payCardsEncode($perfix, $user, $callBack, &$card)
{
    $codeStr = $perfix . 'Codes';
    $payResUrlStr = $perfix . 'PayRequestURI';
    $card[$codeStr] = authcode(substr($user['username'], -5) . substr($user['username'], 0, 1) . $user['user_id'], 'ENCODE', 'a6sbe!x4^5d_ghd');
    $card[$payResUrlStr] = authcode(url('fin', $callBack), 'ENCODE', 'gs4fj@5f!sda*dfuf');
    $card['call_back_url'] = authcode($card['call_back_url'], 'ENCODE', 'a6sbe!x4^5d_ghd');
}

function payCardsEncodeNew($user, $callBack, $card, $isMobile = false)
{
    $card['card_id'] =  isset($card['card_id']) ? authcode($card['card_id'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
    $card['bank_id'] =  isset($card['bank_id']) ? authcode($card['bank_id'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
    $card['netway'] =  isset($card['netway']) ? authcode($card['netway'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
    $card['codes'] = authcode(substr($user['username'], -5) . substr($user['username'], 0, 1) . $user['user_id'], 'ENCODE', 'a6sbe!x4^5d_ghd');
    $card['requestURI'] = authcode(url('fin', $callBack), 'ENCODE', 'gs4fj@5f!sda*dfuf');
    $card['call_back_url'] =  isset($card['call_back_url']) ? authcode($card['call_back_url'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
    $card['return_url'] =  isset($card['return_url']) ? authcode($card['return_url'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';

    if (isset($card['direct_display']) && $card['direct_display'] == 1 && $isMobile) {
        $card['shop_url'] =  isset($card['shop_url']) ? authcode($card['shop_url'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
    }

    return $card;
}


function payCallBack($request, $logName = 'payCallBack.log')
{

    $verify = $request->getPost('verify', 'trim');
    $user_id = $request->getPost('user_id', 'intval');
    $username = $request->getPost('username', 'trim');
    $card_id = $request->getPost('card_id', 'intval');
    $bank_id = $request->getPost('bank_id', 'intval');
    $order_num = $request->getPost('order_num', 'trim');
    $shop_order_num = $request->getPost('shop_order_num', 'trim');
    $player_pay_time = $request->getPost('pay_time', 'trim');
    $amount = $request->getPost('amount', 'floatval');
    $target_fee = $request->getPost('target_fee', 'floatval');
    $source_fee = $request->getPost('source_fee', 'floatval');
    $min_deposit_limit = config::getConfig('min_deposit_limit', 50);

    if (!$verify || !$user_id || !$username || !$card_id || !$bank_id || !$order_num || !$shop_order_num || !$player_pay_time || !$amount || $target_fee < 0 || $source_fee < 0 || $min_deposit_limit < 0) {
        //非法访问，退出
        log2file($logName, "deposit failed:参数不对，非法访问(user_id={$user_id},username={$username},card_id={$card_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
        return '6';
    }
    if ($amount < $min_deposit_limit - 5) {
        log2file($logName, "deposit failed:金额不足{$min_deposit_limit}，拒绝充值(user_id={$user_id},username={$username},card_id={$card_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
        return '3';
    }
    if ($verify !== 'formal') {
        //非法访问，退出
        log2file($logName, "deposit failed:非法访问(user_id={$user_id},username={$username},card_id={$card_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
        return '2';
    }

    if (!$user_id || !$username || !$card_id || !$bank_id || !$order_num || !$amount || !$player_pay_time || !$shop_order_num) {
        log2file($logName, "deposit failed:参数不完整(user_id={$user_id},username={$username},card_id={$card_id},bank_id={$bank_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
        return '5';
    } else {
        try {
            $checkDuplicate = deposits::getItemByOrderNum($order_num);
            if (!empty($checkDuplicate)) {
                //重复上分，退出
                log2file($logName, "deposit repeat:重复上分，退出(user_id={$user_id},username={$username},card_id={$card_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
                return '9';
            }
            $result = deposits::onlinePaymentCharge2($user_id, $username, $card_id, $bank_id, $amount, $player_pay_time, $order_num, $shop_order_num, $target_fee, $source_fee, $logName);
            if ($result === true) {
                log2file($logName, "充值OK！(user_id={$user_id},username={$username},card_id={$card_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
                return '8';
            } else {
                log2file($logName, "deposit failed2:错误码 {$result}(user_id={$user_id},username={$username},card_id={$card_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
                return '4';
            }
        } catch (exception2 $e) {
            log2file($logName, "deposit failed:" . $e->getMessage() . "(user_id={$user_id},username={$username},card_id={$card_id},amount={$amount},player_pay_time={$player_pay_time},trade_number={$order_num},shop_order_number={$shop_order_num})");
            return '1';
        }
    }
}

/**
 * 响应返回,用于ajax javascript的返回,包含先前showMsg与showAlert的页面形式返回
 * @param mixed $data 要返回的数据
 * @param String $type AJAX返回数据格式
 * @return void
 */
function response($data, $type = 'JSON')
{
    if (func_num_args() > 2) {
        $args = func_get_args();
        array_shift($args);
        $info = array();
        $info['data'] = $data;
        $info['info'] = array_shift($args);
        $info['status'] = array_shift($args);
        $data = $info;
        $type = $args ? array_shift($args) : '';
    }

    if (strtoupper($type) == 'JSON') {
        // 返回JSON数据格式到客户端 包含状态信息
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    } elseif (strtoupper($type) == 'XML') {
        // 返回xml格式数据
        header('Content-Type:text/xml; charset=utf-8');
        // exit(xml_encode($data));
    } elseif (strtoupper($type) == 'EVAL') {
        // 返回可执行的js脚本
        header('Content-Type:text/html; charset=utf-8');
        exit($data);
    } elseif (strtoupper($type) == 'ALERT') {
        $msg = $data ? array_shift($data) : '';
        $link = $data ? array_shift($data) : '';
        $style = $data ? array_shift($data) : 1;
        showAlert($msg, $link, $style);
        exit;
    } elseif (strtoupper($type) == 'MSG') {
        $msg = $data ? array_shift($data) : '';
        $msgType = $data ? array_shift($data) : 0;
        $links = $data ? array_shift($data) : [];
        $target = $data ? array_shift($data) : 'self';
        $seconds = $data ? array_shift($data) : -1;
        showMsg($msg, $msgType, $links, $target, $seconds);
        exit;
    } else {
        // TODO 增加其它格式
    }
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type = 0)
{
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name));
    } else {
        return strtolower(trim(preg_replace('/[A-Z]/', "_\\0", $name), '_'));
    }
}

/**
 * 获取浮窗 | QQ | 邮箱 | 微信 | 客服线 配置
 * @param string $key
 * @return mixed|string
 */
function getFloatConfig($key = '')
{
    static $config = [];

    if (!$config) {
        $config = (new float())->getConfig(float::OPTION_MATCH_PATH);
    }

    if ($key) {
        return isset($config[$key]) ? $config[$key] : '';
    }

    return $config;
}

/**
 * 实例化一个没有模型文件的Model
 * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
 * @param string $tablePrefix 表前缀
 * @param mixed $connection 数据库连接信息
 * @return baseModel
 */
function M($name = '', $tablePrefix = '', $connection = '')
{
    static $_model = array();
    if (strpos($name, ':')) {
        list($class, $name) = explode(':', $name);
    } else {
        $class = '\common\model\baseModel';
    }
    $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid]))
        $_model[$guid] = new $class($name, $tablePrefix, $connection);
    return $_model[$guid];
}

/**
 * 实例化模型类
 * @param string $name 资源地址
 * @param string $layer 模型层名称
 * @return baseModel|packages
 */
function D($name = '', $layer = '')
{
    if (empty($name)) return new baseModel;
    static $_model = array();
    if (isset($_model[$name . $layer]))
        return $_model[$name . $layer];

    // 1.没有命名空间
    // 2.目前model都在admin下面
    if (class_exists($name)) {
        $model = new $name();
    } else {
        $model = new baseModel(basename($name));
    }
    $_model[$name . $layer] = $model;
    return $model;
}