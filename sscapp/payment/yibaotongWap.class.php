<?php
/**
 * 易宝通微信WAP
 */

namespace sscapp\payment;


class yibaotongWap extends payModel
{
    private $_merchant_code = '';//商户号1
    private $_notify_url = ''; //异步回掉地址1
    private $_hrefbackurl = '';//
    private $_key = '';//商户key
    private $_requestUrl = 'https://api.yibaotown.com/gateway/api/h5apipay';

    public function orderNumber($asd = 0)
    {
        $orderno = "1015".date("YmdHis").rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

    /**
     * 生成签名
     */


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
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }

        $this->_merchant_code = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_notify_url = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_hrefbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "WX_WAP") {
            $service_type = "weixin_h5api";
        }elseif($card_info["netway"] == "QQ"){
            $service_type = "tenpay_scan";//
        }else{
            $this->_error = '此卡暂不支持该充值方式!'.$service_type;
            return false;
        }
        $dataTmp = array(
            'service_type' => $service_type,//
            'order_amount' => sprintf("%01.2f", $data['money']),//
        );
        $dataTmp['merchant_code'] = $this->_merchant_code;//
        $dataTmp['order_time'] 		= date('Y-m-d H:i:s');//
        $dataTmp['order_no'] = $data['ordernumber'];//
        $dataTmp['client_ip'] = $this->getClientIp();//
        $dataTmp['notify_url'] = $this->_notify_url;//
        $dataTmp['interface_version'] 	= 'V3.1';//
        $dataTmp['sign_type'] 			= 'RSA-S';//
        $dataTmp['product_name'] 		= 'testProductName';//
        $dataTmp['extra_return_param'] = $card_info['card_id'];//

        $dataTmp['sign'] 				= $this->_yiBaoTongMakeInSign($dataTmp,$this->_key);//

        $res = $this->_yiBaoTongCurlPost($this->_requestUrl, $dataTmp);//
        if($card_info["netway"] == "QQ"){
            $apiurlqq = "https://api.yibaotown.com/gateway/api/scanpay";
            $res = $this->_yiBaoTongCurlPost($apiurlqq, $dataTmp);
            if(!isset($res['response']['qrcode'])){
                $this->_error = $res['response']['resp_desc'];
                return false;
            }
            return ['code' => 'qrcode', 'url' => $res['response']['qrcode']];
        }

        if (!isset($res['response']['payURL'])) {
            $this->_error = $res['response']['resp_desc'];
            return false;
        }
        $wapurl = urldecode($res['response']['payURL']);


        return ['code' => 'url', 'url' => $wapurl];
    }

    private static function _yiBaoTongMakeInSign($post,$mer_key)
    {
        ksort($post);
        $signStr = "";
        foreach ($post as $key => $val) {
            //>>排除不参与签名的
            if (  $val != '' && $key != 'sign_type'  && $key != 'sign')  {
                $signStr .= $key . '=' . $val . '&';
            }
        }

        $signStr = substr($signStr,0,-1);
        $merchant_private_key = $mer_key;

        $merchant_private_key = @openssl_get_privatekey($merchant_private_key);

        @openssl_sign($signStr,$sign_info,$merchant_private_key,OPENSSL_ALGO_MD5);
        //>>生成签名
        return base64_encode($sign_info);
    }

    private static function _yiBaoTongCurlPost($url, $postData)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response=curl_exec($ch);

        curl_close($ch);
        $response = simplexml_load_string($response);
        $response = json_decode(json_encode($response),true);

        return $response;
    }

    private  function _yibaotongNotifyHandle($data,$card_pass)
    {
        //>>回调需要验证必须传入的参数
        $options = [
            'merchant_code',
            'notify_type',
            'notify_id',
            'interface_version',
            'sign_type',
            'sign',
            'order_no',
            'order_time',
            'order_amount',
            'extra_return_param',
            'trade_no',
            'trade_time',
            'trade_status',
//            'bank_seq_no',  这个是可选参数
        ];
        //>>遍历判断
        foreach($options as $key => $val){
            if( !isset($data[$val])){
                return '参数错误' . $val;
            }
        }
        //>>传入平台公钥
        return $this->_yiBaoTongHandleSign($data,$card_pass);
    }

    private static function _yiBaoTongHandleSign($post,$card_pass)
    {
        ksort($post);
        $signStr = "";
        foreach ($post as $key => $val) {
            //>>排除不参与签名的
            if (  $val != '' && $key != 'sign_type'  && $key != 'sign' )  {
                $val = trim($val);
                $signStr .= $key . '=' . $val . '&';
            }
        }
        $sign_info = $post['sign'];
        $signStr = substr($signStr,0,-1);
        $dinpay_public_key = $card_pass;

        $sign_info = base64_decode($sign_info);
        $res = @openssl_get_publickey($dinpay_public_key);
        return @(bool)openssl_verify($signStr,$sign_info,$res,OPENSSL_ALGO_MD5);
        //>>生成签名
    }


    /**
     * 支付回掉入口
     * 1.回掉验签
     * 2.执行本地逻辑
     * @param array $response 第三方回掉返回数据
     * @param int $is_raw 返回数据结构形式 主要表示是否是原始数据流 0不是 1是
     */
    public function callback($response = [], $is_raw = 0)
    {
        if (empty($response)) {
            $this->logdump('yibaotongWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response,$response);
        }
        if ($response ===  null) {
            $this->logdump('yibaotongWap', '回掉(返回值为空!)');
            exit;
        }
        if (!isset($response['extra_return_param'])) {
            $this->logdump('yibaotongWap', '回掉(extra_return_param值为空!)');
            exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($response['order_no']);
        if (empty($onlinepayment)) {
            $this->logdump('yibaotongWap', '没有找到订单:' . $response['order_no'] . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$response['order_no']}'");

        if (empty($deposits)) {
            $this->logdump('yibaotongWap', '没有找到订单:' . $response['order_no'] . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('yibaotongWap', '没有找到订单:' . $response['order_no'] . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('yibaotongWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_parter = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('yibaotongWap', '配置有误,请联系客服!');
            exit;
        }
        $pubkey = $card_info['card_pass'];
        $result = $this->_yibaotongNotifyHandle($response,$pubkey);
            if ($result === true)//签名正确
            {
                $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $response['order_no'], 'yibaotongWap');
            } else {
                $this->logdump('yibaotongWap', '回掉(签名验证不通过!请支付小组查看!)');
                exit;
            }
            die('ok');//到不了这里

            // TODO: Implement callback() method.
        }
}