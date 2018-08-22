<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class users
{

    public static $aPrizeMode = [1956, 1954, 1952];

    const CACHE_TIME = 600; //用户层级缓存10分钟

    //注：如需要独占读，必须先开始事务，否则没有效果
    static public function test($id, $status = 8, $needLock = false)
    {
        if (!$id) {
            return array();
        }

        if (is_numeric($id)) {
            $sql = 'SELECT * FROM users WHERE user_id = ' . intval($id);
        } elseif (preg_match('`^[a-zA-Z]\w{3,}$`', $id)) {
            $sql = 'SELECT * FROM users WHERE username = \'' . $id . '\'';
        } else {
            return array();
        }

        if ($status != -1) {
            $sql .= ' AND status = ' . intval($status);
        }
        if ($needLock) {
            $sql .= ' LIMIT 1';
        }

        return $GLOBALS['db']->query($sql);
    }

    //注：如需要独占读，必须先开始事务，否则没有效果

    /**
     * @param <Int> $id
     * @param <Array> $status 状态
     * @param <Array> $needLock 独占锁
     * @param <Array> $flag 1 平台ID 2微信ID
     * @param <Array> $freeze 是否统计冻结账号 1是 0否
     * @return <array>
     */
    static public function getItem($id, $status = 8, $needLock = false, $flag = 1,$freeze = -1, $field = '*')
    {

        if (!$id) {
            return array();
        }

        if (is_numeric($id)) {
            $sql = 'SELECT '.$field.' FROM users WHERE user_id =' . intval($id);
        } elseif (preg_match('`^[a-zA-Z]\w{3,}$`', $id) && $flag == 1) {
            $sql = 'SELECT ' . $field . ' FROM users WHERE username = \'' . $id . '\'';
        } elseif ($flag == 2) {
            $sql = 'SELECT ' . $field . ' FROM users WHERE open_id = \'' . $id . '\'';
        } else {
            return array();
        }

        if ($status != -1) {
            if($freeze == -1)
            {
                $sql .= ' AND status = ' . intval($status);
            }else{
                $sql .= ' AND (status = 1 OR status = ' . intval($status).')';
            }
        }

        if ($needLock) {
            $sql .= ' LIMIT 1';
        }
        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 根据条件查找用户列表
     * @param string $where 查询条件
     * @param string $field 查询字段
     * @return mixed 返回结构 array|null
     */
    static public function getListByCond($where = '', $field = '*')
    {
        if (empty($where)) {
            $sql = "SELECT $field FROM `users`  ";
        } else {
            $sql = "SELECT $field FROM `users` WHERE " . $where;
        }

        return $GLOBALS['db']->getAll($sql, array());
    }

    public static function getItemsListNum($where = '')
    {
        $sql='select COUNT(user_id) as num from users where 1 ';
        if(!empty($where))
        {
            $sql.=$where;
        }
        return $GLOBALS['db']->getRow($sql);
    }

    public static function getItemsList($where,$start = 1,$amount=20)
    {
        $sql='select * from users where 1 ';
        if(!empty($where))
        {
            $sql.=$where;
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }
    //$recursive为true时找包括自己的所有下级列表，为false时找包括自己的直接下级 传0当然表示所有总代
    static public function getItems($parent_id = -1, $includeSelf = true, $recursive = 0, $fields = array(), $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1, $start = -1, $amount = DEFAULT_PER_PAGE, $orderBy = [], $open_id = '', $type = -1,$freeze = -1)
    {
        if (is_array($fields) && count($fields)) {
            $sql = "SELECT " . implode(',', $fields) . " FROM `users` WHERE 1";
        } else {
            $sql = "SELECT * FROM `users` WHERE 1";
        }
        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', parent_tree)";
            } else {
                $sql .= " AND (parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if($freeze == -1) {
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status) . ")";
                } else {
                    $sql .= " AND status = " . intval($status);
                }
            }else{
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status). ",1)";
                } else {
                    $sql .= " AND status IN (1,$status) ";
                }
            }
        }
        if ($is_test != -1) {
            $sql .= " AND is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            if (preg_match('`^\d+$`Ui', $username)) {
                $sql .= " AND user_id = '$username'";
            } else {
                $sql .= " AND username = '$username'";
            }
        }
        if ($reg_ip != '') {
            $sql .= " AND (reg_ip = '$reg_ip' OR last_ip = '$reg_ip')";
        }
        if ($real_name != '') {
            $sql .= " AND real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND email = '$email'";
        }
        if ($open_id != '') {
            $sql .= " AND open_id = '$open_id'";
        }
        if ($type != -1) {
            $sql .= " AND type = $type";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND deposit_num = 0";
            } else {
                $sql .= " AND deposit_num > 0";
            }
        }
        if ($orderBy && is_array($orderBy)) {
            foreach ($orderBy as $orderName => $by) {
                $sql .= " ORDER BY " . $orderName;
                $sql .= $by == -1 ? ' DESC' : ' ASC';
                break;
            }
        } else {
            $sql .= " ORDER BY user_id ASC";
        }

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }
/******************* snow 获取总代下的所有用户id 并组合成in 需要的字符串 start********************************************/

    /**
     * author snow
     * 根据总代名称获取所有下级用户id
     * @param $user_name  string 用户名
     * @return string
     */
    public static function getUsersByUserName($user_name = 'dcsite')
    {
        $sql =<<<SQL
SELECT user_id FROM users WHERE top_id IN (SELECT user_id from users WHERE username = '{$user_name}') OR username = '{$user_name}'
SQL;

        $tmpArr = array_keys($GLOBALS['db']->getAll($sql, [], 'user_id'));
        $strSql= -1;
        if (is_array($tmpArr) && !empty($tmpArr))
        {
            $strSql = implode(',', $tmpArr);
        }

        return ' (' . $strSql . ')';

    }

    /**
     * author snow
     * 根据总代名称获取所有下级用户id
     * @param $user_name  string 用户名
     * @return string
     */
    public static function getUsersSqlByUserName($user_name = 'dcsite')
    {
        $sql =<<<SQL
SELECT a.user_id FROM users AS a,users AS b WHERE a.top_id = b.user_id AND b.username = '{$user_name}'
SQL;
        $result = $GLOBALS['db']->getAll($sql, [], 'user_id');
        $tmpArr = array_keys($result);
        if (!empty($tmpArr)){
           return ' (' .  implode(',', $tmpArr) . ')';
        }
        return ' (0)';

    }


    /******************* snow 获取总代下的所有用户id 并组合成in 需要的字符串 end  ****************************************/
    static public function getItemsNumber($parent_id = -1, $includeSelf = true, $recursive = 0, $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1,$freeze = -1)
    {
        $sql = "SELECT COUNT(*) AS count FROM `users` WHERE 1";
        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', parent_tree)";
            } else {
                $sql .= " AND (parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if($freeze == -1) {
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status) . ")";
                } else {
                    $sql .= " AND status = " . intval($status);
                }
            }else{
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status). ",1)";
                } else {
                    $sql .= " AND status IN (1,$status) ";
                }
            }
        }
        if ($is_test != -1) {
            $sql .= " AND is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            $sql .= " AND username = '$username'";
        }
        if ($reg_ip != '') {
            $sql .= " AND reg_ip = '$reg_ip'";
        }
        if ($real_name != '') {
            $sql .= " AND real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND deposit_num = 0";
            } else {
                $sql .= " AND deposit_num > 0";
            }
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    /************************** snow 复制方法用于脚本统计,不统计dcsite 总代下的人数 start*********************************/
    /**
     * 获取新增加用户数据 ,排除相应总代
     * @param int $parent_id        父级id
     * @param bool $includeSelf     是否包含下级
     * @param int $recursive
     * @param int $status           状态
     * @param int $is_test          是否测试
     * @param string $startDate     开始时间
     * @param string $endDate       结束时间
     * @param string $lastStartDate
     * @param string $lastEndDate
     * @param string $username
     * @param string $reg_ip
     * @param string $real_name
     * @param string $mobile
     * @param string $qq
     * @param string $email
     * @param int $has_deposited
     * @param int $freeze
     * @return mixed
     */
    static public function getItemsNumberExclude($parent_id = -1, $includeSelf = true, $recursive = 0, $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1,$freeze = -1)
    {
        $sql = "SELECT COUNT(*) AS count FROM `users` WHERE 1";
        $sql .= ' AND user_id NOT IN ' . self::getUsersSqlByUserName('dcsite');
        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', parent_tree)";
            } else {
                $sql .= " AND (parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if($freeze == -1) {
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status) . ")";
                } else {
                    $sql .= " AND status = " . intval($status);
                }
            }else{
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status). ",1)";
                } else {
                    $sql .= " AND status IN (1,$status) ";
                }
            }
        }
        if ($is_test != -1) {
            $sql .= " AND is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            $sql .= " AND username = '$username'";
        }
        if ($reg_ip != '') {
            $sql .= " AND reg_ip = '$reg_ip'";
        }
        if ($real_name != '') {
            $sql .= " AND real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND deposit_num = 0";
            } else {
                $sql .= " AND deposit_num > 0";
            }
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    /************************** snow 复制方法用于脚本统计,不统计dcsite 总代下的人数 end  *********************************/
    /**
     * create  snow  create time 2017-08-30 16:45
     * 添加方法 ,以实现添加显示会员上级名称的功能
     * @param int $parent_id
     * @param bool|true $includeSelf
     * @param int $recursive
     * @param array $fields
     * @param int $status
     * @param int $is_test
     * @param string $startDate
     * @param string $endDate
     * @param string $lastStartDate
     * @param string $lastEndDate
     * @param string $username
     * @param string $reg_ip
     * @param string $real_name
     * @param string $mobile
     * @param string $qq
     * @param string $email
     * @param int $has_deposited
     * @param array $orderBy
     * @param int $start
     * @param int $amount
     * @return mixed
     */
    static public function getItemsIncludeParentByLike($parent_id = -1, $includeSelf = true, $recursive = 0, $fields = array(), $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1, $orderBy = [], $start = -1, $amount = DEFAULT_PER_PAGE)
    {

//		$sql = "SELECT u.*, (SELECT s.expire_time FROM sessions s WHERE u.user_id = s.user_id ORDER BY s.expire_time DESC LIMIT 1) expire_time FROM users u WHERE 1";
        //>>修改sql语句  查询出会员上级 名称  用于前端显示   by snow  2017-08-30  16:11
        $sql =<<<SQL
SELECT b.username AS parent_name,b.is_test as parent_is_test,b.type as parent_type,u.* FROM users u
LEFT JOIN users as b ON u.parent_id = b.user_id WHERE 1
SQL;


        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', u.parent_tree)";
            } else {
                $sql .= " AND (u.parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR u.user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND u.status IN (" . implode(',', $status) . ")";
            } else {
                $sql .= " AND u.status = " . intval($status);
            }
        }
        if ($is_test != -1) {
            $sql .= " AND u.is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND u.reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND u.reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND u.last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND u.last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            if (preg_match('`^\d+$`Ui', $username)) {
                $sql .= " AND u.user_id = '$username'";
            } else {
                $sql .= " AND u.username = '$username'";
            }
        }
        if ($reg_ip != '') {
            $sql .= " AND (u.reg_ip like '%$reg_ip%' OR u.last_ip like '%$reg_ip%')";
        }
        if ($real_name != '') {
            $sql .= " AND u.real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND u.mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND u.qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND u.email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND u.deposit_num = 0";
            } else {
                $sql .= " AND u.deposit_num > 0";
            }
        }

        if ($orderBy && is_array($orderBy)) {
            foreach ($orderBy as $orderName => $by) {
                $sql .= " ORDER BY u." . $orderName;
                $sql .= $by == -1 ? ' DESC' : ' ASC';
                break;
            }
        } else {
            $sql .= " ORDER BY u.user_id ASC";
        }

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }
    static public function getItemsIncludeParentByLikeNum($parent_id = -1, $includeSelf = true, $recursive = 0, $fields = array(), $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1)
    {

        $sql = "SELECT COUNT(u.user_id) as num FROM users u  WHERE 1";
        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', u.parent_tree)";
            } else {
                $sql .= " AND (u.parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR u.user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND u.status IN (" . implode(',', $status) . ")";
            } else {
                $sql .= " AND u.status = " . intval($status);
            }
        }
        if ($is_test != -1) {
            $sql .= " AND u.is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND u.reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND u.reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND u.last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND u.last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            if (preg_match('`^\d+$`Ui', $username)) {
                $sql .= " AND u.user_id = '$username'";
            } else {
                $sql .= " AND u.username = '$username'";
            }
        }
        if ($reg_ip != '') {
            $sql .= " AND (u.reg_ip like '%$reg_ip%' OR u.last_ip like '%$reg_ip%')";
        }
        if ($real_name != '') {
            $sql .= " AND u.real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND u.mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND u.qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND u.email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND u.deposit_num = 0";
            } else {
                $sql .= " AND u.deposit_num > 0";
            }
        }



        return $GLOBALS['db']->getRow($sql, array());
    }

    /**
     * 生成查询获取单个或多个用户存款总金额的sql
     * @param $user_ids  int || array  用户id
     * @param string $startDate  string 开始日期
     * @param string $endDate  string 结束日期
     * @param string $depositAmount   numeric  存款金额
     * @return string   返回的sql
     * @throws exception2
     */
    private function _getUserSumAmountSql($user_ids, $startDate = '', $endDate = '', $depositAmount)
    {
        $sql = 'SELECT user_id, SUM(amount) as total_deposit FROM deposits having total_deposit > ' . $depositAmount .' WHERE 1';

        if (is_array($user_ids) && !empty($user_ids)) {
            foreach ($user_ids as &$v) {
                if (!is_numeric($v)) {
                    throw new exception2('参数无效');
                }
            }
            $sql .= ' AND user_id IN(' . implode(',', $user_ids) . ')';
        } elseif (is_numeric($user_ids)) {
            $sql .= ' AND user_id = ' . $user_ids;
        }

        //这里应该用finish_time，因为只有执行时用户资金才会变化
        if ($startDate !== '') {
            $sql .= ' AND finish_time >= \'' . $startDate . '\'';
        }
        if ($endDate !== '') {
            $sql .= ' AND finish_time <= \'' . $endDate . '\'';
        }
        $sql .= ' AND status = 8 GROUP BY user_id ORDER BY user_id ASC';
        return $sql;
    }

    /**
     * create  snow  create time 2017-08-30 16:45
     *
     * @param int $parent_id
     * @param bool|true $includeSelf
     * @param int $recursive
     * @param array $fields
     * @param int $status
     * @param int $is_test
     * @param string $startDate
     * @param string $endDate
     * @param string $lastStartDate
     * @param string $lastEndDate
     * @param string $username
     * @param string $reg_ip
     * @param string $real_name
     * @param string $mobile
     * @param string $qq
     * @param string $email
     * @param int $has_deposited
     * @param array $orderBy
     * @param int $start
     * @param int $amount
     * @return mixed
     */
    static public function getItemsIncludeParentByLikeOnline($parent_id = -1, $includeSelf = true, $recursive = 0, $fields = array(), $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1, $orderBy = [], $start = -1, $amount = DEFAULT_PER_PAGE ,$onlineUserIdList,$online)
    {
        if(empty($onlineUserIdList))
        {
            return [];
        }
        $strCond = implode(',',$onlineUserIdList);
        if($online === 0) {
            $sql = "SELECT b.username AS parent_name,b.is_test as parent_is_test,b.type as parent_type,u.* FROM users u LEFT JOIN users as b ON u.parent_id = b.user_id WHERE  u.user_id NOT IN ($strCond) ";
        }else{
            $sql = "SELECT b.username AS parent_name,b.is_test as parent_is_test,b.type as parent_type,u.* FROM users u LEFT JOIN users as b ON u.parent_id = b.user_id WHERE  u.user_id IN ($strCond) ";
        }

        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', u.parent_tree)";
            } else {
                $sql .= " AND (u.parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR u.user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND u.status IN (" . implode(',', $status) . ")";
            } else {
                $sql .= " AND u.status = " . intval($status);
            }
        }
        if ($is_test != -1) {
            $sql .= " AND u.is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND u.reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND u.reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND u.last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND u.last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            if (preg_match('`^\d+$`Ui', $username)) {
                $sql .= " AND u.user_id = '$username'";
            } else {
                $sql .= " AND u.username = '$username'";
            }
        }
        if ($reg_ip != '') {
            $sql .= " AND (u.reg_ip like '%$reg_ip%' OR u.last_ip like '%$reg_ip%')";
        }
        if ($real_name != '') {
            $sql .= " AND u.real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND u.mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND u.qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND u.email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND u.deposit_num = 0";
            } else {
                $sql .= " AND u.deposit_num > 0";
            }
        }

        if ($orderBy && is_array($orderBy)) {
            foreach ($orderBy as $orderName => $by) {
                $sql .= " ORDER BY u." . $orderName;
                $sql .= $by == -1 ? ' DESC' : ' ASC';
                break;
            }
        } else {
            $sql .= " ORDER BY u.user_id ASC";
        }

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }

    static public function getItemsOnlineListNum($parent_id = -1, $includeSelf = true, $recursive = 0, $fields = array(), $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1, $orderBy = [], $onlineUserIdList,$online)
    {
        if(empty($onlineUserIdList))
        {
            return 0;
        }
        $strCond = implode(',',$onlineUserIdList);
        if($online === 0)
        {
            $sql = "SELECT COUNT(u.user_id) as num FROM users u  WHERE  u.user_id NOT IN ($strCond) ";
        }else {
            $sql = "SELECT COUNT(u.user_id) as num FROM users u  WHERE  u.user_id in ($strCond) ";
        }
        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', u.parent_tree)";
            } else {
                $sql .= " AND (u.parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR u.user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND u.status IN (" . implode(',', $status) . ")";
            } else {
                $sql .= " AND u.status = " . intval($status);
            }
        }
        if ($is_test != -1) {
            $sql .= " AND u.is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND u.reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND u.reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND u.last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND u.last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            if (preg_match('`^\d+$`Ui', $username)) {
                $sql .= " AND u.user_id = '$username'";
            } else {
                $sql .= " AND u.username = '$username'";
            }
        }
        if ($reg_ip != '') {
            $sql .= " AND (u.reg_ip like '%$reg_ip%' OR u.last_ip like '%$reg_ip%')";
        }
        if ($real_name != '') {
            $sql .= " AND u.real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND u.mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND u.qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND u.email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND u.deposit_num = 0";
            } else {
                $sql .= " AND u.deposit_num > 0";
            }
        }

        if ($orderBy && is_array($orderBy)) {
            foreach ($orderBy as $orderName => $by) {
                $sql .= " ORDER BY u." . $orderName;
                $sql .= $by == -1 ? ' DESC' : ' ASC';
                break;
            }
        } else {
            $sql .= " ORDER BY u.user_id ASC";
        }


        return $GLOBALS['db']->getRow($sql, array());
    }




    static public function getItemsByLike($parent_id = -1, $includeSelf = true, $recursive = 0, $fields = array(), $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1, $orderBy = [], $start = -1, $amount = DEFAULT_PER_PAGE)
    {

//		$sql = "SELECT u.*, (SELECT s.expire_time FROM sessions s WHERE u.user_id = s.user_id ORDER BY s.expire_time DESC LIMIT 1) expire_time FROM users u WHERE 1";
        $sql = "SELECT u.* FROM users u WHERE 1";

        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', u.parent_tree)";
            } else {
                $sql .= " AND (u.parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR u.user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND u.status IN (" . implode(',', $status) . ")";
            } else {
                $sql .= " AND u.status = " . intval($status);
            }
        }
        if ($is_test != -1) {
            $sql .= " AND u.is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND u.reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND u.reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND u.last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND u.last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            if (preg_match('`^\d+$`Ui', $username)) {
                $sql .= " AND u.user_id = '$username'";
            } else {
                $sql .= " AND u.username = '$username'";
            }
        }
        if ($reg_ip != '') {
            $sql .= " AND (u.reg_ip like '%$reg_ip%' OR u.last_ip like '%$reg_ip%')";
        }
        if ($real_name != '') {
            $sql .= " AND u.real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND u.mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND u.qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND u.email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND u.deposit_num = 0";
            } else {
                $sql .= " AND u.deposit_num > 0";
            }
        }

        if ($orderBy && is_array($orderBy)) {
            foreach ($orderBy as $orderName => $by) {
                $sql .= " ORDER BY u." . $orderName;
                $sql .= $by == -1 ? ' DESC' : ' ASC';
                break;
            }
        } else {
            $sql .= " ORDER BY u.user_id ASC";
        }

        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }


    static public function getItemsNumberByLike($parent_id = -1, $includeSelf = true, $recursive = 0, $fields = array(), $status = 8, $is_test = -1, $startDate = '', $endDate = '', $lastStartDate = '', $lastEndDate = '', $username = '', $reg_ip = '', $real_name = '', $mobile = '', $qq = '', $email = '', $has_deposited = -1)
    {

        $sql = "SELECT COUNT(DISTINCT(u.user_id)) as count FROM users u WHERE 1";

        if ($parent_id != -1) {
            if ($recursive) {
                $sql .= " AND (FIND_IN_SET('$parent_id', u.parent_tree)";
            } else {
                $sql .= " AND (u.parent_id = $parent_id";
            }
            if ($includeSelf) {
                $sql .= " OR u.user_id = $parent_id"; //默认包含自己
            }
            $sql .= ")";
        }
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND u.status IN (" . implode(',', $status) . ")";
            } else {
                $sql .= " AND u.status = " . intval($status);
            }
        }
        if ($is_test != -1) {
            $sql .= " AND u.is_test = '$is_test'";
        }
        if ($startDate !== '') {
            $sql .= " AND u.reg_time >= '$startDate'";
        }
        if ($endDate !== '') {
            $sql .= " AND u.reg_time <= '$endDate'";
        }
        if ($lastStartDate !== '') {
            $sql .= " AND u.last_time >= '$lastStartDate'";
        }
        if ($lastEndDate !== '') {
            $sql .= " AND u.last_time <= '$lastEndDate'";
        }
        if ($username != '') {
            if (preg_match('`^\d+$`Ui', $username)) {
                $sql .= " AND u.user_id = '$username'";
            } else {
                $sql .= " AND u.username = '$username'";
            }
        }
        if ($reg_ip != '') {
            $sql .= " AND (u.reg_ip like '%$reg_ip%' OR u.last_ip like '%$reg_ip%')";
        }
        if ($real_name != '') {
            $sql .= " AND u.real_name = '$real_name'";
        }
        if ($mobile != '') {
            $sql .= " AND u.mobile = '$mobile'";
        }
        if ($qq != '') {
            $sql .= " AND u.qq = '$qq'";
        }
        if ($email != '') {
            $sql .= " AND u.email = '$email'";
        }
        if ($has_deposited != -1) {
            if ($has_deposited == 0) {
                $sql .= " AND u.deposit_num = 0";
            } else {
                $sql .= " AND u.deposit_num > 0";
            }
        }

        return $GLOBALS['db']->getRow($sql)['count'];
    }


    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }
        if (empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM users WHERE user_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }

    static public function getEgameRakebackUsers($ref_group_id)
    {
        if ($ref_group_id < 1) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT egame_rakeback.user_id, egame_rakeback.rakeback_amount, egame_rakeback.last_rakeback_time, users.parent_id, users.parent_id, users.top_id, users.balance, users.username FROM egame_rakeback LEFT JOIN users ON egame_rakeback.user_id = users.user_id WHERE users.ref_group_id =' . $ref_group_id;

        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }

    static public function getEgameUsers($top_id)
    {
        $sql = 'SELECT users.user_id, users.parent_id, users.top_id, users.username, users.level FROM egame_rakeback LEFT JOIN users ON egame_rakeback.user_id = users.user_id WHERE 1';

        if ($top_id != -1) {
            $sql .=  'users.top =' . $top_id;
        }

        return $GLOBALS['db']->getAll($sql, array(), 'user_id');
    }

    static public function getItemByUsername($username)
    {
        if ($username == '') {
            throw new exception2('参数无效');
        }

        if ($username == '') {
            return array();
        }

        $sql = 'SELECT * FROM users WHERE username=\'' . $username . '\'';

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 缓存用户的层级关系，默认读取包括自己的直接下级，这个使用很频繁，应充分利用缓存
     * @param int $parent_id 父级id
     * @param bool $includeSelf 包含自身
     * @param int $recursive 递归所有层级(所有下级)
     * @param int $status 用户状态
     * @param int $is_test 是否测试
     * @param string $domain 域名
     * @param int $freeze 是否冻结
     * @return mixed
     */
    static public function getUserTree($parent_id = -1, $includeSelf = true, $recursive = 0, $status = -1, $is_test = -1, $domain = '', $freeze = -1)
    {
        $cacheKey = __FUNCTION__ . '_' . $parent_id . '_' . $recursive . '_' . $domain;
        $paramArr = [];
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT user_id,username,parent_id,top_id,level,status,is_test,type,parent_tree,balance,ref_group_id FROM `users` WHERE 1';
            if ($parent_id != -1) {
                if ($recursive) {
                    $sql .= ' AND (FIND_IN_SET(:parent_id, parent_tree)';
                } else {
                    $sql .= ' AND (parent_id = :parent_id';
                }

                // 1.可以在包含自己的情况下带上这个条件,反之亦然.
                // 2.这里是为了无论是否包含自己都可以使用同一个缓存
                $sql .= ' OR user_id = :user_id)';

                $paramArr[':parent_id'] = $parent_id;
                $paramArr[':user_id'] = $parent_id;
            }
            if ($domain != '') {
                $sql .= ' AND username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like \'%' . $domain . '%\')';
            }

            $result = $GLOBALS['db']->getAll($sql, $paramArr, 'user_id');

            // 这里的 self::CACHE_TIME 只有10分钟
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        // 包含自身,看上方注释
        if (!$includeSelf) {
            unset($result[$parent_id]);
        }

        // 非冻结
        if ($freeze == -1) {
            if ($status != -1) {
                foreach ($result as $k => $v) {
                    if ($v['status'] != $status) {
                        unset($result[$k]);
                    }
                }
            }
            // 冻结
        } else {
            if ($status != -1) {
                foreach ($result as $k => $v) {
                    if (!in_array($v['status'], [1, 8])) {
                        unset($result[$k]);
                    }
                }
            }
        }

        // 是否测试
        if ($is_test != -1) {
            foreach ($result as $k => $v) {
                if ($v['is_test'] != $is_test) {
                    unset($result[$k]);
                }
            }
        }

        return $result;
    }

    /* 这个方法是拆分出字段的 begin */

    static public function getUserTreeField($option = [])
    {
        // 1.显示声明
        $field = $parent_id = $includeSelf = $recursive = $status = $is_test = $domain = $freeze = null;

        // 2.默认字段
        // 说明一下 field 参数 user_id status is_test 目前是必须存在的
        $initOption = [
            'field' => '`user_id`,`username`,`parent_id`,`top_id`,`level`,`status`,`is_test`,`type`,`parent_tree`,`balance`,`ref_group_id`', // 查询字段
            'parent_id' => -1, // 父级id
            'includeSelf' => true, // 包含自身
            'recursive' => 0, // 递归所有层级(所有下级)
            'status' => -1, // 用户状态
            'is_test' => -1, // 是否测试
            'domain' => '', // 域名
            'freeze' => -1, // 是否冻结
        ];

        // 3.合并传入参数
        $option = array_merge($initOption, $option);

        // 4.隐式赋值 ( 从数组中将变量导入到当前的符号表 )
        extract($option);
        // 上面的都走完过后就可以在下面用啦

        // 5.字段可以是数组形式
        if (is_array($field)) {
            !in_array('status', $field) && $field[] = 'status';
            !in_array('is_test', $field) && $field[] = 'is_test';

            natsort($field);
            $field = implode(',', $field);
        }

        // 6.键名要加上字段
        $cacheKey = 'getUserTreeField' . '_' . $field . '_' . $parent_id . '_' . $recursive . '_' . $domain;

        $result = redisGet($cacheKey, function () use ($field, $parent_id, $recursive, $domain) {
            $paramArr = [];
            $sql = 'SELECT ' . $field . ' FROM `users` WHERE 1';
            if ($parent_id != -1) {
                if ($recursive) {
                    $sql .= ' AND (FIND_IN_SET(:parent_id, parent_tree)';
                } else {
                    $sql .= ' AND (parent_id = :parent_id';
                }

                // 1.可以在包含自己的情况下带上这个条件,反之亦然.
                // 2.这里是为了无论是否包含自己都可以使用同一个缓存
                $sql .= ' OR user_id = :user_id)';

                $paramArr[':parent_id'] = $parent_id;
                $paramArr[':user_id'] = $parent_id;
            }
            if ($domain != '') {
                $sql .= ' AND username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like \'%' . $domain . '%\')';
            }

            return $GLOBALS['db']->getAll($sql, $paramArr, 'user_id');
        }, self::CACHE_TIME);

        // 包含自身,看上方注释
        if (!$includeSelf) {
            unset($result[$parent_id]);
        }

        // 非冻结
        if ($freeze == -1) {
            if ($status != -1) {
                foreach ($result as $k => $v) {
                    if ($v['status'] != $status) {
                        unset($result[$k]);
                    }
                }
            }
            // 冻结
        } else {
            if ($status != -1) {
                foreach ($result as $k => $v) {
                    if (!in_array($v['status'], [1, 8])) {
                        unset($result[$k]);
                    }
                }
            }
        }

        // 是否测试
        if ($is_test != -1) {
            foreach ($result as $k => $v) {
                if ($v['is_test'] != $is_test) {
                    unset($result[$k]);
                }
            }
        }

        return $result;
    }

    /* 这个方法是拆分出字段的 end */

    /**************************** snow 复制代码 ,用来排除dcsite****************************************/


    //缓存用户的层级关系，默认读取包括自己的直接下级，这个使用很频繁，应充分利用缓存
    /**
     * @param int $parent_id
     * @param bool $includeSelf
     * @param int $recursive
     * @param int $status
     * @param int $is_test
     * @param string $domain
     * @param int $freeze
     * @return mixed
     */
    static public function getUserTreeExclude($parent_id = -1, $includeSelf = true, $recursive = 0, $status = -1, $is_test = -1, $domain = '',$freeze = -1)
    {
        $cacheKey = __FUNCTION__ . '_' . $parent_id . '_' . $recursive . '_' . $domain;
        $paramArr = array();
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT user_id,username,parent_id,top_id,level,status,is_test,type,parent_tree,balance,ref_group_id FROM `users` WHERE 1';

            //>>添加条件 ,排除dcsite 总代数据
            $sql .= ' AND user_id NOT IN ' . users::getUsersSqlByUserName();
            if ($parent_id != -1) {
                if ($recursive) {
                    $sql .= ' AND (FIND_IN_SET(:parent_id, parent_tree)';
                } else {
                    $sql .= ' AND (parent_id = :parent_id';
                }
                $sql .= ' OR user_id = :user_id)';

                $paramArr[':parent_id'] = $parent_id;
                $paramArr[':user_id'] = $parent_id;
            }
            if ($domain != '') {
                $sql .= ' AND username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like \'%' . $domain . '%\')';
            }

            $result = $GLOBALS['db']->getAll($sql, $paramArr, 'user_id');

            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME); //放入缓存
        }

        if (!$includeSelf) {
            unset($result[$parent_id]);
        }
        if($freeze == -1) {
            if ($status != -1) {
                foreach ($result as $k => $v) {
                    if ($v['status'] != $status) {
                        unset($result[$k]);
                    }
                }
            }
        }else{
            if ($status != -1) {
                foreach ($result as $k => $v) {
                    if (!in_array($v['status'],[1,8])) {
                        unset($result[$k]);
                    }
                }
            }
        }
        if ($is_test != -1) {
            foreach ($result as $k => $v) {
                if ($v['is_test'] != $is_test) {
                    unset($result[$k]);
                }
            }
        }

        return $result;
    }

    /**************************** snow 复制代码 ,用来排除dcsite****************************************/

    static public function getAgentUserTree($parent_id = -1, $includeSelf = true, $recursive = 0, $status = -1, $is_test = -1, $domain = '', $isAgent = true)
    {
        $cacheKey = __FUNCTION__ . '_' . $parent_id . '_' . $recursive . '_' . $domain;
        $paramArr = array();
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = "SELECT user_id,username,parent_id,top_id,level,status,is_test,type,parent_tree,balance,ref_group_id FROM `users` WHERE 1";
            if ($parent_id != -1) {
                if ($recursive) {
                    $sql .= " AND (FIND_IN_SET(:parent_id, parent_tree)";
                } else {
                    $sql .= " AND (parent_id = :parent_id";
                }
                $sql .= " OR user_id = :user_id)";

                $paramArr[':parent_id'] = $parent_id;
                $paramArr[':user_id'] = $parent_id;
            }
            if ($domain != '') {
                $sql .= " AND username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like '%{$domain}%')";
            }

            if ($isAgent) {
                $sql .= " AND level != 100";
            }

            $result = $GLOBALS['db']->getAll($sql, $paramArr, 'user_id');

            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME); //放入缓存
        }

        if (!$includeSelf) {
            unset($result[$parent_id]);
        }

        if ($status != -1) {
            foreach ($result as $k => $v) {
                if ($v['status'] != $status) {
                    unset($result[$k]);
                }
            }
        }

        if ($is_test != -1) {
            foreach ($result as $k => $v) {
                if ($v['is_test'] != $is_test) {
                    unset($result[$k]);
                }
            }
        }

        return $result;
    }

    //得到所有上级 按级别从小一直到总代
    /********************** snow 增加缓存时长 start**************************************************/

    static public function getAllParent($user_id, $include_self = false,$freeze = -1)
    {
        $cacheKey = __FUNCTION__ . '_' . $user_id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            if($freeze == -1) {
                if (!$user = users::getItem($user_id, -1, false, 1, 1)) {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), CACHE_EXPIRE_LONG); //放入缓存
                    return array();
                }
            }else{
                if (!$user = users::getItem($user_id, -1)) {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), CACHE_EXPIRE_LONG); //放入缓存
                    return array();
                }
            }
            if ($user['parent_id'] == 0) {
                if ($include_self) {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey, array($user['user_id'] => $user), CACHE_EXPIRE_LONG); //放入缓存
                    return array($user['user_id'] => $user);
                } else {
                    $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), CACHE_EXPIRE_LONG); //放入缓存
                    return array();
                }
            }

            $ids = explode(',', $user['parent_tree']);
            $ids[] = $user['user_id'];
            $result = users::getItemsById($ids);
            $result = array_spec_key($result, 'level');
            krsort($result);
            $result = array_spec_key($result, 'user_id');
            if (count($result) != count($ids)) {
                log2("用户上级获取异常：" . "ids:" . var_export($ids, true) . "结果:" . var_export($result, true));
            }
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, CACHE_EXPIRE_LONG); //放入缓存
        }
        /********************** snow 增加缓存时长 end  **************************************************/
        if (!$include_self) {
            unset($result[$user_id]);
        }

        return $result;
    }
    /********************** snow 增加缓存时长 end  **************************************************/
    //得到直接下级及每个直接下级的孩子数
    static public function getChild($parent_id = -1, $status = 8)
    {
        $sql = "SELECT a.user_id,a.username,COUNT(b.user_id) AS count FROM users a LEFT JOIN users b ON a.user_id = b.parent_id WHERE a.parent_id = $parent_id";
        if ($status != -1) {
            if (is_array($status)) {
                $sql .= " AND a.status IN (" . implode(',', $status) . ")";
            } else {
                $sql .= " AND a.status = " . intval($status);
            }
        }
        $sql .= " GROUP BY a.user_id ORDER BY a.user_id";
        $result = $GLOBALS['db']->getAll($sql, array(), 'user_id');

        return $result;
    }

    //得到某个用户团队余额
    static public function getTeamBalance($user_id, $include_self = true, $is_test = -1,$freeze = -1)
    {
        if($freeze == -1) {
            $sql = "SELECT SUM(balance) AS total_balance FROM users WHERE status = 8";
        }else{
            $sql = "SELECT SUM(balance) AS total_balance FROM users WHERE (status = 8 or status = 1)";
        }
        if ($include_self) {
            $sql .= " AND (FIND_IN_SET('$user_id', parent_tree) OR user_id = " . intval($user_id) . ')';
        } else {
            $sql .= " AND FIND_IN_SET('$user_id', parent_tree)";
        }
        if ($is_test != -1) {
            $sql .= " AND is_test = $is_test";
        }
        if (!$result = $GLOBALS['db']->getRow($sql)) {
            return 0;
        }

        return $result['total_balance'];
    }

    //得到任意用户及其直属下级团队余额
    static public function getTeamBalances($user_id,$freeze = -1)
    {
        $result = array();
        if($freeze == -1)
        {
            // $teamUsers = users::getUserTree($user_id, true, 1, 8,-1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'balance', 'parent_tree'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
            ]);
        }else{
            // $teamUsers = users::getUserTree($user_id, true, 1, 8,-1,'',1);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'balance', 'parent_tree'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
                'freeze' => 1
            ]);
        }

        if (!$teamUsers) {
            throw new exception2('参数无效');
        }

        //获得直接下级
        foreach ($teamUsers as $v) {
            if ($v['parent_id'] == $user_id) {
                $result[$v['user_id']]['total_balance'] = 0;
            }
        }

        $self = $teamUsers[$user_id];
        $totalBalance = $self['balance'];

        if ($self['parent_id'] == 0) {
            $selfLevel = 0;
        } else {
            $selfLevel = count(explode(',', $self['parent_tree']));
        }

        unset($teamUsers[$user_id]);
        foreach ($teamUsers as $k => $v) {
            $totalBalance += $v['balance']; //团队余额量
            if (isset($result[$k])) {
                //直接下级
                $result[$k]['total_balance'] += $v['balance'];
            } else {
                //非直接下级
                $parent_ids = explode(',', $v['parent_tree']);
                if(isset($parent_ids[$selfLevel + 1])&& isset($result[$parent_ids[$selfLevel + 1]])) {
                    $result[$parent_ids[$selfLevel + 1]]['total_balance'] += $v['balance'];
                }
            }
        }

        $result[$user_id]['total_balance'] = $self['balance'];
        $result['team_total_balance'] = $totalBalance;

        return $result;
    }

    //得到所有总代团队余额
    static public function getTopTeamBalance($status = -1, $is_test = -1,$freeze = -1)
    {
        $sql = "SELECT user_id,top_id,username,sum(balance) AS total_balance FROM users WHERE 1";

        if ($status != -1) {
            if($freeze == -1) {
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status) . ")";
                } else {
                    $sql .= " AND status = " . intval($status);
                }
            }else{
                if (is_array($status)) {
                    $sql .= " AND status IN (" . implode(',', $status) . ",1)";
                } else {
                    $sql .= " AND (status = 1 or status = " . intval($status).')';
                }
            }
        }
        if ($is_test != -1) {
            $sql .= " AND is_test = $is_test";
        }
        $sql .= " GROUP BY top_id ORDER BY top_id ASC";
        $result = $GLOBALS['db']->getAll($sql, array(), 'top_id');

        return $result;
    }

    /**
     * 得到所有用户的统计信息
     * @return type
     */
    static public function getAllBalances()
    {
        $result = array();
        $sql = "SELECT user_id,balance FROM users ";
        $result = $GLOBALS['db']->getAll($sql, array(), 'user_id');

        return $result;
    }

    /**
     *  得到所有非总代的代理
     * @param type $status
     * @param type $is_test
     * @param type $userType 是否总代，或者非总代代理 , 0非总代 1总代 2非总代 +总代 3 普通会员
     * @return type
     */
    static public function getAllAgents($status = -1, $is_test = -1, $userType = 0)
    {
        $GLOBALS['db']->query('SET SESSION group_concat_max_len=1024000; ');
        $sql = "SELECT u1.user_id,u1.username,CAST(GROUP_CONCAT(u2.user_id)AS CHAR) AS user_id_children FROM users AS u1 LEFT JOIN users AS u2 "
            . "ON FIND_IN_SET(u1.user_id, u2.parent_tree)  ";
        if ($is_test != -1) {
            $sql .= " AND u2.is_test = $is_test";
        }
        if ($status != -1) {
            $sql .= " AND u2.status = $status";
        }
        $sql .= " WHERE 1=1";
        if ($status != -1) {
            $sql .= " AND u1.status = " . intval($status);
        }
        if ($is_test != -1) {
            $sql .= " AND u1.is_test = $is_test";
        }
        if ($userType == 1) {
            $sql .= " AND u1.level=0";
        } else if ($userType == 0) {
            //$sql .= " AND u1.level IN (1,2,3,4,5,6,7,8,9)";
            $sql .= " AND u1.level IN (1,2)";
        } else if ($userType == 2) {
            // $sql .= " AND u1.level IN (0,1,2,3,4,5,6,7,8,9)";
            $sql .= " AND u1.level IN (0,1,2)";
        } else if ($userType == 3) {
            $sql .= " AND u1.level=10";
        }
        $sql .= " GROUP BY u1.user_id";
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     *  得到所有非总代的代理
     * @param type $status
     * @param type $is_test
     * @param type $userType 是否总代，或者非总代代理 , 0非总代 1总代 2非总代 +总代 3 普通会员
     * @return type
     */
    static public function getAllAgentsWithId($status = -1, $is_test = -1, $userType = 0)
    {
        $GLOBALS['db']->query('SET SESSION group_concat_max_len=1024000; ');
        $sql = "SELECT u1.user_id,u1.username,CAST(GROUP_CONCAT(u2.user_id)AS CHAR) AS user_id_children FROM users AS u1 LEFT JOIN users AS u2 "
            . "ON FIND_IN_SET(u1.user_id, u2.parent_tree)  ";
        if ($is_test != -1) {
            $sql .= " AND u2.is_test = $is_test";
        }
        if ($status != -1) {
            $sql .= " AND u2.status = $status";
        }
        $sql .= " WHERE 1=1";
        if ($status != -1) {
            $sql .= " AND u1.status = " . intval($status);
        }
        if ($is_test != -1) {
            $sql .= " AND u1.is_test = $is_test";
        }
        if ($userType == 1) {
            $sql .= " AND u1.level=0";
        } else if ($userType == 0) {
            //$sql .= " AND u1.level IN (1,2,3,4,5,6,7,8,9)";
            $sql .= " AND u1.level IN (1,2)";
        } else if ($userType == 2) {
            // $sql .= " AND u1.level IN (0,1,2,3,4,5,6,7,8,9)";
            $sql .= " AND u1.level IN (0,1,2)";
        } else if ($userType == 3) {
            $sql .= " AND u1.level=10";
        }
        $sql .= " GROUP BY u1.user_id";
        $result = $GLOBALS['db']->getAll($sql, array(), 'user_id');

        return $result;
    }

    /**
     *  得到所有会员
     * @param type $status
     * @param type $is_test
     * @param type $parentUserType 父级类型 , 0非总代 1总代 2非总代 +总代 3 普通会员
     * @return type
     */
    static public function getAllUsers($status = -1, $is_test = -1, $parentUserType = 0)
    {
        $sql = "SELECT u1.user_id FROM users AS u1  WHERE 1=1";
        if ($status != -1) {
            $sql .= " AND u1.status = " . intval($status);
        }
        if ($is_test != -1) {
            $sql .= " AND u1.is_test = $is_test";
        }
        if ($parentUserType == 1) {

        } else if ($parentUserType == 0) {
            $sql .= " AND u1.level IN (1)";
        } else if ($parentUserType == 2) {
            $sql .= " AND u1.level IN (0,1)";
        }
        $parents = $GLOBALS['db']->getAll($sql);
        $result = array();
        if ($parents) {
            $parentSql = '';
            foreach ($parents as $parent) {
                if ($parentSql) {
                    $parentSql .= ',';
                }
                $parentSql .= $parent['user_id'];
            }

            $sql = "SELECT u1.user_id ,NULL AS user_id_children FROM users AS u1  WHERE 1=1";
            if ($status != -1) {
                $sql .= " AND u1.status = " . intval($status);
            }
            if ($is_test != -1) {
                $sql .= " AND u1.is_test = $is_test";
            }
            $sql .= " AND u1.level=10";
            $sql .= " AND u1.parent_id in ($parentSql) ";
            $result = $GLOBALS['db']->getAll($sql);
        }

        return $result;
    }

    /**
     *  得到所有会员的 各个平台的名字
     * @return type
     */
    static public function getAllUsernames()
    {
        $sql = "SELECT user_id,username,pt_username,bbin_username  FROM users ";
        $result = $GLOBALS['db']->getAll($sql, array(), 'user_id');

        return $result;
    }

    static public function getMaxUserId()
    {
        $sql = "SELECT MAX(user_id) AS max_user_id FROM users";
        $result = $GLOBALS['db']->getOne($sql);

        return $result;
    }

    //检查是否重复资料，注册时使用
    static public function checkUnique($real_name = '', $email = '', $qq = '', $mobile = '')
    {
        if (!$real_name && !$email && !$qq && !$mobile) {
            throw new exception2('参数无效');
        }
        $sql = "SELECT * FROM users WHERE 0";
        if ($real_name) {
            $sql .= " OR real_name='$real_name'";
        }
        if ($email) {
            $sql .= " OR email='$email'";
        }
        if ($qq) {
            $sql .= " OR qq='$qq'";
        }
        if ($mobile) {
            $sql .= " OR mobile='$mobile'";
        }

        if ($result = $GLOBALS['db']->getRow($sql)) {
            if ($real_name && $result['real_name'] == $real_name) {
                return -2;
            } elseif ($email && $result['email'] == $email) {
                return -3;
            } elseif ($qq && $result['qq'] == $qq) {
                return -4;
            } elseif ($mobile && $result['mobile'] == $mobile) {
                return -5; //重名也是可以的，但需要注意一下
            }
        }

        return 0;
    }

    //根据用户名重置登录密码
    static public function resetLoginPwd($username, $pwd)
    {
        if (!preg_match('`^[a-zA-Z]\w+$`', $username)) {
            throw new exception2('用户名非法');
        }
        if (!preg_match('`^\S{6,15}$`', $pwd)) {
            throw new exception2('密码长度不符规定，应由6个字符以上，不超过15个字符');
        }

        $sql = "UPDATE users SET pwd = '" . password_hash($pwd, PASSWORD_DEFAULT) . "',remark=concat(remark,'用户于" . date('Y-m-d H:i:s') . "自行修改密码;') WHERE username='$username' LIMIT 1";

        return $GLOBALS['db']->query($sql, array(), 'u');
    }

    //用户登录 $pwd已经是md5过的密码 返回非数组表示出错
    static public function login($username, $pwd, $secpwd = '', $first_flag = 2, $frm = 1)
    {
        if (!preg_match('`^[a-zA-Z]\w{5,15}$`i', $username)) {
            return '用户名不正确';
        }

        $flag = 0;
        $str = '';
        $user_id = 0;

        if (!$user = users::getItem($username, -1)) {
            $flag = -1;
            $str = '用户不存在';
        }

        if ($user) {

            $user_id = $user['user_id'];
            if ($GLOBALS['mc']->get('lock', $user_id)) {
                $flag = -3;
                $str = '由于您4次输入密码，帐号已被锁定，请明天尝试，或者联系客服';
            } else {
                if ($user['status'] != 8) {
                    $flag = -3;
                    $str = '您的帐户存在异常，请联系上级';
                } else {
                    if ($secpwd) {
                        if ($user['secpwd'] != $secpwd) {
                            $flag = -2;
                            $todayLoginFailedTimes = userLogs::getItemsNumber($username, 'login', '', date('Y-m-d'), '', -2);
                            $str = "用户名或资金密码不正确，您有" . (4 - $todayLoginFailedTimes) . "次重试机会";
                        }
                    } else {
                        if (!password_verify($pwd, $user['pwd'])) {
                            $flag = -2;
                            $todayLoginFailedTimes = userLogs::getItemsNumber($username, 'login', '', date('Y-m-d'), '', -2);
                            $str = "用户名或密码不正确，您有" . (4 - $todayLoginFailedTimes) . "次重试机会";
                        } else {//这里不需要新用户弹出协议层了
                            //存在几种情况下才弹出
                            if ($user['last_ip'] === '0.0.0.0' && $user['last_time'] === '0000-00-00 00:00:00' && $first_flag !== 2) {
                                return '必须同意协议';
                            }
                        }
                    }
                }
            }
            if ($flag == -2) {
                //判断是否达到5次重试机会 如果是就冻结此用户
                if ($todayLoginFailedTimes >= 4) {
                    $GLOBALS['mc']->set('lock', $user_id, 1, ((strtotime(date('Y-m-d 00:00:00')) + 60 * 60 * 24) - time()));
                    //$sql = "UPDATE users SET last_ip='" . $GLOBALS['REQUEST']['client_ip'] . "', last_time = NOW(), status = 1, remark = '密码输错5次自动冻结' WHERE username='$username' LIMIT 1";
                    //$GLOBALS['db']->query($sql);
                    //重置今天的登录失败重试记录
                    userLogs::resetTodayFailedRecord($user['user_id']);
                    $str = '由于您4次输入密码，帐号已被锁定，请明天尝试，或者联系客服';
                }
            }

            //最后判断域名是否允许访问
            // $flag2 = 0;
            // $domains = domains::getUserDomains($user['top_id']);
            // $domains = reset($domains);
            // if (is_array($domains)) {
            // 	foreach ($domains as $v) {
            // 		if ($v['name'] == DOMAIN) {
            // 			$flag2 = 1;
            // 		}
            // 	}
            // }
            // if (!$flag2) {
            // 	$flag = -9;
            // 	$str = 'Error route';
            // }

            //只有本站用户才记登录日志 1表示登录成功 负数表示失败的状态值
            userLogs::addLog($flag < 0 ? $flag : 1, $str, $username, $user_id, $frm);
        }

        if ($flag == 0) {
            $str = '登录成功';
            $sql = "UPDATE users SET reg_ip = if(reg_ip = '0.0.0.0','{$GLOBALS['REQUEST']['client_ip']}',reg_ip) ,last_ip='" . $GLOBALS['REQUEST']['client_ip'] . "', last_time = '" . date('Y-m-d H:i:s') . "' WHERE user_id=" . $user['user_id'];
            if (!$GLOBALS['db']->query($sql, array(), 'u')) {
                $flag = -4;
                $str = "更新用户信息出错(errCode=-4)";
            }

            if (isset($todayLoginFailedTimes) && $todayLoginFailedTimes) {
                //重置今天的登录失败重试记录
                userLogs::resetTodayFailedRecord($user['user_id']);
            }

            //pt 自动创建帐号密码 没接入PT暂时关闭
            // if (!$user['pt_username'] || !$user['pt_pwd']) {
            // 	$PT = new PT();
            // 	$result = $PT->createPlayer($user['username'], $user['pwd']);
            // 	if ($result && $result['errno'] == 0) {
            // 		users::updateItem($user['user_id'], array('pt_username' => $result['username'], 'pt_pwd' => $result['password']));
            // 	}
            // }
        }

        if ($flag < 0) {
            return $str;
        }

        return $user;
    }

    /**
     *  模拟登录,针对移动端普通登录&&微信用户无需登录步骤
     * @param string $username 也可以表示openid
     * @param string $pwd
     * @param int $type 为普通登录 2微信登录
     * @return bool
     */
    static function imitateLogin($username, $pwd = '', $type = 1)
    {
        if ($type == 2) {//如果是open_id
            $user = users::getItem($username, 8, false, 2);
        } else {
            $user = users::getItem($username);
            if ($user) {
                $sql = "UPDATE users SET reg_ip = if(reg_ip = '0.0.0.0','{$GLOBALS['REQUEST']['client_ip']}',reg_ip) ,last_ip='" . $GLOBALS['REQUEST']['client_ip'] . "', last_time = '" . date('Y-m-d H:i:s') . "' WHERE user_id=" . $user['user_id'];
                if (!$GLOBALS['db']->query($sql, array(), 'u')) {
                    return false;
                }
            }
        }

        if (!is_array($user) || empty($user)) {
            return false;
        }

        //登录信息写入session
        $GLOBALS['SESSION']['user_id'] = $user['user_id'];
        $GLOBALS['SESSION']['open_id'] = $user['open_id'];
        $GLOBALS['SESSION']['username'] = $user['username'];
        $GLOBALS['SESSION']['nick_name'] = $user['nick_name'];
        $GLOBALS['SESSION']['wechat_name'] = $user['wechat_name'];
        $GLOBALS['SESSION']['is_test'] = $user['is_test'];
        $GLOBALS['SESSION']['level'] = $user['level'];
        $GLOBALS['SESSION']['parent_id'] = $user['parent_id'];
        $GLOBALS['SESSION']['top_id'] = $user['top_id'];
        $GLOBALS['SESSION']['group_id'] = $user['group_id'];
        $GLOBALS['SESSION']['last_ip'] = $user['last_ip'];
        $GLOBALS['SESSION']['last_time'] = $user['last_time'];
        $GLOBALS['SESSION']['balance'] = $user['balance'];
        $GLOBALS['SESSION']['is_tourist'] = $user['is_tourist'];
        //登录成功后记录其登录来源
        $GLOBALS['SESSION']['frm'] = 2;

        return true;
    }

    /**
     *  AJAX生成推广代码
     * @param type $userId
     * @param type $prize_group
     * @param type $userType 1代理 2 用户
     * @param type $flag 1pc 2mobile 3oauth2
     * @param type $regType 1auto 2manual
     * @return type
     */
    static public function generalPromoteURL($userId, $prize_group, $userType, $flag = 1, $regType)
    {
        if ($userType == 2 && in_array($prize_group, array_keys(userRebates::$highRebates))) {
            return array('errno' => 2201, 'errstr' => '高点帐号只能是代理，不能开给会员');
        }

        // if($flag == 1){
        // 	$domainConf = 'site_main_domain';
        // }elseif ($flag == 2 || $flag == 3) {
        // 	$domainConf = 'mobile_site_main_domain';
        // }

        //  $config = config::getConfig($domainConf);
        // if($config == NULL){
        // 	return '';
        // }
        // $domains = explode(',', $config);
        // $domain = $domains[array_rand($domains, 1)];
        $domain = $_SERVER['HTTP_HOST'];
        $url = '';

        if ($regType == 1) {
            $url = "http://{$domain}/?a=autoReg&var=" . urlencode(encode($userId . "|" . $prize_group . "|" . $userType));
        } else {
            $url = "http://{$domain}/?a=register&var=" . urlencode(encode($userId . "|" . $prize_group . "|" . $userType));
        }

        if ($flag == 3) {
            $appid = config::getConfig('appid');
            $webUrl = urlencode($url);
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$webUrl}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
        }

        return $url;
    }

    /**
     *  AJAX生成推广代码
     * @param type $userId
     * @param type $prize_group
     * @param type $userType 1代理 2 用户
     * @param type $flag 1pc 2mobile 3oauth2
     * @param type $regType 1auto 2manual
     * @return type
     */
    static public function generalPromoteURLC($link)
    {
            $appid = config::getConfig('appid');
            $webUrl = urlencode($link);
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$webUrl}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";

        return $url;
    }

    /**
     * 非总代用户开户
     * @param <String> $username
     * @param <String> $password
     * @param <Int> $type  2一代理 3普通 4会员
     * @param <Array> $setPrizeGroup 下级返点值
     * @param <Array> $user 代理自己
     * @param <Array> $way 1 上级开号 2注册链接
     * @return <type>
     */
    static public function addUser($username, $password, $setPrizeGroup, $user, $addtionalData = array(), $way = 1, $qq = '', $is_tourist = 0, $open_id = '', $wechat_name = '')
    {
        if (!is_array($user) || !$username || !$password) {
            throw new exception2('参数无效');
        }

        if (is_numeric($qq)) {
            if (!preg_match('`^[1-9]\d{4,11}$`', $qq)) {
                throw new exception2('qq号码有误');
            }
        } else {
            $qq = '';
        }

        //先检查是否用户名重复
        if (users::getItem($username, -1)) {
            throw new exception2("该用户{$username}已存在");
        }

        if (!$parentIds = array_keys(users::getAllParent($user['user_id'], true))) {
            throw new exception2('代理层级错误');
        }
        //150701 允许总代降级后 有可能一代比总代ID小 上级ID不一定比下级大 因此不能按ID作为排序依据
        //sort($parentIds);
        krsort($parentIds);

        //限制1 会员不允许开下级
        if ($user['group_id'] == 4) {
            throw new exception2('会员不能开下级');
        }

        //测试账号开的下级号,默认都是测试号
        if ($user['is_test'] == 1) {
            $is_test = 1;
        } else {
            $is_test = 0;
        }

        if (!in_array($setPrizeGroup, array_keys(userRebates::addSubPrizeModes($user)))) {
            throw new exception2("返点值设定不正确");
        }

        // $childRebates = userRebates::getRebateByPrizeMode($setPrizeGroup);

        //开始事务
        $GLOBALS['db']->startTransaction();

        //1.添加至users表
        $addtionalData['nick_name'] = !isset($addtionalData['nick_name']) ? '无昵称' : $addtionalData['nick_name'];
        $addtionalData['mobile'] = !isset($addtionalData['mobile']) ? '' : $addtionalData['mobile'];
        $addtionalData['real_name'] = !isset($addtionalData['real_name']) ? '' : $addtionalData['real_name'];

        $reg_ip = $way == 1 ? '0.0.0.0' : $GLOBALS['REQUEST']['client_ip'];

        $level = $way == 1 ? $user['level'] + 1 : 100;

        if ($level == 0) {
            $group_id = 1;
        } elseif ($level == 1) {
            $group_id = 2;
        } elseif ($level > 1 && $level < 100) {
            $group_id = 3;
        } else {
            $group_id = 4;
        }

        $data = array(
            'open_id' => $open_id,
            'username' => $username,
            'pwd' => password_hash($password, PASSWORD_DEFAULT),
            'secpwd' => '', //资金密码由客户自己设置
            'safe_pwd' => '', //安全码由客户自己设置
            'level' => $level, //0总代 1一代 2二代 3三代 4四代 5五代 10会员
            'parent_id' => $user['user_id'],
            'top_id' => $user['top_id'], //未知，需添加后才知道
            'parent_tree' => implode(',', $parentIds),
            'nick_name' => $addtionalData['nick_name'],
            'real_name' => $addtionalData['real_name'],
            'wechat_name' => $wechat_name,
            'balance' => 0,
            'is_test' => $is_test,
            'is_tourist' => $is_tourist,
            'group_id' => $group_id,
            'reg_src' => filter_has_var(INPUT_SERVER, 'HTTP_HOST') ? filter_input(INPUT_SERVER, 'HTTP_HOST') : '', //注册用户时的域名
            'reg_ip' => $reg_ip,
            'reg_proxy_ip' => $GLOBALS['REQUEST']['proxy_ip'],
            'reg_time' => date('Y-m-d H:i:s'),
            'last_ip' => '0.0.0.0',
            'last_time' => '0000-00-00 00:00:00',
            'user_rank' => '0',
            'remark' => '',
            'status' => 8, //0已删除 1冻结 8正常
            'admin_id' => 0,
            'qq' => $qq,
            'mobile' => $addtionalData['mobile'],
//			'quota' => self::generateQuota($level,$setPrizeGroup),
        );

        if (!users::addItem($data)) {
            throw new exception2('添加用户资料失败');
        }
        if (!$user_id = $GLOBALS['db']->insert_id()) {
            $GLOBALS['db']->rollback();
            throw new exception2('事务失败1');
        }

        //2.保存返点设置
        if (!userRebates::saveRebate($user_id, $setPrizeGroup)) {
            $GLOBALS['db']->rollback();
            throw new exception2('保存用户返点失败');
        }

        //It seems OK
        if (!$GLOBALS['db']->commit()) {
            $GLOBALS['db']->rollback();
            throw new exception2('提交事务失败');
        }

        return true;
    }

    //总代开户
    static public function addTop($data, $domainIds, $prize_mode)
    {

       // if (!is_array($domainIds) || !count($domainIds)) {
         //   throw new exception2('没有指定域名');
       // }

        //开始事务
        $GLOBALS['db']->startTransaction();
        //先检查是否用户名重复
        if (users::getItem($data['username'], -1)) {
            throw new exception2("该用户{$data['username']}已存在");
        }

        if (!users::addItem($data)) {
            throw new exception2('addItem失败');
        }
        if (!$top_id = $GLOBALS['db']->insert_id()) {
            $GLOBALS['db']->rollback();
            throw new exception2('事务失败1');
        }
        if (!users::updateItem($top_id, array('top_id' => $top_id))) {
            $GLOBALS['db']->rollback();
            throw new exception2('事务失败2');
        }
        //添加至总代域名表user_domains
        $tops = users::getItems(0);
        //$domains = domains::getItemsById($domainIds);
        $count = 0;
      //  foreach ($domains as $v) {
            //分配域名
          //  if (domains::getUserDomains(0, $v['domain_id'], 1)) {
           //     continue;
           // }

            $userDomainData = array(
                'top_id' => $top_id,
                'username' => $tops[$top_id]['username'],
               // 'domain_id' => $v['domain_id'],
            );

//             if (!domains::addUserDomain($userDomainData)) {
//                 $GLOBALS['db']->rollback();
//                 throw new exception2('事务失败4');
//             }

            $count++;
        //}

        if ($count == 0) {
            $GLOBALS['db']->rollback();
            throw new exception2('事务失败6');
        }

        //插入返点
        if (!userRebates::saveRebate($top_id, $prize_mode)) {
            $GLOBALS['db']->rollback();
            throw new exception2('事务失败5');
        }

        //It seems OK
        $GLOBALS['db']->commit();

        return $top_id;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('users', $data);
    }

    //冻结用户
    static public function setStatus($user_id, $status, $reason, $includeChild = false, $admin_id = 0, $freezeCard = 0)
    {
        if (!is_numeric($user_id) || !is_numeric($status) || !($reason) || !is_numeric($freezeCard)) {
            throw new exception2('参数无效');
        }

        $GLOBALS['db']->startTransaction();

        $reason = date('Y-m-d H:i:s') . "由{$GLOBALS['SESSION']['admin_username']}进行冻结，原因：$reason";
        if ($includeChild) {
            $childs = users::getItems($user_id, true, 1, -1, -1);
            foreach ($childs as $k => $v) {
                $sql = "UPDATE users SET status = $status, remark = concat(remark,\"{$reason};\")";
                if ($admin_id) {
                    $sql .= ", admin_id = $admin_id";
                }
                $sql .= " WHERE user_id = {$v['user_id']} LIMIT 1";
                if ($GLOBALS['db']->query($sql, array(), 'u')) {
//冻结该用户成功后冻结银行卡
                    if ($freezeCard) {
                        if ($cards = userBindCards::getItems($v['user_id'], 0, '', 1)) {
                            $cardData = array(
                                'status' => 2, //不可再用
                                'finish_admin_id' => $admin_id,
                                'remark' => $reason,
                            );
                            foreach ($cards as $card) {
                                if (userBindCards::updateItem($card['bind_card_id'], $cardData) === false) {
                                    $GLOBALS['db']->rollback();
                                    throw new exception2('事务失败1');
                                }
                            }
                        }
                    }
                } else {
                    $GLOBALS['db']->rollback();
                    throw new exception2('事务失败2');
                }
            }
        } else {
            $sql = "UPDATE users SET status = $status, remark = concat(remark,\"{$reason};\")";
            if ($admin_id) {
                $sql .= ", admin_id = $admin_id";
            }
            $sql .= " WHERE user_id = $user_id LIMIT 1";
            if ($GLOBALS['db']->query($sql, array(), 'u')) {
                if ($freezeCard) {
                    if ($cards = userBindCards::getItems($user_id, 0, '', 1)) {
                        $cardData = array(
                            'status' => 2, //不可再用
                            'finish_admin_id' => $admin_id,
                            'remark' => $reason,
                        );
                        foreach ($cards as $card) {
                            if (userBindCards::updateItem($card['bind_card_id'], $cardData) === false) {
                                $GLOBALS['db']->rollback();
                                throw new exception2('事务失败3');
                            }
                        }
                    }
                }
            } else {
                $GLOBALS['db']->rollback();
                throw new exception2('事务失败2');
            }
        }

        $GLOBALS['db']->commit();

        return true;
    }

    static public function updatePwd($user, $pwd = '', $secpwd = '', $safepwd = '', $reason = '', $admin_id = 0)
    {
        if (!$user || (!$pwd && !$secpwd && !$safepwd)) {
            throw new exception2('参数无效');
        }
        //开始事务
        $GLOBALS['db']->startTransaction();
        $tmp = array();
        if ($pwd) {
            // 插入events
            $eventData1 = array(
                'user_id' => $user['user_id'],
                'type' => 102,
                'new_value' => password_hash($pwd, PASSWORD_DEFAULT),
                'old_value' => $user['pwd'],
                'remark' => $reason,
                'create_time' => date('Y-m-d H:i:s'),
                'admin_id' => $admin_id,
            );
            if (!events::addItem($eventData1)) {
                return false;
            }
            $tmp[] = " pwd = '" . password_hash($pwd, PASSWORD_DEFAULT) . "'";
        }
        if ($secpwd) {
            // 插入events
            $eventData2 = array(
                'user_id' => $user['user_id'],
                'type' => 103,
                'new_value' => generateEnPwd($secpwd),
                'old_value' => $user['secpwd'],
                'remark' => $reason,
                'create_time' => date('Y-m-d H:i:s'),
                'admin_id' => $GLOBALS['SESSION']['admin_id'],
            );
            if (!events::addItem($eventData2)) {
                return false;
            }
            $tmp[] = " secpwd = '" . generateEnPwd($secpwd) . "'";
        }

        if ($safepwd) {
            // 插入events
            $eventData3 = array(
                'user_id' => $user['user_id'],
                'type' => 107,
                'new_value' => generateEnPwd($secpwd),
                'old_value' => $user['safe_pwd'],
                'remark' => $reason,
                'create_time' => date('Y-m-d H:i:s'),
                'admin_id' => $GLOBALS['SESSION']['admin_id'],
            );
            if (!events::addItem($eventData3)) {
                return false;
            }
            $tmp[] = " safe_pwd = '" . generateEnPwd($safepwd) . "'";
        }

        $remark = date('Y-m-d H:i:s') . "由{$GLOBALS['SESSION']['admin_username']}修改密码，原因：$reason";
        $sql = "UPDATE users SET" . implode(',', $tmp) . ", remark = concat(remark,\"{$remark};\")";
        if ($admin_id) {
            $sql .= ", admin_id = $admin_id";
        }
        $sql .= " WHERE user_id = {$user['user_id']} LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            $GLOBALS['db']->rollback();
            throw new exception2("修改密码失败", 500);
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }
        return true;
    }

    //更新余额
    static public function updateBalance($user_id, $amount, $is_deposit = false)
    {
        if (!is_numeric($user_id) || !is_numeric($amount)) {
            return false;
        }
        $sql = "UPDATE users SET balance = balance + $amount";
        if ($is_deposit) {
            $sql .= ", deposit_num = deposit_num + 1";
        }
        $sql .= " WHERE user_id = $user_id LIMIT 1";

        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    /**
     * 手工更新金额 带事务，所以另写了
     *  BY  kk    2013-04-09
     *  传入用户ID， 金额 ,账变类型, 管理员ID
     */
    static public function manualUpdateBalance($user, $amount, $orderType, $admin_id, $remark = '')
    {
        if (empty($user) || !is_numeric($amount) || !is_numeric($orderType)) {
            return false;
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        //1.先加一条存款记录
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $user['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => $orderType,
            'amount' => $amount,
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] + $amount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => 0,
            'admin_id' => $admin_id,
            'remark' => $remark,
        );
        if (!orders::addItem($orderData)) {
            return false;
        }
        if (!self::updateBalance($user['user_id'], $amount, $admin_id)) {
            $GLOBALS['db']->rollback();
            throw new exception2("手工修改金额失败", 500);
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    /**
     * 手工更新金额 带事务，所以另写了-批量
     *  BY  kk    2013-04-09
     *  传入用户ID， 金额 ,账变类型, 管理员ID
     */
    static public function manualUpdateBalanceBatch($params, $userIdArr, $orderType, $admin_id)
    {
        if (empty($params) || !is_numeric($orderType) || empty($userIdArr)) {
            return false;
        }
        //开始事务
        $GLOBALS['db']->startTransaction();
        $users = M('users')->where(['user_id'=>['IN',$userIdArr]])->field('user_id,balance')->index('user_id')->select();
        if(empty($users))
        {
            return false;
        }
        foreach ($params as $k=>&$vo)
        {
            if($users[$k]['user_id'] == $vo['user_id'])
            {
                $vo['after_balance'] = $users[$k]['balance'] + $vo['amount'];
                $vo['balance'] = $users[$k]['balance'];
            }
        }

        //1.先加一条存款记录
        $va = '';//日志sql拼凑
        $wh = '';//修改sql拼凑
        $timeTmp = date('Y-m-d H:i:s');
        foreach ($params as $k => $v) {
            $tmp = 'balance +' . $v['amount'];
            $wh .= " WHEN {$v['user_id']}  THEN $tmp ";
            $va .= "(0,'',{$v['user_id']},'{$v['username']}',0,'',$orderType,{$v['amount']},{$v['balance']},{$v['after_balance']},'{$timeTmp}',0,$admin_id,'{$v['remark']}'),";
        }
        $va = rtrim($va, ',');
        $sql = "insert into orders (lottery_id,issue,from_user_id,from_username,to_user_id,to_username,type,amount,pre_balance,balance,create_time,business_id,admin_id,remark) VALUES " . $va;
        $res = $GLOBALS['db']->query($sql, array(), 'u');
        if (!$res) {
            return false;
        }
        $idStr = implode(',', $userIdArr);
        $sql = "UPDATE users SET balance = CASE user_id" . $wh . " END WHERE user_id IN ($idStr)";
        $res = $GLOBALS['db']->query($sql, array(), 'u');
        if (!$res) {
            $GLOBALS['db']->rollback();
            throw new exception2("手工修改金额失败", 500);
        }
        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    /**
     * 总代降成一代
     * @param type $srcTopName 将要降级的原总代
     * @param type $dstTopName 挂到新的总代下
     * @return $srcTopName new user id
     */
    static public function checkAdjustLevel($type, $srcTopName, $dstTopName = '')
    {
        $result = array('errno' => 0, 'errstr' => '', 'childs' => array(), 'srcTop' => array(), 'dstTop' => array());
        if (!$srcTop = users::getItem($srcTopName)) {
            $result['errno'] = 1;
            $result['errstr'] = "用户{$srcTopName}不存在";
            return $result;
        }

        if ($type == 1) {
            if ($srcTop['level'] != 0) {
                $result['errno'] = 2;
                $result['errstr'] = "该用户不是总代， 不能转成一代";
                return $result;
            }
            if (!$dstTop = users::getItem($dstTopName)) {
                $result['errno'] = 3;
                $result['errstr'] = "目标总代{$dstTopName}不存在";
                return $result;
            }

            //判断最高等级数不超过5级代理
            $childs = users::getItems($srcTop['user_id'], true, 1, array('user_id,username,level,parent_id,top_id,parent_tree'));
            foreach ($childs as $v) {
                if ($v['level'] >= 5 && $v['level'] < 10) {
                    $result['errno'] = 4;
                    $result['errstr'] = "最高等级数不超过5级代理    | " . $v['user_id'] . ' level = ' . $v['level'];
                    return $result;
                }
            }

            //判断各采种返点必须低于目标总代
            $tmp = userRebates::getItems(array($srcTop['user_id'], $dstTop['user_id']));
            $srcTopRebates = $dstTopRebates = array();
            foreach ($tmp as $v) {
                if ($v['user_id'] == $srcTop['user_id']) {
                    $srcTopRebates[$v['property_id']] = $v;
                } elseif ($v['user_id'] == $dstTop['user_id']) {
                    $dstTopRebates[$v['property_id']] = $v;
                }
            }

            foreach ($srcTopRebates as $property_id => $v) {
                if (!isset($dstTopRebates[$property_id])) {
                    $result['errno'] = 5;
                    $result['errstr'] = "目标总代{$dstTopName}没有设置好property_id={$property_id}的返点";
                    return $result;
                }
                if ($srcTopRebates[$property_id]['rebate'] >= $dstTopRebates[$property_id]['rebate']) {
                    $result['errno'] = 6;
                    $result['errstr'] = "源总代{$srcTopName}的property_id={$property_id}返点{$srcTopRebates[$property_id]['rebate']}超过了目标总代
                    {$dstTopName}({$dstTopRebates[$property_id]['rebate']})，请先降到比目标总代低的档位";
                    return $result;
                }
            }

            $result['dstTop'] = $dstTop;
            $result['childs'] = $childs;
        } elseif ($type == 2) {
            if ($srcTop['level'] != 1) {
                $result['errno'] = 7;
                $result['errstr'] = "该用户不是一代， 不能转成总代";
                return $result;
            }
            $result['srcTop'] = $srcTop;
        } else {
            throw new exception2("非法调级类型");
        }

        return $result;
    }

    /**
     * 总代降成一代
     * @param type $srcTopName 将要降级的原总代
     * @param type $dstTopName 挂到新的总代下
     * @return $srcTopName new user id
     */
    static public function decreaseLevel($srcTopName, $dstTopName)
    {
        $result = users::checkAdjustLevel(1, $srcTopName, $dstTopName);
        if ($result['errno'] != 0) {
            throw new exception2($result['errstr']);
        }

        $dstTop = $result['dstTop'];
        $childs = $result['childs'];

        //3.给予新的user_id 要求从最大ID开始有序排列
        $maxUserId = users::getMaxUserId();

        //同步各个关联表冗余数据 user_id , top_id; 没有同步报表,msg表等会引起一些问题。原因及详情参考JIRA
        $syncTable = array(
            'deposits',
            'user_diff_rebates',
            'withdraws',
        );

        //开始事务
        $GLOBALS['db']->startTransaction();

        $newUserId = 1;
        foreach ($childs as $user_id => &$user) {
            $user['new_user_id'] = $newUserId + $maxUserId;
            if ($user['level'] < 5) {
                $user['level']++;
            }
            if ($user['username'] == $srcTopName) {
                $user['parent_id'] = $dstTop['user_id'];
                $user['parent_tree'] = $dstTop['user_id'];
            } else {
                if (!isset($childs[$user['parent_id']])) {
                    $GLOBALS['db']->rollback();
                    throw new exception2("user:" . $user['user_id'] . " can`t find parent");
                }
                $user['parent_tree'] = $childs[$user['parent_id']]['parent_tree'] . ',' . $childs[$user['parent_id']]['new_user_id'];
                $user['parent_id'] = $childs[$user['parent_id']]['new_user_id'];
            }
            $user['top_id'] = $dstTop['user_id'];
            users::updateItem($user['user_id'], array('user_id' => $user['new_user_id'], 'level' => $user['level'], 'parent_id' => $user['parent_id'], 'top_id' => $user['top_id'], 'parent_tree' => $user['parent_tree']));

            //4.同步各个关联表冗余数据 user_id , top_id
            foreach ($syncTable as $tb) {
                $sql = "UPDATE $tb SET user_id='" . $user['new_user_id'] . "', top_id='" . $dstTop['user_id'] . "' WHERE user_id='" . $user['user_id'] . "'";
                if (!$GLOBALS['db']->query($sql, array(), 'u')) {
                    $GLOBALS['db']->rollback();
                    throw new exception2("更新{$tb} 表user_id,top_id失败");
                }
            }
            //user_rebates
            $sql = "UPDATE user_rebates SET user_id='" . $user['new_user_id'] . "' WHERE user_id='" . $user['user_id'] . "'";
            if (!$GLOBALS['db']->query($sql, array(), 'u')) {
                $GLOBALS['db']->rollback();
                throw new exception2("更新{$tb} 表user_id失败");
            }

            $newUserId++;
        }

        //5.删除domain中的配置
        $sql = "DELETE FROM user_domains WHERE username='{$srcTopName}'";
        if (!$GLOBALS['db']->query($sql, array(), 'd')) {
            $GLOBALS['db']->rollback();
            throw new exception2("删除domain中的配置失败");
        }

        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        //返回原总代替换后的新user_id
        return $maxUserId + 1;
    }

    /**
     * @todo   一代升总代
     * @param  $srcProxyName        一代
     * @return Array
     */
    static public function IncreaseLevel($srcProxyName)
    {
        $result = users::checkAdjustLevel(2, $srcProxyName);
        if ($result['errno'] != 0) {
            throw new exception2($result['errstr']);
        }

        $srcProxy = $result['srcTop'];
        // $childsIdTree = users::getUserTree($srcProxy['user_id'], false, 1);
        $childsIdTree = users::getUserTreeField([
            'field' => ['user_id'],
            'parent_id' => $srcProxy['user_id'],
            'includeSelf' => false,
            'recursive' => 1,
        ]);

        //开始事务
        $GLOBALS['db']->startTransaction();

        //修改一代
        $toTopData = array(
            'level' => 0,
            'parent_id' => 0,
            'top_id' => $srcProxy['user_id'],
            'parent_tree' => '',
        );
        if (!users::updateItem($srcProxy['user_id'], $toTopData)) {
            $GLOBALS['db']->rollback();
            throw new exception2("更新一代失败");
        }
        if ($childsIdTree) {
            //修改childs
            $replace_id = $srcProxy['top_id'] . ',';
            $sql = "UPDATE users SET `level` = IF(`level`=10, 10, `level` - 1), top_id = '" . $srcProxy['user_id'] . "', ";
            $sql .= "parent_tree = REPLACE(parent_tree,'{$replace_id}','') WHERE user_id IN (" . implode(',', array_keys($childsIdTree)) . ")";
            if (!$GLOBALS['db']->query($sql, array(), 'u')) {
                $GLOBALS['db']->rollback();
                throw new exception2("更新一代childs失败");
            }

            //同步各表top_id    必须同时有user_id
            $sorTable = array(
                'deposits',
                'packages',
                'projects',
                'promos',
                'user_diff_rebates',
                'withdraws',
                'traces',
            );
            foreach ($sorTable as $tb) {
                $sql = "UPDATE {$tb} SET top_id='" . $srcProxy['user_id'] . "' WHERE user_id IN (" . implode(',', array_keys($childsIdTree)) . ")";
                if (!$GLOBALS['db']->query($sql, array(), 'u')) {
                    $GLOBALS['db']->rollback();
                    throw new exception2("更新{$tb} 表top_id失败");
                }
            }
        }

        //复制$srcProxy总代的domain给$srcProxy
        $sql = "INSERT INTO user_domains (top_id, username, domain_id)
                SELECT '" . $srcProxy['user_id'] . "', username, domain_id FROM user_domains WHERE top_id='" . $srcProxy['top_id'] . "'";
        if (!$GLOBALS['db']->query($sql, array(), 'i')) {
            $GLOBALS['db']->rollback();
            throw new exception2("复制domains失败");
        }

        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    /**
     * 清理合符条件的用户和代理
     *  BY  nyjah    2015-06-10
     * @param int $amount 金额
     * @param int $days 天数
     *  return array
     */
    static public function cleanUser($amount, $days)
    {
        if (!is_numeric($amount) && !is_numeric($days)) {
            throw exception2('参数错误');
        }
        $lastEndTS = strtotime("-{$days} day");
        $users = users::getItems(-1, true, 0, array(), 8, 0); //查询所有正常状态用户
        $illegalUsers = array();
        $legalUserIds = array();
        foreach ($users as $v) {
            if ($v['balance'] < $amount && strtotime($v['last_time']) < $lastEndTS && strtotime($v['reg_time']) < $lastEndTS) {
                $illegalUsers[$v['user_id']] = $v;
            } else {
                foreach (explode(',', $v['parent_tree']) as $parentId) {
                    $legalUserIds[$parentId] = $parentId;
                }
            }
        }
        foreach ($illegalUsers as $userId => $user) {
            if ($user['group_id'] < 4 && isset($legalUserIds[$userId])) {
                unset($illegalUsers[$userId]);
            }
        }
        $userTmp = array(
            'status' => 5,
            'remark' => "资金少于{$amount}元并且{$days}未活跃",
            'admin_id' => isset($GLOBALS['SESSION']['admin_id']) ? $GLOBALS['SESSION']['admin_id'] : 0,
        );
        $cardTmp = array(
            'status' => 0,
            'finish_admin_id' => isset($GLOBALS['SESSION']['admin_id']) ? $GLOBALS['SESSION']['admin_id'] : 0,
        );
        $failUserIds = array();
        $successUserIds = array();
        foreach ($illegalUsers as $v) {

            $GLOBALS['db']->startTransaction();

            if ($cards = userBindCards::getItems($v['user_id'], '', 1)) {
                foreach ($cards as $card) {
                    if (userBindCards::updateItem($card['bind_card_id'], $cardTmp) === false) {
                        $failUserIds[] = $v['user_id'];
                        $GLOBALS['db']->rollback();
                        continue 2;
                    }
                }
            }

            if (users::updateItem($v['user_id'], $userTmp)) {
                $successUserIds[] = $v['user_id'];
                $GLOBALS['db']->commit();
            } else {
                $failUserIds[] = $v['user_id'];
                $GLOBALS['db']->rollback();
            }
        }

        $afterUsers = users::getItems(-1, true, 0, array(), 8, 0); //查询所有正常状态用户

        return array('beforeUserIds' => array_keys($users), 'afterUserIds' => array_keys($afterUsers), 'successUserIds' => $successUserIds, 'failUserIds' => $failUserIds);
    }


    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }
        $GLOBALS['mc']->delete(__CLASS__, $id);

        return $GLOBALS['db']->updateSM('users', $data, array('user_id' => $id));
    }

    static public function getUsersForUpdateGroup($defaultGroupId)
    {
        $sql = "SELECT u.user_id, u.ref_group_id, sum(d.amount) total_amount, count(d.user_id) total_count FROM users u, deposits d WHERE u.user_id = d.user_id AND d.status = 8 and d.trade_type != 6 GROUP BY d.user_id UNION ALL SELECT u.user_id, u.ref_group_id, '0' total_amount, '0' total_count FROM users u WHERE u.user_id NOT IN (SELECT d.user_id FROM deposits d WHERE d.status = 8 and d.trade_type != 6) AND u.ref_group_id != $defaultGroupId;";
        $result = $GLOBALS['db']->getAll($sql, array(), 'user_id');

        return $result;
    }

    static public function updateUsersGroup($users)
    {
        if (!is_array($users)) {
            throw new exception2('参数无效');
        }

        $GLOBALS['db']->startTransaction();

        foreach ($users as $users_key => $users_value) {
            if ($users_value != '') {
                $sql = "UPDATE users u SET u.ref_group_id = $users_key WHERE u.user_id in($users_value)";
                $GLOBALS['db']->query($sql, array(), 'u');
            }
        }

        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    /**
     * rex,2014-05-21 . 会员间转账功能
     * @param array $fromUser
     * @param array $toUser
     * @param float $amount
     * @param string $secpassword
     */
    static public function transferMoney($fromUser, $toUser, $amount, $secpassword)
    {
        if ($amount <= 0) {
            throw new exception2('参数无效');
        }
        if ($fromUser['balance'] < $amount) {
            throw new exception2('余额不足');
        }
        if ($fromUser['secpwd'] != generateEnPwd($secpassword)) {
            $GLOBALS['db']->rollback();
            throw new exception2('您输入的资金密码不对');
        }
        $fromId = $fromUser['user_id'];
        $toId = $toUser['user_id'];

        $GLOBALS['db']->startTransaction();
        if (!users::updateBalance($fromId, -1 * $amount)) {
            throw new exception2('db error');
        }
        if (!users::updateBalance($toId, $amount)) {
            throw new exception2('db error');
        }

        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $fromId,
            'from_username' => $fromUser['username'],
            'to_user_id' => $toId,
            'to_username' => $toUser['username'],
            'type' => 212, //212表示"给下级分红"
            'amount' => -$amount,
            'pre_balance' => $fromUser['balance'],
            'balance' => $fromUser['balance'] - $amount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => 0,
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            log2("db error 3130", $orderData);
            throw new exception2('DB error', 3130);
        }
        $orderData = array(
            'lottery_id' => 0,
            'issue' => '',
            'from_user_id' => $toId,
            'from_username' => $toUser['username'],
            'to_user_id' => $fromId,//这里表示谁代充的
            'to_username' => $fromUser['username'],//这里表示谁代充的
            'type' => 154, //154表示"接收转账"
            'amount' => $amount,
            'pre_balance' => $toUser['balance'],
            'balance' => $toUser['balance'] + $amount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => 0,
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            log2("db error 3130", $orderData);
            throw new exception2('DB error', 3130);
        }
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('DB error', 3160);
        }

        return true;
    }

    /**
     * nyjah,2015-06-8 . 查找同名用户数
     * @param array $realNames
     * return array
     */
    static public function getRealNameNum($realNames)
    {
        if (!is_array($realNames) || empty($realNames)) {
            throw new exception2('参数错误');
        }

        $sql = 'SELECT real_name,count(*) AS num FROM users WHERE `real_name` IN (\'' . implode("','", $realNames) . '\') GROUP BY `real_name`';

        return $GLOBALS['db']->getAll($sql, array(), 'real_name');
    }

    /**
     * nyjah,2015-06-19 . 真删除
     * @param int $id
     * return int
     */
    static public function deleteItem($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数错误');
        }

        $sql = "DELETE FROM users WHERE user_id = {$id}";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * nyjah,2015-06-19 . 清理回收用户
     * @param array $users 参数为二维数组
     * return int
     */
    static public function deleteGarbageUsers($users)
    {
        if (!is_array($users)) {
            throw new exception2('参数错误');
        }

        $GLOBALS['db']->startTransaction();
        foreach ($users as $userId => $user) {
            if (is_array($user) && !empty($user)) {
                unset($user['ts']);
                if (garbageUsers::addItem($user)) {
                    if (users::deleteItem($userId)) {
                        continue;
                    }
                }
                $GLOBALS['db']->rollback();
                return false;
            }
        }
        $GLOBALS['db']->commit();

        return true;
    }

    static function addManualBalanceToTest($username, $amount)
    {
        if (!$user = users::getItem($username)) {
            throw new exception2('无效的用户名，请检查');
        }
        if ($user['is_test'] != 1) {
            throw new exception2('该用户非测试账号');
        }
        //不允许负数
        if ($amount <= 0) {
            throw new exception2('金额不能为负数。请检查');
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        // 修改用户金额
        if (!users::updateBalance($user['user_id'], $amount)) {
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
            'type' => 155,
            'amount' => $amount,
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] + $amount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => 0,
            'admin_id' => $GLOBALS['SESSION']['admin_id'],
        );
        if (!orders::addItem($orderData)) {
            throw new exception2('提交事务失败2');
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('提交事务失败3');
        }

        return true;
    }


    /**
     * 有安全码获取用户数据
     * @param $safe_pwd
     * @return mixed
     */
    static public function getItemBySafePwd($safe_pwd)
    {
        $sql = 'SELECT * FROM users WHERE safe_pwd = :safe_pwd';
        $paramArr[':safe_pwd'] = $safe_pwd;

        return $GLOBALS['db']->getRow($sql, $paramArr);
    }

    /**
     * 获取用户配额数、已用数(默认配额只能创建,不能传递. 下发的配额可以创建,也可以传递)
     * @param $user
     * @return array|void
     * @param $deliver //是否为传递配额
     * @param $is_balance
     * @throws exception2
     */
    static public function countOfQuota($user, $is_balance = false, $deliver = false)
    {
        $aData = [];
        $quotas = $user['quota'] ? unserialize($user['quota']) : [];

        $directChilds = users::getItems($user['user_id'], false, 0, array(), array(8, 1));
        $directChildsRebates = userRebates::getUsersRebates(array_keys($directChilds), 1, 0);

        $userPrizeMode = userRebates::userPrizeMode($user['user_id']);
        $configQuotas = users::configQuota($user['level'], $userPrizeMode);

        if (!$deliver) {
            foreach ($quotas as $prizeMode => $available) {
                if (isset($configQuotas[$prizeMode])) {
                    $quotas[$prizeMode] += $configQuotas[$prizeMode];
                    unset($configQuotas[$prizeMode]);
                }
            }
            $quotas = $quotas + $configQuotas;
        }

        foreach ($quotas as $prizeMode => $available) {
            $used = 0;
            $rebate = userRebates::getRebateByPrizeMode($prizeMode);
            foreach ($directChildsRebates as $subRebate) {
                if (strval($subRebate * 100) == $rebate) $used++;
            }

            if ($deliver) {
                $configNum = isset($configQuotas[$prizeMode]) ? $configQuotas[$prizeMode] : 0;
                $used = $used > $configNum ? ($used - $configNum) : 0;
            }

            if ($is_balance) {
                $aData[$prizeMode] = $available - $used;
            } else {
                $aData[$prizeMode] = ['prize_mode' => $prizeMode, 'rebate' => number_format($rebate, 1), 'available' => $available, 'used' => $used];
            }
        }
        krsort($aData);

        return $aData;
    }

    /**
     * 生成用户配额
     * @param $level
     * @param $setPrizeMode
     * @return array
     */
    static public function configQuota($level, $setPrizeMode)
    {
        $data = [];
        $highPrizeMode = array_keys(userRebates::levelPrizeModes($level, $setPrizeMode, true));

        if (in_array($setPrizeMode, $highPrizeMode)) {
            foreach ($highPrizeMode as $prizeMode) {
                //总代、一代的默认配额奖金组可以平水、二代、二代以后的默认配额奖金组要小于当前代理的奖金组
                if (($level <= 1 && $prizeMode <= $setPrizeMode) || ($level == 2 && $prizeMode < $setPrizeMode)) {
                    $key = 'limit_quota_level_' . $level . '_' . $prizeMode;
                    $value = config::getConfig($key);
                    !is_numeric($value) or $data[$prizeMode] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * 下发或回收配额
     * @param $user    用户
     * @param $subUser　下级用户
     * @param $aCount    数量
     * @return bool
     */
    static public function sendOutQuota($user, $subUser, $aCount)
    {
        if ($user['user_id'] != $subUser['parent_id'] || !is_array($aCount)) {
            return false;
        }
        $aQuotas = users::countOfQuota($user, true, true);
        $aSubQuotas = users::countOfQuota($subUser, true, true);

        $subPrizeMode = userRebates::userPrizeMode($subUser['user_id']);
        //validate
        foreach ($aCount as $prizeMode => $count) {
            if (empty($count)) $aCount[$prizeMode] = $count = 0;

            if ($prizeMode > $subPrizeMode || !in_array($prizeMode, array_keys($aQuotas)) || $count < 0) {
                return false;
            }
            $aSubQuotas[$prizeMode] = isset($aSubQuotas[$prizeMode]) ? $aSubQuotas[$prizeMode] : 0;

            if ($count == $aSubQuotas[$prizeMode]) {
                unset($aCount[$prizeMode]);
                continue;
            }
            $aCount[$prizeMode] = $count - $aSubQuotas[$prizeMode];

            if ($aCount[$prizeMode] > 0) { //send out
                if ($aCount[$prizeMode] > $aQuotas[$prizeMode]) return false;
            } else { //retrieve
                if ($aCount[$prizeMode] > $aSubQuotas[$prizeMode]) return false;
            }
        }

        if ($aCount) {
            $GLOBALS['db']->startTransaction();

            $aQuotas = $user['quota'] ? unserialize($user['quota']) : [];
            $aSubQuotas = $subUser['quota'] ? unserialize($subUser['quota']) : [];

            foreach ($aCount as $prizeMode => $count) {
                if ($count > 0) {
                    $aSubQuotas[$prizeMode] = isset($aSubQuotas[$prizeMode]) ? $aSubQuotas[$prizeMode] + abs($count) : abs($count);
                    $aQuotas[$prizeMode] -= abs($count);
                } else {
                    $aQuotas[$prizeMode] = isset($aQuotas[$prizeMode]) ? $aQuotas[$prizeMode] + abs($count) : abs($count);
                    $aSubQuotas[$prizeMode] -= abs($count);
                }
            }
            if (users::updateItem($user['user_id'], ['quota' => serialize($aQuotas)]) === false || users::updateItem($subUser['user_id'], ['quota' => serialize($aSubQuotas)]) === false) {
                $GLOBALS['db']->rollback();
                return false;
            }

            $GLOBALS['db']->commit();
        }
        return true;
    }

    /**
     * 增加配额
     * @param $user    用户
     * @param $aCount
     * @return bool
     * @throws exception2
     */
    static public function addQuota(&$user, $prizeMode, $count)
    {
        $aQuota = $user['quota'] ? unserialize($user['quota']) : [];
        /*		foreach ($aCount as $prizeMode => $count) {
                    $aQuota[$prizeMode] = isset($aQuota[$prizeMode]) ? $aQuota[$prizeMode]+$count : $count;
                }*/

        $aQuota[$prizeMode] = isset($aQuota[$prizeMode]) ? $aQuota[$prizeMode] + $count : $count;
        $data = array('quota' => serialize($aQuota));
        $user['quota'] = $data['quota'];
        return users::updateItem($user['user_id'], $data);
    }

    /**
     * 减少配额
     * @param $user
     * @param $prizeMode
     * @param $count
     * @return bool
     * @throws exception2
     */
    static public function decQuota(&$user, $prizeMode, $count)
    {
        $aQuota = unserialize($user['quota']);
        /*		foreach ($aCount as $prizeMode => $count) {
                    $aQuota[$prizeMode] -= $count;
                }*/
        $aQuota[$prizeMode] -= $count;
        $data = array('quota' => serialize($aQuota));
        $user['quota'] = $data['quota'];
        return users::updateItem($user['user_id'], $data);
    }


    /**
     * 安全码检查
     * @param $userPwd
     * @param $inputPwd
     * @param $user_id
     * @param $lose
     * @param $noajax
     * @return bool
     */
    static public function checkSafePwd($userPwd, $inputPwd, $user_id = null, $lose = false, $noajax = true)
    {
        $user_id = $user_id ? $user_id : $GLOBALS['SESSION']['user_id'];

        if ($lose && !$userPwd) {
            if ($noajax) {
                showMsg('没有设置安全码，请联系客服');
            } else {
                return '没有设置安全码，请联系客服';
            }
        }

        if (!$userPwd) {
            if ($noajax) {
                showMsg("您尚未设置安全码，请先 <a href='javascript:void(0);' onclick=window.top.document.getElementById('mainFrame').src='?c=user&a=editSafePwd';parent.layer.closeAll();>点此设置安全码</a>");
            } else {
                return '您尚未设置安全码，请先设置安全码';
            }

        }

        if (!$inputPwd) {
            if ($noajax) {
                showMsg("请输入安全码");
            } else {
                return '请输入安全码';
            }
        }

        $cacheKey = 'safe_pwd_user_id_' . $user_id;
        $errorTime = $GLOBALS['mc']->get(__CLASS__, $cacheKey);

        if ($errorTime && $errorTime >= 4) {
            if ($noajax) {
                showMsg("您已连续输错4次,请15分钟重试");
            } else {
                return '您已连续输错4次,请15分钟重试';
            }
        }

        if ($userPwd != generateEnPwd($inputPwd)) {
            $errorTime = $errorTime ? $errorTime + 1 : 1;
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $errorTime, 900); //放入缓存
            if ($errorTime >= 4) {
                if ($noajax) {
                    showMsg("您已连续输错4次,请15分钟重试");
                } else {
                    return '您已连续输错4次,请15分钟重试';
                }
            } else {
                if ($noajax) {
                    showMsg("安全码错误");
                } else {
                    return '安全码错误';
                }
            }
        }
        $GLOBALS['mc']->delete(__CLASS__, $cacheKey);
        return $noajax ? true : null;
    }

}

?>
