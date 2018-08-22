<?php
namespace sscapp\payment;
class sanerPay extends payModel{
    /**
     * 元宝
     * @param array $data
     * @return string $str
     * @author Jacky
     */

    public function orderNumber($asd = 0)
    {
        $orderno = "1044" . date("YmdHis") . rand(1000, 9999);
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
        $apiUrl = $GLOBALS['cfg']['pay_url']['32pay_api'];
        if ($card_info['netway'] == "QQ") {
            $banktype = "89";
        } elseif ($card_info['netway'] == "WX") {
            $banktype = "21";
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
        $postData['P_UserId'] = $partner;//
        $postData['P_ServiceName'] = "GetBankGateway";//
        $postData['P_TimesTamp'] = "";//
        $str = $postData['P_UserId'].'|'.$postData['P_ServiceName'].'|'.$postData['P_TimesTamp'].'|'.$key;
        $postData['P_PostKey'] = md5($str);//
        $info = array();

        $url = $apiUrl."?P_UserId=".$postData['P_UserId'];
        $url.= "&P_ServiceName=".$postData['P_ServiceName'];
        $url.= "&P_TimesTamp=".$postData['P_TimesTamp'];
        $url.= "&P_PostKey=".$postData['P_PostKey'];//
        $jsonResponse = $this->curlGetData($url, $info);
        $jsonResponse = json_decode($jsonResponse,true);//
        if(!isset($jsonResponse['P_SubmitUrl'])){
            $this->_error = "返回值为空!";
            return false;
        }//

        $postData['P_UserId'] = $partner;
        $postData['P_OrderId'] = $data['ordernumber'];
        $postData['P_CardId'] = "";
        $postData['P_CardPass'] = "";
        $postData['P_FaceValue'] = $data['money'];
        $postData['P_ChannelId'] = $banktype;
        $postData['P_Result_URL'] = $callbackurl;//不参与签名
        $postData['P_Notify_URL'] = $sameBackUrl;
        $postData['P_Price'] = "1.01";
        $postData['P_Quantity'] = "1";//

        $str = $postData['P_UserId'].'|'.$postData['P_OrderId'].'|'.$postData['P_CardId'].'|'.$postData['P_CardPass'].'|'.$postData['P_FaceValue'].'|'.$postData['P_ChannelId'].'|'.$key;
        $postData['P_PostKey'] = md5($str);//

        $url = $jsonResponse['P_SubmitUrl']."?P_UserId=".$postData['P_UserId'];
        $url.= "&P_OrderId=".$postData['P_OrderId'];
        $url.= "&P_CardId=".$postData['P_CardId'];
        $url.= "&P_CardPass=".$postData['P_CardPass'];
        $url.= "&P_FaceValue=".$postData['P_FaceValue'];
        $url.= "&P_ChannelId=".$postData['P_ChannelId'];
        $url.= "&P_Result_URL=".$postData['P_Result_URL'];
        $url.= "&P_Notify_URL=".$postData['P_Notify_URL'];
        $url.= "&P_Price=".$postData['P_Price'];
        $url.= "&P_Quantity=".$postData['P_Quantity'];
        $url.= "&P_PostKey=".$postData['P_PostKey'];//
        return ['code' => 'url', 'url' => $url];

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
            $this->logdump('sanerPay', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }

        if ($is_raw) {
            parse_str($response, $response);
        }

        $P_FaceValue = isset($response['P_FaceValue']) ? $response['P_FaceValue'] : '';//订单号

        if (empty($P_FaceValue)) {
            $this->logdump('sanerPay', '回掉(P_FaceValue为空!)');
            exit;
        }


        $P_PayMoney = isset($response['P_PayMoney']) ? $response['P_PayMoney'] : 0;//订单号

        if ($P_PayMoney === 0) {
            $this->logdump('sanerPay', '回掉(P_PayMoney为空!)');
            exit;
        }


        $P_UserId = isset($response['P_UserId']) ? $response['P_UserId'] : '';//订单号
        if (empty($P_UserId)) {
            $this->logdump('sanerPay', '回掉(P_UserId为空!)');
            exit;
        }

        $P_OrderId = isset($response['P_OrderId']) ? $response['P_OrderId'] : '';//1:支付成功，非1为支付失败
        if (empty($P_OrderId)) {
            $this->logdump('sanerPay', '回掉(P_OrderId为空)');
            exit;
        }


        $P_ChannelId = isset($response['P_ChannelId']) ? $response['P_ChannelId'] : '';//月宝订单号
        if (empty($P_ChannelId)) {
            $this->logdump('sanerPay', '回掉(P_ChannelId为空!)');
            exit;
        }
        $P_ErrCode = isset($response['P_ErrCode']) ? $response['P_ErrCode'] : 0;//月宝订单号

        $P_PostKey = isset($response['P_PostKey']) ? $response['P_PostKey'] : '';//月宝订单号
        if (empty($P_PostKey)) {
            $this->logdump('sanerPay', '回掉(P_PostKey为空!)');
            exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$P_OrderId}'");

        if (empty($deposits)) {
            $this->logdump('sanerPay', '没有找到订单:' . $P_OrderId . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('sanerPay', '没有找到订单:' . $P_OrderId . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('sanerPay', '配置有误,请联系客服!');
            exit;
        }
        $partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('sanerPay', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $str = $P_UserId.'|'.$P_OrderId .'|'.$response["P_CardId"].'|'.$response["P_CardPass"].'|'.$P_FaceValue.'|'.$P_ChannelId.'|'.$P_PayMoney.'|'.$P_ErrCode.'|'.$key;
        $newPostKey = md5($str);

        if ($newPostKey == $P_PostKey && $P_ErrCode == "0")//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'errCode=0', $deposits, $P_OrderId, 'sanerPay');
        } else {
            $this->logdump('sanerPay', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}