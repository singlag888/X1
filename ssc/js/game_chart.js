$(function () {
    var url = window.location.href;
    var param = url.split('?')[1].split('&');
    var lotteryId = 0;
    var $data = null;

    param.forEach(function (item) {
        item = item.split('=');
        if (item[0] === 'lottery_id') {
            lotteryId = item[1];
        }
    });

    if (lotteryId <= 0) return false;

    lotteryId = parseInt(lotteryId);

    $.ajax({
        url: "?a=openInfo",
        data: {
            lotteryId: lotteryId,
            sort: 'ASC'
        },
        dataType: 'JSON',
        beforeSend: function () {
            var html = '';

            if ($.inArray(lotteryId, [1, 4, 5, 8, 10, 11, 18, 24]) > -1) {
                //时时彩
                html = template('head_ssc');
            } else if ($.inArray(lotteryId, [9, 23]) > -1) {
                //福彩3D,体彩
                html = template('head_3D');
            } else if ($.inArray(lotteryId, [17, 26]) > -1) {
                //北京pks
                html = template('head_pks');
            } else if ($.inArray(lotteryId, [2, 6, 7, 16]) > -1) {
                //11选5
                html = template('head_115');
            } else if ($.inArray(lotteryId, [22]) > -1) {
                //双色球
                html = template('head_ssq');
            } else if ($.inArray(lotteryId, [21, 25]) > -1) {
                //香港六合彩
                html = template('head_lhc');
            }
            $('#chartsTable tbody').append(html);
        },
        success: function (json) {
            if (typeof json !== 'object') json = JSON.parse(json);

            if (json.length <= 0) return true;

            for (var i = 0, len = json.length; i < len; ++i) {
                // 有双数号码的,需要先处理格式
                if (json[i].code.indexOf(' ') > -1) {
                    json[i].code = json[i].code.split(' ');
                } else {
                    // 这里是将char数组转换成数组对象,因为char数组中无法将单个元素设置成字符串
                    json[i].code = json[i].code.split('');
                }
            }

            var html = '';

            if ($.inArray(lotteryId, [1, 4, 5, 8, 10, 11, 18, 24]) > -1) {
                //时时彩
                html = template('content_ssc', {list: json});
            } else if ($.inArray(lotteryId, [17, 26]) > -1) {
                //北京pks
                html = template('content_pks', {list: json});
            } else if ($.inArray(lotteryId, [9, 23]) > -1) {
                //福彩
                html = template('content_ssc', {list: json});
            } else if ($.inArray(lotteryId, [2, 6, 7, 16]) > -1) {
                //11选5
                html = template('content_115', {list: json});
            } else if ($.inArray(lotteryId, [22]) > -1) {
                //双色球
                html = template('content_ssq', {list: json});
            } else if ($.inArray(lotteryId, [21, 25]) > -1) {
                //六合彩
                html = template('content_lhc', {list: json});
            }

            $('#chartsTable tbody').append(html);

            $data = json;
            //生成遗漏
            creatMissing(lotteryId, json);
        },
        complete: function () {
            $('.layer').css('display', 'none');

            var html = '';

            if ($.inArray(lotteryId, [1, 4, 5, 8, 10, 11, 18, 24]) > -1) {
                //时时彩
                html = template('foot_ssc');
            } else if ($.inArray(lotteryId, [9, 23]) > -1) {
                //福彩3D,体彩
                html = template('foot_3D');
            } else if ($.inArray(lotteryId, [17, 26]) > -1) {
                //北京pks
                html = template('foot_pks');
            } else if ($.inArray(lotteryId, [2, 6, 7, 16]) > -1) {
                //11选5
                html = template('foot_115');
            } else if ($.inArray(lotteryId, [22]) > -1) {
                //双色球
                html = template('foot_ssq');
            } else if ($.inArray(lotteryId, [21, 25]) > -1) {
                //香港六合彩
                html = template('foot_lhc');
            }

            $('#chartsTable tbody').append(html);

            /* 下面是画线 */
            if (!$data || lotteryId === 21 || lotteryId === 22 || lotteryId === 25) return false;
            Chart.init();
            DrawLine.bind("chartsTable", "has_line");

            if (lotteryId === 2 || lotteryId === 6 || lotteryId === 7 || lotteryId === 16) {
                var $codeLen = $data[0].code.length;
                for (var i = 0; i < $codeLen; i++) {
                    DrawLine.color(i % 2 > 0 ? '#3672b2' : '#ab0000');
                    DrawLine.add((i * 11 + $codeLen + 1), 2, 11, 0);
                }
            } else {
                var $codeLen = $data[0].code.length;
                for (var i = 0; i < $codeLen; i++) {
                    DrawLine.color(i % 2 > 0 ? '#3672b2' : '#ab0000');
                    DrawLine.add((i * 10 + $codeLen + 1), 2, 10, 0);
                }
            }

            DrawLine.draw(Chart.ini.default_has_line);
        }

    });

    //遗漏
    function creatMissing($lotteryId, $data) {
        // 没有数据不计算
        if (!$data)return false;
        // 获取奖号长度
        var $codeLen = $data[0].code.length;
        var $colNum = 1 + $codeLen;

        var $rowNum = 2;

        // 循环列
        for (var i = $colNum, $colLen = $('#chartsTable tr').eq($rowNum).find('td').length; i < $colLen; ++i) {
            //console.log($colLen);
            var $missNum = 0;
            // 行循环
            for (var j = $rowNum, $rowLen = $rowNum + $data.length; j < $rowLen; ++j) {
                //console.log($rowLen);
                // 找到行和列(通过坐标找到块)
                var $currentRow = $('#chartsTable tr').eq(j);
                var $currentCol = $($currentRow[0]).find('td').eq(i);

                if ($currentCol.hasClass('charball')) {
                    $missNum = 0;
                    //console.log(0);
                } else {
                    $missNum++;
                    //console.log($missNum);

                    $currentCol.find('div').last().html($missNum);
                }
            }
        }
        //添加遗漏背景颜色
        for (var i = $colNum, $colLen = $('#chartsTable tr').eq($rowNum).find('td').length; i < $colLen; ++i) {
            for (var j = $rowLen = $rowNum + $data.length; j >= $rowNum; --j) {
                var $currentRow = $('#chartsTable tr').eq(j);
                var $currentCol = $($currentRow[0]).find('td').eq(i);
                if ($currentCol.hasClass('charball')) {
                    break;
                } else {
                    $currentCol.find('div').last().addClass('gray').css('background', 'rgba(166,229,250,0.4)');
                }
            }
        }
    }

});
//遗漏的关闭与否
window.onload = function () {
    $('#no_miss').click(function () {
        var nols = $("div[class^='ball03'],div[class^='ball04']");
        var checked = $(this).attr("checked");
        $.each(nols, function (i, n) {
            if (checked) {
                n.style.display = "none"
            } else {
                n.style.display = 'block'
            }
        });
    });

    //遗漏分层显示
    $('#has_miss').click(function () {
        var rols = $("div[class^='ball03 gray'],div[class^='ball04 gray'],div[class^='ball04 m gray']");
        var checked = $(this).attr("checked");
        $.each(rols, function (j, v) {
            if (checked) {
                v.style.background = "rgba(166,229,250,0.4)"
            } else {
                v.style.background = "none"
            }
        });
    });
};
