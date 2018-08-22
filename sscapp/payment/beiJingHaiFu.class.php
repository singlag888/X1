<?php
/**
 * 北京海富支付
 * @author stone
 */

namespace sscapp\payment;


class beiJingHaiFu extends payModel
{

    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_requestUrl = 'http://haifu.neargh.com:9091/paying/lovepay/getQr';//第三方请求地址
    private $_sameBackUrl = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1008' . date('YmdHis') . rand(100, 999) . rand(100, 999) . rand(1, 9);
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
        $this->_partner = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $className = end($className);
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $protocol = $this->getProtocol($area_flag);
        $this->_callbackurl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . $className;
        $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . $className;
        $netway = $card_info['netway'];
        if ($netway == "WX_WAP") {
            $service_type = "2";
        } elseif ($netway == 'WX') {
            $service_type = "0";//微信扫码
        } else if ($netway == "ZFB_WAP") {
            $service_type = "3";
        } else if ($netway === 'KJ') {
            $service_type = "6";
        } else {
            $this->_error = '此卡暂不支持该充值方式!';
            return false;
        }
        $body = array(
            '充电宝',
            '华为手机',
            '充电器',
            '掌中宝',
            '手电筒'
        );
        shuffle($body);
        $total_fee = strval($data['money'] * 100);
        $dataTmp = array(
            'body' => reset($body),
            'goods_tag' => '29937',
            'nonce_str' => strval(rand(10000000, 99999999)),
            'front_notify_url' => $this->_sameBackUrl,
            'notify_url' => $this->_callbackurl,
            'spbill_create_ip' => $this->getClientIp(),
            'pay_type' => $service_type,
            'product_id' => $data['ordernumber'],
            'op_user_id' => $this->_partner,
            'bank_id' => '10001',
            'total_fee' => $total_fee,
        );
        $dataTmp['sign'] = strtoupper(sha1("bank_id={$dataTmp['bank_id']}&body={$dataTmp['body']}&front_notify_url={$dataTmp['front_notify_url']}&goods_tag={$dataTmp['goods_tag']}&nonce_str={$dataTmp['nonce_str']}&notify_url={$dataTmp['notify_url']}&op_user_id={$dataTmp['op_user_id']}&pay_type={$dataTmp['pay_type']}&product_id={$dataTmp['product_id']}&spbill_create_ip={$dataTmp['spbill_create_ip']}&total_fee={$total_fee}{$this->_key}"));
//        echo '<pre>';
//        print_r(json_encode($dataTmp));exit;
        $res = $this->curlPost($this->_requestUrl, json_encode($dataTmp), $className, 1);
        if (empty($res)) {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }
        $objRes = json_decode($res);
        if (is_object($objRes)) {
            if ($objRes->errcode == 200) {
                $code = 'url';
                if ($service_type == 0) {
                    $code = 'qrcode';
                };
                return ['code' => $code, 'url' => $objRes->code_url];
            } else {
                $this->_error = '支付通道异常,请选择其他支付进行充值!';
                $this->logdump($className, $objRes->errcode . $objRes->info);
                return false;
            }
        } else {
            $this->_error = '支付通道异常,请选择其他支付进行充值!';
            $this->logdump($className, $res);
            return false;
        }


        // TODO: Implement run() method.
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
            $this->logdump('beiJingHaiFu', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw = 1) {
            $response = json_decode($response, true);
        }
        $errcode = isset($response['errcode']) ? $response['errcode'] : 0;//返回的状态码
        if (($errcode === 0) || ($errcode != 200)) {
            $this->logdump('beiJingHaiFu', '回掉出错:' . $response);
            exit;
        }
        $state = isset($response['state']) ? $response['state'] : '';//订单号
        if (empty($state)) {
            $this->logdump('beiJingHaiFu', '没有反回支付状态');
            exit;
        }

        $info = isset($response['info']) ? $response['info'] : '';//订单号
        if (empty($info)) {
            $this->logdump('beiJingHaiFu', '没有反回支付处理信息');
            exit;
        }
        $tradeNum = isset($response['tradeNum']) ? $response['tradeNum'] : '';
        if (empty($tradeNum)) {
            $this->logdump('beiJingHaiFu', '没有返回第三方的交易号');
            exit;
        }

        if ($state != 1) {
            $this->logdump('beiJingHaiFu', $response);
        }
        $notifyUrl = isset($response['notifyUrl']) ? $response['notifyUrl'] : '';//1:支付成功，非1为支付失败
        if (empty($notifyUrl)) {
            $this->logdump('beiJingHaiFu', '回掉(notifyUrl为空)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';//订单金额 单位元（人民币）
        if (empty($sign)) {
            $this->logdump('beiJingHaiFu', '回掉(sign为空)');
            exit;
        }
        $ordernumber = isset($response['productId']) ? $response['productId'] : 0;//月宝订单号
        if ($ordernumber === 0) {
            $this->logdump('beiJingHaiFu', '回掉(我们平台订单号为空!)');
            exit;
        }

        $fee = isset($response['fee']) ? $response['fee'] : 0;
        if ($fee === 0) {
            $this->logdump('beiJingHaiFu', '回掉(订单金额为0!)');
            exit;
        }
        $opUserId = isset($response['opUserId']) ? $response['opUserId'] : '';
        if (empty($opUserId)) {
            $this->logdump('beiJingHaiFu', '回掉(opUserId为空!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");
        if (empty($deposits)) {
            $this->logdump('beiJingHaiFu', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('beiJingHaiFu', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('beiJingHaiFu', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('beiJingHaiFu', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $signSource = strtoupper(sha1("errcode={$errcode}&fee={$fee}&info={$info}&notifyUrl={$notifyUrl}&opUserId={$opUserId}&productId={$ordernumber}&state={$state}&time={$response['time']}&tradeNum={$tradeNum}{$this->_key}"));

        if ($sign == $signSource)//签名正确
        {
            $ok = json_encode(['errcode' => 200, 'info' => '成功接收请求', 'tradeNum' => $tradeNum, 'notifyUrl' => $notifyUrl, 'sign' => $signSource]);
            $this->bak('gs4fj@5f!sda*dfuf', $ok, $deposits, $tradeNum, 'beiJingHaiFu');
        } else {
            $this->logdump('beiJingHaiFu', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}