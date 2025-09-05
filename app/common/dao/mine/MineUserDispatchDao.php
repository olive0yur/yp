<?php

namespace app\common\dao\mine;

use app\common\dao\BaseDao;
use app\common\model\mine\MineUserDispatchModel;

class MineUserDispatchDao extends BaseDao
{

    /**
     * @return MineUserDispatchModel
     */
    protected function getModel(): string
    {
        return MineUserDispatchModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        return MineUserDispatchModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['mine_id']) && $where['mine_id'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('mine_id', $where['mine_id']);
            })
            ->when(isset($where['pool_id']) && $where['pool_id'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('pool_id', $where['pool_id']);
            })
            ->when(isset($where['user_pool_id']) && $where['user_pool_id'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('user_pool_id', $where['user_pool_id']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('type', $where['type']);
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
