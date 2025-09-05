<?php

namespace app\common\dao\sign;

use app\common\dao\BaseDao;
use app\common\model\sign\SignAbcModel;
use think\db\BaseQuery;

class SignAbcDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = SignAbcModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return SignAbcModel::class;
    }
}
