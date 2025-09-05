<?php

namespace app\common\dao\givLog;

use app\common\dao\BaseDao;
use app\common\model\givLog\GivLogModel;
use think\db\BaseQuery;

class GivLogDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = GivLogModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
        ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
            $query->where('buy_type', $where['buy_type']);
        })
       ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
                $query->where('goods_id', $where['goods_id']);
            })
        ->when(isset($where['sell_id']) && $where['sell_id'] !== '', function ($query) use ($where) {
                $query->where('sell_id', $where['sell_id']);
            })
        ->when(isset($where['to_uuid']) && $where['to_uuid'] !== '', function ($query) use ($where) {
            $query->where('to_uuid', $where['to_uuid']);
        })
        ->when(isset($where['order_no']) && $where['order_no'] !== '', function ($query) use ($where) {
            $query->where('order_no', $where['order_no']);
        })
        ;

        return $query;
    }

    /**
     * @return GivLogModel
     */
    protected function getModel(): string
    {
        return GivLogModel::class;
    }



}
