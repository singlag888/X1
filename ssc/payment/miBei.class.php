<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19
 * Time: 15:54
 */
namespace ssc\payment;
if (!defined('IN_LIGHT')) {
    die('KCAH');
}
class miBei extends newPayModel
{
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
        $result = file_get_contents($url, false, $context);
        return $result;
    }
    /**
     * 生成随机
     * @param string $length 长度
     * @return string $key 返回的值
     * @author L
     */
    public function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = '';
        for ($i = 0; $i < $length; ++$i) {
            $key .= $pattern{mt_rand(0, 62)};    //生成php随机数
        }
        return $key;
    }
    /**
     * 数组转为xml
     * @param $array
     * @return string
     */
    public function toXml($array)
    {
        $xml = '<xml>';
        forEach ($array as $k => $v) {
            $xml .= '<' . $k . '>' . $v . '</' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }
    public function run($data=[])
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
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
        }
        $apiUrl = $GLOBALS['cfg']['pay_url']['mb_api'];
        $partner = $card_info['mer_no'];
        $key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $shop_url = $card_info['shop_url'];
        if ($shop_url == "") {
            $callbackurl = $domain . 'newPay/backPay/' . end($className);
            $sameBackUrl = $domain . 'pay/hrefback.php';
        } else if (strpos($shop_url, '?c=pay') === 0) {
            $this->logdump('mbUrl','第三方支付域名错误');
            shopMSG('支付域名错误');
        } else if (strpos($shop_url, '?c=pay') > 0) {
            $this->logdump('mbUrl','第三方支付域名错误');
            shopMSG('支付域名错误');
        } else {
            $callbackurl = $shop_url .  'pay/' . end($className) . 'Back.php';
            $sameBackUrl = $shop_url . 'hrefback.php';
        }
        $service_type = '';
        $bankId = '';
        if ($card_info['netway'] == 'WX') {
            $service_type = "trade.weixin.native";
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = 'trade.qqpay.native';
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = 'trade.alipay.native';
        } elseif ($card_info['netway'] == 'JD') {
            $service_type = 'trade.jdcom.native';
        } elseif ($card_info['netway'] == 'WXWAP') {
            $service_type = 'trade.weixin.h5pay';
        } elseif ($card_info['netway'] == 'QQWAP') {
            $service_type = 'trade.qqpay.wappay';
        } else {
            shopMSG('暂时支持该支付');
        }
        $postData['mch_id'] = $partner;//商户号
        $postData['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];//下单IP
        $postData['total_fee'] = $data['money']*100;//金额
        $postData['nonce_str'] = $this->randomkeys(32);//商品名称
        $postData['body'] = 'thisvip';//商品名称
        if(strstr($data['card_info']['netway'], "WAP") > '-1'){
            $postData['return_url'] = $sameBackUrl;//商品名称
        }
        $postData['notify_url'] = $callbackurl;//异步地址
        $postData['out_trade_no'] = $data['ordernumber'];//订单号
        $postData['trade_type'] = $service_type;//支付类型
        $postData['sign'] = strtoupper(md5($this->azSign($postData).'key='.$key));
        $info = array();
        $postDataXml = $this->toXml($postData);
        $res = $this->curlPost($apiUrl,$postDataXml,$info);
        $code = $this->xmlTonewArray($res);
        if ($code['result_code'] = 'SUCCESS' && !empty($code['code_url'])){
            $requestURI = $data['requestURI'];
            $bank_id = $data['bank_id'];
            $bank_id = authcode($bank_id, 'DECODE', 'a6sbe!x4^5d_ghd');
            $array = array(
                'order_number' => $data['ordernumber'],
                'user_id' => $data['user_id'],
                'username' => $data['username'],
                'amount' => $data['money'],
                'pay_time' => date('Y-m-d H:i:s'),
                'source' => $_SERVER['HTTP_HOST'],
                'requestURI' => $requestURI,
                'card_id' => $data['card_id'],
                'bank_id' => $bank_id,
            );
            if (\pay::addItem($array)) {
                if (strstr($data['card_info']['netway'], "WAP") > '-1') {
                    header("location:" . $code['prepay_url']);
                    die();
                } else {
                    header("location:" . '../pay/qrcode.php?code=' . $code['code_url'] . '&netway=' . $data['card_info']['netway'] . '&amount=' . $data['money'] ."&cdn=" .$this->cdn() );
                }
            }
        } else {
            echo '系统繁忙，请稍后访问';
            $this->logdump('mbPay.log', $postData);
            $this->logdump('mbPay.log', $res);
        }
    }
    public function callback($response = [], $is_raw = 0){
        if (empty($response)) {
            $this->logdump('mbPay', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        $response = $this->xmlTonewArray($response);
        $partner = isset($response['mch_id']) ? $response['mch_id'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('mbPay', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['out_trade_no']) ? $response['out_trade_no'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('mbPay', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['result_code']) ? $response['result_code'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('mbPay', '回掉(订单状态为空)');
            exit;
        }
        $paymoney = isset($response['total_fee']) ? $response['total_fee'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('mbPay', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['third_trans_id']) ? $response['third_trans_id'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('mbPay', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('mbPay', '回掉(签名不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('mbPay', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('mbPay', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('mbPay', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('mbPay', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $sign = strtoupper(md5($this->azSign($response).'key='.$key));
        if ($response['sign'] == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'mbPay');
        } else {
            $this->logdump('mbPay', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}