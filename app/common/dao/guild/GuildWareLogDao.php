<?php

namespace app\common\dao\guild;

use app\common\dao\BaseDao;
use app\common\model\guild\GuildWareLogModel;
use think\db\BaseQuery;

class GuildWareLogDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = GuildWareLogModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['guild_id']) && $where['guild_id'] !== '', function ($query) use ($where) {
            $query->whereLike('guild_id', $where['guild_id']);
        })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->whereLike('type', $where['type']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->whereLike('status', $where['status']);
            })

        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
;
        return $query;
    }

    /**
     * @return GuildWareLogModel
     */
    protected function getModel(): string
    {
        return GuildWareLogModel::class;
    }



}
