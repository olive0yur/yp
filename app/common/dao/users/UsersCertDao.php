<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersCertModel;

class UsersCertDao extends BaseDao
{

    /**
     * @return UsersCertModel
     */
    protected function getModel(): string
    {
        return UsersCertModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = UsersCertModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['cert_status']) && $where['cert_status'] !== '', function ($query) use ($where) {
                $query->where('cert_status', (int)$where['cert_status']);
            })
            ->when(isset($where['number']) && $where['number'] !== '', function ($query) use ($where) {
                $query->where('number', (int)$where['number']);
            })
            ->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where) {
                $query->where('id', 'in', function ($query) use ($where) {
                    $query->name('users')->whereLike('mobile|nickname', '%' . $where['mobile'] . '%')->field('cert_id');
                });
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('username|number|remark', '%' . trim($where['keywords']) . '%');
            })
            ->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use ($where) {
                $query->where('user_id', (int)$where['user_id']);
            })->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                $query->where('id', (int)$where['id']);
            });
        return $query;
    }

}
