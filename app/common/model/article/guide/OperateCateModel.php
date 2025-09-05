<?php

namespace app\common\model\article\guide;

use app\common\model\BaseModel;

class OperateCateModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'article_operate_cate';
    }

}
