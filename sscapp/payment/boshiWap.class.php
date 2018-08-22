<?php
/**
 * 博士微信WAP,博士京东WAP
 */

namespace sscapp\payment;


class boshiWap extends payModel
{
    private $_MerchantCode = '';//商户号
    private $_NotifyUrl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_requestUrl = 'http://pay.api11.com';

    public function orderNumber($asd = 0)
    {
        $orderno = "1004".date("YmdHis").rand(1000, 9999);
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

        $this->_MerchantCode = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_NotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "WX_WAP") {
            $service_type = "WECHAT_WAP";
        } else if ($card_info["netway"] == "JD_WAP") {
            $service_type = "JD_WAP";
        } else if ($card_info["netway"] == "WX") {
            $service_type = "WECHAT";
        } else if ($card_info["netway"] == "ZFB") {
            $service_type = "ALIPAY";
        } else if ($card_info["netway"] == "QQ") {
            $service_type = "QQ";
        }else{
            $this->_error = '此卡暂不支持该充值方式!'.$service_type;
            return false;
        }
        $dataTmp = array(
            'BankCode' => $service_type,
            'Amount' => $data['money'],
        );
        $dataTmp['MerchantCode'] = $this->_MerchantCode;
        $dataTmp['OrderId'] = $data['ordernumber'];
        $dataTmp['NotifyUrl'] = $this->_NotifyUrl;
        $dataTmp['OrderDate'] = time();
        $dataTmp['key'] = $this->_key;
        $dataTmp['Sign'] = $this->bscreateSign($dataTmp);
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
    <input type="hidden" name="BankCode" value="{$dataTmp['BankCode']}">
    <input type="hidden" name="Amount" value="{$dataTmp['Amount']}">
    <input type="hidden" name="MerchantCode" value="{$dataTmp['MerchantCode']}">
    <input type="hidden" name="OrderId" value="{$dataTmp['OrderId']}">
    <input type="hidden" name="NotifyUrl" value="{$dataTmp['NotifyUrl']}">
    <input type="hidden" name="OrderDate" value="{$dataTmp['OrderDate']}">
    <input type="hidden" name="key" value="{$dataTmp['key']}">
    <input type="hidden" name="Sign" value="{$dataTmp['Sign']}">
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

    public function curlPostData($url, $data, &$info)
    {
        // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl);
        if (curl_errno($curl)) {
            logdump('CURLErrno' . curl_error($curl)); //捕抓异常
        }
        $info = curl_getinfo($curl);
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
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
            $this->logdump('boshiWap','回掉异常(第三方返回参数为空,请支付小组查看处理!)');exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $MerchantCode = isset($response['MerchantCode']) ? $response['MerchantCode'] : 0;//商户ID
        if ($MerchantCode === 0) {
            $this->logdump('boshiWap','回掉(商户ID不正确!)');exit;
        }
        $OrderId = isset($response['OrderId']) ? $response['OrderId'] : '';//订单号
        if (empty($OrderId)) {
            $this->logdump('boshiWap','回掉(订单号为空!)');exit;
        }
        $OrderDate = isset($response['OrderDate']) ? $response['OrderDate'] : '';//1:支付成功，非1为支付失败
        if (empty($OrderDate)) {
            $this->logdump('boshiWap','回掉(订单日期为空)');exit;
        }

        $OutTradeNo = isset($response['OutTradeNo']) ? $response['OutTradeNo'] : 0;//订单金额 单位元（人民币）
        if ($OutTradeNo === 0) {
            $this->logdump('boshiWap','回掉-订单号为空');exit;
        }
        $BankCode = isset($response['BankCode']) ? $response['BankCode'] : 0;//月宝订单号
        if ($BankCode === 0) {
            $this->logdump('boshiWap','回掉(类型为空!)');exit;
        }
        $Amount = isset($response['Amount']) ? $response['Amount'] : '';
        if (empty($Amount)) {
            $this->logdump('boshiWap','回掉(金额为空!)');exit;
        }
        $Status = isset($response['Status']) ? $response['Status'] : '';
        if (empty($Status)) {
            $this->logdump('boshiWap','回掉(状态值为空!)');exit;
        }
        $Time = isset($response['Time']) ? $response['Time'] : '';
        if (empty($Time)) {
            $this->logdump('boshiWap','回掉(时间为空!)');exit;
        }
        $Sign = isset($response['Sign']) ? $response['Sign'] : '';
        if (empty($Sign)) {
            $this->logdump('boshiWap','回掉(签名不正确!)');exit;
        }
        $onlinepayment = \pay::getItemByOrderNumber($OrderId);
        if(empty($onlinepayment))
        {
            $this->logdump('boshiWap','没有找到订单:'.$OrderId.'的在线支付信息');exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$OrderId}'");

        if(empty($deposits))
        {
            $this->logdump('boshiWap','没有找到订单:'.$OrderId.'的在线支付信息');exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if(empty($card_info))
        {
            $this->logdump('boshiWap','没有找到订单:'.$OrderId.'的支付卡信息1');exit;
        }
        if(empty($card_info['mer_no']))
        {
            $this->logdump('boshiWap','配置有误,请联系客服!');exit;
        }
        $this->_MerchantCode = $card_info['mer_no'];
        if(empty($card_info['mer_key']))
        {
            $this->logdump('boshiWap','配置有误,请联系客服!');exit;
        }
        $this->_key = $card_info['mer_key'];
        $signText = 'MerchantCode=['.$response['MerchantCode'].']OrderId=['.$response['OrderId'].']OutTradeNo=['.$response['OutTradeNo'].']Amount=['.$response['Amount'].']OrderDate=['.$response['OrderDate'].']BankCode=['.$response['BankCode'].']Remark=['.$response['Remark'].']Status=['.$response['Status'].']Time=['.$response['Time'].']TokenKey=['.$card_info['mer_key'].']';
        $SignLocal = strtoupper(md5($signText));
        if ($SignLocal == $Sign && $Status == "1")//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits,$OrderId,'boshiWap');
        } else {
            $this->logdump('boshiWap','回掉(签名验证不通过!请支付小组查看!)');exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}