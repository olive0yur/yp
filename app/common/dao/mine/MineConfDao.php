<?php

namespace app\common\dao\mine;

use app\common\dao\BaseDao;
use app\common\model\mine\MineConfModel;
use app\common\model\mine\MineModel;

class MineConfDao extends BaseDao
{

    /**
     * @return MineConfModel
     */
    protected function getModel(): string
    {
        return MineConfModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        return MineConfModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
          ;
    }
}
