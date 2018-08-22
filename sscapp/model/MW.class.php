<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

define('API_DOMAIN', 'api/domain');
define('API_GAME_INFO', 'api/gameInfo');
define('API_OAUTH', 'api/oauth');
define('API_TRANSFER_PREPARE', 'api/transferPrepare');
define('API_TRANSFER_PAY', 'api/transferPay');
define('API_USER_INFO', 'api/userInfo');
define('API_USERS_GM_INFO', 'api/usersgminfo');
define('API_MERCHANTS_GM', 'api/merchantsgm');
define('API_SITE_USER_GAME_LOG', 'api/siteUsergamelog');


class MW
{
    private $domainUrl = 'http://www.168at168.com/as-lobby/';
    private $siteId = '10005000';
    private $merchantId = 'xy00001';
    private $ecPlatformPrivateKey = 'MIICXQIBAAKBgQCtsf3wAPpEMd9ay+TgdDLJTh4lfszBG2zr67tC4nF4oAmghA4qQvWoMNf4IP8ipuU2S7Lma7K/W3SKo/fGxhNaCdtAP25WpPp68tMRpiUckQ0WPz8WOYbQI2snILO0fukpD0BLJfVrlRrQ004gpPiWsP8JZgv/Vd59SNas9hXsSwIDAQABAoGBAKqFKip7mzZly8Okld6w1McCFOB0mmkeFpGKDH4+Nm3Yl3rpCcr9j5P915h2NGN9e1sRd+F0a7gm/cO5819GR+mjb9m6dxngRVX9iXwBa08x95tuvfVTpuDSgGPqV/IxWCTuJvGwLsJXhFTiVdnRpv54sDYLZHwrIB+AWgjP3ZQRAkEA9Dd9jpVbsvYpXeCVvFsAqbc7Q0LbAemTA+G+X3+6/V53r55CpbiCfJs/yysntP0vV632VoCp1WD47zEZYYHb2QJBALYTb7J4vqCceME9G0oYF+13ufU2Kwxm+/d//k/Dsv6N4wiI6Cz3GSTi0LImLcaJzxSpYDSnUcuMLUUAgppLZsMCQQDGo6Pyp7WweNzoyNXCINsHMEx5xxVQFuZHkARCtSgpEf+Tzswy80Lfi392B6ICarVpQcxOS9TYBEo2c99LGk7xAkBK4ONmulFrK+5dSgwXBKGSf1JyjbxWdWPZ/UFQ/GJ4XVGpCOSy2Uv153F8UkcxByuqi52NCeKRmyxi3DrZyXiRAkB+xTQxN1cdSAKVUwEjO4WvgXIe25LVVeMXg5h0/sdhHhBtvLIm/DbMTgI9We7w+S7xPhusXRNArhBX+jZ7u9Z1';
    private $mwPlatformPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC07iwUxR9aJBZai4KWabKaDYgkHjabnkErbdk6cAk78P1OEy4CwEmZhe6qIKl5TEiJtqdB4tmEXpjZ98aUyI84BVfyj+UqHqIYMIlv8o+HZ3rcs8L+MdCrIyuoNIpGEDI88/jvqwhKCBXyYMr1FPRveFPrHR/5W//WRqMky53NhQIDAQAB';
    private $ecPlatformAesKey = 'abc1234567812345';

    public static $retCodes = array(
        '0000' => '成功',
    );

    public function __construct()
    {
        if (isset($GLOBALS['cfg']['mw']['domainUrl'])) {
            $this->domainUrl = $GLOBALS['cfg']['mw']['domainUrl'];
        }

        if (isset($GLOBALS['cfg']['mw']['$ecPlatformPrivateKey'])) {
            $this->ecPlatformPrivateKey = $GLOBALS['cfg']['mw']['ecPlatformPrivateKey'];
        }

        if (isset($GLOBALS['cfg']['mw']['mwPlatformPublicKey'])) {
            $this->mwPlatformPublicKey = $GLOBALS['cfg']['mw']['mwPlatformPublicKey'];
        }

        if (isset($GLOBALS['cfg']['mw']['ecPlatformAesKey'])) {
            $this->ecPlatformAesKey = $GLOBALS['cfg']['mw']['ecPlatformAesKey'];
        }

        $this->siteId = MW_SITE_ID;
        $this->merchantId = XY_PREFIX;
    }

    public function getDomain()
    {
        if ($mw_domain = $GLOBALS['redis']->get('mw_domain')) {
            return $mw_domain;
        } else {
            $data = array(
                'timestamp' => time(),
            );

            $data = $this->getData($data);
            $key = $this->getKey();

            $postData = array(
                'func' => 'domain',
                'resultType' => 'json',
                'siteId' => $this->siteId,
                'data' => urlencode($data),
                'key' => urlencode($key),
            );

            $response = $this->curlPost($this->domainUrl . API_DOMAIN, $postData);
            $response = json_decode($response, true);
            $GLOBALS['redis']->set('mw_domain', $response['domain'], 86400);

            return $response['domain'];
        }
    }

    public function getGameInfo() {
        $data = array(
            'timestamp' => time(),
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'gameInfo',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $domain = $this->getDomain();
        $response = $this->curlPost($domain . API_GAME_INFO, $postData);
        $response = json_decode($response, true);

        return $response;
    }

    public function getUsersGMInfo($uid, $username, $parentId, $topId, $beginTime, $endTime)
    {
        $data = array(
            'uid' => $this->siteId . '_' . $this->merchantId. '_' . $uid,
            'merchantId' => $this->merchantId . '_' .  $topId . '_' . $parentId,
            'beginTime' => $beginTime,
            'endTime' => $endTime,
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'usersgminfo',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $domain = $this->getDomain();
        $response = $this->curlPost($domain . API_USERS_GM_INFO, $postData);
        $response = json_decode($response, true);

        if ($response['ret'] == '0000') {
            $response['total_amount'] = 0;
            $response['total_prize'] = 0;
            $response['total_profit'] = 0;

            foreach ($response['userSgmInfos'] as $key => &$value) {
                $value['username'] = $username;
                $response['total_amount'] += $value['playAmount'];
                $response['total_prize'] += $value['playWin'];
                $response['total_profit'] += $value['playJifenAmount'];
            }
        }

        return $response;
    }

    public function getAllUsersGMInfo($uid, $beginTime, $endTime)
    {
        //$userTree = users::getUserTree($uid , true, 1, -1, -1, '');
        $userTree = users::getUserTreeField([
            'field' => ['user_id', 'top_id', 'username'],
            'parent_id' => $uid,
            'recursive' => 1,
        ]);
        $result = array();

        $total = array(
            'total_amount' => 0,
            'total_prize' => 0,
            'total_profit' => 0,
        );

        $arrayIndex = 0;

        foreach ($userTree as $userId => $user) {
            $data = array(
                'uid' => $this->siteId . '_' . $this->merchantId. '_' . $uid,
                'merchantId' => $this->merchantId . '_' . $user['top_id'] . '_' . $userId,
                'beginTime' => $beginTime,
                'endTime' => $endTime,
            );

            $data = $this->getData($data);
            $key = $this->getKey();

            $postData = array(
                'func' => 'usersgminfo',
                'resultType' => 'json',
                'siteId' => $this->siteId,
                'data' => urlencode($data),
                'key' => urlencode($key),
            );

            $domain = $this->getDomain();
            $response = $this->curlPost($domain . API_USERS_GM_INFO, $postData);
            $response = json_decode($response, true);

            if (empty($response['userSgmInfos'])) {
                continue;
            }

            foreach ($response['userSgmInfos'] as $userSgmInfos) {
                $result[$arrayIndex] = array ();
                $result[$arrayIndex]['username'] = $user['username'];
                $result[$arrayIndex]['gameName'] = $userSgmInfos['gameName'];
                $result[$arrayIndex]['playLoop'] = $userSgmInfos['playLoop'];
                $result[$arrayIndex]['playAmount'] = $userSgmInfos['playAmount'];
                $result[$arrayIndex]['playJifenAmount'] = $userSgmInfos['playJifenAmount'];
                $result[$arrayIndex]['playWin'] = $userSgmInfos['playWin'];
                $total['total_amount'] += $userSgmInfos['playAmount'];
                $total['total_prize'] += $userSgmInfos['playWin'];
                $total['total_profit'] += $userSgmInfos['playJifenAmount'];
                $arrayIndex++;
            }
        }

        $result['total'] = $total;
        return $result;
    }

    public function getMerchantsGm($user_id , $beginTime, $endTime, $isOnlyAgent = false)
    {
        $userTree = array();

        if ($isOnlyAgent) {
            $userTree = users::getAgentUserTree($user_id , true, 1, -1, -1, '', true);
        } else {
            // $userTree = users::getUserTree($user_id , true, 1, -1, -1, '');
            $userTree = users::getUserTreeField([
                'field' => ['user_id', 'username'],
                'parent_id' => $user_id,
                'recursive' => 1,
            ]);
        }

        $result = array();

        foreach ($userTree as $userId => $user) {
            $data = array(
                'merchantId' => $this->merchantId . '_' . $user['user_id'] . '_' . $userId,
                'beginTime' => $beginTime,
                'endTime' => $endTime,
                'isFlip' => 0,
            );

            $data = $this->getData($data);
            $key = $this->getKey();

            $postData = array(
                'func' => 'merchantsgm',
                'resultType' => 'json',
                'siteId' => $this->siteId,
                'lang' => 'cn',
                'data' => urlencode($data),
                'key' => urlencode($key),
            );

            $domain = $this->getDomain();
            $response = $this->curlPost($domain . API_MERCHANTS_GM, $postData);
            $response = json_decode($response, true);

            if (empty($response['merchantSgms'])) {
                continue;
            }

            foreach ($response['merchantSgms'] as $merchantSgm) {
                if (!isset($result[$merchantSgm['merchantId']])) {
                    $result[$merchantSgm['merchantId']] = array(
                        'merchantId' => $merchantSgm['merchantId'],
                        'merchantName' => $user['username'],
                        'playLoop' => 0,
                        'playAmount' => 0,
                        'playJifenAmount' => 0,
                        'playWin' => 0,
                    );
                }

                $result[$merchantSgm['merchantId']]['playLoop'] += $merchantSgm['playLoop'];
                $result[$merchantSgm['merchantId']]['playAmount'] += $merchantSgm['playAmount'];
                $result[$merchantSgm['merchantId']]['playJifenAmount'] += $merchantSgm['playJifenAmount'];
                $result[$merchantSgm['merchantId']]['playWin'] += $merchantSgm['playWin'];
            }
        }

        return $result;
    }

    public function getUsersGmInfos($user_id , $beginTime, $endTime)
    {
        $userTree = users::getEgameUsers($user_id);
        $result = array();

        foreach ($userTree as $userId => $user) {
            $data = array(
                'uid' => $this->siteId . '_' . $this->merchantId. '_' . $userId,
                'merchantId' => $this->merchantId . '_' .  $user['top_id'] . '_' . $user['parent_id'],
                'beginTime' => $beginTime,
                'endTime' => $endTime,
            );

            $data = $this->getData($data);
            $key = $this->getKey();

            $postData = array(
                'func' => 'usersgminfo',
                'resultType' => 'json',
                'siteId' => $this->siteId,
                'lang' => 'cn',
                'data' => urlencode($data),
                'key' => urlencode($key),
            );

            $domain = $this->getDomain();
            $response = $this->curlPost($domain . API_USERS_GM_INFO, $postData);
            $response = json_decode($response, true);

            if (empty($response['userSgmInfos'])) {
                continue;
            }

            foreach ($response['userSgmInfos'] as $merchantSgm) {
                if (!isset($result[$user['top_id']])) {
                    $result[$user['top_id']] = array(

                    );

                    $result[$user['top_id']] = array(
                        'top_id' => $user['top_id'],
                        'playLoop' => 0,
                        'playAmount' => 0,
                        'playJifenAmount' => 0,
                        'playWin' => 0,
                    );
                }

                $result[$user['top_id']]['playLoop'] += $merchantSgm['playLoop'];
                $result[$user['top_id']]['playAmount'] += $merchantSgm['playAmount'];
                $result[$user['top_id']]['playJifenAmount'] += $merchantSgm['playJifenAmount'];
                $result[$user['top_id']]['playWin'] += $merchantSgm['playWin'];
            }
        }

        return $result;
    }

    public function oauth($uid, $parentId, $topId, $username, $gameId, $jumpType = 0) {
        $uid = $this->siteId . '_' . $this->merchantId. '_' . $uid;

        $data = array(
            'uid' => $uid,
            'utoken' => $utoken = md5($uid . $username),
            'merchantId' => $this->merchantId . '_' . $topId . '_' . $parentId,
            'timestamp' => time(),
            'jumpType' => $jumpType,
            'gameId' => $gameId,
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'oauth',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $domain = $this->getDomain();
        $response = $this->curlPost($domain . API_OAUTH, $postData);
        $response = json_decode($response, true);

        return $response;
    }

    public function userInfo($uid, $parentId, $topId, $username) {
        $uid = $this->siteId . '_' . $this->merchantId. '_' . $uid;

        $data = array(
            'uid' => $uid,
            'utoken' => md5($uid . $username),
            'merchantId' => $this->merchantId . '_' . $topId . '_' . $parentId,
            'timestamp' => time(),
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'userInfo',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $domain = $this->getDomain();
        $response = $this->curlPost($domain . API_USER_INFO, $postData);
        $response = json_decode($response, true);

        if ($response['ret'] != '0000') {
            return json_encode(array('msg' => $response['msg']));
        } else {
            return json_encode(array('money' => $response['userInfo']['money']));
        }
    }

    public function transferPrepare($uid, $parentId, $topId, $username, $transferAmount, $transferOrderNo, $transferNotifierUrl, $transferType)
    {
        $uid = $this->siteId . '_' . $this->merchantId. '_' . $uid;
        $utoken = md5($uid . $username);

        $data = array(
            'uid' => $uid,
            'utoken' => $utoken,
            'transferType' => $transferType,
            'transferAmount' => $transferAmount,
            'transferOrderNo' => $transferOrderNo . $uid,
            'transferOrderTime' => date('Y-m-d H:i:s'),
            'transferClientIp' => $this->getClientIp(),
            'transferNotifierUrl' => $transferNotifierUrl,
            'merchantId' => $this->merchantId . '_' . $topId . '_' . $parentId,
            'timestamp' => time(),
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'transferPrepare',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $domain = $this->getDomain();
        $response = $this->curlPost($domain . API_TRANSFER_PREPARE, $postData);
        $response = json_decode($response, true);

        return $response;
    }

    public function transfer($asinTransferOrderNo, $asinTransferOrderTime, $transferAmount, $transferOrderNo)
    {
        $uid = $GLOBALS['SESSION']['user_id'];
        $uid = $this->siteId . '_' . $this->merchantId. '_' . $uid;
        $username = $GLOBALS['SESSION']['username'];
        $utoken = md5($uid . $username);

        $data = array(
            'uid' => $uid,
            'utoken' => $utoken,
            'asinTransferOrderNo' => $asinTransferOrderNo,
            'asinTransferOrderTime' => $asinTransferOrderTime,
            'transferOrderNo' => $transferOrderNo . $uid,
            'transferAmount' => $transferAmount,
            'transferClientIp' => $this->getClientIp(),
            'timestamp' => time(),
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'transferPay',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $domain = $this->getDomain();
        $response = $this->curlPost($domain . API_TRANSFER_PAY, $postData);

        return json_decode($response, true);
    }

    public function transferOut($asinTransferOrderNo, $asinTransferOrderTime, $transferAmount, $transferOrderNo)
    {
        $uid = $GLOBALS['SESSION']['user_id'];
        $uid = $this->siteId . '_' . $this->merchantId. '_' . $uid;
        $username = $GLOBALS['SESSION']['username'];
        $utoken = md5($uid . $username);

        $data = array(
            'uid' => $uid,
            'utoken' => $utoken,
            'asinTransferOrderNo' => $asinTransferOrderNo,
            'asinTransferOrderTime' => $asinTransferOrderTime,
            'transferOrderNo' => $transferOrderNo . $uid,
            'transferAmount' => $transferAmount,
            'transferClientIp' => $this->getClientIp(),
            'timestamp' => time(),
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'transferPay',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $domain = $this->getDomain();
        $response = $this->curlPost($domain . API_TRANSFER_PAY, $postData);

        return json_decode($response, true);
    }

    public function getSiteUsergamelog($beginTime = '', $endTime ='', $page = 1, $iGetLogInfoType = 0, $getType = 0) {
        $domain = $this->getDomain();
        $domain = str_replace('as-lobby', 'as-service', $domain);

        $date = getdate(strtotime($beginTime));
        $date['seconds'] = 1;

        $data = array(
            'beginTime' => $date['year'] . '-' .  $date['mon']. '-' .  $date['mday']. ' ' .  $date['hours'] . ':' . $date['minutes'] . ':' . $date['seconds'],
            'endTime' => $endTime,
            'page' => $page,
            'iGetLogInfoType' => $iGetLogInfoType,
            'getType' => $getType,
        );

        $data = $this->getData($data);
        $key = $this->getKey();

        $postData = array(
            'func' => 'siteUsergamelog',
            'resultType' => 'json',
            'siteId' => $this->siteId,
            'lang' => 'cn',
            'data' => urlencode($data),
            'key' => urlencode($key),
        );

        $response = $this->curlPost($domain . API_SITE_USER_GAME_LOG, $postData);
        $response = json_decode($response, true);

        return $response;
    }

    public function getData($data)
    {
        $ecPlatformPrivateKey = $this->formatRSAKey($this->ecPlatformPrivateKey, 'private');
        ksort($data); // 按照字母字典顺序先后排序

        $params = '';

        foreach ($data as $key => $val) {
            $params .= $key . '=' . $val;
        }

        $data['sign'] = $this->getSign($params, $ecPlatformPrivateKey);
        $json = json_encode($data);
        $areEncryptJson = $this->aesEncrypt($json, $this->ecPlatformAesKey);

        return $areEncryptJson;
    }

    public function getKey()
    {
        $mwPlatformPublicKey =  $this->formatKey($this->mwPlatformPublicKey);
        return $this->rsaPublicEncrypt($this->ecPlatformAesKey, $mwPlatformPublicKey);
    }

    public function getSign($params, $ecPlatformPrivateKey)
    {
        return $this->rsaPrivateEncrypt($params, $ecPlatformPrivateKey);
    }

    public function aesEncrypt($params, $key)
    {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'ecb');
        $params = $this->pkcs5_pad($params, $size);
        $params = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $params, MCRYPT_MODE_ECB, $key);

        return base64_encode($params);
    }

    public function rsaPrivateEncrypt($data, $key)
    {
        $key = openssl_pkey_get_private($key);

        if (!$key) {
            die('私钥无效！');
        }

        openssl_sign($data, $out, $key);
        $sign = base64_encode($out);

        return str_replace("\\", "", $sign);
    }

    public function rsaPublicEncrypt($data, $key, $type = 'public')
    {
        $key = openssl_pkey_get_public($key);

        if (!$key) {
            die('公钥无效！');
        }

        openssl_public_encrypt($data, $out, $key);
        $sign = base64_encode($out);

        return str_replace("\\", "", $sign);
    }

    public function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
    }

    public function curlPost($url, $data)
    {
        $curl = curl_init();

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
    );

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function formatKey($key, $type = 'public'){
        $key = str_replace("-----BEGIN PRIVATE KEY-----", "", $key);
        $key = str_replace("-----END PRIVATE KEY-----", "", $key);
        $key = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        $key = str_replace("-----END PUBLIC KEY-----", "", $key);
        $key = $this->trimAll($key);

        if ($type == 'public') {
            $begin = "-----BEGIN PUBLIC KEY-----\n";
            $end = "-----END PUBLIC KEY-----";
        } else {
            $begin = "-----BEGIN PRIVATE KEY-----\n";
            $end = "-----END PRIVATE KEY-----";
        }

        $key = chunk_split($key, 64, "\n");

        return $begin . $key . $end;
    }

    public function formatRSAKey($key, $type = 'public'){
        $key = str_replace("-----BEGIN RSA PRIVATE KEY-----", "", $key);
        $key = str_replace("-----END RSA PRIVATE KEY-----", "", $key);
        $key = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        $key = str_replace("-----END PUBLIC KEY-----", "", $key);
        $key = $this->trimAll($key);

        if ($type == 'public') {
            $begin = "-----BEGIN RSA PUBLIC KEY-----\n";
            $end = "-----END RSA PUBLIC KEY-----";
        } else {
            $begin = "-----BEGIN RSA PRIVATE KEY-----\n";
            $end = "-----END RSA PRIVATE KEY-----";
        }

        $key = chunk_split($key, 64, "\n");

        return $begin . $key . $end;
    }

    public function trimAll($str)
    {
        $qian = array(" ", "　", "\t", "\n", "\r");
        $hou = array("", "", "", "", "");
        return str_replace($qian, $hou, $str);
    }

    public function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}