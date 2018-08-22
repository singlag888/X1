<?php

/**
 * 响应对象
 */
class response
{

    static protected $statusTexts = array(
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '306' => '(Unused)',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
    );

    function header_304()
    {
        myheader('Content-Type:');
        myheader($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304);

        exit;
    }

    function header_404($content = '')
    {
        myheader('Cache-Control: no-cache');
        myheader($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);

        exit("<h1>404 - Not Found" . $content . "</h1>\r\n");
    }

    function header_503($content = '')
    {
        myheader('Cache-Control: no-cache');
        myheader($_SERVER['SERVER_PROTOCOL'] . ' 503 - Service Temporarily Unavailable', true, 503);

        if ($content) {
            echo $content;
        }
        else {
            echo "<h1>503 - Service Temporarily Unavailable</h1>\r\n";
        }

        exit;
    }

    function redirect($url, $permanently = false)
    {
        if ($permanently === true) {
            myheader('Location: ' . $url, true, 301);
        }
        else {
            myheader('Location: ' . $url, true, 302);
        }

        exit;
    }

    function myheader($string, $replace = true, $http_response_code = 0)
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
        }
        else {
            @header($string, $replace, $http_response_code);
        }
    }

    function expires_header($source_time, $expire_time, $allow_304_header = false)
    {
        myheader('Cache-Control: max-age=' . $expire_time, true);

        /* 累加操作，避免因为 source_time+expire_time 小于当前时间，导致不能缓存 */
        while ($source_time + $expire_time <= REQUEST_TIME) {
            $expire_time += $expire_time;
        }

        myheader('Expires: ' . http_date($source_time + $expire_time));

        $last_modified = http_date($source_time);
        if ($allow_304_header === true && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) === true && strpos($_SERVER['HTTP_IF_MODIFIED_SINCE'], $last_modified) === 0) {
            header_304();
            exit;
        }

        myheader('Last-Modified: ' . $last_modified);
    }

    function outputEtag($string, $allow_304_header = false)
    {
        $etag = 'apf_' . abs(crc32($string));

        if ($allow_304_header === true && isset($_SERVER['HTTP_IF_NONE_MATCH']) === true && $etag === $_SERVER['HTTP_IF_NONE_MATCH']) {
            header_304();
            exit;
        }

        myheader('ETag: ' . $etag);
    }

    function outputJs($js)
    {
        $content = @file_get_contents($js);
        if ($content === false) {
            header_404(', errcode: OUTPUT_JS1');

            return false;
        }

        myheader('Content-Type: application/x-javascript; charset=utf-8');

        /* 输出文件大小 */
        myheader('Content-Length: ' . strlen($content));

        /* header 输出 */
        expires_header(filemtime($js), 3600 * 24 * 365);

        /* ETAG 输出 */
        output_etag($content);

        /* 输出文件内容 */
        echo $content;
    }

    function outputAttachment($content, $filename, $content_type = '', $no_cache = false)
    {
        /* MSIE 需要进行一下编码处理 */
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
            myheader('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        }
        else {
            myheader('Content-Disposition: attachment; filename="' . $filename . '"');
        }

        if ($content_type === '') {
            $content_type = 'application/octet-stream';
        }
        myheader('Content-Type: ' . $content_type);

        /* 不允许客户端缓存 */
        if ($no_cache === true) {
            myheader('Cache-Control: no-store');
        }
        else {
            /* 输出文件大小 */
            myheader('Content-Length: ' . strlen($content));

            /* ETAG 输出 */
            output_etag($content);
        }

        echo $content;
    }

    /**
     * Sets response status code.
     *
     * @param string $code  HTTP status code
     * @param string $name  HTTP status text
     *
     */
    public static function statusCode($code, $text = null)
    {
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        $text = (null === $text) ? self::$statusTexts[$code] : $text;
        $status = "$protocol $code $text";
        header($status);
    }

    /**
     * Set cookie
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param string $secure
     * @param boolean $httpOnly
     * @return boolean
     */
    public static function cookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = false, $httpOnly = false)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Set response charset
     *
     * @param string $enc
     * @param string $type
     */
    public static function charset($enc = 'UTF-8', $type = 'text/html')
    {
        header("Content-Type:$type;charset=$enc");
    }

    /**
     * Redirect to other url
     *
     * @param string $url
     */
    public static function redirect($url, $code = 302)
    {
        header("Location:$url", true, $code);
        exit();
    }

    /**
     * Alert
     *
     * @param string $text
     * @param string $url
     */
    public static function alert($text, $url = null)
    {
        $text = addslashes($text);
        echo "\n<script type=\"text/javascript\">\nalert(\"$text\");\n";
        if ($url) {
            echo "window.location.href=\"$url\";\n";
        }
        echo "</script>\n";
    }

    /**
     * Forces the user's browser not to cache the results of the current request.
     *
     * @return void
     * @access protected
     * @link http://book.cakephp.org/view/431/disableCache
     */
    public static function disableBrowserCache()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Etag
     *
     * Set or check etag
     * @param string $etag
     * @param boolean $notModifiedExit
     */
    public static function etag($etag, $notModifiedExit = true)
    {
        if ($notModifiedExit && isset($_SERVER['HTTP_IF_NONE_MATCH']) && $etag == $_SERVER['HTTP_IF_NONE_MATCH']) {
            self::statusCode('304');
            exit();
        }
        header("Etag: $etag");
    }

    /**
     * Last modified
     *
     * @param int $modifiedTime
     * @param boolean $notModifiedExit
     */
    public static function lastModified($modifiedTime, $notModifiedExit = true)
    {
        $modifiedTime = date('D, d M Y H:i:s \G\M\T', $modifiedTime);
        if ($notModifiedExit && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $modifiedTime == $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
            self::statusCode('304');
            exit();
        }
        header("Last-Modified: $modifiedTime");
    }

    /**
     * Expires
     *
     * @param int $seconds
     */
    public static function expires($seconds = 1800)
    {
        $time = date('D, d M Y H:i:s', time() + $seconds) . ' GMT';
        header("Expires: $time");
    }

}