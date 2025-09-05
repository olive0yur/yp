<?php

namespace app\common\dao\guild;

use app\common\dao\BaseDao;
use app\common\model\guild\GuildModel;
use think\db\BaseQuery;

class GuildDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = GuildModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['guild_name']) && $where['guild_name'] !== '', function ($query) use ($where) {
            $query->whereLike('guild_name', $where['guild_name']);
        })

        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
            ->when(isset($where['level']) && $where['level'] !== '', function ($query) use ($where) {
                $query->whereLike('level', $where['level']);
            })
;
        return $query;
    }

    /**
     * @return GuildModel
     */
    protected function getModel(): string
    {
        return GuildModel::class;
    }



}
