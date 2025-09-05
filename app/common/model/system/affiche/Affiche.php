<?php

namespace app\common\model\system\affiche;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class Affiche extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'system_affiche';
    }

    public function cateInfo()
    {
        return $this->hasOne(AfficheCate::class, 'id', 'cate_id')->field('id,name');
    }


    public function file()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'file_id');
    }


    public function isFollow(){
        return $this->hasMany(AfficheFollow::class,'affiche_id','id');
    }


    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }
}
