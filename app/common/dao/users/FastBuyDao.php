<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\FastBuyModel;

class FastBuyDao extends BaseDao
{

    /**
     * @return FastBuyModel
     */
    protected function getModel(): string
    {
        return FastBuyModel::class;
    }


    public function search(array $where, int $companyId = null)
    {
        return FastBuyModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
                $query->where(['buy_type'=>$where['buy_type']]);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where(['uuid'=>$where['uuid']]);
            })
            ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
                $query->where(['goods_id'=>$where['goods_id']]);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ;
    }
}
