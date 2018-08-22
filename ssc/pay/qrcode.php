<?php

if (isset($_GET['cdn'])){
    $imgCdnUrl = $_GET['cdn'];
}

?>

<html>
	<head>
		<title>识别二维码付款</title>
		<meta http-equiv="content-type" content="text/html;charset=utf-8">
		<meta name="menu" content="terminalVersion" />
		<script src="<?php echo $imgCdnUrl ?>/js/jquery.min.1.7.2.js" type="text/javascript" ></script>
		<script src='<?php echo $imgCdnUrl ?>/js/jquery.qrcode.js' type="text/javascript"></script>
		<script src='<?php echo $imgCdnUrl ?>/js/utf.js' type="text/javascript"></script>
		<link href="<?php echo $imgCdnUrl ?>/css/wechat_pay.css" rel="stylesheet" media="screen">
	</head>
	<script>
        <?php
        $code = '';
        if (isset($_GET['code'])){
            $code = $_GET['code'];
        }

        if (isset($_GET['netway'])){
            $netway = $_GET['netway'];
        }

        if (isset($_GET['amount'])){
            $amount = $_GET['amount'];
        }

        if($netway =='QQ') {
            $query_string = $_SERVER['QUERY_STRING'];
            $queryStrings = explode('&netway', $query_string);
            $url = str_replace('code=','',$queryStrings[0]);
            //_wv=1027&_bid=2183&t=6V59c752b6e8b415cefa5ae80e6262c4&
            echo "qrcode_url = '$url'";
        } else {
            echo "qrcode_url = '$code'";
        }
        ?>
		
	</script>
	<body style="text-align: center;background:#FFF;">
	<div class="body">
    <h1 class="mod-title">
	<span class="ico-wechat">
        <?php
            if($netway == "WX") {
                echo "</span><span class=\"text\">微信扫码支付</span>";
            } else if ($netway == "ZFB"){
                echo "</span><span class=\"text\">支付宝钱包扫码支付</span>";
            } else if ($netway == "QQ") {
            	echo "</span><span class=\"text\">QQ 扫码支付</span>";
            } else if ($netway == "BD") {
                echo "</span><span class=\"text\">百度钱包扫码支付</span>";
            }
        ?>
    </h1>
    <div class="mod-ct">
        <div class="order">
        </div>
        <div class="amount">
            <span>充值 </span><?php echo $amount?><span> 元</span>
        </div>
		<div id="contentWrap">
			<div id="widget table-widget">
				<div class="pageTitle"></div>
		    	<div class="pageColumn">
					<div>
						<input id="qrcodeURL" type="hidden"/>
		      		</div>
				    <table >
				    	<div id="code" style="margin-top: 50px;display: none;"></div>
				    	<div style="margin-top: 50px;">
				    		<img id="qrcode"  width='25%' src="" />
				    	</div>
					</table>
				</div>
			</div>
		</div>
        <div class="tip">
            <span class="dec dec-left"></span>
            <span class="dec dec-right"></span>
            <div class="ico-scan" style="background: url('<?php echo $imgCdnUrl ?>/images/wechat-pay.png') 0 0 no-repeat;">
            </div>
            <div class="tip-text">
                <p>
 					<?php
                    if (isset($_GET['netway'])){
                        $netway = $_GET['netway'];
                    }

                    if($netway=="WX") {
                        echo "用微信扫一扫";
                    } else if ($netway=="ZFB") {
                        echo "用支付宝钱包扫一扫";
                    } else if ($netway=="QQ")  {
                        echo "用 QQ 扫一扫";
                    } else if ($netway=="BD")  {
                        echo "用百度钱包扫一扫";
                    }
                    ?>
                </p>
                <p>
                    扫描二维码完成支付
                </p>
            </div>
        </div>
    </div>
    <div class="foot">
        <div class="inner">
            <p>
            </p>

        </div>
    </div>
</div>

		<script type="text/javascript">
			if (qrcode_url === null){
				
			}else{
				$("#code").qrcode({ 
			    width: 350,
			    height: 350,
			    text: qrcode_url
				});
				$(function(){
					var type = "png";
					var oCanvas = document.getElementById("myCanvas");
					var imgData = oCanvas.toDataURL(type);
					var qrcode = document.getElementById("qrcode");
					qrcode.src = imgData;
				});
			
			}
			
		</script>	
	</body>
</html>