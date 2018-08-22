$(document).ready(function() {
    $("button[name=bank_card]").eq(0).click();
});

$(function() {
    var receipts_item = $('.receipts_item');

    receipts_item.click(function () {
        $('.receipts_item_bg').css('display','none');
		var index = $(this).index();
		$(this).attr('class','receipts_item_current').siblings().attr('class','receipts_item');
		$('.Receipts_body_table').eq(index).show().siblings('.Receipts_body_table').hide();
        $(this).children('.receipts_item_bg').css('display','block');
	});

    receipts_item.eq(0).click();

    $("input[name=deposit_amount]").bind('keypress', function(event) {
        if (event.keyCode === 13) {
            bankPay();
            return false;
        }
    });

	$('.bankPay_btn').click(function() {
        bankPay();
    });

    $('#remark').keyup(function() {
        var vo = $(this).val();

        if(vo.length > 200) {
            $(this).val(vo.substring(0, 200));
        }
    });

    $("input[name=pay_account_id]").keyup(function() {
        var vo = $(this).val();

        if (vo.length > 60) {
            $(this).val(vo.substring(0, 60));
        }
    });

    function bankPay() {
        var card_id = $('input[name=card_id]').val();
        var bank_id = $('input[name=bank_id]').val();
        var deposit_amount = 0;
        var deposit_name = '';

        if (bank_id < 98) {
            deposit_amount = $('input[name=bank_deposit_amount]').val();
            deposit_name = $('input[name=bank_deposit_name]').val();
        } else if (bank_id == 98) {
            deposit_amount = $('input[name=wechat_deposit_amount]').val();
            deposit_name = $('input[name=wechat_deposit_name]').val();
        } else if (bank_id == 99) {
            deposit_amount = $('input[name=alipay_deposit_amount]').val();
            deposit_name = $('input[name=alipay_deposit_name]').val();
        }

        var pay_max_input = 500000;
        var pay_small_input = 100;
        var card = null;

        $.each(cardList,function(key, value) {
            if (value.card_id == card_id) {
                card = value;
            }
        });

        var pay_account_id = $("input[name=pay_account_id]").val();

        $("input[name=pay_account_id]").keyup(function() {
            var vo = $(this).val();

            if(vo.length > 60) {
                $(this).val(vo.substring(0, 60));
            }
        });

        if(card !== null) {
            // 说明有数据
            pay_max_input = card.pay_max_input === undefined || card.pay_max_input == 0 ? pay_max_input : card.pay_max_input;
            pay_small_input = card.pay_small_input === undefined || card.pay_small_input == 0 ? pay_small_input : card.pay_small_input;

            if (card.pay_id_input == 1 && pay_account_id == '') {
                parent.layer.msg('请输入支付账号名称', {icon:7});
                return false;
            }
        }

        if (deposit_amount === "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < pay_small_input || parseFloat(deposit_amount) > pay_max_input || deposit_amount.match(/^(([1-9][0-9]*)|(([0]\.\d{1,2}|[1-9][0-9]*\.\d{1,2})))$/) === null) {
            top.layer.alert("请输入正确的金额，不能低于"+ pay_small_input +"，不能高于"+ pay_max_input +"的任意数值(若为小数,请保留二位数)！", { icon:7 });
            return false;
        }

        var pattern = /^\s*[\u4E00-\u9FA5]{1,}\s*$/;

        if (deposit_name == '') {
            parent.layer.msg('请输入存款人姓名', { icon:7 });
            return false;
        }

        if (!pattern.test(deposit_name)) {
            parent.layer.msg('存款人姓名必须全为中文', { icon:7 });
            return false;
        }

        var old_ts = $("input[name=th_ts]").val();
        var now_ts = Date.parse(new Date());

        if(now_ts - old_ts >= 5000) {
            // 默认5秒钟
            $("input[name=th_ts]").val(now_ts);
        } else {
            parent.layer.msg('请耐心等待五秒后继续充值', { icon:7 });
            return false;
        }

        parent.layer.confirm('<div title="银行转账">您的充值金额为：' + deposit_amount +'&nbsp;&nbsp;<br>请尽快完成充值，如发生异常请及时联系客服</div>', {
			icon: 7,
            title:'请妥善保存订单号',
			btn: '确定',
		}, function(other) {
			parent.layer.close(other);
        $.ajax({
            url: "?c=fin&a=deposit",
            type: "POST",
            data: {
                "op": "autoAddCase",
                "deposit_amount": deposit_amount,
                "card_id": card_id,
                "bank_id": bank_id,
                "flag": "BP",
                'remark': deposit_name,
                "pay_account_id" : pay_account_id
            },
            cache: false,
            dataType: "json",
            timeout: 30000,
            success: function(response) {
                if (response.errno == 0) {
                    parent.layer.confirm('<div title="银行转账">您的充值订单号:<br>' + response.errstr+'&nbsp;&nbsp;<br>已经提交,请尽快完成充值以便人员审核</div>',{
                        icon: 7,
                        title:'请妥善保存订单号',
						btn:'充值',
                        closeBtn:0,
                        btnAlign:'c'
                    }, function(index) {
                        parent.layer.close(index);

                        if(card['shop_url'] !== '') {
                            window.open(card['shop_url']);
                        } else {

                        }
                    },function(index1) {
                        $.ajax({
                            url: "?c=fin&a=deposit",
                            type: "POST",
                            data: {
                                "op": "delCase",
                                "deposit_id": response.deposit_id,
                            },
                            cache: false,
                            dataType: "json",
                            timeout: 30000,
                            success: function(res) {
                                return true;
                            }
                        })
                    });
                } else {
                    alert(response.errstr);
                    return false;
                }
            }
        })
	}, function(){
			layer.msg('也可以这样', {
			time: 20000, //20s后自动关闭
			btn: ['明白了', '知道了']
			});
		});
    }
});
