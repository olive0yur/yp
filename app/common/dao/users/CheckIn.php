<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\CheckIn as CheckInModel;
use app\common\model\users\UsersLabelModel;

class CheckIn extends BaseDao
{

    /**
     * @return CheckInModel
     */
    protected function getModel(): string
    {
        return CheckInModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = CheckInModel::getDB()
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
                $query->whereIn('user_id', function ($query) use ($where) {
                    $query->name('users')->whereLike('mobile', '%' . $where['keywords'] . '%')->field('id');
                });
            });
        return $query;
    }

}
