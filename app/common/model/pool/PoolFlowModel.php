<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;
use app\common\model\pool\PoolSaleModel;

class PoolFlowModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_flow';
    }

    public function userInfo(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function poolInfo(){
        return $this->hasOne(PoolSaleModel::class,'id','pool_id');
    }
}
