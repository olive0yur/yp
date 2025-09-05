<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\RoleDebrisModel;
use app\common\model\game\UsersRoleModel;
use app\common\model\game\WeaponDebrisModel;
use app\common\model\game\WeaponModel;
use think\db\BaseQuery;

class UsersRoleDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = UsersRoleModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
        ->when(isset($where['role_id']) && $where['role_id'] !== '', function ($query) use ($where) {
            $query->where('role_id', $where['role_id']);
        })
 ;

        return $query;
    }

    /**
     * @return RoleDebrisModel
     */
    protected function getModel(): string
    {
        return UsersRoleModel::class;
    }



}
