<?php
namespace sscapp\payment;
class wzhPay extends payModel{
    /**
     * 口袋
     * @param array $data
     * @return string $str
     * @author Jacky
     */
    public function orderNumber($asd = 0)
    {
        $orderno = "1052" . date("YmdHis") . rand(1000, 9999);
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
        }
        $partner = $card_info['mer_no'];
        $key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $shop_url = $card_info['shop_url'];
        $callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $sameBackUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['wzhpay_api'];
        if ($card_info['netway'] == 'WX') {
              $service_type = "1";
            $apiqrcodeUrl = $GLOBALS['cfg']['pay_url']['wzhpay_wx_api'];
        } elseif ($card_info['netway'] == 'ZFB') {
              $service_type = "2";
            $apiqrcodeUrl = $GLOBALS['cfg']['pay_url']['wzhpay_zfb_api'];
        } else {
            $this->_error = '暂时不支持该支付!';
        }
        $postData['type'] = "PayData";
        $postData['amount'] = $data['money'];
        $postData['userid'] = $data['ordernumber'];
        $postData['Paytype'] = intval($service_type);
        $postData['callbackurl'] = $callbackurl;
        $header = array("identifying:" . $key);
        $curldata = $this->tocurl($apiUrl, $header, $postData);
        if(!isset($curldata) || $curldata == ""){
            $this->_error = '系统繁忙,请稍候再试!';
            return false;
        }

        $qrcode_url = $apiqrcodeUrl . "?Code=" . $curldata . "&SuccessUrl=".urlencode($sameBackUrl);

        return ['code' => 'url', 'url' => $qrcode_url];

    }


    public function tocurl($url, $header, $content){
        $ch = curl_init();
        if(substr($url,0,5)=='https'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
        $response = curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        curl_close($ch);
        return $response;
    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('wzhPay', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $tradeNo = isset($response['tradeNo']) ? $response['tradeNo'] : '';//商户ID
        if (empty($tradeNo)) {
            $this->logdump('wzhPay', '回掉(tradeNo为空!)');
            exit;
        }

        $time = isset($response['time']) ? $response['time'] : '';//订单号
        if (empty($time)) {
            $this->logdump('wzhPay', '回掉(time为空!)');
            exit;
        }

        $userid = isset($response['userid']) ? $response['userid'] : '';
        if (empty($userid)) {
            $this->logdump('wzhPay', '回掉(userid为空)');
            exit;
        }

        $amount = isset($response['amount']) ? $response['amount'] : 0;//1:支付成功，非1为支付失败
        if ($amount === 0) {
            $this->logdump('wzhPay', '回掉(amount为空)');
            exit;
        }

        $status = isset($response['status']) ? $response['status'] : '';//1:支付成功，非1为支付失败
        if (empty($status)) {
            $this->logdump('wzhPay', '回掉(status为空)');
            exit;
        }

        $type = isset($response['type']) ? $response['type'] : '';//1:支付成功，非1为支付失败
        if (empty($type)) {
            $this->logdump('wzhPay', '回掉(type为空)');
            exit;
        }

        $sig = isset($response['sig']) ? $response['sig'] : '';//1:支付成功，非1为支付失败
        if (empty($sig)) {
            $this->logdump('wzhPay', '回掉(sig为空)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$userid}'");

        if (empty($deposits)) {
            $this->logdump('wzhPay', '没有找到订单:' . $userid . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('wzhPay', '没有找到订单:' . $userid . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('wzhPay', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('wzhPay', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $sign_text  = $tradeNo."|". $response["desc"]."|". $time."|". $userid."|". $amount."|". $status."|". $type."|". $key;
        $sign_md5 	= strtoupper(md5($sign_text));

        if ($sig == $sign_md5)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'ok', $deposits, $userid, 'wzhPay');
        } else {
            $this->logdump('wzhPay', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}