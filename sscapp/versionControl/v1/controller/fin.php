<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class finController extends sscappController
{
    const KEY = '4AEDD405E0A893D495BD8F02358A0D01';

    //方法概览
    public $titles = array(
        'pay' => '充值页面展示数据',
        'orderList' => '账变信息',
        'bindCard' => '绑定银行卡',
        'withdraw' => '提现',
        'tranMoney' => '奖金转移',
        'withdrawIpChk' => '提现ip检测',
        'depositList' => '团队充值明细',
        'withdrawList' => '团队提现明细',
    );

    public function init()
    {
        parent::init(parent::INIT_SESSION);
    }

    /********************************充值部分**************************************/
    /**
     * 充值展示支付方式
     */
    public function pay()
    {
        list($user_id, $user) = $this->chkUser(0);
        $type = $this->request->getGet('type', 'intval', 1);
        if ($type == 1) $use_place = 4;
        elseif ($type == 2) $use_place = 2;
        else $this->showMsg(6003, mobileErrorCode::REQUEST_PARAMS_ERROR);
        //充值界面
        $cardDepositGroup = (new cardDepositGroup())
            ->field('cdg_id,group_name,fee_rate')
            ->where(['status' => 1])
            ->order('sort ASC')
            ->index(true)
            ->select();
        if (!empty($cardDepositGroup)) {
            foreach ($cardDepositGroup as $key => &$val) {
                if (!isset($val['cdg_id'])) $this->showMsg(4004, mobileErrorCode::DATA_EXCEPTION);
                if ($val['cdg_id'] == 1) {
                    $bankPay = $this->bankPay($use_place, $user);
                } else {
                    $bankPay = $this->deposit($val['cdg_id'], $use_place);
                }
                if (empty($bankPay) || empty($bankPay['cardList'])) unset($cardDepositGroup[$key]);
                else $val['child_cards'] = !empty($bankPay) ? $bankPay : '';
            }
            unset($val);
            if (empty($cardDepositGroup)) $this->showMsg(3001, '展示充值页面失败,缺少支付方式!');
            $appSetting = M('appSetting')->find();
            $warm_prompt = isset($appSetting['warm_prompt']) ? $appSetting['warm_prompt'] : '';
            $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['show_data' => array_values($cardDepositGroup), 'warm_prompt' => $warm_prompt]);
        }
        $this->showMsg(3001, mobileErrorCode::PAY_SHOW_ERROR);
    }

    /**
     * 银行支付展示数据
     * @param use_place
     * @param user
     * @return mixed
     */
    public function bankPay($use_place, $user)
    {
        /**推广阶段暂时对所有人员开放工行start**/
        $cards = cards::getCompanyPayCards($user['ref_group_id']);
        if ($cards) {
            foreach ($cards as $k => &$v) {
                if (empty($v['use_place']) || ($v['use_place'] & $use_place) != $use_place) {
                    unset($cards[$k]);
                    continue;
                }
                $v['bank_name'] = $GLOBALS['cfg']['bankList'][$v['bank_id']];
                $v['postscript'] = 'ID' . $user['user_id'] . '（转账时请复制此附言，方便客服查询确认）';
            }
            unset($v);
        }
        if (!empty($cards)) {
            $cards = array_values($cards);
        }
        return ['cardList' => $cards];
    }

    /**
     * 支付展示数据
     * @param $usage
     * @return array
     */
    public function deposit($usage, $use_place)
    {
        $min_deposit_limit = config::getConfig('min_deposit_limit');
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') == false ? 'http' : 'https';
        $domain = $protocol . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/';
        $user = users::getItem($GLOBALS['SESSION']['user_id']);
        $select = 'card_id,bank_id,`usage`,not_integer,not_zero,discount,pay_small_input,pay_max_input,card_name,card_num,balance,balance_limit,day_limit,shop_url,netway,login_name,call_back_url,return_url,direct_display,remark,obj_name,use_place';
        $cards = cards::getItemsC(1, 0, $usage, '', 2, cards::ORDER_BY_SORT, $user['ref_group_id'], $select);
        $cardList = '';
        $tempCardList = array();
        foreach ($cards as $value) {
            if ($value['bank_id'] != 254) {
                if (!array_key_exists($value['bank_id'], $tempCardList)) {
                    $tempCardList[$value['bank_id']] = array();
                }

                array_push($tempCardList[$value['bank_id']], $value);
            }
        }
        $cardSeq = 1;
        foreach ($tempCardList as $value) {
            if (!empty($value)) {
                $card = $this->payCardsEncodeNew($user, 'receivePayResult', cards::choseCardToSaveNew($value, $domain), true);
                if (isset($card['card_id']) && !empty($card['card_id'])) {
                    if (!empty($card['use_place']) && ($card['use_place'] & $use_place) == $use_place) {
                        $cardList[$card['card_id']]['card_id'] = isset($card['card_id']) ? $card['card_id'] : '';
                        $login_name = isset($card['login_name']) ? $card['login_name'] : '';
//                        $cardList[$card['card_id']]['login_name'] = $login_name . $cardSeq++;
                        $cardList[$card['card_id']]['login_name'] = $login_name;
                        $cardList[$card['card_id']]['bank_id'] = isset($card['bank_id']) ? $card['bank_id'] : '';

                        $cardList[$card['card_id']]['usage'] = isset($card['usage']) ? $card['usage'] : ''; //1:银行卡收款 2:线上支付 3:幸运支付 4;微信收款 5:支付宝收款 6:优惠彩金
                        $cardList[$card['card_id']]['not_integer'] = isset($card['not_integer']) ? $card['not_integer'] : '';//是否可以存整数金额
                        $cardList[$card['card_id']]['not_zero'] = isset($card['not_zero']) ? $card['not_zero'] : '';//尾数是否可以为 0
                        $cardList[$card['card_id']]['discount'] = isset($card['discount']) ? $card['discount'] : '';//折扣
                        $cardList[$card['card_id']]['pay_small_input'] = isset($card['pay_small_input']) ? $card['pay_small_input'] : '';//每笔支付最小额度
                        $cardList[$card['card_id']]['pay_max_input'] = isset($card['pay_max_input']) ? $card['pay_max_input'] : ''; //每笔支付最大额度
                        $cardList[$card['card_id']]['obj_name'] = isset($card['obj_name']) ? $card['obj_name'] : '';//每支付类名/每一个第三方支付一个名字,不能重复.同一个第三方的不同支付类型用同一个支付类名
                        $cardList[$card['card_id']]['use_place'] = isset($card['use_place']) ? $card['use_place'] : '';//使用地方: 1 web 2 wap 4app使用与算法后存储 5 app+web 6 app+wap 7app+web+wap

                        $cardList[$card['card_id']]['codes'] = isset($card['codes']) ? $card['codes'] : '';
                        $cardList[$card['card_id']]['requestURI'] = isset($card['requestURI']) ? $card['requestURI'] : '';
                        $cardList[$card['card_id']]['shop_url'] = isset($card['shop_url']) ? $card['shop_url'] : '';
                        $cardList[$card['card_id']]['call_back_url'] = isset($card['call_back_url']) ? $card['call_back_url'] : '';
                        $cardList[$card['card_id']]['return_url'] = isset($card['return_url']) ? $card['return_url'] : '';
                        $cardList[$card['card_id']]['netway'] = isset($card['netway']) ? $card['netway'] : '';
                        $cardList[$card['card_id']]['remark'] = isset($card['remark']) ? $card['remark'] : '';
                    }
                }
            }
        }
        $hash = generateEnPwd($user['username'] . '_' . $user['user_id'] . '_' . $user['user_id'] . '_' . $user['username'] . '_' . date('Ymd'));
        $payTimeOut = config::getConfig('pay_time_out', 60);
        $time = time() + $payTimeOut;
        $hash = substr($time, 0, 5) . $hash . substr($time, 5, 5);
        $data = [];
        $data['usage'] = $usage;
        $data['hash'] = $hash;
        $data['min_deposit_limit'] = $min_deposit_limit;
        $data['cardList'] = is_array($cardList) ? array_values($cardList) : '';
        return $data;
    }

    private function payCardsEncodeNew($user, $callBack, $card, $isMobile = false)
    {
        $card['card_id'] = isset($card['card_id']) ? authcode($card['card_id'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
        $card['bank_id'] = isset($card['bank_id']) ? authcode($card['bank_id'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
//        $card['netway'] =  isset($card['netway']) ? authcode($card['netway'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
        $card['codes'] = authcode(substr($user['username'], -5) . substr($user['username'], 0, 1) . $user['user_id'], 'ENCODE', 'a6sbe!x4^5d_ghd');
        $card['requestURI'] = authcode(url('fin', $callBack), 'ENCODE', 'gs4fj@5f!sda*dfuf');
        $card['call_back_url'] = isset($card['call_back_url']) ? authcode($card['call_back_url'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
        $card['return_url'] = isset($card['return_url']) ? authcode($card['return_url'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';

        if (isset($card['direct_display']) && $card['direct_display'] == 1 && $isMobile) {
            $card['shop_url'] = isset($card['shop_url']) ? authcode($card['shop_url'], 'ENCODE', 'a6sbe!x4^5d_ghd') : '';
        }

        return $card;
    }

    /********************************团队账变处理*************************************/
    public function teamOrderList()
    {
        list($start_time, $end_time) = $this->searchDate2(1, 3);
        $include_childs = $this->request->getGet('user_id', 'trim') != $GLOBALS['SESSION']['user_id'] ? 0 : 1;
        $showDatas = $this->_orderList($start_time, $end_time, $include_childs);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $showDatas);
    }
    /********************************个人账变处理*************************************/
    /**
     * 账变信息
     */
    public function orderList()
    {
        list($startDate, $endDate) = $this->searchDate2(1, 3);
        $showDatas = $this->_orderList($startDate, $endDate, 0);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $showDatas);
    }

    private function _handle_show_time()
    {
        $endDate = date('Y-m-d 23:59:59');
        $startDate = date("Y-m-d 00:00:00", strtotime("$endDate -1 month +1 day"));
        return [$startDate, $endDate];
    }

    /**
     * 账变数据获取及处理结构
     * @param $startDate
     * @param $endDate
     * @param $include_childs
     * @return array
     */
    private function _orderList($startDate, $endDate, $include_childs)
    {
        list($user_id, $user) = $this->chkUserAndChidId(0);
        list($orderType, $lotteryId, $limit, $username) = $this->_getOrderlistGetData();
        $trafficInfo = orders::getTrafficInfo($lotteryId, '', $orderType, $user_id, $include_childs, 0, 0, $startDate, $endDate);
        if (empty($trafficInfo)) return $this->_returnOrderList();
        list($page, $totalPages, $totals, $startPos) = $this->getPageList($trafficInfo['count'], $limit);
        $startPos = ($page - 1) * $limit;
        $orders = orders::getItems($lotteryId, '', $orderType, $user_id, $include_childs, 0, 0, $startDate, $endDate, $startPos, $limit);
        if (empty($orders)) return $this->_returnOrderList();
        $orders = $this->_handleWrap($orders);
        $lotteries = lottery::getItemsNew(['lottery_id', 'cname']);
        $showDatas = $this->_handleOrders($orders, $lotteries);
        $show_data = $this->_returnOrderList($page, $totalPages, $trafficInfo, $showDatas, $limit);
        return $show_data;
    }

    /**
     * 获取账变接口的请求数据
     * @return array
     */
    private function _getOrderlistGetData()
    {
        $orderTypes = self::$orderTypes;
        $orderType = $this->request->getGet('orderType', 'intval');
        if (empty($orderType)) $orderType = array_keys($orderTypes);
        $lotteryId = $this->request->getGet('lotteryId', 'intval', 0);
        $limit = $this->request->getGet('limit', 'intval', DEFAULT_PER_PAGE);
        if ($limit <= 0) $limit = DEFAULT_PER_PAGE;
        if ($limit > DEFAULT_MAX_PAGELIMIT) $limit = DEFAULT_MAX_PAGELIMIT;
        $username = $this->chkUsername($this->request->getGet('username', 'trim'));
        return [$orderType, $lotteryId, $limit, $username];
    }

    /**
     * 验证账变请求数据的页码和验证页码是否正确
     * @param $count
     * @return array|int|string
     */
    private function _handle_page($count = 0, $limit = DEFAULT_PER_PAGE)
    {
        $page = $this->request->getGet('page', 'intval', 1);
        if ($page < 1) $page = 1;
        $pages = !empty($count) ? $count / $limit : 1;
        $pages_max = (int)ceil($pages);
        return $page > $pages_max ? $pages_max : $page;
    }

    /**
     * 验证请求用户名 可不传
     * @param $username
     * @return mixed
     */
    private function chkUsername($username)
    {
        if (!empty($username) && $username != $GLOBALS['SESSION']['username']) {
            if (!$user = users::getItem($username)) $this->showMsg(7012, mobileErrorCode::USER_INVALID);
            if ($username != $GLOBALS['SESSION']['username'] && !in_array($GLOBALS['SESSION']['user_id'], explode(',', $user['parent_tree']))) $this->showMsg(7018, mobileErrorCode::CHILDREN_NOT_FOUND);
        } else {
            $username = $GLOBALS['SESSION']['username'];
        }
        return $username;
    }

    /**
     * 客户游戏相关的帐变的处理
     * @param $orders
     * @return mixed
     */
    private function _handleWrap($orders)
    {
        foreach ($orders as $k => $v) {
            $business = orders::$business[$v['type']];
            if ($v['business_id'] > 0 && isset($business['key_id']) && $business['key_id'] == 'wrap_id') {
                $orders[$k]['wrap_id'] = projects::wrapId($v['business_id'], $v['issue'], $v['lottery_id']);
            }
        }
        return $orders;
    }

    /**
     * 对返回的orderlist数据做返回格式处理
     * @param $orders
     * @param $lotteries
     * @return array|string
     */
    private function _handleOrders($orders, $lotteries)
    {
        $orderTypes = self::$orderTypes;
        if ($GLOBALS['SESSION']['level'] == 100) unset($orderTypes[212], $orderTypes[302], $orderTypes[501], $orderTypes[502]);
        $showDatas = '';
        if (!empty($orders)) {
            $showDatas = [];
            foreach ($orders as $v) {
                $data = [];
                $data['user_id'] = $v['from_user_id'];
                $data['create_time'] = $v['create_time'];
                $data['type'] = $orderTypes[$v['type']];
                $data['amount'] = $v['amount'];
                $info = [];
                $info['username'] = $v['from_username'];
                $info['ordernum'] = isset($v['wrap_id']) && !empty($v['wrap_id']) ? $v['wrap_id'] : '';
                $info['gamename'] = !empty($lotteries[$v['lottery_id']]) && isset($lotteries[$v['lottery_id']]['cname']) ? $lotteries[$v['lottery_id']]['cname'] : '';
                $info['issue'] = $v['issue'];
                $info['balance'] = $v['balance'];
                $data['info'] = $info;
                $showDatas[] = $data;
            }
        }
        return $showDatas;
    }

    /**
     * 为null时返回数据
     * @return array
     */
    private function _handle_orderlist_return_null()
    {
        return ['page' => 1, 'totalPage' => 1, 'count' => 0, 'total_amount' => 0, 'showDatas' => ''];
    }

    /**
     * 返回数据
     * @param string $page
     * @param string $trafficInfo
     * @param string $showDatas
     * @return array
     */
    private function _returnOrderList($page = 1, $totalPages = 1, $trafficInfo = '', $showDatas = '', $limit = DEFAULT_PER_PAGE)
    {
        if (!empty($page) && is_int($page) && is_array($trafficInfo) && !empty($trafficInfo['count']) && !empty($trafficInfo['total_amount']) && !empty($showDatas)) {
            return [
                'page' => $page,
                'totalPage' => $totalPages,
                'count' => $trafficInfo['count'],
                'total_amount' => $trafficInfo['total_amount'],
                'showDatas' => $showDatas
            ];
        } else {
            return $this->_handle_orderlist_return_null();
        }
    }

    /********************************绑定银行卡*************************************/
    public function bindCard()
    {
        list($userId, $user) = $this->chkUser();
        if ($this->getIsPostRequest()) {

            /****************** author snow 添加支行名称*****************************************************/
            list($bind_bank_id, $bind_bank_username, $bind_card_num, $secpwd,$branch_name) = $this->_getBankCardPost();
            $userBindCards = userBindCards::getItemByUid($userId, ['in', [1, 2]], 'bind_card_id');
            if (!empty($userBindCards)) $this->showMsg(7020, '已经绑定银行卡,换绑请联系客服');
            is_numeric($result = userBindCards::newBindCard($userId, $bind_bank_id, $bind_card_num, $bind_bank_username, $secpwd,$branch_name)) ? $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['bind_card_id' => $result]) : $this->showMsg(7020, $result);
            /****************** author snow 添加支行名称*****************************************************/
           }
        //得到支持银行列表  去掉支付宝 ，财付通
        $bankList = $this->_handle_bankList($GLOBALS['cfg']['withdrawBankList']);
        $returnDatas = $this->_handle_return_bankList($bankList);
        $this->logdumpNew('app.fin', $returnDatas);
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, ['banklist' => $returnDatas]);
    }

    private function _getBankCardPost()
    {
        $post = [];
        $post[] = $this->request->getPost('bind_bank_id', 'intval');//>>author snow 修改回原来的传入int类型
        $post[] = $this->request->getPost('bind_bank_username', 'string');
        $post[] = $this->request->getPost('bind_card_num', 'string');
        $post[] = $this->request->getPost('secpwd', 'string');
        /****************** author snow 添加支行名称*****************************************************/
        //        $province = $this->request->getPost('province', 'trim');
        //        $city = $this->request->getPost('city', 'trim');
        $post[] = $this->request->getPost('branch_name', 'trim');
        $this->logdumpNew('app.fin', $this->request->getPostAsArray());
        $this->logdumpNew('app.fin', $this->request);
        $this->logdumpNew('app.fin', $post);
        /****************** author snow 添加支行名称*****************************************************/
        return $post;
    }

    private function _handle_bankList($bankList)
    {
        if (empty($bankList)) $this->showMsg(7020, '配置数据读取失败,请稍后再试');
        if (isset($bankList[101])) unset($bankList[101]);
        if (isset($bankList[102])) unset($bankList[102]);
        return $bankList;
    }

    private function _handle_return_bankList($bankList)
    {
        $returnDatas = [];
        foreach ($bankList as $key => $val) {
            $list = [];
            $list['bank_id'] = $key;
            $list['bank_name'] = $val;
            $returnDatas[] = $list;
        }
        return $returnDatas;
    }


    /****************** snow dcsite 总代下的用户不用出款 start***************************/
    /**
     * author snow
     * 验证总代dcsite下及自己不能进行提款操作
     * @param $flag
     * @param $user_id int 用户id
     */
    private function _handleDcsite($user_id)
    {
        $sql = <<<SQL
SELECT a.user_id,b.username AS top_name FROM  users AS a,users AS b WHERE a.user_id = {$user_id} AND a.top_id = b.user_id AND b.username = 'dcsite'
SQL;
        if ($GLOBALS['db']->getRow($sql)) {
            //>>如果存在,说明属于dcsite 总代名下 ,不能进行提款
            $this->showMsg(7002, '属于dcsite 总代名下用户 ,不能进行提款');
            exit;
        }
    }

    /****************** snow dcsite 总代下的用户不用出款 end  ***************************/
    /********************************提现*************************************/
    /**
     * 申请提现
     * @author nyjah 2016年1月18日
     * @param
     * @return json
     */
    public function withdraw()
    {
        list($user_id, $user) = $this->chkUser();

        /****************** snow dcsite 总代下的用户不用出款 start***************************/
        //>> 兼容以前的代码 .调用方法判断
        if (!$this->getIsPostRequest()) {
            $this->_handleDcsite($user_id);
        }

        /****************** snow dcsite 总代下的用户不用出款 end  ***************************/
        if (!$user['secpwd']) $this->showMsg(7008, mobileErrorCode::USER_NOT_MONEY_PWD);
        /*********************** author snow 添加获取支行名称***********************************************/
        if (!$userBindCard = userBindCards::getItemByUid($user_id, 1, 'bind_card_id,bank_id,bank_username,card_num,branch')) $this->showMsg(7009, mobileErrorCode::USER_NOT_BIND_CARD);
        /*********************** author snow 添加获取支行名称***********************************************/
        if ($this->getIsPostRequest()) {

            /****************** snow dcsite 总代下的用户不用出款 start***************************/
            //>> 兼容以前的代码 .调用方法判断

            $this->_handleDcsite($user_id);

            /****************** snow dcsite 总代下的用户不用出款 end  ***************************/

            list($bind_card_id, $withdraw_bank_id, $withdraw_amount, $secpwd) = $this->_chkWithdrawPost($userBindCard);
            $this->_chkWithdrawSize();//每日提款次数限制
            //判断提交的银行卡ID 是否存在在用户 绑定的ID里
            list($withdraw_card_num, $province, $city, $branch_name, $card_name) = $this->_getUserBankInfo($bind_card_id, $user_id);
            //提交提款请求
            $result = withdraws::_new_applyWithdraw($user_id, $withdraw_bank_id, $withdraw_card_num, $province, $city, $branch_name, $withdraw_amount, $secpwd, $card_name);
            if (!is_numeric($result)) $this->showMsg(7007, $result);
            if (!$user['is_test']) $this->showMsg(0, "添加提款成功，请等待审核", ['withdraw_id' => $result]);
            withdraws::payTester($result);
            $this->showMsg(0, "提款成功[测试帐号]", ['withdraw_id' => $result]);
        }
//        if(!$user['real_name'])用户真实名字不能为空
        $subLen = mb_strlen($userBindCard['bank_username'], 'utf-8') - 1;
        $userBindCard['bank_username'] = '*' . mb_substr($userBindCard['bank_username'], 1, $subLen, 'utf-8');
        $card_num = $userBindCard['card_num'];
        $userBindCard['card_num'] = substr($card_num, 0, 4) . str_repeat('*', strlen($card_num) - 8) . substr($card_num, -4, 4);

        $returnDatas = !empty($userBindCard) ? $userBindCard : ['bank_username' => '', 'card_num' => ''];
        $returnDatas['bankName'] = isset($GLOBALS['cfg']['withdrawBankList'][$returnDatas['bank_id']]) ? $GLOBALS['cfg']['withdrawBankList'][$returnDatas['bank_id']] : '';
        $returnDatas['amount'] = !empty($user['balance']) ? $user['balance'] : '0.00';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $returnDatas);
    }

    private function _todayStarEnd()
    {
        return [date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")];
    }

    private function _getWithdrawPost()
    {
        $post = [];
        $post[] = $this->request->getPost('bind_card_id', 'intval');
        $post[] = $this->request->getPost('bank_id', 'intval');
        $post[] = $this->request->getPost('withdraw_amount', 'floatval');
        $post[] = $this->request->getPost('secpwd', 'string');
        return $post;
    }

    private function _chkWithdrawPost($userBindCard)
    {
        list($bind_card_id, $withdraw_bank_id, $withdraw_amount, $secpwd) = $this->_getWithdrawPost();
        if (!$secpwd) $this->showMsg(7007, "请输入资金密码");
        if (strlen($secpwd) < 6 || strlen($secpwd) > 15 || preg_match('`^\d+$`', $secpwd) || preg_match('`^[a-zA-Z]+$`', $secpwd)) $this->showMsg(7007, "资金密码不对");
        if (!$withdraw_bank_id) $this->showMsg(7007, "请选择提款银行");
        if ($withdraw_amount <= 0) $this->showMsg(7007, "请填写正确的金额");
        if ($withdraw_bank_id != $userBindCard['bank_id']) $this->showMsg(7007, "非法提交的银行卡数据");
        if (empty($GLOBALS['cfg']['withdrawBankList'][$userBindCard['bank_id']])) $this->showMsg(7007, '提现银行不存在');
        return [$bind_card_id, $withdraw_bank_id, $withdraw_amount, $secpwd];
    }

    private function _chkWithdrawSize()
    {
        //取得提款记录
        list($start_date, $end_date) = $this->_todayStarEnd();
        //获取今日提款笔数,金额
        $stats = withdraws::getTrafficInfo($GLOBALS['SESSION']['username'], 0, 0, 8, 0, 0, 0, $start_date, $end_date);
        //每日提款次数限制
        if ($stats['count'] >= config::getConfig('day_withdraw_num', 13)) $this->showMsg(7010, mobileErrorCode::USER_WITHDRAW_LIMIT);
    }

    private function _getUserBankInfo($bind_card_id, $user_id)
    {
        if (!$userBankInfo = userBindCards::getItem($bind_card_id, $user_id, 1, 'card_num,province,city,branch,bank_username')) $this->showMsg(7007, "无效的卡号");
        if (empty($userBankInfo['card_num'])) $this->showMsg(7007, '绑定银行卡卡号不存在');
        return array_values($userBankInfo);
    }

    /**
     * 提现境外ip检测
     */
    public function withdrawIpChk()
    {
        list($uid, $user) = $this->chkUser();
        $withdraw_id = $this->request->getPost('withdraw_id', 'intval');
        if (empty($withdraw_id)) $this->showMsg(8002, mobileErrorCode::SYS_GET_PARAM_ERR);
        if (withdraws::ipIsChina() === false) {
            if (!empty($withdraw_id) && $withdraw_id > 0 && !empty($uid)) {
                p('进入');
                $m_model = M('withdraws');
                $withdraw = $m_model
                    ->field('amount,user_id')
                    ->where(['withdraw_id' => $withdraw_id])
                    ->find();
                $u_model = M('users');
                $user = $u_model
                    ->field('top_id,user_id')
                    ->where(['user_id' => $uid])
                    ->find();
                if (!empty($withdraw) && !empty($user) && $withdraw['user_id'] == $uid) {
                    $withdraw_amount = $withdraw['amount'];
                    $ipNotes = array(
                        'withdraw_id' => $withdraw_id,
                        'user_id' => $user['user_id'],
                        'top_id' => $user['top_id'],
                        'amount' => $withdraw_amount,
                        'withdraw_ip' => $GLOBALS['REQUEST']['client_ip'],
                        'create_time' => date('Y-m-d H:i:s'),
                    );
                    if (withdrawIps::addItem($ipNotes)) {
                        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
                    } else {
                        log2file('ipException.log', $ipNotes);
                        $this->showMsg(7021, mobileErrorCode::WITHDRAWIP_ADD_ERROR);
                    }
                }
            }
            $this->showMsg(7021, '数据异常');
        }
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS);
    }
    //===============================团队管理====================================//
    /******************************团队充值明细***********************************/
    /**
     * author snow 修改取首存人数时键名 用错,导致数据为0
     * 修改缓存时间
     * @param $user_id
     * @param $start_time
     * @param $end_time
     * @return array|bool|mixed
     */
    private function count_deposit($user_id,$start_time,$end_time)
    {
        return $this->cutRedisDatabase(function()use($user_id,$start_time,$end_time)
        {
            $cacheExpire = $end_time < date('Y-m-d 00:00:00') ? CACHE_EXPIRE_LONG : 600;
            //>>设置key
            $key = 'deposit_count_'.$user_id.$start_time.$end_time;
            return redisGet($key, function () use($user_id,$start_time,$end_time){

                //>>获取时间区间内的首存人数
                $totals_first_deposits = deposits::getFirstDepositsExclude($user_id, 1, 0,$start_time,$end_time);
                //>>获取时间区间内的总存款人数
                $totals_all_deposits   = deposits::getTotalInfo($user_id , $start_time, $end_time);
                //>>组合数据返回
                return  [
                    'first' => !empty($totals_first_deposits['total_count']) ? $totals_first_deposits['total_count'] : 0,
                    'all'   => !empty($totals_all_deposits['total_count']) ? $totals_all_deposits['total_count'] : 0,
                ];
            },$cacheExpire);
        });
    }
    /**
     * 用户存款记录
     */
    public function depositList()
    {
        list($user_id, $user) = $this->chkUserAndChidId(0);
        list($start_time, $end_time) = $this->searchDate2(1, 3);

        $include_childs = $this->request->getGet('include_childs', 'intval', 1);
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        $limit = $this->request->getGet('limit', 'intval', DEFAULT_PER_PAGE);
        if ($limit <= 0) $limit = DEFAULT_PER_PAGE;
        if ($limit > DEFAULT_MAX_PAGELIMIT) $limit = DEFAULT_MAX_PAGELIMIT;
        $trafficInfo = deposits::getTrafficInfo($user_id, $include_childs, -1, -1, 8, 0, 0, 0, 0, 0, '', $start_time, $end_time);//存款记录总数
        $count = $trafficInfo['count'];
        $page = $this->_handle_page($count, $limit);
        $startPos = ($page - 1) * $limit;
        $orderby = 'deposit_id DESC';
        if ($sortKey) $orderby = (in_array($sortKey, ['user_id', 'username', 'level']) ? 'b.' : 'a.') . $sortKey . ($sortDirection == 1 ? ' ASC' : ' DESC');
        $deposits = deposits::new_getItems($user_id, $include_childs, 8, $start_time, $end_time, $startPos, $limit, $orderby);//存款记录
        $tradeTypes = [1 => '网转', 2 => 'ATM有卡转账', 3 => '自助终端转账', 4 => '手机网转', 5 => 'ATM无卡现存', 6 => '柜台汇款', 7 => '跨行汇款'];
        $status = [0 => '未处理', 1 => '已受理', 2 => '已审核', 3 => '机器正在受理', 4 => '需要人工干预', 8 => '已成功', 9 => '因故取消'];
        $totalAmount = $realAmount = 0;
        foreach ($deposits as &$deposit) {
            $totalAmount += $deposit['amount'];
            if ($deposit['status'] == 8) $realAmount += $deposit['amount'];
            $deposit['status_str'] = $status[$deposit['status']];
            $deposit['tradeType_str'] = $tradeTypes[$deposit['trade_type']];
            $deposit['wrap_id'] = deposits::wrapId($deposit['deposit_id']);
            unset($deposit['trade_type'], $deposit['status']);
        }
        unset($deposit);

        $totalPages = ceil($trafficInfo['count'] / $limit);
        if ($totalPages == 0) $totalPages = 1;
        $datas['page'] = $page;
        $datas['totalPages'] = $totalPages;
        $datas['count'] = $trafficInfo['count'];
        /********************** author snow 修改获取首存人数与存款总人数**************************************/
        $s_today=date('Y-m-d').' 00:00:00';
        $e_today=date('Y-m-d').' 23:59:59';
        $deposit_count = [
            'all'   => $this->count_deposit($user_id,$start_time,$end_time),
            'today' => $this->count_deposit($user_id,$s_today,$e_today),
        ];
        /********************** author snow 修改获取首存人数与存款总人数**************************************/
        $datas['deposit_count'] = $deposit_count;
        $datas['all_count'] = [
            'totals' => $trafficInfo,
            'page' => [
                "count" => count($deposits),
                "page_amount" => $totalAmount,
                "page_real_amount" => $realAmount
            ]
        ];
        $datas['showDatas'] = !empty($deposits) ? $deposits : '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $datas);
    }

    /**
     * 日期查找范围
     * @param $start_time
     * @param $end_time
     * @param $number
     * @param $type 1=>day, 2=>week, 3=>month
     * @param $delay_day 延迟天数
     */
    protected function searchDate(&$start_time, &$end_time, $number = 1, $type = 2, $delay_day = 0)
    {
        $type = $type == 1 ? ' days' : ($type == 2 ? ' weeks' : ' months');
        $curTime = date('Y-m-d', strtotime('-' . $delay_day . ' days'));
        $minDate = date('Y-m-d', strtotime('-' . $number . $type . '+1 days -' . $delay_day . ' days'));

        if ($endDate = $this->request->getGet('endDate', 'trim', $curTime)) {
            if ($endDate > $curTime) $endDate = $curTime;
            elseif ($endDate < $minDate) $endDate = $minDate;
        }

        if ($startDate = $this->request->getGet('startDate', 'trim', $curTime)) {
            if ($startDate > $endDate) $startDate = $endDate;
            elseif ($startDate < $minDate) $startDate = $minDate;
            elseif ($startDate > $curTime) $startDate = $curTime;
        }

        $start_time = $startDate . ' 00:00:00';
        $end_time = $endDate . ' 23:59:59';
        return [$start_time, $end_time];
    }

    /******************************团队提现明细***********************************/
    /**
     * 用户取款记录
     */
    public function withdrawList()
    {
        list($user_id, $user) = $this->chkUserAndChidId(0);
        list($start_time, $end_time) = $this->searchDate2(1, 3);
        $include_childs = $this->request->getGet('include_childs', 'intval', 1);
        $status = $this->request->getGet('status', 'intval', -1);
        $sortKey = $this->request->getGet('sortKey', 'trim');
        $sortDirection = $this->request->getGet('sortDirection', 'intval', '-1|1');
        $limit = $this->request->getGet('limit', 'intval', DEFAULT_PER_PAGE);
        if ($limit <= 0) $limit = DEFAULT_PER_PAGE;
        if ($limit > DEFAULT_MAX_PAGELIMIT) $limit = DEFAULT_MAX_PAGELIMIT;
        if ($status != -1) $status = in_array($status, [8, 9]) ? $status : [0, 1, 2, 3, 4];

        $trafficInfo = withdraws::getTrafficInfo($user_id, $include_childs, -1, $status, 0, 0, 0, '', '', $start_time, $end_time);
        $count = $trafficInfo['count'];

        list($page, $totalPages, $totals, $startPos) = $this->getPageList($count, $limit);
        $startPos = ($page - 1) * $limit;
        $orderby = 'a.withdraw_id DESC';
        if ($sortKey) $orderby = (in_array($sortKey, ['user_id', 'username', 'level']) ? 'b.' : 'a.') . $sortKey . ($sortDirection == 1 ? ' ASC' : ' DESC');
        $withdraws = withdraws::new_getItems($user_id, $include_childs, $status, $startPos, DEFAULT_PER_PAGE, $start_time, $end_time);
        $withdrawStatus = [7 => '正在处理', 8 => '结算成功', 9 => '结算失败'];
        //0未处理 1已受理 2已审核 3交给机器受理 4机器正在受理 8已成功 9因故取消
        $totalAmount = $realAmount = 0;

        foreach ($withdraws as $k => $v) {

            $totalAmount += $v['amount'];
            $withdraws[$k]['wrap_id'] = withdraws::wrapId($v['withdraw_id']);
            if ($v['status'] < 8) {
                $withdraws[$k]['withdrawStatus'] = '正在处理';
            } elseif ($v['status'] == 8) {
                $realAmount += $v['amount'];
                $withdraws[$k]['withdrawStatus'] = '结算成功';
            } elseif ($v['status'] == 9) {
                $withdraws[$k]['withdrawStatus'] = '结算失败';
            } else {
                $withdraws[$k]['withdrawStatus'] = '正在处理';
            }
            unset($withdraws[$k]['status']);
        }
        $datas['page'] = $page;
        $datas['totalPages'] = $totalPages;
        $datas['count'] = $trafficInfo['count'];
        $datas['all_count'] = [
            'totals' => $trafficInfo,
            'page' => [
                "count" => count($withdraws),
                "page_amount" => $totalAmount,
                "page_real_amount" => $realAmount
            ]
        ];
        $datas['showDatas'] = !empty($withdraws) ? $withdraws : '';
        $this->showMsg(0, mobileErrorCode::RETURN_SUCCESS, $datas);
    }

    /**
     * author snow
     * 奖金操作
     */
    public function tranMOney()
    {
        $result = $this->_tranMoney();
        $this->showMsg($result['errno'], $result['errstr'], isset($result['balances']) ? $result['balances'] : []);
    }

    /**
     * author snow
     * 资金转移
     */
    private function _tranMoney()
    {
        if (!$user = users::getItem($GLOBALS['SESSION']['user_id'])) {
            return ['errno' => 9000, 'errstr' => '非法请求，该用户不存在或已被冻结'];
        }
        if (!$op = $this->request->getPost('op', 'trim')) {
            return ['errno' => 9000, 'errstr' => '非法请求，没有操作类型'];
        }
        if ($user['is_test'] == 1) {
            return ['errno' => 90012, 'errstr' => '测试帐号不能够执行转帐'];
        }
        switch ($op) {
            case 'show':
                $balancePt = '未获取';
                $balances = array('0' => $user['balance'], '1' => $balancePt);
                return ['errno' => 0, 'errstr' => '', 'balances' => $balances];
                break;
            case 'tran':
                //>>进行奖金转入,转出操作
                return $this->_tranMoneyForTran($user);
                break;
            default:
                return ['errno' => 9000, 'errstr' => "非法请求，不支持的操作类型"];
                break;
        }
    }

    /**
     * author snow
     * 奖金转入 转出 操作
     * @param $user
     * @return array
     */
    private function _tranMoneyForTran($user)
    {

        if (!$user['secpwd']) {
            return ['errno' => 90001, 'errstr' => '您尚未设置资金密码'];
        }

        if (!is_numeric($tranFrom = $this->request->getPost('tranFrom', 'intval'))) {
            return ['errno' => 90002, 'errstr' => '请选择转出方'];
        }

        if (!is_numeric($tranTo = $this->request->getPost('tranTo', 'intval'))) {
            return ['errno' => 90004, 'errstr' => '请选择转入方'];
        }

        if (!is_numeric($tranAmount = $this->request->getPost('tranAmount', 'intval'))) {
            return ['errno' => 90005, 'errstr' => '请输入正确金额'];
        }

        if (!$tranPass = $this->request->getPost('tranPass', 'trim')) {
            return ['errno' => 90006, 'errstr' => '请输入资金密码'];
        }

        if ($user['secpwd'] != generateEnPwd($tranPass)) {
            return ['errno' => 90006, 'errstr' => '请输入正确的资金密码'];
        }

        if ($tranFrom == 0 && $tranTo == 1) {
            try {
                if (transfers::transferInMW($user['user_id'], $tranAmount)) {
                    return ['errno' => 0, 'errstr' => '转入成功'];
                } else {
                    return ['errno' => 90008, 'errstr' => '资金转移出错，请联系咨询相关客服'];
                }
            } catch (Exception $ex) {
                return ['errno' => 90010, 'errstr' => $ex->getMessage()];
            }
        } elseif ($tranFrom == 1 && $tranTo == 0) {
            try {
                if (transfers::transferOutMW($user['user_id'], $tranAmount)) {
                    return ['errno' => 0, 'errstr' => '转出成功'];
                } else {
                    return ['errno' => 90009, 'errstr' => '资金转移出错，请联系咨询相关客服'];
                }
            } catch (Exception $ex) {
                return ['errno' => 90011, 'errstr' => $ex->getMessage()];
            }
        } else {
            return ['errno' => 90007, 'errstr' => '非法的转入转出方'];
        }

    }

    public function receivePayResult()
    {
        echo payCallBack($this->request, 'PayError.log');
    }

}


