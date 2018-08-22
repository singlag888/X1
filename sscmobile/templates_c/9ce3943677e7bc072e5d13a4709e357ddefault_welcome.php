<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta content="webkit" name="renderer"><!-- 页面默认用极速核 -->
        <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- 指定浏览器按照最高的标准模式解析页面针对IE -->
        <meta name="format-detection" content="telephone=no,email=no"/><!-- 使设备浏览网页时对数字不启用电话功能 -->
        <meta name="apple-touch-fullscreen" content="YES"/><!-- "添加到主屏幕"后，全屏显示 -->
        <meta name="apple-mobile-web-app-capable" content="yes"/>  <!-- 如果内容设置为YES，Web应用程序运行在全屏模式;否则，它不会。默认行为是使用Safari浏览器显示网页内容 -->
        <!--<meta http-equiv="Cache-Control" content="no-cache"/>-->  <!-- 每次打开都清除浏览器页面缓存 -->
        <meta http-equiv="Cache-Control" content="no-siteapp" /><!-- 度SiteApp转码声明 -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>

        <title><?php echo config::getConfig('site_title'); ?></title>
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobileStyle.css?v=<?php echo $html_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle.css?v=<?php echo $html_version; ?>">
    </head>
    <body class="WWelcome">
        <!--/*头部*/-->
<body class="WWelcome">
<!--/*头部*/-->
<header class="headerbg">
    <div class="logo" style="padding-top: 0.2rem;margin-left: -0.6rem;">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/logo_m.png" style="width: 5.5rem">
    </div>
    <a class="headboxright" href="index.jsp?c=game&a=packageList"><img src="<?php echo $imgCdnUrl ?>/images/mobile/personage.png"/></a>
</header>
<!-- 中间选择游戏部分 -->
<div class="GameBoxall martop1875">
    <!-- 轮播图 -->
    <div class="nw_banner">
        <div class="nw_b_min">
            <ul id="slider">
               <?php foreach ($activities as $v): ?>
                    <?php if ($v['m_banner_img'] == '') continue; ?>
                    <li><a href="?c=help&a=platformact"><img
                                    src="<?php echo $imgCdnUrl ?>/<?php echo $v['m_banner_img'] ?>" alt=""/></a></li>
                <?php endforeach; ?>
            </ul>
            <div id="pagenavi"></div>
        </div>
    </div>
    <!--欢迎公告滚动-->
    <div class="nw_gg">
        <div class="nw_ggimg">
            <img src="<?php echo $imgCdnUrl ?>/images/mobile/nw_ggimg.png">
        </div>
        <marquee behavior="scroll" direction="left" scrollamount="3" scrolldelay="100" onMouseOver="this.stop()"
                 onMouseOut="this.start()">
            <?php if ($notices): ?>
                <?php foreach ($notices as $v): ?>
                    <span><?php echo $v['title'] ?>...<?php echo date('Y-m-d', strtotime($v['create_time'])) ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span>暂无公告...</span>
            <?php endif; ?>
        </marquee>
    </div>

    <!--存取提客服快捷入口-->
    <div class="nw_indexbox">
        <li><a href="<?php echo getFloatConfig('service_url'); ?>"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox01.png"/>
                <span style="color: #333">在线客服</span>
            </a></li>
        <li><!-- <a javascript="void(0)" class="withdrawMoney"> -->
            <a href="?c=help&a=result">
                <img src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox021.png"/>
                <span style="color: #333">开奖结果</span>
            </a></li>
        <li><a href="?c=game&a=chart&lottery_id=1"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox03.png"/>
                <span style="color: #333">走势图</span>
            </a></li>
        <li><a href="index.jsp?c=help&a=platformact"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox05.png"/>
                <span style="color: #333">优惠活动</span>
            </a></li>
    </div>
    <div class="MianNewsCont" style="padding-bottom: 2.5rem;">
        <p>热门彩票<a href="index.jsp?c=game&a=lobby" class="more_cai">更多&gt;&gt;</a></p>
        <div class="hotGame">
            <li>
                <a href="index.jsp?c=game&a=lhc">
                <!-- <b class="top_r_x">信</b> -->
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-dp04.png">
                    <span>香港⑥合彩</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=cqssc">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-ssc03.png">
                    <span>重庆时时彩</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=bjpks">
                <!-- <b class="top_r_x">信</b> -->
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-klc01.png">
                    <span>北京PK拾</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=xyft_x">
                <!-- <b class="top_r_x">信</b> -->
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/26.png">
                    <span>幸运飞艇</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=xy28">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dtimg/xy28.png">
                    <span>幸运28</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=yzmmc">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-ssc01.png">
                    <span>幸运秒秒彩</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=jslhc">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/25.png">
                    <span>极速⑥合彩</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=xjssc">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/4.png">
                    <span>新疆时时彩</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=jsks">
                <!-- <b class="top_r_x">信</b> -->
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-115-03.png">
                    <span>江苏快三</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=yzffc">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/11.png">
                    <span>幸运分分彩</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=gd115">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dtimg/gd115.png">
                    <span>广东11选5</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=sd11y">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-115-01.png">
                    <span>山东11选5</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=klpk">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/14.png">
                    <span>山东快乐扑克</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=ssq">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dtimg/ssq.png">
                    <span>双色球</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=low3D">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-dp01.png">
                    <span>福彩3D</span>
                </a>
            </li>
            <li>
                <a href="?c=game&a=P3P5">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/10.png">
                    <span>体彩P3P5</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=ahks">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/19.png">
                    <span>安徽快3</span>
                </a>
            </li>
            <li>
                <a href="index.jsp?c=game&a=ksffc">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-klc05.png">
                    <span>快三分分彩</span>
                </a>
            </li>
        </div>
    </div>
        <!--弹窗-->
        <div class="toolTipBox DisplayNone">
            <p>温馨提示 <a class="toolTipBoxG"><img class="toolTipBoxClose FloatRight"
                                                src="<?php echo $imgCdnUrl ?>/images/mobile/toolTipBoxClose.png"/></a>
            </p>
            <div class="rechargeBoxText">
                <pre>只有代理可以进入</pre>
            </div>
        </div>
        <!--充值弹窗-->
        <div class="rechargeBoxBox DisplayNone">
            <p>温馨提示 <a class="rechargeBoxBoxG"><img class="rechargeBoxBoxClose FloatRight"
                                                    src="<?php echo $imgCdnUrl ?>/images/mobile/toolTipBoxClose.png"/></a>
            </p>
            <div class="rechargeBoxBoxText">
                <pre>正在开发，请到PC端充值提现</pre>
            </div>
        </div>
    </div>
</div>
<!--<div style="width:80px;height:150px;position:fixed;right:2px;bottom:2px;" class="qhb">
	<a href="http://hb87388.com/" target="view_window">
	<img src="<?php echo $imgCdnUrl ?>/images/mobile/qhb.png" style="width:75px;height:75px;">
	</a>	
	<span style="font-size:15px;position:absolute;top:0px;right:0">X</span>
</div>-->

<footer class="dtbg">
    <div class="FootMain"><a href="index.jsp?&a=welcome"><i class="footimg01"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg01-f.png"></i>
            <p>首页</p></a></div>
    <div class="FootMain"><a href="index.jsp?c=fin&a=pay"><i class="footimg02"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg02.png"></i>
            <p>立即存款</p></a></div>
    <div class="FootMain"><a href="index.jsp?c=game&a=lobby"><i class="footimg04"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg04.png"></i>
            <p>购彩大厅</p></a></div>
    <div class="FootMain"><a href="index.jsp?c=game&a=packageList"><i class="footimg05"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg05.png"></i>
            <p>投注记录</p></a></div>
    <div class="FootMain"><a href="index.jsp?c=game&a=packageList"><i class="footimg03"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg03.png"></i>
            <p>会员中心</p></a></div>
</footer>
<div class="indexbg_01">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/new_bgc.jpg"/></a>
    </div>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js?v=<?php echo $html_version; ?>"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/touchslider.dev.js?v=<?php echo $html_version; ?>"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js?v=<?php echo $html_version; ?>"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js?v=<?php echo $html_version; ?>"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js?v=<?php echo $html_version; ?>"></script><!--jquery小插件-->
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.themepunch.plugins.min.js?v=<?php echo $html_version; ?>"></script><!--bannerNew切换插件-->
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.themepunch.revolution.min.js?v=<?php echo $html_version; ?>"></script><!--bannerNew切换插件-->
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/index.js?v=<?php echo $html_version; ?>"></script>
        <script type="text/javascript">

//	$(".qhb>span").click(function(){
//     	$(".qhb").css("display","none")
//   });
//    
            //banner处滚动公告
           var active=0,
                as=document.getElementById('pagenavi').getElementsByTagName('div');
                for(var i=0;i<as.length;i++){
                    (function(){
                        var j=i;
                        as[i].onclick=function(){
                            t2.slide(j);
                            return false;
                        }
                    })();
                }
            var t2=new TouchSlider({id:'slider', speed:600, timeout:6000, before:function(index){
                    active=index;
                }});

    $(document).ready(function() {
        //检测是否游客身份并提示修改账号
        <?php if($user['is_tourist'] == 1): ?>
            layer.prompt({title: '重置您的账号', formType: 3},
             function(username, index){
                layer.close(index);
                layer.prompt({title: '重置登陆密码', formType: 1}, function(pwd, index){
                    layer.close(index);
                    if(/^[a-zA-Z]\w{5,11}$/i.test(username) == false){
                        // layer.msg("请输入正确6-12位用户名");
                        layer.alert('用户名长度为6-12个字母或数字，且必须以字母开头', {
                            icon: 1,
                            skin: 'layer-ext-moon'
                        }, function() {
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
                            } else {
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
        $(".withdrawMoney").click(function() {
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
                        layer.alert("您尚未设置安全码，请先 <a style='vertical-align:top;color:#ef984b;' href='index.jsp?c=user&a=editSafePwd' onclick=parent.layer.closeAll();>点此设置安全码</a>",{icon:7});
                    }
                    else if(data.errno == 3){
                        layer.alert("您尚未设置资金密码，请先 <a style='vertical-align:top;color:#ef984b;' href='index.jsp?c=user&a=editSecPwd' onclick=parent.layer.closeAll();>点此设置资金密码</a>",{icon:7});
                    }
                    else if(data.errno == 4){
                        layer.alert("您尚未绑定任何银行卡，请先 <a style='vertical-align:top;color:#ef984b;' href='index.jsp?c=fin&a=bindCard' onclick=parent.layer.closeAll();>点此绑定卡号</a>方可提款",{icon:7});
                    }
                }
            });
        });
    });
            function showNotice(notice_id) {
                $(".popNewsLayer").hide();
                $("#notice_info_" + notice_id).show();
                var i = parent.layer.open({
                    type: 1,
                    title: '最新公告',
                    skin: 'layui-layer-rim',
                    offset: ['50px', ''],
                    area: ['850px', 'auto'],
                    content: $('.layer_containerMore').html(),
                    success: function(l) {
                        parent.$(".ShowNews").click(function() {
                            parent.$(".popNewsLayer").hide();
                            var notice_id = parent.$(this).attr('notice_id');
                            parent.$("#notice_info_" + notice_id).show();
                        });
                    }

                });
            }
            //最新公告弹出层
            $(".ShowNewsMore").live("click",function() {
                var notice_id = $(this).attr('notice_id');
                showNotice(notice_id);
            });
            //登录后即刻弹出最新公告
            var s = '<?php echo $user['last_time']; ?>';
            var ss = new Date().getTime() - new Date(s).getTime();
            if(ss/1000 < 2){
                $(".ShowNewsMore").eq(0).click();
            };

            // <!-- 关闭按钮 -->
            $(".toolTipBoxG").click(function(){
                $(".toolTipBox").removeClass("DisplayBlock");
            })

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
        </script>
    <?php $this->import('public_tongji') ?>

        <?php if ($userAlert): ?>
        <script type="text/javascript">
            <?php if ($userAlert['type']==userAlert::TYPE_TEXT): ?>
            layer.alert('<?php echo $userAlert['content']; ?></br>安全认证官网：<a style="color:red"><?php echo $userAlert['domain']; ?></a>',{
                title:'<?php echo $userAlert['title']; ?>',
                btn:0,
                area: ['94%', ''],
            });
            <?php else: ?>
            layer.alert('<a href="?c=help&a=platformact"><?php echo '<img src="' . $imgCdnUrl .'/'. $userAlert['m_main_img'] . '" />'; ?></a>',{
                title:'<?php echo $userAlert['title']; ?>',
                skin: 'layui-layer-login',
                btn:0,
                area: ['94%', ''],
            });
            <?php endif; ?>
        </script>
    <?php endif; ?>
<style type="text/css">
.layui-layer-login{min-height: 10rem !important; top: 75px !important;}
.layui-layer-login .layui-layer-content{height: auto !important;padding:0 !important;}
.layui-layer-login .layui-layer-content img{width: 100%;}
/*.layui-layer-title{background: #fa6200;}*/
}
</style>
</body>
</html>
