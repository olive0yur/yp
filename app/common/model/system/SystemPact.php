<?php

namespace app\common\model\system;

use app\common\model\BaseModel;

class SystemPact extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'system_pact';
    }

    // -----------------------------------------------------------------------------------------------------------------
    // 获取器
    // -----------------------------------------------------------------------------------------------------------------

    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }

}
