<?php

namespace app\common\model\article\faq;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class FaqModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'article_faq';
    }

    public function cateInfo()
    {
        return $this->hasOne(FaqCateModel::class, 'id', 'cate_id')->field('id,name');
    }


    public function file()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'picture_id');
    }

}
