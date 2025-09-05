<?php

namespace app\common\dao\sign;

use app\common\dao\BaseDao;
use app\common\model\sign\SignAbcModel;
use app\common\model\sign\SignBoxModel;
use think\db\BaseQuery;

class SignBoxDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = SignBoxModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', $where['type']);
        })
        ->when(isset($where['debris_id']) && $where['debris_id'] !== '', function ($query) use ($where) {
            $query->where('debris_id', $where['debris_id']);
        })
       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return SignBoxModel::class;
    }
}
