<?php

namespace app\common\dao\mine;

use app\common\dao\BaseDao;
use app\common\model\mine\MineDispatchModel;

class MineDispatchDao extends BaseDao
{

    /**
     * @return MineDispatchModel
     */
    protected function getModel(): string
    {
        return MineDispatchModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        return MineDispatchModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['frend_uuid']) && $where['frend_uuid'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('frend_uuid', $where['frend_uuid']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('status', $where['status']);
            })

            ;
    }
}
