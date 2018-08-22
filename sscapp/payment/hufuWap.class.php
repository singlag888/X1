<?php
/**
 * 琥付微信,QQ,支付宝和支付宝WAP
 */

namespace sscapp\payment;


class hufuWap extends payModel
{
    private $_parter = '';//商户号1
    private $_callbackurl = ''; //异步回掉地址1
    private $_hrefbackurl = '';//1
    private $_key = '';//商户key
    private $_requestUrl = 'http://pay.hufupay1.com/bank/';

    public function orderNumber($asd = 0)
    {
        $orderno = "1014".date("YmdHis").rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

    /**
     * 生成签名
     */


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
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }

        $this->_parter = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_hrefbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "WX") {
            $service_type = "1004";
        }elseif($card_info["netway"] == "QQ") {
            $service_type = "1009";
        }elseif($card_info["netway"] == "ZFB") {
            $service_type = "1003";
        }elseif($card_info["netway"] == "ZFB_WAP") {
            $service_type = "1006";
        }else{
            $this->_error = '此卡暂不支持该充值方式!'.$service_type;
            return false;
        }
        $dataTmp = array(
            'type' => $service_type,//
            'value' => $data['money'],//
        );
        $dataTmp['parter'] = $this->_parter;//
        $dataTmp['random'] = (string)rand(1000, 9999);
        $dataTmp['orderid'] = $data['ordernumber'];//
        $dataTmp['goodsInfo'] = '充值';
        $dataTmp['callbackurl'] = $this->_callbackurl;//
        $dataTmp['hrefbackurl'] = $this->_hrefbackurl;//
        $dataTmp['sign'] = md5("parter={$dataTmp['parter']}&type={$dataTmp['type']}&value={$dataTmp['value']}&orderid={$dataTmp['orderid']}&callbackurl={$dataTmp['callbackurl']}{$this->_key}");//

        $hfurl = $this->_requestUrl."?parter=".$dataTmp['parter']."&type=".$dataTmp['type']."&value=".$dataTmp['value']."&orderid=".$dataTmp['orderid']."&callbackurl=".$dataTmp['callbackurl']."&hrefbackurl=".$dataTmp['hrefbackurl']."&sign=".$dataTmp['sign'];

        return ['code' => 'url', 'url' => $hfurl];
    }

    /**
     * 支付回掉入口
     * 1.回掉验签
     * 2.执行本地逻辑
     * @param array $response 第三方回掉返回数据
     * @param int $is_raw 返回数据结构形式 主要表示是否是原始数据流 0不是 1是
     */
    public function callback($response = [], $is_raw = 0)
    {
        if (empty($response)) {
            $this->logdump('shunfuWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $orderid = isset($response['orderid']) ? $response['orderid']: '';//商户ID
        if (empty($orderid)) {
            $this->logdump('hufuWap', '回掉(订单号为空!)');
            exit;
        }
        $opstate = isset($response['opstate']) ? $response['opstate'] : '';//订单号
        if (!isset($opstate)) {
            $this->logdump('hufuWap', '回掉(状态值为空!)');
            exit;
        }
        $ovalue = isset($response['ovalue']) ? $response['ovalue'] : 0;//订单号
        if (empty($ovalue)) {
            $this->logdump('hufuWap', '回掉(金额为空!)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//订单号
        if (empty($sign)) {
            $this->logdump('hufuWap', '回掉(签名为空!)');
            exit;
        }
        $onlinepayment = \pay::getItemByOrderNumber($orderid);
        if (empty($onlinepayment)) {
            $this->logdump('hufuWap', '没有找到订单:' . $orderid . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$orderid}'");

        if (empty($deposits)) {
            $this->logdump('hufuWap', '没有找到订单:' . $orderid . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('hufuWap', '没有找到订单:' . $orderid . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('hufuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_parter = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('hufuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $callsign = md5("orderid={$orderid}&opstate={$opstate}&ovalue={$ovalue}{$this->_key}");
            if ($callsign == $sign)//签名正确
            {

                $this->bak('gs4fj@5f!sda*dfuf', 'opstate=0', $deposits, $orderid, 'hufuWap');
            } else {
                $this->logdump('hufuWap', '回掉(签名验证不通过!请支付小组查看!)');
                exit;
            }
            die('ok');//到不了这里

            // TODO: Implement callback() method.
        }
}