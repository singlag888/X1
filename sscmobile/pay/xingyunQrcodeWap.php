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
    <link href="<?php echo $imgCdnUrl ?>/css/wechat_pay_wap.css" rel="stylesheet" media="screen">
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0"/>
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no">
</head>
<body style="text-align: center;background:#FFF;">
<div class="body">
    <h1 class="mod-title">
        <span class="text">微信支付宝QQ充值
</span>
    </h1>
    <div class="mod-ct">
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
                        <div>
                            <?php
                            echo '<img id="qrcode" width="50%" src="' . $pay_image . '"/>';
                            ?>
                        </div>
                    </table>
                </div>
            </div>
        </div>
        <div class="tip">

            <div class="tip-text">

                <p style="margin-top: 0.4rem">
                    重要提醒：
                </p>
                <p>
                    支付成功后要返回，会生成一个<a style="color: #FF0000; font-weight: bold">交易单号</a>，还请您凭<a
                            style="color: #FF0000; font-weight: bold">会员账号</a>、<a
                            style="color: #FF0000; font-weight: bold">交易单号</a>提交至在线客服<br/><br/><a
                            style="color: #FF0000">客服QQ：<?php echo $qq; ?><br/>客服微信：<?php echo $wechat; ?></a>
                </p>
                <br/>
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
