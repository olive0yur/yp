<?php

namespace app\common\dao\users;

use app\common\model\users\UsersFoodTimeModel;
use think\db\BaseQuery;
use app\common\dao\BaseDao;

class UsersFoodTimeDao extends BaseDao
{

    /**
     * @return UsersFoodTimeModel
     */
    protected function getModel(): string
    {
        return UsersFoodTimeModel::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = UsersFoodTimeModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })


            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid',  trim($where['uuid']));
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status',  trim($where['status']));
            })
             ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
        $query->where('type',  trim($where['type']));
    })
       ;
        return $query;
    }
}
