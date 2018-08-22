<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 功能 : 服务器保存可靠的收款信息
 */
class clientController extends sscController
{
    private $post = array();

    private $isEncrypted = true;

    const PRIVATE_KEY = '012345678901234'; //此值须双方设定一致

    public function init()
    {
        //parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    // 默认方法
    public function index()
    {
        $encryptData = file_get_contents("php://input");
        $this->post = self::decrypt($encryptData);
log2("服务器接收的数据：{$encryptData}", $this->post);
        if (!isset($this->post['a']) || !method_exists($this, $this->post['a'])) {
            echo self::encrypt(array('errno'=>10,'errstr'=>'invalid action'));
log2("服务器返回的数据：invalid action");
            return;
        }
        $result = $this->{$this->post['a']}();
log2("服务器返回的数据：" . var_export($result, true));
        //交给具体的action
        //echo self::encrypt($result);
    }

    /**
     * 检查是否有新版本
     */
    public function checkVersion()
    {
        $result = array('errno' => 0, 'errstr' => '', 'curVersion' => '');

        // 如果不传，那肯定是有问题的 发送halt指令999
        if (empty($this->post['version'])) {
            echo self::encrypt(array('errno' => 1, 'errstr' => 'non get version'));
            return array('errno' => 1, 'errstr' => 'no special version');
        }

        $result['curVersion'] = config::getConfig('client_latest_version', '1.0');

        echo self::encrypt($result);
        return $result;
    }
    
    /**
     * 得到需更新的文件列表
     * @return type 
     */
    public function getFileList()
    {
        $result = array('errno' => 0, 'errstr' => '', 'fileList' => '');

        // 如果不传，那肯定是有问题的 发送halt指令999
        if (empty($this->post['version'])) {
            echo self::encrypt(array('errno' => 1, 'errstr' => 'non get version'));
            return array('errno' => 1, 'errstr' => 'no special version');
        }

        $result['fileList'] = config::getConfig('client_update_file_list');

        echo self::encrypt($result);
        return $result;
    }
    
    public function download()
    {
        $filename = FORE_PATH . 'upload/' . $this->post['filename'];
        // 如果不传，那肯定是有问题的 发送halt指令999
        if (!file_exists($filename)) {
            return false;
        }
        
        if ($this->post['version'] == config::getConfig('client_latest_version', '1.0')) {
            return false;
        }

        $content = file_get_contents($filename);
        outputAttachment($content, $filename);
        
        return true;
    }

    /**
     * @param array $arr 原数组
     * @return string 加密字符串
     */
    public function encrypt($arr)
    {
        $tmp = array();
        foreach ($arr as $k => $v) {
            $tmp[] = "$k=" . $v;
        }

        if ($this->isEncrypted == false) {
            $result = rawurlencode(implode('&', $tmp));
        }
        else {
            $result = authcode(rawurlencode(implode('&', $tmp)), 'ENCODE', self::PRIVATE_KEY);
        }

        return $result;
    }

    /**
     *
     * @param string $encryptData 加密字符串
     * @return array 还原为数组
     */
    public function decrypt($encryptData)
    {
        $result = array();
        if (preg_match('`a(=|%3D)\w{3,}`', $encryptData) != false) { //如果是明文不需解密
            $this->isEncrypted = false;
            parse_str(rawurldecode($encryptData), $result);
        }
        else {
            $this->isEncrypted = true;
            parse_str(rawurldecode(authcode($encryptData, 'DECODE', self::PRIVATE_KEY)), $result);
        }

        return $result;
    }
}
?>