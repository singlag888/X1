<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：文章管理
 */
class helpController extends sscappController
{
    //方法概览 测试提交
    public $titles = array(
        'deposit'       => '充值问题',
        'withdraw'      => '提现问题',
        'account'       => '账号相关',
        'lottery'       => '如何购彩',
        'method'        => '玩法介绍',
        'chatInterface' => '聊天室接口',
        'platformact'   => '平台活动',
        'latestNew'     => '最新公告',
        'genroom'       => '聊天室进入页面',
        'safe'          => '安全相关',
        'buy'           => '购彩相关',
        'statement'     => '责任与声明',
        'download'      => '客户端下载',
        'protocol'      => '用户协议',
        'card'          => '银行卡相关',
        'error403'      => 'error',
        'result'        => '开奖结果',
        'useHelp'       => '使用帮助',
    );

    public function getService()
    {
        $url = config::getConfig('service_url', '');
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['ser_addr' => $url]);
    }

    public function feedback()
    {
        list($user_id, $user) = $this->chkUser(1);
        $type = $this->request->getPost('type', 'intval', 0);
        $content = $this->request->getPost('content', 'string', '');
        if ($type < 0 || $type > 3 || empty($content)) $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
        $data = [
            'user_id' => $user_id,
            'type' => $type,
            'content' => $content,
            'create_time' => time(),
        ];
        if (!feedbacks::addItem($data)) $this->showMsg(7028, '添加数据失败,请稍后再试');
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
    }

    public function aboutme()
    {
        $html=$this->help(7);
//        if (self::$view === NULL) self::$view = new view('user','sscapp');
//        self::$view->render('aboutme');
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['html' => $html]);
    }

    public function doc()
    {
        $type = $this->request->getGet('type', 'intval', 1);
        if ($type > 6 || $type < 1) $type = 1;
        $html=$this->help($type);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['html' => $html]);
    }
    private function help($id)
    {
        if ($id > 7 || $id < 1) $type = 1;
        $html=$this->cutRedisDatabase(function()use($id){
            return $GLOBALS['redis']->hget('appset', 'help_'.$id);
        });
        if(empty($html)){
            $help = M('appHelp');
            $helpInfo = $help->where(['id'=>$id])->find();
            if(empty($helpInfo['content'])){
                if($id!=7){
                    switch ($id) {
                        case 1: //安全相关
                            $name = "anquan";
                            break;
                        case 2: //充值
                            $name = "chongzhi";
                            break;
                        case 3: //购彩
                            $name = "goucai";
                            break;
                        case 4: //提现
                            $name = "tixian";
                            break;
                        case 5: //用户协议
                            $name = "xieyi";
                            break;
                        case 6: //责任声明
                            $name = "zeren";
                            break;
                        default:
                            $name = "anquan";
                            break;
                    }
                    $path = dirname(__FILE__)  . "view/help/$name.phtml";
                }else{
                    $path = dirname(__FILE__)  . "view/user/aboutme.phtml";
                }
                if (!is_readable($path)) $this->showMsg(7027, '页面不存在');
                $html = file_get_contents($path);
                $content=htmlspecialchars($html);
                $help->where(['id'=>$id])->update(['content'=>$content]);
            }else{
                $content=$helpInfo['content'];
                $html=htmlspecialchars_decode($helpInfo['content'], ENT_HTML5);
            }
            $this->cutRedisDatabase(function()use($id,$content){
                $GLOBALS['redis']->hset('appset', 'help_'.$id,$content);
            });
        }else{
            $html=htmlspecialchars_decode($html, ENT_HTML5);
        }
        return $html;
    }

    /**
     * author snow 使用帮助接口,从数据库文章列表获取数据
     */
    public function useHelp()
    {

        /**
         * 定义单个文章缓存redis键名以及文章键名
         *
         * articels_article_id_ 后面连接上具体的id 号方便,更新缓存
         * articels_ 后面连接上  "使用帮助"  的md5值.作为键名
         */
        $article_id = $this->request->getGet('article_id', 'intval', 0);

        if ($article_id > 0) {

            //>>使用缓存
            $articel_id_key = 'articels_article_id_' . $article_id;
            $row = redisGet($articel_id_key,function() use($article_id){
                //>>传入了文章id 获取文章详情
                $arSql = 'SELECT article_id,title,content FROM articles WHERE article_id = ' . $article_id;
               return $GLOBALS['db']->getRow($arSql);
            }, CACHE_EXPIRE_LONG);

            if (!empty($row)) {
                //>>有数据
                $row['content'] = htmlspecialchars_decode($row['content']);
                $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $row);
            } else {
                $this->showMsg(7027, '页面不存在');
            }

        } else {
            //>>写死,文件类型只能为 "使用帮助"
            $articelsListKey = 'articels_' . md5('使用帮助');

            $rows = redisGet($articelsListKey, function(){
                $sql =<<<SQL
SELECT a.article_id,a.title FROM articles AS a,article_categories AS ac  
WHERE a.category_id = ac.category_id 
AND ac.name = '使用帮助'
AND `status` = 1
SQL;
                return $GLOBALS['db']->getAll($sql, [], 'article_id');
            }, CACHE_EXPIRE_LONG);


            if (!empty($rows)) {
                //>>有数据
                $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $rows);
            } else {
                $this->showMsg(7027, '没有相关数据');
            }

        }



    }
    /**
     * 初始化
     */
    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    /**
     * author snow
     * 今日热点
     */
    public function latestNew()
    {
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $this->_getLatestNew());
    }

    /**
     * author snow
     * 获取今日热点列表或单个新闻
     * @return array
     */
    private function _getLatestNew()
    {
        $GLOBALS['nav'] = 'news';
        $amount = $this->request->getGet('limit','intval',20);//每页10条
//        $startLimit = ($this->request->getGet('curPage', 'intval', 1) - 1) * $amount;
        //详情
        if ($article_id = $this->request->getGet('article_id', 'intval', 0)) {
//            if (($article = $GLOBALS['mc']->get(__CLASS__, __FUNCTION__.$article_id)) == false)
            {
                $article = articles::getItem($article_id);

//                $GLOBALS['mc']->set(__CLASS__, __FUNCTION__.$article_id, $article, CACHE_EXPIRE_LONG);
                if (empty($article)) {
                    return [];
                }

                $article['content'] = $this->_replaceHtmlImageUrl($article['content']);

            }
            return $article;
        }


        //列表/查询所有文章/todo  这里应该只查询新闻资讯
        $fields = [
            'article_id',
            'category_id',
            'is_stick',
            'title',
            'create_time',
            'status',
            'ts',
        ];
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        $articlesNum = articles::getItemsNumber(0, 1);//>>获取总条数.

        $maxPage = ceil($articlesNum / $amount);
        if ($curPage > $maxPage) {
            return [];
        }
        $offset = getStartOffset($curPage, $articlesNum, $amount);
        $article_list = articles::getItemsFields(-1, 1, $offset, $amount, $fields);
        return $article_list;
    }

    /**
     * author snow
     * 替换html 代码中的image 相对路径为绝对路径
     * @param $html
     * @return mixed1
     */
    private function _replaceHtmlImageUrl($html)
    {
        //>>搜索图片的正则
        $pattern = "/\<img.*?src=['\"](.*?)['\"].*?\>/";
        $domain = self::$mobileDomain;  //>>获取主机地址 wap 服务器.
        //>>使用方法进行替换
        return preg_replace_callback($pattern, function ($matches) use ($domain) {
            //>>处理每一个搜索到选项
            $pat = "/(\<img.*?src=['\"])/";
            $httpHead = "/(\<img.*?src=['\"]http)/";
            $mat = array_shift($matches);
            if (preg_match($httpHead, $mat)) {
                return $mat;
            }
            return preg_replace($pat, "$1" . $domain, $mat);
        }, $html);
    }
}
