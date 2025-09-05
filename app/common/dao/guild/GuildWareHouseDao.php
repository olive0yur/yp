<?php

namespace app\common\dao\guild;

use app\common\dao\BaseDao;
use app\common\model\guild\GuildWareHouseModel;
use think\db\BaseQuery;

class GuildWareHouseDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = GuildWareHouseModel::getDB()
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
     * @return GuildWareHouseModel
     */
    protected function getModel(): string
    {
        return GuildWareHouseModel::class;
    }



}
