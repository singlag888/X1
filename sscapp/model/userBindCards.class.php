<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class userBindCards
{
    static public function getItem($id = 0, $user_id = 0, $status = 1,$select="*")
    {
        $sql = "SELECT {$select} FROM user_bind_cards WHERE 1";
        if (strlen($id) >= 15) {
            $sql .= " AND card_num = '$id'";
        }
        else {
            $sql .= " AND bind_card_id = " . intval($id);
        }
        if ($user_id != 0) {
            $sql .= " AND user_id = $user_id";
        }

        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }
        return $GLOBALS['db']->getRow($sql);
    }
    static public function getItemByUid($user_id,$status=1,$select='*'){
        $sql = "SELECT ".$select." FROM user_bind_cards WHERE user_id = {$user_id} ";
        if(is_array($status)){
            if(strtolower(trim($status[0]))=='in'){
                if(is_array($status[1])) $sql.="AND status in (".implode(',',$status[1]).")";
                if(is_string($status[1])) $sql.="AND status in ({$status[1]})";
            }else{
                preg_match("/^<|<=|<>|>=|>$/",$status[0])||preg_match("/^!=$/",trim($status[0])) && is_int($status[1])?$sql.='AND status '.$status[0].' '.$status[1]:die('参数错误');
            }
        }else{
            $sql.="AND status={$status}";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }

    static public function getItems($user_id = 0, $bank_id = 0, $card_num = '', $status = -1, $start = -1, $amount = DEFAULT_PER_PAGE, $username='', $bank_username='',$select='*')
    {
        $sql = 'SELECT '.$select.' FROM user_bind_cards WHERE 1';
        if($user_id != 0){
            $sql .= " AND user_id = {$user_id}";
        }
        if($username){
            $sql .= " AND username = '$username'";
        }
        if($bank_username){
            $sql .= " AND bank_username = '$bank_username'";
        }
        if ($bank_id != 0) {
            $sql .= " AND bank_id = " . intval($bank_id);
        }
        if ($card_num != '') {
            $sql .= " AND card_num = '$card_num'";
        }
        if (is_array($status)) {
            $sql .= " AND status IN (" . implode(',', $status) . ')';
        }
        else if ($status != -1) {
            $sql .= " AND status = " . intval($status);
        }
        $sql .= ' ORDER BY create_time DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber($user_id = 0, $bank_id = 0, $card_num = '', $status = -1, $username='', $bank_username='')
    {
        $sql = 'SELECT count(*) AS count FROM user_bind_cards WHERE 1';
        if ($user_id != 0) {
            $sql .= " AND user_id = {$user_id}";
        }
        if($username){
            $sql .= " AND username = '$username'";
        }
        if($bank_username){
            $sql .= " AND bank_username = '$bank_username'";
        }
        if ($bank_id != 0) {
            $sql .= " AND bank_id = " . intval($bank_id);
        }
        if ($card_num != '') {
            $sql .= " AND card_num = '$card_num'";
        }
        if ($status != -1) {
            $sql .= " AND status = " . intval($status);
        }
        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM user_bind_cards WHERE bind_card_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'bind_card_id');
    }


    //绑定卡，同一个银行只能绑定一张，且最多绑3张卡，卡号不能重复
    static public function bindCard($user_id, $bind_bank_id, $bind_card_num, $province, $city, $branch_name, $bind_bank_username)
    {
        //数据正确性判断
        if (!$bind_bank_id) {
            return "请选择银行";
        }

        if (!$bind_bank_username) {
            return "输入银行卡开户姓名";
        }

        if (!$bind_card_num) {
            return "请输入卡号";
        }

        if (!checkBankCard($bind_card_num)) {
            return "填写的卡号不合法，请确认位数";
        }

        if (!$province || !$city) {
            return "请选择正确的省份和城市";
        }
        if (!$branch_name) {
            return "请输入支行详细地址";
        }

        if (!$user = users::getItem($user_id)) {
            return "非法请求，该用户不存在或已被冻结";
        }
/*        if ($user['secpwd'] != generateEnPwd($secpwd)) {
            return "您输入的资金密码不对";
        }*/
        $bindCards = userBindCards::getItems($user_id, 0, '', 1);

        if (count($bindCards) >= 3) {
            return "您已经绑定了3张银行卡不能再绑定更多的卡";
        }

        foreach ($bindCards as $v) {
            if ($v['bank_id'] == $bind_bank_id) {
                return "您已经绑定了一张{$GLOBALS['cfg']['bankList'][$v['bank_id']]}的卡，每个银行只能绑定一张卡";
            }
            elseif ($v['card_num'] === $bind_card_num) {
                return "您已经绑定了一张卡号为 {$bind_card_num} 的卡";
            }
        }
        //卡号必须唯一
        if ($hasBindCards = userBindCards::getItems(0, 0, $bind_card_num, array(1, 2))) {
            foreach ($hasBindCards as $hasBindCard) {
                if ($hasBindCard['status'] == 1) {
                    if ($hasBindCard['user_id'] == $user_id) {
                        return "您已经绑定该卡号，不能再次绑定";
                    }
                    else {
                        return "该卡已经被其他用户绑定，不能再次绑定";
                    }
                }
                else {
                    return "该卡已经被冻结，不能再次绑定";
                }
            }
        }
        //增加绑卡
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'bank_id' => $bind_bank_id,
            'bank_username' => $bind_bank_username,
            'card_num' => $bind_card_num,
            'province' => $province,
            'city' => $city,
            'branch' => $branch_name,
            'create_time' => date('Y-m-d H:i:s'),
            'status' => 1,
        );
        if (!userBindCards::addItem($data)) {
            return "数据表出错";
        }

        return true;
    }
    static public function newBindCard($user_id, $bind_bank_id, $bind_card_num, $bind_bank_username,$secpwd,$branch_name='')
    {
        //数据正确性判断
        if (!$bind_bank_id) return "请选择银行";
        if (!$bind_bank_username) return "输入银行卡开户姓名";
        if (!$bind_card_num) return "请输入卡号";
        if (!checkBankCard($bind_card_num)) return "填写的银行卡信息不正确";
        if (!$user = users::getItem($user_id)) return "非法请求，该用户不存在或已被冻结";
        if ($user['secpwd'] != generateEnPwd($secpwd))  return "您输入的资金密码不对";
        $bindCards = userBindCards::getItems($user_id, 0, '', 1);
        if (count($bindCards) >= 1) return "您已经绑定了1张银行卡不能再绑定更多的卡";
        foreach ($bindCards as $v) {
            if ($v['bank_id'] == $bind_bank_id) {
                return "您已经绑定了一张{$GLOBALS['cfg']['bankList'][$v['bank_id']]}的卡，每个银行只能绑定一张卡";
            }
            elseif ($v['card_num'] === $bind_card_num) {
                return "您已经绑定了一张卡号为 {$bind_card_num} 的卡";
            }
        }
        //卡号必须唯一
        if ($hasBindCards = userBindCards::getItems(0, 0, $bind_card_num, array(1, 2))) {
            foreach ($hasBindCards as $hasBindCard) return $hasBindCard['status'] == 1? ($hasBindCard['user_id'] == $user_id ? "您已经绑定该卡号，不能再次绑定" : "该卡已经被其他用户绑定，不能再次绑定"):"该卡已经被冻结，不能再次绑定";
        }
        //增加绑卡
        $data = array(
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'bank_id' => $bind_bank_id,
            'bank_username' => $bind_bank_username,
            'card_num' => $bind_card_num,
//            'branch_name' => $branch_name,
            'province' => '',
            'city' => '',
            /****************** author snow 添加支行名称*****************************************************/
            'branch' => $branch_name,
            /****************** author snow 添加支行名称*****************************************************/
            'create_time' => date('Y-m-d H:i:s'),
            'status' => 1,
        );
        if (($res=userBindCards::_new_addItem($data))===false) return "数据表出错";
        return $res;
    }
    /**
     * @param $id
     * @param $data
     * @return bool|string
     * @throws exception2
     */
    static public function changeBindCard($id, $data)
    {
        //数据正确性判断
        if (empty($data['bank_id']) || empty($data['bank_username']) || empty($data['card_num']) || empty($data['province']) || empty($data['city']) || empty($data['branch'])) {
            return "信息不能为空";
        }
        $userBindCard = self::getItem($id);
        
        if (!checkBankCard($data['card_num'])) {
            return "填写的银行卡信息不正确";
        }

        if (!$user = users::getItem($userBindCard['user_id'])) {
            return "非法请求，该用户不存在或已被冻结";
        }
        //卡号必须唯一
        if ($hasBindCards = userBindCards::getItems(0, 0, $data['card_num'], array(1, 2))) 
        {
            if(in_array($data['card_num'], array_column($hasBindCards, 'card_num'))){
                return "该卡已经被绑定，不能再次绑定";
            }
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        if (!userBindCards::updateItem($id, $data)) {
            return "数据表出错";
        }

        return true;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }
        return $GLOBALS['db']->insert('user_bind_cards', $data);
    }
    static public function _new_addItem($data)
    {
        return $GLOBALS['db']->insert('user_bind_cards', $data)===true?$GLOBALS['db']->insert_id():false;
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('user_bind_cards',$data,array('bind_card_id'=>$id));
    }

    static public function deleteItem($id, $blacklist = false, $remark = '')
    {
        if ($id < 0) {
            throw new exception2('参数无效');
        }

        if ($blacklist) {
//            $sql = "DELETE FROM user_bind_cards WHERE bind_card_id = " . intval($id) . " LIMIT 1";
            $sql = "UPDATE user_bind_cards SET status =2, finish_admin_id = {$GLOBALS['SESSION']['admin_id']}, remark = '{$remark}' WHERE bind_card_id = " . intval($id) . " LIMIT 1";
        }
        else {
            $sql = "UPDATE user_bind_cards SET status = 0, finish_admin_id = {$GLOBALS['SESSION']['admin_id']}, remark = '{$remark}' WHERE bind_card_id = " . intval($id) . " LIMIT 1";
        }

        return $GLOBALS['db']->query($sql, array(), 'u');
    }
}

?>