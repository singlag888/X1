<?php
namespace ssc\payment;
if (!defined('IN_LIGHT')) {
    die('KCAH');
}
class anXin extends newPayModel{
    /**
     * 安心支付微信,QQ和QQWAP
     * @param array $data
     * @return string $str
     * @author Jacky
     */
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
            $apiUrl = $GLOBALS['cfg']['pay_url']['anxinwx_api'];
        } elseif ($card_info['netway'] == 'QQ') {
            $apiUrl = $GLOBALS['cfg']['pay_url']['anxinqq_api'];
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $apiUrl = $GLOBALS['cfg']['pay_url']['anxinwap_api'];
        } else {
            shopMSG('暂时支持该支付');
        }
        $postData['p1_MerchantNo'] = $partner;//
        $postData['p3_Amount'] = $data['money'];//
        $postData['p4_Cur'] = '1';//
        $postData['p6_NotifyUrl'] = $callbackurl;//
        $postData['p2_OrderNo'] = $data['ordernumber'];//
        $postData['p5_ProductName'] = 'lucky';//
        $postData['sign'] = md5($postData['p1_MerchantNo']."".$postData['p2_OrderNo']."".$postData['p3_Amount']."".$postData['p4_Cur']."".$postData['p5_ProductName']."".$postData['p6_NotifyUrl'].$key);//

        $res = $this->curlPost($apiUrl, $postData);
        $code = json_decode($res,true);
        if (isset($code['ra_Code']) && !empty($code['ra_Code'])){
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
                    header("location:" . $code['ra_Code']);
                    die();
                } else {
                    header("location:" . '../pay/qrcode.php?code=' . $code['ra_Code'] . '&netway=' . $data['card_info']['netway'] . '&amount=' . $data['money'] ."&cdn=" .$this->cdn() );
                }
            }
        } else {
                echo $code['rc_CodeMsg'];
                log2file('anXinPay.log', $postData);
                log2file('anXinPay.log', $res);
        }


    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('anXin', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $r1_MerchantNo = isset($response['r1_MerchantNo']) ? $response['r1_MerchantNo'] : '';//商户ID
        if (empty($r1_MerchantNo)) {
            $this->logdump('anXin', '回掉(r1_MerchantNo为空!)');
            exit;
        }
        $r3_Amount = isset($response['r3_Amount']) ? $response['r3_Amount'] : 0;//订单号
        if ($r3_Amount === 0) {
            $this->logdump('anXin', '回掉(r3_Amount为空!)');
            exit;
        }

        $r2_OrderNo = isset($response['r2_OrderNo']) ? $response['r2_OrderNo'] : '';//订单号
        if (empty($r2_OrderNo)) {
            $this->logdump('anXin', '回掉(订单号为空!)');
            exit;
        }

        $r4_Cur = isset($response['r4_Cur']) ? $response['r4_Cur'] : '';//订单金额 单位分（人民币）
        if (empty($r4_Cur)) {
            $this->logdump('anXin', '回掉-r4_Cur为空');
            exit;
        }

        $r5_Status = isset($response['r5_Status']) ? $response['r5_Status'] : '';//1:支付成功，非1为支付失败
        if (empty($r5_Status)) {
            $this->logdump('anXin', '回掉(r5_Status为空)');
            exit;
        }


        $ra_PayTime = isset($response['ra_PayTime']) ? $response['ra_PayTime'] : '';//月宝订单号
        if (empty($ra_PayTime)) {
            $this->logdump('anXin', '回掉(ra_PayTime为空!)');
            exit;
        }
        $rb_DealTime = isset($response['rb_DealTime']) ? $response['rb_DealTime'] : '';
        if (empty($rb_DealTime)) {
            $this->logdump('anXin', '回掉(rb_DealTime为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('anXin', '回掉(sign为空!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$r2_OrderNo}'");

        if (empty($deposits)) {
            $this->logdump('anXin', '没有找到订单:' . $r2_OrderNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('anXin', '没有找到订单:' . $r2_OrderNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('anXin', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('anXin', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $callsign = md5($r1_MerchantNo."".$r2_OrderNo."".$r3_Amount."".$r4_Cur."".$r5_Status."".$ra_PayTime."".$rb_DealTime.$key);
        if ($sign == $callsign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $r2_OrderNo, 'anxin');
        } else {
            $this->logdump('anxin', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}