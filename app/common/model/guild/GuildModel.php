<?php

namespace app\common\model\guild;

use app\common\model\BaseModel;
use app\common\model\system\ConfigModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class GuildModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'guild';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }




}
