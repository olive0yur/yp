<?php

namespace app\common\dao\gashapon;

use app\common\model\gashapon\GashaponModel;
use think\db\BaseQuery;
use app\common\dao\BaseDao;

class GashaponDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId = null)
    {
        $query = GashaponModel::getDB()
            ->when(isset($where['mode']) && $where['mode'] !== '', function ($query) use ($where) {
                $query->where('mode', $where['mode']);
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
        return GashaponModel::class;
    }
}
