<?php
/**
 * 雅付QQ和微信WAP
 *
 * @author jacky

 */


namespace sscapp\payment;


class yafuWap extends payModel
{

    private $_merchno = '';//商户ID1
    private $_notifyUrl = ''; //异步回掉地址1
    private $_key = '';//商户key
    private $_requestUrl = 'http://yf.yafupay.com/yfpay/cs/pay.ac';//第三方请求地址1
    private $_sameBackUrl = '';

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1018' . date('YmdHis') . rand(100, 999) . rand(100, 999) . rand(1, 9);
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
            $service_type = "0901";
        }elseif ($netway == "QQ"){
            $service_type = "0502";
        }elseif ($netway == "ZFB_WAP"){
            $service_type = "0303";
        }elseif ($netway == "YL"){
            $service_type = "0702";
        }elseif ($netway == "QQ_WAP"){
            $service_type = "0503";
        } else {
            $this->_error = '此卡暂不支持该充值方式!';
            return false;
        }
        $dataTmp['version'] = "3.0";//
        $dataTmp['consumerNo'] = $this->_merchno;//
        $dataTmp['merOrderNo'] = $data['ordernumber'];//
        $dataTmp['transAmt'] = $data['money'];//
        $dataTmp['backUrl'] = $this->_notifyUrl;//
        $dataTmp['frontUrl'] = $this->_sameBackUrl;//
        $dataTmp['merRemark'] = 'thisisotherthings';//
        $dataTmp['payType'] = $service_type;//
        $dataTmp['goodsName'] = "lucky";//
        $dataTmp['sign'] = $this->yfsignstr($dataTmp,$this->_key);//
        if ($netway == "WX_WAP") {
            $resHtml = <<< html
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>跳转中</title>
</head>
<body >
<form name="diy" id="diy" action="$this->_requestUrl"  method="post">
    <input type="hidden" name="version" value="{$dataTmp['version']}">
    <input type="hidden" name="consumerNo" value="{$dataTmp['consumerNo']}">
    <input type="hidden" name="merOrderNo" value="{$dataTmp['merOrderNo']}">
    <input type="hidden" name="transAmt" value="{$dataTmp['transAmt']}">
    <input type="hidden" name="backUrl" value="{$dataTmp['backUrl']}">
    <input type="hidden" name="merRemark" value="{$dataTmp['merRemark']}">
    <input type="hidden" name="frontUrl" value="{$dataTmp['frontUrl']}">
    <input type="hidden" name="payType" value="{$dataTmp['payType']}">
    <input type="hidden" name="goodsName" value="{$dataTmp['goodsName']}">
    <input type="hidden" name="sign" value="{$dataTmp['sign']}">
</form>
<script>
window.setTimeout(function() {
document.getElementById("diy").submit();
},500)
</script>
</body></html>
html;
            $this->logdump('yfHtml',$resHtml);
            return ['code' => 'html', 'html' => $resHtml];
        }else{
            $info = array();
            $jsonResponse = $this->curlPostData($this->_requestUrl, http_build_query($dataTmp), $info);//
            if (!isset($jsonResponse)) {
                $this->_error = '第三方返回值为空!';
                $this->logdump('yfPay',$dataTmp);
                return false;
            }
            if (!(strstr($netway, "WAP") > -1)) {
                $yfarray = json_decode($jsonResponse, true);
                if (!isset($yfarray['busContent'])) {
                    $this->_error = $yfarray['msg'];
                    return false;
                }
                return ['code' => 'qrcode', 'url' => $yfarray['busContent']];
            }
            return ['code' => 'html', 'html' => $jsonResponse];

        }
        // TODO: Implement run() method.
    }

    public function yfsignstr($paramers,$md5Key){
        ksort($paramers);

        //组装签名串
        $signstr = '';
        foreach ($paramers as $key=>$param){
            if(!empty($param))
                $signstr .= $key.'='.$param.'&';
        }

        //加上签名KEY
        $signstr .= 'key='.$md5Key;
        $yfsign = md5($signstr);
        return $yfsign;
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
            $this->logdump('yafuWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
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
        unset($response["merRemark"]);

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

        if ($sign == $callsign && $orderStatus == '1')//签名正确
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