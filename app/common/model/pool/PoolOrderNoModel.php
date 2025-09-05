<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;

class PoolOrderNoModel extends BaseModel
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';
    protected $updateTime = false;

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_order_no';
    }

    public function poolInfo(){
        return $this->hasOne(PoolSaleModel::class,'id','pool_id');
    }
    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
