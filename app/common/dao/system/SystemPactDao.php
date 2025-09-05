<?php

namespace app\common\dao\system;

use app\common\dao\BaseDao;
use app\common\model\system\SystemPact;

class SystemPactDao extends BaseDao
{

    /**
     * @return SystemPact
     */
    protected function getModel(): string
    {
        return SystemPact::class;
    }

    public function search(array $where, int $companyId = null)
    {

        $query = SystemPact::getDB();
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }
        if (isset($where['keywords']) && $where['keywords'] !== '') {
            $query->where('content', 'like', '%' . trim($where['keywords']) . '%');
        }
        return $query;
    }

}
