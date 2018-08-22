<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class liveWaters
{
    static public function getItem($id, $status = 1)
    {
        $sql = 'SELECT * FROM live_waters WHERE lw_id = ' . intval($id);
        if ($status != -1) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($top_id = 0, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM live_waters WHERE 1';
        if ($top_id > 0) {
            $sql .= " AND top_id = " . intval($top_id);
        }
        if ($startDate != '') {    //提案发起时间
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate != '') {
            $sql .= " AND belong_date <= '$endDate'";
        }
        
        $sql .= ' ORDER BY lw_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getTrafficInfo($top_id = 0, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT COUNT(*) AS count, SUM(bet_times) AS total_bet_times, SUM(water) AS total_water, SUM(real_water) AS total_real_water, SUM(amount) AS total_amount FROM live_waters WHERE 1';
        if ($top_id > 0) {
            $sql .= " AND top_id = " . intval($top_id);
        }
        if ($startDate != '') {    //提案发起时间
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate != '') {
            $sql .= " AND belong_date <= '$endDate'";
        }

        $result = $GLOBALS['db']->getRow($sql);
        $result['total_bet_times'] = floatval($result['total_bet_times']);
        $result['total_water'] = floatval($result['total_water']);
        $result['total_real_water'] = floatval($result['total_real_water']);
        $result['total_amount'] = floatval($result['total_amount']);

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }
        if (empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM live_waters WHERE lw_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'lw_id');
    }

    static public function addLiveWaters($data)
    {
        if (empty($data) || !isset($data['top_id']) || !isset($data['belong_date'])) {
            throw new exception2('参数无效');
        }
        
        //同一用户一天只有一条记录，多次调用不能重复添加
        $sql = "INSERT INTO live_waters (top_id, platform, belong_date, bet_times, water, real_water, amount, create_time) VALUES(".
            "'{$data['top_id']}', '{$data['platform']}', '{$data['belong_date']}', '{$data['bet_times']}', '{$data['water']}', '{$data['real_water']}', '{$data['amount']}', '{$data['create_time']}')".
            " ON DUPLICATE KEY UPDATE bet_times='{$data['bet_times']}', water='{$data['water']}', real_water='{$data['real_water']}', amount='{$data['amount']}', ts='".date('Y-m-d H:i:s')."'";

        if (!$GLOBALS['db']->query($sql, array(), 'i')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('live_waters', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('live_waters',$data, array('lw_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM live_waters WHERE lw_id=" . intval($id) . ' LIMIT 1';

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}
?>