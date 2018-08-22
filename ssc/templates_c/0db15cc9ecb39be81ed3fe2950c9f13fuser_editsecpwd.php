<!DOCTYPE HTML>   <!-- 资金密码修改 -->
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo config::getConfig('site_title'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/common_operate.css" />
    <?php $this->import('public_cssjs') ?>
</head>

<body>
<!-- 顶部导航信息部分 -->
<?php $this->import('public_header') ?>
<div class="operate_middle_page">
    <?php $this->import("public_usermenu"); ?>

    <div class="common_pages service_center_pageheight padding_top_eight">
        <div class="middle_modify_layer">
          <!--  <form name="form2" id="form2" action="index.jsp?c=user&a=<?php echo ACTION; ?>" method="post"> -->
            <form name="form2" id="form2" onsubmit="return false;">
              <?php if($key == 'up'):?>
                <div class="form_group">
                    <label for="secpassword" class="control_label">旧的资金密码：</label>
                    <div class="col_sm_10">
                        <input type="password" class="form_control two_hundred_width" name="oldsecpassword" id="oldsecpassword">
                    </div>
                </div>
                <?php endif;?>
                <div class="form_group">
                    <label for="secpassword" class="control_label">新的资金密码：</label>
                    <div class="col_sm_10">
                        <input type="password" class="form_control two_hundred_width" name="secpassword" id="secpassword">
                    </div>
                </div>
                <div class="form_group">
                    <label for="secpassword2" class="control_label">确认资金密码：</label>
                    <div class="col_sm_10">
                        <input type="password" class="form_control two_hundred_width" name="secpassword2" id="secpassword2">
                    </div>
                </div>
<!--                <div class="form_group">-->
<!--                    <label for="safe_pwd" class="control_label">安全码：</label>-->
<!--                    <div class="col_sm_10">-->
<!--                        <input type="password" class="form_control two_hundred_width" name="safe_pwd" id="safe_pwd">-->
<!--                    </div>-->
<!--                </div>-->
                <div class="form_group msgsend_btn_layer">
                    <input type="submit" id="pwdBtn" name="submit" value="确定修改" class="default_navyblue_btn modify_btn">
                </div>
            </form>
        </div>
    </div>
</div>
<?php $this->import('public_foot') ?>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->

<script>
    var formUrl = '?c=user&a=<?php echo ACTION; ?>';
    $(document).ready(function() {

        $('#pwdBtn').on('click',function(){
            var pwd = $('input[name=secpassword]').val();
            var pwd2 = $('input[name=secpassword2]').val();
            var oldsecpwd = $('input[name=oldsecpassword]').val();
              if (oldsecpwd.length == 0) {
                  top.layer.alert('请输入旧的资金密码');
                  return false;
              }
//          var safe_pwd = $('input[name=safe_pwd]').val();
            if (pwd.length == 0) {
                top.layer.alert('请输入新的资金密码');
                return false;
            }
            if(pwd!=$('input[name=secpassword2]').val()){
                top.layer.alert('您输入的新的资金密码和确认资金密码不相同');
                return false;
            }

            var re1 = /^[A-Za-z]+$/;
            var re2 = /^\d+$/;
            var re3 = /^\w{6,15}$/;
            if(!re3.test(pwd) || re2.test(pwd) || re1.test(pwd)){
                top.layer.alert("密码必须是6-15位字母数字混合，且不能为全是数字或全是字母");
                return false;
            }
//            if(safe_pwd.length == 0){
//                top.layer.alert('请输入安全码');
//                return false;
//            }
            $.ajax({
                type: "POST",
                url: formUrl,
                data: {'submit':1,secpassword:pwd,secpassword2:pwd2},//,safe_pwd:safe_pwd
                dataType: "json", //返回0和1
                success: function(data) {
                    if(data.errno == 0){
                       top.layer.alert(data.errstr,1);
                        $('#form2').trigger('reset');
                    }else if(data.errno > 0){
                        top.layer.alert(data.errstr);
                    }
                }
            });
        });


    });
</script>


</body>
</html>
