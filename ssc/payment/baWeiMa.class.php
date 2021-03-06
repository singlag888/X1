<?php
/**
 * 八维码支付
 * @author L
 * Date: 2018/1/4
 * Time: 15:23
 */
namespace ssc\payment;
class baWeiMa extends newPayModel
{
    public function azSign($data){
        $str = '';
        foreach ($data as $key => $value){
            if ($value !== 'sign'){
                $str .= $value . "#";
            }
        }
        return $str;
    }
    public function send_post($url, $post_data)//
    {
        $postdata = $post_data;
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
    public function bcurlPost($url, $data, $errorLogName = 'curlError', $is_json = 0, $is_xml = 0,$shop_url = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if(!empty($shop_url)) {
            curl_setopt($ch, CURLOPT_REFERER, $shop_url); //伪造来路页面
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300); //附加
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); //附加


        if ($is_json == 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data))
            );
        }
        if ($is_xml == 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/xml;charset=utf-8',
                'Content-Length:' . strlen($data)
            ));
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $return = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->logdump($errorLogName.'CURLErrno' ,$url.':'.curl_error($ch).curl_error($ch)); //捕抓异常
        }
        curl_close($ch);
        return $return;
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
        if ($card_info['netway'] == 'WX') {
            $service_type = "WX_SCANCODE";
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $service_type = 'WX_H5';
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = 'Alipay_SCANCODE';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = 'Alipay_H5';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $apiUrl = $GLOBALS['cfg']['pay_url']['bwm_qqwap_api'];
            $service_type = 'QQ_H5';
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = 'QQPAY_SCANCODE';
            $apiUrl = $GLOBALS['cfg']['pay_url']['bwm_api'];
        } else {
            shopMSG('暂时支持该支付');
        }
        $postData['trxType'] = $service_type;
        $postData['merchantNo'] = $partner;
        $postData['orderNum'] = $data['ordernumber'];
        $postData['amount'] = $data['money'];
        $postData['goodsName'] = 'Recharge';
        $postData['callbackUrl'] = $sameBackUrl;
        $postData['serverCallbackUrl'] = $callbackurl;
        $postData['orderIp'] = $_SERVER["REMOTE_ADDR"];
        $postData['encrypt'] = 'T0';
        $sign = '#'.$this->azSign($postData).$key;
        $postData['sign'] = md5($sign);
        $res = $this->bcurlPost($apiUrl,http_build_query($postData));
        $json = json_decode($res,true);
        if ($json['resultCode'] = '1000' && !empty($json['qrCode'])){
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
                    header("location:" . $json['order']['pay_link']);
                    die();
                } else {
                    header("location:" . '../pay/qrcode.php?code=' . $json['qrCode'] . '&netway=' . $data['card_info']['netway'] . '&amount=' . $data['money'] ."&cdn=" .$this->cdn() );
                }
            }
        } else {
            echo '系统繁忙，请稍后访问';
            $this->logdump('baWeiMa', $postData);
            $this->logdump('baWeiMa', $res);
        }
    }
    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('baWeiMa', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $ordernumber = isset($response['r2_orderNumber']) ? $response['r2_orderNumber'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('baWeiMa', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['r8_orderStatus']) ? $response['r8_orderStatus'] : '';//1:支付成功，非1为支付失败
        if ($orderstatus !== 'SUCCESS') {
            $this->logdump('baWeiMa', '回掉(订单状态为空)');
            exit;
        }
        $paymoney = isset($response['r3_amount']) ? $response['r3_amount'] : 0;//订单金额 单位分（人民币）
        if ($paymoney === 0) {
            $this->logdump('baWeiMa', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = !empty($response['r9_serialNumber']) ? $response['r9_serialNumber'] : $response['r9_withdrawStatus'];//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('baWeiMa', '回掉(商家订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('baWeiMa', '回掉(签名不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('baWeiMa', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('baWeiMa', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('baWeiMa', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('baWeiMa', '配置有误,请联系客服!');
            exit;
        }
        $data['trxType'] = $response['trxType'];
        $data['retCode'] = $response['retCode'];
        $data['r1_merchantNo'] = $response['r1_merchantNo'];
        $data['r2_orderNumber'] = $response['r2_orderNumber'];
        $data['r3_amount'] = $response['r3_amount'];
        $data['r4_bankId'] = $response['r4_bankId'];
        $data['r5_business'] = $response['r5_business'];
        if(!empty($response['r6_createDate'])){
            $data['r6_createDate'] = $response['r6_createDate'];
            $key = $card_info['card_pass'];
        }else{
            $data['r6_timestamp'] =  $response['r6_timestamp'] ;
            $key = $card_info['mer_key'];
        }
        $data['r7_completeDate'] = $response['r7_completeDate'];
        $data['r8_orderStatus'] = $response['r8_orderStatus'];
        $data['r9_serialNumber'] = $sysnumber;
        if ($response['trxType']==='QQPAY_SCANCODE'){
            $data['r10_t0PayResult'] = $response['r10_t0PayResult'];
        }else{

        }
        $azsign = '#'.$this->azSign($data).$key;
        $md5Sign = md5($azsign);
        if ($response['sign'] == $md5Sign)//签名正确
        {   $sysnumber = !empty($response['r9_serialNumber']) ? $response['r9_serialNumber'] : $response['r2_orderNumber'];
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'baWeiMa');
        } else {
            $this->logdump('baWeiMa', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}