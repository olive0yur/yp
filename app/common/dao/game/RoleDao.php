<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\KillModel;
use app\common\model\game\RoleModel;
use app\common\model\givLog\GivLogModel;
use think\db\BaseQuery;

class RoleDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = RoleModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('name', 'like', '%' . trim($where['keywords']) . '%');
            })
 ;

        return $query;
    }

    /**
     * @return GivLogModel
     */
    protected function getModel(): string
    {
        return RoleModel::class;
    }



}
