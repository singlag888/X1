<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userLevelAdjust
{
    /**
     * 获取单挑数据
     * @param int $id
     * @return int
     */
    static public function getItem($id)
    {
        if (!$id) {
            return array();
        }

        $sql = 'SELECT * FROM user_level_adjust WHERE la_id=' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 获取多条数据
     * @param int $user_id
     * @param int $type
     * @param int $input_admin_id
     * @param string $start_input_time 
     * @param string $end_input_time 
     * @param int $finish_admin_id
     * @param string $start_finish_time
     * @param string $end_finish_time
     * @param int $status
     * @param int $start
     * @param int $amount
     * @return type
     */
    static public function getItems($user_id = 0, $type = -1, $input_admin_id = 0, $start_input_time = '', $end_input_time = '', $finish_admin_id = 0, $start_finish_time = '', $end_finish_time = '', $status = -1, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM user_level_adjust WHERE 1';
        if ($user_id != 0) {
            if (is_numeric($user_id)) {
                $sql .= " AND user_id = {$user_id}";
            }
            elseif (preg_match('`^[a-zA-Z]\w{3,}$`', $user_id)) {
                $sql .= " AND username = '{$user_id}'";
            }
            else {
                throw new exception2('参数无效');
            }
        }

        if ($type != -1) {
            $sql .= " AND type = {$type}";
        }
        if ($input_admin_id != 0) {
            $sql .= " AND input_admin_id = {$input_admin_id}";
        }
        if ($start_input_time != '') {
            $sql .= " AND input_time >= '{$start_input_time}'";
        }
        if ($end_input_time != '') {
            $sql .= " AND input_time <= '{$end_input_time}'";
        }
        if ($finish_admin_id != 0) {
            $sql .= " AND finish_admin_id = {$finish_admin_id}";
        }
        if ($start_finish_time != '') {
            $sql .= " AND finish_time >= '{$start_finish_time}'";
        }
        if ($end_finish_time != '') {
            $sql .= " AND finish_time <= '{$end_finish_time}'";
        }
        if ($status != -1) {
            $sql .= " AND status = {$status}";
        }

        $sql .= ' ORDER BY la_id DESC';

        if ($start > -1) {
            $sql .= " LIMIT {$start}, {$amount}";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber($user_id = 0, $type = -1, $input_admin_id = 0, $start_input_time = '', $end_input_time = '', $finish_admin_id = 0, $start_finish_time = '', $end_finish_time = '', $status = -1)
    {
        $sql = 'SELECT COUNT(*) AS count FROM user_level_adjust WHERE 1';

        if ($user_id != 0) {
            $sql .= " AND user_id = {$user_id}";
        }
        if ($type != -1) {
            $sql .= " AND type = {$type}";
        }
        if ($input_admin_id != 0) {
            $sql .= " AND input_admin_id = {$input_admin_id}";
        }
        if ($start_input_time != '') {
            $sql .= " AND input_time >= '{$start_input_time}'";
        }
        if ($end_input_time != '') {
            $sql .= " AND input_time <= '{$end_input_time}'";
        }
        if ($finish_admin_id != 0) {
            $sql .= " AND finish_admin_id = {$finish_admin_id}";
        }
        if ($start_finish_time != '') {
            $sql .= " AND finish_time >= '{$start_finish_time}'";
        }
        if ($end_finish_time != '') {
            $sql .= " AND finish_time <= '{$end_finish_time}'";
        }
        if ($status != -1) {
            $sql .= " AND status = {$status}";
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    /**
     * 添加
     * @param array $data
     * @return int
     * @throws exception2
     */
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('user_level_adjust', $data);
    }

    /**
     * 更新
     * @param int $id
     * @param array $data
     * @return int
     */
    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('user_level_adjust', $data, array('la_id' => $id));
    }

    /**
     * 取消调级申请
     * @param int $la_id
     * @return boolean
     * @throws exception2
     */
    public static function cancelLevelAdjust($la_id)
    {
        if (empty($la_id)) {
            throw new exception2('提案参数无效');
        }
        $data = array(
            'status' => 9,
            'finish_admin_id' => $GLOBALS['SESSION']['admin_id'],
            'finish_time' => date('Y-m-d H:i:s')
        );
        if (!userLevelAdjust::updateItem($la_id, $data)) {
            throw new exception2('操作失败', 500);
        }

        return true;
    }

}

?>