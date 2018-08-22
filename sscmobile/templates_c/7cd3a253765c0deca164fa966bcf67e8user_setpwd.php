<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="webkit" name="renderer"><!-- 页面默认用极速核 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- 指定浏览器按照最高的标准模式解析页面针对IE -->
    <meta content="telephone=no,email=no" name="format-detection" /><!-- 使设备浏览网页时对数字不启用电话功能 -->
    <meta name="apple-touch-fullscreen" content="YES"/><!-- "添加到主屏幕"后，全屏显示 -->
    <meta name="apple-mobile-web-app-capable" content="yes"/>  <!-- 如果内容设置为YES，Web应用程序运行在全屏模式;否则，它不会。默认行为是使用Safari浏览器显示网页内容 -->
    <!--<meta http-equiv="Cache-Control" content="no-cache"/>-->  <!-- 每次打开都清除浏览器页面缓存 -->
    <meta http-equiv="Cache-Control" content="no-siteapp" /><!-- 度SiteApp转码声明 -->
    <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo config::getConfig('site_title'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobileStyle.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle.css">
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
</head>
<body>
<div class="operate_middle_page">
 <!--/*头部*/-->
        <header class="headerbg">
            <a class="headbox01" href="javascript:history.go(-1)"><img src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
            <p class="headtetle">设置</p>
        </header>
        <div class="mobile_set">
            <li class="ImportText">
                <a href="index.jsp?c=user&a=editPwd"><b>修改登录密码</b><img src="<?php echo $imgCdnUrl ?>/images/mobile/arrows_right.png" /></a>
            </li>
            <li class="ImportText">
                <a href="index.jsp?c=user&a=editSecPwd"><b>修改资金密码</b><img src="<?php echo $imgCdnUrl ?>/images/mobile/arrows_right.png" /></a>
            </li>
<!--            <li class="ImportText">-->
<!--                <a href="index.jsp?c=user&a=editSafePwd"><b>修改安全码</b><img src="--><?php //echo $imgCdnUrl ?><!--/images/mobile/arrows_right.png" /></a>-->
<!--            </li>-->
            <li class="ImportText">
                <a href="index.jsp?c=fin&a=bindCard"><b>卡号绑定</b><img src="<?php echo $imgCdnUrl ?>/images/mobile/arrows_right.png" /></a>
            </li>
        </div>
        <p class="button_top"><a href="javascript:void(0);" class="topLinkBtn index_outbtn"><input type="button" value="退出账号" class="Button_red"/></a></p>
</div>
<script src="<?php echo $imgCdnUrl ?>/js/common.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script> <!-- layer调用弹出层 -->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
<?php $this->import('public_tongji') ?>
</body>
</html>