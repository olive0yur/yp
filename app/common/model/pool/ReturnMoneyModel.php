<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;

class ReturnMoneyModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'return_money';
    }
}
