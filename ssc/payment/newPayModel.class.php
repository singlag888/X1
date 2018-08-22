<?php

namespace ssc\payment;

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class newPayModel
{
    public $_error = '支付参数配置异常,请联系客服!';//错误信息
    private $_lockedNames = [];//用于存储redis锁的名字
    //文件锁存放路径
    private $path='';
    //文件句柄
    private $fp='';
    //锁文件
    private $lockFile='';
    /**
     * 去支付
     */
    public function run()
    {
    }

    /**
     * 外部回调操作.
     */
    public function callback()
    {
    }
    public function  orderNumber()
    {}
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


    public function cdn(){
        if (file_exists(ROOT_PATH . 'cdn.xml')) {
            $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
            $imgCdnUrl = (string)$xml->public_pay;
        } else {
            $imgCdnUrl = config::getConfig('site_main_domain');
        }

        return $imgCdnUrl;
    }
    /**
     * 文件锁-加锁
     */
    public function lock($name){
        $this->path=LOG_PATH.'lock/';
        $this->lockFile=$this->path.md5($name).'.lock';
        if (file_exists($this->path) === false) {
            if (@mkdir(  $this->path, 0777, true) === false) {

            }
        }
        $this->fp=fopen($this->lockFile,'a+');
        if($this->fp===false){
            return false;
        }
        return flock($this->fp,LOCK_EX);//获取独占锁
    }

    /**
     * 文件锁-解锁
     */
    public function unlock(){
        if($this->fp!==false){
            @flock($this->fp,LOCK_UN);
            clearstatcache();
        }
        @fclose($this->fp);
        @unlink($this->lockFile);
    }


    /**
     * 1.加锁防止并发回掉
     * 2.根据订单号查询表是否存在
     * 3.回掉处理自己的订单相关表
     * @param string $authcode_key 回掉地址加密解密 钥匙
     * @param string $success_code 第三方需要的回掉成功标识 输出
     * @param array $order  订单信息
     * @param string $payName 支付名称
     * @return bool
     */
    public function bak($authcode_key = '', $success_code = '', $order = array(), $tradeNo = 0 ,$payName = '')
    {
        $order['requestURI'] = $this->authcode(url('fin', 'receivePayResult'), 'ENCODE', $authcode_key);
        $orderNo = $order['shop_order_num'];
        $orderAmount = $order['amount'];

        if(!$this->lock($payName.$orderNo))
        {
            return false;
        }
        if (empty($order)) {
            $this->unlock();
            $this->logdump($payName,'回掉时根据订单号' . $orderNo . '未找到订单');
            exit;
        }
        $urlCode = $this->authcode($order['requestURI'], 'DECODE', $authcode_key);
        if (empty($urlCode)) {
            $this->unlock();
            $this->logdump($payName,'内部requestURI回调地址没有解析出来');
            exit;
        }
        $this->logdump($payName, '回调地址：' . $urlCode);
        // $depositOrder = \deposits::getItemByOrderNum($orderNo, 2);
        $onlinepayment = \pay::getItemByOrderNumber($orderNo);
        if ($order['status'] == '8' && $onlinepayment['status'] == 8) {
            $this->unlock();
            echo $success_code;
            die();
        }
        $random_benefit = \config::getConfig('random_benefit');
        if (empty($orderAmount) || $order['amount'] - $orderAmount > $random_benefit) {
            $this->unlock();
            $this->logdump($payName,"订单异常：{$order['amount']}与{$orderAmount}金额不相等");
            die;
        }
        $now = date('Y-m-d H:i:s');

        $update_data = array(
            'status' => 8,
            'pay_time' => $now,
            'trade_number' => $tradeNo,
        );
        \pay::updateItem($orderNo, $update_data);
        //用CURL  向游戏提交数据
        $data = "verify=formal&user_id=" . $order['user_id'] . "&username=" . $order['username'] . "&amount=" . $order['amount'] . "&order_num=" . $tradeNo . "&shop_order_num=" . $orderNo . "&card_id=" . $order['deposit_card_id'] . "&bank_id="
            . $order['deposit_bank_id'] . "&pay_time=" . $now . "&source_fee=" . $onlinepayment['source_fee'] . "&target_fee=" . $order['target_fee'];

//         $this->logdump(var_export($data, TRUE));
        $info = array();

        $call_back_result = $this->curlPost($urlCode, $data, $info);

        if (!is_numeric($call_back_result)) {
            $times = 0;
            while ($times < 3) {
                sleep(60);

                $call_back_result = $this->curlPost($urlCode, $data, $info);
                $this->logdump($payName,"第一次执行失败后 重发第" . ($times + 1) . "次 用户名{$order['username']} 订单{$order['trade_number']} 结果call_back_result={$call_back_result}");
                if (is_numeric($call_back_result)) {
                    break;
                }
                $times++;
            }
        }

        if ($call_back_result != "" && is_numeric($call_back_result)) {
            $update_data = array(
                'call_back_result' => $call_back_result,
            );

            if (!\pay::updateItem($orderNo, $update_data)) {
                //如果商城的数据库修改失败需要记录，然后手动更新
                $data = array(
                    'user_id' => $order['user_id'],
                    'order_number' => $orderNo,
                    'trade_number' => $tradeNo,
                );

                \pay::addErrorItem($data);
            }
        }


        //但是商城还是要返回成功给第三方的
        $this->logdump($payName, $success_code . "!用户名{$order['username']} 交易订单{$tradeNo} 金额{$order['amount']} 回调结果 {$call_back_result}");

        if ($call_back_result == '8' || $call_back_result == '9') {
            $this->unlock();
            echo $success_code;
        }

        die;
    }

    /**
     * 本地回掉地址的加密解密方法
     * @param $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @return bool|string
     */
    public function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
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
     * 数组转为xml
     * @param $array
     * @return string
     */
    public function toXml($array)
    {
        $xml = '<xml>';
        forEach ($array as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * xml转为数组
     * @param $xml
     * @return array|null
     */
    public function xmlTonewArray($xml)
    {
        /**
         * @var \SimpleXMLIterator $xmlDoc
         */
        $ret = array();
        if ($xml instanceOf \SimpleXMLElement) {
            $xmlDoc = $xml;
        } else {
            $xmlDoc = simplexml_load_string($xml, 'SimpleXMLIterator');
            if (!$xmlDoc) {      // xml字符串格式有问题
                return null;
            }
        }

        for ($xmlDoc->rewind(); $xmlDoc->valid(); $xmlDoc->next()) {
            $key = $xmlDoc->key();       // 获取标签名
            $val = $xmlDoc->current();   // 获取当前标签
            if ($xmlDoc->hasChildren()) {     // 如果有子元素
                $ret[$key] = $this->xmlToArray($val);  // 子元素变量递归处理返回
            } else {
                $ret[$key] = (string)$val;
            }
        }
        return $ret;
    }

    /**
     * curl模拟post请求
     * @param string $url 请求地址
     * @param mixed $data 请求参数
     * @param int $is_json 是否是json数据
     * @param string $errorLogName curl请求错误日志文件名
     * @param int $is_xml 是否是xml数据
     * @return mixed 返回结果
     */
    public function curlPost($url, $data, $errorLogName = 'curlError', $is_json = 0, $is_xml = 0,$shop_url = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if(!empty($shop_url)) {
            curl_setopt($ch, CURLOPT_REFERER, $shop_url); //伪造来路页面
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300); //附加
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); //附加


        if ($is_json == 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data))
            );
        }
        if ($is_xml == 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/xml;charset=utf-8',
                'Content-Length:' . strlen($data)
            ));
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $return = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->logdump($errorLogName.'CURLErrno' ,$url.':'.curl_error($ch).curl_error($ch)); //捕抓异常
        }
        curl_close($ch);
        return $return;
    }

    /**
     *  curl模拟get请求
     * @param string $url 请求地址
     * @param mixed $info 请求参数
     * @param string $errorLogName 文件日志名称
     * @return mixed 返回数据
     */
    public function curlGet($url, &$info, $errorLogName = 'curlError')
    {
        // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            $this->logdump($errorLogName, curl_error($curl));
            die();
        }
        $info = curl_getinfo($curl);
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    /**
     * 生成充值订单-调度方法
     * 1.生成充值记录订单
     * 2.生成在线支付订单
     * @param int $user_id
     * @param string $username
     * @param int $top_id
     * @param int $amount
     * @param $bank_id
     * @param $card_id
     * @param $shop_order_num
     * @param $orderStatus
     * @param $requestURI
     * @param int $usage
     * @return bool
     */
    public function newOrder($user_id = 0, $username = '', $top_id = 0, $amount = 0, $bank_id, $card_id, $shop_order_num, $orderStatus, $requestURI, $usage = 0)
    {
        $GLOBALS['db']->startTransaction();
        $res = $this->createOrder($user_id, $username, $top_id, $amount, $bank_id, $card_id, $shop_order_num, $orderStatus, $usage);

        if (!$res) {
            $this->_error = '订单创建不成功1!';
            $GLOBALS['db']->rollback();
            return false;
        }
        $res = $this->createOnlinePayment($shop_order_num, $user_id, $username, $amount, $requestURI, $bank_id, $card_id);
        if (!$res) {
            $this->_error = '订单创建不成功2!';
            $GLOBALS['db']->rollback();
            return false;
        }
        $GLOBALS['db']->commit();
        return true;
    }

    /**
     * 生成充值订单号
     * @param int $user_id 用户id
     * @param string $username 用户姓名
     * @param int $top_id 所属总代id 总代填自己id
     * @param int $amount 订单金额
     * @param int $bank_id  银行id
     * @param int $card_id 卡id
     * @param string $shop_order_num 商城交易号 我们自己的订单号
     * @param int $orderStatus 订单处理状态
     * @param int $usage
     * @return bool
     */
    private function createOrder($user_id = 0, $username = '', $top_id = 0, $amount = 0, $bank_id, $card_id, $shop_order_num, $orderStatus, $usage = 0)
    {
        $data = array(
            'user_id' => $user_id,
            'username' => $username,
            'top_id' => $top_id,
            'player_pay_time' => date("Y-m-d H:i:s"),
            'player_card_name' => '',
            'real_name' => '',
            'trade_type' => $usage,
            'amount' => $amount,
            'deposit_bank_id' => $bank_id,
            'deposit_card_id' => $card_id,
            'shop_order_num' => $shop_order_num,
            'create_time' => date("Y-m-d H:i:s"),
            'status' => $orderStatus, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
            'local_order_num'=>date("YmdHis") . strtoupper(uniqid('', false)),//本地订单号 用于展示给用户
        );
        if (\deposits::addItem($data)) {
            return true;
        } else {
            return false;
        }
    }

    private function createOnlinePayment($shop_order_num, $user_id = 0, $username = '', $amount = 0, $requestURI, $bank_id, $card_id)
    {
        $data = array(
            'order_number' => $shop_order_num,
            'user_id' => $user_id,
            'username' => $username,
            'amount' => $amount,
            'pay_time' => date('Y-m-d H:i:s'),
            'source' => $_SERVER['HTTP_HOST'],
            'requestURI' => $requestURI,
            'card_id' => $card_id,
            'bank_id' => $bank_id,
        );
        if (\pay::addItem($data)) {
            return true;
        } else {
            return false;
        }
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
        $res =  $GLOBALS['redis'] -> setnx($redisKey,$expireAt);//设定锁
        if(!$res)
        {
            return true;//表示获取到了锁 不在进行锁的设定
        }
        while (true)
        {

            if($res)
            {
                $GLOBALS['redis'] -> expire($redisKey,$expireAt);//设定key的失效时间
                $this->_lockedNames[$name] = $expireAt;//将锁标识房间数组里
                return false;//表示设置锁
            }
            $ttl = $GLOBALS['redis'] ->ttl($redisKey);//获取key的剩余过期时间(秒)
            //ttl小于0表示key没有设置生存时间（key是不会不存在的，因为前面setnx会自动创建）
            //如果出现这种状况，那就是进程的某个实例setnx成功后 crash 导致紧跟着的expire没有被调用 这时可以直接设置expire并把锁纳为己用
            if($ttl < 0)
            {
                $GLOBALS['redis'] -> set($redisKey,$expireAt);//设定key的失效时间
                $this->_lockedNames[$name] = $expireAt;//将锁标识房间数组里
                return false;
            }
            //如果没有设置锁的失败时间 或者已经超过最大等待时间,结束循环
            if($timeout <= 0 || $timeoutAt < microtime(true))
            {
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
        if($this->isLocking($name))
        {
            //删除锁
            if($GLOBALS['redis'] -> deleteKey("Lock:$name"))
            {
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
        if(isset($this->_lockedNames['redis'][$name]))
        {
            return $this->_lockedNames['redis'][$name] =   $GLOBALS['redis']->get("Lock:$name");
        }
        return false;
    }

    /**
     * 格式化RSA的key格式-多种支付会使用到
     * @param $key
     * @param string $type
     * @return string
     */
    public function formatRSAKey($key, $type = 'public'){
        $key = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $key);
        $key = str_replace("-----END RSA PRIVATE KEY-----", "", $key);
        $key = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        $key = str_replace("-----END PUBLIC KEY-----", "", $key);
        $key = $this->_trimAll($key);

        if ($type == 'public') {
            $begin = "-----BEGIN RSA PUBLIC KEY-----\n";
            $end = "-----END RSA PUBLIC KEY-----";
        } else {
            $begin = "-----BEGIN RSA PRIVATE KEY-----\n";
            $end = "-----END RSA PRIVATE KEY-----";
        }

        $key = chunk_split($key, 64, "\n");

        return $begin . $key . $end;
    }

    private function _trimAll($str)
    {
        $qian = array(" ", " ", "\t", "\n", "\r");
        $hou = array("", "", "", "", "");
        return str_replace($qian, $hou, $str);
    }

    /**
     * 获取用户真实ip地址
     * @return array|false|null|string
     */
    public function getClientIp()
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

    /**
     * 判断字符串是否是xml 是返回数组 不是 返回false
     * @param $str
     * @return bool|mixed
     */
    function xml_parser($str){
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$str,true)){
            xml_parser_free($xml_parser);
            return false;
        }else {
            return (json_decode(json_encode(simplexml_load_string($str)),true));
        }
    }

    /**
     * 检查支付类型
     * @param  string $area_flag
     * @return bool|string
     */
    public function getProtocol ($area_flag)
    {
        if ($area_flag == 1)//来源-wap
        {
            $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        } elseif ($area_flag == 2)//来源-app
        {
            $protocol = 'https';
        } else {
            $this->_error = '来源标识出错!';
            return false;
        }
        return $protocol;
    }
    /**
     * 生成随机
     * @param string $length 长度
     * @return string $key 返回的值
     * @author L
     */
    public function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = '';
        for ($i = 0; $i < $length; ++$i) {
            $key .= $pattern{mt_rand(0, 62)};    //生成php随机数
        }
        return $key;
    }

    public function xmlToArrayOrObj($xml,$is_array = 1)
    {
        $xml =simplexml_load_string($xml);
        if($is_array)
        {
            $obj= json_decode(json_encode($xml),true);
        }else{
            $obj= json_decode(json_encode($xml));
        }
        return $obj;
    }


}