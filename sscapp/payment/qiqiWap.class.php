<?php
/**
 * 柒柒QQWAP
 * Jacky
 */

namespace sscapp\payment;


class qiqiWap extends payModel
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


    private $_requestUrl = 'http://www.777-pay.com/Pay/df_pay.aspx';

    public function orderNumber($asd = 0)
    {
        $orderno = "1027" . date("YmdHis") . rand(1000, 9999);
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
        if ($card_info["netway"] == "QQ_WAP") {
            $service_type = "3";
        } elseif ($card_info["netway"] == "WX") {
            $service_type = "1";
        } elseif ($card_info["netway"] == "ZFB") {
            $service_type = "2";
        }elseif ($card_info["netway"] == "QQ") {
            $service_type = "3";
        }elseif ($card_info["netway"] == "JD") {
            $service_type = "4";
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $dataTmp = array(
            'UId' => $this->_p1_mchtid,//
            'Type' => $service_type,//
            'Amount' => $data['money'],//
        );
        $dataTmp['Sh_OrderNo'] = $data['ordernumber'];//
        $dataTmp['Msg'] = $data['ordernumber'];//
        $dataTmp['ip'] = $this->getClientIp();//
        $body = "Amount=" . $dataTmp['Amount'];
        $body .= "&Msg=" . $dataTmp['Msg'];
        $body .= "&Sh_OrderNo=" . $dataTmp['Sh_OrderNo'];
        $body .= "&Type=" . $dataTmp['Type'];
        $body .= "&UId=" . $dataTmp['UId'];
        $signkey = $body . "&key=" . $this->_key;
        $dataTmp['sign'] = strtoupper(md5($signkey));//
        $info = array();
        $response = $this->curlPostData($this->_requestUrl, http_build_query($dataTmp), $info);
        $response = json_decode($response, true);

        if ($response != null && isset($response['Qrcode'])){
            if (strstr($card_info["netway"], "WAP") > -1){
                return ['code' => 'url', 'url' => $response['Qrcode']];
            }else {
                return ['code' => 'qrcode', 'url' => $response['Qrcode']];
            }
        }else{
            $this->_error = '返回值为空!';
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
            $this->logdump('qiqiWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $Msg = isset($response['Msg']) ? $response['Msg'] : '';//商户ID
        if (empty($Msg)) {
            $this->logdump('qiqiWap', '回掉(Msg为空!)');
            exit;
        }
        $OrderNo = isset($response['OrderNo']) ? $response['OrderNo'] : '';//订单号
        if (empty($OrderNo)) {
            $this->logdump('qiqiWap', '回掉(订单号为空!)');
            exit;
        }
        $TimeEnd = isset($response['TimeEnd']) ? $response['TimeEnd'] : '';//1:支付成功，非1为支付失败

        $OrderAmount = isset($response['OrderAmount']) ? $response['OrderAmount'] : 0;//订单金额 单位元（人民币）
        if ($OrderAmount === 0) {
            $this->logdump('qiqiWap', '回掉-付款金额为空');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';//月宝订单号
        if (empty($sign)) {
            $this->logdump('qiqiWap', '回掉(签名类型为空!)');
            exit;
        }

        $onlinepayment = \pay::getItemByOrderNumber($Msg);
        if (empty($onlinepayment)) {
            $this->logdump('qiqiWap', '没有找到订单:' . $Msg . '的在线支付信息');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$Msg}'");

        if (empty($deposits)) {
            $this->logdump('qiqiWap', '没有找到订单:' . $Msg . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($onlinepayment['card_id']);
        if (empty($card_info)) {
            $this->logdump('qiqiWap', '没有找到订单:' . $Msg . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('qiqiWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_p1_mchtid = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('qiqiWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $body = "Msg=".$Msg;
        $body .= "&OrderAmount=".$OrderAmount;
        $body .= "&OrderNo=".$OrderNo;
        $body .= "&TimeEnd=".$TimeEnd;
        $signkey = $body."&key=".$this->_key;
        $newSign = strtoupper(md5($signkey));
        if ($sign == $newSign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', '0000', $deposits, $Msg, 'qiqiWap');
        } else {
            $this->logdump('qiqiWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }

}