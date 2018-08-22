<?php
/**
 * 科讯QQ和QQWAP
 * 充值下限10元
 * @author jacky
 */


namespace sscapp\payment;


class yuebaoWAP extends payModel
{

    private $_merchno = '';//商户ID1
    private $_notifyUrl = ''; //异步回掉地址1
    private $_key = '';//商户key
    private $_requestUrl = 'http://pay.kexunpay.com/ChargeBank.aspx';//第三方请求地址1
    private $_sameBackUrl = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1021' . date('YmdHis') . rand(100, 999) . rand(100, 999) . rand(1, 9);
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
        if ($netway == "KJ_WAP") {
            $service_type = "SHORTCUTWAP";
        } else {
            $this->_error = '此卡暂不支持该充值方式!';
            return false;
        }
        $dataTmp['version'] = "3.0";//
        $dataTmp['method'] = "Yb.online.interface";//
        $dataTmp['partner'] = $this->_merchno;//
        $dataTmp['banktype'] = $service_type;//
        $dataTmp['paymoney'] = $data['money'];//
        $dataTmp['ordernumber'] = $data['ordernumber'];//
        $dataTmp['callbackurl'] = $this->_notifyUrl;//
        $signSource = sprintf("version=%s&method=%s&partner=%s&banktype=%s&paymoney=%s&ordernumber=%s&callbackurl=%s%s", $dataTmp['version'],$dataTmp['method'],$dataTmp['partner'], $dataTmp['banktype'], $dataTmp['paymoney'], $dataTmp['ordernumber'], $dataTmp['callbackurl'], $this->_key);//
        $sign = md5($signSource);//
        $postUrl = $this->_requestUrl. "?version=".$dataTmp['version'];
        $postUrl.="&method=".$dataTmp['method'];
        $postUrl.="&partner=".$dataTmp['partner'];
        $postUrl.="&banktype=".$dataTmp['banktype'];
        $postUrl.="&paymoney=".$dataTmp['paymoney'];
        $postUrl.="&ordernumber=".$dataTmp['ordernumber'];
        $postUrl.="&callbackurl=".$dataTmp['callbackurl'];
        $postUrl.="&sign=".$sign;//

        return ['code' => 'url', 'url' => $postUrl];
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
            $this->logdump('changchengfu', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
        $version = isset($response['version']) ? $response['version'] : '';//返回的状态码
        if (empty($version)) {
            $this->logdump('yafuWap', '回掉version为空:' . $response);
            exit;
        }
        $transAmt = isset($response['transAmt']) ? $response['transAmt'] : 0;//订单号
        if ($transAmt == 0) {
            $this->logdump('yafuWap', '没有反回支付金额');
            exit;
        }

        $consumerNo = isset($response['consumerNo']) ? $response['consumerNo'] : '';//订单号
        if (empty($consumerNo)) {
            $this->logdump('yafuWap', '没有反回consumerNo信息');
            exit;
        }
        $merOrderNo = isset($response['merOrderNo']) ? $response['merOrderNo'] : '';
        if (empty($merOrderNo)) {
            $this->logdump('yafuWap', '没有返回merOrderNo');
            exit;
        }

        $orderNo = isset($response['orderNo']) ? $response['orderNo'] : '';//1:支付成功，非1为支付失败
        if (empty($orderNo)) {
            $this->logdump('yafuWap', '回掉(orderNo为空)');
            exit;
        }
        $orderStatus = isset($response['orderStatus']) ? $response['orderStatus'] : '';//订单金额 单位元（人民币）
        if ( empty($orderStatus)) {
            $this->logdump('yafuWap', '回掉(orderStatus为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('yafuWap', '回掉(sign为空!)');
            exit;
        }

        $payType = isset($response['payType']) ? $response['payType'] : '';
        if (empty($payType)) {
            $this->logdump('yafuWap', '回掉(payType为空!)');
            exit;
        }
        unset($response["sign"]);

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$merOrderNo}'");
        if (empty($deposits)) {
            $this->logdump('yafuWap', '没有找到订单:' . $merOrderNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('yafuWap', '没有找到订单:' . $merOrderNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('yafuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_merchno = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('yafuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];

        $callsign = strtoupper($this->yfsignstr($response,$this->_key));

        if ($sign == $callsign)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $merOrderNo, 'yafuWap');
        } else {
            $this->logdump('yafuWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}