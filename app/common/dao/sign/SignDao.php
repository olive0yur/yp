<?php

namespace app\common\dao\sign;

use app\common\dao\BaseDao;
use app\common\model\sign\SignModel;
use think\db\BaseQuery;

class SignDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = SignModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
        ->when(isset($where['day']) && $where['day'] !== '', function ($query) use ($where) {
            $query->where('day', $where['day']);
        })
       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return SignModel::class;
    }




}
