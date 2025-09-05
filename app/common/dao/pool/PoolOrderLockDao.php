<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\pool\PoolOrderLockModel;
use app\common\repositories\users\UsersRepository;
use think\db\BaseQuery;

class PoolOrderLockDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = PoolOrderLockModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
                $query->where('order_id', $where['order_id']);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })

            ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
                $query->where('buy_type', $where['buy_type']);
            })
            ->when(isset($where['is_mark']) && $where['is_mark'] !== '', function ($query) use ($where) {
                $query->where('is_mark', $where['is_mark']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('remark', 'like', '%' . trim($where['keywords']) . '%');
            })
            ->when(isset($where['username']) && $where['username'] !== '', function ($query) use ($where,$companyId) {
                $query->where('uuid', 'in', function ($query) use ($where) {
                    $query->name('users')->whereLike('mobile|nickname', '%' . $where['username'] . '%')->field('id');
                });
            })
            ->when(isset($where['add_time']) && $where['add_time'] !== '', function ($query) use ($where) {
                $this->timeSearchBuild($query, $where['add_time'], 'lock_time');
            });
        return $query;
    }


    /**
     * @return
     */
    protected function getModel(): string
    {
        return PoolOrderLockModel::class;
    }


}
