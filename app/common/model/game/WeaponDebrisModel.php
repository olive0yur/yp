<?php

namespace app\common\model\game;

use app\common\model\BaseModel;

class WeaponDebrisModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'game_weapon_debris';
    }

}
