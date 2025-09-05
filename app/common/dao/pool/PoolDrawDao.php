<?php

namespace app\common\dao\pool;

use app\common\dao\BaseDao;
use app\common\model\company\Company;
use app\common\model\pool\PoolDrawModel;
use app\common\repositories\users\UsersRepository;
use think\db\BaseQuery;

class PoolDrawDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = PoolDrawModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where, $companyId) {
                $uuid = app()->make(UsersRepository::class)->search([], $companyId)->whereLike('mobile|nickname', '%' . trim($where['keywords']) . '%')->column('id');
                $query->whereIn('uuid', $uuid);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', $where['type']);
            })
            ->when(isset($where['pool_id']) && $where['pool_id'] !== '', function ($query) use ($where) {
                $query->where('pool_id', $where['pool_id']);
            })
            ->when(isset($where['is_use']) && $where['is_use'] !== '', function ($query) use ($where) {
                $query->where('is_use', $where['is_use']);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            });

        return $query;
    }

    /**
     * @return Company
     */
    protected function getModel(): string
    {
        return PoolDrawModel::class;
    }


}
