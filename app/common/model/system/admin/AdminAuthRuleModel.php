<?php

namespace app\common\model\system\admin;

use app\common\model\BaseModel;
use think\Model;

class AdminAuthRuleModel extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'admin_auth_rule';
    }

    // -----------------------------------------------------------------------------------------------------------------
    // 搜索器
    // -----------------------------------------------------------------------------------------------------------------

    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->where('id', 'in', $value);
        } else {
            $query->where('id', '=', (int)$value);
        }
    }
}