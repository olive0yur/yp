<?php

namespace app\common\dao\system\affiche;

use app\common\dao\BaseDao;
use app\common\model\system\affiche\AfficheFollow;

class AfficheFollowDao extends BaseDao
{
    /**
     * @return AfficheFollow
     */
    protected function getModel(): string
    {
        return AfficheFollow::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = AfficheFollow::getDB();
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if (isset($where['uuid']) && $where['uuid'] !== '') {
            $query->where('uuid', (int)$where['uuid']);
        }
         if (isset($where['affiche_id']) && $where['affiche_id'] !== '') {
             $query->where('affiche_id', (int)$where['affiche_id']);
         }
        return $query;
    }
}
