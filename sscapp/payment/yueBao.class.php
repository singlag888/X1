<?php
/**
 * 月宝支付
 * @author stone
 */

namespace sscapp\payment;


class yueBao extends payModel
{

    private $_version = '3.0';//版本号
    private $_method = 'Yb.online.interface';//接口名称
    private $_partner = '';//商户ID
    private $_callbackurl = ''; //异步回掉地址
    private $_key = '';//商户key
    private $_requestUrl = 'http://gateway.yuebaopay.cn/online/gateway';//第三方请求地址

    /**
     * 根据第三方规则生成
     * @param int $asd
     * @return int
     */
    public function orderNumber($asd = 0)
    {
        return '1001'.date('YmdHis'). rand(100, 999).rand(100,999).rand(1,9);
        // TODO: Implement orderNumber() method.
    }

    /**
     * 生成签名
     */
    private function createSign($data)
    {
        $signSource = sprintf("version=%s&method=%s&partner=%s&banktype=%s&paymoney=%s&ordernumber=%s&callbackurl=%s%s", $this->_version, $this->_method, $this->_partner, $data['banktype'], $data['paymoney'], $data['ordernumber'], $this->_callbackurl, $this->_key);
        return md5($signSource);//32位小写MD5签名值，UTF-8编码
    }

    /**
     * 执行
     * @param array $data 参数
     * @return mixed 返回数据
     */
    public function run($data = [])
    {

        $card_info =isset($data['card_info'])?$data['card_info']:'';
        if(empty($card_info))
        {
            $this->_error = '卡信息出错,请联系客服!';
            return false;
        }
        if(empty($card_info['mer_key']))
        {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_key = $card_info['mer_key'];
        if(empty($card_info['mer_no']))
        {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $this->_partner = $card_info['mer_no'];
        $this->_key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $className = end($className);
        $this->_callbackurl = 'http://'.$_SERVER['HTTP_HOST'].'/pay/backPay/'.$className;
        $service_type ='';
        if ($card_info["netway"] == "WX_WAP") {
            $service_type = "WEIXINWAP";
        }elseif($card_info['netway'] == 'ZFB_WAP')
        {
            $service_type = 'ALIPAYWAP';
        }elseif($card_info['netway'] == 'QQ_WAP')
        {
            $service_type = 'QQWAP';
        }else{
            $this->_error = '此卡暂不支持该充值方式!'.$service_type;
            return false;
        }
        $dataTmp = array(
            'banktype' => $service_type,
            'paymoney' => $data['money'],
            'ordernumber' =>$data['ordernumber'],
        );
        $dataTmp['sign'] = $this->createSign($dataTmp);
        $dataTmp['version'] = $this->_version;
        $dataTmp['method'] = $this->_method;
        $dataTmp['partner'] = $this->_partner;
        $dataTmp['callbackurl'] = $this->_callbackurl;
        $res = $this->curlPost($this->_requestUrl, $dataTmp,$className);
        if(empty($res))
        {
            $this->_error = '第三方支付没有响应,请稍后重试!';
            return false;
        }
        if (is_object(json_decode($res)))
        {
            $arrBack = json_decode($res);
            if($arrBack->status == 0)
            {
                $this->_error = '充值通道异常,请选择其他充值通道充值!';
                $this->logdump('yueBao',$arrBack->message);
                return false;
        }
        }
        preg_match('/(http[s]?:.+)"/', $res, $arr);

        $url = isset($arr[1])?$arr[1]:'';
        if(!$url)
        {
            $this->_error = '充值通道异常,请选择其他充值通道充值!';
            $this->logdump('yueBao',$res);
            return false;
        }
        return ['code'=>'url','url'=>isset($arr[1])?$arr[1]:''];
        // TODO: Implement run() method.
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
            $this->logdump('yueBao','回掉异常(第三方返回参数为空,请支付小组查看处理!)');exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $partner = isset($response['partner']) ? $response['partner'] : 0;//商户ID
        if ($partner === 0) {
            $this->logdump('yueBao','回掉(商户ID不正确!)');exit;
        }
        $ordernumber = isset($response['ordernumber']) ? $response['ordernumber'] : '';//订单号
        if (empty($ordernumber)) {
            $this->logdump('yueBao','回掉(订单号为空!)');exit;
        }
        $orderstatus = isset($response['orderstatus']) ? $response['orderstatus'] : '';//1:支付成功，非1为支付失败
        if (empty($orderstatus)) {
            $this->logdump('yueBao','回掉(订单状态为空)');exit;
        }

        $paymoney = isset($response['paymoney']) ? $response['paymoney'] : 0;//订单金额 单位元（人民币）
        if ($paymoney === 0) {
            $this->logdump('yueBao','回掉-订单金额为空');exit;
        }
        $sysnumber = isset($response['sysnumber']) ? $response['sysnumber'] : 0;//月宝订单号
        if ($sysnumber === 0) {
            $this->logdump('yueBao','回掉(月宝订单号为空!)');exit;
        }
        $sign = isset($response['sign']) ? $response['sign'] : '';
        if (empty($sign)) {
            $this->logdump('yueBao','回掉(签名不正确!)');exit;
        }

        $deposits = \deposits::getItemByCond(" shop_order_num = '{$ordernumber}'");

        if(empty($deposits))
        {
            $this->logdump('yueBao','没有找到订单:'.$ordernumber.'的在线支付信息');exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if(empty($card_info))
        {
            $this->logdump('yueBao','没有找到订单:'.$ordernumber.'的支付卡信息1');exit;
        }
        if(empty($card_info['mer_no']))
        {
            $this->logdump('yueBao','配置有误,请联系客服!');exit;
        }
        $this->_partner = $card_info['mer_no'];
        if(empty($card_info['mer_key']))
        {
            $this->logdump('yueBao','配置有误,请联系客服!');exit;
        }
        $this->_key = $card_info['mer_key'];
        $signSource = sprintf("partner=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s",  $this->_partner, $ordernumber, $orderstatus, $paymoney, $this->_key);
        if ($sign == md5($signSource))//签名正确
        {

            $this->bak('gs4fj@5f!sda*dfuf', 'ok', $deposits,$sysnumber,'yueBao');
        } else {
            $this->logdump('yueBao','回掉(签名验证不通过!请支付小组查看!)');exit;
        }

        die('ok');//到不了这里

        // TODO: Implement callback() method.
    }


}