<!DOCTYPE HTML>
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
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/Jstyle.css"/>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script><!--通用-->
    <script src="<?php echo $imgCdnUrl ?>/js/common.js" type="text/javascript"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer/layer.min.js"></script> <!-- 调用弹出层 -->
    <script src="<?php echo $imgCdnUrl ?>/js/jquery.md5.js" type="text/javascript"></script>

</head>

<body class="loginbody">
<div class="loginContent">
    <div class="loginMain">
        <div class="loginCont fix">
            <style type="text/css">
                button, input[type=button], input[type=file], input[type=submit] {
                    cursor: pointer;
                    -webkit-appearance: none;
                }

                .login-back {
                    position: absolute;
                    top: 0;
                    left: 20px;
                }

                .login-back a {
                    color: #fff;
                    font-size: .65rem;
                }

                .logoForm {
                    width: 100%;
                }

                .loginbox ul {
                    padding-top: .5rem;
                    border-top: 1px solid #ddd;
                    background: #fff;
                }

                .loginbox ul li {
                    position: relative;
                    margin-bottom: .5rem;
                    width: 100%;
                }

                .loginbox ul li input {
                    padding: 3px .5rem;
                    width: 11.8rem;
                    height: 2rem;
                    border: 1px solid #ccc;
                    -webkit-appearance: none;
                }

                .loginLast {
                    display: inline-block;
                    width: 100%;
                }

                .loginbox ul li img {
                    position: absolute;
                    top: 0;
                    right: .6rem;
                    display: block;
                }

                .footList {
                    display: block;
                }

                .btn_login {
                    display: block;
                    margin: 0 auto;
                    margin-top: 30px;
                    width: 92%;
                    height: 40px;
                    outline: 0;
                    border: 0;
                    border-radius: 3px;
                    background: #f13031;
                    color: #fff;
                    font-size: 18px;
                    font-family: 'Helvetica Neue', Arial, "Hiragino Sans GB", 'Microsoft YaHei', sans-serif;
                    line-height: 40px;
                }

                .footList {
                    padding: 30px 20px 0;
                }

                .loginbox ul li span b {
                    display: inline-block;
                    width: 3.5rem;
                    text-align: right;
                    font-size: .6rem;
                }

                .loginbox ul li img {
                    z-index: 11;
                    cursor: pointer;
                }

                .classyzm-text-m {
                    position: absolute;
                    top: 0;
                    right: .6rem;
                    z-index: 0;
                    display: block;
                    width: 4rem;
                    height: 2rem;
                    border: 1px solid #ccc;
                    color: #999;
                    text-align: center;
                    font-size: .6rem;
                    line-height: 2rem;
                    cursor: pointer;
                }

                .mark_ok {
                    position: fixed;
                    top: 0;
                    left: 0;
                    z-index: 999;
                    width: 100%;
                    height: 100%;
                }

                .mobile_tips {
                    padding-left: 3.6rem;
                    color: red;
                    text-align: left;
                    line-height: 1rem;
                }

                /*所有header公用*/
                .headerbg {
                    position: fixed !important;
                    height: 1.875rem;
                    background-color: #e4393c;
                    color: white;
                    padding: 0.1rem 0rem 0.6rem 0.6rem;
                    position: absolute;
                    top: 0;
                    width: 100%;
                    z-index: 999999;
                }

                .headerbg .headbox01 {
                    position: absolute;
                    left: 0;
                    top: 0;
                    height: 1.875rem;
                    text-align: left;
                    display: inline-block;
                    z-index: 1;
                    line-height: 1.875rem;
                    padding: 0rem 0.5rem;
                    min-width: 1.875rem;
                    color: white;
                    font-size: 0.65rem;
                    font-weight: bold;
                }

                .headerbg .headtetle {
                    font-size: 0.8rem;
                    position: absolute;
                    left: 0;
                    top: 0;
                    height: 1.875rem;
                    text-align: center;
                    display: inline-block;
                    z-index: 0;
                    line-height: 1.875rem;
                    width: 100%;
                }

                .loginContent {
                    padding-top: 1.875rem;
                }

                .headerbg .headboxright {
                    position: absolute;
                    z-index: 1;
                    display: inline-block;
                    right: 0;
                    top: 0;
                    min-width: 1.875rem;
                    line-height: 1.875rem;
                    text-align: right;
                    padding: 0 0.5rem;
                    height: 1.875rem;
                    color: white;
                    font-size: 0.65rem;
                    font-weight: bold;
                }


            </style>
            <!--/*头部*/-->
            <header class="headerbg">
                <a class="headbox01" href="?a=login">首 页</a>
                <p class="headtetle">账户注册</p>
                <a class="headboxright" href="?a=login2">登 录</a>
            </header>
            <div class="logoForm">
                <div class="loginbox">
                    <form>
                        <ul class="fix">
                            <li class="username">
                                    <span class="loginLast">
                                        <b><font color="red">*</font>会员账号：</b>
                                        <input style="font-size:12px;color:#a9a9a9;" type="text" name="username"
                                               maxlength="18" id="username" class="reginput"
                                               value="以字母开头，长度为6-12个字母或数字!"
                                               onfocus="if (this.value=='以字母开头，长度为6-12个字母或数字!'||this.value=='用户名不能为空!')this.value='',this.style.color='#666',this.style.fontSize='14px'"
                                               onblur="if (this.value=='')this.value='用户名不能为空!',this.style.color='red'">
                                    </span>
                            </li>
                            <?php if ($realNameToggle > 0): ?>
                                <li class="username">
                                    <span class="loginLast">
                                        <b><font color="red">*</font>真实姓名：</b>
                                        <input style="font-size:12px;color:#a9a9a9;" type="text" name="real_name"
                                               maxlength="18" id="real_name" class="reginput" placeholder="真实姓名">
                                    </span>
                                    <p class="mobile_tips">必须与您的银行账户名称相同，否则不能出款!</p>
                                </li>
                            <?php endif; ?>
                            <li class="password">
                                    <span class="loginLast">
                                        <b><font color="red">*</font>登录密码：</b>
                                        <input style="font-size:12px;color:#a9a9a9;" type="text" name="pwd"
                                               maxlength="15" id="pwd" class="reginput" value="6-15位字母数字，且不能全为字母或数字"
                                               onfocus="if (this.value=='6-15位字母数字，且不能全为字母或数字'||this.value=='密码不能为空!'||this.value=='密码有误!')this.value='',this.style.color='#666',this.style.fontSize='14px',this.type='password'"
                                               onblur="if (this.value=='')this.value='密码不能为空!',this.style.color='red',this.type='text'">
                                    </span>
                            </li>
                            <li class="password">
                                    <span class="loginLast">
                                        <b><font color="red">*</font>确认密码：</b>
                                        <input style="font-size:12px;color:#a9a9a9;" type="text" name="confirm_pwd"
                                               maxlength="15" id="confirm_pwd" class="reginput" value="确认密码"
                                               onfocus="if (this.value=='确认密码'||this.value=='确认密码错误')this.value='',this.style.color='#666',this.style.fontSize='14px',this.type='password'"
                                               onblur="if (this.value=='')this.value='确认密码错误',this.style.color='red',this.type='text'">
                                    </span>
                            </li>

                            <?php if ($regNeedMobile > 0): ?>
                                <li class="mobile">
                                    <span class="loginLast">
                                        <b><font color="red">*</font>手机号码：</b>
                                        <input style="font-size:12px;color:#a9a9a9;" type="text" name="mobile"
                                               maxlength="20" id="mobile" class="reginput" value="手机号码"
                                               onfocus="if (this.value=='手机号码'||this.value=='手机号不能为空')this.value='',this.style.color='#666',this.style.fontSize='14px',this.type='text'"
                                               onblur="if (this.value=='')this.value='手机号不能为空',this.style.color='red',this.type='text'">
                                    </span>
                                    <p class="mobile_tips">平台将通过此方式确认会员本人并给予活动回馈</p>
                                </li>
                            <?php endif; ?>

                            <?php if ($regNeedQq > 0): ?>
                                <li class="qq">
                                    <span class="loginLast">
                                        <b><font color="red">*</font>QQ号码：</b>
                                        <input style="font-size:12px;color:#a9a9a9;" type="text" name="qq"
                                               maxlength="20" id="qq" class="reginput" value="QQ号码"
                                               onfocus="if (this.value=='QQ号码')this.value='',this.style.fontSize='14px',this.style.color='#666'"
                                               onblur="if (this.value=='')this.value='QQ号码',this.style.fontSize='12px',this.style.color='#a9a9a9'">
                                    </span>
                                </li>
                            <?php endif; ?>

                            <li class="vercode">
                                    <span class="loginLast">
                                        <b><font color="red">*</font>验证码：</b>
                                        <input type="text" name="verifyCode" maxlength="4" id="verifyCode"
                                               placeholder="验证码" class="regyzm" style="width:7rem;">
                                        <img class="login-yzmimg" style="width:4rem;height:2rem" alt="">
                                        <a class="classyzm-text-m">获取验证码</a>
                                    </span>
                            </li>
                        </ul>
                        <div class="loginFeatures fix">
                            <a class="passwordinfo off" href="javascript:void(0);">
                                <span><b></b></span>
                            </a>
                            <!-- <a class="CustService" href="" target="_blank">客服</a> -->
                        </div>
                        <input type="hidden" name="verify" value="register">

                        <div class="loginBtn">
                            <input type="button" value="立即注册" name="btn_Add" id="btn_Add" class="btn_login"/>
                        </div>
                    </form>
                </div>
                <div class="loginboxFoot"></div>
            </div>
        </div>
        <!-- footlist开始 -->
        <div class="footList">
            <div class="loginBrowser">
                <a class="loginChrome" href="http://www.google.cn/intl/zh-CN/chrome/browser/" target="_blank"></a>
                <a class="loginFirefox" href="http://www.firefox.com.cn/" target="_blank"></a>
                <a class="loginIE" href="https://support.microsoft.com/zh-cn/help/17621/internet-explorer-downloads"
                   target="_blank"></a>
            </div>
            <p>1.标记有<span style="color: red"> *</span> 者为必填项目。</p>
            <p>2.建议使用谷歌、火狐、IE（Internet Explorer）9.0 浏览器，可达到最佳使用效果</p>
        </div>
        <!-- footlist结束 -->
    </div>
</div>
<!-- 注册成功弹出层 -->
<div class="popLayer mark_ok">
    <div class="reg_poplayer">
        <h1>恭喜您注册成功！！</h1>
        <div class="reg_main">
            <div class="reg_boxtab">
                <div class="reg_content">
                    <p><label>用户名：</label><span id="successUsername"></span></p>
                    <p><label>密　码：</label><span id="successPassword"></span></p>
                    <p style="color: #f13031;">请牢记您的密码，点击确定登录平台</p>
                </div>
            </div>
            <div class="reg_btn"><a id="loginURL" href="#" class="reglogin">确定</a></div>
        </div>
    </div>
    <div class="maskLayer"></div>
</div>
<!-- 弹出层end -->
<?php $this->import('public_tongji') ?>
</body>
<script type="text/javascript">
    $(function () {
        $(".passwordinfo").click(function () {
            if (!$(this).hasClass("off")) {
                $(this).addClass("off");
                $(".password input")[0].type = "password";
                $(".password input")[1].type = "password";
            } else {
                $(this).removeClass("off");
                $(".password input")[0].type = "text";
                $(".password input")[1].type = "text";
            }
        });
        $("#username").blur(function () {
            var regex1 = /^[a-zA-Z]\w{5,11}$/;
            if ($("#username").val() == '') {
                $("#usernameTips").html("用户名不能为空!");
                $("#usernameTips").css("color", "red");
            } else if (!regex1.test($("#username").val())) {
                $("#usernameTips").html("以字母开头，长度为6-12个字母或数字!");
                $("#usernameTips").css("color", "red");
            } else {
                $("#usernameTips").html("正确");
                $("#usernameTips").css("color", "#CCCCCC");
            }
        });
        var Flag=0;
        $('#btn_Add').click(function () {
            var regex1 = /^[a-zA-Z]\w{5,11}$/;
            var regex2 = /^[0-9]+$/;
            var regex3 = /^[a-zA-Z]+$/;
            var mobileRegex = /^1[34578]\d{9}$/;
            var is_qq = "<?php echo $regNeedQq;?>";
            if(is_qq > 0)
            {
                var qq = $('#qq').val();
                var regx = /^[1-9]\d{4,11}$/;
                if(regx.test(qq) === false)
                {
                    layer.alert('qq号码格式不正确!');
                }
            }
            // if ($("#agree").attr("checked") != true) {
            //     alert('请先同意条款再注册');
            //     return false;
            // }
            if ($("input[name=username]").val() == '' || !regex1.test($("input[name=username]").val())) {
                $("input[name=username]").val("以字母开头，长度为6-12个字母或数字!");
                $("input[name=username]").css({"color": "red", "fontSize": "12px"});
                return false;
            }
            if ($("#pwd").val() == '' || $("#pwd").val().length < 6 || regex2.test($("#pwd").val()) || $("#pwd").val().length > 15 || regex3.test($("#pwd").val())) {
                $("#pwd").val("密码有误!");
                $("#pwd").css({"color": "red", "fontSize": "12px"});
                $("#pwd")[0].type = "text";
                return false;
            }
            if ($("#confirm_pwd").val() == '' || $("#pwd").val() != $("#confirm_pwd").val()) {
                $("#confirm_pwd").val("确认密码错误");
                $("#confirm_pwd").css({"color": "red", "fontSize": "12px"});
                $("#confirm_pwd")[0].type = "text";
                return false;
            }
            <?php if($regNeedMobile > 0): ?>
            if (!mobileRegex.test($('#mobile').val())) {
                layer.alert('请输入正确的手机号!');
                return false;
            }
            <?php endif; ?>
            Flag+=1;		
		    if(Flag==1){
            $.post(
                '/?a=marketReg',
                $("form").serialize(),
                function (response) {
                    if (response.errno == 0) {
                        $("#successUsername").html($('input[name=username]').val());
                        $("#successPassword").html($('input[name=pwd]').val());
                        $("#loginURL").attr('href', response.loginURL);
                        $(".popLayer").fadeIn(100);
                    }
                    else {
                        layer.alert(response.errstr + '!');
                    }
                }, 'json');
                var disonckick=setInterval(function(){
					Flag=0;
					if(Flag==0){
						clearInterval(disonckick);
						
					}
				},5000)
            }
            return false;
        });

    });

    //注册弹出js
    $(function () {
        //关注册闭弹出层
        $(".reglogin").click(function () {
            $(".popLayer").fadeOut(100);
        });
    });

    // $('#verifyCode').focus(function(){
    //    getCode('.login-yzmimg');
    // })
    $('.login-yzmimg').click(function () {
        getCode('.login-yzmimg');
        $('#verifyCode').focus();
    });
    getCode('.login-yzmimg');
    function getCode(ele) {
        $(ele).attr('src', '/?a=verifyCode&' + Math.random());
    }
</script>
</html>
