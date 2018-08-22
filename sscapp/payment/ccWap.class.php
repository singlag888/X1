<?php

namespace sscapp\payment;

class ccWap extends payModel
{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_returnurl = '';//商户key
    private $_requestUrl = 'http://a.cc8pay.com/api/wapPay';//第三方请求地址

    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        return '1006' . $this->randomkeys(6) . time() . $this->randomkeys(8);
    }

    /**
     * 生成随机
     * @param string $length 长度
     * @return string $key 返回的值
     * @author L
     */
    function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = '';
        for ($i = 0; $i < $length; ++$i) {
            $key .= $pattern{mt_rand(0, 35)};    //生成php随机数
        }
        return $key;
    }

    /**
     * 顺序排例
     * @param array $data
     * @return string $str
     * @author L
     */
    public function tsSign($data){
        ksort($data);
        $str = '';
        foreach ($data as $key => $value){
            if(!empty($value) && $key != 'signature'){
                $str .=  $key . "=" .$value . "&";
            }
        }
        return $str;
    }

    /**
     * 长城curlPost
     * @param  string $url 接口地址
     * @param  array $data 传输数据
     * @param  array $info 空数组
     * @return json mixed 返回的json
     */
    public function tsCurlPost($url, $data,&$info)
    {   foreach ($data as $k => $v){
        $dataGbk[iconv('UTF-8','GBK',$k)] =  iconv('UTF-8','GBK',$v);
    }
        // 模拟提交数据函数
        $header[] = "Content-type: application/x-www-form-urlencoded;charset=GBK";
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //
        curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
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
     * 支付方法
     * @param array $data 需要的数据
     * @return array|bool $url 跳转链接
     * @author L
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
        $this->_partner = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $this->_callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_returnurl = 'http://' . $_SERVER['HTTP_HOST'];
        $service_type = '';
        if ($card_info['netway'] == 'WX_WAP') {
            $service_type = "2";
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $service_type = '1';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $service_type = '4';
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $postData['traceno'] = $data['ordernumber'];//订单号
        $postData['merchno'] = $card_info['mer_no'];//商户号
        $postData['amount'] = $data['money'];//支付金额
        $postData['goodsName'] = 'chongzhi';//订单创建时间
        $postData['remark'] = 'chongzhi';//*
        $postData['notifyUrl'] = $this->_callbackurl;//*
        $postData['settleType'] = '1';//*
        $postData['payType'] = $service_type;//*
        $postData['returnUrl'] = $this->_returnurl;
        $postData['signature'] = md5($this->tsSign($postData).$this->_key);
        $info = array();
        if ($postData['amount']<'1000'){
            $this->_error = '充值金额低于1000块';
            return false;
        }
        $res = $this->tsCurlPost($this->_requestUrl, $postData, $info);
        if (empty($res)) {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }
        $res = iconv('GBK','UTF-8',$res);
        if (is_array(json_decode($res,true))) {
            $arrBack = json_decode($res,true);
            if ($arrBack['respCode'] !== '00') {
                $this->_error = '支付通道维护,请选择其他充值';
                $this->logdump('ccWap', $arrBack['message']);
                return false;
            }
        }
        $url = isset($arrBack['barCode'])?$arrBack['barCode']:'';
        if (!$url) {
            $this->_error = '此充值通道异常,请选择其它充值通道充值!';
            $this->logdump('ccWap', $res);
            return false;
        }
        return ['code' => 'url', 'url' => isset($url) ? $url : ''];
    }

    public function callback($response = [], $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('ccWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $partner = isset($response['merchno']) ? $response['merchno'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('ccWap', '回掉(商户ID不正确!)');
            exit;
        }
        $ordernumber = isset($response['traceno']) ? $response['traceno'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('ccWap', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['status']) ? $response['status'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('ccWap', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['amount']) ? $response['amount'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('ccWap', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['orderno']) ? $response['orderno'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('ccWap', '回掉(泽圣订单号为空!)');
            exit;
        }
        $sign = isset($response['signature']) ? $response['signature'] : '';
        if (empty($sign)) {
            $this->logdump('ccWap', '回掉(签名不正确!)');
            exit;
        }
        $transTime = isset($response['transTime']) ? $response['transTime'] : 0;
        if (empty($transTime)) {
            $this->logdump('ccWap', '回掉(发送时间不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            $this->logdump('ccWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('ccWap', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('ccWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('ccWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $sign = md5($this->tsSign($response).$this->_key);

        if ($response['signature'] == $sign)//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'SUCCESS', $deposits, $sysnumber, 'ccWap');
        } else {
            $this->logdump('ccWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }
}