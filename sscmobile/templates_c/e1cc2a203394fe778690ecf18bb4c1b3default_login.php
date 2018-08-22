<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="webkit" name="renderer"><!-- 页面默认用极速核 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- 指定浏览器按照最高的标准模式解析页面针对IE -->
    <meta name="format-detection" content="telephone=no,email=no"/><!-- 使设备浏览网页时对数字不启用电话功能 -->
    <meta name="apple-touch-fullscreen" content="YES"/><!-- "添加到主屏幕"后，全屏显示 -->
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <!-- 如果内容设置为YES，Web应用程序运行在全屏模式;否则，它不会。默认行为是使用Safari浏览器显示网页内容 -->
    <!--<meta http-equiv="Cache-Control" content="no-cache"/>-->  <!-- 每次打开都清除浏览器页面缓存 -->
    <meta http-equiv="Cache-Control" content="no-siteapp"/><!-- 度SiteApp转码声明 -->
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>

    <title><?php echo config::getConfig('site_title'); ?></title>
    <link rel="stylesheet" type="text/css"
          href="<?php echo $imgCdnUrl ?>/css/mobileStyle.css?v=<?php echo $html_version; ?>">
    <link rel="stylesheet" type="text/css"
          href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle.css?v=<?php echo $html_version; ?>">
</head>
<body class="WWelcome">
<!--/*头部*/-->
<header class="headerbg">
    <div class="logo" style="padding-top: 0.2rem;margin-left: -0.6rem;">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/logo_m.png" style="width: 5.5rem">
    </div>
    <a class="headbox01 headbox0001" href="?a=login2">登 录</a>
    <a class="headboxright" href='?a=marketReg'>注 册</a>
</header>
<!-- 中间选择游戏部分 -->
<div class="GameBoxall martop1875">
    <!-- 轮播图 -->
    <div class="nw_banner">
        <div class="nw_b_min">
            <ul id="slider">
                <?php foreach ($activities as $v): ?>
                    <?php if ($v['m_banner_img'] == '') continue; ?>
                    <li><a href="?c=fake&a=platformact"><img
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
        <li><a href="<?php echo getFloatConfig('service_url'); ?>" target="_blank"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox01.png"/>
                <span style="color: #333">在线客服</span>
            </a></li>
        <li><!-- <a javascript="void(0)" class="withdrawMoney"> -->
            <a href="?c=fake&a=result">
                <img src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox021.png"/>
                <span style="color: #333">开奖结果</span>
            </a></li>
        <li><a href="?a=login2"><img src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox03.png"/>
                <span style="color: #333">走势图</span>
            </a></li>
        <li><a href="index.jsp?c=fake&a=platformact"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/nw_indexbox05.png"/>
                <span style="color: #333">优惠活动</span>
            </a></li>
    </div>

     <div class="MianNewsCont" style="padding-bottom: 2.5rem;">
        <p>热门彩票<a href="index.jsp?c=game&a=lobby" class="more_cai">更多&gt;&gt;</a></p>
        <div class="hotGame">
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-dp04.png">
                    <span>香港⑥合彩</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-ssc03.png">
                    <span>重庆时时彩</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-klc01.png">
                    <span>北京PK拾</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/26.png">
                    <span>幸运飞艇</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dtimg/xy28.png">
                    <span>幸运28</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-ssc01.png">
                    <span>幸运秒秒彩</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/25.png">
                    <span>极速⑥合彩</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/4.png">
                    <span>新疆时时彩</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-115-03.png">
                    <span>江苏快三</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/11.png">
                    <span>幸运分分彩</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dtimg/gd115.png">
                    <span>广东11选5</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-115-01.png">
                    <span>山东11选5</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/14.png">
                    <span>山东快乐扑克</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dtimg/ssq.png">
                    <span>双色球</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/dt-dp01.png">
                    <span>福彩3D</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/10.png">
                    <span>体彩P3P5</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
                    <img src="<?php echo $imgCdnUrl ?>/images/mobile/id/19.png">
                    <span>安徽快3</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" class="goLogin">
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
<footer class="dtbg">
    <div class="FootMain"><a href="?a=login2"><i class="footimg01"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg01-f.png"></i>
            <p>首页</p></a></div>
    <div class="FootMain"><a href="?a=login2"><i class="footimg02"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg02.png"></i>
            <p>立即存款</p></a></div>
    <div class="FootMain"><a href="?c=fake&a=lobby"><i class="footimg04"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg04.png"></i>
            <p>购彩大厅</p></a></div>
    <div class="FootMain"><a href="?a=login2"><i class="footimg05"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg05.png"></i>
            <p>投注记录</p></a></div>
    <div class="FootMain"><a href="?a=login2"><i class="footimg03"><img
                        src="<?php echo $imgCdnUrl ?>/images/mobile/footimg03.png"></i>
            <p>会员中心</p></a></div>
</footer>
<div class="indexbg_01">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/new_bgc.jpg"/></a>
    </div>
<script type="text/javascript"
        src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript"
        src="<?php echo $imgCdnUrl ?>/js/touchslider.dev.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js?v=<?php echo $html_version; ?>"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/index.js?v=<?php echo $html_version; ?>"></script>

<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js?v=<?php echo $html_version; ?>"></script>

<?php $this->import('public_tongji') ?>

<!--弹窗-->
<!--
<script type="text/javascript">
layer.alert('接上游渠道通知，北京时间7.1日凌晨起大部分第三方微信，支付宝通道进行维护，导致无法存款，特此公告，建议广大会员使用公司入款（网银转账）进行游戏，或联系24小时在线客服索取支付方式，给您带来的不便还请您谅解，谢谢。</br>安全认证官网：<a style="color:red">www.51877.com</a>',{
title:'公告',
area: ['94%', ''],
});
</script>
-->
<?php if ($userAlert): ?>
    <script type="text/javascript">
        <?php if ($userAlert['type']==userAlert::TYPE_TEXT): ?>
        layer.alert('<?php echo $userAlert['content']; ?></br>安全认证官网：<a style="color:red"><?php echo $userAlert['domain']; ?></a>',{
            title:'<?php echo $userAlert['title']; ?>',
            btn:0,
            area: ['94%', ''],
        });
        <?php else: ?>
        layer.alert('<a href="?c=fake&a=platformact"><?php echo '<img src="' . $imgCdnUrl .'/'. $userAlert['m_main_img'] . '" />'; ?></a>',{
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
</style></body>
</html>
