<?php
/**
 * F86WAP
 * @author stone
 */

namespace sscapp\payment;


class F86WAP extends payModel
{

    private $_merchno = '';//商户ID12
    private $_notifyUrl = ''; //异步回掉地址1
    private $_key = '';//商户key2
    private $_requestUrl = 'https://gw.f86pay.com/native/';//第三方请求地址
    private $_sameBackUrl = '';
    private $_cerno = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1023' . date('YmdHis') . rand(100, 999) . rand(100, 999) . rand(1, 9);
        // TODO: Implement orderNumber() method.
    }


    /**
     * 执行
     * @param array $data 参数
     * @return mixed 返回数据
     */
    public function run($data = [])
    {

        $card_info = isset($data['card_info']) ? $data['card_info'] : '';
        if (empty($card_info)) {
            $this->_error = '卡信息出错,请联系客服!';
            return false;
        }
        if (empty($card_info['mer_key'])) {
            $this->_error = '配置有误,请联系客服1!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服2!';
            return false;
        }
        $bankId = $card_info['bank_id'];
        if (empty($bankId)) {
            $this->_error = '配置有误,请联系客服3!';
            return false;
        }
        $this->_merchno = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $this->_cerno = $card_info['ukeypwd'];
        $className = explode('\\', __CLASS__);
        $className = end($className);
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $protocol = $this->getProtocol($area_flag);
        $this->_notifyUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . $className;
        $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . $className;
        $netway = $card_info['netway'];
        if ($netway == "WX") {
            $service_type = "WECHAT_QRCODE_PAY";
        } elseif($netway == "QQ") {
            $service_type = "QQ_QRCODE_PAY";
        } elseif($netway == "QQ_WAP") {
            $service_type = "QQ_WAP_PAY";
        } elseif($netway == "KJ") {
            $service_type = "H5_UNION_PAY";
        } else {
            $this->_error = '此卡暂不支持该充值方式!';
            return false;
        }
        $dataTmp = array(
            'merchantNo' => $this->_merchno,
            'outTradeNo' => $data['ordernumber'],
            'amount' => $data['money']*100,
            'content' => $service_type,
            'returnURL' => $this->_sameBackUrl,
            'callbackURL' => $this->_notifyUrl,
        );
        $cerno = $this->_cerno;
        $signMethod = "MD5";
        $response = $this->F86request("com.opentech.cloud.easypay.trade.create", "0.0.1", $dataTmp,$files = NULL,$this->_requestUrl,$cerno,$this->_key,$signMethod);
        $responseqrcode = json_decode($response['data'],true);
        if(!isset($responseqrcode['paymentInfo'])){
            $this->_error = '返回值为空!';
            return false;
        }
        $f86url = $responseqrcode['paymentInfo'];
        if ($response != null && $response['errorCode'] == 'SUCCEED'){
            if (strstr($netway, "WAP") > -1 || $netway == "KJ"){

                $str = <<<HTML
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>跳转中</title>
</head>
<body >
<form name="diy" id="diy" action="$f86url"  method="post">
   
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;


                return ['code' => 'html', 'html' => $str];
            }else{
                return ['code' => 'qrcode', 'url' => $f86url];
            }
        }else{
            $this->_error = $response['msg'];
            return false;
        }
        // TODO: Implement run() method.
    }


    public 	function F86request($apiName, $apiVersion, $parameters, $files = NULL,$gateway,$certNo,$privkey,$signMethod) {
        $url = $gateway . $apiName . "/" . $apiVersion;
        $httpHeaders = array();
        $headers = array();
        $headers["x-oapi-pv"] = "x-oapi-pv";
        $headers["x-oapi-sdkv"] = "x-oapi-sdkv";
        $headers["x-oapi-sk"] = $certNo;
        $headers["x-oapi-sm"] = $signMethod;

        $ch = curl_init($url);

        //curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:7777');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $body = NULL;
        if(!isset($files) || count($files) == 0) {
            $body = json_encode($parameters);
            $headers["x-oapi-sign"] = $this->F86sign($url, $headers, $body,$privkey);
            array_push($httpHeaders, "Content-Type:application/json;charset=utf-8");
        } else {
            $headers["x-oapi-sign"] = $this->F86sign($url, $headers, null,$privkey);

            $boundary = '-------------' . md5(time());
            array_push($httpHeaders, 'Content-Type: multipart/form-data; boundary=' . $boundary);

            // 参数
            $body = '';
            foreach ($parameters as $key => $value) {
                $body .= "--" . $boundary . "\r\n"
                    . 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n"
                    . $value . "\r\n";
            }

            // 文件
            foreach ($files as $key => $file) {
                $body .= "--" . $boundary . "\r\n";
                $body .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $file['fileName'] . "\" \r\n";
                $contentType = isset($file['contentType']) ? $file['contentType'] : 'application/octet-stream';
                $body .= 'Content-Type: ' . $contentType . "\r\n\r\n";
                $body .= $file['content'] . "\r\n";
            }

            $body .= "--" . $boundary . "--";
        }

        foreach ($headers as $key => $value) {
            array_push($httpHeaders, $key . ':' . $value);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);


        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            //echo 'HTTP Request Error: ' . curl_error($ch);
            return;
        }


        $parsedResponse = $this->parse_http_response($ch, $response);
        $httpStatus = @$parsedResponse["httpStatus"];
        $headers = $parsedResponse["headers"];
        $body = $parsedResponse["body"];

        //print_r($parsedResponse);

        curl_close($ch);


        //printf("url: %s\n", $url);
        //print_r($headers);
        //printf("body: %s\n", $body);
        //print_r($parameters);

        $result = array(
            "errorCode" => $headers["x-oapi-error-code"],
            "msg" => @$headers["x-oapi-msg"],
            "data" => $body
        );

        return $result;
    }

    public function parse_http_response($ch, $response) {
        $headers = array();

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $header_text = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        //printf("header_text: %s\n", $header_text);

        $status = 0;

        foreach (explode("\r\n", $header_text) as $i => $line) {
            if(!$line) {
                continue;
            }
            if ($i === 0) {
                list ($protocol, $status) = explode(' ', $line);
                $status = $status;
            } else {
                list ($key, $value) = explode(': ', $line);
                $headers[strtolower($key)] = urldecode($value);
            }
        }

        //print_r($headers);

        return array("status" => $status, "headers" => $headers, "body" => $body);
    }


    public function F86sign($url, $headers, $body,$privateKey) {

        ksort($headers);

        $str = $url;
        foreach ($headers as $key => $value) {
            if(strpos($key, "x-oapi-") === 0) {
                $str = $str . "&" . $key . "=" . $value ;
            }
        }

        if(isset($body)) {
            $str = $str . "&" . $body;
        }

        $str = $str . "&" . $privateKey;

        return strtoupper(md5($str));

    }


    /**
     * 支付回掉入口
     * 1.回掉验签
     * 2.执行本地逻辑
     * @param array $response 第三方回掉返回数据
     * @param int $is_raw 返回数据结构形式 主要表示是否是原始数据流 0不是 1是
     */
    public function callback($response = '', $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('changchengfu', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $merchno = isset($response['merchno']) ? $response['merchno'] : '';//返回的状态码
        if (empty($merchno)) {
            $this->logdump('changchengfu', '回掉商户号为空:' . $response);
            exit;
        }
        $amount = isset($response['amount']) ? $response['amount'] : 0;//订单号
        if ($amount == 0) {
            $this->logdump('changchengfu', '没有反回支付金额');
            exit;
        }

        $traceno = isset($response['traceno']) ? $response['traceno'] : '';//订单号
        if (empty($traceno)) {
            $this->logdump('changchengfu', '没有反回支付处理信息');
            exit;
        }
        $orderno = isset($response['orderno']) ? $response['orderno'] : '';
        if (empty($orderno)) {
            $this->logdump('changchengfu', '没有返回订单号');
            exit;
        }

        $status = isset($response['status']) ? $response['status'] : '';//1:支付成功，非1为支付失败
        if (empty($status)) {
            $this->logdump('changchengfu', '回掉(status为空)');
            exit;
        }
        $signature = isset($response['signature']) ? $response['signature'] : '';//订单金额 单位元（人民币）
        if ( empty($signature)) {
            $this->logdump('changchengfu', '回掉(签名为空!)');
            exit;
        }
        $channelOrderno = isset($response['channelOrderno']) ? $response['channelOrderno'] : '';//月宝订单号
        if (empty($channelOrderno)) {
            $this->logdump('changchengfu', '回掉(channelOrderno为空!)');
            exit;
        }

        $channelTraceno = isset($response['channelTraceno']) ? $response['channelTraceno'] : '';
        if (empty($channelTraceno)) {
            $this->logdump('changchengfu', '回掉(channelTraceno为空!)');
            exit;
        }
        $merchName = isset($response['merchName']) ? $response['merchName'] : '';
        if (empty($merchName)) {
            $this->logdump('changchengfu', '回掉(merchName为空!)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$traceno}'");
        if (empty($deposits)) {
            $this->logdump('changchengfu', '没有找到订单:' . $traceno . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('changchengfu', '没有找到订单:' . $traceno . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('changchengfu', '配置有误,请联系客服!');
            exit;
        }
        $this->_merchno = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('changchengfu', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        unset($response['signature']);
        ksort($response);
        $a='';
        foreach($response as $x=>$x_value)
        {
            if($x_value){
                $a=$a.$x."=".$x_value."&";
            }
        }
        if ($signature != strtoupper(md5($a.$this->_key))) {
            $this->logdump('签名错误');
            die('签名错误');
        }

        if ($response['status']==1)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $traceno, 'changchengfu');
        } else {
            $this->logdump('changchengfu', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}