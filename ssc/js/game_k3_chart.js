    //划线
    function drawrun() {
        Chart.init();
        DrawLine.bind("chartsTable", "has_line");

        DrawLine.color('#499495');
        DrawLine.add(10, 2, 4, 0);
        DrawLine.color('#E4A8A8');
        DrawLine.add(14, 2, 4, 0);
        DrawLine.color('#499495');
        DrawLine.add(18, 2, 16, 0);

        DrawLine.draw(Chart.ini.default_has_line);
        if ($("#chartsTable").width() > $('body').width()) {
            $('body').width($("#chartsTable").width() + "px");
        }
        $("#container").height($("#chartsTable").height() + "px");
        $("#missedTable").width($("#chartsTable").width() + "px");
    }
    /***/
    var $codeLen = 0;
    var $dataLen = 0;

    function runoddeven(flag) {
        var class1 = 'evencol';
        var class2 = 'oddcol';
        var dataname = 'missodd';

        if (flag == 2) {
            class2 = 'evencol';
            class1 = 'oddcol';
            dataname = 'misseven';
        }
        $("canvas").remove();
        $(".oe_none").each(function (j, jtem) {
            $(jtem).html($(jtem).data(dataname));
        });
        $("." + class1).each(function (i, item) {
            $(item).attr("class", "wdh " + class1);
            var evenchild = $(item).find(".ball02")[0];

            $(evenchild).data("val", $(evenchild).html());
            $(evenchild).html($(evenchild).data(dataname));

            $(evenchild).attr("class", "ball04");
            if ($("#no_miss").attr("checked") === 'checked') {
                $(evenchild).css("display", "none");
            }
        });
        $("." + class2).each(function (i1, item1) {
            $(item1).attr("class", "charball " + class2);
            var oddchild = $(item1).find(".ball04")[0];

            $(oddchild).attr("class", "ball02");
            $(oddchild).html($(oddchild).data("val"));

            if ($("#no_miss").attr("checked") === 'checked') {
                $(oddchild).css("display", "block");
            }
        });

        drawrun();
        $dataLen && createMissing($codeLen,$dataLen);
    }
    function change1(flag) {
        // 切换链接样式
        var str1 = '#odd';
        var str2 = '#even';
        if (flag == 'even') {
            str1 = '#even';
            str2 = '#odd';
        }
        $(str2).attr("class", 'run');
        $(str1).attr("class", '');

        if (flag == 'even') {
            runoddeven(2);
        } else {
            runoddeven(1);
        }
    }
    function change2(flag) {
        var str1 = '#big';
        var str2 = '#small';
        if (flag == 'small') {
            str1 = '#small';
            str2 = '#big';
        }
        $(str2).attr("class", 'run');
        $(str1).attr("class", '');
        if (flag == 'small') {
            runbigsmall(2);
        } else {
            runbigsmall(1);
        }
    }
    function runbigsmall(flag) {
        var class1 = 'smallcol';
        var class2 = 'bigcol';
        var dataname = 'missbig';
        if (flag == 2) {
            class2 = 'smallcol';
            class1 = 'bigcol';
            dataname = 'misssmall';
        }
        $("canvas").remove();
        $(".bs_none").each(function (j, jtem) {
            $(jtem).html($(jtem).data(dataname));
        });
        $("." + class1).each(function (i, item) {
            $(item).attr("class", "wdh " + class1);
            var evenchild = $(item).find(".ball01")[0];
            $(evenchild).data("val", $(evenchild).html());
            $(evenchild).html($(evenchild).data(dataname));
            $(evenchild).attr("class", "ball03");
            if ($("#no_miss").attr("checked") === 'checked') {
                $(evenchild).css("display", "none");
            }
        });
        $("." + class2).each(function (i1, item1) {
            $(item1).attr("class", "charball " + class2);
            var oddchild = $(item1).find(".ball03")[0];
            $(oddchild).attr("class", "ball01");
            $(oddchild).html($(oddchild).data("val"));
            if ($("#no_miss").attr("checked") === 'checked') {
                $(oddchild).css("display", "block");
            }
        });
        drawrun();

        $dataLen && createMissing($codeLen,$dataLen);
    }

    window.onload = function () {
        $("#even").click(function () {
            change1('even');
        });
        $("#odd").click(function () {
            change1('odd');
        });
        $("#big").click(function () {
            change2('big');
        });
        $("#small").click(function () {
            change2('small');
        });

        //显示遗漏
        $("#no_miss").click(function () {
            var checked = $(this).attr("checked");
            $.each($("div[class^='ball03'],div[class^='ball04']"), function (i, n) {
                if (checked == 'checked') {
                    n.style.display = 'none';
                } else {
                    n.style.display = 'block';
                }
            });
        });
    };
    /****/
    //生成遗漏
    function createMissing($startColNum,$dataLen) {
        //获取遗漏值的第一行tr
        var $rowNum = 2;
        // 循环列
        for (var i = $startColNum, $colLen = $('#chartsTable tr').eq($rowNum).find('td').length; i < $colLen; ++i) {
            //console.log($colLen);
            //定义遗漏值
            var $missNum = 0;
            //循环行
            for (var j = $rowNum, $rowLen = $rowNum + $dataLen; j < $rowLen; ++j) {
                //通过行和列找到具体的一个td
                var $currentRow = $('#chartsTable tr').eq(j);
                //console.log($currentRow);
                var $currentCol = $($currentRow[0]).find('td').eq(i);
                //console.log($($currentRow[0]));
                //判断，如果有开奖号码,就将遗漏值归零；
                // if($currentCol.hasClass('charball')){
                var lastDiv = $currentCol.find('div').last();
                if (lastDiv.hasClass('ball01') || lastDiv.hasClass('ball02')) {
                    //console.log($currentCol);
                    $missNum = 0;
                } else {
                    $missNum++;
                    $currentCol.find('div').last().html($missNum);
                }
            }
        }
    }

    $(function () {
        var url = window.location.href;
        //console.log(url);
        //定义变量分割地址
        var param = url.split("?")[1].split("&");
        param.forEach(function (item) {
            item = item.split('=');
            if (item[0] == 'lottery_id') {
                lotteryId = item[1];
            }
        });
        if (lotteryId <= false) return false;
        //请求ajax
        $.ajax({
            url: "?a=openInfo",
            data: {
                lotteryId: lotteryId,
                sort: 'ASC'//获得的数据按照倒序排列
            },
            dataType: 'JSON',
            beforeSend: function () {
                var html = template("head_k3");
                $("#chartsTable tbody").append(html);
            },
            success: function (json) {
                //console.log(json);
                for (var i = 0; i < json.length; ++i) {
                    //拿到的数据只能进处理
                    //console.log(json[i].code);
                    json[i].code = json[i].code.split('');
                    //循环开奖号码
                    for (var j = 0; j < json[i].code.length; ++j) {
                        //将字符串转化为整数类型
                        //console.log(json[i].code[j]);
                        json[i].code[j] = parseInt(json[i].code[j]);
                    }
                }

                var html = template('content_k3', {list: json});
                $('#chartsTable tbody').append(html);
                //回调
                $data = json;

                if(json){
                    $codeLen = json[0].code.length;
                    $dataLen = json.length;
                    createMissing(++$codeLen,$dataLen);
                }
            },
            complete: function () {

                var html = template('foot_k3');
                $("#chartsTable tbody").append(html);
                $('.layer').css('display', 'none');

                change1('odd');
            }
        });
    })