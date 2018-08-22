<?php

// 定义ROOT_PATH,FRAMEWORK_PATH，引入框架基本类库common.lib，
define('IN_LIGHT', true);
//编译文件夹
define('TPL_C_DIR', PROJECT_PATH . 'templates_c/');
//缓存文件夹
define('CACHE_DIR', PROJECT_PATH . 'cache/');
define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);

date_default_timezone_set('Asia/Shanghai');

// 这里不要强制设置了,根据环境不同来在php.ini中配置
// 如果打开,生产环境都要报路径,这种不允许.
// error_reporting(E_ALL);

//正式环境关闭错误显示
if (RUN_ENV < 3) {
    ini_set('display_errors', 'on');
} else {
    if (!defined('FORCE_DISPLAY_ERROR') || !FORCE_DISPLAY_ERROR) {
        ini_set('display_errors', 'on');
    }
}

// 暂时还是由各自index.php来定义吧
// 运行环境，1表示开发机，2表示测试机，3表示生产机，可根据IP来判断
//if (!defined(RUN_ENV)) {
//    if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
//        define('RUN_ENV', 1);
//    }
//    else {
//        define('RUN_ENV', 3);
//    }
//}

define('MICROTIME', microtime(true));
define('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
if (stripos(PHP_OS, 'win') === false) {
    define('TMP_PATH', '/tmp/');
} else {
    if (isset($_ENV['TEMP'])) {
        define('TMP_PATH', $_ENV['TEMP']);
    }
}

// 这里没必要用once,框架内容的加载如果还会加载两次那就真的有毒了.
require_once ROOT_PATH . 'projects.config.php';
require_once ROOT_PATH . 'define.config.php';
// 这是框架函数库
require_once FRAMEWORK_PATH . 'library/common.lib.php';

// 应用公共函数库 (并非必须存在)
if(is_file(ROOT_PATH. 'common/common/function.php')){
    include ROOT_PATH. 'common/common/function.php';
}
// 模块下各自函数库 (并非必须存在)
// 如果存在模块相互调用的情况下要注意跨模块的内容
if(is_file(PROJECT_PATH.'common/function.php')){
    include PROJECT_PATH.'common/function.php';
}

if (empty($GLOBALS['PROJECTS'][PROJECT])) {
    die('Invalid project');
}

$GLOBALS['AUTOLOAD_CLASSES'] += array(
    'controller' => FRAMEWORK_PATH . 'base/' . 'controller.class.php',
    'exception2' => FRAMEWORK_PATH . 'base/' . 'exception2.class.php',
    'request' => FRAMEWORK_PATH . 'base/' . 'request.class.php',
    'response' => FRAMEWORK_PATH . 'base/' . 'response.class.php',
    'view' => FRAMEWORK_PATH . 'library/view/' . 'view.class.php',
    'db' => FRAMEWORK_PATH . 'library/db/' . 'db.class.php',
    'cache' => FRAMEWORK_PATH . 'library/cache/' . 'cache.class.php',
    'redisCache' => FRAMEWORK_PATH . 'library/cache/' . 'redisCache.class.php',
    'session' => FRAMEWORK_PATH . 'library/session/' . 'session.class.php',
    'encrypt' => FRAMEWORK_PATH . 'library/' . 'encrypt.class.php',
    'performance' => FRAMEWORK_PATH . 'library/' . 'performance.class.php',
    'ip' => FRAMEWORK_PATH . 'library/ip/' . 'ip.class.php',
    'wechat' => FRAMEWORK_PATH . 'base/' . 'wechat.class.php',
);
function loadClass($className, $dir = '')
{
    $classFile = '';
    //先看是否在指定的路径中
    if (isset($GLOBALS['AUTOLOAD_CLASSES'][$className])) {
        if (is_readable($GLOBALS['AUTOLOAD_CLASSES'][$className])) {
            $classFile = $GLOBALS['AUTOLOAD_CLASSES'][$className];
        }
    } else {
        //是否在系统库中
        if (is_readable(FRAMEWORK_PATH . 'library/' . $className . '.class.php')) {
            $classFile = FRAMEWORK_PATH . 'library/' . $className . '.class.php';
        } elseif (is_readable(PROJECT_PATH . 'model/' . $className . '.class.php')) {
            //方便调用model类
            $classFile = PROJECT_PATH . 'model/' . $className . '.class.php';
        } elseif (is_readable(ADMIN_PATH . 'model/' . $className . '.class.php')) {
            //常见的情况是前台所需的类统一在后台
            $classFile = ADMIN_PATH . 'model/' . $className . '.class.php';
        } else {
            $classFile = str_replace('\\', DIRECTORY_SEPARATOR, ROOT_PATH . $className) . '.class.php';
            if (!is_file($classFile)) {
                $classFile = str_replace('.class.php', '.php', $classFile);
            }
        }
    }

    if (!is_file($classFile)) {
        throw new exception2('文件被怪兽吃掉啦！', 1, '犯人就是它-> ' . $classFile);
    }

    include $classFile;

    if (!class_exists($className, false) && !interface_exists($className, false)) {
        throw new exception2('Class被怪兽吃掉啦！', 1, '犯人就是它-> ' . $className);
    }

    return true;
}

spl_autoload_extensions('.php');
spl_autoload_register('loadClass');
set_exception_handler('exception_handler');

# TODO :
# 这是指定模板的,看代码是在cookie中选择
# 但是设置是在父控制器中
# 然后错误提示的showMsg方法在index文件中
# 使用的是这个参数,所以必须存在这个键哪怕是空值
$GLOBALS['templateDirectory'] = '';

require FRAMEWORK_PATH . 'base/request.class.php';

// 初始化请求变量 $REQUEST为全局变量
$REQUEST = Request::getInstance();
$REQUEST->init();
//dump($REQUEST);

define('DOMAIN', $REQUEST['domain']);
define('MAIN_DOMAIN', $REQUEST['main_domain']);
define('SUB_DOMAIN', $REQUEST['sub_domain']);
define('BROWSER_TYPE', $REQUEST['browser_type']);

if ($REQUEST['is_post'] === false) {
    $_POST = array();
}

$__GET = $_GET;
$__POST = $_POST;
$__COOKIE = $_COOKIE;

$_GET = $_POST = array();
//$_COOKIE = array(); // 如果这样原生session将不起作用
// 严格禁止使用 _REQUEST 数组
$_REQUEST = array();

start_performance();

register_shutdown_function('dump_profile');

if (empty($GLOBALS['PROJECTS'][PROJECT])) {
    die('Invalid project');
}
my_session_start();

// 预先声明DB对象
$GLOBALS['db'] = db::getInstance($GLOBALS['PROJECTS'][PROJECT]['db_host']['*']);
$GLOBALS['share_db'] = db::getInstance($GLOBALS['PROJECTS'][PROJECT]['db_host']['share_host'], true);
//加载memcache
$GLOBALS['mc'] = cache::getInstance($GLOBALS['PROJECTS'][PROJECT]['remote_cache']);
//加载xcache
//$GLOBALS['xc'] = cache::getInstance($GLOBALS['PROJECTS'][PROJECT]['local_cache']);
//加载redis
$GLOBALS['redis'] = redisCache::getInstance($GLOBALS['PROJECTS'][PROJECT]['redis_cache'], 0);

// 得到controller和action，并执行
$controller = $REQUEST->getGet('c', 'trim', 'default');
$action = $REQUEST->getGet('a', 'trim', 'login');
//如果是特定的var参数 说明是短推广链接省略了a,c参数,这对框架就有了约束，其他业务永不能用var作为参数名
if ($REQUEST->isMarketLink()) {
    $action = 'register';
}
// $GLOBALS['isSelfLottery'] = $REQUEST->isSelfLottery();

// 这里的判断是检测例如  /?c=s&a=~!##
// 但是还有一种可以过检测 /?c=s&a=x~!##
if (!preg_match('`\w+`Ui', $controller) || !preg_match('`\w+`Ui', $action)) {
    # TODO: 这里倒是给404呀
    throw new exception2('访问地址不存在！', 1, 'ERROR: controller : ' . $controller . ' action : ' . $action);
    // throw new exception2('Illegal action');
}

$controllerClassName = $controller . 'Controller';
define('CONTROLLER', $controller);
define('ACTION', $action);
define('CONTROLLER_CLASS_NAME', $controllerClassName);
define('DISPATCH_FILE', PROJECT_PATH . 'controller/' . $controller . '.php');

if (!is_readable(DISPATCH_FILE)) {
    # TODO: 这里倒是给404呀
    throw new exception2('访问地址不存在！', 1, 'ERROR: Request controller "' . $controller . '" don\'t exists!');
}

require DISPATCH_FILE;

if (!method_exists($controllerClassName, $action)) {
    # TODO: 这里倒是给404呀
    throw new exception2('访问地址不存在！', 1, 'ERROR: controller : ' . $controller . ' action : ' . $action);
}

$controllerObject = new $controllerClassName();

// 设置供控制器使用的requestInfo，方便控制器中使用传递过来的变量！
// 注：非控制器使用Request::getInstance()来使用传递过来的变量！
$controllerObject->setRequestInfo(Request::getInstance());

/**
 * 想利用控制器的validate来实现权限控制
 */
if ($controllerObject->validate($controller, $action) === false) {
    put_file('403.log', date('[Y-m-d H:i:s]', REQUEST_TIME) . '->' . DOMAIN . $REQUEST['uri'] . '<-' . $_SERVER['HTTP_REFERER'] . '->' . $REQUEST['ua']);
    header_403();
}

//清除不需要的变量
//unset($REQUEST);
// 在这句之前不应该有输出，所以将不能进入缓冲区，这意味着content-length小于实际值，信息将显示不完整
ob_start();
$controllerObject->init();
$controllerObject->{$action}();
//call_user_func(array(CONTROLLER, 'init'));
//call_user_func(array(CONTROLLER, ACTION));

ob_end_flush();

//echo "<p>页面执行时间:" . loadtime();
//fastcgi_finish_request();
//后面的输出不再产生，但会执行后续任务
// 程序执行完毕退出
exit();
