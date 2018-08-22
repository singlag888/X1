<?php
/**
 * 在线宝微信WAP
 * 充值金额100以上
 * @author Jacky
 */

namespace sscapp\payment;


class zaixianbaoWAP extends payModel
{

    private $_merchno = '';//商户ID1
    private $_notifyUrl = ''; //异步回掉地址1
    private $_key = '';//商户key
    private $_requestUrl = 'http://p.1wpay.com/a/wapPay';//第三方请求地址
    private $_sameBackUrl = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1017' . date('YmdHis') . rand(100, 999) . rand(100, 999) . rand(1, 9);
        // TODO: Implement orderNumber() method.
    }


    /**
     * 执行
     * @param array $data 参数
     * @return mixed 返回数据
     */
    public function run($data = [])
    {

        $card_info = isset($data['card_info']) ? $data['card_info'] : '';
        if (empty($card_info)) {
            $this->_error = '卡信息出错,请联系客服!';
            return false;
        }
        if (empty($card_info['mer_key'])) {
            $this->_error = '配置有误,请联系客服1!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服2!';
            return false;
        }
        $bankId = $card_info['bank_id'];
        if (empty($bankId)) {
            $this->_error = '配置有误,请联系客服3!';
            return false;
        }
        $this->_merchno = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $className = end($className);
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $protocol = $this->getProtocol($area_flag);
        $this->_notifyUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . $className;
        $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . $className;
        $netway = $card_info['netway'];
        if ($netway == "WX_WAP") {
            $service_type = "2";
        } elseif($netway == "QQ") {
            $service_type = "4";
        } else {
            $this->_error = '此卡暂不支持该充值方式!';
            return false;
        }
        $dataTmp = array(
            'settleType' => '1',//
            'notifyUrl' => $this->_notifyUrl,//
            'payType' => $service_type,//
            'traceno' => $data['ordernumber'],//
            'merchno' => $this->_merchno,//
            'goodsName' => 'happy',//
            'amount' => $data['money'],//
        );
        $jsonResponse = @iconv('GB2312', 'UTF-8', $this -> doCurlPostRequest($dataTmp['payType'], $dataTmp['amount'],$timeout = 5,$this->_requestUrl,$this->_key,$dataTmp));
        $response = json_decode($jsonResponse,true);
        if ($response['respCode'] != "00"){
            $this->_error = $response['message'];
            return false;
        }
            return ['code' => 'qrcode', 'url' => $response['barCode']];


        // TODO: Implement run() method.
    }


    public function doCurlPostRequest($paytype, $amount,$timeout = 5,$urls,$Md5,$post_data) {
        //$urls = Configs::URLPASSIVEPAY;
        //$Md5 = Configs::SIGNATURE;
        //$merchnos = Configs::MERCHNO;
        //$timetraceno = Configs::TIMETRA . date('ymdhis', time());
        //$certno = Configs::CERTNO;
        //$notifyUrl = Configs::NOTIFYURL;
        //$accountno = Configs::ACCOUNTNO;
        //$mobile = Configs::MOBILE;
        //$account_name = Configs::ACCOUNT_NAME;
        //$fee = $amount * Configs::FEE;
        /*if ($fee < 0.01) {
            $fee = 0.01;
        }*/
        $pyte = '';
        //$post_data = '';
        /*if ($gettp == '1') {
            $post_data = array('merchno' => $merchnos, 'amount' => $amount, 'traceno' => $timetraceno, 'payType' => $paytype, 'certno' => $certno, 'accountno' => $accountno, 'account_name' => $account_name, 'fee' => $fee, 'notifyUrl' => $notifyUrl, 'settleType' => '0', 'mobile' => $mobile, "goodsName" => '商品', 'remark' => "beizhu");
        } else {
            $post_data = array('merchno' => $merchnos, 'amount' => $amount, 'traceno' => $timetraceno, 'payType' => $paytype, 'notifyUrl' => $notifyUrl, 'goodsName' => '商品', 'settleType' => '1', 'remark' => "beizhu");
        }*/
        ksort($post_data);
        $a = '';
        foreach ($post_data as $x => $x_value) {
            $a = $a . $x . "=" . iconv('UTF-8', 'GB2312', $x_value) . "&";
        }
        $b = md5($a . $Md5);
        $c = $a . 'signature' . '=' . $b;
        if ($urls == '' || $c == '' || $timeout <= 0) {
            return false;
        }
        $con = curl_init((string)$urls);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POSTFIELDS, $c);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);
        return curl_exec($con);
    }

    /**
     * 支付回掉入口
     * 1.回掉验签
     * 2.执行本地逻辑
     * @param array $response 第三方回掉返回数据
     * @param int $is_raw 返回数据结构形式 主要表示是否是原始数据流 0不是 1是
     */
    public function callback($response = '', $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('zaixianbao', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $merchno = isset($response['merchno']) ? $response['merchno'] : '';//返回的状态码
        if (empty($merchno)) {
            $this->logdump('zaixianbao', '回掉商户号为空:' . $response);
            exit;
        }
        $status = isset($response['status']) ? $response['status'] : '';//订单号
        if (empty($status)) {
            $this->logdump('zaixianbao', '没有反回支付状态');
            exit;
        }

        $traceno = isset($response['traceno']) ? $response['traceno'] : '';//订单号
        if (empty($traceno)) {
            $this->logdump('zaixianbao', '没有反回支付处理信息');
            exit;
        }
        $orderno = isset($response['orderno']) ? $response['orderno'] : '';
        if (empty($orderno)) {
            $this->logdump('zaixianbao', '没有返回订单号');
            exit;
        }

        $merchName = isset($response['merchName']) ? $response['merchName'] : '';//1:支付成功，非1为支付失败
        if (empty($merchName)) {
            $this->logdump('zaixianbao', '回掉(merchName为空)');
            exit;
        }
        $amount = isset($response['amount']) ? $response['amount'] : 0;//订单金额 单位元（人民币）
        if ( $amount === 0) {
            $this->logdump('zaixianbao', '回掉(订单金额为0!)');
            exit;
        }
        $transDate = isset($response['transDate']) ? $response['transDate'] : '';//月宝订单号
        if (empty($transDate)) {
            $this->logdump('zaixianbao', '回掉(交易日期为空!)');
            exit;
        }

        $transTime = isset($response['transTime']) ? $response['transTime'] : '';
        if (empty($transTime)) {
            $this->logdump('zaixianbao', '回掉(交易时间为空!)');
            exit;
        }
        $payType = isset($response['payType']) ? $response['payType'] : '';
        if (empty($payType)) {
            $this->logdump('zaixianbao', '回掉(payType为空!)');
            exit;
        }

        $signature = isset($response['signature']) ? $response['signature'] : '';
        if (empty($signature)) {
            $this->logdump('zaixianbao', '回掉(signature为空!)');
            exit;
        }
        unset($response["signature"]);
        unset($response["channelOrderno"]);
        unset($response["channelTraceno"]);
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$traceno}'");
        if (empty($deposits)) {
            $this->logdump('zaixianbao', '没有找到订单:' . $traceno . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('zaixianbao', '没有找到订单:' . $traceno . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('zaixianbao', '配置有误,请联系客服!');
            exit;
        }
        $this->_merchno = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('zaixianbao', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        ksort($response);

        $a = '';
        foreach ($response as $x => $x_value) {
            if ($x_value) {
                $a = $a . $x . "=" . $x_value . "&";
            }
        }

        $b = md5($a . $this->_key);
        $d = strtoupper($b);

        if ($d == $signature)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $traceno, 'zaixianbao');
        } else {
            $this->logdump('zaixianbao', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}