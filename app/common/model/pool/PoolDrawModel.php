<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class PoolDrawModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_draw';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
