<?php

namespace app\common\model\currency;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class EtcModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'etc_log';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

}
