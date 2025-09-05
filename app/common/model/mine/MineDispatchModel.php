<?php

namespace app\common\model\mine;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class MineDispatchModel extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'mine_dispatch';
    }

}
