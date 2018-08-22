<?php
require FRAMEWORK_PATH . 'library/vendor/autoload.php';
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class uptoqiniu {
    private $fileName;
    private $filePath;
    private $ins;
    private $accessKey;
    private $secretKey;
    private $cndDir;
    private $pcBucket;
    private $mobileBucket;

    public function __construct($fileName, $filePath)
    {
        $qiniuCnf = config::getConfigs(array('accessKey','secretKey','cndDir','pc_bucket','mobile_bucket'));

        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->accessKey = $qiniuCnf['accessKey'];
        $this->secretKey = $qiniuCnf['secretKey'];
        $this->cndDir = $qiniuCnf['cndDir'];
        $this->pcBucket = $qiniuCnf['pc_bucket'];
        $this->mobileBucket = $qiniuCnf['mobile_bucket'];
    }

    public function upload()
    {
        $auth = new Auth($this->accessKey, $this->secretKey);
        // 空间名  https://developer.qiniu.io/kodo/manual/concepts
        // 生成上传Token
        if(strpos($this->filePath,'mobile') !== false){
            $token = $auth->uploadToken($this->mobileBucket);
        }else{
            $token = $auth->uploadToken($this->pcBucket);
        }

        // 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
        $cndDir = $this->cndDir.'/'.substr($this->filePath,strrpos($this->filePath,'images_fh'));

        return $uploadMgr->putFile($token,$cndDir,$this->fileName,$this->filePath,null,'application/octet-stream');
    }
}
?>