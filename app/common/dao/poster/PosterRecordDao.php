<?php

namespace app\common\dao\poster;

use app\common\dao\BaseDao;
use app\common\model\poster\PosterRecordModel;

class PosterRecordDao extends BaseDao
{

    /**
     * @return PosterRecordModel
     */
    protected function getModel(): string
    {
        return PosterRecordModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = PosterRecordModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['site_id']) && $where['site_id'] !== '', function ($query) use ($where) {
                $query->where('site_id', (int)$where['site_id']);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where) {
                $query->where('is_show', (int)$where['is_show']);
            });
        return $query;
    }
}