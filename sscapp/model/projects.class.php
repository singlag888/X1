<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

// 投注记录模型
class projects
{
    const CANCELPACKAGES = 65535;//代表所有撤单类型

    static public $sqlConds = '';

    static public function getItem($id, $user_id = 0)
    {
        $sql = 'SELECT * FROM projects WHERE project_id = ' . intval($id);
        if ($user_id != 0) {
            $sql .= ' AND user_id = ' . intval($user_id);
        }

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 增加了user_id和create_time的联合索引
     * @param type $lottery_id
     * @param type $issue
     * @param type $package_id
     * @param type $user_id
     * @param type $start_time
     * @param type $end_time
     * @param type $start
     * @param type $amount
     * @return type
     */
    static public function getItems($lottery_id = 0, $issue = '', $package_id = 0, $user_id = 0, $start_time = '', $end_time = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM projects WHERE 1';
        if ($user_id > 0) {
            $sql .= " AND user_id = '$user_id'";
        }

        if ($start_time != '') {
            $sql .= " AND create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND create_time <= '$end_time'";
        }
        if ($lottery_id != 0) {
            $sql .= " AND lottery_id = " . intval($lottery_id);
        }
        if ($issue != '') {
            $sql .= " AND issue = '$issue'";
        }
        if ($package_id != 0) {
            if (is_array($package_id) && count($package_id)) {
                $sql .= " AND package_id IN(" . implode(',', $package_id) . ')';
            }
            else {
                $sql .= " AND package_id = " . intval($package_id);
            }
        }

        $sql .= " ORDER BY project_id DESC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql, array(),'project_id');

        return $result;
    }

    static public function getItemsNumber($lottery_id = 0, $issue = '', $package_id = 0, $user_id = 0, $include_childs = 0, $start_time = '', $end_time = '')
    {
        $sql = 'SELECT COUNT(*) AS count FROM projects WHERE 1';
        if ($lottery_id != 0) {
            $sql .= " AND lottery_id = " . intval($lottery_id);
        }
        if ($issue != '') {
            $sql .= " AND issue = '$issue'";
        }
        if ($package_id != 0) {
            if (is_array($package_id) && count($package_id)) {
                $sql .= " AND package_id IN(" . implode(',', $package_id) . ')';
            }
            else {
                $sql .= " AND package_id = " . intval($package_id);
            }
        }
        if ($user_id > 0) {
            $sql .= " AND user_id = '$user_id'";
        }

        if ($start_time != '') {
            $sql .= " AND create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND create_time <= '$end_time'";
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM projects WHERE project_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'project_id');
    }

    /**
     * 151215 取得最大奖金列表
     * mc改成xc 原因 本地缓存更优 避免网络IO
     * @param type $start_time
     * @param type $end_time
     * @param type $start
     * @param type $amount
     *  return array
     */
    static public function getMaxPrizeList($start_time = '', $end_time = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        //先从缓存里拿结果
        $cacheKey = __FUNCTION__;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {

            $sql = 'SELECT p.lottery_id, l.cname, p.issue, p.user_id,u.username, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u' .
                ' ON p.user_id=u.user_id LEFT JOIN lottery l ON l.lottery_id = p.lottery_id WHERE p.check_prize_status = 1 AND p.cancel_status = 0 AND u.is_test=0';

            if ($start_time != '') {
                $sql .= " AND p.create_time >= '$start_time'";
            }
            if ($end_time != '') {
                $sql .= " AND p.create_time <= '$end_time'";
            }
            $sql .= " GROUP BY p.user_id,p.issue,p.lottery_id ORDER BY total_prize DESC";
            if ($start > -1) {
                $sql .= " LIMIT $start, $amount";
            }
            $result = $GLOBALS['db']->getAll($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 7200 * 1 + 8); //缓存二个小时加10秒
        }

        return $result;
    }

    //按规则生成唯一订单编号 P表示package
    static public function wrapId($package_id, $issue, $lottery_id)
    {
        switch ($lottery_id) {
            case '1':
                $str = 'CQ';
                break;
            case '2':
                $str = 'SD';
                break;
            case '3':
                $str = 'HLJ';
                break;
            case '4':
                $str = 'XJ';
                break;
            case '5':
                $str = 'JS5';
                break;
            case '6':
                $str = 'JX5';
                break;
            case '7':
                $str = 'GD5';
                break;
            case '8':
                $str = 'TJ';
                break;
            case '9':
                $str = 'L3D';
                break;
            case '10':
                $str = 'P3';
                break;
            case '11':
                $str = 'YF';
                break;
            case '12':
                $str = 'JS';
                break;
            case '13':
                $str = 'KSF';
                break;
            case '14':
                $str = 'PK';
                break;
            case '15':
                $str = 'MMC';
                break;
            case '16':
                $str = 'FF5';
                break;
            case '17':
                $str = 'PKS';
                break;
            case '18':
                $str = 'DJ';
                break;
            case '19':
                $str = 'AHKS';
                break;
            case '20':
                $str = 'FJKS';
                break;
            case '21':
                $str = 'LHC';
                break;
            case '22':
                $str = 'SSQ';
                break;
            case '23':
                $str = 'XY';
                break;
            case '24':
                $str = 'QQ';
                break;
            case '25':
                $str = 'JSLH';
                break;
            case '26':
                $str = 'XYFT';
                break;
            default:
                throw new exception2("Unknown rules for lottery {$lottery_id}");
                break;
        }
        $str .= substr(str_replace('-', '', $issue), -8);
        $str .= str_pad($package_id, 8, '0', STR_PAD_LEFT);
        $result = "{$str}P";

        return $result;
    }

    static public function dewrapId($str)  //, $issue, $lottery_id
    {
        if (!preg_match('`^(\w{15,21})P$`Ui', $str, $match)) {
            return 0;
        }
        $result = ltrim(substr($str, -9, 8), '0');

        return $result;
    }

    //批量插入提高速度
    static public function addItems($datas)
    {
        $tmp = array();
        foreach ($datas as $v) {
            $v['prize'] = empty($v['prize']) ? 0 : $v['prize'];
            $tmp[] = "('{$v['package_id']}','{$v['user_id']}','{$v['top_id']}','{$v['lottery_id']}','{$v['method_id']}','{$v['issue']}','{$v['code']}','{$v['single_num']}'," .
                    "'{$v['multiple']}','{$v['amount']}','{$v['cur_rebate']}','{$v['modes']}','{$v['create_time']}','{$v['hash_value']}','{$v['prize']}')";
        }
        $sql = "INSERT INTO projects (package_id,user_id,top_id,lottery_id,method_id,issue,code,single_num,multiple,amount,cur_rebate,modes,create_time,hash_value,prize) VALUES " . implode(',', $tmp);

        return $GLOBALS['db']->query($sql, array(), 'i');
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('projects', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $tmp = array();
        foreach ($data as $k => $v) {
            $tmp[] = "`$k`='" . $v . "'";
        }
        $sql = "UPDATE projects SET " . implode(',', $tmp) . " WHERE project_id=$id LIMIT 1";

        if ($GLOBALS['db']->query($sql, array(), 'u') === false) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM projects WHERE project_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * package模型
     */
    static public function getPackage($id, $user_id = 0, $needLock = false,$betMoney=0,$winMoney=0)
    {
        $sql = 'SELECT * FROM packages WHERE package_id = ' . intval($id);
        if ($user_id != 0) {
            $sql .= ' AND user_id = ' . intval($user_id);
        }

        if ($betMoney > 0) {
            $sql .= ' AND prize = ' . $betMoney;
        }

        if ($winMoney > 0) {
            $sql .= ' AND amount >= ' . $winMoney;
        }

        if ($needLock) {
            $sql .= ' LIMIT 1';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    /***************************** snow 修改sql  一次性取出所有  的 投注内容***************************************/
    /**
     * snow  2017-09-17  不改动以前的代码 ,复制一份进行修改.now 修改sql  一次性取出所有  的 投注内容
     * @param $id
     * @param int $user_id
     * @param bool|false $needLock
     * @param int $betMoney
     * @param int $winMoney
     * @return mixed
     */
    static public function getPackageSnow($id, $user_id = 0, $needLock = false,$betMoney=0,$winMoney=0)
    {
        $id = (int)$id;
//        $sql =<<<SQL
//SELECT a.*, c.code FROM packages AS a
//LEFT JOIN (SELECT GROUP_CONCAT(`code` SEPARATOR  '|' ) AS `code`, package_id FROM projects GROUP BY package_id) c ON a.package_id=c.package_id
//WHERE a.package_id = {$id}
//SQL;

        $sql = 'SELECT * FROM packages AS a WHERE package_id = ' . intval($id);
        if ($user_id != 0) {
            $sql .= ' AND a.user_id = ' . intval($user_id);
        }

        if ($betMoney > 0) {
            $sql .= ' AND a.prize = ' . $betMoney;
        }

        if ($winMoney > 0) {
            $sql .= ' AND a.amount >= ' . $winMoney;
        }

        if ($needLock) {
            $sql .= ' LIMIT 1';
        }

        $codeSql = 'SELECT package_id,`code` FROM projects WHERE package_id = ' . intval($id);

        $codeData = $GLOBALS['db']->getAll($codeSql);
        $tmpCode = [];
        if($codeData && is_array($codeData) && !empty($codeData)){
            //>>获取code值
            foreach($codeData as $key => $val){
                $tmpCode[] = $val['code'];
            }
            $tmpCode = implode('|',$tmpCode);
        }
        $result =  $GLOBALS['db']->getRow($sql);
        if($result && is_array($result) && !empty($result)){
            $result['code'] = empty($tmpCode) ? '' : $tmpCode;
        }
        return $result;
    }
    /***************************** snow 修改sql  一次性取出所有  的 投注内容***************************************/



    static public function getPackagesAdmin($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '', $cancel_status = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE, $is_award = -1,$betMoney=0,$winMoney=0)
    {
        /***************************** snow 修改sql  一次性取出所有  的 投注内容***************************************/

//        $sql =<<<SQL   影响性能
//SELECT a.*,b.username,b.status AS user_status,c.`code` FROM packages a
//LEFT JOIN users b ON a.user_id=b.user_id
//LEFT JOIN (SELECT GROUP_CONCAT(`code` SEPARATOR  '|' ) AS `code`,package_id FROM projects GROUP BY package_id) c ON a.package_id=c.package_id WHERE 1
//SQL;
        /***************************** snow 修改sql  一次性取出所有  的 投注内容***************************************/
$sql =<<<SQL
SELECT a.*,b.username,b.status AS user_status FROM packages AS a, users AS b
WHERE 1 AND a.user_id = b.user_id
SQL;

        self::$sqlConds = '';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            self::$sqlConds .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            self::$sqlConds .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            self::$sqlConds .= " AND a.check_prize_status = " . intval($check_prize_status);
        }
        if ($is_test != -1) {
            self::$sqlConds .= " AND b.is_test = " . intval($is_test);
        }

        if (!empty($issue) && is_array($issue)) {
            self::$sqlConds .= " AND a.issue IN ('" . implode("','", $issue) . "')";
        }
        elseif ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            self::$sqlConds .= " AND a.issue = '$issue'";
        }
        if ($trace_id != -1) {
            if ($trace_id == -255) {
                self::$sqlConds .= " AND a.trace_id > 0";
            }
            else {
                self::$sqlConds .= " AND a.trace_id = '$trace_id'";
            }
        }
        if ($modes != 0) {
            self::$sqlConds .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$sqlConds .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$sqlConds .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if (!$user = users::getItem($user_id, -1)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                ]);
                self::$sqlConds .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            self::$sqlConds .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            self::$sqlConds .= " AND a.create_time <= '$end_time'";
        }
        if ($send_start_time != '') {
            self::$sqlConds .= " AND a.send_prize_time >= '$send_start_time'";
        }
        if ($send_end_time != '') {
            self::$sqlConds .= " AND a.send_prize_time <= '$send_end_time'";
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= " AND a.cancel_status <> 0";
            }
            else {
                self::$sqlConds .= " AND a.cancel_status = '$cancel_status'";
            }
        }

        if( $betMoney > 0 ){
            self::$sqlConds .= " AND a.amount >= $betMoney";
        }

        if( $winMoney > 0 ){
            self::$sqlConds .= " AND a.prize >= $winMoney";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($order_by) {

            $sql .= self::$sqlConds." ORDER BY $order_by";
        } else {
            $sql .= self::$sqlConds." ORDER BY a.package_id DESC";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql , array(),'package_id');
        $tmpData = [];
        if(is_array($result) && !empty($result)){
            foreach ($result as $key => $val){
                $tmpData[] = "'" . $val['package_id'] . "'";
            }
            $tmpData = implode(',',$tmpData);
            $tmpData = !empty($tmpData) ? '(' . $tmpData . ')' : '';

            $codeSql = 'SELECT package_id,`code` FROM projects WHERE package_id IN ' . $tmpData;
            $codeRrsult = $GLOBALS['db']->getAll($codeSql,[],'package_id');

            if(is_array($codeRrsult) && !empty($codeRrsult)){
               foreach($codeRrsult as $key => $val){
                   $result[$key]['code'] = $val['code'];
               }
            }
        }

        return $result;
    }
/********************** author snow  添加数组传值********************************************/
    /**
     * author snow
     * @param array $options
     * @return mixed
     */
    static public function getPackagesExclude($options=[])
    {

        $defaultOptions = [
            'lottery_id'           => 0,
            'check_prize_status'   => -1,
            'is_test'              => -1,
            'issue'                => '',
            'trace_id'             => -1,
            'modes'                => 0,
            'user_id'              => '',
            'include_childs'       => 0,
            'start_time'           => '',
            'end_time'             => '',
            'send_start_time'      => '',
            'send_end_time'        => '',
            'cancel_status'        => -1,
            'order_by'             => '',
            'start'                => -1,
            'amount'               => DEFAULT_PER_PAGE,
            'is_award'             => -1,
            'betMoney'             => 0,
            'winMoney'             => 0,
            'freeze'               => -1,
            'field'               => '*',
        ];

        $defaultOptions = is_array($options) ? array_merge($defaultOptions, $options) : $defaultOptions;
        //>>不想使用   extract($defaultOptions);  编辑器不解析 ,看起来报错,不爽
        $field = $defaultOptions['field'];
        $from = 'a.*';
        if (is_string($field)) {
            $from = 'a.' . $field;
        } elseif (is_array($field) && !empty($field)) {
            foreach ($field as $key => $value) {
                $field[$key] = 'a.' . $value;
            }
            $from = implode(',', $field);
        }
        $sql = 'SELECT ' . $from . ',b.username,b.status AS user_status FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        self::$sqlConds = '';
        if(is_array($defaultOptions['lottery_id']) && !empty($defaultOptions['lottery_id'])) {
            self::$sqlConds .= " AND a.lottery_id IN ( '" . implode("','", $defaultOptions['lottery_id']) . "')";
        }
        elseif ($defaultOptions['lottery_id'] != 0) {
            self::$sqlConds .= " AND a.lottery_id = " . intval($defaultOptions['lottery_id']);
        }
        if ($defaultOptions['check_prize_status'] != -1) {
            self::$sqlConds .= " AND a.check_prize_status = " . intval($defaultOptions['check_prize_status']);
        }
        if ($defaultOptions['is_test'] != -1) {
            self::$sqlConds .= " AND b.is_test = " . intval($defaultOptions['is_test']);
        }

        if (!empty($defaultOptions['issue']) && is_array($defaultOptions['issue'])) {
            self::$sqlConds .= " AND a.issue IN ('" . implode("','", $defaultOptions['issue']) . "')";
        }
        elseif ($defaultOptions['issue'] != '' && preg_match('`^[\d-]+$`', $defaultOptions['issue'])) {
            self::$sqlConds .= " AND a.issue = '{$defaultOptions['issue']}'";
        }
        if ($defaultOptions['trace_id'] != -1) {
            if ($defaultOptions['trace_id'] == -255) {
                self::$sqlConds .= " AND a.trace_id > 0";
            }
            else {
                self::$sqlConds .= " AND a.trace_id = '{$defaultOptions['trace_id']}'";
            }
        }
        if ($defaultOptions['modes'] != 0) {
            self::$sqlConds .= " AND a.modes = '{$defaultOptions['modes']}'";
        }
        if ($defaultOptions['user_id'] != '') {
            $tmp = preg_match('`^\d+$`Ui', $defaultOptions['user_id']);
            if (!$defaultOptions['include_childs']) {
                if ($tmp) {
                    self::$sqlConds .= " AND b.user_id = '{$defaultOptions['user_id']}'";
                }
                else {
                    self::$sqlConds .= " AND b.username = '{$defaultOptions['user_id']}'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if($defaultOptions['freeze'] == -1) {
                        if (!$user = users::getItem($defaultOptions['user_id'], -1)) {
                            return array();
                        }
                    }else{
                        if (!$user = users::getItem($defaultOptions['user_id'], -1,false,1,1)) {
                            return array();
                        }
                    }
                    $defaultOptions['user_id'] = $user['user_id'];
                }
                if($defaultOptions['freeze'] == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $defaultOptions['user_id'],
                        'recursive' => 1,
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1,-1,'',1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $defaultOptions['user_id'],
                        'recursive' => 1,
                        'freeze' => 1
                    ]);
                }
                self::$sqlConds .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($defaultOptions['start_time'] != '') {
            self::$sqlConds .= " AND a.create_time >= '{$defaultOptions['start_time']}'";
        }
        if ($defaultOptions['end_time'] != '') {
            self::$sqlConds .= " AND a.create_time <= '{$defaultOptions['end_time']}'";
        }
        if ($defaultOptions['send_start_time'] != '') {
            self::$sqlConds .= " AND a.send_prize_time >= '{$defaultOptions['send_start_time']}'";
        }
        if ($defaultOptions['send_end_time'] != '') {
            self::$sqlConds .= " AND a.send_prize_time <= '{$defaultOptions['send_end_time']}'";
        }
        if ($defaultOptions['cancel_status'] != -1) {
            if ($defaultOptions['cancel_status'] == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= " AND a.cancel_status <> 0";
            }
            else {
                self::$sqlConds .= " AND a.cancel_status = '{$defaultOptions['cancel_status']}'";
            }
        }

        if( $defaultOptions['betMoney'] > 0 ){
            self::$sqlConds .= " AND a.amount >= {$defaultOptions['betMoney']}";
        }

        if( $defaultOptions['winMoney'] > 0 ){
            self::$sqlConds .= " AND a.prize >= {$defaultOptions['betMoney']}";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($defaultOptions['order_by']) {

            $sql .= self::$sqlConds." ORDER BY {$defaultOptions['order_by']}";
        } else {
            $sql .= self::$sqlConds." ORDER BY package_id DESC";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($defaultOptions['start'] > -1) {
            $sql .= " LIMIT {$defaultOptions['start']}, {$defaultOptions['amount']}";
        }
        $result = $GLOBALS['db']->getAll($sql , array(),'package_id');

        return $result;
    }
/********************** author snow  添加数组传值********************************************/

    static public function getPackages($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '', $cancel_status = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE, $is_award = -1,$betMoney=0,$winMoney=0,$freeze = -1)
    {
        $sql = 'SELECT a.*,b.username,b.status AS user_status FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        self::$sqlConds = '';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            self::$sqlConds .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            self::$sqlConds .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            self::$sqlConds .= " AND a.check_prize_status = " . intval($check_prize_status);
        }
        if ($is_test != -1) {
            self::$sqlConds .= " AND b.is_test = " . intval($is_test);
        }

        if (!empty($issue) && is_array($issue)) {
            self::$sqlConds .= " AND a.issue IN ('" . implode("','", $issue) . "')";
        }
        elseif ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            self::$sqlConds .= " AND a.issue = '$issue'";
        }
        if ($trace_id != -1) {
            if ($trace_id == -255) {
                self::$sqlConds .= " AND a.trace_id > 0";
            }
            else {
                self::$sqlConds .= " AND a.trace_id = '$trace_id'";
            }
        }
        if ($modes != 0) {
            self::$sqlConds .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$sqlConds .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$sqlConds .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if($freeze == -1) {
                        if (!$user = users::getItem($user_id, -1)) {
                            return array();
                        }
                    }else{
                        if (!$user = users::getItem($user_id, -1,false,1,1)) {
                            return array();
                        }
                    }
                    $user_id = $user['user_id'];
                }
                if($freeze == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1,-1,'',1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'freeze' => 1
                    ]);
                }
                self::$sqlConds .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            self::$sqlConds .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            self::$sqlConds .= " AND a.create_time <= '$end_time'";
        }
        if ($send_start_time != '') {
            self::$sqlConds .= " AND a.send_prize_time >= '$send_start_time'";
        }
        if ($send_end_time != '') {
            self::$sqlConds .= " AND a.send_prize_time <= '$send_end_time'";
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= " AND a.cancel_status <> 0";
            }
            else {
                self::$sqlConds .= " AND a.cancel_status = '$cancel_status'";
            }
        }

        if( $betMoney > 0 ){
            self::$sqlConds .= " AND a.amount >= $betMoney";
        }

        if( $winMoney > 0 ){
            self::$sqlConds .= " AND a.prize >= $winMoney";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($order_by) {

            $sql .= self::$sqlConds." ORDER BY $order_by";
        } else {
            $sql .= self::$sqlConds." ORDER BY package_id DESC";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql , array(),'package_id');

        return $result;
    }

    static public function new_getPackages2($lottery_id = 0, $check_prize_status = -1, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $cancel_status = -1, $order_by = '', $start = -1, $limit = DEFAULT_PER_PAGE,$select="a.*,b.username,b.status AS user_status",$is_trace=0)
    {
        $sql="SELECT * FROM (SELECT * FROM packages WHERE user_id=$user_id AND trace_id=0 UNION (SELECT * FROM packages WHERE trace_id IN (SELECT trace_id FROM traces WHERE user_id=$user_id) GROUP BY trace_id) ORDER BY create_time DESC LIMIT $start,$limit) AS a LEFT JOIN users b ON a.user_id=b.user_id";
        if($is_trace){
            $sql.=' LEFT JOIN traces c ON c.trace_id=a.trace_id WHERE c.status>=0 AND c.status<=3 AND a.trace_id!=0';
        }else{
            $sql.=' WHERE 1';
        }
        self::$sqlConds = '';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            self::$sqlConds .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            self::$sqlConds .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            self::$sqlConds .= " AND a.check_prize_status = " . intval($check_prize_status);
        }

        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$sqlConds .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$sqlConds .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if (!$user = users::getItem($user_id, -1)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                ]);
                self::$sqlConds .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            self::$sqlConds .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            self::$sqlConds .= " AND a.create_time <= '$end_time'";
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= " AND a.cancel_status <> 0";
            }
            else {
                self::$sqlConds .= " AND a.cancel_status = '$cancel_status'";
            }
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($order_by) {

            $sql .= self::$sqlConds." ORDER BY $order_by";
        } else {
            $sql .= self::$sqlConds." ORDER BY package_id DESC";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($start > -1) {
            $sql .= " LIMIT $start, $limit";
        }
        $result = $GLOBALS['db']->getAll($sql , array(),'package_id');

        return $result;
    }
    static public function new_getPackages($lottery_id = 0, $check_prize_status = -1, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $cancel_status = -1, $order_by = '', $start = -1, $limit = DEFAULT_PER_PAGE,$select="a.*,b.username,b.status AS user_status",$is_trace=0)
    {
        $sql = 'SELECT '.$select.' FROM packages a LEFT JOIN users b ON a.user_id=b.user_id';
        if($is_trace==1){
            $sql.=' LEFT JOIN traces c ON c.trace_id=a.trace_id WHERE 1';
        }else{
            $sql.=' WHERE 1';
        }
        self::$sqlConds = '';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            self::$sqlConds .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            self::$sqlConds .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            self::$sqlConds .= " AND a.check_prize_status = " . intval($check_prize_status);
        }
        if($is_trace==0){
            self::$sqlConds .= " AND a.trace_id = 0";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$sqlConds .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$sqlConds .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if (!$user = users::getItem($user_id, -1)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                ]);
                self::$sqlConds .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            self::$sqlConds .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            self::$sqlConds .= " AND a.create_time <= '$end_time'";
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= " AND a.cancel_status <> 0";
            }
            else {
                self::$sqlConds .= " AND a.cancel_status = '$cancel_status'";
            }
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($order_by) {

            $sql .= self::$sqlConds." ORDER BY $order_by";
        } else {
            $sql .= self::$sqlConds." ORDER BY package_id DESC";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($start > -1) {
            $sql .= " LIMIT $start, $limit";
        }
        $result = $GLOBALS['db']->getAll($sql , array(),'package_id');

        return $result;
    }
    static public function new_getPackageTotals($lottery_id = 0, $check_prize_status = -1, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $cancel_status = -1, $order_by = '', $is_trace=0,$select="count(*) as count",$freeze = -1)
    {
        if($is_trace==1){
            $sql='SELECT '.$select.' FROM packages a LEFT JOIN traces c ON c.trace_id=a.trace_id LEFT JOIN users b ON a.user_id=b.user_id WHERE c.status>=0 AND c.status<=3 AND a.trace_id!=0';
        }else{
            $sql='SELECT '.$select.' FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        }
        self::$sqlConds = '';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            self::$sqlConds .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            self::$sqlConds .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            self::$sqlConds .= " AND a.check_prize_status = " . intval($check_prize_status);
        }
        if($is_trace==0){
            self::$sqlConds .= " AND a.trace_id = 0";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$sqlConds .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$sqlConds .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if($freeze == -1) {
                        if (!$user = users::getItem($user_id, -1)) {
                            return array();
                        }
                    }else{
                        if (!$user = users::getItem($user_id, -1,false,1,1)) {
                            return array();
                        }
                    }
                    $user_id = $user['user_id'];
                }
                if($freeze == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1,-1,'',1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'freeze' => 1
                    ]);
                }
                self::$sqlConds .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            self::$sqlConds .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            self::$sqlConds .= " AND a.create_time <= '$end_time'";
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= " AND a.cancel_status <> 0";
            }
            else {
                self::$sqlConds .= " AND a.cancel_status = '$cancel_status'";
            }
        }
        if ($order_by) {

            $sql .= self::$sqlConds." ORDER BY $order_by";
        } else {
            $sql .= self::$sqlConds." ORDER BY package_id DESC";
        }
        $result = $GLOBALS['db']->getRow($sql , array(),'package_id');

        return $result;
    }
    static public function new_getPackageTotals2($lottery_id = 0, $check_prize_status = -1, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $cancel_status = -1, $order_by = '', $is_trace=0,$select="count(*) as count",$freeze = -1)
    {
        if($is_trace){
            $sql="SELECT $select FROM (SELECT * FROM packages WHERE user_id=$user_id AND trace_id=0 UNION (SELECT * FROM packages WHERE trace_id IN (SELECT trace_id FROM traces WHERE user_id=$user_id) GROUP BY trace_id)) AS a LEFT JOIN traces c ON c.trace_id=a.trace_id LEFT JOIN users b ON a.user_id=b.user_id WHERE c.status>=0 AND c.status<=3 AND a.trace_id!=0";
        }else{
            $sql="SELECT $select FROM (SELECT * FROM packages WHERE user_id=$user_id AND trace_id=0 UNION (SELECT * FROM packages WHERE trace_id IN (SELECT trace_id FROM traces WHERE user_id=$user_id) GROUP BY trace_id)) AS a  LEFT JOIN users b ON a.user_id=b.user_id WHERE 1";
        }
        self::$sqlConds = '';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            self::$sqlConds .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            self::$sqlConds .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            self::$sqlConds .= " AND a.check_prize_status = " . intval($check_prize_status);
        }

        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    self::$sqlConds .= " AND b.user_id = '$user_id'";
                }
                else {
                    self::$sqlConds .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if($freeze == -1) {
                        if (!$user = users::getItem($user_id, -1)) {
                            return array();
                        }
                    }else{
                        if (!$user = users::getItem($user_id, -1,false,1,-1)) {
                            return array();
                        }
                    }
                    $user_id = $user['user_id'];
                }
                if($freeze == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1,-1,'',1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'freeze' => 1
                    ]);
                }
                self::$sqlConds .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            self::$sqlConds .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            self::$sqlConds .= " AND a.create_time <= '$end_time'";
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= " AND a.cancel_status <> 0";
            }
            else {
                self::$sqlConds .= " AND a.cancel_status = '$cancel_status'";
            }
        }
        if ($order_by) {

            $sql .= self::$sqlConds." ORDER BY $order_by";
        } else {
            $sql .= self::$sqlConds." ORDER BY package_id DESC";
        }
        $result = $GLOBALS['db']->getRow($sql , array(),'package_id');

        return $result;
    }

    static public function getNewPackagesNumber(){
        //增加全局统计
        $sql = 'SELECT COUNT(*) AS count,SUM(a.prize) as prizes,SUM(a.amount) as amounts FROM packages a INNER JOIN users b ON a.user_id=b.user_id WHERE 1'.self::$sqlConds;
        return $GLOBALS['db']->getRow($sql);

    }

    static public function getPackageTotal($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '', $cancel_status = -1,$is_trace = 0 ,$freeze = -1)
    {
        $sql = 'SELECT count(*) as total_count, SUM(a.prize) as total_prize, sum(a.amount) as total_amount, sum(a.prize-if(a.cancel_status=0 and a.check_prize_status>0, a.amount, 0)) as total_profit FROM packages a LEFT JOIN users b ON a.user_id=b.user_id';
        if($is_trace){
            $sql.=' LEFT JOIN traces c ON c.trace_id=a.trace_id WHERE c.status>=0 AND c.status<=3 AND a.trace_id!=0';
        }else{
            $sql.=' WHERE 1';
        }
        if(is_array($lottery_id) && !empty($lottery_id)) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            if( is_array($check_prize_status) ){
                $sql .= " AND a.check_prize_status IN (" . implode(',',$check_prize_status).")";
            }else{
                $sql .= " AND a.check_prize_status = " . $check_prize_status;
            }
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }

        if (!empty($issue) && is_array($issue)) {
            $sql .= " AND a.issue IN ('" . implode("','", $issue) . "')";
        }
        elseif ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            $sql .= " AND a.issue = '$issue'";
        }
        if ($trace_id != -1) {
            if ($trace_id == -255) {
                $sql .= " AND a.trace_id > 0";
            }
            else {
                $sql .= " AND a.trace_id = '$trace_id'";
            }
        }
        if ($modes != 0) {
            $sql .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if($freeze == -1) {
                        if (!$user = users::getItem($user_id, -1)) {
                            return array();
                        }
                    }else{
                        if (!$user = users::getItem($user_id, -1,false,1,1)) {
                            return array();
                        }
                    }
                    $user_id = $user['user_id'];
                }
                if($freeze == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1,-1,'',1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'freeze' => 1
                    ]);
                }
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        if ($send_start_time != '') {
            $sql .= " AND a.send_prize_time >= '$send_start_time'";
        }
        if ($send_end_time != '') {
            $sql .= " AND a.send_prize_time <= '$send_end_time'";
        }


        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                $sql .= " AND a.cancel_status <> 0";
            }
            else {
                $sql .= " AND a.cancel_status = ".intval($cancel_status);
            }
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result;
    }

    static public function getPrizeTotal($lottery_id = 0, $check_prize_status = -1, $issue = '', $cancel_status = -1, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '')
    {
        $sql = "SELECT COUNT(*) AS total_count, SUM(a.prize) AS total_prize FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1";
        if($lottery_id>0) $sql.=" AND a.lottery_id = {$lottery_id}";
        if(!empty($issue)) $sql.=" AND a.issue = {$issue}";
        if($check_prize_status!=-1) $sql.="  AND a.check_prize_status = {$check_prize_status}";
        if($cancel_status!=-1) $sql.="  AND b.is_test = 0 AND a.cancel_status = {$cancel_status}";
        if ($start_time != '') $sql .= " AND a.create_time >= '$start_time'";
        if ($end_time != '') $sql .= " AND a.create_time <= '$end_time'";
        if ($send_start_time != '') $sql .= " AND a.send_prize_time >= '$send_start_time'";
        if ($send_end_time != '') $sql .= " AND a.send_prize_time <= '$send_end_time'";
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }
    static public function getAmountTotal($lottery_id = 0, $check_prize_status = -1, $issue = '', $cancel_status = -1, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '')
    {
        $sql = "SELECT COUNT(*) AS total_count, sum(amount) as total_amount FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1";
        if($lottery_id>0) $sql.=" AND a.lottery_id = {$lottery_id}";
        if(!empty($issue)) $sql.=" AND a.issue = {$issue}";
        if($check_prize_status!=-1) $sql.="  AND a.check_prize_status = {$check_prize_status}";
        if($cancel_status!=-1) $sql.="  AND b.is_test = 0 AND a.cancel_status = {$cancel_status}";
        if ($start_time != '') $sql .= " AND a.create_time >= '$start_time'";
        if ($end_time != '') $sql .= " AND a.create_time <= '$end_time'";
        if ($send_start_time != '') $sql .= " AND a.send_prize_time >= '$send_start_time'";
        if ($send_end_time != '') $sql .= " AND a.send_prize_time <= '$send_end_time'";
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }
    //针对追号单总计查询
    static public function getTracePackageTotal($lottery_id = 0, $user_id = '', $trace_status = -1, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $package_cancel_status = -1, $modes = 0, $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '')
    {
        $sql = 'SELECT SUM(a.prize) as total_prize, sum(a.amount) as total_amount, sum(a.prize-if(a.cancel_status=0 and a.check_prize_status>0, a.amount, 0)) as total_profit FROM packages a LEFT JOIN traces c ON a.trace_id = c.trace_id LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        } elseif ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }

        if ($check_prize_status != -1) {
            $sql .= " AND a.check_prize_status = " . intval($check_prize_status);
        }

        if ($trace_status != -1) {
            $sql .= " AND c.status = " . intval($trace_status);
        }

        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }

        if (!empty($issue) && is_array($issue)) {
            $sql .= " AND a.issue IN ('" . implode("','", $issue) . "')";
        } elseif ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            $sql .= " AND a.issue = '$issue'";
        }

        if ($trace_id != -1) {
            if ($trace_id == -255) {
                $sql .= " AND a.trace_id > 0";
            }
            else {
                $sql .= " AND a.trace_id = '$trace_id'";
            }
        }
        if ($modes != 0) {
            $sql .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if (!$user = users::getItem($user_id, -1)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        if ($send_start_time != '') {
            $sql .= " AND a.send_prize_time >= '$send_start_time'";
        }
        if ($send_end_time != '') {
            $sql .= " AND a.send_prize_time <= '$send_end_time'";
        }
        if ($package_cancel_status != -1) {
            if ($package_cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                $sql .= " AND a.cancel_status <> 0";
            }
            else {
                $sql .= " AND a.cancel_status = '$package_cancel_status'";
            }
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result;
    }

    /**
     *  用于后台统计用
     * @param type $lottery_id
     * @param type $issue
     * @param type $trace_id
     * @param type $modes
     * @param type $user_id
     * @param type $include_childs
     * @param type $start_time
     * @param type $end_time
     * @param type $cancel_status
     * @param type $order_by
     * @param type $start
     * @param type $amount
     * @return type
     */
    static public function getReportPackages($lottery_id = 0, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $cancel_status = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.username FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            $sql .= " AND a.issue = '$issue'";
        }
        if ($trace_id != -1) {
            if ($trace_id == -255) {
                $sql .= " AND a.trace_id > 0";
            }
            else {
                $sql .= " AND a.trace_id = '$trace_id'";
            }
        }
        if ($modes != 0) {
            $sql .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if (!$include_childs) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if (!$user = users::getItem($user_id, -1)) {
                        return array();
                    }
                    $user_id = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $user_id,
                    'recursive' => 1,
                ]);
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            $sql .= " AND a.send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.send_prize_time <= '$end_time'";
        }
        if ($cancel_status != -1) {
            $sql .= " AND a.cancel_status = '$cancel_status'";
        }
        if ($order_by) {
            $sql .= " ORDER BY $order_by";
        }
        else {
            $sql .= " ORDER BY package_id DESC";
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        $result = $GLOBALS['db']->getAll($sql, array(),'package_id');

        return $result;
    }

    static public function getPackagesNumber($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $cancel_status = -1,$betMoney=0,$winMoney=0,$freeze = -1)
    {
        $sql = 'SELECT COUNT(*) AS count FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            $sql .= " AND a.lottery_id IN ( '" . implode("','", $lottery_id) . "')";
        }
        elseif ($lottery_id != 0) {
            $sql .= " AND a.lottery_id = " . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            $sql .= " AND a.check_prize_status = " . intval($check_prize_status);
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            $sql .= " AND a.issue = '$issue'";
        }
        if ($trace_id != -1) {
            if ($trace_id == -255) {
                $sql .= " AND a.trace_id > 0";
            }
            else {
                $sql .= " AND a.trace_id = '$trace_id'";
            }
        }
        if ($modes != 0) {
            $sql .= " AND a.modes = '$modes'";
        }
        if ($user_id != '') {
            $tmp = preg_match('`^\d+$`Ui', $user_id);
            if ($include_childs == 0) {
                if ($tmp) {
                    $sql .= " AND b.user_id = '$user_id'";
                }
                else {
                    $sql .= " AND b.username = '$user_id'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    if($freeze == -1) {
                        if (!$user = users::getItem($user_id, -1)) {
                            return array();
                        }
                    }else{
                        if (!$user = users::getItem($user_id, -1,false,1,1)) {
                            return array();
                        }
                    }
                    $user_id = $user['user_id'];
                }
                if($freeze == -1) {
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                    ]);
                }else{
                    // $teamUsers = users::getUserTree($user_id, true, 1, -1,-1,'',1);
                    $teamUsers = users::getUserTreeField([
                        'field' => ['user_id'],
                        'parent_id' => $user_id,
                        'recursive' => 1,
                        'freeze' => 1
                    ]);
                }
                $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            $sql .= " AND a.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND a.create_time <= '$end_time'";
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                $sql .= " AND a.cancel_status <> 0";
            }
            else {
                $sql .= " AND a.cancel_status = '$cancel_status'";
            }
        }

        if( $betMoney > 0 ){
            self::$sqlConds .= " AND a.amount >= $betMoney";
        }

        if( $winMoney > 0 ){
            self::$sqlConds .= " AND a.prize >= $winMoney";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getPackagesById($ids, $user_id = 0)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        if (empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM packages WHERE package_id IN(' . implode(',', $ids) . ')';
        if ($user_id) {
            $sql .= " AND user_id = $user_id";
        }

        return $GLOBALS['db']->getAll($sql, array(),'package_id');
    }

    /**
     * 统计 取得团队用户状况   可以按订单状态 , 撤单状态 查询
     * @param type $top_id 代表默认全部总代
     * @param type $prize_status  订单状态
     * @param type $cancel_status  撤单状态
     * @param type $start_time
     * @param type $end_time
     * @return array
     * @author KK 2013-05-27
     */
    static public function getTopUserPackages($top_id = -1, $prize_status = -1, $cancel_status = -1, $start_time = '', $end_time = '')
    {
        $sql = 'SELECT top_id, COUNT(DISTINCT user_id) AS total_user FROM packages WHERE 1';
        if ($top_id != -1) {
            $sql .= " AND top_id = " . intval($top_id);
        }
        if ($prize_status != -1) {
            $sql .= " AND check_prize_status = " . intval($prize_status);
        }
        if ($cancel_status != -1) {
            $sql .= " AND cancel_status = " . intval($cancel_status);
        }
        if ($start_time != '') {
            $sql .= " AND send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND send_prize_time <= '$end_time'";
        }
        $sql .= " GROUP BY top_id";

        return $GLOBALS['db']->getAll($sql, array(),'top_id');
    }

    /**
     * 指定时间范围内，所有用户范围用户流水排行
     * @param type $start_time
     * @param type $end_time
     * @param type $orderType 0正序 1倒序
     * @param type $limit  排行数目 默认100
     * @return type
     * @throws exception2
     * @author jerry 2014-07-27
     */
    static public function getUsersRankPackages($start_time = '', $end_time = '', $orderType = 1, $limit = 100)
    {
        if (!is_numeric($limit)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT p.user_id,u.username, SUM(p.amount) AS total_amount ,ubc.bank_username,u.reg_ip, u.last_ip, ubc.card_num FROM packages AS p LEFT JOIN users AS u ON p.user_id=u.user_id LEFT JOIN user_bind_cards AS ubc ON u.user_id=ubc.user_id WHERE 1';

        if ($start_time != '') {
            $sql .= " AND p.send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND p.send_prize_time <= '$end_time'";
        }
        //只有有效的订单
        $sql .= " AND p.cancel_status =0 ";
        $sql .= " AND u.is_test = 0";
        $sql .= " AND u.status = 8";
        $sql .= ' GROUP BY p.user_id ';
        //倒序还是正序
        $sql .= ' ORDER BY total_amount  ' . ($orderType == 1 ? 'DESC' : 'ASC');
        $sql .= ' LIMIT ' . $limit;

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 指定时间范围内，所有用户范围用户输赢排行
     * @param type $start_time
     * @param type $end_time
     * @param type $orderType 0正序 1倒序
     * @param type $limit  排行数目 默认100
     * @return type
     * @throws exception2
     */
    static public function getUsersRankWin($start_time = '', $end_time = '', $orderType = 1, $limit = 100)
    {
        if (!is_numeric($limit)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT p.user_id,u.username,u.reg_ip, u.last_ip, SUM(p.prize-p.amount) AS total_win FROM packages AS p LEFT JOIN users AS u ON p.user_id=u.user_id WHERE 1';

        if ($start_time != '') {
            $sql .= " AND p.send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND p.send_prize_time <= '$end_time'";
        }
        //只有有效的订单
        $sql .= " AND p.cancel_status =0 ";
        $sql .= " AND p.check_prize_status > 0";
        $sql .= " AND u.is_test = 0";
        $sql .= " AND u.status = 8";
        $sql .= ' GROUP BY p.user_id ';
        $sql .= ' LIMIT ' . $limit;
        $retData = $sort_arr = array(); //这个变量用于多维排序;
        if($retData = $GLOBALS['db']->getAll($sql)){
            foreach ($retData as $k => $v) {
                $teamTotalProjects = projects::getChildReport($v['user_id'] ,  $start_time , $end_time);
                $retData[$k]['total_amount'] = $v['total_win'] + $teamTotalProjects[$v['user_id']]['total_rebate'];
                $sort_arr[$k] = $retData[$k]['total_amount'];
            }

            if ($orderType == 0) {
                array_multisort($sort_arr, SORT_ASC, $retData);
            } else {
                array_multisort($sort_arr, SORT_DESC, $retData);
            }
        }

        return $retData;
    }

    /**
     *  指定时间范围内，用户每日的流水
     * @param type $start_time
     * @param type $end_time
     * @param type $lottery_id 限定某个彩种的id
     * @param type $groupByDay 是否在按照用户分组的情况下，再按照日期分组 默认 是
     * @return type
     * @throws exception2
     */
    static public function getUsersDayPackages($start_time, $end_time, $lottery_id = 0, $user_id = 0, $groupByDay = true)
    {
        if (!$start_time || !$end_time) {
            throw new exception2('参数无效');
        }
        $sql = 'SELECT p.user_id,u.username, SUM(p.amount) AS total_amount,DATE(p.create_time) AS create_date, SUM(p.prize) AS total_prize  FROM packages AS p LEFT JOIN users AS u ON p.user_id=u.user_id WHERE 1';
        $sql .= " AND p.create_time >= '$start_time'";
        $sql .= " AND p.create_time <= '$end_time'";
        if ($lottery_id != 0) {
            if (is_array($lottery_id)) {
                $sql .= " AND p.lottery_id IN (" . join(',', $lottery_id) . ")";
            }
            else {
                $sql .= " AND p.lottery_id = $lottery_id";
            }
        }
        if ($user_id != 0) {
            $sql .= " AND p.user_id = $user_id";
        }
        //只有有效的订单
        $sql .= " AND p.cancel_status =0 ";
        $sql .= " AND p.check_prize_status > 0";
        $sql .= " AND u.is_test = 0";
        $sql .= " AND u.status = 8";
        $sql .= ' GROUP BY p.user_id';
        if ($groupByDay) {
            $sql .= ',create_date';
        }

//        $sql .= " HAVING total_amount >= $min";
//        $sql .= " AND total_amount <= $max";

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 指定条件查出具体彩种或者玩法每期或者每种玩法销量,奖金，盈亏
     * @param type $lottery_id
     * @param type $method_id
     * @param type $types
     * @param type $start_time
     * @param type $end_time
     * @param type $group
     * @param type $rankType 0正序 1倒序
     * @param type $limit  排行数目 默认100
     * @return array
     * @throws exception2
     * @author jerry 2014-07-27 ,update nyjah2015-07-28
     */
    static public function getIssueMethodRank($lottery_id = -1, $method_id = -1, $types = 'sale', $start_time = '', $end_time = '', $group = 'issue', $rankType = 1, $limit = 100)
    {
        switch ($types) {
            case 'sale':
                $sql = 'SELECT p1.issue, m.cname AS method_name, l.cname, ' . ($method_id == -1 ? 'SUM(p1.amount)' : 'SUM(p2.amount)') . ' AS total_amount , COUNT(p1.package_id) AS  total_count ';
                break;
            case 'prize':
                $sql = 'SELECT p1.issue, m.cname AS method_name, l.cname, ' . ($method_id == -1 ? 'SUM(p1.prize)' : 'SUM(p2.prize)' ) . ' AS total_prize , COUNT(p1.package_id) AS  total_count ';
                break;
            case 'profit':
                $sql = 'SELECT p1.issue, m.cname AS method_name, l.cname, ' . ($method_id == -1 ? 'SUM(p1.amount)' : 'SUM(p2.amount)') . ' AS total_amount ,' . ($method_id == -1 ? 'SUM(p1.prize)' : 'SUM(p2.prize)') . ' AS total_prize , COUNT(p1.package_id) AS  total_count ';
                break;
            default:
                throw new exception2('未知查询项');
        }

        $sql .='FROM packages AS p1 LEFT JOIN projects AS p2 ON p1.package_id = p2.package_id  LEFT JOIN lottery AS l ON p2.lottery_id=l.lottery_id LEFT JOIN methods AS m ON p2.method_id = m.method_id LEFT JOIN users AS u ON p1.user_id=u.user_id WHERE 1';

        if ($lottery_id != -1) {
            $sql .= " AND p1.lottery_id = $lottery_id";
        }
        if ($method_id != -1) {
            $sql .= " AND p2.method_id = $method_id";
        }
        if ($start_time != '') {
            $sql .= " AND p1.send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND p1.send_prize_time <= '$end_time'";
        }

        //合法用户的有效订单
        $sql .= " AND p1.cancel_status =0 ";
        $sql .= " AND p1.check_prize_status > 0";
        $sql .= " AND u.is_test = 0";
        $sql .= " AND u.status = 8";

        if ($group == 'issue') {
            $where = ' GROUP BY ' . ($method_id == -1 ? 'p1.issue,p1.lottery_id' : 'p2.issue,p2.method_id');
        }
        if ($group == 'method') {
            $where = ' GROUP BY p2.method_id ';
        }

        $where .= ' ORDER BY ' . ($types == 'sale' ? 'total_amount ' : 'total_prize ') . ($rankType == 1 ? 'DESC' : 'ASC');
        $where .= ' LIMIT ' . $limit;

        return $GLOBALS['db']->getAll($sql . $where);
    }

    //必须选择一个采种以进行查询
    static public function getIssueSales($lottery_id, $is_test = -1, $modes = 0, $start_time = '', $end_time = '')
    {
        $sql = "SELECT p.issue, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE p.cancel_status = 0";
        if ($is_test != -1) {
            $sql .= " AND u.is_test = $is_test";
        }
        if ($lottery_id != 0) {
            $sql .= " AND p.lottery_id = $lottery_id";
        }
        if ($modes != 0) {
            $sql .= " AND p.modes = '$modes'";
        }
        if ($start_time != '') {
            $sql .= " AND p.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND p.create_time <= '$end_time'";
        }
        $sql .= ' GROUP BY p.issue ORDER BY p.issue ASC';
        $result1 = $GLOBALS['db']->getAll($sql, array(),'issue');
        //得到各奖期总返点
        $sql = "SELECT udr.issue, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id=u.user_id WHERE udr.status != 2";
        if ($is_test != -1) {
            $sql .= " AND u.is_test = $is_test";
        }
        if ($lottery_id != 0) {
            $sql .= " AND udr.lottery_id = $lottery_id";
        }
        if ($modes != 0) {
            $sql .= " AND udr.modes = '$modes'";
        }
        if ($start_time != '') {
            $sql .= " AND udr.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND udr.create_time <= '$end_time'";
        }
        $sql .= ' GROUP BY udr.issue ORDER BY udr.issue ASC';
        $result2 = $GLOBALS['db']->getAll($sql, array(),'issue');
        foreach ($result1 as $issue => $v) {
            $result1[$issue]['total_rebate'] = isset($result2[$issue]['total_rebate']) ? $result2[$issue]['total_rebate'] : 0;
        }

        return $result1;
    }

    //前后台会员报表用 得到任意用户及其直属下级的总投注，总奖金，总返点，下级贡献佣金 IFNULL(SUM(p.amount),0)
    static public function getReportByUid($user_id, $start_time = '', $end_time = ''){
        $sql=<<<sql
SELECT 
p.user_id,IFNULL(SUM(p.amount),0) AS total_amount, IFNULL(SUM(p.prize),0) AS total_prize  
FROM packages p 
LEFT JOIN users u 
ON p.user_id=u.user_id 
WHERE u.status = 8 
AND p.cancel_status = 0
AND u.user_id={$user_id}
AND p.send_prize_time >= '{$start_time}'
AND p.send_prize_time <= '{$end_time}'
sql;
        return $GLOBALS['db']->getRow($sql);
    }
    static public function getChildReport($user_id, $start_time = '', $end_time = '',$freeze = -1)
    {
        $cacheKey = (__FUNCTION__ . '_' . $user_id . date("YmdHis", strtotime($start_time)) . '_' . date("YmdHis", strtotime($end_time)));
        $result = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
        if ($result !== false) {
            //logdump("getChildReport() user_id={$user_id} CACHE OK");
            return $result;
        } else {
            //logdump("getChildReport() user_id={$user_id} 取缓存失败");
        }

        //先得到所有孩子 140609 取所有孩子
        if($freeze == -1) {
            // $users = users::getUserTree($user_id, true, 1, 8);
            $users = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'username', 'level', 'type'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
            ]);
            if (!$users) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), 60 * 30); //放入缓存
                return array();
            }
        }else{
            // $users = users::getUserTree($user_id, true, 1, 8,-1,'',1);
            $users = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'username', 'level', 'type'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
                'freeze' => 1
            ]);
            if (!$users) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), 60 * 30); //放入缓存
                return array();
            }
        }
        $targetUsers = array();
        foreach ($users as $v) {
            if ($v['parent_id'] == $user_id) {
                $targetUsers[$v['user_id']] = $v;
            }
        }

        //先初始化
        $result = array();
        foreach ($targetUsers as $k => $v) {
                $result[$v['user_id']]['user_id'] = $v['user_id'];
                $result[$v['user_id']]['username'] = $v['username'];
                $result[$v['user_id']]['level'] = $v['level'];
                $result[$v['user_id']]['is_test'] = $v['is_test'];
                $result[$v['user_id']]['type'] = $v['type'];
                $result[$v['user_id']]['parent_id'] = $v['parent_id'];
                $result[$v['user_id']]['total_amount'] = 0;
                $result[$v['user_id']]['total_prize'] = 0;
                $result[$v['user_id']]['total_rebate'] = 0;
                $result[$v['user_id']]['total_contribute_rebate'] = 0;
            // $result[$v['user_id']]['pt_game_win'] = 0;
            // $result[$v['user_id']]['pt_buy_amount'] = 0;
            // $result[$v['user_id']]['pt_prize'] = 0;  //PT 中奖金额
            // $result[$v['user_id']]['pt_amount'] = 0;
        }

        //目标直指直接下级
        $self = $users[$user_id];
        $selfStatistic = array(
            'user_id' => $self['user_id'],
            'username' => $self['username'],
            'level' => $self['level'],
            'type' => $self['type'],
            'parent_id' => $self['parent_id'],
            'total_amount' => 0,
            'total_prize' => 0,
            'total_rebate' => 0,
            'total_contribute_rebate' => 0, //表示下级贡献的佣金量，因此对自己而言始终为0
            // 'pt_game_win' => 0, //PT 盈亏
            // 'pt_buy_amount' => 0, //PT 投注
            // 'pt_prize' => 0, //PT 中奖金额
            // 'pt_amount' => 0, //PT 余额
        );
        //得到自身所属层级，总代为0级，一代为1级，二代为2级，会员为3级
        // if ($self['parent_id'] == 0) {
        //     $selfLevel = 0;
        // }
        // else {
        //     $selfLevel = count(explode(',', $self['parent_tree']));
        // }
        $selfLevel = $self['level'] ;
        //步骤1.得到自己和直接下级(包括下面所有级别孩子)的总投注，总奖金 需一次性取出再手动合并
        if($freeze == -1) {
            $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize  FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0';
        }else{
            $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize  FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE (u.status = 8 OR u.status = 1) AND p.cancel_status = 0';
        }
        if ($start_time != '') {
            $sql .= " AND p.send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND p.send_prize_time <= '$end_time'";
        }
        $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($users), 'p.user_id');
        $sql .= ' GROUP BY p.user_id ORDER BY p.user_id DESC';
        $tmp1 = $GLOBALS['db']->getAll($sql, array(),'user_id');
        //若自己也有记录，先分出来
        if (isset($tmp1[$self['user_id']])) {
            $selfStatistic['total_amount'] = $tmp1[$self['user_id']]['total_amount'];
            $selfStatistic['total_prize'] = $tmp1[$self['user_id']]['total_prize'];
            unset($tmp1[$self['user_id']]);
        }

        foreach ($tmp1 as $k => $v) {
            if (isset($result[$k])) { //直接下级
                if(isset( $v['total_amount'])) {
                    $result[$k]['total_amount'] += $v['total_amount'];
                }else{
                    $result[$k]['total_amount'] +=0;
                }
                if(isset($v['total_prize'])) {
                    $result[$k]['total_prize'] += $v['total_prize'];
                }else{
                    $result[$k]['total_prize'] +=0;
                }
            }
            else { //非直接下级
                $parent_ids = explode(',', $v['parent_tree']);
                if(isset($parent_ids[$selfLevel + 1]) && isset($result[$parent_ids[$selfLevel + 1]])) {
                    $result[$parent_ids[$selfLevel + 1]]['total_amount'] += $v['total_amount'];
                    $result[$parent_ids[$selfLevel + 1]]['total_prize'] += $v['total_prize'];
                }
            }
        }

        //步骤2.得到某用户团队(自己和所有下级)的总返点
        $tmp2 = userDiffRebates::getTeamRebates($user_id, 0, 0, 0, $start_time, $end_time,'',1);

        //若自己也有记录，先分出来
        if (isset($tmp2[$self['user_id']])) {
            $selfStatistic['total_rebate'] = $tmp2[$self['user_id']]['total_rebate'];
            unset($tmp2[$self['user_id']]);
        }

        foreach ($tmp2 as $k => $v) {
            if (isset($result[$k])) { //直接下级
                $result[$k]['total_rebate'] += $v['total_rebate'];
            }
            else { //非直接下级
                $parent_ids = explode(',', $v['parent_tree']);
                if(isset($parent_ids[$selfLevel + 1]) && isset($result[$parent_ids[$selfLevel + 1]])) {
                    $result[$parent_ids[$selfLevel + 1]]['total_rebate'] += $v['total_rebate'];
                }
            }
        }

        //步骤3.得到直接下级(包括下面所有级别孩子)为其贡献的总佣金
        $result3 = userDiffRebates::getChildContributeRebates($users[$user_id], $start_time, $end_time);
        //krsort($result3);
        //合并结果
        foreach ($result as $k => $v) {
            if (isset($result3[$v['user_id']])) {
                $result[$v['user_id']]['total_contribute_rebate'] = $result3[$v['user_id']]['total_contribute_rebate'];
            }
        }
        /**暂不统计PT**/
        //步骤4.得到某用户团队(自己和所有下级)的总PT盈亏,投注;每一条记录都是自己的数据按日期范围汇总
        // $tmp4 = reportPt::getSumGroupByUser($user_id, $start_time, $end_time, 8);

        // //自己PT数据
        // if (isset($tmp4[$self['user_id']])) {
        //     $selfStatistic['pt_game_win'] = $tmp4[$self['user_id']]['pt_game_win'];
        //     $selfStatistic['pt_buy_amount'] = $tmp4[$self['user_id']]['pt_buy_amount'];
        //     $selfStatistic['pt_prize'] = $tmp4[$self['user_id']]['pt_prize'];
        //     unset($tmp4[$self['user_id']]);
        // }

        // foreach ($tmp4 as $k => $v) {
        //     if (isset($result[$k])) { //直接下级
        //         $result[$k]['pt_game_win'] += $v['pt_game_win'];
        //         $result[$k]['pt_buy_amount'] += $v['pt_buy_amount'];
        //         $result[$k]['pt_prize'] += $v['pt_prize'];
        //     }
        //     else { //非直接下级
        //         $parent_ids = explode(',', $v['parent_tree']);
        //         $result[$parent_ids[$selfLevel + 1]]['pt_game_win'] += $v['pt_game_win'];
        //         $result[$parent_ids[$selfLevel + 1]]['pt_buy_amount'] += $v['pt_buy_amount'];
        //         $result[$parent_ids[$selfLevel + 1]]['pt_prize'] += $v['pt_prize'];
        //     }
        // }

        //加上自己
        $result[$self['user_id']] = $selfStatistic;
        ksort($result);

        /************************ snow 2017/10/31 增加缓存时长 ,当天不缓存 start******************************/
        if($end_time !== date('Y-m-d 23:59:59'))
        {
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, CACHE_EXPIRE_LONG); //放入缓存
        }
        /************************ snow 2017/10/31 增加缓存时长 ,当天不缓存 end  ************************/

        return $result;
    }


    //得到任意用户及其直属下级每日的团队总投注，总奖金，总返点，下级贡献佣金
    static public function getChildDayReport($user_id, $start_time = '', $end_time = '')
    {
        $paramArr = $days = $agent = $dayRport = array();

        $starDay = date('Y-m-d' , strtotime($start_time));
        $endDay = date('Y-m-d' , strtotime($end_time));

        $i = 0;
        do{
            $date = date('Y-m-d',strtotime($starDay) + $i * 86400);
            $days[] = $date;
            $i++;
        }while (strtotime($date) < strtotime($endDay));

        foreach ($days as $day) {
            $beginTime = date("YmdHis", strtotime($day . ' 00:00:00'));
            $endTime = date("YmdHis", strtotime($day . ' 23:59:59'));



            $cacheKey = (__FUNCTION__ . '_' . $user_id . $beginTime . '_' . $endTime);
            $result = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
            if ($result !== false) {
                $dayRport[$day] = $result;
                continue;
            }

            //先得到所有孩子 140609 取所有孩子
            // $users = users::getUserTree($user_id, true, 1, 8);
            $users = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'username', 'level'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8
            ]);
            if (!$users) {
                $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), 60 * 30); //放入缓存
                 $dayRport[$day] = array();
                 continue;
            }

            foreach ($users as $v) {
                if ($v['parent_id'] == $user_id) {
                    $agent[$v['user_id']] = $v;
                }
            }

            //先初始化
            $result = array();

            foreach ($agent as $k => $v) {
                $result[$v['user_id']]['user_id'] = $v['user_id'];
                $result[$v['user_id']]['username'] = $v['username'];
                $result[$v['user_id']]['level'] = $v['level'];
                $result[$v['user_id']]['is_test'] = $v['is_test'];
                $result[$v['user_id']]['parent_id'] = $v['parent_id'];
                $result[$v['user_id']]['total_amount'] = 0;
                $result[$v['user_id']]['total_prize'] = 0;
                $result[$v['user_id']]['total_rebate'] = 0;
                $result[$v['user_id']]['total_contribute_rebate'] = 0;
                $result[$v['user_id']]['pt_game_win'] = 0;
                $result[$v['user_id']]['pt_buy_amount'] = 0;
                $result[$v['user_id']]['pt_prize'] = 0;  //PT 中奖金额
                $result[$v['user_id']]['pt_amount'] = 0;
            }

            //目标直指直接下级
            $self = $users[$user_id];
            $selfStatistic = array(
                'user_id' => $self['user_id'],
                'username' => $self['username'],
                'level' => $self['level'],
                'parent_id' => $self['parent_id'],
                'total_amount' => 0,
                'total_prize' => 0,
                'total_rebate' => 0,
                'total_contribute_rebate' => 0, //表示下级贡献的佣金量，因此对自己而言始终为0
                'pt_game_win' => 0, //PT 盈亏
                'pt_buy_amount' => 0, //PT 投注
                'pt_prize' => 0, //PT 中奖金额
                'pt_amount' => 0, //PT 余额
            );
            //得到自身所属层级，总代为0级，一代为1级，二代为2级，会员为3级
            // if ($self['parent_id'] == 0) {
            //     $selfLevel = 0;
            // }
            // else {
            //     $selfLevel = count(explode(',', $self['parent_tree']));
            // }
            $selfLevel = $self['level'] ; 
            //步骤1.得到自己和直接下级(包括下面所有级别孩子)的总投注，总奖金 需一次性取出再手动合并
            $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0';
            $sql .= " AND p.send_prize_time >= :start_time";
            $sql .= " AND p.send_prize_time <= :end_time";

            $paramArr[':start_time'] = $beginTime;
            $paramArr[':end_time'] = $endTime;
            
            $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($users), 'p.user_id');
            $sql .= ' GROUP BY p.user_id ORDER BY p.user_id DESC';
            $tmp1 = $GLOBALS['db']->getAll($sql, $paramArr,'user_id');
            //若自己也有记录，先分出来
            if (isset($tmp1[$self['user_id']])) {
                $selfStatistic['total_amount'] = $tmp1[$self['user_id']]['total_amount'];
                $selfStatistic['total_prize'] = $tmp1[$self['user_id']]['total_prize'];
                unset($tmp1[$self['user_id']]);
            }

            foreach ($tmp1 as $k => $v) {
                if (isset($result[$k])) { //直接下级
                    $result[$k]['total_amount'] += $v['total_amount'];
                    $result[$k]['total_prize'] += $v['total_prize'];
                }
                else { //非直接下级
                    $parent_ids = explode(',', $v['parent_tree']);
                    $result[$parent_ids[$selfLevel + 1]]['total_amount'] += $v['total_amount'];
                    $result[$parent_ids[$selfLevel + 1]]['total_prize'] += $v['total_prize'];
                }
            }

            //步骤2.得到某用户团队(自己和所有下级)的总返点
            $tmp2 = userDiffRebates::getTeamRebates($user_id, 0, 0, 0, $beginTime, $endTime);

            //若自己也有记录，先分出来
            if (isset($tmp2[$self['user_id']])) {
                $selfStatistic['total_rebate'] = $tmp2[$self['user_id']]['total_rebate'];
                unset($tmp2[$self['user_id']]);
            }

            foreach ($tmp2 as $k => $v) {
                if (isset($result[$k])) { //直接下级
                    $result[$k]['total_rebate'] += $v['total_rebate'];
                }
                else { //非直接下级
                    $parent_ids = explode(',', $v['parent_tree']);
                    $result[$parent_ids[$selfLevel + 1]]['total_rebate'] += $v['total_rebate'];
                }
            }

            //步骤3.得到直接下级(包括下面所有级别孩子)为其贡献的总佣金
            $result3 = userDiffRebates::getChildContributeRebates($users[$user_id], $beginTime, $endTime);
            //krsort($result3);
            //合并结果
            foreach ($result as $k => $v) {
                if (isset($result3[$v['user_id']])) {
                    $result[$v['user_id']]['total_contribute_rebate'] = $result3[$v['user_id']]['total_contribute_rebate'];
                }
            }

            //步骤4.得到某用户团队(自己和所有下级)的总PT盈亏,投注;每一条记录都是自己的数据按日期范围汇总
            $tmp4 = reportPt::getSumGroupByUser($user_id, $beginTime, $endTime, 8);

            //自己PT数据
            if (isset($tmp4[$self['user_id']])) {
                $selfStatistic['pt_game_win'] = $tmp4[$self['user_id']]['pt_game_win'];
                $selfStatistic['pt_buy_amount'] = $tmp4[$self['user_id']]['pt_buy_amount'];
                $selfStatistic['pt_prize'] = $tmp4[$self['user_id']]['pt_prize'];
                unset($tmp4[$self['user_id']]);
            }

            foreach ($tmp4 as $k => $v) {
                if (isset($result[$k])) { //直接下级
                    $result[$k]['pt_game_win'] += $v['pt_game_win'];
                    $result[$k]['pt_buy_amount'] += $v['pt_buy_amount'];
                    $result[$k]['pt_prize'] += $v['pt_prize'];
                }
                else { //非直接下级
                    $parent_ids = explode(',', $v['parent_tree']);
                    $result[$parent_ids[$selfLevel + 1]]['pt_game_win'] += $v['pt_game_win'];
                    $result[$parent_ids[$selfLevel + 1]]['pt_buy_amount'] += $v['pt_buy_amount'];
                    $result[$parent_ids[$selfLevel + 1]]['pt_prize'] += $v['pt_prize'];
                }
            }

            //加上自己
            $result[$self['user_id']] = $selfStatistic;
            ksort($result);

            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 60 * 30); //放入缓存

            $dayRport[$day] = $result;
        }

        return $dayRport;
    }





    //后台盈亏报表核算专用 得到任意用户及其直属下级的总投注，总奖金，总返点，$parent_id为0表示所有总代
    //暂不支持$method_id，如果有需求，应改用projects表进行操作
    //注意追号时的create_time是当时投注时间，而非每期的销售期之内，所以精确查询一天的某段时间可能不准确，但一天之内肯定是准的，因为追号不能跨天
    //140609 为了防止凌晨2点后追号的情况，即check_prize_status应大于0，否则为0的话表示pending的追号单是要排除掉的
    static public function getChildSales($lottery_id = 0, $user_id = 0, $is_test = -1, $modes = 0, $start_time = '', $end_time = '',$isCached = true, $domain= '')
    {
       $cacheKey = (__FUNCTION__ . '_' . $lottery_id . '_' . $user_id . '_' . $is_test . '_' . date("YmdHis", strtotime($start_time)) . '_' . date("YmdHis", strtotime($end_time)).'_'.$domain);
        if ($isCached && ($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) !== false) {
           return $result;
        }



        //方便得到所有总代的
        if ($user_id == 0) {
            // $targetUsers = users::getUserTree(0, true, 0, 8, $is_test,$domain);
            $targetUsers = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'username', 'level', 'type', 'balance', 'parent_tree'],
                'parent_id' => 0,
                'status' => 8,
                'is_test' => $is_test,
                'domain' => $domain,
            ]);
            if (!$targetUsers) {
                return array();
            }

            //先初始化
            $result = array();
            foreach ($targetUsers as $k => $v) {
                $result[$v['user_id']]['user_id'] = $v['user_id'];
                $result[$v['user_id']]['username'] = $v['username'];
                $result[$v['user_id']]['level'] = $v['level'];
                $result[$v['user_id']]['is_test'] = $v['is_test'];
                $result[$v['user_id']]['parent_id'] = $v['parent_id'];
                $result[$v['user_id']]['total_amount'] = 0;
                $result[$v['user_id']]['total_prize'] = 0;
                $result[$v['user_id']]['total_rebate'] = 0;
                $result[$v['user_id']]['type'] = $v['type'];
                $result[$v['user_id']]['balance'] = $v['balance'];
            }

            //1.得到各总代团队的总投注，总奖金
            $sql = "SELECT p.top_id, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0";
            if ($is_test != -1) {
                $sql .= " AND u.is_test = $is_test";
            }
            if ($lottery_id != 0) {
                $sql .= " AND p.lottery_id = $lottery_id";
            }
            if ($modes != 0) {
                $sql .= " AND p.modes = '$modes'";
            }
            if ($start_time != '') {
                $sql .= " AND p.send_prize_time >= '$start_time'";
            }
            if ($end_time != '') {
                $sql .= " AND p.send_prize_time <= '$end_time'";
            }
            if ($domain != '') {
                $sql .= " AND u.username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like '%{$domain}%')";
            }

            $sql .= " AND p.check_prize_status > 0";
            $sql .= ' GROUP BY p.top_id ORDER BY p.top_id ASC';
            $tmp1 = $GLOBALS['db']->getAll($sql, array(),'top_id');


            foreach ($tmp1 as $k => $v) {
                //调试信息，可去掉
                if (!isset($result[$k])) {
                    logdump("不存在的top_id1:$k");
                    continue;
                }
                $result[$k]['total_amount'] += $v['total_amount'];
                $result[$k]['total_prize'] += $v['total_prize'];
            }


            //得到各总代团队的总返点
            $sql = "SELECT udr.top_id, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id=u.user_id WHERE u.status = 8 AND udr.status = 1";
            if ($is_test != -1) {
                $sql .= " AND u.is_test = $is_test";
            }
            if ($lottery_id != 0) {
                $sql .= " AND udr.lottery_id = $lottery_id";
            }
            if ($modes != 0) {
                $sql .= " AND udr.modes = '$modes'";
            }
            //by jerry 这里user_diff_rebates的create_time与packages的send_prize_time应该是同步的，
            //但是目前没有实现
            if ($start_time != '') {
                $sql .= " AND udr.create_time >= '$start_time'";
            }
            if ($end_time != '') {
                $sql .= " AND udr.create_time <= '$end_time'";
            }
            if ($domain != '') {
                $sql .= " AND u.username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like '%{$domain}%')";
            }
            $sql .= " GROUP BY udr.top_id";

            $tmp2 = $GLOBALS['db']->getAll($sql, array(),'top_id');


            foreach ($tmp2 as $k => $v) {
                //调试信息，可去掉
                if (!isset($result[$k])) {
                    logdump("不存在的top_id2:$k");
                    continue;
                }
                $result[$k]['total_rebate'] += isset($v['total_rebate']) ? $v['total_rebate'] : 0;

            }
        }
        else {  //如果是普通代理下的直接下级，只能一次性取出所有下级，再手动合并
            //先得到所有孩子
            //子
            // $users = users::getUserTree($user_id, true, 1, 8, $is_test,$domain);
            $users = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'username', 'level', 'type', 'balance', 'parent_tree'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
                'is_test' => $is_test,
                'domain' => $domain,
            ]);
            if (!$users) {
                return array();
            }

            $targetUsers = array();
            foreach ($users as $v) {
                if ($v['parent_id'] == $user_id) {
                    $targetUsers[$v['user_id']] = $v;
                }
            }

            //先初始化
            //子
            $result = array();
            foreach ($targetUsers as $k => $v) {
                $result[$v['user_id']]['user_id'] = $v['user_id'];
                $result[$v['user_id']]['username'] = $v['username'];
                $result[$v['user_id']]['level'] = $v['level'];
                $result[$v['user_id']]['is_test'] = $v['is_test'];
                $result[$v['user_id']]['parent_id'] = $v['parent_id'];
                $result[$v['user_id']]['total_amount'] = 0;
                $result[$v['user_id']]['total_prize'] = 0;
                $result[$v['user_id']]['total_rebate'] = 0;
                $result[$v['user_id']]['balance'] = $v['balance'];
            }

            //目标直指直接下级
            //子
            $self = $users[$user_id];
            $selfStatistic = array(
                'user_id' => $self['user_id'],
                'username' => $self['username'],
                'level' => $self['level'],
                'is_test' => $self['is_test'],
                'parent_id' => $self['parent_id'],
                'total_amount' => 0,
                'total_prize' => 0,
                'total_rebate' => 0,
                'balance' => $self['balance']
            );

            //得到自身所属层级，总代为0级，一代为1级，二代为2级，会员为3级
            //子
            if ($self['parent_id'] == 0) {
                $selfLevel = 0;
            }
            else {
                $selfLevel = count(explode(',', $self['parent_tree']));
            }

            //步骤1.得到自己和直接下级(包括下面所有级别孩子)的总投注，总奖金 需一次性取出再手动合并
            //子
            $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0';
            if ($lottery_id != 0) {
                $sql .= " AND p.lottery_id = $lottery_id";
            }
            if ($is_test != -1) {
                $sql .= " AND u.is_test = $is_test";
            }
            if ($modes != 0) {
                $sql .= " AND p.modes = '$modes'";
            }
            if ($start_time != '') {
                $sql .= " AND p.send_prize_time >= '$start_time'";
            }
            if ($end_time != '') {
                $sql .= " AND p.send_prize_time <= '$end_time'";
            }
            if ($domain != '') {
                $sql .= " AND u.username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like '%{$domain}%')";
            }
            $sql .= " AND p.check_prize_status > 0";
            $sql .= " AND " . $GLOBALS['db']->dbCreatIn(array_keys($users), 'p.user_id');
            $sql .= ' GROUP BY p.user_id ORDER BY p.user_id DESC';
            $tmp1 = $GLOBALS['db']->getAll($sql, array(),'user_id');


            //若自己也有记录，先分出来
            if (isset($tmp1[$self['user_id']])) {
                $selfStatistic['total_amount'] = $tmp1[$self['user_id']]['total_amount'];
                $selfStatistic['total_prize'] = $tmp1[$self['user_id']]['total_prize'];
                unset($tmp1[$self['user_id']]);
            }


            foreach ($tmp1 as $k => $v) {
                if (isset($result[$k])) { //直接下级
                    $result[$k]['total_amount'] += $v['total_amount'];
                    $result[$k]['total_prize'] += $v['total_prize'];
                }
                else { //非直接下级
                    $parent_ids = explode(',', $v['parent_tree']);
                    $result[$parent_ids[$selfLevel + 1]]['total_amount'] += $v['total_amount'];
                    $result[$parent_ids[$selfLevel + 1]]['total_prize'] += $v['total_prize'];
                }

            }

            //步骤2.得到某用户团队(自己和所有下级)的总返点
            //子
            $tmp2 = userDiffRebates::getTeamRebates($user_id, $is_test, $lottery_id, $modes, $start_time, $end_time,$domain);

            //若自己也有记录，先分出来
            if (isset($tmp2[$self['user_id']])) {
                $selfStatistic['total_rebate'] = $tmp2[$self['user_id']]['total_rebate'];
                unset($tmp2[$self['user_id']]);
            }


            foreach ($tmp2 as $k => $v) {
                if (isset($result[$k])) { //直接下级
                    $result[$k]['total_rebate'] += $v['total_rebate'];
                }
                else { //非直接下级
                    $parent_ids = explode(',', $v['parent_tree']);
                    $result[$parent_ids[$selfLevel + 1]]['total_rebate'] += $v['total_rebate'];
                }
            }


            //加上自己
            //子
            $result[$self['user_id']] = $selfStatistic;
            ksort($result);

        }
       $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 60 * 5); //放入缓存
        //子
        return $result;
    }

    /**
     * 根据彩种得到所有用户的投注奖金返点
     * @param type $start_time
     * @param type $end_time
     * @param type $lottery_id
     * @return type
     */
    static public function getAllSales($start_time = '', $end_time = '', $lottery_id = 0)
    {
        // 根据彩种获得所有用户的总投注和总奖金
        $sql1 = 'SELECT p.user_id, u.top_id, u.username, SUM(p.amount) AS buy_amount, SUM(p.prize) AS prize_amount FROM packages AS p LEFT JOIN users AS u ON p.user_id = u.user_id WHERE 1 ';
        if ($start_time != '') {
            $sql1 .= " AND p.send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql1 .= " AND p.send_prize_time <= '$end_time'";
        }
        if ($lottery_id != 0) {
            $sql1 .= " AND p.lottery_id = $lottery_id";
        }
        $sql1 .= ' AND p.cancel_status = 0';
        $sql1 .= ' AND p.check_prize_status > 0';
        $sql1 .= ' AND u.is_test = 0';
        $sql1 .= ' GROUP BY p.user_id ';

        $tmp1 = $GLOBALS['db']->getAll($sql1, array(),'user_id');
        $tmpUserIds1 = array_keys($tmp1);

        // 根据彩种获得用户的返点
        $sql2 = "SELECT udr.user_id, udr.top_id, u.username, SUM(udr.rebate_amount) AS rebate_amount FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id = u.user_id WHERE u.status = 8 AND udr.status = 1";
        //by ray 这里user_diff_rebates的create_time与packages的send_prize_time应该是同步的，
        //但是目前没有实现
        if ($start_time != '') {
            $sql2 .= " AND udr.create_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql2 .= " AND udr.create_time <= '$end_time'";
        }
        if ($lottery_id != 0) {
            $sql2 .= " AND udr.lottery_id = $lottery_id";
        }
        $sql2 .= " AND u.is_test = 0";
        $sql2 .= " GROUP BY udr.user_id";
        $tmp2 = $GLOBALS['db']->getAll($sql2, array(),'user_id');
        $tmpUserIds2 = array_keys($tmp2);

        // 获得所有的user_id
        $userIds = array_unique(array_merge($tmpUserIds1, $tmpUserIds2));
        $result = $amount = array();
        foreach ($userIds AS $user_id) {
            $result[$user_id]['top_id'] = isset($tmp1[$user_id]['top_id']) ? $tmp1[$user_id]['top_id'] : $tmp2[$user_id]['top_id'];
            $result[$user_id]['username'] = isset($tmp1[$user_id]['username']) ? $tmp1[$user_id]['username'] : $tmp2[$user_id]['username'];
            $result[$user_id]['buy_amount'] = isset($tmp1[$user_id]['buy_amount']) ? $tmp1[$user_id]['buy_amount'] : 0;
            $result[$user_id]['prize_amount'] = isset($tmp1[$user_id]['prize_amount']) ? $tmp1[$user_id]['prize_amount'] : 0;
            $result[$user_id]['rebate_amount'] = isset($tmp2[$user_id]['rebate_amount']) ? $tmp2[$user_id]['rebate_amount'] : 0;
            $result[$user_id]['win_amount'] = round($result[$user_id]['rebate_amount'] + $result[$user_id]['prize_amount'] - $result[$user_id]['buy_amount'], 4);
        }
        return $result;
    }

    //得到一批用户的最近一次购买时间，必须使用子查询
    static public function getRecentBuy($user_ids)
    {
        if (!is_array($user_ids)) {
            throw new exception2('参数无效');
        }
        if (!count($user_ids)) {
            return array();
        }

        $sql = "SELECT user_id, create_time FROM (SELECT p.user_id, p.create_time FROM packages p WHERE 1 AND user_id IN(" . implode(',', $user_ids) . ") ORDER BY create_time DESC) b WHERE 1";
        $sql .= " GROUP BY user_id ORDER BY create_time DESC";
        $result = $GLOBALS['db']->getAll($sql, array(),'user_id');
        foreach ($user_ids as $v) {
            if (!isset($result[$v])) {
                $result[$v] = array(
                    'user_id' => $v,
                    'create_time' => '0000-00-00 00:00:00',
                );
            }
        }

        return $result;
    }

    //得到未开始的订单，用于“空开撤单”
    static public function getPrePackages($issue)
    {
        $sql = "SELECT p.* FROM packages p LEFT JOIN traces t ON p.trace_id = t.trace_id WHERE t.start_issue > '$issue' AND t.stop_on_win = 2 GROUP BY p.trace_id ORDER BY p.package_id ASC";

        return $GLOBALS['db']->getAll($sql, array(),'package_id');
    }

    //获取某个总代下某个彩种或者全部彩种的所有投注量
    static public function getTeamSale($user_id, $start_time, $end_time, $lottery_id = 0, $perfix = __CLASS__)
    {
        $teamSale = array();
        if(!$teamSale = $GLOBALS['mc']->get($perfix, date('Y-m-d', strtotime($start_time)))){

            $sql = "SELECT SUM(p.amount) AS total_amount , p.top_id FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0 AND u.top_id = {$user_id}";

            if ($lottery_id != 0) {
                $sql .= " AND p.lottery_id = $lottery_id";
            }
            if ($start_time != '') {
                $sql .= " AND p.send_prize_time >= '$start_time'";
            }
            if ($end_time != '') {
                $sql .= " AND p.send_prize_time <= '$end_time'";
            }

            $sql .= " AND u.is_test = 0";
            $sql .= " AND p.check_prize_status > 0";
            $sql .= ' GROUP BY p.top_id ORDER BY p.top_id ASC';
            $teamSale = $GLOBALS['db']->getAll($sql, array(), 'top_id');
            $GLOBALS['mc']->set($perfix, date('Y-m-d', strtotime($start_time)), $teamSale, 86400);
        }

        return $teamSale;
    }
    //获取某个总代下某个彩种或者全部彩种的所有投注量
    static public function getTeamSaleC($user_id, $start_time, $end_time, $lottery_id = 0, $freeze = -1)
    {
        if($freeze == -1) {
            $sql = "SELECT SUM(p.amount) AS total_amount , p.top_id FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0 AND u.top_id = {$user_id}";
        }else{
            $sql = "SELECT SUM(p.amount) AS total_amount , p.top_id FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE (u.status = 8 and u.status = 1) AND p.cancel_status = 0 AND u.top_id = {$user_id}";

        }
        if ($lottery_id != 0) {
            $sql .= " AND p.lottery_id = $lottery_id";
        }
        if ($start_time != '') {
            $sql .= " AND p.send_prize_time >= '$start_time'";
        }
        if ($end_time != '') {
            $sql .= " AND p.send_prize_time <= '$end_time'";
        }

        $sql .= " AND u.is_test = 0";
        $sql .= " AND p.check_prize_status > 0";
        $sql .= ' GROUP BY p.top_id ORDER BY p.top_id ASC';
        $teamSale = $GLOBALS['db']->getAll($sql, array(), 'top_id');
        return $teamSale;
    }
    static public function addPackage($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }
        //logdump($data);
        return $GLOBALS['db']->insert('packages', $data);
    }

    static public function addPackages($datas)
    {
        $tmp = array();
        foreach ($datas as $v) {
            $tmp[] = "('{$v['user_id']}','{$v['top_id']}','{$v['lottery_id']}','{$v['issue']}','{$v['single_num']}'," .
                    "'{$v['multiple']}','{$v['cur_rebate']}','{$v['modes']}','{$v['amount']}','{$v['create_time']}','{$v['cancel_status']}','{$v['cancel_time']}','{$v['user_ip']}','{$v['proxy_ip']}','{$v['server_ip']}'," .
                    "'{$v['check_prize_status']}','{$v['send_prize_time']}','{$v['send_prize_status']}','{$v['prize']}' )";
        }
        $sql = "INSERT INTO packages (user_id,top_id,lottery_id,issue,single_num,multiple,cur_rebate,modes,amount,create_time,cancel_status,cancel_time,user_ip,proxy_ip,server_ip,check_prize_status,send_prize_time,send_prize_status,prize) VALUES " . implode(',', $tmp);

        return $GLOBALS['db']->query($sql, array(), 'i');
    }

    static public function updatePackage($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $tmp = array();
        foreach ($data as $k => $v) {
            $tmp[] = "`$k`='" . $v . "'";
        }
        $sql = "UPDATE packages SET " . implode(',', $tmp) . " WHERE package_id=$id LIMIT 1";
        if (!$GLOBALS['db']->query($sql, array(), 'u')) {
            return false;
        }

        return $GLOBALS['db']->affected_rows();
    }
}

?>
