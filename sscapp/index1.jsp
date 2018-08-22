<?php
$v=!empty($_GET['v'])?(int)$_GET['v']:0;
if($v<1||$v>2)$v=1;

/*
if(isset($_GET['sid'])) {
	$_COOKIE['sscSESSID']=$_GET['sid'];
	unset($_GET['sid']);
}
if(isset($_GET['pid'])) {
	$_COOKIE['PHPSESSID']=$_GET['pid'];
	unset($_GET['pid']);
}
*/

function showMsg($errno, $errstr, $data = '')
{
    $result = array(
        'errno' => strval($errno),
        'errstr' => $errstr,
    );
    if(isset($_GET['is_wap'])&&$_GET['is_wap']==1) {
        die(base64_encode(json_encode($result)));
    }
    die(json_encode($result));
}
//================ 以下通常不用修改 ================//
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') . DIRECTORY_SEPARATOR);    //根路径
define('FRAMEWORK_PATH', realpath(ROOT_PATH . 'framework') . DIRECTORY_SEPARATOR);  //框架路径
define('PROJECT_PATH', dirname(__FILE__) .'/versionControl/v'.$v .DIRECTORY_SEPARATOR);  //项目路径
define('LOG_PATH', PROJECT_PATH . 'logs' . DIRECTORY_SEPARATOR); //日志路径
//================ 以下可能需要改动 ================//
//定义环境 1表示开发机，2表示测试机，3表示生产机
define('RUN_ENV', 1);
define('FORCE_DISPLAY_ERROR', false);   //是否强制在页面显示错误 默认false不在页面显示错误

//项目名
define('PROJECT', 'ssc');

if(isset($_COOKIE[PROJECT.'SESSID']))unset($_COOKIE[PROJECT.'SESSID']);//清除原始cookie的sscSESSID数据

//任何一个项目，均应有前台应用和后台管理
define('FORE_PATH', realpath(ROOT_PATH . 'sscapp') . DIRECTORY_SEPARATOR);//前台应用
define('ADMIN_PATH', realpath(ROOT_PATH . 'sscadmin') . DIRECTORY_SEPARATOR);  //后台管理

//====================全局配置在此添加====================//
define('DEFAULT_PER_PAGE', 20);     // 每页显示记录数 全局常量
define('DEFAULT_MAX_PAGELIMIT', 100);     // 每页显示记录数 全局常量
define('REBATE_PRECISION', 3);      //返点小数点精度
define('PRIZE_PRECISION', 4);       //奖金小数点精度

//================ 邪恶的分割线 ================//

//library中的所有文件需在此指定 不然找不到！
$GLOBALS['AUTOLOAD_CLASSES']['sscappController'] = FORE_PATH . 'library/sscappController.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['mobileErrorCode'] =  FORE_PATH . 'library/mobileErrorCode.class.php';
//系统会自动找到所需的类，如果显式声明有助于加快类加载速度
$GLOBALS['AUTOLOAD_CLASSES']['autoDeposits']= FORE_PATH . 'model/autoDeposits.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['articles']    = FORE_PATH . 'model/articles.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['cards']       = FORE_PATH . 'model/cards.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['cardOrders']  = FORE_PATH . 'model/cardOrders.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['config']      = FORE_PATH . 'model/config.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['deposits']    = FORE_PATH . 'model/deposits.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['domains']     = FORE_PATH . 'model/domains.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['game']        = FORE_PATH . 'model/game.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['issues']      = FORE_PATH . 'model/issues.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['lottery']     = FORE_PATH . 'model/lottery.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['messages']    = FORE_PATH . 'model/messages.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['methods']     = FORE_PATH . 'model/methods.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['notices']     = FORE_PATH . 'model/notices.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['orders']      = FORE_PATH . 'model/orders.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['prizes']      = FORE_PATH . 'model/prizes.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['projects']    = FORE_PATH . 'model/projects.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['traces']      = FORE_PATH . 'model/traces.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['promos']      = FORE_PATH . 'model/promos.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userBindCards']= FORE_PATH . 'model/userBindCards.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userDiffRebates']= FORE_PATH . 'model/userDiffRebates.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGroups']  = FORE_PATH . 'model/userGroups.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userLogs']    = FORE_PATH . 'model/userLogs.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userMenus']   = FORE_PATH . 'model/userMenus.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userRebates'] = FORE_PATH . 'model/userRebates.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['users']       = FORE_PATH . 'model/users.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['withdraws']   = FORE_PATH . 'model/withdraws.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['PT']  = FORE_PATH . 'library/PT.class.php';
$GLOBALS['AUTOLOAD_CLASSES']['userGiftsControl'] =  FORE_PATH . 'model/usergift/userGiftsControl.class.php';
define('LOG_TAG', 'app'); // 日志标识
try {
    require FRAMEWORK_PATH . '/light.php';
} catch (exception2 $e) {
    ob_start();
    exception_handler($e);
    $text = ob_get_clean();
    $text = str_replace('<br />', PHP_EOL, $text);
    $dateTime = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . PHP_EOL;
    $target = '盘口: ' . (defined('XY_PREFIX') ? XY_PREFIX : '未定义的盘口') . ',发生错误.' . PHP_EOL;
    if (method_exists($e, 'getLogStr')) {
        $logStr = $e->getLogStr();
        $logStr && $logStr .= PHP_EOL;
    } else {
        $logStr = '';
    }

    // 项目路径 测试用这个
    tofile('run_' . LOG_TAG . '_error.log', $dateTime . $target . $logStr . $text, FILE_APPEND);
    // /tmp 下
    // toFileAtTmp('run_' . LOG_TAG . '_error.log', $dateTime . $target . $logStr . $text, FILE_APPEND);

    showMsg($e->getErrno(), $e->getMessages());
}

exit;
?>