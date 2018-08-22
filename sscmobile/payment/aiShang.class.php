<?php
namespace sscmobile\payment;
if (!defined('IN_LIGHT')) {
    die('KCAH');
}
class aiShang extends newPayModel{
    /**
     * 艾尚支付支持微信,QQ和网银
     * @param array $data
     * @return string $str
     * @author Jacky
     */
    public function run($data=[])
    {
        $card_info = isset($data['card_info']) ? $data['card_info'] : '';
        if (empty($card_info)) {
            echo '卡信息出错,请联系客服!';
            return false;
        }
        if (empty($card_info['mer_key'])) {
            echo '配置有误,请联系客服!';
            return false;
        }
        if (empty($card_info['mer_no'])) {
            echo '配置有误,请联系客服!';
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
            $this->logdump('xdShopUrl', '第三方支付域名错误');
            echo '支付域名错误';
        } else if (strpos($shop_url, '?c=pay') > 0) {
            $this->logdump('xdShopUrl', '第三方支付域名错误');
            echo '支付域名错误';
        } else {
            $callbackurl = $shop_url . 'pay/' . end($className) . 'Back.php';
            $sameBackUrl = $shop_url . 'hrefback.php';
        }
        $paytype = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['aishang_api'];
        if ($card_info['netway'] == 'WX') {
            $paytype = '10000';
            $postData['tradeRate'] = "1.3";
        } elseif ($card_info['netway'] == 'QQ') {
            $paytype = '20000';
            $postData['tradeRate'] = "1.2";
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $paytype = '30000';
            $postData['tradeRate'] = "1.7";
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $paytype = '70000';
            $postData['tradeRate'] = "3.5";
        } elseif ($card_info['netway'] == 'ZFB') {
            $paytype = '60000';
            $postData['tradeRate'] = "3";
        } elseif ($card_info['netway'] == 'WY') {
            $paytype = '40000';
            $postData['tradeRate'] = "4.3";
            /*$third_party_bank_id = $this->request->getPost('third_party_bank_id', 'intval');
            switch ($third_party_bank_id) {
                // 工商银行
                case '1':
                    $banktype = '1002';
                    break;
                // 农业银行
                case '2':
                    $banktype = '1005';
                    break;
                // 建设银行
                case '3':
                    $banktype = '1003';
                    break;
                // 招商银行
                case '4':
                    $banktype = '1001';
                    break;
                // 交通银行
                case '5':
                    $banktype = '1020';
                    break;
                // 中信银行
                case '6':
                    $banktype = '1021';
                    break;
                case '7':
                    break;
                // 中国光大银行
                case '8':
                    $banktype = '1022';
                    break;
                // 民生银行
                case '9':
                    $banktype = '1006';
                    break;
                // 上海浦东发展银行
                case '10':
                    $banktype = '1004';
                    break;
                // 兴业银行
                case '11':
                    $banktype = '1009';
                    break;
                // 广发银行
                case '12':
                    $banktype = '1027';
                    break;
                // 平安银行
                case '13':
                    $banktype = '1010';
                    break;
                // 华夏银行
                case '15':
                    $banktype = '1025';
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
                    $banktype = '1032';
                    break;
                // 广州银行
                case '21':
                    $banktype = '';
                    break;
                // 中国银行
                case '22':
                    $banktype = '1052';
                    break;
                // 邮政储蓄
                case '23':
                    $banktype = '1028';
                    break;
            }*/

        } else {
            echo '暂时不支持该支付!';
        }
        $postData['version'] = "1.0.0";//
        $postData['service'] = "pay";//
        $postData['platformNo'] = @explode("_",$partner)[1];//
        //$postData['channelNo'] = @explode("_",$partner)[2];
        $postData['merNo'] = explode("_",$partner)[0];//
        $postData['tranType'] = $paytype;//
        $postData['orderAmount'] = $data['money']*100;//
        $postData['subject'] = "lucky";//
        $postData['merOrderNo'] = $data['ordernumber'];//
        $postData['frontUrl'] = $sameBackUrl;//
        $postData['backUrl'] = $callbackurl;////
        $postData['drawFee'] = "1";//
        $postData['desc'] = "ceshi";//
        $postData['signature'] = $this->sign($postData, $key);//
        if ($card_info['netway'] != 'WY' && !(strstr($card_info['netway'], "WAP") > '-1')){
        $jsonstr = $this->curlPost($apiUrl, $postData);
        $arrnew = json_decode($jsonstr,true);
            if(!isset($arrnew['qrCode']) || $arrnew['qrCode'] == "") {
                if (isset($arrnew['respMsg']) && $arrnew['respMsg'] != "") {
                    echo $arrnew['respMsg'];
                    return false;
                } else {
                    echo "生成二维码失败!";
                    return false;
                }
            }

        }
        if (strstr($data['card_info']['netway'], "WAP") > '-1'){
            $jsonstr = $this->curlPost($apiUrl, $postData);
            $arrnew = json_decode($jsonstr,true);
            if(!isset($arrnew['payUrl']) || $arrnew['payUrl'] == "") {
                if (isset($arrnew['respMsg']) && $arrnew['respMsg'] != "") {
                    echo $arrnew['respMsg'];
                    return false;
                } else {
                    echo "生成二维码失败!";
                    return false;
                }
            }

        }
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
<a>正在加载，请勿离开页面或者刷新页面</a>
<form name='dingyiPay' id="diy" action="{$apiUrl}" method='post'>
    <input type="hidden" name="version" value="{$postData['version']}"/>
    <input type="hidden" name="platformNo"		  value="{$postData['platformNo']}" />
    <input type="hidden" name="merNo"     value="{$postData['merNo']}"/>
    <input type="hidden" name="tranType"  value="{$postData['tranType']}"/>
    <input type="hidden" name="orderAmount" value="{$postData['orderAmount']}"/>
    <input type="hidden" name="subject" value="{$postData['subject']}"/>
    <input type="hidden" name="merOrderNo" value="{$postData['merOrderNo']}"/>
    <input type="hidden" name="frontUrl"     value="{$postData['frontUrl']}"/>
    <input type="hidden" name="backUrl"  value="{$postData['backUrl']}"/>
    <input type="hidden" name="tradeRate" value="{$postData['tradeRate']}"/>
    <input type="hidden" name="drawFee" value="{$postData['drawFee']}"/>
    <input type="hidden" name="desc" value="{$postData['desc']}"/>
    <input type="hidden" name="service" value="{$postData['service']}"/>
    <input type="hidden" name="signature" value="{$postData['signature']}"/>  
</form>
</body>
</html>

HTML;
                echo $res;die;
            } else {
                if (strstr($card_info['netway'], "WAP") > '-1') {
                    $asurl = $arrnew['payUrl'];
                    if (file_exists(ROOT_PATH . 'cdn.xml')) {
                        $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
                        $imgCdnUrl = (string)$xml->mobile;
                    } else {
                        $imgCdnUrl = config::getConfig('site_main_domain');
                    }
                    $jsUrl = $imgCdnUrl . "/js/jquery.js";
                    log2file('aiShang.log', $jsUrl);
                    $str = <<<HTML
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>跳转中</title>
       <script src="$jsUrl" type="text/javascript"></script>
    <script>
        $(document).ready(function(){
            $("input[name=pay]").click(function(){
                window.open($("input[name=qrcode_url]").val(), "_blank");
            });
        });
    </script>
</head>
<body>
<input name="qrcode_url" type="hidden" value="$asurl" />
<input name="pay" type="button" value="启动 QQ/微信/支付宝 支付" />
</body>
</html>
HTML;
                    echo $str;
                    die();
                } else {
                    header("location:" . '../pay/qrcode.php?code=' . $arrnew['qrCode'] . '&netway=' . $data['card_info']['netway'] . '&amount=' . $data['money'] ."&cdn=" .$this->cdn() );

                }
            }
        } else {
            echo "生成订单失败!";
            log2file('aiShang.log', $postData);
        }
    }

    public function arrToQuery($arrayQuery, $urlEncode = true)
    {
        ksort($arrayQuery);
        $tmp = array();
        foreach ($arrayQuery as $k => $param) {
            $tmp[] = $k . '=' . ($urlEncode ? urlencode($param) : $param);
        }
        $signStr = implode('&', $tmp);
        return $signStr;
    }

    public function sign(&$params, $privKey)
    {
        $params_str = $this->arrToQuery($params, false);
        $status = @openssl_sign($params_str, $signature, $privKey);
        if ($status) {
            $signature_base64 = base64_encode($signature);
            //$params['signature'] = $signature_base64;
            $assign = $signature_base64;
            return $assign;
        }
    }

    public function verify($params, $pubKey)
    {
        $signature_str = $params['signature'];
        unset($params['signature']);
        $params_str = $this->arrToQuery($params, false);
        $signature = base64_decode($signature_str);
        $status = @openssl_verify($params_str, $signature, $pubKey);
        return $status;
    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('aiShang', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
            //$response = json_decode($response,true);
        }

        $orderNo = isset($response['orderNo']) ? $response['orderNo'] : '';//商户ID
        if (empty($orderNo)) {
            $this->logdump('aiShang', '回掉(orderNo为空!)');
            exit;
        }
        $payAmount = isset($response['payAmount']) ? $response['payAmount'] : 0;//订单号
        if ($payAmount === 0) {
            $this->logdump('aiShang', '回掉(payAmount为空!)');
            exit;
        }

        $merOrderNo = isset($response['merOrderNo']) ? $response['merOrderNo'] : '';//订单号
        if (empty($merOrderNo)) {
            $this->logdump('aiShang', '回掉(merOrderNo为空!)');
            exit;
        }

        $status = isset($response['status']) ? $response['status'] : '';//订单金额 单位分（人民币）
        if (empty($status)) {
            $this->logdump('aiShang', '回掉status为空');
            exit;
        }

        $reqTime = isset($response['reqTime']) ? $response['reqTime'] : '';//1:支付成功，非1为支付失败
        if (empty($reqTime)) {
            $this->logdump('aiShang', '回掉(reqTime为空)');
            exit;
        }

        $payTime = isset($response['payTime']) ? $response['payTime'] : '';//月宝订单号
        if (empty($payTime)) {
            $this->logdump('aiShang', '回掉(payTime为空!)');
            exit;
        }

        $signature = isset($response['signature']) ? $response['signature'] : '';//月宝订单号
        if (empty($signature)) {
            $this->logdump('aiShang', '回掉(signature为空!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$merOrderNo}'");

        if (empty($deposits)) {
            $this->logdump('aiShang', '没有找到订单:' . $merOrderNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('aiShang', '没有找到订单:' . $merOrderNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('aiShang', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('aiShang', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['card_pass'];//
        $status = $this->verify($response, $key);//第三方提供的回调验签函数
        if ($status)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $merOrderNo, 'aiShang');
        } else {
            $this->logdump('aiShang', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}