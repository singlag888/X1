<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo config::getConfig('site_title'); ?></title>
    <?php $this->import('public_cssjs') ?>
    <link rel="stylesheet" type="text/css"
          href="<?php echo $imgCdnUrl ?>/css/rechargeBank.css?v=<?php echo $html_version; ?>"/>
    <link rel="stylesheet" type="text/css"
          href="<?php echo $imgCdnUrl ?>/css/pay_banknew.css?v=<?php echo $html_version; ?>"/>
    <script type="text/javascript">
        var cardList = JSON.parse('<?php  echo json_encode($cards); ?>');
    </script>
    <script type="text/javascript"
            src="<?php echo $imgCdnUrl ?>/js/clipboard.min.js?v=<?php echo $html_version; ?>"></script>
</head>
<body style="background:#fff;">
<form action="" name="bp" target="_blank" id="bp" method="post">
    <input name="card_id" type="hidden"/>
    <input name="bank_id" type="hidden"/>
    <ul class="all">
        <li class="lt">
            <ul>
                <?php foreach ($cards as $key => $value): ?>
                    <?php if ($value['login_name']): ?>
                        <?php echo "<li><button type=\"button\" name=\"bank_card\"" . " data-remark=\"" . $value['remark'] . "\"  data-netway=\"" . $value['netway'] . "\" data-card_id=\"" . $value['card_id'] . "\" data-card_name=\"" . $value['card_name'] . "\" data-card_bank=\"" . $bankList[$value['bank_id']] . "\" data-card_num=\"" . $value['card_num'] . "\" data-bank_id=\"" . $value['bank_id'] . "\" value=\"" . $value['login_name'] . "\">" . $value['login_name'] . "</button></li>"; ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!--            <li><button type="button" name="button" class="active" value="支付宝转账">支付宝账号</button></li>-->
                <!--            <li><button type="button" name="button" value="银行卡">银行卡</button></li>-->
                <!--            <li><button type="button" name="button" value="微信">微信账号</button></li>-->
            </ul>
        </li>
        <li class="rt">
            <ul>
                <li style="width:601px; :80px;">
                    <p>
                        <span >支付宝昵称 :</span>
                        <input type="text" readonly="readonly" name="alipay_nickname" class="copy1"/>
                        <input type="button" value="复制" style="float:right" class="copy" data-clipboard-action="copy" data-clipboard-target=".copy1"/>
                    </p>
                </li>
                <li style="width:601px; height:72px;">
                    <p>
                        <span>账号 :</span>
                        <input type="text" readonly="readonly" name="alipay_account"  class="copy2"/>
                        <input type="button" value="复制" style="float:right" class="copy" data-clipboard-action="copy" data-clipboard-target=".copy2"/>
                    </p>
                </li>
                <li>
                    <p>支付宝扫一扫</p>
                    <img name="alipay_qrcode" src="" />
                    <p id="alipay_remark">此处添加文字说明</p>
                </li>
            </ul>
            <p>
                <span>存款姓名 :</span>
                <input type="text" name="alipay_deposit_name" />
                <span style="margin-left:160px ">存款金额 :</span>
                <input type="text" name="alipay_deposit_amount" />
            </p>
        </li>
        <li class="wx-rt" style="display:none">
            <ul>
                <li style="width:601px;height:80px;">
                    <p>
                        <span>微信昵称 :</span>
                        <input type="text" readonly="readonly" name="wechat_nickname" class="copy3"/>
                        <input type="button" value="复制" style="float:right" class="copy" data-clipboard-action="copy" data-clipboard-target=".copy3"/>
                    </p>
                </li>
                <li style="width:601px;height:72px;">
                    <p>
                        <span>账号 :</span>
                        <input type="text" readonly="readonly" name="wechat_account" class="copy4"/>
                        <input type="button" value="复制" style="float:right" class="copy" data-clipboard-action="copy" data-clipboard-target=".copy4"/>
                    </p>
                </li>
                <li>
                    <p>微信扫一扫</p>
                    <img name="wechat_qrcode" src="" />
                    <p id="wechat_remark">此处添加文字说明</p>
                </li>
            </ul>
            <p>
                <span>存款姓名 :</span>
                <input type="text" name="wechat_deposit_name" />
                <span style="margin-left:160px ">存款金额 :</span>
                <input type="text" name="wechat_deposit_amount" />
            </p>
        </li>
        <li class="bank-rt" style="display:none">
            <ul>
                <li style="width:601px; height:127px;">
                    <p>
                        <span >姓名 :</span>
                        <input type="text" readonly="readonly" name="name" class="copy5"/>
                        <input type="button" value="复制" style="float:right" class="copy" data-clipboard-action="copy" data-clipboard-target=".copy5"/>
                    </p>
                    <p>
                        <span>银行 :</span>
                        <input type="text" readonly="readonly" name="bank_name" class="copy6"/>
                        <input type="button" value="复制" style="float:right" class="copy" data-clipboard-action="copy" data-clipboard-target=".copy6"/>
                    </p>
                    <p>
                        <span>银行卡卡号 :</span>
                        <input type="text" readonly="readonly" name="card_number" class="copy7"/>
                        <input type="button" value="复制" style="float:right" class="copy" data-clipboard-action="copy" data-clipboard-target=".copy7"/>
                    </p>
                </li>
            </ul>
            <p>
                <span>存款姓名 :</span>
                <input name="bank_deposit_name" type="text" />
                <span style="margin-left:160px ">存款金额 :</span>
                <input name="bank_deposit_amount" type="text" />
            </p>
        </li>
    </ul>
    <input name="shop_url" type="hidden"/>
    <input name="th_ts" type="hidden"/>
    <input name="bankPay_btn" value="下一步" class="bankPay_btn" />
</form>
<script>
$(function(){
  var clipboard = new Clipboard('.copy');
          clipboard.on('success', function(e) {
              layer.msg('复制成功!',{icon:1,time:1000})
          });
          clipboard.on('error', function(e) {
              layer.msg('暂时不能复制',{icon:2,time:1000})
          });
})
    $(".all .lt ul li button").click (
        function (e) {
            var obj = $(e.target);
            obj.addClass("active");
            obj.parent().siblings().children().removeClass('active');
            var bankId = obj.attr('data-bank_id');
            var cardNum = obj.attr('data-card_num');
            var cardBank = obj.attr('data-card_bank');
            var cardName = obj.attr('data-card_name');
            var cardId = obj.attr('data-card_id');
            var netway = obj.attr('data-netway');
            var remark = obj.attr('data-remark');

            if (bankId < 50) {
                $(".all li.bank-rt").show();
                $(".all li.rt").hide();
                $(".all li.wx-rt").hide();
                $("input[name=card_number]").val(cardNum);
                $("input[name=bank_name]").val(cardBank);
                $("input[name=name]").val(cardName);
                $("input[name=card_id]").val(cardId);
                $("input[name=bank_id]").val(bankId);
            } else if (bankId === '99') {
                $(".all li.rt").show();
                $(".all li.wx-rt").hide();
                $(".all li.bank-rt").hide();
                $("input[name=alipay_nickname]").val(cardName);
                $("input[name=alipay_account]").val(cardNum);
                $("input[name=card_id]").val(cardId);
                $("input[name=bank_id]").val(bankId);
                $("#alipay_remark").text(remark);
                $("img[name=alipay_qrcode]").attr("src", netway);
            } else if (bankId === '98') {
                $(".all li.wx-rt").show();
                $(".all li.rt").hide();
                $(".all li.bank-rt").hide();
                $("input[name=wechat_nickname]").val(cardName);
                $("input[name=wechat_account]").val(cardNum);
                $("input[name=card_id]").val(cardId);
                $("input[name=bank_id]").val(bankId);
                $("#wechat_remark").text(remark);
                $("img[name=wechat_qrcode]").attr("src", netway);
            }
        }
    );
    //复制值得函数；


    function choiceBack(obj) {
        var card_id = $(obj).find('option:selected').data('card_id');
        var bank_id = $(obj).find('option:selected').data('bank_id');
        //>>添加跳转地址
        var shop_url = $(obj).find('option:selected').data('shop_url');
        $("div[id^='cont']").each(function () {
            if ($(this).css('display') == 'block') {
                $(this).css('display', 'none')
            }
        });

        $('#cont' + card_id).css('display', 'block');
        $('#cont2' + card_id).css('display', 'block');
        $("input[name='bank_id']").val(bank_id);
        $("input[name='card_id']").val(card_id);
        //>>添加跳转地址
        $("input[name='shop_url']").val(shop_url);

        /*************** 添加聊友或显示 支付账号*************************************/
        //>.如果不是,隐藏并且赋值为空
        $('#user_pay_id_tr').hide();
        $('#user_pay_id_tr').find('input').prop({'disabled': true});
        $('#user_pay_id_tr').find('input').val('');
        var card = null;
        $.each(cardList, function (key, value) {
            if (value.card_id == card_id) {
                card = value;
            }
        });
        if (card !== null) {
            if (card.pay_id_input === 1) {
                //>>显示支付账号
                $('#user_pay_id_tr').show();
                $('#user_pay_id_tr').find('input').prop({'disabled': false});
            }
        }
    }

</script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/bankPay.js?v=<?php echo $html_version; ?>"></script>
</body>
</html>
