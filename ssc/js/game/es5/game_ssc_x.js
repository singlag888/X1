if (typeof console != "object") {
    var console = {
        info: function (a) {
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
    buyTodayTimer: 0,
    clearAll: function () {
        clearInterval(runTime.remainTimer);
        clearInterval(runTime.waitOpenTimer);
        clearInterval(runTime.getLastOpenTimer);
        clearInterval(runTime.scollTopIntervalTimer);
        clearInterval(runTime.traceRemainTimer);
        clearInterval(runTime.traceWaitOpenTimer);
        clearInterval(runTime.buyTodayTimer);
    }
};

(function ($) {

    $.init = function (settings) {
        var KaiQiZhiXuanMoShi = false;

        //检查传过来的参数的正确性
        var verifyParams = function () {
            var flag = 0;
            if (settings.lotteryId == undefined || !is_numeric(settings.lotteryId) || settings.lotteryId <= 0) {
                flag = -1;
            } else if (settings.lotteryName == undefined || settings.lotteryName == '') {
                flag = -2;
            } else if (settings.lotteryType == undefined || !is_numeric(settings.lotteryType) || $.inArray(settings.lotteryType, [1, 2, 4, 5, 6, 7, 8]) == -1) {
                flag = -3;
            } else if (settings.methods == undefined || !$.isArray(settings.methods) || settings.methods.length == 0) {
                flag = -4;
            } else if (settings.openedIssues == undefined || !$.isArray(settings.openedIssues) || settings.openedIssues.length == 0) {
                flag = -5;
            } else if (settings.minRebateGaps == undefined || !$.isArray(settings.minRebateGaps) || settings.minRebateGaps.length == 0) {
                flag = -6;
            } else if (settings.rebate == undefined || !is_numeric(settings.rebate) || settings.rebate < 0 || settings.rebate > 0.15) {
                flag = -7;
            } else if (settings.defaultMode == undefined || !is_numeric(settings.defaultMode) || !$.inArray(settings.defaultMode, [1, 0.5, 0.1, 0.05, 0.01, 0.001]) == -1) {
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
            property_id: 1,
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
            halt: function (msg) {
                //致命错误处理
                alert(msg + '!');
                throw msg;
            },
            //运行时变量
            prizeRate: 0, //返奖率
            curIssueInfo: {}, //当前奖期{ issue_id="15712", issue="20130201-070", end_time="2013-02-01 17:48:30", input_time=2013-02-01 17:50:30"}
            lastIssueInfo: {}, //上一期{ issue_id="15712", issue="20130201-070", code="96983"}
            curMode: 0, //当前选择的元角分模式
            //curRebate: 0,       //当前选择的返点值=ps.rebateGapList.get(ps.curPrizeIndex).rebate，这里不再需要
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
            canTraceIssues: [], //可追号的期号列表
            counters: 0 //第一次加载
        }, settings);
        //更新当前期出结果的开奖期数
        function todayGetCurIssue(lastIssueInfo) {
            if (!lastIssueInfo.code) {
                return;
            }
            var codes = "";
            var codes2 = "";
            for (var i = 0; i < lastIssueInfo.code.length; i++) {
                codes += "<b>" + lastIssueInfo.code[i] + "</b>";
                codes2 += lastIssueInfo.code[i];
            }
            if (ps.lotteryType != 2) {
                $("#todayIssuesBody").prepend('<ul class="fix"><li class="wb40">' + lastIssueInfo.issue + '</li><li class="wb60">' + codes + '</li></ul>');
            } else {
                $("#todayIssuesBody").prepend('<ul class="fix"><li class="wb40">' + lastIssueInfo.issue + '</li><li class="wb60"><b>' + codes2.split(' ').join('</b><b>') + '</b></li></ul>');
            }
        }

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
            factorial: function (n) {
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
            getNumByIssue: function (issue) {
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
            expandLotto: function ($nums) {
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

        var tool = {
            uniqueArr: function (a) {
                var hash = {};
                len = a.length;
                result = [];

                for (var i = 0; i < len; i++) {
                    if (!hash[a[i]]) {
                        hash[a[i]] = true;
                        result.push(a[i]);
                    }
                }
                return result;
            }
        };

        var initModesBar = function () {
            var tmpMode = 0.5;
            var mod = parseFloat(getCookie("mod_" + ps.lotteryId));
            $.each([0.5], function (k, v) {
                if (v == mod) {
                    tmpMode = v;
                }
            });
            ps.curMode = tmpMode;

            //元角分厘点击事件
            /*$("#yuan").click(function(){
             $("#yuan").removeClass().addClass("yuan");
             $("#jiao").removeClass();
             $("#fen").removeClass();
             $("#li").removeClass();
             $("#yuanYi").removeClass();
             $("#jiaoYi").removeClass();
             $("#modesDIV").val(1);
             modesBar.modesBtn_Click();
             });
             $("#jiao").click(function(){
             $("#jiao").removeClass().addClass("jiao");
             $("#yuan").removeClass();
             $("#fen").removeClass();
             $("#li").removeClass();
             $("#yuanYi").removeClass();
             $("#jiaoYi").removeClass();
             $("#modesDIV").val(0.1);
             modesBar.modesBtn_Click();
             });
             $("#fen").click(function(){
             $("#fen").removeClass().addClass("fen");
             $("#jiao").removeClass();
             $("#yuan").removeClass();
             $("#li").removeClass();
             $("#yuanYi").removeClass();
             $("#jiaoYi").removeClass();
             $("#modesDIV").val(0.01);
             modesBar.modesBtn_Click();
             });
             $("#yuanYi").click(function(){
             $("#yuanYi").removeClass().addClass("yuanYi");
             $("#jiao").removeClass();
             $("#yuan").removeClass();
             $("#fen").removeClass();
             $("#jiaoYi").removeClass();
             $("#li").removeClass();
             $("#modesDIV").val(0.5);
             modesBar.modesBtn_Click();
             });
             $("#jiaoYi").click(function(){
             $("#jiaoYi").removeClass().addClass("jiaoYi");
             $("#jiao").removeClass();
             $("#yuan").removeClass();
             $("#yuanYi").removeClass();
             $("#fen").removeClass();
             $("#li").removeClass();
             $("#modesDIV").val(0.05);
             modesBar.modesBtn_Click();
             });
             $("#li").click(function(){
             $("#li").removeClass().addClass("li");
             $("#fen").removeClass();
             $("#jiao").removeClass();
             $("#yuan").removeClass();
             $("#yuanYi").removeClass();
             $("#jiaoYi").removeClass();
             $("#modesDIV").val(0.001);
             modesBar.modesBtn_Click();
             });
             switch (ps.curMode) {
             case 1:
             $("#yuan").click();
             break;
             case 0.5:
             $("#yuanYi").click();
             case 0.1:
             $("#jiao").click();
             break;
             case 0.05:
             $("#jiaoYi").click();
             break;
             case 0.01:
             $("#fen").click();
             break;
             case 0.001:
             $("#li").click();
             break;
             };*/
            // $('<option value="1">元</option><option value="0.1">角</option><option value="0.01">分</option><option value="0.001">厘</option>').prependTo("#modesDIV");
            // $("#modesDIV").change(modesBar.modesBtn_Click);
            // $("#modesDIV").val(ps.curMode);
        };

        var modesBar = {
            //点击模式按钮事件
            modesBtn_Click: function () {
                var curModeSpan = $("#modesDIV").val();
                if ($('#projectList').children('li').length > 0) {
                    parent.layer.confirm('切换元角分模式将影响您现有投注项，是否继续？', { icon: 7 }, function (i) {
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
                        parent.layer.close(i);
                    }, function (i) {
                        switch (ps.curMode) {
                            case '1':
                                $("#yuan").removeClass().addClass("yuan");
                                $("#jiao").removeClass().addClass("jiao_off");
                                $("#fen").removeClass().addClass("fen_off");
                                $("#li").removeClass().addClass("li_off");
                                $("#yuanYi").removeClass().addClass("yuanYi_off");
                                $("#jiaoYi").removeClass().addClass("jiaoYi_off");
                                break;
                            case '0.5':
                                $("#yuan").removeClass().addClass("yuan_off");
                                $("#jiao").removeClass().addClass("jiao_off");
                                $("#fen").removeClass().addClass("fen_off");
                                $("#li").removeClass().addClass("li_off");
                                $("#yuanYi").removeClass().addClass("yuanYi");
                                $("#jiaoYi").removeClass().addClass("jiaoYi_off");
                                break;
                            case '0.1':
                                $("#yuan").removeClass().addClass("yuan_off");
                                $("#jiao").removeClass().addClass("jiao");
                                $("#fen").removeClass().addClass("fen_off");
                                $("#li").removeClass().addClass("li_off");
                                $("#yuanYi").removeClass().addClass("yuanYi_off");
                                $("#jiaoYi").removeClass().addClass("jiaoYi_off");
                                break;
                            case '0.05':
                                $("#yuan").removeClass().addClass("yuan_off");
                                $("#jiao").removeClass().addClass("jiao_off");
                                $("#fen").removeClass().addClass("fen_off");
                                $("#li").removeClass().addClass("li_off");
                                $("#yuanYi").removeClass().addClass("yuanYi_off");
                                $("#jiaoYi").removeClass().addClass("jiaoYi");
                                break;
                            case '0.01':
                                $("#yuan").removeClass().addClass("yuan_off");
                                $("#jiao").removeClass().addClass("jiao_off");
                                $("#fen").removeClass().addClass("fen");
                                $("#li").removeClass().addClass("li_off");
                                $("#yuanYi").removeClass().addClass("yuanYi_off");
                                $("#jiaoYi").removeClass().addClass("jiaoYi_off");
                                break;
                            case '0.001':
                                $("#li").removeClass().addClass("li");
                                $("#yuan").removeClass().addClass("yuan_off");
                                $("#jiao").removeClass().addClass("jiao_off");
                                $("#fen").removeClass().addClass("fen_off");
                                $("#yuanYi").removeClass().addClass("yuanYi_off");
                                $("#jiaoYi").removeClass().addClass("jiaoYi_off");
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

                    //加上选择样式
                    //curModeSpan.parent().children().removeClass('colorRed').filter('[mode=' + ps.curMode + ']').addClass('colorRed');
                    //重置所有小球为未选择的状态
                    //ballBar.reset();
                    //保存所选模式
                    modesBar.saveLastModes();
                    prizeBar.showPirze(); //每点一下应更新对应的玩法
                }
            },
            saveLastModes: function () {
                //目前是保存到cookie里面
                setCookie('mod_' + ps.lotteryId, ps.curMode, 30 * 86400);
            }
        };

        //1.2 滑动奖金栏
        var initPrizeBar = function () {
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
            if (ps.rebateGapList.size == 0) {
                ps.halt("initPrizeBar failed");
            }
            // if(ps.lotteryType == 1){//如果是SSC 初始化时把大于1950的干掉
            //     ps.rebateGapList.forEach(function(v,k){
            //             if(ps.lotteryId == 10 && v.prize > 1920){//如果是3D 大于1920的干掉
            //                 ps.rebateGapList.devare(k);
            //             }
            // else if(v.prize > 1950) {
            //     ps.rebateGapList.devare(k);
            // }
            // });
            // } else if (ps.lotteryType == 2){//如果是115 初始化时把大于1901的干掉
            //     ps.rebateGapList.forEach(function(v,k){
            //             if(v.prize > 1901){
            //                 ps.rebateGapList.devare(k);
            //             }
            //         });
            // } else if (ps.lotteryType == 4){//如果是3D 初始化时把大于1920的干掉
            //     ps.rebateGapList.forEach(function(v,k){
            //             if(v.prize > 1920){
            //                 ps.rebateGapList.devare(k);
            //             }
            //         });
            // }
            ps.curPrizeIndex = ps.rebateGapList.size - 1;
            prizeBar.showPirze();
            $('#curPrizeSpan').change(prizeBar.changePrize);
        };

        //奖金滑动事件处理
        var prizeBar = {
            changePrize: function () {
                //     if (ps.curMethod.name == 'YMBDW' || ps.curMethod.name == 'QSYMBDW' || ps.curMethod.name == 'ZSYMBDW' || ps.curMethod.name == 'SXYMBDW' || ps.curMethod.name == 'WXYMBDW') {
                //         if (ps.rebateGapList.get(parseInt($("#curPrizeSpan").val())).prize > 1900) {
                //             var maxPrize = 0;
                //             ps.rebateGapList.forEach(function (v, k) {
                //                 if (v.prize <= 1900 && v.prize > maxPrize) {
                //                     ps.curPrizeIndex = parseInt(k);
                //                     maxPrize = v.prize;
                //                 }
                //             });
                //             var prizeOptions = $("#curPrizeSpan option").eq(ps.curPrizeIndex).html().split("/");

                //             switch (ps.curMethod.name) {
                //                 case 'YMBDW':
                //                 case 'QSYMBDW':
                //                 case 'ZSYMBDW':
                //                     sscmsg = '一码不定位最大奖金固定是￥' + prizeOptions[0] + '，不能再往上调节';
                //                     break;
                //                 case 'SXYMBDW':
                //                     sscmsg = '四星一码不定位最大奖金固定是￥' + prizeOptions[0] + '，不能再往上调节';
                //                     break;
                //                 case 'WXYMBDW':
                //                     sscmsg = '一码不定位最大奖金固定是￥' + prizeOptions[0] + '，不能再往上调节';
                //                     break;
                //             }
                //             $("#curPrizeSpan").val(ps.curPrizeIndex);
                //             // parent.layer.alert(sscmsg);
                //             var curIndex = ps.curPrizeIndex;
                //             var alert_msg = layer.confirm(sscmsg, {
                //                 closeBtn: 0,
                //                 icon: 7,
                //                 btn: ['确定']
                //             }, function () {
                //                 layer.close(alert_msg);
                //                 $("#curPrizeSpan").val(curIndex);
                //                 $('#rebateValue').html($('#curPrizeSpan option').eq(curIndex).html());
                //                 $('#selectRebate').slider('option', 'value', curIndex);
                //             });
                //             return;
                //         }
                //     }

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
                ps.curPrizeIndex = parseInt($("#curPrizeSpan").val());
                prizeBar.saveLastPrize();
                buyBar.updateTotalSingle();
            },
            generateGapList: function () {
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
                var result2 = new Map();
                result.forEach(function ($item, $i) {
                    var prize = 0;
                    if (ps.lotteryType == 7) {
                        prize = Math.floor(ps.maxCombPrize / 24 * (ps.prizeRate + $item), 0);
                    } else {
                        prize = round(ps.maxCombPrize * (ps.prizeRate + $item), 0);
                    }
                    result2.set($i, {
                        'rebate': round(ps.rebate - $item, 3),
                        'prize': prize
                    });
                });
                return result2;
            },
            //显示当前奖金
            showPirze: function () {
                if ($.isEmptyObject(ps.curMethod) !== true) {
                    $('#curPrizeSpan').empty();
                    ps.rebateGapList.forEach(function (v, k) {
                        var selectPrize1 = round(ps.curMode * ps.curMethod.prize[1] * (ps.prizeRate + ps.rebate - v.rebate) / ps.prizeRate, 4);
                        var selectPrize2 = round(ps.curMode * ps.curMethod.prize[2] * (ps.prizeRate + ps.rebate - v.rebate) / ps.prizeRate, 4);
                        var selectPrize3 = round(ps.curMode * ps.curMethod.prize[3] * (ps.prizeRate + ps.rebate - v.rebate) / ps.prizeRate, 4);
                        var selectPrize4 = round(ps.curMode * ps.curMethod.prize[4] * (ps.prizeRate + ps.rebate - v.rebate) / ps.prizeRate, 4);
                        var selectPrize5 = round(ps.curMode * ps.curMethod.prize[5] * (ps.prizeRate + ps.rebate - v.rebate) / ps.prizeRate, 4);
                        var selectPrize6 = round(ps.curMode * ps.curMethod.prize[6] * (ps.prizeRate + ps.rebate - v.rebate) / ps.prizeRate, 4);

                        // var selectPrize = round(ps.curMode * ps.curMethod.prize[1] * (ps.prizeRate + ps.rebate - v.rebate) / ps.prizeRate, 4);
                        var selectRebate = number_format(parseFloat(v.rebate) * 100, 1);

                        // if (ps.property_id == 1 && v.prize > 1950) {//如果时时彩奖金组大于1950的就不显示
                        //     return true;
                        // }

                        // if (ps.property_id == 2 && v.prize > 1901) {//如果115奖金组大于1901的就不显示
                        //     return true;
                        // }

                        // if (ps.property_id == 5 && v.prize > 1920) {//如果3D P3P5奖金组大于1920的就不显示
                        //     return true;
                        // }

                        // if (ps.property_id == 6 && v.prize > 1404) {//如果pk拾 奖金组大于1404的就不显示
                        //     return true;
                        // }
                        //$('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize + '/' + selectRebate + '%</option>');
                        if (ps.curMethod.name === 'SDDDS') {
                            $('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize1 + ',' + selectPrize2 + ',' + selectPrize3 + ',' + selectPrize4 + ',' + selectPrize5 + ',' + selectPrize6 + '/' + selectRebate + '%</option>');
                            //$('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize1 + ',' + selectPrize2 + ',' + selectPrize3 + ',' + selectPrize4 + ',' + selectPrize5 + ',' + selectPrize6 + '</option>');
                        } else if (ps.curMethod.name === 'SDCZW') {
                            $('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize1 + ',' + selectPrize2 + ',' + selectPrize3 + ',' + selectPrize4 + '/' + selectRebate + '%</option>');
                            //$('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize1 + ',' + selectPrize2 + ',' + selectPrize3 + ',' + selectPrize4 + '</option>');
                        } else {
                            $('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize1 + '/' + selectRebate + '%</option>');
                            //$('#curPrizeSpan').append('<option value="' + k + '">' + selectPrize1 + '</option>');
                        }
                        // $('#curPrizeSpan').siblings('span').html('奖金/返点');
                    });
                    $("#curPrizeSpan").val(ps.curPrizeIndex);
                    prizeBar.changePrize();

                    $('#selectRebate').slider({ //初始化滑动块
                        range: 'min',
                        min: 0,
                        max: $('#curPrizeSpan option').length - 1,
                        value: $('#curPrizeSpan option:selected').index(),
                        slide: function (event, ui) {
                            $('#curPrizeSpan option').removeAttr('selected');
                            $('#curPrizeSpan option').eq(ui.value).attr('selected', true);
                            var $curPrizeSpan = $('#curPrizeSpan option').eq(ui.value).html();

                            if (ps.curMethod.name === 'SDDDS') {
                                $('#rebateValue').html('');
                                $('.ballPrize_1').html($curPrizeSpan.split('/')[0].split(',')[0]);
                                $('.ballPrize_2').html($curPrizeSpan.split('/')[0].split(',')[2]);
                                $('.ballPrize_3').html($curPrizeSpan.split('/')[0].split(',')[4]);
                                $('.ballPrize_4').html($curPrizeSpan.split('/')[0].split(',')[5]);
                                $('.ballPrize_5').html($curPrizeSpan.split('/')[0].split(',')[3]);
                                $('.ballPrize_6').html($curPrizeSpan.split('/')[0].split(',')[1]);
                            } else if (ps.curMethod.name === 'SDCZW') {
                                $('#rebateValue').html('');
                                $('.ballPrizeSDCZW_1').html($curPrizeSpan.split('/')[0].split(',')[0]);
                                $('.ballPrizeSDCZW_2').html($curPrizeSpan.split('/')[0].split(',')[1]);
                                $('.ballPrizeSDCZW_3').html($curPrizeSpan.split('/')[0].split(',')[2]);
                                $('.ballPrizeSDCZW_4').html($curPrizeSpan.split('/')[0].split(',')[3]);
                            }
                            if (['WXDW', 'SSC-LMDXDS', 'QSYMBDW', 'YFFS', 'HSCS', 'SXBX', 'SJFC', 'SDQSDWD', 'SDQSBDW', 'SDRX1', 'SDDDS', 'SDCZW', 'SXDW', 'YMBDW', 'WXDW', 'QSYMBDW', 'ZSYMBDW', 'SXYMBDW'].includes(ps.curMethod.name)) {
                                $('.rebateValue').html($curPrizeSpan.split('/')[0]);
                                $('#rebateValue').siblings('span').html('返点:');
                                $('#rebateValue').html($curPrizeSpan.split('/')[1]);
                            } else {
                                $('#rebateValue').siblings('span').html('赔率/返点:');
                                $('#rebateValue').html($curPrizeSpan);
                            }
                            $('#curPrizeSpan').change();
                            ps.curPrizeIndex = parseInt(ui.value);
                        }
                    });

                    // 初始化滑块到最左边
                    // ps.curPrizeIndex = 0;
                    // $("#curPrizeSpan").val(0);
                    // $('#rebateValue').html($('#curPrizeSpan option').eq(0).html());
                    // $('#selectRebate').slider('option', 'value', 0);
                    $('#curPrizeSpan').change();

                    var $curPrizeSpan = $('#curPrizeSpan option:selected').html();

                    if (ps.curMethod.name === 'SDDDS') {
                        $('#rebateValue').html('');
                        $('.ballPrize_1').html($curPrizeSpan.split('/')[0].split(',')[0]);
                        $('.ballPrize_2').html($curPrizeSpan.split('/')[0].split(',')[2]);
                        $('.ballPrize_3').html($curPrizeSpan.split('/')[0].split(',')[4]);
                        $('.ballPrize_4').html($curPrizeSpan.split('/')[0].split(',')[5]);
                        $('.ballPrize_5').html($curPrizeSpan.split('/')[0].split(',')[3]);
                        $('.ballPrize_6').html($curPrizeSpan.split('/')[0].split(',')[1]);
                        // $('#rebateValue').siblings('span').html('返点');
                    } else if (ps.curMethod.name === 'SDCZW') {
                        $('#rebateValue').html('');
                        $('.ballPrizeSDCZW_1').html($curPrizeSpan.split('/')[0].split(',')[0]);
                        $('.ballPrizeSDCZW_2').html($curPrizeSpan.split('/')[0].split(',')[1]);
                        $('.ballPrizeSDCZW_3').html($curPrizeSpan.split('/')[0].split(',')[2]);
                        $('.ballPrizeSDCZW_4').html($curPrizeSpan.split('/')[0].split(',')[3]);
                        // $('#rebateValue').siblings('span').html('返点');
                    }
                    if (['WXDW', 'SSC-LMDXDS', 'QSYMBDW', 'YFFS', 'HSCS', 'SXBX', 'SJFC', 'SDQSDWD', 'SDQSBDW', 'SDRX1', 'SDDDS', 'SDCZW', 'SXDW', 'YMBDW', 'WXDW', 'QSYMBDW', 'ZSYMBDW', 'SXYMBDW'].includes(ps.curMethod.name)) {
                        $('.rebateValue').html($curPrizeSpan.split('/')[0]);
                        $('#rebateValue').siblings('span').html('返点:');
                        $('#rebateValue').html($curPrizeSpan.split('/')[1]);
                    } else {
                        $('#rebateValue').siblings('span').html('赔率/返点:');
                        $('#rebateValue').html($curPrizeSpan);
                    }
                }
            },
            //保存当前奖金设置
            saveLastPrize: function () {
                setCookie("reb_" + ps.lotteryId, ps.rebateGapList.get(ps.curPrizeIndex).rebate, 30 * 86400);
            }
        };

        //3.玩法相关
        var initMethodBar = function () {
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
                var dl1 = '',
                    dl2 = '',
                    dl3 = '',
                    dl4 = '',
                    dl5 = '',
                    dl6 = '',
                    dl7 = '',
                    dl8 = '',
                    dl9 = '',
                    dl0 = '',
                    dl10 = '';
                if (n.mg_id == 6 || n.mg_id == 50 || n.mg_id == 57 || n.mg_id == 169) {
                    //如果是11选5任选
                    dl0 = '<dl class="fix"><dt>任选复式：</dt>';
                    dl10 = '<dl class="fix"><dt>任选单式：</dt>';
                } else {
                    dl1 = '<dl class="fix"><dt>直选：</dt>';
                    dl2 = '<dl class="fix"><dt>组选：</dt>';
                    dl3 = '<dl class="fix"><dt>趣味：</dt>';
                    dl4 = '<dl class="fix"><dt>特殊：</dt>';
                    dl5 = '<dl class="fix"><dt>定位：</dt>';
                    dl6 = '<dl class="fix"><dt>不定位：</dt>';
                    dl7 = '<dl class="fix"><dt>任选二：</dt>';
                    dl8 = '<dl class="fix"><dt>任选三：</dt>';
                    dl9 = '<dl class="fix"><dt>任选四：</dt>';
                    dl0 = '<dl class="fix"><dt>其他：</dt>';
                }
                var methodStr = '';
                var div;
                if (i == 0) {
                    div = $('<div id="method_' + i + '" class="methodControl"></div>').addClass('methodPopStyle'); //.hide();
                } else {
                    div = $('<div id="method_' + i + '" class="methodControl"></div>').addClass('methodPopStyle').hide();
                }
                $.each(n.childs, function (ii, nn) {
                    var dd = '<dd class="method" name="' + nn.name + '" pid="method_' + i + '" id="method_' + i + "_" + ii + '">' + nn.cname + '</dd>';
                    var ddSon = '';
                    // nn.method_property 1直选2组选3趣味4特殊0其他
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
                    //如果该玩法有单式就再补充进去
                    //目前删除所有单式玩法
                    if (KaiQiZhiXuanMoShi) {
                        if (nn.can_input == 1) {
                            ddSon = '<dd class="method" name="' + nn.name + "son" + '" pid="method_' + i + '" id="method_' + i + "_" + ii + "son" + '">' + nn.cname + "单式" + "</dd>";
                            if (nn.method_property == 1) {
                                dl1 += ddSon;
                            } else if (nn.method_property == 2) {
                                dl2 += ddSon;
                            } else if (nn.method_property == 3) {
                                dl3 += ddSon;
                            } else if (nn.method_property == 4) {
                                dl4 += ddSon;
                            } else if (nn.method_property == 5) {
                                dl5 += ddSon;
                            } else if (nn.method_property == 6) {
                                dl6 += ddSon;
                            } else if (nn.method_property == 7) {
                                dl7 += ddSon;
                            } else if (nn.method_property == 8) {
                                dl8 += ddSon;
                            } else if (nn.method_property == 9) {
                                dl9 += ddSon;
                            } else if (nn.method_property == 0) {
                                if (n.mg_id == 6 || n.mg_id == 50 || n.mg_id == 57 || n.mg_id == 169) {
                                    dl10 += ddSon;
                                } else {
                                    dl0 += ddSon;
                                }
                            }
                        }
                    }
                });
                dl1 += '</dl>';
                dl2 += '</dl>';
                dl3 += '</dl>';
                dl4 += '</dl>';
                dl5 += '</dl>';
                dl6 += '</dl>';
                dl7 += '</dl>';
                dl8 += '</dl>';
                dl9 += '</dl>';
                dl0 += '</dl>';
                dl10 += '</dl>';
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
                if (dl10.indexOf('</dd>') < 0) {
                    dl10 = '';
                }
                $(dl1 + dl2 + dl3 + dl4 + dl5 + dl6 + dl7 + dl8 + dl9 + dl0 + dl10).appendTo(div);
                $.each(n.childs, function (ii, nn) {
                    $('#method_' + i + "_" + ii).live("click", nn, methodBar.method_Click).hover(methodBar.method_hoverOver, methodBar.methodGroup_hoverOut);
                    $("#method_" + i + "_" + ii + "son").live("click", nn, buyBar.inputBtn_Click).hover(methodBar.method_hoverOver, methodBar.methodGroup_hoverOut);
                });
                $("#methods").append(div);
            });
            $(".subTopBar").mouseleave(function () {
                $(this).find("li").removeClass("methodGroup_hover");
                // /\w+_(\d+)/.test($(this).attr("id"));
                // var index = RegExp.$1;
                //$(this).find("ul li .methodPopStyle").hide();
            });
            methodBar.selectDefault();
        };

        //玩法组及玩法菜单事件处理
        var methodBar = {
            methodGroup_Click: function (e) {
                $(this).addClass("methodGroup_selected").siblings().removeClass("methodGroup_selected methodGroup_hover");
                //必须这样做
                if ($(e.target).is('label')) {
                    $(this).find('dd:first').click();
                    $("#traceBtn").show();
                    if ($(".choMainTab").children('.progressBar')) {
                        $(".progressBar").remove();
                        $(".choMainTab").css("padding-top", "");
                    }
                }
                ;
                $(this).addClass("methodGroup_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
                $(".playNav ul .methodPopStyle").hide();
                $(this).find('.methodPopStyle').show();
                $(".methodControl").hide();
                $("#method_" + index).show();
                $("#method_" + index + "_0").click();
                //$("#method_" + index).children(":first").click();
            },
            methodGroup_hoverOver: function () {
                $(this).addClass("methodGroup_hover");
                if ($(this).hasClass("methodGroup_selected")) {
                    $(this).find('.methodPopStyle').show();
                }
                ;
            },
            methodGroup_hoverOut: function () {
                $(this).removeClass("methodGroup_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
            },
            method_hoverOver: function () {
                $(this).addClass("method_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
                //$("#method_" + index).show().siblings().hide();
                //$("#method_" + index).children(":first").click();
                $(this).find('.methodPopStyle').show();
            },
            method_hoverOut: function () {
                $(this).removeClass("method_hover");
                /\w+_(\d+)/.test($(this).attr("id"));
                var index = RegExp.$1;
                $(this).find('.methodPopStyle').hide();
            },
            method_Click: function (e) {
                $('#quickSelect').text('快捷投注');
                ps.curMethod = e.data;
                //$('.methodPopStyle').hide();
                //设置当前玩法前景色以示区别
                //$('#methodGroupContainer').find('.method').removeClass("method_selected");
                var $method = $(this);
                var pid = $method.attr("pid");
                $("#" + pid).find('.method').removeClass("method_selected");
                $method.addClass("method_selected");
                $('#curMethod').text(ps.curMethod.cname);
                //玩法提示文字
                $("#methodDesc").text(ps.curMethod.description).hide();
                //备注tips
                $("#methodTipInfo").hover(function () {
                    var t = layer.tips($("#methodDesc").text(), this, {
                        tips: [2, '#f13131'],
                        maxWidth: 250,
                        time: 0,
                        closeBtn: 0
                    });
                }, function () {
                    layer.closeAll('tips');
                });
                // $('#methodTipInfo').hover(function() {
                //     $("#methodDesc").show();
                // }, function() {
                //     $("#methodDesc").hide();
                // });
                //显示手工输入
                // if (ps.curMethod.can_input == 1) {
                //     $("#inputBtn").show();
                // } else {
                //     $("#inputBtn").hide();
                // }
                ballBar.generateBall();
                //$("input[name=missHotBtn]:checked").click();
                //$("#inputBtn").text("手工录入");
                //$("#singleInfo").hide();
                $(".selectRandomBtn").parent().hide();
                buyBar.updateSingle(0);
                prizeBar.showPirze();
                if (propLen(ps.curMethod.field_def) == 0) {
                    ballBar.showInput();
                    $("#singleInfo").show();
                    // $("#delTA").click(function() {
                    //     $("#inputTA").val("");
                    // });
                    $("#selectArea").removeClass("N-selected");
                }

                var counts = propLen(ps.curMethod.field_def);
                if (counts == 2) {
                    $(".locate").css("padding", "10px 0");
                }

                if (propLen(ps.curMethod.field_def) == 3) {
                    $(".locate").css("padding", "10px 0");
                }

                if (propLen(ps.curMethod.field_def) == 4) {
                    $(".locate").css("padding", "5px 0");
                }
            },
            selectDefault: function () {
                var $methodGroupContainer = $("#methodGroupContainer");
                var ob = $methodGroupContainer.find(":contains('定位胆')");
                if (typeof ob[0] === 'object') {
                    if (ob.length > 1) {
                        for (var i = 0; i < ob.length; ++i) {
                            if ($(ob[i]).text() === '定位胆') {
                                $(ob[i]).click();
                                break;
                            }
                        }
                    } else {
                        ob[0].click();
                    }

                    $(".playNav ul ul").hide();
                    return true;
                }
            }
        };
        //3.1投注球事件处理 ball_Selected为选中后的样式
        var ballBar = {
            reset: function () {
                if ($.isEmptyObject(ps.curMethod.field_def)) {
                    return;
                }
                $.each(ps.curMethod.field_def, function (i, prop) {
                    $('#field_' + i).children().removeClass('ball_Selected');
                });
                buyBar.updateSingle(0);
            },
            showInput: function () {
                $("#selectArea").children().remove();
                if (ps.lotteryType == 1) {
                    var rxIds = {
                        376: 2,
                        377: 3,
                        378: 2,
                        379: 3,
                        380: 2,
                        381: 3,
                        382: 2,
                        383: 3,
                        384: 2,
                        385: 3,
                        583: 4,
                        584: 4,
                        585: 4,
                        586: 4,
                        587: 4,
                        588: 4,
                        666: 2,
                        667: 3,
                        691: 4
                    }; //时时彩任二任三ID（除开秒秒彩）
                    var str = '<div class="manualInput fix"><div class="manualInputTop"><ul><li><p class="NumbBasket">号码篮</p><textarea cols="" rows="" class="inputTA MachineSeleBg" id="inputTA"></textarea></li><li id="delTA" class="delTA custBtnStyle">清空号码</li></div><div class="manualInputBottom"><li class="inputTip1"><p>提示：</p><p>请把您已有的大底号码复制或者输入到下边文本框中。</p><p>每注号码之间用 空格 或者换行符 隔开，每注号码每位之间不使用任何分隔符。</p><p>仅支持单式。 </p></li><li class="inputTip2"><p>例如：</p><p id="inputExample">';
                    var tmp = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
                    if (rxIds[ps.curMethod.method_id] > 0) {
                        var checked = new Array();
                        for (var i = 1; i <= rxIds[ps.curMethod.method_id]; i++) {
                            checked[i] = 'checked';
                        }
                        str = '<div class="manualInput fix"><div class="manualInputTop"><span class="RX_checkbox"><input type="checkbox" name="rx" value=0 ' + checked[5] + '>万位&nbsp;&nbsp;<input type="checkbox" name="rx" value=1 ' + checked[4] + '>千位&nbsp;&nbsp;<input type="checkbox" name="rx" value=2 ' + checked[3] + '>百位&nbsp;&nbsp;<input type="checkbox" name="rx" value=3 ' + checked[2] + '>十位&nbsp;&nbsp;<input type="checkbox" name="rx" value=4 ' + checked[1] + '>个位</span><ul><li><p class="NumbBasket">号码篮</p><textarea cols="" rows="" class="inputTA MachineSeleBg" id="inputTA"></textarea></li><li id="delTA" class="delTA custBtnStyle">清空号码</li></div><div class="manualInputBottom"><li class="inputTip1"><p>提示：</p><p>请把您已有的大底号码复制或者输入到下边文本框中。</p><p>每注号码之间用 空格 或者换行符 隔开，每注号码每位之间不使用任何分隔符。</p><p>仅支持单式。</p></li><li class="inputTip2"><p>例如：</p><p id="inputExample">';
                    }
                    if (ps.curMethod.name == "SXHHZX" || ps.curMethod.name == "ZSHHZX" || ps.curMethod.name == "QSHHZX") {
                        str += "123 112<br></p></li></ul></div></div>";
                    } else if (ps.curMethod.name == 'REZX') {
                        str += "12 34 68<br></p></li></ul></div></div>";
                    } else if (ps.curMethod.name == 'RSZX') {
                        str += "123 456 334<br></p></li></ul></div></div>";
                    } else if (ps.curMethod.name == 'RSIZX') {
                        str += "1234 4567 3354<br></p></li></ul></div></div>";
                    } else {
                        str += tmp.slice(0, propLen(ps.curMethod.field_def)).join("") + " " + tmp.slice(propLen(ps.curMethod.field_def), propLen(ps.curMethod.field_def) + propLen(ps.curMethod.field_def)).join("") + "<br></p></li></div></div>";
                    }
                } else if (ps.lotteryType == 2) {
                    var str = '<div class="manualInput fix"><div class="manualInputTop"><ul><li><p class="NumbBasket">号码篮</p><textarea cols="" rows="" class="inputTA MachineSeleBg" id="inputTA"></textarea></li><li id="delTA" class="delTA custBtnStyle">清空号码</li></div><div class="manualInputBottom"><li class="inputTip1"><p>提示：</p><p>请把您已有的大底号码复制或者输入到下边文本框中。</p><p>每注号码之间必须用 换行  隔开，每注号码每位之间必须使用 空格 作为分隔。</p><p>仅支持单式。 </p></li><li class="inputTip2"><p>例如：</p><p id="inputExample">';
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
                    var str = '<div class="manualInput fix"><div class="manualInputTop"></a><ul><li><p class="NumbBasket">号码篮</p><textarea cols="" rows="" class="inputTA MachineSeleBg" id="inputTA"></textarea></li><li id="delTA" class="delTA custBtnStyle">清空号码</li></div><div class="manualInputBottom"><li class="inputTip1"><p>提示：</p><p>请把您已有的大底号码复制或者输入到下边文本框中。</p><p>每注号码之间用 空格 或者换行符 隔开，每注号码每位之间不使用任何分隔符。</p><p>仅支持单式。 </p></li><li class="inputTip2"><p>例如：</p><p id="inputExample">';
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
                    $(".FatherCodeBtn").removeClass("selectCodeBtn_selected");
                    //$("#selectCodeBtn").removeClass("selectCodeBtn_selected");
                });
                $("#inputTA").live('keyup', function () {
                    if ($("#inputTA").val() != '') {
                        $(".FatherCodeBtn").addClass('selectCodeBtn_selected');
                        $(this).removeClass('MachineSeleBg');
                    } else {
                        $(".FatherCodeBtn").removeClass('selectCodeBtn_selected');
                        $(this).addClass('MachineSeleBg');
                    }
                });
            },
            //显示投注球
            generateBall: function () {
                $("#selectArea").children().remove();
                $('#quickSelect').data('val', 0); // quickSelect

                var filterBtn = '<li class="Quick"><div class="QuickChoose"><dl class="navSub"><dd>全</dd><dd>奇</dd><dd>偶</dd> <dd>大</dd><dd>小</dd><dd>清</dd></dl></div></li>';
                if (ps.lotteryType == 7) {
                    var filterBtn = '<li class="SelectControl"><div class="QuickChoose"><dl class="navSub"><dd class="allSelect">全选</dd><dd class="clearSelect">清除</dd></dl></div></li>';
                }

                /* 信用不需要最下边的输入框  begin */
                // $('#selectRebate').hide();

                if (['WXDW', 'SSC-LMDXDS', 'QSYMBDW', 'YFFS', 'HSCS', 'SXBX', 'SJFC', 'SDQSDWD', 'SDQSBDW', 'SDRX1', 'SDDDS', 'SDCZW', 'SXDW', 'YMBDW', 'WXDW', 'QSYMBDW', 'ZSYMBDW', 'SXYMBDW'].includes(ps.curMethod.name)) {
                    $('#totalSingleInfo').hide();
                    $('#singleInfo').hide();
                    $('#quickSelect').show();
                } else {
                    $('#totalSingleInfo').show();
                    $('#singleInfo').show();
                    $('#quickSelect').hide();
                }
                /* 信用不需要最下边的输入框  end */

                $.each(ps.curMethod.field_def, function (i, prop) {
                    //注：i从1开始
                    var numList = prop.nums.split(" ");
                    var ballStr = '',
                        hzbdStr = "",
                        prizeStr = "",
                        $inputStr = '';
                    $.each(numList, function (ii, nn) {
                        switch (ps.curMethod.name) {
                            case 'WXDW':
                            case 'SSC-LMDXDS':
                                ballStr += '<li class="list_1">' + '<span class="ball_hz">' + nn + '</span><b class="rebateValue"></b>' + '<input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></li>';
                                break;
                            case 'SXDW':
                                ballStr += '<li class="list_1">' + '<div class="ball_hz_SDDWD"><span class="ball_hz">' + nn + '</span></div><div class="ball_hz_SDDWD"><b class="rebateValue"></b></div>' + '<div class="ball_hz_SDDWD"><input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></div></li>';
                                break;
                            case 'SDDDS':
                                ballStr += '<li class="list_2">' + '<span class="ball_SDDDS">' + nn + '</span><b class="ballPrize_' + (ii + 1) + '"></b>' + '<input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></li>';
                                break;
                            case 'SDCZW':
                                var $pos = ii + 1;
                                if ($pos > 4) $pos = 4 - Math.abs(4 - $pos);
                                ballStr += '<li class="list_1">' + '<span class="ball_hz">' + nn + '</span><b class="ballPrizeSDCZW_' + $pos + '"></b>' + '<input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></li>';
                                break;
                            case 'SDQSDWD':
                                ballStr += '<li class="list_1">' + '<div class="ball_hz_SDDWD"><span class="ball_hz">' + nn + '</span></div><div class="ball_hz_SDDWD"><b class="rebateValue"></b></div>' + '<div class="ball_hz_SDDWD"><input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></div></li>';
                                break;
                            case 'SDQSBDW':
                            case 'SDRX1':
                                ballStr += '<li class="list_1">' + '<span class="ball_hz">' + nn + '</span><b class="rebateValue"></b>' + '<input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></li>';
                                break;
                            case 'YFFS':
                            case 'HSCS':
                            case 'SXBX':
                            case 'SJFC':
                                ballStr += '<li class="list_2">' + '<span class="ball_hz">' + nn + '</span><b class="rebateValue"></b>' + '<input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></li>';
                                break;
                            case 'QSYMBDW':
                            case 'ZSYMBDW':
                            case 'YMBDW':
                            case 'SXYMBDW':
                                ballStr += '<li class="list_2">' + '<span class="ball_hz">' + nn + '</span><b class="rebateValue"></b>' + '<input value="" list="itemlist" name="multiple" class="moneyNum multiple1" maxlength="5" data-ball="' + nn + '" type="number" min="0" max="50000" /></li>';
                                break;

                            case "SXBD":
                            case "ZSBD":
                            case "QSBD":
                                ballStr += '<li>' + nn + '</li>';
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
                                var poker_trans = {
                                    "黑桃": "poker_tao",
                                    "红桃": "poker_xin",
                                    "梅花": "poker_mei",
                                    "方块": "poker_fang"
                                };
                                ballStr += '<li class="' + eval('poker_trans.' + nn) + '"><div zhuma="' + nn + '"><em></em><i class="gou"></i></div></li>';
                                break;
                            case 'PKTHS':
                                //扑克.同花顺
                                //需要把玩法设定投注球的名称翻译成对应的class
                                var poker_trans = {
                                    "黑桃顺子": "poker_tao",
                                    "红桃顺子": "poker_xin",
                                    "梅花顺子": "poker_mei",
                                    "方块顺子": "poker_fang"
                                };
                                ballStr += '<li class="' + eval('poker_trans.' + nn) + '"><div zhuma="' + nn + '"><em></em><span>顺子</span><i class="gou"></i></div></li>';
                                break;
                            case 'PKBX':
                                //扑克.包选
                                //需要把玩法设定投注球的名称翻译成对应的class
                                var poker_trans = {
                                    "同花顺包选": "poker_ths",
                                    "同花包选": "poker_th",
                                    "顺子包选": "poker_sz",
                                    "豹子包选": "poker_bz",
                                    "对子包选": "poker_dz"
                                };
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

                    var ballListName, prizeListName;
                    if (ps.curMethod.name === "SDCZW") {
                        ballListName = 'ballList_czw';
                        prizeListName = 'prizeList_czw';
                    } else {
                        ballListName = 'ballList';
                        prizeListName = 'prizeList';
                    }
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

                    var parentLiClass = '';
                    /* 设置信用玩法输入框 那个 li 的样式 */
                    if ($.inArray(ps.curMethod.name, ['SSC-LMDXDS', 'WXDW', 'YFFS', 'HSCS', 'SXBX', 'SJFC', 'SXDW', 'QSYMBDW', 'ZSYMBDW', 'YMBDW', 'SXYMBDW']) !== -1) {
                        ballListName = 'ballList_x_ssc';
                        parentLiClass = 'dwdLi';
                    }

                    if ($.inArray(ps.curMethod.name, ['SDDDS', 'SDCZW', 'SDQSDWD', 'SDQSBDW']) !== -1) {
                        ballListName = 'ballList_x_115';
                    }

                    if ($.inArray(ps.curMethod.name, ['SDCZW']) !== -1) {
                        ballListName = 'ballList_x_115_SDCZW';
                    }

                    if ($.inArray(ps.curMethod.name, ['SDQSBDW', 'SDRX1']) !== -1) {
                        ballListName = 'ballList_x_BDW';
                    }

                    ballStr = '<li class="' + parentLiClass + '" >' + '<ul class="' + ballListName + (/(BD|HZ)$/.test(ps.curMethod.name) ? " w400" : "") + '" id=field_' + i + ">" + ballStr + "</ul>" + "</li>";

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
                    } else if ($.inArray(ps.curMethod.name, ['SSC-LMDXDS', 'WXDW']) !== -1) {
                        $('<div class="locateSSC locate" id="locate_' + i + '"><ul class="lotteryNumber' + specialClass + '">' + (prop.prompt ? '<li class="areaPrefix areaPrefixDWD">' + prop.prompt + "</li>" : "") + ballStr + "</ul></div>").appendTo("#selectArea");
                    } else if ($.inArray(ps.curMethod.name, ['SDQSDWD', 'SXDW']) !== -1) {
                        $('<div class="locate115 locate" id="locate_' + i + '"><ul class="lotteryNumber' + specialClass + '">' + (prop.prompt ? '<li class="areaPrefix areaPrefixDWD">' + prop.prompt + "</li>" : "") + ballStr + "</ul></div>").appendTo("#selectArea");
                    } else {
                        $('<div class="locate" id="locate_' + i + '"><ul class="lotteryNumber' + specialClass + '">' + (prop.prompt ? '<li class="areaPrefix">' + prop.prompt + "</li>" : "") + ballStr + "</ul></div>").appendTo("#selectArea");
                    }

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
                        } else if ($.inArray(ps.curMethod.name, ['WXDW', 'SDQSDWD', 'YFFS', 'HSCS', 'SXBX', 'SJFC', 'SXDW', 'QSYMBDW', 'ZSYMBDW', 'YMBDW', 'SXYMBDW', 'SDQSBDW', 'SDRX1']) !== -1) {
                            // 去掉信用定位胆的筛选按钮
                            $("#locate_" + i).find(".QuickChoose").hide();
                        } else {
                            $("#locate_" + i).find("ul").first().append(filterBtn);
                            if (ps.curMethod.name == "EXZUX" || ps.curMethod.name == "QEZUX") {}
                            // $("#locate_" + i).find(".navSub :first").text(" ");
                            // $("#locate_" + i).find(".navSub :last").text(" ");
                            // $("#locate_" + i).find(".QuickChoose").hide();

                            // $("#locate_" + i).find(".QuickChoose").hover(
                            //  function() {
                            //      $("#locate_" + i).find(".navSub").show();
                            //  },
                            //     function() {
                            //         $("#locate_" + i).find(".navSub").hide();
                            //         //setTimeout('$("#locate_' + i+'").find(".navSub").hide()',500);
                            //     }
                            // );
                            $("#locate_" + i).find("dd").click(function () {
                                switch ($(this).text()) {
                                    case '全':
                                        $('#field_' + i).children().addClass('ball_Selected');
                                        break;
                                    case '奇':
                                        if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":odd").addClass('ball_Selected');
                                        } else if (ps.lotteryType == 2) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":even").addClass('ball_Selected');
                                        }
                                        break;
                                    case '偶':
                                        if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":even").addClass('ball_Selected');
                                        } else if (ps.lotteryType == 2) {
                                            $('#field_' + i).children().removeClass('ball_Selected').parent().find(":odd").addClass('ball_Selected');
                                        }
                                        break;
                                    case '大':
                                        $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                            return idx >= 5;
                                        }).addClass('ball_Selected');
                                        break;
                                    case '小':
                                        $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                            return idx < 5;
                                        }).addClass('ball_Selected');
                                        break;
                                    case '清':
                                        $('#field_' + i).children().removeClass('ball_Selected');
                                        break;
                                    case '质':
                                        if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                                            $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                                return $.inArray(idx, [2, 3, 5, 7]) != -1;
                                            }).addClass('ball_Selected');
                                        } else if (ps.lotteryType == 2) {
                                            $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                                return $.inArray(idx, [1, 2, 4, 6, 10]) != -1;
                                            }).addClass('ball_Selected');
                                        }
                                        break;
                                    case '合':
                                        if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                                            $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                                return $.inArray(idx, [4, 6, 8, 9]) != -1;
                                            }).addClass('ball_Selected');
                                        } else if (ps.lotteryType == 2) {
                                            $('#field_' + i).children().removeClass('ball_Selected').filter(function (idx) {
                                                return $.inArray(idx, [3, 5, 7, 8, 9]) != -1;
                                            }).addClass('ball_Selected');
                                        }
                                        break;
                                    // case '反':
                                    //     $('#field_' + i).children().toggleClass('ball_Selected');
                                    //     break;
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

                /* 信用玩法的跳过,只需要输入金额即可,没有点击选中. */
                if ($.inArray(ps.curMethod.name, ['SSC-LMDXDS', 'WXDW', 'YFFS', 'HSCS', 'SXBX', 'SJFC']) === -1) {
                    //绑定球点击事件
                    $(".ballList li").bind("click", ballBar.ball_Click);
                    $(".ballList_czw li").bind("click", ballBar.ball_Click);
                    // $(".ballList_k3no_square li").bind("click", ballBar.ball_Click);
                    // $(".ballList_k3no_sttx li").bind("click", ballBar.ball_Click);
                    // $('.lotteryNumber ul>li').bind("click", ballBar.ball_Click);
                };
                //input输入判断
                $(".moneyNum").click(function () {
                    this.focus();
                    this.select();
                }).blur(function () {
                    if (this.value == '' || this.value == 0) {
                        this.value = '';
                        buyBar.updateTotalSingle();
                    }
                }).keyup(buyBar.checkMultiple).keyup(buyBar.updateTotalSingle);
            },

            //是否显示遗漏冷热
            getAssisInfo: function (field_def_idx) {
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
            ball_Click: function (e) {
                var $this = $(this);
                $this.toggleClass("ball_Selected");

                /* quickSelect begin */
                if ($this.find('span').length > 0) {
                    $this.find('span').toggleClass('x_spanball_selected');
                }
                /* quickSelect end */

                var $reg = /\w+_(\d+)/;
                $reg.test($this.parent().attr("id"));
                var index = RegExp.$1;
                if ($this.parent().find(".ball_Selected").length > ps.curMethod.field_def[index].max_selected) {
                    if (ps.curMethod.field_def[index].max_selected == 1) {
                        $this.siblings(".ball_Selected").removeClass("ball_Selected");
                    } else {
                        $this.removeClass("ball_Selected");
                        parent.layer.alert("当前最多只能选择" + ps.curMethod.field_def[index].max_selected + "个号码", { icon: 7 });
                    }
                } else {
                    //JSETDX 江苏快三 二同号单选 特殊处理
                    if (ps.curMethod.name == 'JSETDX') {
                        $('#locate_' + (index == 1 ? 2 : 1)).find(".ballList_k3no_square li").eq(parseInt($this.text().substr(0, 1)) - 1).removeClass("ball_Selected");
                    }

                    ballBar.computeSingle();
                }
            },
            //计算注数
            computeSingle: function () {
                var codes = [];
                $.each(ps.curMethod.field_def, function (i, n) {
                    var tmp = "";
                    var tmp2 = ps.curMethod.field_def[i].nums.split(" ");
                    $("#field_" + i + " li").each(function (ii) {
                        var $this = $(this);
                        if ($this.hasClass("ball_Selected")) {

                            if (ps.lotteryType === 2 || ps.curMethod.field_def[i].max_selected > 10 || tmp2[tmp2.length - 1].length > 1) {
                                /* quickSelect begin */
                                if (+$('#quickSelect').data('val') === 1) {
                                    tmp += $this.find('span').text() + '_';
                                } else {
                                    tmp += $this.text() + '_';
                                }
                                /* quickSelect end */
                            } else {
                                /* quickSelect begin */
                                if (+$('#quickSelect').data('val') === 1) {
                                    tmp += $this.find('span').text();
                                } else {
                                    tmp += $this.text();
                                }
                                /* quickSelect end */
                            }
                        }
                    });
                    if (tmp.indexOf("_") === -1) {
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
        var initMissHotBar = function () {
            $("input[name=missHotBtn]").click(missHotBar.missHotBtn_Click);
            $('input[name=missHotBtn][value="1"]').click();
        };
        var missHotBar = {
            missHotBtn_Click: function () {
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
        var initBuyBar = function () {
            ps.nextProjectCounter = 0;
            buyBar.removeAll();
            //$("#totalSingleInfo").prepend('总计[<span>0</span>]注 倍数<input name="multiple" id="multiple" value="1"/>');
            $("#multiple").click(function () {
                this.focus();
                this.select();
            }).keyup(buyBar.checkMultiple).keyup(buyBar.updateTotalSingle);
            //input输入判断
            // $(".moneyNum").click(function () {
            //     this.focus();
            //     this.select();
            // }).blur(function () {
            //     if (this.value == '' || this.value == 0) {
            //         this.value = '';
            //         buyBar.updateTotalSingle();
            //     }
            // }).keyup(buyBar.checkMultiple).keyup(buyBar.updateTotalSingle);
            // .blur(function(){
            // if(this.value == '' || this.value == 0) {
            //     this.value = '';
            // }
            // })
            $(".xDel").live("click", function () {
                $(this).parent().remove();
                buyBar.updateTotalSingle();
            });

            /* quickSelect begin */
            $("#quickSelect").click(function () {
                var $this = $(this);
                var quickSelect = +$(this).data('val');
                if (quickSelect === 0) {
                    $this.data('val', 1);
                    // 禁用行内输入框
                    $('.multiple1').attr('readonly', 'readonly').css('backgroundColor', '#eee').addClass('cur-not').val('');
                    // 显示输入栏
                    $('#totalSingleInfo').show();
                    $('#singleInfo').show();
                    $('#multiple').val('');
                    // 绑定行内点击事件
                    $('.lotteryNumber ul>li').bind('click', ballBar.ball_Click);
                    $this.text('一般投注');
                } else {
                    $this.data('val', 0);
                    // 启用行内输入框
                    $('.multiple1').removeAttr('readonly').css('backgroundColor', '').removeClass('cur-not').val('');
                    // 显示输入栏
                    $('#totalSingleInfo').hide();
                    $('#singleInfo').hide();
                    $('#multiple').val('');
                    // 解除行内点击事件
                    $('.lotteryNumber ul>li').unbind('click', ballBar.ball_Click);
                    // 清除已选球样式
                    $('.ball_Selected').removeClass('ball_Selected');
                    $('.x_spanball_selected').removeClass('x_spanball_selected');
                    $this.text('快捷投注');
                }
            });

            // 快捷金额
            $('#choPirze li').click(function () {
                var length = $('.multiple1').length;
                var quickSelect = +$('#quickSelect').data('val');
                if (quickSelect || length === 0) {
                    $('#multiple').val($(this).data('value'));
                } else {
                    $('.multiple1').val($(this).data('value'));
                }
            });
            /* quickSelect end */

            $("#clearProjectBtn").click(buyBar.removeAll);
            $("#inputBtn").click(buyBar.inputBtn_Click);
            $("#selectCodeBtn").click(buyBar.selectCodeBtn_Click);
            $("#selectCodeBtnFast").click(buyBar.selectCodeBtnFast);
            $("#confirmBtn").click(function () {
                buyBar.confirmBtn_Click(0);
            });
            $("#traceBtn").click(buyBar.traceBtn_Click);
            $(".selectRandomBtn").click(buyBar.selectRandomBtn_Click);
            $(".selectRandomBtn1").click(buyBar.selectRandomBtn_Click);
            $("#plusBtn").click(buyBar.plusBtn_Click);
            $("#minusBtn").click(buyBar.minusBtn_Click);
        };
        var buyBar = {
            selectCodeBtnFast: function () {
                buyBar.selectCodeBtn_Click();
                buyBar.confirmBtn_Click(1); //秒投传个参不走confirm
            },
            plusBtn_Click: function () {
                var multiple = $("#multiple").val();
                $("#multiple").val(parseInt(multiple) + 1);
                buyBar.checkMultiple();
                buyBar.updateTotalSingle();
            },
            minusBtn_Click: function () {
                var multiple = $("#multiple").val();
                if (parseInt(multiple) > 1) {
                    $("#multiple").val(multiple - 1);
                    buyBar.checkMultiple();
                    buyBar.updateTotalSingle();
                }
            },
            inputBtn_Click: function (e) {
                ps.curMethod = e.data;
                //$('.methodPopStyle').hide();
                ballBar.showInput();
                //$("#singleInfo").hide();
                //$('#methodGroupContainer').find('.method').removeClass("method_selected");
                var $method = $(this);
                var pid = $method.attr("pid");
                $("#" + pid).find('.method').removeClass("method_selected");
                $method.addClass("method_selected");
                $('#curMethod').text(ps.curMethod.cname);
                $("#methodDesc").text(ps.curMethod.description).hide();
                $("#selectCodeBtn").addClass("selectCodeBtn_selected");
                $(".selectRandomBtn").parent().show();
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
                //$(".selectRandomBtn").hide();
                //            if ($(this).text() == "手工录入") {
                //                $(this).text("常规录入");
                //            } else {
                //                $(this).text("手工录入");
                //                $("#singleInfo").show();
                //                ballBar.generateBall();
                //                $("input[name=missHotBtn]:checked").click();
                //                $("#selectArea").addClass("N-selected");
                //                $("#selectCodeBtn").removeClass("selectCodeBtn_selected");
                // $(".selectRandomBtn").show();
                //            }
                buyBar.updateSingle(0);
                prizeBar.showPirze();
            },
            selectCodeBtn_Click: function () {
                //var d = new Date();var t0 = d.getTime();
                if ($("#inputTA").length > 0) {
                    var allCodes = [];
                    var str = $.trim($("#inputTA").val());
                    if (str.length == 0) {
                        return false;
                    }
                    if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                        var arrs = str.split(/\s+|,|，|;|；/);
                        var arrsHHZX = [];
                        var hhzxIds = [81, 594, 597, 591, 82, 88, 109, 110, 95, 108, 600, 603, 606, 128, 122, 612, 609, 195, 216, 230, 615, 235, 250, 351, 352, 358, 486, 479, 468];
                        if (hhzxIds.indexOf(ps.curMethod.method_id) >= 0) {
                            $.each(arrs, function (i) {
                                var test = arrs[i].split("");
                                test = test.sort(function (a, b) {
                                    return a - b;
                                });
                                arrsHHZX.push(test.join(""));
                            });
                            var arrs = [];
                            arrs = arrsHHZX;
                        } //混合组选去重
                        var arr = tool.uniqueArr(arrs); //号码去重
                        //because HHZX have no field_def
                        if (ps.curMethod.name == "SXHHZX" || ps.curMethod.name == "ZSHHZX" || ps.curMethod.name == "QSHHZX") {
                            var re = eval("/^\\d{3}$/");
                        } else {
                            var re = eval("/^\\d{" + propLen(ps.curMethod.field_def) + "}$/");
                        }
                        if (ps.curMethod.name == 'REZX' || ps.curMethod.name == 'RSZX' || ps.curMethod.name == 'RSIZX') {
                            //如果是任二直选或任三直选任四直选手工录入的话需要这里处理下生成正确注码
                            var position = [0, 0, 0, 0, 0];
                            var positionLength = 0;
                            $("input[name=rx]:checkbox").each(function () {
                                if (this.checked) {
                                    position[$(this).val()] = 1;
                                    positionLength++;
                                }
                            });
                            var code,
                                allCodesString = [];
                            for (var m in arr) {
                                tmp = arr[m].split("");
                                if (tmp.length > positionLength) {
                                    parent.layer.alert("您输入的号码有误，请重新检查输入", { icon: 7 });
                                    return false;
                                }

                                var code = ["-", "-", "-", "-", "-"];
                                var tmpCode;
                                switch (ps.curMethod.name) {
                                    case 'REZX':
                                        if (tmp.length != 2) {
                                            parent.layer.alert("您输入的号码有误，请重新检查输入", { icon: 7 });
                                            return false;
                                        }
                                        for (var i = 0; i <= 3; i++) {
                                            if (position[i] == 1) {
                                                code[i] = tmp[0];
                                                for (var j = i + 1; j <= 4; j++) {
                                                    if (position[j] == 1) {
                                                        code[j] = tmp[1];
                                                        allCodesString.push(code.toString());
                                                        code[j] = '-';
                                                    }
                                                }
                                                code[i] = '-';
                                            }
                                        }
                                        break;
                                    case 'RSZX':
                                        if (tmp.length != 3) {
                                            parent.layer.alert("您输入的号码有误，请重新检查输入", { icon: 7 });
                                            return false;
                                        }
                                        for (var i = 0; i <= 2; i++) {
                                            if (position[i] == 1) {
                                                code[i] = tmp[0];
                                                for (var j = i + 1; j <= 3; j++) {
                                                    if (position[j] == 1) {
                                                        code[j] = tmp[1];
                                                        for (var k = j + 1; k <= 4; k++) {
                                                            if (position[k] == 1) {
                                                                code[k] = tmp[2];
                                                                allCodesString.push(code.toString());
                                                                code[k] = '-';
                                                            }
                                                        }
                                                        code[j] = '-';
                                                    }
                                                }
                                                code[i] = '-';
                                            }
                                        }
                                        break;
                                    case 'RSIZX':
                                        if (tmp.length != 4) {
                                            parent.layer.alert("您输入的号码有误，请重新检查输入", { icon: 7 });
                                            return false;
                                        }
                                        for (var g = 0; g <= 1; g++) {
                                            if (position[g] == 1) {
                                                code[g] = tmp[0];
                                                for (var i = g + 1; i <= 2; i++) {
                                                    if (position[i] == 1) {
                                                        code[i] = tmp[1];
                                                        for (var j = i + 1; j <= 3; j++) {
                                                            if (position[j] == 1) {
                                                                code[j] = tmp[2];
                                                                for (var k = j + 1; k <= 4; k++) {
                                                                    if (position[k] == 1) {
                                                                        code[k] = tmp[3];
                                                                        allCodesString.push(code.toString());
                                                                        code[k] = '-';
                                                                    }
                                                                }
                                                                code[j] = '-';
                                                            }
                                                        }
                                                        code[i] = '-';
                                                    }
                                                }
                                                code[g] = '-';
                                            }
                                        }

                                        break;
                                    default:
                                        break;
                                }
                            }
                            for (n in allCodesString) {
                                allCodes.push(allCodesString[n].split(","));
                            }
                        } else {

                            for (var i in arr) {
                                // if (!re.test(arr[i])) {
                                //     parent.layer.alert("您输入的号码有误，请重新检查输入");
                                //     return false
                                // }
                                allCodes.push(arr[i].split(""));
                            }
                        }
                    } else if (ps.lotteryType == 2) {
                        var arrs = str.split(/,|，|\n|;|；/);
                        var re = /^[01]\d$/;
                        var arr = tool.uniqueArr(arrs); //号码去重
                        for (var i in arr) {
                            arr[i] = $.trim(rtrim($.trim(arr[i]), ","));
                            var tmp = arr[i].split(" ");
                            for (var i2 in tmp) {
                                if (!re.test(tmp[i2])) {
                                    parent.layer.alert("您输入的号码有误，请重新检查输入", { icon: 7 });
                                    return false;
                                }
                            }
                            if (tmp.length != array_unique(tmp).length) {
                                parent.layer.alert("您输入的号码有重复，请重新检查输入", { icon: 7 });
                                return false;
                            }
                            if (ps.curMethod.name == 'SDQSZUX' || ps.curMethod.name == 'SDQEZUX' || /SDRX(\d+)/.test(ps.curMethod.name)) {
                                allCodes.push([tmp.join("_")]);
                            } else {
                                allCodes.push(tmp);
                            }
                        }
                    }
                    //节省字符串连接时间
                    var ob = {
                        singleNum: 1,
                        isDup: 0
                    };
                    var strPart1 = '<li><span class="width1" mid="' + ps.curMethod.method_id + '" mname="' + ps.curMethod.name + '">';
                    var strPart2 = ps.curMethod.cname + '</span><span class="width2">';
                    //+ps.nextProjectCounter + "." + ps.curMethod.cname + '</span><span class="width60px">';
                    if (allCodes.length <= 1000) {
                        for (var i in allCodes) {
                            var ob = isLegalCode(allCodes[i]);
                            //对于三星连选，选一注的singleNum是3注，所以这个得动态算
                            var singleAmount = number_format(ob.singleNum * 2 * ps.curMode, 3);
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
                            $(strPart1 + strPart2 + allCodes[i].join(",") + strPart3).appendTo("#projectList");
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
                        var singleAmount = number_format(allCodes.length * 2 * ps.curMode, 3);
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
                    var singleAmount = number_format(ob.singleNum * 2 * ps.curMode, 3);
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
                    $('<li><span class="width1" mid="' + ps.curMethod.method_id + '" mname="' + ps.curMethod.name + '">' + ps.curMethod.cname + '</span><span class="width2">' + ob.code + '</span><span class="width3">' + ob.singleNum + '注</span><span class="width4">￥' + singleAmount + '</span><span class="xDel">X</span></li>').appendTo("#projectList");
                    buyBar.updateTotalSingle();
                    //ballBar.reset();
                    //$("#confirmBtn").removeClass('CantapCodeBtn');
                }
                //var d = new Date();var t1 = d.getTime();
                //alert("66 t0=" + t0 + "\nt1=" + t1 + "\nt1-t0=" + (t1-t0));
            },
            rxInputCodes: function (position, inputArr) {
                // position: 勾选的位数; inputArr: 填入的其中的一注号码
                var code = ['-', '-', '-', '-', '-'];
                for (var i = position[0]; i <= position[position.length - 1]; i++) {
                    code[i] = '';
                }
            },
            removeAll: function () {
                $("#token").val("");
                $("#projectList").empty();
                ps.nextProjectCounter = 0;
                $("#totalSingleInfo input").val("");
                buyBar.updateTotalSingle();
                ballBar.reset();
                $("#multiple").val("");
                $(".multiple1").val("");

                /* quickSelect begin */
                $('.x_spanball_selected').removeClass('x_spanball_selected');
                /* quickSelect end */
            },
            updateSingle: function (singleNum) {
                var singleAmount = number_format(singleNum * 2 * ps.curMode, 3);
                $("#betCount").text(singleNum);
                $("#betAmount").text(singleAmount);
                var projectListLen = $('#projectList li').length == 0 ? 1 : $('#projectList li').length;
                if (singleNum > 0) {
                    $("#selectCodeBtn").parent().addClass('selectCodeBtn_selected');
                    //计算盈亏
                    var totalBetAmount = $("#totalBetAmount").text();
                    var curPrizeHtml = $('#curPrizeSpan').find("option:selected").text();
                    var curPrize = curPrizeHtml.split('/')[0];
                    $("#totalWin").text(number_format(curPrize * projectListLen * $("#multiple").val() - totalBetAmount - singleAmount, 3));
                } else {
                    $("#selectCodeBtn").parent().removeClass('selectCodeBtn_selected');
                    //计算盈亏
                    var totalBetAmount = $("#totalBetAmount").text();
                    var curPrizeHtml = $('#curPrizeSpan').find("option:selected").text();
                    var curPrize = curPrizeHtml.split('/')[0];
                    $("#totalWin").text(number_format(curPrize * projectListLen * $("#multiple").val() - totalBetAmount, 3));
                }
            },
            updateTotalSingle: function () {
                var totalSingleNum = 0;
                $("#projectList").children("li").each(function (i) {
                    var spans = $(this).children();
                    totalSingleNum += parseInt(spans.eq(2).text());
                    spans.eq(3).text("￥" + number_format(parseInt(spans.eq(2).text()) * 2 * $("#multiple").val() * ps.curMode, 3));
                });
                if (totalSingleNum > 0) {
                    $("#confirmBtn").addClass('CantapCodeBtn_selected');
                } else {
                    $("#confirmBtn").removeClass('CantapCodeBtn_selected');
                }
                var projectListLen = $('#projectList li').length == 0 ? 1 : $('#projectList li').length;
                $("#totalBetCount").text(totalSingleNum);
                var totalBetAmount = number_format(totalSingleNum * 2 * $("#multiple").val() * ps.curMode, 3);
                $("#totalBetAmount").text(totalBetAmount);
                var curPrizeHtml = $('#curPrizeSpan').find("option:selected").text();
                var curPrize = curPrizeHtml.split('/')[0];
                $("#totalWin").text(number_format(curPrize * projectListLen * $("#multiple").val() - totalBetAmount, 3));
            },
            checkMultiple: function () {
                var multipleNum = 50000;
                this.value = this.value.replace(/^0|[^\d]/g, '');
                if ($(this).val() > multipleNum) {
                    parent.layer.alert("请填写投注金额", { icon: 7 });
                    $(this).val(multipleNum);
                    return true;
                }
                return true;
            },

            //确认按钮
            confirmBtn_Click: function (fastBet) {
                // 定义购买入口
                var $op = '';
                var betTotalAmount = 0;
                var betTotalBetCount = 0;
                var totalSingleInfo = 0;
                var codes = '';
                var quickSelect = +$('#quickSelect').data('val'); // quickSelect

                /* 拼接codes begin */
                // 要添加玩法的时候,把这里写成数组,别写 ||
                if (['SSC-LMDXDS', 'WXDW', 'SDDDS', 'SDCZW', 'SDQSDWD', 'QSYMBDW', 'ZSYMBDW', 'YMBDW', 'SXYMBDW', 'SDQSBDW', 'SDRX1', 'SXDW', 'YFFS', 'HSCS', 'SXBX', 'SJFC'].includes(ps.curMethod.name) && quickSelect === 0) {
                    // quickSelect
                    $op = 'xbuy';
                    codes = ps.curMethod.method_id.toString() + ':';
                    $('.multiple1').each(function (i, item) {
                        var $item = $(item);
                        var $betValue = parseInt($item.val());
                        var $betBall = $item.data('ball');

                        if (!/^[1-9]*[1-9][0-9]*$/.test($betValue)) return true;

                        switch (ps.curMethod.name) {
                            case 'SSC-LMDXDS':
                                if (i >= 20) return false;
                                codes += ','.repeat(i / 4) + $betBall + '+' + $betValue + ','.repeat((19 - i) / 4) + '|';
                                break;
                            case 'WXDW':
                                if (i >= 50) return false;
                                codes += ','.repeat(i / 10) + $betBall + '+' + $betValue + ','.repeat((49 - i) / 10) + '|';
                                break;
                            case 'SDQSDWD':
                                if (i >= 33) return false;
                                codes += ','.repeat(i / 11) + $betBall + '+' + $betValue + ','.repeat((32 - i) / 11) + '|';
                                break;
                            case 'SXDW':
                                if (i >= 30) return false;
                                codes += ','.repeat(i / 10) + $betBall + '+' + $betValue + ','.repeat((29 - i) / 10) + '|';
                                break;
                            default:
                                codes += $betBall + '+' + $betValue + '|';
                        }

                        betTotalAmount += $betValue;
                        betTotalBetCount++;
                    });
                    codes = rtrim(codes, '|');
                    totalSingleInfo = 1;
                } else {
                    $op = 'buy';
                    // 清除之前的
                    $("#projectList").empty();
                    buyBar.selectCodeBtn_Click();

                    //1.先按玩法归类到一个对象
                    var methodCodes = {};
                    // 这里是投注栏里面
                    $("#projectList").children("li").each(function (i) {
                        var mname = $(this).children().eq(0).attr("mname");

                        if (!methodCodes[$(this).children().eq(0).attr("mid")]) {
                            methodCodes[$(this).children().eq(0).attr("mid")] = {};
                        }

                        methodCodes[$(this).children().eq(0).attr("mid")][i] = $(this).children().eq(1).text();
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

                    betTotalAmount = $("#totalBetAmount").text();
                    betTotalBetCount = $("#totalBetCount").text();
                    totalSingleInfo = +$("#multiple").val();

                    //输入正整数时
                    if (!/^[1-9]*[1-9][0-9]*$/.test(totalSingleInfo)) {
                        parent.layer.alert("请填写投注金额", { icon: 7 });
                        $("#multiple").val("");
                        return false;
                    }
                }
                codes = rtrim(codes, '#');
                /* 拼接codes end */

                if (!ps.canBuy) {
                    parent.layer.alert("该期已截止购买", { icon: 7 });
                    return false;
                }
                if (codes.length === 0) {
                    parent.layer.alert("投注号码不完整，请确认后再投注", { icon: 7 });
                    return false;
                }

                var $postData = {
                    op: $op,
                    lotteryId: ps.lotteryId,
                    issue: ps.curIssueInfo.issue,
                    curRebate: ps.rebateGapList.get(ps.curPrizeIndex).rebate,
                    modes: ps.curMode,
                    codes: codes,
                    xgame: 1, // 信用玩法这里都是xgame=1
                    multiple: $op === 'xbuy' ? 1 : $("#multiple").val(),
                    token: getRandChar(32)
                };
                var Flag = 0;
                var confirmInfo = '<div id="buy_message">请确认以下投注内容：<br>************************<br>单倍注数：' + betTotalBetCount + "注<br>总 金 额：￥" + betTotalAmount + "<br>************************<br></div>";
                var confirmLayer = parent.layer.confirm(confirmInfo, { icon: 7 }, function (i) {
                    //使用进度条代替按钮
                    parent.$('.xubox_botton').empty();
                    parent.$('.xubox_botton').html('<div class="LoadingShow"><span class="Loading_icon"></span><span class="Loading_Font">投注中，请稍后...</span></div>');
                    //快乐扑克10转成注码T
                    if (ps.lotteryType == 7) {
                        codes = codes.replace(/10/g, 'T');
                    }
                    //清楚开奖号码颜色
                    ballBar.reset();

                    //判断是重新新疆天津否是奖池玩法
                    Flag += 1;
                    if (Flag == 1) {
                        $.post("?c=game&a=play", $postData, function (response) {
                            if (response.errno == 0) {
                                drawBar.todayBuyBtnNew_Click();
                                //奖池玩法刷新
                                buyBar.removeAll();
                                var msg = '<div id="buy_success_message">购买成功!<br>************************<br>订单编号：' + response.pkgnum + "<br>投注期号：" + ps.curIssueInfo.issue + "<br>投注总额：￥" + betTotalAmount + "<br>************************<br></div>";
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
                            } else if (response.errno == 65534) {//重复投啥都不用做

                            } else {
                                parent.layer.close(i);
                                parent.layer.alert("购买失败:" + response.errstr, { icon: 2 });
                                console.log("错误代码:" + response.errno);
                            }
                            showBalance();
                        }, "json").fail(function (msg) {
                            parent.layer.close(i);
                            parent.layer.alert("网络异常，请在“游戏记录-订单查询”中确认该订单是否提交成功。", { icon: 2 });
                        });
                        var disonckick = setInterval(function () {
                            Flag = 0;
                            if (Flag == 0) {
                                clearInterval(disonckick);
                            }
                        }, 5000);
                    }
                }, function () {
                    $("#token").val("");
                    $("#projectList").empty();
                    ps.nextProjectCounter = 0;
                    $("#totalSingleInfo input").val("");
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
            },
            //追号按钮
            traceBtn_Click: function () {

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
                    success: function (response) {
                        $("#traceBtn").removeAttr("disabled");
                        if (response.errno == 0) {
                            ps.canTraceIssues = response.issues;
                            ps.traceMethodPrize = response.prize;
                            ps.tracePrizeLimit = response.prizeLimit;
                            //var content = $('#traceHtml').html();        //传html有问题，要传DOM
                            var i = traceFunc.showTracePage($('#traceHtml'));

                            //特例： 一下玩法不能使用倍投工具。
                            var disableMethods = [250, 251, 360, 368, 28, 56, 101, 202, 298, 34, 57, 102, 203, 304, 4, 52, 106, 193, 274, 5, 53, 107, 194, 275, 81, 108, 109, 195, 351, 42, 75, 115, 219, 312, 43, 76, 116, 220, 313, 38, 71, 120, 214, 308, 39, 72, 121, 215, 309, 82, 110, 122, 216, 352, 86, 93, 126, 228, 356, 87, 94, 127, 229, 357, 88, 95, 128, 230, 358, 47, 78, 141, 222, 317, 235, 236, 386, 25, 155, 173, 189, 24, 154, 172, 188];

                            if (response.prize == 0 || mids.length != 1 || $.inArray(parseInt(ps.curMethod.method_id), disableMethods) != -1) {
                                $("#multipleStyle2").attr("disabled", true);
                            }
                            $("input[name=multipleStyle]").click(traceFunc.multipleStyle_Click);
                            $("#confirmTraceBtn").click(function () {
                                if (traceFunc.confirmTraceBtn_Click()) {
                                    layer.close(i);
                                }
                            });
                            $("#cancelTraceBtn").click(function () {
                                traceFunc.cancelTraceBtn_Click();
                                layer.close(i);
                            });
                            $("#startIssue").change(traceFunc.startIssue_Change);
                            $("#traceNum").click(function () {
                                this.focus();
                                this.select();
                            }).keyup(buyBar.checkMultiple).keyup(traceFunc.traceNum_Keyup);
                            // 点击下三角图标
                            $(".multipleNumDropdown").mouseover(function () {
                                $(this).addClass('multipleNumDropdownSelected');
                            }).mouseout(function () {
                                $(this).removeClass('multipleNumDropdownSelected');
                            }).unbind("click").click(function () {
                                //console.log("click");
                                var $multipleNumValue = $(this).parent().find('.multipleNumValue');
                                $multipleNumValue.toggle();
                            });
                            // 选择下拉菜单
                            $(".multipleNumValue li").mousemove(function () {
                                $(this).addClass('multipleNumValueSelected');
                            }).mouseout(function () {
                                $(this).removeClass('multipleNumValueSelected');
                            }).click(function () {
                                var obj = $(this).parent().siblings('.zhuiz_number_e2');
                                obj.val(parseInt($(this).text()));
                                // 转换选择框的值的类型
                                var proxy_0 = $.proxy(buyBar.checkMultiple, obj.get(0));
                                proxy_0();

                                // 当是倍数下拉框时，修改倍数的输入框，并且修改总金额
                                if (obj.attr('id') == 'style1BodyMultiple') {
                                    $('.style1BodyMultiple').val(obj.val()).each(function () {
                                        var proxy_1 = $.proxy(traceFunc.style1BodyMultiple_Keyup, $(this).get(0));
                                        proxy_1();
                                    });
                                    traceFunc.updateTotalMoney();
                                }
                                // 当是期数下拉框时，修改期数的输入框，并且调用对应的方法
                                else if (obj.attr('id') == 'traceNum') {
                                        // $.proxy 将参数2的作用域传递给参数1的函数中。
                                        var proxy_2 = $.proxy(traceFunc.traceNum_Keyup, obj.get(0));
                                        proxy_2();
                                    }
                                $(this).parent().slideUp('fast');
                            });
                            // 倍数除了下拉框，还可以通过输入框来调整倍数，并且修改总金额
                            $('#style1BodyMultiple').click(function () {
                                this.focus();
                                this.select();
                            }).keyup(buyBar.checkMultiple).keyup(function () {
                                if (isNaN(parseInt($(this).val()))) {
                                    $(this).val("1");
                                }
                                $('.style1BodyMultiple').val($(this).val()).each(function () {
                                    var proxy_1 = $.proxy(traceFunc.style1BodyMultiple_Keyup, $(this).get(0));
                                    proxy_1();
                                });
                                traceFunc.updateTotalMoney();
                            });
                            $('#checkAll').click(function () {
                                if ($(this).is(':checked')) {
                                    $('.checkbox input[type=checkbox]').attr('checked', true).parent().parent().css('color', '');
                                } else {
                                    $('.checkbox input[type=checkbox]').attr('checked', false).parent().parent().css('color', '#ccc');
                                }
                                traceFunc.updateTotalMoney();
                            });
                            $('.checkbox input[type=checkbox]').live('click', function () {
                                if ($(this).attr('id') != 'checkAll') {
                                    if ($(this).is(':checked')) {
                                        $(this).parent().parent().css('color', '');
                                    } else {
                                        // 修改文字颜色为灰色
                                        $(this).parent().parent().css('color', '#ccc');
                                    }
                                    var index = $(this).parent().parent().index();
                                    var proxy_3 = $.proxy(traceFunc.style1BodyMultiple_Keyup, $('#style1BodyMultiple_' + index).get(0));
                                    proxy_3();
                                    traceFunc.updateTotalMoney();
                                }
                            });

                            //                            $("#ui-dialog2").keyup(function(e) {
                            //                                var key = e.keyCode ? e.keyCode : e.which;
                            //                                if (key == 27) {
                            //                                    $("#cancelTraceBtn").click()
                            //                                }
                            //                            });


                            $("#singleNum").text($("#totalBetCount").text());
                            $("#issuesNum2").text("1");
                            $("#multipleStyle1").click();
                            traceFunc.updateTotalMoney();
                            runTime.traceRemainTimer = window.setInterval(traceFunc.traceRemain_Timer_Handle, 1000);
                        } else {
                            alert(response.errstr); //alert("系统繁忙，请稍候再试(01)")
                        }
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
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
            selectRandom: function () {
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
                                ball = parseInt(ps.curMethod.field_def[i].max_selected); // 获得投注球个数
                                code = Math.floor(Math.random() * ball);
                                var codesArr = ps.curMethod.field_def[i].nums.split(" ");
                                var value = codesArr[code];
                                // 获得随机出来的号码对应的投注值,生成的是一个字符串 code:1 value:"1"
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
                                ball = parseInt(ps.curMethod.field_def[i].max_selected); // 获得投注球个数
                                code = Math.floor(Math.random() * ball);
                                var codesArr = ps.curMethod.field_def[i].nums.split(" ");
                                var value = codesArr[code];
                                // 获得随机出来的号码对应的投注值,生成的是一个字符串 code:1 value:"1"
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
                            ball = parseInt(ps.curMethod.field_def[i].max_selected); // 获得投注球个数

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
                            ball = parseInt(ps.curMethod.field_def[i].max_selected); // 获得投注球个数
                            // 获得号码
                            code = Math.floor(Math.random() * ball);
                            var codesArr = ps.curMethod.field_def[i].nums.split(" ");
                            var value = codesArr[code];
                            // 获得随机出来的号码对应的投注值,生成的是一个字符串 code:1 value:"1"
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
                var singleAmount = number_format(ob.singleNum * 2 * ps.curMode, 3);
                $('<li><span class="width1" mid="' + ps.curMethod.method_id + '" mname="' + ps.curMethod.name + '">' + ps.curMethod.cname + '</span><span class="width2">' + resultCode + '</span><span class="width3">' + ob.singleNum + '注</span><span class="width4">￥' + singleAmount + '</span><span class="xDel">X</span></li>').appendTo("#projectList");

                buyBar.updateTotalSingle();
            },
            // 机选点击事件
            selectRandomBtn_Click: function () {
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
            multipleStyle_Click: function () {
                $("#startIssue").get(0).selectedIndex = 0;
                $("#traceNum").val(1);
                $("#style1BodyMultiple").val(1);
                if ($(this).val() == 1) {
                    $(".style1BodyMultiple").live("click", function () {
                        this.focus();
                        this.select();
                    }).live("keyup", buyBar.checkMultiple).live("keyup", traceFunc.style1BodyMultiple_Keyup);
                    traceFunc.updateStyle1();
                    $(".multipleNum").eq(0).show();
                    $("#multipleStyle1DIV").show();
                    $("#multipleStyle2DIV").hide();
                } else {
                    $("#startMultiple").click(function () {
                        this.focus();
                        this.select();
                    });
                    $("#beitouToolSmainbtzk input").click(function () {
                        $(this).parent().click();
                    }).focus(function () {
                        this.select();
                    });
                    $("#beitouToolSmainbtzk li").click(function () {
                        $(this).addClass("checked").siblings().removeClass("checked");
                        $(this).find("input[name=profitStyle]").attr("checked", true);
                    });
                    $(".multipleNum").eq(0).hide();
                    $("#generalPlanBtn").click(traceFunc.generalPlanBtn_Click);
                    $("#issuesNum2").text("1");
                    $("#traceTotalAmount").text(number_format(parseInt($("#singleNum").text()) * 2 * ps.curMode, 3));
                    $("#style2Body").empty();
                    $("#startMultiple").val("1");
                    $("input[name=totalProfitRate]").val("10");
                    $("input[name=first5Rate]").val("5");
                    $("input[name=first5RateValue]").val("10");
                    $("input[name=laterRateValue]").val("5");
                    $("input[name=totalProfit]").val("100");
                    $("input[name=first5Profit]").val("5");
                    $("input[name=first5ProfitValue]").val("100");
                    $("input[name=laterProfitValue]").val("50");
                    $("#beitouToolSmainbtzk li:first").click();
                    $("#multipleStyle1DIV").hide();
                    $("#multipleStyle2DIV").show();
                }
            },
            startIssue_Change: function () {
                if ($("input[name=multipleStyle]:checked").val() == "1") {
                    traceFunc.updateStyle1();
                } else {
                    traceFunc.generalPlanBtn_Click();
                }
            },
            traceNum_Keyup: function () {
                if ($("input[name=multipleStyle]:checked").val() == "1") {
                    traceFunc.updateStyle1();
                }
            },
            //追号界面也加一个倒计时
            traceRemain_Timer_Handle: function () {
                var d = subTime(ps.curRemainTime);
                if (ps.curRemainTime > 0) {
                    $("#remainTimerLabel").text(d.hour + ":" + d.minute + ":" + d.second);
                    $("#traceBtn").attr("mark", 0);
                } else {
                    clearInterval(runTime.traceRemainTimer);
                    var d2 = subTime(ps.curWaitOpenTime);
                    $("#remainTimerLabel").text(d2.hour + ":" + d2.minute + ":" + d2.second);
                    runTime.traceWaitOpenTimer = window.setInterval(traceFunc.traceWaitOpen_Timer_Handle, 100);
                    //去掉过期的一期
                    layer.alert("第" + ps.curIssueInfo.issue + "期投注时间已结束，投注内容将进入到下一期！", { icon: 7 });
                    if ($("#traceBtn").attr("mark") != 1) {

                        $("#startIssue").children(":first").remove();
                        $("#startIssue").children(":first").text($("#startIssue").children(":first").text() + "(当前期)");

                        // 自定义追号
                        if ($("input[name=multipleStyle]:checked").val() == "1") {
                            var tmpArr = [];
                            $(".style1BodyMultiple").each(function () {
                                tmpArr.push(this.value);
                            });
                            traceFunc.updateStyle1();
                            $(".style1BodyMultiple").each(function () {
                                this.value = tmpArr.pop();
                            });
                        }
                        // 倍投时，当已经生成投资计划表时，当前期改变，投资计划表一起改变
                        else {
                                if ($("#style2Body li").length > 0) {
                                    traceFunc.generalPlanBtn_Click();
                                }
                            }
                    }
                }
            },
            //显示锁倒计时
            traceWaitOpen_Timer_Handle: function () {
                var d = subTime(ps.curWaitOpenTime);
                $("#remainTimerLabel").text(d.hour + ":" + d.minute + ":" + d.second);
                if (ps.curWaitOpenTime < 0) {
                    clearInterval(runTime.traceWaitOpenTimer);
                    runTime.traceRemainTimer = window.setInterval(traceFunc.traceRemain_Timer_Handle, 1000);
                }
            },
            style1BodyMultiple_Keyup: function () {
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
                    var prevIdx;
                    for (var i = idx - 1; i >= 0; i--) {
                        if ($('#check_' + i).is(':checked')) {
                            prevIdx = i;
                            break;
                        }
                    }
                    var prevTotalMoney = parseFloat($("#totalMoney_" + prevIdx).text());
                }
                // 只改变修改单个倍数对应的期号金额
                $("#style1BodyMultiple_" + idx).val(multiple);
                var curMoney = parseInt($("#singleNum").text()) * multiple * 2 * ps.curMode;
                $("#curMoney_" + idx).text(number_format(curMoney, 3));
                while (idx <= $("#style1Body li").length) {
                    if ($('#check_' + idx).is(':checked')) {
                        prevTotalMoney += parseFloat($("#curMoney_" + idx).text());
                        $("#totalMoney_" + idx).text(number_format(prevTotalMoney, 3));
                    }
                    idx++;
                }
                traceFunc.updateTotalMoney();
            },
            updateStyle1: function () {
                var idx = -1;
                //console.log($("#startIssue").val());
                //console.log(ps.canTraceIssues);
                $.each(ps.canTraceIssues, function (k, v) {
                    if (v == $("#startIssue").val()) {
                        idx = k;
                    }
                });
                //console.log(idx);
                if (idx == -1) {
                    //    alert("数据出错");
                    //    throw "数据出错";
                    return;
                }
                //console.log("updateStyle1");
                if (isNaN(parseInt($("#traceNum").val()))) {
                    $("#traceNum").val("1");
                }
                $('#style1BodyMultiple').val("1");
                var willTraceIssues = ps.canTraceIssues.slice(idx, idx + parseInt($("#traceNum").val()));
                if (willTraceIssues.length < $("#traceNum").val()) {
                    layer.alert("最多只能追" + willTraceIssues.length + "期", { icon: 7 });
                    $("#traceNum").val(willTraceIssues.length);
                }
                $("#style1Body").empty();
                var str = "",
                    curMoney,
                    totalMoney = 0;
                $('#checkAll').attr('checked', true);

                $.each(willTraceIssues, function (k, v) {
                    curMoney = parseInt($("#singleNum").text()) * 2 * ps.curMode;
                    totalMoney += curMoney;
                    var str = '<li id="traceIssueLI_' + k + '"><span class="checkbox"><input type="checkbox" id="check_' + k + '" checked /></span><span id="traceIssue_' + k + '">' + v + '</span><span><input type="text" value="1" id="style1BodyMultiple_' + k + '" class="beitouToolsinput style1BodyMultiple" maxlength="5" /></span><span id=curMoney_' + k + ">" + number_format(curMoney, 3) + "</span><span id=totalMoney_" + k + ">" + number_format(totalMoney, 3) + "</span></li>";
                    $("#style1Body").append(str);
                    $(".style1BodyMultiple").bind("click", function () {
                        this.focus();
                        this.select();
                    }).bind("keyup", buyBar.checkMultiple).bind("keyup", traceFunc.style1BodyMultiple_Keyup);
                });
                traceFunc.updateTotalMoney();
            },
            confirmTraceBtn_Click: function () {
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
                if ($("input[name=multipleStyle]:checked").val() == "1") {
                    $("#style1Body li").each(function (i) {
                        if ($(this).find("input[type=checkbox]").is(':checked')) {
                            var issue = $(this).find("span:eq(1)").text();
                            var multiple = $(this).find("input[type=text]").val();
                            traceData.push({
                                issue: issue,
                                multiple: multiple
                            });
                        }
                    });
                } else {
                    $("#style2Body li").each(function (i) {
                        var issue = $(this).find("span:eq(0)").text();
                        var multiple = $(this).find("span:eq(1)").text();
                        traceData.push({
                            issue: issue,
                            multiple: multiple
                        });
                    });
                }
                if (!traceData || traceData.length <= 0) {
                    parent.layer.alert("追号的期数不能为空", { icon: 7 });
                    return false;
                }

                $("#token").val(getRandChar(32));

                var traceTotalAmount = $("#traceTotalAmount").text();
                var stopOnWin = $("input[name=stopOnWin]").attr("checked") ? 1 : 0;
                var confirmInfo = '<div id="buy_message">请确认以下投注内容：<br>************************<br>是否追号：是<br>单倍注数：' + $("#totalBetCount").text() + "注<br>总 金 额：￥" + traceTotalAmount + "<br>超始期号：" + traceData[0].issue + "<br>追号期数：" + traceData.length + "<br>模&nbsp;&nbsp;式：" + (ps.curMode == 1 ? "2元" : ps.curMode == 0.5 ? "1元" : ps.curMode == 0.1 ? "2角" : ps.curMode == 0.05 ? "1角" : ps.curMode == 0.01 ? "分" : "厘") + "模式<br>************************<br></div>";
                traceFunc.destroyTracePage();
                parent.layer.confirm(confirmInfo, { icon: 7 }, function (i) {
                    //快乐扑克10转成注码T
                    if (ps.lotteryType == 7) {
                        codes = codes.replace(/10/g, 'T');
                    }
                    $.post("?c=game&a=play", {
                        op: "buy",
                        lotteryId: ps.lotteryId,
                        issue: ps.curIssueInfo.issue,
                        curRebate: ps.rebateGapList.get(ps.curPrizeIndex).rebate,
                        modes: ps.curMode,
                        codes: codes,
                        traceData: traceData,
                        stopOnWin: stopOnWin,
                        token: $("#token").val()
                    }, function (response) {
                        if (response.errno == 0) {
                            buyBar.removeAll();
                            drawBar.todayBuyBtnNew_Click();
                            var msg = '<div id="buy_success_message">追号订单成功!<br>************************<br>订单编号：' + response.pkgnum + "<br>起始期号：" + traceData[0].issue + "<br>追号期数：" + traceData.length + "<br>追号总额：￥" + traceTotalAmount + "<br>模&nbsp;&nbsp;式：" + (ps.curMode == 1 ? "2元" : ps.curMode == 0.5 ? "1元" : ps.curMode == 0.1 ? "2角" : ps.curMode == 0.05 ? "1角" : ps.curMode == 0.01 ? "分" : "厘") + "模式<br>************************<br></div>";
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
                            parent.layer.alert("追号失败:" + response.errstr + response.errno, { icon: 2 });
                        }
                        //showBalance()
                    }, "json").fail(function (msg) {
                        parent.layer.close(i);
                        layer.alert("网络异常，请在“游戏记录-追号记录”中确认该订单是否提交成功。", { icon: 2 });
                    });
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
            cancelTraceBtn_Click: function () {
                traceFunc.destroyTracePage();
            },
            showTracePage: function (content) {
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
                var i = layer.open({
                    type: 1,
                    title: '追号管理',
                    offset: ['50px', ''],
                    //border: [0],
                    area: ['700px', 'auto'],
                    content: content,
                    success: function (l) {

                        var traceIssueOptions = '';
                        $.each(ps.canTraceIssues, function (k, v) {
                            traceIssueOptions += '<option value="' + v + '">' + v + '期</option>';
                        });

                        $("#startIssue").html(traceIssueOptions).children(":first").text($("#startIssue").children(":first").text() + "(当前期)");
                        $('#maxTraceCount').html(ps.canTraceIssues.length);
                    },
                    close: traceFunc.cancelTraceBtn_Click
                });
                $(document).one('keyup', function (e) {
                    var key = e.keyCode ? e.keyCode : e.which;
                    if (key == 27) {
                        traceFunc.cancelTraceBtn_Click();
                        layer.close(i);
                    }
                });
                return i;
            },
            destroyTracePage: function () {
                //                $("#ui-dialog2").remove();
                //                $("#ui-widget-overlay2").remove();
                clearInterval(runTime.traceRemainTimer);
                clearInterval(runTime.traceWaitOpenTimer);
            },
            updateTotalMoney: function () {
                var totalMultiple = 0;
                if ($("input[name=multipleStyle]:checked").val() == "1") {
                    $("#style1Body li").each(function (i) {
                        if ($(this).find("input[type=checkbox]").is(':checked')) {
                            totalMultiple += parseInt($(this).find("input[type=text]").val());
                        }
                    });
                    $("#issuesNum2").text($("#style1Body li input[type=checkbox]:checked").length);
                } else {
                    $("#style2Body li").each(function (i) {
                        totalMultiple += parseInt($(this).find("span:eq(1)").text());
                    });
                    $("#issuesNum2").text($("#style2Body li").length);
                }
                $("#traceTotalAmount").text(number_format(parseInt($("#singleNum").text()) * totalMultiple * 2 * ps.curMode, 3));
            },
            generalPlanBtn_Click: function () {
                var computeMultiple = function (startMultiple, profitRate, singleAmount, totalMoney, prize) {
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
                    if (v == $("#startIssue").val()) {
                        idx = k;
                    }
                });
                if (idx == -1) {
                    alert("数据出错");
                    throw "数据出错";
                }
                if (isNaN(parseInt($("#traceNum").val()))) {
                    $("#traceNum").val("1");
                }
                var willTraceIssues = ps.canTraceIssues.slice(idx, idx + parseInt($("#traceNum").val()));
                if (willTraceIssues.length < $("#traceNum").val()) {
                    parent.layer.alert("最多只能追" + willTraceIssues.length + "期", { icon: 7 });
                    $("#traceNum").val(willTraceIssues.length);
                }
                $("#style2Body").empty();
                var traces = [],
                    str = "",
                    curMultiple,
                    curMoney,
                    totalMoney = 0;
                var singleMoney = parseInt($("#singleNum").text()) * 2 * ps.curMode;
                var prize = ps.traceMethodPrize * (ps.rebateGapList.get(ps.curPrizeIndex).prize / (ps.maxCombPrize * ps.prizeRate)) * ps.curMode;
                $.each(willTraceIssues, function (k, v) {
                    if ($("input[name=profitStyle]:checked").val() == 1) {
                        if (k == 0) {
                            curMultiple = parseInt($("#startMultiple").val());
                            curMoney = curMultiple * parseInt($("#singleNum").text()) * 2 * ps.curMode;
                            if ((curMultiple * prize - curMoney) / curMoney * 100 < $("input[name=totalProfitRate]").val()) {
                                parent.layer.alert("该计划无法实现，请调整目标", { icon: 7 });
                                return false;
                            }
                        } else {
                            curMultiple = computeMultiple($("#startMultiple").val(), $("input[name=totalProfitRate]").val(), singleMoney, totalMoney, prize);
                        }
                    } else {
                        if ($("input[name=profitStyle]:checked").val() == 2) {
                            if (k == 0) {
                                curMultiple = parseInt($("#startMultiple").val());
                                curMoney = curMultiple * parseInt($("#singleNum").text()) * 2 * ps.curMode;
                                if ((curMultiple * prize - curMoney) / curMoney * 100 < $("input[name=first5RateValue]").val()) {
                                    parent.layer.alert("该计划无法实现，请调整目标", { icon: 7 });
                                    return false;
                                }
                            } else {
                                if (k < $("input[name=first5Rate]").val()) {
                                    curMultiple = computeMultiple($("#startMultiple").val(), $("input[name=first5RateValue]").val(), singleMoney, totalMoney, prize);
                                } else {
                                    curMultiple = computeMultiple($("#startMultiple").val(), $("input[name=laterRateValue]").val(), singleMoney, totalMoney, prize);
                                }
                            }
                        } else {
                            if ($("input[name=profitStyle]:checked").val() == 3) {
                                curMultiple = Math.ceil(round((parseInt($("input[name=totalProfit]").val()) + totalMoney) / (prize - parseInt($("#singleNum").text()) * 2 * ps.curMode), 3));
                                if (curMultiple < $("#startMultiple").val()) {
                                    curMultiple = $("#startMultiple").val();
                                }
                            } else {
                                if ($("input[name=profitStyle]:checked").val() == 4) {
                                    if (k < $("input[name=first5Profit]").val()) {
                                        curMultiple = Math.ceil(round((parseInt($("input[name=first5ProfitValue]").val()) + totalMoney) / (prize - parseInt($("#singleNum").text()) * 2 * ps.curMode), 3));
                                    } else {
                                        curMultiple = Math.ceil(round((parseInt($("input[name=laterProfitValue]").val()) + totalMoney) / (prize - parseInt($("#singleNum").text()) * 2 * ps.curMode), 3));
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
                    curMoney = curMultiple * parseInt($("#singleNum").text()) * 2 * ps.curMode;
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
            _showPlan: function (traces) {
                $.each(traces, function (k, v) {
                    var str = '<li><span class="spanWidth90px">' + v.issue + '</span><span class="spanWidth50px">' + v.multiple + '</span><span class="spanWidth70px">' + v.curMoney + '</span><span class="spanWidth70px">' + v.totalMoney + '</span><span class="spanWidth70px">' + v.curPrize + '</span><span class="spanWidth70px">' + v.totalProfit + '</span><span class="spanWidth70px">' + Math.round(v.totalProfitRate * 100) + "%</span></li>";
                    $("#style2Body").append(str);
                });
                traceFunc.updateTotalMoney();
            }
        };
        //6.开奖区
        var initDrawBar = function () {
            $("#curLotteryName").text(ps.lotteryName);
            $("#curLotteryName2").text(ps.lotteryName);
            if (ps.lotteryType == 1) {
                $("#todayIssuesHead").html('<li class="width:30%">期号</li><li class="width:70%">开奖号</li><li class="width55px">前三组态</li><li class="width55px">后三组态</li>');
            } else if (ps.lotteryType == 2) {
                $("#todayIssuesHead").html('<li class="width:30%">期号</li><li class="width:70%">开奖号</li>');
            } else if (ps.lotteryType == 4) {
                $("#todayIssuesHead").html('<li class="width:30%">期号</li><li class="width:70%">开奖号</li><li class="width55px">三星组态</li><li class="width55px">三星和值</li>');
            } else if (ps.lotteryType == 6) {
                $("#todayIssuesHead").html('<li class="width:30%">期号</li><li class="width:70%">开奖号</li><li class="width55px">和值</li><li class="width55px">形态</li>');
            } else if (ps.lotteryType == 7) {
                $("#todayIssuesHead").html('<li class="width:30%">期号</li><li style="width:70%">开奖号</li><li class="width55px">形态</li>');
            }
            $("#todayDrawBtn").find(drawBar.todayDrawBtn_Click);
            $("#prizeRankBtn").click(drawBar.prizeRankBtn_Click);
            drawBar.todayBuyBtnNew_Click();

            $.each(ps.openedIssues, function (k, v) {
                v.prop = drawBar.getMoreInfo(v.code);
                ps.todayDrawList.push(v);
            });
            $("#prizeRankBtn").click();
            drawBar.getCurIssue(drawBar.init);
            //为东京1.5开奖号算法初始化移入移出效果
            // $('#thisIssueNumUL').live({
            //     mouseenter: function () {
            //         if($('#original_code').html() != ''){
            //             $('#original_code').fadeIn(200);
            //         }
            //     },
            //     mouseleave: function () {
            //         $('#original_code').hide().stop();
            //     }
            // });
        };

        var drawBar = {
            init: function () {
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

                    if (ps.lastIssueInfo.issue != latest.issue) {
                        var tmp = ps.lastIssueInfo;
                        var ob = drawBar.getMoreInfo(tmp.code);
                        tmp.prop = ob;
                        ps.todayDrawList.unshift(tmp);
                        if ($("#todayDrawBtn").hasClass("cur")) {
                            var v = ps.todayDrawList[0];
                            if (ps.lotteryType == 1) {
                                var str = '<ul><li class="width80px">' + v.issue + '</li><li class="width60px">' + v.code + '</li><li class="width53px">' + v.prop.qszt + '</li><li class="width53px">' + v.prop.hszt + "</li></ul>";
                            } else if (ps.lotteryType == 2) {
                                var str = '<ul class="fix"><li class="width:30%">' + v.issue + '</li><li class="width:70%">' + v.code + "</li>";
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

                var nums;
                //初始化开奖球数目
                if (ps.todayDrawList[0].code != null) {
                    if (ps.lotteryType == 1 || ps.lotteryType == 4 || ps.lotteryType == 6) {
                        nums = ps.todayDrawList[0].code.split("");
                    } else if (ps.lotteryType == 2 || ps.lotteryType == 7) {
                        nums = ps.todayDrawList[0].code.split(" ");
                    }
                }

                $('#thisIssueNumUL').empty().hide();
                $('#pendingText').show();
                if (nums != null) {
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
                }
                drawBar.showLastDraw();
                ps.canBuy = true;
            },
            //扑克 开奖区 显示code：以花色+数字的方式显示   code : 7s Ac Td
            pokerOpenCode: function (code) {
                var parts = code.split(' ');
                var trans = { 's': 'poker_heit', 'h': 'poker_hongt', 'c': 'poker_meih', 'd': 'poker_fangk' };
                var poker = '';
                var str = '<div class="poker_kj">';
                for (var i = 0; i < 3; i++) {
                    poker = parts[i].split(''); //0=>number 1=>suit
                    str += '<span class="poker_kj_num ' + eval('trans.' + poker[1]) + '"><i></i><em>' + drawBar.translateT(poker[0]) + '</em></span>';
                }
                str += '</div>';
                return str;
            },
            getCurIssue: function (callback) {
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
                    success: function (response) {
                        if (response.errno == 0) {
                            if (response.kTime != 0) {
                                $('#thisIssueMoreInfo').css('display', 'none');
                                $('.secondBtn').attr("disabled", "disabled");
                                $('.secondBtn').css("background", "#ccc");
                                $('#traceBtn').attr("disabled", "disabled");
                                $('#traceBtn').css("background", "#ccc");
                                $('#confirmBtn').attr("disabled", "disabled");
                                $('#confirmBtn').css("background", "#ccc url(../images/light_blue/confirmBtn.png) no-repeat 11px 6px");
                                $('#quickSelect').attr('disabled', 'disabled');
                                $('#quickSelect').css('background', '#ccc');
                                $('#lastIssueSpan').text('**');
                                $('#curIssueSpan').text('**');
                                $('#thisIssueSpan').text('*************');
                                var t = response.kTime / 1000;

                                window.setInterval(function () {
                                    --t;
                                    var d = subTime(t);
                                    var ht = '<span style="font-size: 26px;color: red; text-align: center;   margin-top: 20px;">休市中...</span>';
                                    ht += "<div style='font-size: 18px;'><span>距离下次开市:" + d.hour + "</span><em>:</em><span>" + d.minute + "</span><em>:</em><span>" + d.second + "</span></div>";
                                    $('#pendingText').html(ht);
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
                                if (response.lastIssueInfo && ps.counters > 0) {
                                    todayGetCurIssue(response.lastIssueInfo);
                                }
                                ps.counters++;
                                if (typeof callback == "function") {
                                    callback();
                                }
                            }
                        } else {
                            alert("系统繁忙，请稍候再试(02)");
                        }
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        if (errorThrown.indexOf("a=logout") != -1 || errorThrown.indexOf("a=login") != -1) {
                            top.layer.alert("您已经超时退出，请重新登录", { icon: 7 });
                            window.location.href = "?a=logout";
                        } else {
                            top.layer.alert("操作超时，请刷新页面..", { icon: 7 });
                        }
                    }
                });
            },
            showCurIssue_Timer: function () {
                $("#thisIssueSpan").text(ps.curIssueInfo.issue);
                $("#thisIssueSpan2").text(ps.curIssueInfo.issue);
                var d = subTime(--ps.curRemainTime);
                if (ps.curRemainTime >= 0) {
                    // $("#thisIssueRemainTime").html(
                    //     "<span>" + d.hour + "</span><em>:</em><span>" + d.minute + "</span><em>:</em><span>" + d.second + "</span>"
                    // );
                    var hour = d.hour.toString().split('');
                    var minute = d.minute.toString().split('');
                    var second = d.second.toString().split('');

                    $('#thisIssueRemainTime').html("<span>" + hour[0] + "</span><span>" + hour[1] + "</span><em>:</em>" + "<span>" + minute[0] + "</span><span>" + minute[1] + "</span><em>:</em>" + "<span>" + second[0] + "</span><span>" + second[1] + "</span>");

                    $("#thisIssueTimerIcon").removeClass("lock").addClass('clock').text("可以投注");
                } else {
                    clearInterval(runTime.remainTimer);
                    layer.alert(ps.curIssueInfo.issue + '期销售已截止，请进入下一期购买', { icon: 7, time: 3000 });
                    $('#thisIssueTimerIcon').removeClass('clock').addClass('lock').text("已截止投注");
                    var d2 = subTime(ps.curWaitOpenTime);
                    var hour = d2.hour.split('');
                    var minute = d2.minute.split('');
                    var second = d2.second.toString().split('');

                    $('#thisIssueRemainTime').html("<span>" + hour[0] + "</span><span>" + hour[1] + "</span><em>:</em>" + "<span>" + minute[0] + "</span><span>" + minute[1] + "</span><em>:</em>" + "<span>" + second[0] + "</span><span>" + second[1] + "</span>");
                    //$("#thisIssueRemainTime").addClass("lotteryTime-lock");
                    //$("#thisIssueMoreInfo").html('<div class="remainOpenDIV">第 ' + ps.curIssueInfo.issue + ' 期开奖倒计时：<span class="lotteryTime2">' + ps.curWaitOpenTime + "</span></div>");
                    ps.canBuy = false;
                    runTime.waitOpenTimer = window.setInterval(drawBar.waitOpen_Timer, 1000);
                }
            },
            //显示锁倒计时
            waitOpen_Timer: function () {
                $('#pendingText').show();
                $('#thisIssueNumUL').hide();
                --ps.curWaitOpenTime;

                var d = subTime(ps.curWaitOpenTime);
                // $("#thisIssueRemainTime").html("<span>" + d.hour + "</span><em>:</em><span>" + d.minute + "</span><em>:</em><span>" + d.second + "</span>");

                var hour = d.hour.split('');
                var minute = d.minute.split('');
                var second = d.second.toString().split('');

                $('#thisIssueRemainTime').html("<span>" + hour[0] + "</span><span>" + hour[1] + "</span><em>:</em>" + "<span>" + minute[0] + "</span><span>" + minute[1] + "</span><em>:</em>" + "<span>" + second[0] + "</span><span>" + second[1] + "</span>");

                if (ps.curWaitOpenTime < 0) {
                    clearInterval(runTime.waitOpenTimer);
                    drawBar.getCurIssue(drawBar.init);
                }
            },
            getLastOpen_Timer: function () {
                ps.getLastOpenTime++;

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
                        success: function (response) {
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
                                        todayGetCurIssue(response.issueInfo);
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
                                    }
                                }
                            } else {
                                alert("系统繁忙，请稍候再试(03)");
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
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
            showLastDraw: function () {
                var latest = ps.todayDrawList[0];
                $("#lastIssueSpan").text(ps.lastIssueInfo.issue);
                $("#curIssueSpan").text(ps.lastIssueInfo.issue);
                var str;
                if (ps.lastIssueInfo.issue == latest.issue) {
                    if (latest.code == null) return;
                    $('#pendingText').hide();
                    $('#thisIssueNumUL').show();
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

                    /**pendingBall
                     * 动画第二版
                     */
                    var index = 0,
                        index2 = 0;
                    if (ps.lotteryType == 7) {
                        var $cards = drawBar.translateCards(nums);
                    }
                    if ($("#todayIssuesBody").find("li:first").text() != ps.curIssueInfo.issue) {
                        //如果开奖才更新，否则不操作
                        drawBar.todayBuyBtnNew_Click(); //此时开号了就更新今日投注
                    }

                    if (ps.lotteryId == 18) {
                        //东京1.5的显示具体开号算法
                        var originalCode = latest.original_code.split(' '),
                            sum = 0,
                            contact = '',
                            k = 1;

                        $("#original_code").html("<div class='numb_main'><dl class='numb_1'><dt>万位：</dt><dd></dd><dd>=<span></span></dd></dl><dl class='numb_2'><dt>千位：</dt><dd></dd><dd>=<span></span></dd></dl><dl class='numb_3'><dt>百位：</dt><dd></dd><dd>=<span></span></dd></dl><dl class='numb_4'><dt>十位：</dt><dd></dd><dd>=<span></span></dd></dl><dl class='numb_5'><dt>个位：</dt><dd></dd><dd>=<span></span></dd></dl></div>");

                        $(originalCode).each(function (i) {
                            if (i % 4 == 3) {
                                contact += originalCode[i];
                            } else {
                                contact += originalCode[i] + "+";
                            }
                            sum += parseInt(originalCode[i]);
                            if (i % 4 == 3) {
                                var arr_sum = sum.toString().split("");
                                var str_sum = '';
                                $(arr_sum).each(function (j) {
                                    if (j == arr_sum.length - 1) {
                                        str_sum += "<b>" + arr_sum[j] + "</b>";
                                    } else {
                                        str_sum += arr_sum[j];
                                    }
                                });
                                $(".numb_" + k + " dd:first").html(contact);
                                $(".numb_" + k + " dd:last span").html(str_sum);
                                k++;
                                contact = '';
                                sum = 0;
                            }
                        });
                    }

                    var openCodeCallback = function () {
                        if ($("#thisIssueNumUL").children(".pendingBall").length > 0) {
                            var obj = $("#thisIssueNumUL").children(".pendingBall").first();
                            window.setTimeout(function () {
                                if (ps.lotteryType == 2) {
                                    obj.removeClass("pendingBall sd115Ball").text(nums[index++]); //显示数字
                                    obj.addClass("sd115_Ball bl" + nums[index2++]);
                                } else {
                                    obj.removeClass("pendingBall").text(nums[index++]); //显示数字
                                    obj.addClass("bl" + nums[index2++]);
                                }
                                openCodeCallback();
                            }, 1000);
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
                        $('#original_code').empty(); //东京1.5清空算术内容
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
            todayDrawBtn_Click: function () {
                // $("#todayIssuesHead").empty().show();
                // $("#todayIssuesBody").empty().show();
                // $("#prizeScrollContent").hide();
                if (ps.lotteryType == 1) {
                    $("#todayIssuesHead").html('<li class="wb40">期号</li><li class="wb60">开奖号</li>');
                } else if (ps.lotteryType == 2) {
                    $("#todayIssuesHead").html('<li class="wb40">期号</li><li class="wb60">开奖号</li>');
                } else if (ps.lotteryType == 4) {
                    $("#todayIssuesHead").html('<li class="wb40">期号</li><li class="wb60">开奖号</li>');
                } else if (ps.lotteryType == 6) {
                    $("#todayIssuesHead").html('<li class="wb40">期号</li><li class="wb60">开奖号</li>');
                } else if (ps.lotteryType == 7) {
                    $("#todayIssuesHead").html('<li class="wb40">期号</li><li class="wb60">开奖号</li>');
                } else {
                    throw exception('unknown lt type');
                }
                $("#todayIssuesBody").empty();
                $.each(ps.todayDrawList, function (k, v) {
                    var splitCode = "";
                    var splitCode2 = "";
                    for (var i = 0; i < v.code.length; i++) {
                        splitCode += "<b>" + v.code[i] + "</b>";
                        splitCode2 += v.code[i];
                    }
                    if (ps.lotteryType == 1) {
                        var str = '<ul class="fix"><li style="width:42%;">' + v.issue + '</li><li style="width:55%;">' + splitCode + "</li></ul>";
                    } else if (ps.lotteryType == 2) {
                        var arr = splitCode2.split(" ").join("</b><b>");
                        var str = '<ul class="fix1"><li style="width:42%;">' + v.issue + '</li><li style="width:55%;"><b>' + arr + "</b></li></ul>";
                    } else if (ps.lotteryType == 4) {
                        var str = '<ul class="fix"><li style="width:42%;">' + v.issue + '</li><li style="width:55%;">' + splitCode + "</li></ul>";
                    } else if (ps.lotteryType == 6) {
                        var tmp = v.code.split("");
                        var hz = parseInt(tmp[0]) + parseInt(tmp[1]) + parseInt(tmp[2]);
                        var str = '<ul class="fix"><li style="width:42%;">' + v.issue + '</li><li style="width:55%;">' + v.code + "</li></ul>";
                    } else if (ps.lotteryType == 7) {
                        var str = '<ul class="fix"><li style="width:40%;">' + v.issue + '</li><li class="width_poker">' + drawBar.pokerOpenCode(v.code) + '</li><li>' + drawBar.getMoreInfo(v.code).pkzt + "</li></ul>";
                    } else {
                        throw exception('unknown lt type');
                    }
                    $("#todayIssuesBody").append(str);
                });
            },
            //中奖排行榜
            prizeRankBtn_Click: function () {
                $("#todayBuyBody").empty().hide();
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
                        alert("暂时没有排行数据！");
                    }
                }, "json");
            },
            todayBuyBtnNew_Click: function () {
                if ($("#todayIssuesBody").find("li:first").text() != ps.curIssueInfo.issue) {
                    $.post("?c=game&a=play", {
                        op: "getBuyRecords",
                        lotteryId: ps.lotteryId
                    }, function (response) {
                        if (response.errno == 0) {
                            $("#todayBuyBody").empty();
                            $('<ul><li class="C1">玩法</li><li class="C2">期号</li><li class="C4">金额</li><li class="C5">状态</li></ul><div class="clear"></div>').appendTo("#todayBuyBody");
                            if (response.prj.length == 0) {
                                $('<div class="todayNoBet">暂时没有记录！</div>').appendTo("#todayBuyBody");
                            } else {
                                $.each(response.prj, function (k, v) {
                                    // 宽度有限 奖金一栏不要了'</li><li style="width:80px">' + v.prize
                                    //快乐扑克10转成注码T
                                    if (ps.lotteryType == 7) {
                                        v.code = v.code.replace(/T/g, '10');
                                    }
                                    $('<ul class="fix"><li class="C1"><a href="javascript:void(0);" onclick="showPackageDetail(\'' + v.wrapId + '\');return false;">' + v.methodName + '</a></li><li class="C2">' + helper.getNumByIssue(v.issue) + '</li><li class="C4">' + v.amount + '</li><li class="C5">' + v.prizeStatus + "</li></ul>").click(function () {
                                        //为适应客户端statusText，这里不再用jq定义
                                        //window.open("?c=game&a=packageDetail&wrap_id=" + v.wrapId, "_blank")
                                    }).appendTo("#todayBuyBody");
                                });
                            }
                        } else {
                            alert("系统繁忙，请稍候再试(04)");
                        }
                    }, "json");
                }
            },
            scrollPrizeRank: function () {
                var obj = $("#prizeScrollContent")[0];
                obj.scrollTop += 1;
                if (obj.scrollTop >= $("#prizeScrollContent")[0].scrollHeight / 2) {
                    obj.scrollTop = 0;
                }
            },
            getMoreInfo: function (code) {
                //得到扩展信息
                var $result = {};
                if (ps.lotteryType == 1 || ps.lotteryType == 4) {
                    if (code == null) return;
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
            translateT: function (num) {
                if (num == 'T') {
                    return '10';
                }
                return num;
            },
            translateCards: function ($nums) {
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

            zutai: function (a, b, c) {
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
            dxds: function (n1, n2) {
                var a = [],
                    b = [];
                a.push(n1 >= 5 ? "大" : "小");
                a.push(n1 % 2 == 1 ? "单" : "双");
                b.push(n2 >= 5 ? "大" : "小");
                b.push(n2 % 2 == 1 ? "单" : "双");
                return [a[0] + b[0], a[0] + b[1], a[1] + b[0], a[1] + b[1]].join(",");
            },
            //扑克组态
            pokerZutai: function (a, b, c) {
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

        var isLegalCode = function (codes) {

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
                /* =========== 时时彩 begin =========== */
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
                    for (var i = 0; i < codes.length; ++i) {
                        if (codes[i] !== '-') {
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

                case 'SSC-LMDXDS':
                    singleNum = 0;
                    codes.forEach(function ($item) {
                        singleNum += $item.length;
                    });
                    break;
                /* =========== 时时彩 end =========== */

                /* =========== sd11y begin =========== */
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
                    //任三直选  ["678", "67", "7", "7", "7"]
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