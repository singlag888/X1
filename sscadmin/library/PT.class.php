<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * PT接口：
 * 一。创建用户
 * https://kioskpublicapi.redhorse88.com/player/create/playername/jyztest001/password/a123456/adminname/JYZPLAYERS/kioskname/JYZPLAYERS/custom02/JYZPLAYERS/
 *
 * ********************************* 接口列表 *************************************
 * createPlayer($username, $password) ---- 创建会员
 * updatePlayer($username, $password)    ---- 改密码等资料
 * getPlayerInfo($username)   ---- 查询玩家信息
 * isPlayerOnline($username) ---- 查询玩家是否在线
 * transferIn($username, $amount, $ref_id) ---- 转入PT
 * transferOut($username, $amount, $ref_id)  ---- 从PT转出
 * ********************************************************************************

  一、登录
  发过去的数据：
  <?xml version="1.0"?>
  <request action="Login">
  <element>
  <website>LWIN999</properties>
  <username>7sv3</username>
  <uppername>7svbase</username>
  <username>123def</username>
  </element>
  </request>
  验证登录请求

  待完善

 */
class PT
{

    /**
     * 官方接口地址
     * @var <type>
     */
    private $interfaceURL = "https://kioskpublicapi.redhorse88.com";
    private $keyB = array(
        'custmo02' => 'JYZ',
        'user_prefix' => 'JYZ',
        'kioskname' => 'JYZPLAYERS',
        'kioskadminname' => 'JYZPLAYERS',
        'entity_key' => '53dd5ac6476b6a45b326863e6c8342ea36b4fe02f41cd1c965010d63e23884a1bee99b3f1dfc39ce7b5035d1f5ca83fa7118aa7a018556ecb4692c6ca20df87e',
    );
    protected $wget = NULL;

    const DIGITAL_PADDING_NUM = 6;  //生成element_id中的用户id用几位填充，一般6位即足够，能表示百万了，即便超过也不会出错的，只是长度增加了一位而已

    static $successCodes = array(
        '11000' => '转帐成功',
        '10003' => '转帐失败',
        '11000' => '重复转帐',
        '10002' => '余额不足',
        '10004' => 'key不得为空',
        '10005' => '额度检查错误',
        '10006' => '提款需为正整数或浮点',
    );

    public function __construct()
    {
        $this->wget = new wget();
        $this->wget->setHttpVersion('1.0')
                ->setConnection('Close')    // Keep-Alive 将非常慢
                ->setReferer('')
                ->setCookie('')
                //->setPostData($postData)  //用时再具体设置
                ->setContentType('application/x-www-form-urlencoded')
                ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)');
    }

    /**
     * 创建新玩家
     *
     * array (
      'result' =>
      array (
      'result' => 'New player has been created',
      'playername' => 'JYZTEST02',
      'password' => 'a123456',
      ),
      )
     *
     * array (
      'error' => 'The username you requested is already being used by another player.',
      'errorcode' => 19,
      )
     *
     * array (
      'error' => 'Player password is too short',
      'errorcode' => 50,
      )
     */
    public function createPlayer($username, $password)
    {
        $username = $this->keyB['user_prefix'] . $username;
        $url = $this->interfaceURL . "/player/create/playername/{$username}/password/{$password}/adminname/" . $this->keyB['kioskadminname'] .
                "/kioskname/" . $this->keyB['kioskname'] . "/custom02/" . $this->keyB['custmo02'] . "/";
        $result = $this->sslCall($url);
        logdump($result);
        $finalResult = PT::processError($result);
        if (isset($result['result']) && $result['result']['result'] == 'New player has been created') {
            $finalResult['username'] = $result['result']['playername'];
            $finalResult['password'] = $result['result']['password'];
        }
        else {
            return false;
        }
        return $finalResult;
    }

    /**
     * 修改玩家资料 https://baseurl/player/update/playername/{username}/password/{password}/{attr_name}/{attr_value}/../
     * 正常返回
     * array (
      'result' =>
      array (
      'result' => 'Player\'s information has been updated.',
      ),
      )
     * 错误返回
     * array (
      'error' => 'Entered the wrong request format',
      'errorcode' => 76,
      )
     */
    public function updatePlayer($username, $password)
    {
        $url = $this->interfaceURL . "/player/update/playername/{$username}/password/{$password}/";
        $result = $this->sslCall($url);
        var_dump($result);
//logdump($result);
        $finalResult = PT::processError($result);
        if (isset($result['result']) && $result['result']['result'] == 'Player\\\'s information has been updated.') {
            //nothing to do
        }

        return $finalResult;
    }

    /**
     * 查询玩家信息 最有用的字段是余额 https://baseurl/player/info/playername/{username}/
     *
     * array (
      'result' =>
      array (
      'ACCOUNTBUSINESSPHASE' => 'online',
      'ACCOUNTMIGRATED' => 'false',
      'ADDRESS' => 'NA',
      'ADVERTISER' => 'default71',
      'ADVERTISERTYPE' => 'Affiliate',
      'AGEVERIFICATIONSTATUS' => 'unknown',
      'BALANCE' => 0,
      'BANNERID' => '-',
      'BIRTHDATE' => '1950-01-01',
      'BONUSBALANCE' => 0,
      'CASINOCODE' => '1778',
      'CASINONAME' => 'luckystar88',
      'CASINONICKNAME' => 0,
      'CHECKIDDOCUMENT' => 'false',
      'CITY' => 'NA',
      'CLIENTSKIN' => 'luckystar88',
      'CLIENTTYPE' => 'casino',
      'CODE' => '12000821',
      'COMMENTS' => 'Created through Public API. ',
     *
     * array (
      'error' => 'Player does not exist',
      'errorcode' => 41,
      )
     */
    public function getPlayerInfo($username)
    {
        $url = $this->interfaceURL . "/player/info/playername/{$username}/";
        $result = $this->sslCall($url);
        $finalResult = PT::processError($result);
        if (isset($result['result']) && is_numeric($result['result']['BALANCE'])) {
            $finalResult['username'] = $result['result']['PLAYERNAME'];
            $finalResult['balance'] = $result['result']['BALANCE'];
        }

        return $finalResult;
    }

    /**
     * 玩家是否在线 https://baseurl/player/online/playername/{username}
     * array (
      'result' =>
      array (
      'result' => 0,
      ),
      )
     *
     * array (
      'error' => 'Player does not exist',
      'errorcode' => 41,
      )
     */
    public function isPlayerOnline($username)
    {
        $url = $this->interfaceURL . "/player/online/playername/{$username}/";
        $result = $this->sslCall($url);
        $finalResult = PT::processError($result);
        if (isset($result['result']) && is_numeric($result['result']['result'])) {
            $finalResult['is_online'] = $result['result']['result'];
        }

        return $finalResult;
    }

    /**
     * 转入PT https://baseurl/player/deposit/playername/{username}/amount/{amount}/adminname/{kioskadminname}/externaltranid/{externaltranid}
     * array (
      'result' =>
      array (
      'amount' => '5',
      'currentplayerbalance' => '110',
      'executiontime' => '199.573 ms',
      'externaltransactionid' => 'D20141205111818008473E',
      'instantcashtype' => 'local',
      'kiosktransactionid' => '136039543',
      'kiosktransactiontime' => '2014-12-05 11:18:15',
      'ptinternaltransactionid' => '925572575',
      'result' => 'Deposit OK',
      ),
      )
     *
     * 没指定amount返回错误
     * array (
      'error' => 'Amount not specified',
      'errorcode' => 13,
      )
     */
    public function transferIn($username, $amount, $ref_id)
    {
        $url = $this->interfaceURL . "/player/deposit/playername/{$username}/amount/{$amount}/adminname/{$this->keyB['kioskadminname']}/externaltranid/{$ref_id}/";
        $result = $this->sslCall($url);
        logdump($result);
        $finalResult = PT::processError($result);
        if (isset($result['result']) && $result['result']['result'] == 'Deposit OK') {
            $finalResult['new_balance'] = $result['result']['currentplayerbalance'];
            $finalResult['ref_id'] = $result['result']['externaltransactionid'];
            $finalResult['kiosk_trade_id'] = $result['result']['kiosktransactionid'];
        }
        else {
            return false;
        }

        return $finalResult;
    }

    /**
     * 从PT转出 https://baseurl/player/withdraw/playername/{username}/amount/{amount}/isForce/1/adminname/{kioskadminname}/externaltranid/{externaltranid}
     * array (
      'result' =>
      array (
      'amount' => '5',
      'currentplayerbalance' => '105',
      'executiontime' => '214.122 ms',
      'externaltransactionid' => 'W20141205110204005702E',
      'instantcashtype' => 'local',
      'kiosktransactionid' => '136038572',
      'kiosktransactiontime' => '2014-12-05 11:02:02',
      'ptinternaltransactionid' => '925567941',
      'result' => 'Withdraw OK',
      ),
      )
     *
     * array (
      'error' => 'Playername name not specified',
      'errorcode' => 10,
      )
     */
    public function transferOut($username, $amount, $ref_id)
    {
        $url = $this->interfaceURL . "/player/withdraw/playername/{$username}/amount/{$amount}/isForce/1/adminname/{$this->keyB['kioskadminname']}/externaltranid/{$ref_id}/";
        $result = $this->sslCall($url);
        logdump($result);
        $finalResult = PT::processError($result);
        if (isset($result['result']) && $result['result']['result'] == 'Withdraw OK') {
            $finalResult['new_balance'] = $result['result']['currentplayerbalance'];
            $finalResult['ref_id'] = $result['result']['externaltransactionid'];
            $finalResult['kiosk_trade_id'] = $result['result']['kiosktransactionid'];
        }
        else {
            return false;
        }

        return $finalResult;
    }

    /**
     * 登出游戏 https://baseurl/player/logout/playername/{username}
     */
    public function logoutPlayer($username)
    {
        $url = $this->interfaceURL . "/player/logout/playername/{$username}/";
        $result = $this->sslCall($url);

        return $result;
    }

    /**
     * 清除玩家登录失败记录 https://baseurl/player/resetFailedLogin/playername/{username}
     */
    public function resetFailedLoginPlayer($username)
    {
        $url = $this->interfaceURL . "/player/resetFailedLogin/playername/{$username}/";
        $result = $this->sslCall($url);

        return $result;
    }

    /**
     * 玩家投注额统计 https://baseurl/customreport/getdata/reportname/GameStats/startdate/2014-07-24%2000:00:00/enddate/2014-07-24%2023:59:59/gametype/both/reportby/playername/sortby/players/
     * 加 /playername/{username}/ 指定单一玩家
     *
     * array (
      'result' =>
      array (
      ),
      'pagination' =>
      array (
      'currentPage' => 1,
      'totalPages' => 1,
      'itemsPerPage' => 50,
      'totalCount' => 0,
      ),
      )
     * array (
      'error' => 'startdate is not valid YYYY-MM-DD date',
      'errorcode' => 76,
     */
    public function gameStats($username, $startDate, $endDate)
    {
        $startDate = str_replace(' ', '%20', $startDate);
        $endDate = str_replace(' ', '%20', $endDate);
        $url = $this->interfaceURL . "/customreport/getdata/reportname/GameStats/startdate/$startDate/enddate/$endDate/gametype/both/reportby/playername/sortby/players/";
        if ($username) {
            $url .= "playername/{$username}/";
        }
        $url.='page/1/perPage/10000/';
        $result = $this->sslCall($url);
        $finalResult = PT::processError($result);
        if (isset($result['result'])) {
            $finalResult['sale_stats'] = $result['result'];
        }

        return $finalResult;
    }

    /**
     * 所有玩家汇总      https://baseurl/customreport/getdata/reportname/PlayerStats/startdate/2014-08-11%2000:00:00/enddate/2014-08-11%2023:59:59/
     * 按玩家列出所有资讯https://baseurl/customreport/getdata/reportname/PlayerStats/startdate/2014-08-11%2000:00:00/enddate/2014-08-11%2023:59:59/reportby/player
     * 指定單一玩家:     https://baseurl/customreport/getdata/reportname/PlayerStats/startdate/2014-08-11%2000:00:00/enddate/2014-08-11%2023:59:59/playername/{username}
     *
     * array (
      'result' =>
      array (
      0 =>
      array (
      'STATSDATE' => '2014-12-05',
      'CURRENCYCODE' => 'CNY',
      'ACTIVEPLAYERS' => '1',
      'BALANCECHANGE' => '10',
      'DEPOSITS' => '15',
      'WITHDRAWS' => '5',
      'COMPENSATION' => '0',
      'BONUSES' => '0',
      'COMPS' => '0',
      'PROGRESSIVEBETS' => '0',
      'PROGRESSIVEWINS' => '0',
      'BETS' => '0',
      'WINS' => '0',
      'NETLOSS' => '0',
      'NETPURCHASE' => '10',
      'NETGAMING' => '0',
      'HOUSEEARNINGS' => '0',
      'RNUM' => '1',
      ),
      ),
      'pagination' =>
      array (
      'currentPage' => 1,
      'totalPages' => 1,
      'itemsPerPage' => 50,
      'totalCount' => 1,
      ),
      )
     */
    public function playerStats($username, $startDate, $endDate, $byPlayer = false)
    {
        $startDate = str_replace(' ', '%20', $startDate);
        $endDate = str_replace(' ', '%20', $endDate);
        $url = $this->interfaceURL . "/customreport/getdata/reportname/PlayerStats/startdate/$startDate/enddate/$endDate/";
        if ($username) {
            $url .= "playername/{$username}/";
        }
        elseif ($byPlayer) {
            $url .= "reportby/player/";
        }
        $url.='page/1/perPage/10000/';
        $result = $this->sslCall($url);
        $finalResult = PT::processError($result);
        if (isset($result['result'])) {
            $finalResult['player_stats'] = $result['result'];
        }

        return $finalResult;
    }

    /**
     * 查询游戏明细   https://baseurl/customreport/getdata/reportname/PlayerGames/startdate/2014-07-30%2011:00:00/enddate/2014-07-30%2011:29:29/playername/{username}/frozen/all
     *
     * array (
      'result' =>
      array (
      ),
      'pagination' =>
      array (
      'currentPage' => 1,
      'totalPages' => 1,
      'itemsPerPage' => 50,
      'totalCount' => 0,
      ),
      )
     */
    public function playerGames($username, $startDate, $endDate)
    {
        $startDate = str_replace(' ', '%20', $startDate);
        $endDate = str_replace(' ', '%20', $endDate);
        $url = $this->interfaceURL . "/customreport/getdata/reportname/PlayerGames/startdate/$startDate/enddate/$endDate/playername/{$username}/frozen/all/page/1/perPage/10000/";
        $result = $this->sslCall($url);
        $finalResult = PT::processError($result);
        if (isset($result['result'])) {
            $finalResult['game_detail_stats'] = $result['result'];
        }

        return $finalResult;
    }

    public function sslCall($url)
    {
        if (!is_readable(__DIR__ . '/pt/file.pem') || !is_readable(__DIR__ . '/pt/file.key')) {
            throw new exception2('certification file not found');
        }
        $header = array();
        $header[] = "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive:timeout=5, max=100";
        $header[] = "Accept-Charset:ISO-8859-1,utf-8;q=0.7,*;q=0.3";
        $header[] = "Accept-Language:es-ES,es;q=0.8";
        $header[] = "Pragma: ";
        $header[] = "X_ENTITY_KEY: " . $this->keyB['entity_key'];
//logdump($header);

        for ($i = 0; $i < 2; $i++) {
            $tuCurl = curl_init();
            curl_setopt($tuCurl, CURLOPT_URL, $url);
            curl_setopt($tuCurl, CURLOPT_PORT, 443);
            curl_setopt($tuCurl, CURLOPT_VERBOSE, 0);
            curl_setopt($tuCurl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($tuCurl, CURLOPT_SSLCERT, __DIR__ . '/pt/file.pem');
            curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($tuCurl, CURLOPT_SSLKEY, __DIR__ . '/pt/file.key');
            $exec = curl_exec($tuCurl);
            $info = curl_getinfo($tuCurl);
            log2($info);
            curl_close($tuCurl);
            $data = json_decode($exec, TRUE);
            if ($data !== NULL) {
                break;
            }
            sleep(1);
        }
        $data = json_decode($exec, TRUE);
        log2($data);
        return $data;
    }

    //按指定长度生成随机字符
//    static public function rndStr($length)
//    {
//        if ($length <= 0) {
//            return '';
//        }
//
//        $haystack = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//        $result = '';
//        for ($i = 0; $i < $length; $i++) {
//            $result .= $haystack{rand(0, 61)};
//        }
//
//        return $result;
//    }
//
//    //把bbin代理名还原成总代的user_id
//    static public function decodeUserId($bbin_agent_username)
//    {
//        if (!preg_match('`^djyz(\d+)x$`', $bbin_agent_username, $match)) {
//            return 0;
//        }
//
//        return intval($match[1]);
//    }

    public function processError($result)
    {
        if (!is_array($result)) {
            //throw new exception2("network problem:$result", 999);
            $result = array('errno' => 90001, 'errstr' => "network problem:$result");
            //var_dump($result);
            return $result;
        }
        if (isset($result['errorcode'])) {
            // throw new exception2("network problem:{$result['error']}", $result['errorcode']);
            $result['errno'] = 90002;
            $result['errstr'] = "network problem:{$result['error']} {$result['errorcode']}";
            //var_dump($result);
            return $result;
        }

        return array('errno' => 0, 'errstr' => '');
    }

}

?>