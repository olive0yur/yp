<?php

namespace app\common\dao\system\sms;

use app\common\dao\BaseDao;
use app\common\model\system\sms\SmsConfigModel;

class SmsConfigDao extends BaseDao
{

    /**
     * @return SmsConfigModel
     */
    protected function getModel(): string
    {
        return SmsConfigModel::class;
    }

}
