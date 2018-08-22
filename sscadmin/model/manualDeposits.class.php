<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 手工存款记录模型
 */
class manualDeposits
{
    static public function getItem($id)
    {
        if (!$id) {
            return array();
        }
        $sql = 'SELECT * FROM manual_deposits WHERE md_id=' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 
     * @param type $username
     * @param type $depositBankId
     * @param type $depositCardId
     * @param type $orderNum1
     * @param type $status
     * @param type $inputAdmin
     * @param type $finishAdmin
     * @param type $startInputDate
     * @param type $endInputDate
     * @param type $startFinishDate
     * @param type $endFinishDate
     * @param type $start
     * @param type $amount
     * @return type
     */
    static public function getItems($username = '', $depositBankId = -1, $depositCardId = -1, $orderNum1 = '', $status = -1, $inputAdmin = '', $finishAdmin = '', $startInputDate = '', $endInputDate = '', $startFinishDate = '', $endFinishDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM manual_deposits WHERE 1';
        if ($username != '') {
            $sql .= " AND username = '$username'";
        }
        if ($depositBankId != -1) {
            $sql .= ' AND deposit_bank_id = ' . $depositBankId;
        }
        if ($depositCardId != -1) {
            $sql .= ' AND deposit_card_id = ' . $depositCardId;
        }
        if ($orderNum1 != '') {
            $sql .= " AND order_num_1 = '$orderNum1'";
        }
        if ($status != -1) {
            $sql .= ' AND status = ' . $status;
        }
        if ($inputAdmin != '') {
            $inputAdminInfo = admins::getItemsByNames($inputAdmin);
            $inputAdminId = empty($inputAdminInfo) ? 0 : $inputAdminInfo[$inputAdmin]['admin_id'];
            $sql .= " AND input_admin_id = '{$inputAdminId}'";
        }
        if ($finishAdmin != '') {
            $finishAdminInfo = admins::getItemsByNames($finishAdmin);
            $finishAdminId = empty($finishAdminInfo) ? 0 : $finishAdminInfo[$finishAdmin]['admin_id'];
            $sql .= " AND finish_admin_id = '{$finishAdminId}'";
        }
        if ($startInputDate != '') {
            $sql .= " AND input_time >= '$startInputDate'";
        }
        if ($endInputDate != '') {
            $sql .= " AND input_time <= '$endInputDate'";
        }
        if ($startFinishDate != '') {
            $sql .= " AND finish_time >= '$startFinishDate'";
        }
        if ($endFinishDate != '') {
            $sql .= " AND finish_time <= '$endFinishDate'";
        }
        $sql .= ' ORDER BY input_time DESC';

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql);
    }
    
    /**
     * 
     * @param type $username
     * @param type $depositBankId
     * @param type $depositCardId
     * @param type $orderNum1
     * @param type $status
     * @param type $inputAdmin
     * @param type $finishAdmin
     * @param type $startInputDate
     * @param type $endInputDate
     * @param type $startFinishDate
     * @param type $endFinishDate
     * @return type
     */
    static public function getItemsCount($username = '', $depositBankId = -1, $depositCardId = -1, $orderNum1 = '', $status = -1, $inputAdmin = '', $finishAdmin = '', $startInputDate = '', $endInputDate = '', $startFinishDate = '', $endFinishDate = '')
    {
        $sql = 'SELECT COUNT(*) AS count, SUM(amount_0) AS totalAmount0, SUM(amount_1) AS totalAmount1 FROM manual_deposits WHERE 1';
        if ($username != '') {
            $sql .= " AND username = '$username'";
        }
        if ($depositBankId != -1) {
            $sql .= ' AND deposit_bank_id = ' . $depositBankId;
        }
        if ($depositCardId != -1) {
            $sql .= ' AND deposit_card_id = ' . $depositCardId;
        }
        if ($orderNum1 != '') {
            $sql .= " AND order_num_1 = '$orderNum1'";
        }
        if ($status != -1) {
            $sql .= ' AND status = ' . $status;
        }
        if ($inputAdmin != '') {
            $inputAdminInfo = admins::getItemsByNames($inputAdmin);
            $inputAdminId = empty($inputAdminInfo) ? 0 : $inputAdminInfo[$inputAdmin]['admin_id'];
            $sql .= " AND input_admin_id = '{$inputAdminId}'";
        }
        if ($finishAdmin != '') {
            $finishAdminInfo = admins::getItemsByNames($finishAdmin);
            $finishAdminId = empty($finishAdminInfo) ? 0 : $finishAdminInfo[$finishAdmin]['admin_id'];
            $sql .= " AND finish_admin_id = '{$finishAdminId}'";
        }
        if ($startInputDate != '') {
            $sql .= " AND input_time >= '$startInputDate'";
        }
        if ($endInputDate != '') {
            $sql .= " AND input_time <= '$endInputDate'";
        }
        if ($startFinishDate != '') {
            $sql .= " AND finish_time >= '$startFinishDate'";
        }
        if ($endFinishDate != '') {
            $sql .= " AND finish_time <= '$endFinishDate'";
        }

        $result = $GLOBALS['db']->getRow($sql);

        return array('count' => $result['count'], 'totalAmount0' => $result['totalAmount0'], 'totalAmount1' => $result['totalAmount1'],);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('manual_deposits', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('manual_deposits', $data, array('md_id' => $id));
    }

    static public function compareManualDeposit($md_id, $data)
    {
        if (!is_numeric($md_id)) {
            throw new exception2("无效的参数", 1);
        }
        if (!$manualDeposit = manualDeposits::getItem($md_id)) {
            throw new exception2("错误的手工存款提案ID：" . $md_id, 1);
        }
        if ($manualDeposit['finish_admin_id'] != $GLOBALS['SESSION']['admin_id']) {
            throw new exception2('您不是受理人，无法进行此操作', 1);
        }
        if ($manualDeposit['status'] < 1) {
            throw new exception2('该提案还未被受理', 1);
        }
        if ($manualDeposit['status'] >= 4) {
            throw new exception2('提案状态已经被改变，无法进行操作', 1);
        }
        if ($data['deposit_card_id'] <= 0) {
            throw new exception2('无效的收款卡', 2);
        }
        if ($manualDeposit['deposit_bank_id'] != 203 && $data['card_name_1'] == '') {
            throw new exception2('无效的汇款户名', 2);
        }
        if (!preg_match("`^\d+(\.\d{1,2})?$`", $data['amount_1'])) {
            throw new exception2('无效的存款金额', 2);
        }
        if ($data['order_num_1'] == '') {
            throw new exception2('无效的流水号', 2);
        }

        // 财务信息插入
        if (!manualDeposits::updateItem($md_id, $data)) {
            throw new exception2('录入信息失败，请检查参数', 2);
        }

        //系统对比
        $result = true;
        if ($manualDeposit['deposit_bank_id'] == 203) {// 支付渠道是智付时对比
            if ($manualDeposit['order_num_0'] != $data['order_num_1'] || $manualDeposit['amount_0'] != $data['amount_1']) {
                $result = false;
            }
        }
        else {
            if ($manualDeposit['order_num_0'] != $data['order_num_1'] || $manualDeposit['card_name_0'] != $data['card_name_1'] || $manualDeposit['amount_0'] != $data['amount_1']) {
                $result = false;
            }
        }
        
        // 对比成功时，判断是否存款记录已经存在，存在则系统撤单
        if ($result === true) {
            if ($deposit = deposits::getItemByOrderNum($data['order_num_1'])) {
                if (!manualDeposits::updateItem($md_id, array('status' => 5))) {
                    throw new exception2('对比信息失败，请重新录入数据', 2);
                }
                throw new exception2('该订单号已存在，系统自动撤单', 1);
            }
        }
        $user = users::getItem($manualDeposit['user_id']);

        if ($result === true) {
            // 同步deposit
            $depositData = array(
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'top_id' => $user['top_id'],
                'player_card_name' => $data['card_name_1'],
                'player_pay_time' => date('Y-m-d H:i:s'),
                'amount' => $data['amount_1'],
                'fee' => 0,
                'order_num' => $data['order_num_1'],
                'trade_type' => 1,
                'deposit_bank_id' => $manualDeposit['deposit_bank_id'],
                'deposit_card_id' => $data['deposit_card_id'],
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 0, //0未处理 1已受理 2已审核 3机器正在受理 4需要人工干预 8已成功 9取消提案
                'remark' => '客服备注：' . $manualDeposit['remark_0'] . '；财务备注：' . $data['remark_1'],
            );
            if (!deposits::addItem($depositData)) {
                throw new exception2('添加存款不成功', 2);
            }
            $deposit_id = $GLOBALS['db']->insert_id();

            $flag = false;
            try {
                $flag = deposits::charge($deposit_id, $GLOBALS['SESSION']['admin_id']);
            } catch (Exception2 $e) {
                $GLOBALS['db']->rollback();
            }

            if (!manualDeposits::updateItem($md_id, array('status' => 8))) {
                throw new exception2('修改状态失败', 2);
            }

            if ($flag !== true) {
                log2('手动充值事务失败，deposit_id: ' . $deposit_id);
                manualDeposits::updateItem($md_id, array('status' => 1));
                deposits::deleteItem($deposit_id, true);
                throw new exception2('添加存款不成功', 2);
            }
        }
        else {
            if (!manualDeposits::updateItem($md_id, array('status' => 5))) {
                throw new exception2('对比信息失败，请重新录入数据', 2);
            }
        }

        return $result;
    }

}

?>