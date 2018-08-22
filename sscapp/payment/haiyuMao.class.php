<?php
namespace sscapp\payment;
class haiyuMao extends payModel{
    /**
     * 海羽毛支付宝WAP
     * @param array $data
     * @return string $str
     * @author Jacky
     */
    public function orderNumber($asd = 0)
    {
        $orderno = "1046" . date("YmdHis") . rand(1000, 9999);
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['haiyumao_api'];
        if ($card_info['netway'] == 'ZFB_WAP') {
              $service_type = "23";
        } elseif ($card_info['netway'] == 'QQ') {

        } elseif ($card_info['netway'] == 'QQ_WAP') {

        } else {
            $this->_error = '暂时不支持该支付';
        }
        $postData['amount'] = $data['money'];
        $postData['appid'] = $partner;
        $postData['subject'] = 'lucky';//
        $postData['orderid'] = $data['ordernumber'];//
        $postData['payType'] = $service_type;
        $postData['notifyUrl'] = $callbackurl;//
        $postData['returnUrl'] = $sameBackUrl;
        $postData['orderInfo'] = "lucky";
        $s = "";
        ksort($postData);
        foreach($postData as $k => $v){
            $s .= $k ."=" .$v ."&";
        }
        $s .= "key=".$key;
        $postData['signature'] = md5($s);
        $postData = json_encode($postData);
        $jsonreturnv = $this->jsonpost($apiUrl,$postData);
        $jsonarr = json_decode($jsonreturnv,true);

        if (isset($jsonarr['data']['pay_url']) && !empty($jsonarr['data']['pay_url'])){


                if (strstr($card_info['netway'], "WAP") > -1) {
                    return ['code' => 'url', 'url' => $jsonarr['data']['pay_url']];
                    die();
                } else {
                    return ['code' => 'qrcode', 'url' => $jsonarr['data']['pay_url']];
                }


        } else {
            if (isset($jsonarr['data']['msg'])) {
                $this->_error = $jsonarr['data']['msg'];
                log2file('haiyuMao.log', $postData);
                log2file('haiyuMao.log', $jsonreturnv);
            } else {
                $this->_error = "生成二维码失败!";
                log2file('haiyuMao.log', $postData);
                log2file('haiyuMao.log', $jsonreturnv);
            }
        }
    }

    public function jsonpost ($url,$p){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $p);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);

        return $result;
    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('haiyuMao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            //parse_str($response, $response);
            $response = json_decode($response,true);

        }

        $orderid = isset($response['orderid']) ? $response['orderid'] : '';//商户ID
        if (empty($orderid)) {
            $this->logdump('haiyuMao', '回掉(orderid为空!)');
            exit;
        }
        $amount = isset($response['amount']) ? $response['amount'] : 0;//订单号
        if ($amount === 0) {
            $this->logdump('haiyuMao', '回掉(amount为空!)');
            exit;
        }

        $status = isset($response['status']) ? $response['status'] : '';//订单号
        if (empty($status)) {
            $this->logdump('haiyuMao', '回掉(status为空!)');
            exit;
        }

        $signature = isset($response['signature']) ? $response['signature'] : '';//订单金额 单位分（人民币）
        if (empty($signature)) {
            $this->logdump('haiyuMao', '回掉-signature为空');
            exit;
        }

        $appid = isset($response['appid']) ? $response['appid'] : '';//1:支付成功，非1为支付失败
        if (empty($appid)) {
            $this->logdump('haiyuMao', '回掉(appid为空)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$orderid}'");

        if (empty($deposits)) {
            $this->logdump('haiyuMao', '没有找到订单:' . $orderid . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('haiyuMao', '没有找到订单:' . $orderid . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('haiyuMao', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('haiyuMao', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        unset($response['signature']);
        $s = "";
        ksort($response);
        foreach($response as $k => $v){
            $s .= $k ."=" .$v ."&";
        }
        $s .= "key=".$key;
        $callsign = md5($s);
        if ($callsign == $signature)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $orderid, 'haiyuMao');
        } else {
            $this->logdump('haiyuMao', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}