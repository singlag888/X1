<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class methodGroups
{
    // 当值为0时存在所有玩法中
    // 当值大于0时仅却在对应玩法中
    // bit value
    const GROUP_ALL = 0;
    const GROUP_OFFICIAL = 1;
    const GROUP_CREDIT = 2;
}