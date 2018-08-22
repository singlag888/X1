<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 暂只实现业务分组
 */
class cards
{
	const ORDER_BY_BANK_ID = 1;

	const ORDER_BY_BANK_ID_AND_SORT = 2;

    const ORDER_BY_SORT = 3;

    static public function getItem($id, $type = NULL, $status = NULL)
    {
        $sql = 'SELECT * FROM cards WHERE 1';
         if (strlen($id) >= 15) {
            $sql .= " AND card_num = '$id'";
        }
        else {
            $sql .= " AND card_id = " . intval($id);
        }
        if ($type !== NULL) {
            $sql .= " AND type = " . intval($type);
        }
        if ($status !== NULL) {
            $sql .= " AND status = " . intval($status);
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItemWithCache($id, $type = NULL, $status = NULL)
    {
        $cacheKey = $id;

        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM cards WHERE 1';
            if (strlen($id) >= 15) {
                $sql .= " AND card_num = '$id'";
            }
            else {
                $sql .= " AND card_id = " . intval($id);
            }
            if ($type !== NULL) {
                $sql .= " AND type = " . intval($type);
            }
            if ($status !== NULL) {
                $sql .= " AND status = " . intval($status);
            }

            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, CACHE_EXPIRE_LONG);
        }

        return $result;
    }

    static public function getItemsWithDeletedCard($type = 0, $bank_id = 0, $usage = 0, $card_name = '', $status = -2, $orderBy = '', $ref_group_id = -1)
    {
        $sql = 'SELECT * FROM cards WHERE 1';
        if ($type != 0) {
            $sql .= " AND type = " . intval($type);
        }
        if ($bank_id != 0) {
            if (is_array($bank_id)) {
                $sql .= " AND bank_id IN(" . implode(',', $bank_id) . ")";
            }
            else {
                $sql .= " AND bank_id = " . intval($bank_id);
            }
        }
        if ($usage != 0) {
            $sql .= " AND `usage` = '$usage'";  //usage是关键字，需要反引号
        }
        if ($card_name != '') {
            $sql .= " AND card_name = '$card_name'";
        }
        if ($status !== -2) {
            if (is_array($status)) {
                $sql .= " AND status IN(" . implode(',', $status) . ")";
            }
            else {
                $sql .= " AND status = " . intval($status);
            }
        }
        elseif ($status !== NULL) {
            $sql .= " AND status > -2"; //除非显式传null，否则一般情况下均不返回被删除的卡
        }
        if ($ref_group_id != -1) {
            $sql .= " AND `ref_group_id` = '$ref_group_id'";  //ref_group_id是关键字，需要反引号
        }
        if ($orderBy == 1) {
            $sql .= ' ORDER BY bank_id ASC';
        }
        elseif ($orderBy == 2) {
            $sql .= ' ORDER BY bank_id ASC, sort ASC';
        }
        elseif ($orderBy == 3) {
            $sql .= ' ORDER BY sort ASC';
        }
        else {
            $sql .= ' ORDER BY card_id ASC';
        }

        $result = $GLOBALS['db']->getAll($sql, array(),'card_id');

        return $result;
    }

    static public function getItems($type = 0, $bank_id = 0, $usage = 0, $card_name = '', $status = -2, $orderBy = '', $ref_group_id = -1)
    {
        $sql = 'SELECT * FROM cards WHERE 1';
        if ($type != 0) {
            $sql .= " AND type = " . intval($type);
        }
        if ($bank_id != 0) {
            if (is_array($bank_id)) {
                $sql .= " AND bank_id IN(" . implode(',', $bank_id) . ")";
            }
            else {
                $sql .= " AND bank_id = " . intval($bank_id);
            }
        }
        if ($usage != 0) {
            $sql .= " AND `usage` = '$usage'";  //usage是关键字，需要反引号
        }
        if ($card_name != '') {
            $sql .= " AND card_name = '$card_name'";
        }
        if ($status !== -2) {
            if (is_array($status)) {
                $sql .= " AND status IN(" . implode(',', $status) . ")";
            }
            else {
                $sql .= " AND status = " . intval($status);
            }
        }
        elseif ($status !== NULL) {
            $sql .= " AND status > -1"; //除非显式传null，否则一般情况下均不返回被删除的卡
        }
        if ($ref_group_id != -1) {
            $sql .= " AND `ref_group_id` = '$ref_group_id'";  //ref_group_id是关键字，需要反引号
        }
		if ($orderBy == 1) {
			$sql .= ' ORDER BY bank_id ASC';
		}
		elseif ($orderBy == 2) {
			$sql .= ' ORDER BY bank_id ASC, sort ASC';
		}
        elseif ($orderBy == 3) {
            $sql .= ' ORDER BY sort ASC';
        }
		else {
			$sql .= ' ORDER BY card_id ASC';
		}

        $result = $GLOBALS['db']->getAll($sql, array(),'card_id');

        return $result;
    }

    /**
     * 前台改版后用
     * @param int $type
     * @param int $bank_id
     * @param int $usage
     * @param string $card_name
     * @param int $status
     * @param string $orderBy
     * @param int $ref_group_id
     * @return mixed
     */
    static public function getItemsC($type = 0, $bank_id = 0, $usage = 0, $card_name = '', $status = -2, $orderBy = '', $ref_group_id = -1,$select="*")
    {
        $sql = 'SELECT '.$select.' FROM cards WHERE 1';
        if ($type != 0) {
            $sql .= " AND type = " . intval($type);
        }
        if ($bank_id != 0) {
            if (is_array($bank_id)) {
                $sql .= " AND bank_id IN(" . implode(',', $bank_id) . ")";
            }
            else {
                $sql .= " AND bank_id = " . intval($bank_id);
            }
        }
        if ($usage != 0) {
            $sql .= " AND `usage` = '$usage'";  //usage是关键字，需要反引号
        }
        if ($card_name != '') {
            $sql .= " AND card_name = '$card_name'";
        }
        if ($status !== -2) {
            if (is_array($status)) {
                $sql .= " AND status IN(" . implode(',', $status) . ")";
            }
            else {
                $sql .= " AND status = " . intval($status);
            }
        }
        elseif ($status !== NULL) {
            $sql .= " AND status > -1"; //除非显式传null，否则一般情况下均不返回被删除的卡
        }
        if ($ref_group_id != -1) {
//            $sql .= " AND `ref_group_id` = '$ref_group_id'";  //ref_group_id是关键字，需要反引号
            $sql.=" and FIND_IN_SET($ref_group_id,`ref_group_id`)";
        }
        if ($orderBy == 1) {
            $sql .= ' ORDER BY bank_id ASC';
        }
        elseif ($orderBy == 2) {
            $sql .= ' ORDER BY bank_id ASC, sort ASC';
        }
        elseif ($orderBy == 3) {
            $sql .= ' ORDER BY sort ASC';
        }
        else {
            $sql .= ' ORDER BY card_id ASC';
        }
        $result = $GLOBALS['db']->getAll($sql, array());
        return $result;
    }

    /**
     * 后台改版后用
     * @param int $type
     * @param int $bank_id
     * @param int $usage
     * @param string $card_name
     * @param int $status
     * @param string $orderBy
     * @param int $ref_group_id
     * @return mixed
     */
    static public function getItemsAd($type = 0, $bank_id = 0, $usage = 0, $card_name = '', $status = -2, $orderBy = '', $ref_group_id = -1)
    {
        $sql = 'SELECT * FROM cards WHERE 1';
        if ($type != 0) {
            $sql .= " AND type = " . intval($type);
        }
        if ($bank_id != 0) {
            if (is_array($bank_id)) {
                $sql .= " AND bank_id IN(" . implode(',', $bank_id) . ")";
            }
            else {
                $sql .= " AND bank_id = " . intval($bank_id);
            }
        }
        if ($usage != 0) {
            $sql .= " AND `usage` = '$usage'";  //usage是关键字，需要反引号
        }
        if ($card_name != '') {
            $sql .= " AND card_name = '$card_name'";
        }
        if ($status !== -2) {
            if (is_array($status)) {
                $sql .= " AND status IN(" . implode(',', $status) . ")";
            }
            else {
                $sql .= " AND status = " . intval($status);
            }
        }
        elseif ($status !== NULL) {
            $sql .= " AND status > -1"; //除非显式传null，否则一般情况下均不返回被删除的卡
        }
        if ($ref_group_id == -1) {//默认不包含测试层级
//            $sqlGr="select cg_id,ref_group_id from card_groups where `name` like '%测试%' and status = 1 ";
//            $ceShiGro=$GLOBALS['db']->getRow($sqlGr);
//            if($ceShiGro)
//            {
//                $ref_group_id=$ceShiGro['ref_group_id'];
                //$sql .= " AND `ref_group_id` != '$cg_id'";
            //    $sql.=" and NOT FIND_IN_SET($ref_group_id,`ref_group_id`)";
//            }

        }else{
            if(strpos($ref_group_id,',') !== false)
            {
              $arrTmp=explode(',',$ref_group_id);

              $sql.=' AND ';
              $tmpStr='';
              foreach ($arrTmp as $k => $v)
              {
                  $tmpStr.=" FIND_IN_SET($v,`ref_group_id`) or";
              }
                $sql.='('.rtrim($tmpStr,'or').')';

            }else{
                $sql .= " AND  FIND_IN_SET($ref_group_id,`ref_group_id`)";  //ref_group_id是关键字，需要反引号

            }
        }
        if ($orderBy == 1) {
            $sql .= ' ORDER BY bank_id ASC';
        }
        elseif ($orderBy == 2) {
            $sql .= ' ORDER BY bank_id ASC, sort ASC';
        }
        elseif ($orderBy == 3) {
            $sql .= ' ORDER BY sort ASC';
        }
        else {
            $sql .= ' ORDER BY card_id ASC';
        }
        $result = $GLOBALS['db']->getAll($sql, array(),'card_id');

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids) || empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM cards WHERE card_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'card_id');
    }

    //随机获取网转储收款蓄卡并把超额的自动下架
    static public function getRandSaveCards($bankId){
        if($bankId == 0 || !is_numeric($bankId)){
            throw new exception2('invalid bank id arg');
        }
        $cards = cards::getItems(1, $bankId, 1, '', 2);
        foreach ($cards as $k => $card) {
            if($card['balance'] > $card['balance_limit']){//如果超额则下架并改变排序
                cards::updateItem($card['card_id'], array('status' => 3,'sort' => $card['sort']+1));
                unset($cards[$k]);
            } elseif ($card['balance'] > $card['day_limit']){//如果超额则下架并改变排序
                cards::updateItem($card['card_id'], array('status' => 5,'sort' => $card['sort']+1));
                unset($cards[$k]);
            }
        }

        return $cards;
    }

    //随机获取网转储收款蓄卡并把超额的自动下架
    static public function getRandSaveBankCards($ref_group_id){
        $bankId = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24);
        $cards = cards::getItemsC(1, $bankId, 1, '', 2, '', $ref_group_id,'card_id,bank_id,card_name,card_num,balance,balance_limit,day_limit,pay_small_input,pay_max_input,use_place');
        foreach ($cards as $k => $card) {
            if($card['balance'] > $card['balance_limit']){//如果超额则下架并改变排序
                cards::updateItem($card['card_id'], array('status' => 3,'sort' => $card['sort']+1));
                unset($cards[$k]);
            } elseif ($card['balance'] > $card['day_limit']){//如果超额则下架并改变排序
                cards::updateItem($card['card_id'], array('status' => 5,'sort' => $card['sort']+1));
                unset($cards[$k]);
            }
        }
        return $cards;
    }

    public static function getCompanyPayCards ($ref_group_id) {
        $bankId = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 98, 99);
        $cards = cards::getItemsC(1, $bankId, 1, '', 2, '', $ref_group_id);

        foreach ($cards as $k => $card) {
            if ($card['balance'] > $card['balance_limit']) {
                // 如果超额则下架并改变排序
                cards::updateItem($card['card_id'], array('status' => 3, 'sort' => $card['sort'] + 1));
                unset($cards[$k]);
            } elseif ($card['balance'] > $card['day_limit']) {
                // 如果超额则下架并改变排序
                cards::updateItem($card['card_id'], array('status' => 5, 'sort' => $card['sort'] + 1));
                unset($cards[$k]);
            }
        }

        return $cards;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('invalid args');
        }
        /************************ 修改添加逻辑为一次性插入多条*******************************************/
        return $GLOBALS['db']->insert('cards', $data);
        /************************ 修改添加逻辑为一次性插入多条*******************************************/
    }

    // 其他费用登记 事务
    static public function addOtherFee($card_id, $amount, $my_fee, $order_type, $admin_id, $remark)
    {
        if (!is_numeric($card_id) || !is_numeric($amount) || $amount <= 0 || !is_numeric($my_fee) || $my_fee < 0 || !is_numeric($order_type)) {
            throw new exception2("无效的参数");
        }
        if ($order_type < 10) {
            throw new exception2("请选择正确的帐变类型，只能是运营相关费用");
        }
        if (!$card = cards::getItem($card_id)) {
            throw new exception2("找不到源卡");
        }
        if ($my_fee > ceil($amount * 0.01)) {
            throw new exception2("手续费不能高于1%");
        }
        if (in_array($order_type, array(10, 12, 13, 15))) {
            $amount = -$amount;
        }
        if ($card['balance'] < abs($amount + $my_fee)) {
            throw new exception2("源卡余额不足");
        }

        //开始事务
        $GLOBALS['db']->startTransaction();
        //1.增加银行卡帐变
        $cardOrderData = array(
            'from_card_type'=> $card['type'],  //源卡类型 1收款 2付款 3备用金
            'from_bank_id'  => $card['bank_id'],
            'from_card_id'  => $card['card_id'],
            'to_card_type'  => 0,  //目的卡类型 1收款 2付款 3备用金
            'to_bank_id'    => 0,
            'to_card_id'    => 0,
            'order_type'    => $order_type,  //帐变类型 1从玩家收款 2付款给玩家 3内部转入 4内部转出 10运营费用 11借款 12还款 13坏帐 14结息 15帐户管理费
            'amount'        => $amount, //注：支出型为负，收入型为正
            'my_fee'        => -$my_fee,    //手续费都是支出，肯定为负
            'pre_balance'   => $card['balance'],
            'balance'       => $card['balance'] + $amount + (-$my_fee),
            'create_time'   => date('Y-m-d H:i:s'),
            'ref_id'        => 0,
            'ref_user_id'   => 0,
            'ref_username'  => '',
            'admin_id'      => $admin_id,
            'remark'        => $remark,
        );
        if (!cardOrders::addItem($cardOrderData)) {
            $GLOBALS['db']->rollback();
            throw new exception2("事务失败1");
        }

        //2.更新源卡余额
        if (!cards::updateBalance($card_id, $amount + (-$my_fee))) {
            $GLOBALS['db']->rollback();
            throw new exception2("更新源卡余额失败");
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2("提交事务失败");
        }

        return true;
    }

    // 内转余额 事务
    static public function innerTransfer($card_id, $amount, $fee, $to_card_id)
    {
        if (!is_numeric($card_id) || !is_numeric($amount) || $amount <= 0 || !is_numeric($fee) || $fee < 0 || !is_numeric($to_card_id)) {
            return "无效的参数";
        }

        if (!$card = cards::getItem($card_id)) {
            return "找不到源卡";
        }
        if (!$to_card = cards::getItem($to_card_id)) {
            return "找不到目的卡";
        }
        if ($card['balance'] < $amount + $fee) {
            return "源卡余额不足";
        }

        //开始事务
        $GLOBALS['db']->startTransaction();
        //1.增加银行卡帐变
        $cardOrderData = array(
            'from_card_type'=> $card['type'],  //源卡类型 1收款 2付款 3备用金
            'from_bank_id'  => $card['bank_id'],
            'from_card_id'  => $card['card_id'],
            'to_card_type'  => $to_card['type'],  //源卡类型 1收款 2付款 3备用金
            'to_bank_id'    => $to_card['bank_id'],
            'to_card_id'    => $to_card['card_id'],
            'order_type'    => 4,  //1收款 2付款 3内转入 4内转出 每次内转将产生2条卡帐变
            'amount'        => -$amount,
            'my_fee'           => -$fee,
            'pre_balance'   => $card['balance'],
            'balance'       => $card['balance'] + (-$amount) + (-$fee),
            'create_time'   => date('Y-m-d H:i:s'),
            'ref_id'        => 0,  //相关id，对于存提款为其提案id，对于内转由于没有专门表记录，所以为0
            'ref_user_id'   => 0, //相关用户id，对于存提款为当事用户id，对于内转为0
            'ref_username'  => '',   //相关用户名，对于存提款为当事用户名，对于内转为空
            'admin_id'      => $GLOBALS['SESSION']['admin_id'],
            'remark'        => '',
        );
        if (!cardOrders::addItem($cardOrderData)) {
            $GLOBALS['db']->rollback();
            return false;
        }
        //内转将产生2个卡帐变
        $cardOrderData2 = array(
            'from_card_type'=> $to_card['type'],  //源卡类型 1收款 2付款 3备用金
            'from_bank_id'  => $to_card['bank_id'],
            'from_card_id'  => $to_card['card_id'],
            'to_card_type'  => 0,  //源卡类型 1收款 2付款 3备用金
            'to_bank_id'    => 0,
            'to_card_id'    => 0,
            'order_type'    => 3,  //1收款 2付款 3内转入 4内转出 每次内转将产生2条卡帐变
            'amount'        => $amount,
            'my_fee'           => 0,   //一般为0，除非用ATM无卡现存才会扣手续费
            'pre_balance'   => $to_card['balance'],
            'balance'       => $to_card['balance'] + $amount,
            'create_time'   => date('Y-m-d H:i:s'),
            'ref_id'        => 0,  //相关id，对于存提款为其提案id，对于内转由于没有专门表记录，所以为0
            'ref_user_id'   => 0, //相关用户id，对于存提款为当事用户id，对于内转为0
            'ref_username'  => '',   //相关用户名，对于存提款为当事用户名，对于内转为空
            'admin_id'      => $GLOBALS['SESSION']['admin_id'],
            'remark'        => '',
        );
        if (!cardOrders::addItem($cardOrderData2)) {
            $GLOBALS['db']->rollback();
            return false;
        }

        //2.更新源卡余额
        if (!cards::updateBalance($card_id, -$amount - $fee)) {
            $GLOBALS['db']->rollback();
            return "更新源卡余额失败";
        }

        //3.更新目的卡余额
        if (!cards::updateBalance($to_card_id, $amount)) {
            $GLOBALS['db']->rollback();
            return "更新目的卡余额失败";
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return "提交事务失败";
        }

        return true;
    }

    //接口用 得到一张可用的付款卡
    static function getAvailableCard($type, $bank_id, $usage = 1, $min_balance = 0)
    {
        if (!in_array($type, array(1,2))) {
            throw new exception2('invalid args');
        }

        //这里必须显式声明LIMIT 1，不然自动加尾部就出错
        $sql = "SELECT * FROM cards WHERE type = $type AND bank_id = $bank_id AND `usage` = $usage AND status = 1 AND balance >= $min_balance ORDER BY rand()"; // LIMIT 1 FOR UPDATE

        return $GLOBALS['db']->getRow($sql);
    }

    // 更新卡余额
    static public function updateBalance($id, $amount)
    {
        if (!is_numeric($id) || !is_numeric($amount)) {
            return false;
        }

        //对于扣钱，要检查是否有足够的钱扣
        if ($amount < 0) {
            if (!$card = cards::getItem($id)) {
                return false;
            }
            if ($card['balance'] < abs($amount)) {
                return false;
            }
        }

        $sql = "UPDATE cards SET balance=balance + {$amount} WHERE card_id=$id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function updateItem($id, $data, $addonsConditions = array())
    {
        if (!is_numeric($id)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->updateSM('cards', $data, array_merge(array('card_id' => $id), $addonsConditions));
    }


    /**
     * @todo   bank_id>100的第三方手动处理状态账户
     * 需要进行卡的充值限额,超过额度自动状态下线并变更sort排序到最后
     * @param  $cardList	其中一种bank_id的卡列表,例如环迅下有两个账号
     * Array(	//$cardList 必须是经过sort asc排序的,type=1,status=2的数据行;此处无需多余校验
     [0] => Array(
	     [card_id] => 20
	     [type] => 1
     [1] => Array(
	     [card_id] => 31
	     [type] => 1
     * @return Array(
	     [card_id] => 20
	     [type] => 1
     */
    static public function choseCardToSave($cardList, $domain = '')
    {
    	if (empty($cardList) || !is_array($cardList)) {
    		return false;
    	}

    	foreach ($cardList as $k => $v) {
    		// if ($v['bank_id'] < 100) {
    		// 	continue;
    		// }
            if (strpos($v['shop_url'],"http") !== 0 && strpos($v['shop_url'],"http") === false) {
                $v['shop_url'] = $domain . $v['shop_url'];
            }
    		if ($v['balance'] > $v['balance_limit']){
    			//放在循环中的原因是很低概率进入if,有多个同时超额的概率更低除非人为长时间未处理
    			$sql = "SELECT sort FROM cards order by sort desc limit 1";
    			$r = $GLOBALS['db']->getRow($sql);
    			$max_sort = $r['sort']+1;
    			//处理状态并改变排序
    			$data = array(
    					'status' => 3,
    					'sort' => $max_sort,
    			);
    			cards::updateItem($v['card_id'], $data);
    			continue;
    		}
    		return $v;
    	}
    	return array();
    }

    static public function choseCardToSaveNew($cardList, $domain = '')
    {
        if (empty($cardList) || !is_array($cardList)) {
            return false;
        }
        foreach ($cardList as $k => $v) {
            if ($v['bank_id'] >= 100) {
                if (!isset($GLOBALS['cfg']['pay_name'][$v['bank_id']])) {
                    continue;
                }

                $payName = $GLOBALS['cfg']['pay_name'][$v['bank_id']];
//
//                if ($v['shop_url'] == "") {
//                    $v['shop_url'] = $domain . '?c=pay&a=' . $payName . 'Pay';
//                } else if (strpos($v['shop_url'], '?c=pay') === 0) {
//                    $v['shop_url'] = $domain .  $v['shop_url'];
//                } else if (strpos($v['shop_url'], '?c=pay') > 0) {
//
//                } else {
//                    $v['shop_url'] = $v['shop_url']  . '/?c=pay&a=' . $payName . 'Pay';
//                }
            }

            if ($v['balance'] > $v['balance_limit']){
                //放在循环中的原因是很低概率进入if,有多个同时超额的概率更低除非人为长时间未处理
                $sql = "SELECT sort FROM cards order by sort desc limit 1";
                $r = $GLOBALS['db']->getRow($sql);
                $max_sort = $r['sort']+1;
                //处理状态并改变排序
                $data = array(
                    'status' => 3,
                    'sort' => $max_sort,
                );
                cards::updateItem($v['card_id'], $data);
                continue;
            }
            return $v;
        }
        return array();
    }

    static public function deleteItem($id, $realDelete = false)
    {
        if (!is_numeric($id)) {
            throw new exception2('invalid args');
        }

        if ($realDelete) {
            $sql = "DELETE FROM cards WHERE card_id = " . intval($id);
            $type = 'd';
        }
        else {
            $sql = "UPDATE cards SET status = -1 WHERE card_id = " . intval($id);
            $type = 'u';
        }

        return $GLOBALS['db']->query($sql, array(), $type);
    }

    /**
     * 为支持代理分卡，ref_group_id对应支付系统的group_id
     */
    static public function getGroup($id)
    {
        $sql = 'SELECT * FROM card_groups WHERE cg_id = ' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getGroupByRefGroupId($id, $status = -1)
    {
        $sql = 'SELECT * FROM card_groups WHERE ref_group_id = ' . intval($id);

        if($status != -1) {
            $sql .= ' AND status =' . $status;
        }

        return $GLOBALS['db']->getRow($sql);
    }
    static public function getGroupByRefGroupIdC($id, $status = -1)
    {
        $sql = "SELECT name FROM card_groups WHERE ref_group_id in ($id) ";

        if($status != -1) {
            $sql .= ' AND status =' . $status;
        }
        $datas= $GLOBALS['db']->getAll($sql);
        if(count($datas) > 1)
        {
            $name='';
            foreach ($datas as $k=>$v)
            {
                $name.=$v['name'].',';
            }
            return rtrim($name,',');
        }else{
             return $datas[0]['name'];
        }
    }
    static public function getGroupByDepositAmount($cg_id, $level_amount)
    {
        $sql = 'SELECT * FROM card_groups WHERE level_amount = ' . floatval($level_amount) . ' AND cg_id != ' . $cg_id;

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getGroups($status = -1, $is_fixed = -1, $orderBy = -1)
    {
        $sql = 'SELECT * FROM card_groups WHERE 1';

        if($status != -1) {
            $sql .= ' AND status = ' . $status;
        }

        if($is_fixed != -1) {
            $sql .= ' AND is_fixed = ' . $is_fixed;
        }

        if($orderBy == 0) {
            $sql .= ' ORDER BY level_amount ASC';
        } else if($orderBy == 1){
            $sql .= ' ORDER BY level_amount DESC';
        }

        $result = $GLOBALS['db']->getAll($sql, array(),'ref_group_id');
        /******************************** snow 添加在层级管理处显示 在层级管理卡组添加一个显示该层级人数的功能 ***********************************************/
        if(is_array($result) && !empty($result)){
            foreach($result as $key => $val){
                if($val['ref_group_id'] > 0) {
                    $_refNumbers = static::_getUserRefNumbers($val['ref_group_id']);
                    $result[$key]['ref_group_num'] = isset($_refNumbers['count_num'])
                        ? $_refNumbers['count_num'] : 0;
                }else{
                    $result[$key]['ref_group_num'] = 0;
                }
            }
        }
        /******************************** snow 添加在层级管理处显示 在层级管理卡组添加一个显示该层级人数的功能 ***********************************************/
        return $result;
    }

    /******************************** snow 添加在层级管理处显示 在层级管理卡组添加一个显示该层级人数的功能 ***********************************************/
    static private function _getUserRefNumbers($ref_group_id){
        $sql = 'SELECT COUNT(user_id) AS count_num,ref_group_id FROM users WHERE status = 8 and  ref_group_id ='.$ref_group_id;

        return $GLOBALS['db']->getRow($sql,array(),'ref_group_id');

    }
    /******************************** snow 添加在层级管理处显示 在层级管理卡组添加一个显示该层级人数的功能 ***********************************************/

    static public function getUserRefGroupId($userId){
        //0禁用，1启用，2正在使用，3超过收款限额而下线 4余额不足而下线 5超过当日支付限额而下线，当日不再使用
        $cardGroups = cards::getGroups();

        $sort = array(
                    'direction' => 'SORT_DESC', // 排序顺序标志 SORT_DESC 降序 SORT_ASC 升序
                    'field'     => 'level_amount',       // 排序字段
                );

        $arrSort = $resCardGroup = array();

        foreach($cardGroups AS $uniqid => $row){
            foreach($row AS $key=>$value){
                $arrSort[$key][$uniqid] = $value;
            }
        }

        if($sort['direction']){
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $cardGroups);
        }


        $usersDeposits = deposits::getUsersDeposits($userId);

        $totalDeposit = floatval($usersDeposits[$userId]['total_deposit']);

        foreach ($cardGroups as $cardGroup) {
            if ($totalDeposit >= $cardGroup['level_amount']) {
                $resCardGroup = $cardGroup;
                break;
            }
        }

        return $resCardGroup;
    }

    static public function addGroup($data)
    {
        if (!is_array($data)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->insert('card_groups', $data);
    }

    static public function updateGroup($id, $data, $addonsConditions = array())
    {
        if (!is_numeric($id)) {
            throw new exception2('invalid args');
        }

        $addonsConditions['cg_id'] = $id;

        return $GLOBALS['db']->updateSM('card_groups',$data,$addonsConditions);
    }

    static public function deleteGroup($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('invalid args');
        }

        $sql = "DELETE FROM card_groups WHERE cg_id = " . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * 检测是否已经拥有测试分组
     * @return null|array
     */
    static public function chkCardCeshiGroups($name='')
    {
        if(!empty($name))
        {
            $sql="select cg_id from card_groups where `name` = '{$name}'";
            $res=$GLOBALS['db']->getRow($sql);
            if($res)
            {
                return null;
            }

        }
        $sql="select cg_id from card_groups where `name` like '%测试%'";

        return $GLOBALS['db']->getRow($sql);
    }
}
?>
