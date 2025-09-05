<?php

namespace app\common\dao\turntable;

use app\common\dao\BaseDao;
use app\common\model\turntable\TurntableGoodsModel;
use think\db\BaseQuery;

class TurntableGoodsDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = TurntableGoodsModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })

            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', $where['type']);
            })
            ->when(isset($where['debris_id']) && $where['debris_id'] !== '', function ($query) use ($where) {
                $query->where('debris_id', $where['debris_id']);
            })
          ;

        return $query;
    }

    /**
     * @return TurntableGoodsModel
     */
    protected function getModel(): string
    {
        return TurntableGoodsModel::class;
    }


}
