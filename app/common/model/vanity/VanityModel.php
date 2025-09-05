<?php

namespace app\common\model\vanity;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class VanityModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'vanity_code';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
