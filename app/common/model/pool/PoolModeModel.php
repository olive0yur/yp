<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\common\album\AlbumModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class PoolModeModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_mode';
    }
    public function img()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'file_id');
    }
    public function back()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'back_id');
    }


    public function tableImg()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'table_id');
    }
}
