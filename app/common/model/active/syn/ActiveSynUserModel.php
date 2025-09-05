<?php

namespace app\common\model\active\syn;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class ActiveSynUserModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'active_syn_user';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
