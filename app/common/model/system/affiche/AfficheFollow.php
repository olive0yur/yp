<?php

namespace app\common\model\system\affiche;

use app\common\model\BaseModel;

class AfficheFollow extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'system_affiche_follow';
    }


    public function affiche()
    {
        return $this->hasOne(Affiche::class,'id','affiche_id');
    }
}
