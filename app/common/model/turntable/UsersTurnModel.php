<?php

namespace app\common\model\turntable;

use app\common\model\BaseModel;

class UsersTurnModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users_turn';
    }

    public function goods(){
        return $this->hasOne(TurntableGoodsModel::class,'id','turn_id');
    }
}
