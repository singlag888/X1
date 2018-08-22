<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

/**
 * 控制器：文章管理
 */
class articleController extends sscAdminController
{
    //方法概览
    public $titles = array(
        'articleList' => '文章管理',
        'addArticle' => '添加文章',
        'editArticle' => '修改文章',
        'deleteArticle' => '删除文章',
        'topArticle' => '文章置顶',

        'categoryList' => '文章分类列表',
        'addCategory' => '添加分类',
        'editCategory' => '修改分类',
        'deleteCategory' => '删除分类',

    );

    public function init()
    {

        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    public function articleList()
    {
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $articles = new baseModel('articles');
        $articlesNumber = $articles->where(['status' => 1])->count();

        /******************** snow  修改获取正确页码值******************************/
        $curPage  = $this->request->getGet('curPage', 'intval', 1);  //>>snow 获取页码值
        //>>获取正确页码值
        $startPos = getStartOffset($curPage, $articlesNumber);
        /******************** snow  修改获取正确页码值******************************/
        $articleList = $articles
            ->alias('a')
            ->field('a.*,ac.name AS category_name')
            ->join(' __ARTICLE_CATEGORIES__ AS ac ON a.category_id = ac.category_id ','LEFT')
            ->where([
                'a.status' => 1
            ])
            ->order('a.create_time DESC')
            ->limit($startPos, DEFAULT_PER_PAGE)
            ->select();


        self::$view->setVar('articles', $articleList);
        self::$view->setVar('articlesNumber', $articlesNumber);
        self::$view->setVar('pageList', getPageList($articlesNumber, DEFAULT_PER_PAGE));
        self::$view->setVar('canEdit', adminGroups::verifyPriv(array(CONTROLLER, 'editArticle')));
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteArticle')));
        self::$view->setVar('actionLinks', array(1 => array('title' => '文章分类管理', 'url' => url('article', 'categoryList')), 0 => array('title' => '添加文章', 'url' => url('article', 'addArticle')),));
        self::$view->render('article_articlelist');
    }

    public function topArticle()
    {
        $locations = array(0 => array('title' => '返回文章列表', 'url' => url('article', 'articleList')));
        $article_id = $this->request->getGet('article_id', 'intval');
        $is_stick = $this->request->getGet('is_stick', 'intval');
        $is_stick = empty($is_stick) ? 0 : 1;

        if (empty($article_id)) {
            showMsg("参数无效", 1, $locations);
        }

        $data = array(
            'is_stick' => $is_stick,
        );
        //删除cache文件
        exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
        exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
        @exec('nohup sh  ' . CLEAR_CACHE_DIR . 'clear_cache.sh &');
        if (!articles::updateItem($article_id, $data)) {
            showMsg("没有数据被更新！", 1, $locations);
        }

        /************************** 清除文章相应缓存 start*********************************************/
        $this->_deleteArticleCache($article_id);
        /************************** 清除文章相应缓存 end  *********************************************/
        if ($is_stick == 1) {
            showMsg("置顶成功", 0, $locations);
        } else {
            showMsg("解除置顶成功", 0, $locations);
        }
    }

    /**
     * snow 添加删除文章缓存
     * @param $article_id  string 文章id
     * @return bool
     */
    private function _deleteArticleCache($article_id)
    {
        $GLOBALS['mc']->delete('articles', $article_id);//>>登录状态

        //>>删除redis 缓存 针对使用帮助分类下文章
        $articel_id_key = 'articels_article_id_' . $article_id;
        $articelsListKey = 'articels_' . md5('使用帮助');
        $GLOBALS['redis']->del($articel_id_key);
        $GLOBALS['redis']->del($articelsListKey);
    }


    public function addArticle()
    {
        $locations = array(0 => array('title' => '返回文章列表', 'url' => url('article', 'articleList')));
        //新增数据
        if ($this->request->getPost('submitBtn', 'trim')) {
            if (!$title = $this->request->getPost('title', 'trim', '', false)) {
                showMsg("请输入标题");
            }
            if (($category_id = $this->request->getPost('category_id', 'intval')) <= 0) {
                showMsg("请选择文章分类");
            }
            if (($status = $this->request->getPost('status', 'intval')) == -1) {
                showMsg("请选择状态");
            }
            if (!$content = $this->request->getPost('content', 'trim', '', 0)) {
                showMsg("请输入内容");
            }
            $data = array(
                'title' => $title,
                'category_id' => $category_id,
                'content' => $content,
                'create_time' => date('Y-m-d H:i:s'),
                'status' => $status,
            );

            /******************************* snow  因为前台无法读取到后台图片路径,上传成功后复制一份到前台 start***********************/
            $this->_addImageToFrontend($content);
            /******************************* snow  因为前台无法读取到后台图片路径,上传成功后复制一份到前台 end ************************/
            if (!articles::addItem($data)) {
                showMsg("添加文章失败!请检查数据输入是否完整。");
            }
            //删除cache文件
            exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
            exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
            @exec('nohup sh  ' . CLEAR_CACHE_DIR . 'clear_cache.sh &');
            showMsg("添加成功", 0, $locations);
        }

//        // 使用 fckeditor
//        require_once PROJECT_PATH ."js/fckeditor/fckeditor.php";
//        $FCKeditor = new FCKeditor( 'FCKeditor1' );
//        $FCKeditor->BasePath   = 'js/fckeditor/';
//        $FCKeditor->Width      = '100%';
//        $FCKeditor->Height     = '420';
//        $FCKeditor->Value      = '';
//        $FCKeditor             = $FCKeditor->CreateHtml();
//        self::$view->setVar('FCKeditor', $FCKeditor);

        //得到文章分类列表
        self::$view->setVar('categories', articles::getCategories());
        self::$view->render('article_addarticle');
    }

    /******************************* snow  因为前台无法读取到后台图片路径,上传成功后复制一份到前台 start***********************/
    /**
     * author  snow
     * 从html 代码中取出图片地址,并复制一分到前台
     * @param $content
     */
    private function _addImageToFrontend($content)
    {

        //>>这里可以直接使用preg_replace 方法替换图片 snow  但是为了使用新方法,就不更改了.

        //>>提取图片地址的正则
        $partern = "/\<img.*?src=['\"](.*?)['\"].*?\>/";
        //>>从content 中提取取图片地址
        preg_match_all($partern,$content,$match);
        //>>判断是否有匹配到
        if (isset($match[1]) && is_array($match[1]) && !empty($match[1]))
        {
            $images = $match[1];
            foreach ($images as $key => $val)
            {
                //>>复制相应图片到前台
                if (strrpos($val,'/images_fh/upload/ckeditor') === 0)
                {
                    $path = pathinfo($val);
                    $val = urldecode($val);
                    $pa_url = $path['dirname'];
                    $path_url = ROOT_PATH . "ssc" . $pa_url;
                    $mobile_path_url = ROOT_PATH . "sscmobile" . $pa_url;
                    //>>检查路径是否存在
                    if ( !file_exists($path_url))
                    {
                        //>>创建路径
                        mkdir($path_url, 0777, true);
                    }

                    if ( !file_exists($mobile_path_url))
                    {
                        //>>创建路径
                        mkdir($mobile_path_url, 0777, true);
                    }
                    //>>判断图片是否是本地图片
                    $admin_path =  ROOT_PATH . 'sscadmin' . $val;
                    //>>如果图片存在,则复制一分到前台,如果不存在,略过
                    if (file_exists($admin_path))
                    {
                        $frontend   =  ROOT_PATH . 'ssc' . $val;
                        $wap        =  ROOT_PATH . 'sscmobile' . $val;
                        $commfro = 'cp -f ' .$admin_path . ' ' . $frontend;  //>>copy 到前台
                        $commwap = 'cp -f ' .$admin_path . ' ' . $wap;       //>>copy 到wap
                        if ( !copy($admin_path, $frontend))
                        {
                            //>>如果copy失败 linux 命令copy copy 命令失败机会更小.
                            exec($commfro);
                        }
                        //>>copy 到 手机端
                        if ( !copy($admin_path, $wap))
                        {
                            //>>如果copy失败 linux 命令copy copy 命令失败机会更小.
                            exec($commwap);
                        }
                    }

                }
            }
        }

    }

    /**
     * author snow 删除p标签开头的nbsp
     * @param $content string  html 内容
     * @return mixed  返回替换后的内容
     */
    private function _replaceStrSpace($content)
    {
        //>>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;在
        //>>提取图片地址的正则
        $partern = "/\<p\>(&nbsp;\s*)*(.*?)\<\/p\>/";
        //>>从content 中提取取图片地址

        return preg_replace($partern,'<p>$2</p>',$content);
    }

    /******************************* snow  因为前台无法读取到后台图片路径,上传成功后复制一份到前台 end ************/
    //修改文章
    public function editArticle()
    {
        $locations = array(0 => array('title' => '返回文章列表', 'url' => url('article', 'articleList')));

        //修改数据
        if ($this->request->getPost('submitBtn', 'trim')) {
            if (!$title = $this->request->getPost('title', 'trim', '', false)) {
                showMsg("请输入标题");
            }
            if (($category_id = $this->request->getPost('category_id', 'intval')) <= 0) {
                showMsg("请选择文章分类");
            }
            if (($status = $this->request->getPost('status', 'intval')) == -1) {
                showMsg("请选择状态");
            }
            if (!$content = $this->request->getPost('content', 'trim', '', 0)) {
                showMsg("请输入内容");
            }
            if (!$article_id = $this->request->getPost('article_id', 'intval')) {
                showMsg("数据错误，没有id号");
            }
//            if (!$expire_time = $this->request->getPost('expire_time', 'trim')) {
//                showMsg("请输入到期时间");
//            }
/******************************* snow  因为前台无法读取到后台图片路径,上传成功后复制一份到前台 start***********************/
            $content = $this->_replaceStrSpace($content);  //>>删除p标签开头的空格数量
            $this->_addImageToFrontend($content);
/******************************* snow  因为前台无法读取到后台图片路径,上传成功后复制一份到前台 end ************************/
            if ($status == 0) {
                if (!articles::deleteItem($article_id, true)) {
                    showMsg('删除失败', 1, $locations);
                } else {

                    /************************** 清除文章相应缓存 start*********************************************/
                    $this->_deleteArticleCache($article_id);
                    /************************** 清除文章相应缓存 end  *********************************************/
                    showMsg('删除成功', 0, $locations);
                };
            }

            $data = array(
                'title' => $title,
                'category_id' => $category_id,
                'content' => $content,
                'status' => $status
            );
            //删除cache文件
            exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
            exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
            @exec('nohup sh  ' . CLEAR_CACHE_DIR . 'clear_cache.sh &');
            if (!articles::updateItem($article_id, $data)) {
                showMsg("没有数据被更新", 1, $locations);
            }

            /************************** 清除文章相应缓存 start*********************************************/
            $this->_deleteArticleCache($article_id);
            /************************** 清除文章相应缓存 end  *********************************************/
            showMsg("更新成功", 0, $locations);
        }

        if (!$article_id = $this->request->getGet('article_id', 'intval')) {
            showMsg("invalid args");
        }
        if (!$article = articles::getItem($article_id, NULL)) {
            showMsg("该文章不存在");
        }

//        // 使用 fckeditor
//        require_once PROJECT_PATH ."js/fckeditor/fckeditor.php";
//        $FCKeditor = new FCKeditor( 'FCKeditor1' );
//        $FCKeditor->BasePath   = 'js/fckeditor/';
//        $FCKeditor->Width      = '100%';
//        $FCKeditor->Height     = '420';
//        $FCKeditor->Value      = $article['content'];
//        $FCKeditor             = $FCKeditor->CreateHtml();
//        self::$view->setVar('FCKeditor', $FCKeditor);

        //得到文章分类列表
        self::$view->setVar('categories', articles::getCategories());
        self::$view->setVar('article', $article);
        self::$view->render('article_addarticle');
    }

    public function deleteArticle()
    {
        $locations = array(0 => array('title' => '返回文章列表', 'url' => url('article', 'articleList')));
        if (!$article_id = $this->request->getGet('article_id', 'intval')) {
            showMsg("参数无效");
        }
        //删除cache文件
        exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
        exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
        @exec('nohup sh  ' . CLEAR_CACHE_DIR . 'clear_cache.sh &');
        if (!articles::deleteItem($article_id, true)) {
            showMsg("删除数据失败");
        }

        /************************** 清除文章相应缓存 start*********************************************/
        $this->_deleteArticleCache($article_id);
        /************************** 清除文章相应缓存 end  *********************************************/
        showMsg("删除数据成功", 1, $locations);
    }

    public function deleteCategory()
    {
        $locations = array(0 => array('title' => '返回文章列表', 'url' => url('article', 'articleList')));
        if (!$article_id = $this->request->getGet('category_id', 'intval')) {
            showMsg("参数无效");
        }
        //删除cache文件
        exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
        exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
        @exec('nohup sh  ' . CLEAR_CACHE_DIR . 'clear_cache.sh &');
        if (!articles::deleteCategory($article_id)) {
            showMsg("删除数据失败");
        }

        showMsg("删除数据成功", 1, $locations);
    }


    public function categoryList()
    {
        //得到文章分类列表
        self::$view->setVar('categories', articles::getCategories());

        self::$view->setVar('canEdit', adminGroups::verifyPriv(array(CONTROLLER, 'editCategory')));
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteCategory')));
        self::$view->setVar('actionLinks', array(0 => array('title' => '添加分类', 'url' => url('article', 'addCategory'))));
        self::$view->render('article_categorylist');
    }

    public function addCategory()
    {
        $locations = array(0 => array('title' => '返回分类列表', 'url' => url('article', 'categoryList')));
        //新增数据
        if ($this->request->getPost('submitBtn', 'trim')) {
            if (!$name = $this->request->getPost('name', 'trim')) {
                showMsg("请输入名字");
            }
            $data = array(
                'name' => $name,
                'create_time' => date('Y-m-d H:i:s', time())
            );

            if (!articles::addCategory($data)) {
                showMsg("添加分类失败");
            }

            showMsg("添加成功", 1, $locations);
        }

        self::$view->render('article_addcategory');
    }

    //修改文章
    public function editCategory()
    {
        $locations = array(0 => array('title' => '返回文章分类列表', 'url' => url('article', 'categoryList')));

        //修改数据
        if ($this->request->getPost('submitBtn', 'trim')) {

            if (!$name = $this->request->getPost('name', 'trim')) {
                showMsg("请输入名字");
            }
            if (!$category_id = $this->request->getPost('category_id', 'intval')) {
                showMsg("数据错误，没有id号");
            }
            $data = array(
                'name' => $name,
            );

            if (!articles::updateCategory($category_id, $data)) {
                showMsg("没有数据被更新");
            }
            showMsg("更新成功", 1, $locations);
        }

        if (!$category_id = $this->request->getGet('category_id', 'intval')) {
            showMsg("参数无效");
        }
        if (!$category = articles::getCategory($category_id)) {
            showMsg("该文章不存在");
        }


        self::$view->setVar('category', $category);
        self::$view->render('article_addcategory');
    }
}

?>