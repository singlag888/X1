<?php
/**
 * 全宇微信WAP
 * @author Jacky
 * @time 2017年11月30日
 * @group 支付小组
 */

namespace sscapp\payment;


class yhxfWap extends payModel
{
    private $_userId = '';//商户号
    private $_notifyUrl = ''; //异步回掉地址
    private $_frontUrl = ''; //支付同步地址
    private $_key = '';//商户key
    private $_requestUrl = 'http://dsfzf.vnetone.com/createorder/index';

    public function orderNumber($asd = 0)
    {
        $orderno = "4000".date("YmdHis").rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

    /**
     * 生成签名
     */


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

        $this->_userId = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_notifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_frontUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "QQ") {
            $service_type = "4";
        }else{
            $this->_error = '此卡暂不支持该充值方式!'.$service_type;
            return false;
        }
        $orderCreateTime = date('YmdHis', time());
        $dataTmp = array(
            'ordertype' => $service_type,//7
            'mz' => $data['money'],//3
        );

        $dataTmp['spid'] = $this->_userId;//1
        $dataTmp['orderid'] = $data['ordernumber'];//2
        $dataTmp['spzdy'] = date('YmdHis', time());//4
        $dataTmp['uid'] = date('YmdHis', time());//5
        $dataTmp['spsuc'] = $this->_frontUrl;//6
        $dataTmp['interfacetype'] = '1';
        $dataTmp['productname'] = '充值';
        $dataTmp['notifyurl'] = $this->_notifyUrl;//9
        $dataTmp['sign'] = strtoupper(md5("{$dataTmp['spid']}{$dataTmp['orderid']}{$this->_key}{$dataTmp['mz']}{$dataTmp['spsuc']}{$dataTmp['ordertype']}{$dataTmp['interfacetype']}"));
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
<form name="diy" id="diy" action="$this->_requestUrl"  method="get">
    <input type="hidden" name="spid" value="{$dataTmp['spid']}">
    <input type="hidden" name="orderid" value="{$dataTmp['orderid']}">
    <input type="hidden" name="mz" value="{$dataTmp['mz']}">
    <input type="hidden" name="spzdy" value="{$dataTmp['spzdy']}">
    <input type="hidden" name="uid" value="{$dataTmp['uid']}">
    <input type="hidden" name="spsuc" value="{$dataTmp['spsuc']}">
    <input type="hidden" name="ordertype" value="{$dataTmp['ordertype']}">
    <input type="hidden" name="interfacetype" value="{$dataTmp['interfacetype']}">
    <input type="hidden" name="productname" value="{$dataTmp['productname']}">
    <input type="hidden" name="notifyurl" value="{$dataTmp['nnotifyurl']}">
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
            return ['code' => 'html', 'html' => $str];

    }

    /**
     * 签名参数拼装
     * @param $params
     * @return string
     */
    public function qypackageParams($params){
        ksort($params);
        $index = 0;
        $body = '';
        foreach($params as $key => $value ){
            if($index != 0){
                $body .= "&";
            }
            $body .= $key . '=' . $value;
            $index++;
        }
        return $body;
    }

    /**
     * 全宇摸拟提交
     * @param $url
     * @param $post_data
     * @return mixed
     */
    public function qycurlpost($url,$post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
        $output = curl_exec($ch);
        return $output;
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
            $this->logdump('quanyuWap','回掉异常(第三方返回参数为空,请支付小组查看处理!)');exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $amount = isset($response['amount']) ? $response['amount'] : 0;//商户ID
        if ($amount === 0) {
            $this->logdump('quanyuWap','回掉(金额为空!)');exit;
        }
        $body = isset($response['body']) ? $response['body'] : '';//订单号
        if (empty($body)) {
            $this->logdump('quanyuWap','回掉(参数为空!)');exit;
        }
        $outOrderNo = isset($response['outOrderNo']) ? $response['outOrderNo'] : '';//1:支付成功，非1为支付失败
        if (empty($outOrderNo)) {
            $this->logdump('quanyuWap','回掉(订单号为空)');exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : 0;//订单金额 单位元（人民币）
        if ($sign === 0) {
            $this->logdump('quanyuWap','回掉-签名为空');exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($outOrderNo);
        if(empty($onlinepayment))
        {
            $this->logdump('quanyuWap','没有找到订单:'.$outOrderNo.'的在线支付信息');exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$outOrderNo}'");

        if(empty($deposits))
        {
            $this->logdump('quanyuWap','没有找到订单:'.$outOrderNo.'的在线支付信息');exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if(empty($card_info))
        {
            $this->logdump('quanyuWap','没有找到订单:'.$outOrderNo.'的支付卡信息1');exit;
        }
        if(empty($card_info['mer_no']))
        {
            $this->logdump('quanyuWap','配置有误,请联系客服!');exit;
        }
        $this->_MerchantCode = $card_info['mer_no'];
        if(empty($card_info['mer_key']))
        {
            $this->logdump('quanyuWap','配置有误,请联系客服!');exit;
        }
        $this->_key = $card_info['mer_key'];
        $callsign = md5("amount=".$amount."&body=".$body."&outOrderNo=".$outOrderNo."&key=".$this->_key);
        if ($callsign == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits,$outOrderNo,'quanyuWap');
        } else {
            $this->logdump('quanyuWap','回掉(签名验证不通过!请支付小组查看!)');exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}