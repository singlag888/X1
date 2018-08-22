<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * ===========================================================================
 * 关于user_prize_groups表 准备废弃这种固定奖金模式
 * 其实只需要实现变动奖金即可，因为1700+10%==1800+5%==1900+0%
 * 因此，这个表只需记录每个用户在每个彩种的返水值rebate即可
 * user_id  lottery_id  rebate
 * 1        1           0.125
 * 1        2           0.10
 * 2        1           0.12
 * 2        2           0.09
 *
 * 所以，固定奖金组的作用已经没有了，只需记录每个用户在每个彩种的返点即可，另建一表名为user_rebates
 * ===========================================================================
 */
class prizes
{
    static public function getItem($id)
    {
        $cacheKey = $id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM prizes WHERE prize_id = ' . intval($id);
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 7200);
        }

        return $result;
    }

    // 不再支持固定奖金组，pg_id一般指基本奖金组，无需指定，一个彩种只有一个基本奖金组，因此可以去掉$pg_id
    static public function getItems($lottery_id = 0, $method_id = 0, $pg_id = 0, $level = 0, $style = 0)
    {
        $cacheKey = __FUNCTION__ . $lottery_id . '_' . $method_id . '_' . $pg_id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT a.* FROM prizes a LEFT JOIN prize_groups b ON a.pg_id = b.pg_id WHERE b.is_base = 1';  //只查基本奖金组
            if ($lottery_id != 0) {
                $sql .= " AND a.lottery_id = " . intval($lottery_id);
            }
            if ($method_id != 0) {
                $sql .= " AND a.method_id = " . intval($method_id);
            }
            if ($pg_id != 0) {
                if (is_array($pg_id)) {
                    $sql .= " AND a.pg_id IN(" . implode(',', $pg_id) .")";
                }
                else {
                    $sql .= " AND a.pg_id = " . intval($pg_id);
                }
            }
            if ($level != 0) {
                $sql .= " AND a.level = " . intval($level);
            }
            $sql .= ' ORDER BY a.prize_id ASC';
            $result = $GLOBALS['db']->getAll($sql, array(),'prize_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, 7200);
        }

        if ($style == 1) {
            $tmp = array();
            foreach ($result as $k => $v) {
                $tmp[$v['method_id']][$v['level']] = $v;
            }
            $result = $tmp;
        }
        elseif ($style == 2) {
            $tmp = array();
            foreach ($result as $k => $v) {
                $tmp[$v['lottery_id']][$v['method_id']][$v['level']] = $v;
            }
            $result = $tmp;
        }

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $noCacheKeys = array();
        $cacheKeys = $ids;
        $result = $GLOBALS['mc']->gets(__CLASS__, $cacheKeys, $noCacheKeys);
//dump($result, $noCacheKeys);
        if ($noCacheKeys) {
            $sql = 'SELECT * FROM prizes WHERE prize_id IN(' . implode(',', $noCacheKeys) . ')';
            $noCacheResult = $GLOBALS['db']->getAll($sql, array(),'prize_id');
            $GLOBALS['mc']->sets(__CLASS__, $noCacheResult, 7200);
            $result += $noCacheResult;
        }
        
        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('prizes', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('prizes',$data,array('prize_id'=>$id));
    }

    static public function deleteItem($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $sql = "DELETE FROM prizes WHERE prize_id = $id";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }


    //以下是奖金组模型
    static public function getGroup($id)
    {
        $sql = 'SELECT * FROM prize_groups WHERE pg_id = ' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    //取固定奖金组
    static public function getGroups($lottery_id = 0, $is_base = 0)
    {
        $sql = 'SELECT * FROM prize_groups WHERE 1';
        if ($lottery_id != 0) {
            if (is_array($lottery_id)) {
                $sql .= " AND lottery_id IN(" . implode(',', $lottery_id) .")";
            }
            else {
                $sql .= " AND lottery_id = " . intval($lottery_id);
            }
        }
        if ($is_base !== NULL) {
            $sql .= " AND is_base = " . intval($is_base);
        }
        $sql .= ' ORDER BY pg_id ASC';
        $result = $GLOBALS['db']->getAll($sql, array(),'pg_id');

        return $result;
    }

    static public function getBaseGroup($lottery_id)
    {
        $sql = 'SELECT * FROM prize_groups WHERE lottery_id = ' . intval($lottery_id) . " AND is_base = 1";

        return $GLOBALS['db']->getRow($sql);
    }

    static public function addGroup($data)
    {
        if (!is_array($data) || !isset($data['lottery_id']) || !isset($data['name']) || !isset($data['prize']) || !isset($data['top_rebate']) || !isset($data['max_top_rebate'])) {
            throw new exception2('参数无效');
        }

        $GLOBALS['db']->startTransaction();
        //插入奖金组表
        $prizeGroupData = array(
                'lottery_id' => $data['lottery_id'],
                'name' => $data['name'],
                'disp_name' => $data['disp_name'],
                'description' => $data['description'],
                'is_base' => $data['is_base'],
                'max_top_rebate' => $data['max_top_rebate'],
                );
        if (!$GLOBALS['db']->insert('prize_groups', $prizeGroupData)) {
            $GLOBALS['db']->rollback();
            throw new exception2("事务失败1");
        }
        $pg_id = $GLOBALS['db']->insert_id();

        //每个玩法的每个奖级的具体奖金插入奖金表
        foreach ($data['prize'] as $method_id => $prizes) {
            foreach ($prizes as $level => $prize) {
                $prizeData = array(
                    'lottery_id' => $data['lottery_id'],
                    'pg_id' => $pg_id,
                    'method_id' => $method_id,
                    'level' => $level,
                    'prize' => $prize,
                    'top_rebate' => $data['top_rebate'][$method_id],
                );
                if (!prizes::addItem($prizeData)) {
                    $GLOBALS['db']->rollback();
                    throw new exception2("事务失败2");
                }
            }
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2("提交事务失败");
        }

        return true;
    }

    static public function updateGroup($pg_id, $data)
    {
        if (!is_numeric($pg_id) || !is_array($data) || !isset($data['lottery_id']) || !isset($data['name']) || !isset($data['prize']) || !isset($data['top_rebate']) || !isset($data['max_top_rebate'])) {
            throw new exception2('参数无效');
        }

        $GLOBALS['db']->startTransaction();
        //更新奖金组
        $prizeGroupData = array(
                //'lottery_id' => $data['lottery_id'],
                'name' => $data['name'],
                'disp_name' => $data['disp_name'],
                'description' => $data['description'],
                'is_base' => $data['is_base'],
                'max_top_rebate' => $data['max_top_rebate'],
                );

        if ($GLOBALS['db']->updateSM('prize_groups', $prizeGroupData, array('pg_id' => $pg_id)) === false) {
            $GLOBALS['db']->rollback();
            throw new exception2("事务失败1"); 
        }

        //每个玩法的每个奖级的具体奖金插入奖金表
        foreach ($data['prize'] as $method_id => $prizes) {
            foreach ($prizes as $level => $prize) {
                if (prizes::getItems($data['lottery_id'], $method_id, $pg_id, $level)) {

                    if ($GLOBALS['db']->updateSM('prizes', array('prize'=>$prize,'top_rebate'=>$data['top_rebate'][$method_id]),array('pg_id'=>$pg_id,'method_id'=>$method_id,'level'=>$level)) === false) {
                        $GLOBALS['db']->rollback();
                        throw new exception2("事务失败2");
                    }
                }
                else {
                    $tmp = array(
                        'lottery_id' => $data['lottery_id'],
                        'pg_id' => $pg_id,
                        'method_id' => $method_id,
                        'level' => $level,
                        'prize' => $prize,
                        'top_rebate' => $data['top_rebate'][$method_id],
                    );
                    prizes::addItem($tmp);
                }
            }
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2("提交事务失败");
        }

        return true;
    }


}
?>