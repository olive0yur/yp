<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\company\Company;
use app\common\model\pool\PoolBrandModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\pool\PoolVolumnModel;
use think\db\BaseQuery;

class PoolVolumnDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId= null)
    {
        $query = PoolVolumnModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })

        ->when(isset($where['pool_id']) && $where['pool_id'] !== '', function ($query) use ($where) {
            $query->where('pool_id', $where['pool_id']);
        })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
        $query->where('uuid', $where['uuid']);
         })
        ;
        return $query;
    }

    /**
     * @return Company
     */
    protected function getModel(): string
    {
        return PoolVolumnModel::class;
    }


}
