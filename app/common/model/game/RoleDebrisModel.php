<?php

namespace app\common\model\game;

use app\common\model\BaseModel;

class RoleDebrisModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'game_role_debris';
    }

}
