<?php
//�������ݳ���
date_default_timezone_set('PRC');

//�ӿ���ַ
$apiurl = "http://api.caipiaokong.com/lottery/?name=******&format=******&uid=******&token=******";

//�����ļ���
$cachefile = "cache.xml";

//�����ļ���������ʱ�䣩
$filemtime = filemtime($cachefile);

//�����ļ�������Ƶ�����ã�
$second = '5';

if ( time() - $filemtime > $second ) {

    //���ò���
    $data = file_get_contents($apiurl);
    file_put_contents("".$cachefile."",$data,LOCK_EX);

}

?>