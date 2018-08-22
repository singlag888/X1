<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userPrizeGroups
{
    static public function getItem($id, $status = 1)
    {
        $sql = 'SELECT * FROM user_prize_groups WHERE upg_id = :upg_id';
        $paramArr = array();
        $paramArr[':upg_id'] = intval($id);
        if ($status != -1) {
            $sql .= ' AND status= :status';
            $paramArr[':status'] = intval($status);
        }

        return $GLOBALS['db']->getRow($sql , $paramArr);
    }

    //status为0禁用 1启用
    static public function getItems($user_id = -1, $lottery_id = -1, $status = 1)
    {
        $sql = 'SELECT a.*,b.name AS pg_name FROM user_prize_groups a LEFT JOIN prize_groups b ON a.pg_id = b.pg_id WHERE 1';
        if ($user_id != -1) {
            if (is_array($user_id) && count($user_id)) {
                $sql .= " AND a.user_id IN(" . implode(',', $user_id) .")";
            }
            else {
                $sql .= " AND a.user_id = " . intval($user_id);
            }
        }

        if ($lottery_id != -1) {
            if (is_array($lottery_id)) {
                $sql .= " AND a.lottery_id IN(" . implode(',', $lottery_id) .")";
            }
            else {
                $sql .= " AND a.lottery_id = " . intval($lottery_id);
            }
        }
        if ($status != -1) {
            $sql .= ' AND status= ' . intval($status);
        }
        $sql .= ' ORDER BY upg_id ASC';
//logdump($sql);
        $result = $GLOBALS['db']->getAll($sql, 'upg_id');

        return $result;
    }

    //得到用户某个彩种某个奖金组具体返点
    static public function getUserRebate($user_id, $lottery_id, $pg_id, $status = 1)
    {
        if (!is_numeric($user_id) || !is_numeric($lottery_id) || !is_numeric($pg_id)) {
            throw new exception2('invalid args');
        }
        $sql = "SELECT * FROM user_prize_groups WHERE user_id = $user_id AND lottery_id = $lottery_id AND pg_id = $pg_id";
        if ($status != -1) {
            $sql .= ' AND status= ' . intval($status);
        }
        if (!$result = $GLOBALS['db']->getRow($sql)) {
            return NULL;
        }

        return $result['rebate'];
    }

    //批量得到一批用户某个彩种某个奖金组具体返点 通常用于查询某用户的所有上级返点
    static public function getUserRebates($user_ids, $lottery_id, $pg_id, $status = 1)
    {
        if (!is_array($user_ids) || !is_numeric($lottery_id) || !is_numeric($pg_id)) {
            throw new exception2('invalid args');
        }
        foreach ($user_ids as $v) {
            if (!$v || !is_numeric($v)) {
                throw new exception2('invalid args');
            }
        }
        $sql = "SELECT * FROM user_prize_groups WHERE user_id IN(" . implode(',', $user_ids) .") AND lottery_id = $lottery_id AND pg_id = $pg_id";
        if ($status != -1) {
            $sql .= ' AND status= ' . intval($status);
        }
        $sql .= ' ORDER BY user_id ASC';    //必须的，按代理层次由上往下排列

        $tmp = $GLOBALS['db']->getAll($sql, 'user_id');
        $result = array();
        foreach ($user_ids as $v) {
            if (isset($tmp[$v])) {
                $result[$v] = $tmp[$v]['rebate'];
            }
        }
        arsort($result); //保证按从大到小的顺序排列，保留数组下标

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data) || !isset($data['lottery_id']) || !isset($data['user_id']) || !isset($data['rebate'])) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->insert('user_prize_groups', $data);
    }

    /**
     *
     * @param array $newGroups 数据结构 array('1,2,3','user_id,lottery_id,pg_id')
     */
    static public function updateUserPrizeGroups($newGroups)
    {
        //得到总代现有的奖金组
        // $tops = users::getUserTree(0);
        $tops = users::getUserTreeField([
            'field' => ['user_id'],
            'parent_id' => 0,
        ]);
        $userPrizeGroups = userPrizeGroups::getItems(array_keys($tops));
        $curGroups = array();
        foreach ($userPrizeGroups as $v) {
            $curGroups[$v['upg_id']] = "{$v['user_id']},{$v['lottery_id']},{$v['pg_id']}";
        }

        //新加的，应全新添加
        if ($new2Add = array_diff($newGroups, $curGroups)) {
//dump($new2Add);
            foreach ($new2Add as $v) {
                list($user_id, $lottery_id, $pg_id) = explode(',', $v);
                //得到返水
                if (!$prizeGroup = prizes::getGroup($pg_id)) {
                    throw new exception2("找不到奖金组");
                }

                //如果有数据只是禁用了就设置为启用
                if ($upg = self::getUserPrizeGroup($user_id, $lottery_id, $pg_id, -1)) {
                    if (!userPrizeGroups::updateItem($upg['upg_id'], array('status' => 1))) {
                        throw new exception2("更新奖金组出错");
                    }
                }
                else {  //否则加条新的
                    $data = array(
                        'user_id' => $user_id,
                        'lottery_id' => $lottery_id,
                        'pg_id' => $pg_id,
                        'rebate' => $prizeGroup['max_top_rebate'],
                        'status' => 1,  //0禁用 1启用
                    );
                    if (!userPrizeGroups::addItem($data)) {
                        throw new exception2("添加奖金组出错");
                    }
                }

            }
        }

        //取消选中的，应标记为禁用
        if ($new2Disable = array_diff($curGroups, $newGroups)) {
//dump($new2Disable);
            foreach ($new2Disable as $upg_id => $v) {
                if (!userPrizeGroups::updateItem($upg_id, array('status' => 0))) {
                    throw new exception2("添加奖金组出错");
                }
            }
        }

        return true;
    }

    static public function updateItem($upg_id, $data)
    {
        if (!is_numeric($upg_id) || !is_array($data)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->updateSM('user_prize_groups',$data,array('upg_id'=>$upg_id));
    }


}
?>