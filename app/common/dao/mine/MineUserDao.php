<?php

namespace app\common\dao\mine;

use app\common\dao\BaseDao;
use app\common\model\mine\MineUserModel;

class MineUserDao extends BaseDao
{

    /**
     * @return MineUserModel
     */
    protected function getModel(): string
    {
        return MineUserModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        return MineUserModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where,$companyId)  {
                if(is_array($where['uuid'])){
                    $query->whereIn('uuid', $where['uuid']);
                }else{
                    $query->where('uuid', $where['uuid']);
                }
            })
            ->when(isset($where['mine_id']) && $where['mine_id'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('mine_id', $where['mine_id']);
            })
            ->when(isset($where['level']) && $where['level'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('level', $where['level']);
            })

            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('status', $where['status']);
            })

            ;
    }
}
