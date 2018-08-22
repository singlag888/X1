<?php
namespace sscapp\payment;
class yiDao extends payModel{
    /**
     * 联付宝
     * @param array $data
     * @return string $str
     * @author Jacky
     */

    public function orderNumber($asd = 0)
    {
        $orderno = "1051" . date("YmdHis") . rand(1000, 9999);
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
        $tkey = $card_info['ukeypwd'];
        $className = explode('\\', __CLASS__);
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $shop_url = $card_info['shop_url'];
        $callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $sameBackUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['yidao_api'];
        if ($card_info['netway'] == 'WX') {
              $service_type = "WXZF";
        } elseif ($card_info['netway'] == 'WX_WAP') {
              $service_type = "WXH5";
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = "ZFBZF";
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = "ZFBH5";
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = "QQZF";
        } elseif ($card_info['netway'] == 'WY') {
            $apiUrl = $GLOBALS['cfg']['pay_url']['yidaowy_api'];
            switch ($data['third_party_bank_id']) {
                // 工商银行
                case '1':
                    $banktype = '10001';
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
                    $banktype = 'ECITIC';
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
                    $banktype = 'CGB';
                    break;
                // 平安银行
                case '13':
                    $banktype = 'PAB';
                    break;
                // 华夏银行
                case '15':
                    $banktype = '10025';
                    break;
                // 东莞银行
                case '16':
                    break;
                // 渤海银行
                case '17':
                    $banktype = '10017';
                    break;
                // 浙商银行
                case '19':
                    break;
                // 北京银行
                case '20':
                    $banktype = 'BOB';
                    break;
                // 广州银行
                case '21':
                    $banktype = '';
                    break;
                // 中国银行
                case '22':
                    $banktype = 'BOC';
                    break;
                // 邮政储蓄
                case '23':
                    $banktype = 'PSBC';
                    break;
            }
        } elseif ($card_info['netway'] == 'KJ') {
            $apiUrl = $GLOBALS['cfg']['pay_url']['yidaokj_api'];
        } else {
            $this->_error = '暂时不支持该支付!';
        }
        if($card_info['netway'] != 'WY' && $card_info['netway'] != 'KJ') {
            $merchantCode = $partner;
            $version = "1.0.1";//
            $subject = "lucky";//
            $amount = $data['money'];
            $notifyUrl = $callbackurl;
            $orgOrderNo = $data['ordernumber'];//
            $returnUrl = $sameBackUrl;
            $expireTime = 2;//
            $source = $service_type;
            $tranTp = 0;
            $extra_para = $data['ordernumber'];
            $yddata = $this->pay($orgOrderNo, $subject, $amount, $source, $version, $notifyUrl, $tranTp, $extra_para, $returnUrl, $key, $partner, $tkey);
            $response = $this->ydpost($apiUrl, $yddata);
            if (!isset($response['responseObj']['qrCode']) || $response['responseObj']['qrCode'] == "") {
                if (isset($response['responseObj']['respMsg'])) {
                    $this->_error = $response['responseObj']['respMsg'];
                    die();
                } else {
                    $this->_error = "生成二维码失败!";
                    die();
                }
            }
            $qrCode = $response['responseObj']['qrCode'];
        }
        if($card_info['netway'] == 'WY'){
            $merchantCode = $partner;
            $amount = $data['money'];
            $version = "1.0.1";
            $notifyUrl = $callbackurl;
            $tranTp= 0;
            $orgOrderNo = $data['ordernumber'];
            $num = '1';
            $esc= '1';
            $subject = "lucky";
            $extra_para = $data['ordernumber'];
            $bankWay = $banktype;
            $returnUrl =urlencode($sameBackUrl);
            $ydwyback = $this->ydwypay($merchantCode,$amount,$version,$notifyUrl,$tranTp,$orgOrderNo,$subject,$extra_para,$bankWay,$returnUrl,$key,$partner,$tkey);
            if(!isset($ydwyback['transData']) || !isset($ydwyback['merchantCode']) || !isset($ydwyback['extra_para']) ){
                $this->_error = "网银通道繁忙,请稍候再试!";
                die();
            }
            $ydtrandata = $ydwyback['transData'];
            $ydmerchantCode = $ydwyback['merchantCode'];
            $ydextra_para = $ydwyback['extra_para'];

        }
                if (strstr($card_info['netway'], "WAP") > '-1') {
                    $qrCode = 'http://'.$qrCode;
                    return ['code' => 'url', 'url' => $qrCode];
                    die();
                }elseif($card_info['netway'] == 'WY'){
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
    <input type="hidden" name="version" value="{$ydtrandata}"/>
    <input type="hidden" name="platformNo"		  value="{$ydmerchantCode}" />
    <input type="hidden" name="merNo"     value="{$ydextra_para}"/>
</form>
</body>
</html>

HTML;
                    echo $res;die;
                } else {
                    $ydorder = $data['ordernumber'];
                    $ydmoney = $data['money'];
                    $xinyun =  <<<XINGYUN
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<style>
  .dd{
    text-align: center;
  }
  .money{
    text-align: center;
    font-size: 35px ;
  }
   .qrcode{
       display: block;
       margin: 30px auto 0;
       width: 300px;
   }
   p{
       text-align: center;
       font-size: 14px;
   }
   @media (max-width: 600px) {
       .qrcode{
           margin: 0 auto;
           width: 80%;
       }
   }
</style>
<body>
    <br/><br/>
  <div class="dd">订单号：$ydorder</div>
  <br/><br/>
  <div class="money">￥ $ydmoney</div>
<img class="qrcode" src="http://qr.liantu.com/api.php?text=$qrCode" alt="">
<p>扫描二维码完成支付</p>
XINGYUN;
                    return ['code' => 'html', 'html' => $xinyun];
                    die();
                }
    }

    public function ydwypay($merchantCode,$amount,$version,$notifyUrl,$tranTp,$orgOrderNo,$subject,$extra_para,$bankWay,$returnUrl,$key,$partner,$tkey)
    {
        //组装参数
        $arr['merchantCode'] = $merchantCode;
        $arr['amount'] = $amount;
        $arr['version'] = $version;
        $arr['notifyUrl '] = urlencode($notifyUrl);
        $arr['tranTp'] = $tranTp;
        $arr['orgOrderNo'] = $orgOrderNo;
        $arr['num'] = '1';
        $arr['desc'] = '1';
        $arr['subject'] = $subject;
        $arr['extra_para'] = $extra_para;
        $arr['bankWay'] = $bankWay;
        $arr['returnUrl'] =urlencode($returnUrl);
        return $this->wysign($arr,$tkey,$key,$partner,$extra_para);
    }

    public function ydpost($ydurl,$data){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ydurl);

        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_HEADER,false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
// post数据
        curl_setopt($ch, CURLOPT_POST,1);
// post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($output,true);
        return $response;
    }

    public function pay($orgOrderNo, $subject, $amount, $source, $version, $notifyUrl, $tranTp, $extra_para, $returnUrl,$key,$partner,$tkey)
    {
        //组装参数
        $arr['amount'] = $amount;
        $arr['version'] = $version;
        $arr['notifyUrl '] = $notifyUrl;
        $arr['tranTp'] = $tranTp;
        $arr['orgOrderNo'] = $orgOrderNo;
        $arr['subject'] = $subject;
        $arr['extra_para'] = $extra_para;
        $arr['source'] = $source;
        $arr['returnUrl'] = $returnUrl;
        return $this->sign($arr,$key,$partner,$tkey);
    }

    public function sign($arr,$key,$partner,$tkey)
    {
        $str = $this->sortData($arr);
        $baseStr = base64_encode($str);
        $aesPrivage = $this->encrypt($baseStr, $tkey);
        $aesPrivage = strtoupper($aesPrivage);
        $sign = strtoupper(md5($aesPrivage . $key));
        $arr['sign'] = $sign;
        $str2 = $this->sortData($arr);
        $baseStr2 = base64_encode($str2);
        $transData = $this->encrypt($baseStr2, $tkey);
        $data = array();
        $data['merchantCode'] = $partner;
        $data['transData'] = $transData;
        $reqStr = "reqJson=" . json_encode($data);
        return $reqStr;
    }

    public function wysign($arr,$tkey,$key,$partner,$extra_para)
    {
        $str = $this->sortData($arr);
        $baseStr = base64_encode($str);
        $aesPrivage = $this->encrypt($baseStr, $tkey);
        $aesPrivage = strtoupper($aesPrivage);
        $sign = strtoupper(md5($aesPrivage . $key));
        $arr['sign'] = $sign;
        $str2 = $this->sortData($arr);
        $baseStr2 = base64_encode($str2);
        $transData = $this->encrypt($baseStr2, $tkey);
        $data = array();
        $data['merchantCode'] = $partner;
        $data['transData'] = $transData;
        $data['extra_para'] = $extra_para;
        return $data;
    }


    public function sortData($arr)
    {
        array_walk($arr, function (&$v) {
            if (is_array($v)) {
                array_walk_recursive($v, function (&$v1) {
                    if (is_object($v1)) {
                        $v1 = get_object_vars($v1);
                        ksort($v1);
                    }
                });
                ksort($v);
            }
        });

        ksort($arr);
        key($arr);
        $str = "";
        foreach (array_keys($arr) as $key) {
            $str .= $key . "=" . $arr[$key] . "&";
        }
        $str = rtrim($str, "&");
        $str = str_replace(" ", "", $str);
        return $str;
    }

    public function encrypt($input,$key) {

        $size = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = $this->pkcs5_pad($input, $size);
        $td = @mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        @mcrypt_generic_init($td, $key, $iv);
        $data = @mcrypt_generic($td, $input);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);
        $data = bin2hex($data);
        return $data;

    }

    private function pkcs5_pad($text, $blocksize) {

        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);

    }

    public function VerifySign($str,$key,$tkey){
        //解密
        $sec_dec = $this->decrypt($str,$tkey);
        $sec_dec = base64_decode($sec_dec);
        //分割
        $pra = explode("&",$sec_dec);
        $result_pra = array();//异步回调的参数
        foreach($pra as $thispra){
            $temp_pra = explode("=",$thispra);
            $result_pra[$temp_pra[0]] = $temp_pra[1];
        }
        $sign = $result_pra["sign"];
        //移除sign
        unset($result_pra["sign"]);
        $result_str = $this->sortData($result_pra);
        $baseStr = base64_encode($result_str);
        $aesPrivage = $this->encrypt($baseStr, $tkey);
        $aesPrivage = strtoupper($aesPrivage);
        $sign2 = strtoupper(md5($aesPrivage . $key));
        if ($sign==$sign2){
            return $result_pra;
        }else{
            return false;
        }

    }

    public function decrypt($sStr,$key) {
        $decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$key,$this->hex2bin($sStr), MCRYPT_MODE_ECB);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    public function hex2bin($data) {
        $len = strlen($data);
        $newdata='';
        for($i=0;$i<$len;$i+=2) {
            $newdata .= pack("C",hexdec(substr($data,$i,2)));
        }
        return $newdata;
    }


    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('yiDao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        /*if ($is_raw) {
            parse_str($response, $response);
            $response = json_decode(stripslashes($response['reqJson']),true);
        }*/
        parse_str($response, $response);
        $response = json_decode(stripslashes($response['reqJson']),true);
        $transData = isset($response['transData']) ? $response['transData'] : '';//商户ID
        if (empty($transData)) {
            $this->logdump('yiDao', '回掉(transData为空!)');
            exit;
        }
        $extra_para = isset($response['extra_para']) ? $response['extra_para'] : '';//订单号
        if (empty($extra_para)) {
            $this->logdump('yiDao', '回掉(extra_para为空!)');
            exit;
        }

        $merchantCode = isset($response['merchantCode']) ? $response['merchantCode'] : '';//1:支付成功，非1为支付失败
        if (empty($merchantCode)) {
            $this->logdump('yiDao', '回掉(merchantCode为空)');
            exit;
        }

        $TradeCode = isset($response['TradeCode']) ? $response['TradeCode'] : '';//1:支付成功，非1为支付失败
        if (empty($TradeCode)) {
            $this->logdump('yiDao', '回掉(TradeCode为空)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$extra_para}'");

        if (empty($deposits)) {
            $this->logdump('yiDao', '没有找到订单:' . $extra_para . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('yiDao', '没有找到订单:' . $extra_para . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('yiDao', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('yiDao', '配置有误,请联系客服!');
            exit;
        }
        $tkey = $card_info['ukeypwd'];
        $key = $card_info['mer_key'];
        if ($yddata = $this->VerifySign($transData,$key,$tkey)){
            if($yddata['isClearOrCancel']=="0"){
                $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $extra_para, 'yiDao');
            }else{
                $this->logdump('yiDao', '回掉(签名验证不通过!请支付小组查看!)');

            }
        }//签名正确
        die('ok');//到不了这里
    }


}