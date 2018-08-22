<?php
/**
 * 支付调度
 */
if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class payController extends sscappController
{
    /**
     * 第三方支付入口
     * 1.检查参数 包括内部回掉地址的检查
     * 2.使用redis锁
     * 3.判断金额是否需要做变化--为了增加支付成功率
     * 4.检查类文件是否存在
     * 5.生成充值订单
     * 6.实例化支付类唤起支付
     */
    public function pay()
    {

        $token = $this->request->getPost('token', 'trim', '');//支付类型
        $paymoney = $this->request->getPost('money', 'floatval', 0);//支付金额
        $card_id = $this->request->getPost('card_id', 'trim', '');//支付卡
        $requestURI = $this->request->getPost('requestURI', 'trim');//内部回掉处理订单加密地址
        $area_flag = $this->request->getPost('area_flag', 'intval');//内部回掉处理订单加密地址
        if ($paymoney <= 0 || empty($token) || empty($card_id) || empty($requestURI) || !in_array($area_flag, [1, 2])) {
            log2file('app_pay_log', $paymoney . '_' . $token . '_' . $card_id . '_' . $requestURI);
            log2file('app_pay_log', $area_flag);
            $this->showMsg(1, 'API参数出错1,请联系客服!');
        }
        $token = $GLOBALS['SESSION']['user_id'] . $token;
        if ($this->redisLock($token))//加锁
        {
            $this->showMsg(1, '请勿重复提交订单!');
        }
        $card_id = authcode($card_id, 'DECODE', 'a6sbe!x4^5d_ghd');
        if ($card_id <= 0) {
            $this->showMsg(1, '卡异常,请选择其他卡进行充值!');
        }
        $urlCode = authcode($requestURI, 'DECODE', 'gs4fj@5f!sda*dfuf');
        if (!strpos($urlCode, '?c=fin&a=receivePayResult')) {
            $this->showMsg(1, 'API参数出错2,请联系客服!');
        }
        $cardInfo = cards::getItem($card_id);
        if (!$cardInfo) {
            $this->showMsg(1, '未找到卡信息,请联系客服!');
        }

        $randomMoney = 0;
        if ($cardInfo['not_integer'] == 1 && !strpos((string)($paymoney), '.')) {
            $randomMoney = (mt_rand(1, 99) / 100.0);
            $paymoney -= $randomMoney;

        }
        if ($cardInfo['not_zero'] == 1 && $paymoney  >= 10 && $paymoney % 10 == 0) {
            $randomMoney += mt_rand(1, 4);
            $paymoney -= $randomMoney;

        }

        if ($cardInfo['not_integer'] == 1) {
            $randomMoney = number_format($randomMoney,2, '.', '');
            $paymoney = number_format($paymoney,2, '.', '');
        }

        if (!isset($cardInfo['pay_small_input']) || floatval($cardInfo['pay_small_input']) <= 0) {
            $this->showMsg(1, '后台配置出错2,请联系客服!');
        }
        if ($cardInfo['pay_small_input'] - $randomMoney > $paymoney) {
            $this->showMsg(1, '您好!此次充值金额最小为:' . $cardInfo['pay_small_input'] . '元');
        }
        if (!isset($cardInfo['pay_max_input']) || (floatval($cardInfo['pay_max_input']) <= floatval($cardInfo['pay_small_input']))) {
            $this->showMsg(1, '后台配置出错3,请联系客服!');
        }
        if ($cardInfo['pay_max_input'] < $paymoney) {
            $this->showMsg(1, '您好!此次充值金额最大为:' . $cardInfo['pay_max_input'] . '元');
        }

        if (($cardInfo['bank_id'] == 118) && strpos($cardInfo['netway'], 'ttp')) {

            $arr = array_merge(range('a', 'z'), range('1', '9'));
            shuffle($arr);
            $res['order_number'] = '9999' . date('YmdHis') . rand(1000, 9999) . reset($arr) . rand(1000, 9999) . end($arr);
            $userInfo = users::getItem($GLOBALS['SESSION']['user_id']);
            if (!$userInfo) {
                $this->showMsg(1, '非法用户!');
            }
            $data = array(
                'user_id' => $userInfo['user_id'],
                'username' => $userInfo['username'],
                'top_id' => $userInfo['top_id'],
                'player_pay_time' => date("Y-m-d H:i:s"),
                'player_card_name' => '',
                'real_name' => '',
                'trade_type' => $cardInfo['usage'],
                'amount' => $paymoney,
                'deposit_bank_id' => $cardInfo['bank_id'],
                'deposit_card_id' => $card_id,
                'shop_order_num' => $res['order_number'],
                'create_time' => date("Y-m-d H:i:s"),
                'status' => 0, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
                'local_order_num' => date("YmdHis") . strtoupper(uniqid('', false)),//本地订单号 用于展示给用户
            );

            if (!$deposit_id = deposits::addItem($data)) {
                $this->showMsg(1, '下单失败！!');
            }
             if($area_flag == 1)
             {
                 $res['code'] = 'qrcode';
                 $res['img'] = $cardInfo['netway'];
             }else{
                 $res['code'] = 'html';
                 $res['html'] = $this->xingYun($cardInfo['netway'],$area_flag);
             }

            $this->showMsg(0, '', $res);

            die();//幸运支付 结束程序
        }
        $obj_name = isset($cardInfo['obj_name']) ? $cardInfo['obj_name'] : '';
        if (!$obj_name) {
            $this->showMsg(1, '后台配置出错1,请联系客服!');
        }

        if ($obj_name === 'jinYinBao') {
            $paymoney = (int)$paymoney;
            $rightAmount = array(2,3,4,5,6,7,8,9,10,11,12,13,15,16,18,19,20,22,25,29,30,32,33,35,39,40,45,49,50,55,59,60,65,66,68,70,75,80,88,90,95,99,100,101,110,120,130,140,150,160,170,180,188,190,198,199,200,210,250,290,298,300,400,500,600,700,800,900,999,1000,1200,1400,1500,1800,1900,1999,2000,2500,2900,3000);

            while (!in_array($paymoney, $rightAmount)) {
                $paymoney--;
            }
        }

        if (!defined('FORE_PATH')) {
            $this->showMsg(1, '配置出错,请联系客服!');
        }

        $file_root = FORE_PATH . 'payment/' . $obj_name . '.class.php';
        if (!file_exists($file_root)) {
            $this->showMsg(1, '暂不支持该支付1!');
        }
        $payName = '\sscapp\payment\\' . $obj_name;
        /**
         * @var sscapp\payment\payModel $model
         */
        $model = new $payName();
        $shop_order_num = $model->orderNumber();//订单号的生成 接口定义 每个支付模型必须继承实现
        if (empty($shop_order_num)) {
            $this->showMsg(1, '订单号生成失败,请联系客服!');
        }
        //生产成充值订单
        $res = $model->newOrder($GLOBALS['SESSION']['user_id'], $GLOBALS['SESSION']['username'], $GLOBALS['SESSION']['top_id'], $paymoney, $cardInfo['bank_id'], $card_id, $shop_order_num, 3, $requestURI, $cardInfo['usage']);
        if ($res === false) {
            $this->showMsg(1, $model->_error);
        }
        $res = $model->run(['money' => $paymoney, 'ordernumber' => $shop_order_num, 'card_info' => $cardInfo, 'area_flag' => $area_flag]);
        if ($res === false) {
            $this->showMsg(1, $model->_error);
        }
        $this->redisUnlock($token);//解锁
        $res['order_number'] = $shop_order_num;
//        $this->log_write_file('app.pay.log',$res);
        $this->showMsg(0, '', $res);
    }

    private function xingYun($url,$area_flag = 1)
    {
        $xinyun =  <<<XINGYUN
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<style>
   .qrcode{
       display: block;
       margin: 100px auto 0;
       width: 300px;
   }
   p{
       text-align: center;
       font-size: 14px;
   }
   @media (max-width: 600px) {
       .qrcode{
           margin: 0 auto;
           width: 80%;
       }
   }
</style>
<body>
<img class="qrcode" src="$url" alt="">
<p>请长按二维码保存或者将二维码截图到相册,使用相应的途径扫码</p>
</body></html>
XINGYUN;



return $xinyun;

    }

    /**
     * 回掉入口
     * 1.去除预定义get传输参数
     * 2.获取返回数据
     * 3.记录第三方返回数据
     * 4.根据预定义get参数 唤起支付回掉
     */
    public function backPay()
    {

        $arrTmp = $this->request->getGetAsArray();
        if (!isset($arrTmp['obj']) || empty($arrTmp['obj'])) {
            $this->logdump('payBack', '支付回掉错误:未找到预定义参数,请检查回掉地址写法!');
        }
        $payName = $arrTmp['obj'];
        unset($arrTmp['obj']);
        unset($arrTmp['c']);
        unset($arrTmp['a']);
        $data = [];
        $is_raw = 0;//用于判断返回数据形式是否是原始数据流 0否 1是
        if ($this->request->getPostAsArray()) {
            $data = $this->request->getPostAsArray();
        } elseif ($arrTmp) {
            $data = $arrTmp;
        } elseif (file_get_contents('php://input')) {
            $data = file_get_contents('php://input');
            $is_raw = 1;
        } else {
            $this->logdump($payName, '未知的第三方返回数据方式,请检查支付文档或者联系第三方确认数据返回类型');
        }
        if (empty($data)) {
            $this->logdump($payName, '没有接收到第三方返回的数据,支付小分队 干活!');
            die;
        }
        if (!defined('FORE_PATH')) {
            $this->logdump($payName, '入口文件中的常量FORE_PATH不见了,谁来背锅!');
            die;
        }
        $file_root = FORE_PATH . 'payment/' . $payName . '.class.php';
        if (!file_exists($file_root)) {
            $this->logdump($payName, '回掉地址文件' . $file_root . '没有找到,请确认是否未上传' . $payName . '支付类文件');
        }
        $header = $this->getallheaders();
        $tmp = is_array($data) ? http_build_query($data) : $data;
        $log = 'header信息:' . http_build_query($header) . "\r\n返回数据:" . $tmp;
        $this->logdump($payName . 'BackData' . date('Y-m-d'), $log);
        $payName = '\sscapp\payment\\' . $payName;
        $model = new $payName();

        $model->callback($data, $is_raw, $header);
    }

    private function getallheaders()
    {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))));
                if (0 == strcmp($key, "x-oapi-sign")) {
                    $headers[$key] = $value;
                } else {
                    $headers[$key] = urldecode($value);
                }
            }
        }
        return $headers;
    }

    /**
     * 线下充值-只生产成充值订单
     */
    public function offlinePay()
    {
        $card_id = $this->request->getPost('card_id', 'intval', 0);//卡id
        $bank_id = $this->request->getPost('bank_id', 'intval', 0);//银行id
        $real_name = $this->request->getPost('real_name', 'trim', '');//付款人姓名
        $paymoney = $this->request->getPost('money', 'floatval', 0);//支付金额
        $token = $this->request->getPost('token', 'trim');
        if ($card_id <= 0 || $bank_id <= 0 || empty($real_name) || empty($token) || floatval($paymoney) <= 0) {
            $this->showMsg(1, 'API参数出错1,请联系客服!');
        }
        $token = $GLOBALS['SESSION']['user_id'] . $token;
        if (!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $real_name)) {
            $this->showMsg(1, '姓名必须为中文!');
        }
        if ($this->redisLock($token))//加锁
        {
            $this->showMsg(1, '请勿重复提交订单!');
        }
        $userInfo = users::getItem($GLOBALS['SESSION']['user_id']);
        if (!$userInfo) {
            $this->showMsg(1, '非法用户!');
        }
        $cardInfo = cards::getItem($card_id);
        if (empty($cardInfo)) {
            $this->showMsg(1, '没有获取到卡信息,请联系客服!');
        }
        if ($cardInfo['pay_small_input'] > $paymoney) {
            $this->showMsg(1, '您好!此次充值金额最小为:' . $cardInfo['pay_small_input'] . '元');
        }

        if (floatval($cardInfo['pay_max_input']) <= floatval($cardInfo['pay_small_input'])) {
            $this->showMsg(1, '后台配置出错3,请联系客服!');
        }
        if ($cardInfo['pay_max_input'] < $paymoney) {
            $this->showMsg(1, '您好!此次充值金额最大为:' . $cardInfo['pay_max_input'] . '元');
        }
        $arr = array_merge(range('a', 'z'), range('1', '9'));
        shuffle($arr);
        $shop_order_num = '8888' . date('YmdHis') . rand(1000, 9999) . reset($arr) . end($arr);
        $local_order_num = date('YmdHis') . strtoupper(uniqid('', false));
        unset($arr);
        $data = array(
            'user_id' => $userInfo['user_id'],
            'username' => $userInfo['username'],
            'top_id' => $userInfo['top_id'],
            'player_pay_time' => date("Y-m-d H:i:s"),
            'player_card_name' => '',
            'real_name' => $real_name,
            'trade_type' => $cardInfo['usage'],
            'amount' => $paymoney,
            'deposit_bank_id' => $bank_id,
            'deposit_card_id' => $card_id,
            'shop_order_num' => $shop_order_num,
            'create_time' => date("Y-m-d H:i:s"),
            'status' => 0, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
            'local_order_num' => $local_order_num,//本地订单号 用于展示给用户
        );

        if (!$deposit_id = deposits::addItem($data)) {
            $this->showMsg(1, '下单失败！!');
        }
        $this->redisUnlock($token);
        $url = $cardInfo['shop_url_wap'];
        if (empty($url)) {
            $url = $cardInfo['shop_url'];
        }
        $this->showMsg(0, '', ['code' => 'url', 'url' => $url,'local_order_num'=>$shop_order_num]);
    }

    /**
     * 根据订单号查询订单状态
     */
    public function lookOrder()
    {
        $orderNumer = $this->request->getPost('order_number', 'trim', '');//支付类型
        if (empty($orderNumer)) {
            $this->showMsg(1, '参数错误!请到个人中心查看充值是否到账!');
        }
        $deposits = deposits::getItemByCond("shop_order_num ='{$orderNumer}'");
        if (!$deposits) {
            $this->showMsg(1, '订单异常!');
        }

        if ($deposits['status'] == 8) {
            $this->showMsg(0, '充值成功!');
        } else {
            $this->showMsg(1, '因网络延时,请1分钟之后到个人中心查看是否充值成功!');
        }
    }

    /**
     * 支付同步接口
     */
    public function inStep()
    {
        echo '支付成功!';
    }

}


