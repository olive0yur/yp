<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\pool\PoolFollowModel;
use app\common\model\pool\PoolOrderNoModel;
use think\db\BaseQuery;

class PoolFollowDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = PoolFollowModel::getDB()

            ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
                $query->where('goods_id', $where['goods_id']);
            })
            ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
                $query->where('buy_type', $where['buy_type']);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
        ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return PoolFollowModel::class;
    }
}
