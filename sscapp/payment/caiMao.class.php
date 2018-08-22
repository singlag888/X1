<?php
namespace sscapp\payment;
class caiMao extends payModel{
    /**
     * 元宝
     * @param array $data
     * @return string $str
     * @author Jacky
     */

    public function orderNumber($asd = 0)
    {
        $orderno = "1043" . date("YmdHis") . rand(1000, 9999);
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
        if(!(strstr($card_info['netway'], "WAP") > -1)){
            $apiUrl = $GLOBALS['cfg']['pay_url']['caimao_api'];
        }else {
            $apiUrl = $GLOBALS['cfg']['pay_url']['caimao_wap_api'];
        }
        if ($card_info['netway'] == "QQ") {
            $banktype = "tenpay_scan";
        } else if ($card_info['netway'] == "WX") {
            $banktype = "weixin_scan";
        } else if ($card_info['netway'] == "ZFB") {
            $banktype = "alipay_scan";
        } else if ($card_info['netway'] == "WX_WAP") {
            $banktype = "weixin_h5api";
        } else if ($card_info['netway'] == "QQ_WAP") {
            $banktype = "qq_h5api";
        } else if ($card_info['netway'] == "ZFB_WAP") {
            $banktype = "alipay_h5api";
        } else if ($card_info['netway'] == "JD") {
            $banktype = "jdpay_scan";

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
        $postData['interface_version'] = 'V3.1';
        $postData['sign_type'] = 'RSA-S';
        $postData['order_no'] = $data['ordernumber'];
        $postData['client_ip'] = $this->getClientIp();
        $postData['order_time'] = date('Y-m-d H:i:s');
        $postData['order_amount'] = $data['money'];
        $postData['product_name'] = 'lucky';//

        $signStr = "";

        $signStr = $signStr."client_ip=".$postData['client_ip']."&";	//5

        $signStr = $signStr."interface_version=".$postData['interface_version']."&";	//4

        $signStr = $signStr."merchant_code=".$postData['merchant_code']."&";	//1

        $signStr = $signStr."notify_url=".$postData['notify_url']."&";	//3

        $signStr = $signStr."order_amount=".$postData['order_amount']."&";	//8

        $signStr = $signStr."order_no=".$postData['order_no']."&";		//6

        $signStr = $signStr."order_time=".$postData['order_time']."&";	//7

        $signStr = $signStr."product_name=".$postData['product_name']."&";//10

        $signStr = $signStr."service_type=".$postData['service_type'];//2

        $merchant_private_key= openssl_get_privatekey($key);//
        @openssl_sign($signStr,$sign_info,$merchant_private_key,OPENSSL_ALGO_MD5);//

        $sign = base64_encode($sign_info);
        $postData['sign'] = $sign;//

        $jsonResponse = @$this->caimaocurlPostData($apiUrl, $postData);
        $response = @$this->xmlToArray($jsonResponse);

        if(!isset($response['response'])){
            $this->_error = "返回值为空!";
            return false;
        };

        $wxwapurl = @urldecode($response['response']['payURL']);//
        if ($response['response'] != null && $response['response']['result_code'] == '0'){
            if (!(strstr($card_info['netway'], "WAP") > -1)) {
                $url = urldecode($response['response']['qrcode']);
                if (empty($url)){
                    $this->_error = '服务器繁忙，请稍后再试';
                    $this->logdump('caimao',$response);
                }
                return ['code' => 'qrcode', 'url' => $url];
            }else{
                return ['code' => 'url', 'url' => $response['response']['payURL']];
            }
        }else{
            if(isset($response['response']['resp_desc'])){
                $this->_error = $response['response']['resp_desc'];
                return false;
            }else{
                $this->_error = "生成二维码失败!";
                return false;
            }
        }//
    }

    public function xmlToArray($xml, $recursive = false )
    {
        if (!$recursive){
            $array = simplexml_load_string($xml);
        } else  {
            $array = $xml;
        }

        $newArray = array ();
        $array = (array) $array ;
        foreach ($array as $key => $value ) {
            $value = (array) $value ;
            if (isset ($value [0])){
                $newArray [$key] = trim($value [0]) ;
            } else {
                $newArray [$key] = self::xmlToArray($value, true) ;
            }
        }
        return $newArray ;
    }



    public function caimaocurlPostData($url,$data){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $response=curl_exec($ch);
        curl_close($ch);
        return $response;
    }


    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('caiMao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }

        if ($is_raw) {
            parse_str($response, $response);
        }
        $response["trade_time"] = trim($response["trade_time"]);

        $order_amount = isset($response['order_amount']) ? $response['order_amount'] : 0;//订单号

        if ($order_amount === 0) {
            $this->logdump('caiMao', '回掉(order_amount为空!)');
            exit;
        }

        $trade_no = isset($response['trade_no']) ? $response['trade_no'] : '';//订单号
        if (empty($trade_no)) {
            $this->logdump('caiMao', '回掉(trade_no为空!)');
            exit;
        }

        $sign_type = isset($response['sign_type']) ? $response['sign_type'] : '';//1:支付成功，非1为支付失败
        if (empty($sign_type)) {
            $this->logdump('caiMao', '回掉(sign_type为空)');
            exit;
        }

        $notify_type = isset($response['notify_type']) ? $response['notify_type'] : '';//月宝订单号
        if (empty($notify_type)) {
            $this->logdump('caiMao', '回掉(notify_type为空!)');
            exit;
        }


        $merchant_code = isset($response['merchant_code']) ? $response['merchant_code'] : '';//月宝订单号
        if (empty($merchant_code)) {
            $this->logdump('caiMao', '回掉(merchant_code为空!)');
            exit;
        }

        $order_no = isset($response['order_no']) ? $response['order_no'] : '';//月宝订单号
        if (empty($order_no)) {
            $this->logdump('caiMao', '回掉(order_no为空!)');
            exit;
        }

        $trade_status = isset($response['trade_status']) ? $response['trade_status'] : '';//月宝订单号
        if (empty($trade_status)) {
            $this->logdump('caiMao', '回掉(trade_status为空!)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('caiMao', '回掉(sign为空!)');
            exit;
        }

        $interface_version = isset($response['interface_version']) ? $response['interface_version'] : '';//月宝订单号
        if (empty($interface_version)) {
            $this->logdump('caiMao', '回掉(interface_version为空!)');
            exit;
        }

        $bank_seq_no = isset($response['bank_seq_no']) ? $response['bank_seq_no'] : '';//月宝订单号
        if (empty($bank_seq_no)) {
            $this->logdump('caiMao', '回掉(bank_seq_no为空!)');
            exit;
        }

        $order_time = isset($response['order_time']) ? $response['order_time'] : '';//月宝订单号
        if (empty($order_time)) {
            $this->logdump('caiMao', '回掉(order_time为空!)');
            exit;
        }

        $notify_id = isset($response['notify_id']) ? $response['notify_id'] : '';//月宝订单号
        if (empty($notify_id)) {
            $this->logdump('caiMao', '回掉(notify_id为空!)');
            exit;
        }

        $trade_time = isset($response['trade_time']) ? $response['trade_time'] : '';//月宝订单号
        if (empty($trade_time)) {
            $this->logdump('caiMao', '回掉(trade_time为空!)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$order_no}'");

        if (empty($deposits)) {
            $this->logdump('caiMao', '没有找到订单:' . $order_no . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('caiMao', '没有找到订单:' . $order_no . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('caiMao', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('caiMao', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $dinpaySign = base64_decode($sign);

        $signStr = "";
        $signStr = $signStr."bank_seq_no=".$bank_seq_no."&";

        $signStr = $signStr."interface_version=".$interface_version."&";

        $signStr = $signStr."merchant_code=".$merchant_code."&";

        $signStr = $signStr."notify_id=".$notify_id."&";

        $signStr = $signStr."notify_type=".$notify_type."&";

        $signStr = $signStr."order_amount=".$order_amount."&";

        $signStr = $signStr."order_no=".$order_no."&";

        $signStr = $signStr."order_time=".$order_time."&";

        $signStr = $signStr."trade_no=".$trade_no."&";


        $signStr = $signStr."trade_status=".$trade_status."&";

        $signStr = $signStr."trade_time=".$trade_time;


        $dinpay_public_key = openssl_get_publickey($card_info['card_pass']);
        $flag = openssl_verify($signStr,$dinpaySign,$dinpay_public_key,OPENSSL_ALGO_MD5);

        if ($flag)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $order_no, 'caiMao');
        } else {
            $this->logdump('caiMao', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}