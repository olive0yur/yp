<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\RoleDebrisModel;
use think\db\BaseQuery;

class RoleDebrisDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = RoleDebrisModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['weapon_id']) && $where['weapon_id'] !== '', function ($query) use ($where) {
            $query->where('weapon_id', $where['weapon_id']);
        })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('name', 'like', '%' . trim($where['keywords']) . '%');
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
