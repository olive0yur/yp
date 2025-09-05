<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\KillModel;
use app\common\model\game\LevelModel;
use app\common\model\game\LevelTeamModel;
use app\common\model\game\RoleModel;
use app\common\model\givLog\GivLogModel;
use think\db\BaseQuery;

class LevelTeamDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = LevelTeamModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['level']) && $where['level'] !== '', function ($query) use ($where) {
                $query->where('level', $where['level']);
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
        return LevelTeamModel::class;
    }



}
