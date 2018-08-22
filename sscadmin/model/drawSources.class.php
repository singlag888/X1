<?php

/**
 * todo:比如早晨第一期都没开出，那不应该去抓取
 */
class drawSources
{

    static $fields = array('lottery_id', 'name', 'url', 'needlogin', 'loginname', 'loginpwd', 'refresh', 'interface', 'rank', 'enabled', 'date');
    static $wget = NULL;

    const CHECK_OPEN_TIME_LIMIT = 18000; //开奖时间校验，考虑官方开奖时间跟本地的销售截止时间差额最大限(5小时)

    static public function getItem($id, $is_enabled = 1)
    {
        if ($id <= 0) {
            throw new exception2('参数无效');
        }
        $sql = "SELECT * FROM `drawsources` WHERE ds_id = '$id'";
        if ($is_enabled !== NULL) {
            $sql .= " AND is_enabled = '$is_enabled'";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($lottery_id = 0, $is_enabled = 1, $style = 0)
    {
        $sql = "SELECT * FROM `drawsources` WHERE 1";
        if ($lottery_id != 0) {
            if (is_array($lottery_id)) {
                $sql .= " AND lottery_id IN (" . implode(',', $lottery_id) . ")";
            } else {
                $sql .= " AND lottery_id = " . intval($lottery_id);
            }
        }

        if ($is_enabled !== NULL) {
            $sql .= " AND is_enabled = '$is_enabled'";
        }
        $sql .= " ORDER BY lottery_id ASC,`rank` DESC";
        $result = $GLOBALS['db']->getAll($sql, array(), 'ds_id');
        if ($style == 1) {
            $tmp = array();
            foreach ($result as $v) {
                $tmp[$v['lottery_id']][$v['ds_id']] = $v;
            }
            $result = $tmp;
        }

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('drawsources', $data);
    }

    static public function updateItem($id, $data, $addonsConditions = array())
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $addonsConditions['ds_id'] = $id;

        return $GLOBALS['db']->updateSM('drawsources', $data, $addonsConditions);
    }

    static public function deleteItem($id)
    {
        if (empty($id)) {
            throw new exception2('参数无效');
        }

        $sql = "DELETE FROM drawsources WHERE ds_id = " . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * 奖期历史模型
     */
    static public function getLastHistories($onlyError = false)
    {
        $sql = "SELECT lottery_id, max(ds_id) AS ds_id FROM drawhistory WHERE 1";
        if ($onlyError) {
            $sql .= " AND errno > 0";
        }
        $sql .= " GROUP BY lottery_id ORDER BY lottery_id ASC";
        $ids = array();
        foreach ($this->oDB->getAll($sql) as $v) {
            $ids[$v['lottery_id']] = $v['ds_id'];
        }

        return self::getHistoriesById($ids);
    }

    static public function getHistories($lottery_id = 0, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT * FROM drawhistory WHERE 1';
        if ($lottery_id != 0) {
            $sql .= " AND lottery_id = '$lottery_id'";
        }
        $sql .= ' ORDER BY dh_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);
        return $result;
    }

    static public function getHistoriesNumber($lottery_id = 0)
    {
        $sql = 'SELECT COUNT(*) AS count FROM drawhistory WHERE 1';
        if ($lottery_id != 0) {
            $sql .= " AND lottery_id = '$lottery_id'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result['count'];
    }

    static public function addUpdateHistory($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }
        $sql = "INSERT INTO drawhistory (lottery_id,issue,earliest_input_time,ds_id,rank,errno,number,original_number,open_time,retry,spent,create_time) VALUES(" .
            "'{$data['lottery_id']}', '{$data['issue']}', '{$data['earliest_input_time']}', '{$data['ds_id']}', '{$data['rank']}', '{$data['errno']}', '{$data['number']}', '{$data['original_number']}', '{$data['open_time']}', '{$data['retry']}', '{$data['spent']}', '{$data['create_time']}')" .
            " ON DUPLICATE KEY UPDATE retry=retry+1, errno='{$data['errno']}', number='{$data['number']}', original_number='{$data['original_number']}', create_time='{$data['create_time']}'";
        return $GLOBALS['db']->query($sql, array(), 'i');
    }

    /**
     * 分分采开奖策略 可以很复杂 也可以很简单
     * @param type $lottery
     * @param type $expectedIssue
     * @param type $debug
     * @return type
     */
    static public function drawSelf($lottery, $expectedIssue, $projects = array())
    {
        $result = drawStrategy::getPrizeCode($lottery, $expectedIssue, $projects);

        return $result;
    }

    /**
     * 抓取号码，功能单一，只取指定彩种指定奖期，按设置一次轮一圈，直到取完，出错抛异常
     * @param <type> $lottery_id
     * @param <type> $issue
     */
    static public function fetchDrawNumber($lottery, $expectedDate, $expectedIssue, $earliest_input_time, $debug = 0)
    {
        if (!$sources = drawSources::getItems($lottery['lottery_id'])) {
            throw new exception2("No source to be fetched", 31);
        }

        $least_rank = config::getConfig('least_rank');
        $retry = $rank = 0;
        $result = array();
        $openTime = ''; //官方提前开奖时间
        $startInterval = $lottery['catch_interval'];
        do {
            //先全部轮流抓一遍，看有没有达到分值
            foreach ($sources as $k => $v) {
                // $tmp = array('errno' => 0, 'issuestr' => NULL, 'issue' => $tmp['issue'], 'number' => $tmp['number'], 'time' => $tmp['time'])
                echo "{$v['name']}(id={$v['ds_id']}) (URL={$v['url']})";
                $tmp = drawSources::fetchFromURL($lottery, $v['url'], $expectedDate, $expectedIssue);

                //如果是扑克先给扑克开奖号码排序
                if ($lottery['lottery_id'] == 14 && isset($tmp['number'])) {

                    $nuMmap = array_flip(methods::$pokerNumMaps);
                    $colorMap = array('s' => 1, 'h' => 2, 'c' => 3, 'd' => 4);
                    $poker = array();
                    $pokerCode = explode(' ', $tmp['number']);
                    foreach ($pokerCode as $k1 => $v1) {
                        $poker[$k1]['num'] = $nuMmap[$v1[0]];
                        $poker[$k1]['color'] = $colorMap[$v1[1]];
                        $poker[$k1]['code'] = $v1;
                    }
                    $sortPoker = array_spec_key($poker, 'num');
                    if (count($sortPoker) == 1) {
                        $sortPoker = array_spec_key($poker, 'color');
                        ksort($sortPoker);
                        $first = current($sortPoker);
                        $second = next($sortPoker);
                        $thrid = next($sortPoker);
                        $tmp['number'] = $first['code'] . ' ' . $second['code'] . ' ' . $thrid['code'];
                    }
                    if (count($sortPoker) == 3) {
                        ksort($sortPoker);
                        $first = current($sortPoker);
                        $second = next($sortPoker);
                        $thrid = next($sortPoker);
                        $tmp['number'] = $first['code'] . ' ' . $second['code'] . ' ' . $thrid['code'];
                    }
                    if (count($sortPoker) == 2) {
                        ksort($sortPoker);
                        foreach ($poker as $v2) {
                            if (array_key_exists($v2['num'], $sortPoker) && $sortPoker[$v2['num']]['color'] != $v2['color']) {
                                $part1 = array_values($sortPoker); //重新计算索引
                                $key = array_search($sortPoker[$v2['num']], $part1);
                                $part2 = array($v2);
                                if ($v2['color'] > $sortPoker[$v2['num']]['color']) {
                                    array_splice($part1, $key + 1, 0, $part2);
                                } else {
                                    array_splice($part1, $key, 0, $part2);
                                }
                                break;
                            }
                        }
                        $first = current($part1);
                        $second = next($part1);
                        $thrid = next($part1);
                        $tmp['number'] = $first['code'] . ' ' . $second['code'] . ' ' . $thrid['code'];
                    }
                }
                $original_number = isset($tmp['original_number']) ? $tmp['original_number'] : '';
                // 写抓取历史
                if (!$debug) {
                    $data = array(
                        'lottery_id' => $lottery['lottery_id'],
                        'issue' => $expectedIssue,
                        'ds_id' => $v['ds_id'],
                        'rank' => $v['rank'],
                        'errno' => $tmp['errno'],
                        'number' => isset($tmp['number']) ? $tmp['number'] : '',
                        'original_number' => $original_number,
                        'retry' => 0,
                        'spent' => $tmp['time'],
                        'open_time' => !empty($tmp['openTime']) ? $tmp['openTime'] : '0000-00-00 00:00:00',
                        'earliest_input_time' => $earliest_input_time,
                        'create_time' => date('Y-m-d H:i:s'),
                    );
                    drawSources::addUpdateHistory($data);
                }

                //多个源的开奖时间
                if (!empty($tmp['openTime'])) {
                    $openTime = empty($openTime) ? $tmp['openTime'] : ((strtotime($tmp['openTime']) > strtotime($openTime)) ? $openTime : $tmp['openTime']);
                }

                if ($tmp['errno']) {
                    echo "没取到对应奖期`$expectedIssue`，可能因为源还没有更新" . "errno=" . $tmp['errno'] . "\n"; // 返回错误消息是".$tmp['errstr']
                    if ($retry >= $lottery['catch_retry']) {
                        //$fn = "/tmp/{$v['name']}_{$expectedIssue}_" . date('YmdHis') . ".txt";
                        echo("\n\n异常：{$v['name']}_{$expectedIssue}_" . date('YmdHis') . str_repeat('=', 50) . "\n\n");
                        echo($tmp['errstr']);
                    }
                    continue;   //这个号源没抓到，先放下，抓其他的源如果分值达到即开奖
                }
                $tmp['ds_id'] = $v['ds_id'];
                $result[] = $tmp;
                $rank += $v['rank'];
                unset($sources[$k]);    //重要：对于抓到号码了的应在下次foreach之前被排除
                echo "\t号码 {$tmp['number']}, 权值:{$v['rank']} ,时间 {$tmp['time']}\n";
                // 如果rank达标提前返回 没必要浪费时间
                if ($rank >= $least_rank) {
                    drawSources::assertEqual($result);
                    return array('number' => $tmp['number'], 'original_number' => $original_number, 'rank' => $rank, 'openTime' => $openTime);
                }
            }
            // 数据源已经轮询一次，未到达标分值
            $retry++;
            if ($retry > $lottery['catch_retry']) {
                echo "程序反复执行超过{$lottery['catch_retry']}次仍然没取到，退出\n";
                break;
            }

            if ($sources) {
                echo "\n以下开奖源(id=" . implode(',', array_keys($sources)) . ")没有取到号码！将延时{$lottery['catch_interval']}秒，现在开始重试第 {$retry}/{$lottery['catch_retry']} 次......\n";
                //140210 为应对有些采种如TJSSC经常延迟的问题
                sleep($startInterval);
                $startInterval += intval($lottery['catch_interval'] / 4);
            }
        } while (!empty($sources));

        if (!$result) {
            throw new exception2("ALL SOURCE FETCH FAILED!", 35);
        }
        drawSources::assertEqual($result);
        $tmp = reset($result);

        return array('number' => $tmp['number'], 'original_number' => $tmp['original_number'], 'rank' => $rank, 'openTime' => $openTime);
    }

    // 判断是否全等，如果有一个错就是非常严重的错误，因为一个网站没有及时更新数据是正常的，但如果更新一个错误的号码是不能容忍的
    private static function assertEqual($numbers)
    {
        if (!is_array($numbers)) {
            return true;
        }

        $tmp = array_pop($numbers);
        foreach ($numbers as $v) {
            if ($tmp['number'] !== $v['number']) {
                throw new exception2("The source id {$v['ds_id']} result ({$v['number']}) is different from {$tmp['ds_id']}({$tmp['number']})", 14);
            }
        }

        return true;
    }

    static public function fetchFromURL($lottery, $url, $expectedDate, $expectedIssue = 0)
    {

        $t1 = microtime(true);
        if (!self::$wget) {
            self::$wget = new wget();
        }
        $parts = parse_url($url);
        $mat = '';

        if (preg_match('`^(\d+\.){3}\d+$`', $parts['host'])) {//紧急切换成IP的奖源
            $result = drawSources::_getFromLotteryAPI($lottery, $url, $expectedIssue);
        } else {
            preg_match('`([\w-\d]+\.)*([\w-\d]+)\.(?:com\.cn|cn|com|co|cc|net|biz|info)$`Ui', $parts['host'], $match);
            $mat = $match[2];
            switch ($mat) {
                case '9ckj':
                    $result = drawSources::_getFrom9ckj($lottery, $url, $expectedIssue);
                    break;
                case 'qq':
                    $result = drawSources::_getFromQq($lottery, $url, $expectedIssue);
                    break;
                case 'jiangyuan365':
                    $result = drawSources::_getFrom365($lottery, $url, $expectedIssue);
                    break;
                case 'shishicai':
                    $result = drawSources::_getFromShiShiCaiCn($lottery, $url, $expectedIssue);
                    break;
                case '500':
                    $result = drawSources::_getFrom500Com($lottery, $url, $expectedIssue);
                    break;
                //            case 'sohu':
                //                $result = drawSources::_getFromSohuCom($lottery, $url, $expectedDate, $expectedIssue);
                //                break;
                case 'xjflcp':
                    $result = drawSources::_getFromXjflcpCom($lottery, $url, $expectedIssue);
                    break;
                case 'touzhuzhan':
                    $result = drawSources::_getFromTouZhuZhanCom($lottery, $url, $expectedIssue);
                    break;
                case 'kuai3' :
                    $result = drawSources::_getFromKuai3Com($lottery, $url, $expectedIssue);
                    break;
                case 'caishijie':
                    $result = drawSources::_getFromCaishijieCom($lottery, $url, $expectedIssue);
                    break;
                case 'ecp888':
                    $result = drawSources::_getFromEcp888Com($lottery, $url, $expectedIssue);
                    break;
                case 'cailele':
                    $result = drawSources::_getFromCaileleCom($lottery, $url, $expectedIssue);
                    break;
                case 'aicai':
                    $result = drawSources::_getFromAicaiCom($lottery, $url, $expectedIssue);
                    break;
                case 'huacai':
                    $result = drawSources::_getFromHuacaiCn($lottery, $url, $expectedIssue);
                    break;
                case 'cjcp':
                    $result = drawSources::_getFromCjcpComCn($lottery, $url, $expectedIssue);
                    break;
                case '163':
                    $result = drawSources::_getFrom163Com($lottery, $url, $expectedIssue);
                    break;
                case '360':
                    $result = drawSources::_getFromCp360Cn($lottery, $url, $expectedIssue);
                    break;
                case '168kai':
                    $result = drawSources::_getFrom168Kai($lottery, $url, $expectedIssue);
                    break;
                case 'lecai':
                    $result = drawSources::_getFromBaiduLecai($lottery, $url, $expectedIssue);
                    break;
                case 'apiplus':
                    $result = drawSources::_getFromLotteryAPI($lottery, $url, $expectedIssue);
                    break;
                case 'kaijiangtong':
                    $result = drawSources::_getFromKaiJiangTong($lottery, $url, $expectedIssue);
                    break;
                default:
                    return drawSources::failed(11, "暂不支持的网站:`{$url}`");
                    break;
            }
        }
        if (!$result) {
            return false;
        }
        $result['time'] = round(microtime(true) - $t1, 3);

        return $result;
    }

    /**
     * 彩票网
     * @param $lottery 根据drawsources表lottery_id 查询lottery数据
     * @param $url 开奖源网址
     * @param $expectedIssue 奖期
     * @return array
     * author: the rock
     */
    public static function _getFromKaiJiangTong($lottery, $url, $expectedIssue)
    {
        self::$wget->setInCharset('gb2312');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        $jsonObj = json_decode($contents);
        if ($jsonObj == null) return drawSources::failed(31, "取内容出错：" . $contents);
        $result = array();
        switch ($lottery['lottery_id']) {
            //=====================Data comes from: snow============================
            case 1: //重庆时时彩    正确格式：20140116-66 96513
            case 8: //天津时时彩
                $result = self::_manipulationToShiShiCai($jsonObj);
                break;
            case 4: //新疆时时彩
                $result = self::_manipulationToXJSSC($jsonObj);
                break;
            case 2: //山东11选5  正确格式：20150918-046 06 04 10 11 01
            case 6: //江西11选5
                $result = self::_manipulationShiYiXuanWu($jsonObj);
                break;
            case 5: //江苏11选5  正确格式：20150918-046 06 04 10 11 01
            case 7: //广东11选5
                $result = self::_manipulationShiYiXuanWu($jsonObj,true);
                break;
            case 9: //福彩3D   正确格式：2015258  227
                $result = self::_manipulationFuCaiSanD($jsonObj);
                break;
            case 10: //体彩P3P5  正确格式：2016016 12345
                $result = self::_manipulationTiCaiPSanPWu($jsonObj);
                break;
            //======================Data comes from: the rock===========================
            case 12: //快三 正确格式：20160311-036：222
            case 19:
                $result = self::_getKuai3ByKaiJiangTong($jsonObj);
                break;
            case 14: //快乐扑克 正确格式：20160311-036 3h 6s 6d
                $result = self::_getHappyPokerByKaiJiangTong($jsonObj);
                break;
            case 17: //北京PK拾 02 07 03 10 01 05 06 04 08 09
            case 26: //飞艇 02 07 03 10 01 05 06 04 08 09
                $result = self::_getPk10ByKaiJiangTong($jsonObj);
                break;
            //======================Data comes from: stone===========================
            case 21: //六合彩 彩票控
                $result = self::_getFromLhcKjt($jsonObj);
                break;
            case 22: //双色球 彩票控
                $result = self::_getFromSsqKjt($jsonObj);
                break;
            case 23: //幸运28 彩票控
                $result = self::_getFromXy28Kjt($jsonObj);
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 彩票控处理时时彩数据   正确格式：20140116-66
     * @param $jsonObj object 对像
     * @return array   返回处理好的数据
     * author: snow
     */
    private static function _manipulationToXJSSC($jsonObj)
    {
        $result = [];
        foreach($jsonObj as $key => $val){
            $tmpNumber = str_replace(',','',$val->number);
            $pattern = "/^\d{5}$/";
            if(!preg_match($pattern,$tmpNumber)){
                return drawSources::failed(25, "号码：contents={$tmpNumber}");
            };
            if (!preg_match('/^20\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $val->dateline)) {
                return drawSources::failed(25, "时间：{$val->dateline}");
            }
            //>>验证并处理奖期
            if(!preg_match('/^\d{9,11}$/',$key)){
                return drawSources::failed(25, "奖期：{$key}");
            }
            $temp = "00" . substr($key,8);
            $isueTmp = substr($key,0,8) . "-" . substr($temp,strlen($temp) - 2);
            $tmpopenTime = $val->dateline;
            $result[$isueTmp] = ['issue' => $isueTmp, 'number' => $tmpNumber, 'openTime' => $tmpopenTime];
        }

        return $result;
    }

    /**
     * 彩票控处理时时彩数据   正确格式：20140116-66 96513
     * @param $jsonObj object 对像
     * @return array   返回处理好的数据
     * author: snow
     */
    private static function _manipulationToShiShiCai($jsonObj)
    {
        $result = [];
        foreach($jsonObj as $key => $val){
            $tmpNumber = str_replace(',','',$val->number);
            $pattern = "/^\d{5}$/";
            if(!preg_match($pattern,$tmpNumber)){
                return drawSources::failed(25, "号码：contents={$tmpNumber}");
            };
            if (!preg_match('/^20\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $val->dateline)) {
                return drawSources::failed(25, "时间：{$val->dateline}");
            }
            //>>验证并处理奖期
            if(!preg_match('/^\d{9,11}$/',$key)){
                return drawSources::failed(25, "奖期：{$key}");
            }
            $temp = "00" . substr($key,8);
            $isueTmp = substr($key,0,8) . "-" . substr($temp,strlen($temp) - 3);
            $tmpopenTime = $val->dateline;
            $result[$isueTmp] = ['issue' => $isueTmp, 'number' => $tmpNumber, 'openTime' => $tmpopenTime];
        }

        return $result;
    }

    /**
     * 彩票控处理11选5数据  正确格式：20150918-046 06 04 10 11 01
     * @param $jsonObj object 对像
     * @return array   返回处理好的数据
     * @throws exception2
     * author: snow
     */
    private static function _manipulationShiYiXuanWu($jsonObj,$flag = false)
    {
        $result = [];
        foreach($jsonObj as $key => $val){
            //>>检查并验证开奖号码
            $tmpNumber = str_replace(',',' ',trim($val->number));
            $pattern = "/^(\d{2}\s{1}){4}\d{2}$/";
            if(!preg_match($pattern,$tmpNumber)){
                return drawSources::failed(25, "号码：contents={$tmpNumber}");
            };
            //>>时间不做验证.
//            if (!preg_match('/^20\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $val->dateline)) {
//                return drawSources::failed(25, "时间：{$val->dateline}");
//            }
            if($flag === true){
                //>>年份没有以20.开头需要加上
                $key = 20 . $key;
            }
            //>>验证并处理奖期
            $temp = "00" . substr($key,8);
            $isueTmp = substr($key,0,8) . "-" . substr($temp,strlen($temp) - 3);
            $tmpopenTime = $val->dateline;
            $result[$isueTmp] = ['issue' => $isueTmp, 'number' => $tmpNumber, 'openTime' => $tmpopenTime];
        }
        return $result;
    }
    /**
     * 彩票控处理福彩3d数据   正确格式：2015258  227
     * @param $jsonObj object 对像
     * @return array   返回处理好的数据
     * author: snow
     */
    private static function _manipulationFuCaiSanD($jsonObj)
    {
        $result = [];
        foreach($jsonObj as $key => $val){
            $tmpNumber = str_replace(',','',trim($val->number));
            $pattern = "/^\d{3}$/";
            if(!preg_match($pattern,$tmpNumber)){
                return drawSources::failed(25, "号码：contents={$tmpNumber}");
            };
            //>>时间不做处理.
//            if (!preg_match('/^20\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $val->dateline)) {
//                return drawSources::failed(25, "时间：{$val->dateline}");
//            }
//            //>>验证并处理奖期
            if(!preg_match('/^\d{7}$/',$key)){
                return drawSources::failed(25, "奖期：{$key}");
            }
            $tmpopenTime = $val->dateline;
            $result[$key] = ['issue' => $key, 'number' => $tmpNumber, 'openTime' => $tmpopenTime];
        }
        return $result;
    }
    /**
     * 彩票控处理体彩排3排5数据  P3P5 正确格式：2016016 12345
     * @param $jsonObj object 对像
     * @return array   返回处理好的数据
     * author: snow
     */
    private static function _manipulationTiCaiPSanPWu($jsonObj)
    {
        $result = [];
        foreach($jsonObj as $key => $val){
            $tmpNumber = str_replace(',','',trim($val->number));
            //>>验证号码
            $pattern = '/^\d{5}$/Ui';
            if(!preg_match($pattern,$tmpNumber)){
                return drawSources::failed(25, "号码：contents={$tmpNumber}");
            };
            //>>时间不做处理
//            if (!preg_match('/^20\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $val->dateline)) {
//                return drawSources::failed(25, "时间：{$val->dateline}");
//            }
//            //>>验证并处理奖期
            if(!preg_match('/^\d{7}$/',$key)){
                return drawSources::failed(25, "奖期：{$key}");
            }
            $tmpopenTime = $val->dateline;
            $result[$key] = ['issue' => $key, 'number' => $tmpNumber, 'openTime' => $tmpopenTime];
        }
        return $result;
    }

    /**
     * 快3 彩票控
     * @param $jsonObj
     * @return array
     * author: the rock
     */
    static private function _getKuai3ByKaiJiangTong($jsonObj)
    {
        $result = array();
        foreach ($jsonObj as $key => $item) {
            if (strlen($key) == 9) {
                if (!preg_match('`\d{9}$`', $key)) return drawSources::failed(25, "奖期：{$item}");
                $tmpIssue = '20' . substr($key, 0, 6) . "-" . substr($key, 6, 3);
            } else {
                if (!preg_match('`\d{9}$`', $key)) return drawSources::failed(25, "奖期：{$item}");
                $tmpIssue = substr($key, 0, 8) . "-" . substr($key, 8, 3);
            }
            $tmpNumber = implode('', explode(',', $item->number));
            if (!preg_match('`^[1-6]{3}$`Ui', $tmpNumber)) return drawSources::failed(25, "号码：{$tmpNumber}");
            $tmpopenTime = $item->dateline;
            $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
        }
        return $result;
    }

    /**
     * 快乐扑克 彩票控
     * @param $jsonObj
     * @return array
     * author: the rock
     */
    static private function _getHappyPokerByKaiJiangTong($jsonObj)
    {
        $colors = array('1' => 's', '4' => 'd', '3' => 'c', '2' => 'h');
        $codes = array('01' => 'A', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => 'T', '11' => 'J', '12' => 'Q', '13' => 'K');
        $result = array();
        foreach ($jsonObj as $key => $item) {
            if (!preg_match('`\d{10}$`', $key)) return drawSources::failed(25, "奖期：{$item}");
            $tmpIssue = substr($key, 0, 8) . '-' . str_pad(substr($key, 8, 2), 3, 0, STR_PAD_LEFT);
            $tmpNumberBefore = explode(',', $item->number);
            $tmpNumberAfter = $tmpNumberReverse = array();
            foreach ($tmpNumberBefore AS $k => $val) $tmpNumberReverse[] = substr($val, 1, 2) . substr($val, 0, 1);
            sort($tmpNumberReverse);
            foreach ($tmpNumberReverse AS $reverseNumber) $tmpNumberAfter[] = $codes[substr($reverseNumber, 0, 2)] . $colors[substr($reverseNumber, 2, 1)];
            $tmpNumber = implode(' ', $tmpNumberAfter);
            if (!preg_match('@^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$@Ui', $tmpNumber)) return drawSources::failed(31, "抓取号码不正确：{$tmpNumber}");
            $tmpopenTime = $item->dateline;
            $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
        }
        return $result;
    }

    /**
     * pk10 彩票控
     * @param $jsonObj
     * @return array
     * author: the rock
     */
    static private function _getPk10ByKaiJiangTong($jsonObj)
    {
        $result = array();
        foreach ($jsonObj as $key => $item) {
            if (!preg_match('`\d{6}$`', $key)) return drawSources::failed(25, "奖期：{$item}");
            $tmpNumber = implode(' ', explode(',', $item->number));
            if (!preg_match('`^([01]\d\s){9}[01]\d$`Ui', $tmpNumber)) return drawSources::failed(25, "号码：{$tmpNumber}");
            $tmpopenTime = $item->dateline;
            $result[$key] = array('issue' => $key, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
        }
        return $result;
    }

    /**
     * 幸运28彩票控
     * @param object $jsonObj
     * @return array
     * author: stone
     */
    static public function _getFromXy28Kjt($jsonObj)
    {
        $result = array();
        foreach ($jsonObj as $k=>$v) {
            if (!preg_match('`^\d{6}$`', $k)) {
                return drawSources::failed(25, "奖期：{$k}");
            }
            if (!preg_match('`^([0-9]{2},){20}[0-9]{2}$`Ui', $v->number)) {
                return drawSources::failed(25, "号码：{$v->number}");
            }

            $codesArr = explode(',', $v->number);
            array_pop($codesArr);//去除飞盘位
            sort($codesArr);//确保一定是从小到大
            $tmpNumber=implode(' ',$codesArr);
            $one = substr(array_sum(array_slice($codesArr, 0, 6)), -1);
            $tow = substr(array_sum(array_slice($codesArr, 6, 6)), -1);
            $three = substr(array_sum(array_slice($codesArr, 12, 6)), -1);

            $num = $one . $tow . $three;
            $result[$k] = array('issue' => $k, 'number' => $num,'original_number' => $tmpNumber,  'openTime' => $v->dateline);
        }
        return $result;
    }

    /**
     * 六合彩票控
     * @param object $jsonObj
     * @return array
     * author: stone
     */
    static public function _getFromLhcKjt($jsonObj)
    {
        $result = array();
        foreach ($jsonObj as $k => $v) {
            $arr = explode(',', $v->number);
            $arr = array_map(function ($v) {
                return intval($v);
            }, $arr);
            $tmpSum = implode(' ', $arr);

            if (!preg_match('`^\d{7}$`Ui', $k)) {
                // 严格检查并修正
                return drawSources::failed(25, "号码：{$tmpSum}");
            }
            $tmpIssue=$k;
            if (!preg_match('`^([0-9]{1,2}\s){6}[0-9]{1,2}$`Ui', $tmpSum)) {
                // 严格检查并修正
                return drawSources::failed(25, "号码：{$tmpSum}");
            }
            $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpSum, 'openTime' => date('Y-m-d H:i:s', strtotime($v->dateline)));
        }
        return $result;
    }

    /**
     * 双色球彩票控
     * @param object $jsonObj
     * @return array
     * author: stone
     */
    static public function _getFromSsqKjt($jsonObj)
    {
        $result = array();
        foreach ($jsonObj as $k =>$v) {
            $tmpSum = $str = preg_replace('/,/', ' ', $v->number);
            if (!preg_match('`^([0-9]{2}\s){6}[0-9]{2}$`Ui', $tmpSum)) {
                // 严格检查并修正
                return drawSources::failed(25, "号码：{$tmpSum}");
            }
            if (!preg_match('`^\d{7}$`Ui', $k)) {
                // 严格检查并修正
                return drawSources::failed(25, "号码：{$tmpSum}");
            }
            $tmpIssue=$k;
            $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpSum, 'openTime' => date('Y-m-d H:i:s', strtotime($v->dateline)));
        }
        return $result;
    }

    /**
     * 99统计QQ在线人数
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    static public function _getFrom9ckj($lottery, $url, $expectedIssue = 0)
    {
        $result = array();
        self::$wget->setInCharset('gb2312');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }
        $jsonObj = json_decode($contents);
        if ($jsonObj == null) {
            return drawSources::failed(31, "取内容出错：" . $contents);
        }

        foreach ($jsonObj as $v) {
// object(stdClass)#14 (3) {
//   ["onlinetime"]=>
//   string(19) "2017-08-08 15:43:00"
//   ["onlinenumber"]=>
//   int(268732578)
//   ["onlinechange"]=>
//   int(27711)
// }
            $tmpDay = date('Ymd', strtotime($v->onlinetime));
            $hours = date('H', strtotime($v->onlinetime));
            $mins = date('i', strtotime($v->onlinetime));
            $n = $hours * 60 + $mins;
            $n4 = str_pad($n, 4, 0, STR_PAD_LEFT);
            $tmpIssue = $tmpDay . '-' . $n4;

            $tmpSum = substr(array_sum(str_split($v->onlinenumber)), -1);
            $tmpNumber = $tmpSum . substr($v->onlinenumber, -4);
            // 严格检查并修正
            if (!preg_match('`^[0-9]{5}$`Ui', $tmpNumber)) {
                return drawSources::failed(25, "号码：{$tmpNumber}");
            }

            $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => date('Y-m-d H:i:s', strtotime($v->onlinetime)));
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 365
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    static public function _getFrom365($lottery, $url, $expectedIssue = 0)
    {
        $result = array();
        self::$wget->setInCharset('gb2312');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }
        $jsonObj = json_decode($contents);
        if ($jsonObj == null) {
            return drawSources::failed(31, "取内容出错：" . $contents);
        }
        foreach ($jsonObj as $v) {
// object(stdClass)#14 (3) {
//   [issue] => 20171126-0793
//   [opendate] => 2017-11-26 13:13:00
//   [code] => 4,7,9,0,1
//   [lotterycode] => TXFFC
//   [cur_online] => 253077901
//   [max_online] => 272203829
//   [officialissue] => 20171126-0793
// }
            $tmpDay = date('Ymd', strtotime($v->opendate));
            $hours = date('H', strtotime($v->opendate));
            $mins = date('i', strtotime($v->opendate));
            $n = $hours * 60 + $mins;
            $n4 = str_pad($n, 4, 0, STR_PAD_LEFT);
            $tmpIssue = $tmpDay . '-' . $n4;

            $tmpSum = substr(array_sum(str_split($v->cur_online)), -1);
            $tmpNumber = $tmpSum . substr($v->cur_online, -4);
            // 严格检查并修正
            if (!preg_match('`^[0-9]{5}$`Ui', $tmpNumber)) {
                return drawSources::failed(25, "号码：{$tmpNumber}");
            }

            $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => date('Y-m-d H:i:s', strtotime($v->opendate)));
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 校验官方开奖时间是否比截止购彩时间早
     * @author      Davy
     * @param arr $issue 当前奖期end_sale_time
     * @param str $openTime 官方开奖时间,格式必须是    Y-m-d H:i:s
     * ex:2015-05-08 22:09:50 如果来源字串无秒则自行添加00:00:00
     *
     * @return false 表示校验无效;true 表示通过无问题;
     * 检验出官方开奖时间比截止购彩时间早则返回 400 到上层处理
     */
    static public function checkOpenTime($issueEndSaleTime, $openTime)
    {
        //校验参数
        if (empty($issueEndSaleTime) || empty($openTime)) {
            echo "checkOpenTime参数丢失:$issueEndSaleTime:$openTime";
            return false;
        }

        // 校验两个时间参数的合法性
        if (!preg_match('/\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2}:\d{2}/i', $openTime) || !preg_match('/\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2}:\d{2}/i', $issueEndSaleTime)) {
            echo "checkOpenTime时间参数格式非法:$openTime:$issueEndSaleTime\n";
            return false;
        }
        //时间戳
        $openTimeTS = strtotime($openTime);
        $saleEndTimeTS = strtotime($issueEndSaleTime);

        if (abs($openTimeTS - $saleEndTimeTS) > self::CHECK_OPEN_TIME_LIMIT) {
            echo "_checkOpenTime时间参数差值非法:$openTime:$issueEndSaleTime\n";
            return false;
        }

        //比较逻辑
        if ($saleEndTimeTS > $openTimeTS) {
            //问题处理优先级较高
            return 400;
        }

        return true; //正确的时间
    }

    /**
     * 彩经网 http://shishicai.cjcp.com.cn/xinjiang/kaijiang/ 仅支持IE,fb不作用
     * @author
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private static function _getFromCjcpComCn($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('gb2312');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        if ((int)$lottery['lottery_id'] == 12) {
            $pattern = '`<tr>\s*<td>(201\d{8})期</td>\s*<td>[^>]*</td>\s*<td><div class="kjjg_hm_bg">\s*<div class="hm_bg">(\d)</div>\s*<div class="hm_bg">(\d)</div>\s*<div class="hm_bg">(\d)</div>\s*</div>\s*</td>`Uims';
        } else {
            $pattern = '`<tr>\s*<td>(201\d{8})期</td>\s*<td>[^>]*</td>\s*<td><div class="kjjg_hm_bg">\s*<div class="hm_bg">(\d)</div>\s*<div class="hm_bg">(\d)</div>\s*<div class="hm_bg">(\d)</div>\s*<div class="hm_bg">(\d)</div>\s*<div class="hm_bg">(\d)</div>\s*</div>\s*</td>`Uims';
        }
        preg_match_all($pattern, $contents, $matches);

        if (count($matches[1]) < 1) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '4':
                /**
                 *  XJSSC 正确格式：20140116-66 96513
                 * 20140116067
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^201\d{8}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v, 0, 8) . "-" . substr($v, 9, 2);
                    $tmpNumber = $matches[2][$k] . $matches[3][$k] . $matches[4][$k] . $matches[5][$k] . $matches[6][$k];
                    if (!preg_match('`^\d{5}$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '8':
                /**
                 * TJSSC 正确格式：20140116-075   83285
                 * 20140116074
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^201\d{8}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v, 0, 8) . "-" . substr($v, 8, 3);
                    $tmpNumber = $matches[2][$k] . $matches[3][$k] . $matches[4][$k] . $matches[5][$k] . $matches[6][$k];
                    if (!preg_match('`^\d{5}$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '12':
                /**
                 *  江苏快三 正确格式：20150330-20 112
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^201\d{8}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v, 0, 8) . "-" . substr($v, 8, 3);
                    $tmpNumber = $matches[2][$k] . $matches[3][$k] . $matches[4][$k];
                    if (!preg_match('`^[1-6]{3}$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 专业开奖源API
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    static public function _getFromLotteryAPI($lottery, $url, $expectedIssue = 0)
    {
        $result = array();
        self::$wget->setInCharset('gb2312');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }
        $jsonObj = json_decode($contents);
        if ($jsonObj == null) {
            return drawSources::failed(31, "取内容出错：" . $contents);
        }

        switch ($lottery['lottery_id']) {
            case 3:
                /**
                 *  黑龙江时时彩 奖期格式：0184558
                 */
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{7}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v->expect;
                    $tmpNumber = implode('', explode(',', $v->opencode));
                    // 严格检查并修正
                    if (!preg_match('`^[0-9]{5}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 4:
                /**
                 *  新疆时时彩 正确格式：20140116-66 96513
                 * 20140116067
                 */
                foreach ($jsonObj->data as $k => $v) {
                    if (!preg_match('`\d{11}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v->expect, 0, 8) . "-" . substr($v->expect, 9, 2);
                    $tmpNumber = implode('', explode(',', $v->opencode));
                    if (!preg_match('`^[0-9]{5}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 1:
            case 8:
                /**
                 *  重庆时时彩   天津时时彩
                 */
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{11}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v->expect, 0, 8) . "-" . substr($v->expect, 8, 3);
                    $tmpNumber = implode('', explode(',', $v->opencode));
                    // 严格检查并修正
                    if (!preg_match('`^[0-9]{5}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 2:
            case 5:
            case 6:
            case 7:
                /**
                 *  广东11选5
                 *  江西11选5
                 *  山东11选5
                 */
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{10}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v->expect, 0, 8) . "-0" . substr($v->expect, 8, 3);
                    $tmpNumber = implode(' ', explode(',', $v->opencode));
                    // 严格检查并修正
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 9:
                /**
                 * 福彩3D
                 * 正确格式：2015258  227
                 */
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{7}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v->expect;
                    $tmpNumber = implode('', explode(',', $v->opencode));
                    // 严格检查并修正
                    if (!preg_match('`^\d{3}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 10:
                /**
                 *  P3P5 正确格式：2016016 12345
                 */
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{7}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v->expect;
                    $tmpNumber = implode('', explode(',', $v->opencode));
                    // 严格检查并修正
                    if (!preg_match('`^[0-9]{5}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 12:
            case 19:
            case 20:
                /**
                 *  江苏快三 正确格式：20150330-20 112
                 */
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{11}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v->expect, 0, 8) . "-" . substr($v->expect, 8, 3);
                    $tmpNumber = implode('', explode(',', $v->opencode));
                    // 严格检查并修正
                    if (!preg_match('`^[1-6]{3}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 14:
                /**
                 *  快乐扑克 正确格式：20160311-036 3h 6s 6d
                 * $colors = array('1' => 's', '4' => 'd', '3' => 'c', '2' => 'h');
                 * $codes = array('01' => 'A', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => 'T', '11' => 'J', '12' => 'Q', '13' => 'K');
                 */
                $colors = array('1' => 's', '4' => 'd', '3' => 'c', '2' => 'h');
                $codes = array('01' => 'A', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => 'T', '11' => 'J', '12' => 'Q', '13' => 'K');
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{10}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }

                    $tmpIssue = substr($v->expect, 0, 8) . "-0" . substr($v->expect, 8, 2);
                    $tmpNumberBefore = explode(',', $v->opencode);
                    $tmpNumberAfter = $tmpNumberReverse = array();
                    foreach ($tmpNumberBefore AS $key => $value) {
                        $codeColor = substr($value, 0, 1);
                        $originCode = substr($value, 1, 2);
                        $tmpNumberReverse[] = $originCode . $codeColor;
                    }
                    sort($tmpNumberReverse);
                    foreach ($tmpNumberReverse AS $reverseNumber) {
                        $code = $codes[substr($reverseNumber, 0, 2)];
                        $codeColor = $colors[substr($reverseNumber, 2, 1)];
                        $tmpNumberAfter[] = $code . $codeColor;
                    }

                    $tmpNumber = implode(' ', $tmpNumberAfter);
                    if (!preg_match('@^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$@Ui', $tmpNumber)) {
                        return drawSources::failed(31, "抓取号码不正确：{$tmpNumber}");
                    }
                    $tmpopenTime = $v->opentime;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 17:
            case 26:
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{6}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpNumber = implode(' ', explode(',', $v->opencode));
                    // 严格检查并修正
                    if (!preg_match('`^([01]\d\s){9}[01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = $v->opentime;
                    $result[$v->expect] = array('issue' => $v->expect, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 18://"opencode":"07,10,12,14,16,21,23,26,32,33,35,43,47,49,52,55,57,59,74,79"
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`\d{11}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmp = explode(',', $v->opencode);
                    sort($tmp);

                    $original_number = implode(' ', $tmp);
                    // 严格检查并修正
                    if (!preg_match('`^([0-8]\d\s){19}[0-8]\d$`U', $original_number)) {
                        return drawSources::failed(25, "号码：{$original_number}");
                    }

                    //计算最终开奖号码
                    $code1 = substr($tmp[0] + $tmp[1] + $tmp[2] + $tmp[3], -1);
                    $code2 = substr($tmp[4] + $tmp[5] + $tmp[6] + $tmp[7], -1);
                    $code3 = substr($tmp[8] + $tmp[9] + $tmp[10] + $tmp[11], -1);
                    $code4 = substr($tmp[12] + $tmp[13] + $tmp[14] + $tmp[15], -1);
                    $code5 = substr($tmp[16] + $tmp[17] + $tmp[18] + $tmp[19], -1);

                    $openCode = $code1 . $code2 . $code3 . $code4 . $code5;

                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = $v->opentime;
                    $result[$v->expect] = array('issue' => $v->expect, 'number' => $openCode, 'original_number' => $original_number, 'openTime' => $tmpopenTime);
                }
                break;
            case 21:
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`^\d{7}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    if (!preg_match('@^([0-4]\d,){5}[0-4]\d\+[0-4]\d$@', $v->opencode)) {
                        return drawSources::failed(25, "号码：{$v->opencode}");
                    }

                    $tmpCode = str_replace('+', ',', $v->opencode);
                    $tmpCodeArr = explode(',', $tmpCode);
                    array_walk_recursive($tmpCodeArr, create_function('&$value, $key', '$value = intval($value);'));
                    $tmpNumber = implode(' ', $tmpCodeArr);

                    $result[$v->expect] = array('issue' => $v->expect, 'number' => $tmpNumber, 'openTime' => $v->opentime);
                }
                break;
            case 22:
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`^\d{7}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    if (!preg_match('@^([0-3]\d,){5}[0-3]\d\+[0-1]\d$@', $v->opencode)) {
                        return drawSources::failed(25, "号码：{$v->opencode}");
                    }

                    $tmpCode = str_replace('+', ',', $v->opencode);
                    $tmpCodeArr = explode(',', $tmpCode);
                    $tmpNumber = implode(' ', $tmpCodeArr);

                    $result[$v->expect] = array('issue' => $v->expect, 'number' => $tmpNumber, 'openTime' => $v->opentime);
                }
                break;
            case 23:
                foreach ($jsonObj->data as $v) {
                    if (!preg_match('`^\d{6}$`', $v->expect)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    if (!preg_match('`^(\d{2},){19}\d{2}\+\d{2}$`', $v->opencode)) {
                        return drawSources::failed(25, "号码：{$v->opencode}");
                    }

                    $tmpCodeArr = explode('+', $v->opencode);
                    $tmpNumber = str_replace(',', ' ', $tmpCodeArr[0]);
                    $codesArr = explode(' ', $tmpNumber);
                    sort($codesArr);//确保一定是从小到大
                    $one = substr(array_sum(array_slice($codesArr, 0, 6)), -1);
                    $tow = substr(array_sum(array_slice($codesArr, 6, 6)), -1);
                    $three = substr(array_sum(array_slice($codesArr, 12, 6)), -1);

                    $num = $one . $tow . $three;
                    $result[$v->expect] = array('issue' => $v->expect, 'number' => $num, 'original_number' => $tmpNumber, 'openTime' => $v->opentime);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
        }

        return drawSources::getNumber($result, $expectedIssue);
    }


    /**
     * 网易163彩票
     * @author    Davy,nyjah
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private static function _getFrom163Com($lottery, $url, $expectedIssue = 0)
    {
        $result = array();
        self::$wget->setInCharset('utf-8');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        switch ($lottery['lottery_id']) {
            case 1:
                $jsonObj = json_decode($contents);
                if ($jsonObj == null) {
                    return drawSources::failed(31, "取内容出错：" . $contents);
                }

                /**
                 *  重庆时时彩 正确格式：20150918-046 26226
                 */
                foreach ($jsonObj->awardNumberInfoList as $k => $v) {
                    if (!preg_match('`\d{9}$`', $v->period)) {
                        return drawSources::failed(31, "奖期：{$v}");
                    }
                    $tmpIssue = '20' . substr($v->period, 0, 6) . "-" . substr($v->period, 6, 3);
                    $tmpNumber = implode('', explode(' ', $v->winningNumber));
                    // 严格检查并修正奖期
                    if (!preg_match('`^[0-9]{5}$`Ui', $tmpNumber)) {
                        return drawSources::failed(31, "号码：{$tmpNumber}");
                    }

                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case 2:
                $jsonObj = json_decode($contents);
                if ($jsonObj == null) {
                    return drawSources::failed(31, "取内容出错：" . $contents);
                }

                /**
                 *  山东11选5 正确格式：20150918-046 26226
                 */
                foreach ($jsonObj->awardNumberInfoList as $k => $v) {
                    if (!preg_match('`\d{8}$`', $v->period)) {
                        return drawSources::failed(31, "奖期：{$v}");
                    }
                    $tmpIssue = '20' . substr($v->period, 0, 6) . "-0" . substr($v->period, 6, 2);
                    $tmpNumber = implode(' ', explode(' ', $v->winningNumber));
                    // 严格检查并修正奖期
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(31, "号码：{$tmpNumber}");
                    }

                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case 6:
                $jsonObj = json_decode($contents);
                if ($jsonObj == null) {
                    return drawSources::failed(31, "取内容出错：" . $contents);
                }

                /**
                 *  江西11选5 正确格式：20150918-046 26226
                 */
                foreach ($jsonObj->awardNumberInfoList as $k => $v) {
                    if (!preg_match('`\d{8}$`', $v->period)) {
                        return drawSources::failed(31, "奖期：{$v}");
                    }
                    $tmpIssue = '20' . substr($v->period, 0, 6) . "-0" . substr($v->period, 6, 2);
                    $tmpNumber = implode(' ', explode(' ', $v->winningNumber));
                    // 严格检查并修正奖期
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(31, "号码：{$tmpNumber}");
                    }

                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case 7:
                $jsonObj = json_decode($contents);
                if ($jsonObj == null) {
                    return drawSources::failed(31, "取内容出错：" . $contents);
                }

                /**
                 *  广东11选5 正确格式：20150918-046 26226
                 */
                foreach ($jsonObj->awardNumberInfoList as $k => $v) {
                    if (!preg_match('`\d{8}$`', $v->period)) {
                        return drawSources::failed(31, "奖期：{$v}");
                    }
                    $tmpIssue = '20' . substr($v->period, 0, 6) . "-0" . substr($v->period, 6, 2);
                    $tmpNumber = implode(' ', explode(' ', $v->winningNumber));
                    // 严格检查并修正奖期
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(31, "号码：{$tmpNumber}");
                    }

                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case 9:
                $jsonObj = json_decode($contents);
                if ($jsonObj == null) {
                    return drawSources::failed(31, "取内容出错：" . $contents);
                }

                /**
                 *  福彩3D 正确格式：2015258  227
                 */
                foreach ($jsonObj->awardNumberInfoList as $k => $v) {
                    if (!preg_match('`\d{7}$`', $v->period)) {
                        return drawSources::failed(31, "奖期：{$v}");
                    }
                    $tmpIssue = $v->period;
                    $tmpNumber = implode('', explode(' ', $v->winningNumber));
                    // 严格检查并修正奖期
                    if (!preg_match('`^\d{3}$`', $tmpNumber)) {
                        return drawSources::failed(31, "号码：{$tmpNumber}");
                    }

                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case 12:
                $jsonObj = json_decode($contents);
                if ($jsonObj == null) {
                    return drawSources::failed(31, "取内容出错：" . $contents);
                }

                /**
                 *  江苏快三 正确格式：20150330-020 112
                 */
                foreach ($jsonObj as $k => $v) {
                    if (!preg_match('`\d{9}$`', $v->period)) {
                        return drawSources::failed(31, "奖期：{$v}");
                    }
                    $tmpIssue = '20' . substr($v->period, 0, 6) . "-" . substr($v->period, 6, 3);
                    $tmpNumber = implode('', explode(' ', $v->winningNumber));
                    // 严格检查并修正奖期
                    if (!preg_match('`^[1-6]{3}$`Ui', $tmpNumber)) {
                        return drawSources::failed(31, "号码：{$tmpNumber}");
                    }
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $day = (strlen($v->awardTime->day) == 1) ? '0' . $v->awardTime->day : $v->awardTime->day;
                    $date = date('Y-m-') . $day;
                    $tmpopenTime = $date . ' ' . $v->awardTime->hours . ':' . $v->awardTime->minutes . ':' . $v->awardTime->seconds;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            case 14:
                $startPosition = strpos($contents, 'start');
                $endPosition = strpos($contents, 'haomaTj');
                $length = $endPosition - $startPosition;
                $contents = substr($contents, $startPosition, $length);
                //<td class="start" data-period="15062608" data-award="303 409 411">08</td>
                $pattern = '@<td\s+class="start"\s+data-period="\d+"\s+data-award="((\s*\d{3}\s*){3})">(\d{2})</td>@Uims';
                preg_match_all($pattern, $contents, $matches);

                if (empty($matches[0])) {
                    return drawSources::failed(33, '没有奖期开奖或者获取匹配内容失败：content：' . $contents);
                }
                /**
                 *  快乐扑克 奖期格式：20150630-020
                 *  $matches[3] 奖期集合
                 *  $matches[1] 开奖号集合
                 */
                $tmpResult = array_combine($matches[3], $matches[1]);
                ksort($tmpResult);

                $colors = array('1' => 's', '4' => 'd', '3' => 'c', '2' => 'h');
                $codes = array('01' => 'A', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => 'T', '11' => 'J', '12' => 'Q', '13' => 'K');
                foreach ($tmpResult as $k => $v) {
                    $tmpNumber = '';
                    $tmpIssue = date("Ymd") . '-0' . $k;
                    $tmpCodes = explode(' ', $v);
                    foreach ($tmpCodes as $vv) {
                        $tmpNumber .= $codes[substr($vv, 1)] . $colors[$vv[0]] . ' ';
                    }
                    $tmpNumber = trim($tmpNumber);

                    //严格检查奖期和开奖号码
                    if (!preg_match('@^\d{8}-\d{3}$@', $tmpIssue)) {
                        return drawSources::failed(31, "抓取奖期不正确：{$tmpIssue}");
                    }
                    if (!preg_match('@^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$@Ui', $tmpNumber)) {
                        return drawSources::failed(31, "抓取号码不正确：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                    krsort($result);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 360彩票
     * @author    nyjah
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private static function _getFromCp360Cn($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        $startPosition = strpos($contents, 'his-tab');
        $endPosition = strpos($contents, 'width:65px');
        $length = $endPosition - $startPosition;
        $contents = substr($contents, $startPosition, $length);

        $pattern = "@<td\s+class='gray'>(\d{2})</td>\s*<td\s+class='poker-kj-list'>\s*<span\s+class=\"poker-kj-num.*\">\s*<i\s+class=\"ico-suit\s+ico\-(\w{4,5})\">\s*</i>\s*<em\s+class=\"num\">(\d{1,2}|[AJQK])</em>\s*</span>\s*<span\s+class=\"poker-kj-num.*\">\s*<i\s+class=\"ico-suit\s+ico\-(\w{4,5})\">\s*</i>\s*<em\s+class=\"num\">(\d{1,2}|[AJQK])</em>\s*</span>\s*<span\s+class=\"poker-kj-num.*\">\s*<i\s+class=\"ico-suit\s+ico-(\w{4,5})\">\s*</i>\s*<em\s+class=\"num\">(\d{1,2}|[AJQK])</em>\s*</span>\s*</td>@Uims";
        preg_match_all($pattern, $contents, $matches);

        if (empty($matches[0])) {
            return drawSources::failed(33, '没有奖期开奖或者获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        switch ($lottery['lottery_id']) {
            case '14':
                /**
                 *  快乐扑克 奖期格式：20150630-020
                 *  $matches[1] 奖期集合
                 *  $matches[2] 百位花色集合
                 *  $matches[3] 百位号码集合
                 *  $matches[4] 十位花色集合
                 *  $matches[5] 十位号码集合
                 *  $matches[6] 个位花色集合
                 *  $matches[7] 个位号码集合
                 */
                $colors = array('heit' => 's', 'fangp' => 'd', 'heim' => 'c', 'hongt' => 'h');
                for ($i = 0, $len = count($matches[1]); $i < $len; $i++) {
                    $tmpIssue = date("Ymd") . '-0' . $matches[1][$i];

                    $tmpOpenCode1 = $matches[3][$i] == 10 ? 'T' . $colors[$matches[2][$i]] : $matches[3][$i] . $colors[$matches[2][$i]];
                    $tmpOpenCode2 = $matches[5][$i] == 10 ? 'T' . $colors[$matches[4][$i]] : $matches[5][$i] . $colors[$matches[4][$i]];
                    $tmpOpenCode3 = $matches[7][$i] == 10 ? 'T' . $colors[$matches[6][$i]] : $matches[7][$i] . $colors[$matches[6][$i]];
                    $tmpNumber = $tmpOpenCode1 . ' ' . $tmpOpenCode2 . ' ' . $tmpOpenCode3;
                    // 严格检查并修正奖期
                    if (!preg_match('@^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$@Ui', $tmpNumber)) {
                        return drawSources::failed(25, "抓取号码不正确：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                    krsort($result);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 168Kai
     * @author    hack
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private static function _getFrom168Kai($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        if (!$contents = drawSources::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        $contents_json = json_decode($contents, 1);
        $contents = ($contents_json && isset($contents_json['list'])) ? $contents_json['list'] : '';
        if (!$contents) {
            return drawSources::failed(33, '没有奖期开奖或者获取匹配内容失败：content：' . $contents);
        }
        unset($contents_json);

        $result = array();
        switch ($lottery['lottery_id']) {
            case '14':
                /**
                 *  快乐扑克 奖期格式：20150630-020
                 *  $contents[i][c_t] 奖期集合
                 *  $contents[i][c_r] 奖期内容
                 */
                foreach ($contents as $value) {
                    $tmpIssue = date('Ymd') . '-0' . substr($value['c_t'], 6);
                    $tmpNumber = array();
                    foreach (explode(',', $value['c_r']) as $val) {
                        $tmpNumber[] = str_replace(array('-th', '-fh', '-hh', '-xh', '01'), array('s', 'd', 'c', 'h', 'T'), strrev($val));
                    }
                    $tmpNumber = implode(' ', $tmpNumber);
                    // 严格检查并修正奖期
                    if (!preg_match('@^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$@Ui', $tmpNumber)) {
                        return drawSources::failed(25, "抓取号码不正确：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * BaiduLecai
     * @author    hack
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private static function _getFromBaiduLecai($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        preg_match('`var phaseData = (.+);`Ui', $contents, $matches);

        $contents = '';
        if ($matches && isset($matches[1]) && $matches[1]) {
            $contents_json = json_decode($matches[1], 1);
            if ($contents_json && isset($contents_json[date('Y-m-d')]) && $contents_json[date('Y-m-d')]) {
                $contents = $contents_json[date('Y-m-d')];
            }
        }

        if (!$contents) {
            return drawSources::failed(33, '没有奖期开奖或者获取匹配内容失败：content：' . $contents);
        }

        unset($matches, $contents_json);

        krsort($contents);
        $result = array();
        switch ($lottery['lottery_id']) {
            case '14':
                /**
                 *  快乐扑克 奖期格式：20150630-020
                 *  $key 奖期集合
                 *  $value.result.red 奖期内容
                 */
                foreach ($contents as $key => $value) {
                    $tmpIssue = date('Ymd') . '-0' . substr($key, 8);
                    $tmpNumber = array();
                    foreach ($value['result']['red'] as $val) {
                        $tmpNumber[] = str_replace(
                                array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13'), array('A', '2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K'), substr($val, 1, 2))
                            . str_replace(array('1', '2', '3', '4'), array('s', 'h', 'c', 'd'), substr($val, 0, 1));
                    }
                    $tmpNumber = implode(' ', $tmpNumber);
                    // 严格检查并修正奖期
                    if (!preg_match('@^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$@Ui', $tmpNumber)) {
                        return drawSources::failed(25, "抓取号码不正确：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $value['open_time']);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 广东体彩中心 http://www.gdlottery.cn/
     * @param <int> $lottery
     * @param <string> $url
     * @param <int> $expectedIssue
     * @return <array>
     */
    private static function _getFromGdlotteryCn($lottery, $url, $expectedIssue = 0)
    {
        $port = 80;
        $method = "POST";
        $referer = $cookie = '';
        $post = "callCount=1&page=/&httpSessionId=ADAEA9ECADDC6D91DC3A7D561FD9CA76&scriptSessionId=C8371569907FEE7A51C72A4857B36157705&c0-scriptName=lot&c0-methodName=getLot11x5&c0-id=0&batchId=672";
        self::$wget->setInCharset('utf-8');
        switch ($lottery['lottery_id']) {
            case '8':
                $url = "http://www.gdlottery.cn/dwr/call/plaincall/lot.getLot11x5.dwr";
                break;
        }
        if (!self::$wget->getContents('CURL', $url, $port, $method, $referer, $cookie, $post)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());

        if (strlen($contents) < 10) {
            return drawSources::failed(32, "取内容异常：contents=$contents");
        }

        $pattern = '`<div\s+class=\\\\"text_wrap\\\\">.*<div\s+class=\\\\"text_1\\\\">(\d{8})</div>.*<span\s+class=\\\\"lot11x5_blue\\\\">(\d{2})</span><span\s+class=\\\\"lot11x5_blue\\\\">(\d{2})</span><span\s+class=\\\\"lot11x5_blue\\\\">(\d{2})</span><span\s+class=\\\\"lot11x5_blue\\\\">(\d{2})</span><span\s+class=\\\\"lot11x5_blue\\\\">(\d{2})</span>`Uims';
        preg_match_all($pattern, $contents, $matches);
        if (count($matches[1]) < 2) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '8':
                /**
                 *  正确格式：10091801  05 10 06 02 03
                 * 10100828
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^\d{8}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    $tmpNumber = $matches[2][$k] . ' ' . $matches[3][$k] . ' ' . $matches[4][$k] . ' ' . $matches[5][$k] . ' ' . $matches[6][$k];
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 彩票二元网  http://www.cp2y.com/buy/?lid=10046
     * @param array $lottery
     * @param string $url
     * @param int $expectedIssue
     */
    private static function _getFromCp2yCom($lottery, $url, $expectedIssue = 0)
    {
        $iPort = 80;
        $sMethod = "GET";
        $referer = $cookie = $post = "";
        self::$wget->setInCharset('gb2312');
        switch ($lottery['lottery_id']) {
            case 5:
                $url = 'http://www.cp2y.com/buy/draw_number!.jsp?rc=0.03606850378205917&lid=10046&baseDir=../';
                break;
        }
        if (!self::$wget->getContents('CURL', $url, $iPort, $sMethod, $referer, $cookie, $post)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = self::$wget->getResponseBody();
        if (strlen($contents) < 100) {
            return drawSources::failed(32, '取内容异常：contents=' . $contents);
        }
        $pattern = "/\<td\s*align=\"center\"\s*bgcolor=\"#\w{6}\"\s*class=\"instant-lt\s*instant-ll\s*ft\">(\d{8})\<\/td>.*\<td\s*align=\"center\"\s*class=\"instant-lt\s*instant-ll\s*font-red\s*ft\"\s*bgcolor=\"#\w{6}\">(\d{2}\s\d{2}\s\d{2}\s\d{2}\s\d{2})\<\/td>/Uims";
        preg_match_all($pattern, $contents, $matches);

        if (count($matches[1]) < 2) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '5':
                foreach ($matches[1] as $k => $v) {
                    //正确格式 奖期：100709033	奖号：29144
                    $v = trim($v);
                    if (!preg_match('/^\d{8}$/', $v)) {
                        return drawSources::failed(25, '奖期' . $v);
                    }
                    $tmpIssue = $v;
                    if (!preg_match('/^(\d{2})\s(\d{2})\s(\d{2})\s(\d{2})\s(\d{2})$/', $matches[2][$k])) {
                        return drawSources::failed(25, '号码' . $matches[2][$k]);
                    }
                    $tmpNumber = $matches[2][$k];
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 华彩网 TJSSC http://www.huacai.com/buy/index_163.html
     * @param array $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private static function _getFromHuacaiCn($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('gb2312');
        if (!self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = self::$wget->getResponseBody();
        if (strlen($contents) < 100) {
            return drawSources::failed(32, '取内容异常：contents=' . $contents);
        }
        $pattern = '`<td[^>]*>(\d{11})</td>.*<td[^>]*><div\s*class="haoma3"><span[^>]*>(\d)</span>\s*<span[^>]*>(\d)</span>\s*<span[^>]*>(\d)</span>\s*<span[^>]*>(\d)</span>\s*<span[^>]*>(\d)</span>\s*</div></td>`Uims';
        preg_match_all($pattern, $contents, $matches);
        if (count($matches[2]) < 1) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }
        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '8':
                /**
                 * TJSSC 正确格式 20131218-015
                 * 20131218015
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('/^\d{11}$/', $v)) {
                        return drawSources::failed(25, '奖期' . $v);
                    }
                    $tmpIssue = substr($v, 0, 8) . '-' . substr($v, 8, 3);
                    $tmpNumber = $matches[2][$k] . $matches[3][$k] . $matches[4][$k] . $matches[5][$k] . $matches[6][$k];
                    if (!preg_match('`^\d{5}$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
//            case '9'://北京快乐八
//                foreach ($matches[2] as $k => $v) {
//                    //正确格式 奖期：402686 开奖号码：02 16 19 23 26 28 33 40 41 43 46 49 50 55 58 59 62 70 74 79
//                    $v = trim($v);
//                    if (!preg_match('/^\d{6}$/', $v)) {
//                        return drawSources::failed(25, '奖期' . $v); //获取的奖期格式不正确
//                    }
//                    $tmpIssue = $v;
//                    $aTmpNumber = explode(",", $matches[1][$k]);
//                    if (count($aTmpNumber) != 20) {
//                        return drawSources::failed(25, '号码' . $matches[1][$k]); //获取的号码不正确
//                    }
//                    sort($aTmpNumber); //号码排序处理
//                    $tmpNumber = implode(" ", $aTmpNumber);
//                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
//                }
//                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 我中啦 http://www.wozhongla.com/lottery/115/index.shtml
     * @param <type> $lottery
     * @param <type> $url
     * @param <type> $expectedIssue
     * @return <type>
     */
    private function _getFromWozhonglaCom($lottery, $url, $expectedIssue = 0)
    {
        $port = 80;
        $referer = $cookie = $post = "";
        self::$wget->setInCharset('utf-8');
        switch ($lottery['lottery_id']) {
            case '3':
                $url = "http://www.wozhongla.com/sp1/act/data.resultsscListOne.action?page.pagesize=10&page.no=1&type=006";
                break;
            case '5':
                $url = "http://www.wozhongla.com/sp1/act/data.resultsscListOne.action?page.pagesize=10&page.no=1&type=107";
            case '9':
                $url = "http://www.wozhongla.com/lotdata/WzlChart.dll?wAgent=100&wAction=101&wParam=LotID=20108_ChartID=20111_StatType=0";
                break;
        }
        if (!self::$wget->getContents('CURL', "GET", $url)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());

        if (strlen($contents) < 10) {
            return drawSources::failed(32, "取内容异常：contents=$contents");
        }
        if ($lottery['lottery_id'] == 9) {
            $pattern = "/\<td\s*class=\"Issue\">\s*(\d{6})\s*\<\/td>.*\<td\s*class=\"Issue\">(.*)\+.*\<\/td>/Uims";
        } else {
            $pattern = '`"issueNumber":"(\d{7,11})",.*"resultNumber":"(\d[^"]+\d)"`Uims';
        }
        preg_match_all($pattern, $contents, $matches);
        if (count($matches[1]) < 2) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '3':
                /**
                 *  正确格式：20100315-001 96513
                 * 0713046  1,8,2,7,4
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^\d{7}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = date('Y') . substr($v, 0, 4) . '-' . substr($v, 4, 3);
                    if (!preg_match('`^\d,\d,\d,\d,\d$`', $matches[2][$k])) {
                        return drawSources::failed(25, "号码：{$matches[2][$k]}");
                    }
                    $tmpNumber = implode('', explode(',', trim($matches[2][$k])));
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '5':
                /**
                 *  正确格式：10062301  07 04 11 01 08
                 * 10071341  05,09,11,02,03
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^1\d{7}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    if (!preg_match('`^\d{2},\d{2},\d{2},\d{2},\d{2}$`', $matches[2][$k])) {
                        return drawSources::failed(25, "号码：{$matches[2][$k]}");
                    }
                    $tmpNumber = implode(' ', explode(',', trim($matches[2][$k])));
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '9'://北京快乐八
                /**
                 *  正确格式：奖期：402686 开奖号码：02 16 19 23 26 28 33 40 41 43 46 49 50 55 58 59 62 70 74 79
                 */
                krsort($matches[1]);
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    //奖期格式检测
                    if (!preg_match('`^\d{6}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    //开奖号码检测
                    $tmpNumber = trim($matches[2][$k]);
                    if (!preg_match('`^(\d{2}\s){19}(\d{2})$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $tmpIssue = $v;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 快三网 http://www.kuai3.com/jsks/
     * @author    Davy
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private function _getFromKuai3Com($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        switch ($lottery['lottery_id']) {
            case '12':
                //DB:20150530-082	kuai3:20150604-040
                $url = "http://www.kuai3.com/lottery/awardlist/JSKS";
                $pattern = '`<tr>.*<td>(\d{8}-\d{3})</td>.*<td>(\d{2}:\d{2})</td>.*<td[^>]*>(\d,\d,\d)</td>`Uims';
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        preg_match_all($pattern, $contents, $matches);
        if (count($matches[1]) < 1) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '12':
                /**
                 * 江苏快三：20150403-014   5,4,4
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`\d{8}-\d{3}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    $tmpNumber = implode('', explode(',', trim($matches[3][$k])));
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = date('Y-m-d') . ' ' . trim($matches[2][$k]) . ':00';
                    if (!preg_match('`^[1-6]{3}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 彩乐乐 http://www.cailele.com/lottery/ssl/ 仅支持IE,fb不作用
     * @author
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private function _getFromCaileleCom($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        switch ($lottery['lottery_id']) {
            case '1':     //CQSSC <tr><td>131215075</td><td>18:30</td><td>1,4,9,0,4</td></tr>
                $url = "http://www.cailele.com/static/ssc/newlyopenlist.html";
                $pattern = '`<tr><td>(\d{9})</td><td>[^<>]*</td><td>(\d{1},\d{1},\d{1},\d{1},\d{1})</td></tr>`Uims';
                break;
            case '2':   //SD11Y <tr><td>121555</td><td>18:05</td><td>07,11,05,04,03</td></tr>
                $url = "http://www.cailele.com/static/11yun/newlyopenlist.html";
                $pattern = '`<tr><td>(\d{6})</td><td>[^<>]*</td><td>(\d{2},\d{2},\d{2},\d{2},\d{2})</td></tr>`Uims';
                break;
            case '5':   //CQ115 <tr><td>13121556</td><td>18:10</td><td>01,02,06,05,04</td></tr>
                $url = "http://www.cailele.com/static/cq11x5/newlyopenlist.html";
                $pattern = '`<tr><td>(\d{8})</td><td>[^<>]*</td><td>(\d{2},\d{2},\d{2},\d{2},\d{2})</td></tr>`Uims';
                break;
            case '6':   //JX115 <tbody id="openPanel">.*</tbody>
            case '7':   //GD115 <tbody id="openPanel">.*</tbody>
                $pattern = '`<tbody\s+id="openPanel">(.+)</tbody>`Uims';
                break;
            case '9':   //3d
                $url = "http://www.cailele.com/static/3d/newlyopenlist.html";
                $pattern = '`<tr><td>(\d{7})</td><td>[^<>]*</td><td>(\d{1},\d{1},\d{1})</td></tr>`Uims';
                break;
            case '12':   //JSKS <tr><td>0403014</td><td>10:50</td><td>1,3,4</td><td>8</td></tr>
                //DB:20150530-082	彩乐乐:150602078
                if (!empty($expectedIssue)) {
                    $tmp = explode('-', $expectedIssue);
                    $searchIssue = substr(implode('', $tmp), 2);
                }
                $url = "http://kjh.cailele.com/common/kjgg.php?lotType=157&term=" . $searchIssue;
                //$pattern = '`<tr><td>(\d{7})</td><td>[^<>]*</td><td>(\d{1},\d{1},\d{1})</td>`Uims';
                $pattern = '`id=\"resultStr\"[^>]*value=\"(\d{1},\d{1},\d{1})\"[^>]*>.*?\"cz_name_period\">(\d+)</p>.*?\"underthe_box Pool\"[^>]*>.*<span>(\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2})</span><br>`Uims';
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        switch ($lottery['lottery_id']) {
            case '6':   //<tr><td>13121707</td><td>10:10</td><td>09,10,08,04,11</td></tr>
            case '7':
                if (!preg_match($pattern, trim($contents), $match)) {
                    return drawSources::failed(32, '获取匹配内容失败：content：' . $contents);
                }
                $contents = trim($match[1]);
                $pattern = '`<tr><td>(\d{8})</td><td>.*</td><td>(\d{2},\d{2},\d{2},\d{2},\d{2})</td></tr>`Uims';
                break;
        }

        preg_match_all($pattern, $contents, $matches);
        if (count($matches[1]) < 1) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '1':
                /**
                 *  CQSSC 正确格式：20130604-051 96513
                 * <tr><td>131220037</td><td>12:10</td><td>1,8,6,5,3</td></tr>
                 * 慎用.*，不严谨的正则会导致可能的漏洞
                 * <tr><td>150305018</td><td>01:30</td><td>255,255,255,255,255</td></tr>
                 * <tr><td>150305017</td><td>01:25</td><td>9,2,6,4,3</td></tr>
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^1\d{8}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = "20" . substr($v, 0, 6) . "-" . substr($v, 6, 3);
                    $tmpNumber = implode('', explode(',', trim($matches[2][$k])));
                    if (!preg_match('`^\d{5}$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$matches[2][$k]}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;

            case '2':
                /**
                 * sd11y 正确格式：20130110-001   01 09 11 07 05
                 * <tr><td>121555</td><td>18:05</td><td>07,11,05,04,03</td></tr>
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^[01]\d{5}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = date('Y') . substr($v, 0, 4) . "-0" . substr($v, 4, 2);
                    $tmpNumber = implode(' ', explode(',', trim($matches[2][$k])));
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '5':
            case '6':
            case '7':
                /**
                 * cq115 正确格式：20130110-001   01 09 11 07 05
                 * <tr><td>13121556</td><td>18:10</td><td>01,02,06,05,04</td></tr>
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^1\d{7}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = "20" . substr($v, 0, 6) . "-0" . substr($v, 6, 2);
                    $tmpNumber = implode(' ', explode(',', trim($matches[2][$k])));
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '9':
                /**
                 * 福彩3D：2014081   5,4,4
                 * <tr><td>2014081</td><td>18:10</td><td>  5,4,4</td></tr>
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^\d{7}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    $tmpNumber = implode('', explode(',', trim($matches[2][$k])));
                    if (!preg_match('`^\d{3}$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$matches[2][$k]}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '12':
                /**
                 * 江苏快三：20150403-014   5,4,4
                 */
                foreach ($matches[2] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`\d{9}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = date('Y', time()) . substr($v, 2, 4) . "-" . substr($v, 6, 3);
                    $tmpNumber = implode('', explode(',', trim($matches[1][$k])));
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = trim($matches[3][$k]) . ':00';
                    if (!preg_match('`^[1-6]{3}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * aicai.com
     * @param Array $lottery
     * @param String $url
     * @param String $expectedIssue
     * @return Array
     */
    private function _getFromAicaiCom($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        $result = array();
        switch ($lottery['lottery_id']) {
            case '7':   //GD115 <tbody id="jq_body_kc_result">.*</tbody>
                $pattern = '`<tbody\s+id="jq_body_kc_result">(.+)</tbody>`Uims';
                if (!preg_match($pattern, trim($contents), $match)) {
                    return drawSources::failed(32, '获取匹配内容失败：content：' . $contents);
                }
                $contents = trim($match[1]);
                $pattern = '`<tr[^>]*>\W*<td[^>]*>([\d-]+)期</td>\W*<td[^>]*>.*</td>\W*<td>(\d{2},\d{2},\d{2},\d{2},\d{2})</td>`Uims';
                preg_match_all($pattern, $contents, $matches);
                if (count($matches[1]) < 1) {
                    return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
                }
                /**
                 * GD115 正确格式：20130110-001   01 09 11 07 05
                 * 20131217-26  10,11,06,04,08
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^201\d{5}-\d{2}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = substr($v, 0, 8) . "-0" . substr($v, 9, 2);
                    $tmpNumber = implode(' ', explode(',', trim($matches[2][$k])));
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }

                break;
            case '6'://江西11选5
                $pattern = '`<p class="lot_kjqs">(\d+)期(.*)<div class="kj_ball lot_i">(.*)</div>`Uims';
                preg_match_all($pattern, $contents, $matches);
                if (count($matches[1]) < 1) {
                    return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
                }
                if (count($matches)) {
                    $tmp_issue = $matches[1][0];
                    $tmp_issue = (substr($tmp_issue, 0, 8) . "-0" . substr($tmp_issue, 8));
                    $tmp_num = trim($matches[3][0]);
                    $tmp_num = str_replace('<i>', '', $tmp_num);
                    $tmp_num = str_replace('</i>', ' ', $tmp_num);
                    $result[$tmp_issue] = array('issue' => $tmp_issue, 'number' => $tmp_num);
                } else {
                    return drawSources::failed(12, "解析内容失败:" . __LINE__);
                }

                break;
            case '14':
                $startPosition = strpos($contents, 'jq_body_kc_result');
                $endPosition = strpos($contents, 'lotboxright');
                $length = $endPosition - $startPosition;
                $contents = substr($contents, $startPosition, $length);
                $pattern = "@<td>(\d{8}-\d{2})期</td>\s*<td\s*[^>]*>(\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2})</td>\s*<td><i\s*class='fs16[^>]*'>(♠|♦|♥|♣)</i>([\dAJQK]+),<i\s* class='fs16[^>]*'>(♠|♦|♥|♣)</i>([\dAJQK]+),<i\s*class='fs16[^>]*'>(♠|♦|♥|♣)</i>([\dAJQK]+)</td>@Uims";
                preg_match_all($pattern, $contents, $matches);
                if (count($matches[1]) < 1) {
                    return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
                }
                /**
                 *  快乐扑克 奖期格式：20150630-020
                 *  $matches[1] 奖期集合 20150714-27
                 *  $matches[2] 开奖时间集合 2015-07-14 13:22
                 *  $matches[3] 一位花色集合
                 *  $matches[4] 一位号码集合
                 *  $matches[5] 二位花色集合
                 *  $matches[6] 二位号码集合
                 *  $matches[7] 三位花色集合
                 *  $matches[8] 三位号码集合
                 */
                $colorMap = array('♠' => 's', '♦' => 'd', '♥' => 'h', '♣' => 'c');
                for ($i = 0, $len = count($matches[1]); $i < $len; $i++) {
                    $tmpIssues = explode('-', $matches[1][$i]);
                    $tmpIssue = $tmpIssues[0] . '-0' . $tmpIssues[1];
                    $tmpOpenTime = $matches[2][$i];
                    $tmpOpenCode1 = $matches[4][$i] == 10 ? 'T' . $colorMap[$matches[3][$i]] : $matches[4][$i] . $colorMap[$matches[3][$i]];
                    $tmpOpenCode2 = $matches[6][$i] == 10 ? 'T' . $colorMap[$matches[5][$i]] : $matches[6][$i] . $colorMap[$matches[5][$i]];
                    $tmpOpenCode3 = $matches[8][$i] == 10 ? 'T' . $colorMap[$matches[7][$i]] : $matches[8][$i] . $colorMap[$matches[7][$i]];
                    $tmpNumber = $tmpOpenCode1 . ' ' . $tmpOpenCode2 . ' ' . $tmpOpenCode3;

                    if (!preg_match('@^([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]\s([2-9]|[ATJQK])[sdch]$@Ui', $tmpNumber)) {
                        return drawSources::failed(25, "抓取号码不正确：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpOpenTime);
                }

                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`,{$lottery['lottery_id']}");
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 彩票直通车 http://tools.ecp888.com/Trade/sscjx/bin/NumberallXml.asp
     * XML形式数据
     * @author jack,
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private function _getFromEcp888Com($lottery, $url, $expectedIssue = 0)
    {
//        switch ($lottery['lottery_id']) {
//            case 1:
//                $url = "http://tools.ecp888.com/Trade/ssc/bin/NumberallXml.asp";
//                break;
////            case 3:
////                $url = "http://tools.ecp888.com/Trade/sscjx/bin/NumberallXml.asp";
////                break;
////            case 4:
////                $url = "http://tools.ecp888.com/Trade/ssl/bin/NumberallXml.asp";
////                break;
////            case 7:
////                $url = "http://tools.ecp888.com/Trade/SyJx/bin/NumberallXml.asp";
////                break;
//        }
        self::$wget->setInCharset('utf-8')->setReferer('http://tools.ecp888.com/ssc/');
        if (!self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());
//logdump($contents);
        /*
         * 正确格式：100604024 69262
         * 20121211051 1,5,3,0,6
          <div class="bk_1 height2" id="kjNumberDiv">
          <div class='danqi_hao'><p>第<span id='kjIssue'>20121211052</span>期开奖号码</p><div class='qjz'><span>4</span><span>1</span><span>7</span><span>3</span><span>2</span></div></div>
          <ul class='fff'>
          <li style='display: none;'><span>20121211052</span><em>14:40</em><strong>4,1,7,3,2</strong></li>
          <li><span>20121211051</span><em>14:30</em><strong>1,5,3,0,6</strong></li>
          <li><span>20121211050</span><em>14:20</em><strong>2,9,9,5,5</strong></li>
          <li><span>20121211049</span><em>14:10</em><strong>4,2,5,8,2</strong></li>
          <li><span>20121211048</span><em>14:00</em><strong>7,3,1,3,4</strong></li>
          <li><span>20121211047</span><em>13:50</em><strong>2,0,5,0,8</strong></li>
          </ul>
          </div>
         */
        $pattern = '`<div[^>]*id="kjNumberDiv">.*</div></div><ul[^>]*>(.*)</ul>`Uims';
        if (!preg_match($pattern, $contents, $match)) {
            return drawSources::failed(22, "获取块内容出错：content length:" . strlen($contents));
        }

        $pattern = '`<li[^>]*><span>(\d{11})</span><em>[\d:]*</em><strong>(\d,\d,\d,\d,\d)</strong></li>`Uims';
        preg_match_all($pattern, $match[1], $matches);

        if (count($matches[1]) < 2) {
            return drawSources::failed(33, "获取匹配内容失败：content length:" . strlen($contents));
        }

        $result = array();

        foreach ($matches[1] as $k => $v) {
            $v = trim($v);
            if (!preg_match('/^\d{11}$/', $v)) {
                return drawSources::failed(25, '奖期：' . $v);
            }
            $tmpIssue = substr($v, 2);
            if (!preg_match('/^\d,\d,\d,\d,\d$/', $matches[2][$k])) {
                return drawSources::failed(25, '号码：' . $matches[2][$k]);
            }
            $tmpNumber = str_replace(',', '', $matches[2][$k]);
            $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
        }
        arsort($result);
        // 严格判断
//        switch ($lottery['lottery_id']) {
//            case '1':
//
//
//                $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
//                arsort($result);
//                break;
//            case '3':
//                /*
//                 * 正确格式：20100622-001 70405
//                  <row  digit="20100712041" atime="15:57" anum="9 0 7 9 8" />
//                 */
//                for ($i = count($xmlArray) - 1, $j = 0; $i >= 0 && $j < 120; $i--, $j++) {
//                    $tmpIssue = trim($xmlArray[$i]['@digit']);
//                    $tmpNumber = implode('', explode(' ', $xmlArray[$i]['@anum']));
//                    if (!preg_match('`^201\d{8}$`Ui', $tmpIssue)) {
//                        return drawSources::failed(25, "奖期：{$tmpIssue}");
//                    }
//                    $tmpIssue = substr($tmpIssue, 0, 8) . '-' . substr($tmpIssue, 8, 3);
//                    if (!preg_match('`^\d{5}$`Ui', $tmpNumber)) {
//                        return drawSources::failed(25, "号码：{$tmpNumber}");
//                    }
//                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
//                }
//                arsort($result);
//                break;
//            case '4':
//                /*
//                 * 正确格式：20100622-01 772
//                  <row  digit="20100712-12" atime="16:00" anum="3 0 2" />
//                 */
//                for ($i = count($xmlArray) - 1, $j = 0; $i >= 0 && $j < 120; $i--, $j++) {
//                    $tmpIssue = trim($xmlArray[$i]['@digit']);
//                    $tmpNumber = implode('', explode(' ', $xmlArray[$i]['@anum']));
//                    if (!preg_match('`^201\d{5}-\d{2}$`Ui', $tmpIssue)) {
//                        return drawSources::failed(25, "奖期：{$tmpIssue}");
//                    }
//                    if (!preg_match('`^\d{3}$`Ui', $tmpNumber)) {
//                        return drawSources::failed(25, "号码：{$tmpNumber}");
//                    }
//                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
//                }
//                arsort($result);
//                break;
//            case '7':
//                /*
//                 * 正确格式：20100623-01     04 08 11 01 07
//                  <row  digit="2010071333" atime="15:34" anum="03 08 01 10 09" />
//                 */
//                for ($i = count($xmlArray) - 1, $j = 0; $i >= 0 && $j < 120; $i--, $j++) {
//                    $tmpIssue = trim($xmlArray[$i]['@digit']);
//                    $tmpNumber = $xmlArray[$i]['@anum'];
//                    if (!preg_match('`^201\d{7}$`Ui', $tmpIssue)) {
//                        return drawSources::failed(25, "奖期：{$tmpIssue}");
//                    }
//                    $tmpIssue = substr($tmpIssue, 0, 8) . '-' . substr($tmpIssue, 8, 2);
//                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
//                        return drawSources::failed(25, "号码：{$tmpNumber}");
//                    }
//                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
//                }
//                arsort($result);
//                break;
//            default:
//                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
//                break;
//        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 盈彩网 http://ssl.betzc.com/
     * @author jack
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private function _getFromBetzcCom($lottery, $url, $expectedIssue = 0)
    {
        $port = 80;
        $method = "GET";
        $referer = $cookie = $post = "";
        self::$wget->setInCharset('gbk');
        if (!self::$wget->getContents('CURL', $url, $port, $method, $referer, $cookie, $post)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());
        if (strlen($contents) < 50000) {
            return drawSources::failed(32, '取内容异常：contents=' . $contents);
        }

        /*
         * CQSSC
         * <tr class="ssl_fatr2">
          <td >0713061</td>
          <td >16:10</td>
          <td >96253</td>
          </tr>

         * <tr class="ssl_fatr2">
          <td >0713-12</td>
          <td >16:00</td>
          <td >490</td>
          </tr>
         */
        /*
         * GD115
         * <tr class="trw">
          <td>10100814</td>
          <td>11:46</td>
          <td class="rebchar">08&nbsp;05&nbsp;11&nbsp;03&nbsp;01&nbsp;</td>

          </tr>
          <tr class="trgray">
          <td>10100813</td>
          <td>11:34</td>
          <td class="rebchar">02&nbsp;07&nbsp;04&nbsp;11&nbsp;06&nbsp;</td>

          </tr>
         */
        switch ($lottery['lottery_id']) {
            case '1':
            case '4':
                $pattern = "/\<tr\s*class=\"ssl_fatr[23]\">\s*<td\s*>([\d-]{7,})\<\/td>.*\<td\s*>(\d{3,5})\<\/td>/Uims";
                break;
            case '8':
                $pattern = '`\<tr\s*class="(?:trw|trgray)">\s*<td\s*>(\d{8})\</td>.*\<td\s+class="rebchar">(\d{2})&nbsp;(\d{2})&nbsp;(\d{2})&nbsp;(\d{2})&nbsp;(\d{2})&nbsp;\</td>`Uims';
                break;
            default:
                return drawSources::failed(13, "没有定义规则");
                break;
        }
        preg_match_all($pattern, $contents, $matches);

        if (count($matches[1]) < 2) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '1':
                /**
                 *  正确格式：100623-01  490
                 * 0713061  96253
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('/^\d{7}$/', $v)) {
                        return drawSources::failed(25, '奖期：' . $v);
                    }
                    $tmpIssue = date('y') . $v;
                    if (!preg_match('/^\d{5}$/', $matches[2][$k])) {
                        return drawSources::failed(25, '号码：' . $matches[2][$k]);
                    }
                    $tmpNumber = $matches[2][$k];
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case 4:
                /**
                 *  正确格式：20100623-01  490
                 * 0713-12  490
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('/^\d{4}-\d{2}$/', $v)) {
                        return drawSources::failed(25, '奖期：' . $v);
                    }
                    $tmpIssue = date('Y') . $v;
                    if (!preg_match('/^\d{3}$/', $matches[2][$k])) {
                        return drawSources::failed(25, '号码：' . $matches[2][$k]);
                    }
                    $tmpNumber = $matches[2][$k];
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '8':
                /**
                 *  正确格式：10091801  05 10 06 02 03
                 * 10100814
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('/^\d{8}$/', $v)) {
                        return drawSources::failed(25, '奖期：' . $v);
                    }
                    $tmpIssue = $v;
                    $tmpNumber = $matches[2][$k] . ' ' . $matches[3][$k] . ' ' . $matches[4][$k] . ' ' . $matches[5][$k] . ' ' . $matches[6][$k];
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, '号码：' . $matches[2][$k]);
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * 双彩 带开奖时间
     * @author    Davy
     * @param int $lottery
     * @param string $url
     * @param int $expectedIssue
     * @return array
     */
    private function _getFromTouZhuZhanCom($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        switch ($lottery['lottery_id']) {
            case '12':
                /*
                 *  	<tr>
                  <td class="cbg1">150601006</td>
                  <td class="cbg3">2015-06-01 09:29:49</td>
                  <td class="cbg4"><span class="s_ball_red">1</span><span class="s_ball_red">1</span><span class="s_ball_red">3</span></td>
                  <td class="cbg2"><span class='fang chartfang03'>二不同</span></td>
                  <td class="cbg6"><span class="fang chartfang06">5</span></td>
                  </tr><tr>
                 */
                $url = "http://www.touzhuzhan.com/bull/index.jsp/Index/list_jsks";
                $pattern = '`<tr[^>]*>.*<td.*cbg1.*>(.*)</td>.*<td.*cbg3.*>(.*)</td>.*<td.*cbg4.*><span.*s_ball_red.*>(\d)</span><span.*s_ball_red.*>(\d)</span><span.*s_ball_red.*>(\d)</span></td>.*<td[^>]*>.*</td>.*<td[^>]*>.*</td>.*</tr>`Uims';
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }

        //$contents = file_get_contents("D:/xw.html");

        preg_match_all($pattern, $contents, $matches);

        if (count($matches[1]) < 1) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '12':
                /**
                 * 江苏快三：150601048    =>    20150403-014
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`\d{9}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    //奖期
                    $tmpIssue = '20' . substr($v, 0, 6) . "-" . substr($v, 6, 3);
                    //开奖时间 ,必须格式化为带秒的时间字符串,无秒自己添加0, ex:   2015-05-05 00:00:00
                    $tmpopenTime = trim($matches[2][$k]);
                    //开奖号码
                    $tmpNumber = $matches[3][$k] . $matches[4][$k] . $matches[5][$k];
                    if (!preg_match('`^[1-6]{3}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber, 'openTime' => $tmpopenTime);
                }
                break;
            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return self::getNumber($result, $expectedIssue);
    }

    /**
     *
     * @param <int> $lottery
     * @param <string> $url
     * @param <int> $expectedIssue
     * @return <array>
     */
    private function _getFromCaishijieCom($lottery, $url, $expectedIssue = 0)
    {
        //下面地址可以取得xml的数据，取出号码
        //$url = "http://ssc.caishijie.com/luckynumber/newsscnumbernew.xml?tt=70661.87565214932";
        self::$wget->setInCharset('utf-8');

        $randNum = "0." . rand(10000000, 100000000 - 1) . rand(10000000, 100000000 - 1);
        switch ($lottery['lottery_id']) {
            case '3':   //hljssc
                $url = "http://goucai.caishijie.com/lottery/hisnumber.action?lotteryId=006&issueLen=10&d=$randNum";
                break;
            case '10':   //hljssc
                $url = "http://goucai.caishijie.com/lottery/hisnumber.action?lotteryId=109&issueLen=10&d=$randNum";
                break;
            default:
                break;
        }
        if (!$url) {
            return drawSources::failed(31, "取内容出错：不支持的地址");
        }
        if (!self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());
        if (strlen($contents) < 100) {
            return drawSources::failed(32, "取内容异常：contents=$contents");
        }
        switch ($lottery['lottery_id']) {
            case '3':
                $hljData = json_decode($contents);
                break;
            case '10':
                $hljData = json_decode($contents);
                break;
            default:
                return drawSources::failed(13, "没有定义规则");
                break;
        }
        if (empty($hljData)) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '3':
                /**
                 *  正确格式：0122290 96513
                 * 0122290 0206000003
                 */
                foreach ($hljData as $k => $v) {
//                    $v = trim($v);
//                    if (!preg_match('`^00\d{5}$`', $v))
//                    {
//                        return drawSources::failed(25, "奖期：{$v}");
//                    }
//                    $tmpIssue = $v;
//                    if (!preg_match('`^(\d),(\d),(\d),(\d),(\d)$`', $matches[2][$k], $match))
//                    {
//                        return drawSources::failed(25, "号码：{$matches[2][$k]}");
//                    }
//                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => "{$match[1]}{$match[2]}{$match[3]}{$match[4]}{$match[5]}");
                    $tmpNumber = intval(substr($v->lotteryNumber, 0, 2)) . intval(substr($v->lotteryNumber, 2, 2)) . intval(substr($v->lotteryNumber, 4, 2)) . intval(substr($v->lotteryNumber, 6, 2)) . intval(substr($v->lotteryNumber, 8, 2));
                    $result[$v->lotteryExpect] = array('issue' => $v->lotteryExpect, 'number' => $tmpNumber);
                }
                break;
            case '10':
                foreach ($hljData as $k => $v) {
                    $tmpNumber = intval(substr($v->lotteryNumber, 0, 1)) . intval(substr($v->lotteryNumber, 2, 1)) . intval(substr($v->lotteryNumber, 4, 1)) . intval(substr($v->lotteryNumber, 6, 1)) . intval(substr($v->lotteryNumber, 8, 1));
                    if (!preg_match('/^(\d{5})$/', $tmpNumber)) {
                        return drawSources::failed(25, '号码' . $tmpNumber);
                    }
                    //将奖期处理过
                    $tmpIssue = date("Y") . substr($v->lotteryExpect, -3);

                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            case '9'://北京快乐八
                /**
                 *  正确格式：奖期：402686 开奖号码：02 16 19 23 26 28 33 40 41 43 46 49 50 55 58 59 62 70 74 79
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^\d{6}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    $tmpNumber = $matches[2][$k];
                    for ($i = 3; $i < 22; $i++) {
                        $tmpNumber .= " " . $matches[$i][$k];
                    }
                    if (!preg_match('`^(\d{2}\s){19}(\d{2})$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * xjflcp.com处理，必须指定奖期
     * @param <type> $url
     * @return 成功返回array('issue' => $issue, 'number' => $number)，出错返回false，不在奖期范围内返回字符串
     */
    private static function _getFromXjflcpCom($lottery, $url, $expectedIssue)
    {
        self::$wget->setInCharset('gbk');

        if (!self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());
        if (strlen($contents) < 100) {
            return drawSources::failed(32, "取内容异常：contents=$contents");
        }

        $pattern = "`<div id=\"main1left\">(.*)<div id=\"main1right\">`Uims";
        if (!preg_match($pattern, $contents, $match)) {
            return drawSources::failed(22, "获取块内容出错：content length:" . strlen($contents) . ", content=$contents");
        }

        $pattern = '`<tr>\s*<td><a href="javascript:detatilssc\(\'\d+\'\);">(\d+)</a></td>.*<td class="red"><p>(.*)</p></td>`Uims';
        preg_match_all($pattern, $match[1], $matches);
        if (empty($matches[1]) || empty($matches[2])) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        // 严格判断
        $result = array();
        switch ($lottery['lottery_id']) {
            case '4':
                /**
                 *  正确格式：20130731-01 96513
                 * 2013073129 4 2 8 7 4
                 */
                foreach ($matches[1] as $k => $v) {
                    $tmpIssue = trim($matches[1][$k]);
                    if (!preg_match('`^201\d{7}$`', $tmpIssue)) {
                        return drawSources::failed(25, "奖期：{$tmpIssue}");
                    }
                    $tmpIssue = substr($tmpIssue, 0, 8) . '-' . substr($tmpIssue, 8, 2);
                    if (!preg_match('`^(\d)\D*(\d)\D*(\d)\D*(\d)\D*(\d)$`', $matches[2][$k], $match3)) {
                        return drawSources::failed(25, "号码：{$matches[2][$k]}");
                    }
                    $tmpNumber = $match3[1] . $match3[2] . $match3[3] . $match3[4] . $match3[5];
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    /**
     * sohu.com处理
     * @param <type> $url
     * @return 成功返回array('issue' => $issue, 'number' => $number)，出错返回false，不在奖期范围内返回字符串
     */
    private static function _getFromSohuCom($lottery, $url, $expectedDate, $expectedIssue = 0)
    {
        $method = "GET";
        $referer = $cookie = $post = "";
        self::$wget->setInCharset('utf-8');
        $date = date("Ymd", strtotime($expectedDate));
        switch ($lottery['lottery_id']) {
            case '1':   // http://lottery.sports.sohu.com/open/ssc.shtml
                $url = 'http://lottery.sports.sohu.com/open/inc/getHttpXml.php?lotname=ssc&expect=' . $date . '&type=0&callback=_getGPResult';
                break;
            case '4':  // http://lottery.sports.sohu.com/open/ssl.shtml
                $url = 'http://lottery.sports.sohu.com/open/inc/getHttpXml.php?lotname=ssl&expect=' . $date . '&type=0&callback=_getGPResult';
                break;
            case '5':  // http://lottery.sports.sohu.com/open/syydj.shtml
                $url = 'http://lottery.sports.sohu.com/open/inc/getHttpXml.php?lotname=syydj&expect=' . $date . '&type=0&callback=_getGPResult';
            case '9':
                $url = 'http://bjfcdt.gov.cn/LtrAPI/happy8/v1/getAwardNumber.aspx?date=' . $expectedDate;
                break;
        }

        if (!self::$wget->getContents('CURL', $url, 80, $method, $referer, $cookie, $post)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());
        if (strlen($contents) < 10) {
            return drawSources::failed(32, "取内容异常：contents=$contents");
        }

        // _getGPResult('ssl','');
        if ($lottery['lottery_id'] == 9) {
            $pattern = "/\<span\s*class=\"flow_font\">(\d{6})";
            for ($i = 0; $i < 20; $i++) {
                $pattern .= ".*\<td\s*align=\"center\"\s*class=\".*\">(\d{2})\<\/td>";
            }
            $pattern .= "/Uims";
            preg_match_all($pattern, $contents, $match);
        } else {
            $pattern = '`<\?xml.*</xml>`Uims';
            preg_match($pattern, $contents, $match);
        }

        if ($lottery['lottery_id'] == 9) {
            if (count($match[1]) < 1) {
                logdump("Sohu.com 分析内容失败 contents=$contents");
                return drawSources::failed(36, "分析内容失败,content length=" . strlen($contents));
            }
        } else {
            if (strlen($match[0]) < 10 || empty($match[0])) {
                logdump("Sohu.com 分析内容失败 contents=$contents");
                return drawSources::failed(36, "分析内容失败,content length=" . strlen($contents));
            }

            $contents = $match[0];
            if (!$xmlArray = drawSources::parseXML($contents)) {
                return drawSources::failed(23, "解析XML出错");
            }

            $xmlArray = $xmlArray['xml'][0]['row'];
            if (!$xmlArray || count($xmlArray) == 0) {
                return drawSources::failed(24, "XML2array出错");
            }
        }
        $result = array();
        // 严格判断
        switch ($lottery['lottery_id']) {
            case '1':
                /*
                 * 正确格式：100604024 69262
                  [31] => Array
                  (
                  [@expect] => 100607032
                  [@opencode] => 3,3,1,8,7
                  [@opentime] => 2010-6-7 11:20:00
                  )
                 */
                for ($i = count($xmlArray) - 1, $j = 0; $i >= 0 && $j < 120; $i--, $j++) {
                    $tmpIssue = trim($xmlArray[$i]['@expect']);
                    $tmpNumber = implode('', explode(',', $xmlArray[$i]['@opencode']));
                    if (!preg_match('`^1\d{8}$`Ui', $tmpIssue)) {
                        return drawSources::failed(25, "奖期：{$tmpIssue}");
                    }
                    if (!preg_match('`^\d{5}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                arsort($result);
                break;
            case '4':
                /*
                 * 正确格式：20100315-01 515
                  [2] => Array
                  (
                  [@expect] => 20100607-03
                  [@opencode] => 355
                  [@opentime] => 2010-6-7 11:30:00
                  )
                 */
                for ($i = count($xmlArray) - 1, $j = 0; $i >= 0 && $j < 120; $i--, $j++) {
                    $tmpIssue = trim($xmlArray[$i]['@expect']);
                    $tmpNumber = implode('', explode(',', $xmlArray[$i]['@opencode']));
                    if (!preg_match('`^201\d{5}-\d{2}$`Ui', $tmpIssue)) {
                        return drawSources::failed(25, "奖期：{$tmpIssue}");
                    }
                    if (!preg_match('`^\d{3}$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                arsort($result);
                break;
            case '5':
                /*
                 * 正确格式：10031501 04 09 07 05 11
                  (
                  [@expect] => 10061809
                  [@opencode] => 04,05,07,09,10
                  [@opentime] => 2010-06-18 00:00:00
                  )
                 */
                for ($i = count($xmlArray) - 1, $j = 0; $i >= 0 && $j < 120; $i--, $j++) {
                    $tmpIssue = trim($xmlArray[$i]['@expect']);
                    $tmpNumber = implode(' ', explode(',', $xmlArray[$i]['@opencode']));
                    if (!preg_match('`^1[01]\d{6}$`Ui', $tmpIssue)) {
                        return drawSources::failed(25, "奖期：{$tmpIssue}");
                    }
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                arsort($result);
                break;
            case '9'://北京快乐八
                /**
                 *  正确格式：奖期：402686 开奖号码：02 16 19 23 26 28 33 40 41 43 46 49 50 55 58 59 62 70 74 79
                 */
                foreach ($match[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^\d{6}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    $tmpNumber = $match[2][$k];
                    for ($i = 3; $i < 22; $i++) {
                        $tmpNumber .= " " . $match[$i][$k];
                    }
                    if (!preg_match('`^(\d{2}\s){19}(\d{2})$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return self::getNumber($result, $expectedIssue);
    }

    /**
     * 500wan.com处理注 ： 500wan 已经改为 500 了    改为只针对福彩  和  p3p5
     * @param <type> $url
     * @return 成功返回array('issue' => $issue, 'number' => $number)，出错返回false，不在奖期范围内返回字符串
     */
    private static function _getFrom500Com($lottery, $url, $expectedIssue = 0)
    {

        self::$wget->setInCharset('gb2312');
        if (!self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：post header=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());

        if (strlen($contents) < 100) {
            return drawSources::failed(32, '取内容异常：contents=' . $contents);
        }
        switch ($lottery['lottery_id']) {
            case '9':
                $pattern3D = "/\<font\s*class=\"cfont2\">\<strong>(\d{7})\<\/strong>\<\/font>/Uims";
                //先取得奖期
                preg_match($pattern3D, $contents, $matcheIssue);
                break;
            case '10':
                $patternP5 = "/\<font\s*class=\"cfont2\">\<strong>(\d{5})\<\/strong>\<\/font>/Uims";
                preg_match($patternP5, $contents, $matcheIssue);
                break;
        }

        //return array('issue' => '123', 'number' => $matcheIssue[1]);
        //取得开奖号
        $pattern2 = "/\<li\s*class=\"ball_orange\">(\d{1})\<\/li>/Uims";
        preg_match_all($pattern2, $contents, $matches);

        if (strlen($matcheIssue[1]) < 2 && count($matches[1]) < 2) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }
        $result = array();
        $codes = '';
        // 严格检查并修正奖期  9=3d
        switch ($lottery['lottery_id']) {
            case '9':
                if (!preg_match('/^\d{7}$/', $matcheIssue[1])) {
                    return drawSources::failed(25, '奖期' . $matcheIssue[1]);
                }
                foreach ($matches[1] as $k => $v) {
                    if (!preg_match('/^(\d{1})$/', $v)) {
                        return drawSources::failed(25, '号码' . $v);
                    }
                    $codes .= $v;
                }
                $result[$matcheIssue[1]] = array('issue' => $matcheIssue[1], 'number' => $codes);
                break;
            case '10':
                if (!preg_match('/^\d{5}$/', $matcheIssue[1])) {
                    return drawSources::failed(25, '奖期' . $matcheIssue[1]);
                }
                foreach ($matches[1] as $k => $v) {
                    if (!preg_match('/^(\d{1})$/', $v)) {
                        return drawSources::failed(25, '号码' . $v);
                    }
                    $codes .= $v;
                }
                $tmpIssue = date("Y") . substr($matcheIssue[1], -3);
                $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $codes);
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }
        return self::getNumber($result, $expectedIssue);
    }

    private static function _getFromShiShiCaiCn($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        if (!self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：post header=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());
        if (strlen($contents) < 10000) {
            return drawSources::failed(32, "取内容异常：彩种 {$lottery['cname']} 奖期 {$expectedIssue} 原始内容=$contents");
        }

        //分析内容
//        $pattern = '`<table>\s*<tr>\s*<td>期号</td>\s*<td>开奖号码</td>(.*)((更多开奖号码)|(</table>))`Uims';
//        if (!preg_match($pattern, $contents, $match)) {
//            return drawSources::failed(22, "获取块内容出错：content length:" . strlen($contents) . ", content=$contents");
//        }
//
//        $contents = $match[1];
        switch ($lottery['lottery_id']) {
            case '1':
                $pattern = '`<tr><td class="borRB">(\d[\d-]+)</td><td class="borB">(\d{5})</td></tr>`Uims';
                break;
            case '2':   //SD11Y
            case '5':   //CQ115
            case '6':   //JX115
            case '7':   //GD115 <tr><td class="borRB">20131217-025</td><td class="borB">01,09,04,02,07</td></tr>
                $pattern = '`<tr><td class="borRB">(\d[\d-]+)</td><td class="borB">(\d{2},\d{2},\d{2},\d{2},\d{2})</td></tr>`Uims';
                break;
        }


        preg_match_all($pattern, $contents, $matches);
        if (count($matches[1]) < 2) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '1':
                /**
                 *  CQSSC 正确格式：20100604-051 96513
                 * 20100604-051 96513
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^201\d{5}-\d{3}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    if (!preg_match('`^\d{5}$`', $matches[2][$k])) {
                        return drawSources::failed(25, "号码：{$matches[2][$k]}");
                    }
                    $tmpNumber = $matches[2][$k];
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;

            case '2':
            case '5':
            case '6':
            case '7':
                /**
                 * 7 正确格式：20130110-001   01 09 11 07 05
                 * 20131217-012    10,05,06,11,04
                 */
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    if (!preg_match('`^201\d{5}-\d{3}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    $tmpIssue = $v;
                    $tmpNumber = implode(' ', explode(',', trim($matches[2][$k])));
                    if (!preg_match('`^[01]\d [01]\d [01]\d [01]\d [01]\d$`Ui', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    //北京福利彩票中心
    private static function _getFromBJFCGov($lottery, $url, $expectedDate, $expectedIssue = 0)
    {
        $port = 80;
        $method = "GET";
        $referer = $cookie = $post = "";
        self::$wget->setInCharset('utf-8');
        switch ($lottery['lottery_id']) {
            case '9':
                $url = "http://tb.bjfcdt.gov.cn/interface.aspx?years=" . $expectedDate . "&charttype=H8other_2";
                break;
            default:
                break;
        }
        if (!self::$wget->getContents('CURL', $url, $port, $method, $referer, $cookie, $post)) {
            return drawSources::failed(31, "取内容出错：postheader=" . self::$wget->getRequestHeaderStream());
        }
        $contents = trim(self::$wget->getResponseBody());

        if (strlen($contents) < 10) {
            return drawSources::failed(32, "取内容异常：contents=$contents");
        }
        if ($lottery['lottery_id'] == 9) {
            $pattern = "/new h8_succ\((\d{6}),\[(.*)\],.*\)/Uims";
        }
        preg_match_all($pattern, $contents, $matches);
        if (count($matches[1]) < 1) {
            return drawSources::failed(33, '获取匹配内容失败：content：' . $contents);
        }

        $result = array();
        // 严格检查并修正奖期
        switch ($lottery['lottery_id']) {
            case '9'://北京快乐八
                /**
                 *  正确格式：奖期：402686 开奖号码：02 16 19 23 26 28 33 40 41 43 46 49 50 55 58 59 62 70 74 79
                 */
                krsort($matches[1]);
                foreach ($matches[1] as $k => $v) {
                    $v = trim($v);
                    //奖期格式检测
                    if (!preg_match('`^\d{6}$`', $v)) {
                        return drawSources::failed(25, "奖期：{$v}");
                    }
                    //开奖号码检测
                    $aNumber = explode(",", trim($matches[2][$k]));
                    $tmpNumber = implode(" ", $aNumber);
                    if (!preg_match('`^(\d{2}\s){19}(\d{2})$`', $tmpNumber)) {
                        return drawSources::failed(25, "号码：{$tmpNumber}");
                    }
                    $tmpIssue = $v;
                    $result[$tmpIssue] = array('issue' => $tmpIssue, 'number' => $tmpNumber);
                }
                break;
            default:
                return drawSources::failed(12, "没有相应彩种`{$lottery['cname']}`");
                break;
        }
        return drawSources::getNumber($result, $expectedIssue);
    }

    // 开彩网（获得黑龙江时时彩）
    static public function _getFromApiplus($lottery, $url, $expectedIssue = 0)
    {
        self::$wget->setInCharset('utf-8');
        if (!$contents = self::$wget->getContents('CURL', 'GET', $url)) {
            return drawSources::failed(31, "取内容出错：request header=" . self::$wget->getRequestHeaderStream());
        }
        $contentsJson = json_decode($contents, 1);

        if ($contentsJson['code'] !== 'hljssc' && empty($contentsJson['data'])) {
            return drawSources::failed(33, '没有奖期开奖或者获取匹配内容失败：content：' . $contents);
        }
        $result = array();
        switch ($lottery['lottery_id']) {

            default:
                return drawSources::failed(12, '没有相应彩种' . $lottery['cname']);
                break;
        }

        return drawSources::getNumber($result, $expectedIssue);
    }

    static public function parseXML($xml)
    {
        $reader = new XMLReader();

        if (!$reader->XML($xml)) {
            return false;
        }

        $container = array();
        $container_stack = array();
        $result = &$container;
        $is_empty_element = false;

        while ($reader->read()) {
            switch ($reader->nodeType) {
                case XMLReader::ELEMENT :
                    $container[$reader->name][] = array();
                    $container = &$container[$reader->name][count($container[$reader->name]) - 1];
                    $container_stack[] = &$container;

                    if ($reader->isEmptyElement) {
                        $is_empty_element = true;
                    }

                    if ($reader->hasAttributes) {
                        while ($reader->moveToNextAttribute()) {
                            $container['@' . $reader->name] = $reader->value;
                        }
                    }

                    if ($is_empty_element) {
                        array_pop($container_stack);
                        $container = &$container_stack[count($container_stack) - 1];
                        $is_empty_element = false;
                    }

                    break;

                case XMLReader::TEXT :
                case XMLReader::CDATA :
                    if (isset($container['#text'])) {
                        $container['#text'] .= $reader->value;
                    } else {
                        $container['#text'] = $reader->value;
                    }

                    break;

                case XMLReader::END_ELEMENT :
                    array_pop($container_stack);
                    $container = &$container_stack[count($container_stack) - 1];

                    break;

                default :
                    continue;
            }
        }

        $reader->close();

        if (empty($result)) {
            print_r($xml);
        }

        return empty($result) ? false : $result;
    }

    static public function getNumber($result, $expectedIssue)
    {
        if (!$expectedIssue) {
            $tmp = reset($result);
        } else {
            if (empty($result[$expectedIssue])) {
                // 没找到号码，一般是因为没有及时更新，这不应该视为出错
                return drawSources::failed(36, "issueList:$expectedIssue" . var_export($result, true)); // .
            }
            $tmp = $result[$expectedIssue];
        }

        return array('errno' => 0, 'issue' => $tmp['issue'], 'number' => $tmp['number'], 'original_number' => isset($tmp['original_number']) ? $tmp['original_number'] : '', 'openTime' => isset($tmp['openTime']) ? $tmp['openTime'] : '');
    }

    static public function failed($errno, $errstr)
    {
        /*
          switch ($errno)
          {
          case 11: // 网址错误
          return array('errno' => $errno, 'errstr' => "开奖源设置错误:$errstr");
          break;
          case 21: // 网站不可访问
          return array('errno' => $errno, 'errstr' => "网站不可访问:$errstr");
          break;
          case 22: // 解析错误
          return array('errno' => $errno, 'errstr' => "解析错误:$errstr");
          break;
          case 23: // 格式错误
          return array('errno' => $errno, 'errstr' => "格式错误:$errstr");
          break;
          case 36: // 未读取到奖期 不抛异常
          return array('errno' => $errno, 'errstr' => "未读取到奖期:$errstr");
          break;
          case 14: // 错误号码
          return array('errno' => $errno, 'errstr' => "错误号码:$errstr");
          break;
          default:
          die("unknown errno $errno");
          break;
          }
         */
        switch (substr($errno, 0, 1)) {
            case 1:// error 开奖源设置错误 14号码错误
                throw new exception2("$errstr", $errno);
                break;
            case 2:
                // warning级错误: 数据格式类错误，一般应报警
                throw new exception2("$errstr", $errno);
                break;
            case 3:
                // notice: 更新不及时，网络读取错误，这里不抛异常
                //throw new exception("$errstr", $errno);
                return array('errno' => $errno, 'errstr' => $errstr);
                break;
            default:
                throw new exception2("unknown error", 10);
                break;
        }
    }

    /**
     * 商城心跳监控
     * @author    davy
     * @return string
     */
    static public function eshopMonitor($eshopSetting)
    {
        if (!self::$wget) {
            self::$wget = new wget();
        }
        self::$wget->setInCharset('utf-8');

        $res = array();
        $i = 0;
        foreach ($eshopSetting['shops'] as $shopName => $shopSetting) {
            $res[$i]['shop'] = $shopName;
            $res[$i]['error'] = 1;
            try {
                if (!$contents = self::$wget->getContents('CURL', 'GET', $shopSetting['url'])) {
                    continue;
                }
            } catch (exception2 $e) {
                $i++;
                continue;
            }
            preg_match_all($shopSetting['pattern'], $contents, $matches);
            if (count($matches[1]) != 0) {
                $res[$i]['error'] = 0;
            }
            $i++;
        }

        return $res;
    }

}

?>
