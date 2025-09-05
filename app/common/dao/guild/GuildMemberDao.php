<?php

namespace app\common\dao\guild;

use app\common\dao\BaseDao;
use app\common\model\guild\GuildMemberModel;
use think\db\BaseQuery;

class GuildMemberDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = GuildMemberModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['guild_id']) && $where['guild_id'] !== '', function ($query) use ($where) {
            $query->whereLike('guild_id', $where['guild_id']);
        })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereIn('uuid', function ($query) use ($where) {
                    $query->name('users')->whereLike('mobile', '%' . $where['keywords'] . '%')->field('id');
                });
            })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
;
        return $query;
    }

    /**
     * @return GuildMemberModel
     */
    protected function getModel(): string
    {
        return GuildMemberModel::class;
    }



}
