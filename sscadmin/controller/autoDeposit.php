<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 功能 : 商户保存可靠的收款信息
 */
class autoDepositController extends sscAdminController
{
    static $bankCodes = array(
        'ID' => 1,  //工
        'LD' => 101,    //支付宝
        'TD' => 102,    //财付通
    );

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

    /**
     * 保存收款 工行网银
     */
    public function deposit()
    {
        $result = array('errno' => 0, 'errstr' => '', 'ref_proposals' => '');
        //如果返回的信息出错
        if ($this->post['errno'] > 0) {
            //保存日志记录
            return $result;
        }

        // 如果不传，那肯定是有问题的 发送halt指令999
        if (empty($this->post['records'])) {
            return array('errno' => -999, 'errstr' => 'dbf');
        }

        $usage = isset($this->post['usage']) ? $this->post['usage'] : 1;

        $datas = array();
        $tmp = cards::getItems(1);
        $cards = array_spec_key($tmp, 'card_num');
        $cardsEmails = array_spec_key($tmp, 'bind_email');
//logdump($cards, $cardsEmails);
        foreach (explode('|', $this->post['records']) as $v) {
            $items = explode(",", $v);
            if ($usage == 1) {
                if (!isset($cards[$items[1]]['card_id']) && !isset($cardsEmails[$items[1]]['card_id'])) {
                    return array('errno' => -1, 'errstr' => "Non-exists card({$items[1]})");
                }
                $data = array(
                    'bank_id' => self::$bankCodes[substr($items[0], 0, 2)],
                    'card_id' => isset($cards[$items[1]]['card_id']) ? $cards[$items[1]]['card_id'] : $cardsEmails[$items[1]]['card_id'],
                    //'usage' => $usage,    //因为只记录网转，这里没有再区分是网转还是ATM
                    'player_card_name' => $items[3],
                    'player_pay_time' => $items[2],
                    'amount' => $items[4],
                    'fee' => $items[5],
                    'order_num' => $items[7],  //流水号附言还是决定传过来，供商户参考
                    'ref_user' => $items[6],
                    'status' => 0,  //-1首次使用，不参与任何计算 0未处理 1已充值 2重复等原因暂不处理
                );
            }
            else {
                return array('errno' => 500, 'errstr' => '');
            }
            $datas[$items[0]] = $data;
        }
//log2($usage,$datas);
        $tmp = array();
        try {
            foreach ($datas as $k => $data) {
                $deposit_id = autoDeposits::saveDeposit($data);
                $tmp[] = "$k,$deposit_id";
            }
            $result['ref_proposals'] = implode('|', $tmp);
        } catch (Exception $e) {
            //这里不需要返回错误，但要在后台记录下出错情况，客户端为什么要传相同的记录过来呢
            return array('errno' => 500, 'errstr' => $e->getMessage());
        }

        return $result;
    }

    /**
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
        if (preg_match('`a(=|%3D)\w{3,}`', $encryptData) != false) { //如果是明文不需解密
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