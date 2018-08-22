<?php
class wechat implements ArrayAccess {

    static private $config;
    private $oauth2;
    private $var;

    private function __construct() {
        // init
        $this->oauth2 = config::getConfigs(array('appid', 'secret', 'grant_type', 'token'));

    }

    public static function instance() {
        if (self::$config == null) {
            self::$config = new wechat();
        }
        return self::$config;
    }

    public function offsetExists($index) {
        return isset($this->var[$index]);
    }

    public function offsetGet($index) {
        return $this->var[$index];
    }

    public function offsetSet($index, $newvalue) {
        $this->var[$index] = $newvalue;
    }

    public function offsetUnset($index) {
        unset($this->var[$index]);
    }

    public function oauth2() {
        $request = Request::getInstance();
        $code  = $request->getGet('code', 'trim', '');

        if($code == ''){
            return false;
        }

        try{
            $curl  = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->oauth2['appid'].'&secret='.$this->oauth2['secret'].'&code='.$code.'&grant_type='.$this->oauth2['grant_type'];
            curl_setopt($curl, CURLOPT_URL, $url);
            $response     = curl_exec($curl);
            $json         = json_decode($response);
            $access_token = $json->{'access_token'};
            $openid       = $json->{'openid'};

            $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
            curl_setopt($curl, CURLOPT_URL, $url);
            $response = curl_exec($curl);
            curl_close($curl);
            $json     = json_decode($response);
            $nickname = $json->{'nickname'};

            return array('nickname' => $nickname , 'openid' => $openid);
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

}

?>