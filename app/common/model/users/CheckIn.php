<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\model\pool\PoolFollowModel;
use app\common\model\pool\PoolSaleModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersPoolRepository;

class CheckIn extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'check_in';
    }

    public function userInfo()
    {
        return $this->hasOne(UsersModel::class, 'id', 'uuid');
    }
}
