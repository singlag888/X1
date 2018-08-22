//弹窗
(function () {
    $('.goLogin').live('click', function () {
        layer.alert('请先登录!', {
            skin: 'layui-layer-lan',
            closeBtn: 1,
            anim: 3,//动画类型
            title: ''
        });
    })
})();

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

$(function () {
    $.ajax({
        url: '?a=openInfo',
        data: {'onlyLast': 1},
        cache: false,
        type: "GET",
        dataType: "JSON",
        beforeSend: function () {
            showLoading();
        },
        success: function ($data) {
            // delete $data[15];

            var codes = [];
            var li = '';
            $.each($data, function ($lotteryId, $item) {

                if ($item.lastIssueInfo.code) {
                    //对特殊奖号进行处理
                    if ($item.lastIssueInfo.code.indexOf(' ') > -1) {
                        codes[$lotteryId] = $item.lastIssueInfo.code.split(' ');
                    } else {
                        codes[$lotteryId] = $item.lastIssueInfo.code.split('');
                    }

                    //香港六合彩
                    if ($lotteryId == 21) {
                        var Reds = ['1', '2', '7', '8', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46'];
                        var Blues = ['3', '4', '9', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48'];
                        var Greens = ['5', '6', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49'];

                        $.each(codes[21], function (k, it) {
                            if ($.inArray(it, Reds) != -1) {
                                li = '<li class="red2">' + it + '</li>';
                            } else if ($.inArray(it, Blues) != -1) {
                                li = '<li class="blue1">' + it + '</li>';
                            } else if ($.inArray(it, Greens) != -1) {
                                li = '<li class="green1">' + it + '</li>';
                            }

                            codes[21][k] = li;
                        });
                        //双色球    
                    } else if ($lotteryId == 22) {
                        $.each(codes[22], function (k, it) {
                            if (k == 6) {
                                li = '<li class="blue1">' + it + '</li>';
                            } else {
                                li = '<li>' + it + '</li>';
                            }
                            codes[22][k] = li;
                        });
                        //北京pk   
                    } else if ($lotteryId == 17 || $lotteryId == 26) {
                        $.each(codes[$lotteryId], function (k, it) {
                            if (it == 1) {
                                li = '<li class="yellow1">' + it + '</li>';
                            } else if (it == 2) {
                                li = '<li class="blue1">' + it + '</li>';
                            } else if (it == 3) {
                                li = '<li class="gary1">' + it + '</li>';
                            } else if (it == 4) {
                                li = '<li class="orange1">' + it + '</li>';
                            } else if (it == 5) {
                                li = '<li class="blue2">' + it + '</li>';
                            } else if (it == 6) {
                                li = '<li class="blue3">' + it + '</li>';
                            } else if (it == 7) {
                                li = '<li class="gary2">' + it + '</li>';
                            } else if (it == 8) {
                                li = '<li class="red2">' + it + '</li>';
                            } else if (it == 9) {
                                li = '<li class="dark">' + it + '</li>';
                            } else if (it == 10) {
                                li = '<li class="green1">' + it + '</li>';
                            }
                            codes[$lotteryId][k] = li;
                        });
                        //山东快乐扑克    
                    } else if ($lotteryId == 14) {
                        $.each(codes[14], function (k, it) {
                            var suit = {
                                99: '<i class="mh"></i>',
                                100: '<i class="fk"></i>',
                                104: '<i class="rt"></i>',
                                115: '<i class="bt"></i>'
                            };
                            var it = it.split('');
                            if (it[0].toString().toUpperCase() === 'T') it[0] = '10';

                            li = '<li class="klpk">' + suit[it[1].charCodeAt()];
                            if (it[1] == "h" || it[1] == "d") {
                                li += '<span class="red4">' + it[0] + '</span></li>'
                            } else {
                                li += '<span>' + it[0] + '</span></li>'
                            }
                            codes[14][k] = li;
                        });

                    } else if ($lotteryId == 25) {
                        var Reds = ['1', '2', '7', '8', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46'];
                        var Blues = ['3', '4', '9', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48'];
                        var Greens = ['5', '6', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49'];

                        $.each(codes[25], function (k, it) {
                            if ($.inArray(it, Reds) != -1) {
                                li = '<li class="red2">' + it + '</li>';
                            } else if ($.inArray(it, Blues) != -1) {
                                li = '<li class="blue1">' + it + '</li>';
                            } else if ($.inArray(it, Greens) != -1) {
                                li = '<li class="green1">' + it + '</li>';
                            }

                            codes[25][k] = li;
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

            var html = template('template_model', {list: $data, codes: codes});
            $('.theLottery .lotteryUl').append(html);

        },
        complete: function () {
            hideLoading();
        }
    });
});