<?php
/**
 * 科讯QQ和QQWAP
 * 充值下限10元
 * @author jacky
 */


namespace sscapp\payment;


class kexunWap extends payModel
{

    private $_merchno = '';//商户ID1
    private $_notifyUrl = ''; //异步回掉地址1
    private $_key = '';//商户key
    private $_requestUrl = 'http://pay.kexunpay.com/ChargeBank.aspx';//第三方请求地址1
    private $_sameBackUrl = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1020' . date('YmdHis') . rand(100, 999) . rand(100, 999) . rand(1, 9);
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
        $bankId = $card_info['bank_id'];
        if (empty($bankId)) {
            $this->_error = '配置有误,请联系客服3!';
            return false;
        }
        $this->_merchno = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $className = end($className);
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $protocol = $this->getProtocol($area_flag);
        $this->_notifyUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . $className;
        $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . $className;
        $netway = $card_info['netway'];
        if ($netway == "QQ_WAP") {
            $service_type = "1007";
        }elseif ($netway == "QQ"){
            $service_type = "1006";
        } else {
            $this->_error = '此卡暂不支持该充值方式!';
            return false;
        }
        $dataTmp['parter'] = $this->_merchno;//
        $dataTmp['orderid'] = $data['ordernumber'];//
        $dataTmp['value'] = $data['money'];//
        $dataTmp['callbackurl'] = $this->_notifyUrl;//
        $dataTmp['hrefbackurl'] = $this->_sameBackUrl;//
        $dataTmp['type'] = $service_type;//
        $dataTmp['sign'] = md5("parter=".$dataTmp['parter'] ."&type=".$dataTmp['type'] ."&value=". $dataTmp['value']."&orderid=".$dataTmp['orderid']."&callbackurl=".$dataTmp['callbackurl'].$this->_key);//

        $kxurl = $this->_requestUrl."?parter=".$dataTmp['parter'] ."&type=".$dataTmp['type'] ."&value=". $dataTmp['value']."&orderid=".$dataTmp['orderid']."&callbackurl=".$dataTmp['callbackurl']."&hrefbackurl=".$dataTmp['hrefbackurl']."&sign=".$dataTmp['sign'];

        if (!(strstr($netway, "WAP") > -1)) {
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
    <input type="hidden" name="orderid" value="{$dataTmp['orderid']}">
    <input type="hidden" name="value" value="{$dataTmp['value']}">
    <input type="hidden" name="callbackurl" value="{$dataTmp['callbackurl']}">
    <input type="hidden" name="hrefbackurl" value="{$dataTmp['hrefbackurl']}">
    <input type="hidden" name="type" value="{$dataTmp['type']}">
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

        return ['code' => 'url', 'url' => $kxurl];


        // TODO: Implement run() method.
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
            $this->logdump('changchengfu', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $version = isset($response['version']) ? $response['version'] : '';//返回的状态码
        if (empty($version)) {
            $this->logdump('yafuWap', '回掉version为空:' . $response);
            exit;
        }
        $transAmt = isset($response['transAmt']) ? $response['transAmt'] : 0;//订单号
        if ($transAmt == 0) {
            $this->logdump('yafuWap', '没有反回支付金额');
            exit;
        }

        $consumerNo = isset($response['consumerNo']) ? $response['consumerNo'] : '';//订单号
        if (empty($consumerNo)) {
            $this->logdump('yafuWap', '没有反回consumerNo信息');
            exit;
        }
        $merOrderNo = isset($response['merOrderNo']) ? $response['merOrderNo'] : '';
        if (empty($merOrderNo)) {
            $this->logdump('yafuWap', '没有返回merOrderNo');
            exit;
        }

        $orderNo = isset($response['orderNo']) ? $response['orderNo'] : '';//1:支付成功，非1为支付失败
        if (empty($orderNo)) {
            $this->logdump('yafuWap', '回掉(orderNo为空)');
            exit;
        }
        $orderStatus = isset($response['orderStatus']) ? $response['orderStatus'] : '';//订单金额 单位元（人民币）
        if ( empty($orderStatus)) {
            $this->logdump('yafuWap', '回掉(orderStatus为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('yafuWap', '回掉(sign为空!)');
            exit;
        }

        $payType = isset($response['payType']) ? $response['payType'] : '';
        if (empty($payType)) {
            $this->logdump('yafuWap', '回掉(payType为空!)');
            exit;
        }
        unset($response["sign"]);

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$merOrderNo}'");
        if (empty($deposits)) {
            $this->logdump('yafuWap', '没有找到订单:' . $merOrderNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('yafuWap', '没有找到订单:' . $merOrderNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('yafuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_merchno = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('yafuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];

        $callsign = strtoupper($this->yfsignstr($response,$this->_key));

        if ($sign == $callsign)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $merOrderNo, 'yafuWap');
        } else {
            $this->logdump('yafuWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}