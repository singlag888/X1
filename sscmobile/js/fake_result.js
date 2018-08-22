//显示登录弹窗
$(".goLogin").live('click', function () {
    layer.alert('请先登录!', {
        skin: 'layui-layer-lan',
        anim: 3,//动画类型
        title: ''
    })
});

//切换页面
$(".GameNav ul li").each(function (i) {
    $(".GameNav ul li").eq(0).click();
    $(".GameNav ul li").eq(i).click(function () {
        $(".GameNav ul li").eq(i).addClass("borcor").siblings().removeClass("borcor");
        $(".GameMain").eq(i).addClass("DisplayBlock").siblings().removeClass("DisplayBlock");
    })
});

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

//香港六合彩
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

//北京pk拾
function getPksColor($num) {
    var pksColor = {
        '01': 'yellow1',
        '02': 'blue1',
        '03': 'gray1',
        '04': 'orange1',
        '05': 'blue2',
        '06': 'blue3',
        '07': 'gray2',
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
    if (it[1] === "h" || it[1] === "d") {
        return [suit[it[1].charCodeAt()], it[0], 'red4'];
    } else {
        return [suit[it[1].charCodeAt()], it[0], ''];
    }
}

//彩种分类
var $list = [
    [1, 4, 8, 17, 12, 19, 14, 23, 24, 25, 26],//高频彩
    [9, 10, 21, 22],//低频彩
    [2, 5, 6, 7],//11选5
    [16, 13, 11, 18]//分分彩
];

$.ajax({
    url: "?a=openInfo",
    data: {'onlyLast': 1},
    cache: false,
    type: "GET",
    dataType: "JSON",
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
                    var codestr = '';
                    item.lotteryId = lotteryId;

                    var html = template('template_default', {'value': item});
                    content.append(html);

                    var codes;
                    if (item.lastIssueInfo.code) {
                        //有两个号码的特殊处理
                        if (item.lastIssueInfo.code.indexOf(' ') > -1) {
                            codes = item.lastIssueInfo.code.split(' ');
                        } else {
                            codes = item.lastIssueInfo.code.split('');
                        }

                        var color;
                        if (lotteryId === 21 || lotteryId === 25) {
                            codes.forEach(function (it, k) {
                                color = getLhcColor(it);
                                codestr += '<b class="' + color + '">' + it + '</b>';
                            });
                        } else if (lotteryId === 14) {
                            $.each(codes, function (k, it) {
                                color = getKlpkColor(it);
                                codestr += '<b class="klpk">' + color[0] + '<p class="' + color[2] + '">' + color[1] + '</p></b>';
                            });
                        } else if (lotteryId === 17 || lotteryId === 26) {
                            $.each(codes, function (k, it) {
                                color = getPksColor(it);
                                codestr += '<b class="' + color + '">' + it + '</b>';
                            });
                        } else if (lotteryId === 22) {
                            $.each(codes, function (k, it) {
                                if (k === 6) {
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