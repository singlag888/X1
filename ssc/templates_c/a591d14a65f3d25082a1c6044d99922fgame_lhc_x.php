<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="webkit" name="renderer">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo config::getConfig('site_title'); ?></title>
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/global_reset.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/all_LightBlue_x.css?v=<?php echo $html_version ?>" />
        <?php $this->import('public_cssjs') ?>
        <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/layer-v2.4/layer.js"></script>
        <!--        ==================================视频播放加载js css部分  start========================================================-->
        <?php if($live_type=='rtmp' || $live_type=='http'):?>
        <link href="<?php echo $imgCdnUrl ?>/js/videojs/video-js.css" rel="stylesheet" type="text/css">
            <script src="<?php echo $imgCdnUrl ?>/js/videojs/video.js"></script>
            <script>
                videojs.options.flash.swf = "<?php echo $imgCdnUrl ?>/js/videojs/video-js.swf";
            </script>
        <?php elseif($live_type=='hls'):?>
            <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/fwplayer/flowplayer-3.2.12.min.js"></script>
            <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/fwplayer/flowplayer.ipad-3.2.12.min.js"></script>
        <?php endif;?>
        <!--        ==================================视频播放加载js css部分  end========================================================-->
    </head>
    <body style="background:none;">
    <?php $this->import('public_header') ?>
    <div class="tz_nr">
            <div class="NumberBox5" style="padding:0 15px;">
                <div class="Prizepool_lhc">
                    <div class="cz_logo"><img src="<?php echo $imgCdnUrl?>/images/LHC.png" alt=""></div>
                    <h3>香港⑥合彩</h3>
                    <p>第<span id="curIssueSpan" class="thisIssueSpan"></span>期</p>
                    <a target="_blank" class="bt01" href="?c=game&a=chart&lottery_id=<?php echo $lottery['lottery_id']; ?>"><span class="zoushi"></span>号码走势</a>
                </div>
                <div class="Lottmain_lhc">

                    <!--===================开奖视频区域   start================-->
                    <?php if($live_switch==1):?>
                        <button class="video_button">
                            开奖直播
                        </button>
                        <div class="video_area" style="display: none">
                            <div class="top_area">
                                <span>开奖视频</span>
                                <span>×</span>
                            </div>
                            <?php if($live_type=='rtmp' || $live_type=='http'):?>
                                <div class="test-vjs">

                                </div>
                            <?php elseif($live_type=='hls'):?>
                                <div id="flashls_vod">
                                </div>
                            <?php endif;?>
                        </div>
                        <script>
                            $(function () {
                                var player;
                                $('.video_button').click(function () {
                                    var live_src="<?php echo $live_src;?>";
                                    var live_type="<?php echo $live_type;?>";
                                    if(live_src==''){
                                        layer.alert("视频源为空,不可播放!", {icon: 7});
                                        return false;
                                    }
                                    if(live_type==''){
                                        layer.alert("视频源格式错误!", {icon: 7});
                                        return false;
                                    }
                                    if(live_type=='http'){
                                        var source='';
                                        <?php if(preg_match('/\.mp4/', $live_src, $matches)):?>
                                        source='<source src="'+live_src+'" type="video/mp4" />';
                                        <?php elseif(preg_match('/\.webm/', $live_src, $matches)):?>
                                        source='<source src="'+live_src+'" type="video/webm" />';
                                        <?php elseif(preg_match('/\.ogv/', $live_src, $matches)):?>
                                        source='<source src="'+live_src+'" type="video/ogg" />';
                                        <?php elseif(preg_match('/\.flv/', $live_src, $matches)):?>
                                        source='<source src="'+live_src+'" type="video/flv" />'+
                                            '<source src="'+live_src+'" type="video/x-flv" />';
                                        <?php else:?>
                                        source='视频格式暂不支持';
                                        <?php endif;?>
                                        if (typeof (player) !== 'undefined' && player.destroy) {
                                            player.dispose();
                                        }
                                        var obj='<video id="example_video_1" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="none" width="584" height="350" data-setup="{}">'+source+'</video>';
                                        $(".test-vjs").empty().append(obj);
                                        player=videojs('example_video_1');
                                        player.width(584);
                                        player.height(350);
                                        player.src(live_src);
                                        player.play();
                                    }else if(live_type=='hls'){
                                        $('#flashls_vod').css('width',584);
                                        $('#flashls_vod').css('height',350);
                                        flowplayer("flashls_vod", "<?php echo $imgCdnUrl ?>/js/fwplayer/flowplayer.swf", {
                                            plugins: {
                                                flashls: {
                                                    url: '<?php echo $imgCdnUrl ?>/js/fwplayer/flashlsFlowPlayer.swf',
                                                }
                                            },
                                            clip: {
                                                url: live_src,
                                                //live: true,
                                                urlResolvers: "flashls",
                                                provider: "flashls"
                                            }
                                        }).ipad();
                                    }else if(live_type=='rtmp'){
                                        if (typeof (player) !== 'undefined' && player.destroy) {
                                            player.dispose();
                                        }
                                        var obj='<video id="example_video_1" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="none" width="584" height="350" data-setup="{}">' +
                                            '<source src="'+live_src+'" type="rtmp/flv"/>' +
                                            '</video>';
                                        $(".test-vjs").empty().append(obj);
                                        player=videojs('example_video_1');
                                        player.width(584);
                                        player.height(350);
                                        player.src(live_src);
                                        player.play();
                                    }else{
                                        layer.alert("请后台设置正确的视频源格式!", {icon: 7});
                                        return false;
                                    }
                                    $(this).attr('disabled','disabled');
                                    $('.top_area').show();
                                    $('.video_area').show();
                                });

                                $('.top_area span:nth-child(2)').click(function () {
                                    $('.video_button').attr('disabled',false);
                                    var live_type="<?php echo $live_type;?>";
                                    if(live_type=='flash'){
                                        $('.live_video').remove();
                                    }else if(live_type=='http' || live_type=='rtmp'){
                                        player=videojs('example_video_1');
                                        player.dispose();
                                    }else if(live_type=='hls'){
                                        $('#flashls_vod_api').remove();
                                    }
                                    $('.video_area').hide();
                                });
                            })
                        </script>
                    <?php endif;?>
                    <!--===================开奖视频区域   end================-->


                    <div class="GameName">
                        <span id="curLotteryName2"></span>
                        <label>第<em id="lastIssueSpan" class="lastIssueSpan"></em>期</label>
                    </div>
                    <div id="pendingText" class="pendingText">正在开奖中...</div>
                    <div class="lotteryNum GameNuberFont_lhc" id="thisIssueNumUL"></div>
                    <div id="original_code" style="display:none"></div>
                </div>
                <div class="playTimer_lhc thisIssueInfo" id="thisIssueInfo">
                    <p class="thisIssue">
                        <span class="issue thisIssueDIV">第<span id="thisIssueSpan"></span>期</span>
                        <em class="clock" id="thisIssueTimerIcon"></em>
                    </p>
                    <span class="Timer fix"><div class="thisIssueRemainTime" id="thisIssueRemainTime"><span>0</span><span>0</span><em>:</em><span>0</span><span>0</span><em>:</em><span>0</span><span>0</span></div></span>
                </div>
                <!-- <div class="GameNumberResult lotteryInfor" id="thisIssueMoreInfo"></div> -->
            </div>

        <!-- 中间选择游戏部分 -->
        <div class="GameBoxall SubGamePlatePadding">
            <div class="Gamepart">
                <div class="subTopBar">
                   
                    <!-- 投注玩法 -->
                    <div class="playNav">
                        <ul class="lotteryTab" id="methodGroupContainer">
                            
                        </ul>
                    </div>
                    <div class="crumbs"></div>
                </div>
                <div class="clear"></div>
                <!-- 投注主体部分 -->
                <div class="PlayCenter">
                    <div class="playControlBox">
                        <div id="methods" class="methods">

                        </div>
                        <div class="clear"></div>
                        <!-- 投注选号 -->
                        <div class="choMainTab">
                            <div class="chooseNO selectArea" id="selectArea"></div>
                            <div class="MachineSele">
                                <?php if (in_array($lottery['lottery_id'], array(1, 3, 4, 8, 11))): ?>
                                <div class="MachineSeleBtn">
                                    <input type="button" value="机选10注" num="10" class="Mach10 custBtnStyle selectRandomBtn">
                                    <input type="button" value="机选50注" num="50" class="Mach50 custBtnStyle selectRandomBtn">
                                </div>
                                <?php endif;?>
                            </div>
                            <datalist id="itemlist">
                                <option>100</option>
                                <option>200</option>
                                <option>500</option>
                                <option>1000</option>
                            </datalist>
                        </div>
                        <div class="bonusSlide fix fr" style="float: left;height: 34px;line-height:31px;">
                        <span>返点:</span>
                        <div id="selectRebate" class="selectRebate"></div>
                        <span id="rebateValue" class="rebateValue"></span>
                        <!-- <span class="rebateValue rebateValue_lmlx" style="display: none;"></span> -->
                        </div>
                        <a hidden="" href="javascript:void(0)" class="quickSelect" id="quickSelect" title="快捷投注" data-val="0">快捷投注</a>
                        <div class="clear"></div>
                        <!-- 添加号码 选择按钮 -->
                        <!-- <div class="FatherCodeBtn fix">
                            <input type="button" value="添加号码" id="selectCodeBtn" class="selectCodeBtn">
                            <input type="button" value="一键投注" id="selectCodeBtnFast" class="secondBtn">
                        </div> -->
                        <div class="jj-box"><!--奖金-->
                        <div class="fl moneySlect" style="display:none;">
                                <input type="hidden" id="modesDIV" style="display:none;" value="1">
                                <!-- <span class="moneyMode"><a href="javascript:void(0);" id="yuan">2元</a><a href="javascript:void(0);" id="jiao">2角</a><a href="javascript:void(0);" id="fen">2分</a><br/><a href="javascript:void(0);" id="yuanYi">1元</a><a href="javascript:void(0);" id="jiaoYi">1角</a><a href="javascript:void(0);" id="li">2厘</a></span> -->
                        </div>

                        </div>
                        <div class="chooseOKBtn gameMoney fix" >
                            <div class="fl prizeQuestion">
                                <div class="fl choPirze" id="choPirze">
                                    <ul class="prizeList">
                                        <li class="pirze1" data-value="10">￥10</li>
                                        <li class="pirze2" data-value="50">￥50</li>
                                        <li class="pirze3" data-value="100">￥100</li>
                                        <li class="pirze4" data-value="500">￥500</li>
                                        <li class="pirze5" data-value="1000">￥1000</li>
                                        <li class="pirze6" data-value="5000">￥5000</li>
                                    </ul>
                                </div>
                                <div class="multipleTotal fl" id="totalSingleInfo" style="margin-left: 0px; display: block;">
                                <div class="fl gameLeftLI" id="singleInfo" style="line-height: 30px; margin-right: 15px; display: block;">
                                    您当前选择了<em class="redNum" id="betCount">0</em>注<!-- ,共￥<em class="redNum" id="betAmount">0.000</em> -->
                                </div>
                                <div class="x_lhxlin30 fl">
                                    <!-- <span class="fl">倍数</span><span id="minusBtn" type="button" class="minusBtn" >-</span> -->
                                    投注金额：<input value="" placeholder="投注金额" id="multiple" name="multiple" class="txtStyle txtaddSty" maxlength="5" style="display: inline-block;">
                                    <!-- <span id="plusBtn" type="button" class="plusBtn" >+</span> -->
                                </div>
                            </div>
                                <i class="question ShowTips methodTipInfo fr" id="methodTipInfo" title="">
                                   <span> 玩法介绍</span>
                                    <div class="methodDesc" id="methodDesc"></div>
                                </i>
                                <select id="curPrizeSpan" style="display:none;"></select>

                            </div>
                        </div>
                        <div class="totalcont fix">

                            <input type="button" value="确认投注" class="CantapCodeBtn" id="confirmBtn">
                            <a href="javascript:void(0)" class="clearProjectBtn" id="clearProjectBtn" title="删除投注内容">清空</a>
                        </div>
                        <div class="mutiChoose" style="display: none;">
                            <dl>
                                <dt>
                                <div class="selectDiv">
                                    
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
                             <div style="display: none;">
                                 总计<em class="redNum" id="totalBetCount">0</em>注,
                                 合计￥<em class="redNum" id="totalBetAmount">0.00</em>, 
                                 盈利￥<em class="redNum" id="totalWin">0.00</em>
                                 (默认最高奖金盈利)
                             </div>
                             </span>
                        </div>
                    </div>
                    <div class="GameNumber">
                        <div class="todayDraw">
                            <h4 class="todayDrawBtn" id="todayDrawBtn">最近开奖<a target="_blank" href="?c=game&a=chart&lottery_id=<?php echo $lottery['lottery_id']; ?>"><span class="tz_m">走势图&gt;&gt;</span></a></h4>
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
        </div>
        </div>
    <?php $this->import('public_foot') ?>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jqueryui.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/ext.js"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.slider.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $imgCdnUrl ?>/js/game/min/game_lhc_x.min.js?v=<?php echo time(); ?>" type="text/javascript"></script>
    <script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/qiehuangame.js?v=<?php echo $html_version ?>"></script>
    <script type="text/javascript">
        $(function() {
            var methods = <?php echo $methods ?>;
            $.init({
                lotteryId: <?php echo $lottery['lottery_id']; ?>, lotteryName: '<?php echo $lottery['cname']; ?>', property_id:<?php echo $lottery['property_id']; ?>, prizeRate: <?php echo 1 - $lottery['total_profit']; ?>, lotteryType: <?php echo $lottery['lottery_type']; ?>, methods: methods[<?php echo $lottery['lottery_id'] ?>], maxCombPrize: <?php echo $maxCombPrize; ?>, openedIssues: <?php echo $json_openedIssues; ?>, minRebateGaps: <?php echo $minRebateGaps; ?>, rebate: <?php echo $rebate; ?>, defaultMode: 1, defaultRebate: <?php echo $rebate; ?>, missHot: <?php echo $json_missHot; ?>
            });
            $(".moneyMode a").eq(3).click();
            $('#traceBtn').css({'display':'none'});
            /***金额输入***/
            // $('#choPirze li').click(function(){
            //     $('#multiple').val($(this).data('value'));
            //     $('.f-left').val($(this).data('value'));
            // });
        });
    </script>
</body>
</html>
