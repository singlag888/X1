<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="webkit" name="renderer">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo config::getConfig('site_title'); ?></title>
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/global_reset.css?v=<?php echo $html_version_num; ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/all_LightBlue.css?v=<?php echo time(); ?>" />
        <?php $this->import('public_cssjs') ?>
    </head>

    <body style="background:none;">
            <?php $this->import('public_header') ?>
            <div class="tz_nr">
            <div class="NumberBox5" style="padding:0 15px;">
                <div class="Prizepool_lhc">
                    <div class="cz_logo"><img src="<?php echo $imgCdnUrl?>/images/SSQ.png" alt=""></div>
                    <h3>双色球</h3>
                    <p>第<span id="curIssueSpan" class="thisIssueSpan"></span>期</p>
                    <a target="_blank" class="bt01" href="index.jsp?c=game&a=chart&lottery_id=<?php echo $lottery['lottery_id']; ?>"><span class="zoushi"></span>号码走势</a>
                </div>
                <div class="Lottmain_lhc">
                    <div class="GameName">
                        <span id="curLotteryName2"></span>
                        <label>第<em id="lastIssueSpan" class="lastIssueSpan"></em>期</label>
                    </div>
					<div id="pendingText" class="pendingText">正在开奖中...</div>
                    <div class="lotteryNum GameNuberFont_ssq" id="thisIssueNumUL"></div>
                    <div id="original_code" style="display:none"></div>
                </div>
                <div class="playTimer_lhc thisIssueInfo" id="thisIssueInfo">
                    <p class="thisIssue">
                        <span class="issue thisIssueDIV">第<span id="thisIssueSpan"></span>期</span>
                        <em class="clock" id="thisIssueTimerIcon"></em>
                    </p>
                    <span class="Timer fix"><div class="thisIssueRemainTime" id="thisIssueRemainTime"><span>00</span><em>:</em><span>00</span><em>:</em><span>00</span></div></span>
                </div>
                <!-- <div class="GameNumberResult lotteryInfor" id="thisIssueMoreInfo"></div> -->
            </div>

        <!-- 中间选择游戏部分 -->
        <div class="GameBoxall SubGamePlatePadding">
            <div class="Gamepart">
                <div class="subTopBar">
                    <!-- <div class="SubTit">
                        <h3 class="results" id="curLotteryName"></h3>
                    </div> -->
                    <!-- 投注玩法 -->
                    <div class="playNav">
                        <ul class="lotteryTab" id="methodGroupContainer">
                            <!--
                            <li id="methodGroup_0"><label>后一</label>
                                    <ul id="method_0" class="methodPopStyle" style="display: none;">
                                        <li id="method_0_1">后一直选</li>
                                        <li id="method_0_2">五星选</li>
                                    </ul>
                            </li>
                            -->
                        </ul>
                    </div>
                    <div class="crumbs"></div>
                </div>
                <div class="clear"></div>
                <!-- 投注主体部分 -->
                <div class="PlayCenter">
                    <div class="playControlBox">
						<div id="methods" class="methods"></div>
                        <!-- <div class="top thisIssueInfo">
                            <div class="PlayGame_way"><span>当前玩法：<em class="curMethod" id="curMethod"></em></span>
                            </div>
                            <button class="inputBtn btnStyle4 fr" id="inputBtn">手工录入</button>
                        </div> -->
                        <div class="clear"></div>
                        <!-- 投注选号 -->
                        <div class="choMainTab">
                            <div class="chooseNO selectArea" id="selectArea"></div>
                            <!-- <div class="MachineSele">
                                <?php if (in_array($lottery['lottery_id'], array(1, 3, 4, 8, 11))): ?>
                                <div class="MachineSeleBtn">
                                    <input type="button" value="机选10注" num="10" class="Mach10 custBtnStyle selectRandomBtn">
                                    <input type="button" value="机选50注" num="50" class="Mach50 custBtnStyle selectRandomBtn">
                                </div>
                                <?php endif;?>
                            </div> -->
                        </div>
                        <div class="clear"></div>
                        <!-- 添加号码 选择按钮 -->
                        <div class="FatherCodeBtn fix">
                            <input type="button" value="添加号码" id="selectCodeBtn" class="selectCodeBtn">
                            <input type="button" value="一键投注" id="selectCodeBtnFast" class="secondBtn">
                        </div>
                        <div class="chooseOKBtn gameMoney fix" >
                            <hr>
                            <div class="fl moneySlect">
                                <input type="hidden" id="modesDIV" style="display:none;" value="1">
                                <span class="moneyMode"><a href="javascript:void(0);" id="yuan">2元</a><a href="javascript:void(0);" id="jiao">2角</a><a href="javascript:void(0);" id="fen">2分</a><br/><a href="javascript:void(0);" id="yuanYi">1元</a><a href="javascript:void(0);" id="jiaoYi">1角</a><a href="javascript:void(0);" id="li">2厘</a></span>
                           </div>
                           <div class="fl multipleTotal" id="totalSingleInfo">
                               <div style="height: 25px;margin-bottom: 8px">
                                   <span class="fl">倍数</span><span id="minusBtn" type="button" class="minusBtn" >-</span><input value="1" id="multiple" name="multiple" class="txtStyle txtaddSty" maxlength="5"/><span id="plusBtn" type="button" class="plusBtn" >+</span>
                                </div>
                                <div class="fl gameLeftLI" id="singleInfo">
                                   您当前选择了<em class="redNum" id="betCount">0</em>注,共￥<em class="redNum" id="betAmount">0.000</em>
                                </div>
                           </div>
                           <div class="fr prizeQuestion">
                               <div class="bonusSlide fix">
                                   <span>奖金/返点</span>
                                   <div id="selectRebate" class="selectRebate"></div>
                                   <span id="rebateValue" class="rebateValue"></span>
                               </div>
                               <i class="question ShowTips methodTipInfo" id="methodTipInfo" title="">
                                   玩法介绍<label>?</label>
                                   <div class="methodDesc" id="methodDesc"></div>
                               </i>
                               <select id="curPrizeSpan" style="display:none;"></select>
                            </div>
                        </div>
                        <div class="mutiChoose">
                            <dl>
                                <dt>
                                <div class="selectDiv">
                                    <!-- <select class="SelectStyle" id="curPrizeSpan">
                                    </select>
                                    <select class="SelectStyle" id="modesDIV">
                                    </select> -->
                                </div>
                                </dt>
                                <dd>
                                    <div class="projectListTitle fix"><span class="width1">玩法</span><span class="width2">号码</span><span class="width3">注数</span><span class="width4">倍/元</span></div>
                                    <ul class="projectList" id="projectList">
                                    </ul>
                                </dd>
                            </dl>
                        </div>
                        <!-- 添加号码 选择按钮 -->
                        <div class="chooseOKBtn" style="display:none;">
                            <div class="fr">
                                <input type="hidden" value="" id="token">
                            </div>
                        </div>
                        <div class="playControlBox_bottom">
                             <span class="fl">
                                 总计<em class="redNum" id="totalBetCount">0</em>注,
                                 合计￥<em class="redNum" id="totalBetAmount">0.00</em>, 
                                 盈利￥<em class="redNum" id="totalWin">0.00</em>
                                 (默认最高奖金盈利)
                                 <div class="gameTZbtn fr">
                                 <a href="javascript:void(0)" class="del clearProjectBtn" id="clearProjectBtn" title="删除投注内容">清空</a>
                                     <?php if($lottery['lottery_id'] != 9 && $lottery['lottery_id'] != 10): ?>
                                     <input type="button" value="追号" class="btnStyle m05 Chasing_ball" id="traceBtn" mark="0"/>
                                     <?php endif; ?>
                                     <input type="button" value="确认投注" class="CantapCodeBtn confirm"  id="confirmBtn"/>
                                 </div>
                             </span>
                        </div>
                    </div>
                    <div class="GameNumber">
                        <div class="todayDraw">
                            <h4 class="todayDrawBtn" id="todayDrawBtn">最近开奖<a target="_blank" href="index.jsp?c=game&a=chart&lottery_id=<?php echo $lottery['lottery_id']; ?>"><span class="tz_m">走势图&gt;&gt;</span></a></h4>
                            <!--  今日开奖 -->
                            <div class="rightBoxSrt">
                                <div class="lotteryTodayBox">
                                    <ul class="lotteryTodayTitle" id="todayIssuesHead"></ul>
                                    <div class="lotteryTodayContent bet" id="todayIssuesBody"></div>
                                </div>
                            </div>
                        </div>
                        <!-- 今日开奖，排行 -->
                        <div class="TodayReward">
                            <div class="nav">
                               <!--  <ul class="navul">
                                    <li><a href="javascript:void(0)" class="todayDrawBtn" id="prizeRankBtn">中奖排行</a></li>
                                    <li><a href="javascript:void(0)" class="todayDrawBtn" id="todayBuyBtn">最近投注</a></li>
                                </ul> -->
                                <h4 class="todayDrawBtn" id="todayBuyBtn">今日投注<a href="?c=game&a=packageList"  target="_blank"><span class="tz_m">投注记录&gt;&gt;</span></a></h4>

                                <div class="rightBoxSrt">
                                <div class="lotteryTodayBox">
                                    <div class="lotteryTodayContent" id="todayBuyBody"></div>
                                </div>
                                </div>
                            </div>


                            <div class="clear"></div>
                        </div>
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
                    <p>
                        <input  type="checkbox" value="1" name="stopOnWin" checked="checked" />
                        <span style="font-weight:bold;">中奖后停止</span>&nbsp;&nbsp;投注多期时，当某期中奖后，自动放弃后面几期投注操作。</p>
                    <!--
              <p><input type="checkbox" value="" checked="checked" />
                  <span style="font-weight:bold;">出号后放弃</span>&nbsp;&nbsp;延后投注时，投注号码提前开出，自动放弃后面几期投注操作。</p>
              -->
                </div>
            </div>
        </div>
        </div>   </div>
        <?php $this->import('public_foot') ?>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js?v=<?php echo $html_version_num; ?>"></script><!--jquery小插件-->
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jqueryui.js?v=<?php echo $html_version_num; ?>"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.slider.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $imgCdnUrl ?>/js/game/min/game_ssq.min.js?v=<?php echo time(); ?>" type="text/javascript"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/qiehuangame.js?v=<?php echo $html_version ?>"></script>
    <script type="text/javascript">
        $(function() {
            var methods = <?php echo $methods ?>;
            $.init({
                lotteryId: <?php echo $lottery['lottery_id']; ?>, lotteryName: '<?php echo $lottery['cname']; ?>', property_id:<?php echo $lottery['property_id']; ?>, prizeRate: <?php echo 1 - $lottery['total_profit']; ?>, lotteryType: <?php echo $lottery['lottery_type']; ?>, methods: methods[<?php echo $lottery['lottery_id'] ?>], maxCombPrize: <?php echo $maxCombPrize; ?>, openedIssues: <?php echo $json_openedIssues; ?>, minRebateGaps: <?php echo $minRebateGaps; ?>, rebate: <?php echo $rebate; ?>, defaultMode: 1, defaultRebate: <?php echo $rebate; ?>, missHot: <?php echo $json_missHot; ?>
            });
            $(".moneyMode a").eq(0).click();
            $('#traceBtn').css({'display':'none'});
        });
    </script>
</body>
</html>
