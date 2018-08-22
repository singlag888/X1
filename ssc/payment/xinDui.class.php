<?php
namespace ssc\payment;
if (!defined('IN_LIGHT')) {
    die('KCAH');
}
class xinDui extends newPayModel{
    /**
     * 顺序排例
     * @param array $data
     * @return string $str
     * @author L
     */
    public function azSign($data){
        ksort($data);
        $str = '';
        foreach ($data as $key => $value){
            if(!empty($value) && $key != 'sign' && $value != 'null'){
                $str .=  $key . "=" .$value . "&";
            }
        }
        return $str;
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['xind_api'];
        $partner = $card_info['mer_no'];
        $key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $shop_url = $card_info['shop_url'];
        if ($shop_url == "") {
            $callbackurl = $domain . 'newPay/backPay/' . end($className);
            $sameBackUrl = $domain . 'pay/hrefback.php';
        } else if (strpos($shop_url, '?c=pay') === 0) {
            $this->logdump('xdShopUrl','第三方支付域名错误');
            shopMSG('支付域名错误');
        } else if (strpos($shop_url, '?c=pay') > 0) {
            $this->logdump('xdShopUrl','第三方支付域名错误');
            shopMSG('支付域名错误');
        } else {
            $callbackurl = $shop_url .  'pay/' . end($className) . 'Back.php';
            $sameBackUrl = $shop_url . 'hrefback.php';
        }
        $service_type = '';
        $payway = '';
        if ($card_info['netway'] == 'WX') {
            $payway = 'WECHAT';
            $service_type = "WECHAT_SCANPAY";
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $payway = 'WECHAT';
            $service_type = 'WECHAT_H5PAY';
        } elseif ($card_info['netway'] == 'QQ') {
            $payway = 'QQ';
            $service_type = 'QQ_SCANPAY';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $payway = 'QQ';
            $service_type = 'QQ_WAP';
        } elseif ($card_info['netway'] == 'ZFB') {
            $payway = 'ALIPAY';
            $service_type = 'ALIPAY_SCAN_PAY';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $payway = 'ALIPAY';
            $service_type = 'ALIPAY_WAP';
        } else {
            shopMSG('暂时支持该支付');
        }
        $postData['mechno'] = $partner;
        $postData['orderip'] = $_SERVER['REMOTE_ADDR'];
        $postData['amount'] = $data['money']*100;
        $postData['body'] = 'thisvip';
        $postData['notifyurl'] = $callbackurl;
        $postData['returl'] = $sameBackUrl;
        $postData['orderno'] = $data['ordernumber'];
        $postData['payway'] = $payway;
        $postData['paytype'] = $service_type;
        $postData['sign'] = strtoupper(md5($this->azSign($postData).'key='.$key));
        $url = $this->azSign($postData).'key='.$key.'&sign='.$postData['sign'];
        $http_url = $apiUrl.$url;
        $info = array();
        $res = $this->curlGet($http_url,$info);
        $code = json_decode($res,true);
        if ($code['success'] = 'true' && !empty($code['QRCodeUrl'])){
            $requestURI = $data['requestURI'];
            $bank_id = $data['bank_id'];
            $bank_id = authcode($bank_id, 'DECODE', 'a6sbe!x4^5d_ghd');
            $array = array(
                'order_number' => $data['ordernumber'],
                'user_id' => $data['user_id'],
                'username' => $data['username'],
                'amount' => $data['money'],
                'pay_time' => date('Y-m-d H:i:s'),
                'source' => $_SERVER['HTTP_HOST'],
                'requestURI' => $requestURI,
                'card_id' => $data['card_id'],
                'bank_id' => $bank_id,
            );
            if (\pay::addItem($array)) {
                if (strstr($data['card_info']['netway'], "WAP") > '-1') {
                    header("location:" . $code['QRCodeUrl']);
                    die();
                } else {
                    header("location:" . '../pay/qrcode.php?code=' . $code['QRCodeUrl'] . '&netway=' . $data['card_info']['netway'] . '&amount=' . $data['money'] ."&cdn=" .$this->cdn() );
                }
            }
        } else {
                echo '系统繁忙，请稍后访问';
                $this->logdump('testPay.log', $postData);
                $this->logdump('testPay.log', $res);
        }


    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('test', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $partner = isset($response['mchid']) ? $response['mchid'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('test', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['outorderno']) ? $response['outorderno'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('test', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['status']) ? $response['status'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('test', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['totalfee']) ? $response['totalfee'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('test', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['transactionid']) ? $response['transactionid'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('test', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('test', '回掉(签名不正确!)');
            exit;
        }
        $transTime = isset($response['charset']) ? $response['charset'] : 0;
        if (empty($transTime)) {
            $this->logdump('test', '回掉(发送时间不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('test', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('test', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('test', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('test', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $sign = strtoupper(md5($this->azSign($response).'key='.$key));
        if ($response['sign'] == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'test');
        } else {
            $this->logdump('test', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}