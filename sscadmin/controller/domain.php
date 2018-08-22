<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：域名管理
 */
class domainController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'domainList' => '域名列表',
        'addDomain' => '增加域名',
        //'editDomain'    => '修改域名',
        'cancelAssociate' => '取消关联域名',
        'associate' => '关联域名',
        'deleteDomain' => '删除域名',
        'allocDomain' => '分配域名',
        'manuallyAssociate' => '域名手动分配',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function domainList()
    {
        $top_id = $this->request->getGet('top_id', 'intval', 0);
        $domain_id = $this->request->getGet('domain_id', 'intval', 0);

        //得到所有总代
        // $tops = users::getUserTree(0);
        $tops = users::getUserTreeField([
            'field' => ['user_id', 'username', 'type'],
            'parent_id' => 0,
        ]);
        self::$view->setVar('tops', $tops);

        //得到所有已分配域名
        $userDomains = domains::getUserDomains($top_id, $domain_id);
        self::$view->setVar('userDomains', $userDomains);
        self::$view->setVar('domains', domains::getCanBoundDomains());
        self::$view->setVar('domainsWithKey', domains::getItemsWithKey());
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加域名', 'url' => url('domain', 'addDomain')), 1 => array('title' => '推广域名手动分配', 'url' => url('domain', 'manuallyAssociate'))));
        self::$view->render('domain_domainlist');
    }

    public function addDomain()
    {
        $locations = array(0 => array('title' => '返回域名列表', 'url' => url('domain', 'domainList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            $names = $this->request->getPost('names', 'trim');

            $type = 1;//默认都是推广类型
            //这样*nix/win通用了
            $names = explode(",", trim(trim($names,' '),','));
            foreach ($names as $k => $v) {
                $names[$k] = trim($names[$k]);
                if($names[$k] != ''){
                    $data = array(
                        'name' => $names[$k],
                        'status' => 1, //0已删除 1从未使用 8使用中
                        'type' => $type,
                    );
                    if (!domains::addItem($data)) {
                        showMsg("添加域名失败!请检查数据输入是否完整。");
                    }
                }
            }

            showMsg("添加成功，本次共添加 " . count($names) . " 个域名");
        }

        self::$view->render('domain_adddomain');
    }

    //取消关联域名
    public function cancelAssociate()
    {
        $locations = array(0 => array('title' => '返回域名列表', 'url' => url('domain', 'domainList')));
        $deleteItems = $this->request->getPost('deleteItems', 'array');
        if (!$deleteItems) {
            showMsg("参数无效", 1, $locations);
        }

        foreach ($deleteItems as $v) {
            $parts = explode(',', $v);
            if (!domains::deleteUserDomain($parts[0], $parts[1])) {
                showMsg("取消关联失败", 1);
            }
        }

        showMsg("取消关联成功", 0, $locations);
    }

    //关联域名
    public function associate()
    {
        $locations = array(0 => array('title' => '返回域名列表', 'url' => url('domain', 'domainList')));
        $domainIds = $this->request->getPost('domainIds', 'array');
        $top_id = $this->request->getPost('top_id', 'intval');
        if (!$domainIds || $top_id == -1) {
            showMsg("参数无效", 1);
        }

        if ($top_id == 0) {
            //$tops = users::getUserTree(0);
            $tops = users::getUserTreeField([
                'field' => ['user_id', 'username'],
                'parent_id' => 0,
            ]);
        }
        else {
            $top = users::getItem($top_id);
            if (!$top || $top['parent_id'] != 0) {
                showMsg("总代参数无效", 1);
            }
            $tops = array($top);
        }

        $count = 0;
        $domains = domains::getItemsById($domainIds);
        foreach ($domains as $v) {
            foreach ($tops as $vv) {
                $domainUser = domains::getUserDomains(0, $v['domain_id'], 1);
                if(!empty($domainUser)) {
                    continue;
                }

                $data = array(
                    'top_id' => $vv['user_id'],
                    'username' => $vv['username'],
                    'domain_id' => $v['domain_id'],
                );

                if (!domains::addUserDomain($data)) {
                    showMsg("关联失败", 0, $locations);
                }
                $count++;
            }
        }

        showMsg("关联{$count}个域名成功", 0, $locations);
    }

    //关联域名
    public function manuallyAssociate()
    {
        if ($this->request->getPost('submit', 'trim')) {
            $domain = $this->request->getPost('domain', 'trim');
            $items = domains::getItemsByName($domain);

            if($items) {
                if($items[$domain]['type'] == 1) {
                    if(isset(domains::getDomainUser($domain)['user_id']) && domains::getDomainUser($domain)['user_id']) {
                        showMsg('该推广域名已被绑定');
                    } else {
                        $top_id = $this->request->getPost('top_id', 'intval');
                        $top = users::getItem($top_id);

                        if (!$top) {
                           showMsg('参数无效');
                        }

                        $data = array(
                            'top_id' => $top['user_id'],
                            'username' => $top['username'],
                            'domain_id' => $items[$domain]['domain_id'],
                        );

                        if (!domains::addUserDomain($data)) {
                            showMsg('关联失败');
                        } else {
                            showMsg('关联成功');
                        }
                    }
                } else {
                    showMsg('该域名为非推广域名，请输入推广域名');
                }
            } else {
                showMsg('域名不存在');
            }
        } else if($this->request->getPost('check_domain', 'intval') == 1) {
            $domain = $this->request->getPost('domain', 'trim');
            $items = domains::getItemsByName($domain);

            if($items) {
                if($items[$domain]['type'] == 1) {
                    if(isset(domains::getDomainUser($domain)['user_id']) && domains::getDomainUser($domain)['user_id']) {
                        die(json_encode(array('errno' => 1, 'errstr' => '该推广域名已被绑定')));
                    } else {
                        die(json_encode(array('errno' => 0, 'errstr' => '该域名可用')));
                    }
                } else {
                    die(json_encode(array('errno' => 2, 'errstr' => '该域名为非推广域名，请输入推广域名')));
                }
            } else {
                die(json_encode(array('errno' => 3, 'errstr' => '域名不存在')));
            }
        }
        else {
            // $tops = users::getUserTree(0); // 得到所有总代
            $tops = users::getUserTreeField([
                'field' => ['user_id', 'username', 'type'],
                'parent_id' => 0,
            ]);
            self::$view->setVar('tops', $tops);
            self::$view->render('domain_manuallyassociate');
        }
    }

    public function deleteDomain()
    {
        $locations = array(0 => array('title' => '返回域名列表', 'url' => url('domain', 'domainList')));
        $domainIds = $this->request->getPost('domainIds', 'array');
        if (!$domainIds) {
            showMsg("参数无效", 1);
        }

        foreach ($domainIds as $v) {
            if (domains::deleteUserDomain($v) !== false) {
                if(!domains::deleteItem($v)){
                    showMsg("删除失败", 0, $locations);
                }
            }
        }

        showMsg("删除成功", 0, $locations);
    }

    //分配域名
    public function allocDomain()
    {
        $locations = array(0 => array('title' => '返回域名列表', 'url' => url('domain', 'domainList')));
        $sa = $this->request->getPost('sa', 'trim');

        // $tops = users::getUserTree(0);
        $tops = users::getUserTreeField([
            'field' => ['user_id', 'username', 'type'],
            'parent_id' => 0,
        ]);

        switch ($sa) {
            case 'alloc':
                $domainIds = $this->request->getPost('domainIds', 'array');
                $deleteItems = $this->request->getPost('deleteItems', 'array');
                $domains = domains::getItemsById($domainIds);

                //先删除
                foreach ($domainIds as $v) {
                    domains::deleteUserDomain($v);
                }

                //再加
                foreach ($deleteItems as $v) {
                    $parts = explode(',', $v);  //格式：domain_id,user_id
                    if (!isset($tops[$parts[1]])) {
                        showMsg("参数无效", 1);
                    }

                    if($domains[$parts[0]]['type'] == $tops[$parts[1]]['type']) {
                        if($domains[$parts[0]]['type'] == 1 && domains::getUserDomains(0, $parts[0], 1)) {
                            continue;
                        }

                        $data = array(
                            'top_id' => $parts[1],
                            'username' => $tops[$parts[1]]['username'],
                            'domain_id' => $parts[0],
                        );
                        if (!domains::addUserDomain($data)) {
                            showMsg("分配失败", 1);
                        }
                    }
                }

                showMsg("分配成功", 0, $locations);
                break;
        }

        $domainIds = $this->request->getPost('domainIds', 'array');
        if (!$domainIds) {
            showMsg("参数无效", 1);
        }

        //得到所有总代
        self::$view->setVar('tops', $tops);
        //得到所有已分配域名
        $domains = domains::getItemsById($domainIds);
        self::$view->setVar('domains', $domains);

        $userDomains = domains::getUserDomains(0, $domainIds, 1);
        self::$view->setVar('userDomains', $userDomains);
        self::$view->setVar('actionLinks', $locations);
        self::$view->render('domain_allocdomain');
    }

}

?>