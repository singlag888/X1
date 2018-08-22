<?php

/**
 * 改进：
 * 1.应该可以单独设置任意http头，比如：
 * $wget->setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
 * // $this->_requestHeaders = array('Content-Type'=>'application/x-www-form-urlencoded');
 * $wget->setRequestHeader('X-Power-By', 'GFS');
 * // $this->_requestHeaders = array('Content-Type'=>'application/x-www-form-urlencoded', 'X-Power-By'=>'GFS');
 *
 * 2.file_get_contents()现在支持上下文，就是可以自定义http头，因此也可以用file_get_contents()来发送POST请求，理论上，可以发送任意http头
 * $context = array(
  'http' => array(
  'method' => $method,
  'header' => $header,
  'content' => $method == 'POST' ? $content : NULL,
  )
  );
  $this->_context = stream_context_create($context);
 *
 * 注意：http 1.1协议将多一些传输字节数信息，不利于解析
 */
class wget
{
    const DEFAULT_CHARSET = 'utf-8';

    public $connectTimeOut = 10;
    public $readTimeOut = 15;
    public $inCharset;
 // 指定网页字符集
    public $outCharset;
 // 输出字符集要求
    public $httpVersion = '1.0';
    // 1.0/1.1
    public $retry = 1;
    private $_fp = NULL;
    private $_errno = 0;
    private $_errstr = '';
    //  发出去的HTTP头
    private $_requestHeaders = array();
    private $_additionalRequestHeaders = array();
    private $_requestHeaderStream;
    private $_postData = '';
    private $_contentType = 'application/x-www-form-urlencoded';
    private $_responseHeaders = array();
    private $_responseHeaderStream;
    private $_responseBody = '';
    private $_responseHttpCode = -1;
    // runtime info
    private $_currentCharset;
    private static $_charset = array('auto', 'gb2312', 'gbk', 'gb18030', 'utf-8', 'utf-16', 'UCS-2', 'iso-8859-1');

    //private $_context = NULL;

    public function __construct($inCharset = 'auto', $outCharset = 'utf-8', $httpVersion = '1.0')
    {
        $this->init();
        $this->_requestHeaders = array(
            'Accept' => 'image/gif, image/jpeg, image/pjpeg, image/pjpeg, application/x-shockwave-flash, application/xaml+xml, application/vnd.ms-xpsdocument, application/x-ms-xbap, application/x-ms-application, application/vnd.ms-excel, application/vnd.ms-powerpoint, application/msword, */*',
            'Referer' => '',
            'Accept-Language' => 'zh-cn',
            'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Charset' => 'UTF-8,*',
            'Connection' => 'Close', // Keep-Alive 不推荐用
            'Cookie' => '',
            'Cache-Control' => 'no-cache', //private
            'Keep-Alive' => '115',
        );
        $this->setInCharset($inCharset);
        $this->setOutCharset($outCharset);
        $this->setHttpVersion($httpVersion);
    }

    private function init()
    {
        $this->_responseHeaders = array();
        $this->_responseBody = '';
        //$this->_postData = '';
        $this->_requestHeaderStream = $this->_responseHeaderStream = '';
        $this->error(0, '');
    }

    // , $referer = "", $cookie = "", $post = "", $addtional = ""
    public function getContents($fetchMode, $method, $url)
    {
        for ($i = 0; $i < $this->retry; $i++) {
            if ($i > 0) {
                log2("第 ".($i+1)." 次尝试getContents()...");
                sleep(rand(10 * ($i), 20 * ($i)));
            }

            switch ($fetchMode) {
                case 'FILE':
                    $flag = $this->getByFile('GET', $url);    // POST方法文档上表示支持，但我还没调试通过
                    break;
                case 'SOCKET':
                    $flag = $this->getBySocket($method, $url);
                    break;
                case 'CURL':
                    $flag = $this->getByCurl($method, $url);
                    break;
                default:
                    throw new Exception('please specifiy one method');
                    break;
            }

            if ($flag === true) {
                break;
            }
        }
        if ($flag === false) {
            return false;
        }

        // decompress
        if ($contentEncoding = strtolower($this->getResponseHeader('Content-Encoding'))) {
            if ($contentEncoding == 'gzip') {
                if (strlen($this->_responseBody) < 18 || $this->_responseBody{0} . $this->_responseBody{1} !== "\x1f\x8b") {
                    logdump("非GZIP格式:" . $this->_responseBody);
                    throw new Exception('Not GZIP format');
                }
                $string = substr($this->_responseBody, 10);
                $this->_responseBody = gzinflate($string);
            }
            elseif ($contentEncoding == 'deflate') {
                $this->_responseBody = gzinflate($this->_responseBody);
            }
            else {
                throw new Exception('Unrecognized content encoding');
            }
        }

        if ($this->inCharset == "auto") {
            if ($tmp = $this->getResponseHeader('charset')) {
                $currentCharset = strtolower($tmp);
            }
            elseif (preg_match('`charset=([\w-]+)`', $this->getResponseHeader('Content-Type'), $match)) {
                // Content-Type	text/html; charset=GBK
                $currentCharset = strtolower($match[1]);
            }
            else if (preg_match("`<meta.*charset\s*=([\w-]+)['\"].*>`Uim", $this->_responseBody, $match)) {
                // find charset from text <meta http-equiv="Content-Type" content="text/html; charset=GBK">
                $currentCharset = strtolower(trim($match[1]));
            }
            else {
                // 找不到字符集
                //throw new Exception('Unknown charset');
                $currentCharset = self::DEFAULT_CHARSET;
            }
        }
        else {
            $currentCharset = $this->inCharset;
        }

        if (!in_array($currentCharset, self::$_charset)) {
            throw new exception("Non-supported charset `$currentCharset`");
        }

        if ($currentCharset != $this->outCharset) {
            $this->_responseBody = mb_convert_encoding($this->_responseBody, $this->outCharset, $currentCharset);
        }

        return $this->_responseBody;
    }

    public function getByCurl($method, $url)
    {
        $tmpInfo = '';
        $curl = $this->addCurlHandle($method, $url, $this->_postData, isset($this->_requestHeaders['Cookie']) ? $this->_requestHeaders['Cookie'] : '', isset($this->_requestHeaders['Referer']) ? $this->_requestHeaders['Referer'] : '');
        $content = curl_exec($curl);
        if (curl_errno($curl)) {
            log2('getByCurl错误:' . curl_error($curl));
            return false;
        }

        $parts = explode("\x0d\x0a\x0d\x0a", $content, 2);
        $this->_responseHeaderStream = $parts[0];
        $this->_responseBody = $parts[1];
        $this->_responseHeaders = $this->parseResponseHeader($this->_responseHeaderStream);
        $httpcode = $this->getResponseHeader("httpcode");

        $info = curl_getinfo($curl);
        curl_close($curl);
        if ($httpcode != 200) {
            $this->close();
            return $this->error($httpcode, "Error httpcode $httpcode");
        }

        return true;
    }

    public function addCurlHandle($method, $url, $postData = '', $cookie = '', $referer = '')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->_requestHeaders['User-Agent']);
        if (!empty($referer)) {
            if ($referer == 'home') {
                $referer = $parts['scheme'] . "://" . $parts['host'];
            }
            else {
                $referer = $referer;
            }
            curl_setopt($curl, CURLOPT_REFERER, $referer);
        }

        if ($method == "POST") {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        }

        if (!empty($cookie)) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);    //不自动跳转302 因为有些登录跳转需要携带session，而跳转时CURL没有自动加上
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_HEADER, 1);  //返回http头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        //尝试解决 SSL certificate problem, verify that the CA cert is OK. Details: error:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
//            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, true);
//            curl_setopt($curl,CURLOPT_CAINFO,dirname(__FILE__).'/cacert.pem');

        return $curl;
    }

    // $params = array(0 => array('method'=>GET, 'url'=>URL, 'data'=>'','cookie'=>'','referer'=>'')
    public function getMultiContents($params)
    {
        $curls = $contents = array();
        $multiHandle = curl_multi_init();
        foreach ($params as $k => $v) {
            $curl = $this->addCurlHandle($v['method'], $v['url'], $v['postData'], $v['cookie'], $v['referer']);
            curl_multi_add_handle($multiHandle, $curl);
            $curls[$k] = $curl;
        }

        $isRunning = null;
        do {
            $status = curl_multi_exec($multiHandle, $isRunning);
        } while ($isRunning > 0);

        if ($status != CURLM_OK) {
            return false;
        }

        // retrieve data
        foreach ($curls as $k => $curl) {
            $info = curl_getinfo($curl);
            dump($info);
            if (($err = curl_error($curl)) == '') {
                $contents[$k] = curl_multi_getcontent($curl);
            }
            else {
                echo "第 $k 个发生错误：$err\n";
            }
            curl_multi_remove_handle($multiHandle, $curl);
            //curl_close($curl);
        }
        curl_multi_close($multiHandle);

        return $contents;
    }

    public function getByFile($method, $url)
    {
        $header = '';
        foreach ($this->_requestHeaders as $k => $v) {
            $header .= "$k: $v\r\n";
        }
        $params = array(
            'http' => array(
                'method' => $method,
                'header' => $header,
            )
        );
        if ($method == 'POST' && $this->_postData !== '') {
            $params['http']['content'] = $this->_postData;
        }
        //$context = stream_context_create($params);
        // 加$context后出现502 bad gateway错误，原因未知
        $context = NULL;
        if (($this->_responseBody = file_get_contents($url, FALSE, $context)) === false) {
            return false;
        }

        return true;
    }

    // The method must be uppercase instead of lowercase, or it will can't transfer the post data  !!!
    public function getBySocket($method, $url)
    {
        $this->init();

        if (!$this->connect($url)) {
            return $this->error(404, 'connect failed');
        }

        // 拼装http头。。。
        $requestHeaders = $this->_buildHttpHeaders($url, $method);
        $this->_requestHeaderStream = implode("\n", $requestHeaders);
        // 发送报头。。。
        foreach ($requestHeaders as $k => $v) {
            fputs($this->_fp, "$v\r\n");
        }

        // 取内容。。。
        // 先取http头。。。
        stream_set_timeout($this->_fp, $this->readTimeOut);
        while (!feof($this->_fp)) {
            $line = fgets($this->_fp, 8092);    // 测试一行内容>8092的情况
            if ($line == "\r\n") {
                break;
            }
            $this->_responseHeaderStream .= $line;
        }

        if (!$this->_responseHeaders = $this->parseResponseHeader($this->_responseHeaderStream)) {
            return false;
        }
        $this->_responseBody = '';
        $httpcode = $this->getResponseHeader("httpcode");
        if ($httpcode != 200) {
            $this->close();
            return $this->error($httpcode, "Error httpcode $httpcode");
        }

        $i = 0;
        while (!feof($this->_fp)) {
            $line = fread($this->_fp, 4096);
            $this->_responseBody .= $line;
            if (!strlen($line)) {
                break;
            }
        }

        //$this->_responseBody = stream_get_contents($this->_fp);
        $info = stream_get_meta_data($this->_fp);
        $this->close();
        if ($info['timed_out']) {
            return $this->error(405, 'get data timeout');
        }

        return true;
    }

    private function _buildHttpHeaders($url, $method)
    {
        $requestHeaders = array();
        $parts = parse_url($url);

        if (isset($parts['query'])) {
            $path = $parts['path'] . "?" . $parts['query'];
        }
        else {
            $path = !empty($parts['path']) ? $parts['path'] : '/';
        }

        $requestHeaders['Status'] = $method . " $path HTTP/{$this->httpVersion}";
        if (!empty($this->_requestHeaders['Accept'])) {
            $requestHeaders['Accept'] = "Accept: " . $this->_requestHeaders['Accept'];
        }

        if (!empty($this->_requestHeaders['Referer'])) {
            $referer = $this->_requestHeaders['Referer'];
        }
        else {
            $referer = $parts['scheme'] . "://" . $parts['host'];
        }
        $requestHeaders['Referer'] = "Referer: " . $referer;

        if (!empty($this->_requestHeaders['Accept-Language'])) {
            $requestHeaders['Accept-Language'] = "Accept-Language: " . $this->_requestHeaders['Accept-Language'];
        }
        if (!empty($this->_requestHeaders['User-Agent'])) {
            $requestHeaders['User-Agent'] = "User-Agent: " . $this->_requestHeaders['User-Agent'];
        }
        if ($method == "POST") {
            $requestHeaders['Content-Type'] = "Content-Type: {$this->_contentType}";
        }
        if (!empty($this->_requestHeaders['Accept-Encoding'])) {
            $requestHeaders['Accept-Encoding'] = "Accept-Encoding: " . $this->_requestHeaders['Accept-Encoding'];
        }

        $requestHeaders['Host'] = "Host: " . $parts['host'];

        if ($method == "POST") {
            $requestHeaders['Content-Length'] = "Content-Length: " . strlen($this->_postData);
        }
        if (!empty($this->_requestHeaders['Connection'])) {
            $requestHeaders['Connection'] = "Connection: " . $this->_requestHeaders['Connection'];
        }
        if (!empty($this->_requestHeaders['Cache-Control'])) {
            $requestHeaders['Cache-Control'] = "Cache-Control: " . $this->_requestHeaders['Cache-Control'];
        }
        if (!empty($this->_requestHeaders['Cookie'])) {
            $requestHeaders['Cookie'] = "Cookie: " . $this->_requestHeaders['Cookie'];
        }

        foreach ($this->getAdditionalRequestHeaders() as $k => $v) {
            $requestHeaders[$k] = "$k: $v";
        }

        $requestHeaders['End-Line'] = "";

        if ($method == "POST" && !empty($this->_postData)) {
            $requestHeaders['postData'] = $this->_postData;
        }

        return array_values($requestHeaders);
    }

    public function parseResponseHeader($header)
    {
        $result = array('charset' => '');
        // Content-Type: text/html; charset=GBK
        // Content-Type: text/html; charset=gbk
        // Content-Type: text/html;charset=ISO-8859-1

        if (preg_match('`content-type:.*;\s*charset=([\w-]{3,})`i', $header, $match)) {
            $result['charset'] = strtolower($match[1]);
        }
        $arr = explode("\n", trim($header, "\n"));

        $statusLine = array_shift($arr);
        if (!preg_match("@http/1\.\d+\s(\d{3})\s\w+@i", $statusLine, $match)) {
            return $this->error(400, 'http code error');
        }
        $this->setResponseHttpCode($match[1]);
        $result['httpcode'] = intval($match[1]);
        foreach ($arr as $k => $v) {
            $pos = strpos($v, ':');
            $result[trim(substr($v, 0, $pos))] = trim(substr($v, $pos + 1));
        }

        return $result;
    }

    final private function connect($url)
    {
        $parts = parse_url($url);
//dump(stream_get_transports());

        $host = $parts['host'];
        if ($parts['scheme'] == 'https') {
            $host = 'ssl://' . $parts['host'];
            $port = 443;
        }
        else {
            $port = isset($parts['port']) ? $parts['port'] : 80;
        }

        if (!$this->_fp = @fsockopen($host, $port, $errno, $errstr, $this->connectTimeOut)) {
            return $this->error(404, "connect host $host failed");
        }

        // verify we connected properly
        if (empty($this->_fp)) {
            return $this->error($errno, $errstr);
        }

        return true;
    }

    public function close()
    {
        if (!empty($this->_fp)) {
            // close the connection and cleanup
            fclose($this->_fp);
            $this->_fp = null;
        }
    }
    
    public function setRetry($times = 1)
    {
        $this->retry = $times;

        return $this;
    }

    public function setInCharset($charset)
    {
        if (in_array($charset, self::$_charset)) {
            $this->inCharset = $charset;
        }
        else {
            //$this->inCharset = self::DEFAULT_CHARSET;
            throw new exception("Non-supported charset `$charset`");
        }

        return $this;
    }

    public function setOutCharset($charset)
    {
        if (in_array($charset, self::$_charset)) {
            $this->outCharset = $charset;
        }
        else {
            //$this->outCharset = self::DEFAULT_CHARSET;
            throw new exception("Non-supported charset `$charset`");
        }

        return $this;
    }

    public function setHttpVersion($value)
    {
        if (in_array($value, array('1.0', '1.1'))) {
            $this->httpVersion = $value;
        }
        else {
            //$this->outCharset = self::DEFAULT_CHARSET;
            throw new exception("Non-supported httpVersion `$value`");
        }

        return $this;
    }

    public function setConnectTimeOut($connectTimeOut)
    {
        $this->connectTimeOut = $connectTimeOut;

        return $this;
    }

    public function setRequestHeaders($headers)
    {
        $this->_requestHeaders = $headers;
    }

    public function setRequestHeader($name, $value)
    {
        if ($value) {
            $this->_requestHeaders[$name] = $value;
        }
        else {
            unset($this->_requestHeaders[$name]);
        }

        return $this;
    }

    public function setAdditionalRequestHeader($name, $value)
    {
        if ($value) {
            $this->_additionalRequestHeaders[$name] = $value;
        }
        else {
            unset($this->_additionalRequestHeaders[$name]);
        }

        return $this;
    }

    public function setPostData($postData)
    {
        $this->_postData = $postData;
        return $this;
    }

    public function setContentType($value)
    {
        $this->_contentType = $value;
        return $this;
    }

    public function setCookie($value)
    {
        return $this->setRequestHeader('Cookie', $value);
    }

    public function setReferer($value)
    {
        return $this->setRequestHeader('Referer', $value);
    }

    public function setUserAgent($value)
    {
        return $this->setRequestHeader('User-Agent', $value);
    }

    public function setConnection($value)
    {
        return $this->setRequestHeader('Connection', $value);
    }

    public function setAcceptEncoding($value)
    {
        return $this->setRequestHeader('Accept-Encoding', $value);
    }

    public function error($errno, $errstr)
    {
        $this->_errno = $errno;
        $this->_errstr = $errstr;

        return false;
    }

    public function setResponseHeader($key, $value)
    {
        $this->_responseHeaders[$key] = $value;
    }

    public function getResponseHeaders()
    {
        return $this->_responseHeaders;
    }

    public function getResponseHeader($key, $defaultValue = '')
    {
        return isset($this->_responseHeaders[$key]) ? $this->_responseHeaders[$key] : $defaultValue;
    }

    public function getResponseHttpCode()
    {
        return $this->_responseHttpCode;
    }

    public function setResponseHttpCode($responseHttpCode)
    {
        $this->_responseHttpCode = $responseHttpCode;
    }

    public function getResponseHeaderStream()
    {
        return $this->_responseHeaderStream;
    }

    public function getResponseBody()
    {
        return $this->_responseBody;
    }

    public function getRequestHeaders()
    {
        return $this->_responseHeaders;
    }

    public function getAdditionalRequestHeaders()
    {
        return $this->_additionalRequestHeaders;
    }

    public function getRequestHeader($key, $defaultValue = '')
    {
        return isset($this->_requestHeaders[$key]) ? $this->_requestHeaders[$key] : $defaultValue;
    }

    public function getAdditionalRequestHeader($key, $defaultValue = '')
    {
        return isset($this->_additionalRequestHeaders[$key]) ? $this->_additionalRequestHeaders[$key] : $defaultValue;
    }

    public function getRequestHeaderStream()
    {
        return $this->_requestHeaderStream;
    }

    public function errno()
    {
        return $this->_errno;
    }

    public function errstr()
    {
        return $this->_errstr;
    }

}
?>
