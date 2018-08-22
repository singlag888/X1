<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

/**
 * 游戏完整流程
 * 投注
 * 返点
 * 撤单
 * 中奖 已在methods.class.php中实现
 * 派奖
 * 为支持变动奖金组，去掉了pg_id
 * 对追号单的处理如更新完成期数、追中即停等功能在checkPrize()方法中，crond会定期执行
 * *****方法列表*****
 * buy()                trace()
 * cancelPackage()
 * batchRebate()        rebate()        cancelRebate()
 * batchCheckPrize()    checkPrize()
 * batchSendPrize()     sendPrize()     cancelPrize()
 * cancelTrace()
 *
 * 未完成：
 * 1.出号撤单——指当前期是01期，对从05期开始进行追号，若在第04期即开出号码，则本次追号自动停止，因一般情况再开相同号码的可能性不大，所以判断时刻应在检查中奖checkPrize()的时候;
 *      查找有选了此选项，并且还没有开始的追号单（start_issue<curIssue），预检查其追号是否中奖，如中奖即取消本次所有追号
 *
 */
class game
{
    const PACKAGE_MAX_AMOUNT = 300000;
    const SYSTEM_ADMIN_ID = 65535;
    const MAX_LIMIT_BETS = 100000; //限定最大投注数
    const REG_ALLOWED_CODES = '`^[\d_\-,大小单双庄闲]+$`'; //允许的投注代码 正则表达式
    static public $error  = '';

    /**
     *  用户前台投注 一次购买称为一个订单（package），一个订单可以包括不同玩法购买方案（project）
     *  购买成功时返回该订单编号
     * @param int $lottery_id
     * @param string $issue
     * @param float $curRebate
     * @param float $modes
     * @param int $user_id
     * @param array $projects 结构如下：
     *  array(0 => array(
     *      'method_id' => 1,   //玩法id 三星直选
     *      'code'      => '1,2,345',   //所购号码
     *      'single_num' => 3, //单倍注数，不作依赖，需要核实
     *      'single_amount' => 6,   //单倍金额，不作依赖，需要核实
     *      ),
     * )
     */
    static public function buy($lottery_id, $issue, $curRebate, $modes, $user_id, $projects, $multiple, $xgame = 0)
    {
        if (!is_numeric($lottery_id) || !preg_match('`^[\d-]{6,14}$`', $issue) || !is_numeric($curRebate) || $curRebate < 0 || !is_numeric($modes) || !isset($GLOBALS['cfg']['modes'][$modes]) || !is_numeric($user_id) || !is_array($projects) || !is_numeric($multiple) || $multiple <= 0 || !is_numeric($xgame)) {
            log2("errcode=999", func_get_args());
            throw new exception2('参数不对', 999);
        }

        if($xgame == 1 && $modes != 0.5){
            throw new exception2('信用玩法模式错误', 995);
        }

        //在有数据库操作前先定义$now，这是客户端提交的准确时间
        $now = date('Y-m-d H:i:s');

        //1.先检查彩种是否在售，是否在销售期，奖金组是否存在
        if (!$lottery = lottery::getItem($lottery_id)) {
            throw new exception2('彩种不存在或已停售', 1110);
        }
        if ($lottery['status'] != 8) {
            throw new exception2('彩种正在维护中，稍后开放', 1120);
        }
        if ($lottery_id != 15) {//不是秒秒彩的彩种进行奖期检查
            $currentIssue = issues::getCurrentIssue($lottery_id);
            if (!$currentIssue || $currentIssue['issue'] != $issue) {
                throw new exception2('当前奖期不在销售期', 1130);
            }
            if ($currentIssue['code'] != '') {
                throw new exception2('异常：当前奖期已有开奖号码'.$currentIssue['code'].'--'.$issue, 1131);
            }
            if(time() >= strtotime($currentIssue['end_sale_time'])){
                throw new exception2('购买时间超过奖期销售时间', 11331);
            }
        }

        //得到每种玩法的奖金 用于稍后判断是否超限
        if (!$prizes = prizes::getItems($lottery_id, 0, 0, 0, 1)) {
            throw new exception2("找不到基本组数据", 1160);
        }

        //2.再检查所提交号码$codes的正确性，玩法是否被禁用，核算单期注数、总金额是否正确，并判断用户奖金是否足够。。。
        //2.1 五星的手工录入限制，防止过多的数据，意义不大，也影响用户投注体验
        if (count($projects) > self::MAX_LIMIT_BETS) {
            throw new exception2("最大注数不得超过" . self::MAX_LIMIT_BETS . "注", 2101);
        }

        $totalAmount = $singleTotalAmount = $totalSingleNums = 0;
        $codeGroups = $singleNumGroups = $amountGroups = $tmp = array();
        $limits = config::getConfigs(array('prize_limit', 'multiple_limit','3d_prize_limit'));

        //倍数不能无限翻倍
        if ($limits['multiple_limit'] && $multiple > $limits['multiple_limit']) {
            throw new exception2("投注倍数超过最大允许数{$limits['multiple_limit']}", 2205);
        }

        //优化情节：把数据库查询放在可能的大数组循环是非常失败的！
        $methods = methods::getItemsById(array_unique(array_keys(array_spec_key($projects, 'method_id'))));
        foreach ($projects as $v) {
            //fixed 取消single_num和single_amount验证
            if (empty($v['method_id']) || !is_numeric($v['method_id']) || $v['method_id'] <= 0) {
                throw new exception2('号码选择错误', 2210);
            }
            if (!isset($methods[$v['method_id']])) {
                throw new exception2('玩法不存在', 2220);
            }

            if ($methods[$v['method_id']]['lottery_id'] != $lottery_id) {//防止出现非法改单
                throw new exception2('非法投注', 22202);
            }

            if (!$singleNums = methods::isLegalCode($methods[$v['method_id']], $v['code'])) {
                throw new exception2("所选号码不符规定({$v['code']})", 2230);
            }
            $v['single_num'] = $singleNums;

            //核实single_num fixed 取消single_num验证
            //接下来，单倍注数$singleNums * 模式 = 单倍价格，用来核算提交的金额是否正确，不正确拒绝继续
            //bugfix:浮点数运算后直接比较会出错
            $v['single_amount'] = round($singleNums * 2 * $modes, 3);

            $singleTotalAmount += $v['single_amount'];

            $totalSingleNums += $v['single_num'];

            //按玩法分组入库 避免手工录入产生太多记录
            $codeGroups[$v['method_id']][] = $v['code'];
            if (!isset($singleNumGroups[$v['method_id']])) {
                $singleNumGroups[$v['method_id']] = $v['single_num'];
            }
            else {
                $singleNumGroups[$v['method_id']] += $v['single_num'];
            }
            if (!isset($amountGroups[$v['method_id']])) {
                $amountGroups[$v['method_id']] = $v['single_amount'];
            }
            else {
                $amountGroups[$v['method_id']] += $v['single_amount'];
            }
        }

        $totalAmount = round($singleTotalAmount * $multiple, 3);  //本次订单总金额
        //logdump("总单倍注数$totalSingleNums , 总单倍金额$singleTotalAmount ，倍数$multiple ，总金额$totalAmount");
        if($totalAmount >= self::PACKAGE_MAX_AMOUNT){
            throw new exception2('每笔订单最大不能超过'.self::PACKAGE_MAX_AMOUNT.'元!', 22556);
        }
        if ($singleTotalAmount <= 0) {
            throw new exception2('投注额异常', 2255);
        }

        //3.开始事务
        $GLOBALS['db']->startTransaction();

        //bugfix:为保证对奖金操作的原子性，事务内读用户表时用FOR UPDATE可有效防止并发对该行的读！
        if (!$user = users::getItem($user_id, 8, true)) {
            throw new exception2('用户不存在或已被禁用', 1100);
        }

        //检查用户余额是否足够本次投注
        if ($user['balance'] < $totalAmount) {
            throw new exception2('用户余额不足', 2260);
        }

        //获取用户返点
        if (!$user['parent_tree']) {
            //throw new exception2('总代不可以玩游戏', 1135);   //总代可以玩游戏
            $userIds = array();
        } else {
            $userIds = explode(',', $user['parent_tree']);
        }
        array_push($userIds, $user_id);
        //$userIds[] = $user_id;  //还有自身
        $rebates = userRebates::getUsersRebates($userIds, $lottery['property_id']);


        /********************* snow 对返点进行重新排序**************************************/
        $rebates = self::_sortRebate($userIds, $rebates);
        /********************* snow 对返点进行重新排序**************************************/
        if (empty($rebates[$user_id])) {
            throw new exception2('用户奖金组不存在或被禁用', 1140);
        }
        if ($curRebate > $rebates[$user_id]) {
            throw new exception2("用户返点错误", 1150);
        }

        //判断奖金限红
        if ($limits['prize_limit']) {
            //$wxzxIds = [46,77,140,221,316,494];//五星直选ID
            foreach ($codeGroups as $method_id => $v) {
                $betCount = 1;
                // if(in_array($method_id,$wxzxIds)){//当五星直选时为了防止分注单投注时突破彩陪限额
                //     $betCount = count($v);
                // }

                $tmpPrize = ($multiple * $modes * $prizes[$method_id][1]['prize'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate) / (1 - $lottery['total_profit'])) * $betCount;
                $limitsPrize = $limits['prize_limit'];//正常彩陪限额
                if($lottery['property_id'] == 5){//低频彩陪限额
                    $limitsPrize = $limits['3d_prize_limit'];
                }

                if ($tmpPrize > $limitsPrize) {
                    throw new exception2("投注超过最大赔彩限额{$limitsPrize}", 2252);
                }
            }
        }

        //036114 如果是时时彩，加入对过高奖金组的检查：所选奖金不允许超过1950
        // if ($lottery['property_id'] == 1) {
            //直选转直注数	zx_max_comb
            //总水率		total_profit
            //公司最少留水	min_profit
            //最小返点差	min_rebate_gaps[0][from]	min_rebate_gaps[0][to]		min_rebate_gaps[0][gap]	 返点差
            // $prizeMode = intval(2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate));
            // if ($prizeMode > 1950) {
            //     throw new exception2("您可选择的最大返点为1950模式", 1151);
            // }
        // }

        //3.1 先生成订单
        $packageData = array(
            'user_id' => $user_id,
            'top_id' => $user['top_id'],
            'lottery_id' => $lottery_id,
            'issue' => $issue,
            'single_num' => $totalSingleNums,
            'multiple' => $multiple,
            'xgame' => $xgame,
            'cur_rebate' => $curRebate,
            'modes' => $modes,
            'amount' => $totalAmount, //本订单总金额
            'create_time' => $now,
            'cancel_status' => 0,
            'cancel_time' => '0000-00-00 00:00:00',
            'user_ip' => isset($GLOBALS['REQUEST']['client_ip']) ? $GLOBALS['REQUEST']['client_ip'] : '0.0.0.0',
            'proxy_ip' => isset($GLOBALS['REQUEST']['proxy_ip']) ? $GLOBALS['REQUEST']['proxy_ip'] : '0.0.0.0',
            'server_ip' => $_SERVER['HTTP_HOST'], //暂时先记录从哪台机器来的，因为极少数订单延迟好几分钟才到达DB机器
        );
        if (!projects::addPackage($packageData)) {
            log2("errno=3100", $packageData);
            throw new exception2('数据错误', 3100);
        }
        $package_id = $GLOBALS['db']->insert_id();

        //3.2 写方案表 按玩法组分
        $projectDatas = array();
        foreach ($codeGroups as $method_id => $v) {
            $projectDatas[] = array(
                'package_id' => $package_id,
                'user_id' => $user_id,
                'top_id' => $user['top_id'],
                'lottery_id' => $lottery_id,
                'method_id' => $method_id,
                'issue' => $issue,
                'xgame' => $xgame,
                'code' => implode("|", $v),
                'single_num' => $singleNumGroups[$method_id],
                'multiple' => $multiple, //各方案的倍数是一致的
                'amount' => $amountGroups[$method_id] * $multiple,
                'cur_rebate' => $curRebate,
                'modes' => $modes,
                'create_time' => $now,
                'hash_value' => md5($user_id . implode("|", $codeGroups[$method_id]) . $now),
            );
        }
        //在这里才是真正的插入数据,这里可以做优化循环插入非常失败
        # TODO : insertAll 已经提供了,用不用看你咯.
        foreach ($projectDatas as $projectData) {
            if (!projects::addItem($projectData)) {
                log2("errno=3110", $projectData);
                throw new exception2('数据错误', 3110);
            }
        }

        //3.3 本次订单的资金变化$totalAmount 投注扣款 及帐变
        if (!users::updateBalance($user_id, -$totalAmount)) {
            log2("errno=3120", $user, -$totalAmount);
            throw new exception2('数据错误', 3120);
        }
        $orderData = array(
            'lottery_id' => $lottery_id,
            'issue' => $issue,
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 401, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$totalAmount,

            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] - $totalAmount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $package_id,
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            log2("errno=3130", $orderData);
            throw new exception2('数据错误', 3130);
        }
        $user['balance'] += -$totalAmount; //及时更新最新余额
        //3.4 上级代理返点数据也插入返点表，但不马上更新上级的余额，以避免顶层代理余额更新过于频繁导致锁表，将另有批处理执行
        //先算出返点差及每个上级应返金额
        $diffRebates = array();
        $userGifts = false;
        $maxRebateLimit = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION);
        //注：测试帐号不允许向上级返点！
        if ($user['is_test'] == 0) {
            foreach ($rebates as $k => $v) {
                if ($v == reset($rebates)) {
                    $last_id = $k;
                }
                else {
                    $diffRebates[$last_id] = round($rebates[$last_id] - $v, REBATE_PRECISION);
                    //六合彩赔率不统一 不判断maxRebateLimit
                    if ($lottery['lottery_id'] != 21 && $lottery['lottery_id'] != 25 && ($diffRebates[$last_id] < 0 || $diffRebates[$last_id] > $maxRebateLimit)) {   //上级必定有返点，且不能超过总返点,现在可以和上级返点相等
                        log2("返点出错：3400...last_id:{$last_id},对应的返点差{$diffRebates[$last_id]}，最大返点:{$maxRebateLimit},{$user['username']}(user_id={$user['user_id']}) 的上级user_id={$last_id}的返点为{$rebates[$last_id]}，给他的返点为{$v}");
                        throw new exception2('返点出错', 3400);
                    }
                    $last_id = $k;
                }
            }
        }

        !!config::getConfig('rebate_toself', 1) && $diffRebates[$user_id] = $curRebate;    //注意：自身返点为投注时选择的具体返点

        //注意不能直接比较浮点数! 0.1 != 0.1
        if ($lottery['lottery_id'] != 21 && $lottery['lottery_id'] != 25 && round(array_sum($diffRebates), REBATE_PRECISION) > $maxRebateLimit) {    //返点总额当然不能超过总返点
            log2("返点出错:3410...{$user['username']}(user_id={$user['user_id']})", $diffRebates, array_sum($diffRebates), $maxRebateLimit);
            throw new exception2('返点出错', 3410);
        }

        //写user_diff_rebates表 返点为0的可以考虑不写入
        $udrData = array();
        foreach ($diffRebates as $k => $v) {
            if (round($totalAmount * $v, 4) > 0.0001) {
                $udrData[] = array(
                    'user_id' => $k,
                    'top_id' => $user['top_id'],
                    'lottery_id' => $lottery_id,
                    'issue' => $issue,
                    'package_id' => $package_id,
                    'package_user_id' => $user_id, //$k == $user_id ? 1 : 0,
                    'modes' => $modes,
                    'diff_rebate' => $v,
                    'rebate_amount' => round($totalAmount * $v, 4),
                    'status' => 0, //$k == $user_id ? 1 : 0,
                    'create_time' => date('Y-m-d H:i:s'),
                );
            }
        }

        if ($udrData && !userDiffRebates::addItems($udrData)) {
            log2("返点出错:3420...{$user['username']}(user_id={$user['user_id']})", $udrData);
            throw new exception2('返点出错', 3420);
        }

        //3.5 用户自己的返点也可以先不返 提高投注速度
        // $selfRebateAmount = round($totalAmount * $diffRebates[$user_id], 4);
        // if ($selfRebateAmount > 0) {    //有返点才写帐变
        //    if (!users::updateBalance($user_id, $selfRebateAmount)) {
        //        log2("errno=3140", $user, $totalAmount);
        //        throw new exception2('数据错误', 3500);
        //    }
        //    $orderData = array(
        //        'lottery_id' => $lottery_id,
        //        'issue' => $issue,
        //        'from_user_id' => $user_id,
        //        'from_username' => $user['username'],
        //        'to_user_id' => 0,
        //        'to_username' => '',
        //        'type' => 301,    //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖 414嘉奖
        //        'amount' => $selfRebateAmount,
        //        'pre_balance' => $user['balance'],
        //        'balance' => $user['balance'] + $selfRebateAmount,
        //        'create_time' => date('Y-m-d H:i:s'),
        //        'admin_id' => 0,
        //    );

        //    if (!orders::addItem($orderData)) {
        //        log2("errno=3150", $orderData);
        //        throw new exception2('数据错误', 3510);
        //    }
        //    $user['balance'] += $selfRebateAmount; //及时更新最新余额
        // }
        //检查是否超出xlock限额
        $result = array();
        if($lottery['lottery_id'] == 9 || $lottery['lottery_id'] == 10){//封锁3D
            $tmpCodeGroups = [];
            foreach($codeGroups as $methodId => $code){
                if ($methods[$methodId]['is_lock']) {
                    $tmpCodeGroups[$methods[$methodId]['name']] = $code;
                }
            }
            if($tmpCodeGroups){
                $result = locks::check3DP3MultipleLimit($lottery['lottery_id'] , $issue, $tmpCodeGroups, intval($modes * $multiple * 1930));
                if ($result['result']) {
                    throw new exception2('尊敬的客户：<br/>以下投注号码在本期已经达到购买上限，祝您下一期中购得您心仪的号码，祝您好运多多！<br/>' . implode(",", array_keys($result['allP'])), 22112);
                }
            }

        } else {
            foreach ($codeGroups as $methodId => $v) {
                if ($methods[$methodId]['is_lock']) {
                    $tmpCodes = array();
                    foreach ($v as $vv) {
                        $tmpCodes = array_merge($tmpCodes, game::getExpandCodes($methods[$methodId]['name'], $vv));
                    }

                    $result = locks::checkMultipleLimit($lottery, $methods[$methodId], $issue, $tmpCodes, intval($modes * $multiple * methods::computeMethodPrize($lottery_id, $methodId)));

                    if ($result) {
                        throw new exception2('尊敬的客户：<br/>以下投注号码在本期已经达到购买上限，祝您下一期中购得您心仪的号码，祝您好运多多！<br/>' . implode(",", $result), 22111);
                    }
                }
            }
        }
        //更新xlock限额
        if($lottery['lottery_id'] == 9 || $lottery['lottery_id'] == 10){
            if(isset($result['allP'])){
                $countAllP = array_sum($result['allP']);
                $amount =  intval($totalAmount/$countAllP * 1930 * $modes * $multiple);
                $res = locks::update3DP3MultipleLimit($lottery['lottery_id'], $issue, $result['allP'],$amount);
                if (!$res) {
                    throw new exception2('数据错误', 31601);
                }
            }
        }else{
            foreach ($codeGroups as $methodId => $v) {
                if ($methods[$methodId]['is_lock']) {
                    $tmpCodes = array();
                    foreach ($v as $vv) {

                        $tmpCodes = array_merge($tmpCodes, game::getExpandCodes($methods[$methodId]['name'], $vv));
                    }

                    $result = locks::updateMultipleLimit($lottery, $methods[$methodId], array($issue), ($tmpCodes), intval($modes * $multiple * methods::computeMethodPrize($lottery_id, $methodId)));
                    if (!$result) {
                        if($lottery['lottery_id'] == 21 || $lottery['lottery_id'] == 22){
                            throw new exception2('该玩法于次日早8:00开始销售', 31601);
                        } else {
                            throw new exception2('数据错误', 31600);
                        }
                    }
                }
            }
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return $package_id;
    }

    //信用玩法购买
    static public function xbuy($lottery_id, $issue, $curRebate, $modes, $user_id, $projects)
    {
        if (!is_numeric($lottery_id) || !preg_match('`^[\d-]{6,14}$`', $issue) || !is_numeric($curRebate) || $curRebate < 0 || !is_numeric($modes) || !isset($GLOBALS['cfg']['modes'][$modes]) || !is_numeric($user_id) || !is_array($projects)) {
            log2("errcode=999", func_get_args());
            throw new exception2('参数不对', 999);
        }

        if($modes != 0.5){
            throw new exception2('信用玩法模式错误', 995);
        }

        //在有数据库操作前先定义$now，这是客户端提交的准确时间
        $now = date('Y-m-d H:i:s');

        //1.先检查彩种是否在售，是否在销售期，奖金组是否存在
        if (!$lottery = lottery::getItem($lottery_id)) {
            throw new exception2('彩种正在维护中，稍后开放', 1110);
        }
        if ($lottery['status'] != 8) {
            throw new exception2('彩种正在维护中，稍后开放', 1120);
        }

        $currentIssue = issues::getCurrentIssue($lottery_id);
        if (!$currentIssue || $currentIssue['issue'] != $issue) {
            throw new exception2('当前奖期不在销售期', 1130);
        }
        if ($currentIssue['code'] != '') {
            throw new exception2('异常：当前奖期已有开奖号码'.$currentIssue['code'].'--'.$issue, 1131);
        }

        if(time() >= strtotime($currentIssue['end_sale_time'])){
                throw new exception2('购买时间超过奖期销售时间', 11331);
        }

        //得到每种玩法的奖金 用于稍后判断是否超限
        if (!$prizes = prizes::getItems($lottery_id, 0, 0, 0, 1)) {
            throw new exception2("找不到基本组数据", 1160);
        }

        //2.再检查所提交号码$codes的正确性，玩法是否被禁用，核算单期注数、总金额是否正确，并判断用户奖金是否足够。。。
        //2.1 五星的手工录入限制，防止过多的数据，意义不大，也影响用户投注体验
        if (count($projects) > self::MAX_LIMIT_BETS) {
            throw new exception2("最大注数不得超过" . self::MAX_LIMIT_BETS . "注", 2101);
        }

        $totalAmount = $singleTotalAmount = $totalSingleNums = 0;
        $codeGroups = $singleNumGroups = $amountGroups = $tmp = array();
        $limits = config::getConfigs(array('prize_limit', 'multiple_limit','3d_prize_limit'));


        //优化情节：把数据库查询放在可能的大数组循环是非常失败的！
        $methods = methods::getItemsById(array_unique(array_keys(array_spec_key($projects, 'method_id'))));
        $i = 0;
        foreach ($projects as $v) {
            //fixed 取消single_num和single_amount验证
            if (empty($v['method_id']) || !is_numeric($v['method_id']) || $v['method_id'] <= 0) {
                throw new exception2('号码选择错误', 2210);
            }
            if (!isset($methods[$v['method_id']])) {
                throw new exception2('玩法不存在', 2220);
            }

            if ($methods[$v['method_id']]['lottery_id'] != $lottery_id) {//防止出现非法改单
                throw new exception2('非法投注', 22202);
            }
            //注单5+1 注意这里必须是一个号码的注单不能是5+4_6+10,一个号码就是一个project
            $xCode = preg_replace('@^([\x{4e00}-\x{9fa5},\d]+)(\+\d+)(.*)$@u', '${1}${3}', $v['code']);

            if (!$singleNums = methods::isLegalCode($methods[$v['method_id']], $xCode)) {
                throw new exception2("所选号码不符规定({$xCode})", 2230);
            }

            $v['single_num'] = $singleNums;

            $xTmpAmount = floatval(preg_replace('@(.*)(\+(\d+))([,_|].*)*$@U','${3}', $v['code']));
            $xAmount = intval($xTmpAmount);
            if ($xTmpAmount != $xAmount) {
                throw new exception2("输入金额只能为正整数({$xTmpAmount})", 22303);
            }
            $v['single_amount'] = $xAmount;

            $singleTotalAmount += $v['single_amount'];

            $totalSingleNums += $v['single_num'];

            //按玩法分组入库 避免手工录入产生太多记录
            $codeGroups[$v['method_id']][$i]['code'] = $xCode;
            $codeGroups[$v['method_id']][$i]['multiple'] = $xAmount;
            // if (!isset($singleNumGroups[$v['method_id']])) {
            //     $singleNumGroups[$v['method_id']] = $v['single_num'];
            // } else {
            //     $singleNumGroups[$v['method_id']] += $v['single_num'];
            // }
            // if (!isset($amountGroups[$v['method_id']])) {
            //     $amountGroups[$v['method_id']] = $v['single_amount'];
            // } else {
            //     $amountGroups[$v['method_id']] += $v['single_amount'];
            // }
            $i++;
        }

        $totalAmount = round($singleTotalAmount, 3);  //本次订单总金额

        if($totalAmount >= self::PACKAGE_MAX_AMOUNT){
            throw new exception2('每笔订单最大不能超过'.self::PACKAGE_MAX_AMOUNT.'元!', 22556);
        }
        if ($singleTotalAmount <= 0) {
            throw new exception2('投注额异常', 2255);
        }

        //3.开始事务
        $GLOBALS['db']->startTransaction();

        //bugfix:为保证对奖金操作的原子性，事务内读用户表时用FOR UPDATE可有效防止并发对该行的读！
        if (!$user = users::getItem($user_id, 8, true)) {
            throw new exception2('用户不存在或已被禁用', 1100);
        }

        //检查用户余额是否足够本次投注
        if ($user['balance'] < $totalAmount) {
            throw new exception2('用户余额不足', 2260);
        }

        //获取用户返点
        if (!$user['parent_tree']) {
            //throw new exception2('总代不可以玩游戏', 1135);   //总代可以玩游戏
            $userIds = array();
        } else {
            $userIds = explode(',', $user['parent_tree']);
        }
        $userIds[] = $user_id;  //还有自身
        $rebates = userRebates::getUsersRebates($userIds, $lottery['property_id']);

        /********************* snow 对返点进行重新排序**************************************/
        $rebates = self::_sortRebate($userIds, $rebates);
        /********************* snow 对返点进行重新排序**************************************/
        if (empty($rebates[$user_id])) {
            throw new exception2('用户奖金组不存在或被禁用', 1140);
        }
        if ($curRebate > $rebates[$user_id]) {
            throw new exception2("用户返点错误", 1150);
        }

        //判断奖金限红
        if ($limits['prize_limit']) {
            //$wxzxIds = [46,77,140,221,316,494];//五星直选ID
            foreach ($codeGroups as $method_id => $v) {
                foreach ($v as  $vv) {
                    $betCount = 1;
                    // if(in_array($method_id,$wxzxIds)){//当五星直选时为了防止分注单投注时突破彩陪限额
                    //     $betCount = count($v);
                    // }

                    $tmpPrize = ($vv['multiple'] * $modes * $prizes[$method_id][1]['prize'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate) / (1 - $lottery['total_profit'])) * $betCount;
                    $limitsPrize = $limits['prize_limit'];//正常彩陪限额
                    if($lottery['property_id'] == 5){//低频彩陪限额
                        $limitsPrize = $limits['3d_prize_limit'];
                    }

                    if ($tmpPrize > $limitsPrize) {
                        throw new exception2("投注超过最大赔彩限额{$limitsPrize}", 2252);
                    }
                }
            }
        }

        //036114 如果是时时彩，加入对过高奖金组的检查：所选奖金不允许超过1950
        // if ($lottery['property_id'] == 1) {
            //直选转直注数    zx_max_comb
            //总水率       total_profit
            //公司最少留水    min_profit
            //最小返点差 min_rebate_gaps[0][from]    min_rebate_gaps[0][to]      min_rebate_gaps[0][gap]  返点差
        //     $prizeMode = intval(2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate));
        //     if ($prizeMode > 1950) {
        //         throw new exception2("您可选择的最大返点为1950模式", 1151);
        //     }
        // }

        //3.1 先生成订单
        $packageData = array(
            'user_id' => $user_id,
            'top_id' => $user['top_id'],
            'lottery_id' => $lottery_id,
            'xgame' => 1,
            'issue' => $issue,
            'single_num' => $totalSingleNums,
            'multiple' => 1,
            'cur_rebate' => $curRebate,
            'modes' => $modes,
            'amount' => $totalAmount, //本订单总金额
            'create_time' => $now,
            'cancel_status' => 0,
            'cancel_time' => '0000-00-00 00:00:00',
            'user_ip' => isset($GLOBALS['REQUEST']['client_ip']) ? $GLOBALS['REQUEST']['client_ip'] : '0.0.0.0',
            'proxy_ip' => isset($GLOBALS['REQUEST']['proxy_ip']) ? $GLOBALS['REQUEST']['proxy_ip'] : '0.0.0.0',
            'server_ip' => $_SERVER['HTTP_HOST'], //暂时先记录从哪台机器来的，因为极少数订单延迟好几分钟才到达DB机器
        );
        if (!projects::addPackage($packageData)) {
            log2("errno=3100", $packageData);
            throw new exception2('数据错误', 3100);
        }
        $package_id = $GLOBALS['db']->insert_id();

        //3.2 写方案表 按玩法组分
        $projectDatas = array();
        foreach ($codeGroups as $method_id => $v) {
            foreach ($v as $k => $vv) {
                $projectDatas[] = array(
                    'package_id' => $package_id,
                    'user_id' => $user_id,
                    'top_id' => $user['top_id'],
                    'lottery_id' => $lottery_id,
                    'method_id' => $method_id,
                    'xgame' => 1,
                    'issue' => $issue,
                    'code' => $vv['code'],
                    'single_num' => 1,
                    'multiple' => $vv['multiple'],
                    'amount' => $vv['multiple'],
                    'cur_rebate' => $curRebate,
                    'modes' => $modes,
                    'create_time' => $now,
                    'hash_value' => md5($user_id . $vv['code'] . $now),
                );
            }
        }
        //在这里才是真正的插入数据,这里可以做优化循环插入非常失败
        foreach ($projectDatas as $projectData) {
            if (!projects::addItem($projectData)) {
                log2("errno=3110", $projectData);
                throw new exception2('数据错误', 3110);
            }
        }

        //3.3 本次订单的资金变化$totalAmount 投注扣款 及帐变
        if (!users::updateBalance($user_id, -$totalAmount)) {
            log2("errno=3120", $user, -$totalAmount);
            throw new exception2('数据错误', 3120);
        }
        $orderData = array(
            'lottery_id' => $lottery_id,
            'issue' => $issue,
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 401, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$totalAmount,
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] - $totalAmount,
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $package_id,
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            log2("errno=3130", $orderData);
            throw new exception2('数据错误', 3130);
        }
        $user['balance'] += -$totalAmount; //及时更新最新余额
        //3.4 上级代理返点数据也插入返点表，但不马上更新上级的余额，以避免顶层代理余额更新过于频繁导致锁表，将另有批处理执行
        //先算出返点差及每个上级应返金额
        $diffRebates = array();
        $userGifts = false;
        $maxRebateLimit = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION);

        //注：测试帐号不允许向上级返点！
        if ($user['is_test'] == 0) {
            foreach ($rebates as $k => $v) {
                if ($v == reset($rebates)) {
                    $last_id = $k;
                }
                else {
                    $diffRebates[$last_id] = round($rebates[$last_id] - $v, REBATE_PRECISION);
                    //六合彩赔率不统一 不判断maxRebateLimit
                    if ($lottery['lottery_id'] != 21 && $lottery['lottery_id'] != 25  && ($diffRebates[$last_id] < 0 || $diffRebates[$last_id] > $maxRebateLimit)) {   //上级必定有返点，且不能超过总返点,现在可以和上级返点相等
                        log2("返点出错：3400...last_id:{$last_id},对应的返点差{$diffRebates[$last_id]}，最大返点:{$maxRebateLimit},{$user['username']}(user_id={$user['user_id']}) 的上级user_id={$last_id}的返点为{$rebates[$last_id]}，给他的返点为{$v}");
                        throw new exception2('返点出错', 3400);
                    }
                    $last_id = $k;
                }
            }
        }

        !!config::getConfig('rebate_toself', 1) && $diffRebates[$user_id] = $curRebate;    //注意：自身返点为投注时选择的具体返点

        //注意不能直接比较浮点数! 0.1 != 0.1
        if ($lottery['lottery_id'] != 21 && $lottery['lottery_id'] != 25 && round(array_sum($diffRebates), REBATE_PRECISION) > $maxRebateLimit) {    //返点总额当然不能超过总返点
            log2("返点出错:3410...{$user['username']}(user_id={$user['user_id']})", $diffRebates, array_sum($diffRebates), $maxRebateLimit);
            throw new exception2('返点出错', 3410);
        }

        //写user_diff_rebates表 返点为0的可以考虑不写入
        $udrData = array();
        foreach ($diffRebates as $k => $v) {
            if (round($totalAmount * $v, 4) > 0.0001) {
                $udrData[] = array(
                    'user_id' => $k,
                    'top_id' => $user['top_id'],
                    'lottery_id' => $lottery_id,
                    'issue' => $issue,
                    'package_id' => $package_id,
                    'package_user_id' => $user_id,
                    'modes' => $modes,
                    'diff_rebate' => $v,
                    'rebate_amount' => round($totalAmount * $v, 4),
                    'status' => 0, //$k == $user_id ? 1 : 0,
                    'create_time' => date('Y-m-d H:i:s'),
                );
            }
        }

        if ($udrData && !userDiffRebates::addItems($udrData)) {
            log2("返点出错:3420...{$user['username']}(user_id={$user['user_id']})", $udrData);
            throw new exception2('返点出错', 3420);
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return $package_id;
    }

    /**
     *  用户前台投注 一次购买称为一个订单（package），一个订单可以包括不同玩法购买方案（project）
     *
     * @param type $lottery_id
     * @param type $methodId
     * @param type $curRebate
     * @param type $modes
     * @param type $user_id
     * @param array $projects 结构如下：
     *  array(0 => array(
     *      'method_id' => 1,   //玩法id 三星直选
     *      'code'      => '1,2,345',   //所购号码
     *      'single_num' => 3, //单倍注数，不作依赖，需要核实
     *      'single_amount' => 6,   //单倍金额，不作依赖，需要核实
     *      ),
     * )
     * @param array $traceData 结构如下：
     *  array(
     *     'issue' => XXX,
     *     'multiple' => XXX,
     * )
     * 改进：去掉$multiple，增加超始期号$startTraceIssue，$traceData要列出每期期号和相应倍数
     */
    static public function trace($lottery_id, $curRebate, $modes, $user_id, $projects, $traceData, $stopOnWin, $curIssue)
    {

        if (!is_numeric($lottery_id) || !is_numeric($curRebate) || $curRebate < 0 || !is_numeric($modes) || !isset($GLOBALS['cfg']['modes'][$modes]) || !is_numeric($user_id) || !is_array($projects) || empty($projects) || !is_array($traceData) || empty($traceData) || $stopOnWin < 0 || !strlen($curIssue)) {
            log2("errcode=999", func_get_args());
            throw new exception2('参数不对', 999);
        }
        //1.先检查彩种是否在售，是否在销售期，奖金组是否存在
        if (!$lottery = lottery::getItem($lottery_id)) {
            throw new exception2('彩种不存在或已停售', 1111);
        }
        if ($lottery['status'] != 8) {
            throw new exception2('彩种不存在或已停售', 1121);
        }

        //判断追的是否是超前订单，如果是入库为0 ，从当前期追的话是1
        $tarceStatus = $traceData[0]['issue'] == $curIssue ? 1 : 0 ;


        //得到每种玩法的奖金 用于稍后判断是否超限
        $prizes = prizes::getItems($lottery_id, 0, 0, 0, 1);


        //2.1再检查所提交号码$codes的正确性，玩法是否被禁用，核算单期注数、单期金额是否正确
        $totalAmount = $singleTotalAmount = $totalSingleNums = 0;
        $mids = array();
        $methods = $methodIds = $codeGroups = $singleNumGroups = $amountGroups = array();
        $limits = config::getConfigs(array('prize_limit', 'multiple_limit', '3d_prize_limit'));

        foreach ($projects as $v) {
            $methodIds[] = $v['method_id'];
        }
        $methods = methods::getItemsById($methodIds);
        foreach ($projects as $v) {

            if (empty($v['method_id']) || !is_numeric($v['method_id']) || $v['method_id'] <= 0) {
                throw new exception2('号码选择错误', 22112);
            }
            $method = $methods[$v['method_id']];
            $mids[$v['method_id']] = $v['method_id'];
            if (!$singleNums = methods::isLegalCode($method, $v['code'])) {
                throw new exception2("所选号码不符规定({$v['code']})", 2230);
            }
            $v['single_num'] = $singleNums;
            //接下来，单倍注数$singleNums * 模式 = 单倍价格，用来核算提交的金额是否正确，不正确拒绝继续
            $v['single_amount'] = round($singleNums * 2 * $modes, 3);
            $totalSingleNums += $v['single_num'];
            $singleTotalAmount += $v['single_amount'];

            //按玩法分组入库 避免手工录入产生太多记录
            $codeGroups[$v['method_id']][] = $v['code'];
            if (!isset($singleNumGroups[$v['method_id']])) {
                $singleNumGroups[$v['method_id']] = $v['single_num'];
            }
            else {
                $singleNumGroups[$v['method_id']] += $v['single_num'];
            }
            if (!isset($amountGroups[$v['method_id']])) {
                $amountGroups[$v['method_id']] = $v['single_amount'];
            }
            else {
                $amountGroups[$v['method_id']] += $v['single_amount'];
            }
        }
        if ($singleTotalAmount <= 0) {
            throw new exception2('投注额异常', 2261);
        }

        //2.2 检查追号奖期及倍数是否合法，并统计总金额
        //得到今天从当前期算起的剩余奖期
        $todayRemainIssues = array_spec_key(issues::getItems($lottery_id, date('Y-m-d'), 0, 0, time()), 'issue');
        $traceData = array_spec_key($traceData, 'issue');
        ksort($traceData);
        $totalMultiple = 0;
        foreach ($traceData as $v) {
            if (!isset($todayRemainIssues[$v['issue']])) {
                $tmpIssue = issues::getItem(0, $v['issue'], $lottery_id);
               // if ($tmpIssue['status_code'] != 0 || $tmpIssue['code'] != '') {//如果用户在时间临界点追号则查看此期是否已开奖录号
               //     throw new exception2('您追号奖期不在当天未售奖期范围内，请重新购买', 2271);
               // }
                throw new exception2('您追号奖期不在当天未售奖期范围内，请重新购买', 2271);
            }
            if (!is_numeric($v['multiple']) || $v['multiple'] <= 0) {
                throw new exception2('追号倍数不正确', 2272);
            }

            //倍数不能无限翻倍
            if ($limits['multiple_limit'] && $v['multiple'] > $limits['multiple_limit']) {
                throw new exception2("追号投注倍数超过最大允许数{$limits['multiple_limit']}", 2205);
            }

            $totalMultiple += $v['multiple'];
            $totalAmount += $singleTotalAmount * $v['multiple'];
        }
        //倍数不能无限翻倍
        if ($totalMultiple > 64535) {
            throw new exception2("追号投注总倍数超过最大允许数64535", 2206);
        }
        $totalAmount = round($totalAmount, 3);  //本次追号总金额
        log2("本次追号总共" . count($traceData) . "期，共计{$totalMultiple}倍，单倍金额：{$singleTotalAmount},总金额：{$totalAmount}");

        //3.开始事务
        $GLOBALS['db']->startTransaction();

        //bugfix:为保证对奖金操作的原子性，事务内读用户表时用FOR UPDATE可有效防止并发对该行的读！
        if (!$user = users::getItem($user_id, 8, true)) {
            throw new exception2('用户不存在或已被禁用', 1101);
        }

        //检查用户余额是否足够本次投注
        if ($user['balance'] < $totalAmount) {
            throw new exception2('用户余额不足', 2261);
        }

        //获取用户返点
        if (!$user['parent_tree']) {
            //throw new exception2('总代不可以玩游戏', 1135);   //总代可以玩游戏
            $userIds = array();
        }
        else {
            $userIds = explode(',', $user['parent_tree']);
        }
        $userIds[] = $user_id;  //还有自身
        $rebates = userRebates::getUsersRebates($userIds, $lottery['property_id']);
        /******************* snow 对返点重新排序************************************/
        $rebates = self::_sortRebate($userIds, $rebates);
        /******************* snow 对返点重新排序************************************/
        if (empty($rebates[$user_id])) {
            throw new exception2('用户奖金组不存在或被禁用', 1141);
        }
        if ($curRebate > $rebates[$user_id]) {
            throw new exception2("用户返点错误", 1150);
        }

        //判断奖金限红
        if ($limits['prize_limit']) {
            //$wxzxIds = [46,77,140,221,316,494];//五星直选ID
            foreach ($traceData as $v) {
                $tmp = array();
                foreach ($codeGroups as $method_id => $vv) {
                    $betCount = 1;
                    // if(in_array($method_id,$wxzxIds)){//当五星直选时为了防止分注单投注时突破彩陪限额
                    //     $betCount = count($vv);
                    // }
                    $tmpPrize = ($v['multiple'] * $modes * $prizes[$method_id][1]['prize'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate) / (1 - $lottery['total_profit'])) * $betCount;
                    $limitsPrize = $limits['prize_limit'];//正常彩陪限额
                    if($lottery['property_id'] == 5){//低频彩陪限额
                        $limitsPrize = $limits['3d_prize_limit'];
                    }
                    if ($tmpPrize > $limitsPrize) {
                        throw new exception2("投注超过最大赔彩限额{$limitsPrize}", 2252);
                    }
                }
            }
        }

        //036114 如果是时时彩，加入对过高奖金组的检查：所选奖金不允许超过1950
        // if ($lottery['property_id'] == 1) {
        //     $prizeMode = intval(2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate));
        //     if ($prizeMode > 1950) {
        //         throw new exception2("您可选择的最大返点为1950模式", 1151);
        //     }
        // }

        //3.0.1 添加追号记录
       // 比较简单，把追号要求写入traces表，详细期数和倍数写入trace_details表
       // 以后每期开售时把当期追号转为注单（新增一CRON程序，判断如果要求追中即停，应先判断前一期确定没中才生成当期的注单）
       // 后面的流程都一样（判断中奖加点东西，如果是追号生成的注单，且中了奖，且用户设置了追中即停，则停止这个追号任务）
        $firstTraceIssue = reset($traceData);
        $data = array(
            'user_id' => $user_id,
            'top_id' => $user['top_id'],
            'lottery_id' => $lottery_id,
            'start_issue' => $firstTraceIssue['issue'],
            'single_num' => $totalSingleNums,
            'total_multiple' => $totalMultiple,
            'modes' => $modes,
            'total_amount' => $totalAmount,
            'title' => "{$lottery['name']}从{$firstTraceIssue['issue']}开始追" . count($traceData) . "期",
            'trace_times' => count($traceData),
            'win_times' => '0',
            'finish_times' => '0',
            'cancel_times' => '0',
            'stop_on_win' => intval($stopOnWin),
            'create_time' => date('Y-m-d H:i:s'),
            'status' => $tarceStatus, //追号状态(0:未开始 1:正在进行;2:已完成;3:已取消)
            'user_ip' => isset($GLOBALS['REQUEST']['client_ip']) ? $GLOBALS['REQUEST']['client_ip'] : '0.0.0.0',
            'proxy_ip' => isset($GLOBALS['REQUEST']['proxy_ip']) ? $GLOBALS['REQUEST']['proxy_ip'] : '0.0.0.0',
        );
        if (!traces::addItem($data)) {
            log2("errno=3010", $data);
            throw new exception2('数据错误', 3011);
        }
        $trace_id = $GLOBALS['db']->insert_id();

        //改进：简化流程。不再一次性扣款再当期追号返款，一次性循环生成未来订单即可，不再详细记录trace_details表
        $projectDatas = $udrDatas = array();
        foreach ($traceData as $trace) {
            $packageTotalAmount = $singleTotalAmount * $trace['multiple'];
            //3.1 为每一期生成一条订单
            $packageData = array(
                'user_id' => $user_id,
                'top_id' => $user['top_id'],
                'lottery_id' => $lottery_id,
                'issue' => $trace['issue'],
                'trace_id' => $trace_id,
                'single_num' => $totalSingleNums,
                'multiple' => $trace['multiple'],
                'cur_rebate' => $curRebate,
                'modes' => $modes,
                'amount' => $packageTotalAmount, //本订单总金额
                'create_time' => date('Y-m-d H:i:s'),
                'cancel_status' => 0,
                'cancel_time' => '0000-00-00 00:00:00',
                'user_ip' => isset($GLOBALS['REQUEST']['client_ip']) ? $GLOBALS['REQUEST']['client_ip'] : '0.0.0.0',
                'proxy_ip' => isset($GLOBALS['REQUEST']['proxy_ip']) ? $GLOBALS['REQUEST']['proxy_ip'] : '0.0.0.0',
                'server_ip' => $_SERVER['HTTP_HOST'], //暂时先记录从哪台机器来的，因为极少数订单延迟好几分钟才到达DB机器
            );
            if (!projects::addPackage($packageData)) {
                log2("errno=3100", $packageData);
                throw new exception2('数据错误', 3100);
            }
            $package_id = $GLOBALS['db']->insert_id();

            //3.2 写方案表 按玩法组分
            $now = date('Y-m-d H:i:s');
            foreach ($codeGroups as $methodId => $v) {
                $projectDatas[] = array(
                    'package_id' => $package_id,
                    'user_id' => $user_id,
                    'top_id' => $user['top_id'],
                    'lottery_id' => $lottery_id,
                    'method_id' => $methodId,
                    'issue' => $trace['issue'],
                    'code' => implode("|", $codeGroups[$methodId]),
                    'single_num' => $singleNumGroups[$methodId],
                    'multiple' => $trace['multiple'], //各方案的倍数是一致的
                    'amount' => $amountGroups[$methodId] * $trace['multiple'],
                    'cur_rebate' => $curRebate,
                    'modes' => $modes,
                    'create_time' => $now,
                    'hash_value' => md5($user_id . implode("|", $codeGroups[$methodId]) . $now),
                );
            }

            //3.3 本次订单的资金变化$packageTotalAmount 投注扣款 及帐变
            if (!users::updateBalance($user_id, -$packageTotalAmount)) {
                log2("errno=3120", $user, -$packageTotalAmount);
                throw new exception2('数据错误', 3120);
            }
            $orderData = array(
                'lottery_id' => $lottery_id,
                'issue' => $trace['issue'],
                'from_user_id' => $user_id,
                'from_username' => $user['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => 401,
                'amount' => -$packageTotalAmount,
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] - $packageTotalAmount,
                'create_time' => date('Y-m-d H:i:s'),
                'business_id' => $package_id,
                'admin_id' => 0,
            );
            if (!orders::addItem($orderData)) {
                log2("errno=3130", $orderData);
                throw new exception2('数据错误', 3130);
            }
            $user['balance'] += -$packageTotalAmount; //及时更新最新余额
            //3.4 上级代理返点数据也插入返点表，但不马上更新上级的余额，以避免顶层代理余额更新过于频繁导致锁表，将另有批处理执行
            //3.4.1 先算出返点差及每个上级应返金额
            $diffRebates = array();
            $maxRebateLimit = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION);

            //注：测试帐号不允许向上级返点！
            if ($user['is_test'] == 0) {
                foreach ($rebates as $k => $v) {
                    if ($v == reset($rebates)) {
                        $last_id = $k;
                    }
                    else {
                        $diffRebates[$last_id] = round($rebates[$last_id] - $v, REBATE_PRECISION);
                        if ($diffRebates[$last_id] < 0 || $diffRebates[$last_id] > $maxRebateLimit) {   //上级必定有返点，且不能超过总返点
                            throw new exception2('返点出错', 3400);
                        }
                        $last_id = $k;
                    }
                }
            }

            !!config::getConfig('rebate_toself', 1) && $diffRebates[$user_id] = $curRebate;    //注意：自身返点为投注时选择的具体返点

            if (round(array_sum($diffRebates), REBATE_PRECISION) > $maxRebateLimit) {    //返点总额当然不能超过总返点
                throw new exception2('返点出错', 3410);
            }

            //3.4.2 写user_diff_rebates表 返点为0的可以考虑不写入
            //todo:由于返点数据量较大，如果追120期，120*5级代理=600数据要写。目前是采取批量插入，可以不一次性写入几百条数据，考虑到该期开售时再写入
            foreach ($diffRebates as $k => $v) {
                if (round($packageTotalAmount * $v, 4) > 0.0001) {
                    $udrDatas[] = array(
                        'user_id' => $k,
                        'top_id' => $user['top_id'],
                        'lottery_id' => $lottery_id,
                        'issue' => $trace['issue'],
                        'package_id' => $package_id,
                        'package_user_id' => $user_id, //$k == $user_id ? 1 : 0,
                        'modes' => $modes,
                        'diff_rebate' => $v,
                        'rebate_amount' => round($packageTotalAmount * $v, 4),
                        'status' => 0, //$k == $user_id ? 1 : 0,
                        'create_time' => date('Y-m-d H:i:s'),
                    );
                }
            }

            //3.5 为简化，对于追号，包括自己的返点都等开奖后再返 这里不做什么
        }

        //批量插入方案表
        if (!projects::addItems($projectDatas)) {
            log2("errno=3110", $projectDatas);
            throw new exception2('数据错误', 3110);
        }

        //批量插入返点表
        if ($udrDatas && !userDiffRebates::addItems($udrDatas)) {
            throw new exception2('返点出错', 3420);
        }

        //全局投注限额--检查
        foreach ($traceData as $issue => $trace) {
            foreach ($codeGroups as $methodId => $v) {
                if ($methods[$methodId]['is_lock']) {
                    $tmpCodes = array();
                    foreach ($v as $vv) {
                        $tmpCodes = array_merge($tmpCodes, game::getExpandCodes($methods[$methodId]['name'], $vv));
                    }
                    //$tmpCodes = array_unique($tmpCodes);
                    $computeMethodPrize = methods::computeMethodPrize($lottery_id, $methodId);
                    $result = locks::checkMultipleLimit($lottery, $methods[$methodId], $issue, $tmpCodes, intval($modes * $trace['multiple'] * $computeMethodPrize));
                    if ($result) {
                        throw new exception2('尊敬的客户：<br/>以下投注号码在本期已经达到购买上限，祝您下一期中购得您心仪的号码，祝您好运多多！<br/>' . implode("，", $result), 22113);
                    }
                    //优化追号封锁
                    $result = locks::updateMultipleLimit($lottery, $methods[$methodId], array($issue), $tmpCodes, intval($modes * $trace['multiple'] * $computeMethodPrize));
                    if (!$result) {
                        throw new exception2('数据错误', 3160);
                    }
                }
            }
        }

        // foreach ($traceData as $issue => $trace) {
        //     foreach ($codeGroups as $methodId => $v) {
        //         if ($methods[$methodId]['is_lock']) {
        //             $tmpCodes = array();
        //             foreach ($v as $vv) {
        //                 $tmpCodes = array_merge($tmpCodes, game::getExpandCodes($methods[$methodId]['name'], $vv));
        //             }
        //             //$tmpCodes = array_unique($tmpCodes);
        //             $computeMethodPrize = methods::computeMethodPrize($lottery_id, $methodId)));
        //             $result = locks::updateMultipleLimit($lottery, $methods[$methodId], array($issue), $tmpCodes, intval($modes * $trace['multiple'] * $computeMethodPrize;
        //             if (!$result) {
        //                 throw new exception2('数据错误', 3160);
        //             }
        //         }
        //     }
        // }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return $trace_id;
    }

    /**
     * 撤消订单package 和投注相反的流程 分管理员撤单和用户撤单，具体流程是一样的
     * @param <array> $package
     * @param <int> $cacel_type 1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
     * @param <int> $admin_id 管理员撤单填管理员id，个人撤单填0，其他系统强制撤单行为填self::SYSTEM_ADMIN_ID
     * @return <boolean>
     */
    static public function cancelPackage($package, $cacel_type, $admin_id = 0)
    {
        if (empty($package)) {
            throw new exception2('参数不合法', 1100);
        }
        //130903 被禁用的用户还有未完成的追号，应允许其撤单
        if (!$user = users::getItem($package['user_id'], -1)) {
            throw new exception2('用户不存在', 1101);
        }
        if (!$lottery = lottery::getItem($package['lottery_id'])) {
            throw new exception2('彩种不存在或已停售', 1200);
        }

        if (!$issueInfo = issues::getItem(0, $package['issue'], $package['lottery_id'])) {
            throw new exception2('找不到奖期', 1210);
        }
        if (!$projects = projects::getItems(0, '', $package['package_id'])) {
            throw new exception2('找不到方案', 1220);
        }

        //todo:该奖期正在进行中奖派奖等cron时暂不处理，要等cron完成

        $nowTime = time();
        //最大允许撤单时间
        if ($admin_id > 0) {    //系统管理员撤单或系统强制性撤单
            $admin_cancel_limit = config::getConfig('admin_cancel_limit');
            /*if ($nowTime > (strtotime($issueInfo['end_sale_time']) + $admin_cancel_limit * 60)) {
                throw new exception2("管理撤单时间已超过{$admin_cancel_limit}分钟，不能撤单(id={$package['package_id']})", 1300);
            }*/
        }
        else {  //用户撤单
            if ($nowTime > strtotime($issueInfo['cannel_deadline_time'])) {
                throw new exception2("撤单时间已过，不能撤单", 1301);
            }
        }

        log2("彩种{$lottery['name']}第{$issueInfo['issue']}期 package_id={$package['package_id']} user_id={$user['user_id']} username={$user['username']} 进行撤单 cacel_type={$cacel_type}(1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单) admin_id={$admin_id}，所购号码：" . var_export($projects, true));

        //开始撤单流程
        //开始事务
        $GLOBALS['db']->startTransaction();

        //bugfix:为保证对订单操作的原子性，事务内读用户表时用FOR UPDATE可有效防止并发对该行的读！
        if (!$package = projects::getPackage($package['package_id'], 0, true)) {
            throw new exception2('订单不存在或正在使用', 3010);
        }

        //先判断当前是否可以撤单
        if ($package['cancel_status'] > 0) {    //已经撤过单了
            return true;
        }

        //1.撤消派奖
        game::cancelPrize($lottery, $issueInfo, $package);

        //2.撤单取消返点
        game::cancelRebate($lottery, $issueInfo, $package, $admin_id);

        //获得最新余额
        if (!$user = users::getItem($package['user_id'], -1)) {
            throw new exception2("找不到用户user_id={$package['user_id']}");
        }

        //3.撤单返款 及帐变
        if (!users::updateBalance($package['user_id'], $package['amount'])) {
            throw new exception2('数据错误1', 3120);
        }

        $orderData = array(
            'lottery_id' => $package['lottery_id'],
            'issue' => $package['issue'],
            'from_user_id' => $package['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 303, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => $package['amount'],
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] + $package['amount'],
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $package['package_id'],
            'admin_id' => $admin_id,
        );
        if (!orders::addItem($orderData)) {
            log2("errno=3130", $orderData);
            throw new exception2('数据错误2', 3130);
        }
        $user['balance'] += $package['amount']; //及时更新最新余额
        //超过底限要收取撤单手续费 防止有人故意炒水
        $cancel_fee_limit = config::getConfig('cancel_fee_limit');
        if ($admin_id <= 0 && $package['amount'] >= $cancel_fee_limit) {
            log2("所购金额：{$package['amount']} 超过系统最高免手续费金额：{$cancel_fee_limit}");
            $fee = $package['amount'] * config::getConfig('cancel_fee_rate');
            if (!users::updateBalance($package['user_id'], -$fee)) {
                throw new exception2('数据错误3', 3120);
            }
            $orderData = array(
                'lottery_id' => $package['lottery_id'],
                'issue' => $package['issue'],
                'from_user_id' => $package['user_id'],
                'from_username' => $user['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => 412, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                'amount' => -$fee,
                'pre_balance' => $user['balance'],
                'balance' => $user['balance'] - $fee,
                'create_time' => date('Y-m-d H:i:s'),
                'business_id' => $package['package_id'],
                'admin_id' => $admin_id,
            );
            if (!orders::addItem($orderData)) {
                log2("errno=3130", $orderData);
                throw new exception2('数据错误4', 3130);
            }
            $user['balance'] -= $fee; //及时更新最新余额
            log2("因package_id={$package['package_id']}订单金额{$package['amount']}超过系统设定值{$cancel_fee_limit}，所以征收了{$fee}元手续费");
        }

        //3.更新订单下的所有方案cancel_status状态为“公司撤单” 0未撤单 1个人撤单 2追中撤单 3出号撤单 4未开撤单 9公司撤单
        //projects表不必再有cancel_status属性
       // foreach ($projects as $v) {
       //     if (!projects::updateItem($v['project_id'], array('cancel_status' => $admin_id > 0 ? 9 : 1))) {
       //         throw new exception2('数据错误', 3150);
       //     }
       // }
       //  4.更新订单状态为“公司撤单” 0未撤单 1个人撤单 2追中撤单 3出号撤单 4未开撤单 9公司撤单
        if (!projects::updatePackage($package['package_id'], array('cancel_status' => $cacel_type, 'cancel_admin_id' => $admin_id, 'cancel_time' => date('Y-m-d H:i:s')))) {
            throw new exception2('数据错误5', 3150);
        }

        //5.顺便检查下如果是追号单，更新下traces表的cancel_times+1
        if ($package['trace_id']) {   //如果是追号订单
            traces::updateCancelTimes($package['trace_id'], 1);
            if ($package['check_prize_status'] > 0) {
                traces::updateFinishTimes($package['trace_id'], -1);
            }
        }

        $tmpCode3DP3 = array();
        foreach ($projects as $project) {
            $method = methods::getItem($project['method_id']);
            if ($method['is_lock']) {
                //如果是低频方案则循环算出所有转直选的号码次数，P3中后二和定位胆两个玩法是不封锁的所有不会进入下面
                if($project['lottery_id'] == 9 || $project['lottery_id'] == 10){
                    $codeArr = explode('|',$project['code']);
                    $codeAllP = methods::get3dP3AllP($method['name'],$codeArr);
                    foreach($codeAllP as $c => $num){//计算所有选号的转直个数
                        if(array_key_exists($c, $tmpCode3DP3)){
                            $tmpCode3DP3[$c] += $num;
                        } else {
                            $tmpCode3DP3[$c] = $num;
                        }
                    }
                }else{
                    $codes_raw = explode('|', $project['code']);
                    $tmpCodes = array();
                    foreach ($codes_raw as $v) {
                        $tmpCodes = array_merge($tmpCodes, game::getExpandCodes($method['name'], $v));
                    }

                    $result = locks::updateMultipleLimit($lottery, $method, array($project['issue']), ($tmpCodes), - intval($project['modes'] * $project['multiple'] * methods::computeMethodPrize($project['lottery_id'], $project['method_id'])));
                    if (!$result) {
                        throw new exception2('数据错误6', 3162);
                    }
                }
            }
        }
        //如果撤单是低频 则更新低频封锁表，利用上面已算出的号码最大次数
        if($package['lottery_id'] == 9 || $package['lottery_id'] == 10){
            if(!empty($tmpCode3DP3)){
                $times = array_sum($tmpCode3DP3);
                $amount = - intval($package['amount']/$times * 1930 * $package['modes'] * $package['multiple']);
                $res = locks::update3DP3MultipleLimit($lottery['lottery_id'], $package['issue'], $tmpCode3DP3, $amount);
                if (!$res) {
                    throw new exception2('数据错误7', 316011);
                }
            }
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误8', 3163);
        }
        log2("彩种{$lottery['name']}第{$issueInfo['issue']}期 package_id={$package['package_id']} user_id={$user['user_id']} username={$user['username']} 撤单成功！");

        return true;
    }

    /**
     * 160110 批量返点 第2版
     * @param integer $recentNoRebateId
     * @param array $needRebateIssues  本次需要返点的所有奖期列表
     * @return integer
     */
    static public function batchRebate2($recentNoRebateId = 0, $amount = 0, $needRebateIssues = array())
    {
        if ($recentNoRebateId == 0) {
            $recentNoRebateId = userDiffRebates::getRecentNoRebateId();
            echo "recentNoRebateId={$recentNoRebateId}\n";
        }
        else {
            echo "强制指定了recentNoRebateId=$recentNoRebateId\n";
        }

        if ($recentNoRebateId == 0) {
            throw new exception2('返点表不可能没有记录', 3410);
        }

        $lotteryIssues = $lotteryPackageIds = array();

        //开始事务
        $GLOBALS['db']->startTransaction();

        for ($start = 0; $start < $amount; $start += 10000) {
            // 这才是返点总开始点
            $isAutomaticRebates = !!config::getConfig('rebate_toself', 1);
            if (!$isAutomaticRebates) {
                break;
            }

            if (!$recentNoRebates = userDiffRebates::getRecentNoRebates($recentNoRebateId, $start, 10000)) {
                break;
            }

            $users = users::getItemsById(array_keys(array_spec_key($recentNoRebates, 'user_id')));
            $userLastBalances = $userRebateAmounts = $udrIds = $orderDatas = $lotteryIssues = $lotteryPackageIds = array();

            // 下面这个foreach只是拼的帐变数据
            foreach ($recentNoRebates as $v) {
                $lotteryIssues[$v['lottery_id']][$v['issue']] = $v['issue'];
                $lotteryPackageIds[$v['lottery_id']][$v['package_id']] = $v['package_id'];
                if ($v['rebate_amount'] > 0) {    //有返点才写帐变
                    if ($users[$v['user_id']]['status'] != 8) {
                        //对于被冻结用户，忽略
                        echo "用户不存在或已被禁用(user_id={$v['user_id']})，忽略对其返点";
                        continue;
                    }

                    if (!isset($userRebateAmounts[$v['user_id']])) {
                        $userRebateAmounts[$v['user_id']] = $v['rebate_amount'];
                        $userLastBalances[$v['user_id']] = $users[$v['user_id']]['balance'];
                    }
                    else {
                        $userRebateAmounts[$v['user_id']] += $v['rebate_amount'];
                    }

                    $udrIds[] = $v['udr_id'];

                    $orderDatas[] = array(
                        'lottery_id' => $v['lottery_id'],
                        'issue' => $v['issue'],
                        'from_user_id' => $v['user_id'],
                        'from_username' => $users[$v['user_id']]['username'],
                        'to_user_id' => 0,
                        'to_username' => '',
                        'type' => $v['user_id'] == $v['package_user_id'] ? 301 : 302, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                        'amount' => $v['rebate_amount'],
                        'pre_balance' => $userLastBalances[$v['user_id']],
                        'balance' => $userLastBalances[$v['user_id']] + $v['rebate_amount'],
                        'create_time' => date('Y-m-d H:i:s'),
                        'business_id' => $v['package_id'],
                        'admin_id' => 0,
                    );
                    $userLastBalances[$v['user_id']] += $v['rebate_amount'];
                }
            }

            //置状态为1
            if ($udrIds && !userDiffRebates::updateItem($udrIds, array('status' => 1))) {
                throw new exception2('数据错误', 3510);
            }

            if ($orderDatas && !orders::addItems($orderDatas)) {
                log2("errno=3150", $orderDatas);
                throw new exception2('数据错误', 3510);
            }

            //余额只能挨个更新
            foreach ($userRebateAmounts as $userId => $rebateAmount) {
                if (!users::updateBalance($userId, $rebateAmount)) {
                    throw new exception2('数据错误', 3500);
                }
            }

            if (count($recentNoRebates) < 10000) {
                break;
            }
        }

        //由于没有issue_id可用，只能按彩种部分批量更新奖期，最好存issue_id为优
        //所有应该计算返点的奖期在前面正确的情况下一次性全部修改为完成返点状态
        foreach ($needRebateIssues as $lottery_id => $issues) {
            issues::updateItemByLottery($lottery_id, $issues, array('status_rebate' => 2));
            issuesMini::updateItemByLottery($lottery_id, $issues, array('status_rebate' => 2));
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return $lotteryPackageIds;
    }

        /**
     * 共享库后批量返点 第3版 By tim
     * @param integer $recentNoRebateId
     * @param array $needRebateIssues  本次需要返点的所有奖期列表
     * @return integer
     */
    static public function batchRebate3($recentNoRebateId = 0, $amount = 0, $needRebateIssues = array())
    {
        if ($recentNoRebateId == 0) {
            $recentNoRebateId = userDiffRebates::getRecentNoRebateId();
            echo "recentNoRebateId={$recentNoRebateId}\n";
        }
        else {
            echo "强制指定了recentNoRebateId=$recentNoRebateId\n";
        }

        if ($recentNoRebateId == 0) {
            throw new exception2('返点表不可能没有记录', 3410);
        }

        $lotteryIssues = $lotteryPackageIds = array();

        //开始事务
        $GLOBALS['db']->startTransaction();

        for ($start = 0; $start < $amount; $start += 10000) {
            // 这才是返点总开始点
            $isAutomaticRebates = !!config::getConfig('rebate_toself', 1);
            if (!$isAutomaticRebates) {
                break;
            }

            if (!$recentNoRebates = userDiffRebates::getRecentNoRebates2($recentNoRebateId, $start, 10000)) {
                break;
            }

            $users = users::getItemsById(array_keys(array_spec_key($recentNoRebates, 'user_id')));
            $userLastBalances = $userRebateAmounts = $udrIds = $orderDatas = $lotteryIssues = $lotteryPackageIds = array();

            // 下面这个foreach只是拼的帐变数据
            foreach ($recentNoRebates as $v) {
                $lotteryIssues[$v['lottery_id']][$v['issue']] = $v['issue'];
                $lotteryPackageIds[$v['lottery_id']][$v['package_id']] = $v['package_id'];
                if ($v['rebate_amount'] > 0) {    //有返点才写帐变
                    if ($users[$v['user_id']]['status'] != 8) {
                        //对于被冻结用户，忽略
                        echo "用户不存在或已被禁用(user_id={$v['user_id']})，忽略对其返点";
                        continue;
                    }

                    if (!isset($userRebateAmounts[$v['user_id']])) {
                        $userRebateAmounts[$v['user_id']] = $v['rebate_amount'];
                        $userLastBalances[$v['user_id']] = $users[$v['user_id']]['balance'];
                    }
                    else {
                        $userRebateAmounts[$v['user_id']] += $v['rebate_amount'];
                    }

                    $udrIds[] = $v['udr_id'];

                    $orderDatas[] = array(
                        'lottery_id' => $v['lottery_id'],
                        'issue' => $v['issue'],
                        'from_user_id' => $v['user_id'],
                        'from_username' => $users[$v['user_id']]['username'],
                        'to_user_id' => 0,
                        'to_username' => '',
                        'type' => $v['user_id'] == $v['package_user_id'] ? 301 : 302, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                        'amount' => $v['rebate_amount'],
                        'pre_balance' => $userLastBalances[$v['user_id']],
                        'balance' => $userLastBalances[$v['user_id']] + $v['rebate_amount'],
                        'create_time' => date('Y-m-d H:i:s'),
                        'business_id' => $v['package_id'],
                        'admin_id' => 0,
                    );
                    $userLastBalances[$v['user_id']] += $v['rebate_amount'];
                }
            }

            // 置状态为1
            if ($udrIds && !userDiffRebates::updateItem($udrIds, array('status' => 1))) {
                throw new exception2('数据错误', 3510);
            }

            if ($orderDatas && !orders::addItems($orderDatas)) {
                log2("errno=3150", $orderDatas);
                throw new exception2('数据错误', 3510);
            }

            // 余额只能挨个更新
            foreach ($userRebateAmounts as $userId => $rebateAmount) {
                if (!users::updateBalance($userId, $rebateAmount)) {
                    throw new exception2('数据错误', 3500);
                }
            }

            if (count($recentNoRebates) < 10000) {
                break;
            }
        }

        //由于没有issue_id可用，只能按彩种部分批量更新奖期，最好存issue_id为优
        //所有应该计算返点的奖期在前面正确的情况下一次性全部修改为完成返点状态
        foreach ($needRebateIssues as $lottery_id => $issues) {
            checkSend::updateItemByLottery($lottery_id, $issues, array('status_rebate' => 2));
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return $lotteryPackageIds;
    }

    //批量返点 对user_diff_rebates表的status为0的统一返点 根据issues表的status_rebate表示返点状态(0:未开始;1:进行中;2:已完成)
    static public function batchRebate($lottery, $issueInfo)
    {
        if (!is_array($lottery) || !is_array($issueInfo)) {
            throw new exception2('Invalid arg', 1101);
        }
        if (!$issueInfo['code'] || $issueInfo['status_code'] != 2) {
            throw new exception2('该期还未开奖', 1109);
        }

        //异常处理优先
        if ($pendingError = issueErrors::getPendingError($lottery['lottery_id'], $issueInfo['issue'])) {
            // 标记为未执行, CLI 退出 (让异常CLI优先运行)
            issues::updateItem($issueInfo['issue_id'], array('status_rebate' => 0));
            throw new exception2('该期有异常处理任务', 1106);
        }

        //奖期标记设置为: 进行'集中返点'中...
        issues::updateItem($issueInfo['issue_id'], array('status_rebate' => 1));

        $packages = projects::getPackages($lottery['lottery_id'], -1, -1, $issueInfo['issue'], -1, 0, '', 0, '', '', '', '', 0);    //仅对未撤单的返点
        game::rebate($lottery, $issueInfo, $packages);

        //奖期标记设置为: 已完成
        issues::updateItem($issueInfo['issue_id'], array('status_rebate' => 2));

        return count($packages);
    }

    //批量返点 对user_diff_rebates表的status为0的统一返点 根据issues表的status_rebate表示返点状态(0:未开始;1:进行中;2:已完成)
    static public function rebate($lottery, $issueInfo, $packages)
    {
        if (!is_array($packages) || count($packages) == 0) {
            return 0;
        }

        if (!$userDiffRebates = userDiffRebates::getItems(0, 0, array_keys($packages), 0)) {
            return 0;
        }
        $users = users::getItemsById(array_keys(array_spec_key($userDiffRebates, 'user_id')));
        $udrIds = $orderDatas = array();

        //开始事务
        $GLOBALS['db']->startTransaction();
        foreach ($userDiffRebates as $v) {
            if ($v['rebate_amount'] > 0) {    //有返点才写帐变
                if ($users[$v['user_id']]['status'] != 8) {
                    //对于被冻结用户，忽略
                    echo "用户不存在或已被禁用(user_id={$v['user_id']})，忽略对其返点";
                    continue;
                    //throw new exception2("用户不存在或已被禁用(user_id={$v['user_id']})", 1100);
                }

                if (!users::updateBalance($v['user_id'], $v['rebate_amount'])) {
                    throw new exception2('数据错误', 3500);
                }

                $udrIds[] = $v['udr_id'];
                $orderDatas[] = array(
                    'lottery_id' => $lottery['lottery_id'],
                    'issue' => $issueInfo['issue'],
                    'from_user_id' => $v['user_id'],
                    'from_username' => $users[$v['user_id']]['username'],
                    'to_user_id' => 0,
                    'to_username' => '',
                    'type' => $v['user_id'] == $v['package_user_id'] ? 301 : 302, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                    'amount' => $v['rebate_amount'],
                    'pre_balance' => $users[$v['user_id']]['balance'],
                    'balance' => $users[$v['user_id']]['balance'] + $v['rebate_amount'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'business_id' => $v['package_id'],
                    'admin_id' => 0,
                );
            }
        }

        //置状态为1
        if ($udrIds && !userDiffRebates::updateItem($udrIds, array('status' => 1))) {
            throw new exception2('数据错误', 3510);
        }

        if ($orderDatas && !orders::addItems($orderDatas)) {
            log2("errno=3150", $orderDatas);
            throw new exception2('数据错误', 3510);
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return count($userDiffRebates);
    }

    //批量撤消上级返点 无事务 对user_diff_rebates表的status为1的统一返点 返点状态(0:未返;1:已返;2:已撤), , $package, $admin_id
    static public function cancelRebate($lottery, $issueInfo, $package, $admin_id = 0)
    {
        $userDiffRebates = userDiffRebates::getItems(0, 0, $package['package_id']);  //这里不加$status
        static $users = array();
        foreach ($userDiffRebates as $v) {
            if ($v['rebate_amount'] > 0) {    //有返点的才可能操作
                if ($v['status'] == 2) {    //已经撤消了的忽略
                    continue;
                }
                elseif ($v['status'] == 0) {    //还没返点的直接更改状态为2
                }
                elseif ($v['status'] == 1) {    //对于已经返点的
                    if (!isset($users[$v['user_id']])) {
                        if (!$users[$v['user_id']] = users::getItem($v['user_id'], -1)) {
                            throw new exception2('用户不存在或已被禁用', 1100);
                        }
                    }
                    //1.扣除用户返点 及帐变
                    if (!users::updateBalance($v['user_id'], -$v['rebate_amount'])) {
                        throw new exception2('数据错误', 3500);
                    }
                    $orderData = array(
                        'lottery_id' => $lottery['lottery_id'],
                        'issue' => $issueInfo['issue'],
                        'from_user_id' => $v['user_id'],
                        'from_username' => $users[$v['user_id']]['username'],
                        'to_user_id' => 0,
                        'to_username' => '',
                        'type' => 411, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                        'amount' => -$v['rebate_amount'],
                        'pre_balance' => $users[$v['user_id']]['balance'],
                        'balance' => $users[$v['user_id']]['balance'] - $v['rebate_amount'],
                        'create_time' => date('Y-m-d H:i:s'),
                        'business_id' => $v['package_id'],
                        'admin_id' => $admin_id,
                    );
                    if (!orders::addItem($orderData)) {
                        log2("errno=3150", $orderData);
                        throw new exception2('数据错误', 3510);
                    }
                    $users[$v['user_id']]['balance'] -= $v['rebate_amount']; //及时更新最新余额
                }
                else {
                    throw new exception2('未知的状态');
                }
            }

            //2.置状态为2 返点状态(0:未返;1:已返;2:已撤)
            if (false === userDiffRebates::updateItem($v['udr_id'], array('status' => 2))) {
                throw new exception2('数据错误', 3510);
            }
        }

        return true;
    }

    /**
     * 批量判断中奖 开错号码时需要重新判断中奖时要调用此方法
     * @param Array $lottery
     * @param Array $issueInfo
     * @return Array  array('totalPackages' => 总订单数, 'totalAmount' => 总金额, 'prizeCounter' => 中奖订单数, 'totalPrize' => 总奖金, );
     */
    static public function batchCheckPrize($lottery, $issueInfo)
    {
        if (empty($lottery) || empty($issueInfo)) {
            throw new exception2('参数无效', 1101);
        }
        if (!$issueInfo['code'] || $issueInfo['status_code'] != 2) {
            throw new exception2('该期还未开奖', 1109);
        }
        if ((!$lottery = lottery::getItem($lottery['lottery_id']))) {
            throw new exception2('彩种正在维护中，稍后开放', 1120);
        }

        //异常处理优先
        if ($pendingError = issueErrors::getPendingError($lottery['lottery_id'], $issueInfo['issue'])) {
            // 标记为未执行, CLI 退出 (让异常CLI优先运行)
            issues::updateItem($issueInfo['issue_id'], array('status_check_prize' => 0));
            throw new exception2('该期有异常处理任务', 1106);
        }

        //奖期标记设置为: 进行'中奖判断'中...
        issues::updateItem($issueInfo['issue_id'], array('status_check_prize' => 1));
        issuesMini::updateItem($issueInfo['issue_id'], array('status_check_prize' => 1));
        $packages = projects::getPackages($lottery['lottery_id'], -1, -1, $issueInfo['issue']);

        //批量无疑更高效
        $result = game::checkPrize($lottery, $issueInfo, $packages);

        log2("batchCheckPrize {$lottery['name']} {$issueInfo['issue']}期开奖号码{$issueInfo['code']},共有{$result['totalPackages']}个订单，总计{$result['totalAmount']}元，其中{$result['prizeCounter']}个订单中奖，总奖金{$result['totalPrize']}元");

        //奖期标记设置为: 完成'中奖判断'...
        issues::updateItem($issueInfo['issue_id'], array('status_check_prize' => 2));
        issuesMini::updateItem($issueInfo['issue_id'], array('status_check_prize' => 2));
        return $result;
    }

    /**
     * 使用共享库后批量判断中奖 开错号码时需要重新判断中奖时要调用此方法
     * @param Array $lottery
     * @param Array $issueInfo
     * @return Array  array('totalPackages' => 总订单数, 'totalAmount' => 总金额, 'prizeCounter' => 中奖订单数, 'totalPrize' => 总奖金, );
     */
    static public function batchCheckPrize2($lottery, $issueInfo)
    {
        if (empty($lottery) || empty($issueInfo)) {
            throw new exception2('参数无效', 1101);
        }
        if (!$issueInfo['code']) {
            throw new exception2('该期还未开奖', 1109);
        }
        if ((!$lottery = lottery::getItem($lottery['lottery_id']))) {
            throw new exception2('彩种正在维护中，稍后开放', 1120);
        }

        //异常处理优先
        if ($pendingError = issueErrors::getPendingError($lottery['lottery_id'], $issueInfo['issue'])) {
            // 标记为未执行, CLI 退出 (让异常CLI优先运行)
            checkSend::updateItem($issueInfo['issue_id'], array('status_check_prize' => 0));
            throw new exception2('该期有异常处理任务', 1106);
        }

        //奖期标记设置为: 进行'中奖判断'中...
        // checkSend::updateItem($issueInfo['issue_id'], array('status_check_prize' => 1));
        $packages = projects::getPackages($lottery['lottery_id'], -1, -1, $issueInfo['issue']);

        //批量无疑更高效
        $result = game::checkPrize($lottery, $issueInfo, $packages);

        log2("batchCheckPrize {$lottery['name']} {$issueInfo['issue']}期开奖号码{$issueInfo['code']},共有{$result['totalPackages']}个订单，总计{$result['totalAmount']}元，其中{$result['prizeCounter']}个订单中奖，总奖金{$result['totalPrize']}元");

        //奖期标记设置为: 完成'中奖判断'...
        checkSend::updateItem($issueInfo['issue_id'], array('status_check_prize' => 2));

        return $result;
    }

    //批量判断订单是否中奖 中奖后返回所得奖金，不中返回0 开错号码时需要重新判断中奖时要调用此方法 一般不单独执行
    static public function checkPrize($lottery, $issueInfo, $packages)
    {
        if (!is_array($lottery) || !is_array($issueInfo) || !is_array($packages)) {
            throw new exception2('参数无效', 1121);
        }

        if (!$packages) {
            return array('totalPackages' => 0, 'totalAmount' => 0, 'prizeCounter' => 0, 'totalPrize' => 0);
        }

        $packageIds = array_keys(array_spec_key($packages, 'package_id'));
        $projects = projects::getItems(0, 0, $packageIds);
        asort($projects);
        $packageProjects = array();
        foreach ($projects as $v) {
            $packageProjects[$v['package_id']][$v['project_id']] = $v;
        }
        $methods = methods::getItems($lottery['lottery_id']);
        //得到该期所有游戏用户的返点
        $userIds = array_keys(array_spec_key($packages, 'user_id'));
        $rebates = userRebates::getUsersRebates($userIds, $lottery['property_id']);
        $totalAmount = $totalPrize = $prizeCounter = 0;
        foreach ($packages as $package) {
            //如果撤单了的不应再中奖派奖
            if ($package['cancel_status'] > 0) {
                continue;
            }

            if ($package['create_time'] > $issueInfo['end_sale_time']) {
                // log2("system halted.购买时间{$package['create_time']}超过当期销售截止时间{$issueInfo['end_sale_time']}，拒绝判奖！");
                // throw new exception2("system halted.购买时间{$package['create_time']}超过当期销售截止时间{$issueInfo['end_sale_time']}，拒绝判奖！");
                log2file('checkSendErr.log', "订单ID:{$package['package_id']}异常.购买时间{$package['create_time']}超过当期销售截止时间{$issueInfo['end_sale_time']}，拒绝判奖！");
                continue;
            }

            $packagePrize = 0;
            foreach ($packageProjects[$package['package_id']] as $v) {
                if (!isset($methods[$v['method_id']])) {
                    // throw new exception2("玩法id={$v['method_id']}不存在,projectID：{$v['project_id']}");
                    log2file('checkSendErr.log', "订单ID:{$package['package_id']}异常.玩法id={$v['method_id']}不存在,projectID：{$v['project_id']}");
                    continue 2;
                }

                if (!isset($rebates[$v['user_id']])) {
                    // throw new exception2("user_id={$v['user_id']}的返点不存在");
                    log2file('checkSendErr.log', "订单ID:{$package['package_id']}异常.user_id={$v['user_id']}的返点不存在");
                    continue 2;
                }

                if (!in_array($v['modes'], array('1','0.5', '0.1', '0.05','0.01','0.005', '0.001'))) {
                    // throw new exception2('Invalid modes');
                    log2file('checkSendErr.log', "订单ID:{$package['package_id']}异常.Invalid modes{$v['modes']}");
                    continue 2;
                }

                //030411 检查投注号码是否被恶意修改 md5("{$user_id}{$v['code']}{$now}")
                if (md5($v['user_id'] . $v['code'] . $v['create_time']) != $v['hash_value']) {
                    // throw new exception2("system halted.号码已被非法修改，拒绝进行中奖判断 project_id:{$v['project_id']},user_id:{$v['user_id']},code:{$code}");
                    log2file('checkSendErr.log', "号码已被非法修改，拒绝进行中奖判断 project_id:{$v['project_id']},user_id:{$v['user_id']},code:{$v['code']}");
                    continue 2;
                }
                $prize = methods::computePrize($lottery, $methods[$v['method_id']], $v['user_id'], $rebates[$v['user_id']], $v['cur_rebate'], $issueInfo['code'], $v['code']);

                $finalPrize = round($prize * $v['multiple'] * $v['modes'], PRIZE_PRECISION);

                //log2file("checkPrize.log", $logStr);
                if ($finalPrize > 0) {
                    $packagePrize += $finalPrize;
                    if (false === projects::updateItem($v['project_id'], array('prize' => $finalPrize))) {
                        // throw new exception2('数据错误');
                        log2file('checkSendErr.log', "projectID：{$v['project_id']},更新prize：{$finalPrize} 失败");
                        continue 2;
                    }
                }
            }

            $packagePrize = round($packagePrize, 4);

            //check prize的时候，同时更新send_prize_time，作为此订单的最终统计时间
            projects::updatePackage($package['package_id'], array('prize' => $packagePrize, 'check_prize_status' => $packagePrize > 0 ? 1 : 2, 'send_prize_time' => date('Y-m-d H:i:s')));

            //顺便检查下如果是追号单，更新下traces表的finish_times+1 追号单的处理最好另做一个cron单独执行
            if ($package['trace_id']) {   //如果是追号订单
                $trace = traces::getItem($package['trace_id']);
                //如果未开始，先置状态为开始再说
                if ($trace['status'] == 0) {
                    traces::updateItem($package['trace_id'], array('status' => 1));
                }
                traces::updateFinishTimes($package['trace_id'], 1);

                //得到是否有待完成的订单
                $tracePackages = projects::getPackages($trace['lottery_id'], -1, -1, '', $trace['trace_id']);
                foreach ($tracePackages as $kk => $vv) {
                    //if ($vv['package_id'] <= $package['package_id'] || $vv['cancel_status'] > 0) {
                    //以前的逻辑是默认 前面的全部开奖了.后面的全部没开
                    //现在是未判断的 ,未撤单的,不是当前期才撤单
                    if ($vv['cancel_status'] > 0 || $vv['check_prize_status'] > 0 || $vv['package_id'] == $package['package_id']) {
                        unset($tracePackages[$kk]); //这里是反逻辑 unset的是 不能被撤单的
                    }
                }

                //如果中奖了 对之后未完成的订单撤单
                if ($packagePrize > 0) {
                    //先更新中奖次数
                    traces::updateWinTimes($package['trace_id'], 1);
                    log2("追号订单中奖：lottery_id={$package['lottery_id']},issue={$package['issue']},trace_id={$package['trace_id']},package_id={$package['package_id']}");
                    if ($trace['stop_on_win']) {    //如果设置了中奖即停，则应撤消之后的所有订单
                        if ($tracePackages) {
                            log2("因设置了追中即停，所以后面几期(" . implode(',', array_keys($tracePackages)) . ")均被自动撤单");
                            //traces::updateCancelTimes($package['trace_id'], count($tracePackages));
                            foreach ($tracePackages as $v) {
                                if ($v['cancel_status'] == 0) {
                                    game::cancelPackage($v, 2, self::SYSTEM_ADMIN_ID);   //撤单类型 1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
                                }
                            }
                            //本次追号状态更新为已完成
                            traces::updateItem($package['trace_id'], array('status' => 2));
                        }
                    }
                }

                //如果没有需要追号的订单了，应设置该追号为已完成
                if (!$tracePackages) {
                    traces::updateItem($package['trace_id'], array('status' => 2));
                    log2("lottery_id={$package['lottery_id']},issue={$package['issue']},package_id={$package['package_id']},trace_id={$package['trace_id']}刚好是追号的最后一期，因此直接更新状态为已完成，不需判断中奖即停");
                }
            }

            $totalAmount += $package['amount'];
            if ($packagePrize > 0) {
                $totalPrize += $packagePrize;
                $prizeCounter++;
            }
        }

        return array('totalPackages' => count($packages), 'totalAmount' => $totalAmount, 'prizeCounter' => $prizeCounter, 'totalPrize' => $totalPrize);
    }

    /**
     *  by jerry 功能：审核指定条件的所有订单是否有各种异常（如投注时间大于奖期截止时间、hash值校验不符等等），用来审核订单的合法性
     * 可指定条件：采种，日期，奖期（不指定为当天所有奖期）
     * @staticvar array $methods
     * @param type $lottery
     * @param type $issueInfo
     * @param type $package
     * @return array 返回有问题的$projects数组array('projects id'=>'问题类型')
     * @throws exception2
     */
    static public function checkProjects($lotteryId = 0, $issue = '', $start_time = '', $end_time = '')
    {
        if (!is_int($lotteryId)) {
            throw new exception2('参数无效', 1221);
        }
        $projects = projects::getItems($lotteryId, $issue, 0, 0, $start_time, $end_time);
        if (!$projects || !is_array($projects)) {
            return array();
        }
        asort($projects);
        $result = array();
        //作为缓存 防止每次需要这个奖期都去取数据
        $issues = array();
        foreach ($projects as $v) {
            $code = substr($v['code'], 0, 20) . '......';
            $info = "project_id:{$v['project_id']},package_id:{$v['package_id']},user_id:{$v['user_id']},code:{$code}";
            $issue = array();
            if (isset($issues[md5($v['issue'] . $v['lottery_id'])])) {
                $issue = $issues[md5($v['issue'] . $v['lottery_id'])];
            }
            $issue = issues::getItem(0, $v['issue'], $v['lottery_id']);
            if (!$issue) {
                $tempError = "没有这个奖期, $info";
                if (isset($result[$v['project_id']])) {
                    //有2个错误 拼接起来
                    $result[$v['project_id']] = $result[$v['project_id']] . '------' . $tempError;
                }
                else {
                    $result[$v['project_id']] = $tempError;
                }
                //没有这个奖期就不需要判断了
                continue;
            }
            $issues[md5($v['issue'] . $v['lottery_id'])] = $issue;
            //030411 检查投注号码是否被恶意修改 md5("{$user_id}{$v['code']}{$now}")
            if (md5($v['user_id'] . $v['code'] . $v['create_time']) != $v['hash_value']) {
                $tempError = "号码已被非法修改, $info";
                if (isset($result[$v['project_id']])) {
                    //有2个错误 拼接起来
                    $result[$v['project_id']] = $result[$v['project_id']] . '------' . $tempError;
                }
                else {
                    $result[$v['project_id']] = $tempError;
                }
            }
           // if ($v['create_time'] < $issue['start_sale_time']) {
           //     $tempError = "投注时间大于奖期起始时间，$info";
           //     if (isset($result[$v['project_id']])) {
           //         //有2个错误 拼接起来
           //         $result[$v['project_id']] = $result[$v['project_id']] . '------' . $tempError;
           //     }
           //     else {
           //         $result[$v['project_id']] = $tempError;
           //     }
           // }
            if ($v['create_time'] > $issue['end_sale_time']) {
                $tempError = "投注时间大于奖期截止时间，$info";
                if (isset($result[$v['project_id']])) {
                    //有2个错误 拼接起来
                    $result[$v['project_id']] = $result[$v['project_id']] . '------' . $tempError;
                }
                else {
                    $result[$v['project_id']] = $tempError;
                }
            }
        }

        return $result;
    }

    //批量派奖 根据获奖的方案project的具体奖金$project['prize']添加到用户帐号，写帐变，就这么简单
    static public function batchSendPrize($lottery, $issueInfo)
    {
        if (!is_array($lottery) || !is_array($issueInfo)) {
            throw new exception2('Invalid arg', 1101);
        }
        if (!$issueInfo['code'] || $issueInfo['status_code'] != 2) {
            throw new exception2('该期还未开奖', 1105);
        }
        //如果还未进行过中奖判断，拒绝继续执行
        //bugfix: status_check_prize不仅是!=0 必须为2方可
        if ($issueInfo['status_check_prize'] != 2 || $issueInfo['status_code'] != 2) {
            throw new exception2('该期还未进行中奖判断11', 1108);
        }

        //异常处理优先
        if ($pendingError = issueErrors::getPendingError($lottery['lottery_id'], $issueInfo['issue'])) {
            // 标记为未执行, CLI 退出 (让异常CLI优先运行)
            issues::updateItem($issueInfo['issue_id'], array('status_send_prize' => 0));
            throw new exception2('该期有异常处理任务', 1106);
        }

        //奖期标记设置为: 进行'派奖'中...
        issues::updateItem($issueInfo['issue_id'], array('status_send_prize' => 1));
        issuesMini::updateItem($issueInfo['issue_id'], array('status_send_prize' => 1));
        $packages = projects::getPackages($lottery['lottery_id'], -1, -1, $issueInfo['issue']);

        foreach ($packages as $package) {
            game::sendPrize($lottery, $issueInfo, $package);
        }

        //休眠5秒再次派送一次，因为出现了1+1!=2的诡异情况。实在菊紧
        echo "休眠5秒再次派送一次\n";
        sleep(5);
        $packageObj = new baseModel('packages');
        $packagess = $packageObj->where([
                        'lottery_id' => $lottery['lottery_id'],
                        'issue' => $issueInfo['issue'],
                        'check_prize_status' => 1,
                        'send_prize_status' => 0
                        ])->select();

        foreach ($packagess as $item) {
            game::sendPrize($lottery, $issueInfo, $item);
        }

        //奖期标记设置为: 完成'派奖'中...
        issues::updateItem($issueInfo['issue_id'], array('status_send_prize' => 2));
        issuesMini::updateItem($issueInfo['issue_id'], array('status_send_prize' => 2));

        return count($packages) + count($packagess);
    }

    //共享库后批量派奖 根据获奖的方案project的具体奖金$project['prize']添加到用户帐号，写帐变，就这么简单
    static public function batchSendPrize2($lottery, $issueInfo)
    {
        if (!is_array($lottery) || !is_array($issueInfo)) {
            throw new exception2('Invalid arg', 1101);
        }
        if (!$issueInfo['code']) {
            throw new exception2('该期还未开奖', 1105);
        }

        //异常处理优先
        if ($pendingError = issueErrors::getPendingError($lottery['lottery_id'], $issueInfo['issue'])) {
            // 标记为未执行, CLI 退出 (让异常CLI优先运行)
            checkSend::updateItem($issueInfo['issue_id'], array('status_send_prize' => 0));
            throw new exception2('该期有异常处理任务', 1106);
        }

        //奖期标记设置为: 进行'派奖'中...
        // checkSend::updateItem($issueInfo['issue_id'], array('status_send_prize' => 1));

        $packages = projects::getPackages($lottery['lottery_id'], -1, -1, $issueInfo['issue']);

        foreach ($packages as $package) {
            game::sendPrize($lottery, $issueInfo, $package);
        }

        // 休眠5秒再次派送一次，因为出现了1+1!=2的诡异情况。实在菊紧
        // echo "休眠3秒再次派送一次\n";
        // sleep(3);
        // $packageObj = new baseModel('packages');
        // $packagess = $packageObj->where([
        //                 'lottery_id' => $lottery['lottery_id'],
        //                 'issue' => $issueInfo['issue'],
        //                 'check_prize_status' => 1,
        //                 'send_prize_status' => 0
        //                 ])->select();

        // foreach ($packagess as $item) {
        //     game::sendPrize($lottery, $issueInfo, $item);
        // }

        //奖期标记设置为: 完成'派奖'中...
        checkSend::updateItem($issueInfo['issue_id'], array('status_send_prize' => 2));

        return count($packages);
    }

    //派奖 一般不单独执行
    static public function sendPrize($lottery, $issueInfo, $package)
    {
        if (!is_array($lottery) || !is_array($issueInfo) || !is_array($package)) {
            throw new exception2('参数无效', 1121);
        }
        if ($package['check_prize_status'] != 1) {
            log2("奖期 {$package['issue']} package_id={$package['package_id']} user_id {$package['user_id']}没有中奖，忽略 check_prize_status {$package['check_prize_status']}");
            return 0;
        }
        if ($package['send_prize_status'] != 0) {
            log2("奖期 {$package['issue']} package_id={$package['package_id']} 已派过奖，不能再次派奖");
            return 0;
        }
        if ($package['cancel_status'] > 0) {
            log2("奖期 {$package['issue']} package_id={$package['package_id']},cancel_status={$package['cancel_status']} 已撤单的当然不能再派奖");
            return 0;
        }

        //开始事务
        $GLOBALS['db']->startTransaction();
        if (!$user = users::getItem($package['user_id'], -1)) {
            throw new exception2("找不到用户user_id={$package['user_id']}");
        }

        //1给用户加钱 及帐变
        if (!users::updateBalance($package['user_id'], $package['prize'])) {
            log2("奖期 {$package['issue']} user_id={$package['user_id']} package_id={$package['package_id']} 金额={$package['prize']}派奖失败");
            $GLOBALS['db']->rollback();
            throw new exception2('数据错误', 3120);
        }

        $orderData = array(
            'lottery_id' => $package['lottery_id'],
            'issue' => $package['issue'],
            'from_user_id' => $package['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 308, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖 414追号扣款 415奖池嘉奖
            'amount' => $package['prize'],
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] + $package['prize'],
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $package['package_id'],
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            log2("errno=3130", $orderData);
            $GLOBALS['db']->rollback();
            throw new exception2('数据错误', 3130);
        }


        //2.send_prize_status 由0未派奖改为1已派奖
        projects::updatePackage($package['package_id'], array('send_prize_status' => 1));

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        return true;
    }

    //派奖 一般不单独执行-用户后台人工派奖
    static public function sendPrizeAdmin($lottery, $issueInfo, $package)
    {
        if (!is_array($lottery) || !is_array($issueInfo) || !is_array($package)) {
            self::$error = '异常信息';
            return false;
        }

        if ($package['cancel_status'] > 0) {
            self::$error = '订单已经撤单!';
            return false;
        }

        //开始事务
        $GLOBALS['db']->startTransaction();
        if (!$user = users::getItem($package['user_id'], -1)) {
            self::$error = '找不到用户!';
            return false;
        }

        //1给用户加钱 及帐变
        if (!users::updateBalance($package['user_id'], $package['prize'])) {
            self::$error = '用户帐变失败!';
            $GLOBALS['db']->rollback();
            return false;
        }
        $lottery_id = $package['lottery_id'];
        $user_id = $package['user_id'];
        $package_id = $package['package_id'];
        $orderData = array(
            'lottery_id' => $lottery_id,
            'issue' => $package['issue'],
            'from_user_id' => $user_id,
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 308, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖 414追号扣款 415奖池嘉奖
            'amount' => $package['prize'],
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] + $package['prize'],
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $package_id,
            'admin_id' => 0,
        );

        if (!orders::addItem($orderData)) {
            self::$error = '添加order信息时出错!';
            $GLOBALS['db']->rollback();
            return false;
        }


        //2.send_prize_status 由0未派奖改为1已派奖
       $res =  projects::updatePackage($package_id, array('send_prize_status' => 1));
        if(!$res)
        {
            self::$error = '更改订单信息出错!';
            $GLOBALS['db']->rollback();
            return false;
        }
        $t = date('Y-m-d H:i:s');
        $admin_username = $GLOBALS['SESSION']['admin_username'];
        $admin_id = $GLOBALS['SESSION']['admin_id'];
        $log[0]=array(
              'admin_id'=>$admin_id,
            'package_id'=>$package_id,
            'admin_username'=>$admin_username,
            'prize'=>$package['prize'],
            'remark'=>$admin_username."($admin_id)在{$t}对订单".$package_id.'进行人工反奖',
            'package_create_date'=>date('Y-m-d',strtotime($package['create_time'])),
            'package_create_time'=>$package['create_time'],
            'client_ip'=>self::getClientIp(),
            'date'=>date('Y-m-d'),
        );
        $res = projects::addThePrizePackageLog($log);
        if(!$res)
        {
            self::$error = '记录日志出错!';
            $GLOBALS['db']->rollback();
            return false;
        }
        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            self::$error = '提交事务时出错!';
            return false;
        }

        return true;
    }

   static public function getClientIp()
    {
        static $realip = NULL;

        if ($realip !== NULL) {
            return $realip;
        }

        if (isset($_SERVER)) {

            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
            elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realip = $ip;
                        break;
                    }
                }
            }
            elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $realip = $_SERVER['HTTP_X_REAL_IP'];
            }
            else {
                $realip = '0.0.0.0';
            }
        }
        else {
            if (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            }
            elseif (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            }
            elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $realip = $_SERVER['HTTP_X_REAL_IP'];
            }
            else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

        return $realip;
    }


    //取消派奖 无事务 第3个参数为0表示按奖期批量取消
    static public function cancelPrize($lottery, $issueInfo, $package)
    {
        if ($lottery['status'] != 8) {
            throw new exception2('彩种不存在或已停售', 1120);
        }
        //判断该期是否开出号码了
        if (!$issueInfo['code'] || $issueInfo['status_code'] != 2) {
            //这里正常返回即可，因为还没开奖的奖期追号单允许撤消的
            return 0;
        }
        if ($package['check_prize_status'] != 1 || $package['prize'] == 0) {
            return 0;
        }
        if ($package['send_prize_status'] != 1) {
            log2("package_id={$package['package_id']} 未曾派过奖，忽略");
            return 0;
        }

        if (!$user = users::getItem($package['user_id'], -1)) {
            throw new exception2("找不到用户user_id={$package['user_id']}");
        }
        //1.扣除奖金 及帐变
        if (!users::updateBalance($package['user_id'], -$package['prize'])) {
            throw new exception2('数据错误', 3120);
        }
        $orderData = array(
            'lottery_id' => $package['lottery_id'],
            'issue' => $package['issue'],
            'from_user_id' => $package['user_id'],
            'from_username' => $user['username'],
            'to_user_id' => 0,
            'to_username' => '',
            'type' => 413, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
            'amount' => -$package['prize'],
            'pre_balance' => $user['balance'],
            'balance' => $user['balance'] - $package['prize'],
            'create_time' => date('Y-m-d H:i:s'),
            'business_id' => $package['package_id'],
            'admin_id' => 0,
        );
        if (!orders::addItem($orderData)) {
            log2("errno=3130", $orderData);
            throw new exception2('数据错误', 3130);
        }

        //2.send_prize_status 由已派奖1改为未派奖0
        projects::updatePackage($package['package_id'], array('send_prize_status' => 0));    //时间记录保留无妨, 'send_prize_time' => '0000-00-00 00:00:00'

        return true;
    }

    static public function cancelTrace($user_id, $trace_id, $package_ids, $admin_id = 0)
    {
        if (!is_numeric($user_id) || !is_numeric($trace_id) || !is_array($package_ids) || !count($package_ids)) {
            throw new exception2('参数无效');
        }

        if (!$trace = traces::getItem($trace_id, true)) {
            throw new exception2('找不到该追号单', 1100);
        }

        if ($trace['is_locked']) {
            throw new exception2('该追号单正在使用', 1101);
        }


        if (!$packages = projects::getPackages(0, -1, -1, '', $trace_id, 0, $user_id)) {
            throw new exception2('找不到该追号计划相应订单', 1102);
        }
        foreach ($package_ids as $package_id) {
            if (!isset($packages[$package_id])) {
                throw new exception2('参数无效');
            }
        }
        //>>author snow 在所有没有问题的地方再进行锁操作,要不然会形成死锁
        if (!traces::updateItem($trace_id, array('is_locked' => 1))) {
            throw new exception2('锁定追号单失败', 9801);
        }
        foreach ($package_ids as $package_id) {
            game::cancelPackage($packages[$package_id], $admin_id ? 9 : 1, $admin_id);    //1用户撤单 2追中撤单 3出号撤单 4未开撤单 9管理员撤单
        }

        //如果没有可以进行的追号单，应更改追号状态为已取消
        foreach ($package_ids as $package_id) {
            if (isset($packages[$package_id])) {
                unset($packages[$package_id]);
            }
        }
        if (!$packages) {
            traces::updateItem($trace_id, array('status' => 3));
        } else {
            $package = reset($packages);
            $issueInfos = issues::getItemsByIssue($package['lottery_id'], array_keys(array_spec_key($packages, 'issue')));
            $count = 0;
            foreach ($packages as $v) {
                if (time() < strtotime($issueInfos[$v['issue']]['cannel_deadline_time']) && $v['cancel_status'] == 0) {
                    $count++;
                }
            }
            if (!$count) {
                traces::updateItem($trace_id, array('status' => 3));
            }
        }

        //130903 为防止并发读 加入独占锁 解锁
        traces::updateItem($trace_id, array('is_locked' => 0));


        return true;
    }

    /**
     * @desc 组装数据格式
     * @param type $codesType 46:1,2,3,4,5|6,7,8,9,0|1,2,3,4,5#43:1,2,3|6,7,0
     * @param type $isXgame 是否信用玩法
     * @return array
     */
    static public function getCodesType($codesType)
    {
        $codesGroup = explode('#', $codesType);
        if (is_array($codesGroup)) {
            $key = 0;
            foreach ($codesGroup as $group) {
                $arrMethodCode = explode(':', $group);
                $methodId = $arrMethodCode[0];
                $arrCode = explode('|', $arrMethodCode[1]);
                if (is_array($arrCode)) {
                    foreach ($arrCode as $code) {
                        $codes[$key]['method_id'] = $methodId;
                        $codes[$key]['code'] = $code;
                        $key++;
                    }
                }
                else {
                    $codes = array();
                }
            }
        }
        else {
            $codes = array();
        }
        return $codes;
    }

    /**
     *  展开所选的项目
     * @param type $tmpCodes 返回的数组
     * @param type $methodName
     * @param type $code 原始数字
     * @throws exception2 错误
     */
    public static function getExpandCodes($methodName, $code)
    {
        $result = array();
        switch ($methodName) {
            case 'HLZX'://01_02_03_04_05,05
                $tmp = explode(',', $code);
                $redCode = explode('_', $tmp[0]);
                $redRes = methods::C($redCode, 5);
                $blueRes = explode('_', $tmp[1]);
                foreach ($blueRes as $blue) {
                    foreach ($redRes as $red) {
                        $result[] = (int)($red . $blue);
                    }
                }
                break;
            case 'TMZX':
            case 'TMSX':
            case 'TMWS':
            case 'TMSB':
            case 'TMDXDS':
            case 'ZTYM':
            case 'ZTYX':
            case 'ZTWS':
                $result = array_unique(explode('_', $code));
                break;
            case 'ELX':
            case 'SLX':
            case 'SILX':
                $c = $methodName == 'ELX' ? 2 : $methodName == 'SLX' ? 3 : 4;
                $codes = array_unique(explode('_', $code));
                $result = methods::C($codes, $c);
                break;
            case 'SZS':
            case 'SZE':
                $codes = array_unique(explode('_', $code));
                $result = methods::C($codes, 3);
                break;
            case 'EZE':
                $codes = array_unique(explode('_', $code));
                $result = methods::C($codes, 2);
                break;
            case 'SXZS'://三星组三
            case 'QSZS':
            case 'ZSZS':
                $codes = array_unique(str_split($code));
                sort($codes);
                $result = methods::C($codes, 2);
                break;
            case 'SXZL':
            case 'QSZL':
            case 'ZSZL':
                $codes = array_unique(str_split($code));
                sort($codes);
                $result = methods::C($codes, 3);
                break;
            case 'ZSZX':
            case 'QSZX':
            case 'SXZX':
            case 'QEZX':
            case 'EXZX':
            case 'YXZX':
                $result = methods::expand(explode(",", $code));
                break;
            case 'SXHHZX':    //三星混合组选 仅支持单式手工录入 12,34,567
            case "ZSHHZX":
            case 'QSHHZX':
                $codeExp = explode(",", $code);
                $codeExp = array_unique($codeExp);
                sort($codeExp);
                $result = methods::expand($codeExp);
                break;
            default:
                throw new exception2('不支持限制此玩法');
                break;
        }

        return $result;
    }

    /**
     *  用户前台投注 一次购买称为一个订单（package），一个订单可以包括不同玩法购买方案（project）
     *  购买成功时返回该订单编号
     * @param int $lottery_id
     * @param string $issue
     * @param float $curRebate
     * @param float $modes
     * @param int $user_id
     * @param array $projects 结构如下：
     *  array(0 => array(
     *      'method_id' => 1,   //玩法id 三星直选
     *      'code'      => '1,2,345',   //所购号码
     *      'single_num' => 3, //单倍注数，不作依赖，需要核实
     *      'single_amount' => 6,   //单倍金额，不作依赖，需要核实
     *      ),
     * )
     */
    static public function mmcBuy($curRebate, $modes, $projects, $multiple, $openCounts = 1)
    {
        //防止同一用户瞬间多次摇奖; 3秒之内不允许
        if (!preventDDosAttack('mmc_ddos_cache', $GLOBALS['SESSION']['user_id'], 3)) {
            throw new exception2('网络繁忙，请稍后再试', 3001);
        }

        $lottery_id = 15;
        $user_id = $GLOBALS['SESSION']['user_id'];

        //////////////////////// 购彩前 校验 ///////////////////////////////
        if (!is_numeric($lottery_id) || !is_numeric($curRebate) || $curRebate < 0 || !is_numeric($modes) || !isset($GLOBALS['cfg']['modes'][$modes]) || !is_numeric($user_id) || !is_array($projects) || !is_numeric($multiple) || $multiple <= 0) {
            log2("errcode=999", func_get_args());
            throw new exception2('参数不对', 999);
        }

        //在有数据库操作前先定义$now，这是客户端提交的准确时间
        $now = date('Y-m-d H:i:s');

        //先检查是否开售时间
        if (date('Hi') >= '0400' && date('Hi') <= '0459') {
            throw new exception2('现在是停售时间，请耐心等待', 1100);
        }

        //1.检查彩种是否在售，是否在销售期，奖金组是否存在
        if (!$lottery = lottery::getItem($lottery_id)) {
            throw new exception2('彩种不存在或已停售', 1110);
        }

        if($user_id!=1){
            if ($lottery['status'] != 8) {
                throw new exception2('彩种正在维护中，稍后开放', 1120);
            }
        }


        //得到每种玩法的奖金 用于稍后判断是否超限
        if (!$prizes = prizes::getItems($lottery_id, 0, 0, 0, 1)) {
            throw new exception2("找不到基本组数据", 1160);
        }

        //2.再检查所提交号码$codes的正确性，玩法是否被禁用，核算单期注数、总金额是否正确，并判断用户奖金是否足够。。。
        //2.1 五星的手工录入限制，防止过多的数据，意义不大，也影响用户投注体验
        if (count($projects) > self::MAX_LIMIT_BETS) {
            throw new exception2("最大注数不得超过" . self::MAX_LIMIT_BETS . "注", 2101);
        }
        $totalAmount = $singleTotalAmount = $totalSingleNums = 0;
        $limits = config::getConfigs(array('prize_limit', 'multiple_limit'));

        //倍数不能无限翻倍
        if ($limits['multiple_limit'] && $multiple > $limits['multiple_limit']) {
            throw new exception2("投注倍数超过最大允许数{$limits['multiple_limit']}", 2205);
        }

        $codeGroups = $singleNumGroups = $amountGroups = $tmp = array();

        //优化情节：把数据库查询放在可能的大数组循环是非常失败的！
        $methods = methods::getItemsById(array_unique(array_keys(array_spec_key($projects, 'method_id'))));
        foreach ($projects as $v) {
            //fixed 取消single_num和single_amount验证
            if (empty($v['method_id']) || !is_numeric($v['method_id']) || $v['method_id'] <= 0) {
                throw new exception2('号码选择错误', 2210);
            }
            if (!isset($methods[$v['method_id']])) {
                throw new exception2('玩法不存在', 2220);
            }

            if (!$singleNums = methods::isLegalCode($methods[$v['method_id']], $v['code'])) {
                throw new exception2("所选号码不符规定({$v['code']})", 2230);
            }
            $v['single_num'] = $singleNums;

            //核实single_num fixed 取消single_num验证
            //接下来，单倍注数$singleNums * 模式 = 单倍价格，用来核算提交的金额是否正确，不正确拒绝继续
            //bugfix:浮点数运算后直接比较会出错
            $v['single_amount'] = round($singleNums * 2 * $modes, 3);

            if (!in_array($modes, array('1','0.5', '0.1', '0.05','0.01','0.005', '0.001'))) {
                throw new exception2('Invalid modes');
            }

            $totalSingleNums += $v['single_num'];
            $singleTotalAmount += $v['single_amount'];

            //按玩法分组入库 避免手工录入产生太多记录
            $codeGroups[$v['method_id']][] = $v['code'];
            if (!isset($singleNumGroups[$v['method_id']])) {
                $singleNumGroups[$v['method_id']] = $v['single_num'];
            }
            else {
                $singleNumGroups[$v['method_id']] += $v['single_num'];
            }
            if (!isset($amountGroups[$v['method_id']])) {
                $amountGroups[$v['method_id']] = $v['single_amount'];
            }
            else {
                $amountGroups[$v['method_id']] += $v['single_amount'];
            }
        }

        $totalAmount = round($singleTotalAmount * $multiple, 3);  //本次订单总金额
        //logdump("总单倍注数$totalSingleNums , 总单倍金额$singleTotalAmount ，倍数$multiple ，总金额$totalAmount");
        if ($singleTotalAmount <= 0) {
            throw new exception2('投注金额异常', 2255);
        }

        //开始事务
        $GLOBALS['db']->startTransaction();

        //bugfix:为保证对奖金操作的原子性，事务内读用户表时用FOR UPDATE可有效防止并发对该行的读！
        if (!$user = users::getItem($user_id, 8, true)) {
            throw new exception2('用户不存在或已被禁用', 1100);
        }

        //检查用户余额是否足够本次投注; MMC追加连续多次开奖的判断
        if ($user['balance'] < round($totalAmount * $openCounts, 4)) {
            throw new exception2('用户余额不足', 2260);
        }

        //获取用户返点
        if (!$user['parent_tree']) {
            //throw new exception2('总代不可以玩游戏', 1135);   //总代可以玩游戏
            $userIds = array();
        }
        else {
            $userIds = explode(',', $user['parent_tree']);
        }
        $userIds[] = $user_id;  //还有自身
        $rebates = userRebates::getUsersRebates($userIds, $lottery['property_id']);

        /********************* snow 对返点进行重新排序**************************************/
        $rebates = self::_sortRebate($userIds, $rebates);
        /********************* snow 对返点进行重新排序**************************************/
        if (empty($rebates[$user_id])) {
            throw new exception2('用户奖金组不存在或被禁用', 1140);
        }
        if ($curRebate > $rebates[$user_id]) {
            throw new exception2("用户返点错误", 1150);
        }

        //判断奖金限红
        if ($limits['prize_limit']) {
            //$wxzxIds = [46,77,140,221,316,494];//五星直选ID
            foreach ($codeGroups as $method_id => $v) {
                $betCount = 1;
                // if(in_array($method_id,$wxzxIds)){//当五星直选时为了防止分注单投注时突破彩陪限额
                //     $betCount = count($v);
                // }
                $tmpPrize = ($multiple * $modes * $prizes[$method_id][1]['prize'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate) / (1 - $lottery['total_profit'])) * $betCount;
                if ($tmpPrize > $limits['prize_limit']) {
                    throw new exception2("投注超过最大赔彩限额{$limits['prize_limit']}", 2252);
                }
            }
        }

        //036114 如果是时时彩，加入对过高奖金组的检查：所选奖金不允许超过1950
        // if ($lottery['property_id'] == 1) {
            //直选转直注数	zx_max_comb
            //总水率	total_profit
            //公司最少留水	min_profit
            //最小返点差		min_rebate_gaps[0][from]	min_rebate_gaps[0][to]		min_rebate_gaps[0][gap]	 返点差
        //     $prizeMode = intval(2 * $lottery['zx_max_comb'] * (1 - $lottery['total_profit'] + $rebates[$user_id] - $curRebate));
        //     if ($prizeMode > 1950) {
        //         throw new exception2("您可选择的最大返点为1950模式", 1151);
        //     }
        // }

        //////////////////////// 购彩开始 ///////////////////////////////
        $thisBalance = $user['balance'];
        $mmcIssues = array();      //存储批量秒秒彩奖期：连续多次开奖时
        $mmcOpencodes = array();   //存储批量秒秒彩开奖号：连续多次开奖时
        $mmcTotalPrizes = array();   //每个package 中奖的奖金和(多个project累计)
        $packageDatas = $orderDatas = array();

        //生成随机奖期号 不能重复
        do {
            $key = substr(date('mdH') . strtoupper(md5(microtime(true) . rand(0, 10000000))), 0, 13);
            $mmcIssues[$key] = $key;
        } while (count($mmcIssues) < $openCounts);

        //先拼装projects数据 按玩法组分
        $projectDatas = array();
        foreach ($codeGroups as $method_id => $v) {
            $projectDatas[] = array(
                'user_id' => $user_id,
                'lottery_id' => $lottery_id,
                'method_id' => $method_id,
                'code' => implode("|", $v),
                'single_num' => $singleNumGroups[$method_id],
                'multiple' => $multiple, //各方案的倍数是一致的
                'amount' => $amountGroups[$method_id] * $multiple,
                'cur_rebate' => $curRebate,
                'modes' => $modes,
            );
        }

        $todayAllPackages = projects::getPackages(15, -1, 0, '', -1, 0, $user_id, 0, date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'), '', '', 0);
        $allCount = count($todayAllPackages);
        $winCount = 0;

        foreach ($todayAllPackages as $v) {
            if($v['check_prize_status'] == 1) {
                $winCount++;//当天已中奖次数
            }
        }

        $kill = config::getConfigs(array('kill_percent_15','loop_15'));
        $killPercent = $user['kill_mmc'] != 0 ? $user['kill_mmc'] : $kill['kill_percent_15'];
        //提前算中奖号，奖金
        foreach ($mmcIssues as $issue) {
            $killCodeArr = $tmpInfo = array();
            $loop = $kill['loop_15'];
            while ($loop) {
                $mmcTotalPrizes[$issue]['package_prize'] = 0;           //每个package的总奖金
                $mmcTotalPrizes[$issue]['method_prize'] = array();    //每个package，所有project玩法的奖金(数组)

                $tmp = drawSources::drawSelf($lottery, $issue, $projectDatas);
                $mmcOpencodes[$issue] = $tmp['number'];

                //按玩法组分
                foreach ($codeGroups as $method_id => $v) {
                    $prize = methods::computePrize($lottery, $methods[$method_id], $user_id, $rebates[$user_id], $curRebate, $mmcOpencodes[$issue], implode("|", $v));
                    $finalPrize = round($prize * $multiple * $modes, PRIZE_PRECISION);

                    $mmcTotalPrizes[$issue]['package_prize'] += $finalPrize;
                    $mmcTotalPrizes[$issue]['method_prize'][$method_id] = $finalPrize;
                }

                if($allCount == 0){
                    break;//当天第一次购买
                }
                $percent = round($winCount/$allCount,2);
                log2file('mmc.log', "中奖次数：{$winCount}，总次数：{$allCount}");
                if($percent <= $killPercent){//如果当前中奖率比设定值小 则这一期自然开奖
                    if($mmcTotalPrizes[$issue]['package_prize'] > 0){
                        $winCount++;
                    }
                    break;
                } else {//取系统设定值中最小的奖金开奖号

                    //$killCodeArr[$mmcTotalPrizes[$issue]['package_prize']] = $mmcOpencodes[$issue];
                    if(empty($tmpInfo)){
                        $tmpInfo = $mmcTotalPrizes[$issue];
                        $tmpInfo['code'] = $mmcOpencodes[$issue];
                    } else {
                        if($tmpInfo['package_prize'] > $mmcTotalPrizes[$issue]['package_prize']){
                            $tmpInfo = $mmcTotalPrizes[$issue];
                            $tmpInfo['code'] = $mmcOpencodes[$issue];
                        }
                    }

                }
                $loop--;
            }

            // if(!empty($killCodeArr)){//如果有杀率号码则取奖金最小的
            //     ksort($killCodeArr);
            //     $c=reset($killCodeArr);
            //     $mmcTotalPrizes[$issue] = $tmpInfo[$c];
            // }

            if(!empty($tmpInfo)){
                $mmcTotalPrizes[$issue] = $tmpInfo;
                $mmcOpencodes[$issue] = $tmpInfo['code'];
            }

            $mmcTotalPrizes[$issue]['package_prize'] = round($mmcTotalPrizes[$issue]['package_prize'], 4);

            ////////////////  package 表     ////////////////
            $packageDatas[$issue] = array(
                'user_id' => $user_id,
                'top_id' => $user['top_id'],
                'lottery_id' => $lottery_id,
                'issue' => $issue,
                'single_num' => $totalSingleNums,
                'multiple' => $multiple,
                'cur_rebate' => $curRebate,
                'modes' => $modes,
                'amount' => $totalAmount, //本订单总金额
                'create_time' => $now,
                'cancel_status' => 0,
                'cancel_time' => '0000-00-00 00:00:00',
                'user_ip' => isset($GLOBALS['REQUEST']['client_ip']) ? $GLOBALS['REQUEST']['client_ip'] : '0.0.0.0',
                'proxy_ip' => isset($GLOBALS['REQUEST']['proxy_ip']) ? $GLOBALS['REQUEST']['proxy_ip'] : '0.0.0.0',
                'server_ip' => $_SERVER['HTTP_HOST'], //暂时先记录从哪台机器来的，因为极少数订单延迟好几分钟才到达DB机器
                'check_prize_status' => $mmcTotalPrizes[$issue]['package_prize'] > 0 ? 1 : 2, // checkprize 1 中  2未中
                'send_prize_status' => $mmcTotalPrizes[$issue]['package_prize'] > 0 ? 1 : 0, // send_prize_status 由0未派奖改为1已派奖
                'send_prize_time' => $now,
                'prize' => $mmcTotalPrizes[$issue]['package_prize'], //直接写入中奖奖金
            );
            $allCount++;//当日总订单数递增
        }

        //写DB 第1次
        if (!projects::addPackages($packageDatas)) {
            log2("errno=3100", $packageDatas);
            throw new exception2('数据错误', 3100);
        }

        //读DB 第1次
        $packages = projects::getPackages($lottery_id, -1, -1, $mmcIssues);
        ksort($packages);

        //写project表
        $projectDatas = array();
        foreach ($packages as $package_id => $package) {
            //写方案表 按玩法组分
            foreach ($codeGroups as $method_id => $v) {
                $projectDatas[] = array(
                    'package_id' => $package_id,
                    'user_id' => $user_id,
                    'top_id' => $user['top_id'],
                    'lottery_id' => $lottery_id,
                    'method_id' => $method_id,
                    'issue' => $package['issue'],
                    'code' => implode("|", $v),
                    'single_num' => $singleNumGroups[$method_id],
                    'multiple' => $multiple, //各方案的倍数是一致的
                    'amount' => $amountGroups[$method_id] * $multiple,
                    'cur_rebate' => $curRebate,
                    'modes' => $modes,
                    'create_time' => $now,
                    'hash_value' => md5($user_id . implode("|", $codeGroups[$method_id]) . $now),
                    'prize' => $mmcTotalPrizes[$package['issue']]['method_prize'][$method_id], //中奖奖金
                );
            }

            //购彩扣款帐变
            $orderDatas[] = array(
                'lottery_id' => $lottery_id,
                'issue' => $package['issue'],
                'from_user_id' => $user_id,
                'from_username' => $user['username'],
                'to_user_id' => 0,
                'to_username' => '',
                'type' => 401, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                'amount' => -$totalAmount,
                'pre_balance' => $thisBalance,
                'balance' => $thisBalance - $totalAmount,
                'create_time' => $now,
                'business_id' => $package_id,
                'admin_id' => 0,
            );
            $thisBalance -= $totalAmount;
        }

        //写DB 第2次
        if (!projects::addItems($projectDatas)) {
            log2("errno=3110", $projectDatas);
            throw new exception2('数据错误', 3110);
        }

        //////////////////////////////// 派奖过程  提前合并到购彩帐变后面    //////////////////////////////
        foreach ($packages as $package_id => $package) {
            if ($package['prize'] > 0) {
                $orderDatas[] = array(
                    'lottery_id' => $package['lottery_id'],
                    'issue' => $package['issue'],
                    'from_user_id' => $package['user_id'],
                    'from_username' => $user['username'],
                    'to_user_id' => 0,
                    'to_username' => '',
                    'type' => 308, //帐变类型 101充值 102手续费优惠 103首存优惠 104再存优惠 105其他优惠 106提现不符退款 201提现 301返点 302下级返点 303撤单返款 304追号中止返款 308中奖 321理赔 401投注 411撤消返点 412撤单手续费 413撤消中奖
                    'amount' => $package['prize'],
                    'pre_balance' => $thisBalance,
                    'balance' => $thisBalance + $package['prize'],
                    'create_time' => $now,
                    'business_id' => $package['package_id'],
                    'admin_id' => 0,
                );

                $thisBalance += $package['prize']; //及时更新最新余额
            }
        }

        //写DB 第3次 给用户加钱 及帐变
        if (!users::updateBalance($user_id, $thisBalance - $user['balance'])) {
            log2('errno=3120,mmcbuy'.($thisBalance - $user['balance']));
            throw new exception2('数据错误', 3120);
        }

        //写DB 第4次 帐变表： 合并购彩与派奖一起插入DB
        if (!orders::addItems($orderDatas)) {
            log2("errno=3130", $orderDatas);
            throw new exception2('数据错误', 3130);
        }
        //////////////////////////////// End 派奖过程   //////////////////////////////
        //3.4 上级代理返点数据也插入返点表，但不马上更新上级的余额，以避免顶层代理余额更新过于频繁导致锁表，将另有批处理执行
        //先算出返点差及每个上级应返金额
        $diffRebates = array();
        $userGifts = false;
        $maxRebateLimit = round($lottery['total_profit'] - $lottery['min_profit'], REBATE_PRECISION);

        //注：测试帐号不允许向上级返点！
        //if ($user['is_test'] == 0 && !$userGifts) {
        if ($user['is_test'] == 0) {
            foreach ($rebates as $k => $v) {
                if ($v == reset($rebates)) {
                    $last_id = $k;
                }
                else {
                    $diffRebates[$last_id] = round($rebates[$last_id] - $v, REBATE_PRECISION);
                    if ($diffRebates[$last_id] < 0 || $diffRebates[$last_id] > $maxRebateLimit) {//上级必定有返点，且不能超过总返点
                        log2("返点出错：3400...{$user['username']}(user_id={$user['user_id']}) 的上级user_id={$last_id}的返点为{$rebates[$last_id]}，给他的返点为{$v}");
                        throw new exception2('返点出错', 3400);
                    }
                    $last_id = $k;
                }
            }
        }

        !!config::getConfig('rebate_toself', 1) && $diffRebates[$user_id] = $curRebate;    //注意：自身返点为投注时选择的具体返点

        //logdump($diffRebates, $lottery['total_profit'], $lottery['min_profit'], array_sum($diffRebates), $maxRebateLimit);
        //注意不能直接比较浮点数! 0.1 != 0.1

        if (round(array_sum($diffRebates), REBATE_PRECISION) > $maxRebateLimit) {    //返点总额当然不能超过总返点
            log2("返点出错:3410...{$user['username']}(user_id={$user['user_id']})", $diffRebates, array_sum($diffRebates), $maxRebateLimit);
            throw new exception2('返点出错', 3410);
        }

        //写user_diff_rebates表 返点为0的可以考虑不写入
        $udrData = array();
        foreach ($packages as $package_id => $package) {
            foreach ($diffRebates as $k => $v) {
                if (($rebateAmount = round($totalAmount * $v, 4)) > 0) {
                    $udrData[] = array(
                        'user_id' => $k,
                        'top_id' => $user['top_id'],
                        'lottery_id' => $lottery_id,
                        'issue' => $package['issue'],
                        'package_id' => $package_id,
                        'package_user_id' => $user_id, //$k == $user_id ? 1 : 0,
                        'modes' => $modes,
                        'diff_rebate' => $v,
                        'rebate_amount' => $rebateAmount,
                        'status' => 0, //$k == $user_id ? 1 : 0,
                        'create_time' => $now,
                    );
                }
            }
        }

        //写DB 第5次  写返点表
        if ($udrData && !userDiffRebates::addItems($udrData)) {
            log2("返点出错:3420...{$user['username']}(user_id={$user['user_id']})", $udrData);
            throw new exception2('返点出错', 3420);
        }

        //写DB 第6次 开奖结果存入issue_mmc表 供订单详情查
        $mmcIssueDatas = array();
        foreach ($mmcOpencodes as $issue => $code) {
            $mmcIssueDatas[] = array(
                'user_id' => $user['user_id'],
                'issue' => $issue,
                'code' => $code,
                'belong_date' => date('Y-m-d'),
            );
        }
        if (!issues::addIssueMMCs($mmcIssueDatas)) {
            throw new exception2('保存奖期出错', 3430);
        }

        //看来前面的操作都顺利，提交事务:)
        if (!$GLOBALS['db']->commit()) {
            throw new exception2('数据错误', 3160);
        }

        //需要返回每期开奖号码及奖金给前台
        $result = array();
        foreach ($mmcTotalPrizes as $issue => $v) {
            $tmp = array();
            $tmp['issue'] = $issue;
            $tmp['prize'] = $v['package_prize'];
            $tmp['opencode'] = $mmcOpencodes[$issue];

            $result[] = $tmp;
        }

        return $result;
    }

    /**
     * author snow 对投注时,获取的返点数据进行按代理级别进行排序.
     * @param $userIds
     * @param $rebates
     * @return array
     */
    private static function _sortRebate($userIds, $rebates)
    {

        /***************** snow  获取用户的层级,**************************/
        $idStr = implode(',', $userIds);
        $sql =<<<SQL
SELECT user_id,`level` FROM users WHERE user_id IN ({$idStr}) ORDER BY `level`
SQL;
        //>>对返点进行排序 ,按代理级别进行排序.
        $userLevel = $GLOBALS['db']->getAll($sql, [], 'user_id');
        $newRebates = [];
        foreach ($userLevel as $key => $val)
        {
            if (isset($rebates[$key])) {
                $newRebates[$key] = $rebates[$key];
            }
        }
        return $newRebates;
        /***************** snow  获取用户的层级,**************************/
    }

}
