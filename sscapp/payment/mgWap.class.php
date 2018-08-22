<?php
/**
 * 芒果支付
 * User: L
 * Date: 2017/12/2
 * Time: 16:19
 */

namespace sscapp\payment;


class mgWap extends payModel
{
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_sameBackUrl = '';//商户key
    private $_requestUrl = 'http://www.magopay.net/api/trans/pay';//第三方请求地址

    /**
     * 生成订单号
     * @param int $asd 状态码
     * @return string $number 返回的订单号
     * @author L
     */
    public function orderNumber($asd = 0)
    {
        return '1010' . $this->randomkeys(6) . time() . $this->randomkeys(8);
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

    /** 公钥格式转换
     * @param $key
     * @param string $type
     * @return string
     * @author L
     */
    public function formatKey($key, $type = 'public'){
        $key = str_replace("-----BEGIN PRIVATE KEY-----", "", $key);
        $key = str_replace("-----END PRIVATE KEY-----", "", $key);
        $key = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        $key = str_replace("-----END PUBLIC KEY-----", "", $key);
        $key = $this->trimAll($key);

        if ($type == 'public') {
            $begin = "-----BEGIN PUBLIC KEY-----\n";
            $end = "-----END PUBLIC KEY-----";
        } else {
            $begin = "-----BEGIN PRIVATE KEY-----\n";
            $end = "-----END PRIVATE KEY-----";
        }

        $key = chunk_split($key, 64, "\n");

        return $begin . $key . $end;
    }

    public function mgCurlPost($url, $data)
    {
        try{
            $header[] = "Content-type: application/x-www-form-urlencoded;charset=UTF-8";
            $ch = curl_init ();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            //dd(http_build_query($data));
            $result = curl_exec($ch);
            curl_close($ch);
            //dd($ch);
            return json_decode($result, true);
        }catch(\Exception $e){
            return ['code'=>'9999', 'msg'=>'接口链接失败'];
        }
    }
    function trimAll($str)
    {
        $qian = array(" ", "　", "\t", "\n", "\r");
        $hou = array("", "", "", "", "");
        return str_replace($qian, $hou, $str);
    }

    public function encrypt($params, $path)
    {
        $originalData = json_encode($params);
        $crypto = '';
        $encryptData = '';
        $rsaPublicKey = $path;
        foreach (str_split($originalData, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $rsaPublicKey);
            $crypto .= $encryptData;
        }
        return base64_encode($crypto);
    }
    /**
     * 生成内部签名
     * @access public
     * @param array $post  数据
     * @param string $secretKey  密钥
     * @return Driver
     * @author L
     * @date 2017-12-02
     * @time 16:45:29
     */
    public function makeInSign($post, $secretKey)
    {
        $noarr = ['sign','code','msg'];
        ksort($post);
        $data = "";
        foreach ($post as $key => $val) {
            if ( !in_array($key, $noarr) && (!empty($val) || $val ===0 || $val ==='0') ) {
                $data .= $key . '=' . $val . '&';
            }
        }
        $data .= 'key='.$secretKey;
        return strtolower(md5($data));
    }
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
        $area_flag = isset($data['area_flag']) ? $data['area_flag'] : 0;
        $protocol = $this->getProtocol($area_flag);
        $this->_callbackurl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $this->_sameBackUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $this->logdump('mgWapUrl', $_SERVER['HTTP_HOST']);
        $service_type = '';
        if ($card_info['netway'] == 'WX_WAP') {
            $service_type = "wxh5";
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $service_type = 'qqh5';
        } elseif ($card_info['netway'] == 'WX') {
            $service_type = 'wxbs';
        } elseif ($card_info['netway'] == 'ZFB') {
            $service_type = 'zfbbs';
        } elseif ($card_info['netway'] == 'QQ') {
            $service_type = 'qqbs';
        } elseif ($card_info['netway'] == 'WX') {
            $service_type = 'wxbs';
        } else {
            $this->_error = '此卡暂不支持该充值方式!' . $service_type;
            return false;
        }
        $md5Key = $this->_key;
        $postData['type_code'] = $service_type;//支付类型1
        $postData['member_code'] = $card_info['mer_no'];//商户号1
        $postData['amount'] = $data['money'];//金额1
        $postData['bank_segment'] = '103';//这B参数是必填
        $postData['notify_url'] = $this->_callbackurl;//回调地址1
        $postData['down_sn'] = $data['ordernumber'];//订单号1
        $postData['subject'] = '充值';//订单标题（可空）1
        $path = $card_info['card_pass'];
        $path = $this->formatKey($path);
        //生成签名
        $dataSign['type_code'] = $postData['type_code'];
        $dataSign['subject'] = $postData['subject'];
        $dataSign['amount'] = $postData['amount'];
        $dataSign['notify_url'] = $this->_callbackurl;
        $dataSign['down_sn'] = $data['ordernumber'];
        $dataSign['sign'] = $this->makeInSign($dataSign, $md5Key);
        //组合数据
        $post = array(
            'member_code' => $postData['member_code'],
            'cipher_data' => $this->encrypt($dataSign, $path),
        );
        //提交到接口

        $url = $this->_requestUrl;
        $res = $this->mgCurlPost($url, $post);
        if($res['code'] != "0000"){
            $this->_error = $res['msg'];
            return false;
        }
        if (empty($res)) {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }

        $url = isset($res['code_url']) ? $res['code_url'] : '';
        if (!$url) {
            $this->_error = '此充值通道异常,请选择其它充值通道充值!';
            $this->logdump('mgWap', $res);
            return false;
        }
        if (!(strstr($card_info['netway'], "WAP") > -1)) {
            $url = isset($res['code_url']) ? $res['code_url'] : '';
            if (!$url) {
                $this->_error = $res['msg'];
                return false;
            }
            return ['code' => 'qrcode', 'url' => $url];
        }
        return ['code' => 'url', 'url' => isset($url) ? $url : ''];
    }

    public function callback($response = [], $is_raw = 0)
    {

        if (empty($response)) {
            $this->logdump('mgWap', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }
     
        $ordernumber = isset($response['down_sn']) ? $response['down_sn'] : '';//订单号
        if (empty($ordernumber)) {
            echo 'down_sn';
            $this->logdump('mgWap', '回掉(订单号为空!)');
            exit;
        }
        $orderstatus = isset($response['code']) ? $response['code'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            echo 'code';
            $this->logdump('mgWap', '回掉(订单状态为空)');
            exit;
        }

        $paymoney = isset($response['amount']) ? $response['amount'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            echo 'amount';
            $this->logdump('mgWap', '回掉-订单金额为空');
            exit;
        }
        $sysnumber = isset($response['order_sn']) ? $response['order_sn'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            echo 'order_sn';
            $this->logdump('mgWap', '回掉(泽圣订单号为空!)');
            exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            echo 'sign';
            $this->logdump('mgWap', '回掉(签名不正确!)');
            exit;
        }
        $status = isset($response['status']) ? $response['status'] : 0;
        if (empty($status)) {
            echo 'status';
            $this->logdump('mgWap', '回掉(状态不正确!)');
            exit;
        }
        $msg = isset($response['msg']) ? $response['msg'] : 0;
        if (empty($msg)) {
            echo 'msg';
            $this->logdump('mgWap', '回掉(信息不正确!)');
            exit;
        }
        $fee = isset($response['fee']) ? $response['fee'] : 0;
        if (empty($fee)) {
            echo 'fee';
            $this->logdump('mgWap', '回掉(手续费不正确!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if (empty($deposits)) {
            echo 'shop_order_num';
            $this->logdump('mgWap', '没有找到订单:' . $ordernumber . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            echo 'deposit_card_id';
            $this->logdump('mgWap', '没有找到订单:' . $ordernumber . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            echo 'mer_no';
            $this->logdump('mgWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            echo 'mer_key';
            $this->logdump('mgWap', '配置有误,请联系客服!');
            exit;
        }
        $this->_key = $card_info['mer_key'];
        $checkSign['order_sn'] =$response['order_sn'];
        $checkSign['down_sn'] = $response['down_sn'];
        $checkSign['status'] = $response['status'];
        $checkSign['amount'] = $response['amount'];
        $checkSign['fee'] = $response['fee'];
        $checkSign['trans_time'] = $response['trans_time'];
        if ($this->makeInSign($checkSign,$this->_key) == $response['sign'])//签名正确
        {
            echo '成功了';
            $this->bak('gs4fj@5f!sda*dfuf', '00', $deposits, $sysnumber, 'mgWap');
        } else {
            echo '签名错了';
            $this->logdump('mgWap', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }

}