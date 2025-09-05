<?php

namespace app\common\model\system\admin;

use app\common\model\BaseModel;

class AdminLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
         return 'admin_log';
    }

    // -----------------------------------------------------------------------------------------------------------------
    // 关联模型
    // -----------------------------------------------------------------------------------------------------------------

    public function adminUser()
    {
        return $this->hasOne(AdminUserModel::class, 'id', 'admin_id')->field('id,username,account');
    }

}
