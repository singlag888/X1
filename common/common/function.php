<?php

/**
 * 公共函数库
 */

if (!function_exists('getStartOffset')) {

    /**
     * author snow 根据传入的页码,总页数,分页尺寸得到正确的分页起始码
     * @param $curPage      integer  当前页码
     * @param $totalCount   integer 总条数
     * @param int $pageSize integer 分页尺寸
     * @return mixed
     */
    function getStartOffset($curPage, $totalCount, $pageSize = DEFAULT_PER_PAGE)
    {
        //>>得到最大页码
        if (!is_numeric($curPage) || !is_numeric($totalCount) || !is_numeric($pageSize)) {
            return 0;
        }
        $maxPage = ceil($totalCount / $pageSize);
        $curPage = $curPage < 1 ? 1 : ($curPage > $maxPage ? ($maxPage != 0 ? $maxPage : 1) : $curPage);
        return ($curPage - 1) * $pageSize;

    }
}

if (!function_exists('calculateBackWater')) {

    /**
     * author  snow  根据传入用户编号  ,存款量,投注量,获取用户投注返佣
     * @param $user_id  int 用户id
     * @param $deposit  int 存款量
     * @param $package  int 提款量
     * @return int  返回计算出来的用户返佣
     */
    function calculateBackWater($user_id, $deposit, $package)
    {

        //>>根据配置取出进行返佣的条件
        $commissionDeposit = config::getConfig('commission_deposit');

        //>>存款必须大于最小额度才会有返佣
        if ($deposit < $commissionDeposit) {
            //>>没有返佣为0
            return 0;
        }
        //>>获取用户对应卡组
        $refGroup = cards::getUserRefGroupId($user_id);

        if (!$refGroup) {
            //>>如果不存在,出错返水为0异常:用户{$user_id}卡组不明
            return 0;
        }

        //默认卡组反水比例,100万 0.3%,500万以上 0.5%
        if ($package < 1000000) {
            if ($refGroup['commission_percentage'] == 0) {
                //>> "用户{$user_id}反水比例为0,跳过\n";
                return 0;
            }
            $percentage = $refGroup['commission_percentage'];
        } elseif ($package < 5000000) {
            $percentage = 0.003;
        } else {
            $percentage = 0.005;
        }
        return $percentage * $package;
    }

}

if (!function_exists('validateDateFormat')) {

    /**
     *  author snow 验证时间格式是否符合条件
     * @param $date  string 传入的时间
     * @param string $format 格式化的样式
     * @return bool
     */
    function validateDateFormat($date, $format = 'Y-m-d')
    {
        if (strtotime($date) === false) {
            return false;
        }
        return date($format, strtotime($date)) == $date;

    }
}

if (!function_exists('redisGet')) {

    /**
     * author snow 获取值如果不存在设置默认
     * @param string $key redis 键值
     * @param Closure | string | null $val 匿名函数获取的值 | 默认使用的值
     * @param int $expire 过期时间
     * @return bool | string | array
     */
    function redisGet($key, $val = null, $expire = 0)
    {
        //>>先获取缓存值
        $data = $GLOBALS['redis']->get($key);
        if ($data !== false) {
            //>>对取回的数据进行处理,如果是json字符串,转换后再返回
            return asJson($data);
        }

        //>> 是否有传入回调方法
        if ($val instanceof Closure) {
            $data = $val();
            //>>再判断是否有传入val值
        } else if ($val !== null) {
            $data = $val;
        }

        if (!empty($data)) {

            //>>写入缓存
            if ($expire > 0) {
                $GLOBALS['redis']->setex($key, $expire, arrayOrObjectToJson($data));
            } else {
                $GLOBALS['redis']->set($key, arrayOrObjectToJson($data));
            }

            return $data;
        }

        return false;
    }
}

if (!function_exists('memcacheGet')) {

    /**
     * author snow 验证时间格式是否符合条件
     * @param string $prefix memcache 头
     * @param string $key memcache 键值
     * @param Closure | string $val 默认值,或者通过回调取得数据
     * @param  int $expire 过期时间
     * @return bool
     */
    function memcacheGet($prefix, $key, $val = null, $expire = 600)
    {

        //>>先获取缓存值
        if (($data = $GLOBALS['mc']->get($key)) !== false) {
            return asJson($data);
        }

        //>> 是否有传入回调方法
        if ($val instanceof Closure) {
            $data = $val();
            if ($data !== false) {
                //>>写入缓存
                $GLOBALS['mc']->set($prefix, $key, arrayOrObjectToJson($data), $expire);
                return $data;
            }

            return false;
        }

        //>>再判断是否有传入val值
        if ($val !== null) {
            $GLOBALS['mc']->set($prefix, $key, $val, $expire);
            return $val;
        }

        return false;
    }
}

if (!function_exists('array_encode')) {
    /**
     * author stone
     * @param String $string
     * @return array|bool
     * 字符串转数组，用以替换eval(),支持多维索引，键值对数组
     * 数组格式错误返回布尔false，成功解析返回数组
     * 2017-05-18
     */
    function array_encode($string)
    {
        //删除空格
        $string = str_replace(' ', '', $string);
        //容错空数组
        if ($string == 'array()') {
            return array();
        }
        //数组格式容错
        if (substr($string, 0, 6) == 'array(' && $string[strlen($string) - 1] == ')') {
            $Array = array();
            $array = substr($string, 6, strlen($string) - 7);
            //容错，不要分隔小数组中的逗号
            if (strpos($array, 'array(') === 0) {
                $array = str_replace(",array", ",#array", $array);
                $array = explode(',#', $array);
            } else {
                $array = explode(',', $array);
            }
            if (strpos($array[0], 'array(') === 0) {
                //小数组
                foreach ($array as $key => &$value) {
                    $Array[] = array_encode($value);
                }
            } elseif (strpos($array[0], '=>')) {
                //键值对数组
                foreach ($array as $key => &$value) {
                    //容错，不要分隔小数组中的键值符号
                    if (strpos($value, 'array(') > 0) {
                        $value = str_replace("=>array", "=>#array", $value);
                        $value = explode('=>#', $value);
                    } else {
                        $value = explode('=>', $value);
                    }
                    if (!(strpos($value[1], '\'') === 0 || strpos($value[1], '"') === 0 || strpos($value[1], 'array') === 0)) {
                        if (strpos($value[1], '.') > 0) {
                            //双精度
                            $Array[preg_replace("/'|\"/", "", $value[0])] = (double)$value[1];
                        } else {
                            //整形
                            $Array[preg_replace("/'|\"/", "", $value[0])] = (int)$value[1];
                        }
                    } elseif (strpos($value[1], 'array') === 0) {
                        //小数组
                        $Array[preg_replace("/'|\"/", "", $value[0])] = array_encode($value[1]);
                    } else {
                        //字符串
                        $Array[preg_replace("/'|\"/", "", $value[0])] = preg_replace("/'|\"/", "", $value[1]);
                    }
                }
            } else {
                //索引数组
                foreach ($array as $key => &$value) {
                    if (!(strpos($value, '\'') === 0 || strpos($value, '"') === 0 || strpos($value, 'array') === 0)) {
                        if (strpos($value, '.') > 0) {
                            //双精度
                            $Array[] = (double)$value;
                        } else {
                            //整形
                            $Array[] = (int)$value;
                        }
                    } elseif (strpos($value, 'array') === 0) {
                        //小数组
                        $Array[] = array_encode($value);
                    } else {
                        //字符串
                        $Array[] = preg_replace("/'|\"/", "", $value);
                    }
                }
            }
            return $Array;
        } else {
            return false;
        }
    }
}

if (!function_exists('asJson')) {

    /**
     * author snow 对一个字符串进行判断 是否是合法json 格式,如果是返回解码后的数组
     * 如果不是,返回原字符串.
     * @param $str string 传入的字符串
     * @param bool $flag 标志,如果是true ,返回数组,false返回对象
     * @return mixed
     */
    function asJson($str, $flag = true)
    {
        if (!is_string($str) || is_numeric($str)) {
            return $str;
        }

        $tmp = json_decode($str, $flag);
        return is_null($tmp) ? $str : $tmp;
    }

}

if (!function_exists('export_data_to_csv')) {

    /**
     * author snow 全量导出数据到csv文件
     * @param $headList  array 标题
     * @param $excelData array  数据
     * @param $fileName  string 导出的文件名称
     * @return array  返回结果 包含错误信息与文件路径 ,以供下载`
     */
    function export_data_to_csv($headList, $excelData, $fileName = '结果')
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        if (!file_exists(ROOT_PATH . 'sscadmin/upload/file')) {
            //>>如果文件夹不存在
            if (!mkdir(ROOT_PATH . 'sscadmin/upload/file',0777, true)) {
                //>>创建目录失败
                die(json_encode(['flag' => false, 'data' => ['error' => '创建目录失败']]));
            };
        }
        //>>删除原来的文件,
        $filePath = ROOT_PATH . 'sscadmin/upload/file/' . $fileName . '.csv';
        if (file_exists($filePath)) {
            //>>snow 这个方法有可能会报错,现在没有好的处理方法,只能抑制
            @unlink($filePath);
        }
        $fp = fopen(ROOT_PATH . 'sscadmin/upload/file/' . $fileName . '.csv', 'a');
        //输出Excel列名信息
        foreach ($headList as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            //>>snow 这个方法有可能会报错,现在没有好的处理方法,只能抑制
            $headList[$key] = @iconv('utf-8', 'GBK//IGNORE', $value);
        }
        //将数据通过fputcsv写到文件句柄
        if (fputcsv($fp, $headList) === false) {
            fclose($fp);
            return [
                'flag' => false,
                'error' => '首行数据出错'
            ];
        };
        unset($headList);
        $count = count($excelData);
        //>>循环写入数据到csv文件
        for ($i = 0; $i < $count; $i++) {

            $row = $excelData[$i];
            foreach ($row as $key => $value) {
                $value = $value === '' ? '空' : $value;
                //>>snow 这个方法有可能会报错,现在没有好的处理方法,只能抑制
                $row[$key] = @iconv('utf-8', 'GBK//IGNORE', $value);
            }

            if (fputcsv($fp, $row) === false) {
                fclose($fp);
                return [
                    'flag' => false,
                    'error' => '写入第' . $i . '行数据出错',
                ];
            };
            unset($row);
        }

        //>>关闭文件句柄
        fclose($fp);
        //>>设计返回数据
        return [
            'flag' => true,
            'fileName' => 'upload/file/' . $fileName . '.csv',
        ];
    }
}

if (!function_exists('redisDelHashForKey')) {

    /**
     * author snow
     * 删除指定hash 表中的所有字段的数据
     * @param $key
     * @return mixed
     */
    function redisDelHashForKey($key)
    {
        //>>里面要求传入字符串.
        return $GLOBALS['redis']->hdel($key, implode(',', $GLOBALS['redis']->hKeys($key)));
    }
}

if (!function_exists('redisHashGet')) {

    /**
     * author snow 获取或者设置hash字段的值,可以通过回调函数设置值.
     * @param string $key
     * @param string $field
     * @param Closure | null | int | string $val
     * @return bool|mixed
     */
    function redisHashGet($key, $field, $val = null)
    {
        //>>先获取缓存值
        if (($data = $GLOBALS['redis']->hGet($key, $field)) !== false) {
            //>>对取回的数据进行处理,如果是json字符串,转换后再返回
            return asJson($data);
        }
        //>> 是否有传入回调方法
        if ($val instanceof Closure) {
            $data = $val();
            if (!empty($data)) {
                //>>写入缓存
                $GLOBALS['redis']->hSet($key, $field, arrayOrObjectToJson($data));
                return $data;
            }

            return false;
        }
        //>>再判断是否有传入val值
        if ($val !== null) {
            $GLOBALS['redis']->hSet($key, $field, $val);
            return false;
        }


        return false;

    }
}

if (!function_exists('arrayOrObjectToJson')) {

    /**
     * author snow q转换数组或者对象为json字符串,如果不是,则原样返回.
     * @param $data
     * @return string
     */
    function arrayOrObjectToJson($data)
    {
        return is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
    }
}


if (!function_exists('db_transaction')) {

    /**
     * author  snow 使用匿名函数实现事务.
     * @param Closure $closure
     * @return bool|mixed  失败返回false ,成功返回期望返回的结果
     */
    function db_transaction(Closure $closure)
    {
        $GLOBALS['db']->startTransaction();
        $result = $closure();
        if ($result === false) {
            $GLOBALS['db']->rollback();
            return false;
        }
        $GLOBALS['db']->commit();
        return $result;

    }
}

if (!function_exists('memory_usage')) {
    /**
     * author snow 获取当前脚本内存使用峰值
     * @return string
     */
    function memory_usage()
    {
        $memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        return $memory;
    }
}


if (!function_exists('getFirstCharter')) {
    /**
     * author snow 输入单个字符 ,获取单个字符的拼音首字母
     * 如果是拼音和数字,原样返回
     * @param $str
     * @return null|string
     */
    function getFirstCharter($str)
    {
        if (empty($str)) {
            return '';
        }
        $fchar = ord($str{0});
        if ($fchar >= ord('A') && $fchar <= ord('z')) {
            return strtoupper($str{0});
        }

        if ($fchar >= ord('0') && $fchar <= ord('9')) {
            return strtoupper($str{0});
        }

        //>>判断是否是中文字符. 因为有可能出现notic错误 ,所以使用这个方法来进行转换.
        $s1 = @iconv('UTF-8','GB2312//IGNORE',$str );
        $s2 = @iconv('GB2312','UTF-8//IGNORE',$s1);


        $s      = $s2 == $str ? $s1 : $str;
        $asc    = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc >= -20319 && $asc <= -20284) return 'A';
        if ($asc >= -20283 && $asc <= -19776) return 'B';
        if ($asc >= -19775 && $asc <= -19219) return 'C';
        if ($asc >= -19218 && $asc <= -18711) return 'D';
        if ($asc >= -18710 && $asc <= -18527) return 'E';
        if ($asc >= -18526 && $asc <= -18240) return 'F';
        if ($asc >= -18239 && $asc <= -17923) return 'G';
        if ($asc >= -17922 && $asc <= -17418) return 'H';
        if ($asc >= -17417 && $asc <= -16475) return 'J';
        if ($asc >= -16474 && $asc <= -16213) return 'K';
        if ($asc >= -16212 && $asc <= -15641) return 'L';
        if ($asc >= -15640 && $asc <= -15166) return 'M';
        if ($asc >= -15165 && $asc <= -14923) return 'N';
        if ($asc >= -14922 && $asc <= -14915) return 'O';
        if ($asc >= -14914 && $asc <= -14631) return 'P';
        if ($asc >= -14630 && $asc <= -14150) return 'Q';
        if ($asc >= -14149 && $asc <= -14091) return 'R';
        if ($asc >= -14090 && $asc <= -13319) return 'S';
        if ($asc >= -13318 && $asc <= -12839) return 'T';
        if ($asc >= -12838 && $asc <= -12557) return 'W';
        if ($asc >= -12556 && $asc <= -11848) return 'X';
        if ($asc >= -11847 && $asc <= -11056) return 'Y';
        if ($asc >= -11055 && $asc <= -10247) return 'Z';
        return '';
    }

}

if (!function_exists('getAllCharter')) {
    /**
     * author snow 把输入的文字转换成拼音首字母
     * @param $str
     * @return string
     */
    function getAllCharter($str)
    {
        if (empty($str)) {
            return '';
        }

        $str = filter_non_chinese($str);
        $len = mb_strlen($str);
        $upLower = '';
        for ($i = 0; $i< $len; $i++) {
            $tmp = mb_substr($str, $i, 1);
            $upLower .= getFirstCharter($tmp);
        }
        return $upLower;
    }

}


if (!function_exists('filter_non_chinese')) {
    /**
     * author snow 过滤掉非中文字符
     * @param $str
     * @return mixed|string
     */
    function filter_non_chinese($str) {

        //转换 GB2312 -> UTF-8  //>>搜索中文与英文.
        preg_match_all('/[\x{4e00}-\x{9fff}a-zA-Z0-9]+/u', $str, $matches);
        $str = implode('', $matches[0]);
        $encode = mb_detect_encoding($str, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        //>>2 将字符编码改为utf-8
        return  mb_convert_encoding($str, 'UTF-8', $encode);
    }
}


if (!function_exists('getBankListFirstCharter')) {

    /**
     * author snow 把银行卡转换为中文与首字母对应
     * @param $bankList
     * @return array
     */
    function getBankListFirstCharter($bankList) {
        $bank = [];
        if (!is_array($bankList) || empty($bankList)) {
            return [];
        }
        foreach ($bankList as $key => $value) {
            $bank[$key] = [
                'id' => $key,
                'name' => $value,
                'firstCharter' => getAllCharter($value),
            ];
        }
        return $bank;
    }

}

if (!function_exists('getCardGroupName')) {

    /**
     * 获取卡组名称
     * @param $ref_group_id
     * @param $cards_groups
     * @return string
     */
    function getCardGroupName($ref_group_id, $cards_groups)
    {
        if (is_string($ref_group_id)) {
            $ids = explode(',', $ref_group_id);
            $name = [];
            if (!empty($ids)) {
                foreach ($ids as $val) {
                    $name[] = isset($cards_groups[$val]) ? $cards_groups[$val]['name'] : '';
                }
                return empty($name) ? '暂无' : implode(',', $name);
            }
        }
        return '暂无';
    }
}

if (!function_exists('createGameTotalReportForDay')) {

    /**
     * author snow 生成总账报表默认数据 一天的.
     * @return array
     */
    function createGameTotalReportForDay()
    {
        return [
            1                   =>  ['amount' => 0, 'num' => 0],
            2                   =>  ['amount' => 0, 'num' => 0],
            3                   =>  ['amount' => 0, 'num' => 0],
            4                   =>  ['amount' => 0, 'num' => 0],
            5                   =>  ['amount' => 0, 'num' => 0],
            6                   =>  ['amount' => 0, 'num' => 0],
            102                 =>  ['amount' => 0, 'num' => 0],
            503                 =>  ['amount' => 0, 'num' => 0],
            'deposit'           =>  ['amount' => 0, 'num' => 0],
            'withdraw'          =>  ['amount' => 0, 'num' => 0],
            'sum_amount'        =>  0,
            'sum_prize'         =>  0,
            'sum_win'           =>  0,
            'top_num'           =>  0,
            'new_top_num'       =>  0,
            'new_user_num'      =>  0,
            'first_deposit_num' =>  0,
        ];
    }
}

if (!function_exists('createGameTotalReportForMoreDay')) {

    /**
     * author snow  生成总账报表 开始时间到结束时间 的 默认数据
     * @param $start
     * @param $end
     * @return array
     */
    function createGameTotalReportForMoreDay($start, $end)
    {
        $start = strtotime($start);
        $end   = strtotime($end);
        $tmp = [];
        $i = 0;
        while (true) {
            $now = $end - ($i * 3600 * 24);
            if ($now < $start) {
                break;
            }
            $tmp[date('Y-m-d', $now)] = createGameTotalReportForDay();
            ++$i;
        }
        return $tmp;
    }
}

if (!function_exists('array_index')) {
    function array_index($arr, $index)
    {
        $data = [];
        foreach ($arr as &$item) {
            $data[$item[$index]] = $item;
        }
        return $data;
    }
}




