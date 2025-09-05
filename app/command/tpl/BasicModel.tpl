<?php

namespace app\common\model{$namespace};

use app\common\model\BaseModel;

class {$modelName}Model extends BaseModel
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'add_time';
    protected $updateTime = false;

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return '{$tableName}';
    }

}
