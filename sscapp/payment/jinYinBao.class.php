<?php
/**
 * 金银宝支付
 * @author L
 * Date: 2018/1/3
 * Time: 15:25
 */
namespace sscapp\payment;
class jinYinBao extends payModel
{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_sameBackUrl = '';//商户key
    private $_requestUrl = 'https://api.rujin8.com/gateway/pay.htm';//第三方请求地址


    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        return '2004' . $this->randomkeys(6) . time() . $this->randomkeys(8);
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
        for ($i = 0; $i < $length; ++$i) {
            $key .= $pattern{mt_rand(0, 35)};    //生成php随机数
        }
        return $key;
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
            if( $key != 'sign'){
                $str .=  $key . "=" .$value . "&";
            }
        }
        return $str;
    }
    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * return 签名结果字符串
     */
    public function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $mysign = "";
        switch (strtoupper(trim('MD5'))) {
            case "MD5" :
                $mysign = $this->md5Sign($prestr, $this->_key);
                break;
            default :
                $mysign = "";
        }

        return $mysign;
    }

    /**
     * 生成要请求给支付服务器的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    public function buildRequestPara($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;

        return $para_sort;
    }
    public function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign")continue;
            else	$para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
   public function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }
    /**
     * 生成要请求给支付服务器的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组字符串
     */
    public function buildRequestParaToString($para_temp) {
        //待请求参数数组
        $para = $this->buildRequestPara($para_temp);

        //把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
        $request_data = createLinkstringUrlencode($para);

        return $request_data;
    }
    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    public function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * POST提交
     * @param string $url 提交地址
     * @param array $post_data 提交数据
     * @return mixed $result 提交的返回数据
     * @author L
     */
    public function send_post($url, $post_data)//泽圣传输
    {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $post_data,
                'timeout' => 15 * 60
            ) // 超时时间（单位:s）

        );
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }

    /**
     * 支付方法
     * @param array $data 需要的数据
     * @return array|bool $url 跳转链接
     * @author L
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
        $this->_partner = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_sameBackUrl ='http://'. $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info['netway'] == 'WX') {
            $service_type = "wxpay";
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $service_type = 'wxpaywap';
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = 'qqpay';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $service_type = 'qqpaywap';
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = 'alipay';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = 'alipaywap';
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $postData['merchant_no'] = $card_info['mer_no'];
        $postData['version'] = '1.0.3';
        $postData['out_trade_no'] = $data['ordernumber'];
        $postData['payment_type'] = $service_type;
        $postData['payment_bank'] = '';
        $postData['notify_url'] = $this->_callbackurl;
        $postData['page_url'] = $this->_sameBackUrl;
        $postData['total_fee'] = $data['money'];
        $postData['trade_time'] = date('YmdHis');
        $postData['user_account'] = 'whatsthis';
        $postData['body'] = '';
        $postData['channel'] = 'default';
        $sign = 'body='.$postData['body'].'&channel='.$postData['channel'].'&merchant_no='.$postData['merchant_no'].'&notify_url='.$postData['notify_url'].'&out_trade_no='.$postData['out_trade_no'].'&page_url='.$postData['page_url'].'&payment_bank=&payment_type='.$postData['payment_type'].'&total_fee='.$postData['total_fee'].'&trade_time='.$postData['trade_time'].'&user_account='.$postData['user_account'].'&version=1.0.3';
        $postData['sign'] = md5($sign . $this->_key);
        $res = <<<HTML
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
    <input type="hidden" name="merchant_no" value="{$postData['merchant_no']}">
    <input type="hidden" name="version" value="{$postData['version']}">
    <input type="hidden" name="out_trade_no" value="{$postData['out_trade_no']}">
    <input type="hidden" name="payment_type" value="{$postData['payment_type']}">
    <input type="hidden" name="payment_bank" value="{$postData['payment_bank']}">
    <input type="hidden" name="notify_url" value="{$postData['notify_url']}">
    <input type="hidden" name="page_url" value="{$postData['page_url']}">
    <input type="hidden" name="total_fee" value="{$postData['total_fee']}">
    <input type="hidden" name="trade_time" value="{$postData['trade_time']}">
    <input type="hidden" name="user_account" value="{$postData['user_account']}">
    <input type="hidden" name="body" value="{$postData['body']}">
    <input type="hidden" name="channel" value="{$postData['channel']}">
    <input type="hidden" name="sign" value="{$postData['sign']}">
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;

        return ['code' => 'html', 'html' => $res];
    }

    public function callback($response = [], $is_raw = 0)
    {
        if (empty($response)) {
            $this->logdump('jinYinBao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $ordernumber = isset($response['out_trade_no']) ? $response['out_trade_no'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('jinYinBao', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['trade_status']) ? $response['trade_status'] : '';//1:支付成功，非1为支付失败
        if ($orderstatus !== 'SUCCESS') {
            $this->logdump('jinYinBao', '回掉(订单状态为空)');
            exit;
        }
        $paymoney = isset($response['total_fee']) ? $response['total_fee'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('jinYinBao', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['trade_no']) ? $response['trade_no'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('jinYinBao', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('jinYinBao', '回掉(签名不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('jinYinBao', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('jinYinBao', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('jinYinBao', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('xzfPay', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $signStr = 'body='.$response['body'].'&merchant_no='.$response['merchant_no'].'&notify_time='.$response['notify_time'].'&obtain_fee='.$response['obtain_fee'].'&out_trade_no='.$response['out_trade_no'].'&payment_bank=&payment_type='.$response['payment_type'].'&total_fee='.$response['total_fee'].'&trade_no='.$response['trade_no'].'&trade_status='.$response['trade_status'].'&version='.$response['version'].$key;
        $sign = md5($signStr);
        if ($response['sign'] == $sign)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $sysnumber, 'jinYinBao');
        } else {
            $this->logdump('jinYinBao', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}