<?php

namespace app\common\model\givLog;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class MineGivLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'mine_giv_log';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','get_uuid');
    }

}
