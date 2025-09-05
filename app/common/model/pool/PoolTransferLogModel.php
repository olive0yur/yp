<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class PoolTransferLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_transfer_log';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function pool(){
        return $this->hasOne(PoolSaleModel::class,'id','pool_id');
    }
}
