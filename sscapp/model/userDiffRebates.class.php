<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userDiffRebates
{
    static public function getItem($id, $status = 1)
    {
        $sql = 'SELECT * FROM user_diff_rebates WHERE udr_id = ' . intval($id);
        if ($status != -1) {
            $sql .= ' AND status= ' . intval($status);
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //status为0禁用 1启用
    static public function getItems($lottery_id = 0, $issue = '', $package_id = 0, $status = -1, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        if (!$lottery_id && !$issue && !$package_id) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM user_diff_rebates WHERE 1';

        if ($lottery_id != 0) {
            if (is_array($lottery_id)) {
                $sql .= " AND lottery_id IN(" . implode(',', $lottery_id) . ")";
            }
            else {
                $sql .= " AND lottery_id = " . intval($lottery_id);
            }
        }
        if ($issue != '') {
            $sql .= " AND issue = '$issue'";
        }
        if ($package_id != 0) {
            if (is_array($package_id)) {
                $sql .= " AND package_id IN(" . implode(',', $package_id) . ")";
            }
            else {
                $sql .= " AND package_id = " . intval($package_id);
            }
        }
        if ($status != -1) {
            $sql .= ' AND status= ' . intval($status);
        }
        $sql .= ' ORDER BY udr_id ASC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);
//logdump($sql);
        return $result;
    }

    //得到最近没返点的udr_id，大大提高SQL查询速度
    static public function getRecentNoRebateId()
    {
        $cacheKey = "lastestRebateId_" . date('Y-m-d', strtotime('-1 days'));

        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) == false) {//ssc_userDiffRebates_lastestRebateId_2016-06-01
            $time = date('Y-m-d 00:00:00', strtotime('-1 days'));
            $sql = "SELECT udr_id FROM `user_diff_rebates` WHERE status = 0 AND create_time >='{$time}'";

            //通常不可能没有记录，但如果真没找到（比如春节休市后）则找最大一条记录
            if (!$result = $GLOBALS['db']->getRow($sql)) {
                $sql2 = "SELECT udr_id FROM `user_diff_rebates` ORDER BY udr_id DESC";
                $result = $GLOBALS['db']->getRow($sql2);
            }

            $result = ($result ? $result['udr_id'] : 0);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 86400 * 5); //保存5天，但每天0点运行时都会更新
        }

        return $result;
    }

    /**
     * 找到已判奖的订单列表，用于给crond跑返点
     * @param integer $recentNoRebateId 必须指定id号作为起始点 否则引起效率问题!
     * @return array
     */
    static public function getRecentNoRebates($recentNoRebateId, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        if (!is_numeric($recentNoRebateId)) {
            throw new exception2('参数无效', 3320);
        }
        $sql = "SELECT udr.* FROM `user_diff_rebates` udr LEFT JOIN issues i ON udr.lottery_id=i.lottery_id AND udr.issue=i.issue WHERE 1" .
                " AND udr.status = 0 && i.status_check_prize = 2 && udr_id >= $recentNoRebateId";
        $sql .= " ORDER BY udr.udr_id";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        $sql2 = "SELECT udr.* FROM `user_diff_rebates` udr WHERE 1" .
                " AND udr.status = 0 && udr.lottery_id = 15 && udr_id >= $recentNoRebateId";
        $sql2 .= " ORDER BY udr.udr_id";
        if ($start > -1) {
            $sql2 .= " LIMIT $start, $amount";
        }
        $result2 = $GLOBALS['db']->getAll($sql2);

        return array_merge($result, $result2);
    }


      static public function getUserDiffRebates($user_id, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
      {
      $sql = "SELECT udr.package_user_id,udr.package_id,udr.issue,udr.lottery_id,udr.diff_rebate,udr.rebate_amount,udr.create_time FROM user_diff_rebates udr LEFT JOIN users u ON udr.package_user_id=u.user_id WHERE udr.user_id = '$user_id' AND u.status = 8 AND udr.package_user_id != '$user_id'";
      if ($startDate != '') {
      $sql .= " AND udr.create_time >= '$startDate'";
      }
      if ($endDate != '') {
      $sql .= " AND udr.create_time <= '$endDate'";
      }

      $sql .= " AND udr.status=1 AND udr.rebate_amount > 0";
      $sql .= ' ORDER BY udr_id DESC';
      if ($start > -1) {
      $sql .= " LIMIT $start, $amount";
      }
      $result = $GLOBALS['db']->getAll($sql);

      return $result;
      }

      static public function getUserDiffRebatesNumber($user_id, $startDate = '', $endDate = '', $start = -1, $amount = DEFAULT_PER_PAGE)
      {
      $sql = "SELECT COUNT(*) AS count FROM user_diff_rebates udr LEFT JOIN users u ON udr.package_user_id=u.user_id WHERE udr.user_id = '$user_id' AND u.status = 8 AND udr.package_user_id != '$user_id'";
      if ($startDate != '') {
      $sql .= " AND udr.create_time >= '$startDate'";
      }
      if ($endDate != '') {
      $sql .= " AND udr.create_time <= '$endDate'";
      }
      $sql .= ' AND udr.status=1';
      $result = $GLOBALS['db']->getRow($sql);

      return $result['count'];
      }


    /* 得到某用户团队(自己和所有下级)的总返点
     * 老写法 260万记录需要1.5秒
      explain SELECT u.user_id, u.parent_id, u.parent_tree, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr, users u
      WHERE u.user_id = udr.user_id AND u.status = 8 AND udr.status = 1 AND udr.create_time >= '2014-05-01 00:00:00' AND udr.create_time <= '2014-05-05 00:00:00' AND (u.user_id=1025 OR FIND_IN_SET('1025',u.parent_tree))
      GROUP BY u.user_id ORDER BY u.user_id DESC;
      id 	select_type 	table 	type 	possible_keys 	key 	key_len 	ref 	rows 	Extra
      1 	SIMPLE 	udr 	ALL 	user_id 	NULL 	NULL 	NULL 	2669192 	Using where; Using temporary; Using filesort
      1 	SIMPLE 	u 	eq_ref 	PRIMARY 	PRIMARY 	4 	ssc_product.udr.user_id 	1 	Using where
     *
     * 140620 优化SQL写法，并增加user_id__create_time索引，避免udr全表扫描。260万记录只需要0.1秒
     * explain SELECT u.user_id, u.parent_id, u.parent_tree, SUM( udr.rebate_amount ) AS total_rebate
      FROM
      (select user_id,status,parent_id,parent_tree from users where (user_id=1025 OR FIND_IN_SET('1025',parent_tree)) and status =8 ) u, user_diff_rebates udr
      WHERE u.user_id = udr.user_id AND udr.status =1 AND udr.create_time >= '2014-05-01 00:00:00' AND udr.create_time <= '2014-05-05 00:00:00'
      GROUP BY u.user_id ORDER BY user_id DESC;
      id 	select_type 	table 	type 	possible_keys 	key 	key_len 	ref 	rows 	Extra
      1 	PRIMARY 	<derived2> 	ALL 	NULL 	NULL 	NULL 	NULL 	53 	Using temporary; Using filesort
      1 	PRIMARY 	udr 	ref 	user_id 	user_id 	3 	u.user_id 	914 	Using where
      2 	DERIVED 	users 	ALL 	PRIMARY 	NULL 	NULL 	NULL 	6385 	Using where
     *
     * 151215 优化
     * id 	select_type 	table 	type 	possible_keys 	key 	key_len 	ref 	rows 	Extra
      1 	SIMPLE 	udr 	range 	user_id 	user_id 	11 	NULL 	45936 	Using where; Using temporary; Using filesort
      1 	SIMPLE 	u 	eq_ref 	PRIMARY 	PRIMARY 	4 	ssc_product.udr.user_id 	1 	Using where
     * 查询性能提升明显
     *
     */
    static public function getTeamRebates($user_id, $is_test = -1, $lottery_id = 0, $modes = 0, $start_time = '', $end_time = '',$domain = '',$freeze = -1)
    {
        if($freeze == -1) {
            $sql = "SELECT u.user_id, u.parent_id, u.parent_tree, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id = u.user_id WHERE 1 AND u.status = 8 AND udr.status = 1";
            //得到用户层级
            // $teamUsers = users::getUserTree($user_id, true, 1, 8, 0);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
                'is_test' => 0,
            ]);
        }else{
            $sql = "SELECT u.user_id, u.parent_id, u.parent_tree, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id = u.user_id WHERE 1 AND (u.status = 8 OR u.status = 1) AND udr.status = 1";
            //得到用户层级
            // $teamUsers = users::getUserTree($user_id, true, 1, 8, 0,'',1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
                'is_test' => 0,
                'freeze' => 1
            ]);
        }

        $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'udr.user_id');
        if ($is_test != -1) {
            $sql .= " AND is_test = $is_test";
        }
        if ($start_time != '') {
            $sql .= " AND udr.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND udr.create_time <= '$end_time'";
        }
        if ($lottery_id != 0) {
            $sql .= " AND udr.lottery_id = $lottery_id";
        }
        if ($modes != 0) {
            $sql .= " AND udr.modes = '$modes'";
        }
        if ($domain != '') {
            $sql .= ' AND u.username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like "%$domain%")';
        }
        $sql .= ' GROUP BY udr.user_id ORDER BY udr.user_id DESC';   //这里order by必须为desc
        $result = $GLOBALS['db']->getAll($sql, array(),'user_id');

        return $result;
    }


    //得到任意级别用户的每个直接下级(含下属团队)为其贡献的返点记录 用于前台的会员报表
    static public function getChildContributeRebates($user, $start_time = '', $end_time = '')
    {
        //1.得到直属下级的贡献值
        /**
         * array (
          '3341' =>
          array (
          'user_id' => '3341',
          'total_contribute_rebate' => '0.0001',
          ),
          '3342' =>
          array (
          'user_id' => '3342',
          'total_contribute_rebate' => '1.23',
          ),
          )
         */
        $sql = "SELECT udr.package_user_id AS user_id, SUM( udr.rebate_amount ) AS total_contribute_rebate" .
                " FROM user_diff_rebates udr LEFT JOIN users u ON udr.package_user_id = u.user_id" .
                " WHERE udr.user_id = {$user['user_id']} AND u.parent_id = {$user['user_id']} AND udr.user_id != udr.package_user_id AND udr.status = 1";
        if ($start_time != '') {
            $sql .= " AND udr.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND udr.create_time <= '$end_time'";
        }
        $sql .= ' GROUP BY user_id';
        $result = $GLOBALS['db']->getAll($sql, array(),'user_id');

        //2.得到直属下级的所有孩子贡献值
        /*
         * SELECT SUBSTRING_INDEX( u.parent_tree, ',', 3 ) AS direct_child, SUM( udr.rebate_amount ) AS total_contribute_rebate
          FROM user_diff_rebates udr
          LEFT JOIN users u ON udr.package_user_id = u.user_id
          WHERE udr.user_id =3 && udr.user_id != udr.package_user_id
          GROUP BY direct_child
         */
        if (empty($user['parent_tree'])) {
            $userLevel = 2;
        }
        else {
            $userLevel = count(explode(',', $user['parent_tree'])) + 2;
        }
        $sql = "SELECT SUBSTRING_INDEX( u.parent_tree, ',', {$userLevel} ) AS direct_child, SUM( udr.rebate_amount ) AS total_contribute_rebate" .
                " FROM user_diff_rebates udr LEFT JOIN users u ON udr.package_user_id = u.user_id" .
                " WHERE u.parent_id != {$user['user_id']} AND udr.user_id = {$user['user_id']} AND udr.user_id != udr.package_user_id AND udr.status = 1";
        if ($start_time != '') {
            $sql .= " AND udr.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND udr.create_time <= '$end_time'";
        }
        $sql .= ' GROUP BY direct_child';
        $result2 = $GLOBALS['db']->getAll($sql, array(),'direct_child');

        //3.合并结果
        /**
         * array (
          '1260,3341' =>
          array (
          'direct_child' => '1260,3341',
          'total_contribute_rebate' => '0.2688',
          ),
          '1260,3342' =>
          array (
          'direct_child' => '1260,3342',
          'total_contribute_rebate' => '1.2345',
          ),
          )
         */
        foreach ($result2 as $selfChildUserId => $v) {
            $tmp = explode(',', $selfChildUserId);
            $directUserId = end($tmp);
            //list($selfUserId, $directUserId) = explode(',', $selfChildUserId);

            if (!isset($result[$directUserId])) {
                $result[$directUserId] = array('user_id' => $directUserId, 'total_contribute_rebate' => $v['total_contribute_rebate']);
            }
            else {
                $result[$directUserId]['total_contribute_rebate'] += $v['total_contribute_rebate'];
            }
        }

        return $result;
    }

    //批量插入提高速度
    static public function addItems($datas)
    {
        if (!is_array($datas)) {
            throw new exception2('参数无效', 3309);
        }
        if (empty($datas)) {
            return true;
        }
        $tmp = array();
        foreach ($datas as $v) {
            $tmp[] = "('{$v['user_id']}','{$v['top_id']}','{$v['lottery_id']}','{$v['issue']}','{$v['package_id']}','{$v['package_user_id']}','{$v['diff_rebate']}','{$v['rebate_amount']}','{$v['status']}','".date('Y-m-d H:i:s')."')";
        }
        $sql = "INSERT INTO user_diff_rebates (user_id,top_id,lottery_id,issue,package_id,package_user_id,diff_rebate,rebate_amount,status,create_time) VALUES " . implode(',', $tmp);

        return $GLOBALS['db']->query($sql, array(), 'i');
    }

    static public function addItem($data)
    {
        if (!is_array($data) || !isset($data['lottery_id']) || !isset($data['user_id']) || !isset($data['rebate'])) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('user_diff_rebates', $data);
    }

    static public function updateItem($udr_id, $data)
    {
        if (empty($udr_id) || !is_array($data)) {
            throw new exception2('参数无效');
        }

        if (is_array($udr_id)) {
            return $GLOBALS['db']->updateSM('user_diff_rebates', $data, array('udr_id IN'=>$udr_id), count($udr_id));
        }
        else {
            return $GLOBALS['db']->updateSM('user_diff_rebates', $data, array('udr_id'=>$udr_id));
        }
    }

}

?>