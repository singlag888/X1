<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="webkit" name="renderer"><!-- 页面默认用极速核 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- 指定浏览器按照最高的标准模式解析页面针对IE -->
    <meta content="telephone=no,email=no" name="format-detection" /><!-- 使设备浏览网页时对数字不启用电话功能 -->
    <!--<meta name="apple-touch-fullscreen" content="YES"/>&lt;!&ndash; "添加到主屏幕"后，全屏显示 &ndash;&gt;-->
    <meta name="apple-mobile-web-app-capable" content="yes"/>  <!-- 如果内容设置为YES，Web应用程序运行在全屏模式;否则，它不会。默认行为是使用Safari浏览器显示网页内容 -->
    <meta http-equiv="Cache-Control" content="no-cache"/>
    <meta http-equiv="Cache-Control" content="no-siteapp" /><!-- 度SiteApp转码声明 -->
    <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo config::getConfig('site_title'); ?></title>
<!--    <link rel="stylesheet" type="text/css" href="--><?php //echo $imgCdnUrl ?><!--/css/mobileStyle.css">-->
<!--    <link rel="stylesheet" type="text/css" href="--><?php //echo $imgCdnUrl ?><!--/css/mobile_overallStyle.css">-->
<!--    <link rel="stylesheet" type="text/css" href="--><?php //echo $imgCdnUrl ?><!--/css/global_reset.css" />-->
<!--    <link rel="stylesheet" type="text/css" href="--><?php //echo $imgCdnUrl ?><!--/css/all_LightBlue.css" />-->
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
<!--    <script type="text/javascript" src="--><?php //echo $imgCdnUrl ?><!--/js/public.js"></script>-->
<!--    <script type="text/javascript" src="--><?php //echo $imgCdnUrl ?><!--/js/jquery.plugin.js"></script>&lt;!&ndash;jquery小插件&ndash;&gt;-->
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script>
<!--    <script type="text/javascript" src="--><?php //echo $imgCdnUrl ?><!--/js/ZeroClipboard.107.js"></script>-->
<!--    <script type="text/javascript" src="--><?php //echo $imgCdnUrl ?><!--/js/jquery.md5.js"></script>-->

<style type="text/css">
html{
/*font-size: 20rem;*/
}
*{
margin: 0;
padding:0;
box-sizing: border-box;
list-style: none;
}
.service{
background: #E4393C;
width: 100%;
position: relative;
height: 2.5rem;
line-height:2.5rem;
display: flex;
}
.service a img{
width: 2.5rem;
height: 2.5rem;
}
.service span{
font-size: 0.75rem;
    color: white;
    display: inline-block;
    margin: 0 auto;
    position: absolute;
    text-align: center;
    width: 100%;
    left: 0;
}
.pay_list{
width: 100%;
display: flex;
justify-content: center;
/*margin-top: 0.5rem;*/
/*margin-bottom: 0.5rem;*/
}
.pay_list ul{
display: flex;
width:100%;
border-top: 1px solid #E7DFDF;
align-items: center;
}
.pay_list ul li{
font-size: 0.75rem;
width: 24%;
text-align: center;
font-weight: 700;
float: left;
height: 2rem;
line-height: 2rem;
}
.iframe_{
width: 100%;
height: 22.5rem;
}
.change_style{
color:red;
background: #F9F5F5;
}
.layui-layer-btn a{font-size: 0.7REM;}
input[type=button], input[type=submit], input[type=file], button {
    cursor: pointer;
    -webkit-appearance: none;
}
.fin_pay_kefu{height: 2.5rem; color: #fff;display: inline-block;position: absolute;right: 0rem;top: 0;font-size: 0.8rem;width: 2.5rem;z-index: 1;}
.fin_pay_kefu img{width: 100%;}
.head_back{position:relative;z-index: 1;left: 0;top: 0;}
</style>
    <script type="application/javascript">

//        (function (doc, win) {
//            var docEl = doc.documentElement,
//                resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
//                recalc = function () {
//                    var clientWidth = docEl.clientWidth;
//                    if (!clientWidth) return;
//                    if(clientWidth>=640){
//                        docEl.style.fontSize = '100px';
//                    }else{
//                        docEl.style.fontSize = 100 * (clientWidth / 640) + 'px';
//                    }
//                };
//            if (!doc.addEventListener) return;
//            win.addEventListener(resizeEvt, recalc, false);
//            doc.addEventListener('DOMContentLoaded', recalc, false);
//        })(document, window);

        $(function () {

            $('.pay_list ul li').last().addClass('change_style');
            $('.pay_list ul li').click(function () {
                var index=$(this).index();
                $(this).addClass('change_style').siblings().removeClass('change_style');
                if (index==0){
                    $('iframe').attr("src","?&c=fin&a=deposit&usage=3");
                }else if (index==1){
                    $('iframe').attr("src","?&c=fin&a=deposit&usage=2");
                }else if (index==2){
                    $('iframe').attr("src","?&c=fin&a=deposit&usage=5");
                }else if (index==3){
                    $('iframe').attr("src","?&c=fin&a=deposit&usage=4");
                }else if (index==4){
                    $('iframe').attr("src","?&c=fin&a=bankPay");
                }
            })
        })
    </script>
</head>
<body>
<div class="service">
    <a href="?a=welcome" class="head_back"><img src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
    <span>7x24小时充值服务</span>
    <a class="fin_pay_kefu" href="<?php echo getFloatConfig('service_url'); ?>" target="_blank"><img src="<?php echo $imgCdnUrl ?>/images/mobile/finpay_kefu.png"></a>
</div>
<div class="pay_list">
    <ul>
        <li>扫码</li>
        <li>在线</li>
        <li>支付宝</li>
        <li>微信</li>
        <li>银行卡</li>
    </ul>
</div>
<div class="iframe_">
    <iframe src="?&c=fin&a=bankPay" frameborder="0" scrolling="yes" width="100%" height="100%"></iframe>
</div>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js?v=<?php echo $html_version; ?>"></script>
</body>
</html>
