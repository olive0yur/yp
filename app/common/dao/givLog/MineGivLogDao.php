<?php

namespace app\common\dao\givLog;

use app\common\dao\BaseDao;
use app\common\model\givLog\GivLogModel;
use app\common\model\givLog\MineGivLogModel;
use think\db\BaseQuery;

class MineGivLogDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = MineGivLogModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
        ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', $where['type']);
        })
        ->when(isset($where['balance_type']) && $where['balance_type'] !== '', function ($query) use ($where) {
            $query->where('balance_type', $where['balance_type']);
        })
;

        return $query;
    }

    /**
     * @return GivLogModel
     */
    protected function getModel(): string
    {
        return MineGivLogModel::class;
    }



}
