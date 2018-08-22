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
</head>
<style type="text/css">
html, body {width: 100%;height: 100%;position: relative;}
#total {width: 100%;height: 100%;background: rgba(0, 0, 0, 0);position: absolute;top: 3rem;display: none;z-index: 99999;}
</style>
<body>
<!--/*头部*/-->
<header id="firstHeader" class="headerbg">
    <a class="headbox01" href="?c=game&a=lobby">游戏大厅</a>
    <!--<p class="headtetle"><?php /*echo $lottery['cname'] */ ?><span class="title_x">[信]</span></p>-->
    <p class="headtetle"><?php echo $lottery['cname'] ?>[信]</p>
    <!--<a class="headboxright" href="?c=game&a=packageList" type="button">投注记录</a>-->
    <a class="headboxright" type="button">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/button_icon.png" alt="">
    </a>
</header>

<div class="top-box">
    <div class="NumberBox_k3" id="todayDrawBtn">
        <div class="LottLeft">
            <div class="playTimer thisIssueInfo" id="thisIssueInfo">
                <p class="thisIssue">
                    <span class="issue">距离下次开奖</span>
                </p>
                <span class="Timer fix">
                        <div class="thisIssueRemainTime"
                             id="thisIssueRemainTime"><span>00</span><em>:</em><span>00</span><em>:</em><span>00</span></div></span>
            </div>
        </div>
        <div class="Lottmain">
            <div class="GameName">
                <label>第<em id="lastIssueSpan" class="lastIssueSpan"></em>期</label>
            </div>
            <div class="lotteryNum GameNuberFont" id="thisIssueNumUL">
                <div class="NumdiceMove"></div>
            </div>
        </div>
    </div>
    <!-- 中间选择游戏部分 -->
    <div class="moreGame" id="todayDrawBtn">
        <button class="question ShowTips methodTipInfo" id="methodTipInfo">玩法介绍</button>
        <p class="GameText"><span class="playingMethod" id="playingMethod"></span><b class="hz_lmlx">赔率：<b
                        class="rebateValue_lmlx"></b></b></p>
        <button id="moreGame" class="paright">更多玩法<img
                    src="<?php echo $imgCdnUrl ?>/images/mobile/arrows_right_02.png"/></button>
    </div>
    <div class="methodDesc" id="methodDesc"></div>
</div>
<!--下拉菜单-->
<div class="icon-list" style="margin-top:0.875rem;display: none;">
    <a href="?c=game&a=packageList">个人中心</a>
    <a href="?c=game&a=packageList">投注记录</a>
    <a href="?c=game&a=chart&lottery_id=<?php echo $lottery['lottery_id'] ?>">开奖走势</a>
    <a href="?c=fin&a=pay" style="border-bottom: none;">快速充值</a>
</div>
<!-- <div class="bonusSlide fix">
    <span>奖金/返点</span>
    <div id="selectRebate" class="selectRebate"></div>
    <span id="rebateValue" class="rebateValue"></span>
    <select id="curPrizeSpan" style="display:none;"></select>
    <i class="manuaTip" id="manuaTip"></i>
</div> -->
<div class="GameBoxall SubGamePlatePadding GameBoxall-new01">
    <div class="Gamepart">
        <div class="subTopBar">
            <!-- 投注玩法 -->
            <div class="playNav DisplayNone">
                <ul class="lotteryTab" id="methodGroupContainer"></ul>
            </div>
            <!--关闭玩法背景-->
            <div class="wanfa_bg"></div>
            <div class="crumbs"></div>
        </div>
        <!-- 投注主体部分 -->
        <div class="PlayCenter">
            <div class="playControlBox">
                <div class="clear"></div>
                <!-- 投注选号 -->
                <div class="choMainTabKS">
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
                <datalist id="itemlist">
                    <option>100</option>
                    <option>200</option>
                    <option>500</option>
                    <option>1000</option>
                </datalist>
                <div class="clear"></div>
            </div>
            <!-- 选定按钮 -->
            <div class="FatherCodeBtn01">
                <div class="xfootbox01">
                    <!--奖金拉动条-->
                    <div class="bonusSlide fix" style="float:left;">
                        <!-- <span>奖金/返点</span> -->
                        <div id="selectRebate" class="selectRebate"></div>
                        <span id="rebateValue" class="rebateValue"></span>
                    </div>
                    <div class="important_font_box"><span class="important_font" id="betCount">0</span>注</div>
                    <div id="basic_slider"></div>
                    <div class="siale_dandian" style="display: none;">
                        <b class="f-left">奖金/返点</b>
                        <select id="curPrizeSpan" class="f-left"></select>
                    </div>
                </div>
                <div class="xfootbox02">
                    <!--隐藏投注按钮，有用-->
                        <a class="qiehuan_x" id="qiehuan"></a>
                        <a href="javascript:void(0)"  class="custBtnStyle" id="clearProjectBtn" title="删除投注内容">清空</a>
                        <div class="btnGroup"  style="display: inline-block">
                            <input value="1" id="multiple" name="multiple" class="injine_x" maxlength="5" type="number" pattern="[0-9]*">
                        </div>
                        <!-- <button class="confirmBtn_x" id="confirmBtn">投注</button> -->
                        <button class="confirmBtn_g" id="selectCodeBtn">投注</button>
                        <button id="confirmBtn_g" style="display: none;">选定</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="betPage chooseOKBtnKS DisplayNone">
    <!--/*头部*/-->
    <!--                             <header id="firstHeader" class="headerbg">
                <a class="headbox01" href="javascript:history.go(-1)"><img src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
                <p class="headtetle"><?php echo $lottery['cname'] ?></p>
                <a class="headboxright" href="?c=game&a=packageList" type="button">投注记录</a>
            </header> -->
    <div class="mutiChoose" style="display: none;">
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
            <div class="SingleInfo" id="totalSingleInfo">
                <div class="bor-top01">
                    <!-- <div class="siale_dandian">
                        <b class="f-left">奖金/返点</b>
                        <select id="curPrizeSpan" class="f-left"></select>
                    <span id="rebateValue" class="rebateValue"></span>
                    </div> -->
                    <div class="gameTZbtn">
                        <a href="javascript:void(0)" class="del clearProjectBtn" id="clearProjectBtn"
                           title="删除投注内容">清空</a>
                    </div>
                </div>

            </div>
        </div>
        <div class="displayBtn DisplayNone">
            <div class="gameLeftLI" id="singleInfo">
                已选： 注
                <input type="hidden" id="modesDIV" style="display:none;" value="1">
            </div>

        </div>
    </div>
    <div class="chooseOKBtn" style="display:none;">
        <div class="fl">
            <div class="bonusSlide fix">
            </div>
            <!--投注金额，有用-->
            <div class="Padding07" style="display: none;">
                注数:<b>注</b><em class="colorEB7A70" id="totalBetCount">0</em>
                <br>
                总计:<b>元</b><em class="yellow" id="totalBetAmount">0.00</em>
                <br>
                最高盈利:<b>元</b><em class="yellow" id="totalWin">0.00</em>
                <br>
                余额:<b>元</b>
                <em class="ShowTipsMoney" id="nowBalance"><?php echo $GLOBALS['SESSION']['balance']; ?></em>
            </div>
        </div>
        <div class="fr">
            <input type="hidden" value="" id="token">
        </div>
    </div>
</div>
<!--最近奖期开奖号-->
<div id="total">
    <div class="theme-popover-mask">
        <div class="theme-popover">
                <span class="sp1">
                    <span class="sp2"></span>
                </span>
            <div class="theme-poptit">
                <ul class="lotteryTodayTitle">
                    <li>奖期</li>
                    <li>期号</li>
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
<!--点击任意区域下拉框消失-->
<div class="index-box" hidden>
</div>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script> <!-- 调用弹出层 -->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/game/min/game_jsks_x.min.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.jodometer.js?v=<?php echo time(); ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/qiehuangame.js?v=<?php echo $html_version; ?>"></script>
<!-- 奖池数字滚动插件 -->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/common.js?v=<?php echo time(); ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.nouislider.min.js"></script>
<script type="text/javascript">

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
        // //初始化切换官方链接
        // var lotteryId = <?php echo $lottery['lottery_id']; ?>;
        // var switchUrl = '';
        // switch (lotteryId) {
        //     case 19:
        //         switchUrl = 'ahks';
        //         break;
        //     case 12:
        //         switchUrl = 'jsks';
        //         break;
        //     case 13:
        //         switchUrl = 'ksffc';
        //         break;

        // }
        // ;
        // document.getElementById("qiehuan_g").href = '?c=game&a=' + switchUrl;
        //元角分 字体颜色
        $(".moneyMode a").eq(3).click();
        $(".moneyMode a").each(function (i) {
            $(".moneyMode a").eq(3).click();
            $(".moneyMode a").eq(i).click(function () {
                $(".moneyMode a").eq(i).addClass("bgcolor").siblings().removeClass("bgcolor");
            })
        })
        // 默认玩法点击一次
        //$(".methodGroupLI").eq(0).find("dd:first").click();
        //$(".GameText span.playingMethod").html($(".methodGroupLI").eq(0).find("dd:first").html());

        // 更多玩法点击
        $("#moreGame").click(function () {
            if($('.playNav').hasClass('DisplayBlock')){
                $('.PlayCenter').removeClass("DisplayNone").addClass('DisplayBlock');
                $('p.GameText').removeClass("DisplayNone").addClass('DisplayBlock');
                $(".playNav").removeClass("DisplayBlock");
                $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
            }else{
                $('.PlayCenter').removeClass("DisplayBlock").addClass('DisplayNone');
                $('p.GameText').removeClass("DisplayBlock").addClass('DisplayNone');
                $(".playNav").addClass("DisplayBlock");
                $(".wanfa_bg").removeClass("DisplayNone").addClass('DisplayBlock');
            }
        });
        window.onload = function () {
            var somax = ($('#curPrizeSpan option').length - 1)
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
                    $('#curPrizeSpan option').removeAttr('selected');
                    $('#curPrizeSpan option').eq(value).attr('selected', true);
                    $('#rebateValue').html($('#curPrizeSpan option').eq(value).html().split('/')[1]);
                    var selectedHtm2 =$('#curPrizeSpan option').eq(value).html();
                    $('.rebateValue_lmlx').html(selectedHtm2.split('/')[0]);
                    $('.ks_img1').attr('placeholder', selectedHtm2.split(',')[0])
                    $('.ks_img2').attr('placeholder', selectedHtm2.split(',')[1])
                    $('.ks_img3').attr('placeholder', selectedHtm2.split(',')[2])
                    $('.ks_img4').attr('placeholder', selectedHtm2.split(',')[3])
                    $('.ks_img5').attr('placeholder', selectedHtm2.split(',')[4])
                    $('.ks_img6').attr('placeholder', selectedHtm2.split(',')[5])
                    $('.ks_img7').attr('placeholder', selectedHtm2.split(',')[6])
                    $('.ks_img8').attr('placeholder', selectedHtm2.replace(/\/\d+\.\d\%/,'').split(',')[7])
                    $('#curPrizeSpan').change();
                });
            } else {
                $('#selectRebate').hide();
                $('#rebateValue').hide();
            }
            ;
        };
        // 点击玩法选择
        $(".methodGroupLI dd").live("click", function () {
            $('.PlayCenter').addClass('DisplayBlock');
            $(".playNav").removeClass("DisplayBlock");
            $(".GameText span").html($(this).html());
            $('p.GameText').removeClass('DisplayNone').addClass('DisplayBlock');
            $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
        });
        $(".wanfa_bg").live("click", function () {
            $('.PlayCenter').addClass('DisplayBlock');
            $(".playNav").removeClass("DisplayBlock");
            $('p.GameText').removeClass('DisplayNone').addClass('DisplayBlock');
            $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
        });
    });
    //弹框
    $(function () {
        $('.NumberBox_k3').click(function () {
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
