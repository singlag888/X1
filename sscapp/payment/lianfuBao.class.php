<?php
namespace sscapp\payment;
class lianfuBao extends payModel{
    /**
     * 联付宝
     * @param array $data
     * @return string $str
     * @author Jacky
     */
    public function orderNumber($asd = 0)
    {
        $orderno = "1049" . date("YmdHis") . rand(10, 99);
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['lianfuBao_api'];
        if ($card_info['netway'] == 'QQ_WAP') {
              $service_type = "qqwallet";
        } elseif ($card_info['netway'] == 'QQ') {
              $service_type = "qqrcode";
        } elseif ($card_info['netway'] == 'KJ') {
            $service_type = "quickbank";
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = "alipaywap";
        } elseif ($card_info['netway'] == 'WY') {
            $service_type = "bank";
            switch ($data['third_party_bank_id']) {
                // 工商银行
                case '1':
                    $banktype = 'ICBC';
                    break;
                // 农业银行
                case '2':
                    $banktype = 'ABC';
                    break;
                // 建设银行
                case '3':
                    $banktype = 'CCB';
                    break;
                // 招商银行
                case '4':
                    $banktype = 'CMB';
                    break;
                // 交通银行
                case '5':
                    $banktype = 'BOCOM';
                    break;
                // 中信银行
                case '6':
                    $banktype = 'CNCB';
                    break;
                case '7':
                    break;
                // 中国光大银行
                case '8':
                    $banktype = 'CEB';
                    break;
                // 民生银行
                case '9':
                    $banktype = 'CMBC';
                    break;
                // 上海浦东发展银行
                case '10':
                    $banktype = 'SPDB';
                    break;
                // 兴业银行
                case '11':
                    $banktype = 'CIB';
                    break;
                // 广发银行
                case '12':
                    $banktype = 'GDB';
                    break;
                // 平安银行
                case '13':
                    $banktype = 'PAB';
                    break;
                // 华夏银行
                case '15':
                    $banktype = 'HXB';
                    break;
                // 东莞银行
                case '16':
                    break;
                // 渤海银行
                case '17':
                    $banktype = 'CBHB';
                    break;
                // 浙商银行
                case '19':
                    break;
                // 北京银行
                case '20':
                    $banktype = 'BCCB';
                    break;
                // 广州银行
                case '21':
                    $banktype = '';
                    break;
                // 中国银行
                case '22':
                    $banktype = 'BOCSH';
                    break;
                // 邮政储蓄
                case '23':
                    $banktype = 'PSBC';
                    break;
            }

        } elseif ($card_info['netway'] == 'WX_WAP') {
            $service_type = "gzhpay";
        } elseif ($card_info['netway'] == 'WX') {
            $service_type = "weixin";
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = "tenpay";
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = "alipay";
        } else {
            $this->_error = '暂时不支持该支付!';
        }
        $postData['version'] = '1.0';
        $postData['customerid'] = $partner;
        $postData['sdorderno'] = $data['ordernumber'];//
        $postData['total_fee'] = number_format($data['money'],2);
        $postData['paytype'] = $service_type;
        if ($card_info['netway'] == 'WY') {
            $postData['bankcode'] = @$banktype;
        }
        $postData['notifyurl'] = $callbackurl;//
        $postData['returnurl'] = $sameBackUrl;
        $postData['get_code'] = '0';
        $sign=md5('version='.$postData['version'].'&customerid='.$postData['customerid'].'&total_fee='.$postData['total_fee'].'&sdorderno='.$postData['sdorderno'].'&notifyurl='.$postData['notifyurl'].'&returnurl='.$postData['returnurl'].'&'.$key);
        $postData['sign'] = $sign;
                if ($card_info['netway'] == 'WY') {
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
<form name='dingyiPay' id="diy" action="{$apiUrl}" method='get'>
    <input type="hidden" name="version" value="{$postData['version']}"/>
    <input type="hidden" name="customerid"		  value="{$postData['customerid']}" />
    <input type="hidden" name="sdorderno"     value="{$postData['sdorderno']}"/>
    <input type="hidden" name="total_fee"  value="{$postData['total_fee']}"/>
    <input type="hidden" name="paytype" value="{$postData['paytype']}"/>
    <input type="hidden" name="bankcode" value="{$postData['bankcode']}"/>
    <input type="hidden" name="notifyurl" value="{$postData['notifyurl']}"/>
    <input type="hidden" name="returnurl"     value="{$postData['returnurl']}"/>
    <input type="hidden" name="sign" value="{$postData['sign']}"/>  
    <input type="hidden" name="get_code" value="{$postData['get_code']}"/> 
</form>
</body>
</html>

HTML;
                    return ['code' => 'html', 'html' => $res];
                }else{
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
<form name="diy" id="diy" action="$apiUrl"  method="get">
 <input type="hidden" name="version" value="{$postData['version']}"/>
    <input type="hidden" name="customerid"		  value="{$postData['customerid']}" />
    <input type="hidden" name="sdorderno"     value="{$postData['sdorderno']}"/>
    <input type="hidden" name="total_fee"  value="{$postData['total_fee']}"/>
    <input type="hidden" name="paytype" value="{$postData['paytype']}"/>
    <input type="hidden" name="notifyurl" value="{$postData['notifyurl']}"/>
    <input type="hidden" name="returnurl"     value="{$postData['returnurl']}"/>
    <input type="hidden" name="sign" value="{$postData['sign']}"/>  
    <input type="hidden" name="get_code" value="{$postData['get_code']}"/> 
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;

                    return ['code' => 'html', 'html' => $str];
                }

    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('lianfuBao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $status = isset($response['status']) ? $response['status'] : '';//商户ID
        if (empty($status)) {
            $this->logdump('lianfuBao', '回掉(status为空!)');
            exit;
        }
        $total_fee = isset($response['total_fee']) ? $response['total_fee'] : 0;//订单号
        if ($total_fee === 0) {
            $this->logdump('lianfuBao', '回掉(total_fee为空!)');
            exit;
        }

        $customerid = isset($response['customerid']) ? $response['customerid'] : '';//订单号
        if (empty($customerid)) {
            $this->logdump('lianfuBao', '回掉(customerid为空!)');
            exit;
        }

        $sdpayno = isset($response['sdpayno']) ? $response['sdpayno'] : '';//订单金额 单位分（人民币）
        if (empty($sdpayno)) {
            $this->logdump('lianfuBao', '回掉-sdpayno为空');
            exit;
        }

        $sdorderno = isset($response['sdorderno']) ? $response['sdorderno'] : '';//1:支付成功，非1为支付失败
        if (empty($sdorderno)) {
            $this->logdump('lianfuBao', '回掉(sdorderno为空)');
            exit;
        }

        $paytype = isset($response['paytype']) ? $response['paytype'] : '';//1:支付成功，非1为支付失败
        if (empty($paytype)) {
            $this->logdump('lianfuBao', '回掉(paytype为空)');
            exit;
        }

        $sign = isset($response['sign']) ? trim($response['sign']) : '';//1:支付成功，非1为支付失败
        if (empty($sign)) {
            $this->logdump('lianfuBao', '回掉(sign为空)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$sdorderno}'");

        if (empty($deposits)) {
            $this->logdump('lianfuBao', '没有找到订单:' . $sdorderno . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('lianfuBao', '没有找到订单:' . $sdorderno . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('lianfuBao', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('lianfuBao', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $mysign=md5('customerid='.$customerid.'&status='.$status.'&sdpayno='.$sdpayno.'&sdorderno='.$sdorderno.'&total_fee='.$total_fee.'&paytype='.$paytype.'&'.$key);
        if ($sign==$mysign && $status=='1')//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $sdorderno, 'lianfuBao');
        } else {
            $this->logdump('lianfuBao', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}