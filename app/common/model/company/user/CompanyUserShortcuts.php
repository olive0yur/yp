<?php

namespace app\common\model\company\user;

use app\common\model\BaseModel;

class CompanyUserShortcuts extends BaseModel
{
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    // 自动时间戳字段名
    protected $createTime = 'add_time';
    protected $updateTime = false;

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'company_user_shortcuts';
    }

}
