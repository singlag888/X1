<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class cardOrders
{
    static public function getItem($id, $status = 1)
    {
        $sql = 'SELECT * FROM card_orders WHERE cardorder_id = ' . intval($id);
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($from_card_type = 0, $from_card_id = 0, $order_type = 0, $ref_id = -1, $ref_username = '', $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM card_orders WHERE 1';
        if ($from_card_type != 0) {
            $sql .= " AND from_card_type = " . intval($from_card_type);
        }
        if ($from_card_id != 0) {
            $sql .= " AND from_card_id = " . intval($from_card_id);
        }
        if ($order_type != 0) {
            if (is_array($order_type)) {
                $sql .= " AND order_type IN(" . implode(',', $order_type) . ")";
            }
            else {
                $sql .= " AND order_type = " . intval($order_type);
            }
        }
        if ($ref_id != -1) {
            $sql .= " AND ref_id = $ref_id";
        }
        if ($ref_username != '') {
            $sql .= " AND ref_username = '$ref_username'";
        }
        if ($startDate !== '') {
            $sql .= " AND create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND create_time <= '$endDate'";
        }

        $sql .= ' ORDER BY create_time DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);
//dump($sql);
        return $result;
    }

    static public function getTrafficInfo($from_card_type = 0, $from_card_id = 0, $order_type = 0, $ref_id = -1, $ref_username = '', $startDate = '', $endDate = '')
    {
        $sql = 'SELECT count(*) AS count,sum(amount) AS total_amount,sum(my_fee) AS total_fee FROM card_orders WHERE 1';
        if ($from_card_type != 0) {
            $sql .= " AND from_card_type = " . intval($from_card_type);
        }
        if ($from_card_id != 0) {
            $sql .= " AND from_card_id = " . intval($from_card_id);
        }
        if ($order_type != 0) {
            if (is_array($order_type)) {
                $sql .= " AND order_type IN(" . implode(',', $order_type) . ")";
            }
            else {
                $sql .= " AND order_type = " . intval($order_type);
            }
        }
        if ($ref_id != -1) {
            $sql .= " AND ref_id = $ref_id";
        }
        if ($ref_username != '') {
            $sql .= " AND ref_username = '$ref_username'";
        }
        if ($startDate !== '') {
            $sql .= " AND create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND create_time <= '$endDate'";
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM card_orders WHERE cardorder_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'cardorder_id');
    }

    //得到某张卡一天的流量 典型的应用是判断付款卡是否超过当日支付限额
    static public function getDayTraffic($from_bank_id = 0, $from_card_id = 0, $order_type = 0, $startDate = '', $endDate = '')
    {
        $sql = 'SELECT sum(amount) AS sum FROM card_orders WHERE 1';
        if ($from_bank_id != 0) {
            $sql .= " AND from_bank_id = " . intval($from_bank_id);
        }
        if ($from_card_id != 0) {
            $sql .= " AND from_card_id = " . intval($from_card_id);
        }
        if ($order_type != 0) {
            if (is_array($order_type)) {
                $sql .= " AND order_type IN(" . implode(',', $order_type) . ")";
            }
            else {
                $sql .= " AND order_type = " . intval($order_type);
            }
        }
        if ($startDate !== '') {
            $sql .= " AND create_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND create_time <= '$endDate'";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['sum'];
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('card_orders', $data);
    }

}
?>