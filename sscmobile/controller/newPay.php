<?php
/**
 * Created by PhpStorm.
 * User: L
 * Date: 2017/12/11
 * Time: 16:01
 */

if (!defined('IN_LIGHT')) {
    die('KCAH');
}
class newPayController extends sscController {
    public $titles = array(
      'pay' => '支付',
      'fin' => '配置',
      'backPay' => '回调',
      'logdunm' => '报错日志',

    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function pay(){
        $paymoney = $this->request->getPost('deposit_amount', 'floatval', 0);//支付金额
        $card_id = $this->request->getPost('card_id', 'trim', '');//支付卡
        $card_id = authcode($card_id, 'DECODE', 'a6sbe!x4^5d_ghd');
        $is_newpay = $this->request->getPost('is_newpay', 'trim');//判断是否为新支付
        $shop_order_num = $this->request->getPost('shop_order_num', 'trim');//判断是否为新支付
        $username = $this->request->getPost('username', 'trim');//判断是否为新支付
        $user_id = $this->request->getPost('user_id', 'trim');//判断是否为新支付
        $bank_id = $this->request->getPost('bank_id', 'trim');//判断是否为新支付
        $third_party_bank_id = $this->request->getPost('third_party_bank_id', 'trim');//判断是否为新支付
        $shop_order_num = authcode($shop_order_num,'DECODE','a6sbe!x4^5d_ghd');
        $requestURI = $this->request->getPost('requestURI', 'trim');//内部回掉处理订单加密地址
        if ($paymoney <= 0 ||  empty($card_id) || empty($requestURI) || $is_newpay != '1') {
            showMSG('API参数出错1,请联系客服!');
        }
        if ($card_id <= 0) {
            showMSG('卡异常,请选择其他卡进行充值!');
        }
        $cardInfo = cards::getItem($card_id);
        if (!$cardInfo) {
            showMSG( '未找到卡信息,请联系客服!');
        }
        $obj_name = isset($cardInfo['obj_name'])?$cardInfo['obj_name']:'';
        if (!$obj_name) {
            showMSG( '后台配置出错1,请联系客服!');
        }
        if (!isset($cardInfo['pay_small_input']) || floatval($cardInfo['pay_small_input']) <= 0) {
            showMSG('后台配置出错2,请联系客服!');
        }
        if ($cardInfo['pay_small_input'] > $paymoney) {
            showMSG('您好!此次充值金额最小为:' . $cardInfo['pay_small_input'] . '元');
        }
        if (!isset($cardInfo['pay_max_input']) || (floatval($cardInfo['pay_max_input']) <= floatval($cardInfo['pay_small_input']))) {
            showMSG( '后台配置出错3,请联系客服!');
        }
        if ($cardInfo['pay_max_input'] < $paymoney) {
            showMSG('您好!此次充值金额最大为:' . $cardInfo['pay_max_input'] . '元');
        }
        if (!defined('FORE_PATH')) {
            showMSG('配置出错,请联系客服!');
        }
        $file_root = MOBILE_PATH . 'payment/' . $obj_name . '.class.php';
        //$file_root = FORE_PATH . 'payment/' . $obj_name . '.class.php';
        if (!file_exists($file_root)) {
            showMSG('暂不支持该支付1!');
        }
        $payName = '\sscmobile\payment\\' . $obj_name;
        $model = new $payName();//TODO: 实例化对象。
        $res = $model->run(['money' => $paymoney, 'ordernumber' => $shop_order_num, 'username' => $username, 'user_id' => $user_id, 'bank_id' => $bank_id,'card_id' => $card_id,'requestURI' => $requestURI,'third_party_bank_id' => $third_party_bank_id ,'card_info' => $cardInfo,]);
        //TODO:这个地方的参数我不想增加了。该加的都加完了，不知道怎么用的话就自己去打印$data['card_info']

    }
    /**
     * 记录报错信息
     * @param string $logName
     */
    public function logdump($logName = '支付')
    {
        static $count = 0;
        $argsNum = func_num_args();
        $args = func_get_args();
        $str = '';
        if (extension_loaded('xdebug')) {
            $str .= "" . date('Y-m-d H:i:s') . " BEGIN DEBUG($count) at " . xdebug_call_class() . "::" . xdebug_call_function() . "() [" . " " . xdebug_call_line() . "]\n";
        } else {
            $call_stack = debug_backtrace();
            $str .= date('Y-m-d H:i:s') . " Debug (no xdebug)  " . $call_stack [0] ['file'] . ":" . $call_stack [0] ['line'] . "\n";
        }

        for ($i = 0; $i < $argsNum; ++$i) {
            if (is_string($args[$i])) {
                $str .= $args[$i] . "\n";
            } else {
                $str .= var_export($args[$i], true) . "\n";
            }
        }

        $count++;
        $str .= "**************END DEBUG($count)**************";
        $log_file = LOG_PATH . 'payLog/' . $logName . '.log';
        $this->log_write_file($log_file, $str . "\n", FILE_APPEND);
    }
    /**
     * 创建错误日志文件夹与写入错误文件
     * @param $file
     * @param $content
     * @param int $flag
     */
    private function log_write_file($file, $content, $flag = 0)
    {
        $dirname = LOG_PATH . 'payLog';
        if (file_exists($dirname) === false) {
            if (@mkdir($dirname, 0777, true) === false) {

            }
        }
        if ($flag === FILE_APPEND) {
            file_put_contents($file, $content . "\n", LOCK_EX | FILE_APPEND);
        } else {
            file_put_contents($file, $content . "\n", LOCK_EX);
        }
    }
    public function fin()
    {
        $gflag = $this->request->getGet('flag', 'trim', '');
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            $showmsg = $errstr = '非法请求，该用户不存在或已被冻结';
            $this->_checkWithdraw($gflag, 1, $errstr, $showmsg);
        }
        $this->_checkWithdraw($gflag, 0, '', '');
        $op = $this->request->getPost('op', 'trim');
        $min_deposit_limit = config::getConfig('min_deposit_limit');
        $max_deposit_limit = config::getConfig('max_deposit_limit');
        if ('autoAddCase' == $op){
            if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
                die(json_encode(array("errno"=>101,"errstr" => '非法用户')));
            }
            $bankFlag = $this->request->getPost('flag', 'trim', '');
            if ($bankFlag == 'BP') {
                $bank_id = $this->request->getPost('bank_id', 'intval');
                $card_id = $this->request->getPost('card_id', 'intval');
            } else {
                $bank_id = $this->request->getPost('bank_id', 'trim');
                $bank_id = authcode($bank_id, 'DECODE', 'a6sbe!x4^5d_ghd');
                $card_id = $this->request->getPost('card_id', 'trim');
                $card_id = authcode($card_id, 'DECODE', 'a6sbe!x4^5d_ghd');
            }
            $deposit_amount = $this->request->getPost('deposit_amount', 'floatval');
            $remark=$this->request->getPost('remark', 'trim');
            $pay_account_id = $this->request->getPost('pay_account_id','trim');
            $flag = isset($GLOBALS['cfg']['pay_flag'][$bank_id]) ? $GLOBALS['cfg']['pay_flag'][$bank_id] : 'BP';
            $shop_order_num = $card_id . '_' . $flag . time() . $this->randomkeys(8);
            $shop_order_num = substr($shop_order_num,0,30);
            switch ($flag){
                case 'MBWX':
                case 'MBZFB':
                case 'MBQQ':
                case 'MBJD':
                case 'MBWXWAP':
                case 'MBQQWAP':
                $shop_order_num = $card_id . '_' . $flag . $this->randomkeys(2).time() .$this->randomkeys(8) ;
                $shop_order_num = substr($shop_order_num,0,20);
            }
            switch ($flag){
                case 'LIANFUBAOQQW':
                case 'LIANFUBAOKJ':
                case 'LIANFUBAOZFBW':
                case 'LIANFUBAOWY':
                case 'LIANFUBAOQQ':
                    $shop_order_num = $card_id . '_' . $flag . $this->randomkeys(2).time() .$this->randomkeys(8) ;
                    $shop_order_num = substr($shop_order_num,0,20);
            }
            if(in_array($flag,['XIDAKEJIWX','XIDAKEJIWXW','XIDAKEJIZFB','XIDAKEJIZFBW','XIDAKEJIWY','CHANGCHENGFUWX','CHANGCHENGFUWXW','CHANGCHENGQQ'])){
                $min_deposit_limit=10;
            }elseif(in_array($flag,['YUNXUNWX','YUNXUNQQ','YUNXUNWY'])){
                $min_deposit_limit=30;
            }
            if(in_array($flag,['CAIFUBAOZFB','CAIFUBAOWX','CAIFUBAOQQ'])){
                $tmp=(int)($deposit_amount*100);
                if(!is_int($tmp/100)){
                    die(json_encode(array("errno"=>103, "errstr"=>"充值金额必须为整数!，无法提交")));
                }
            }
            if ($deposit_amount < $min_deposit_limit) {
                die(json_encode(array("errno"=>103, "errstr"=>"金额低于最低充值限额{$min_deposit_limit}，无法提交")));
            }
            if(!$card = cards::getItem($card_id, NULL, 2)){
                die(json_encode(array("errno"=>105, "errstr"=>"此充值卡不存在或已禁用")));
            }
            if($card['discount'] != '' && $deposit_amount  >= 10 && $card['discount'] > '0' && $card['discount'] < 10){
                $deposit_amount = intval($deposit_amount * $card['discount'] / 10 );
            }
            if ($card['not_integer'] == 1 && !strpos((string)($deposit_amount), '.')) {
                $deposit_amount -= (mt_rand(1, 99) / 100.0);
            }
            if ($card['not_zero'] == 1 && $deposit_amount  >= 10 && $deposit_amount % 10 == 0) {
                $deposit_amount -= mt_rand(1, 4);
            }
            if ($card['not_integer'] == 1) {
                $deposit_amount = number_format($deposit_amount,2, '.', '');
            }
            //如果是幸运支付的订单状态都是0，后台可以人工手动审核执行上分
            $orderStatus = $card['usage'] == 3 ? 0 : 3;
            $data = array(
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'top_id' => $user['top_id'],
                'player_pay_time' => date("Y-m-d H:i:s"),
                'player_card_name' => '',
                'real_name'=>$remark,
                'trade_type' => $card['usage'],
                'amount' => $deposit_amount,
                'deposit_bank_id' => $bank_id,
                'deposit_card_id' => $card_id,
                'shop_order_num' => $shop_order_num,
                'local_order_num' => date("YmdHis") . strtoupper(uniqid('', false)),
                'create_time' => date("Y-m-d H:i:s"),
                'status' => $orderStatus, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
                'pay_account_id' => $pay_account_id, //支付账号
            );
            if (!$deposit_id = deposits::addItem($data)) {
                die(json_encode(array("errno"=>102,"errstr" => '添加数据失败'))) ;
            }
            if ($bankFlag == 'BP') {
                die(json_encode(array('errno'=>0, 'errstr' => $shop_order_num, 'deposit_id' => $GLOBALS['db']->insert_id(), 'deposit_amount' => $deposit_amount, 'local_order_num' => $data['local_order_num']))) ;
            } else {
                die(json_encode(array('errno'=>0, 'errstr' =>  authcode($shop_order_num, 'ENCODE', 'a6sbe!x4^5d_ghd'), 'deposit_id' => $GLOBALS['db']->insert_id(), 'deposit_amount' => $deposit_amount, 'local_order_num' => $data['local_order_num']))) ;
            }

        } elseif('delCase' == $op){
            $deposit_id = $this->request->getPost('deposit_id', 'trim');
            if(deposits::deleteItem($deposit_id, true)){
                die(json_encode(array("errno"=>0,"errstr" => ''))) ;
            }
            die(json_encode(array("errno"=>104,"errstr" => '取消订单失败'))) ;
        }
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') == false ? 'http' : 'https';
        $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $usage = $this->request->getGet('usage', 'trim', '');
        $cards = cards::getItemsC(1, 0, $usage, '', 2, cards::ORDER_BY_SORT, $user['ref_group_id']);
        $cardList = array();
        $tempCardList = array();
        foreach ($cards as $value) {
            if (!array_key_exists($value['bank_id'], $tempCardList)) {
                $tempCardList[$value['bank_id']] = array();
            }
            if (strpos($value['netway'], 'WAP') === false) {
                array_push($tempCardList[$value['bank_id']], $value);
            }
        }
        $cardSeq = 1;
        $cardIndex = 1;
        foreach ($tempCardList as $value) {
            if(!empty($value)) {
                $card = payCardsEncodeNew($user, 'receivePayResult', cards::choseCardToSaveNew($value, $domain), false);
                if (isset ($card['card_id']) && !empty($card['use_place']) && ($card['use_place'] & 1) === 1) {
                    $cardList[$cardIndex]['card_id'] = $card['card_id'];

                    if (!isset ($card['login_name'])) {
                        continue;
                    }
                    $cardList[$cardIndex]['login_name'] = $card['login_name'] . $cardSeq++;
                    $cardList[$cardIndex]['bank_id'] = $card['bank_id'];
                    $cardList[$cardIndex]['codes'] = $card['codes'];
                    $cardList[$cardIndex]['requestURI'] = $card['requestURI'];
                    $cardList[$cardIndex]['shop_url'] = $card['shop_url'];
                    $cardList[$cardIndex]['call_back_url'] = $card['call_back_url'];
                    $cardList[$cardIndex]['return_url'] = $card['return_url'];
                    $cardList[$cardIndex]['netway'] = authcode($card['netway'], 'DECODE', 'a6sbe!x4^5d_ghd');
                    $cardList[$cardIndex]['remark'] = $card['remark'];
                    $cardList[$cardIndex]['pay_id_input'] = $card['pay_id_input'];
                    $cardList[$cardIndex]['pay_max_input'] = $card['pay_max_input'];
                    $cardList[$cardIndex]['pay_small_input'] = $card['pay_small_input'];
                    $cardList[$cardIndex]['discount'] = $card['discount'];
                    $cardList[$cardIndex]['is_newpay'] = $card['is_newpay'];
                    $cardIndex++;
                }
            }
        }
        $hash = generateEnPwd($user['username'] . '_' . $user['user_id'] . '_' . $user['user_id'] . '_'. $user['username'] . '_' . date('Ymd'));
        $payTimeOut = config::getConfig('pay_time_out', 60);
        $time = time() + $payTimeOut;
        self::$view->setVar('cardList', json_encode($cardList));
        self::$view->setVar('usage', $usage);
        self::$view->setVar('client_ip', $GLOBALS['REQUEST']['client_ip']);
        self::$view->setVar('hash',  substr($time,0,5) . $hash . substr($time,5,5) );
        self::$view->setVar('user', $user);
        self::$view->setVar('min_deposit_limit', $min_deposit_limit);
        self::$view->setVar('max_deposit_limit', $max_deposit_limit);
        self::$view->render('fin_deposit');

    }
    private function _checkWithdraw($flag, $errno = 0, $errstr = '', $showmsg = ''){
        if($flag == 'ajax'){
            $res = array('errno'=>$errno,'errstr'=>$errstr);
            die(json_encode($res));
        } else {
            if($showmsg != ''){
                showMsg($showmsg);
            }
        }
    }
    /**
     * 生成随机
     * @param string $length 长度
     * @return string $key 返回的值
     * @author L
     */
    public function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = '';
        for ($i = 0; $i < $length; ++$i) {
            $key .= $pattern{mt_rand(0, 62)};    //生成php随机数
        }
        return $key;
    }
    public function isXml($data){
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$data,true)){
            xml_parser_free($xml_parser);
            return null;
        }else {
            return (json_decode(json_encode(simplexml_load_string($data)),true));
        }
    }
    /**
     * 回调
     * @param array | null | string  $data
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
        if (file_get_contents('php://input')) {
            $data = file_get_contents('php://input');
            $is_raw = 1;
        } elseif ($arrTmp) {
            $data = $arrTmp;
        } elseif ($this->request->getPostAsArray()) {
            $data = $this->request->getPostAsArray();
        } else {
            $this->logdump($payName, '未知的第三方返回数据方式,请检查支付文档或者联系第三方确认数据返回类型');
        }
        if (empty($data)) {
            $this->logdump($payName, '没有接收到第三方返回的数据,干活!');
            die;
        }
        if (!defined('MOBILE_PATH')) {
            $this->logdump($payName, '入口文件中的常量MOBILE_PATH不见了,谁来背锅!');
            die;
        }
        $file_root = MOBILE_PATH . 'payment/' . $payName . '.class.php';
        if (!file_exists($file_root)) {
            $this->logdump($payName, '回掉地址文件' . $file_root . '没有找到,请确认是否未上传' . $payName . '支付类文件');
        }
        $header = $this->getallheaders();
        $tmp = is_array($data) ? http_build_query($data) : $data;
        $log = 'header信息:' . http_build_query($header) . "\r\n返回数据:" . $tmp;
        $this->logdump($payName . 'BackData' . date('Y-m-d'), $log);
        $payName = '\sscmobile\payment\\' . $payName;
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
}