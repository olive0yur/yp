<?php

namespace app\common\model\refund;

use app\common\model\BaseModel;

class RefundStatusModel extends BaseModel
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
        return 'refund_status';
    }

}
