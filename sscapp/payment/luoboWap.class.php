<?php
/**
 * 萝卜微信WAP,快捷,银联,京东
 *
 */

namespace sscapp\payment;


class luoboWap extends payModel
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


    private $_requestUrl = 'http://gt.luobofu.net/chargebank.aspx';

    public function orderNumber($asd = 0)
    {
        $orderno = "1022" . date("YmdHis") . rand(1000, 9999);
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
        if ($card_info["netway"] == "WX_WAP") {
            $service_type = "9932";
        }elseif ($card_info["netway"] == "KJ") {
            $service_type = "9933";
        }elseif ($card_info["netway"] == "YL") {
            $service_type = "9931";
        }elseif ($card_info["netway"] == "JD") {
            $service_type = "996";
        }else{
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $dataTmp = array(
            'parter' => $this->_p1_mchtid,//
            'type' => $service_type,//
            'value' => $data['money'],//
        );
        $dataTmp['orderid'] = $data['ordernumber'];//
        $dataTmp['callbackurl'] = $this->_p5_callbackurl;//
        $dataTmp['hrefbackurl'] = $this->_p6_notifyurl;//
        $dataTmp['attach'] = "";//
        $dataTmp['payerIp'] = $this->getClientIp();//
        $dataTmp['sign'] =  $this->getLuoBoSign($dataTmp, $this->_key);//
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
    <input type="hidden" name="parter" value="{$dataTmp['parter']}">
    <input type="hidden" name="type" value="{$dataTmp['type']}">
    <input type="hidden" name="value" value="{$dataTmp['value']}">
    <input type="hidden" name="orderid" value="{$dataTmp['orderid']}">
    <input type="hidden" name="callbackurl" value="{$dataTmp['callbackurl']}">
    <input type="hidden" name="hrefbackurl" value="{$dataTmp['hrefbackurl']}">
    <input type="hidden" name="payerIp" value="{$dataTmp['payerIp']}">
    <input type="hidden" name="attach" value="{$dataTmp['attach']}">
    <input type="hidden" name="sign" value="{$dataTmp['sign']}">
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;
        return ['code' => 'html', 'html' => $str];

    }

    public function getLuoBoSign($value, $key)
    {
        $toSignString = "parter=" . $value['parter'] . "&type=" . $value['type'] . "&value=". $value['value'] . "&orderid=" . $value['orderid'] . "&callbackurl=" . $value['callbackurl'] . $key;

        $sign = md5($toSignString);
        return $sign;
    }

    public function checkLuoBoSign($array, $mer_key)
    {
        $toSignString = "orderid=" . $array['orderid'] . "&opstate=" . $array['opstate'] . "&ovalue=" . $array['ovalue'] . $mer_key;
        $_sign = md5($toSignString);
        return $_sign == $array['sign'];
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
            $this->logdump('luoboWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }

        /*if ($is_raw) {
            parse_str($response, $response);
        }*/

        $orderid = isset($response['orderid']) ? $response['orderid'] : '';//商户ID
        if (empty($orderid)) {
            $this->logdump('luoboWap', '回掉(商户ID不正确!)');
            exit;
        }

        $opstate = isset($response['opstate']) ? $response['opstate'] : '';//订单号
        if (!isset($opstate)) {
            $this->logdump('luoboWap', '回掉(opstate为空!)');
            exit;
        }

        $ekaorderid = isset($response['ekaorderid']) ? $response['ekaorderid'] : '';//1:支付成功，非1为支付失败
        if (empty($ekaorderid)) {
            $this->logdump('luoboWap', '回掉(ekaorderid为空)');
            exit;
        }

        $ekatime = isset($response['ekatime']) ? $response['ekatime'] : '';//1:支付成功，非1为支付失败
        if (empty($ekatime)) {
            $this->logdump('luoboWap', '回掉(ekatime为空)');
            exit;
        }

        $ovalue = isset($response['ovalue']) ? $response['ovalue'] : 0;//订单金额 单位元（人民币）
        if ($ovalue === 0) {
            $this->logdump('luoboWap', '回掉-付款金额为空');
            exit;
        }

        /*$attach = isset($response['attach']) ? $response['attach'] : '';//月宝订单号
        if (empty($attach)) {
            $this->logdump('luoboWap', '回掉(attach为空!)');
            exit;
        }

        $msg = isset($response['msg']) ? $response['msg'] : '';//月宝订单号
        if (empty($msg)) {
            $this->logdump('luoboWap', '回掉(msg为空!)');
            exit;
        }*/

        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('luoboWap', '回掉(签名为空!)');
            exit;
        }


        $onlinepayment = \pay::getItemByOrderNumber($orderid);
        if (empty($onlinepayment)) {
            $this->logdump('luoboWap', '没有找到订单:' . $orderid . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$orderid}'");

        if (empty($deposits)) {
            $this->logdump('luoboWap', '没有找到订单:' . $orderid . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('luoboWap', '没有找到订单:' . $orderid . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('luoboWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_p1_mchtid = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('luoboWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        if ($this->checkLuoBoSign($response, $this->_key) && $response['opstate'] == "0")//
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'opstate=0', $deposits, $orderid, 'luoboWap');
        } else {
            $this->logdump('luoboWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}