<?php

namespace app\common\dao\active\syn;

use app\common\dao\BaseDao;
use app\common\model\active\syn\ActiveSynLogModel;
use app\common\model\active\syn\ActiveSynUserModel;
use think\db\BaseQuery;

class ActiveSynUserDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveSynUserModel::getDB()
        ->when(isset($where['syn_id']) && $where['syn_id'] !== '', function ($query) use ($where) {
            $query->where('syn_id',$where['syn_id']);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid',$where['uuid']);
        })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status',$where['status']);
            });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveSynUserModel::class;
    }


}
