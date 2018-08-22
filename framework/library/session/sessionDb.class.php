<?php

/**
 * session实现
 *
 * 同一个域名情况下，用户会话和管理员会话的cookie SESSID一样，所以只能共用，共用的缺点就是某一边退出，另一边也退出了，但不共用的话更糟（前后台无法同时使用）
 * 可以把
 */
class session implements ArrayAccess
{
    private $expire_time = 0;
    private $session_cookie_lifetime = 0;
    private $session_name = '';
    private $session_id = '';

    private $_project = NULL;
    private $_ip = NULL;

    private $inited = false;
    private $_is_exists = NULL; //标记存储中是否存在该session，不管到没到期
    private $_sess_active = NULL;   //到期时间
    private $_sess_hash   = NULL;
    private $session_cookie_path = '/';
    private $session_cookie_domain = '';
    private $session_cookie_secure = false;

    private $_SESSION = array();

    /**
     *
     * @param type $expire_time 到期的分钟数
     * @param type $session_cookie_lifetime cookie有效分钟数，为0表示
     * @param type $project
     * @param type $session_name
     * @param type $session_id
     */
    public function __construct($expire_time = 10, $session_cookie_lifetime = 0, $project = PROJECT, $session_id = '')
    {
        if ($expire_time <= 0 || $session_cookie_lifetime < 0) {
            throw new exception2('expire time must more than 0');
        }

        $this->expire_time = REQUEST_TIME + $expire_time * 60;
        if ($session_cookie_lifetime > 0) {
            $this->session_cookie_lifetime = REQUEST_TIME + $session_cookie_lifetime * 60;
        }
        else {
            $this->session_cookie_lifetime = 0;
        }

        $this->_project = $project;
        $this->session_name = $this->_project . "SESSID";

        $cookie_session_id = $GLOBALS['REQUEST']->getCookie($this->session_name, 'trim');
        if ($session_id === '' && $cookie_session_id !== '') {
            $this->session_id = $cookie_session_id;
        }
        else {
            $this->session_id = $session_id;
        }

        if ($this->session_id) {
            $tmp_session_id = substr($this->session_id, 0, 32);
            if ($this->gen_session_key($tmp_session_id) === substr($this->session_id, 32)) {
                $this->session_id = $tmp_session_id;
            }
            else {
                $this->session_id = '';
            }
        }

        $this->_ip = $GLOBALS['REQUEST']['client_ip'];
    }

    public function __destruct()
    {
        if ($this->inited === true) {
            $this->save_session();
        }

        //给一定概率进行垃圾回收
        if (rand(1, 9999) == 9000) {
            //for debug 暂不删除过期session以便排查
            self::ga_session();
        }
    }

    private function init()
    {
        if (!empty($GLOBALS['cookie_path'])) {
            $this->session_cookie_path = $GLOBALS['cookie_path'];
        }
        else {
            $this->session_cookie_path = '/';
        }

        if (!empty($GLOBALS['cookie_domain'])) {
            $this->session_cookie_domain = $GLOBALS['cookie_domain'];
        }
        else {
            if (defined('MAIN_DOMAIN') === true) {
                //$this->session_cookie_domain = '.' . MAIN_DOMAIN;
            }
            else {
                $this->session_cookie_domain = '';
            }
        }

        if (!empty($GLOBALS['cookie_secure'])) {
            $this->session_cookie_secure = $GLOBALS['cookie_secure'];
        }
        else {
            $this->session_cookie_secure = false;
        }

        //如果有session id就预先取出来 否则随机生成一个
        if ($this->session_id !== '') {
            $this->fetch_session();
        }
        else {
            $this->session_id = $this->gen_session_id();
            $this->_sess_hash   = $this->_project . crc32(serialize(array()));
            $this->_sess_active = 0;
            setcookie($this->session_name, $this->session_id . $this->gen_session_key($this->session_id), $this->session_cookie_lifetime, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);
        }

        $this->inited = true;
    }

    private function gen_session_id()
    {
        return md5(uniqid(mt_rand(), true) . uniqid(mt_rand(), true) . $this->_ip);
    }

    private function gen_session_key($session_id)
    {
        return sprintf('%08x', crc32($_SERVER['HTTP_USER_AGENT'] . PROJECT . $session_id)); //如果用PROJECT_PATH则前后台会互相冲突不能同时登录
    }

    private function fetch_session()
    {
        //从数据库中取
        $sql = "SELECT * FROM sessions WHERE session_id = :session_id AND expire_time > :expire_time";
        $paramArr = array(':session_id' => $this->session_id , ':expire_time' => REQUEST_TIME);
        $result = $GLOBALS['db']->getAll($sql, $paramArr);
        if (count($result) > 1) {
            logdump("session:::出现异常:返回结果大于一条 $sql(REQUEST_TIME=".date('Y-m-d H:i:s', REQUEST_TIME).")", $result);
        }

        if ($result) {
            $result = reset($result);
            $this->_SESSION = unserialize($result['data']);
            $this->_sess_hash = $this->_project . crc32($result['data']);
            $this->_sess_active = strtotime($result['ts']);
            $this->_is_exists = true;
        }
        else {
//logdump('有session_id而没取到数据，这表示session不存在或已过期，将执行$this->_is_exists = false');
            //对于游客，没有写任何session数据，例如连验证码都没有的情况，这不需要保存
            $this->_sess_hash = $this->_project . crc32(serialize(array()));
            $this->_is_exists = false;
        }

        return true;
    }

    private function save_session()
    {
        $data = serialize($this->_SESSION);
        //如果数据有变化，或者用户超过一分钟没有动作，就更新一下表中的值以及到期时间
        if ($this->_sess_hash !== $this->_project . crc32($data) || ($this->_sess_active > 0 && REQUEST_TIME > $this->_sess_active + 60)) {
            //如果存在就更新其资料 否则新增一条记录
            if (!($user_id = !empty($this->_SESSION['user_id']) ? $this->_SESSION['user_id'] : 0)) {
                $user_id = !empty($this->_SESSION['admin_id']) ? $this->_SESSION['admin_id'] : 0;
            }
            $is_admin = !empty($this->_SESSION['admin_id']) ? 1 : 0;
            $now = date('Y-m-d H:i:s');
            $paramArr = array();

            //用户清空cookie或cookie丢失,session_id会重新生成
/*            if($user_id){
                $sql = "DELETE FROM sessions WHERE session_id!='{$this->session_id}' AND user_id={$user_id} AND is_admin=0";
                $GLOBALS['db']->query($sql, array(), 'd');
            }*/
            if ($this->_is_exists) {
                $sql = "UPDATE sessions SET data = :data, expire_time =:expire_time, user_id=:user_id, is_admin=:is_admin WHERE session_id=:session_id AND expire_time > :expire_times LIMIT 1";
                $paramArr[':data'] = $data;
                $paramArr[':expire_time'] = $this->expire_time;
                $paramArr[':user_id'] = $user_id;
                $paramArr[':is_admin'] = $is_admin;
                $paramArr[':session_id'] = $this->session_id;
                $paramArr[':expire_times'] = REQUEST_TIME;
                $type = 'u';
            }
            else {
                //首次保存的是登录验证码
/*                $sql = "DELETE FROM sessions WHERE session_id='{$this->session_id}'";
                $GLOBALS['db']->query($sql, array(), 'd');*/

                $sql = "INSERT INTO sessions (session_id,data,create_time,expire_time,user_id,is_admin,client_ip) VALUES(:session_id,:data,:now,:expire_time,:user_id, :is_admin,:ip)";
                $paramArr[':session_id'] = $this->session_id;
                $paramArr[':data'] = $data;
                $paramArr[':now'] = $now;
                $paramArr[':expire_time'] = $this->expire_time;
                $paramArr[':user_id'] = $user_id;
                $paramArr[':is_admin'] = $is_admin;
                $paramArr[':ip'] = $this->_ip;
                $type = 'i';

            }

            if (!$GLOBALS['db']->query($sql, $paramArr, $type)) {
                return false;
            }
        }

        return $GLOBALS['db']->affected_rows();
    }

    private function ga_session()
    {
        $sql = "DELETE FROM sessions WHERE expire_time < '" . (REQUEST_TIME - 60 * 60) . "'";
        logdump("ga_session(): $sql");
        if (!$GLOBALS['db']->query($sql, array(), 'd')) {
            return false;
        }
        $ar = $GLOBALS['db']->affected_rows();
        logdump("清理垃圾清理了{$ar}条记录：" . $sql);
        return $ar;
    }

    public function offsetSet($offset, $value)
    {
        if ($this->inited === false) {
            $this->init();
        }

        if (is_null($offset)) {
            $this->_SESSION[] = $value;
        }
        else {
            $this->_SESSION[$offset] = $value;
        }
    }

    //isLogined()将会调用此方法
    public function offsetExists($offset)
    {
        if ($this->inited === false) {
            $this->init();
        }

        return isset($this->_SESSION[$offset]);
    }

    public function offsetUnset($offset)
    {
        if ($this->inited === false) {
            $this->init();
        }

        unset($this->_SESSION[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($this->inited === false) {
            $this->init();
        }

        return isset($this->_SESSION[$offset]) ? $this->_SESSION[$offset] : null;
    }

    //没什么用
    public function getSessionId()
    {
        if ($this->inited === false) {
            $this->init();
        }

        return $this->session_id;
    }

    //注销时调用
    public function destroy()
    {
        $sql = "DELETE FROM sessions WHERE session_id='{$this->session_id}' LIMIT 1";

        //当用户过期后user_id不存在
/*        if(isset($this->_SESSION['user_id'])){
            $sql = "DELETE FROM sessions WHERE user_id={$this->_SESSION['user_id']} AND is_admin=0";
        }else{
            $sql = "DELETE FROM sessions WHERE session_id='{$this->session_id}'";
        }*/
        $GLOBALS['db']->query($sql, array(), 'd');

        setcookie($this->session_name, '', -1, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);

        /* 变量重新初始化 */
        $this->_SESSION = array();
        $this->session_id = '';
        $this->inited = false;
    }


    //可以在登录的时候调用此方法来判断并踢人
    public function getActiveUser($user_id, $is_admin = 0)
    {
        if ((!is_numeric($user_id) || $user_id <= 0)) {
            throw new exception2('invalid args');
        }

        $flag = 0;
        $sql = "SELECT * FROM sessions WHERE user_id = $user_id AND is_admin = $is_admin AND expire_time > " . REQUEST_TIME . " ORDER BY id DESC";

        return $GLOBALS['db']->getAll($sql);
    }

    /**
     * 查询某人是否被挤下线了 用于除登录以后的所有动作检查
     * @param int $user_id
     * @param int $is_admin
     * @return int
     */
    public function isEdgeOut($user_id, $is_admin = 0, &$newSessInfo = array())
    {
        $flag = 0;
        $result = self::getActiveUser($user_id, $is_admin);

        if (count($result) > 1) {
            $selfSession = NULL;
            foreach ($result as $k => $v) {
                if ($v['session_id'] == $this->session_id) {
                    $selfSession = $v;
                    unset($result[$k]);
                }
            }

            if (!$selfSession) {
                throw new exception2('session system error');
            }
            foreach ($result as $k => $v) {
                if ($v['create_time'] > $selfSession['create_time']) {
                    $newSessInfo = $v;
                    $flag = 1;
                    break;
                }
            }
        }

        return $flag;
    }

    //主动踢人下线
    public function edgeOut($user_id)
    {
        if (!is_numeric($user_id) || $user_id <= 0) {
            throw new exception2('invalid args');
        }

        $sql = "DELETE FROM sessions WHERE user_id = :user_id";
        $paramArr[':user_id'] = $user_id;

        $GLOBALS['db']->query($sql , $paramArr, 'd');

        return $GLOBALS['db']->affected_rows();
    }
}

?>
