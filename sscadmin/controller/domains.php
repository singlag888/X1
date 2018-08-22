<?php

if (!defined('IN_LIGHT')) {
	die('KCAH');
}

/**
 * 控制器：域名管理
 */
class domainsController extends sscAdminController {
	static $pageNum = DEFAULT_PER_PAGE;
	public $titles = array(
			'domainsList' => '域名绑定',
			'domainsUpdata' => '修改绑定',
			'domainsSelect' => '绑定查询',
			'domainsDel' => '解除绑定',
			'domainsAdd' => '添加绑定',
            'marketLink'=>'推广码',
            'regChild'=>'推广码操做',
		);

	
	public function init($init = 0)
	{
		parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
	}
	
	//根据POST条件查询
	public function domainsSelect(){
		$domain = $this->request->getPost('domain', 'trim'); 
		$username = $this->request->getPost('username','trim');
		$nick_name = $this->request->getPost('nick_name','trim');
		$order = $this->request->getPost('order','trim');
		if(!empty($order)){
// 			setcookie("domainsOrder","$order",time()+3600);
// 			$order = $_COOKIE['domainsOrder'];	
			$GLOBALS['SESSION']['domainsOrder'] = $order;
			$order = $GLOBALS['SESSION']['domainsOrder'];
			//dd($order);
			//echo 1;		
		}else{
// 			$order = $_COOKIE['domainsOrder'];
			$order = $GLOBALS['SESSION']['domainsOrder'];
			//echo 2;
		}
		$isCache = NULL ;
//		$offset = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;


        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $domainNumber = domain::getCountNumber();
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/

        self::$view->setVar('pageList', getPageList($domainNumber,self::$pageNum));
		if(!empty($domain) && !empty($username)){
			echo "<script>alert('仅限单条查询,当前查询结果为：域名查询');</script>";
		}
		else if(!empty($domain) && !empty($nick_name)){
			echo "<script>alert('仅限单条查询,当前查询结果为：域名查询');</script>";
		}
		else if (!empty($domain) ){
			echo "<script>alert('仅限单条查询,当前查询结果为：域名查询');</script>";
		}
		else if (!empty($username) && !empty($nick_name)){
			echo "<script>alert('仅限单条查询,当前查询结果为：代理帐号查询');</script>";
		}
		else if (!empty($username) ){
			echo "<script>alert('仅限单条查询,当前查询结果为：代理帐号查询');</script>";
		}
		else if(!empty($nick_name) ){
			echo "<script>alert('仅限单条查询,当前查询结果为：代理昵称查询');</script>";
		}
		if(!empty($domain)){//域名不为空查询
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            $domainsNumber = domain::getDomainNumber($domain);//获取条件查询的数据
            //>>判断输入的页码是否超过最大值.
            $offset = getStartOffset($curPage, $domainsNumber, self::$pageNum);
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            $sel = domain::getDomains($domain,$order,$offset,self::$pageNum);
		    self::$view->setVar('pageList', getPageList($domainsNumber,self::$pageNum));
			self::$view->setVar('userDomains', $sel);
			self::$view->render('user_domains');
			return ;
		}
		if (!empty($username)){//用户名不为空查询
            $domainsNumber = domain::getUsernameNumber($username);
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            //>>判断输入的页码是否超过最大值.
            $offset = getStartOffset($curPage, $domainsNumber, self::$pageNum);
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            $sel = domain::getUsername($username,$order,$offset,self::$pageNum);
			self::$view->setVar('pageList', getPageList($domainsNumber,self::$pageNum));
			self::$view->setVar('userDomains', $sel);
			self::$view->render('user_domains');
			return ;
		}
		if(!empty($nick_name)){//昵称不为空查询
            $domainsNumber = domain::getNickNameNumber($nick_name);
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            //>>判断输入的页码是否超过最大值.
            $offset = getStartOffset($curPage, $domainsNumber, self::$pageNum);
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            $sel = domain::getNickName($nick_name,$order,$offset,self::$pageNum);
			self::$view->setVar('pageList', getPageList($domainsNumber,self::$pageNum));
			self::$view->setVar('userDomains', $sel);
			self::$view->render('user_domains');
			return ;
			
		}

		if (empty($domain) && empty($username) && empty($nick_name)){
			$domain_id = $this->request->getGet('domain_id', 'intval', 0);
			$domainNumber = domain::getCountNumber();
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
            //>>判断输入的页码是否超过最大值.
            $offset = getStartOffset($curPage, $domainNumber, self::$pageNum);
            /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
			$userDomains = domain::getUserDomains( $domain_id,$order,$isCache,$offset,self::$pageNum);
			self::$view->setVar('userDomains', $userDomains);
			self::$view->render('user_domains');
			//dd($order);
			return ;
		}
	}
//    public function domainsSelect(){
//        $domain = $this->request->getPost('domain', 'trim');
//        $username = $this->request->getPost('username','trim');
//        $nick_name = $this->request->getPost('nick_name','trim');
//        $ext_code = $this->request->getPost('ext_code','trim');
//        $order = $this->request->getPost('order','trim');
//        if(!empty($order)){
//// 			setcookie("domainsOrder","$order",time()+3600);
//// 			$order = $_COOKIE['domainsOrder'];
//            $GLOBALS['SESSION']['domainsOrder'] = $order;
//            $order = $GLOBALS['SESSION']['domainsOrder'];
//            //dd($order);
//            //echo 1;
//        }else{
//// 			$order = $_COOKIE['domainsOrder'];
//            $order = $GLOBALS['SESSION']['domainsOrder'];
//            //echo 2;
//        }
//        $isCache = NULL ;
//        $offset = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
//        $domainNumber = domain::getCountNumber();
//        self::$view->setVar('pageList', getPageList($domainNumber,self::$pageNum));
//        if(!empty($domain) && !empty($username)){
//            echo "<script>alert('仅限单条查询,当前查询结果为：域名查询');</script>";
//        }
//        else if(!empty($domain) && !empty($nick_name)){
//            echo "<script>alert('仅限单条查询,当前查询结果为：域名查询');</script>";
//        }
//        else if (!empty($domain) && !empty($ext_code)){
//            echo "<script>alert('仅限单条查询,当前查询结果为：域名查询');</script>";
//        }
//        else if (!empty($username) && !empty($nick_name)){
//            echo "<script>alert('仅限单条查询,当前查询结果为：代理帐号查询');</script>";
//        }
//        else if (!empty($username) && !empty($ext_code)){
//            echo "<script>alert('仅限单条查询,当前查询结果为：代理帐号查询');</script>";
//        }
//        else if(!empty($nick_name) && !empty($ext_code)){
//            echo "<script>alert('仅限单条查询,当前查询结果为：代理昵称查询');</script>";
//        }
//        if(!empty($domain)){//域名不为空查询
//            $sel = domain::getDomains($domain,$order,$offset,self::$pageNum);
//            $domainsNumber = domain::getDomainNumber($domain);//获取条件查询的数据
//            self::$view->setVar('pageList', getPageList($domainsNumber,self::$pageNum));
//            self::$view->setVar('userDomains', $sel);
//            self::$view->render('user_domains');
//            return ;
//        }
//        if (!empty($username)){//用户名不为空查询
//            $sel = domain::getUsername($username,$order,$offset,self::$pageNum);
//            $domainsNumber = domain::getUsernameNumber($username);
//            self::$view->setVar('pageList', getPageList($domainsNumber,self::$pageNum));
//            self::$view->setVar('userDomains', $sel);
//            self::$view->render('user_domains');
//            return ;
//        }
//        if(!empty($nick_name)){//昵称不为空查询
//            $sel = domain::getNickName($nick_name,$order,$offset,self::$pageNum);
//            $domainsNumber = domain::getNickNameNumber($nick_name);
//            self::$view->setVar('pageList', getPageList($domainsNumber,self::$pageNum));
//            self::$view->setVar('userDomains', $sel);
//            self::$view->render('user_domains');
//            return ;
//
//        }
//        if(!empty($ext_code)){//推广码不为空查询
//            $sel = domain::getExtCode($ext_code,$order,$offset,self::$pageNum);
//            $domainsNumber = domain::getExtCodeNumber($ext_code);
//            self::$view->setVar('pageList', getPageList($domainsNumber,self::$pageNum));
//            self::$view->setVar('userDomains', $sel);
//            self::$view->render('user_domains');
//            return ;
//
//        }
//        if (empty($domain) && empty($username) && empty($nick_name) && empty($ext_code)){
//            $domain_id = $this->request->getGet('domain_id', 'intval', 0);
//            $domainNumber = domain::getCountNumber();
//            $userDomains = domain::getUserDomains( $domain_id,$order,$isCache,$offset,self::$pageNum);
//            self::$view->setVar('userDomains', $userDomains);
//            self::$view->render('user_domains');
//            //dd($order);
//            return ;
//        }
//    }
    public function domainsAdd(){//绑定域名
        $locations = array(0 => array('title'=> '返回列表', 'url'=> url('domains','domainsList')));
        $domain = $this->request->getPost('domain', 'trim');
        $username = $this->request->getPost('username','trim');
        $nick_name = $this->request->getPost('nick_name','trim');
        $domainNum = strlen($domain);
        $nick_nameNum = strlen($nick_name);
        if ($domainNum > 50){
            showMSG("域名长度超过50字数限制", 1,$locations);
        }elseif($nick_nameNum > 15){
            showMSG("昵称长度超过15字数限制", 1,$locations);
        }

        if(!empty($domain) && !empty($username) && !empty($nick_name)){
            $user_id = domain::getUdId($username);
            $domain_id = domain::getDomainId($domain);
            $top_id = domain::getTopId($username);
            $domainSql = domain::domainsAdd($top_id, $domain_id, $username);
            $usersSql = domain::usersUpdata($nick_name, $username);
            if (!empty($domainSql) && isset($usersSql)){
                showMSG("绑定成功",0 ,$locations);
            }else {
                showMSG("绑定失败", 1,$locations);
            }
        }else{
            showMSG("绑定失败,参数不全",1,$locations);
        }


    }
//	public function domainsAdd(){//绑定域名
//		$locations = array(0 => array('title'=> '返回列表', 'url'=> url('domains','domainsList')));
//		$domain = $this->request->getPost('domain', 'trim');
//		$username = $this->request->getPost('username','trim');
//		$nick_name = $this->request->getPost('nick_name','trim');
//		$ext_code = $this->request->getPost('ext_code','trim');
//		$domainNum = strlen($domain);
//		$nick_nameNum = strlen($nick_name);
//		$ext_codeNum = strlen($ext_code);
//		if ($domainNum > 50){
//			showMSG("域名长度超过50字数限制", 1,$locations);
//		}elseif($nick_nameNum > 15){
//			showMSG("昵称长度超过15字数限制", 1,$locations);
//		}elseif($ext_codeNum > 8){
//			showMSG("推广码长度超过8字数限制", 1,$locations);
//		}
//		$checkExtCode = domain::inspectExtCode($ext_code);
//		if(!empty($checkExtCode['ext_code'])){
//			showMSG("该推广码已存在");
//		}
//		if(!empty($domain) && !empty($username) && !empty($nick_name) && !empty($ext_code)){
//			$user_id = domain::getUdId($username);
//			$domain_id = domain::getDomainId($domain);
//			$top_id = domain::getTopId($username);
//			$domainSql = domain::domainsAdd($top_id, $domain_id, $username);
//			$usersSql = domain::usersUpdata($nick_name, $ext_code, $username);
//			if (!empty($domainSql) && isset($usersSql)){
//				showMSG("绑定成功",0 ,$locations);
//			}else {
//				showMSG("绑定失败", 1,$locations);
//			}
//		}else{
//			showMSG("绑定失败,参数不全",1,$locations);
//		}
//
//
//	}
    public function marketLink()
    {
        $user_id = $this->request->getGet('user_id', 'intval', 0);
        $host = $this->request->getGet('host', 'trim', '');
        $locations = array(0 => array('title' => '返回公告列表', 'url' => url('domains', 'domainsList')));
        if($user_id == 0)
        {
            showMSG("参数错误!",1,$locations);
        }
        $user = users::getItem($user_id);
        $aPrizeMode = userRebates::addSubPrizeModes($user);
        $marketlinklist=marketLink::getItemsByCond("user_id = $user_id");
        $arr=[];
        if($marketlinklist)
        {
            foreach ($marketlinklist as $k=>$v)
            {
                $arr[$v['prize_mode']]=$v;
            }
        }


        self::$view->setVar('aPrizeMode', $aPrizeMode);
        self::$view->setVar('user_id', $user_id);
        self::$view->setVar('host', $host);
        self::$view->setVar('marketlinklist', $arr);
        self::$view->render('marketlinklist');
     }
    /**
     * 下级开户
     * 1.判断其group_id
     * 2.检查返点值（奖金）是否正确
     * 3.看users表还有哪些需要的字段
     */
    public function regChild()
    {
            $key= $this->request->getPost('key', 'trim','');

            if($key)
            {
                $market_code = $this->request->getPost('market_code', 'trim','');
                if(!preg_match('`^[0-9a-zA-Z]{3,10}$`',$market_code))
                {
                    $result['errno'] = 0;
                    $result['msg'] = '推广码格式不正确!';
                    die(json_encode($result));
                }
                $prize_mode = $this->request->getPost('prize_mode', 'trim','');
                $user_id = $this->request->getPost('user_id', 'intval',0);
                $info = marketLink::getItemByCond(" market_code = '{$market_code}'",'market_code');
                if($info)
                {
                    $result['errno'] = 0;
                    $result['msg'] = '推广码已经存在!';
                    die(json_encode($result));
                }
                //判断进行的操作是增加还是修改
                $info = marketLink::getItemByCond(" prize_mode = '{$prize_mode}' AND user_id={$user_id}", 'prize_mode');
                $key = empty($info) ? 'ad' : 'up';
                if($key === 'ad')
                {
                    $data=array(
                        'user_id'=>$user_id,
                        'market_code'=>$market_code,
                        'prize_mode'=>$prize_mode,
                    );
                    $res=marketLink::addItem($data);
                    if(!$res)
                    {
                        $result['errno'] = 0;
                        $result['msg'] = '添加失败!';

                    }else{
                        $result['errno'] = 1;
                        $result['msg'] = '添加成功!';
                    }
                    die(json_encode($result));
                }//添加
                if($key === 'up')
                {
                    $info = marketLink::getItemByCond(" prize_mode = '{$prize_mode}' and user_id = '{$user_id}'",'link');
                    $url = '';
                    if(isset($info['link']) && !empty($info['link']))
                    {
                       $arrTmp = explode('=',$info['link']);
                       $arrTmp[1]=$market_code;
                       $url=implode('=',$arrTmp);
                    }
                    $data=array(
                        'user_id'=>$user_id,
                        'market_code'=>$market_code,
                        'prize_mode'=>$prize_mode,
                    );
                    if($url)
                    {
                        $data['link'] = $url;
                    }
                    $res=marketLink::updateItem($data,['user_id'=>$user_id,'prize_mode'=>$prize_mode]);

                    if(!$res)
                    {
                        $result['errno'] = 0;
                        $result['msg'] = '修改失败';
                    }else{
                        $result['errno'] = 1;
                        $result['msg'] = '修改成功!';
                    }
                    die(json_encode($result));
                }//修改
            }
        }

	//域名推广列表
	public function domainsList(){
		$order = "asc" ;
		$isCache = NULL ;
//		$offset = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
		$domain_id = $this->request->getGet('domain_id', 'intval', 0);
        $domainNumber = domain::getCountNumber();

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $offset = getStartOffset($curPage, $domainNumber, self::$pageNum);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $userDomains = domain::getUserDomains( $domain_id,$order,$isCache,$offset,self::$pageNum);

        self::$view->setVar('domainNumber', $domainNumber);
		self::$view->setVar('pageList', getPageList($domainNumber,self::$pageNum));
		self::$view->setVar('userDomains', $userDomains);
		self::$view->render('user_domains');
	}
	//跳转修改数据
	public function domainsUpdata(){

		$locations = array(0 => array('title' => '返回公告列表', 'url' => url('domains', 'domainsList')));
		$ud_id = $this->request->getGet('ud_id', 'intval', 0);
        //$ext_code = $this->request->getPost('ext_code','trim');
		$userDomains = domain::getUdidDomainC($ud_id);
		//dd($userDomains);
		self::$view->setVar('userDomains',$userDomains);
		$domain = $this->request->getPost('domain','trim');
		$nick_name = $this->request->getPost('nick_name','trim');
		$username = $this->request->getPost('username','trim');
		$udId = $this->request->getPost('ud_id','trim');
		$top_id = domain::getTopId($username);

		if (!empty($username) && !empty($udId)){
			if(empty($nick_name)){
				showMSG("昵称不能为空",1);
			}
//			if(empty($ext_code)){
//				showMSG("推广码不能为空",1,$locations);
//			}
//			if(@$userDomains['0']['ext_code']!= ""){
//                showMSG("推广码已存在",1,$locations);
//            }
            /*if(!preg_match("/[a-zA-Z0-9]{6,8}/", $ext_code)){
                showMSG("推广码格式不正确",1,$locations);
            }*/
			$domain_id = domain::getDomainsId($udId);
			$userData = domain::usersUpdata($nick_name, $username);
			$domainUpdata = domain::userDomainsUpdata($username);
			if (!empty($userData)){
				showMSG("修改成功", 0,$locations);
			}else{
				showMSG("没有更新的数据", 0,$locations);
			}
		}
		self::$view->render('user_domainsUpdata');
	}
	
	public function domainsDel(){//清除绑定
		$domain = $this->request->getGet('ud_id', 'trim');
		$locations = array(0 => array('title' => '返回公告列表', 'url' => url('domains', 'domainsList')));
		if (!$domain) {
			showMsg("参数无效", 1, $locations);
		}
		if (!domain::deleteUserDomain($domain)) {
			showMsg("解绑域名失败", 0, $locations);
		}
		showMsg("解绑域名成功", 0, $locations);
		
	}

}
