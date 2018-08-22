// 这里定义了6个分类框中的具体彩种
// 要添加、删除、移动 只需要在下面修改id即可
var $list = [
    [1, 8, 4, 21, 22, 17, 26, 23, 11, 18, 2, 7, 16, 6, 5, 9, 10, 12, 19, 14, 13, 25, 24], // credit
    [1, 8, 4, 21, 22, 17, 23, 11, 18, 2, 7, 16, 6, 5, 9, 10, 12, 19, 14, 13, 15, 25, 24], // allLottery
    [15, 1, 8, 4, 11, 18, 24], // sscList
    [16, 2, 6, 7, 5], // elevenChoiceFive
    [9, 10, 21, 22], // lows
    [17, 23, 12, 13, 19, 14, 25] // happy
];


// 留一个是为了看格式
var $runTimerList = {
    // timer_1: 0,
};
var $countdownList = {
    // cd_1: 0,
};

var $ajaxAble = true;
var $initialize = true;
// var $ableSet = new Set();

$(function() {
    loadData();
});

function loadData() {
    if ($ajaxAble) {
        $ajaxAble = false;
        $.ajax({
            url: '?a=openInfo',
            data: {
                'onlyLast': 1
            },
            type: 'GET',
            cache: false,
            dataType: 'JSON',
            success: function($data) {
                // $data[22].lastIssueInfo.code = '1 2 3 4 5 6 7';
                // $data[22].issueInfo.count_down = 200;

                $list.forEach(function($group, $i) {
                    var $box = $('.ls-logo').eq($i);
                    $group.forEach(function($lotteryId) {
                        if (!$initialize) {
                            // 初始化都允许,以后必须倒计时结束
                            // if ($ableSet.has($lotteryId)) {
                            //     $ableSet.delete($lotteryId);
                            // } else {
                            //     return true;
                            // }
                        }

                        var $item = $data[$lotteryId];

                        // 有值则是默认模板
                        if ($item) {
                            $item = $item.clone();

                            // 因为当前信用玩法在第0位,所以这里是0,换位后注意修改
                            if ($i === 0 && $item.fun.indexOf('_x') === -1) {
                                // 信用彩种的链接加上 '_x'
                                $item.fun += '_x';
                            }

                            /* 倒计时 begin */
                            var $countdown = $item.issueInfo.count_down;
                            if (typeof $countdown === 'number' && $countdown > 0) {
                                // console.log('设置id：' + $lotteryId + ' 倒计时为 ' + $countdown);
                                eval('$countdownList.cd_' + $lotteryId + ' = $countdown;');
                                eval('if(!$runTimerList.timer_' + $lotteryId + ') runTimer($lotteryId);');
                            } else {
                                eval('$countdownList.cd_' + $lotteryId + ' = 0;');
                            }

                            /* 倒计时 end */

                            /* 第一次加载时加载模板 begin */
                            if ($initialize) {
                                if ($item.lastIssueInfo.issue === 'null' || $item.lastIssueInfo.issue === null) {
                                    return true;
                                }

                                // 默认模板
                                $item.lotteryId = $lotteryId;
                                $box.append(template('lottery_default', {
                                    value: $item
                                }));
                                eval('if(!$runTimerList.timer_' + $lotteryId + ') runTimer($lotteryId);');
                            }
                            /* 第一次加载时加载模板 end */

                            /* 奖期 begin */
                            $('.LotteryId_' + $lotteryId + ' .issue').html('第' + $item.lastIssueInfo.issue + '期');
                            /* 奖期 end */

                            /* 开奖号 begin */
                            var $codes = [];
                            var $li = '';

                            if ($item.lastIssueInfo.code && $countdown > 0) {
                                if ($item.lastIssueInfo.code.indexOf(' ') > -1) {
                                    $codes = $item.lastIssueInfo.code.split(' ');
                                } else {
                                    // 这里是将char数组转换成数组对象,因为char数组中无法将单个元素设置成字符串
                                    $codes = $item.lastIssueInfo.code.split('');
                                }

                                // 特殊的格式挨个处理
                                if ($lotteryId === 14) {
                                    // 扑克型
                                    $codes.forEach(function(number) {
                                        var $suit = {
                                            99: '<i class="mh"></i>',
                                            100: '<i class="fk"></i>',
                                            104: '<i class="rt"></i>',
                                            115: '<i class="bt"></i>'
                                        };
                                        // 's' => '黑桃', 'h' => '红桃', 'c' => '梅花', 'd' => '方块'
                                        var $it = number.split('');
                                        // 把T变成10
                                        if ($it[0].toString().toUpperCase() === 'T') $it[0] = '10';

                                        $li += '<li class="klpk">' + $suit[$it[1].charCodeAt()];
                                        if ($it[1] === 'h' || $it[1] === 'd') {
                                            $li += '<span class="red4">' + $it[0] + '</span></li>';
                                        } else {
                                            $li += '<span>' + $it[0] + '</span></li>';
                                        }
                                    });
                                } else if ([17, 26].includes($lotteryId)) {
                                    // pks型
                                    $codes.forEach(function($number) {
                                        var color = getPksColor($number);
                                        $li += '<li class="' + color + '">' + $number + '</li>';
                                    });
                                } else if ([21, 25].includes($lotteryId)) {
                                    // 六合彩
                                    $codes.forEach(function($number) {
                                        var color = getLhcColor($number);
                                        $li += '<li class="' + color + '">' + $number + '</li>';
                                    });
                                } else if ($lotteryId === 22) {
                                    // 双色球
                                    $codes.forEach(function($number, $i) {
                                        var color = $i === 6 ? 'blue1' : '';
                                        $li += '<li class="' + color + '">' + $number + '</li>';
                                    });
                                } else {
                                    // 默认格式
                                    $codes.forEach(function($number) {
                                        $li += '<li>' + $number + '</li>';
                                    });
                                }

                                // 统一追加号码
                                $('.LotteryId_' + $lotteryId + ' .banner-num').html($li);
                            } else {
                                $('.LotteryId_' + $lotteryId + ' .banner-num').html('<li>正</li><li>在</li><li>开</li><li>奖</li>');
                            }

                            /* 开奖号 end */

                        } else {
                            if (!$initialize) {
                                return false;
                            }
                            // 特殊模板
                            if ($lotteryId === 15) {
                                $box.append(template('lottery_15', {}));
                            }
                        }
                    });
                });

                $ajaxAble = true;
                // 第一次加载后关闭,以后不再载入模板,仅刷新数据
                $initialize = false;
            }
        });
    }
}

function runTimer($lotteryId) {
    var $code =
        '$runTimerList.timer_' + $lotteryId + ' = setInterval(function () { ' +
        '$countdownList.cd_' + $lotteryId + '--; ' +
        'setTime($countdownList.cd_' + $lotteryId + ', $lotteryId);' +
        '},1000);';
    eval($code);
}

// 倒计时
function setTime($sec, $lotteryId) {
    // if ($lotteryId === 13) {
    //     console.log('刷新 ' + $lotteryId + ' 倒计时为 ' + $sec);
    // }

    if ($sec < 1) {
        eval(
            'clearInterval($runTimerList.timer_' + $lotteryId + ');' +
            '$runTimerList.timer_' + $lotteryId + ' = false;'
        );
        // console.log('清除了 ' + $lotteryId + ' 倒计时');

        // $ableSet.add($lotteryId);
        $('.LotteryId_' + $lotteryId + ' .day').text(0);
        $('.LotteryId_' + $lotteryId + ' .hour').text(0);
        $('.LotteryId_' + $lotteryId + ' .min').text(0);
        $('.LotteryId_' + $lotteryId + ' .second').text(0);

        loadData();
    } else {
        var $day = parseInt($sec / (3600 * 24));
        $('.LotteryId_' + $lotteryId + ' .day').text($day);
        $sec = $sec - (3600 * 24) * $day;
        var $hour = parseInt($sec / 3600);
        $('.LotteryId_' + $lotteryId + ' .hour').text($hour);
        $sec = $sec - 3600 * $hour;
        var $min = parseInt($sec / 60);
        $('.LotteryId_' + $lotteryId + ' .min').text($min);
        $sec = $sec - 60 * $min;
        $('.LotteryId_' + $lotteryId + ' .second').text($sec);
    }
}

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