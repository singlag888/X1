<?php
/**
 * 金阳QQWAP
 */

namespace sscapp\payment;


class jinyangWap extends payModel
{
    private $_p1_mchtid = '';//商户号
    private $_key = '';//商户key
    private $_p5_callbackurl = ''; //异步回掉地址
    private $_p6_notifyurl = '';
    private $_p7_version = 'v2.8';
    private $_p8_signtype = 1;
    private $_p9_attach = '';
    private $_p10_appname = '';
    private $_p11_isshow = 0;


    private $_requestUrl = 'http://pay.095pay.com/zfapi/order/pay';

    public function orderNumber($asd = 0)
    {
        $orderno = "1005" . date("YmdHis") . rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

    /**
     * 生成签名
     */
    public function bscreateSign($params)
    {
        $signText = 'MerchantCode=[' . $params['MerchantCode'] . ']OrderId=[' . $params['OrderId'] . ']Amount=[' . $params['Amount'] . ']NotifyUrl=[' . $params['NotifyUrl'] . ']OrderDate=[' . $params['OrderDate'] . ']BankCode=[' . $params['BankCode'] . ']TokenKey=[' . $params["key"] . ']';
        return strtoupper(md5($signText));
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
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }

        $this->_p1_mchtid = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_p5_callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_p6_notifyurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "QQ_WAP") {
            $service_type = "QQPAYWAP";
        } elseif($card_info["netway"] == "WX_WAP") {
            $service_type = "WEIXINWAP";
        } else if ($card_info["netway"] == "QQ") {
            $service_type = "QQPAY";
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $dataTmp = array(
            'p1_mchtid' => $this->_p1_mchtid,
            'p2_paytype' => $service_type,//
            'p3_paymoney' => $data['money'],//
        );
        $dataTmp['p4_orderno'] = $data['ordernumber'];//
        $dataTmp['p5_callbackurl'] = $this->_p5_callbackurl;//
        $dataTmp['p6_notifyurl'] = $this->_p6_notifyurl;//
        $dataTmp['p7_version'] = $this->_p7_version;//
        $dataTmp['p8_signtype'] = $this->_p8_signtype;//
        $dataTmp['p9_attach'] = $this->_p9_attach;//
        $dataTmp['p10_appname'] = $this->_p10_appname;//
        $dataTmp['p11_isshow'] = $this->_p11_isshow;//
        $dataTmp['p12_orderip'] = $this->getClientIp();//
        $dataTmp['sign'] = $this->jySign($dataTmp, $this->_key);//
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
<form name="diy" id="diy" action="$this->_requestUrl"  method="post">
    <input type="hidden" name="p1_mchtid" value="{$dataTmp['p1_mchtid']}">
    <input type="hidden" name="p2_paytype" value="{$dataTmp['p2_paytype']}">
    <input type="hidden" name="p3_paymoney" value="{$dataTmp['p3_paymoney']}">
    <input type="hidden" name="p4_orderno" value="{$dataTmp['p4_orderno']}">
    <input type="hidden" name="p5_callbackurl" value="{$dataTmp['p5_callbackurl']}">
    <input type="hidden" name="p6_notifyurl" value="{$dataTmp['p6_notifyurl']}">
    <input type="hidden" name="p7_version" value="{$dataTmp['p7_version']}">
    <input type="hidden" name="p8_signtype" value="{$dataTmp['p8_signtype']}">
    <input type="hidden" name="p9_attach" value="{$dataTmp['p9_attach']}">
    <input type="hidden" name="p10_appname" value="{$dataTmp['p10_appname']}">
    <input type="hidden" name="p11_isshow" value="{$dataTmp['p11_isshow']}">
    <input type="hidden" name="p12_orderip" value="{$dataTmp['p12_orderip']}">
    <input type="hidden" name="sign" value="{$dataTmp['sign']}">
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;
        if (strstr($card_info["netway"], "WAP") > -1){
            return ['code' => 'html', 'html' => $str];
        }else{
            $response = $this->jycurlPost($dataTmp, $this->_requestUrl, 2, 60);
            if (!isset($response['data'])) {
                $this->_error = "返回值为空!";
                return false;
            }
            if ($response != null && $response['rspCode'] == 1){
                $imgurl = $response['data']['r6_qrcode'];
                $xinyun =  <<<XINGYUN
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<style>
   .qrcode{
       display: block;
       margin: 100px auto 0;
       width: 300px;
   }
   p{
       text-align: center;
       font-size: 14px;
   }
   @media (max-width: 600px) {
       .qrcode{
           margin: 0 auto;
           width: 80%;
       }
   }
</style>
<body>
<img class="qrcode" src="$imgurl" alt="">
<p>请长按二维码保存或者将二维码截图到相册,使用相应的途径扫码</p>
XINGYUN;
                    return ['code' => 'html', 'html' => $xinyun];
            }else{
                $this->_error = "生成二维码失败!";
                return false;
            }

        }


    }

    public function jycurlPost($aPostData, $sUrl, $respondType = 1, $timeout = 5) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aPostData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11');// 添加浏览器内核信息，解决403问题 add by ben 2017/10/25

        $response = curl_exec($ch);

        if (1 === $respondType) {
            $res = $this->xmlToArray($response);
        } elseif (2 === $respondType) {
            // echo $response;echo '<br>';
            $res = json_decode($response, true);
            // 如果没有decode成功，也许是因为三方用的是GB2312
            if (is_null($res)) {
                $res = json_decode(iconv('GB2312', 'UTF-8', $response), true);
            }
        } else {
            $res = $response;
        }
        curl_close($ch);

        return $res;
    }

    public function jySign($param, $key)
    {
        $string = '';
        foreach ($param as $k => $value) {
            $string .= $k . '=' . $value . '&';
        }
        $string = rtrim($string, '&');
        // echo $string . $key;echo '<br>';
        // echo md5($string . $key);echo '<br>';
        return md5($string . $key);
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
            $this->logdump('jinyangWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $partner = isset($response['partner']) ? $response['partner'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('jinyangWap', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['ordernumber']) ? $response['ordernumber'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('jinyangWap', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['orderstatus']) ? $response['orderstatus'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('jinyangWap', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['paymoney']) ? $response['paymoney'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('jinyangWap', '回掉-付款金额为空');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : 0;//月宝订单号
        if ($sign === 0) {
            $this->logdump('jinyangWap', '回掉(签名类型为空!)');
            exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($ordernumber);
        if (empty($onlinepayment)) {
            $this->logdump('jinyangWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('jinyangWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('jinyangWap', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('jinyangWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_p1_mchtid = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('jinyangWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $callsign = md5("partner={$partner}&ordernumber={$ordernumber}&orderstatus={$orderstatus}&paymoney={$paymoney}{$card_info['mer_key']}");
        if ($callsign == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'ok', $deposits, $ordernumber, 'jinyangWap');
        } else {
            $this->logdump('jinyangWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}