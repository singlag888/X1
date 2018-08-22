<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class admins
{
    const CACHE_TIME = 600;

    static public function getItem($id, $isEnabled = 1)
    {
        $cacheKey = $id;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM admins WHERE admin_id = ' . intval($id);
            $result = $GLOBALS['db']->getRow($sql);
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }
        if ($isEnabled !== NULL) {
            return $result && ($result['is_enabled'] == $isEnabled);
        }

        return $result;
    }

    static public function getItems($group_id = 0, $is_enabled = -1)
    {
        $cacheKey = __FUNCTION__ . $group_id . $is_enabled;
        if (($result = $GLOBALS['mc']->get(__CLASS__, $cacheKey)) === false) {
            $sql = 'SELECT * FROM admins WHERE 1';
            if ($group_id != 0) {
                $sql .= " AND group_id = " . intval($group_id);
            }
            if ($is_enabled != -1) {
                $sql .= " AND is_enabled = " . intval($is_enabled);
            }
            $result = $GLOBALS['db']->getAll($sql, array(), 'admin_id');
            $GLOBALS['mc']->set(__CLASS__, $cacheKey, $result, self::CACHE_TIME);
        }

        return $result;
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('参数无效');
        }

        $sql = 'SELECT * FROM admins WHERE admin_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(), 'admin_id');
    }

    static public function getItemsByNames($names)
    {
        if (!$names) {
			return array();
		}

        $sql = '';
        if (is_array($names)) {
            $sql .= "SELECT * FROM admins WHERE BINARY username IN ('" . implode("','", $names) . "')";
        }
        else {
            $sql .= "SELECT * FROM admins WHERE BINARY username = '$names'";
        }

        return $GLOBALS['db']->getAll($sql, array(),'username');
    }

    //管理员后台登录 返回非数组表示出错
    static public function login($username, $pwd)
    {
        //111111 安全过滤检查！
        if (!preg_match('`^[a-zA-Z]\w{3,20}$`ims', $username)) {
            adminLogs::addLog(0, "{$username}登录失败：用户名非法");
            return 1;   //用户名或密码不正确
        }

        $sql = "SELECT * FROM admins WHERE username = '$username'";
        if (!$user = $GLOBALS['db']->getRow($sql)) {
            adminLogs::addLog(0, "{$username}登录失败：无此用户");
            return 1;
        }
        if ($user['pwd'] != $pwd) {
            adminLogs::addLog(0, "{$username}登录失败：密码不对", $username);
            return 2;
        }
        if ($user['is_enabled'] != 1) {
            adminLogs::addLog(0, "{$username}登录失败：已被冻结", $username);
            return 3;   //被禁用
        }

        //判断允许的ip
        $group = adminGroups::getItem($user['group_id']);
        if (trim($group['allow_ips']) != '') {
            $allow_ips = explode(',', $group['allow_ips']);
            if (!mattchIpSection($GLOBALS['REQUEST']['client_ip'],$allow_ips)) {
                adminLogs::addLog(0, "{$username}登录失败：非法IP{$GLOBALS['REQUEST']['client_ip']},允许IP{$group['allow_ips']}", $username);
                return 4;
            }
        }

        $sql = "UPDATE admins SET last_ip='" . $GLOBALS['REQUEST']['client_ip'] . "', last_time = '".date('Y-m-d H:i:s')."' WHERE admin_id=" . $user['admin_id'];
        $GLOBALS['db']->query($sql, array(), 'u');

        adminLogs::addLog(1, '登录成功', $username);

        return $user;
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->insert('admins', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        return $GLOBALS['db']->updateSM('admins',$data,array('admin_id'=>$id));
    }

    static public function deleteItem($id)
    {
        if (!is_numeric($id)) {
            throw new exception2('参数无效');
        }

        $sql = "DELETE FROM admins WHERE admin_id = $id";

        return $GLOBALS['db']->query($sql, array(), 'd');
    }


    /**
     * author snow 获取当前用户的权限
     * @param $options
     * @return array
     */
    public static function getAdminPermission($options = [])
    {



        //>>从缓存中取出用户信息
        $admin_id = $GLOBALS['SESSION']['admin_id'];
        if (is_array($options) && !empty($options))
        {
            $tmp = [];
            foreach ($options as $key => $val)
            {
                $tmpStr = implode('/', $val);
                $tmp[$key] = "'{$tmpStr}'";
            }

            $in = implode(',', $tmp);
            $sql =<<<SQL
SELECT menu_id,CONCAT(control,'/', `action`) AS route FROM adminmenus WHERE FIND_IN_SET(menu_id,(
SELECT ag.priv_list FROM admingroups AS ag,admins AS a
WHERE 1 
AND ag.group_id = a.group_id 
AND a.admin_id = {$admin_id})
) 
AND  CONCAT(control,'/', `action`) IN({$in})
SQL;
            $result = $GLOBALS['db']->getAll($sql,[], 'route');
            if (is_array($result) && !empty($result)) {
                //>>忽略大小写
                $tmpRoute = explode(',',strtolower(implode(',',array_keys($result))));

                foreach ($tmp as $key => $val)
                {
                    $val = trim($val,'\'');
                    $tmp[$key] = in_array(strtolower($val),$tmpRoute) ?  true : false;
                }

                unset($tmpRoute);

            } else {
                foreach ($tmp as $key => $val)
                {
                    $tmp[$key] = false;
                }
            }


        }

        return $tmp;
    }

}
