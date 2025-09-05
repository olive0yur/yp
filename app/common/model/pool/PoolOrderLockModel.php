<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class PoolOrderLockModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_order_lock';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

}
