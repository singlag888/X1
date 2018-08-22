<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：公告管理
 */
class noticeController extends sscAdminController
{

    public $titles = array(
        'noticeList' => '公告列表',
        'addNotice' => '增加公告',
        'editNotice' => '修改公告',
        'enableNotice' => '启用公告',
        'disableNotice' => '禁用公告',
        'deleteNotice' => '删除公告',
        'topNotice' => '置顶公告',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //公告列表
    public function noticeList()
    {
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $noticesNumber = notices::getItemsNumber(NULL);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $noticesNumber);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $notices = notices::getItems(1, -1, -1, $startPos, DEFAULT_PER_PAGE);

        self::$view->setVar('notices', $notices);
        self::$view->setVar('noticesNumber', $noticesNumber);
        self::$view->setVar('pageList', getPageList($noticesNumber, DEFAULT_PER_PAGE));
        self::$view->setVar('canEdit', adminGroups::verifyPriv(array(CONTROLLER, 'editNotice')));
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteNotice')));
        self::$view->setVar('actionLinks', array(0 => array('title' => '增加公告', 'url' => url('notice', 'addNotice'))));
        self::$view->render('notice_noticelist');
    }

    public function addNotice()
    {
        $locations = array(0 => array('title' => '返回公告列表', 'url' => url('notice', 'noticeList')));
        //新增数据
        if ($this->request->getPost('submit', 'trim')) {
            if (!$title = $this->request->getPost('title', 'trim', '', false)) {
                showMsg("请输入标题");
            }
            if (!$content = $this->request->getPost('content', 'trim', '', false)) {
                showMsg("请输入内容");
            }
            if (!$expire_time = $this->request->getPost('expire_time', 'trim')) {
                showMsg("请输入到期时间");
            }

            $start_time = $this->request->getPost('start_time', 'trim');
            if (strtotime($expire_time) <= strtotime($start_time)) {
                showMsg("公告开始时间不能大于过期时间");
            }

            $type = $this->request->getPost('type', 'intval', '1|2|3');
            $data = array(
                'title' => $title,
                'type' => $type,
                'content' => $content,
                'create_time' => date('Y-m-d H:i:s'),
                'expire_time' => $expire_time,
                'status' => 1,
                'start_time' => $start_time,
            );

            if ($type == 3) {//如果是手机端的公告类型
                if (!$save_path = $this->request->getPost('save_path', 'trim')) {
                    showMsg("保存路径不能为空");
                }

                $obj = new upload(array('savePath' => 'images/' . $save_path, 'maxSize' => 307200, 'allowedExts' => 'jpg|jpeg|png|bmp|gif', 'allowedTypes' => 'image'));
                $ext = substr($_FILES['app_img']['name'], strrpos($_FILES['app_img']['name'], '.'));
                $imgPath = $obj->save($_FILES['app_img'], date('Ymdhis') . rand(1000, 9999) . $ext);

                if (!$imgPath) {
                    showMsg("图片上传失败");
                }
                $data['link'] = $this->request->getPost('link', 'trim', '', 1);
                $data['img_path'] = $imgPath;
            }

            if (!notices::addItem($data)) {
                showMsg("添加公告失败!请检查数据输入是否完整。");
            }
            /***************** snow 删除redis cache ********************/
            $this->_deleteRdisCache();
            /***************** snow 删除redis cache ********************/
            //删除cache文件
            exec('rm -f '.ROOT_PATH.'ssc/cache/*');
            exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
            @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
            showMsg("添加成功！", 0, $locations);
        }

//        // 使用 fckeditor
//        require_once PROJECT_PATH . "js/fckeditor/fckeditor.php";
//        $FCKeditor = new FCKeditor('FCKeditor1');
//        $FCKeditor->BasePath = 'js/fckeditor/';
//        $FCKeditor->Width = '490';
//        $FCKeditor->Height = '420';
//        $FCKeditor->Value = '';
//        $FCKeditor = $FCKeditor->CreateHtml();
//        self::$view->setVar('FCKeditor', $FCKeditor);
        self::$view->setVar('save_path', 'mobile/');
        self::$view->render('notice_addnotice');
    }

    public function editNotice()
    {
        $locations = array(0 => array('title' => '返回公告列表', 'url' => url('notice', 'noticeList')));

        //修改数据
        if ($this->request->getPost('submit', 'trim')) {
            if (!$title = $this->request->getPost('title', 'trim', '', false)) {
                showMsg("请输入标题");
            }
            if (!$content = $this->request->getPost('content', 'trim', '', false)) {
                showMsg("请输入内容");
            }
            if (!$notice_id = $this->request->getPost('notice_id', 'intval')) {
                showMsg("数据错误，没有id号");
            }
            if (!$expire_time = $this->request->getPost('expire_time', 'trim')) {
                showMsg("请输入到期时间");
            }

            $start_time = $this->request->getPost('start_time', 'trim');
            if (strtotime($expire_time) <= strtotime($start_time)) {
                showMsg("公告开始时间不能大于过期时间");
            }

            $type = $this->request->getPost('type', 'intval', '1|2|3');

            $data = array(
                'title' => $title,
                'type' => $type,
                'content' => $content,
                'expire_time' => $expire_time,
                'start_time' => $start_time,
            );
            if ($type == 3) {//如果是手机端公告
                if (!$save_path = $this->request->getPost('save_path', 'trim')) {
                    showMsg("保存路径不能为空");
                }

                $save_path = 'images/' . $save_path;

                $obj = new upload(array('savePath' => $save_path, 'maxSize' => 307200, 'allowedExts' => 'jpg|jpeg|png|bmp|gif', 'allowedTypes' => 'image'));
                $ext = substr($_FILES['app_img']['name'], strrpos($_FILES['app_img']['name'], '.'));
                $imgPath = $obj->save($_FILES['app_img'], date('Ymdhis') . rand(1000, 9999) . $ext);

                $oldPath = $this->request->getPost('old_img_path', 'trim');
                $oldSavePath = dirname($oldPath) . '/';

                if ($save_path != $oldSavePath && $imgPath == '') {
                    showMsg("保存路劲改变，必须上传图片");
                }
                $path = $imgPath != '' ? $imgPath : $oldPath;

                $data['link'] = $this->request->getPost('link', 'trim', '', 1);
                $data['img_path'] = $path;
            }
            //删除cache文件
            exec('rm -f '.ROOT_PATH.'ssc/cache/*');
            exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
            @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
            if (!notices::updateItem($notice_id, $data)) {

                showMsg("没有数据被更新！", 1, $locations);
            }
            /***************** snow 删除redis cache ********************/
            $this->_deleteRdisCache();
            /***************** snow 删除redis cache ********************/
            showMsg("更新成功", 0, $locations);
        }

        if (!$notice_id = $this->request->getGet('notice_id', 'intval')) {
            showMsg("参数无效");
        }
        //JYZ-466 公告修改：后台始终可以编辑 ；置顶但过期的公告不能在前台显示；
        if (!$notice = notices::getItem($notice_id, -1)) {
            showMsg("该公告不存在");
        }

//        // 使用 fckeditor
//        require_once PROJECT_PATH . "js/fckeditor/fckeditor.php";
//        $FCKeditor = new FCKeditor('FCKeditor1');
//        $FCKeditor->BasePath = 'js/fckeditor/';
//        $FCKeditor->Width = '490';
//        $FCKeditor->Height = '420';
//        $FCKeditor->Value = $notice['content'];
//        $FCKeditor = $FCKeditor->CreateHtml();
//        self::$view->setVar('FCKeditor', $FCKeditor);

        if ($notice['img_path'] != '') {
            $save_path = str_replace('images/', '', dirname($notice['img_path']) . '/');
        }
        else {
            $save_path = 'upload/';
        }

        self::$view->setVar('save_path', $save_path);
        self::$view->setVar('notice', $notice);
        self::$view->render('notice_addnotice');
    }

    public function enableNotice()
    {
        $locations = array(0 => array('title' => '返回公告列表', 'url' => url('notice', 'noticeList')));
        if (!$notice_id = $this->request->getGet('notice_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }

        $data = array(
            'status' => 1,
        );
        //删除cache文件
        exec('rm -f '.ROOT_PATH.'ssc/cache/*');
        exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
        @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
        if (!notices::updateItem($notice_id, $data)) {
            showMsg("没有数据被更新", 0, $locations);
        }

        /***************** snow 删除redis cache ********************/
        $this->_deleteRdisCache();
        /***************** snow 删除redis cache ********************/
        showMsg("更新成功", 0, $locations);
    }

    public function disableNotice()
    {
        $locations = array(0 => array('title' => '返回公告列表', 'url' => url('notice', 'noticeList')));
        if (!$notice_id = $this->request->getGet('notice_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }

        $data = array(
            'status' => 0,
        );
        //删除cache文件
        exec('rm -f '.ROOT_PATH.'ssc/cache/*');
        exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
        @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
        if (!notices::updateItem($notice_id, $data)) {
            showMsg("没有数据被更新", 0, $locations);
        }

        /***************** snow 删除redis cache ********************/
        $this->_deleteRdisCache();
        /***************** snow 删除redis cache ********************/
        showMsg("更新成功", 0, $locations);
    }

    public function deleteNotice()
    {
        $locations = array(0 => array('title' => '返回公告列表', 'url' => url('notice', 'noticeList')));
        if (!$notice_id = $this->request->getGet('notice_id', 'intval')) {
            showMsg("参数无效", 1, $locations);
        }
        //删除cache文件
        exec('rm -f '.ROOT_PATH.'ssc/cache/*');
        exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
        @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
        if (!notices::deleteItem($notice_id)) {
            showMsg("删除数据失败", 0, $locations);
        }

        /***************** snow 删除redis cache ********************/
        $this->_deleteRdisCache();
        /***************** snow 删除redis cache ********************/
        showMsg("删除数据成功", 0, $locations);
    }

    /**
     * @todo 置顶/解除置顶单条公告
     * @author Davy 2015/02/16
     * @param <int> $notice_id      置顶/解除置顶公告的ID
     * @param <int> $is_stick         1 变更为置顶，0 变更为非置顶
     * @return redirect noticeList
     */
    public function topNotice()
    {
        $locations = array(0 => array('title' => '返回公告列表', 'url' => url('notice', 'noticeList')));
        $notice_id = $this->request->getGet('notice_id', 'intval');
        $is_stick = $this->request->getGet('is_stick', 'intval');
        $is_stick = empty($is_stick) ? 0 : 1;

        if (empty($notice_id)) {
            showMsg("参数无效", 1, $locations);
        }

        $data = array(
            'is_stick' => $is_stick,
        );

        if (!notices::updateItem($notice_id, $data)) {
            showMsg("没有数据被更新！", 1, $locations);
        }
        /***************** snow 删除redis cache ********************/
        $this->_deleteRdisCache();
        /***************** snow 删除redis cache ********************/
        //删除cache文件
        exec('rm -f '.ROOT_PATH.'ssc/cache/*');
        exec('rm -f '.ROOT_PATH.'sscmobile/cache/*');
        @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
        if ($is_stick == 1) {
            showMsg("置顶成功", 0, $locations);
        }
        else {
            showMsg("解除置顶成功", 0, $locations);
        }
    }

    /**
     * author snow  删除公告的redis 缓存.
     */
    private function _deleteRdisCache()
    {
        //>>此hash 表为固定值.
        redisDelHashForKey('noticesGetItems');
    }

}

