<?php

namespace app\common\model\game;

use app\common\model\BaseModel;

class KillRebateModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'kill_rebate';
    }

}
