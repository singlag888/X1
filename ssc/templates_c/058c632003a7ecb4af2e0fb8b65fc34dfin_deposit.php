<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="webkit" name="renderer">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo config::getConfig('site_title'); ?></title>
        <?php $this->import('public_cssjs') ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/global_reset.css?v=<?php echo $html_version; ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/all_LightBlue.css?v=<?php echo $html_version; ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/rechargeBank.css?v=<?php echo $html_version; ?>" />

        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/ZeroClipboard.107.js"></script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js_fh/pay.js?v=<?php echo $html_version; ?>"></script>
        <script type="text/javascript">
            var cardList = JSON.parse('<?php  echo $cardList?>');
            var min_deposit_limit = JSON.parse(<?php echo ($min_deposit_limit)?>);
            var max_deposit_limit = JSON.parse(<?php echo ($max_deposit_limit)?>);
        </script>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/thirdParty.js?v=<?php echo $html_version; ?>"></script>
<!--        --><?php
//        echo "<script>";
//        echo "var cardList=$cardList;";
//        echo "var min_deposit_limit=$min_deposit_limit;";
//        echo "</script>";
//        ?>

    </head>
    <body style="background:#fff;">
        <!-- 中间选择游戏部分 -->
        <form action="" name="bp" target="_blank" id="bp" method="post">
            <table class="recharge_bank_body"  border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="30%">会员账号</td>
                    <td width="70%"><?php echo $user['username'] ?></td>
                </tr>
                <tr>
                    <td width="30%">银行卡选择</td>
                    <td width="70%">
                        <?php $cardList = json_decode($cardList, true); if (count($cardList) == 0) echo "暂无收款卡（请选择其他充值方式）"; ?>
                        <?php foreach($cardList as $cardIndex => $card): ?>
    <?php echo "<a class=\"third_party_btn\" id=\"" . $cardIndex . "\">" . $card['login_name'] . "</a>"; ?>
                        <?php endforeach; ?>
                    </td>
                </tr>

                <tr id="third_party_bank" style="display: none">
                    <td width="30%">银行选择</td>
                    <td width="70%">
                        <select name="third_party_bank_id">
                            <option value="1" selected="selected">工商银行</option>
                            <option value="2">农业银行</option>
                            <option value="3">建设银行</option>
                            <option value="4">招商银行</option>
                            <option value="5">交通银行</option>
                            <option value="6">中信银行</option>
                            <option value="7">邮政储汇</option>
                            <option value="8">中国光大银行</option>
                            <option value="9">民生银行</option>
                            <option value="10">上海浦东发展银行</option>
                            <option value="11">兴业银行</option>
                            <option value="12">广发银行</option>
                            <option value="13">平安银行</option>
                            <option value="15">华夏银行</option>
                            <option value="16">东莞银行</option>
                            <option value="17">渤海银行</option>
                            <option value="19">浙商银行</option>
                            <option value="20">北京银行</option>
                            <option value="21">广州银行</option>
                            <option value="22">中国银行</option>
                        </select>
                    </td>
                </tr>
                <!----------------------- snow 2017-10-04 添加支付账号功能---------------------------------->

                <tr id="user_pay_id_tr" >
                    <td width="30%">*支付账号</td>
                    <td width="70%">
                        <input name="pay_account_id" type="text" class="recharge_input" />
                    </td>
                </tr>
                <!----------------------- snow 2017-10-04 添加支付账号功能---------------------------------->
                <tr>
                    <td width="30%">*存款金额</td>
                    <td width="70%">
                        <input name="deposit_amount" type="text" class="recharge_input" />
                        <span id="card_remark" class=""></span>
                        <input name="username" type="hidden" value="<?php echo $user['username']; ?>" />
                        <input name="user_id" type="hidden" value="<?php echo $user['user_id']; ?>" />
                        <input name="card_id" type="hidden" value="" />
                        <input name="bank_id" type="hidden" value="" />
                        <input name="codes" type="hidden" value="" />
                        <input name="requestURI" type="hidden" value="">
                        <input name="call_back_url" type="hidden" value="">
                        <input name="shop_order_num" type="hidden" value="">
                        <input name="th_ts" type="hidden" value="">
                        <input name="netway" type="hidden" value="">
                        <input name="hash" type="hidden" value="<?php echo $hash; ?>">
                        <input name="is_newpay" type="hidden" value="">
                    </td>
                </tr>

                <?php if($usage == 3):?>
                <tr>
                    <td width="30%">*支付账户昵称</td>
                    <td width="70%">
                        <input name="remark" type="text"  class="recharge_input" id="remark"/>
                    </td>
                </tr>
                <?php endif;?>
            </table>
            <input name="sub" type="button" value="下一步" class="third_party_next_btn" />
        </form>
    </body>
</html>
