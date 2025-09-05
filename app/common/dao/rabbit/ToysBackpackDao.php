<?php

namespace app\common\dao\rabbit;

use app\common\dao\BaseDao;
use app\common\model\rabbit\ToysBackpackModel;
use app\common\model\rabbit\ToysGearLevelModel;
use app\common\model\rabbit\ToysGearModel;
use app\common\model\rabbit\ToysLevelModel;
use app\common\model\sign\SignSetModel;
use think\db\BaseQuery;

class ToysBackpackDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ToysBackpackModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where) {
            $query->where('uuid', $where['uuid']);
        })
        ->when(isset($where['gear_id']) && $where['gear_id'] !== '', function ($query) use ($where) {
            $query->where('gear_id', $where['gear_id']);
        })
        ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('status', $where['status']);
        })
        ->when(isset($where['is_use']) && $where['is_use'] !== '', function ($query) use ($where) {
            $query->where('is_use', $where['is_use']);
        })
        ->when(isset($where['rl']) && $where['rl'] !== '', function ($query) use ($where) {
            $query->where('rl', $where['rl']);
        })
       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ToysBackpackModel::class;
    }




}
