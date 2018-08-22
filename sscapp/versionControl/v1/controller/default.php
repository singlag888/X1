<?php

use common\model\onlineUser;

/**
 * 控制器：商户管理后台
 * 承接管理员登录等基本后台业务
 */
class defaultController extends sscappController
{
    const ODD_WIN_INTERCEPT = 40;     //热门游戏中奖率拦截值(百分比)
    const BET_PEOPLE_CEIL = 30;     //热门游戏下注人数向上提升(百分比)
    const TOTAL_CEIL = 30;     //热门游戏下注金额向上提升(百分比)
    const WIN_CEIL = 30;     //热门游戏中奖金额向上提升(百分比)
    const SERACHHOT_DAYTIME_BEFORE = 6;      //热门游戏查询几天前开始的,-1:表示全部
    const TOTAL_PRIZE_CEIL = 50;     //累计中奖金额向上提升(百分比)

    const ADD_ONLINE_USER_NUMBER = 2000;   //>>查询在线人数时需要添加的数量
    const ADD_DEPOSITS_OR_WITHDRAW_NUMBER = 200000; //>>查询提款或存款记录时需要添加的数量
    /***************************彩种随机*****************************/
    private $defaultRand=[
        "total" => [10,18],//总投注对应中奖金额倍数
        "odd_win" => [50,58],//中奖率
        "people" => [80,160],//投注人数对应投注金额相除倍数
    ];
    //>>不同位数的上下浮动
    private $rand=[
        '8'=>[300,-100],
        '7'=>[100,-50],
        '6'=>[50,-20],
        '5'=>[2,-1],
        'min'=>[2,0],
    ];
    private $lottery=[
        '15'=>[
            "prize" => 72346872,//中奖金额基数
        ],
        '17'=>[
            "prize" => 11300335,//中奖金额基数
            "total" => [10,18],//总投注对应中奖金额倍数
            "odd_win" => [50,58],//中奖率
            "people" => [80,160],//投注人数对应投注金额相除倍数
        ],
        '1'=>[
            "prize" => 3811506,
        ],
        '26'=>[
            "prize" => 948290,
        ],
        '21'=>[
            "prize" => 3046436,
        ],
        '4'=>[
            "prize" => 89292,
        ],
        '23'=>[
            "prize" => 48524,
        ],
        '9'=>[
            "prize" => 47495,
        ],
        '12'=>[
            "prize" => 40889,
        ],
        '19'=>[
            "prize" => 18789,
        ],
        '7'=>[
            "prize" => 16227,
        ],
        '2'=>[
            "prize" => 8426,
        ],
        '10'=>[
            "prize" => 13493,
        ],
        '6'=>[
            "prize" => 5011,
        ],
        '5'=>[
            "prize" => 4894,
        ],
        '14'=>[
            "prize" => 8826,
        ],
        '22'=>[
            "prize" => 6426,
        ],
        '3'=>[
            "prize" => 66426,
        ],
        '8'=>[
            "prize" => 12426,
        ],
        '11'=>[
            "prize" => 21426,
        ],
        '13'=>[
            "prize" => 4126,
        ],
        '16'=>[
            "prize" => 4134,
        ],
        '18'=>[
            "prize" => 2343,
        ],
        '20'=>[
            "prize" => 1234,
        ],
        '24'=>[
            "prize" => 1874,
        ],
        '25'=>[
            "prize" => 234,
        ],
    ];
    /*=================================*/
    private $total_prize_rand=[
        'num'=>200183222,
        'ceil'=>[399999,999999]
    ];
    /***************************彩种随机 end*****************************/
    //方法概览
    public $titles = array(
        'bannerList' => 'Banner列表',
        'noticeList' => '公告列表',
        'noticeDetail' => '公告详情',
        'activityList' => '优惠活动列表',
        'activityDetail' => '优惠活动详情',
        'getLastVersion' => '获取最新版本号',
        'openInfo' => '获取开奖信息',
        'welcome' => '欢迎页',
        'lobby' => '购彩大厅',
    );

    public function init($init = 0)
    {
        parent::init(parent::INIT_SESSION);
    }

    public function getType()
    {
        $this->chkUser(0);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, self::$searchOrderTypes);
    }

    private function generate_rand($length = -1)
    {
        if($length==-1){
            $rand_str=rand(1111,9999);
            $rand_arr=$array = str_split($rand_str, 1);
        }else{
            // 密码字符集，可任意添加你需要的字符
            $chars = '1Mq2Nw3Be4Vr5Ct6Xy7Zu8Li9Ko0Jp1Ha2Gs3Fd4Df5Sg6Ah7Pj8Ok9Il0Uz1Yx2Tc3Rv4Eb5Wn6Qm7890';
            $rand_str = '';
            $rand_arr = [];
            for ($i = 0; $i < $length; $i++) {
                // 这里提供两种字符获取方式
                // 第一种是使用 substr 截取$chars中的任意一位字符；
                // 第二种是取字符数组 $chars 的任意元素
                // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1);
                $code = $chars[mt_rand(0, strlen($chars) - 1)];
                $rand_str .= $code;
                $rand_arr [] = $code;
            }
        }
        return ['codeStr' => (string)$rand_str, 'codeArr' => $rand_arr];
    }

    public function verifyCode()
    {
        $expire=300;
        $code = $this->generate_rand();
        $tmp=time()+(int)$expire;
        $code['captcha_id'] = $key = $tmp.'_'.md5(uniqid(md5(microtime(true)), true));
        $this->cutRedisDatabase(function () use ($key, $code,$expire) {
            $GLOBALS['redis']->setex('verify_' . $key, $expire, $code['codeStr']);
        });
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $code);
    }

    public function getCurrentIssue()
    {
        if (!$lottery_id = $this->request->getPost('lottery_id', 'intval')) $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
        if (!$issueInfo = issues::getCurrentIssue($lottery_id)) { //issues_mini表查询当日当前时间之前最新开奖数据
            //issue表查询当前彩种最新的一条数据
            if (!$issues = issues::getItems($lottery_id, '', 0, 0, 0, 0, 0, '', 0, 1)) $this->showMsg(7030, mobileErrorCode::ISSUES_DATA_ERROR);
            $issueInfo = reset($issues);
        }
        $issueInfo = array(
            'issue_id' => $issueInfo['issue_id'],
            'issue' => $issueInfo['issue'],
            'end_time' => $issueInfo['end_sale_time'],
            'input_time' => $issueInfo['earliest_input_time'],
        );
        if ($lastIssueInfo = issues::getLastIssue($lottery_id, true)) {
            $lastIssueInfo = array(
                'issue_id' => $lastIssueInfo['issue_id'],
                'issue' => $lastIssueInfo['issue'],
                'code' => $lastIssueInfo['code'],
                'original_code' => $lastIssueInfo['original_code'],
            );
        }
        $lastOpenIssueInfo = issues::getLastOpenIssue($lottery_id);
        $kTime = $this->calTime($lottery_id);
        $result = array(
            'kTime' => $kTime,
            'issueInfo' => $issueInfo,
            'lastIssueInfo' => $lastIssueInfo,
            'lastOpenIssueInfo' => $lastOpenIssueInfo,
            'serverTime' => date('Y-m-d H:i:s')
        );
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $result);
    }
    public function getAppAlert()
    {
        $responseDatas = [
            'is_use_wp' => 0,
            'welcome_page' => '',
            'is_pretender' => '',
            'show_demo' => '',
            'ios_version' => '',
            'show_menu' => '',
            'menu_info' => '',
        ];
        $imgcdn = $this->getimgCdnUrl();
        list($appSetting, $appAlert) = $this->cutRedisDatabase(function () {
            $setting = $GLOBALS['redis']->hget('appset', 'appSetting');
            $alert = $GLOBALS['redis']->hget('appset', 'appAlert');
            return [$setting, $alert];
        });
        if (!empty($appAlert)) {
            $appAlert = unserialize($appAlert);
            $responseDatas['is_use_wp'] = !empty($appAlert['is_use_wp']) ? $appAlert['is_use_wp'] : 0;
            $responseDatas['welcome_page'] = !empty($appAlert['welcome_page']) ? $appAlert['welcome_page'] : '';
        } else {
            if (!empty($appAlert = M('appAlert')->field(['is_use_wp', 'welcome_page'])->find())) {
                if (!empty($appAlert['is_use_wp'])) $responseDatas['is_use_wp'] = $appAlert['is_use_wp'];
                if (!empty($appAlert['welcome_page'])) $responseDatas['welcome_page'] = !empty($path = $this->matchPath($appAlert['welcome_page'])) ? $imgcdn . '/' . $path : '';
                $this->cutRedisDatabase(function () use ($appAlert) {
                    $GLOBALS['redis']->hset('appset', 'appAlert', serialize($appAlert));
                });
            }
        }
        if (!empty($appSetting)) {
            $appSetting = unserialize($appSetting);
            $responseDatas['is_pretender'] = !empty($appSetting['is_pretender']) ? $appSetting['is_pretender'] : 1;
            $responseDatas['show_demo'] = !empty($appSetting['show_demo']) ? $appSetting['show_demo'] : 0;
            $responseDatas['ios_version'] = !empty($appSetting['ios_version']) ? $appSetting['ios_version'] : '';
            $show_menu = '';
            if (!empty($appSetting['show_menu'])) {
                $showMenu = explode(',', $appSetting['show_menu']);
                $show_menu = array_map(function ($menu) {
                    return ['menu' => $menu];
                }, $showMenu);
            }
            $responseDatas['show_menu'] = $show_menu;
            $responseDatas['menu_info'] = !empty($appSetting['menu_info']) ? $appSetting['menu_info'] : '';
        } else {
            $appSetting = M('appSetting')->find();
            if (!empty($appSetting)) {
                $sort = empty($appSetting['sort']) ? [1, 2, 3, 4, 5, 6] : explode(',', $appSetting['sort']);
                $responseDatas['is_pretender'] = !empty($appSetting['is_pretender']) ? $appSetting['is_pretender'] : 1;
                $responseDatas['show_demo'] = !empty($appSetting['show_demo']) ? $appSetting['show_demo'] : 0;
                $responseDatas['ios_version'] = !empty($appSetting['ios_version']) ? $appSetting['ios_version'] : '';
                $show_menu = '';
                if (!empty($appSetting['show_menu'])) {
                    $showMenu = explode(',', $appSetting['show_menu']);
                    $show_menu = array_map(function ($menu) {
                        return ['menu' => $menu];
                    }, $showMenu);
                }
                $responseDatas['show_menu'] = $show_menu;
                $m_info = [];
                if (!empty($appSetting['menu_info'])) {
                    $menu_info = unserialize($appSetting['menu_info']);
                    if (!empty($menu_info)) {

                        foreach ($sort as $mid) {
                            foreach ($menu_info as $menu) {
                                if ($menu['menu'] == $mid) {
                                    $menu['img'] = !empty($path = $this->matchPath($menu['img'])) ? $imgcdn . '/' . $path : '';
                                    $m_info[] = $menu;
                                }
                            }
                        }
                    }
                }
                $responseDatas['menu_info'] = !empty($m_info) ? $m_info : '';
                $appSetting['menu_info'] = $m_info;
                $this->cutRedisDatabase(function () use ($appSetting) {
                    $GLOBALS['redis']->hset('appset', 'appSetting', serialize($appSetting));
                });
            }
        }
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $responseDatas);
    }

    public function getAppAlertByDb()
    {
        $responseDatas = [
            'is_use_wp' => '',
            'welcome_page' => '',
            'is_pretender' => '',
            'show_demo' => '',
            'ios_version' => '',
            'show_menu' => '',
            'menu_info' => '',
        ];
        $imgcdn = $this->getimgCdnUrl();
        if (!empty($appAlert = M('appAlert')->field(['is_use_wp', 'welcome_page'])->find())) {
            if (!empty($appAlert['is_use_wp'])) $responseDatas['is_use_wp'] =  !empty($appAlert['is_use_wp']) ? $appAlert['is_use_wp'] : 0;
            if (!empty($appAlert['welcome_page'])) $responseDatas['welcome_page'] = !empty($path = $this->matchPath($appAlert['welcome_page'])) ? $imgcdn . '/' . $path : '';
        }
        $appSetting = M('appSetting')->find();
        if (!empty($appSetting)) {
            $sort = empty($appSetting['sort']) ? [1, 2, 3, 4, 5, 6] : explode(',', $appSetting['sort']);
            $responseDatas['is_pretender'] = !empty($appSetting['is_pretender']) ? $appSetting['is_pretender'] : 1;
            $responseDatas['show_demo'] = !empty($appSetting['show_demo']) ? $appSetting['show_demo'] : 0;
            $responseDatas['ios_version'] = !empty($appSetting['ios_version']) ? $appSetting['ios_version'] : '';
            $show_menu = '';
            if (!empty($appSetting['show_menu'])) {
                $showMenu = explode(',', $appSetting['show_menu']);
                $show_menu = array_map(function ($menu) {
                    return ['menu' => $menu];
                }, $showMenu);
            }
            $responseDatas['show_menu'] = $show_menu;
            $m_info = [];
            if (!empty($appSetting['menu_info'])) {
                $menu_info = unserialize($appSetting['menu_info']);
                if (!empty($menu_info)) {

                    foreach ($sort as $mid) {
                        foreach ($menu_info as $menu) {
                            if ($menu['menu'] == $mid) {
                                $menu['img'] = !empty($path = $this->matchPath($menu['img'])) ? $imgcdn . '/' . $path : '';
                                $m_info[] = $menu;
                            }
                        }
                    }
                }
            }
            $responseDatas['menu_info'] = !empty($m_info) ? $m_info : '';
        }
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $responseDatas);
    }

    /*
     * 设置apptoken 暂未使用
     */
    public function getAppToken()
    {
        if (!$this->getIsPostRequest()) $this->showMsg(6002, mobileErrorCode::REQUEST_ERROR);
        $key = $this->request->getPost('key', 'string', '');
        if (empty($key) || strlen($key) != 16 || !preg_match('/^[A-Za-z0-9]{16}$/', $key) || preg_match('/^[A-Za-z]$/', $key) || preg_match('/^[0-9]$/', $key)) {
            $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
        }
        $token = md5(uniqid(md5(microtime(true)), true));
        $this->cutRedisDatabase(function () use ($token, $key) {
            if (!$GLOBALS['redis']->setex('appToken_' . $token, 3600 * 24 * 1, $key)) {
                $this->showMsg(6006, mobileErrorCode::REDIS_ERROR);
            }
        });
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['token' => $token]);
    }

    /**
     * 公告列表
     * @author snow
     */
    public function noticeList()
    {
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $this->_getNoticeList());//手机端公告类型是4
    }

    /**
     * author snow 获取公告列表数据
     * @return array|bool|mixed
     */
    private function _getNoticeList()
    {
        $options = [
            'expired' => 0,  //>>公告过期时间
            'type' => 4,  //>>类型
            'start' => 0,  //>>分页起始页
            'amount' => 15, //>>分页尺寸
            'field' => 'notice_id, title',//>>查询字段
        ];

        return notices::getItems($options);
    }

    /**
     * Banner列表
     * @author nyajh 2016年1月18日
     */
    public function bannerList()
    {
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $this->getBannerList());
    }

    private function setHotCacheKeyExpire()
    {
        $serachhot_daytime_before = (int)config::getConfig('serachhot_daytime_before', self::SERACHHOT_DAYTIME_BEFORE);
        if ($serachhot_daytime_before < -1) {
            $this->showMsg(6007, mobileErrorCode::CONFIG_ERROR);
        }
        if ($serachhot_daytime_before == -1) {
            $expire = -1;
        } else {
            $expire = $serachhot_daytime_before * 24 * 3600;//当前时间今日的剩余秒数
        }
        $cachekey = 'lotteryShow';
        return [$cachekey, $expire];
    }
//    private function setCacheKeyExpire(){
//        $time=time();
//        $show_time=(int)config::getConfig('hot_time',1);
//        $h=date('H');
//        $expire_s=strtotime(date('Y-m-d',strtotime('+1 days')))-$time;//当前时间今日的剩余秒数
//        if($expire_s<0) response(['errCode' => 1, 'errMsg' => '计算错误',]);
//        //>>因为凌晨3点更新,当前时间小于3时,要多计算一天,比如2,2到3一小时也要算作一天
//        $expire=$expire_s<75600?$expire_s+($show_time-1)*3600+10800:($expire_s-75600)+($show_time-1)*3600;//75600=(24-3)*3600
//        $cachekey=$h<3?$cachekey=date('Ymd',strtotime('-1 days')):date('Ymd');
//        return [$cachekey,$expire];
//    }

//    private function _editHotReids(){
//        $hots=hot::getItems();
//        $show_datas=[];
//        if(count($hots)>=1){
//            foreach ($hots as $item){
//                $show=[];
//                $tmp_bet_people=$item['bet_people']*100;
//                $tmpfloat_bet_people=$item['float_people']*100;
//                $tmp_totals=$item['totals']*100;
//                $tmpfloat_totals=$item['float_totals']*100;
//                $tmp_prize=$item['prize']*100;
//                $tmpfloat_prize=$item['float_prize']*100;
//                $tmp_odds_win=$item['odds_win']*100;
//                $tmpfloat_odds_win=$item['float_odds_win']*100;
//                $show['lottery_id']=$item['lottery_id'];
//                $show['lottery_name']=$item['lottery_name'];
//                $show['lottery_belong']=$item['lottery_belong'];
//                $show['bet_people']=(float)(rand(($tmp_bet_people-$tmpfloat_bet_people),($tmp_bet_people+$tmpfloat_bet_people))/100);
//                $show['totals']=(float)(rand(($tmp_totals-$tmpfloat_totals),($tmp_totals+$tmpfloat_totals))/100);
//                $show['prize']=(float)(rand(($tmp_prize-$tmpfloat_prize),($tmp_prize+$tmpfloat_prize))/100);
//                $show['odds_win']=(float)(rand(($tmp_odds_win-$tmpfloat_odds_win),($tmp_odds_win+$tmpfloat_odds_win))/100);
//                $show_datas[]=$show;
//            }
//        }
//        return $show_datas;
//    }

//    private function _setHotRedis($show_datas,$cachekey='',$expire=''){
//        if(empty($show_datas)) return false;
//        if(empty($cachekey)||empty($expire))list($cachekey,$expire)=$this->setCacheKeyExpire();
//        $data=serialize($show_datas);
//        if(!empty($show_datas))return $GLOBALS['redis']->set('hot_'.$cachekey,$data,$expire)==true?$data:false;
//        return false;
//    }
//    public function hotShow(){
//        $hots=$this->_getSetHot();
//        if($hots){
//            foreach ($hots as $k=>$hot){
//                $openinfo=$this->_openInfo($hot['lottery_id']);
//                $hots[$k]['openinfo']=$openinfo['issueInfo'];
//            }
//        }
//        $this->showMsg(0,mobileErrorCode::RETURN_SUCCESS,$hots);
//    }
//    private function _getSetHot(){
//        $GLOBALS['redis']->select(REDIS_DB_APP);
//        list($cachekey,$expire)=$this->setCacheKeyExpire();
//        if(($res=$GLOBALS['redis']->get('hot_'.$cachekey))==false){
//            $datas=$this->_setHotRedis($this->_editHotReids(),$cachekey,$expire);
//            if($datas!=false)$res=$datas;
//            else $res=false;
//        }
//        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
//        return $res!=false?unserialize($res):false;
//    }

    private function getLotteryPrizeInfo($lottery_id,$startDate, $endDate,$expire){
        $data1=$this->cutRedisDatabase(function()use($lottery_id){
            $GLOBALS['redis']->get('lottery_'.$lottery_id.'_prizeTotal');
        });
        if($data1==false){
            $data1 = projects::getPrizeTotal($lottery_id, 1, '', 0, $startDate, $endDate);
            $this->cutRedisDatabase(function()use($lottery_id,$data1,$expire){
                $GLOBALS['redis']->setex('lottery_'.$lottery_id.'_prizeTotal',$expire,serialize($data1));
            });
        }else $data1=unserialize($data1);
        $data2=$this->cutRedisDatabase(function()use($lottery_id){
            $GLOBALS['redis']->get('lottery_'.$lottery_id.'_amountTotal');
        });
        if($data2==false){
            $data2 = projects::getAmountTotal($lottery_id, -1, '', 0, $startDate, $endDate);
            $this->cutRedisDatabase(function()use($lottery_id,$data2,$expire){
                $GLOBALS['redis']->setex('lottery_'.$lottery_id.'_amountTotal',$expire,serialize($data2));
            });
        }else $data2=unserialize($data2);
        return [
            'prizeTotal'=>$data1,
            'amountTotal'=>$data2,
            ];
    }

    public function randdata($lottery_ids,$is_flush=0){
        $randdata=$this->cutRedisDatabase(function(){
            return $GLOBALS['redis']->get('randdata_lottery');
        });
        if($randdata==false||$is_flush){
            $randdata=[];
            foreach ($lottery_ids as $lottery_id){
                $prize=$this->lottery[$lottery_id];
                $len=strlen(floor($prize['prize']));
                if($len>8){
                    $len=8;
                }elseif($len<5){
                    $len='min';
                }
                $a=$this->rand[$len][1];
                $b=$this->rand[$len][0];
                $i_prize=(($prize['prize']/10000+rand($a,$b))*100+rand(99,11))*100;
                if(!empty($prize['total'])){
                    $beishu=rand($prize['total'][0],$prize['total'][1]);//总投注对应中奖金额倍数
                }else{
                    $beishu=rand($this->defaultRand['total'][0],$this->defaultRand['total'][1]);//总投注对应中奖金额倍数
                }

                $i_total=$i_prize*$beishu;
                if(!empty($prize['odd_win'])){
                    $odd_win=rand($prize['odd_win'][0],$prize['odd_win'][1]);//总投注对应中奖金额倍数
                }else{
                    $odd_win=rand($this->defaultRand['odd_win'][0],$this->defaultRand['odd_win'][1]);//总投注对应中奖金额倍数
                }
                if(!empty($prize['people'])){
                    $ren=rand($prize['people'][0],$prize['people'][1]);//总投注对应中奖金额倍数
                }else{
                    $ren=rand($this->defaultRand['people'][0],$this->defaultRand['people'][1]);//总投注对应中奖金额倍数
                }

                $i_bet_people=ceil($i_total/$ren);
                $randdata[$lottery_id]= [
                    'bet_people' => $i_bet_people,
                    'total' => $i_total,
                    'prize' => $i_prize,
                    'odds_win' => $odd_win,
                ];
            }
            $expire=strtotime(date('Y-m-d').' 23:59:59')-time();
            $this->cutRedisDatabase(function()use($randdata,$expire){
                $GLOBALS['redis']->setex('randdata_lottery', $expire ,serialize($randdata));
            });
        }else{
            $randdata=unserialize($randdata);
        }
        return $randdata;
    }
    public function hotShow()
    {
        list($cachekey, $expire) = $this->setHotCacheKeyExpire();
        $GLOBALS['redis']->select(REDIS_DB_APP);
        if (($res = $GLOBALS['redis']->get('hot_' . $cachekey)) == false) {
            $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
            $lottery_ids = array_column($lotterys, 'lottery_id');
            $res = [];
            $randData = $this->randdata(array_column($lotterys, 'lottery_id'));
            foreach ($lottery_ids as $lottery_id) {
                if ($lottery_id == 15) continue;
                $rand = $randData[$lottery_id];
                $rand['lottery_id'] = $lottery_id;
                $rand['lottery_name'] = $lotterys[$lottery_id]['cname'];
                $res[] = $rand;
            }
            foreach ($res as $key => $row) {
                $total[$key] = $row['total'];
                $bet_peoples[$key] = $row['bet_people'];
                $openinfos[$key] = empty($info) ? 0 : 1;
            }
            array_multisort($openinfos, SORT_DESC, $total, SORT_DESC, $bet_peoples, SORT_DESC, $res);
            $output = array_slice($res, 0, 4);
            if ($expire == -1) {
                $GLOBALS['redis']->set('hot_' . $cachekey, serialize($output));
            } else $GLOBALS['redis']->setex('hot_' . $cachekey, $expire, serialize($output));
        } else {
            $output = unserialize($res);
        }
        $cache_liuhecai='';
        $lhc_index='';
        foreach ($output as $k => $i) {
            $openinfo = $this->_openInfo($i['lottery_id']);
            $info = '';
            if (!empty($openinfo['issueInfo'])) $info = $openinfo['issueInfo'];
            if (!empty($info)) $info['count_down'] = strtotime($info['end_time']) - REQUEST_TIME;
            $output[$k]['openinfo'] = $info;
            $output[$k]['sertime'] = REQUEST_TIME;
            $kTime = $this->calTime($i['lottery_id']);
            $output[$k]['kTime'] = $kTime;
            $n_prize[$k] = $i['prize'];
            $n_bet_peoples[$k] = $i['bet_people'];
            $n_openinfos[$k] = empty($info) ? 0 : 1;
            //>>六合彩
            if($i['lottery_id']==21){
                $liuhecai=$output[$k];
                $GLOBALS['redis']->select(REDIS_DB_APP);
                $lhc = $GLOBALS['redis']->get('hot_' . $cachekey . '_lhc');
                if ($lhc === false) {
                    if ($expire == -1) {
                        $GLOBALS['redis']->set('hot_' . $cachekey . '_lhc', serialize($output));
                    } else {
                        $lhc_expire=!empty($info['input_time'])?strtotime(date('Y-m-d 23:59:59',strtotime($info['input_time'])))-time():($expire+86400);
                        $GLOBALS['redis']->setex('hot_' . $cachekey . '_lhc', $lhc_expire, serialize($liuhecai));
                    }
                } else {
                    $lhc_index=$k;
                    $cache_liuhecai = unserialize($lhc);
                }
            }
        }
        if(!empty($cache_liuhecai)){
            if(!empty($lhc_index)){
                unset($output[$lhc_index]);
                if(!empty($n_prize))unset($n_prize[$lhc_index]);
                if(!empty($n_bet_peoples))unset($n_bet_peoples[$lhc_index]);
                if(!empty($n_openinfos))unset($n_openinfos[$lhc_index]);
            }
            $output[]=$cache_liuhecai;
            $n_prize[] = $cache_liuhecai['prize'];
            $n_bet_peoples[] = $cache_liuhecai['bet_people'];
            $n_openinfos[] = empty($cache_liuhecai['openinfo']) ? 0 : 1;
        }
        if(!empty($n_bet_peoples)&&!empty($n_prize)&&!empty($n_openinfos)){
            array_multisort($n_openinfos, SORT_DESC, $n_prize, SORT_DESC, $n_bet_peoples, SORT_DESC, $output);
        }
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $output);
    }
//    public function hotShow()
//    {
//        list($cachekey, $expire) = $this->setHotCacheKeyExpire();
//        $GLOBALS['redis']->select(REDIS_DB_APP);
//        if (($res = $GLOBALS['redis']->get('hot_' . $cachekey)) == false) {
//            $win_ceil = config::getConfig('win_ceil', self::WIN_CEIL);
//            $lotterys = lottery::getItemsNew(['lottery_id', 'cname']);
//            $total_ceil = config::getConfig('total_ceil', self::TOTAL_CEIL);
//            $lottery_ids = array_column($lotterys, 'lottery_id');
//            $bet_people_ceil = config::getConfig('bet_people_ceil', self::BET_PEOPLE_CEIL);
//            $odd_win_intercept = config::getConfig('odd_win_intercept', self::ODD_WIN_INTERCEPT);
//            $serachhot_daytime_before = config::getConfig('serachhot_daytime_before', self::SERACHHOT_DAYTIME_BEFORE);
//
//            if ($serachhot_daytime_before < -1) {
//                $this->showMsg(6007, mobileErrorCode::CONFIG_ERROR);
//            } elseif ($serachhot_daytime_before == -1) {
//                $endDate = '';
//                $startDate = '';
//            } else {
//                $endDate = date('Y-m-d 23:59:59');
//                $startDate = date("Y-m-d 00:00:00", strtotime("$endDate -$serachhot_daytime_before day"));
//            }
//            $res=[];
//            foreach ($lottery_ids as $lottery_id){
//                if($lottery_id==15) continue;
//                $lo_pri_info=$this->getLotteryPrizeInfo($lottery_id,$startDate, $endDate,$expire);
//                $data1=$lo_pri_info['prizeTotal'];
//                $data2=$lo_pri_info['amountTotal'];
//                $prize_count = empty($data1) || empty($data1['total_count']) ? 0 : $data1['total_count'];
//                $total_prize = empty($data1) || empty($data1['total_prize']) ? 0 : $data1['total_prize'];
//                $all_count = empty($data2) || empty($data2['total_count']) ? 0 : $data2['total_count'];
//                $total_amount = empty($data2) || empty($data2['total_amount']) ? 0 : $data2['total_amount'];
//                $odd_win = 0;
//                if ($all_count != 0) $odd_win = round($prize_count / $all_count * 100, 2);
//                if ($odd_win < $odd_win_intercept) {
//                    if ($odd_win_intercept < 50) $odd_win = $odd_win_intercept + $odd_win / 2;
//                    elseif ($odd_win_intercept < 80) $odd_win = $odd_win_intercept + $odd_win / 10;
//                    else $odd_win = $odd_win_intercept + $odd_win / 15;
//                }
//                $i_total=ceil($total_amount * (100 + $total_ceil) / 100);
//                $i_bet_people=ceil($all_count * (100 + $bet_people_ceil) / 100);
//                $i_prize=ceil($total_prize * (100 + $win_ceil) / 100);
//
//                $res[]= [
//                    'lottery_id' => $lottery_id,
//                    'lottery_name' => $lotterys[$lottery_id]['cname'],
//                    'bet_people' => $i_bet_people,
//                    'total' => $i_total,
//                    'prize' => $i_prize,
//                    'odds_win' => $odd_win,
//                ];
//
//            }
//            foreach ($res as $key => $row) {
//                $total[$key] = $row['total'];
//                $bet_peoples[$key] = $row['bet_people'];
//                $openinfos[$key] = empty($info) ? 0 : 1;
//            }
//            array_multisort($openinfos, SORT_DESC, $total, SORT_DESC, $bet_peoples, SORT_DESC, $res);
//            $output = array_slice($res, 0, 3);
//            foreach ($output as $k => $i) {
//                $openinfo = $this->_openInfo($i['lottery_id']);
//                $info = '';
//                if (!empty($openinfo['issueInfo'])) $info = $openinfo['issueInfo'];
//                if (!empty($info)) $info['count_down'] = strtotime($info['end_time']) - REQUEST_TIME;
//                $output[$k]['openinfo'] = $info;
//                $output[$k]['sertime'] = REQUEST_TIME;
//            }
//            $GLOBALS['redis']->select(REDIS_DB_APP);
//            if ($expire == -1) {
//                $GLOBALS['redis']->set('hot_' . $cachekey, serialize($output));
//            } else $GLOBALS['redis']->setex('hot_' . $cachekey, $expire, serialize($output));
//        } else $res = unserialize($res);
//
//        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
//        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $output);
//    }

    public function getOpen()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval', 0);
        $res = $this->_openInfo($lottery_id);
//        dd(array_slice($res['openIssues'], 0, 5));
//        dd($res['openIssues']);
        $openIssues = empty($res) || empty($res['openIssues']) ? '' : $res['openIssues'];
        if ($res) $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['lottery_id' => $lottery_id, 'openIssues' => $openIssues]);
        $this->showMsg(7029, '获取开奖数据失败');
    }

//    protected function writeFile($postdata,$filename='requestLog', $dir = 'lottery')
//    {
//        $path = './'.$dir . '/' ;
//        $filename = $path . $filename . '.txt';
//        if (!file_exists($path)) mkdir($path, 0777, true);
//        return file_put_contents($filename, $postdata) ? true : false;
//    }
//    protected function readFile($filename='requestLog', $dir = 'lottery')
//    {
//        $path = './'.$dir . '/' ;
//        $file = $path . $filename . '.txt';
//        if(!is_file($file)){
//            $res=$this->total_prize_rand['num'];
//        }else{
//            $res=file_get_contents($file);
//        }
//        $res= $res+rand($this->total_prize_rand['ceil'][0],$this->total_prize_rand['ceil'][1]);
//        $this->writeFile($res,$filename,$dir);
//        return $res;
//    }
//    public function totalPrize()
//    {
//        $show_total_prize=$this->cutRedisDatabase(function(){
//            return $GLOBALS['redis']->get('rand_lotteryTotalPrize');
//        });
//        if($show_total_prize==false){
//            $show_total_prize = $this->readFile('rand_lotteryTotalPrize');
//            $this->writeFile($show_total_prize,'rand_lotteryTotalPrize');
//            $expire=strtotime(date('Y-m-d').' 23:59:59')-time();
//            $this->cutRedisDatabase(function()use($show_total_prize,$expire){
//                $GLOBALS['redis']->setex('rand_lotteryTotalPrize',86400,$show_total_prize);
//            });
//        }
//        $data['show_total_prize'] = $show_total_prize;
//        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $data);
//    }

    public function totalPrize()
    {
        $lottery_id = $this->request->getGet('lottery_id', 'intval', 0);
        $issue = $this->request->getGet('issue', 'string', '');
        $show_total_prize=$this->cutRedisDatabase(function(){
            return $GLOBALS['redis']->get('lotteryTotalPrize');
        });
        if($show_total_prize==false){
            $res = projects::getPrizeTotal($lottery_id, 1, $issue, 0);
            $total_prize_ceil = config::getConfig('total_prize_ceil', self::TOTAL_PRIZE_CEIL);
            $show_total_prize=$res['total_prize'] * (100 + $total_prize_ceil) / 100;
            $this->cutRedisDatabase(function()use($show_total_prize){
                $GLOBALS['redis']->setex('lotteryTotalPrize',86400,$show_total_prize);
            });
        }
//        $res['show_total_prize'] = number_format($show_total_prize, 0, '', '');
        $res['show_total_prize'] = sprintf("%.0f", $show_total_prize);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $res);
    }

    public function newOpen()
    {
        $res = $this->_openInfo(0, 1);
        $randdata=$this->randdata(array_column($res,'lotteryId'));
        if(count($randdata)!=count($res)){
            $randdata=$this->randdata(array_column($res,'lotteryId'),1);
        }
        foreach ($res as &$lottery) {
            $lottery_id=$lottery['lotteryId'];
            $lottery['total_amount'] = $randdata[$lottery_id]['total'] > 0 ? $randdata[$lottery_id]['total'] : -($randdata[$lottery_id]['total']);
            $lottery['kTime'] = $this->calTime($lottery['lotteryId']);
        }
        unset($lottery);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, array_values($res));
    }
//
//    public function newOpen()
//    {
//        $res = $this->_openInfo(0, 1);
//        foreach ($res as &$lottery) {
//            $lottery_id=$lottery['lotteryId'];
//            $total_amount=$this->cutRedisDatabase(function()use($lottery_id){
//                return $GLOBALS['redis']->get('lotery_amount'.$lottery_id);
//            });
//            if(empty($total_amount)){
//                $data2 = projects::getAmountTotal($lottery['lotteryId'], -1, '', 0, '', '');
//                $total_amount = empty($data2) || empty($data2['total_amount']) ? 0 : $data2['total_amount'];
//                $this->cutRedisDatabase(function()use($lottery_id,$total_amount){
//                    $GLOBALS['redis']->setex('lotery_amount'.$lottery_id, 86400, $total_amount);
//                });
//            }
//            $lottery['total_amount'] = $total_amount;
//            $lottery['kTime'] = $this->calTime($lottery['lotteryId']);
//        }
//        unset($lottery);
//        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, array_values($res));
//    }

    /**
     * 拿到开奖结果或最新开奖信息
     * @param int | array $lotteryParam 彩种id 或 数组
     * @param bool $onlyLast 最后一期
     * @return array|mixed
     */
    private function _openInfo($lotteryParam = 0, $onlyLast = false)
    {
        // $lotteryIdList = $lotteryParam > 0 ? [] : is_array($lotteryParam) ? $lotteryParam : lottery::getItemsNew(['lottery_id']);

        $lotteryList = [];
        if(is_numeric($lotteryParam) && $lotteryParam > 0){
        }else{
            if(is_array($lotteryParam)){
                $lotteryList = &$lotteryParam;
            }else{
                $lotteryList = lottery::getItemsNew(['lottery_id']);
                $lotteryList = array_column($lotteryList, 'lottery_id');
            }
        }

        // sort 根据奖期排序 ASC DESC
        $sort = $this->request->getGet('sort', 'trim', 'DESC');

        $GLOBALS['redis']->pushPrefix()->select(REDIS_DB_COMMON_DATA);
        if ($lotteryList) {
            foreach ($lotteryList as $key => &$lotteryId) {
                $cacheKey = 'openInfo_' . $lotteryId;
                if ($info = $GLOBALS['redis']->hGet('cronOpenInfo', $cacheKey)) {
                    $info = json_decode($info, true);
                    if (empty($info['lastIssueInfo']['cname'])) {
                        if (!empty($lottery_item = lottery::getItem($lotteryId))) {
                            $info['lastIssueInfo']['cname'] = $lottery_item['cname'];
                        }
                    }

                    if ($onlyLast) {
                        $lotteryId = [
                            'lotteryId' => $lotteryId,
                            'serverTime' => $info['serverTime'],
                            'issueInfo' => [
                                'count_down' => strtotime($info['issueInfo']['end_time']) - REQUEST_TIME,
                                'end_time' => $info['issueInfo']['end_time'],
                            ],

                            'lastIssueInfo' => [
                                'cname' => !empty($info['lastIssueInfo']['cname']) ? $info['lastIssueInfo']['cname'] : '',
                                'issue' => isset($info['lastIssueInfo']['issue'])?$info['lastIssueInfo']['issue']:'',
                                'code' => isset($info['lastIssueInfo']['code'])?$info['lastIssueInfo']['code']:'',
                            ],
                            'lastopenIssues' => !empty($info['openIssues'][0]) ? $info['openIssues'][0] : ['issue' => '', 'code' => '', 'end_sale_time' => ''],
                        ];
                    } else {
                        $info['issueInfo']['end_time'] = strtotime($info['issueInfo']['end_time']);
                        $info['issueInfo']['count_down'] = $info['issueInfo']['end_time'] - REQUEST_TIME;
                        $info['issueInfo']['input_time'] = strtotime($info['issueInfo']['input_time']);

                        if (strtoupper($sort) == 'ASC' && $info['openIssues']) $info['openIssues'] = array_reverse($info['openIssues']);
                        $lotteryId = $info;
                    }
                } else {
                    // 类似秒秒彩这样的彩种是没有记录数据的
                    unset($lotteryList[$key]);
                }
            }
        } else {
            $cacheKey = 'openInfo_' . $lotteryParam;
            if ($info = $GLOBALS['redis']->hGet('cronOpenInfo', $cacheKey)) {
                $info = json_decode($info, true);
                $info['lotteryId'] = $lotteryParam;
                if (strtoupper($sort) == 'ASC') $info['openIssues'] = array_reverse($info['openIssues']);
            }

            $lotteryList =& $info;
        }
        $GLOBALS['redis']->popPrefix()->select(REDIS_DB_DEFAULT);
        // 这里根据不同端返回不同格式
        return $lotteryList;
    }

    /**
     * activityList
     * @author nyajh 2016年1月18日
     */
    public function activityList()
    {
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $this->getActivities());
    }

    /**
     * 公告详情
     * @author nyjah 2016年1月18日
     */
    public function noticeDetail()
    {
        $nid = $this->request->getGet('nid', 'intval');

        if (!$notice = notices::getItem($nid, 0)) {
            $this->showMsg(8001, mobileErrorCode::GET_DATA_FAIL);
        }

        if (strtotime($notice['start_time']) > time() || strtotime($notice['expire_time']) < time()) {
            $this->showMsg(8001, mobileErrorCode::GET_DATA_FAIL);
        }
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $notice);
    }

    /**
     * 获取最新版本号
     * @author Davy 2016年1月13日
     * @param  $id
     */
    public function getLastVersion()
    {
        $result = array();
        $lastVersion = mobileLottery::getLastVersion(date('Y-m-d H:i:s'));
        if (empty($lastVersion)) {
            $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $result);
        }
        $result['ver_number'] = $lastVersion['version_number'];
        $result['file_name'] = $lastVersion['filename'];
        $result['isForce'] = $lastVersion['is_force'];
        $result['update_describe'] = $lastVersion['description'];
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $result);
    }

    public function lotteryList(){
        $this->getLotteryList();
    }

    private function getLotteryList($type=0){
        $list=$this->cutRedisDatabase(function(){
            return $GLOBALS['redis']->hget('appset','appLotteryImg');
        });
        if($list===false){
            $list=M('appLotteryList')->find();
            if(empty($list)){
                $list= [
                    'id_1'=>[
                        'list_id'=>1,
                        'name'=>'信用玩法',
                        'is_use'=>1,
                        'lotteryList'=>[1, 26, 12, 17, 11, 2, 13, 9, 10, 4, 5, 6, 7, 8, 14, 19, 22, 23, 21, 24, 18, 16, 25]
                    ],
                    'id_2'=>[
                        'list_id'=>2,
                        'name'=>'时时彩',
                        'is_use'=>1,
                        'lotteryList'=>[1, 4, 8, 18, 15, 11, 24]
                    ],
                    'id_3'=>[
                        'list_id'=>3,
                        'name'=>'11选5',
                        'is_use'=>1,
                        'lotteryList'=>[2, 5, 6, 7, 16]
                    ],
                    'id_4'=>[
                        'list_id'=>4,
                        'name'=>'低频彩',
                        'is_use'=>1,
                        'lotteryList'=>[9, 10, 21, 22]
                    ],
                    'id_5'=>[
                        'list_id'=>5,
                        'name'=>'快乐彩',
                        'is_use'=>1,
                        'lotteryList'=>[17, 12, 13, 19, 23, 14, 25]
                    ],
                ];
            }else {
                $list=unserialize($list['list']);
                foreach ($list as $k=>$i){
                    if($i['is_use']!=1)unset($list[$k]);
                }
                $this->cutRedisDatabase(function()use($list){
                    $GLOBALS['redis']->hset('appset','appLotteryImg',serialize($list));
                });
            }
        }else $list=unserialize($list);
        if($type==0){
            if(!empty($list))$list=array_values($list);
            foreach ($list as &$it)unset($it['lotteryList']);
            unset($it);
            $this->showMsg(0,mobileErrorCode::RETURN_SUCCESS,['list'=>$list]);
        }
        return $list;
    }
    /**
     * 购彩大厅
     */
    public function lobby()
    {
        $index = $this->request->getGet('index', 'intval', 0);

        # TODO: 这个列表写到后台配置
        $list = [
            [1, 26, 12, 17, 11, 2, 13, 9, 10, 4, 5, 6, 7, 8, 14, 19, 22, 23, 21, 24, 18, 16, 25], // 信用玩法
            [1, 4, 8, 18, 15, 11, 24], // 时时彩
            [2, 5, 6, 7, 16], // 11选5
            [9, 10, 21, 22], // 低频彩
            [17, 12, 13, 19, 23, 14, 25] // 快乐彩
        ];

        $lotterys = lottery::getItemsNew(['lottery_id']);
        $lottery_ids = !empty($lotterys) ? array_column($lotterys, 'lottery_id') : [];
        foreach ($list as &$item) {
            $item = array_intersect($item, $lottery_ids);
        }

        if ($index > count($list) - 1 || empty($list[$index])) {
            $this->showMsg(8001, mobileErrorCode::GET_DATA_FAIL);
        }

        $openInfo = $this->_openInfo($list[$index], 1);
        $openInfo = array_index($openInfo, 'lotteryId');
        $allowedIdList = array_keys($openInfo);

        $data = [];
        foreach ($list[$index] as $lotteryId) {
            if (in_array($lotteryId, $allowedIdList)) {
                $kTime = $this->calTime($lotteryId);
                $data[] = [
                    'lottery_id' => $lotteryId,
                    'count_down' => $openInfo[$lotteryId]['issueInfo']['count_down'],
                    'cname' => $openInfo[$lotteryId]['lastIssueInfo']['cname'],
                    'issue' => $openInfo[$lotteryId]['lastIssueInfo']['issue'],
                    'code' => $openInfo[$lotteryId]['lastIssueInfo']['code'],
                    'kTime' => $kTime,
                ];
            } else if ($lotteryId === 15) {
                $data[] = [
                    'lottery_id' => 15,
                    'count_down' => 0,
                    'cname' => '秒秒彩',
                    'issue' => '0000000',
                    'code' => '',
                    'kTime' => 0,
                ];
            }
        }

        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $data);
    }
    /**
     * 购彩大厅
     */
    public function newLobby()
    {
        $index = $this->request->getGet('index', 'intval', 0);
        $list=$this->getLotteryList(1);
        if(!isset($list['id_'.$index]['lotteryList'])||empty($lotteryList=$list['id_'.$index]['lotteryList'])) $this->showMsg(8001, mobileErrorCode::GET_DATA_FAIL);
        $lotterys = lottery::getItemsNew(['lottery_id']);
        $lottery_ids = !empty($lotterys) ? array_column($lotterys, 'lottery_id') : [];
        foreach ($list as &$item) {
            $item['lotteryList'] = array_intersect($item['lotteryList'], $lottery_ids);
        }
        unset($item);
        if ($index > count($list) || $index<1) {
            $this->showMsg(8001, mobileErrorCode::GET_DATA_FAIL);
        }
        $openInfo = $this->_openInfo($lotteryList, 1);
        $openInfo = array_index($openInfo, 'lotteryId');
        $allowedIdList = array_keys($openInfo);

        $data = [];
        foreach ($lotteryList as $lotteryId) {
            if (in_array($lotteryId, $allowedIdList)) {
                $kTime = $this->calTime($lotteryId);
                $data[] = [
                    'lottery_id' => $lotteryId,
                    'count_down' => $openInfo[$lotteryId]['issueInfo']['count_down'],
                    'cname' => $openInfo[$lotteryId]['lastIssueInfo']['cname'],
                    'issue' => $openInfo[$lotteryId]['lastIssueInfo']['issue'],
                    'code' => $openInfo[$lotteryId]['lastIssueInfo']['code'],
                    'kTime' => $kTime,
                ];
            } else if ($lotteryId === 15) {
                $data[] = [
                    'lottery_id' => 15,
                    'count_down' => 0,
                    'cname' => '秒秒彩',
                    'issue' => '0000000',
                    'code' => '',
                    'kTime' => 0,
                ];
            }
        }

        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $data);
    }

    /**
     * 获取首页bnner 图 ,公告 ,优惠活动.
     */
    public function welcome()
    {
        $info = [
            'bannerList' => $this->getActivities(false,1),              //>>获取banner 图
            'noticeList' => $this->_getNoticeList(),   //>>获取公告
            'lotteryList' => $this->_getLotteryLs(),   //>>获取采种信息
            'userAlert' => $this->getAlert(),   //>>获取弹窗公告
            'game_type' => config::getConfig('app_game_type',3),   //>>玩法:1:官方 2:信用 3:官方&信用
        ];

        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $info);
    }

    private function getAlert()
    {
        /* 已登录状态下默认弹窗 开始 */
        if (!empty($user = $this->isLogined(1))) {
            $userAlert = (new userAlert)->getUserAlert($user['top_id']);
        } else {
            $userAlert = (new userAlert)->getUserAlert();
        }
        if ($userAlert && $userAlert['type'] == userAlert::TYPE_IMAGE) {
            $userAlert['m_main_img'] = $this->getimgCdnUrl() . '/' . $this->matchPath($userAlert['m_main_img']);
        }
        unset($userAlert['main_img']);
        return !empty($userAlert) ? $userAlert : '';
    }

    private function matchPath($path)
    {
        preg_match('@.*(images_fh.*)$@', $path, $macth);
        return isset($macth[1]) ? $macth[1] : '';
    }

    /**
     * author  snow
     * 处理gift_banner 为数组形式
     * @return array
     */
    private function getBannerList()
    {
        //>>创建banner html 标签

        $gift_banner = userGiftsControl::createBanners(1);

        //>>提取出html 标签中的链接地址与图上地址 的正则表达式
        $parten = <<<PREG
/\<a\s*href=['"](.*?)['"]\s*.*?><\s*img\s*src=['"](.*?)['"]\/\>/
PREG;
        preg_match_all($parten, $gift_banner, $data);
        //>>判断
        if (!empty($data)) {
            //>>有匹配到数据

            $c = 0;
            $tmpArr = array_map(function ($a, $b) use ($c) {
                return [
                    'id' => $c++,
                    'type' => 'banner',
                    'link' => self::$mobileDomain . $a,
                    'image_path' => self::$PublicImgCdn . preg_replace('/.*(\/images.*)/', '$1', $b)
                ];
            }, $data[1], $data[2]);
        } else {
            //>>判断如果没有匹配上
            $tmpArr = [];
        }

        return $tmpArr;
    }

    /**
     * author  snow
     * 获取优惠活动图片
     * @param bool $flag //>>默认为true  获取完整数据  ,如果为false  只获取关键数据
     * @return bool
     */

    private function getActivities($flag = true,$is_banner=0)
    {

        //>>需要处理的图片
        $redisKey = 'appGetActivities_' . ($flag ? 1 : 0);
        $GLOBALS['redis']->select(REDIS_DB_APP);//>>切换到app库
        $data = redisGet($redisKey, function () use ($flag,$is_banner) {
            $kindImg = ['banner_img', 'thumb_img', 'main_img', 'm_banner_img', 'm_thumb_img', 'm_main_img'];
            $field = $flag === true ? ['id','m_banner_img', 'm_thumb_img', 'm_main_img','end_time', 'title','target','content','start_time','end_time'] : '*';
            $activities = activity::getItems($field);

            if (empty($activities)) {
                return [];
            }
            $imgcdn = $this->getimgCdnUrl();
            foreach ($activities as $k => &$activity) {
                $chk=$is_banner==1?'m_banner_img':'m_thumb_img';
                if(empty($activity[$chk])){
                    unset($activities[$k]);
                    continue;
                }
                if (strtotime($activity['end_time']) < time()) {
                    unset($activities[$k]);
                    continue;
                }

                foreach ($activity as $key => $v) {
                    if (in_array($key, $kindImg)) {
                        preg_match('@.*(images_fh.*)$@', $v, $macth);
                        if (isset($macth[1])) {
                            $activity[$key] = $imgcdn . '/' . $macth[1];
                        } else {
                            $activity[$key] = '';
                        }
                    }
                }

                //>>判断如果传入false 值 ,只获取部分数据
                if ($flag === false) {
                    $activities[$k] = [
                        'id' => $activity['id'],
                        'type' => 'activity',
                        'link' => $imgcdn . '?c=fake&a=platformact',
                        'image_path' => $activity['m_banner_img'],
                        'm_thumb_img' => $activity['m_thumb_img'],
                        'm_main_img' => $activity['m_main_img'],
                    ];
                }
            }
            unset($activity);
            unset($kindImg);
            return array_values($activities);
        }, 86400);
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);//>>切换回默认库
        return $data;

    }

    /**
     * author snow
     * 获取活动详情
     */
    public function activityDetail()
    {
        $id = $this->request->getGet('id', 'intval');
        if ($id === 0) {
            //>>参数错误
            $this->showMsg('6003', mobileErrorCode::REQUEST_PARAMS_ERROR, []);
        }

        //>>执行查询,获取数据
        $imgcdn = $this->getimgCdnUrl();
        $info = activity::getItem($id);
        if(!empty($info)){
            unset($info["thumb_img"],$info["main_img"], $info["banner_img"]);
            $info["m_thumb_img"]= !empty($path = $this->matchPath($info["m_thumb_img"])) ? $imgcdn . '/' . $path : '';
            $info["m_main_img"]= !empty($path = $this->matchPath($info["m_main_img"])) ? $imgcdn . '/' . $path : '';
            $info["m_banner_img"]= !empty($path = $this->matchPath($info["m_banner_img"])) ? $imgcdn . '/' . $path : '';
        }
        if (empty($info)) {
            //>>没有相关数据
            $this->showMsg('6003', mobileErrorCode::REQUEST_PARAMS_ERROR, []);
        }

        //>>正常返回数据
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $info);
    }

    /**
     * 获取彩种图标
     */
    private function _getLotteryLs()
    {

        //>>获取所有彩种信息
        $cond = 'status = 8';
        $fields = 'lottery_id, cname';
        $lottery = lottery::getAllItems($cond, $fields);
        $appLotteryImg=$this->cutRedisDatabase(function(){
            return $GLOBALS['redis']->hget('appset','appLotteryImg');
        });
        $imgs=[];
        if(!empty($appLotteryImg)){
            $imgs=unserialize($appLotteryImg);
            $imgs = array_column($imgs, 'img', 'lottery_id');
        }
        $tmp = [];
        $imgcdn = $this->getimgCdnUrl(false);
        foreach ($lottery as $key => $val) {
            if(!empty($imgs[$val['lottery_id']])){
                $val['img'] = $imgs[$val['lottery_id']];
            }else{
                //>>拼接图片地址
                $val['img'] = $imgcdn . '/images_fh/ls/idapptemp/' . $val['lottery_id'] . '.png';
            }
            $tmp[] = $val;
        }
        unset($lottery);
        return $tmp;

    }

    /**
     * author snow
     * 获取展示数据
     */
    public function showDatas()
    {
        $result = [
            'deposits' => $this->_getCountRechargeData('deposits'),//>>存款
            'withdraws' => $this->_getCountRechargeData('withdraws'),//>>提款
            'onLineUserNumber' => $this->_getOnlineUserNumber(),//>>在线人数
            'depositsAmount' => $this->_getComparedData(),//>>获取充值金额比例数据
            'depositsType' => $this->_getRechargeData(),//>>获取充值方式比例数据
        ];
        //>>返回给前端
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $result);

    }

    /**
     * author snow
     * 获取充值金额比例数据
     * @return array
     */
    private function _getComparedData()
    {
        return $this->cutRedisDatabase(function () {
            //>>生成7天前时间
            $date = $this->_getDateAgo(7);
            $cacheKey = '_getComparedData' . $date;

            if (($tmpData = json_decode($GLOBALS['redis']->get($cacheKey))) === null) {
                //>>没有获取到数据,从数据库读取
                //>>获取充值金额比例数据
                $sql = <<<SQL
SELECT a.number1,b.number2,c.number3,d.number4,e.number5 FROM
(SELECT  COUNT(*) AS number1,'{$date}' AS create_time FROM deposits WHERE amount < 20000 AND  create_time > '{$date}') AS a
LEFT JOIN 
(SELECT COUNT(*) AS number2,'{$date}' AS create_time FROM deposits WHERE amount < 50000  AND amount > 20000 AND  create_time > '{$date}') AS b ON a.create_time = b.create_time
LEFT JOIN 
(SELECT COUNT(*) AS number3,'{$date}' AS create_time FROM deposits WHERE amount < 100000  AND amount > 50000 AND  create_time > '{$date}') AS c ON a.create_time = c.create_time
LEFT JOIN 
(SELECT COUNT(*) AS number4,'{$date}' AS create_time FROM deposits WHERE amount < 150000  AND amount > 100000 AND  create_time > '{$date}') AS d ON a.create_time = d.create_time
LEFT JOIN 
(SELECT COUNT(*) AS number5,'{$date}' AS create_time FROM deposits WHERE amount > 1500000  AND  create_time > '{$date}') AS e ON a.create_time = e.create_time
		 
SQL;
                //>>为了使数据好看每个最小10%
                $result = $GLOBALS['db']->getRow($sql);

                $tmp = [];
                $sum = 0;
                for ($i = 1; $i <= 5; ++$i) {
                    $tmp['key' . $i] = $result['number' . $i];
                    $sum += $result['number' . $i];
                }
                $tmpData = [];
                foreach ($tmp as $k => $v) {
                    //>>得到每个的百分比
                    $tmpData[] = [
                        'percentage' => round(((0.1 + ($sum == 0 ? 0 : ($v / $sum / 2))) * 100), 2)
                    ];
                }
                unset($tmp);
                //>>写缓存 存入json 数据
                //>>设置过期时间
                $expire = strtotime(date('Y-m-d 23:59:59')) - time();
                $GLOBALS['redis']->setex($cacheKey, $expire, json_encode($tmpData));
            }
            return $tmpData;
        });
    }

    /**
     * author snow
     * 获取充值方式比例数据
     */
    private function _getRechargeData()
    {
        return $this->cutRedisDatabase(function () {
            $type = [
                1 => '银行卡收款',
                2 => '线上支付',
                3 => '扫码支付',
                4 => '微信收款',
                5 => '支付宝收款',
            ];
            //>>只统计15天之内的数据
            $date = $this->_getDateAgo(15);
            $cacheKey = '_getRechargeData' . $date;

            if (($result = json_decode($GLOBALS['redis']->get($cacheKey))) === null) {
                //>>设计查询数据 ,只查询这几种类型
                $sql = <<<SQL
SELECT SUM(d.amount) AS sum_amount,c.usage FROM deposits AS d,cards AS c WHERE d.deposit_card_id = c.card_id AND  c.usage <= 5 AND create_time > '{$date}' GROUP BY c.usage
SQL;
                $result = $GLOBALS['db']->getAll($sql);
                $tmpSum = 0;
                //>>得到总和
                foreach ($result as $key => $val) {
                    $tmpSum += $val['sum_amount'];
                }


                //>>得到每种类型所占百分比
                foreach ($result as $key => $val) {
                    //>>为了数据好看,最少10%
                    $result[$key]['percentage'] = round(((0.1 + $val['sum_amount'] / $tmpSum / 2) * 100), 2);
                    //>>添加类型说明
                    $result[$key]['info'] = isset($type[$val['usage']]) ? $type[$val['usage']] : '';
                }
                //>>写缓存 存入json 数据
                //>>设置过期时间
                $expire = strtotime(date('Y-m-d 23:59:59')) - time();
                $GLOBALS['redis']->setex($cacheKey, $expire, json_encode($result));
            }
            return $result;
        });

    }


    /**
     * author snow
     * 获取15天之内的在线人数
     */
    private function _getOnlineUserNumber()
    {
        //>>获取15天之前的日期
        return $this->cutRedisDatabase(function () {
            $keys = $this->_createDateArray(15);
            //>>从redis 中获取数据
            $userNumber = [];
            $redisKey = 'onLineUserNumber';
            foreach ($keys as $val) {
                $cacheKey = $redisKey . $val;
                if (($userNumber[$val] = $GLOBALS['redis']->get($cacheKey)) === false) {
                    //>>如果没有数据.生成一条数据,写入
                    $number = $this->_getONline();
                    $userNumber[$val] = $number;
                    $expire = 17 * 24 * 3600;  //>>缓存时间
                    $GLOBALS['redis']->setex($cacheKey, $expire, $number);
                }
            }
            $tmp = [];
            foreach ($userNumber as $key => $value) {
                $tmp[] = ['date' => $key, 'number' => $value];
            }
            $tmp[] = ['date' => date('Y-m-d'), 'number' => $this->_getONline()];

            return $tmp;
        });

    }

    /**
     * author snow
     * 生成键名
     * @param $number int  生成多少天之前的  多少天
     * @return array
     */
    private function _createDateArray($number)
    {
        //>>生成键名
        $keys = [];
        for ($i = $number; $i > 0; --$i) {
            $keys[] = $this->_getDateAgo($i);
        }
        return $keys;
    }

    /**
     * author snow
     * 创建存款默认数组   不包含查询当天数据
     * @param $number
     * @return array
     */
    private function _createDepositArray($number)
    {
        $keys = [];
        for ($i = $number; $i > 0; --$i) {
            $keys[] = ['create_time' => $this->_getDateAgo($i), 'sum_amount' => 0];
        }
        return $keys;
    }

    /**
     * author snow
     * 获取传入天数之前的日期
     * @param $number
     * @return false|string
     */
    private function _getDateAgo($number)
    {
        return date('Y-m-d', time() - $number * 24 * 3600);
    }

    /**
     * author snow
     * 获取7天之内的每天的存款/提款总额
     * @param $table
     * @return array
     */
    private function _getCountRechargeData($table)
    {

        return $this->cutRedisDatabase(function () use ($table) {
            $day_number = 7; //>>查询的天数
            $tmpArray = $this->_createDepositArray($day_number);
            foreach ($tmpArray as $key => $value) {
                $redisKey = $table . '_EveryDayData';
                $cacheKey = $table . '_sumAmount_' . $key;
                //>>从缓存读取数据
                $expire = 15 * 24 * 3600;
                $tmpArray[$key]['sum_amount'] = redisGet($redisKey . $cacheKey, function () use ($table, $key) {
                    //>>如果没有缓存  从数据库读取数据
                    $startTime = $key . ' 00:00:00';
                    $endTime = $key . ' 23:59:59';
                    //>>从数据库查询出数据
                    $sql = <<<SQL
SELECT SUM(amount) AS sum_amount FROM {$table} 
WHERE create_time > '{$startTime}' AND create_time < '{$endTime}'
SQL;
                    $dayResult = $GLOBALS['db']->getRow($sql);
                    if (is_array($dayResult) && !empty($dayResult)) {
                        //>>加上一个固定值,让数据好看.
                        return $dayResult['sum_amount'] += self::ADD_DEPOSITS_OR_WITHDRAW_NUMBER + rand(-100000, 100000);

                    }
                    return false;
                }, $expire);
            }

            return $tmpArray;
        });

    }

    /**
     * author snow
     * 生成在线人数
     * 这就是个坑
     * @return int
     */
    private function _getONline()
    {
        //>>1从redis 获取在线人数 先加上一个常量
        $online = new onlineUser();
        $number = $online->countOnline();

        //>>这个里面的方法切换了redis 库 .需要切换到app库 天坑
        $this->selectRedisApp();
        return (float)($number + rand(1, 100) / 100 * self::ADD_ONLINE_USER_NUMBER + self::ADD_ONLINE_USER_NUMBER);
    }
    private function _handle_version($type,$lastOne){
        $appVersion = M('appVersion');
        $res=$this->cutRedisDatabase(function()use($lastOne,$type){
            if($type==1) {
                if ($lastOne) $GLOBALS['redis']->hget('appset', 'iosNewVersion');
                else $GLOBALS['redis']->hget('appset', 'iosVersions');
            }else{
                if($lastOne)$GLOBALS['redis']->hget('appset', 'andrNewVersion');
                else $GLOBALS['redis']->hget('appset', 'andrVersions');
            }
        });
        if(empty($res)){
            if($type==1) {
                $appVersion->field('ios_version,ios_describe,ios_update')->where("ios_version !='' and status = 1")->order('create_time desc');
            }else{
                $appVersion->field('andr_version,andr_describe,andr_update')->where("andr_version !='' and status = 1")->order('create_time desc');
            }
            if($lastOne){
                $res = $appVersion->find();
            }else{
                $res = $appVersion->select();
            }
            $this->cutRedisDatabase(function()use($lastOne,$res,$type){
                if($type==1){
                    if($lastOne)$GLOBALS['redis']->hset('appset', 'iosNewVersion',serialize($res));
                    else $GLOBALS['redis']->hset('appset', 'iosVersions',serialize($res));
                }else{
                    if($lastOne)$GLOBALS['redis']->hset('appset', 'andrNewVersion',serialize($res));
                    else $GLOBALS['redis']->hset('appset', 'andrVersions',serialize($res));
                }
            });
        }else $res=unserialize($res);
        return $res;
    }

    /**
     * 获取最新版本
     * @param $type 1:ios 2:安卓
     * @param $lastOne 是否只要最新一条 1:是 0:否
     */
    public function getVersion(){
        $type=$this->request->getGet('type','intval',1);
        $lastOne=$this->request->getGet('lastOne','intval',1);
        if($type==1){
            $download_url=config::getConfig('app_ios_download_url','');
        }else{
            $download_url=config::getConfig('app_andr_download_url','');
        }
        $this->showMsg(0,mobileErrorCode::RETURN_SUCCESS,['download'=>$download_url,'version'=>$this->_handle_version($type,$lastOne)]);
    }

    public function testJpush(){
        require_once FRAMEWORK_PATH . 'library/vendor/autoload.php';
        $client= new JPush\Client('ec34c1c62c8a577f2a98d04f','3cad8ed2a0d4ce9cf0889e05');
        $pusher = $client->push();
        $pusher->setPlatform('all');
//        $pusher->addAllAudience();
        $pusher->addTag(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20']);
        $pusher->addalias(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20']);
        $pusher->setNotificationAlert('test1, JPush');
        try {
            $pusher->send();
            print '发送完成';
        } catch (\JPush\Exceptions\JPushException $e) {
            // try something else here
            var_dump($e->getMessage());
            exit;
        }
//
//// 完整的推送示例
//        try {
//            $response = $client->push()
//                ->setPlatform(array('ios', 'android'))
//                // 一般情况下，关于 audience 的设置只需要调用 addAlias、addTag、addTagAnd  或 addRegistrationId
//                // 这四个方法中的某一个即可，这里仅作为示例，当然全部调用也可以，多项 audience 调用表示其结果的交集
//                // 即是说一般情况下，下面三个方法和没有列出的 addTagAnd 一共四个，只适用一个便可满足大多数的场景需求
//                ->addAllAudience()
////                 ->addAlias('5513')
//                //->addTag(array('tag1', 'tag2'))
//                // ->addRegistrationId($registration_id)
//
//                ->setNotificationAlert('Hi, JPush')
//                ->iosNotification([
//                    "title"=> "此app暂时下线,敬请期待未来",
//                    "subtitle"=> "最新通知",
//                      "body"=> "你个扑街佬你个扑街佬你个扑街佬你个扑街佬你个扑街佬你个扑街佬你个扑街佬,ybqwudcu9wobjcxuqwnbyvcqw华晨宇不急回去吃完VB聚会确定不叫韩潜处于底部奖期预测不进去成都不计划参与都不睡觉星期越长大错误vqdbu"
//                    ], array(
//                    'sound' => 'sound.caf',
//                    // 'badge' => '+1',
//                     'content-available' => true,
//                     'mutable-content' => true,
//                    'category' => 'jiguang',
//                    'extras' => array(
//                        'key' => 'iosNotification',
//                        'jiguang'
//                    ),
//                ))
////                ->androidNotification('Hello Android', array(
////                    'title' => 'hello jpush',
////                    // 'builder_id' => 2,
////                    'extras' => array(
////                        'key' => 'value',
////                        'jiguang'
////                    ),
////                ))
//                ->message('message content', array(
//                    'title' => 'hello jpush',
//                    // 'content_type' => 'text',
//                    'extras' => array(
//                        'key' => 'message',
//                        'jiguang'
//                    ),
//                ))
//                ->options(array(
//                    // sendno: 表示推送序号，纯粹用来作为 API 调用标识，
//                    // API 返回时被原样返回，以方便 API 调用方匹配请求与返回
//                    // 这里设置为 100 仅作为示例
//
//                     'sendno' => 'test_100',
//
//                    // time_to_live: 表示离线消息保留时长(秒)，
//                    // 推送当前用户不在线时，为该用户保留多长时间的离线消息，以便其上线时再次推送。
//                    // 默认 86400 （1 天），最长 10 天。设置为 0 表示不保留离线消息，只有推送当前在线的用户可以收到
//                    // 这里设置为 1 仅作为示例
//
//                     'time_to_live' => 86400,
//
//                    // apns_production: 表示APNs是否生产环境，
//                    // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境
//
//                    'apns_production' => false,
//
//                    // big_push_duration: 表示定速推送时长(分钟)，又名缓慢推送，把原本尽可能快的推送速度，降低下来，
//                    // 给定的 n 分钟内，均匀地向这次推送的目标用户推送。最大值为1400.未设置则不是定速推送
//                    // 这里设置为 1 仅作为示例
//
//                    // 'big_push_duration' => 1
//                ))
//                ->send();
////            print_r($response);
//            print '发送完成';
//
//        } catch (\JPush\Exceptions\APIConnectionException $e) {
//            // try something here
//            print $e;
//        } catch (\JPush\Exceptions\APIRequestException $e) {
//            // try something here
//            print $e;
//        }
//        dd($this->pushAll('dwscsd','wdcwqdwc',['shu'=>'dzdxszs']));
    }

    public function pushAll($title, $alert,array $extras, $type = 1)
    {
        require_once FRAMEWORK_PATH . 'library/vendor/autoload.php';
        $client = new JPush\Client('ec34c1c62c8a577f2a98d04f', '3cad8ed2a0d4ce9cf0889e05');
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
                    "title" => "版本更新",
                    "subtitle" => $title,
                    "body" => $alert
                ], array(
                    'sound' => 'sound.caf',
                    'content-available' => true,
                    'mutable-content' => true,
                    'category' => 'jiguang',
                    'extras' => $extras,
                ));
            } elseif ($type == 2) {
                $pusher->androidNotification($alert, array(
                    'title' => $title,
                    'extras' => $extras,
                ));
            } else {
                $pusher->iosNotification([
                    "title" => "版本更新",
                    "subtitle" => $title,
                    "body" => $alert
                ], array(
                    'sound' => 'sound.caf',
                    'content-available' => true,
                    'mutable-content' => true,
                    'category' => 'jiguang',
                    'extras' => $extras,
                ))->androidNotification($alert, array(
                    'title' => $title,
                    'extras' => $extras,
                ));
            }

            $pusher->options(array(
                'time_to_live' => 86400,
            ))
                ->send();
            return true;

        } catch (\JPush\Exceptions\APIConnectionException $e) {
            return $e->getMessage();
        } catch (\JPush\Exceptions\APIRequestException $e) {
            return $e->getMessage();
        }
    }
}
