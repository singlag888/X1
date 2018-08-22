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

        if( $betMoney > 0 ){
            $sql.= ' AND prize = ' . $betMoney;
        }

        if( $winMoney > 0 ){
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

        if( $betMoney > 0 ){
            $sql.= ' AND a.prize = ' . $betMoney;
        }

        if( $winMoney > 0 ){
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


    /**
     * @param int $lottery_id
     * @param int $check_prize_status
     * @param int $is_test
     * @param string $issue
     * @param int $trace_id
     * @param int $modes
     * @param string $user_id
     * @param int $include_childs
     * @param string $start_time
     * @param string $end_time
     * @param string $send_start_time
     * @param string $send_end_time
     * @param int $cancel_status
     * @param string $order_by
     * @param int $start
     * @param int $amount
     * @param int $is_award
     * @param int $betMoney
     * @param int $winMoney
     * @return array
     */
    static public function getPackagesAdmin($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '', $cancel_status = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE, $is_award = -1,$betMoney=0,$winMoney=0)
    {
        /***************************** snow 修改sql  一次性取出所有  的 投注内容***************************************/
$sql =<<<SQL
SELECT a.*,b.username,b.status AS user_status FROM packages AS a LEFT JOIN  users AS b ON a.user_id = b.user_id
WHERE 1
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
                self::$sqlConds .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
            }
        }

        if ($start_time != '') {
            self::$sqlConds .= ' AND a.create_time >= \'' . $start_time . '\'';
        }
        if ($end_time != '') {
            self::$sqlConds .= ' AND a.create_time <= \'' . $end_time . '\'';
        }
        if ($send_start_time != '') {
            self::$sqlConds .= ' AND a.send_prize_time >= \'' . $send_start_time . '\'';
        }
        if ($send_end_time != '') {
            self::$sqlConds .= ' AND a.send_prize_time <= \'' . $send_end_time . '\'';
        }
        if ($cancel_status != -1) {
            if ($cancel_status == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$sqlConds .= ' AND a.cancel_status <> 0';
            } else {
                self::$sqlConds .= ' AND a.cancel_status = \'' . $cancel_status . '\'';
            }
        }

        if ($betMoney > 0) {
            self::$sqlConds .= ' AND a.amount >= ' . $betMoney;
        }

        if ($winMoney > 0) {
            self::$sqlConds .= ' AND a.prize >= ' . $winMoney;
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($order_by) {

            $sql .= self::$sqlConds . ' ORDER BY ' . $order_by;
        } else {
            $sql .= self::$sqlConds . ' ORDER BY a.package_id DESC';
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($start > -1) {
            $sql .= ' LIMIT ' . $start . ', ' . $amount;
        }

        $result = $GLOBALS['db']->getAll($sql , array(),'package_id');
        $tmpData = [];
        if(is_array($result) && !empty($result)){
            foreach ($result as $key => $val){
                $tmpData[] = '\'' . $val['package_id'] . '\'';
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



    /****************** snow 排除dcsite 数据 start************************************/

    /**
     * author snow
     * @param $options
     * @return array
     */
    private static $packageSqlExc = '';//>>保存sql条件
    static public function getPackagesAdminExclude($options)
    {

        $default_options = [
            'orderBy'                   => '',  //>>排序
            'start'                     => -1,  //>>limit 的开始记录数
            'amount'                    => DEFAULT_PER_PAGE,//>>默认每页显示条数
        ];


        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        /***************************** snow 修改sql  一次性取出所有  的 投注内容***************************************/
        $sql =<<<SQL
SELECT a.*,b.username,b.status AS user_status FROM packages AS a LEFT JOIN  users AS b ON a.user_id = b.user_id
WHERE 1
SQL;

//        //>> snow 排除dcsite  现在又不需要了
//        $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
        $sql .= self::$packageSqlExc;
        //TODO 这里要注意，条件要忽略limit order by 等
        if ($default_options['orderBy'] != '') {

            $sql .= " ORDER BY {$default_options['orderBy']}";
        } else {
            $sql .= " ORDER BY a.package_id DESC";
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($default_options['start'] > -1) {
            $sql .= " LIMIT {$default_options['start']}, {$default_options['amount']}";
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

    /**
     * author snow 获取总投注,总中奖,总盈亏
     * @param $start
     * @param $end
     */
    public static  function getItemsExclude($start, $end)
    {
        //>>获取开始时间到结束时间的总数据
        $ids = users::getUsersSqlByUserName();
        $sql =<<<SQL
SELECT  LEFT(create_time,10) AS belong_date,SUM(p.amount) AS sum_amount, SUM(p.prize) AS sum_prize, (SUM(p.amount)- SUM(p.prize)) AS sum_win
FROM packages AS p 
LEFT JOIN users AS u ON p.user_id = u.user_id 
WHERE 1
AND p.create_time >= '{$start}' AND  p.create_time <= '{$end}'
AND p.cancel_status = 0
AND p.check_prize_status > 0
AND u.is_test = 0
AND u.user_id NOT IN {$ids}
GROUP BY belong_date
SQL;
        return $GLOBALS['db']->getAll($sql, [], 'belong_date');


    }

    //>>根据彩种来获取总投注 ,总中奖,总盈亏
    public static function getItemsForLotteryExclude($start, $end)
    {
        //>>获取开始时间到结束时间的总数据
        $sql =<<<SQL
SELECT p.lottery_id,l.cname as lottery_name, SUM(p.amount) AS sum_amount, SUM(p.prize) AS sum_prize, (SUM(p.amount)- SUM(p.prize)) AS sum_win
FROM packages AS p 
LEFT JOIN users AS u ON p.user_id = u.user_id 
LEFT JOIN lottery AS l ON p.lottery_id = l.lottery_id 
WHERE 1
AND p.create_time >= '{$start}' AND  p.create_time <= '{$end}'
AND p.cancel_status = 0
AND p.check_prize_status > 0
AND u.is_test = 0
AND u.user_id NOT IN (SELECT user_id FROM users WHERE top_id = (SELECT user_id FROM users WHERE username = 'dcsite'))
GROUP BY lottery_id,lottery_name
SQL;
        return $GLOBALS['db']->getAll($sql, [], 'lottery_id');
    }
    /****************** snow 排除dcsite 数据 start************************************/
    /**
     * @param int $start
     * @param int $amount
     * @param null $where
     * @return mixed
     */
    //>>获取分批次导出的数据
    static public function snow_getPackagesAdmin($start = -1, $amount = 10000, $where = null)
    {

$sql =<<<SQL
SELECT a.*,b.username,b.status AS user_status FROM packages AS a LEFT JOIN users AS b ON a.user_id = b.user_id
WHERE 1
SQL;
        //>>如果传入了条件 ,直接使用条件  否则判断有没有 静态变量条件

        //>>排除dcsite
        $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
        if(!is_null($where)){
            $sql .= $where;
        }else{
            if(!empty(self::$sqlConds)){
                $sql .= self::$sqlConds;
            }
        }


        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }

        return $GLOBALS['db']->getAll($sql , array());


    }

    /**
     * 导出数据 ,先计算总数据
     * @return mixed
     */
    static public function snow_getNewPackagesNumber($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '', $cancel_status = -1, $order_by = '', $betMoney=0,$winMoney=0){
        //增加全局统计
        $sql = 'SELECT COUNT(*) AS count,SUM(a.prize) as prizes,SUM(a.amount) as amounts FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';


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
                self::$sqlConds .= " AND b.top_id != (SELECT user_id FROM users where username = 'dcsite')";
            }
        } else {
            //>>排除dcsite
            self::$sqlConds .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName();
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


        $sql .= self::$sqlConds;
        $result =  $GLOBALS['db']->getRow($sql);
        $result['where'] = self::$sqlConds;
        return $result;

    }

    static public function getPackages($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '', $cancel_status = -1, $order_by = '', $start = -1, $amount = DEFAULT_PER_PAGE, $is_award = -1,$betMoney=0,$winMoney=0,$freeze = -1)
    {
        $sql = 'SELECT a.*,b.username,b.status AS user_status FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
        self::$sqlConds = '';
        if(is_array($lottery_id) && !empty($lottery_id)) {
            self::$sqlConds .= ' AND a.lottery_id IN ( \'' . implode('\',\'', $lottery_id) . '\')';
        }
        elseif ($lottery_id != 0) {
            self::$sqlConds .= ' AND a.lottery_id = ' . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            self::$sqlConds .= ' AND a.check_prize_status = ' . intval($check_prize_status);
        }
        if ($is_test != -1) {
            self::$sqlConds .= ' AND b.is_test = ' . intval($is_test);
        }

        if (!empty($issue) && is_array($issue)) {
            self::$sqlConds .= ' AND a.issue IN (\'' . implode('\',\'', $issue) . '\')';
        }
        elseif ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            self::$sqlConds .= " AND a.issue = '$issue'";
        }
        if ($trace_id != -1) {
            if ($trace_id == -255) {
                self::$sqlConds .= ' AND a.trace_id > 0';
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
                self::$sqlConds .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
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
                self::$sqlConds .= ' AND a.cancel_status <> 0';
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
            $sql .= self::$sqlConds.' ORDER BY package_id DESC';
        }

        //TODO 这里要注意，条件要忽略limit order by 等
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql , array(),'package_id');

        return $result;
    }

    /****************** snow 排除dcsite 数据 start************************************/
    /**
     * author snow
     * @param $options
     * @return mixed
     */
    static public function getNewPackagesNumber($options){
        //增加全局统计
        $default_options = [
            'lottery_id'                => 0,   //>>彩种id
            'check_prize_status'        => -1,  //>>中奖判断状态
            'is_test'                   => -1,  //>>默认不显示测试账号
            'issue'                     => '',  //>>奖期
            'trace_id'                  => -1,  //>>追号id
            'modes'                     => 0,   //>>元角模式
            'user_id'                   => '',  //>>用户id
            'include_childs'            => 0,   //>>是否包含下级
            'start_date'                => '',  //>>起始时间
            'end_date'                  => '',  //>>结束时间
            'send_start_date'           => '',  //>>派奖起始时间
            'send_end_date'             => '',  //>>派奖结束时间
            'cancel_status'             => -1,  //>>是否撤单//>>撤单状态
            'is_award'                  => -1,  //>>这个参数暂时没有发现用处
            'betMoney'                  => 0,   //>>投注金额
            'winMoney'                  => 0,   //>>中奖金额
        ];

        //>>合并传入的参数
        $default_options = is_array($options) ? array_merge($default_options, $options) : $default_options;
        $sql = 'SELECT COUNT(*) AS count,SUM(a.prize) as prizes,SUM(a.amount) as amounts FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1 ';

        self::$packageSqlExc = '';
        if(is_array($default_options['lottery_id']) && !empty($default_options['lottery_id'])) {
            self::$packageSqlExc .= " AND a.lottery_id IN ( '" . implode("','", $default_options['lottery_id']) . "')";
        }
        elseif ($default_options['lottery_id'] != 0) {
            self::$packageSqlExc .= " AND a.lottery_id = " . intval($default_options['lottery_id']);
        }
        if ($default_options['check_prize_status'] != -1) {
            self::$packageSqlExc .= " AND a.check_prize_status = " . intval($default_options['check_prize_status']);
        }

        if (!empty($default_options['issue']) && is_array($default_options['issue'])) {
            self::$packageSqlExc .= " AND a.issue IN ('" . implode("','", $default_options['issue']) . "')";
        }
        elseif ($default_options['issue'] != '' && preg_match('`^[\d-]+$`', $default_options['issue'])) {
            self::$packageSqlExc .= " AND a.issue = '{$default_options['issue']}'";
        }
        if ($default_options['trace_id'] != -1) {
            if ($default_options['trace_id'] == -255) {
                self::$packageSqlExc .= " AND a.trace_id > 0";
            }
            else {
                self::$packageSqlExc .= " AND a.trace_id = '{$default_options['trace_id']}'";
            }
        }
        if ($default_options['modes'] != 0) {
            self::$packageSqlExc .= " AND a.modes = '{$default_options['modes']}'";
        }
        if ($default_options['user_id'] != '') {

            //>>snow 添加  ,如果传入的用户不为空 ,把istest 置为-1 查询全部相关数据
//            $default_options['is_test'] = -1;
            $tmp = preg_match('`^\d+$`Ui', $default_options['user_id']);
            if (!$default_options['include_childs']) {
                if ($tmp) {
                    self::$packageSqlExc .= " AND b.user_id = '{$default_options['user_id']}'";
                }
                else {
                    self::$packageSqlExc .= " AND b.username = '{$default_options['user_id']}'";
                }
            }
            else {
                if (!$tmp) {
                    //如果不是数字id,先得到用户id
                    //151201 by william 冻结用户也可以看到其游戏记录，不应该被屏蔽
                    if (!$user = users::getItem($default_options['user_id'], -1)) {
                        return array();
                    }
                    $default_options['user_id'] = $user['user_id'];
                }
                // $teamUsers = users::getUserTree($default_options['user_id'], true, 1, -1);
                $teamUsers = users::getUserTreeField([
                    'field' => ['user_id'],
                    'parent_id' => $default_options['user_id'],
                    'recursive' => 1,
                ]);
                self::$packageSqlExc .= " AND  b.top_id != (SELECT user_id FROM users WHERE username = 'dcsite')" ;
            }
        } else {
            //>>没有输入用户名的时候
            //>>排除dcsite
            self::$packageSqlExc .= ' AND b.top_id != (SELECT user_id FROM users WHERE username = \'dcsite\')';
        }

        if ($default_options['is_test'] != -1) {
            self::$packageSqlExc .= " AND b.is_test = " . intval($default_options['is_test']);
        }

        if ($default_options['start_date'] != '') {
            self::$packageSqlExc .= " AND a.create_time >= '{$default_options['start_date']}'";
        }
        if ($default_options['end_date'] != '') {
            self::$packageSqlExc .= " AND a.create_time <= '{$default_options['end_date']}'";
        }
        if ($default_options['send_start_date'] != '') {
            self::$packageSqlExc .= " AND a.send_prize_time >= '{$default_options['send_start_date']}'";
        }
        if ($default_options['send_end_date'] != '') {
            self::$packageSqlExc .= " AND a.send_prize_time <= '{$default_options['send_end_date']}'";
        }
        if ($default_options['cancel_status'] != -1) {
            if ($default_options['cancel_status'] == self::CANCELPACKAGES) {//代表针对所有撤单情况
                self::$packageSqlExc .= " AND a.cancel_status <> 0";
            }
            else {
                self::$packageSqlExc .= " AND a.cancel_status = '{$default_options['cancel_status']}'";
            }
        }

        if( $default_options['betMoney'] > 0 ){
            self::$packageSqlExc .= " AND a.amount >= {$default_options['betMoney']}";
        }

        if( $default_options['winMoney'] > 0 ){
            self::$packageSqlExc .= " AND a.prize >= {$default_options['winMoney']}";
        }
        $sql .= self::$packageSqlExc;
        return $GLOBALS['db']->getRow($sql);

    }

    /****************** snow 排除dcsite 数据 start************************************/

    static public function getPackageTotal($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $send_start_time = '', $send_end_time = '', $cancel_status = -1,$freeze = -1)
    {
        $sql = 'SELECT count(*) as total_count, SUM(a.prize) as total_prize, sum(a.amount) as total_amount, sum(a.prize-if(a.cancel_status=0 and a.check_prize_status>0, a.amount, 0)) as total_profit FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';
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
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
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
                    if($freeze == -1) {
                        //如果不是数字id,先得到用户id
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
/********************************* snow 复制代码用来排除dcsite 总代数据 start***********************************************************/

    /**
     * author snow
     * 获取总订单数
     * @param int $lottery_id
     * @param int $check_prize_status
     * @param int $is_test
     * @param string $issue
     * @param int $trace_id
     * @param int $modes
     * @param string $user_id
     * @param int $include_childs
     * @param string $start_time
     * @param string $end_time
     * @param int $cancel_status
     * @param int $betMoney
     * @param int $winMoney
     * @param int $freeze
     * @return mixed
     */
    static public function getPackagesNumberExclude($lottery_id = 0, $check_prize_status = -1, $is_test = -1, $issue = '', $trace_id = -1, $modes = 0, $user_id = '', $include_childs = 0, $start_time = '', $end_time = '', $cancel_status = -1,$betMoney=0,$winMoney=0,$freeze = -1)
    {
        $sql = 'SELECT COUNT(*) AS count FROM packages a LEFT JOIN users b ON a.user_id=b.user_id WHERE 1';

        //>>添加排除总代dcsite 相关数据
        $sql .= ' AND a.user_id NOT IN ' . users::getUsersSqlByUserName('dcsite');
        if(is_array($lottery_id) && !empty($lottery_id)) {
            $sql .= ' AND a.lottery_id IN ( \'' . implode('\',\'', $lottery_id) . '\')';
        }
        elseif ($lottery_id != 0) {
            $sql .= ' AND a.lottery_id = ' . intval($lottery_id);
        }
        if ($check_prize_status != -1) {
            $sql .= ' AND a.check_prize_status = ' . intval($check_prize_status);
        }
        if ($is_test != -1) {
            $sql .= " AND b.is_test = " . intval($is_test);
        }
        if ($issue != '' && preg_match('`^[\d-]+$`', $issue)) {
            $sql .= " AND a.issue = '$issue'";
        }
        if ($trace_id != -1) {
            if ($trace_id == -255) {
                $sql .= ' AND a.trace_id > 0';
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
                    if($freeze == -1) {
                        //如果不是数字id,先得到用户id
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
                $sql .= ' AND ' . $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), 'a.user_id');
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
                $sql .= ' AND a.cancel_status <> 0';
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

/********************************* snow 复制代码用来排除dcsite 总代数据 end  ***********************************************************/
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

    /******************************** snow  复制代码 ,用来排除dcsite**********************************************/
    //必须选择一个采种以进行查询
    static public function getIssueSalesExclude($lottery_id, $is_test = -1, $modes = 0, $start_time = '', $end_time = '')
    {
        $sql = "SELECT p.issue, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE p.cancel_status = 0";
        //>>添加条件 ,排除dcsite 总代数据
        $sql .= ' AND p.user_id NOT IN ' . users::getUsersSqlByUserName();

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


    /******************************** snow  复制代码 ,用来排除dcsite**********************************************/
    //前后台会员报表用 得到任意用户及其直属下级的总投注，总奖金，总返点，下级贡献佣金
    static public function getChildReport($user_id, $start_time = '', $end_time = '',$freeze = -1)
    {
        /************************ snow 2017/10/31 增加缓存时长 ,当天不缓存 start******************************/

        $cacheKey = (__FUNCTION__ . '_' . $user_id . date("YmdHis", strtotime($start_time)) . '_' . date("YmdHis", strtotime($end_time)));

        $result = $GLOBALS['mc']->get(__CLASS__, $cacheKey);
        if ($result !== false) {
            //logdump("getChildReport() user_id={$user_id} CACHE OK");
            return $result;
        }

        if ($freeze == -1) {
            //先得到所有孩子 140609 取所有孩子
            // $users = users::getUserTree($user_id, true, 1, 8, -1);
            $users = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'username', 'level', 'is_test', 'type', 'parent_tree'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8
            ]);
            if (!$users) {
//                 $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), 60 * 30); //放入缓存
                return [];
            }
        } else {
            //先得到所有孩子 140609 取所有孩子
            // $users = users::getUserTree($user_id, true, 1, 8, -1, '', 1);
            $users = users::getUserTreeField([
                'field' => ['user_id', 'parent_id', 'username', 'level', 'is_test', 'type', 'parent_tree'],
                'parent_id' => $user_id,
                'recursive' => 1,
                'status' => 8,
                'freeze' => 1
            ]);
             if (!$users) {
//                 $GLOBALS['mc']->set(__CLASS__, $cacheKey, array(), 60 * 30); //放入缓存
                 return [];
             }
         }

        $targetUsers = [];
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
            $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize  FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE (u.status = 8 or u.status = 1) AND p.cancel_status = 0';

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
        if($freeze == -1) {
            $tmp2 = userDiffRebates::getTeamRebates($user_id, 0, 0, 0, $start_time, $end_time);
        }else{
            $tmp2 = userDiffRebates::getTeamRebates($user_id, 0, 0, 0, $start_time, $end_time,'',1);
        }

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
        foreach ($result as $k => $v)
        {
            if (isset($result3[$v['user_id']]))
            {
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


        //>>定义是否要写缓存  如果结束时间不等于当天 ,且没有缓存的情况下写入缓存.如果查询结束时间为当天 ,则不缓存
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
                'field' => ['user_id', 'parent_id', 'username', 'level', 'is_test', 'type', 'parent_tree'],
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
    static public function getChildSales($lottery_id = 0, $user_id = 0, $is_test = -1, $modes = 0, $start_time = '', $end_time = '',$isCached = true, $domain= '',$freeze = -1)
    {
//       $cacheKey = (__FUNCTION__ . '_' . $lottery_id . '_' . $user_id . '_' . $is_test . '_' . date("YmdHis", strtotime($start_time)) . '_' . date("YmdHis", strtotime($end_time)).'_'.$domain);
//        if ($isCached && ($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) !== false) {
//           return $result;
//        }

        //方便得到所有总代的
        if ($user_id == 0) {
            if ($freeze == -1) {
                // $targetUsers = users::getUserTree(0, true, 0, 8, $is_test, $domain);
                $targetUsers = users::getUserTreeField([
                    'parent_id' => 0,
                    'status' => 8,
                    'is_test' => $is_test,
                    'domain' => $domain,
                ]);
                if (!$targetUsers) {
                    return array();
                }
            } else {
                // $targetUsers = users::getUserTree(0, true, 0, 8, $is_test, $domain, 1);
                $targetUsers = users::getUserTreeField([
                    'parent_id' => 0,
                    'status' => 8,
                    'is_test' => $is_test,
                    'domain' => $domain,
                    'freeze' => 1
                ]);
                if (!$targetUsers) {
                    return array();
                }
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
                $result[$v['user_id']]['deposit_totalAmount'] = 0;
            }

            //1.得到各总代团队的总投注，总奖金
            if($freeze == -1) {
                $sql = "SELECT p.top_id, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0";
            }else{
                $sql = "SELECT p.top_id, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE (u.status = 8 or u.status = 0) AND p.cancel_status = 0";

            }
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

            $sql .= ' AND p.check_prize_status > 0';
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
            if($freeze == -1) {
                $sql = 'SELECT udr.top_id, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id=u.user_id WHERE u.status = 8 AND udr.status = 1';
            }else{
                $sql = 'SELECT udr.top_id, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id=u.user_id WHERE (u.status = 8 or u.status = 1) AND udr.status = 1';

            }
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
            $sql .= ' GROUP BY udr.top_id';

            $tmp2 = $GLOBALS['db']->getAll($sql, array(),'top_id');


            /******************** snow 获取各总代团队的存款量 start *****************************************************/
            $tmpDepostis = self::_getTopTeamDepositSum( $is_test,  $start_time, $end_time, $domain, $freeze);

            if (is_array($tmpDepostis) && !empty($tmpDepostis))
            {
                foreach ($tmpDepostis as $key => $val)
                {
                    if (!isset($result[$key])) {
                        logdump("不存在的top_id2:$key");
                        continue;
                    }
                    $result[$key]['deposit_totalAmount'] = isset($val['deposit_totalAmount']) ? $val['deposit_totalAmount'] : 0;
                }
            }
            unset($tmpDepostis);
            /******************** snow 获取各总代团队的存款量 end   *****************************************************/
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
            if($freeze == -1) {
                // $users = users::getUserTree($user_id, true, 1, 8, $is_test, $domain);
                $users = users::getUserTreeField([
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8,
                    'is_test' => $is_test,
                    'domain' => $domain,
                ]);
                if (!$users) {
                    return array();
                }
            }else{
                // $users = users::getUserTree($user_id, true, 1, 8, $is_test, $domain,1);
                $users = users::getUserTreeField([
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8,
                    'is_test' => $is_test,
                    'domain' => $domain,
                    'freeze' => 1
                ]);
                if (!$users) {
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
                $result[$v['user_id']]['deposit_totalAmount'] = 0;
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
                'balance' => $self['balance'],
                'deposit_totalAmount' => 0,
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
            if($freeze == -1) {
                $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0';
            }else{
                $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE (u.status = 8 or u.status = 1) AND p.cancel_status = 0';

            }
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

            unset($tmp1);
            //步骤2.得到某用户团队(自己和所有下级)的总返点
            //子
            $tmp2 = userDiffRebates::getTeamRebates($user_id, $is_test, $lottery_id, $modes, $start_time, $end_time,$domain,1);

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
            unset($tmp2);
            /**************************** snow  获取当前用户及用户的直接下级 start*****************************************/
            //>>获取当前用户及用户的直接下级
//            $tmpUsers = self::_getSubordinateUsers($user_id);
            //>>获取当前用户的团队存款量
            //>>调用方法获取每个用户所有下级用户包括自己的存款量
            $tmpDe = self::_getTeamDepositSum($user_id, $is_test,  $start_time, $end_time, $domain, $freeze);
            //若自己也有记录，先分出来
            if (isset($tmpDe[$self['user_id']])) {
                $selfStatistic['deposit_totalAmount'] = $tmpDe[$self['user_id']]['deposit_totalAmount'];
                unset($tmpDe[$self['user_id']]);
            }


            foreach ($tmpDe as $k => $v) {
                if (isset($result[$k])) { //直接下级
                    $result[$k]['deposit_totalAmount'] += isset($v['deposit_totalAmount']) ? $v['deposit_totalAmount'] : 0;
                }
                else { //非直接下级
                    $parent_ids = explode(',', $v['parent_tree']);
                    $result[$parent_ids[$selfLevel + 1]]['deposit_totalAmount'] += isset($v['deposit_totalAmount']) ? $v['deposit_totalAmount'] : 0;
                }
            }
            unset($tmpDe);
            /**************************** snow  获取当前用户及用户的直接下级 end  *****************************************/
            //加上自己
            //子
            $result[$self['user_id']] = $selfStatistic;
            ksort($result);

        }
//       $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 60 * 5); //放入缓存
        //子
        return $result;
    }


    /**************************** 复制代码 ,用来过滤掉dcsite 总代*************************************/

    //后台盈亏报表核算专用 得到任意用户及其直属下级的总投注，总奖金，总返点，$parent_id为0表示所有总代
    //暂不支持$method_id，如果有需求，应改用projects表进行操作
    //注意追号时的create_time是当时投注时间，而非每期的销售期之内，所以精确查询一天的某段时间可能不准确，但一天之内肯定是准的，因为追号不能跨天
    //140609 为了防止凌晨2点后追号的情况，即check_prize_status应大于0，否则为0的话表示pending的追号单是要排除掉的
    /**
     * @param int $lottery_id
     * @param int $user_id
     * @param int $is_test
     * @param int $modes
     * @param string $start_time
     * @param string $end_time
     * @param bool $isCached
     * @param string $domain
     * @param int $freeze
     * @return array
     */
    static public function getChildSalesExclude($lottery_id = 0, $user_id = 0, $is_test = -1, $modes = 0, $start_time = '', $end_time = '',$isCached = true, $domain= '',$freeze = -1)
    {
//       $cacheKey = (__FUNCTION__ . '_' . $lottery_id . '_' . $user_id . '_' . $is_test . '_' . date("YmdHis", strtotime($start_time)) . '_' . date("YmdHis", strtotime($end_time)).'_'.$domain);
//        if ($isCached && ($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) !== false) {
//           return $result;
//        }

        //方便得到所有总代的
        if ($user_id == 0) {
            if ($freeze == -1) {
                $targetUsers = users::getUserTreeExclude(0, true, 0, 8, $is_test, $domain);
                if (!$targetUsers) {
                    return array();
                }
            } else {
                $targetUsers = users::getUserTreeExclude(0, true, 0, 8, $is_test, $domain, 1);
                if (!$targetUsers) {
                    return array();
                }
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
                $result[$v['user_id']]['deposit_totalAmount'] = 0;
            }

            //1.得到各总代团队的总投注，总奖金
            if ($freeze == -1) {
                $sql = "SELECT p.top_id, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0";
            } else {
                $sql = "SELECT p.top_id, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE (u.status = 8 or u.status = 0) AND p.cancel_status = 0";

            }

            //>>添加条件 ,排除dcsite 总代数据
            $sql .= ' AND p.user_id NOT IN ' . users::getUsersSqlByUserName();
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
            if($freeze == -1) {
                $sql = "SELECT udr.top_id, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id=u.user_id WHERE u.status = 8 AND udr.status = 1";
            }else{
                $sql = "SELECT udr.top_id, SUM(udr.rebate_amount) AS total_rebate FROM user_diff_rebates udr LEFT JOIN users u ON udr.user_id=u.user_id WHERE (u.status = 8 or u.status = 1) AND udr.status = 1";

            }

            //>>添加条件 ,排除dcsite 总代数据
            $sql .= ' AND udr.user_id NOT IN ' . users::getUsersSqlByUserName();
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


            /******************** snow 获取各总代团队的存款量 start *****************************************************/
            $tmpDepostis = self::_getTopTeamDepositSum( $is_test,  $start_time, $end_time, $domain, $freeze);

            if (is_array($tmpDepostis) && !empty($tmpDepostis))
            {
                foreach ($tmpDepostis as $key => $val)
                {
                    if (!isset($result[$key])) {
                        logdump("不存在的top_id2:$key");
                        continue;
                    }
                    $result[$key]['deposit_totalAmount'] = isset($val['deposit_totalAmount']) ? $val['deposit_totalAmount'] : 0;
                }
            }
            unset($tmpDepostis);
            /******************** snow 获取各总代团队的存款量 end   *****************************************************/
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
            if($freeze == -1) {
                // $users = users::getUserTree($user_id, true, 1, 8, $is_test, $domain);
                $users = users::getUserTreeField([
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8,
                    'is_test' => $is_test,
                    'domain' => $domain,
                ]);
                if (!$users) {
                    return array();
                }
            }else{
                // $users = users::getUserTree($user_id, true, 1, 8, $is_test, $domain,1);
                $users = users::getUserTreeField([
                    'parent_id' => $user_id,
                    'recursive' => 1,
                    'status' => 8,
                    'is_test' => $is_test,
                    'domain' => $domain,
                    'freeze' => 1
                ]);
                if (!$users) {
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
                $result[$v['user_id']]['deposit_totalAmount'] = 0;

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
                'balance' => $self['balance'],
                'deposit_totalAmount' => 0,
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
            if($freeze == -1) {
                $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE u.status = 8 AND p.cancel_status = 0';
            }else{
                $sql = 'SELECT p.user_id,u.parent_id, u.parent_tree, SUM(p.amount) AS total_amount, SUM(p.prize) AS total_prize FROM packages p LEFT JOIN users u ON p.user_id=u.user_id WHERE (u.status = 8 or u.status = 1) AND p.cancel_status = 0';

            }
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

            unset($tmp1);
            //步骤2.得到某用户团队(自己和所有下级)的总返点
            //子
            $tmp2 = userDiffRebates::getTeamRebates($user_id, $is_test, $lottery_id, $modes, $start_time, $end_time,$domain,1);

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
            unset($tmp2);
            /**************************** snow  获取当前用户及用户的直接下级 start*****************************************/
            //>>获取当前用户及用户的直接下级
//            $tmpUsers = self::_getSubordinateUsers($user_id);
            //>>获取当前用户的团队存款量
            //>>调用方法获取每个用户所有下级用户包括自己的存款量
            $tmpDe = self::_getTeamDepositSum($user_id, $is_test,  $start_time, $end_time, $domain, $freeze);
            //若自己也有记录，先分出来
            if (isset($tmpDe[$self['user_id']])) {
                $selfStatistic['deposit_totalAmount'] = $tmpDe[$self['user_id']]['deposit_totalAmount'];
                unset($tmpDe[$self['user_id']]);
            }


            foreach ($tmpDe as $k => $v) {
                if (isset($result[$k])) { //直接下级
                    $result[$k]['deposit_totalAmount'] += isset($v['deposit_totalAmount']) ? $v['deposit_totalAmount'] : 0;
                }
                else { //非直接下级
                    $parent_ids = explode(',', $v['parent_tree']);
                    $result[$parent_ids[$selfLevel + 1]]['deposit_totalAmount'] += isset($v['deposit_totalAmount']) ? $v['deposit_totalAmount'] : 0;
                }
            }
            unset($tmpDe);
            /**************************** snow  获取当前用户及用户的直接下级 end  *****************************************/
            //加上自己
            //子
            $result[$self['user_id']] = $selfStatistic;
            ksort($result);

        }
//       $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 60 * 5); //放入缓存
        //子
        return $result;
    }

    /**************************** 复制代码 ,用来过滤掉dcsite 总代*************************************/
    /**'
     * author snow
     * 根据传入用户id 获取自己以及自己直接下级的用户id
     * @param int $user_id
     * @return mixed
     */
    private static function _getSubordinateUsers($user_id = 0)
    {
        $sql =<<<SQL
SELECT user_id FROM users WHERE parent_id = {$user_id} OR user_id = {$user_id}
SQL;
        return $GLOBALS['db']->getAll($sql);

    }
    /************************* snow 获取各总代团队存款量 start***********************************************************/
    /**
     * author  snow
     * 获取各总代团队存款量
     * @param int $is_test int      是否测试
     * @param string $start_time    开始时间
     * @param string $end_time      结束时间
     * @param string $domain        域名
     * @param int $freeze int       是否统计冻结用户
     * @return array
     */
    private static function _getTopTeamDepositSum($is_test = -1,  $start_time = '', $end_time = '', $domain= '',$freeze = -1)
    {

        //>>1. 生成是否统计冻结账号的条件
        $userStatus = $freeze == -1 ? ' (u.status = 8 OR u.status = 1)' : ' (u.status = 8)';
        $sql =<<<SQL
SELECT d.top_id,SUM(d.amount) AS deposit_totalAmount FROM deposits AS d
LEFT JOIN users AS u ON d.`user_id` = u.`user_id`
WHERE {$userStatus} 
AND d.`status` = 8
SQL;
        //>>添加条件 ,排除dcsite 总代数据
        $sql .= ' AND d.user_id NOT IN ' . users::getUsersSqlByUserName();

        //>>是否统计测试账号
        if ($is_test != -1) {
            $sql .= " AND u.is_test = $is_test";
        }

        //>>统计时间 起始时间
        if ($start_time != '') {
            $sql .= " AND d.`finish_time` >= '$start_time'";
        }

        //>>统计时间 结束时间
        if ($end_time != '') {
            $sql .= " AND d.`finish_time` <= '$end_time'";
        }

        //>>之前的,不知道什么用,先留着
        if ($domain != '') {
            $sql .= " AND u.username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like '%{$domain}%')";
        }
        $sql .= " GROUP BY d.top_id";
        return $GLOBALS['db']->getAll($sql, array(),'top_id');
    }


    /************************* snow 获取各总代团队存款量 end ************************************************************/

    /************************* snow 获取各团队存款量 start ************************************************************/
    /**
     * author  snow
     * 获取各总代团队存款量
     * @param int $user_id int      用户编号
     * @param string $start_time    开始时间
     * @param string $start_time    开始时间
     * @param string $end_time      结束时间
     * @param string $domain        域名
     * @param int $freeze int       是否统计冻结用户
     * @return array
     */
    private static function _getTeamDepositSum($user_id = 0,$is_test = -1,  $start_time = '', $end_time = '', $domain= '',$freeze = -1)
    {
        //>>获取各团队存款量
        //>>1. 生成是否统计冻结账号的条件
        $userStatus = $freeze == -1 ? ' (u.status = 8 OR u.status = 1)' : ' (u.status = 8)';
        //>>1.获取当前用户所有下级的存款量 包括自己的.
        $sql =<<<SQL
SELECT d.user_id,u.parent_id, u.parent_tree,IFNULL(SUM(d.amount), 0) AS deposit_totalAmount FROM deposits AS d 
LEFT JOIN users AS u ON d.user_id = u.user_id
WHERE {$userStatus}
AND d.user_id IN (SELECT user_id FROM users WHERE FIND_IN_SET({$user_id}, parent_tree) OR user_id = {$user_id})
SQL;

        //>>是否统计测试账号
        if ($is_test != -1) {
            $sql .= " AND u.is_test = $is_test";
        }

        //>>统计时间 起始时间
        if ($start_time != '') {
            $sql .= " AND d.`finish_time` >= '$start_time'";
        }

        //>>统计时间 结束时间
        if ($end_time != '') {
            $sql .= " AND d.`finish_time` <= '$end_time'";
        }

        //>>之前的,不知道什么用,先留着
        if ($domain != '') {
            $sql .= " AND u.username in (SELECT a.username FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id WHERE b.name like '%{$domain}%')";
        }
        $sql .= " GROUP BY d.user_id ORDER BY d.user_id DESC";
        return $GLOBALS['db']->getAll($sql, [], 'user_id');
    }
    /************************* snow 获取各团队存款量 end   ************************************************************/
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

    /************************ snow 复制代码 排除dcsite start***********************************************/
    /**
     * @param string $start_time
     * @param string $end_time
     * @param int $lottery_id
     * @return array
     */
    static public function getAllSalesExclude($start_time = '', $end_time = '', $lottery_id = 0)
    {
        // 根据彩种获得所有用户的总投注和总奖金
        $sql1 = 'SELECT p.user_id, u.top_id, u.username, SUM(p.amount) AS buy_amount, SUM(p.prize) AS prize_amount FROM packages AS p LEFT JOIN users AS u ON p.user_id = u.user_id WHERE 1 ';


        //>>添加排除总代dcsite 相关数据
        $sql1 .= ' AND p.user_id NOT IN ' . users::getUsersSqlByUserName('dcsite');
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

        //>>添加排除总代dcsite 相关数据
        $sql1 .= ' AND udr.user_id NOT IN ' . users::getUsersSqlByUserName('dcsite');
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


    /************************ snow 复制代码 排除dcsite end  ***********************************************/
    /************************ snow 获取总代的团队返点之和 排除dcsite start***********************************************/
    /**
     * author snow 获取总代的团队返点之和当天实时数据
     * @return array
     */
    public static function getTeamRebate()
    {

        //>>开始时间
        $start = date('Y-m-d 00:00:00');
        //>>结束时间
        $end   = date('Y-m-d H:i:s');
        $dcSite = users::getUsersSqlByUserName('dcsite');
        //>>设计查询补天实时总代团队返点数据
        $sql =<<<SQL
SELECT udr.top_id, SUM(udr.rebate_amount) AS rebate_amount 
FROM user_diff_rebates udr 
LEFT JOIN users u ON udr.user_id = u.user_id 
WHERE u.status = 8 AND udr.status = 1
AND udr.user_id NOT IN {$dcSite}
AND  udr.create_time >= '{$start}'
AND  udr.create_time <= '{$end}'
AND u.is_test = 0
GROUP BY udr.top_id
SQL;
       return $GLOBALS['db']->getAll($sql,[],'top_id');

    }

    /************************ snow 获取总代的团队返点之和 排除dcsite end  ***********************************************/
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
    static public function getTeamSale($user_id, $start_time, $end_time, $lottery_id = 0, $perfix = __CLASS__,$freeze = -1)
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


    static public function addThePrizePackageLog($datas)
    {
        $tmp = array();
        foreach ($datas as $v) {
            $tmp[] = "('{$v['admin_id']}','{$v['package_id']}','{$v['admin_username']}','{$v['prize']}','{$v['remark']}','{$v['package_create_date']}','{$v['package_create_time']}','{$v['client_ip']}','{$v['date']}' )";
        }
        $sql = "INSERT INTO theprizebypersonlog (admin_id,package_id,admin_username,prize,remark,package_create_date,package_create_time,client_ip,`date`) VALUES " . implode(',', $tmp);
        return $GLOBALS['db']->query($sql, array(), 'i');
    }

}

?>
