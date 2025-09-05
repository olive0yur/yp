<?php

namespace app\common\dao\turntable;

use app\common\dao\BaseDao;
use app\common\model\turntable\TurntableGoodsModel;
use app\common\model\turntable\UsersTurnModel;
use think\db\BaseQuery;

class UsersTurnDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = UsersTurnModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })

            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['turn_id']) && $where['turn_id'] !== '', function ($query) use ($where) {
                $query->where('turn_id', $where['turn_id']);
            })
          ;
        return $query;
    }

    /**
     * @return TurntableGoodsModel
     */
    protected function getModel(): string
    {
        return UsersTurnModel::class;
    }


}
