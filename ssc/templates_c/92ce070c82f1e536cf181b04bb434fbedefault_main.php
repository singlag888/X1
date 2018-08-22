<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="yes" name="apple-touch-fullscreen">
    <meta content="telephone=no,email=no" name="format-detection">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo config::getConfig('site_title'); ?></title>

    <link rel="stylesheet" href="<?php echo $imgCdnUrl ?>/css_fh/pulic-login.css?v=<?php echo $html_version ?>">
    <?php $this->import('public_cssjs') ?>
    <script src="<?php echo $imgCdnUrl ?>/js/jqueryUI/dialog/js/jquery.dialog.js" type="text/javascript"></script>
    <script src="<?php echo $imgCdnUrl ?>/js/login.js"></script>
    <style type="text/css">
    .layui-layer-title{
        background-color:#e4393c;
    }
    </style>
</head>
<body style="background: #fff">
    <!--头部-->
<div class="header-box">
    <div class="header-ts">
        <div class="ts-play">
            <img src="<?php echo $imgCdnUrl?>/images_fh/laba.png" alt="shibai">
            <div class="NewSlides">
            <div id="NewSl">
                <ul id="NewSl_begin">
                    <?php foreach (range(0, 9) as $v) : ?>
                        <li><span class="listpart"><a href="javascript:void(0);" class="ShowNewsMore" article_id="<?php echo $notices[$v]['article_id']; ?>"><?php echo mb_substr($notices[$v]['title'], 0, 50, 'utf-8'); ?></a></span><span class="time"><?php
                                if (!empty($notices[$v]['create_time'])) {
                                    echo date("Y-m-d", strtotime($notices[$v]['create_time']));
                                }
                                ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <ul id="NewSl_end"></ul>
            </div>
        </div>
    </div>
</div>
<div class="men_list">
    <div class="wid1">
        <div>
            <div class="logo mt0">
                <a href="?a=main"><img src="<?php echo $imgCdnUrl?>/images_600/logoo.png" alt=""></a>
            </div>
            <img src="<?php echo $imgCdnUrl?>/images_fh/slogan.jpg" alt="" style="margin-top:8px;margin-left: 52px;">
        </div>
    </div>
</div>
<div style="width: 100%;background: #f13131;">
    <div class="bar-play cf">
        <h3>选择彩票种类</h3>
        <div class="navmenu fl bar-a">
            <a href="?a=main">首页</a>
            <a href="?c=game&a=lobby" >购彩大厅</a>
            <a href="?c=help&a=result">开奖结果</a>
            <a href="?c=help&a=download">手机购彩
                <div class="hot">
                    <div class="hot-all">
                        <img src="<?php echo $imgCdnUrl?>/images_fh/hot.gif" alt="">
                    </div>
                </div>
            </a>
            <a href="?c=help&a=chart" >开奖走势
                <div class="hot1">
                    <div class="hot-all">
                        <img src="<?php echo $imgCdnUrl?>/images_fh/hot.gif" alt="">
                    </div>
                </div>
            </a>

            <a href="?c=help&a=platformact">优惠活动
                <div class="hot1">
                    <div class="hot-all">
                        <img src="<?php echo $imgCdnUrl?>/images_fh/hot.gif" alt="">
                    </div>
                </div>
            </a>

            <a href="?c=help&a=latestnew">彩票资讯</a>
                <!--<a href="?c=fake&a=safe">帮助中心</a>-->
            <!--移动的滑动-->
            <div class="move-bg"></div>
            <script>
                $(function () {
                    $(".navmenu").movebg({
                        width: 94/*滑块的大小*/,
                        extra: 10/*额外反弹的距离*/,
                        speed: 300/*滑块移动的速度*/,
                        rebound_speed: 350/*滑块反弹的速度*/
                    });
                })

            </script>
            <!--移动的滑动 end-->
        </div>
        <div class="bar-service fr">
            <a class="duokebo-btn" target="_blank" href="<?php echo getFloatConfig('service_url'); ?>">
                <img src="<?php echo $imgCdnUrl ?>/images_fh/servicelogo.png" alt="">
                客服
            </a>
        </div>
    </div>
</div>


<!--公告tab.js-->
<!-- <script type="text/javascript">
var myclick = function(v) {
var llis = document.getElementsByClassName("edded");
for(var i = 0; i < llis.length; i++) {
var lli = llis[i];
if(lli == document.getElementById("tab" + v)) {
lli.style.border = "2px solid red";
} else {
lli.style.border = "2px solid #fff";
}
}

var divs = document.getElementsByClassName("tab_css_gg");
for(var i = 0; i < divs.length; i++) {

var divv = divs[i];

if(divv == document.getElementById("tab" + v + "_content")) {
divv.style.display = "block";
} else {
divv.style.display = "none";
}
}

}
</script> -->

<div class="main_layout wid1">
    <div class="left_layout">
        <ul>
            <li><a href="?c=game&a=cqssc">
                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-3.png" alt="">重庆时时彩<i class="i0">最火爆彩种</i></a>
            </li>
            <li><a href="?c=game&a=bjpks">
                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-19.png" alt="">北京PK10<i class="i0">5分钟一期 快速</i></a>
            </li>
            <li><a href="?c=game&a=xyft_x">
                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-31.png" alt="">幸运飞艇<i class="i0">激情飞艇 快速</i></a>
            </li>
            <li><a href="?c=game&a=yzmmc">
                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-1.png" alt="">幸运秒秒彩<i class="i0">随点随开</i></a>
            </li>
            <li><a href="?c=game&a=lhc">
                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-18.png" alt="">香港⑥合彩<i class="i0">火爆低频</i></a>
            </li>
            <li><a href="?c=game&a=jsks">
                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-20.png" alt="">江苏快3<i class="i0">10分钟一期</i></a>
            </li>
            <li><a href="?c=game&a=sd11y">
                <img src="<?php echo $imgCdnUrl?>/images/lottery_logo_2.png" alt="">山东11选5<i class="i0">趣味玩法</i></a>
            </li>

            <li><a href="?c=game&a=ssq">
                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-27.png" alt="">幸运双色球<i class="i0">千万大奖等着您</i></a>
            </li>
            <!--<li><a href="javascript:void(0)" onclick="openGcdt('klsf')"><img src="https://www.6008871.com:443/static/theme/600w/img/ico26.png" alt="">广东快乐十分</a><i>十分钟一期</i></li>-->
        </ul>
        <div class="high">
            <b class="b0"><img src="<?php echo $imgCdnUrl?>/images_600/ico79.png" alt="">官 方 彩</b>
            <p>
                <a href="?c=game&a=yzffc">幸运分分彩</a>
                <a href="?c=game&a=cqssc">重庆时时彩</a>
                <a href="?c=game&a=bjpks">北京PK10</a>
                <a href="?c=game&a=sd11y">山东11选5</a>
                <a href="?c=game&a=jsks">江苏快3</a>
                <a href="?c=game&a=yzmmc">幸运秒秒彩</a>
                <a href="?c=game&a=dj15">东京1.5分彩</a>
                <a href="?c=game&a=xjssc">新疆时时彩</a>
            </p>

            <a href="javascript:void(0)" class="more"><img src="<?php echo $imgCdnUrl?>/images_600/ico81.png" alt=""></a>
            <div class="downt_more">
                <h5>官方彩</h5>
                <strong>
                    <a href="?c=game&a=cqssc">重庆时时彩</a>
                    <a href="?c=game&a=xjssc">新疆时时彩</a>
                    <a href="?c=game&a=tjssc">天津时时彩</a>
                    <a href="?c=game&a=yzmmc">幸运秒秒彩</a>
                    <a href="?c=game&a=yzffc">幸运分分彩</a>
                    <a href="?c=game&a=dj15">东京1.5分彩</a>
                    <!-- <a class="cur-not" href="javascript:;">印尼5分彩</a> -->
                    <!-- <a class="cur-not" href="javascript:;">七星彩</a> -->
                    <a href="?c=game&a=xy28">幸运28</a>
                    <!-- <a class="cur-not" href="javascript:;">泰国5分彩</a> -->
                    <a href="?c=game&amp;a=qqffc">腾讯分分彩</a>
                    <a href="?c=game&a=klpk">山东快乐扑克</a>
                    <a href="?c=game&a=sd11y">山东11选5</a>
                    <a href="?c=game&a=jx115">江西11选5</a>
                    <a href="?c=game&a=js115">江苏11选5</a>
                    <a href="?c=game&a=gd115">广东11选5</a>
                    <a href="?c=game&a=ffc115">11选5分分彩</a>
                    <a href="?c=game&a=bjpks">北京PK拾</a>
                    <a href="?c=game&a=low3D">福彩3D</a>
                    <a href="?c=game&a=P3P5">体彩P3P5</a>
                    <a href="?c=game&a=lhc">香港⑥合彩</a>
                    <a href="?c=game&a=jsks">江苏快三</a>
                    <a href="?c=game&a=ahks">安徽快三</a>
                    <a href="?c=game&a=ksffc">快三分分彩</a>
                    <a href="?c=game&a=ssq">双色球</a>
                    <a href="?c=game&a=jslhc">极速⑥合彩</a>
                    <a class="cur-not" href="javascript:;">上海时时乐</a>
                </strong>
            </div>
        </div>
        <div class="high">
            <b class="b1"><img src="<?php echo $imgCdnUrl?>/images_600/ico80.png" alt="">信 用 彩</b>
            <p>
                <a href="?c=game&a=lhc_x">香港⑥合彩</a>
                <a href="?c=game&a=jsks_x">江苏快三</a>
                <a href="?c=game&a=ahks_x">安徽快三</a>
                <a href="?c=game&a=ksffc_x">快三分分彩</a>
                <a href="?c=game&a=bjpks_x">北京PK拾</a>
                <a href="?c=game&a=jslhc_x">极速⑥合彩</a>
                <a href="?c=game&a=xyft_x">幸运飞艇</a>
                <a href="?c=game&a=bjpks_x">北京PK拾</a>
            </p>
            <a href="javascript:void(0)" class="more"><img src="<?php echo $imgCdnUrl?>/images_600/ico81.png" alt=""></a>
            <div class="downt_more bott">
                <h5>信 用 彩</h5>
                <strong>
                    <a href="?c=game&a=cqssc_x">重庆时时彩</a>
                    <a href="?c=game&a=xjssc_x">新疆时时彩</a>
                    <a href="?c=game&a=tjssc_x">天津时时彩</a>
                    <a href="?c=game&a=yzffc_x">幸运分分彩</a>
                    <a href="?c=game&a=dj15_x">东京1.5分彩</a>
                    <!-- <a class="cur-not" href="javascript:;">印尼5分彩</a> -->
                    <a href="?c=game&a=qqffc_x">腾讯分分彩</a>
                    <a href="?c=game&a=xy28_x">幸运28</a>
                    <a href="?c=game&a=xyft_x">幸运飞艇</a>
                    <a href="?c=game&a=sd11y_x">山东11选5</a>
                    <a href="?c=game&a=jx115_x">江西11选5</a>
                    <a href="?c=game&a=js115_x">江苏11选5</a>
                    <a href="?c=game&a=gd115_x">广东11选5</a>
                    <a href="?c=game&a=klpk_x">山东快乐扑克</a>
                    <a href="?c=game&a=bjpks_x">北京PK拾</a>
                    <a href="?c=game&a=low3D_x">福彩3D</a>
                    <a href="?c=game&a=P3P5_x">体彩P3P5</a>
                    <a href="?c=game&a=lhc_x">香港⑥合彩</a>
                    <a href="?c=game&a=jsks_x">江苏快三</a>
                    <a href="?c=game&a=ahks_x">安徽快三</a>
                    <a href="?c=game&a=ksffc_x">快三分分彩</a>
                    <a href="?c=game&a=ssq_x">双色球</a>
                    <!-- <a class="cur-not" href="javascript:;">上海时时乐</a> -->
                    <a href="?c=game&a=jslhc_x">极速⑥合彩</a>
                    <a href="?c=game&a=ffc115_x">11选5分分彩</a>
                </strong>
            </div>
        </div>
        <div class="high tesh">
            <h3>全部&gt;&gt;</h3>
            <div class="downt_more bott1">
                <h5>全部</h5>
                <strong>
                    <a href="?c=game&a=cqssc">重庆时时彩</a>
                    <a href="?c=game&a=xjssc">新疆时时彩</a>
                    <a href="?c=game&a=tjssc">天津时时彩</a>
                    <a href="?c=game&a=yzmmc">幸运秒秒彩</a>
                    <a href="?c=game&a=yzffc">幸运分分彩</a>
                    <a href="?c=game&a=dj15">东京1.5分彩</a>
                    <a class="cur-not" href="javascript:;">印尼5分彩</a>
                    <a class="cur-not" href="javascript:;">七星彩</a>
                    <a href="?c=game&a=xy28">幸运28</a>
                    <a href="?c=game&amp;a=qqffc">腾讯分分彩</a>
                    <a class="cur-not" href="javascript:;">泰国5分彩</a>
                    <a href="?c=game&a=klpk">山东快乐扑克</a>
                    <a href="?c=game&a=sd11y">山东11选5</a>
                    <a href="?c=game&a=jx115">江西11选5</a>
                    <a href="?c=game&a=js115">江苏11选5</a>
                    <a href="?c=game&a=gd115">广东11选5</a>
                    <a href="?c=game&a=ffc115">11选5分分彩</a>
                    <a href="?c=game&a=bjpks">北京PK拾</a>
                    <a href="?c=game&a=low3D">福彩3D</a>
                    <a href="?c=game&a=P3P5">体彩P3P5</a>
                    <a href="?c=game&a=lhc">香港⑥合彩</a>
                    <a href="?c=game&a=jsks">江苏快三</a>
                    <a href="?c=game&a=ahks">安徽快三</a>
                    <a href="?c=game&a=ksffc">快三分分彩</a>
                    <a href="?c=game&a=ssq">双色球</a>
                    <a class="cur-not" href="javascript:;">上海时时乐</a>
                    <a href="?c=game&a=lhc_x">信-香港⑥合彩</a>
                    <a href="?c=game&a=jsks_x">信-江苏快三</a>
                    <a href="?c=game&a=ahks_x">信-安徽快三</a>
                    <a href="?c=game&a=ksffc_x">信-快三分分彩</a>
                    <a href="?c=game&a=bjpks_x">信-北京PK拾</a>
                </strong>
            </div>
        </div>
    </div>

    <div class="right_layotu">
        <div class="left_wrap">
            <div class="id-lb ">
                <div class="lb-img" id="lb-img">
                    <?php foreach ($activities as $v): ?>
                        <?php if ($v['banner_img'] == '') continue; ?>
                        <a href="?c=help&a=platformact" class="lb-img1 fl">
                            <img src="<?php echo $imgCdnUrl ?>/<?php echo $v['banner_img'] ?>"/>
                        </a>
                    <?php endforeach; ?>
                     <!-- <?php echo $giftBanner; ?> -->
                </div>
                <ul class="lb-tt ">
                    <li class="on"></li>
                    <?php for($i = 1;$i < $j;$i++){ ?>
                    <li></li>
                    <?php } ?>
                </ul>
            </div>
            <div class="id-banner fl">
                <ul class="id-banner-tt cf" id="number_show">
                    <li class="banner_list_x" onMouseOver="idSe_x(this,'#number_show li','.id-banner-text',ssc_change())">重庆时时彩</li>
                    <li class="banner_list_hover_x" onMouseOver="idSe_x(this,'#number_show li','.id-banner-text',sd_change())">幸运分分彩</li>
                    <li class="banner_list_x" onMouseOver="idSe_x(this,'#number_show li','.id-banner-text',xy_change())">幸运28</li>
                    <li class="banner_list_x" onMouseOver="idSe_x(this,'#number_show li','.id-banner-text',js_change())">江苏快三</li>
                    <li class="banner_list_x" onMouseOver="idSe_x(this,'#number_show li','.id-banner-text',fc_change())">香港⑥合彩</li>
                </ul>
                <div>
                    <!--重庆时时彩-->
                    <div class="id-banner-text" id="bannerLotteryId_1">
                        <div class="banner-time">

                            <div class="fr ">
                                <a href="?c=game&a=cqssc">手动选号</a>
                                <span class="one" style="cursor: pointer">|幸运选号</span>
                                <span>|</span>
                                <a href="?c=game&a=chart&lottery_id=1" target="_blank" style="color:#00a0e9;">走势图</a>
                            </div>
                        </div>
                        <ul class="banner-num cf five" id="ssc_number">
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                        </ul>

                        <div class="banner-toal">
                            <div class="fl">
                                <div class="id-banner-re fl" onclick="re()">-</div>
                                <div class="id-banner-input fl">
                                    <input type="text" value="1" onchange="inpChange()">
                                </div>
                                <div class="id-banner-add fl" onclick="add()">+</div>
                                <div class="banner-ta fl">倍
                                    共<span class="toal-much">2</span>元
                                </div>
                            </div>

                            <div class="fr">
                                <div class="fr now-join"><a href="?c=game&a=cqssc" ><font color=white>立即投注</font></a></div>
                            </div>
                        </div>
                    </div>
                    <!--北京PK10-->
                    <div class="id-banner-text ds" id="bannerLotteryId_11">
                        <div class="banner-time">

                            <div class="fr ">
                                <a href="?c=game&a=sd11y">手动选号</a>
                                 <span class="two" style="cursor: pointer">|幸运选号</span>
                                <span>|</span>
                                <a href="?c=game&a=chart&lottery_id=11" target="_blank" style="color:#00a0e9;">走势图</a>
                            </div>
                        </div>
                        <ul class="banner-num cf five" id="sd_number">
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                        </ul>
                        <div class="banner-toal">
                            <div class="fl">
                                <div class="id-banner-re fl" onclick="re()">-</div>
                                <div class="id-banner-input fl">
                                    <input type="text" value="1" onchange="inpChange()">
                                </div>
                                <div class="id-banner-add fl" onclick="add()">+</div>
                                <div class="banner-ta fl">倍
                                    共<span class="toal-much">2</span>元
                                </div>
                            </div>

                            <div class="fr">
                                <div class="fr now-join"><a href="?c=game&a=yzffc" ><font color=white>立即投注</font></a></div>
                            </div>
                        </div>
                    </div>
                    <!--幸运28-->
                    <div class="id-banner-text ds" id="bannerLotteryId_23">
                        <div class="banner-time">

                            <div class="fr ">
                                <a href="?c=game&a=xy28">手动选号</a>
                                 <span class="three" style="cursor: pointer">|幸运选号</span>
                                <span>|</span>
                                <a href="?c=game&a=chart&lottery_id=23" target="_blank" style="color:#00a0e9;">走势图</a>
                            </div>
                        </div>
                        <ul class="banner-num cf" id="xy_number">
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                        </ul>
                        <div class="banner-toal">
                            <div class="fl">
                                <div class="id-banner-re fl" onclick="re()">-</div>
                                <div class="id-banner-input fl">
                                    <input type="text" value="1" onchange="inpChange()">
                                </div>
                                <div class="id-banner-add fl" onclick="add()">+</div>
                                <div class="banner-ta fl">倍
                                    共<span class="toal-much">2</span>元
                                </div>
                            </div>
                            <div class="fr">
                                <div class="fr now-join"><a href="?c=game&a=xy28" ><font color=white>立即投注</font></a></div>
                            </div>
                        </div>
                    </div>
                    <!--江苏快三-->
                    <div class="id-banner-text ds" id="bannerLotteryId_12">
                        <div class="banner-time">

                            <div class="fr ">
                                <a href="?c=game&a=jsks">手动选号</a>
                                 <span class="four" style="cursor: pointer">|幸运选号</span>
                                <span>|</span>
                                <a href="?c=game&a=chart&lottery_id=12" target="_blank" style="color:#00a0e9;">走势图</a>
                            </div>
                        </div>
                        <ul class="banner-num cf" id="js_number">
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                        </ul>
                        <div class="banner-toal">
                            <div class="fl">
                                <div class="id-banner-re fl" onclick="re()">-</div>
                                <div class="id-banner-input fl">
                                    <input type="text" value="1" onchange="inpChange()">
                                </div>
                                <div class="id-banner-add fl" onclick="add()">+</div>
                                <div class="banner-ta fl">倍
                                    共<span class="toal-much">2</span>元
                                </div>
                            </div>

                            <div class="fr">
                                <div class="fr now-join"><a href="?c=game&a=jsks" ><font color=white>立即投注</font></a></div>
                            </div>
                        </div>
                    </div>
                    <!--福彩3D-->
                    <div class="id-banner-text ds" id="bannerLotteryId_9">
                        <div class="banner-time">

                            <div class="fr ">
                                <a href="?c=game&a=low3D">手动选号</a>
                                 <span class="five" style="cursor: pointer">|幸运选号</span>
                                <span>|</span>
                                <a href="?c=game&a=chart&lottery_id=9" target="_blank" style="color:#00a0e9;">走势图</a>
                            </div>
                        </div>
                        <ul class="banner-num cf" id="fc_number">
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                            <li>0</li>
                        </ul>
                        <div class="banner-toal">
                            <div class="fl">
                                <div class="id-banner-re fl" onclick="re()">-</div>
                                <div class="id-banner-input fl">
                                    <input type="text" value="1" onchange="inpChange()">
                                </div>
                                <div class="id-banner-add fl" onclick="add()">+</div>
                                <div class="banner-ta fl">倍
                                    共<span class="toal-much">2</span>元
                                </div>
                            </div>

                            <div class="fr">
                                <div class="fr now-join"><a href="?c=game&a=lhc" ><font color=white>立即投注</font></a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!--Bettingbag-->
        </div>
        <div class="right_wrap id-login" style="overflow:hidden;">
            <div class="all-login fr login_main">
                <div class="id-login-inp" style="font-size: 12px; text-align: left;">
                    <h4 style="padding-left: 0;border-bottom: 1px solid #e4393c;margin-bottom: 12px;">
                        <p>会员登录</p>
                    </h4>
                    <div class="User user-boxs02">
                    <em class="name ShowTipsUserInfo"><span style="display: inline-block;float:left;">您好，</span>
                    <span style="color:#e4393c;display:inline-block;width: 60px; overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"><?php echo $user['username']; ?></span>
                    </em>
                    <div class="UserPopLayer hide"> <a href="javascript:void(0);" class="CloseUser"></a>
                        <div class="UserInfoBox">
                            <ul>
                                <li>
                                    <label class="user_Label">登录IP：</label>
                                    <span class="user_info"><?php echo $user['last_ip']; ?></span></li>
                                <li>
                                    <label class="user_Label">最后登录：</label>
                                    <span class="user_info"><?php echo $user['last_time']; ?></span></li>
                            </ul>
                        </div>
                        <div class="shadowLayer"></div>
                    </div>
                    <a href="javascript:void(0);" class="topLinkBtn main_tcdl index_outbtn" >安全退出</a>
                    <a href="?c=game&a=packageList" class="topLinkBtn main_glzx"><em class="em-num"><?php echo $noReadMsg ?></em>管理中心</a>
                    <a href="?c=user&a=receiveBox"><em class="Icon_Mail"></em></a>
                    <!-- 用户信息弹出层 -->

                <!-- 用户信息弹出end -->
                    </div>
                    <div class="displayInbok">
                        <label>可用：</label>
                        <a>
                        <span class="ShowTipsMoney user-NUM" id="nowBalance">￥<?php echo $user['balance']; ?></span><i style="margin-left: 3px;">显示</i>
                        </a>
                    </div>
                    <div class="user-boxs01">
                        <a href="?c=fin&a=pay" class="pay">充值</a>
                        <a href="javascript:void(0);" class="topLinkBtn" id="withdrawMoney">提现</a>
                    </div>
                </div>
            </div>
            <div class="download_sec" style="margin-top:195px">
                <div style="height: 37px;text-align: center;font-size: 14px;cursor: pointer;border-bottom: 2px solid red">
                    苹果APP下载
                </div>
                <!--<div style="width: 136px;height: 37px;float: left;text-align: center;font-size: 14px;cursor: pointer;">
                    Android下载
                </div>-->
            </div>
            <div class="download_icn">
                <div style="position: absolute;width: 273px;padding-top: 17px;">
                    <img src="<?php echo $imgCdnUrl?>/images_fh/new_20171217_iphone.jpg" alt="" style="margin-left: 56px;margin-top: 10px;width: 160px;height: 170px;">
                </div>
                 <!--<div style="position: absolute;width: 273px;padding-top:17px;display: none;">
                    <img src="<?php echo $imgCdnUrl?>/images_fh/float_05r.png" alt="" style="margin-left: 56px;margin-top: 10px;width: 160px;height: 170px;">
                </div>-->
            </div>
        </div>
        <div class="chart">
            <div class="title">
                <h2>走势图</h2>
                <a href="?c=help&a=chart" target="_blank">更多&gt;&gt;</a>
            </div>
            <div class="pic_wp">
                <div class="pic_box">
                    <img src="<?php echo $imgCdnUrl?>/images_600/img11.jpg" alt="">
                </div>
                <div class="links">
                    <p>
                        <span>高频彩</span>
                        <a href="?c=game&a=chart&lottery_id=1" target="_blank">重庆时时彩</a>
                        <a href="?c=game&a=chart&lottery_id=4" target="_blank">新疆时时彩</a>
                        <a href="?c=game&a=chart&lottery_id=8" target="_blank">天津时时彩</a>
                        <a href="?c=game&a=chart&lottery_id=11" target="_blank">幸运分分彩</a>
                        <a href="?c=game&a=chart&lottery_id=18" target="_blank">东京1.5分彩</a>
                        <a href="?c=game&a=chart&lottery_id=23" target="_blank">幸运28</a>
                        <a href="?c=game&a=chart&lottery_id=24" target="_blank">腾讯分分彩</a>
                        <a href="?c=game&a=chart&lottery_id=2" target="_blank">山东11选5</a>
                        <a href="?c=game&a=chart&lottery_id=6" target="_blank">江西11选5</a>
                        <a href="?c=game&a=chart&lottery_id=7" target="_blank">广东11选5</a>
                        <a href="?c=game&a=chart&lottery_id=16" target="_blank">11选5分分彩</a>
                        <a href="?c=game&a=chart&lottery_id=17" target="_blank">北京PK拾</a>
                        <a href="?c=game&a=chart&lottery_id=12" target="_blank">江苏快三</a>
                        <a href="?c=game&a=chart&lottery_id=19" target="_blank">安徽快三</a>
                        <a href="?c=game&a=chart&lottery_id=13" target="_blank">快三分分彩</a>
                        <a href="?c=game&a=chart&lottery_id=14" target="_blank">山东快乐扑克</a>
                        <a href="?c=game&a=chart&lottery_id=25" target="_blank">极速⑥合彩</a>
                    </p>
                    <p>
                        <span>低频彩</span>
                        <a href="?c=game&a=chart&lottery_id=21" target="_blank">香港⑥合彩</a>
                        <a href="?c=game&a=chart&lottery_id=9" target="_blank">福彩3D</a>
                        <a href="?c=game&a=chart&lottery_id=10" target="_blank">体彩P3P5</a>
                        <a href="?c=game&a=chart&lottery_id=22" target="_blank">幸运双色球</a>
                    </p>
                </div>

            </div>
        </div>


    </div>
</div>
<div class="id-lottery cf">
    <div class="id-lt fl">
        <div class="id-lt1">
            <div class="id-lt-gg" id="tab_bar">
                <ul>
                    <li class="edded" id="tab2">最新中奖</li>
                </ul>
            </div>
        </div>
         <!--中奖排行-->
         <div id="tab_content" style="background:white;height: 417px;width: 263px;margin-top: 1px;overflow: hidden;border: 1px solid #F1F1F1;display: block;">
            <div id="move">
            <?php foreach($userWinRank as $v): ?>
                <div style="width: 263px;height: 63px;margin-bottom: 2px;border-bottom:1px dashed #ddd;margin-left: 5%;">
                    <p style="height: 50%;display: flex;align-items: center;">
                        <span style="width: 40%">恭喜 <em style="color:#E4393C;font-weight: 700"><?php echo $v['username']; ?></em></span>
                        <span style="width: 60%">喜中 <em style="color:#E4393C;font-weight: 700"><?php echo $v['prize']; ?></em>元</span>
                    </p>
                     <p style="height: 50%;display: flex;align-items: center;">
                         <span style="width: 40%"><?php echo $v['cname']; ?></span>
                         <span style="width: 60%">第 <em><?php echo $v['issue']; ?></em> 期</span>
                    </p>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!--彩票资讯-->
    <div class="id-cpg fr">
        <div class="cpg-t cf">
            <div class="fl cpg-tt">彩票资讯</div>
            <a href="?c=help&a=latestnew" class="fr dsb">更多>></a>
        </div>
        <div class="cp-xw">
            <div class="cp-xwtt">
                <h1> <a href="?c=help&a=latestnew&article_id=<?php echo $topNotice['article_id'] ?>"><?php echo $topNotice['title'] ?></a></h1>
                <a href="?c=help&a=latestnew&article_id=<?php echo $topNotice['article_id'] ?>" ><?php echo mb_substr($topNotice['content'], 0, 30 ,'utf8') ?>...</a>
            </div>
            <div class="cp-xwlist">
                <div class="fl cpzx">
                <?php foreach($leftPart as $v ): ?>
                    <p style="font-size: 14px;margin-bottom: 10px;color: #676767;line-height: 21px;height: 21px;text-align: left;"><a href="?c=help&a=latestnew&article_id=<?php echo $v['article_id'] ?>"><?php echo $v['category_name'] ?><span>|</span><?php echo $v['title']; ?></a></p>
                <?php endforeach; ?>
                </div>
                <div class="fr cpzx">
                    <?php foreach($rightPart as $v ): ?>
                    <p style="font-size: 14px;margin-bottom: 10px;color: #676767;line-height: 21px;height: 21px;text-align: left;"><a href="?c=help&a=latestnew&article_id=<?php echo $v['article_id'] ?>"><?php echo $v['category_name'] ?><span>|</span><?php echo $v['title']; ?></a></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="id-foot">
    <div class="idf-play">
        <div class="cf" style="width: 100%">
            <!--账户相关-->
            <div class="fl idf-list1">
                <h1>安全相关</h1>
                <p><a href="?c=help&a=safe">如何修改登录密码</a></p>
                <p><a href="?c=help&a=safe">如何修改资金密码</a></p>
                <p><a href="?c=help&a=safe">如何解绑银行资料</a></p>
                <p><a href="?c=help&a=safe">如何修改真实姓名</a></p>
            </div>
            <!--充值购彩-->
            <div class="fl idf-list1">
                <h1>充值购彩</h1>
                <p><a href="?c=help&a=safe#2f">银行卡入款</a></p>
                <p><a href="?c=help&a=safe#2f">线上支付</a></p>
                <p><a href="?c=help&a=safe#2f">存款需知</a></p>
                <p><a href="?c=help&a=safe#2f">充值没到账怎么办</a></p>
            </div>
            <!--兑换提款-->
            <div class="fl idf-list1">
                <h1>兑换提款</h1>
                <p><a href="?c=help&a=safe#3f">取款方法</a></p>
                <p><a href="?c=help&a=safe#3f">取款须知</a></p>
                <p><a href="?c=help&a=safe#3f">锁定银行卡</a></p>
                <p><a href="?c=help&a=safe#3f">提款不成功怎么办</a></p>
            </div>
            <!--在线客服-->
            <div class="fl idf-list1 mar-r">
                <h1>在线客服</h1>
                <p class="idf-list1p">QQ咨询： <a href="tencent://message/?uin=<?php echo getFloatConfig('qq_number'); ?>&Site=sc.chinaz.com&Menu=yes"><?php echo getFloatConfig('qq_number'); ?></a></p>
                <p class="idf-list1p">微信客服：<a  class="onlink-text-cor"><?php echo getFloatConfig('wechat_number'); ?>
                <img class="weixinimg2wm" src="<?php echo $imgCdnUrl?>/images_fh/weixinimg2wm.jpg"></a></p>
                <p class="idf-list1p">在线客服：<a target="_blank" href="<?php echo getFloatConfig('service_url'); ?>" class="duokebo-btn">
                <img class="weixinimg2wm-01" src="<?php echo $imgCdnUrl?>/images_fh/nw_indexbox051.png"></a></p>
                <p><a>在线咨询时间：7*24小时</a></p>
            </div>
        </div>
        <div class="idf-lj cf">
            <a href="javascript:;" class="idf-lj-list1 dsb fl">
                <div class="idf-lj-list1i  fl"></div>
                <div class="idf-lj-list1t fl">账户安全</div>
            </a>
            <a href="javascript:;" class="idf-lj-list1 dsb fl">
                <div class="idf-lj-list1i bg1 fl"></div>
                <div class="idf-lj-list1t  fl">购彩便捷</div>
            </a>
            <a href="javascript:;" class="idf-lj-list1 dsb fl">
                <div class="idf-lj-list1i bg2 fl"></div>
                <div class="idf-lj-list1t  fl">存款简单</div>
            </a>
            <a href="javascript:;" class="idf-lj-list1 last dsb fl">
                <div class="idf-lj-list1i bg3 fl"></div>
                <div class="idf-lj-list1t fl">提款迅速</div>
            </a>
        </div>
    </div>
</div>
<!--<div class="qhb" style="position:fixed;bottom:2px;left:2px;" >
<a  href="http://hb87388.com/" target="view_window">
	<img src="<?php echo $imgCdnUrl?>/images_fh/qhb.png" style="width:180px;height:180px;">
</a>
<span style="font-size:28px;position:absolute;top:0px;right:0;cursor:pointer ;">X</span>
</div>-->
<?php $this->import('public_foot') ?>
<div class="bgindex01"><img src="<?php echo $imgCdnUrl?>/images_fh/indexbg.jpg"></div>
</div>
<script src="<?php echo $imgCdnUrl ?>/js/jqueryUI/dialog/js/jquery.dialog.js" type="text/javascript"></script>
<!-- <div class="bgindex01"><img src="<?php echo $imgCdnUrl?>/images_fh/indexbg.jpg"></div> -->
<script src="<?php echo $imgCdnUrl ?>/js_fh/fun.js?v=<?php echo $html_version ?>"></script>
<script src="<?php echo $imgCdnUrl ?>/js_fh/login.js"></script>
<script src="<?php echo $imgCdnUrl ?>/js/index.js"></script>
<!--弹窗-->
<script type="text/javascript">
	       //抢红包弹窗
//     $(".qhb>span").click(function(){
//     	$(".qhb").css("display","none")
//     })
//          //检测是否游客身份并提示修改账号
            <?php if($user['is_tourist'] == 1): ?>
                layer.prompt({title: '重置您的账号', formType: 3}, function(username, index){
                    layer.close(index);
                    layer.prompt({title: '重置登陆密码', formType: 1}, function(pwd, index){
                        layer.close(index);
                        if(/^[a-zA-Z]\w{5,11}$/i.test(username) == false){
                            // layer.msg("请输入正确6-12位用户名");
                            layer.alert('用户名长度为6-12个字母或数字，且必须以字母开头', {
                                icon: 1,
                                skin: 'layer-ext-moon'
                            },function() {
                                location.reload();
                            });

                            return false;
                        }
                        $.ajax({
                            type: "POST",
                            url: "index.jsp?c=user&a=editPwd",
                            data: 'editTourist=1&username='+username+'&pwd='+pwd,
                            dataType: "json",
                            success: function(res) {
                                if(res.errno == 0){
                                    $('.ShowTipsUserInfo').html('您好，' + username);

                                    layer.alert(res.errstr,
                                    {
                                        icon: 1,
                                        skin: 'layer-ext-moon'
                                    });
                                }
                                else {
                                    layer.alert(res.errstr,
                                    {
                                        icon: 1,
                                        skin: 'layer-ext-moon'
                                    }, function() {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                layer.msg("网络异常");
                                location.reload();
                            }
                        });
                    });
                });
            <?php endif; ?>
    $(".index_outbtn").click(function() {
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0, //不显示关闭按钮
            shade: false,
            area: ['604px', '290px'],
            skin: 'layui-layer-molv', //样式类名
            shade: [0.7, '#000'],
            time: 5000, //5秒后自动关闭
            anim: 2,
            content:'<div class="mian_out"><img src="<?php echo $imgCdnUrl?>/images_fh/img_outbg.png"><a class="mian_colosbtn layui-layer-close" href="javascript:void(0);"><img src="<?php echo $imgCdnUrl?>/images_fh/img_btn01.png"></a><a class="mian_outbtn" href="index.jsp?a=logout" id="logoutBtn"><img src="<?php echo $imgCdnUrl?>/images_fh/img_btn02.png"></a></div>',
        });
    });
    // $('.duokebo-btn').click(function() {
    //     top.layer.open({
    //         type: 2,
    //         title: '中彩网',
    //         anim: 2,
    //         offset: ['100px', ''],
    //         fixed: false, //不固定
    //         shade: 0, //不显示遮罩
    //         shadeClose: true, //开启遮罩关闭
    //         area: ['500px', '500px'],
    //         content:'<?php echo getFloatConfig('service_url') ?>',
    //     });
    // });
</script>
<?php if ($userAlert): ?>
    <script type="text/javascript">
        layer.open({
            type: 1,
            title: '<?php echo $userAlert['title'];?>：<?php echo $userAlert['domain'];?>',
            closeBtn: 2, //不显示关闭按钮
            shade: [0],
            area:['600px','395px'],
            <?php if($userAlert['type'] == userAlert::TYPE_TEXT)echo 'area: [\'600px\', \'395px\'],'; ?>
            style: 'background-color',
            shade: [0.3, '#000'],
            anim: 2,
            content: '<div class="layerbox01"><?php
                if ($userAlert['type'] == userAlert::TYPE_IMAGE) {
                    echo '<a href="?c=help&a=platformact"><img src="' . $imgCdnUrl .'/'. $userAlert['main_img'] . '" /></a>';
                }elseif($userAlert['type'] == userAlert::TYPE_TEXT){
                    echo $userAlert['content'];
                }
                ?></div>',
        });
    </script>
<?php endif; ?>
</body>
</html>
