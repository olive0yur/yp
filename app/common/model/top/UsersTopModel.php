<?php

namespace app\common\model\top;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class UsersTopModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users_top';
    }


    public function users(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
