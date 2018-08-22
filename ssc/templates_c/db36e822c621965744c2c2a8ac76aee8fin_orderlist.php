<!DOCTYPE HTML>  <!-- 个人账变 -->
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="webkit" name="renderer">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo config::getConfig('site_title'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $imgCdnUrl ?>/css/common_operate.css" />
    <?php $this->import('public_cssjs') ?>
</head>
<body>
<!-- 顶部导航信息部分 -->
<?php $this->import('public_header') ?>
<!-- End 顶部导航条 -->
<div class="operate_middle_page">
    <?php $this->import("public_usermenu"); ?>

    <div class="common_pages default_big_pageheight">
        <div class="default_record_search">
            <form method="get" action="" id="form1" name="form1">
                <ul>
                    <li>
                        <label class="search_record_gamelab">游戏：</label>
                        <select name="lotteryId" class="default_common_input" >
                            <option value="0">所有游戏</option>
                            <?php foreach ($lotteries as $k => $v): ?>
                                <option value="<?php echo $v['lottery_id']; ?>" <?php if($lotteryId == $v['lottery_id']) echo 'selected';?>><?php echo $v['cname']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </li>
                    <li>
                        <label class="search_record_datelab">时间：</label>
                        <div class="record_date_range">
                            <input size="15" name="startDate" id="startDate" my97mark="false" onfocus="WdatePicker({dateFmt: 'yyyy-MM-dd'})" class="default_common_input datepicker85"/>
                            <em>到</em>
                            <input size="15" name="endDate" id="endDate" my97mark="false" onfocus="WdatePicker({dateFmt: 'yyyy-MM-dd'})" class="default_common_input datepicker85"/>
                        </div>
                    </li>
                    <li>
                        <label class="search_record_statuslab">帐变类型：</label>
                        <select name="orderType" class="default_common_input" >
                            <option value="0">请选择</option>
                            <?php foreach ($orderTypes as $k => $v): ?>
                                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </li>
                    <li>
                        <input type="hidden" name="c" value="fin"/>
                        <input type="hidden" name="a" value="<?php echo ACTION; ?>"/>
                        <input type="submit" value="搜索" class="default_record_searchbtn" name="submit">
                    </li>
                    <li class="float_right">
                        <div class="user-page-boxs">
                            <?php echo $pageList; ?>
                        </div>
                    </li>
                </ul>
            </form>
        </div>

        <table class="defoperate_record_tablist">
            <thead>
            <tr class="defoperate_record_tabtitle">
                <th >用户名</th>
                <th >订单编号</th>
                <th >游戏</th>
                <th >期号</th>
                <th >帐变时间</th>
                <th >类型</th>
                <th >帐变金额</th>
                <th >余额</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($orders): ?>
                <?php foreach ($orders as $v): ?>
                    <tr class="recordsContent">
                        <td><?php echo $v['from_username']; ?></td>
                        <td>
                            <?php if (!empty($v['wrap_id'])): ?>
                                <a href="javascript:showPackageDetail('<?php echo $v['wrap_id']; ?>');"><?php echo $v['wrap_id']; ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo $lotteries[$v['lottery_id']]['cname']; ?></td>
                        <td><?php echo $v['issue']; ?></td>
                        <td><?php echo $v['create_time']; ?></td>
                          <?php if ($v['remark']): ?>
                            <td style="line-height:33px;">
                            <span><?php echo $orderTypes[$v['type']]; ?></span>
                            <br />
                            <span title="<?php echo $v['remark']; ?>" style="margin-bottom:-11px;width: 180px;display: inline-block;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">
                          <?php echo $v['remark']; ?>
                            </span>
                        </td>
                        <?php else: ?>
                             <td><?php echo $orderTypes[$v['type']]; ?></td>
                        <?php endif; ?>
                        <td><?php echo $v['amount']; ?></td>
                        <td><?php echo $v['balance']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="8"><div class="bottom_info">注：只能查询15天以内的数据</div></th>
                </tr>
            <?php else: ?>
                <tr>
                    <th colspan="8" class="text_center">暂无数据显示</th>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
  <?php $this->import('public_foot') ?>
<script type="text/javascript" src="<?php echo $imgCdnUrl ?>/js/jquery.plugin.js"></script><!--jquery小插件-->
<script src="js/My97DatePicker/WdatePicker.js"></script>
<script>
    $(function() {
        //设置默认值
        $('select[name=orderType]').val('<?php echo $orderType; ?>');
        $('#startDate').val('<?php echo $startDate; ?>');
        $('#endDate').val('<?php echo $endDate; ?>');
    });

    function showPackageDetail(wrapId) {
        var str_award = '普通玩法';
        layer.open({
            type: 2,
            shadeClose: true,
            title: str_award+'&nbsp;&nbsp;订单编号：'+wrapId,
            closeBtn: false,
            shade: [0.3, '#000'],
            border: [0],
            area: ['824px','550px'],
            content: ['index.jsp?c=game&a=packageDetail&wrap_id='+wrapId]
        });
        $('div.xubox_title', document).addClass('layui-layer-title');
    }
</script>
</body>
</html>