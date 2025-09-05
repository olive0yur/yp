<?php

namespace app\common\model\article\guide;

use think\Model;
use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class OperateModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'article_operate';
    }

    public function cateInfo()
    {
        return $this->hasOne(OperateCateModel::class, 'id', 'cate_id')->field('id,name');
    }

    public function file()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'picture_id');
    }

    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }
}
