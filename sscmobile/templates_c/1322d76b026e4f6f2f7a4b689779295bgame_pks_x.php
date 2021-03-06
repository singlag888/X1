<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="webkit" name="renderer"><!-- 页面默认用极速核 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- 指定浏览器按照最高的标准模式解析页面针对IE -->
    <meta content="telephone=no,email=no" name="format-detection"/><!-- 使设备浏览网页时对数字不启用电话功能 -->
    <meta name="apple-touch-fullscreen" content="YES"/><!-- "添加到主屏幕"后，全屏显示 -->
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <!-- 如果内容设置为YES，Web应用程序运行在全屏模式;否则，它不会。默认行为是使用Safari浏览器显示网页内容 -->
    <!--<meta http-equiv="Cache-Control" content="no-cache"/>-->  <!-- 每次打开都清除浏览器页面缓存 -->
    <meta http-equiv="Cache-Control" content="no-siteapp"/><!-- 度SiteApp转码声明 -->
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo config::getConfig('site_title'); ?></title>
    <link rel="stylesheet" type="text/css"
          href="<?php echo $imgCdnUrl ?>/css/mobileStyle_x.css?v=<?php echo $html_version; ?>">
    <link rel="stylesheet" type="text/css"
          href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle_x.css?v=<?php echo time(); ?>">
    <style type="text/css">
        html, body {
            width: 100%;
            height: 100%;
            position: absolute;
        }

        #total {
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0);
            position: absolute;
            top: 4.3rem;
            display: none;
            z-index: 99999;
        }
    </style>
</head>
<body>
<!--/*头部*/-->
<header id="firstHeader" class="headerbg">
    <a class="headbox01" href="?c=game&a=lobby">游戏大厅</a>
    <!--<p class="headtetle">北京PK拾[信]</p>-->
    <p class="headtetle">北京PK拾</p>
    <!--<a class="headboxright" href="?c=game&a=packageList" type="button">投注记录</a>-->
    <a class="headboxright" type="button">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/button_icon.png" alt="">
    </a>
</header>
<div class="top-box">
    <div>
        <div class="Gamepart">
            <div class="NumberBox5">
                <div class="bjpkLottCont">
                    <div class="GameName">
                        <div class="fix">
                            <label>第<em id="lastIssueSpan"></em>期</label>
                        </div>
                    </div>
                    <div class="lotteryNum" id="thisIssueNumUL"></div>
                </div>
                <div class="playTimer thisIssueInfo" id="thisIssueInfo">
                    <span class="issue">距离下次开奖</span>
                    <div class="Timer fix">
                                    <span class="thisIssueRemainTime" id="thisIssueRemainTime">
                                        <span>00</span><em>:</em><span>00</span><em>:</em><span>00</span>
                                    </span>
                    </div>
                </div>
            </div>
            <div class="moreGame" id="todayDrawBtn">
                <!--
                    <a class="SingleBtn" id="inputBtn" href="javascript:void(0);">
                    <span></span>
                    <label>手工录入</label>
                </a> -->
                <button class="question ShowTips methodTipInfo" id="methodTipInfo">玩法介绍</button>
                <p class="GameText"><span class="playingMethod" id="playingMethod"></span><b class="hz_lmlx">赔率：<b
                                class="rebateValue_lmlx"></b></b></p>
                <button id="moreGame" class="paright">更多玩法<img
                            src="<?php echo $imgCdnUrl ?>/images/mobile/arrows_right_02.png"/></button>
            </div>
            <div class="methodDesc" id="methodDesc"></div>
        </div>
        <!--下拉菜单-->
        <div class="icon-list" style="margin-top: 0.875rem;display: none;">
            <a href="?c=game&a=packageList">个人中心</a>
            <a href="?c=game&a=packageList">投注记录</a>
            <a href="?c=game&a=chart&lottery_id=<?php echo $lottery['lottery_id'] ?>">开奖走势</a>
            <a href="?c=fin&a=pay" style="border-bottom: none;">快速充值</a>
        </div>
    </div>
</div>

<!-- 中间选择游戏部分 -->
<div class="GameBoxall SubGamePlatePadding GameBoxall-new01">
    <div class="subTopBarPK DisplayNone">
        <div class="playNav">
            <ul class="lotteryTab pks_x_displayNone" id="methodGroupContainer"></ul>
        </div>
        <div class="crumbs"></div>
    </div>
    <!--关闭玩法背景-->
    <div class="wanfa_bg"></div>
    <!-- 投注主体部分 -->
    <div class="PlayCenter fix">
        <div class="playControlBox">
            <!-- 投注选号 -->
            <div class="choMainTab">
                <div class="chooseNO selectArea" id="selectArea"></div>
                <div class="MachineSele">
                    <?php if (in_array($lottery['lottery_id'], array(1, 3, 4, 8, 11))): ?>
                        <div class="MachineSeleBtn">
                            <input type="button" value="机选10注" num="10" class="Mach10 custBtnStyle selectRandomBtn">
                            <input type="button" value="机选50注" num="50" class="Mach50 custBtnStyle selectRandomBtn">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div id="basic_slider"></div>
            <div class="siale_dandian" style="display: none;">
                <b class="f-left">奖金/返点</b>
                <select id="curPrizeSpan" class="f-left"></select>
            </div>
            <!-- 选定按钮 -->
            <div class="FatherCodeBtn01">
                <!--<a href="?c=game&a=bjpks" class="qiehuan_g">切换官方</a>-->
                <!--显示注数 -->
                <div class="xfootbox01">
                    <!--奖金拉动条-->
                    <div class="bonusSlide fix" style="float:left;">
                        <!-- <span>奖金/返点</span> -->
                        <div id="selectRebate" class="selectRebate"></div>
                        <span id="rebateValue" class="rebateValue"></span>
                    </div>
                    <div class="important_font_box">
                        <span class="important_font" id="betCount">0</span>注
                    </div>
                </div>
                <div class="xfootbox02">
                    <a class="qiehuan_x" id="qiehuan"></a>
                    <a href="javascript:void(0)" class="custBtnStyle" id="clearProjectBtn" title="删除投注内容">清空</a>
                    <div class="btnGroup" style="display: inline-block">
                        <input value="" placeholder="投注金额" id="multiple" name="multiple" class="injine_x" class="form-control" maxlength="5" type="number" pattern="[0-9]*"/>
                    </div>
                    <!-- <button class="confirmBtn_x" id="confirmBtn">投注</button> -->
                    <button class="confirmBtn_g" id="selectCodeBtn">投注</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--下一页-->
<div style="display: none;"><!--删掉样式class="betPage chooseOKBtnPK  DisplayNone" -->
    <!--/*头部*/-->
    <header id="firstHeader" class="headerbg">
        <a class="headbox01" href="javascript:history.go(-1)"><img
                    src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
        <p class="headtetle">北京PK拾</p>
        <a class="headboxright" href="?c=game&a=packageList" type="button">投注记录</a>
    </header>
    <div class="mutiChoose">
        <dl>
            <dt>
            <div class="projectListTitle fix">
                <span class="width1">玩法</span>
                <span class="width2">号码</span>
                <span class="width3">注数</span>
                <span class="width4">倍/元</span>
            </div>
            </dt>
            <dd>
                <div class="projectListTitle fix"><span class="width1">玩法</span><span class="width2">号码</span><span
                            class="width3">注数</span><span class="width4">倍/元</span></div>
                <ul class="projectList" id="projectList">
                </ul>
            </dd>
        </dl>
    </div>
    <div class="Selectnumber fix">
        <div class="moneySlide">
            <!--displayNone-->
            <div class="SingleInfo">
                <div class="bor-top01">
                    <div class="siale_dandian">
                        <b class="f-left">奖金/返点</b>
                        <select id="curPrizeSpan" class="f-left"></select>
                        <!-- <span id="rebateValue" class="rebateValue"></span> -->
                    </div>
                    <div class="gameTZbtn">
                        <a href="javascript:void(0)" class="del clearProjectBtn" id="clearProjectBtn"
                           title="删除投注内容">清空</a>
                        <?php if ($lottery['lottery_id'] != 9 && $lottery['lottery_id'] != 10): ?>
                            <input type="button" value="追号" class="btnStyle m05 Chasing_ball" id="traceBtn" mark="0"/>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
        <div class="displayBtn DisplayNone">
            <div class="gameLeftLI" id="singleInfo">
                已选注
                <input type="hidden" id="modesDIV"value="1">
            </div>
        </div>
    </div>
    <!--displayNone-->
    <div class="totalcont fix" style="display: none;">
        <div class="totalText">
            注数：<b>注</b><em id="totalBetCount">0</em>
            <br/>
            总计：<b>元</b><em id="totalBetAmount">0.00</em>
            <br>
            最高盈利：<b>元</b><em id="totalWin">0.00</em>
            <br>
            余额: <b>元</b>
            <em class="ShowTipsMoney" id="nowBalance"><?php echo $GLOBALS['SESSION']['balance']; ?></em>
        </div>
    </div>
    <div class="totalBtn MarginTop05">
        <input type="button" value="确认投注" class="CantapCodeBtn" id="confirmBtn_g"/>
    </div>
    <!-- 添加号码 选择按钮 -->
    <div class="chooseOKBtn" style="display:none;">
        <div class="fr">
            <input type="hidden" value="" id="token">
        </div>
    </div>
</div>
<div id="total">
    <!--最近奖期开奖号-->
    <div class="theme-popover-mask">
        <div class="theme-popover">
                    <span class="sp1">
                        <span class="sp2"></span>
                    </span>
            <div class="theme-poptit">
                <ul class="lotteryTodayTitle">
                    <li>奖期</li>
                    <li>开奖号</li>
                </ul>
            </div>
            <div>
                <div class="lotteryTodayBox">
                    <div class="lotteryTodayContent bet" id="todayIssuesBody"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--任意区域弹出框消失-->
<div class="index-box" hidden>
</div>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/qiehuangame.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.nouislider.min.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.jodometer.js"></script><!-- 奖池数字滚动插件 -->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/common.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script> <!-- 调用弹出层 -->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/ext.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/game/min/game_pks_x.min.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript">
    $('#logoutBtn').click(function () {
        location.href = '?a=logout';
    });

    //用户弹出层
    /*$(".CloseUser").click(function() {
     $(".UserPopLayer").toggle(150);
     });
     $(".User .name").click(function() {
     $(".UserPopLayer").toggle(150);
     });
     */
    $(function () {
        var methods = <?php echo $methods ?>;
        $.init({
            lotteryId: <?php echo $lottery['lottery_id']; ?>,
            lotteryName: '<?php echo $lottery['cname']; ?>',
            property_id:<?php echo $lottery['property_id']; ?>,
            prizeRate: <?php echo 1 - $lottery['total_profit']; ?>,
            lotteryType: <?php echo $lottery['lottery_type']; ?>,
            methods: methods[<?php echo $lottery['lottery_id'] ?>],
            maxCombPrize: <?php echo $maxCombPrize; ?>,
            openedIssues: <?php echo $json_openedIssues; ?>,
            minRebateGaps: <?php echo $minRebateGaps; ?>,
            rebate: <?php echo $rebate; ?>,
            defaultMode: 1,
            defaultRebate: <?php echo $rebate; ?>,
            missHot: <?php echo $json_missHot; ?>
        });

// 手机端JS 分割线-----------------------------------------------------------------------------------------
        $(".methodPopStyle li").each(function (i) {
            $(".methodPopStyle li").eq(i).click(function () {
                $(".methodPopStyle li").eq(i).addClass("methodselected").siblings().removeClass("methodselected");
                $(".subTopBarPK").removeClass("DisplayBlock");
            })
        })
        //元角分 字体颜色
        /*$(".moneyMode a").each(function(i){
         $(".moneyMode a").eq(3).click();
         $(".moneyMode a").eq(i).click(function(){
         $(".moneyMode a").eq(i).addClass("bgcolor").siblings().removeClass("bgcolor");
         })
         })*/
//滑块---------------------------------------------------------------------------------------------------------
//滑块开始-返点大于0时
        window.onload = function () {
            var somax = ($('#curPrizeSpan option').length - 1);
            if (somax > 0) {
                var rangeSlider = document.getElementById('selectRebate');
                noUiSlider.create(rangeSlider, {
                    start: ($('#curPrizeSpan option').length - 1),
                    step: 1,
                    connect: true,
                    range: {
                        'min': 0,
                        'max': ($('#curPrizeSpan option').length - 1),
                    },
                    format: {
                        to: function (value) {
                            return value + '';
                        },
                        from: function (value) {
                            return value.replace('', '');
                        }
                    }
                });
                rangeSlider.noUiSlider.on('update', function (value) {
                    //rangeSliderValueElement.innerHTML = values[handle]+'%';
                    var $curPrizeSpan = $('#curPrizeSpan option').eq(value).html();

                    $('#curPrizeSpan option').removeAttr('selected');
                    $('#curPrizeSpan option').eq(value).attr('selected', true);
                    $('#rebateValue').html($curPrizeSpan.split('/')[1]);
                    $('.rebateValue_lmlx').html($curPrizeSpan.split('/')[0]);
                    $('.rebateValue1').html($curPrizeSpan.split('/')[0]);
                    $('.rebateValue1_1').attr('placeholder', $curPrizeSpan.split(';')[0]);
                    $('.rebateValue1_2').attr('placeholder', $curPrizeSpan.split(';')[1]);
                    $('.rebateValue1_3').attr('placeholder', $curPrizeSpan.split(';')[2]);
                    $('.rebateValue1_4').attr('placeholder', $curPrizeSpan.split(';')[3]);
                    $('.rebateValue1_5').attr('placeholder', $curPrizeSpan.split('/')[0].split(';')[4]);

                    // 冠亚和值大小单双
                    if($curPrizeSpan.split(';').length >= 4){
                        var $rebateValueGYDXDS = $('.rebateValueGYDXDS');
                        $rebateValueGYDXDS.eq(0).attr('placeholder', $curPrizeSpan.split(';')[0]);
                        $rebateValueGYDXDS.eq(1).attr('placeholder', $curPrizeSpan.split(';')[1]);
                        $rebateValueGYDXDS.eq(2).attr('placeholder', $curPrizeSpan.split(';')[2]);
                        $rebateValueGYDXDS.eq(3).attr('placeholder', $curPrizeSpan.split(';')[3].split('/')[0]);
                    }

                    $('#curPrizeSpan').change();
                });
            } else {
                $('#selectRebate').hide();
                $('#rebateValue').hide();
            }
            ;
        };//滑块结束
//end-----------------------------------------------------------------------

        // 默认玩法文本一次
        $(".GameText span").html('前五两面');

        // 更多玩法点击
        $("#moreGame").click(function () {
            if ($('.subTopBarPK').hasClass('DisplayBlock')) {
                $('.playControlBox').removeClass("DisplayNone").addClass('DisplayBlock');
                $('p.GameText').removeClass("DisplayNone").addClass('DisplayBlock');
                $(".subTopBarPK").removeClass("DisplayBlock");
                $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
            } else {
                $('.playControlBox').removeClass("DisplayBlock").addClass('DisplayNone');
                $('p.GameText').removeClass("DisplayBlock").addClass('DisplayNone');
                $(".subTopBarPK").addClass("DisplayBlock");
                $(".wanfa_bg").removeClass("DisplayNone").addClass('DisplayBlock');
            }
        });
        // 点击玩法选择  文本切换
        $(".methodGroupLI li").live("click", function () {
            $(".playToolTipBox").removeClass("DisplayBlock");
            $('.playControlBox').addClass('DisplayBlock');
            $(".GameText span").html($(this).html());
            $('p.GameText').removeClass('DisplayNone').addClass('DisplayBlock');
            $('#playingMethod').html($(this).html());
            $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
        });
        $(".wanfa_bg").live("click", function () {
            $(".playToolTipBox").removeClass("DisplayBlock");
            $('.playControlBox').addClass('DisplayBlock');
            $(".subTopBarPK").addClass("DisplayNone").removeClass("DisplayBlock");
            $('p.GameText').removeClass('DisplayNone').addClass('DisplayBlock');
            $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
        });
    });
    //弹框
    $(function () {
        $('.NumberBox5').click(function () {
            $('#total').slideToggle(200);
        });
        $('#total').click(function () {
            $(this).hide();
        });

    });

    //头部点击弹出菜单
    $('.headboxright').click(function () {
        $('.icon-list').toggle();
        $('.index-box').show();
    });
    $('.index-box').click(function () {
        $('.icon-list').hide();
        $('.index-box').hide();

    });


</script>

<?php $this->import('public_tongji') ?>
</body>
</html>
