   <style type="text/css">
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
    <div class="header-box">
        <div class="header-ts">
            <div class="ts-play" style="width: 1190px;">
                <img src="<?php echo $imgCdnUrl ?>/images_fh/laba.png" alt="shibai">
              <div class="NewSlides" style="width:1150px;">
                <div id="NewSl" style="width:1150px;">
                    <ul id="NewSl_begin">
                        <?php foreach (range(0, 9) as $v) : ?>
                            <li><span class="listpart"><a href="javascript:void(0);" class="ShowNewsMore" notice_id="<?php echo $notices[$v]['notice_id']; ?>"><?php echo mb_substr($notices[$v]['title'], 0, 32, 'utf-8'); ?></a></span><span class="time"><?php
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
                    <img src="<?php echo $imgCdnUrl ?>/images_600/neiLogo.png" alt="中彩网">
                </a>
                <!--登录后状态-->

            </div>
        </div>
        <div class="header-bar">
            <div class="bar-play cf" style="width:1190px;">
                <div class="fl select" onmouseover="mv()" onmouseout="mu()">
                    <div class="select-text fl">请选择彩票种类</div>
                    <div class="select-dis fr">
                        <img src="<?php echo $imgCdnUrl ?>/images_fh/display.png" alt="">
                    </div>
                    <div class="select-list">
                        <div class="select-list-t is">
                            <a href="javascript:;" class="select-list1 cf goLogin">
                                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-3.png" alt="">
                                <span>重庆时时彩</span>
                                <i class="i0">最火爆彩种</i>
                            </a>
                            <a href="javascript:;" class="select-list1 cf goLogin">
                                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-19.png" alt="">
                                <span>北京PK拾</span>
                                <i class="i0">5分钟一期 快速</i>
                            </a>
                            <a href="javascript:;" class="select-list1 cf goLogin">
                               <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-31.png" alt="">
                               <span>幸运飞艇</span>
                               <i class="i0">激情飞艇 快速</i>
                            </a>
                            <a href="javascript:;" class="select-list1 cf goLogin">
                                 <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-1.png" alt="">
                                 <span>幸运秒秒彩</span>
                                 <i class="i0">随点随开</i>
                            </a>
                            <a href="javascript:;" class="select-list1 cf goLogin">
                                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-18.png" alt="">
                                <span>香港⑥合彩</span>
                                <i class="i0">火爆低频</i>
                            </a>
                            <a href="javascript:;" class="select-list1 cf goLogin">
                                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-20.png" alt="">
                                <span>江苏快3</span>
                                <i class="i0">10分钟一期</i>
                            </a>
                            <a href="javascript:;" class="select-list1 cf goLogin">
                                <img src="<?php echo $imgCdnUrl?>/images/lottery_logo_2.png" alt="">
                                <span>山东11选5</span>
                                <i class="i0">趣味玩法</i>
                            </a>
                            <a href="javascript:;" class="select-list1 cf goLogin">
                                <img src="<?php echo $imgCdnUrl ?>/images_fh/ls/ls-27.png" alt="">
                                <span>幸运双色球</span>
                                <i class="i0">千万大奖等着您</i>
                            </a>
                            <!-- <a href="javascript:;" class="select-list1 cf goLogin">
                                <img src="<?php echo $imgCdnUrl?>/images_fh/ls/ls-15.png" alt="">
                                <span>福彩3D</span>
                            </a> -->
                        </div>
                        <div onmouseover="amv()" onmouseout="amu()">
                            <div class="select-all cf">
                                <div class="se-all fl">
                                    全部
                                </div>
                                <div class="fr se-all-tx">
                                    <a href="javascript:;" class="goLogin" class="on"> 重庆时时彩</a>
                                    <a href="javascript:;" class="goLogin"> 山东11选5</a>
                                    <a href="javascript:;" class="goLogin"> 北京PK拾</a>
                                    <a href="javascript:;" class="goLogin"> 江苏快三</a>
                                    <a href="javascript:;" class="goLogin"> 幸运秒秒彩</a>
                                    <a href="javascript:;" class="goLogin"> 幸运分分彩</a>
                                    <a href="javascript:;" class="goLogin">极速⑥合彩</a>
                                </div>
                            </div>
                            <div class="se-all-list ds">
                                <div class="all">全部</div>
                                <a href="javascript:;" class="goLogin">重庆时时彩</a>
                                <a href="javascript:;" class="goLogin">新疆时时彩</a>
                                <a href="javascript:;" class="goLogin">天津时时彩</a>
                                <a href="javascript:;" class="goLogin">幸运秒秒彩</a>
                                <a href="javascript:;" class="goLogin">幸运分分彩</a>
                                <a href="javascript:;" class="goLogin">东京1.5分彩</a>
                                <a href="javascript:;" class="goLogin">印尼5分彩</a>
                                <!-- <a href="javascript:;" class="goLogin">七星彩</a> -->
                                <a href="javascript:;" class="goLogin">幸运28</a>
                                <!-- <a href="javascript:;" class="goLogin">泰国5分彩</a> -->
                                <a href="javascript:;" class="goLogin">山东11选5</a>
                                <a href="javascript:;" class="goLogin">腾讯分分彩</a>
                                <a href="javascript:;" class="goLogink">山东快乐扑克</a>
                                <a href="javascript:;" class="goLogin">江西11选5</a>
                                <a href="javascript:;" class="goLogin">江苏11选5</a>
                                <a href="javascript:;" class="goLogin">广东11选5</a>
                                <a href="javascript:;" class="goLogin">11选5分分彩</a>
                                <a href="javascript:;" class="goLogin">北京PK拾</a>
                                <a href="javascript:;" class="goLogin">福彩3D</a>
                                <a href="javascript:;" class="goLogin">体彩P3P5</a>
                                <a href="javascript:;" class="goLogin">香港⑥合彩</a>
                                <a href="javascript:;" class="goLogin">江苏快三</a>
                                <a href="javascript:;" class="goLogin">安徽快三</a>
                                <a href="javascript:;" class="goLogin">快三分分彩</a>
                                <a href="javascript:;" class="goLogin">双色球</a>
                                <a class="goLogin" href="javascript:void(0);">极速⑥合彩</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!--mennu-->
                <div class="navmenu fl bar-a">

                        <a href="?a=login" <?php if($GLOBALS['SESSION']['nav'] == 'main') echo 'class="on"'; ?>>首页</a>

                        <a href="?c=fake&a=lobby" class="<?php if($GLOBALS['SESSION']['nav'] == 'game') echo 'on'; ?>"  >购彩大厅</a>


                        <a href="?c=fake&a=download" <?php if($GLOBALS['SESSION']['nav'] == 'mobile') echo 'class="on"'; ?>>手机购彩
                        <div class="hot">
                            <div class="hot-all"><img src="<?php echo $imgCdnUrl ?>/images_fh/hot.gif" alt=""></div>
                        </div>
                        </a>

                        <a href="?c=fake&a=result" class="<?php if($_SESSION['nav'] == 'help') echo 'on'; ?>"  >开奖结果</a>

                        <a href="?c=fake&a=chart" <?php if($GLOBALS['SESSION']['nav'] == 'chart') echo 'class="on"'; ?>>开奖走势
                        <div class="hot1">
                            <div class="hot-all"><img src="<?php echo $imgCdnUrl ?>/images_fh/hot.gif" alt=""></div>
                        </div>
                        </a>

                        <a href="?c=fake&a=platformact" <?php if($GLOBALS['SESSION']['nav'] == 'promo') echo 'class="on"'; ?>>优惠活动
                        <div class="hot1">
                            <div class="hot-all"><img src="<?php echo $imgCdnUrl ?>/images_fh/hot.gif" alt=""></div>
                        </div>
                        </a>

                        <a href="?c=fake&a=latestnew" <?php if($GLOBALS['SESSION']['nav'] == 'news') echo 'class="on"'; ?>>彩票资讯</a>

                        <!--<a href="?c=fake&a=safe" <?php if($GLOBALS['SESSION']['nav'] == 'help') echo 'class="on"'; ?>>帮助中心</a>-->
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

                    <a class="duokebo-btn" target="_blank" href="<?php echo getFloatConfig('service_url'); ?>"><img src="<?php echo $imgCdnUrl ?>/images_fh/servicelogo.png" alt="">客服</a>
                </div>
            </div>
        </div>
    </div>
