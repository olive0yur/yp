<?php

namespace app\common\model\union;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class UnionBrandModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'union_brand';
    }
    public function file()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'file_id');
    }

    public function headInfo(){
        return $this->hasOne(UploadFileModel::class,'id','head_file_id');
    }
}
