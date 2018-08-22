<?php
/**
 * 轻易付
 * jacky
 */

namespace sscapp\payment;


class qingyifuWap extends payModel
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

    private $_requestUrl = 'http://wxwap.qyfpay.com:90/api/pay.action';
    private $_requestUrl1 = 'http://qq.qyfpay.com:90/api/pay.action';

    public function orderNumber($asd = 0)
    {
        $orderno = "1025" . date("YmdHis") . rand(1000, 9999);
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
        if ($card_info["netway"] == "WX_WAP") {
            $service_type = "WX_WAP";
            $apiUrl = $this->_requestUrl;
        } elseif($card_info["netway"] == "QQ") {
            $service_type = "QQ";
            $apiUrl = $this->_requestUrl1;
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $dataTmp = array(
            'merNo' => $this->_p1_mchtid,//
            'netway' => $service_type,//
            'amount' => strval($data['money'] * 100),//
        );
        $dataTmp['version'] = 'V2.0.0.0';//
        $dataTmp['random'] = strval(rand(1000, 9999));//
        $dataTmp['orderNum'] = $data['ordernumber'];//
        $dataTmp['goodsName'] = 'lucky';//
        $dataTmp['charset'] = 'utf-8';//
        $dataTmp['callBackUrl'] = $this->_p5_callbackurl;//
        $dataTmp['callBackViewUrl'] = $this->_p6_notifyurl;//
        ksort($dataTmp);
        $dataTmp['sign'] = strtoupper(md5($this->qingyifuJsonEncode($dataTmp).$this->_key));//
        $data = $this->qingyifuJsonEncode($dataTmp);//
        $post = array('data'=>$data);
        $info = array();
        $jsonResponse = $this->curlPostData($apiUrl, $post, $info);//
        $response = json_decode($jsonResponse, true);
        if (!isset($response['qrcodeUrl'])) {
            $this->_error = $response['msg'];
            return false;
        }
        if ($response != null && isset($response['stateCode']) && $response['stateCode'] == '00'){
            if ($this->checkQingYiFuSign($response, $this->_key)){
                if (strstr($card_info["netway"], "WAP") > -1){
                    return ['code' => 'url', 'url' => $response['qrcodeUrl']];
                }else{
                    return ['code' => 'qrcode', 'url' => $response['qrcodeUrl']];
                }

            }

        }else{
            $this->_error = $response['msg'];
            return false;
        }


    }

    public function qingyifuJsonEncode($input){
        if(is_string($input)){
            $text = $input;
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(
                array("\r", "\n", "\t", "\""),
                array('\r', '\n', '\t', '\\"'),
                $text);
            $text = str_replace("\\/", "/", $text);
            return '"' . $text . '"';
        }else if(is_array($input) || is_object($input)){
            $arr = array();
            $is_obj = is_object($input) || (array_keys($input) !== range(0, count($input) - 1));
            foreach($input as $k=>$v){
                if($is_obj){
                    $arr[] = $this->qingyifuJsonEncode($k) . ':' . $this->qingyifuJsonEncode($v);
                }else{
                    $arr[] = $this->qingyifuJsonEncode($v);
                }
            }
            if($is_obj){
                $arr = str_replace("\\/", "/", $arr);
                return '{' . join(',', $arr) . '}';
            }else{
                $arr = str_replace("\\/", "/", $arr);
                return '[' . join(',', $arr) . ']';
            }
        }else{
            $input = str_replace("\\/", "/", $input);
            return $input . '';
        }
    }

    public function checkQingYiFuSign($row, $signKey) {
        $r_sign = $row['sign'];
        $arr = array();

        foreach ($row as $key=>$v){
            if ($key !== 'sign'){
                $arr[$key] = $v;
            }
        }

        ksort($arr);

        $sign = strtoupper(md5($this->qingyifuJsonEncode($arr) . $signKey));
        if ($sign == $r_sign){
            return true;
        }else{
            return false;
        }

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
    public function callback($response = [], $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('qingyifuWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);

        }
        $response = json_decode($response['data'],true);

        $amount = isset($response['amount']) ? ((int)$response["amount"]) / 100 : 0;//商户ID
        if ($amount === 0) {
            $this->logdump('qingyifuWap', '回掉(amount)为空!');
            exit;
        }
        $goodsName = isset($response['goodsName']) ? $response['goodsName'] : '';//订单号
        if (empty($goodsName)) {
            $this->logdump('qingyifuWap', '回掉(goodsName为空!)');
            exit;
        }
        $merNo = isset($response['merNo']) ? $response['merNo'] : '';//1:支付成功，非1为支付失败
        if (empty($merNo)) {
            $this->logdump('qingyifuWap', '回掉(merNo为空)');
            exit;
        }

        $netway = isset($response['netway']) ? $response['netway'] : '';//订单金额 单位元（人民币）
        if (empty($netway)) {
            $this->logdump('qingyifuWap', '回掉-netway为空');
            exit;
        }
        $orderNum = isset($response['orderNum']) ? $response['orderNum'] : '';//月宝订单号
        if (empty($orderNum)) {
            $this->logdump('qingyifuWap', '回掉(orderNum为空!)');
            exit;
        }

        $payDate = isset($response['payDate']) ? $response['payDate'] : '';//月宝订单号
        if (empty($payDate)) {
            $this->logdump('qingyifuWap', '回掉(payDate为空!)');
            exit;
        }

        $payResult = isset($response['payResult']) ? $response['payResult'] : '';//月宝订单号
        if (empty($payResult)) {
            $this->logdump('qingyifuWap', '回掉(payResult为空!)');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('qingyifuWap', '回掉(sign为空!)');
            exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($orderNum);
        if (empty($onlinepayment)) {
            $this->logdump('qingyifuWap', '没有找到订单:' . $orderNum . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$orderNum}'");

        if (empty($deposits)) {
            $this->logdump('qingyifuWap', '没有找到订单:' . $orderNum . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('qingyifuWap', '没有找到订单:' . $orderNum . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('qingyifuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_p1_mchtid = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('qingyifuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        if ($this->checkQingYiFuSign($response, $this->_key) && $response['payResult'] == '00')//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', '0', $deposits, $orderNum, 'qingyifuWap');
        } else {
            $this->logdump('qingyifuWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}