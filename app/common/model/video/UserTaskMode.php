<?php

namespace app\common\model\video;

use app\common\model\BaseModel;
use app\common\model\system\ConfigModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;
use app\common\model\video\VideoTaskModel;

class UserTaskMode extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'user_task';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function task(){
        return $this->hasOne(VideoTaskModel::class,'id','uuid');
    }




}
