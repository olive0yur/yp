<?php

namespace app\common\dao\rabbit;

use app\common\dao\BaseDao;
use app\common\model\rabbit\ToysGearLevelModel;
use app\common\model\rabbit\ToysGearModel;
use app\common\model\rabbit\ToysLevelModel;
use app\common\model\sign\SignSetModel;
use think\db\BaseQuery;

class ToysLevelDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = ToysLevelModel::getDB()
        ->when($companyId !== null, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->when(isset($where['lv']) && $where['lv'] !== '', function ($query) use ($where) {
            $query->where('lv', $where['lv']);
        })

       ;
        return $query;
    }

    /**
     * @return
     */
    protected function getModel(): string
    {
        return ToysLevelModel::class;
    }




}
