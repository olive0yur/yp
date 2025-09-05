<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\model\pool\PoolFollowModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\pool\PoolOrderNoModel;
use app\common\repositories\active\ActiveRepository;
use app\common\repositories\active\syn\ActiveSynInfoRepository;
use app\common\repositories\active\syn\ActiveSynRepository;

class UsersPoolModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'user_pool';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function pool(){
        return $this->hasOne(PoolSaleModel::class,'id','pool_id');
    }

    public function onInfo(){
        return $this->hasOne(PoolOrderNoModel::class,'pool_id','pool_id')->where('no','no');
    }

}
