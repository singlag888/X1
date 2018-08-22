    <!--底部-->
    <div class="foot-box cf">
        <div class="foot-play">
            <div class="foot-other cf">
                <a href="javascript:;" class="other-1 fl">
                    <div class="other-img fl">
                        <img src="<?php echo $imgCdnUrl ?>/images_fh/pliceman.png" alt="">
                    </div>
                    <div class="other-text fl">
                        <p>网络警察</p>
                        <p>举报平台</p>
                    </div>
                </a>
                <a href="javascript:;" class="other-1 fl">
                    <div class="other-img fl">
                        <img src="<?php echo $imgCdnUrl ?>/images_fh/wangan.png" alt="">
                    </div>
                    <div class="other-text fl">
                        <p>公共信息</p>
                        <p>网络安全监察</p>
                    </div>
                </a>
                <a href="javascript:;" class="other-1 fl">
                    <div class="other-img fl">
                        <img src="<?php echo $imgCdnUrl ?>/images_fh/wangjiao.png" alt="">
                    </div>
                    <div class="other-text fl">
                        <p>网上交易</p>
                        <p>保障中心</p>
                    </div>
                </a>
                <a href="javascript:;" class="other-1 fl">
                    <div class="other-img fl">
                        <img src="<?php echo $imgCdnUrl ?>/images_fh/wangxin.png" alt="">
                    </div>
                    <div class="other-text fl">
                        <p>网站信用良好</p>
                        <p>Credit Rating</p>
                    </div>
                </a>
                <a href="javascript:;" class="other-2 fl">
                    <img src="<?php echo $imgCdnUrl ?>/images_fh/wangshen.png" alt="">
                </a>
            </div>
            <div class="foot-copy">
                <span>2002-2017©中彩网</span>
                <span>| </span>
                <span>客服邮箱：<?php echo getFloatConfig('email_address'); ?></span>
                <span>|</span>
                <span>客服QQ：<a href="tencent://message/?uin=<?php echo getFloatConfig('qq_number'); ?>&Site=sc.chinaz.com&Menu=yes"><?php echo getFloatConfig('qq_number'); ?></a></span>
                <span>|</span>
                <div class="foot-ts">中彩网郑重提示：彩票有风险，投注需谨慎！ 不向未满18周岁的青少年出售彩票</div>
            </div>

        </div>
    </div>

    <!-- 多条最新公告弹出层 -->
        <div class="layer_containerMore" id="layer_containerMore">
        <!-- 右侧内容部分 -->
            <div class="MoreNewsCont">
                <?php foreach ($notices as $k => $v) : ?>
                    <div class="popNewsLayer" id="notice_info_<?php echo $v['notice_id']; ?>">
                        <h2><?php echo $v['title']; ?></h2>
                        <?php echo $v['content']; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <!-- 左侧信息列表 -->
            <div class="MainListUl_More">
                <ul>
                    <?php foreach ($notices as $k => $v) : ?>
                        <li id="notice_infoa_<?php echo $v['notice_id']; ?>"><span class="listpart"><a href="javascript:void(0);" class="ShowNews" notice_id="<?php echo $v['notice_id']; ?>"><?php echo mb_substr($v['title'], 0, 17, 'utf-8'); ?></a></span><span class="time"><font color="red"><?php echo date("Y-m-d", strtotime($v['start_time'])); ?></font></span>

                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
 <?php
$floatConfig = getFloatConfig();
if ($floatConfig['left_img']): ?>
    <!--左侧浮动窗-->
    <div class="float-l-div">
        <img usemap="#leftMap" border="0" src="<?php echo $imgCdnUrl ?>/<?php echo $floatConfig['left_img']; ?>"/>
        <?php if ($floatConfig['left_target']): ?>
            <map name="leftMap" id="leftMap">
                <?php $preHeight = 0;
                foreach ($floatConfig['left_target'] as $target): ?>
                    <area shape="rect" coords="<?php echo "0,{$preHeight},9999,{$target['height']}"; ?>"
                        <?php if ($target['target']) echo 'target="_blank"'; ?>
                          href="<?php if (isset($GLOBALS['SESSION']['user_id']) && $GLOBALS['SESSION']['user_id']) {
                              echo $target['url'];
                          } else {
                              echo $target['fake_url'];
                          } ?>"/>
                    <?php $preHeight = $target['height']; endforeach; ?>
            </map>
        <?php endif; ?>
        <a class="float-close01"><img src="<?php echo $imgCdnUrl ?>/images_fh/float_06.png"></a>
    </div>
<?php endif; ?>
<?php if ($floatConfig['right_img']): ?>
    <!--右侧浮动窗-->
    <div class="float-r-div">
        <img usemap="#rightMap" border="0" src="<?php echo $imgCdnUrl ?>/<?php echo $floatConfig['right_img']; ?>"/>
        <?php if ($floatConfig['right_target']): ?>
            <map name="rightMap" id="rightMap">
                <?php $preHeight = 0;
                foreach ($floatConfig['right_target'] as $target):
                    ?>
                    <area shape="rect" coords="<?php echo "0,{$preHeight},9999,{$target['height']}"; ?>"
                        <?php if ($target['target']) echo 'target="_blank"'; ?>
                          href="<?php if (isset($GLOBALS['SESSION']['user_id']) && $GLOBALS['SESSION']['user_id']) {
                              echo $target['url'];
                          } else {
                              echo $target['fake_url'];
                          } ?>"/>
                    <?php
                    $preHeight = $target['height'];
                endforeach; ?>
            </map>
        <?php endif; ?>
        <a class="float-close"><img src="<?php echo $imgCdnUrl ?>/images_fh/float_06.png"></a>
    </div>
<?php endif; ?>
<?php if ($floatConfig['left_img'] || $floatConfig['right_img']): ?>
    <script type="text/javascript">
        //关闭右侧浮动窗
        $('.float-close').click(function () {
            $('.float-r-div').css({'height': '0'});
        });
        //关闭左侧浮动窗
        $('.float-close01').click(function () {
            $('.float-l-div').css({'height': '0'});
        });
    </script>
<?php endif; ?>
<script src="<?php echo $imgCdnUrl ?>/js_fh/fun.js?v=<?php echo $html_version ?>"></script>
<script>
$(function () {
     //充值的tip
    $('.pay').on('mouseover', function() {
        layer.tips('充值', this, {
            tips: [3, '#F26C4F'],
            time: 1000,
            maxWidth: 240
        });
    });
    //提款
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
                    layer.alert("您尚未设置安全码，请先 <a style='vertical-align:top;color:#ef984b;' href='javascript:void(0);' onclick=window.location.href='?c=user&a=editSafePwd';parent.layer.closeAll();>点此设置安全码</a>",{icon:7});
                }
                else if(data.errno == 3){
                    layer.alert("您尚未设置资金密码，请先 <a style='vertical-align:top;color:#ef984b;' href='javascript:void(0);' onclick=window.location.href='?c=user&a=editSecPwd';parent.layer.closeAll();>点此设置资金密码</a>",{icon:7});
                }
                else if(data.errno == 4){
                    layer.alert("您尚未绑定任何银行卡，请先 <a style='vertical-align:top;color:#ef984b;' href='javascript:void(0);' onclick=window.location.href='?c=fin&a=bindCard';parent.layer.closeAll();>点此绑定卡号</a>方可提款",{icon:7});
                }
                else if(data.errno == -1){
                    layer.alert(data.errstr)
                }
            }
        });
     });
    //退出
    $('#logoutBtn').click(function() {
        location.href = 'index.jsp?a=logout';
    });
    function showNotice(notice_id) {
        $(".popNewsLayer").hide();
        $("#notice_info_" + notice_id).show();
        $("#notice_infoa_"+notice_id).css({'border-right': '2px solid red'});
        var i = layer.open({
            type: 1,
            title: '最新公告',
            skin: 'layui-layer-rim newsbg',
            shade: [0.7, '#000'],
            style:'background-color',
            offset: ['50px', ''],
            area: ['850px', '500px'],
            content: $('#layer_containerMore'),       //.html(),
            success: function(dom,index) {
                 $(".ShowNews").click(function() {
                    $(".popNewsLayer").hide();
                    $(".MainListUl_More >ul >li").css({'border-right': '2px solid white'});
                    var notice_id = $(this).attr('notice_id');

                    $("#notice_info_"+notice_id).show();
                    $("#notice_infoa_"+notice_id).css({'border-right': '2px solid red'});
                });
                $(".MainListUl_More a").eq(0).click();
            }

        });
    }

        var flag;
        setInterval(function () {
        if (flag) {
        $('.hot').css({'display': 'block'});
        $('.hot1').css({'display': 'block'})
        } else {
        $('.hot1').css({'display': 'block'});
        $('.hot').css({'display': 'block'})
        }
        flag = !flag;
        }, 100);

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
        // window.setInterval(function() {
        // $('#nowBalance').click();
        // }, 10000);
        //金额隐藏显示切换
        $('#nowBalance').text('***');
        $('#nowBalance').next().click(function(){
        if($('#nowBalance').next().text() == '显示'){
                $('#nowBalance').text('￥<?php echo $user['balance']; ?>');
                $('#nowBalance').next().text('隐藏');
            }else if($('#nowBalance').next().text() == '隐藏'){
                $('#nowBalance').text('***');
                $('#nowBalance').next().text('显示');
            }
        });

        /****************公告滚动begin***************/
        var speed = 30;
        var scroll_begin = document.getElementById("NewSl_begin");
        var scroll_end = document.getElementById("NewSl_end");
        var scroll_div = document.getElementById("NewSl");
        scroll_end.innerHTML = scroll_begin.innerHTML;

        function Marquee() {
            if (scroll_end.offsetWidth-scroll_div.scrollLeft<=0)
              scroll_div.scrollLeft -= scroll_begin.offsetWidth;
              else
              scroll_div.scrollLeft++;
              }
              var MyMar = setInterval(Marquee, speed);
              scroll_div.onmouseover = function() { clearInterval(MyMar); }
              scroll_div.onmouseout = function() { MyMar = setInterval(Marquee, speed); }
              /****************公告滚动end***************/

              //最新公告弹出层
              $(".ShowNewsMore").live("click",function() {
              var notice_id = $(this).attr('notice_id');
              showNotice(notice_id);
              });
              });
</script>
<!--BEGIN ProvideSupport.com Visitor Monitoring Code -->
<div id="ciGP0T" style="z-index:100;position:absolute"></div><div id="sdGP0T" style="display:none"></div><script type="text/javascript">var seGP0T=document.createElement("script");seGP0T.type="text/javascript";var seGP0Ts=(location.protocol.indexOf("https")==0?"https":"http")+"://image.providesupport.com/js/0wxbq4zfrd9381qcyjqs58ee1r/safe-monitor.js?ps_h=GP0T&ps_t="+new Date().getTime();setTimeout("seGP0T.src=seGP0Ts;document.getElementById('sdGP0T').appendChild(seGP0T)",1)</script><noscript><div style="display:inline"><a href="http://www.providesupport.com?monitor=0wxbq4zfrd9381qcyjqs58ee1r"><img src="http://image.providesupport.com/image/0wxbq4zfrd9381qcyjqs58ee1r.gif" style="border:0px" alt=""/></a></div></noscript>
<!--— END ProvideSupport.com Visitor Monitoring Code —-->