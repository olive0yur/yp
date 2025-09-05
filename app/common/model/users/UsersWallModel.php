<?php

namespace app\common\model\users;

use app\common\model\BaseModel;

class UsersWallModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users_wall';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
