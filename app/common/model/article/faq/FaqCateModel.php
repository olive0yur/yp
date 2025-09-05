<?php

namespace app\common\model\article\faq;

use app\common\model\BaseModel;

class FaqCateModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'article_faq_cate';
    }

}
