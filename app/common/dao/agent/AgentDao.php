<?php

namespace app\common\dao\agent;

use app\common\dao\BaseDao;
use app\common\model\agent\AgentModel;

class AgentDao extends BaseDao
{

    /**
     * @return AgentModel
     */
    protected function getModel(): string
    {
        return AgentModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        $query = AgentModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereIn('uuid', function ($query) use ($where) {
                    $query->name('users')->whereLike('mobile', '%' . $where['keywords'] . '%')->field('id');
                });
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['agent']) && $where['agent'] !== '', function ($query) use ($where) {
                $query->where('agent', $where['agent']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['wechat']) && $where['wechat'] !== '', function ($query) use ($where) {
                $query->where('wechat', $where['wechat']);
            })

        ;

        return $query;
    }


}