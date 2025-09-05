<?php

namespace app\common\model\mine;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class MineUserDispatchModel extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'mine_user_dispatch';
    }
    public function userInfo(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
