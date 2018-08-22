<?php

if (!defined('IN_LIGHT')) {
	die('KCAH');
}

class events
{
	/**
	 * 添加
	 */
	static public function addItem($data)
	{
		if (!is_array($data)) {
			throw new exception2('参数无效');
		}

		return $GLOBALS['db']->insert('events', $data);
	}

	/**
	 * 获得数据结果集
	 * @param type $username
	 * @param type $type
	 * @param type $startDate
	 * @param type $endDate
	 * @param type $admin
	 * @param type $start
	 * @param type $amount
	 * @return type
	 */
	static public function getItems($username = '', $type = '', $startDate = '', $endDate = '', $admin = '', $start = -1, $amount = DEFAULT_PER_PAGE)
	{
		$sql = 'SELECT t1.*, t2.username, t2.level, t3.username AS admin
			FROM events AS t1 
			LEFT JOIN users AS t2 ON (t2.user_id = t1.user_id) 
			LEFT JOIN admins AS t3 ON (t3.admin_id = t1.admin_id) WHERE 1';
        if (is_array($type)) {
            $sql .= " AND t1.type IN (" . implode(',', $type) . ')';
        }
		else if ($type != '' AND $type > -1) {
			$sql .= " AND t1.type = " . intval($type);
		}
		if ($startDate !== '') {
			$sql .= " AND t1.create_time >= '$startDate'";
		}
		if ($endDate !== '') {
			$sql .= " AND t1.create_time <= '$endDate'";
		}
		if ($username != '') {
			$sql .= " AND t2.username = '$username'";
		}
		if ($admin != '') {
			$sql .= " AND t3.username = '$admin'";
		}

		$sql .= ' ORDER BY t1.event_id DESC';
		if ($start > -1) {
			$sql .= " LIMIT $start, $amount";
		}
		$result = $GLOBALS['db']->getAll($sql);

		return $result;
	}

	/**
	 * 获得条数目
	 * @param type $user_id
	 * @param type $type
	 * @param type $startDate
	 * @param type $endDate
	 * @param type $admin_id
	 * @param type $start
	 * @param type $amount
	 * @return type
	 */
	static public function getItemsNumber($username = '', $type = '', $startDate = '', $endDate = '', $admin = '')
	{
		$sql = 'SELECT COUNT(*) AS count
			FROM events AS t1 
			LEFT JOIN users AS t2 ON (t2.user_id = t1.user_id) 
			LEFT JOIN admins AS t3 ON (t3.admin_id = t1.admin_id) WHERE 1';
		if (is_array($type)) {
            $sql .= " AND t1.type IN (" . implode(',', $type) . ')';
        }
		else if ($type != '' AND $type > -1) {
			$sql .= " AND t1.type = " . intval($type);
		}
		if ($startDate !== '') {
			$sql .= " AND t1.create_time >= '$startDate'";
		}
		if ($endDate !== '') {
			$sql .= " AND t1.create_time <= '$endDate'";
		}
		if ($username != '') {
			$sql .= " AND t2.username = '$username'";
		}
		if ($admin != '') {
			$sql .= " AND t3.username = '$admin'";
		}

        $result = $GLOBALS['db']->getRow($sql);

		return $result['count'];
	}

}

?>