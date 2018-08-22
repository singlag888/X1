<?php
/**
 * 五福支付
 * @author L
 * Date: 2018/1/6
 * Time: 14:53
 */
namespace sscmobile\payment;
class wuFu extends newPayModel
{
    public function azSign($data){
        ksort($data);
        $str = '';
        foreach ($data as $key => $value){
            if ($key !== 'md5value'){
                $str .= $value ;
            }
        }
        return $str;
    }
    public function send_post($url, $post_data)//
    {
        $postdata = $post_data;
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60
            ) // 超时时间（单位:s）

        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['wufu_api'];
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
        if ($card_info['netway'] == 'WX') {
            $service_type = "WEIXIN_NATIVE";
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $service_type = 'WEIXIN_H5';
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = 'ALIPAY_NATIVE';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = 'ALIPAY_H5';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $service_type = 'QQ_H5';
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = 'QQ_NATIVE';
        } else {
            echo '暂时支持该支付';
        }
        $postData['svcName'] = 'UniThirdPay';
        $postData['merId'] =$partner ;
        $postData['merchOrderId'] = $data['ordernumber'];
        $postData['tranType'] = $service_type;
        $postData['pName'] = 'Recharge';
        $postData['amt'] = $data['money']*100;
        $postData['notifyUrl'] = $callbackurl;
        $postData['retUrl'] = $sameBackUrl;
        $postData['showCashier'] = '0';
        $postData['md5value'] = strtoupper(md5($this->azSign($postData).$key));
        $res = $this->send_post($apiUrl,http_build_query($postData));
        $json = json_decode($res,true);
        if (!empty($res)){
            if (strstr($data['card_info']['netway'], "WAP") > '-1') {
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
                    echo $res;
                }
            }else{
                if ($json['retCode'] = '0000' && !empty($json['payUrl'])){
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
                            header("location:" . '../pay/qrcode.php?code=' . $json['payUrl'] . '&netway=' . $data['card_info']['netway'] . '&amount=' . $data['money'] ."&cdn=" .$this->cdn() );
                    }
                } else {
                    echo '系统繁忙，请稍后访问';
                    $this->logdump('wuFu', $postData);
                    $this->logdump('wuFu', $res);
                }
            }
        }else{
            echo '系统繁忙，请稍后访问';
            $this->logdump('wuFu', $postData);
            $this->logdump('wuFu', $res);
        }
    }
    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('wuFu', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $ordernumber = isset($response['merchOrderId']) ? $response['merchOrderId'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('wuFu', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['status']) ? $response['status'] : '';//1:支付成功，非1为支付失败
        if ($orderstatus !== '0') {
            $this->logdump('wuFu', '回掉(订单状态为空)');
            exit;
        }
        $paymoney = isset($response['amt']) ? $response['amt'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('wuFu', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = !empty($response['orderId']) ? $response['orderId'] : '';//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('wuFu', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['md5value']) ? $response['md5value'] : '';
        if (empty($sign)) {
            $this->logdump('wuFu', '回掉(签名不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('wuFu', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('wuFu', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('wuFu', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('wuFu', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $wufuSign = strtoupper(md5($this->azSign($response).$key));
        if ($response['md5value'] == $wufuSign)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'wuFu');
        } else {
            $this->logdump('wuFu', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}