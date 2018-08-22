<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userGifts
{

    /**
     * 用于表示截止时间无限的一个时间
     */
    const MAX_TIME = '2050-01-01 00:00:00';
    //0不可用，完成任务发放1可用，完成任务前不可提现
    const GIFT_NOWITHDRAW = 1; //
    const GIFT_NOUSE = 0; //

    static public $types=[
        101, //签到
        102, //注册
        103, //首充
        104, //日盈利送
        105, //日亏损送
    ];

    static public function buildGiftLog($date, $amount)
    {
        return '-sg-' . $date . '=>' . $amount . '=sg=';
    }

    static public function getGiftLogs($log)
    {
        $result = array();
        preg_match_all("/-sg-(.+?)=sg=/", $log, $matches);
        if ($matches) {
            $dateGifts = $matches[1];
            foreach ($dateGifts as $dateGift) {
                $arrGift = explode('=>', $dateGift);
                if (count($arrGift) == 2) {
                    $result[$arrGift[0]] = $arrGift[1];
                }
            }
        }

        return $result;
    }

    static public function getItem($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM user_gifts WHERE ug_id = ' . intval($id);
        }
        elseif (is_string($id)) {
            $sql = 'SELECT * FROM user_gifts  WHERE ug_id = \'' . $id . '\'';
        }

        return $GLOBALS['db']->getRow($sql);
    }

    //取得报表 按UID , 日期
    static public function getItems($startTime = '', $endTime = '', $userId = '', $status = '', $type = '', $title = '', $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,u.username,u1.username as parentusername FROM user_gifts  AS a LEFT JOIN users AS u ON a.user_id=u.user_id LEFT JOIN users AS u1 ON u.parent_id=u1.user_id WHERE 1';
        if ($startTime !== '') {    //提案发起时间
            $sql .= " AND a.from_time >= '$startTime'";
        }
        if ($endTime !== '') {
            $sql .= " AND a.to_time <= '$endTime'";
        }
        if ($userId !== '') {
            $sql .= " AND a.user_id = '$userId'";
        }
        if ($status !== '') {
            $sql .= " AND a.status = '$status'";
        }
        if ($type !== '') {
            $sql .= " AND a.type = '$type'";
        }
        if ($title !== '') {
            $sql .= " AND a.title = '$title'";
        }
        $sql .= " ORDER BY ug_id DESC";
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }


    /**
     * @param string $startTime
     * @param string $endTime
     * @param string $userId
     * @param string $status
     * @param string $type
     * @param int $include_childs
     * @return mixed
     */
    static public function getTotalGift($startTime = '', $endTime = '', $userId = '', $status = '', $type = '', $include_childs=0)
    {
        $sql = 'SELECT TRUNCATE(COALESCE(SUM(a.gift),0),0) as gift,u.username, a.type, a.user_id  FROM user_gifts  AS a LEFT JOIN users AS u ON a.user_id=u.user_id WHERE 1';

        if ($startTime !== '') {    //提案发起时间
            $sql .= " AND a.from_time >= '$startTime'";
        }
        if ($endTime !== '') {
            $sql .= " AND a.to_time <= '$endTime'";
        }

        if($include_childs && $userId !== ''){
            // $teamUsers = users::getUserTree($userId, true, 1, 8);
            $teamUsers = users::getUserTreeField([
                'field' => ['user_id'],
                'parent_id' => $userId,
                'recursive' => 1,
                'status' => 8
            ]);
            $sql .= $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), ' AND a.user_id');
        }elseif($userId !== ''){
            $sql .= " AND a.user_id = '$userId'";
        }

        if ($status !== '') {
            $sql .= " AND a.status = '$status'";
        }
        if ($type !== '') {
            $sql .= " AND a.type = '$type'";
        }

        $sql .= " GROUP BY a.user_id,a.type";

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     * 团队即时报表使用
     * @param $userId
     * @param string $startTime
     * @param string $endTime
     * @return mixed
     */
    static public function getTeamTotalGift($userId, $startTime = '', $endTime = '',$freeze = -1)
    {
        $sql = 'SELECT TRUNCATE(COALESCE(SUM(a.gift),0),0) as gift,u.username, u.parent_tree, a.user_id  FROM user_gifts  AS a LEFT JOIN users AS u ON a.user_id=u.user_id WHERE 1';

        if ($startTime !== '') {
            $sql .= " AND a.from_time >= '$startTime'";
        }
        if ($endTime !== '') {
            $sql .= " AND a.to_time <= '$endTime'";
        }
        if($freeze == -1) {
            // $directUsers = users::getUserTree($userId, false, 0, 8);
            $directUsers = users::getUserTreeField([
                'field' => ['user_id'],
                'parent_id' => $userId,
                'includeSelf' => false,
                'status' => 8
            ]);
        }else{
            // $directUsers = users::getUserTree($userId, false, 0, 8,-1,'',1);
            $directUsers = users::getUserTreeField([
                'field' => ['user_id'],
                'parent_id' => $userId,
                'includeSelf' => false,
                'status' => 8,
                'freeze' => 1
            ]);
        }

        //初始化
        $teamTotal[$userId] = 0;
        foreach ($directUsers as $user_id=>$user){
            $teamTotal[$user_id] = 0;
        }
         if($freeze == -1) {
             // $teamUsers = users::getUserTree($userId, true, 1, 8);
             $teamUsers = users::getUserTreeField([
                 'field' => ['user_id'],
                 'parent_id' => $userId,
                 'recursive' => 1,
                 'status' => 8
             ]);
         }else{
             // $teamUsers = users::getUserTree($userId, true, 1, 8,-1,'',1);
             $teamUsers = users::getUserTreeField([
                 'field' => ['user_id'],
                 'parent_id' => $userId,
                 'recursive' => 1,
                 'status' => 8,
                 'freeze' => 1
             ]);
         }
        $sql .= $GLOBALS['db']->dbCreatIn(array_keys($teamUsers), ' AND a.user_id');

        $sql .= " AND a.status = 4";
        $sql .= " GROUP BY a.user_id";

        if($result = $GLOBALS['db']->getAll($sql))
        {
            foreach ($result as $userGift)
            {
                if($userGift['user_id'] == $userId){
                    $teamTotal[$userId] += $userGift['gift'];
                }
                else{
                    $parentIds = explode(',', $userGift['parent_tree']);
                    foreach ($directUsers as $user_id=>$user)
                    {
                        if(in_array($user_id, $parentIds) || $user_id == $userGift['user_id']){
                            $teamTotal[ $user_id ] += $userGift['gift'];
                            break;
                        }
                    }
                }
            }
        }
        
        return $teamTotal;
    }

    static public function getIpNumber($ip, $title = '', $startTime = '', $endTime = '', $userId = '')
    {
        $sql = "SELECT COUNT(*) AS count FROM user_gifts  WHERE 1";
        if ($title !== '') {
            $sql .= " AND title = '$title'";
        }
        $sql .= " AND apply_ip = '$ip'";
        if ($startTime !== '') {    //提案发起时间
            $sql .= " AND from_time >= '$startTime'";
        }
        if ($endTime !== '') {
            $sql .= " AND to_time <= '$endTime'";
        }
        if ($userId !== '') {
            $sql .= " AND user_id = '$userId'";
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsNumber($startTime = '', $endTime = '', $userId = '', $status = '', $type = '', $title = '')
    {
        $sql = "SELECT COUNT(*) AS count FROM user_gifts  WHERE 1";
        if ($startTime !== '') {    //提案发起时间
            $sql .= " AND from_time >= '$startTime'";
        }
        if ($endTime !== '') {
            $sql .= " AND to_time <= '$endTime'";
        }
        if ($userId !== '') {
            $sql .= " AND user_id = '$userId'";
        }
        if ($status !== '') {
            $sql .= " AND status = '$status'";
        }
        if ($type !== '') {
            $sql .= " AND type = '$type'";
        }
        if ($title !== '') {
            $sql .= " AND title = '$title'";
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('user_gifts', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('user_gifts', $data, array('ug_id' => $id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM user_gifts WHERE ug_id=" . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * 用一个事务类似审核优惠
     * @param type $id
     * @return type
     */
    static public function sendUserGifts($id, $admin_id, $notes = '', $gift = 0, $userGiftData = array(), $verify_time = '', $finish_time = '', $type = 107)
    {
        if (!is_numeric($id) || !is_numeric($admin_id)) {
            throw new exception2('参数无效');
        }
        if (!is_array($userGiftData)) {
            throw new exception2('参数无效');
        }
        if (!$userGift = userGifts::getItem($id)) {
            throw new exception2('数据不存在');
        }
        if (!$user = users::getItem($userGift['user_id'])) {
            throw new exception2('用户不存在');
        }

        if ($notes == '') {
            $notes = $userGift['title'] . "-" . '活动红包';
        }

        if (empty($userGiftData)) {
            $userGiftData = array('status' => 4, 'verify_time' => date('Y-m-d H:i:s'), 'verify_admin_id' => $admin_id);
        }
        if ($gift == 0) {
            $gift = $userGift['gift'];
        }
        if ($verify_time == '') {
            $verify_time = date('Y-m-d H:i:s');
        }
        if ($finish_time == '') {
            $finish_time = date('Y-m-d H:i:s');
        }
        //开始事务
        $GLOBALS['db']->startTransaction();
        
        userGifts::updateItem($id, $userGiftData);
        
        //完成后上分类型
        if (in_array($userGift['type'], [0,103,501,502,601,602,603,604])) {//增加签到和投注两个活动类型，时间到期可删除后面两个条件
            $promo_data = array(
                'user_id' => $user['user_id'],
                'top_id' => $user['top_id'],
                'type' => 6, //'活动红包'
                'win_lose' => 0,
                'amount' => $gift,
                'create_time' => date('Y-m-d H:i:s'),
                'notes' => $notes,
                'status' => 8, //已执行
                'admin_id' => 0, //自动执行 无admin所以ID = 0
                'verify_admin_id' => 0,
                'verify_time' => $verify_time, //在签到投注活动中用来记录投注活动的线性开始时间，不影响其它业务功能
                'finish_admin_id' => 0,
                'finish_time' => $finish_time,
                'remark' => '自动执行',
            );
            if (!promos::addItem($promo_data)) {
                $GLOBALS['db']->rollback();
                return false;
            }
            $promo_id = $GLOBALS['db']->insert_id();

            $orderData = array(
                'lottery_id' => 0,
                'issue' => '',
                'from_user_id' => $user['user_id'],
                'from_username' => $user['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => $type, //帐变类型107 活动红包
                'amount' => $gift,
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] + $gift,
                'create_time' => date('Y-m-d H:i:s'),
                'business_id' => $promo_id,
                'admin_id' => $admin_id,
            );
            if (!orders::addItem($orderData)) {
                $GLOBALS['db']->rollback();
                return false;
            }

            if (!users::updateBalance($userGift['user_id'], $gift)) {
                $GLOBALS['db']->rollback();
                return false;
            }
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    /**
     * 用一个事务类似 发放任务失败的补偿金额
     * @param type $id
     * @return type
     */
    static public function sendUserFailedGifts($id, $admin_id)
    {
        if (!is_numeric($id) || !is_numeric($admin_id)) {
            throw new exception2('参数无效');
        }
        if (!$userGift = userGifts::getItem($id)) {
            throw new exception2('数据不存在');
        }
        if (!$user = users::getItem($userGift['user_id'])) {
            throw new exception2('用户不存在');
        }
        //开始事务
        $GLOBALS['db']->startTransaction();
        userGifts::updateItem($id, array('status' => 5, 'verify_time' => date('Y-m-d H:i:s'), 'verify_admin_id' => $admin_id));
        //完成后上分类型
        if ($userGift['failed_gift'] != 0) {
            $promo_data = array(
                'user_id' => $user['user_id'],
                'top_id' => $user['top_id'],
                'type' => 6, //'活动红包'
                'win_lose' => 0,
                'amount' => $userGift['failed_gift'],
                'create_time' => date('Y-m-d H:i:s'),
                'notes' => '活动红包',
                'status' => 8, //已执行
                'admin_id' => 0, //自动执行 无admin所以ID = 0
                'verify_admin_id' => 0,
                'verify_time' => date('Y-m-d H:i:s'),
                'finish_admin_id' => 0,
                'finish_time' => date('Y-m-d H:i:s'),
                'remark' => '自动执行',
            );
            if (!promos::addItem($promo_data)) {
                $GLOBALS['db']->rollback();
                return false;
            }
            $promo_id = $GLOBALS['db']->insert_id();

            $orderData = array(
                'lottery_id' => 0,
                'issue' => '',
                'from_user_id' => $user['user_id'],
                'from_username' => $user['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => 107, //帐变类型107 礼券返现
                'amount' => $userGift['failed_gift'],
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] + $userGift['failed_gift'],
                'create_time' => date('Y-m-d H:i:s'),
                'business_id' => $promo_id,
                'admin_id' => $admin_id,
            );
            if (!orders::addItem($orderData)) {
                $GLOBALS['db']->rollback();
                return false;
            }

            if (!users::updateBalance($userGift['user_id'], $userGift['failed_gift'])) {
                $GLOBALS['db']->rollback();
                return false;
            }
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    /**
     * 用一个事务 添加一个进行中的礼品券,对于每一个礼品卷来讲 这个函数只能执行一次
     * @param type $data
     * 'ug_id'=> 存在时表示将存在的礼品卷设置为进行中
     * 'user_id' => 用户id 必需
      'gift' => 礼品金额,
      'type' => 礼品类型 0不可用，完成任务发放;1可用，完成任务前不可提现
      'title' => 礼品名称,
      'from_time' => 流水计算开始时间,
      'to_time' => 流水计算结束时间,
      'min_day_water' => 每日流水要求,
      'min_total_water' => 总流水要求,
      'is_include_child' => 是否包括团队 0不包括 1包括,
      'remark' =>备注表示礼品卷发放时候的一些额外信息，比如做活动的时候绑定的银行卡
      'admin_id' => 创建人id 0为系统默认,
      'status' => 必需 0未开始1正在进行2未完成任务3已完成任务4已领取
     * @ return type
     */
    static public function addUserGifts($data, $fromUserId = 0)
    {
        if (!$user = users::getItem($data['user_id'])) {
            throw new exception2('用户不存在');
        }
        if (!isset($data['status'])) {
            throw new exception2('状态必须设置');
        }
        //开始事务
        $GLOBALS['db']->startTransaction();
        if (isset($data['ug_id'])) {
            userGifts::updateItem($data['ug_id'], $data);
        }
        else {
            userGifts::addItem($data);
        }

            $promo_data = array(
                'user_id' => $user['user_id'],
                'top_id' => $user['top_id'],
                'type' => 6, //'活动红包'
                'win_lose' => 0,
                'amount' => $data['gift'],
                'create_time' => date('Y-m-d H:i:s'),
                'notes' => $data['type'],
                'status' => 8, //已执行
                'admin_id' => 0, //自动执行 无admin所以ID = 0
                'verify_admin_id' => 0,
                'verify_time' => date('Y-m-d H:i:s'),
                'finish_admin_id' => 0,
                'finish_time' => date('Y-m-d H:i:s'),
                'remark' => '自动执行',
            );
            if (!promos::addItem($promo_data)) {
                $GLOBALS['db']->rollback();
                return false;
            }
            $promo_id = $GLOBALS['db']->insert_id();

            $orderData = array(
                'lottery_id' => 0,
                'issue' => '',
                'from_user_id' => $user['user_id'],
                'from_username' => $user['username'],
                'to_user_id' => $fromUserId,//501 502活动是下级返的 所以这里to_user_id其实记录的是返佣下级ID
                'to_username' => '',
                'type' =>$data['type'],
                'amount' => $data['gift'],
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] + $data['gift'],
                'create_time' => date('Y-m-d H:i:s'),
                'business_id' => $promo_id,
                'admin_id' => $data['admin_id'],
            );
            if (!orders::addItem($orderData)) {
                $GLOBALS['db']->rollback();
                return false;
            }

            if (!users::updateBalance($data['user_id'], $data['gift'])) {
                $GLOBALS['db']->rollback();
                return false;
            }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    /**
     * 用一个事务 删除礼品券
     * @param type $id
     * @return type
     */
    static public function deleteUserGifts($id, $admin_id)
    {
        if (!is_numeric($id) || !is_numeric($admin_id)) {
            throw new exception2('参数无效');
        }
        if (!$userGift = userGifts::getItem($id)) {
            throw new exception2('数据不存在');
        }
        if (!$user = users::getItem($userGift['user_id'])) {
            throw new exception2('用户不存在');
        }
        if ($userGift['status'] == 4) {
            throw new exception2('当前礼品券已经完成');
        }
        if ($userGift['status'] == 5) {
            throw new exception2('当前礼品券已经完成');
        }

        //开始事务
        $GLOBALS['db']->startTransaction();
        userGifts::deleteItem($id);
        //完成后上分类型
        if ($userGift['type'] == 1) {
            $amount = $userGift['status'] == 4 ? -$userGift['gift'] : -$userGift['failed_gift'];
            //1.增加用户帐变
            $orderData = array(
                'lottery_id' => 0,
                'issue' => '',
                'from_user_id' => $user['user_id'],
                'from_username' => $user['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => 205, //帐变类型107 礼券返现
                'amount' => -$userGift['gift'],
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] - $userGift['gift'],
                'create_time' => date('Y-m-d H:i:s'),
                'admin_id' => $admin_id,
            );
            if (!orders::addItem($orderData)) {
                $GLOBALS['db']->rollback();
                return false;
            }
            //7.为客户减去游戏币
            if (!users::updateBalance($userGift['user_id'], -$userGift['gift'])) {
                $GLOBALS['db']->rollback();
                return false;
            }
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            return false;
        }

        return true;
    }

    //取得所有礼金名称
    static public function getTitles()
    {
        $sql = 'SELECT DISTINCT title FROM user_gifts ';

        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    /**
     * 活动过期时间校验
     * @author Davy 2015年10月9日
     * @param  $day 2015-10-09
     * @param  $title       活动名称（中文）title字段
     * @param  $start_time  活动开始时间
     * @param  $expire_time 活动结束时间
     * @param  $error_str   通知用户文言
     * @param  $return_method   die 则直接停止返回json字串； boole 则返回布尔值
     */
    static public function expireTimeCheck($title, $error_str = '', $return_method = 'die')
    {
        $checkResult = false;

        //注册当前启用的活动，并判断时效
        switch ($title) {
            case '圣诞前夕,彩蛋再次来袭':
                if (strtotime('2015-12-10 00:00:00') < time() && time() < strtotime('2015-12-16 23:59:59')) {
                    $checkResult = true;
                }
                break;
            case '新人彩金红包':
                if (strtotime('2015-06-18 00:00:00') < time() && time() < strtotime('2015-12-18 23:59:59')) {
                    $checkResult = true;
                }
                break;
            case '卡牌活动':
                if (strtotime(userGiftsCard::ACTIVITY_START_TIME) < time() && time() < strtotime(userGiftsCard::EXCHANGE_END_TIME)) {
                    $checkResult = true;
                }
                break;
            case 'SEO直客活动':
                if (strtotime('2016-01-01 00:00:00') < time() && time() < strtotime('2016-01-30 23:59:59')) {
                    $checkResult = true;
                }
                break;
            default :
                //die(json_encode(array('errno' => 128002, 'errstr' => "活动已结束，请您联系客服谢谢")));
                break;
        }

        if($checkResult != true) {
            if($return_method == 'die'){
                die(json_encode(array('errno' => 128002, 'errstr' => $error_str)));
            }
            elseif ($return_method == 'boole') {
                return false;
            }
        }

        return true;
    }

    /**
     *存在相似IP领取奖励
     * @param $giftTypes
     * @param $clientIp
     * @return bool
     */
    static public function existLikeIpUser($giftTypes, $clientIp, $fromTime='')
    {
        if(empty($clientIp)) return false;

        $user_id = $GLOBALS['SESSION']['user_id'];

        $likeIp = substr($clientIp, 0, strrpos($clientIp, '.')+1);
        $sql = 'SELECT * FROM users where user_id!='.$user_id.' AND last_ip LIKE \''.$likeIp.'%\'';

        //判断IP是否相同由前三段相同改为前四段
        //$sql = 'SELECT * FROM users where user_id!='.$user_id.' AND last_ip=\''.$clientIp.'\'';

        if(!$users = $GLOBALS['db']->getAll($sql)) return false;

        $userIds = array_column($users, 'user_id');

        return self::exitUserGifts($userIds, $giftTypes, $fromTime);
    }

    /**
     * 存在相同银行账户名领取奖励
     * @param $giftTypes
     * @return bool
     */
    static public function existSameBankUser($giftTypes, $fromTime=''){

        $userIds = [];
        $user_id = $GLOBALS['SESSION']['user_id'];
        $bank_userNames = array_unique(array_column(userBindCards::getItems($user_id), 'bank_username'));

        foreach($bank_userNames as $bankUser)
        {
            $sql = 'SELECT * FROM user_bind_cards where user_id!='.$user_id.' AND bank_username=\''.$bankUser.'\'';

            if(!$result = $GLOBALS['db']->getAll($sql)){
                return false;
            }
            $userIds = array_unique(array_column($result, 'user_id'));
        }
        return self::exitUserGifts($userIds, $giftTypes, $fromTime);
    }

    static private function exitUserGifts($userIds, $giftTypes, $fromTime='')
    {
        if(!$userIds || !$giftTypes) return false;

        $userIds = implode(',', $userIds);
        $giftTypes = implode(',', $giftTypes);
        $sql = 'SELECT * FROM user_gifts WHERE status=4 AND user_id in ('.$userIds.') AND type in ('.$giftTypes.')';
        if($fromTime) $sql .= ' AND from_time>=\''.$fromTime.'\'';

        if($result = $GLOBALS['db']->getAll($sql)){
            return true;
        }else{
            return false;
        }
    }
}

?>