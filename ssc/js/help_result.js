$(function () {
    // 加载完成显示汇总
    $('.selectBtn').eq(0).click();
});

function getLhcColor($num) {
    var Reds = ['1', '2', '7', '8', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46'];
    var Blues = ['3', '4', '9', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48'];
    var Greens = ['5', '6', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49'];

    if ($.inArray($num, Reds) !== -1) {
        return 'red2';
    } else if ($.inArray($num, Blues) !== -1) {
        return 'blue1';
    } else if ($.inArray($num, Greens) !== -1) {
        return 'green1';
    }
}

function getPksColor($num) {
    var pksColor = {
        '01': 'yellow1',
        '02': 'blue1',
        '03': 'gary1',
        '04': 'orange1',
        '05': 'blue2',
        '06': 'blue3',
        '07': 'gary2',
        '08': 'red2',
        '09': 'dark',
        '10': 'green1'
    };
    return pksColor[$num];
}

function getKlpkColor($num) {
    var suit = {
        99: '<i class="mh"></i>',
        100: '<i class="fk"></i>',
        104: '<i class="rt"></i>',
        115: '<i class="bt"></i>'
    };
    var it = $num.split('');
    if (it[0].toString().toUpperCase() === 'T') it[0] = '10';
    if (it[1] === 'h' || it[1] === 'd') {
        return [suit[it[1].charCodeAt()], it[0], 'red4'];
    } else {
        return [suit[it[1].charCodeAt()], it[0], ''];
    }
}

//数据加载的时候出现弹窗
function showLoading() {
    layer.load(2, {
        shade: [0.1, '#000'] //0.1透明度的白色背景
    });
    $(".layui-layer-loading").css({"width": "auto", "left": "50%"});
}

function hideLoading() {
    layer.closeAll();
}

var $lotteryData = {
    1: {'cname': '', 'lotteryTimes': '120期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    2: {'cname': '', 'lotteryTimes': '83期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    4: {'cname': '', 'lotteryTimes': '120期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    5: {'cname': '', 'lotteryTimes': '78期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    6: {'cname': '', 'lotteryTimes': '84期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    7: {'cname': '', 'lotteryTimes': '78期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    8: {'cname': '', 'lotteryTimes': '84期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    9: {'cname': '', 'lotteryTimes': '1期', 'openTimes': '每天一次', 'chart': '', 'fun': ''},
    10: {'cname': '', 'lotteryTimes': '1期', 'openTimes': '每天一次', 'chart': '', 'fun': ''},
    11: {'cname': '', 'lotteryTimes': '1380期', 'openTimes': '1分钟', 'chart': '', 'fun': ''},
    12: {'cname': '', 'lotteryTimes': '82期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    13: {'cname': '', 'lotteryTimes': '1380期', 'openTimes': '1分钟', 'chart': '', 'fun': ''},
    14: {'cname': '', 'lotteryTimes': '88期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    16: {'cname': '', 'lotteryTimes': '180期', 'openTimes': '1分钟', 'chart': '', 'fun': ''},
    17: {'cname': '', 'lotteryTimes': '179期', 'openTimes': '5分钟', 'chart': '', 'fun': ''},
    18: {'cname': '', 'lotteryTimes': '920期', 'openTimes': '1.5分钟', 'chart': '', 'fun': ''},
    19: {'cname': '', 'lotteryTimes': '80期', 'openTimes': '10分钟', 'chart': '', 'fun': ''},
    21: {'cname': '', 'lotteryTimes': '两天一期', 'openTimes': '两天一期', 'chart': '', 'fun': ''},
    22: {'cname': '', 'lotteryTimes': '周二,周四,周日', 'openTimes': '周二,周四,周日', 'chart': '', 'fun': ''},
    23: {'cname': '', 'lotteryTimes': '179期', 'openTimes': '5分钟', 'chart': '', 'fun': ''},
    24: {'cname': '', 'lotteryTimes': '120期', 'openTimes': '5分钟', 'chart': '', 'fun': ''},
    25: {'cname': '', 'lotteryTimes': '287期', 'openTimes': '5分钟', 'chart': '', 'fun': ''},
    26: {'cname': '', 'lotteryTimes': '180期', 'openTimes': '5分钟', 'chart': '', 'fun': ''}
};

$(".selectBtn").click(function () {
    var $clickLotteryId = parseInt($(this).data('id'));
    $(this).addClass('acti').siblings().removeClass('acti');

    $.ajax({
        url: '?a=openInfo',
        type: "GET",
        data: {lotteryId: $clickLotteryId},
        dataType: "JSON",
        beforeSend: function () {
            showLoading();
        },
        success: function ($data) {
            var codes = [];
            var li = '';
            var html = '';

            if ($clickLotteryId > 0) {

                $.each($data, function ($i, $item) {
                    var $lotteryId = $clickLotteryId;
                    var code = '';
                    if ($item.code) {
                        //对特殊奖号进行处理
                        if ($item.code.indexOf(' ') > -1) {
                            code = $item.code.split(' ');
                        } else {
                            code = $item.code.split('');
                        }

                        //香港六合彩
                        if ($lotteryId === 21 || $lotteryId === 25) {
                            $.each(code, function (k, it) {
                                var color = getLhcColor(it);
                                li = '<li class="' + color + '">' + it + '</li>';
                                code[k] = li;
                            });
                            //双色球
                        } else if ($lotteryId === 22) {
                            $.each(code, function (k, it) {
                                if (k === 6) {
                                    li = '<li class="blue1">' + it + '</li>'
                                } else {
                                    li = '<li>' + it + '</li>'
                                }
                                code[k] = li;
                            });
                            //北京pk
                        } else if ($lotteryId === 17 || $lotteryId === 26) {
                            $.each(code, function (k, it) {
                                var color = getPksColor(it);
                                li = '<li class="' + color + '">' + it + '</li>';
                                code[k] = li;
                            });
                            //山东快乐扑克
                        } else if ($lotteryId === 14) {
                            $.each(code, function (k, it) {
                                var color = getKlpkColor(it);
                                if (it[0].toString().toUpperCase() === 'T') it[0] = '10';
                                li = '<li class="klpk">' + color[0] + '<span class="' + color[2] + '">' + color[1] + '</span></li>';
                                code[k] = li;
                            });
                        }
                        else {
                            $.each(code, function (k, it) {
                                code[k] = '<li>' + it + '</li>';
                            });
                        }

                        codes[codes.length] = code;
                    }
                });
                html = template('template_head');
                $('.theLottery .lotteryUl').html(html);
                html = template('template_detail', {
                    list: $data,
                    codes: codes,
                    lotteryData: $lotteryData,
                    lotteryId: $clickLotteryId
                });
                $('.theLottery .lotteryUl').append(html);
                return true;
            }

            /* ------------ 下面是汇总的 ---------------- */
            $.each($data, function ($lotteryId, $item) {
                $lotteryId = +($lotteryId);

                // 当openInfo中多出现彩种时,可能并未上线,所以添加一个空的信息对象
                if (!$lotteryData[$lotteryId]) {
                    $lotteryData[$lotteryId] = {
                        'cname': '',
                        'lotteryTimes': '',
                        'openTimes': '',
                        'chart': '',
                        'fun': ''
                    };
                }
                $lotteryData[$lotteryId].cname = $item.lastIssueInfo.cname;
                // $lotteryData[$lotteryId].chart = '?c=game&a=chart&lottery_id='+$lotteryId;
                $lotteryData[$lotteryId].fun = $item.fun;

                if ($item.lastIssueInfo.code) {

                    //对特殊奖号进行处理
                    if ($item.lastIssueInfo.code.indexOf(' ') > -1) {
                        codes[$lotteryId] = $item.lastIssueInfo.code.split(' ');
                    } else {
                        codes[$lotteryId] = $item.lastIssueInfo.code.split('');
                    }

                    //香港六合彩
                    if ($lotteryId === 21) {
                        $.each(codes[$lotteryId], function (k, it) {
                            var color = getLhcColor(it);
                            li = '<li class="' + color + '">' + it + '</li>';
                            codes[$lotteryId][k] = li;
                        });
                        //双色球
                    } else if ($lotteryId === 22) {
                        $.each(codes[$lotteryId], function (k, it) {
                            if (k === 6) {
                                li = '<li class="blue1">' + it + '</li>';
                            } else {
                                li = '<li>' + it + '</li>';
                            }
                            codes[$lotteryId][k] = li;
                        });

                        //北京pk
                    } else if ($lotteryId === 17 || $lotteryId === 26) {
                        $.each(codes[$lotteryId], function (k, it) {
                            var color = getPksColor(it);
                            li = '<li class="' + color + '">' + it + '</li>';
                            codes[$lotteryId][k] = li;
                        });
                        //山东快乐扑克
                    } else if ($lotteryId === 14) {
                        console.log('aaa');

                        $.each(codes[$lotteryId], function (k, it) {
                            var color = getKlpkColor(it);
                            li = '<li class="klpk">' + color[0] + '<span class="' + color[2] + '">' + color[1] + '</span></li>';
                            codes[$lotteryId][k] = li;
                        });
                        //极速六合彩
                    } else if ($lotteryId === 25) {
                        $.each(codes[$lotteryId], function (k, it) {
                            var color = getLhcColor(it);
                            li = '<li class="' + color + '">' + it + '</li>';
                            codes[$lotteryId][k] = li;
                        });
                    }
                    else {
                        $.each(codes[$lotteryId], function (k, it) {
                            codes[$lotteryId][k] = '<li>' + it + '</li>';
                        });
                    }
                } else {
                    codes[$lotteryId] = [
                        "<li>正</li>",
                        "<li>在</li>",
                        "<li>开</li>",
                        "<li>奖</li>"
                    ];
                }
            });
            html = template('template_model', {list: $data, codes: codes});
            $('.theLottery .lotteryUl').html(html);
        },
        complete: function () {
            hideLoading();
        }
    });
});