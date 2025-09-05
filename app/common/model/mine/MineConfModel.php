<?php

namespace app\common\model\mine;

use app\common\model\BaseModel;

class MineConfModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'mine_conf';
    }



}
