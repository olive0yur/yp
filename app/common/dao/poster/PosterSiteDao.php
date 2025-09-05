<?php

namespace app\common\dao\poster;

use app\common\dao\BaseDao;
use app\common\model\poster\PosterSiteModel;

class PosterSiteDao extends BaseDao
{

    /**
     * @return PosterSiteModel
     */
    protected function getModel(): string
    {
        return PosterSiteModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = PosterSiteModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where) {
                $query->where('is_show', (int)$where['is_show']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('site_name', 'like', '%' . trim($where['keywords']) . '%');
            });
        return $query;
    }
}