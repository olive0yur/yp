<?php

namespace app\common\model\guild;

use app\common\model\BaseModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;

class GuildWareHouseModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'guild_warehouse';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
    public function pool(){
        return $this->hasOne(PoolSaleModel::class,'id','pool_id');
    }

    public function guild(){
        return $this->hasOne(GuildModel::class,'id','guild_id');
    }

}
