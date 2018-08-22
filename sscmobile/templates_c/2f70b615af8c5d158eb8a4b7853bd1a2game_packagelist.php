<!DOCTYPE HTML> <!-- 投注记录 -->
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
    <style type="text/css">html,body{height: 100%;}</style>
</head>
<body>
<div class="operate_middle_page">
    <!--/*头部*/-->
        <header class="headerbg">
            <a class="headbox01" href="javascript:history.back(-1)"><img src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
            <p class="headtetle">个人中心</p>
            <a class="headboxright" href="index.jsp?c=user&a=setPwd">设置</a>
        </header>
        <div class="mobile_personal">
            <div class="mobile_personal_main">
                <div class="HeadPortrait"><img src="<?php echo $imgCdnUrl ?>/images/mobile/name.png"/></div>
                <div class="callYou"><a>您好，<span><?php echo $user['username']; ?></span></a></div>
                <!-- <a href="index.jsp?c=user&a=receiveBox"><img src="<?php echo $imgCdnUrl ?>/images/mobile/envelope.png"/>站内信</a> -->
            </div>
            <div class="balance TextAlignC">
                <span>余&nbsp; 额<em class="ShowTipsMoney" id="nowBalance">￥<?php echo $GLOBALS['SESSION']['balance']; ?></em>元</span>
            </div>
            <!--充值弹窗-->
                <div class="rechargeBoxBox DisplayNone">
                    <p>温馨提示   <a class="rechargeBoxBoxG"><img class="rechargeBoxBoxClose FloatRight" src="<?php echo $imgCdnUrl ?>/images/mobile/toolTipBoxClose.png"/></a></p>
                    <div class="rechargeBoxBoxText">
                        <pre>正在开发，请到PC端充值提现</pre>
                    </div>
                </div>
            <div class="MainNav">
                <div class="Nav"><a href="index.jsp?c=fin&a=pay"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_01.png"/><p>充值</p></a></div>
                <div class="Nav"><a href="javascript:void(0);" class="topLinkBtn" id="withdrawMoney"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_02.png"/><p>提现</p></a></div>
                <div class="Nav"><a href="index.jsp?c=fin&a=orderList"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_03.png"/><p>帐变</p></a></div>
                <div class="Nav"><a href="index.jsp?c=user&a=receiveBox" class="topLinkBtn"><em class="em-num"><?php echo $noReadMsg ?></em><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_05.png"/><p>信箱</p></a></div>
                <div class="Nav"><a href="index.jsp?c=user&a=teamReportCentral"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_06.png"/><p>代理</p></a></div>
                <!-- <div class="Nav"><a href="index.jsp?c=egame&a=transfer"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_07.png"/><p>钱包</p></a></div> -->
                <div class="Nav"><a href="index.jsp?c=fin&a=rechargeWithdrawMenu"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_08.png"/><p>充提</p></a></div>
                <div class="Nav"><a href="javascript:void(0);" class="topLinkBtn index_outbtn"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nav_04.png"/><p>退出</p></a></div>
                        </div>
        </div>
        <div class="shrink1">
            <div class="shrink_center">
                <span class="shrink_left">展</span><img src="<?php echo $imgCdnUrl ?>/images/mobile/arrow2.png"><span class="shrink_right">开</span>
            </div>
        </div>
        <div class="shrink2">
            <div class="shrink_center">
                <span class="shrink_left">收</span><img src="<?php echo $imgCdnUrl ?>/images/mobile/arrow2_reverse.png"><span class="shrink_right">起</span>
            </div>
        </div>
    <?php $this->import("_usermenu"); ?>

        <table  class="defoperate_record_tablist" style="margin-top: 0.5rem">
            <thead>
            <tr class="defoperate_record_tabtitle">
                <th style="width: 1.75rem;"><div class="BettingRecordShu">
                    <hr class="pipe20"/>
                   <div class="BettingRecordYuan05 DisPlay colorE74C3C"></div>
                </div></th>
                <th>游戏类别</th>
                <th>投注时间</th>
                <th>投注金额</th>
                <th>状态</th>
                <th style="width: 1rem;"></th>
            </tr>
            </thead>
            <tbody>
            <!--  页面测试数据 -->
            <?php if ($packages): ?>
                <?php foreach ($packages as $v): ?>
                    <tr>
                        <td style="width: 1.6rem;"><div class="BettingRecordShu">
                            <hr class="pipe20"/>
                           <div class="BettingRecordYuan05 DisPlay colorE74C3C"></div>
                        </div></td>
                        <td><?php echo $v['xgame'] ? '(信)' : ''; ?><?php echo $lotterys[$v['lottery_id']]['cname']; ?></td>
                        <td><?php echo $v['create_time']; ?></td>
                        <td><?php echo $v['amount'];?></td>
                        <td class="gray_black_font betOrderStatus" id="status_<?php echo $v['wrap_id']; ?>">
                            <?php if ($v['cancel_status'] == 0): ?>
                                <?php if ($v['check_prize_status'] == 0): ?>未开奖
                                <?php elseif ($v['check_prize_status'] == 1): ?>已中奖
                                <?php else: ?>未中奖
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($v['cancel_status'] == 1): ?>个人撤单
                                <?php elseif ($v['cancel_status'] == 2): ?>追中撤单
                                <?php elseif ($v['cancel_status'] == 3): ?>出号撤单
                                <?php elseif ($v['cancel_status'] == 4): ?>未开撤单
                                <?php elseif ($v['cancel_status'] == 9): ?>系统撤单
                                <?php endif; ?>
                            <?php endif; ?>

                        </td>
                         <td>
                            <a class="orderNumberBtn buttonDDH" href="javascript:;"><?php echo $v['wrap_id']; ?></a>
                            <input type="hidden" name="is_award" value="<?php echo $v['is_award']; ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <th colspan="10" class="bom">暂无数据显示</th>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>
 <div>
    <?php echo $pageList; ?>
 </div>
</div>


<script src="<?php echo $imgCdnUrl ?>/js/common.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script> <!-- layer调用弹出层 -->
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
<script>
    $(function() {
        <?php $this->import("default_Df"); ?>
        //设置默认值
        $('select[name=lottery_id]').val('<?php echo $lottery_id; ?>').change();
        $('input[name=wrap_id]').val('<?php echo $wrap_id; ?>');
        $('select[name=check_prize_status]').val('<?php echo $check_prize_status; ?>').change();

        $('a.orderNumberBtn').on('click', function(){
            var is_award = parseInt($(this).siblings('input[name=is_award]').val());
            var str_award = is_award ? '奖池玩法':' ';
            layer.open({
                type: 2,
                shadeClose: true,
                title: str_award+'投注详情',
                closeBtn: 0,
                shade: [0.3, '#000'],
                border: [0],
                offset: ['0', '0'],
                area: ['100%','28.44rem'],
                content: ['index.jsp?c=game&a=packageDetail&wrap_id='+$(this).text()]
            });
            $('div.xubox_title', document).addClass('layui-layer-title');
        });

        $('.buttonCanle').click(function(){
          var obj = $(this),
              name = obj.attr('name');
          layer.confirm('您真的要撤单吗？',{icon:7},function(i) {
            $.post(
                'index.jsp?c=game&a=cacelPackage',
                {
                    'wrap_id': name
                },
                function(response){
                    if(response.errno　== 0){
                        obj.hide();
                        obj.closest("tr").find('td.betOrderStatus').html('个人撤单');
                    }
                    layer.alert(response.errstr,{icon:1});
                }, 'json');
          });
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
            //要求定时刷新余额
            window.setInterval(function() {
                $('#nowBalance').click();
            }, 10000);
            function showBalance() {
            var wnd = parent || self;
            $('#nowBalance', wnd.document).text(' loading... ');
            $.post(
                    'index.jsp?c=user&a=showBalance',
                    {},
                    function(response) {
                        if (response.balance >= 0) {
                            $('#nowBalance', wnd.document).text('￥' + response.balance);
                        }
                        else {
                            alert('系统繁忙，请稍候再试');
                        }
                    }, 'json');
        }
    });
            // 退出JS
        $('#logoutBtn').click(function() {
            location.href = 'index.jsp?a=logout';
        });

        ///充值弹框
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
                                title:false,
                                shade: [0.3, '#000'],
                                border: [0],
                                closeBtn:0,
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
       $(".shrink1").click(function(){
          $(".shrink1").css({'display':'none'});
          $(".shrink2").css({'display':'block'});
          $(".mobile_personal").slideToggle();
        });
       $(".shrink2").click(function(){
          $(".shrink2").css({'display':'none'});
          $(".shrink1").css({'display':'block'});
          $(".mobile_personal").slideToggle();
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
                            offset: ['0', '0'],
                            shade: [0.3, '#000'],
                            border: [0],
                            area: ['100%', '28.44rem'],
                            content: ['index.jsp?&a=withdraw&c=fin']
                        });

                    }else if(data.errno == 1){
                        layer.alert("非法请求，该用户不存在或已被冻结",{icon:7});
                    }else if(data.errno == 2){
                        layer.alert("您尚未设置安全码，请先 <a href='index.jsp?c=user&a=editSafePwd';>点此设置安全码</a>",{icon:7});
                    }
                    else if(data.errno == 3){
                        layer.alert("您尚未设置资金密码，请先 <a href='index.jsp?c=user&a=editSecPwd';>点此设置资金密码</a>",{icon:7});
                    }
                    else if(data.errno == 4){
                        layer.alert("您尚未绑定任何银行卡，请先 <a href='index.jsp?c=fin&a=bindCard';>点此绑定卡号</a>方可提款",{icon:7});
                    }
                    else if(data.errno == -1){
                    layer.alert(data.errstr)
                }
                }
            });
         });
</script>
<?php $this->import('public_tongji') ?>
</body>
</html>
