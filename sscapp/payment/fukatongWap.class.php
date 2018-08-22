<?php
/**
 * 福卡通QQ
 */

namespace sscapp\payment;


class fukatongWap extends payModel
{
    private $_merchant_code = '';//商户号//1
    private $_key = '';//商户key
    private $_inform_url = ''; //异步回掉地址1
    private $_return_url = '';//1
    private $_p7_version = 'v2.8';
    private $_p8_signtype = 1;
    private $_p9_attach = '';
    private $_p10_appname = '';
    private $_p11_isshow = 0;


    private $_requestUrl = 'http://pay.fktpay.vip/gateway/pay.html';

    public function orderNumber($asd = 0)
    {
        $orderno = "1016" . date("YmdHis") . rand(1000, 9999);
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

        $this->_merchant_code = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_inform_url = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_return_url = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "QQ") {
            $service_type = "5";
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $dataTmp = array(
            'merchant_code' => $this->_merchant_code,//1
            'pay_type' => $service_type,//1
        );
        $dataTmp['input_charset'] = "UTF-8";//1
        $dataTmp['inform_url'] = $this->_inform_url;//1
        $dataTmp['return_url'] = $this->_return_url;//1
        $dataTmp['order_no'] = $data['ordernumber'];//1
        $encrypted = openssl_encrypt($data['money'],'aes-128-ecb',hex2bin($this->_key),OPENSSL_RAW_DATA);//1
        $dataTmp['order_amount'] = strtoupper(bin2hex($encrypted));//1
        $dataTmp['order_time'] = date('Y-m-d H:i:s', time());//1
        $dataTmp['customer_ip'] = $this->getClientIp();//1
        $dataTmp['sign'] = MD5("customer_ip=".$dataTmp['customer_ip']."&inform_url=".$dataTmp['inform_url']."&input_charset=".$dataTmp['input_charset']."&merchant_code=".$dataTmp['merchant_code']."&order_amount=".$dataTmp['order_amount']."&order_no=".$dataTmp['order_no']."&order_time=".$dataTmp['order_time']."&pay_type=".$dataTmp['pay_type']."&return_url=".$dataTmp['return_url']."&key=".$this->_key);//1
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
<input type="hidden" name="input_charset" value="{$dataTmp['input_charset']}">
    <input type="hidden" name="inform_url" value="{$dataTmp['inform_url']}">
    <input type="hidden" name="return_url" value="{$dataTmp['return_url']}">
    <input type="hidden" name="pay_type" value="{$dataTmp['pay_type']}">
    <input type="hidden" name="merchant_code" value="{$dataTmp['merchant_code']}">
    <input type="hidden" name="order_no" value="{$dataTmp['order_no']}">
    <input type="hidden" name="order_amount" value="{$dataTmp['order_amount']}">
    <input type="hidden" name="order_time" value="{$dataTmp['order_time']}">
    <input type="hidden" name="customer_ip" value="{$dataTmp['customer_ip']}">
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
            $this->logdump('fukatongWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $merchant_code = isset($response['merchant_code']) ? $response['merchant_code'] : 0;//商户ID
        if ($merchant_code === 0) {
            $this->logdump('fukatongWap', '回掉(商户ID不正确!)');
            exit;
        }
        $order_no = isset($response['order_no']) ? $response['order_no'] : '';//订单号
        if (empty($order_no)) {
            $this->logdump('fukatongWap', '回掉(订单号为空!)');
            exit;
        }
        $order_amount = isset($response['order_amount']) ? $response['order_amount'] : '';//1:支付成功，非1为支付失败
        if (empty($order_amount)) {
            $this->logdump('fukatongWap', '回掉(金额为空)');
            exit;
        }

        $order_time = isset($response['order_time']) ? $response['order_time'] : "";//订单金额 单位元（人民币）
        if (empty($order_time)) {
            $this->logdump('fukatongWap', '回掉-订单时间为空');
            exit;
        }
        $trade_status = isset($response['trade_status']) ? $response['trade_status'] : "";//月宝订单号
        if (empty($trade_status)) {
            $this->logdump('fukatongWap', '回掉(交易状态为空!)');
            exit;
        }

        $trade_no = isset($response['trade_no']) ? $response['trade_no'] : "";//月宝订单号
        if (empty($trade_no)) {
            $this->logdump('fukatongWap', '回掉(订单号为空!)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : "";//月宝订单号
        if (empty($sign)) {
            $this->logdump('fukatongWap', '回掉(签名为空!)');
            exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($order_no);
        if (empty($onlinepayment)) {
            $this->logdump('fukatongWap', '没有找到订单:' . $order_no . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$order_no}'");

        if (empty($deposits)) {
            $this->logdump('fukatongWap', '没有找到订单:' . $order_no . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('fukatongWap', '没有找到订单:' . $order_no . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('fukatongWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_merchant_code = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('fukatongWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $fktsign = MD5("merchant_code=".$merchant_code."&order_amount=".$order_amount."&order_no=".$order_no."&order_time=".$order_time."&trade_no=".$trade_no."&trade_status=".$trade_status."&key=".$this->_key);
        if ($fktsign == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $order_no, 'fukatongWap');
        } else {
            $this->logdump('fukatongWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}