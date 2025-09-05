<?php

namespace app\common\model\article\news;

use app\common\model\system\upload\UploadFileModel;
use think\Model;
use app\common\model\BaseModel;

class NewsModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'article_news';
    }

    public function cateInfo()
    {
        return $this->hasOne(NewsCateModel::class, 'id', 'cate_id')->field('id,name');
    }


    public function file()
    {
        return $this->hasOne(UploadFileModel::class, 'id', 'picture_id');
    }


}
