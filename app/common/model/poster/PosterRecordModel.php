<?php

namespace app\common\model\poster;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class PosterRecordModel extends BaseModel
{

    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';
    protected $updateTime = 'edit_time';

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'poster_record';
    }

    public function file()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'file_id');
    }


    public function site()
    {
        return $this->hasOne(PosterSiteModel::class, 'id', 'site_id');
    }
}
