<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class UsersCertModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users_cert';
    }

    public function frontFile()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'front_file_id');
    }

    public function backFile()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'back_file_id');
    }

    public function user()
    {
        return $this->hasOne(UsersModel::class, 'id', 'user_id');
    }
}
