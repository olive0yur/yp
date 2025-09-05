<?php

namespace app\common\model\gashapon;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class GashaponUserModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'gashapon_user';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
