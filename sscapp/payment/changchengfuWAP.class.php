<?php
/**
 * 长城微信WAP
 *充值金额100以上
 * @author stone
 */

namespace sscapp\payment;


class changchengfuWAP extends payModel
{

    private $_merchno = '';//商户ID12
    private $_notifyUrl = ''; //异步回掉地址1
    private $_key = '';//商户key2
    private $_requestUrl = 'http://a.cc8pay.com/api/wapPay';//第三方请求地址
    private $_requestUrlscan = 'http://a.cc8pay.com/api/passivePay';
    private $_sameBackUrl = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1018' . date('YmdHis') . rand(100, 999) . rand(100, 999) . rand(1, 9);
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
        if ($netway == "WX_WAP") {
            $service_type = "2";
        } elseif($netway == "QQ") {
            $service_type = "4";
            $sertype = "1";
        } elseif($netway == "QQ_WAP") {
            $service_type = "4";
        } elseif($netway == "ZFB") {
            $service_type = "1";
            $sertype = "1";
        } elseif($netway == "ZFB_WAP") {
            $service_type = "1";
        } elseif($netway == "WX") {
            $service_type = "2";
            $sertype = "1";
        } else {
            $this->_error = '此卡暂不支持该充值方式!';
            return false;
        }
        $dataTmp = array(
            'type' => '2',
        );
        if($sertype = "1"){
            $ccapiurl = $this->_requestUrlscan;
        }else{
            $ccapiurl = $this->_requestUrl;
        }
        $jsonResponse=$this->changChengFuCurlPostRequest($this->_merchno,$data['ordernumber'],$service_type,$data['money'],$this->_notifyUrl,$ccapiurl,$this->_key,$this->_sameBackUrl,$dataTmp['type']);//
        $response=json_decode($jsonResponse,true);//
        if($response['respCode']=='00' && isset($response['barCode']) && !empty($response['barCode'])){
            $ccurl = $response['barCode'];
        }else{
            $this->_error = $response['message'];
            return false;
        }
        if (strstr($card_info['netway'], "WAP") > '-1') {
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
<form name="diy" id="diy" action="$ccurl"  method="post">
   
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;

            return ['code' => 'html', 'html' => $str];
        } else {
            return ['code' => 'qrcode', 'url' => $ccurl];
        }
        // TODO: Implement run() method.
    }


    private function changChengFuCurlPostRequest($merchno,$timetraceno,$paytype,$amount,$notifyUrl,$apiUrl,$key,$returnUrl,$type=1,$timeout = 120){
        $post_data = array(
            'merchno' => $merchno,
            'amount' => $amount,
            'traceno' => $timetraceno,
            'payType' => $paytype,
            'notifyUrl'=>$notifyUrl,
            'goodsName'=>'pay',
            'remark'=>"remark"
        );
        if($type==1){
            $post_data['settleType']='1';
        }else{
            $post_data['returnUrl']=$returnUrl;
        }
        ksort($post_data);
        $a='';
        foreach($post_data as $x=>$x_value)
        {
            $a=$a.$x."=".iconv('UTF-8', 'GB2312',$x_value)."&";
        }
        $b=md5($a.$key);
        $c=$a.'signature'.'='.$b;
        if($apiUrl == '' || $c == '' || $timeout <=0){
            return false;
        }
        $con = curl_init((string)$apiUrl);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POSTFIELDS, $c);
        curl_setopt($con, CURLOPT_POST,true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con, CURLOPT_TIMEOUT,(int)$timeout);
        return iconv('GB2312', 'UTF-8',curl_exec($con));
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
        $merchno = isset($response['merchno']) ? $response['merchno'] : '';//返回的状态码
        if (empty($merchno)) {
            $this->logdump('changchengfu', '回掉商户号为空:' . $response);
            exit;
        }
        $amount = isset($response['amount']) ? $response['amount'] : 0;//订单号
        if ($amount == 0) {
            $this->logdump('changchengfu', '没有反回支付金额');
            exit;
        }

        $traceno = isset($response['traceno']) ? $response['traceno'] : '';//订单号
        if (empty($traceno)) {
            $this->logdump('changchengfu', '没有反回支付处理信息');
            exit;
        }
        $orderno = isset($response['orderno']) ? $response['orderno'] : '';
        if (empty($orderno)) {
            $this->logdump('changchengfu', '没有返回订单号');
            exit;
        }

        $status = isset($response['status']) ? $response['status'] : '';//1:支付成功，非1为支付失败
        if (empty($status)) {
            $this->logdump('changchengfu', '回掉(status为空)');
            exit;
        }
        $signature = isset($response['signature']) ? $response['signature'] : '';//订单金额 单位元（人民币）
        if ( empty($signature)) {
            $this->logdump('changchengfu', '回掉(签名为空!)');
            exit;
        }
        $channelOrderno = isset($response['channelOrderno']) ? $response['channelOrderno'] : '';//月宝订单号

        $channelTraceno = isset($response['channelTraceno']) ? $response['channelTraceno'] : '';

        $merchName = isset($response['merchName']) ? $response['merchName'] : '';
        if (empty($merchName)) {
            $this->logdump('changchengfu', '回掉(merchName为空!)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$traceno}'");
        if (empty($deposits)) {
            $this->logdump('changchengfu', '没有找到订单:' . $traceno . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('changchengfu', '没有找到订单:' . $traceno . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('changchengfu', '配置有误,请联系客服!');
            exit;
        }
        $this->_merchno = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('changchengfu', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        unset($response['signature']);
        ksort($response);
        $a='';
        foreach($response as $x=>$x_value)
        {
            if($x_value){
                $a=$a.$x."=".$x_value."&";
            }
        }
        if ($signature != strtoupper(md5($a.$this->_key))) {
            $this->logdump('签名错误');
            die('签名错误');
        }

        if ($response['status']==1)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $traceno, 'changchengfu');
        } else {
            $this->logdump('changchengfu', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}