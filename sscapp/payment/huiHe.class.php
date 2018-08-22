<?php
namespace sscapp\payment;
class huiHe extends payModel{
    /**
     * 汇合京东和QQ
     * @param array $data
     * @return string $str
     * @author Jacky
     */

    public function orderNumber($asd = 0)
    {
        $orderno = "1041" . date("YmdHis") . rand(1000, 9999);
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['huihe_api'];
        if ($card_info['netway'] == "QQ") {
            $banktype = "3";
        } elseif ($card_info['netway'] == "JD") {
            $banktype = "9";
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
        $postData['AppId'] = $partner;//
        $postData['Method'] = "trade.page.pay";//
        $postData['Format'] = "JSON";//
        $postData['Charset'] = "UTF-8";
        $postData['Version'] = "1.0";
        $postData['SignType'] = "MD5";
        $postData['Timestamp'] = date("Y-m-d H:i:s");//
        $postData['PayType'] = $banktype;//
        $postData['OutTradeNo'] = $data['ordernumber'];//
        $postData['TotalAmount'] = $data['money'];//
        $postData['Subject'] = "lucky";
        $postData['Body'] = "lucky";//
        $postData['NotifyUrl'] = $callbackurl;//

        $waitLink = "AppId=".$postData["AppId"]
            ."&Body=".$postData["Body"]
            ."&Charset=".$postData["Charset"]
            ."&Format=".$postData["Format"]
            ."&Method=".$postData["Method"]
            ."&NotifyUrl=".$postData["NotifyUrl"]
            ."&OutTradeNo=".$postData["OutTradeNo"]
            ."&PayType=".$postData["PayType"]
            ."&Subject=".$postData["Subject"]
            ."&Timestamp=".$postData["Timestamp"]
            ."&TotalAmount=".$postData["TotalAmount"]
            ."&Version=".$postData["Version"];//

        $postData["Sign"] = md5($waitLink . $key);//
        $response = $this->hhcurlpost($apiUrl,$postData);
        $response = json_decode($response,true);
        if(!isset($response['QrCode'])){
            $this->_error = "返回值为空!";
            return false;
        }//

        return ['code' => 'qrcode', 'url' => $response['QrCode']];
    }




    public function hhcurlpost($apiUrl,$postData){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response=curl_exec($ch);

        return $response;
    }

    public  function hhbuildRequestPara($para_temp,$SignType,$merkey) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->hhparaFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->hhargSort($para_filter);

        //生成签名结果
        $mysign = $this->hhbuildRequestMysign($para_sort,$SignType,$merkey);

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['Sign'] = $mysign;
        $para_sort['SignType'] = strtoupper(trim($SignType));

        return $para_sort;
    }

    public function hhparaFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "Sign" || $key == "SignType" || $val == "")continue;
            else	$para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    public function hhbuildRequestMysign($para_sort,$SignType,$merkey) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->hhcreateLinkstring($para_sort);

        $mysign = "";
        switch (strtoupper(trim($SignType))) {
            case "MD5" :
                $mysign = $this->hhmd5Sign($prestr,$merkey);
                break;
            default :
                $mysign = "";
        }

        return $mysign;
    }

    public function hhargSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }


    public function hhcreateLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }

    public function hhmd5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }


    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('huiHe', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }

        if ($is_raw) {
            parse_str($response, $response);
        }

        $TotalAmount = isset($response['TotalAmount']) ? $response['TotalAmount'] : 0;//订单号

        if ($TotalAmount === 0) {
            $this->logdump('huiHe', '回掉(TotalAmount为空!)');
            exit;
        }

        $AppId = isset($response['AppId']) ? $response['AppId'] : '';//订单号
        if (empty($AppId)) {
            $this->logdump('huiHe', '回掉(AppId为空!)');
            exit;
        }

        $Code = isset($response['Code']) ? $response['Code'] : 0;//1:支付成功，非1为支付失败

        $OutTradeNo = isset($response['OutTradeNo']) ? $response['OutTradeNo'] : '';//月宝订单号
        if (empty($OutTradeNo)) {
            $this->logdump('huiHe', '回掉(OutTradeNo为空!)');
            exit;
        }

        $Sign = isset($response['Sign']) ? $response['Sign'] : '';//月宝订单号
        if (empty($Sign)) {
            $this->logdump('huiHe', '回掉(Sign为空!)');
            exit;
        }

        $TradeNo = isset($response['TradeNo']) ? trim($response['TradeNo']) : '';//月宝订单号
        if (empty($TradeNo)) {
            $this->logdump('huiHe', '回掉(TradeNo为空!)');
            exit;
        }

        $SignType = isset($response['SignType']) ? $response['SignType'] : '';//月宝订单号
        if (empty($SignType)) {
            $this->logdump('huiHe', '回掉(SignType为空!)');
            exit;
        }
        unset($response["Sign"]);
        unset($response["SignType"]);
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$OutTradeNo}'");

        if (empty($deposits)) {
            $this->logdump('huiHe', '没有找到订单:' . $OutTradeNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('huiHe', '没有找到订单:' . $OutTradeNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('huiHe', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('huiHe', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $html_text = $this->hhbuildRequestPara($response,$SignType,$key);
        if (($html_text['Sign']==strtolower($Sign))&&($response['Code']=="0"))//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $OutTradeNo, 'huiHe');
        } else {
            $this->logdump('huiHe', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}