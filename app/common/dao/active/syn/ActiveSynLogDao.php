<?php

namespace app\common\dao\active\syn;

use app\common\dao\BaseDao;
use app\common\model\active\syn\ActiveSynLogModel;
use think\db\BaseQuery;

class ActiveSynLogDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveSynLogModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
            })
        ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
        $query->where('status',$where['status']);
       })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid',$where['uuid']);
            })
            ->when(isset($where['userNo']) && $where['userNo'] !== '', function ($query) use ($where) {
                $query->where('userNo',$where['userNo']);
            })
        ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveSynLogModel::class;
    }


}
