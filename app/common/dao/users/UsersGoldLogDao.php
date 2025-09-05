<?php

namespace app\common\dao\users;

use app\common\model\users\UsersFoodLogModel;
use app\common\model\users\UsersGoldLogModel;
use think\db\BaseQuery;
use app\common\dao\BaseDao;

class UsersGoldLogDao extends BaseDao
{

    /**
     * @return UsersGoldLogModel
     */
    protected function getModel(): string
    {
        return UsersGoldLogModel::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = UsersGoldLogModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where) {
                $query->whereIn('user_id', function ($query) use ($where) {
                    $query->name('users')->whereLike('mobile', '%' . $where['mobile'] . '%')->field('id');
                });
            })
            ->when(isset($where['log_type']) && $where['log_type'] !== '', function ($query) use ($where) {
                $query->where('log_type',  trim($where['log_type']));
            })
            ->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use ($where) {
                $query->where('user_id',  trim($where['user_id']));
            })
            ->when(isset($where['is_frends']) && $where['is_frends'] !== '', function ($query) use ($where) {
                $query->where('is_frends',  $where['is_frends']);
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->whereLike('remark', '%' . trim($where['keyword']) . '%');
            });
        return $query;
    }
}
