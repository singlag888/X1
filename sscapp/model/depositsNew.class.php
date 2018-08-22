<?php

use common\model\baseModel;

class depositsNew extends baseModel {
    protected $tableName='deposits';

    /**
     *
    SELECT

    FROM
    deposits a
    LEFT JOIN
    users b
    ON
    a.user_id=b.user_id
    WHERE
    a.user_id IN ('276','335','340') AND a.status = 8 AND a.finish_time >= '2017-10-17 00:00:00' AND a.finish_time <= '2017-10-17 23:59:59'
    ORDER BY
    deposit_id DESC
     * @param $uid
     * @param string $select
     */
    public function getOne($wheres,$select='*',$offset=0,$limit=DEFAULT_PER_PAGE,$orderby='deposit_id DESC'){
        $this->alias('a');
        $this->join('__USERS2__ b on b.user_id=a.user_id');
        $s=$this->field($select)
            ->where($wheres)
            ->order($orderby)
            ->limit($offset,$limit)
            ->select(true);
        dd($s);
    }
    public function getByUid($uid,$start_time,$end_time,$sortKey,$sortDirection,$offset=0,$limit=DEFAULT_PER_PAGE,$status=8){
        $select=['a.*','b.username','b.level','b.is_test','b.status AS user_status'];
        $orderby='deposit_id DESC';
        if($sortKey)$orderby=(in_array($sortKey,['username','level','is_test','status'])?'a.':'b.').$sortKey.($sortDirection==1?' ASC':' DESC');
        $wheres=[];
        if(is_array($uid)) $wheres['a.user_id']=['in',$uid];
        else $wheres['a.user_id']=(int)$uid;
        $wheres['a.status']=(int)$status;
        $wheres['a.finish_time']=[['>=', $start_time],['<=', $end_time],'and'];
//        $wheres['a.finish_time']=['>=', $start_time];
//        $wheres['a.finish_time']=['<=', $end_time];
//        dd($wheres);
        $res=$this->getOne($wheres,$select,$offset,$limit,$orderby);
    }
}