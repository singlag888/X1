<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 秒到系统传过来的数据存放处
 */
class autoDeposits
{
    protected static $wget = NULL;

    static $isEncrypted = false;

    const PRIVATE_KEY = 'be56e057f20f'; //此值须和YJ中的设定一致

    static public function getItem($id)
    {
        $sql = 'SELECT * FROM auto_deposits WHERE auto_id = ' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($card_id = 0, $startDate = '', $endDate = '', $status = -1, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM auto_deposits WHERE 1';
        if ($card_id > 0) {
            $sql .= ' AND card_id = ' . intval($card_id);
        }
        if ($startDate !== '') {    //发起时间
            $sql .= " AND player_pay_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND player_pay_time <= '$endDate'";
        }
        if ($status !== -1) {
            $sql .= " AND status = " . intval($status);
        }

        $sql .= ' ORDER BY auto_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
//dump($sql);
        return $GLOBALS['db']->getAll($sql);
    }

    static public function getTrafficInfo($card_id = 0, $startDate = '', $endDate = '', $status = -1)
    {
        $sql = 'SELECT count(*) AS count, SUM(amount) AS total_amount FROM auto_deposits WHERE 1';
        if ($card_id > 0) {
            $sql .= ' AND card_id = ' . intval($card_id);
        }
        if ($startDate !== '') {    //发起时间
            $sql .= " AND player_pay_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND player_pay_time <= '$endDate'";
        }
        if ($status !== -1) {
            $sql .= " AND status = " . intval($status);
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM auto_deposits WHERE auto_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'card_id');
    }

    //得到所有待充值项 默认网转类型 状态为1表示已经更新了卡余额，不再受制于必须客户提交才能更新卡余额
    static public function getPendingAutoDeposits($trade_type = 1)
    {
        //todo:考虑加上时间限制 例如，超过1小时的不能自动充值
        $sql = "SELECT * FROM auto_deposits WHERE status = 0 AND trade_type = $trade_type";  // 慎用FOR UPDATE，不然无法临时删除

        return $GLOBALS['db']->getAll($sql);
    }

    static public function getRemoteURL()
    {
        if (RUN_ENV == 1) {  //测试环境
            $remoteURL = 'http://custom2.yijiezhifu.com/?c=customIntf&custom=jyz';
        }
        elseif (RUN_ENV == 2) {
            $remoteURL = 'http://custom2.yijiezhifu.com/?c=customIntf&custom=jyz';
        }
        else {
            $remoteURL = 'http://custom2.yijiezhifu.com/?c=customIntf&custom=jyz';
        }

        return $remoteURL;
    }

    //远程调用YJ得到卡号和姓名，用于页面显示
    static public function getUsingCard($bankId, $refGroupId = 0)
    {
        autoDeposits::init();
        $postDataArray = array(
            'a' => 'get_using_card',
            'dept_id'  => $refGroupId,    //可以指定，分卡功能保留
            'bank_id'  => $bankId,
            'ref_user' => $GLOBALS['SESSION']['username'],
        );
        $encData = autoDeposits::encrypt($postDataArray);
        self::$wget->setPostData($encData);
        $content = trim(self::$wget->getContents('SOCKET', 'POST', autoDeposits::getRemoteURL()));
        $result = autoDeposits::decrypt($content);
        if (self::$wget->getResponseHttpCode() != 200) {
            $flag = -200;    //网络通信错误
        }
        else {
            if (is_array($result)) {
                $flag = $result;
            }
            else {
                log2("getUsingCard() 返回的内容解码错误：$content");
                $flag = -201; //内容解码错误
            }
        }

        //成功时返回数组，包括可能的充值成功的提案号，否则为负数错误码
        return $flag;
    }

    //注：强制使用附言后，工行网转在这里即可决定该笔存款是否可以成功充值，不再需要等待用户提交，所以在这里调用 autoCharge()
    static public function saveDeposit($autoDeposit)
    {
        if (!is_array($autoDeposit)) {
            throw new exception2('参数无效');
        }

        if (!$card = cards::getItem($autoDeposit['card_id'], NULL, NULL)) {
            throw new exception2("找不到该卡(id={$autoDeposit['card_id']})");
        }

        //1.添加记录
        autoDeposits::addItem($autoDeposit);
        $auto_id = $GLOBALS['db']->insert_id();
        $autoDeposit['auto_id'] = $auto_id;
        //说明：付款卡player_bank_id和收款卡bank_id是同一银行
        $autoDeposit['player_bank_id'] = $autoDeposit['bank_id'];

        $deposit_id = deposits::autoCharge($autoDeposit);

        return $deposit_id;
    }

    //小心添加重复记录
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('auto_deposits', $data);
    }

    static public function updateItem($id, $data, $addonsConditions = array())
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $addonsConditions['auto_id'] = $id;

        return $GLOBALS['db']->updateSM('auto_deposits',$data,$addonsConditions);
    }

    static public function deleteItem($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $sql = "DELETE FROM auto_deposits WHERE auto_id = $id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'd')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function init()
    {
        if (self::$wget == NULL) {
            self::$wget = new wget();
            self::$wget->setHttpVersion('1.0')
                ->setConnection('Close')    // Keep-Alive 将非常慢
                ->setReferer('')
                ->setCookie('')
                ->setContentType('application/x-www-form-urlencoded')
                ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)');
        }
    }

    /**
     *
     * @param array $arr 原数组
     * @return string 加密字符串
     */
    static public function encrypt($arr)
    {
        $tmp = array();
        foreach ($arr as $k => $v) {
            $tmp[] = "$k=" . $v;
        }

        if (self::$isEncrypted == false) {
            //$result = implode('&', $tmp);
            $result = rawurlencode(implode('&', $tmp));
        }
        else {
            $result = authcode(rawurlencode(implode('&', $tmp)), 'ENCODE', self::PRIVATE_KEY);
        }

        return $result;
    }

    /**
     * @param string $encryptData 加密字符串
     * @return array 还原为数组
     */
    static public function decrypt($encryptData)
    {
        $result = array();
        if (self::$isEncrypted == false) {
            parse_str(rawurldecode($encryptData), $result);
        }
        else {
            parse_str(rawurldecode(authcode($encryptData, 'DECODE', self::PRIVATE_KEY)), $result);
        }

        return $result;
    }
}