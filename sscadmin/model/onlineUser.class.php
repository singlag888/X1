<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class onlineUser
{
    // 每一步的秒数,必须被86400整除
    # TODO: 暂时的硬编码,可以添加到配置管理中去.但同时需要做一个配置值验证hook
    const stepLength = 240;

    private $redisHashPrefix = 'online_users_hash_';
    private $redisBitmapPrefix = 'online_users_bitmap_';

    /**
     * 用户id集中筛选出在线的用户id
     * @param array $uidList
     * @return array
     */
    public function getIntersectByUid($uidList)
    {
        $userList = $this->getOnline();
        return array_intersect($uidList, $userList);
    }

    /**
     * 获取在线用户数
     * @return int
     */
    public function countOnline()
    {
        $userList = $this->getOnline();
        return count($userList);
    }

    /* 统一入口,统一切换. */

    public function getOnline()
    {
        $GLOBALS['redis']->select(REDIS_DB_SESSION);
        // $data = $this->getOnlineBitmap();
        $data = $this->getOnlineHash();
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
        return $data;
    }

    public function recordOnline($userId)
    {
        $GLOBALS['redis']->select(REDIS_DB_SESSION);
        // $this->recordOnlineBitmap($userId);
        $this->recordOnlineHash($userId);
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
    }

    public function offline($userId)
    {
        $GLOBALS['redis']->select(REDIS_DB_SESSION);
        // $this->offlineBitmap($userId);
        $this->offlineHash($userId);
        $GLOBALS['redis']->select(REDIS_DB_DEFAULT);
    }

    /* ------------------- 下面是REDIS的 ---------------------- */

    /**
     * 利用redis的bitmap来记录在线用户,对应的格式就是 bitmapKey userId 1
     * 如果是hash的话就是 hashesKey userId 1
     *
     * 然后是创建区间
     * 例如 当前flag 为 3, 创建两步键 2-4 和 一步键 3-4
     * 就使用上一个的一步键 2-3 作为2-4的初始值
     */

    /* -------------------- Bitmap begin -------------------- */

    private function recordOnlineBitmap($userId)
    {
    }

    private function getOnlineBitmap()
    {
    }

    private function offlineBitmap($userId)
    {
    }

    /* -------------------- Bitmap end -------------------- */

    /* -------------------- Hashes begin -------------------- */

    /**
     * 记录用户在线状态
     * @param int $userId
     */
    private function recordOnlineHash($userId)
    {
        $twoStepKey = $this->redisHashPrefix . $this->getStepKey(2);

        // 先判断是否有这个2步键
        if (!$GLOBALS['redis']->exists($twoStepKey)) {
            $this->initHash($twoStepKey);
        }

        // 记录1步键
        $oneStepKey = $this->redisHashPrefix . $this->getStepKey(1);
        $GLOBALS['redis']->hSetNx($oneStepKey, $userId, 1);
        $GLOBALS['redis']->expire($oneStepKey, self::stepLength * 4);

        // 记录2步键
        $GLOBALS['redis']->hSetNx($twoStepKey, $userId, 1);
        $GLOBALS['redis']->expire($twoStepKey, self::stepLength * 4);
    }

    /**
     * 初始化2步键
     * @param string $twoStepKey
     */
    private function initHash($twoStepKey)
    {
        // 当不存在的时候拿到上一个一步键来初始化
        $preOneStepKey = $this->redisHashPrefix . $this->getStepKey(1, -1);

        $initData = [];

        // 创建 2-4 就是用2-3的一步键
        if ($GLOBALS['redis']->exists($preOneStepKey)) {

            $initData = $GLOBALS['redis']->hKeys($preOneStepKey);

            // 键值交换
            // userId唯一做键即可,值无所谓.
            $initData = array_flip($initData);
        }

        $GLOBALS['redis']->hMset($twoStepKey, $initData);
    }

    /**
     * 获取在线用户id列表
     * @return array
     */
    private function getOnlineHash()
    {
        $twoStepKey = $this->redisHashPrefix . $this->getStepKey(2);

        // 先判断是否有这个2步键
        if (!$GLOBALS['redis']->exists($twoStepKey)) {
            $this->initHash($twoStepKey);
        }

        return $GLOBALS['redis']->hKeys($twoStepKey);
    }

    /**
     * 清除在线状态
     * @param int $userId
     */
    private function offlineHash($userId)
    {
        // 分别清除1步键与2步键
        $twoStepKey = $this->redisHashPrefix . $this->getStepKey(1);
        $GLOBALS['redis']->hdel($twoStepKey, $userId);

        $twoStepKey = $this->redisHashPrefix . $this->getStepKey(2);
        $GLOBALS['redis']->hdel($twoStepKey, $userId);
    }

    /* -------------------- Hashes end -------------------- */

    /**
     * 生成区间键
     * @param int $step
     * @param int $offset
     * @return string
     */
    private function getStepKey($step, $offset = 0)
    {
        $totalStep = 86400 / self::stepLength; // 一定要可以整除
        $hour = date('G', REQUEST_TIME);
        $minute = date('i', REQUEST_TIME);
        $right = floor(($hour * 3600 + $minute * 60) / self::stepLength) + 1 + $offset;
        $right >= $totalStep && $right = 0;
        $left = $right - $step;
        $left < 0 && $left += $totalStep;

        return $left . '_' . $right;
    }
}