<?php

namespace app\common\model\mine;

use app\common\model\BaseModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\product\ProductModel;
use app\common\model\users\UsersModel;

class MineUserModel extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'mine_user';
    }

    public function userInfo(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function mineInfo()
    {
        return $this->hasOne(MineModel::class, 'id', 'mine_id');
    }

    public function dispatch(){
        return $this->hasMany(MineUserDispatchModel::class,'mine_user_id','id');
    }

    public function getRateDayAttr($v,$data){
       return get_rate($data['dispatch_count'],$data['company_id'])['total'];
    }
}
