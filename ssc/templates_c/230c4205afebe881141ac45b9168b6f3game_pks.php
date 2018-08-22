<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo config::getConfig('site_title'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/global_reset.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/game_bjpks.css" />
    <?php $this->import('public_cssjs') ?>
</head>
<body class="BodyMain">
    <!-- 背景层开始 -->
    <div class="pksBodyBg">
        <div class="Bodybg01"></div>
        <div class="Bodybg02"></div>
        <div class="Bodybg03"></div>
        <div class="Bodybg04"></div>
        <div class="Bodybg05"></div>
        <div class="Bodybg06"></div>
        <div class="Bodybg07"></div>
        <div class="Bodybg08"></div>
        <div class="Bodybg09"></div>
        <div class="Bodybg10"></div>
        <div class="Bodybg11"></div>
        <div class="Bodybg12"></div>
        <div class="Bodybg13"></div>
        <div class="Bodybg14"></div>
        <div class="Bodybg15"></div>
        <div class="Bodybg16"></div>
        <div class="Bodybg17"></div>
        <div class="Bodybg18"></div>
        <div class="Bodybg19"></div>
        <div class="Bodybg20"></div>
        <div class="Bodybg21"></div>
        <div class="Bodybg22"></div>
        <div class="Bodybg23"></div>
        <div class="Bodybg24"></div>
        <div class="Bodybg25"></div>
        <div class="Bodybg26"></div>
        <div class="Bodybg27"></div>
    </div>
    <!-- 背景层结束 -->
    <div class="newMain">
    <?php $this->import('public_header') ?>
        <div class="topBar">
        </div>
        <div class="Gamepart">
            <div class="bjpkstopBox">
                <div class="subTopBar fix">
                    <div class="playNav">
                        <ul class="lotteryTab" id="methodGroupContainer"></ul>
                    </div>
                    <div class="crumbs">
                        <img src="<?php echo $imgCdnUrl ?>/images/pks_car.png" alt="">
                    </div>
                </div>
                <div class="bjpkLottCont fix">
                    <div class="PlayGame_way">
                        <span></span>
                        <em class="curMethod" id="curMethod"></em>
                    </div>
                    <div class="Lottmain">
                        <div class="GameName">
                            <div class="fix">
                                <em class="clock" id="thisIssueTimerIcon"></em>
                                <label>第<em id="thisIssueSpan"></em>期</label>
                                <span id="curLotteryName2"></span>
                            </div>
                            <div class="Timer fix">
                                <i>剩余时间</i>
                                <div class="thisIssueRemainTime" id="thisIssueRemainTime">
                                    <span>00</span><em>:</em><span>00</span><em>:</em><span>00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ShowTips" id="methodTipInfo">
                        <span></span>
                        <label>玩法介绍</label>
                        <div class="methodDesc" id="methodDesc"></div>
                    </div>
                    <!--<a class="SingleBtn" id="inputBtn" href="javascript:void(0);">
                        <span></span>
                        <label>手工录入</label>
                    </a>-->
                    <div class="playTimer thisIssueInfo" id="thisIssueInfo">
                        <p class="thisIssue">
                            <span class="issue thisIssueDIV">第<span id="lastIssueSpan" class="lastIssueSpan"></span>期</span>
                            <em>开奖号码</em>
                        </p>
                        <div class="endNums" id="endNums"></div>
                    </div>
                    <a class="trendBtn" href="index.jsp?c=game&a=chart&lottery_id=<?php echo $lottery['lottery_id']; ?>" target="_blank">
                        <span></span>
                        <label>开奖走势图</label>
                    </a>
                    <!-- <div class="GameNumberResult lotteryInfor" id="thisIssueMoreInfo"></div> -->
                </div>
            </div>
            <!-- 中间选择游戏部分 -->
            <div class="GameBoxall SubGamePlatePadding">
                <!-- 投注主体部分 -->
                <div class="PlayCenter fix">
                    <div class="playControlBox">
                        <!-- 投注选号 -->
                        <div class="choMainTab">
                            <div class="chooseNO selectArea" id="selectArea"></div>
                            <div class="MachineSele" style="display: none;">
                                <?php if (in_array($lottery['lottery_id'], array(1, 3, 4, 8, 11))): ?>
                                <div class="MachineSeleBtn">
                                    <input type="button" value="机选10注" num="10" class="Mach10 custBtnStyle selectRandomBtn">
                                    <input type="button" value="机选50注" num="50" class="Mach50 custBtnStyle selectRandomBtn">
                                </div>
                                <?php endif;?>
                            </div>
                        </div>
                        <div class="chooseOKBtn gameMoney fix" >
                            <div class="Selectnumber fix">
                                <div class="bettingPick">
                                    <div class="displayBtn fr">
                                        <div class="gameLeftLI" id="singleInfo">
                                            已选： <em class="betCount" id="betCount"></em>注
                                            <input type="hidden" id="modesDIV" style="display:none;" value="1">
                                        </div>
                                        <a href="javascript:void(0)" class="clearProjectBtn" id="clearProjectBtn" title="删除投注内容">清空</a>
                                        <input type="button" value="选号" id="selectCodeBtn" class="selectCodeBtn">
                                        <input type="button" value="一键投注" id="selectCodeBtnFast" class="selectCodeBtn">
                                    </div>
                                </div>
                                <div class="moneySlide">
                                    <div class="multiplePrize">
                                        <span class="moneyMode"><a href="javascript:void(0);" id="yuan">2元</a><a href="javascript:void(0);" id="jiao">2角</a><a href="javascript:void(0);" id="fen">2分</a><br/><a href="javascript:void(0);" id="yuanYi">1元</a><a href="javascript:void(0);" id="jiaoYi">1角</a><a href="javascript:void(0);" id="li">2厘</a></span>
                                    </div>
                                    <div class="SingleInfo" id="totalSingleInfo">
                                        <div class="multipleNumber">
                                            倍数 
                                            <span id="minusBtn" type="button" class="minusBtn" >-</span><input value="1" id="multiple" name="multiple" class="txtStyle txtaddSty" maxlength="5"/><span id="plusBtn" type="button" class="plusBtn" >+</span>
                                        </div>
                                        <div class="bonusSlide fix">
                                            <span>奖金/返点</span>
                                            <div id="selectRebate" class="selectRebate"></div>
                                            <span id="rebateValue" class="rebateValue"></span>
                                            <select id="curPrizeSpan" style="display:none;"></select>
                                            <i class="manuaTip" id="manuaTip"></i>
                                         </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mutiChoose">
                                <dl>
                                    <dt>
                                        <div class="projectListTitle fix">
                                            <span class="width1">玩法</span>
                                            <span class="width2">号码</span>
                                            <span class="width3">注数</span>
                                            <span class="width4">倍/元</span>
                                        </div>
                                        <!-- <div class="selectDiv">
                                            <select class="SelectStyle" id="curPrizeSpan">
                                            </select>
                                            <select class="SelectStyle" id="modesDIV">
                                            </select>
                                        </div> -->
                                    </dt>
                                    <dd>
                                        <ul class="projectList" id="projectList">
                                        </ul>
                                    </dd>
                                </dl>
                                <div class="totalcont fix">
                                    <div class="totalText">
                                        总注数：<em id="totalBetCount">0</em>注, 总金额：<em id="totalBetAmount">0.00</em>元, 盈利￥<em id="totalWin">0.00</em>
                                        (默认最高奖金盈利)
                                     </div>
                                     <div class="totalBtn">
                                        <input type="button" value="追号" class="Chasing_ball" id="traceBtn" mark="0"/>
                                        <input type="button" value="确认投注" class="CantapCodeBtn"  id="confirmBtn"/>
                                     </div>
                            </div>
                            </div>
                            <!-- 添加号码 选择按钮 -->
                            <div class="chooseOKBtn" style="display:none;">
                                <div class="fr">
                                    <input type="hidden" value="" id="token">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="GameNumber">
                        <div class="lotteryNum GameNuberFont" id="thisIssueNumUL"></div>
                        <div class="TodayReward">
                            <div class="nav">
                                <ul class="navul fix">
                                    <li><a href="javascript:void(0)" class="todayDrawBtn" id="todayDrawBtn">今日开奖</a></li>
                                    <!-- <li><a href="javascript:void(0)" class="todayDrawBtn" id="prizeRankBtn">中奖排行</a></li> -->
                                    <li><a href="javascript:void(0)" class="todayDrawBtn" id="todayBuyBtn">最近投注</a></li>
                                </ul>
                            </div>
                            <div class="lotteryTodayMain">
                                <div class="lotteryTodayBg"></div>
                                <!--  今日开奖 -->
                                <div class="rightBoxSrt" id="rightBoxSrt">
                                    <div class="lotteryTodayBox">
                                        <div class="lotteryTodayContent bet" id="todayIssuesBody"></div>
                                    </div>
                                </div>
                                <!--  最近投注 -->
                                <div class="lotteryTodayContent" id="todayBuyBody"></div>
                            </div>
                            <div class="curImg"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 追号部分 -->
            <div id="traceHtml">
                <div class="beitouToolsBox">
                    <ul class="beitouToolsTop">
                        <li class="beitouToolsTopContent fix">
                            <ul>
                                <li class="beitouToolsTopCont1">单倍注数<span id="singleNum"></span>注,购买<span id="issuesNum2"></span>期,合计￥<span id="traceTotalAmount"></span></li>
                                <li class="beitouToolsButton"><span class="btnOne" id="confirmTraceBtn">确认追号</span><span class="btnTwo" id="cancelTraceBtn">取消追号</span></li>
                            </ul>
                        </li>
                        <li class="beitouToolsTopR"></li>
                    </ul>
                    <ul class="beitouToolsQs">
                        <li>期数倍数
                            <span>
                            <input type="radio" name="multipleStyle" id="multipleStyle1" value="1" class="radio1" />
                            <label for="multipleStyle1">自定义</label>
                            </span>
                            <span>
                            <!-- <input type="radio" name="multipleStyle" id="multipleStyle2"  value="2" class="radio1" />
                            <label for="multipleStyle2">倍投工具</label> -->
                            </span>
                            <span>
                                当前期倒计时：<label id="remainTimerLabel"></label>
                            </span>
                        </li>
                    </ul>
                    <ul class="beitouToolsQishi">
                        <li class="beitouToolsQishiL"><span>起始</span> <span>
                            <select id="startIssue">

                            </select>
                            </span> </li>
                        <li class="beitouToolsQishiR">
                            <div class="multipleNum fl">
                                倍数
                                <div class="inputNum">
                                    <input type="text" id="style1BodyMultiple" class="zhuiz_number_e2" value="1" size="5"  maxlength="5" />
                                    <div class="multipleNumDropdown hand">
                                        <i class="downTriangle"></i>
                                    </div>
                                    <ul class="multipleNumValue hand"><li>1</li><li>5</li><li>10</li><li>20</li></ul>
                                </div>
                            </div>
                            <div  class="multipleNum fr">追
                                <div class="inputNum">
                                    <input type="text" id="traceNum" class="zhuiz_number_e2" value="1" size="5"  maxlength="5" />
                                    <div class="multipleNumDropdown hand">
                                        <i class="downTriangle"></i>
                                    </div>
                                    <ul class="multipleNumValue hand"><li>1</li><li>5</li><li>10</li><li>20</li></ul>
                                </div>
                                期(包含当前期最多追<span id="maxTraceCount">0</span>期)
                            </div>
                            <div class="clear"></div>
                        </li>
                    </ul>
                    <div class="beitouToolsmainBox">
                        <div style="display:block;" id="multipleStyle1DIV">
                            <ul class="beitouToolsmainBoxTop" id="style1Head">
                                <li class="checkbox"><input type="checkbox" id="checkAll" checked /></li>
                                <li>期号</li>
                                <li>倍数</li>
                                <li>当前投入</li>
                                <li>累计投入</li>
                            </ul>
                            <ul class="beitouToolsmainBoxCont" id="style1Body">
                                <!--
                          <li>
                           <span>123456789021</span>
                           <span><input type="text" value="1" class="beitouToolsinput style1BodyMultiple" /></span>
                           <span>10.00</span>
                           <span>100.00</span>
                         </li>
                          -->
                            </ul>
                        </div>
                        <div class="beitouToolSmainbt" id="multipleStyle2DIV" style="display:none;">
                            <p>起始倍数
                                <input type="text" value="1" class="beitouToolsinput" name="startMultiple" id="startMultiple" maxlength="5" size="5"/>
                            </p>
                            <ul class="beitouToolSmainbtzk" id="beitouToolSmainbtzk">
                                <li>
                                    <input type="radio" name="profitStyle"  value="1" />
                                    全程利润率:
                                    <input type="text" size="5" value="10" name="totalProfitRate" class="beitouToolsinput" class="beitouToolsinput" />
                                    % </li>
                                <li>
                                    <input type="radio" name="profitStyle"  value="2"/>
                                    前
                                    <input type="text" size="5" value="5" name="first5Rate" class="beitouToolsinput"/>
                                    期利润率
                                    <input type="text" size="5"  class="beitouToolsinput" value="10" name="first5RateValue"/>
                                    %,之后利润率
                                    <input type="text" size="5" value="5" class="beitouToolsinput" name="laterRateValue"/>
                                    % </li>
                                <li>
                                    <input type="radio" name="profitStyle"  value="3"/>
                                    全程累计利润:
                                    <input type="text" size="5" value="100" name="totalProfit" class="beitouToolsinput" />
                                    元 </li>
                                <li>
                                    <input type="radio" name="profitStyle" value="4"/>
                                    前
                                    <input type="text" size="5" value="5" class="beitouToolsinput" name="first5Profit" />
                                    期累计利润
                                    <input type="text" size="5"  class="beitouToolsinput" value="100" name="first5ProfitValue" />
                                    元,之后累计利润
                                    <input type="text" size="5" value="50" class="beitouToolsinput"  name="laterProfitValue" />
                                    元 </li>
                            </ul>
                            <span class="beitouTooltzjhb">
                                <input type="button"  class="bt_tools_navys" id="generalPlanBtn" value="生成投资计划表"/>
                            </span>
                            <ul class="beitouToolSmainbtContTop" id="style2Head">
                                <li class="spanWidth90px">期号</li>
                                <li class="spanWidth50px">倍数</li>
                                <li class="spanWidth70px">当前投入</li>
                                <li class="spanWidth70px">累积投入</li>
                                <li class="spanWidth70px">当期奖金</li>
                                <li class="spanWidth70px">合计利润</li>
                                <li class="spanWidth70px">利润率</li>
                            </ul>
                            <ul class="beitouToolSmainBTContZneir" id="style2Body">
                                <!--
                           <li>
                             <span class="spanWidth90px"></span>
                             <span class="spanWidth50px"></span>
                             <span class="spanWidth70px"></span>
                             <span class="spanWidth70px"></span>
                             <span class="spanWidth70px"></span>
                             <span class="spanWidth70px"></span>
                             <span class="spanWidth70px"></span>
                           </li>
                            -->
                            </ul>
                        </div>
                        <div class="beitouToolsfotter">
                            <p><input  type="checkbox" value="1" name="stopOnWin" checked="checked" />
                                <span style="font-weight:bold;">中奖后停止</span>&nbsp;&nbsp;投注多期时，当某期中奖后，自动放弃后面几期投注操作。</p>
                            <!--
                      <p><input type="checkbox" value="" checked="checked" />
                          <span style="font-weight:bold;">出号后放弃</span>&nbsp;&nbsp;延后投注时，投注号码提前开出，自动放弃后面几期投注操作。</p>
                      -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $this->import('public_foot') ?>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jqueryui.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.slider.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $imgCdnUrl ?>/js/game/min/game_pks.min.js?v=<?php echo time(); ?>" type="text/javascript"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/qiehuangame.js?v=<?php echo $html_version ?>"></script>
    <script type="text/javascript">
        $(function() {
            var methods = <?php echo $methods ?>;
            $.init({
                lotteryId: <?php echo $lottery['lottery_id']; ?>, lotteryName: '<?php echo $lottery['cname']; ?>', property_id:<?php echo $lottery['property_id']; ?>, prizeRate: <?php echo 1 - $lottery['total_profit']; ?>, lotteryType: <?php echo $lottery['lottery_type']; ?>, methods: methods[<?php echo $lottery['lottery_id'] ?>], maxCombPrize: <?php echo $maxCombPrize; ?>, openedIssues: <?php echo $json_openedIssues; ?>, minRebateGaps: <?php echo $minRebateGaps; ?>, rebate: <?php echo $rebate; ?>, defaultMode: 1, defaultRebate: <?php echo $rebate; ?>, missHot: <?php echo $json_missHot; ?>
            });
            $(".moneyMode a").eq(0).click();
        });
    </script>
</body>
</html>
