<?php

namespace app\common\model\mine;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class DragonCannelModel extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'dragon_cannel';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
