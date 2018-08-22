<?php
/**
 * 利盈支付
 * @author L
 * @time 2017年11月30日 12:38:31
 */
namespace sscapp\payment;
class lyWap extends payModel{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_requestUrl = 'http://103.78.122.231:8356/scanpay.php';//第三方请求地址

    /**
     * @param  array $params
     * @param  string $key
     * @return string $sign
     * @author L
     */
    public function lyMD5Sign($params,$key){

        ksort($params);
        $hbq = http_build_query($params)."&key=".$key;
        $sign = strtoupper( md5( $hbq ) );
        return $sign;
    }
    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        $number = '1003'.$this->randomkeys(6).time().$this->randomkeys(8);
        return $number;
        // TODO: Implement orderNumber() method.
    }
    /**
     * 生成随机
     * @param string $length 长度
     * @return string $key 返回的值
     * @author L
     */
    function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = '';
        for($i=0;$i<$length;$i++)
        {
            $key .= $pattern{mt_rand(0,35)};    //生成php随机数
        }
        return $key;
    }
    /**
     * POST提交
     * @param string $url 提交地址
     * @param array $post_data 提交数据
     * @return json $result 提交的返回数据
     * @author L
     */
    public function send_post($url, $post_data)//传输
    {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60
            ) // 超时时间（单位:s）

        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
    /**
     * 支付方法
     * @param array $data 需要的数据
     * @return string $url 跳转链接
     * @author L
     */
    public function run($data = []){
        $card_info =isset($data['card_info'])?$data['card_info']:'';
        if(empty($card_info))
        {
            $this->_error = '卡信息出错,请联系客服!';
            return false;
        }
        if(empty($card_info['mer_key']))
        {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if(empty($card_info['mer_no']))
        {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_partner = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_callbackurl = 'http://'.$_SERVER['HTTP_HOST'].'/pay/backPay/'.end($className);
        $service_type ='';
        if ($card_info["netway"] == "QQ_WAP") {
            $service_type = "06";
        }elseif ($card_info["netway"] == "QQ"){
            $service_type = '05';
        }elseif ($card_info["netway"] == "ZFB"){
            $service_type = '02';
        }elseif ($card_info["netway"] == "JD"){
            $service_type = '07';
        }elseif ($card_info["netway"] == "WX_WAP"){
            $service_type = '08';
        }else{
            $this->_error = '此卡暂不支持该充值方式!'.$service_type;
            return false;
        }
        $orderCreateTime = $orderCreateTime = date('YmdHis', time());
        $postData['out_trade_no'] = $data['ordernumber'];//订单号
        $postData['mch_id'] = $card_info['mer_no'];//商户号
        $postData['total_fee'] = $data['money']*100;//支付金额
        $postData['time_start'] = $orderCreateTime;//订单创建时间
        $postData['notify_url'] = $this->_callbackurl;
        $postData['nonce_str'] = 'thisisvip';
        $postData['trade_type'] = $service_type;
        $postData['sign'] = $this->lyMD5Sign($postData,$this->_key);
        $postData['body'] = '充值';
        $postData['attach'] = '这是干嘛的';
        $res = $this->send_post($this->_requestUrl, $postData);
        if(empty($res))
        {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }
        if (is_object(json_decode($res)))
        {
            $arrBack = json_decode($res);
            if(($arrBack->status !== 'SUCCESS') || ($arrBack->result_code == 'FAIL'))
            {
                $this->logdump('lyWap',$arrBack);
                $this->_error = '支付通道异常,请选择其它支付!';
                return false;
            }
        }
        $url = isset($arrBack->code_url)?$arrBack->code_url:'';
        if(!$url) {
            $this->_error = '支付通道异常,请选择其它支付!';
            $this->logdump('lyWap', $res);
            return false;
        }
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
<form name="diy" id="diy" action="$url"  method="get">
   
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;

        return ['code' => 'html', 'html' => $str];
        //return ['code'=>'url','url'=>$url ];
        // TODO: Implement run() method.
    }

    public function callback($response = [], $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('lyWap','回掉异常(第三方返回参数为空,请支付小组查看处理!)');exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $partner = isset($response['mch_id']) ? $response['mch_id'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('lyWap','回掉(商户ID不正确!)');exit;
        }
        $ordernumber = isset($response['out_trade_no']) ? $response['out_trade_no'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('lyWap','回掉(订单号为空!)');exit;
        }
        $orderstatus = isset($response['trade_type']) ? $response['trade_type'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('lyWap','回掉(订单状态为空)');exit;
        }

        $paymoney = isset($response['total_fee']) ? $response['total_fee'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('lyWap','回掉-订单金额为空');exit;
        }
        $sysnumber = isset($response['trade_no']) ? $response['trade_no'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('lyWap','回掉(利盈订单号为空!)');exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('lyWap','回掉(签名不正确!)');exit;
        }
        $transTime = isset($response['time_end']) ? $response['time_end'] : 0;
        if (empty($transTime)) {
            $this->logdump('lyWap','回掉(发送时间不正确!)');exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if(empty($deposits))
        {
            $this->logdump('lyWap','没有找到订单:'.$ordernumber.'的在线支付信息');exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if(empty($card_info))
        {
            $this->logdump('lyWap','没有找到订单:'.$ordernumber.'的支付卡信息1');exit;
        }
        if(empty($card_info['mer_no']))
        {
            $this->logdump('lyWap','配置有误,请联系客服!');exit;
        }
        $this->_partner = $card_info['mer_no'];
        if(empty($card_info['mer_key']))
        {
            $this->logdump('lyWap','配置有误,请联系客服!');exit;
        }
        $this->_key = $card_info['mer_key'];
        $sigArray = array(
            "mch_id" => $response['mch_id'],
            "nonce_str" => $response['nonce_str'],
            "out_trade_no" => $response['out_trade_no'],
            "time_end" => $response['time_end'],
            "total_fee" => $response['total_fee'],
            "trade_no" => $response['trade_no'],
            "trade_state" => $response['trade_state'],
            "trade_type" => $response['trade_type'],
        );
        $checkSign = $this->lyMD5Sign($sigArray,$this->_key);
        if ($checkSign == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits,$sysnumber,'lyWap');
        } else {
            $this->logdump('lyWap','回掉(签名验证不通过!请支付小组查看!)');exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }




}








