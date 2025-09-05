<?php

namespace app\common\model\poster;

use app\common\model\BaseModel;

class PosterFileModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'poster_file';
    }

}
