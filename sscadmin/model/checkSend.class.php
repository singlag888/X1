<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

class checkSend extends baseModel
{

    public function getItems($issueIds)
    {

        if (!is_array($issueIds)) {
            throw new exception2('参数无效');
        }

        $issues = $this->where([
            'issue_id' => ['IN', implode(',', $issueIds)]
        ])->select();

        return $issues;
    }

    /**
     * 作用:
     *  用于防止因为有奖期官方未开奖 导致后面奖期不开奖
     *
     * 规则:
     * 用于检测 开奖周期x2 时候之前有没有官方未开奖的
     * 奖检测到的奖期改为官方为开奖
     *
     */
    public function controlCheckSend()
    {

        $dt = date("Y-m-d");

        $noCheckIssuesNowDay = $this->_calUpCheckPrize($dt);


        if (!empty($noCheckIssuesNowDay)) {
            $issue_ids = array_values(array_column($noCheckIssuesNowDay, 'issue_id'));

            if (!empty($issue_ids)) {
                $sql = "UPDATE check_send set status_check_prize = 3 ,status_send_prize = 3 WHERE issue_id IN (" . implode(',', $issue_ids) . ")";
                $GLOBALS['db']->query($sql);
            }
        }
    }

    /**
     * 10分钟开一期的彩种id：1,3,4,8,2,5,6,7,12,19,20,14 检测时间 21分钟之前
     * 5分钟开一期的彩种id：17 ,23 ,26检测时间 11分钟之前
     * 低频彩id:10,15,9,21,22  不进行检测
     * 系统彩id: 25,18,13,11,16   不进行检查
     * 腾讯分分彩id: 24  不进行检查
     * @param $dt 属于哪天 格式 2018-10-01
     * @return array|null
     */
    private function _calUpCheckPrize($dt)
    {
        $sql = <<<SQL
SELECT max(issue_id) issue_id,max(earliest_input_time) as earliest_input_time,lottery_id,max(issue) issue FROM check_send 
WHERE status_check_prize=0 and belong_date='{$dt}' and lottery_id in (1,3,17,23,24,4,8,2,5,6,7,12,19,20,14,26) and earliest_input_time<date_sub(now(),INTERVAL 21 MINUTE) 
GROUP BY lottery_id;
SQL;

      return $GLOBALS['db']->getAll($sql);

    }

    public function getNeed2CheckPrizeIssue()
    {

        $res = array();
        $sql = 'SELECT MAX(issue_id) issue_id,lottery_id,MAX(issue) issue FROM check_send WHERE status_check_prize=0 AND earliest_input_time<NOW() GROUP BY lottery_id';
        $noCheckIssues = $GLOBALS['db']->getAll($sql);

        if (empty($noCheckIssues)) {
            return array();
        }

        $GLOBALS['redis']->pushPrefix()->select(REDIS_DB_COMMON_DATA);

        foreach ($noCheckIssues as $nok => $noCheckMiniIssue) {
            if ($issue = $GLOBALS['redis']->get('lottery_' . $noCheckMiniIssue['lottery_id'] . '_issue_' . $noCheckMiniIssue['issue'])) {
                $decodeIssue = json_decode($issue, true);
                if ($decodeIssue['code']) {
                    array_push($res, $decodeIssue);
                    unset($noCheckIssues[$nok]);
                }
            }
        }

        $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);
        //如果redis中遗漏了一些奖期在mini中查询这些checksend的奖期 是否开奖
        if (!empty($noCheckIssues)) {
            $sql = 'SELECT * FROM ssc_share.`issues_mini` WHERE `issue_id` IN (' . implode(',', array_keys(array_spec_key($noCheckIssues, 'issue_id'))) . ')';
            $noCheckMiniIssues = $GLOBALS['share_db']->getAll($sql);
            foreach ($noCheckMiniIssues as $noCheckMiniIssue) {
                if ($noCheckMiniIssue['code']) {
                    array_push($res, $noCheckMiniIssue);
                }
            }
        }

        return $res;
    }

    public function getNeed2SendPrizeIssue()
    {
        $sql = "SELECT MAX(issue_id) issue_id FROM check_send WHERE status_check_prize=2 AND status_send_prize=0 AND belong_date>='" . date('Y-m-d', strtotime("-7 day")) . "' AND earliest_input_time<NOW() GROUP BY lottery_id";
        $noSendIssues = $GLOBALS['db']->getAll($sql);

        if (empty($noSendIssues)) {
            return array();
        }

        $sql2 = "select * from ssc_share.issues_mini where issue_id in (" . implode(',', array_keys(array_spec_key($noSendIssues, 'issue_id'))) . ")";
        $issues = $GLOBALS['share_db']->getAll($sql2);

        if (empty($issues)) {
            return array();
        }
        foreach ($issues as $k => $issue) {
            if ($issue['code'] == '') {
                unset($issues[$k]);
            }
        }

        return $issues;
    }

    public function getNeed2RebateIssue($lotteryId)
    {

        if (!is_numeric($lotteryId)) {
            throw new exception2('参数无效');
        }

        $noRebateIssue = $this->field('issue')
            ->where([
                'lottery_id' => $lotteryId,
                'status_rebate' => 0,
                'earliest_input_time' => ['<', date('Y-m-d H:i:s')]
            ])->order('`issue_id` ASC')->select();

        return $noRebateIssue;
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('check_send', $data, array('issue_id' => $id));
    }

    static public function updateItemByLottery($lottery_id, $issue, $data)
    {
        if ($lottery_id <= 0 || !$issue || !$data) {
            throw new exception2('参数无效');
        }

        //issue_mmc不用更新status_rebate
        if ($lottery_id == 15) {
            return true;
        }

        $where = array();

        if ($lottery_id == 15) {
            $table = 'issue_mmc';
        } else {
            $table = 'check_send';
            $where = array('lottery_id' => $lottery_id);
        }

        if (is_array($issue)) {
            foreach ($issue as $v) {//循环为了防止死锁
                $where['issue'] = $v;
                $GLOBALS['db']->updateSM($table, $data, $where);
            }
        } else {
            $where['issue'] = $issue;
            $GLOBALS['db']->updateSM($table, $data, $where);
        }

        return true;
    }
}

