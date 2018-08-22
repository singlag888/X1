<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：管理员组别管理
 */
class userAlertController extends sscAdminController
{
    //方法概览
    public $titles = array(
        'alertList' => '弹窗列表',
        'addAlert' => '增加弹窗',
        'editAlert' => '修改弹窗',
        'bindUser' => '绑定用户',
        'changeType' => '改变类型(快速)',
        'delAlert' => '删除弹窗',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function alertList()
    {
//        $offset = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $userAlertModel = new userAlert();
        /******************* snow 获取正确的页码********************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        $alertCount = $userAlertModel->count();
        $offset  = getStartOffset($curPage, $alertCount);
        /******************* snow 获取正确的页码********************************/

        $alertList = $userAlertModel
            ->limit(DEFAULT_PER_PAGE, $offset)
            ->select();

        foreach ($alertList as &$item) {
            $item['main_img'] = $this->_activityThumbImg($item['main_img']);
            $item['m_main_img'] = $this->_activityThumbImg($item['m_main_img']);
        }

        self::$view->setVar('pageList', getPageList($alertCount, DEFAULT_PER_PAGE));
        self::$view->setVar('alertList', $alertList);
        self::$view->setVar('actionLinks', array(0 => array('title' => '新增弹窗', 'url' => url('userAlert', 'addAlert'))));
        self::$view->render('useralert_list');
    }

    public function addAlert()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $data = [
                'title' => $this->request->getPost('title', 'trim'),
                'domain' => $this->request->getPost('domain', 'trim'),
                'content' => $this->request->getPost('content', 'trim'),
                'default' => $this->request->getPost('default', 'intval', 0),
                'type' => $this->request->getPost('type', 'trim'),
            ];

            if ($_FILES) {
                $up = new upload();
                $up->set_thumb(100, 80);
                $fs = $up->execute();

                foreach ($fs as $name => &$item) {
                    if ($item['flag'] != 1) {

                        // 这里返回报错了就
                        $error = '上传'.$item['name'].'时出错。';
                        $error .= $up->getError($item['flag']);
                        response([$error], 'MSG');

                        $data[$name] = '';
                        continue;
                    }
                    $data[$name] = $item['dir'] . $item['name'];
                    //上传七牛
                    $qiniu = new uptoqiniu($item['name'], $item['dir']);
                    $qiniu->upload();

                    /************************************ snow 添加弹窗上传到阿里云**************************************************/
                    //>>上传到阿里云存储
                    $aliyun = new uploadaliyun($item['name'], $item['dir']);
                    if(($result = $aliyun->upload()) !== true){
                        showMsg($result);
                    }
                    /************************************ snow 添加弹窗上传到阿里云**************************************************/
                }
            }

            $userAlert = new userAlert();
            $id = $userAlert->insert($data);

            if ($id !== false) {
                $this->_clearCache();
                // 清除默认项
                if ($data['default'] > 0) {
                    $userAlert->flushRedisCache(0);
                }

                response(['添加成功', 0, [['title' => '返回弹窗列表', 'url' => url('userAlert', 'alertList')]]], 'MSG');
            } else {
                $error = $userAlert->getError();
                !$error && $error = '添加失败';
                response([$error], 'MSG');
            }
        } else {
            self::$view->render('useralert_edit');
        }
    }

    public function editAlert()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $uaId = $this->request->getPost('ua_id', 'intval');
            $data = [
                'title' => $this->request->getPost('title', 'trim'),
                'domain' => $this->request->getPost('domain', 'trim'),
                'content' => $this->request->getPost('content', 'trim', '', 0), // 加0表示不转义
                'default' => $this->request->getPost('default', 'intval', 0),
                'type' => $this->request->getPost('type', 'trim'),
            ];

            $userAlert = new userAlert();
            $oldData = $userAlert->find($uaId);

            if ($_FILES) {
                $up = new upload();
                $up->set_thumb(100, 80);
                $fs = $up->execute();

                foreach ($fs as $name => &$item) {
                    if ($item['flag'] != 1) {

                        // 这里返回报错了就
                        $error = '上传'.$item['name'].'时出错。';
                        $error .= $up->getError($item['flag']);
                        response([$error], 'MSG');

                        $data[$name] = '';
                        continue;
                    }
                    $data[$name] = $item['dir'] . $item['name'];
                    // 上传七牛
                    $qiniu = new uptoqiniu($item['name'], $item['dir']);
                    $qiniu->upload();
/************************************ snow 添加弹窗上传到阿里云**************************************************/
                    //>>上传到阿里云存储
                    $aliyun = new uploadaliyun($item['name'], $item['dir']);
                    if(($result = $aliyun->upload()) !== true){
                        showMsg($result);
                    }
/************************************ snow 添加弹窗上传到阿里云**************************************************/
                    // 如果上传了新图则删除旧图
                    is_file($oldData[$name]) && unlink($this->_activityThumbImg($oldData[$name]));
                }
            }

            $result = $userAlert->where(['ua_id' => $uaId])->update($data);

            if ($result !== false) {
                $this->_clearCache();
                $userTree = explode(',', $oldData['user_tree']);
                $userAlert->flushRedisCache(array_merge($userTree, [0]));

                response(['编辑成功', 0, [['title' => '返回弹窗列表', 'url' => url('userAlert', 'alertList')]]], 'MSG');
            } else {
                $error = $userAlert->getError();
                !$error && $error = '编辑失败';
                response([$error], 'MSG');
            }
        } else {
            $uaId = $this->request->getGet('ua_id', 'intval');

            $userAlert = (new userAlert())->find($uaId);
            $userAlert['main_img'] = $this->_activityThumbImg($userAlert['main_img']);
            $userAlert['m_main_img'] = $this->_activityThumbImg($userAlert['m_main_img']);

            self::$view->setVar('userAlert', $userAlert);
            self::$view->render('useralert_edit');
        }
    }

    public function bindUser()
    {
        if (!IS_AJAX) {
            response(['status' => 0]);
        }

        $userId = $this->request->getPost('user_id', 'intval');
        $uaId = $this->request->getPost('ua_id', 'intval');
        $userAlert = new userAlert();

        // 清除之前的绑定
        $data = $userAlert
            ->field('ua_id,user_tree')
            ->where("FIND_IN_SET({$userId},user_tree)")
            ->find();
        if ($data) {
            $user_tree = explode(',', $data['user_tree']);
            $key = array_search($userId, $user_tree);
            if ($key !== false) unset($user_tree[$key]);
            $userAlert->where(['ua_id' => $data['ua_id']])->update(['user_tree' => implode(',', $user_tree)]);

            $userAlert->flushRedisCache([$userId, 0]);
        }

        // 当ua_id大于0时才存在新的绑定,否则为清除绑定.
        if ($uaId > 0) {
            // 开始新的绑定
            $user_tree = $userAlert->where(['ua_id' => $uaId])->getField('user_tree');

            if ($user_tree) {
                $user_tree = explode(',', $user_tree);
                $user_tree[] = $userId;
                $user_tree = implode(',', $user_tree);
            } else {
                $user_tree = $userId;
            }
            if (!$userAlert->where(['ua_id' => $uaId])->update(['user_tree' => $user_tree])) {
                response(['status' => 0, 'info' => '绑定失败']);
            }
        }

        $this->_clearCache();
        response(['status' => 1, 'info' => '绑定成功']);
    }

    public function changeType()
    {
        if (!IS_AJAX) {
            response(['status' => 0]);
        }

        $type = $this->request->getPost('type', 'trim');
        $uaId = $this->request->getPost('ua_id', 'intval');

        $userAlert = new userAlert();
        if ($userAlert->where(['ua_id' => $uaId])->update(['type' => $type])) {
            $this->_clearCache();
            $userIdList = $userAlert->getUserTreeIdList($uaId);
            $userAlert->flushRedisCache(array_merge($userIdList, [0]));
            response(['status' => 1, 'info' => '更新成功']);
        }
        response(['status' => 0]);
    }

    public function delAlert()
    {
        if (!IS_AJAX) {
            response(['status' => 0]);
        }

        $uaId = $this->request->getPost('ua_id', 'intval');

        $userAlert = new userAlert();
        $data = $userAlert->find($uaId);

        if ($userAlert->where(['ua_id' => $uaId])->delete()) {
            $this->_clearCache();
            $data['main_img'] && unlink($this->_activityThumbImg($data['main_img']));
            $data['m_main_img'] && unlink($this->_activityThumbImg($data['m_main_img']));

            $userTree = explode(',', $data['user_tree']);
            $userAlert->flushRedisCache(array_merge($userTree, [0]));
            response(['status' => 1, 'info' => '删除成功']);
        }
        response(['status' => 0]);
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

    private function _clearCache(){
        exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
        exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
        @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
    }
}