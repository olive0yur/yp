<?php

namespace app\common\model\poster;

use app\common\model\BaseModel;

class PosterSiteModel extends BaseModel
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
        return 'poster_site';
    }

}
