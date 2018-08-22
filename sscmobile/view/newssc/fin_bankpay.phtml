<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="webkit" name="renderer"><!-- 页面默认用极速核 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"><!-- 指定浏览器按照最高的标准模式解析页面针对IE -->
    <meta content="telephone=no,email=no" name="format-detection" /><!-- 使设备浏览网页时对数字不启用电话功能 -->
    <!--<meta name="apple-touch-fullscreen" content="YES"/>&lt;!&ndash; "添加到主屏幕"后，全屏显示 &ndash;&gt;-->
    <meta name="apple-mobile-web-app-capable" content="yes"/>  <!-- 如果内容设置为YES，Web应用程序运行在全屏模式;否则，它不会。默认行为是使用Safari浏览器显示网页内容 -->
    <meta http-equiv="Cache-Control" content="no-cache"/>
    <meta http-equiv="Cache-Control" content="no-siteapp" /><!-- 度SiteApp转码声明 -->
    <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title><?php echo config::getConfig('site_title'); ?></title>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/bankPay.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/clipboard.min.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script>
    <!-- <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/public.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/ZeroClipboard.107.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.md5.js"></script> -->
    <script type="text/javascript">
        var cardList = JSON.parse('<?php echo json_encode($cards) ?>')
    </script>
    <style type="text/css">
        html{
            font-size: 20rem;
        }
        *{
            margin: 0;
            padding:0;
            box-sizing: border-box;
            list-style: none;
        }
        .balance{
            background: #F9F5F5;
            width: 100%;
            display: flex;
            height: 1.2rem;

            justify-content: space-around;
            align-items: center;
            margin: 0 auto;
        }
        .balance span{
            font-size: 0.3rem;
            font-weight:700;
        }
        .balance input{
            height: 0.75rem;
            line-height: 0.75rem;font-size: 0.5rem;
            width: 4rem;
        }
        .bank_info{
            width: 80%;
            border: 1px solid #6DA244;
            margin: 0 auto;
            font-size: 0.3rem;
            font-weight: 700;
            margin-top: 0.3rem;
            /*background: url("<?php echo $imgCdnUrl ?>/images/mobile/bank_area.png") no-repeat 4.8rem 0;*/
            background-size: 0.4rem 0.4rem;
        }
        .bank_info p:nth-child(1){
            height: 0.7rem;
            line-height:0.6rem;
            padding-left: 10px;
        }
        .bank_info p:nth-child(even){
            border-top: 1px dotted #6DA244;
            border-bottom: 1px dotted #6DA244;
            overflow: hidden;
            padding-top: 10px;
            padding-left: 20px;
            background: #F9F5F5;
        }
        .bank_info p:nth-child(odd){
            height: 1rem;
            line-height:0.5rem;
            padding-left: 10px;
        }
        .success_button{
            width: 94%;
            margin: 0 auto;
            height: 2.5rem;
            /*background-image: url("<?php echo $imgCdnUrl ?>/images/mobile/notice.png");*/
            background-repeat: no-repeat;
            background-size: 0.45rem 0.45rem;
            padding-top: 0.2rem;
        }
        .success_button p{
            font-size: 0.2rem;
            color: #E83B3E;
            font-weight: 700;
        }
        .bankPay_btn {
            width: 100%;
            height: 1rem;
            background:#E4393C;
            color: white;
            border: none;
            margin-top: 10px;
            border-radius: 10px;
            font-size: 0.35em;
        }
        .qq{
            width: 94%;
            height: 1rem;
            margin: 0 auto;
            font-size: 0.2rem;
        }
        .qq a{
            color:#E4393C;
            font-weight: 700;
        }
        .bank_monew01{float:right;margin-right: 8px;    height: 0.6rem;
    line-height: 0.6rem;}
    input[type=button], input[type=submit], input[type=file], button {
    cursor: pointer;
    -webkit-appearance: none;
}

    </style>
    <script type="application/javascript">

        (function (doc, win) {
            var docEl = doc.documentElement,
                resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
                recalc = function () {
                    var clientWidth = docEl.clientWidth;
                    if (!clientWidth) return;
                    if(clientWidth>=640){
                        docEl.style.fontSize = '100px';
                    }else{
                        docEl.style.fontSize = 100 * (clientWidth / 640) + 'px';
                    }
                };
            if (!doc.addEventListener) return;
            win.addEventListener(resizeEvt, recalc, false);
            doc.addEventListener('DOMContentLoaded', recalc, false);
        })(document, window);
//        $(function () {
//            $('.bank_info p:nth-child(1)').click(function () {
//                $(this).siblings().toggle();
//            })
//        })
    </script>
</head>

<body>
<form action="<?php echo $result['shop_url']; ?>" name="bp" target="_blank" id="bp" method="post">
    <div class="balance">
        <span>存款额度</span>
        <input name="deposit_amount" type="text" placeholder="10-50000">
    </div>
    <div class="balance">
        <span>存款人姓名</span>
        <input name="remark" id="remark" type="text" placeholder="" onkeyup="chkRemark(this)">
    </div>
    <div class="balance" id="user_pay_id_tr">
        <span>支付账号</span>
        <input name="pay_account_id" id="pay_account_id"  type="text" placeholder="">
    </div>
    <div class="bank_info" id="div">

        <?php if(empty($cards)):?>
        <p>收款银行： 暂无收款卡</p>
       <?php else:?>
        <p>收款卡：<select style="height: 0.8rem;" onchange="choiceBack(this)">
                <?php $arrTmp=array_values($cards);  $j=1;foreach ($arrTmp as $id=>$v):?>
                       <?php
                    if(isset($arrTmp[$id]['bank_id'])) {
                        if ($id > 0 && ($arrTmp[$id]['bank_id'] != $arrTmp[$id - 1]['bank_id'])) {
                            $j = 1;
                        }
                    }
                       ?>
                    <option style="width: 50%;" data-bank_id="<?php echo $v['bank_id']?>" data-card_id="<?php echo $v['card_id']?>" data-shop_url="<?php echo $v['shop_url']?>" ><?php $bank = isset($bankList[$v['bank_id']])?$bankList[$v['bank_id']]:'未知';echo $bank.'-卡'.$j;?></option>
                <?php ++$j;endforeach;?>

            </select>
                </p>
        <?php $j=0;foreach ($cards as $id=>$v):?>
                <?php if($j==0):?>
        <p id="cont<?php echo $id;?>">
                    <?php else:?>
                    <p id="cont<?php echo $id;?>" style="display: none;">
                <?php endif;?>
            <span>收款人：<?php echo $v['card_name']; ?></span><br>
            <span>卡号：<?php echo $v['card_num'] ?></span>
            <?php if (!empty($v['subbranch'])): ?>
                <span>支行：<?php echo $v['subbranch'] ?></span>
            <?php endif; ?>
        </p>
        <?php if($j==0):?>
        <p id="cont2<?php echo $id;?>">附言：<?php echo $v['postscript'] ?></p>
        <?php else:?>
                    <p id="cont2<?php echo $id;?>" style="display: none">附言：<?php echo $v['postscript'] ?></p>
            <?php endif;?>
            <?php ++$j;endforeach;?>
        <?php endif;?>
    </div>
<!--    <button style="color: blue;font-size: 20px;margin-left: 40px;display: block;border: none" type="button" data-clipboard-action="copy" class="copy" data-clipboard-target="--><?php //echo '#div';?><!--">一键复制</button>-->

    <div class="success_button">
        <p>支付成功后，</p>
        <p>请您凭会员账号，交易单号提交至客服QQ或者客服微信</p>
        <input name="sub" type="button" value="确认充值" class="bankPay_btn" />
        <input name="th_ts" type="hidden" value="">
        <input name="bank_id" type="hidden" value="<?php echo reset($cards)['bank_id'] ?>">
        <input name="card_id" type="hidden" value="<?php echo reset($cards)['card_id'] ?>">
<!--        //>>添加跳转地址-->
        <input name="shop_url" type="hidden" value="<?php echo reset($cards)['shop_url'] ?>">
    </div>
    <div class="qq" style="color: red;">
        <p>温馨提示：</p>
<!--        <p>银行卡入款享受2%存款优惠</p>-->
        <p>若多次充值未成功，请点击<a style="color:#157fd0;" href="<?php echo getFloatConfig('service_url'); ?>" target="_blank">在线客服</a>索取二维码</p>
    </div>
</form>
<script>
    function chkRemark(obj) {
        var vo=$(obj).val();
        if(vo.length >200)
        {
            $(obj).val(vo.substring(0,200));
        }
    }

    function choiceBack(obj) {
        var card_id = $(obj).find('option:selected').data('card_id');
        var bank_id = $(obj).find('option:selected').data('bank_id');
        //>>添加跳转地址
        var shop_url = $(obj).find('option:selected').data('shop_url');
        $("p[id^='cont']").each(function () {
            if($(this).css('display') == 'block')
            {
                $(this).css('display','none')
            }
        })

        $('#cont'+card_id).css('display','block');
        $('#cont2'+card_id).css('display','block');
        $("input[name='bank_id']").val(bank_id);
        $("input[name='card_id']").val(card_id);
        //>>添加跳转地址
        $("input[name='shop_url']").val(shop_url);

        /****************** snow 添加是否显示支付账号************************************/
        var card = null;
        $.each(cardList,function(key, value){
            if(value.card_id == card_id){
                card = value;
            }
        });
//console.log(card)
        //>>如果不是,隐藏并且赋值为空
        $('#user_pay_id_tr').hide();
        $('#user_pay_id_tr').find('input').prop({'disabled' : true});
        $('#user_pay_id_tr').find('input').val('');
        if(card !== null){
            if(card.pay_id_input === 1){
                //>>显示支付账号
                $('#user_pay_id_tr').show();
                $('#user_pay_id_tr').find('input').prop({'disabled' : false});
            }
        }



        /****************** snow 添加是否显示支付账号************************************/
    }
    $(function () {
        /****************** snow 添加是否显示支付账号************************************/
        var card = null;
        var card_id = $('input[name=card_id]').val();
        $.each(cardList,function(key, value){
            if(value.card_id == card_id){
                card = value;
            }
        });

//        console.log(card)
        //>.如果不是,隐藏并且赋值为空
        $('#user_pay_id_tr').hide();
        $('#user_pay_id_tr').find('input').prop({'disabled' : true});
        $('#user_pay_id_tr').find('input').val('');
        if(card !== null){
            if(card.pay_id_input === 1){
                //>>显示支付账号
                $('#user_pay_id_tr').show();
                $('#user_pay_id_tr').find('input').prop({'disabled' : false});
            }
        }



        /****************** snow 添加是否显示支付账号************************************/


        var clipboard = new Clipboard('.copy');
        clipboard.on('success', function(e) {
            layer.msg('复制成功!',{icon:1,time:1000})
        });
        clipboard.on('error', function(e) {
            layer.msg('暂时不能复制',{icon:2,time:1000})
        });
    })
</script>
</body>
</html>
