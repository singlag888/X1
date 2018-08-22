<?php
namespace sscapp\payment;
class yiheXin extends payModel{
    /**
     * 益和信微信WAP,QQ和QQWAP
     * @param array $data
     * @return string $str
     * @author Jacky
     */
    public function orderNumber($asd = 0)
    {
        $orderno = "1034" . date("YmdHis") . rand(1000, 9999);
        return $orderno;
        // TODO: Implement orderNumber() method.
    }

    public function run($data=[])
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
        if (empty($card_info['mer_no'])) {
            $this->_error = '配置有误,请联系客服!';
            return false;
        }
        $partner = $card_info['mer_no'];
        $key = $card_info['mer_key'];
        $className = explode('\\', __CLASS__);
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $shop_url = $card_info['shop_url'];
        $callbackurl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/backPay/' . end($className);
        $sameBackUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/inStep/' . end($className);
        $banktype = '';
        $apiUrl = $GLOBALS['cfg']['pay_url']['yihexin_api'];
        if ($card_info['netway'] == 'WX') {
            $banktype = '2001';
        } elseif ($card_info['netway'] == 'ZFB') {
            $banktype = '2003';
        } elseif ($card_info['netway'] == 'WX_WAP') {
            $banktype = '2005';
        } elseif ($card_info['netway'] == 'ZFB_WAP') {
            $banktype = '2007';
        } elseif ($card_info['netway'] == 'QQ') {
            $banktype = '2008';
        } elseif ($card_info['netway'] == 'QQ_WAP') {
            $banktype = '2009';
        } elseif ($card_info['netway'] == 'JD') {
            $banktype = '2010';
        } elseif ($card_info['netway'] == 'JD_WAP') {
            $banktype = '2011';
        } elseif ($card_info['netway'] == 'YL') {
            $banktype = '2012';
        } elseif ($card_info['netway'] == 'WY') {
            $third_party_bank_id = $this->request->getPost('third_party_bank_id', 'intval');
            switch ($third_party_bank_id) {
                // 工商银行
                case '1':
                    $banktype = '1002';
                    break;
                // 农业银行
                case '2':
                    $banktype = '1005';
                    break;
                // 建设银行
                case '3':
                    $banktype = '1003';
                    break;
                // 招商银行
                case '4':
                    $banktype = '1001';
                    break;
                // 交通银行
                case '5':
                    $banktype = '1020';
                    break;
                // 中信银行
                case '6':
                    $banktype = '1021';
                    break;
                case '7':
                    break;
                // 中国光大银行
                case '8':
                    $banktype = '1022';
                    break;
                // 民生银行
                case '9':
                    $banktype = '1006';
                    break;
                // 上海浦东发展银行
                case '10':
                    $banktype = '1004';
                    break;
                // 兴业银行
                case '11':
                    $banktype = '1009';
                    break;
                // 广发银行
                case '12':
                    $banktype = '1027';
                    break;
                // 平安银行
                case '13':
                    $banktype = '1010';
                    break;
                // 华夏银行
                case '15':
                    $banktype = '1025';
                    break;
                // 东莞银行
                case '16':
                    break;
                // 渤海银行
                case '17':
                    $banktype = 'CBHB';
                    break;
                // 浙商银行
                case '19':
                    break;
                // 北京银行
                case '20':
                    $banktype = '1032';
                    break;
                // 广州银行
                case '21':
                    $banktype = '';
                    break;
                // 中国银行
                case '22':
                    $banktype = '1052';
                    break;
                // 邮政储蓄
                case '23':
                    $banktype = '1028';
                    break;
            }

        } else {
            $this->_error = '暂时不支持该支付!';
            return false;
        }
        $postData['userid'] = $partner;
        $postData['orderid'] = $data['ordernumber'];
        $postData['money'] = $data['money'];
        $postData['url'] = $callbackurl;
        $postData['bankid'] = $banktype;
        $postData['sign'] = "userid=" . $postData['userid'] . "&orderid=" . $postData['orderid'] . "&bankid=" . $postData['bankid'] . "&keyvalue=" . $key;
        $postData['sign2'] = "money=" . $postData['money'] . "&userid=" . $postData['userid'] . "&orderid=" . $postData['orderid'] . "&bankid=" . $postData['bankid'] . "&keyvalue=" . $key;
        $sign = md5($postData['sign']);
        $sign2 = md5($postData['sign2']);
        $url=$apiUrl."?userid=".$postData['userid']."&orderid=".$postData['orderid']."&money=".$postData['money']."&url=".$postData['url']."&bankid=".$postData['bankid']."&sign=".$sign."&sign2=".$sign2;

            return ['code' => 'url', 'url' => $url];
    }

    public function callback($response = [], $is_raw = 0){

        if (empty($response)) {
            $this->logdump('yiheXin', '回掉异常(第三方返回参数为空,请支付小组查看处理!)');
            exit;
        }
        if ($is_raw) {
            parse_str($response, $response);
        }

        $returncode = isset($response['returncode']) ? $response['returncode'] : '';//商户ID
        if (empty($returncode)) {
            $this->logdump('yiheXin', '回掉(returncode为空!)');
            exit;
        }
        $money = isset($response['money']) ? $response['money'] : 0;//订单号
        if ($money === 0) {
            $this->logdump('yiheXin', '回掉(money为空!)');
            exit;
        }

        $userid = isset($response['userid']) ? $response['userid'] : '';//订单号
        if (empty($userid)) {
            $this->logdump('yiheXin', '回掉(userid为空!)');
            exit;
        }

        $orderid = isset($response['orderid']) ? $response['orderid'] : '';//订单金额 单位分（人民币）
        if (empty($orderid)) {
            $this->logdump('yiheXin', '回掉orderid为空');
            exit;
        }

        $sign = isset($response['sign']) ? $response['sign'] : '';//1:支付成功，非1为支付失败
        if (empty($sign)) {
            $this->logdump('yiheXin', '回掉(sign为空)');
            exit;
        }

        $sign2 = isset($response['sign2']) ? $response['sign2'] : '';//月宝订单号
        if (empty($sign2)) {
            $this->logdump('yiheXin', '回掉(sign2为空!)');
            exit;
        }
        $deposits = \deposits::getItemByCond(" shop_order_num = '{$orderid}'");

        if (empty($deposits)) {
            $this->logdump('yiheXin', '没有找到订单:' . $orderid . '的在线支付信息');
            exit;
        }
        $card_info = \cards::getItem($deposits['deposit_card_id']);
        if (empty($card_info)) {
            $this->logdump('yiheXin', '没有找到订单:' . $orderid . '的支付卡信息1');
            exit;
        }
        if (empty($card_info['mer_no'])) {
            $this->logdump('yiheXin', '配置有误,请联系客服!');
            exit;
        }
        $this->_partner = $card_info['mer_no'];
        if (empty($card_info['mer_key'])) {
            $this->logdump('yiheXn', '配置有误,请联系客服!');
            exit;
        }
        $key = $card_info['mer_key'];
        $localsign="returncode=".$returncode."&userid=".$userid."&orderid=".$orderid."&keyvalue=".$key;
        $localsign = md5($localsign);
        $localsign2="money=".$money."&returncode=".$returncode."&userid=".$userid."&orderid=".$orderid."&keyvalue=".$key;
        $localsign2 = md5($localsign2);
        if ($returncode == '1' && $localsign == $sign && $localsign2 == $sign2)//签名正确
        {
            $this->bak('gs4fj@5f!sda*dfuf', 'ok', $deposits, $orderid, 'yiheXin');
        } else {
            $this->logdump('yiheXin', '回掉(签名验证不通过!请支付小组查看!)');
            exit;
        }
        die('ok');//到不了这里
    }


}