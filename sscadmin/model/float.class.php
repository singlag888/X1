<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

// 并没有float表,只是一个伪模型.
class float
{
    const OPTION_DEFAULT = 0;
    const OPTION_MATCH_PATH = 1;

    private $defaultData = [
        'qq_number' => '',
        'email_address' => '',
        'wechat_number' => '',
        'wechat_qr' => '',
        'service_url' => '',
        'left_img' => [],
        'left_target' => [],
        'right_img' => [],
        'right_target' => [],
    ];

    private $configKey = 'float_config';

    public function getConfig($option = self::OPTION_DEFAULT)
    {
        $floatConfig = config::getConfig($this->configKey);

        if (!$floatConfig) {
            // 之前的配置在基础配置中,如果完全换过来了的话可以删除这里.
            $this->defaultData['qq_number'] = config::getConfig('qq_number');
            $this->defaultData['wechat_number'] = config::getConfig('wechat_number');
            $this->defaultData['email_address'] = config::getConfig('email_address');
            $this->defaultData['service_url'] = config::getConfig('service_url');

            config::addItem([
                'title' => '浮窗配置',
                'description' => '浮窗图片 客服链接 QQ 微信 二维码 邮箱',
                'cfg_key' => 'float_config',
                'cfg_value' => json_encode($this->defaultData, JSON_UNESCAPED_UNICODE),
                'parent_id' => 1,
            ]);

            $this->_clearCache();
            $floatConfig = $this->defaultData;
        } else {
            $floatConfig = array_merge($this->defaultData, json_decode($floatConfig, true));
        }

        if($option & self::OPTION_MATCH_PATH){
            $floatConfig['wechat_qr'] && $floatConfig['wechat_qr'] = $this->matchPath($floatConfig['wechat_qr']);
            $floatConfig['left_img'] && $floatConfig['left_img'] = $this->matchPath($floatConfig['left_img']);
            $floatConfig['right_img'] && $floatConfig['right_img'] = $this->matchPath($floatConfig['right_img']);
        }

        return $floatConfig;
    }

    public function update($data)
    {
        $result = (new baseModel('config'))
            ->where(['cfg_key' => $this->configKey])
            ->update(['cfg_value' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

        $result !== false && $this->_clearCache();
        return $result;
    }

    private function matchPath($path) {
        preg_match('@.*(images_fh.*)$@', $path, $macth);
        return isset($macth[1]) ? $macth[1] : '';
    }

    private function _clearCache()
    {
        exec('rm -f ' . ROOT_PATH . 'ssc/cache/*');
        exec('rm -f ' . ROOT_PATH . 'sscmobile/cache/*');
        @exec('nohup sh  '.CLEAR_CACHE_DIR.'clear_cache.sh &');
        $GLOBALS['mc']->flush();
    }
}