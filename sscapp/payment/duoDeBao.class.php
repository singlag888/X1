<?php
/**
 * 多得宝
 * @author stone
 */

namespace sscapp\payment;


class duoDeBao extends payModel
{

    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_requestUrl = 'https://pay.ddbill.com/gateway?input_charset=UTF-8';//第三方请求地址
    private $_requestUrlSao = 'https://api.ddbill.com/gateway/api/scanpay';//第三方请求地址
    private $_sameBackUrl = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        $arr = array_merge(range('a', 'z'), range('1', '9'));
        shuffle($arr);
        $shop_order_num = '1011' . date("YmdHis") . rand(1000, 9999) . reset($arr) . end($arr);
        unset($arr);
        return $shop_order_num;
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
        $service_typeId = $card_info['bank_id'];
        if (empty($service_typeId)) {
            $this->_error = '配置有误,请联系客服3!';
            return false;
        }
        $this->_partner = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $className = end($className);
        $netway = $card_info['netway'];
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $amount = number_format(floatval($data['money']), 2, '.', '');
        if ($netway == "WX") {
            $bank = 'weixin_scan';
        } else if ($netway == "ZFB") {
            $bank = 'alipay_scan';
        } else if ($netway == "QQ") {
            $protocol = $this->getProtocol($area_flag);
            $this->_callbackurl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . $className;
            $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . $className;
            $bank = 'qq_scan';
        } else if ($netway == "WX_WAP") {
            $shop_url = $card_info['shop_url'];
            if(empty($shop_url))
            {
                $this->_error = '配置出错!';
                return false;
            }
            $this->_callbackurl = $shop_url. '/pay/backPay/' . $className;
            $this->_sameBackUrl = $shop_url . '/pay/inStep/' . $className;
            $bank = 'wxpay_h5';
        }else if ($netway == "WY") {
            $shop_url = $card_info['shop_url'];
            if(empty($shop_url))
            {
                $this->_error = '配置出错!';
                return false;
            }
            $this->_callbackurl = $shop_url. '/pay/backPay/' . $className;
            $this->_sameBackUrl = $shop_url . '/pay/inStep/' . $className;
            $third_party_bank_id = $card_info['bank_id'];
            $banktype = 'ICBC';



            $merchant_code = $this->_partner;
            $service_type = "direct_pay";
            $notify_url = $this->_callbackurl;
            $interface_version = "V3.0";
            $input_charset = "UTF-8";
            $sign_type = "RSA-S";
            $return_url =  $this->_sameBackUrl;
            $pay_type = "b2c";
            $order_no = $data['ordernumber'];
            $order_time = date('Y-m-d H:i:s');
            $order_amount = $amount;
            $product_name = "充值";

            $signStr = "";
            $bank_code = $banktype;

            if ($bank_code != "") {
                $signStr = $signStr . "bank_code=" . $bank_code . "&";
            }

            $signStr = $signStr . "input_charset=" . $input_charset . "&";
            $signStr = $signStr . "interface_version=" . $interface_version . "&";
            $signStr = $signStr . "merchant_code=" . $merchant_code . "&";
            $signStr = $signStr . "notify_url=" . $notify_url . "&";
            $signStr = $signStr . "order_amount=" . $order_amount . "&";
            $signStr = $signStr . "order_no=" . $order_no . "&";
            $signStr = $signStr . "order_time=" . $order_time . "&";

            if ($pay_type != "") {
                $signStr = $signStr . "pay_type=" . $pay_type . "&";
            }

            $signStr = $signStr . "product_name=" . $product_name . "&";

            if ($return_url != "") {
                $signStr = $signStr . "return_url=" . $return_url . "&";
            }
            $signStr = $signStr . "service_type=" . $service_type;

            $merchant_private_key = openssl_get_privatekey($this->formatKey($this->_key, "private"));
            openssl_sign($signStr, $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
            $sign = base64_encode($sign_info);

            $postFields = array(
                'url' => $this->_requestUrl,
                'sign' => $sign,
                'merchant_code' => $merchant_code,
                'bank_code' => $bank_code,
                'order_no' => $order_no,
                'order_amount' => $order_amount,
                'service_type' => $service_type,
                'input_charset' => $input_charset,
                'notify_url' => $notify_url,
                'interface_version' => $interface_version,
                'sign_type' => $sign_type,
                'order_time' => $order_time,
                'product_name' => $product_name,
                'pay_type' => $pay_type,
                'return_url' => $return_url,
            );
            $res = $this->curlPost($this->_requestUrl, http_build_query($postFields), $className, 0, 0, $shop_url);

            return ['code' => 'html', 'html' => $res];

        }
        else {
            $this->_error = '暂不支付该充值方式';
            return false;
        }

        $merchant_code = $this->_partner;
        $service_type = $bank; //微信：weixin_scan 支付宝：alipay_scan 智汇宝：zhb_scan
        $notify_url = $this->_callbackurl;
        $interface_version = 'V3.3';
        $client_ip = $this->getClientIp();
        $sign_type = 'RSA-S';
        $order_no = $data['ordernumber'];
        $order_time = date('Y-m-d H:i:s');
        $order_amount = $amount;
        $product_name = 'testpay';
        $input_charset = $service_type == 'wxpay_h5' ? "UTF-8" : '';

        $signStr = '';
        $signStr = $signStr . "client_ip=" . $client_ip . "&";
        $signStr = $service_type == 'wxpay_h5' ? $signStr . "input_charset=" . $input_charset . "&" : $signStr . '';
        $signStr = $signStr . "interface_version=" . $interface_version . "&";
        $signStr = $signStr . "merchant_code=" . $merchant_code . "&";
        $signStr = $signStr . "notify_url=" . $notify_url . "&";
        $signStr = $signStr . "order_amount=" . $order_amount . "&";
        $signStr = $signStr . "order_no=" . $order_no . "&";
        $signStr = $signStr . "order_time=" . $order_time . "&";
        $signStr = $signStr . "product_name=" . $product_name . "&";
        $signStr = $signStr . "service_type=" . $service_type;
        $key = $this->formatKey($this->_key, "private");
        $merchant_private_key = @openssl_get_privatekey($key);
        @openssl_sign($signStr, $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
        $sign = @base64_encode($sign_info);


        $postdata = array(
            'merchant_code' => $merchant_code,
            'service_type' => $service_type,
            'notify_url' => $notify_url,
            'interface_version' => $interface_version,
            'sign_type' => $sign_type,
            'order_no' => $order_no,
            'client_ip' => $client_ip,
            'sign' => $sign,
            'order_time' => $order_time,
            'order_amount' => $amount,
            'product_name' => $product_name
        );
        if (isset($shop_url) && !empty($shop_url)) {
            $res = $this->curlPost($this->_requestUrl, http_build_query($postdata), $className, 0, 0, $shop_url);
            if (empty($res)) {
                $this->_error = '第三方支付没有响应,请稍后重试!';
                return false;
            }
            /*if (!strpos($res, 'onload')) {
                $this->_error = '支付通道异常,请选择其他通道充值!';
                $this->logdump('duoDeBao', $res);
                return false;
            }*/
            return ['code' => 'html', 'html' => $res];
        } else {
            $res = $this->curlPost($this->_requestUrlSao, http_build_query($postdata), $className, 0, 0);
            if (empty($res)) {
                $this->_error = '第三方支付没有响应,请稍后重试!';
                return false;
            }
            $arr = $this->xmlToArrayOrObj($res);
            if (!is_array($arr['response'])) {
                $this->_error = '第三方支付没有响应,请稍后重试!';
                return false;
            }
            $data = $arr['response'];
            if ($data['resp_code'] === 'FAIL') {
                $this->_error = '支付通道异常,请选择其他通道充值!';
                $this->logdump($className, $data['resp_desc']);
                return false;
            } elseif ($data['resp_code'] === 'SUCCESS') {
                if ($data['result_code'] == 0) {
                    return ['code' => 'qrcode', 'url' => $data['qrcode']];
                }
            }

            $this->_error = '支付通道异常,请选择其他通道充值!';
            $this->logdump($className, $res);
            return false;


        }


        // TODO: Implement run() method.
    }

    public function formatKey($key, $type = 'public')
    {
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

    public function formatRSAKey($key, $type = 'public')
    {
        $key = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $key);
        $key = str_replace("-----END RSA PRIVATE KEY-----", "", $key);
        $key = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        $key = str_replace("-----END PUBLIC KEY-----", "", $key);
        $key = $this->trimAll($key);

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

    function trimAll($str)
    {
        $qian = array(" ", "　", "\t", "\n", "\r");
        $hou = array("", "", "", "", "");
        return str_replace($qian, $hou, $str);
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
            $this->logdump('duoDeBao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw == 1) {
            $response = json_decode($response, true);
        }

        if (!isset($response["merchant_code"])) {
            $this->logdump('duoDeBao', 'duoDeBao', '商户号为空');
            exit;
        }

        $merchant_code = $response["merchant_code"];

        if (!isset($response["notify_type"])) {
            $this->logdump('duoDeBao', '通知类型为空!');
            exit;
        }

        $notify_type = $response["notify_type"];

        if (!isset($response["notify_id"])) {
            $this->logdump('duoDeBao', '通知校验ID为空');
            exit;
        }

        $notify_id = $response['notify_id'];

        if (!isset($response["interface_version"])) {
            $this->logdump('duoDeBao', '接口版本号为空');
            exit;
        }

        $interface_version = $response['interface_version'];

        if (!isset($response["sign_type"])) {
            $this->logdump('duoDeBao', '签名方式为空');
            exit;
        }

        $sign_type = $response['sign_type'];

        if (!isset($response["sign"])) {
            $this->logdump('duoDeBao', '返回签名为空!');
            exit;
        }

        $sign = base64_decode($response['sign']);

        if (!isset($response["order_no"])) {
            $this->logdump('duoDeBao', '商户网站唯一订单号为空');
            exit;
        }

        $order_no = $response['order_no'];

        if (!isset($response["order_time"])) {
            $this->logdump('duoDeBao', '商户订单时间出错');
            exit;
        }

        $order_time = $response['order_time'];

        if (!isset($response["order_amount"])) {
            $this->logdump('duoDeBao', '商户订单总金额出错');
            exit;
        }

        $order_amount = $response['order_amount'];

        if (!isset($response["trade_no"])) {
            $this->logdump('duoDeBao', '多得宝交易订单号出错');
            exit;
        }

        $trade_no = $response['trade_no'];

        if (!isset($response["trade_time"])) {
            $this->logdump('duoDeBao', '多得宝交易订单时间出错');
            exit;
        }

        $trade_time = $response['trade_time'];

        if (!isset($response["trade_status"])) {
            $this->logdump('duoDeBao', '交易状态出错');
            exit;
        }
        $trade_status = $response['trade_status'];
        $signStr = "";
        $bank_seq_no = isset($response['bank_seq_no']) ? $response['bank_seq_no'] : '';
        if ($bank_seq_no != "") {
            $signStr = $signStr . "bank_seq_no=" . $bank_seq_no . "&";
        }
        $signStr = $signStr . "interface_version=" . $interface_version . "&";
        $signStr = $signStr . "merchant_code=" . $merchant_code . "&";
        $signStr = $signStr . "notify_id=" . $notify_id . "&";
        $signStr = $signStr . "notify_type=" . $notify_type . "&";
        $signStr = $signStr . "order_amount=" . $order_amount . "&";
        $signStr = $signStr . "order_no=" . $order_no . "&";
        $signStr = $signStr . "order_time=" . $order_time . "&";
        $orginal_money = isset($response["orginal_money"]) ? $response["orginal_money"] : "";
        if ($orginal_money != "") {
            $signStr = $signStr . "orginal_money=" . $orginal_money . "&";
        }
        $signStr = $signStr . "trade_no=" . $trade_no . "&";
        $signStr = $signStr . "trade_status=" . $trade_status . "&";
        $signStr = $signStr . "trade_time=" . $trade_time;
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$order_no}'");
        if (empty($deposits)) {
            $this->logdump('duoDeBao', '没有找到订单:' . $order_no . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('duoDeBao', '没有找到订单:' . $order_no . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('duoDeBao', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('duoDeBao', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $dinpay_public_key = openssl_get_publickey($this->formatKey($card_info['card_pass']));
        $flag = openssl_verify($signStr, $sign, $dinpay_public_key, OPENSSL_ALGO_MD5);
        if ($flag) {
            $order = \pay::getItemByOrderNumber($order_no);

            if ($trade_status == "SUCCESS") {
                $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $trade_no, 'duoDeBao');
            } else {
                $this->logdump('duoDeBao', "failed!交易失败码:{$trade_status},用户名{$order['username']} 交易订单{$trade_no} 金额{$order['amount']} ");
                die;
            }
        } else {
            echo '不合法数据';
            $this->logdump('duoDeBao', "签名验证失败，程序中止。");
            die;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}