<?php
require FRAMEWORK_PATH . 'library/vendor/autoload.php';

use OSS\OssClient;

class uploadaliyun {
    private $bucket;
    private $object;
    private $file;
    private $client = null;


    public function __construct($fileName, $filePath)
    {
        //>>开始写日志

        $file = ROOT_PATH . 'sscadmin/logs/' . 'aliyunUpload.log';
        file_put_contents($file, (__FUNCTION__ . "开始调用aliyun oss 进行实例化\n") . "\n", FILE_APPEND);
        //>>获取配置文件
        $aliyunConfig   = config::getConfigs(['aliyun_accessKeyId','aliyun_accessKeySecret','aliyun_endpoint','aliyun_phBucket','aliyun_pcBucket']);
        $this->file     = $filePath . $fileName;//>>获取本地文件路径
        $prefix         = defined('XY_PREFIX') && !empty(XY_PREFIX) ? XY_PREFIX . '/' : '';
        $this->object   = $prefix . substr($this->file,strpos($this->file,'images_fh')) ;
        $accessKeyId        = $aliyunConfig['aliyun_accessKeyId'];          //>>阿里云AK
        $accessKeySecret    = $aliyunConfig['aliyun_accessKeySecret'];      //>>阿里云AKS
        $endpoint           = $aliyunConfig['aliyun_endpoint'];             //>>访问域名
        $isCName = false;
        //>>判断图片上传是否是移动端的
        $this->bucket   =  strpos($filePath,'mobile') !== false ? $aliyunConfig['aliyun_phBucket'] : $aliyunConfig['aliyun_pcBucket'];

        $securityToken  = NULL;
        try {
            $this->client = new OssClient($accessKeyId, $accessKeySecret, $endpoint, $isCName , $securityToken);
        } catch (\OSS\Core\OssException $e) {
            file_put_contents($file, (__FUNCTION__ . "实例化oss对象出错,请检查配置 creating OssClient instance: FAILED\n") . "\n", FILE_APPEND);
            file_put_contents($file, (json_encode($e->getMessage())) . "\n", FILE_APPEND);
            $this->client = null;
            return null;
        }


    }

    //>>上传到阿里云oss
    public function upload()
    {
        $file = ROOT_PATH . 'sscadmin/logs/' . 'aliyunUpload.log';
        if(is_null($this->client))
        {
            //>.写日志文件
            file_put_contents($file, '实例化oss对象出错,请检查配置' . "\n", FILE_APPEND);
            return '实例化oss对象出错,请检查配置';
        }
        //>>写日志文件
        try{
            $result = $this->client->uploadFile($this->bucket, $this->object,$this->file);
            $str = json_encode($result) ;
            file_put_contents($file, $str . "\n", FILE_APPEND);
            return true;
        }catch (\OSS\Core\OssException $e){
            $str = $e->getDetails();
            $str_two = $e->getErrorMessage();
            file_put_contents($file, json_encode($str) . "原始信息\n" . json_encode($str_two) . "错误信息\n", FILE_APPEND);
            return $str;
        }


    }
}
