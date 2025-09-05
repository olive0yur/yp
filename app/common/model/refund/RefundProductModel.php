<?php

namespace app\common\model\refund;

use app\common\model\BaseModel;
use app\common\model\order\OrderProductModel;

class RefundProductModel extends BaseModel
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
        return 'refund_product';
    }

    public function product()
    {
        return $this->hasOne(OrderProductModel::class, 'id', 'order_product_id');
    }

}
