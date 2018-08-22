var bankLinks = {
                1: "https://mybank.icbc.com.cn/icbc/perbank/index.jsp",
                2: "http://www.abchina.com/cn/EBanking/Ebanklogin/PCustomerLogin/default.htm",
                3: "https://ibsbjstar.ccb.com.cn/app/V5/CN/STY1/login.jsp",
                4: "https://pbsz.ebank.cmbchina.com/CmbBank_GenShell/UI/GenShellPC/Login/Login.aspx",
                5: "https://pbank.95559.com.cn/personbank/common_logon.jsp",
                6: "https://e.bank.ecitic.com/perbank5/signIn.do",
                7: "https://pbank.psbc.com/pweb/prelogin.do?_locale=zh_CN&BankId=9999",
                8: "https://www.cebbank.com/per/prePerlogin.do?ident=gr&_locale=zh_CN",
                9: "https://ebank.cmbc.com.cn/index_NonPrivate.html",
                10: "https://ebank.spdb.com.cn/per/gb/otplogin.jsp",
                11: "https://personalbank.cib.com.cn/pers/main/login.do",
                12: "https://ebanks.cgbchina.com.cn/perbank/",
                13: "https://www.pingan.com.cn/pinganone/pa/directToMenu.screen?directToMenu=bank_index_index",
                15: "https://sbank.hxb.com.cn/easybanking/jsp/login/login.jsp",
                16: "https://ebank.dongguanbank.cn:888/perbank/commons/login.jsp",
                17: "https://ebank.cbhb.com.cn/webappservice/pub/preperlogincert.html",
                18: "https://ebank-public.hzbank.com.cn/perbank/logon.jsp",
                19: "https://e.czbank.com/PERBANK/pbcspCheck.jsp?type=0",
                20: "https://ebank.bankofbeijing.com.cn/bccbpb/accountLogon.jsp?language=zh_CN",
                21: "https://ebank.gzcb.com.cn/perbank/logon_pro.jsp",
                22: "https://ebsnew.boc.cn/boc15/login.html"
            };
            function showCard() {
                $('.othersDIV').hide();
                //固定
                $('#bank_4').css('display', 'block');
                //显示网银链接 $('select[name=otherBankId]').find('option:selected').text() +
                $('#bankName').empty();
                if (parseInt($('select[name=otherBankId]').val()) < 100) {
                    $('#bankName').html('［<a target="_blank" href="' + bankLinks[parseInt($('select[name=otherBankId]').val())] + '">点击进入</a>］');
                }
            }
            function checkform(ob) {
                msg = '';
                if (ob.player_card_name.value == "") { // || !/^[\u4E00-\u9FA50]{2,4}$/.test(ob.player_card_name.value)
                    msg = "请输入正确的银行户名";
                }
                else if (ob.deposit_amount.value == "" || !/^[1-9]\d{1,}(\d+)?(\.\d{1,2})?$/.test(ob.deposit_amount.value) || ob.deposit_amount.value < 10) {
                    msg = "请输入正确的金额，不能低于10，不超过2位小数";
                }
                else if ($('select[name=otherBankId]').val() > "1") {
                    if (ob.deposit_time.value == "" || !/^201[2-9]\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}$/.test(ob.deposit_time.value)) {
                        msg = "请输入正确的汇款时间";
                    }
                    else if (ob.deposit_trade_type.value == "0") {
                        msg = "请选择交易类型";
                    }
                    else if ($('input[name=order_num]').val() == "" && ob.otherBankId.value > "100") {
                        msg = "请输入交易流水号";
                    }
                }
                if (msg != "") {
                    alert(msg + "！",{icon:7});
                    return false;
                }

                if ($(ob).attr('name') != 'alipay' && $(ob).attr('name') != 'tenpay') {
                    ob.otherBankId.value = $('#otherBankId').val();
                }
                //alert(ob.otherBankId.value);
                ob.submit.disabled = "true";
                return true;
            }
            $(function() {
                parent.layer.alert('<font color=red>如果多次充值不上,请保存截图及时联系客服</font>,平台充值卡号会不定期更换，望大家用最新帐号进行存款，切勿保存收款信息直接存款，由此造成的损失，平台不负任何责任！', 9);
                $('input[name=selectMethod]').change(function() {
                    if ($('input[name=selectMethod]:checked').val() == 'icbcDeposit') {
                        $('#otherOption').hide();
                        $('.othersDIV').hide();

                        $.post(
                                "index.jsp?c=fin&a=showAccount&bankId=1&rndStr=" + Math.random(),
                                function(result) {
                                    eval("data=" + result + ";");
                                    //$('#balance').html(data.balance);
                                    if (data.errno != '0') {
                                        if (data.errno == 1) {
                                            top.layer.alert("系统暂无收款卡，请选择其他方式充值。");
                                        }
                                        else {
                                            top.layer.alert("系统出错：" + data.errstr);
                                        }
                                    }
                                    else {
                                        $('input[name=card_bank_id]').val(data.bank_id);
                                        $('#div_email').text(data.card_num);
                                        $('#div_bank').text(data.bank_name);
                                        $('#div_name').text(data.card_name);
                                        $('#div_postscript').text(data.postscript);
                                        //$('#icbcAuto').css('display', 'block');
                                        $('#icbcAuto').slideDown('fast');
                                        $('.nextBtn').append('<a class="bankBtn" style="margin:14px 0;" target="_blank" href="' + bankLinks[parseInt(data.bank_id)] + '">下一步</a>');
                                    }
                                });
                    }
                    else {
                        //$('#otherOption').slideDown('fast');
                        //$('#icbcAuto').hide();
                        //固定招行
                        //$('select[name=otherBankId]').val('4').change();
                    }
                });

                //大tab切换
                $('.rechargeTab').click(function() {
                    $('.rechargeTab').addClass('rechargeTab2').removeClass('rechargeTab');
                    $(this).addClass('rechargeTab').removeClass('rechargeTab2');
                    /\w+_(\d+)/.test($(this).attr('id'));
                    var index = RegExp.$1;
                    $(".rechargeContent").hide();
                    $('#content_' + index).show();   //取消选中 .find('input[type=radio]').attr('checked', false)
                });
                //默认点中
                $('.recharge li:eq(1)').click();

                //=======================通用复制功能 开始=======================//
                var clip = null, curBtn = null;
                function move_swf(ee, txt)
                {
                    clip.setText(txt);
                    if (clip.div) {
                        clip.receiveEvent('mouseout', null);
                        clip.reposition(ee.id);
                    }
                    else {
                        clip.glue(ee.id);
                    }
                    clip.receiveEvent('mouseover', null);
                }

                ZeroClipboard.setMoviePath('js/ZeroClipboard.swf');
                clip = new ZeroClipboard.Client();
                clip.setHandCursor(false);

                clip.addEventListener("mouseUp", function(client) {
                    $('.copyBtn').text('点击复制');
                    $(curBtn).text('已复制');
                    //clip.setText(copy); // 设置要复制的文本。
                });


                $('#copyBankBtn').mouseover(function() {
                    move_swf(this, $('#div_bank').text());
                    curBtn = this;
                });
                $('#copyNameBtn').mouseover(function() {
                    move_swf(this, $('#div_name').text());
                    curBtn = this;
                });
                $('#copyEmailBtn').mouseover(function() {
                    move_swf(this, $('#div_email').text());
                    curBtn = this;
                });
                $('#copyPostscriptBtn').mouseover(function() {
                    move_swf(this, $('#div_postscript').text());
                    curBtn = this;
                });
                //=======================通用复制功能 结束=======================//

                //=======================仁信微信提交检测=======================//
                $("#rxwxPay input[name=sub]").click(function(e) {
                    rxwxPay();
                });

                $("#rxwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        rxwxPay();
                        return false;
                    }
                });

                function rxwxPay() {
                    // if (!$('#wechatThPay input[name=bankCode]:checked').length) {
                    //     alert('请选择银行');
                    //     return false;
                    // }

                    var deposit_amount = $("#rxwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 9999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于9999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#rxwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#rxwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#rxwxPay input[name=card_id]").val(),
                            "bank_id":$("#rxwxPay input[name=bank_id]").val(),
                            "flag":"RXWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno == 0) {
                                $("#rxwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#rxwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
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
                }

                //=======================仁信支付宝提交检测=======================//
                $("#rxzfbPay input[name=sub]").click(function(e) {
                    rxzfbPay();
                });

                $("#rxzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        rxzfbPay();
                        return false;
                    }
                });

                function rxzfbPay() {
                    // if (!$('#wechatThPay input[name=bankCode]:checked').length) {
                    //     alert('请选择银行');
                    //     return false;
                    // }

                    var deposit_amount = $("#rxzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 9999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于9999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#rxzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#rxzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#rxzfbPay input[name=card_id]").val(),
                            "bank_id":$("#rxzfbPay input[name=bank_id]").val(),
                            "flag":"RXZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno == 0) {
                                $("#rxzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#rxzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
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
                }

                //=======================百付支付宝提交检测=======================//
                $("#bfzfbPay input[name=sub]").click(function(e) {
                    bfzfbPay();
                });

                $("#bfzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        bfzfbPay();
                        return false;
                    }
                });

                function bfzfbPay() {
                    // if (!$('#wechatThPay input[name=bankCode]:checked').length) {
                    //     alert('请选择银行');
                    //     return false;
                    // }

                    var deposit_amount = $("#bfzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 2999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于2999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#bfzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#bfzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#bfzfbPay input[name=card_id]").val(),
                            "bank_id":$("#bfzfbPay input[name=bank_id]").val(),
                            "flag":"BFZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno == 0) {
                                $("#bfzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#bfzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
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
                }

                //=======================百付微信提交检测=======================//
                $("#bfwxPay input[name=sub]").click(function(e) {
                    bfwxPay();
                });

                $("#bfwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        bfwxPay();
                        return false;
                    }
                });

                function bfwxPay() {
                    // if (!$('#wechatThPay input[name=bankCode]:checked').length) {
                    //     alert('请选择银行');
                    //     return false;
                    // }

                    var deposit_amount = $("#bfwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 9999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于9999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#bfwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#bfwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#bfwxPay input[name=card_id]").val(),
                            "bank_id":$("#bfwxPay input[name=bank_id]").val(),
                            "flag":"BFWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno == 0) {
                                $("#bfwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#bfwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
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
                }

                //=======================付乾微信提交检测=======================//
                $("#fqwxPay input[name=sub]").click(function(e) {
                    fqwxPay();
                });

                $("#fqwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        fqwxPay();
                        return false;
                    }
                });

                function fqwxPay() {
                    // if (!$('#wechatThPay input[name=bankCode]:checked').length) {
                    //     alert('请选择银行');
                    //     return false;
                    // }

                    var deposit_amount = $("#fqwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 9999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于9999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#fqwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#fqwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#fqwxPay input[name=card_id]").val(),
                            "bank_id":$("#fqwxPay input[name=bank_id]").val(),
                            "flag":"FQWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#fqwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#fqwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================付乾网银提交检测=======================//
                $("#fqwyPay input[name=sub]").click(function(e) {
                    fqwyPay();
                });

                $("#fqwyPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        fqwyPay();
                        return false;
                    }
                });

                function fqwyPay() {
                    // if (!$('#wechatThPay input[name=bankCode]:checked').length) {
                    //     alert('请选择银行');
                    //     return false;
                    // }

                    var deposit_amount = $("#fqwyPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 50000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于50000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#fqwyPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#fqwyPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#fqwyPay input[name=card_id]").val(),
                            "bank_id":$("#fqwyPay input[name=bank_id]").val(),
                            "flag":"FQWY",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#fqwyPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">网银充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#fqwyPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================付乾微信提交检测=======================//
                $("#fqqqPay input[name=sub]").click(function(e) {
                    fqqqPay();
                });

                $("#fqqqPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        fqqqPay();
                        return false;
                    }
                });

                function fqqqPay() {
                    // if (!$('#wechatThPay input[name=bankCode]:checked').length) {
                    //     alert('请选择银行');
                    //     return false;
                    // }

                    var deposit_amount = $("#fqqqPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 9999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于9999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#fqqqPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#fqqqPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#fqqqPay input[name=card_id]").val(),
                            "bank_id":$("#fqqqPay input[name=bank_id]").val(),
                            "flag":"FQQQ",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#fqqqPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">QQ钱包充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#fqqqPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================顺宝支付宝提交检测=======================//
                $("#sbzfbPay input[name=sub]").click(function(e) {
                    sbzfbPay();
                });

                $("#sbzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        sbzfbPay();
                        return false;
                    }
                });

                function sbzfbPay() {
                    var deposit_amount = $("#sbzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 2999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于4999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#sbzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#sbzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#sbzfbPay input[name=card_id]").val(),
                            "bank_id":$("#sbzfbPay input[name=bank_id]").val(),
                            "flag":"SBZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#sbzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#sbzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================顺宝微信提交检测=======================//
                $("#sbwxPay input[name=sub]").click(function(e) {
                    sbwxPay();
                });

                $("#sbwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        sbwxPay();
                        return false;
                    }
                });

                function sbwxPay() {
                    var deposit_amount = $("#sbwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 9999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于4999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#sbwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#sbwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#sbwxPay input[name=card_id]").val(),
                            "bank_id":$("#sbwxPay input[name=bank_id]").val(),
                            "flag":"SBWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#sbwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#sbwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================幸运支付提交检测=======================//
                $("#xyPay input[name=sub]").click(function(e) {
                    xyPay();
                });

                $("#xyPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        xyPay();
                        return false;
                    }
                });

                function xyPay() {
                    var deposit_amount = $("#xyPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 49999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于49999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#xyPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#xyPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#xyPay input[name=card_id]").val(),
                            "bank_id":$("#xyPay input[name=bank_id]").val(),
                            "flag":"XY",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#xyPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#xyPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================熊猫支付提交检测=======================//
                $("#xmPay input[name=sub]").click(function(e) {
                    xmPay();
                });

                $("#xmPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        xmPay();
                        return false;
                    }
                });

                function xmPay() {
                    var deposit_amount = $("#xmPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 50000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于50000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#xmPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#xmPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#xmPay input[name=card_id]").val(),
                            "bank_id":$("#xmPay input[name=bank_id]").val(),
                            "flag":"XMZF",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#xmPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#xmPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================银宝支付宝提交检测=======================//
                $("#ybzfbPay input[name=sub]").click(function(e) {
                    ybzfbPay();
                });

                $("#ybzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        ybzfbPay();
                        return false;
                    }
                });

                function ybzfbPay() {
                    var deposit_amount = $("#ybzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 5000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于5000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#ybzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#ybzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#ybzfbPay input[name=card_id]").val(),
                            "bank_id":$("#ybzfbPay input[name=bank_id]").val(),
                            "flag":"YBZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#ybzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#ybzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================银宝微信提交检测=======================//
                $("#ybwxPay input[name=sub]").click(function(e) {
                    ybwxPay();
                });

                $("#ybwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        ybwxPay();
                        return false;
                    }
                });

                function ybwxPay() {
                    var deposit_amount = $("#ybwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 5000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于5000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#ybwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#ybwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#ybwxPay input[name=card_id]").val(),
                            "bank_id":$("#ybwxPay input[name=bank_id]").val(),
                            "flag":"YBWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#ybwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#ybwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================好付支付宝提交检测=======================//
                $("#hfzfbPay input[name=sub]").click(function(e) {
                    hfzfbPay();
                });

                $("#hfzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        hfzfbPay();
                        return false;
                    }
                });

                function hfzfbPay() {
                    var deposit_amount = $("#hfzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 8000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于8000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#hfzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#hfzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#hfzfbPay input[name=card_id]").val(),
                            "bank_id":$("#hfzfbPay input[name=bank_id]").val(),
                            "flag":"HFZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#hfzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#hfzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================好付微信提交检测=======================//
                $("#hfwxPay input[name=sub]").click(function(e) {
                    hfwxPay();
                });

                $("#hfwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        hfwxPay();
                        return false;
                    }
                });

                function hfwxPay() {
                    var deposit_amount = $("#hfwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 8000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于8000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#hfwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#hfwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#hfwxPay input[name=card_id]").val(),
                            "bank_id":$("#hfwxPay input[name=bank_id]").val(),
                            "flag":"HFWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#hfwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#hfwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================U付支付宝提交检测=======================//
                $("#ufzfbPay input[name=sub]").click(function(e) {
                    ufzfbPay();
                });

                $("#ufzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        ufzfbPay();
                        return false;
                    }
                });

                function ufzfbPay() {
                    var deposit_amount = $("#ufzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 50000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于50000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#ufzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#ufzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#ufzfbPay input[name=card_id]").val(),
                            "bank_id":$("#ufzfbPay input[name=bank_id]").val(),
                            "flag":"UFZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#ufzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#ufzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================U付微信提交检测=======================//
                $("#ufwxPay input[name=sub]").click(function(e) {
                    ufwxPay();
                });

                $("#ufwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        ufwxPay();
                        return false;
                    }
                });

                function ufwxPay() {
                    var deposit_amount = $("#ufwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 50000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于50000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#ufwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#ufwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#ufwxPay input[name=card_id]").val(),
                            "bank_id":$("#ufwxPay input[name=bank_id]").val(),
                            "flag":"UFWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#ufwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#ufwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================瞬付微信提交检测=======================//
                $("#sfwxPay input[name=sub]").click(function(e) {
                    sfwxPay();
                });

                $("#sfwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        sfwxPay();
                        return false;
                    }
                });

                function sfwxPay() {
                    var deposit_amount = $("#sfwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 3000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于3000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#sfwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#sfwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#sfwxPay input[name=card_id]").val(),
                            "bank_id":$("#sfwxPay input[name=bank_id]").val(),
                            "flag":"SFWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#sfwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#sfwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================瞬付支付宝提交检测=======================//
                $("#sfzfbPay input[name=sub]").click(function(e) {
                    sfzfbPay();
                });

                $("#sfzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        sfzfbPay();
                        return false;
                    }
                });

                function sfzfbPay() {
                    var deposit_amount = $("#sfzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 3000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于3000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#sfzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#sfzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#sfzfbPay input[name=card_id]").val(),
                            "bank_id":$("#sfzfbPay input[name=bank_id]").val(),
                            "flag":"SFZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#sfzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#sfzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================瞬付QQ提交检测=======================//
                $("#sfqqPay input[name=sub]").click(function(e) {
                    sfqqPay();
                });

                $("#sfqqPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        sfqqPay();
                        return false;
                    }
                });

                function sfqqPay() {
                    var deposit_amount = $("#sfqqPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 3000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于3000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#sfqqPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#sfqqPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#sfqqPay input[name=card_id]").val(),
                            "bank_id":$("#sfqqPay input[name=bank_id]").val(),
                            "flag":"SFQQ",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#sfqqPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">QQ充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#sfqqPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================高通支付宝提交检测=======================//
                $("#gtzfbPay input[name=sub]").click(function(e) {
                    gtzfbPay();
                });

                $("#gtzfbPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        gtzfbPay();
                        return false;
                    }
                });

                function gtzfbPay() {
                    var deposit_amount = $("#gtzfbPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 4999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于4999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#gtzfbPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#gtzfbPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#gtzfbPay input[name=card_id]").val(),
                            "bank_id":$("#gtzfbPay input[name=bank_id]").val(),
                            "flag":"GTZFB",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#gtzfbPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">支付宝充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#gtzfbPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================高通微信提交检测=======================//
                $("#gtwxPay input[name=sub]").click(function(e) {
                    gtwxPay();
                });

                $("#gtwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        gtwxPay();
                        return false;
                    }
                });

                function gtwxPay() {
                    var deposit_amount = $("#gtwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 4999 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于4999的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#gtwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#gtwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#gtwxPay input[name=card_id]").val(),
                            "bank_id":$("#gtwxPay input[name=bank_id]").val(),
                            "flag":"GTWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#gtwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#gtwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================高通微信提交检测=======================//
                $("#lbwxPay input[name=sub]").click(function(e) {
                    lbwxPay();
                });

                $("#lbwxPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        lbwxPay();
                        return false;
                    }
                });

                function lbwxPay() {
                    var deposit_amount = $("#lbwxPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 5000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于5000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#lbwxPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#lbwxPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#lbwxPay input[name=card_id]").val(),
                            "bank_id":$("#lbwxPay input[name=bank_id]").val(),
                            "flag":"LBWX",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#lbwxPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">微信充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#lbwxPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //=======================高通网银提交检测=======================//
                $("#lbwyPay input[name=sub]").click(function(e) {
                    lbwyPay();
                });

                $("#lbwyPay input[name=deposit_amount]").bind('keypress',function(event){
                    if(event.keyCode == "13") {
                        lbwxPay();
                        return false;
                    }
                });

                function lbwyPay() {
                    var deposit_amount = $("#lbwyPay input[name=deposit_amount]").val();
                    if (deposit_amount == "" || isNaN(deposit_amount) || parseFloat(deposit_amount) < 10 || parseFloat(deposit_amount) > 50000 || deposit_amount.lastIndexOf(".") >= 0) {
                        top.layer.alert("请输入正确的金额，不能低于10，不能高于50000的正整数！",{icon:7});
                        return false;
                    }

                    var old_ts = $("#lbwyPay input[name=th_ts]").val();
                    var now_ts = Date.parse(new Date());
                    if(now_ts - old_ts >= 5000){//默认5秒钟
                        $("#lbwyPay input[name=th_ts]").val(now_ts);
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
                            "card_id":$("#lbwyPay input[name=card_id]").val(),
                            "bank_id":$("#lbwyPay input[name=bank_id]").val(),
                            "flag":"LBWY",
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function(response) {
                            if (response.errno === 0) {
                                $("#lbwyPay input[name=shop_order_num]").val(response.errstr);
                                parent.layer.confirm('<div title="在线充值确认">网银充值金额：' + deposit_amount + '<br>订单号:'+response.errstr+'</div>',{
                                    icon: 7,
                                    title:'请妥善保存订单号',
                                    closeBtn:0,
                                    btnAlign:'c'
                                },function(index){
                                    parent.layer.close(index);
                                    $("#lbwyPay").submit();
                                },function(index1){
                                    $.ajax({
                                        url: "?c=fin&a=deposit",
                                        type: "POST",
                                        data: {
                                            "op": "delCase",
                                            "deposit_id":response.deposit_id,
                                        },
                                        cache: false,
                                        dataType: "json",
                                        timeout: 30000,
                                        success: function(res) {
                                            return true;
                                        }
                                    });
                                });
                            } else {
                                alert(response.errstr);
                                return false;
                            }
                        }
                    });
                }

                //银行选中状态
                $(".radioBtn").click(function(){
                    $(".radioBtn").find("label").children("b").remove();
                    if($(this).find("label").children("b").length === 0){
                        $(this).find("label").prepend("<b></b>");
                    }
                });
                //快捷通银行选中状态
                $(".BkradioBtn").click(function(){
                    $(".BkradioBtn").find("label").children("b").remove();
                    $("input[name=PayBkMethod]:checked").removeAttr('checked');
                    if($(this).find("label").children("b").length === 0){
                        $(this).find("input").attr("checked","checked");
                        $(this).find("label").prepend("<b></b>");
                    }
                });
            });
