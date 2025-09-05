<?php

namespace app\common\repositories\refund;

use app\common\dao\refund\RefundStatusDao;
use app\common\repositories\BaseRepository;

class RefundStatusRepository extends BaseRepository
{

    public function __construct(RefundStatusDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 添加退款订单日志
     *
     * @param $refund_order_id
     * @param $ordchange_typeer_id
     * @param $change_message
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function addLog($refund_order_id, $change_type, $change_message)
    {
        return $this->dao->create(compact('refund_order_id', 'change_type', 'change_message'));
    }
}