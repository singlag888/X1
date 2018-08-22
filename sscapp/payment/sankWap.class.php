<?php
namespace sscapp\payment;
class sankWap extends payModel{
    /**
     * 3K微信WAP,支付宝WAP和3K支付宝
     * @param array $data
     * @return string $str
     * @author Jacky
     */
    public function orderNumber($asd = 0)
    {
        $orderno = "1035" . date("YmdHis") . rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

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
        $callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $sameBackUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $banktype = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['3k_api'];
        if ($card_info['netway'] == "WX_WAP") {
            $banktype = "1300";
        } elseif ($card_info['netway'] == "ZFB_WAP") {
            $banktype = "2000";
        } elseif ($card_info['netway'] == "ZFB") {
            $banktype = "1800";
            $apiUrl = $GLOBALS['cfg']['pay_url']['sank_zfb_api'];
        } elseif ($card_info['netway'] == 'WY') {
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
            echo '暂时不支持该支付!';
        }
        $postData['appId'] = explode('_', $partner)[1];//
        $postData['partnerId'] = explode('_', $partner)[0];//
        $postData['channelOrderId'] = $data['ordernumber'];//
        $postData['body'] = 'vip';//
        $postData['totalFee'] = $data['money']*100;//
        $postData['payType'] = $banktype;//
        $postData['timeStamp'] = time() * 1000;//
        $postData['notifyUrl'] = $callbackurl;//
        $postData['returnUrl'] = $sameBackUrl;//
        ksort($postData);//
        $info = array();//
        $postUrl = $apiUrl.'?'.http_build_query($postData);//
        $jsonResponse = $this->curlGetData($postUrl, $info);//
        //$this->logdump('3KPay.log', $jsonResponse);
        //dd($jsonResponse);
        $postData['sign'] = $this->getSanKSign($postData, explode('_', $key)[0]);//
        $response = json_decode($jsonResponse, true);//

        if (!isset($response['return_code'])) {
            $this->logdump('3KPay.log', '第三方返回数据异常');
            $this->logdump('3KPay.log', $postData);
            $this->logdump('3KPay.log', $jsonResponse);
            echo '第三方返回数据异常';
        }

        if ($response['return_code'] !== 0) {
            $this->logdump('3KPay.log', $response['return_msg']);
            $this->logdump('3KPay.log', $postData);
            $this->logdump('3KPay.log', $jsonResponse);
            echo $response['return_msg'];
        }

        if ($response != null && isset($response['return_code']) && $response['return_code'] == '0'){

            return ['code' => 'url', 'url' => $response['payParam']['pay_info']];

        }else{
            if ($response != null && isset($response['return_msg'])) {
                $this->logdump('3kPay.log', $response);
                echo $response['return_msg'];
            } else {
                echo '生成订单失败';
            }

            $this->logdump('3kPay.log', $postData);
            $this->logdump('3kPay.log', $jsonResponse);
        }
    }

    public function getSanKSign($data, $key)
    {
        $signString = 'appId=' . $data['appId'] . '&timeStamp=' . $data['timeStamp'] . '&totalFee=' . $data['totalFee'] . '&key=' . $key;
        return strtoupper(md5($signString));
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
            $this->logdump('sankWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $return_code = isset($response['return_code']) ? $response['return_code'] : '';//商户ID
        if (empty($return_code)) {
            $this->logdump('sankWap', '回掉(return_code为空!)');
            exit;
        }
        $totalFee = isset($response['totalFee']) ? $response['totalFee'] : 0;//订单号
        if ($totalFee === 0) {
            $this->logdump('sankWap', '回掉(totalFee为空!)');
            exit;
        }

        $channelOrderId = isset($response['channelOrderId']) ? $response['channelOrderId'] : '';//订单号
        if (empty($channelOrderId)) {
            $this->logdump('sankWap', '回掉(channelOrderId为空!)');
            exit;
        }

        $orderId = isset($response['orderId']) ? $response['orderId'] : '';//订单金额 单位分（人民币）
        if (empty($orderId)) {
            $this->logdump('sankWap', '回掉orderId为空');
            exit;
        }

        $timeStamp = isset($response['timeStamp']) ? $response['timeStamp'] : '';//1:支付成功，非1为支付失败
        if (empty($timeStamp)) {
            $this->logdump('sankWap', '回掉(timeStamp为空)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('sankWap', '回掉(sign为空!)');
            exit;
        }

        $transactionId = isset($response['transactionId']) ? $response['transactionId'] : '';//月宝订单号
        if (empty($transactionId)) {
            $this->logdump('sankWap', '回掉(transactionId为空!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$channelOrderId}'");

        if (empty($deposits)) {
            $this->logdump('sankWap', '没有找到订单:' . $channelOrderId . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('sankWap', '没有找到订单:' . $channelOrderId . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('sankWap', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('sankWap', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $signSource = sprintf("channelOrderId=%s&key=%s&orderId=%s&timeStamp=%s&totalFee=%s", $channelOrderId, explode('_',$key)[1], $orderId, $timeStamp, $totalFee);

        if ($sign == md5($signSource) && $response['return_code'] == '0')//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'ok', $deposits, $channelOrderId, 'sankWap');
        } else {
            $this->logdump('sankWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}