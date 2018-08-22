<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="yes" name="apple-touch-fullscreen">
    <meta content="telephone=no,email=no" name="format-detection">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>优惠活动-<?php echo config::getConfig('site_title'); ?></title>
    <style>.header-logo .logo-play { padding:16px 0;}</style>
    <?php $this->import('public_cssjs') ?>
</head>
<body>
<div class="big-box">
    <!--头部-->
    <?php $this->import('public_fake_header') ?>
    <!--主要main-->
    <div class="discount">
        <div class="dc-play">
        <?php foreach($activities as $v): ?>
        <?php if($v['thumb_img'] == '' || $v['main_img'] == '') continue; ?>
        <div class="promo_main dc-list1 cf">
                <a href="javascript:;" class="slide dc-img fl">
                    <img class="batplay" src="<?php echo $imgCdnUrl ?>/<?php echo $v['thumb_img'] ?>" alt="">
                </a>
                <div class="fl dc-ul">
                    <h2><?php echo $v['title'] ?></h2>
                    <ul>
                        <li>活动对象：<?php echo $v['target'] ?></li>
                        <li>活动内容：<?php echo $v['content'] ?></li>
                        <li>活动时间：<?php echo substr($v['start_time'],0,10) . '至' . substr($v['end_time'],0,10) ?></li>
                    </ul>
                </div>
                <div class="info">
                    <p class="t_i">
                    <img src="<?php echo $imgCdnUrl ?>/<?php echo $v['main_img'] ?>" alt="">
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <!--优惠详情展开-->
    <script type="text/javascript">
    $(function(){
        $(".promo_main").eq(0).find(".info").height(".info");
        $(".promo_main").eq(0).find(".batplay").html("+展开查看详情");
        $(".promo_main").each(function(){
            $(this).find(".batplay").click(function(){
                  var oindex = $(this).parent(".slide").siblings(".info");
                    if(oindex.height()==0){
                        var curHeight = oindex.height();
                        var autoHeight = oindex.css("height","auto").height();
                        oindex.height(curHeight);
                        oindex.animate({height:autoHeight},500);
                        //$(this).html("-收起");
                    }
                    else{
                        oindex.animate({height:"0"});
                        //$(this).html("+展开查看详情");
                    }
                 })

         })

    })
</script>
    <?php $this->import('fake_public_foot') ?>
    <script>
        (function(){
            $(".goLogin").live('click',function(){
            layer.alert('请先登录!', {
            skin: 'layui-layer-lan',
            closeBtn: 1,
            anim: 3 ,//动画类型
            title:'',
            })
        });
        })()
    </script>
</div>
</body>
</html>