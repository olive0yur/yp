<?php

namespace app\common\dao\rabbit;

use app\common\dao\BaseDao;
use app\common\model\rabbit\ToysGearModel;
use app\common\model\sign\SignSetModel;
use think\db\BaseQuery;

class ToysGearDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ToysGearModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
        $query->where('company_id', $companyId);
       })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
        })
        ->when(isset($where['gw']) && $where['gw'] !== '', function ($query) use ($where) {
            $query->where('gw', $where['gw']);
        })
        ->when(isset($where['level_id']) && $where['level_id'] !== '', function ($query) use ($where) {
            $query->where('level_id', $where['level_id']);
        })

       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ToysGearModel::class;
    }




}
