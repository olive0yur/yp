<?php

namespace app\common\model\refund;

use app\common\model\BaseModel;
use app\common\model\order\OrderModel;

class RefundOrderModel extends BaseModel
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
        return 'refund_order';
    }

    public function orderInfo()
    {
        return $this->hasOne(OrderModel::class, 'id', 'order_id');
    }

    public function refundProduct()
    {
        return $this->hasMany(RefundProductModel::class, 'refund_order_id', 'id');
    }

    public function refundStatus()
    {
        return $this->hasMany(RefundStatusModel::class, 'refund_order_id', 'id');
    }

    public function getPicsAttr($val)
    {
        return $val ? explode(',', $val) : [];
    }

    public function setPicsAttr($val)
    {
        return $val ? implode(',', $val) : '';
    }

}
