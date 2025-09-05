<?php

namespace app\common\dao\union;

use think\db\BaseQuery;
use app\common\dao\BaseDao;
use app\common\model\company\Company;
use app\common\model\union\UnionBrandModel;

class UnionBrandDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId= null)
    {
        $query = UnionBrandModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('name', '%' . trim($where['name']) . '%');
        })
        ->when(isset($where['is_type']) && $where['is_type'] !== '', function ($query) use ($where) {
            $query->where('is_type', $where['is_type']);
        });
        return $query;
    }

    /**
     * @return Company
     */
    protected function getModel(): string
    {
        return UnionBrandModel::class;
    }


}
