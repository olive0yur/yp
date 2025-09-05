<?php

namespace app\common\model\system\admin;

use app\common\model\BaseModel;
use app\common\repositories\system\admin\AdminUserRepository;
use think\Model;

class AdminUserModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'admin_user';
    }

    // -----------------------------------------------------------------------------------------------------------------
    // 关联模型
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * 关联快捷操作
     *
     * @return \think\model\relation\HasMany
     */
    public function shortcuts()
    {
        return $this->hasMany(AdminShortcutsModel::class, 'admin_id', 'id');
    }

    // -----------------------------------------------------------------------------------------------------------------
    // 修改器
    // -----------------------------------------------------------------------------------------------------------------

    public function setPasswordAttr($value, $array)
    {
        /** @var AdminUserRepository $repository */
        $repository = app()->make(AdminUserRepository::class);
        if (!empty($value)) {
            $password = $repository->passwordEncrypt($value);
            $this->set('password', $password);
        } else {
            $this->offsetUnset('password');
        }
    }
}
