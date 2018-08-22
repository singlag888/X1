'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

if ((typeof console === 'undefined' ? 'undefined' : _typeof(console)) != "object") {
    var console = {
        info: function info(a) {
            window.status = a;
        }
    };
}
var runTime = {
    remainTimer: 0,
    waitOpenTimer: 0,
    getLastOpenTimer: 0,
    scollTopIntervalTimer: 0,
    traceRemainTimer: 0,
    traceWaitOpenTimer: 0,
    clearAll: function clearAll() {
        clearInterval(runTime.remainTimer);
        clearInterval(runTime.waitOpenTimer);
        clearInterval(runTime.getLastOpenTimer);
        clearInterval(runTime.scollTopIntervalTimer);
        clearInterval(runTime.traceRemainTimer);
        clearInterval(runTime.traceWaitOpenTimer);
    }
};

var mySlotMachine = {
    //////////////////////////////老虎机参数   //////////////////////////////
    //转动的最小一个时长
    minRunTime: 300,
    //延迟停止时长
    delayStopTime: 30,
    //转，模糊方式
    runMethod: 1,

    //调度器对象
    interval: {},

    //弹出层对象
    popup: '',

    ////////////////////////////// 控制购彩参数   //////////////////////////////
    //再来一次：记录的已投注方案
    buyBarCodes: '',

    //控制是否重复运行
    playAgain: 0,

    //记载已选择的倍数
    multiple: 0,

    //连续开奖次数内部计数器
    openCounts: 0,

    //开奖结果(单次)
    openResult: {},

    //开奖结果(连续开奖统一返回)
    fullOpenResult: '',

    //连续开奖过程中，曾经中过奖 > 1, 否则 0;多次中奖则累加;
    hasPrize: 0,

    //遮罩层
    runShadePopup: '',

    //老虎机手柄动画
    startHandleAnimal: function startHandleAnimal() {
        //启动手柄动画
        var time = 30; //动画每帧间隔时间
        var handleObj = document.getElementById('slotMachineButton1');
        mySlotMachine.interval = setInterval(function () {
            var left = handleObj.style.left == '' ? 0 : parseInt(handleObj.style.left);
            if (left > 0 || left <= -234) {
                //复位
                handleObj.style.left = '0px';
                clearInterval(mySlotMachine.interval);
                handleObj.style.disable = false;
                return true;
            }
            handleObj.style.left = left - 39 + 'px';
        }, time);
    },

    //设置第N次开奖
    setOpenCount: function setOpenCount(num) {
        var str = '<div class="openCount">第<span class="openCountNum">' + num + '</span>次开奖</div>';
        $("#multipleOpenCode").html(str);
    },

    //设置中奖金额
    setHeaderPrizeValue: function setHeaderPrizeValue(popupId, prize) {
        var str = '<div class="getPrizeDiv">恭喜中奖<span class="getPrize">' + floatCaculate.doRound(prize, 2) + '</span>元</div>';
        $("#" + popupId).html(str);
    },

    //设置未中奖
    setHeaderNoPrize: function setHeaderNoPrize(popupId) {
        var str = '<div class="noPrize">再接再厉</div>';
        $("#" + popupId).html(str);
    },

    //设置中奖详细数据行
    setPrizeList: function setPrizeList() {
        var prize = parseFloat(mySlotMachine.openResult.prize) > 0 ? '<span>￥' + floatCaculate.doRound(mySlotMachine.openResult.prize, 2) + '</span>' : '未中奖';
        var str = '<tr><td>第' + mySlotMachine.openCounts + '次开奖</td><td>' + mySlotMachine.openResult.opencode + '</td><td>' + prize + '</td></tr>';
        $("#prizeList tbody").append(str);

        //开奖列表行
        var str = '<li class="show_code">' + mySlotMachine.openResult.opencode + '</li>';
        $("#listDiv ul").prepend(str);
        scrollCode.init();
    },

    //显示最后结果
    showLastResult: function showLastResult() {
        if (mySlotMachine.hasPrize > 0) {
            //有中奖
            mySlotMachine.setHeaderPrizeValue('resultHeader', floatCaculate.doRound(mySlotMachine.hasPrize, 2));
        } else {
            //一个也未中
            mySlotMachine.setHeaderNoPrize('resultHeader');
        }
        mySlotMachine.msgPopUpResult();
    },

    //判奖行为
    checkPrize: function checkPrize() {
        if (parseFloat(mySlotMachine.openResult.prize) > 0) {
            var sumVal = floatCaculate.add(parseFloat(mySlotMachine.hasPrize), parseFloat(mySlotMachine.openResult.prize));
            mySlotMachine.hasPrize = floatCaculate.doRound(sumVal, 2);
            return true;
        }
        return false;
    },

    //单次开奖遮罩
    onceShowShade: function onceShowShade() {
        mySlotMachine.runShadePopup = parent.layer.open({
            type: 1,
            offset: ['0', '0'],
            border: false,
            title: false,
            area: ['auto', 'auto'],
            closeBtn: 0,
            shade: [0.3, '#000'],
            time: 0, //0表示不自动关闭，若3秒后自动关闭，time: 3即可
            content: $('#onceShowShade')
        });
    },

    //连续开奖弹出框
    msgPopUp4Multiple: function msgPopUp4Multiple() {
        mySlotMachine.msgClose();

        //第N次开奖
        mySlotMachine.popup = parent.layer.open({
            type: 1,
            offset: ['210px', ''],
            border: false,
            title: false,
            skin: 'layui-layer-nobg',
            area: ['auto', 'auto'],
            closeBtn: 0,
            shade: [0.3, '#000'],
            time: 0, //0表示不自动关闭，若3秒后自动关闭，time: 3即可
            content: $('#multipleOpenCode')
        });
    },

    //开奖结果弹出框
    msgPopUpResult: function msgPopUpResult() {
        mySlotMachine.msgClose();

        mySlotMachine.popup = parent.layer.open({
            type: 1,
            offset: ['5.5rem', '0.5rem'],
            border: false,
            title: false,
            skin: 'layui-layer-nobg',
            area: ['15rem', '17rem'],
            closeBtn: 0,
            shade: [0.3, '#000'],
            time: 0, //0表示不自动关闭，若3秒后自动关闭，time: 3即可
            content: $('#openCodeResult')
        });
    },

    msgClose: function msgClose() {
        if (mySlotMachine.popup == '') {
            return;
        }
        parent.layer.close(mySlotMachine.popup);
    },

    //初始化动作
    init: function init() {
        $("#goBack").live("click", function () {
            mySlotMachine.msgClose();
            //在点返回重新选号时才清除openCounts控件值
            //mySlotMachine.clearParams();
            mySlotMachine.halfClearParams();
            $("#projectList").empty();
        });
        $("#playAgain").live("click", function () {

            mySlotMachine.hasPrize = 0;
            mySlotMachine.openCounts = 0;
            $("#prizeList td").remove();
            mySlotMachine.msgClose();
            mySlotMachine.playAgain = 1;
            //$("#slotMachineButton1").click();
            $("#confirmBtn").click();
        });
    },

    //用于摇奖中间中断，还不是完全restore，恢复锁定等
    halfClearParams: function halfClearParams() {
        mySlotMachine.openCounts = 0;
        mySlotMachine.buyBarCodes = '';
        mySlotMachine.playAgain = 0;
        mySlotMachine.openResult = {};
        mySlotMachine.hasPrize = 0;
        mySlotMachine.fullOpenResult = '';

        $("#prizeList td").remove();

        //chrome IE other
        $(document).off('mousewheel');
        //FF
        $(document).off('DOMMouseScroll');
    },

    //结尾清空缓存恢复初始化
    clearParams: function clearParams() {
        $("#multiple").val(1);
        $("#open_counts").val(1);
        $("#prizeList td").remove();
        mySlotMachine.openCounts = 0;
        mySlotMachine.buyBarCodes = '';
        mySlotMachine.multiple = 1;
        mySlotMachine.playAgain = 0;
        mySlotMachine.openResult = {};
        mySlotMachine.hasPrize = 0;

        $("#projectList").empty();
        $("#confirmBtn").removeClass('CantapCodeBtn_selected');
        $("#totalBetCount").text('0');
        $("#totalBetAmount").text('0.00');

        //chrome IE other
        $(document).off('mousewheel');
        //FF
        $(document).off('DOMMouseScroll');
    },

    //老虎机停止回调
    onComplete: function onComplete(active) {
        if (this.element[0].id == 'machine5') {
            var multipleOpenCount = $("#open_counts").val();
            //本次中奖返回true, 否则false; 并且总的hasPrize += ;
            var isGetPrize = mySlotMachine.checkPrize();
            //本次结果写入页面
            mySlotMachine.setPrizeList();

            if (multipleOpenCount > 1) {
                //连续开奖
                if (mySlotMachine.openCounts >= multipleOpenCount) {
                    //最后一次开奖已经结束
                    mySlotMachine.showLastResult();
                    mySlotMachine.fullOpenResult = '';
                    //更新余额到老虎机结束
                    showBalance();
                } else {
                    //进行下一次开奖
                    if (isGetPrize == true) {
                        //中奖先显示中奖奖金提示
                        mySlotMachine.setHeaderPrizeValue('multipleOpenCode', mySlotMachine.openResult.prize);
                        mySlotMachine.msgPopUp4Multiple();
                        var delayTime = 2000; //延迟弹窗和老虎机启动时间(毫秒)
                    } else {
                        var delayTime = 0; //延迟弹窗和老虎机启动时间(毫秒)
                    }
                    window.setTimeout("$('#confirmBtn').click()", delayTime);
                }
            } else {
                //单次开奖
                if (mySlotMachine.runShadePopup != '') {
                    parent.layer.close(mySlotMachine.runShadePopup);
                }

                mySlotMachine.fullOpenResult = '';
                //更新余额到老虎机结束
                showBalance();

                if (mySlotMachine.hasPrize > 0) {
                    //有中奖
                    mySlotMachine.setHeaderPrizeValue('resultHeader', mySlotMachine.hasPrize);
                } else {
                    //一个也未中
                    mySlotMachine.setHeaderNoPrize('resultHeader');
                }
                mySlotMachine.msgPopUpResult();
            }
        }
    },

    forbideMouseScroll: function forbideMouseScroll() {
        //页面移动到最顶端，防止看不到老虎机摇号
        $(document).scrollTop(0);

        //防止用户滚动：老虎机离开显示区会立刻停止
        var scrollFunc = function scrollFunc(e) {
            e = e || window.event;
            if (e && e.preventDefault) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                e.returnvalue = false;
                return false;
            }
        };

        //chrome IE other
        $(document).on('mousewheel', function (e) {
            scrollFunc(e);
        });
        //FF
        $(document).on('DOMMouseScroll', function (e) {
            scrollFunc(e);
        });
        //End防止用户滚动：老虎机离开显示区会立刻停止
    },

    //服务器返回数据后启动摇奖逻辑
    startOpenCode: function startOpenCode(response, codes, totalSingleInfo) {
        $("#token").val("");
        if (response.errno == 0 && response.opencode != '') {
            response = response.data[mySlotMachine.openCounts];
            var multipleOpenCount = $("#open_counts").val();

            mySlotMachine.forbideMouseScroll();
            mySlotMachine.openResult = response;
            mySlotMachine.buyBarCodes = codes;
            mySlotMachine.multiple = totalSingleInfo;
            mySlotMachine.playAgain = 0;
            mySlotMachine.openCounts++;

            //连续开奖
            if (multipleOpenCount > 1) {
                //转的同时：显示第N次开奖
                mySlotMachine.setOpenCount(mySlotMachine.openCounts);
                mySlotMachine.msgPopUp4Multiple();
                window.setTimeout(function () {
                    mySlotMachine.startHandleEvent(response.opencode);
                }, 1000);
            } else {
                mySlotMachine.onceShowShade();
                mySlotMachine.startHandleEvent(response.opencode); //老虎机启动
            }
            //要求点击开奖不清除已投注列表和各项统计
            //buyBar.removeAll();
        } else if (response.errno == 65534) {//重复订单的情况

        } else {
            var str = "购买失败:" + response.errstr + "(错误代码:" + response.errno + ")";
            if (multipleOpenCount > 1 && mySlotMachine.openCounts > 0) {
                var error_alert = layer.open({
                    dialog: {
                        msg: str,
                        btns: 1,
                        type: 0,
                        yes: function yes() {
                            parent.layer.close(error_alert);
                            mySlotMachine.showLastResult();
                        }
                    }
                });
            } else {
                //单次和连续的首次(还没摇奖)失败则什么都不改动保留用户所有投注
                mySlotMachine.halfClearParams();
                parent.layer.alert(str, { icon: 2 });
            }
        }
    },

    //启动老虎机事件
    startHandleEvent: function startHandleEvent(number) {

        //Ajax 获取开奖号，赋予轮盘
        var code = String(number).split("");

        //转，多少圈停止
        var runTimes = 15;

        var machine1 = $("#machine1").slotMachine({
            active: 0,
            delay: mySlotMachine.minRunTime, //动画时间，所有图案滚动一圈所用的时间，越小越快
            spins: mySlotMachine.runMethod,
            direction: 'down'
        });

        var machine2 = $("#machine2").slotMachine({
            active: 0,
            delay: mySlotMachine.minRunTime + mySlotMachine.delayStopTime,
            spins: mySlotMachine.runMethod,
            direction: 'down'
        });

        var machine3 = $("#machine3").slotMachine({
            active: 0,
            delay: mySlotMachine.minRunTime + mySlotMachine.delayStopTime * 2,
            spins: mySlotMachine.runMethod,
            direction: 'down'

        });

        var machine4 = $("#machine4").slotMachine({
            active: 0,
            delay: mySlotMachine.minRunTime + mySlotMachine.delayStopTime * 3,
            spins: mySlotMachine.runMethod,
            direction: 'down'
        });

        var machine5 = $("#machine5").slotMachine({
            active: 0,
            delay: mySlotMachine.minRunTime + mySlotMachine.delayStopTime * 4,
            spins: mySlotMachine.runMethod,
            direction: 'down'
        });

        machine1.setRandomize(function () {
            return code[0];
        });
        machine2.setRandomize(function () {
            return code[1];
        });
        machine3.setRandomize(function () {
            return code[2];
        });
        machine4.setRandomize(function () {
            return code[3];
        });
        machine5.setRandomize(function () {
            return code[4];
        });

        machine1.shuffle(runTimes, mySlotMachine.onComplete);
        machine2.shuffle(runTimes, mySlotMachine.onComplete);
        machine3.shuffle(runTimes, mySlotMachine.onComplete);
        machine4.shuffle(runTimes, mySlotMachine.onComplete);
        machine5.shuffle(runTimes, mySlotMachine.onComplete);
    }

};

(function ($) {
    $.init = function (settings) {

        //检查传过来的参数的正确性
        var verifyParams = function verifyParams() {
            var flag = 0;
            if (settings.lotteryId == undefined || !is_numeric(settings.lotteryId) || settings.lotteryId <= 0) {
                flag = -1;
            } else if (settings.lotteryName == undefined || settings.lotteryName == '') {
                flag = -2;
            } else if (settings.lotteryType == undefined || !is_numeric(settings.lotteryType) || $.inArray(settings.lotteryType, [1, 2, 4, 5, 6, 7]) == -1) {
                flag = -3;
            } else if (settings.methods == undefined || !$.isArray(settings.methods) || settings.methods.length == 0) {
                flag = -4;
            } else if (settings.minRebateGaps == undefined || !$.isArray(settings.minRebateGaps) || settings.minRebateGaps.length == 0) {
                flag = -6;
            }
            // else if (settings.rebate == undefined || !is_numeric(settings.rebate) || settings.rebate < 0 || settings.rebate > 0.178 ) {
            //     flag = -7;
            // }
            else if (settings.defaultMode == undefined || !is_numeric(settings.defaultMode) || !$.inArray(settings.defaultMode, [1, 0.1, 0.01]) == -1) {
                    flag = -8;
                } else if (settings.defaultRebate == undefined || !is_numeric(settings.defaultRebate) || settings.defaultRebate < 0 || settings.defaultRebate > settings.rebate) {
                    flag = -9;
                } else if (settings.maxCombPrize == undefined || !is_numeric(settings.maxCombPrize) || settings.maxCombPrize <= 0) {
                    flag = -10;
                }

            if (flag < 0) {
                console.info('参数错误：flag=' + flag);
            }
            return flag == 0;
        };

        var ps = $.extend({
            counter: 0,
            //应传过来的设置
            lotteryId: 1,
            lotteryName: 'CQSSC',
            lotteryType: 1, //采种类型
            //startIssueInfo: {issue_id: '11444', issue:'20130131-080', 'end_time': '2013/01/31 19:14:10', 'input_time': '2013/01/31 19:14:20'},
            methods: [],
            maxCombPrize: 0, //全包奖金
            openedIssues: [], //已开奖奖期
            minRebateGaps: [{
                from: 0,
                to: 0.12,
                gap: 0.005
            }, {
                from: 0.12,
                to: 0.125,
                gap: 0.001
            }],
            rebate: 0.123, //用户的返点
            defaultMode: 1, //1,0.1,0.01
            defaultRebate: 0.123, //默认选中的返点
            missHot: {
                miss: [],
                hot: []
            }, //上期开奖冷热数据
            halt: function halt(msg) {
                //致命错误处理
                alert(msg + '!');
                throw msg;
            },
            //运行时变量
            prizeRate: 0, //返奖率
            curIssueInfo: {}, //当前奖期{ issue_id="15712", issue="20130201-070", end_time="2013-02-01 17:48:30", input_time=2013-02-01 17:50:30"}
            lastIssueInfo: {}, //上一期{ issue_id="15712", issue="20130201-070", code="96983"}
            curMode: 0, //当前选择的元角分模式
            //curRebate: 0,       //当前选择的返点值=ps.rebateGapList[ps.curPrizeIndex].rebate，这里不再需要
            curMethod: {}, //当前选择的玩法
            curProjects: [], //当前投注栏内容
            nextProjectCounter: 0, //投注栏计数器
            curPrizeIndex: -1, //当前选择的返点在rebateGapList数组的下标
            rebateGapList: [], //计算出来的滑动奖金列表
            todayDrawList: [], //今天已开奖奖期
            curServerTime: '', //当前服务器时间
            curRemainTime: 0, //当前期剩余秒数
            remainTimer: {}, //倒数计时器
            curWaitOpenTime: 0, //当前等待开奖秒数
            waitOpenTimer: {}, //等待开奖计时器
            getLastOpenTime: 0, //等待开奖的循环时间计时
            getLastOpenTimer: {}, //得到上一期开奖结果计时器
            canBuy: false, //当前状态是否允许游戏
            traceMethodPrize: 0, //可利润率追号时，传回该玩法奖金
            tracePrizeLimit: 0, //购买奖金限额
            canTraceIssues: [] //可追号的期号列表
        }, settings);

        var helper = {
            SXBD: {
                0: 1,
                1: 1,
                2: 2,
                3: 3,
                4: 4,
                5: 5,
                6: 7,
                7: 8,
                8: 10,
                9: 12,
                10: 13,
                11: 14,
                12: 15,
                13: 15,
                14: 15,
                15: 15,
                16: 14,
                17: 13,
                18: 12,
                19: 10,
                20: 8,
                21: 7,
                22: 5,
                23: 4,
                24: 3,
                25: 2,
                26: 1,
                27: 1
            },
            EXBD: {
                0: 1,
                1: 1,
                2: 2,
                3: 2,
                4: 3,
                5: 3,
                6: 4,
                7: 4,
                8: 5,
                9: 5,
                10: 5,
                11: 4,
                12: 4,
                13: 3,
                14: 3,
                15: 2,
                16: 2,
                17: 1,
                18: 1
            },
            SXHZ: {
                0: 1,
                1: 3,
                2: 6,
                3: 10,
                4: 15,
                5: 21,
                6: 28,
                7: 36,
                8: 45,
                9: 55,
                10: 63,
                11: 69,
                12: 73,
                13: 75,
                14: 75,
                15: 73,
                16: 69,
                17: 63,
                18: 55,
                19: 45,
                20: 36,
                21: 28,
                22: 21,
                23: 15,
                24: 10,
                25: 6,
                26: 3,
                27: 1
            },
            EXHZ: {
                0: 1,
                1: 2,
                2: 3,
                3: 4,
                4: 5,
                5: 6,
                6: 7,
                7: 8,
                8: 9,
                9: 10,
                10: 9,
                11: 8,
                12: 7,
                13: 6,
                14: 5,
                15: 4,
                16: 3,
                17: 2,
                18: 1
            },
            SXZXHZ: {
                1: 1,
                2: 2,
                3: 2,
                4: 4,
                5: 5,
                6: 6,
                7: 8,
                8: 10,
                9: 11,
                10: 13,
                11: 14,
                12: 14,
                13: 15,
                14: 15,
                15: 14,
                16: 14,
                17: 13,
                18: 11,
                19: 10,
                20: 8,
                21: 6,
                22: 5,
                23: 4,
                24: 2,
                25: 2,
                26: 1
            },
            pokerNumMaps: {
                A: 1,
                2: 2,
                3: 3,
                4: 4,
                5: 5,
                6: 6,
                7: 7,
                8: 8,
                9: 9,
                T: 10,
                J: 11,
                Q: 12,
                K: 13
            },
            factorial: function factorial(n) {
                if (n <= 1) {
                    return 1;
                } else {
                    return n * helper.factorial(n - 1);
                }
            },
            /**
             * 提取issue的期号 By Davy
             * issue 有如下类型：20150403-001 20150615-01     2015040
             * 逻辑：有'-'的取其后所有字符,没有的取最后三位
             */
            getNumByIssue: function getNumByIssue(issue) {
                if (issue.length == 0) {
                    return false;
                }
                var pos = issue.indexOf("-");
                if (pos != -1) {
                    return issue.substr(pos + 1);
                } else {
                    return issue.substr(issue.length - 3);
                }
            },
            expandLotto: function expandLotto($nums) {
                var result = [];
                var tempVars = [];
                var oneAreaIsEmpty = 0;
                $.each($nums, function (k, v) {
                    if ($.trim(v) == "") {
                        oneAreaIsEmpty = 1;
                        return;
                    }
                    var tmp = v.split("_");
                    tmp.sort();
                    tempVars.push(tmp);
                });
                if (oneAreaIsEmpty) {
                    return [];
                }
                var i, j, k, L, m;
                switch ($nums.length) {
                    case 2:
                        for (i = 0; i < tempVars[0].length; i++) {
                            for (j = 0; j < tempVars[1].length; j++) {
                                result.push(tempVars[0][i] + " " + tempVars[1][j]);
                            }
                        }
                        break;
                    case 3:
                        for (i = 0; i < tempVars[0].length; i++) {
                            for (j = 0; j < tempVars[1].length; j++) {
                                for (k = 0; k < tempVars[2].length; k++) {
                                    result.push(tempVars[0][i] + " " + tempVars[1][j] + " " + tempVars[2][k]);
                                }
                            }
                        }
                        break;
                    case 5:
                        for (i = 0; i < tempVars[0].length; i++) {
                            for (j = 0; j < tempVars[1].length; j++) {
                                for (k = 0; k < tempVars[2].length; k++) {
                                    for (L = 0; L < tempVars[3].length; L++) {
                                        for (m = 0; m < tempVars[4].length; m++) {
                                            result.push(tempVars[0][i] + " " + tempVars[1][j] + " " + tempVars[2][k] + " " + tempVars[2][L] + " " + tempVars[2][m]);
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        throw "unkown expand";
                        break;
                }
                var $finalResult = [];
                $.each(result, function (k, v) {
                    var $parts = v.split(" ");
                    var tmp = array_unique($parts);
                    if (tmp.length == $parts.length) {
                        $finalResult.push(v);
                    }
                });
                return $finalResult;
            }
        };

        var initModesBar = function initModesBar() {
            var tmpMode = 1;
            var mod = parseFloat(getCookie("mod_" + ps.lotteryId));
            $.each([1, 0.1, 0.01], function (k, v) {
                if (v == mod) {
                    tmpMode = v;
                }
            });
            ps.curMode = tmpMode;

            //元角分点击事件
            $("#yuan").click(function () {
                $("#yuan").removeClass().addClass("yuan");
                $("#jiao").removeClass().addClass("jiao_off");
                $("#fen").removeClass().addClass("fen_off");
                $("#modesDIV").val(1);
                modesBar.modesBtn_Click();
            });
            $("#jiao").click(function () {
                $("#jiao").removeClass().addClass("jiao");
                $("#yuan").removeClass().addClass("yuan_off");
                $("#fen").removeClass().addClass("fen_off");
                $("#modesDIV").val(0.1);
                modesBar.modesBtn_Click();
            });
            $("#fen").click(function () {
                $("#fen").removeClass().addClass("fen");
                $("#jiao").removeClass().addClass("jiao_off");
                $("#yuan").removeClass().addClass("yuan_off");
                $("#modesDIV").val(0.01);
                modesBar.modesBtn_Click();
            });

            switch (ps.curMode) {
                case 1:
                    $("#yuan").click();
                    break;
                case 0.1:
                    $("#jiao").click();
                    break;
                case 0.01:
                    $("#fen").click();
                    break;
            }
            //$('<option value="1">元</option><option value="0.1">角</option><option value="0.01">分</option>').prependTo("#modesDIV");
            //$("#modesDIV").change(modesBar.modesBtn_Click);
            //$("#modesDIV").val(ps.curMode);
        };

        var modesBar = {
            //点击模式按钮事件
            modesBtn_Click: function modesBtn_Click() {
                var curModeSpan = $("#modesDIV").val();
                if ($('#projectList').children('li').length > 0) {
                    layer.confirm('切换元角分模式将影响您现有投注项，是否继续？', { icon: 7 }, function (i) {
                        //更新当前选择的模式
                        ps.curMode = curModeSpan;
                        //重新计算投注区金额
                        buyBar.updateTotalSingle();
                        //重置所有小球为未选择的状态
                        //ballBar.reset();
                        //更新当前已选中小球金额
                        if ($('#betCount').text() != 0) {
                            buyBar.updateSingle($('#betCount').text());
                        }
                        //保存所选模式
                        modesBar.saveLastModes();
                        prizeBar.showPirze(); //每点一下应更新对应的玩法
                        layer.close(i);
                    }, function (i) {
                        switch (ps.curMode) {
                            case '1':
                                $("#yuan").removeClass().addClass("yuan");
                                $("#jiao").removeClass().addClass("jiao_off");
                                $("#fen").removeClass().addClass("fen_off");
                                break;
                            case '0.1':
                                $("#yuan").removeClass().addClass("yuan_off");
                                $("#jiao").removeClass().addClass("jiao");
                                $("#fen").removeClass().addClass("fen_off");
                                break;
                            case '0.01':
                                $("#yuan").removeClass().addClass("yuan_off");
                                $("#jiao").removeClass().addClass("jiao_off");
                                $("#fen").removeClass().addClass("fen");
                                break;
                        }
                        $("#modesDIV").val(ps.curMode);
                    });
                } else {
                    //更新当前选择的模式
                    ps.curMode = curModeSpan;
                    //更新当前已选中小球金额
                    if ($('#betCount').text() != 0) {
                        buyBar.updateSingle($('#betCount').text());
                    }
                    //console.info("您选择了 " + $(this).find(':visible').text() + "模式,ps.curMode="+ps.curMode);
                    //加上选择样式
                    //curModeSpan.parent().children().removeClass('colorRed').filter('[mode=' + ps.curMode + ']').addClass('colorRed');
                    //重置所有小球为未选择的状态
                    //ballBar.reset();
                    //保存所选模式
                    modesBar.saveLastModes();
                    prizeBar.showPirze(); //每点一下应更新对应的玩法
                }
            },
            saveLastModes: function saveLastModes() {
                //目前是保存到cookie里面
                setCookie('mod_' + ps.lotteryId, ps.curMode, 30 * 86400);
            }
        };

        //1.2 滑动奖金栏
        var initPrizeBar = function initPrizeBar() {
            ps.rebateGapList = prizeBar.generateGapList();
            // ps.curPrizeIndex = 0;
            // var reb = getCookie("reb_" + ps.lotteryId);
            // $.each(ps.rebateGapList,
            //         function(k, v) {
            //             if (reb == v.rebate) {
            //                 ps.curPrizeIndex = k;
            //             }
            //         });
            // if (ps.curPrizeIndex == undefined) {
            //     ps.halt("initPrizeBar failed");
            // }
            if (ps.rebateGapList.length == 0) {
                ps.halt("initPrizeBar failed");
            }
            // if(ps.lotteryType == 1){//如果是SSC 初始化时把大于1950的干掉
            //     $.each(ps.rebateGapList,
            //         function(k, v) {
            //             if(v.prize > 1950){
            //                 ps.rebateGapList.splice(k, 1);
            //             }
            //         });
            // }
            ps.curPrizeIndex = ps.rebateGapList.length - 1;
            prizeBar.showPirze();
            $('#curPrizeSpan').change(prizeBar.changePrize);
        };

        //奖金滑动事件处理
        var prizeBar = {
            changePrize: function changePrize() {
                // if (ps.curMethod.name == 'YMBDW' || ps.curMethod.name == 'QSYMBDW' || ps.curMethod.name == 'ZSYMBDW' || ps.curMethod.name == 'SXYMBDW' || ps.curMethod.name == 'WXYMBDW') {
                //     if (ps.rebateGapList[$("#curPrizeSpan").val()].prize > 1900) {
                //             var sscmsg;
                //             var maxPrize = 0;
                //             for (var gap in ps.rebateGapList) {
                //                 if (ps.rebateGapList[gap].prize <= 1900 && ps.rebateGapList[gap].prize > maxPrize) {
                //                     ps.curPrizeIndex = gap;
                //                     maxPrize = ps.rebateGapList[gap].prize;
                //                 }
                //             }
                //             var prizeOptions = $("#curPrizeSpan option").eq(ps.curPrizeIndex).html().split("/");
                //             // switch (ps.curMethod.name){
                //             //     case 'YMBDW':
                //             //     case 'QSYMBDW':
                //             //     case 'ZSYMBDW':
                //             //         sscmsg = '一码不定位最大奖金是￥' + prizeOptions[0] + '，不能再往上调节';
                //             //         break;
                //             //     case 'SXYMBDW':
                //             //         sscmsg = '四星一码不定位最大奖金是￥' + prizeOptions[0] + '，不能再往上调节';
                //             //         break;
                //             //     case 'WXYMBDW':
                //             //         sscmsg = '五星一码不定位最大奖金是￥' + prizeOptions[0] + '，不能再往上调节';
                //             //         break;
                //             // }
                //             console.log(ps.curMethod)
                //             console.log(ps.lotteryId)
                //             $("#curPrizeSpan").val(ps.curPrizeIndex);
                //             // var curIndex = ps.curPrizeIndex;
                //         // if (ps.lotteryId == 15) {
                //         //     var alert_msg = layer.open({
                //         //         closeBtn: [0, false],
                //         //         dialog: {
                //         //             msg: sscmsg,
                //         //             btns: 1,
                //         //             type: 0,
                //         //             yes: function() {
                //         //                 parent.layer.close(alert_msg);
                //         //                 $("#curPrizeSpan").val(curIndex);
                //         //                 $('#rebateValue').html($('#curPrizeSpan option').eq(curIndex).html());
                //         //                 $('#selectRebate').slider('option', 'value', curIndex);
                //         //                     }
                //         //                 }
                //         //     });
                //         // } else {//预留其他彩种秒秒彩
                //         //     parent.layer.alert(sscmsg,{icon:7});
                //         // }

                //         return;
                //     }
                // }

                //150630 仅时时采才判断高奖金模式 其他采种不需要判断
                // if (ps.lotteryType == 1 && ps.rebateGapList[$("#curPrizeSpan").val()].prize > 1950) {
                //     parent.layer.alert('您可选择的最大返点为1950模式',{icon:7});
                //     $("#curPrizeSpan").val(ps.curPrizeIndex);
                //     return;
                // }
                if ($.isEmptyObject(ps.curMethod) !== true) {
                    if (ps.curMethod.description.indexOf("@P@") > 0) {
                        //变更相应的玩法奖金说明
                        var curPrize = $("#curPrizeSpan option:selected").text().split("/");
                        var newDescription = '';
                        var descriptions = ps.curMethod.description.split("@P@");
                        for (var i = 1; i < descriptions.length; i++) {
                            if (ps.curMethod.prize[i] != undefined) {
                                //以避免后台人员录入通配符错误导致程序错误
                                newDescription += descriptions[i - 1] + Math.round(ps.curMethod.prize[i] * curPrize[0] / ps.curMethod.prize[1] * 10000) / 10000 + "元";
                            }
                        }
                        newDescription += descriptions[descriptions.length - 1];
                        $("#methodDesc").text(newDescription);
                    }
                }
                ps.curPrizeIndex = $("#curPrizeSpan").val();
                prizeBar.saveLastPrize();
                buyBar.updateTotalSingle();
            },
            generateGapList: function generateGapList() {
                var result = [];
                $.each(ps.minRebateGaps, function (k, v) {
                    v.from = parseFloat(v.from);
                    v.to = parseFloat(v.to);
                    v.gap = parseFloat(v.gap);
                    if (ps.rebate > v.to) {
                        for (var i = v.from; i <= v.to; i += v.gap) {
                            result.push(parseFloat(number_format(i, 3)));
                        }
                    } else {
                        for (i = v.from; i < v.to && i < ps.rebate; i += v.gap) {
                            result.push(parseFloat(number_format(i, 3)));
                        }
                        result.push(parseFloat(number_format(ps.rebate, 3)));
                    }
                });
                result = array_unique(result);
                var result2 = [];
                $.each(result, function (k, v) {
                    if (ps.lotteryType == 7) {
                        var prize = Math.floor(ps.maxCombPrize / 24 * (ps.prizeRate + v), 0);
                    } else {
                        var prize = round(ps.maxCombPrize * (ps.prizeRate + v), 0);
                    }

                    result2.push({
                        rebate: round(ps.rebate - v, 3),
                        prize: prize
                    });
                });

                return result2;
            },
            //显示当前奖金
            showPirze: function showPirze() {
                if ($.isEmptyObject(ps.curMethod) !== true) {
                    $('#curPrizeSpan').empty();
                    $.each(ps.rebateGapList, function (k, v) {
                        var selectPrize = round(ps.curMode * ps.curMethod.prize[1] * (ps.prizeRate + ps.rebate - ps.rebateGapList[k].rebate) / ps.prizeRate, 2);
                        var selectRebate = number_format(parseFloat(ps.rebateGapList[k].rebate) * 100, 1);

                        $('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize + '/' + selectRebate + '%</option>');
                    });
                    $("#curPrizeSpan").val(ps.curPrizeIndex);
                    $("#gamesPrize").html('赔率：' + $('#curPrizeSpan').find("option:selected").text().split("/")[0]);

                    $('#rebateValue').html($('#curPrizeSpan option:selected').html().split('/')[1]);
                    prizeBar.changePrize();
                    //var realPrize = round(ps.curMode * ps.curMethod.prize * (ps.prizeRate + ps.rebate - ps.rebateGapList[ps.curPrizeIndex].rebate) / ps.prizeRate, 2);
                    //$("#curPrizeSpan").text(realPrize + "/" + number_format(parseFloat(ps.rebateGapList[ps.curPrizeIndex].rebate) * 100, 1));
                    // if(ps.lotteryId == 15) {    //初始化滑动块
                    // $('#selectRebate').slider({
                    //      range: 'min',
                    //      min: 0,
                    //      max: ($('#curPrizeSpan option').length -1),
                    //      value: $('#curPrizeSpan option:selected').index(),
                    //      slide: function(event, ui){
                    //          $('#curPrizeSpan option').removeAttr('selected');
                    //          $('#curPrizeSpan option').eq(ui.value).attr('selected', true);
                    //          $('#rebateValue').html($('#curPrizeSpan option').eq(ui.value).html());
                    //          $('#curPrizeSpan').change();
                    //          ps.curPrizeIndex = ui.value;
                    //      }
                    //  });
                    // }
                }
            },
            //保存当前奖金设置
            saveLastPrize: function saveLastPrize() {
                setCookie("reb_" + ps.lotteryId, ps.rebateGapList[ps.curPrizeIndex].rebate, 30 * 86400);
            }
        };

        //3.玩法相关
        var initMethodBar = function initMethodBar() {
            /*
             * <li id="methodGroup_0"><label>后一</label>
             <ul id="method_0" class="methodPopStyle" style="display: none;">
             <li id="method_0_1">后一直选</li>
             <li id="method_0_2">五星选</li>
             </ul>
             </li>
             */

            $.each(ps.methods, function (i, n) {
                if (n.childs.length == 1) {
                    $('<li class="methodGroupLI" id="methodGroup_' + i + '"><label>' + n.mg_name + "</label></li>").click(methodBar.methodGroup_Click).appendTo("#methodGroupContainer");
                } else {
                    $('<li class="methodGroupLI" id="methodGroup_' + i + '"><label>' + n.mg_name + "</label></li>").click(methodBar.methodGroup_Click).hover(methodBar.methodGroup_hoverOver, methodBar.methodGroup_hoverOut).appendTo("#methodGroupContainer");
                }

                var dl1 = '<dl class="fix"><dt>直选：</dt>';
                var dl2 = '<dl class="fix"><dt>组选：</dt>';
                var dl3 = '<dl class="fix"><dt>趣味：</dt>';
                var dl4 = '<dl class="fix"><dt>特殊：</dt>';
                var dl5 = '<dl class="fix"><dt>定位：</dt>';
                var dl6 = '<dl class="fix"><dt>不定位：</dt>';
                var dl7 = '<dl class="fix"><dt>任选二：</dt>';
                var dl8 = '<dl class="fix"><dt>任选三：</dt>';
                var dl9 = '<dl class="fix"><dt>任选四：</dt>';
                var dl0 = '<dl class="fix"><dt>其他：</dt>';

                var methodStr = '';
                var div = $('<div id="method_' + i + '"></div>').addClass('methodPopStyle').hide();
                $.each(n.childs, function (ii, nn) {
                    var dd = '<dd class="method" name="' + nn.name + '" id="method_' + i + "_" + ii + '">' + nn.cname + '</dd>';
                    var ddSon = '';
                    // nn.method_property 1直选2组选3趣味4特殊5定位6不定位7任选二8任选三9任选四0其他
                    if (nn.method_property == 1) {
                        dl1 += dd;
                    } else if (nn.method_property == 2) {
                        dl2 += dd;
                    } else if (nn.method_property == 3) {
                        dl3 += dd;
                    } else if (nn.method_property == 4) {
                        dl4 += dd;
                    } else if (nn.method_property == 5) {
                        dl5 += dd;
                    } else if (nn.method_property == 6) {
                        dl6 += dd;
                    } else if (nn.method_property == 7) {
                        dl7 += dd;
                    } else if (nn.method_property == 8) {
                        dl8 += dd;
                    } else if (nn.method_property == 9) {
                        dl9 += dd;
                    } else if (nn.method_property == 0) {
                        dl0 += dd;
                    }
                });
                dl1 += '</dl>';dl2 += '</dl>';dl3 += '</dl>';dl4 += '</dl>';dl5 += '</dl>';dl6 += '</dl>';dl7 += '</dl>';dl8 += '</dl>';dl9 += '</dl>';dl0 += '</dl>';
                if (dl1.indexOf('</dd>') < 0) {
                    dl1 = '';
                }
                if (dl2.indexOf('</dd>') < 0) {
                    dl2 = '';
                }
                if (dl3.indexOf('</dd>') < 0) {
                    dl3 = '';
                }
                if (dl4.indexOf('</dd>') < 0) {
                    dl4 = '';
                }
                if (dl5.indexOf('</dd>') < 0) {
                    dl5 = '';
                }
                if (dl6.indexOf('</dd>') < 0) {
                    dl6 = '';
                }
                if (dl7.indexOf('</dd>') < 0) {
                    dl7 = '';
                }
                if (dl8.indexOf('</dd>') < 0) {
                    dl8 = '';
                }
                if (dl9.indexOf('</dd>') < 0) {
                    dl9 = '';
                }
                if (dl0.indexOf('</dd>') < 0) {
                    dl0 = '';
                }
                $(dl1 + dl2 + dl3 + dl4 + dl5 + dl6 + dl7 + dl8 + dl9 + dl0).appendTo(div);
                $.each(n.childs, function (ii, nn) {
                    $('#method_' + i + "_" + ii).live("click", nn, methodBar.method_Click).hover(methodBar.method_hoverOver, methodBar.methodGroup_hoverOut);
                });
                $("#methodGroup_" + i).append(div);
            });
            $(".subTopBar").mouseleave(function () {
                $(this).find("div").removeClass("methodGroup_hover");
                // /\w+_(\d+)/.test($(this).attr("id"));
                // var index = RegExp.$1;
                $(this).find("ul li .methodPopStyle").hide();
            });
            methodBar.selectDefault();
        };

        //玩法组及玩法菜单事件处理
        var methodBar = {
            methodGroup_Click: function methodGroup_Click(e) {
                $(this).addClass("methodGroup_selected").siblings().removeClass("methodGroup_selected methodGroup_hover");
                //必须这样做
                // if ($(e.target).is('label')) {
                //     $(this).find('dd:first').click();
                // };
                $(this).addClass("methodGroup_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
                $(".playNav ul .methodPopStyle").hide();
                $(this).find('.methodPopStyle').show();
            },
            methodGroup_hoverOver: function methodGroup_hoverOver() {
                $(this).addClass("methodGroup_hover");
                if ($(this).hasClass("methodGroup_selected")) {
                    $(this).find('.methodPopStyle').show();
                };
            },
            methodGroup_hoverOut: function methodGroup_hoverOut() {
                $(this).removeClass("methodGroup_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
            },
            method_hoverOver: function method_hoverOver() {
                $(this).addClass("method_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
                //$("#method_" + index).show().siblings().hide();
                //$("#method_" + index).children(":first").click();
                $(this).find('.methodPopStyle').show();
            },
            method_hoverOut: function method_hoverOut() {
                $(this).removeClass("method_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
                $(this).find('.methodPopStyle').hide();
            },
            method_Click: function method_Click(e) {
                ps.curMethod = e.data;
                $('.methodPopStyle').hide();
                //设置当前玩法前景色以示区别
                $('#methodGroupContainer').find('.method').removeClass("method_selected");
                $(this).addClass("method_selected");
                $('#curMethod').text(ps.curMethod.cname);
                //玩法提示文字
                $("#methodDesc").html(ps.curMethod.description).hide();
                //备注tips
                $("#methodTipInfo").click(function () {
                    layer.tips($("#methodDesc").html(), this, {
                        tips: [2, '#f13131'],
                        time: 0,
                        closeBtn: 0,
                        shade: [0.1, '#000'],
                        shadeClose: true
                    });
                });
                ballBar.generateBall();
                //$("input[name=missHotBtn]:checked").click();
                $("#singleInfo").show();
                buyBar.updateSingle(0);
                prizeBar.showPirze();
                if (propLen(ps.curMethod.field_def) == 0) {
                    $(".selectRandomBtn").hide();
                    ballBar.showInput();
                    $("#singleInfo").hide();
                    //                    $("#delTA").click(function() {
                    //                        $("#inputTA").val("");
                    //                    });
                    $("#selectArea").removeClass("N-selected");
                };
                var counts = propLen(ps.curMethod.field_def);
                if (counts == 2) {
                    $(".locate").css("padding", "10px 0");
                };
                if (propLen(ps.curMethod.field_def) == 3) {
                    $(".locate").css("padding", "10px 0");
                };
                if (propLen(ps.curMethod.field_def) == 4) {
                    $(".locate").css("padding", "5px 0");
                };
            },
            //默认玩法
            selectDefault: function selectDefault() {
                $("#methodGroupContainer").find("dd[name='WXDW']").click();
                $(".GameText span").html($(".methodGroupLI").find("dd[name='WXDW']").html());
                // if (ps.lotteryType == 1) {
                //     $("#methodGroupContainer").find("label:contains('定位胆')").click();
                //     $("#methodGroupContainer").find("label:contains('P3直选')").click();
                // } else if (ps.lotteryType == 2) {
                //     var ob = $("#methodGroupContainer").find("label:contains('任选')");
                //     ob.click();
                //     // $("#methodGroupContainer").find(".tabGray").attr("id");
                //     // \w+_(\d+)/.test($("#methodGroupContainer").find(".tabGray").attr("id"));
                //     // $("#method_" + RegExp.$1).children(":contains('任选五中五')").click();
                //     ob.siblings('ul').children(":contains('任选五中五')").click();
                // } else if (ps.lotteryType == 4) {
                //     $("#methodGroupContainer").find("label:contains('直选')").click();
                // } else if (ps.lotteryType == 6) {
                //     $("#methodGroupContainer").find("label:contains('和值')").click();
                // } else if (ps.lotteryType == 7) {
                //     $("#methodGroupContainer").find("label:contains('包选')").click();
                // }
                $('.methodPopStyle').hide();
            }
        };
        //3.1投注球事件处理 ball_Selected为选中后的样式
        var ballBar = {
            reset: function reset() {
                if ($.isEmptyObject(ps.curMethod.field_def)) {
                    return;
                }
                $.each(ps.curMethod.field_def, function (i, prop) {
                    $('#field_' + i).children().removeClass('ball_Selected');
                });
                if (ps.lotteryId == 15) {
                    $(".all_button_off").removeClass("all_button_on");
                    $(".big_button_off").removeClass("big_button_on");
                    $(".small_button_off").removeClass("small_button_on");
                    $(".single_button_off").removeClass("single_button_on");
                    $(".double_button_off").removeClass("double_button_on");
                    $(".clear_button_off").removeClass("clear_button_on");
                }
                buyBar.updateSingle(0);
            },
            showInput: function showInput() {
                $("#selectArea").children().remove();
                if (ps.lotteryType == 1) {
                    var str = '<div class="manualInput"><div class="manualInputTop fix"><ul><li><p class="NumbBasket">号码篮</p><textarea cols="" rows="" class="inputTA" id="inputTA"></textarea></li><li id="delTA" class="delTA custBtnStyle">清空号码</li></div><div class="manualInputBottom DisplayNone fix"><li class="inputTip1"><p>提示：</p><p>请把您已有的大底号码复制或者输入到下边文本框中。</p><p>每注号码之间用 空格 或者换行符 隔开,</p><p>每注号码每位之间不使用任何分隔符。仅支持单式。 </p></li><li class="inputTip2"><p>例如：</p><p id="inputExample">';
                    var tmp = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
                    if (ps.curMethod.name == "SXHHZX" || ps.curMethod.name == "ZSHHZX" || ps.curMethod.name == "QSHHZX") {
                        str += "123 112<br></p></li></ul></div></div>";
                    } else {
                        str += tmp.slice(0, propLen(ps.curMethod.field_def)).join("") + " " + tmp.slice(propLen(ps.curMethod.field_def), propLen(ps.curMethod.field_def) + propLen(ps.curMethod.field_def)).join("") + "<br></p></li></div></div>";
                    }
                } else if (ps.lotteryType == 2) {
                    var str = '<div class="manualInput"><div class="manualInputTop fix"><ul><li><p class="NumbBasket">号码篮</p><textarea cols="" rows="" class="inputTA" id="inputTA"></textarea></li><li id="delTA" class="delTA custBtnStyle">清空号码</li></div><div class="manualInputBottom DisplayNone fix"><li class="inputTip1"><p>提示：</p><p>请把您已有的大底号码复制或者输入到下边文本框中。</p><p>每注号码之间必须用 换行  隔开,</p><p>每注号码每位之间必须使用 空格 作为分隔。仅支持单式。 </p></li><li class="inputTip2"><p>例如：</p><p id="inputExample">';
                    var tmp = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11"];
                    var num;
                    if (/SDRX(\d+)/.test(ps.curMethod.name)) {
                        num = parseInt(RegExp.$1);
                    } else if (ps.curMethod.name == 'SDQSZUX') {
                        num = 3;
                    } else if (ps.curMethod.name == 'SDQEZUX') {
                        num = 2;
                    } else {
                        num = propLen(ps.curMethod.field_def);
                    }
                    str += tmp.slice(0, num).join(" ") + "<br/>" + tmp.slice(1, 1 + num).join(" ") + "<br></p></li></ul></div></div>";
                } else if (ps.lotteryType == 4) {
                    var str = '<div class="manualInput"><div class="manualInputTop fix"><ul><li><p class="NumbBasket">号码篮</p><textarea cols="" rows="" class="inputTA" id="inputTA"></textarea></li><li id="delTA" class="delTA custBtnStyle">清空号码</li></div><div class="manualInputBottom DisplayNone fix"><li class="inputTip1"><p>提示：</p><p>请把您已有的大底号码复制或者输入到下边文本框中。</p><p>每注号码之间用 空格 或者换行符 隔开,</p><p>每注号码每位之间不使用任何分隔符。仅支持单式。 </p></li><li class="inputTip2"><p>例如：</p><p id="inputExample">';
                    var tmp = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
                    if (ps.curMethod.name == "SXHHZX") {
                        str += "123 112<br></p></li></ul></div></div>";
                    } else {
                        str += tmp.slice(0, propLen(ps.curMethod.field_def)).join("") + " " + tmp.slice(propLen(ps.curMethod.field_def), propLen(ps.curMethod.field_def) + propLen(ps.curMethod.field_def)).join("") + "<br></p></li></ul></div></div>";
                    }
                }
                $(str).appendTo("#selectArea");
                $("#delTA").click(function () {
                    $("#inputTA").val("");
                    //$("#selectCodeBtn").removeClass("selectCodeBtn_selected");
                });
                $("#inputTA").keyup(function () {
                    if ($("#inputTA").val() != '') {
                        $("#selectCodeBtn").addClass('selectCodeBtn_selected');
                    }
                });
            },
            //显示投注球
            generateBall: function generateBall() {
                $("#selectArea").children().remove();
                var filterBtn = '<li class="Quick">';
                filterBtn += '<input type="button" class="all_button_off" value="全">';
                filterBtn += '<input type="button" class="big_button_off" value="大">';
                filterBtn += '<input type="button" class="small_button_off" value="小">';
                filterBtn += '<input type="button" class="single_button_off" value="单">';
                filterBtn += '<input type="button" class="double_button_off" value="双">';
                filterBtn += '<input type="button" class="clear_button_off" value="清">';
                filterBtn += '</li>';
                $.each(ps.curMethod.field_def, function (i, prop) {
                    //注：i从1开始
                    var numList = prop.nums.split(" ");
                    var ballStr = '',
                        hzbdStr = "";
                    $.each(numList, function (ii, nn) {
                        switch (ps.curMethod.name) {
                            case "SXBD":
                            case "ZSBD":
                            case "QSBD":
                                ballStr += '<li>' + nn + '</li>'; //<span class="HZBD">' + helper.SXBD[ii] + "</span>"
                                hzbdStr += '<li>' + helper.SXBD[ii] + '</li>';
                                break;
                            case "SXHZ":
                            case "ZSHZ":
                            case "QSHZ":
                                ballStr += '<li>' + nn + '</li>';
                                hzbdStr += '<li>' + helper.SXHZ[ii] + '</li>';
                                break;
                            case "EXBD":
                            case "QEBD":
                                ballStr += '<li>' + nn + '</li>';
                                hzbdStr += '<li>' + helper.EXBD[ii] + '</li>';
                                break;
                            case "EXHZ":
                            case "QEHZ":
                                ballStr += '<li>' + nn + '</li>';
                                hzbdStr += '<li>' + helper.EXHZ[ii] + '</li>';
                                break;
                            case 'SXZXHZ':
                            case 'QSZXHZ':
                                ballStr += '<li>' + nn + '</li>';
                                hzbdStr += '<li>' + helper.SXZXHZ[ii + 1] + '</li>';
                                break;
                            case 'PKSZ': //扑克.顺子
                            case 'PKBZ':
                                //扑克.豹子
                                var tmp = nn.split("");
                                ballStr += '<li><div zhuma="' + nn + '"><span><i>' + drawBar.translateT(tmp[0]) + '</i></span><span class="p2"><i>' + drawBar.translateT(tmp[1]) + '</i></span><span class="cover"><i>' + drawBar.translateT(tmp[2]) + '</i><em></em></span><i class="gou"></i></div></li>';
                                break;
                            case 'PKDZ':
                                //扑克.对子
                                var tmp = nn.split("");
                                ballStr += '<li><div zhuma="' + nn + '"><span><i>' + drawBar.translateT(tmp[0]) + '</i></span><span class="cover"><i>' + drawBar.translateT(tmp[1]) + '</i><em></em></span><i class="gou"></i></div></li>';
                                break;
                            case 'PKTH':
                                //扑克.同花
                                //需要把玩法设定投注球的名称翻译成对应的class
                                var poker_trans = { "黑桃": "poker_tao", "红桃": "poker_xin", "梅花": "poker_mei", "方块": "poker_fang" };
                                ballStr += '<li class="' + eval('poker_trans.' + nn) + '"><div zhuma="' + nn + '"><em></em><i class="gou"></i></div></li>';
                                break;
                            case 'PKTHS':
                                //扑克.同花顺
                                //需要把玩法设定投注球的名称翻译成对应的class
                                var poker_trans = { "黑桃顺子": "poker_tao", "红桃顺子": "poker_xin", "梅花顺子": "poker_mei", "方块顺子": "poker_fang" };
                                ballStr += '<li class="' + eval('poker_trans.' + nn) + '"><div zhuma="' + nn + '"><em></em><span>顺子</span><i class="gou"></i></div></li>';
                                break;
                            case 'PKBX':
                                //扑克.包选
                                //需要把玩法设定投注球的名称翻译成对应的class
                                var poker_trans = { "同花顺包选": "poker_ths", "同花包选": "poker_th", "顺子包选": "poker_sz", "豹子包选": "poker_bz", "对子包选": "poker_dz" };
                                ballStr += '<li class="' + eval('poker_trans.' + nn) + '"><div zhuma="' + nn + '"><b>' + nn + '</b><p>&nbsp;</p><span class="hua"></span> <i class="gou"></i> </div></li>';
                                break;
                            case 'PKRX1': //扑克.任选1
                            case 'PKRX2': //扑克.任选2
                            case 'PKRX3': //扑克.任选3
                            case 'PKRX4': //扑克.任选4
                            case 'PKRX5': //扑克.任选5
                            case 'PKRX6':
                                //扑克.任选6
                                ballStr += '<li><div zhuma="' + nn + '">' + drawBar.translateT(nn) + '<i class="gou"></i></div></li>';
                                break;
                            default:
                                ballStr += '<li>' + nn + "</li>";
                                break;
                        }
                    });

                    var ballListName = 'ballList';
                    if (ps.lotteryType == 6 && ps.curMethod.name != 'JSHZ') {
                        ballListName = 'ballList_k3no_square';
                    }
                    if (ps.lotteryType == 6 && (ps.curMethod.name == 'JSSTTX' || ps.curMethod.name == 'JSSLTX')) {
                        ballListName = 'ballList_k3no_sttx';
                    } else if (ps.lotteryType == 7) {
                        switch (ps.curMethod.name) {
                            case 'PKSZ': //扑克.顺子
                            case 'PKBZ':
                                //扑克.豹子
                                ballListName = 'poker3';
                                break;
                            case 'PKDZ':
                                //扑克.对子
                                ballListName = 'poker3 dz';
                                break;
                            case 'PKBX': //扑克.包选
                            case 'PKTHS': //扑克.同花顺
                            case 'PKTH':
                                //扑克.同花
                                ballListName = 'pokerBig';
                                break;
                            case 'PKRX1': //扑克.任选1
                            case 'PKRX2': //扑克.任选2
                            case 'PKRX3': //扑克.任选3
                            case 'PKRX4': //扑克.任选4
                            case 'PKRX5': //扑克.任选5
                            case 'PKRX6':
                                //扑克.任选6
                                ballListName = 'pokerRx';
                                break;
                            default:
                                ballStr += '<li>' + nn + "</li>";
                                break;
                        }
                    }
                    ballStr = '<li><ul class="' + ballListName + (/(BD|HZ)$/.test(ps.curMethod.name) ? " w400" : "") + '" id=field_' + i + ">" + ballStr + "</ul></li>";
                    hzbdStr = '<li><ul class="BDHZinfo">' + hzbdStr + "</ul></li>";
                    var specialClass = "";
                    if (/(DXDS)$/.test(ps.curMethod.name)) {
                        specialClass = ' DXDS-margin-left';
                    } else if (/(SDDDS)$/.test(ps.curMethod.name)) {
                        specialClass = ' SDDDS-margin-left';
                    } else if (/(SDCZW)$/.test(ps.curMethod.name)) {
                        specialClass = ' SDCZW-margin-left';
                    }

                    if (ps.lotteryType == 6 && (ps.curMethod.name == 'JSSTTX' || ps.curMethod.name == 'JSSLTX')) {
                        $('<div class="locate" id="locate_' + i + '"><ul class="lotteryNumber k3">' + ballStr + "</ul></div>").appendTo("#selectArea");
                    } else {
                        $('<div class="locate" id="locate_' + i + '"><ul class="lotteryNumber' + specialClass + '">' + (prop.prompt ? '<li class="areaPrefix">' + prop.prompt + "</li>" : "") + ballStr + "</ul></div>").appendTo("#selectArea");
                    }

                    //特殊处理和值包点
                    // if (/(BD|HZ)$/.test(ps.curMethod.name) && ps.curMethod.name != 'JSHZ') {
                    //     $('#locate_' + i + ' .lotteryNumber').append('<li class="BDHZprompt">包含注数:</li>' + hzbdStr);
                    // }
                    //处理是否有筛选功能
                    if (prop.has_filter_btn) {
                        if (ps.lotteryType == 7) {
                            //显示：全选/清除
                            $(".lotteryNumber").first().append(filterBtn);
                            $('.allSelect').click(function () {
                                $(".lotteryNumber li ul li").addClass('ball_Selected');
                                ballBar.computeSingle();
                            });
                            $('.clearSelect').click(function () {
                                $(".lotteryNumber li ul li").removeClass('ball_Selected');
                                ballBar.computeSingle();
                            });
                        } else {
                            $("#locate_" + i).find("ul .areaPrefix").after(filterBtn);
                            if (ps.curMethod.name == "EXZUX" || ps.curMethod.name == "QEZUX") {
                                //                                $("#locate_" + i).find(".navSub :first").text(" ");
                                //                                $("#locate_" + i).find(".navSub :last").text(" ");
                                $("#locate_" + i).find(".QuickChoose").hide();
                            }

                            var jQueryListObj = $("#locate_" + i).find(".Quick input");
                            jQueryListObj.click(function () {
                                var str_arr = this.className.split('_');
                                var pre_fix = str_arr[0] + "_" + str_arr[1];

                                switch (pre_fix) {
                                    case 'all_button':
                                        $('#field_' + i).children().addClass('ball_Selected');
                                        ballBar.changeFilterButtonClass(jQueryListObj);
                                        //$(this).addClass('all_button_on');
                                        $(this).addClass("DDcolor").siblings().removeClass("DDcolor");
                                        break;
                                    case 'single_button':
                                        if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":odd").addClass('ball_Selected');
                                        } else if (ps.lotteryType == 2) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":even").addClass('ball_Selected');
                                        }
                                        ballBar.changeFilterButtonClass(jQueryListObj);
                                        //$(this).addClass('single_button_on');
                                        $(this).addClass("DDcolor").siblings().removeClass("DDcolor");
                                        break;
                                    case 'double_button':
                                        if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":even").addClass('ball_Selected');
                                        } else if (ps.lotteryType == 2) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":odd").addClass('ball_Selected');
                                        }
                                        ballBar.changeFilterButtonClass(jQueryListObj);
                                        //$(this).addClass('double_button_on');
                                        $(this).addClass("DDcolor").siblings().removeClass("DDcolor");
                                        break;
                                    case 'big_button':
                                        $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                            return idx >= 5;
                                        }).addClass('ball_Selected');
                                        ballBar.changeFilterButtonClass(jQueryListObj);
                                        //$(this).addClass('big_button_on');
                                        $(this).addClass("DDcolor").siblings().removeClass("DDcolor");
                                        break;
                                    case 'small_button':
                                        $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                            return idx < 5;
                                        }).addClass('ball_Selected');
                                        ballBar.changeFilterButtonClass(jQueryListObj);
                                        //$(this).addClass('small_button_on');
                                        $(this).addClass("DDcolor").siblings().removeClass("DDcolor");
                                        break;
                                    case 'clear_button':
                                        $('#field_' + i).children().removeClass('ball_Selected');
                                        ballBar.changeFilterButtonClass(jQueryListObj);
                                        //$(this).addClass('clear_button_on');
                                        $(this).addClass("DDcolor").siblings().removeClass("DDcolor");
                                        break;
                                }
                                ballBar.computeSingle();
                            });
                        }
                    }
                    //暂不显示遗漏冷热
                    //var result = ballBar.getAssisInfo(i);
                    var result = [];
                    if (result.length > 0) {
                        $.each(result, function (i, n) {
                            $(n).appendTo('#selectArea');
                        });
                    }
                });
                //绑定球点击事件
                $('.lotteryNumber ul>li').bind("click", ballBar.ball_Click);
            },
            changeFilterButtonClass: function changeFilterButtonClass(jQueryObjList) {
                var nameList = ['all_button_off', 'big_button_off', 'small_button_off', 'single_button_off', 'double_button_off', 'clear_button_off'];
                jQueryObjList.each(function (i) {
                    $(this).removeClass().addClass(nameList[i]);
                });
            },

            //是否显示遗漏冷热
            getAssisInfo: function getAssisInfo(field_def_idx) {
                var str = "",
                    result = [];
                if (!$.isArray(ps.missHot.miss) || !$.isArray(ps.missHot.hot)) {
                    return [];
                }
                var idx = -1;
                switch (ps.curMethod.name) {
                    case "QSZX":
                    case "QSLX":
                    case "QEZX":
                    case "QELX":
                    case "QSIZX":
                    case "WXZX":
                    case "WXLX":
                    case "WXDW":
                    case 'SXDW':
                    case "SDQSZX":
                    case "SDQEZX":
                        idx = field_def_idx - 1 + 0;
                        break;
                    case "SIXZX":
                    case "ZSZX":
                    case "ZSLX":
                        idx = field_def_idx - 1 + 1;
                        break;
                    case "SXZX":
                    case "SXLX":
                        idx = field_def_idx - 1 + 2;
                        break;
                    case "EXZX":
                    case "EXLX":
                        idx = field_def_idx - 1 + 3;
                        break;
                    case "YXZX":
                        idx = field_def_idx - 1 + 4;
                        break;
                }
                if (idx > -1) {
                    $.each(ps.missHot.miss[idx], function (i, n) {
                        str += '<li class="yiLouNum">' + n + "</li>";
                    });
                    str = '<div class="missDIV" id="missHot_' + field_def_idx + '"><ul class="missHotNumber"><li class="missHotUnit">遗漏:</li><li><ul class="yiLouList" id=yiLouList_' + field_def_idx + ">" + str + "</ul></li></ul></div>";
                    result.push(str);
                    str = "";
                    $.each(ps.missHot.hot[idx], function (i, n) {
                        str += '<li class="yiLouNum">' + n + "</li>";
                    });
                    str = '<div class="hotDIV" id="missHot_' + field_def_idx + '"><ul class="missHotNumber"><li class="missHotUnit">冷热:</li><li><ul class="yiLouList" id=yiLouList_' + field_def_idx + ">" + str + "</ul></li></ul></div>";
                    result.push(str);
                }
                return result;
            },
            ball_Click: function ball_Click(e) {
                $(this).toggleClass("ball_Selected");
                /\w+_(\d+)/.test($(this).parent().attr("id"));
                var index = RegExp.$1;
                if ($(this).parent().find(".ball_Selected").length > ps.curMethod.field_def[index].max_selected) {
                    if (ps.curMethod.field_def[index].max_selected == 1) {
                        $(this).siblings(".ball_Selected").removeClass("ball_Selected");
                    } else {
                        $(this).removeClass("ball_Selected");
                        parent.layer.alert("当前最多只能选择" + ps.curMethod.field_def[index].max_selected + "个号码", { icon: 7 });
                    }
                } else {
                    //JSETDX 江苏快三 二同号单选 特殊处理
                    if (ps.curMethod.name == 'JSETDX') {
                        $('#locate_' + (index == 1 ? 2 : 1)).find(".ballList_k3no_square li").eq(parseInt($(this).text().substr(0, 1)) - 1).removeClass("ball_Selected");
                    }

                    ballBar.computeSingle();
                }
            },
            //计算注数
            computeSingle: function computeSingle() {
                var codes = [];
                $.each(ps.curMethod.field_def, function (i, n) {
                    var tmp = "";
                    var tmp2 = ps.curMethod.field_def[i].nums.split(" ");
                    $("#field_" + i + " li").each(function (ii) {
                        if ($(this).hasClass("ball_Selected")) {
                            if (ps.lotteryType == 2 || ps.curMethod.field_def[i].max_selected > 10 || tmp2[tmp2.length - 1].length > 1) {
                                if (ps.lotteryType == 7) {
                                    //通过自定义属性取  注码
                                    //$(this).find('div').get(0).getAttribute("zhuma");
                                    tmp += $(this).find('div').attr("zhuma") + "_";
                                } else {
                                    tmp += $(this).text() + "_";
                                }
                            } else {
                                if (ps.lotteryType == 7) {
                                    //通过自定义属性取  注码
                                    tmp += $(this).find('div').attr("zhuma");
                                } else {
                                    tmp += $(this).text();
                                }
                            }
                        }
                    });
                    if (tmp.indexOf("_") == -1) {
                        codes.push(tmp);
                    } else {
                        codes.push(tmp.substr(0, tmp.length - 1));
                    }
                });
                var ob = isLegalCode(codes);
                buyBar.updateSingle(ob.singleNum);

                if (ps.curMethod.name == 'WXDW' || ps.curMethod.name == 'REZX' || ps.curMethod.name == 'RSZX' || ps.curMethod.name == 'RSIZX') {
                    //如果是REZX || RSZX
                    $.each(codes, function (k, v) {
                        if (v == '') {
                            codes[k] = '-';
                        }
                    });
                }

                var resultCode = codes.join(",");
                if (ps.curMethod.name == 'JSSTTX') {
                    resultCode = '111_222_333_444_555_666';
                } else if (ps.curMethod.name == 'JSSLTX') {
                    //如果是三连号通选(nyjah)
                    resultCode = '123_234_345_456';
                }

                return {
                    singleNum: ob.singleNum,
                    isDup: ob.isDup,
                    code: resultCode
                };
            }
        };

        //4.遗漏
        var initMissHotBar = function initMissHotBar() {
            $("input[name=missHotBtn]").click(missHotBar.missHotBtn_Click);
            $('input[name=missHotBtn][value="1"]').click();
        };
        var missHotBar = {
            missHotBtn_Click: function missHotBtn_Click() {
                if ($(this).val() == "1") {
                    $("#selectArea .missDIV").removeClass("hidden");
                    $("#selectArea .hotDIV").addClass("hidden");
                } else {
                    $("#selectArea .missDIV").addClass("hidden");
                    $("#selectArea .hotDIV").removeClass("hidden");
                }
            }
        };

        //5.投注区相关
        var initBuyBar = function initBuyBar() {
            ps.nextProjectCounter = 0;
            buyBar.removeAll();
            //$("#totalSingleInfo").prepend('总计[<span>0</span>]注 倍数<input name="multiple" id="multiple" value="1"/>');
            //////////////////  倍数事件    //////////////////
            $("#multiple").click(function () {
                this.focus();
                this.select();
            }).blur(function () {
                if (this.value == '' || this.value == 0) {
                    this.value = 1;
                    buyBar.updateTotalSingle();
                }
            }).keyup(buyBar.checkMultiple).keyup(buyBar.updateTotalSingle);
            $(".inputNumJian").click(function () {
                var multipleVal = $("#multiple").val();
                if (multipleVal == 1) {
                    $("#multiple").val(parseInt(1));
                } else {
                    $("#multiple").val(parseInt(multipleVal) - 1);
                };
                buyBar.updateTotalSingle();
            });
            $(".inputNumJia").click(function () {
                var multipleVal = $("#multiple").val();
                if (multipleVal > 5000) {
                    $("#multiple").val(parseInt(5000));
                } else {
                    $("#multiple").val(parseInt(multipleVal) + 1);
                };
                buyBar.updateTotalSingle();
            });
            $(".minus").click(function () {
                var org_value = parseInt($("#multiple").val());
                var new_value = org_value <= 1 ? 1 : org_value - 1;
                $("#multiple").val(new_value);
                $("#multiple").keyup();
                $("#multiple").keyup();
            });

            $(".plus").click(function () {
                var org_value = parseInt($("#multiple").val());
                var new_value = org_value + 1;
                $("#multiple").val(new_value);
                $("#multiple").keyup();
                $("#multiple").keyup();
            });
            //////////////////  连续开奖事件    //////////////////
            $("#open_counts").live("click", function () {
                this.focus();
                this.select();
            }).live("blur", function () {
                if (this.value == '' || this.value == 0) {
                    this.value = 1;
                }
            }).live("keyup", function () {
                //if (!/^[1-9]\d{0,2}$/.test($(this).val())) {
                this.value = this.value.replace(/^0|[^\d]/g, '');
                var maxValue = 30;
                if ($(this).val() > maxValue) {
                    parent.layer.alert("请输入正确的数值，最大为" + maxValue + "次", { icon: 7 });
                    $(this).val(maxValue);
                    return true;
                }
                return true;
            }).live("keyup", traceFunc.style1BodyMultiple_Keyup);
            //箭头点击事件
            $(".open_counts .arrow").click(function (event) {
                event.stopPropagation();
                $("#open_counts_option").show();
                $(document).live("click", function () {
                    $("#open_counts_option").hide();
                });
            });
            //选择次数事件
            $("#open_counts_option li").click(function () {
                var val = $(this).html();
                $("#open_counts").val(val);
                //$("#open_counts_option").hide();
            });

            $(".xDel").live("click", function () {
                $(this).parent().remove();
                buyBar.updateTotalSingle();
            });

            $("#clearProjectBtn").click(buyBar.removeAll);
            $("#clearProjectBtn1").click(buyBar.removeAll);
            $("#inputBtn").click(buyBar.inputBtn_Click);
            $("#selectCodeBtn").click(buyBar.selectCodeBtn_Click);
            $("#confirmBtn").click(buyBar.confirmBtn_Click);
            //老虎机手柄事件
            //$("#slotMachineButton1").click(buyBar.confirmBtn_Click);
            $("#slotMachineButton1").click(function () {
                mySlotMachine.startHandleAnimal();
                $("#confirmBtn").click();
            });

            $("#traceBtn").click(buyBar.traceBtn_Click);
            $(".selectRandomBtn").click(buyBar.selectRandomBtn_Click);
        };
        var buyBar = {
            inputBtn_Click: function inputBtn_Click() {
                if ($(this).text() == "手工录入") {
                    $(this).text("常规录入");
                    ballBar.showInput();
                    $("#singleInfo").hide();
                    $("#selectCodeBtn").addClass("selectCodeBtn_selected");
                    /*
                     $("#inputTA").mouseout(function() {
                     if ($("#inputTA").val() !== "") {
                     $("#selectCodeBtn").addClass("selectCodeBtn_selected");
                     } else {
                     $("#selectCodeBtn").removeClass("selectCodeBtn_selected");
                     }
                     });
                     */
                    $("#selectArea").removeClass("N-selected");
                    $(".selectRandomBtn").hide();
                } else {
                    $(this).text("手工录入");
                    $("#singleInfo").show();
                    ballBar.generateBall();
                    $("input[name=missHotBtn]:checked").click();
                    $("#selectArea").addClass("N-selected");
                    $("#selectCodeBtn").removeClass("selectCodeBtn_selected");
                    $(".selectRandomBtn").show();
                }
            },
            selectCodeBtn_Click: function selectCodeBtn_Click() {
                //var d = new Date();var t0 = d.getTime();
                if ($("#inputTA").length > 0) {
                    var allCodes = [];
                    var str = $.trim($("#inputTA").val());
                    if (str.length == 0) {
                        return false;
                    }
                    if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                        var arr = str.split(/\s+/);
                        //because HHZX have no field_def
                        if (ps.curMethod.name == "SXHHZX" || ps.curMethod.name == "ZSHHZX" || ps.curMethod.name == "QSHHZX") {
                            var re = eval("/^\\d{3}$/");
                        } else {
                            var re = eval("/^\\d{" + propLen(ps.curMethod.field_def) + "}$/");
                        }
                        for (var i in arr) {
                            //                            if (!re.test(arr[i])) {
                            //                                parent.layer.alert("您输入的号码有误，请重新检查输入");
                            //                                return false
                            //                            }
                            allCodes.push(arr[i].split(""));
                        }
                    } else {
                        if (ps.lotteryType == 2) {
                            var arr = str.split(/\n/);
                            var re = /^[01]\d$/;
                            for (var i in arr) {
                                arr[i] = $.trim(rtrim($.trim(arr[i]), ","));
                                var tmp = arr[i].split(" ");
                                //                                for (var i2 in tmp) {
                                //                                    if (!re.test(tmp[i2])) {
                                //                                        parent.layer.alert("您输入的号码有误，请重新检查输入");
                                //                                        return false
                                //                                    }
                                //                                }
                                if (tmp.length != array_unique(tmp).length) {
                                    parent.layer.alert("您输入的号码有重复，请重新检查输入", { icon: 7 });
                                    return false;
                                }
                                if (ps.curMethod.name == 'SDQSZUX' || ps.curMethod.name == 'SDQEZUX' || /SDRX(\d+)/.test(ps.curMethod.name)) {
                                    allCodes.push([arr[i].split(" ").join("_")]);
                                } else {
                                    allCodes.push(arr[i].split(" "));
                                }
                            }
                        }
                    }
                    //节省字符串连接时间
                    var ob = {
                        singleNum: 1,
                        isDup: 0
                    };
                    var strPart1 = '<li><span class="width1" mid="' + ps.curMethod.method_id + '">';
                    var strPart2 = "." + ps.curMethod.cname + '</span><span class="width2">';
                    //+ps.nextProjectCounter + "." + ps.curMethod.cname + '</span><span class="width60px">';
                    if (allCodes.length <= 1000) {
                        for (var i in allCodes) {
                            var ob = isLegalCode(allCodes[i]);
                            //对于三星连选，选一注的singleNum是3注，所以这个得动态算
                            var singleAmount = number_format(ob.singleNum * 2 * ps.curMode, 2);
                            var strPart3 = '</span><span class="width3">' + ob.singleNum + '注</span><span class="width4">￥' + singleAmount + '</span><span class="xDel">X</span></li>';
                            if (ob.isDup) {
                                //对于三星连选，选1注相当于3注，所以不能加ob.singleNum != 1条件
                                parent.layer.alert("您输入的号码有误，请重新检查输入!", { icon: 7 });
                                return false;
                            }
                            ps.nextProjectCounter++;
                            //var singleAmount = number_format(ob.singleNum * 2 * ps.curMode, 2);
                            //140329 加入排除重复号码功能 相同号码将无法加上 为效率起见100以下方案才判断
                            //                        var isDuplicate = false;
                            //                        if (allCodes.length < 100) {
                            //                            $("#projectList").children("li").each(function(ii) {
                            //                                if (ps.curMethod.method_id == $(this).children().eq(0).attr("mid") && allCodes[i].join(",") == $(this).children().eq(1).text()) {
                            //                                    isDuplicate = true;
                            //                                }
                            //                            });
                            //                            if (isDuplicate) {
                            //                                //parent.layer.alert('"' + allCodes[i].join(",") + '" 已添加至投注项，请勿重复添加！');
                            //                                continue;
                            //                            }
                            //                        }

                            //$('<li><span class="width80px" mid="' + ps.curMethod.method_id + '">' + ps.nextProjectCounter + "." + ps.curMethod.cname + '</span><span class="width60px">' + allCodes[i].join(",") + '</span><span class="width100px">' + ob.singleNum + '注</span><span class="width100px">￥' + singleAmount + '</span><span class="xDel">X</span></li>').appendTo("#projectList")
                            $(strPart1 + ps.nextProjectCounter + strPart2 + allCodes[i].join(",") + strPart3).appendTo("#projectList");
                        }
                    } else {
                        var allCodesStr = '';
                        var allProjectsStr = '';
                        for (var i in allCodes) {
                            if (allCodesStr) {
                                allCodesStr += '|';
                            }
                            allCodesStr += allCodes[i].join(",");
                            var ob = isLegalCode(allCodes[i]);
                            if (ob.isDup) {
                                //对于三星连选，选1注相当于3注，所以不能加ob.singleNum != 1条件
                                parent.layer.alert("您输入的号码有误，请重新检查输入!", { icon: 7 });
                                return false;
                            }
                        }
                        var singleAmount = number_format(allCodes.length * 2 * ps.curMode, 2);
                        var strPart3 = '</span><span class="width3">' + allCodes.length + '注</span><span class="width4">￥' + singleAmount + '</span><span class="xDel">X</span></li>';
                        ps.nextProjectCounter = allCodes.length;
                        allProjectsStr += strPart1 + strPart2 + allCodesStr + strPart3;
                        $(allProjectsStr).appendTo("#projectList");
                    }
                    buyBar.updateTotalSingle();
                    //$("#confirmBtn").removeClass('CantapCodeBtn');
                    $("#delTA").click();
                } else {
                    var ob = ballBar.computeSingle();
                    if (ob.singleNum == 0) {
                        return false;
                    }
                    ps.nextProjectCounter++;
                    var singleAmount = number_format(ob.singleNum * 2 * ps.curMode, 2);
                    //140329 加入排除重复号码功能 相同号码将无法加上 为效率起见100以下方案才判断
                    //                    var isDuplicate = false;
                    //                    if ($("#projectList").children("li").length < 100) {
                    //                        $("#projectList").children("li").each(function(i) {
                    //                            if (ps.curMethod.method_id == $(this).children().eq(0).attr("mid") && ob.code == $(this).children().eq(1).text()) {
                    //                                isDuplicate = true;
                    //                            }
                    //                        });
                    //                        if (isDuplicate) {
                    //                            //parent.layer.alert('"' + ob.code + '" 已添加至投注项，请勿重复添加！');
                    //                            return false;
                    //                        }
                    //                    }
                    if (ps.curMethod.method_id == 367) {
                        //如果是三连号通选(nyjah)
                        ob.code = '123_234_345_456';
                    }
                    //快乐扑克转注码T为10
                    if (ps.lotteryType == 7) {
                        ob.code = ob.code.replace(/T/g, '10');
                    }
                    $('<li><span class="width1" mid="' + ps.curMethod.method_id + '">' + ps.nextProjectCounter + "." + ps.curMethod.cname + '</span><span class="width2">' + ob.code + '</span><span class="width3">' + ob.singleNum + '注</span><span class="width4">￥' + singleAmount + '</span><span class="xDel">X</span></li>').appendTo("#projectList");
                    buyBar.updateTotalSingle();
                    ballBar.reset();
                    //$("#confirmBtn").removeClass('CantapCodeBtn');
                }
                $(".confirmArea").addClass("DisplayBlock");
                $(".locate input").removeClass("DDcolor");
                //var d = new Date();var t1 = d.getTime();
                //alert("66 t0=" + t0 + "\nt1=" + t1 + "\nt1-t0=" + (t1-t0));
            },
            removeAll: function removeAll() {
                $("#projectList").empty();
                ps.nextProjectCounter = 0;
                $("#multiple").val("1");
                $('.lotteryNumber li').removeClass('ball_Selected');
                $('#betCount').html('0');
                $("#open_counts").val("1");
                buyBar.updateTotalSingle();
            },
            updateSingle: function updateSingle(singleNum) {
                var singleAmount = number_format(singleNum * 2 * ps.curMode, 2);
                $("#betCount").text(singleNum);
                $("#betAmount").text(singleAmount);
                var projectListLen = $('#projectList li').length == 0 ? 1 : $('#projectList li').length;
                if (singleNum > 0) {
                    $("#selectCodeBtn").addClass('selectCodeBtn_selected');
                    //选号
                    $(".selectCodeBtn_selected").click(function () {
                        $(".NumberBox5").addClass("DisplayNone").removeClass("DisplayBlock");
                        // $(".moreGame").addClass("DisplayNone").removeClass("DisplayBlock");
                        $(".GameBoxall").addClass("DisplayNone").removeClass("DisplayBlock");
                        $(".betPage").addClass('DisplayBlock').removeClass("DisplayNone");
                        $(".locate dd").removeClass("DDcolor");
                    });
                    //计算盈亏
                    var totalBetAmount = $("#totalBetAmount").text();
                    var curPrizeHtml = $('#curPrizeSpan').find("option:selected").text();
                    var curPrize = curPrizeHtml.split('/')[0];
                    $("#totalWin").text(number_format(curPrize * projectListLen * $("#multiple").val() - totalBetAmount - singleAmount, 3));
                } else {
                    $("#selectCodeBtn").click(function () {
                        $(".NumberBox5").addClass("DisplayBlock").removeClass("DisplayNone");
                        $(".moreGame").addClass("DisplayBlock").removeClass("DisplayNone");
                        $(".GameBoxall").addClass("DisplayBlock").removeClass("DisplayNone");
                        $(".betPage").addClass('DisplayNone').removeClass("DisplayBlock");
                    });
                    $("#selectCodeBtn").removeClass('selectCodeBtn_selected');
                    //计算盈亏
                    var totalBetAmount = $("#totalBetAmount").text();
                    var curPrizeHtml = $('#curPrizeSpan').find("option:selected").text();
                    var curPrize = curPrizeHtml.split('/')[0];
                    $("#totalWin").text(number_format(curPrize * projectListLen * $("#multiple").val() - totalBetAmount, 3));
                }
            },
            updateTotalSingle: function updateTotalSingle() {
                var totalSingleNum = 0;
                $("#projectList").children("li").each(function (i) {
                    var spans = $(this).children();
                    totalSingleNum += parseInt(spans.eq(2).text());
                    spans.eq(3).text("￥" + number_format(parseInt(spans.eq(2).text()) * 2 * $("#multiple").val() * ps.curMode, 2));
                });
                if (totalSingleNum > 0) {
                    $("#confirmBtn").addClass('CantapCodeBtn_selected');
                } else {
                    $("#confirmBtn").removeClass('CantapCodeBtn_selected');
                    //秒秒彩保留投注和计数，如果用户全部清除了则重置计数器
                    ps.nextProjectCounter = 0;
                }
                var projectListLen = $('#projectList li').length == 0 ? 1 : $('#projectList li').length;
                $("#totalBetCount").text(totalSingleNum);
                var totalBetAmount = number_format(totalSingleNum * 2 * $("#multiple").val() * ps.curMode, 3);
                $("#totalBetAmount").text(totalBetAmount);
                var curPrizeHtml = $('#curPrizeSpan').find("option:selected").text();
                var curPrize = curPrizeHtml.split('/')[0];
                $("#totalWin").text(number_format(curPrize * projectListLen * $("#multiple").val() - totalBetAmount, 3));
            },
            checkMultiple: function checkMultiple() {
                this.value = this.value.replace(/^0|[^\d]/g, '');
                if ($(this).val() > 50000) {
                    parent.layer.alert("请输入正确的倍数，最大为50000倍", { icon: 7 });
                    $(this).val(50000);
                    return true;
                }
                return true;
            },
            //确认按钮
            confirmBtn_Click: function confirmBtn_Click() {
                //输入正整数时
                if (!/^[1-9]*[1-9][0-9]*$/.test($("#multiple").val())) {
                    parent.layer.alert("请输入正确的倍数，最大为50000倍", { icon: 7 });
                    $("#multiple").val("1");
                    return false;
                } else {

                    var methodCodes = {},
                        codes = '';
                    $("#token").val(getRandChar(32));
                    //秒秒彩开奖单独分支

                    var multipleOpenCount = $("#open_counts").val();

                    //连续开奖
                    if (parseInt(multipleOpenCount) > 1) {
                        mySlotMachine.playAgain = 1;
                    }

                    ///////////////////// 获取参数     /////////////////////
                    if (mySlotMachine.buyBarCodes != '' && mySlotMachine.playAgain == 1) {
                        //沿用上次参数再次开奖
                        codes = mySlotMachine.buyBarCodes;
                        //倍数
                        var totalSingleInfo = mySlotMachine.multiple;
                    } else {
                        //140329 按要求去掉重复选号
                        //1.先按玩法归类到一个对象
                        $("#projectList").children("li").each(function (i) {
                            if (!methodCodes[$(this).children().eq(0).attr("mid")]) {
                                methodCodes[$(this).children().eq(0).attr("mid")] = {};
                            };
                            methodCodes[$(this).children().eq(0).attr("mid")][i] = $(this).children().eq(1).text();
                            //methodCodes[$(this).children().eq(0).attr("mid")][$(this).children().eq(1).text()] = $(this).children().eq(1).text();
                        });
                        //2.拼出codes格式  46:1,2,3,4,5|6,7,8,9,0|1,2,3,4,5#43:1,2,3|6,7,0
                        $.each(methodCodes, function (mid, v) {
                            codes += mid + ':';
                            $.each(v, function (code, vv) {
                                codes += vv + '|';
                            });
                            codes = rtrim(codes, '|');
                            codes += '#';
                        });
                        codes = rtrim(codes, '#');
                        if (!ps.canBuy) {
                            parent.layer.alert("该期已截止购买", { icon: 7 });
                            return false;
                        }
                        if (codes.length == 0) {
                            parent.layer.alert("请先选择号码再投注", { icon: 7 });
                            return false;
                        }
                        var betTotalAmount = $("#totalBetAmount").text();
                        var betTotalBetCount = $("#totalBetCount").text();
                        var totalSingleInfo = $("#multiple").val();
                    }

                    //连续开奖一次性拿到所有结果，缓存到内存，每次摇奖取出
                    if (mySlotMachine.fullOpenResult != '') {
                        mySlotMachine.startOpenCode(mySlotMachine.fullOpenResult, codes, totalSingleInfo);
                    } else {
                        //第一次进入则向后台请求数据
                        ///////////////////// 提交购彩     /////////////////////
                        $.post("?c=game&a=play", {
                            "op": "buy",
                            "lotteryId": ps.lotteryId,
                            "issue": ps.curIssueInfo.issue,
                            "curRebate": ps.rebateGapList[ps.curPrizeIndex].rebate,
                            "modes": ps.curMode,
                            "codes": codes,
                            "multiple": totalSingleInfo,
                            "openCounts": multipleOpenCount,
                            "token": $("#token").val()
                        }, function (response) {
                            mySlotMachine.fullOpenResult = response;
                            mySlotMachine.startOpenCode(response, codes, totalSingleInfo);
                        }, "json").fail(function (msg) {
                            mySlotMachine.halfClearParams();
                            parent.layer.alert("购买失败:服务器处理请求失败，请稍后尝试！", { icon: 2 });
                        });
                    }
                    $(".confirmArea").removeClass("DisplayBlock");
                }
            },
            //追号按钮
            traceBtn_Click: function traceBtn_Click() {
                $("#traceBtn").attr("mark", 1);
                if ($("#projectList li").length == 0) {
                    parent.layer.alert("请先选择投注号码", { icon: 7 });
                    return false;
                }
                $("#traceBtn").attr('disabled', "true");
                var mids = [];
                $("#projectList li").each(function (i) {
                    if ($.inArray($(this).find("span:first").attr("mid"), mids) == -1) {
                        mids.push($(this).find("span:first").attr("mid"));
                    }
                });

                $.ajax({
                    url: "?c=game&a=play",
                    type: "POST",
                    data: {
                        op: "getTracePage",
                        lotteryId: ps.lotteryId,
                        mids: mids.join(",")
                    },
                    cache: false,
                    dataType: "json",
                    timeout: 30000,
                    success: function success(response) {
                        $("#traceBtn").removeAttr("disabled");
                        if (response.errno == 0) {
                            ps.canTraceIssues = response.issues;
                            ps.traceMethodPrize = response.prize;
                            ps.tracePrizeLimit = response.prizeLimit;
                            var i = traceFunc.showTracePage(response.content);
                            //特例： 一下玩法不能使用倍投工具。
                            var disableMethods = [250, 251, 360, 368, 28, 56, 101, 202, 298, 34, 57, 102, 203, 304, 4, 52, 106, 193, 274, 5, 53, 107, 194, 275, 81, 108, 109, 195, 351, 42, 75, 115, 219, 312, 43, 76, 116, 220, 313, 38, 71, 120, 214, 308, 39, 72, 121, 215, 309, 82, 110, 122, 216, 352, 86, 93, 126, 228, 356, 87, 94, 127, 229, 357, 88, 95, 128, 230, 358, 47, 78, 141, 222, 317, 235, 236, 386, 25, 155, 173, 189, 24, 154, 172, 188];

                            if (response.prize == 0 || mids.length != 1 || $.inArray(parseInt(ps.curMethod.method_id), disableMethods) != -1) {
                                $("#multipleStyle2", parent.document).attr("disabled", true);
                            }
                            $("input[name=multipleStyle]", parent.document).click(traceFunc.multipleStyle_Click);
                            $("#confirmTraceBtn", parent.document).click(function () {
                                if (traceFunc.confirmTraceBtn_Click()) {
                                    parent.layer.close(i);
                                }
                            });
                            $("#cancelTraceBtn", parent.document).click(function () {
                                traceFunc.cancelTraceBtn_Click();
                                parent.layer.close(i);
                            });
                            $("#startIssue", parent.document).change(traceFunc.startIssue_Change);
                            $("#traceNum", parent.document).click(function () {
                                this.focus();
                                this.select();
                            }).keyup(buyBar.checkMultiple).keyup(traceFunc.traceNum_Keyup);
                            //                            $("#ui-dialog2", parent.document).keyup(function(e) {
                            //                                var key = e.keyCode ? e.keyCode : e.which;
                            //                                if (key == 27) {
                            //                                    $("#cancelTraceBtn", parent.document).click()
                            //                                }
                            //                            });


                            $("#singleNum", parent.document).text($("#totalBetCount").text());
                            $("#issuesNum2", parent.document).text("1");
                            $("#multipleStyle1", parent.document).click();
                            traceFunc.updateTotalMoney();
                            runTime.traceRemainTimer = window.setInterval(traceFunc.traceRemain_Timer_Handle, 1000);
                        } else {
                            alert("系统繁忙，请稍候再试(01)");
                        }
                    },
                    error: function error(XMLHttpRequest, textStatus, errorThrown) {
                        $("#traceBtn").removeAttr("disabled");
                        if (errorThrown.indexOf("a=logout") != -1 || errorThrown.indexOf("a=login") != -1) {
                            alert("您已经退出，请重新登录");
                            window.location.href = "?a=logout";
                        } else {
                            alert("操作超时，请刷新页面.");
                        }
                    }
                });
            },
            // 机选方法
            selectRandom: function selectRandom() {
                var counts = propLen(ps.curMethod.field_def); // 获得位数
                var ball,
                    code,
                    singleNum,
                    mixSelect = {},
                    codes = []; // ball: 球的个数; code: 随机取的号码; mixSelect: 选择号码最小个数
                switch (ps.curMethod.name) {
                    case 'WXDW':
                        //五星定位(任一直选) 随机两位，每位一个数
                        mixSelect['WXDW'] = 1;
                    case 'REZX':
                        //任二直选:随机两位，每位一个数
                        mixSelect['REZX'] = 2;
                    case 'RSZX':
                        //任三直选:随机两位，每位一个数
                        mixSelect['RSZX'] = 3;

                        // 初始化每个位数的值
                        for (var n = 0; n < counts; n++) {
                            codes[n] = "-";
                        }
                        // 生成不重复的两个位数，然后在这两个位数中各自生成一个随机数
                        var tmpCounts = [];
                        for (var i = 1; i <= mixSelect[ps.curMethod.name]; i++) {
                            count = Math.floor(Math.random() * counts + 1); // 随机位数
                            var isEqual = false;
                            for (var idx in tmpCounts) {
                                if (tmpCounts[idx] === count) {
                                    isEqual = true;
                                    break;
                                }
                            }
                            if (isEqual) {
                                i--;
                            } else {
                                tmpCounts.push(count);

                                // 生成随机数并存入codes数组中
                                ball = $('#field_' + count + ' li').length; // 获得投注球个数
                                code = Math.floor(Math.random() * ball);
                                var value = $("#field_" + count + " li").eq(code).text(); // 获得随机出来的号码对应的投注值,生成的是一个字符串 code:1 value:"1"
                                codes[count - 1] = value;
                            }
                        }
                        break;
                    case 'RSIZX':
                        //任四直选:随机四位，每位一个数
                        mixSelect['RSIZX'] = 4;

                        // 初始化每个位数的值
                        for (var n = 0; n < counts; n++) {
                            codes[n] = "-";
                        }
                        // 生成不重复的两个位数，然后在这两个位数中各自生成一个随机数
                        var tmpCounts = [];
                        for (var i = 1; i <= mixSelect[ps.curMethod.name]; i++) {
                            count = Math.floor(Math.random() * counts + 1); // 随机位数
                            var isEqual = false;
                            for (var idx in tmpCounts) {
                                if (tmpCounts[idx] === count) {
                                    isEqual = true;
                                    break;
                                }
                            }
                            if (isEqual) {
                                i--;
                            } else {
                                tmpCounts.push(count);

                                // 生成随机数并存入codes数组中
                                ball = $('#field_' + count + ' li').length; // 获得投注球个数
                                code = Math.floor(Math.random() * ball);
                                var value = $("#field_" + count + " li").eq(code).text(); // 获得随机出来的号码对应的投注值,生成的是一个字符串 code:1 value:"1"
                                codes[count - 1] = value;
                            }
                        }
                        break;
                    case 'EXZUX':
                        //后二组选
                        mixSelect['EXZUX'] = [2];
                    case 'SXZS':
                        //后三组三
                        mixSelect['SXZS'] = [2];
                    case 'SXZL':
                        //后三组六
                        mixSelect['SXZL'] = [3];
                    case 'QEZUX':
                        //前二组选
                        mixSelect['QEZUX'] = [2];
                    case 'QSZS':
                        //前三组三
                        mixSelect['QSZS'] = [2];
                    case 'QSZL':
                        //前三组六
                        mixSelect['QSZL'] = [3];
                    case "ZSZS":
                        //中三组三
                        mixSelect['ZSZS'] = [2];
                    case "ZSZL":
                        //中三组六
                        mixSelect['ZSZL'] = [3];
                    case 'ZUX24':
                        //后四组选24
                        mixSelect['ZUX24'] = [4];
                    case 'ZUX12':
                        //后四组选12
                        mixSelect['ZUX12'] = [1, 2];
                    case 'ZUX6':
                        //后四组选6
                        mixSelect['ZUX6'] = [2];
                    case 'ZUX4':
                        //后四组选4
                        mixSelect['ZUX4'] = [1, 1];
                    case 'ZUX120':
                        //组选120
                        mixSelect['ZUX120'] = [5];
                    case 'ZUX60':
                        //五星组选60
                        mixSelect['ZUX60'] = [1, 3];
                    case 'ZUX30':
                        //五星组选30
                        mixSelect['ZUX30'] = [2, 1];
                    case 'ZUX20':
                        //五星组选20
                        mixSelect['ZUX20'] = [1, 2];
                    case 'ZUX10':
                        //五星组选10
                        mixSelect['ZUX10'] = [1, 1];
                    case 'ZUX5':
                        //五星组选5
                        mixSelect['ZUX5'] = [1, 1];

                        // 根据位数以及最小选择数生成不重复的数值
                        var allCodes = [];
                        for (var i = 1; i <= counts; i++) {
                            var tmpArr = [],
                                tmp = "";
                            ball = parseInt($('#field_' + i + ' li').length); // 获得投注球个数

                            for (var j = 1; j <= mixSelect[ps.curMethod.name][i - 1]; j++) {
                                var isEqual = false;
                                // 生成随机数
                                code = Math.floor(Math.random() * ball);
                                for (var idx in allCodes) {
                                    if (allCodes[idx] === code) {
                                        isEqual = true;
                                        break;
                                    }
                                }
                                if (isEqual) {
                                    j--;
                                } else {
                                    allCodes.push(code); // 将每位的号码存入tmpCodes数组中，不能重复
                                    tmpArr.push(code); // 将每位的号码存入tmpArr数组中
                                    tmp = tmpArr.sort().join(''); // 排序并将一列号码转为字符串，例[1,2] => "12"
                                }
                            }
                            codes.push(tmp);
                        }
                        break;
                    default:
                        // 循环位数,每个位数必须有一个号码
                        for (i = 1; i <= counts; i++) {
                            ball = parseInt($('#field_' + i + ' li').length); // 获得投注球个数
                            // 获得号码
                            code = Math.floor(Math.random() * ball);
                            var value = $("#field_" + i + " li").eq(code).text(); // 获得随机出来的号码对应的投注值,生成的是一个字符串 code:1 value:"1"
                            codes.push(value);
                        }
                        break;
                }
                var ob = isLegalCode(codes); // 验证号码并获得注数
                var resultCode = codes.join(",");
                if (ob.singleNum == 0) {
                    return false;
                }
                ps.nextProjectCounter++;
                var singleAmount = number_format(ob.singleNum * 2 * ps.curMode, 2);
                $('<li><span class="width1" mid="' + ps.curMethod.method_id + '">' + ps.nextProjectCounter + "." + ps.curMethod.cname + '</span><span class="width2">' + resultCode + '</span><span class="width3">' + ob.singleNum + '注</span><span class="width4">￥' + singleAmount + '</span><span class="xDel">X</span></li>').appendTo("#projectList");

                buyBar.updateTotalSingle();
            },
            // 机选点击事件
            selectRandomBtn_Click: function selectRandomBtn_Click() {
                // 将按钮变为未选中
                $(this).attr('disabled', 'true');
                var num = $(this).attr('num'),
                    i;
                // 循环机选注数
                for (i = 1; i <= num; i++) {
                    buyBar.selectRandom();
                }
                $(this).removeAttr('disabled');
            }
        };

        //5.1定义追号几个按钮事件 放在buyBar前面
        var traceFunc = {
            multipleStyle_Click: function multipleStyle_Click() {
                $("#startIssue", parent.document).get(0).selectedIndex = 0;
                $("#traceNum", parent.document).val(1);
                if ($(this).val() == 1) {
                    $(".style1BodyMultiple", parent.document).live("click", function () {
                        this.focus();
                        this.select();
                    }).live("keyup", buyBar.checkMultiple).live("keyup", traceFunc.style1BodyMultiple_Keyup);
                    traceFunc.updateStyle1();
                    $("#multipleStyle1DIV", parent.document).show();
                    $("#multipleStyle2DIV", parent.document).hide();
                } else {
                    $("#startMultiple", parent.document).click(function () {
                        this.focus();
                        this.select();
                    });
                    $("#beitouToolSmainbtzk input", parent.document).click(function () {
                        $(this).parent().click();
                    }).focus(function () {
                        this.select();
                    });
                    $("#beitouToolSmainbtzk li", parent.document).click(function () {
                        $(this).addClass("checked").siblings().removeClass("checked");
                        $(this).find("input[name=profitStyle]").attr("checked", true);
                    });
                    $("#generalPlanBtn", parent.document).click(traceFunc.generalPlanBtn_Click);
                    $("#issuesNum2", parent.document).text("1");
                    $("#style2Body", parent.document).empty();
                    $("#startMultiple", parent.document).val("1");
                    $("input[name=totalProfitRate]", parent.document).val("10");
                    $("input[name=first5Rate]", parent.document).val("5");
                    $("input[name=first5RateValue]", parent.document).val("10");
                    $("input[name=laterRateValue]", parent.document).val("5");
                    $("input[name=totalProfit]", parent.document).val("100");
                    $("input[name=first5Profit]", parent.document).val("5");
                    $("input[name=first5ProfitValue]", parent.document).val("100");
                    $("input[name=laterProfitValue]", parent.document).val("50");
                    $("#beitouToolSmainbtzk li:first", parent.document).click();
                    $("#multipleStyle1DIV", parent.document).hide();
                    $("#multipleStyle2DIV", parent.document).show();
                }
            },
            startIssue_Change: function startIssue_Change() {
                if ($("input[name=multipleStyle]:checked", parent.document).val() == "1") {
                    traceFunc.updateStyle1();
                }
            },
            traceNum_Keyup: function traceNum_Keyup() {
                if ($("input[name=multipleStyle]:checked", parent.document).val() == "1") {
                    traceFunc.updateStyle1();
                }
            },
            //追号界面也加一个倒计时
            traceRemain_Timer_Handle: function traceRemain_Timer_Handle() {
                var d = subTime(ps.curRemainTime);
                if (ps.curRemainTime > 0) {
                    $("#remainTimerLabel", parent.document).text(d.hour + ":" + d.minute + ":" + d.second);
                    $("#traceBtn").attr("mark", 0);
                } else {
                    clearInterval(runTime.traceRemainTimer);
                    var d2 = subTime(ps.curWaitOpenTime);
                    $("#remainTimerLabel", parent.document).text(d2.hour + ":" + d2.minute + ":" + d2.second);
                    runTime.traceWaitOpenTimer = window.setInterval(traceFunc.traceWaitOpen_Timer_Handle, 1000);
                    //去掉过期的一期
                    parent.layer.alert("第" + ps.curIssueInfo.issue + "期投注时间已结束，投注内容将进入到下一期！", { icon: 7 });
                    if ($("#traceBtn").attr("mark") != 1) {
                        $("#startIssue", parent.document).children(":first").remove();
                        $("#startIssue", parent.document).children(":first").text($("#startIssue", parent.document).children(":first").text() + "(当前期)");

                        var tmpArr = [];
                        $(".style1BodyMultiple", parent.document).each(function () {
                            tmpArr.push(this.value);
                        });
                        traceFunc.updateStyle1();
                        $(".style1BodyMultiple", parent.document).each(function () {
                            this.value = tmpArr.pop();
                        });
                    }
                }
            },
            //显示锁倒计时
            traceWaitOpen_Timer_Handle: function traceWaitOpen_Timer_Handle() {
                var d = subTime(ps.curWaitOpenTime);
                $("#remainTimerLabel", parent.document).text(d.hour + ":" + d.minute + ":" + d.second);
                if (ps.curWaitOpenTime <= 0) {
                    clearInterval(runTime.traceWaitOpenTimer);
                    runTime.traceRemainTimer = window.setInterval(traceFunc.traceRemain_Timer_Handle, 1000);
                }
            },
            style1BodyMultiple_Keyup: function style1BodyMultiple_Keyup() {
                var multiple = parseInt($(this).val());
                if (isNaN(multiple) || multiple < 1 || multiple > 99999) {
                    multiple = 1;
                }
                var $reg = /\w+_(\d+)/;
                $reg.test($(this).attr("id"));
                var idx = RegExp.$1;
                if (idx == 0) {
                    var prevTotalMoney = 0;
                } else {
                    var prevTotalMoney = parseFloat($("#totalMoney_" + (idx - 1), parent.document).text());
                }
                while (idx <= $("#style1Body li", parent.document).length) {
                    $("#style1BodyMultiple_" + idx, parent.document).val(multiple);
                    var curMoney = parseInt($("#singleNum", parent.document).text()) * multiple * 2 * ps.curMode;
                    prevTotalMoney += curMoney;
                    $("#curMoney_" + idx, parent.document).text(number_format(curMoney, 2));
                    $("#totalMoney_" + idx, parent.document).text(number_format(prevTotalMoney, 2));
                    idx++;
                }
                traceFunc.updateTotalMoney();
            },
            updateStyle1: function updateStyle1() {
                var idx = -1;
                $.each(ps.canTraceIssues, function (k, v) {
                    if (v == $("#startIssue", parent.document).val()) {
                        idx = k;
                    }
                });
                if (idx == -1) {
                    alert("数据出错");
                    throw "数据出错";
                }
                if (isNaN(parseInt($("#traceNum", parent.document).val()))) {
                    $("#traceNum", parent.document).val("1");
                }
                var willTraceIssues = ps.canTraceIssues.slice(idx, idx + parseInt($("#traceNum", parent.document).val()));
                if (willTraceIssues.length < $("#traceNum", parent.document).val()) {
                    parent.layer.alert("最多只能追" + willTraceIssues.length + "期", { icon: 7 });
                    $("#traceNum", parent.document).val(willTraceIssues.length);
                }
                $("#style1Body", parent.document).empty();
                var str = "",
                    curMoney,
                    totalMoney = 0;
                $.each(willTraceIssues, function (k, v) {
                    curMoney = parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode;
                    totalMoney += curMoney;
                    var str = '<li id="traceIssueLI_' + k + '"><span id="traceIssue_' + k + '">' + v + '</span><span><input type="text" value="1" id="style1BodyMultiple_' + k + '" class="beitouToolsinput style1BodyMultiple" maxlength="5" /></span><span id=curMoney_' + k + ">" + number_format(curMoney, 2) + "</span><span id=totalMoney_" + k + ">" + number_format(totalMoney, 2) + "</span></li>";
                    $("#style1Body", parent.document).append(str);
                    $(".style1BodyMultiple", parent.document).bind("click", function () {
                        this.focus();
                        this.select();
                    }).bind("keyup", buyBar.checkMultiple).bind("keyup", traceFunc.style1BodyMultiple_Keyup);
                });
                traceFunc.updateTotalMoney();
            },
            confirmTraceBtn_Click: function confirmTraceBtn_Click() {
                var spans, codes, tmpMethod, mid, code, listFirst;
                $("#projectList").children("li").each(function (i) {
                    spans = $(this).children();
                    mid = spans.eq(0).attr("mid");
                    code = spans.eq(1).text();
                    listFirst = false;
                    if (!tmpMethod) {
                        tmpMethod = mid;
                        codes = mid + ":";
                        listFirst = true;
                    }

                    if (tmpMethod != mid) {
                        codes += "#" + mid + ":";
                        tmpMethod = mid;
                        codes += code;
                    } else {
                        if (listFirst == true) {
                            codes += code;
                        } else {
                            codes += "|" + code;
                        }
                    }
                });
                if (!ps.canBuy) {
                    parent.layer.alert("该期已截止购买", { icon: 7 });
                    return false;
                }
                if (codes.length == 0) {
                    parent.layer.alert("请先选择号码再投注", { icon: 7 });
                    return false;
                }

                var traceData = [];
                if ($("input[name=multipleStyle]:checked", parent.document).val() == "1") {
                    $("#style1Body li", parent.document).each(function (i) {
                        var issue = $(this).find("span:eq(0)").text();
                        var multiple = $(this).find("input").val();
                        traceData.push({
                            issue: issue,
                            multiple: multiple
                        });
                    });
                } else {
                    $("#style2Body li", parent.document).each(function (i) {
                        var issue = $(this).find("span:eq(0)").text();
                        var multiple = $(this).find("span:eq(1)").text();
                        traceData.push({
                            issue: issue,
                            multiple: multiple
                        });
                    });
                }
                var Flag = 0;
                var traceTotalAmount = $("#traceTotalAmount", parent.document).text();
                var stopOnWin = $("input[name=stopOnWin]", parent.document).attr("checked") ? 1 : 0;
                var confirmInfo = '<div id="buy_message">请确认以下投注内容：<br>************************<br>是否追号：是<br>单倍注数：' + $("#totalBetCount").text() + "注<br>总 金 额：￥" + traceTotalAmount + "<br>超始期号：" + traceData[0].issue + "<br>追号期数：" + traceData.length + "<br>模&nbsp;&nbsp;式：" + (ps.curMode == 1 ? "元" : ps.curMode == 0.1 ? "角" : "分") + "模式<br>************************<br></div>";
                traceFunc.destroyTracePage();
                parent.layer.confirm(confirmInfo, { icon: 7 }, function (i) {
                    //快乐扑克10转成注码T
                    if (ps.lotteryType == 7) {
                        codes = codes.replace(/10/g, 'T');
                    }
                    Flag += 1;
                    if (Flag == 1) {
                        $.post("?c=game&a=play", {
                            op: "buy",
                            lotteryId: ps.lotteryId,
                            issue: ps.curIssueInfo.issue,
                            curRebate: ps.rebateGapList[ps.curPrizeIndex].rebate,
                            modes: ps.curMode,
                            codes: codes,
                            traceData: traceData,
                            stopOnWin: stopOnWin
                        }, function (response) {
                            if (response.errno == 0) {
                                buyBar.removeAll();
                                var msg = '<div id="buy_success_message">追号订单成功!<br>************************<br>订单编号：' + response.pkgnum + "<br>起始期号：" + traceData[0].issue + "<br>追号期数：" + traceData.length + "<br>追号总额：￥" + traceTotalAmount + "<br>模&nbsp;&nbsp;式：" + (ps.curMode == 1 ? "元" : ps.curMode == 0.1 ? "角" : "分") + "模式<br>************************<br></div>";
                                parent.layer.close(i);
                                parent.layer.alert(msg, { icon: 1 });
                                parent.$('.xubox_yes').focus();
                                parent.$('.xubox_yes').one('keyup', function (e) {
                                    var key = e.keyCode ? e.keyCode : e.which;
                                    if (key == 32) {
                                        parent.$('.xubox_yes').trigger("click");
                                    }
                                    if (key == 27) {
                                        parent.$('.xubox_no').trigger("click");
                                    }
                                });
                            } else {
                                parent.layer.close(i);
                                parent.layer.alert("追号失败:" + response.errstr, { icon: 2 });
                            }
                            //showBalance()
                        }, "json").fail(function () {
                            parent.layer.close(i);
                            layer.alert("购买失败:服务器处理请求失败，请稍后尝试！", { icon: 2 });
                        });
                        var disonckick = setInterval(function () {
                            Flag = 0;
                            if (Flag == 0) {
                                clearInterval(disonckick);
                            }
                        }, 5000);
                    }
                    //使用进度条代替按钮
                    parent.$('.xubox_botton').empty();
                    parent.$('.xubox_botton').html('<div class="LoadingShow"><span class="Loading_icon"></span><span class="Loading_Font">投注中，请稍后...</span></div>');
                });

                parent.$('.xubox_yes').focus();
                parent.$('.xubox_yes').one('keyup', function (e) {
                    var key = e.keyCode ? e.keyCode : e.which;
                    if (key == 32) {
                        parent.$('.xubox_yes').trigger("click");
                    }
                    if (key == 27) {
                        parent.$('.xubox_no').trigger("click");
                    }
                });

                return true;
            },
            cancelTraceBtn_Click: function cancelTraceBtn_Click() {
                traceFunc.destroyTracePage();
            },
            showTracePage: function showTracePage(content) {
                //                var wnd = window.parent;
                //                $("body", wnd.document).append('<div id="ui-dialog2" style="outline: 0px none; z-index: 1002;" class="ui-dialog" tabindex="-1"></div>');
                //                var uiDialog2 = $("#ui-dialog2", wnd.document).append(content).css("width", 530).hide();
                //                $("body", wnd.document).append('<div id="ui-widget-overlay2" class="ui-widget-overlay2" style="z-index: 1001;"></div>');
                //                var dialogOverlay2 = $("#ui-widget-overlay2", wnd.document).css("width", $(wnd.document).width()).css("height", $(wnd.document).height());
                //                var rect = getXY(wnd);
                //                uiDialog2.css("left", rect.scrollX + (rect.width - uiDialog2.width()) / 2);
                //                uiDialog2.css("top", rect.scrollY + (rect.height - uiDialog2.height()) / 2);
                //                uiDialog2.show();
                //                dialogOverlay2.show();
                var i = parent.layer.open({
                    type: 1,
                    title: '追号管理',
                    offset: ['50px', ''],
                    //border: [0],
                    area: ['700px', 'auto'],
                    content: content,
                    success: function success(l) {
                        $("#startIssue", parent.document).children(":first").text($("#startIssue", parent.document).children(":first").text() + "(当前期)");
                    },
                    close: traceFunc.cancelTraceBtn_Click
                });
                $(document, parent.document).one('keyup', function (e) {
                    var key = e.keyCode ? e.keyCode : e.which;
                    if (key == 27) {
                        traceFunc.cancelTraceBtn_Click();
                        parent.layer.close(i);
                    }
                });
                return i;
            },
            destroyTracePage: function destroyTracePage() {
                //                $("#ui-dialog2", parent.document).remove();
                //                $("#ui-widget-overlay2", parent.document).remove();
                clearInterval(runTime.traceRemainTimer);
                clearInterval(runTime.traceWaitOpenTimer);
            },
            updateTotalMoney: function updateTotalMoney() {
                var totalMultiple = 0;
                if ($("input[name=multipleStyle]:checked", parent.document).val() == "1") {
                    $("#style1Body li", parent.document).each(function (i) {
                        totalMultiple += parseInt($(this).find("input").val());
                    });
                    $("#issuesNum2", parent.document).text($("#style1Body li", parent.document).length);
                } else {
                    $("#style2Body li", parent.document).each(function (i) {
                        totalMultiple += parseInt($(this).find("span:eq(1)").text());
                    });
                    $("#issuesNum2", parent.document).text($("#style2Body li", parent.document).length);
                }
                $("#traceTotalAmount", parent.document).text(number_format(parseInt($("#singleNum", parent.document).text()) * totalMultiple * 2 * ps.curMode, 2));
            },
            generalPlanBtn_Click: function generalPlanBtn_Click() {
                var computeMultiple = function computeMultiple(startMultiple, profitRate, singleAmount, totalMoney, prize) {
                    startMultiple = isNaN(parseInt(startMultiple)) ? -1 : parseInt(startMultiple);
                    profitRate = isNaN(parseInt(profitRate)) ? -1 : parseInt(profitRate);
                    singleAmount = isNaN(parseFloat(singleAmount)) ? -1 : parseFloat(singleAmount);
                    totalMoney = isNaN(parseFloat(totalMoney)) ? -1 : parseFloat(totalMoney);
                    prize = parseFloat(prize);
                    if (startMultiple < 0 || profitRate < 0 || singleAmount < 0 || totalMoney < 0 || prize <= 0) {
                        return 0;
                    }
                    var result = 0;
                    if (singleAmount > 0) {
                        if (profitRate > 0) {
                            result = (profitRate / 100 + 1) * totalMoney / (prize - singleAmount * (profitRate / 100 + 1));
                            result = Math.ceil(round(result, 3));
                        } else {
                            result = 1;
                        }
                        if (result > 0 && result < startMultiple) {
                            result = startMultiple;
                        }
                    }
                    return result;
                };
                var idx = -1;
                $.each(ps.canTraceIssues, function (k, v) {
                    if (v == $("#startIssue", parent.document).val()) {
                        idx = k;
                    }
                });
                if (idx == -1) {
                    alert("数据出错");
                    throw "数据出错";
                }
                if (isNaN(parseInt($("#traceNum", parent.document).val()))) {
                    $("#traceNum", parent.document).val("1");
                }
                var willTraceIssues = ps.canTraceIssues.slice(idx, idx + parseInt($("#traceNum", parent.document).val()));
                if (willTraceIssues.length < $("#traceNum", parent.document).val()) {
                    parent.layer.alert("最多只能追" + willTraceIssues.length + "期", { icon: 7 });
                    $("#traceNum", parent.document).val(willTraceIssues.length);
                }
                $("#style2Body", parent.document).empty();
                var traces = [],
                    str = "",
                    curMultiple,
                    curMoney,
                    totalMoney = 0;
                var singleMoney = parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode;
                var prize = ps.traceMethodPrize * (ps.rebateGapList[ps.curPrizeIndex].prize / (ps.maxCombPrize * ps.prizeRate)) * ps.curMode;
                $.each(willTraceIssues, function (k, v) {
                    if ($("input[name=profitStyle]:checked", parent.document).val() == 1) {
                        if (k == 0) {
                            curMultiple = parseInt($("#startMultiple", parent.document).val());
                            curMoney = curMultiple * parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode;
                            if ((curMultiple * prize - curMoney) / curMoney * 100 < $("input[name=totalProfitRate]", parent.document).val()) {
                                parent.layer.alert("该计划无法实现，请调整目标", { icon: 7 });
                                return false;
                            }
                        } else {
                            curMultiple = computeMultiple($("#startMultiple", parent.document).val(), $("input[name=totalProfitRate]", parent.document).val(), singleMoney, totalMoney, prize);
                        }
                    } else {
                        if ($("input[name=profitStyle]:checked", parent.document).val() == 2) {
                            if (k == 0) {
                                curMultiple = parseInt($("#startMultiple", parent.document).val());
                                curMoney = curMultiple * parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode;
                                if ((curMultiple * prize - curMoney) / curMoney * 100 < $("input[name=first5RateValue]", parent.document).val()) {
                                    parent.layer.alert("该计划无法实现，请调整目标", { icon: 7 });
                                    return false;
                                }
                            } else {
                                if (k < $("input[name=first5Rate]", parent.document).val()) {
                                    curMultiple = computeMultiple($("#startMultiple", parent.document).val(), $("input[name=first5RateValue]", parent.document).val(), singleMoney, totalMoney, prize);
                                } else {
                                    curMultiple = computeMultiple($("#startMultiple", parent.document).val(), $("input[name=laterRateValue]", parent.document).val(), singleMoney, totalMoney, prize);
                                }
                            }
                        } else {
                            if ($("input[name=profitStyle]:checked", parent.document).val() == 3) {
                                curMultiple = Math.ceil(round((parseInt($("input[name=totalProfit]", parent.document).val()) + totalMoney) / (prize - parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode), 3));
                                if (curMultiple < $("#startMultiple", parent.document).val()) {
                                    curMultiple = $("#startMultiple", parent.document).val();
                                }
                            } else {
                                if ($("input[name=profitStyle]:checked", parent.document).val() == 4) {
                                    if (k < $("input[name=first5Profit]", parent.document).val()) {
                                        curMultiple = Math.ceil(round((parseInt($("input[name=first5ProfitValue]", parent.document).val()) + totalMoney) / (prize - parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode), 3));
                                    } else {
                                        curMultiple = Math.ceil(round((parseInt($("input[name=laterProfitValue]", parent.document).val()) + totalMoney) / (prize - parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode), 3));
                                    }
                                }
                            }
                        }
                    }
                    if (curMultiple == 0) {
                        parent.layer.alert("您输入的参数有误，必须为正整数", { icon: 7 });
                        return false;
                    } else {
                        if (curMultiple < 0) {
                            parent.layer.alert("该计划不可能实现，请调整目标", { icon: 7 });
                            return false;
                        } else {
                            if (curMultiple * prize > ps.tracePrizeLimit) {
                                parent.layer.alert("该计划超出无法实现，请调整目标", { icon: 7 });
                                return false;
                            }
                        }
                    }
                    curMoney = curMultiple * parseInt($("#singleNum", parent.document).text()) * 2 * ps.curMode;
                    totalMoney += curMoney;
                    traces.push({
                        issue: v,
                        multiple: curMultiple,
                        curMoney: number_format(curMoney, 2),
                        totalMoney: number_format(totalMoney, 2),
                        curPrize: number_format(curMultiple * prize, 2),
                        totalProfit: number_format(curMultiple * prize - totalMoney, 2),
                        totalProfitRate: round((curMultiple * prize - totalMoney) / totalMoney, 2)
                    });
                });
                traceFunc._showPlan(traces);
            },
            _showPlan: function _showPlan(traces) {
                $.each(traces, function (k, v) {
                    var str = '<li><span class="spanWidth90px">' + v.issue + '</span><span class="spanWidth50px">' + v.multiple + '</span><span class="spanWidth70px">' + v.curMoney + '</span><span class="spanWidth70px">' + v.totalMoney + '</span><span class="spanWidth70px">' + v.curPrize + '</span><span class="spanWidth70px">' + v.totalProfit + '</span><span class="spanWidth70px">' + Math.round(v.totalProfitRate * 100) + "%</span></li>";
                    $("#style2Body", parent.document).append(str);
                });
                traceFunc.updateTotalMoney();
            }
        };

        //6.开奖区
        var initDrawBar = function initDrawBar() {
            /* 初始化开奖区 */

            //秒秒彩无需前后奖期什么时候都可以买
            ps.canBuy = true;

            return true;

            //$("#curLotteryName").text(ps.lotteryName);
            $("#curLotteryName2").text(ps.lotteryName);
            if (ps.lotteryType == 1) {
                $("#todayIssuesHead").html('<li class="width80px">期号</li><li class="width60px">开奖号</li><li class="width55px">前三组态</li><li class="width55px">后三组态</li>');
            } else if (ps.lotteryType == 2) {
                $("#todayIssuesHead").html('<li class="width247px">期号</li><li class="width247px">开奖号</li>');
            } else if (ps.lotteryType == 4) {
                $("#todayIssuesHead").html('<li class="width80px">期号</li><li class="width60px">开奖号</li><li class="width55px">三星组态</li><li class="width55px">三星和值</li>');
            } else if (ps.lotteryType == 6) {
                $("#todayIssuesHead").html('<li class="width80px">期号</li><li class="width60px">开奖号</li><li class="width55px">和值</li><li class="width55px">形态</li>');
            } else if (ps.lotteryType == 7) {
                $("#todayIssuesHead").html('<li class="width80px">期号</li><li style="width:120px">开奖号</li><li class="width55px">形态</li>');
            }
            $("#todayDrawBtn").click(drawBar.todayDrawBtn_Click);
            $("#prizeRankBtn").click(drawBar.prizeRankBtn_Click);
            $("#todayBuyBtn").click(drawBar.todayBuyBtn_Click);
            $.each(ps.openedIssues, function (k, v) {
                v.prop = drawBar.getMoreInfo(v.code);
                ps.todayDrawList.push(v);
            });
            $("#todayDrawBtn").click();
            drawBar.getCurIssue(drawBar.init);
        };

        var drawBar = {
            init: function init() {
                /* 开奖初始化 */

                return true;

                runTime.remainTimer = window.setInterval(drawBar.showCurIssue_Timer, 1000);
                if (ps.lastIssueInfo.code == "") {
                    ps.getLastOpenTime = 0;
                    clearInterval(runTime.getLastOpenTimer);
                    runTime.getLastOpenTimer = window.setInterval(drawBar.getLastOpen_Timer, 1000);
                    $("#thisIssueInfo").addClass("lock");
                    ps.canBuy = false;
                    $("#thisIssueSpan").text(ps.lastIssueInfo.issue);
                } else {
                    //友好界面 1秒等待后显示
                    //更新最近一期数据，否则导致draw.init()中重复调用
                    var latest = ps.todayDrawList[0];
                    //tconsole.info("latest.issue=" + latest.issue);
                    if (ps.lastIssueInfo.issue != latest.issue) {
                        var tmp = ps.lastIssueInfo;
                        var ob = drawBar.getMoreInfo(tmp.code);
                        tmp.prop = ob;
                        ps.todayDrawList.unshift(tmp);
                        $.post("?c=game&a=play", {
                            op: "getBuyRecords",
                            lotteryId: ps.lotteryId,
                            issue: ps.lastIssueInfo.issue
                        }, function (response) {
                            if (response.errno == 0) {
                                if (response.prj.length != 0) {
                                    var proj = response.prj[0];

                                    if (proj.prizeStatus == "已中奖") {
                                        parent.layer.open({
                                            title: "中奖通知",
                                            offset: '172px',
                                            content: '恭喜您中得<span style="color:red">' + ps.lotteryName + '</span>第<span style="color:red">' + proj.issue + '</span>期<span style="color:red">￥' + proj.prize + '</span>'
                                        });
                                    }
                                }
                            }
                        }, "json");
                        if ($("#todayDrawBtn").hasClass("cur")) {
                            var v = ps.todayDrawList[0];
                            if (ps.lotteryType == 1) {
                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + v.prop.qszt + '</li><li class="width53px">' + v.prop.hszt + "</li></ul>";
                            } else if (ps.lotteryType == 2) {
                                var str = '<ul><li class="width247px">' + v.issue + '</li><li class="width247px">' + v.code + "</li>";
                            } else if (ps.lotteryType == 4) {
                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + v.prop.qszt + '</li><li class="width53px">' + v.prop.qshz + "</li></ul>";
                            } else if (ps.lotteryType == 6) {
                                var tmp = v.code.split("");
                                var hz = parseInt(tmp[0]) + parseInt(tmp[1]) + parseInt(tmp[2]);
                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + hz + '</li><li class="width53px">' + (hz > 10 ? "大" : "小") + (hz % 2 ? "单" : "双") + "</li></ul>";
                            } else if (ps.lotteryType == 7) {
                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width_poker">' + drawBar.pokerOpenCode(v.code) + '</li><li class="width60px">' + drawBar.getMoreInfo(v.code).pkzt + "</li></ul>";
                            }

                            $("#todayIssuesBody").prepend(str);
                        }
                    }
                }

                //初始化开奖球数目
                if (ps.lotteryType == 1 || ps.lotteryType == 4 || ps.lotteryType == 6) {
                    var nums = ps.todayDrawList[0].code.split("");
                } else if (ps.lotteryType == 2 || ps.lotteryType == 7) {
                    var nums = ps.todayDrawList[0].code.split(" ");
                }
                $('#thisIssueNumUL').empty();
                $.each(nums, function (i, n) {
                    if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                        $('#thisIssueNumUL').append('<span class="pendingBall"></span>');
                    } else if (ps.lotteryType == 2) {
                        $('#thisIssueNumUL').append('<span class="pendingBall"></span>');
                        //$('#thisIssueNumUL').append('<span class="sd115_Ball"></span>');
                    } else if (ps.lotteryType == 6) {
                        //快三
                        $('#thisIssueNumUL').append('<span class="pendingNum_k3' + (i + 1) + ' k3"></span>');
                    } else if (ps.lotteryType == 7) {
                        //扑克
                        $('#thisIssueNumUL').append('<div class="pendingNum_poker poker"><span class="poker_kj_num poker_kj_wait' + (i + 1) + ' poker_kj_wait"><i></i><em></em></span></div>');
                    }
                });
                drawBar.showLastDraw();
                ps.canBuy = true;
            },
            //扑克 开奖区 显示code：以花色+数字的方式显示   code : 7s Ac Td
            pokerOpenCode: function pokerOpenCode(code) {
                var parts = code.split(' ');
                var trans = { 's': 'poker_heit', 'h': 'poker_hongt', 'c': 'poker_meih', 'd': 'poker_fangk' };
                var poker = '';
                var str = '<div class="poker_kj">';
                for (i = 0; i < 3; i++) {
                    poker = parts[i].split(''); //0=>number 1=>suit
                    str += '<span class="poker_kj_num ' + eval('trans.' + poker[1]) + '"><i></i><em>' + drawBar.translateT(poker[0]) + '</em></span>';
                }
                str += '</div>';

                return str;
            },
            getCurIssue: function getCurIssue(callback) {
                $.ajax({
                    url: "?c=game&a=play",
                    type: "POST",
                    data: {
                        op: "getCurIssue",
                        lotteryId: ps.lotteryId
                    },
                    cache: false,
                    dataType: "json",
                    timeout: 30000,
                    success: function success(response) {
                        if (response.errno == 0) {
                            if (response.kTime != 0) {
                                $('#selectCodeBtn').attr("disabled", "disabled");
                                $('#selectCodeBtn').css("background", "#ccc");
                                $('.selectRandomBtn').attr("disabled", "disabled");
                                $('.selectRandomBtn').css("background", "#ccc");
                                $('.GameName').text('');
                                var t = response.kTime / 1000;
                                $('#thisIssueNumUL').html("<div style='font-size: 30px;text-align:center;float: left;margin-left: 90px;'>休市中...</div>");
                                $('.issue').text('距离下次开市:');
                                window.setInterval(function () {
                                    --t;
                                    var d = subTime(t);
                                    $("#thisIssueRemainTime").html("<span>" + d.hour + "</span><em>:</em><span>" + d.minute + "</span><em>:</em><span>" + d.second + "</span>");
                                }, 1000);

                                window.setTimeout(function () {
                                    clearInterval(runTime.waitOpenTimer);
                                    window.location.reload();
                                    /// drawBar.getCurIssue(drawBar.init1);
                                }, response.kTime);
                            } else {
                                ps.curIssueInfo = response.issueInfo;
                                ps.curServerTime = response.serverTime;
                                ps.curRemainTime = getTS(ps.curIssueInfo.end_time) - getTS(ps.curServerTime);
                                ps.curWaitOpenTime = 8; //显示锁形的时间，可酌情减少，不构成风险
                                ps.lastIssueInfo = response.lastIssueInfo;
                                if (typeof callback == "function") {
                                    callback();
                                }
                            }
                        } else {
                            alert("系统繁忙，请稍候再试(02)");
                        }
                    },
                    error: function error(XMLHttpRequest, textStatus, errorThrown) {
                        if (errorThrown.indexOf("a=logout") != -1 || errorThrown.indexOf("a=login") != -1) {
                            top.layer.alert("您已经超时退出，请重新登录", { icon: 7 });
                            window.location.href = "?a=logout";
                        } else {
                            top.layer.alert("操作超时，请刷新页面..", { icon: 7 });
                        }
                    }
                });
            },
            showCurIssue_Timer: function showCurIssue_Timer() {
                $("#thisIssueSpan").text(ps.curIssueInfo.issue);
                $("#thisIssueSpan2").text(ps.curIssueInfo.issue);
                var d = subTime(--ps.curRemainTime);
                if (ps.curRemainTime >= 0) {
                    $("#thisIssueRemainTime").text(d.hour + ":" + d.minute + ":" + d.second);
                    $("#thisIssueTimerIcon").removeClass("lock").addClass('clock');
                } else {
                    clearInterval(runTime.remainTimer);
                    $('#thisIssueTimerIcon').removeClass('clock').addClass('lock');
                    var d2 = subTime(ps.curWaitOpenTime);
                    $('#thisIssueRemainTime').text(d2.hour + ":" + d2.minute + ":" + d2.second);
                    //$("#thisIssueRemainTime").addClass("lotteryTime-lock");
                    //$("#thisIssueMoreInfo").html('<div class="remainOpenDIV">第 ' + ps.curIssueInfo.issue + ' 期开奖倒计时：<span class="lotteryTime2">' + ps.curWaitOpenTime + "</span></div>");
                    ps.canBuy = false;
                    runTime.waitOpenTimer = window.setInterval(drawBar.waitOpen_Timer, 1000);
                }
            },
            //显示锁倒计时
            waitOpen_Timer: function waitOpen_Timer() {
                --ps.curWaitOpenTime;
                var d = subTime(ps.curWaitOpenTime);
                $("#thisIssueRemainTime").text(d.hour + ":" + d.minute + ":" + d.second);
                if (ps.curWaitOpenTime < 0) {
                    clearInterval(runTime.waitOpenTimer);
                    drawBar.getCurIssue(drawBar.init);
                }
            },
            getLastOpen_Timer: function getLastOpen_Timer() {
                ps.getLastOpenTime++;
                //console.info("ps.getLastOpenTime计时器=" + ps.getLastOpenTime);
                //每10秒请求一次
                if (ps.getLastOpenTime % 10 == 0) {
                    $.ajax({
                        url: "?c=game&a=play",
                        type: "POST",
                        data: {
                            op: "getLastIssueCode",
                            lotteryId: ps.lotteryId,
                            issue: ps.lastIssueInfo.issue
                        },
                        cache: false,
                        dataType: "json",
                        timeout: 30000,
                        success: function success(response) {
                            if (response.errno == 0) {
                                if (response.kTime != 0) {
                                    clearInterval(runTime.waitOpenTimer);
                                    window.location.reload();
                                    //        window.setTimeout(function(){
                                    //            $ ('#pendingText').html('<span style="font-size: 26px;color: red; text-align: center;   margin-top: 20px;">休市中...</span>');
                                    //            clearInterval(runTime.waitOpenTimer);
                                    //            window.location.reload();
                                    //     /// drawBar.getCurIssue(drawBar.init1);
                                    // },response.kTime)
                                } else {
                                    if (typeof response.issueInfo.code != "undefined") {
                                        clearInterval(runTime.getLastOpenTimer);
                                        ps.getLastOpenTime = 0;
                                        //更新最近一期数据，否则导致draw.init()中重复调用
                                        ps.lastIssueInfo = response.issueInfo;
                                        var ob = drawBar.getMoreInfo(response.issueInfo.code);
                                        response.issueInfo.prop = ob;
                                        ps.todayDrawList.unshift(response.issueInfo);
                                        if ($("#todayDrawBtn").hasClass("cur")) {
                                            var v = ps.todayDrawList[0];
                                            if (ps.lotteryType == 1) {
                                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + v.prop.qszt + '</li><li class="width53px">' + v.prop.hszt + "</li></ul>";
                                            } else if (ps.lotteryType == 2) {
                                                var str = '<ul><li class="width247px">' + v.issue + '</li><li class="width247px">' + v.code + "</li>";
                                            } else if (ps.lotteryType == 4) {
                                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + v.prop.qszt + '</li><li class="width53px">' + v.prop.qshz + "</li></ul>";
                                            } else if (ps.lotteryType == 6) {
                                                var tmp = v.code.split("");
                                                var hz = parseInt(tmp[0]) + parseInt(tmp[1]) + parseInt(tmp[2]);
                                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + hz + '</li><li class="width53px">' + (hz > 10 ? "大" : "小") + (hz % 2 ? "单" : "双") + "</li></ul>";
                                            } else if (ps.lotteryType == 7) {
                                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width_poker">' + drawBar.pokerOpenCode(v.code) + '</li><li class="width60px">' + drawBar.getMoreInfo(v.code).pkzt + "</li></ul>";
                                            }

                                            $("#todayIssuesBody").prepend(str);
                                        }
                                        drawBar.showLastDraw();
                                    } else {}
                                }
                            } else {
                                alert("系统繁忙，请稍候再试(03)");
                            }
                        },
                        error: function error(XMLHttpRequest, textStatus, errorThrown) {
                            if (errorThrown.indexOf("a=logout") != -1 || errorThrown.indexOf("a=login") != -1) {
                                top.layer.alert("您已经超时退出，请重新登录", { icon: 7 });
                                window.location.href = "?a=logout";
                            } else {
                                top.layer.alert("操作超时，请刷新页面...", { icon: 7 });
                            }
                        }
                    });
                }
            },
            //显示上一期开奖结果
            showLastDraw: function showLastDraw() {
                var latest = ps.todayDrawList[0];
                $("#lastIssueSpan").text(ps.lastIssueInfo.issue);
                var str;
                if (ps.lastIssueInfo.issue == latest.issue) {
                    if (ps.lotteryType == 1) {
                        var nums = latest.code.split("");
                        str = "<ul><li>三星形态:[<span>" + latest.prop.hszt + "</span>]</li><li>三星和值:[<span>" + latest.prop.hshz + "</span>]</li><li>二星和值:[<span>" + latest.prop.hehz + '</span>]</li><li class="last">大小单双:[<span>' + latest.prop.dxds + "</span>]</li></ul>";
                    } else if (ps.lotteryType == 2) {
                        var nums = latest.code.split(" ");
                        str = "<ul><li>第一位：<span>" + nums[0] + "</span></li><li>第二位：<span>" + nums[0] + "," + nums[1] + "</span></li></ul><ul><li>第三位：<span>" + nums[0] + "," + nums[1] + "," + nums[2] + "</span></li></ul>";
                    } else if (ps.lotteryType == 4) {
                        var nums = latest.code.split("");
                        str = "<ul><li>三星形态:[<span>" + latest.prop.qszt + "</span>]</li><li>三星和值:[<span>" + latest.prop.qshz + "</span>]</li></ul>";
                    } else if (ps.lotteryType == 6) {
                        var nums = latest.code.split("");
                        var hzNum = parseInt(nums[0]) + parseInt(nums[1]) + parseInt(nums[2]);
                        str = '<ul><li class="last">形态：[ <span class="bs">' + (hzNum > 10 ? '大' : '小') + '</span> <span class="oe">' + (hzNum % 2 ? '单' : '双') + '</span> ]&nbsp;&nbsp;和值：[ <span class="num">' + hzNum + '</span> ]</li></ul>';
                        $("#openCode").html(latest.code);
                    } else if (ps.lotteryType == 7) {
                        var nums = latest.code.split(" ");
                        str = '<ul><li class="last">形态：' + drawBar.getMoreInfo(latest.code).pkzt + '</li></ul>';
                        //str = '<ul><li class="last">形态：dwc</li></ul>';
                    } else {
                        throw new exception('无效的数据引用1');
                    }

                    /**
                     * 动画第二版
                     */
                    var index = 0,
                        index2 = 0;
                    if (ps.lotteryType == 7) {
                        var $cards = drawBar.translateCards(nums);
                    }
                    var openCodeCallback = function openCodeCallback() {
                        if ($("#thisIssueNumUL").children(".pendingBall").length > 0) {
                            var obj = $("#thisIssueNumUL").children(".pendingBall").first();
                            window.setTimeout(function () {
                                obj.removeClass("pendingBall").text(nums[index++]); //显示数字
                                if (ps.lotteryType == 2) {
                                    obj.addClass("sd115_Ball");
                                }
                                openCodeCallback();
                            }, 500);
                        } else if ($("#thisIssueNumUL").children(".k3").length > 0) {
                            //快三
                            var obj = $("#thisIssueNumUL").children(".k3").first();
                            window.setTimeout(function () {
                                obj.removeClass().addClass("pendingNum_k3_" + nums[index++]); //显示骰子数字
                                openCodeCallback();
                            }, 1000);
                        } else if ($("#thisIssueNumUL").children(".poker").length > 0) {
                            //扑克
                            var obj = $("#thisIssueNumUL").children(".poker").first();
                            //poker_kj_step1 poker_kj_step2 poker_kj_step3
                            $("#thisIssueNumUL").addClass("poker_kj_step" + ++index); //翻牌效果
                            obj.removeClass('poker');
                            window.setTimeout(function () {
                                obj.find('span').removeClass("poker_kj_wait poker_kj_wait" + (index2 + 1)).addClass($cards[index2].className); //显示扑克
                                obj.find('em').text($cards[index2].num);
                                index2++;
                                openCodeCallback();
                            }, 2000);
                        }
                    };
                    window.setTimeout(openCodeCallback, 100); //需要间隔时间触发才转
                } else {
                    if (ps.lotteryType == 6) {
                        $.each($("#thisIssueNumUL").children(), function (i, n) {
                            $(this).removeClass().addClass("pendingNum_k3" + (i + 1) + " k3"); //恢复转动的骰子的
                        });
                    } else if (ps.lotteryType == 7) {
                        $("#thisIssueNumUL").removeClass('poker_kj_step1 poker_kj_step2 poker_kj_step3');
                        $.each($("#thisIssueNumUL").children(), function (i, n) {
                            $(this).removeClass().addClass("pendingNum_poker" + " poker");
                        });
                    } else {
                        $("#thisIssueNumUL").children().addClass("pendingBall").removeClass("sd115_Ball");
                    }

                    if (ps.lotteryType == 1) {
                        str = '<ul><li>三星形态:[<span></span>]</li><li>二星和值:[<span></span>]</li><li>三星和值:[<span></span>]</li><li class="last">大小单双:[<span></span>]</li></ul>';
                    } else if (ps.lotteryType == 2) {
                        str = "<ul><li>第一位：<span></span></li><li>第二位：<span></span></li><li>第三位：<span></span></li></ul>";
                    } else if (ps.lotteryType == 4) {
                        str = "<ul><li>三星形态:[<span></span>]</li><li>三星和值:[<span></span>]</li></ul>";
                    } else if (ps.lotteryType == 6) {
                        str = '<ul><li class="last">形态：[ <span class="bs">-</span> <span class="oe">-</span> ]&nbsp;&nbsp;和值：[ <span class="num">-</span> ]</li></ul>';
                        $("#openCode").html('');
                    } else if (ps.lotteryType == 7) {
                        str = '<ul><li class="last">形态：</li></ul>';
                    } else {
                        throw new exception('无效的数据引用2');
                    }
                }
                $("#thisIssueMoreInfo").html(str);
            },
            //今日开奖
            todayDrawBtn_Click: function todayDrawBtn_Click() {
                $("#todayIssuesHead").empty().show();
                $("#todayIssuesBody").empty().show();
                $("#prizeScrollContent").hide();
                if (ps.lotteryType == 1) {
                    $("#todayIssuesHead").html('<li class="width80px">期号</li><li class="width60px">开奖号</li><li class="width55px">前3组态</li><li class="width55px">后3组态</li>');
                } else if (ps.lotteryType == 2) {
                    $("#todayIssuesHead").html('<li class="width247px">期号</li><li class="width247px">开奖号</li>');
                } else if (ps.lotteryType == 4) {
                    $("#todayIssuesHead").html('<li class="width80px">期号</li><li class="width60px">开奖号</li><li class="width55px">三星组态</li><li class="width55px">三星和值</li>');
                } else if (ps.lotteryType == 6) {
                    $("#todayIssuesHead").html('<li class="width80px">期号</li><li class="width60px">开奖号</li><li class="width55px">和值</li><li class="width55px">形态</li>');
                } else if (ps.lotteryType == 7) {
                    $("#todayIssuesHead").html('<li class="width80px">期号</li><li style="width:120px">开奖号</li><li class="width55px">形态</li>');
                } else {
                    throw exception('unknown lt type');
                }
                $("#todayIssuesBody").empty();
                $.each(ps.todayDrawList, function (k, v) {
                    if (ps.lotteryType == 1) {
                        var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + v.prop.qszt + '</li><li class="width53px">' + v.prop.hszt + "</li></ul>";
                    } else if (ps.lotteryType == 2) {
                        var str = '<ul><li class="width247px">' + v.issue + '</li><li class="width247px">' + v.code + "</li>";
                    } else if (ps.lotteryType == 4) {
                        var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + v.prop.qszt + '</li><li class="width53px">' + v.prop.qshz + "</li></ul>";
                    } else if (ps.lotteryType == 6) {
                        var tmp = v.code.split("");
                        var hz = parseInt(tmp[0]) + parseInt(tmp[1]) + parseInt(tmp[2]);
                        var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + hz + '</li><li class="width53px">' + (hz > 10 ? "大" : "小") + (hz % 2 ? "单" : "双") + "</li></ul>";
                    } else if (ps.lotteryType == 7) {
                        var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width_poker">' + drawBar.pokerOpenCode(v.code) + '</li><li class="width60px">' + drawBar.getMoreInfo(v.code).pkzt + "</li></ul>";
                    } else {
                        throw exception('unknown lt type');
                    }
                    $("#todayIssuesBody").append(str);
                });
            },
            //中奖排行榜
            prizeRankBtn_Click: function prizeRankBtn_Click() {
                $("#todayIssuesHead").empty().hide();
                $("#todayIssuesBody").empty().hide();
                $.post("?c=game&a=play", {
                    op: "getPrizeRank",
                    lotteryId: ps.lotteryId
                }, function (response) {
                    if (response.errno == 0) {
                        if (response.data.length == 0) {
                            $('<div class="todayNoBet">暂时没有记录！</div>').appendTo("#prizeScrollContent");
                        } else {
                            $("#prizeScrollContent").empty();
                            $.each(response.data, function (k, v) {
                                $('<ul><li><span>' + v.nick_name + "</span> 喜中 <em>" + number_format(v.total_prize, 2) + "</em>元</li></ul>").appendTo($("#prizeScrollContent"));
                            });
                            $("#prizeScrollContent").html($("#prizeScrollContent").html() + $("#prizeScrollContent").html()).show();
                            if (runTime.scollTopIntervalTimer == 0) {
                                runTime.scollTopIntervalTimer = window.setInterval(drawBar.scrollPrizeRank, 100);
                                $("#prizeScrollContent").mouseover(function () {
                                    clearInterval(runTime.scollTopIntervalTimer);
                                }).mouseout(function () {
                                    runTime.scollTopIntervalTimer = window.setInterval(drawBar.scrollPrizeRank, 100);
                                });
                            }
                        }
                    } else {
                        alert("暂时没有数据！");
                    }
                }, "json");
            },
            //今日投注
            todayBuyBtn_Click: function todayBuyBtn_Click() {
                $.post("?c=game&a=play", {
                    op: "getCurContextIssues",
                    lotteryId: ps.lotteryId
                }, function (response) {
                    $("#todayIssuesHead").empty().show();
                    $("#todayIssuesBody").empty().show();
                    $("#prizeScrollContent").hide();
                    if (response.errno == 0) {
                        $.each(response.issueInfos, function (k, v) {
                            $('<li name="' + v.issue + '" class="todayRecentIssues"' + (k == 2 ? " style='width:80px;'" : "") + '>' + helper.getNumByIssue(v.issue) + (k == 2 ? "(当前期)" : "") + "</li>").click(function () {
                                $(this).addClass("yellow").siblings().removeClass("yellow");
                                $.post("?c=game&a=play", {
                                    op: "getBuyRecords",
                                    lotteryId: ps.lotteryId,
                                    issue: $(this).attr("name")
                                }, function (response) {
                                    if (response.errno == 0) {
                                        $("#todayIssuesBody").empty();
                                        $('<ul><li class="C1">玩法类型</li><li class="C2">投注内容</li><li class="C3">倍数</li><li class="C4">金额</li><li class="C5">状态</li></ul>').appendTo("#todayIssuesBody");
                                        if (response.prj.length == 0) {
                                            $('<div class="todayNoBet">暂时没有记录！</div>').appendTo("#todayIssuesBody");
                                        } else {
                                            $.each(response.prj, function (k, v) {
                                                // 宽度有限 奖金一栏不要了'</li><li style="width:80px">' + v.prize
                                                //快乐扑克10转成注码T
                                                if (ps.lotteryType == 7) {
                                                    v.code = v.code.replace(/T/g, '10');
                                                }
                                                $('<ul><li class="C1"><a href="javascript:showPackageDetail(\'' + v.wrapId + '\');">' + v.methodName + '</a></li><li class="C2">' + v.code + '</li><li class="C3">' + v.multiple + '</li><li class="C4">' + v.amount + '</li><li class="C5">' + v.prizeStatus + "</li></ul>").click(function () {
                                                    //为适应客户端statusText，这里不再用jq定义
                                                    //window.open("?c=game&a=packageDetail&wrap_id=" + v.wrapId, "_blank")
                                                }).appendTo("#todayIssuesBody");
                                            });
                                        }
                                    } else {
                                        alert("系统繁忙，请稍候再试(04)");
                                    }
                                }, "json");
                            }).appendTo("#todayIssuesHead");
                        });
                        $("#todayIssuesHead li:eq(2)").click();
                    } else {
                        alert("系统繁忙，请稍候再试(05)");
                    }
                }, "json");
            },
            scrollPrizeRank: function scrollPrizeRank() {
                var obj = $("#prizeScrollContent")[0];
                obj.scrollTop += 1;
                if (obj.scrollTop >= $("#prizeScrollContent")[0].scrollHeight / 2) {
                    obj.scrollTop = 0;
                }
            },
            getMoreInfo: function getMoreInfo(code) {
                //得到扩展信息
                var $result = {};
                if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                    var nums = code.split("");
                    $result.qshz = parseInt(nums[0]) + parseInt(nums[1]) + parseInt(nums[2]);
                    $result.qszt = drawBar.zutai(nums[0], nums[1], nums[2]);
                    $result.hshz = parseInt(nums[2]) + parseInt(nums[3]) + parseInt(nums[4]);
                    $result.hszt = drawBar.zutai(nums[2], nums[3], nums[4]);
                    $result.hehz = parseInt(nums[3]) + parseInt(nums[4]);
                    $result.dxds = drawBar.dxds(nums[3], nums[4]);
                } else if (ps.lotteryType == 7) {
                    var nums = code.split(" ");
                    $result.pkzt = drawBar.pokerZutai(nums[0], nums[1], nums[2]);
                }

                return $result;
            },
            translateT: function translateT(num) {
                if (num == 'T') {
                    return '10';
                }
                return num;
            },
            translateCards: function translateCards($nums) {
                var $result = [],
                    $tmp;
                for (var i = 0; i < $nums.length; i++) {
                    $tmp = { num: "", className: "" };
                    switch ($nums[i].charAt(0)) {
                        case 'T':
                            $tmp.num = '10';
                            break;
                        default:
                            $tmp.num = $nums[i].charAt(0);
                            break;
                    }

                    switch ($nums[i].charAt(1)) {
                        case 's':
                            $tmp.className = 'poker_heit';
                            break;
                        case 'h':
                            $tmp.className = 'poker_hongt';
                            break;
                        case 'c':
                            $tmp.className = 'poker_meih';
                            break;
                        case 'd':
                            $tmp.className = 'poker_fangk';
                            break;
                        default:
                            throw new exception('unknown color');
                            break;
                    }
                    $result.push($tmp);
                }

                return $result;
            },

            zutai: function zutai(a, b, c) {
                if (a == b && a == c && b == c) {
                    return "豹子";
                } else {
                    if (a == b || a == c || b == c) {
                        return "组三";
                    } else {
                        return "组六";
                    }
                }
            },
            dxds: function dxds(n1, n2) {
                var a = [],
                    b = [];
                a.push(n1 >= 5 ? "大" : "小");
                a.push(n1 % 2 == 1 ? "单" : "双");
                b.push(n2 >= 5 ? "大" : "小");
                b.push(n2 % 2 == 1 ? "单" : "双");
                return [a[0] + b[0], a[0] + b[1], a[1] + b[0], a[1] + b[1]].join(",");
            },
            //扑克组态
            pokerZutai: function pokerZutai(a, b, c) {
                var num = [helper.pokerNumMaps[a.charAt(0)], helper.pokerNumMaps[b.charAt(0)], helper.pokerNumMaps[c.charAt(0)]];
                num.sort();
                if (a.charAt(0) == b.charAt(0) && a.charAt(0) == c.charAt(0) && b.charAt(0) == c.charAt(0)) {
                    return "豹子";
                } else if (num[0] + 1 == num[1] && num[1] + 1 == num[2] || num[0] == 1 && num[1] == 12 && num[2] == 13) {
                    if (a.charAt(1) == b.charAt(1) && a.charAt(1) == c.charAt(1) && b.charAt(1) == c.charAt(1)) {
                        return "同花顺";
                    } else {
                        return "顺子";
                    }
                } else if (a.charAt(1) == b.charAt(1) && a.charAt(1) == c.charAt(1) && b.charAt(1) == c.charAt(1)) {
                    return "同花";
                } else if (a.charAt(0) == b.charAt(0) || b.charAt(0) == c.charAt(0) || a.charAt(0) == c.charAt(0)) {
                    return "对子";
                } else {
                    return "散牌";
                }
            }
        };
        function subTime(t) {
            var ob = t > 0 ? {
                day: Math.floor(t / 86400),
                hour: Math.floor(t % 86400 / 3600),
                minute: Math.floor(t % 3600 / 60),
                second: Math.floor(t % 60)
            } : {
                day: 0,
                hour: 0,
                minute: 0,
                second: 0
            };
            if ((ob.hour + "").length == 1) {
                ob.hour = "0" + ob.hour;
            }
            if ((ob.minute + "").length == 1) {
                ob.minute = "0" + ob.minute;
            }
            if ((ob.second + "").length == 1) {
                ob.second = "0" + ob.second;
            }
            return ob;
        }
        if (!verifyParams()) {
            parent.layer.alert("数据初始化失败", { icon: 2 });
            return false;
        }
        initPrizeBar();
        initModesBar();
        initMethodBar();
        initMissHotBar();
        initBuyBar();
        initDrawBar();

        //判断是否每区都有值
        function allHasValue(cds) {
            var flag = 1,
                charsNum = 0;
            $.each(cds, function (k, v) {
                charsNum += v.length;
                if (v.length == 0) {
                    flag = 0;
                }
            });
            return {
                flag: flag,
                charsNum: charsNum
            };
        }

        var isLegalCode = function isLegalCode(codes) {

            //这一段加上否则直选和值类玩法不选号也能添加
            if (allHasValue(codes)['charsNum'] == 0) {
                return {
                    singleNum: 0,
                    isDup: 0
                };
            }

            var singleNum = 0,
                isDup = 0,
                parts;
            switch (ps.curMethod.name) {
                case 'SXZX': //三星直选 12,34,567
                case "ZSZX":
                case 'QSZX':
                    //前三直选
                    singleNum = codes[0].length * codes[1].length * codes[2].length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SXZS': //三星组三
                case "ZSZS":
                case 'QSZS':
                    singleNum = codes[0].length * (codes[0].length - 1);
                    isDup = singleNum > 2 ? 1 : 0;
                    break;
                case 'SXZL': //三星组六  1234
                case "ZSZL":
                case 'QSZL':
                    singleNum = codes[0].length * (codes[0].length - 1) * (codes[0].length - 2) / helper.factorial(3);
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SXLX': //三星连选 12345,123,58
                case "ZSLX":
                case 'QSLX':
                    //每区都必须有数字
                    if (allHasValue(codes)['flag'] == 0) {
                        return {
                            singleNum: 0,
                            isDup: 0
                        };
                    }

                    var $betNums3 = 0,
                        $betNums2 = 0,
                        $betNums1 = 0;
                    //算注数 后三注数+后二注数+后一注数
                    $betNums3 = codes[0].length * codes[1].length * codes[2].length;
                    $betNums2 = codes[1].length * codes[2].length;
                    $betNums1 = codes[2].length;
                    singleNum = $betNums3 + $betNums2 + $betNums1;
                    isDup = singleNum > 3 ? 1 : 0;
                    break;
                case 'SXBD': //三星包点 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果
                case "ZSBD":
                case 'QSBD':
                    parts = codes[0].split('_');
                    $.each(parts, function (k, v) {
                        singleNum += helper.SXBD[v];
                    });
                    isDup = parts.length > 1 ? 1 : 0;
                    break;
                case 'SXHHZX': //三星混合组选 仅支持单式手工录入 12,34,567
                case "ZSHHZX":
                case 'QSHHZX':
                    //前三混合组选 仅支持单式手工录入 12,34,567
                    singleNum = codes[0].length * codes[1].length * codes[2].length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'EXZX': //二星直选 0123456789,0123456789
                case 'QEZX':
                    singleNum = codes[0].length * codes[1].length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'EXZUX': //二星组选 0123456789
                case 'QEZUX':
                    singleNum = codes[0].length * (codes[0].length - 1) / 2;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'EXLX': //二星连选 0123456789,0123456789
                case 'QELX':
                    //每区都必须有数字
                    if (allHasValue(codes)['flag'] == 0) {
                        return {
                            singleNum: 0,
                            isDup: 0
                        };
                    }

                    //算注数 后二注数+后一注数
                    var $betNums2 = 0,
                        $betNums1 = 0;
                    $betNums2 = codes[0].length * codes[1].length;
                    $betNums1 = codes[1].length;
                    singleNum = $betNums2 + $betNums1;
                    isDup = singleNum > 2 ? 1 : 0;
                    break;
                case 'EXBD': //二星包点 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果
                case 'QEBD':
                    parts = codes[0].split('_');
                    $.each(parts, function (k, v) {
                        singleNum += helper.EXBD[v];
                    });
                    isDup = parts.length > 1 ? 1 : 0;
                    break;
                case 'YXZX':
                    //一星直选
                    singleNum = codes[0].length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'WXDW':
                    //五星定位
                    var n = 4; //5!
                    for (var i = 0; i < 5; i++) {
                        if (codes[i] != '-') {
                            singleNum += codes[i].length;
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SXDW':
                    //低频特有 三星定位
                    singleNum = codes[0].length + codes[1].length + codes[2].length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'EMBDW': //三星二码不定位 一注仅限一组号码，如1,2，因为奖金本来就低，也为了判断方便
                case 'QSEMBDW': //新增前三二码
                case 'ZSEMBDW': //新增中三二码
                case 'SXEMBDW': //新增四星二码不定位
                case 'WXEMBDW':
                    //新增五星二码不定位
                    singleNum = codes[0].length * (codes[0].length - 1) / 2;
                    isDup = 0;
                    break;
                case 'WXSMBDW':
                    //新增五星三码不定位
                    singleNum = codes[0].length * (codes[0].length - 1) * (codes[0].length - 2) / 6;
                    isDup = 0;
                    break;
                case 'EXDXDS': //二星大小单双 一注仅限一个号码 因为奖金本来就低
                case 'QEDXDS':
                    //低频3D特有 前二大小单双 一注仅限一个号码 因为奖金本来就低
                    singleNum = codes[0].length * codes[1].length == 1 ? 1 : 0;
                    isDup = 0;
                    break;
                case 'SXDXDS':
                    //三星大小单双 一注仅限一个号码 因为奖金本来就低
                    singleNum = codes[0].length * codes[1].length * codes[2].length == 1 ? 1 : 0;
                    isDup = 0;
                    break;
                case 'YMBDW': //三星一码不定位 一注仅限一个号码，如1，因为奖金本来就低，也为了判断方便
                case 'ZSYMBDW': //新增中三一码不定位
                case 'SXYMBDW': //新增四星一码不定位
                case 'WXYMBDW': //新增五星一码不定位
                case 'QSYMBDW':
                    //低频P3P5特有 前三一码不定位
                    singleNum = codes[0].length;
                    isDup = 0;
                    break;
                case 'SXHZ': //三星和值 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果
                case "ZSHZ":
                case 'QSHZ':
                    parts = codes[0].split('_');
                    $.each(parts, function (k, v) {
                        singleNum += helper.SXHZ[v];
                    });
                    isDup = parts.length > 1 ? 1 : 0;
                    break;
                case 'EXHZ': //二星和值 一注可以有多个号码 不同号码之间要用_分隔 因为有大于9的结果
                case 'QEHZ':
                    parts = codes[0].split('_');
                    $.each(parts, function (k, v) {
                        singleNum += helper.EXHZ[v];
                    });
                    isDup = parts.length > 1 ? 1 : 0;
                    break;
                case 'SXZXHZ': //低频3D特有 组选和值
                case 'QSZXHZ':
                    //低频P3P5特有 组选和值
                    parts = codes[0].split('_');
                    $.each(parts, function (k, v) {
                        singleNum += helper.SXZXHZ[v];
                    });
                    isDup = parts.length > 1 ? 1 : 0;
                    break;
                case 'SIXZX': //四星直选 12,34,567
                case 'QSIZX':
                    //前四直选
                    singleNum = codes[0].length * codes[1].length * codes[2].length * codes[3].length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'WXZX':
                    //五星直选
                    //算注数 相乘即可
                    singleNum = codes[0].length * codes[1].length * codes[2].length * codes[3].length * codes[4].length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'WXLX':
                    //五星连选
                    //每区都必须有数字
                    if (allHasValue(codes)['flag'] == 0) {
                        return {
                            singleNum: 0,
                            isDup: 0
                        };
                    }

                    var $betNums5 = 0,
                        $betNums3 = 0,
                        $betNums2 = 0,
                        $betNums1 = 0;
                    //算注数 后三注数+后二注数+后一注数
                    $betNums5 = codes[0].length * codes[1].length * codes[2].length * codes[3].length * codes[4].length;
                    $betNums3 = codes[2].length * codes[3].length * codes[4].length;
                    $betNums2 = codes[3].length * codes[4].length;
                    $betNums1 = codes[4].length;
                    singleNum = $betNums5 + $betNums3 + $betNums2 + $betNums1;
                    isDup = singleNum > 4 ? 1 : 0;
                    break;

                //========== sd11y ===========//
                case 'REZX':
                    //任二直选
                    var n = 4; //5!
                    for (var i = 0; i < 4; i++) {
                        //如果注码不写'-'的话可以省略两个if判断,效率差不多
                        if (codes[i] != '-') {
                            for (var j = i + 1; j < 5; j++) {
                                if (codes[j] != '-') {
                                    singleNum += codes[i].length * codes[j].length;
                                }
                            }
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'RSZX':
                    //任三直选      xxxxxx
                    for (var i = 0; i < 3; i++) {
                        if (codes[i] != '-') {
                            for (var j = i + 1; j < 4; j++) {
                                if (codes[j] != '-') {
                                    for (var k = j + 1; k < 5; k++) {
                                        if (codes[k] != '-') {
                                            singleNum += codes[i].length * codes[j].length * codes[k].length;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'RSIZX':
                    //任四直选["678", "67", "7", "7", "7"]
                    for (var g = 0; g < 2; g++) {
                        if (codes[g] != '-') {
                            for (var i = g + 1; i < 3; i++) {
                                if (codes[i] != '-') {
                                    for (var j = i + 1; j < 4; j++) {
                                        if (codes[j] != '-') {
                                            for (var k = j + 1; k < 5; k++) {
                                                if (codes[k] != '-') {
                                                    singleNum += codes[g].length * codes[i].length * codes[j].length * codes[k].length;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDQSZX':
                    //前三直选 01_02_03_04,02_03,01_05
                    if (codes.length != 3) {
                        return {
                            singleNum: 0,
                            isDup: 0
                        };
                    }
                    var result = helper.expandLotto(codes);
                    singleNum = result.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDQEZX':
                    //前二直选 二段 01_02_03_04,02_03
                    if (codes.length != 2) {
                        return {
                            singleNum: 0,
                            isDup: 0
                        };
                    }
                    var result = helper.expandLotto(codes);

                    singleNum = result.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDQSZUX':
                    //前三组选 一段 01_02_03_04
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) / helper.factorial(3);
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDQEZUX':
                    //前二组选 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) / 2;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX1':
                    //任选1 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX2':
                    //任选2 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) / 2;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX3':
                    //任选3 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) / 6;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX4':
                    //任选4 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) * (parts.length - 3) / 24;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX5':
                    //任选5 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) * (parts.length - 3) * (parts.length - 4) / 120;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX6':
                    //任选6 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) * (parts.length - 3) * (parts.length - 4) * (parts.length - 5) / 720;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX7':
                    //任选7 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) * (parts.length - 3) * (parts.length - 4) * (parts.length - 5) * (parts.length - 6) / 5040;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDRX8':
                    //任选8 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) * (parts.length - 3) * (parts.length - 4) * (parts.length - 5) * (parts.length - 6) * (parts.length - 7) / 40320;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDQSBDW':
                    //前3不定位胆 一段 01_02_03_04_05_06_07_08_09_10_11
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'SDQSDWD':
                    //前3定位胆 01_02_03,04_05,06_07为一单 也可以只买一位，如'01_02_03,,'表示只买个位胆，没买的位留空
                    $.each(codes, function (k, v) {
                        if (v != '') {
                            //号码不得重复
                            parts = v.split('_');
                            singleNum += parts.length; //注意是数组长度，所以前面必须判断v != ''
                        }
                    });
                    isDup = singleNum > 3 ? 1 : 0;
                    break;
                case 'SDDDS': //0单5双:750.0000元 (1注) 5单0双:125.0000元 (6注)1单4双:25.0000元 (30注)4单1双:10.0000元 (75注)2单3双:5.0000元 (150注)3单2双:3.7000元 (200注)
                case 'SDCZW':
                    // 一次只能选一注
                    singleNum = 1;
                    isDup = 1;
                    break;

                case 'YFFS': //趣味玩法,一帆风顺
                case 'HSCS': //趣味玩法,好事成双
                case 'SXBX': //趣味玩法,三星报喜
                case 'SJFC':
                    //趣味玩法,四季发财
                    singleNum = codes[0].length; //传来的数据模式 13567
                    isDup = singleNum > 1 ? 1 : 0;
                    break;

                case 'ZUX120':
                    //组选120
                    if (codes[0].length > 4) {
                        singleNum = codes[0].length === 5 ? 1 : helper.factorial(codes[0].length) / (helper.factorial(codes[0].length - 5) * 120);
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'ZUX24':
                    //组选24
                    if (codes[0].length > 3) {
                        singleNum = helper.factorial(codes[0].length) / (helper.factorial(codes[0].length - 4) * 24);
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'ZUX6':
                    //组选6
                    if (codes[0].length > 1) {
                        singleNum = helper.factorial(codes[0].length) / (helper.factorial(codes[0].length - 2) * 2);
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;

                case 'ZUX10': //组选10
                case 'ZUX5': //组选5
                case 'ZUX4':
                    //组选4
                    if (codes[0].length > 0 && codes[1].length > 0) {
                        var compareNum = codes[1].length;
                        for (i = 0; i < codes[0].length; i++) {
                            var tmp = compareNum;
                            if (codes[1].indexOf(codes[0].substr(i, 1)) > -1) {
                                tmp = compareNum - 1;
                            }
                            if (tmp > 0) {
                                singleNum += helper.factorial(tmp) / helper.factorial(tmp - 1);
                            }
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;

                case 'ZUX20': //组选20
                case 'ZUX12':
                    //组选12
                    if (codes[0].length > 0 && codes[1].length > 1) {
                        var compareNum = codes[1].length;
                        for (i = 0; i < codes[0].length; i++) {
                            var tmp = compareNum;
                            if (codes[1].indexOf(codes[0].substr(i, 1)) > -1) {
                                tmp = compareNum - 1;
                            }
                            if (tmp > 1) {
                                singleNum += helper.factorial(tmp) / (helper.factorial(tmp - 2) * 2);
                            }
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;

                case 'ZUX60':
                    //组选60
                    if (codes[0].length > 0 && codes[1].length > 2) {
                        var compareNum = codes[1].length;
                        for (i = 0; i < codes[0].length; i++) {
                            var tmp = compareNum;
                            if (codes[1].indexOf(codes[0].substr(i, 1)) > -1) {
                                tmp = compareNum - 1;
                            }
                            if (tmp > 2) {
                                singleNum += helper.factorial(tmp) / (helper.factorial(tmp - 3) * 6);
                            }
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'ZUX30':
                    //组选30
                    if (codes[0].length > 1 && codes[1].length > 0) {
                        var compareNum = codes[0].length;
                        for (i = 0; i < codes[1].length; i++) {
                            var tmp = compareNum;
                            if (codes[0].indexOf(codes[1].substr(i, 1)) > -1) {
                                tmp = compareNum - 1;
                            }
                            if (tmp > 1) {
                                singleNum += helper.factorial(tmp) / (helper.factorial(tmp - 2) * 2);
                            }
                        }
                    }
                    isDup = singleNum > 1 ? 1 : 0;
                    break;

                //江苏快三
                case 'JSETDX':
                    //二同单选 2个号区 11_22,34
                    if (codes.length != 2) {
                        return {
                            singleNum: 0,
                            isDup: 0
                        };
                    }
                    var parts0 = codes[0].length ? codes[0].split('_') : [];
                    var parts1 = codes[1].length ? codes[1].split('') : [];
                    singleNum = parts0.length * parts1.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'JSETFX':
                    //二同复选 1个号区 11_22_33
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;

                case 'JSHZ':
                    //快三和值
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = parts.length > 1 ? 1 : 0;
                    break;
                case 'JSSTTX':
                    //快三   江苏三同号通选
                    //parts = codes[0].split('_');  //111_222_333_444_555_666
                    singleNum = 1;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'JSSLTX':
                    //快三三连号通选
                    singleNum = 1;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'JSEBT':
                    //二不同号
                    var codesLen = codes[0].length;
                    singleNum = (codesLen - 1) * codesLen / 2;
                    isDup = codesLen > 2 ? 1 : 0;
                    break;
                case 'JSSTDX':
                    //三同号单选
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = parts.length > 1 ? 1 : 0;
                    break;
                case 'JSSBT':
                    //三不同号
                    var codesLen = codes[0].length;
                    singleNum = (codesLen - 1) * (codesLen - 2) * codesLen / 6;
                    isDup = codesLen > 3 ? 1 : 0;
                    break;

                //快乐扑克
                case 'PKSZ':
                    //顺子
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKBZ':
                    //豹子
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKDZ':
                    //对子
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKTH':
                    //同花
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKTHS':
                    //同花顺
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKBX':
                    //包选
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKRX1':
                    //任选1
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKRX2':
                    //任选2
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) / 2;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKRX3':
                    //任选3
                    parts = codes[0].split('_');
                    singleNum = parts.length * (parts.length - 1) * (parts.length - 2) / 6;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKRX4':
                    //任选4
                    parts = codes[0].split('_');
                    singleNum = parts.length;
                    var codeNum = parts.length;
                    singleNum = codeNum * (codeNum - 1) * (codeNum - 2) * (codeNum - 3) / 24;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKRX5':
                    //任选5
                    parts = codes[0].split('_');
                    var codeNum = parts.length;
                    singleNum = codeNum * (codeNum - 1) * (codeNum - 2) * (codeNum - 3) * (codeNum - 4) / 120;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                case 'PKRX6':
                    //任选6
                    parts = codes[0].split('_');
                    var codeNum = parts.length;
                    singleNum = codeNum * (codeNum - 1) * (codeNum - 2) * (codeNum - 3) * (codeNum - 4) * (codeNum - 5) / 720;
                    isDup = singleNum > 1 ? 1 : 0;
                    break;
                default:
                    throw "unknown method2 " + ps.curMethod.name;
                    break;
            }

            return {
                singleNum: singleNum,
                isDup: isDup
            };
        };
    };
})(jQuery);