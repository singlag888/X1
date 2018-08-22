<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 自定义开奖策略类 修正伪随机缺陷
 *
 * 待进一步完善
 */
class drawStrategy
{

    static public function getPrizeCode($lottery, $issue = '', $projects = array())
    {
        $rank = 108;
        # TODO : 这里的明明只用 lottery_id 但是传入了整个彩种数据.
        switch ($lottery['lottery_id']) {
            case 11:    //YZFFC
            case 15:    //YZMMC
            case 18:
                # TODO : 外层其实切库切了很多次呀.
                $salt = config::getConfig('probability_adjust_index', 60);
                $openCode = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                $tmp = str_split($openCode);
                if (count($tmp) != count(array_unique($tmp))) {
                    if (rand(1, $salt) == 1) {
                        for ($i = 0; $i < 10; $i++) {
                            $openCode = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                            $tmp = str_split($openCode);
                            if (count($tmp) == count(array_unique($tmp))) {
                                break;
                            }
                        }
                        if ($i == 10) {
                            $tmp = rand(0, 9);
                            if (rand(1, 5) == 1) {
                                $openCode = $tmp . $tmp . $tmp . rand(0, 9) . rand(0, 9);
                            } elseif (rand(1, 5) == 2) {
                                $openCode = rand(0, 9) . $tmp . $tmp . $tmp . rand(0, 9);
                            } else {
                                $openCode = rand(0, 9) . rand(0, 9) . $tmp . $tmp . $tmp;
                            }
                        }
                    }
                }
                $result = array('number' => strval($openCode), 'rank' => $rank);
                break;
            case 13:    //快三分分彩
                $codes = array(rand(1, 6), rand(1, 6), rand(1, 6));
                sort($codes);
                $openCode = implode('', $codes);
                $result = array('number' => strval($openCode), 'rank' => $rank);
                break;
            case 16:    //11选5分分彩
                $sample = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11');
                $tmp = array();
                while (1) {
                    if (count(array_unique($tmp)) == 5) {
                        break;
                    }
                    $randIndex = abs(crc32(md5(microtime(true) . rand(0, 1000000)))) % count($sample);
                    $tmp[] = $sample[$randIndex];
                }
                $tmp = array_values(array_unique($tmp));
                $openCode = $tmp[0] . ' ' . $tmp[1] . ' ' . $tmp[2] . ' ' . $tmp[3] . ' ' . $tmp[4];
                $result = array('number' => strval($openCode), 'rank' => $rank);
                break;
            case 25:    //极速六合彩
                $sample = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49);
                $tmp = array();
                while (1) {
                    if (count(array_unique($tmp)) == 7) {
                        break;
                    }
                    $randIndex = abs(crc32(md5(microtime(true) . rand(0, 1000000)))) % count($sample);
                    $tmp[] = $sample[$randIndex];
                }
                $tmp = array_values(array_unique($tmp));
                shuffle($tmp);
                $openCode = $tmp[0] . ' ' . $tmp[1] . ' ' . $tmp[2] . ' ' . $tmp[3] . ' ' . $tmp[4] . ' ' . $tmp[5] . ' ' . $tmp[6];
                $result = array('number' => trim(strval($openCode)), 'rank' => $rank);
                break;
            default:
                throw new exception2('不支持的彩种');
                break;
        }
        return $result;
    }
}
