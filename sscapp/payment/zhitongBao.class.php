<?php
namespace sscapp\payment;
class zhitongBao extends payModel{
    /**
     * 彩虹支持QQ
     * @param array $data
     * @return string $str
     * @author Jacky
     */

    public function orderNumber($asd = 0)
    {
        $orderno = "1040" . date("YmdHis") . rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

    public function run($data=[])
    {
        $card_info = isset($data['card_info']) ? $data['card_info'] : '';
        if (empty($card_info)) {
            $this->_error = '卡信息出错,请联系客服!';
            return false;
        }
        if (empty($card_info['mer_key'])) {
            $this->_error = '配置有误,请联系客服!';
            return false;

        }
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $partner = $card_info['mer_no'];
        $key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $shop_url = $card_info['shop_url'];
        $callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $sameBackUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $banktype = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['zhitongbao_wap_api'];
        if ($card_info['netway'] == "WX_WAP") {
            $banktype = "weixin_h5api";
        } elseif ($card_info['netway'] == "QQ_WAP") {
            $banktype = "qq_h5api";
        } elseif ($card_info['netway'] == "WY") {
            $third_party_bank_id = $this->request->getPost('third_party_bank_id', 'intval');
            switch ($third_party_bank_id) {
                // 工商银行
                case '1':
                    $banktype = '1002';
                    break;
                // 农业银行
                case '2':
                    $banktype = '1005';
                    break;
                // 建设银行
                case '3':
                    $banktype = '1003';
                    break;
                // 招商银行
                case '4':
                    $banktype = '1001';
                    break;
                // 交通银行
                case '5':
                    $banktype = '1020';
                    break;
                // 中信银行
                case '6':
                    $banktype = '1021';
                    break;
                case '7':
                    break;
                // 中国光大银行
                case '8':
                    $banktype = '1022';
                    break;
                // 民生银行
                case '9':
                    $banktype = '1006';
                    break;
                // 上海浦东发展银行
                case '10':
                    $banktype = '1004';
                    break;
                // 兴业银行
                case '11':
                    $banktype = '1009';
                    break;
                // 广发银行
                case '12':
                    $banktype = '1027';
                    break;
                // 平安银行
                case '13':
                    $banktype = '1010';
                    break;
                // 华夏银行
                case '15':
                    $banktype = '1025';
                    break;
                // 东莞银行
                case '16':
                    break;
                // 渤海银行
                case '17':
                    $banktype = 'CBHB';
                    break;
                // 浙商银行
                case '19':
                    break;
                // 北京银行
                case '20':
                    $banktype = '1032';
                    break;
                // 广州银行
                case '21':
                    $banktype = '';
                    break;
                // 中国银行
                case '22':
                    $banktype = '1052';
                    break;
                // 邮政储蓄
                case '23':
                    $banktype = '1028';
                    break;
            }

        } else {
            $this->_error = '暂时不支持该支付!';
            return false;
        }
        $postData['merchant_code'] = $partner;//
        $postData['service_type'] = $banktype;//
        $postData['notify_url'] = $callbackurl;//
        $postData['interface_version'] = 'V3.1';//
        $postData['client_ip'] = $this->getClientIp();//
        $postData['sign_type'] = 'RSA-S';//
        $postData['order_no'] = $data['ordernumber'];//
        $postData['order_time'] = date('Y-m-d H:i:s');//
        $postData['order_amount'] = $data['money'];//
        $postData['product_name'] = '充值';//
        $signStr = "";
        $signStr = $signStr."client_ip=".$postData['client_ip']."&";
        $signStr = $signStr."interface_version=".$postData['interface_version']."&";
        $signStr = $signStr."merchant_code=".$postData['merchant_code']."&";
        $signStr = $signStr."notify_url=".$postData['notify_url']."&";
        $signStr = $signStr."order_amount=".$postData['order_amount']."&";
        $signStr = $signStr."order_no=".$postData['order_no']."&";
        $signStr = $signStr."order_time=".$postData['order_time']."&";
        $signStr = $signStr."product_name=".$postData['product_name']."&";
        $signStr = $signStr."service_type=".$postData['service_type'];
        $merchant_private_key= @openssl_get_privatekey($this->formatRSAKey($key, 'private'));//
        @openssl_sign($signStr,$sign_info,$merchant_private_key,OPENSSL_ALGO_MD5);//
        $sign = @base64_encode($sign_info);//
        $postData['sign'] = $sign;
        $jsonResponse = @$this->curlPostData($apiUrl, http_build_query($postData), $info);//
        $response = @json_decode(json_encode(simplexml_load_string($jsonResponse)), true);//
        $response = $response['response'];
        if(!isset($response)){
            $this->_error = "返回值为空!";
            return false;
        }
        $wxwapurl = @urldecode($response['payURL']);//
        if ($response != null && isset($response['resp_code']) && isset($response['result_code']) && $response['resp_code'] == 'SUCCESS' && $response['result_code'] == '0') {
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
<form name="diy" id="diy" action="$wxwapurl"  method="get">
   
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;

            return ['code' => 'html', 'html' => $str];
            //return ['code' => 'url', 'url' => $wxwapurl];
        }else{
            $this->logdump('zhitongbaoPay.log', $postData);
            $this->logdump('zhitongbaoPay.log', $jsonResponse);

            if ($response !== null && isset($response['resp_desc'])) {
                $this->logdump('zhitongbaoPay.log', $response);
                $this->_error = $response['resp_desc'];
                return false;
            } else {
                $this->_error = "生成订单失败!";
                return false;
            }
        }

    }

    public function curlPostData($url, $data, &$info)
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
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
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

    public function checkZhiTongBaoSign($data, $private_key, $dinpaySign)
    {
        $signStr = "";

        if(isset($data['bank_seq_no']) && $data['bank_seq_no'] != ""){
            $signStr = $signStr."bank_seq_no=".$data['bank_seq_no']."&";
        }

        if(isset($data['extra_return_param']) && $data['extra_return_param'] != ""){
            $signStr = $signStr."extra_return_param=".$data['extra_return_param']."&";
        }

        $signStr = $signStr . "interface_version=".$data['interface_version']."&";
        $signStr = $signStr . "merchant_code=".$data['merchant_code']."&";
        $signStr = $signStr . "notify_id=".$data['notify_id']."&";
        $signStr = $signStr . "notify_type=".$data['notify_type']."&";
        $signStr = $signStr . "order_amount=".$data['order_amount']."&";
        $signStr = $signStr . "order_no=".$data['order_no']."&";
        $signStr = $signStr . "order_time=".$data['order_time']."&";
        $signStr = $signStr . "trade_no=".$data['trade_no']."&";
        $signStr = $signStr . "trade_status=".$data['trade_status']."&";
        $signStr = $signStr . "trade_time=".$data['trade_time'];
        $dinpay_public_key = openssl_get_publickey($private_key);
        $flag = openssl_verify($signStr, $dinpaySign, $dinpay_public_key, OPENSSL_ALGO_MD5);
        if($flag){
            return true;
        }else{
            return false;
        }
    }


    public function formatKey($key, $type = 'public'){
        $key = str_replace("-----BEGIN PRIVATE KEY-----", "", $key);
        $key = str_replace("-----END PRIVATE KEY-----", "", $key);
        $key = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        $key = str_replace("-----END PUBLIC KEY-----", "", $key);
        $key = $this->trimAll($key);

        if ($type == 'public') {
            $begin = "-----BEGIN PUBLIC KEY-----\n";
            $end = "-----END PUBLIC KEY-----";
        } else {
            $begin = "-----BEGIN PRIVATE KEY-----\n";
            $end = "-----END PRIVATE KEY-----";
        }

        $key = chunk_split($key, 64, "\n");

        return $begin . $key . $end;
    }

    public function trimAll($str)
    {
        $qian = array(" ", "　", "\t", "\n", "\r");
        $hou = array("", "", "", "", "");
        return str_replace($qian, $hou, $str);
    }





    public function callback($response = [], $is_raw = 0){
        if (empty($response)) {
            $this->logdump('zhitongBao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $order_amount = isset($response['order_amount']) ? $response['order_amount'] : 0;//订单号

        if ($order_amount === 0) {
            $this->logdump('zhitongBao', '回掉(order_amount为空!)');
            exit;
        }

        $merchant_code = isset($response['merchant_code']) ? $response['merchant_code'] : '';//订单号
        if (empty($merchant_code)) {
            $this->logdump('zhitongBao', '回掉(merchant_code为空!)');
            exit;
        }

        $notify_type = isset($response['notify_type']) ? $response['notify_type'] : '';//月宝订单号
        if (empty($notify_type)) {
            $this->logdump('zhitongBao', '回掉(notify_type为空!)');
            exit;
        }

        $notify_id = isset($response['notify_id']) ? $response['notify_id'] : '';//月宝订单号
        if (empty($notify_id)) {
            $this->logdump('zhitongBao', '回掉(notify_id为空!)');
            exit;
        }

        $interface_version = isset($response['interface_version']) ? $response['interface_version'] : '';//月宝订单号
        if (empty($interface_version)) {
            $this->logdump('zhitongBao', '回掉(interface_version为空!)');
            exit;
        }

        $sign_type = isset($response['sign_type']) ? $response['sign_type'] : '';//月宝订单号
        if (empty($sign_type)) {
            $this->logdump('zhitongBao', '回掉(sign_type为空!)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('zhitongBao', '回掉(sign为空!)');
            exit;
        }
        $sign = base64_decode($sign);

        $order_no = isset($response['order_no']) ? $response['order_no'] : '';//月宝订单号
        if (empty($order_no)) {
            $this->logdump('zhitongBao', '回掉(order_no为空!)');
            exit;
        }

        $order_time = isset($response['order_time']) ? $response['order_time'] : '';//月宝订单号
        if (empty($order_time)) {
            $this->logdump('zhitongBao', '回掉(order_time为空!)');
            exit;
        }

        $trade_no = isset($response['trade_no']) ? $response['trade_no'] : '';//月宝订单号
        if (empty($trade_no)) {
            $this->logdump('zhitongBao', '回掉(trade_no为空!)');
            exit;
        }

        $trade_time = isset($response['trade_time']) ? $response['trade_time'] : '';//月宝订单号
        if (empty($trade_time)) {
            $this->logdump('zhitongBao', '回掉(trade_time为空!)');
            exit;
        }

        $trade_status = isset($response['trade_status']) ? $response['trade_status'] : '';//月宝订单号
        if (empty($trade_status)) {
            $this->logdump('zhitongBao', '回掉(trade_status为空!)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$order_no}'");

        if (empty($deposits)) {
            $this->logdump('zhitongBao', '没有找到订单:' . $order_no . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('zhitongBao', '没有找到订单:' . $order_no . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('zhitongBao', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('zhitongBao', '配置有误,请联系客服!');
            exit;
        }
        if ($this->checkZhiTongBaoSign($response, $this->formatKey($card_info['card_pass']), $sign) && $response['trade_status'] == 'SUCCESS')//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $order_no, 'zhitongBao');
        } else {
            $this->logdump('zhitongBao', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}