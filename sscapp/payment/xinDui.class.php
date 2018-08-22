<?php
/**
 * 信兑支付
 * @author L
 * @time 2017年12月14日 13:03:07
 */
namespace sscapp\payment;
class xinDui extends payModel
{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_sameBackUrl = '';//
    private $_requestUrl = 'http://xdpay.zhongguangbeinengkeji.com/api/pay/orderPay?';//第三方请求地址

    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        return '1024' . $this->randomkeys(6) . time() . $this->randomkeys(8);
    }

    /**
     * 顺序排例
     * @param array $data
     * @return string $str
     * @author L
     */
    public function azSign($data){
        ksort($data);
        $str = '';
        foreach ($data as $key => $value){
            if(!empty($value) && $key != 'sign' && $value != 'null'){
                $str .=  $key . "=" .$value . "&";
            }
        }
        return $str;
    }

    /**
     * 支付
     * @param array $data 提交过来的数据
     * @return qrcode| url | html   返回数据
     * @author L
     * @time 2017年12月14日 13:07:34
     */
    public function run($data = [])
    {
        $card_info = isset($data['card_info']) ? $data['card_info'] : '';
        if (empty($card_info)) {
            $this->_error = '卡信息出错,请联系客服!';
            $this->logdump('test','卡信息出错,请联系客服!');
            return false;
        }
        if (empty($card_info['mer_key'])) {
            $this->_error = '配置有误,请联系客服!';
            $this->logdump('test','卡信息出错,请联系客服!');
            return false;
        }
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
            $this->logdump('test','卡信息出错,请联系客服!');
            return false;
        }
        $this->_partner = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $protocol = $this->getProtocol($area_flag);
        $this->_callbackurl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        $payway = '';
        if ($card_info['netway'] == 'WX') {
            $payway = 'WECHAT';
            $service_type = "WECHAT_SCANPAY";
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $payway = 'WECHAT';
            $service_type = 'WECHAT_H5PAY';
        } elseif ($card_info['netway'] == 'QQ') {
            $payway = 'QQ';
            $service_type = 'QQ_SCANPAY';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $payway = 'QQ';
            $service_type = 'QQ_WAP';
        } elseif ($card_info['netway'] == 'ZFB') {
            $payway = 'ALIPAY';
            $service_type = 'ALIPAY_SCAN_PAY';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $payway = 'ALIPAY';
            $service_type = 'ALIPAY_WAP';
        } else {
            $this->logdump('test','支付方式不存在');
            $this->_error('支付方式不存在，请选择其他方式支付');
            return false;
        }
        $postData['mechno'] = $card_info['mer_no'];
        $postData['orderip'] = $_SERVER['REMOTE_ADDR'];
        $postData['amount'] = $data['money']*100;
        $postData['body'] = 'thisvip';
        $postData['notifyurl'] = $this->_callbackurl;
        $postData['returl'] = $this->_sameBackUrl;
        $postData['orderno'] = $data['ordernumber'];
        $postData['payway'] = $payway;
        $postData['paytype'] = $service_type;
        $postData['sign'] = strtoupper(md5($this->azSign($postData).'key='.$this->_key));
        $url = $this->azSign($postData).'key='.$this->_key.'&sign='.$postData['sign'];
        $http_url = $this->_requestUrl.$url;
        $info = array();
        $res = $this->curlGet($http_url, $info);
        if (!empty(json_decode($res))) {
            $arrBack = json_decode($res,true);
            $url = isset($arrBack['QRCodeUrl']) ? $arrBack['QRCodeUrl'] : '';
            if (!$url) {
                $this->_error = '此充值通道异常,请选择其它充值通道充值!';
                $this->logdump('test', $res);
                return false;
            }
            return ['code' => 'qrcode', 'url' => isset($url) ? $url : ''];
        }else{
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
    <input type="hidden" name="mechno" value="{$postData['mechno']}">
    <input type="hidden" name="orderip" value="{$postData['orderip']}">
    <input type="hidden" name="amount" value="{$postData['amount']}">
    <input type="hidden" name="body" value="{$postData['body']}">
    <input type="hidden" name="notifyurl" value="{$postData['notifyurl']}">
    <input type="hidden" name="returl" value="{$postData['returl']}">
    <input type="hidden" name="orderno" value="{$postData['orderno']}">
    <input type="hidden" name="payway" value="{$postData['payway']}">
    <input type="hidden" name="paytype" value="{$postData['paytype']}">
    <input type="hidden" name="sign" value="{$postData['sign']}">
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;
            $url = isset($res) ? $res : '';
            $str = isset($str) ? $str : '';
            if (empty($url)) {
                $this->_error = '此充值通道异常,请选择其它充值通道充值!';
                $this->logdump('test', $res);
                return false;
            }
            return ['code' => 'html', 'html' => isset($str) ? $str : ''];
        }
    }

    /**
     * 回调
     * @param string $data
     * @return strting  success | other message
     * @auhtor L
     * @time 2017年12月14日 13:41:50
     */
    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('test', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $partner = isset($response['mchid']) ? $response['mchid'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('test', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['outorderno']) ? $response['outorderno'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('test', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['status']) ? $response['status'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('test', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['totalfee']) ? $response['totalfee'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('test', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['transactionid']) ? $response['transactionid'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('test', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('test', '回掉(签名不正确!)');
            exit;
        }
        $transTime = isset($response['charset']) ? $response['charset'] : 0;
        if (empty($transTime)) {
            $this->logdump('test', '回掉(发送时间不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('test', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('test', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('test', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('test', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $sign = strtoupper(md5($this->azSign($response).'key='.$key));
        if ($response['sign'] == $sign)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'test');
        } else {
            $this->logdump('test', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }

}