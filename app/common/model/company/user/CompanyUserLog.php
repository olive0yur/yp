<?php
namespace app\common\model\company\user;

use app\common\model\BaseModel;

class CompanyUserLog extends BaseModel{
    protected $autoWriteTimestamp = true;
    protected $createTime = "add_time";
    protected $updateTime = false;

    public static function tablePk(): ?string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'company_user_log';
    }
    
    public function adminUser()
    {
        return $this->hasOne(CompanyUser::class, 'id', 'admin_id')->field('id,username,account');
    }
}