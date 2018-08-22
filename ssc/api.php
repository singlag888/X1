<?php
error_reporting(E_ALL ^ E_NOTICE); 
date_default_timezone_set('PRC');

//设置接口参数
$name = '';
$uid = '';
$token = '';

//设置缓存文件
$cache_url = "".$name.".txt";

//缓存文件（最后更新时间）
$filemtime = filemtime($cache_url);

//缓存文件（更新频率设置）
$second = '10';

if ( time() - $filemtime > $second ) {

    //设置参数
    $data = file_get_contents("http://api.caipiaokong.com/lottery/?name=".$name."&format=json&uid=".$uid."&token=".$token."");

    //$data缓存
    $array = json_decode($data,true);
    if(is_array($array)) {
        file_put_contents($cache_url,$data,LOCK_EX);
    }

}

//读取缓存
$data = file_get_contents($cache_url);
$array = json_decode($data,true);

//输出数组
//print_r($array);

foreach($array as $key => $value) {

    $html .= "<table>";
    $html .= "<tr>";

    $html .= "<td>".$key."</td>";

    //开奖号码分解
    $number = explode(",",$array[$key]['number']);
    foreach($number as $k => $v) {
        $html .= "<td><strong>".$v."</strong></td>";
    }

    $html .= "<td><em>".$array[$key]['dateline']."</em></td>";

    $html .= "</tr>";
    $html .= "</table>";

}

echo $html;

?>