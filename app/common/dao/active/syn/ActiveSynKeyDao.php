<?php

namespace app\common\dao\active\syn;

use app\common\dao\BaseDao;
use app\common\model\active\syn\ActiveSynKeyModel;
use app\common\model\active\syn\ActiveSynModel;
use think\db\BaseQuery;

class ActiveSynKeyDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveSynKeyModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
            })
        ->when(isset($where['syn_id']) && $where['syn_id'] !== '', function ($query) use ($where) {
        $query->where('syn_id',$where['syn_id']);
    });
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveSynKeyModel::class;
    }


}
