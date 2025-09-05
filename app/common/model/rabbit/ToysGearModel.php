<?php

namespace app\common\model\rabbit;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersMarkModel;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\model\union\UnionBrandModel;
use app\common\model\union\UnionAlbumModel;

class ToysGearModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'toys_gear';
    }

    public function level()
    {
        return $this->hasOne(ToysGearLevelModel::class,'id','level_id');
    }
    public function cover()
    {
        return $this->hasOne(UploadFileModel::class,'id','file_id');
    }

    public function headerCover()
    {
        return $this->hasOne(UploadFileModel::class,'id','head_file_id');
    }
}
