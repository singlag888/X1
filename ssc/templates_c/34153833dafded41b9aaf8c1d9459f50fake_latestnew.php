<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="yes" name="apple-touch-fullscreen">
    <meta content="telephone=no,email=no" name="format-detection">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title>资讯-<?php echo config::getConfig('site_title'); ?></title>
    <?php $this->import("public_cssjs"); ?>
</head>
<body>
<div class="big-box">
    <!--头部-->
    <?php $this->import('public_fake_header') ?>
    <!--主要main-->
    <div class="infm">
        <div class="infm-play">
            <div class="infm-tt">
                资讯列表
            </div>
            <div class="infm-list">
            <?php if($news):?>
            <?php foreach($news as $new): ?>
                <div class="infm-list1 cf">
                    <a href="?c=fake&a=latestnew&article_id=<?php echo $new['article_id'] ?>">[新闻] <?php echo $new['title']; ?></a>
                    <div class="fr"><?php echo $new['start_time']; ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="infm-list1 cf">
                    暂无任何资讯
                </div>
        <?php endif; ?>
            </div>
            <div class="infm-bar cf">
                <?php echo $pageList; ?>
            </div>
        </div>
    </div>
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
    <script type="text/javascript">
    $(function(){
        $('.navmenu a').eq(6).addClass('bianse')
    })
</script>
</div>
</body>
</html>