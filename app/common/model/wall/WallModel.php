<?php

namespace app\common\model\wall;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class WallModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'dividend_wall';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
