<?php
/**
 * 鑫支付
 * @author L
 * @time 2017年12月27日 17:43:59
 */
namespace sscapp\payment;
class xinzf extends payModel
{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_sameBackUrl = '';//商户key
    private $_requestUrl = 'http://pay.api.tzinformation.com/api/wypay/createOrder';//第三方请求地址


    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        return '2001' . $this->randomkeys(6) . time() . $this->randomkeys(8);
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
            if(!empty($value) && $key != 'sign' && $value != 'null'){
                $str .=  $key . "=" .$value . "&";
            }
        }
        return $str;
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
            $service_type = "7";
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $service_type = '3';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $service_type = '9';
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $postData['mchno'] = $card_info['mer_no'];
        $postData['pay_type'] = $service_type;
        $postData['price'] = $data['money']*100;
        $postData['bill_title'] = 'thisiscz';
        $postData['bill_body'] = 'thisisvipgogogo';
        $postData['nonce_str'] = $data['ordernumber'];
        $postData['linkId'] = $data['ordernumber'];
        $postData['sign'] = strtoupper(md5($this->azSign($postData).'key='.$this->_key));
        $postData = json_encode($postData);
        $res = $this->send_post($this->_requestUrl,$postData);
        $code = json_decode($res,true);
        if (empty($res)) {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            $this->logdump('xinzf',$res);
            return false;
        }
        if (strstr($data['card_info']['netway'], "WAP") > '-1') {
            $url = isset($code['order']['pay_link']) ? $code['order']['pay_link'] : '';
            if (empty($url)) {
                $this->_error = '此充值通道异常,请选择其它充值通道充值!';
                $this->logdump('xinzf', $res);
                return false;
            }
            return ['code' => 'url', 'url' => isset($url) ? $url : ''];
        }else{
            $url = isset($code['order']['pay_link']) ? $code['order']['pay_link'] : '';
            if (empty($url)) {
                $this->_error = '此充值通道异常,请选择其它充值通道充值!';
                $this->logdump('xinzf', $res);
                return false;
            }
            return ['code' => 'qrcode', 'url' => isset($url) ? $url : ''];
        }
    }

    public function callback($response = [], $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('xzfPay', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $ordernumber = isset($response['link_id']) ? $response['link_id'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('xzfPay', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['feeResult']) ? $response['feeResult'] : '';//1:支付成功，非1为支付失败
        if ($orderstatus !== '0') {
            $this->logdump('xzfPay', '回掉(订单状态为空)');
            exit;
        }
        $paymoney = isset($response['bill_fee']) ? $response['bill_fee'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('xzfPay', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['bill_no']) ? $response['bill_no'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('xzfPay', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('xzfPay', '回掉(签名不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('xzfPay', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('xzfPay', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('xzfPay', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('xzfPay', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $sign = strtoupper(md5('bill_no='.$response['bill_no'].'&bill_fee='.$response['bill_fee'].'&key='.$key));
        if ($response['sign'] == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'xzfPay');
        } else {
            $this->logdump('xzfPay', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}