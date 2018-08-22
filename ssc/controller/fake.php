<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：文章管理
 */
class fakeController extends sscController
{
    //方法概览 测试提交
    public $titles = array(
        'lobby' => '彩种大厅',
        'download' => '手机购彩',
        'chart' => '走势',
        'result' => '开奖结果',
        'platformact' => '优惠活动',
        'latestnew' => '行业资讯',
        'safe' => '帮助中心'
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
        $this->getNotics();
        $domain = domains::getItem($_SERVER['HTTP_HOST']);
        $domainType = isset($domain['type']) ? $domain['type'] : 0;
        self::$view->setVar('domainType', $domainType);
    }

    public function lobby()
    {
        $GLOBALS['nav'] = 'game';
        self::$view->render('fake_lobby');
    }

    public function safe()
    {
        $GLOBALS['nav'] = 'help';
        self::$view->render('fake_safe');
    }

    public function chart()
    {
        $GLOBALS['nav'] = 'chart';
        self::$view->render('fake_chart');
    }

    public function result()
    {
        // $result = lottery::getItems(0);
        $result = lottery::getItemsNew(['lottery_id']);
        $keys=array_keys($result);
        self::$view->setVar('keys',json_encode($keys));
        $GLOBALS['nav'] = 'result';
        self::$view->render('fake_result');
    }

    public function download()
    {
        $GLOBALS['nav'] = 'mobile';
        self::$view->render('fake_download');
    }


    public function platformact()
    {
        $GLOBALS['nav'] = 'promo';
        $this->getActivities();
        self::$view->render('fake_platformact',true);
    }

    public function latestnew()
    {
        $article_id = $this->request->getGet('article_id', 'intval', 0);
        $GLOBALS['nav'] = 'news';
        if ($article_id) {
            if (($article = $GLOBALS['mc']->get('articles', $article_id)) == false) {
                $article = articles::getItem($article_id);
                //>>snow 在其中加入摘要内容 .如果摘要内容有图片.去掉
                //>>先处理掉内容中的img 标签
                $patern = "/\<img.*?src=['\"](.*?)['\"].*?\>/";
                $patern_space = ["/\s*/", "/(\r\n)+/"];
                $tmpStr = preg_replace($patern, '', $article['content']);
                $tmpStr = preg_replace($patern_space, ['', "\r\n"], $tmpStr);
                $article['summary'] = mb_substr($tmpStr, 0, 120);
                $GLOBALS['mc']->set('articles', $article_id, $article, CACHE_EXPIRE_LONG);
            }

            self::$view->setVar('article', $article);
            self::$view->render('fake_newdetail');
        } else {
            $curPage = $this->request->getGet('curPage', 'intval', 1);
            $listRows = 10;
            $articlesNum = articles::getItemsNumber(0, 1);
            /***************** snow  获取正确的分页开始****************************/
            $offset = getStartOffset($curPage, $articlesNum, $listRows);
            /***************** snow  获取正确的分页开始****************************/
            $news = M('articles')
                ->where(['status' => 1])
                ->order('create_time DESC')
                ->limit($offset, $listRows)
                ->select();

            self::$view->setVar('news', $news);
            self::$view->setVar('pageList', getPageList($articlesNum, $listRows));
            self::$view->render('fake_latestnew');
        }
    }
}
