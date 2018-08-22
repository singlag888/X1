<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 用户表快照对象
 * 用户快照表 仅记录状态为正常和冻结的用户
 * 主要目的是清算每日的Balance, 未来可扩展用于该用户日结算的其他数据：例如充值金额，提款金额，购彩金额，各彩种比例等
 * 虽然能够从数据源表计算出来但是时间太长数据量太大，且太久历史数据可能被清或分表。
 * 扩展后此表更可用于对用户的大数据分析
 * 每日零点运行 crond/userSnapshot.cli.php
 * 数据量：大概每日1W条,需要考虑以后的数据清理及分表处理
 * @author Davy
 */
class userSnapshots
{
	/**
	 * 获取一条数据
	 * @param $id
	 */
    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM users_snapshot WHERE uss_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM users_snapshot  WHERE uss_id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }
    
    static public function getItems($startDate = '', $endDate = '', $start = -1, $pageSize = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM users_snapshot  WHERE 1';
        if ($startDate !== '') {    // 起始时间
            $sql .= " AND belong_date >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND belong_date <= '$endDate'";
        }
        $sql .= " ORDER BY belong_date ASC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $pageSize";
        }

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     * 得到所有总代团队余额
     * @param  $date	查询日期
     * @param  $status	默认-1,可传数组和数值
     * @param  $is_test	是否测试帐号
     */
    static public function getTopTeamBalance($date, $status = -1, $is_test = -1)
    {
        $sql = "SELECT top_id,username,sum(balance) AS total_balance FROM users_snapshot WHERE belong_date = '{$date}'";
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND status IN (" . implode(',', $status) . ")";
            }
            else {
                $sql .= " AND status = " . intval($status);
            }
        }
        if ($is_test != -1) {
            $sql .= " AND is_test = $is_test";
        }
        $sql .= " GROUP BY top_id ORDER BY top_id ASC";
        $result = $GLOBALS['db']->getAll($sql, array(),'top_id');

        return $result;
    }

    /**
     * 插入
     * @param unknown_type $data
     * @throws exception2
     */
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('users_snapshot', $data);
    }

    /**
     * 批量写入
     * @param  $fields	字段列表
     * @param  $datas	数据结构
     */
    static public function addItems($fields, $datas)
    {
        if (!is_array($datas) || !is_array($fields)) {
            throw new exception2('参数无效', 3409);
        }
        if (count($datas) == 0 || count($fields) == 0) {
            return true;
        }
		$sqlFields = implode(',', $fields);

		$fieldString = array();
        foreach ($datas as $v) {
        	$tmp = array();
        	foreach ($fields as $f) {
        		$tmp[] = $v[$f];
        	}
        	$fieldString[] = "('" . implode("','", $tmp) . "')";
        }
        $sql = "INSERT INTO users_snapshot ( $sqlFields ) VALUES " . implode(',', $fieldString);
        return $GLOBALS['db']->query($sql, array(), 'i');
    }

    /**
     * 更新
     * @param  $id
     * @param  $data
     */
    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('users_snapshot', $data, array('uss_id' => $id));
    }

    /**
     * 删除
     * @param  $id
     */
    static public function deleteItem($id)
    {
        $sql = "DELETE FROM users_snapshot WHERE uss_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * 按日期删除数据
     * @param $date
     */
    static public function deleteItemByBelongDate($date)
    {
        $sql = "DELETE FROM users_snapshot WHERE belong_date='{$date}'";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }
}

?>