<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 负责投注封锁的model层操作.
 * 所使用的mysql数据库max_heap_table_size,tmp_table_size需要配置比较大的值
 */
class locks
{

    //表格前缀
    const TABLE_PREFIX = 'xlock_';

    /**
     * 默认期号字符串长度
     */
    const DEFAULT_ISSUE_CHAR_LENGTH = 13;

    /**
     * 默认投注项字符串长度
     */
    const DEFAULT_CODE_CHAR_LENGTH = 5;

    //要封锁的彩种id
    //static $lotterys = array(1, 4, 8, 9, 10);   //黑龙江时时彩已经禁用
    static $lotterys = array(9, 10, 21, 22);
    //要封锁的列表
    static $methods = array(
        /* 六合彩 */
        'TMZX',
        'TMSX',
        'TMWS',
        'TMSB',
        'TMDXDS',
        'ZTYX',
        'ZTYM',
        'ZTWS',
        'ELX',
        'SLX',
        'SILX',
        'SZS',
        'EZE',
        'SZE',
        /* 双色球 */
        'HLZX',
        /* 直选 */
        'SXZX',
        'SXHZ',
        'SXZS',
        'SXZL',
        'SXHHZX',
        'SXZXHZ',
        'YMBDW',
        'EMBDW',
        'QEZX',
        'QEZUX',
        'EXZX',
        'EXZUX',
        'QEDXDS',
        'EXDXDS',
        'SXDW',
        'QSZX',
        'QSHZ',
        'QSZS',
        'QSZL',
        'QSHHZX',
        'QSZXHZ',
        'QSYMBDW',
        'QSEMBDW',
        'QEDXDS',
        'EXDXDS',
        'WXDW',
    );

    /*
     * 为了节省内存限制期数列的长度
     */
    static $issue_char_length = array(
        '3D' => 7,
        'P3P5' => 7,
        'LHC' => 7,
        'SSQ' => 7,
    );

    /**
     * 为了节省内存限制方法代码的长度
     */
    static $code_char_length = array(
        'ZSZX' => 3,
        'QSZX' => 3,
        'SXZX' => 3,
        'QEZX' => 2,
        'EXZX' => 2,
        'YXZX' => 1,
        'TMZX' => 2,
        'TMSX' => 1,
        'TMWS' => 1,
        'TMSB' => 1,
        'TMDXDS' => 1,
        'ZTYM' => 2,
        'ZTYX' => 1,
        'ZTWS' => 1,
        'ELX' => 2,
        'SLX' => 3,
        'SILX' => 4,
        'SZS' => 3,
        'EZE' => 2,
        'SZE' => 3,
        'HLZX' => 12,
    );

    static public function getItems($lottery_id = null, $method_id = null)
    {
        $sql = 'SELECT l.lock_id,l.lottery_id,l.lock_limit,l.create_time,m.name,m.cname,m.method_id FROM methods AS m LEFT JOIN locks AS l ON m.method_id=l.method_id WHERE m.is_lock=1';
        $paramArr = array();
        if ($lottery_id) {
            $sql.=' AND m.`lottery_id`= :lottery_id';
            $paramArr[':lottery_id'] = $lottery_id;
        }
        if ($method_id) {
            $sql.=' AND m.`method_id`= :method_id';
            $paramArr[':method_id'] = $method_id;
        }

        $sql .= ' ORDER BY m.name ASC';

        return $GLOBALS['db']->getAll($sql , $paramArr);
    }

    /**
     * 获取封锁限制金额,返回封锁的金额上限，或者null找不到的情况。
     * @param type $lottery_id
     * @param type $method_id
     * @return 返回封锁的金额上限，或者null找不到的情况。
     */
    static public function getLockLimit($lottery_id, $method_id)
    {
        $sql = 'SELECT `lock_limit` FROM locks WHERE `lottery_id`=:lottery_id AND `method_id`=:method_id';

        if($lottery_id == 9 || $lottery_id == 10){
            $method_id = 0;
        }

        $paramArr = array();
        $paramArr[':lottery_id'] = $lottery_id;
        $paramArr[':method_id'] = $method_id;
        $result = $GLOBALS['db']->getRow($sql , $paramArr);
        if ($result) {
            return $result['lock_limit'];
        }

        return 0;
    }

    /**
     *
     * @param type $data
     * @return type
     * @throws exception2
     */
    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('locks', $data);
    }

    /**
     *
     * @param type $id
     * @param type $data
     * @return boolean
     * @throws exception2
     */
    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('locks',$data,array('lock_id'=>$id));
    }

    /**
     *
     * @param type $id
     * @return type
     * @throws exception2
     */
    static public function deleteItem($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }
        $sql = "DELETE FROM locks WHERE `lock_id` = :lock_id";
        $paramArr[':lock_id'] = $id;
        return $GLOBALS['db']->query($sql , $paramArr, 'd');
    }

    /* --------------------------------------------lock数据表业务------------------------------------------------------ */
    /**
     * 获取玩法的初始的投注项，这是展开之后的项目，受欢迎的玩法一般是全部
     * @param type $method 玩法 代码，如：ZSZX 混合祖选会被分割为 组三 祖六
     * @return type
     */
    static public function getCodes($methodName)
    {
        $methodName = strtoupper($methodName);
        $codes = array();
        $lhcCodes = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49);
        $lhcZodaics = array('鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪');
        $ssqRedCodes = array('01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33');
        $ssqBlueCodes = array('01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16');
        switch ($methodName) {
            /*  3 位直选  */
            case 'ZSZX':
            case 'QSZX':
            case 'SXZX':
                for ($i = 0; $i < 1000; $i++) {
                    $codes[] = str_pad($i, 3, '0', STR_PAD_LEFT);
                }
                break;
            /*  2 位直选  */
            case 'QEZX':
            case 'EXZX':
                for ($i = 0; $i < 100; $i++) {
                    $codes[] = str_pad($i, 2, '0', STR_PAD_LEFT);
                }
                break;
            /*  1位直选  */
            case 'YXZX':
                $codes = range(0, 9);
                break;
            /* 组三 */
            case 'SXZS':
            case 'QSZS':
            case 'ZSZS':
                $codes = methods::C(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), 2);
                break;
            /* 组六 */
            case 'SXZL':
            case 'QSZL':
            case 'ZSZL':
                $codes = methods::C(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), 3);
                break;
            case 'TMZX':
            case 'ZTYM':
                $codes = $lhcCodes;
                break;
            case 'ZTYX':
            case 'TMSX':
                $codes = $lhcZodaics;
                break;
            case 'TMWS':
            case 'ZTWS':
                $codes = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
                break;
            case 'TMSB':
                $codes = array('红', '蓝', '绿');
                break;
            case 'TMDXDS':
                $codes = array('大', '小', '单', '双');
                break;
            case 'ELX':
                $codes =  methods::C($lhcZodaics, 2);
                break;
            case 'SLX':
                $codes =  methods::C($lhcZodaics, 3);
                break;
            case 'SILX':
                $codes =  methods::C($lhcZodaics, 4);
                break;
            case 'SZS':
            case 'SZE':
                $codes = methods::C($lhcCodes, 3);
                break;
            case 'EZE':
                $codes = methods::C($lhcCodes, 2);
                break;
            case 'HLZX':
                $redTmp = methods::C($ssqRedCodes, 5);
                foreach ($variable as $key => $value) {
                    # code...
                }
                break;
            default:
                throw new exception2('不支持的玩法');
                break;
        }

        return $codes;
    }

    static public function getTableName($lotteryName, $methodName)
    {
        if (!$lotteryName || !$methodName) {
            throw new exception2('参数无效');
        }

        return strtolower(self::TABLE_PREFIX) . strtolower($lotteryName) . '_' . strtolower($methodName);
    }

    /**
     * 创建一个 投注封锁表
     * $lottery='CQSSC', 彩种
     * $method='SXZX' 玩法
     */
    static public function createTable($tableName, $lotteryName, $methodName)
    {
        if (!$tableName || !$lotteryName) {
            throw new exception2('参数无效');
        }

        $length_code = isset(self::$code_char_length[$methodName]) ? self::$code_char_length[$methodName] : self::DEFAULT_CODE_CHAR_LENGTH;
        $length_issue = isset(self::$issue_char_length[$lotteryName]) ? self::$issue_char_length[$lotteryName] : self::DEFAULT_ISSUE_CHAR_LENGTH;
        $sql = 'CREATE TABLE IF NOT EXISTS `' . $tableName . '` (
            `issue` CHAR(' . $length_issue . ') NOT NULL COMMENT \'奖期\',
            `code` CHAR(' . $length_code . ') NOT NULL COMMENT \'号码\',
            `amount` MEDIUMINT NOT NULL DEFAULT \'0\' COMMENT \'金额\' ,
            UNIQUE INDEX `issue_code`  (`issue`, `code`))
            ENGINE = MEMORY;';
        //MEMORY
        return $GLOBALS['db']->doExec($sql);
    }

    /**
     * 插入数据到locks相关的类表里
     * @param type $lottery_id
     * @param type $methodName
     * @param type $date
     * @return boolean
     */
    static public function insertTableData($tableName, $methodName, $issues)
    {
        if (!$tableName || !$methodName) {
            throw new exception2('参数无效');
        }
        $codes = locks::getCodes($methodName);
        $count = 0;

        if ($issues && $codes) {
            $datas = array();
            foreach ($issues as $issue) {
                if(empty($issue)){
                    continue;
                }
                foreach ($codes as $code) {
                    $datas[] = array(
                        'issue' => $issue['issue'],
                        'code' => $code,
                    );
                }
                //自主采种一天1380期，数据量过大容易内存溢出，因此每100期插一次记录
                if (++$count % 10 == 0) {
                    $GLOBALS['db']->normalMultipInsert($tableName, $datas);
                    $affected_rows = $GLOBALS['db']->affected_rows();
                    if ($affected_rows != count($datas)) {
                        echo "插入表 $tableName 数据错误：数目不对 $affected_rows 数目为0可能是已经运行过一次了也是一种常见情况\n";
                    }
                    unset($datas);
                    $datas = array();
                }
            }

            if ($datas) {
                $GLOBALS['db']->normalMultipInsert($tableName, $datas);
                $affected_rows = $GLOBALS['db']->affected_rows();
                if ($affected_rows != count($datas)) {
                    echo "插入表 $tableName 数据错误：数目不对 $affected_rows ,数目为0可能是已经运行过一次了也是一种常见情况\n";
                }
                unset($datas);
            }
        }
        unset($codes);

        return true;
    }


    /**
     * 插入数据到locks相关的类表里，针对双色球
     * @param type $tableName
     * @param type $methodName
     * @param type $issue
     * @return boolean
     */
    static public function insertTableSsqData($tableName, $issue)
    {
        if (!$tableName) {
            throw new exception2('参数无效');
        }
        $count = 0;
        $ssqRedCodes = array('01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33');
        $ssqBlueCodes = array('01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16');
        $redTmp = methods::C($ssqRedCodes, 5);
        $datas = array();

        foreach ($ssqBlueCodes as $v) {
            foreach ($redTmp as $vv) {
                $datas[] = array(
                    'issue' => $issue,
                    'code' => (int)($vv.$v),
                );
                //数据量过大容易内存溢出，因此每20000插一次记录
                if (++$count % 1000 == 0) {
                    $GLOBALS['db']->normalMultipInsert($tableName, $datas);
                    $affected_rows = $GLOBALS['db']->affected_rows();

                    unset($datas);
                    $datas = array();
                }
            }
        }


        if ($datas) {//没整除剩下的
            $GLOBALS['db']->normalMultipInsert($tableName, $datas);
            $affected_rows = $GLOBALS['db']->affected_rows();

            unset($datas);
        }


        return true;
    }

    /**
     * 插入数据到locks相关的类表里针对低频
     * @param type $tableName
     * @param type $issue
     * @return boolean
     */
    static public function insert3DP3TableData($tableName, $issue)
    {
        if (!$tableName || !$issue) {
            throw new exception2('参数无效29999');
        }
        $codeStr = array('0123456789','0123456789','0123456789');
        $codes = methods::expand($codeStr);

        $datas = array();
        foreach ($codes as $code) {
            $datas[] = array(
                'issue' => $issue,
                'code' => $code,
            );
        }

        $GLOBALS['db']->normalMultipInsert($tableName, $datas);
        $affected_rows = $GLOBALS['db']->affected_rows();
        if ($affected_rows != count($datas)) {
            echo "插入表 $tableName 数据错误：数目不对 $affected_rows 数目为0可能是已经运行过一次了也是一种常见情况\n";
        }
        unset($datas);
        $datas = array();
        unset($codes);

        return true;
    }

    /**
     * 插入新数据 并且删除指定日期的旧数据
     * @param type $lotteryIds 彩种列表
     * @param type $methodNames 方法列表
     * @param type $date 新数据所属的日期
     * @param type $deleteDate 要要删除的数据所属的日期
     */
    static public function batchEditLocksData($lotteryIds, $methodNames, $date, $deleteDate)
    {
        if (!($date || $deleteDate) || !$lotteryIds || !$methodNames) {
            throw new exception2('参数无效');
        }

        foreach ($lotteryIds as $lotteryId) {
            if (!in_array($lotteryId, self::$lotterys)) {
                echo '不支持所传入的彩种:' . $lotteryId;
                continue;
            }

            $lottery = lottery::getItem($lotteryId);
            $methods = methods::getItems($lotteryId);
            $methods = array_spec_key($methods, 'name');
            $issues = array();
            if (!empty($date)) {
                if($lotteryId == 21 || $lotteryId == 22){//由于六合彩双色球是两三天一期，不存在一天多期，所以只需获得当前销售奖期
                    $issues[] = issues::getCurrentIssue($lotteryId);
                }else{
                    $issues = issues::getItems($lotteryId, date('Y-m-d', $date)); //format like :2014-05-05
                }
            }

            $deleteIssues = array();
            if (!empty($deleteDate)) {
                if($lotteryId == 21 || $lotteryId == 22){//由于六合彩双色球是两三天一期，不存在一天多期，所以只需获得当前销售奖期
                    $deleteIssues[] = issues::getCurrentIssue($lotteryId);
                }else{
                    $deleteIssues = issues::getItems($lotteryId, date('Y-m-d', $deleteDate)); //format like :2014-05-05
                }
            }

            if ($deleteIssues) {
                $tmpIssues = array();
                foreach ($deleteIssues as $deleteIssue) {
                    $tmpIssues[] = $deleteIssue['issue'];
                }
                $deleteIssues = implode("','", $tmpIssues);
                unset($tmpIssues);
            }

            if($lotteryId == 9 || $lotteryId == 10){//对于低频不做表分离
                $tableName = 'xlock_3d';
                if($lotteryId == 10){
                    $tableName = 'xlock_p3';
                }
                locks::createTable($tableName, $lottery['name'], '');

                if ($issues) {
                    if(locks::insert3DP3TableData($tableName, $issues[0]['issue'])){
                        echo "插入表 $tableName  数据成功\n";
                    } else {
                        echo "插入表 $tableName  数据失败\n";
                        return false;
                    }
                }

                if ($deleteIssues) {
                    $sql = "DELETE FROM $tableName WHERE `issue` in ('$deleteIssues')";

                    if ($GLOBALS['db']->query($sql, array(), 'd') === false) {
                        echo "删除表 $tableName 数据出错\n";
                        return false;
                    }
                    $affected_rows = $GLOBALS['db']->affected_rows();

                    echo "删除表 $tableName 数据：影响数目$affected_rows \n";
                }
            }else{
                foreach ($methodNames as $methodName) {
                    if (!isset($methods[$methodName])) {
                        //echo $lotteryId . ' 彩种没有此方法:' . $methodName;
                        continue;
                    }
                    if (!in_array($methodName, self::$methods)) {
                        echo "不支持封锁的玩法:$methodName \n";
                        continue;
                    }

                    $tableName = locks::getTableName($lottery['name'], $methodName);
                    locks::createTable($tableName, $lottery['name'], $methodName);
                    if ($issues) {
                        if($lotteryId == 21 || $lotteryId == 22){//六合彩双色球跨天，故每天cli运行时需检查是否是当前期数据
                            $sql = "SELECT issue from $tableName LIMIT 0,1";
                            $issue = $GLOBALS['db']->getCol($sql, 'issue');
                            if(isset($issue[0]) && !empty($issues[0]) && $issue[0] == $issues[0]['issue']){
                                echo "无需插入表 $tableName  数据,直接跳过\n";
                                continue;
                            }
                        }

                        if($lotteryId == 22){//双色球独立插入方法
                            $res = locks::insertTableSsqData($tableName, $issues[0]['issue']);
                        }else{
                            $res = locks::insertTableData($tableName, $methodName, $issues);
                        }
                        if($res){
                            echo "插入表 $tableName  数据成功\n";
                        }else{
                            echo "插入表 $tableName  数据失败\n";
                            return false;
                        }
                    }

                    if ($deleteIssues) {
                        if($lotteryId == 21 || $lotteryId == 22){//如果是六合彩双色球无视$deleteIssues，删除当前期以前所有即可
                            $sql = "DELETE FROM $tableName WHERE `issue` < '{$deleteIssues}'";
                        }else{
                            $sql = "DELETE FROM $tableName WHERE `issue` in ('$deleteIssues')";
                        }

                        if ($GLOBALS['db']->query($sql, array(), 'd') === false) {
                            echo "删除表 $tableName 数据出错\n";
                            return false;
                        }
                        $affected_rows = $GLOBALS['db']->affected_rows();

                        echo "删除表 $tableName 数据：影响数目$affected_rows \n";
                    }
                }
            }
            unset($lottery);
            unset($methods);
            unset($issues);
            unset($deleteIssues);
        }

        return true;
    }

    /**
     * 清空旧数据
     * @param type $lotteryIds 彩种列表
     * @param type $methodNames 方法列表
     */
    static public function truncateLocksData($lotteryIds, $methodNames)
    {
        if (!$lotteryIds || !$methodNames) {
            throw new exception2('参数无效');
        }

        foreach ($lotteryIds as $lotteryId) {
            if (!in_array($lotteryId, self::$lotterys)) {
                echo '不支持所传入的彩种:' . $lotteryId;
                continue;
            }
            $lottery = lottery::getItem($lotteryId);
            $methods = methods::getItems($lotteryId);
            $methods = array_spec_key($methods, 'name');

            foreach ($methodNames as $methodName) {
                if (!isset($methods[$methodName])) {
                    //echo $lotteryId . ' 彩种没有此方法:' . $methodName;
                    continue;
                }
                if (!in_array($methodName, self::$methods)) {
                    echo "不支持所传入的玩法:$methodName \n";
                    continue;
                }
                $tableName = locks::getTableName($lottery['name'], $methodName);
                locks::createTable($tableName, $lottery['name'], $methodName);
                $sql = "truncate table $tableName ;";
                if (!$GLOBALS['db']->doExec($sql)) {
                    echo "删除表 $tableName 数据出错\n";
                    return false;
                }
            }
            unset($lottery);
            unset($methods);
        }

        return true;
    }

    /**
     * 判断是否超出全局投注限额
     * @param array $lottery  彩种
     * @param array $method  玩法
     * @param string $issue 奖期
     * @param Array $codes 投注值 投注号及投注中奖奖金金额: (array(code=>123,amount=>999 ))
     * @return array  返回false表示未达到限额,否则返回已达到限额的号码数组
     */
    public static function checkMultipleLimit($lottery, $method, $issue, $codes, $amount)
    {
        if (!is_array($lottery) || !is_array($method) || !is_array($codes) || $amount < 0) {
            throw new exception2('参数非法(2201)');
        }
        if ($amount < 500) {//如果假设中奖小于500就算超过封锁也不检查
            return false;
        }

        $lockLimit = locks::getLockLimit($lottery['lottery_id'], $method['method_id']);
        if (empty($lockLimit)) {
            return false;
        }
        $tableNames = locks::getRealTables($lottery, $method, $codes);

        $result = false;
        foreach ($tableNames as $tableName => $tableCodes) {
            if (!$tableCodes) {
                continue;
            }
            $tmpCodes = implode("','", $tableCodes);
            $codeTimes = array_count_values($tableCodes);
            rsort($codeTimes); //按照从大到小排序
            $times = $codeTimes[0]; //号码被重复投注的最大次数
            $maxAmount = $lockLimit - ($amount * $times);

            $sql = "SELECT code FROM $tableName WHERE issue=:issue AND code IN ('$tmpCodes') AND amount >= :amount ";
            $paramArr = array(':issue' => $issue , ':amount' => $maxAmount);
            $tmp = $GLOBALS['db']->getCol($sql,'code',$paramArr);

            if ($tmp) {
                $result = $result ? array_merge($result, $tmp) : $tmp;
            }
        }

        return $result;
    }

    /**
     * 判断是否超出全局投注限额
     * @param array $issue  彩种
     * @param array $codeGroups  注码组
     * @param string $amount 最高奖金3D都按最高1930算
     * @return array  返回false表示未达到限额,否则返回已达到限额的号码数组
     */
    public static function check3DP3MultipleLimit($lotteryId, $issue, $codeGroups, $amount)
    {
        if (!is_array($codeGroups) || $amount < 0) {
            throw new exception2('参数非法(2201)');
        }
        if ($amount < 500) {
            return false;
        }
        $lockLimit = locks::getLockLimit($lotteryId, 0);
        if (empty($lockLimit)) {
            return false;
        }
        $result = false;
        $tmpCode = array();
        foreach ($codeGroups as $methodName => $code) {
            $codeAllP = methods::get3dP3AllP($methodName,$code);
            foreach($codeAllP as $c => $num){//计算所有直选号的个数
                if(array_key_exists($c, $tmpCode)){
                    $tmpCode[$c] += $num;
                } else {
                    $tmpCode[$c] = $num;
                }
            }
        }
        $times = array_values($tmpCode);
        rsort($times); //按照从大到小排序
        $maxAmount = $lockLimit - ($amount * $times[0]);
        $tmpCodeStr = implode("','",array_keys($tmpCode));
        $tableName = 'xlock_3d';
        if($lotteryId == 10){
            $tableName = 'xlock_p3';
        }
        $sql = "SELECT code FROM {$tableName} WHERE issue=:issue AND code IN ('$tmpCodeStr') AND amount >= :amount ";
        $paramArr = array(':issue' => $issue , ':amount' => $maxAmount);
        if ($tmp = $GLOBALS['db']->getCol($sql,'code',$paramArr)) {
            $result = $tmp;
        }

        return array('result'=>$result,'allP'=>$tmpCode);
    }

    /**
     * 更新全局投注限额
     * @param array $lottery  彩种
     * @param array $method  玩法
     * @param array $issues 奖期
     * @param type $code 投注信息 投注号及投注中奖奖金金额
     * @return boolean
     */
    public static function updateMultipleLimit($lottery, $method, $issues, $codes, $amount)
    {
        if (!is_array($lottery) || !is_array($method) || !is_array($codes)) {
            throw new exception2('参数非法(2202)');
        }
        if (abs($amount) < 500) {
            return true;
        }

        $tmpIssues = implode("','", $issues);
        $tableNames = locks::getRealTables($lottery, $method, $codes);//array('xlock_3d_exzx'=>array(110,234,...))
        $result = 0;
        foreach ($tableNames as $tableName => $tableCodes) {
            if (!$tableCodes) {
                continue;
            }
            $tmpCodes = implode("','", $tableCodes);
            $codeTimes = array_count_values($tableCodes);
            rsort($codeTimes); //按照从大到小排序
            $times = $codeTimes[0]; //号码被重复投注的最大次数
            $maxAmount = $amount * $times; //资金限额统一增加
            $sql = "UPDATE $tableName SET amount = amount + $maxAmount WHERE issue IN ('$tmpIssues') AND code IN ('$tmpCodes')";

            $tmp = $GLOBALS['db']->query($sql, array(), 'u');
            if ($tmp) {
                $result += $GLOBALS['db']->affected_rows();
            }
            else {
                return false;
            }
        }

        return $result;
    }

    /**
     * 更新低频投注限额
     * @param array $lotteryId  彩种
     * @param array $issue 奖期
     * @param type $codes 投注信息 投注号及投注中奖奖金金额
     * @param type $amount 增加封锁金额
     * @return boolean
     */
    public static function update3DP3MultipleLimit($lotteryId, $issue, $codes, $amount)
    {
        if (!is_numeric($lotteryId) || !is_array($codes) || !$issue) {
            throw new exception2('参数非法(2202)');
        }
        $tableName = 'xlock_3d';
        $result = false;
        if($lotteryId == 10){
            $tableName = 'xlock_p3';
        }

        $tmpCodes = implode("','",array_keys($codes));

        $sql = "UPDATE $tableName SET amount = amount + $amount WHERE issue = '$issue' AND code IN ('$tmpCodes')";

        $tmp = $GLOBALS['db']->query($sql, array(), 'u');
        if ($tmp) {
            $result = $GLOBALS['db']->affected_rows();
        }

        return $result;
    }

    static function getRealTables($lottery, $method, $codes)
    {
        $methodName = $method['name'];
        $tableNames = array();
        switch ($methodName) {
            case 'SXHHZX':
                $tableNames[locks::getTableName($lottery['name'], 'SXZS')] = array();
                $tableNames[locks::getTableName($lottery['name'], 'SXZL')] = array();
                foreach ($codes as $code) {
                    //组三
                    if (strlen($code) == 2) {
                        $tableNames[locks::getTableName($lottery['name'], 'SXZS')][] = $code;
                    }
                    //组六
                    else {
                        $tableNames[locks::getTableName($lottery['name'], 'SXZL')][] = $code;
                    }
                }
                break;
            case "ZSHHZX":
                $tableNames[locks::getTableName($lottery['name'], 'ZSZS')] = array();
                $tableNames[locks::getTableName($lottery['name'], 'ZSZL')] = array();
                foreach ($codes as $code) {
                    //组三
                    if (strlen($code) == 2) {
                        $tableNames[locks::getTableName($lottery['name'], 'ZSZS')][] = $code;
                    }
                    //组六
                    else {
                        $tableNames[locks::getTableName($lottery['name'], 'ZSZL')][] = $code;
                    }
                }
                break;
            case 'QSHHZX':
                $tableNames[locks::getTableName($lottery['name'], 'QSZS')] = array();
                $tableNames[locks::getTableName($lottery['name'], 'QSZL')] = array();
                foreach ($codes as $code) {
                    //组三
                    if (strlen($code) == 2) {
                        $tableNames[locks::getTableName($lottery['name'], 'QSZS')][] = $code;
                    }
                    //组六
                    else {
                        $tableNames[locks::getTableName($lottery['name'], 'QSZL')][] = $code;
                    }
                }
                break;
            default:
                if($lottery['lottery_id'] == 9){
                    $tableKey = 'xlock_3d';
                } elseif ($lottery['lottery_id'] == 10) {
                    $tableKey = 'xlock_p3';
                } else {
                    $tableKey = locks::getTableName($lottery['name'], $methodName);
                }
                $tableNames[$tableKey] = $codes;
                break;
        }

        return $tableNames;
    }

}

?>