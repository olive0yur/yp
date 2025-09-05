<?php

namespace app\common\dao\wall;

use app\common\dao\BaseDao;
use app\common\model\wall\WallModel;

class WallDao extends BaseDao
{

    public function search(array $where, int $companyId = null)
    {
        return WallModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', (int)$where['uuid']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', (int)$where['status']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('code',$where['keywords']);
            })
            ;
    }

    /**
     * @return WallModel
     */
    protected function getModel(): string
    {
        return WallModel::class;
    }

}
