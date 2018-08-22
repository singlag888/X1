<?php
/**
 * YZ支付
 * @author L
 * @time 2017年12月15日 16:38:44
 */
namespace sscapp\payment;
class yzWap extends payModel
{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_sameBackUrl = '';//
    private $_requestUrl = 'http://jh.yizhibank.com/groupapi/createOrder';//第三方请求地址

    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        $shop_order_number = '1026' . $this->randomkeys(6) . time() . $this->randomkeys(8);
        $shop_order_number = substr($shop_order_number,0,18);
        return $shop_order_number;
    }
    /**
     * 生成随机
     * @param string $length 长度
     * @return string $key 返回的值
     * @author L
     */
    public function randomkeys($length)
    {
        $pattern = '1234567890';
        $key = '';
        for ($i = 0; $i < $length; ++$i) {
            $key .= $pattern{mt_rand(0, 9)};    //生成php随机数
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
     * 支付
     * @param array $data 提交过来的数据
     * @return qrcode| url | html   返回数据
     * @author L
     * @time 2017年12月15日 16:38:14
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
        if ($card_info['netway'] == 'WX') {
            $service_type = "2";
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = '1';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = '1';
        } else {
            $this->logdump('test','支付方式不存在');
            $this->_error = '支付方式不存在，请选择其他方式支付';
            return false;
        }
        $postData['mch_no'] = $card_info['mer_no'];
        $postData['merchantOutOrderNo'] = $data['ordernumber'];
        $this->logdump('yzWap',$data['ordernumber']);
        $postData['method'] = "2";
        $postData['noncestr'] = mt_rand(1000, 9999);
        $postData['notifyUrl'] = $this->_callbackurl;
        $postData['orderMoney'] =$data['money'];
        $postData['orderTime'] = date('YmdHis');
        $postData['type'] = $service_type;
       // $postData['sign'] = md5($this->azSign($postData).'key='.$this->_key);
        $signstr = 'mch_no='.$postData['mch_no'].'&merchantOutOrderNo='.$postData['merchantOutOrderNo'].'&noncestr='.$postData['noncestr'].'&notifyUrl='.$postData['notifyUrl'].'&orderMoney='.$postData['orderMoney'].'&orderTime='.$postData['orderTime'];
        $signstr.= '&key='.$this->_key;
        $postData['sign'] = md5($signstr);
        $postData['url'] = $this->_requestUrl;
        if (strstr($data['card_info']['netway'], "WAP") > -1) {
            $info = array();
            $str = $this->curlPost($this->_requestUrl,$postData,$info);
            $res = json_decode($str,true);
            $url = isset($res['url']) ? $res['url'] : '';
            if (empty($url)) {
                $this->_error = '此充值通道异常,请选择其它充值通道充值!';
                $this->logdump('yzWap', $str);
                return false;
            }
            $this->logdump('yzHtml',$str);
            return ['code' => 'url', 'url' => isset($url) ? $url : ''];
        }else{
            $res = $this->curlPost($this->_requestUrl,$postData);
            $res_json = json_decode($res,true);
            if(empty($res)){
                $this->_error = '此充值通道异常,请选择其它充值通道充值';
                $this->logdump('yzWap','返回为空，请注意检查支付方式');
                $this->logdump('yzWap',$postData);
                return false;
            }
            $url = isset($res_json['url']) ? isset($res_json['url']) : '' ;    //dd($url);
            if (empty($url)){
                $this->_error = '此充值通道异常,请选择其它充值通道充值';
                $this->logdump('yzWap','没有抓到二维码的链接地址');
                return false;
            }
            return ['code' => 'qrcode', 'url' => isset($res_json['url']) ? $res_json['url'] : ''];

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
            $this->logdump('yzWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $money = json_decode($response['msg'],true);
        $response['money'] = $money['payMoney'];
        $ordernumber = isset($response['merchantOutOrderNo']) ? $response['merchantOutOrderNo'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('yzWap', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['payResult']) ? $response['payResult'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('yzWap', '回掉(订单状态为空)');
            exit;
        }
        $paymoney = isset($response['money']) ? $response['money'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('yzWap', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['orderNo']) ? $response['orderNo'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('yzWap', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('yzWap', '回掉(签名不正确!)');
            exit;
        }
        $transTime = isset($response['noncestr']) ? $response['noncestr'] : 0;
        if (empty($transTime)) {
            $this->logdump('yzWap', '回掉(发送穿透参数不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('yzWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('yzWap', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('yzWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('yzWap', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        //$sign = md5($this->azSign($response).'key='.$key);
        $signstr = 'merchantOutOrderNo='.$response['merchantOutOrderNo'].'&merid='.$response['merid'].'&msg='.$response['msg'].'&noncestr='.$response['noncestr'].'&orderNo='.$response['orderNo'].'&payResult='.$response['payResult'];
        $signstr .= '&key=' . $key;
        $localSign = md5($signstr);
        if ($response['sign'] == $localSign)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'yzWap');
        } else {
            $this->logdump('yzWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}