<?php

namespace app\common\dao\active\syn;

use app\common\dao\BaseDao;
use app\common\model\active\syn\ActiveSynMaterInfoModel;
use think\db\BaseQuery;

class ActiveSynMaterInfoDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveSynMaterInfoModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['syn_id']) && $where['syn_id'] !== '', function ($query) use ($where) {
            $query->where('syn_id', $where['syn_id']);
            })
        ->when(isset($where['pool_id']) && $where['pool_id'] !== '', function ($query) use ($where) {
            $query->where('pool_id', $where['pool_id']);
            })
            ->when(isset($where['key_id']) && $where['key_id'] !== '', function ($query) use ($where) {
                $query->where('key_id', $where['key_id']);
            })
        ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveSynMaterInfoModel::class;
    }


}
