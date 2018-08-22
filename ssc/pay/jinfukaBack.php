<?php

$info = array();
$result =  curlPostData(getRootUrl() . "/?c=pay&a=jinfukaBack", http_build_query($_POST), $info);
echo $result;

function curlPostData($url, $data, &$info)
{
    // 模拟提交数据函数
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
    );
    curl_setopt($curl, CURLOPT_ENCODING, "gzip");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 60); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        logdump('CURL Errno' . curl_error($curl)); //捕抓异常
    }
    $info = curl_getinfo($curl);
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}

function getRootUrl()
{
    $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
}

function logdump()
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
    $log_file = 'jinfukaBack.log';

    return log_write_file($log_file, $str . "\n", FILE_APPEND);
}

function log_write_file($file, $content, $flag = 0)
{
    $pathinfo = pathinfo($file);

    if (!empty($pathinfo['dirname'])) {
        if (file_exists($pathinfo['dirname']) === false) {
            if (@mkdir($pathinfo['dirname'], 0777, true) === false) {
                return false;
            }
        }
    }
    if ($flag === FILE_APPEND) {
        return file_put_contents($file, $content . "\n", LOCK_EX | FILE_APPEND);
    } else {
        return file_put_contents($file, $content . "\n", LOCK_EX);
    }
}