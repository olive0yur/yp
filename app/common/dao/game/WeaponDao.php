<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\RoleDebrisModel;
use app\common\model\game\WeaponModel;
use think\db\BaseQuery;

class WeaponDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = WeaponModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['level_id']) && $where['level_id'] !== '', function ($query) use ($where) {
            $query->where('level_id', $where['level_id']);
        })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('title', 'like', '%' . trim($where['keywords']) . '%');
            })
 ;

        return $query;
    }

    /**
     * @return RoleDebrisModel
     */
    protected function getModel(): string
    {
        return RoleDebrisModel::class;
    }



}
