//数据加载的时候出现弹窗
function showLoading() {
    layer.load(2, {
        shade: [0.1, '#000'] //0.1透明度的白色背景
    });
    $(".layui-layer-loading").css({"width": "auto", "left": "50%"});
};
function hideLoading() {
    layer.closeAll();
};
//香港六合彩
function getLhcColor($num) {
    var Reds = ['1', '2', '7', '8', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46'];
    var Blues = ['3', '4', '9', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48'];
    var Greens = ['5', '6', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49'];

    if ($.inArray($num, Reds) != -1) {
        return 'red2';
    } else if ($.inArray($num, Blues) != -1) {
        return 'blue1';
    } else if ($.inArray($num, Greens) != -1) {
        return 'green1';
    }
};
//北京pk
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
//山东快乐扑克
function getKlpkColor($num) {
    var suit = {
        99: '<i class="mh"></i>',
        100: '<i class="fk"></i>',
        104: '<i class="rt"></i>',
        115: '<i class="bt"></i>'
    };
    var it = $num.split('');
    if (it[0].toString().toUpperCase() === 'T') it[0] = '10';
    if (it[1] == "h" || it[1] == "d") {
        return [suit[it[1].charCodeAt()], it[0], 'red4'];
    } else {
        return [suit[it[1].charCodeAt()], it[0], ''];
    }
}
var $lotteryData = {
    1: {'cname': '重庆时时彩', 'image': '/images/mobile/dt-ssc03.png'},
    8: {'cname': '天津时时彩', 'image': '/images/mobile/dt-ssc04.png'},
    4: {'cname': '新疆时时彩', 'image': '/images/mobile/dt-ssc05.png'},
    17: {'cname': '北京pk拾', 'image': '/images/mobile/id/17.png'},
    12: {'cname': '江苏快三', 'image': '/images/mobile/dt-klc02.png'},
    19: {'cname': '安徽快三', 'image': '/images/mobile/dt-klc06.png'},
    23: {'cname': '幸运28', 'image': '/images/mobile/id/23.png'},
    9: {'cname': '福彩3D', 'image': '/images/mobile/dt-dp01.png'},
    21: {'cname': '香港⑥合彩', 'image': '/images/mobile/dtimg/dt-dp04.png'},
    22: {'cname': '双色球', 'image': '/images/mobile/id/22.png'},
    10: {'cname': '体彩P3P5', 'image': '/images/mobile/dtimg/p3p5.png'},
    2: {'cname': '山东11选5', 'image': '/images/mobile/id/2.png'},
    5: {'cname': '江苏11选5', 'image': '/images/mobile/id/5.png'},
    6: {'cname': '江西11选5', 'image': '/images/mobile/id/6.png'},
    7: {'cname': '广东11选5', 'image': '/images/mobile/id/7.png'},
    16: {'cname': '11选5分分彩', 'image': '/images/mobile/id/16.png'},
    11: {'cname': '幸运分分彩', 'image': '/images/mobile/dt-ssc02.png'},
    18: {'cname': '东京1.5分彩', 'image': '/images/mobile/dtimg/dj15.png'},
    13: {'cname': '快三分分彩', 'image': '/images/mobile/dt-klc05.png'},
    24: {'cname': '腾讯分分彩', 'image': '/images/mobile/dt-txffc29.png'},
    14: {'cname': '山东快乐扑克', 'image': '/images/mobile/dtimg/ls-28.png'},
    25: {'cname': '极速⑥合彩', 'image': '/images/mobile/id/25.png'},
    26: {'cname': '幸运飞艇', 'image': '/images/mobile/id/26.png'}
};
/******彩种汇总*****/
$(function () {
    //tab切换
    $(".GameNav ul li").each(function (i) {
        $(".GameNav ul li").eq(0).click();
        $(".GameNav ul li").eq(i).click(function () {
            $(".GameNav ul li").eq(i).addClass("borcor").siblings().removeClass("borcor")
            $(".GameMain").eq(i).addClass("DisplayBlock").siblings().removeClass("DisplayBlock")
        })
    });
    //定义彩种列表id
    var $list = [
        [1, 4, 8, 17, 12, 19, 14, 23, 24, 25, 26],//高频彩
        [9, 10, 21, 22],//低频彩
        [2, 5, 6, 7],//11选5
        [16, 13, 11, 18]//分分彩
    ];
    //数据请求
    $.ajax({
        url: '?a=openInfo',
        cache: false,
        type: 'GET',
        dataType: 'JSON',
        beforeSend: function () {
            showLoading();
        },
        success: function (data) {
            //循环彩种分类列表
            $list.forEach(function ($value, $index) {
                var content = $('.resultMain').eq($index);
                $value.forEach(function (lotteryId) {
                    var item = data[lotteryId];
                    if (item) {
                        $lotteryData[lotteryId].cname = item.lastIssueInfo.cname;
                        var codestr = '';
                        item.lotteryId = lotteryId;
                        var html = template('template_default', {value: item});
                        content.append(html);
                        if (item.lastIssueInfo.code) {
                            var codes;
                            //有两个号码的特殊处理
                            if (item.lastIssueInfo.code.indexOf(' ') > -1) {
                                codes = item.lastIssueInfo.code.split(' ');
                            } else {
                                codes = item.lastIssueInfo.code.split('');
                            }
                            if (lotteryId === 21 || lotteryId == 25) {
                                codes.forEach(function (it, k) {
                                    var color = getLhcColor(it);
                                    codestr += '<b class="' + color + '">' + it + '</b>';
                                });
                            } else if (lotteryId == 14) {
                                $.each(codes, function (k, it) {
                                    var color = getKlpkColor(it);
                                    codestr += '<b class="klpk">' + color[0] + '<p class="' + color[2] + '">' + color[1] + '</p></b>';
                                });
                            } else if (lotteryId == 17 || lotteryId == 26) {
                                $.each(codes, function (k, it) {
                                    var color = getPksColor(it);
                                    codestr += '<b class="' + color + '">' + it + '</b>';
                                });
                            } else if (lotteryId == 22) {
                                $.each(codes, function (k, it) {
                                    if (k == 6) {
                                        codestr += '<b class="blue1">' + it + '</b>'
                                    } else {
                                        codestr += '<b>' + it + '</b>'
                                    }
                                });
                            }
                            else {
                                codes.forEach(function ($code) {
                                    codestr += '<b>' + $code + '</b>';
                                });
                            }

                            $('.LotteryId_' + lotteryId + ' .gameCir').html(codestr);
                        }

                    }
                });
            });
        },
        complete: function () {
            hideLoading();
        }
    });
});

// 返回按钮
// 判断 .resultNav 的 display属性
// 为none则当前是详情页，则显示彩种
// 不为none则为彩种页，则回退history.go(-1)
$('.btn-back').click(function () {
    var $nav = $('.resultNav');
    if ($nav.css('display') == 'none') {
        $('.resultDetail').removeClass('DisplayBlock');
        $('.resultNav').css('display', 'flex');
        // 判断导航中的选中状态，选中第几项
        $.each($('.resultNav ul li'), function (i, item) {
            if ($(item).hasClass('borcor')) {
                // 就将第几项添加class
                $('.resultMain').eq(i).addClass('DisplayBlock');
            }
        });
    } else {
        history.go(-1);
    }
});

// 分页奖期开奖号码展示
$('.selectBtn').live('click', function () {
    function showLoading() {
        layer.load(2, {
            shade: [0.1, '#000'] //0.1透明度的白色背景
        });
        $(".layui-layer-loading").css({"width": "auto", "left": "50%"});
    }

    function hideLoading() {
        layer.closeAll();
    }

    // $('.lottResult li').remove();
    // $('.resultNav').css('display','none')

    // 隐藏导航 隐藏彩种
    $('.resultNav').css('display', 'none');
    $('.resultMain').removeClass('DisplayBlock');
    $('.resultDetail').first().addClass('DisplayBlock');

    var lotteryId = $(this).data('id');
    var param = lotteryId == 0 ? '' : '&lotteryId=' + $(this).data('id');

    $.ajax({
        url: "?a=openInfo",
        data: {
            lotteryId: lotteryId,
            pageIndex: 1,
            pageSize: 30
        },
        dataType: 'JSON',
        beforeSend: function () {
            showLoading();
        },
        success: function (json) {
            var $data = [];
            for (var i = json.length - 1; i >= 0; i--) {
                // 有双数号码的,需要先处理格式
                if (json[i].code.indexOf(' ') > -1) {
                    json[i].code = json[i].code.split(' ');
                } else {
                    // 这里是将char数组转换成数组对象,因为char数组中无法将单个元素设置成字符串
                    json[i].code = json[i].code.split('');
                }

                // 将单个球的标签既样式添加到这里,模板中直接输出即可
                for (var j = json[i].code.length - 1; j >= 0; j--) {
                    //香港六合彩
                    if (lotteryId == 21 || lotteryId == 25) {
                        var value = json[i].code[j];
                        var color = getLhcColor(value);
                        json[i].code[j] = '<b class="' + color + '">' + value + '</b>';
                    }
                    //北京pk
                    else if (lotteryId == 17) {
                        var key = json[i].code[j];
                        var color = getPksColor(key);
                        json[i].code[j] = '<b class="' + color + '">' + key + '</b>';
                    }
                    //双色球
                    else if (lotteryId == 22) {
                        if (j == 6) {
                            json[i].code[j] = '<b class="blue1">' + json[i].code[j] + '</b>';
                        } else {
                            json[i].code[j] = '<b>' + json[i].code[j] + '</b>';
                        }
                    }
                    //山东快乐扑克
                    else if (lotteryId == 14) {
                        var color = getKlpkColor(json[i].code[j]);
                        json[i].code[j] = '<b class="klpk">' + color[0] + '<p class="' + color[2] + '">' + color[1] + '</p></b>';
                    }
                    else {
                        json[i].code[j] = '<b>' + json[i].code[j] + '</b>';
                    }
                }
                // 添加cname与彩票图片
                json[i]['cname'] = $lotteryData[lotteryId].cname;
                json[i]['image'] = $lotteryData[lotteryId].image;
            }
            var html = template('template', {list: json});
            $('.lotteryUl').html(html);
        },
        complete: function () {
            hideLoading();
        }
    });
});