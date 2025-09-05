<?php

namespace app\common\model\company;

use app\common\model\BaseModel;
use app\common\model\company\user\CompanyUser;

class Company extends BaseModel
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
        return 'company';
    }

    public function mainUserInfo()
    {
        return $this->hasOne(CompanyUser::class, 'company_id', 'id')->where('is_main', 1);
    }

    public function getMainAccountAttr()
    {
        return $this->mainUserInfo()->value('account');
    }
}
