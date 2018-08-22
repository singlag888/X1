   <style type="text/css">
    .layui-layer-title{
        background-color:#e4393c;
    }
    .i0{
       height: 22px;
    color: #fff;
    background: #e4393c;
    line-height: 22px;
    padding: 0 6px;
    position: relative;
    float: right;
    }
</style>
    <div class="header-box" style="background: #fff;">
        <div class="header-ts">
            <div class="ts-play" style="width: 1180px">
                <img src="<?php echo $imgCdnUrl ?>/images_fh/laba.png" alt="shibai">
              <div class="NewSlides" style="width:1150px;">
                <div id="NewSl" style="width:1150px;">
                    <ul id="NewSl_begin">
                        <?php foreach (range(0, 9) as $v) : ?>
                            <li><span class="listpart"><a href="javascript:void(0);" class="ShowNewsMore" notice_id="<?php echo $notices[$v]['notice_id']; ?>"><?php echo mb_substr($notices[$v]['title'], 0, 50, 'utf-8'); ?></a></span><span class="time"><?php
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
        <div class="header-logo">
            <div class="logo-play cf" style="width:1190px;">
                <a href="?a=main" class="logo fl">
                    <img src="<?php echo $imgCdnUrl ?>/images_600/logoo.png" alt="">
                </a>
                <!--登录后状态-->
                <div class="logo-r fr" style="margin-top:0">
                    <div class="logo-r-t">
                        <div class="fr">
                            <div class="fl m-l-10">
                                <span>您好，</span>
                                <span class="login-name"><?php echo $user['username'] ?></span>
                                <span> 可用余额：</span>
                            </div>
                            <div class="fl money">
                                <span id="nowBalance">￥0.000</span>
                                <i>显示</i>
                            </div>
                        </div>
                    </div>
                    <ul class="logo-r-b fr">
                        <li>
                            <img src="<?php echo $imgCdnUrl ?>/images/public_icon/personalCenter.png" alt="个人中心"/>
                            <a href="?c=fin&a=orderList">个人中心</a>
                        </li>
                        <li>
                            <img src="<?php echo $imgCdnUrl ?>/images/public_icon/topUp.png" alt="充值"/>
                            <a href="?c=fin&a=pay" class="pay">充值</a>
                        </li>
                        <li>
                            <img src="<?php echo $imgCdnUrl ?>/images/public_icon/withdrawDeposit.png" alt="提现"/>
                            <a href="javascript:void(0)" id="withdrawMoney">提现</a>
                        </li>
                        <li>
                            <img src="<?php echo $imgCdnUrl ?>/images/public_icon/BetRecord.png" alt="投注记录"/>
                            <a href="?c=game&a=packageList">投注记录</a>
                        </li>
                        <li>
                            <img src="<?php echo $imgCdnUrl ?>/images/public_icon/message.png" alt="站内信"/>
                            <a href="?c=user&a=receiveBox">站内信<em class="em-num"><?php echo $noReadMsg ?></em></a>
                        </li>
                        <li>
                            <img src="<?php echo $imgCdnUrl ?>/images/public_icon/close.png" alt="安全退出"/>
                            <a class="index_outbtn" href="javascript:void(0)">安全退出</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="header-bar">
            <div class="bar-play cf" style="width:1190px;">
                <div class="fl select" onmouseover="mv()" onmouseout="mu()">
                    <div class="select_top">
                        <div class="select-text fl">请选择彩票种类</div>
                        <div class="select-dis fr">
                            <img src="<?php echo $imgCdnUrl ?>/images_fh/display.png" alt="">
                        </div>
                    </div>
                    <div class="select-list">
                        <div class="select-list-t is">
                            <a href="?c=game&a=cqssc" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-3.png" alt="">
                                        <span>重庆时时彩</span>
                                        <i class="i0">最火爆彩种</i>
                                    </a>
                                    <a href="?c=game&a=bjpks" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-19.png" alt="">
                                        <span>北京PK拾</span>
                                        <i class="i0">5分钟一期 快速</i>
                                    </a>
                                    <a href="?c=game&a=xyft_x" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-31.png" alt="">
                                        <span>幸运飞艇</span>
                                        <i class="i0">激情飞艇 快速</i>
                                    </a>
                                    <a href="?c=game&a=yzmmc" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-1.png" alt="">
                                        <span>幸运秒秒彩</span>
                                        <i class="i0">随点随开</i>
                                    </a>
                                    <a href="?c=game&a=lhc" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-18.png" alt="">
                                        <span>香港⑥合彩</span>
                                        <i class="i0">火爆低频</i>
                                    </a>
                                    <a href="?c=game&a=jsks" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-20.png" alt="">
                                        <span>江苏快3</span>
                                        <i class="i0">10分钟一期</i>
                                    </a>
                                    <a href="?c=game&a=sd11y" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images/lottery_logo_2.png" alt="">
                                        <span>山东11选5</span>
                                        <i class="i0">趣味玩法</i>
                                    </a>
                                    <a href="?c=game&a=ssq" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl ?>/images_fh/ls/ls-27.png" alt="">
                                        <span>幸运双色球</span>
                                        <i class="i0">千万大奖等着您</i>
                                    </a>
                                    <!-- <a href="?c=game&a=low3D" class="select-list1 cf">
                                        <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-15.png" alt="">
                                        <span>福彩3D</span>
                                    </a> -->
                        </div>
                        <div class="id-sel-all" onmouseover="xyOver()" onmouseout="xyOut()">
                                    <div class="select-all bor-r on cf" >
                                        <div class="se-all  fl">
                                            信用
                                        </div>
                                        <div class="fr se-all-tx">
                                            <a href="?c=game&a=lhc_x">香港⑥合彩</a>
                                            <a href="?c=game&a=jsks_x">江苏快三</a>
                                            <a href="?c=game&a=ahks_x">安徽快三</a>
                                            <a href="?c=game&a=ksffc_x">快三分分彩</a>
                                            <a href="?c=game&a=jslhc_x">极速⑥合彩</a>
                                            <a href="?c=game&a=bjpks_x">北京PK拾</a>
                                        </div>
                                        <div class="se-xy-list ds">
                                            <div class="all">信用玩法</div>
                                                <a href="?c=game&a=cqssc_x">重庆时时彩</a>
                                                <a href="?c=game&a=xjssc_x">新疆时时彩</a>
                                                <a href="?c=game&a=tjssc_x">天津时时彩</a>
                                                <a href="?c=game&a=yzffc_x">幸运分分彩</a>
                                                <a href="?c=game&a=dj15_x">东京1.5分彩</a>
                                                <a class="cur-not" href="javascript:;">印尼5分彩</a>
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
                                            </div>
                                        </div>
                                    <!-- <div class="se-all-list ds">
                                        <div class="all">全部</div>
                                        <a href="javascript:;" class="goLogin">香港⑥合彩</a>
                                        <a href="javascript:;" class="goLogin">江苏快三</a>
                                        <a href="javascript:;" class="goLogin">安徽快三</a>
                                        <a href="javascript:;" class="goLogin">快三分分彩</a>
                                        </div>
                                    </div>-->
                                </div>
                        <div onmouseover="amv()" onmouseout="amu()">
                            <div class="select-all cf">
                                <div class="se-all fl">
                                    官方
                                </div>
                                <div class="fr se-all-tx">
                                    <a href="?c=game&a=cqssc" class="on"> 重庆时时彩</a>
                                    <a href="?c=game&a=sd11y"> 山东11选5</a>
                                    <a href="?c=game&a=bjpks"> 北京PK拾</a>
                                    <a href="?c=game&a=jsks"> 江苏快三</a>
                                    <a href="?c=game&a=yzmmc"> 幸运秒秒彩</a>
                                    <a href="?c=game&a=yzffc"> 幸运分分彩</a>
                                </div>
                            </div>
                            <div class="se-all-list ds">
                                <div class="all">官方玩法</div>
                                <a href="?c=game&a=cqssc">重庆时时彩</a>
                                <a href="?c=game&a=xjssc">新疆时时彩</a>
                                <a href="?c=game&a=tjssc">天津时时彩</a>
                                <a href="?c=game&a=yzmmc">幸运秒秒彩</a>
                                <a href="?c=game&a=yzffc">幸运分分彩</a>
                                <a href="?c=game&a=dj15">东京1.5分彩</a>
                                <a class="cur-not" href="javascript:;">印尼5分彩</a>
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
                            </div>
                        </div>
                    </div>
                </div>
                <!--mennu-->
                <div class="navmenu fl bar-a">

                        <a href="?a=main" <?php if($GLOBALS['nav'] == 'main') echo 'class="on"'; ?>>首页</a>

                        <a href="?c=game&a=lobby" <?php if($GLOBALS['nav'] == 'game') echo 'class="on"'; ?>>购彩大厅</a>

                        <a href="?c=help&a=result" <?php if($GLOBALS['nav'] == 'result') echo 'class="on"'; ?>>开奖结果
                        </a>

                        <a href="?c=help&a=download" <?php if($GLOBALS['nav'] == 'mobile') echo 'class="on"'; ?>>手机购彩
                        </a>


                        <a href="?c=help&a=chart" <?php if($GLOBALS['nav'] == 'chart') echo 'class="on"'; ?>>开奖走势
                        <div class="hot1">
                            <div class="hot-all"><img src="<?php echo $imgCdnUrl ?>/images_fh/hot.gif" alt=""></div>
                        </div>
                        </a>

                        <a href="?c=help&a=platformact" <?php if($GLOBALS['nav'] == 'promo') echo 'class="on"'; ?>>优惠活动
                        <div class="hot1">
                            <div class="hot-all"><img src="<?php echo $imgCdnUrl ?>/images_fh/hot.gif" alt=""></div>
                        </div>
                        </a>

                        <a href="?c=help&a=latestnew" <?php if($GLOBALS['nav'] == 'news') echo 'class="on"'; ?>>彩票资讯</a>

                        <!--<a href="?c=help&a=safe" <?php if($_SESSION['nav'] == 'help') echo 'class="on"'; ?>>帮助中心</a>-->
                        <!--移动的滑动-->
                        <div class="move-bg"></div>
                        <script>
                        $(function(){
                            $(".navmenu").movebg({width:94/*滑块的大小*/,extra:10/*额外反弹的距离*/,speed:300/*滑块移动的速度*/,rebound_speed:350/*滑块反弹的速度*/});
                        })

                        </script>
                        <!--移动的滑动 end-->

                </div>

                <div class="bar-service fr">

                    <a target="_blank" href="<?php echo getFloatConfig('service_url'); ?>" class="duokebo-btn"><img src="<?php echo $imgCdnUrl ?>/images_fh/servicelogo.png" alt="">客服</a>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo $imgCdnUrl ?>/js/index.js"></script>
    <!--退出layer -->
    <script type="text/javascript">
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
    //         content:'<?php echo config::getConfig('service_url') ?>',
    //     });
    // });
    </script>
