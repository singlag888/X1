<?php
/**
 * 泽圣支付
 * @author L
 * @time 2017年11月30日 12:40:33
 */
namespace sscapp\payment;

class zshWap extends payModel
{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_requestUrl = 'http://spayment.zsagepay.com/wap/createOrder.do';//第三方请求地址

    /**
     * 签名生成原签名字符串
     * @param array $sign_fields 初始字段
     * @param array $map 带值字段
     * @param string $md5_key KEY
     * @return string $sign_src 签名字符串
     * @author L
     */
    public function sign_src($sign_fields, $map, $md5_key)//泽圣签名用
    {
        // 排序-字段顺序
        sort($sign_fields);
        $sign_src = '';
        foreach ($sign_fields as $field) {
            $sign_src .= $field . '=' . $map[$field] . '&';
        }
        $sign_src .= 'KEY=' . $md5_key;
        return $sign_src;
    }

    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        return '1002' . $this->randomkeys(6) . time() . $this->randomkeys(8);
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
     * MD5签名
     * @param array $sign_fields 初始字段
     * @param array $map 带值字段
     * @param string $md5_key KEY
     * @return string $sign_src md5签名字符串
     * @author L
     */
    public function sign_mac($sign_fields, $map, $md5_key)
    {
        //泽圣签名用
        $sign_src = $this->sign_src($sign_fields, $map, $md5_key);
        return md5($sign_src);
    }

    /**
     * 验签生成原签名字符串
     * @param array $sign_fields 初始字段
     * @param array $map 带值字段
     * @param string $md5_key KEY
     * @return string $sign_src 签名字符串
     * @author L
     */
    public function signCheck($sign_fields, $map, $md5_key)
    {//验证返回签名
        sort($sign_fields);
        $sign_src = '';
        foreach ($sign_fields as $field) {
            $sign_src .= $field . '=' . $map[$field] . '&';
        }
        $sign_src .= 'KEY=' . $md5_key;
        return $sign_src;
    }

    /**
     * MD5验签
     * @param array $sign_fields 初始字段
     * @param array $map 带值字段
     * @param string $md5_key KEY
     * @return string $sign_src md5签名字符串
     * @author L
     */
    public function zsCheckSign($sign_fields, $map, $md5_key)
    {//验证返回签名
        $sign_src = $this->signCheck($sign_fields, $map, $md5_key);
        return md5($sign_src);
    }

    /**
     * 泽圣POST提交
     * @param string $url 提交地址
     * @param array $post_data 提交数据
     * @return mixed $result 提交的返回数据
     * @author L
     */
    public function send_post($url, $post_data)//泽圣传输
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
        return file_get_contents($url, false, $context);
    }

    /**
     * 泽圣回调验签
     * @param array $data
     * @param string $mer_key
     * @return bool
     * @author L
     */
    public function zshCheckSign($data, $mer_key)
    {
        $stringA = 'instructCode=' . $data['instructCode'] . '&merchantCode=' . $data['merchantCode'] . '&outOrderId=' . $data['outOrderId'] . '&totalAmount=' . $data['totalAmount'] . '&transTime=' . $data['transTime'] . '&transType=' . $data['transType'] . '&KEY=' . $mer_key;
        $sin0 = md5($stringA);
        $sign1 = $data['sign'];
        $sign = strtoupper($sin0);
        return $sign == $sign1;
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
        $service_type = '';
        if ($card_info['netway'] == 'WX_WAP') {
            $service_type = "00";
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = '01';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $service_type = '02';
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $orderCreateTime = $orderCreateTime = date('YmdHis', time());

        //签名用
        $sign_fields1 = [
            'merchantCode',
            'outOrderId',
            'totalAmount',
            'merchantOrderTime',
            'notifyUrl',
            'randomStr'
        ];
        $map1 = [
            'merchantCode' => $card_info['mer_no'],
            'outOrderId' => $data['ordernumber'],//第三方订单号
            'totalAmount' => $data['money'] * 100,
            'merchantOrderTime' => $orderCreateTime,
            'notifyUrl' => $this->_callbackurl,
            'randomStr' => '0'
        ];

        $sign0 = $this->sign_mac($sign_fields1, $map1, $this->_key);//生成签名
        $sign1 = strtoupper($sign0);//签名转大写
        $postData['outOrderId'] = $data['ordernumber'];//订单号
        $postData['merchantCode'] = $card_info['mer_no'];//商户号
        $postData['totalAmount'] = $data['money'] * 100;//支付金额
        $postData['merchantOrderTime'] = $orderCreateTime;//订单创建时间
        $postData['notifyUrl'] = $this->_callbackurl;
        $postData['randomStr'] = '0';
        $postData['payWay'] = $service_type;
        $postData['sign'] = $sign1;
        //$res = $this->curlPost($this->_requestUrl, $postData, end($className));
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
    <input type="hidden" name="outOrderId" value="{$postData['outOrderId']}">
    <input type="hidden" name="merchantCode" value="{$postData['merchantCode']}">
    <input type="hidden" name="totalAmount" value="{$postData['totalAmount']}">
    <input type="hidden" name="merchantOrderTime" value="{$postData['merchantOrderTime']}">
    <input type="hidden" name="notifyUrl" value="{$postData['notifyUrl']}">
    <input type="hidden" name="randomStr" value="{$postData['randomStr']}">
    <input type="hidden" name="payWay" value="{$postData['payWay']}">
    <input type="hidden" name="sign" value="{$postData['sign']}">
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;
        if (empty($res)) {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }
        if (is_object(json_decode($res))) {
            $arrBack = json_decode($res);
            if ($arrBack->status == 0) {
                $this->_error = '支付通道维护,请选择其他充值';
                $this->logdump('zshWap', $arrBack->message);
                return false;
            }
        }
//        preg_match('/<body[^>]*>([\s\S]*)<\/body>/', $res, $body);
//        preg_match('/href=\'([^(\}>)]+)\'/', $body[1], $arr);
        $url = isset($res) ? $res : '';
        if (!$url) {
            $this->_error = '此充值通道异常,请选择其它充值通道充值!';
            $this->logdump('zshWap', $res);
            return false;
        }
        return ['code' => 'html', 'url' => isset($res) ? $url : ''];
    }

    public function callback($response = [], $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('zshWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $partner = isset($response['merchantCode']) ? $response['merchantCode'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('zshWap', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['outOrderId']) ? $response['outOrderId'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('zshWap', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['transType']) ? $response['transType'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('zshWap', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['totalAmount']) ? $response['totalAmount'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('zshWap', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['instructCode']) ? $response['instructCode'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('zshWap', '回掉(泽圣订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('zshWap', '回掉(签名不正确!)');
            exit;
        }
        $transTime = isset($response['transTime']) ? $response['transTime'] : 0;
        if (empty($transTime)) {
            $this->logdump('zshWap', '回掉(发送时间不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('zshWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('zshWap', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('zshWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('zshWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];

        if ($this->zshCheckSign($response, $this->_key))//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', '00', $deposits, $sysnumber, 'zshWap');
        } else {
            $this->logdump('zshWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}