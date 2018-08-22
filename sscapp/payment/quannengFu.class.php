<?php
namespace sscapp\payment;
class quannengFu extends payModel{
    /**
     * 全能付微信和QQ
     * @param array $data
     * @return string $str
     * @author Jacky
     */

    public function orderNumber($asd = 0)
    {
        $orderno = "1036" . date("YmdHis") . rand(1000, 9999);
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['quannengfu_api'];//http://api.chudianw.com:5051/pay1.0/
        if ($card_info['netway'] == 'WX') {
            $banktype = 'weixin';
        } elseif ($card_info['netway'] == 'QQ') {
            $banktype = 'qq';
        } elseif ($card_info['netway'] == 'ZFB') {
            $banktype = 'alipay';
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $banktype = 'weixin_h5';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $banktype = 'alipay_h5';
        } elseif ($card_info['netway'] == 'WY') {
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
        $postData['pay_fs'] = $banktype;//
        $postData['merchantNo'] = $card_info['ukeypwd'];//
        $postData['pay_orderNo'] = $data['ordernumber'];//
        $postData['pay_Amount'] = $data['money'];//
        $postData['pay_NotifyUrl'] = $callbackurl;//
        $postData['pay_ewm'] = "No";//
        if ($card_info['netway'] == 'ZFB'){
            $postData['tranType'] = "2";
        }
        $postData['pay_MerchantNo'] = $partner;
        $str=$postData['pay_fs']."".$postData['merchantNo']."".$postData['pay_orderNo']."".$postData['pay_Amount']."".$postData['pay_NotifyUrl']."".$postData['pay_ewm']."".$key;
        $postData['sign'] = md5($str);
        unset($postData['merchantNo']);
        $responseText=$this->ewmpost($apiUrl,$postData);
        if (!(strstr($card_info["netway"], "WAP") > -1)){
            $txt= json_decode($responseText,true);
        if(!isset($txt['pay_Status']) || empty($txt['pay_Status'])){
            $this->_error = "商户信息不正确或返回值为空!";
            return false;
        }
        if(!isset($txt['pay_CodeMsg']) || empty($txt['pay_CodeMsg'])){
            $this->_error = "商户信息不正确或返回值为空!";
            return false;
        }
        if(!isset($txt['pay_Code']) || empty($txt['pay_Code'])){
            $this->logdump('quannengFu.log', $postData);
            $this->_error = $txt['pay_CodeMsg'];
            return false;
        }
        $qrcode=$txt['pay_Code'];}


            if (strstr($card_info["netway"], "WAP") > -1) {
                $qrcodeUrl = str_replace("<script>window.location.href='", '', $responseText);
                $qrcodeUrl = str_replace("'</script>", '', $qrcodeUrl);
                return ['code' => 'url', 'url' => $qrcodeUrl];
            } else {
                return ['code' => 'qrcode', 'url' => $qrcode];
            }
    }

    public function ewmpost($url_ewm,$data){
        $ch = curl_init($url_ewm);
        $header = array('apikey: safepay',);
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $responseTextt = curl_exec($ch);
        return $responseTextt;
    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('quannengFu', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }

        if ($is_raw) {
            //parse_str($response, $response);
            $response = json_decode($response,true);
        }
        $response = key($response);
        $response = json_decode($response,true);

        $pay_Amount = isset($response['pay_Amount']) ? str_replace("_",".",$response['pay_Amount']) : 0;//订单号
        //$this->logdump('quannengFuAmount', $pay_Amount);

        if ($pay_Amount === 0) {
            $this->logdump('quannengFu', '回掉(pay_Amount为空!)');
            exit;
        }

        $pay_OrderNo = isset($response['pay_OrderNo']) ? $response['pay_OrderNo'] : '';//订单号
        if (empty($pay_OrderNo)) {
            $this->logdump('quannengFu', '回掉(pay_OrderNo为空!)');
            exit;
        }

        $pay_Status = isset($response['pay_Status']) ? $response['pay_Status'] : '';//1:支付成功，非1为支付失败
        if (empty($pay_Status)) {
            $this->logdump('quannengFu', '回掉(pay_Status为空)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('quannengFu', '回掉(sign为空!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$pay_OrderNo}'");

        if (empty($deposits)) {
            $this->logdump('quannengFu', '没有找到订单:' . $pay_OrderNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('quannengFu', '没有找到订单:' . $pay_OrderNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('quannengFu', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('quannengFu', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $callmerno = $card_info['ukeypwd'];
        //$callsign = md5($callmerno."".$pay_OrderNo."".$pay_Amount."".$key);
        $callsign = md5($callmerno.$pay_OrderNo.$pay_Amount.$key);
        if ($sign == $callsign && $pay_Status == "100")//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $pay_OrderNo, 'quannengFu');
        } else {
            $this->logdump('quannengFu', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}