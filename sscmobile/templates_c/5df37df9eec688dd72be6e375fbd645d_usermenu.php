<?php
$aMenus = [
    // 'fin_orderList'    => '个人中心',
    'game_packageList' => '投注记录',
    'game_traceList'   => '追号记录',
    // 'user_childList'   => '团队管理',
    // 'help_platformact' =>'平台活动',
    // 'help_latestnew'   =>'最新公告',
    // 'user_receiveBox'  =>'服务中心',
];

$aSubMenus = [
    'fin_orderList'  => [
        'fin_bindCard'  => '银行卡资料管理',
        'user_editPwd' => '登录密码修改',
        'user_editSecPwd' => '资金密码修改',
        'user_editSafePwd' => '安全码修改',
        'fin_orderList'  => '个人帐变',
    ],
    'user_childList' => [
//        'game_packageTeamList' => '团队投注明细',
        'user_childPackageList'=> '团队投注明细',
        'fin_depositList' => '团队充值明细',
        'fin_withdrawList' => '团队提现明细',
        'user_teamNowReport' => '团队即时报表',
//        'user_teamDayReport' => '团队日结报表',
        'user_teamGiftReport' => '团队活动报表',
        'user_teamWinReport' => '团队盈亏报表',
        'fin_teamOrderList' => '团队帐变报表',
        // 'user_childList' => '会员管理',
        'user_regChild' => '新增会员',
    ],
];

$aMenusToBelong = [
    'fin_unBindCard' => 'fin_bindCard',
    'fin_lockCard' => 'fin_bindCard',
    'user_setRebate' => 'user_childList',
    'user_recycle' => 'user_childList',
    'user_sendOutQuota' => 'user_childList',
    'user_sendMsg' => 'user_receiveBox',
    'user_sendBox' => 'user_receiveBox',
    'help_buy' => 'user_receiveBox',
    'help_statement' => 'user_receiveBox',
    'help_method' => 'user_receiveBox',
    'help_download' => 'user_receiveBox',
    'help_safe' => 'user_receiveBox',
    'help_deposit' => 'user_receiveBox',
    'help_withdraw' => 'user_receiveBox',
    'help_protocol' => 'user_receiveBox',
    'help_genroom' => 'user_receiveBox',
];

$routeName = CONTROLLER.'_'.ACTION;
if(in_array($routeName, array_keys($aMenusToBelong))){
    $routeName = $aMenusToBelong[$routeName];
}

// $subHtml = '<div class="defoperate_submenu_layer common_pages">';

foreach ($aSubMenus as $parentRouteName => $aMenu)
{
    if(in_array($routeName, array_keys($aMenu)))
    {
        foreach ($aMenu as $route => $name)
        {
            list($c, $a) = explode('_', $route);
            //todo 添加点击样式
            $subHtml .= '<a href="index.jsp?c='.$c.'&a='.$a .'" class="operate_submenu_btn'.( $route == $routeName? ' active': '').'">'.$name.'</a>';
        }
        $routeName = $parentRouteName;
        break;
    }
}
$subHtml .= '</div>';

$html = '<div class="operate_menu_layer">';

$flag = 1;
foreach ($aMenus as $route => $name)
{
    list($c, $a) = explode('_', $route);
    //todo 添加点击样式
    $html .= '<a href="index.jsp?c='.$c.'&a='.$a .'" class="user_menu'.$flag.' deTop_common_btn'.( $route == $routeName? ' active': '').'"><span>'.$name.'</span></a>';
    $flag++;
}

$htm=rtrim($html,'|');

$htm .= '</div>';

echo $htm.$subHtml;

?>