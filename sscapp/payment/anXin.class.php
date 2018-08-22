<?php


namespace sscapp\payment;
class anXin extends payModel{
    /**
     * 安心支付微信,QQ和QQWAP
     * @param array $data
     * @return string $str
     * @author Jacky
     *
     *
     */

    public function orderNumber($asd = 0)
    {
        $orderno = "1033" . date("YmdHis") . rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
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
        $callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $sameBackUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
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
        if (strstr($card_info['netway'], "WAP") > -1) {
            $postData['p7_pageUrl'] = $sameBackUrl;
            $postData['bizType'] = $service_type;
            $postData['sign'] = md5($postData['p1_MerchantNo'] . "" . $postData['p2_OrderNo'] . "" . $postData['p3_Amount'] . "" . $postData['p4_Cur'] . "" . $postData['p5_ProductName'] . "" . $postData['p6_NotifyUrl'] . "" . $postData['p7_pageUrl'] . "" . $postData['bizType'] . $key);//
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
<a>正在加载，请勿离开页面或者刷新页面</a>
<form name="diy" id="diy" action="$apiUrl"  method="post">
    <input type="hidden" name="p1_MerchantNo" value="{$postData['p1_MerchantNo']}">
    <input type="hidden" name="p2_OrderNo" value="{$postData['p2_OrderNo']}">
    <input type="hidden" name="p3_Amount" value="{$postData['p3_Amount']}">
    <input type="hidden" name="p4_Cur" value="{$postData['p4_Cur']}">
    <input type="hidden" name="p5_ProductName" value="{$postData['p5_ProductName']}">
    <input type="hidden" name="p6_NotifyUrl" value="{$postData['p6_NotifyUrl']}">
    <input type="hidden" name="p7_pageUrl" value="{$postData['p7_pageUrl']}">
    <input type="hidden" name="bizType" value="{$postData['bizType']}">
    <input type="hidden" name="sign" value="{$postData['sign']}">
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;
        }
        $postData['sign'] = md5($postData['p1_MerchantNo']."".$postData['p2_OrderNo']."".$postData['p3_Amount']."".$postData['p4_Cur']."".$postData['p5_ProductName']."".$postData['p6_NotifyUrl'].$key);//

        $res = $this->curlPost($apiUrl, $postData);
        $code = json_decode($res,true);
        if (strstr($card_info['netway'], "WAP") > -1) {
            return ['code' => 'html', 'html' => $str];
        } else {
            if (isset($code['ra_Code']) && !empty($code['ra_Code'])) {
                return ['code' => 'qrcode', 'url' => $code['ra_Code']];
            } else {
                $this->logdump('anXinPay.log', $postData);
                $this->logdump('anXinPay.log', $res);
                $this->_error = $code['rc_CodeMsg'];
                return false;
            }
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