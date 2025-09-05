<?php

namespace app\common\model\system\sms;

use app\common\model\BaseModel;

class SmsTemplateModel extends BaseModel
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';
    protected $updateTime = 'edit_time';

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'sms_template';
    }

}
