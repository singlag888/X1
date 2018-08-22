<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

use common\model\baseModel;

class cardDepositGroup extends baseModel
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * when insert and update,let the data
     * @param $data
     */
    protected function _autoComplete(&$data, $action = '')
    {
        !isset($data['ts']) && $data['ts'] = date('Y-m-d H:i:s');
    }

}