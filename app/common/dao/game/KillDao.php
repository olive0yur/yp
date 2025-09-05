<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\KillModel;
use app\common\model\givLog\GivLogModel;
use think\db\BaseQuery;

class KillDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where, int $companyId = null)
    {
        $query = KillModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['batch_no']) && $where['batch_no'] !== '', function ($query) use ($where) {
                $query->where('batch_no', $where['batch_no']);
            })
            ->when(isset($where['coinType']) && $where['coinType'] !== '', function ($query) use ($where) {
                $query->where('coinType', $where['coinType']);
            })
            ->when(isset($where['gameName']) && $where['gameName'] !== '', function ($query) use ($where) {
                $query->where('gameName', $where['gameName']);
            })
            ->when(isset($where['reg_time']) && $where['reg_time'] !== '', function ($query) use ($where) {
                $times = explode(' - ', trim($where['reg_time']));
                $query->where('gameDate', 'between', [$times[0], $times[1]]);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', $where['type']);
            })
            ->when(isset($where['requestNo']) && $where['requestNo'] !== '', function ($query) use ($where) {
                $query->where('requestNo', $where['requestNo']);
            });

        return $query;
    }

    /**
     * @return GivLogModel
     */
    protected function getModel(): string
    {
        return KillModel::class;
    }


}
