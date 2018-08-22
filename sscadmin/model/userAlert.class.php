<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

class userAlert extends baseModel
{
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';

    /**
     * 根据user
     * @param $userId
     * @return array|mixed
     */
    public function getUserAlert($userId = 0)
    {
        $redisKey = $this->getRedisKey($userId);
        // 这里来理一波思路
        // 1.查看cache里有没有数据 有取cache没有则查询后,存储cache
        $data = $GLOBALS['redis']->get($redisKey);

        if ($data) {
            // 存在则转义
            $data = json_decode($data, true);
        } else {
            $this->field('ua_id,title,domain,content,main_img,m_main_img,type');
            // 否则查询
            if ($userId > 0) {
                $this->where("FIND_IN_SET({$userId},user_tree)");
            } else {
                // 没有userId则为默认
                // 默认可能有多个,取最新的.
                $this->where(['default' => 1])->order($this->pk . ' DESC');
            }
            $data = $this->find();

            // 有配置才存
            $data && $GLOBALS['redis']->setex($redisKey, 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        return $data;
    }

    /**
     * 清除缓存
     * @param int|array $userIdList
     * @return $this
     */
    public function flushRedisCache($userIdList)
    {
        !is_array($userIdList) && $userIdList = [$userIdList];

        foreach ($userIdList as &$item) {
            $GLOBALS['redis']->del($this->getRedisKey($item));
        }
        return $this;
    }

    /**
     * 获取userIdList
     * @param int|array $userId
     * @return array
     */
    public function getUserTreeIdList($uaId)
    {
        if (is_array($uaId)) {
            $this->where('ua_id in (' . implode(',', $uaId) . ')');
        } else {
            $this->where(['ua_id' => $uaId]);
        }

        $data = [];
        $list = $this->field('`user_tree`,`default`')->select();

        foreach ($list as &$item) {
            if ($item['default'] > 0) {
                // 这样是默认项,key为0
                $data[] = 0;
            } else {
                if (strpos($item['user_tree'], ',') !== false) {
                    $data = array_merge($data, explode(',', $item['user_tree']));
                } else {
                    $data[] = $item['user_tree'];
                }
            }
        }

        return $data;
    }

    /**
     * when insert and update,let the data auto completed
     * @param array $data
     * @param int $action
     */
    protected function _autoComplete(&$data, $action = '')
    {
        if ($action & (static::METHOD_INSERT | static::METHOD_UPDATE)) {
            !isset($data['ts']) && $data['ts'] = date('Y-m-d H:i:s', REQUEST_TIME);
            isset($data['content']) && $data['content'] = preg_replace("/\r|\n|\r\n/", '', $data['content']);
        }
    }

    /**
     * 获取redis中的key
     * @param $userId
     * @return string
     */
    private function getRedisKey($userId)
    {
        return 'userAlert_' . $userId;
    }
}