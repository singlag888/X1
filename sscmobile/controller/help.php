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
        'result' => '开奖结果',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
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

    public function safe(){
        self::$view->render('help_safe');
    }

    public function buy(){
        self::$view->render('help_buy');
    }

    public function statement(){
        self::$view->render('help_statement');
    }

    public function protocol(){
        self::$view->render('help_protocol');
    }

    public function card(){
        self::$view->render('help_card');
    }

    public function download(){
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
        // $chilren =  users::getUserTree($GLOBALS['SESSION']['user_id'], false, 0, 8);
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
        $articles = articles::getItems($this->titles[__FUNCTION__], 1);
        $this->getActivities();
        self::$view->setVar('articles', $articles);
        self::$view->render('help_platformact');
    }

    public function latestnew()
    {
        if($notice_id = $this->request->getPost('notice_id', 'intval')){ //ajax
            $notice = notices::getItem($notice_id);
            die(json_encode(['data'=>$notice]));
        }

//        $page = $this->request->getPost('curPage', 'intval'); //ajax

        $notices = notices::getItems(1, 0, 4);

/*        if($page){
            $data = [];
            foreach ($notices as $notice){
                $data[] = ['title'=>$notice['title'], 'content'=>$notice['content'], 'start_time'=>date('Y.m.d', strtotime($notice['start_time'])),'is_stick'=>$notice['is_stick']];
            }
            die(json_encode(['count'=>count($notices), 'data'=>$data]));
        }*/


        self::$view->setVar('notices', $notices);
        self::$view->render('help_latestnew');
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
    public function genroom(){
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

    public function result(){
        self::$view->render('help_result');
    }
}
?>