<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class notices
{
    static public function getItem($id, $expired = -1, $status = 1)
    {
        $sql = 'SELECT * FROM notices WHERE notice_id = ' . intval($id);
        if ($expired != -1) {
            if ($expired == 0) {
                $sql .= " AND expire_time > '" . date('Y-m-d H:i:s') . "'";
            }
            else {
                $sql .= " AND expire_time < '" . date('Y-m-d H:i:s') . "'";
            }
        }
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }


    /**
     * author snow  修改,使用redis缓存
     * @param int $status
     * @param int $expired
     * @param int $type
     * @param int $start
     * @param int $amount
     * @return bool
     */
    static public function getItems($status = 1, $expired = -1, $type = -1,$start = -1, $amount = DEFAULT_PER_PAGE)
    {

        $tmp = is_array($type) ? implode('_', $type) : $type;
        $hashKey = 'noticesGetItems';
        $cacheKey = 'noticesGetItems' .  $status.'_'.$expired.'_'.$tmp.'_'.$start.'_'.$amount;
        //>>过期时间设置为1天
        $result   = redisHashGet($hashKey,$cacheKey,function () use ($status, $start, $expired, $amount, $type){
            $sql = 'SELECT * FROM notices WHERE 1';
            if ($status !== NULL) {
                $sql .= " AND status = " . intval($status);
            }
            if ($expired != -1) {
                if ($expired == 0) {
                    $sql .= " AND expire_time > '" . date('Y-m-d H:i:s') . "'";
                }
                else {
                    $sql .= " AND expire_time < '" . date('Y-m-d H:i:s') . "'";
                }
            }
            if ($type !== -1) {
                if(is_numeric($type)){
                    $sql .= " AND type = " . intval($type);
                } elseif (is_array($type)) {
                    $sql .= ' AND type in(' .  implode(',', $type) . ')';
                }
            }
            $sql .= ' ORDER BY is_stick DESC, notice_id DESC';
            if ($start > -1) {
                $sql .= " LIMIT $start, $amount";
            }
            return $GLOBALS['db']->getAll($sql);
        });

        return $result === false ? [] : $result;
    }

    static public function getItemsNumber($status = NULL, $type = 1)
    {
        $sql = 'SELECT count(*) AS count FROM notices WHERE 1';
        if ($status !== NULL) {
            $sql .= " AND status = " . intval($status);
        }

        $sql .= " AND type = " . $type;

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM notices WHERE notice_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'notice_id');
    }

    //首页显示5个公告
    static public function getIndexNews($amount = 5)
    {
        $sql = 'SELECT * FROM notices WHERE status = 1';
        $sql .= ' ORDER BY notice_id DESC';
        $sql .= " LIMIT $amount";
        $result = $GLOBALS['db']->getAll($sql, array(),'notice_id');

        return $result;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }
        $GLOBALS['mc']->flush();
        return $GLOBALS['db']->insert('notices', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }
        $GLOBALS['mc']->flush();
        return $GLOBALS['db']->updateSM('notices',$data,array('notice_id'=>$id));
    }

    static public function deleteItem($id, $realDelete = false)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new exception2('参数无效');
        }

        if ($realDelete) {
            $sql = "DELETE FROM notices WHERE notice_id = " . intval($id);
            $type ='d';
        }
        else {
            $sql = "UPDATE notices SET status = -1 WHERE notice_id = " . intval($id);
            $type = 'u';
        }
        $GLOBALS['mc']->flush();
        return $GLOBALS['db']->query($sql, array(), $type);
    }

}
?>