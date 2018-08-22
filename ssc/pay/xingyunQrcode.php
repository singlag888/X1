<?php
$pay_image = isset($_REQUEST["netway"]) ? trim($_REQUEST["netway"]) : '';
$coin = isset($_REQUEST["coin"]) ? trim($_REQUEST["coin"]) : '0';
$qq = isset($_REQUEST["qq"]) ? trim($_REQUEST["qq"]) : '';
$wechat = isset($_REQUEST["wechat"]) ? trim($_REQUEST["wechat"]) : '';
$imgCdnUrl = isset($_REQUEST["cdn"]) ? trim($_REQUEST["cdn"]) : '';

function getClientIp()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}

?>
<html>
<head>
    <title>幸运支付</title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <meta name="menu" content="terminalVersion"/>
    <script src="<?php echo $imgCdnUrl ?>/js/jquery.min.1.7.2.js" type="text/javascript" ></script>
    <script src='<?php echo $imgCdnUrl ?>/js/jquery.qrcode.js' type="text/javascript"></script>
    <script src='<?php echo $imgCdnUrl ?>/js/utf.js' type="text/javascript"></script>
    <link href="<?php echo $imgCdnUrl ?>/css/wechat_pay.css" rel="stylesheet" media="screen">
</head>
<?php
echo ' <script>';
echo "var queryString = '" . $_SERVER['QUERY_STRING'] . "';";
echo ' </script>';
?>
<script>
    var sUserAgent = navigator.userAgent.toLowerCase();
    var bIsIpad = sUserAgent.match(/ipad/i) == "ipad";
    var bIsIphoneOs = sUserAgent.match(/iphone os/i) == "iphone os";
    var bIsMidp = sUserAgent.match(/midp/i) == "midp";
    var bIsUc7 = sUserAgent.match(/rv:1.2.3.4/i) == "rv:1.2.3.4";
    var bIsUc = sUserAgent.match(/ucweb/i) == "ucweb";
    var bIsAndroid = sUserAgent.match(/android/i) == "android";
    var bIsCE = sUserAgent.match(/windows ce/i) == "windows ce";
    var bIsWM = sUserAgent.match(/windows mobile/i) == "windows mobile";

    if (!(bIsIpad || bIsIphoneOs || bIsMidp || bIsUc7 || bIsUc || bIsAndroid || bIsCE || bIsWM)) {

    } else {
        window.location.href = 'xingyunQrcodeWap.php?' + queryString;
    }
</script>
<body style="text-align: center;background:#FFF;">
<div class="body">
    <h1 class="mod-title">
        <span class="text">微信支付宝QQ充值</span>
    </h1>
    <div class="mod-ct">
        <div class="order">
        </div>
        <div class="amount">
            <span>充值 </span>
            <?php echo $coin ?><span> 元</span>
        </div>
        <div id="contentWrap">
            <div id="widget table-widget">
                <div class="pageTitle"></div>
                <div class="pageColumn">
                    <div>
                        <input id="qrcodeURL" type="hidden"/>
                    </div>
                    <table>
                        <div id="code" style="margin-top: 50px;display: none;"></div>
                        <div style="margin-top: 50px;">
                            <?php
                            echo '<img id="qrcode" width="25%" src="' . $pay_image . '"/>';
                            ?>
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
                    用微信或支付宝扫一扫
                </p>
                <p>
                    扫描二维码完成支付
                </p>
                <p>
                    支付成功后要返回，会生成一个交易单号，还请您凭会员账号、交易单号提交至在线客服<br/>客服QQ：<?php echo $qq; ?>，客服微信：<?php echo $wechat; ?>
                </p>
                <br/>
                <p>
                    重要提醒：
                </p>
                <p>
                    支付成功后要返回，会生成一个<a style="color: #FF0000; font-weight: bold">交易单号</a>，还请您凭<a
                            style="color: #FF0000; font-weight: bold">会员账号</a>、<a
                            style="color: #FF0000; font-weight: bold">交易单号</a>提交至在线客服<br/><a style="color: #FF0000">客服QQ：<?php echo $qq; ?>，客服微信：<?php echo $wechat; ?></a>
                </p>
                <p>
                    手机支付的请保存二维码到相册，再用微信或支付宝扫码
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
</body>
</html>
