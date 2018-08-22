<?php
/**
 * 瞬付微信WAP
 */

namespace sscapp\payment;


class shunfuWap extends payModel
{
    private $_merNo = '';//商户号
    private $_NotifyUrl = ''; //异步回掉地址
    private $_frontUrl = '';
    private $_key = '';//商户key
    private $_requestUrl = 'http://trade.595pay.com:8080/api/pay.action';

    public function orderNumber($asd = 0)
    {
        $orderno = "1013".date("YmdHis").rand(1000, 9999);
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

        $this->_merNo = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_NotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_frontUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $service_type = '';
        if ($card_info["netway"] == "WX_WAP") {
            $service_type = "WX_WAP";
        }elseif($card_info["netway"] == "WX"){
            $service_type = "WX";
        }elseif($card_info["netway"] == "QQ"){
            $service_type = "QQ";
        }elseif($card_info["netway"] == "YL"){
            $service_type = "YL";
        }elseif($card_info["netway"] == "JD"){
            $service_type = "JDQB";
        }elseif($card_info["netway"] == "JD_WAP"){
            $service_type = "JDQB_WAP";
        }else{
            $this->_error = '此卡暂不支持该充值方式!'.$service_type;
            return false;
        }
        $dataTmp = array(
            'payNetway' => $service_type,//1
            'amount' => (string)($data['money'] * 100),//1
        );
        $dataTmp['merNo'] = $this->_merNo;//1
        $dataTmp['random'] = (string)rand(1000, 9999);//1
        $dataTmp['orderNo'] = $data['ordernumber'];//1
        $dataTmp['goodsInfo'] = '充值';//1
        $dataTmp['callBackUrl'] = $this->_NotifyUrl;//1
        $dataTmp['callBackViewUrl'] = $this->_frontUrl;//1
        $dataTmp['clientIP'] = $this->getClientIp();//1
        ksort($dataTmp);
        $dataTmp['sign'] = $this->getShunFuSign($dataTmp, $this->_key);
        $postJson = $this->shunfuJsonEncode($dataTmp);
        $postArray = array('data' => $postJson);
        $response = $this->shunfuPost($this->_requestUrl, $postArray);
        if (empty($response)) {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }
        $res = json_decode($response, true);
        if ($res !== null && isset($res['resultCode']) & $res['resultCode'] === '00'){
        $sfurl = $res['qrcodeInfo'];}else{
            $this->_error = '此充值通道异常,请选择其它充值通道充值!';
            $this->logdump('shunfuWap',$response);
            return false;
        }
        if (strstr($card_info["netway"], "WAP") > -1){
            return ['code' => 'url', 'url' => $sfurl];
        }else{
            return ['code' => 'qrcode', 'url' => $sfurl];
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
        $tmpInfo = curl_exec($curl);
        if (curl_errno($curl)) {
            logdump('CURLErrno' . curl_error($curl)); //捕抓异常
        }
        $info = curl_getinfo($curl);
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }
    //生成签名
    public function getShunFuSign($value, $key)
    {
        return strtoupper(md5($this->shunfuJsonEncode($value) . $key));
    }


    public function shunfuJsonEncode($input)
    {
        if (is_string($input)) {
            $text = $input;
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(
                array("\r", "\n", "\t", "\""),
                array('\r', '\n', '\t', '\\"'),
                $text);
            $text = str_replace("\\/", "/", $text);
            return '"' . $text . '"';
        } else if (is_array($input) || is_object($input)) {
            $arr = array();
            $is_obj = is_object($input) || (array_keys($input) !== range(0, count($input) - 1));
            foreach ($input as $k => $v) {
                if ($is_obj) {
                    $arr[] = self::shunfuJsonEncode($k) . ':' . self::shunfuJsonEncode($v);
                } else {
                    $arr[] = self::shunfuJsonEncode($v);
                }
            }
            if ($is_obj) {
                $arr = str_replace("\\/", "/", $arr);
                return '{' . join(',', $arr) . '}';
            } else {
                $arr = str_replace("\\/", "/", $arr);
                return '[' . join(',', $arr) . ']';
            }
        } else {
            $input = str_replace("\\/", "/", $input);
            return $input . '';
        }
    }

    public function shunfuPost($url,$data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);

        if (curl_errno($ch)) {
            return curl_error($ch);
        }

        return $tmpInfo;
    }
    //回调验签
    public function shunfuCheckSign($data, $mer_key)
    {
        $signBak = $data['sign'];
        $newData = array();

        foreach ($data as $key => $value) {
            if ($key !== 'sign') {
                $newData[$key] = $value;
            }
        }

        ksort($newData);
        $sign = strtoupper(md5($this->shunfuJsonEncode($newData) . $mer_key));

        if ($sign == $signBak) {
            return true;
        } else {
            return false;
        }
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
        $response = json_decode($response['data'],true);

        $amount = isset($response['amount']) ? (int)$response['amount'] / 100 : 0;//商户ID
        if ($amount === 0) {
            $this->logdump('shunfuWap', '回掉(金额不正确!)');
            exit;
        }
        $orderNo = isset($response['orderNo']) ? $response['orderNo'] : '';//订单号
        if (empty($orderNo)) {
            $this->logdump('shunfuWap', '回掉(订单号为空!)');
            exit;
        }
        $onlinepayment = \pay::getItemByOrderNumber($orderNo);
        if (empty($onlinepayment)) {
            $this->logdump('shunfuWap', '没有找到订单:' . $orderNo . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$orderNo}'");

        if (empty($deposits)) {
            $this->logdump('shunfuWap', '没有找到订单:' . $orderNo . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('shunfuWap', '没有找到订单:' . $orderNo . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('shunfuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_merNo = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('shunfuWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        if ($this->shunfuCheckSign($response, $card_info['mer_key'])) {
            if ($response['resultCode'] == '00')//签名正确
            {

                $this->bak('gs4fj@5f!sda*dfuf', '000000', $deposits, $orderNo, 'shunfuWap');
            } else {
                $this->logdump('shunfuWap', '回掉(签名验证不通过!请支付小组查看!)');
                exit;
            }
            die('ok');//到不了这里

            // TODO: Implement callback() method.
        }
    }

}