<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersLabelModel;

class UsersLabelDao extends BaseDao
{

    /**
     * @return UsersLabelModel
     */
    protected function getModel(): string
    {
        return UsersLabelModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = UsersLabelModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where) {
                $query->where('is_show', (int)$where['is_show']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('name', 'like', '%' . trim($where['keywords']) . '%');
            });
        return $query;
    }

}
