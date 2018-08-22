<?php
logdump(file_get_contents('php://input'));
$info = array();

$datas = file_get_contents('php://input');

//$datas = 'trade_no=1000026151&extra_return_param=348&sign_type=RSA-S&notify_type=offline_notify&merchant_code=388003002017&order_no=348YIBAOTONGQQ201709252144263&trade_status=SUCCESS&sign=ePlnPbQCGINtZN9ag0NRRU6Od6vbR30RrE1P1uXlx8N6F1SfM%2FJ66A6TBD8rdbcC5BVocWO46tYV6oR5TfIqOvMpGRdb43Kgd2%2B3G03ZmbKrhMPhncO2I%2BSPRteJ%2BUmogriAG85zxq0m1xnTIE6R9h%2FenNXlZOH6CsURsu%2BwvmA%3D&order_amount=1&interface_version=V3.0&bank_seq_no=C1067620584&order_time=2017-09-25+21%3A44%3A27&notify_id=27e30d3e344047b3a5d89563d6c754d6&trade_time=2017-09-25+21%3A44%3A27';
//$datas = 'merchant_code=388003002017&notify_type=offline_notify&notify_id=98e4dd7beec84ffebc119425033e0fab&interface_version=V3.0&sign_type=RSA-S&sign=QDTN02aMVEUTawWri98tLxxw6163RtQnhIDwdwaAz%252Fi2c9WdCFqNpYAmK6Vn9URL8%252B5fFnHHk7lXNH5%252FyU7TWpN9cBdlBX0CSAovjI4qUFFj0H0k8plCZFdVKYM4Q843d7CQYksJz%252F0Fkiv3IUcBz22Ocns0UxlnD%252Fr325cV8Ag%253D&order_no=344YIBAOTONGWX201709251632237&order_time=2017-09-25%2B16%253A32%253A24&order_amount=12&extra_return_param=344&trade_no=1000025433&trade_time=2017-09-25%2B16%253A32%253A24&trade_status=SUCCESS&bank_seq_no=C1067535162';
//>>测试数据
//$data = [
//	'trade_no' 			=> '1000026151',  //>>平台交易号
//	'extra_return_param'=> '348',         //>>回传参数
//	'sign_type' 		=> 'RSA-S',		//>>签名方式
//	'notify_type' 		=> 'offline_notify',  //>>回调类型
//	'merchant_code' 	=> '388003002017',    //>>商户号
//	'order_no' 			=> '348YIBAOTONGQQ201709252144263',//>>订单号
//	'trade_status' 		=> 'SUCCESS',		//>>交易状态
//	'sign' 				=> 'ePlnPbQCGINtZN9ag0NRRU6Od6vbR30RrE1P1uXlx8N6F1SfM%2FJ66A6TBD8rdbcC5BVocWO46tYV6oR5TfIqOvMpGRdb43Kgd2%2B3G03ZmbKrhMPhncO2I%2BSPRteJ%2BUmogriAG85zxq0m1xnTIE6R9h%2FenNXlZOH6CsURsu%2BwvmA%3D',//>>签名
//	'order_amount' 		=> '1',			//>>订单金额
//	'interface_version' => 'V3.0',		//>>版本 固定号
//	'bank_seq_no' 		=> 'C1067620584',   //>>网银交易流水号
//	'order_time' 		=> '2017-09-25+21%3A44%3A27',//>>订单时间
//	'notify_id' 		=> '27e30d3e344047b3a5d89563d6c754d6',//>>回调id
//	'trade_time' 		=> '2017-09-25+21%3A44%3A27', //>>交易时间
//];


//$data = [
//	'merchant_code' 	=> '388003002017',    //>>商户号
//	'notify_type' 		=> 'offline_notify',  //>>回调类型
//	'notify_id' 		=> '98e4dd7beec84ffebc119425033e0fab',//>>回调id
//	'interface_version' => 'V3.0',		//>>版本 固定号
//	'sign_type' 		=> 'RSA-S',		//>>签名方式
//	'sign' 				=> 'QDTN02aMVEUTawWri98tLxxw6163RtQnhIDwdwaAz%2Fi2c9WdCFqNpYAmK6Vn9URL8%2B5fFnHHk7lXNH5%2FyU7TWpN9cBdlBX0CSAovjI4qUFFj0H0k8plCZFdVKYM4Q843d7CQYksJz%2F0Fkiv3IUcBz22Ocns0UxlnD%2Fr325cV8Ag%3D',//>>签名
//	'order_no' 			=> '344YIBAOTONGWX201709251632237',//>>订单号
//	'order_time' 		=> '2017-09-25+16%3A32%3A24',//>>订单时间
//	'order_amount' 		=> '12',			//>>订单金额
//	'extra_return_param'=> '344',         //>>回传参数
//	'trade_no' 			=> '1000025433',  //>>平台交易号
//	'trade_time' 		=> '2017-09-25+16%3A32%3A24', //>>交易时间
//	'trade_status' 		=> 'SUCCESS',		//>>交易状态
//	'bank_seq_no' 		=> 'C1067535162',   //>>网银交易流水号
//];
//>>处理拿到的数据



$result =  curlPostData(getRootUrl() . "/?c=pay&a=yibaotongBack", $datas, $info);
file_put_contents('yibaotongBackNotify.log', $datas . "\n", LOCK_EX | FILE_APPEND);
file_put_contents('yibaotongBackNotify.log', $result . "\n", LOCK_EX | FILE_APPEND);
echo $result;

function curlPostData($url, $data, &$info)
{
	// 模拟提交数据函数
	$curl = curl_init(); // 启动一个CURL会话
	$urlInfo =  explode('://', $url);

	if (strpos($urlInfo[1], ':') === false) {
		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
	} else{
		$port =  explode('/', explode(':', $urlInfo[1])[1])[0];
		curl_setopt($curl, CURLOPT_URL, str_replace(':' . $port, '', $url)); // 要访问的地址
		curl_setopt($curl, CURLOPT_PORT, $port); // 要访问的地址
	} // 要访问的地址
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
	$log_file = 'yibaotongBack.log';

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