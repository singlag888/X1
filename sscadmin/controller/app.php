<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：管理员组别管理
 */
class appController extends sscAdminController
{
    //方法概览
    public $titles = array(
        'addAlert' => '添加/修改 app启动页',
        'setting' => '添加/修改 app基础设置',
        'lottery' => '添加/修改 app彩种图标',
        'systemMaintenance' => '添加/修改 app维护设置',
        'lotteryList' => 'app 彩种显示列表编辑',
        'versionList' => 'app 版本控制',
        'addVersion' => 'app 添加版本',
        'editVersion' => 'app 编辑版本',
        'deleteVersion' => 'app 删除版本',
        'pushVersion' => 'app 推送版本更新',
        'helpList' => '帮助中心',
        'editHelp' => '编辑帮助文档',
        'showHelp' => '查看帮助文档',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }
    public function pushVersion(){
        $locations = array(0 => array('title' => '返回版本列表', 'url' => url('app', 'versionList')));
        $appVersion = M('appVersion');
        if (empty($id = $this->request->getGet('id', 'intval'))) showMsg("参数无效");
        if (empty($version=$appVersion->where(['id'=>$id])->find())) {
            showMsg("该版本不存在");
        }
        $type=-1;
        $msg=[];
        if(!empty($version['push_msg'])){
            $msg=unserialize($version['push_msg']);
        }
        if(!empty($version['ios_version'])&&!empty($version['andr_version'])) {
            $type=0;
            $alert=['ios'=>"最新{$version['ios_version']}版本,快来下载更新吧!",'andr'=>"最新{$version['andr_version']}版本,快来下载更新吧!"];
            $title=['ios'=>'出新版本啦!','andr'=>'出新版本啦!'];

            if(isset($msg['title']['ios']))$title['ios']=$msg['title']['ios'];
            if(isset($msg['title']['andr']))$title['andr']=$msg['title']['andr'];
            if(isset($msg['alert']['ios']))$alert['ios']=$msg['alert']['ios'];
            if(isset($msg['alert']['andr']))$alert['andr']=$msg['alert']['andr'];

        }elseif(!empty($version['ios_version'])) {
            $type = 1;
            $alert['ios']="最新{$version['ios_version']}版本,快来下载更新吧!";
            $title['ios']='出新版本啦!';
            if(isset($msg['title']['ios']))$title['ios']=$msg['title']['ios'];
            if(isset($msg['alert']['ios']))$alert['ios']=$msg['alert']['ios'];
        }elseif(!empty($version['andr_version'])) {
            $type=2;
            $alert['andr']="最新{$version['andr_version']}版本,快来下载更新吧!";
            $title['andr']='出新版本啦!';
            if(isset($msg['title']['andr']))$title['andr']=$msg['title']['andr'];
            if(isset($msg['alert']['andr']))$alert['andr']=$msg['alert']['andr'];
        }
        if ($type==-1) {
            showMsg("该版本不存在");
        }
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $ios_title=$this->request->getPost('ios_title','string','');
            $andr_title=$this->request->getPost('andr_title','string','');
            $ios_alert=$this->request->getPost('ios_alert','string','');
            $andr_alert=$this->request->getPost('andr_alert','string','');
            if($type==0) {
                if(!empty($ios_title)){
                    $title['ios']=mb_strlen($ios_title)>20?mb_substr($ios_title,0,20).'...':$ios_title;
                }
                if(!empty($andr_title)){
                    $title['andr']=mb_strlen($andr_title)>20?mb_substr($andr_title,0,20).'...':$andr_title;
                }
                if(!empty($ios_alert)){
                    $alert['ios']=mb_strlen($ios_alert)>50?mb_substr($ios_alert,0,50).'...':$ios_alert;
                }
                if(!empty($andr_alert)){
                    $alert['andr']=mb_strlen($andr_alert)>50?mb_substr($andr_alert,0,50).'...':$andr_alert;
                }
            }
            elseif($type==1) {
                if(!empty($ios_title)){
                    $title['ios']=mb_strlen($ios_title)>20?mb_substr($ios_title,0,20).'...':$ios_title;
                }
                if(!empty($ios_alert)){
                    $alert['ios']=mb_strlen($ios_alert)>50?mb_substr($ios_alert,0,50).'...':$ios_alert;
                }
            }
            elseif($type==2) {
                if(!empty($andr_title)){
                    $title['andr']=mb_strlen($andr_title)>20?mb_substr($andr_title,0,20).'...':$andr_title;
                }
                if(!empty($andr_alert)){
                    $alert['andr']=mb_strlen($andr_alert)>50?mb_substr($andr_alert,0,50).'...':$andr_alert;
                }
            }
            else showMsg("该版本不存在");
            M()->startTrans();
            $res=$appVersion->where(['id'=>$id])->update(['push_msg'=>serialize(['title'=>$title,'alert'=>$alert])]);
            if($res===false){
                M()->rollback();
                showMsg('推送数据失败1');
            }
            if($this->pushAll($title, $alert,['type'=>'1'], $type)!==true){
                M()->rollback();
                showMsg('推送数据失败2');
            }
            M()->commit();
            showMsg("推送数据成功", 0, $locations);
        }
        self::$view->setVar('type', $type);
        self::$view->setVar('id', $id);
        self::$view->setVar('alert', $alert);
        self::$view->setVar('title', $title);
        self::$view->render('app_addversionPush');
    }
    private function _handle_version(){
        $appVersion = M('appVersion');
        $iosList = $appVersion->field('ios_version,ios_describe,ios_update')->where("ios_version !='' and status = 1")->order('create_time desc')->select();
        $andrList = $appVersion->field('andr_version,andr_describe,andr_update')->where("andr_version !='' and status = 1")->order('create_time desc')->select();
        //>>写入redis缓存
        if (!empty($iosList)) {
            $GLOBALS['redis']->select(REDIS_DB_APP);
            $res = $GLOBALS['redis']->hset('appset', 'iosVersions', serialize($iosList));
            if(empty($iosList)||!isset($iosList[0])){
                $new=[];
            }else{
                $new=$iosList[0];
            }
            $res1 = $GLOBALS['redis']->hset('appset', 'iosNewVersion', serialize($new));
            $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
            if ($res === false||$res1 === false) {
                return false;
            }
        }
        if (!empty($andrList)) {
            $GLOBALS['redis']->select(REDIS_DB_APP);
            $res = $GLOBALS['redis']->hset('appset', 'andrVersions', serialize($andrList));
            if(empty($andrList)||!isset($andrList[0])){
                $new=[];
            }else{
                $new=$andrList[0];
            }
            $res1 = $GLOBALS['redis']->hset('appset', 'andrNewVersion', serialize($new));
            $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
            if ($res === false||$res1 === false) {
                return false;
            }
        }
        return true;
    }
    public function deleteVersion()
    {
        $locations = array(0 => array('title' => '返回版本列表', 'url' => url('app', 'versionList')));
        if (empty($id = $this->request->getGet('id', 'intval'))) showMsg("参数无效");
        $appVersion = M('appVersion');
        //JYZ-466 公告修改：后台始终可以编辑 ；置顶但过期的公告不能在前台显示；
        if (empty($version=$appVersion->where(['id'=>$id])->find())) {
            showMsg("该版本不存在");
        }
        M()->startTrans();
        if ($appVersion->where(['id'=>$id])->delete()==false) {
            showMsg("删除数据失败", 0, $locations);
        }
        if($this->_handle_version()===false){
            M()->rollback();
            showMsg('删除失败');
        }
        M()->commit();
        showMsg("删除数据成功", 0, $locations);
    }
    public function editVersion()
    {
        $locations = array(0 => array('title' => '返回版本列表', 'url' => url('app', 'versionList')));
        if (empty($id = $this->request->getGet('id', 'intval'))) showMsg("参数无效");
        $appVersion = M('appVersion');
        //JYZ-466 公告修改：后台始终可以编辑 ；置顶但过期的公告不能在前台显示；
        if (empty($version=$appVersion->where(['id'=>$id])->find())) {
            showMsg("该版本不存在");
        }
        //修改数据
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $ios_version = $this->request->getPost('ios_version', 'trim', '', false);
            $andr_version = $this->request->getPost('andr_version', 'trim', '', false);
            $ios_describe = $this->request->getPost('ios_describe', 'trim', '', false);
            $andr_describe = $this->request->getPost('andr_describe', 'trim', '', false);
            $ios_update = $this->request->getPost('ios_update', 'intval', 0, false);
            $andr_update = $this->request->getPost('andr_update', 'intval', 0, false);
            if (empty($ios_version) && empty($andr_version)) showMsg('请输入ios或安卓版本号');
            if (!empty($ios_version) && empty($ios_describe)) showMsg('请填写ios版本描述');
            if (!empty($andr_version) && empty($andr_describe)) showMsg('请填写安卓版本描述');
            $data = array(
                'ios_version' => $ios_version,
                'andr_version' => $andr_version,
                'ios_describe' => $ios_describe,
                'andr_describe' => $andr_describe,
                'ios_update' => $ios_update==1?1:0,
                'andr_update' => $andr_update==1?1:0,
                'status' => 1,
                'create_time' => time(),
            );
            M()->startTrans();
            $res = $appVersion->where(['id'=>$id])->update($data);
            if ($res !== false) {
                if($this->_handle_version()===false){
                    M()->rollback();
                    showMsg('编辑失败');
                }
                M()->commit();
                showMsg("编辑成功！", 0, $locations);
            }
            M()->rollback();
            showMsg('编辑失败');
        }
        self::$view->setVar('version', $version);
        self::$view->render('app_addversion');
    }

    public function addVersion()
    {
        $locations = array(0 => array('title' => '返回版本列表', 'url' => url('app', 'versionList')));
        //新增数据
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $ios_version = $this->request->getPost('ios_version', 'trim', '', false);
            $andr_version = $this->request->getPost('andr_version', 'trim', '', false);
            $ios_describe = $this->request->getPost('ios_describe', 'trim', '', false);
            $andr_describe = $this->request->getPost('andr_describe', 'trim', '', false);
            $ios_update = $this->request->getPost('ios_update', 'intval', 0, false);
            $andr_update = $this->request->getPost('andr_update', 'intval', 0, false);
            if (empty($ios_version) && empty($andr_version)) showMsg('请输入ios或安卓版本号');
            $appVersion = M('appVersion');
            if (!empty($ios_version)) {
                if(empty($ios_describe)){
                    showMsg('请填写ios版本描述');
                }
                if(!empty($appVersion->where("andr_version='$andr_version' and status!=-1")->find())){
                    showMsg('此ios版本已存在');
                }
            }
            if (!empty($andr_version)) {
                if(empty($andr_describe)){
                    showMsg('请填写安卓版本描述');
                }
                if(!empty($appVersion->where("andr_version='$andr_version' and status!=-1")->find())){
                    showMsg('此ios版本已存在');
                }
            }
            $data = array(
                'ios_version' => $ios_version,
                'andr_version' => $andr_version,
                'ios_describe' => $ios_describe,
                'andr_describe' => $andr_describe,
                'ios_update' => $ios_update==1?1:0,
                'andr_update' => $andr_update==1?1:0,
                'status' => 1,
                'create_time' => time(),
            );
            M()->startTrans();
            $id = $appVersion->insert($data);
            if ($id !== false) {
                if($this->_handle_version()===false){
                    M()->rollback();
                    showMsg('编辑失败');
                }
                M()->commit();
                showMsg("添加成功！", 0, $locations);
            }
            M()->rollback();
            showMsg('添加失败');
        }
        self::$view->render('app_addversion');
    }

    //版本列表
    public function versionList()
    {
        $count_versions = M('appVersion')->field('count(id) as count')->where('status > 1')->find();
        $count_versions = !empty($count_versions['count']) ? $count_versions['count'] : 0;
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $count_versions);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $field=<<<feild
id,ios_version,andr_version,
 CASE
     WHEN CHAR_LENGTH(ios_describe)>20 THEN CONCAT(LEFT(ios_describe,20), '...')
     ELSE ios_describe END AS `ios_describe`,
 CASE
     WHEN CHAR_LENGTH(andr_describe)>20 THEN CONCAT(LEFT(andr_describe,20), '...')
     ELSE andr_describe END AS `andr_describe`
feild;

        $versions = M('appVersion')->field($field)->where('status > 0')->order('create_time desc')->limit($startPos, DEFAULT_PER_PAGE)->select();

        self::$view->setVar('versions', $versions);
        self::$view->setVar('noticesNumber', $count_versions);
        self::$view->setVar('pageList', getPageList($count_versions, DEFAULT_PER_PAGE));
        self::$view->setVar('canEdit', adminGroups::verifyPriv(array(CONTROLLER, 'editVersion')));
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteVersion')));
        self::$view->setVar('canPush', adminGroups::verifyPriv(array(CONTROLLER, 'pushVersion')));
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加新版本', 'url' => url('app', 'addVersion'))));
        self::$view->render('app_versionlist');
    }

    public function lotteryList()
    {
        $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $lotteryList = $this->request->getPost('lotteryList', 'array', []);
            if (count($lotteryList) > 5 || count($lotteryList) < 1) response(['列表长度为1-5,不能超出此范围'], 'MSG');
            if (count(array_unique(array_column($lotteryList, 'list_id'))) != 5) response(['提交数据格式错误,请重试!'], 'MSG');
            $names = array_column($lotteryList, 'name');
            if (count(array_unique($names)) != count($names)) response(['请保持列表名称的唯一性!'], 'MSG');
            $count = count($lotterys);
            $lotteryids = array_column($lotterys, 'lottery_id');
            $redisLotteryList = [];
            $lotteryListRe = [];
            foreach ($lotteryList as $item) {
                if ($item['list_id'] > 5 || $item['list_id'] < 1) response(['列表长度为1-5,不能超出此范围'], 'MSG');
                if ($item['is_use'] == 1) {
                    if (empty($item['name']) || mb_strlen($item['name']) > 4) {
                        response(['列表名称不能为空且长度不能大于4'], 'MSG');
                    }
                    if (empty($item['lotteryList'])) {
                        response(['开启的列表展示彩种不能为空'], 'MSG');
                    }
                    if (!empty(array_diff($item['lotteryList'], $lotteryids)) || count($item['lotteryList']) > $count) {
                        response(['数据错误,请选择存在的彩种'], 'MSG');
                    }
                    $redisLotteryList['id_' . $item['list_id']] = $item;
                }
                $lotteryListRe['id_' . $item['list_id']] = $item;
            }
            M()->startTrans();
            $appLotteryList = M('appLotteryList');
            if (!empty($appLotteryList->find())) {
                if (!$appLotteryList->where('1')->delete()) {
                    M()->rollback();
                    response(['编辑失败,请重试'], 'MSG');
                    exit;
                }
            }
            $id = $appLotteryList->insert(['list' => serialize($lotteryListRe)]);
            if ($id !== false) {
                //>>切换到app库
                $GLOBALS['redis']->select(REDIS_DB_APP);
                $res = $GLOBALS['redis']->hset('appset', 'appLotteryImg', serialize($redisLotteryList));
                $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
                if ($res !== false) {
                    M()->commit();
                    response(['添加成功', 0, [['title' => '返回', 'url' => url('app', 'lotteryList')]]], 'MSG');
                }
            } else {
                M()->rollback();
                $error = $appLotteryList->getError();
                !$error && $error = '添加失败';
                response([$error], 'MSG');
            }
        }
        $loteryList = M('appLotteryList')->find();
        if (empty($loteryList)) {
            $loteryList = [
                'id_1' => [
                    'list_id' => 1,
                    'name' => '信用玩法',
                    'is_use' => 1,
                    'lotteryList' => [1, 26, 12, 17, 11, 2, 13, 9, 10, 4, 5, 6, 7, 8, 14, 19, 22, 23, 21, 24, 18, 16, 25]
                ],
                'id_2' => [
                    'list_id' => 2,
                    'name' => '时时彩',
                    'is_use' => 1,
                    'lotteryList' => [1, 4, 8, 18, 15, 11, 24]
                ],
                'id_3' => [
                    'list_id' => 3,
                    'name' => '11选5',
                    'is_use' => 1,
                    'lotteryList' => [2, 5, 6, 7, 16]
                ],
                'id_4' => [
                    'list_id' => 4,
                    'name' => '低频彩',
                    'is_use' => 1,
                    'lotteryList' => [9, 10, 21, 22]
                ],
                'id_5' => [
                    'list_id' => 5,
                    'name' => '快乐彩',
                    'is_use' => 1,
                    'lotteryList' => [17, 12, 13, 19, 23, 14, 25]
                ],
            ];
        } else $loteryList = unserialize($loteryList['list']);

        self::$view->setVar('loteryList', $loteryList);
        self::$view->setVar('lotterys', $lotterys);
        self::$view->render('app_lotteryList');
    }

    public function lottery()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $datas = [];
            $redis_all = [];
            if ($_FILES) {
                foreach ($_FILES as $ky => $vl) {
                    if (empty($vl['name'])) unset($_FILES[$ky]);
                }
                if (count($_FILES) > 20) response(['一次最多上传20张'], 'MSG');
                $up = new upload();
                $up->set_thumb(100, 80);
                $fs = $up->execute();
                if (empty($fs)) response(['请上传图片'], 'MSG');
                $imgcdn = $this->getimgCdnUrl();
                foreach ($fs as $name => &$item) {
                    $lottery_id = str_replace('m_', '', $name);
                    $data['lottery_id'] = $lottery_id;
                    $redis['lottery_id'] = $lottery_id;
                    $column_name = 'img';
                    if ($item['flag'] != 1) {
                        // 这里返回报错了就
                        $error = '上传' . $item['name'] . '时出错。';
                        $error .= $up->getError($item['flag']);
                        response([$error], 'MSG');

                        $data[$column_name] = '';
                        continue;
                    }
                    $data[$column_name] = $item['dir'] . $item['name'];
                    $redis[$column_name] = !empty($path = $this->matchPath($data[$column_name])) ? $imgcdn . '/' . $path : '';
                    $datas[] = $data;
                    $redis_all[] = $redis;
                    //上传七牛
                    $qiniu = new uptoqiniu($item['name'], $item['dir']);
                    $qiniu->upload();

                    /************************************ snow 添加弹窗上传到阿里云**************************************************/
                    //>>上传到阿里云存储
                    $aliyun = new uploadaliyun($item['name'], $item['dir']);
                    if (($result = $aliyun->upload()) !== true) {
                        showMsg($result);
                    }
                    /************************************ snow 添加弹窗上传到阿里云**************************************************/
                }
            } else {
                response(['请上传图片'], 'MSG');
            }
            if (!empty($datas)) {
                $GLOBALS['db']->startTransaction();
                $values = '';
                foreach ($datas as $val) {
                    $values .= '(' . $val['lottery_id'] . ',"' . $val['img'] . '") ,';
                }
                $values = trim($values, ',');
                $sql = 'INSERT INTO app_lottery_img(lottery_id,img) VALUES ' . $values . ' ON DUPLICATE KEY UPDATE img=VALUES(img)';
                $res = $GLOBALS['db']->query($sql, array(), 'i');
                if ($res === false) {
                    $GLOBALS['db']->rollback();
                    response(['添加修改失败,请重试2'], 'MSG');
                }
                $GLOBALS['redis']->select(REDIS_DB_APP);
                $old_res = $GLOBALS['redis']->hget('appset', 'appLotteryImg');
//                $res=$GLOBALS['redis']->hget('appset','appLotteryImg',serialize($redis_all));
                $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
                $s_lottery_ids = array_column($redis_all, 'lottery_id');
                if (!empty($old_res)) {
                    $old_res = unserialize($old_res);
                    foreach ($old_res as $k => $v) {
                        if (in_array($v['lottery_id'], $s_lottery_ids)) {
                            unset($old_res[$k]);
                        }
                    }
                    $redis_all = array_merge($old_res, $redis_all);
                }
                $GLOBALS['redis']->select(REDIS_DB_APP);
                $res = $GLOBALS['redis']->hset('appset', 'appLotteryImg', serialize($redis_all));
                $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
                if ($res === false) {
                    $GLOBALS['db']->rollback();
                    response(['添加修改失败,请重试3'], 'MSG');
                }
                $GLOBALS['db']->commit();
                response(['添加成功', 0, [['title' => '返回', 'url' => url('app', 'lottery')]]], 'MSG');
            }
            response(['添加修改失败,请重试4'], 'MSG');
        } else {
            $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
            $model = M('appLotteryImg');
            $imgs = $model->where('1')->select();
            if (!empty($imgs)) {
                array_walk($imgs, function (&$item) {
                    if (!empty($item['img'])) $item['img'] = $this->_activityThumbImg($item['img']);
                });
            }
            $data = array_column($imgs, 'img', 'lottery_id');
            self::$view->setVar('lotterys', $lotterys);
            self::$view->setVar('imgs', $data);
            self::$view->render('app_lottery');
        }
    }

    public function systemMaintenance()
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            $data['is_show'] = $is_show = $this->request->getPost('is_show', 'intval', 0);
            $data['info'] = $info = $this->request->getPost('info', 'string', '');
            $data['show_time'] = $show_time = $this->request->getPost('show_time', 'string', '');
            if ($is_show == 1) {
                if (empty($info) || mb_strlen($info) > 40) response(['提示维护信息不能为空,且长度不能大于40'], 'MSG');
                if (empty($show_time)) response(['维护显示时间不能为空'], 'MSG');
            }
            M()->startTrans();
            $systemMaintenance = M('appSystemMaintenance');
            if (!empty($rs = $systemMaintenance->find())) {
                if (!$systemMaintenance->where('1')->delete()) {
                    M()->rollback();
                    response(['添加修改失败,请重试'], 'MSG');
                    exit;
                }
            }
            $id = $systemMaintenance->insert($data);

            if ($id !== false) {
                //>>切换到app库
                $GLOBALS['redis']->select(REDIS_DB_APP);
                $res = $GLOBALS['redis']->hset('appset', 'systemMaintenance', serialize($data));
                $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
                if ($res !== false) {
                    M()->commit();
                    response(['添加成功', 0, [['title' => '返回', 'url' => url('app', 'systemMaintenance')]]], 'MSG');
                }
            } else {
                M()->rollback();
                $error = $systemMaintenance->getError();
                !$error && $error = '添加失败';
                response([$error], 'MSG');
            }
        }

        $systemMaintenance = M('appSystemMaintenance');

        $sm = $systemMaintenance
            ->find();
        self::$view->setVar('sm', $sm);
        self::$view->render('app_systemMaintenance');
    }

    public function setting()
    {
        //修改数据
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            $ios_version = $this->request->getPost('ios_version', 'string', '');
            $warm_prompt = $this->request->getPost('warm_prompt', 'string', '');
            if(empty($warm_prompt))
            {
                response(['请输入支付温馨提示!'], 'MSG');
            }
            if (empty($ios_version)) response(['ios版本号不能为空'], 'MSG');
            $show_menu = $this->request->getPost('show_menu', 'array', []);
            if (empty($show_menu)) response(['请勾选app首页展示按钮,不得低于4个'], 'MSG');
            if (count($show_menu) < 4) response(['app首页展示按钮至少4个'], 'MSG');
            if (count($show_menu) > 5) response(['app首页展示按钮最多5个'], 'MSG');
            $sort = $this->request->getPost('sort', 'string', '');
            if (!empty($sort)) {
                $sort_arr = explode(',', $sort);
            } else {
                $sort_arr = [1, 2, 3, 4, 5, 6];
            }

            if (!empty($diff = array_diff($show_menu, $sort_arr))) {
                $name = '';
                foreach ($diff as $mid) {
                    switch ($mid) {
                        case 1:
                            $name .= '充值,';
                            break;
                        case 2:
                            $name .= '提现,';
                            break;
                        case 3:
                            $name .= '走势,';
                            break;
                        case 4:
                            $name .= '在线客服,';
                            break;
                        case 5:
                            $name .= '电子游戏,';
                            break;
                        case 6:
                            $name .= '电子钱包,';
                            break;
                        default:
                            response(['非法数据,无法执行'], 'MSG');
                    }
                }
                $name = trim($name, ',');
                response(['app首页展示按钮:' . $name . '未排序,请排序'], 'MSG');
            }

            $menu_name = $this->request->getPost('menu_name', 'array', []);
            $app_datas = [
                'is_pretender' => $this->request->getPost('is_pretender', 'intval', 1),
                'show_demo' => $this->request->getPost('show_demo', 'intval', 0),
                'ios_version' => $ios_version,
                'show_menu' => !empty($show_menu) ? implode(',', $show_menu) : '',
                'sort' => !empty($sort) ? $sort : '',
                'warm_prompt'=>$warm_prompt,
            ];
            $has_old = 0;
            $olddata = M('appSetting')->find();
            $menu_info = [];
            if (!empty($olddata)) {
                $has_old = 1;
                $menu_info = !empty($olddata['menu_info']) ? unserialize($olddata['menu_info']) : [];
            }
            $file_datas = [];
            if ($_FILES) {
                $up = new upload();
                $up->set_thumb(100, 80);
                $fs = $up->execute();
                if (empty($fs) && $has_old == 0) response(['请上传图片'], 'MSG');
                if (!empty($fs)) {
                    foreach ($fs as $name => &$item) {
                        $column_name = str_replace('m_', '', $name);
                        if ($item['flag'] != 1) {
                            // 这里返回报错了就
                            $error = '上传' . $item['name'] . '时出错。';
                            $error .= $up->getError($item['flag']);
                            response([$error], 'MSG');

                            $file_datas[$column_name] = '';
                            continue;
                        }
                        $file_datas[$column_name] = $item['dir'] . $item['name'];
                        //上传七牛
                        $qiniu = new uptoqiniu($item['name'], $item['dir']);
                        $qiniu->upload();

                        /************************************ snow 添加弹窗上传到阿里云**************************************************/
                        //>>上传到阿里云存储
                        $aliyun = new uploadaliyun($item['name'], $item['dir']);
                        if (($result = $aliyun->upload()) !== true) {
                            showMsg($result);
                        }
                        /************************************ snow 添加弹窗上传到阿里云**************************************************/
                    }
                }
            } else {
                if ($has_old == 0) response(['请上传图片'], 'MSG');
            }
            $menuArrs = [];
            $sortMenuArrs = [];
            if (!empty($menu_name) && !empty($show_menu)) {
                $imgcdn = $this->getimgCdnUrl();
                foreach ($show_menu as $menu) {
                    if ($menu > 6 || $menu < 1) response(['非法数据,无法执行'], 'MSG');
                    $name = $menu_name[$menu];
                    if (empty($name)) {
                        switch ($menu) {
                            case 1:
                                $name = '充值';
                                break;
                            case 2:
                                $name = '提现';
                                break;
                            case 3:
                                $name = '走势';
                                break;
                            case 4:
                                $name = '在线客服';
                                break;
                            case 5:
                                $name = '电子游戏';
                                break;
                            case 6:
                                $name = '电子钱包';
                                break;
                            default:
                                response(['非法数据,无法执行'], 'MSG');
                        }
                    }
                    $img = !empty($file_datas["menuIcon_" . $menu]) ? $file_datas["menuIcon_" . $menu] : '';
                    if (empty($img) && $has_old == 1) {
                        $img = isset($menu_info[$menu]['img']) && !empty($old_img = $menu_info[$menu]['img']) ? $old_img : '';
                        if (empty($img)) response(['请上传' . $name . '图标'], 'MSG');
                    }
                    $me_arr = [
                        'menu' => $menu,
                        'name' => $name,
                        'img' => !empty($path = $this->matchPath($img)) ? $imgcdn . '/' . $path : '',
                    ];
                    $menuArrs[$menu] = $me_arr;
                }
            }
            foreach ($sort_arr as $sort) {
                if (!empty($menuArrs[$sort])) {
                    $sortMenuArrs[] = $menuArrs[$sort];
                }
            }
            $app_datas['menu_info'] = !empty($menuArrs) && is_array($menuArrs) ? serialize($menuArrs) : '';

            M()->startTrans();
            $appSetting = M('appSetting');
            if ($has_old == 1) {
                if (!$appSetting->where('1')->delete()) {
                    M()->rollback();
                    response(['添加修改失败,请重试'], 'MSG');
                    exit;
                }
            }
            $id = $appSetting->insert($app_datas);

            if ($id !== false) {
                //>>切换到app库
                $app_datas['menu_info'] = !empty($sortMenuArrs) && is_array($sortMenuArrs) ? $sortMenuArrs : '';
                $GLOBALS['redis']->select(REDIS_DB_APP);
                $res = $GLOBALS['redis']->hset('appset', 'appSetting', serialize($app_datas));
                $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
                if ($res !== false) {
                    M()->commit();
                    response(['添加成功', 0, [['title' => '返回', 'url' => url('app', 'setting')]]], 'MSG');
                }
            } else {
                M()->rollback();
                $error = $appSetting->getError();
                !$error && $error = '添加失败';
                response([$error], 'MSG');
            }
        }
        $appSettingModel = M('appSetting');

        $appSetting = $appSettingModel
            ->find();

        if (!empty($appSetting['menu_info'])) {
            $appSetting['menu_info'] = unserialize($appSetting['menu_info']);
            foreach ($appSetting['menu_info'] as &$item) {
                $item['img'] = $this->_activityThumbImg($item['img']);
            }
            unset($item);
        }
        if (!empty($appSetting['show_menu'])) $appSetting['show_menu'] = explode(',', $appSetting['show_menu']);
        self::$view->setVar('app', $appSetting);
        self::$view->render('app_setting');
    }

    private function matchPath($path)
    {
        preg_match('@.*(images_fh.*)$@', $path, $macth);
        return isset($macth[1]) ? $macth[1] : '';
    }

    private function getimgCdnUrl()
    {
        if (file_exists(ROOT_PATH . 'cdn.xml')) {
            $xml = simplexml_load_file(ROOT_PATH . 'cdn.xml');
            $imgCdnUrl = (string)$xml->mobile;
        } else {
            $imgCdnUrl = config::getConfig('mobile_site_main_domain');
        }
        return $imgCdnUrl;
    }

    public function addAlert()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $data = [
                'is_use_wp' => $this->request->getPost('is_use_wp', 'intval', 0),
                'ts' => time(),
            ];
            $has_img = 0;
            $appAlert = M('appAlert');
            if (!empty($rs = $appAlert->find())) {
                if (empty($rs['welcome_page'])) $has_img = 1;
            }
            if ($_FILES) {
                $up = new upload();
                $up->set_thumb(100, 80);
                $fs = $up->execute();
                if (empty($fs)) {
                    if ($data['is_use_wp'] == 1 && $has_img = 0) {
                        response(['请上传图片'], 'MSG');
                    }
                } else {
                    foreach ($fs as $name => &$item) {
                        $column_name = str_replace('m_', '', $name);
                        if ($item['flag'] != 1) {
                            // 这里返回报错了就
                            $error = '上传' . $item['name'] . '时出错。';
                            $error .= $up->getError($item['flag']);
                            response([$error], 'MSG');

                            $data[$column_name] = '';
                            continue;
                        }
                        $data[$column_name] = $item['dir'] . $item['name'];
                        //上传七牛
                        $qiniu = new uptoqiniu($item['name'], $item['dir']);
                        $qiniu->upload();

                        /************************************ snow 添加弹窗上传到阿里云**************************************************/
                        //>>上传到阿里云存储
                        $aliyun = new uploadaliyun($item['name'], $item['dir']);
                        if (($result = $aliyun->upload()) !== true) {
                            showMsg($result);
                        }
                        /************************************ snow 添加弹窗上传到阿里云**************************************************/
                    }
                }
            } else {
                if ($data['is_use_wp'] == 1 && $has_img = 0) response(['请上传图片'], 'MSG');
            }
            M()->startTrans();
            if (!empty($rs)) {
                if (empty($data['welcome_page'])) $data['welcome_page'] = $rs['welcome_page'];
                if (!$appAlert->where('1')->delete()) {
                    M()->rollback();
                    response(['添加修改失败,请重试'], 'MSG');
                    exit;
                }
            }
            $imgcdn = $this->getimgCdnUrl();
            $data['welcome_page'] = !empty($path = $this->matchPath($data['welcome_page'])) ? $imgcdn . '/' . $path : '';
            $id = $appAlert->insert($data);

            if ($id !== false) {
                //>>切换到app库
                $GLOBALS['redis']->select(REDIS_DB_APP);
                $res = $GLOBALS['redis']->hset('appset', 'appAlert', serialize($data));
                $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
                if ($res !== false) {
                    M()->commit();
                    response(['添加成功', 0, [['title' => '返回', 'url' => url('app', 'addAlert')]]], 'MSG');
                }
            } else {
                M()->rollback();
                $error = $appAlert->getError();
                !$error && $error = '添加失败';
                response([$error], 'MSG');
            }

        } else {
            $userAlertModel = M('appAlert');

            $appAlert = $userAlertModel
                ->find();

            if (!empty($appAlert['welcome_page'])) $appAlert['welcome_page'] = $this->_activityThumbImg($appAlert['welcome_page']);

            self::$view->setVar('appAlert', $appAlert);
            self::$view->render('app_alert');
        }
    }

    private function _activityThumbImg($srcImg)
    {
        if ($srcImg == '') {
            return $srcImg;
        }
        preg_match('@.*(images_fh.*)$@', $srcImg, $macth);
        if (isset($macth[1])) {
            $tmp = explode('/', $macth[1]);
            $srcName = $tmp[count($tmp) - 1];
            unset($tmp[count($tmp) - 1]);
            $newImg = implode('/', $tmp);
            $img = $newImg . '/thumb_' . $srcName;

            //return $newImg.'/thumb_'.$srcName;
            /***************  snow 注释 掉下面的代码 .没事转什么码嘛.**********************/

//            //由于系统jpeg类库问题先将jpg图片的缩略图转换成png格式
//            if (preg_match('@^.*jpg$@', $img)) {
//                $img = substr($img, 0, strrpos($img, '.') + 1) . 'png';
//            }
            /***************  snow 注释 掉下面的代码 .没事转什么码嘛.**********************/
        } else {
            $img = '';
        }

        return $img;
    }

    private function _clearCache()
    {
        exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
        exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
        @exec('nohup sh  ' . CLEAR_CACHE_DIR . 'clear_cache.sh &');
    }

    public function pushAll($title,$alert,array $extras, $type = 1)
    {
        $appKey=config::getConfig('app_jpush_key','');
        $masterSecret=config::getConfig('app_jpush_masterSecret','');
        if(empty($appKey)||empty($masterSecret))showMsg('请添加推送相关配置信息');
        if($type==0){
            $ios_title=$title['ios'];
            $andr_title=$title['andr'];
            $ios_alert=$alert['ios'];
            $andr_alert=$alert['andr'];
        } elseif($type==1) {
            $ios_title=$title['ios'];
            $ios_alert=$alert['ios'];
        }else{
            $andr_title=$title['andr'];
            $andr_alert=$alert['andr'];
        }
        require_once FRAMEWORK_PATH . 'library/vendor/autoload.php';
        $client = new JPush\Client(trim($appKey), trim($masterSecret));
        try {
            $pusher = $client->push();
            if ($type == 1) {
                $pusher->setPlatform(array('ios'));
            } elseif ($type == 2) {
                $pusher->setPlatform(array('android'));
            } else {
                $pusher->setPlatform(array('ios', 'android'));
            }
            $pusher->addAllAudience();

            if ($type == 1) {
                $pusher->iosNotification([
                    "title" => $ios_title,
                    "body" => $ios_alert
                ], array(
                    'sound' => 'sound.caf',
                    'content-available' => true,
                    'mutable-content' => true,
                    'category' => 'jiguang',
                    'extras' => $extras,
                ));
            } elseif ($type == 2) {
                $pusher->androidNotification($andr_alert, array(
                    'title' => $andr_title,
                    'extras' => $extras,
                ));
            } else {
                $pusher->iosNotification([
                    "title" => $ios_title,
                    "body" => $ios_alert
                ], array(
                    'sound' => 'sound.caf',
                    'content-available' => true,
                    'mutable-content' => true,
                    'category' => 'jiguang',
                    'extras' => $extras,
                ))->androidNotification($andr_alert, array(
                    'title' => $andr_title,
                    'extras' => $extras,
                ));
            }

            $pusher->options(array(
                'time_to_live' => 86400,
                'apns_production' => JPUSH_SWITCH,
            ))
                ->send();
            return true;

        } catch (\JPush\Exceptions\APIConnectionException $e) {
            return $e->getMessage();
        } catch (\JPush\Exceptions\APIRequestException $e) {
            return $e->getMessage();
        }
    }

    public function editHelp()
    {
        $locations = array(0 => array('title' => '返回帮助中心', 'url' => url('app', 'helpList')));
        $id=$this->request->getGet('id','intval',0);
        if(empty($id))showMsg('参数错误');
        $help = M('appHelp');
        $helpInfo = $help->where(['id'=>$id])->find();
        if(empty($helpInfo))showMsg('查询文档不存在!');
        if(strtolower($_SERVER['REQUEST_METHOD'])=='post'){
            $content=$this->request->getPost('content','string','');
            if(empty($content))showMsg('请填写文档内容');
            $content=htmlspecialchars($content);
            M()->startTrans();
            if($help->where(['id'=>$id])->update(['content'=>$content])===false){
                M()->rollback();
                showMsg('编辑失败');
            }
            $GLOBALS['redis']->select(REDIS_DB_APP);
            $res = $GLOBALS['redis']->hset('appset', 'help_'.$id, $content);
            $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
            if($res===false){
                M()->rollback();
                showMsg('编辑失败');
            }
            M()->commit();
            showMsg('编辑成功',0,$locations);
        }
        self::$view->setVar('helpInfo', $helpInfo);
        self::$view->render('app_addHelp');
    }
    public function showHelp()
    {
        $id=$this->request->getGet('id','intval',0);
        if(empty($id))showMsg('参数错误');
        $help = M('appHelp');
        $helpInfo = $help->where(['id'=>$id])->find();
        if(empty($helpInfo))showMsg('查询文档不存在!');
        self::$view->setVar('helpInfo', $helpInfo);
        self::$view->render('app_addHelp');
    }
    public function helpList()
    {
        $help = M('appHelp');
        $field=<<<feild
id,name,
 CASE
     WHEN CHAR_LENGTH(content)>40 THEN CONCAT(LEFT(content,40), '...')
     ELSE content END AS `show_content`
feild;
        $helpList = $help->field($field)->where('id <= 7')->select();

        self::$view->setVar('canEdit', adminGroups::verifyPriv(array(CONTROLLER, 'editHelp')));
        self::$view->setVar('helpList', $helpList);
        self::$view->render('app_helpList');
    }
}
