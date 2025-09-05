<?php

namespace app\common\dao\rabbit;

use app\common\dao\BaseDao;
use app\common\model\rabbit\ToysGearModel;
use app\common\model\rabbit\ToysTaskModel;
use app\common\model\sign\SignSetModel;
use think\db\BaseQuery;

class ToysTaskDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = ToysTaskModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['num']) && $where['num'] !== '', function ($query) use ($where) {
                $query->where('num', $where['num']);
            })
            ->when(isset($where['price']) && $where['price'] !== '', function ($query) use ($where) {
                $query->where('price', $where['price']);
            });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ToysTaskModel::class;
    }


}
