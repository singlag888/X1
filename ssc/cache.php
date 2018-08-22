<?php
//缓存数据程序
date_default_timezone_set('PRC');

//接口网址
$apiurl = "http://api.caipiaokong.com/lottery/?name=******&format=******&uid=******&token=******";

//缓存文件名
$cachefile = "cache.xml";

//缓存文件（最后更新时间）
$filemtime = filemtime($cachefile);

//缓存文件（更新频率设置）
$second = '5';

if ( time() - $filemtime > $second ) {

    //设置参数
    $data = file_get_contents($apiurl);
    file_put_contents("".$cachefile."",$data,LOCK_EX);

}

?>