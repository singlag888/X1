<?php
//================ 以下通常不用修改 ================//
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') . DIRECTORY_SEPARATOR); //根路径
define('FRAMEWORK_PATH', realpath(ROOT_PATH . 'framework') . DIRECTORY_SEPARATOR); //框架路径
define('PROJECT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR); //项目路径
define('LOG_PATH', PROJECT_PATH . 'logs' . DIRECTORY_SEPARATOR); //日志路径

//================ 以下可能需要改动 ================//
//定义环境 1表示开发机，2表示测试机，3表示生产机
define('RUN_ENV', 1);
define('FORCE_DISPLAY_ERROR', false); //是否强制在页面显示错误 默认false不在页面显示错误

//项目名
define('PROJECT', 'ssc');

//任何一个项目，均应有前台应用和后台管理
define('FORE_PATH', realpath(ROOT_PATH . 'sscmobile') . DIRECTORY_SEPARATOR); //前台应用
define('ADMIN_PATH', realpath(ROOT_PATH . 'sscadmin') . DIRECTORY_SEPARATOR); //后台管理

//====================全局配置在此添加====================//
define('DEFAULT_PER_PAGE', 15); // 每页显示记录数 全局常量
define('REBATE_PRECISION', 3); //返点小数点精度
define('PRIZE_PRECISION', 4); //奖金小数点精度


//================ 邪恶的分割线 ================//

//library中的所有文件需在此指定 不然找不到！
$GLOBALS['AUTOLOAD_CLASSES']['sscController'] = PROJECT_PATH . 'library/sscController.class.php';
//系统会自动找到所需的类，如果显式声明有助于加快类加载速度
$GLOBALS['AUTOLOAD_CLASSES']['autoDeposits'] = ADMIN_PATH . 'model/autoDeposits.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['articles'] = ADMIN_PATH . 'model/articles.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['cards'] = ADMIN_PATH . 'model/cards.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['cardOrders'] = ADMIN_PATH . 'model/cardOrders.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['config'] = ADMIN_PATH . 'model/config.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['deposits'] = ADMIN_PATH . 'model/deposits.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['domains'] = ADMIN_PATH . 'model/domains.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['game'] = ADMIN_PATH . 'model/game.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['issues'] = ADMIN_PATH . 'model/issues.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['lottery'] = ADMIN_PATH . 'model/lottery.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['messages'] = ADMIN_PATH . 'model/messages.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['methods'] = ADMIN_PATH . 'model/methods.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['notices'] = ADMIN_PATH . 'model/notices.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['orders'] = ADMIN_PATH . 'model/orders.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['prizes'] = ADMIN_PATH . 'model/prizes.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['projects'] = ADMIN_PATH . 'model/projects.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['traces'] = ADMIN_PATH . 'model/traces.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['promos'] = ADMIN_PATH . 'model/promos.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userBindCards'] = ADMIN_PATH . 'model/userBindCards.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userDiffRebates'] = ADMIN_PATH . 'model/userDiffRebates.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGroups'] = ADMIN_PATH . 'model/userGroups.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userLogs'] = ADMIN_PATH . 'model/userLogs.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userMenus'] = ADMIN_PATH . 'model/userMenus.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userRebates'] = ADMIN_PATH . 'model/userRebates.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['users'] = ADMIN_PATH . 'model/users.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['withdraws'] = ADMIN_PATH . 'model/withdraws.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['PT'] = ADMIN_PATH . 'library/PT.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsControl'] = ADMIN_PATH . 'model/usergift/userGiftsControl.class.php';

try {
	require FRAMEWORK_PATH . '/light.php';
} catch (exception2 $e) {
		exception_handler($e);
}

dump('ddd'); //这里执行不到，上面已经退出

/**
 *
 * @param type $msg
 * @param type $link
 * @param type $style 1先alert()再显示目标页面（如果指定了的话），如果2先显示出目标页面再弹出alert()，这样客户看到alert()的时候页面不是空白，是有内容的，体验度不一样
 */
function showAlert($msg, $link = '', $style = 1) {
	echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
	if ($link != '' && $style == 2) {
		$str = "<script>window.location.href='$link';alert('" . addslashes($msg) . "');</script>";
		echo $str;
	} else {
		$str = "<script>alert('" . addslashes($msg) . "');";
		if ($link) {
			$str .= "window.location.href='$link';";
		} else {
			$str .= "history.back();";
		}
		$str .= "</script>";
		echo $str;
	}
	exit;
}

function showMsg($msg, $msgType = 0, $links = array(), $target = 'self' , $diy = false) {

	if (empty($links)) {
		//        $links[0]['title'] = '返回上一页';
		//        $links[0]['url'] = 'javascript:history.go(-1)';
		//        $links[0]['target'] = $target;
	} else {
		foreach ($links as &$v) {
			if (empty($v['url'])) {
				$v['url'] = "index.jsp?a=welcome";
			}
			if (empty($v['title'])) {
				$v['title'] = "返回上一页";
			}
			if (empty($v['target'])) {
				$v['target'] = $target;
			}
		}
	}

	if (RUN_ENV == 1) {
		$seconds = 86400;
	} elseif (RUN_ENV == 2) {
		$seconds = 7200;
	} else {
		$seconds = 5;
	}
	$view = new view($GLOBALS['templateDirectory']);
	$view->setVar('ur_here', '系统信息');
	$view->setVar('auto_redirection', '系统将在 <span id="spanSeconds">' . $seconds . '</span> 秒后自动跳转到第一个链接。');
	//默认风格

	$view->setVar('styleDir', $GLOBALS['REQUEST']->getCookie('styleDir', 'string', 'blue|dark'));
	$view->setVar('msg_detail', $msg);
	$view->setVar('diy', $diy);
	$view->setVar('msg_type', $msgType);
	$view->setVar('links', $links);
	$view->render('message');
	EXIT;
}

?>