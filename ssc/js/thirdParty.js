$(function(){
    /*********************  snow 修正  ,js 遍历对象错误  添加支付账号及 根据后台配置取最大最小值功能 *****************************************/
    $('#user_pay_id_tr').hide();
    $('#user_pay_id_tr').find('input').prop({'disabled' : true});
    $('#user_pay_id_tr').find('input').val('');

    //>> snow 验证支付昵称  长度不能超过200
    $('#remark').keyup(function(){
        var vo=$(this).val();
        if(vo.length >200)
        {
            $(this).val(vo.substring(0,200));
        }
    });

    //>> snow 验证支付账号  长度不能超过60
    $("input[name=pay_account_id]").keyup(function(){
        var vo=$(this).val();
        if(vo.length >60)
        {
            $(this).val(vo.substring(0,60));
        }
    });

    var   card = null;
    $('.third_party_btn').click(function () {
        //>>隐藏支付账号输入框及禁用input框
        $('#user_pay_id_tr').hide();
        $('#user_pay_id_tr').find('input').prop({'disabled' : true})
        $('#user_pay_id_tr').find('input').val('');
        var id = $(this).attr('id');
        card = cardList[id];
        $(this).attr('class','thirdPartyCurrentNow').siblings().attr('class','third_party_btn');
        $('#card_remark').text(card.remark);
        $('#bp').attr('action', card.shop_url );
        $("input[name='card_id']").val(card.card_id );
        $("input[name='bank_id']").val(card.bank_id );
        $("input[name='codes']").val(card.codes );
        $("input[name='requestURI']").val(card.requestURI );
        $("input[name='call_back_url']").val(card.call_back_url );
        $("input[name='shop_order_num']").val(card.shop_order_num);
        $("input[name='th_ts']").val(card.th_ts );
        $("input[name='netway']").val(card.netway );
		    $("input[name='is_newpay']").val(card.is_newpay);//TODO: it's webs place
        var netway = '' + card.netway;
        if (netway.indexOf('WY') > -1) {
            $("tr[id='third_party_bank']").show();
        }else{
            $("tr[id='third_party_bank']").hide();
        }

        /****************** snow 2017-10-04 添加根据卡字段 pay_id_input 判断是否需要输入支付账号**********************/

        if(card !== null && card.pay_id_input === 1){
            //>>需要填写支付账号
            $('#user_pay_id_tr').show();
            $('#user_pay_id_tr').find('input').prop({'disabled' : false});

        }

        /****************** snow 2017-10-04 添加根据卡字段 pay_id_input 判断是否需要输入支付账号**********************/
        $('.third_party_next_btn').css('display','block');
    });

    $('.third_party_btn').eq(0).click();

    $(".third_party_next_btn").click(function(e) {
        thridPartyPay();
    });

    $("input[name=deposit_amount]").bind('keypress', function(event) {
        if (event.keyCode === 13) {
            thridPartyPay();
            return false;
        }
    });

    function thridPartyPay() {
        var deposit_amount = $("input[name=deposit_amount]").val();
        /*********************** 修改提示金额值**********************************/
        var pay_max_input   = max_deposit_limit ? max_deposit_limit : 5000;
        var pay_small_input = min_deposit_limit ? min_deposit_limit : 1;
        var pay_account_id = $("input[name=pay_account_id]").val();

        if(card !== null)
        {
            //>>说明有数据
            pay_max_input = card.pay_max_input === undefined || card.pay_max_input == 0 ? pay_max_input : card.pay_max_input;
            pay_small_input = card.pay_small_input   === undefined || card.pay_small_input   == 0 ? pay_small_input : card.pay_small_input;
            if(card.pay_id_input == 1 && pay_account_id == '')
            {
                parent.layer.msg('请输入支付账号名称',{icon:7});
                return false;
            }
        }
        if (deposit_amount === ""  || isNaN(deposit_amount) || parseFloat(deposit_amount) < pay_small_input  || parseFloat(deposit_amount) > pay_max_input  ) {
            top.layer.alert("请输入正确的金额:<br>不能低于 " + pay_small_input + "不能高于"+ pay_max_input +" 的任意数值<br>(若为小数,请保留二位数)！", {icon: 7});
            return false;
        }
        /*********************** 修改提示金额值**********************************/
        var old_ts = $("input[name=th_ts]").val();
        var now_ts = Date.parse(new Date());

        if(now_ts - old_ts >= 5000){
            // 默认5秒钟
            $("input[name=th_ts]").val(now_ts);
        } else {
            parent.layer.msg('请耐心等待五秒后继续充值', {icon: 7});
            return false;
        }
        var re=$('#remark').val();
        if(re == '')
        {
            parent.layer.msg('请输入扫码昵称或者账号', {icon: 7});
            return false;
        }
		parent.layer.confirm('<div title="银行转账">您的充值金额为：' + $("input[name=deposit_amount]").val() +'&nbsp;&nbsp;<br>请尽快完成充值，如发生异常请及时联系客服</div>', {
			icon: 7,
            title:'请妥善保存订单号',
			btn: '确定', //按钮
		}, function(other) {
            parent.layer.close(other);
            if ($("input[name=is_newpay]").val() === '1') {//TODO: it's ajax
                $.ajax({
                    url: "?c=newPay&a=fin",
                    type: "POST",
                    data: {
                        "op": "autoAddCase",
                        "deposit_amount": deposit_amount,
                        "card_id": $("input[name=card_id]").val(),
                        "bank_id": $("input[name=bank_id]").val(),
                        'remark': re,
                        "pay_account_id": pay_account_id
                    },
                    cache: false,
                    dataType: "json",
                    timeout: 30000,
                    success: function (response) {
                        if (response.errno === 0) {
                            $("input[name=shop_order_num]").val(response.errstr);
                            $("input[name=deposit_amount]").val(response.deposit_amount);
                            parent.layer.confirm('<div title="在线充值确认"><br>订单号:' + response.local_order_num + '</div></br>请妥善保管您的订单号', {
                                icon: 7,
                                title: '请妥善保存订单号',
                                closeBtn: 0,
                                btnAlign: 'c'
                            }, function (index) {
                                parent.layer.close(index);
                                $("#bp").submit();
                            // }, function (index1) {
                            //     $.ajax({
                            //         url: "?c=newPay&a=pay",
                            //         type: "POST",
                            //         data: {
                            //             "op": "delCase",
                            //             "deposit_id": response.deposit_id,
                            //         },
                            //         cache: false,
                            //         dataType: "json",
                            //         timeout: 30000,
                            //         success: function (res) {
                            //             return true;
                            //         }
                            //     })
                            });
                        } else {
                            parent.layer.confirm(response.errstr, {icon: 7});
                            return false;
                        }
                    }
                    // url: "?c=newPay&a=pay",
                    // type: "POST",
                    // data: {
                    //     "deposit_amount":deposit_amount,
                    //     "card_id":$("input[name=card_id]").val(),
                    //     "bank_id":$("input[name=bank_id]").val(),
                    //     "is_newpay":$("input[name=is_newpay]").val(),
                    //     "requestURI":$("input[name=requestURI]").val(),
                    // },
                    // cache: false,
                    // timeout: 30000,

                })
            }else {
            $.ajax({
                url: "?c=fin&a=deposit",
                type: "POST",
                data: {
                    "op": "autoAddCase",
                    "deposit_amount": deposit_amount,
                    "card_id": $("input[name=card_id]").val(),
                    "bank_id": $("input[name=bank_id]").val(),
                    'remark': re,
                    /************* 添加是否有支付账号************************/
                    "pay_account_id": pay_account_id
                    /************* 添加支付账号******************************/
                },
                cache: false,
                dataType: "json",
                timeout: 30000,
                success: function (response) {
                    if (response.errno === 0) {
                        $("input[name=shop_order_num]").val(response.errstr);
                        $("input[name=deposit_amount]").val(response.deposit_amount);
                        parent.layer.confirm('<div title="在线充值确认"><br>订单号:' + response.local_order_num + '</div></br>请妥善保管您的订单号', {
                            icon: 7,
                            title: '请妥善保存订单号',
                            closeBtn: 0,
                            btnAlign: 'c'
                        }, function (index) {
                            parent.layer.close(index);
                            $("#bp").submit();
                        // }, function (index1) {
                        //     $.ajax({
                        //         url: "?c=fin&a=deposit",
                        //         type: "POST",
                        //         data: {
                        //             "op": "delCase",
                        //             "deposit_id": response.deposit_id,
                        //         },
                        //         cache: false,
                        //         dataType: "json",
                        //         timeout: 30000,
                        //         success: function (res) {
                        //             return true;
                        //         }
                        //     })
                        });
                    } else {
                        parent.layer.confirm(response.errstr, {icon: 7});
                        return false;
                    }
                }
            })
        }
		})
    }

    $('.bankPay_btn').click(function(){

        var deposit_amount = $("input[name=deposit_amount]").val();
        if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 9999 || deposit_amount.lastIndexOf(".") >= 0  ) {
            top.layer.alert("请输入正确的金额，不能低于10，不能高于9999的任意数值(若为小数,请保留二位数)！",{icon:7});
            return false;
        }

        var old_ts = $("input[name=th_ts]").val();
        var now_ts = Date.parse(new Date());
        if(now_ts - old_ts >= 5000){//默认5秒钟
            $("input[name=th_ts]").val(now_ts);
        } else {
            parent.layer.msg('请耐心等待五秒后继续充值',{icon:7});
            return false;
        }

        $.ajax({
            url: "?c=fin&a=deposit",
            type: "POST",
            data: {
                "op": "autoAddCase",
                "deposit_amount":deposit_amount,
                "card_id":$("input[name=card_id]").val(),
                "bank_id":$("input[name=bank_id]").val(),
                "flag":"BP",
                /************* 添加支付账号******************************/
                "pay_account_id" : $("input[name=pay_account_id]").val()
                /************* 添加支付账号******************************/
            },
            cache: false,
            dataType: "json",
            timeout: 30000,
            success: function(response) {
                if (response.errno == 0) {
                    parent.layer.confirm('<div title="银行转账">转账金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                        icon: 7,
                        title:'请妥善保存订单号',
                        closeBtn:0,
                        btnAlign:'c'
                    },function(index){
                        parent.layer.close(index);
                        $("#bp").submit();
                    },function(index1){
                        $.ajax({
                            url: "?c=fin&a=deposit",
                            type: "POST",
                            data: {
                                "op": "delCase",
                                "deposit_id":response.deposit_id
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
    })
})
