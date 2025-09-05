<?php

namespace app\common\model\system\upload;

use app\common\model\BaseModel;

class UploadFileModel extends BaseModel
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
        return 'system_file';
    }

}
