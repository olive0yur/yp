<?php

namespace app\common\dao\rabbit;

use app\common\dao\BaseDao;
use app\common\model\rabbit\ToysGearLevelModel;
use app\common\model\rabbit\ToysGearModel;
use app\common\model\sign\SignSetModel;
use think\db\BaseQuery;

class ToysGearLevelDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ToysGearLevelModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
            $query->whereLike('title', '%' . trim($where['keywords']) . '%');
        })
        ->when(isset($where['level']) && $where['level'] !== '', function ($query) use ($where) {
            $query->where('level', $where['level']);
        })

       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ToysGearLevelModel::class;
    }




}
