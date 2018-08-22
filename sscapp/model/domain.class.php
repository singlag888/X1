<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class domain
{

    const CACHE_TIME = 7200;

    static public function getItem($id, $status = 1)
    {
        if(($result = $GLOBALS['mc']->get(__CLASS__, $id)) === false){
            if (is_numeric($id)) {
                $sql = 'SELECT * FROM domains WHERE domain_id = ' . intval($id);
            }
            elseif (is_string($id)) {
                $sql = 'SELECT * FROM domains WHERE name = \'' . $id . '\'';
            }
            else {
                throw new exception2('参数无效');
            }
            if ($status != -1) {
                $sql .= " AND status = $status";
            }

            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $id, $result, 86400);
        }

        return $result;
    }
    //获取列表信息

    static public function getItems($status = 1)
    {
        $sql = 'SELECT * FROM domains WHERE 1';
        if ($status !== -1) {
            $sql .= " AND status = " . intval($status);
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsWithKey($status = 1)
    {
        $sql = 'SELECT * FROM domains WHERE 1';
        if ($status !== -1) {
            $sql .= " AND status = " . intval($status);
        }
        $result = $GLOBALS['db']->getAll($sql, array(),'domain_id');

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }
        if (empty($ids)) {
            return array();
        }

        $sql = 'SELECT * FROM domains WHERE domain_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'domain_id');
    }

    static public function getItemsByName($name)
    {
        $sql = 'SELECT * FROM domains WHERE name ="' . $name . '"';

        return $GLOBALS['db']->getAll($sql, array(),'name');
    }

    static public function getCanBoundDomains()
    {
        $sql = "SELECT DISTINCT(d.`name`),d.type,d.domain_id,d.status FROM domains d LEFT JOIN user_domains ud using(domain_id) where ud.domain_id is NULL";

        return $GLOBALS['db']->getAll($sql, array(),'domain_id');
    }

    //获取域名绑定的用户
    static public function getDomainUser($name)
    {
        if (!is_string($name)) {
            throw new exception2('参数无效');
        }

        $sql = "SELECT u.type,u.user_id FROM domains d  LEFT JOIN user_domains ud ON d.domain_id = ud.domain_id LEFT JOIN users u ON u.user_id = ud.top_id WHERE d.name = '{$name}' LIMIT 1";
        $res = $GLOBALS['db']->getRow($sql);
        if(empty($res) || $res['user_id'] === NULL || $res['type'] === NULL){
            $res = array();
        }

        return $res;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('domains', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('domains',$data,array('domain_id'=>$id));
    }

    static public function deleteItem($id)
    {
        $sql = "DELETE FROM domains WHERE domain_id=" . intval($id) . ' LIMIT 1';

        return $GLOBALS['db']->query($sql, array(), 'd');
    }

    /**
     * @param type $domain_id
     * @param type $isCache 如果为1表示强制重新取
     * @return
     */
    static public function getUserDomains( $domain_id = 0,$order ,$isCache,$start = -1,$amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,b.name , c.nick_name , c.ext_code FROM user_domains a , domains b ,users c WHERE a.username = c.username AND  a.domain_id = b.domain_id ';
        $paramArr = array();
        if ($domain_id != 0) {
            if (is_array($domain_id)) {
                $sql .= " AND a.domain_id IN (" . implode(',', $domain_id) . ")";
            }
            else {
                $sql .= " AND a.domain_id = " . intval($domain_id);
            }
        }
        $sql .= " AND b.status = 1";
        if(!empty($order)){
            $sql .= " ORDER BY a.ud_id ".$order;
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        //dd($sql);
        $result = array();
        $tmp = $GLOBALS['db']->getAll($sql);
        foreach ($tmp as $v) {
            $result[] = $v;
        }
        return $result;
    }
    //获取分页条数
    /**
     * 这是分页条数获取的方法
     * @return array
     * @author L
     */
    static public function getCountNumber(){
        $sql = "SELECT count(*) AS count FROM user_domains a LEFT JOIN domains b ON a.domain_id=b.domain_id  LEFT JOIN users c ON a.username = c.username";
        $result = $GLOBALS['db']->getRow($sql);
        return $result['count'];
    }
    static public function getDomainNumber($domain){
        $sql = "SELECT count(*) AS count FROM user_domains a , users b , domains c WHERE  c.domain_id = a.domain_id and a.username = b.username ";
        if(!empty($domain)){
            $sql .= " AND c.name = "."'".$domain."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result['count'];
    }
    /**
     * 根据域名查询
     * @param type $domain
     * @param type $order
     * @return array
     * @author L
     */
    static public function getDomains($domain,$order,$start = -1,$amount = DEFAULT_PER_PAGE){//根据域名查询
        $sql = 'SELECT  a.*,c.name , b.nick_name , b.ext_code FROM user_domains a , users b , domains c WHERE  c.domain_id = a.domain_id and a.username = b.username';
        if(!empty($domain)){
            $sql .= " AND c.name = "."'".$domain."'";
            if (!empty($order)){
                $sql .= " ORDER BY a.ud_id ".$order;
            }
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = array();
        $tmp = $GLOBALS['db']->getAll($sql);
        foreach ($tmp as $v){
            $result[] = $v;
        }
        return $result;
    }
    static function getUsernameNumber($username){
        $sql = "SELECT count(*) AS count FROM user_domains a , users b , domains c WHERE  b.username = a.username AND a.domain_id = c.domain_id";
        if (!empty($username)){
            $sql .= " AND b.username = "."'".$username."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result['count'];
    }
    /**
     * 根据用户名查询
     * @param type $username 用户名
     * @param type $order 排序
     * @return array
     * @author L
     */
    static public function getUsername($username,$order,$start = -1,$amount = DEFAULT_PER_PAGE){//根据用户名查询
        $sql = 'SELECT a.*,c.name , b.nick_name , b.ext_code FROM user_domains a , users b , domains c WHERE  b.username = a.username AND a.domain_id = c.domain_id';
        if (!empty($username)){
            $sql .= " AND b.username = "."'".$username."'";
            if (!empty($order)){
                $sql .= " ORDER BY a.ud_id ".$order;
            }
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = array();
        $tmp = $GLOBALS['db']->getAll($sql);
        foreach ($tmp as $v){
            $result[] = $v;
        }
        return $result;
    }
    static public function getNickNameNumber($nick_name){//获取NICK_NAME查询的条数
        $sql = "SELECT count(*) AS count FROM user_domains a , users b , domains c WHERE  b.username = a.username and a.domain_id = c.domain_id";
        if (!empty($nick_name)){
            $sql .= " AND b.nick_name = "."'".$nick_name."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result['count'];
    }
    /**
     * 根据昵称查询
     * @param type $nick_name 昵称
     * @param type $order 排序
     * @return array
     * @author L
     */
    static public function getNickName($nick_name,$order,$start = -1,$amount = DEFAULT_PER_PAGE){//根据昵称查询
        $sql = 'SELECT a.*,c.name , b.nick_name , b.ext_code FROM user_domains a , users b , domains c WHERE  b.username = a.username and a.domain_id = c.domain_id';
        if (!empty($nick_name)){
            $sql .= " AND b.nick_name = "."'".$nick_name."'";
            if (!empty($order)){
                $sql .= " ORDER BY a.ud_id ".$order;
            }
        }
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = array();
        $tmp = $GLOBALS['db']->getAll($sql);
        foreach ($tmp as $v){
            $result[] = $v;
        }
        return $result;
    }
    //根据推广码获取条数
    static public function getExtCodeNumber($ext_code){
        $sql = "SELECT count(*) AS count FROM user_domains a , users b , domains c WHERE b.username = a.username AND a.domain_id = c.domain_id";
        if (!empty($ext_code)){
            $sql .= " AND  b.ext_code = "."'".$ext_code."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result['count'];
    }
    /**
     * 根据推广码查询
     * @param type $ext_code 推广码
     * @param type $order 排序
     * @return array
     * @author L
     */
    static public function getExtCode($ext_code,$order,$start = -1,$amount = DEFAULT_PER_PAGE){//根据推广码查询
        $sql = 'SELECT a.*,c.name , b.nick_name , b.ext_code FROM user_domains a , users b , domains c WHERE b.username = a.username AND a.domain_id = c.domain_id';
        if (!empty($ext_code)){
            $sql .= " AND  b.ext_code = "."'".$ext_code."'";
            if (!empty($order)){
                $sql .= " ORDER BY a.ud_id ".$order;
            }
        }
        $result = array();
        $tmp = $GLOBALS['db']->getAll($sql);
        foreach ($tmp as $v){
            $result[] = $v;
        }
        return $result;
    }
    /**
     * 根据ud_id获取列表的单条信息
     * @param type $ud_id ud_id
     * @return array
     * @author L
     */
    static public function getUdidDomain($ud_id){//根据ud_id获取列表的单条信息
        $sql = "SELECT  a.*,c.name , b.nick_name , b.ext_code FROM user_domains a , users b , domains c WHERE  c.domain_id = a.domain_id and a.username = b.username";
        if(!empty($ud_id)){
            $sql .= " AND a.ud_id = "."'".$ud_id."'";
        }
        $result = array();
        $result = $GLOBALS['db']->getAll($sql);
        return $result;
    }
    //获取UDid
    static public function getUdId($username){
        $sql = "SELECT user_id From users WHERE 1";
        if (!empty($username)){
            $sql .= " AND username = "."'".$username."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }
    //获取top_id
    static public function getTopId($username){
        $sql = "SELECT top_id FROM users WHERE 1";
        if (!empty($username)){
            $sql .= " AND username = "."'".$username."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }
    //根据usernamehe domain_id 查询ud_id
    static public function getDomainUdId($username,$domain_id){
        $sql = "SELECT ud_id FROM user_domains WHERE 1";
        if(!empty($username) && !empty($domain_id)){
            $sql .=" AND username = "."'".$username."'"." AND domain_id = "."'".$domain_id['domain_id']."'";
            //dd($sql);
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }
    //获取domain_id,如果没有就添加
    static public function getDomainId($domain){//获取domain_id,如果没有就添加
        $url = preg_match('/[\w][\w-]*\.(?:com\.cn|com|cn|co|net|org|gov|cc|biz|info)(\/|$)/isU',$domain,$addurl);
        if($url == 0){
            return "输入的域名不符合规则";
    	}
        $sql = "SELECT domain_id FROM domains WHERE 1";
        if(!empty($domain)){
            $sql .= " AND name = "."'".$addurl[0]."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        if (!empty($result)){
            $sql = "SELECT domain_id FROM domains WHERE 1 AND name = "."'".$addurl[0]."'";
            $res = "SELECT * FROM user_domains WHERE 1 AND domain_id ="."'".$result['domain_id']."'";
            $res = $GLOBALS['db']->getRow($res);
            if(!empty($res)){
                return "该域名已被绑定";
            }
        }else{
            $domainsData = array(
                'name' => $addurl[0],
                'type' => '1',
                'status' => '1'
            );
            $GLOBALS['mc']->flush();
            $GLOBALS['db']->insert('domains',$domainsData);
            $sql = "SELECT domain_id FROM domains WHERE 1 AND name = "."'".$addurl[0]."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }
    //根据ud_id获取domain_id
    static public function getDomainsId($udId){
        $sql = "SELECT domain_id FROM user_domains WHERE 1";
        if(!empty($udId)){
            $sql .= " AND ud_id = "."'".$udId."'";
        }
        $result = $GLOBALS['db']->getRow($sql);
        return $result;
    }
    //添加user_domains表的信息
    static public function domainsAdd($top_id,$domain_id,$username){
        $levelSql = "SELECT level FROM users WHERE 1 AND username = "."'".$username."'";
        $level = $GLOBALS['db']->getRow($levelSql);
        if(!isset($level['level'])){
            return "您输入的代理帐号不是网站会员";
        }
        if($level['level'] !== 0){
            return "您输入的代理帐号不是总代理，请到：用户管理->'代理管理'页面确认";
        }
        if(!empty($top_id) && !empty($domain_id) && !empty($username)){
            $data = array(
                'top_id' => $top_id['top_id'],
                'domain_id' => $domain_id['domain_id'],
                'username' => $username
            );
        }
        $GLOBALS['mc']->flush();
        return $GLOBALS['db']->insert('user_domains',$data);
    }
    //判断传过来帐号验证
    static function userDomainsUpdata($username){
        $levelSql = "SELECT level FROM users WHERE 1 AND username = "."'".$username."'";
        $level = $GLOBALS['db']->getRow($levelSql);
        if(!isset($level['level'])){
            return "您输入的代理帐号不是网站会员";
        }
        if($level['level'] !== 0){
            return "您输入的代理帐号不是总代理，请到：用户管理->'代理管理'页面确认";
        }
    }
    //修改doamins的name
    static public function updataDomains($domain,$domain_id){
        if(!empty($domain_id) && !empty($domain)){
            $sql = "UPDATE domains SET name = "."'".$domain."'"."WHERE domain_id = "."'".$domain_id['domain_id']."'";
        }
        return $GLOBALS['db']->query($sql,array(),'u');
    }
    //验证推广码是否存在
    static public function inspectExtCode($ext_code){
        $codeSql = "SELECT ext_code FROM users WHERE ext_code = "."'".$ext_code."'";
        $code = $GLOBALS['db']->getRow($codeSql);
        return $code;
    }
    //修改users表的昵称和推广码
    static public function usersUpdata($nick_name,$ext_code,$username){//修改users表的昵称和推广码
        $res = preg_match('/^[0-9a-zA-Z]{6,8}$/', $ext_code);
        if($res == 0){
            return "请确认输入的推广码长度是否在6到8之间，且是否为字母，数字或字母数字组合";
        }
        if (!empty($nick_name) && !empty($ext_code) && !empty($username)){
            $userSql = "UPDATE users SET nick_name = "."'".$nick_name."' ,"."ext_code = "."'".$ext_code."'"."WHERE username = "."'".$username."'";
        }
        $result = $GLOBALS['db']->query($userSql, array(),'u');
        return $result;
    }


    //取消绑定
    static public function deleteUserDomain($domain_id)
    {
        if (!empty($domain_id)){
            $sql = "DELETE FROM user_domains WHERE ud_id = "."'".$domain_id."'";
        }
        return $GLOBALS['db']->query($sql, array(), 'd');
    }

}

?>