<?php
/**
 * 高通
 * jacky
 */

namespace sscapp\payment;


class gaoTong extends payModel
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


    private $_requestUrl = 'http://wgtj.gaotongpay.com/PayBank.aspx';

    public function orderNumber($asd = 0)
    {
        $orderno = "1032" . date("YmdHis") . rand(1000, 9999);
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

        $this->_p1_mchtid = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_p5_callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_p6_notifyurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "WX") {
            $service_type = "WEIXIN";
        } elseif($card_info["netway"] == "ZFB") {
            $service_type = "ALIPAY";
        } elseif($card_info["netway"] == "QQ") {
            $service_type = "QQPAY";
        } elseif($card_info["netway"] == "WX_WAP") {
            $service_type = "WEIXINWAP";
        } elseif($card_info["netway"] == "ZFB_WAP") {
            $service_type = "ALIPAYWAP";
        } elseif($card_info["netway"] == "QQ_WAP") {
            $service_type = "QQPAYWAP";
        } elseif($card_info["netway"] == "JD") {
            $service_type = "JDPAY";
        } elseif($card_info["netway"] == "JD_WAP") {
            $service_type = "JDPAYWAP";
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $dataTmp = array(
            'partner' => $this->_p1_mchtid,//
            'banktype' => $service_type,//
            'paymoney' => $data['money'],//
        );
        $dataTmp['ordernumber'] = $data['ordernumber'];//
        $dataTmp['callbackurl'] = $this->_p5_callbackurl;//
        $dataTmp['attach'] = '';//
        $dataTmp['sign'] = $this->gaotongSign($dataTmp, $this->_key);//
        $postUrl = $this->_requestUrl . '?' . http_build_query($dataTmp);//
        if($data['area_flag'] == 1) {
            $str = <<<HTML
<!DOCTYPE html>  
<html>  
<head>  
    <meta charset="utf-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, target-densitydpi=device-dpi" />
</head>  
<body>  
<div style="display: flex;justify-content: center;align-items: center;min-height: 500px">
    <button id="diy" style="display:block;width:80%;height:50px;margin: 50px auto;font-size: 24px;line-height: 50px;">请点击继续支付</button>
</div>
</body>
<script>
    document.getElementById("diy").onclick=function () {
        location.href="$postUrl"
    };
</script>
</body>  
</html>  
HTML;


            return ['code' => 'html', 'html' => $str];
        }else{
            return ['code' => 'url', 'url' => $postUrl];
        }

    }

   public function gaotongSign($array, $mer_key)
   {
       $url = '';

       foreach ($array as $key => $v) {
           if ($key !== 'hrefbackurl' and $key !== 'attach') {    #hrefbackurl 不参与签名
               $url = $url . $key . '=' . $v . '&';
           }
       }
       $url = substr($url, 0, strlen($url) - 1) . $mer_key;
       return md5($url);
   }


    public function gaotongCheckSign($data, $mer_key)
    {
        $sign_str = 'partner=' . $data['partner']  . '&ordernumber=' . $data['ordernumber'] . '&orderstatus=' . $data['orderstatus'] . '&paymoney='  . $data['paymoney']  . $mer_key;
        return md5($sign_str) == $data['sign'];
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
            $this->logdump('gaoTong', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $partner = isset($response['partner']) ? $response['partner'] : '';//商户ID
        if (empty($partner)) {
            $this->logdump('gaoTong', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['ordernumber']) ? $response['ordernumber'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('gaoTong', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['orderstatus']) ? $response['orderstatus'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('gaoTong', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['paymoney']) ? $response['paymoney'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('gaoTong', '回掉-付款金额为空');
            exit;
        }
        $sysnumber = isset($response['sysnumber']) ? $response['sysnumber'] : '';//月宝订单号
        if (empty($sysnumber)) {
            $this->logdump('gaoTong', '回掉(sysnumber为空!)');
            exit;
        }

        $attach = isset($response['attach']) ? $response['attach'] : '';//月宝订单号

        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('gaoTong', '回掉(签名为空!)');
            exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($ordernumber);
        if (empty($onlinepayment)) {
            $this->logdump('gaoTong', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('gaoTong', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('gaoTong', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('gaoTong', '配置有误,请联系客服!');
            exit;
        }
        $this->_p1_mchtid = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('gaoTong', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];

        if ($this->gaotongCheckSign($response, $this->_key) && $response['orderstatus'] == '1')//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'ok', $deposits, $ordernumber, 'gaoTong');
        } else {
            $this->logdump('gaoTong', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}