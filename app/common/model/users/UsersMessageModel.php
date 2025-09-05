<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\product\ProductRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersRepository;

class UsersMessageModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'user_message';
    }


    public function userInfo(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function getCoverAttr($v,$data){
        return  explode(',',$data['files']);
    }
}
