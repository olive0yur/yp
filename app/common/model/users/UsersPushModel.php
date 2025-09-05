<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\repositories\mine\MineUserDispatchRepository;

class UsersPushModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users_push';
    }

    public function tjrOne()
    {
        return $this->hasOne(UsersModel::class, 'id', 'parent_id');
    }

    public function userInfo()
    {
        return $this->hasOne(UsersModel::class, 'id', 'user_id');
    }

    public function user()
    {
        return $this->hasOne(UsersModel::class, 'id', 'user_id');
    }

    public function pool(){
        return $this->hasMany(UsersPoolModel::class,'uuid','user_id');
    }


}
