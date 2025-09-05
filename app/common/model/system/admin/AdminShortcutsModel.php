<?php

namespace app\common\model\system\admin;

use app\common\model\BaseModel;

class AdminShortcutsModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'admin_shortcuts';
    }

}
