<?php

namespace app\common\dao\mine;

use app\common\dao\BaseDao;
use app\common\model\mine\MineModel;

class MineDao extends BaseDao
{

    /**
     * @return MineModel
     */
    protected function getModel(): string
    {
        return MineModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        return MineModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where,$companyId)  {
                $query->whereLike('name','%'.$where['keywords'].'%');
            })
            ->when(isset($where['level']) && $where['level'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('level', $where['level']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('status', $where['status']);
            });
    }
}
