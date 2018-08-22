<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class reportDay
{
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM report_day WHERE rd_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM report_day  WHERE rd_id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //取得报表 按UID , 日期
    static public function getItems($startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM report_day  WHERE 1';
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND date <= '$endDate'";
        }
        $sql .= " ORDER BY date ASC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
// dump($sql);
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber($startDate = '', $endDate = '')
    {
        $sql = "SELECT COUNT(*) AS count FROM report_day  WHERE 1";
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND date <= '$endDate'";
        }
        $sql .= " LIMIT 0,1 ";
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('report_day', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('report_day',$data,array('rp_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM report_day WHERE rd_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * 根据条件删除多条数据
     * @param type $startDate
     * @param type $endDate
     */
    static public function deleteItems($startDate = '', $endDate = '')
    {
        $sql = ' DELETE FROM report_day WHERE 1 ';
        if ($startDate !== '') {    //提案发起时间
            $sql .= " AND date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND date <= '$endDate'";
        }

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}

?>