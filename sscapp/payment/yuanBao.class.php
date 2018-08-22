<?php
namespace sscapp\payment;
class yuanBao extends payModel{
    /**
     * 元宝
     * @param array $data
     * @return string $str
     * @author Jacky
     */

    public function orderNumber($asd = 0)
    {
            $orderno = "1038" . date("YmdHis") . rand(1000, 9999);
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
            return false;
        }
        $partner = $card_info['mer_no'];
        $key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $shop_url = $card_info['shop_url'];
        $callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $sameBackUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $banktype = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['yuanbao_api'];
        if ($card_info['netway'] == "ZFB") {
            $banktype = "ALIPAY";
        } elseif ($card_info['netway'] == "QQ") {
            $banktype = "QQ";
        } elseif ($card_info['netway'] == "ZFB_WAP") {
            $banktype = "ALIPAYWAP";
        } elseif ($card_info['netway'] == "QQ_WAP") {
            $banktype = "QQWAP";
        } elseif ($card_info['netway'] == "WX") {
            $banktype = "WEIXIN";
        } elseif ($card_info['netway'] == "WX_WAP") {
            $banktype = "WEIXINWAP";
        } elseif ($card_info['netway'] == "WY") {
            $third_party_bank_id = $this->request->getPost('third_party_bank_id', 'intval');
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
            }

        } else {
            $this->_error = '暂时不支持该支付!';
            return false;
        }
        $postData['version'] = "3.0";//
        $postData['method'] = "Boh.online.interface";//
        $postData['partner'] = $partner;//
        $postData['banktype'] = $banktype;//
        $postData['paymoney'] = $data['money'];//
        $postData['ordernumber'] = $data['ordernumber'];//
        $postData['callbackurl'] = $callbackurl;//
        $postData['sign'] = md5("version=".$postData['version']."&method=".$postData['method']."&partner=".$postData['partner']."&banktype=".$postData['banktype']."&paymoney=".$postData['paymoney']."&ordernumber=".$postData['ordernumber']."&callbackurl=".$postData['callbackurl'].$key);
        $info = array();//
        $jsonResponse = $this->curlPostData($apiUrl, $postData, $info);//
        if ($jsonResponse == null) {
            $this->logdump('yuanbaoPay.log', '第三方返回数据异常');
            $this->logdump('yuanbaoPay.log', $postData);
            $this->logdump('yuanbaoPay.log', $jsonResponse);
            $this->_error = '第三方返回数据异常';
            return false;
        }

        //echo $jsonResponse;
        //dd($jsonResponse);
        $reponse = json_decode($jsonResponse,true);

        if ($reponse != null && $reponse['status'] !== '1') {
            $this->logdump('yuanbaoPay.log', $reponse['message']);
            $this->logdump('yuanbaoPay.log', $postData);
            $this->logdump('yuanbaoPay.log', $jsonResponse);
            $this->_error = $reponse["message"];
            return false;
        }
        if (strstr($card_info["netway"], "WAP") > -1){
            $http=$jsonResponse;
            preg_match("/(https:\/\/).*(\")/i",$http,$matches);
            $wapurl = substr($matches['0'],0,-1);
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
<form name="diy" id="diy" action="$wapurl"  method="post">
   
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
HTML;

            return ['code' => 'html', 'html' => $str];
        }else{
            if ($reponse != null && $reponse['status'] == '1') {
                return ['code' => 'qrcode', 'url' => $reponse['qrurl']];
            }else{
                if(isset($reponse['message'])){
                    echo $reponse['message'];
                }else {
                    $this->_error = "生成支付二维码失败!";
                    return false;
                }
            }
        }
    }


    public function curlPostData($url, $data, &$info)
    {
        // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            logdump('CURL Errno' . curl_error($curl)); //捕抓异常
        }
        $info = curl_getinfo($curl);
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    public function curlGetData($url, &$info)
    {
        // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            logdump('CURL Errno' . curl_error($curl)); //捕抓异常
        }
        $info = curl_getinfo($curl);
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('yuanBao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }

        if ($is_raw) {
            parse_str($response, $response);
        }

        $paymoney = isset($response['paymoney']) ? $response['paymoney'] : 0;//订单号

        if ($paymoney === 0) {
            $this->logdump('yuanBao', '回掉(paymoney为空!)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//订单号
        if (empty($sign)) {
            $this->logdump('yuanBao', '回掉(sign为空!)');
            exit;
        }

        $partner = isset($response['partner']) ? $response['partner'] : '';//1:支付成功，非1为支付失败
        if (empty($partner)) {
            $this->logdump('yuanBao', '回掉(partner为空)');
            exit;
        }

        $ordernumber = isset($response['ordernumber']) ? $response['ordernumber'] : '';//月宝订单号
        if (empty($ordernumber)) {
            $this->logdump('yuanBao', '回掉(ordernumber为空!)');
            exit;
        }

        $orderstatus = isset($response['orderstatus']) ? $response['orderstatus'] : '';//月宝订单号
        if (empty($orderstatus)) {
            $this->logdump('yuanBao', '回掉(orderstatus为空!)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('yuanBao', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('yuanBao', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('yuanBao', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('yuanBao', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $ansign = md5("partner=" . $partner . "&ordernumber=" . $ordernumber . "&orderstatus=" . $orderstatus . "&paymoney=" . $paymoney . $key);

        if ($ansign == $sign)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'ok', $deposits, $ordernumber, 'yuanBao');
        } else {
            $this->logdump('yuanBao', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}