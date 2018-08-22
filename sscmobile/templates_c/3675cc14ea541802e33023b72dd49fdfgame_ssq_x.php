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
          href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle_x.css?v=<?php echo $html_version; ?>">
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
<header class="headerbg">
    <a class="headbox01" href="?c=game&a=lobby">游戏大厅</a>
    <p class="headtetle">幸运双色球</p>
    <!--<a class="headboxright" href="?c=game&a=packageList" type="button">投注记录</a>-->
    <a class="headboxright" type="button">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/button_icon.png" alt="">
    </a>
</header>
<div class="top-box">
    <div class="NumberBox5">
        <div class="Lottmain">
            <div class="GameName">
                <label>第<em id="lastIssueSpan" class="lastIssueSpan"></em>期</label>
            </div>
            <div class="lotteryNum GameNuberFont numssq" id="thisIssueNumUL"></div>
            <div id="original_code" style="display:none"></div>
        </div>
        <div class="playTimer thisIssueInfo" id="thisIssueInfo">
            <p class="thisIssue">
                <span class="issue">距离下次开奖</span>
            </p>
            <span class="Timer fix">
                    <div class="thisIssueRemainTime" id="thisIssueRemainTime">
                    <span>00</span><span>:</span><span>00</span><em>:</em><span>00</span><em>:</em><span>00</span>
                    </div>
                    </span>
        </div>
    </div>

    <!-- 中间选择游戏部分 -->
    <div class="moreGame">
        <button class="question ShowTips methodTipInfo" id="methodTipInfo">玩法介绍</button>
        <p class="GameText"><span class="playingMethod" id="playingMethod"></span>赔率:<em class="gamesPrize"
                                                                                         id="gamesPrize"></em></p>
        <button id="moreGame" class="paright" style="display: none;">更多玩法<img
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
<div class="GameBoxall SubGamePlatePadding GameBoxall-new01">
    <div class="Gamepart">
        <div class="subTopBar">
            <!-- 投注玩法 -->
            <div class="playToolTipBox DisplayNone">
                <div class="playNav">
                    <ul class="lotteryTab" id="methodGroupContainer">

                    </ul>
                </div>
            </div>
        </div>
        <div class="clear"></div>
        <!-- 投注主体部分 -->
        <div class="PlayCenter">
            <div class="playControlBox">
                <div class="clear"></div>
                <!-- 投注选号 -->
                <div class="choMainTab">
                    <div class="chooseNO selectArea" id="selectArea"></div>
                    <!--<div class="MachineSele">
                                <?php /*if (in_array($lottery['lottery_id'], array(1, 3, 4, 8, 11))): */ ?>
                                <div class="MachineSeleBtn">
                                    <input type="button" value="机选10注" num="10" class="Mach10 custBtnStyle selectRandomBtn">
                                    <input type="button" value="机选50注" num="50" class="Mach50 custBtnStyle selectRandomBtn">
                                </div>
                                <?php /*endif;*/ ?>
                            </div>-->
                </div>
                <div class="clear"></div>
                <div class="FatherCodeBtn01">
                    <div class="xfootbox01">
                        <div class="important_font_box">
                            <span class="important_font" id="betCount">0</span>注
                        </div>
                        <!-- <div id="basic_slider"></div>
                        <div class="siale_dandian" style="display: none;">
                            <b class="f-left">奖金/返点</b>
                            <select id="curPrizeSpan" class="f-left"></select>
                        </div> -->
                    </div>
                    <div class="xfootbox02">
                        <a class="qiehuan_x" id="qiehuan"></a>
                        <button class="custBtnStyle" id="clearProjectBtn">清空</button>
                        <div class="btnGroup" style="display: inline-block;">
                        <input value="" placeholder="投注金额" id="multiple" name="multiple" class="txtStyle txtaddSty injine_x"
                               class="form-control" maxlength="5" type="number" pattern="[0-9]*"/>
                        </div>
                        <button class="selectCodeBtn01" id="selectCodeBtn" style="display: none">选定号码</button>
                        <button class="confirmBtn_g" id="confirmBtn">投注</button>
                    </div>
                    <!--<div class="MachineSele">
                                <?php /*if (in_array($lottery['lottery_id'], array(1, 3, 4, 8, 11))): */ ?>
                                <div class="MachineSeleBtn">
                                    <input type="button" value="机选10注" num="10" class="Mach10 custBtnStyle selectRandomBtn">
                                </div>
                                <?php /*endif;*/ ?>
                            </div>-->
                </div>

            </div>
        </div>
    </div>
</div>
</div>
<div class="betPage DisplayNone" style="display: none!important;">
    <div class="mutiChoose">
        <dl>
            <dt>
                <div class="selectDiv">
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
    <div class="chooseOKBtn gameMoney fix">
        <div class="fl">
            <div class="bonusSlide fix">
                <div class="bor-top01">
                    <div class="siale_dandian">
                        <b class="f-left">奖金</b>
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
                <div class="fh gameLeftLI" id="singleInfo">
                    <div class="MultiplePattern" id="totalSingleInfo">
                        <em class="betCount DisplayNone" id="betCount"></em><em class="betAmount DisplayNone"
                                                                                id="betAmount"></em><input type="hidden"
                                                                                                           id="modesDIV"
                                                                                                           style="display:none; DisplayNone"
                                                                                                           value="1"><span
                                class="moneyMode"><a href="javascript:void(0);" id="yuan">2元</a><a
                                    href="javascript:void(0);" id="jiao">2角</a><a href="javascript:void(0);"
                                                                                  id="fen">2分</a><a
                                    href="javascript:void(0);" id="yuanYi">1元</a><a href="javascript:void(0);"
                                                                                    id="jiaoYi">1角</a><a
                                    href="javascript:void(0);" id="li">2厘</a></span>
                    </div>
                    <!--<div class="multiples">
                                            <b class="f-left">倍 数：&nbsp;</b>
                                            <button class="inputNumJian f-left"><img src="<?php /*echo $imgCdnUrl */ ?>/images/mobile/inputjian.png"/></button>
                                            <input value="1" id="multiple" name="multiple" class="txtStyle txtaddSty f-left" class="form-control" maxlength="5"/>
                                            <button class="inputNumJia  f-left"><img src="<?php /*echo $imgCdnUrl */ ?>/images/mobile/inputjia.png"/></button>
                                        </div>-->
                </div>
            </div>
            <div class="Padding07">
                注数:<b>注</b><em class="colorEB7A70" id="totalBetCount">0</em>
                <br>
                总计:<b>元</b><em class="yellow" id="totalBetAmount">0.00</em>
                <br>
                最高盈利：<b>元</b><em class="yellow" id="totalWin">0.00</em>
                <br>
                余额:<b>元</b>
                <em class="ShowTipsMoney" id="nowBalance"><?php echo $GLOBALS['SESSION']['balance']; ?></em>
            </div>
        </div>

        <input type="button" value="确认投注" class="CantapCodeBtn confirm" id="confirmBtn"/>

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
                <ul class="lotteryTodayTitle" id="todayIssuesHead"></ul>
            </div>
            <div class="lotteryTodayBox dform">
                <div class="lotteryTodayContent bet" id="todayIssuesBody"></div>
            </div>
        </div>
    </div>
</div>
<!--点击任意区域下拉菜单消失-->
<div class="index-box" hidden></div>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/qiehuangame.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/common.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script> <!-- 调用弹出层 -->
<script src="<?php echo $imgCdnUrl ?>/js/game/min/game_ssq_x.min.js?v=<?php echo $html_version; ?>" type="text/javascript"></script>
<script type="text/javascript">
    <?php $this->import("default_Df"); ?>
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
    });
    $(document).ready(function () {
        //元角分 字体颜色
        // $(".moneyMode a").each(function (i) {
        //     $(".moneyMode a").eq(0).click();
        //     $(".moneyMode a").eq(i).click(function () {
        //         $(".moneyMode a").eq(i).addClass("bgcolor").siblings().removeClass("bgcolor");
        //     })
        //     $('#traceBtn').css({'display': 'none'});
        // })
        // 默认玩法点击一次
        $(".methodGroupLI").eq(0).find("dd:first").click();
        // 默认玩法文本一次
        $(".GameText span.playingMethod").html($(".methodGroupLI").eq(0).find("dd:first").html());
        //// 默认奖金文本一次
        $("#gamesPrize").html($('#curPrizeSpan').find("option:selected").text().split("/")[0]);
        //根据奖金变动，动态给gamesPrize赋值
        $('#curPrizeSpan').on('change', function (e) {
            var target = $(this).find('option[value=' + e.target.value + ']').html().split("/")[0];
            $("#gamesPrize").html(target);
        })
        $("#moreGame").click(function () {
            if ($(".playToolTipBox").hasClass("DisplayBlock")) {
                $('.PlayCenter').removeClass("DisplayNone").addClass('DisplayBlock');
                $('p.GameText').removeClass("DisplayNone").addClass('DisplayBlock');
                $(".playToolTipBox").removeClass("DisplayBlock");
                $(".methodGroupLI").removeClass("DisplayBlock");
            } else {
                $('.PlayCenter').removeClass("DisplayBlock").addClass('DisplayNone');
                $('p.GameText').removeClass("DisplayBlock").addClass('DisplayNone');
                $(".playToolTipBox").addClass("DisplayBlock");
                $(".methodGroupLI").addClass("DisplayBlock");
            }
        })
        // 点击玩法选择  文本切换
        $(".methodGroupLI dd").live("click", function () {
            $(".playToolTipBox").removeClass("DisplayBlock");
            $('.PlayCenter').addClass('DisplayBlock');
            $(".GameText span").html($(this).html());
            $('p.GameText').removeClass('DisplayNone').addClass('DisplayBlock');
        })

        //返回关闭按钮
        $("#GameA").click(function () {
            $("#projectList .xDel").click();
            $(".betPage").removeClass("DisplayBlock").addClass("DisplayNone");
            $(".NumberBox5").addClass("DisplayBlock").removeClass("DisplayNone");
            $(".moreGame").addClass("DisplayBlock").removeClass("DisplayNone");
            $(".GameBoxall").addClass("DisplayBlock").removeClass("DisplayNone");
            $("#selectCodeBtn").removeClass("selectCodeBtn_selected");
        });

        //点击刷新金额
        // $('#nowBalance').click(function () {
        //     showBalance();
        //     if ($('#nowBalance').next().text() == '显示') {
        //         $('#nowBalance').next().text('隐藏');
        //     } else if ($('#nowBalance').next().text() == '显示') {
        //         $('#nowBalance').next().text('隐藏');
        //     }
        // });
        // //要求定时刷新余额
        // window.setInterval(function () {
        //     $('#nowBalance').click();
        // }, 120000);

    //     function showBalance() {
    //         var wnd = parent || self;
    //         $('#nowBalance', wnd.document).text(' loading... ');
    //         $.post(
    //             '?c=user&a=showBalance',
    //             {},
    //             function (response) {
    //                 if (response.balance >= 0) {
    //                     $('#nowBalance', wnd.document).text('￥' + response.balance);
    //                 }
    //                 else {
    //                     alert('系统繁忙，请稍候再试');
    //                 }
    //             }, 'json');
    //     }
    })
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
