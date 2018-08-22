<?php
defined('ATTACT_NUM') or define('ATTACT_NUM', 100);
defined('PC_ATTACT_NUM') or define('PC_ATTACT_NUM', 100);
defined('MOBILE_ATTACT_NUM') or define('MOBILE_ATTACT_NUM', 100);
defined('HTML_VERSION_NUM') or define('HTML_VERSION_NUM', '160309');
defined('REDIS_DB_DEFAULT') or define('REDIS_DB_DEFAULT', 0);
defined('REDIS_DB_SESSION') or define('REDIS_DB_SESSION', 1);
defined('REDIS_DB_BACKSTAGE') or define('REDIS_DB_BACKSTAGE', 2);
defined('REDIS_DB_COMMON_DATA') or define('REDIS_DB_COMMON_DATA', 3);
defined('ATTACT_NUM') or define('ATTACT_NUM', 100);
defined('PC_ATTACT_NUM') or define('PC_ATTACT_NUM', 100);
defined('MOBILE_ATTACT_NUM') or define('MOBILE_ATTACT_NUM', 100);
defined('HTML_VERSION_NUM') or define('HTML_VERSION_NUM', '160310');
defined('CLEAR_CACHE_DIR') or define('CLEAR_CACHE_DIR', '/root/shell/bin/');
defined('REDIS_DB_DEFAULT') or define('REDIS_DB_DEFAULT', 0);
defined('REDIS_DB_SESSION') or define('REDIS_DB_SESSION', 1);
defined('REDIS_DB_BACKSTAGE') or define('REDIS_DB_BACKSTAGE', 2);
defined('REDIS_DB_COMMON_DATA') or define('REDIS_DB_COMMON_DATA', 3);
defined('REDIS_DB_APP') or define('REDIS_DB_APP', 4);
defined('XY_PREFIX') or define('XY_PREFIX', 'xy00001');
defined('MW_SITE_ID') or define('MW_SITE_ID', '10005000');
defined('ADMIN_BACKEND_NAME') or define('ADMIN_BACKEND_NAME','DC管理系统');
defined('CACHE_EXPIRE_LONG') or define('CACHE_EXPIRE_LONG', '2592000');//»定义缓存最长时间
defined('DCSITE_TRANSFER_DATE') or define('DCSITE_TRANSFER_DATE', '2017-11-10');
defined('ONLINE_EXPIRATION_TIME') or define('ONLINE_EXPIRATION_TIME', 1200);
defined('USE_ENCODE') or define('USE_ENCODE', 0);//»定义接口是否加密
//支持的存款银行列表
$GLOBALS['cfg']['bankList'] = array(
	'1' => '工商银行',
	//'100' =>'工行ATM及柜台',
	'2' => '农业银行',
	'3' => '建设银行',
	'4' => '招商银行',
	'5' => '交通银行',
	'6' => '中信银行',
	'7' => '邮政储汇',
	'8' => '中国光大银行',
	'9' => '民生银行',
	'10' => '上海浦东发展银行',
	'11' => '兴业银行',
	'12' => '广发银行',
	'13' => '平安银行',
	'15' => '华夏银行',
	'16' => '东莞银行',
	'17' => '渤海银行',
//    '18'=> '杭州银行',
	'19' => '浙商银行',
	'20' => '北京银行',
	'21' => '广州银行',
	'22' => '中国银行',
	//>100 的属于第三方手工处理状态的账户,关系到充值到限自动切换卡的功能
    '101'=> '通汇',
	'102' => '快捷通',
	'103'=>'微信',
	'109'=>'启付支付宝WAP',
);

//支持的提款银行列表，和上面的区别是客户可以绑定其他众多小银行的卡，方便提款
$GLOBALS['cfg']['withdrawBankList'] = array(
	'1' => '工商银行',
	'2' => '农业银行',
	'3' => '建设银行',
	'4' => '招商银行',
	'5' => '交通银行',
	'6' => '中信银行',
	'7' => '邮政储汇',
	'8' => '中国光大银行',
	'9' => '民生银行',
	'10' => '上海浦东发展银行',
	'11' => '兴业银行',
	'12' => '广发银行',
	'13' => '平安银行',
	'15' => '华夏银行',
	'16' => '东莞银行',
	'17' => '渤海银行',
//    '18'=> '杭州银行',
	'19' => '浙商银行',
	'20' => '北京银行',
	'21' => '广州银行',
	'22' => '中国银行',
);

//开通的彩种业务
$GLOBALS['cfg']['property'] = array(
    '1' => '时时彩',
    '2' => '十一选五',
    '3' => '快三',
    '4' => '快乐扑克',
    '5' => '低频3D',
    '6' => 'pk拾',
    '7' => '六合彩',
    '8' => '双色球',
    '9' => '幸运28'
);

$GLOBALS['cfg']['lottery_type'] = array(
    '1' => '数字类型',
    '2' => '乐透同区型(例如sd11y)',
    '3' => '乐透分区型(例如蓝红球)',
    '4' => '低频3D',
    '5' => '基诺型',
    '6' => '快三型(例如江苏快三)',
    '7' => '快乐扑克(例如山东快乐扑克)',
    '8' => 'pk拾(例如北京pk拾)',
    '9' => '六合彩',
    '10' => 'PC蛋蛋',
);

//支持的模式列表
$GLOBALS['cfg']['modes'] = array(
	'1' => '2元',
	'0.5' => '1元',
	'0.1' => '2角',
	'0.05' => '1角',
	'0.01' => '2分',
	'0.001' => '2厘',
);

//用户级别
$GLOBALS['cfg']['userLevels'] = array(
	'0' => '总代',
	'1' => '一代',
	'2' => '二代',
	'3' => '三代',
	'4' => '四代',
	'5' => '五代',
	'10' => '会员',
);

//用户资金帐变类型
//帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 202撤单取消返点 203撤单手续费 208取消派奖 301返点 302追号返款 303撤单返款 308中奖 320理赔 401投注 402追号
$GLOBALS['cfg']['orderTypes'] = array(
	/* 10x 20x 客户帐变相关的帐变 */
	'101' => '充值',
	'102' => '手续费优惠',
	'103' => '首冲送',
	'104' => '再存优惠',
	'105' => '其他优惠',
	'106' => '提现不符退款',
	'107' => '活动红包',
	'151' => '不活跃下级清理',
	'152' => '下级存款返佣',
	'153' => '代理分红', //特指总代
	'154' => '接收转账', //目前特指一代所得分红
	'155' => '手工加余额', //只允许加
	'161' => '从波音转出',
	'162' => '从休闲游戏转出',
	'201' => '提现',
	'202' => '手工减余额', //只允许减
	'203' => '小额资金清理',
	'204' => '特殊充值扣费',
	'205' => '取消礼品卷',
	'211' => '转入波音',
	'212' => '给下级转账', //注意和153的区别：特指总代给一代的分红，扣钱，153指代理得到的分红
	'213' => '转入休闲游戏',
	//'300' => '特殊金额清理',
	/* 30x 40x 客户游戏相关的帐变 */
	'301' => '投注返点',
	'302' => '下级投注返点',
	'303' => '撤单返款',
	'304' => '追号中止返款', //比如追中即停，后面的几期不再追号，返还游戏，甚至用户可以中途取消追号
	'308' => '中奖',
	'321' => '平台理赔', //因平台故障影响玩家投注，而错过的时间刚好中奖了的
	'401' => '投注', //暂不区分投注和追号
	'411' => '撤消返点',
	'412' => '撤单手续费',
	'413' => '撤消中奖',
	'414' => '追号扣款',
	'415' => '奖池嘉奖',
	'501' => '日流水返佣',
	'502' => '日亏损返佣',
	'503' => '日投注返佣',
	'601' => '注册送',
	'602' => '签到送',
	'603' => '日盈利送',
	'604' => '日亏损送',
);

//客户付款方式
$GLOBALS['cfg']['tradeTypes'] = array(
	'1' => '网转',
	'2' => 'ATM有卡转账',
	'3' => '支付宝转银行卡',
	'4' => '手机网转', //包括门户网站网转
	'5' => 'ATM无卡现存',
	'6' => '柜台汇款',
	'7' => '跨行汇款',
	'8' => '微信转银行卡',
);

$GLOBALS['cfg']['promoTypes'] = array(
//    '1' => '首存优惠',
	//    '2' => '再存优惠',
	'3' => '代理分红',
	'4' => '平台理赔',
	'5' => '下级存款返佣',
	'6' => '活动红包',
	'9' => '其他优惠',
);
