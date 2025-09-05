<?php

namespace app\common\dao\active\syn;

use app\common\dao\BaseDao;
use app\common\model\active\syn\ActiveSynModel;
use think\db\BaseQuery;

class ActiveSynDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveSynModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
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
        return ActiveSynModel::class;
    }


}
