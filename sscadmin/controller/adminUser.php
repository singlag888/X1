<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：管理员管理
 *
 */
class adminUserController extends sscAdminController
{

    static $pageNum = DEFAULT_PER_PAGE;
    //方法概览
    public $titles = array(
        'userList' => '管理员列表',
        'logList' => '管理员操作日志列表',
        'addUser' => '增加管理员',
        'editUser' => '修改管理员',
        'editPwd' => '修改密码',
        'enableUser' => '启用用户',
        'disableUser' => '禁用用户',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function userList()
    {
        $locations = array(
            0 => array('title' => '增加管理员', 'url' => url('adminUser', 'addUser')),
        );
        self::$view->setVar('actionLinks', $locations);

        $group_id = $this->request->getGet('group_id', 'intval', 0);
        $is_enabled = $this->request->getGet('is_enabled', 'intval', 1);
        $users = admins::getItems($group_id, $is_enabled);
        $groups = adminGroups::getItemsById(array_keys(array_spec_key($users, 'group_id')));

        self::$view->setVar('group_id', $group_id);
        self::$view->setVar('is_enabled', $is_enabled);
        self::$view->setVar('firstSecondThirdItems', adminGroups::getAllLevelItems(0, 3));

        self::$view->setVar('users', $users);
        self::$view->setVar('groups', $groups);
        self::$view->render('adminuser_userlist');
    }

    public function logList()
    {
        $username = $this->request->getGet('username', 'trim');
        $control = $this->request->getGet('control', 'trim');
        $action = $this->request->getGet('action', 'trim');
        $ip = $this->request->getGet('ip', 'trim');
        $startDate = $this->request->getGet('startDate', 'trim');
        $endDate = $this->request->getGet('endDate', 'trim');
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * self::$pageNum;
        /******************** snow  修改获取正确页码值******************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
        $adminLogsNumber = adminLogs::getItemsNumber($username, $action, $ip, $startDate, $endDate);
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $adminLogsNumber, self::$pageNum);
        /******************** snow  修改获取正确页码值******************************/
        $adminLogs = adminLogs::getItems($username, $action, $ip, $startDate, $endDate, $startPos, self::$pageNum);

        //加入default  控制器的两个方法 login 和logout
        $menus = array('default' => array('control' => 'default', 'submenu' => array(array('action' => 'login', 'title' => '登陆', 'is_log' => 1), array('action' => 'logout', 'title' => '注销', 'is_log' => 1))));
        $tmp = adminMenus::getItems();

        foreach ($tmp as $v) {
            if ($v['parent_id'] > 0) {
                $menus[$v['control']]['control'] = $v['control'];
                $menus[$v['control']]['submenu'][] = $v;
            }
        }

        self::$view->setVar('allMenus', $menus);
        self::$view->setVar('username', $username);
        self::$view->setVar('control', $control);
        self::$view->setVar('action', $action);
        self::$view->setVar('ip', $ip);
        self::$view->setVar('startDate', $startDate);
        self::$view->setVar('endDate', $endDate);
        self::$view->setVar('adminLogs', $adminLogs);
        self::$view->setVar('pageList', getPageList($adminLogsNumber, self::$pageNum));
        self::$view->render('adminuser_loglist');
    }

    public function addUser()
    {
        $locations = array(0 => array('title' => '返回管理员列表', 'url' => url('adminUser', 'userList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $pwd = $this->request->getPost('pwd', 'trim');
            $pwd2 = $this->request->getPost('pwd2', 'trim');
            if ($this->request->getPost('group_id', 'intval') == 0) {
                showMsg("请选择分组！");
            }
            if ($pwd == "") {
                showMsg("请输入密码！");
            }
            if (strlen($pwd) < 6) {
                showMsg("密码长度必须不少于6位！");
            }
            if ($pwd !== $pwd2) {
                showMsg("两次密码不一致！");
            }
            $username = strtolower($this->request->getPost('username', 'trim'));
            if (admins::getItemsByNames($username)) {
                showMsg("该管理员已存在！");
            }
            $data = array(
                'username' => $username,
                'pwd' => md5($pwd),
                'group_id' => $this->request->getPost('group_id', 'intval'),
                'is_enabled' => $this->request->getPost('is_enabled', 'intval'),
                'last_ip' => '0.0.0.0',
                'last_time' => '0000-00-00 00:00:00',
                'date' => date('Y-m-d H:i:s'),
            );
            if (!admins::addItem($data)) {
                showMsg("添加管理员失败!请检查数据输入是否完整。");
            }

            showMsg("添加成功！", 0, $locations);
        }
        $groups = adminGroups::getItems();

        self::$view->setVar('groups', $groups);
        self::$view->setVar('firstSecondThirdItems', adminGroups::getAllLevelItems(0, 3));
        self::$view->render('adminuser_adduser');
        $GLOBALS['mc']->flush();
    }

    public function editUser()
    {
        $locations = array(0 => array('title' => '返回管理员列表', 'url' => url('adminUser', 'userList')));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $pwd = $this->request->getPost('pwd', 'trim');
            $pwd2 = $this->request->getPost('pwd2', 'trim');
            $data = array(
                'username' => $this->request->getPost('username', 'trim'),
                'group_id' => $this->request->getPost('group_id', 'intval'),
                'is_enabled' => $this->request->getPost('is_enabled', 'intval'),
            );
            if ($pwd != "") {
                if (strlen($pwd) < 6) {
                    showMsg("密码长度必须不少于6位！", 1, $locations);
                }
                if ($pwd !== $pwd2) {
                    showMsg("两次密码不一致！", 1, $locations);
                }
                $data['pwd'] = md5($pwd);
            }
            $user_id = $this->request->getPost('user_id', 'trim');
            if (!admins::updateItem($user_id, $data)) {
                showMsg("没有数据被更新！", 1, $locations);
            }

            /***************** now 把相应管理员踢下线 start************************/
            $this->_delUserFromSession($user_id);
            /***************** now 把相应管理员踢下线 end  ************************/
            showMsg("更新成功！", 0, $locations);
        }
        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
            showMsg("参数无效");
        }
        $user = admins::getItem($user_id, NULL);
        $groups = adminGroups::getItems();

        self::$view->setVar('user', $user);
        self::$view->setVar('groups', $groups);
        self::$view->setVar('firstSecondThirdItems', adminGroups::getAllLevelItems(0, 3));
        self::$view->render('adminuser_adduser');
        $GLOBALS['mc']->flush();
    }


    /********************* snow 禁用用户成功后马上踢下线 start**************************************/

    /**
     * author  snow  万恶的redis 切库
     * 踢用户下线  删除redis 和session 数据
     * @param $user_id  int 用户id
     * @return mixed
     */
    private  function _delUserFromSession($user_id)
    {
        //>>第一步,判断并获取需要踢下线的所有用户
            //>>获取管理员信息并转换为数组,获取session_id
        $GLOBALS['redis']->select(REDIS_DB_SESSION);
        $session_id = $this->_getSessionIdByUser_id($user_id);
            $GLOBALS['redis']->del('session_1_' . $user_id);
            if (!empty($session_id))
            {
                //>>删除相应session
                $GLOBALS['redis']->del('session_' . $session_id);
            };
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
        return true;
    }

    /**
     * 根据管理员id 获取session_id
     * @param $user_id  int 根据管理员id
     * @return string string session_id
     */
    private function _getSessionIdByUser_id($user_id)
    {
        $userInfo = json_decode($GLOBALS['redis']->get('session_1_' . $user_id), true);
        return  is_array($userInfo) && !empty($userInfo) ? $userInfo['session_id'] : '';
    }
    /********************* snow 禁用用户成功后马上踢下线 end**************************************/
    public function editPwd()
    {
        $locations = array(0 => array('title' => '修改密码', 'url' => url('adminUser', 'editPwd')));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            $pwd = $this->request->getPost('pwd', 'trim');
            $pwd2 = $this->request->getPost('pwd2', 'trim');
            if (strlen($pwd) < 6) {
                showMsg("密码长度必须不少于6位！", 1, $locations);
            }
            if ($pwd !== $pwd2) {
                showMsg("两次密码不一致！", 1, $locations);
            }
            $data['pwd'] = md5($pwd);

            if (!admins::updateItem($GLOBALS['SESSION']['admin_id'], $data)) {
                showMsg("没有数据被更新！", 1, $locations);
            }

            showMsg("更新成功！", 0, $locations);
        }

        $user = admins::getItem($GLOBALS['SESSION']['admin_id'], NULL);
        $groups = adminGroups::getItems();

        self::$view->setVar('user', $user);
        self::$view->setVar('groups', $groups);
        self::$view->setVar('firstSecondThirdItems', adminGroups::getAllLevelItems(0, 3));
        self::$view->render('adminuser_adduser');
    }

    public function enableUser()
    {
        $locations = array(0 => array('title' => '返回管理员列表', 'url' => url('adminUser', 'userList')));
        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
            showMsg("参数无效！", 1, $locations);
        }

        $data = array(
            'status' => 1,
        );
        if (!admins::updateItem($user_id, $data)) {
            showMsg("没有数据被更新！", 0, $locations);
        }

        showMsg("更新成功！", 0, $locations);
    }

    public function disableUser()
    {
        $locations = array(0 => array('title' => '返回管理员列表', 'url' => url('adminUser', 'userList')));
        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
            showMsg("参数无效！", 1, $locations);
        }

        $data = array(
            'status' => 0,
        );
        if (!admins::updateItem($user_id, $data)) {
            showMsg("没有数据被更新！", 0, $locations);
        }

        showMsg("更新成功！", 0, $locations);
    }

    //暂不允许删除，可以将其禁用
//    public function deleteUser()
//    {
//        $locations  = array(0 => array('title'=>'返回管理员列表','url'=>url('adminUser','userList')));
//        if (!$user_id = $this->request->getGet('user_id', 'trim')) {
//            showMsg("参数无效！", 1, $locations);
//        }
//
//        if (!admins::deleteItem($user_id)) {
//            showMsg("删除数据失败！", 0, $locations);
//        }
//
//        showMsg("删除数据成功！", 0, $locations);
//    }
}

?>