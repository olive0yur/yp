<?php

namespace app\common\dao\active\syn;

use app\common\dao\BaseDao;
use app\common\model\active\syn\ActiveSynInfoModel;
use think\db\BaseQuery;

class ActiveSynInfoDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveSynInfoModel::getDB()

        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['is_target']) && $where['is_target'] !== '', function ($query) use ($where) {
            $query->where(['is_target'=>$where['is_target']]);
        })
            ->when(isset($where['target_type']) && $where['target_type'] !== '', function ($query) use ($where) {
                $query->where(['target_type'=>$where['target_type']]);
            })
        ->when(isset($where['syn_id']) && $where['syn_id'] !== '', function ($query) use ($where) {
            $query->where(['syn_id'=>$where['syn_id']]);
        })
        ->when(isset($where['goods_id']) && $where['goods_id'] !== '', function ($query) use ($where) {
            $query->where(['goods_id '=>$where['goods_id']]);
         });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveSynInfoModel::class;
    }


}
