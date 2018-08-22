<?php
namespace sscapp\payment;
class aiShang extends payModel{
    /**
     * 艾尚支付支持微信,QQ和网银
     * @param array $data
     * @return string $str
     * @author Jacky
     */
    public function orderNumber($asd = 0)
    {
        $orderno = "1045" . date("YmdHis") . rand(1000, 9999);
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
        $paytype = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['aishang_api'];
        if ($card_info['netway'] == 'QQ_WAP') {
            $paytype = '30000';
            $postData['tradeRate'] = "1.7";
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $paytype = '70000';
            $postData['tradeRate'] = "3.5";
        } elseif ($card_info['netway'] == 'QQ') {
            $paytype = '20000';
            $postData['tradeRate'] = "1.2";
        } elseif ($card_info['netway'] == 'ZFB') {
            $paytype = '60000';
            $postData['tradeRate'] = "3";
        } elseif ($card_info['netway'] == 'WX') {
            $paytype = '10000';
            $postData['tradeRate'] = "1.3";
        } else {
            $this->_error = '暂时不支持该支付!';
        }
        $postData['version'] = "1.0.0";//
        $postData['service'] = "pay";//
        $postData['platformNo'] = @explode("_",$partner)[1];//
        //$postData['channelNo'] = @explode("_",$partner)[2];
        $postData['merNo'] = explode("_",$partner)[0];//
        $postData['tranType'] = $paytype;//
        $postData['orderAmount'] = $data['money']*100;//
        $postData['subject'] = "lucky";//
        $postData['merOrderNo'] = $data['ordernumber'];//
        $postData['frontUrl'] = $sameBackUrl;//
        $postData['backUrl'] = $callbackurl;////
        $postData['drawFee'] = "1";//
        $postData['desc'] = "ceshi";//
        $postData['signature'] = $this->sign($postData, $key);//
        if ($card_info['netway'] != 'WY' && !(strstr($card_info['netway'], "WAP") > '-1')){
            $jsonstr = $this->curlPost($apiUrl, $postData);
            $arrnew = json_decode($jsonstr,true);
            if(!isset($arrnew['qrCode']) || $arrnew['qrCode'] == "") {
                if (isset($arrnew['respMsg']) && $arrnew['respMsg'] != "") {
                    $this->_error = $arrnew['respMsg'];
                    return false;
                } else {
                    $this->_error = "生成二维码失败!";
                    return false;
                }
            }

        }
        if (strstr($card_info['netway'], "WAP") > '-1'){
            $jsonstr = $this->curlPost($apiUrl, $postData);
            $arrnew = json_decode($jsonstr,true);
            if(!isset($arrnew['payUrl']) || $arrnew['payUrl'] == "") {
                if (isset($arrnew['respMsg']) && $arrnew['respMsg'] != "") {
                    $this->_error = $arrnew['respMsg'];
                    return false;
                } else {
                    $this->_error = "生成二维码失败!";
                    return false;
                }
            }

        }



        if ($card_info['netway'] == 'WY') {
            $res = <<<HTML
            <!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>跳转中</title>
</head>
<body onLoad="document.dingyiPay.submit();">
<a>正在加载，请勿离开页面或者刷新页面</a>
<form name='dingyiPay' id="diy" action="{$apiUrl}" method='post'>
    <input type="hidden" name="version" value="{$postData['version']}"/>
    <input type="hidden" name="platformNo"		  value="{$postData['platformNo']}" />
    <input type="hidden" name="merNo"     value="{$postData['merNo']}"/>
    <input type="hidden" name="tranType"  value="{$postData['tranType']}"/>
    <input type="hidden" name="orderAmount" value="{$postData['orderAmount']}"/>
    <input type="hidden" name="subject" value="{$postData['subject']}"/>
    <input type="hidden" name="merOrderNo" value="{$postData['merOrderNo']}"/>
    <input type="hidden" name="frontUrl"     value="{$postData['frontUrl']}"/>
    <input type="hidden" name="backUrl"  value="{$postData['backUrl']}"/>
    <input type="hidden" name="tradeRate" value="{$postData['tradeRate']}"/>
    <input type="hidden" name="drawFee" value="{$postData['drawFee']}"/>
    <input type="hidden" name="desc" value="{$postData['desc']}"/>
    <input type="hidden" name="service" value="{$postData['service']}"/>
    <input type="hidden" name="signature" value="{$postData['signature']}"/>  
</form>
</body>
</html>

HTML;

            return ['code' => 'html', 'html' => $res];
        } else {
            if (strstr($card_info['netway'], "WAP") > '-1') {
                $asurl = $arrnew['payUrl'];
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
<form name="diy" id="diy" action="$asurl"  method="post">
   
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;

                return ['code' => 'html', 'html' => $str];
                //return ['code' => 'url', 'url' => $arrnew['payUrl']];
            } else {
                return ['code' => 'qrcode', 'url' => $arrnew['qrCode']];
            }

        }
    }


    public function arrToQuery($arrayQuery, $urlEncode = true)
    {
        ksort($arrayQuery);
        $tmp = array();
        foreach ($arrayQuery as $k => $param) {
            $tmp[] = $k . '=' . ($urlEncode ? urlencode($param) : $param);
        }
        $signStr = implode('&', $tmp);
        return $signStr;
    }

    public function sign(&$params, $privKey)
    {
        $params_str = $this->arrToQuery($params, false);
        $status = @openssl_sign($params_str, $signature, $privKey);
        if ($status) {
            $signature_base64 = base64_encode($signature);
            //$params['signature'] = $signature_base64;
            $assign = $signature_base64;
            return $assign;
        }
    }

    public function verify($params, $pubKey)
    {
        $signature_str = $params['signature'];
        unset($params['signature']);
        $params_str = $this->arrToQuery($params, false);
        $signature = base64_decode($signature_str);
        $status = @openssl_verify($params_str, $signature, $pubKey);
        return $status;
    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('aiShang', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
            //$response = json_decode($response,true);
        }

        $orderNo = isset($response['orderNo']) ? $response['orderNo'] : '';//商户ID
        if (empty($orderNo)) {
            $this->logdump('aiShang', '回掉(orderNo为空!)');
            exit;
        }
        $payAmount = isset($response['payAmount']) ? $response['payAmount'] : 0;//订单号
        if ($payAmount === 0) {
            $this->logdump('aiShang', '回掉(payAmount为空!)');
            exit;
        }

        $merOrderNo = isset($response['merOrderNo']) ? $response['merOrderNo'] : '';//订单号
        if (empty($merOrderNo)) {
            $this->logdump('aiShang', '回掉(merOrderNo为空!)');
            exit;
        }

        $status = isset($response['status']) ? $response['status'] : '';//订单金额 单位分（人民币）
        if (empty($status)) {
            $this->logdump('aiShang', '回掉status为空');
            exit;
        }

        $reqTime = isset($response['reqTime']) ? $response['reqTime'] : '';//1:支付成功，非1为支付失败
        if (empty($reqTime)) {
            $this->logdump('aiShang', '回掉(reqTime为空)');
            exit;
        }

        $payTime = isset($response['payTime']) ? $response['payTime'] : '';//月宝订单号
        if (empty($payTime)) {
            $this->logdump('aiShang', '回掉(payTime为空!)');
            exit;
        }

        $signature = isset($response['signature']) ? $response['signature'] : '';//月宝订单号
        if (empty($signature)) {
            $this->logdump('aiShang', '回掉(signature为空!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$merOrderNo}'");

        if (empty($deposits)) {
            $this->logdump('aiShang', '没有找到订单:' . $merOrderNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('aiShang', '没有找到订单:' . $merOrderNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('aiShang', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('aiShang', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['card_pass'];//
        $status = $this->verify($response, $key);//第三方提供的回调验签函数
        if ($status)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $merOrderNo, 'aiShang');
        } else {
            $this->logdump('aiShang', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}