<?php

namespace app\common\dao\system;

use app\common\dao\BaseDao;
use app\common\model\system\RegionModel;

class RegionDao extends BaseDao
{

    /**
     * @return RegionModel
     */
    protected function getModel(): string
    {
        return RegionModel::class;
    }

    public function search(array $where)
    {
        return RegionModel::getDB()->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use ($where) {
            $query->where('pid', $where['pid']);
        });
    }

}
