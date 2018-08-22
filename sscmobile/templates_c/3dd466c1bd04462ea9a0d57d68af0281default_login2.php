<!doctype html>
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
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobileStyle.css?v=<?php echo $html_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/mobile_overallStyle.css?v=<?php echo $html_version; ?>">
        <script src="<?php echo $imgCdnUrl ?>/js/common.js?v=<?php echo $html_version; ?>" type="text/javascript"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js?v=<?php echo $html_version; ?>"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js?v=<?php echo $html_version; ?>"></script>
        <script src="<?php echo $imgCdnUrl ?>/js/jquery.md5.js" type="text/javascript"></script>
        <link href="<?php echo $imgCdnUrl ?>/js/jqueryUI/dialog/css/jquery.dialog.blue.css?v=<?php echo $html_version; ?>" rel="stylesheet" type="text/css"  />
        <script src="<?php echo $imgCdnUrl ?>/js/jqueryUI/dialog/js/jquery.dialog.js?v=<?php echo $html_version; ?>" type="text/javascript"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js?v=<?php echo $html_version; ?>"></script> <!-- layer调用弹出层 -->
        <script>
            var agreementHtml = '<div>';
            agreementHtml += '<div class="agreement3">';
            agreementHtml += '<h3>用户协议</h3>';
            agreementHtml += '<p>一、为避免于本网站投注时之争议，请会员务必于进入网站前详阅本娱乐城所定之各项规则，客户一经“我同意”进入本网站进行投注时，即被视为已接受纵横游戏的所有协议与规则。</p>';
            agreementHtml += '<p>二、请定期修改自己的登录密码及资金密码，会员有责任确保自己的帐户以及登入资料的保密性，以会员帐号及密码进行的任何网上投注，将被视为有效。敬请不定时做密码变更之动作，若帐号密码被盗用，进行的投注，本公司一概不负赔偿责任。</p>';
            agreementHtml += '<p>三、投注相关条款：①网上投注如未能成功提交，投注将被视为无效。②凡玩家于开奖途中且尚无结果前自动或强制断线时，并不影响开奖结算之结果。③若遇官网未开奖或开奖结果错误，本平台将根据实际情况做退奖退买处理；④如遇发生不可抗拒之灾害，骇客入侵，网络问题造成数据丢失的情况，以本公司公告为最终方案。⑤本公司将会对所有的电子交易进行记录，如有任何争议，本公司将会以注单记录为准。</p>';
            agreementHtml += '<p>四、若经本公司发现会员以不正当手法<利用外挂程式>进行投注或以任何非正常方式进行的个人、团体投注有损公司利益之投注情事发生，本公司保留权利取消该类注单以及注单产生之红利，并停用该会员帐号。无论代理还是会员，发现漏洞隐瞒不报，利用漏洞恶意刷钱、通过非法手段作弊、或造谣污蔑，攻击平台者，经平台核实后一律无条件永久冻结账号处理，账号所有金钱概不退还。</p>';
            agreementHtml += '<p>五、若本公司发现会员有重复申请帐号行为时，保留取消、收回会员所有优惠红利，以及优惠红利所产生的盈利之权利。每位玩家、每一住址、每一电子邮箱、每一电话号码、相同支付卡/信用卡号码，以及共享电脑环境 (例如:网吧、其他公共用电脑等)只能够拥有一个会员帐号，各项优惠只适用于每位客户在本公司唯一的帐户。</p>';
            agreementHtml += '<p>六、本平台高频彩种每期最高奖金限额300000.00元，超出按300000.00元计算，超出的奖金无效并清0；低频彩种每期最高奖金限额100000.00元，超出按100000.00元计算，超出的奖金无效并清0。</p>';
            agreementHtml += '<p>七、本平台任何彩种每期单挑奖金限额20000.00元，超出按20000.00元计算，超出的奖金无效并清0。单挑模式说明：彩种和玩法通过换算后属于单挑规定之内的，中奖慨率低于1%的均为单挑模式。例如：[五星直选]1000注及以内，[四星直选]100注及以内，[三星直选]10注及以内，[二星直选]4注及以内，[五星组选120]10注及以内，[五星组选60]20注及以内，[五星组选30]35注及以内，[五星组选20]50注及以内，[五星组选10]90注及以内，[五星组选5]90注及以内，[四星直选24]5注及以内，[四星直选12]10注及以内，[四星直选6]20注及以内，[四星直选4]25注及以内，[三星组六]2注及以内，[三星组三]4注及以内，[二星组选]2注及以内......等等。</p>';
            agreementHtml += '<p>八、平台取款时间为上午10:00-凌晨2:00，开业期间提款次数不限制，后期根据实际情况调整，每次最低100元,最高100000.00元，由于自身绑定账号错误或者提款自行取消等自身问题造成的取款次数减少失效，平台慨不负责。对于日量较大的客户，根据自身情况可向平台申请绿色VIP通道，增加提款次数和提款额度。</p>';
            agreementHtml += '<p>九、平台严禁内招，首次发现冻结账号30天处理，期间不允许任何开号和招商。第二次发现直接封号或降级处理。情形严重，扰乱市场者，直接驱逐出平台，拉入平台黑名单，永久不再接纳。</p>';
            agreementHtml += '<p>十、为了防止有人恶意洗钱，会员提款必须要消费充值的一倍方可进行，否则财务不予受理。</p>';
            agreementHtml += '<p>十一、不经核实乱放高点，恶意利用手中配额扰乱市场者，广告词中以超高返点分红为诱饵，回收全部配额处理，严重的降级或者封号。</p>';
            agreementHtml += '<p>十二、本公司保留不定时修改或增加本协定或游戏规则或保密条例等的操作权利，更改之条款将从更改发生后立即生效，并保留一切有争议事项及最后的决策权。</p>';
            agreementHtml += '</div>';
            agreementHtml += '<div class="agreement2"><input class="agreement2-input" type="checkbox" value="2" id="flag" /> 我已阅读并同意用户协议  <div class="but-box"><input id="goIn" class="disable ui-button" type="button" value="进入平台" />  <input style="background:gray;border:1px solid gray;" class="ui-button" type="button" value="取消" onclick="$agreementHtml.dialog(\'close\', window.parent);" /></div></div>';
            //agreementHtml += '</div>';
            agreementHtml += '</div>';

            var $agreementHtml = $(agreementHtml);

            $(function() {
                $("#flag").live('click',function(){
                    if($(this).attr("checked")){
                        $("#goIn").removeClass('disable');
                    }else{
                        $("#goIn").addClass('disable');
                    }
                });

                $("#goIn").live('click',function(){
                    if(!$(this).hasClass('disable')){
                        $('#loginBtn').click();
                    }
                });

                var submitAction = function(e) { //按esc关闭层
                    var key = e.keyCode ? e.keyCode : e.which;
                    if (key == 13) {
                        $('#loginBtn').click();
                    }
                }
                $('input[name=username]').keyup(submitAction);
                $('input[name=password]').keyup(submitAction);
                $('input[name=verifyCode]').keyup(submitAction);

                $('#loginBtn').click(function() {
                    if ($('input[name=username]').val() == '') {
                        layer.alert('请输入用户名 ');
                        return false;
                    }
                    if ($('input[name=password]').val() == '') {
                        layer.alert('请输入密码');
                        return false;
                    }

                    if ($('input[name=verifyCode]').val() == '') {
                        layer.alert('请输入验证码');
                        return false;
                    }
                    $.ajax({
                        type : 'post',
                        dataType : 'json',
                        url:'?a=login2',
                        data : {
                                verifyCode: $('input[name=verifyCode]').val(),
                                username: $('input[name=username]').val(),
                                encpassword: $('input[name=password]').val(),
                                flag:2,
                                verify: 'login'
                            },
                        success : function(response){
                            if (response.errno == 0) {
                                location.href = response.errstr;
                            }
                            else {
                                if(response.errno == 4){
                                    $agreementHtml.dialog({
                                        bgiframe:true,
                                        width:"14rem",
                                        showTitle:false,
                                        buttons:{
                                        }
                                    }, window.parent);
                                }else{
                                    layer.alert(response.errstr + '!');
                                }
                            }
                        },
                        error : function(data){
                            <?php if(RUN_ENV == 3): ?>
                                console.log(data);
                            <?php endif; ?>
                        }
                    });
                });
            });
        </script>
    </head>
    <body class="loginbody">
        <div class="loginTop">      <!-- toplist开始 -->
           <img src="<?php echo $imgCdnUrl ?>/images/mobile/logobig.png">            <!-- 背景图 -->
        </div>                       <!-- toplist结束 -->
        <div class="loginContent">
            <div class="loginMain">
                <li>
                    <span class="loginLast">
                        <!--<b><img src="<?php echo $imgCdnUrl ?>/images/mobile/registerName.png"></b>-->
                        <input type="text" name="username" id="accountName" placeholder="输入您的账号">
                        <!-- <p class="tips">请输入正确用户名</p> //预留验证-->
                    </span>
                </li>
                <li>
                    <span class="loginLast">
                       <!--<b><img src="<?php echo $imgCdnUrl ?>/images/mobile/registerPassword.png"></b>-->
                       <input type="password" name="password" id="accountPassword" value="" placeholder="输入您的密码">
                    </span>
                     <!-- <p class="tips">密码错误，请输入正确密码</p> //预留验证 -->
                </li>
                <li>
                    <span class="loginLast loginyzminput"><input type="text" class="ssc-yzm" placeholder="验证码:" name="verifyCode" maxlength="4" value="" id="getCode"></span>
                    <img class="login-yzmimg" align="center" Valign="middle" width="4rem" height="2rem">
                    <b class="classyzm-text">获取验证码</b>
                </li>
                <li>
                     <span class="loginLastPassword">
                         <input type="checkbox" name="vehicle" value="Bike" id="save"><span>记住密码</span>
                     </span>
                </li>
            </div>
                <div id="gozc_btn" class="loginBtn">
                <input type="button" value="登录" class="btn_login" id="loginBtn">
                <a class="gozc_btn" href="?a=marketReg">注册</a>
                </div>
        </div>
        <script>
               //保存数据
                    $("#save").click(function(){
                        var accountName = $("#accountName").val();
                        var accountPassword = $("#accountPassword").val();
                        localStorage.setItem(accountName, accountPassword);
                    });
                $(document).ready(function(){
                    $("#accountName").keyup(function(){
                        var search_site = $("#accountName").val();
                        var sitename = localStorage.getItem(search_site);
                        $("#accountPassword").val(sitename)
                    });
                });
            // $('#getCode').focus(function(){
            //     getCode('.login-yzmimg');
            // })
            //$('.login-yzmimg').click(function(){
                //getCode('.login-yzmimg');
                //$('#getCode').focus();
            //})
            //function getCode(ele){
                //$(ele).attr('src','?a=verifyCode&'+Math.random())
            //}

                // $.ajax({
                //     type : 'post',
                //     dataType : 'json',
                //     url : '?a=domainInfo',
                //     success : function(res){
                //         var htmStr = '';
                //         if(res == 1){
                //             htmStr = '<a class="gozc_btn" href=\'?a=marketReg\'>注册</a>';
                //         }
                //         $('#gozc_btn').append(htmStr);
                //     },
                //     error : function(data){
                //             console.log(data);
                //     }
                // });
                              $('.login-yzmimg').click(function(){
                                  getCode('.login-yzmimg');
                                  $('#verifyCode').focus();
                              });
                              getCode('.login-yzmimg');
                              function getCode(ele){
                                  $(ele).attr('src','/?a=verifyCode&'+Math.random());
                              }
        </script>
    <?php $this->import('public_tongji') ?>
</body>
</html>
