<?php

namespace app\common\model\article\news;

use app\common\model\BaseModel;

class NewsCateModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'article_news_cate';
    }

}
