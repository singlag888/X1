<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：商户管理后台
 * 承接管理员登录等基本后台业务
 */
class defaultController extends sscAdminController
{

    //方法概览
    public $titles = array(
        'index' => '默认首页',
        'main' => '主框架',
        'top' => '顶部内容',
        'usermenu' => '用户菜单',
        'welcome' => '欢迎页',
        'login' => '用户登录',
        'logout' => '退出登录',
        'verifyCode' => '验证码',
        'excel' => '下载Excel表格',
        'siteTool' => '站长工具',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    // 默认方法
    public function index()
    {
        if (self::isLogined()) {
            redirect(url("default", "main"), 2, TRUE);
        }
        else {
            redirect(url("default", "login"), 2, TRUE);
        }
    }

    public function main()
    {
        if (!self::isLogined()) {
            redirect(url("default", "login"), 2, TRUE);
        }

        self::$view->render('default_main');
    }

    public function top()
    {
        self::$view->render('default_top');
    }

    public function usermenu()
    {
        //根据权限显示对应菜单
        if (!$group = adminGroups::getItem($GLOBALS['SESSION']['admin_group_id'])) {
            showMsg("找不到分组!");
        }
        $menus = adminMenus::getCatMenus(explode(',', $group['priv_list']));
        self::$view->setVar('menus', $menus);
        self::$view->render('default_usermenu');
    }

    //这里加个简单记事本
    public function welcome()
    {
        self::$view->render('default_welcome');
    }

    public function login()
    {
        $verify = $this->request->getPost('verify', 'trim');
        $username = $this->request->getPost('username', 'trim');
        if ($verify != 'login') { // 登陆界面
            self::$view->setVar('kkk', '1');
            self::$view->render("default_login");
            exit;
        }
        else { // 执行登陆
            $verifyCode = $this->request->getPost('verifyCode', 'trim');
            $username = $this->request->getPost('username', 'trim');
            $password = $this->request->getPost('password', 'trim');

            if (strtoupper($verifyCode) !== strtoupper($GLOBALS['SESSION']['verifyCode'])) {
                adminLogs::addLog(0, "{$username}登录失败：验证码错误");
                showMsg("验证码错误!");
            }

            if (!$username || !$password) {
                adminLogs::addLog(0, '登录失败：用户名或密码为空');
                showMsg("用户名或密码为空");
            }

            // 普通登陆
            $user = admins::login($username, $password);
            if (!is_array($user)) {
                showMsg("登录失败，用户不存在或者密码错误");
            }

            //登录信息写入session
            $GLOBALS['SESSION']['admin_id'] = $user['admin_id'];
            $GLOBALS['SESSION']['admin_username'] = $user['username'];
            $GLOBALS['SESSION']['admin_group_id'] = $user['group_id'];
            $GLOBALS['SESSION']['admin_last_ip'] = $user['last_ip'];
            $GLOBALS['SESSION']['admin_last_time'] = $user['last_time'];

            $links = array(array('title' => '进入管理中心', 'url' => url('default', 'main')));
            if ($user['last_ip'] != $GLOBALS['REQUEST']['client_ip'] && $user['last_ip'] != '0.0.0.0') { // 最后一次登陆IP和本次不同
                $msg = "您本次登陆的IP是{$GLOBALS['REQUEST']['client_ip']}和上次{$user['last_ip']}不同\\n您上次登陆的时间是\\n" . $GLOBALS['SESSION']['admin_last_time'];
                //$msg = "欢迎登录";
                //showMsg($msg, 0, $links, 'top');
                showAlert($msg, url('default', 'main'));
            }

            $msg = "欢迎登录";
            showMsg($msg, 0, $links, 'top', 0);
            //redirect(url('default', 'main'));
        }
    }

    //退出登录
    public function logout()
    {
        //记录日志
        adminLogs::addLog(1, $GLOBALS['SESSION']['admin_username'] . '退出登录');

        //登录信息写入session
        $GLOBALS['SESSION']->destroy();
        $GLOBALS['SESSION'] = array();

        $url = getUrl() . "?a=login";
        //这个只能重定向本帧，现在需要刷新父窗口
        //redirect(url('default', 'login'));
        $str = "<script>window.parent.location.href = '$url'; </script>";
        echo $str;
    }

    public function verifyCode()
    {
        $codeName = $this->request->getGet('cn', 'trim');
        if (!$codeName) {
            $codeName = 'verifyCode';
        }
        //生成一个验证码，并把验证码信息存到session里面
        $captcha = new captcha();
        $captcha->setImage(array('width' => 100, 'height' => 30, 'type' => 'png'));
        //改动：默认只显示数字
        //$captcha->setCode(array('characters' => '0-9,A-Z', 'length' => 4, 'deflect' => FALSE, 'multicolor' => FALSE));
        $captcha->setFont(array("space" => 5, "size" => 18, "left" => 5, "top" => 25, "file" => ''));
        $captcha->setMolestation(array("type" => 'point', "density" => 'fewness'));
        $captcha->setBgColor(array('r' => 200, 'g' => 200, 'b' => 200));
        $captcha->setFgColor(array('r' => 0, 'g' => 0, 'b' => 0));
        // 输出到浏览器
        $captcha->paint();
        $GLOBALS['SESSION'][$codeName] = $captcha->getcode();
    }

    public function phpinfo()
    {
        phpinfo();
    }

    public function encrypt()
    {
        $encrypt = new encrypt();
        $encrypt->config('xor', false);
        //$encrypt->config('noise', false);

        echo $encrypt->encode('QWkJMch92D+SkixmFvDpZW3U', 'SkixmFvDpZW3U'), '<br />';
        echo $encrypt->decode($encrypt->encode('1234567890123456', 'chaoqun'), 'chaoqun');
    }

    public function yaml()
    {
        $data = array(
            1 => array('t' => 'txt', 'd' => '<b>foo</b>'),
            2 => array('t' => 'rdo', 'd' => array('菁华 （qīng）    宁可（nìng）   冠心病（guān）  翘首回望（qiáo）', 'Bar', 'FooBar', 'BarFoo')),
            3 => array('t' => 'txt', 'd' => 'bar')
        );
        $yaml = $this->com->yaml->dump($data);
        echo "<pre>$yaml</pre>";
    }

    public function excel()
    {
        $excelData = $this->request->getPost('excelData', 'trim', '', false);
        $excelFile = $this->request->getPost('excelFile', 'trim', '报表', false);
//      $excelFile = iconv( 'utf-8', 'gb2312',$excelFile);
        $excelArrray = json_decode($excelData);
        if (!$excelArrray) {
            die();
        }

        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel.php';
//        /** Include PHPExcel */
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/String.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel.php';
//        /* ---------------------------if no aoto load file start---------------------------- */
//
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Calculation.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Calculation/Function.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/CalcEngine/CyclicReferenceStack.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/CalcEngine/Logger.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/IComparable.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/CachedObjectStorageFactory.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/CachedObjectStorage/Memory.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/CachedObjectStorage/CacheBase.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/CachedObjectStorage/ICache.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/PageSetup.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/PageMargins.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/HeaderFooter.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/SheetView.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/Protection.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/RowDimension.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/ColumnDimension.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Worksheet/AutoFilter.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/DocumentProperties.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/DocumentSecurity.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Supervisor.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Font.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Color.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Fill.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Borders.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Border.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Alignment.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/NumberFormat.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Style/Protection.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Cell.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Cell/DataType.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Cell/DefaultValueBinder.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Cell/IValueBinder.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/ReferenceHelper.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/WorksheetIterator.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/IOFactory.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel5.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Abstract.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/IWriter.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel5/Parser.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Calculation/Functions.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel5/Workbook.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel5/BIFFwriter.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel5/Worksheet.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel5/Xf.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Writer/Excel5/Font.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/OLE/PPS/File.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/OLE/PPS.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/OLE.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/Font.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/Date.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/OLE/PPS/Root.php';
//        require_once FRAMEWORK_PATH . 'library' . DIRECTORY_SEPARATOR . 'PHPExcel/Shared/File.php';
//        /* ---------------------------if no aoto load file end---------------------------- */
// Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
// Set document properties
        $objPHPExcel->getProperties()->setCreator($GLOBALS['SESSION']['admin_username'])
                ->setLastModifiedBy($GLOBALS['SESSION']['admin_username']);
//                ->setTitle($excelFile)
//                ->setSubject($excelFile)
//                ->setDescription($excelFile)
//                ->setKeywords($excelFile)
//                ->setCategory($excelFile);
        // Add title row
        $col = 0;
        foreach ($excelArrray[0] as $key => $value) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(PHPExcel_Cell::stringFromColumnIndex($col) . '1', $key);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth(15);
            $col++;
        }
        // Add data row
        for ($index = 0; $index < count($excelArrray); $index++) {
            $col = 0;
            foreach ($excelArrray[$index] as $key => $value) {

                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue(PHPExcel_Cell::stringFromColumnIndex($col) . ($index + 2), (string) $value);
                $col++;
            }
        }
        //excel format
        $format = 'Excel5';
        $endfix = '.xls';
        $contentType = 'Content-Type: application/vnd.ms-excel';
        if (extension_loaded('ZipArchive')) {
            $format = 'Excel2007';
            $endfix = '.xlsx';
            $contentType = 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
// Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle($excelFile);
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
// Redirect output to a client’s web browser (Excel2007)
        header($contentType);
        header('Content-Disposition: attachment;filename="' . $excelFile . $endfix . '"');
        header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $format);
        $objWriter->save('php://output');
        exit;
    }

    public function siteTool()
    {
        switch ($this->request->getPost('op', 'trim')) {
            case 'jspacker':
                $upload = new upload();
                $upfile = $upload->files();
                $uploadFile = reset($upfile);

                $srcFileContent = file_get_contents($uploadFile['tmp_name']);
                $ptr = strrpos($uploadFile['name'], '.');
                $dstFile = substr($uploadFile['name'], 0, $ptr) . '.min' . substr($uploadFile['name'], $ptr);
                //dump(strlen($srcFileContent), $dstFile);

                $packer = new javascriptPacker($srcFileContent, 'Normal', true, false);
                $packed = $packer->pack();
                $time = loadtime();

                //file_put_contents($out, $packed);
                //下载文件
                outputAttachment($packed, $dstFile, 'application/octet-stream');
                die();
                break;
            case 'createMethods':
                $filename = 'method.txt';

                $result = array();
                $methods = methods::getPlayMethods();
                $prizes = prizes::getItems(0, 0, 0, 0, 1);
                foreach ($methods as $k => $v) {
                    foreach ($v['childs'] as $kk => $vv) {
                        for ($i = 1; $i <= $vv['levels']; $i++) {
                            $methods[$k]['childs'][$kk]['prize'][$i] = $prizes[$methods[$k]['childs'][$kk]['method_id']][$i]['prize'];
                        }
                    }

                    $result[$v['lottery_id']][] = $methods[$k];
                }

                outputAttachment('var methods = ' . json_encode($result), $filename, 'application/octet-stream');
                die();
                break;
            case 'flush':
                if($GLOBALS['mc']->flush()){
                    die ('memcache清除成功');
                } else {
                    die ('memcache清除失败');
                }
                break;
            case 'flushRedis' :
                if($GLOBALS['redis']->flushDB()){
                    die ('redis清除成功');
                } else {
                    die ('redis清除失败');
                }
            case 'flushAppRedis' :
                $GLOBALS['redis']->select(REDIS_DB_APP);
                if($GLOBALS['redis']->flushDB()){
                    die ('App redis清除成功');
                } else {
                    die ('App redis清除失败');
                }
            default:
                break;
        }

        self::$view->setVar('foo', 'bar');
        self::$view->render('default_sitetool');
    }

}

?>
