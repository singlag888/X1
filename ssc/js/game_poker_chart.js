$(function () {
    var $codeLen = 0;
    var $dataLen = 0;
    var lotteryId = 0;

    var $suitClass = {'s': 'poker_heit', 'h': 'poker_hongt', 'c': 'poker_meih', 'd': 'poker_fangk'};
    var $style = {'s': 'color:black', 'h': 'color:red', 'c': 'color:black', 'd': 'color:red'};
    var $points = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    var $suitList = ['h', 'c', 's', 'd'];
    var $formList = [
        {'name': '散牌', 'style': 'background: #999'},
        {'name': '同花', 'style': 'background: #339999'},
        {'name': '顺子', 'style': 'background: #668721'},
        {'name': '同花顺', 'style': 'background: #aa3300'},
        {'name': '豹子', 'style': 'background: #aeba47'},
        {'name': '对子', 'style': 'background: #ae7a47'}
    ];

    var url = window.location.href;
    //定义变量分割地址
    var param = url.split("?")[1].split("&");
    param.forEach(function (item) {
        item = item.split('=');
        if (item[0] === 'lottery_id') {
            lotteryId = item[1];
        }
    });

    if (lotteryId <= 0) return false;

    lotteryId = parseInt(lotteryId);

    //请求ajax
    $.ajax({
        url: "?a=openInfo",
        data: {
            lotteryId: lotteryId,
            sort: 'ASC'
        },
        dataType: 'JSON',
        beforeSend: function () {
            var html = template('head_poker');
            $("#chartsTable tbody").append(html);
        },
        success: function (json) {
            if (json.length <= 0) return false;

            for (var i = 0; i < json.length; ++i) {
                json[i].code = json[i].code.split(' ');
                json[i].codeList = [];
                json[i].suitList = [];
                json[i].codeCount = {};
                json[i].suitCount = {'h': 0, 'c': 0, 's': 0, 'd': 0};

                for (var j = 0; j < json[i].code.length; ++j) {
                    json[i].code[j] = json[i].code[j].split('');

                    // 这里就先把花色/点数颜色给设置上
                    var $code = {'point': json[i].code[j][0], 'suit': json[i].code[j][1]};
                    $code.suitClass = $suitClass[$code.suit];
                    $code.style = $style[$code.suit];
                    // 注意这里转的是字符串 '10'
                    if ($code.point.toUpperCase() === 'T') $code.point = '10';
                    json[i].code[j] = $code;

                    // 统计,数字不能作为 .后面的值 所以在前面加个 c
                    eval('if(!json[i].codeCount.c' + $code.point + '){json[i].codeCount.c' + $code.point + ' = 1}else{++json[i].codeCount.c' + $code.point + '}');
                    eval('if(!json[i].suitCount.' + $code.suit + '){json[i].suitCount.' + $code.suit + ' = 1}else{++json[i].suitCount.' + $code.suit + '}');
                    json[i].codeList.push($code.point);
                    json[i].suitList.push($code.suit);
                }

                // json[i]是对象,所以传值依然是引用传递.
                // 克隆一份转值传递
                json[i].form = getForm(json[i].codeList.clone(), json[i].suitList.clone());
            }

            var html = template('content_poker', {
                'list': json,
                'points': $points,
                'suitList': $suitList,
                'suitClass': $suitClass,
                'formList': $formList
            });
            $('#chartsTable tbody').append(html);

            if (json) {
                $codeLen = json[0].code.length * 2;
                $dataLen = json.length;
                createMissing(++$codeLen, $dataLen);
            }
        },
        complete: function () {

            var html = template('foot_poker');
            $("#chartsTable tbody").append(html);
            $('.layer').css('display', 'none');
        }
    });

    /**
     * 获取形态
     * @param $codeList
     * @param $suitList
     */
    function getForm($codeList, $suitList) {
        // 先将字母转成对应数值
        for (var $i = 0, $len = $codeList.length; $i < $len; ++$i) {
            switch ($codeList[$i]) {
                case 'A':
                    $codeList[$i] = 1;
                    break;
                case 'J':
                    $codeList[$i] = 11;
                    break;
                case 'Q':
                    $codeList[$i] = 12;
                    break;
                case 'K':
                    $codeList[$i] = 13;
                    break;
                default:
                    $codeList[$i] = parseInt($codeList[$i]);
            }
        }

        // 正序排列
        $codeList.sort(function (a, b) {
            return a - b
        });

        // 这里的 $formList 看顶部定义
        if (isLeopard($codeList)) {
            return $formList[4];
        } else if (isPair($codeList)) {
            return $formList[5];
        } else if (isFlush($suitList) && isStraight($codeList)) {
            return $formList[3];
        } else if (isFlush($suitList)) {
            return $formList[1];
        } else if (isStraight($codeList)) {
            return $formList[2];
        } else {
            return $formList[0];
        }
    }

    /**
     * 0=1=2
     * @param $suitList
     * @returns {boolean}
     */
    function isFlush($suitList) {
        return ($suitList[0] === $suitList[1] && $suitList[0] === $suitList[2] && $suitList[1] === $suitList[2]);
    }

    /**
     * 等差数列 step = 1
     * AQK特殊情况单独判断
     * @param $codeList
     * @returns {boolean}
     */
    function isStraight($codeList) {
        // AQK
        if ($codeList.indexOf(1) > -1 && $codeList.indexOf(12) > -1 && $codeList.indexOf(13) > -1) return true;

        return ($codeList[0] + 1 === $codeList[1] && $codeList[1] + 1 === $codeList[2]);
    }

    /**
     * 0=1=2
     * @param $codeList
     * @returns {boolean}
     */
    function isLeopard($codeList) {
        return (
            $codeList[0] === $codeList[1] &&
            $codeList[0] === $codeList[2]
        );
    }

    /**
     * 0=1 || 0=2 || 1=2
     * @param $codeList
     * @returns {boolean}
     */
    function isPair($codeList) {
        return (
            ($codeList[0] === $codeList[1] && $codeList[0] !== $codeList[2]) ||
            ($codeList[0] !== $codeList[1] && $codeList[0] === $codeList[2]) ||
            ($codeList[0] !== $codeList[1] && $codeList[1] === $codeList[2])
        );
    }

    /**
     * 生成遗漏
     * @param $startColNum 开始列号
     * @param $dataLen 数据长度
     * @param $colLen 处理列长度
     */
    function createMissing($startColNum, $dataLen, $colLen = 0) {
        var $endColNum = 0;
        //获取遗漏值的第一行tr
        var $rowNum = 2;
        // 指定则加上起始点得到终点.不指定长度则为全部
        if ($colLen > 0){
            $endColNum = $colLen + $startColNum;
        }else{
            $endColNum = $('#chartsTable tr').eq($rowNum).find('td').length;
        }

        // 循环列
        for (var $i = $startColNum; $i < $endColNum; ++$i) {
            //定义遗漏值
            var $missNum = 0;
            //循环行
            for (var $j = $rowNum, $rowLen = $rowNum + $dataLen; $j < $rowLen; ++$j) {
                //通过行和列找到具体的一个td
                var $currentRow = $('#chartsTable tr').eq($j);
                var $currentCol = $($currentRow[0]).find('td').eq($i);
                //判断，如果有开奖号码,就将遗漏值归零；
                var $lastDiv = $currentCol.find('div').last();
                if ($lastDiv.hasClass('ball01') ||
                    $lastDiv.hasClass('ball02') ||
                    $lastDiv.hasClass('tenthousand_pork1') ||
                    $lastDiv.hasClass('pork_form')) {
                    $missNum = 0;
                } else {
                    $missNum++;
                    $currentCol.find('div').last().html($missNum);
                }
            }
        }
    }

    //显示遗漏
    $("#no_miss").click(function () {
        var checked = $(this).attr("checked");
        $.each($("div[class^='ball03'],div[class^='ball04']"), function (i, n) {
            if (checked === 'checked') {
                n.style.display = 'none';
            } else {
                n.style.display = 'block';
            }
        });
    });
});