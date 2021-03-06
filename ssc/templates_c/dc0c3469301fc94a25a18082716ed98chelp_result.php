<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="yes" name="apple-touch-fullscreen">
    <meta content="telephone=no,email=no" name="format-detection">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>购彩大厅-<?php echo config::getConfig('site_title'); ?></title>
    <?php $this->import('public_cssjs') ?>
</head>
<body>
<div class="big-box">
    <!--头部-->
    <?php $this->import('public_header') ?>
    <!--主要main-->
    <div class="ls Result Resultt" id="Result">
        <div class="ls-play result_play">
            <div class="ls-text cf">
                <div class="fl on" onclick="lsSelect(this)">全部彩种</div>
                <div class="fl" onclick="lsSelect(this)">高频彩</div>
                <div class="fl" onclick="lsSelect(this)">低频彩</div>
                <div class="fl" onclick="lsSelect(this)">地方彩种开奖</div>
                <div class="fl" onclick="lsSelect(this)">境外彩种开奖</div>
                <div class="fl" onclick="lsSelect(this)">本站品牌</div>
            </div>

            <div>
                <!--全部彩种-->
                <div class="ls-logo">
                    <!--全部彩种分类选项卡-->
                    <div class="wrap_select at corl1 kjjg_hz_style" id="subNav_1">
                        <div>
                            <a data-id=0 class="acti selectBtn">汇总</a>
                            <a data-id=1 class="selectBtn">重庆时时彩</a>
                            <a data-id=8 class="selectBtn">天津时时彩</a>
                            <a data-id=4 class="selectBtn">新疆时时彩</a>
                            <a data-id=3 class="selectBtn">黑龙江时时彩</a>
                            <a data-id=21 class="selectBtn">香港⑥合彩</a>
                            <a data-id=22 class="selectBtn">双色球</a>
                            <a data-id=17 class="selectBtn">北京PK拾</a>
                            <a data-id=23 class="selectBtn">幸运28</a>
                            <a data-id=11 class="selectBtn">幸运分分彩</a>
                            <a data-id=18 class="selectBtn">东京1.5分彩</a>
                            <a data-id=2  class="selectBtn">山东11选5</a>
                            <a data-id=7 class="selectBtn">广东11选5</a>
                            <a data-id=16 class="selectBtn">11选5分分彩</a>
                            <a data-id=6 class="selectBtn">江西11选5</a>
                            <a data-id=5 class="selectBtn">江苏11选5</a>
                            <a data-id=9 class="selectBtn">福彩3D</a>
                            <a data-id=10 class="selectBtn">体彩P3P5</a>
                            <a data-id=12 class="selectBtn">江苏快三</a>
                            <a data-id=19 class="selectBtn">安徽快三</a>
                            <a data-id=13 class="selectBtn">快三分分彩</a>
                            <a data-id=24 class="selectBtn">腾讯分分彩</a>
                            <a data-id=14 class="selectBtn">山东快乐扑克</a>
                            <a data-id=25 class="selectBtn">极速⑥合彩</a>
                            <a data-id=26 class="selectBtn">幸运飞艇</a>
                        </div>
                    </div>
                    <!--全部彩种列表-->
                    <div class="code_list theLottery" style="display: table;">
                        <ul class="lotteryUl">
                        </ul>
                    </div>
                    <!--奖期开奖号码-->
                    <div class="code_list codeList mt2 ds">
                    </div>
                </div>
                <!--高频彩-->
                <div class="ls-logo ds cf">
                    <!--高频彩分类选项卡-->
                    <div class="wrap_select at corl1 kjjg_hz_style" id="subNav_2">
                        <div>
                            <a class="selectBtn" data-id=1>重庆时时彩</a>
                            <a class="selectBtn" data-id=8>天津时时彩</a>
                            <a class="selectBtn" data-id=4>新疆时时彩</a>
                            <a class="selectBtn" data-id=3>黑龙江时时彩</a>
                            <a class="selectBtn" data-id=17>北京PK拾</a>
                            <a class="selectBtn" data-id=23>幸运28</a>
                            <a class="selectBtn" data-id=11>幸运分分彩</a>
                            <a class="selectBtn" data-id=18>东京1.5分彩</a>
                            <a class="selectBtn" data-id=2>山东11选5</a>
                            <a class="selectBtn" data-id=7>广东11选5</a>
                            <a class="selectBtn" data-id=16>11选5分分彩</a>
                            <a class="selectBtn" data-id=6>江西11选5</a>
                            <a class="selectBtn" data-id=5 >江苏11选5</a>
                            <a class="selectBtn" data-id=12>江苏快三</a>
                            <a class="selectBtn" data-id=19>安徽快三</a>
                            <a class="selectBtn" data-id=13>快三分分彩</a>
                            <a class="selectBtn" data-id=24>腾讯分分彩</a>
                            <a class="selectBtn" data-id=14>山东快乐扑克</a>
                        </div>
                    </div>
                    <!--高频彩种分类列表-->
                    <div class="code_list theLottery" style="display: table;">
                        <ul class="lotteryUl">
                        </ul>
                    </div>
                </div>
                <!--低频彩-->
                <div class="ls-logo ds cf">
                   <!--低频彩分类选项卡-->
                    <div class="wrap_select at corl1 kjjg_hz_style" id="subNav_3">
                        <div>
                            <a class="selectBtn" data-id=21>香港⑥合彩</a>
                            <a class="selectBtn" data-id=22>双色球</a>
                            <a class="selectBtn" data-id=9>福彩3D</a>
                            <a class="selectBtn" data-id=10>体彩P3P5</a>
                        </div>
                    </div>
                    <!--低频彩列表-->
                    <div class="code_list theLottery" style="display: table;">
                        <ul class="lotteryUl">
                        </ul>
                    </div>
                </div>
                <!--地方彩种开奖-->
                <div class="ls-logo ds cf">
                    <!---地方彩种分类选项卡-->
                    <div class="wrap_select at corl1 kjjg_hz_style" id="subNav_5">
                        <div>
                            <a class="selectBtn" data-id=1>重庆时时彩</a>
                            <a class="selectBtn" data-id=8>天津时时彩</a>
                            <a class="selectBtn" data-id=3>黑龙江时时彩</a>
                            <a class="selectBtn" data-id=4>新疆时时彩</a>
                            <a class="selectBtn" data-id=17>北京PK拾</a>
                            <a class="selectBtn" data-id=2>山东11选5</a>
                            <a class="selectBtn" data-id=7>广东11选5</a>
                            <a class="selectBtn" data-id=6>江西11选5</a>
                            <a class="selectBtn" data-id=5>江苏11选5</a>
                            <a class="selectBtn" data-id=12>江苏快三</a>
                            <a class="selectBtn" data-id=19>安徽快三</a>
                            <a class="selectBtn" data-id=14>山东快乐扑克</a>
                        </div>
                    </div>
                    <!--地方彩种列表-->
                    <div class="code_list theLottery" style="display: table;">
                        <ul class="lotteryUl">
                        </ul>
                    </div>
                </div>
                <!--境外彩种开奖-->
                <div class="ls-logo ds cf">
                    <!--境外彩种选项卡-->
                    <div class="wrap_select at corl1 kjjg_hz_style" id="subNav_6">
                        <div>
                            <a class="selectBtn" data-id=21>香港⑥合彩</a>
                            <a class="selectBtn" data-id=18>东京1.5分彩</a>
                            <a class="selectBtn" data-id=26>幸运飞艇</a>
                        </div>
                    </div>
                    <!--境外彩种列表-->
                    <div class="code_list theLottery" style="display: table;">
                        <ul class="lotteryUl">
                        </ul>
                    </div>

                </div>
                <!--本站品牌-->
                <div class="ls-logo ds cf">
                    <!--本站品牌分类选项卡-->
                    <div class="wrap_select at corl1 kjjg_hz_style" id="subNav_7">
                        <div>
                            <a class="selectBtn" data-id=11>幸运分分彩</a>
                            <a class="selectBtn" data-id=13>快三分分彩</a>
                            <a class="selectBtn" data-id=22>双色球</a>
                            <a class="selectBtn" data-id=23>幸运28</a>
                            <a class="selectBtn" data-id=16>11选5分分彩</a>
                            <a class="selectBtn" data-id=25>极速⑥合彩</a>
                        </div>
                    </div>
                    <!--本站品牌分类列表-->
                     <div class="code_list theLottery" style="display: table;">
                        <ul class="lotteryUl">
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $this->import('public_foot') ?>
</div>
<div class="qhb" style="position:fixed;bottom:2px;left:2px;" >
<a  href="http://hb87388.com/">
	<img src="<?php echo $imgCdnUrl?>/images_fh/qhb.png" style="width:180px;height:180px;">
</a>
<span style="font-size:28px;position:absolute;top:0px;right:0;cursor:pointer ;">X</span>
</div>
</body>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script><!-- 调用弹出层 -->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/template-web.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/help_result.js"></script>
<script>
	//抢红包弹窗
//	      window.onload = function(){ 
//　　		$(".qhb").animate({left:'2px'},10000);
//     } 
       $(".qhb>span").click(function(){
       	$(".qhb").css("display","none")
       })
   $(function(){
        //>>隐藏或者显示彩种
        var keys = JSON.parse('<?php echo $keys; ?>');
        $($('.acti').siblings()).each(function () {
            var id = $(this).data('id');
            if($.inArray(id, keys) == -1 )
            {
                $('.selectBtn[data-id=' + id + ']').hide();
            }
        })


    })
</script>
<!--汇总模板-->
<script type="text/html" id="template_model">
    {{each list value $lotteryId}}
    <li>
        <div class="box1">
            <var class="no"></var>
            <div class="text">
                <h3>{{value.lastIssueInfo.cname}}</h3>
                <div class="dt-wq">
                    <span class="on">第{{value.lastIssueInfo.issue}}期</span>
                </div>
            </div>
        </div>
        <div class="box2">
            <ul class="box_num">
                {{each codes[$lotteryId] codeItem}}
                {{#codeItem}}
                {{/each}}
            </ul>
        </div>
        <div class="box3">
            <a href="?c=game&a=chart&lottery_id={{$lotteryId}}" target="_blank">走势图表</a>
        </div>
        <div class="box4">
            <a href="{{value.fun}}" target="_blank">立即投注</a>
        </div>
    </li>
    {{/each}}
</script>
<script type="text/html" id="template_head">
    <li class="head">
        <span class="sp1">彩种</span>
        <span class="sp2">奖期</span>
        <span class="sp3">开奖时间</span>
        <span class="sp4">开奖号码</span>
        <span class="sp5">期数(每天)</span>
        <span class="sp6">开奖频率</span>
        <span class="sp7">走势</span>
        <span class="sp8">购彩</span>
    </li>
</script>
<script type="text/html" id="template_detail">
{{each list value $i}}
    <li id="LotteryId_0">
        <div class="box5">
            <p class="sp1">
                <a>{{lotteryData[lotteryId].cname}}</a>
            </p>
        </div>
        <div class="box6">
            <span class="sp2">第 {{value.issue}} 期</span>
        </div>
        <div class="box7">
            <span class="sp3">
                <p class="Ltime">{{value.end_sale_time}}</p>
            </span>
        </div>
        <div class="box8">
            <span class="sp4">
                <ul class="code_num">
                    {{each codes[$i] codeItem}}
                    {{#codeItem}}
                    {{/each}}
                </ul>
            </span>
        </div>
        <div class="box9">
            <span class="sp5">
                <p>{{lotteryData[lotteryId].lotteryTimes}}</p>
            </span>
        </div>
        <div class="box10">
            <span class="sp6">
                <p>{{lotteryData[lotteryId].openTimes}}</p>
            </span>
        </div>
        <div class="box11">
            <span class="sp7">
                <a href="?c=game&a=chart&lottery_id={{lotteryId}}" target="_blank">走势图表</a>
            </span>
        </div>
        <div class="box12">
            <span class="sp8">
                <a href="{{lotteryData[lotteryId].fun}}" target="_blank">立即投注</a>
            </span>
        </div>
    </li>
    {{/each}}
</script>

</html>