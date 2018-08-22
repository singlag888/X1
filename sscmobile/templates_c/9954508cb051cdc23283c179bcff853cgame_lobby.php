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
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobileStyle.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle.css">
    </head>
    <body>
    	<div style="width:16rem">
	    	<!--/*头部*/-->
            <header class="headerbg">
            <a class="headbox01" href="javascript:history.go(-1)"><img src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
            <p class="headtetle">游戏大厅</p>
	      </header>
	        <nav class="GameNav GameNav_x" style="margin-top: 1.875rem;">
			    <ul>
			        <li>信用彩<b class="menunew_x">信</b></li>|
			        <li>时时彩<b class="menunew_g">官</b></li>|
			        <li>11选5<b class="menunew_g">官</b></li>|
			        <li>低频彩<b class="menunew_g">官</b></li>|
			        <li>快乐彩<b class="menunew_g">官</b></li>
                </ul>
			</nav>
						<div class="GameMain DisplayNone pad_bottom6">
				<article>
				    <div class="DisPlay game-w100">
				      
				    </div>
				</article>
			</div>
			<div class="GameMain DisplayNone pad_bottom6">
				<article>
				    <!-- <div class="BWtitle DisPlay MarginTop05">时时彩</div> -->
				    <div class="DisPlay game-w100">
				        
				    </div>
				</article>

			</div>
			<div class="GameMain DisplayNone pad_bottom6">
				<article>
				    <div class="DisPlay game-w100">

				    </div>
				</article>
			</div>
			<div class="GameMain DisplayNone pad_bottom6">
				<article>
				    <div class="DisPlay game-w100">
				    </div>
				</article>
			</div>
			<div class="GameMain DisplayNone pad_bottom6">
				<article >
				    <div class="DisPlay game-w100">
				    </div>
				</article>
			</div>
			 <!--弹窗-->
            <div class="toolTipBox DisplayNone">
                <p>温馨提示   <a class="toolTipBoxG"><img class="toolTipBoxClose FloatRight" src="<?php echo $imgCdnUrl ?>/images/mobile/toolTipBoxClose.png"/></a></p>
                <div class="toolTipBoxText">
                    <pre>只有代理可以进入</pre>
                </div>
            </div>
			<!--充值弹窗-->
			<div class="rechargeBoxBox DisplayNone">
					<p>温馨提示   <a class="rechargeBoxBoxG"><img class="rechargeBoxBoxClose FloatRight" src="<?php echo $imgCdnUrl ?>/images/mobile/toolTipBoxClose.png"/></a></p>
					<div class="rechargeBoxBoxText">
							<pre>研发中，请到PC端充值提现</pre>
					</div>
			</div> 
	        <footer class="dtbg">
            <div class="FootMain"><a  href="index.jsp?&a=welcome"><i class="footimg01"><img src="<?php echo $imgCdnUrl ?>/images/mobile/footimg01.png"></i><p>首页</p></a></div>
            <div class="FootMain"><a  href="index.jsp?c=game&a=lobby"><i class="footimg02"><img src="<?php echo $imgCdnUrl ?>/images/mobile/footimg02.png"></i><p>大厅</p></a></div>
            <div class="FootMain"><a  href="index.jsp?c=help&a=platformact"><i class="footimg04"><img src="<?php echo $imgCdnUrl ?>/images/mobile/footimg04.png"></i><p>活动</p></a></div>
            <div class="FootMain"><a  href="?c=game&a=chart&lottery_id=1"><i class="footimg05"><img src="<?php echo $imgCdnUrl ?>/images/mobile/footimg05.png"></i><p>走势</p></a></div>
            <div class="FootMain"><a  href="index.jsp?c=game&a=packageList"><i class="footimg03"><img src="<?php echo $imgCdnUrl ?>/images/mobile/footimg03.png"></i><p>我的</p></a></div>
        </footer>
    	</div>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/template-web.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/ext.js?v=<?php echo $html_version; ?>"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/lobby.js?v=<?php echo $html_version; ?>"></script>
    <?php $this->import('public_tongji') ?>
</body>
<script type="text/javascript">
	$(".GameNav ul li").each(function(i){
     	$(".GameNav ul li").eq(0).click();
     	 $(".GameNav ul li").eq(i).click(function(){
     	 	$(".GameNav ul li").eq(i).addClass("borcor").siblings().removeClass("borcor")
     	 	$(".GameMain").eq(i).addClass("DisplayBlock").siblings().removeClass("DisplayBlock")
     	 })
     })
</script>
<script type="text/template" id="template-model">
	<li class="GameOption LotteryId_{{value.lotteryId}}">
	    <a href="{{value.fun}}">
	    	<div class="gameLeft">
	    		<img class="dt-sscimg" src="<?php echo $imgCdnUrl ?>/images/mobile/id/{{value.lotteryId}}.png">
	    	</div>
	    	<div class="gameMid">
	    		<p>{{value.lastIssueInfo.cname}}</p>
	    		<div class="gameCir">
		    		<ul class="box_num">
		    			<li>正</li>
		    			<li>在</li>
		    			<li>开</li>
		    			<li>奖</li>
		    		</ul>
	    		</div>
	    		<div class="gameTime">距离下期开奖倒计时</div>
	    	</div>
	    	<div class="gameRight">
	    		<div class="dt-wq">
	    			<span class="issue"></span>
		    	</div>
		    	<div class="play-timer banner-timer">
					<span class="day">0</span>
					<em>:</em>
	                <span class="hour">0</span>
	                <em>:</em>
	                <span class="min">0</span>
	                <em>:</em>
	                <span class="second">0</span>
    			</div>
	    	</div>
		</a>
	</li>
</script>
<!--秒秒彩-->
<script type="text/html" id="LotteryId_15">
	<li class="GameOption">
	    <a href="?c=game&a=yzmmc">
	    	<div class="gameLeft">
	    		<img class="dt-sscimg" src="<?php echo $imgCdnUrl ?>/images/mobile/id/15.png"">
	    	</div>
	    	<div class="gameMid">
	    		<p>幸运秒秒彩</p>
	    		<div class="gameCir">
		    		<ul class="box_num">
		    			<li>正</li>
		    			<li>在</li>
		    			<li>开</li>
		    			<li>奖</li>
		    		</ul>
	    		</div>
	    		<div class="gameTime">距离下期开奖倒计时</div>
	    	</div>
	    	<div class="gameRight">
	    		<div class="dt-wq">
	    			<span class="on">第0000000期</span>
		    	</div>
		    	<div class="play-timer banner-timer">
					<span class="day">0</span>
					<em>:</em>
	                <span class="hour">0</span>
	                <em>:</em>
	                <span class="min">0</span>
	                <em>:</em>
	                <span class="second">0</span>
    			</div>
	    	</div>
		</a>
	</li>
</script>
    <!--电子游戏-->
    <script type="text/html" id="LotteryId_15">
        <li class="GameOption">
            <a href="?c=egame&a=lobby">
                <div class="gameLeft">
                    <img class="dt-sscimg" src="<?php echo $imgCdnUrl ?>/images/mobile/id/15.png"">
                </div>
                <div class="gameMid">
                    <p>电子游戏</p>
                    <div class="gameCir">
                        <ul class="box_num">
                            <li>正</li>
                            <li>在</li>
                            <li>开</li>
                            <li>奖</li>
                        </ul>
                    </div>
                    <div class="gameTime">距离下期开奖倒计时</div>
                </div>
                <div class="gameRight">
                    <div class="dt-wq">
                        <span class="on">第0000000期</span>
                    </div>
                    <div class="play-timer banner-timer">
                        <span class="day">0</span>
                        <em>:</em>
                        <span class="hour">0</span>
                        <em>:</em>
                        <span class="min">0</span>
                        <em>:</em>
                        <span class="second">0</span>
                    </div>
                </div>
            </a>
        </li>
    </script>
</html>