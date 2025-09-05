<?php

namespace app\common\dao\union;

use think\db\BaseQuery;
use app\common\dao\BaseDao;
use app\common\model\company\Company;
use app\common\model\union\UnionConvertModel;

class UnionConvertDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = UnionConvertModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('out_id', 'in', function ($query) use ($where) {
                    $query->name('pool_sale')
                        ->where('title', 'like', '%' . trim($where['keywords']) . '%')
                        ->field('id');
                });
            })
            ->when(isset($where['buy_type']) && $where['buy_type'] !== '', function ($query) use ($where) {
                $query->where('buy_type', $where['buy_type']);
            })
            ->when(isset($where['out_id']) && $where['out_id'] !== '', function ($query) use ($where) {
                $query->where('out_id', $where['out_id']);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where) {
                $query->where('is_show', $where['is_show']);
            });
        return $query;
    }

    /**
     * @return Company
     */
    protected function getModel(): string
    {
        return UnionConvertModel::class;
    }


}
