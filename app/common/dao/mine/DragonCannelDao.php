<?php

namespace app\common\dao\mine;

use app\common\dao\BaseDao;
use app\common\model\mine\DragonCannelModel;

class DragonCannelDao extends BaseDao
{

    /**
     * @return DragonCannelModel
     */
    protected function getModel(): string
    {
        return DragonCannelModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        return DragonCannelModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('status', $where['status']);
            });
    }
}
