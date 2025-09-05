<?php

namespace app\common\model\system\sms;

use app\common\model\BaseModel;

class SmsConfigModel extends BaseModel
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';
    protected $updateTime = false;

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'sms_config';
    }

}
