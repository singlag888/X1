<!DOCTYPE html>
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
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobileStyle.css?v=<?php echo $html_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle.css?v=<?php echo $html_version; ?>">
        <style type="text/css">html,body{width: 100%;height: 100%;    position: absolute;}</style>
</head>
<body class="BodyMain">
       <!--/*头部*/-->
       <header class="headerbg">
            <a class="headbox01" href="/?c=game&a=lobby">游戏大厅</a>
            <p class="headtetle">幸运秒秒彩</p>
            <!-- <a class="headboxright" href="index.jsp?c=game&a=packageList" type="button">投注记录</a> -->
            <a class="headboxright" type="button">
                <img src="<?php echo $imgCdnUrl ?>/images/mobile/button_icon.png" alt="">
            </a>
        </header>
    <div class="newMain" style="margin-top: 1.875rem;">
        <div id="onceShowShade" style="width:1px;height:1px;"></div>
        <!-- 彩种主体 -->
        <div id='Gamepart' class='Gamepart'>
            <!-- 开号区 -->
            <div id='GameNumber' class='GameNumber'>

                <div id='open_code' class='open_code'>
                    <div id="machine1" class="slotMachine">
                        <div class="slot slot0"></div>
                        <div class="slot slot1"></div>
                        <div class="slot slot2"></div>
                        <div class="slot slot3"></div>
                        <div class="slot slot4"></div>
                        <div class="slot slot5"></div>
                        <div class="slot slot6"></div>
                        <div class="slot slot7"></div>
                        <div class="slot slot8"></div>
                        <div class="slot slot9"></div>
                    </div>
                    <div id="machine2" class="slotMachine">
                        <div class="slot slot0"></div>
                        <div class="slot slot1"></div>
                        <div class="slot slot2"></div>
                        <div class="slot slot3"></div>
                        <div class="slot slot4"></div>
                        <div class="slot slot5"></div>
                        <div class="slot slot6"></div>
                        <div class="slot slot7"></div>
                        <div class="slot slot8"></div>
                        <div class="slot slot9"></div>
                    </div>

                    <div id="machine3" class="slotMachine">
                        <div class="slot slot0"></div>
                        <div class="slot slot1"></div>
                        <div class="slot slot2"></div>
                        <div class="slot slot3"></div>
                        <div class="slot slot4"></div>
                        <div class="slot slot5"></div>
                        <div class="slot slot6"></div>
                        <div class="slot slot7"></div>
                        <div class="slot slot8"></div>
                        <div class="slot slot9"></div>
                    </div>

                    <div id="machine4" class="slotMachine">
                        <div class="slot slot0"></div>
                        <div class="slot slot1"></div>
                        <div class="slot slot2"></div>
                        <div class="slot slot3"></div>
                        <div class="slot slot4"></div>
                        <div class="slot slot5"></div>
                        <div class="slot slot6"></div>
                        <div class="slot slot7"></div>
                        <div class="slot slot8"></div>
                        <div class="slot slot9"></div>
                    </div>

                    <div id="machine5" class="slotMachine">
                        <div class="slot slot0"></div>
                        <div class="slot slot1"></div>
                        <div class="slot slot2"></div>
                        <div class="slot slot3"></div>
                        <div class="slot slot4"></div>
                        <div class="slot slot5"></div>
                        <div class="slot slot6"></div>
                        <div class="slot slot7"></div>
                        <div class="slot slot8"></div>
                        <div class="slot slot9"></div>
                    </div>
                    <div style="clear:both;display:none;">
                        <div id="machine1Result" class="slotMachine noBorder" style="text-align:left">&nbsp;</div>
                        <div id="machine2Result" class="slotMachine noBorder" style="text-align:left">&nbsp;</div>
                        <div id="machine3Result" class="slotMachine noBorder" style="text-align:left">&nbsp;</div>
                        <div id="machine4Result" class="slotMachine noBorder" style="text-align:left">&nbsp;</div>
                        <div id="machine5Result" class="slotMachine noBorder" style="text-align:left">&nbsp;</div>
                        <div class="slotMachine noBorder"></div>
                    </div>
                </div>
                <!-- 开奖记录 -->
                <div class="moreGame" >
                <button class="question ShowTips methodTipInfo" id="methodTipInfo">玩法介绍</button>
                 <p class="GameText"><span class="playingMethod" id="playingMethod"></span><em class="gamesPrize" id="gamesPrize"></em></p>
                   <button id="moreGame" class="paright">更多玩法<img src="<?php echo $imgCdnUrl ?>/images/mobile/arrows_right_02.png"/></button>
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
            <!-- End 开号区 -->
            <!-- 导航菜单 -->
            <div class="subTopBarMMC DisplayNone" >
               <!-- 投注玩法 -->
               <div class="playNav">
               <ul class="lotteryTab" id="methodGroupContainer">

               </ul>
               </div>
               <!-- End投注玩法 -->
               <!--关闭玩法背景-->
               <div class="wanfa_bg"></div>
               <div class="crumbs"></div>
            </div>
            <!-- End 导航菜单 -->
            <!-- 选号区 -->
            <div  class="chooseNO selectArea wdmmc_01" >
                <!-- 投注球 -->
                <div class="selectArea_center" id="selectArea" style="margin-bottom: 4rem;">

                </div>
                <!-- End投注球 -->
            </div>
            <!-- End 选号区 -->
            <!-- 添加按钮 -->

            <!-- 选定按钮 -->
            <div class="FatherCodeBtn01">
                <div class="bor-top01 lhc_new02">
                <!--奖金拉动条-->
                <div class="bonusSlide fix" style="float:left;">
                    <!-- <span>奖金/返点</span> -->
                    <div id="selectRebate" class="selectRebate"></div>
                    <span id="rebateValue" class="rebateValue"></span>
                </div>
                <a href="javascript:void(0)" class="del clearProjectBtn lhc_new03" id="clearProjectBtn1" title="删除投注内容" style="float: right;margin-right: 0.5rem;">清空</a>
                <div id="basic_slider"></div>
                <div class="siale_dandian" style="display: none;">
                    <select id="curPrizeSpan" class="f-left"></select>
                </div>
            </div>
                <div class="important_font_box">
                    <span class="important_font" id="betCount">0</span>注
                </div>
                <button class="selectCodeBtn01" id="selectCodeBtn">选定</button>
            </div>
            <!-- 确认区 -->
            <div class="confirmArea DisplayNone" id="confirmArea">
            <!--/*头部*/-->
       <header class="headerbg">
            <a class="headbox01" href="javascript:history.go(-1)"><img src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
            <p class="headtetle">幸运秒秒彩</p>
            <a class="headboxright" href="index.jsp?c=game&a=packageList" type="button">投注记录</a>
        </header>
                <!-- 投注列表行 -->
                <div class="mutiChoose">
                     <div class="projectListTitle fix"><span class="width1">玩法</span><span class="width2">号码</span><span class="width3">注数</span><span class="width4">倍/元</span></div>
                    <ul class=" projectList" id="projectList">
                    </ul>
                </div>
                <div class="bonusSlide fix">
                    <div class="bor-top01" id="totalSingleInfo">
                        <b class="f-left">倍数：&nbsp;</b>
                        <button class="inputNumJian f-left"><img src="<?php echo $imgCdnUrl ?>/images/mobile/inputjian.png"/></button>
                            <input value="1" id="multiple" name="multiple" class="txtStyle txtaddSty f-left" maxlength="5"/>
                            <button class="inputNumJia f-left"><img src="<?php echo $imgCdnUrl ?>/images/mobile/inputjia.png"/></button>
                        <!-- <div id="selectRebate" class="selectRebate f-left"></div> -->
                       <!--  <div id="rebateValue" class="rebateValue"></div> -->
                            <a href="javascript:void(0)" class=" del clearProjectBtn lhc_new04" id="clearProjectBtn" style="height:1.5rem;margin-right:1rem;">清空</a>
                     </div>
                </div>
                <!-- 添加按钮行 -->
                <div class="confirmArea_top fix">
                    <!-- 元角分隐藏域 -->
                    <input type="hidden" id="modesDIV" style='display:none;' value="1" ></input>
                    <div class="fix">
                        <div class="selectMode fh" id="modes">模式：<input type="button" id="yuan" class="yuan_off" value="元" /><input type="button" id="jiao" class="jiao_off" value="角" /><input type="button" id="fen" class="fen_off" value="分" />
                             <div class="open_counts MarginTop04 FloatRight">
                            <span class="txt_right">连续开奖：</span>
                            <input class="txt_right" type="text" name="open_counts" id="open_counts" value="1" />
                            <div class="arrow FloatLeft"></div>
                            <span>次</span>
                        </div>
                        </div>
                        <div class="fl Padding05">
                              <div class="select_prize">
                                <span class="important_font DisplayNone" id="betCount">0</span><span class="important_font DisplayNone" id="betAmount">0.00</span>
                            注数：<b class="FloatRight">注</b><em id="totalBetCount" class="important_font colorEB7A70 FloatRight">0</em> 
                            <br>
                            总计：<b class="FloatRight">元</b><em id="totalBetAmount" class="important_font colorEB7A70 FloatRight">0.00</em>
                            <br>
                            最高盈利：<b class="FloatRight">元</b><em id="totalWin" class="important_font colorEB7A70 FloatRight">0.00</em>
                            <br>
                            余额: <b class="FloatRight">元</b>
                            <em class="ShowTipsMoney colorEB7A70 FloatRight" id="nowBalance"><?php echo $GLOBALS['SESSION']['balance']; ?></em> 
                        </div>
                        </div>
                    </div>
                </div>
                <!-- End 投注列表行 -->

                <!-- 开始按钮 -->
                <input type="hidden" value="" id="token" />
                <input id="confirmBtn" type="button" class="CantapCodeBtn" value="开始" />
                <!-- End 开始按钮 -->
            </div>
            <!-- End 确认区 -->
        </div>
        <!-- End 彩种主体 -->
        <!-- 开奖结果弹出层 -->
        <div id="openCodeResult" class="openCodeResult">
            <div id="resultHeader" class="header">
                <!-- <div class="getPrizeDiv"><div class="getPrize">123456</div></div>
                <div class="noPrize"></div> -->
            </div>
            <div class="line"></div>
            <div class="center">
                <table id="prizeList" class="prizeList">
                    <tbody>
                    <tr>
                        <th>开奖次数</th>
                        <th>开奖号码</th>
                        <th>中奖情况</th>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="bottom">
                <input type="button" name="goBack" id="goBack" class="goBack" value="返回" />
                <input type="button" name="playAgain" id="playAgain" class="playAgain" value="再玩一次" />
            </div>
        </div>


        <!-- 连续开奖弹出框 -->
        <div id="multipleOpenCode" class="multipleOpenCode">
        </div>
    </div>
    <?php $this->import("default_WinTips"); ?>
<!--任意区域弹出框消失-->
<div class="index-box" hidden>
</div>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.nouislider.min.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jqueryui.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/common.js?v=10000" ></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.slotmachine.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.slider.js"></script>


<script type="text/javascript">
$('#logoutBtn').click(function() {
    location.href = 'index.jsp?a=logout';
});

/**
 * 浮点数计算
 */
var floatCaculate = {
    add : function (a, b) {
        var c, d, e;
        try {
            c = a.toString().split(".")[1].length;
        } catch (f) {
            c = 0;
        }
        try {
            d = b.toString().split(".")[1].length;
        } catch (f) {
            d = 0;
        }
        return e = Math.pow(10, Math.max(c, d)), (floatCaculate.mul(a, e) + floatCaculate.mul(b, e)) / e;
    },

    sub : function (a, b) {
        var c, d, e;
        try {
            c = a.toString().split(".")[1].length;
        } catch (f) {
            c = 0;
        }
        try {
            d = b.toString().split(".")[1].length;
        } catch (f) {
            d = 0;
        }
        return e = Math.pow(10, Math.max(c, d)), (floatCaculate.mul(a, e) - floatCaculate.mul(b, e)) / e;
    },

    mul : function (a, b) {
        var c = 0,
            d = a.toString(),
            e = b.toString();
        try {
            c += d.split(".")[1].length;
        } catch (f) {}
        try {
            c += e.split(".")[1].length;
        } catch (f) {}
        return Number(d.replace(".", "")) * Number(e.replace(".", "")) / Math.pow(10, c);
    },

    div : function (a, b) {
        var c, d, e = 0,
            f = 0;
        try {
            e = a.toString().split(".")[1].length;
        } catch (g) {}
        try {
            f = b.toString().split(".")[1].length;
        } catch (g) {}
        return c = Number(a.toString().replace(".", "")), d = Number(b.toString().replace(".", "")), mul(c / d, Math.pow(10, f - e));
    },

    /*Javascript设置要保留的小数位数，四舍五入。
     *ForDight(Dight,How):数值格式化函数，Dight要格式化的 数字，How要保留的小数位数。
     *这里的方法是先乘以10的倍数，然后去掉小数，最后再除以10的倍数。
     */
    doRound : function (Dight,How){
        Dight = Math.round(Dight*Math.pow(10,How))/Math.pow(10,How);
        return Dight;
    }
}

//缓存的开奖记录控件
var scrollCode = {
    scrollCode : function (type, showNum){
        var showPosition = new Array();
        var obj = $('#open_code_list ul li');
        var length = obj.length;

        // 判断要显示的li是否大于等于总的li，如果大于等于，说明显示的code不用分页
        if (showNum >= length) {
            return false;
        }
        if (type == 'next') {
            $('#prevCode').removeClass('up_triangle_off').addClass('up_triangle');
        } else if (type == 'prev') {
            $('#nextCode').removeClass('down_triangle_off').addClass('down_triangle');
        }

        // 获得显示的code的li的位置，并根据type是进行+1或-1，直接得出点击后的show_code要插入的位置
        obj.each(function(){
            if ($(this).hasClass('show_code')) {
                if (type == 'next') {
                    showPosition.push($(this).index()+1);
                } else if (type == 'prev') {
                    showPosition.push($(this).index()-1);
                }
            }
        });

        if (showPosition[showNum-1] > length-1) {
            $('#nextCode').removeClass('down_triangle').addClass('down_triangle_off');
            return false;
        }
        if (showPosition[0] < 0) {
            $('#prevCode').removeClass('up_triangle').addClass('up_triangle_off');
            return false;
        }
        if (type == 'next') {
            obj.eq(showPosition[0] - 1).removeClass('show_code');
            obj.eq(showPosition[showNum - 1]).addClass('show_code');
        } else if (type == 'prev') {
            obj.eq(showPosition[showNum - 1]+1).removeClass('show_code');
            obj.eq(showPosition[0]).addClass('show_code');
        }
    },
    //插入一条新纪录后重置显示区
    init : function() {
        var obj = $('#open_code_list ul li');
        obj.removeClass('show_code');
        obj.each(function(i){
           $(this).addClass("show_code");
           i++;
           if(i>5) {
               return;
           }
        });
    }
};
</script>

<script type="text/javascript">
$(function() {
    $("#searchMore").click(function(){
        layer.open({
            type: 2,
            shadeClose: false,
            title: '查询更多',
            shade: [0.3, '#000'],
            offset: ['60px', ''],
            border: [0],
            area: ['824px','500px'],
            content: ["index.jsp?lottery_id=15&c=game&a=packageList&mmcPopup=1"]
        });
    });
    $('.ShowTipsUserInfo').on('mouseover', function() {
        layer.tips('显示用户信息', this, {
            tips: [3, '#F26C4F'],
            time: 1000,
            maxWidth: 240
        });
    });
    //充值的tip
    $('a.pay').on('mouseover', function() {
        layer.tips('充值', this, {
            tips: [3, '#F26C4F'],
            time: 1000,
            maxWidth: 240
        });
    });
    //充值弹框
    $('a.pay').on('click', function(){
        $.ajax({
            type: "GET",
            url: "index.jsp?c=fin&a=deposit",
            data: {flag: 'ajax'},
            dataType: "json", //返回0和1
            success: function(data) {
                if(data.errno == 0){
                    layer.open({
                        type: 2,
                        shadeClose: false,
                        title: '充值处理时间：7*24小时充值服务',
                        shade: [0.3, '#000'],
                        border: [0],
                        offset: ['0', '0'],
                        area: ['100%', '28.44rem'],
                        content: ['index.jsp?c=fin&a=deposit']
                    });

                }else if(data.errno == 1){
                    layer.alert("非法请求，该用户不存在或已被冻结",{icon:7});
                }else if(data.errno == 2){
                    layer.alert("您尚未设置安全码，请先 <a style='vertical-align:top;color:#ef984b;' href='javascript:void(0);' onclick=window.location.href='?c=user&a=editSafePwd';parent.layer.closeAll();>点此设置安全码</a>",{icon:7});
                }
                else if(data.errno == 3){
                    layer.alert("您尚未设置资金密码，请先 <a style='vertical-align:top;color:#ef984b;' href='javascript:void(0);' onclick=window.location.href='?c=user&a=editSecPwd';parent.layer.closeAll();>点此设置资金密码</a>",{icon:7});
                }
                else if(data.errno == 4){
                    layer.alert("您尚未绑定任何银行卡，请先 <a style='vertical-align:top;color:#ef984b;' href='javascript:void(0);' onclick=window.location.href='?c=fin&a=bindCard';parent.layer.closeAll();>点此绑定卡号</a>方可提款",{icon:7});
                }
            }
        });
    });

    $("#withdrawMoney").click(function() {
       $.ajax({
           type: "GET",
           url: "index.jsp?c=fin&a=withdraw",
           data: {flag: 'ajax'},
           dataType: "json", //返回0和1
           success: function(data) {
               if(data.errno == 0){
                    var i = layer.open({
                        type: 2,
                        title: '提取余额到银行卡',
                        offset: ['100px', ''],
                        shade: [0.3, '#000'],
                        border: [0],
                        area: ['824px','480px'],
                        content: ['index.jsp?&a=withdraw&c=fin']

                    });

                    }else if(data.errno == 1){
                        layer.alert("非法请求，该用户不存在或已被冻结",{icon:7});
                    }else if(data.errno == 2){
                        layer.alert("您尚未设置安全码，请到【个人中心】设置安全码！",{icon:7});
                    }
                    else if(data.errno == 3){
                        layer.alert("您尚未设置资金密码，请到【个人中心】设置资金密码！",{icon:7});
                    }
                    else if(data.errno == 4){
                        layer.alert("您尚未绑定任何银行卡，请到【个人中心】绑定银行卡！",{icon:7});
                    }
           }
       });
    });

    //用户弹出层
    $(".CloseUser").click(function() {
        $(".UserPopLayer").toggle(150);
    });
    $(".User .name").click(function() {
        $(".UserPopLayer").toggle(150);
    });
<?php if(date("H") == '04'): ?>
            layer.open({
                closeBtn : 1,
                btn: 0,
                area : ['auto' , '150'],
                maxWidth:350,
                content: '4:00至5:00为平台维护时间，在此期间，秒秒彩将暂停购彩服务，请您稍后再游戏。给您带来的不便，敬请谅解。'
            });
<?php endif; ?>
    var methods = <?php echo $methods ?>;
    $.init({
        lotteryId: <?php echo $lottery['lottery_id']; ?>, lotteryName: '<?php echo $lottery['cname']; ?>', prizeRate: <?php echo 1 - $lottery['total_profit']; ?>, lotteryType: <?php echo $lottery['lottery_type']; ?>, methods: methods[<?php echo $lottery['lottery_id'] ?>], maxCombPrize: <?php echo $maxCombPrize; ?>, openedIssues: <?php echo $json_openedIssues; ?>, minRebateGaps: <?php echo $minRebateGaps; ?>, rebate: <?php echo $rebate; ?>, defaultMode: 1, defaultRebate: <?php echo $rebate; ?>, missHot: <?php echo $json_missHot; ?>
    });

    //初始化老虎机控件
    mySlotMachine.init();

    $('#nextCode').click(function(){
        scrollCode.scrollCode('next', 5);
    });
    $('#prevCode').click(function(){
        scrollCode.scrollCode('prev', 5);
    });

    //点击刷新金额
    $('#nowBalance').click(function() {
        showBalance();
        if($('#nowBalance').next().text() == '显示'){
            $('#nowBalance').next().text('隐藏');
        }else if($('#nowBalance').next().text() == '显示'){
            $('#nowBalance').next().text('隐藏');
        }
    });
    // //金额隐藏显示切换
    // $('#nowBalance').text('***');
    // $('#nowBalance').next().click(function(){
    //     if($('#nowBalance').next().text() == '显示'){
    //         $('#nowBalance').text('￥'+<?php echo $user['balance']; ?>);
    //         $('#nowBalance').next().text('隐藏');
    //     }else if($('#nowBalance').next().text() == '隐藏'){
    //         $('#nowBalance').text('***');
    //         $('#nowBalance').next().text('显示');
    //     }
    // });

    $('.ShowTipsMoney').on('mouseover', function() {
        layer.tips('点击刷新余额', this, {
            tips: [3, '#F26C4F'],
            time: 1000,
            maxWidth: 240
        });
    });

    //mySlotMachine.msgPopUp4Multiple();

    var customerUrl ="http://s2.myapple88.com/new/client.php?arg=jyz-88&style=1",role = '',username = '<?php echo $user['username'] ?>',level = <?php echo $user['level'] ?>,is_test = <?php echo $user['is_test'] ?>;

    if(is_test){
            role = '&user_level=tester';
    }else if(level == 0){
            role = '&user_level=topAgent';
    }else if(level > 0 && level < 10){
            role = '&user_level=agent';
    }else if(level == 10){
            role = '&user_level=player';
    }else{
            role = '&user_level=login';
    };
    if(username == ""){
        username = "Visitor";
    }
    var callParams = '&user_name=' + username + role;

    $('#callCenter').click(function(event) {
        window.open(customerUrl + callParams,"","height=550,width=800,top=0,left=0,toolbar=no,menubar=no,scrollbars=no,resizable=no,location=no,status=no");
        return false;
    });
});
window.onload=function(){
                var somax = ($('#curPrizeSpan option').length -1)
                if(somax > 0){
                    var rangeSlider = document.getElementById('selectRebate');
                    noUiSlider.create(rangeSlider,{
                        start:($('#curPrizeSpan option').length -1),
                        step:1,
                        connect: true,
                        range: {
                            'min': 0,
                            'max': ($('#curPrizeSpan option').length -1),
                        },
                        format: {
                            to: function ( value ) {
                                return value + '';
                                },
                            from: function ( value ) {
                                return value.replace('', '');
                                }
                        }
                    });
                    rangeSlider.noUiSlider.on('update', function(value) {
                        $('#curPrizeSpan option').removeAttr('selected');
                        var selectedHtm2 =$('#curPrizeSpan option').eq(value).html();
                        $('#curPrizeSpan option').eq(value).attr('selected', true);
                        $('#rebateValue').html(selectedHtm2.split('/')[1]);
                        $('#gamesPrize').html('赔率：'+selectedHtm2.split('/')[0]);
                        $('#curPrizeSpan').change();
                        });
                }else{
                    $('#selectRebate').hide();
                    $('#rebateValue').hide();
                };
            };
$(document).ready(function(){

             //元角分 字体颜色
            $(".selectMode input").each(function(i){
                $(".selectMode input").eq(0).click();
                $(".selectMode input").eq(i).click(function(){
                     $(".selectMode input").eq(i).addClass("bgcolor").siblings().removeClass("bgcolor");
                })
            })
         
             // 默认玩法文本一次
            $(".GameText span").html('五星定位');

            // 更多玩法点击
            $("#moreGame").click(function(){
                $('.selectArea_center').removeClass("DisplayBlock");
                $('.selectArea_center').addClass('DisplayNone');
                $('p.GameText').removeClass("DisplayBlock").addClass('DisplayNone');
                $(".wanfa_bg").removeClass("DisplayNone").addClass('DisplayBlock');
                if($(".subTopBarMMC").hasClass("DisplayBlock")){
                    return;
                }else{
                    $(".subTopBarMMC").addClass("DisplayBlock");
                }
            })
            // 点击玩法选择
            $(".methodGroupLI dd").live("click",function(){
                  $(".subTopBarMMC").removeClass("DisplayBlock");
                  $('.selectArea_center').addClass('DisplayBlock');
                  $('p.GameText').removeClass('DisplayNone').addClass('DisplayBlock');
                  $(".GameText span").html($(this).html());
                  $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
              });
            $(".wanfa_bg").live("click",function(){
                  $(".subTopBarMMC").removeClass("DisplayBlock");
                  $('.selectArea_center').addClass('DisplayBlock');
                  $('p.GameText').removeClass('DisplayNone').addClass('DisplayBlock');
                  $(".wanfa_bg").removeClass("DisplayBlock").addClass('DisplayNone');
              });
             //返回关闭按钮
            $("#GameA").click(function(){
                $(".confirmArea").removeClass("DisplayBlock");
                $("#selectCodeBtn").removeClass("selectCodeBtn_selected");
            })
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
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/game/min/game_mmc.min.js?v=<?php echo $html_version; ?>"></script>
<?php $this->import('public_tongji') ?>
</body>
</html>
