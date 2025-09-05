<?php

namespace app\common\dao\active;

use app\common\dao\BaseDao;
use app\common\model\active\ActiveModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolSaleModel;
use think\db\BaseQuery;

class ActiveDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ActiveModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
            })
        ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
        $query->where('status',$where['status']);
         })
            ->when(isset($where['active_type']) && $where['active_type'] !== '', function ($query) use ($where) {
                $query->where('active_type',$where['active_type']);
            })
        ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ActiveModel::class;
    }


}
