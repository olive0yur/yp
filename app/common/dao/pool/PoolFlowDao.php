<?php

namespace app\common\dao\pool;

use think\db\BaseQuery;
use app\common\dao\BaseDao;
use app\common\model\pool\PoolFlowModel;

class PoolFlowDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where)
    {
        $query = PoolFlowModel::getDB()
            ->when(isset($where['pool_id']) && $where['pool_id'] !== '', function ($query) use ($where) {
                $query->where('pool_id', $where['pool_id']);
            })
            ->when(isset($where['no']) && $where['no'] !== '', function ($query) use ($where) {
                $query->where('no', $where['no']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('nickname', 'like', '%' . trim($where['keywords']) . '%');
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['reg_time']) && $where['reg_time'] !== '', function ($query) use ($where) {
                $this->timeSearchBuild($query, $where['reg_time'], 'add_time');
            });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return PoolFlowModel::class;
    }
}
