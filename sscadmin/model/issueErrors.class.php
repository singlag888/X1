<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

class issueErrors
{
    static public function getItem($id)
    {
        $sql = 'SELECT * FROM issue_errors WHERE ie_id = ' . intval($id);

        return $GLOBALS['share_db']->getRow($sql);
    }

    static public function getItems($lottery_id = 0, $issue = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT i.*,a.username AS admin_user,l.cname AS lottery_name FROM issue_errors AS i LEFT JOIN admins AS a ON a.admin_id=i.admin_id LEFT JOIN lottery AS l ON l.lottery_id=i.lottery_id WHERE 1';
        if ($lottery_id != 0) {
            $sql .= " AND i.lottery_id = " . intval($lottery_id);
        }
        if ($issue != '') {
            $sql .= " AND i.issue = '$issue'";
        }
        $sql .= " AND i.status = 1";    //只查正常记录
        $sql .= ' ORDER BY ie_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['share_db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber()
    {
        $sql = 'SELECT count(*) AS count FROM issue_errors WHERE 1';
        $result = $GLOBALS['share_db']->getRow($sql);

        return $result['count'];
    }

    /**
     * 根据登记时间查系统发现的官方提前开奖记录
     * @param date_time $date_time  大于此时间系统报出的官方提前开奖被检索出来
     * return array()
     */
    static public function getItemsByEarlyOpenTime($date_time)
    {
        if (empty($date_time)) {
            return false;
        }
        $sql = "SELECT lt.cname, i.issue, i.open_time FROM issue_errors i LEFT JOIN lottery lt ON i.lottery_id = lt.lottery_id
				WHERE i.create_time > '{$date_time}' AND i.admin_id = 65535 AND i.type = 1";
        $result = $GLOBALS['share_db']->getAll($sql);
        return $result;
    }

    //检查是否有待处理的任务
    static public function getPendingError($lottery_id, $issue)
    {
        if (!is_numeric($lottery_id) || !preg_match('`\d[\d-]+`Ui', $issue)) {
            throw new exception2('参数无效');
        }
        $sql = "SELECT * FROM issue_errors WHERE lottery_id = {$lottery_id} AND issue = '{$issue}' AND (status_cancel_prize NOT IN(2,9) OR status_repeal NOT IN(2,9))";

        return $GLOBALS['share_db']->getRow($sql);
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('issue_errors', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('issue_errors',$data,array('ie_id'=>$id));
    }

    static public function deleteItem($id, $realDelete = false)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        if ($realDelete) {
            $sql = "DELETE FROM issue_errors WHERE ie_id = " . intval($id);
            $type = 'd';
        }
        else {
            $sql = "UPDATE issue_errors SET status = 0 WHERE ie_id = " . intval($id);
            $type = 'u';
        }

        return $GLOBALS['db']->query($sql, array(), $type);
    }

    //共享库后这里的业务只操作订单  不操作奖期相关表
    static public function processIssueError($lottery_id, $issue, $type, $real_code = '', $real_start_time = '', $admin_id = 0)
    {
        //判断输入数据
        if (!$lottery_id || !$issue || !in_array($type, array(1, 2, 3, 4))) {
            return false;
        }

        if (!$lottery = lottery::getItem($lottery_id)) {
            throw new exception2('彩种不存在或已停售');
        }
        //检查是否存在奖期
        if (!$issueInfo = issues::getItem(0, $issue, $lottery_id)) {
            throw new exception2('找不到奖期');
        }
        //一个奖期只允许一个异常情况
        // if (issueErrors::getItems($lottery_id, $issue)) {
        //     throw new exception2("奖期{$issue}已经添加了异常处理，不能再次处理");
        // }

        // $errorData = array(
        //     "lottery_id" => $lottery_id,
        //     "issue" => $issue,
        //     "type" => $type,
        //     "open_time" => $real_start_time,
        //     "create_time" => date("Y-m-d H:i:s"),
        //     "admin_id" => $admin_id,
        //     "old_code" => $issueInfo['code'],
        //     "old_status_code" => $issueInfo['status_code'],
        //     "old_status_rebate" => $issueInfo['status_rebate'],
        //     "old_status_check_prize" => $issueInfo['status_check_prize'],
        //     "old_status_send_prize" => $issueInfo['status_send_prize'],
        //     'code' => $real_code,
        //     'status_code' => 0,
        //     'status_rebate' => 0,
        //     'status_check_prize' => 0,
        //     'status_send_prize' => 0,
        //     'status_cancel_prize' => 0,
        //     'status_repeal' => 0,
        // );
        // //  增加到issue_errors 奖期异常 表去
        // if (!issueErrors::addItem($errorData)) {
        //     throw new exception2('添加记录失败');
        // }

        // $ie_id = $GLOBALS['db']->insert_id();
        /**
         * 记录3种异常情况至issue_errors表，并实时处理异常情况
         * 有以下三种异常情况：
         * 1.提前开奖:
         *      晚于这个时间的单子全部撤消;
         * 2.开奖号码错误：
         *      对于已中奖的单子撤消派奖;所有单子重新判断中奖;重新派奖
         * 3.官方未开奖:
         *      本期全部单子撤消;
         * 4.偶尔会有网络问题延迟，造成订单在任务结束后还没有中奖判断
         */
        if ($type == 1) {
            $packages = projects::getPackages($lottery_id, -1, -1, $issue, -1, 0, '', 0, $real_start_time, '', '', '', 0);
            foreach ($packages as $package) {
                game::cancelPackage($package, 9, $admin_id);
            }

            //异常处理任务结束
            // $data = array(
            //     'code' => '',
            //     'status_code' => 2,
            //     'status_rebate' => 2,
            //     'status_check_prize' => 2,
            //     'status_send_prize' => 2,
            //     'status_cancel_prize' => 2, //撤销派奖状态 0:未开始, 1=进行中, 2=已完成, 9=被忽略
            //     'status_repeal' => 2, //系统撤单状态 0:未开始, 1=进行中, 2=已完成, 9=被忽略
            // );
            // if (!issueErrors::updateItem($ie_id, $data)) {
            //     throw new exception2('更新记录失败');
            // }
        }
        elseif ($type == 2) {
            //2.1 对已中奖的订单撤消派奖
            $packages = projects::getPackages($lottery_id, -1, -1, $issue, -1, 0, '', 0, '', '', '', '', 0);
            foreach ($packages as $package) {
                if ($package['check_prize_status'] == 1) {
                    game::cancelPrize($lottery, $issueInfo, $package);
                }
            }

            //2.2 开出正确的号码
            // $resetData = array(
            //     'status_code' => 0,
            //     'code' => '',
            //     'status_check_prize' => 0,
            //     'status_send_prize' => 0,
            // );
            // if (issues::updateItem($issueInfo['issue_id'], $resetData) === false) {
            //     throw new exception2('更新失败');
            // }

            //删除错误的奖期历史
            // if (!issues::deleteIssueHistory($issueInfo['issue_id'])) {
            //     throw new exception2('清除原奖期记录出错');
            // }
            //重新录入号码 500意义为服务器内部错误:)
            // if (!issues::drawNumber($issueInfo['issue_id'], $real_code, 500, $admin_id)) {
            //     throw new exception2('录入号码错误');
            // }

            //重新取得要重新判断中奖的信息等
            $packages2 = projects::getPackages($lottery_id, -1, -1, $issue, -1, 0, '', 0, '', '', '', '', 0);
            $issueInfo2 = issues::getItem(0, $issue, $lottery_id);
            //把错误中奖的原订单的帐变删除
            $orderObj = new baseModel('orders');
            foreach ($packages2 as $v) {
                if($v['check_prize_status'] == 1){
                    $orderObj->where([
                        'type' => 308,
                        'from_user_id' => $v['user_id'],
                        'business_id' => $v['package_id']
                        ])->delete();
                }
            }
            //2.3 所有订单重新判断中奖 确定check_prize_status一定是正确的
            game::checkPrize($lottery, $issueInfo2, $packages2);

            // if (!issues::updateItem($issueInfo2['issue_id'], array('status_check_prize' => 2))) {
            //     throw new exception2('更新失败');
            // }
            //  重新取得要重新派奖的信息  因为上面checkPrize 更改了check_prize_status 状态,所以一定要再次取得,不然会再次派奖
            $packages3 = projects::getPackages($lottery_id, -1, -1, $issue, -1, 0, '', 0, '', '', '', '', 0);
            //2.4 所有订单重新派奖
            foreach ($packages3 as $package) {
                if ($package['check_prize_status'] == 1) {
                    game::sendPrize($lottery, $issueInfo2, $package);
                }
            }
            // if (!issues::updateItem($issueInfo2['issue_id'], array('status_send_prize' => 2))) {
            //     throw new exception2('更新失败');
            // }

            //2.5 异常处理任务结束
            // $data = array(
            //     'code' => $real_code,
            //     'status_code' => 2,
            //     'status_rebate' => 2,
            //     'status_check_prize' => 2,
            //     'status_send_prize' => 2,
            //     'status_cancel_prize' => 2, //0:未开始, 1=进行中, 2=已完成, 9=被忽略
            //     'status_repeal' => 9, //0:未开始, 1=进行中, 2=已完成, 9=被忽略
            // );
            // if (!issueErrors::updateItem($ie_id, $data)) {
            //     throw new exception2('更新记录失败');
            // }
        }
        elseif ($type == 3) {
            //官方未开奖
            $packages = projects::getPackages($lottery_id, -1, -1, $issue, -1, 0, '', 0, '', '', '', '', 0);
            foreach ($packages as $package) {
                game::cancelPackage($package, 9, $admin_id);
            }

            // if (issues::updateItem($issueInfo['issue_id'], array('status_code' => '3')) === false) {
            //     throw new exception2('更新错误');
            // }

            //异常处理任务结束
            // $data = array(
            //     'code' => '',
            //     'status_code' => 3,
            //     'status_rebate' => 0,
            //     'status_check_prize' => 0,
            //     'status_send_prize' => 0,
            //     'status_cancel_prize' => 2, //撤销派奖状态 0:未开始, 1=进行中, 2=已完成, 9=被忽略
            //     'status_repeal' => 9, //系统撤单状态 0:未开始, 1=进行中, 2=已完成, 9=被忽略
            // );
            // if (!issueErrors::updateItem($ie_id, $data)) {
            //     throw new exception2('更新记录失败');
            // }
        }
        elseif ($type == 4) {
            //重新取得要重新判断中奖的信息等
            $packages2 = projects::getPackages($lottery_id, -1, -1, $issue, -1, 0, '', 0, '', '', '', '', 0);
            $issueInfo2 = issues::getItem(0, $issue, $lottery_id);

            //2.3 所有订单重新判断中奖 确定check_prize_status一定是正确的
            game::checkPrize($lottery, $issueInfo2, $packages2);

            //  重新取得要重新派奖的信息  因为上面checkPrize 更改了check_prize_status 状态,所以一定要再次取得,不然会再次派奖
            $packages3 = projects::getPackages($lottery_id, -1, -1, $issue, -1, 0, '', 0, '', '', '', '', 0);
            //2.4 所有订单重新派奖
            foreach ($packages3 as $package) {
                if ($package['check_prize_status'] == 1) {
                    game::sendPrize($lottery, $issueInfo2, $package);
                }
            }

            //2.5 异常处理任务结束
            // $data = array(
            //     'code' => $real_code,
            //     'status_code' => 2,
            //     'status_rebate' => 2,
            //     'status_check_prize' => 2,
            //     'status_send_prize' => 2,
            //     'status_cancel_prize' => 2, //0:未开始, 1=进行中, 2=已完成, 9=被忽略
            //     'status_repeal' => 9, //0:未开始, 1=进行中, 2=已完成, 9=被忽略
            // );
            // if (!issueErrors::updateItem($ie_id, $data)) {
            //     throw new exception2('更新记录失败');
            // }
        }

        return true;
    }

}

?>