<?php

use common\model\onlineUser;

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
    private $_sess_hash = NULL;
    private $session_cookie_path = '/';
    private $session_cookie_domain = '';
    private $session_cookie_secure = false;

    private $redis_prefix = 'session_';
    private $full_data = [];
    private $user_id = 0;
    private $is_admin = 0;
    private $ys_session_id = '';

    private $_SESSION = [];

    /**
     * @param int $expire_time 到期的分钟数
     * @param int $session_cookie_lifetime cookie有效分钟数，为0表示
     * @param string $project
     * @param string $session_id
     * @throws exception2
     */
    public function __construct($expire_time = 10, $session_cookie_lifetime = 0, $project = PROJECT, $session_id = '')
    {
        if ($expire_time <= 0 || $session_cookie_lifetime < 0) {
            throw new exception2('expire time must more than 0');
        }

        $this->expire_time = REQUEST_TIME + $expire_time * 60;

        if ($session_cookie_lifetime > 0) {
            $this->session_cookie_lifetime = REQUEST_TIME + $session_cookie_lifetime * 60;
        } else {
            $this->session_cookie_lifetime = 0;
        }

        $this->_project = $project;
        $this->session_name = $this->_project . "SESSID";
        $cookie_session_id = $GLOBALS['REQUEST']->getCookie($this->session_name, 'trim');
        if ($session_id === '' && $cookie_session_id !== '') {
            $this->session_id = $cookie_session_id;
        } else {
            $this->session_id = $session_id;
        }
        $this->ys_session_id = $this->session_id;
        if ($this->session_id) {
            $tmp_session_id = substr($this->session_id, 0, 32);
            if ($this->gen_session_key($tmp_session_id) === substr($this->session_id, 32)) {
                $this->session_id = $tmp_session_id;
            } else {
                $this->session_id = '';
            }
        }
        $this->_ip = $GLOBALS['REQUEST']['client_ip'];
    }

    public function getSessionId()
    {
        if ($this->inited === false) {
            $this->init();
        }

        return $this->session_id;
    }
    public function getYSSessionId()
    {
        if ($this->inited === false) {
            $this->init();
        }

        return $this->ys_session_id;
    }

    public function destroy()
    {
        $GLOBALS['redis']->pushPrefix(XY_PREFIX)->select(REDIS_DB_SESSION);
        $this->fetch_session();
        $data = $this->fetch_session_user($this->user_id, $this->is_admin);

        // 这里判断是否是退出操作
        // 如果是被挤下线不删除在线标识
        if ($data['session_id'] == $this->session_id) {
            $GLOBALS['redis']->del($this->redis_prefix . $this->is_admin . '_' . $this->user_id);
            if (!$this->is_admin && $this->user_id) {
                (new onlineUser())->offline($this->user_id);
            }
        }

        $GLOBALS['redis']->del($this->redis_prefix . $this->session_id);
        $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);
        setcookie($this->session_name, '', -1, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);

        /* 变量重新初始化 */
        $this->_SESSION = [];
        $this->session_id = '';
        $this->inited = false;
    }

    /**
     * 查询某人是否被挤下线了 用于除登录以后的所有动作检查
     * @param int $user_id
     * @param int $is_admin
     * @return int 返回1 是有问题, 0 是没问题.  想知道为什么?问神去啊!
     */
    public function isEdgeOut($user_id, $is_admin = 0, &$newSessInfo = [])
    {
        $GLOBALS['redis']->pushPrefix(XY_PREFIX)->select(REDIS_DB_SESSION);
        // 1.通过userId拿到数据,比对当前sessionId与数据中sessionId是否相同
        $this->fetch_session();
        $data = $this->fetch_session_user($user_id, $is_admin);
        $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);
        // 2.相同则没事
        // 3.不同则判断时间
        // if ($data['session_id'] != $this->session_id) {
        if ($data['session_id'] != $this->session_id && $data['login_time'] > $this->full_data['login_time']) {
            $newSessInfo = $data;
            return 1;
        }

        return 0;
    }

    /**
     * 主动踢人下线
     * @param $user_id
     * @throws exception2
     */
    public function edgeOut($user_id, $is_admin = 0)
    {
        if (!is_numeric($user_id) || $user_id <= 0) {
            throw new exception2('invalid args');
        }

        $GLOBALS['redis']->pushPrefix(XY_PREFIX)->select(REDIS_DB_SESSION);

        $data = $this->fetch_session_user($user_id, $is_admin);

        if($data){
            $GLOBALS['redis']->del($this->redis_prefix . $data['session_id']);
            $GLOBALS['redis']->del($this->redis_prefix . $is_admin . '_' . $user_id);
        }
        $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);
    }

    private function init()
    {
        if (!empty($GLOBALS['cookie_path'])) {
            $this->session_cookie_path = $GLOBALS['cookie_path'];
        } else {
            $this->session_cookie_path = '/';
        }

        if (!empty($GLOBALS['cookie_domain'])) {
            $this->session_cookie_domain = $GLOBALS['cookie_domain'];
        } else {
            if (defined('MAIN_DOMAIN') === true) {
                // $this->session_cookie_domain = '.' . MAIN_DOMAIN;
            } else {
                $this->session_cookie_domain = '';
            }
        }

        if (!empty($GLOBALS['cookie_secure'])) {
            $this->session_cookie_secure = $GLOBALS['cookie_secure'];
        } else {
            $this->session_cookie_secure = false;
        }

        // 如果有session id就预先取出来 否则随机生成一个
        if ($this->session_id !== '') {
            $GLOBALS['redis']->pushPrefix(XY_PREFIX)->select(REDIS_DB_SESSION);
            $this->fetch_session();
            $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);
        } else {
            $this->session_id = $this->gen_session_id();
            $this->_sess_hash = $this->_project . crc32(serialize([]));
            $this->_sess_active = 0;
            $session_id=$this->session_id . $this->gen_session_key($this->session_id);
            setcookie($this->session_name, $this->session_id . $this->gen_session_key($this->session_id), $this->session_cookie_lifetime, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);
            $this->ys_session_id = $session_id;
        }

        $this->inited = true;
    }

    private function gen_session_id()
    {
        return md5(uniqid(mt_rand(), true) . uniqid(mt_rand(), true) . $this->_ip);
    }

    private function gen_session_key($session_id)
    {
        // 如果用PROJECT_PATH则前后台会互相冲突不能同时登录
        return sprintf('%08x', crc32($_SERVER['HTTP_USER_AGENT'] . PROJECT . $session_id));
    }

    private function fetch_session()
    {
        $sessionData = $GLOBALS['redis']->get($this->redis_prefix . $this->session_id);
        if ($sessionData) {
            $sessionData = json_decode($sessionData, true);
            $this->_SESSION = unserialize($sessionData['data']);
            $this->full_data = $sessionData;
            $this->user_id = $sessionData['user_id'];
            $this->is_admin = $sessionData['is_admin'];
            $this->_sess_hash = $this->_project . crc32($sessionData['data']);
            $this->_sess_active = strtotime($sessionData['ts']);
            $this->_is_exists = true;
        } else {
            //对于游客，没有写任何session数据，例如连验证码都没有的情况，这不需要保存
            $this->_sess_hash = $this->_project . crc32(serialize([]));
            $this->_is_exists = false;
        }

        return true;
    }

    private function fetch_session_user($user_id, $is_admin)
    {
        $data = $GLOBALS['redis']->get($this->redis_prefix . $is_admin . '_' . $user_id);
        if ($data) {
            $data = json_decode($data, true);
            $data['data'] = unserialize($data['data']);
        }

        return $data;
    }

    private function save_session()
    {
        $data = serialize($this->_SESSION);
        // 如果数据有变化，或者用户超过一分钟没有动作，就更新一下表中的值以及到期时间
        if ($this->_sess_hash !== $this->_project . crc32($data) || ($this->_sess_active > 0 && REQUEST_TIME > $this->_sess_active + 60)) {
            if (!($user_id = !empty($this->_SESSION['user_id']) ? $this->_SESSION['user_id'] : 0)) {
                $user_id = !empty($this->_SESSION['admin_id']) ? $this->_SESSION['admin_id'] : 0;
            }
            $is_admin = !empty($this->_SESSION['admin_id']) ? 1 : 0;
            $param = [];

            $param['user_id'] = $user_id;
            $param['session_id'] = $this->session_id;
            $param['data'] = $data;
            $param['login_time'] = !empty($this->full_data['login_time']) ? $this->full_data['login_time'] : REQUEST_TIME;
            $param['expire_time'] = $this->expire_time;
            $param['is_admin'] = $is_admin;
            $param['client_ip'] = $this->_ip;
            $param['ts'] = date('Y-m-d H:i:s');

            $param = json_encode($param, JSON_UNESCAPED_UNICODE);

            $GLOBALS['redis']->pushPrefix(XY_PREFIX)->select(REDIS_DB_SESSION);

            $key = $this->redis_prefix . $this->session_id;
            if (!$GLOBALS['redis']->setex($key, $this->expire_time - REQUEST_TIME, $param)) {
                return false;
            }

            // 记录userId对应值
            if ($user_id > 0) {
                $key = $this->redis_prefix . $is_admin . '_' . $user_id;
                if (!$GLOBALS['redis']->setex($key, $this->expire_time - REQUEST_TIME, $param)) {
                    return false;
                }
            }

            // 记录在线用户(不记录后台用户)
            if (!$is_admin && $user_id) {
                (new onlineUser())->recordOnline($user_id);
            }

            $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);
        }

        return true;
    }

    public function __destruct()
    {
        if ($this->inited === true) {
            $this->save_session();
        }
    }

    public function offsetSet($offset, $value)
    {
        if ($this->inited === false) {
            $this->init();
        }

        if (is_null($offset)) {
            $this->_SESSION[] = $value;
        } else {
            $this->_SESSION[$offset] = $value;
        }
    }

    // 是否登录,将会调用此方法
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
}