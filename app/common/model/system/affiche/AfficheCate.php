<?php

namespace app\common\model\system\affiche;

use app\common\model\BaseModel;

class AfficheCate extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'system_affiche_cate';
    }

    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }
}
