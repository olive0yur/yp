<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\company\Company;
use app\common\model\pool\PoolBrandModel;
use app\common\model\pool\PoolModeModel;
use app\common\model\pool\PoolSaleModel;
use think\db\BaseQuery;

class PoolModeDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = PoolModeModel::getDB();
        if (isset($where['type']) && $where['type'] !== '') {
            $query->where('type', '=',$where['type']);
        }
        if (isset($where['pool_id']) && $where['pool_id'] !== '') {
            $query->where('pool_id', '=',$where['pool_id']);
        }
        return $query;
    }

    /**
     * @return Company
     */
    protected function getModel(): string
    {
        return PoolModeModel::class;
    }


}
