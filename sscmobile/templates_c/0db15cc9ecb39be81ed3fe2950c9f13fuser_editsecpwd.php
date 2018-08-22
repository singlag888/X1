<!DOCTYPE HTML>   <!-- 资金密码修改 -->
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="webkit" name="renderer"><!-- 页面默认用极速核 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- 指定浏览器按照最高的标准模式解析页面针对IE -->
    <meta content="telephone=no,email=no" name="format-detection"/><!-- 使设备浏览网页时对数字不启用电话功能 -->
    <meta name="apple-touch-fullscreen" content="YES"/><!-- "添加到主屏幕"后，全屏显示 -->
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <!-- 如果内容设置为YES，Web应用程序运行在全屏模式;否则，它不会。默认行为是使用Safari浏览器显示网页内容 -->
    <!--<meta http-equiv="Cache-Control" content="no-cache"/>-->  <!-- 每次打开都清除浏览器页面缓存 -->
    <meta http-equiv="Cache-Control" content="no-siteapp"/><!-- 度SiteApp转码声明 -->
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo config::getConfig('site_title'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobileStyle.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle.css">
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script> <!-- layer调用弹出层 -->
</head>

<body>
<div class="operate_middle_page">
    <!--/*头部*/-->
    <header class="headerbg">
        <a class="headbox01" href="javascript:history.go(-1)"><img
                    src="<?php echo $imgCdnUrl ?>/images/mobile/head_Box1.png"/></a>
        <p class="headtetle">设置</p>
    </header>
    <form name="form2" id="form2" class="martop1875" onsubmit="return false;">
        <?php if($key == 'up'):?>
        <li class="ImportText MarginTop10">
            <label for="password" class="control_label">旧资金密码：</label>
            <input type="password" placeholder="请输入资金密码" name="oldsecpassword" id="oldsecpassword">
        </li>
        <?php endif;?>
        <li class="ImportText MarginTop10">
            <label for="password" class="control_label">资金密码：</label>
            <input type="password" placeholder="请输入资金密码" name="secpassword" id="secpassword">
        </li>
        <li class="ImportText MarginTop10">
            <label for="password2" class="control_label">确认资金密码：</label>
            <input type="password" placeholder="请确认资金密码" name="secpassword2" id="secpassword2">
        </li>
        <!--                    <li class="ImportText MarginTop10">-->
        <!--                      <label for="safe_pwd" class="control_label">安全码：</label>-->
        <!--                        <input type="password" placeholder="请输入安全码" name="safe_pwd" id="safe_pwd">-->
        <!--                    </li>-->
    </form>
    <div class="remind">
        <img src="<?php echo $imgCdnUrl ?>/images/mobile/point_01.png"/>
        <p>
            为了您的账户安全，如需更改银行卡信息，<br/>请联系我们客服！</p>
    </div>
    <p class="button_top msgsend_btn_layer">
        <input type="hidden" name="key" value="<?php echo $key;?>">
        <input type="button" class="Button_red" id="pwdBtn" value="确定修改" name="submit">
    </p>
</div>


<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>

<script>
    var formUrl = '/?c=user&a=<?php echo ACTION; ?>';
    $(document).ready(function () {
        <?php $this->import("default_Df"); ?>
        $('#pwdBtn').on('click', function () {
            var pwd = $('input[name=secpassword]').val();
            var pwd2 = $('input[name=secpassword2]').val();
            var oldsecpwd = $('input[name=oldsecpassword]').val();
//            var safe_pwd = $('input[name=safe_pwd]').val();
            if (pwd.length == 0) {
                top.layer.alert('请输入新的资金密码');
                return false;
            }
            var key = '<?php echo $key?>';
            if(key == 'up') {
                if (oldsecpwd.length == 0) {
                    top.layer.alert('请输入旧的资金密码');
                    return false;
                }
                if (pwd != $('input[name=secpassword2]').val()) {
                    top.layer.alert('您输入的新的资金密码和确认资金密码不相同');
                    return false;
                }
            }

            var re1 = /^[A-Za-z]+$/;
            var re2 = /^\d+$/;
            var re3 = /^\w{6,15}$/;
            if (!re3.test(pwd) || re2.test(pwd) || re1.test(pwd)) {
                top.layer.alert("密码必须是6-15位字母数字混合，且不能为全是数字或全是字母");
                return false;
            }
//            if (safe_pwd.length == 0) {
//                top.layer.alert('请输入安全码');
//                return false;
//            }
            $.ajax({
                type: "POST",
                url: formUrl,
                data: {'submit': 1, secpassword: pwd, secpassword2: pwd2,oldsecpwd:oldsecpwd},//, safe_pwd: safe_pwd
                dataType: "json", //返回0和1
                success: function (data) {
                    if (data.errno == 0) {
                        top.layer.alert(data.errstr, 1);
                        $('#form2').trigger('reset');
                    } else if (data.errno > 0) {
                        top.layer.alert(data.errstr);
                    }
                }
            });
        });


    });
</script>


<?php $this->import('public_tongji') ?>
</body>
</html>