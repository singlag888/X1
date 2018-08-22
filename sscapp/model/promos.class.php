<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 优惠记录模型
 */
class promos
{
    static $promoOrderType = array(
        '1' => '103',   //首存优惠
        '2' => '104',   //再存优惠
        '3' => '153',  //代理分红
        '4' => '321',  //平台理赔
        '5' => '152',  //下级存款返佣
        '6' => '107',  //活动红包
        '9' => '105',   //其他优惠
    );

    static public function getItem($id, $status = NULL)
    {
        $sql = 'SELECT * FROM promos WHERE promo_id = ' . intval($id);
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($user_id = '', $include_childs = 0, $is_test = -1, $status = -1, $type = 0, $startDate = '', $endDate = '', $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.username,b.is_test FROM promos a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($type != 0) {
            if (is_array($type)) {
                $sql .= " AND a.type IN(" . implode(',', $type) . ")";
            }
            else {
                $sql .= " AND a.type = " . intval($type);
            }
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }
		if (empty($order_by)) {
			$sql .= ' ORDER BY a.promo_id DESC';
		}
		else{
			$sql .= ' '.$order_by;
		}

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getTrafficInfo($user_id = '', $include_childs = 0, $is_test = -1, $status = -1, $type = 0, $notes = '', $startDate = '', $endDate = '')
    {
        $sql = 'SELECT COUNT(*) AS count,SUM(amount) AS total_amount FROM promos a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id)) {
                        return array('count' => 0, 'total_amount' => 0);
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, 8);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($type != 0) {
            if (is_array($type)) {
                $sql .= " AND a.type IN(" . implode(',', $type) . ")";
            }
            else {
                $sql .= " AND a.type = " . intval($type);
            }
        }
        if ($notes != '') {
            if (is_array($notes)) {
                $sql .= " AND a.notes IN('" . implode("','", $notes) . "')";
            }
            else {
                $sql .= " AND a.notes = '$notes'";
            }
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }

        $result = $GLOBALS['db']->getRow($sql);
        if (!$result['total_amount']) {
            $result['total_amount'] = 0;
        }

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM promos WHERE promo_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'promo_id');
    }

    static public function getItemsByNotes($notes = '', $userId = '', $status = '', $orderBy = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT p.*, u.username FROM promos AS p LEFT JOIN users AS u ON (p.user_id = u.user_id) WHERE 1';
        if ($notes != '') {
            $sql .= " AND p.notes = '" . $notes . "'";
        }
        if ($userId != '') {
            $sql .= ' AND p.user_id = ' . $userId;
        }
        if ($status != '') {
            $sql .= ' AND p.status = ' . $status;
        }
        if ($orderBy != '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function wrapId($id)    //, $issue, $lottery_id
    {
//        //T130117001010028E
//        switch ($lottery_id) {
//            case '1':
//            case '2':
//                $str = substr(str_replace('-', '', $issue), 4);
//                $str = str_pad($str, 10, '0', STR_PAD_RIGHT) . str_pad($trace_id, 5, '0', STR_PAD_LEFT);
//                break;
//            default:
//                throw new exception2("Unknown rules for lottery {$lottery_id}");
//                break;
//        }
//
//        $result = "T{$lottery_id}{$str}E";

        return 'R' . encode($id) . 'E';
    }

    static public function dewrapId($str)  //, $issue, $lottery_id
    {
        if (!preg_match('`^R(\w+)E$`Ui', $str, $match)) {
            return 0;
        }
        $result = decode($match[1]);
        if (!is_numeric($result)) {
            return 0;
        }

        return $result;
    }

	/**
	 * 根据用户名查询自己+旗下所有直属代理/会员的优惠汇总
	 * 自己的汇总不包含任何下级数据,列在第一行
	 * 直属下级包含:其所有下级的汇总
	 * @param $username
	 * @param $include_childs
	 * @param $status
	 * @param $types
	 * @param $startDate
	 * @param $endDate
	 * @author Davy
	 */
    static public function getTotalAmountByUserName($username = '', $include_childs = 0, $status = -1, $types = array(), $startDate = '', $endDate = '')
    {
    	$is_test = 0;	//正式账号
    	$types = empty($types) ? 0 : $types;

        //前端查询的用户
    	if (!$search_user = users::getItem($username)) {
    		throw new exception2('此用户不存在！');
    	}
        //得到自身所属层级，总代为0级，一代为1级，二代为2级，会员为3级
        if ($search_user['parent_id'] == 0) {
            $selfLevel = 0;
        }
        else {
            $selfLevel = count(explode(',', $search_user['parent_tree']));
        }

    	//得到所有子
    	// $users = users::getUserTree($search_user['user_id'], true, 1, 8);
        $users = users::getUserTreeField([
            'field' => ['user_id', 'parent_id', 'username', 'level', 'parent_tree'],
            'parent_id' => $search_user['user_id'],
            'recursive' => 1,
            'status' => 8
        ]);
    	$promos = promos::getItems($username, $include_childs, $is_test, $status, $types, $startDate, $endDate);
        if (empty($promos)) {
    		return array();
        }

    	$result = array();			//输出结果
    	$total_by_team = 0;			//查询用户及其所有下属的汇总额
    	$total_by_user = array();	//按用户汇总
        $targetUsers = array();		//直接下属
        foreach ($users as $v) {
            if ($v['parent_id'] == $search_user['user_id']) {
                $targetUsers[$v['user_id']] = $v;
            }
        }
    	//init	$total_by_user
    	foreach ($users as $v) {
    		$total_by_user[$v['user_id']] = 0;
    	}

    	//按用户累计自己的优惠
    	foreach ($promos as $promo) {
    		$total_by_user[$promo['user_id']] += $promo['amount'];
    	}

    	//查询的当前User数据,排列在第一条记录
    	$result[$search_user['user_id']] = array(
            'user_id' => $search_user['user_id'],
            'username' => $search_user['username'],
            'level' => $search_user['level'],
    		'user_type' => ($search_user['level'] == 10)?'会员':'代理',
            'parent_id' => $search_user['parent_id'],
            'total_amount' => $total_by_user[$search_user['user_id']],
    		'include_childs' => 0,
    	);

    	unset($total_by_user[$search_user['user_id']]);

        //初始化
        foreach ($targetUsers as $k => $v) {
            $result[$v['user_id']] = array(
	            'user_id' => $v['user_id'],
	            'username' => $v['username'],
	            'level' => $v['level'],
    			'user_type' => ($v['level'] == 10)?'会员':'代理',
	            'parent_id' => $v['parent_id'],
	            'total_amount' => '',
    			'include_childs' => 1,
            );
        }

    	//按团队累计自己包括直接下级的优惠(直接下级的数据包含其团队所有的累计)
    	foreach ($total_by_user as $k => $v) {
            if (isset($result[$k])) { //直接下级
                $result[$k]['total_amount'] += $v;
            }
            else { //非直接下级
            	$parent_ids = explode(',', $users[$k]['parent_tree']);
            	if(isset($parent_ids[$selfLevel + 1]) && isset($result[$parent_ids[$selfLevel + 1]])) {
                    $result[$parent_ids[$selfLevel + 1]]['total_amount'] += $v;
            	}
            }
    	}

    	return $result;
    }


    /**
     * 获取总代团队汇总
     * @param $top_id
     * @param $type
     * @param $notes
     * @param $is_test
     * @param $status
     * @param $startDate
     * @param $endDate
     */
    static public function getTopPromos($top_id = -1, $type = 0, $notes = '',$is_test = -1, $status = -1, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT a.top_id AS top_id, SUM(a.amount) AS total_amount FROM promos a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($top_id != -1) {
            $sql .= " AND top_id = " . intval($top_id);
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($status !== -1) {
            $sql .= " AND a.status = " . intval($status);
        }
        if ($type != 0) {
            if (is_array($type)) {
                $sql .= " AND a.type IN(" . implode(',', $type) . ")";
            }
            else {
                $sql .= " AND a.type = " . intval($type);
            }
        }
        if ($notes != '') {
            if (is_array($notes)) {
                $sql .= " AND a.notes IN('" . implode("','", $notes) . "')";
            }
            else {
                $sql .= " AND a.notes = '$notes'";
            }
        }
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND a.create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND a.create_time <= '$endDate'";
        }
		$sql .= " GROUP BY a.top_id";
        $result = $GLOBALS['db']->getAll($sql, array(),'top_id');

        return $result;
    }

    //得到某个用户的时间段所有优惠，在核算用户提款时用到 其实可在orders表查询得到，因为任何帐变都有记录
    static public function getTotalPromos($user_id, $type = 0, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT SUM(amount) AS sum FROM promos WHERE 1';
        $sql .= " AND user_id = " . intval($user_id);
        if ($type != 0) {
            if (is_array($type)) {
                $sql .= " AND type IN(" . implode(',', $type) . ")";
            }
            else {
                $sql .= " AND type = " . intval($type);
            }
        }
        if ($startDate !== '') {    //改动：按提案执行时间
            $sql .= " AND finish_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND finish_time <= '$endDate'";
        }
        $sql .= " AND status = 8";
        $result = $GLOBALS['db']->getRow($sql);

        return floatval($result['sum']);
    }

    //审核优惠
    static public function verify($promo_id, $admin_id, $remark)
    {
        if (!$promo = promos::getItem($promo_id)) {
            return "找不到该优惠";
        }
        if ($promo['status'] > 1) {
            return "该优惠状态不是“未处理”，拒绝审核";
        }
        if (!$user = users::getItem($promo['user_id'])) {
            return "该用户名不存在或已被禁用，不能继续";
        }
        $sql = "UPDATE promos SET verify_admin_id = $admin_id, status = 2, verify_time ='".date('Y-m-d H:i:s')."', remark = if(remark='', '$remark', concat(remark,';$remark'))" .
                " WHERE promo_id = $promo_id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return "审核失败数据库错误";
        }

        return true;
    }

    //执行优惠
    static public function execute($promo_id, $admin_id, $remark)
    {
        if (!$promo = promos::getItem($promo_id)) {
            return "找不到该优惠";
        }
        if (!isset(self::$promoOrderType[$promo['type']])) {
            return "未知优惠类型";
        }
        if ($promo['status'] != 2) {
            return "该优惠状态不是“已审核”，拒绝执行";
        }
        if (!$user = users::getItem($promo['user_id'])) {
            return "该用户名不存在或已被禁用，不能继续";
        }

        //开始事务
        $GLOBALS['db']->startTransaction();
        //'5' => '152',  //下级存款返佣	'6' => '107',  //活动红包
		//以上两种类型已经完成帐变和用户余额等变更，所以这里只变更状态
        if(self::$promoOrderType[$promo['type']] != 5 && self::$promoOrderType[$promo['type']] !=6){
	        //1.添加帐变 应在执行时添加
	        $orderData = array(
	            'lottery_id'    => 0,
	            'issue'         => '',
	            'from_user_id' => $promo['user_id'],
	            'from_username' => $user['username'],
	            'to_user_id' => 0,
	            'to_username' => '',
	            'type' => self::$promoOrderType[$promo['type']],  //103首存优惠 104再存优惠 153代理分红 105其他优惠
	            'amount' => $promo['amount'],
	            'pre_balance' => $user['balance'],
	            'balance' => $user['balance'] + $promo['amount'],
	            'create_time' => date('Y-m-d H:i:s'),
                'business_id' => $promo['promo_id'],
	            'admin_id' => $GLOBALS['SESSION']['admin_id'],
	            );
	        if (!orders::addItem($orderData)) {
	            $GLOBALS['db']->rollback();
	            return "插入帐变表数据失败";
	        }

	        //2.更新余额
	        if (!users::updateBalance($promo['user_id'], $promo['amount'])) {
	            $GLOBALS['db']->rollback();
	            return "更新用户余额时失败";
	        }
        }

        //3.更新状态为“已成功”
        $sql = "UPDATE promos SET finish_admin_id = $admin_id, status = 8, finish_time ='".date('Y-m-d H:i:s')."', remark = if(remark='', '$remark', concat(remark,';$remark'))" .
                " WHERE promo_id = $promo_id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            $GLOBALS['db']->rollback();
            return "更新状态时失败";
        }
        if (!$GLOBALS['db']->commit()) {
            return "提交事务失败";
        }

        return true;
    }

    //取消优惠
    static public function cancel($promo_id, $admin_id, $remark)
    {
        if ($remark == '' || $remark == '请输入取消原因') {
            return"取消优惠必须写明原因";
        }
        if (!$promo = promos::getItem($promo_id)) {
            return "找不到该优惠";
        }
        if ($promo['status'] >= 8) {
            return "该优惠已经执行或者取消，不能再次取消:(";
        }
        if (!$user = users::getItem($promo['user_id'])) {
            return "该用户名不存在或已被禁用，不能继续";
        }
        $sql = "UPDATE promos SET finish_admin_id = $admin_id, status = 9, finish_time ='".date('Y-m-d H:i:s')."', remark = if(remark='', '$remark', concat(remark,';$remark'))" .
                " WHERE promo_id = $promo_id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return "取消失败数据库错误";
        }

        return true;
    }

    /**
     * 添加优惠
     * @param int $user_id
     * @param int $type
     * @param float $win_lose 这在分红时有意义，记录了分红当时的盈亏量
     * @param float $amount
     * @param string $notes
     * @param string $date
     * @param int $status
     * @return boolean
     */
    static public function addPromo($user_id, $type, $win_lose, $amount, $notes, $date, $status)
    {
        if (!$user = users::getItem($user_id)) {
            return "该用户id $user_id 不存在或已被禁用，不能继续";
        }
        if ($amount <= 0) {
            return"金额必须大于0";
        }

        //添加优惠表
        $data = array(
            'user_id' => $user['user_id'],
            'top_id'    => $user['top_id'],
            'type' => $type,  //优惠类型 1首存 2再存 3代理分红 9其他
            'win_lose' => $win_lose,
            'amount' => $amount,
            'create_time' => $date,
            'notes' => $notes,
            'status' => $status,
            'admin_id' => empty($GLOBALS['SESSION']['admin_id'])?0:$GLOBALS['SESSION']['admin_id'],
            );

        if (!promos::addItem($data)) {
            return "插入优惠表数据失败";
        }

        return true;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('promos', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('promos',$data,array('promo_id'=>$id));
    }

    /** 暂不允许删除
    static public function deleteItem($id, $realDelete = false)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        if ($realDelete) {
            $sql = "DELETE FROM promos WHERE promo_id = " . intval($id);
        }
        else {
            $sql = "UPDATE promos SET status = -1 WHERE promo_id = " . intval($id);
        }

        return $GLOBALS['db']->query($sql);
    }
     *
     */

}
?>