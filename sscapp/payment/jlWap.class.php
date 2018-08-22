<?php
/**
 * 嘉联支付
 * @time 2017年12月6日 10:30:06
 * @author L
 */
namespace sscapp\payment;
class jlWap extends payModel{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_sameBackUrl = '';//
    private $_requestUrl = 'http://m.jialianjinfu.com/Pay_Index.html';//第三方请求地址

    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        return '1012' . $this->randomkeys(6) . time() . $this->randomkeys(8);
    }

    /**
     * 加密方式
     * @param $data
     * @return string
     * @author L
     */
    public function jlSign($data){
        ksort($data);
        $str = '';
        foreach ($data as $key => $value){
            if(!empty($value) && $key != 'sign'){
                $str .=  $key . "=" .$value . "&";
            }
        }
        return $str;
    }

    /**
     * post提交
     * @param $url
     * @param $post_data
     * @return bool|string
     * @author L
     */
    public function send_post($url, $post_data)
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
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $protocol = $this->getProtocol($area_flag);
        $this->_callbackurl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info['netway'] == 'WX_WAP') {
            $service_type = "901";
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $service_type = '905';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = '904';
        }else if($card_info['netway'] == "ZFB"){
            $service_type = "903";
        } else if ($card_info['netway'] == "QQ") {
            $service_type = "908";
        } else if ($card_info['netway'] == "JD") {
            $service_type = "910";
        } else if ($card_info['netway'] == "WX") {
            $service_type = "902";
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }

        $orderCreateTime = date('Y-m-d H:i:s', time());
        $postData['pay_memberid'] = $card_info['mer_no'];//1
        $postData['pay_amount'] = $data['money'];//3
        $postData['pay_orderid'] = $data['ordernumber'];//2
        $postData['pay_bankcode'] = $service_type;//5
        $postData['pay_applydate'] = $orderCreateTime;//4
        $postData['pay_notifyurl'] = $this->_callbackurl;//6
        $postData['pay_callbackurl'] = $this->_sameBackUrl;//7
        $postData['pay_md5sign'] = strtoupper(md5($this->jlSign($postData).'key='.$this->_key));
        $info = array();
        $res = $this->send_post($this->_requestUrl, $postData,$info);
        if (empty($res)) {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }
        $this->logdump('jlWap', $res);
        if (!empty(json_decode($res))) {
            $arrBack = json_decode($res,true);
            $url = isset($arrBack['codeUrl']) ? $arrBack['codeUrl'] : '';
            if (!$url) {
                $this->_error = '此充值通道异常,请选择其它充值通道充值!';
                $this->logdump('jlWap', $res);
                return false;
            }
            return ['code' => 'qrcode', 'url' => isset($url) ? $url : ''];
        }else{
//            preg_match('/<script[^>]*>([\s\S]*)<\/script>/', $res, $body);
//            preg_match('/"(.*?)"/', end($body), $arr);
//            $url = isset($arr[1]) ? $arr[1] : '';
            $url = isset($res) ? $res : '';
            if (!strpos($url,'href')) {
                $this->_error = '此充值通道异常,请选择其它充值通道充值!';
                $this->logdump('jlWap', $res);
                return false;
            }
            return ['code' => 'html', 'html' => isset($url) ? $url : ''];
        }
    }

    public function callback($response = [], $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('jlWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $ordernumber = isset($response['orderid']) ? $response['orderid'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('jlWap', '回掉(订单号为空!)');
            exit;
        }

        $paymoney = isset($response['amount']) ? $response['amount'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('jlWap', '回掉（订单金额为空）');
            exit;
        }
        $sysnumber = isset($response['transaction_id']) ? $response['transaction_id'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('jlWap', '回掉(订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('jlWap', '回掉(签名不正确!)');
            exit;
        }
        $status = isset($response['memberid']) ? $response['memberid'] : 0;
        if (empty($status)) {
            $this->logdump('jlWap', '回掉(ID不正确!)');
            exit;
        }
        $msg = isset($response['datetime']) ? $response['datetime'] : 0;
        if (empty($msg)) {
            $this->logdump('jlWap', '回掉(时间不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('jlWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('jlWap', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('jlWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('jlWap', '配置有误,请联系客服!');
            exit;
        }
        $res = $this->jlSign($response);
        $sign = strtoupper(md5($res.'key='.$this->_key));
        if ($sign == $response['sign'])//签名正确
        {
            echo '成功了';
            $this->bak('gs4fj@5f!sda*dfuf', 'OK', $deposits, $sysnumber, 'jlWap');
        } else {
            echo '签名错了';
            $this->logdump('jlWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}