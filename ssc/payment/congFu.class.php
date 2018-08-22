<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/18
 * Time: 14:16
 */
namespace ssc\payment;
if (!defined('IN_LIGHT')) {
    die('KCAH');
}
class congFu extends newPayModel{
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['cf_api'];
        //$wyUrl = $GLOBALS['cfg']['pay_url']['cf_wy_api'];
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
            $this->logdump('xdShopUrl','第三方支付域名错误');
            shopMSG('支付域名错误');
        } else if (strpos($shop_url, '?c=pay') > 0) {
            $this->logdump('xdShopUrl','第三方支付域名错误');
            shopMSG('支付域名错误');
        } else {
            $callbackurl = $shop_url .  'pay/' . end($className) . 'Back.php';
            $sameBackUrl = $shop_url . 'hrefback.php';
        }
        $service_type = '';
        $bankId = '';
        if ($card_info['netway'] == 'WX') {
            $service_type = "10000103";
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = '70000103';
        } elseif ($card_info['netway'] == 'WY') {
            $service_type = '60000101';
            switch ($data['third_party_bank_id']){
                case '1':
                    $bankId = 'ICBC';
                    break;
                // 农业银行1
                case '2':
                    $bankId = 'ABC';
                    break;
                // 建设银行1
                case '3':
                    $bankId = 'CCB';
                    break;
                // 招商银行1
//                case '4':
//                    $bankId = '308';
//                    break;
//                // 交通银行1
//                case '5':
//                    $bankId = '301';
//                    break;
                // 中信银行1
                case '6':
                    $bankId = 'ECITIC';
                    break;
                // 邮政储蓄1
                case '7':
                    $bankId = 'POST';
                    break;
                // 中国光大银行1
                case '8':
                    $bankId = 'CEB';
                    break;
                // 兴业银行1
                case '11':
                    $bankId = 'CIB';
                    break;
                // 广发银行1
                case '12':
                    $bankId = 'CGB';
                    break;
                // 中国银行1
                case '22':
                    $bankId = 'BOC';
                    break;
            }
            $serverTime = date('YmdHis',time());
            $postData['orderTime'] = $serverTime;//支付时间
            $postData['payKey'] = $partner;//商户号
            $postData['bankCode'] = $bankId;//银行卡
            $postData['bankAccountType'] = 'PRIVATE_DEBIT_ACCOUNT';//卡类型
            $postData['orderIp'] = $_SERVER['REMOTE_ADDR'];//下单IP
            $postData['orderPrice'] = $data['money'];//金额
            $postData['productName'] = 'thisvip';//商品名称
            $postData['notifyUrl'] = $callbackurl;//异步地址
            $postData['returnUrl'] = $sameBackUrl;//同步地址
            $postData['outTradeNo'] = $data['ordernumber'];//订单号
            $postData['productType'] = $service_type;//支付类型
            $postData['sign'] = strtoupper(md5($this->azSign($postData).'paySecret='.$key));
            $res = <<<HTML
            <!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>跳转中</title>
</head>
<body onLoad="document.dingyiPay.submit();">
<form name='dingyiPay' id="diy" action="{$wyUrl}" method='post'>
    <input type="hidden" name="orderTime" value="{$postData['orderTime']}"/>
    <input type="hidden" name="payKey"		  value="{$postData['payKey']}" />
    <input type="hidden" name="bankCode" value="{$postData['bankCode']}" />
    <input type="hidden" name="bankAccountType"     value="{$postData['bankAccountType']}"/>
    <input type="hidden" name="orderIp"  value="{$postData['orderIp']}"/>
    <input type="hidden" name="orderPrice" value="{$postData['orderPrice']}"/>
    <input type="hidden" name="productName" value="{$postData['productName']}"/>
    <input type="hidden" name="notifyUrl" value="{$postData['notifyUrl']}"/>
    <input type="hidden" name="returnUrl" value="{$postData['returnUrl']}"/>
    <input type="hidden" name="outTradeNo" value="{$postData['outTradeNo']}"/>
    <input type="hidden" name="productType" value="{$postData['productType']}"/>
    <input type="hidden" name="sign" value="{$postData['sign']}"/>
</form>
</body>
</html>

HTML;
            echo $res;die;
        } else {
            shopMSG('暂时支持该支付');
        }
        $serverTime = date('YmdHis',time());
        $postData['orderTime'] = $serverTime;//支付时间
        $postData['payKey'] = $partner;//商户号
        $postData['orderIp'] = $_SERVER['REMOTE_ADDR'];//下单IP
        $postData['orderPrice'] = $data['money'];//金额
        $postData['productName'] = 'thisvip';//商品名称
        $postData['notifyUrl'] = $callbackurl;//异步地址
        $postData['returnUrl'] = $sameBackUrl;//同步地址
        $postData['outTradeNo'] = $data['ordernumber'];//订单号
        $postData['productType'] = $service_type;//支付类型
        $postData['sign'] = strtoupper(md5($this->azSign($postData).'paySecret='.$key));
        $info = array();
        $res = $this->send_post($apiUrl,$postData,$info);
        $code = json_decode($res,true);
        if ($code['resultCode'] = '0000' && !empty($code['payMessage'])){
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
                    header("location:" . $code['QRCodeUrl']);
                    die();
                } else {
                    header("location:" . '../pay/qrcode.php?code=' . $code['payMessage'] . '&netway=' . $data['card_info']['netway'] . '&amount=' . $data['money'] ."&cdn=" .$this->cdn() );
                }
            }
        } else {
            echo '系统繁忙，请稍后访问';
            $this->logdump('cfPay.log', $postData);
            $this->logdump('cfPay.log', $res);
        }
    }
    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('cfPay', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $partner = isset($response['payKey']) ? $response['payKey'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('cfPay', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['outTradeNo']) ? $response['outTradeNo'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('cfPay', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['tradeStatus']) ? $response['tradeStatus'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('cfPay', '回掉(订单状态为空)');
            exit;
        }
        $paymoney = isset($response['orderPrice']) ? $response['orderPrice'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('cfPay', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['trxNo']) ? $response['trxNo'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('cfPay', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('cfPay', '回掉(签名不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('cfPay', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('cfPay', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('cfPay', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('cfPay', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $sign = strtoupper(md5($this->azSign($response).'paySecret='.$key));
        if ($response['sign'] == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'cfPay');
        } else {
            $this->logdump('test', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}