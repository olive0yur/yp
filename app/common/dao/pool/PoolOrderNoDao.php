<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\pool\PoolOrderNoModel;
use think\db\BaseQuery;

class PoolOrderNoDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = PoolOrderNoModel::getDB()

            ->when(isset($where['pool_id']) && $where['pool_id'] !== '', function ($query) use ($where) {
                $query->where('pool_id', $where['pool_id']);
            })
            ->when(isset($where['no']) && $where['no'] !== '', function ($query) use ($where) {
                $query->where('no', $where['no']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
        ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return PoolOrderNoModel::class;
    }
}
