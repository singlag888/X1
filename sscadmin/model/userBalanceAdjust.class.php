<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userBalanceAdjust
{
    static public function getItem($id)
    {
        if (!$id) {
            return array();
        }

        $sql = 'SELECT * FROM user_balance_adjust WHERE ba_id=' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 
     * @param type $username
     * @param type $realname
     * @param type $type
     * @param type $amountCondition 在controller中将判断符与值组合成字符串传递 eg: $tmpAmount = '> 10000';
     * @param type $inputAdmin 
     * @param type $finishAdmin
     * @param type $startInputDate
     * @param type $endInputDate
     * @param type $startFinishDate
     * @param type $endFinishDate
     * @param type $reason
     * @param type $remark
     * @param type $status
     * @param type $start
     * @param type $amount
     * @return type
     */
    static public function getItems($username = '', $realname = '', $type = '', $amountCondition = '', $inputAdmin = '', $finishAdmin = '', 
        $startInputDate = '', $endInputDate = '', $startFinishDate = '', $endFinishDate = '', $reason = '', $remark = '', $status = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT ba.*, u.username AS username, u.real_name
			FROM user_balance_adjust AS ba 
			LEFT JOIN users AS u ON (u.user_id = ba.user_id) WHERE 1';
        if ($username != '') {
            $sql .= " AND u.username = '$username'";
        }
        if ($realname != '') {
            $sql .= " AND u.real_name = '$realname'";
        }
        if ($type == 1) {
            $sql .= " AND ba.amount < 0";
        }
        elseif ($type == 2) {
            $sql .= " AND ba.amount > 0";
        }
        if ($amountCondition != '') {
            $sql .= " AND abs(ba.amount) " . $amountCondition;
        }
        if ($inputAdmin != '') {
            $inputAdminInfo = admins::getItemsByNames($inputAdmin);
            $sql .= " AND ba.input_admin_id = '{$inputAdminInfo[$inputAdmin]['admin_id']}'";
        }
        if ($finishAdmin != '') {
            $finishAdminInfo = admins::getItemsByNames($finishAdmin);
            $sql .= " AND ba.finish_admin_id = '{$finishAdminInfo[$finishAdmin]['admin_id']}'";
        }
        if ($startInputDate != '') {
            $sql .= " AND ba.input_time >= '$startInputDate'";
        }
        if ($endInputDate != '') {
            $sql .= " AND ba.input_time <= '$endInputDate'";
        }
        if ($startFinishDate != '') {
            $sql .= " AND ba.finish_time >= '$startFinishDate'";
        }
        if ($endFinishDate != '') {
            $sql .= " AND ba.finish_time <= '$endFinishDate'";
        }
        if ($reason > 0) {
            $sql .= " AND ba.reason = $reason";
        }
        if ($status > -1) {
            $sql .= " AND ba.status = $status";
        }
        if ($remark != '') {
            $sql .= " AND ba.remark LIKE '%$remark%'";
        }

        $sql .= ' ORDER BY ba.ba_id DESC';

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     * 
     * @param type $username
     * @param type $realname
     * @param type $type
     * @param type $amountCondition 在controller中将判断符与值组合成字符串传递 eg: $tmpAmount = '> 10000';
     * @param type $inputAdmin
     * @param type $finishAdmin
     * @param type $startInputDate
     * @param type $endInputDate
     * @param type $startFinishDate
     * @param type $endFinishDate
     * @param type $reason
     * @param type $remark
     * @param type $status
     * @return type
     */
    static public function getItemsCount($username = '', $realname = '', $type = '', $amountCondition = '', $inputAdmin = '', $finishAdmin = '', 
        $startInputDate = '', $endInputDate = '', $startFinishDate = '', $endFinishDate = '', $reason = '', $remark = '', $status = '')
    {
        $sql = 'SELECT COUNT(*) AS count, SUM(ABS(amount)) AS totalAmount
			FROM user_balance_adjust AS ba 
			LEFT JOIN users AS u ON (u.user_id = ba.user_id) WHERE 1';
        if ($username != '') {
            $sql .= " AND u.username = '$username'";
        }
        if ($realname != '') {
            $sql .= " AND u.real_name like '%$realname%'";
        }
        if ($type == 1) {
            $sql .= " AND ba.amount < 0";
        }
        elseif ($type == 2) {
            $sql .= " AND ba.amount > 0";
        }
        if ($amountCondition != '') {
            $sql .= " AND abs(ba.amount) " . $amountCondition;
        }
        if ($inputAdmin != '') {
            $inputAdminInfo = admins::getItemsByNames($inputAdmin);
            $sql .= " AND ba.input_admin_id = '{$inputAdminInfo[$inputAdmin]['admin_id']}'";
        }
        if ($finishAdmin != '') {
            $finishAdminInfo = admins::getItemsByNames($finishAdmin);
            $sql .= " AND ba.finish_admin_id = '{$finishAdminInfo[$finishAdmin]['admin_id']}'";
        }
        if ($startInputDate != '') {
            $sql .= " AND ba.input_time >= '$startInputDate'";
        }
        if ($endInputDate != '') {
            $sql .= " AND ba.input_time <= '$endInputDate'";
        }
        if ($startFinishDate != '') {
            $sql .= " AND ba.finish_time >= '$startFinishDate'";
        }
        if ($endFinishDate != '') {
            $sql .= " AND ba.finish_time <= '$endFinishDate'";
        }
        if ($reason > 0) {
            $sql .= " AND ba.reason = $reason";
        }
        if ($status > -1) {
            $sql .= " AND ba.status = $status";
        }
        if ($remark != '') {
            $sql .= " AND ba.remark = '$remark'";
        }

        $sql .= ' ORDER BY ba.ba_id DESC';

        $result = $GLOBALS['db']->getRow($sql);

        return array('count' => $result['count'], 'totalAmount' => $result['totalAmount']);
    }

    /**
     * 获得金额总计
     * @return type
     */
    public static function getTotalAmount()
    {
        $sql = 'SELECT SUM(abs(amount)) AS total FROM user_balance_adjust';

        $result = $GLOBALS['db']->getRow($sql);

        return $result['total'];
    }

    /**
     * 添加
     */
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('user_balance_adjust', $data);
    }

    //伪删除即置status为0
    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('user_balance_adjust' , $data, array('ba_id'=>$id));
    }

    /**
     * 执行调额申请
     * @param type $ba_id
     * @return boolean
     * @throws exception2
     */
    public static function executeBalanceAdjust($ba_id)
    {
        if (!is_numeric($ba_id)) {
            throw new exception2("无效的参数", 1110);
        }
        if (!$balanceAdjust = userBalanceAdjust::getItem($ba_id)) {
            throw new exception2("无效的调额申请", 1120);
        }
        if ($balanceAdjust['status'] > 0) {
            throw new exception2('该申请已经被受理，无法再次受理', 1130);
        }
        if (!$user = users::getItem($balanceAdjust['user_id'])) {
            throw new exception2('无效的用户', 1100);
        }
        $admin_id = $GLOBALS['SESSION']['admin_id'];
        if ($admin_id == $balanceAdjust['input_admin_id']) {
            throw new exception2('执行人和申请人不能是同一个人', 1140);
        }

        if ($balanceAdjust['amount'] > 0) { // 手动增加余额
            $orderType = 155;
            $eventType = 105;
        }
        else {  // 手动减少余额
            $orderType = 202;
            $eventType = 106;
        }

        //开始事务
        $GLOBALS['db']->startTransaction();
        
        // 修改用户金额
        if (!users::updateBalance($user['user_id'], $balanceAdjust['amount'])) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败1');
        }

        // 增加一条存款记录
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => $orderType,
            'amount' => $balanceAdjust['amount'],
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] + $balanceAdjust['amount'],
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $ba_id,
            'admin_id' => $admin_id,
        );
        if (!orders::addItem($orderData)) {
            throw new exception2('提交事务失败2');
        }

        // 插入事件表
        $eventData = array(
            'user_id' => $user['user_id'],
            'type' => $eventType,
            'new_value' => $user['balance'] + $balanceAdjust['amount'],
            'old_value' => $user['balance'],
            'remark' => $balanceAdjust['remark'],
            'create_time' => date('Y-m-d H:i:s'),
            'admin_id' => $admin_id
        );
        if (!events::addItem($eventData)) {
            throw new exception2('提交事务失败3');
        }

        // 修改额度申请状态为已执行
        $adjustData = array(
            'status' => 8,
            'finish_admin_id' => $admin_id,
            'finish_time' => date('Y-m-d H:i:s')
        );
        if (!userBalanceAdjust::updateItem($ba_id, $adjustData)) {
            throw new exception2('提交事务失败4');
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('提交事务失败5');
        }

        return true;
    }

    /**
     * 取消调额申请
     * @param type $ba_id
     * @return boolean
     * @throws exception2
     */
    public static function cancelBalanceAdjust($ba_id)
    {
        if (empty($ba_id)) {
            throw new exception2('参数无效');
        }
        if (!$balanceAdjust = userBalanceAdjust::getItem($ba_id)) {
            throw new exception2('无效的调额申请');
        }
        if ($balanceAdjust['status'] > 0) {
            throw new exception2('该申请已经被受理，无法再次受理');
        }

        $admin_id = $GLOBALS['SESSION']['admin_id'];

        // 取消调额申请,直接修改状态
        $data = array(
            'status' => 9,
            'finish_admin_id' => $admin_id,
            'finish_time' => date('Y-m-d H:i:s')
        );
        if (!userBalanceAdjust::updateItem($ba_id, $data)) {
            throw new exception2('操作失败', 500);
        }

        return true;
    }

}

?>