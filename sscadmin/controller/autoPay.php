<?php

if (!defined('IN_LIGHT')) {
    die('Hacking attempt');
}

/**
 * 功能 : 原计划中的，商户应提供的提款接口
 * get_withdraw()   --得到一笔待付款项
 * save_withdraw()  --保存该笔待付款项的付款结果
 */
class autoPayController extends sscAdminController
{
    private $post = array();

    private $isEncrypted = true;

    const PRIVATE_KEY = '01063c81aa2328c3c6df19879577d8d9'; //此值须和YJ中的设定一致

    public function init()
    {
        //parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    // 默认方法
    public function index()
    {
        $encryptData = file_get_contents("php://input");
        $this->post = self::decrypt($encryptData);
log2("商户接收的数据：{$encryptData}", $this->post);
        if (!isset($this->post['a']) || !method_exists($this, $this->post['a'])) {
            echo self::encrypt(array('errno'=>10,'errstr'=>'invalid action'));
log2("商户返回的数据：invalid action");
            return;
        }
        $result = $this->{$this->post['a']}();
log2("商户返回的数据：" . var_export($result, true));
        echo self::encrypt($result);
    }

    //得到一笔待付款项 a=get_withdraw&bank_id=1
    public function get_withdraw()
    {
        //传过来的数据 bank_id card_id
        $result = array('errno' => 0, 'errstr' => '');
        
//return array(
//            'errno' => 0,
//            'errstr' => '',
//            'withdraw_id' => 11,
//            'player_bank_id' => 2,
//            'card_name' => '康利平',
//            'card_num' => '6228480718910227872',    //农行
//            'province' => 'beijing',
//            'city' => 'dongcheng',
//            'amount' => 1,
//            'mobile' => "",
//        );

        // 民生可以跨行 不用指定特定的银行
//        if (empty($this->post['player_bank_id']) || $this->post['player_bank_id'] <= 0) {
//            return array('errno' => 999, 'errstr' => 'dbf');
//        }

        if (!$withdraw = withdraws::getSubmitMachineWithdraw($this->post['player_bank_id'])) {
            return array('errno' => 2, 'errstr' => '没有待付款项');
        }

        $result += array(
            'username' => $withdraw['username'],
            'withdraw_id' => $withdraw['withdraw_id'],
            'player_bank_id' => $withdraw['bank_id'],
            'card_name' => $withdraw['card_name'],
            'card_num' => $withdraw['card_num'],
            'province' => $withdraw['province'],
            'city' => $withdraw['city'],
            'amount' => $withdraw['amount'],
            'mobile' => "",
        );

        return $result;
    }

    //a=save_withdraw&pay_bank_id=9&pay_card_num=6226224300611812&withdraw_id=74138&errno=0&errstr=&fee=0&order_num=&pay_time=2015-02-13 09:49:34&amount=1&player_bank_id=1&player_card_num=6212261702009799636&player_card_name=康利平
    public function save_withdraw()
    {
        //传过来的数据 bank_id card_id withdraw_id errno errstr fee order_num
        $result = array('errno' => 0, 'errstr' => '');

        // 如果不传，那肯定是有问题的 发送halt指令999
        if (empty($this->post['pay_bank_id']) || $this->post['pay_bank_id'] <= 0 || 
                empty($this->post['withdraw_id']) || $this->post['withdraw_id'] <= 0) {
            return array('errno' => 999, 'errstr' => 'dbf');
        }

        try {
            $flag = withdraws::saveMachinePayResult($this->post['pay_bank_id'], $this->post['pay_card_num'], $this->post['withdraw_id'], $this->post['errno'], $this->post['errstr'], $this->post['fee'], $this->post['order_num']);
        } catch (Exception $e) {
            //这里不需要返回错误，但要在后台记录下出错情况，客户端为什么要传相同的记录过来呢
            return array('errno' => 500, 'errstr' => $e->getMessage());
        }

        return $result;
    }

    /**
     *
     * @param array $arr 原数组
     * @return string 加密字符串
     */
    public function encrypt($arr)
    {
        $tmp = array();
        foreach ($arr as $k => $v) {
            $tmp[] = "$k=" . $v;
        }

        if ($this->isEncrypted == false) {
            $result = rawurlencode(implode('&', $tmp));
        }
        else {
            $result = authcode(rawurlencode(implode('&', $tmp)), 'ENCODE', self::PRIVATE_KEY);
        }

        return $result;
    }

    /**
     *
     * @param string $encryptData 加密字符串
     * @return array 还原为数组
     */
    public function decrypt($encryptData)
    {
        $result = array();
        if (preg_match('`a=\w{3,}`', $encryptData) != false) { //如果是明文不需解密
            $this->isEncrypted = false;
            parse_str(rawurldecode($encryptData), $result);
        }
        else {
            $this->isEncrypted = true;
            parse_str(rawurldecode(authcode($encryptData, 'DECODE', self::PRIVATE_KEY)), $result);
        }

        return $result;
    }
}
?>