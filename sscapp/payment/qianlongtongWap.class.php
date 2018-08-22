<?php
/**
 * 乾隆通
 * jacky
 */

namespace sscapp\payment;


class qianlongtongWap extends payModel
{
    private $_p1_mchtid = '';//商户号
    private $_key = '';//商户key
    private $_p5_callbackurl = ''; //异步回掉地址
    private $_p6_notifyurl = '';
    private $_p7_version = 'v2.8';
    private $_p8_signtype = 1;
    private $_p9_attach = '';
    private $_p10_appname = '';
    private $_p11_isshow = 0;


    private $_requestUrl = 'http://pay.qianlongt.com/Pay.html';

    public function orderNumber($asd = 0)
    {
        $orderno = "1026" . date("YmdHis") . rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

    /**
     * 生成签名
     */
    public function bscreateSign($params)
    {
        $signText = 'MerchantCode=[' . $params['MerchantCode'] . ']OrderId=[' . $params['OrderId'] . ']Amount=[' . $params['Amount'] . ']NotifyUrl=[' . $params['NotifyUrl'] . ']OrderDate=[' . $params['OrderDate'] . ']BankCode=[' . $params['BankCode'] . ']TokenKey=[' . $params["key"] . ']';
        return strtoupper(md5($signText));
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
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }

        $this->_p1_mchtid = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_p5_callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_p6_notifyurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "QQ") {
            $service_type = "3";
        } elseif($card_info["netway"] == "QQ_WAP") {
            $service_type = "6";
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $dataTmp = array(
            'appid' => $this->_p1_mchtid,//
            'paytype' => $service_type,//
            'paymoney' => $data['money'],//
        );
        $dataTmp['ordernumber'] = $data['ordernumber'];//
        $dataTmp['callbackurl'] = $this->_p5_callbackurl;//
        $signSource = sprintf("appid=%s&paytype=%s&paymoney=%s&ordernumber=%s&callbackurl=%s%s", $dataTmp['appid'], $dataTmp['paytype'], $dataTmp['paymoney'], $dataTmp['ordernumber'], $dataTmp['callbackurl'], $this->_key);//
        $dataTmp['sign'] = md5($signSource);//
        $postUrl = $this->_requestUrl . "?paytype=" . $dataTmp['paytype'];
        $postUrl .= "&appid=" . $dataTmp['appid'];
        $postUrl .= "&paymoney=" . $dataTmp['paymoney'];
        $postUrl .= "&ordernumber=" . $dataTmp['ordernumber'];
        $postUrl .= "&callbackurl=" . $dataTmp['callbackurl'];
        $postUrl .= "&sign=" . $dataTmp['sign'];
        $str = <<<HTML
<button id="diy">如果没有自动跳转，请点击按钮</button>
</body>
<script>
    document.getElementById("diy").onclick=function () {
        location.href="$postUrl"
    };
    // document.getElementById("diy").click();
</script>
HTML;
        return ['code' => 'html', 'html' => $str];

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
            $this->logdump('qianlongtongWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $appid = isset($response['appid']) ? $response['appid'] : '';//商户ID
        if (empty($appid)) {
            $this->logdump('qianlongtongWap', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['ordernumber']) ? $response['ordernumber'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('qianlongtongWap', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['orderstatus']) ? $response['orderstatus'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('qianlongtongWap', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['paymoney']) ? $response['paymoney'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('qianlongtongWap', '回掉-付款金额为空');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : 0;//月宝订单号
        if ($sign === 0) {
            $this->logdump('qianlongtongWap', '回掉(签名类型为空!)');
            exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($ordernumber);
        if (empty($onlinepayment)) {
            $this->logdump('qianlongtongWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('qianlongtongWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('qianlongtongWap', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('qianlongtongWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_p1_mchtid = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('qianlongtongWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $signSource = sprintf("appid=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s", $appid, $ordernumber, $orderstatus, $paymoney, $this->_key);
        if ($sign == md5($signSource))//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'success', $deposits, $ordernumber, 'qianlongtongWap');
        } else {
            $this->logdump('qianlongtongWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}