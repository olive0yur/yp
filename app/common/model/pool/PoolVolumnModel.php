<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class PoolVolumnModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_volumn';
    }
    public function goods()
    {
        return $this->hasOne(PoolSaleModel::class, 'id', 'pool_id');
    }

    public function user()
    {
        return $this->hasOne(UsersModel::class, 'id', 'uuid');
    }

}
