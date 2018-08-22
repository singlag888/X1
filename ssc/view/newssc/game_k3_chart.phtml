<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <title><?php echo config::getConfig('site_title'); ?></title>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <meta content="no-cache" http-equiv="Pragma">
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/global_reset.css"/>
    <script src="<?php echo $imgCdnUrl ?>/js/jquery-1.8.3.min.js" type="text/javascript"></script>
    <script src="<?php echo $imgCdnUrl ?>/js/line.js" type="text/javascript" language="javascript"></script>
    <style>
        esun\: * {
            behavior: url(#default#VML)
        }

        #num {
            position: relative;
            z-index: 8;
            background: #DA8028;
            width: 16px;
            height: 16px;
            border-radius: 5px;
            border: 1px solid #fff;
            color: #fff;
            display: block;
            top: -30px;
            left: 13px;
            line-height: 14px;
            text-align: center;
        }

        .run {
            color: #ee5859
        }
        #odd,#even,#big,#small{cursor: pointer;}
    </style>
    <link href="<?php echo $imgCdnUrl ?>/css/chart.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="rightcon_k3">
    <div style="display:none;" class="win_bot" id="msgbox">
        <h5 id="msgtitle"></h5>
        <div class="clear"></div>
        <div class="wb_con">
            <p id="msgcontent"></p>
        </div>
        <div class="clear"></div>
        <a id="msgpre" onClick="javascript:prenotice();" href="#" class="wb_p">上一条</a><a
                onClick="javascript:nextnotice();" href="#" class="wb_n">下一条</a></div>
    <div class="rc_con history">
        <div class="rc_con_lt"></div>
        <div class="rc_con_rt"></div>
        <div class="rc_con_lb"></div>
        <div class="rc_con_rb"></div>
        <div class="rc_con_to">
            <div class="rc_con_ti">
                <div class="history_code">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" id="tm">
                        <tbody>
                        <tr>
                            <td width="300" align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>
                                    <font><?php echo $lottery['cname']; ?>：</font> 基本走势 </strong></td>
                            <td align="right">
                                <div class="Tabfixed">
                                    <form method="POST">
                                            <span>
                                            <label for="has_line">
                                                <input type="checkbox" id="has_line" value="checkbox" name="checkbox2"/>
                                                显示折线 </label>
                                            </span>&nbsp; <span>
                                            <label for="no_miss">
                                                <input type="checkbox" id="no_miss" value="checkbox" name="checkbox"/>
                                                不带遗漏</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                            </span>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="hrc_list">
                    <div class="hrl_list">
                        <table border="0" cellspacing="1" cellpadding="0" id="chartsTable">
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>
<div class="layer"></div>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/template-web.js"></script>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/game_k3_chart.js"></script>
<!--定义模板-->
<script type="text/html" id="head_k3">
    <tr class="th">
        <td rowspan="2" width="6.5%">
            <div style="width:auto;">期号</div>
        </td>
        <td colspan="3" rowspan="2">开奖号码</td>
        <td colspan="6">号码分布</td>
        <td colspan="4"><span id="odd" class="">奇数个数</span>/<span id="even" class="run">偶数个数</span></td>
        <td colspan="4"><span id="big" class="">大数个数</span>/<span id="small" class="run">小数个数</span></td>
        <td colspan="16">和值走势</td>
    </tr>
    <tr class="th">
        <td class="wdh">1</td>
        <td class="wdh">2</td>
        <td class="wdh">3</td>
        <td class="wdh">4</td>
        <td class="wdh">5</td>
        <td class="wdh">6</td>
        <td class="wdh">0</td>
        <td class="wdh">1</td>
        <td class="wdh">2</td>
        <td class="wdh">3</td>
        <td class="wdh">0</td>
        <td class="wdh">1</td>
        <td class="wdh">2</td>
        <td class="wdh">3</td>
        <td class="wdh">3</td>
        <td class="wdh">4</td>
        <td class="wdh">5</td>
        <td class="wdh">6</td>
        <td class="wdh">7</td>
        <td class="wdh">8</td>
        <td class="wdh">9</td>
        <td class="wdh">10</td>
        <td class="wdh">11</td>
        <td class="wdh">12</td>
        <td class="wdh">13</td>
        <td class="wdh">14</td>
        <td class="wdh">15</td>
        <td class="wdh">16</td>
        <td class="wdh">17</td>
        <td class="wdh">18</td>
    </tr>
</script>
<script type="text/html" id="content_k3">
    {{each list value}}
    <tr>
        <!--奖期-->
        <td class="issue">
            <div>{{value.issue}}</div>
        </td>
        <!--开奖号码-->
        {{each value.code codeItem}}
        <td align="center">
            <div class="wth">{{codeItem}}</div>
        </td>
        {{/each}}
        <!--号码分布-->
        <!--循环号码分布上的1-6个数字-->
        <% for(var j=1; j<=6; ++j) { %>
        {{set count = 0}}
        <!--循环开奖号码-->
        <% for(var i=0; i< value.code.length; ++i) {
        if(value.code[i] == j){
        count++;
        }
        %>
        <% } %>
        <% if(count > 0) { %>
        <td align="center" class="charball">
            <div class="tenthousand">
                <div class="ball01">{{j}}</div>
                <% if(count > 1) { %>
                <em id="num">{{count}}</em>
                <% } %>
            </div>
        </td>
        <% } else { %>
        <td align="center" class="wdh">
            <div class="tenthousand">
                <div class="ball03"></div>
            </div>
        </td>
        <% } %>
        <% } %>
        <!--奇数偶数-->
        <!--循环奇数上的0-3个数字-->
        <% for(var j = 0; j<=3; ++j) { %>
        <!--定义变量，奇数偶数-->
        {{set odd =0 }}
        {{set even = 0 }}
        <!--循环开奖号码-->
        <% for(var i=0; i< value.code.length; i++) {
        <!--判断条件:奇数偶数-->
        　if(value.code[i] % 2 > 0){
        ++odd;
        }else{
        ++even;
        }

        %>
        <% } %>
        <!--开奖号码奇偶判断-->
        <% if(odd == j) { %>
        <td align="center" class="charball oddcol">
            <div class="thousand">
                <div class="ball02" data-val="{{odd}}" data-odd="{{odd}}">{{odd}}</div>
            </div>
        </td>
        <% } else if(even == j) { %>
        <td align="center" class="charball evencol">
            <div class="thousand">
                <div class="ball04" data-val="{{even}}" data-even="{{even}}"></div>
            </div>
        </td>
        <% } else { %>
        <td align="center" class="wdh">
            <div class="thousand">
                <div class="ball04 oe_none"></div>
            </div>
        </td>
        <% } %>
        <% } %>
        <!--开奖号码大小判断-->
        <!--大小数个数-->
        <% for(var j= 0; j<= 3; ++j) { %>
        <!--定义大小-->
        {{set big = 0}}
        {{set small = 0}}
        <% for(var i= 0; i< value.code.length; ++i) {
        if(value.code[i] > 3){
        ++big;
        }else{
        ++small;
        }
        %>
        <% } %>
        <% if(big == j) { %>
        <td align="center" class="charball bigcol">
            <div class="hundred">
                <div class="ball01" data-val="{{big}}" data-big="{{big}}">{{big}}</div>
            </div>
        </td>
        <% }else if(small == j) { %>
        <td align="center" class="wdh smallcol">
            <div class="hundred">
                <div class="ball03" data-val="{{small}}" data-small="{{small}}"></div>
            </div>
        </td>
        <% } else { %>
        <td align="center" class="wdh">
            <div class="hundred">
                <div class="ball03 bs_none"></div>
            </div>
        </td>
        <% } %>
        <% } %>
        <!--和值走势-->
        <!--循环3-18的数字-->
        <% for(var j=3; j <=18; ++j) { %>
        <!--定义和值-->
        {{set sum = 0}}
        <% for(var i = 0; i< value.code.length; ++i)
        sum += value.code[i];
        %>
        <% if(sum == j) { %>
        <td align="center" class="charball">
            <div class="tenthousand">
                <div class="ball02">{{sum}}</div>
            </div>
        </td>
        <% } else { %>
        <td align="center" class="wdh">
            <div class="tenthousand">
                <div class="ball04"></div>
            </div>
        </td>
        <% } %>
        <% } %>
    </tr>
    {{/each}}
</script>
<script type="text/html" id="foot_k3">
    <tr class="th">
        <td rowspan="2">
            <div style="width:200px; margin: 0 auto; text-align: center;">期号</div>
        </td>
        <td colspan="3" rowspan="2">开奖号码</td>
        <td class="wdh">1</td>
        <td class="wdh">2</td>
        <td class="wdh">3</td>
        <td class="wdh">4</td>
        <td class="wdh">5</td>
        <td class="wdh">6</td>
        <td class="wdh">0</td>
        <td class="wdh">1</td>
        <td class="wdh">2</td>
        <td class="wdh">3</td>
        <td class="wdh">0</td>
        <td class="wdh">1</td>
        <td class="wdh">2</td>
        <td class="wdh">3</td>
        <td class="wdh">3</td>
        <td class="wdh">4</td>
        <td class="wdh">5</td>
        <td class="wdh">6</td>
        <td class="wdh">7</td>
        <td class="wdh">8</td>
        <td class="wdh">9</td>
        <td class="wdh">10</td>
        <td class="wdh">11</td>
        <td class="wdh">12</td>
        <td class="wdh">13</td>
        <td class="wdh">14</td>
        <td class="wdh">15</td>
        <td class="wdh">16</td>
        <td class="wdh">17</td>
        <td class="wdh">18</td>
    </tr>
    <tr class="th">
        <td colspan="6">号码分布</td>
        <td colspan="4">奇偶数</td>
        <td colspan="4">大小数</td>
        <td colspan="16">和值走势</td>
    </tr>
</script>


</body>
</html>
