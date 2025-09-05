<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\RoleDebrisModel;
use app\common\model\game\UsersKnspModel;
use app\common\model\game\WeaponDebrisModel;
use app\common\model\game\WeaponModel;
use think\db\BaseQuery;

class UsersKnspDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = UsersKnspModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
        ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', $where['type']);
        })
        ->when(isset($where['of']) && $where['of'] !== '', function ($query) use ($where) {
            $query->where('of', $where['of']);
        })
 ;

        return $query;
    }

    /**
     * @return RoleDebrisModel
     */
    protected function getModel(): string
    {
        return UsersKnspModel::class;
    }



}
