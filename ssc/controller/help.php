<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：文章管理
 */
class helpController extends sscController
{
    //方法概览 测试提交
    public $titles = array(
        'deposit' => '充值问题',
        'withdraw' => '提现问题',
        'account' => '账号相关',
        'lottery' => '如何购彩',
        'method' => '玩法介绍',
        'chatInterface' => '聊天室接口',
        'platformact' => '平台活动',
        'latestnew' => '最新公告',
        'genroom' => '聊天室进入页面',
        'safe' => '安全相关',
        'buy' => '购彩相关',
        'statement' => '责任与声明',
        'download' => '客户端下载',
        'protocol' => '用户协议',
        'card' => '银行卡相关',
        'error403' => 'error',
        'chart' => '游戏走势',
        'result' => '开奖结果',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
        $this->getUser();
        $this->getNotics();
        $this->getNotReadMsgNum();
    }

    public function deposit()
    {
/*        $articles = articles::getItems($this->titles[__FUNCTION__], 1);

        self::$view->setVar('articles', $articles);*/
        self::$view->render('help_deposit');
    }

    public function withdraw()
    {
        $articles = articles::getItems($this->titles[__FUNCTION__], 1);

        self::$view->setVar('articles', $articles);
        self::$view->render('help_withdraw');
    }

    public function account()
    {
        $articles = articles::getItems($this->titles[__FUNCTION__], 1);

        self::$view->setVar('articles', $articles);
        self::$view->render('help_deposit');
    }

    public function lottery()
    {
        $articles = articles::getItems($this->titles[__FUNCTION__], 1);

        self::$view->setVar('articles', $articles);
        self::$view->render('help_deposit');
    }

    public function method()
    {
/*        $articles = articles::getItems($this->titles[__FUNCTION__], 1);

        self::$view->setVar('articles', $articles);*/
        self::$view->render('help_method');
    }

    public function safe()
    {
        $GLOBALS['nav'] = 'help';
        self::$view->render('help_safe');
    }

    public function chart()
    {
        $GLOBALS['nav'] = 'chart';
        self::$view->render('help_chart');
    }

    public function buy()
    {
        self::$view->render('help_buy');
    }

    public function statement()
    {
        self::$view->render('help_statement');
    }

    public function protocol()
    {
        self::$view->render('help_protocol');
    }

    public function result()
    {
        //$result = lottery::getItems(0);
        $result = lottery::getItemsNew(['lottery_id']);
        $keys=array_keys($result);
        self::$view->setVar('keys',json_encode($keys));
        $GLOBALS['nav'] = 'result';
        self::$view->render('help_result');
    }

    public function card()
    {
        self::$view->render('help_card');
    }

    public function download()
    {
        $GLOBALS['nav'] = 'mobile';

        self::$view->render('help_download');
    }

    public function chatInterface()
    {
        if(!$self = users::getItem($GLOBALS['SESSION']['user_id'])){
            throw new exception2('用户非法');
        }
        $json = array();
        $json['parent'] = array('user_id' => 0, 'username' => '');
        $json['chilren'] = array();
        $parent = users::getItem($self['parent_id']);

        // $chilren = users::getUserTree($GLOBALS['SESSION']['user_id'], false, 0, 8);
        $chilren = users::getUserTreeField([
            'field' => ['user_id', 'username'],
            'parent_id' => $GLOBALS['SESSION']['user_id'],
            'includeSelf' => false,
            'status' => 8
        ]);

        $json['self'] = array('user_id' => $self['user_id'], 'username' => $self['username']);
        if($parent){
            $json['parent'] = array('user_id' => $parent['user_id'], 'username' => $parent['username']);
        }
        if($chilren){
            foreach ($chilren as $k => $v) {
                $json['chilren'][] = array('user_id' => $v['user_id'], 'username' => $v['username']);
            }
        }

        echo json_encode($json);
    }

    public function platformact()
    {
        $GLOBALS['nav'] = 'promo';
        $this->getActivities();
        self::$view->render('help_platformact');
    }

    /**
     * 最新公告  公告列表
     */
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
            self::$view->render('help_newdetail');
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
            self::$view->render('help_latestnew');
        }
    }



    private $encryptKey = 'abcdef12kj054321';
    private $iv = '0123456765321281';
    private $blocksize = 16;


    public function decrypt($data)
    {
        return $this->unpad(mcrypt_decrypt(MCRYPT_RIJNDAEL_128,
            $this->encryptKey,
            hex2bin($data),
            MCRYPT_MODE_CBC, $this->iv), $this->blocksize);
    }

    public function encrypt($data)
    {
        //don't use default php padding which is '\0'
        $pad = $this->blocksize - (strlen($data) % $this->blocksize);
        $data = $data . str_repeat(chr($pad), $pad);
        return bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128,
            $this->encryptKey,
            $data, MCRYPT_MODE_CBC, $this->iv));
    }

    private function unpad($str, $blocksize)
    {
        $len = mb_strlen($str);
        $pad = ord( $str[$len - 1] );
        if ($pad && $pad < $blocksize) {
            $pm = preg_match('/' . chr($pad) . '{' . $pad . '}$/', $str);
            if( $pm ) {
                return mb_substr($str, 0, $len - $pad);
            }
        }
        return $str;
    }

    /**
     * 生成聊天连接
     * author Simon
     * Date: 2016/8/15
     */
    public function genroom()
    {
        if ( !$this->isLogined() ) {
            exit('还没登录');
        }

        $params = json_encode([
            'uid'=> $GLOBALS['SESSION']['user_id'],
            'pid'=> $GLOBALS['SESSION']['parent_id'],
            //'level'=> $GLOBALS['SESSION']['level'],
            //'timestamp'=> time(),
            //    'type'=>'gen',
            'nickname'=> $GLOBALS['SESSION']['username'],
            'phpSessId'=>$GLOBALS['SESSION']->getSessionId()
        ]);
        //c=userMenu&a=menuList
        self::$view->setVar('chatToken', $this->encrypt($params));
        self::$view->setVar('chatCurLevel', $GLOBALS['SESSION']['level']);
       self::$view->setVar('chatHost', 'http://chat.go8w.biz');

        self::$view->render('chat_genroom');
    }

    public function error403(){
        self::$view->render('help_error403');
    }
}
?>