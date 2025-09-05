<?php

namespace app\common\dao\sign;

use app\common\dao\BaseDao;
use app\common\model\sign\SignSetModel;
use think\db\BaseQuery;

class SignSetDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = SignSetModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['day']) && $where['day'] !== '', function ($query) use ($where) {
            $query->where('day', $where['day']);
        })
        ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', $where['type']);
        })
       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return SignSetModel::class;
    }




}
