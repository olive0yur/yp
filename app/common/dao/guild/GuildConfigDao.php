<?php

namespace app\common\dao\guild;

use app\common\dao\BaseDao;
use app\common\model\guild\GuildConfigModel;
use think\db\BaseQuery;

class GuildConfigDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = GuildConfigModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
        })
        ->when(isset($where['level']) && $where['level'] !== '', function ($query) use ($where) {
            $query->where('level', $where['level']);
        })
        ->when(isset($where['gold']) && $where['gold'] !== '', function ($query) use ($where) {
            $query->where('gold', $where['gold']);
        })
        ->when(isset($where['people']) && $where['people'] !== '', function ($query) use ($where) {
                $query->where('people', $where['people']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
;
        return $query;
    }

    /**
     * @return GuildConfigModel
     */
    protected function getModel(): string
    {
        return GuildConfigModel::class;
    }



}
