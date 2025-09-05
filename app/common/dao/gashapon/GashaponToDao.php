<?php

namespace app\common\dao\gashapon;

use app\common\model\gashapon\GashaponModel;
use app\common\model\gashapon\GashaponToModel;
use think\db\BaseQuery;
use app\common\dao\BaseDao;

class GashaponToDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId = null)
    {
        $query = GashaponToModel::getDB()
            ->when(isset($where['number']) && $where['number'] !== '', function ($query) use ($where) {
                $query->where('number', $where['number']);
            })
            ->when(isset($where['count']) && $where['count'] !== '', function ($query) use ($where) {
                $query->where('count', $where['count']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', $where['type']);
            })
            ->when(isset($where['add_time']) && $where['add_time'] !== '', function ($query) use ($where) {
                $this->timeSearchBuild($query, $where['add_time'], 'add_time');
            });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return GashaponToModel::class;
    }
}
